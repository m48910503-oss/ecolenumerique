<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class WebNotificationsModel extends Model
{
    private ?bool $hasReadAtColumn = null;
    private ?bool $hasClickedAtColumn = null;
    private ?bool $hasAnnouncementColumn = null;
    private ?bool $hasArchivedAtColumn = null;
    private ?bool $hasImportanceColumn = null;

    public function findByIdForUser(int $notificationId, int $userId, bool $excludeAnnouncements = false): ?array
    {
        $sql = 'SELECT *
             FROM notifications
             WHERE id = :id
               AND user_id = :uid';
        if ($excludeAnnouncements && $this->hasAnnouncementColumn()) {
            $sql .= ' AND (announcement_id IS NULL OR announcement_id = 0)';
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $notificationId,
            ':uid' => $userId,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchUnreadAnnouncementCounts(int $userId): array
    {
        if ($userId < 1 || !$this->hasAnnouncementColumn()) {
            return [];
        }
        $sql = 'SELECT announcement_id, variant, COUNT(*) AS total
             FROM notifications
             WHERE user_id = :uid
               AND is_read = 0
               AND announcement_id IS NOT NULL';
        if ($this->hasArchivedAtColumn()) {
            $sql .= ' AND archived_at IS NULL';
        }
        $sql .= ' GROUP BY announcement_id, variant';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    public function markAllRead(int $userId, bool $excludeAnnouncements = false): void
    {
        $where = ' WHERE user_id = :uid AND is_read = 0';
        if ($excludeAnnouncements && $this->hasAnnouncementColumn()) {
            $where .= ' AND (announcement_id IS NULL OR announcement_id = 0)';
        }
        if ($this->hasArchivedAtColumn()) {
            $where .= ' AND archived_at IS NULL';
        }
        if ($this->hasReadAtColumn()) {
            $stmt = $this->pdo->prepare('UPDATE notifications SET is_read = 1, read_at = COALESCE(read_at, NOW())' . $where);
        } else {
            $stmt = $this->pdo->prepare('UPDATE notifications SET is_read = 1' . $where);
        }
        $stmt->execute([':uid' => $userId]);
    }

    public function markRead(int $notificationId, int $userId): void
    {
        $where = ' WHERE id = :id AND user_id = :uid';
        if ($this->hasReadAtColumn()) {
            $stmt = $this->pdo->prepare('UPDATE notifications SET is_read = 1, read_at = COALESCE(read_at, NOW())' . $where);
        } else {
            $stmt = $this->pdo->prepare('UPDATE notifications SET is_read = 1' . $where);
        }
        $stmt->execute([
            ':id' => $notificationId,
            ':uid' => $userId,
        ]);
    }

    public function markUnread(int $notificationId, int $userId): void
    {
        try {
            $stmt = $this->pdo->prepare('UPDATE notifications SET is_read = 0, popup_shown_at = NULL WHERE id = :id AND user_id = :uid');
            $stmt->execute([
                ':id' => $notificationId,
                ':uid' => $userId,
            ]);
            return;
        } catch (\Throwable $e) {
            // Fallback for old schema.
        }

        $stmt = $this->pdo->prepare('UPDATE notifications SET is_read = 0 WHERE id = :id AND user_id = :uid');
        $stmt->execute([
            ':id' => $notificationId,
            ':uid' => $userId,
        ]);
    }

    public function deleteByIdForUser(int $notificationId, int $userId): bool
    {
        if ($notificationId < 1 || $userId < 1) {
            return false;
        }

        $stmt = $this->pdo->prepare('DELETE FROM notifications WHERE id = :id AND user_id = :uid');
        $stmt->execute([
            ':id' => $notificationId,
            ':uid' => $userId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function archiveByIdForUser(int $notificationId, int $userId): bool
    {
        if ($notificationId < 1 || $userId < 1) {
            return false;
        }

        if (!$this->hasArchivedAtColumn()) {
            return $this->deleteByIdForUser($notificationId, $userId);
        }

        $sql = 'UPDATE notifications
             SET archived_at = COALESCE(archived_at, NOW())';
        if ($this->hasReadAtColumn()) {
            $sql .= ', is_read = 1, read_at = COALESCE(read_at, NOW())';
        } else {
            $sql .= ', is_read = 1';
        }
        if ($this->hasPopupShownAtColumn()) {
            $sql .= ', popup_shown_at = NULL';
        }
        $sql .= ' WHERE id = :id AND user_id = :uid';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $notificationId,
            ':uid' => $userId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function restoreByIdForUser(int $notificationId, int $userId): bool
    {
        if ($notificationId < 1 || $userId < 1 || !$this->hasArchivedAtColumn()) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE notifications
             SET archived_at = NULL
             WHERE id = :id AND user_id = :uid'
        );
        $stmt->execute([
            ':id' => $notificationId,
            ':uid' => $userId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function setImportanceForUser(int $notificationId, int $userId, string $importance): bool
    {
        if ($notificationId < 1 || $userId < 1 || !$this->hasImportanceColumn()) {
            return false;
        }

        $importance = strtolower(trim($importance));
        if (!in_array($importance, ['info', 'important', 'urgent'], true)) {
            $importance = 'info';
        }

        $stmt = $this->pdo->prepare(
            'UPDATE notifications
             SET importance = :importance
             WHERE id = :id AND user_id = :uid'
        );
        $stmt->execute([
            ':importance' => $importance,
            ':id' => $notificationId,
            ':uid' => $userId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function markClicked(int $notificationId, int $userId): void
    {
        if (!$this->hasClickedAtColumn()) {
            return;
        }
        $stmt = $this->pdo->prepare('UPDATE notifications SET clicked_at = COALESCE(clicked_at, NOW()) WHERE id = :id AND user_id = :uid');
        $stmt->execute([
            ':id' => $notificationId,
            ':uid' => $userId,
        ]);
    }

    public function incrementAnnouncementReadCount(int $announcementId, string $variant, int $delta = 1): void
    {
        if ($announcementId < 1) {
            return;
        }
        $delta = max(1, $delta);
        if (strtoupper($variant) === 'B') {
            $stmt = $this->pdo->prepare('UPDATE admin_announcements SET read_count_b = read_count_b + :delta, updated_at = NOW() WHERE id = :id');
        } else {
            $stmt = $this->pdo->prepare('UPDATE admin_announcements SET read_count = read_count + :delta, updated_at = NOW() WHERE id = :id');
        }
        $stmt->execute([
            ':delta' => $delta,
            ':id' => $announcementId,
        ]);
    }

    public function incrementAnnouncementClickCount(int $announcementId, string $variant, int $delta = 1): void
    {
        if ($announcementId < 1) {
            return;
        }
        $delta = max(1, $delta);
        if (strtoupper($variant) === 'B') {
            $stmt = $this->pdo->prepare('UPDATE admin_announcements SET click_count_b = click_count_b + :delta, updated_at = NOW() WHERE id = :id');
        } else {
            $stmt = $this->pdo->prepare('UPDATE admin_announcements SET click_count = click_count + :delta, updated_at = NOW() WHERE id = :id');
        }
        $stmt->execute([
            ':delta' => $delta,
            ':id' => $announcementId,
        ]);
    }

    public function findRecentForUser(int $userId, int $limit = 50, bool $excludeAnnouncements = false): array
    {
        $limit = max(1, min(5000, $limit));
        $sql = 'SELECT * FROM notifications WHERE user_id = :uid';
        if ($excludeAnnouncements && $this->hasAnnouncementColumn()) {
            $sql .= ' AND (announcement_id IS NULL OR announcement_id = 0)';
        }
        $sql .= ' ORDER BY created_at DESC LIMIT ' . $limit;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    public function countUnreadForUser(int $userId, bool $excludeAnnouncements = false): int
    {
        $sql = 'SELECT COUNT(*) AS unread FROM notifications WHERE user_id = :uid AND is_read = 0';
        if ($excludeAnnouncements && $this->hasAnnouncementColumn()) {
            $sql .= ' AND (announcement_id IS NULL OR announcement_id = 0)';
        }
        if ($this->hasArchivedAtColumn()) {
            $sql .= ' AND archived_at IS NULL';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return (int) ($stmt->fetch()['unread'] ?? 0);
    }

    private function hasReadAtColumn(): bool
    {
        if ($this->hasReadAtColumn !== null) {
            return $this->hasReadAtColumn;
        }
        try {
            $this->hasReadAtColumn = $this->columnExists('notifications', 'read_at');
        } catch (\Throwable $e) {
            $this->hasReadAtColumn = false;
        }
        return $this->hasReadAtColumn;
    }

    private function hasClickedAtColumn(): bool
    {
        if ($this->hasClickedAtColumn !== null) {
            return $this->hasClickedAtColumn;
        }
        try {
            $this->hasClickedAtColumn = $this->columnExists('notifications', 'clicked_at');
        } catch (\Throwable $e) {
            $this->hasClickedAtColumn = false;
        }
        return $this->hasClickedAtColumn;
    }

    private function hasPopupShownAtColumn(): bool
    {
        try {
            return $this->columnExists('notifications', 'popup_shown_at');
        } catch (\Throwable $e) {
            return false;
        }
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

    private function hasArchivedAtColumn(): bool
    {
        if ($this->hasArchivedAtColumn !== null) {
            return $this->hasArchivedAtColumn;
        }
        try {
            $this->hasArchivedAtColumn = $this->columnExists('notifications', 'archived_at');
        } catch (\Throwable $e) {
            $this->hasArchivedAtColumn = false;
        }
        return $this->hasArchivedAtColumn;
    }

    private function hasImportanceColumn(): bool
    {
        if ($this->hasImportanceColumn !== null) {
            return $this->hasImportanceColumn;
        }
        try {
            $this->hasImportanceColumn = $this->columnExists('notifications', 'importance');
        } catch (\Throwable $e) {
            $this->hasImportanceColumn = false;
        }
        return $this->hasImportanceColumn;
    }
}
?>
