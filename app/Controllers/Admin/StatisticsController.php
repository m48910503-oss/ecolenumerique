<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\AdminDashboardModel;
use App\Models\AdminStatisticsModel;
use App\Services\Admin\AdminAccessService;
use App\Services\Admin\StatisticsService;

final class StatisticsController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $user = $this->requireAuth(true, $this->siteUrl('admin/statistics'));
        $access = new AdminAccessService();
        if (!$access->canAccess($user, AdminAccessService::PERMISSION_DASHBOARD)) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        $service = new StatisticsService(
            new AdminDashboardModel($pdo),
            new AdminStatisticsModel($pdo)
        );
        $viewData = $service->buildViewData($user, $_GET);

        $this->render('admin/statistics', array_merge(
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
