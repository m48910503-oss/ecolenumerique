<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class AdminCoursesModel extends Model
{
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        $this->pdo->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    public function findCourseWithTeacherById(int $courseId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, u.first_name, u.last_name, u.id AS teacher_user_id, cat.name AS category_name
             FROM courses c
             JOIN users u ON u.id = c.teacher_id
             LEFT JOIN categories cat ON cat.id = c.category_id
             WHERE c.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $courseId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function markCourseApproved(int $courseId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE courses
             SET status = :status, last_rejection_reason = NULL, updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            ':status' => 'approved',
            ':id' => $courseId,
        ]);
    }

    public function markCourseRejected(int $courseId, string $reason): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE courses
             SET status = :status, last_rejection_reason = :reason, updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            ':status' => 'rejected',
            ':reason' => $reason,
            ':id' => $courseId,
        ]);
    }

    public function insertNotification(int $userId, string $title, string $body, string $url): void
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

    /**
     * @param array<string,mixed> $meta
     */
    public function insertAudit(int $userId, string $action, int $courseId, array $meta = []): void
    {
        $metaJson = null;
        if ($meta !== []) {
            $encoded = json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (is_string($encoded) && $encoded !== '') {
                $metaJson = $encoded;
            }
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO audit_logs (user_id, action, target_type, target_id, meta_json, created_at)
             VALUES (:uid, :action, :type, :tid, :meta, NOW())'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':action' => $action,
            ':type' => 'course',
            ':tid' => $courseId,
            ':meta' => $metaJson,
        ]);
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    public function fetchPendingCourses(int $limit = 0, int $offset = 0, array $filters = []): array
    {
        [$whereSql, $params] = $this->buildPendingWhere($filters);

        $sql = 'SELECT c.*, u.first_name, u.last_name, cat.name AS category_name
                FROM courses c
                JOIN users u ON u.id = c.teacher_id
                LEFT JOIN categories cat ON cat.id = c.category_id
                WHERE ' . $whereSql . '
                ORDER BY c.submitted_at ASC, c.id ASC';

        $limit = max(0, $limit);
        $offset = max(0, $offset);
        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @param array<string,mixed> $filters
     */
    public function fetchPendingCoursesTotal(array $filters = []): int
    {
        [$whereSql, $params] = $this->buildPendingWhere($filters);
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS total
             FROM courses c
             JOIN users u ON u.id = c.teacher_id
             LEFT JOIN categories cat ON cat.id = c.category_id
             WHERE ' . $whereSql
        );
        $stmt->execute($params);
        $row = $stmt->fetch() ?: [];
        return (int) ($row['total'] ?? 0);
    }

    public function fetchPendingCourseById(int $courseId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, u.first_name, u.last_name, cat.name AS category_name
             FROM courses c
             JOIN users u ON u.id = c.teacher_id
             LEFT JOIN categories cat ON cat.id = c.category_id
             WHERE c.id = :id AND c.status = "submitted"
             LIMIT 1'
        );
        $stmt->execute([':id' => $courseId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchHistoryCourses(int $limit = 20, array $filters = []): array
    {
        $limit = max(1, $limit);
        [$whereSql, $params] = $this->buildHistoryWhere($filters);
        $stmt = $this->pdo->prepare(
            'SELECT c.*, u.first_name, u.last_name, cat.name AS category_name
             FROM courses c
             JOIN users u ON u.id = c.teacher_id
             LEFT JOIN categories cat ON cat.id = c.category_id
             WHERE ' . $whereSql . '
             ORDER BY c.updated_at DESC
             LIMIT ' . $limit
        );
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return array{
     *   pending_total:int,
     *   urgent_total:int,
     *   processed_today:int,
     *   avg_review_minutes:int,
     *   approved_total:int,
     *   rejected_total:int
     * }
     */
    public function fetchModerationSummary(): array
    {
        $summary = [
            'pending_total' => 0,
            'urgent_total' => 0,
            'processed_today' => 0,
            'avg_review_minutes' => 0,
            'approved_total' => 0,
            'rejected_total' => 0,
        ];

        $pendingStmt = $this->pdo->query(
            'SELECT
                COUNT(*) AS pending_total,
                COALESCE(SUM(CASE WHEN submitted_at IS NOT NULL AND TIMESTAMPDIFF(HOUR, submitted_at, NOW()) >= 72 THEN 1 ELSE 0 END), 0) AS urgent_total
             FROM courses
             WHERE status = "submitted"'
        );
        $pendingRow = $pendingStmt->fetch() ?: [];
        $summary['pending_total'] = (int) ($pendingRow['pending_total'] ?? 0);
        $summary['urgent_total'] = (int) ($pendingRow['urgent_total'] ?? 0);

        $processedStmt = $this->pdo->query(
            'SELECT COUNT(*) AS processed_today
             FROM courses
             WHERE status IN ("approved","rejected")
               AND DATE(updated_at) = CURDATE()'
        );
        $processedRow = $processedStmt->fetch() ?: [];
        $summary['processed_today'] = (int) ($processedRow['processed_today'] ?? 0);

        $avgStmt = $this->pdo->query(
            'SELECT COALESCE(AVG(TIMESTAMPDIFF(MINUTE, submitted_at, updated_at)), 0) AS avg_review_minutes
             FROM courses
             WHERE status IN ("approved","rejected")
               AND submitted_at IS NOT NULL
               AND updated_at >= submitted_at'
        );
        $avgRow = $avgStmt->fetch() ?: [];
        $summary['avg_review_minutes'] = (int) round((float) ($avgRow['avg_review_minutes'] ?? 0));

        $statusStmt = $this->pdo->query(
            'SELECT
                COALESCE(SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END), 0) AS approved_total,
                COALESCE(SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END), 0) AS rejected_total
             FROM courses'
        );
        $statusRow = $statusStmt->fetch() ?: [];
        $summary['approved_total'] = (int) ($statusRow['approved_total'] ?? 0);
        $summary['rejected_total'] = (int) ($statusRow['rejected_total'] ?? 0);

        return $summary;
    }

    /**
     * @param array<int,int> $courseIds
     * @return array<int,array{
     *   students:int,
     *   revenue_xof:int,
     *   paid_orders:int,
     *   avg_rating:float,
     *   rating_count:int,
     *   completion_rate:int,
     *   monthly_enrollments:array<int,array{label:string,count:int}>
     * }>
     */
    public function fetchCourseStatsForIds(array $courseIds): array
    {
        $ids = [];
        foreach ($courseIds as $courseId) {
            $courseId = (int) $courseId;
            if ($courseId > 0) {
                $ids[$courseId] = $courseId;
            }
        }
        $ids = array_values($ids);
        if ($ids === []) {
            return [];
        }

        $months = $this->buildLastSixMonthBuckets();
        $stats = [];
        foreach ($ids as $courseId) {
            $stats[$courseId] = [
                'students' => 0,
                'revenue_xof' => 0,
                'paid_orders' => 0,
                'avg_rating' => 0.0,
                'rating_count' => 0,
                'completion_rate' => 0,
                'monthly_enrollments' => array_values($months),
            ];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        if ($this->enrollmentsSchemaReady()) {
            $stmt = $this->pdo->prepare(
                'SELECT course_id, COUNT(DISTINCT user_id) AS students
                 FROM enrollments
                 WHERE status = "active"
                   AND course_id IN (' . $placeholders . ')
                 GROUP BY course_id'
            );
            $stmt->execute($ids);
            foreach ($stmt->fetchAll() ?: [] as $row) {
                $courseId = (int) ($row['course_id'] ?? 0);
                if (isset($stats[$courseId])) {
                    $stats[$courseId]['students'] = (int) ($row['students'] ?? 0);
                }
            }

            if ($this->columnExistsSafe('enrollments', 'created_at')) {
                $startAt = array_key_first($months);
                $monthlyStmt = $this->pdo->prepare(
                    'SELECT course_id, created_at
                     FROM enrollments
                     WHERE status = "active"
                       AND course_id IN (' . $placeholders . ')
                       AND created_at >= ?'
                );
                $monthlyStmt->execute([...$ids, $startAt . '-01 00:00:00']);
                $monthlyCounts = [];
                foreach ($stats as $courseId => $_row) {
                    $monthlyCounts[$courseId] = $months;
                }
                foreach ($monthlyStmt->fetchAll() ?: [] as $row) {
                    $courseId = (int) ($row['course_id'] ?? 0);
                    $createdAt = trim((string) ($row['created_at'] ?? ''));
                    if (!isset($monthlyCounts[$courseId]) || $createdAt === '') {
                        continue;
                    }
                    try {
                        $bucket = (new \DateTimeImmutable($createdAt))->format('Y-m');
                    } catch (\Throwable $e) {
                        continue;
                    }
                    if (isset($monthlyCounts[$courseId][$bucket])) {
                        $monthlyCounts[$courseId][$bucket]['count']++;
                    }
                }
                foreach ($monthlyCounts as $courseId => $rows) {
                    if (isset($stats[$courseId])) {
                        $stats[$courseId]['monthly_enrollments'] = array_values($rows);
                    }
                }
            }
        }

        if ($this->courseSalesSchemaReady()) {
            $stmt = $this->pdo->prepare(
                'SELECT
                    oi.course_id,
                    COUNT(DISTINCT o.id) AS paid_orders,
                    COALESCE(SUM(oi.quantity * oi.unit_price_xof), 0) AS revenue_xof
                 FROM order_items oi
                 JOIN orders o ON o.id = oi.order_id
                 WHERE oi.course_id IN (' . $placeholders . ')
                   AND LOWER(o.status) IN ("paid", "completed", "succeeded", "success", "free")
                 GROUP BY oi.course_id'
            );
            $stmt->execute($ids);
            foreach ($stmt->fetchAll() ?: [] as $row) {
                $courseId = (int) ($row['course_id'] ?? 0);
                if (isset($stats[$courseId])) {
                    $stats[$courseId]['paid_orders'] = (int) ($row['paid_orders'] ?? 0);
                    $stats[$courseId]['revenue_xof'] = (int) ($row['revenue_xof'] ?? 0);
                }
            }
        }

        if ($this->tableExistsSafe('course_ratings')) {
            $hiddenWhere = $this->columnExistsSafe('course_ratings', 'is_hidden_by_teacher')
                ? ' AND COALESCE(is_hidden_by_teacher, 0) = 0'
                : '';
            $stmt = $this->pdo->prepare(
                'SELECT course_id, COALESCE(AVG(rating), 0) AS avg_rating, COUNT(*) AS rating_count
                 FROM course_ratings
                 WHERE course_id IN (' . $placeholders . ')
                 ' . $hiddenWhere . '
                 GROUP BY course_id'
            );
            $stmt->execute($ids);
            foreach ($stmt->fetchAll() ?: [] as $row) {
                $courseId = (int) ($row['course_id'] ?? 0);
                if (isset($stats[$courseId])) {
                    $stats[$courseId]['avg_rating'] = round((float) ($row['avg_rating'] ?? 0), 2);
                    $stats[$courseId]['rating_count'] = (int) ($row['rating_count'] ?? 0);
                }
            }
        }

        if (
            $this->tableExistsSafe('lesson_completions')
            && $this->tableExistsSafe('course_lessons')
            && $this->tableExistsSafe('course_chapters')
            && $this->enrollmentsSchemaReady()
        ) {
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
                    WHERE cc.course_id IN (' . $placeholders . ')
                    GROUP BY cc.course_id
                 ) lessons ON lessons.course_id = c.id
                 LEFT JOIN (
                    SELECT e.course_id, COUNT(DISTINCT e.user_id) AS student_count
                    FROM enrollments e
                    WHERE e.status = "active"
                      AND e.course_id IN (' . $placeholders . ')
                    GROUP BY e.course_id
                 ) students ON students.course_id = c.id
                 LEFT JOIN (
                    SELECT lc.course_id, COUNT(*) AS done_count
                    FROM lesson_completions lc
                    WHERE lc.course_id IN (' . $placeholders . ')
                    GROUP BY lc.course_id
                 ) done ON done.course_id = c.id
                 WHERE c.id IN (' . $placeholders . ')'
            );
            $stmt->execute([...$ids, ...$ids, ...$ids, ...$ids]);
            foreach ($stmt->fetchAll() ?: [] as $row) {
                $courseId = (int) ($row['course_id'] ?? 0);
                if (!isset($stats[$courseId])) {
                    continue;
                }
                $lessonCount = (int) ($row['lesson_count'] ?? 0);
                $studentCount = (int) ($row['student_count'] ?? 0);
                $doneCount = (int) ($row['done_count'] ?? 0);
                $possible = $lessonCount * $studentCount;
                $stats[$courseId]['completion_rate'] = $possible > 0
                    ? max(0, min(100, (int) round(($doneCount / $possible) * 100)))
                    : 0;
            }
        }

        return $stats;
    }

    /**
     * @param array<string,mixed> $filters
     * @return array{0:string,1:array<string,mixed>}
     */
    private function buildPendingWhere(array $filters): array
    {
        $clauses = ['c.status = "submitted"'];
        $params = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $clauses[] = '(c.title LIKE :q OR CONCAT_WS(" ", u.first_name, u.last_name) LIKE :q OR COALESCE(cat.name, "") LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }

        $teacher = trim((string) ($filters['teacher'] ?? ''));
        if ($teacher !== '') {
            $clauses[] = 'CONCAT_WS(" ", u.first_name, u.last_name) LIKE :teacher';
            $params[':teacher'] = '%' . $teacher . '%';
        }

        $category = trim((string) ($filters['category'] ?? ''));
        if ($category !== '') {
            $clauses[] = 'COALESCE(cat.name, "") LIKE :category';
            $params[':category'] = '%' . $category . '%';
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) === 1) {
            $clauses[] = 'DATE(c.submitted_at) >= :date_from';
            $params[':date_from'] = $dateFrom;
        }

        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) === 1) {
            $clauses[] = 'DATE(c.submitted_at) <= :date_to';
            $params[':date_to'] = $dateTo;
        }

        return [implode(' AND ', $clauses), $params];
    }

    /**
     * @param array<string,mixed> $filters
     * @return array{0:string,1:array<string,mixed>}
     */
    private function buildHistoryWhere(array $filters): array
    {
        $clauses = ['c.status IN ("approved","rejected")'];
        $params = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $clauses[] = '(c.title LIKE :q OR CONCAT_WS(" ", u.first_name, u.last_name) LIKE :q OR COALESCE(cat.name, "") LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }

        $teacher = trim((string) ($filters['teacher'] ?? ''));
        if ($teacher !== '') {
            $clauses[] = 'CONCAT_WS(" ", u.first_name, u.last_name) LIKE :teacher';
            $params[':teacher'] = '%' . $teacher . '%';
        }

        $category = trim((string) ($filters['category'] ?? ''));
        if ($category !== '') {
            $clauses[] = 'COALESCE(cat.name, "") LIKE :category';
            $params[':category'] = '%' . $category . '%';
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) === 1) {
            $clauses[] = 'DATE(c.updated_at) >= :date_from';
            $params[':date_from'] = $dateFrom;
        }

        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) === 1) {
            $clauses[] = 'DATE(c.updated_at) <= :date_to';
            $params[':date_to'] = $dateTo;
        }

        return [implode(' AND ', $clauses), $params];
    }

    /**
     * @return array{
     *   chapters_count:int,
     *   lessons_count:int,
     *   videos_count:int,
     *   documents_count:int,
     *   first_video_url:string,
     *   chapters:array<int,array<string,mixed>>
     * }
     */
    public function fetchCourseOutline(int $courseId): array
    {
        $outline = [
            'chapters_count' => 0,
            'lessons_count' => 0,
            'videos_count' => 0,
            'documents_count' => 0,
            'first_video_url' => '',
            'chapters' => [],
        ];

        $countStmt = $this->pdo->prepare(
            'SELECT
                COUNT(DISTINCT ch.id) AS chapters_count,
                COUNT(cl.id) AS lessons_count,
                COALESCE(SUM(CASE WHEN cl.lesson_type = "video" THEN 1 ELSE 0 END), 0) AS videos_count,
                COALESCE(SUM(CASE WHEN cl.lesson_type = "document" THEN 1 ELSE 0 END), 0) AS documents_count
             FROM course_chapters ch
             LEFT JOIN course_lessons cl ON cl.chapter_id = ch.id
             WHERE ch.course_id = :cid'
        );
        $countStmt->execute([':cid' => $courseId]);
        $countRow = $countStmt->fetch() ?: [];
        $outline['chapters_count'] = (int) ($countRow['chapters_count'] ?? 0);
        $outline['lessons_count'] = (int) ($countRow['lessons_count'] ?? 0);
        $outline['videos_count'] = (int) ($countRow['videos_count'] ?? 0);
        $outline['documents_count'] = (int) ($countRow['documents_count'] ?? 0);

        $videoStmt = $this->pdo->prepare(
            'SELECT cl.youtube_url
             FROM course_chapters ch
             JOIN course_lessons cl ON cl.chapter_id = ch.id
             WHERE ch.course_id = :cid
               AND cl.lesson_type = "video"
               AND cl.youtube_url IS NOT NULL
               AND cl.youtube_url <> ""
             ORDER BY ch.position ASC, cl.position ASC, cl.id ASC
             LIMIT 1'
        );
        $videoStmt->execute([':cid' => $courseId]);
        $videoRow = $videoStmt->fetch() ?: [];
        $outline['first_video_url'] = trim((string) ($videoRow['youtube_url'] ?? ''));

        $chaptersStmt = $this->pdo->prepare(
            'SELECT
                ch.id,
                ch.title,
                ch.position,
                COUNT(cl.id) AS lessons_count,
                COALESCE(SUM(CASE WHEN cl.lesson_type = "video" THEN 1 ELSE 0 END), 0) AS videos_count,
                COALESCE(SUM(CASE WHEN cl.lesson_type = "document" THEN 1 ELSE 0 END), 0) AS documents_count
             FROM course_chapters ch
             LEFT JOIN course_lessons cl ON cl.chapter_id = ch.id
             WHERE ch.course_id = :cid
             GROUP BY ch.id, ch.title, ch.position
             ORDER BY ch.position ASC, ch.id ASC
             LIMIT 8'
        );
        $chaptersStmt->execute([':cid' => $courseId]);
        $outline['chapters'] = $chaptersStmt->fetchAll() ?: [];

        return $outline;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchCourseRejectionHistory(int $courseId, int $limit = 5): array
    {
        $limit = max(1, $limit);
        $stmt = $this->pdo->prepare(
            'SELECT
                al.action,
                al.created_at,
                COALESCE(JSON_UNQUOTE(JSON_EXTRACT(al.meta_json, "$.reason")), "") AS reason,
                u.first_name,
                u.last_name
             FROM audit_logs al
             LEFT JOIN users u ON u.id = al.user_id
             WHERE al.target_type = "course"
               AND al.target_id = :cid
               AND al.action IN ("course_reject","course_correction_request")
             ORDER BY al.created_at DESC
             LIMIT ' . $limit
        );
        $stmt->execute([':cid' => $courseId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return array<string,array{label:string,count:int}>
     */
    private function buildLastSixMonthBuckets(): array
    {
        $start = (new \DateTimeImmutable('first day of this month 00:00:00'))->modify('-5 months');
        $months = [];
        for ($i = 0; $i < 6; $i++) {
            $month = $start->modify('+' . $i . ' months');
            $months[$month->format('Y-m')] = [
                'label' => $month->format('m/Y'),
                'count' => 0,
            ];
        }

        return $months;
    }

    private function courseSalesSchemaReady(): bool
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

    private function enrollmentsSchemaReady(): bool
    {
        return $this->tableExistsSafe('enrollments')
            && $this->columnExistsSafe('enrollments', 'course_id')
            && $this->columnExistsSafe('enrollments', 'user_id')
            && $this->columnExistsSafe('enrollments', 'status');
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
}
