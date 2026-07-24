<?php
declare(strict_types=1);

namespace App\Services\Company;

use App\Core\Service;

use App\Models\CompanyInviteAcceptModel;
use App\Services\Shared\CompanySchemaService;
use App\Services\Shared\ReferralService;

final class InviteAcceptService extends Service
{
    private \PDO $pdo;
    private CompanyInviteAcceptModel $model;
    private ReferralService $referrals;
    private CompanySchemaService $companies;

    public function __construct(
        \PDO $pdo,
        CompanyInviteAcceptModel $model,
        ReferralService $referrals,
        CompanySchemaService $companies
    )
    {
        $this->pdo = $pdo;
        $this->model = $model;
        $this->referrals = $referrals;
        $this->companies = $companies;
    }

    public function buildInitialState(string $token): array
    {
        $this->companies->ensureFullSchema();

        $state = [
            'errors' => [],
            'successMessage' => '',
            'invite' => null,
            'token' => $token,
            'firstName' => '',
            'lastName' => '',
            'guestEmail' => '',
        ];

        if ($token === '') {
            $state['errors'][] = 'Token manquant.';
            return $state;
        }

        try {
            $invite = $this->model->findInviteByTokenHash(hash('sha256', $token));
            if (!$invite) {
                $state['errors'][] = 'Invitation introuvable.';
                return $state;
            }

            $status = (string) ($invite['status'] ?? 'pending');
            if ((string) ($invite['company_status'] ?? '') !== 'active') {
                $state['errors'][] = 'Entreprise inactive.';
            }

            if ($status === 'pending') {
                $expiresAt = (string) ($invite['expires_at'] ?? '');
                if ($expiresAt !== '') {
                    $expTs = strtotime($expiresAt) ?: 0;
                    if ($expTs > 0 && $expTs < time()) {
                        $this->model->markInviteStatus((int) ($invite['id'] ?? 0), 'expired');
                        $invite['status'] = 'expired';
                        $status = 'expired';
                    }
                }
            }

            if ($status === 'revoked') {
                $state['errors'][] = 'Invitation annulée.';
            } elseif ($status === 'expired') {
                $state['errors'][] = 'Invitation expirée.';
            } elseif ($status === 'accepted') {
                $state['successMessage'] = 'Invitation déjà acceptée.';
            } elseif ($status === 'awaiting_approval') {
                $state['successMessage'] = 'Demande de rattachement envoyée. En attente de validation administrateur.';
            }

            $state['invite'] = $invite;
        } catch (\Throwable $e) {
            $state['errors'][] = 'Invitation introuvable.';
        }

        return $state;
    }

    public function submit(array $state, ?array $currentUser, array $post): array
    {
        $invite = $state['invite'] ?? null;
        if (!$invite || !empty($state['errors'])) {
            $state['errors'][] = 'Invitation invalide.';
            return $state;
        }

        $inviteStatus = (string) ($invite['status'] ?? 'pending');
        if (!in_array($inviteStatus, ['pending', 'awaiting_approval'], true)) {
            $state['errors'][] = 'Invitation déjà utilisée.';
            return $state;
        }
        if ($inviteStatus === 'awaiting_approval') {
            $state['successMessage'] = 'Demande de rattachement déjà envoyée. En attente de validation administrateur.';
            return $state;
        }

        $inviteMode = $this->normalizeInviteMode((string) ($invite['invite_mode'] ?? 'email'));
        $approvalRequired = !empty($invite['approval_required']) || $inviteMode === 'link';
        $inviteEmail = strtolower((string) ($invite['email'] ?? ''));
        $companyId = (int) ($invite['company_id'] ?? 0);
        $inviteId = (int) ($invite['id'] ?? 0);

        if ($currentUser) {
            try {
                $userId = (int) ($currentUser['id'] ?? 0);
                $userEmail = strtolower((string) ($currentUser['email'] ?? ''));
                $requesterName = trim((string) ($currentUser['first_name'] ?? '') . ' ' . (string) ($currentUser['last_name'] ?? ''));
                $requesterLabel = $requesterName !== '' ? $requesterName : $userEmail;
                if ($inviteMode === 'email' && ($inviteEmail === '' || $userEmail !== $inviteEmail)) {
                    $state['errors'][] = 'Cette invitation par e-mail est réservée à une autre adresse.';
                    return $state;
                }

                $this->pdo->beginTransaction();
                if ($this->model->memberExistsInCompany($companyId, $userId)) {
                    $this->model->markInviteAccepted($inviteId);
                    $this->pdo->commit();
                    $state['successMessage'] = 'Votre compte est déjà rattaché à cette entreprise.';
                    return $state;
                }

                if ($approvalRequired) {
                    $this->model->markInviteAwaitingApproval($inviteId, $userId);
                    $state['successMessage'] = 'Demande envoyée. Un administrateur doit valider votre ajout.';
                } else {
                    $this->model->attachActiveMember($companyId, $userId, 'member');
                    $updatedRoles = $this->mergeRolesFromJson((string) ($currentUser['roles_json'] ?? '[]'), ['company_member']);
                    if ($updatedRoles !== null) {
                        $this->model->updateUserRoles($userId, $updatedRoles);
                    }
                    $this->model->markInviteAccepted($inviteId);
                    $state['successMessage'] = 'Invitation acceptée. Vous êtes ajouté à l’entreprise.';
                }

                $this->pdo->commit();
                if ($approvalRequired) {
                    $this->notifyAdminsInviteApprovalNeeded($invite, $userId, $requesterLabel);
                }
            } catch (\Throwable $e) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                $state['errors'][] = 'Erreur lors de la validation.';
            }

            return $state;
        }

