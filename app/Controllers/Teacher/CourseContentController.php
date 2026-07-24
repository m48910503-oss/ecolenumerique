<?php
declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Core\Controller;
use App\Models\TeacherCourseContentModel;
use App\Services\Teacher\CourseContentService;

final class CourseContentController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();
        $requestMethod = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $isAjax = strtolower(trim((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''))) === 'xmlhttprequest';

        $siteLogoUrl = $this->siteLogoUrl();
        $user = $this->requireAuth(true, $this->siteUrl('login'));
        $roles = json_decode($user['roles_json'] ?? '[]', true) ?: [];
        $isAdmin = in_array('admin', $roles, true);
        if (!$isAdmin && !in_array('teacher', $roles, true)) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        if ($requestMethod === 'POST') {
            $this->requireCsrf();
        }

        $service = new CourseContentService($pdo, new TeacherCourseContentModel($pdo));
        $result = $service->handle(
            $user,
            $isAdmin,
            $requestMethod,
            $_GET,
            $_POST,
            $_FILES
        );

        if ($isAjax && $requestMethod === 'POST') {
            if (!empty($result['redirectTo'])) {
                header('Content-Type: application/json; charset=utf-8');
                echo (string) json_encode([
                    'ok' => true,
                    'redirectTo' => (string) $result['redirectTo'],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            $view = is_array($result['view'] ?? null) ? $result['view'] : [];
            $errors = is_array($view['errors'] ?? null)
                ? array_values(array_map(static fn($error): string => (string) $error, $view['errors']))
                : [];
            if ($errors !== []) {
                http_response_code(422);
                header('Content-Type: application/json; charset=utf-8');
                echo (string) json_encode([
                    'ok' => false,
                    'errors' => $errors,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
        }

        if (!empty($result['redirectTo'])) {
            header('Location: ' . (string) $result['redirectTo']);
            exit;
        }

        if (!empty($result['httpStatus']) && !empty($result['abortMessage'])) {
            http_response_code((int) $result['httpStatus']);
            exit((string) $result['abortMessage']);
        }

        $this->render('teacher/course_content', array_merge(
            [
                'siteLogoUrl' => $siteLogoUrl,
                'injectGlobalAssets' => false,
            ],
            is_array($result['view'] ?? null) ? $result['view'] : []
        ));
    }
}

