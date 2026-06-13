<?php
declare(strict_types=1);

namespace App\Services\Shared;

use App\Core\DashboardContextResolver;
use App\Models\StudentDashboardModel;
use App\Models\TeacherDashboardModel;

final class DashboardBadgeService
{ 
    private StudentDashboardModel $studentModel;
    private TeacherDashboardModel $teacherModel;

    public function __construct(\PDO $pdo)
    {
        $this->studentModel = new StudentDashboardModel($pdo);
        $this->teacherModel = new TeacherDashboardModel($pdo);
    }

    /**
     * @param array<string,mixed> $user
     * @return array{space:?string,unreadCount:int,supportUnreadCount:int,pendingMeetingsCount:int,pendingExamCount:int}
     */
    public function build(array $user, ?string $requestedSpace = null): array
    {
        $roles = DashboardContextResolver::rolesFromUser($user);
        $space = DashboardContextResolver::resolveSpaceForRoles($roles, $requestedSpace);
        $userId = (int) ($user['id'] ?? 0);
        if ($userId < 1 || $space === null) {
            return [
                'space' => null,
                'unreadCount' => 0,
                'supportUnreadCount' => 0,
                'pendingMeetingsCount' => 0,
                'pendingExamCount' => 0,
            ];
        }

        if ($space === 'teacher') {
            return [
                'space' => 'teacher',
                'unreadCount' => $this->safeCount(fn () => $this->teacherModel->countUnreadNotifications($userId)),
                'supportUnreadCount' => $this->maxSupportCount(
                    fn () => $this->teacherModel->countUnreadSupportReplies($userId),
                    fn () => $this->teacherModel->countUnreadSupportNotifications($userId)
                ),
                'pendingMeetingsCount' => $this->safeCount(fn () => $this->teacherModel->countPendingMeetings($userId)),
                'pendingExamCount' => $this->safeCount(fn () => $this->teacherModel->countPendingExamAttempts($userId)),
            ];
        }

        if ($space === 'student') {
            return [
                'space' => 'student',
                'unreadCount' => $this->safeCount(fn () => $this->studentModel->countUnreadNotifications($userId)),
                'supportUnreadCount' => $this->maxSupportCount(
                    fn () => $this->studentModel->countUnreadSupportReplies($userId),
                    fn () => $this->studentModel->countUnreadSupportNotifications($userId)
                ),
                'pendingMeetingsCount' => 0,
                'pendingExamCount' => 0,
            ];
        }

        return [
            'space' => $space,
            'unreadCount' => 0,
            'supportUnreadCount' => 0,
            'pendingMeetingsCount' => 0,
            'pendingExamCount' => 0,
        ];
    }

    private function safeCount(callable $resolver): int
    {
        try {
            return max(0, (int) $resolver());
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function maxSupportCount(callable $messagesResolver, callable $notificationsResolver): int
    {
        $messages = $this->safeCount($messagesResolver);
        $notifications = $this->safeCount($notificationsResolver);
        return max($messages, $notifications);
    }
}
