<?php
declare(strict_types=1);

namespace App\Controllers\Student;

use App\Core\Controller;
use App\Models\EnrollmentModel;
use App\Models\StudentDashboardModel;
use App\Services\Student\MyCoursesService;

final class MyCoursesController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $siteLogoUrl = $this->siteLogoUrl();
        $user = $this->requireAuth(true, $this->siteUrl('student/my_courses'));
        $this->disableCache();
        $roles = json_decode($user['roles_json'] ?? '[]', true) ?: [];
        if (!in_array('student', $roles, true) && !in_array('teacher', $roles, true) && !in_array('admin', $roles, true)) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        $service = new MyCoursesService(new EnrollmentModel($pdo));
        $data = $service->buildPageData((int) $user['id'], $roles, $_GET);
        $badges = new StudentDashboardModel($pdo);
        $unreadCount = 0;
        $supportUnreadCount = 0;
        $supportUnreadMessages = 0;
        $supportUnreadNotifications = 0;
        try {
            $unreadCount = $badges->countUnreadNotifications((int) $user['id']);
        } catch (\Throwable $e) {
            $unreadCount = 0;
        }
        try {
            $supportUnreadMessages = $badges->countUnreadSupportReplies((int) $user['id']);
        } catch (\Throwable $e) {
            $supportUnreadMessages = 0;
        }
        try {
            $supportUnreadNotifications = $badges->countUnreadSupportNotifications((int) $user['id']);
        } catch (\Throwable $e) {
            $supportUnreadNotifications = 0;
        }
        $supportUnreadCount = max($supportUnreadMessages, $supportUnreadNotifications);

        $this->render('student/my_courses', [
            'siteLogoUrl' => $siteLogoUrl,
            'user' => $user,
            'unreadCount' => $unreadCount,
            'supportUnreadCount' => $supportUnreadCount,
            'activeEnrollments' => (int) ($data['enrollmentsCount'] ?? 0),
            'enrollments' => $data['enrollments'],
            'enrollmentsCount' => (int) ($data['enrollmentsCount'] ?? 0),
            'coursesPagination' => $data['coursesPagination'] ?? [],
            'attemptedExams' => $data['attemptedExams'],
            'attemptedExamsCount' => (int) ($data['attemptedExamsCount'] ?? 0),
            'examsPagination' => $data['examsPagination'] ?? [],
            'levelLabels' => $data['levelLabels'],
            'dashboardUrl' => $data['dashboardUrl'],
        ]);
    }
}

