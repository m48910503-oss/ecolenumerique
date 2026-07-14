<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\ApiSupportModel;
use App\Services\Support\SupportGuestContextService;
use App\Services\Api\SupportService;

final class SupportMessagesController extends ApiController
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $user = $this->currentUser();
        $guestContext = (new SupportGuestContextService())->ensure();

        $method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $data = [];
        if ($method === 'POST') {
            $data = $this->hydrateCsrfFromHeaderOrBody($_POST);
            if ($data === []) {
                $data = $_POST;
            }
            $this->requireCsrf();
        }

        $service = new SupportService(new ApiSupportModel($pdo));
        $result = $service->handleMessages(
            $user,
            $guestContext,
            $method,
            $_GET,
            $data,
            $_FILES
        );
        $this->json((int) $result['status'], (array) $result['payload']);
    }
}
