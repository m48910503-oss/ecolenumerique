<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\AdminSecondaryAdminsModel;
use App\Services\Admin\AdminAccessService;
use App\Services\Admin\SecondaryAdminsService;

final class SecondaryAdminsController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $user = $this->requireAuth(true, $this->siteUrl('admin/secondary_admins'));
        $access = new AdminAccessService();
        if (!$access->isGeneralAdmin($user)) {
            header('Location: ' . $this->siteUrl('admin/dashboard'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf();
        }

        $service = new SecondaryAdminsService(new AdminSecondaryAdminsModel($pdo));
        $result = $service->handle(
            (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $_POST,
            $user,
            $_GET
        );

        if (!empty($result['redirectTo'])) {
            header('Location: ' . (string) $result['redirectTo']);
            exit;
        }

        $this->render('admin/secondary_admins', array_merge(
            [
                'siteLogoUrl' => $this->siteLogoUrl(),
                'user' => $user,
                'adminPermissions' => $access->allowedPermissions($user),
                'hasAdminAccess' => $access->hasAdminAccess($user),
                'isGeneralAdmin' => $access->isGeneralAdmin($user),
            ],
            is_array($result['view'] ?? null) ? $result['view'] : []
        ));
    }
}
