<?php
declare(strict_types=1);

namespace App\Controllers\Student;

use App\Core\Controller;
use App\Models\EnrollmentModel;
use App\Models\SharedCertificateModel;
use App\Models\SharedReferralModel;
use App\Models\StudentDashboardModel;
use App\Services\Shared\CertificateOpsService;
use App\Services\Shared\ReferralService;
use App\Services\Student\DashboardService;

final class DashboardController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $user = $this->requireAuth(true, $this->siteUrl('student/dashboard'));
        $this->disableCache();
        $siteLogoUrl = $this->siteLogoUrl();

        $service = new DashboardService(
            new StudentDashboardModel($pdo),
            new EnrollmentModel($pdo),
            new ReferralService(new SharedReferralModel($pdo)),
            new CertificateOpsService(new SharedCertificateModel($pdo))
        );
        $viewData = $service->buildViewData($user);

        $this->render('student/dashboard', array_merge(
            ['siteLogoUrl' => $siteLogoUrl],
            $viewData
        ));
    }
}


