<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\AdminSupportModel;
use App\Services\Admin\AdminAccessService;
use App\Services\Admin\SupportService;
use App\Services\Support\SupportAccessService;

final class SupportController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $user = $this->requireAuth(true, $this->siteUrl('admin/support'));
        $adminAccess = new AdminAccessService();
        $access = new SupportAccessService();
        $roles = $access->rolesFromUser($user);
        $isSupportAgent = in_array('support', $roles, true);
        $isAllowedAdmin = $adminAccess->canAccess($user, AdminAccessService::PERMISSION_SUPPORT);
        if (!$isSupportAgent && !$isAllowedAdmin) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        $service = new SupportService(new AdminSupportModel($pdo));
        $viewData = $service->buildViewData($user, $_GET);

        $this->render('admin/support', array_merge([
            'siteLogoUrl' => $this->siteLogoUrl(),
            'user' => $user,
            'adminPermissions' => $adminAccess->allowedPermissions($user),
            'hasAdminAccess' => $adminAccess->hasAdminAccess($user),
            'isGeneralAdmin' => $adminAccess->isGeneralAdmin($user),
        ], $viewData));
    }
}
