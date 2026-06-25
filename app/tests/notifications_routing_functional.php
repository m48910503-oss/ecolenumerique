<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only\n";
    exit(1);
}

$rootDir = dirname(__DIR__, 2);
chdir($rootDir);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', '/ecolenumerique');
}
if (!defined('APP_BASE_URL')) {
    define('APP_BASE_URL', 'http://localhost/ecolenumerique');
}

require_once $rootDir . '/app/bootstrap.php';
require_once $rootDir . '/includes/helpers.php';

/**
 * @param array<int,string> $errors
 */
function assert_true(bool $condition, string $message, array &$errors): void
{
    if (!$condition) {
        $errors[] = $message;
    }
}

/**
 * @param array<int,string> $columns
 */
function register_sqlite_schema(PDO $pdo, string $table, array $columns): void
{
    $stmtTable = $pdo->prepare('INSERT INTO INFORMATION_SCHEMA.TABLES (TABLE_SCHEMA, TABLE_NAME) VALUES (:schema, :table)');
    $stmtColumn = $pdo->prepare('INSERT INTO INFORMATION_SCHEMA.COLUMNS (TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME) VALUES (:schema, :table, :column)');

    $stmtTable->execute([
        ':schema' => 'main',
        ':table' => $table,
    ]);

    foreach ($columns as $column) {
        $stmtColumn->execute([
            ':schema' => 'main',
            ':table' => $table,
            ':column' => $column,
        ]);
    }
}

function build_sqlite_pdo(): PDO
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("ATTACH DATABASE ':memory:' AS INFORMATION_SCHEMA");
    $pdo->sqliteCreateFunction('DATABASE', static fn(): string => 'main', 0);
    $pdo->exec('CREATE TABLE INFORMATION_SCHEMA.TABLES (TABLE_SCHEMA TEXT, TABLE_NAME TEXT)');
    $pdo->exec('CREATE TABLE INFORMATION_SCHEMA.COLUMNS (TABLE_SCHEMA TEXT, TABLE_NAME TEXT, COLUMN_NAME TEXT)');

    return $pdo;
}

$errors = [];
$_SESSION = [];
$_GET = [];
$_POST = [];
$_COOKIE = [];
$_SERVER = [];

$pdo = build_sqlite_pdo();
\App\Core\PdoRegistry::set($pdo);

$pdo->exec('CREATE TABLE users (
    id INTEGER PRIMARY KEY,
    first_name TEXT,
    last_name TEXT,
    email TEXT,
    status TEXT,
    email_verified_at TEXT,
    roles_json TEXT,
    notification_preferences_json TEXT
)');
register_sqlite_schema($pdo, 'users', ['id', 'first_name', 'last_name', 'email', 'status', 'email_verified_at', 'roles_json', 'notification_preferences_json']);

$pdo->exec('CREATE TABLE notifications (
    id INTEGER PRIMARY KEY,
    user_id INTEGER,
    title TEXT,
    body TEXT,
    summary TEXT,
    importance TEXT,
    url TEXT,
    announcement_id INTEGER,
    variant TEXT,
    is_read INTEGER,
    clicked_at TEXT,
    read_at TEXT,
    created_at TEXT,
    popup_shown_at TEXT
)');
register_sqlite_schema($pdo, 'notifications', ['id', 'user_id', 'title', 'body', 'summary', 'importance', 'url', 'announcement_id', 'variant', 'is_read', 'clicked_at', 'read_at', 'created_at', 'popup_shown_at']);

