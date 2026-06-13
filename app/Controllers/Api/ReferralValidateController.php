<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\ApiCommerceModel;
use App\Models\SharedCompanySchemaModel;
use App\Models\SharedPromotionModel;
use App\Models\SharedReferralModel;
use App\Services\Shared\CompanySchemaService;
use App\Services\Shared\PromotionsService;
use App\Services\Shared\ReferralService;
use App\Services\Api\CommerceService;

final class ReferralValidateController extends ApiController
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        try {
            $user = $this->requireAuth(true);
            $method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
            if ($method === 'POST') {
                $this->requireCsrf();
            }

            $service = new CommerceService(
                new ApiCommerceModel($pdo),
                new ReferralService(new SharedReferralModel($pdo)),
                new PromotionsService(
                    new SharedPromotionModel($pdo),
                    new CompanySchemaService(new SharedCompanySchemaModel($pdo))
                )
            );
            $result = $service->handleReferralValidate($user, $method, $_POST);
            $this->json((int) $result['status'], (array) $result['payload']);
        } catch (\Throwable $e) {
            $this->json(400, ['ok' => false, 'error' => $e->getMessage()]);
        }
    }
}
