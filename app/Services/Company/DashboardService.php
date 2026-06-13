<?php
declare(strict_types=1);

namespace App\Services\Company;

use App\Core\Service;
use App\Models\CompanyDashboardModel;
use App\Services\Shared\CompanySchemaService;
use DateTimeImmutable;
use Throwable;

final class DashboardService extends Service
{
    private CompanyDashboardModel $model;
    private CompanySchemaService $companies;

    public function __construct(CompanyDashboardModel $model, CompanySchemaService $companies)
    {
        $this->model = $model;
        $this->companies = $companies;
    }

    public function buildInitialState(array $user): array
    {
        return $this->buildDashboardState($user);
    }

    public function buildDashboardState(array $user): array
    {
        return $this->buildState($user, 'dashboard');
    }

    public function buildInvitesState(array $user): array
    {
        return $this->buildState($user, 'invites');
    }

    public function buildMembersState(array $user): array
    {
        return $this->buildState($user, 'members');
    }

    public function buildSeatsState(array $user): array
    {
        return $this->buildState($user, 'seats');
    }

    public function buildBillingState(array $user): array
    {
        return $this->buildState($user, 'billing');
    }

    public function buildSettingsState(array $user): array
    {
        return $this->buildState($user, 'dashboard');
    }

    public function buildAnalyticsState(array $user): array
    {
        return $this->buildState($user, 'dashboard');
    }

    public function buildOpsState(array $user): array
    {
        return $this->buildState($user, 'dashboard');
    }

    public function buildOnboardingState(array $user): array
    {
        return $this->buildState($user, 'dashboard');
    }

    public function buildSuccessReviewsState(array $user): array
    {
        return $this->buildState($user, 'dashboard');
    }

    public function buildTeamsState(array $user): array
    {
        return $this->buildState($user, 'members');
    }

    public function handlePost(array $state, array $user, array $post, array $files = [], string $page = 'dashboard'): array
    {
        $company = is_array($state['company'] ?? null) ? $state['company'] : null;
        $companyId = (int) ($company['id'] ?? 0);
        $actorUserId = (int) ($user['id'] ?? 0);
        if ($companyId < 1 || $actorUserId < 1) {
            $state['errors'][] = 'Entreprise introuvable.';
            return $state;
        }

        $action = trim((string) ($post['action'] ?? ''));
        [$errors, $success, $inviteLink] = match ($action) {
            'invite_member' => $this->postInviteMember($companyId, $actorUserId, $post),
            'resend_invite' => $this->postResendInvite($companyId, $actorUserId, $post),
            'revoke_invite' => $this->postRevokeInvite($companyId, $post),
            'set_member_status' => $this->postSetMemberStatus($companyId, $actorUserId, $post),
            'remove_member' => $this->postRemoveMember($companyId, $actorUserId, $post),
            'assign_course_seat' => $this->postAssignCourseSeat($companyId, $actorUserId, $post),
            'revoke_course_seat' => $this->postRevokeCourseSeat($companyId, $actorUserId, $post),
            default => [['Action invalide.'], '', ''],
        };

        $next = $this->buildState($user, $page);
        $next['errors'] = $errors;
        $next['successMessage'] = $success;
        $next['inviteLink'] = $inviteLink;
        return $next;
    }

