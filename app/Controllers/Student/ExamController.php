<?php
declare(strict_types=1);

namespace App\Controllers\Student;

use App\Core\Controller;
use App\Models\ExamModel;
use App\Models\SharedCertificateModel;
use App\Models\StudentDashboardModel;
use App\Services\Shared\CertificateOpsService;
use App\Services\Student\ExamService;

final class ExamController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $siteLogoUrl = $this->siteLogoUrl();
        $examId = (int) ($_GET['exam_id'] ?? 0);
        $returnPath = $this->siteUrl('student/exam?exam_id=' . $examId);
        $user = $this->requireAuth(true, $returnPath);
        $this->disableCache();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf();
        }

        $service = new ExamService(
            $pdo,
            new ExamModel($pdo),
            new CertificateOpsService(new SharedCertificateModel($pdo))
        );
        $result = $service->handle(
            $examId,
            $user,
            (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $_POST,
            $_FILES
        );

        if (!empty($result['redirectTo'])) {
            header('Location: ' . (string) $result['redirectTo']);
            exit;
        }

        if (!empty($result['httpStatus']) && !empty($result['abortMessage'])) {
            http_response_code((int) $result['httpStatus']);
            exit((string) $result['abortMessage']);
        }

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

        $this->render('student/exam', array_merge(
            [
                'siteLogoUrl' => $siteLogoUrl,
                'user' => $user,
                'unreadCount' => $unreadCount,
                'supportUnreadCount' => $supportUnreadCount,
                'activeEnrollments' => $activeEnrollments,
            ],
            is_array($result['view'] ?? null) ? $result['view'] : []
        ));
    }
}

