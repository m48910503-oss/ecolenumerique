<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only\n";
    exit(1);
}

$rootDir = dirname(__DIR__, 2);
chdir($rootDir);

require_once $rootDir . '/app/bootstrap.php';

if (!defined('APP_DRIVE_SERVICE_ACCOUNT_JSON')) {
    define('APP_DRIVE_SERVICE_ACCOUNT_JSON', '');
}
if (!defined('APP_DRIVE_ROOT_FOLDER_ID')) {
    define('APP_DRIVE_ROOT_FOLDER_ID', '');
}
if (!defined('APP_DRIVE_PUBLIC_LINK')) {
    define('APP_DRIVE_PUBLIC_LINK', true);
}
if (!defined('APP_DRIVE_MAX_UPLOAD_MB')) {
    define('APP_DRIVE_MAX_UPLOAD_MB', 25);
}
if (!defined('APP_DRIVE_OAUTH_CLIENT_ID')) {
    define('APP_DRIVE_OAUTH_CLIENT_ID', '');
}
if (!defined('APP_DRIVE_OAUTH_CLIENT_SECRET')) {
    define('APP_DRIVE_OAUTH_CLIENT_SECRET', '');
}
if (!defined('APP_DRIVE_OAUTH_TOKEN_PATH')) {
    define('APP_DRIVE_OAUTH_TOKEN_PATH', '');
}

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
    $pdo->sqliteCreateFunction('DATABASE', static fn (): string => 'main', 0);
    $pdo->exec('CREATE TABLE INFORMATION_SCHEMA.TABLES (TABLE_SCHEMA TEXT, TABLE_NAME TEXT)');
    $pdo->exec('CREATE TABLE INFORMATION_SCHEMA.COLUMNS (TABLE_SCHEMA TEXT, TABLE_NAME TEXT, COLUMN_NAME TEXT)');

    return $pdo;
}

$errors = [];
$pdo = build_sqlite_pdo();

$pdo->exec('CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    first_name TEXT,
    last_name TEXT,
    country TEXT,
    city TEXT,
    avatar_url TEXT,
    roles_json TEXT
)');
register_sqlite_schema($pdo, 'users', ['id', 'first_name', 'last_name', 'country', 'city', 'avatar_url', 'roles_json']);

$pdo->exec('CREATE TABLE teacher_applications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    title TEXT,
    bio TEXT,
    cv_path TEXT,
    status TEXT
)');
register_sqlite_schema($pdo, 'teacher_applications', ['id', 'user_id', 'title', 'bio', 'cv_path', 'status']);

$pdo->exec("INSERT INTO users (id, first_name, last_name, country, city, avatar_url, roles_json) VALUES
    (7, 'Mina', 'Kone', 'BJ', 'Cotonou', 'avatar.png', '[\"student\",\"teacher_candidate\"]'),
    (8, 'Corp', 'Member', 'BJ', 'Cotonou', 'avatar.png', '[\"student\",\"company_member\"]')
");
$pdo->exec("INSERT INTO teacher_applications (user_id, title, bio, cv_path, status) VALUES (7, 'Formateur', 'Bio', NULL, 'pending')");

$service = new \App\Services\Teacher\ApplyService(
    $pdo,
    new \App\Models\TeacherApplicationModel($pdo),
    new \App\Models\UserModel($pdo),
    new \App\Models\NotificationModel($pdo)
);

$user = [
    'id' => 7,
    'first_name' => 'Mina',
    'last_name' => 'Kone',
    'country' => 'BJ',
    'city' => 'Cotonou',
    'avatar_url' => 'avatar.png',
];

$state = $service->buildInitialState($user, ['student', 'teacher_candidate']);
assert_true(!empty($state['pendingApplication']), 'Pending teacher application should be exposed in the view state.', $errors);
assert_true(!empty($state['formLocked']), 'Pending teacher application should lock the form.', $errors);
assert_true(empty($state['alreadyTeacher']), 'Pending teacher candidate should not be treated as an approved teacher.', $errors);

$submitState = $service->submit($state, $user, []);
$submitErrors = is_array($submitState['errors'] ?? null) ? $submitState['errors'] : [];
assert_true(in_array("Votre demande est déjà en cours d’examen.", $submitErrors, true), 'Submit should short-circuit with a clear pending application message.', $errors);

$companyUser = [
    'id' => 8,
    'first_name' => 'Corp',
    'last_name' => 'Member',
    'country' => 'BJ',
    'city' => 'Cotonou',
    'avatar_url' => 'avatar.png',
];
$companyState = $service->buildInitialState($companyUser, ['student', 'company_member']);
assert_true(!empty($companyState['isCompanyAccount']), 'Company member should be exposed in the view state.', $errors);
assert_true(!empty($companyState['formLocked']), 'Company member should not be allowed to open the teacher application form.', $errors);

$companySubmitState = $service->submit($companyState, $companyUser, []);
$companySubmitErrors = is_array($companySubmitState['errors'] ?? null) ? $companySubmitState['errors'] : [];
assert_true(
    in_array('Les comptes entreprise ne peuvent pas devenir enseignants. Utilisez un compte personnel distinct pour déposer une candidature.', $companySubmitErrors, true),
    'Company member submit should be rejected before upload validation.',
    $errors
);

if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, "[FAIL] {$error}\n");
    }
    exit(1);
}

fwrite(STDOUT, "teacher_apply_pending_state_functional: OK\n");
