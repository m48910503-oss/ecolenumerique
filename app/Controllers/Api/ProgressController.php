<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\ApiLearningModel;
use App\Services\Api\LearningService;

final class ProgressController extends ApiController
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
        $action = (string) ($_POST['action'] ?? 'complete');
        $lessonId = (int) ($_POST['lesson_id'] ?? 0);

        $service = new LearningService(new ApiLearningModel($pdo));
        $result = $service->handleProgress($user, $method, $action, $lessonId);
        $this->json((int) $result['status'], (array) $result['payload']);
    }
}

