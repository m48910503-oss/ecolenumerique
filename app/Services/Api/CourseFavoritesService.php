<?php
declare(strict_types=1);

namespace App\Services\Api;

use App\Core\Service;
use App\Models\ApiCatalogModel;

final class CourseFavoritesService extends Service
{
    private ApiCatalogModel $model;

    public function __construct(ApiCatalogModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $input
     * @return array{status:int,payload:array<string,mixed>}
     */
    public function handle(array $user, string $method, array $input): array
    {
        $userId = (int) ($user['id'] ?? 0);
        if ($userId < 1) {
            return ['status' => 401, 'payload' => ['ok' => false, 'error' => 'auth']];
        }

        if (!$this->model->courseFavoritesTableReady()) {
            return ['status' => 503, 'payload' => ['ok' => false, 'error' => 'favorites_schema_missing']];
        }

        try {
            $this->model->removeOwnedCourseFavorites($userId);
        } catch (\Throwable $e) {
            // Ignore cleanup failures and keep favorites endpoint available.
        }

        if ($method === 'GET') {
            return [
                'status' => 200,
                'payload' => [
                    'ok' => true,
                    'course_ids' => $this->model->fetchFavoriteCourseIds($userId),
                    'count' => $this->model->countCourseFavorites($userId),
                ],
            ];
        }

        if ($method !== 'POST') {
            return ['status' => 405, 'payload' => ['ok' => false, 'error' => 'method_not_allowed']];
        }

        $courseId = (int) ($input['course_id'] ?? 0);
        if ($courseId < 1) {
            return ['status' => 400, 'payload' => ['ok' => false, 'error' => 'missing_course_id']];
        }

        $course = $this->model->findApprovedCourseForFavorite($courseId);
        if (!$course) {
            return ['status' => 404, 'payload' => ['ok' => false, 'error' => 'course_not_found']];
        }

        if ($this->model->hasActiveEnrollmentForCourseGroup($userId, $courseId)) {
            return ['status' => 400, 'payload' => ['ok' => false, 'error' => 'already_owned']];
        }

        $action = strtolower(trim((string) ($input['action'] ?? 'toggle')));
        $isFavorite = $this->model->isCourseFavorite($userId, $courseId);
        $favorited = $isFavorite;

        if ($action === 'add') {
            if (!$isFavorite) {
                $this->model->addCourseFavorite($userId, $courseId);
            }
            $favorited = true;
        } elseif ($action === 'remove') {
            if ($isFavorite) {
                $this->model->removeCourseFavorite($userId, $courseId);
            }
            $favorited = false;
        } else {
            if ($isFavorite) {
                $this->model->removeCourseFavorite($userId, $courseId);
                $favorited = false;
            } else {
                $this->model->addCourseFavorite($userId, $courseId);
                $favorited = true;
            }
        }

        return [
            'status' => 200,
            'payload' => [
                'ok' => true,
                'course_id' => $courseId,
                'favorited' => $favorited ? 1 : 0,
                'count' => $this->model->countCourseFavorites($userId),
            ],
        ];
    }
}
