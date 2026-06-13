<?php
// Fonctions utilitaires partagées (exposées partout via bootstrap.php).

// Helpers d'échappement pour la sortie HTML.
if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('is_local_environment')) {
    function is_local_environment(): bool
    {
        $env = strtolower(trim((string) getenv('APP_ENV')));
        if (in_array($env, ['local', 'development', 'dev'], true)) {
            return true;
        }

        $host = strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? '')));
        if (in_array($host, ['localhost', '127.0.0.1', '[::1]'], true)) {
            return true;
        }

        $baseUrl = strtolower(trim((string) (defined('APP_BASE_URL') ? APP_BASE_URL : '')));
        foreach (['http://localhost', 'https://localhost', 'http://127.0.0.1', 'https://127.0.0.1'] as $prefix) {
            if ($baseUrl !== '' && str_starts_with($baseUrl, $prefix)) {
                return true;
            }
        }

        return false;
    }
}

// Construit une URL interne en préfixant le dossier racine.
if (!function_exists('normalize_routable_path')) {
    function normalize_routable_path(string $path): string
    {
        static $routable = null;
        if ($routable === null) {
            $routable = [];
            $routesFile = __DIR__ . '/../app/routes.php';
            if (is_file($routesFile)) {
                /** @var array<string, mixed> $routes */
                $routes = require $routesFile;
                foreach (array_keys($routes) as $routePath) {
                    $routable[(string) $routePath] = true;
                }
            } 
        } 

        $fragment = '';
        $query = '';

        $hashPos = strpos($path, '#');
        if ($hashPos !== false) {
            $fragment = substr($path, $hashPos);
            $path = substr($path, 0, $hashPos);
        }

        $queryPos = strpos($path, '?');
        if ($queryPos !== false) {
            $query = substr($path, $queryPos);
            $path = substr($path, 0, $queryPos);
        }

        $trimmed = ltrim($path, '/');
        if ($trimmed === '' || $trimmed === 'index' || $trimmed === 'index.php') {
            return $query . $fragment;
        }

        if (str_ends_with($trimmed, '.php')) {
            $candidate = substr($trimmed, 0, -4);
            if (isset($routable[$candidate])) {
                $trimmed = $candidate;
            }
        }

        return $trimmed . $query . $fragment;
    }
}

if (!function_exists('site_url')) {
    function site_url(string $path = ''): string
    {
        $base = rtrim(BASE_PATH, '/');
        $normalizedPath = normalize_routable_path($path);
        $normalized = '/' . ltrim($normalizedPath, '/');
        return $base . $normalized;
    }
}

// Construit une URL absolue (utile pour les emails).
if (!function_exists('abs_url')) {
    function abs_url(string $path = ''): string
    {
        $base = APP_BASE_URL ?: '';
        $normalizedPath = normalize_routable_path($path);
        $normalized = '/' . ltrim($normalizedPath, '/');
        return rtrim($base, '/') . $normalized;
    }
}

if (!function_exists('mail_sanitize_header_value')) {
    function mail_sanitize_header_value(string $value): string
    {
        return trim(str_replace(["\r", "\n"], '', $value));
    }
}

if (!function_exists('mail_encode_header_utf8')) {
    function mail_encode_header_utf8(string $value): string
    {
        $safe = mail_sanitize_header_value($value);
        if ($safe === '') {
            return '';
        }
        if (function_exists('mb_encode_mimeheader')) {
            $encoded = mb_encode_mimeheader($safe, 'UTF-8', 'B', "\r\n");
            if (is_string($encoded) && $encoded !== '') {
                return $encoded;
            }
        }
        return '=?UTF-8?B?' . base64_encode($safe) . '?=';
    }
}

if (!function_exists('smtp_read_response')) {
    /**
     * @param resource $socket
     * @return array{code:int, message:string}
     */
    function smtp_read_response($socket): array
    {
        $lines = [];
        while (is_resource($socket) && ($line = fgets($socket, 515)) !== false) {
            $lines[] = rtrim($line, "\r\n");
            if (preg_match('/^\d{3}\s/', $line) === 1) {
                break;
            }
        }

        if ($lines === []) {
            return ['code' => 0, 'message' => ''];
        }

        $last = (string) end($lines);
        $code = 0;
        if (preg_match('/^(\d{3})/', $last, $m) === 1) {
            $code = (int) ($m[1] ?? 0);
        }

        return ['code' => $code, 'message' => implode("\n", $lines)];
    }
}

if (!function_exists('smtp_expect')) {
    /**
     * @param resource $socket
     * @param array<int,int> $expectedCodes
     */
    function smtp_expect($socket, array $expectedCodes, ?string $command = null, ?array &$response = null): bool
    {
        if ($command !== null) {
            $written = @fwrite($socket, $command . "\r\n");
            if ($written === false) {
                $response = ['code' => 0, 'message' => 'SMTP write failed'];
                return false;
            }
        }
        $response = smtp_read_response($socket);
        return in_array((int) ($response['code'] ?? 0), $expectedCodes, true);
    }
}

if (!function_exists('app_log_sanitize_context')) {
    function app_log_sanitize_context(mixed $value, int $depth = 0): mixed
    {
        if ($depth > 4) {
            return '[truncated]';
        }

        if (is_null($value) || is_bool($value) || is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_string($value)) {
            $value = preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);
            if (strlen($value) > 800) {
                return substr($value, 0, 797) . '...';
            }
            return $value;
        }

        if ($value instanceof Throwable) {
            return [
                'type' => $value::class,
                'message' => $value->getMessage(),
                'file' => $value->getFile(),
                'line' => $value->getLine(),
            ];
        }

        if (is_array($value)) {
            $sanitized = [];
            foreach ($value as $key => $item) {
                $keyString = is_int($key) ? (string) $key : trim((string) $key);
                $lowerKey = strtolower($keyString);
                if ($lowerKey !== '' && (
                    str_contains($lowerKey, 'password')
                    || str_contains($lowerKey, 'secret')
                    || str_contains($lowerKey, 'token')
                    || str_contains($lowerKey, 'authorization')
                    || str_contains($lowerKey, 'app_key')
                )) {
                    $sanitized[$keyString] = '[redacted]';
                    continue;
                }
                $sanitized[$keyString] = app_log_sanitize_context($item, $depth + 1);
            }
            return $sanitized;
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return app_log_sanitize_context((string) $value, $depth + 1);
        }

        return '[' . get_debug_type($value) . ']';
    }
}

