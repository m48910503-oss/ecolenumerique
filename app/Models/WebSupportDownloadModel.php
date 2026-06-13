<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class WebSupportDownloadModel extends Model
{
    public function findAttachmentWithConversation(int $attachmentId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.id, a.file_path, a.original_name, a.mime, a.size,
                    m.conversation_id, c.created_by
             FROM support_message_attachments a
             JOIN support_messages m ON m.id = a.message_id
             JOIN support_conversations c ON c.id = m.conversation_id
             WHERE a.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $attachmentId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}

