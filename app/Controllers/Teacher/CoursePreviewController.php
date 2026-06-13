<?php
declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Core\Controller;
use App\Models\TeacherCoursePreviewModel;
use App\Services\Teacher\CoursePreviewService;

final class CoursePreviewController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $siteLogoUrl = $this->siteLogoUrl();
        $user = $this->requireAuth(true, $this->siteUrl('teacher/course_preview'));
        $roles = json_decode($user['roles_json'] ?? '[]', true) ?: [];
        if (!in_array('teacher', $roles, true) && !in_array('admin', $roles, true)) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        if ((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->requireCsrf();
        }

        $service = new CoursePreviewService(new TeacherCoursePreviewModel($pdo));
        $result = $service->build(
            $user,
            $roles,
            $_GET,
            (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $_POST
        );

        if (!empty($result['httpStatus']) && !empty($result['abortMessage'])) {
            http_response_code((int) $result['httpStatus']);
            exit((string) $result['abortMessage']);
        }

        if (!empty($result['redirectTo'])) {
            header('Location: ' . (string) $result['redirectTo']);
            exit;
        }

        $this->render('teacher/course_preview', array_merge(
            ['siteLogoUrl' => $siteLogoUrl],
            is_array($result['view'] ?? null) ? $result['view'] : []
        ));
    }
}

