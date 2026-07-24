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
$config = require $rootDir . '/includes/config.php';
require_once $rootDir . '/includes/db.php';

$limit = 100;
$ageMinutes = 5;
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $limit = (int) substr($arg, 8);
    } elseif (str_starts_with($arg, '--age-minutes=')) {
        $ageMinutes = (int) substr($arg, 14);
    }
}
$limit = max(1, min(1000, $limit));
$ageMinutes = max(0, $ageMinutes);

/** @var PDO $pdo */
$pdo = db($config['db']);
\App\Core\PdoRegistry::set($pdo);

$promotions = new \App\Services\Shared\PromotionsService(
    new \App\Models\SharedPromotionModel($pdo),
    new \App\Services\Shared\CompanySchemaService(new \App\Models\SharedCompanySchemaModel($pdo))
);
$paymentOps = new \App\Services\Shared\PaymentOpsService(
    new \App\Models\SharedPaymentModel($pdo),
    new \App\Services\Shared\ReferralService(new \App\Models\SharedReferralModel($pdo)),
    $promotions,
    new \App\Models\ReferralWalletModel($pdo)
);
$service = new \App\Services\Shared\FedapayReconciliationService(
    $paymentOps,
    new \App\Services\Shared\FedapayService()
);

$result = $service->run($limit, $ageMinutes);
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit(($result['errors_count'] ?? 0) > 0 ? 2 : 0);