if (!function_exists('app_log')) {
    function app_log(string $level, string $channel, string $message, array $context = []): bool
    {
        $path = defined('APP_ERROR_LOG_PATH') ? (string) APP_ERROR_LOG_PATH : (__DIR__ . '/../storage/logs/application-error.log');
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $level = strtoupper(trim($level));
        if ($level === '') {
            $level = 'INFO';
        }

        $channel = trim($channel);
        if ($channel === '') {
            $channel = 'app';
        }

        $message = preg_replace('/\s+/', ' ', trim($message)) ?? trim($message);
        if ($message === '') {
            $message = 'No message';
        }

        $baseContext = [
            'method' => (string) ($_SERVER['REQUEST_METHOD'] ?? ''),
            'uri' => (string) ($_SERVER['REQUEST_URI'] ?? ''),
            'ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            'sapi' => PHP_SAPI,
        ];
        $sanitizedContext = app_log_sanitize_context(array_merge($baseContext, $context));
        $contextJson = json_encode($sanitizedContext, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $line = '[' . date('c') . '] ' . $level . ' ' . $channel . ' ' . $message;
        if (is_string($contextJson) && $contextJson !== '' && $contextJson !== '[]' && $contextJson !== '{}') {
            $line .= ' | ' . $contextJson;
        }
        $line .= PHP_EOL;

        $written = @file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
        if ($written !== false) {
            return true;
        }

        @error_log($line);
        return false;
    }
}

if (!function_exists('app_log_error')) {
    function app_log_error(string $channel, string $message, array $context = []): bool
    {
        return app_log('error', $channel, $message, $context);
    }
}

if (!function_exists('clear_last_mail_error')) {
    function clear_last_mail_error(): void
    {
        $GLOBALS['__app_last_mail_error'] = [];
    }
}

if (!function_exists('set_last_mail_error')) {
    function set_last_mail_error(array $error): void
    {
        $GLOBALS['__app_last_mail_error'] = $error;
    }
}

if (!function_exists('last_mail_error')) {
    /**
     * @return array<string,mixed>
     */
    function last_mail_error(): array
    {
        $error = $GLOBALS['__app_last_mail_error'] ?? [];
        return is_array($error) ? $error : [];
    }
}

if (!function_exists('mail_fail')) {
    function mail_fail(string $message, array $context = []): bool
    {
        $payload = array_merge(['message' => $message], $context);
        set_last_mail_error($payload);
        app_log_error('mail.delivery', $message, $payload);
        return false;
    }
}

if (!function_exists('smtp_send_email')) {
    function smtp_send_email(string $to, string $subject, string $body): bool
    {
        $from = mail_sanitize_header_value((string) (APP_FROM_EMAIL ?? ''));
        $toEmail = mail_sanitize_header_value($to);
        clear_last_mail_error();

        if (!filter_var($from, FILTER_VALIDATE_EMAIL) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return mail_fail('Adresse email expediteur ou destinataire invalide.', [
                'driver' => 'smtp',
                'from' => $from,
                'to' => $toEmail,
                'subject' => $subject,
            ]);
        }

        $host = trim((string) (APP_MAIL_SMTP_HOST ?? ''));
        $port = max(1, (int) (APP_MAIL_SMTP_PORT ?? 587));
        $encryption = strtolower(trim((string) (APP_MAIL_SMTP_ENCRYPTION ?? 'tls')));
        if (!in_array($encryption, ['tls', 'ssl', 'none'], true)) {
            $encryption = 'tls';
        }
        $username = (string) (APP_MAIL_SMTP_USERNAME ?? '');
        $password = (string) (APP_MAIL_SMTP_PASSWORD ?? '');
        $timeout = max(5, (int) (APP_MAIL_SMTP_TIMEOUT ?? 15));

        if ($host === '') {
            return mail_fail('Configuration SMTP incomplete: host absent.', [
                'driver' => 'smtp',
                'to' => $toEmail,
                'subject' => $subject,
            ]);
        }

        $remoteHost = $encryption === 'ssl' ? ('ssl://' . $host) : $host;
        $remote = $remoteHost . ':' . $port;
        $errno = 0;
        $errstr = '';
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ]);

        $socket = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
        if (!is_resource($socket)) {
            return mail_fail('Connexion SMTP impossible.', [
                'driver' => 'smtp',
                'to' => $toEmail,
                'subject' => $subject,
                'smtp_host' => $host,
                'smtp_port' => $port,
                'smtp_encryption' => $encryption,
                'socket_errno' => $errno,
                'socket_error' => $errstr,
            ]);
        }

        try {
            stream_set_timeout($socket, $timeout);
            $response = [];
            $failStep = static function (string $step, array $expectedCodes, array $response = []) use ($toEmail, $subject, $host, $port, $encryption, $from, $username): bool {
                return mail_fail('Echec SMTP a l etape ' . $step . '.', [
                    'driver' => 'smtp',
                    'step' => $step,
                    'expected_codes' => $expectedCodes,
                    'smtp_code' => (int) ($response['code'] ?? 0),
                    'smtp_response' => (string) ($response['message'] ?? ''),
                    'smtp_host' => $host,
                    'smtp_port' => $port,
                    'smtp_encryption' => $encryption,
                    'smtp_user' => $username !== '' ? $username : null,
                    'from' => $from,
                    'to' => $toEmail,
                    'subject' => $subject,
                ]);
            };

            if (!smtp_expect($socket, [220], null, $response)) {
                return $failStep('server_greeting', [220], $response);
            }

            $clientHost = gethostname();
            if (!is_string($clientHost) || trim($clientHost) === '') {
                $clientHost = 'localhost';
            }

            if (!smtp_expect($socket, [250], 'EHLO ' . $clientHost, $response)) {
                if (!smtp_expect($socket, [250], 'HELO ' . $clientHost, $response)) {
                    return $failStep('ehlo_or_helo', [250], $response);
                }
            }

            if ($encryption === 'tls') {
                if (!smtp_expect($socket, [220], 'STARTTLS', $response)) {
                    return $failStep('starttls', [220], $response);
                }

                $cryptoMethod = defined('STREAM_CRYPTO_METHOD_TLS_CLIENT')
                    ? STREAM_CRYPTO_METHOD_TLS_CLIENT
                    : (defined('STREAM_CRYPTO_METHOD_SSLv23_CLIENT') ? STREAM_CRYPTO_METHOD_SSLv23_CLIENT : null);
                if ($cryptoMethod === null) {
                    return mail_fail('TLS indisponible sur cette installation PHP.', [
                        'driver' => 'smtp',
                        'to' => $toEmail,
                        'subject' => $subject,
                        'smtp_host' => $host,
                        'smtp_port' => $port,
                        'smtp_encryption' => $encryption,
                    ]);
                }

                $cryptoEnabled = @stream_socket_enable_crypto($socket, true, $cryptoMethod);
                if ($cryptoEnabled !== true) {
                    return mail_fail('Activation TLS impossible.', [
                        'driver' => 'smtp',
                        'to' => $toEmail,
                        'subject' => $subject,
                        'smtp_host' => $host,
                        'smtp_port' => $port,
                        'smtp_encryption' => $encryption,
                    ]);
                }

                if (!smtp_expect($socket, [250], 'EHLO ' . $clientHost, $response)) {
                    return $failStep('ehlo_after_starttls', [250], $response);
                }
            }

            if ($username !== '') {
                if (!smtp_expect($socket, [334], 'AUTH LOGIN', $response)) {
                    return $failStep('auth_login', [334], $response);
                }
                if (!smtp_expect($socket, [334], base64_encode($username), $response)) {
                    return $failStep('auth_username', [334], $response);
                }
                if (!smtp_expect($socket, [235], base64_encode($password), $response)) {
                    return $failStep('auth_password', [235], $response);
                }
            }

            if (!smtp_expect($socket, [250], 'MAIL FROM:<' . $from . '>', $response)) {
                return $failStep('mail_from', [250], $response);
            }
            if (!smtp_expect($socket, [250, 251], 'RCPT TO:<' . $toEmail . '>', $response)) {
                return $failStep('rcpt_to', [250, 251], $response);
            }
            if (!smtp_expect($socket, [354], 'DATA', $response)) {
                return $failStep('data', [354], $response);
            }

            $fromName = mail_encode_header_utf8((string) (APP_FROM_NAME ?? ''));
            $subjectHeader = mail_encode_header_utf8($subject);
            $headers = [
                'From: ' . ($fromName !== '' ? ('"' . $fromName . '" <' . $from . '>') : ('<' . $from . '>')),
                'To: <' . $toEmail . '>',
                'Subject: ' . $subjectHeader,
                'Date: ' . date(DATE_RFC2822),
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
                'Content-Transfer-Encoding: 8bit',
            ];

            $normalizedBody = str_replace(["\r\n", "\r"], "\n", $body);
            $normalizedBody = preg_replace('/^\./m', '..', $normalizedBody) ?? $normalizedBody;
            $message = implode("\r\n", $headers) . "\r\n\r\n" . str_replace("\n", "\r\n", $normalizedBody);
            $written = @fwrite($socket, $message . "\r\n.\r\n");
            if ($written === false) {
                return mail_fail('Ecriture du contenu SMTP impossible.', [
                    'driver' => 'smtp',
                    'to' => $toEmail,
                    'subject' => $subject,
                    'smtp_host' => $host,
                    'smtp_port' => $port,
                    'smtp_encryption' => $encryption,
                ]);
            }

            if (!smtp_expect($socket, [250], null, $response)) {
                return $failStep('message_acceptance', [250], $response);
            }

            smtp_expect($socket, [221, 250], 'QUIT', $response);
            clear_last_mail_error();
            return true;
        } finally {
            @fclose($socket);
        }
    }
}

