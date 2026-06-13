<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\AdminAnnouncementsModel;
use App\Services\Admin\AdminAccessService;
use App\Services\Admin\AnnouncementsService;

final class AnnouncementsController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        if (is_string($requestPath) && str_contains($requestPath, '/admin/announcements/create')) {
            $_GET['view'] = 'create';
        }

        $user = $this->requireAuth(true, $this->siteUrl('admin/announcements'));
        $access = new AdminAccessService();
        if (!$access->canAccess($user, AdminAccessService::PERMISSION_ANNOUNCEMENTS)) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->requireCsrf();
        }

        if (isset($_POST['view']) && $_POST['view'] === 'create') {
            $_GET['view'] = 'create';
        }

        $service = new AnnouncementsService(new AdminAnnouncementsModel($pdo));
        $result = $service->handle(
            $user,
            (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $_POST,
            $_GET
        );

        if (!empty($result['redirectTo'])) {
            header('Location: ' . (string) $result['redirectTo']);
            exit;
        }

        $this->render('admin/announcements', array_merge(
            [
                'user' => $user,
                'siteLogoUrl' => $this->siteLogoUrl(),
                'hasAdminAccess' => $access->hasAdminAccess($user),
                'isGeneralAdmin' => $access->isGeneralAdmin($user),
                'adminPermissions' => $access->allowedPermissions($user),
            ],
            is_array($result['view'] ?? null) ? $result['view'] : []
        ));
    }
}
