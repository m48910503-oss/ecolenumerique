<?php
declare(strict_types=1);

namespace App\Services\Web;

final class CertificatePdfBuilder
{
    public function buildFromUrl(string $sourceUrl): ?string
    {
        $url = trim($sourceUrl);
        if (!$this->isHttpUrl($url)) {
            return null;
        }

        $tmpDir = $this->ensureTempDir();
        if ($tmpDir === '') {
            return null;
        }

        $suffix = $this->randomSuffix();
        $pdfPath = $tmpDir . DIRECTORY_SEPARATOR . 'certificate-' . $suffix . '.pdf';
        $profileDir = $tmpDir . DIRECTORY_SEPARATOR . 'chrome-profile-' . $suffix;

        $ok = $this->buildWithChrome($url, $pdfPath, $profileDir);
        if (!$ok) {
            $ok = $this->buildWithWkhtmltopdf($url, $pdfPath);
        }

        $bytes = null;
        clearstatcache(true, $pdfPath);
        if ($ok && is_file($pdfPath)) {
            $raw = @file_get_contents($pdfPath);
            if (is_string($raw) && $raw !== '') {
                $bytes = $raw;
            }
        }

        $this->deleteFile($pdfPath);
        $this->deleteDirectory($profileDir);

        return $bytes;
    }

    private function buildWithChrome(string $url, string $pdfPath, string $profileDir): bool
    {
        if (!is_callable('exec')) {
            return false;
        }

        $binary = $this->findChromeBinary();
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
            '--no-first-run',
            '--no-default-browser-check',
            '--run-all-compositor-stages-before-draw',
            '--virtual-time-budget=12000',
            '--user-data-dir=' . escapeshellarg($profileDir),
            '--print-to-pdf=' . escapeshellarg($pdfPath),
            '--print-to-pdf-no-header',
            escapeshellarg($url),
            '2>&1',
        ]);

        $output = [];
        $exitCode = 1;
        @exec($cmd, $output, $exitCode);

        return $exitCode === 0 && $this->waitForGeneratedFile($pdfPath);
    }

    private function buildWithWkhtmltopdf(string $url, string $pdfPath): bool
    {
        if (!is_callable('exec')) {
            return false;
        }

        $binary = $this->findWkhtmltopdfBinary();
        if ($binary === '') {
            return false;
        }

        $cmd = implode(' ', [
            escapeshellarg($binary),
            '--quiet',
            '--page-size A4',
            '--orientation Landscape',
            '--margin-top 10mm',
            '--margin-right 10mm',
            '--margin-bottom 10mm',
            '--margin-left 10mm',
            '--encoding UTF-8',
            escapeshellarg($url),
            escapeshellarg($pdfPath),
            '2>&1',
        ]);

        $output = [];
        $exitCode = 1;
        @exec($cmd, $output, $exitCode);

        return $exitCode === 0 && $this->waitForGeneratedFile($pdfPath);
    }

    private function findChromeBinary(): string
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

    private function findWkhtmltopdfBinary(): string
    {
        $candidates = [
            'C:\\Program Files\\wkhtmltopdf\\bin\\wkhtmltopdf.exe',
            'C:\\Program Files (x86)\\wkhtmltopdf\\bin\\wkhtmltopdf.exe',
            '/usr/local/bin/wkhtmltopdf',
            '/usr/bin/wkhtmltopdf',
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

    private function waitForGeneratedFile(string $path, int $timeoutMs = 5000): bool
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

    private function isHttpUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return false;
        }
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = (string) ($parts['host'] ?? '');

        return in_array($scheme, ['http', 'https'], true) && $host !== '';
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
