<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\ApiFedapayWebhookModel;
use App\Models\SharedCompanySchemaModel;
use App\Models\SharedPaymentModel;
use App\Models\SharedPromotionModel;
use App\Models\SharedReferralModel;
use App\Models\ReferralWalletModel;
use App\Services\Shared\CompanySchemaService;
use App\Services\Shared\FedapayService;
use App\Services\Shared\PaymentOpsService;
use App\Services\Shared\PromotionsService;
use App\Services\Shared\ReferralService;
use App\Services\Api\FedapayWebhookService;

final class FedapayWebhookController extends ApiController
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $rawPayload = (string) file_get_contents('php://input');
        $signatureHeader = (string) ($_SERVER['HTTP_X_FEDAPAY_SIGNATURE'] ?? '');

        $promotions = new PromotionsService(
            new SharedPromotionModel($pdo),
            new CompanySchemaService(new SharedCompanySchemaModel($pdo))
        );
        $service = new FedapayWebhookService(new ApiFedapayWebhookModel(
            new PaymentOpsService(
                new SharedPaymentModel($pdo),
                new ReferralService(new SharedReferralModel($pdo)),
                $promotions,
                new ReferralWalletModel($pdo)
            ),
            new FedapayService()
        ));
        $result = $service->handle($method, $rawPayload, $signatureHeader);
        $this->json((int) $result['status'], (array) $result['payload']);
    }
}
