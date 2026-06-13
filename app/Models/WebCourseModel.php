<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class WebCourseModel extends Model
{
    private ?bool $ratingsModerationReady = null;

    public function findApprovedCourse(?string $slug, int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT c.*, u.first_name, u.last_name, u.email, u.avatar_url AS teacher_avatar_url, u.id AS teacher_user_id, cat.name AS category_name
             FROM courses c
             LEFT JOIN users u ON u.id = c.teacher_id
             LEFT JOIN categories cat ON cat.id = c.category_id
             WHERE c.status = 'approved'
               AND c.deleted_at IS NULL
               AND (c.slug = :slug OR c.id = :id)
             LIMIT 1"
        );
        $stmt->execute([
            ':slug' => $slug,
            ':id' => $id,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchApprovedCoursesByTeacherId(int $teacherId, int $excludeCourseId, int $limit = 3): array
    {
        return $this->fetchApprovedCoursesByScope('c.teacher_id', $teacherId, $excludeCourseId, $limit);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchApprovedCoursesByCategoryId(int $categoryId, int $excludeCourseId, int $limit = 3): array
    {
        return $this->fetchApprovedCoursesByScope('c.category_id', $categoryId, $excludeCourseId, $limit);
    }

    public function findCourseBySlugOrId(?string $slug, int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT c.*
             FROM courses c
             WHERE c.slug = :slug OR c.id = :id
             LIMIT 1"
        );
        $stmt->execute([
            ':slug' => $slug,
            ':id' => $id,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findApprovedCourseById(int $courseId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, title
             FROM courses
             WHERE id = :cid
               AND status = 'approved'
               AND deleted_at IS NULL
             LIMIT 1"
        );
        $stmt->execute([':cid' => $courseId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findFirstVideoLessonForCourse(int $courseId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                l.id AS lesson_id,
                l.title AS lesson_title,
                l.youtube_url
             FROM course_chapters ch
             JOIN course_lessons l ON l.chapter_id = ch.id
             JOIN courses c ON c.id = ch.course_id
             WHERE ch.course_id = :cid
               AND c.status = "approved"
               AND c.deleted_at IS NULL
               AND l.lesson_type = "video"
               AND l.youtube_url IS NOT NULL
               AND l.youtube_url <> ""
             ORDER BY ch.position ASC, l.position ASC, l.id ASC
             LIMIT 1'
        );
        $stmt->execute([':cid' => $courseId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function fetchChapters(int $courseId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, title, description, position FROM course_chapters WHERE course_id = :cid ORDER BY position ASC');
        $stmt->execute([':cid' => $courseId]);
        return $stmt->fetchAll() ?: [];
    }

    public function fetchLessonsByChapterIds(array $chapterIds): array
    {
        if ($chapterIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($chapterIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT id, title, lesson_type, position, duration_estimate_min, chapter_id, youtube_url, document_path
             FROM course_lessons
             WHERE chapter_id IN ($placeholders)
             ORDER BY chapter_id, position"
        );
        $stmt->execute($chapterIds);
        return $stmt->fetchAll() ?: [];
    }

    public function fetchDocumentsByChapterIds(array $chapterIds): array
    {
        if ($chapterIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($chapterIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT id, title, type, position, chapter_id, file_path, mime
             FROM chapter_documents
             WHERE chapter_id IN ($placeholders)
             ORDER BY chapter_id, position"
        );
        $stmt->execute($chapterIds);
        return $stmt->fetchAll() ?: [];
    }

    public function fetchMeetingTemplatesByChapterIds(array $chapterIds, bool $withDuration): array
    {
        if ($chapterIds === []) {
            return [];
        }
        $fields = $withDuration
            ? 'id, title, mode, chapter_id, duration_min'
            : 'id, title, mode, chapter_id, 60 AS duration_min';
        $placeholders = implode(',', array_fill(0, count($chapterIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT {$fields}
             FROM course_meetings
             WHERE chapter_id IN ($placeholders) AND student_id IS NULL
             ORDER BY id"
        );
        $stmt->execute($chapterIds);
        return $stmt->fetchAll() ?: [];
    }

    public function fetchExamSessions(int $courseId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, title, type, duration_min, meeting_mode FROM exam_sessions WHERE course_id = :cid ORDER BY id');
        $stmt->execute([':cid' => $courseId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function fetchApprovedCoursesByScope(string $scopeColumn, int $scopeId, int $excludeCourseId, int $limit): array
    {
        if ($scopeId < 1) {
            return [];
        }

        $limit = max(1, min(12, $limit));
        $scopeColumn = in_array($scopeColumn, ['c.teacher_id', 'c.category_id'], true) ? $scopeColumn : 'c.teacher_id';

        $sql = "
            SELECT
                c.id,
                c.slug,
                c.title,
                c.description,
                c.cover_path,
                c.cover_alt,
                c.price_xof,
                c.level,
                c.created_at,
                c.updated_at,
                c.teacher_id,
                c.category_id,
                u.first_name,
                u.last_name,
                u.avatar_url AS teacher_avatar_url,
                cat.name AS category_name,
                COALESCE(r.avg_rating, 0) AS avg_rating,
                COALESCE(r.rating_count, 0) AS rating_count,
                COALESCE(e.enrolled_count, 0) AS enrolled_count,
                COALESCE(d.total_min, 0) AS total_min
            FROM courses c
            LEFT JOIN users u ON u.id = c.teacher_id
            LEFT JOIN categories cat ON cat.id = c.category_id
            LEFT JOIN (
                SELECT course_id, AVG(rating) AS avg_rating, COUNT(*) AS rating_count
                FROM course_ratings
                GROUP BY course_id
            ) r ON r.course_id = c.id
            LEFT JOIN (
                SELECT course_id, COUNT(*) AS enrolled_count
                FROM enrollments
                WHERE status = 'active'
                GROUP BY course_id
            ) e ON e.course_id = c.id
            LEFT JOIN (
                SELECT cc.course_id, SUM(cl.duration_estimate_min) AS total_min
                FROM course_chapters cc
                JOIN course_lessons cl ON cl.chapter_id = cc.id
                GROUP BY cc.course_id
            ) d ON d.course_id = c.id
            WHERE c.status = 'approved'
              AND c.deleted_at IS NULL
              AND c.id <> :exclude_id
              AND {$scopeColumn} = :scope_id
            ORDER BY COALESCE(r.rating_count, 0) DESC, COALESCE(e.enrolled_count, 0) DESC, c.created_at DESC, c.id DESC
            LIMIT :limit
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':exclude_id', $excludeCourseId, \PDO::PARAM_INT);
        $stmt->bindValue(':scope_id', $scopeId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }

    public function fetchCourseRatingStats(int $courseId): array
    {
        $sql = 'SELECT AVG(rating) AS avg_rating, COUNT(*) AS cnt FROM course_ratings WHERE course_id = :cid';
        if ($this->ratingsModerationReady()) {
            $sql .= ' AND is_hidden_by_teacher = 0';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':cid' => $courseId]);
        $row = $stmt->fetch();
        return $row ?: ['avg_rating' => null, 'cnt' => 0];
    }

    public function fetchCourseReviews(int $courseId, int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));
        $hiddenWhere = $this->ratingsModerationReady() ? ' AND cr.is_hidden_by_teacher = 0' : '';
        $stmt = $this->pdo->prepare(
            'SELECT cr.rating, cr.comment, cr.created_at, u.first_name, u.last_name
             FROM course_ratings cr
             JOIN users u ON u.id = cr.user_id
             WHERE cr.course_id = :cid' . $hiddenWhere . '
             ORDER BY cr.created_at DESC
             LIMIT ' . $limit
        );
        $stmt->execute([':cid' => $courseId]);
        return $stmt->fetchAll() ?: [];
    }

    public function hasActiveEnrollment(int $userId, int $courseId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM enrollments WHERE user_id = :uid AND course_id = :cid AND status = "active" LIMIT 1');
        $stmt->execute([
            ':uid' => $userId,
            ':cid' => $courseId,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    public function fetchDoneLessonIds(int $userId, int $courseId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT DISTINCT lesson_id
             FROM lesson_completions
             WHERE user_id = :uid
               AND course_id = :cid
             ORDER BY lesson_id ASC'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':cid' => $courseId,
        ]);
        return array_map('intval', array_column($stmt->fetchAll() ?: [], 'lesson_id'));
    }

    public function findMeetingTemplateForRequest(int $templateId, int $courseId, bool $withDuration): ?array
    {
        $fields = $withDuration
            ? 'id, course_id, chapter_id, title, mode, duration_min'
            : 'id, course_id, chapter_id, title, mode, 60 AS duration_min';
        $stmt = $this->pdo->prepare("SELECT {$fields} FROM course_meetings WHERE id = :id AND course_id = :cid AND student_id IS NULL LIMIT 1");
        $stmt->execute([
            ':id' => $templateId,
            ':cid' => $courseId,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findMeetingTemplateForCancel(int $templateId, int $courseId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, chapter_id, title, mode FROM course_meetings WHERE id = :id AND course_id = :cid AND student_id IS NULL LIMIT 1');
        $stmt->execute([
            ':id' => $templateId,
            ':cid' => $courseId,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function hasConfirmedMeeting(int $courseId, mixed $chapterId, string $title, string $mode, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM course_meetings
             WHERE course_id = :cid AND chapter_id <=> :chid AND title = :title AND mode = :mode
               AND student_id = :uid AND status = "confirmed"
             LIMIT 1'
        );
        $stmt->execute([
            ':cid' => $courseId,
            ':chid' => $chapterId,
            ':title' => $title,
            ':mode' => $mode,
            ':uid' => $userId,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    public function cancelPendingMeetingRequests(int $courseId, mixed $chapterId, string $title, string $mode, int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE course_meetings
             SET status = "cancelled", updated_at = NOW()
             WHERE course_id = :cid AND chapter_id <=> :chid AND title = :title AND mode = :mode
               AND student_id = :uid AND status = "pending"'
        );
        $stmt->execute([
            ':cid' => $courseId,
            ':chid' => $chapterId,
            ':title' => $title,
            ':mode' => $mode,
            ':uid' => $userId,
        ]);
    }

    public function insertPendingMeetingSlotWithDuration(
        int $courseId,
        mixed $chapterId,
        string $title,
        string $mode,
        int $durationMin,
        int $userId,
        string $startAt,
        string $endAt
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO course_meetings (course_id, chapter_id, title, mode, duration_min, status, student_id, start_at, end_at, created_at)
             VALUES (:cid, :chid, :title, :mode, :duration_min, "pending", :uid, :start_at, :end_at, NOW())'
        );
        $stmt->execute([
            ':cid' => $courseId,
            ':chid' => $chapterId,
            ':title' => $title,
            ':mode' => $mode,
            ':duration_min' => $durationMin,
            ':uid' => $userId,
            ':start_at' => $startAt,
            ':end_at' => $endAt,
        ]);
    }

    public function insertPendingMeetingSlot(
        int $courseId,
        mixed $chapterId,
        string $title,
        string $mode,
        int $userId,
        string $startAt,
        string $endAt
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO course_meetings (course_id, chapter_id, title, mode, status, student_id, start_at, end_at, created_at)
             VALUES (:cid, :chid, :title, :mode, "pending", :uid, :start_at, :end_at, NOW())'
        );
        $stmt->execute([
            ':cid' => $courseId,
            ':chid' => $chapterId,
            ':title' => $title,
            ':mode' => $mode,
            ':uid' => $userId,
            ':start_at' => $startAt,
            ':end_at' => $endAt,
        ]);
    }

    public function insertNotification(int $userId, string $title, string $body, string $url): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO notifications (user_id, title, body, url, created_at) VALUES (:uid, :title, :body, :url, NOW())');
        $stmt->execute([
            ':uid' => $userId,
            ':title' => $title,
            ':body' => $body,
            ':url' => $url,
        ]);
    }

    public function fetchStudentMeetings(int $courseId, int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, chapter_id, title, mode, status, start_at, end_at, meeting_link, meeting_place
             FROM course_meetings
             WHERE course_id = :cid AND student_id = :uid
             ORDER BY created_at DESC, id DESC'
        );
        $stmt->execute([
            ':cid' => $courseId,
            ':uid' => $userId,
        ]);
        return $stmt->fetchAll() ?: [];
    }

    public function fetchAdminIds(): array
    {
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE roles_json LIKE :pattern');
        $stmt->execute([':pattern' => '%"admin"%']);
        $rows = $stmt->fetchAll();
        if (!$rows) {
            return [];
        }
        return array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $rows);
    }

    public function fetchUserCourseRating(int $courseId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT rating, comment FROM course_ratings WHERE course_id = :cid AND user_id = :uid LIMIT 1');
        $stmt->execute([
            ':cid' => $courseId,
            ':uid' => $userId,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function ratingsModerationReady(): bool
    {
        if (is_bool($this->ratingsModerationReady)) {
            return $this->ratingsModerationReady;
        }
        $this->ratingsModerationReady = $this->columnExists('course_ratings', 'is_hidden_by_teacher');
        return $this->ratingsModerationReady;
    }

    public function countActiveBuyers(int $courseId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM enrollments WHERE course_id = :cid AND status = "active"');
        $stmt->execute([':cid' => $courseId]);
        return (int) $stmt->fetchColumn();
    }

    public function registerUniqueCourseView(int $courseId, ?int $userId, string $viewerKey): void
    {
        $uid = ($userId !== null && $userId > 0) ? $userId : null;
        $stmt = $this->pdo->prepare(
            'INSERT INTO course_views (course_id, user_id, viewer_key, created_at, last_viewed_at)
             VALUES (:cid, :uid_insert, :viewer_key, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                 last_viewed_at = NOW(),
                 user_id = IFNULL(course_views.user_id, :uid_update)'
        );
        $stmt->bindValue(':cid', $courseId, \PDO::PARAM_INT);
        if ($uid === null) {
            $stmt->bindValue(':uid_insert', null, \PDO::PARAM_NULL);
            $stmt->bindValue(':uid_update', null, \PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':uid_insert', $uid, \PDO::PARAM_INT);
            $stmt->bindValue(':uid_update', $uid, \PDO::PARAM_INT);
        }
        $stmt->bindValue(':viewer_key', $viewerKey, \PDO::PARAM_STR);
        $stmt->execute();
    }

    public function countCourseViews(int $courseId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM course_views WHERE course_id = :cid');
        $stmt->execute([':cid' => $courseId]);
        return (int) $stmt->fetchColumn();
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }
}
