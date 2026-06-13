<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    private const POPUP_MAX_ITEMS = 12;
    private const POPUP_DB_LIMIT = 6;
    private const POPUP_SEEN_SESSION_KEY = '_popup_seen_notification_ids';

    public static function render(string $view, array $data = []): void
    {
        $base = dirname(__DIR__);
        $helpers = $base . '/View/helpers.php';
        if (is_file($helpers)) {
            require_once $helpers;
        }
        $teacherUiHelpers = $base . '/View/teacher_ui.php';
        if (is_file($teacherUiHelpers)) {
            require_once $teacherUiHelpers;
        }

        if (!array_key_exists('user', $data) || !is_array($data['user']) || $data['user'] === []) {
            $currentUser = null;
            if (method_exists(__CLASS__, 'resolveCurrentUser')) {
                $currentUser = self::resolveCurrentUser();
            } else {
                try {
                    $auth = new Auth(PdoRegistry::get(), new Session());
                    $currentUser = $auth->currentUser();
                } catch (\Throwable $e) {
                    $currentUser = null;
                }
            }
            if (is_array($currentUser)) {
                $data['user'] = $currentUser;
            }
        }

        $path = $base . '/Views/' . str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $view) . '.php';
        if (!is_file($path)) {
            http_response_code(500);
            echo 'View not found: ' . htmlspecialchars($view, ENT_QUOTES, 'UTF-8');
            return;
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $path;
        $html = (string) ob_get_clean();

        echo self::injectThemeLayer($html, $view, $data);
    }

    private static function resolveCurrentUser(): ?array
    {
        try {
            $auth = new Auth(PdoRegistry::get(), new Session());
            return $auth->currentUser();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function injectThemeLayer(string $html, string $view, array $data): string
    {
        if ($html === '') {
            return $html;
        }

        $html = self::injectBodyRoleAttributes($html, $view);
        $html = self::injectSeoMeta($html, $view, $data);
        $html = self::injectFavicon($html);
        $html = self::injectIconFontLayer($html);
        $html = self::injectTypographyLayer($html);

        if (!self::shouldInjectGlobalAssets($html, $data)) {
            return self::normalizeRenderedHtml(self::injectNotificationRuntime($html));
        }

        $themeJsUrl = self::assetUrl('assets/js/theme.js');
        $siteAnnouncementJsUrl = self::assetUrl('assets/js/global_site_announcement.js');
        $popupsJsUrl = self::assetUrl('assets/js/global_popups.js');
        $pushJsUrl = self::assetUrl('assets/js/push_notifications.js');
        $feedbackVisibilityJsUrl = self::assetUrl('assets/js/feedback_visibility.js');

        $html = self::injectHeadAsset(
            $html,
            'assets/js/theme.js',
            '<script src="' . htmlspecialchars($themeJsUrl, ENT_QUOTES, 'UTF-8') . '" defer></script>'
        );
        $html = self::injectHeadAsset(
            $html,
            'assets/js/global_site_announcement.js',
            '<script src="' . htmlspecialchars($siteAnnouncementJsUrl, ENT_QUOTES, 'UTF-8') . '" defer></script>'
        );
        $html = self::injectHeadAsset(
            $html,
            'assets/js/global_popups.js',
            '<script src="' . htmlspecialchars($popupsJsUrl, ENT_QUOTES, 'UTF-8') . '" defer></script>'
        );
        $html = self::injectHeadAsset(
            $html,
            'assets/js/push_notifications.js',
            '<script src="' . htmlspecialchars($pushJsUrl, ENT_QUOTES, 'UTF-8') . '" defer></script>'
        );
        $html = self::injectHeadAsset(
            $html,
            'assets/js/feedback_visibility.js',
            '<script src="' . htmlspecialchars($feedbackVisibilityJsUrl, ENT_QUOTES, 'UTF-8') . '" defer></script>'
        );

        $pushPayload = self::buildPushPayload();
        if ($pushPayload !== null) {
            $json = json_encode(
                $pushPayload,
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_HEX_TAG
                | JSON_HEX_AMP
                | JSON_HEX_APOS
                | JSON_HEX_QUOT
                | JSON_INVALID_UTF8_SUBSTITUTE
            );
            if (is_string($json) && $json !== '') {
                $html = self::injectBodyPayload(
                    $html,
                    '<script id="global-push-data" type="application/json">' . $json . '</script>',
                    'id="global-push-data"'
                );
            }
        }

        $siteAnnouncementPayload = self::buildSiteAnnouncementPayload();
        if ($siteAnnouncementPayload !== null) {
            $json = json_encode(
                $siteAnnouncementPayload,
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_HEX_TAG
                | JSON_HEX_AMP
                | JSON_HEX_APOS
                | JSON_HEX_QUOT
                | JSON_INVALID_UTF8_SUBSTITUTE
            );
            if (is_string($json) && $json !== '') {
                $html = self::injectBodyPayload(
                    $html,
                    '<script id="global-site-announcement-data" type="application/json">' . $json . '</script>',
                    'id="global-site-announcement-data"'
                );
            }
        }

        return self::normalizeRenderedHtml($html);
    }

    /**
     * @param array<string,mixed> $data
     */
    private static function shouldInjectGlobalAssets(string $html, array $data): bool
    {
        $override = $data['injectGlobalAssets'] ?? null;
        if (is_bool($override)) {
            return $override;
        }

        return stripos($html, 'name="app-global-assets" content="off"') === false
            && stripos($html, "name='app-global-assets' content='off'") === false;
    }

    private static function injectNotificationRuntime(string $html): string
    {
        $pushPayload = self::buildPushPayload();
        if ($pushPayload === null) {
            return $html;
        }

        $pushJsUrl = self::assetUrl('assets/js/push_notifications.js');

        $html = self::injectHeadAsset(
            $html,
            'assets/js/push_notifications.js',
            '<script src="' . htmlspecialchars($pushJsUrl, ENT_QUOTES, 'UTF-8') . '" defer></script>'
        );

        $json = json_encode(
            $pushPayload,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
            | JSON_INVALID_UTF8_SUBSTITUTE
        );
        if (is_string($json) && $json !== '') {
            $html = self::injectBodyPayload(
                $html,
                '<script id="global-push-data" type="application/json">' . $json . '</script>',
                'id="global-push-data"'
            );
        }

        return $html;
    }

    private static function injectTypographyLayer(string $html): string
    {
        $typographyCssUrl = self::assetUrl('assets/css/app-typography.css');

        return self::injectHeadAsset(
            $html,
            'assets/css/app-typography.css',
            '<link rel="stylesheet" href="' . htmlspecialchars($typographyCssUrl, ENT_QUOTES, 'UTF-8') . '" data-app-typography="1">'
        );
    }

    private static function injectIconFontLayer(string $html): string
    {
        if (
            preg_match('/<link\b[^>]+assets\/vendor\/fontawesome\/all\.min\.css[^>]*>/i', $html) === 1
            || stripos($html, 'data-app-icons=') !== false
        ) {
            return $html;
        }

        $localIconCss = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'fontawesome' . DIRECTORY_SEPARATOR . 'all.min.css';
        $iconCssUrl = is_file($localIconCss)
            ? self::assetUrl('assets/vendor/fontawesome/all.min.css')
            : 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css';

        return self::injectHeadAsset(
            $html,
            'data-app-icons="1"',
            '<link rel="stylesheet" href="' . htmlspecialchars($iconCssUrl, ENT_QUOTES, 'UTF-8') . '" data-app-icons="1">'
        );
    }

    /**
     * Resolve per-view CSS assets.
     *
     * @param array<string, mixed> $data
     * @return array<int, string>
     */
    private static function resolveViewCss(string $view, array $data): array
    {
        return [];
    }

    private static function injectHeadAsset(string $html, string $needle, string $tag): string
    {
        if (stripos($html, $needle) !== false) {
            return $html;
        }

        if (stripos($html, '</head>') !== false) {
            return preg_replace('/<\/head>/i', $tag . "\n</head>", $html, 1) ?? $html;
        }

        return $tag . "\n" . $html;
    }

    private static function injectFavicon(string $html): string
    {
        if (
            stripos($html, 'rel="icon"') !== false
            || stripos($html, "rel='icon'") !== false
            || stripos($html, 'rel="shortcut icon"') !== false
            || stripos($html, "rel='shortcut icon'") !== false
        ) {
            return $html;
        }

        $faviconUrl = '';
        if (\function_exists('site_favicon_url')) {
            $faviconUrl = trim((string) \site_favicon_url());
        }
        if ($faviconUrl === '' && \function_exists('site_logo_url')) {
            $faviconUrl = trim((string) \site_logo_url());
        }
        if ($faviconUrl === '') {
            return $html;
        }

        $escapedUrl = htmlspecialchars($faviconUrl, ENT_QUOTES, 'UTF-8');
        $tag = '<link rel="icon" href="' . $escapedUrl . '">' . "\n"
            . '<link rel="apple-touch-icon" href="' . $escapedUrl . '">';

        return self::injectHeadAsset($html, $escapedUrl, $tag);
    }

    private static function injectBodyPayload(string $html, string $payloadTag, ?string $idNeedle = null): string
    {
        if ($idNeedle !== null && stripos($html, $idNeedle) !== false) {
            return $html;
        }

        if (stripos($html, '</body>') !== false) {
            return preg_replace('/<\/body>/i', $payloadTag . "\n</body>", $html, 1) ?? $html;
        }

        return $html . "\n" . $payloadTag;
    }

    private static function buildPopupPayload(): array
    {
        $session = new Session();
        $userId = (int) $session->get('user_id', 0);
        $items = [];
        $seenFingerprints = [];
        $append = static function (string $type, string $message, ?string $url = null, ?int $notificationId = null) use (&$items, &$seenFingerprints): void {
            self::appendPopupItem($items, $seenFingerprints, $type, $message, $url, $notificationId);
        };

        self::collectPopupMessagesFromDatabaseNotifications($append);

        return [
            'user_id' => $userId > 0 ? $userId : 0,
            'items' => $items,
        ];
    }

    private static function buildPushPayload(): ?array
    {
        $session = new Session();
        $userId = (int) $session->get('user_id', 0);
        if ($userId < 1) {
            return null;
        }

        $vapidPublicKey = defined('WEBPUSH_VAPID_PUBLIC_KEY')
            ? trim((string) WEBPUSH_VAPID_PUBLIC_KEY)
            : '';
        $webPushEnabled = false;
        if (defined('WEBPUSH_ENABLED') && WEBPUSH_ENABLED && $vapidPublicKey !== '') {
            try {
                $service = new \App\Services\Shared\WebPushService(
                    new \App\Models\SharedWebPushModel(PdoRegistry::get())
                );
                $webPushEnabled = $service->isEnabled();
            } catch (\Throwable $e) {
                $webPushEnabled = false;
            }
        }

        return [
            'user_id' => $userId,
            'feed_url' => self::assetUrl('api/notifications_feed'),
            'fallback_url' => self::assetUrl('notifications'),
            'poll_interval_ms' => 2500,
            'webpush_enabled' => $webPushEnabled,
            'vapid_public_key' => $webPushEnabled ? $vapidPublicKey : '',
            'push_subscription_url' => self::assetUrl('api/push_subscription'),
            'service_worker_url' => self::assetUrl('service_worker.js'),
            'csrf_token' => (new Csrf($session))->token(),
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    private static function buildSiteAnnouncementPayload(): ?array
    {
        try {
            $model = new \App\Models\WebHomeModel(PdoRegistry::get());
            $row = $model->fetchLatestPublicAnnouncement();
        } catch (\Throwable $e) {
            return null;
        }

        if (!is_array($row) || $row === []) {
            return null;
        }

        $title = self::cleanAnnouncementText((string) ($row['title'] ?? ''), 'Annonce');
        $summary = self::cleanAnnouncementText((string) ($row['summary'] ?? ''));
        $body = self::cleanAnnouncementText((string) ($row['body'] ?? ''));
        $message = $summary !== '' ? $summary : self::truncatePopupText($body, 180);
        $url = trim((string) ($row['action_url'] ?? ''));

        if (self::isNonPublicAnnouncementUrl($url)) {
            $url = '';
        } else {
            $url = self::normalizePopupUrl($url) ?? '';
        }

        if ($title === '' && $message === '') {
            return null;
        }

        return [
            'id' => max(0, (int) ($row['id'] ?? 0)),
            'title' => $title !== '' ? $title : 'Annonce',
            'message' => $message,
            'url' => $url,
            'linkLabel' => 'En savoir plus',
        ];
    }

    private static function cleanAnnouncementText(string $text, string $fallback = ''): string
    {
        $decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalized = preg_replace('/\s+/u', ' ', trim(strip_tags($decoded)));
        $value = trim((string) ($normalized ?? ''));

        return $value !== '' ? $value : $fallback;
    }

    private static function isNonPublicAnnouncementUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '') {
            return true;
        }

        $normalized = str_replace('&amp;', '&', strtolower($url));
        return str_contains($normalized, '/notifications?scope=admin')
            || str_contains($normalized, '/admin/notifications')
            || str_contains($normalized, '/admin/');
    }

    private static function collectPopupMessagesFromDatabaseNotifications(callable $append): void
    {
        $session = new Session();
        $userId = (int) $session->get('user_id', 0);
        if ($userId < 1) {
            return;
        }
        $roles = NotificationRouting::normalizeRoles($session->get('user_roles', []));

        // Preferred strategy: show only unread notifications never shown in popup.
        // This prevents replaying the full unread backlog at every new login.
        try {
            $pdo = PdoRegistry::get();
            $stmt = $pdo->prepare(
                'SELECT id, title, body, url, announcement_id
                 FROM notifications
                 WHERE user_id = :uid
                   AND is_read = 0
                   AND popup_shown_at IS NULL
                 ORDER BY id DESC
                 LIMIT ' . self::POPUP_DB_LIMIT
            );
            $stmt->execute([':uid' => $userId]);
            $rows = $stmt->fetchAll();
            if (is_array($rows) && $rows !== []) {
                $rows = array_reverse($rows);
                $idsToMark = [];
                foreach ($rows as $row) {
                    $id = (int) ($row['id'] ?? 0);
                    if ($id < 1) {
                        continue;
                    }
                    if (NotificationRouting::shouldHideNotification($row, $roles)) {
                        continue;
                    }
                    $title = self::normalizePopupText((string) ($row['title'] ?? ''));
                    $body = self::normalizePopupText((string) ($row['body'] ?? ''));
                    $message = $title !== '' ? $title : $body;
                    if ($message === '') {
                        continue;
                    }
                    if ($title !== '' && $body !== '' && strcasecmp($title, $body) !== 0) {
                        $message .= ' - ' . $body;
                    }
                    $message = self::truncatePopupText($message, 220);
                    $append('info', $message, self::resolveNotificationUrl($row, $roles), $id);
                    $idsToMark[] = $id;
                }
                self::markPopupNotificationsShown($pdo, $userId, $idsToMark);
                self::markAllUnreadPopupNotificationsShown($pdo, $userId);
            }
            return;
        } catch (\Throwable $e) {
            // Fallback kept for old schemas where popup_shown_at column is missing.
        }

        $seenIds = [];
        $seenRaw = $session->get(self::POPUP_SEEN_SESSION_KEY, []);
        if (is_array($seenRaw)) {
            foreach ($seenRaw as $id) {
                $value = (int) $id;
                if ($value > 0) {
                    $seenIds[$value] = true;
                }
            }
        }

        try {
            $pdo = PdoRegistry::get();
            try {
                $stmt = $pdo->prepare(
                    'SELECT id, title, body, url, announcement_id
                     FROM notifications
                     WHERE user_id = :uid AND is_read = 0
                     ORDER BY created_at DESC
                     LIMIT ' . self::POPUP_DB_LIMIT
                );
                $stmt->execute([':uid' => $userId]);
                $rows = $stmt->fetchAll();
            } catch (\Throwable $e) {
                $stmt = $pdo->prepare(
                    'SELECT id, title, body, url
                     FROM notifications
                     WHERE user_id = :uid AND is_read = 0
                     ORDER BY created_at DESC
                     LIMIT ' . self::POPUP_DB_LIMIT
                );
                $stmt->execute([':uid' => $userId]);
                $rows = $stmt->fetchAll();
            }
        } catch (\Throwable $e) {
            return;
        }

        if (!is_array($rows) || $rows === []) {
            return;
        }

        $freshRows = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id < 1 || isset($seenIds[$id])) {
                continue;
            }
            $freshRows[] = $row;
            $seenIds[$id] = true;
        }

        if ($freshRows === []) {
            return;
        }

        $freshRows = array_reverse($freshRows);
        foreach ($freshRows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if (NotificationRouting::shouldHideNotification($row, $roles)) {
                continue;
            }
            $title = self::normalizePopupText((string) ($row['title'] ?? ''));
            $body = self::normalizePopupText((string) ($row['body'] ?? ''));
            $message = $title !== '' ? $title : $body;
            if ($message === '') {
                continue;
            }
            if ($title !== '' && $body !== '' && strcasecmp($title, $body) !== 0) {
                $message .= ' - ' . $body;
            }
            $message = self::truncatePopupText($message, 220);
            $append('info', $message, self::resolveNotificationUrl($row, $roles), $id);
        }

        $seenList = array_values(array_filter(array_map('intval', array_keys($seenIds)), static fn (int $id): bool => $id > 0));
        if (count($seenList) > 250) {
            $seenList = array_slice($seenList, -250);
        }
        $session->set(self::POPUP_SEEN_SESSION_KEY, $seenList);
    }

    private static function appendPopupItem(
        array &$items,
        array &$seenFingerprints,
        string $type,
        string $message,
        ?string $url = null,
        ?int $notificationId = null
    ): void {
        if (count($items) >= self::POPUP_MAX_ITEMS) {
            return;
        }

        $message = self::normalizePopupText($message);
        if ($message === '') {
            return;
        }
        $message = self::truncatePopupText($message, 260);

        $normalizedType = self::normalizePopupType($type);
        $normalizedUrl = self::normalizePopupUrl($url);
        $fingerprint = hash('sha256', $normalizedType . '|' . strtolower($message) . '|' . (string) $normalizedUrl);
        if (isset($seenFingerprints[$fingerprint])) {
            return;
        }
        $seenFingerprints[$fingerprint] = true;

        $item = [
            'type' => $normalizedType,
            'message' => $message,
        ];
        $id = (int) ($notificationId ?? 0);
        if ($id > 0) {
            $item['id'] = $id;
        }
        if ($normalizedUrl !== null) {
            $item['url'] = $normalizedUrl;
        }
        $items[] = $item;
    }

    /**
     * @param array<int,int> $notificationIds
     */
    private static function markPopupNotificationsShown(\PDO $pdo, int $userId, array $notificationIds): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $notificationIds), static fn (int $id): bool => $id > 0)));
        if ($userId < 1 || $ids === []) {
            return;
        }

        $placeholders = [];
        $params = [':uid' => $userId];
        foreach ($ids as $index => $id) {
            $key = ':id' . $index;
            $placeholders[] = $key;
            $params[$key] = $id;
        }

        $stmt = $pdo->prepare(
            'UPDATE notifications
             SET popup_shown_at = NOW()
             WHERE user_id = :uid
               AND popup_shown_at IS NULL
               AND id IN (' . implode(',', $placeholders) . ')'
        );
        $stmt->execute($params);
    }

    private static function markAllUnreadPopupNotificationsShown(\PDO $pdo, int $userId): void
    {
        if ($userId < 1) {
            return;
        }

        $stmt = $pdo->prepare(
            'UPDATE notifications
             SET popup_shown_at = NOW()
             WHERE user_id = :uid
               AND is_read = 0
               AND popup_shown_at IS NULL'
        );
        $stmt->execute([':uid' => $userId]);
    }

    private static function normalizePopupType(string $type): string
    {
        $normalized = strtolower(trim($type));
        if (!in_array($normalized, ['success', 'error', 'warning', 'info'], true)) {
            return 'info';
        }
        return $normalized;
    }

    private static function normalizePopupText(string $text): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($text));
        return trim((string) ($normalized ?? $text));
    }

    private static function truncatePopupText(string $text, int $maxLength): string
    {
        if ($maxLength < 4) {
            return $text;
        }

        if (\function_exists('mb_strlen') && \function_exists('mb_substr')) {
            if (mb_strlen($text) <= $maxLength) {
                return $text;
            }
            return rtrim(mb_substr($text, 0, $maxLength - 3)) . '...';
        }

        if (strlen($text) <= $maxLength) {
            return $text;
        }
        return rtrim(substr($text, 0, $maxLength - 3)) . '...';
    }

    private static function normalizePopupUrl(?string $url): ?string
    {
        $candidate = trim((string) ($url ?? ''));
        if ($candidate === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $candidate) === 1) {
            return $candidate;
        }
        if (str_starts_with($candidate, '//') || str_starts_with(strtolower($candidate), 'javascript:')) {
            return null;
        }

        return self::assetUrl(self::normalizeStoredPath($candidate));
    }

    private static function resolveNotificationUrl(array $notification, array $roles): ?string
    {
        $resolved = NotificationRouting::resolveActionUrl(
            $notification,
            $roles,
            fn (string $path): string => self::assetUrl($path)
        );
        if (!NotificationRouting::hasMeaningfulTarget($resolved, self::assetUrl('notifications'))) {
            return null;
        }

        return $resolved;
    }

    private static function injectBodyRoleAttributes(string $html, string $view): string
    {
        $normalizedView = str_replace('\\', '/', trim($view));
        $segments = explode('/', $normalizedView);
        $role = preg_replace('/[^a-z0-9_-]/i', '', strtolower((string) ($segments[0] ?? 'web')));
        if ($role === '') {
            $role = 'web';
        }

        $dataView = htmlspecialchars($normalizedView, ENT_QUOTES, 'UTF-8');
        $dataRole = htmlspecialchars($role, ENT_QUOTES, 'UTF-8');
        $roleClass = 'app-view app-role-' . $role;

        $patched = preg_replace_callback(
            '/<body\b([^>]*)>/i',
            static function (array $matches) use ($dataView, $dataRole, $roleClass): string {
                $attrs = $matches[1] ?? '';

                if (stripos($attrs, 'data-view=') === false) {
                    $attrs .= ' data-view="' . $dataView . '"';
                }
                if (stripos($attrs, 'data-role=') === false) {
                    $attrs .= ' data-role="' . $dataRole . '"';
                }

                if (preg_match('/\bclass\s*=\s*"([^"]*)"/i', $attrs, $cm)) {
                    $existing = trim((string) $cm[1]);
                    $newClass = trim($existing . ' ' . $roleClass);
                    $attrs = preg_replace('/\bclass\s*=\s*"[^"]*"/i', 'class="' . htmlspecialchars($newClass, ENT_QUOTES, 'UTF-8') . '"', $attrs, 1) ?? $attrs;
                } else {
                    $attrs .= ' class="' . htmlspecialchars($roleClass, ENT_QUOTES, 'UTF-8') . '"';
                }

                return '<body' . $attrs . '>';
            },
            $html,
            1
        );

        return $patched ?? $html;
    }

    private static function injectSeoMeta(string $html, string $view, array $data): string
    {
        $canonicalUrl = self::resolveCanonicalUrl($view, $data);
        if ($canonicalUrl !== '') {
            $html = self::injectHeadAsset(
                $html,
                'rel="canonical"',
                '<link rel="canonical" href="' . htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') . '">'
            );
        }

        if (self::shouldNoindexView($view)) {
            $html = self::injectHeadAsset(
                $html,
                'name="robots"',
                '<meta name="robots" content="noindex, nofollow">'
            );
        }

        return $html;
    }

    private static function resolveCanonicalUrl(string $view, array $data): string
    {
        $override = trim((string) ($data['seoCanonicalUrl'] ?? ''));
        if ($override !== '') {
            return $override;
        }

        $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = (string) (parse_url($requestUri, PHP_URL_PATH) ?? '/');
        $query = (string) (parse_url($requestUri, PHP_URL_QUERY) ?? '');

        $basePath = rtrim((string) (defined('BASE_PATH') ? BASE_PATH : ''), '/');
        if ($basePath !== '' && $basePath !== '/' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath)) ?: '/';
        }

        $normalizedPath = '/' . ltrim($path, '/');
        $params = [];
        if ($query !== '') {
            parse_str($query, $params);
        }
        if (!is_array($params)) {
            $params = [];
        }

        $canonicalParams = self::canonicalQueryForPath($normalizedPath, $params, $view, $data);
        $relativePath = ltrim($normalizedPath, '/');
        if ($canonicalParams !== []) {
            $relativePath .= '?' . http_build_query($canonicalParams);
        }

        return self::absoluteUrl($relativePath);
    }

    /**
     * @param array<string,mixed> $params
     * @param array<string,mixed> $data
     * @return array<string,string|int>
     */
    private static function canonicalQueryForPath(string $path, array $params, string $view, array $data): array
    {
        $route = trim($path, '/');
        if ($route === 'course' || str_replace('\\', '/', $view) === 'web/course') {
            $slug = trim((string) ($data['course']['slug'] ?? ($params['slug'] ?? '')));
            if ($slug !== '') {
                return ['slug' => $slug];
            }
            $id = (int) ($data['course']['id'] ?? ($params['id'] ?? 0));
            if ($id > 0) {
                return ['id' => $id];
            }
        }

        return [];
    }

    private static function shouldNoindexView(string $view): bool
    {
        $normalized = strtolower(str_replace('\\', '/', trim($view)));
        $privatePrefixes = ['auth/', 'admin/', 'teacher/', 'student/', 'company/', 'drive/'];
        foreach ($privatePrefixes as $prefix) {
            if (str_starts_with($normalized, $prefix)) {
                return true;
            }
        }

        return in_array($normalized, [
            'web/checkout',
            'web/support',
            'web/notifications',
            'web/profile',
            'web/payment_return',
            'web/certificate',
        ], true);
    }

    private static function assetUrl(string $path): string
    {
        $path = self::appendAssetVersion($path);
        if (\function_exists('site_url')) {
            return \site_url($path);
        }
        return '/' . ltrim($path, '/');
    }

    private static function appendAssetVersion(string $path): string
    {
        $trimmed = ltrim($path, '/');
        if (
            !str_starts_with($trimmed, 'assets/')
            && $trimmed !== 'service_worker.js'
        ) {
            return $path;
        }

        $parts = explode('?', $trimmed, 2);
        $relative = $parts[0] ?? $trimmed;
        $query = $parts[1] ?? '';
        $fullPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (!is_file($fullPath)) {
            return $path;
        }

        $version = (string) (@filemtime($fullPath) ?: time());
        if ($query !== '') {
            parse_str($query, $params);
            $params['v'] = $version;
            return $relative . '?' . http_build_query($params);
        }

        return $relative . '?v=' . rawurlencode($version);
    }

    private static function normalizeRenderedHtml(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        $html = self::repairUiText($html);
        $html = self::repairBrokenInlinePercentStyles($html);

        return $html;
    }

    private static function repairMojibake(string $html): string
    {
        $replacements = [
            'Ã€' => 'À',
            'Ã' => 'Á',
            'Ã‚' => 'Â',
            'Ã„' => 'Ä',
            'Ã‡' => 'Ç',
            'Ãˆ' => 'È',
            'Ã‰' => 'É',
            'ÃŠ' => 'Ê',
            'Ã‹' => 'Ë',
            'ÃŽ' => 'Î',
            'Ã' => 'Ï',
            'Ã”' => 'Ô',
            'Ã–' => 'Ö',
            'Ã™' => 'Ù',
            'Ã›' => 'Û',
            'Ãœ' => 'Ü',
            'ÃŸ' => 'ß',
            'Ã ' => 'à',
            'Ã¡' => 'á',
            'Ã¢' => 'â',
            'Ã¤' => 'ä',
            'Ã§' => 'ç',
            'Ã¨' => 'è',
            'Ã©' => 'é',
            'Ãª' => 'ê',
            'Ã«' => 'ë',
            'Ã®' => 'î',
            'Ã¯' => 'ï',
            'Ã´' => 'ô',
            'Ã¶' => 'ö',
            'Ã¹' => 'ù',
            'Ã»' => 'û',
            'Ã¼' => 'ü',
            'Ã±' => 'ñ',
            'Ã‘' => 'Ñ',
            'Ã—' => '×',
            'Å“' => 'œ',
            'Å’' => 'Œ',
            'â€™' => '’',
            'â€˜' => '‘',
            'â€œ' => '“',
            'â€' => '”',
            'â€¢' => '•',
            'â€¦' => '…',
            'â€“' => '–',
            'â€”' => '—',
            'â‚¬' => '€',
            'Â«' => '«',
            'Â»' => '»',
            'Â·' => '·',
            'Â ' => ' ',
            'Â ' => ' ',
            'lâ€™' => 'l’',
            'dâ€™' => 'd’',
            'Dâ€™' => 'D’',
            'quâ€™' => 'qu’',
            'Quâ€™' => 'Qu’',
            'nâ€™' => 'n’',
            'Nâ€™' => 'N’',
            'sâ€™' => 's’',
            'Sâ€™' => 'S’',
            'câ€™' => 'c’',
            'Câ€™' => 'C’',
            'jâ€™' => 'j’',
            'Jâ€™' => 'J’',
            'tâ€™' => 't’',
            'Tâ€™' => 'T’',
            'mâ€™' => 'm’',
            'Mâ€™' => 'M’',
            'Lâ€™' => 'L’',
            'eisponible' => 'Disponible',
            'eisponibles' => 'Disponibles',
            'eisponibilit' => 'disponibilit',
            'EcoleNumérique' => 'École Numérique',
            'Ecole Numérique' => 'École Numérique',
            'Ecole Numerique' => 'École Numérique',
        ];

        $html = strtr($html, $replacements);

        return str_replace('Â', '', $html);
    }

    private static function repairUiText(string $html): string
    {
        $replacements = [
            'Ã€' => 'À',
            'Ã' => 'Á',
            'Ã‚' => 'Â',
            'Ã„' => 'Ä',
            'Ã‡' => 'Ç',
            'Ãˆ' => 'È',
            'Ã‰' => 'É',
            'ÃŠ' => 'Ê',
            'Ã‹' => 'Ë',
            'ÃŽ' => 'Î',
            'ÃÏ' => 'Ï',
            'Ã”' => 'Ô',
            'Ã–' => 'Ö',
            'Ã™' => 'Ù',
            'Ã›' => 'Û',
            'Ãœ' => 'Ü',
            'ÃŸ' => 'ß',
            'Ã ' => 'à',
            'Ã¡' => 'á',
            'Ã¢' => 'â',
            'Ã¤' => 'ä',
            'Ã§' => 'ç',
            'Ã¨' => 'è',
            'Ã©' => 'é',
            'Ãª' => 'ê',
            'Ã«' => 'ë',
            'Ã®' => 'î',
            'Ã¯' => 'ï',
            'Ã´' => 'ô',
            'Ã¶' => 'ö',
            'Ã¹' => 'ù',
            'Ã»' => 'û',
            'Ã¼' => 'ü',
            'Ã±' => 'ñ',
            'Ã‘' => 'Ñ',
            'Å“' => 'œ',
            'Å’' => 'Œ',
            'â€™' => '’',
            'â€˜' => '‘',
            'â€œ' => '“',
            'â€' => '”',
            'â€¢' => '•',
            'â€¦' => '…',
            'â€“' => '–',
            'â€”' => '—',
            'â‚¬' => '€',
            'Â«' => '«',
            'Â»' => '»',
            'Â·' => '·',
            'Â ' => ' ',
            '&amp;Eacute;' => 'É',
            '&amp;EACUTE;' => 'É',
            '&amp;eacute;' => 'é',
            '&amp;Egrave;' => 'È',
            '&amp;egrave;' => 'è',
            '&amp;Ecirc;' => 'Ê',
            '&amp;ecirc;' => 'ê',
            '&amp;Agrave;' => 'À',
            '&amp;agrave;' => 'à',
            '&amp;Acirc;' => 'Â',
            '&amp;acirc;' => 'â',
            '&amp;Icirc;' => 'Î',
            '&amp;icirc;' => 'î',
            '&amp;Ocirc;' => 'Ô',
            '&amp;ocirc;' => 'ô',
            '&amp;Ucirc;' => 'Û',
            '&amp;ucirc;' => 'û',
            '&amp;Ugrave;' => 'Ù',
            '&amp;ugrave;' => 'ù',
            '&amp;Ccedil;' => 'Ç',
            '&amp;ccedil;' => 'ç',
            '&amp;laquo;' => '«',
            '&amp;raquo;' => '»',
            '&amp;rsquo;' => '’',
            '&amp;ldquo;' => '“',
            '&amp;rdquo;' => '”',
            '&amp;middot;' => '·',
            '&amp;hellip;' => '…',
            '&amp;nbsp;' => ' ',
            'EcoleNumérique' => 'École Numérique',
            'Ecole Numérique' => 'École Numérique',
            'Ecole Numerique' => 'École Numérique',
        ];

        $html = strtr($html, $replacements);

        return str_replace('Â', '', $html);
    }

    private static function repairBrokenInlinePercentStyles(string $html): string
    {
        $fixed = preg_replace('/(width\s*:\s*[^";]+)>%;/i', '$1%;', $html);
        return is_string($fixed) ? $fixed : $html;
    }

    private static function absoluteUrl(string $path): string
    {
        if (\function_exists('abs_url')) {
            return \abs_url($path);
        }

        $base = (string) (defined('APP_BASE_URL') ? APP_BASE_URL : '');
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }

    private static function normalizeStoredPath(string $path): string
    {
        $normalized = ltrim(trim($path), '/');
        $basePath = ltrim(rtrim((string) (defined('BASE_PATH') ? BASE_PATH : ''), '/'), '/');
        if ($basePath !== '' && ($normalized === $basePath || str_starts_with($normalized, $basePath . '/'))) {
            $normalized = ltrim(substr($normalized, strlen($basePath)), '/');
        }
        return $normalized;
    }
}
