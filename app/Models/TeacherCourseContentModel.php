<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class TeacherCourseContentModel extends Model
{
    public function findCourseById(int $courseId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM courses WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $courseId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function nextChapterPosition(int $courseId): int
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(MAX(position), 0) AS max_pos FROM course_chapters WHERE course_id = :cid');
        $stmt->execute([':cid' => $courseId]);
        $row = $stmt->fetch();
        return (int) ($row['max_pos'] ?? 0) + 1;
    }

    public function nextLessonPosition(int $chapterId): int
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(MAX(position), 0) AS max_pos FROM course_lessons WHERE chapter_id = :cid');
        $stmt->execute([':cid' => $chapterId]);
        $row = $stmt->fetch();
        return (int) ($row['max_pos'] ?? 0) + 1;
    }

    public function nextDocumentPosition(int $chapterId): int
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(MAX(position), 0) AS max_pos FROM chapter_documents WHERE chapter_id = :cid');
        $stmt->execute([':cid' => $chapterId]);
        $row = $stmt->fetch();
        return (int) ($row['max_pos'] ?? 0) + 1;
    }

    public function chapterBelongsToCourse(int $chapterId, int $courseId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM course_chapters WHERE id = :id AND course_id = :cid LIMIT 1');
        $stmt->execute([
            ':id' => $chapterId,
            ':cid' => $courseId,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    public function lessonBelongsToChapterAndCourse(int $lessonId, int $chapterId, int $courseId): bool
    {
        $stmt = $this->pdo->prepare('SELECT cl.id
            FROM course_lessons cl
            JOIN course_chapters cc ON cc.id = cl.chapter_id
            WHERE cl.id = :lid AND cl.chapter_id = :chid AND cc.course_id = :cid
            LIMIT 1');
        $stmt->execute([
            ':lid' => $lessonId,
            ':chid' => $chapterId,
            ':cid' => $courseId,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    public function insertChapter(int $courseId, string $title, ?string $description, int $position): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO course_chapters (course_id, title, description, position, created_at)
            VALUES (:cid, :title, :descr, :pos, NOW())');
        $stmt->execute([
            ':cid' => $courseId,
            ':title' => $title,
            ':descr' => $description,
            ':pos' => $position,
        ]);
    }

    public function insertLesson(
        int $chapterId,
        string $title,
        ?string $description,
        int $position,
        int $durationMin,
        string $youtubeUrl
    ): void {
        $stmt = $this->pdo->prepare('INSERT INTO course_lessons
            (chapter_id, lesson_type, youtube_url, document_path, document_mime, document_size, title, description, position, duration_estimate_min, created_at)
            VALUES
            (:cid, :type, :yt, :doc, :mime, :size, :title, :descr, :pos, :dur, NOW())');
        $stmt->execute([
            ':cid' => $chapterId,
            ':type' => 'video',
            ':yt' => $youtubeUrl,
            ':doc' => null,
            ':mime' => null,
            ':size' => null,
            ':title' => $title,
            ':descr' => $description,
            ':pos' => $position,
            ':dur' => $durationMin > 0 ? $durationMin : null,
        ]);
    }

    public function sumCourseLessonsDurationMin(int $courseId): int
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(cl.duration_estimate_min), 0) AS total_min
            FROM course_lessons cl
            JOIN course_chapters cc ON cc.id = cl.chapter_id
            WHERE cc.course_id = :cid');
        $stmt->execute([':cid' => $courseId]);
        return (int) $stmt->fetchColumn();
    }

    public function updateCourseDurationHours(int $courseId, float $durationHours): void
    {
        $stmt = $this->pdo->prepare('UPDATE courses SET duration_hours = :dur, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':dur' => $durationHours,
            ':id' => $courseId,
        ]);
    }

    public function updateCoursePedagogy(int $courseId, string $objectivesJson, string $prerequisitesJson, string $equipmentJson): void
    {
        $stmt = $this->pdo->prepare('UPDATE courses
            SET objectives = :objectives,
                prerequisites = :prerequisites,
                equipment = :equipment,
                updated_at = NOW()
            WHERE id = :id');
        $stmt->execute([
            ':objectives' => $objectivesJson,
            ':prerequisites' => $prerequisitesJson,
            ':equipment' => $equipmentJson,
            ':id' => $courseId,
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
    ): void {
        $stmt = $this->pdo->prepare('INSERT INTO chapter_documents
            (chapter_id, title, type, file_path, mime, size, position, created_at)
            VALUES
            (:cid, :title, :type, :path, :mime, :size, :pos, NOW())');
        $stmt->execute([
            ':cid' => $chapterId,
            ':title' => $title,
            ':type' => $type,
            ':path' => $filePath,
            ':mime' => $mime,
            ':size' => $size,
            ':pos' => $position,
        ]);
    }

    public function insertMeetingWithDuration(
        int $courseId,
        int $chapterId,
        string $title,
        string $mode,
        int $durationMin
    ): void {
        $stmt = $this->pdo->prepare('INSERT INTO course_meetings
            (course_id, chapter_id, title, mode, duration_min, status, start_at, end_at, meeting_link, meeting_place, created_at)
            VALUES
            (:cid, :chapter_id, :title, :mode, :duration_min, :status, :start_at, :end_at, :link, :place, NOW())');
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
    }

    public function insertMeetingWithoutDuration(
        int $courseId,
        int $chapterId,
        string $title,
        string $mode
    ): void {
        $stmt = $this->pdo->prepare('INSERT INTO course_meetings
            (course_id, chapter_id, title, mode, status, start_at, end_at, meeting_link, meeting_place, created_at)
            VALUES
            (:cid, :chapter_id, :title, :mode, :status, :start_at, :end_at, :link, :place, NOW())');
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

    public function updateCourseDraftStatus(int $courseId): void
    {
        $stmt = $this->pdo->prepare('UPDATE courses SET status = :s, submitted_at = NULL, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':s' => 'draft',
            ':id' => $courseId,
        ]);
    }

    public function fetchSubmissionCounts(int $courseId): array
    {
        $stmt = $this->pdo->prepare('SELECT 
            (SELECT COUNT(*) FROM course_chapters cc WHERE cc.course_id = ?) AS chapters_cnt,
            (SELECT COUNT(*) FROM course_lessons cl JOIN course_chapters c2 ON c2.id = cl.chapter_id WHERE c2.course_id = ?) AS lessons_cnt,
            (SELECT COUNT(*) FROM course_meetings cm WHERE cm.course_id = ? AND cm.student_id IS NULL) AS meetings_cnt,
            (SELECT COUNT(*) FROM chapter_documents cd JOIN course_chapters c3 ON c3.id = cd.chapter_id WHERE c3.course_id = ? AND cd.type = \'exam\') AS exams_cnt,
            (SELECT COUNT(*) FROM exam_sessions es WHERE es.course_id = ?) AS adv_exams_cnt');
        $stmt->execute([$courseId, $courseId, $courseId, $courseId, $courseId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : [];
    }

    public function updateCourseSubmittedStatus(int $courseId): void
    {
        $stmt = $this->pdo->prepare('UPDATE courses SET status = :s, submitted_at = NOW(), updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':s' => 'submitted',
            ':id' => $courseId,
        ]);
    }

    public function findAdminIds(): array
    {
        $rows = $this->pdo->query('SELECT id FROM users WHERE roles_json LIKE \'%"admin"%\'')->fetchAll();
        return $rows ?: [];
    }

    public function insertNotification(int $userId, string $title, string $body, string $url): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO notifications (user_id, title, body, url, created_at)
            VALUES (:uid, :title, :body, :url, NOW())');
        $stmt->execute([
            ':uid' => $userId,
            ':title' => $title,
            ':body' => $body,
            ':url' => $url,
        ]);
    }

    public function setCourseUpdateInProgress(int $courseId, int $flag): void
    {
        $stmt = $this->pdo->prepare('UPDATE courses SET update_in_progress = :flag, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':flag' => $flag ? 1 : 0,
            ':id' => $courseId,
        ]);
    }

    public function fetchChaptersByCourse(int $courseId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM course_chapters WHERE course_id = :cid ORDER BY position ASC');
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
        $stmt = $this->pdo->prepare("SELECT * FROM course_lessons WHERE chapter_id IN ($placeholders) ORDER BY position ASC");
        $stmt->execute($chapterIds);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @param array<int,int> $chapterIds
     * @return array<int,array<string,mixed>>
     */
    public function fetchDocumentsByChapterIds(array $chapterIds): array
    {
        if ($chapterIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($chapterIds), '?'));
        $stmt = $this->pdo->prepare("SELECT * FROM chapter_documents WHERE chapter_id IN ($placeholders) ORDER BY position ASC");
        $stmt->execute($chapterIds);
        return $stmt->fetchAll() ?: [];
    }

    public function fetchMeetingsForCourse(int $courseId): array
    {
        $stmt = $this->pdo->prepare('SELECT cm.*, cc.title AS chapter_title
            FROM course_meetings cm
            LEFT JOIN course_chapters cc ON cc.id = cm.chapter_id
            WHERE cm.course_id = :cid AND cm.student_id IS NULL
            ORDER BY cm.id ASC');
        $stmt->execute([':cid' => $courseId]);
        return $stmt->fetchAll() ?: [];
    }

    public function fetchExamsForCourse(int $courseId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM exam_sessions WHERE course_id = :cid ORDER BY created_at DESC');
        $stmt->execute([':cid' => $courseId]);
        return $stmt->fetchAll() ?: [];
    }

    public function fetchLessonsListForCourse(int $courseId): array
    {
        $stmt = $this->pdo->prepare('SELECT
                cl.id,
                cl.chapter_id AS chapter_id,
                cl.title,
                cl.position,
                cc.title AS chapter_title,
                cc.position AS chap_pos
            FROM course_lessons cl
            JOIN course_chapters cc ON cc.id = cl.chapter_id
            WHERE cc.course_id = :cid
            ORDER BY cc.position ASC, cl.position ASC');
        $stmt->execute([':cid' => $courseId]);
        return $stmt->fetchAll() ?: [];
    }

    public function fetchContentStats(int $courseId): array
    {
        $stmt = $this->pdo->prepare('SELECT
            (SELECT COUNT(*) FROM course_chapters cc WHERE cc.course_id = ?) AS chapters_cnt,
            (SELECT COUNT(*)
                FROM course_lessons cl
                JOIN course_chapters cc2 ON cc2.id = cl.chapter_id
                WHERE cc2.course_id = ?) AS lessons_cnt,
            (SELECT COUNT(*)
                FROM chapter_documents cd
                JOIN course_chapters cc3 ON cc3.id = cd.chapter_id
                WHERE cc3.course_id = ? AND cd.type = \'resource\') AS resources_cnt,
            (SELECT COUNT(*)
                FROM chapter_documents cd
                JOIN course_chapters cc4 ON cc4.id = cd.chapter_id
                WHERE cc4.course_id = ? AND cd.type = \'exercise\') AS exercises_cnt,
            (SELECT COUNT(*)
                FROM chapter_documents cd
                JOIN course_chapters cc5 ON cc5.id = cd.chapter_id
                WHERE cc5.course_id = ? AND cd.type = \'exam\') AS exam_docs_cnt,
            (SELECT COUNT(*)
                FROM course_meetings cm
                WHERE cm.course_id = ? AND cm.student_id IS NULL) AS meetings_cnt,
            (SELECT COUNT(*) FROM exam_sessions es WHERE es.course_id = ?) AS adv_exams_cnt');
        $stmt->execute([
            $courseId,
            $courseId,
            $courseId,
            $courseId,
            $courseId,
            $courseId,
            $courseId,
        ]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : [];
    }

    public function fetchAuditLogsForCourse(int $courseId, int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));
        $stmt = $this->pdo->prepare(
            'SELECT al.id, al.user_id, al.action, al.meta_json, al.created_at,
                    u.first_name, u.last_name
             FROM audit_logs al
             LEFT JOIN users u ON u.id = al.user_id
             WHERE al.target_type = "course" AND al.target_id = :cid
             ORDER BY al.id DESC
             LIMIT ' . $limit
        );
        $stmt->execute([':cid' => $courseId]);
        return $stmt->fetchAll() ?: [];
    }

    public function findChapterForCourse(int $chapterId, int $courseId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM course_chapters WHERE id = :id AND course_id = :cid LIMIT 1');
        $stmt->execute([':id' => $chapterId, ':cid' => $courseId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function updateChapter(int $chapterId, int $courseId, string $title, ?string $description): void
    {
        $stmt = $this->pdo->prepare('UPDATE course_chapters
            SET title = :title, description = :description, updated_at = NOW()
            WHERE id = :id AND course_id = :cid');
        $stmt->execute([
            ':title' => $title,
            ':description' => $description,
            ':id' => $chapterId,
            ':cid' => $courseId,
        ]);
    }

    public function deleteChapter(int $chapterId, int $courseId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM course_chapters WHERE id = :id AND course_id = :cid');
        $stmt->execute([
            ':id' => $chapterId,
            ':cid' => $courseId,
        ]);
    }

    public function duplicateChapterWithContent(int $chapterId, int $courseId): int
    {
        $chapter = $this->findChapterForCourse($chapterId, $courseId);
        if (!$chapter) {
            throw new \RuntimeException('Chapitre introuvable.');
        }

        $this->pdo->beginTransaction();
        try {
            $this->insertChapter(
                $courseId,
                (string) $chapter['title'] . ' (copie)',
                isset($chapter['description']) ? (string) $chapter['description'] : null,
                $this->nextChapterPosition($courseId)
            );
            $newChapterId = (int) $this->pdo->lastInsertId();

            $lessonsStmt = $this->pdo->prepare('SELECT * FROM course_lessons WHERE chapter_id = :cid ORDER BY position ASC');
            $lessonsStmt->execute([':cid' => $chapterId]);
            $lessons = $lessonsStmt->fetchAll() ?: [];

            $lessonIdMap = [];
            $lessonPos = 1;
            foreach ($lessons as $lesson) {
                $insLesson = $this->pdo->prepare('INSERT INTO course_lessons
                    (chapter_id, lesson_type, youtube_url, document_path, document_mime, document_size, title, description, position, duration_estimate_min, created_at, updated_at)
                    VALUES
                    (:chapter_id, :lesson_type, :youtube_url, :document_path, :document_mime, :document_size, :title, :description, :position, :duration_estimate_min, NOW(), NOW())');
                $insLesson->execute([
                    ':chapter_id' => $newChapterId,
                    ':lesson_type' => (string) ($lesson['lesson_type'] ?? 'video'),
                    ':youtube_url' => $lesson['youtube_url'] ?? null,
                    ':document_path' => $lesson['document_path'] ?? null,
                    ':document_mime' => $lesson['document_mime'] ?? null,
                    ':document_size' => $lesson['document_size'] ?? null,
                    ':title' => (string) ($lesson['title'] ?? 'Lecon'),
                    ':description' => $lesson['description'] ?? null,
                    ':position' => $lessonPos++,
                    ':duration_estimate_min' => $lesson['duration_estimate_min'] ?? null,
                ]);
                $lessonIdMap[(int) ($lesson['id'] ?? 0)] = (int) $this->pdo->lastInsertId();
            }

            $docsStmt = $this->pdo->prepare('SELECT * FROM chapter_documents WHERE chapter_id = :cid ORDER BY position ASC');
            $docsStmt->execute([':cid' => $chapterId]);
            $docs = $docsStmt->fetchAll() ?: [];

            $docPos = 1;
            foreach ($docs as $doc) {
                $mime = (string) ($doc['mime'] ?? '');
                if (preg_match('/\|lesson:(\d+)/', $mime, $match)) {
                    $oldLessonId = (int) ($match[1] ?? 0);
                    if ($oldLessonId > 0 && isset($lessonIdMap[$oldLessonId])) {
                        $mime = preg_replace('/\|lesson:\d+/', '|lesson:' . $lessonIdMap[$oldLessonId], $mime) ?? $mime;
                    } else {
                        $mime = preg_replace('/\|lesson:\d+/', '', $mime) ?? $mime;
                    }
                }

                $insDoc = $this->pdo->prepare('INSERT INTO chapter_documents
                    (chapter_id, title, type, file_path, mime, size, position, created_at, updated_at)
                    VALUES
                    (:chapter_id, :title, :type, :file_path, :mime, :size, :position, NOW(), NOW())');
                $insDoc->execute([
                    ':chapter_id' => $newChapterId,
                    ':title' => (string) ($doc['title'] ?? 'Ressource'),
                    ':type' => (string) ($doc['type'] ?? 'resource'),
                    ':file_path' => (string) ($doc['file_path'] ?? ''),
                    ':mime' => $mime,
                    ':size' => (int) ($doc['size'] ?? 0),
                    ':position' => $docPos++,
                ]);
            }

            $this->pdo->commit();
            return $newChapterId;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function findLessonForCourse(int $lessonId, int $courseId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT cl.*, cc.course_id
            FROM course_lessons cl
            JOIN course_chapters cc ON cc.id = cl.chapter_id
            WHERE cl.id = :id AND cc.course_id = :cid
            LIMIT 1');
        $stmt->execute([':id' => $lessonId, ':cid' => $courseId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function updateLesson(
        int $lessonId,
        int $courseId,
        int $chapterId,
        string $title,
        ?string $description,
        int $durationMin,
        string $youtubeUrl
    ): void {
        $stmt = $this->pdo->prepare('UPDATE course_lessons cl
            JOIN course_chapters cc ON cc.id = cl.chapter_id
            SET cl.chapter_id = :chapter_id,
                cl.title = :title,
                cl.description = :description,
                cl.duration_estimate_min = :duration_min,
                cl.youtube_url = :youtube_url,
                cl.updated_at = NOW()
            WHERE cl.id = :lesson_id AND cc.course_id = :course_id');
        $stmt->execute([
            ':chapter_id' => $chapterId,
            ':title' => $title,
            ':description' => $description,
            ':duration_min' => $durationMin,
            ':youtube_url' => $youtubeUrl,
            ':lesson_id' => $lessonId,
            ':course_id' => $courseId,
        ]);
    }

    public function deleteLessonWithLinkedDocuments(int $lessonId, int $courseId): void
    {
        $lesson = $this->findLessonForCourse($lessonId, $courseId);
        if (!$lesson) {
            return;
        }

        $chapterId = (int) ($lesson['chapter_id'] ?? 0);
        $this->pdo->beginTransaction();
        try {
            $delDocs = $this->pdo->prepare('DELETE FROM chapter_documents
                WHERE chapter_id = :chapter_id AND mime LIKE :lesson_tag');
            $delDocs->execute([
                ':chapter_id' => $chapterId,
                ':lesson_tag' => '%|lesson:' . $lessonId . '%',
            ]);

            $delLesson = $this->pdo->prepare('DELETE cl FROM course_lessons cl
                JOIN course_chapters cc ON cc.id = cl.chapter_id
                WHERE cl.id = :lesson_id AND cc.course_id = :course_id');
            $delLesson->execute([
                ':lesson_id' => $lessonId,
                ':course_id' => $courseId,
            ]);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function duplicateLessonWithLinkedDocuments(int $lessonId, int $courseId): int
    {
        $lesson = $this->findLessonForCourse($lessonId, $courseId);
        if (!$lesson) {
            throw new \RuntimeException('Lecon introuvable.');
        }

        $chapterId = (int) ($lesson['chapter_id'] ?? 0);
        $this->pdo->beginTransaction();
        try {
            $newLessonPos = $this->nextLessonPosition($chapterId);
            $insLesson = $this->pdo->prepare('INSERT INTO course_lessons
                (chapter_id, lesson_type, youtube_url, document_path, document_mime, document_size, title, description, position, duration_estimate_min, created_at, updated_at)
                VALUES
                (:chapter_id, :lesson_type, :youtube_url, :document_path, :document_mime, :document_size, :title, :description, :position, :duration_estimate_min, NOW(), NOW())');
            $insLesson->execute([
                ':chapter_id' => $chapterId,
                ':lesson_type' => (string) ($lesson['lesson_type'] ?? 'video'),
                ':youtube_url' => $lesson['youtube_url'] ?? null,
                ':document_path' => $lesson['document_path'] ?? null,
                ':document_mime' => $lesson['document_mime'] ?? null,
                ':document_size' => $lesson['document_size'] ?? null,
                ':title' => (string) ($lesson['title'] ?? 'Lecon') . ' (copie)',
                ':description' => $lesson['description'] ?? null,
                ':position' => $newLessonPos,
                ':duration_estimate_min' => $lesson['duration_estimate_min'] ?? null,
            ]);
            $newLessonId = (int) $this->pdo->lastInsertId();

            $docsStmt = $this->pdo->prepare('SELECT * FROM chapter_documents
                WHERE chapter_id = :chapter_id AND mime LIKE :lesson_tag
                ORDER BY position ASC');
            $docsStmt->execute([
                ':chapter_id' => $chapterId,
                ':lesson_tag' => '%|lesson:' . $lessonId . '%',
            ]);
            $docs = $docsStmt->fetchAll() ?: [];

            foreach ($docs as $doc) {
                $nextDocPos = $this->nextDocumentPosition($chapterId);
                $mime = (string) ($doc['mime'] ?? '');
                if (preg_match('/\|lesson:\d+/', $mime) === 1) {
                    $mime = preg_replace('/\|lesson:\d+/', '|lesson:' . $newLessonId, $mime) ?? $mime;
                } else {
                    $mime .= '|lesson:' . $newLessonId;
                }

                $insDoc = $this->pdo->prepare('INSERT INTO chapter_documents
                    (chapter_id, title, type, file_path, mime, size, position, created_at, updated_at)
                    VALUES
                    (:chapter_id, :title, :type, :file_path, :mime, :size, :position, NOW(), NOW())');
                $insDoc->execute([
                    ':chapter_id' => $chapterId,
                    ':title' => (string) ($doc['title'] ?? 'Ressource') . ' (copie)',
                    ':type' => (string) ($doc['type'] ?? 'resource'),
                    ':file_path' => (string) ($doc['file_path'] ?? ''),
                    ':mime' => $mime,
                    ':size' => (int) ($doc['size'] ?? 0),
                    ':position' => $nextDocPos,
                ]);
            }

            $this->pdo->commit();
            return $newLessonId;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function findDocumentForCourse(int $docId, int $courseId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT cd.*, cc.course_id
            FROM chapter_documents cd
            JOIN course_chapters cc ON cc.id = cd.chapter_id
            WHERE cd.id = :id AND cc.course_id = :cid
            LIMIT 1');
        $stmt->execute([':id' => $docId, ':cid' => $courseId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function updateDocumentRecord(
        int $docId,
        int $courseId,
        int $chapterId,
        string $title,
        string $type,
        string $mime
    ): void {
        $position = $this->nextDocumentPosition($chapterId);
        $stmt = $this->pdo->prepare('UPDATE chapter_documents cd
            JOIN course_chapters cc ON cc.id = cd.chapter_id
            SET cd.chapter_id = :chapter_id,
                cd.title = :title,
                cd.type = :type,
                cd.mime = :mime,
                cd.position = :position,
                cd.updated_at = NOW()
            WHERE cd.id = :doc_id AND cc.course_id = :course_id');
        $stmt->execute([
            ':chapter_id' => $chapterId,
            ':title' => $title,
            ':type' => $type,
            ':mime' => $mime,
            ':position' => $position,
            ':doc_id' => $docId,
            ':course_id' => $courseId,
        ]);
    }

    public function deleteDocument(int $docId, int $courseId): void
    {
        $stmt = $this->pdo->prepare('DELETE cd FROM chapter_documents cd
            JOIN course_chapters cc ON cc.id = cd.chapter_id
            WHERE cd.id = :doc_id AND cc.course_id = :course_id');
        $stmt->execute([
            ':doc_id' => $docId,
            ':course_id' => $courseId,
        ]);
    }

    public function duplicateDocument(int $docId, int $courseId): int
    {
        $doc = $this->findDocumentForCourse($docId, $courseId);
        if (!$doc) {
            throw new \RuntimeException('Ressource introuvable.');
        }
        $chapterId = (int) ($doc['chapter_id'] ?? 0);
        $stmt = $this->pdo->prepare('INSERT INTO chapter_documents
            (chapter_id, title, type, file_path, mime, size, position, created_at, updated_at)
            VALUES
            (:chapter_id, :title, :type, :file_path, :mime, :size, :position, NOW(), NOW())');
        $stmt->execute([
            ':chapter_id' => $chapterId,
            ':title' => (string) ($doc['title'] ?? 'Ressource') . ' (copie)',
            ':type' => (string) ($doc['type'] ?? 'resource'),
            ':file_path' => (string) ($doc['file_path'] ?? ''),
            ':mime' => (string) ($doc['mime'] ?? 'application/octet-stream'),
            ':size' => (int) ($doc['size'] ?? 0),
            ':position' => $this->nextDocumentPosition($chapterId),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function findMeetingForCourse(int $meetingId, int $courseId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM course_meetings
            WHERE id = :id AND course_id = :cid AND student_id IS NULL
            LIMIT 1');
        $stmt->execute([':id' => $meetingId, ':cid' => $courseId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function updateMeeting(
        int $meetingId,
        int $courseId,
        int $chapterId,
        string $title,
        string $mode,
        int $durationMin
    ): void {
        $stmt = $this->pdo->prepare('UPDATE course_meetings
            SET chapter_id = :chapter_id,
                title = :title,
                mode = :mode,
                duration_min = :duration_min
            WHERE id = :meeting_id AND course_id = :course_id AND student_id IS NULL');
        $stmt->execute([
            ':chapter_id' => $chapterId,
            ':title' => $title,
            ':mode' => $mode,
            ':duration_min' => $durationMin,
            ':meeting_id' => $meetingId,
            ':course_id' => $courseId,
        ]);
    }

    public function deleteMeeting(int $meetingId, int $courseId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM course_meetings
            WHERE id = :meeting_id AND course_id = :course_id AND student_id IS NULL');
        $stmt->execute([
            ':meeting_id' => $meetingId,
            ':course_id' => $courseId,
        ]);
    }

    public function duplicateMeeting(int $meetingId, int $courseId): int
    {
        $meeting = $this->findMeetingForCourse($meetingId, $courseId);
        if (!$meeting) {
            throw new \RuntimeException('Rendez-vous introuvable.');
        }

        $stmt = $this->pdo->prepare('INSERT INTO course_meetings
            (course_id, chapter_id, title, mode, duration_min, status, start_at, end_at, meeting_link, meeting_place, created_at)
            VALUES
            (:course_id, :chapter_id, :title, :mode, :duration_min, :status, :start_at, :end_at, :meeting_link, :meeting_place, NOW())');
        $stmt->execute([
            ':course_id' => $courseId,
            ':chapter_id' => (int) ($meeting['chapter_id'] ?? 0),
            ':title' => (string) ($meeting['title'] ?? 'Rendez-vous') . ' (copie)',
            ':mode' => (string) ($meeting['mode'] ?? 'online'),
            ':duration_min' => (int) ($meeting['duration_min'] ?? 60),
            ':status' => 'pending',
            ':start_at' => null,
            ':end_at' => null,
            ':meeting_link' => null,
            ':meeting_place' => null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array<int,int> $orderedChapterIds
     */
    public function reorderChapters(int $courseId, array $orderedChapterIds): void
    {
        if ($orderedChapterIds === []) {
            return;
        }

        $existingIds = $this->fetchChapterIdsForCourse($courseId);
        sort($existingIds);
        $sortedIncoming = $orderedChapterIds;
        sort($sortedIncoming);
        if ($existingIds !== $sortedIncoming) {
            throw new \RuntimeException('Ordre des chapitres invalide.');
        }

        $this->pdo->beginTransaction();
        try {
            $tmp = $this->pdo->prepare('UPDATE course_chapters SET position = :position WHERE id = :id AND course_id = :course_id');
            foreach ($orderedChapterIds as $index => $chapterId) {
                $tmp->execute([
                    ':position' => 1000 + $index,
                    ':id' => $chapterId,
                    ':course_id' => $courseId,
                ]);
            }

            $fin = $this->pdo->prepare('UPDATE course_chapters SET position = :position WHERE id = :id AND course_id = :course_id');
            foreach ($orderedChapterIds as $index => $chapterId) {
                $fin->execute([
                    ':position' => $index + 1,
                    ':id' => $chapterId,
                    ':course_id' => $courseId,
                ]);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * @param array<int,int> $orderedLessonIds
     */
    public function reorderLessons(int $courseId, int $chapterId, array $orderedLessonIds): void
    {
        if ($orderedLessonIds === []) {
            return;
        }
        if (!$this->chapterBelongsToCourse($chapterId, $courseId)) {
            throw new \RuntimeException('Chapitre invalide.');
        }

        $existingIds = $this->fetchLessonIdsForChapter($chapterId);
        sort($existingIds);
        $sortedIncoming = $orderedLessonIds;
        sort($sortedIncoming);
        if ($existingIds !== $sortedIncoming) {
            throw new \RuntimeException('Ordre des lecons invalide.');
        }

        $this->pdo->beginTransaction();
        try {
            $tmp = $this->pdo->prepare('UPDATE course_lessons SET position = :position WHERE id = :id AND chapter_id = :chapter_id');
            foreach ($orderedLessonIds as $index => $lessonId) {
                $tmp->execute([
                    ':position' => 1000 + $index,
                    ':id' => $lessonId,
                    ':chapter_id' => $chapterId,
                ]);
            }

            $fin = $this->pdo->prepare('UPDATE course_lessons SET position = :position WHERE id = :id AND chapter_id = :chapter_id');
            foreach ($orderedLessonIds as $index => $lessonId) {
                $fin->execute([
                    ':position' => $index + 1,
                    ':id' => $lessonId,
                    ':chapter_id' => $chapterId,
                ]);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * @return array<int,int>
     */
    private function fetchChapterIdsForCourse(int $courseId): array
    {
        $stmt = $this->pdo->prepare('SELECT id FROM course_chapters WHERE course_id = :course_id ORDER BY position ASC');
        $stmt->execute([':course_id' => $courseId]);
        $rows = $stmt->fetchAll() ?: [];
        return array_values(array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $rows));
    }

    /**
     * @return array<int,int>
     */
    private function fetchLessonIdsForChapter(int $chapterId): array
    {
        $stmt = $this->pdo->prepare('SELECT id FROM course_lessons WHERE chapter_id = :chapter_id ORDER BY position ASC');
        $stmt->execute([':chapter_id' => $chapterId]);
        $rows = $stmt->fetchAll() ?: [];
        return array_values(array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $rows));
    }
}