if (!function_exists('send_email')) {
    function send_email(string $to, string $subject, string $body): bool
    {
        $driver = strtolower(trim((string) (APP_MAIL_DRIVER ?? 'smtp')));
        $from = (string) (APP_FROM_EMAIL ?? '');
        $fromName = (string) (APP_FROM_NAME ?? '');
        clear_last_mail_error();

        if ($driver === 'log') {
            $logPath = APP_MAIL_LOG_PATH;
            $dir = dirname($logPath);
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            $content = "Date: " . date('c') . PHP_EOL
                . "From: {$fromName} <{$from}>" . PHP_EOL
                . "To: {$to}" . PHP_EOL
                . "Subject: {$subject}" . PHP_EOL
                . "Body:" . PHP_EOL . $body . PHP_EOL
                . str_repeat('-', 40) . PHP_EOL;
            if (file_put_contents($logPath, $content, FILE_APPEND) === false) {
                return mail_fail('Impossible d ecrire le mail de developpement dans le journal local.', [
                    'driver' => 'log',
                    'mail_log_path' => $logPath,
                    'to' => $to,
                    'subject' => $subject,
                ]);
            }
            return true;
        }

        if ($driver === 'smtp') {
            return smtp_send_email($to, $subject, $body);
        }

        $headers = [];
        $headers[] = 'From: ' . sprintf('"%s" <%s>', addslashes(mail_sanitize_header_value($fromName)), mail_sanitize_header_value($from));
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $sent = @mail($to, $subject, $body, implode("\r\n", $headers));
        if ($sent) {
            return true;
        }

        return mail_fail('La fonction native mail() a retourne false.', [
            'driver' => $driver !== '' ? $driver : 'mail',
            'to' => $to,
            'subject' => $subject,
        ]);
    }
}

