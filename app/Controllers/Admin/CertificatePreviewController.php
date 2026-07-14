<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\SharedPlatformSettingsModel;
use App\Services\Admin\AdminAccessService;
use App\Services\Shared\PlatformSettingsService;
use App\Services\Web\CertificatePdfBuilder;

final class CertificatePreviewController extends Controller
{
    private const EXPORT_RENDER_SECRET = 'ecolenumerique-certificate-preview-export';

    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $access = new AdminAccessService();
        $isExportRender = strtolower(trim((string) ($_GET['render'] ?? ''))) === 'export';
        if ($isExportRender) {
            $renderExpires = (int) ($_GET['expires'] ?? 0);
            $renderToken = trim((string) ($_GET['token'] ?? ''));
            if (!$this->isValidExportRenderToken($renderExpires, $renderToken)) {
                http_response_code(403);
                echo 'Forbidden';
                return;
            }
            $user = [];
        } else {
            $user = $this->requireAuth(true, $this->siteUrl('admin/certificate_preview'));
            if (!$access->canAccess($user, AdminAccessService::PERMISSION_SITE_SETTINGS)) {
                header('Location: ' . $this->siteUrl(''));
                exit;
            }
        }

        $settings = new PlatformSettingsService(new SharedPlatformSettingsModel($pdo));
        $identity = $settings->publicIdentity();
        $siteName = trim((string) ($identity['siteName'] ?? 'École Numérique'));
        $siteTagline = trim((string) ($identity['siteTagline'] ?? ''));
        $siteDescription = trim((string) ($identity['siteDescription'] ?? ''));
        $supportEmail = trim((string) ($identity['supportEmail'] ?? ''));
        $supportPhone = trim((string) ($identity['supportPhone'] ?? ''));
        $supportWhatsapp = trim((string) ($identity['supportWhatsapp'] ?? ''));
        $siteAddress = trim((string) ($identity['siteAddress'] ?? ''));
        $footerText = trim((string) ($identity['footerText'] ?? ''));
        $dgName = trim($settings->get('platform_dg_name', 'Directeur général'));
        $dgSignatureUrl = $this->assetUrlFromPath($settings->get('platform_dg_signature_path', ''));
        $siteLogoUrl = trim((string) ($identity['siteLogoUrl'] ?? ''));
        if ($siteLogoUrl === '') {
            $siteLogoUrl = $this->siteLogoUrl();
        }

        $sampleCertificate = [
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
        ];

        $viewData = [
            'siteLogoUrl' => $siteLogoUrl,
            'user' => $user,
            'adminPermissions' => $access->allowedPermissions($user),
            'hasAdminAccess' => $access->hasAdminAccess($user),
            'isGeneralAdmin' => $access->isGeneralAdmin($user),
            'siteName' => $siteName !== '' ? $siteName : 'École Numérique',
            'siteTagline' => $siteTagline,
            'siteDescription' => $siteDescription,
            'supportEmail' => $supportEmail,
            'supportPhone' => $supportPhone,
            'supportWhatsapp' => $supportWhatsapp,
            'siteAddress' => $siteAddress,
            'footerText' => $footerText,
            'dgName' => $dgName !== '' ? $dgName : 'Directeur général',
            'dgSignatureUrl' => $dgSignatureUrl,
            'sampleCertificate' => $sampleCertificate,
            'certificatePrintUrl' => $this->certificatePreviewRenderUrl(),
        ];

