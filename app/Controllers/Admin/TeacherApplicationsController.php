<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\AdminTeacherApplicationsModel;
use App\Services\Admin\AdminAccessService;
use App\Services\Admin\TeacherApplicationsService;

final class TeacherApplicationsController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $siteLogoUrl = $this->siteLogoUrl();

        $user = $this->requireAuth(true, $this->siteUrl('admin/teacher_applications'));
        $access = new AdminAccessService();
        if (!$access->canAccess($user, AdminAccessService::PERMISSION_TEACHER_APPLICATIONS)) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf();
        }

        $service = new TeacherApplicationsService($pdo, new AdminTeacherApplicationsModel($pdo));
        $result = $service->handle($user, (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'), $_POST, $_GET);

        if (!empty($result['redirectTo'])) {
            header('Location: ' . (string) $result['redirectTo']);
            exit;
        }

        $this->render('admin/teacher_applications', array_merge(
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

