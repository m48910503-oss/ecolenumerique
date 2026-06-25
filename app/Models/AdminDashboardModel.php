<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class AdminDashboardModel extends Model
{
    private ?bool $hasAnnouncementColumn = null;

    public function countUnreadNotifications(int $userId): int
    {
        $sql = 'SELECT COUNT(*) AS unread FROM notifications WHERE user_id = :uid AND is_read = 0';
        if ($this->hasAnnouncementColumn()) {
            $sql .= ' AND (announcement_id IS NULL OR announcement_id = 0)';
        }

        return $this->intQuery($sql, [':uid' => $userId]);
    }

    private function hasAnnouncementColumn(): bool
    {
        if ($this->hasAnnouncementColumn !== null) {
            return $this->hasAnnouncementColumn;
        }

        try {
            $this->hasAnnouncementColumn = $this->columnExists('notifications', 'announcement_id');
        } catch (\Throwable $e) {
            $this->hasAnnouncementColumn = false;
        }

        return $this->hasAnnouncementColumn;
    }

    public function countOpenSupportTickets(): int
    {
        if (!$this->tableExistsSafe('support_conversations')) {
            return 0;
        }
        return $this->intQuery(
            'SELECT COUNT(*) FROM support_conversations WHERE status IN ("open","waiting_support","waiting_user")'
        );
    }

    public function countWaitingSupportTickets(): int
    {
        if (!$this->tableExistsSafe('support_conversations')) {
            return 0;
        }
        return $this->intQuery(
            'SELECT COUNT(*) FROM support_conversations WHERE status = "waiting_support"'
        );
    }

    public function averageFirstResponseMinutes(int $lookbackDays = 14): int
    {
        if (!$this->tableExistsSafe('support_conversations') || !$this->tableExistsSafe('support_messages')) {
            return 0;
        }

        $days = max(1, min(90, $lookbackDays));
        $sql = 'SELECT COALESCE(AVG(TIMESTAMPDIFF(MINUTE, c.created_at, fr.first_reply_at)), 0)
                FROM support_conversations c
                JOIN (
                    SELECT m.conversation_id, MIN(m.created_at) AS first_reply_at
                    FROM support_messages m
                    JOIN support_conversations c2 ON c2.id = m.conversation_id
                    WHERE m.sender_id <> c2.created_by
                    GROUP BY m.conversation_id
                ) fr ON fr.conversation_id = c.id
                WHERE c.created_at >= DATE_SUB(NOW(), INTERVAL ' . $days . ' DAY)';

        return (int) round($this->floatQuery($sql));
    }

    public function countPendingCourses(): int
    {
        if (!$this->tableExistsSafe('courses')) {
            return 0;
        }
        return $this->intQuery(
            'SELECT COUNT(*) FROM courses WHERE status = "submitted" AND (deleted_at IS NULL)'
        );
    }

    public function countPublishedCourses(): int
    {
        if (!$this->tableExistsSafe('courses')) {
            return 0;
        }
        return $this->intQuery(
            'SELECT COUNT(*) FROM courses WHERE status = "approved" AND (deleted_at IS NULL)'
        );
    }

    public function countPendingWithdrawals(): int
    {
        if (!$this->tableExistsSafe('withdrawals')) {
            return 0;
        }
        return $this->intQuery(
            'SELECT COUNT(*) FROM withdrawals WHERE status IN ("requested","approved")'
        );
    }

    public function countRequestedWithdrawals(): int
    {
        if (!$this->tableExistsSafe('withdrawals')) {
            return 0;
        }
        return $this->intQuery(
            'SELECT COUNT(*) FROM withdrawals WHERE status = "requested"'
        );
    }

    public function countPendingTeacherApplications(): int
    {
        if (!$this->tableExistsSafe('teacher_applications')) {
            return 0;
        }
        return $this->intQuery(
            'SELECT COUNT(*) FROM teacher_applications WHERE status = "pending"'
        );
    }

    public function countFailedPayments(): int
    {
        if (!$this->tableExistsSafe('payments')) {
            return 0;
        }
        return $this->intQuery('SELECT COUNT(*) FROM payments WHERE status = "failed"');
    }

    public function countPendingPayments(): int
    {
        if (!$this->tableExistsSafe('payments')) {
            return 0;
        }
        return $this->intQuery('SELECT COUNT(*) FROM payments WHERE status = "pending"');
    }

    public function countPaidOrders(): int
    {
        if (!$this->tableExistsSafe('orders')) {
            return 0;
        }
        return $this->intQuery('SELECT COUNT(*) FROM orders WHERE status = "paid"');
    }

    public function countFailedPushJobs(): int
    {
        if (!$this->tableExistsSafe('push_notification_queue')) {
            return 0;
        }
        return $this->intQuery('SELECT COUNT(*) FROM push_notification_queue WHERE status = "failed"');
    }

    public function countPendingPushJobs(): int
    {
        if (!$this->tableExistsSafe('push_notification_queue')) {
            return 0;
        }
        return $this->intQuery('SELECT COUNT(*) FROM push_notification_queue WHERE status IN ("pending","processing")');
    }

    public function oldestPendingPushDelayMinutes(): int
    {
        if (!$this->tableExistsSafe('push_notification_queue')) {
            return 0;
        }
        return $this->intQuery(
            'SELECT COALESCE(MAX(TIMESTAMPDIFF(MINUTE, next_attempt_at, NOW())), 0)
             FROM push_notification_queue
             WHERE status IN ("pending","processing")'
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchSupportQueueCandidates(int $limit = 6): array
    {
        if (!$this->tableExistsSafe('support_conversations')) {
            return [];
        }
        $safeLimit = max(1, min(20, $limit));
        return $this->rowsQuery(
            'SELECT id, subject, status, last_message_at, created_at
             FROM support_conversations
             WHERE status IN ("open","waiting_support","waiting_user")
             ORDER BY
                CASE status
                    WHEN "waiting_user" THEN 0
                    WHEN "waiting_support" THEN 1
                    ELSE 2
                END ASC,
                last_message_at ASC,
                created_at ASC
             LIMIT ' . $safeLimit
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchCoursePriorityCandidates(int $limit = 6): array
    {
        if (!$this->tableExistsSafe('courses')) {
            return [];
        }
        $safeLimit = max(1, min(20, $limit));
        return $this->rowsQuery(
            'SELECT id, title, submitted_at, updated_at
             FROM courses
             WHERE status = "submitted" AND deleted_at IS NULL
             ORDER BY submitted_at ASC, updated_at ASC
             LIMIT ' . $safeLimit
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchWithdrawalPriorityCandidates(int $limit = 6): array
    {
        if (!$this->tableExistsSafe('withdrawals')) {
            return [];
        }
        $safeLimit = max(1, min(20, $limit));
        return $this->rowsQuery(
            'SELECT id, user_id, amount_xof, status, created_at
             FROM withdrawals
             WHERE status IN ("requested","approved")
             ORDER BY created_at ASC
             LIMIT ' . $safeLimit
        );
    }

    /**
     * @param array<string,mixed> $meta
     */
    public function insertAuditLog(?int $userId, string $action, string $targetType, ?int $targetId = null, array $meta = []): void
    {
        if (!$this->tableExistsSafe('audit_logs')) {
            return;
        }

        if (!array_key_exists('actor_role', $meta) && $userId !== null && $userId > 0) {
            $actorRole = $this->resolveAuditActorRole($userId);
            if ($actorRole !== null) {
                $meta['actor_role'] = $actorRole;
            }
        }

        $metaJson = null;
        if ($meta !== []) {
            $encoded = json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (is_string($encoded) && $encoded !== '') {
                $metaJson = $encoded;
            }
        }

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO audit_logs (user_id, action, target_type, target_id, meta_json, created_at)
                 VALUES (:user_id, :action, :target_type, :target_id, :meta_json, NOW())'
            );
            $stmt->execute([
                ':user_id' => $userId !== null && $userId > 0 ? $userId : null,
                ':action' => $action,
                ':target_type' => $targetType,
                ':target_id' => $targetId,
                ':meta_json' => $metaJson,
            ]);
        } catch (\Throwable $e) {
            // Audit logging must not block the requested admin action.
        }
    }

    /**
     * @param array<string,mixed> $filters
     * @return array{0:string,1:array<string,mixed>}
     */
    private function buildAuditFilters(array $filters, bool $includeSearch = true): array
    {
        $where = [];
        $params = [];

        $from = trim((string) ($filters['from'] ?? ''));
        if ($from !== '') {
            $where[] = 'al.created_at >= :from_at';
            $params[':from_at'] = $from . ' 00:00:00';
        }

        $to = trim((string) ($filters['to'] ?? ''));
        if ($to !== '') {
            $where[] = 'al.created_at <= :to_at';
            $params[':to_at'] = $to . ' 23:59:59';
        }

        $adminId = max(0, (int) ($filters['admin_id'] ?? 0));
        if ($adminId > 0) {
            $where[] = 'al.user_id = :admin_id';
            $params[':admin_id'] = $adminId;
        }

        $module = trim((string) ($filters['module'] ?? ''));
        if ($module !== '') {
            $where[] = 'al.target_type = :module';
            $params[':module'] = $module;
        }

        if (!empty($filters['secondary_only'])) {
            $where[] = '(u.roles_json LIKE :secondary_role_pattern OR COALESCE(al.meta_json, "") LIKE :secondary_role_meta_pattern)';
            $params[':secondary_role_pattern'] = '%"admin_secondary"%';
            $params[':secondary_role_meta_pattern'] = '%"actor_role":"admin_secondary"%';
        }

        if ($includeSearch) {
            $search = trim((string) ($filters['search'] ?? ''));
            if ($search !== '') {
                $where[] = '(
                    al.action LIKE :search
                    OR al.target_type LIKE :search
                    OR CAST(al.target_id AS CHAR) LIKE :search
                    OR u.first_name LIKE :search
                    OR u.last_name LIKE :search
                    OR u.email LIKE :search
                    OR COALESCE(al.meta_json, "") LIKE :search
                )';
                $params[':search'] = '%' . $search . '%';
            }
        }

        return [$where !== [] ? ('WHERE ' . implode(' AND ', $where)) : '', $params];
    }

    private function resolveAuditActorRole(int $userId): ?string
    {
        if ($userId < 1 || !$this->tableExistsSafe('users')) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT roles_json
                 FROM users
                 WHERE id = :id
                 LIMIT 1'
            );
            $stmt->execute([':id' => $userId]);
            $row = $stmt->fetch();
        } catch (\Throwable $e) {
            return null;
        }

        if (!is_array($row)) {
            return null;
        }

        $roles = json_decode((string) ($row['roles_json'] ?? '[]'), true);
        if (!is_array($roles)) {
            return null;
        }

        if (in_array('admin_secondary', $roles, true)) {
            return 'admin_secondary';
        }
        if (in_array('admin', $roles, true)) {
            return 'admin';
        }

        return null;
    }

    /**
     * @param array<string,mixed> $filters
     */
    public function countRecentAuditLogs(array $filters): int
    {
        if (!$this->tableExistsSafe('audit_logs')) {
            return 0;
        }

        [$whereSql, $params] = $this->buildAuditFilters($filters);

        return $this->intQuery(
            'SELECT COUNT(*)
             FROM audit_logs al
             LEFT JOIN users u ON u.id = al.user_id
             ' . $whereSql,
            $params
        );
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    public function fetchRecentAuditLogs(array $filters, int $limit = 20, int $offset = 0): array
    {
        if (!$this->tableExistsSafe('audit_logs')) {
            return [];
        }

        $safeLimit = max(1, min(100, $limit));
        $safeOffset = max(0, $offset);
        [$whereSql, $params] = $this->buildAuditFilters($filters);
        $sql = 'SELECT
                    al.id,
                    al.action,
                    al.target_type,
                    al.target_id,
                    al.meta_json,
                    al.created_at,
                    u.id AS actor_id,
                    u.first_name AS actor_first_name,
                    u.last_name AS actor_last_name,
                    u.email AS actor_email
                FROM audit_logs al
                LEFT JOIN users u ON u.id = al.user_id
                ' . $whereSql . '
                ORDER BY al.created_at DESC, al.id DESC
                LIMIT ' . $safeLimit . ' OFFSET ' . $safeOffset;

        return $this->rowsQuery($sql, $params);
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    public function fetchAuditActors(array $filters = [], int $limit = 25): array
    {
        if (!$this->tableExistsSafe('audit_logs')) {
            return [];
        }
        $safeLimit = max(1, min(100, $limit));
        [$whereSql, $params] = $this->buildAuditFilters($filters, false);
        return $this->rowsQuery(
            'SELECT
                u.id,
                u.first_name,
                u.last_name,
                u.email,
                COUNT(*) AS action_count
             FROM audit_logs al
             JOIN users u ON u.id = al.user_id
             ' . $whereSql . '
             GROUP BY u.id, u.first_name, u.last_name, u.email
             ORDER BY action_count DESC, u.last_name ASC
             LIMIT ' . $safeLimit,
            $params
        );
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<int,string>
     */
    public function fetchAuditModules(array $filters = [], int $limit = 30): array
    {
        if (!$this->tableExistsSafe('audit_logs')) {
            return [];
        }
        $safeLimit = max(1, min(100, $limit));
        [$whereSql, $params] = $this->buildAuditFilters($filters, false);
        $sql = 'SELECT DISTINCT al.target_type
                FROM audit_logs al
                LEFT JOIN users u ON u.id = al.user_id
                ';
        $sql .= $whereSql !== ''
            ? $whereSql . ' AND al.target_type IS NOT NULL AND al.target_type <> ""'
            : 'WHERE al.target_type IS NOT NULL AND al.target_type <> ""';
        $sql .= '
                ORDER BY al.target_type ASC
                LIMIT ' . $safeLimit;
        $rows = $this->rowsQuery($sql, $params);
        $modules = [];
        foreach ($rows as $row) {
            $module = trim((string) ($row['target_type'] ?? ''));
            if ($module !== '') {
                $modules[] = $module;
            }
        }
        return $modules;
    }

    /**
     * @param array<int,string> $permissionKeys
     * @return array{secondary_total:int,permission_counts:array<string,int>,support_agents:int}
     */
    public function fetchPermissionCoverage(array $permissionKeys): array
    {
        $counts = [];
        foreach ($permissionKeys as $permission) {
            if (is_string($permission) && $permission !== '') {
                $counts[$permission] = 0;
            }
        }

        $secondaryTotal = 0;
        if ($counts !== [] && $this->tableExistsSafe('admin_secondary_permissions')) {
            $rows = $this->rowsQuery(
                'SELECT permissions_json
                 FROM admin_secondary_permissions
                 WHERE is_active = 1'
            );
            $secondaryTotal = count($rows);
            foreach ($rows as $row) {
                $decoded = json_decode((string) ($row['permissions_json'] ?? '[]'), true);
                if (!is_array($decoded)) {
                    continue;
                }
                foreach ($decoded as $permission) {
                    if (is_string($permission) && array_key_exists($permission, $counts)) {
                        $counts[$permission]++;
                    }
                }
            }
        }

        $supportAgents = 0;
        if ($this->tableExistsSafe('users')) {
            $supportAgents = $this->intQuery(
                'SELECT COUNT(*)
                 FROM users
                 WHERE status = "active" AND roles_json LIKE \'%"support"%\''
            );
        }

        return [
            'secondary_total' => $secondaryTotal,
            'permission_counts' => $counts,
            'support_agents' => $supportAgents,
        ];
    }

    public function countRecentNotifications(int $hours = 24): int
    {
        if (!$this->tableExistsSafe('notifications')) {
            return 0;
        }
        $safeHours = max(1, min(168, $hours));
        return $this->intQuery(
            'SELECT COUNT(*) FROM notifications WHERE created_at >= DATE_SUB(NOW(), INTERVAL ' . $safeHours . ' HOUR)'
        );
    }

    public function sumPaidOrdersTotalXof(): int
    {
        if (!$this->tableExistsSafe('orders')) {
            return 0;
        }
        return $this->intQuery(
            'SELECT COALESCE(SUM(total_xof), 0) FROM orders WHERE status = "paid"'
        );
    }

    public function commissionPercent(float $default = 20.0): float
    {
        if (!$this->tableExistsSafe('platform_settings')) {
            return $default;
        }
        $raw = trim((string) $this->scalarQuery(
            'SELECT setting_value FROM platform_settings WHERE setting_key = "commission_percent" LIMIT 1'
        ));
        if ($raw === '' || !is_numeric($raw)) {
            return $default;
        }
        $value = (float) $raw;
        if ($value < 0 || $value > 100) {
            return $default;
        }
        return $value;
    }

    public function countRegisteredUsers(): int
    {
        if (!$this->tableExistsSafe('users')) {
            return 0;
        }
        return $this->intQuery(
            'SELECT COUNT(*) FROM users WHERE status IN ("active","pending_email")'
        );
    }

    public function countActiveUsersByRole(string $role): int
    {
        $normalizedRole = trim($role);
        if ($normalizedRole === '' || !$this->tableExistsSafe('users')) {
            return 0;
        }
        return $this->intQuery(
            'SELECT COUNT(*)
             FROM users
             WHERE status = "active"
               AND roles_json LIKE :role_pattern',
            [':role_pattern' => '%"' . $normalizedRole . '"%']
        );
    }

    public function countActiveCompanies(): int
    {
        if (!$this->tableExistsSafe('companies')) {
            return 0;
        }
        return $this->intQuery(
            'SELECT COUNT(*) FROM companies WHERE status = "active"'
        );
    }

    public function countUsersCreatedSince(string $date): int
    {
        if (!$this->tableExistsSafe('users') || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return 0;
        }
        return $this->intQuery(
            'SELECT COUNT(*) FROM users WHERE DATE(created_at) >= :date_from',
            [':date_from' => $date]
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchPendingCourseCards(int $limit = 3): array
    {
        if (!$this->tableExistsSafe('courses') || !$this->tableExistsSafe('users')) {
            return [];
        }

        $safeLimit = max(1, min(12, $limit));
        return $this->rowsQuery(
            'SELECT
                c.id,
                c.title,
                c.cover_path,
                c.submitted_at,
                c.updated_at,
                u.first_name,
                u.last_name,
                cat.name AS category_name
             FROM courses c
             JOIN users u ON u.id = c.teacher_id
             LEFT JOIN categories cat ON cat.id = c.category_id
             WHERE c.status = "submitted" AND c.deleted_at IS NULL
             ORDER BY c.submitted_at ASC, c.id ASC
             LIMIT ' . $safeLimit
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchRecentUsers(int $limit = 4): array
    {
        if (!$this->tableExistsSafe('users')) {
            return [];
        }

        $safeLimit = max(1, min(12, $limit));
        return $this->rowsQuery(
            'SELECT id, first_name, last_name, email, roles_json, status, created_at
             FROM users
             ORDER BY created_at DESC, id DESC
             LIMIT ' . $safeLimit
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchRecentTransactions(int $limit = 5): array
    {
        if (!$this->tableExistsSafe('orders') || !$this->tableExistsSafe('users')) {
            return [];
        }

        $safeLimit = max(1, min(20, $limit));
        return $this->rowsQuery(
            'SELECT
                o.id,
                o.reference,
                o.total_xof,
                o.status,
                o.provider,
                o.created_at,
                u.first_name AS buyer_first,
                u.last_name AS buyer_last,
                GROUP_CONCAT(c.title ORDER BY oi.id SEPARATOR ", ") AS course_titles
             FROM orders o
             JOIN users u ON u.id = o.user_id
             LEFT JOIN order_items oi ON oi.order_id = o.id
             LEFT JOIN courses c ON c.id = oi.course_id
             GROUP BY
                o.id,
                o.reference,
                o.total_xof,
                o.status,
                o.provider,
                o.created_at,
                u.first_name,
                u.last_name
             ORDER BY o.created_at DESC, o.id DESC
             LIMIT ' . $safeLimit
        );
    }

    /**
     * @return array<int,array{label:string,value:int}>
     */
    public function fetchMonthlyRegistrationCounts(int $months = 6): array
    {
        if (!$this->tableExistsSafe('users')) {
            return [];
        }

        $safeMonths = max(1, min(12, $months));
        $intervalMonths = max(0, $safeMonths - 1);
        $rows = $this->rowsQuery(
            'SELECT DATE_FORMAT(created_at, "%Y-%m-01") AS month_key, COUNT(*) AS total
             FROM users
             WHERE created_at >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL ' . $intervalMonths . ' MONTH), "%Y-%m-01")
             GROUP BY month_key
             ORDER BY month_key ASC'
        );

        $counts = [];
        foreach ($rows as $row) {
            $key = (string) ($row['month_key'] ?? '');
            if ($key !== '') {
                $counts[$key] = (int) ($row['total'] ?? 0);
            }
        }

        $monthLabels = [
            1 => 'Jan',
            2 => 'Fév',
            3 => 'Mar',
            4 => 'Avr',
            5 => 'Mai',
            6 => 'Juin',
            7 => 'Juil',
            8 => 'Août',
            9 => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Déc',
        ];

        $result = [];
        $current = new \DateTimeImmutable('first day of this month');
        for ($i = $safeMonths - 1; $i >= 0; $i--) {
            $month = $current->modify('-' . $i . ' months');
            $key = $month->format('Y-m-01');
            $monthNumber = (int) $month->format('n');
            $result[] = [
                'label' => $monthLabels[$monthNumber] ?? $month->format('M'),
                'value' => (int) ($counts[$key] ?? 0),
            ];
        }

        return $result;
    }

    private function tableExistsSafe(string $table): bool
    {
        try {
            return $this->tableExists($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @param array<string,mixed> $params
     */
    private function intQuery(string $sql, array $params = []): int
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * @param array<string,mixed> $params
     */
    private function floatQuery(string $sql, array $params = []): float
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return (float) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    /**
     * @param array<string,mixed> $params
     */
    private function scalarQuery(string $sql, array $params = []): string
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $value = $stmt->fetchColumn();
            return $value === false ? '' : (string) $value;
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * @param array<string,mixed> $params
     * @return array<int,array<string,mixed>>
     */
    private function rowsQuery(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll() ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}
