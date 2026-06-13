<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class AdminTeacherApplicationsModel extends Model
{
    public function findApplicationWithUserRoles(int $applicationId): ?array
    {
        [$teacherJoin, $teacherSelect] = $this->teacherProfileJoinAndSelect('ta.user_id');
        $stmt = $this->pdo->prepare(
            'SELECT ta.*, u.roles_json' . $teacherSelect . '
             FROM teacher_applications ta
             JOIN users u ON u.id = ta.user_id
             ' . $teacherJoin . '
             WHERE ta.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $applicationId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function approveApplication(int $applicationId, int $adminId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE teacher_applications
             SET status = :status, reason = NULL, decided_by = :admin_id, decided_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            ':status' => 'approved',
            ':admin_id' => $adminId,
            ':id' => $applicationId,
        ]);
    }

    public function rejectApplication(int $applicationId, string $reason, int $adminId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE teacher_applications
             SET status = :status, reason = :reason, decided_by = :admin_id, decided_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            ':status' => 'rejected',
            ':reason' => $reason,
            ':admin_id' => $adminId,
            ':id' => $applicationId,
        ]);
    }

    public function updateUserRoles(int $userId, string $rolesJson): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET roles_json = :roles WHERE id = :uid');
        $stmt->execute([
            ':roles' => $rolesJson,
            ':uid' => $userId,
        ]);
    }

    public function insertNotification(int $userId, string $title, string $body, ?string $url): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO notifications (user_id, title, body, url, created_at)
             VALUES (:uid, :title, :body, :url, NOW())'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':title' => $title,
            ':body' => $body,
            ':url' => $url,
        ]);
    }

    public function fetchRecentApplications(int $limit = 50): array
    {
        [$teacherJoin, $teacherSelect] = $this->teacherProfileJoinAndSelect('ta.user_id');
        $sql = "SELECT ta.*, u.first_name, u.last_name, u.email" . $teacherSelect . "
            FROM teacher_applications ta
            JOIN users u ON u.id = ta.user_id
            " . $teacherJoin . "
            ORDER BY FIELD(ta.status,'pending','approved','rejected'), ta.created_at DESC
            LIMIT " . max(1, (int) $limit);
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll() ?: [];
    }

    public function findTeacherUserById(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, first_name, last_name, email, status, roles_json
             FROM users
             WHERE id = :id
               AND roles_json LIKE :teacher_role
             LIMIT 1'
        );
        $stmt->execute([
            ':id' => $userId,
            ':teacher_role' => '%"teacher"%',
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateUserStatus(int $userId, string $status): void
    {
        if (!in_array($status, ['active', 'suspended'], true)) {
            throw new \InvalidArgumentException('Invalid user status.');
        }

        $stmt = $this->pdo->prepare(
            'UPDATE users
             SET status = :status, updated_at = NOW()
             WHERE id = :id
               AND roles_json LIKE :teacher_role'
        );
        $stmt->execute([
            ':status' => $status,
            ':id' => $userId,
            ':teacher_role' => '%"teacher"%',
        ]);
    }

    /**
     * @param array<string,mixed> $meta
     */
    public function insertAuditLog(int $adminUserId, string $action, string $targetType, int $targetId, array $meta = []): void
    {
        if (!$this->tableExistsSafe('audit_logs')) {
            return;
        }

        $metaJson = null;
        if ($meta !== []) {
            $encoded = json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (is_string($encoded) && $encoded !== '') {
                $metaJson = $encoded;
            }
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO audit_logs (user_id, action, target_type, target_id, meta_json, created_at)
             VALUES (:user_id, :action, :target_type, :target_id, :meta_json, NOW())'
        );
        $stmt->execute([
            ':user_id' => $adminUserId > 0 ? $adminUserId : null,
            ':action' => $action,
            ':target_type' => $targetType,
            ':target_id' => $targetId,
            ':meta_json' => $metaJson,
        ]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchTeacherDirectory(string $search = '', int $limit = 80): array
    {
        [$teacherJoin, $teacherSelect] = $this->teacherProfileJoinAndSelect('u.id');
        $courseJoin = '';
        $courseSelect = $this->zeroTeacherStatsSelect();
        if ($this->coursesSchemaReady()) {
            $courseJoin = '
                LEFT JOIN (
                    SELECT
                        c.teacher_id,
                        COUNT(*) AS total_courses,
                        COALESCE(SUM(CASE WHEN c.status = "approved" THEN 1 ELSE 0 END), 0) AS approved_courses,
                        COALESCE(SUM(CASE WHEN c.status = "submitted" THEN 1 ELSE 0 END), 0) AS submitted_courses,
                        COALESCE(SUM(CASE WHEN c.status = "draft" THEN 1 ELSE 0 END), 0) AS draft_courses,
                        COALESCE(SUM(CASE WHEN c.status = "rejected" THEN 1 ELSE 0 END), 0) AS rejected_courses,
                        COALESCE(SUM(c.price_xof), 0) AS catalog_value_xof,
                        COALESCE(AVG(c.duration_hours), 0) AS avg_duration_hours
                    FROM courses c
                    WHERE c.deleted_at IS NULL
                    GROUP BY c.teacher_id
                ) course_stats ON course_stats.teacher_id = u.id
            ';
            $courseSelect = '
                COALESCE(course_stats.total_courses, 0) AS total_courses,
                COALESCE(course_stats.approved_courses, 0) AS approved_courses,
                COALESCE(course_stats.submitted_courses, 0) AS submitted_courses,
                COALESCE(course_stats.draft_courses, 0) AS draft_courses,
                COALESCE(course_stats.rejected_courses, 0) AS rejected_courses,
                COALESCE(course_stats.catalog_value_xof, 0) AS catalog_value_xof,
                COALESCE(course_stats.avg_duration_hours, 0) AS avg_duration_hours,
                ' . $this->teacherEnrollmentSelect() . ',
                ' . $this->teacherRevenueSelect() . ',
                ' . $this->teacherRatingSelect() . '
            ';
            $courseJoin .= $this->teacherEnrollmentJoin();
            $courseJoin .= $this->teacherRevenueJoin();
            $courseJoin .= $this->teacherRatingJoin();
        }

        $where = [
            'u.roles_json LIKE :teacher_role',
            'u.status IN ("active", "suspended")',
        ];
        $params = [':teacher_role' => '%"teacher"%'];

        $search = trim($search);
        if ($search !== '') {
            $searchClauses = [
                'CONCAT_WS(" ", u.first_name, u.last_name) LIKE :search',
                'u.email LIKE :search',
            ];
            if ($this->tableExistsSafe('teacher_profiles')) {
                if ($this->columnExistsSafe('teacher_profiles', 'professional_title')) {
                    $searchClauses[] = 'COALESCE(tp.professional_title, "") LIKE :search';
                }
            }
            $where[] = '(' . implode(' OR ', $searchClauses) . ')';
            $params[':search'] = '%' . $this->escapeLike($search) . '%';
        }

        $sql = 'SELECT
                    u.id,
                    u.first_name,
                    u.last_name,
                    u.email,
                    u.status,
                    u.created_at,
                    u.updated_at
                    ' . $teacherSelect . ',
                    ' . $courseSelect . '
                FROM users u
                ' . $teacherJoin . '
                ' . $courseJoin . '
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY
                    CASE WHEN u.status = "active" THEN 0 ELSE 1 END,
                    total_courses DESC,
                    u.last_name ASC,
                    u.first_name ASC
                LIMIT ' . max(1, min(200, $limit));

        return $this->rowsQuery($sql, $params);
    }

    /**
     * @param array<int,int> $teacherIds
     * @return array<int,array<string,mixed>>
     */
    public function fetchCoursesForTeacherIds(array $teacherIds, int $limit = 500): array
    {
        if (!$this->coursesSchemaReady()) {
            return [];
        }

        $ids = [];
        foreach ($teacherIds as $teacherId) {
            $teacherId = (int) $teacherId;
            if ($teacherId > 0) {
                $ids[$teacherId] = $teacherId;
            }
        }
        $ids = array_values($ids);
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $categoryJoin = $this->tableExistsSafe('categories') ? 'LEFT JOIN categories cat ON cat.id = c.category_id' : '';
        $categorySelect = $this->tableExistsSafe('categories') ? 'cat.name AS category_name' : 'NULL AS category_name';

        $sql = 'SELECT
                    c.id,
                    c.teacher_id,
                    c.title,
                    c.slug,
                    c.level,
                    c.status,
                    c.duration_hours,
                    c.price_xof,
                    c.cover_path,
                    c.created_at,
                    c.updated_at,
                    c.submitted_at,
                    ' . $categorySelect . ',
                    ' . $this->courseEnrollmentSelect() . ',
                    ' . $this->courseRevenueSelect() . ',
                    ' . $this->courseRatingSelect() . '
                FROM courses c
                ' . $categoryJoin . '
                ' . $this->courseEnrollmentJoin() . '
                ' . $this->courseRevenueJoin() . '
                ' . $this->courseRatingJoin() . '
                WHERE c.teacher_id IN (' . $placeholders . ')
                  AND c.deleted_at IS NULL
                ORDER BY
                    c.teacher_id ASC,
                    CASE c.status
                        WHEN "approved" THEN 0
                        WHEN "submitted" THEN 1
                        WHEN "draft" THEN 2
                        WHEN "rejected" THEN 3
                        ELSE 4
                    END,
                    c.updated_at DESC
                LIMIT ' . max(1, min(1000, $limit));

        return $this->rowsQueryWithNumericParams($sql, $ids);
    }

    /**
     * @return array{0:string,1:string}
     */
    private function teacherProfileJoinAndSelect(string $userIdExpression): array
    {
        if (!$this->tableExistsSafe('teacher_profiles')) {
            return ['', $this->teacherProfileNullSelect()];
        }

        $select = ', ' . implode(', ', [
            $this->profileColumnExpr('professional_title') . ' AS professional_title',
            $this->profileColumnExpr('short_bio') . ' AS short_bio',
            $this->profileColumnExpr('portfolio_url') . ' AS portfolio_url',
            $this->profileColumnExpr('linkedin_url') . ' AS linkedin_url',
            $this->profileColumnExpr('cv_path') . ' AS profile_cv_path',
        ]);

        return [' LEFT JOIN teacher_profiles tp ON tp.user_id = ' . $userIdExpression, $select];
    }

    private function profileColumnExpr(string $column): string
    {
        if (!$this->columnExistsSafe('teacher_profiles', $column)) {
            return 'NULL';
        }
        return 'tp.' . $column;
    }

    private function teacherProfileNullSelect(): string
    {
        return ', NULL AS professional_title,
                  NULL AS short_bio,
                  NULL AS portfolio_url,
                  NULL AS linkedin_url,
                  NULL AS profile_cv_path';
    }

    private function zeroTeacherStatsSelect(): string
    {
        return '0 AS total_courses,
                0 AS approved_courses,
                0 AS submitted_courses,
                0 AS draft_courses,
                0 AS rejected_courses,
                0 AS catalog_value_xof,
                0 AS avg_duration_hours,
                0 AS enrolled_students,
                0 AS paid_orders,
                0 AS revenue_xof,
                0 AS avg_rating,
                0 AS rating_count';
    }

    private function teacherEnrollmentJoin(): string
    {
        if (!$this->enrollmentsSchemaReady()) {
            return '';
        }

        return '
            LEFT JOIN (
                SELECT c.teacher_id, COUNT(DISTINCT e.user_id) AS enrolled_students
                FROM courses c
                JOIN enrollments e ON e.course_id = c.id AND e.status = "active"
                WHERE c.deleted_at IS NULL
                GROUP BY c.teacher_id
            ) enroll_stats ON enroll_stats.teacher_id = u.id
        ';
    }

    private function teacherEnrollmentSelect(): string
    {
        return $this->enrollmentsSchemaReady()
            ? 'COALESCE(enroll_stats.enrolled_students, 0) AS enrolled_students'
            : '0 AS enrolled_students';
    }

    private function teacherRevenueJoin(): string
    {
        if (!$this->salesSchemaReady()) {
            return '';
        }

        return '
            LEFT JOIN (
                SELECT
                    c.teacher_id,
                    COUNT(DISTINCT o.id) AS paid_orders,
                    COALESCE(SUM(oi.quantity * oi.unit_price_xof), 0) AS revenue_xof
                FROM courses c
                JOIN order_items oi ON oi.course_id = c.id
                JOIN orders o ON o.id = oi.order_id
                WHERE c.deleted_at IS NULL
                  AND LOWER(o.status) IN ("paid", "completed", "succeeded", "success", "free")
                GROUP BY c.teacher_id
            ) revenue_stats ON revenue_stats.teacher_id = u.id
        ';
    }

    private function teacherRevenueSelect(): string
    {
        return $this->salesSchemaReady()
            ? 'COALESCE(revenue_stats.paid_orders, 0) AS paid_orders,
               COALESCE(revenue_stats.revenue_xof, 0) AS revenue_xof'
            : '0 AS paid_orders, 0 AS revenue_xof';
    }

    private function teacherRatingJoin(): string
    {
        if (!$this->ratingsSchemaReady()) {
            return '';
        }

        $hiddenWhere = $this->columnExistsSafe('course_ratings', 'is_hidden_by_teacher')
            ? ' AND COALESCE(cr.is_hidden_by_teacher, 0) = 0'
            : '';

        return '
            LEFT JOIN (
                SELECT
                    c.teacher_id,
                    COALESCE(AVG(cr.rating), 0) AS avg_rating,
                    COUNT(cr.id) AS rating_count
                FROM courses c
                JOIN course_ratings cr ON cr.course_id = c.id' . $hiddenWhere . '
                WHERE c.deleted_at IS NULL
                GROUP BY c.teacher_id
            ) rating_stats ON rating_stats.teacher_id = u.id
        ';
    }

    private function teacherRatingSelect(): string
    {
        return $this->ratingsSchemaReady()
            ? 'COALESCE(rating_stats.avg_rating, 0) AS avg_rating,
               COALESCE(rating_stats.rating_count, 0) AS rating_count'
            : '0 AS avg_rating, 0 AS rating_count';
    }

    private function courseEnrollmentJoin(): string
    {
        if (!$this->enrollmentsSchemaReady()) {
            return '';
        }

        return '
            LEFT JOIN (
                SELECT course_id, COUNT(DISTINCT user_id) AS enrolled_students
                FROM enrollments
                WHERE status = "active"
                GROUP BY course_id
            ) course_enrollments ON course_enrollments.course_id = c.id
        ';
    }

    private function courseEnrollmentSelect(): string
    {
        return $this->enrollmentsSchemaReady()
            ? 'COALESCE(course_enrollments.enrolled_students, 0) AS enrolled_students'
            : '0 AS enrolled_students';
    }

    private function courseRevenueJoin(): string
    {
        if (!$this->salesSchemaReady()) {
            return '';
        }

        return '
            LEFT JOIN (
                SELECT
                    oi.course_id,
                    COUNT(DISTINCT o.id) AS paid_orders,
                    COALESCE(SUM(oi.quantity * oi.unit_price_xof), 0) AS revenue_xof
                FROM order_items oi
                JOIN orders o ON o.id = oi.order_id
                WHERE LOWER(o.status) IN ("paid", "completed", "succeeded", "success", "free")
                GROUP BY oi.course_id
            ) course_revenue ON course_revenue.course_id = c.id
        ';
    }

    private function courseRevenueSelect(): string
    {
        return $this->salesSchemaReady()
            ? 'COALESCE(course_revenue.paid_orders, 0) AS paid_orders,
               COALESCE(course_revenue.revenue_xof, 0) AS revenue_xof'
            : '0 AS paid_orders, 0 AS revenue_xof';
    }

    private function courseRatingJoin(): string
    {
        if (!$this->ratingsSchemaReady()) {
            return '';
        }

        $hiddenWhere = $this->columnExistsSafe('course_ratings', 'is_hidden_by_teacher')
            ? ' WHERE COALESCE(is_hidden_by_teacher, 0) = 0'
            : '';

        return '
            LEFT JOIN (
                SELECT course_id, COALESCE(AVG(rating), 0) AS avg_rating, COUNT(*) AS rating_count
                FROM course_ratings
                ' . $hiddenWhere . '
                GROUP BY course_id
            ) course_ratings_stats ON course_ratings_stats.course_id = c.id
        ';
    }

    private function courseRatingSelect(): string
    {
        return $this->ratingsSchemaReady()
            ? 'COALESCE(course_ratings_stats.avg_rating, 0) AS avg_rating,
               COALESCE(course_ratings_stats.rating_count, 0) AS rating_count'
            : '0 AS avg_rating, 0 AS rating_count';
    }

    private function coursesSchemaReady(): bool
    {
        return $this->tableExistsSafe('courses')
            && $this->columnExistsSafe('courses', 'teacher_id')
            && $this->columnExistsSafe('courses', 'status')
            && $this->columnExistsSafe('courses', 'deleted_at');
    }

    private function enrollmentsSchemaReady(): bool
    {
        return $this->tableExistsSafe('enrollments')
            && $this->columnExistsSafe('enrollments', 'course_id')
            && $this->columnExistsSafe('enrollments', 'user_id')
            && $this->columnExistsSafe('enrollments', 'status');
    }

    private function salesSchemaReady(): bool
    {
        return $this->tableExistsSafe('orders')
            && $this->tableExistsSafe('order_items')
            && $this->columnExistsSafe('orders', 'id')
            && $this->columnExistsSafe('orders', 'status')
            && $this->columnExistsSafe('order_items', 'order_id')
            && $this->columnExistsSafe('order_items', 'course_id')
            && $this->columnExistsSafe('order_items', 'quantity')
            && $this->columnExistsSafe('order_items', 'unit_price_xof');
    }

    private function ratingsSchemaReady(): bool
    {
        return $this->tableExistsSafe('course_ratings')
            && $this->columnExistsSafe('course_ratings', 'course_id')
            && $this->columnExistsSafe('course_ratings', 'rating');
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

    /**
     * @param array<int,int> $params
     * @return array<int,array<string,mixed>>
     */
    private function rowsQueryWithNumericParams(string $sql, array $params): array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_values($params));
            return $stmt->fetchAll() ?: [];
        } catch (\Throwable $e) {
            return [];
        }
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

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
