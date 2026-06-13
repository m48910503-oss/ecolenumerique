<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class AdminCoursePreviewModel extends Model
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

    public function fetchChaptersByCourseId(int $courseId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM course_chapters WHERE course_id = :cid ORDER BY position ASC, id ASC');
        $stmt->execute([':cid' => $courseId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @param list<int> $chapterIds
     */
    public function fetchLessonsByChapterIds(array $chapterIds): array
    {
        if ($chapterIds === []) {
            return [];
        }
        $in = implode(',', array_fill(0, count($chapterIds), '?'));
        $stmt = $this->pdo->prepare("SELECT * FROM course_lessons WHERE chapter_id IN ($in) ORDER BY position ASC, id ASC");
        $stmt->execute($chapterIds);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @param list<int> $chapterIds
     */
    public function fetchDocumentsByChapterIds(array $chapterIds): array
    {
        if ($chapterIds === []) {
            return [];
        }
        $in = implode(',', array_fill(0, count($chapterIds), '?'));
        $stmt = $this->pdo->prepare("SELECT * FROM chapter_documents WHERE chapter_id IN ($in) ORDER BY position ASC, id ASC");
        $stmt->execute($chapterIds);
        return $stmt->fetchAll() ?: [];
    }

    public function fetchMeetingsByCourseId(int $courseId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT cm.*, cc.title AS chapter_title
             FROM course_meetings cm
             LEFT JOIN course_chapters cc ON cc.id = cm.chapter_id
             WHERE cm.course_id = :cid AND cm.student_id IS NULL
             ORDER BY cm.id ASC'
        );
        $stmt->execute([':cid' => $courseId]);
        return $stmt->fetchAll() ?: [];
    }

    public function fetchExamsByCourseId(int $courseId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM exam_sessions WHERE course_id = :cid ORDER BY created_at DESC');
        $stmt->execute([':cid' => $courseId]);
        return $stmt->fetchAll() ?: [];
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

    public function insertAudit(int $userId, string $action, int $courseId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO audit_logs (user_id, action, target_type, target_id, created_at)
             VALUES (:uid, :action, :type, :tid, NOW())'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':action' => $action,
            ':type' => 'course',
            ':tid' => $courseId,
        ]);
    }
}

