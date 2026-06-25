<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class AdminSiteSettingsModel extends Model
{
    private function ensureTable(): bool
    {
        return $this->tableExists('platform_settings');
    }

    public function findSetting(string $key): ?string
    {
        if (!$this->ensureTable()) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT setting_value FROM platform_settings WHERE setting_key = :k ORDER BY updated_at DESC LIMIT 1');
        $stmt->execute([':k' => $key]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        return (string) ($row['setting_value'] ?? '');
    }

    public function getSetting(string $key, string $default = ''): string
    {
        if (!$this->ensureTable()) {
            return $default;
        }

        $value = $this->findSetting($key);
        if ($value === null) {
            $ins = $this->pdo->prepare('INSERT INTO platform_settings (setting_key, setting_value) VALUES (:k, :v)');
            $ins->execute([':k' => $key, ':v' => $default]);
            return $default;
        }

        return $value;
    }

    public function setSetting(string $key, string $value): void
    {
        if (!$this->ensureTable()) {
            return;
        }
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

    public function deleteSetting(string $key): void
    {
        if (!$this->ensureTable()) {
            return;
        }

        $stmt = $this->pdo->prepare('DELETE FROM platform_settings WHERE setting_key = :k');
        $stmt->execute([':k' => $key]);
    }
}
