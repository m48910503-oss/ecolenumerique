<?php
declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Core\Controller;
use App\Models\TeacherExamPreviewModel;
use App\Services\Teacher\ExamPreviewService;

final class ExamPreviewController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $siteLogoUrl = $this->siteLogoUrl();

        $examId = (int) ($_GET['exam_id'] ?? 0);
        $returnPath = $this->siteUrl('teacher/exam_preview' . ($examId > 0 ? ('?exam_id=' . $examId) : ''));
        $user = $this->requireAuth(true, $returnPath);
        $roles = json_decode($user['roles_json'] ?? '[]', true) ?: [];
        $isAdmin = in_array('admin', $roles, true);
        if (!$isAdmin && !in_array('teacher', $roles, true)) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        $service = new ExamPreviewService(new TeacherExamPreviewModel($pdo));
        $result = $service->build($user, $roles, $_GET);

        if (!empty($result['httpStatus']) && !empty($result['abortMessage'])) {
            http_response_code((int) $result['httpStatus']);
            exit((string) $result['abortMessage']);
        }

        $this->render('teacher/exam_preview', array_merge(
            ['siteLogoUrl' => $siteLogoUrl],
            is_array($result['view'] ?? null) ? $result['view'] : []
        ));
    }
}

