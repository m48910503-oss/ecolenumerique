<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Models\SharedCertificateModel;
use App\Models\WebProfileModel;
use App\Services\Admin\AdminAccessService;
use App\Services\Shared\DashboardBadgeService;
use App\Services\Shared\CertificateOpsService;
use App\Services\Web\ProfileService;

final class ProfileController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $user = $this->requireAuth(false, $this->siteUrl('profile'));
        if ((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->requireCsrf();
        }

        $service = new ProfileService(
            new WebProfileModel($pdo),
            new CertificateOpsService(new SharedCertificateModel($pdo))
        );
        $data = $service->handle(
            $user,
            (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $_POST,
            $_FILES
        );

        $requestedSpace = isset($_GET['space']) ? (string) $_GET['space'] : null;
        $badgeData = (new DashboardBadgeService($pdo))->build($user, $requestedSpace);
        $adminAccess = new AdminAccessService($pdo);

        $redirectTo = (string) ($data['redirect'] ?? '');
        $activeSpace = (string) ($badgeData['space'] ?? '');
        if (
            $redirectTo !== ''
            && $activeSpace !== ''
            && stripos($redirectTo, 'space=') === false
            && str_starts_with($redirectTo, $this->siteUrl('profile'))
        ) {
            $redirectTo .= (str_contains($redirectTo, '?') ? '&' : '?') . 'space=' . urlencode($activeSpace);
        }

        if ($redirectTo !== '') {
            header('Location: ' . $redirectTo);
            exit;
        }

        $this->render('web/profile', array_merge(
            $data,
            [
                'unreadCount' => (int) ($badgeData['unreadCount'] ?? 0),
                'supportUnreadCount' => (int) ($badgeData['supportUnreadCount'] ?? 0),
                'pendingMeetingsCount' => (int) ($badgeData['pendingMeetingsCount'] ?? 0),
                'dashboardSpace' => $badgeData['space'] ?? null,
                'adminPermissions' => $adminAccess->allowedPermissions($user),
                'isGeneralAdmin' => $adminAccess->isGeneralAdmin($user),
                'hasAdminAccess' => $adminAccess->hasAdminAccess($user),
            ]
        ));
    }
}
