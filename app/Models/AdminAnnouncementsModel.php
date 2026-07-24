<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\NotificationRouting;

final class AdminAnnouncementsModel extends Model
{
    private ?bool $hasNotificationPrefsColumn = null;
    private ?bool $hasEventsTable = null;
    private ?bool $schemaReady = null;

    public function announcementsTableExists(): bool
    {
        try {
            return $this->tableExists('admin_announcements');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function announcementsSchemaReady(): bool
    {
        if ($this->schemaReady !== null) {
            return $this->schemaReady;
        }

        if (!$this->announcementsTableExists()) {
            $this->schemaReady = false;
            return false;
        }

        $requiredAnnouncementColumns = [
            'status',
            'audience',
            'category',
            'importance',
            'summary',
            'sent_count',
            'read_count',
            'click_count',
            'published_by',
            'approved_by',
            'published_at',
            'approved_at',
            'archived_at',
        ];

        foreach ($requiredAnnouncementColumns as $column) {
            if (!$this->columnExists('admin_announcements', $column)) {
                $this->schemaReady = false;
                return false;
            }
        }

        $requiredNotificationColumns = [
            'announcement_id',
            'importance',
            'summary',
            'read_at',
            'clicked_at',
        ];

        foreach ($requiredNotificationColumns as $column) {
            if (!$this->columnExists('notifications', $column)) {
                $this->schemaReady = false;
                return false;
            }
        }

        $this->schemaReady = true;
        return true;
    }

    public function announcementEventsTableExists(): bool
    {
        if ($this->hasEventsTable !== null) {
            return $this->hasEventsTable;
        }
        try {
            $this->hasEventsTable = $this->tableExists('admin_announcement_events');
        } catch (\Throwable $e) {
            $this->hasEventsTable = false;
        }
        return $this->hasEventsTable;
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    public function rollBack(): void
    {
        $this->pdo->rollBack();
    }

    public function createAnnouncement(
        int $adminUserId,
        string $title,
        string $body,
        string $actionUrl,
        string $status,
        string $audience,
        string $category,
        string $importance,
        string $summary
    ): int {
        $announcementId = $this->nextNumericId('admin_announcements');
        $stmt = $this->pdo->prepare(
            'INSERT INTO admin_announcements
                (id, admin_user_id, title, body, summary, action_url, status, audience, category, importance, sent_count, read_count, click_count, created_at, updated_at)
             VALUES
                (:id, :admin_user_id, :title, :body, :summary, :action_url, :status, :audience, :category, :importance, 0, 0, 0, NOW(), NOW())'
        );
        $stmt->execute([
            ':id' => $announcementId,
            ':admin_user_id' => $adminUserId,
            ':title' => $title,
            ':body' => $body,
            ':summary' => $summary,
            ':action_url' => $actionUrl,
            ':status' => $status,
            ':audience' => $audience,
            ':category' => $category,
            ':importance' => $importance,
        ]);
        return $announcementId;
    }

    public function updateAnnouncementStatus(int $announcementId, string $status, ?string $note = null): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE admin_announcements
             SET status = :status, updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            ':status' => $status,
            ':id' => $announcementId,
        ]);
    }

