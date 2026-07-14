<?php
declare(strict_types=1);

use App\Models\ApiSupportModel;
use App\Services\Api\SupportService;
use App\Services\Support\SupportGuestContextService;

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only\n";
    exit(1);
}

$rootDir = dirname(__DIR__, 2);
chdir($rootDir);

require_once $rootDir . '/app/bootstrap.php';

/**
 * @param array<int,string> $errors
 */
function assert_true(bool $condition, string $message, array &$errors): void
{
    if (!$condition) {
        $errors[] = $message;
    }
}

$errors = [];
$_SESSION = [];

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->sqliteCreateFunction('NOW', static fn (): string => '2026-07-14 12:00:00', 0);

$pdo->exec('CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    email TEXT NOT NULL,
    password_hash TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT "active",
    email_verified_at TEXT NULL,
    roles_json TEXT NOT NULL
)');
$pdo->exec('CREATE TABLE support_conversations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    created_by INTEGER NULL,
    viewer_key TEXT NULL,
    guest_name TEXT NULL,
    guest_email TEXT NULL,
    subject TEXT NOT NULL,
    status TEXT NOT NULL,
    priority TEXT NOT NULL DEFAULT "normal",
    category TEXT NOT NULL DEFAULT "other",
    assigned_to INTEGER NULL,
    last_message_at TEXT NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
)');
$pdo->exec('CREATE TABLE support_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    conversation_id INTEGER NOT NULL,
    sender_id INTEGER NULL,
    body TEXT NOT NULL,
    created_at TEXT NOT NULL
)');
$pdo->exec('CREATE TABLE support_message_attachments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    message_id INTEGER NOT NULL,
    file_path TEXT NOT NULL,
    original_name TEXT NOT NULL,
    mime TEXT NOT NULL,
    size INTEGER NOT NULL,
    created_at TEXT NOT NULL
)');
$pdo->exec('CREATE TABLE notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    body TEXT NOT NULL,
    url TEXT NULL,
    created_at TEXT NOT NULL
)');

$pdo->exec("INSERT INTO users (first_name, last_name, email, password_hash, status, email_verified_at, roles_json) VALUES
    ('Amina', 'Support', 'support@example.test', 'hash', 'active', '2026-07-14 11:00:00', '[\"support\"]'),
    ('Admin', 'Console', 'admin@example.test', 'hash', 'active', '2026-07-14 11:00:00', '[\"admin\"]')");

$guestContext = new SupportGuestContextService();
$guestState = $guestContext->ensure();

$service = new SupportService(new ApiSupportModel($pdo));

$createResult = $service->handleConversations(
    null,
    $guestState,
    'POST',
    [],
    [
        'guest_name' => 'Awa Ndiaye',
        'guest_email' => 'awa@example.test',
        'subject' => '',
        'body' => 'Bonjour, je ne peux plus ouvrir mon cours.',
        'category' => 'technical',
    ],
    []
);

assert_true((int) ($createResult['status'] ?? 0) === 200, 'Guest conversation creation should succeed.', $errors);
$conversationId = (int) (($createResult['payload']['conversation_id'] ?? 0));
$messageId = (int) (($createResult['payload']['message_id'] ?? 0));
assert_true($conversationId > 0, 'Guest conversation should return a valid conversation id.', $errors);
assert_true($messageId > 0, 'Guest conversation should return a valid message id.', $errors);
assert_true($guestContext->activeConversationId() === $conversationId, 'Guest session should remember the active conversation.', $errors);

$conversationRow = $pdo->query('SELECT created_by, viewer_key, guest_name, guest_email, subject FROM support_conversations WHERE id = ' . $conversationId)->fetch(PDO::FETCH_ASSOC);
assert_true(is_array($conversationRow), 'Guest conversation should be stored in the database.', $errors);
if (is_array($conversationRow)) {
    assert_true(($conversationRow['created_by'] ?? null) === null, 'Guest conversation should not link to a user id.', $errors);
    assert_true((string) ($conversationRow['guest_name'] ?? '') === 'Awa Ndiaye', 'Guest name should be persisted.', $errors);
    assert_true((string) ($conversationRow['guest_email'] ?? '') === 'awa@example.test', 'Guest email should be persisted.', $errors);
    assert_true(trim((string) ($conversationRow['viewer_key'] ?? '')) !== '', 'Guest viewer key should be stored.', $errors);
}

$messageRow = $pdo->query('SELECT sender_id, body FROM support_messages WHERE id = ' . $messageId)->fetch(PDO::FETCH_ASSOC);
assert_true(is_array($messageRow), 'Guest message should be stored in the database.', $errors);
if (is_array($messageRow)) {
    assert_true(($messageRow['sender_id'] ?? null) === null, 'Guest message should not link to a sender user id.', $errors);
    assert_true((string) ($messageRow['body'] ?? '') === 'Bonjour, je ne peux plus ouvrir mon cours.', 'Guest message body should be stored.', $errors);
}

$listResult = $service->handleConversations(null, $guestState, 'GET', [], [], []);
assert_true((int) ($listResult['status'] ?? 0) === 200, 'Guest conversation listing should succeed.', $errors);
$conversations = (array) ($listResult['payload']['conversations'] ?? []);
assert_true(count($conversations) === 1, 'Guest should see the conversation that was created in the same session.', $errors);
if ($conversations !== []) {
    $first = $conversations[0];
    assert_true((string) ($first['guest_name'] ?? '') === 'Awa Ndiaye', 'Guest conversation payload should expose the guest name.', $errors);
    assert_true((string) ($first['created_by_name'] ?? '') === 'Awa Ndiaye', 'Guest conversation payload should fall back to the guest name.', $errors);
}

$messagesResult = $service->handleMessages(
    null,
    $guestState,
    'GET',
    ['conversation_id' => $conversationId],
    [],
    []
);
assert_true((int) ($messagesResult['status'] ?? 0) === 200, 'Guest message listing should succeed.', $errors);
$messages = (array) ($messagesResult['payload']['messages'] ?? []);
assert_true(count($messages) === 1, 'Guest message listing should return the created message.', $errors);
if ($messages !== []) {
    $firstMessage = $messages[0];
    assert_true((bool) ($firstMessage['is_self'] ?? false) === true, 'Guest message should be marked as self.', $errors);
    assert_true((string) ($firstMessage['sender_name'] ?? '') === 'Awa Ndiaye', 'Guest sender name should be preserved.', $errors);
}

if ($errors !== []) {
    echo "Support guest functional FAILED\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "Support guest functional OK\n";
exit(0);
