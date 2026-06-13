<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class ApiLearningModel extends Model
{
    public function findLessonCourseByLessonId(int $lessonId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, course_id FROM course_lessons WHERE id = :lid LIMIT 1');
        $stmt->execute([':lid' => $lessonId]);
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

    public function insertLessonCompletion(int $userId, int $courseId, int $lessonId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO lesson_completions (user_id, course_id, lesson_id, completed_at)
             VALUES (:uid, :cid, :lid, NOW())'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':cid' => $courseId,
            ':lid' => $lessonId,
        ]);
    }

    public function countCourseLessonsDirect(int $courseId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM course_lessons WHERE course_id = :cid');
        $stmt->execute([':cid' => $courseId]);
        return (int) $stmt->fetchColumn();
    }

    public function countCourseLessonsByChapters(int $courseId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM course_lessons l
             JOIN course_chapters ch ON ch.id = l.chapter_id
             WHERE ch.course_id = :cid'
        );
        $stmt->execute([':cid' => $courseId]);
        return (int) $stmt->fetchColumn();
    }

    public function countLessonCompletions(int $userId, int $courseId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM lesson_completions
             WHERE course_id = :cid
               AND user_id = :uid'
        );
        $stmt->execute([
            ':cid' => $courseId,
            ':uid' => $userId,
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function fetchLessonPositionSeconds(int $userId, int $lessonId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT seconds
             FROM lesson_positions
             WHERE user_id = :uid
               AND lesson_id = :lid
             LIMIT 1'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':lid' => $lessonId,
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function upsertLessonPosition(
        int $userId,
        int $courseId,
        int $lessonId,
        int $seconds,
        int $duration,
        int $progressPercent
    ): void {
        $sql = 'INSERT INTO lesson_positions (user_id, course_id, lesson_id, seconds, duration, progress_percent, updated_at)
                VALUES (:uid, :cid, :lid, :sec, :dur, :pct, NOW())
                ON DUPLICATE KEY UPDATE
                    seconds = :sec2,
                    duration = :dur2,
                    progress_percent = :pct2,
                    updated_at = NOW()';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':uid' => $userId,
            ':cid' => $courseId,
            ':lid' => $lessonId,
            ':sec' => $seconds,
            ':dur' => $duration,
            ':pct' => $progressPercent,
            ':sec2' => $seconds,
            ':dur2' => $duration,
            ':pct2' => $progressPercent,
        ]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchLessonBookmarks(int $userId, int $lessonId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, timestamp_seconds, label, created_at
             FROM lesson_bookmarks
             WHERE user_id = :uid
               AND lesson_id = :lid
             ORDER BY timestamp_seconds ASC, id ASC'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':lid' => $lessonId,
        ]);
        $rows = $stmt->fetchAll();
        if (!is_array($rows)) {
            return [];
        }

        $bookmarks = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $bookmarks[] = [
                'id' => (int) ($row['id'] ?? 0),
                'timestamp' => (int) ($row['timestamp_seconds'] ?? 0),
                'label' => (string) ($row['label'] ?? ''),
                'created_at' => (string) ($row['created_at'] ?? ''),
            ];
        }

        return $bookmarks;
    }

    public function insertLessonBookmark(
        int $userId,
        int $courseId,
        int $lessonId,
        int $timestampSeconds,
        string $label
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO lesson_bookmarks (user_id, course_id, lesson_id, timestamp_seconds, label, created_at, updated_at)
             VALUES (:uid, :cid, :lid, :ts, :lbl, NOW(), NOW())'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':cid' => $courseId,
            ':lid' => $lessonId,
            ':ts' => max(0, $timestampSeconds),
            ':lbl' => $label,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function deleteLessonBookmark(int $userId, int $bookmarkId, int $lessonId): bool
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM lesson_bookmarks
             WHERE id = :bid
               AND user_id = :uid
               AND lesson_id = :lid
             LIMIT 1'
        );
        $stmt->execute([
            ':bid' => $bookmarkId,
            ':uid' => $userId,
            ':lid' => $lessonId,
        ]);
        return $stmt->rowCount() > 0;
    }
}