if (!function_exists('format_duration_minutes_hm')) {
    function format_duration_minutes_hm(int $minutes, bool $alwaysShowHours = false): string
    {
        $minutes = max(0, $minutes);
        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        if ($hours < 1 && !$alwaysShowHours) {
            return $minutes . ' min';
        }

        return $hours . ' h ' . str_pad((string) $rest, 2, '0', STR_PAD_LEFT) . ' min';
    }
}

if (!function_exists('format_duration_hours_hm')) {
    function format_duration_hours_hm(float $hours, bool $alwaysShowHours = false): string
    {
        $minutes = (int) round(max(0.0, $hours) * 60);
        return format_duration_minutes_hm($minutes, $alwaysShowHours);
    }
}

// --- Course meetings schema helpers (local migrations) ---
if (!function_exists('course_meetings_has_duration')) {
    function course_meetings_has_duration(bool $refresh = false): bool
    {
        static $cached = null;
        if ($refresh) {
            $cached = null;
        }
        if ($cached !== null) {
            return (bool) $cached;
        }
        global $pdo;
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM course_meetings LIKE 'duration_min'");
            $cached = (bool) ($stmt && $stmt->fetch());
        } catch (Throwable $e) {
            $cached = false;
        }
        return (bool) $cached;
    }
}

if (!function_exists('ensure_course_meetings_duration_column')) {
    function ensure_course_meetings_duration_column(): bool
    {
        return course_meetings_has_duration();
    }
}

// --- Courses schema helpers (local migrations) ---
if (!function_exists('courses_has_update_flag')) {
    function courses_has_update_flag(bool $refresh = false): bool
    {
        static $cached = null;
        if ($refresh) {
            $cached = null;
        }
        if ($cached !== null) {
            return (bool) $cached;
        }
        global $pdo;
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM courses LIKE 'update_in_progress'");
            $cached = (bool) ($stmt && $stmt->fetch());
        } catch (Throwable $e) {
            $cached = false;
        }
        return (bool) $cached;
    }
}

if (!function_exists('ensure_courses_update_flag_column')) {
    function ensure_courses_update_flag_column(): bool
    {
        return courses_has_update_flag();
    }
}

if (!function_exists('courses_has_share_token')) {
    function courses_has_share_token(bool $refresh = false): bool
    {
        static $cached = null;
        if ($refresh) {
            $cached = null;
        }
        if ($cached !== null) {
            return (bool) $cached;
        }
        global $pdo;
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM courses LIKE 'share_token'");
            $cached = (bool) ($stmt && $stmt->fetch());
        } catch (Throwable $e) {
            $cached = false;
        }
        return (bool) $cached;
    }
}

if (!function_exists('ensure_courses_share_token_column')) {
    function ensure_courses_share_token_column(): bool
    {
        return shared_promotions_service()->ensureCoursesShareTokenColumn();
    }
}

if (!function_exists('generate_share_token')) {
    function generate_share_token(int $bytes = 32): string
    {
        $raw = random_bytes(max(8, $bytes));
        $token = rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
        return $token;
    }
}

if (!function_exists('ensure_course_share_token')) {
    function ensure_course_share_token(int $courseId): ?string
    {
        return shared_promotions_service()->ensureCourseShareToken($courseId);
    }
}

if (!function_exists('courses_has_referral_override')) {
    function courses_has_referral_override(bool $refresh = false): bool
    {
        static $cached = null;
        if ($refresh) {
            $cached = null;
        }
        if ($cached !== null) {
            return (bool) $cached;
        }
        global $pdo;
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM courses LIKE 'referral_override'");
            $cached = (bool) ($stmt && $stmt->fetch());
        } catch (Throwable $e) {
            $cached = false;
        }
        return (bool) $cached;
    }
}

if (!function_exists('ensure_courses_referral_settings_columns')) {
    function ensure_courses_referral_settings_columns(): bool
    {
        return shared_promotions_service()->ensureCoursesReferralSettingsColumns();
    }
}

if (!function_exists('courses_has_promo_columns')) {
    function courses_has_promo_columns(bool $refresh = false): bool
    {
        static $cached = null;
        if ($refresh) {
            $cached = null;
        }
        if ($cached !== null) {
            return (bool) $cached;
        }
        global $pdo;
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM courses LIKE 'promo_type'");
            $cached = (bool) ($stmt && $stmt->fetch());
        } catch (Throwable $e) {
            $cached = false;
        }
        return (bool) $cached;
    }
}

if (!function_exists('ensure_courses_promo_columns')) {
    function ensure_courses_promo_columns(): bool
    {
        return shared_promotions_service()->ensureCoursesPromoColumns();
    }
}

if (!function_exists('course_promo_data')) {
    function course_promo_data(array $course, ?int $nowTs = null, ?int $userId = null): array
    {
        return shared_promotions_service()->coursePromoData($course, $nowTs, $userId);
    }
}

if (!function_exists('ensure_course_promotions_table')) {
    function ensure_course_promotions_table(): bool
    {
        return shared_promotions_service()->ensureCoursePromotionsTable();
    }
}

if (!function_exists('ensure_course_promotion_targets_table')) {
    function ensure_course_promotion_targets_table(): bool
    {
        return shared_promotions_service()->ensureCoursePromotionTargetsTable();
    }
}

if (!function_exists('ensure_companies_table')) {
    function ensure_companies_table(): bool
    {
        return shared_company_schema_service()->ensureCompaniesTable();
    }
}

if (!function_exists('ensure_company_members_table')) {
    function ensure_company_members_table(): bool
    {
        return shared_company_schema_service()->ensureCompanyMembersTable();
    }
}

if (!function_exists('ensure_company_invites_table')) {
    function ensure_company_invites_table(): bool
    {
        return shared_company_schema_service()->ensureCompanyInvitesTable();
    }
}

if (!function_exists('ensure_course_promotion_company_targets_table')) {
    function ensure_course_promotion_company_targets_table(): bool
    {
        return shared_promotions_service()->ensureCoursePromotionCompanyTargetsTable();
    }
}

if (!function_exists('ensure_course_promotion_company_uses_table')) {
    function ensure_course_promotion_company_uses_table(): bool
    {
        return shared_promotions_service()->ensureCoursePromotionCompanyUsesTable();
    }
}

