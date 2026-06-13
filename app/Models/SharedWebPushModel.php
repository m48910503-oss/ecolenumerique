<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use Throwable;

final class SharedWebPushModel extends Model
{
    public function schemaReady(): bool
    {
        return $this->tableExists('notifications')
            && $this->tableExists('push_subscriptions')
            && $this->tableExists('push_notification_queue');
    }

    public function upsertSubscription(
        int $userId,
        string $endpointHash,
        string $endpoint,
        string $p256dh,
        string $auth,
        string $contentEncoding,
        ?string $userAgent
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO push_subscriptions (
                user_id, endpoint_hash, endpoint, p256dh, auth, content_encoding, user_agent, is_active, failure_count, created_at, updated_at
             ) VALUES (
                :uid, :endpoint_hash, :endpoint, :p256dh, :auth, :content_encoding, :user_agent, 1, 0, NOW(), NOW()
             )
             ON DUPLICATE KEY UPDATE
                user_id = VALUES(user_id),
                endpoint = VALUES(endpoint),
                p256dh = VALUES(p256dh),
                auth = VALUES(auth),
                content_encoding = VALUES(content_encoding),
                user_agent = VALUES(user_agent),
                is_active = 1,
                failure_count = 0,
                updated_at = NOW()'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':endpoint_hash' => $endpointHash,
            ':endpoint' => $endpoint,
            ':p256dh' => $p256dh,
            ':auth' => $auth,
            ':content_encoding' => $contentEncoding,
            ':user_agent' => $userAgent,
        ]);
    }

    public function deactivateSubscriptionByHash(int $userId, string $endpointHash): void
    {
        if ($userId < 1 || $endpointHash === '') {
            return;
        }
        $stmt = $this->pdo->prepare(
            'UPDATE push_subscriptions
             SET is_active = 0, updated_at = NOW()
             WHERE user_id = :uid
               AND endpoint_hash = :endpoint_hash'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':endpoint_hash' => $endpointHash,
        ]);
    }

    public function markSubscriptionSuccess(int $subscriptionId): void
    {
        if ($subscriptionId < 1) {
            return;
        }
        $stmt = $this->pdo->prepare(
            'UPDATE push_subscriptions
             SET failure_count = 0,
                 last_success_at = NOW(),
                 updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([':id' => $subscriptionId]);
    }

    public function markSubscriptionFailure(int $subscriptionId, bool $deactivate): void
    {
        if ($subscriptionId < 1) {
            return;
        }
        $stmt = $this->pdo->prepare(
            'UPDATE push_subscriptions
             SET failure_count = failure_count + 1,
                 last_failure_at = NOW(),
                 is_active = CASE WHEN :deactivate = 1 THEN 0 ELSE is_active END,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            ':id' => $subscriptionId,
            ':deactivate' => $deactivate ? 1 : 0,
        ]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findActiveSubscriptionsByUser(int $userId): array
    {
        if ($userId < 1) {
            return [];
        }
        $stmt = $this->pdo->prepare(
            'SELECT id, endpoint_hash, endpoint, p256dh, auth, content_encoding, failure_count
             FROM push_subscriptions
             WHERE user_id = :uid
               AND is_active = 1
             ORDER BY id ASC'
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function claimPendingQueueJobs(int $limit): array
    {
        $safeLimit = max(1, min(50, $limit));

        try {
            if (!$this->pdo->beginTransaction()) {
                return [];
            }

            $stmt = $this->pdo->prepare(
                'SELECT id
                 FROM push_notification_queue
                 WHERE status = "pending"
                   AND next_attempt_at <= NOW()
                 ORDER BY id ASC
                 LIMIT ' . $safeLimit . '
                 FOR UPDATE'
            );
            $stmt->execute();
            $ids = array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $stmt->fetchAll() ?: []);
            $ids = array_values(array_filter($ids, static fn(int $id): bool => $id > 0));
            if ($ids === []) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->commit();
                }
                return [];
            }

            $in = implode(',', array_fill(0, count($ids), '?'));
            $upd = $this->pdo->prepare(
                'UPDATE push_notification_queue
                 SET status = "processing", updated_at = NOW()
                 WHERE id IN (' . $in . ')'
            );
            $upd->execute($ids);

            $sel = $this->pdo->prepare(
                'SELECT id, notification_id, user_id, title, body, url, attempt_count
                 FROM push_notification_queue
                 WHERE id IN (' . $in . ')
                 ORDER BY id ASC'
            );
            $sel->execute($ids);
            $rows = $sel->fetchAll() ?: [];

            if ($this->pdo->inTransaction()) {
                $this->pdo->commit();
            }
            return $rows;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return [];
        }
    }

    public function markQueueSent(int $queueId): void
    {
        if ($queueId < 1) {
            return;
        }
        $stmt = $this->pdo->prepare(
            'UPDATE push_notification_queue
             SET status = "sent",
                 sent_at = NOW(),
                 updated_at = NOW(),
                 last_error = NULL
             WHERE id = :id'
        );
        $stmt->execute([':id' => $queueId]);
    }

    public function markQueueRetry(int $queueId, int $nextDelaySeconds, string $error): void
    {
        if ($queueId < 1) {
            return;
        }
        $stmt = $this->pdo->prepare(
            'UPDATE push_notification_queue
             SET status = "pending",
                 attempt_count = attempt_count + 1,
                 last_error = :err,
                 next_attempt_at = DATE_ADD(NOW(), INTERVAL :delay SECOND),
                 updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            ':id' => $queueId,
            ':delay' => max(5, min(3600, $nextDelaySeconds)),
            ':err' => $this->trimError($error),
        ]);
    }

    public function markQueueFailed(int $queueId, string $error): void
    {
        if ($queueId < 1) {
            return;
        }
        $stmt = $this->pdo->prepare(
            'UPDATE push_notification_queue
             SET status = "failed",
                 attempt_count = attempt_count + 1,
                 last_error = :err,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            ':id' => $queueId,
            ':err' => $this->trimError($error),
        ]);
    }

    private function trimError(string $error): string
    {
        $error = trim($error);
        if ($error === '') {
            return 'unknown_error';
        }
        if (function_exists('mb_substr')) {
            return (string) mb_substr($error, 0, 250);
        }
        return substr($error, 0, 250);
    }
}
