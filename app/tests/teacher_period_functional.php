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

$coursesService = new \App\Services\Teacher\CoursesService(
    new \App\Models\TeacherCoursesModel($pdo)
);
$withdrawalsService = new \App\Services\Teacher\WithdrawalsService(
    new \App\Models\TeacherWithdrawalsModel($pdo),
    new \App\Models\TeacherReferralsModel($pdo),
    new \App\Models\ReferralWalletModel($pdo),
    new \App\Services\Shared\PlatformSettingsService(new \App\Models\SharedPlatformSettingsModel($pdo)),
    new \App\Services\Shared\ReferralService(new \App\Models\SharedReferralModel($pdo))
);
$promotionsService = new \App\Services\Teacher\PromotionsService(
    new \App\Models\TeacherPromotionsModel($pdo),
    new \App\Services\Shared\PromotionsService(
        new \App\Models\SharedPromotionModel($pdo),
        new \App\Services\Shared\CompanySchemaService(new \App\Models\SharedCompanySchemaModel($pdo))
    )
);

// Withdrawals: resolve period + query roundtrip
$withdrawQuery = (string) invoke_private($withdrawalsService, 'buildPeriodQuery', [[
    'key' => '90d',
    'from_date' => '2025-12-01',
    'to_date' => '2026-02-28',
]]);
parse_str(ltrim($withdrawQuery, '?'), $withdrawQueryParams);
assert_true(($withdrawQueryParams['period'] ?? '') === '90d', 'Withdrawals: query should keep period 90d.', $errors);
$withdraw90j = invoke_private($withdrawalsService, 'resolvePeriodState', [['period' => '90j']]);
assert_true(($withdraw90j['key'] ?? '') === '90', 'Withdrawals: period 90j should map to 90.', $errors);

// Courses: normalize + list query should retain custom period
$coursesState = invoke_private($coursesService, 'normalizeListState', [[
    'q' => 'test',
    'period' => 'custom',
    'from' => '2026-01-10',
    'to' => '2026-01-20',
]]);
assert_true(($coursesState['period'] ?? '') === 'custom', 'Courses: custom period should be kept.', $errors);
assert_true(($coursesState['from'] ?? '') === '2026-01-10', 'Courses: custom from should be kept.', $errors);
assert_true(($coursesState['to'] ?? '') === '2026-01-20', 'Courses: custom to should be kept.', $errors);
$coursesListQuery = (string) invoke_private($coursesService, 'buildListQuery', [$coursesState]);
parse_str($coursesListQuery, $coursesQueryParams);
assert_true(($coursesQueryParams['period'] ?? '') === 'custom', 'Courses: list query should include period=custom.', $errors);
assert_true(($coursesQueryParams['from'] ?? '') === '2026-01-10', 'Courses: list query should include from.', $errors);
assert_true(($coursesQueryParams['to'] ?? '') === '2026-01-20', 'Courses: list query should include to.', $errors);
$coursesState30j = invoke_private($coursesService, 'normalizeListState', [['period' => '30j']]);
assert_true(($coursesState30j['period'] ?? '') === '30d', 'Courses: period 30j should map to 30d.', $errors);

// Promotions: listing query roundtrip
$couponQuery = (string) invoke_private($promotionsService, 'buildListingQuery', [[
    'q' => 'promo',
    'courseId' => 12,
    'state' => 'active',
    'offer' => 'coupon',
    'perPage' => 20,
    'promotionPage' => 3,
    'couponPage' => 4,
]]);
parse_str(ltrim($couponQuery, '?'), $couponQueryParams);
assert_true(($couponQueryParams['q'] ?? '') === 'promo', 'Promotions: query should keep search text.', $errors);
assert_true((int) ($couponQueryParams['course_id'] ?? 0) === 12, 'Promotions: query should keep course_id.', $errors);
assert_true(($couponQueryParams['state'] ?? '') === 'active', 'Promotions: query should keep state.', $errors);
assert_true(($couponQueryParams['offer'] ?? '') === 'coupon', 'Promotions: query should keep offer.', $errors);
assert_true((int) ($couponQueryParams['size'] ?? 0) === 20, 'Promotions: query should keep page size.', $errors);
assert_true((int) ($couponQueryParams['pp'] ?? 0) === 3, 'Promotions: query should keep promotion page.', $errors);
assert_true((int) ($couponQueryParams['cp'] ?? 0) === 4, 'Promotions: query should keep coupon page.', $errors);

