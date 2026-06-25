<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\SharedPlatformSettingsModel;
use App\Services\Admin\AdminAccessService;
use App\Services\Web\BrowserScreenshotService;
use App\Services\Shared\PlatformSettingsService;
use App\Services\Web\CertificateDocxBuilder;
use App\Services\Web\ReceiptPdfDocumentBuilder;

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
        ];

        $exportFormat = strtolower(trim((string) ($_GET['export'] ?? '')));
        if (in_array($exportFormat, ['pdf', 'word', 'docx'], true)) {
            $filenameBase = $this->certificateExportFilename((string) $sampleCertificate['code']);
            $renderExpires = $this->certificatePreviewRenderExpires();
            $renderToken = $this->certificatePreviewRenderToken($renderExpires);
            $screenshotUrl = $this->siteUrl(
                'admin/certificate_preview?render=export'
                . '&expires=' . (string) $renderExpires
                . '&token=' . urlencode($renderToken)
            );
            $screenshot = (new BrowserScreenshotService())->captureUrl($screenshotUrl);
            if (!is_string($screenshot) || $screenshot === '') {
                $exportViewData = $viewData;
                $exportHtml = $this->renderViewToString('admin/certificate_preview_export', $exportViewData + [
                    'injectGlobalAssets' => false,
                ]);
                $screenshot = (new BrowserScreenshotService())->captureHtml($exportHtml);
            }
            if (is_string($screenshot) && $screenshot !== '') {
                if ($exportFormat === 'pdf') {
                    $pdfBinary = (new ReceiptPdfDocumentBuilder())->buildFromHtml($this->buildPdfWrapperHtml($screenshot));
                    if (is_string($pdfBinary) && $pdfBinary !== '') {
                        header('Content-Type: application/pdf');
                        header('Content-Disposition: attachment; filename="' . $filenameBase . '.pdf"');
                        header('Content-Length: ' . strlen($pdfBinary));
                        header('X-Content-Type-Options: nosniff');
                        echo $pdfBinary;
                        return;
                    }
                } else {
                    $docxBinary = (new CertificateDocxBuilder())->buildFromImage($screenshot);
                    if (is_string($docxBinary) && $docxBinary !== '') {
                        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
                        header('Content-Disposition: attachment; filename="' . $filenameBase . '.docx"');
                        header('X-Content-Type-Options: nosniff');
                        echo $docxBinary;
                        return;
                    }
                }
            }

            if ($exportFormat === 'pdf') {
                $html = $this->renderViewToString('admin/certificate_preview_export', $viewData + [
                    'injectGlobalAssets' => false,
                ]);
                $pdfBinary = (new ReceiptPdfDocumentBuilder())->buildFromHtml($html);
                if (is_string($pdfBinary) && $pdfBinary !== '') {
                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment; filename="' . $filenameBase . '.pdf"');
                    header('Content-Length: ' . strlen($pdfBinary));
                    header('X-Content-Type-Options: nosniff');
                    echo $pdfBinary;
                    return;
                }
            } else {
                $docxBinary = (new CertificateDocxBuilder())->build($this->buildDocxView(
                    $siteName !== '' ? $siteName : 'École Numérique',
                    $siteTagline,
                    $siteDescription,
                    $supportEmail,
                    $supportPhone,
                    $supportWhatsapp,
                    $footerText,
                    $dgName !== '' ? $dgName : 'Directeur général',
                    $dgSignatureUrl,
                    $siteLogoUrl,
                    $sampleCertificate
                ));
                if (is_string($docxBinary) && $docxBinary !== '') {
                    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
                    header('Content-Disposition: attachment; filename="' . $filenameBase . '.docx"');
                    header('X-Content-Type-Options: nosniff');
                    echo $docxBinary;
                    return;
                }
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
    private function buildDocxView(
        string $siteName,
        string $siteTagline,
        string $siteDescription,
        string $supportEmail,
        string $supportPhone,
        string $supportWhatsapp,
        string $footerText,
        string $dgName,
        string $dgSignatureUrl,
        string $siteLogoUrl,
        array $sampleCertificate
    ): array {
        return [
            'cert' => [
                'course_title' => (string) ($sampleCertificate['courseTitle'] ?? 'Cours'),
                'exam_title' => (string) ($sampleCertificate['examTitle'] ?? 'Examen'),
                'score' => (string) ($sampleCertificate['score'] ?? ''),
                'code' => (string) ($sampleCertificate['code'] ?? 'n/c'),
            ],
            'siteName' => $siteName,
            'siteTagline' => $siteTagline,
            'siteDescription' => $siteDescription,
            'supportEmail' => $supportEmail,
            'supportPhone' => $supportPhone,
            'supportWhatsapp' => $supportWhatsapp,
            'footerText' => $footerText,
            'fullName' => (string) ($sampleCertificate['studentName'] ?? 'Étudiant'),
            'teacherName' => (string) ($sampleCertificate['teacherName'] ?? 'Formateur'),
            'dgName' => $dgName,
            'siteLogoUrl' => $siteLogoUrl,
            'teacherSignatureUrl' => '',
            'dgSignatureUrl' => $dgSignatureUrl,
            'issuedLabel' => (string) ($sampleCertificate['issuedLabel'] ?? 'n/c'),
            'verifyUrl' => (string) ($sampleCertificate['verifyUrl'] ?? ''),
        ];
    }

    private function buildPdfWrapperHtml(string $imageBinary): string
    {
        $dataUri = 'data:image/png;base64,' . base64_encode($imageBinary);

        return '<!doctype html><html lang="fr"><head><meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
            . '<style>'
            . '@page{size:A4 landscape;margin:0;}'
            . 'html,body{margin:0;padding:0;width:100%;height:100%;overflow:hidden;background:#0f172a;print-color-adjust:exact;-webkit-print-color-adjust:exact;}'
            . 'body{display:flex;align-items:center;justify-content:center;}'
            . 'img{display:block;width:100vw;height:100vh;object-fit:contain;}'
            . '</style>'
            . '</head><body><img alt="Certificat" src="' . $dataUri . '"></body></html>';
    }

    private function certificatePreviewRenderExpires(): int
    {
        return time() + 120;
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
