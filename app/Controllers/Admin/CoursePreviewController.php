<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\AdminCoursePreviewModel;
use App\Services\Admin\AdminAccessService;
use App\Services\Admin\CoursePreviewService;

final class CoursePreviewController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $siteLogoUrl = $this->siteLogoUrl();

        $user = $this->requireAuth(true, $this->siteUrl('admin/course_preview'));
        $access = new AdminAccessService();
        if (!$access->canAccess($user, AdminAccessService::PERMISSION_COURSES)) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf();
        }

        $service = new CoursePreviewService(new AdminCoursePreviewModel($pdo));
        $result = $service->handle(
            $user,
            (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $_GET,
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

        $this->render('admin/course_preview', array_merge(
            [
                'user' => $user,
                'siteLogoUrl' => $siteLogoUrl,
                'adminPermissions' => $access->allowedPermissions($user),
                'hasAdminAccess' => $access->hasAdminAccess($user),
                'isGeneralAdmin' => $access->isGeneralAdmin($user),
            ],
            is_array($result['view'] ?? null) ? $result['view'] : []
        ));
    }
}
