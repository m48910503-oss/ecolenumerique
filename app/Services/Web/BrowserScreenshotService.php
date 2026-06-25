<?php
declare(strict_types=1);

namespace App\Services\Web;

final class BrowserScreenshotService
{
    public function captureHtml(string $html, int $width = 2970, int $height = 2100): ?string
    {
        $html = trim($html);
        if ($html === '') {
            return null;
        }

        $tmpDir = $this->ensureTempDir();
        if ($tmpDir === '') {
            return null;
        }

        $suffix = $this->randomSuffix();
        $htmlPath = $tmpDir . DIRECTORY_SEPARATOR . 'certificate-preview-' . $suffix . '.html';
        $pngPath = $tmpDir . DIRECTORY_SEPARATOR . 'certificate-preview-' . $suffix . '.png';
        $profileDir = $tmpDir . DIRECTORY_SEPARATOR . 'chrome-profile-' . $suffix;

        if (@file_put_contents($htmlPath, $html) === false) {
            $this->deleteFile($htmlPath);
            return null;
        }

        $ok = $this->captureWithChrome($htmlPath, $pngPath, $profileDir, $width, $height);

        $bytes = null;
        clearstatcache(true, $pngPath);
        if ($ok && is_file($pngPath)) {
            $raw = @file_get_contents($pngPath);
            if (is_string($raw) && $raw !== '') {
                $bytes = $raw;
            }
        }

        $this->deleteFile($htmlPath);
        $this->deleteFile($pngPath);
        $this->deleteDirectory($profileDir);

        return $bytes;
    }

    public function captureUrl(string $url, int $width = 2970, int $height = 2100): ?string
    {
        $url = trim($url);
        if ($url === '' || !preg_match('~^https?://~i', $url)) {
            return null;
        }

        $tmpDir = $this->ensureTempDir();
        if ($tmpDir === '') {
            return null;
        }

        $suffix = $this->randomSuffix();
        $pngPath = $tmpDir . DIRECTORY_SEPARATOR . 'certificate-preview-' . $suffix . '.png';
        $profileDir = $tmpDir . DIRECTORY_SEPARATOR . 'chrome-profile-' . $suffix;

        $ok = $this->captureWithChromeUrl($url, $pngPath, $profileDir, $width, $height);

        $bytes = null;
        clearstatcache(true, $pngPath);
        if ($ok && is_file($pngPath)) {
            $raw = @file_get_contents($pngPath);
            if (is_string($raw) && $raw !== '') {
                $bytes = $raw;
            }
        }

        $this->deleteFile($pngPath);
        $this->deleteDirectory($profileDir);

        return $bytes;
    }

    private function captureWithChrome(string $htmlPath, string $pngPath, string $profileDir, int $width, int $height): bool
    {
        return $this->captureWithChromeUrl($this->fileUrlFromPath($htmlPath), $pngPath, $profileDir, $width, $height);
    }

    private function captureWithChromeUrl(string $url, string $pngPath, string $profileDir, int $width, int $height): bool
    {
        if (!is_callable('exec')) {
            return false;
        }

        $binary = $this->findBrowserBinary();
        if ($binary === '') {
            return false;
        }

        if (!is_dir($profileDir) && !@mkdir($profileDir, 0775, true) && !is_dir($profileDir)) {
            return false;
        }

        $cmd = implode(' ', [
            escapeshellarg($binary),
            '--headless=new',
            '--disable-gpu',
            '--hide-scrollbars',
            '--no-first-run',
            '--no-default-browser-check',
            '--run-all-compositor-stages-before-draw',
            '--virtual-time-budget=15000',
            '--allow-file-access-from-files',
            '--user-data-dir=' . escapeshellarg($profileDir),
            '--window-size=' . (int) $width . ',' . (int) $height,
            '--screenshot=' . escapeshellarg($pngPath),
            escapeshellarg($url),
            '2>&1',
        ]);

        $output = [];
        $exitCode = 1;
        @exec($cmd, $output, $exitCode);

        return $exitCode === 0 && $this->waitForGeneratedFile($pngPath);
    }

    private function findBrowserBinary(): string
    {
        $candidates = [
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
            'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
            '/usr/bin/google-chrome',
            '/usr/bin/chromium',
            '/usr/bin/chromium-browser',
            '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
        ];

        foreach ($candidates as $candidate) {
            if ($candidate !== '' && is_file($candidate)) {
                return $candidate;
            }
        }

        return '';
    }

    private function ensureTempDir(): string
    {
        $root = dirname(__DIR__, 3);
        $tmp = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'tmp';
        if (!is_dir($tmp) && !@mkdir($tmp, 0775, true) && !is_dir($tmp)) {
            return '';
        }

        return $tmp;
    }

    private function randomSuffix(): string
    {
        try {
            return bin2hex(random_bytes(8));
        } catch (\Throwable) {
            return (string) mt_rand(10000000, 99999999);
        }
    }

    private function waitForGeneratedFile(string $path, int $timeoutMs = 8000): bool
    {
        $deadline = microtime(true) + max(0, $timeoutMs) / 1000;

        do {
            clearstatcache(true, $path);
            if (is_file($path) && (int) (@filesize($path) ?: 0) > 100) {
                return true;
            }
            usleep(200000);
        } while (microtime(true) < $deadline);

        clearstatcache(true, $path);
        return is_file($path) && (int) (@filesize($path) ?: 0) > 100;
    }

    private function fileUrlFromPath(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        $normalized = str_replace(' ', '%20', $normalized);
        if (preg_match('~^[A-Za-z]:/~', $normalized) === 1) {
            return 'file:///' . $normalized;
        }
        if (str_starts_with($normalized, '/')) {
            return 'file://' . $normalized;
        }

        return 'file:///' . ltrim($normalized, '/');
    }

    private function deleteFile(string $path): void
    {
        if ($path !== '' && is_file($path)) {
            @unlink($path);
        }
    }

    private function deleteDirectory(string $path): void
    {
        if ($path === '' || !is_dir($path)) {
            return;
        }

        $entries = @scandir($path);
        if (!is_array($entries)) {
            @rmdir($path);
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $full = $path . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($full)) {
                $this->deleteDirectory($full);
            } elseif (is_file($full)) {
                @unlink($full);
            }
        }

        @rmdir($path);
    }
}
