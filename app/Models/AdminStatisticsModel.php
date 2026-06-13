<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class AdminStatisticsModel extends Model
{
    /**
     * @return array<string,int>
     */
    public function fetchPaidRevenueSnapshot(int $days = 30): array
    {
        $snapshot = [
            'current_total_xof' => 0,
            'previous_total_xof' => 0,
            'current_orders' => 0,
            'previous_orders' => 0,
        ];

        if (!$this->tableExistsSafe('orders')) {
            return $snapshot;
        }

        $safeDays = max(1, min(365, $days));
        $previousWindowDays = $safeDays * 2;
        $paidStatuses = $this->paidOrderStatusSql();

        $row = $this->rowQuery(
            'SELECT
                COALESCE(SUM(CASE
                    WHEN ' . $paidStatuses . ' AND created_at >= DATE_SUB(NOW(), INTERVAL ' . $safeDays . ' DAY)
                        THEN total_xof
                    ELSE 0
                END), 0) AS current_total_xof,
                COALESCE(SUM(CASE
                    WHEN ' . $paidStatuses . '
                        AND created_at < DATE_SUB(NOW(), INTERVAL ' . $safeDays . ' DAY)
                        AND created_at >= DATE_SUB(NOW(), INTERVAL ' . $previousWindowDays . ' DAY)
                        THEN total_xof
                    ELSE 0
                END), 0) AS previous_total_xof,
                COALESCE(SUM(CASE
                    WHEN ' . $paidStatuses . ' AND created_at >= DATE_SUB(NOW(), INTERVAL ' . $safeDays . ' DAY)
                        THEN 1
                    ELSE 0
                END), 0) AS current_orders,
                COALESCE(SUM(CASE
                    WHEN ' . $paidStatuses . '
                        AND created_at < DATE_SUB(NOW(), INTERVAL ' . $safeDays . ' DAY)
                        AND created_at >= DATE_SUB(NOW(), INTERVAL ' . $previousWindowDays . ' DAY)
                        THEN 1
                    ELSE 0
                END), 0) AS previous_orders
             FROM orders'
        );

        if ($row === []) {
            return $snapshot;
        }

        return [
            'current_total_xof' => (int) ($row['current_total_xof'] ?? 0),
            'previous_total_xof' => (int) ($row['previous_total_xof'] ?? 0),
            'current_orders' => (int) ($row['current_orders'] ?? 0),
            'previous_orders' => (int) ($row['previous_orders'] ?? 0),
        ];
    }

    /**
     * @return array<string,int>
     */
    public function fetchUserGrowthSnapshot(int $days = 30): array
    {
        $snapshot = [
            'current_users' => 0,
            'previous_users' => 0,
            'current_teachers' => 0,
            'current_students' => 0,
        ];

        if (!$this->tableExistsSafe('users')) {
            return $snapshot;
        }

        $safeDays = max(1, min(365, $days));
        $previousWindowDays = $safeDays * 2;

        $row = $this->rowQuery(
            'SELECT
                COALESCE(SUM(CASE
                    WHEN status IN ("active", "pending_email")
                        AND created_at >= DATE_SUB(NOW(), INTERVAL ' . $safeDays . ' DAY)
                        THEN 1
                    ELSE 0
                END), 0) AS current_users,
                COALESCE(SUM(CASE
                    WHEN status IN ("active", "pending_email")
                        AND created_at < DATE_SUB(NOW(), INTERVAL ' . $safeDays . ' DAY)
                        AND created_at >= DATE_SUB(NOW(), INTERVAL ' . $previousWindowDays . ' DAY)
                        THEN 1
                    ELSE 0
                END), 0) AS previous_users,
                COALESCE(SUM(CASE
                    WHEN status IN ("active", "pending_email")
                        AND created_at >= DATE_SUB(NOW(), INTERVAL ' . $safeDays . ' DAY)
                        AND roles_json LIKE ' . $this->quote('%"teacher"%') . '
                        THEN 1
                    ELSE 0
                END), 0) AS current_teachers,
                COALESCE(SUM(CASE
                    WHEN status IN ("active", "pending_email")
                        AND created_at >= DATE_SUB(NOW(), INTERVAL ' . $safeDays . ' DAY)
                        AND roles_json LIKE ' . $this->quote('%"student"%') . '
                        THEN 1
                    ELSE 0
                END), 0) AS current_students
             FROM users'
        );

        if ($row === []) {
            return $snapshot;
        }

        return [
            'current_users' => (int) ($row['current_users'] ?? 0),
            'previous_users' => (int) ($row['previous_users'] ?? 0),
            'current_teachers' => (int) ($row['current_teachers'] ?? 0),
            'current_students' => (int) ($row['current_students'] ?? 0),
        ];
    }

    /**
     * @return array<string,int>
     */
    public function fetchCompanyGrowthSnapshot(int $days = 30): array
    {
        $snapshot = [
            'current_companies' => 0,
            'previous_companies' => 0,
        ];

        if (!$this->tableExistsSafe('companies')) {
            return $snapshot;
        }

        $safeDays = max(1, min(365, $days));
        $previousWindowDays = $safeDays * 2;

        $row = $this->rowQuery(
            'SELECT
                COALESCE(SUM(CASE
                    WHEN status = "active"
                        AND created_at >= DATE_SUB(NOW(), INTERVAL ' . $safeDays . ' DAY)
                        THEN 1
                    ELSE 0
                END), 0) AS current_companies,
                COALESCE(SUM(CASE
                    WHEN status = "active"
                        AND created_at < DATE_SUB(NOW(), INTERVAL ' . $safeDays . ' DAY)
                        AND created_at >= DATE_SUB(NOW(), INTERVAL ' . $previousWindowDays . ' DAY)
                        THEN 1
                    ELSE 0
                END), 0) AS previous_companies
             FROM companies'
        );

        if ($row === []) {
            return $snapshot;
        }

        return [
            'current_companies' => (int) ($row['current_companies'] ?? 0),
            'previous_companies' => (int) ($row['previous_companies'] ?? 0),
        ];
    }

    public function countActiveEnrollments(): int
    {
        if (!$this->tableExistsSafe('enrollments')) {
            return 0;
        }

        return $this->intQuery(
            'SELECT COUNT(*) FROM enrollments WHERE status = "active"'
        );
    }

    /**
     * @return array<string,int>
     */
    public function fetchPaidRevenueSnapshotForRange(
        string $fromInclusive,
        string $toExclusive,
        string $previousFromInclusive,
        string $previousToExclusive
    ): array {
        $snapshot = [
            'current_total_xof' => 0,
            'previous_total_xof' => 0,
            'current_orders' => 0,
            'previous_orders' => 0,
        ];

        if (!$this->tableExistsSafe('orders')) {
            return $snapshot;
        }

        $paidStatuses = $this->paidOrderStatusSql();
        $row = $this->rowQuery(
            'SELECT
                COALESCE(SUM(CASE
                    WHEN ' . $paidStatuses . '
                        AND created_at >= ' . $this->quote($fromInclusive) . '
                        AND created_at < ' . $this->quote($toExclusive) . '
                        THEN total_xof
                    ELSE 0
                END), 0) AS current_total_xof,
                COALESCE(SUM(CASE
                    WHEN ' . $paidStatuses . '
                        AND created_at >= ' . $this->quote($previousFromInclusive) . '
                        AND created_at < ' . $this->quote($previousToExclusive) . '
                        THEN total_xof
                    ELSE 0
                END), 0) AS previous_total_xof,
                COALESCE(SUM(CASE
                    WHEN ' . $paidStatuses . '
                        AND created_at >= ' . $this->quote($fromInclusive) . '
                        AND created_at < ' . $this->quote($toExclusive) . '
                        THEN 1
                    ELSE 0
                END), 0) AS current_orders,
                COALESCE(SUM(CASE
                    WHEN ' . $paidStatuses . '
                        AND created_at >= ' . $this->quote($previousFromInclusive) . '
                        AND created_at < ' . $this->quote($previousToExclusive) . '
                        THEN 1
                    ELSE 0
                END), 0) AS previous_orders
             FROM orders'
        );

        if ($row === []) {
            return $snapshot;
        }

        return [
            'current_total_xof' => (int) ($row['current_total_xof'] ?? 0),
            'previous_total_xof' => (int) ($row['previous_total_xof'] ?? 0),
            'current_orders' => (int) ($row['current_orders'] ?? 0),
            'previous_orders' => (int) ($row['previous_orders'] ?? 0),
        ];
    }

    /**
     * @return array<string,int>
     */
    public function fetchUserGrowthSnapshotForRange(
        string $fromInclusive,
        string $toExclusive,
        string $previousFromInclusive,
        string $previousToExclusive
    ): array {
        $snapshot = [
            'current_users' => 0,
            'previous_users' => 0,
            'current_teachers' => 0,
            'current_students' => 0,
        ];

        if (!$this->tableExistsSafe('users')) {
            return $snapshot;
        }

        $row = $this->rowQuery(
            'SELECT
                COALESCE(SUM(CASE
                    WHEN status IN ("active", "pending_email")
                        AND created_at >= ' . $this->quote($fromInclusive) . '
                        AND created_at < ' . $this->quote($toExclusive) . '
                        THEN 1
                    ELSE 0
                END), 0) AS current_users,
                COALESCE(SUM(CASE
                    WHEN status IN ("active", "pending_email")
                        AND created_at >= ' . $this->quote($previousFromInclusive) . '
                        AND created_at < ' . $this->quote($previousToExclusive) . '
                        THEN 1
                    ELSE 0
                END), 0) AS previous_users,
                COALESCE(SUM(CASE
                    WHEN status IN ("active", "pending_email")
                        AND created_at >= ' . $this->quote($fromInclusive) . '
                        AND created_at < ' . $this->quote($toExclusive) . '
                        AND roles_json LIKE ' . $this->quote('%"teacher"%') . '
                        THEN 1
                    ELSE 0
                END), 0) AS current_teachers,
                COALESCE(SUM(CASE
                    WHEN status IN ("active", "pending_email")
                        AND created_at >= ' . $this->quote($fromInclusive) . '
                        AND created_at < ' . $this->quote($toExclusive) . '
                        AND roles_json LIKE ' . $this->quote('%"student"%') . '
                        THEN 1
                    ELSE 0
                END), 0) AS current_students
             FROM users'
        );

        if ($row === []) {
            return $snapshot;
        }

        return [
            'current_users' => (int) ($row['current_users'] ?? 0),
            'previous_users' => (int) ($row['previous_users'] ?? 0),
            'current_teachers' => (int) ($row['current_teachers'] ?? 0),
            'current_students' => (int) ($row['current_students'] ?? 0),
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchPaidRevenueSeriesForRange(
        string $fromInclusive,
        string $toExclusive,
        string $granularity = 'month'
    ): array {
        if (!$this->tableExistsSafe('orders')) {
            return [];
        }

        $bucketExpr = 'DATE_FORMAT(created_at, "%Y-%m")';
        if (strtolower(trim($granularity)) === 'day') {
            $bucketExpr = 'DATE(created_at)';
        }

        $paidStatuses = $this->paidOrderStatusSql();

        return $this->rowsQuery(
            'SELECT
                ' . $bucketExpr . ' AS bucket_key,
                COALESCE(SUM(total_xof), 0) AS total_xof,
                COUNT(*) AS orders_count
             FROM orders
             WHERE ' . $paidStatuses . '
               AND created_at >= ' . $this->quote($fromInclusive) . '
               AND created_at < ' . $this->quote($toExclusive) . '
             GROUP BY ' . $bucketExpr . '
             ORDER BY bucket_key ASC'
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchMetricSeriesForRange(
        string $metric,
        string $granularity,
        string $fromInclusive,
        string $toExclusive
    ): array {
        $config = $this->graphMetricConfig($metric);
        if ($config === null) {
            return [];
        }

        if (
            !$this->tableExistsSafe($config['table'])
            || !$this->columnExistsSafe($config['table'], 'created_at')
        ) {
            return [];
        }

        $bucketExpr = $this->graphBucketExpr($granularity);
        if ($bucketExpr === '') {
            return [];
        }

        return $this->rowsQuery(
            'SELECT
                ' . $bucketExpr . ' AS bucket_key,
                ' . $config['value_expr'] . ' AS metric_value
             FROM ' . $config['table'] . '
             WHERE ' . $config['where_sql'] . '
               AND created_at >= ' . $this->quote($fromInclusive) . '
               AND created_at < ' . $this->quote($toExclusive) . '
             GROUP BY ' . $bucketExpr . '
             ORDER BY bucket_key ASC'
        );
    }

    /**
     * @return array<int,int>
     */
    public function fetchAvailableYears(int $limit = 8): array
    {
        $safeLimit = max(1, min(12, $limit));
        $sources = [];

        foreach (['orders', 'users', 'support_conversations', 'payments', 'courses', 'companies'] as $table) {
            if (!$this->tableExistsSafe($table) || !$this->columnExistsSafe($table, 'created_at')) {
                continue;
            }
            $sources[] = 'SELECT YEAR(created_at) AS year_key FROM ' . $table . ' WHERE created_at IS NOT NULL';
        }

        if ($sources === []) {
            return [(int) date('Y')];
        }

        $rows = $this->rowsQuery(
            'SELECT year_key
             FROM (' . implode(' UNION ALL ', $sources) . ') AS year_pool
             WHERE year_key IS NOT NULL
             GROUP BY year_key
             ORDER BY year_key DESC
             LIMIT ' . $safeLimit
        );

        $years = [];
        foreach ($rows as $row) {
            $year = (int) ($row['year_key'] ?? 0);
            if ($year > 0) {
                $years[] = $year;
            }
        }

        if ($years === []) {
            $years[] = (int) date('Y');
        }

        return array_values(array_unique($years));
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchMonthlyRevenueSeries(int $months = 6): array
    {
        if (!$this->tableExistsSafe('orders')) {
            return [];
        }

        $safeMonths = max(3, min(12, $months));
        $lookbackMonths = $safeMonths - 1;
        $paidStatuses = $this->paidOrderStatusSql();

        return $this->rowsQuery(
            'SELECT
                DATE_FORMAT(created_at, "%Y-%m") AS ym,
                COALESCE(SUM(total_xof), 0) AS total_xof,
                COUNT(*) AS orders_count
             FROM orders
             WHERE ' . $paidStatuses . '
               AND created_at >= DATE_SUB(DATE_FORMAT(CURDATE(), "%Y-%m-01"), INTERVAL ' . $lookbackMonths . ' MONTH)
             GROUP BY DATE_FORMAT(created_at, "%Y-%m")
             ORDER BY ym ASC'
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchDailyUserCounts(int $days = 7): array
    {
        if (!$this->tableExistsSafe('users')) {
            return [];
        }

        $safeDays = max(3, min(31, $days));

        return $this->rowsQuery(
            'SELECT
                DATE(created_at) AS day_key,
                COUNT(*) AS total_count
             FROM users
             WHERE status IN ("active", "pending_email")
               AND created_at >= DATE_SUB(CURDATE(), INTERVAL ' . ($safeDays - 1) . ' DAY)
             GROUP BY DATE(created_at)
             ORDER BY day_key ASC'
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchDailyPaidOrderCounts(int $days = 7): array
    {
        if (!$this->tableExistsSafe('orders')) {
            return [];
        }

        $safeDays = max(3, min(31, $days));
        $paidStatuses = $this->paidOrderStatusSql();

        return $this->rowsQuery(
            'SELECT
                DATE(created_at) AS day_key,
                COUNT(*) AS total_count
             FROM orders
             WHERE ' . $paidStatuses . '
               AND created_at >= DATE_SUB(CURDATE(), INTERVAL ' . ($safeDays - 1) . ' DAY)
             GROUP BY DATE(created_at)
             ORDER BY day_key ASC'
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchDailySupportCounts(int $days = 7): array
    {
        if (!$this->tableExistsSafe('support_conversations')) {
            return [];
        }

        $safeDays = max(3, min(31, $days));

        return $this->rowsQuery(
            'SELECT
                DATE(created_at) AS day_key,
                COUNT(*) AS total_count
             FROM support_conversations
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ' . ($safeDays - 1) . ' DAY)
             GROUP BY DATE(created_at)
             ORDER BY day_key ASC'
        );
    }

    /**
     * @return array<string,int>
     */
    public function fetchCourseStatusBreakdown(): array
    {
        $defaults = [
            'approved' => 0,
            'submitted' => 0,
            'draft' => 0,
            'rejected' => 0,
        ];

        if (!$this->tableExistsSafe('courses')) {
            return $defaults;
        }

        $rows = $this->rowsQuery(
            'SELECT status, COUNT(*) AS total_count
             FROM courses
             WHERE deleted_at IS NULL
             GROUP BY status'
        );

        foreach ($rows as $row) {
            $status = strtolower(trim((string) ($row['status'] ?? '')));
            if (array_key_exists($status, $defaults)) {
                $defaults[$status] = (int) ($row['total_count'] ?? 0);
            }
        }

        return $defaults;
    }

    /**
     * @return array<string,int>
     */
    public function fetchSupportStatusBreakdown(): array
    {
        $defaults = [
            'open' => 0,
            'waiting_support' => 0,
            'waiting_user' => 0,
            'closed' => 0,
        ];

        if (!$this->tableExistsSafe('support_conversations')) {
            return $defaults;
        }

        $rows = $this->rowsQuery(
            'SELECT status, COUNT(*) AS total_count
             FROM support_conversations
             GROUP BY status'
        );

        foreach ($rows as $row) {
            $status = strtolower(trim((string) ($row['status'] ?? '')));
            if (array_key_exists($status, $defaults)) {
                $defaults[$status] = (int) ($row['total_count'] ?? 0);
            }
        }

        return $defaults;
    }

    /**
     * @return array<string,int>
     */
    public function fetchPaymentStatusBreakdown(): array
    {
        $defaults = [
            'paid' => 0,
            'pending' => 0,
            'initiated' => 0,
            'failed' => 0,
            'refunded' => 0,
        ];

        if (!$this->tableExistsSafe('payments')) {
            return $defaults;
        }

        $rows = $this->rowsQuery(
            'SELECT status, COUNT(*) AS total_count
             FROM payments
             GROUP BY status'
        );

        foreach ($rows as $row) {
            $status = strtolower(trim((string) ($row['status'] ?? '')));
            if (array_key_exists($status, $defaults)) {
                $defaults[$status] = (int) ($row['total_count'] ?? 0);
            }
        }

        return $defaults;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchCourseCategoryBreakdown(int $limit = 6): array
    {
        if (!$this->tableExistsSafe('courses')) {
            return [];
        }

        $safeLimit = max(1, min(12, $limit));
        $categoryJoin = '';
        $categorySelect = '"Sans catégorie" AS category_name';
        $categoryGroup = '"Sans catégorie"';
        if ($this->tableExistsSafe('categories')) {
            $categoryJoin = 'LEFT JOIN categories cat ON cat.id = c.category_id';
            $categorySelect = 'COALESCE(NULLIF(cat.name, ""), "Sans catégorie") AS category_name';
            $categoryGroup = 'COALESCE(NULLIF(cat.name, ""), "Sans catégorie")';
        }

        return $this->rowsQuery(
            'SELECT
                ' . $categorySelect . ',
                COUNT(*) AS total_courses,
                COALESCE(SUM(CASE WHEN c.status = "approved" THEN 1 ELSE 0 END), 0) AS approved_courses
             FROM courses c
             ' . $categoryJoin . '
             WHERE c.deleted_at IS NULL
             GROUP BY ' . $categoryGroup . '
             ORDER BY total_courses DESC, category_name ASC
             LIMIT ' . $safeLimit
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchTopTeachersByRevenue(int $limit = 5): array
    {
        if (!$this->tableExistsSafe('courses') || !$this->tableExistsSafe('users')) {
            return [];
        }

        $safeLimit = max(1, min(10, $limit));
        $salesJoin = '';
        $revenueSelect = '0 AS revenue_xof, 0 AS paid_orders';
        if (
            $this->tableExistsSafe('orders')
            && $this->tableExistsSafe('order_items')
            && $this->columnExistsSafe('order_items', 'quantity')
            && $this->columnExistsSafe('order_items', 'unit_price_xof')
        ) {
            $salesJoin = '
                LEFT JOIN order_items oi ON oi.course_id = c.id
                LEFT JOIN orders o ON o.id = oi.order_id AND ' . str_replace('status', 'o.status', $this->paidOrderStatusSql()) . '
            ';
            $revenueSelect = '
                COALESCE(SUM(CASE WHEN o.id IS NOT NULL THEN oi.quantity * oi.unit_price_xof ELSE 0 END), 0) AS revenue_xof,
                COUNT(DISTINCT CASE WHEN o.id IS NOT NULL THEN o.id ELSE NULL END) AS paid_orders
            ';
        }

        return $this->rowsQuery(
            'SELECT
                u.id AS teacher_id,
                CONCAT_WS(" ", u.first_name, u.last_name) AS teacher_name,
                u.email AS teacher_email,
                COUNT(DISTINCT c.id) AS courses_count,
                ' . $revenueSelect . '
             FROM courses c
             JOIN users u ON u.id = c.teacher_id
             ' . $salesJoin . '
             WHERE c.deleted_at IS NULL
             GROUP BY u.id, u.first_name, u.last_name, u.email
             ORDER BY revenue_xof DESC, courses_count DESC, teacher_name ASC
             LIMIT ' . $safeLimit
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchRecentTransactions(int $limit = 6): array
    {
        if (!$this->tableExistsSafe('orders') || !$this->tableExistsSafe('users')) {
            return [];
        }

        $safeLimit = max(1, min(20, $limit));
        $itemsJoin = '';
        $courseSelect = '"" AS course_titles, 0 AS course_count';
        if ($this->tableExistsSafe('order_items') && $this->tableExistsSafe('courses')) {
            $itemsJoin = '
                LEFT JOIN order_items oi ON oi.order_id = o.id
                LEFT JOIN courses c ON c.id = oi.course_id
            ';
            $courseSelect = '
                COALESCE(GROUP_CONCAT(DISTINCT c.title ORDER BY c.title SEPARATOR ", "), "") AS course_titles,
                COUNT(DISTINCT c.id) AS course_count
            ';
        }

        return $this->rowsQuery(
            'SELECT
                o.id,
                o.reference,
                o.total_xof,
                o.status,
                o.provider,
                o.created_at,
                CONCAT_WS(" ", u.first_name, u.last_name) AS customer_name,
                u.email AS customer_email,
                ' . $courseSelect . '
             FROM orders o
             JOIN users u ON u.id = o.user_id
             ' . $itemsJoin . '
             GROUP BY o.id, o.reference, o.total_xof, o.status, o.provider, o.created_at, u.first_name, u.last_name, u.email
             ORDER BY o.created_at DESC, o.id DESC
             LIMIT ' . $safeLimit
        );
    }

    /**
     * @return array<string,int>
     */
    public function fetchConversionFunnel(): array
    {
        $registered = $this->tableExistsSafe('users')
            ? $this->intQuery('SELECT COUNT(*) FROM users WHERE status IN ("active", "pending_email", "suspended")')
            : 0;

        $visitors = 0;
        if ($this->tableExistsSafe('course_views') && $this->columnExistsSafe('course_views', 'viewer_key')) {
            $visitors = $this->intQuery('SELECT COUNT(DISTINCT viewer_key) FROM course_views');
        }
        if ($visitors <= 0) {
            $visitors = $registered;
        }

        $paidOrders = $this->tableExistsSafe('orders')
            ? $this->intQuery('SELECT COUNT(*) FROM orders WHERE ' . $this->paidOrderStatusSql())
            : 0;

        $activeEnrollments = $this->countActiveEnrollments();

        return [
            'visitors' => max($visitors, $registered, $paidOrders, $activeEnrollments),
            'registrations' => $registered,
            'paid_orders' => $paidOrders,
            'active_enrollments' => $activeEnrollments,
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchCompletionRowsByCategory(): array
    {
        if (
            !$this->tableExistsSafe('courses')
            || !$this->tableExistsSafe('enrollments')
            || !$this->tableExistsSafe('course_chapters')
            || !$this->tableExistsSafe('course_lessons')
            || !$this->tableExistsSafe('lesson_completions')
        ) {
            return [];
        }

        $categoryJoin = '';
        $categorySelect = '"Sans catégorie" AS category_name';
        if ($this->tableExistsSafe('categories')) {
            $categoryJoin = 'LEFT JOIN categories cat ON cat.id = c.category_id';
            $categorySelect = 'COALESCE(NULLIF(cat.name, ""), "Sans catégorie") AS category_name';
        }

        return $this->rowsQuery(
            'SELECT
                c.id AS course_id,
                ' . $categorySelect . ',
                COALESCE(lessons.lesson_count, 0) AS lesson_count,
                COALESCE(students.student_count, 0) AS student_count,
                COALESCE(done.done_count, 0) AS done_count
             FROM courses c
             ' . $categoryJoin . '
             LEFT JOIN (
                SELECT cc.course_id, COUNT(cl.id) AS lesson_count
                FROM course_chapters cc
                JOIN course_lessons cl ON cl.chapter_id = cc.id
                GROUP BY cc.course_id
             ) lessons ON lessons.course_id = c.id
             LEFT JOIN (
                SELECT course_id, COUNT(DISTINCT user_id) AS student_count
                FROM enrollments
                WHERE status = "active"
                GROUP BY course_id
             ) students ON students.course_id = c.id
             LEFT JOIN (
                SELECT course_id, COUNT(*) AS done_count
                FROM lesson_completions
                GROUP BY course_id
             ) done ON done.course_id = c.id
             WHERE c.deleted_at IS NULL
               AND c.status = "approved"
             ORDER BY category_name ASC, c.title ASC'
        );
    }

    /**
     * @return array<string,int>
     */
    public function fetchAnnouncementSnapshot(int $days = 30): array
    {
        $snapshot = [
            'total_announcements' => 0,
            'published_recent' => 0,
            'urgent_recent' => 0,
            'sent_recent' => 0,
            'read_recent' => 0,
        ];

        if (!$this->tableExistsSafe('admin_announcements')) {
            return $snapshot;
        }

        $safeDays = max(1, min(365, $days));
        $hasStatus = $this->columnExistsSafe('admin_announcements', 'status');
        $hasPublishedAt = $this->columnExistsSafe('admin_announcements', 'published_at');
        $hasImportance = $this->columnExistsSafe('admin_announcements', 'importance');
        $hasSentCount = $this->columnExistsSafe('admin_announcements', 'sent_count');
        $hasSentCountB = $this->columnExistsSafe('admin_announcements', 'sent_count_b');
        $hasReadCount = $this->columnExistsSafe('admin_announcements', 'read_count');
        $hasReadCountB = $this->columnExistsSafe('admin_announcements', 'read_count_b');

        $recentColumn = $hasPublishedAt ? 'published_at' : 'created_at';
        $publishedCondition = $hasStatus ? '(status = "published")' : '1=1';
        $urgentCondition = $hasImportance ? '(importance = "urgent")' : '0=1';

        $sentExpr = $hasSentCount ? 'COALESCE(sent_count, 0)' : '0';
        if ($hasSentCountB) {
            $sentExpr = '(' . $sentExpr . ' + COALESCE(sent_count_b, 0))';
        }

        $readExpr = $hasReadCount ? 'COALESCE(read_count, 0)' : '0';
        if ($hasReadCountB) {
            $readExpr = '(' . $readExpr . ' + COALESCE(read_count_b, 0))';
        }

        $recentCondition = $recentColumn . ' >= DATE_SUB(NOW(), INTERVAL ' . $safeDays . ' DAY)';

        $row = $this->rowQuery(
            'SELECT
                COUNT(*) AS total_announcements,
                COALESCE(SUM(CASE WHEN ' . $publishedCondition . ' AND ' . $recentCondition . ' THEN 1 ELSE 0 END), 0) AS published_recent,
                COALESCE(SUM(CASE WHEN ' . $publishedCondition . ' AND ' . $urgentCondition . ' AND ' . $recentCondition . ' THEN 1 ELSE 0 END), 0) AS urgent_recent,
                COALESCE(SUM(CASE WHEN ' . $publishedCondition . ' AND ' . $recentCondition . ' THEN ' . $sentExpr . ' ELSE 0 END), 0) AS sent_recent,
                COALESCE(SUM(CASE WHEN ' . $publishedCondition . ' AND ' . $recentCondition . ' THEN ' . $readExpr . ' ELSE 0 END), 0) AS read_recent
             FROM admin_announcements'
        );

        if ($row === []) {
            return $snapshot;
        }

        return [
            'total_announcements' => (int) ($row['total_announcements'] ?? 0),
            'published_recent' => (int) ($row['published_recent'] ?? 0),
            'urgent_recent' => (int) ($row['urgent_recent'] ?? 0),
            'sent_recent' => (int) ($row['sent_recent'] ?? 0),
            'read_recent' => (int) ($row['read_recent'] ?? 0),
        ];
    }

    /**
     * @return array<string,int>
     */
    public function fetchNotificationReadSnapshot(int $days = 30): array
    {
        $snapshot = [
            'sent_recent' => 0,
            'read_recent' => 0,
            'urgent_recent' => 0,
        ];

        if (!$this->tableExistsSafe('notifications')) {
            return $snapshot;
        }

        $safeDays = max(1, min(365, $days));
        $hasReadAt = $this->columnExistsSafe('notifications', 'read_at');
        $hasImportance = $this->columnExistsSafe('notifications', 'importance');
        $readCondition = $hasReadAt ? '(is_read = 1 OR read_at IS NOT NULL)' : '(is_read = 1)';
        $urgentCondition = $hasImportance ? '(importance = "urgent")' : '0=1';

        $row = $this->rowQuery(
            'SELECT
                COUNT(*) AS sent_recent,
                COALESCE(SUM(CASE WHEN ' . $readCondition . ' THEN 1 ELSE 0 END), 0) AS read_recent,
                COALESCE(SUM(CASE WHEN ' . $urgentCondition . ' THEN 1 ELSE 0 END), 0) AS urgent_recent
             FROM notifications
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL ' . $safeDays . ' DAY)'
        );

        if ($row === []) {
            return $snapshot;
        }

        return [
            'sent_recent' => (int) ($row['sent_recent'] ?? 0),
            'read_recent' => (int) ($row['read_recent'] ?? 0),
            'urgent_recent' => (int) ($row['urgent_recent'] ?? 0),
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchAuditModuleCounts(int $days = 30, int $limit = 6): array
    {
        if (!$this->tableExistsSafe('audit_logs')) {
            return [];
        }

        $safeDays = max(1, min(180, $days));
        $safeLimit = max(1, min(12, $limit));

        return $this->rowsQuery(
            'SELECT
                target_type,
                COUNT(*) AS total_count
             FROM audit_logs
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL ' . $safeDays . ' DAY)
               AND target_type <> ""
             GROUP BY target_type
             ORDER BY total_count DESC, target_type ASC
             LIMIT ' . $safeLimit
        );
    }

    private function paidOrderStatusSql(): string
    {
        return 'LOWER(status) IN ("paid", "completed", "succeeded", "success", "free")';
    }

    /**
     * @return array<string,string>|null
     */
    private function graphMetricConfig(string $metric): ?array
    {
        $key = strtolower(trim($metric));
        $paidStatuses = $this->paidOrderStatusSql();

        $config = [
            'revenue_xof' => [
                'table' => 'orders',
                'value_expr' => 'COALESCE(SUM(total_xof), 0)',
                'where_sql' => $paidStatuses,
            ],
            'paid_orders' => [
                'table' => 'orders',
                'value_expr' => 'COUNT(*)',
                'where_sql' => $paidStatuses,
            ],
            'new_accounts' => [
                'table' => 'users',
                'value_expr' => 'COUNT(*)',
                'where_sql' => 'status IN ("active", "pending_email")',
            ],
            'new_teachers' => [
                'table' => 'users',
                'value_expr' => 'COUNT(*)',
                'where_sql' => 'status IN ("active", "pending_email") AND roles_json LIKE ' . $this->quote('%"teacher"%'),
            ],
            'new_students' => [
                'table' => 'users',
                'value_expr' => 'COUNT(*)',
                'where_sql' => 'status IN ("active", "pending_email") AND roles_json LIKE ' . $this->quote('%"student"%'),
            ],
            'new_companies' => [
                'table' => 'companies',
                'value_expr' => 'COUNT(*)',
                'where_sql' => 'status = "active"',
            ],
        ];

        return $config[$key] ?? null;
    }

    private function graphBucketExpr(string $granularity): string
    {
        $key = strtolower(trim($granularity));

        if ($key === 'day') {
            return 'DATE_FORMAT(DATE(created_at), "%Y-%m-%d")';
        }

        if ($key === 'week') {
            return 'DATE_FORMAT(DATE_SUB(DATE(created_at), INTERVAL WEEKDAY(created_at) DAY), "%Y-%m-%d")';
        }

        if ($key === 'month') {
            return 'DATE_FORMAT(created_at, "%Y-%m")';
        }

        if ($key === 'year') {
            return 'DATE_FORMAT(created_at, "%Y")';
        }

        return '';
    }

    private function tableExistsSafe(string $table): bool
    {
        try {
            return $this->tableExists($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function columnExistsSafe(string $table, string $column): bool
    {
        try {
            return $this->columnExists($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function quote(string $value): string
    {
        $quoted = $this->pdo->quote($value);
        return $quoted === false ? "''" : $quoted;
    }

    private function intQuery(string $sql): int
    {
        try {
            $stmt = $this->pdo->query($sql);
            return $stmt ? (int) $stmt->fetchColumn() : 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function rowQuery(string $sql): array
    {
        try {
            $stmt = $this->pdo->query($sql);
            $row = $stmt ? $stmt->fetch() : false;
            return is_array($row) ? $row : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function rowsQuery(string $sql): array
    {
        try {
            $stmt = $this->pdo->query($sql);
            return $stmt ? ($stmt->fetchAll() ?: []) : [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}