// Promotions: schedule resolution should support duration and custom date ranges.
$promoCreateDuration = invoke_private($promotionsService, 'resolvePricingForCreate', [[
    'promo_type' => 'percent',
    'promo_percent' => '12.5',
    'promo_schedule_mode' => 'duration',
    'promo_duration_days' => '7',
], ['price_xof' => 1000], []]);
assert_true(($promoCreateDuration['errors'] ?? []) === [], 'Promotions: duration-based create should be valid.', $errors);
assert_true((string) ($promoCreateDuration['promoStartAt'] ?? '') !== '', 'Promotions: duration-based create should set a start date.', $errors);
assert_true((string) ($promoCreateDuration['promoEndAt'] ?? '') !== '', 'Promotions: duration-based create should set an end date.', $errors);
assert_true(strtotime((string) ($promoCreateDuration['promoEndAt'] ?? '')) > strtotime((string) ($promoCreateDuration['promoStartAt'] ?? '')), 'Promotions: duration-based create should end after it starts.', $errors);
assert_true(abs((strtotime((string) ($promoCreateDuration['promoEndAt'] ?? '')) - strtotime((string) ($promoCreateDuration['promoStartAt'] ?? ''))) - (7 * 86400)) <= 1, 'Promotions: duration-based create should last 7 days.', $errors);

$promoCreateCustom = invoke_private($promotionsService, 'resolvePricingForCreate', [[
    'promo_type' => 'amount',
    'promo_amount' => '250',
    'promo_schedule_mode' => 'custom',
    'promo_start_at' => '2026-05-20',
    'promo_end_at' => '2026-05-25',
], ['price_xof' => 1000], []]);
assert_true(($promoCreateCustom['errors'] ?? []) === [], 'Promotions: custom create should be valid.', $errors);
assert_true((string) ($promoCreateCustom['promoStartAt'] ?? '') === '2026-05-20 00:00:00', 'Promotions: custom create should keep the start date.', $errors);
assert_true((string) ($promoCreateCustom['promoEndAt'] ?? '') === '2026-05-25 23:59:59', 'Promotions: custom create should keep the end date.', $errors);

$promoUpdateDuration = invoke_private($promotionsService, 'resolvePricingForUpdate', [[
    'promo_type' => 'percent',
    'promo_percent' => '15',
    'promo_schedule_mode' => 'duration',
    'promo_duration_days' => '3',
], [
    'price_xof' => 1000,
    'promo_start_at' => '2026-05-01 00:00:00',
    'promo_end_at' => '2026-05-07 23:59:59',
], []]);
assert_true(($promoUpdateDuration['errors'] ?? []) === [], 'Promotions: duration-based update should be valid.', $errors);
assert_true(abs((strtotime((string) ($promoUpdateDuration['promoEndAt'] ?? '')) - strtotime((string) ($promoUpdateDuration['promoStartAt'] ?? ''))) - (3 * 86400)) <= 1, 'Promotions: duration-based update should last 3 days.', $errors);

$promoUpdateCustom = invoke_private($promotionsService, 'resolvePricingForUpdate', [[
    'promo_type' => 'amount',
    'promo_amount' => '200',
    'promo_schedule_mode' => 'custom',
    'promo_start_at' => '2026-06-01',
    'promo_end_at' => '2026-06-15',
], [
    'price_xof' => 1000,
    'promo_start_at' => '2026-05-01 00:00:00',
    'promo_end_at' => '2026-05-07 23:59:59',
], []]);
assert_true(($promoUpdateCustom['errors'] ?? []) === [], 'Promotions: custom update should be valid.', $errors);
assert_true((string) ($promoUpdateCustom['promoStartAt'] ?? '') === '2026-06-01 00:00:00', 'Promotions: custom update should keep the start date.', $errors);
assert_true((string) ($promoUpdateCustom['promoEndAt'] ?? '') === '2026-06-15 23:59:59', 'Promotions: custom update should keep the end date.', $errors);

if ($errors !== []) {
    echo "Teacher period functional FAILED\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "Teacher period functional OK\n";
exit(0);
