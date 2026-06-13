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
function invoke_private(object $object, string $method, array $args = []): mixed
{
    $ref = new ReflectionClass($object);
    $m = $ref->getMethod($method);
    $m->setAccessible(true);
    return $m->invokeArgs($object, $args);
}

$errors = [];
$controller = new \App\Controllers\Teacher\PromotionsController();

assert_true(
    (int) invoke_private($controller, 'resolveWizardStep', ['review']) === 3,
    'Wizard review step should map to step 3.',
    $errors
);

$wizardUrl = (string) invoke_private($controller, 'wizardStepUrl', ['promotion', 2]);
assert_true(str_contains($wizardUrl, 'form=promotion'), 'Wizard URL should keep the form mode.', $errors);
assert_true(str_contains($wizardUrl, 'step=2'), 'Wizard URL should encode the requested step.', $errors);

$baseView = [
    'courses' => [
        ['id' => 12, 'title' => 'Cours A', 'price_xof' => 10000, 'status' => 'approved'],
    ],
];

$stepOne = invoke_private($controller, 'validateWizardStepOne', [[
    'course_id' => '12',
    'promo_type' => 'percent',
    'promo_percent' => '15',
], [], $baseView]);
assert_true(($stepOne['errors'] ?? []) === [], 'Valid step 1 data should pass validation.', $errors);
assert_true((string) ($stepOne['draft']['promo_type'] ?? '') === 'percent', 'Step 1 should keep the selected promo type.', $errors);

$stepOneInvalid = invoke_private($controller, 'validateWizardStepOne', [[
    'course_id' => '12',
    'promo_type' => 'bogus',
    'promo_percent' => '15',
], [], $baseView]);
assert_true(($stepOneInvalid['errors'] ?? []) !== [], 'Invalid promo type should be rejected at step 1.', $errors);

$stepTwo = invoke_private($controller, 'validateWizardStepTwo', [[
    'promo_schedule_mode' => 'duration',
    'promo_duration_days' => '7',
    'promo_target_type' => 'public',
    'coupon_code_mode' => 'manual',
    'coupon_code' => 'PROMO-2026',
], [
    'course_id' => '12',
    'promo_type' => 'percent',
    'promo_percent' => '15',
], [
    'courses' => $baseView['courses'],
    'canTargetPromos' => true,
    'couponCodeSupported' => true,
    'promoTargetCandidates' => [],
    'promoCompanyCandidates' => [],
], 'coupon']);
assert_true(($stepTwo['errors'] ?? []) === [], 'Valid step 2 data should pass validation.', $errors);
assert_true((string) ($stepTwo['draft']['coupon_code_mode'] ?? '') === 'manual', 'Step 2 should preserve the coupon code mode.', $errors);
assert_true((string) ($stepTwo['draft']['coupon_code'] ?? '') === 'PROMO-2026', 'Step 2 should keep the manual code.', $errors);

$stepTwoInvalid = invoke_private($controller, 'validateWizardStepTwo', [[
    'promo_schedule_mode' => 'custom',
    'promo_start_at' => '2026-06-20',
    'promo_end_at' => '2026-06-19',
    'promo_target_type' => 'company',
    'promo_company_id' => '99',
    'promo_company_seats' => '0',
], [
    'course_id' => '12',
    'promo_type' => 'percent',
    'promo_percent' => '15',
], [
    'courses' => $baseView['courses'],
    'canTargetPromos' => false,
    'couponCodeSupported' => true,
    'promoTargetCandidates' => [],
    'promoCompanyCandidates' => [],
], 'promotion']);
assert_true(($stepTwoInvalid['errors'] ?? []) !== [], 'Invalid step 2 data should be rejected.', $errors);

if ($errors !== []) {
    echo "Teacher promotions wizard FAILED\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "Teacher promotions wizard OK\n";
exit(0);
