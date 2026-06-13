<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class SharedPlatformSettingsModel extends Model
{
    public function ensureTable(): bool
    {
        return $this->tableExists('platform_settings');
    }

    public function findValue(string $key): ?string
    {
        $stmt = $this->pdo->prepare('SELECT setting_value FROM platform_settings WHERE setting_key = :k ORDER BY updated_at DESC LIMIT 1');
        $stmt->execute([':k' => $key]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        return (string) ($row['setting_value'] ?? '');
    }

    public function insertDefault(string $key, string $value): void
    {
        $exists = $this->pdo->prepare('SELECT COUNT(*) FROM platform_settings WHERE setting_key = :k');
        $exists->execute([':k' => $key]);
        if ((int) $exists->fetchColumn() > 0) {
            return;
        }

        $stmt = $this->pdo->prepare('INSERT INTO platform_settings (setting_key, setting_value) VALUES (:k, :v)');
        $stmt->execute([':k' => $key, ':v' => $value]);
    }

    public function upsert(string $key, string $value): void
    {
        $exists = $this->pdo->prepare('SELECT COUNT(*) FROM platform_settings WHERE setting_key = :k');
        $exists->execute([':k' => $key]);
        if ((int) $exists->fetchColumn() > 0) {
            $stmt = $this->pdo->prepare('UPDATE platform_settings SET setting_value = :v, updated_at = NOW() WHERE setting_key = :k');
            $stmt->execute([
                ':k' => $key,
                ':v' => $value,
            ]);
            return;
        }

        $stmt = $this->pdo->prepare('INSERT INTO platform_settings (setting_key, setting_value) VALUES (:k, :v)');
        $stmt->execute([
            ':k' => $key,
            ':v' => $value,
        ]);
    }
}
