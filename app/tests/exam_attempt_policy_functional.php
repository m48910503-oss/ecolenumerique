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

$service = new \App\Services\Student\ExamService(
    $pdo,
    new \App\Models\ExamModel($pdo),
    new \App\Services\Shared\CertificateOpsService(new \App\Models\SharedCertificateModel($pdo))
);

$qcmCap = invoke_private($service, 'buildAttemptPolicy', ['qcm', 3, 3, false, false, false, false, 'graded']);
assert_true($qcmCap['canAttempt'] === false, 'QCM should stop new attempts when max attempts is reached.', $errors);
assert_true($qcmCap['remainingAttempts'] === 0, 'QCM remaining attempts should reach zero at cap.', $errors);
assert_true($qcmCap['showRetry'] === false, 'QCM retry CTA should disappear at cap.', $errors);

$qcmPending = invoke_private($service, 'buildAttemptPolicy', ['qcm', 1, 3, false, false, false, false, 'submitted']);
assert_true($qcmPending['canAttempt'] === false, 'QCM should block a new attempt while the previous one is still submitted.', $errors);
assert_true($qcmPending['remainingAttempts'] === 2, 'Pending QCM attempt should preserve remaining attempts count.', $errors);
assert_true($qcmPending['showRetry'] === false, 'Pending QCM attempt should hide retry CTA until correction is done.', $errors);

$qcmRetry = invoke_private($service, 'buildAttemptPolicy', ['qcm', 1, 3, false, false, false, false, 'graded']);
assert_true($qcmRetry['canAttempt'] === true, 'QCM should allow another attempt when quota remains and no correction is pending.', $errors);
assert_true($qcmRetry['showRetry'] === true, 'QCM should show retry CTA when another attempt is genuinely available.', $errors);

$privileged = invoke_private($service, 'buildAttemptPolicy', ['qcm', 3, 3, false, false, false, true, 'submitted']);
assert_true($privileged['canAttempt'] === true, 'Privileged viewers should keep access for supervision even when learner flow is blocked.', $errors);

if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, "[FAIL] {$error}\n");
    }
    exit(1);
}

fwrite(STDOUT, "exam_attempt_policy_functional: OK\n");
