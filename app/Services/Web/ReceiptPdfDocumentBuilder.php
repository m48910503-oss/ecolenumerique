<?php
declare(strict_types=1);

namespace App\Services\Web;

final class ReceiptPdfDocumentBuilder
{
    public function build(array $receiptData): ?string
    {
        $html = $this->renderHtml($receiptData);
        return $this->buildFromHtml($html);
    }

    public function buildFromHtml(string $html): ?string
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
        $htmlPath = $tmpDir . DIRECTORY_SEPARATOR . 'receipt-' . $suffix . '.html';
        $pdfPath = $tmpDir . DIRECTORY_SEPARATOR . 'receipt-' . $suffix . '.pdf';
        $profileDir = $tmpDir . DIRECTORY_SEPARATOR . 'chrome-profile-' . $suffix;

        if (@file_put_contents($htmlPath, $html) === false) {
            $this->deleteFile($htmlPath);
            return null;
        }

        $ok = $this->buildWithChrome($htmlPath, $pdfPath, $profileDir);
        if (!$ok) {
            $ok = $this->buildWithWkhtmltopdf($htmlPath, $pdfPath);
        }

        $bytes = null;
        clearstatcache(true, $pdfPath);
        if ($ok && is_file($pdfPath)) {
            $raw = @file_get_contents($pdfPath);
            if (is_string($raw) && $raw !== '') {
                $bytes = $raw;
            }
        }

        $this->deleteFile($htmlPath);
        $this->deleteFile($pdfPath);
        $this->deleteDirectory($profileDir);

