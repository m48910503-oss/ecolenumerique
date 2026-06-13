<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\SharedWebPushModel;
use App\Services\Api\PushSubscriptionService;
use App\Services\Shared\WebPushService;

final class PushSubscriptionController extends ApiController
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
        $data = $_GET;
        if (strtoupper($method) === 'POST') {
            $data = $this->hydrateCsrfFromHeaderOrBody($_POST);
            if ($data === []) {
                $raw = (string) file_get_contents('php://input');
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $data = $decoded;
                    if (!empty($decoded['_csrf']) && empty($_POST['_csrf'])) {
                        $_POST['_csrf'] = (string) $decoded['_csrf'];
                    }
                }
            }
            $this->requireCsrf();
        }

        try {
            $service = new PushSubscriptionService(
                new WebPushService(new SharedWebPushModel($pdo))
            );
            $result = $service->handle($user, $method, $data, $_SERVER);
            $this->json((int) ($result['status'] ?? 500), (array) ($result['payload'] ?? ['ok' => false]));
        } catch (\Throwable $e) {
            $this->json(500, ['ok' => false, 'error' => 'server_error']);
        }
    }
}
