<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\AdminCourseInsightsModel;
use App\Services\Admin\AdminAccessService;
use App\Services\Admin\CourseInsightsService;

final class CourseInsightsController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $user = $this->requireAuth(true, $this->siteUrl('admin/course_insights'));
        $access = new AdminAccessService();
        if (!$access->canAccess($user, AdminAccessService::PERMISSION_COURSES)) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        $service = new CourseInsightsService(new AdminCourseInsightsModel($pdo));
        $viewData = $service->buildViewData($_GET);

        $this->render('admin/course_insights', array_merge(
            [
                'siteLogoUrl' => $this->siteLogoUrl(),
                'user' => $user,
                'adminPermissions' => $access->allowedPermissions($user),
                'hasAdminAccess' => $access->hasAdminAccess($user),
                'isGeneralAdmin' => $access->isGeneralAdmin($user),
            ],
            $viewData
        ));
    }
}
