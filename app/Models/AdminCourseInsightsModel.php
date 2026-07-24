<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class AdminCourseInsightsModel extends Model
{
    private ?bool $hasOrdersTable = null;
    private ?bool $hasOrderItemsTable = null;
    private ?bool $hasEnrollmentsTable = null;
    private ?bool $hasPromoScopeColumn = null;
    private ?bool $hasCompanyIdColumn = null;

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchTeacherFilters(): array
    {
        $enrollmentJoin = '';
        $enrollmentSelect = '0 AS enrolled_students_count';
        if ($this->hasEnrollmentsTable()) {
            $enrollmentJoin = '
                LEFT JOIN (
                    SELECT e.course_id, COUNT(*) AS enrolled_count
                    FROM enrollments e
                    WHERE e.status = "active"
                    GROUP BY e.course_id
                ) enr ON enr.course_id = c.id
            ';
            $enrollmentSelect = 'COALESCE(SUM(enr.enrolled_count), 0) AS enrolled_students_count';
        }

        $salesJoin = '';
        $salesSelect = '0 AS total_generated_xof';
        if ($this->salesSchemaReady()) {
            $salesJoin = '
                LEFT JOIN (
                    ' . $this->salesAggregateSubquery() . '
                ) sales ON sales.course_id = c.id
            ';
            $salesSelect = 'COALESCE(SUM(sales.total_generated_xof), 0) AS total_generated_xof';
        }

        $sql = 'SELECT
                    u.id AS teacher_id,
                    u.first_name AS teacher_first_name,
                    u.last_name AS teacher_last_name,
                    COUNT(DISTINCT c.id) AS approved_course_count,
                    ' . $enrollmentSelect . ',
                    ' . $salesSelect . '
                FROM courses c
                JOIN users u ON u.id = c.teacher_id
                ' . $enrollmentJoin . '
                ' . $salesJoin . '
                WHERE c.status = "approved"
                  AND c.deleted_at IS NULL
                GROUP BY u.id, u.first_name, u.last_name
                ORDER BY total_generated_xof DESC, approved_course_count DESC, u.last_name ASC, u.first_name ASC';

        return $this->rowsQuery($sql);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findTeacherById(int $teacherId): ?array
    {
        $teacherId = max(0, $teacherId);
        if ($teacherId < 1) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT
                u.id,
                u.first_name,
                u.last_name,
                u.email,
                u.status,
                u.created_at,
                u.updated_at
             FROM users u
             WHERE u.id = :id
               AND u.roles_json LIKE :teacher_role
               AND u.status IN ("active", "suspended")
             LIMIT 1'
        );
        $stmt->execute([
            ':id' => $teacherId,
            ':teacher_role' => '%"teacher"%',
        ]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchApprovedCourseRows(int $teacherId = 0, string $search = ''): array
    {
        $teacherId = max(0, $teacherId);
        $search = trim($search);

        $params = [];
        $filters = [
            'c.status = "approved"',
            'c.deleted_at IS NULL',
        ];

        if ($teacherId > 0) {
            $filters[] = 'c.teacher_id = :teacher_id';
            $params[':teacher_id'] = $teacherId;
        }

        if ($search !== '') {
            $filters[] = '(c.title LIKE :search OR c.slug LIKE :search OR CONCAT_WS(" ", u.first_name, u.last_name) LIKE :search)';
            $params[':search'] = '%' . $this->escapeLike($search) . '%';
        }

        $enrollmentJoin = '';
        $enrollmentSelect = '0 AS enrolled_count';
        if ($this->hasEnrollmentsTable()) {
            $enrollmentJoin = '
                LEFT JOIN (
                    SELECT e.course_id, COUNT(*) AS enrolled_count
                    FROM enrollments e
                    WHERE e.status = "active"
                    GROUP BY e.course_id
                ) enr ON enr.course_id = c.id
            ';
            $enrollmentSelect = 'COALESCE(enr.enrolled_count, 0) AS enrolled_count';
        }

        $salesJoin = '';
        $salesSelect = '
            0 AS paid_units,
            0 AS total_generated_xof,
            0 AS direct_units,
            0 AS direct_generated_xof,
            0 AS promo_units,
            0 AS promo_generated_xof,
            0 AS enterprise_units,
            0 AS enterprise_generated_xof
        ';
        if ($this->salesSchemaReady()) {
            $salesJoin = '
                LEFT JOIN (
                    ' . $this->salesAggregateSubquery() . '
                ) sales ON sales.course_id = c.id
            ';
            $salesSelect = '
                COALESCE(sales.paid_units, 0) AS paid_units,
                COALESCE(sales.total_generated_xof, 0) AS total_generated_xof,
                COALESCE(sales.direct_units, 0) AS direct_units,
                COALESCE(sales.direct_generated_xof, 0) AS direct_generated_xof,
                COALESCE(sales.promo_units, 0) AS promo_units,
                COALESCE(sales.promo_generated_xof, 0) AS promo_generated_xof,
                COALESCE(sales.enterprise_units, 0) AS enterprise_units,
                COALESCE(sales.enterprise_generated_xof, 0) AS enterprise_generated_xof
            ';
        }

        $sql = 'SELECT
                    c.id,
                    c.slug,
                    c.title,
                    c.created_at,
                    c.updated_at,
                    c.teacher_id,
                    u.first_name AS teacher_first_name,
                    u.last_name AS teacher_last_name,
                    ' . $enrollmentSelect . ',
                    ' . $salesSelect . '
                FROM courses c
                JOIN users u ON u.id = c.teacher_id
                ' . $enrollmentJoin . '
                ' . $salesJoin . '
                WHERE ' . implode(' AND ', $filters) . '
                ORDER BY total_generated_xof DESC, enrolled_count DESC, c.created_at DESC';

        return $this->rowsQuery($sql, $params);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findApprovedCourseById(int $courseId): ?array
    {
        $courseId = max(0, $courseId);
        if ($courseId < 1) {
            return null;
        }

        $enrollmentJoin = '';
        $enrollmentSelect = '0 AS enrolled_count';
        if ($this->hasEnrollmentsTable()) {
            $enrollmentJoin = '
                LEFT JOIN (
                    SELECT e.course_id, COUNT(*) AS enrolled_count
                    FROM enrollments e
                    WHERE e.status = "active"
                    GROUP BY e.course_id
                ) enr ON enr.course_id = c.id
            ';
            $enrollmentSelect = 'COALESCE(enr.enrolled_count, 0) AS enrolled_count';
        }

        $salesJoin = '';
        $salesSelect = '
            0 AS paid_units,
            0 AS total_generated_xof,
            0 AS direct_units,
            0 AS direct_generated_xof,
            0 AS promo_units,
            0 AS promo_generated_xof,
            0 AS enterprise_units,
            0 AS enterprise_generated_xof
        ';
        if ($this->salesSchemaReady()) {
            $salesJoin = '
                LEFT JOIN (
                    ' . $this->salesAggregateSubquery() . '
                ) sales ON sales.course_id = c.id
            ';
            $salesSelect = '
                COALESCE(sales.paid_units, 0) AS paid_units,
                COALESCE(sales.total_generated_xof, 0) AS total_generated_xof,
                COALESCE(sales.direct_units, 0) AS direct_units,
                COALESCE(sales.direct_generated_xof, 0) AS direct_generated_xof,
                COALESCE(sales.promo_units, 0) AS promo_units,
                COALESCE(sales.promo_generated_xof, 0) AS promo_generated_xof,
                COALESCE(sales.enterprise_units, 0) AS enterprise_units,
                COALESCE(sales.enterprise_generated_xof, 0) AS enterprise_generated_xof
            ';
        }

        $rows = $this->rowsQuery(
            'SELECT
                c.id,
                c.slug,
                c.title,
                c.description,
                c.cover_path,
                c.duration_hours,
                c.level,
                c.price_xof,
                c.created_at,
                c.updated_at,
                c.teacher_id,
                COALESCE(cat.name, "") AS category_name,
                u.first_name AS teacher_first_name,
                u.last_name AS teacher_last_name,
                ' . $enrollmentSelect . ',
                ' . $salesSelect . '
             FROM courses c
             JOIN users u ON u.id = c.teacher_id
             LEFT JOIN categories cat ON cat.id = c.category_id
             ' . $enrollmentJoin . '
             ' . $salesJoin . '
             WHERE c.id = :course_id
               AND c.status = "approved"
               AND c.deleted_at IS NULL
             LIMIT 1',
            [':course_id' => $courseId]
        );

        return is_array($rows[0] ?? null) ? $rows[0] : null;
    }

    private function salesAggregateSubquery(): string
    {
        $scopeExpr = $this->hasPromoScopeColumn()
            ? 'LOWER(COALESCE(oi.promo_scope, "none"))'
            : '"none"';

        $isEnterprise = match (true) {
            $this->hasCompanyIdColumn() && $this->hasPromoScopeColumn() => '(COALESCE(oi.company_id, 0) > 0 OR ' . $scopeExpr . ' = "enterprise")',
            $this->hasCompanyIdColumn() => '(COALESCE(oi.company_id, 0) > 0)',
            $this->hasPromoScopeColumn() => '(' . $scopeExpr . ' = "enterprise")',
            default => '0',
        };

        $isPromo = $this->hasPromoScopeColumn()
            ? '(' . $scopeExpr . ' IN ("coupon", "promotion", "targeted", "company"))'
            : '0';

        $isDirect = $this->hasPromoScopeColumn()
            ? '(' . $scopeExpr . ' = "none" OR ' . $scopeExpr . ' = "" OR ' . $scopeExpr . ' NOT IN ("coupon", "promotion", "targeted", "company", "enterprise"))'
            : '1';

        $isNonEnterprise = 'NOT (' . $isEnterprise . ')';

        return 'SELECT
                    oi.course_id,
                    COALESCE(SUM(oi.quantity), 0) AS paid_units,
                    COALESCE(SUM(oi.quantity * oi.unit_price_xof), 0) AS total_generated_xof,
                    COALESCE(SUM(CASE WHEN ' . $isNonEnterprise . ' AND ' . $isDirect . ' THEN oi.quantity ELSE 0 END), 0) AS direct_units,
                    COALESCE(SUM(CASE WHEN ' . $isNonEnterprise . ' AND ' . $isDirect . ' THEN oi.quantity * oi.unit_price_xof ELSE 0 END), 0) AS direct_generated_xof,
                    COALESCE(SUM(CASE WHEN ' . $isNonEnterprise . ' AND ' . $isPromo . ' THEN oi.quantity ELSE 0 END), 0) AS promo_units,
                    COALESCE(SUM(CASE WHEN ' . $isNonEnterprise . ' AND ' . $isPromo . ' THEN oi.quantity * oi.unit_price_xof ELSE 0 END), 0) AS promo_generated_xof,
                    COALESCE(SUM(CASE WHEN ' . $isEnterprise . ' THEN oi.quantity ELSE 0 END), 0) AS enterprise_units,
                    COALESCE(SUM(CASE WHEN ' . $isEnterprise . ' THEN oi.quantity * oi.unit_price_xof ELSE 0 END), 0) AS enterprise_generated_xof
                FROM order_items oi
                JOIN orders o ON o.id = oi.order_id
                WHERE LOWER(o.status) IN ("paid", "completed", "succeeded", "success", "free")
                GROUP BY oi.course_id';
    }

    private function salesSchemaReady(): bool
    {
        return $this->hasOrdersTable() && $this->hasOrderItemsTable();
    }

    private function hasOrdersTable(): bool
    {
        if ($this->hasOrdersTable === null) {
            $this->hasOrdersTable = $this->tableExistsSafe('orders');
        }
        return $this->hasOrdersTable;
    }

    private function hasOrderItemsTable(): bool
    {
        if ($this->hasOrderItemsTable === null) {
            $this->hasOrderItemsTable = $this->tableExistsSafe('order_items');
        }
        return $this->hasOrderItemsTable;
    }

    private function hasEnrollmentsTable(): bool
    {
        if ($this->hasEnrollmentsTable === null) {
            $this->hasEnrollmentsTable = $this->tableExistsSafe('enrollments');
        }
        return $this->hasEnrollmentsTable;
    }

    private function hasPromoScopeColumn(): bool
    {
        if ($this->hasPromoScopeColumn === null) {
            $this->hasPromoScopeColumn = $this->salesSchemaReady() && $this->columnExistsSafe('order_items', 'promo_scope');
        }
        return $this->hasPromoScopeColumn;
    }

    private function hasCompanyIdColumn(): bool
    {
        if ($this->hasCompanyIdColumn === null) {
            $this->hasCompanyIdColumn = $this->salesSchemaReady() && $this->columnExistsSafe('order_items', 'company_id');
        }
        return $this->hasCompanyIdColumn;
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

    private function escapeLike(string $value): string
    {
        return str_replace(
            ['\\', '%', '_'],
            ['\\\\', '\\%', '\\_'],
            $value
        );
    }
}
