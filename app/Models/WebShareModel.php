<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class WebShareModel extends Model
{
    public function findApprovedCourseByToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, slug
             FROM courses
             WHERE share_token = :token
               AND status = "approved"
               AND deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}

