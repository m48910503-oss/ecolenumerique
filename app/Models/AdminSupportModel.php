<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class AdminSupportModel extends Model
{
    public function conversationExists(int $conversationId): bool
    {
        if ($conversationId < 1) {
            return false;
        }

        $stmt = $this->pdo->prepare('SELECT id FROM support_conversations WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $conversationId]);
        return (bool) $stmt->fetchColumn();
    }
}

