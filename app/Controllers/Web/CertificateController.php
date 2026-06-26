<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Models\SharedCertificateModel;
use App\Models\SharedPlatformSettingsModel;
use App\Models\WebCertificateModel;
use App\Services\Shared\CertificateOpsService;
use App\Services\Shared\PlatformSettingsService;
use App\Services\Web\CertificatePdfBuilder;
use App\Services\Web\CertificateService;

final class CertificateController extends Controller
{
    public function __invoke(): void
    {
        $format = strtolower(trim((string) ($_GET['format'] ?? 'html')));
        if (!in_array($format, ['html', 'pdf'], true)) {
            $format = 'html';
        }

        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $service = new CertificateService(
            new WebCertificateModel($pdo),
            new PlatformSettingsService(new SharedPlatformSettingsModel($pdo)),
            new CertificateOpsService(new SharedCertificateModel($pdo))
        );
        $result = $service->handle(trim((string) ($_GET['code'] ?? '')), $format);

        if (!empty($result['abortMessage'])) {
            http_response_code((int) ($result['httpStatus'] ?? 404));
            $this->render('web/public_error', $this->publicErrorViewData(
                (string) ($result['abortTitle'] ?? 'Certificat indisponible'),
                (string) $result['abortMessage']
            ));
            return;
        }

        $cert = is_array($result['view']['cert'] ?? null) ? $result['view']['cert'] : [];
        $rawCode = trim((string) ($cert['code'] ?? 'certificate'));
        if ($rawCode === '') {
            $rawCode = 'certificate';
        }
        $safeCode = preg_replace('/[^A-Za-z0-9_-]+/', '-', $rawCode) ?: 'certificate';
        $filenameBase = 'certificat-' . strtolower(trim($safeCode, '-'));

        if ($format === 'pdf') {
            $htmlSourceUrl = $this->absUrl('certificate?code=' . urlencode($rawCode) . '&format=html&pdf_render=1');
            $pdfBinary = (new CertificatePdfBuilder())->buildFromUrl($htmlSourceUrl);
            if (is_string($pdfBinary) && $pdfBinary !== '') {
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . $filenameBase . '.pdf"');
                header('Content-Length: ' . strlen($pdfBinary));
                header('X-Content-Type-Options: nosniff');
                echo $pdfBinary;
                return;
            }
        }

        $this->render('web/certificate', $result['view']);
    }
}
