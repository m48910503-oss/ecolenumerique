<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class TeacherMeetingsModel extends Model
{
    public function findMeetingForModeration(int $meetingId): ?array
    {
        $sql = 'SELECT cm.*, c.title AS course_title, c.slug AS course_slug, c.teacher_id, cc.title AS chapter_title,
                       u.first_name, u.last_name, u.email
                FROM course_meetings cm
                JOIN courses c ON c.id = cm.course_id
                LEFT JOIN course_chapters cc ON cc.id = cm.chapter_id
                LEFT JOIN users u ON u.id = cm.student_id
                WHERE cm.id = :id AND cm.student_id IS NOT NULL
                LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $meetingId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateMeetingConfirmed(
        int $meetingId,
        int $confirmedBy,
        string $startAt,
        string $endAt,
        ?string $meetingLink,
        ?string $meetingPlace
    ): void {
        $stmt = $this->pdo->prepare('UPDATE course_meetings
            SET status = :status,
                confirmed_by = :confirmed_by,
                start_at = :start_at,
                end_at = :end_at,
                meeting_link = :link,
                meeting_place = :place,
                updated_at = NOW()
            WHERE id = :id');
        $stmt->execute([
            ':status' => 'confirmed',
            ':confirmed_by' => $confirmedBy,
            ':start_at' => $startAt,
            ':end_at' => $endAt,
            ':link' => $meetingLink,
            ':place' => $meetingPlace,
            ':id' => $meetingId,
        ]);
    }

    /**
     * @param array<string,mixed> $meeting
     */
    public function cancelSiblingPending(array $meeting): void
    {
        $stmt = $this->pdo->prepare('UPDATE course_meetings
            SET status = :status, updated_at = NOW()
            WHERE course_id = :course_id
              AND chapter_id <=> :chapter_id
              AND title = :title
              AND mode = :mode
              AND student_id = :student_id
              AND status = "pending"
              AND id <> :id');
        $stmt->execute([
            ':status' => 'cancelled',
            ':course_id' => $meeting['course_id'],
            ':chapter_id' => $meeting['chapter_id'],
            ':title' => $meeting['title'],
            ':mode' => $meeting['mode'],
            ':student_id' => $meeting['student_id'],
            ':id' => $meeting['id'],
        ]);
    }

    public function updateMeetingStatus(int $meetingId, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE course_meetings SET status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':status' => $status,
            ':id' => $meetingId,
        ]);
    }

    public function insertNotification(int $userId, string $title, string $body, ?string $url = null): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO notifications (user_id, title, body, url, created_at) VALUES (:uid, :title, :body, :url, NOW())');
        $stmt->execute([
            ':uid' => $userId,
            ':title' => $title,
            ':body' => $body,
            ':url' => $url,
        ]);
    }

    public function findCoursesForFilter(bool $isAdmin, int $teacherId): array
    {
        $params = [];
        $sql = 'SELECT id, title FROM courses WHERE deleted_at IS NULL AND status = "approved"';
        if (!$isAdmin) {
            $sql .= ' AND teacher_id = :tid';
            $params[':tid'] = $teacherId;
        }
        $sql .= ' ORDER BY created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    public function findMeetingRequests(
        bool $isAdmin,
        int $teacherId,
        string $status,
        int $courseFilterId,
        string $q,
        bool $meetingsHasDuration
    ): array {
        $where = ['cm.student_id IS NOT NULL'];
        $params = [];
        if (!$isAdmin) {
            $where[] = 'c.teacher_id = :tid';
            $params[':tid'] = $teacherId;
        }
        $where[] = 'c.deleted_at IS NULL';
        $where[] = 'c.status = "approved"';
        if ($status !== 'all') {
            $where[] = 'cm.status = :status';
            $params[':status'] = $status;
        }
        if ($courseFilterId > 0) {
            $where[] = 'cm.course_id = :course_id';
            $params[':course_id'] = $courseFilterId;
        }
        if ($q !== '') {
            $where[] = '(u.first_name LIKE :q OR u.last_name LIKE :q OR u.email LIKE :q OR c.title LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }

        $meetingFields = $meetingsHasDuration ? 'cm.*' : 'cm.*, 60 AS duration_min';
        $sql = 'SELECT ' . $meetingFields . ', c.title AS course_title, c.slug AS course_slug, cc.title AS chapter_title,
                       u.first_name, u.last_name, u.email
                FROM course_meetings cm
                JOIN courses c ON c.id = cm.course_id
                LEFT JOIN course_chapters cc ON cc.id = cm.chapter_id
                LEFT JOIN users u ON u.id = cm.student_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY FIELD(cm.status,"pending","confirmed","cancelled"), cm.start_at IS NULL, cm.start_at ASC, cm.created_at DESC
                LIMIT 200';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }
}
