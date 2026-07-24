<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class UserModel extends Model
{
    public function findAdminIds(): array
    {
        $rows = $this->pdo->query('SELECT id FROM users WHERE roles_json LIKE \'%"admin"%\'')->fetchAll() ?: [];
        return array_values(
            array_filter(
                array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $rows),
                static fn(int $id): bool => $id > 0
            )
        );
    }
}
