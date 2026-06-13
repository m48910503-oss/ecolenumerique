<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Models\SharedCompanySchemaModel;
use App\Models\SharedPaymentModel;
use App\Models\SharedPromotionModel;
use App\Models\SharedReferralModel;
use App\Models\ReferralWalletModel;
use App\Models\WebPaymentReturnModel;
use App\Services\Shared\CompanySchemaService;
use App\Services\Shared\FedapayService;
use App\Services\Shared\PaymentOpsService;
use App\Services\Shared\PromotionsService;
use App\Services\Shared\ReferralService;
use App\Services\Web\PaymentReturnService;

final class PaymentReturnController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $transactionId = isset($_GET['id']) ? (int) $_GET['id'] : (int) ($_GET['transaction_id'] ?? 0);

        $promotions = new PromotionsService(
            new SharedPromotionModel($pdo),
            new CompanySchemaService(new SharedCompanySchemaModel($pdo))
        );
        $service = new PaymentReturnService(new WebPaymentReturnModel(
            new PaymentOpsService(
                new SharedPaymentModel($pdo),
                new ReferralService(new SharedReferralModel($pdo)),
                $promotions,
                new ReferralWalletModel($pdo)
            ),
            new FedapayService()
        ));
        $result = $service->processTransaction($transactionId);

        $success = (string) ($result['success'] ?? '');
        $errors = is_array($result['errors'] ?? null) ? $result['errors'] : [];
        $completed = !empty($result['completed']);

        if ($success !== '') {
            $this->flashSet('success', $success);
        }
        if ($completed) {
            $this->flashSet('checkout_completed', 1);
        }
        if ($errors !== []) {
            $this->flashSet('errors', $errors);
        }

        header('Location: ' . $this->siteUrl('checkout'));
        exit;
    }
}
