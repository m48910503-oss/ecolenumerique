<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\ApiCatalogEventsModel;
use App\Services\Api\CatalogEventsService;

final class CatalogEventsController extends ApiController
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $data = $_GET;
        if (strtoupper($method) === 'POST') {
            $data = $this->hydrateCsrfFromHeaderOrBody($_POST);
            $this->requireCsrf();
        }

        try {
            $service = new CatalogEventsService(new ApiCatalogEventsModel($pdo));
            $result = $service->handle($this->currentUser(), $method, is_array($data) ? $data : []);
            $this->json((int) ($result['status'] ?? 500), (array) ($result['payload'] ?? ['ok' => false]));
        } catch (\Throwable $e) {
            $this->json(500, ['ok' => false, 'error' => 'server_error']);
        }
    }
}
