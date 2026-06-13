<?php
declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Core\Controller;
use App\Core\DashboardContextResolver;
use App\Core\RouteDispatcher;
use App\Models\TeacherDashboardModel;
use App\Models\TeacherReferralsModel;
use App\Models\SharedPlatformSettingsModel;
use App\Models\SharedReferralModel;
use App\Models\ReferralWalletModel;
use App\Models\TeacherWithdrawalsModel;
use App\Services\Shared\DashboardBadgeService;
use App\Services\Shared\PlatformSettingsService;
use App\Services\Shared\ReferralService;
use App\Services\Teacher\WithdrawalsService;

final class WithdrawalsController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $siteLogoUrl = $this->siteLogoUrl();
        $pageMode = $this->resolvePageMode();
        $pageRoute = $this->resolvePageRoute($pageMode);
        $user = $this->requireAuth(true, $this->siteUrl($pageRoute));
        $roles = json_decode($user['roles_json'] ?? '[]', true) ?: [];
        if (!in_array('teacher', $roles, true) && !in_array('admin', $roles, true)) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        $requestedSpace = 'teacher';
        $badgeData = (new DashboardBadgeService($pdo))->build($user, $requestedSpace);
        $courseCount = 0;
        $activeCourseCount = 0;
        if (($badgeData['space'] ?? null) === 'teacher') {
            $courseTotals = (new TeacherDashboardModel($pdo))->fetchCourseTotalsByStatus((int) ($user['id'] ?? 0));
            foreach ($courseTotals as $row) {
                $count = (int) ($row['cnt'] ?? 0);
                $status = (string) ($row['status'] ?? '');
                $courseCount += $count;
                if ($status === 'approved') {
                    $activeCourseCount += $count;
                }
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf();
        }

        $service = new WithdrawalsService(
            new TeacherWithdrawalsModel($pdo),
            new TeacherReferralsModel($pdo),
            new ReferralWalletModel($pdo),
            new PlatformSettingsService(new SharedPlatformSettingsModel($pdo)),
            new ReferralService(new SharedReferralModel($pdo))
        );
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

        $this->render('teacher/withdrawals', array_merge(
            [
                'siteLogoUrl' => $siteLogoUrl,
                'user' => $user,
                'unreadCount' => (int) ($badgeData['unreadCount'] ?? 0),
                'supportUnreadCount' => (int) ($badgeData['supportUnreadCount'] ?? 0),
                'pendingMeetingsCount' => (int) ($badgeData['pendingMeetingsCount'] ?? 0),
                'courseCount' => $courseCount,
                'activeCourseCount' => $activeCourseCount > 0 ? $activeCourseCount : $courseCount,
                'dashboardUrl' => DashboardContextResolver::dashboardUrlForUser($user, 'teacher'),
                'withdrawalsPageMode' => $pageMode,
                'withdrawalsPageRoute' => $pageRoute,
                'withdrawalsDetailPage' => max(1, (int) ($_GET['page'] ?? 1)),
            ],
            is_array($result['view'] ?? null) ? $result['view'] : []
        ));
    }

    private function resolvePageMode(): string
    {
        $route = RouteDispatcher::normalizePath(
            (string) ($_SERVER['REQUEST_URI'] ?? '/'),
            defined('BASE_PATH') ? (string) BASE_PATH : '/'
        );

        return match ($route) {
            'teacher/withdrawals/revenus' => 'revenues',
            'teacher/withdrawals/transactions' => 'transactions',
            'teacher/withdrawals/retraits' => 'requests',
            default => 'overview',
        };
    }

    private function resolvePageRoute(string $pageMode): string
    {
        return match ($pageMode) {
            'revenues' => 'teacher/withdrawals/revenus',
            'transactions' => 'teacher/withdrawals/transactions',
            'requests' => 'teacher/withdrawals/retraits',
            default => 'teacher/withdrawals',
        };
    }
}

