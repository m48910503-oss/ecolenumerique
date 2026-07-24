<?php
declare(strict_types=1);

namespace App\Services\Admin;

use App\Core\Service;

final class ErrorLogsService extends Service
{
    private const DEFAULT_LIMIT = 300;
    private const MIN_LIMIT = 50;
    private const MAX_LIMIT = 2000;
    private const SMALL_FILE_BYTES = 2097152;

    /**
     * @param array<string,mixed> $query
     * @return array<string,mixed>
     */
    public function buildViewData(array $query): array
    {
        $page = \App\Support\Pagination::pageFromQuery($query, 'page', 1);
        $limit = (int) ($query['limit'] ?? self::DEFAULT_LIMIT);
        $limit = max(self::MIN_LIMIT, min(self::MAX_LIMIT, $limit));
        $quickFilter = $this->normalizeQuickFilter((string) ($query['filter'] ?? 'all'));
        $search = trim((string) ($query['q'] ?? ''));
        $pageWindow = max(1, $page) * $limit;

        $logOptions = $this->availableLogs();
        $activeLog = (string) ($query['log'] ?? '');
        if ($activeLog === '' || !isset($logOptions[$activeLog])) {
            $activeLog = array_key_first($logOptions) ?? '';
        }

        $selected = $activeLog !== '' ? $logOptions[$activeLog] : [];
        $rawPath = (string) ($selected['path'] ?? '');
        $resolvedPath = $this->resolvePath($rawPath);
        $pathToRead = $resolvedPath !== '' ? $resolvedPath : $rawPath;

        $logWarnings = [];
        $logErrorsEnabled = $this->isPhpLogErrorsEnabled();
        if ($activeLog === 'php' && !$logErrorsEnabled) {
            $logWarnings[] = 'Le paramètre PHP log_errors est désactivé, les erreurs PHP ne seront pas journalisées.';
        }

        if ($activeLog === 'php') {
            $phpLogSetting = trim((string) ini_get('error_log'));
            $phpLogLower = strtolower($phpLogSetting);
            if ($phpLogSetting === '' || $phpLogLower === 'syslog' || $phpLogLower === 'stderr') {
                $logWarnings[] = 'Le paramètre PHP error_log ne pointe pas vers un fichier local.';
            }
        }

        $logMeta = [
            'path' => $pathToRead,
            'exists' => false,
            'readable' => false,
            'size_bytes' => 0,
            'size_label' => '',
            'updated_at' => '',
        ];

        $rawLines = [];
        $logLines = [];
        $logEntries = [];
        $severitySummary = $this->emptySeveritySummary();
        $logInsights = [];

        if ($pathToRead !== '' && is_file($pathToRead)) {
            $logMeta['exists'] = true;
            $logMeta['size_bytes'] = (int) @filesize($pathToRead);
            $logMeta['size_label'] = $this->formatBytes($logMeta['size_bytes']);
            $mtime = @filemtime($pathToRead);
            if (is_int($mtime) && $mtime > 0) {
                $logMeta['updated_at'] = date('d/m/Y H:i', $mtime);
            }
            if (is_readable($pathToRead)) {
                $logMeta['readable'] = true;
                $rawLines = $this->tailFileLines($pathToRead, $this->readLimitForFilters($pageWindow, $quickFilter, $search));
                $entries = $this->parseEntries($rawLines);
                $entries = $this->filterEntries($entries, $quickFilter, $search);
                if (count($entries) > $pageWindow) {
                    $entries = array_slice($entries, -$pageWindow);
                }
                $logEntries = $entries;
                $logLines = array_values(array_map(
                    static fn(array $entry): string => (string) ($entry['raw'] ?? ''),
                    $entries
                ));
                $severitySummary = $this->buildSeveritySummary($entries);
                $logInsights = $this->buildLogInsights($entries);
            } else {
                $logWarnings[] = 'Le fichier de log est introuvable ou non lisible.';
            }
        } elseif ($pathToRead !== '') {
            $logWarnings[] = 'Le fichier de log est introuvable.';
        } elseif ($activeLog !== '') {
            $logWarnings[] = 'Aucun fichier de log configuré pour cette source.';
        }

        return [
            'logOptions' => $logOptions,
            'activeLog' => $activeLog,
            'page' => $page,
            'limit' => $limit,
            'logLines' => $logLines,
            'logEntries' => $logEntries,
            'logMeta' => $logMeta,
            'logWarnings' => $logWarnings,
            'logErrorsEnabled' => $logErrorsEnabled,
            'quickFilter' => $quickFilter,
            'quickFilterOptions' => $this->quickFilterOptions(),
            'searchQuery' => $search,
            'rawLineCount' => count($rawLines),
            'filteredLineCount' => count($logEntries),
            'severitySummary' => $severitySummary,
            'logInsights' => $logInsights,
        ];
    }

