<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class WebSeoModel extends Model
{
    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchSitemapCourses(int $limit = 50000): array
    {
        $limit = max(1, min(50000, $limit));
        $stmt = $this->pdo->prepare(
            "SELECT c.id, c.slug, c.updated_at, c.created_at
             FROM courses c
             WHERE c.status = 'approved'
               AND c.deleted_at IS NULL
               AND NOT EXISTS (
                    SELECT 1
                    FROM courses c2
                    WHERE c2.status = 'approved'
                      AND c2.deleted_at IS NULL
                      AND COALESCE(c2.version_of, c2.id) = COALESCE(c.version_of, c.id)
                      AND (c2.created_at > c.created_at OR (c2.created_at = c.created_at AND c2.id > c.id))
               )
             ORDER BY COALESCE(c.updated_at, c.created_at) DESC, c.id DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }
}
