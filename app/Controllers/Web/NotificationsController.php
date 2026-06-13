<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Core\DashboardContextResolver;
use App\Models\EnrollmentModel;
use App\Models\WebNotificationsModel;
use App\Models\SharedCertificateModel;
use App\Models\SharedReferralModel;
use App\Models\StudentDashboardModel;
use App\Models\TeacherDashboardModel;
use App\Services\Shared\CertificateOpsService;
use App\Services\Shared\ReferralService;
use App\Services\Shared\DashboardBadgeService;
use App\Services\Student\DashboardService;
use App\Services\Web\NotificationsService;

final class NotificationsController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $user = $this->requireAuth(true, $this->siteUrl('notifications'));
        $requestedSpace = isset($_GET['space']) ? (string) $_GET['space'] : null;
        $badgeData = (new DashboardBadgeService($pdo))->build($user, $requestedSpace);
        $courseCount = 0;
        if (($badgeData['space'] ?? null) === 'teacher') {
            $courseTotals = (new TeacherDashboardModel($pdo))->fetchCourseTotalsByStatus((int) ($user['id'] ?? 0));
            foreach ($courseTotals as $row) {
                $courseCount += (int) ($row['cnt'] ?? 0);
            }
        }
        $service = new NotificationsService(new WebNotificationsModel($pdo));
        $result = $service->handle(
            (int) $user['id'],
            (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $_GET,
            $_POST
        );

        $studentDashboardData = [];
        if (($badgeData['space'] ?? null) === 'student') {
            $studentDashboardData = (new DashboardService(
                new StudentDashboardModel($pdo),
                new EnrollmentModel($pdo),
                new ReferralService(new SharedReferralModel($pdo)),
                new CertificateOpsService(new SharedCertificateModel($pdo))
            ))->buildViewData($user);
        }

        if (!empty($result['redirect'])) {
            header('Location: ' . (string) $result['redirect']);
            exit;
        }

        $view = $this->resolveNotificationsView($user, $badgeData['space'] ?? null);

        $this->render($view, array_merge($studentDashboardData, [
            'user' => $user,
            'notifications' => $result['notifications'] ?? [],
            'unreadCount' => (int) ($result['unreadCount'] ?? 0),
            'supportUnreadCount' => (int) ($badgeData['supportUnreadCount'] ?? 0),
            'pendingMeetingsCount' => (int) ($badgeData['pendingMeetingsCount'] ?? 0),
            'dashboardSpace' => $badgeData['space'] ?? null,
            'scope' => (string) ($result['scope'] ?? 'all'),
            'scopeCounts' => is_array($result['scopeCounts'] ?? null) ? $result['scopeCounts'] : [],
            'latestNotification' => is_array($result['latestNotification'] ?? null) ? $result['latestNotification'] : [],
            'actionableNotifications' => is_array($result['actionableNotifications'] ?? null) ? $result['actionableNotifications'] : [],
            'success' => (string) ($result['success'] ?? ''),
            'page' => (int) ($result['page'] ?? 1),
            'totalPages' => (int) ($result['totalPages'] ?? 1),
            'totalItems' => (int) ($result['totalItems'] ?? 0),
            'perPage' => (int) ($result['perPage'] ?? 10),
            'dashboardUrl' => DashboardContextResolver::dashboardUrlForUser($user, is_string($badgeData['space'] ?? null) ? (string) $badgeData['space'] : null),
            'siteLogoUrl' => $this->siteLogoUrl(),
            'courseCount' => $courseCount,
        ]));
    }

    private function resolveNotificationsView(array $user, ?string $dashboardSpace): string
    {
        if ($dashboardSpace === 'teacher') {
            return 'teacher/notifications';
        }
        if ($dashboardSpace === 'student') {
            return 'student/notifications';
        }
        if ($dashboardSpace === 'company') {
            return 'company/notifications';
        }

        $roles = DashboardContextResolver::rolesFromUser($user);
        if (in_array('company_admin', $roles, true) || in_array('company_member', $roles, true)) {
            return 'company/notifications';
        }

        return 'web/notifications';
    }
}