    /**
     * @return array<string,array<string,string>>
     */
    private function availableLogs(): array
    {
        $logs = [];

        $appLog = defined('APP_ERROR_LOG_PATH') ? trim((string) APP_ERROR_LOG_PATH) : '';
        $logs['application'] = [
            'label' => 'Journal application',
            'path' => $this->resolvePath($appLog),
        ];

        $phpLog = trim((string) ini_get('error_log'));
        $phpLogLower = strtolower($phpLog);
        if ($phpLogLower === 'syslog' || $phpLogLower === 'stderr') {
            $phpLog = '';
        }
        $logs['php'] = [
            'label' => 'Journal PHP',
            'path' => $this->resolvePath($phpLog),
        ];

        $mysqlErr = $this->projectPath('tmp_mysql_skip_grants.err.log');
        if ($mysqlErr !== '' && is_file($mysqlErr)) {
            $logs['mysql_skip_grants'] = [
                'label' => 'Erreurs MySQL (skip grants)',
                'path' => $mysqlErr,
            ];
        }

        return $logs;
    }

    /**
     * @return array<string,string>
     */
    private function quickFilterOptions(): array
    {
        return [
            'all' => 'Tous',
            'google' => 'Connexion Google',
            'drive' => 'Drive',
            'fedapay' => 'FedaPay',
            'mail' => 'Mail',
            'error' => 'Erreurs',
            'warning' => 'Avertissements',
        ];
    }

    private function normalizeQuickFilter(string $value): string
    {
        $value = trim(strtolower($value));
        $allowed = array_keys($this->quickFilterOptions());
        return in_array($value, $allowed, true) ? $value : 'all';
    }

    private function readLimitForFilters(int $limit, string $quickFilter, string $search): int
    {
        if ($quickFilter === 'all' && $search === '') {
            return $limit;
        }
        return max($limit, min(10000, $limit * 10));
    }

    /**
     * @param array<int,string> $lines
     * @return array<int,array<string,mixed>>
     */
    private function parseEntries(array $lines): array
    {
        $entries = [];

        foreach ($lines as $line) {
            $raw = trim((string) $line);
            if ($raw === '') {
                continue;
            }

            $timestamp = '';
            $channel = $this->detectChannel($raw);
            $message = $raw;
            $context = '';
            $level = '';

            if (preg_match('/^\[(?<timestamp>[^\]]+)\]\s+(?<level>[A-Z]+)\s+(?<channel>[a-z0-9._-]+)\s+(?<message>.*?)(?:\s+\|\s+(?<context>\{.*\}|\[.*\]))?$/i', $raw, $matches) === 1) {
                $timestamp = trim((string) ($matches['timestamp'] ?? ''));
                $level = strtoupper(trim((string) ($matches['level'] ?? '')));
                $channel = trim((string) ($matches['channel'] ?? $channel));
                $message = trim((string) ($matches['message'] ?? $raw));
                $context = trim((string) ($matches['context'] ?? ''));
            } elseif (preg_match('/^\[(?<timestamp>[^\]]+)\]\s*(?<message>.*)$/', $raw, $matches) === 1) {
                $timestamp = trim((string) ($matches['timestamp'] ?? ''));
                $message = trim((string) ($matches['message'] ?? $raw));
            }

            $severityKey = $this->inferSeverity($level, $raw);
            $severityMeta = $this->severityMeta($severityKey);
            $contextData = $this->parseContextData($context);
            $contextSummary = $this->buildContextSummary($contextData);

            $entries[] = [
                'raw' => $raw,
                'timestamp' => $timestamp,
                'channel' => $channel,
                'message' => $message,
                'context' => $context,
                'contextData' => $contextData,
                'contextSummary' => $contextSummary,
                'severityKey' => $severityKey,
                'severityLabel' => $severityMeta['label'],
                'severityTone' => $severityMeta['tone'],
            ];
        }

        return $entries;
    }

