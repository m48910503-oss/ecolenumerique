<?php
declare(strict_types=1);

namespace App\Services\Company\Auth;

use App\Core\Service;
use App\Models\AuthRegisterModel;
use App\Services\Shared\CompanySchemaService;
use App\Services\Shared\ReferralService;
use DateTime;
use Throwable;

final class RegisterService extends Service
{
    private AuthRegisterModel $model;
    private CompanySchemaService $companies;
    private ReferralService $referrals;

    public function __construct(
        AuthRegisterModel $model,
        CompanySchemaService $companies,
        ReferralService $referrals
    ) {
        $this->model = $model;
        $this->companies = $companies;
        $this->referrals = $referrals;
    }

    /**
     * @param array<string,mixed> $post
     * @param array<string,mixed>|null $currentUser
     * @return array{redirectTo:?string,view:array<string,mixed>}
     */
    public function handle(string $method, array $post, ?array $currentUser = null): array
    {
        $errors = [];
        $successMessage = (string) $this->flashGet('success', '');
        $verificationLink = (string) $this->flashGet('verification_link', '');
        $isAuthenticated = is_array($currentUser) && (int) ($currentUser['id'] ?? 0) > 0;
        $isGoogleOnboarding = $isAuthenticated && strtolower((string) ($currentUser['auth_provider'] ?? '')) === 'google';
        $existingCompanyId = $isAuthenticated ? $this->companies->getUserCompanyId((int) ($currentUser['id'] ?? 0)) : null;
        $old = [
            'company_name' => '',
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'terms' => false,
        ];

        if ($isAuthenticated) {
            $old['first_name'] = trim((string) ($currentUser['first_name'] ?? ''));
            $old['last_name'] = trim((string) ($currentUser['last_name'] ?? ''));
            $old['email'] = trim((string) ($currentUser['email'] ?? ''));
        }

        if ($existingCompanyId) {
            return [
                'redirectTo' => $this->siteUrl('company/dashboard'),
                'view' => [],
            ];
        }

        if ($method === 'POST') {
            $companyName = trim((string) ($post['company_name'] ?? ''));
            $terms = !empty($post['terms']);
            $old['company_name'] = $companyName;
            $old['terms'] = $terms;
            if (!$isAuthenticated) {
                $old['first_name'] = trim((string) ($post['first_name'] ?? ''));
                $old['last_name'] = trim((string) ($post['last_name'] ?? ''));
                $old['email'] = trim((string) ($post['email'] ?? ''));
            }

            if ($companyName === '' || strlen($companyName) < 2) {
                $errors[] = 'Le nom de l’entreprise est obligatoire.';
            }
            if (!$terms) {
                $errors[] = 'Vous devez accepter les CGU et la politique de confidentialité.';
            }
            if ($errors === [] && !$this->companies->ensureCoreSchema()) {
                $errors[] = 'Impossible de créer un espace entreprise pour le moment.';
            }

            if ($errors === [] && $isAuthenticated) {
                try {
                    $userId = (int) ($currentUser['id'] ?? 0);
                    $roles = json_decode((string) ($currentUser['roles_json'] ?? '[]'), true);
                    $rolesList = is_array($roles) ? array_values(array_filter($roles, static fn ($role): bool => is_string($role) && $role !== '')) : [];
                    if (!in_array('student', $rolesList, true)) {
                        $rolesList[] = 'student';
                    }
                    if (!in_array('company_admin', $rolesList, true)) {
                        $rolesList[] = 'company_admin';
                    }

                    $this->model->beginTransaction();
                    $this->model->updateUserRoles($userId, $rolesList);
                    $companyId = $this->model->createCompany($companyName, $userId, 'active');
                    $this->model->createCompanyMember($companyId, $userId, 'owner', 'active');
                    $this->model->commit();

                    $successMessage = 'Entreprise créée. Vous pouvez maintenant inviter vos collaborateurs.';
                } catch (Throwable $e) {
                    if ($this->model->inTransaction()) {
                        $this->model->rollBack();
                    }
                    if (function_exists('app_log_error')) {
                        \app_log_error('company.register', 'Création d’entreprise échouée pour un utilisateur connecté.', [
                            'user_id' => (int) ($currentUser['id'] ?? 0),
                            'company_name' => $companyName,
                            'exception' => $e,
                        ]);
                    }
                    $errors[] = 'Une erreur est survenue lors de la création de l’entreprise.';
                }

                if ($errors === [] && $successMessage !== '') {
                    $this->flashSet('success', $successMessage);
                    return [
                        'redirectTo' => $this->siteUrl('company/dashboard'),
                        'view' => [],
                    ];
                }
            } elseif ($errors === []) {
                $first = trim((string) ($post['first_name'] ?? ''));
                $last = trim((string) ($post['last_name'] ?? ''));
                $email = trim((string) ($post['email'] ?? ''));
                $password = (string) ($post['password'] ?? '');
                $confirmPassword = (string) ($post['password_confirm'] ?? '');

                $old['first_name'] = $first;
                $old['last_name'] = $last;
                $old['email'] = $email;

                if ($first === '' || $last === '') {
                    $errors[] = 'Le prénom et le nom sont obligatoires.';
                }
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Adresse e-mail invalide.';
                }
                if (strlen($password) < 8) {
                    $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
                }
                if (!preg_match('/[A-Z]/', $password)) {
                    $errors[] = 'Le mot de passe doit contenir au moins une lettre majuscule.';
                }
                if (!preg_match('/\d/', $password)) {
                    $errors[] = 'Le mot de passe doit contenir au moins un chiffre.';
                }
                if (!preg_match('/[^A-Za-z0-9]/', $password)) {
                    $errors[] = 'Le mot de passe doit contenir au moins un caractère spécial.';
                }
                if ($password !== $confirmPassword) {
                    $errors[] = 'Les mots de passe ne correspondent pas.';
                }
                if ($errors === [] && $this->model->emailExists($email)) {
                    $errors[] = 'Un compte existe déjà avec cette adresse e-mail.';
                }

                if ($errors === []) {
                    $userId = 0;
                    try {
                        $this->model->beginTransaction();

                        $userId = $this->model->createUser(
                            $first,
                            $last,
                            $email,
                            password_hash($password, PASSWORD_DEFAULT),
                            'pending_email',
                            ['student', 'company_admin']
                        );

                        $this->referrals->ensureUserReferralCode($userId);

                        $companyId = $this->model->createCompany($companyName, $userId, 'active');
                        $this->model->createCompanyMember($companyId, $userId, 'owner', 'active');

                        $token = bin2hex(random_bytes(32));
                        $tokenHash = hash('sha256', $token);
                        $expiresAt = (new DateTime('+1 day'))->format('Y-m-d H:i:s');
                        $this->model->createEmailVerification($userId, $tokenHash, $expiresAt);

                        $this->model->commit();

                        $verificationLink = $this->absUrl('verify?token=' . urlencode($token));
                        $mailBody = "Bonjour {$first},\n\nMerci de confirmer votre adresse e-mail : {$verificationLink}\n\nSi vous n’êtes pas à l’origine de cette inscription, ignorez cet e-mail.";
                        if (!$this->sendEmail($email, 'Vérification de votre adresse e-mail', $mailBody)) {
                            if (function_exists('app_log_error')) {
                                \app_log_error('company.register', 'E-mail de vérification non envoyé après création du compte entreprise.', [
                                    'user_id' => $userId,
                                    'email' => $email,
                                    'company_name' => $companyName,
                                    'mail_error' => function_exists('last_mail_error') ? \last_mail_error() : [],
                                ]);
                            }

                            $this->flashSet('error', "Compte entreprise créé, mais l’e-mail de vérification n’a pas pu être envoyé. Réessayez plus tard ou contactez l’administration.");

                            return [
                                'redirectTo' => $this->siteUrl('company/register'),
                                'view' => [],
                            ];
                        }

                        $successMessage = 'Compte entreprise créé. Vérifiez votre adresse e-mail avant de vous connecter.';
                    } catch (Throwable $e) {
                        if ($this->model->inTransaction()) {
                            $this->model->rollBack();
                        }
                        if (function_exists('app_log_error')) {
                            \app_log_error('company.register', 'Création de compte entreprise échouée.', [
                                'email' => $email,
                                'company_name' => $companyName,
                                'exception' => $e,
                            ]);
                        }
                        $errors[] = 'Une erreur est survenue lors de la création du compte entreprise.';
                    }
                }

                if ($errors === [] && $successMessage !== '') {
                    $this->flashSet('success', $successMessage);
                    if (
                        $verificationLink !== ''
                        && function_exists('is_local_environment')
                        && \is_local_environment()
                    ) {
                        $this->flashSet('verification_link', $verificationLink);
                    }
                    return [
                        'redirectTo' => $this->siteUrl('company/register'),
                        'view' => [],
                    ];
                }
            }
        }

        return [
            'redirectTo' => null,
            'view' => [
                'errors' => $errors,
                'successMessage' => $successMessage,
                'verificationLink' => $verificationLink,
                'isAuthenticated' => $isAuthenticated,
                'isGoogleOnboarding' => $isGoogleOnboarding,
                'old' => $old,
            ],
        ];
    }
}
