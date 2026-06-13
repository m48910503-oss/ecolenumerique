<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\SharedPlatformSettingsModel;
use App\Services\Admin\AdminAccessService;
use App\Services\Shared\PlatformSettingsService;

final class CertificatePreviewController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $user = $this->requireAuth(true, $this->siteUrl('admin/certificate_preview'));
        $access = new AdminAccessService();
        if (!$access->canAccess($user, AdminAccessService::PERMISSION_SITE_SETTINGS)) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        $settings = new PlatformSettingsService(new SharedPlatformSettingsModel($pdo));
        $siteName = trim($settings->get('site_name', 'École Numérique'));
        $dgName = trim($settings->get('platform_dg_name', 'Directeur général'));
        $dgSignatureUrl = $this->assetUrlFromPath($settings->get('platform_dg_signature_path', ''));
        $siteLogoUrl = $this->siteLogoUrl();

        $this->render('admin/certificate_preview', [
            'siteLogoUrl' => $siteLogoUrl,
            'user' => $user,
            'adminPermissions' => $access->allowedPermissions($user),
            'hasAdminAccess' => $access->hasAdminAccess($user),
            'isGeneralAdmin' => $access->isGeneralAdmin($user),
            'siteName' => $siteName !== '' ? $siteName : 'École Numérique',
            'dgName' => $dgName !== '' ? $dgName : 'Directeur général',
            'dgSignatureUrl' => $dgSignatureUrl,
            'sampleCertificate' => [
                'studentName' => 'Aïcha Mensah',
                'courseTitle' => 'Développement Web 2026',
                'examTitle' => 'Évaluation finale',
                'teacherName' => 'Michaël Ouwade',
                'score' => '94% (Excellent)',
                'hours' => '42 h',
                'code' => 'CERT-DEMO-2026-0001',
                'issuedLabel' => date('d/m/Y'),
                'verifyUrl' => $this->absUrl('certificate?code=CERT-DEMO-2026-0001'),
                'description' => 'Une formation intensive couvrant HTML5, CSS3, JavaScript moderne, React et Node.js, validée par des projets pratiques et une évaluation finale.',
            ],
        ]);
    }

    private function assetUrlFromPath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }
        if (preg_match('/^https?:\/\//i', $path) === 1) {
            return $path;
        }

        return $this->siteUrl(ltrim($path, '/'));
    }
}
