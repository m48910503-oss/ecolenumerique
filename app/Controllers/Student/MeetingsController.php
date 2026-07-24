<?php
declare(strict_types=1);

namespace App\Controllers\Student;

use App\Core\Controller;
use App\Models\MeetingsModel;
use App\Models\StudentDashboardModel;
use App\Services\Student\MeetingsService;

final class MeetingsController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $siteLogoUrl = $this->siteLogoUrl();
        $user = $this->requireAuth(true, $this->siteUrl('student/meetings'));
        $this->disableCache();

        $service = new MeetingsService(new MeetingsModel($pdo));
        $data = $service->buildPageData((int) $user['id'], $_GET);
        $badges = new StudentDashboardModel($pdo);
        $unreadCount = 0;
        $supportUnreadCount = 0;
        $supportUnreadMessages = 0;
        $supportUnreadNotifications = 0;
        $activeEnrollments = 0;
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
        try {
            $activeEnrollments = $badges->countActiveEnrollments((int) $user['id']);
        } catch (\Throwable $e) {
            $activeEnrollments = 0;
        }

        $this->render('student/meetings', array_merge(
            [
                'siteLogoUrl' => $siteLogoUrl,
                'user' => $user,
                'unreadCount' => $unreadCount,
                'supportUnreadCount' => $supportUnreadCount,
                'activeEnrollments' => $activeEnrollments,
            ],
            $data
        ));
    }
}