        $exportFormat = strtolower(trim((string) ($_GET['export'] ?? '')));
        if ($exportFormat === 'pdf') {
            $filenameBase = $this->certificateExportFilename((string) $sampleCertificate['code']);
            $renderUrl = $this->certificatePreviewRenderUrl();
            $fallbackDocument = $this->buildFallbackDocument($viewData);
            $pdfBinary = (new CertificatePdfBuilder())->buildFromUrl($renderUrl, $fallbackDocument);
            if (is_string($pdfBinary) && $pdfBinary !== '') {
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . $filenameBase . '.pdf"');
                header('Content-Length: ' . strlen($pdfBinary));
                header('X-Content-Type-Options: nosniff');
                echo $pdfBinary;
                return;
            }
        }

        if ($isExportRender) {
            $this->render('admin/certificate_preview_export', $viewData + [
                'injectGlobalAssets' => false,
            ]);
            return;
        }

        $this->render('admin/certificate_preview', $viewData);
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

    /**
     * @param array<string,mixed> $sampleCertificate
     * @return array<string,mixed>
     */
    private function buildPdfCertificateViewData(
        string $siteName,
        string $siteTagline,
        string $siteDescription,
        string $supportEmail,
        string $supportPhone,
        string $supportWhatsapp,
        string $siteAddress,
        string $footerText,
        string $dgName,
        string $dgSignatureUrl,
        string $siteLogoUrl,
        array $sampleCertificate
    ): array {
        $certificateCode = trim((string) ($sampleCertificate['code'] ?? 'CERT-DEMO-2026-0001'));
        if ($certificateCode === '') {
            $certificateCode = 'CERT-DEMO-2026-0001';
        }

        $siteName = trim($siteName);
        if ($siteName === '') {
            $siteName = 'École Numérique';
        }

        $fullName = trim((string) ($sampleCertificate['studentName'] ?? 'Étudiant'));
        if ($fullName === '') {
            $fullName = 'Étudiant';
        }

        $teacherName = trim((string) ($sampleCertificate['teacherName'] ?? 'Formateur'));
        if ($teacherName === '') {
            $teacherName = 'Formateur';
        }

        $dgName = trim($dgName);
        if ($dgName === '') {
            $dgName = 'Direction générale';
        }

        $verifyUrl = $this->absUrl('certificate?code=' . urlencode($certificateCode));

        return [
            'cert' => [
                'code' => $certificateCode,
                'course_title' => (string) ($sampleCertificate['courseTitle'] ?? 'Cours'),
                'exam_title' => (string) ($sampleCertificate['examTitle'] ?? 'Examen'),
                'score' => (string) ($sampleCertificate['score'] ?? ''),
            ],
            'siteName' => $siteName,
            'siteTagline' => $siteTagline,
            'siteDescription' => $siteDescription,
            'supportEmail' => $supportEmail,
            'supportPhone' => $supportPhone,
            'supportWhatsapp' => $supportWhatsapp,
            'siteAddress' => $siteAddress,
            'footerText' => $footerText,
            'fullName' => $fullName,
            'teacherName' => $teacherName,
            'dgName' => $dgName,
            'siteLogoUrl' => $siteLogoUrl,
            'teacherSignatureUrl' => '',
            'dgSignatureUrl' => $dgSignatureUrl,
            'issuedLabel' => (string) ($sampleCertificate['issuedLabel'] ?? 'n/c'),
            'verifyUrl' => $verifyUrl,
            'qrVerifyUrl' => $verifyUrl,
            'exportFormat' => 'html',
            'isPdfExport' => false,
        ];
    }

    /**
     * @param array<string,mixed> $viewData
     * @return array<string,mixed>
     */
    private function buildFallbackDocument(array $viewData): array
    {
        $sampleCertificate = is_array($viewData['sampleCertificate'] ?? null) ? $viewData['sampleCertificate'] : [];
        $siteName = trim((string) ($viewData['siteName'] ?? 'Ecole Numerique'));
        $brandSubtitle = trim((string) ($viewData['siteTagline'] ?? ''));
        if ($brandSubtitle === '') {
            $brandSubtitle = trim((string) ($viewData['siteDescription'] ?? ''));
        }
        $supportLine = trim(implode(' - ', array_values(array_filter([
            trim((string) ($viewData['supportEmail'] ?? '')) !== '' ? trim((string) ($viewData['supportEmail'] ?? '')) : null,
            trim((string) ($viewData['supportPhone'] ?? '')) !== '' ? trim((string) ($viewData['supportPhone'] ?? '')) : null,
            trim((string) ($viewData['supportWhatsapp'] ?? '')) !== '' ? trim((string) ($viewData['supportWhatsapp'] ?? '')) : null,
        ], static fn ($value): bool => is_string($value) && trim($value) !== ''))));
        $footerNote = trim((string) ($viewData['footerText'] ?? ''));
        if ($footerNote === '') {
            $footerNote = trim((string) ($viewData['siteAddress'] ?? ''));
        }

        return [
            'siteName' => $siteName !== '' ? $siteName : 'Ecole Numerique',
            'brandSubtitle' => $brandSubtitle,
            'title' => 'Certificat de reussite',
            'note' => 'Version de secours generee cote serveur.',
            'code' => trim((string) ($sampleCertificate['code'] ?? 'CERT-DEMO-2026-0001')),
            'recipient' => trim((string) ($sampleCertificate['studentName'] ?? 'Etudiant')),
            'courseTitle' => trim((string) ($sampleCertificate['courseTitle'] ?? 'Cours')),
            'examTitle' => trim((string) ($sampleCertificate['examTitle'] ?? 'Examen')),
            'scoreLabel' => trim((string) ($sampleCertificate['score'] ?? '')),
            'hoursLabel' => trim((string) ($sampleCertificate['hours'] ?? '')),
            'issuedLabel' => trim((string) ($sampleCertificate['issuedLabel'] ?? 'n/c')),
            'teacherName' => trim((string) ($sampleCertificate['teacherName'] ?? 'Formateur')),
            'directorName' => trim((string) ($viewData['dgName'] ?? 'Direction generale')),
            'verifyUrl' => trim((string) ($sampleCertificate['verifyUrl'] ?? '')),
            'supportLine' => $supportLine,
            'footerNote' => $footerNote,
        ];
    }

    private function certificatePreviewRenderExpires(): int
    {
        return time() + 3600;
    }

    private function certificatePreviewRenderUrl(): string
    {
        $expires = $this->certificatePreviewRenderExpires();

        return $this->absUrl('admin/certificate_preview?render=export&expires='
            . $expires
            . '&token='
            . rawurlencode($this->certificatePreviewRenderToken($expires)));
    }

    private function certificatePreviewRenderToken(int $expires): string
    {
        $payload = 'certificate-preview-export|landscape|' . $expires;
        return hash_hmac('sha256', $payload, self::EXPORT_RENDER_SECRET);
    }

    private function isValidExportRenderToken(int $expires, string $token): bool
    {
        if ($expires < time() || $token === '') {
            return false;
        }

        $payload = 'certificate-preview-export|landscape|' . $expires;
        $expected = hash_hmac('sha256', $payload, self::EXPORT_RENDER_SECRET);

        return hash_equals($expected, $token);
    }

    private function renderViewToString(string $view, array $data): string
    {
        ob_start();
        $this->render($view, $data);
        return (string) ob_get_clean();
    }

    private function injectAbsoluteBaseHref(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return $html;
        }

        if (stripos($html, '<base ') !== false) {
            return $html;
        }

        $baseHref = rtrim($this->absUrl(''), '/') . '/';
        $baseTag = '<base href="' . htmlspecialchars($baseHref, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';

        $injected = preg_replace('~<head([^>]*)>~i', '<head$1>' . $baseTag, $html, 1);
        if (is_string($injected) && $injected !== '') {
            return $injected;
        }

        return $html;
    }

    private function certificateExportFilename(string $code): string
    {
        $safe = strtolower(trim((string) preg_replace('/[^A-Za-z0-9_-]+/', '-', $code)));
        $safe = trim($safe, '-');
        if ($safe === '') {
            $safe = 'certificat-demo';
        }

        return 'exemplaire-certificat-' . $safe;
    }
}
