<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class TeacherDashboardModel extends Model
{
    private ?bool $ratingsModerationReady = null;
    private ?bool $hasArchivedAtColumn = null;

    public function countUnreadNotifications(int $userId): int
    {
        $sql = 'SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0';
        if ($this->hasArchivedAtColumn()) {
            $sql .= ' AND archived_at IS NULL';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function countUnreadSupportReplies(int $userId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM support_messages m
             JOIN support_conversations c ON c.id = m.conversation_id
             LEFT JOIN support_conversation_reads r
                ON r.conversation_id = c.id
               AND r.user_id = :uid_read
             WHERE c.created_by = :uid_owner
               AND m.id > COALESCE(r.last_read_message_id, 0)
               AND m.sender_id <> :uid_sender'
        );
        $stmt->execute([
            ':uid_read' => $userId,
            ':uid_owner' => $userId,
            ':uid_sender' => $userId,
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function countUnreadSupportNotifications(int $userId): int
    {
        $sql = 'SELECT COUNT(*)
             FROM notifications
             WHERE user_id = :uid
               AND is_read = 0
               AND (
                    LOWER(title) LIKE :support
                    OR LOWER(body) LIKE :support
               )';
        if ($this->hasArchivedAtColumn()) {
            $sql .= ' AND archived_at IS NULL';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':uid' => $userId,
            ':support' => '%support%',
        ]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchNotifications(int $userId, int $limit = 10): array
    {
        $limit = max(1, $limit);
        $sql = 'SELECT * FROM notifications WHERE user_id = :uid';
        if ($this->hasArchivedAtColumn()) {
            $sql .= ' AND archived_at IS NULL';
        }
        $sql .= " ORDER BY created_at DESC LIMIT {$limit}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    public function countPendingMeetings(?int $teacherId = null): int
    {
        $sql = 'SELECT COUNT(DISTINCT CONCAT_WS(CHAR(31), cm.course_id, COALESCE(cm.chapter_id, 0), cm.title, cm.mode, cm.student_id))
            FROM course_meetings cm
            JOIN courses c ON c.id = cm.course_id
            WHERE cm.student_id IS NOT NULL
              AND cm.status = "pending"
              AND c.deleted_at IS NULL
              AND c.status = "approved"';
        $params = [];
        if ($teacherId !== null) {
            $sql .= ' AND c.teacher_id = :tid';
            $params[':tid'] = $teacherId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function fetchLatestCourseUpdate(?int $teacherId = null): ?array
    {
        $sql = 'SELECT al.created_at, al.meta_json, c.id AS course_id, c.title AS course_title
            FROM audit_logs al
            JOIN courses c ON c.id = al.target_id
            WHERE al.target_type = "course"
              AND al.action IN ("course_update","course_update_started","course_update_finished")';
        $params = [];
        if ($teacherId !== null) {
            $sql .= ' AND c.teacher_id = :tid';
            $params[':tid'] = $teacherId;
        }
        $sql .= ' ORDER BY al.created_at DESC LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchCourseStats(int $teacherId, int $limit = 20): array
    {
        $limit = max(1, $limit);
        $viewJoin = '';
        $viewSelect = '0 AS view_count';
        if ($this->tableExists('course_views')) {
            $viewJoin = "LEFT JOIN (
                SELECT course_id, COUNT(*) AS view_count
                FROM course_views
                GROUP BY course_id
            ) v ON v.course_id = c.id";
            $viewSelect = 'COALESCE(v.view_count, 0) AS view_count';
        }

        $stmt = $this->pdo->prepare(
            "SELECT
                c.id,
                c.title,
                c.status,
                COALESCE(enr.nb, 0) AS enroll_count,
                {$viewSelect},
                COALESCE(r.avg_rating, 0) AS avg_rating,
                COALESCE(r.cnt_rating, 0) AS rating_count
            FROM courses c
            LEFT JOIN (
                SELECT course_id, COUNT(*) AS nb
                FROM enrollments
                WHERE status = 'active'
                GROUP BY course_id
            ) enr ON enr.course_id = c.id
            {$viewJoin}
            LEFT JOIN (
                SELECT course_id, AVG(rating) AS avg_rating, COUNT(*) AS cnt_rating
                FROM course_ratings
                " . ($this->ratingsModerationReady() ? "WHERE is_hidden_by_teacher = 0\n                " : "") . "
                GROUP BY course_id
            ) r ON r.course_id = c.id
            WHERE c.teacher_id = :tid
              AND c.deleted_at IS NULL
            ORDER BY c.created_at DESC
            LIMIT {$limit}"
        );
        $stmt->execute([':tid' => $teacherId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return array{done:int,total:int,students:int,rate:int}
     */
    public function fetchCompletionSummary(int $teacherId): array
    {
        if (!$this->tableExists('lesson_completions') || !$this->tableExists('course_lessons') || !$this->tableExists('course_chapters')) {
            return ['done' => 0, 'total' => 0, 'students' => 0, 'rate' => 0];
        }

        $doneStmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM lesson_completions lc
             JOIN courses c ON c.id = lc.course_id
             WHERE c.teacher_id = :tid
               AND c.deleted_at IS NULL'
        );
        $doneStmt->execute([':tid' => $teacherId]);
        $done = (int) $doneStmt->fetchColumn();

        $lessonStmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM course_lessons cl
             JOIN course_chapters cc ON cc.id = cl.chapter_id
             JOIN courses c ON c.id = cc.course_id
             WHERE c.teacher_id = :tid
               AND c.deleted_at IS NULL'
        );
        $lessonStmt->execute([':tid' => $teacherId]);
        $totalLessons = (int) $lessonStmt->fetchColumn();

        $studentStmt = $this->pdo->prepare(
            'SELECT COUNT(DISTINCT e.user_id)
             FROM enrollments e
             JOIN courses c ON c.id = e.course_id
             WHERE e.status = "active"
               AND c.teacher_id = :tid
               AND c.deleted_at IS NULL'
        );
        $studentStmt->execute([':tid' => $teacherId]);
        $students = (int) $studentStmt->fetchColumn();

        $possible = $totalLessons * $students;
        $rate = 0;
        if ($possible > 0) {
            $rate = (int) round(($done / $possible) * 100);
            if ($rate < 0) {
                $rate = 0;
            } elseif ($rate > 100) {
                $rate = 100;
            }
        }

        return [
            'done' => $done,
            'total' => $possible,
            'students' => $students,
            'rate' => $rate,
        ];
    }

    public function countPendingExamAttempts(int $teacherId): int
    {
        if (!$this->tableExists('exam_attempts') || !$this->tableExists('exam_sessions')) {
            return 0;
        }
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM exam_attempts ea
             JOIN exam_sessions es ON es.id = ea.exam_id
             JOIN courses c ON c.id = es.course_id
             WHERE ea.status = "submitted"
               AND c.teacher_id = :tid
               AND c.deleted_at IS NULL
               AND c.status = "approved"'
        );
        $stmt->execute([':tid' => $teacherId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchPendingExamAttempts(int $teacherId, int $limit = 6): array
    {
        if (!$this->tableExists('exam_attempts') || !$this->tableExists('exam_sessions')) {
            return [];
        }
        $limit = max(1, $limit);
        $stmt = $this->pdo->prepare(
            "SELECT ea.id AS attempt_id,
                    ea.submitted_at,
                    es.id AS exam_id,
                    es.title AS exam_title,
                    c.id AS course_id,
                    c.title AS course_title,
                    u.first_name AS student_first,
                    u.last_name AS student_last
             FROM exam_attempts ea
             JOIN exam_sessions es ON es.id = ea.exam_id
             JOIN courses c ON c.id = es.course_id
             LEFT JOIN users u ON u.id = ea.user_id
             WHERE ea.status = \"submitted\"
               AND c.teacher_id = :tid
               AND c.deleted_at IS NULL
               AND c.status = \"approved\"
             ORDER BY ea.submitted_at DESC, ea.id DESC
             LIMIT {$limit}"
        );
        $stmt->execute([':tid' => $teacherId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchPaidSalesByCourse(
        int $teacherId,
        int $limit = 6,
        ?string $startAt = null,
        ?string $endAt = null
    ): array
    {
        $limit = max(1, $limit);
        $joinDate = '';
        $params = [':tid' => $teacherId];
        if ($startAt !== null && $startAt !== '') {
            $joinDate .= ' AND o.created_at >= :start_at';
            $params[':start_at'] = $startAt;
        }
        if ($endAt !== null && $endAt !== '') {
            $joinDate .= ' AND o.created_at < :end_at';
            $params[':end_at'] = $endAt;
        }

        $stmt = $this->pdo->prepare(
            "SELECT c.id, c.title, c.status,
                    COALESCE(SUM(CASE WHEN o.status = 'paid' THEN oi.quantity ELSE 0 END), 0) AS sales_count,
                    COALESCE(SUM(CASE WHEN o.status = 'paid' THEN oi.quantity * oi.unit_price_xof ELSE 0 END), 0) AS revenue_xof
             FROM courses c
             LEFT JOIN order_items oi ON oi.course_id = c.id
             LEFT JOIN orders o ON o.id = oi.order_id {$joinDate}
             WHERE c.teacher_id = :tid
             GROUP BY c.id
             ORDER BY revenue_xof DESC, sales_count DESC, c.created_at DESC
             LIMIT {$limit}"
        );
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchCourseSalesWindowMap(int $teacherId, string $startAt, string $endAt): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.id AS course_id,
                    COALESCE(SUM(CASE WHEN o.id IS NOT NULL THEN oi.quantity ELSE 0 END), 0) AS sales_count,
                    COALESCE(SUM(CASE WHEN o.id IS NOT NULL THEN oi.quantity * oi.unit_price_xof ELSE 0 END), 0) AS revenue_xof
             FROM courses c
             LEFT JOIN order_items oi ON oi.course_id = c.id
             LEFT JOIN orders o ON o.id = oi.order_id
                   AND o.status = "paid"
                   AND o.created_at >= :start_at
                   AND o.created_at < :end_at
             WHERE c.teacher_id = :tid
             GROUP BY c.id'
        );
        $stmt->execute([
            ':tid' => $teacherId,
            ':start_at' => $startAt,
            ':end_at' => $endAt,
        ]);
        return $stmt->fetchAll() ?: [];
    }

    private function completionSchemaReady(): bool
    {
        return $this->tableExists('lesson_completions')
            && $this->tableExists('course_lessons')
            && $this->tableExists('course_chapters');
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchCourseCompletionByCourse(int $teacherId): array
    {
        if (!$this->completionSchemaReady()) {
            return [];
        }

        $stmt = $this->pdo->prepare(
            'SELECT
                c.id AS course_id,
                COALESCE(lessons.lesson_count, 0) AS lesson_count,
                COALESCE(students.student_count, 0) AS student_count,
                COALESCE(done.done_count, 0) AS done_count
             FROM courses c
             LEFT JOIN (
                SELECT cc.course_id, COUNT(*) AS lesson_count
                FROM course_lessons cl
                JOIN course_chapters cc ON cc.id = cl.chapter_id
                GROUP BY cc.course_id
             ) lessons ON lessons.course_id = c.id
             LEFT JOIN (
                SELECT e.course_id, COUNT(DISTINCT e.user_id) AS student_count
                FROM enrollments e
                WHERE e.status = "active"
                GROUP BY e.course_id
             ) students ON students.course_id = c.id
             LEFT JOIN (
                SELECT lc.course_id, COUNT(*) AS done_count
                FROM lesson_completions lc
                GROUP BY lc.course_id
             ) done ON done.course_id = c.id
             WHERE c.teacher_id = :tid
               AND c.deleted_at IS NULL'
        );
        $stmt->execute([':tid' => $teacherId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchApprovedCourseCompletionByCourse(int $teacherId): array
    {
        if (!$this->completionSchemaReady()) {
            return [];
        }

        $stmt = $this->pdo->prepare(
            'SELECT
                c.id AS course_id,
                COALESCE(lessons.lesson_count, 0) AS lesson_count,
                COALESCE(students.student_count, 0) AS student_count,
                COALESCE(done.done_count, 0) AS done_count
             FROM courses c
             LEFT JOIN (
                SELECT cc.course_id, COUNT(*) AS lesson_count
                FROM course_lessons cl
                JOIN course_chapters cc ON cc.id = cl.chapter_id
                GROUP BY cc.course_id
             ) lessons ON lessons.course_id = c.id
             LEFT JOIN (
                SELECT e.course_id, COUNT(DISTINCT e.user_id) AS student_count
                FROM enrollments e
                WHERE e.status = "active"
                GROUP BY e.course_id
             ) students ON students.course_id = c.id
             LEFT JOIN (
                SELECT lc.course_id, COUNT(*) AS done_count
                FROM lesson_completions lc
                GROUP BY lc.course_id
             ) done ON done.course_id = c.id
             WHERE c.teacher_id = :tid
               AND c.status = "approved"'
        );
        $stmt->execute([':tid' => $teacherId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return array<int,array{country_label:string,cnt:int}>
     */
    public function fetchStudentCountryBreakdown(int $teacherId, int $limit = 5): array
    {
        if ($teacherId < 1) {
            return [];
        }

        $limit = max(1, min(20, $limit));
        $stmt = $this->pdo->prepare(
            'SELECT
                COALESCE(NULLIF(TRIM(u.country), ""), "Autres") AS country_label,
                COUNT(DISTINCT e.user_id) AS cnt
             FROM enrollments e
             JOIN courses c ON c.id = e.course_id
             JOIN users u ON u.id = e.user_id
             WHERE e.status = "active"
               AND c.teacher_id = :tid
               AND c.deleted_at IS NULL
             GROUP BY COALESCE(NULLIF(TRIM(u.country), ""), "Autres")
             ORDER BY cnt DESC, country_label ASC
             LIMIT ' . $limit
        );
        $stmt->execute([':tid' => $teacherId]);
        $rows = $stmt->fetchAll() ?: [];
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'country_label' => (string) ($row['country_label'] ?? 'Autres'),
                'cnt' => (int) ($row['cnt'] ?? 0),
            ];
        }
        return $out;
    }

    /**
     * @return array<int,array{rating:int,cnt:int}>
     */
    public function fetchRatingDistribution(int $teacherId): array
    {
        if ($teacherId < 1) {
            return [];
        }

        $where = [
            'c.teacher_id = :tid',
        ];
        if ($this->ratingsModerationReady()) {
            $where[] = 'COALESCE(cr.is_hidden_by_teacher, 0) = 0';
        }

        $stmt = $this->pdo->prepare(
            'SELECT
                CAST(ROUND(cr.rating, 0) AS UNSIGNED) AS rating_value,
                COUNT(*) AS cnt
             FROM course_ratings cr
             JOIN courses c ON c.id = cr.course_id
             WHERE ' . implode(' AND ', $where) . '
             GROUP BY CAST(ROUND(cr.rating, 0) AS UNSIGNED)
             ORDER BY rating_value DESC'
        );
        $stmt->execute([':tid' => $teacherId]);
        $rows = $stmt->fetchAll() ?: [];
        $out = [];
        foreach ($rows as $row) {
            $rating = (int) ($row['rating_value'] ?? 0);
            if ($rating < 1) {
                $rating = 1;
            } elseif ($rating > 5) {
                $rating = 5;
            }
            $out[] = [
                'rating' => $rating,
                'cnt' => (int) ($row['cnt'] ?? 0),
            ];
        }
        return $out;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchCourseFunnelRows(
        int $teacherId,
        string $startAt,
        string $endAt,
        int $limit = 8
    ): array {
        if ($teacherId < 1) {
            return [];
        }
        $hasCourseViews = $this->tableExists('course_views');
        $safeLimit = max(1, min(50, $limit));

        $viewsJoin = '';
        $viewsSelect = '0 AS views_count';
        if ($hasCourseViews) {
            $viewsJoin = 'LEFT JOIN (
                SELECT course_id, COUNT(*) AS views_count
                FROM course_views
                WHERE created_at >= :start_at_view
                  AND created_at < :end_at_view
                GROUP BY course_id
            ) views ON views.course_id = c.id';
            $viewsSelect = 'COALESCE(views.views_count, 0) AS views_count';
        }

        $stmt = $this->pdo->prepare(
            "SELECT
                c.id AS course_id,
                c.title AS course_title,
                c.status,
                {$viewsSelect},
                COALESCE(sales.paid_sales, 0) AS paid_sales,
                COALESCE(sales.revenue_xof, 0) AS revenue_xof
             FROM courses c
             {$viewsJoin}
             LEFT JOIN (
                SELECT oi.course_id,
                       COALESCE(SUM(oi.quantity), 0) AS paid_sales,
                       COALESCE(SUM(oi.quantity * oi.unit_price_xof), 0) AS revenue_xof
                FROM order_items oi
                JOIN orders o ON o.id = oi.order_id
                WHERE o.status = 'paid'
                  AND o.created_at >= :start_at_order
                  AND o.created_at < :end_at_order
                GROUP BY oi.course_id
             ) sales ON sales.course_id = c.id
             WHERE c.teacher_id = :tid
               AND c.deleted_at IS NULL
             ORDER BY revenue_xof DESC, views_count DESC, c.id DESC
             LIMIT {$safeLimit}"
        );

        $params = [
            ':tid' => $teacherId,
            ':start_at_order' => $startAt,
            ':end_at_order' => $endAt,
        ];
        if ($hasCourseViews) {
            $params[':start_at_view'] = $startAt;
            $params[':end_at_view'] = $endAt;
        }
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchApprovedCoursePortfolioRows(
        int $teacherId,
        ?string $startAt = null,
        ?string $endAt = null,
        ?string $courseName = null,
        ?string $status = 'approved',
        int $courseId = 0,
        ?string $studentCountry = null
    ): array {
        if ($teacherId < 1) {
            return [];
        }

        $params = [
            ':main_tid' => $teacherId,
            ':sales_tid' => $teacherId,
        ];

        $salesDateFilter = '';
        if ($startAt !== null && $startAt !== '') {
            $salesDateFilter .= ' AND o.created_at >= :sales_start_at';
            $params[':sales_start_at'] = $startAt;
        }
        if ($endAt !== null && $endAt !== '') {
            $salesDateFilter .= ' AND o.created_at < :sales_end_at';
            $params[':sales_end_at'] = $endAt;
        }

        $courseName = trim((string) $courseName);
        $mainCourseFilter = '';
        $salesCourseFilter = '';
        $courseIdFilter = '';
        $salesCourseIdFilter = '';
        if ($courseName !== '') {
            $mainCourseFilter = ' AND c.title LIKE :main_course_name';
            $salesCourseFilter = ' AND c2.title LIKE :sales_course_name';
            $likeTerm = '%' . $courseName . '%';
            $params[':main_course_name'] = $likeTerm;
            $params[':sales_course_name'] = $likeTerm;
        }
        if ($courseId > 0) {
            $courseIdFilter = ' AND c.id = :main_course_id';
            $salesCourseIdFilter = ' AND c2.id = :sales_course_id';
            $params[':main_course_id'] = $courseId;
            $params[':sales_course_id'] = $courseId;
        }

        $status = strtolower(trim((string) $status));
        $mainStatusFilter = '';
        $salesStatusFilter = '';
        if (in_array($status, ['draft', 'submitted', 'approved', 'rejected'], true)) {
            $mainStatusFilter = ' AND c.status = :main_status';
            $salesStatusFilter = ' AND c2.status = :sales_status';
            $params[':main_status'] = $status;
            $params[':sales_status'] = $status;
        }

        $country = trim((string) $studentCountry);
        $hasCountryFilter = $country !== '' && $this->columnExistsSafe('users', 'country');
        $enrollmentUserJoin = '';
        $enrollmentCountryFilter = '';
        $salesUserJoin = '';
        $salesCountryFilter = '';
        if ($hasCountryFilter) {
            $enrollmentUserJoin = 'JOIN users u_enr ON u_enr.id = e.user_id';
            $enrollmentCountryFilter = ' AND COALESCE(NULLIF(TRIM(u_enr.country), ""), "Autres") = :enr_country';
            $salesUserJoin = 'JOIN users u_sale ON u_sale.id = o.user_id';
            $salesCountryFilter = ' AND COALESCE(NULLIF(TRIM(u_sale.country), ""), "Autres") = :sales_country';
            $params[':enr_country'] = $country;
            $params[':sales_country'] = $country;
        }

        $referralSelect = '0 AS referral_amount, 0 AS referral_count, 0 AS referral_l1_amount, 0 AS referral_l2_amount, 0 AS referral_l1_count, 0 AS referral_l2_count';
        $referralJoin = '';
        if ($this->tableExists('referral_commissions')) {
            $referralSelect = 'COALESCE(refs.referral_amount, 0) AS referral_amount, COALESCE(refs.referral_count, 0) AS referral_count, COALESCE(refs.referral_l1_amount, 0) AS referral_l1_amount, COALESCE(refs.referral_l2_amount, 0) AS referral_l2_amount, COALESCE(refs.referral_l1_count, 0) AS referral_l1_count, COALESCE(refs.referral_l2_count, 0) AS referral_l2_count';
            $referralDateFilter = '';
            if ($startAt !== null && $startAt !== '') {
                $referralDateFilter .= ' AND rc.created_at >= :ref_start_at';
                $params[':ref_start_at'] = $startAt;
            }
            if ($endAt !== null && $endAt !== '') {
                $referralDateFilter .= ' AND rc.created_at < :ref_end_at';
                $params[':ref_end_at'] = $endAt;
            }
            $params[':ref_tid'] = $teacherId;
            $referralJoin = "
                LEFT JOIN (
                    SELECT
                        rc.course_id,
                        COALESCE(SUM(rc.amount_xof), 0) AS referral_amount,
                        COUNT(*) AS referral_count,
                        COALESCE(SUM(CASE WHEN rc.level = 1 THEN rc.amount_xof ELSE 0 END), 0) AS referral_l1_amount,
                        COALESCE(SUM(CASE WHEN rc.level = 2 THEN rc.amount_xof ELSE 0 END), 0) AS referral_l2_amount,
                        COALESCE(SUM(CASE WHEN rc.level = 1 THEN 1 ELSE 0 END), 0) AS referral_l1_count,
                        COALESCE(SUM(CASE WHEN rc.level = 2 THEN 1 ELSE 0 END), 0) AS referral_l2_count
                    FROM referral_commissions rc
                    WHERE rc.teacher_id = :ref_tid
                      AND rc.status = 'earned'
                      {$referralDateFilter}
                    GROUP BY rc.course_id
                ) refs ON refs.course_id = c.id
            ";
        }

        $stmt = $this->pdo->prepare(
            "SELECT
                c.id,
                c.title,
                c.status,
                c.created_at,
                COALESCE(c.price_xof, 0) AS course_price_xof,
                COALESCE(enr.enrolled_count, 0) AS enrolled_count,
                COALESCE(sales.paid_orders, 0) AS paid_orders,
                COALESCE(sales.paid_sales, 0) AS paid_sales,
                COALESCE(sales.gross_amount, 0) AS gross_amount,
                {$referralSelect},
                CASE WHEN COALESCE(sales.paid_sales, 0) > 0 THEN 1 ELSE 0 END AS is_paid
             FROM courses c
             LEFT JOIN (
                SELECT
                    e.course_id,
                    COUNT(*) AS enrolled_count
                FROM enrollments e
                {$enrollmentUserJoin}
                WHERE e.status = 'active'
                  {$enrollmentCountryFilter}
                GROUP BY e.course_id
             ) enr ON enr.course_id = c.id
             LEFT JOIN (
                SELECT
                    oi.course_id,
                    COUNT(DISTINCT o.id) AS paid_orders,
                    COALESCE(SUM(oi.quantity), 0) AS paid_sales,
                    COALESCE(SUM(oi.quantity * oi.unit_price_xof), 0) AS gross_amount
                FROM order_items oi
                JOIN orders o ON o.id = oi.order_id
                JOIN courses c2 ON c2.id = oi.course_id
                {$salesUserJoin}
                WHERE o.status = 'paid'
                  AND c2.teacher_id = :sales_tid
                  {$salesCourseFilter}
                  {$salesCourseIdFilter}
                  {$salesStatusFilter}
                  {$salesCountryFilter}
                  {$salesDateFilter}
                GROUP BY oi.course_id
             ) sales ON sales.course_id = c.id
             {$referralJoin}
             WHERE c.teacher_id = :main_tid
               {$mainCourseFilter}
               {$courseIdFilter}
               {$mainStatusFilter}
             ORDER BY gross_amount DESC, enrolled_count DESC, c.created_at DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    public function countTotalCourseViews(int $teacherId): int
    {
        if (!$this->tableExists('course_views')) {
            return 0;
        }
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM course_views cv
             JOIN courses c ON c.id = cv.course_id
             WHERE c.teacher_id = :tid
               AND c.deleted_at IS NULL'
        );
        $stmt->execute([':tid' => $teacherId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    /**
     * @return array<int,array{status:string,cnt:int}>
     */
    public function fetchCourseTotalsByStatus(int $teacherId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT status, COUNT(*) AS cnt FROM courses WHERE teacher_id = :tid AND deleted_at IS NULL GROUP BY status'
        );
        $stmt->execute([':tid' => $teacherId]);
        $rows = $stmt->fetchAll() ?: [];
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'status' => (string) ($row['status'] ?? ''),
                'cnt' => (int) ($row['cnt'] ?? 0),
            ];
        }
        return $out;
    }

    public function countActiveEnrollments(int $teacherId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
            FROM enrollments e
            JOIN courses c ON c.id = e.course_id
            WHERE e.status = "active"
              AND c.teacher_id = :tid
              AND c.deleted_at IS NULL'
        );
        $stmt->execute([':tid' => $teacherId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array{avg:float,count:int}
     */
    public function fetchRatingSummary(int $teacherId): array
    {
        $where = [
            'c.teacher_id = :tid',
        ];
        if ($this->ratingsModerationReady()) {
            $where[] = 'COALESCE(cr.is_hidden_by_teacher, 0) = 0';
        }

        $stmt = $this->pdo->prepare(
            'SELECT AVG(cr.rating) AS avg_rating, COUNT(*) AS cnt
            FROM course_ratings cr
            JOIN courses c ON c.id = cr.course_id
            WHERE ' . implode(' AND ', $where)
        );
        $stmt->execute([':tid' => $teacherId]);
        $row = $stmt->fetch() ?: [];
        return [
            'avg' => (float) ($row['avg_rating'] ?? 0),
            'count' => (int) ($row['cnt'] ?? 0),
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchApprovedRatingSummaryByCourse(int $teacherId): array
    {
        if ($teacherId < 1) {
            return [];
        }

        $where = [
            'c.teacher_id = :tid',
            'c.status = "approved"',
        ];
        if ($this->ratingsModerationReady()) {
            $where[] = 'COALESCE(cr.is_hidden_by_teacher, 0) = 0';
        }

        $stmt = $this->pdo->prepare(
            'SELECT
                c.id AS course_id,
                AVG(cr.rating) AS avg_rating,
                COUNT(*) AS cnt
             FROM course_ratings cr
             JOIN courses c ON c.id = cr.course_id
             WHERE ' . implode(' AND ', $where) . '
             GROUP BY c.id'
        );
        $stmt->execute([':tid' => $teacherId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchTeacherMetricSeriesForRange(
        int $teacherId,
        string $metric,
        string $granularity,
        string $fromInclusive,
        string $toExclusive,
        array $filters = []
    ): array {
        if ($teacherId < 1) {
            return [];
        }

        $config = $this->teacherGraphMetricConfig($metric);
        if ($config === null) {
            return [];
        }

        foreach ((array) ($config['required_tables'] ?? []) as $table) {
            if (!$this->tableExistsSafe((string) $table)) {
                return [];
            }
        }

        $createdAtField = (string) ($config['created_at_field'] ?? '');
        if ($createdAtField === '') {
            return [];
        }
        $createdAtParts = explode('.', $createdAtField, 2);
        if (count($createdAtParts) === 2 && !$this->columnExistsSafe((string) ($config['created_at_table'] ?? ''), (string) $createdAtParts[1])) {
            return [];
        }

        $bucketExpr = $this->teacherGraphBucketExpr($granularity, $createdAtField);
        if ($bucketExpr === '') {
            return [];
        }

        $extraWhere = '';
        $params = [
            ':tid' => $teacherId,
            ':from_inclusive' => $fromInclusive,
            ':to_exclusive' => $toExclusive,
        ];

        $courseId = max(0, (int) ($filters['course_id'] ?? 0));
        if ($courseId > 0) {
            $extraWhere .= ' AND c.id = :course_id';
            $params[':course_id'] = $courseId;
        }

        $courseStatus = strtolower(trim((string) ($filters['course_status'] ?? '')));
        if (in_array($courseStatus, ['draft', 'submitted', 'approved', 'rejected'], true)) {
            $extraWhere .= ' AND c.status = :course_status';
            $params[':course_status'] = $courseStatus;
        }

        $studentCountry = trim((string) ($filters['country'] ?? ''));
        $userIdField = trim((string) ($config['user_id_field'] ?? ''));
        if ($studentCountry !== '' && $userIdField !== '' && $this->columnExistsSafe('users', 'country')) {
            $extraWhere .= ' AND EXISTS (
                    SELECT 1
                    FROM users u_filter
                    WHERE u_filter.id = ' . $userIdField . '
                      AND COALESCE(NULLIF(TRIM(u_filter.country), ""), "Autres") = :student_country
                )';
            $params[':student_country'] = $studentCountry;
        }

        $sql = 'SELECT
                    ' . $bucketExpr . ' AS bucket_key,
                    ' . (string) ($config['value_expr'] ?? '0') . ' AS metric_value
                FROM ' . (string) ($config['from_sql'] ?? '') . '
                WHERE ' . (string) ($config['where_sql'] ?? '1=0') . '
                  AND ' . $createdAtField . ' >= :from_inclusive
                  AND ' . $createdAtField . ' < :to_exclusive
                  ' . $extraWhere . '
                GROUP BY ' . $bucketExpr . '
                ORDER BY bucket_key ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    public function fetchTeacherMetricFirstDate(int $teacherId, string $metric): string
    {
        if ($teacherId < 1) {
            return '';
        }

        $config = $this->teacherGraphMetricConfig($metric);
        if ($config === null) {
            return '';
        }

        foreach ((array) ($config['required_tables'] ?? []) as $table) {
            if (!$this->tableExistsSafe((string) $table)) {
                return '';
            }
        }

        $createdAtField = (string) ($config['created_at_field'] ?? '');
        if ($createdAtField === '') {
            return '';
        }
        $createdAtParts = explode('.', $createdAtField, 2);
        if (count($createdAtParts) === 2 && !$this->columnExistsSafe((string) ($config['created_at_table'] ?? ''), (string) $createdAtParts[1])) {
            return '';
        }

        $sql = 'SELECT MIN(' . $createdAtField . ') AS first_at
                FROM ' . (string) ($config['from_sql'] ?? '') . '
                WHERE ' . (string) ($config['where_sql'] ?? '1=0') . '
                  AND ' . $createdAtField . ' IS NOT NULL';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':tid' => $teacherId]);
        return trim((string) ($stmt->fetchColumn() ?: ''));
    }

    /**
     * @return array<string,mixed>|null
     */
    private function teacherGraphMetricConfig(string $metric): ?array
    {
        $key = strtolower(trim($metric));

        $config = [
            'gross_revenue_xof' => [
                'required_tables' => ['orders', 'order_items', 'courses'],
                'created_at_table' => 'orders',
                'created_at_field' => 'o.created_at',
                'user_id_field' => 'o.user_id',
                'from_sql' => 'order_items oi
                    JOIN orders o ON o.id = oi.order_id
                    JOIN courses c ON c.id = oi.course_id',
                'where_sql' => 'c.teacher_id = :tid AND LOWER(o.status) IN ("paid", "completed", "succeeded", "success", "free")',
                'value_expr' => 'COALESCE(SUM(oi.quantity * oi.unit_price_xof), 0)',
            ],
            'paid_orders' => [
                'required_tables' => ['orders', 'order_items', 'courses'],
                'created_at_table' => 'orders',
                'created_at_field' => 'o.created_at',
                'user_id_field' => 'o.user_id',
                'from_sql' => 'order_items oi
                    JOIN orders o ON o.id = oi.order_id
                    JOIN courses c ON c.id = oi.course_id',
                'where_sql' => 'c.teacher_id = :tid AND LOWER(o.status) IN ("paid", "completed", "succeeded", "success", "free")',
                'value_expr' => 'COUNT(DISTINCT o.id)',
            ],
            'paid_sales' => [
                'required_tables' => ['orders', 'order_items', 'courses'],
                'created_at_table' => 'orders',
                'created_at_field' => 'o.created_at',
                'user_id_field' => 'o.user_id',
                'from_sql' => 'order_items oi
                    JOIN orders o ON o.id = oi.order_id
                    JOIN courses c ON c.id = oi.course_id',
                'where_sql' => 'c.teacher_id = :tid AND LOWER(o.status) IN ("paid", "completed", "succeeded", "success", "free")',
                'value_expr' => 'COALESCE(SUM(oi.quantity), 0)',
            ],
            'active_enrollments' => [
                'required_tables' => ['enrollments', 'courses'],
                'created_at_table' => 'enrollments',
                'created_at_field' => 'e.created_at',
                'user_id_field' => 'e.user_id',
                'from_sql' => 'enrollments e
                    JOIN courses c ON c.id = e.course_id',
                'where_sql' => 'c.teacher_id = :tid AND c.deleted_at IS NULL AND e.status = "active"',
                'value_expr' => 'COUNT(*)',
            ],
            'course_views' => [
                'required_tables' => ['course_views', 'courses'],
                'created_at_table' => 'course_views',
                'created_at_field' => 'v.created_at',
                'user_id_field' => 'v.user_id',
                'from_sql' => 'course_views v
                    JOIN courses c ON c.id = v.course_id',
                'where_sql' => 'c.teacher_id = :tid AND c.deleted_at IS NULL',
                'value_expr' => 'COUNT(*)',
            ],
        ];

        return $config[$key] ?? null;
    }

    private function teacherGraphBucketExpr(string $granularity, string $createdAtField): string
    {
        $key = strtolower(trim($granularity));

        if ($key === 'day') {
            return 'DATE_FORMAT(DATE(' . $createdAtField . '), "%Y-%m-%d")';
        }

        if ($key === 'week') {
            return 'DATE_FORMAT(DATE_SUB(DATE(' . $createdAtField . '), INTERVAL WEEKDAY(' . $createdAtField . ') DAY), "%Y-%m-%d")';
        }

        if ($key === 'month') {
            return 'DATE_FORMAT(' . $createdAtField . ', "%Y-%m")';
        }

        if ($key === 'year') {
            return 'DATE_FORMAT(' . $createdAtField . ', "%Y")';
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
        if ($table === '' || $column === '') {
            return false;
        }
        try {
            return $this->columnExists($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function ratingsModerationReady(): bool
    {
        if (is_bool($this->ratingsModerationReady)) {
            return $this->ratingsModerationReady;
        }
        $this->ratingsModerationReady = $this->columnExists('course_ratings', 'is_hidden_by_teacher');
        return $this->ratingsModerationReady;
    }

    private function hasArchivedAtColumn(): bool
    {
        if ($this->hasArchivedAtColumn !== null) {
            return $this->hasArchivedAtColumn;
        }

        try {
            $this->hasArchivedAtColumn = $this->columnExists('notifications', 'archived_at');
        } catch (\Throwable $e) {
            $this->hasArchivedAtColumn = false;
        }

        return $this->hasArchivedAtColumn;
    }
}
