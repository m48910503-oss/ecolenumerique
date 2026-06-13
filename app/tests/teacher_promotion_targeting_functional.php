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

/**
 * @param array<int,mixed> $args
 */
function invoke_private(object $service, string $method, array $args = []): mixed
{
    $ref = new ReflectionClass($service);
    $m = $ref->getMethod($method);
    $m->setAccessible(true);
    return $m->invokeArgs($service, $args);
}

$errors = [];
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$promotionsService = new \App\Services\Teacher\PromotionsService(
    new \App\Models\TeacherPromotionsModel($pdo),
    new \App\Services\Shared\PromotionsService(
        new \App\Models\SharedPromotionModel($pdo),
        new \App\Services\Shared\CompanySchemaService(new \App\Models\SharedCompanySchemaModel($pdo))
    )
);

$targetEmailMap = [
    'user1@mail.com' => 12,
    'user2@mail.com' => 34,
];
$companyMap = [
    7 => ['id' => 7, 'name' => 'Acme'],
];

$individual = invoke_private($promotionsService, 'resolveTargets', [
    true,
    [
        'promo_target_type' => 'individual',
        'promo_targets' => 'User1@mail.com, user2@mail.com ; user1@mail.com',
    ],
    $targetEmailMap,
    $companyMap,
    null,
    true,
]);
assert_true(($individual['errors'] ?? []) === [], 'Promotions targeting: valid individual targets should not fail.', $errors);
assert_true(($individual['isTargeted'] ?? false) === true, 'Promotions targeting: individual mode should be active.', $errors);
assert_true(($individual['isCompanyTargeted'] ?? true) === false, 'Promotions targeting: individual mode should not activate company targeting.', $errors);
assert_true(($individual['targetUserIds'] ?? []) === [12, 34], 'Promotions targeting: emails should resolve uniquely to user ids.', $errors);

$individualInvalid = invoke_private($promotionsService, 'resolveTargets', [
    true,
    [
        'promo_target_type' => 'individual',
        'promo_targets' => 'bad-email, user1@mail.com',
    ],
    $targetEmailMap,
    $companyMap,
    null,
    true,
]);
assert_true(
    in_array('Emails invalides: bad-email', $individualInvalid['errors'] ?? [], true),
    'Promotions targeting: invalid emails should be reported.',
    $errors
);

$individualMissing = invoke_private($promotionsService, 'resolveTargets', [
    true,
    [
        'promo_target_type' => 'individual',
        'promo_targets' => 'unknown@mail.com',
    ],
    $targetEmailMap,
    $companyMap,
    null,
    true,
]);
assert_true(
    in_array('Utilisateurs non eligibles: unknown@mail.com', $individualMissing['errors'] ?? [], true),
    'Promotions targeting: unknown users should be reported.',
    $errors
);

$company = invoke_private($promotionsService, 'resolveTargets', [
    true,
    [
        'promo_target_type' => 'company',
        'promo_company_id' => '7',
        'promo_company_seats' => '25',
    ],
    $targetEmailMap,
    $companyMap,
    null,
    true,
]);
assert_true(($company['errors'] ?? []) === [], 'Promotions targeting: valid company target should not fail.', $errors);
assert_true(($company['isCompanyTargeted'] ?? false) === true, 'Promotions targeting: company mode should be active.', $errors);
assert_true((int) ($company['targetCompanyId'] ?? 0) === 7, 'Promotions targeting: company id should be preserved.', $errors);
assert_true((int) ($company['targetCompanySeats'] ?? 0) === 25, 'Promotions targeting: company seats should be preserved.', $errors);

$companyHistorical = invoke_private($promotionsService, 'resolveTargets', [
    true,
    [
        'promo_target_type' => 'company',
        'promo_company_id' => '9',
        'promo_company_seats' => '3',
    ],
    $targetEmailMap,
    $companyMap,
    9,
    true,
]);
assert_true(($companyHistorical['errors'] ?? []) === [], 'Promotions targeting: existing historical company id should stay editable.', $errors);
assert_true(($companyHistorical['isCompanyTargeted'] ?? false) === true, 'Promotions targeting: historical company id should remain valid.', $errors);

$companyInvalidSeats = invoke_private($promotionsService, 'resolveTargets', [
    true,
    [
        'promo_target_type' => 'company',
        'promo_company_id' => '7',
        'promo_company_seats' => '0',
    ],
    $targetEmailMap,
    $companyMap,
    null,
    true,
]);
assert_true(
    in_array('Nombre de places invalide.', $companyInvalidSeats['errors'] ?? [], true),
    'Promotions targeting: invalid seat count should be rejected.',
    $errors
);

$couponCompanyBlocked = invoke_private($promotionsService, 'resolveTargets', [
    true,
    [
        'promo_target_type' => 'company',
        'promo_company_id' => '7',
        'promo_company_seats' => '10',
    ],
    $targetEmailMap,
    $companyMap,
    null,
    false,
]);
assert_true(
    in_array('Les coupons entreprise ne sont pas disponibles pour le moment.', $couponCompanyBlocked['errors'] ?? [], true),
    'Promotions targeting: coupon company targeting should stay blocked.',
    $errors
);
assert_true(($couponCompanyBlocked['isCompanyTargeted'] ?? true) === false, 'Promotions targeting: blocked company coupon must not activate targeting.', $errors);

$disabledTargeting = invoke_private($promotionsService, 'resolveTargets', [
    false,
    [
        'promo_target_type' => 'individual',
        'promo_targets' => 'user1@mail.com',
    ],
    $targetEmailMap,
    $companyMap,
    null,
    true,
]);
assert_true(($disabledTargeting['errors'] ?? []) === [], 'Promotions targeting: disabled targeting should silently fallback to public.', $errors);
assert_true(($disabledTargeting['isTargeted'] ?? true) === false, 'Promotions targeting: disabled targeting should not activate individual mode.', $errors);
assert_true(($disabledTargeting['isCompanyTargeted'] ?? true) === false, 'Promotions targeting: disabled targeting should not activate company mode.', $errors);

if ($errors !== []) {
    echo "Teacher promotion targeting functional FAILED\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "Teacher promotion targeting functional OK\n";
exit(0);
