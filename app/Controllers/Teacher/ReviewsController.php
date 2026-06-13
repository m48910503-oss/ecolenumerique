<?php
declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Core\Controller;
use App\Models\TeacherReviewsModel;
use App\Services\Shared\DashboardBadgeService;
use App\Services\Teacher\ReviewsService;

final class ReviewsController extends Controller
{
    public function __invoke(): void
    {
        $user = $this->requireAuth(true, $this->siteUrl('teacher/reviews'));
        $roles = json_decode((string) ($user['roles_json'] ?? '[]'), true) ?: [];
        $isAdmin = in_array('admin', $roles, true);
        $isTeacher = in_array('teacher', $roles, true);
        if (!$isAdmin && !$isTeacher) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        if ((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->requireCsrf();
        }

        /** @var \PDO $pdo */
        $pdo = $this->pdo();
        $service = new ReviewsService(new TeacherReviewsModel($pdo));
        $result = $service->handle(
            $user,
            array_values($roles),
            (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $_GET,
            $_POST
        );

        if (!empty($result['redirectTo'])) {
            header('Location: ' . (string) $result['redirectTo']);
            exit;
        }

        $badgeData = (new DashboardBadgeService($pdo))->build($user, 'teacher');

        $this->render('teacher/reviews', array_merge(
            [
                'siteLogoUrl' => $this->siteLogoUrl(),
                'user' => $user,
                'unreadCount' => (int) ($badgeData['unreadCount'] ?? 0),
                'supportUnreadCount' => (int) ($badgeData['supportUnreadCount'] ?? 0),
                'pendingMeetingsCount' => (int) ($badgeData['pendingMeetingsCount'] ?? 0),
                'teacherReviewsNotice' => (string) $this->flashGet('teacher_reviews_notice', ''),
            ],
            is_array($result['view'] ?? null) ? $result['view'] : []
        ));
    }
}

