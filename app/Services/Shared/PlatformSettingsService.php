<?php
declare(strict_types=1);

namespace App\Services\Shared;

use App\Core\Service;

use App\Models\SharedPlatformSettingsModel;
use Throwable;

final class PlatformSettingsService extends Service
{
    private SharedPlatformSettingsModel $model;
    /** @var array<string,string>|null */
    private ?array $teacherWithdrawalMethodCatalog = null;

    public function __construct(SharedPlatformSettingsModel $model)
    {
        $this->model = $model;
    }

    public function ensureTable(): bool
    {
        try {
            return $this->model->ensureTable();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function get(string $key, string $default = ''): string
    {
        if (!$this->ensureTable()) {
            return $default;
        }
        try {
            $value = $this->model->findValue($key);
            if ($value !== null) {
                return $value;
            }
            $this->model->insertDefault($key, $default);
            return $default;
        } catch (Throwable $e) {
            return $default;
        }
    }

    public function set(string $key, string $value): bool
    {
        if (!$this->ensureTable()) {
            return false;
        }
        try {
            $this->model->upsert($key, $value);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function getInt(string $key, int $default = 0): int
    {
        return (int) $this->get($key, (string) $default);
    }

    public function getFloat(string $key, float $default = 0.0): float
    {
        return (float) $this->get($key, (string) $default);
    }

    public function siteLogoUrl(): string
    {
        $path = trim($this->get('site_logo_path', ''));
        if ($path === '') {
            return '';
        }
        if (preg_match('/^https?:\\/\\//i', $path) === 1) {
            return $path;
        }
        return $this->siteUrl(ltrim($path, '/'));
    }

    public function siteFaviconUrl(): string
    {
        $path = trim($this->get('site_favicon_path', ''));
        if ($path !== '') {
            if (preg_match('/^https?:\\/\\//i', $path) === 1) {
                return $path;
            }
            return $this->siteUrl(ltrim($path, '/'));
        }

        return $this->siteLogoUrl();
    }

    public function footerText(): string
    {
        return $this->get('site_footer_text', '');
    }

    /**
     * @return array<string,string>
     */
    public function teacherWithdrawalMethodCatalog(): array
    {
        if (is_array($this->teacherWithdrawalMethodCatalog)) {
            return $this->teacherWithdrawalMethodCatalog;
        }

        $this->teacherWithdrawalMethodCatalog = [
            'fedapay' => 'Mobile Money',
            'crypto' => defined('PAY_CRYPTO_LABEL') && PAY_CRYPTO_LABEL !== ''
                ? (string) PAY_CRYPTO_LABEL
                : 'Crypto',
        ];

        return $this->teacherWithdrawalMethodCatalog;
    }

    /**
     * @return string[]
     */
    public function teacherWithdrawalMethodsEnabled(): array
    {
        $catalog = $this->teacherWithdrawalMethodCatalog();
        $stored = $this->get('teacher_withdrawal_methods', implode(',', array_keys($catalog)));
        $enabled = [];

        foreach (preg_split('/\s*,\s*/', strtolower(trim($stored))) ?: [] as $method) {
            if ($method === '' || !isset($catalog[$method]) || in_array($method, $enabled, true)) {
                continue;
            }
            $enabled[] = $method;
        }

        if ($enabled !== []) {
            return $enabled;
        }

        return array_keys($catalog);
    }

    /**
     * @return array<string,string>
     */
    public function teacherWithdrawalMethodOptions(): array
    {
        $catalog = $this->teacherWithdrawalMethodCatalog();
        $options = [];

        foreach ($this->teacherWithdrawalMethodsEnabled() as $method) {
            if (isset($catalog[$method])) {
                $options[$method] = $catalog[$method];
            }
        }

        return $options;
    }
}