if (!function_exists('get_user_company_id')) {
    function get_user_company_id(int $userId): ?int
    {
        return shared_company_schema_service()->getUserCompanyId($userId);
    }
}

if (!function_exists('get_course_active_promotion_for_company')) {
    function get_course_active_promotion_for_company(int $courseId, int $companyId, int $userId): ?array
    {
        return shared_promotions_service()->getCourseActivePromotionForCompany($courseId, $companyId, $userId);
    }
}

if (!function_exists('get_course_active_promotion')) {
    function get_course_active_promotion(int $courseId): ?array
    {
        return shared_promotions_service()->getCourseActivePromotion($courseId);
    }
}

if (!function_exists('get_course_active_promotion_for_user')) {
    function get_course_active_promotion_for_user(int $courseId, int $userId): ?array
    {
        return shared_promotions_service()->getCourseActivePromotionForUser($courseId, $userId);
    }
}

if (!function_exists('sync_course_active_promo')) {
    function sync_course_active_promo(int $courseId): bool
    {
        return shared_promotions_service()->syncCourseActivePromo($courseId);
    }
}

if (!function_exists('users_has_referral_code')) {
    function users_has_referral_code(bool $refresh = false): bool
    {
        static $cached = null;
        if ($refresh) {
            $cached = null;
        }
        if ($cached !== null) {
            return (bool) $cached;
        }
        global $pdo;
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'referral_code'");
            $cached = (bool) ($stmt && $stmt->fetch());
        } catch (Throwable $e) {
            $cached = false;
        }
        return (bool) $cached;
    }
}

if (!function_exists('ensure_users_referral_code_column')) {
    function ensure_users_referral_code_column(): bool
    {
        return users_has_referral_code();
    }
}

if (!function_exists('users_has_signature_path')) {
    function users_has_signature_path(bool $refresh = false): bool
    {
        static $cached = null;
        if ($refresh) {
            $cached = null;
        }
        if ($cached !== null) {
            return (bool) $cached;
        }
        global $pdo;
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'signature_path'");
            $cached = (bool) ($stmt && $stmt->fetch());
        } catch (Throwable $e) {
            $cached = false;
        }
        return (bool) $cached;
    }
}

if (!function_exists('ensure_users_signature_column')) {
    function ensure_users_signature_column(): bool
    {
        return users_has_signature_path();
    }
}

if (!function_exists('ensure_referrals_table')) {
    function ensure_referrals_table(): bool
    {
        return shared_referral_service()->ensureReferralsTable();
    }
}

if (!function_exists('ensure_teacher_referral_settings_table')) {
    function ensure_teacher_referral_settings_table(): bool
    {
        try {
            $pdo = \App\Core\PdoRegistry::get();
            $stmt = $pdo->prepare(
                'SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table LIMIT 1'
            );
            $stmt->execute([':table' => 'teacher_referral_settings']);
            return (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('ensure_referral_commissions_table')) {
    function ensure_referral_commissions_table(): bool
    {
        return shared_referral_service()->ensureReferralCommissionsTable();
    }
}

if (!function_exists('normalize_referral_code')) {
    function normalize_referral_code(string $raw): string
    {
        return shared_referral_service()->normalizeCode($raw);
    }
}

if (!function_exists('generate_referral_code')) {
    function generate_referral_code(): string
    {
        return shared_referral_service()->generateCode();
    }
}

if (!function_exists('ensure_user_referral_code')) {
    function ensure_user_referral_code(int $userId): ?string
    {
        return shared_referral_service()->ensureUserReferralCode($userId);
    }
}

if (!function_exists('get_user_id_by_referral_code')) {
    function get_user_id_by_referral_code(string $code): ?int
    {
        return shared_referral_service()->getUserIdByReferralCode($code);
    }
}

if (!function_exists('set_user_referrer')) {
    function set_user_referrer(int $referredUserId, int $referrerUserId): bool
    {
        return shared_referral_service()->setUserReferrer($referredUserId, $referrerUserId);
    }
}

if (!function_exists('get_referrer_chain')) {
    function get_referrer_chain(int $userId): array
    {
        return shared_referral_service()->getReferrerChain($userId);
    }
}

if (!function_exists('user_has_active_enrollment')) {
    function user_has_active_enrollment(int $userId): bool
    {
        return shared_referral_service()->userHasActiveEnrollment($userId);
    }
}

if (!function_exists('get_teacher_referral_settings')) {
    function get_teacher_referral_settings(int $teacherId): array
    {
        $defaults = ['l1_rate' => 5.0, 'l2_rate' => 2.0, 'l2_enabled' => 0];
        if ($teacherId < 1) {
            return $defaults;
        }
        if (!ensure_teacher_referral_settings_table()) {
            return $defaults;
        }
        global $pdo;
        try {
            $stmt = $pdo->prepare('SELECT l1_rate, l2_rate, l2_enabled FROM teacher_referral_settings WHERE teacher_id = :tid LIMIT 1');
            $stmt->execute([':tid' => $teacherId]);
            $row = $stmt->fetch();
            if ($row) {
                return [
                    'l1_rate' => (float) ($row['l1_rate'] ?? $defaults['l1_rate']),
                    'l2_rate' => (float) ($row['l2_rate'] ?? $defaults['l2_rate']),
                    'l2_enabled' => (int) ($row['l2_enabled'] ?? $defaults['l2_enabled']),
                ];
            }
            $ins = $pdo->prepare('INSERT INTO teacher_referral_settings (teacher_id, l1_rate, l2_rate, l2_enabled) VALUES (:tid, :l1, :l2, :en)');
            $ins->execute([
                ':tid' => $teacherId,
                ':l1' => $defaults['l1_rate'],
                ':l2' => $defaults['l2_rate'],
                ':en' => $defaults['l2_enabled'],
            ]);
        } catch (Throwable $e) {
            return $defaults;
        }
        return $defaults;
    }
}

if (!function_exists('capture_referral_code')) {
    function capture_referral_code(): void
    {
        if (!isset($_GET['ref'])) {
            return;
        }
        $code = normalize_referral_code((string) $_GET['ref']);
        if ($code === '' || strlen($code) < 6) {
            return;
        }
        $_SESSION['referral_code'] = $code;
        $_SESSION['referral_at'] = time();
    }
}

// --- Exam schema helpers (local migrations) ---
if (!function_exists('exam_attempts_has_meeting_id')) {
    function exam_attempts_has_meeting_id(bool $refresh = false): bool
    {
        static $cached = null;
        if ($refresh) {
            $cached = null;
        }
        if ($cached !== null) {
            return (bool) $cached;
        }
        global $pdo;
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM exam_attempts LIKE 'meeting_id'");
            $cached = (bool) ($stmt && $stmt->fetch());
        } catch (Throwable $e) {
            $cached = false;
        }
        return (bool) $cached;
    }
}

if (!function_exists('ensure_exam_attempts_meeting_id_column')) {
    function ensure_exam_attempts_meeting_id_column(): bool
    {
        return exam_attempts_has_meeting_id();
    }
}

if (!function_exists('exam_questions_has_variant')) {
    function exam_questions_has_variant(bool $refresh = false): bool
    {
        static $cached = null;
        if ($refresh) {
            $cached = null;
        }
        if ($cached !== null) {
            return (bool) $cached;
        }
        global $pdo;
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM exam_questions LIKE 'variant'");
            $cached = (bool) ($stmt && $stmt->fetch());
        } catch (Throwable $e) {
            $cached = false;
        }
        return (bool) $cached;
    }
}

if (!function_exists('ensure_exam_questions_variant_column')) {
    function ensure_exam_questions_variant_column(): bool
    {
        return exam_questions_has_variant();
    }
}

if (!function_exists('exam_attempts_has_variant_index')) {
    function exam_attempts_has_variant_index(bool $refresh = false): bool
    {
        static $cached = null;
        if ($refresh) {
            $cached = null;
        }
        if ($cached !== null) {
            return (bool) $cached;
        }
        global $pdo;
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM exam_attempts LIKE 'variant_index'");
            $cached = (bool) ($stmt && $stmt->fetch());
        } catch (Throwable $e) {
            $cached = false;
        }
        return (bool) $cached;
    }
}

