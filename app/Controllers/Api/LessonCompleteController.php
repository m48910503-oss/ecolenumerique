<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\ApiLearningModel;
use App\Services\Api\LearningService;

final class LessonCompleteController extends ApiController
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $user = $this->currentUser();
        if (!$user) {
            $this->json(401, ['status' => 'error', 'message' => 'Vous devez etre connecte.']);
            return;
        }

        $method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $data = $this->hydrateCsrfFromHeaderOrBody();
        if ($method === 'POST') {
            $this->requireCsrf();
        }
        if ($data === []) {
            $data = $_POST;
        }

        $service = new LearningService(new ApiLearningModel($pdo));
        $result = $service->handleLessonComplete($user, $method, $data);
        $this->json((int) $result['status'], (array) $result['payload']);
    }
}

