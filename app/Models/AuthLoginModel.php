<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class AuthLoginModel extends Model
{
    public function findUserByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}