if (!function_exists('ensure_exam_attempts_variant_index_column')) {
    function ensure_exam_attempts_variant_index_column(): bool
    {
        return exam_attempts_has_variant_index();
    }
}

if (!function_exists('exam_files_has_drive_meta')) {
    function exam_files_has_drive_meta(bool $refresh = false): bool
    {
        static $cached = null;
        if ($refresh) {
            $cached = null;
        }
        if ($cached !== null) {
            return (bool) $cached;
        }
        global $pdo;
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM exam_files LIKE 'drive_file_id'");
            $cached = (bool) ($stmt && $stmt->fetch());
        } catch (Throwable $e) {
            $cached = false;
        }
        return (bool) $cached;
    }
}

if (!function_exists('ensure_exam_files_drive_meta_columns')) {
    function ensure_exam_files_drive_meta_columns(): bool
    {
        return exam_files_has_drive_meta();
    }
}

if (!function_exists('exam_sessions_has_drive_subject_columns')) {
    function exam_sessions_has_drive_subject_columns(bool $refresh = false): bool
    {
        static $cached = null;
        if ($refresh) {
            $cached = null;
        }
        if ($cached !== null) {
            return (bool) $cached;
        }
        global $pdo;
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM exam_sessions LIKE 'drive_subject_link'");
            $cached = (bool) ($stmt && $stmt->fetch());
        } catch (Throwable $e) {
            $cached = false;
        }
        return (bool) $cached;
    }
}

if (!function_exists('ensure_exam_sessions_drive_subject_columns')) {
    function ensure_exam_sessions_drive_subject_columns(): bool
    {
        return exam_sessions_has_drive_subject_columns();
    }
}

if (!function_exists('exam_sessions_has_settings_json')) {
    function exam_sessions_has_settings_json(bool $refresh = false): bool
    {
        static $cached = null;
        if ($refresh) {
            $cached = null;
        }
        if ($cached !== null) {
            return (bool) $cached;
        }
        global $pdo;
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM exam_sessions LIKE 'settings_json'");
            $cached = (bool) ($stmt && $stmt->fetch());
        } catch (Throwable $e) {
            $cached = false;
        }
        return (bool) $cached;
    }
}

if (!function_exists('ensure_exam_sessions_settings_json_column')) {
    function ensure_exam_sessions_settings_json_column(): bool
    {
        return exam_sessions_has_settings_json();
    }
}

if (!function_exists('certificates_has_table')) {
    function certificates_has_table(bool $refresh = false): bool
    {
        static $cached = null;
        if ($refresh) {
            $cached = null;
        }
        if ($cached !== null) {
            return (bool) $cached;
        }
        global $pdo;
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'certificates'");
            $cached = (bool) ($stmt && $stmt->fetch());
        } catch (Throwable $e) {
            $cached = false;
        }
        return (bool) $cached;
    }
}

if (!function_exists('ensure_certificates_table')) {
    function ensure_certificates_table(): bool
    {
        return certificates_has_table();
    }
}

if (!function_exists('generate_certificate_code')) {
    function generate_certificate_code(int $bytes = 12): string
    {
        $raw = bin2hex(random_bytes(max(6, $bytes)));
        $token = strtoupper(substr($raw, 0, 16));
        return 'CERT-' . $token;
    }
}

