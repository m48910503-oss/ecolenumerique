<?php
declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Core\Controller;
use App\Models\CourseCreateModel;
use App\Models\SharedCompanySchemaModel;
use App\Models\SharedPromotionModel;
use App\Models\SharedReferralModel;
use App\Services\Shared\CompanySchemaService;
use App\Services\Shared\PromotionsService;
use App\Services\Shared\ReferralService;
use App\Services\Teacher\CourseCreateService;

final class CourseEditController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $siteLogoUrl = $this->siteLogoUrl();

        $user = $this->requireAuth(true, $this->siteUrl('teacher/course_edit'));
        $roles = json_decode($user['roles_json'] ?? '[]', true) ?: [];
        $isAdmin = in_array('admin', $roles, true);
        if (!$isAdmin && !in_array('teacher', $roles, true)) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf();
        }

        $referrals = new ReferralService(new SharedReferralModel($pdo));
        $promotions = new PromotionsService(
            new SharedPromotionModel($pdo),
            new CompanySchemaService(new SharedCompanySchemaModel($pdo))
        );
        $promotions->ensureCoursesReferralSettingsColumns();

        $service = new CourseCreateService($pdo, new CourseCreateModel($pdo), $referrals);
        $result = $service->handleEdit(
            $user,
            $isAdmin,
            (int) ($_GET['id'] ?? 0),
            $_POST,
            $_FILES,
            (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $_SESSION
        );

        if (!empty($result['redirectTo'])) {
            header('Location: ' . (string) $result['redirectTo']);
            exit;
        }

        if (!empty($result['fatal'])) {
            exit((string) $result['fatal']);
        }

        $this->render('teacher/course_create', array_merge(
            [
                'siteLogoUrl' => $siteLogoUrl,
                'injectGlobalAssets' => false,
            ],
            is_array($result['view'] ?? null) ? $result['view'] : []
        ));
    }
}

