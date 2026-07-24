<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\ApiCatalogModel;
use App\Models\SharedCompanySchemaModel;
use App\Models\SharedPromotionModel;
use App\Services\Shared\CompanySchemaService;
use App\Services\Shared\PromotionsService;
use App\Services\Api\CatalogService;

final class CoursesController extends ApiController
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        try {
            $service = new CatalogService(
                new ApiCatalogModel($pdo),
                new PromotionsService(
                    new SharedPromotionModel($pdo),
                    new CompanySchemaService(new SharedCompanySchemaModel($pdo))
                )
            );
            $result = $service->listCourses($this->currentUser(), $_GET);
            $this->json((int) $result['status'], (array) $result['payload']);
        } catch (\Throwable $e) {
            $this->json(500, [
                'data' => [],
                'meta' => ['count' => 0],
                'error' => 'Erreur serveur',
            ]);
        }
    }
}
