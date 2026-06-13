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

final class RatingController extends ApiController
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $user = $this->currentUser();
        if (!$user) {
            $this->json(401, ['error' => 'non_authentifie']);
            return;
        }

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
        $result = $service->handleRating($user, $method, $_POST);
        $this->json((int) $result['status'], (array) $result['payload']);
    }
}
