<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class TeacherCourseEditModel extends Model
{
    public function findCourseById(int $courseId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM courses WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $courseId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findCategories(): array
    {
        $stmt = $this->pdo->query('SELECT id, name FROM categories WHERE is_deleted = 0 ORDER BY name');
        return $stmt->fetchAll() ?: [];
    }

    public function categoryExists(int $categoryId): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM categories WHERE id = :id AND is_deleted = 0');
        $stmt->execute([':id' => $categoryId]);
        return (bool) $stmt->fetch();
    }

    /**
     * @param array<string,mixed> $params
     */
    public function updateCourse(int $courseId, array $params): void
    {
        $set = [
            'category_id = :category_id',
            'title = :title',
            'level = :level',
            'description = :description',
            'objectives = :objectives',
            'prerequisites = :prerequisites',
            'equipment = :equipment',
            'duration_hours = :duration_hours',
            'price_xof = :price_xof',
        ];

        try {
            if ($this->columnExists('courses', 'enterprise_price_xof') && array_key_exists(':enterprise_price_xof', $params)) {
                $set[] = 'enterprise_price_xof = :enterprise_price_xof';
            }
            if ($this->columnExists('courses', 'enterprise_discount_percent') && array_key_exists(':enterprise_discount_percent', $params)) {
                $set[] = 'enterprise_discount_percent = :enterprise_discount_percent';
            }
        } catch (\Throwable $e) {
            // Legacy schema: ignore enterprise pricing fields.
        }

        $set = array_merge($set, [
            'referral_override = :referral_override',
            'referral_l1_rate = :referral_l1_rate',
            'referral_l2_rate = :referral_l2_rate',
            'referral_l2_enabled = :referral_l2_enabled',
            'general_file_path = :general_file_path',
            'general_file_mime = :general_file_mime',
            'general_file_size = :general_file_size',
            'cover_path = :cover_path',
            'updated_at = NOW()',
        ]);

        $stmt = $this->pdo->prepare(
            'UPDATE courses SET ' . implode(', ', $set) . ' WHERE id = :id'
        );
        $params[':id'] = $courseId;
        $stmt->execute($params);
    }

    public function findChapterMaxPosition(int $courseId): int
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(MAX(position), 0) AS max_pos FROM course_chapters WHERE course_id = :cid');
        $stmt->execute([':cid' => $courseId]);
        $row = $stmt->fetch();
        return (int) ($row['max_pos'] ?? 0);
    }

    public function findChapterByTitle(int $courseId, string $title): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id FROM course_chapters WHERE course_id = :cid AND title = :title LIMIT 1');
        $stmt->execute([':cid' => $courseId, ':title' => $title]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function insertChapter(int $courseId, string $title, ?string $description, int $position): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO course_chapters (course_id, title, description, position, created_at) VALUES (:cid, :title, :desc, :pos, NOW())');
        $stmt->execute([
            ':cid' => $courseId,
            ':title' => $title,
            ':desc' => $description,
            ':pos' => $position,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function findChapterByIdInCourse(int $chapterId, int $courseId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, title, description, position FROM course_chapters WHERE id = :id AND course_id = :cid LIMIT 1');
        $stmt->execute([':id' => $chapterId, ':cid' => $courseId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateChapter(int $chapterId, int $courseId, string $title, ?string $description): void
    {
        $stmt = $this->pdo->prepare('UPDATE course_chapters SET title = :title, description = :desc, updated_at = NOW() WHERE id = :id AND course_id = :cid');
        $stmt->execute([
            ':title' => $title,
            ':desc' => $description,
            ':id' => $chapterId,
            ':cid' => $courseId,
        ]);
    }

    public function deleteChapter(int $chapterId, int $courseId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM course_chapters WHERE id = :id AND course_id = :cid');
        $stmt->execute([':id' => $chapterId, ':cid' => $courseId]);
    }

    public function chapterExistsInCourse(int $chapterId, int $courseId): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM course_chapters WHERE id = :id AND course_id = :cid LIMIT 1');
        $stmt->execute([':id' => $chapterId, ':cid' => $courseId]);
        return (bool) $stmt->fetch();
    }

    public function findChapterIdsByCourseOrdered(int $courseId): array
    {
        $stmt = $this->pdo->prepare('SELECT id FROM course_chapters WHERE course_id = :cid ORDER BY position ASC, id ASC');
        $stmt->execute([':cid' => $courseId]);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: []);
    }

    public function findItemMaxPositionForChapter(string $table, int $chapterId): int
    {
        $tableName = $this->assertChapterTable($table);
        $stmt = $this->pdo->prepare("SELECT COALESCE(MAX(position), 0) AS max_pos FROM {$tableName} WHERE chapter_id = :cid");
        $stmt->execute([':cid' => $chapterId]);
        $row = $stmt->fetch();
        return (int) ($row['max_pos'] ?? 0);
    }

    public function findItemIdsByChapterOrdered(string $table, int $chapterId): array
    {
        $tableName = $this->assertChapterTable($table);
        $stmt = $this->pdo->prepare("SELECT id FROM {$tableName} WHERE chapter_id = :cid ORDER BY position ASC, id ASC");
        $stmt->execute([':cid' => $chapterId]);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: []);
    }

    public function updateChapterPositionById(int $chapterId, int $position): void
    {
        $stmt = $this->pdo->prepare('UPDATE course_chapters SET position = :pos WHERE id = :id');
        $stmt->execute([
            ':pos' => $position,
            ':id' => $chapterId,
        ]);
    }

    public function updateItemPositionById(string $table, int $itemId, int $position): void
    {
        $tableName = $this->assertChapterTable($table);
        $stmt = $this->pdo->prepare("UPDATE {$tableName} SET position = :pos WHERE id = :id");
        $stmt->execute([
            ':pos' => $position,
            ':id' => $itemId,
        ]);
    }

    public function insertLesson(
        int $chapterId,
        string $title,
        ?string $description,
        string $youtubeUrl,
        int $position,
        int $durationMin
    ): int {
        $stmt = $this->pdo->prepare('INSERT INTO course_lessons (chapter_id, lesson_type, youtube_url, document_path, document_mime, document_size, title, description, position, duration_estimate_min, created_at) VALUES (:cid, :type, :yt, :doc, :mime, :size, :title, :desc, :pos, :dur, NOW())');
        $stmt->execute([
            ':cid' => $chapterId,
            ':type' => 'video',
            ':yt' => $youtubeUrl,
            ':doc' => null,
            ':mime' => null,
            ':size' => null,
            ':title' => $title,
            ':desc' => $description,
            ':pos' => $position,
            ':dur' => $durationMin > 0 ? $durationMin : null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function findLessonByIdInCourseWithChapter(int $lessonId, int $courseId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT cl.id, cl.chapter_id AS chapter_id, cc.title AS chapter_title FROM course_lessons cl JOIN course_chapters cc ON cc.id = cl.chapter_id WHERE cl.id = :id AND cc.course_id = :cid LIMIT 1');
        $stmt->execute([':id' => $lessonId, ':cid' => $courseId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function lessonExistsInChapterCourse(int $lessonId, int $chapterId, int $courseId): bool
    {
        $stmt = $this->pdo->prepare('SELECT cl.id FROM course_lessons cl JOIN course_chapters cc ON cc.id = cl.chapter_id WHERE cl.id = :lid AND cl.chapter_id = :chid AND cc.course_id = :cid LIMIT 1');
        $stmt->execute([
            ':lid' => $lessonId,
            ':chid' => $chapterId,
            ':cid' => $courseId,
        ]);
        return (bool) $stmt->fetch();
    }

    public function updateLesson(
        int $lessonId,
        string $title,
        ?string $description,
        string $youtubeUrl,
        int $durationMin
    ): void {
        $stmt = $this->pdo->prepare('UPDATE course_lessons SET title = :title, description = :desc, youtube_url = :yt, duration_estimate_min = :dur, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':title' => $title,
            ':desc' => $description,
            ':yt' => $youtubeUrl,
            ':dur' => $durationMin > 0 ? $durationMin : null,
            ':id' => $lessonId,
        ]);
    }

    public function deleteLessonById(int $lessonId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM course_lessons WHERE id = :id');
        $stmt->execute([':id' => $lessonId]);
    }

    public function deleteLessonScopedDocuments(int $chapterId, int $lessonId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM chapter_documents WHERE chapter_id = :chid AND mime LIKE :pattern');
        $stmt->execute([
            ':chid' => $chapterId,
            ':pattern' => '%|lesson:' . $lessonId,
        ]);
    }

    public function insertDocument(
        int $chapterId,
        string $title,
        string $type,
        string $filePath,
        string $mime,
        int $size,
        int $position
    ): int {
        $stmt = $this->pdo->prepare('INSERT INTO chapter_documents (chapter_id, title, type, file_path, mime, size, position, created_at) VALUES (:cid, :title, :type, :path, :mime, :size, :pos, NOW())');
        $stmt->execute([
            ':cid' => $chapterId,
            ':title' => $title,
            ':type' => $type,
            ':path' => $filePath,
            ':mime' => $mime,
            ':size' => $size,
            ':pos' => $position,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function findDocumentByIdInCourse(int $docId, int $courseId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT cd.* FROM chapter_documents cd JOIN course_chapters cc ON cc.id = cd.chapter_id WHERE cd.id = :id AND cc.course_id = :cid LIMIT 1');
        $stmt->execute([
            ':id' => $docId,
            ':cid' => $courseId,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateDocument(
        int $docId,
        int $chapterId,
        string $title,
        string $type,
        string $filePath,
        string $mime,
        int $size,
        int $position
    ): void {
        $stmt = $this->pdo->prepare('UPDATE chapter_documents SET chapter_id = :chid, title = :title, type = :type, file_path = :path, mime = :mime, size = :size, position = :pos, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':chid' => $chapterId,
            ':title' => $title,
            ':type' => $type,
            ':path' => $filePath,
            ':mime' => $mime,
            ':size' => $size,
            ':pos' => $position,
            ':id' => $docId,
        ]);
    }

    public function deleteDocument(int $docId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM chapter_documents WHERE id = :id');
        $stmt->execute([':id' => $docId]);
    }

    public function insertMeetingTemplate(
        bool $withDuration,
        int $courseId,
        int $chapterId,
        string $title,
        string $mode,
        int $durationMin
    ): int {
        if ($withDuration) {
            $stmt = $this->pdo->prepare('INSERT INTO course_meetings (course_id, chapter_id, title, mode, duration_min, status, start_at, end_at, meeting_link, meeting_place, created_at) VALUES (:cid, :chapter_id, :title, :mode, :duration_min, :status, :start_at, :end_at, :link, :place, NOW())');
            $stmt->execute([
                ':cid' => $courseId,
                ':chapter_id' => $chapterId,
                ':title' => $title,
                ':mode' => $mode,
                ':duration_min' => $durationMin,
                ':status' => 'pending',
                ':start_at' => null,
                ':end_at' => null,
                ':link' => null,
                ':place' => null,
            ]);
        } else {
            $stmt = $this->pdo->prepare('INSERT INTO course_meetings (course_id, chapter_id, title, mode, status, start_at, end_at, meeting_link, meeting_place, created_at) VALUES (:cid, :chapter_id, :title, :mode, :status, :start_at, :end_at, :link, :place, NOW())');
            $stmt->execute([
                ':cid' => $courseId,
                ':chapter_id' => $chapterId,
                ':title' => $title,
                ':mode' => $mode,
                ':status' => 'pending',
                ':start_at' => null,
                ':end_at' => null,
                ':link' => null,
                ':place' => null,
            ]);
        }
        return (int) $this->pdo->lastInsertId();
    }

    public function findMeetingTemplateByIdInCourse(int $meetingId, int $courseId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id FROM course_meetings WHERE id = :id AND course_id = :cid AND student_id IS NULL LIMIT 1');
        $stmt->execute([
            ':id' => $meetingId,
            ':cid' => $courseId,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateMeetingTemplate(
        bool $withDuration,
        int $meetingId,
        int $courseId,
        int $chapterId,
        string $title,
        string $mode,
        int $durationMin
    ): void {
        if ($withDuration) {
            $sql = 'UPDATE course_meetings SET title = :title, mode = :mode, duration_min = :duration_min, chapter_id = :chapter_id, updated_at = NOW() WHERE id = :id AND course_id = :cid AND student_id IS NULL';
            $params = [
                ':title' => $title,
                ':mode' => $mode,
                ':duration_min' => $durationMin,
                ':chapter_id' => $chapterId,
                ':id' => $meetingId,
                ':cid' => $courseId,
            ];
        } else {
            $sql = 'UPDATE course_meetings SET title = :title, mode = :mode, chapter_id = :chapter_id, updated_at = NOW() WHERE id = :id AND course_id = :cid AND student_id IS NULL';
            $params = [
                ':title' => $title,
                ':mode' => $mode,
                ':chapter_id' => $chapterId,
                ':id' => $meetingId,
                ':cid' => $courseId,
            ];
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    public function deleteMeetingTemplate(int $meetingId, int $courseId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM course_meetings WHERE id = :id AND course_id = :cid AND student_id IS NULL');
        $stmt->execute([
            ':id' => $meetingId,
            ':cid' => $courseId,
        ]);
    }

    public function deleteMeetingsByCourseChapter(int $courseId, int $chapterId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM course_meetings WHERE course_id = :cid AND chapter_id = :chid');
        $stmt->execute([
            ':cid' => $courseId,
            ':chid' => $chapterId,
        ]);
    }

    public function setCourseDraft(int $courseId): void
    {
        $stmt = $this->pdo->prepare('UPDATE courses SET status = :s, submitted_at = NULL, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':s' => 'draft',
            ':id' => $courseId,
        ]);
    }

    public function setCourseSubmitted(int $courseId): void
    {
        $stmt = $this->pdo->prepare('UPDATE courses SET status = :s, submitted_at = NOW(), updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':s' => 'submitted',
            ':id' => $courseId,
        ]);
    }

    public function fetchSubmissionCounts(int $courseId): array
    {
        $stmt = $this->pdo->prepare('SELECT 
            (SELECT COUNT(*) FROM course_chapters cc WHERE cc.course_id = ?) AS chapters_cnt,
            (SELECT COUNT(*) FROM course_lessons cl JOIN course_chapters c2 ON c2.id = cl.chapter_id WHERE c2.course_id = ?) AS lessons_cnt,
            (SELECT COUNT(*) FROM course_meetings cm WHERE cm.course_id = ? AND cm.student_id IS NULL) AS meetings_cnt,
            (SELECT COUNT(*) FROM chapter_documents cd JOIN course_chapters c3 ON c3.id = cd.chapter_id WHERE c3.course_id = ? AND cd.type = "exam") AS exams_cnt,
            (SELECT COUNT(*) FROM exam_sessions es WHERE es.course_id = ?) AS adv_exams_cnt');
        $stmt->execute([$courseId, $courseId, $courseId, $courseId, $courseId]);
        return $stmt->fetch() ?: [];
    }

    public function fetchAdminIds(): array
    {
        $rows = $this->pdo->query('SELECT id FROM users WHERE roles_json LIKE "%\"admin\"%"')->fetchAll() ?: [];
        return array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $rows);
    }

    public function insertNotification(int $userId, string $title, string $body, ?string $url): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO notifications (user_id, title, body, url, created_at) VALUES (:uid, :title, :body, :url, NOW())');
        $stmt->execute([
            ':uid' => $userId,
            ':title' => $title,
            ':body' => $body,
            ':url' => $url,
        ]);
    }

    public function clearUpdateNotice(int $courseId): void
    {
        $stmt = $this->pdo->prepare('UPDATE courses SET update_in_progress = 0, updated_at = NOW() WHERE id = :id');
        $stmt->execute([':id' => $courseId]);
    }

    public function setUpdateInProgress(int $courseId): void
    {
        $stmt = $this->pdo->prepare('UPDATE courses SET update_in_progress = 1, updated_at = NOW() WHERE id = :id');
        $stmt->execute([':id' => $courseId]);
    }

    public function updateCourseDurationHours(int $courseId, float $hours): void
    {
        $stmt = $this->pdo->prepare('UPDATE courses SET duration_hours = :dur, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':dur' => $hours,
            ':id' => $courseId,
        ]);
    }

    public function sumLessonDurationMinutes(int $courseId): int
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(cl.duration_estimate_min), 0) AS total_min FROM course_lessons cl JOIN course_chapters cc ON cc.id = cl.chapter_id WHERE cc.course_id = :cid');
        $stmt->execute([':cid' => $courseId]);
        return (int) $stmt->fetchColumn();
    }

    public function fetchCourseHistory(int $courseId): array
    {
        $stmt = $this->pdo->prepare('SELECT al.*, u.first_name, u.last_name
            FROM audit_logs al
            LEFT JOIN users u ON u.id = al.user_id
            WHERE al.target_type = "course"
              AND al.target_id = :id
              AND al.action IN ("course_update","course_update_started","course_update_finished")
            ORDER BY al.created_at DESC
            LIMIT 20');
        $stmt->execute([':id' => $courseId]);
        return $stmt->fetchAll() ?: [];
    }

    public function fetchChapters(int $courseId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM course_chapters WHERE course_id = :cid ORDER BY position ASC');
        $stmt->execute([':cid' => $courseId]);
        return $stmt->fetchAll() ?: [];
    }

    public function fetchLessonsList(int $courseId): array
    {
        $stmt = $this->pdo->prepare('SELECT cl.id, cl.chapter_id AS chapter_id, cl.title, cl.position, cc.title AS chapter_title, cc.position AS chap_pos FROM course_lessons cl JOIN course_chapters cc ON cc.id = cl.chapter_id WHERE cc.course_id = :cid ORDER BY cc.position ASC, cl.position ASC');
        $stmt->execute([':cid' => $courseId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @param array<int,int> $chapterIds
     * @return array<int,array<string,mixed>>
     */
    public function fetchLessonsByChapterIds(array $chapterIds): array
    {
        if ($chapterIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($chapterIds), '?'));
        $stmt = $this->pdo->prepare("SELECT * FROM course_lessons WHERE chapter_id IN ({$placeholders}) ORDER BY position ASC");
        $stmt->execute($chapterIds);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @param array<int,int> $chapterIds
     * @return array<int,array<string,mixed>>
     */
    public function fetchDocsByChapterIds(array $chapterIds): array
    {
        if ($chapterIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($chapterIds), '?'));
        $stmt = $this->pdo->prepare("SELECT * FROM chapter_documents WHERE chapter_id IN ({$placeholders}) ORDER BY position ASC");
        $stmt->execute($chapterIds);
        return $stmt->fetchAll() ?: [];
    }

    public function fetchMeetingTemplates(int $courseId): array
    {
        $stmt = $this->pdo->prepare('SELECT cm.*, cc.title AS chapter_title FROM course_meetings cm LEFT JOIN course_chapters cc ON cc.id = cm.chapter_id WHERE cm.course_id = :cid AND cm.student_id IS NULL ORDER BY cm.id ASC');
        $stmt->execute([':cid' => $courseId]);
        return $stmt->fetchAll() ?: [];
    }

    public function fetchLatestAdvancedExam(int $courseId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM exam_sessions WHERE course_id = :cid ORDER BY id DESC LIMIT 1');
        $stmt->execute([':cid' => $courseId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

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

    private function assertChapterTable(string $table): string
    {
        $allowed = ['course_lessons', 'chapter_documents'];
        if (!in_array($table, $allowed, true)) {
            throw new \InvalidArgumentException('Table non autorisee.');
        }
        return $table;
    }
}
