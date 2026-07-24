<?php
declare(strict_types=1);

namespace App\Services\Web;

final class CertificatePdfBuilder
{
    /**
     * @param array<string,mixed>|null $fallbackDocument
     */
    public function buildFromUrl(string $sourceUrl, ?array $fallbackDocument = null): ?string
    {
        $url = trim($sourceUrl);
        if (!$this->isHttpUrl($url)) {
            return $fallbackDocument !== null ? $this->buildFallbackPdf($fallbackDocument) : null;
        }

        $tmpDir = $this->ensureTempDir();
        if ($tmpDir === '') {
            return $fallbackDocument !== null ? $this->buildFallbackPdf($fallbackDocument) : null;
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

        if ($bytes === null && $fallbackDocument !== null) {
            $bytes = $this->buildFallbackPdf($fallbackDocument);
        }

        return $bytes;
    }

    private function buildWithChrome(string $url, string $pdfPath, string $profileDir): bool
    {
        $binary = $this->findChromeBinary();
        if ($binary === '') {
            error_log('[CertificatePdfBuilder] Chrome or Edge binary not found.');
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
            '--force-device-scale-factor=1',
            '--user-data-dir=' . escapeshellarg($profileDir),
            '--print-to-pdf=' . escapeshellarg($pdfPath),
            '--no-pdf-header-footer',
            escapeshellarg($url),
        ]);

        $result = (new ProcessRunner())->run($cmd);

        if ($result['exitCode'] !== 0) {
            $message = trim((string) $result['output']);
            if (strlen($message) > 1200) {
                $message = substr($message, 0, 1200);
            }
            error_log('[CertificatePdfBuilder] Chrome PDF export failed (exit '
                . $result['exitCode'] . '): ' . $message);
        }

        return $result['exitCode'] === 0 && $this->waitForGeneratedFile($pdfPath);
    }

