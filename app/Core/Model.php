<?php
declare(strict_types=1);

namespace App\Core;

abstract class Model
{
    protected \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    protected function tableExists(string $table): bool
    {
        $driver = (string) $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $this->pdo->prepare(
                "SELECT 1 FROM sqlite_master WHERE type IN ('table', 'view') AND name = :table LIMIT 1"
            );
            $stmt->execute([':table' => $table]);
            return (bool) $stmt->fetchColumn();
        }

        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table LIMIT 1'
        );
        $stmt->execute([':table' => $table]);
        return (bool) $stmt->fetchColumn();
    }

    protected function columnExists(string $table, string $column): bool
    {
        $driver = (string) $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
                return false;
            }
            $stmt = $this->pdo->query("PRAGMA table_info({$table})");
            if (!$stmt) {
                return false;
            }
            $columns = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            foreach ($columns as $row) {
                if (trim((string) ($row['name'] ?? '')) === $column) {
                    return true;
                }
            }
            return false;
        }

        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column LIMIT 1'
        );
        $stmt->execute([
            ':table' => $table,
            ':column' => $column,
        ]);
        return (bool) $stmt->fetchColumn();
    }
}
