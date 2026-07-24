<?php
declare(strict_types=1);

namespace App\Services\Admin;

use App\Core\Service;
use App\Models\AdminDashboardModel;
use App\Models\AdminSecondaryAdminsModel;
use App\Services\Student\Pagination;

final class SecondaryAdminsService extends Service
{
    private const PER_PAGE = 6;
    private const ACTIVITY_PER_PAGE = 12;

    private AdminSecondaryAdminsModel $model;
    private AdminDashboardModel $dashboardModel;

    public function __construct(AdminSecondaryAdminsModel $model, AdminDashboardModel $dashboardModel)
    {
        $this->model = $model;
        $this->dashboardModel = $dashboardModel;
    }

    /**
     * @param array<string,mixed> $post
     * @param array<string,mixed> $currentUser
     * @param array<string,mixed> $query
     * @return array{redirectTo:?string,view:array<string,mixed>}
     */
    public function handle(
        string $method,
        array $post,
        array $currentUser,
        array $query = [],
        string $routeMode = 'list'
    ): array {
        $errors = [];
        $successMessage = (string) $this->flashGet('success', '');
        $permissionsCatalog = AdminAccessService::permissionsCatalog();
        $page = $this->resolvePage($query, $post);
        $requestMode = $routeMode;

        if ($method === 'POST') {
            $action = (string) ($post['action'] ?? '');
            $requestMode = $this->resolveRequestMode($routeMode, $action);

            switch ($action) {
                case 'create':
                    $errors = $this->handleCreate($post, $currentUser, $successMessage);
                    break;
                case 'grant':
                    $errors = $this->handleGrant($post, $currentUser, $successMessage);
                    break;
                case 'update_permissions':
                    $errors = $this->handleUpdatePermissions($post, $currentUser, $successMessage);
                    break;
                case 'toggle_permissions_active':
                    $errors = $this->handleTogglePermissionsActive($post, $successMessage);
                    break;
                case 'revoke':
                    $errors = $this->handleRevoke($post, $successMessage);
                    break;
                default:
                    $errors[] = 'Action invalide.';
                    break;
            }
        }

        if ($method === 'POST' && $errors === [] && $successMessage !== '') {
            $this->flashSet('success', $successMessage);

            return [
                'redirectTo' => $this->siteUrl($this->buildListPath($page)),
                'view' => [],
            ];
        }

        if ($requestMode === 'activity') {
            return [
                'redirectTo' => null,
                'view' => $this->buildActivityView($query, $page),
            ];
        }

        if ($requestMode === 'list') {
            return [
                'redirectTo' => null,
                'view' => $this->buildListView($errors, $successMessage, $permissionsCatalog, $page),
            ];
        }

        return [
            'redirectTo' => null,
            'view' => $this->buildFormView(
                $requestMode,
                $query,
                $post,
                $errors,
                $successMessage,
                $permissionsCatalog,
                $page
            ),
        ];
    }

    /**
     * @param array<int,string> $errors
     * @param array<string,string> $permissionsCatalog
     * @return array<string,mixed>
     */
    private function buildListView(
        array $errors,
        string $successMessage,
        array $permissionsCatalog,
        int $page
    ): array {
        $total = $this->model->countSecondaryAdmins();
        $pagination = Pagination::build($total, $page, self::PER_PAGE);
        $rows = $this->model->fetchSecondaryAdminsPage(
            (int) ($pagination['offset'] ?? 0),
            (int) ($pagination['per_page'] ?? self::PER_PAGE)
        );

        $secondaryAdmins = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $permissions = AdminAccessService::normalizePermissions(
                json_decode((string) ($row['permissions_json'] ?? '[]'), true)
            );
            $permissionLabels = [];
            foreach ($permissions as $permission) {
                if (isset($permissionsCatalog[$permission])) {
                    $permissionLabels[] = (string) $permissionsCatalog[$permission];
                }
            }

            $creatorName = trim((string) ($row['creator_first'] ?? '') . ' ' . (string) ($row['creator_last'] ?? ''));

            $secondaryAdmins[] = [
                'id' => (int) ($row['id'] ?? 0),
                'first_name' => (string) ($row['first_name'] ?? ''),
                'last_name' => (string) ($row['last_name'] ?? ''),
                'email' => (string) ($row['email'] ?? ''),
                'status' => (string) ($row['status'] ?? ''),
                'created_at' => (string) ($row['created_at'] ?? ''),
                'is_active' => (int) ($row['is_active'] ?? 0) === 1,
                'permissions' => $permissions,
                'permission_labels' => $permissionLabels,
                'created_by_name' => $creatorName,
            ];
        }