    /**
     * @return array<string,mixed>
     */
    private function parseContextData(string $context): array
    {
        $context = trim($context);
        if ($context === '') {
            return [];
        }

        $decoded = json_decode($context, true);
        if (!is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    /**
     * @param array<string,mixed> $contextData
     */
    private function buildContextSummary(array $contextData): string
    {
        if ($contextData === []) {
            return '';
        }

        $parts = [];

        $method = $this->contextScalarValue($contextData, ['method']);
        if ($method !== '') {
            $parts[] = 'method: ' . strtoupper($method);
        }

        $path = $this->contextScalarValue($contextData, ['path', 'uri']);
        if ($path !== '') {
            $parts[] = 'path: ' . $this->truncateText($path, 48);
        }

        $status = $this->contextScalarValue($contextData, ['status', 'code', 'http_code']);
        if ($status !== '') {
            $parts[] = 'status: ' . $status;
        }

        $env = $this->contextScalarValue($contextData, ['fedapay_env', 'environment']);
        if ($env !== '') {
            $parts[] = 'env: ' . $env;
        }

        $detail = $this->contextScalarValue($contextData, ['hint', 'detail', 'error', 'message']);
        if ($detail !== '') {
            $parts[] = 'detail: ' . $this->truncateText($detail, 96);
        }

        if ($parts === []) {
            return '';
        }

        return implode(' | ', $parts);
    }

    /**
     * @param array<string,mixed> $contextData
     * @param array<int,string> $keys
     */
    private function contextScalarValue(array $contextData, array $keys): string
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $contextData)) {
                continue;
            }

