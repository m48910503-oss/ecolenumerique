<?php
declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Core\Controller;
use App\Models\SharedCertificateModel;
use App\Models\TeacherExamsManageModel;
use App\Services\Shared\CertificateOpsService;
use App\Services\Teacher\ExamsManageService;

final class ExamsManageController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $siteLogoUrl = $this->siteLogoUrl();
        $examId = (int) ($_GET['exam_id'] ?? 0);
        $returnPath = $this->siteUrl('teacher/exams_manage' . ($examId > 0 ? ('?exam_id=' . $examId) : ''));
        $user = $this->requireAuth(true, $returnPath);
        $roles = json_decode($user['roles_json'] ?? '[]', true) ?: [];
        $isAdmin = in_array('admin', $roles, true);
        if (!$isAdmin && !in_array('teacher', $roles, true)) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf();
        }

        $service = new ExamsManageService(
            new TeacherExamsManageModel($pdo),
            new CertificateOpsService(new SharedCertificateModel($pdo))
        );
        $result = $service->handle(
            $examId,
            $user,
            $isAdmin,
            (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $_GET,
            $_POST
        );

        if (!empty($result['redirectTo'])) {
            header('Location: ' . (string) $result['redirectTo']);
            exit;
        }

        if (is_array($result['download'] ?? null)) {
            $download = $result['download'];
            $filename = basename((string) ($download['filename'] ?? 'notes-examen.csv'));
            header('Content-Type: ' . (string) ($download['contentType'] ?? 'text/csv; charset=utf-8'));
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo "\xEF\xBB\xBF" . (string) ($download['content'] ?? '');
            exit;
        }

        if (!empty($result['httpStatus']) && !empty($result['abortMessage'])) {
            http_response_code((int) $result['httpStatus']);
            exit((string) $result['abortMessage']);
        }

        $this->render('teacher/exams_manage', array_merge(
            ['siteLogoUrl' => $siteLogoUrl],
            is_array($result['view'] ?? null) ? $result['view'] : []
        ));
    }
}

