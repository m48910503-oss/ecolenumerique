<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class MeetingsModel extends Model
{
    public function countUnreadNotifications(int $userId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0');
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function countUpcomingConfirmedMeetings(int $userId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*)
            FROM course_meetings
            WHERE student_id = :uid
              AND status = "confirmed"
              AND start_at IS NOT NULL
              AND start_at >= NOW()');
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function findConfirmedMeetings(int $userId): array
    {
        return $this->findConfirmedMeetingsPage($userId, 0, 50);
    }

    public function countConfirmedMeetings(int $userId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM course_meetings
             WHERE student_id = :uid
               AND status = "confirmed"'
        );
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function findConfirmedMeetingsPage(int $userId, int $offset, int $limit): array
    {
        $offset = max(0, $offset);
        $limit = max(1, min(200, $limit));
        $stmt = $this->pdo->prepare('SELECT cm.id, cm.course_id, cm.chapter_id, cm.title AS meeting_title, cm.mode,
               cm.start_at, cm.end_at, cm.meeting_link, cm.meeting_place,
               c.title AS course_title, c.slug AS course_slug, cc.title AS chapter_title
            FROM course_meetings cm
            JOIN courses c ON c.id = cm.course_id
            LEFT JOIN course_chapters cc ON cc.id = cm.chapter_id
            WHERE cm.student_id = :uid AND cm.status = "confirmed"
            ORDER BY (cm.start_at < NOW()), cm.start_at ASC, cm.created_at DESC
            LIMIT ' . $limit . ' OFFSET ' . $offset);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }
}
