<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\ApiLearningModel;
use App\Services\Api\LearningService;

final class ProgressPositionController extends ApiController
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
        if ($method === 'POST') {
            $this->requireCsrf();
        }
        $lessonId = isset($_GET['lesson_id']) ? (int) $_GET['lesson_id'] : (int) ($_POST['lesson_id'] ?? 0);

        $service = new LearningService(new ApiLearningModel($pdo));
        $result = $service->handleProgressPosition($user, $method, $lessonId, $_POST);
        $this->json((int) $result['status'], (array) $result['payload']);
    }
}

