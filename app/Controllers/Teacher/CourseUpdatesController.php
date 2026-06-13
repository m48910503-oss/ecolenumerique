<?php
declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Core\Controller;
use App\Models\TeacherCourseUpdatesModel;
use App\Services\Teacher\CourseUpdatesService;

final class CourseUpdatesController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $siteLogoUrl = $this->siteLogoUrl();
        $user = $this->requireAuth(true, $this->siteUrl('teacher/course_updates'));
        $roles = json_decode($user['roles_json'] ?? '[]', true) ?: [];
        $isAdmin = in_array('admin', $roles, true);
        if (!$isAdmin && !in_array('teacher', $roles, true)) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        $service = new CourseUpdatesService(new TeacherCourseUpdatesModel($pdo));
        $result = $service->build($user, $isAdmin);

        $this->render('teacher/course_updates', array_merge(
            ['siteLogoUrl' => $siteLogoUrl],
            is_array($result['view'] ?? null) ? $result['view'] : []
        ));
    }
}

