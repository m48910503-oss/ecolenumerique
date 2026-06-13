<?php
declare(strict_types=1);

namespace App\Controllers\Student;

use App\Core\Controller;
use App\Models\ApiCatalogModel;
use App\Models\StudentDashboardModel;
use App\Services\Student\FavoritesService;

final class FavoritesController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $siteLogoUrl = $this->siteLogoUrl();
        $user = $this->requireAuth(true, $this->siteUrl('student/favorites'));
        $this->disableCache();
        $roles = json_decode($user['roles_json'] ?? '[]', true) ?: [];
        if (!in_array('student', $roles, true) && !in_array('teacher', $roles, true) && !in_array('admin', $roles, true)) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        $method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($method === 'POST') {
            $this->requireCsrf();
        }

        $service = new FavoritesService(new ApiCatalogModel($pdo));
        $data = $service->buildPageData((int) $user['id'], $roles, $method, $_POST, $_GET);

        $badges = new StudentDashboardModel($pdo);
        $unreadCount = 0;
        $supportUnreadCount = 0;
        $supportUnreadMessages = 0;
        $supportUnreadNotifications = 0;
        $activeEnrollments = 0;
        try {
            $unreadCount = $badges->countUnreadNotifications((int) ($user['id'] ?? 0));
        } catch (\Throwable $e) {
            $unreadCount = 0;
        }
        try {
            $supportUnreadMessages = $badges->countUnreadSupportReplies((int) ($user['id'] ?? 0));
        } catch (\Throwable $e) {
            $supportUnreadMessages = 0;
        }
        try {
            $supportUnreadNotifications = $badges->countUnreadSupportNotifications((int) ($user['id'] ?? 0));
        } catch (\Throwable $e) {
            $supportUnreadNotifications = 0;
        }
        $supportUnreadCount = max($supportUnreadMessages, $supportUnreadNotifications);
        try {
            $activeEnrollments = $badges->countActiveEnrollments((int) ($user['id'] ?? 0));
        } catch (\Throwable $e) {
            $activeEnrollments = 0;
        }

        $this->render('student/favorites', array_merge(
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
