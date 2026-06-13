<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\AdminSupportAgentsModel;
use App\Services\Admin\AdminAccessService;
use App\Services\Admin\SupportAgentsService;

final class SupportAgentsController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $user = $this->requireAuth(true, $this->siteUrl('admin/support_agents'));
        $access = new AdminAccessService();
        if (!$access->canAccess($user, AdminAccessService::PERMISSION_SUPPORT_AGENTS)) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf();
        }

        $service = new SupportAgentsService(new AdminSupportAgentsModel($pdo));
        $result = $service->handle((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'), $_POST);

        if (!empty($result['redirectTo'])) {
            header('Location: ' . (string) $result['redirectTo']);
            exit;
        }

        $this->render('admin/support_agents', array_merge(
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
