<?php
declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Core\Controller;
use App\Core\RouteDispatcher;
use App\Services\Shared\DashboardBadgeService;
use App\Models\SharedReferralModel;
use App\Models\SharedPlatformSettingsModel;
use App\Models\TeacherReferralsModel;
use App\Services\Shared\PlatformSettingsService;
use App\Services\Shared\ReferralService;
use App\Services\Teacher\ReferralsService;

final class ReferralsController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $siteLogoUrl = $this->siteLogoUrl();
        $user = $this->requireAuth(true, $this->siteUrl('teacher/referrals'));
        $roles = json_decode($user['roles_json'] ?? '[]', true) ?: [];
        if (!in_array('teacher', $roles, true) && !in_array('admin', $roles, true)) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        $badgeData = (new DashboardBadgeService($pdo))->build($user, 'teacher');
        $pageMode = $this->resolvePageMode();

        $service = new ReferralsService(
            new TeacherReferralsModel($pdo),
            new ReferralService(new SharedReferralModel($pdo)),
            new PlatformSettingsService(new SharedPlatformSettingsModel($pdo))
        );
        $result = $service->handle($user, $_GET, $pageMode);

        $this->render('teacher/referrals', array_merge(
            [
                'siteLogoUrl' => $siteLogoUrl,
                'unreadCount' => (int) ($badgeData['unreadCount'] ?? 0),
                'supportUnreadCount' => (int) ($badgeData['supportUnreadCount'] ?? 0),
                'pendingMeetingsCount' => (int) ($badgeData['pendingMeetingsCount'] ?? 0),
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
            'teacher/referrals/filleuls',
            'teacher/affiliation/filleuls' => 'referees',
            default => 'overview',
        };
    }
}

