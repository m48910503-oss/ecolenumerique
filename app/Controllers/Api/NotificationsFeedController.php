<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\ApiNotificationsFeedModel;
use App\Services\Api\NotificationsFeedService;

final class NotificationsFeedController extends ApiController
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

        try {
            $service = new NotificationsFeedService(new ApiNotificationsFeedModel($pdo));
            $result = $service->handle($user, $_GET);
            $this->json((int) ($result['status'] ?? 500), (array) ($result['payload'] ?? ['ok' => false]));
        } catch (\Throwable $e) {
            $this->json(500, ['ok' => false, 'error' => 'server_error']);
        }
    }
}
