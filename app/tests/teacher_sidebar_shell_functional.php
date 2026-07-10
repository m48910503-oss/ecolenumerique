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
require_once $rootDir . '/app/View/helpers.php';

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

$user = [
    'id' => 42,
    'first_name' => 'Mina',
    'last_name' => 'Traore',
    'email' => 'mina@example.test',
    'roles_json' => '["teacher"]',
];

$teacherDashboardShell = true;
$teacherNavActive = 'dashboard';
$siteLogoUrl = '';
$studentSpaceLabel = html_entity_decode('Espace &eacute;tudiant', ENT_QUOTES, 'UTF-8');
$logoutLabel = html_entity_decode('D&eacute;connexion', ENT_QUOTES, 'UTF-8');

ob_start();
require $rootDir . '/app/Views/teacher/_sidebar.php';
$html = (string) ob_get_clean();

assert_true(str_contains($html, '>Communication</p>'), 'Teacher sidebar shell should render the Communication section.', $errors);
assert_true(str_contains($html, '>Compte</p>'), 'Teacher sidebar shell should render the Compte section.', $errors);
assert_true(str_contains($html, '>Notifications</span>'), 'Teacher sidebar shell should expose Notifications.', $errors);
assert_true(str_contains($html, '>Assistance</span>'), 'Teacher sidebar shell should expose Assistance.', $errors);
assert_true(str_contains($html, '>Mon profil</span>'), 'Teacher sidebar shell should expose Mon profil.', $errors);
assert_true(str_contains($html, '>' . $studentSpaceLabel . '</span>'), 'Teacher sidebar shell should expose Espace etudiant.', $errors);
assert_true(str_contains($html, '>Retour au site</span>'), 'Teacher sidebar shell should expose Retour au site.', $errors);
assert_true(str_contains($html, $logoutLabel), 'Teacher sidebar shell should keep the logout action in the footer.', $errors);

if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, "[FAIL] {$error}\n");
    }
    exit(1);
}

fwrite(STDOUT, "teacher_sidebar_shell_functional: OK\n");
exit(0);