    private function buildState(array $user, string $page): array
    {
        $state = $this->emptyState();
        $userId = (int) ($user['id'] ?? 0);
        if ($userId < 1) {
            $state['errors'][] = 'Session invalide.';
            return $state;
        }

        try {
            $state['unreadCount'] = $this->model->countUnreadNotifications($userId);
        } catch (Throwable $e) {
            $state['unreadCount'] = 0;
        }

        $this->companies->ensureDashboardSchema();

        try {
            $company = $this->model->findActiveAdminCompanyByUserId($userId);
        } catch (Throwable $e) {
            $company = null;
        }
        if (!is_array($company)) {
            $state['errors'][] = 'Votre compte administrateur d’entreprise n’est relié à aucune entreprise active.';
            return $state;
        }

        $companyId = (int) ($company['id'] ?? 0);
        if ($companyId < 1) {
            $state['errors'][] = 'Entreprise introuvable.';
            return $state;
        }

        $members = $this->safe(fn (): array => $this->model->findMembersByCompanyId($companyId), []);
        $invites = $this->safe(fn (): array => $this->model->findInvitesByCompanyId($companyId), []);
        $seatPurchases = $this->safe(fn (): array => $this->model->findCompanySeatPurchaseRows($companyId), []);
        $seatAssignments = $this->safe(fn (): array => $this->model->findCompanySeatAssignmentRows($companyId), []);
        $billingRows = $this->buildBillingRows(
            $this->safe(fn (): array => $this->model->findCompanyBillingRows($companyId), [])
        );

        $memberRows = $this->buildMemberRows($members);
        $inviteRows = $this->buildInviteRows($invites);
        $seatRows = $this->buildSeatRows($seatPurchases, $seatAssignments);

        $state['company'] = $company;
        $state['members'] = $members;
        $state['invites'] = $invites;
        $state['memberRows'] = $memberRows;
        $state['inviteRows'] = $inviteRows;
        $state['seatCourseRows'] = $seatRows;
        $state['seatMemberOptions'] = $this->buildSeatMemberOptions($memberRows);
        $state['billingRows'] = $billingRows;
        $state['memberSummary'] = $this->buildMemberSummary($memberRows);
        $state['inviteSummary'] = $this->buildInviteSummary($inviteRows);
        $state['seatSummary'] = $this->buildSeatSummary($seatRows);
        $state['billingSummary'] = $this->buildBillingSummary($billingRows);
        $state['currentPage'] = $page;

        return $state;
    }

    private function emptyState(): array
    {
        return [
            'errors' => [],
            'successMessage' => '',
            'inviteLink' => '',
            'company' => null,
            'members' => [],
            'invites' => [],
            'memberRows' => [],
            'inviteRows' => [],
            'seatCourseRows' => [],
            'seatMemberOptions' => [],
            'billingRows' => [],
            'memberSummary' => [],
            'inviteSummary' => [],
            'seatSummary' => [],
            'billingSummary' => [],
            'currentPage' => 'dashboard',
            'unreadCount' => 0,
        ];
    }

