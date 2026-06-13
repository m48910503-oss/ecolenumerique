<?php
declare(strict_types=1);

namespace App\Controllers\Company;

use App\Core\Controller;
use App\Models\CompanyDashboardModel;
use App\Models\EnrollmentModel;
use App\Models\SharedCompanySchemaModel;
use App\Models\StudentDashboardModel;
use App\Services\Company\MemberDashboardService;
use App\Services\Shared\CompanySchemaService;
use App\Services\Company\DashboardService;

final class DashboardController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $siteLogoUrl = $this->siteLogoUrl();
        $user = $this->requireAuth(true, $this->siteUrl('company/dashboard'));
        $roles = json_decode($user['roles_json'] ?? '[]', true) ?: [];
        $isCompanyAdmin = in_array('company_admin', $roles, true);
        $isCompanyMember = in_array('company_member', $roles, true);
        if (!$isCompanyAdmin && !$isCompanyMember) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        if (!$isCompanyAdmin) {
            $service = new MemberDashboardService(
                new CompanyDashboardModel($pdo),
                new StudentDashboardModel($pdo),
                new EnrollmentModel($pdo)
            );
            $state = $service->buildViewData($user);

            $this->render('company/member_dashboard', [
                'siteLogoUrl' => $siteLogoUrl,
                'user' => $user,
                'errors' => $state['errors'] ?? [],
                'company' => $state['company'] ?? null,
                'member' => $state['member'] ?? null,
                'team' => $state['team'] ?? null,
                'companyAdmins' => $state['companyAdmins'] ?? [],
                'assignedCourses' => $state['assignedCourses'] ?? [],
                'otherCourses' => $state['otherCourses'] ?? [],
                'notifications' => $state['notifications'] ?? [],
                'recentActivity' => $state['recentActivity'] ?? [],
                'nextMeeting' => $state['nextMeeting'] ?? null,
                'unreadCount' => (int) ($state['unreadCount'] ?? 0),
                'supportUnreadCount' => (int) ($state['supportUnreadCount'] ?? 0),
                'upcomingMeetingsCount' => (int) ($state['upcomingMeetingsCount'] ?? 0),
                'kpiCards' => $state['kpiCards'] ?? [],
            ]);
            return;
        }

        $service = new DashboardService(
            new CompanyDashboardModel($pdo),
            new CompanySchemaService(new SharedCompanySchemaModel($pdo))
        );
        $state = $service->buildInitialState($user);
        $state['successMessage'] = (string) $this->flashGet('success', '');
        $state['inviteLink'] = (string) $this->flashGet('invite_link', '');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf();
            $state = $service->handlePost($state, $user, $_POST, $_FILES);
        }

        if (
            $_SERVER['REQUEST_METHOD'] === 'POST'
            && empty($state['errors'])
            && (string) ($state['successMessage'] ?? '') !== ''
        ) {
            $this->flashSet('success', (string) $state['successMessage']);
            if ((string) ($state['inviteLink'] ?? '') !== '') {
                $this->flashSet('invite_link', (string) $state['inviteLink']);
            }
            $this->redirectSelf('company/dashboard');
        }

        $this->render('company/dashboard', [
            'siteLogoUrl' => $siteLogoUrl,
            'user' => $user,
            'errors' => $state['errors'] ?? [],
            'successMessage' => (string) ($state['successMessage'] ?? ''),
            'inviteLink' => (string) ($state['inviteLink'] ?? ''),
            'company' => $state['company'] ?? null,
            'members' => $state['members'] ?? [],
            'invites' => $state['invites'] ?? [],
            'memberRows' => $state['memberRows'] ?? [],
            'inviteRows' => $state['inviteRows'] ?? [],
            'seatCourseRows' => $state['seatCourseRows'] ?? [],
            'seatSummary' => $state['seatSummary'] ?? [],
            'billingSummary' => $state['billingSummary'] ?? [],
            'seatMemberOptions' => $state['seatMemberOptions'] ?? [],
            'memberSummary' => $state['memberSummary'] ?? [],
            'inviteSummary' => $state['inviteSummary'] ?? [],
            'teams' => $state['teams'] ?? [],
            'teamAssignments' => $state['teamAssignments'] ?? [],
            'onboardingTasks' => $state['onboardingTasks'] ?? [],
            'onboardingSummary' => $state['onboardingSummary'] ?? [],
            'governance' => $state['governance'] ?? [],
            'businessKpis' => $state['businessKpis'] ?? [],
            'successReviews' => $state['successReviews'] ?? [],
            'healthChecks' => $state['healthChecks'] ?? [],
            'recentActivity' => $state['recentActivity'] ?? [],
            'unreadCount' => (int) ($state['unreadCount'] ?? 0),
        ]);
    }
}