        $firstName = trim((string) ($post['first_name'] ?? ''));
        $lastName = trim((string) ($post['last_name'] ?? ''));
        $guestEmailInput = strtolower(trim((string) ($post['email'] ?? '')));
        $password = (string) ($post['password'] ?? '');
        $confirmPassword = (string) ($post['password_confirm'] ?? '');
        $state['firstName'] = $firstName;
        $state['lastName'] = $lastName;
        $state['guestEmail'] = $guestEmailInput;

        $accountEmail = $inviteMode === 'email' ? $inviteEmail : $guestEmailInput;

        if ($firstName === '' || $lastName === '') {
            $state['errors'][] = 'Prénom et nom obligatoires.';
        }
        if (!filter_var($accountEmail, FILTER_VALIDATE_EMAIL)) {
            $state['errors'][] = $inviteMode === 'email'
                ? 'Invitation par e-mail invalide. Demandez une nouvelle invitation.'
                : 'E-mail invalide.';
        }
        if (strlen($password) < 8) {
            $state['errors'][] = 'Mot de passe : 8 caractères minimum.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $state['errors'][] = 'Le mot de passe doit contenir au moins une lettre majuscule.';
        }
        if (!preg_match('/\d/', $password)) {
            $state['errors'][] = 'Le mot de passe doit contenir au moins un chiffre.';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $state['errors'][] = 'Le mot de passe doit contenir au moins un caractère spécial.';
        }
        if ($password !== $confirmPassword) {
            $state['errors'][] = 'Les mots de passe ne correspondent pas.';
        }
        if (!empty($state['errors'])) {
            return $state;
        }

        try {
            $existing = $this->model->findUserByEmail($accountEmail);
            if ($existing) {
                $state['errors'][] = 'Un compte existe déjà. Connectez-vous pour accepter.';
                return $state;
            }

            $this->pdo->beginTransaction();

            $rolesJson = json_encode($approvalRequired ? ['student'] : ['student', 'company_member']);
            if (!is_string($rolesJson) || $rolesJson === '') {
                $rolesJson = $approvalRequired ? '["student"]' : '["student","company_member"]';
            }
            $userId = $this->model->createActiveVerifiedUser(
                $firstName,
                $lastName,
                $accountEmail,
                password_hash($password, PASSWORD_DEFAULT),
                $rolesJson
            );

            $this->referrals->ensureUserReferralCode($userId);

            if ($approvalRequired) {
                $this->model->markInviteAwaitingApproval($inviteId, $userId);
            } else {
                $this->model->attachActiveMember($companyId, $userId, 'member');
                $this->model->markInviteAccepted($inviteId);
            }

            $this->pdo->commit();
            if ($approvalRequired) {
                $requesterLabel = trim($firstName . ' ' . $lastName);
                if ($requesterLabel === '') {
                    $requesterLabel = $accountEmail;
                }
                $this->notifyAdminsInviteApprovalNeeded($invite, $userId, $requesterLabel);
            }

            $newUser = $this->model->findUserById($userId);
            if ($newUser) {
                $this->loginUser($newUser, false);
            }
            $state['successMessage'] = $approvalRequired
                ? 'Compte créé. Demande envoyée, en attente de validation administrateur.'
                : 'Invitation acceptée. Votre compte est créé.';
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $state['errors'][] = 'Erreur lors de la création du compte.';
        }

        return $state;
    }

    private function normalizeInviteMode(string $value): string
    {
        $mode = strtolower(trim($value));
        if (!in_array($mode, ['email', 'link'], true)) {
            return 'email';
        }
        return $mode;
    }

    /**
     * @param array<int,string> $mustHaveRoles
     */
    private function mergeRolesFromJson(string $rolesJson, array $mustHaveRoles): ?string
    {
        $decoded = json_decode($rolesJson, true);
        $roles = is_array($decoded) ? array_values(array_filter($decoded, static fn ($r): bool => is_string($r) && $r !== '')) : [];
        $original = $roles;
        foreach ($mustHaveRoles as $role) {
            if (!in_array($role, $roles, true)) {
                $roles[] = $role;
            }
        }
        if ($roles !== [] && !in_array('student', $roles, true)) {
            $roles[] = 'student';
        }
        $roles = array_values(array_unique($roles));
        if ($roles === $original) {
            return null;
        }
        $json = json_encode($roles);
        return is_string($json) && $json !== '' ? $json : null;
    }

    /**
     * @param array<string,mixed> $invite
     */
    private function notifyAdminsInviteApprovalNeeded(array $invite, int $requestedUserId, string $requesterLabel): void
    {
        $companyId = (int) ($invite['company_id'] ?? 0);
        if ($companyId < 1 || $requestedUserId < 1) {
            return;
        }

        $companyName = trim((string) ($invite['company_name'] ?? ''));
        if ($companyName === '') {
            $companyName = 'Entreprise';
        }

        try {
            $admins = $this->model->findActiveAdminUsersByCompanyId($companyId);
        } catch (\Throwable $e) {
            $admins = [];
        }
        if ($admins === []) {
            return;
        }

        $safeRequester = trim($requesterLabel);
        if ($safeRequester === '') {
            $safeRequester = 'Un utilisateur';
        }
        $title = 'Demande d’ajout à l’entreprise';
        $body = $safeRequester . " a demand\u{00e9} \u{00e0} rejoindre \"" . $companyName . "\". Ouvrez la section des invitations pour approuver ou refuser.";

        foreach ($admins as $admin) {
            $adminUserId = (int) ($admin['id'] ?? 0);
            if ($adminUserId < 1 || $adminUserId === $requestedUserId) {
                continue;
            }
            try {
                $this->model->insertNotification(
                    $adminUserId,
                    $title,
                    $body,
                    'company/dashboard#invites'
                );
            } catch (\Throwable $e) {
            }
        }
    }
}