        return [
            'viewTemplate' => 'admin/secondary_admins',
            'errors' => $errors,
            'successMessage' => $successMessage,
            'permissionsCatalog' => $permissionsCatalog,
            'secondaryAdmins' => $secondaryAdmins,
            'pagination' => $pagination,
            'paginationPage' => $page,
            'adminDashboardUrl' => $this->siteUrl('admin/dashboard'),
            'adminSupportAgentsUrl' => $this->siteUrl('admin/support_agents'),
        ];
    }

    /**
     * @param array<string,mixed> $query
     * @return array<string,mixed>
     */
    private function buildActivityView(array $query, int $page): array
    {
        $filters = $this->normalizeActivityFilters($query);
        $auditFilters = $filters;
        $auditFilters['secondary_only'] = true;

        $total = $this->dashboardModel->countRecentAuditLogs($auditFilters);
        $pagination = Pagination::build($total, $page, self::ACTIVITY_PER_PAGE);
        $rows = $this->dashboardModel->fetchRecentAuditLogs(
            $auditFilters,
            (int) ($pagination['per_page'] ?? self::ACTIVITY_PER_PAGE),
            (int) ($pagination['offset'] ?? 0)
        );

        $actorRows = $this->dashboardModel->fetchAuditActors([
            'secondary_only' => true,
            'from' => $filters['from'],
            'to' => $filters['to'],
            'module' => $filters['module'],
        ], 5);
        $moduleRows = $this->dashboardModel->fetchAuditModules([
            'secondary_only' => true,
            'from' => $filters['from'],
            'to' => $filters['to'],
        ], 12);

        $activityEntries = $this->buildActivityEntries($rows);
        $topActors = $this->buildActivityActors($actorRows);
        $modules = $this->buildActivityModules($moduleRows);

        $activeFilterCount = 0;
        foreach (['search', 'from', 'to', 'module'] as $key) {
            if (trim((string) ($filters[$key] ?? '')) !== '') {
                $activeFilterCount++;
            }
        }

        return [
            'viewTemplate' => 'admin/secondary_admins_activity',
            'activityFilters' => $filters,
            'activityEntries' => $activityEntries,
            'activityTopActors' => $topActors,
            'activityModules' => $modules,
            'activityCounts' => [
                'total' => $total,
                'active_filters' => $activeFilterCount,
            ],
            'pagination' => $pagination,
            'paginationPage' => $page,
            'paginationTotalPages' => max(1, (int) ($pagination['total_pages'] ?? 1)),
            'secondaryAdminsUrl' => $this->siteUrl('admin/secondary_admins'),
            'secondaryAdminsCreateUrl' => $this->siteUrl('admin/secondary_admins/create'),
            'secondaryAdminsGrantUrl' => $this->siteUrl('admin/secondary_admins/grant'),
        ];
    }

    /**
     * @param array<string,mixed> $query
     * @return array{search:string,from:string,to:string,module:string}
     */
    private function normalizeActivityFilters(array $query): array
    {
        $search = trim((string) ($query['q'] ?? ''));
        $from = trim((string) ($query['from'] ?? ''));
        $to = trim((string) ($query['to'] ?? ''));
        $module = strtolower(trim((string) ($query['module'] ?? '')));

        if ($search !== '' && strlen($search) > 120) {
            $search = substr($search, 0, 120);
        }
        if (!$this->isValidDate($from)) {
            $from = '';
        }
        if (!$this->isValidDate($to)) {
            $to = '';
        }
        if ($module !== '' && !preg_match('/^[a-z0-9_]+$/', $module)) {
            $module = '';
        }

        return [
            'search' => $search,
            'from' => $from,
            'to' => $to,
            'module' => $module,
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function buildActivityEntries(array $rows): array
    {
        $entries = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $action = strtolower(trim((string) ($row['action'] ?? '')));
            $targetType = strtolower(trim((string) ($row['target_type'] ?? '')));
            $targetId = max(0, (int) ($row['target_id'] ?? 0));
            $actorName = trim((string) ($row['actor_first_name'] ?? '') . ' ' . (string) ($row['actor_last_name'] ?? ''));
            if ($actorName === '') {
                $actorName = 'Administrateur secondaire';
            }
            $meta = json_decode((string) ($row['meta_json'] ?? '[]'), true);
            if (!is_array($meta)) {
                $meta = [];
            }

            $entries[] = [
                'id' => (int) ($row['id'] ?? 0),
                'actor_id' => (int) ($row['actor_id'] ?? 0),
                'actor_name' => $actorName,
                'actor_email' => trim((string) ($row['actor_email'] ?? '')),
                'actor_initials' => $this->initialsFromName($actorName, 'AS'),
                'action_key' => $action,
                'action_label' => $this->activityActionLabel($action),
                'action_tone' => $this->activityActionTone($action),
                'action_icon' => $this->activityActionIcon($action),
                'target_type' => $targetType,
                'target_label' => $this->activityTargetLabel($targetType, $targetId),
                'module_label' => $this->activityModuleLabel($targetType),
                'details' => $this->summarizeActivityMeta($meta),
                'created_at' => (string) ($row['created_at'] ?? ''),
                'created_label' => $this->formatActivityTimestamp((string) ($row['created_at'] ?? '')),
            ];
        }

        return $entries;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function buildActivityActors(array $rows): array
    {
        $actors = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $name = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));
            if ($name === '') {
                $name = 'Administrateur secondaire';
            }

            $actors[] = [
                'id' => (int) ($row['id'] ?? 0),
                'name' => $name,
                'email' => trim((string) ($row['email'] ?? '')),
                'count' => max(0, (int) ($row['action_count'] ?? 0)),
                'initials' => $this->initialsFromName($name, 'AS'),
            ];
        }

        return $actors;
    }

    /**
     * @param array<int,string> $rows
     * @return array<int,array{key:string,label:string}>
     */
    private function buildActivityModules(array $rows): array
    {
        $modules = [];
        foreach ($rows as $row) {
            $module = trim((string) $row);
            if ($module === '') {
                continue;
            }

            $modules[] = [
                'key' => $module,
                'label' => $this->activityModuleLabel($module),
            ];
        }

        return $modules;
    }

    private function activityActionLabel(string $action): string
    {
        $action = strtolower(trim($action));
        return match ($action) {
            'secondary_admin_login' => 'Connexion admin secondaire',
            'course_approve' => 'Cours approuvé',
            'course_reject' => 'Cours rejeté',
            'course_remove' => 'Cours retiré',
            'course_correction_request' => 'Correction demandée',
            'course_create' => 'Cours créé',
            'course_update' => 'Cours modifié',
            'course_submit' => 'Cours soumis',
            'course_restore' => 'Cours restauré',
            'course_new_version' => 'Nouvelle version',
            'course_duplicate' => 'Cours dupliqué',
            'teacher_application_approve' => 'Demande enseignant approuvée',
            'teacher_application_reject' => 'Demande enseignant rejetée',
            'teacher_suspend' => 'Enseignant suspendu',
            'teacher_reactivate' => 'Enseignant réactivé',
            'announcement_save_draft' => 'Annonce enregistrée en brouillon',
            'announcement_submit_review' => 'Annonce soumise',
            'announcement_publish' => 'Annonce publiée',
            'announcement_approve_publish' => 'Annonce validée et publiée',
            'announcement_archive' => 'Annonce archivée',
            'announcement_restore' => 'Annonce restaurée',
            'announcement_delete' => 'Annonce supprimée',
            'category_create' => 'Catégorie créée',
            'category_update' => 'Catégorie modifiée',
            'category_delete' => 'Catégorie supprimée',
            'support_agent_create' => 'Compte support créé',
            'support_agent_grant' => 'Rôle support ajouté',
            'support_agent_revoke' => 'Rôle support retiré',
            'sales_commission_update' => 'Commission mise à jour',
            'sales_withdrawal_minimums_update' => 'Seuils de retrait mis à jour',
            'withdrawal_update' => 'Retrait mis à jour',
            'sales_referral_rates_update' => 'Taux de parrainage mis à jour',
            'sales_teacher_withdrawal_methods_update' => 'Méthodes de retrait mises à jour',
            'teacher_payout_simulate' => 'Paiement simulé',
            'order_mark_paid' => 'Commande marquée payée',
            'teacher_payout_mark_paid' => 'Paiement enseignant marqué payé',
            'site_settings_update' => 'Paramètres du site mis à jour',
            'sso_login' => 'Connexion entreprise',
            default => $action !== '' ? ucfirst(str_replace('_', ' ', $action)) : 'Action enregistrée',
        };
    }

    private function activityActionTone(string $action): string
    {
        $action = strtolower(trim($action));
        if ($action === '') {
            return 'done';
        }

        if (
            str_contains($action, 'approve')
            || str_contains($action, 'reactivate')
            || str_contains($action, 'restore')
            || str_contains($action, 'create')
            || str_contains($action, 'submit')
            || str_contains($action, 'login')
            || str_contains($action, 'publish')
            || str_contains($action, 'grant')
            || str_contains($action, 'save')
        ) {
            return 'success';
        }

        if (
            str_contains($action, 'reject')
            || str_contains($action, 'remove')
            || str_contains($action, 'suspend')
            || str_contains($action, 'revoke')
            || str_contains($action, 'delete')
        ) {
            return 'danger';
        }

        if (str_contains($action, 'update') || str_contains($action, 'edit') || str_contains($action, 'duplicate') || str_contains($action, 'mark') || str_contains($action, 'simulate')) {
            return 'info';
        }

        if (str_contains($action, 'archive')) {
            return 'warning';
        }

        return 'done';
    }

    private function activityActionIcon(string $action): string
    {
        $action = strtolower(trim($action));
        if ($action === '') {
            return 'history';
        }

        if (
            str_contains($action, 'approve')
            || str_contains($action, 'reactivate')
            || str_contains($action, 'restore')
            || str_contains($action, 'create')
            || str_contains($action, 'submit')
            || str_contains($action, 'login')
            || str_contains($action, 'publish')
            || str_contains($action, 'grant')
            || str_contains($action, 'save')
        ) {
            return 'check-circle';
        }

        if (
            str_contains($action, 'reject')
            || str_contains($action, 'remove')
            || str_contains($action, 'suspend')
            || str_contains($action, 'revoke')
            || str_contains($action, 'delete')
        ) {
            return 'times-circle';
        }

        if (str_contains($action, 'update') || str_contains($action, 'edit') || str_contains($action, 'duplicate') || str_contains($action, 'mark') || str_contains($action, 'simulate')) {
            return 'edit';
        }

        if (str_contains($action, 'archive')) {
            return 'box-archive';
        }

        return 'history';
    }

    private function activityTargetLabel(string $targetType, int $targetId): string
    {
        $targetType = strtolower(trim($targetType));
        $label = $this->activityModuleLabel($targetType);

        return match ($targetType) {
            'auth' => 'Connexion à l’espace admin',
            'course' => 'Cours #' . $targetId,
            'teacher_application' => 'Demande enseignant #' . $targetId,
            'user' => 'Utilisateur #' . $targetId,
            'announcement' => 'Annonce #' . $targetId,
            'support_agent' => 'Agent support #' . $targetId,
            'support_conversation' => 'Ticket support #' . $targetId,
            'company' => 'Entreprise #' . $targetId,
            'payment' => 'Paiement #' . $targetId,
            'withdrawal' => 'Retrait #' . $targetId,
            'teacher_payout' => 'Paiement enseignant #' . $targetId,
            'order' => 'Commande #' . $targetId,
            'sales_settings' => 'Paramètres des ventes',
            'site_settings' => 'Paramètres du site',
            'secondary_admin' => 'Administrateur secondaire #' . $targetId,
            default => $targetId > 0 ? ($label . ' #' . $targetId) : $label,
        };
    }

    private function activityModuleLabel(string $module): string
    {
        $module = strtolower(trim($module));
        return match ($module) {
            'auth' => 'Authentification',
            'course' => 'Cours',
            'teacher_application' => 'Candidatures enseignants',
            'user' => 'Utilisateurs',
            'announcement' => 'Annonces',
            'support_agent' => 'Agents de support',
            'support_conversation' => 'Support',
            'support' => 'Support',
            'company' => 'Entreprises',
            'payment' => 'Paiements',
            'withdrawal' => 'Retraits',
            'teacher_payout' => 'Paiements enseignants',
            'order' => 'Commandes',
            'sales_settings' => 'Paramètres des ventes',
            'category' => 'Catégories',
            'site_settings' => 'Paramètres du site',
            'secondary_admin' => 'Administrateurs secondaires',
            'error_log' => 'Logs erreurs',
            default => $module !== '' ? ucfirst(str_replace('_', ' ', $module)) : 'Activité',
        };
    }

    private function summarizeActivityMeta(array $meta): string
    {
        $parts = [];

        $reason = trim((string) ($meta['reason'] ?? ''));
        if ($reason !== '') {
            $parts[] = 'Motif : ' . $reason;
        }

        $moderationType = trim((string) ($meta['moderation_type'] ?? ''));
        if ($moderationType !== '') {
            $parts[] = 'Mode : ' . ucfirst(str_replace('_', ' ', $moderationType));
        }

        $previousStatus = trim((string) ($meta['previous_status'] ?? ''));
        $newStatus = trim((string) ($meta['new_status'] ?? ''));
        if ($previousStatus !== '' || $newStatus !== '') {
            $parts[] = 'Statut : ' . ($previousStatus !== '' ? $previousStatus : '?') . ' → ' . ($newStatus !== '' ? $newStatus : '?');
        }

        $userId = max(0, (int) ($meta['user_id'] ?? 0));
        if ($userId > 0) {
            $parts[] = 'Utilisateur #' . $userId;
        }

        $tab = trim((string) ($meta['tab'] ?? ''));
        if ($tab !== '') {
            $parts[] = 'Onglet : ' . ucfirst(str_replace('_', ' ', $tab));
        }

        $siteName = trim((string) ($meta['site_name'] ?? ''));
        if ($siteName !== '') {
            $parts[] = 'Site : ' . $siteName;
        }

        $assets = trim((string) ($meta['assets'] ?? ''));
        if ($assets !== '') {
            $parts[] = 'Assets : ' . $assets;
        }

        $methods = trim((string) ($meta['methods'] ?? ''));
        if ($methods !== '') {
            $parts[] = 'Méthodes : ' . $methods;
        }

        $commissionPercent = trim((string) ($meta['commission_percent'] ?? ''));
        if ($commissionPercent !== '') {
            $parts[] = 'Commission : ' . $commissionPercent . '%';
        }

        $referralL1Rate = trim((string) ($meta['referral_l1_rate'] ?? ''));
        $referralL2Rate = trim((string) ($meta['referral_l2_rate'] ?? ''));
        if ($referralL1Rate !== '' || $referralL2Rate !== '') {
            $parts[] = 'Parrainage : L1 ' . ($referralL1Rate !== '' ? $referralL1Rate : '?') . '% / L2 ' . ($referralL2Rate !== '' ? $referralL2Rate : '?') . '%';
        }

        $amountXof = trim((string) ($meta['amount_xof'] ?? ''));
        if ($amountXof !== '') {
            $parts[] = 'Montant : ' . $amountXof . ' FCFA';
        }

        $source = trim((string) ($meta['source'] ?? ''));
        if ($source !== '') {
            $parts[] = 'Source : ' . ucfirst(str_replace('_', ' ', $source));
        }

        if ($parts !== []) {
            return implode(' · ', $parts);
        }

        foreach ($meta as $key => $value) {
            if (count($parts) >= 2) {
                break;
            }
            if ($key === 'actor_role') {
                continue;
            }
            if (!is_scalar($value) || $value === '') {
                continue;
            }
            $label = ucfirst(str_replace('_', ' ', (string) $key));
            $parts[] = $label . ' : ' . (string) $value;
        }

        return $parts !== [] ? implode(' · ', $parts) : '';
    }

    private function formatActivityTimestamp(string $dateTime): string
    {
        $dateTime = trim($dateTime);
        if ($dateTime === '') {
            return 'Date non disponible';
        }

        $timestamp = strtotime($dateTime);
        if ($timestamp === false) {
            return $dateTime;
        }

        return date('d/m/Y H:i', $timestamp);
    }

    private function initialsFromName(string $name, string $fallback = 'AD'): string
    {
        $name = trim($name);
        if ($name === '') {
            return $fallback;
        }

        $parts = preg_split('/\s+/', $name) ?: [];
        $letters = '';
        foreach ($parts as $part) {
            $letters .= function_exists('mb_substr') ? mb_substr($part, 0, 1) : substr($part, 0, 1);
            if (strlen($letters) >= 2) {
                break;
            }
        }

        $letters = function_exists('mb_strtoupper') ? mb_strtoupper($letters) : strtoupper($letters);

        return $letters !== '' ? $letters : $fallback;
    }

    private function isValidDate(string $value): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }

        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        return $dt instanceof \DateTimeImmutable && $dt->format('Y-m-d') === $value;
    }

    /**
     * @param array<int,string> $errors
     * @param array<string,string> $permissionsCatalog
     * @return array<string,mixed>
     */
    private function buildFormView(
        string $mode,
        array $query,
        array $post,
        array $errors,
        string $successMessage,
        array $permissionsCatalog,
        int $page
    ): array {
        $mode = in_array($mode, ['create', 'grant', 'edit'], true) ? $mode : 'create';
        $meta = $this->formMeta($mode, $page);
        $state = $this->resolveFormState($mode, $query, $post, $permissionsCatalog);

        return [
            'viewTemplate' => 'admin/secondary_admins_form',
            'pageMode' => $mode,
            'formMeta' => $meta,
            'formState' => $state,
            'errors' => $errors,
            'successMessage' => $successMessage,
            'permissionsCatalog' => $permissionsCatalog,
            'permissionsTotal' => count($permissionsCatalog),
            'adminDashboardUrl' => $this->siteUrl('admin/dashboard'),
            'adminSupportAgentsUrl' => $this->siteUrl('admin/support_agents'),
            'listUrl' => $this->siteUrl($this->buildListPath($page)),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function resolveFormState(
        string $mode,
        array $query,
        array $post,
        array $permissionsCatalog
    ): array {
        $mode = in_array($mode, ['create', 'grant', 'edit'], true) ? $mode : 'create';

        $state = [
            'formValues' => [
                'first_name' => '',
                'last_name' => '',
                'email' => '',
                'password' => '',
                'user_id' => '',
            ],
            'selectedPermissions' => [],
            'targetUser' => null,
            'formNotice' => '',
        ];

        if ($mode === 'create') {
            $state['formValues']['first_name'] = trim((string) ($post['first_name'] ?? ''));
            $state['formValues']['last_name'] = trim((string) ($post['last_name'] ?? ''));
            $state['formValues']['email'] = strtolower(trim((string) ($post['email'] ?? '')));
            $state['selectedPermissions'] = AdminAccessService::normalizePermissions($post['permissions'] ?? []);

            return $state;
        }

        if ($mode === 'grant') {
            $state['formValues']['email'] = strtolower(trim((string) ($post['email'] ?? '')));
            $state['selectedPermissions'] = AdminAccessService::normalizePermissions($post['permissions'] ?? []);

            return $state;
        }

        $userId = max(0, (int) ($post['user_id'] ?? $query['user_id'] ?? $query['id'] ?? 0));
        $state['formValues']['user_id'] = (string) $userId;
        if ($userId < 1) {
            $state['formNotice'] = 'Sélectionnez un administrateur depuis la liste.';

            return $state;
        }

        $user = $this->model->findUserById($userId);
        if (!$user) {
            $state['formNotice'] = 'Utilisateur introuvable.';

            return $state;
        }

        $roles = $this->decodeRoles($user['roles_json'] ?? '[]');
        if (!in_array(AdminAccessService::ROLE_SECONDARY, $roles, true)) {
            $state['formNotice'] = 'Ce compte n\'est pas un administrateur secondaire.';

            return $state;
        }

        $permissionsRow = $this->model->fetchPermissionsByUserId($userId);
        $selectedPermissions = $permissionsRow
            ? AdminAccessService::normalizePermissions(json_decode((string) ($permissionsRow['permissions_json'] ?? '[]'), true))
            : [];

        if (array_key_exists('permissions', $post)) {
            $selectedPermissions = AdminAccessService::normalizePermissions($post['permissions'] ?? []);
        }

        $state['selectedPermissions'] = $selectedPermissions;
        $state['targetUser'] = $this->buildSecondaryAdminSummary($user, $permissionsRow, $permissionsCatalog);
        $state['formValues']['first_name'] = (string) ($user['first_name'] ?? '');
        $state['formValues']['last_name'] = (string) ($user['last_name'] ?? '');
        $state['formValues']['email'] = (string) ($user['email'] ?? '');

        return $state;
    }

    /**
     * @param array<string,mixed> $post
     * @param array<string,mixed> $currentUser
     * @param string $successMessage
     * @return array<int,string>
     */
    private function handleCreate(array $post, array $currentUser, string &$successMessage): array
    {
        $errors = [];
        $first = trim((string) ($post['first_name'] ?? ''));
        $last = trim((string) ($post['last_name'] ?? ''));
        $email = strtolower(trim((string) ($post['email'] ?? '')));
        $password = (string) ($post['password'] ?? '');
        $permissions = AdminAccessService::normalizePermissions($post['permissions'] ?? []);

        if ($first === '' || $last === '') {
            $errors[] = 'Prénom et nom sont obligatoires.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email invalide.';
        }
        $this->validateStrongPassword($password, $errors);
        if ($permissions === []) {
            $errors[] = 'Sélectionnez au moins une fonctionnalité.';
        }

        if ($errors === [] && $this->model->findUserByEmail($email)) {
            $errors[] = 'Un compte existe déjà avec cet e-mail.';
        }
        if ($errors !== []) {
            return $errors;
        }

        try {
            $this->model->beginTransaction();

            $userId = $this->model->createSecondaryAdminUser(
                $first,
                $last,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $this->encodeRoles([AdminAccessService::ROLE_SECONDARY])
            );
            $this->model->upsertPermissions(
                $userId,
                (int) ($currentUser['id'] ?? 0),
                $this->encodePermissions($permissions),
                true
            );

            $this->model->commit();
            $successMessage = 'Administrateur secondaire créé avec succès.';
        } catch (\Throwable $e) {
            if ($this->model->inTransaction()) {
                $this->model->rollBack();
            }

            $errors[] = $this->friendlyError($e);
        }

        return $errors;
    }

    /**
     * @param array<string,mixed> $post
     * @param array<string,mixed> $currentUser
     * @param string $successMessage
     * @return array<int,string>
     */
    private function handleGrant(array $post, array $currentUser, string &$successMessage): array
    {
        $errors = [];
        $email = strtolower(trim((string) ($post['email'] ?? '')));
        $permissions = AdminAccessService::normalizePermissions($post['permissions'] ?? []);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email invalide.';
        }
        if ($permissions === []) {
            $errors[] = 'Sélectionnez au moins une fonctionnalité.';
        }
        if ($errors !== []) {
            return $errors;
        }

        $user = $this->model->findUserByEmail($email);
        if (!$user) {
            return ['Utilisateur introuvable.'];
        }

        $roles = $this->decodeRoles($user['roles_json'] ?? '[]');
        if (in_array(AdminAccessService::ROLE_PRIMARY, $roles, true)) {
            return ['Ce compte est déjà administrateur général.'];
        }

        if (!in_array(AdminAccessService::ROLE_SECONDARY, $roles, true)) {
            $roles[] = AdminAccessService::ROLE_SECONDARY;
        }

        try {
            $this->model->beginTransaction();

            $this->model->updateRolesByUserId(
                (int) ($user['id'] ?? 0),
                $this->encodeRoles($roles)
            );
            $this->model->upsertPermissions(
                (int) ($user['id'] ?? 0),
                (int) ($currentUser['id'] ?? 0),
                $this->encodePermissions($permissions),
                true
            );

            $this->model->commit();
            $successMessage = 'Rôle d’administrateur secondaire ajouté ou mis à jour.';
        } catch (\Throwable $e) {
            if ($this->model->inTransaction()) {
                $this->model->rollBack();
            }

            $errors[] = $this->friendlyError($e);
        }

        return $errors;
    }

    /**
     * @param array<string,mixed> $post
     * @param array<string,mixed> $currentUser
     * @param string $successMessage
     * @return array<int,string>
     */
    private function handleUpdatePermissions(array $post, array $currentUser, string &$successMessage): array
    {
        $errors = [];
        $userId = (int) ($post['user_id'] ?? 0);
        $permissions = AdminAccessService::normalizePermissions($post['permissions'] ?? []);

        if ($userId < 1) {
            $errors[] = 'Utilisateur invalide.';
        }
        if ($permissions === []) {
            $errors[] = 'Sélectionnez au moins une fonctionnalité.';
        }
        if ($errors !== []) {
            return $errors;
        }

        $user = $this->model->findUserById($userId);
        if (!$user) {
            return ['Utilisateur introuvable.'];
        }

        $roles = $this->decodeRoles($user['roles_json'] ?? '[]');
        if (!in_array(AdminAccessService::ROLE_SECONDARY, $roles, true)) {
            return ['Ce compte n\'est pas un administrateur secondaire.'];
        }

        try {
            $existing = $this->model->fetchPermissionsByUserId($userId);
            $isActive = (int) ($existing['is_active'] ?? 1) === 1;
            $this->model->upsertPermissions(
                $userId,
                (int) ($currentUser['id'] ?? 0),
                $this->encodePermissions($permissions),
                $isActive
            );
            $successMessage = 'Permissions mises à jour.';
        } catch (\Throwable $e) {
            $errors[] = $this->friendlyError($e);
        }

        return $errors;
    }

    /**
     * @param array<string,mixed> $post
     * @param string $successMessage
     * @return array<int,string>
     */
    private function handleTogglePermissionsActive(array $post, string &$successMessage): array
    {
        $userId = (int) ($post['user_id'] ?? 0);
        $setActive = (int) ($post['set_active'] ?? 0) === 1;
        if ($userId < 1) {
            return ['Utilisateur invalide.'];
        }

        $user = $this->model->findUserById($userId);
        if (!$user) {
            return ['Utilisateur introuvable.'];
        }

        $roles = $this->decodeRoles($user['roles_json'] ?? '[]');
        if (!in_array(AdminAccessService::ROLE_SECONDARY, $roles, true)) {
            return ['Ce compte n\'est pas un administrateur secondaire.'];
        }

        $permRow = $this->model->fetchPermissionsByUserId($userId);
        if (!$permRow) {
            return ['Permissions introuvables pour ce compte.'];
        }

        try {
            $this->model->setPermissionsActive($userId, $setActive);
            $successMessage = $setActive
                ? 'Permissions réactivées.'
                : 'Permissions désactivées (compte conservé).';
        } catch (\Throwable $e) {
            return [$this->friendlyError($e)];
        }

        return [];
    }

    /**
     * @param array<string,mixed> $post
     * @param string $successMessage
     * @return array<int,string>
     */
    private function handleRevoke(array $post, string &$successMessage): array
    {
        $errors = [];
        $userId = (int) ($post['user_id'] ?? 0);
        if ($userId < 1) {
            return ['Utilisateur invalide.'];
        }

        $user = $this->model->findUserById($userId);
        if (!$user) {
            return ['Utilisateur introuvable.'];
        }

        $roles = $this->decodeRoles($user['roles_json'] ?? '[]');
        if (in_array(AdminAccessService::ROLE_PRIMARY, $roles, true)) {
            return ['Impossible de retirer les droits d\'un administrateur général ici.'];
        }

        $roles = array_values(array_filter(
            $roles,
            static fn (string $role): bool => $role !== AdminAccessService::ROLE_SECONDARY
        ));

        try {
            $this->model->beginTransaction();
            $this->model->updateRolesByUserId($userId, $this->encodeRoles($roles));
            $this->model->removePermissions($userId);
            $this->model->commit();
            $successMessage = 'Administrateur secondaire retiré.';
        } catch (\Throwable $e) {
            if ($this->model->inTransaction()) {
                $this->model->rollBack();
            }

            $errors[] = $this->friendlyError($e);
        }

        return $errors;
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed>|null $permissionsRow
     * @param array<string,string> $permissionsCatalog
     * @return array<string,mixed>
     */
    private function buildSecondaryAdminSummary(array $user, ?array $permissionsRow, array $permissionsCatalog): array
    {
        $permissions = [];
        if (is_array($permissionsRow)) {
            $permissions = AdminAccessService::normalizePermissions(
                json_decode((string) ($permissionsRow['permissions_json'] ?? '[]'), true)
            );
        }

        $permissionLabels = [];
        foreach ($permissions as $permission) {
            if (isset($permissionsCatalog[$permission])) {
                $permissionLabels[] = (string) $permissionsCatalog[$permission];
            }
        }

        $displayName = trim((string) ($user['first_name'] ?? '') . ' ' . (string) ($user['last_name'] ?? ''));
        if ($displayName === '') {
            $displayName = 'Administrateur secondaire';
        }

        $isActive = is_array($permissionsRow) ? (int) ($permissionsRow['is_active'] ?? 0) === 1 : false;

        return [
            'id' => (int) ($user['id'] ?? 0),
            'first_name' => (string) ($user['first_name'] ?? ''),
            'last_name' => (string) ($user['last_name'] ?? ''),
            'display_name' => $displayName,
            'email' => (string) ($user['email'] ?? ''),
            'created_at' => (string) ($user['created_at'] ?? ''),
            'status' => $isActive ? 'active' : 'suspended',
            'status_label' => $isActive ? 'Actif' : 'Suspendu',
            'is_active' => $isActive,
            'permissions' => $permissions,
            'permission_labels' => $permissionLabels,
            'permissions_count' => count($permissions),
            'role' => $this->deriveRole($permissions, count($permissionsCatalog)),
        ];
    }

    /**
     * @return array{title:string,subtitle:string,badge:string,badgeTone:string,heroTitle:string,heroLead:string,submitLabel:string,submitIcon:string,formActionUrl:string,showIdentityFields:bool,showPasswordField:bool,showEmailField:bool,showTargetSummary:bool,notice:string}
     */
    private function formMeta(string $mode, int $page): array
    {
        $listUrl = $this->siteUrl($this->buildListPath($page));

        return match ($mode) {
            'grant' => [
                'title' => 'Attribuer un compte',
                'subtitle' => 'Attribution',
                'badge' => 'Attribution',
                'badgeTone' => 'pending',
                'heroTitle' => 'Attribuer le rôle administrateur secondaire',
                'heroLead' => 'Choisissez un compte existant puis sélectionnez les fonctionnalités à déléguer.',
                'submitLabel' => 'Attribuer le rôle',
                'submitIcon' => 'key',
                'formActionUrl' => $this->siteUrl('admin/secondary_admins/grant'),
                'showIdentityFields' => false,
                'showPasswordField' => false,
                'showEmailField' => true,
                'showTargetSummary' => false,
                'notice' => 'Le compte doit déjà exister avant l’attribution du rôle.',
                'listUrl' => $listUrl,
            ],
            'edit' => [
                'title' => 'Modifier un administrateur',
                'subtitle' => 'Modification',
                'badge' => 'Modification',
                'badgeTone' => 'active',
                'heroTitle' => 'Modifier les permissions',
                'heroLead' => 'Ajustez les fonctionnalités autorisées sans toucher à l’identité ni au mot de passe.',
                'submitLabel' => 'Enregistrer les modifications',
                'submitIcon' => 'check',
                'formActionUrl' => $this->siteUrl('admin/secondary_admins/edit'),
                'showIdentityFields' => false,
                'showPasswordField' => false,
                'showEmailField' => false,
                'showTargetSummary' => true,
                'notice' => 'Les permissions sont mises à jour sur le compte sélectionné.',
                'listUrl' => $listUrl,
            ],
            default => [
                'title' => 'Nouvel administrateur',
                'subtitle' => 'Création',
                'badge' => 'Création',
                'badgeTone' => 'active',
                'heroTitle' => 'Créer un administrateur secondaire',
                'heroLead' => 'Renseignez l’identité, le mot de passe temporaire et les permissions à déléguer.',
                'submitLabel' => 'Créer l’administrateur',
                'submitIcon' => 'plus',
                'formActionUrl' => $this->siteUrl('admin/secondary_admins/create'),
                'showIdentityFields' => true,
                'showPasswordField' => true,
                'showEmailField' => true,
                'showTargetSummary' => false,
                'notice' => 'Le mot de passe temporaire doit être transmis de façon sécurisée.',
                'listUrl' => $listUrl,
            ],
        };
    }

    private function resolveRequestMode(string $routeMode, string $action): string
    {
        return match ($action) {
            'create' => 'create',
            'grant' => 'grant',
            'update_permissions' => 'edit',
            default => $routeMode,
        };
    }

    private function resolvePage(array $query, array $post): int
    {
        $page = Pagination::pageFromQuery($query, 'page');
        if ($post !== []) {
            $page = Pagination::pageFromQuery($post, 'page', $page);
        }

        return max(1, $page);
    }

    private function buildListPath(int $page): string
    {
        $page = max(1, $page);

        return 'admin/secondary_admins' . ($page > 1 ? ('?page=' . $page) : '');
    }

    /**
     * @param array<int,string> $roles
     * @return array{key:string,label:string,icon:string}
     */
    private function deriveRole(array $roles, int $availablePermissions): array
    {
        $roles = array_values(array_unique(array_map('strval', $roles)));
        $permissionCount = count($roles);
        $has = static fn (string $permission): bool => in_array($permission, $roles, true);

        if (
            $permissionCount >= max(1, $availablePermissions - 1)
            || $has('dashboard')
            || $has('site_settings')
            || $has('sales')
        ) {
            return ['key' => 'superviseur', 'label' => 'Superviseur', 'icon' => 'fa-crown'];
        }

        if ($has('courses') || $has('categories') || $has('teacher_applications') || $has('announcements')) {
            return ['key' => 'moderateur', 'label' => 'Modérateur', 'icon' => 'fa-user-shield'];
        }

        return ['key' => 'support', 'label' => 'Support', 'icon' => 'fa-headset'];
    }

    /**
     * @return array<int,string>
     */
    private function decodeRoles(mixed $rolesJson): array
    {
        if (!is_string($rolesJson) || trim($rolesJson) === '') {
            return [];
        }

        $decoded = json_decode($rolesJson, true);
        if (!is_array($decoded)) {
            return [];
        }

        $roles = [];
        foreach ($decoded as $role) {
            if (is_string($role) && $role !== '') {
                $roles[] = $role;
            }
        }

        return array_values(array_unique($roles));
    }

    /**
     * @param array<int,string> $roles
     */
    private function encodeRoles(array $roles): string
    {
        $encoded = json_encode(array_values(array_unique($roles)));

        return is_string($encoded) ? $encoded : '[]';
    }

    /**
     * @param array<int,string> $permissions
     */
    private function encodePermissions(array $permissions): string
    {
        $encoded = json_encode(array_values($permissions));

        return is_string($encoded) ? $encoded : '[]';
    }

    private function friendlyError(\Throwable $e): string
    {
        $message = strtolower($e->getMessage());

        if (str_contains($message, 'admin_secondary_permissions')) {
            return 'Migration manquante: appliquez database/schema_v33.sql puis réessayez.';
        }

        return 'Opération impossible pour le moment.';
    }

    /**
     * @param array<int,string> $errors
     */
    private function validateStrongPassword(string $password, array &$errors): void
    {
        if (strlen($password) < 8) {
            $errors[] = 'Mot de passe : 8 caractères minimum.';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Mot de passe : au moins une lettre majuscule.';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Mot de passe : au moins un chiffre.';
        }

        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Mot de passe : au moins un caractère spécial.';
        }
    }
}
