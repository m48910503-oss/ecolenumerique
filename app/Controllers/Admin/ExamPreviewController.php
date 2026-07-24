<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\TeacherExamPreviewModel;
use App\Services\Admin\AdminAccessService;
use App\Services\Teacher\ExamPreviewService;

final class ExamPreviewController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $siteLogoUrl = $this->siteLogoUrl();
        $access = new AdminAccessService();

        $examId = (int) ($_GET['exam_id'] ?? 0);
        $returnPath = $this->siteUrl('admin/exam_preview' . ($examId > 0 ? ('?exam_id=' . $examId) : ''));
        $user = $this->requireAuth(true, $returnPath);
        if (!$access->canAccess($user, AdminAccessService::PERMISSION_COURSES)) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        $service = new ExamPreviewService(new TeacherExamPreviewModel($pdo));
        $result = $service->build($user, $access->rolesFromUser($user), $_GET);

        if (!empty($result['httpStatus']) && !empty($result['abortMessage'])) {
            http_response_code((int) $result['httpStatus']);
            exit((string) $result['abortMessage']);
        }

        $this->render('admin/exam_preview', array_merge(
            [
                'user' => $user,
                'siteLogoUrl' => $siteLogoUrl,
                'adminPermissions' => $access->allowedPermissions($user),
                'hasAdminAccess' => $access->hasAdminAccess($user),
                'isGeneralAdmin' => $access->isGeneralAdmin($user),
            ],
            is_array($result['view'] ?? null) ? $result['view'] : []
        ));
    }
}

