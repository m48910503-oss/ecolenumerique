<?php
declare(strict_types=1);

namespace App\Services\Web;

use App\Core\Service;

final class LegalPageService extends Service
{
    /**
     * @return array{siteLogoUrl:string,lastUpdated:string,backHref:string}
     */
    public function build(string $lastUpdated): array
    {
        return [
            'siteLogoUrl' => $this->siteLogoUrl(),
            'lastUpdated' => $lastUpdated,
            'backHref' => $this->resolveBackHref(),
        ];
    }

    private function resolveBackHref(): string
    {
        $backHref = $this->siteUrl('');
        $referrer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        if ($referrer === '') {
            return $backHref;
        }

        $baseParts = parse_url(APP_BASE_URL);
        $refParts = parse_url($referrer);
        if (!is_array($refParts)) {
            return $backHref;
        }

        if (!empty($refParts['host'])) {
            if (($refParts['host'] ?? '') === ($baseParts['host'] ?? '') && ($refParts['scheme'] ?? '') === ($baseParts['scheme'] ?? '')) {
                return $referrer;
            }
            return $backHref;
        }

        $path = (string) ($refParts['path'] ?? '');
        if ($path === '') {
            return $backHref;
        }

        $basePath = BASE_PATH;
        if ($basePath !== '/' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }
        $backHref = rtrim(APP_BASE_URL, '/') . $path;
        if (!empty($refParts['query'])) {
            $backHref .= '?' . $refParts['query'];
        }
        if (!empty($refParts['fragment'])) {
            $backHref .= '#' . $refParts['fragment'];
        }

        return $backHref;
    }
}