if (!function_exists('issue_certificate')) {
    function issue_certificate(int $userId, int $courseId, int $examId, ?int $attemptId, int $score, int $minScore): ?string
    {
        if ($score < $minScore) {
            return null;
        }
        if (!ensure_certificates_table()) {
            return null;
        }
        global $pdo;
        try {
            $stmt = $pdo->prepare('SELECT code, status FROM certificates WHERE user_id = :uid AND course_id = :cid LIMIT 1');
            $stmt->execute([':uid' => $userId, ':cid' => $courseId]);
            $existing = $stmt->fetch();
            if ($existing) {
                return ($existing['status'] ?? '') === 'issued' ? (string) $existing['code'] : null;
            }
        } catch (Throwable $e) {
            return null;
        }

        for ($i = 0; $i < 5; $i++) {
            $code = generate_certificate_code();
            try {
                $ins = $pdo->prepare('INSERT INTO certificates (user_id, course_id, exam_id, attempt_id, score, code, status, issued_at)
                    VALUES (:uid, :cid, :eid, :aid, :score, :code, "issued", NOW())');
                $ins->execute([
                    ':uid' => $userId,
                    ':cid' => $courseId,
                    ':eid' => $examId,
                    ':aid' => $attemptId,
                    ':score' => $score,
                    ':code' => $code,
                ]);
                return $code;
            } catch (PDOException $e) {
                try {
                    $stmt = $pdo->prepare('SELECT code, status FROM certificates WHERE user_id = :uid AND course_id = :cid LIMIT 1');
                    $stmt->execute([':uid' => $userId, ':cid' => $courseId]);
                    $existing = $stmt->fetch();
                    if ($existing) {
                        return ($existing['status'] ?? '') === 'issued' ? (string) $existing['code'] : null;
                    }
                } catch (Throwable $e2) {
                    return null;
                }
            } catch (Throwable $e) {
                return null;
            }
        }
        return null;
    }
}

if (!function_exists('ensure_platform_settings_table')) {
    function ensure_platform_settings_table(): void
    {
        shared_platform_settings_service()->ensureTable();
    }
}

if (!function_exists('platform_setting_get')) {
    function platform_setting_get(string $key, string $default): string
    {
        return shared_platform_settings_service()->get($key, $default);
    }
}

if (!function_exists('platform_setting_set')) {
    function platform_setting_set(string $key, string $value): void
    {
        shared_platform_settings_service()->set($key, $value);
    }
}

if (!function_exists('get_platform_referral_settings')) {
    function get_platform_referral_settings(): array
    {
        return shared_referral_service()->getPlatformReferralSettings();
    }
}

if (!function_exists('site_logo_url')) {
    function site_logo_url(): string
    {
        return shared_platform_settings_service()->siteLogoUrl();
    }
}

if (!function_exists('site_favicon_url')) {
    function site_favicon_url(): string
    {
        return shared_platform_settings_service()->siteFaviconUrl();
    }
}

if (!function_exists('site_footer_text')) {
    function site_footer_text(): string
    {
        return shared_platform_settings_service()->footerText();
    }
}

if (!function_exists('log_course_audit')) {
    function log_course_audit(?int $userId, int $courseId, string $action, array $meta = []): void
    {
        global $pdo;
        $metaJson = $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null;
        try {
            $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, target_type, target_id, meta_json, created_at) VALUES (:uid, :action, :type, :tid, :meta, NOW())');
            $stmt->execute([
                ':uid' => $userId,
                ':action' => $action,
                ':type' => 'course',
                ':tid' => $courseId,
                ':meta' => $metaJson,
            ]);
        } catch (Throwable $e) {
            // Ignore audit log errors.
        }
    }
}

if (!function_exists('notify_course_enrolled')) {
    function notify_course_enrolled(int $courseId, string $title, string $body, ?string $url = null): void
    {
        global $pdo;
        try {
            $stmt = $pdo->prepare('SELECT user_id FROM enrollments WHERE course_id = :cid AND status = "active"');
            $stmt->execute([':cid' => $courseId]);
            $rows = $stmt->fetchAll() ?: [];
            if (!$rows) {
                return;
            }
            $notif = $pdo->prepare('INSERT INTO notifications (user_id, title, body, url, created_at) VALUES (:uid, :title, :body, :url, NOW())');
            foreach ($rows as $row) {
                $notif->execute([
                    ':uid' => (int) $row['user_id'],
                    ':title' => $title,
                    ':body' => $body,
                    ':url' => $url,
                ]);
            }
        } catch (Throwable $e) {
            // Ignore notification errors.
        }
    }
}

if (!function_exists('notify_course_paid_students')) {
    function notify_course_paid_students(int $courseId, string $title, string $body, ?string $url = null): void
    {
        global $pdo;
        try {
            $stmt = $pdo->prepare(
                'SELECT DISTINCT o.user_id
                 FROM order_items oi
                 JOIN orders o ON o.id = oi.order_id
                 WHERE oi.course_id = :cid
                   AND o.status = "paid"'
            );
            $stmt->execute([':cid' => $courseId]);
            $rows = $stmt->fetchAll() ?: [];
            if (!$rows) {
                return;
            }

            $notif = $pdo->prepare(
                'INSERT INTO notifications (user_id, title, body, url, created_at)
                 VALUES (:uid, :title, :body, :url, NOW())'
            );
            foreach ($rows as $row) {
                $notif->execute([
                    ':uid' => (int) ($row['user_id'] ?? 0),
                    ':title' => $title,
                    ':body' => $body,
                    ':url' => $url,
                ]);
            }
        } catch (Throwable $e) {
            // Ignore notification errors.
        }
    }
}
// URL courante (chemin + query) normalisée avec BASE_PATH.
if (!function_exists('current_url')) {
    function current_url(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return $scheme . '://' . $host . $uri;
    }
}

if (!function_exists('sanitize_return_path')) {
    function sanitize_return_path(?string $input, string $fallback = '/'): string
    {
        $fallbackPath = '/' . ltrim((string) $fallback, '/');
        if ($fallbackPath === '//') {
            $fallbackPath = '/';
        }

        $value = trim((string) ($input ?? ''));
        if ($value === '') {
            return $fallbackPath;
        }

        // Reject absolute external URLs; keep only local path/query/fragment.
        if (preg_match('#^https?://#i', $value) === 1) {
            $parts = parse_url($value);
            $value = (string) ($parts['path'] ?? '');
            if ($value === '') {
                return $fallbackPath;
            }
            if (!empty($parts['query'])) {
                $value .= '?' . $parts['query'];
            }
            if (!empty($parts['fragment'])) {
                $value .= '#' . $parts['fragment'];
            }
        }

        if (!str_starts_with($value, '/')) {
            $value = '/' . ltrim($value, '/');
        }
        if (str_starts_with($value, '//')) {
            return $fallbackPath;
        }

        // Prevent header injection in redirects.
        $value = str_replace(["\r", "\n"], '', $value);

        return $value !== '' ? $value : $fallbackPath;
    }
}

