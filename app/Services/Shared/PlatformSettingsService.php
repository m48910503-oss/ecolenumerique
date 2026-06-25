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
        return $this->pathToUrl($this->get('site_logo_path', ''));
    }

    public function siteFaviconUrl(): string
    {
        $logoUrl = $this->siteLogoUrl();
        if ($logoUrl !== '') {
            return $logoUrl;
        }

        return 'data:image/svg+xml,%3Csvg xmlns%3D%22http://www.w3.org/2000/svg%22/%3E';
    }

    public function footerText(): string
    {
        return $this->get('site_footer_text', '');
    }

    /**
     * @return array{
     *   siteName:string,
     *   siteTagline:string,
     *   siteDescription:string,
     *   supportEmail:string,
     *   supportPhone:string,
     *   supportWhatsapp:string,
     *   siteAddress:string,
     *   footerText:string,
     *   siteLogoUrl:string
     * }
     */
    public function publicIdentity(): array
    {
        $siteName = trim($this->get('site_name', defined('APP_FROM_NAME') ? (string) APP_FROM_NAME : 'École Numérique'));
        if ($siteName === '') {
            $siteName = defined('APP_FROM_NAME') ? (string) APP_FROM_NAME : 'École Numérique';
        }

        return [
            'siteName' => $siteName,
            'siteTagline' => trim($this->get('site_tagline', '')),
            'siteDescription' => trim($this->get('site_description', '')),
            'supportEmail' => trim($this->get('support_email', defined('APP_FROM_EMAIL') ? (string) APP_FROM_EMAIL : '')),
            'supportPhone' => trim($this->get('support_phone', '')),
            'supportWhatsapp' => trim($this->get('support_whatsapp', '')),
            'siteAddress' => trim($this->get('site_address', '')),
            'footerText' => trim($this->get('site_footer_text', '')),
            'siteLogoUrl' => $this->siteLogoUrl(),
        ];
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

    private function pathToUrl(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }
        if (preg_match('/^https?:\\/\\//i', $path) === 1) {
            return $path;
        }

        $url = $this->siteUrl(ltrim($path, '/'));
        $localPath = dirname(__DIR__, 3) . '/' . ltrim($path, '/');
        if (!is_file($localPath)) {
            return '';
        }

        $version = (string) filemtime($localPath);
        if ($version !== '') {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'v=' . $version;
        }

        return $url;
    }
}

