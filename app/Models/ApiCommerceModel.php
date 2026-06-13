<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class ApiCommerceModel extends Model
{
    private ?bool $ratingsModerationReady = null;

    public function findCourseForRating(int $courseId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT c.id, c.title, c.teacher_id FROM courses c WHERE c.id = :cid LIMIT 1');
        $stmt->execute([':cid' => $courseId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function hasActiveEnrollment(int $userId, int $courseId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1
             FROM enrollments
             WHERE user_id = :uid
               AND course_id = :cid
               AND status = "active"
             LIMIT 1'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':cid' => $courseId,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    public function upsertCourseRating(int $courseId, int $userId, int $rating, ?string $comment): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO course_ratings (course_id, user_id, rating, comment)
             VALUES (:cid, :uid, :rating, :comment)
             ON DUPLICATE KEY UPDATE
                 rating = VALUES(rating),
                 comment = VALUES(comment),
                 updated_at = NOW()'
        );
        $stmt->execute([
            ':cid' => $courseId,
            ':uid' => $userId,
            ':rating' => $rating,
            ':comment' => $comment,
        ]);
    }

    public function fetchCourseRatingStats(int $courseId): array
    {
        $sql = 'SELECT AVG(rating) AS avg_rating, COUNT(*) AS cnt
             FROM course_ratings
             WHERE course_id = :cid';
        if ($this->ratingsModerationReady()) {
            $sql .= ' AND is_hidden_by_teacher = 0';
        }
        $stmt = $this->pdo->prepare(
            $sql
        );
        $stmt->execute([':cid' => $courseId]);
        return $stmt->fetch() ?: ['avg_rating' => 0, 'cnt' => 0];
    }

    public function createNotification(int $userId, string $title, string $body, ?string $url = null): void
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

    public function findApprovedCourseForReferral(int $courseId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, teacher_id, share_token
             FROM courses
             WHERE id = :id
               AND status = "approved"
               AND deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([':id' => $courseId]);
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
}
