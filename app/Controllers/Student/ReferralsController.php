<?php
declare(strict_types=1);

namespace App\Controllers\Student;

use App\Core\Controller;
use App\Core\RouteDispatcher;
use App\Models\SharedPlatformSettingsModel;
use App\Models\SharedReferralModel;
use App\Models\StudentDashboardModel;
use App\Models\TeacherWithdrawalsModel;
use App\Models\ReferralWalletModel;
use App\Services\Shared\PlatformSettingsService;
use App\Services\Shared\ReferralService;
use App\Services\Student\ReferralsService;

final class ReferralsController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $siteLogoUrl = $this->siteLogoUrl();
        $user = $this->requireAuth(true, $this->siteUrl('student/affiliation'));
        $this->disableCache();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf();
        }

        $pageMode = $this->resolvePageMode();

        $service = new ReferralsService(
            new StudentDashboardModel($pdo),
            new TeacherWithdrawalsModel($pdo),
            new PlatformSettingsService(new SharedPlatformSettingsModel($pdo)),
            new ReferralService(new SharedReferralModel($pdo)),
            new ReferralWalletModel($pdo)
        );
        $result = $service->handle($user, (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'), $_POST, $_GET, $pageMode);
        if (!empty($result['redirectTo'])) {
            header('Location: ' . (string) $result['redirectTo']);
            exit;
        }

        $this->render('student/referrals', array_merge(
            [
                'siteLogoUrl' => $siteLogoUrl,
                'user' => $user,
                'referralsPageMode' => $pageMode,
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
            'student/referrals/filleuls',
            'student/affiliation/filleuls' => 'referees',
            'student/referrals/gains',
            'student/affiliation/gains' => 'history',
            default => 'overview',
        };
    }
}