    private function buildWithWkhtmltopdf(string $url, string $pdfPath): bool
    {
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
        ]);

        $result = (new ProcessRunner())->run($cmd);

        return $result['exitCode'] === 0 && $this->waitForGeneratedFile($pdfPath);
    }

    private function findChromeBinary(): string
    {
        $localAppData = $this->windowsEnvironmentValue('LOCALAPPDATA');
        $userProfile = $this->windowsEnvironmentValue('USERPROFILE');
        $programFiles = $this->windowsEnvironmentValue('ProgramFiles');
        $programFilesX86 = $this->windowsEnvironmentValue('ProgramFiles(x86)');
        $programW6432 = $this->windowsEnvironmentValue('ProgramW6432');

        if ($localAppData === '' && $userProfile !== '') {
            $localAppData = $userProfile . '\\AppData\\Local';
        }

        $candidates = [
            $localAppData !== '' ? $localAppData . '\\Google\\Chrome\\Application\\chrome.exe' : '',
            $localAppData !== '' ? $localAppData . '\\Microsoft\\Edge\\Application\\msedge.exe' : '',
            $localAppData !== '' ? $localAppData . '\\Microsoft\\EdgeCore\\msedge.exe' : '',
            $localAppData !== '' ? $localAppData . '\\Microsoft\\EdgeWebView\\Application\\msedge.exe' : '',
            $programFiles !== '' ? $programFiles . '\\Google\\Chrome\\Application\\chrome.exe' : '',
            $programFilesX86 !== '' ? $programFilesX86 . '\\Google\\Chrome\\Application\\chrome.exe' : '',
            $programW6432 !== '' ? $programW6432 . '\\Google\\Chrome\\Application\\chrome.exe' : '',
            $programFiles !== '' ? $programFiles . '\\Microsoft\\Edge\\Application\\msedge.exe' : '',
            $programFilesX86 !== '' ? $programFilesX86 . '\\Microsoft\\Edge\\Application\\msedge.exe' : '',
            $programW6432 !== '' ? $programW6432 . '\\Microsoft\\Edge\\Application\\msedge.exe' : '',
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

        foreach ($this->windowsUserChromeCandidates() as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return '';
    }

    private function windowsEnvironmentValue(string $name): string
    {
        foreach ([getenv($name), $_SERVER[$name] ?? null, $_ENV[$name] ?? null] as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return '';
    }

    /** @return list<string> */
    private function windowsUserChromeCandidates(): array
    {
        if (DIRECTORY_SEPARATOR !== '\\') {
            return [];
        }

        $patterns = [
            'C:\\Users\\*\\AppData\\Local\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Users\\*\\AppData\\Local\\Microsoft\\Edge\\Application\\msedge.exe',
        ];
        $candidates = [];
        foreach ($patterns as $pattern) {
            $matches = glob($pattern);
            if (is_array($matches)) {
                $candidates = array_merge($candidates, $matches);
            }
        }

        return array_values(array_unique($candidates));
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

    private function waitForGeneratedFile(string $path, int $timeoutMs = 20000): bool
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

    /**
     * @param array<string,mixed> $document
     */
    private function buildFallbackPdf(array $document): ?string
    {
        $siteName = $this->cleanText($document['siteName'] ?? 'École Numérique', 'École Numérique');
        $brandSubtitle = $this->cleanText($document['brandSubtitle'] ?? '', '');
        $documentTitle = $this->cleanText($document['title'] ?? 'Certificat de réussite', 'Certificat de réussite');
        $documentNote = $this->cleanText($document['note'] ?? 'Version de secours générée côté serveur.', 'Version de secours générée côté serveur.');
        $certificateCode = $this->cleanText($document['code'] ?? 'n/c', 'n/c');
        $recipient = $this->cleanText($document['recipient'] ?? 'Étudiant', 'Étudiant');
        $courseTitle = $this->cleanText($document['courseTitle'] ?? 'Cours', 'Cours');
        $examTitle = $this->cleanText($document['examTitle'] ?? 'Examen', 'Examen');
        $scoreLabel = $this->cleanText($document['scoreLabel'] ?? '', '');
        $hoursLabel = $this->cleanText($document['hoursLabel'] ?? '', '');
        $issuedLabel = $this->cleanText($document['issuedLabel'] ?? 'n/c', 'n/c');
        $teacherName = $this->cleanText($document['teacherName'] ?? 'Formateur', 'Formateur');
        $directorName = $this->cleanText($document['directorName'] ?? 'Direction générale', 'Direction générale');
        $verifyUrl = $this->cleanText($document['verifyUrl'] ?? '', '');
        $supportLine = $this->cleanText($document['supportLine'] ?? '', '');
        $footerNote = $this->cleanText($document['footerNote'] ?? '', '');

        $commands = [];
        $commands[] = 'q';
        $commands[] = '0.8 w 36 36 770 523 re S';

        $y = 540;
        $commands[] = $this->pdfTextCommand(48, $y, 'F2', 20, $siteName);
        $y -= 20;
        if ($brandSubtitle !== '') {
            foreach ($this->wrapText($brandSubtitle, 72) as $line) {
                $commands[] = $this->pdfTextCommand(48, $y, 'F3', 10, $line);
                $y -= 13;
            }
        }

        $y -= 14;
        $commands[] = $this->pdfTextCommand(48, $y, 'F2', 24, $documentTitle);
        $y -= 26;
        $commands[] = $this->pdfTextCommand(48, $y, 'F3', 10, 'Document de secours généré côté serveur');
        $y -= 26;
        $commands[] = $this->pdfTextCommand(48, $y, 'F1', 11, 'Certificat N° : ' . $certificateCode);
        $y -= 22;
        $commands[] = $this->pdfTextCommand(48, $y, 'F1', 11, 'Décerné à :');
        $y -= 18;
        foreach ($this->wrapText($recipient, 42) as $line) {
            $commands[] = $this->pdfTextCommand(48, $y, 'F2', 18, $line);
            $y -= 20;
        }

        $y -= 8;
        $fields = [
            'Formation' => $courseTitle,
            'Examen' => $examTitle,
        ];
        foreach ($fields as $label => $value) {
            $commands[] = $this->pdfTextCommand(48, $y, 'F2', 11, $label . ' :');
            $commands[] = $this->pdfTextCommand(128, $y, 'F1', 11, $value);
            $y -= 18;
        }

        if ($scoreLabel !== '') {
            $commands[] = $this->pdfTextCommand(48, $y, 'F2', 11, 'Score :');
            $commands[] = $this->pdfTextCommand(128, $y, 'F1', 11, $scoreLabel);
            $y -= 18;
        }

        if ($hoursLabel !== '') {
            $commands[] = $this->pdfTextCommand(48, $y, 'F2', 11, 'Heures validées :');
            $commands[] = $this->pdfTextCommand(148, $y, 'F1', 11, $hoursLabel);
            $y -= 18;
        }

        $commands[] = $this->pdfTextCommand(48, $y, 'F2', 11, 'Émis le :');
        $commands[] = $this->pdfTextCommand(128, $y, 'F1', 11, $issuedLabel);
        $y -= 22;

        $commands[] = $this->pdfTextCommand(48, $y, 'F2', 11, 'Formateur :');
        $commands[] = $this->pdfTextCommand(128, $y, 'F1', 11, $teacherName);
        $y -= 18;
        $commands[] = $this->pdfTextCommand(48, $y, 'F2', 11, 'Direction générale :');
        $commands[] = $this->pdfTextCommand(148, $y, 'F1', 11, $directorName);
        $y -= 24;

        if ($verifyUrl !== '') {
            $commands[] = $this->pdfTextCommand(48, $y, 'F2', 11, 'Vérification :');
            foreach ($this->wrapText($verifyUrl, 66) as $line) {
                $commands[] = $this->pdfTextCommand(140, $y, 'F1', 10, $line);
                $y -= 14;
            }
        }

        if ($supportLine !== '') {
            $y -= 4;
            $commands[] = $this->pdfTextCommand(48, $y, 'F2', 10, 'Support :');
            foreach ($this->wrapText($supportLine, 66) as $line) {
                $commands[] = $this->pdfTextCommand(110, $y, 'F1', 10, $line);
                $y -= 13;
            }
        }

        if ($footerNote !== '') {
            $y -= 10;
            foreach ($this->wrapText($footerNote, 80) as $line) {
                $commands[] = $this->pdfTextCommand(48, $y, 'F3', 9, $line);
                $y -= 11;
            }
        }

        $commands[] = 'Q';

        return $this->assembleSimplePdf(implode("\n", $commands), 842, 595);
    }

    /**
     * @param array<int,mixed> $lines
     */
    private function assembleSimplePdf(string $contentStream, int $width, int $height): string
    {
        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
        $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . $width . ' ' . $height . '] /Resources << /Font << /F1 4 0 R /F2 5 0 R /F3 6 0 R >> >> /Contents 7 0 R >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Oblique /Encoding /WinAnsiEncoding >>';
        $objects[] = '<< /Length ' . strlen($contentStream) . " >>\nstream\n" . $contentStream . "\nendstream";

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf('%010d 00000 n ', $offset) . "\n";
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";

        return $pdf;
    }

    private function pdfTextCommand(int $x, int $y, string $font, int $size, string $text): string
    {
        return sprintf(
            'BT /%s %d Tf 1 0 0 1 %d %d Tm %s Tj ET',
            $font,
            max(1, $size),
            $x,
            $y,
            $this->escapePdfText($this->toPdfText($text))
        );
    }

    private function escapePdfText(string $text): string
    {
        return '(' . strtr($text, [
            '\\' => '\\\\',
            '(' => '\\(',
            ')' => '\\)',
        ]) . ')';
    }

    private function toPdfText(string $text): string
    {
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', ' ', $text) ?? $text;
        $text = str_replace(["\r", "\n", "\t"], ' ', $text);

        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
            if (is_string($converted) && $converted !== '') {
                return $converted;
            }
        }

        if (function_exists('utf8_decode')) {
            $converted = utf8_decode($text);
            if (is_string($converted) && $converted !== '') {
                return $converted;
            }
        }

        return $text;
    }

    private function cleanText(mixed $value, string $default = ''): string
    {
        $text = trim((string) $value);
        if ($text === '') {
            $text = $default;
        }
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        return trim($text);
    }

    /**
     * @return list<string>
     */
    private function wrapText(string $text, int $maxChars): array
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        if ($text === '') {
            return [];
        }

        $words = preg_split('/\s+/u', $text) ?: [];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $word = trim($word);
            if ($word === '') {
                continue;
            }

            $candidate = $current === '' ? $word : ($current . ' ' . $word);
            if ($current !== '' && $this->textLength($candidate) > $maxChars) {
                $lines[] = $current;
                $current = $word;
                continue;
            }

            $current = $candidate;
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }

    private function textLength(string $text): int
    {
        if (function_exists('mb_strlen')) {
            $length = mb_strlen($text, 'UTF-8');
            if (is_int($length)) {
                return $length;
            }
        }

        return strlen($text);
    }
}
