<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class ApiCatalogEventsModel extends Model
{
    public function schemaReady(): bool
    {
        return $this->tableExists('catalog_event_logs');
    }

    public function insertEvent(
        string $eventType,
        string $blockKey,
        ?int $courseId,
        ?int $userId,
        ?string $sessionKey,
        ?string $metadataJson
    ): void {
        if (!$this->schemaReady()) {
            return;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO catalog_event_logs (
                event_type, block_key, course_id, user_id, session_key, metadata_json, created_at
             ) VALUES (
                :event_type, :block_key, :course_id, :user_id, :session_key, :metadata_json, NOW()
             )'
        );
        $stmt->execute([
            ':event_type' => $eventType,
            ':block_key' => $blockKey,
            ':course_id' => $courseId !== null && $courseId > 0 ? $courseId : null,
            ':user_id' => $userId !== null && $userId > 0 ? $userId : null,
            ':session_key' => $sessionKey !== '' ? $sessionKey : null,
            ':metadata_json' => $metadataJson !== '' ? $metadataJson : null,
        ]);
    }
}
