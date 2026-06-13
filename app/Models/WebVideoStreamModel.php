<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class WebVideoStreamModel extends Model
{
    public function hasActiveEnrollment(int $userId, int $courseId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1
             FROM enrollments
             WHERE user_id = :user_id AND course_id = :course_id AND status = "active"
             LIMIT 1'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':course_id' => $courseId,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    public function findVideoPathForLessonAndCourse(int $lessonId, int $courseId): ?string
    {
        $stmt = $this->pdo->prepare(
            'SELECT l.document_path
             FROM course_lessons l
             JOIN course_chapters ch ON ch.id = l.chapter_id
             WHERE l.id = :lesson_id
               AND l.lesson_type = "video"
               AND ch.course_id = :course_id
             LIMIT 1'
        );
        $stmt->execute([
            ':lesson_id' => $lessonId,
            ':course_id' => $courseId,
        ]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        return (string) ($row['document_path'] ?? '');
    }
}
