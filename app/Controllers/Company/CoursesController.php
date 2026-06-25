<?php
declare(strict_types=1);

namespace App\Controllers\Company;

use App\Core\Controller;
use App\Models\CompanyDashboardModel;
use App\Models\EnrollmentModel;
use App\Models\SharedCompanySchemaModel;
use App\Models\StudentDashboardModel;
use App\Services\Company\DashboardService;
use App\Services\Company\MemberDashboardService;
use App\Services\Shared\CompanySchemaService;

final class CoursesController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $siteLogoUrl = $this->siteLogoUrl();
        $user = $this->requireAuth(true, $this->siteUrl('company/courses'));
        $roles = json_decode((string) ($user['roles_json'] ?? '[]'), true) ?: [];
        $isCompanyAdmin = in_array('company_admin', $roles, true);
        $isCompanyMember = in_array('company_member', $roles, true);
        if (!$isCompanyAdmin && !$isCompanyMember) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        $memberService = new MemberDashboardService(
            new CompanyDashboardModel($pdo),
            new StudentDashboardModel($pdo),
            new EnrollmentModel($pdo)
        );
        $memberState = $memberService->buildViewData($user);

        $adminState = [
            'company' => null,
            'seatCourseRows' => [],
            'seatSummary' => [],
            'unreadCount' => 0,
        ];
        if ($isCompanyAdmin) {
            $dashboardService = new DashboardService(
                new CompanyDashboardModel($pdo),
                new CompanySchemaService(new SharedCompanySchemaModel($pdo))
            );
            $adminState = $dashboardService->buildInitialState($user);
        }

        $companySimpleMemberMode = $isCompanyMember && !$isCompanyAdmin;

        $this->render('company/courses', [
            'siteLogoUrl' => $siteLogoUrl,
            'user' => $user,
            'company' => $memberState['company'] ?? $adminState['company'] ?? null,
            'member' => $memberState['member'] ?? null,
            'companyAdmins' => $memberState['companyAdmins'] ?? [],
            'assignedCourses' => $memberState['assignedCourses'] ?? [],
            'otherCourses' => $memberState['otherCourses'] ?? [],
            'seatCourseRows' => $adminState['seatCourseRows'] ?? [],
            'seatSummary' => $adminState['seatSummary'] ?? [],
            'unreadCount' => (int) ($memberState['unreadCount'] ?? $adminState['unreadCount'] ?? 0),
            'supportUnreadCount' => (int) ($memberState['supportUnreadCount'] ?? 0),
            'companySimpleMemberMode' => $companySimpleMemberMode,
            'companySimplePage' => 'courses',
            'companySimpleTitle' => 'Mes cours',
            'companySimpleSubtitle' => 'Retrouvez les formations accessibles dans votre espace entreprise.',
            'companyCourseNote' => $isCompanyAdmin
                ? 'Les formations achetées apparaissent ici. Assignez une place pour ouvrir le cours.'
                : 'Les formations attribuées apparaissent ici avec un accès direct au lecteur.',
        ]);
    }
}
