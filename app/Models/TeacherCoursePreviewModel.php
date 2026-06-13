<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class TeacherCoursePreviewModel extends Model
{
    private ?bool $ratingsModerationReady = null;

    public function findCourseById(int $courseId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.*,
                    u.first_name,
                    u.last_name,
                    u.email AS teacher_email,
                    cat.name AS category_name
             FROM courses c
             LEFT JOIN users u ON u.id = c.teacher_id
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

    public function ratingsModerationReady(): bool
    {
        if (is_bool($this->ratingsModerationReady)) {
            return $this->ratingsModerationReady;
        }
        $this->ratingsModerationReady = $this->columnExists('course_ratings', 'is_hidden_by_teacher');
        return $this->ratingsModerationReady;
    }

    public function findCourseRatingForModeration(int $courseId, int $ratingId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, course_id, user_id, rating, comment, created_at, updated_at,
                    COALESCE(is_hidden_by_teacher, 0) AS is_hidden_by_teacher
             FROM course_ratings
             WHERE course_id = :course_id
               AND id = :rating_id
             LIMIT 1'
        );
        $stmt->execute([
            ':course_id' => $courseId,
            ':rating_id' => $ratingId,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateCourseRatingModerationState(int $ratingId, bool $hidden, int $moderatorUserId): void
    {
        if (!$this->ratingsModerationReady()) {
            return;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE course_ratings
             SET is_hidden_by_teacher = :hidden,
                 hidden_by_user_id = :hidden_by,
                 hidden_at = :hidden_at,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            ':hidden' => $hidden ? 1 : 0,
            ':hidden_by' => $hidden ? $moderatorUserId : null,
            ':hidden_at' => $hidden ? date('Y-m-d H:i:s') : null,
            ':id' => $ratingId,
        ]);
    }

    public function fetchCourseRatingsForModeration(int $courseId, int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));

        if ($this->ratingsModerationReady()) {
            $stmt = $this->pdo->prepare(
                'SELECT
                    cr.id,
                    cr.rating,
                    cr.comment,
                    cr.created_at,
                    cr.updated_at,
                    COALESCE(cr.is_hidden_by_teacher, 0) AS is_hidden_by_teacher,
                    cr.hidden_at,
                    cr.hidden_by_user_id,
                    u.id AS reviewer_user_id,
                    u.first_name,
                    u.last_name,
                    hu.first_name AS hidden_by_first_name,
                    hu.last_name AS hidden_by_last_name
                 FROM course_ratings cr
                 JOIN users u ON u.id = cr.user_id
                 LEFT JOIN users hu ON hu.id = cr.hidden_by_user_id
                 WHERE cr.course_id = :course_id
                 ORDER BY cr.created_at DESC
                 LIMIT ' . $limit
            );
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT
                    cr.id,
                    cr.rating,
                    cr.comment,
                    cr.created_at,
                    cr.updated_at,
                    0 AS is_hidden_by_teacher,
                    NULL AS hidden_at,
                    NULL AS hidden_by_user_id,
                    u.id AS reviewer_user_id,
                    u.first_name,
                    u.last_name,
                    NULL AS hidden_by_first_name,
                    NULL AS hidden_by_last_name
                 FROM course_ratings cr
                 JOIN users u ON u.id = cr.user_id
                 WHERE cr.course_id = :course_id
                 ORDER BY cr.created_at DESC
                 LIMIT ' . $limit
            );
        }

        $stmt->execute([':course_id' => $courseId]);
        return $stmt->fetchAll() ?: [];
    }
}
