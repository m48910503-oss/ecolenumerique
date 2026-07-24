<?php
declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Core\Controller;
use App\Models\TeacherCoursesModel;
use App\Services\Teacher\CoursesService;

final class CoursesController extends Controller
{
    public function __invoke(): void
    {
        $siteLogoUrl = $this->siteLogoUrl();

        $user = $this->requireAuth(true, $this->siteUrl('teacher/courses'));
        $roles = json_decode((string) ($user['roles_json'] ?? '[]'), true) ?: [];
        if (!in_array('teacher', $roles, true) && !in_array('admin', $roles, true)) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf();
        }

        /** @var \PDO $pdo */
        $pdo = $this->pdo();
        $service = new CoursesService(new TeacherCoursesModel($pdo));
        $result = $service->handle(
            $user,
            array_values($roles),
            (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $_POST,
            $_GET
        );

        if (!empty($result['redirectTo'])) {
            header('Location: ' . (string) $result['redirectTo']);
            exit;
        }

        $this->render('teacher/courses', array_merge(
            ['siteLogoUrl' => $siteLogoUrl],
            is_array($result['view'] ?? null) ? $result['view'] : []
        ));
    }
}

