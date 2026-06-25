<?php
declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Core\Controller;
use App\Models\CourseCreateModel;
use App\Models\SharedCertificateModel;
use App\Services\Shared\CertificateOpsService;
use App\Services\Teacher\CourseOnboardingService;

final class CourseCreateController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $siteLogoUrl = $this->siteLogoUrl();
        $user = $this->requireAuth(true, $this->siteUrl('teacher/course_create'));
        $roles = json_decode($user['roles_json'] ?? '[]', true) ?: [];
        $isTeacher = in_array('teacher', $roles, true);
        if (!in_array('teacher', $roles, true) && !in_array('admin', $roles, true)) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        $signatureOps = new CertificateOpsService(new SharedCertificateModel($pdo));
        $signatureRequired = $isTeacher && $signatureOps->ensureUsersSignatureColumn();
        $signaturePath = trim((string) ($user['signature_path'] ?? ''));
        if ($signatureRequired && $signaturePath === '') {
            $this->flashSet(
                'profile_message',
                'Ajoutez d abord votre signature dans le profil pour activer la creation de cours.'
            );
            header('Location: ' . $this->siteUrl('profile?space=teacher#teacher-signature-section'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf();
        }

        $service = new CourseOnboardingService($pdo, new CourseCreateModel($pdo));
        $result = $service->handle($user, (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'), $_POST);

        if (!empty($result['redirectTo'])) {
            header('Location: ' . (string) $result['redirectTo']);
            exit;
        }

        $this->render('teacher/course_onboarding', array_merge(
            [
                'siteLogoUrl' => $siteLogoUrl,
                'injectGlobalAssets' => false,
            ],
            is_array($result['view'] ?? null) ? $result['view'] : []
        ));
    }
}

