<?php
declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Core\Controller;
use App\Models\SharedPlatformSettingsModel;
use App\Models\SharedReferralModel;
use App\Models\TeacherDashboardModel;
use App\Models\TeacherCoursesModel;
use App\Models\TeacherMeetingsModel;
use App\Models\TeacherReviewsModel;
use App\Models\TeacherWithdrawalsModel;
use App\Services\Shared\PlatformSettingsService;
use App\Services\Shared\ReferralService;
use App\Services\Teacher\DashboardService;

final class DashboardController extends Controller
{
    public function __invoke(): void
    {
        $user = $this->requireAuth(true, $this->siteUrl('teacher/dashboard'));
        $roles = json_decode((string) ($user['roles_json'] ?? '[]'), true) ?: [];
        $isAdmin = in_array('admin', $roles, true);
        $isTeacher = in_array('teacher', $roles, true);
        if (!$isAdmin && !$isTeacher) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        /** @var \PDO $pdo */
        $pdo = $this->pdo();
        $service = new DashboardService(
            new TeacherDashboardModel($pdo),
            new TeacherWithdrawalsModel($pdo),
            new PlatformSettingsService(new SharedPlatformSettingsModel($pdo)),
            new ReferralService(new SharedReferralModel($pdo)),
            new TeacherCoursesModel($pdo),
            new TeacherMeetingsModel($pdo),
            new TeacherReviewsModel($pdo)
        );
        /** @var array<string,mixed> $query */
        $query = $_GET;
        $viewData = $service->buildViewData($user, array_values($roles), $query);

        $this->render('teacher/dashboard', array_merge(
            [
                'siteLogoUrl' => $this->siteLogoUrl(),
                'injectGlobalAssets' => false,
            ],
            $viewData
        ));
    }
}

