<?php
declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Core\Controller;
use App\Core\DashboardContextResolver;
use App\Models\NotificationModel;
use App\Models\TeacherApplicationModel;
use App\Models\UserModel;
use App\Services\Teacher\ApplyService;

final class ApplyController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $siteLogoUrl = $this->siteLogoUrl();
        $user = $this->requireAuth(true, $this->siteUrl('teacher/apply'));
        $roles = json_decode($user['roles_json'] ?? '[]', true) ?: [];

        $service = new ApplyService(
            $pdo,
            new TeacherApplicationModel($pdo),
            new UserModel($pdo),
            new NotificationModel($pdo)
        );

        $state = $service->buildInitialState($user, $roles, $_POST);
        $state['successMessage'] = (string) $this->flashGet('success', '');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf();
            $state = $service->submit($state, $user, $_FILES);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($state['errors']) && (string) $state['successMessage'] !== '') {
            $this->flashSet('success', (string) $state['successMessage']);
            $this->redirectSelf('teacher/apply');
        }

        $this->render('teacher/apply', [
            'siteLogoUrl' => $siteLogoUrl,
            'alreadyTeacher' => (bool) $state['alreadyTeacher'],
            'pendingApplication' => (bool) ($state['pendingApplication'] ?? false),
            'isCompanyAccount' => (bool) ($state['isCompanyAccount'] ?? false),
            'errors' => $state['errors'],
            'successMessage' => (string) $state['successMessage'],
            'formLocked' => (bool) $state['formLocked'],
            'profileIncomplete' => (bool) ($state['profileIncomplete'] ?? false),
            'profileMissingFields' => is_array($state['profileMissingFields'] ?? null) ? $state['profileMissingFields'] : [],
            'driveReady' => (bool) $state['driveReady'],
            'driveNeedsConnect' => (bool) $state['driveNeedsConnect'],
            'driveMaxMb' => (int) $state['driveMaxMb'],
            'title' => (string) $state['title'],
            'bio' => (string) $state['bio'],
            'teachingExperience' => (string) ($state['teaching_experience'] ?? ''),
            'portfolioUrl' => (string) ($state['portfolio_url'] ?? ''),
            'linkedinUrl' => (string) ($state['linkedin_url'] ?? ''),
            'cvPath' => (string) ($state['cvPath'] ?? ''),
            'dashboardUrl' => DashboardContextResolver::dashboardUrlForUser($user),
            'profileUrl' => $this->siteUrl('profile'),
            'notificationsUrl' => $this->siteUrl('notifications'),
            'contactEmail' => (string) ($user['email'] ?? ''),
            'contactPhone' => trim((string) preg_replace('/[^\d+]+/', '', (string) ($user['phone'] ?? ''))),
        ]);
    }
}

