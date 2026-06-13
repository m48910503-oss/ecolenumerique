<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class TeacherCoursesModel extends Model
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

    public function findCourseById(int $courseId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM courses WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([':id' => $courseId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findCourseByIdIncludingArchived(int $courseId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM courses WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $courseId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function countEnrollments(int $courseId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM enrollments WHERE course_id = :cid');
        $stmt->execute([':cid' => $courseId]);
        return (int) $stmt->fetchColumn();
    }

    public function archiveCourse(int $courseId): void
    {
        $stmt = $this->pdo->prepare('UPDATE courses SET deleted_at = NOW(), updated_at = NOW() WHERE id = :id');
        $stmt->execute([':id' => $courseId]);
    }

    public function softDeleteCourse(int $courseId): void
    {
        $this->archiveCourse($courseId);
    }

    public function restoreCourse(int $courseId): void
    {
        $stmt = $this->pdo->prepare('UPDATE courses SET deleted_at = NULL, updated_at = NOW() WHERE id = :id');
        $stmt->execute([':id' => $courseId]);
    }

    public function markCourseAsDraft(int $courseId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE courses SET status = :status, submitted_at = NULL, updated_at = NOW() WHERE id = :id'
        );
        $stmt->execute([
            ':status' => 'draft',
            ':id' => $courseId,
        ]);
    }

    public function markCourseAsSubmitted(int $courseId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE courses SET status = :status, submitted_at = NOW(), updated_at = NOW() WHERE id = :id'
        );
        $stmt->execute([
            ':status' => 'submitted',
            ':id' => $courseId,
        ]);
    }

    public function insertAudit(?int $userId, string $action, string $targetType, int $targetId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO audit_logs (user_id, action, target_type, target_id, created_at)
             VALUES (:uid, :action, :type, :tid, NOW())'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':action' => $action,
            ':type' => $targetType,
            ':tid' => $targetId,
        ]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function listCoursesForAdmin(): array
    {
        return $this->listCourses(null, true, [
            'status' => 'all',
            'sort' => 'created_desc',
        ], 0, 5000);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function listCoursesForTeacher(int $teacherId): array
    {
        return $this->listCourses($teacherId, false, [
            'status' => 'all',
            'sort' => 'created_desc',
        ], 0, 5000);
    }

    public function countCourses(?int $teacherId, bool $isAdmin, array $filters): int
    {
        $params = [];
        $whereSql = $this->buildListingWhere($teacherId, $isAdmin, $filters, $params, false);
        $sql = 'SELECT COUNT(*)
                FROM courses c' . $whereSql;
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    public function listCourses(
        ?int $teacherId,
        bool $isAdmin,
        array $filters,
        int $offset,
        int $limit
    ): array {
        $params = [];
        $whereSql = $this->buildListingWhere($teacherId, $isAdmin, $filters, $params, false);
        $sortSql = $this->sortSqlFromKey((string) ($filters['sort'] ?? 'updated_desc'));
        $offset = max(0, $offset);
        $limit = max(1, $limit);

        $sql = 'SELECT c.*, u.first_name, u.last_name,
                       CASE
                           WHEN c.deleted_at IS NOT NULL THEN "archived"
                           ELSE c.status
                       END AS workflow_status
                FROM courses c
                LEFT JOIN users u ON u.id = c.teacher_id'
                . $whereSql
                . ' ORDER BY ' . $sortSql
                . ' LIMIT ' . $limit
                . ' OFFSET ' . $offset;

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<string,int>
     */
    public function countCoursesByWorkflowStatus(?int $teacherId, bool $isAdmin, array $filters = []): array
    {
        $counts = [
            'draft' => 0,
            'submitted' => 0,
            'approved' => 0,
            'rejected' => 0,
            'archived' => 0,
        ];

        $params = [];
        $whereSql = $this->buildListingWhere($teacherId, $isAdmin, $filters, $params, true);
        $sql = 'SELECT CASE
                           WHEN c.deleted_at IS NOT NULL THEN "archived"
                           ELSE c.status
                       END AS workflow_status,
                       COUNT(*) AS total
                FROM courses c'
                . $whereSql
                . ' GROUP BY workflow_status';
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        foreach ($stmt->fetchAll() ?: [] as $row) {
            $status = (string) ($row['workflow_status'] ?? '');
            if (array_key_exists($status, $counts)) {
                $counts[$status] = (int) ($row['total'] ?? 0);
            }
        }

        return $counts;
    }

    /**
     * @return array{total:int,draft:int,published:int,students:int,revenue_xof:int}
     */
    public function summarizePortfolio(?int $teacherId, bool $isAdmin): array
    {
        $summary = [
            'total' => 0,
            'draft' => 0,
            'published' => 0,
            'students' => 0,
            'revenue_xof' => 0,
        ];

        $courseWhere = '';
        $courseParams = [];
        if (!$isAdmin) {
            $courseWhere = ' WHERE c.teacher_id = :teacher_id';
            $courseParams[':teacher_id'] = (int) ($teacherId ?? 0);
        }

        $stmt = $this->pdo->prepare(
            'SELECT CASE
                        WHEN c.deleted_at IS NOT NULL THEN "archived"
                        ELSE c.status
                    END AS workflow_status,
                    COUNT(*) AS total
             FROM courses c'
            . $courseWhere
            . ' GROUP BY workflow_status'
        );
        $stmt->execute($courseParams);
        foreach ($stmt->fetchAll() ?: [] as $row) {
            $status = (string) ($row['workflow_status'] ?? '');
            $total = (int) ($row['total'] ?? 0);
            $summary['total'] += $total;
            if ($status === 'draft') {
                $summary['draft'] = $total;
            } elseif ($status === 'approved') {
                $summary['published'] = $total;
            }
        }

        $activeWhere = ' WHERE c.deleted_at IS NULL';
        $activeParams = [];
        if (!$isAdmin) {
            $activeWhere .= ' AND c.teacher_id = :teacher_id';
            $activeParams[':teacher_id'] = (int) ($teacherId ?? 0);
        }

        if ($this->tableExists('enrollments') && $this->columnExists('enrollments', 'user_id')) {
            $studentStmt = $this->pdo->prepare(
                'SELECT COUNT(DISTINCT e.user_id)
                 FROM enrollments e
                 JOIN courses c ON c.id = e.course_id'
                . $activeWhere
                . ' AND e.status = "active"'
            );
            $studentStmt->execute($activeParams);
            $summary['students'] = (int) $studentStmt->fetchColumn();
        }

        if (
            $this->tableExists('orders')
            && $this->tableExists('order_items')
            && $this->columnExists('order_items', 'unit_price_xof')
            && $this->columnExists('order_items', 'quantity')
        ) {
            $revenueStmt = $this->pdo->prepare(
                'SELECT COALESCE(SUM(oi.unit_price_xof * oi.quantity), 0)
                 FROM order_items oi
                 JOIN orders o ON o.id = oi.order_id
                 JOIN courses c ON c.id = oi.course_id'
                . $activeWhere
                . ' AND LOWER(COALESCE(o.status, "")) IN ("paid", "completed", "succeeded", "success")'
            );
            $revenueStmt->execute($activeParams);
            $summary['revenue_xof'] = (int) $revenueStmt->fetchColumn();
        }

        return $summary;
    }

    public function insertCourseVersion(array $course, string $newSlug, int $versionOf): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO courses (
                teacher_id, category_id, title, slug, level, description,
                objectives, prerequisites, equipment, duration_hours, price_xof,
                cover_path, cover_alt, cover_variants_json, seo_title, seo_description,
                general_file_path, general_file_mime, general_file_size, status,
                version_of, created_at
            ) VALUES (
                :tid, :cat, :title, :slug, :level, :descr,
                :obj, :pre, :equip, :dur, :price,
                :cover, :cover_alt, :cover_var, :seo_t, :seo_d,
                :gpath, :gmime, :gsize, :status,
                :version_of, NOW()
            )'
        );
        $stmt->execute([
            ':tid' => $course['teacher_id'],
            ':cat' => $course['category_id'],
            ':title' => $course['title'],
            ':slug' => $newSlug,
            ':level' => $course['level'],
            ':descr' => $course['description'],
            ':obj' => $course['objectives'],
            ':pre' => $course['prerequisites'],
            ':equip' => $course['equipment'],
            ':dur' => $course['duration_hours'],
            ':price' => $course['price_xof'],
            ':cover' => $course['cover_path'],
            ':cover_alt' => $course['cover_alt'],
            ':cover_var' => $course['cover_variants_json'],
            ':seo_t' => $course['seo_title'],
            ':seo_d' => $course['seo_description'],
            ':gpath' => $course['general_file_path'],
            ':gmime' => $course['general_file_mime'],
            ':gsize' => $course['general_file_size'],
            ':status' => 'draft',
            ':version_of' => $versionOf,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findChaptersByCourseId(int $courseId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM course_chapters
             WHERE course_id = :cid
             ORDER BY position ASC, id ASC'
        );
        $stmt->execute([':cid' => $courseId]);
        return $stmt->fetchAll() ?: [];
    }

    public function insertChapter(int $courseId, string $title, ?string $description, int $position): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO course_chapters (course_id, title, description, position, created_at)
             VALUES (:cid, :title, :descr, :pos, NOW())'
        );
        $stmt->execute([
            ':cid' => $courseId,
            ':title' => $title,
            ':descr' => $description,
            ':pos' => $position,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array<int,int> $chapterIds
     * @return array<int,array<string,mixed>>
     */
    public function findLessonsByChapterIds(array $chapterIds): array
    {
        if ($chapterIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($chapterIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT *
             FROM course_lessons
             WHERE chapter_id IN ($placeholders)
             ORDER BY position ASC, id ASC"
        );
        $stmt->execute($chapterIds);
        return $stmt->fetchAll() ?: [];
    }

    public function insertLesson(int $chapterId, array $lesson): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO course_lessons (
                chapter_id, lesson_type, youtube_url, document_path, document_mime,
                document_size, title, description, position, duration_estimate_min, created_at
             ) VALUES (
                :cid, :lt, :yt, :doc, :mime,
                :size, :title, :descr, :pos, :dur, NOW()
             )'
        );
        $stmt->execute([
            ':cid' => $chapterId,
            ':lt' => $lesson['lesson_type'],
            ':yt' => $lesson['youtube_url'],
            ':doc' => $lesson['document_path'],
            ':mime' => $lesson['document_mime'],
            ':size' => $lesson['document_size'],
            ':title' => $lesson['title'],
            ':descr' => $lesson['description'],
            ':pos' => $lesson['position'],
            ':dur' => $lesson['duration_estimate_min'],
        ]);
    }

    /**
     * @param array<int,int> $chapterIds
     * @return array<int,array<string,mixed>>
     */
    public function findChapterDocumentsByChapterIds(array $chapterIds): array
    {
        if ($chapterIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($chapterIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT *
             FROM chapter_documents
             WHERE chapter_id IN ($placeholders)
             ORDER BY position ASC, id ASC"
        );
        $stmt->execute($chapterIds);
        return $stmt->fetchAll() ?: [];
    }

    public function insertChapterDocument(int $chapterId, array $doc): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO chapter_documents (chapter_id, title, type, file_path, mime, size, position, created_at)
             VALUES (:cid, :title, :type, :path, :mime, :size, :pos, NOW())'
        );
        $stmt->execute([
            ':cid' => $chapterId,
            ':title' => $doc['title'],
            ':type' => $doc['type'],
            ':path' => $doc['file_path'],
            ':mime' => $doc['mime'],
            ':size' => $doc['size'],
            ':pos' => $doc['position'],
        ]);
    }

    /**
     * @return array{chapters_cnt:int,lessons_cnt:int,meetings_cnt:int,exams_cnt:int,adv_exams_cnt:int}
     */
    public function findSubmissionCounts(int $courseId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                (SELECT COUNT(*) FROM course_chapters cc WHERE cc.course_id = :cid1) AS chapters_cnt,
                (SELECT COUNT(*) FROM course_lessons cl JOIN course_chapters cc2 ON cc2.id = cl.chapter_id WHERE cc2.course_id = :cid2) AS lessons_cnt,
                (SELECT COUNT(*) FROM course_meetings cm WHERE cm.course_id = :cid3 AND cm.student_id IS NULL) AS meetings_cnt,
                (SELECT COUNT(*) FROM chapter_documents cd JOIN course_chapters cc3 ON cc3.id = cd.chapter_id WHERE cc3.course_id = :cid4 AND cd.type = \'exam\') AS exams_cnt,
                (SELECT COUNT(*) FROM exam_sessions es WHERE es.course_id = :cid5) AS adv_exams_cnt'
        );
        $stmt->execute([
            ':cid1' => $courseId,
            ':cid2' => $courseId,
            ':cid3' => $courseId,
            ':cid4' => $courseId,
            ':cid5' => $courseId,
        ]);
        $row = $stmt->fetch() ?: [];
        return [
            'chapters_cnt' => (int) ($row['chapters_cnt'] ?? 0),
            'lessons_cnt' => (int) ($row['lessons_cnt'] ?? 0),
            'meetings_cnt' => (int) ($row['meetings_cnt'] ?? 0),
            'exams_cnt' => (int) ($row['exams_cnt'] ?? 0),
            'adv_exams_cnt' => (int) ($row['adv_exams_cnt'] ?? 0),
        ];
    }

    /**
     * @param array<int,int> $courseIds
     * @return array<int,array{chapters_cnt:int,lessons_cnt:int,meetings_cnt:int,exams_cnt:int,adv_exams_cnt:int}>
     */
    public function findSubmissionCountsByCourseIds(array $courseIds): array
    {
        if ($courseIds === []) {
            return [];
        }

        $cleanIds = array_values(array_unique(array_filter($courseIds, static fn ($id): bool => (int) $id > 0)));
        if ($cleanIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
        $sql = "SELECT c.id AS course_id,
                       (SELECT COUNT(*) FROM course_chapters cc WHERE cc.course_id = c.id) AS chapters_cnt,
                       (SELECT COUNT(*)
                          FROM course_lessons cl
                          JOIN course_chapters cc2 ON cc2.id = cl.chapter_id
                         WHERE cc2.course_id = c.id) AS lessons_cnt,
                       (SELECT COUNT(*) FROM course_meetings cm WHERE cm.course_id = c.id AND cm.student_id IS NULL) AS meetings_cnt,
                       (SELECT COUNT(*)
                          FROM chapter_documents cd
                          JOIN course_chapters cc3 ON cc3.id = cd.chapter_id
                         WHERE cc3.course_id = c.id AND cd.type = 'exam') AS exams_cnt,
                       (SELECT COUNT(*) FROM exam_sessions es WHERE es.course_id = c.id) AS adv_exams_cnt
                FROM courses c
                WHERE c.id IN ($placeholders)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($cleanIds);
        $rows = $stmt->fetchAll() ?: [];

        $map = [];
        foreach ($rows as $row) {
            $courseId = (int) ($row['course_id'] ?? 0);
            if ($courseId < 1) {
                continue;
            }
            $map[$courseId] = [
                'chapters_cnt' => (int) ($row['chapters_cnt'] ?? 0),
                'lessons_cnt' => (int) ($row['lessons_cnt'] ?? 0),
                'meetings_cnt' => (int) ($row['meetings_cnt'] ?? 0),
                'exams_cnt' => (int) ($row['exams_cnt'] ?? 0),
                'adv_exams_cnt' => (int) ($row['adv_exams_cnt'] ?? 0),
            ];
        }

        return $map;
    }

    /**
     * @param array<int,int> $courseIds
     * @return array<int,array{views:int,enrollments:int,paid_sales:int,paid_amount_xof:int}>
     */
    public function findCourseStatsByCourseIds(array $courseIds): array
    {
        if ($courseIds === []) {
            return [];
        }
        $cleanIds = array_values(array_unique(array_filter($courseIds, static fn ($id): bool => (int) $id > 0)));
        if ($cleanIds === []) {
            return [];
        }

        $stats = [];
        foreach ($cleanIds as $courseId) {
            $stats[$courseId] = [
                'views' => 0,
                'enrollments' => 0,
                'paid_sales' => 0,
                'paid_amount_xof' => 0,
            ];
        }

        $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));

        if ($this->tableExists('course_views')) {
            $stmtViews = $this->pdo->prepare(
                "SELECT course_id, COUNT(*) AS cnt
                 FROM course_views
                 WHERE course_id IN ($placeholders)
                 GROUP BY course_id"
            );
            $stmtViews->execute($cleanIds);
            foreach ($stmtViews->fetchAll() ?: [] as $row) {
                $courseId = (int) ($row['course_id'] ?? 0);
                if (isset($stats[$courseId])) {
                    $stats[$courseId]['views'] = (int) ($row['cnt'] ?? 0);
                }
            }
        }

        $stmtEnroll = $this->pdo->prepare(
            "SELECT course_id, COUNT(*) AS cnt
             FROM enrollments
             WHERE status = 'active'
               AND course_id IN ($placeholders)
             GROUP BY course_id"
        );
        $stmtEnroll->execute($cleanIds);
        foreach ($stmtEnroll->fetchAll() ?: [] as $row) {
            $courseId = (int) ($row['course_id'] ?? 0);
            if (isset($stats[$courseId])) {
                $stats[$courseId]['enrollments'] = (int) ($row['cnt'] ?? 0);
            }
        }

        $stmtSales = $this->pdo->prepare(
            "SELECT oi.course_id,
                    COUNT(*) AS paid_sales,
                    COALESCE(SUM(oi.unit_price_xof * oi.quantity), 0) AS paid_amount_xof
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             WHERE o.status = 'paid'
               AND oi.course_id IN ($placeholders)
             GROUP BY oi.course_id"
        );
        $stmtSales->execute($cleanIds);
        foreach ($stmtSales->fetchAll() ?: [] as $row) {
            $courseId = (int) ($row['course_id'] ?? 0);
            if (isset($stats[$courseId])) {
                $stats[$courseId]['paid_sales'] = (int) ($row['paid_sales'] ?? 0);
                $stats[$courseId]['paid_amount_xof'] = (int) ($row['paid_amount_xof'] ?? 0);
            }
        }

        return $stats;
    }

    /**
     * @return array<int,int>
     */
    public function findAdminIds(): array
    {
        $rows = $this->pdo
            ->query('SELECT id FROM users WHERE roles_json LIKE \'%"admin"%\'')
            ->fetchAll() ?: [];
        $ids = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        return $ids;
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
     * @param array<string,mixed> $filters
     * @param array<string,mixed> $params
     */
    private function buildListingWhere(
        ?int $teacherId,
        bool $isAdmin,
        array $filters,
        array &$params,
        bool $ignoreStatus
    ): string {
        $clauses = [];

        if (!$isAdmin) {
            $clauses[] = 'c.teacher_id = :teacher_id';
            $params[':teacher_id'] = (int) ($teacherId ?? 0);
        }

        if (!$ignoreStatus) {
            $status = (string) ($filters['status'] ?? 'all');
            if ($status === 'archived') {
                $clauses[] = 'c.deleted_at IS NOT NULL';
            } else {
                $clauses[] = 'c.deleted_at IS NULL';
                if (in_array($status, ['draft', 'submitted', 'approved', 'rejected'], true)) {
                    $clauses[] = 'c.status = :status_filter';
                    $params[':status_filter'] = $status;
                }
            }
        }

        $level = (string) ($filters['level'] ?? 'all');
        if (in_array($level, ['beginner', 'intermediate', 'advanced'], true)) {
            $clauses[] = 'c.level = :level_filter';
            $params[':level_filter'] = $level;
        }

        $query = trim((string) ($filters['q'] ?? ''));
        if ($query !== '') {
            $clauses[] = '(c.title LIKE :q_like_title OR c.description LIKE :q_like_description)';
            $params[':q_like_title'] = '%' . $query . '%';
            $params[':q_like_description'] = '%' . $query . '%';
        }

        if (array_key_exists('price_min', $filters) && $filters['price_min'] !== null) {
            $clauses[] = 'c.price_xof >= :price_min';
            $params[':price_min'] = (int) $filters['price_min'];
        }
        if (array_key_exists('price_max', $filters) && $filters['price_max'] !== null) {
            $clauses[] = 'c.price_xof <= :price_max';
            $params[':price_max'] = (int) $filters['price_max'];
        }

        $periodStartAt = trim((string) ($filters['period_start_at'] ?? ''));
        $periodEndAt = trim((string) ($filters['period_end_at'] ?? ''));
        if ($periodStartAt !== '') {
            $clauses[] = 'COALESCE(c.updated_at, c.created_at) >= :period_start_at';
            $params[':period_start_at'] = $periodStartAt;
        }
        if ($periodEndAt !== '') {
            $clauses[] = 'COALESCE(c.updated_at, c.created_at) < :period_end_at';
            $params[':period_end_at'] = $periodEndAt;
        }

        if ($clauses === []) {
            return '';
        }

        return ' WHERE ' . implode(' AND ', $clauses);
    }

    private function sortSqlFromKey(string $sortKey): string
    {
        $statusOrder = 'CASE
            WHEN c.deleted_at IS NOT NULL THEN 5
            WHEN c.status = "draft" THEN 1
            WHEN c.status = "submitted" THEN 2
            WHEN c.status = "approved" THEN 3
            WHEN c.status = "rejected" THEN 4
            ELSE 9
        END';

        return match ($sortKey) {
            'created_asc' => 'c.created_at ASC, c.id ASC',
            'created_desc' => 'c.created_at DESC, c.id DESC',
            'updated_asc' => 'c.updated_at ASC, c.id ASC',
            'revenue_desc' => '(SELECT COALESCE(SUM(oi.unit_price_xof * oi.quantity), 0)
                                FROM order_items oi
                                JOIN orders o ON o.id = oi.order_id
                                WHERE oi.course_id = c.id
                                  AND LOWER(COALESCE(o.status, "")) IN ("paid", "completed", "succeeded", "success")) DESC,
                               c.updated_at DESC,
                               c.id DESC',
            'students_desc' => '(SELECT COUNT(DISTINCT e.user_id)
                                 FROM enrollments e
                                 WHERE e.course_id = c.id
                                   AND e.status = "active") DESC,
                                c.updated_at DESC,
                                c.id DESC',
            'price_asc' => 'c.price_xof ASC, c.created_at DESC',
            'price_desc' => 'c.price_xof DESC, c.created_at DESC',
            'status_asc' => $statusOrder . ' ASC, c.updated_at DESC',
            'status_desc' => $statusOrder . ' DESC, c.updated_at DESC',
            'title_asc' => 'c.title ASC, c.updated_at DESC',
            'title_desc' => 'c.title DESC, c.updated_at DESC',
            default => 'c.updated_at DESC, c.id DESC',
        };
    }
}
