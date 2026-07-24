<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\AdminTeacherApplicationsModel;
use App\Services\Admin\AdminAccessService;

final class TeacherDossierController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $user = $this->requireAuth(true, $this->siteUrl('admin/teacher_dossier'));
        $access = new AdminAccessService();
        if (!$access->canAccess($user, AdminAccessService::PERMISSION_TEACHER_APPLICATIONS)) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        $teacherId = max(0, (int) ($_GET['teacher_id'] ?? 0));
        $searchQuery = trim((string) ($_GET['q'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $model = new AdminTeacherApplicationsModel($pdo);
        $teacher = $teacherId > 0 ? $model->findTeacherDirectoryById($teacherId) : null;

        $backQuery = ['tab' => 'teachers'];
        if ($searchQuery !== '') {
            $backQuery['q'] = $searchQuery;
        }
        if ($page > 1) {
            $backQuery['page'] = $page;
        }
        if ($teacherId > 0) {
            $backQuery['teacher_id'] = $teacherId;
        }

        $this->render('admin/teacher_dossier', [
            'siteLogoUrl' => $this->siteLogoUrl(),
            'user' => $user,
            'adminPermissions' => $access->allowedPermissions($user),
            'hasAdminAccess' => $access->hasAdminAccess($user),
            'isGeneralAdmin' => $access->isGeneralAdmin($user),
            'teacher' => $teacher,
            'teacherId' => $teacherId,
            'teacherBackUrl' => $this->siteUrl('admin/teacher_applications?' . http_build_query($backQuery)),
            'teacherCoursesUrl' => $teacherId > 0 ? $this->siteUrl('admin/courses?tab=approved&teacher_id=' . $teacherId) : $this->siteUrl('admin/courses'),
            'teacherStatsUrl' => $teacherId > 0 ? $this->siteUrl('admin/course_insights?teacher_id=' . $teacherId) : $this->siteUrl('admin/course_insights'),
            'searchQuery' => $searchQuery,
        ]);
    }
}