if (!function_exists('redirect_self')) {
    function redirect_self(string $fallback = '/'): void
    {
        $target = sanitize_return_path($_SERVER['REQUEST_URI'] ?? null, $fallback);
        header('Location: ' . $target);
        exit;
    }
}

if (!function_exists('payment_service_bootstrap')) {
    function payment_service_bootstrap(): void
    {
        if (!class_exists(\App\Services\Shared\PaymentOpsService::class)
            || !class_exists(\App\Services\Shared\FedapayService::class)
            || !class_exists(\App\Services\Shared\ReferralService::class)
            || !class_exists(\App\Models\SharedReferralModel::class)
            || !class_exists(\App\Services\Shared\PlatformSettingsService::class)
            || !class_exists(\App\Models\SharedPlatformSettingsModel::class)
            || !class_exists(\App\Services\Shared\CompanySchemaService::class)
            || !class_exists(\App\Models\SharedCompanySchemaModel::class)
            || !class_exists(\App\Services\Shared\PromotionsService::class)
            || !class_exists(\App\Models\SharedPromotionModel::class)
        ) {
            $bootstrapFile = __DIR__ . '/../app/bootstrap.php';
            if (is_file($bootstrapFile)) {
                require_once $bootstrapFile;
            }
        }
    }
}

if (!function_exists('shared_referral_model')) {
    function shared_referral_model(): \App\Models\SharedReferralModel
    {
        payment_service_bootstrap();
        $pdo = \App\Core\PdoRegistry::get();
        if (!$pdo instanceof PDO) {
            throw new RuntimeException('PDO indisponible.');
        }
        static $models = [];
        $key = spl_object_id($pdo);
        if (!isset($models[$key])) {
            $models[$key] = new \App\Models\SharedReferralModel($pdo);
        }
        return $models[$key];
    }
}

if (!function_exists('shared_referral_service')) {
    function shared_referral_service(): \App\Services\Shared\ReferralService
    {
        payment_service_bootstrap();
        $pdo = \App\Core\PdoRegistry::get();
        if (!$pdo instanceof PDO) {
            throw new RuntimeException('PDO indisponible.');
        }
        static $services = [];
        $key = spl_object_id($pdo);
        if (!isset($services[$key])) {
            $services[$key] = new \App\Services\Shared\ReferralService(shared_referral_model());
        }
        return $services[$key];
    }
}

if (!function_exists('shared_platform_settings_model')) {
    function shared_platform_settings_model(): \App\Models\SharedPlatformSettingsModel
    {
        payment_service_bootstrap();
        $pdo = \App\Core\PdoRegistry::get();
        if (!$pdo instanceof PDO) {
            throw new RuntimeException('PDO indisponible.');
        }
        static $models = [];
        $key = spl_object_id($pdo);
        if (!isset($models[$key])) {
            $models[$key] = new \App\Models\SharedPlatformSettingsModel($pdo);
        }
        return $models[$key];
    }
}

if (!function_exists('shared_platform_settings_service')) {
    function shared_platform_settings_service(): \App\Services\Shared\PlatformSettingsService
    {
        payment_service_bootstrap();
        $pdo = \App\Core\PdoRegistry::get();
        if (!$pdo instanceof PDO) {
            throw new RuntimeException('PDO indisponible.');
        }
        static $services = [];
        $key = spl_object_id($pdo);
        if (!isset($services[$key])) {
            $services[$key] = new \App\Services\Shared\PlatformSettingsService(shared_platform_settings_model());
        }
        return $services[$key];
    }
}

if (!function_exists('shared_company_schema_model')) {
    function shared_company_schema_model(): \App\Models\SharedCompanySchemaModel
    {
        payment_service_bootstrap();
        $pdo = \App\Core\PdoRegistry::get();
        if (!$pdo instanceof PDO) {
            throw new RuntimeException('PDO indisponible.');
        }
        static $models = [];
        $key = spl_object_id($pdo);
        if (!isset($models[$key])) {
            $models[$key] = new \App\Models\SharedCompanySchemaModel($pdo);
        }
        return $models[$key];
    }
}

if (!function_exists('shared_company_schema_service')) {
    function shared_company_schema_service(): \App\Services\Shared\CompanySchemaService
    {
        payment_service_bootstrap();
        $pdo = \App\Core\PdoRegistry::get();
        if (!$pdo instanceof PDO) {
            throw new RuntimeException('PDO indisponible.');
        }
        static $services = [];
        $key = spl_object_id($pdo);
        if (!isset($services[$key])) {
            $services[$key] = new \App\Services\Shared\CompanySchemaService(shared_company_schema_model());
        }
        return $services[$key];
    }
}

if (!function_exists('shared_promotion_model')) {
    function shared_promotion_model(): \App\Models\SharedPromotionModel
    {
        payment_service_bootstrap();
        $pdo = \App\Core\PdoRegistry::get();
        if (!$pdo instanceof PDO) {
            throw new RuntimeException('PDO indisponible.');
        }
        static $models = [];
        $key = spl_object_id($pdo);
        if (!isset($models[$key])) {
            $models[$key] = new \App\Models\SharedPromotionModel($pdo);
        }
        return $models[$key];
    }
}

if (!function_exists('shared_promotions_service')) {
    function shared_promotions_service(): \App\Services\Shared\PromotionsService
    {
        payment_service_bootstrap();
        $pdo = \App\Core\PdoRegistry::get();
        if (!$pdo instanceof PDO) {
            throw new RuntimeException('PDO indisponible.');
        }
        static $services = [];
        $key = spl_object_id($pdo);
        if (!isset($services[$key])) {
            $services[$key] = new \App\Services\Shared\PromotionsService(
                shared_promotion_model(),
                shared_company_schema_service()
            );
        }
        return $services[$key];
    }
}