    public function updateAnnouncementApproval(int $announcementId, int $approverId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE admin_announcements
             SET approved_by = :approved_by, approved_at = NOW(), updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            ':approved_by' => $approverId,
            ':id' => $announcementId,
        ]);
    }

    public function updateAnnouncementPublish(int $announcementId, int $publisherId, ?int $approverId = null): void
    {
        if ($approverId === null) {
            $stmt = $this->pdo->prepare(
                'UPDATE admin_announcements
                 SET status = "published",
                     published_by = :published_by,
                     published_at = NOW(),
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $stmt->execute([
                ':published_by' => $publisherId,
                ':id' => $announcementId,
            ]);
            return;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE admin_announcements
             SET status = "published",
                 published_by = :published_by,
                 published_at = NOW(),
                 approved_by = :approved_by,
                 approved_at = COALESCE(approved_at, NOW()),
                 updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            ':published_by' => $publisherId,
            ':approved_by' => $approverId,
            ':id' => $announcementId,
        ]);
    }

    public function updateAnnouncementArchive(int $announcementId, bool $archive): void
    {
        if ($archive) {
            $stmt = $this->pdo->prepare(
                'UPDATE admin_announcements
                 SET status = "archived", archived_at = NOW(), updated_at = NOW()
                 WHERE id = :id'
            );
        } else {
            $stmt = $this->pdo->prepare(
                'UPDATE admin_announcements
                 SET status = "published", archived_at = NULL, updated_at = NOW()
                 WHERE id = :id'
            );
        }
        $stmt->execute([':id' => $announcementId]);
    }

    public function updateAnnouncementSentCounts(int $announcementId, int $sentTotal): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE admin_announcements
             SET sent_count = :sent_count,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            ':sent_count' => max(0, $sentTotal),
            ':id' => $announcementId,
        ]);
    }

    public function deleteAnnouncement(int $announcementId): void
    {
        if ($announcementId < 1) {
            return;
        }

        if ($this->announcementEventsTableExists()) {
            $stmt = $this->pdo->prepare(
                'DELETE FROM admin_announcement_events WHERE announcement_id = :id'
            );
            $stmt->execute([':id' => $announcementId]);
        }

        if ($this->columnExists('notifications', 'announcement_id')) {
            $stmt = $this->pdo->prepare(
                'UPDATE notifications SET announcement_id = NULL WHERE announcement_id = :id'
            );
            $stmt->execute([':id' => $announcementId]);
        }

        $stmt = $this->pdo->prepare(
            'DELETE FROM admin_announcements WHERE id = :id'
        );
        $stmt->execute([':id' => $announcementId]);
    }

    public function logEvent(int $announcementId, ?int $actorId, string $action, ?string $note = null): void
    {
        if (!$this->announcementEventsTableExists()) {
            return;
        }
        $eventId = $this->nextNumericId('admin_announcement_events');
        $stmt = $this->pdo->prepare(
            'INSERT INTO admin_announcement_events (id, announcement_id, actor_id, action, note, created_at)
             VALUES (:id, :announcement_id, :actor_id, :action, :note, NOW())'
        );
        $stmt->execute([
            ':id' => $eventId,
            ':announcement_id' => $announcementId,
            ':actor_id' => $actorId,
            ':action' => $action,
            ':note' => $note,
        ]);
    }

    /**
     * @return array<int,int>
     */
    public function fetchUserIdsForAudience(string $audience): array
    {
        $audience = $this->normalizeAudience($audience);
        $baseSql = 'SELECT id, notification_preferences_json, roles_json FROM users WHERE status = "active"';
        $filterSql = '';
        $fallbackSql = '';

        if ($audience === 'student') {
            $filterSql = " AND JSON_CONTAINS(roles_json, '\\\"student\\\"')";
            $fallbackSql = " AND roles_json LIKE '%\"student\"%'";
        } elseif ($audience === 'teacher') {
            $filterSql = " AND JSON_CONTAINS(roles_json, '\\\"teacher\\\"')";
            $fallbackSql = " AND roles_json LIKE '%\"teacher\"%'";
        }

        $rows = [];
        try {
            $stmt = $this->pdo->query($baseSql . $filterSql . ' ORDER BY id ASC');
            $rows = $stmt->fetchAll() ?: [];
        } catch (\Throwable $e) {
            if ($fallbackSql === '') {
                throw $e;
            }
            $stmt = $this->pdo->query($baseSql . $fallbackSql . ' ORDER BY id ASC');
            $rows = $stmt->fetchAll() ?: [];
        }

        $ids = [];
        $hasPrefs = $this->hasNotificationPreferencesColumn();
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id < 1) {
                continue;
            }
            $roles = NotificationRouting::normalizeRoles((string) ($row['roles_json'] ?? '[]'));
            if (NotificationRouting::isAdminLike($roles)) {
                continue;
            }
            if ($hasPrefs && !$this->allowAnnouncementForUser((string) ($row['notification_preferences_json'] ?? ''))) {
                continue;
            }
            $ids[] = $id;
        }
        return $ids;
    }

    /**
     * @param array<int,int> $userIds
     */
    public function insertNotificationsForUsers(
        array $userIds,
        string $title,
        string $body,
        string $url,
        string $summary,
        string $importance,
        int $announcementId
    ): int {
        if ($userIds === []) {
            return 0;
        }

        $nextId = $this->nextNumericId('notifications');
        $stmt = $this->pdo->prepare(
            'INSERT INTO notifications (id, user_id, title, body, summary, importance, url, announcement_id, created_at)
             VALUES (:id, :uid, :title, :body, :summary, :importance, :url, :announcement_id, NOW())'
        );
        $sentCount = 0;
        foreach ($userIds as $userId) {
            $userId = (int) $userId;
            if ($userId < 1) {
                continue;
            }
            $stmt->execute([
                ':id' => $nextId + $sentCount,
                ':uid' => $userId,
                ':title' => $title,
                ':body' => $body,
                ':summary' => $summary,
                ':importance' => $importance,
                ':url' => $url,
                ':announcement_id' => $announcementId,
            ]);
            $sentCount++;
        }

        return $sentCount;
    }

    public function autoArchiveOldAnnouncements(int $days): void
    {
        $days = max(1, $days);
        $stmt = $this->pdo->prepare(
            'UPDATE admin_announcements
             SET status = "archived", archived_at = NOW(), updated_at = NOW()
             WHERE status = "published" AND created_at < DATE_SUB(NOW(), INTERVAL :days DAY)'
        );
        $stmt->execute([':days' => $days]);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findAnnouncementById(int $announcementId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM admin_announcements WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $announcementId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    public function fetchAnnouncements(array $filters, int $limit = 40, int $offset = 0): array
    {
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);
        $params = [];
        $whereSql = $this->buildWhereClause($filters, $params);

        $stmt = $this->pdo->prepare(
            "SELECT
                aa.id,
                aa.admin_user_id,
                aa.title,
                aa.body,
                aa.summary,
                aa.action_url,
                aa.status,
                aa.audience,
                aa.category,
                aa.importance,
                aa.sent_count,
                aa.read_count,
                aa.click_count,
                aa.published_by,
                aa.approved_by,
                aa.published_at,
                aa.approved_at,
                aa.archived_at,
                aa.created_at,
                aa.updated_at,
                u.first_name AS admin_first_name,
                u.last_name AS admin_last_name,
                p.first_name AS published_first_name,
                p.last_name AS published_last_name,
                ap.first_name AS approved_first_name,
                ap.last_name AS approved_last_name
             FROM admin_announcements aa
             LEFT JOIN users u ON u.id = aa.admin_user_id
             LEFT JOIN users p ON p.id = aa.published_by
             LEFT JOIN users ap ON ap.id = aa.approved_by
             {$whereSql}
             ORDER BY aa.created_at DESC
             LIMIT {$limit} OFFSET {$offset}"
        );
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @param array<string,mixed> $filters
     */
    public function countAnnouncements(array $filters): int
    {
        $params = [];
        $whereSql = $this->buildWhereClause($filters, $params);
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) AS total FROM admin_announcements aa {$whereSql}"
        );
        $stmt->execute($params);
        return (int) ($stmt->fetch()['total'] ?? 0);
    }

    /**
     * @param array<string,mixed> $filters
     */
    public function sumAnnouncementSentCount(array $filters): int
    {
        $params = [];
        $whereSql = $this->buildWhereClause($filters, $params);
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(SUM(aa.sent_count), 0) AS total FROM admin_announcements aa {$whereSql}"
        );
        $stmt->execute($params);
        return (int) ($stmt->fetch()['total'] ?? 0);
    }

    /**
     * @param array<int,int> $announcementIds
     * @return array<int,array<int,array<string,mixed>>>
     */
    public function fetchAnnouncementEvents(array $announcementIds, int $limitPer = 3): array
    {
        if ($announcementIds === [] || !$this->announcementEventsTableExists()) {
            return [];
        }
        $limitPer = max(1, min(10, $limitPer));
        $ids = array_values(array_filter(array_map('intval', $announcementIds), static fn (int $id): bool => $id > 0));
        if ($ids === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT e.id, e.announcement_id, e.actor_id, e.action, e.note, e.created_at,
                    u.first_name AS actor_first_name, u.last_name AS actor_last_name
             FROM admin_announcement_events e
             LEFT JOIN users u ON u.id = e.actor_id
             WHERE e.announcement_id IN ({$placeholders})
             ORDER BY e.created_at DESC"
        );
        $stmt->execute($ids);
        $rows = $stmt->fetchAll() ?: [];
        $grouped = [];
        foreach ($rows as $row) {
            $aid = (int) ($row['announcement_id'] ?? 0);
            if ($aid < 1) {
                continue;
            }
            if (!isset($grouped[$aid])) {
                $grouped[$aid] = [];
            }
            if (count($grouped[$aid]) >= $limitPer) {
                continue;
            }
            $grouped[$aid][] = $row;
        }
        return $grouped;
    }

    private function normalizeAudience(string $audience): string
    {
        $audience = strtolower(trim($audience));
        if (!in_array($audience, ['all', 'student', 'teacher'], true)) {
            return 'all';
        }
        return $audience;
    }

    private function hasNotificationPreferencesColumn(): bool
    {
        if ($this->hasNotificationPrefsColumn !== null) {
            return $this->hasNotificationPrefsColumn;
        }
        try {
            $this->hasNotificationPrefsColumn = $this->columnExists('users', 'notification_preferences_json');
        } catch (\Throwable $e) {
            $this->hasNotificationPrefsColumn = false;
        }
        return $this->hasNotificationPrefsColumn;
    }

    private function allowAnnouncementForUser(string $prefsJson): bool
    {
        $prefsJson = trim($prefsJson);
        if ($prefsJson === '') {
            return true;
        }
        $decoded = json_decode($prefsJson, true);
        if (!is_array($decoded)) {
            return true;
        }
        if (!array_key_exists('popup_announcements', $decoded)) {
            return true;
        }
        return !empty($decoded['popup_announcements']);
    }

    private function nextNumericId(string $table): int
    {
        $allowedTables = [
            'admin_announcements',
            'admin_announcement_events',
            'notifications',
        ];
        if (!in_array($table, $allowedTables, true)) {
            throw new \InvalidArgumentException('Unsupported table for manual id generation.');
        }

        $stmt = $this->pdo->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM {$table}");
        return (int) ($stmt->fetch()['next_id'] ?? 1);
    }

    /**
     * @param array<string,mixed> $filters
     * @param array<string,mixed> $params
     */
    private function buildWhereClause(array $filters, array &$params): string
    {
        $where = [];
        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '' && in_array($status, ['draft', 'pending_review', 'published', 'archived'], true)) {
            $where[] = 'aa.status = :status';
            $params[':status'] = $status;
        }
        $audience = trim((string) ($filters['audience'] ?? ''));
        if ($audience !== '' && in_array($audience, ['all', 'student', 'teacher'], true)) {
            $where[] = 'aa.audience = :audience';
            $params[':audience'] = $audience;
        }
        $category = trim((string) ($filters['category'] ?? ''));
        if ($category !== '' && in_array($category, ['general', 'system', 'learning', 'support', 'promo'], true)) {
            $where[] = 'aa.category = :category';
            $params[':category'] = $category;
        }
        $importance = trim((string) ($filters['importance'] ?? ''));
        if ($importance !== '' && in_array($importance, ['info', 'important', 'urgent'], true)) {
            $where[] = 'aa.importance = :importance';
            $params[':importance'] = $importance;
        }
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $where[] = '(aa.title LIKE :search OR aa.body LIKE :search OR aa.summary LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        if ($where === []) {
            return '';
        }
        return 'WHERE ' . implode(' AND ', $where);
    }
}
?>