$pdo->exec("INSERT INTO users (id, first_name, last_name, email, status, email_verified_at, roles_json, notification_preferences_json) VALUES
    (1, 'Admin', 'Root', 'admin@example.test', 'active', '2026-01-01 00:00:00', '[\"admin\"]', NULL),
    (2, 'Admin', 'Secondary', 'admin2@example.test', 'active', '2026-01-01 00:00:00', '[\"admin_secondary\"]', NULL),
    (3, 'Support', 'Agent', 'support@example.test', 'active', '2026-01-01 00:00:00', '[\"support\"]', NULL),
    (4, 'Teacher', 'Owner', 'teacher@example.test', 'active', '2026-01-01 00:00:00', '[\"teacher\"]', NULL),
    (5, 'Student', 'Learner', 'student@example.test', 'active', '2026-01-01 00:00:00', '[\"student\"]', NULL),
    (6, 'Hybrid', 'AdminTeacher', 'hybrid@example.test', 'active', '2026-01-01 00:00:00', '[\"admin\",\"teacher\"]', NULL)
");

$pdo->exec("INSERT INTO notifications (id, user_id, title, body, summary, importance, url, announcement_id, variant, is_read, clicked_at, read_at, created_at, popup_shown_at) VALUES
    (10, 1, 'Annonce plateforme', 'Annonce interne', 'Annonce interne', 'info', 'catalogue', 7, 'A', 0, NULL, NULL, '2026-04-05 09:00:00', NULL),
    (11, 1, 'Ticket support mis a jour', 'Conversation support en attente.', 'Conversation support', 'important', NULL, NULL, NULL, 0, NULL, NULL, '2026-04-05 10:00:00', NULL),
    (20, 5, 'Commission de parrainage disponible', 'Votre filleul a validé son achat.', 'Parrainage', 'info', NULL, NULL, NULL, 0, NULL, NULL, '2026-04-05 11:00:00', NULL),
    (21, 5, 'Demande entreprise refusee', 'Aucune action supplementaire requise.', 'Entreprise', 'info', 'notifications?scope=business', NULL, NULL, 0, NULL, NULL, '2026-04-05 12:00:00', NULL)
");

assert_true(\App\Core\NotificationRouting::shouldExcludeAnnouncements(['admin']) === true, 'Admin-like roles must exclude announcement rows from notifications feeds.', $errors);
assert_true(\App\Core\NotificationRouting::shouldExcludeAnnouncements(['teacher']) === false, 'Teacher role must not exclude operational notifications.', $errors);
assert_true(\App\Core\NotificationRouting::shouldHideNotification(['announcement_id' => 7], ['admin']) === true, 'Admin-like roles must hide announcement notifications.', $errors);
assert_true(\App\Core\NotificationRouting::shouldHideNotification(['announcement_id' => 0], ['admin']) === false, 'Non-announcement notifications must remain visible.', $errors);

$_SESSION['user_id'] = 1;
$_SESSION['user_roles'] = ['admin'];
$adminService = new \App\Services\Web\NotificationsService(new \App\Models\WebNotificationsModel($pdo));
$adminResult = $adminService->handle(1, 'GET', [], []);
$adminNotifications = is_array($adminResult['notifications'] ?? null) ? $adminResult['notifications'] : [];

assert_true(count($adminNotifications) === 1, 'Admin notifications page must hide announcement rows.', $errors);
assert_true((int) (($adminNotifications[0]['id'] ?? 0)) === 11, 'Admin notifications page should keep operational notifications visible.', $errors);
assert_true((int) ($adminResult['unreadCount'] ?? 0) === 1, 'Admin unread count must exclude announcement rows.', $errors);
assert_true(!empty($adminNotifications[0]['has_open_action']), 'Admin support notification should expose an open action.', $errors);
assert_true(str_contains((string) ($adminNotifications[0]['resolved_url'] ?? ''), '/admin/support'), 'Admin support notification should resolve to admin/support.', $errors);

$_SESSION['user_id'] = 5;
$_SESSION['user_roles'] = ['student'];
$studentService = new \App\Services\Web\NotificationsService(new \App\Models\WebNotificationsModel($pdo));
$studentResult = $studentService->handle(5, 'GET', [], []);
$studentNotifications = is_array($studentResult['notifications'] ?? null) ? $studentResult['notifications'] : [];
$studentById = [];
foreach ($studentNotifications as $notification) {
    $studentById[(int) ($notification['id'] ?? 0)] = $notification;
}

assert_true(isset($studentById[20]), 'Student referral notification should be present.', $errors);
assert_true(isset($studentById[21]), 'Student legacy notifications-center link should stay visible.', $errors);
assert_true(!empty($studentById[20]['has_open_action']), 'Referral notification should expose an open action.', $errors);
assert_true(str_contains((string) ($studentById[20]['resolved_url'] ?? ''), '/student/affiliation'), 'Referral notification should resolve to student/affiliation.', $errors);
assert_true(empty($studentById[21]['has_open_action']), 'Notifications-center links should not expose a fake open action.', $errors);

$notificationsUrl = site_url('notifications');
$legacyNotificationsUrl = abs_url('notifications?scope=business');
assert_true(
    !\App\Core\NotificationRouting::hasMeaningfulTarget($legacyNotificationsUrl, $notificationsUrl),
    'Notifications URLs must not be considered meaningful open targets.',
    $errors
);

$feed = new \App\Services\Api\NotificationsFeedService(new \App\Models\ApiNotificationsFeedModel($pdo));
$adminFeed = $feed->handle([
    'id' => 1,
    'roles_json' => '["admin"]',
], [
    'after_id' => 0,
    'limit' => 20,
]);
$adminItems = is_array($adminFeed['payload']['items'] ?? null) ? $adminFeed['payload']['items'] : [];
assert_true(count($adminItems) === 1, 'Admin live feed must exclude announcement rows.', $errors);
assert_true(str_contains((string) ($adminItems[0]['url'] ?? ''), '/admin/support'), 'Admin live feed should route support notifications to admin/support.', $errors);

$studentFeed = $feed->handle([
    'id' => 5,
    'roles_json' => '["student"]',
], [
    'after_id' => 0,
    'limit' => 20,
]);
$studentItems = is_array($studentFeed['payload']['items'] ?? null) ? $studentFeed['payload']['items'] : [];
$studentFeedById = [];
foreach ($studentItems as $item) {
    $studentFeedById[(int) ($item['id'] ?? 0)] = $item;
}

assert_true(str_contains((string) ($studentFeedById[20]['url'] ?? ''), '/student/affiliation'), 'Student live feed should route referral notifications to student/affiliation.', $errors);
assert_true((string) ($studentFeedById[21]['url'] ?? '') === '', 'Student live feed should not expose a fake open button for notifications-center links.', $errors);

$dualRoles = \App\Core\NotificationRouting::normalizeRoles('["student","teacher"]');
$dualStudentReferral = \App\Core\NotificationRouting::resolveActionUrl(
    ['title' => 'Commission de parrainage disponible', 'body' => 'Votre filleul a validé son achat.'],
    $dualRoles,
    static fn(string $path): string => '/ecolenumerique/' . ltrim($path, '/'),
    'student'
);
$dualTeacherReferral = \App\Core\NotificationRouting::resolveActionUrl(
    ['title' => 'Commission de parrainage disponible', 'body' => 'Votre filleul a validé son achat.'],
    $dualRoles,
    static fn(string $path): string => '/ecolenumerique/' . ltrim($path, '/'),
    'teacher'
);
$dualStudentMeeting = \App\Core\NotificationRouting::resolveActionUrl(
    ['title' => 'Rendez-vous confirmé', 'body' => 'Votre créneau de cours est confirmé.'],
    $dualRoles,
    static fn(string $path): string => '/ecolenumerique/' . ltrim($path, '/'),
    'student'
);
assert_true(str_contains($dualStudentReferral, '/student/affiliation'), 'Dual-role student space should route referral notifications to student affiliation.', $errors);
assert_true(str_contains($dualTeacherReferral, '/teacher/affiliation'), 'Dual-role teacher space should route referral notifications to teacher affiliation.', $errors);
assert_true(str_contains($dualStudentMeeting, '/student/meetings'), 'Dual-role student space should route meeting notifications to student meetings.', $errors);

$staleClientFeed = $feed->handle([
    'id' => 5,
    'roles_json' => '["student"]',
], [
    'after_id' => 999,
    'limit' => 20,
]);
assert_true((int) ($staleClientFeed['payload']['latest_db_id'] ?? -1) === 21, 'Live feed should expose latest_db_id so clients can recover from stale local lastId.', $errors);

$_SESSION['user_id'] = 999;
$_SESSION['user_roles'] = ['student'];
ob_start();
\App\Core\View::render('web/public_error', [
    'errorTitle' => 'Test',
    'errorMessage' => 'Test',
    'errorActions' => [],
    'siteLogoUrl' => '',
    'footerText' => '',
    'isLoggedIn' => true,
    'dashboardUrl' => site_url('student/dashboard'),
]);
$renderedErrorPage = (string) ob_get_clean();
assert_true(str_contains($renderedErrorPage, 'assets/js/push_notifications.js'), 'Views with app-global-assets off should still inject the push runtime for signed-in users.', $errors);
assert_true(str_contains($renderedErrorPage, 'id="global-push-data"'), 'Views with app-global-assets off should still expose push polling data for signed-in users.', $errors);
assert_true(!str_contains($renderedErrorPage, 'id="global-popup-data"'), 'Notification popup payload should not be injected.', $errors);
assert_true(!str_contains($renderedErrorPage, 'assets/js/global_popups.js'), 'Notification popup runtime should not be injected by the push runtime.', $errors);

if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, "[FAIL] {$error}\n");
    }
    exit(1);
}

fwrite(STDOUT, "notifications_routing_functional: OK\n");
