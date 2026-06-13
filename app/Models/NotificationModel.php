<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class NotificationModel extends Model
{
    public function create(int $userId, string $title, string $body, ?string $url = null): void
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
}
