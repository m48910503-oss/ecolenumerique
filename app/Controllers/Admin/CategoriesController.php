<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\AdminCategoriesModel;
use App\Services\Admin\AdminAccessService;
use App\Services\Admin\CategoriesService;

final class CategoriesController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $siteLogoUrl = $this->siteLogoUrl();

        $user = $this->requireAuth(true, $this->siteUrl('admin/categories'));
        $access = new AdminAccessService();
        if (!$access->canAccess($user, AdminAccessService::PERMISSION_CATEGORIES)) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf();
        }

        $service = new CategoriesService($pdo, new AdminCategoriesModel($pdo));
        $result = $service->handle((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'), $_POST);

        if (!empty($result['redirectTo'])) {
            header('Location: ' . (string) $result['redirectTo']);
            exit;
        }

        $this->render('admin/categories', array_merge(
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

