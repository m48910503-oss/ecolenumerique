<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Models\WebHomeModel;
use App\Services\Web\HomeService;

final class BecomeTeacherController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $homeModel = new WebHomeModel($pdo);
        $service = new HomeService($homeModel);
        $currentUser = $this->currentUser();
        $currentRoles = is_array($currentUser) ? (json_decode((string) ($currentUser['roles_json'] ?? '[]'), true) ?: []) : [];
        $primaryCtaUrl = $this->siteUrl('teacher/apply');
        $primaryCtaLabel = 'Remplir le formulaire';

        if (in_array('teacher', $currentRoles, true)) {
            $primaryCtaUrl = $this->siteUrl('teacher/dashboard');
            $primaryCtaLabel = 'Ouvrir mon espace enseignant';
        }

        $viewData = $service->buildViewData(12, 'become-teacher');
        $viewData['teacherLandingStats'] = [
            'publishedCourses' => $homeModel->countPublishedPublicCourses(),
            'activeEnrollments' => $homeModel->countActivePublicEnrollments(),
            'activeTeachers' => $homeModel->countPublicTeachers(),
            'categories' => $homeModel->countPublicCategories(),
        ];
        $viewData['teacherApplyFormUrl'] = $this->siteUrl('teacher/apply');
        $viewData['teacherApplyUrl'] = $this->siteUrl('become-teacher');
        $viewData['teacherLandingPrimaryUrl'] = $primaryCtaUrl;
        $viewData['teacherLandingPrimaryLabel'] = $primaryCtaLabel;
        $viewData['teacherLandingSecondaryUrl'] = $this->siteUrl('catalogue');

        $this->render('web/become_teacher', $viewData);
    }
}
