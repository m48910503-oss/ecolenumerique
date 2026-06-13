<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\AdminDashboardModel;
use App\Services\Admin\AdminAccessService;
use App\Services\Admin\DashboardService;

final class DashboardController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $user = $this->requireAuth(true, $this->siteUrl('admin/dashboard'));
        $access = new AdminAccessService();
        if (!$access->canAccess($user, AdminAccessService::PERMISSION_DASHBOARD)) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        $service = new DashboardService(new AdminDashboardModel($pdo));
        $viewData = $service->buildViewData($user, $_GET);

        $this->render('admin/dashboard', array_merge(
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


