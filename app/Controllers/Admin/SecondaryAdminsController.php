<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\RouteDispatcher;
use App\Models\AdminDashboardModel;
use App\Models\AdminSecondaryAdminsModel;
use App\Services\Admin\AdminAccessService;
use App\Services\Admin\SecondaryAdminsService;

final class SecondaryAdminsController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();
        $pageMode = $this->resolvePageMode();

        $user = $this->requireAuth(true, $this->siteUrl('admin/secondary_admins'));
        $access = new AdminAccessService();
        if (!$access->isGeneralAdmin($user)) {
            header('Location: ' . $this->siteUrl('admin/dashboard'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf();
        }

        $service = new SecondaryAdminsService(
            new AdminSecondaryAdminsModel($pdo),
            new AdminDashboardModel($pdo)
        );
        $result = $service->handle(
            (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $_POST,
            $user,
            $_GET,
            $pageMode
        );

        if (!empty($result['redirectTo'])) {
            header('Location: ' . (string) $result['redirectTo']);
            exit;
        }

        $viewData = is_array($result['view'] ?? null) ? $result['view'] : [];
        $viewName = trim((string) ($viewData['viewTemplate'] ?? 'admin/secondary_admins'));
        if ($viewName === '') {
            $viewName = 'admin/secondary_admins';
        }
        unset($viewData['viewTemplate']);

        $this->render($viewName, array_merge(
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

    private function resolvePageMode(): string
    {
        $route = RouteDispatcher::normalizePath(
            (string) ($_SERVER['REQUEST_URI'] ?? '/'),
            defined('BASE_PATH') ? (string) BASE_PATH : '/'
        );

        return match ($route) {
            'admin/secondary_admins/create' => 'create',
            'admin/secondary_admins/grant' => 'grant',
            'admin/secondary_admins/edit' => 'edit',
            'admin/secondary_admins/activity' => 'activity',
            default => 'list',
        };
    }
}
