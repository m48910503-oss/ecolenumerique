<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class ApiNotificationsFeedModel extends Model
{
    private ?bool $hasAnnouncementColumn = null;

    public function findLatestNotificationIdForUser(int $userId, bool $excludeAnnouncements = false): int
    {
        if ($userId < 1) {
            return 0;
        }
        $sql = 'SELECT COALESCE(MAX(id), 0) AS latest_id
             FROM notifications
             WHERE user_id = :uid';
        if ($excludeAnnouncements && $this->hasAnnouncementColumn()) {
            $sql .= ' AND (announcement_id IS NULL OR announcement_id = 0)';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return (int) ($stmt->fetch()['latest_id'] ?? 0);
    }

    public function countUnreadForUser(int $userId, bool $excludeAnnouncements = false): int
    {
        if ($userId < 1) {
            return 0;
        }
        $sql = 'SELECT COUNT(*) AS unread_count
             FROM notifications
             WHERE user_id = :uid
               AND is_read = 0';
        if ($excludeAnnouncements && $this->hasAnnouncementColumn()) {
            $sql .= ' AND (announcement_id IS NULL OR announcement_id = 0)';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return (int) ($stmt->fetch()['unread_count'] ?? 0);
    }

    public function countUnreadSupportRepliesForUser(int $userId): int
    {
        if ($userId < 1) {
            return 0;
        }
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS unread_count
             FROM support_messages m
             JOIN support_conversations c ON c.id = m.conversation_id
             LEFT JOIN support_conversation_reads r
                ON r.conversation_id = c.id
               AND r.user_id = :uid_read
             WHERE c.created_by = :uid_owner
               AND m.id > COALESCE(r.last_read_message_id, 0)
               AND m.sender_id <> :uid_sender'
        );
        $stmt->execute([
            ':uid_read' => $userId,
            ':uid_owner' => $userId,
            ':uid_sender' => $userId,
        ]);
        return (int) ($stmt->fetch()['unread_count'] ?? 0);
    }

    public function countUnreadSupportNotificationsForUser(int $userId): int
    {
        if ($userId < 1) {
            return 0;
        }
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS unread_count
             FROM notifications
             WHERE user_id = :uid
               AND is_read = 0
               AND (
                    LOWER(title) LIKE :support
                    OR LOWER(body) LIKE :support
               )'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':support' => '%support%',
        ]);
        return (int) ($stmt->fetch()['unread_count'] ?? 0);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findAfterIdForUser(int $userId, int $afterId, int $limit = 20, bool $excludeAnnouncements = false): array
    {
        if ($userId < 1) {
            return [];
        }
        $safeLimit = max(1, min(50, $limit));
        $sql = 'SELECT id, title, body, summary, importance, url, is_read, created_at
             FROM notifications
             WHERE user_id = :uid
               AND is_read = 0
               AND id > :after_id';
        if ($excludeAnnouncements && $this->hasAnnouncementColumn()) {
            $sql .= ' AND (announcement_id IS NULL OR announcement_id = 0)';
        }
        $sql .= ' ORDER BY id ASC
             LIMIT ' . $safeLimit;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':uid' => $userId,
            ':after_id' => max(0, $afterId),
        ]);
        return $stmt->fetchAll() ?: [];
    }

    private function hasAnnouncementColumn(): bool
    {
        if ($this->hasAnnouncementColumn !== null) {
            return $this->hasAnnouncementColumn;
        }

        try {
            $this->hasAnnouncementColumn = $this->columnExists('notifications', 'announcement_id');
        } catch (\Throwable $e) {
            $this->hasAnnouncementColumn = false;
        }

        return $this->hasAnnouncementColumn;
    }
}
