<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

final class PdoRegistry
{
    private static ?PDO $pdo = null;

    public static function set(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    public static function get(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }
        throw new RuntimeException('PDO registry is not initialized.');
    }
}

