<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\ApiCatalogModel;
use App\Services\Api\CourseFavoritesService;

final class CourseFavoritesController extends ApiController
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $user = $this->currentUser();
        if (!$user) {
            $this->json(401, ['ok' => false, 'error' => 'auth']);
            return;
        }

        $method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $payload = [];
        if ($method === 'POST') {
            $payload = $this->hydrateCsrfFromHeaderOrBody($_POST);
            $this->requireCsrf();
        } else {
            $payload = $_GET;
        }

        $service = new CourseFavoritesService(new ApiCatalogModel($pdo));
        $result = $service->handle($user, $method, $payload);
        $this->json((int) $result['status'], (array) $result['payload']);
    }
}

