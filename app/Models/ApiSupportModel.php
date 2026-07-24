<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class ApiSupportModel extends Model
{
    /** @var array<string,int|null> */
    private array $autoIncrementCache = [];
    private ?bool $hasConversationCategoryColumn = null;
    private ?bool $hasGuestSupportColumns = null;

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

    public function findConversationById(int $conversationId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM support_conversations WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $conversationId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function listConversations(
        int $userId,
        bool $isStaff,
        string $scope,
        string $status,
        string $assigned,
        int $limit,
        ?string $viewerKey = null
    ): array {
        $params = [];
        $where = [];
        $viewerKey = trim((string) $viewerKey);
        $guestMode = $userId < 1 && $viewerKey !== '';
        if ($guestMode && !$this->hasGuestSupportColumns()) {
            return [];
        }

        if (!$isStaff || $scope !== 'all') {
            if ($guestMode) {
                $where[] = 'c.viewer_key = :viewer_key';
                $params[':viewer_key'] = $viewerKey;
            } else {
                $where[] = 'c.created_by = :uid_owner';
                $params[':uid_owner'] = $userId;
            }
        }
        if ($status !== '') {
            $where[] = 'c.status = :status';
            $params[':status'] = $status;
        }
        if ($isStaff && $scope === 'all') {
            if ($assigned === 'me') {
                $where[] = 'c.assigned_to = :uid_assigned';
                $params[':uid_assigned'] = $userId;
            } elseif ($assigned === 'none') {
                $where[] = 'c.assigned_to IS NULL';
            }
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $unreadSql = $guestMode
            ? '0 AS unread_count'
            : '(SELECT COUNT(*) FROM support_messages m
                    WHERE m.conversation_id = c.id
                      AND m.id > COALESCE(r.last_read_message_id, 0)
                      AND m.sender_id <> :uid_unread) AS unread_count';

        $sql = "SELECT
                c.*,
                u.first_name AS creator_first,
                u.last_name AS creator_last,
                a.first_name AS assigned_first,
                a.last_name AS assigned_last,
                (SELECT m.id FROM support_messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_message_id,
                (SELECT m.body FROM support_messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_message_body,
                {$unreadSql}
            FROM support_conversations c
            LEFT JOIN users u ON u.id = c.created_by
            LEFT JOIN users a ON a.id = c.assigned_to";

        if (!$guestMode) {
            $sql .= ' LEFT JOIN support_conversation_reads r ON r.conversation_id = c.id AND r.user_id = :uid_read';
            $params[':uid_read'] = $userId;
            $params[':uid_unread'] = $userId;
        }

        $sql .= " {$whereSql}
            ORDER BY c.last_message_at DESC
            LIMIT {$limit}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    public function insertConversation(
        ?int $createdBy,
        string $subject,
        string $status,
        string $category = 'other',
        ?string $viewerKey = null,
        ?string $guestName = null,
        ?string $guestEmail = null
    ): int
    {
        $table = 'support_conversations';
        if ($createdBy === null && !$this->hasGuestSupportColumns()) {
            throw new \RuntimeException('Guest support schema is missing.');
        }
        $id = $this->generatePrimaryId($table);
        $usesAutoIncrement = $this->usesAutoIncrement($table);

        $columns = ['created_by', 'subject', 'status'];
        $values = [':uid', ':subject', ':status'];
        $params = [
            ':uid' => $createdBy,
            ':subject' => $subject,
            ':status' => $status,
        ];

        if ($this->hasConversationCategoryColumn()) {
            $columns[] = 'category';
            $values[] = ':category';
            $params[':category'] = $category;
        }

        if ($createdBy === null) {
            $viewerKey = trim((string) $viewerKey);
            $guestName = trim((string) $guestName);
            $guestEmail = trim((string) $guestEmail);
            if ($viewerKey === '') {
                throw new \RuntimeException('Guest viewer key is missing.');
            }

            $columns[] = 'viewer_key';
            $values[] = ':viewer_key';
            $params[':viewer_key'] = $viewerKey;

            if ($guestName === '') {
                $guestName = 'Visiteur';
            }
            $columns[] = 'guest_name';
            $values[] = ':guest_name';
            $params[':guest_name'] = $guestName;

            if ($guestEmail !== '') {
                $columns[] = 'guest_email';
                $values[] = ':guest_email';
                $params[':guest_email'] = $guestEmail;
            }
        }

        $columns[] = 'last_message_at';
        $values[] = 'NOW()';
        $columns[] = 'created_at';
        $values[] = 'NOW()';
        $columns[] = 'updated_at';
        $values[] = 'NOW()';

        if (!$usesAutoIncrement) {
            array_unshift($columns, 'id');
            array_unshift($values, ':id');
            $params[':id'] = $id;
        }

        $columnSql = implode(', ', $columns);
        $valueSql = implode(', ', $values);
        $stmt = $this->pdo->prepare(
            "INSERT INTO support_conversations ({$columnSql}) VALUES ({$valueSql})"
        );
        $stmt->execute($params);

        if ($usesAutoIncrement) {
            return (int) $this->pdo->lastInsertId();
        }

        return $id;
    }

    public function insertMessage(int $conversationId, ?int $senderId, string $body): int
    {
        $table = 'support_messages';
        $id = $this->generatePrimaryId($table);

        if ($this->usesAutoIncrement($table)) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO support_messages (conversation_id, sender_id, body, created_at)
                 VALUES (:cid, :uid, :body, NOW())'
            );
            $stmt->execute([
                ':cid' => $conversationId,
                ':uid' => $senderId,
                ':body' => $body,
            ]);

            return (int) $this->pdo->lastInsertId();
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO support_messages (id, conversation_id, sender_id, body, created_at)
             VALUES (:id, :cid, :uid, :body, NOW())'
        );
        $stmt->execute([
            ':id' => $id,
            ':cid' => $conversationId,
            ':uid' => $senderId,
            ':body' => $body,
        ]);

        return $id;
    }

    public function insertAttachments(int $messageId, array $attachments): void
    {
        if ($attachments === []) {
            return;
        }

        $usesAutoIncrement = $this->usesAutoIncrement('support_message_attachments');
        $stmt = $usesAutoIncrement
            ? $this->pdo->prepare(
                'INSERT INTO support_message_attachments (message_id, file_path, original_name, mime, size, created_at)
                 VALUES (:mid, :path, :name, :mime, :size, NOW())'
            )
            : $this->pdo->prepare(
                'INSERT INTO support_message_attachments (id, message_id, file_path, original_name, mime, size, created_at)
                 VALUES (:id, :mid, :path, :name, :mime, :size, NOW())'
            );

        foreach ($attachments as $attachment) {
            $params = [
                ':mid' => $messageId,
                ':path' => (string) ($attachment['file_path'] ?? ''),
                ':name' => (string) ($attachment['original_name'] ?? ''),
                ':mime' => (string) ($attachment['mime'] ?? 'application/octet-stream'),
                ':size' => (int) ($attachment['size'] ?? 0),
            ];
            if (!$usesAutoIncrement) {
                $params[':id'] = $this->generatePrimaryId('support_message_attachments');
            }

            $stmt->execute($params);
        }
    }

    public function upsertConversationRead(int $conversationId, int $userId, int $messageId, bool $keepGreatest = false): void
    {
        $existing = $this->findConversationRead($conversationId, $userId);
        if ($existing !== null) {
            $nextMessageId = $keepGreatest
                ? max($existing, $messageId)
                : $messageId;

            $stmt = $this->pdo->prepare(
                'UPDATE support_conversation_reads
                 SET last_read_message_id = :mid, updated_at = NOW()
                 WHERE conversation_id = :cid AND user_id = :uid'
            );
            $stmt->execute([
                ':mid' => $nextMessageId,
                ':cid' => $conversationId,
                ':uid' => $userId,
            ]);

            return;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO support_conversation_reads (conversation_id, user_id, last_read_message_id, updated_at)
             VALUES (:cid, :uid, :mid, NOW())'
        );
        $stmt->execute([
            ':cid' => $conversationId,
            ':uid' => $userId,
            ':mid' => $messageId,
        ]);
    }

    public function updateConversationAssign(int $conversationId, ?int $assignedTo): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE support_conversations
             SET assigned_to = :aid, updated_at = NOW()
             WHERE id = :cid'
        );
        $stmt->execute([
            ':aid' => $assignedTo,
            ':cid' => $conversationId,
        ]);
    }

    public function updateConversationStatus(int $conversationId, string $status): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE support_conversations
             SET status = :status, updated_at = NOW()
             WHERE id = :cid'
        );
        $stmt->execute([
            ':status' => $status,
            ':cid' => $conversationId,
        ]);
    }

    public function updateConversationAfterMessage(
        int $conversationId,
        string $status,
        ?int $assignedTo
    ): void {
        $stmt = $this->pdo->prepare(
            'UPDATE support_conversations
             SET status = :status, assigned_to = :assigned, last_message_at = NOW(), updated_at = NOW()
             WHERE id = :cid'
        );
        $stmt->execute([
            ':status' => $status,
            ':assigned' => $assignedTo,
            ':cid' => $conversationId,
        ]);
    }

    public function listMessages(int $conversationId, int $sinceId): array
    {
        if ($sinceId > 0) {
            $stmt = $this->pdo->prepare(
                'SELECT m.id, m.sender_id, m.body, m.created_at, u.first_name, u.last_name, c.viewer_key, c.guest_name
                 FROM support_messages m
                 LEFT JOIN users u ON u.id = m.sender_id
                 JOIN support_conversations c ON c.id = m.conversation_id
                 WHERE m.conversation_id = :cid AND m.id > :since
                 ORDER BY m.id ASC
                 LIMIT 200'
            );
            $stmt->execute([
                ':cid' => $conversationId,
                ':since' => $sinceId,
            ]);
            return $stmt->fetchAll() ?: [];
        }

        $stmt = $this->pdo->prepare(
            'SELECT t.id, t.sender_id, t.body, t.created_at, t.first_name, t.last_name, t.viewer_key, t.guest_name
             FROM (
                SELECT m.id, m.sender_id, m.body, m.created_at, u.first_name, u.last_name, c.viewer_key, c.guest_name
                FROM support_messages m
                LEFT JOIN users u ON u.id = m.sender_id
                JOIN support_conversations c ON c.id = m.conversation_id
                WHERE m.conversation_id = :cid
                ORDER BY m.id DESC
                LIMIT 200
             ) AS t
             ORDER BY t.id ASC'
        );
        $stmt->execute([':cid' => $conversationId]);
        return $stmt->fetchAll() ?: [];
    }

    public function listAttachmentsByMessageIds(array $messageIds): array
    {
        if ($messageIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($messageIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT id, message_id, original_name, mime, size
             FROM support_message_attachments
             WHERE message_id IN ({$placeholders})
             ORDER BY id ASC"
        );
        $stmt->execute($messageIds);
        return $stmt->fetchAll() ?: [];
    }

    public function listStaffIds(): array
    {
        try {
            $rows = $this->pdo
                ->query(
                    'SELECT u.id
                     FROM users u
                     LEFT JOIN admin_secondary_permissions asp
                        ON asp.user_id = u.id AND asp.is_active = 1
                     WHERE u.roles_json LIKE \'%"admin"%\'
                        OR u.roles_json LIKE \'%"support"%\'
                        OR (
                            u.roles_json LIKE \'%"admin_secondary"%\'
                            AND asp.permissions_json LIKE \'%"support"%\'
                        )'
                )
                ->fetchAll();
        } catch (\Throwable $e) {
            $rows = $this->pdo
                ->query('SELECT id FROM users WHERE roles_json LIKE \'%"admin"%\' OR roles_json LIKE \'%"support"%\'')
                ->fetchAll();
        }
        $ids = [];
        foreach ($rows ?: [] as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        return $ids;
    }

    public function createNotification(int $userId, string $title, string $body, ?string $url): void
    {
        $table = 'notifications';
        $id = $this->generatePrimaryId($table);

        if ($this->usesAutoIncrement($table)) {
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

            return;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO notifications (id, user_id, title, body, url, created_at)
             VALUES (:id, :uid, :title, :body, :url, NOW())'
        );
        $stmt->execute([
            ':id' => $id,
            ':uid' => $userId,
            ':title' => $title,
            ':body' => $body,
            ':url' => $url,
        ]);
    }

    private function findConversationRead(int $conversationId, int $userId): ?int
    {
        $stmt = $this->pdo->prepare(
            'SELECT MAX(last_read_message_id) AS last_read_message_id
             FROM support_conversation_reads
             WHERE conversation_id = :cid AND user_id = :uid'
        );
        $stmt->execute([
            ':cid' => $conversationId,
            ':uid' => $userId,
        ]);
        $row = $stmt->fetch();
        if (!is_array($row) || $row['last_read_message_id'] === null) {
            return null;
        }

        return (int) $row['last_read_message_id'];
    }

    private function usesAutoIncrement(string $table): bool
    {
        if (array_key_exists($table, $this->autoIncrementCache)) {
            return $this->autoIncrementCache[$table] === 1;
        }

        $driver = (string) $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $this->pdo->prepare(
                "SELECT sql
                 FROM sqlite_master
                 WHERE type IN ('table', 'view')
                   AND name = :table
                 LIMIT 1"
            );
            $stmt->execute([':table' => $table]);
            $row = $stmt->fetch();
            $createSql = strtolower((string) ($row['sql'] ?? ''));
            $usesAutoIncrement = str_contains($createSql, 'autoincrement');
        } else {
            $stmt = $this->pdo->prepare('SHOW CREATE TABLE `' . $table . '`');
            $stmt->execute();
            $row = $stmt->fetch();
            $createSql = strtolower((string) ($row['Create Table'] ?? $row['create table'] ?? ''));
            $usesAutoIncrement = str_contains($createSql, 'auto_increment');
        }
        $this->autoIncrementCache[$table] = $usesAutoIncrement ? 1 : 0;

        return $usesAutoIncrement;
    }

    private function hasConversationCategoryColumn(): bool
    {
        if ($this->hasConversationCategoryColumn !== null) {
            return $this->hasConversationCategoryColumn;
        }

        try {
            $this->hasConversationCategoryColumn = $this->columnExists('support_conversations', 'category');
        } catch (\Throwable $e) {
            $this->hasConversationCategoryColumn = false;
        }

        return $this->hasConversationCategoryColumn;
    }

    private function hasGuestSupportColumns(): bool
    {
        if ($this->hasGuestSupportColumns !== null) {
            return $this->hasGuestSupportColumns;
        }

        try {
            $this->hasGuestSupportColumns =
                $this->columnExists('support_conversations', 'viewer_key')
                && $this->columnExists('support_conversations', 'guest_name')
                && $this->columnExists('support_conversations', 'guest_email');
        } catch (\Throwable $e) {
            $this->hasGuestSupportColumns = false;
        }

        return $this->hasGuestSupportColumns;
    }

    private function generatePrimaryId(string $table): int
    {
        if ($this->usesAutoIncrement($table)) {
            return 0;
        }

        $allowed = [
            'support_conversations',
            'support_messages',
            'support_message_attachments',
            'notifications',
        ];
        if (!in_array($table, $allowed, true)) {
            throw new \InvalidArgumentException('Unsupported table for manual id generation.');
        }

        $stmt = $this->pdo->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM {$table}");
        $nextId = (int) ($stmt->fetchColumn() ?: 1);

        return max(1, $nextId);
    }
}