            $value = $contextData[$key];
            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }
            if (is_int($value) || is_float($value)) {
                return trim((string) $value);
            }
            if (is_string($value)) {
                $value = trim($value);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return '';
    }

    /**
     * @param array<int,array<string,mixed>> $entries
     * @return array<int,string>
     */
    private function buildLogInsights(array $entries): array
    {
        $insights = [];

        foreach ($entries as $entry) {
            $insight = $this->buildEntryInsight($entry);
            if ($insight === '' || in_array($insight, $insights, true)) {
                continue;
            }

            $insights[] = $insight;
            if (count($insights) >= 3) {
                break;
            }
        }

        return $insights;
    }

    /**
     * @param array<string,mixed> $entry
     */
    private function buildEntryInsight(array $entry): string
    {
        $channel = strtolower((string) ($entry['channel'] ?? ''));
        if ($channel === '' || !str_contains($channel, 'fedapay')) {
            return '';
        }

        $haystack = strtolower(implode(' ', [
            (string) ($entry['raw'] ?? ''),
            (string) ($entry['message'] ?? ''),
            (string) ($entry['contextSummary'] ?? ''),
        ]));

        if (
            str_contains($haystack, 'self-signed certificate')
            || str_contains($haystack, 'certificate problem')
            || str_contains($haystack, 'unable to get local issuer certificate')
            || str_contains($haystack, 'ssl certificate')
        ) {
            return 'SSL/TLS FedaPay: le serveur ne fait pas confiance au certificat de l API. Mettez a jour le bundle CA de PHP/cURL ou configurez curl.cainfo / openssl.cafile.';
        }

        if (str_contains($haystack, 'cle secrete fedapay manquante') || str_contains($haystack, 'missing secret key')) {
            return 'FedaPay: configurez la cle secrete dans Parametres > Paiements.';
        }

        if (str_contains($haystack, 'reponse json invalide')) {
            return 'FedaPay: la reponse API est invalide. Verifiez le proxy, le bundle CA et la disponibilite de l API.';
        }

        if (str_contains($haystack, 'echec reseau lors d un appel fedapay') || str_contains($haystack, 'curl_error')) {
            return 'FedaPay: verifier la connectivite reseau et le certificat TLS de l API.';
        }

        return '';
    }

    private function truncateText(string $value, int $limit): string
    {
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);
        if ($value === '' || $limit <= 0) {
            return '';
        }

        if (mb_strlen($value) <= $limit) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, max(1, $limit - 3))) . '...';
    }

    /**
     * @param array<int,array<string,mixed>> $entries
     * @return array<int,array<string,mixed>>
     */
    private function filterEntries(array $entries, string $quickFilter, string $search): array
    {
        $filtered = $entries;

        if ($quickFilter !== 'all') {
            $filtered = array_values(array_filter(
                $filtered,
                fn(array $entry): bool => $this->matchesQuickFilter($entry, $quickFilter)
            ));
        }

        if ($search !== '') {
            $filtered = array_values(array_filter(
                $filtered,
                static function (array $entry) use ($search): bool {
                    $haystack = strtolower(
                        trim(
                            implode(
                                ' ',
                                [
                                    (string) ($entry['raw'] ?? ''),
                                    (string) ($entry['channel'] ?? ''),
                                    (string) ($entry['message'] ?? ''),
                                    (string) ($entry['context'] ?? ''),
                                ]
                            )
                        )
                    );
                    return str_contains($haystack, strtolower($search));
                }
            ));
        }

        return $filtered;
    }

    /**
     * @param array<string,mixed> $entry
     */
    private function matchesQuickFilter(array $entry, string $quickFilter): bool
    {
        $channel = strtolower((string) ($entry['channel'] ?? ''));
        $raw = strtolower((string) ($entry['raw'] ?? ''));
        $severity = strtolower((string) ($entry['severityKey'] ?? 'unknown'));

        return match ($quickFilter) {
            'google' => str_contains($channel, 'google.') || str_contains($raw, 'google.'),
            'drive' => str_contains($channel, 'drive.') || str_contains($raw, 'drive.'),
            'fedapay' => str_contains($channel, 'fedapay.') || str_contains($raw, 'fedapay.'),
            'mail' => str_contains($channel, 'mail.') || str_contains($raw, 'mail.'),
            'error' => in_array($severity, ['critical', 'error'], true),
            'warning' => $severity === 'warning',
            default => true,
        };
    }

    private function detectChannel(string $raw): string
    {
        $lower = strtolower($raw);
        foreach (['google.', 'drive.', 'fedapay.', 'mail.'] as $prefix) {
            $pos = strpos($lower, $prefix);
            if ($pos !== false && preg_match('/([a-z0-9._-]+)/', substr($lower, $pos), $matches) === 1) {
                return (string) ($matches[1] ?? '');
            }
        }
        return 'system';
    }

    private function inferSeverity(string $level, string $raw): string
    {
        $level = strtoupper(trim($level));
        if (in_array($level, ['CRITICAL', 'ALERT', 'EMERGENCY', 'FATAL'], true)) {
            return 'critical';
        }
        if (in_array($level, ['ERROR'], true)) {
            return 'error';
        }
        if (in_array($level, ['WARNING', 'WARN', 'NOTICE', 'DEPRECATED'], true)) {
            return 'warning';
        }
        if (in_array($level, ['INFO', 'DEBUG'], true)) {
            return 'info';
        }

        $lower = strtolower($raw);
        if (
            str_contains($lower, 'fatal error')
            || str_contains($lower, 'uncaught')
            || str_contains($lower, 'critical')
            || str_contains($lower, 'panic')
        ) {
            return 'critical';
        }
        if (str_contains($lower, ' error ') || str_contains($lower, 'exception')) {
            return 'error';
        }
        if (str_contains($lower, 'warning') || str_contains($lower, 'deprecated')) {
            return 'warning';
        }
        if (str_contains($lower, 'notice') || str_contains($lower, 'info')) {
            return 'info';
        }

        return 'unknown';
    }

    /**
     * @return array{label:string,tone:string}
     */
    private function severityMeta(string $key): array
    {
        return match ($key) {
            'critical' => ['label' => 'Critique', 'tone' => 'critical'],
            'error' => ['label' => 'Erreur', 'tone' => 'danger'],
            'warning' => ['label' => 'Avertissement', 'tone' => 'warning'],
            'info' => ['label' => 'Info', 'tone' => 'info'],
            default => ['label' => 'Inconnu', 'tone' => 'neutral'],
        };
    }

    /**
     * @return array<string,int>
     */
    private function emptySeveritySummary(): array
    {
        return [
            'critical' => 0,
            'error' => 0,
            'warning' => 0,
            'info' => 0,
            'unknown' => 0,
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $entries
     * @return array<string,int>
     */
    private function buildSeveritySummary(array $entries): array
    {
        $summary = $this->emptySeveritySummary();
        foreach ($entries as $entry) {
            $key = (string) ($entry['severityKey'] ?? 'unknown');
            if (!array_key_exists($key, $summary)) {
                $key = 'unknown';
            }
            $summary[$key]++;
        }
        return $summary;
    }

    private function projectPath(string $relative): string
    {
        $base = dirname(__DIR__, 3);
        $relative = ltrim($relative, '\\/');
        return $base . DIRECTORY_SEPARATOR . $relative;
    }

    private function resolvePath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }
        $real = realpath($path);
        if ($real !== false) {
            return $real;
        }
        return $path;
    }

    private function isPhpLogErrorsEnabled(): bool
    {
        $value = trim((string) ini_get('log_errors'));
        if ($value === '') {
            return false;
        }
        return $value !== '0';
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' o';
        }
        $units = ['Ko', 'Mo', 'Go', 'To'];
        $size = $bytes / 1024;
        $unitIndex = 0;
        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }
        return number_format($size, $size < 10 ? 2 : 1, ',', ' ') . ' ' . $units[$unitIndex];
    }

    /**
     * @return array<int,string>
     */
    private function tailFileLines(string $path, int $limit): array
    {
        $limit = max(1, $limit);

        $size = @filesize($path);
        if (!is_int($size) || $size <= 0) {
            return [];
        }

        if ($size <= self::SMALL_FILE_BYTES) {
            $lines = @file($path, FILE_IGNORE_NEW_LINES);
            if (!is_array($lines)) {
                return [];
            }
            $lines = array_map(static fn(string $line): string => rtrim($line, "\r"), $lines);
            return array_slice($lines, -$limit);
        }

        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return [];
        }

        $buffer = '';
        $lines = [];
        $pos = $size;

        while ($pos > 0 && count($lines) <= $limit) {
            $chunkSize = min(4096, $pos);
            $pos -= $chunkSize;
            fseek($handle, $pos);
            $chunk = fread($handle, $chunkSize);
            if ($chunk === false) {
                break;
            }
            $buffer = $chunk . $buffer;
            $lines = explode("\n", $buffer);
        }

        fclose($handle);

        if (count($lines) > $limit) {
            $lines = array_slice($lines, -$limit);
        }

        $lines = array_map(static fn(string $line): string => rtrim($line, "\r"), $lines);
        while ($lines !== [] && end($lines) === '') {
            array_pop($lines);
        }

        return $lines;
    }
}
