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
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (method_exists($pdo, 'sqliteCreateFunction')) {
    $pdo->sqliteCreateFunction('NOW', static fn (): string => '2026-04-17 12:00:00');
}

$pdo->exec('CREATE TABLE course_promotions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    course_id INTEGER NOT NULL,
    teacher_id INTEGER NOT NULL,
    promo_mode TEXT NOT NULL,
    promo_type TEXT NOT NULL,
    promo_value REAL NOT NULL,
    coupon_code TEXT NULL,
    promo_start_at TEXT NOT NULL,
    promo_end_at TEXT NOT NULL,
    deleted_at TEXT NULL
)');
$pdo->exec('CREATE TABLE course_promotion_targets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    promotion_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL
)');
$pdo->exec('CREATE TABLE course_promotion_company_targets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    promotion_id INTEGER NOT NULL,
    company_id INTEGER NOT NULL,
    seat_limit INTEGER NOT NULL
)');

$model = new \App\Models\TeacherPromotionsModel($pdo);

$pdo->exec("INSERT INTO course_promotions
    (id, course_id, teacher_id, promo_mode, promo_type, promo_value, coupon_code, promo_start_at, promo_end_at, deleted_at)
    VALUES
    (1, 9, 1, 'promotion', 'amount', 1200, NULL, '2026-04-16 12:00:00', '2026-04-18 12:00:00', NULL),
    (2, 9, 1, 'coupon', 'percent', 15, 'CPN-9-AAAAAA', '2026-04-10 12:00:00', '2026-04-12 12:00:00', '2026-04-12 12:00:00'),
    (3, 9, 1, 'promotion', 'percent', 25, NULL, '2026-04-16 12:00:00', '2026-04-18 12:00:00', NULL),
    (4, 9, 1, 'coupon', 'amount', 1000, 'CPN-9-BBBBBB', '2026-04-16 12:00:00', '2026-04-18 12:00:00', NULL),
    (5, 9, 1, 'promotion', 'percent', 20, NULL, '2026-04-16 12:00:00', '2026-04-18 12:00:00', NULL),
    (6, 9, 1, 'coupon', 'percent', 10, 'CPN-9-CCCCCC', '2026-04-16 12:00:00', '2026-04-18 12:00:00', NULL)
");
$pdo->exec("INSERT INTO course_promotion_targets (promotion_id, user_id) VALUES (3, 77), (6, 88)");
$pdo->exec("INSERT INTO course_promotion_company_targets (promotion_id, company_id, seat_limit) VALUES (5, 501, 20)");

assert_true(
    $model->hasActivePublicConflict(9, true, true, null, 'promotion') === true,
    'Promotion conflict: an active public promotion should still be detected.',
    $errors
);
assert_true(
    $model->hasActivePublicConflict(9, true, true, null, 'coupon') === true,
    'Promotion conflict: an active public coupon should still be detected.',
    $errors
);

$pdo->exec('DELETE FROM course_promotions WHERE id = 4');

assert_true(
    $model->hasActivePublicConflict(9, true, true, null, 'coupon') === false,
    'Promotion conflict: an active public promotion must not block coupon creation.',
    $errors
);

assert_true(
    $model->hasActiveIndividualConflict(9, [77], null, 'promotion') === true,
    'Promotion conflict: an active targeted promotion should still be detected for the same mode.',
    $errors
);
assert_true(
    $model->hasActiveIndividualConflict(9, [77], null, 'coupon') === false,
    'Promotion conflict: an active targeted promotion must not block a coupon target.',
    $errors
);
assert_true(
    $model->hasActiveCompanyConflict(9, 501, null, 'promotion') === true,
    'Promotion conflict: an active company promotion should still be detected for the same mode.',
    $errors
);
assert_true(
    $model->hasActiveCompanyConflict(9, 501, null, 'coupon') === false,
    'Promotion conflict: an active company promotion must not block a coupon.',
    $errors
);

if ($errors !== []) {
    echo "Teacher promotion conflicts functional FAILED\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "Teacher promotion conflicts functional OK\n";
exit(0);