    /**
     * @template T
     * @param callable():T $callback
     * @param T $fallback
     * @return T
     */
    private function safe(callable $callback, mixed $fallback): mixed
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            return $fallback;
        }
    }

    /**
     * @return array{0:array<int,string>,1:string,2:string}
     */
    private function postInviteMember(int $companyId, int $actorUserId, array $post): array
    {
        $email = strtolower(trim((string) ($post['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [['Adresse e-mail invalide.'], '', ''];
        }
        if ($this->model->memberExistsByEmail($companyId, $email)) {
            return [['Ce collaborateur est déjà rattaché à l’entreprise.'], '', ''];
        }
        if ($this->model->pendingInviteExists($companyId, $email, 'email')) {
            return [['Une invitation active existe déjà pour cette adresse.'], '', ''];
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = (new DateTimeImmutable('+7 days'))->format('Y-m-d H:i:s');
        $this->model->createInvite($companyId, $email, hash('sha256', $token), $expiresAt, $actorUserId, 'email', false);

        $companyName = $this->companyName($companyId);
        $inviteLink = $this->absUrl('company/invite_accept?token=' . urlencode($token));
        $body = $this->buildInviteEmailBody($companyName, $inviteLink);
        $sent = $this->sendEmail($email, 'Invitation entreprise - ' . $companyName, $body);

        $message = $sent
            ? 'Invitation envoyée. Le collaborateur pourra créer son compte ou se connecter.'
            : 'Invitation créée. L’e-mail n’a pas pu être envoyé, copiez le lien ci-dessous.';

        return [[], $message, $inviteLink];
    }

    /**
     * @return array{0:array<int,string>,1:string,2:string}
     */
    private function postResendInvite(int $companyId, int $actorUserId, array $post): array
    {
        $inviteId = max(0, (int) ($post['invite_id'] ?? 0));
        $invite = $this->model->findInviteByIdForCompany($companyId, $inviteId);
        if (!is_array($invite)) {
            return [['Invitation introuvable.'], '', ''];
        }

        $status = strtolower((string) ($invite['status'] ?? ''));
        if (!in_array($status, ['pending', 'awaiting_approval'], true)) {
            return [['Seules les invitations en attente peuvent être renvoyées.'], '', ''];
        }

        $email = strtolower(trim((string) ($invite['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [['Cette invitation ne contient pas d’e-mail valide.'], '', ''];
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = (new DateTimeImmutable('+7 days'))->format('Y-m-d H:i:s');
        $ok = $this->model->refreshPendingInvite($companyId, $inviteId, hash('sha256', $token), $expiresAt, 'email', false);
        if (!$ok) {
            return [['Impossible de renvoyer cette invitation.'], '', ''];
        }

        $companyName = $this->companyName($companyId);
        $inviteLink = $this->absUrl('company/invite_accept?token=' . urlencode($token));
        $sent = $this->sendEmail($email, 'Invitation entreprise - ' . $companyName, $this->buildInviteEmailBody($companyName, $inviteLink));

        return [[], $sent ? 'Invitation renvoyée.' : 'Nouveau lien généré. L’e-mail n’a pas pu être envoyé.', $inviteLink];
    }

    /**
     * @return array{0:array<int,string>,1:string,2:string}
     */
    private function postRevokeInvite(int $companyId, array $post): array
    {
        $inviteId = max(0, (int) ($post['invite_id'] ?? 0));
        if ($inviteId < 1 || !$this->model->revokePendingInvite($inviteId, $companyId)) {
            return [['Invitation introuvable ou déjà traitée.'], '', ''];
        }

        return [[], 'Invitation annulée.', ''];
    }

    /**
     * @return array{0:array<int,string>,1:string,2:string}
     */
    private function postSetMemberStatus(int $companyId, int $actorUserId, array $post): array
    {
        $memberId = max(0, (int) ($post['member_id'] ?? 0));
        $status = strtolower(trim((string) ($post['status'] ?? '')));
        if (!in_array($status, ['active', 'blocked'], true)) {
            return [['Statut invalide.'], '', ''];
        }

        $member = $this->model->findMemberByIdForCompany($companyId, $memberId);
        if (!is_array($member)) {
            return [['Collaborateur introuvable.'], '', ''];
        }

        if ((int) ($member['user_id'] ?? 0) === $actorUserId && $status === 'blocked') {
            return [['Vous ne pouvez pas bloquer votre propre accès.'], '', ''];
        }

        if (in_array((string) ($member['role'] ?? ''), ['owner', 'admin'], true) && $status === 'blocked') {
            $activeAdmins = $this->model->countActiveAdminsByCompanyId($companyId);
            if ($activeAdmins <= 1 && strtolower((string) ($member['status'] ?? '')) === 'active') {
                return [['Impossible de bloquer le dernier administrateur actif.'], '', ''];
            }
        }

        if (!$this->model->updateMemberStatus($companyId, $memberId, $status)) {
            return [['Aucune modification appliquée.'], '', ''];
        }

        $this->syncUserCompanyRoles((int) ($member['user_id'] ?? 0));
        return [[], $status === 'active' ? 'Collaborateur réactivé.' : 'Collaborateur bloqué.', ''];
    }

    /**
     * @return array{0:array<int,string>,1:string,2:string}
     */
    private function postRemoveMember(int $companyId, int $actorUserId, array $post): array
    {
        $memberId = max(0, (int) ($post['member_id'] ?? 0));
        $member = $this->model->findMemberByIdForCompany($companyId, $memberId);
        if (!is_array($member)) {
            return [['Collaborateur introuvable.'], '', ''];
        }

        $memberUserId = (int) ($member['user_id'] ?? 0);
        if ($memberUserId === $actorUserId) {
            return [['Vous ne pouvez pas retirer votre propre compte.'], '', ''];
        }

        if (in_array((string) ($member['role'] ?? ''), ['owner', 'admin'], true)) {
            $activeAdmins = $this->model->countActiveAdminsByCompanyId($companyId);
            if ($activeAdmins <= 1 && strtolower((string) ($member['status'] ?? '')) === 'active') {
                return [['Impossible de retirer le dernier administrateur actif.'], '', ''];
            }
        }

        if (!$this->model->removeMember($companyId, $memberId)) {
            return [['Impossible de retirer ce collaborateur.'], '', ''];
        }

        $this->syncUserCompanyRoles($memberUserId);
        return [[], 'Collaborateur retiré de l’entreprise.', ''];
    }

    /**
     * @return array{0:array<int,string>,1:string,2:string}
     */
    private function postAssignCourseSeat(int $companyId, int $actorUserId, array $post): array
    {
        $courseId = max(0, (int) ($post['course_id'] ?? 0));
        $memberUserId = max(0, (int) ($post['member_user_id'] ?? 0));
        if ($courseId < 1 || $memberUserId < 1) {
            return [['Cours ou collaborateur invalide.'], '', ''];
        }

        $member = $this->model->findCompanyMemberByUserId($companyId, $memberUserId);
        if (!is_array($member) || strtolower((string) ($member['status'] ?? '')) !== 'active') {
            return [['Ce collaborateur n’est pas actif dans l’entreprise.'], '', ''];
        }

        if ($this->model->findActiveSeatAssignment($companyId, $courseId, $memberUserId)) {
            return [['Une place est déjà attribuée à ce collaborateur pour ce cours.'], '', ''];
        }

        $purchased = $this->model->sumCompanyPurchasedSeatsForCourse($companyId, $courseId);
        $assigned = $this->model->countCompanyActiveSeatAssignmentsForCourse($companyId, $courseId);
        if ($purchased < 1 || $assigned >= $purchased) {
            return [['Aucune place disponible pour ce cours.'], '', ''];
        }

        if (!$this->model->upsertSeatAssignmentActive($companyId, $courseId, $memberUserId, $actorUserId)) {
            return [['Impossible d’attribuer cette place.'], '', ''];
        }

        $this->model->ensureEnrollmentActive($memberUserId, $courseId);
        $course = $this->model->findCourseById($courseId);
        $title = trim((string) ($course['title'] ?? 'Cours entreprise'));
        $this->model->insertNotification(
            $memberUserId,
            'Nouvelle formation entreprise',
            'Une place vous a été attribuée pour "' . $title . '".',
            'student/my_courses'
        );

        return [[], 'Place attribuée au collaborateur.', ''];
    }

    /**
     * @return array{0:array<int,string>,1:string,2:string}
     */
    private function postRevokeCourseSeat(int $companyId, int $actorUserId, array $post): array
    {
        $courseId = max(0, (int) ($post['course_id'] ?? 0));
        $memberUserId = max(0, (int) ($post['member_user_id'] ?? 0));
        if ($courseId < 1 || $memberUserId < 1) {
            return [['Cours ou collaborateur invalide.'], '', ''];
        }

        if (!$this->model->revokeSeatAssignment($companyId, $courseId, $memberUserId)) {
            return [['Attribution introuvable.'], '', ''];
        }

        $this->model->revokeEnrollment($memberUserId, $courseId);
        return [[], 'Place retirée.', ''];
    }

    private function buildInviteEmailBody(string $companyName, string $inviteLink): string
    {
        return "Bonjour,\n\nVous êtes invité à rejoindre l’entreprise \"{$companyName}\" sur École Numérique.\n\n"
            . "Cliquez sur ce lien pour accepter l’invitation : {$inviteLink}\n\n"
            . "Si vous avez déjà un compte, connectez-vous avec cette adresse e-mail. Sinon, créez votre compte depuis la page d’invitation.\n\n"
            . "Ce lien expire dans 7 jours.";
    }

    private function companyName(int $companyId): string
    {
        $company = $this->model->findCompanyById($companyId);
        $name = trim((string) ($company['name'] ?? ''));
        return $name !== '' ? $name : 'Entreprise';
    }

    private function syncUserCompanyRoles(int $userId): void
    {
        if ($userId < 1) {
            return;
        }

        $user = $this->model->findUserById($userId);
        if (!is_array($user)) {
            return;
        }

        $roles = json_decode((string) ($user['roles_json'] ?? '[]'), true);
        $roles = is_array($roles) ? array_values(array_filter($roles, static fn ($role): bool => is_string($role) && $role !== '')) : [];
        if (!in_array('student', $roles, true)) {
            $roles[] = 'student';
        }

        $hasAdmin = $this->model->countActiveAdminMembershipsForUser($userId) > 0;
        $hasMember = $this->model->countActiveMembershipsForUser($userId) > 0;

        $roles = array_values(array_filter($roles, static function (string $role) use ($hasAdmin, $hasMember): bool {
            if ($role === 'company_admin') {
                return $hasAdmin;
            }
            if ($role === 'company_member') {
                return $hasMember;
            }
            return true;
        }));

        if ($hasAdmin && !in_array('company_admin', $roles, true)) {
            $roles[] = 'company_admin';
        }
        if ($hasMember && !in_array('company_member', $roles, true)) {
            $roles[] = 'company_member';
        }

        $roles = array_values(array_unique($roles));
        $encoded = json_encode($roles);
        if (is_string($encoded) && $encoded !== (string) ($user['roles_json'] ?? '')) {
            $this->model->updateUserRoles($userId, $encoded);
        }
    }

    private function buildMemberRows(array $members): array
    {
        $rows = [];
        foreach ($members as $member) {
            $first = trim((string) ($member['first_name'] ?? ''));
            $last = trim((string) ($member['last_name'] ?? ''));
            $email = trim((string) ($member['email'] ?? ''));
            $name = trim($first . ' ' . $last);
            if ($name === '') {
                $name = $email !== '' ? $email : 'Collaborateur';
            }

            $role = strtolower((string) ($member['role'] ?? 'member'));
            $status = strtolower((string) ($member['status'] ?? 'active'));
            $rows[] = [
                'id' => (int) ($member['id'] ?? 0),
                'user_id' => (int) ($member['user_id'] ?? 0),
                'full_name' => $name,
                'email' => $email,
                'role' => $role,
                'role_label' => $this->roleLabel($role),
                'status' => $status,
                'status_label' => $status === 'active' ? 'Actif' : 'Bloqué',
                'joined_at_label' => $this->formatDate((string) ($member['created_at'] ?? '')),
                'is_admin' => in_array($role, ['owner', 'admin'], true),
            ];
        }
        return $rows;
    }

    private function buildInviteRows(array $invites): array
    {
        $rows = [];
        foreach ($invites as $invite) {
            $status = strtolower((string) ($invite['status'] ?? 'pending'));
            $expiresAt = (string) ($invite['expires_at'] ?? '');
            if ($status === 'pending' && $expiresAt !== '' && strtotime($expiresAt) < time()) {
                $status = 'expired';
            }

            $rows[] = [
                'id' => (int) ($invite['id'] ?? 0),
                'email' => (string) ($invite['email'] ?? ''),
                'status' => $status,
                'status_label' => $this->inviteStatusLabel($status),
                'created_at_label' => $this->formatDate((string) ($invite['created_at'] ?? '')),
                'expires_at_label' => $this->formatDate($expiresAt),
                'can_manage' => in_array($status, ['pending', 'awaiting_approval'], true),
            ];
        }
        return $rows;
    }

    private function buildSeatRows(array $purchases, array $assignments): array
    {
        $assignedByCourse = [];
        foreach ($assignments as $assignment) {
            $courseId = (int) ($assignment['course_id'] ?? 0);
            if ($courseId < 1) {
                continue;
            }
            $first = trim((string) ($assignment['first_name'] ?? ''));
            $last = trim((string) ($assignment['last_name'] ?? ''));
            $email = trim((string) ($assignment['email'] ?? ''));
            $name = trim($first . ' ' . $last);
            $assignedByCourse[$courseId][] = [
                'member_user_id' => (int) ($assignment['member_user_id'] ?? 0),
                'full_name' => $name !== '' ? $name : $email,
                'email' => $email,
                'assigned_at_label' => $this->formatDate((string) ($assignment['assigned_at'] ?? '')),
            ];
        }

        $rows = [];
        foreach ($purchases as $purchase) {
            $courseId = (int) ($purchase['course_id'] ?? 0);
            if ($courseId < 1) {
                continue;
            }
            $assignedUsers = $assignedByCourse[$courseId] ?? [];
            $seatsTotal = max(0, (int) ($purchase['seats_total'] ?? 0));
            $assigned = count($assignedUsers);
            $rows[] = [
                'course_id' => $courseId,
                'course_title' => (string) ($purchase['course_title'] ?? 'Cours'),
                'seats_total' => $seatsTotal,
                'seats_assigned' => $assigned,
                'seats_available' => max(0, $seatsTotal - $assigned),
                'unit_price_xof' => max(0, (int) ($purchase['unit_price_xof'] ?? 0)),
                'total_xof' => max(0, (int) ($purchase['total_xof'] ?? 0)),
                'assigned_users' => $assignedUsers,
            ];
        }
        return $rows;
    }

    private function buildBillingRows(array $rows): array
    {
        $billingRows = [];
        foreach ($rows as $row) {
            $buyer = trim((string) ($row['purchased_by_first_name'] ?? '') . ' ' . (string) ($row['purchased_by_last_name'] ?? ''));
            if ($buyer === '') {
                $buyer = (string) ($row['purchased_by_email'] ?? '');
            }

            $billingRows[] = [
                'order_reference' => (string) ($row['order_reference'] ?? ('Commande #' . (string) ($row['order_id'] ?? '-'))),
                'course_title' => (string) ($row['course_title'] ?? 'Cours'),
                'seats_total' => max(1, (int) ($row['seats_total'] ?? 1)),
                'unit_price_xof' => max(0, (int) ($row['unit_price_xof'] ?? 0)),
                'total_xof' => max(0, (int) ($row['total_xof'] ?? 0)),
                'order_status' => strtolower((string) ($row['order_status'] ?? '')),
                'payment_status' => strtolower((string) ($row['payment_status'] ?? '')),
                'buyer' => $buyer !== '' ? $buyer : '-',
                'created_at_label' => $this->formatDate((string) ($row['order_created_at'] ?? $row['purchase_created_at'] ?? '')),
            ];
        }
        return $billingRows;
    }

    private function buildSeatMemberOptions(array $memberRows): array
    {
        $options = [];
        foreach ($memberRows as $member) {
            if (($member['status'] ?? '') !== 'active') {
                continue;
            }
            $options[] = [
                'user_id' => (int) ($member['user_id'] ?? 0),
                'label' => (string) ($member['full_name'] ?? ''),
                'email' => (string) ($member['email'] ?? ''),
            ];
        }
        return $options;
    }

    private function buildMemberSummary(array $memberRows): array
    {
        $summary = ['total' => count($memberRows), 'active' => 0, 'blocked' => 0, 'admins' => 0];
        foreach ($memberRows as $row) {
            if (($row['status'] ?? '') === 'active') {
                $summary['active']++;
            } else {
                $summary['blocked']++;
            }
            if (!empty($row['is_admin'])) {
                $summary['admins']++;
            }
        }
        return $summary;
    }

    private function buildInviteSummary(array $inviteRows): array
    {
        $summary = ['total' => count($inviteRows), 'pending' => 0, 'accepted' => 0, 'expired' => 0, 'revoked' => 0];
        foreach ($inviteRows as $row) {
            $status = (string) ($row['status'] ?? 'pending');
            if (!isset($summary[$status])) {
                $status = 'pending';
            }
            $summary[$status]++;
        }
        return $summary;
    }

    private function buildSeatSummary(array $seatRows): array
    {
        $summary = ['courses' => count($seatRows), 'seats_total' => 0, 'seats_assigned' => 0, 'seats_available' => 0];
        foreach ($seatRows as $row) {
            $summary['seats_total'] += (int) ($row['seats_total'] ?? 0);
            $summary['seats_assigned'] += (int) ($row['seats_assigned'] ?? 0);
            $summary['seats_available'] += (int) ($row['seats_available'] ?? 0);
        }
        return $summary;
    }

    private function buildBillingSummary(array $billingRows): array
    {
        $summary = ['orders' => count($billingRows), 'seats_total' => 0, 'total_xof' => 0];
        foreach ($billingRows as $row) {
            $summary['seats_total'] += (int) ($row['seats_total'] ?? 0);
            $summary['total_xof'] += (int) ($row['total_xof'] ?? 0);
        }
        return $summary;
    }

    private function roleLabel(string $role): string
    {
        return match ($role) {
            'owner' => 'Propriétaire',
            'admin' => 'Administrateur',
            default => 'Collaborateur',
        };
    }

    private function inviteStatusLabel(string $status): string
    {
        return match ($status) {
            'accepted' => 'Acceptée',
            'revoked' => 'Annulée',
            'expired' => 'Expirée',
            'awaiting_approval' => 'En attente',
            default => 'En attente',
        };
    }

    private function formatDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '-';
        }
        $ts = strtotime($value);
        return $ts ? date('d/m/Y', $ts) : '-';
    }
}
