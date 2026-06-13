<?php
declare(strict_types=1);

namespace App\Controllers\Student;

use App\Core\Controller;
use App\Models\SharedCertificateModel;
use App\Models\StudentDashboardModel;
use App\Models\StudentCertificatesModel;
use App\Services\Shared\CertificateOpsService;
use App\Services\Student\CertificatesService;

final class CertificatesController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $siteLogoUrl = $this->siteLogoUrl();
        $user = $this->requireAuth(true, $this->siteUrl('student/certificates'));
        $this->disableCache();
        $roles = json_decode($user['roles_json'] ?? '[]', true) ?: [];
        if (!in_array('student', $roles, true) && !in_array('teacher', $roles, true) && !in_array('admin', $roles, true)) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        $service = new CertificatesService(
            new StudentCertificatesModel($pdo),
            new CertificateOpsService(new SharedCertificateModel($pdo))
        );
        $data = $service->buildPageData((int) $user['id'], $roles, $_GET);
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

        $this->render('student/certificates', array_merge(
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