        return $bytes;
    }

    /**
     * @param array<string,mixed> $receiptData
     */
    private function renderHtml(array $receiptData): string
    {
        $order = is_array($receiptData['order'] ?? null) ? $receiptData['order'] : [];
        $items = is_array($receiptData['items'] ?? null) ? array_values($receiptData['items']) : [];

        $siteName = trim((string) ($receiptData['site_name'] ?? (defined('APP_FROM_NAME') ? (string) APP_FROM_NAME : 'École Numérique')));
        if ($siteName === '') {
            $siteName = 'École Numérique';
        }

        $reference = trim((string) ($order['reference'] ?? ''));
        if ($reference === '') {
            $reference = 'Commande';
        }

        $orderId = max(0, (int) ($order['id'] ?? 0));
        $customerName = trim((string) ($receiptData['customer_name'] ?? ''));
        if ($customerName === '') {
            $customerName = $this->displayName($receiptData['user'] ?? null);
        }
        if ($customerName === '') {
            $customerName = 'Étudiant';
        }

        $totalXof = max(0, (int) ($order['total_xof'] ?? 0));
        $logoDataUri = $this->logoDataUri(trim((string) ($receiptData['site_logo_url'] ?? '')));
        $initials = $this->initialsFromText($siteName, 'EN');

        $rowsHtml = '';
        if ($items === []) {
            $rowsHtml .= '<tr><td colspan="4" class="empty-row">Aucun article</td></tr>';
        } else {
            foreach ($items as $row) {
                $title = trim((string) ($row['course_title'] ?? 'Cours'));
                if ($title === '') {
                    $title = 'Cours';
                }
                $qty = max(1, (int) ($row['quantity'] ?? 1));
                $unit = max(0, (int) ($row['unit_price_xof'] ?? 0));
                $lineTotal = max(0, (int) ($row['line_total_xof'] ?? ($qty * $unit)));
                $rowsHtml .= '<tr>'
                    . '<td class="course">' . $this->escape($title) . '</td>'
                    . '<td class="qty">' . $this->escape((string) $qty) . '</td>'
                    . '<td class="money">' . $this->escape($this->money($unit)) . '</td>'
                    . '<td class="money">' . $this->escape($this->money($lineTotal)) . '</td>'
                    . '</tr>';
            }
        }

        $html = '<!doctype html><html lang="fr"><head><meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
            . '<title>Reçu - ' . $this->escape($reference) . '</title>'
            . '<style>'
            . '@page{size:A4;margin:12mm;}'
            . 'html,body{margin:0;padding:0;background:#fff;color:#0f172a;font-family:"Segoe UI",Arial,Helvetica,sans-serif;-webkit-font-smoothing:antialiased;text-rendering:optimizeLegibility;}'
            . '.page{width:100%;}'
            . '.header{display:flex;justify-content:space-between;gap:16px;align-items:stretch;background:#1d4ed8;color:#fff;border-radius:20px;padding:18px 22px;}'
            . '.brand{display:flex;align-items:center;gap:14px;min-width:0;}'
            . '.logo{width:60px;height:60px;border-radius:16px;background:#fff;display:flex;align-items:center;justify-content:center;overflow:hidden;flex:0 0 auto;}'
            . '.logo img{max-width:100%;max-height:100%;object-fit:contain;display:block;}'
            . '.logo .fallback{font-size:16px;font-weight:800;color:#1d4ed8;letter-spacing:.04em;}'
            . '.kicker{margin:0 0 6px;font-size:11px;font-weight:700;letter-spacing:.16em;text-transform:uppercase;opacity:.84;}'
            . '.title{margin:0;font-size:26px;line-height:1.08;font-weight:700;}'
            . '.ref{min-width:198px;background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.16);border-radius:16px;padding:14px 16px;text-align:center;}'
            . '.ref-label{font-size:11px;font-weight:700;letter-spacing:.15em;text-transform:uppercase;opacity:.84;}'
            . '.ref-value{margin:10px 0 0;font-size:20px;line-height:1.1;font-weight:700;word-break:break-word;}'
            . '.ref-meta{margin:8px 0 0;font-size:12px;opacity:.92;line-height:1.35;}'
            . '.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:14px;}'
            . '.card{border:1px solid #e2e8f0;border-radius:16px;background:#fff;padding:16px;}'
            . '.label{margin:0 0 10px;font-size:11px;font-weight:800;letter-spacing:.15em;text-transform:uppercase;color:#64748b;}'
            . '.value{margin:0;font-size:18px;line-height:1.2;font-weight:700;color:#0f172a;word-break:break-word;}'
            . '.section{margin-top:14px;border:1px solid #e2e8f0;border-radius:18px;overflow:hidden;background:#fff;}'
            . '.section-head{padding:16px 18px;border-bottom:1px solid #e2e8f0;background:#fbfdff;}'
            . '.section-head h2{margin:0;font-size:18px;line-height:1.2;font-weight:700;color:#0f172a;}'
            . '.table-wrap{padding:0;}'
            . 'table{width:100%;border-collapse:collapse;}'
            . 'th,td{padding:12px 18px;border-bottom:1px solid #eef2f7;font-size:13px;vertical-align:top;}'
            . 'th{background:#f8fafc;color:#64748b;font-size:11px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;text-align:left;white-space:nowrap;}'
            . 'td.qty,th.qty,td.money,th.money{text-align:right;white-space:nowrap;}'
            . 'td.course{font-weight:700;color:#0f172a;}'
            . 'tr:last-child td{border-bottom:none;}'
            . '.empty-row{padding:20px 18px;color:#64748b;}'
            . '.total{background:#f8fafc;font-weight:700;}'
            . '.total td{font-size:14px;font-weight:700;color:#0f172a;}'
            . '</style></head><body><div class="page">'
            . '<div class="header">'
            . '<div class="brand">'
            . '<div class="logo">' . ($logoDataUri !== ''
                ? '<img src="' . $this->escape($logoDataUri) . '" alt="">'
                : '<span class="fallback">' . $this->escape($initials) . '</span>') . '</div>'
            . '<div>'
            . '<p class="kicker">' . $this->escape($siteName) . '</p>'
            . '<h1 class="title">Reçu</h1>'
            . '</div></div>'
            . '<div class="ref">'
            . '<div class="ref-label">Référence</div>'
            . '<div class="ref-value">#' . $this->escape($reference) . '</div>'
            . '<div class="ref-meta">Commande #' . $this->escape((string) $orderId) . '</div>'
            . '</div></div>'
            . '<div class="grid">'
            . '<div class="card"><p class="label">Client</p><p class="value">' . $this->escape($customerName) . '</p></div>'
            . '<div class="card"><p class="label">Commande</p><p class="value">#' . $this->escape($reference) . '</p></div>'
            . '<div class="card"><p class="label">Montant réglé</p><p class="value">' . $this->escape($this->money($totalXof)) . '</p></div>'
            . '</div>'
            . '<div class="section">'
            . '<div class="section-head"><h2>Cours achetés</h2></div>'
            . '<div class="table-wrap"><table><thead><tr><th>COURS</th><th class="qty">QTÉ</th><th class="money">PRIX UNITAIRE</th><th class="money">TOTAL LIGNE</th></tr></thead><tbody>'
            . $rowsHtml
            . '<tr class="total"><td>TOTAL</td><td class="qty"></td><td class="money"></td><td class="money">' . $this->escape($this->money($totalXof)) . '</td></tr>'
            . '</tbody></table></div></div>'
            . '</div></body></html>';

        return $html;
    }

    private function buildWithChrome(string $htmlPath, string $pdfPath, string $profileDir): bool
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
            '--hide-scrollbars',
            '--no-first-run',
            '--no-default-browser-check',
            '--run-all-compositor-stages-before-draw',
            '--virtual-time-budget=12000',
            '--allow-file-access-from-files',
            '--user-data-dir=' . escapeshellarg($profileDir),
            '--print-to-pdf=' . escapeshellarg($pdfPath),
            '--print-to-pdf-no-header',
            escapeshellarg($this->fileUrlFromPath($htmlPath)),
            '2>&1',
        ]);

        $output = [];
        $exitCode = 1;
        @exec($cmd, $output, $exitCode);

        return $exitCode === 0 && $this->waitForGeneratedFile($pdfPath);
    }

    private function buildWithWkhtmltopdf(string $htmlPath, string $pdfPath): bool
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
            '--enable-local-file-access',
            '--page-size A4',
            '--orientation Portrait',
            '--margin-top 10mm',
            '--margin-right 10mm',
            '--margin-bottom 10mm',
            '--margin-left 10mm',
            '--encoding UTF-8',
            escapeshellarg($htmlPath),
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

    private function fileUrlFromPath(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        if (preg_match('#^[A-Za-z]:/#', $normalized) === 1) {
            return 'file:///' . $normalized;
        }
        if (str_starts_with($normalized, '/')) {
            return 'file://' . $normalized;
        }
        return 'file:///' . $normalized;
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

    private function logoDataUri(string $source): string
    {
        if ($source === '') {
            return '';
        }

        $bytes = $this->fetchBinary($source);
        if (!is_string($bytes) || $bytes === '') {
            return '';
        }

        $mime = $this->detectMime($bytes);
        if ($mime === '') {
            $mime = 'image/png';
        }

        return 'data:' . $mime . ';base64,' . base64_encode($bytes);
    }

    private function fetchBinary(string $source): ?string
    {
        $source = trim($source);
        if ($source === '') {
            return null;
        }

        if (is_file($source)) {
            $binary = @file_get_contents($source);
            return is_string($binary) && $binary !== '' ? $binary : null;
        }

        if (!preg_match('#^[a-z][a-z0-9+.-]*://#i', $source) && str_starts_with($source, '//')) {
            $source = 'https:' . $source;
        }

        if (preg_match('#^https?://#i', $source) === 1) {
            if (!function_exists('curl_init')) {
                $binary = @file_get_contents($source);
                return is_string($binary) && $binary !== '' ? $binary : null;
            }

            $ch = curl_init($source);
            if ($ch === false) {
                return null;
            }

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_USERAGENT => 'EcoleNumeriqueReceiptPdf/1.0',
            ]);
            $binary = curl_exec($ch);
            curl_close($ch);
            return is_string($binary) && $binary !== '' ? $binary : null;
        }

        $root = dirname(__DIR__, 3);
        $localPath = $root . DIRECTORY_SEPARATOR . ltrim($source, '/\\');
        if (is_file($localPath)) {
            $binary = @file_get_contents($localPath);
            return is_string($binary) && $binary !== '' ? $binary : null;
        }

        return null;
    }

    private function detectMime(string $bytes): string
    {
        if (function_exists('finfo_buffer')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if (is_resource($finfo)) {
                $mime = finfo_buffer($finfo, $bytes);
                finfo_close($finfo);
                if (is_string($mime) && $mime !== '') {
                    return $mime;
                }
            }
        }

        if (str_starts_with($bytes, "\x89PNG\r\n\x1a\n")) {
            return 'image/png';
        }
        if (str_starts_with($bytes, "\xFF\xD8")) {
            return 'image/jpeg';
        }
        if (str_starts_with($bytes, 'GIF87a') || str_starts_with($bytes, 'GIF89a')) {
            return 'image/gif';
        }

        return '';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function money(int $amount): string
    {
        return number_format(max(0, $amount), 0, ',', ' ') . ' F CFA';
    }

    private function initialsFromText(string $value, string $fallback = 'EN'): string
    {
        $value = trim($value);
        if ($value === '') {
            return $fallback;
        }

        $parts = preg_split('/[\s\-_]+/u', $value) ?: [];
        $letters = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $first = mb_substr($part, 0, 1, 'UTF-8');
            if ($first !== '') {
                $letters[] = $first;
            }
            if (count($letters) >= 2) {
                break;
            }
        }

        $initials = implode('', $letters);
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $initials);
        if (is_string($ascii) && trim($ascii) !== '') {
            $initials = $ascii;
        }

        $initials = strtoupper(trim(preg_replace('/[^A-Za-z0-9]/', '', $initials) ?? ''));
        return $initials !== '' ? substr($initials, 0, 2) : $fallback;
    }

    /**
     * @param array<int|string,mixed>|null $user
     */
    private function displayName(mixed $user): string
    {
        if (!is_array($user)) {
            return '';
        }

        $full = trim((string) ($user['display_name'] ?? ''));
        if ($full !== '') {
            return $full;
        }

        $parts = [];
        foreach (['first_name', 'last_name'] as $key) {
            $value = trim((string) ($user[$key] ?? ''));
            if ($value !== '') {
                $parts[] = $value;
            }
        }

        return trim(implode(' ', $parts));
    }
}
