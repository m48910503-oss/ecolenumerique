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
        $fallbackDocument = $this->buildFallbackDocument($result['view']);

        if ($format === 'pdf') {
            $htmlSourceUrl = $this->absUrl('certificate?code=' . urlencode($rawCode) . '&format=html&pdf_render=1');
            $pdfBinary = (new CertificatePdfBuilder())->buildFromUrl($htmlSourceUrl, $fallbackDocument);
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

    /**
     * @param array<string,mixed> $view
     * @return array<string,mixed>
     */
    private function buildFallbackDocument(array $view): array
    {
        $cert = is_array($view['cert'] ?? null) ? $view['cert'] : [];
        $siteName = trim((string) ($view['siteName'] ?? 'École Numérique'));
        $brandSubtitle = trim((string) ($view['siteTagline'] ?? ''));
        if ($brandSubtitle === '') {
            $brandSubtitle = trim((string) ($view['siteDescription'] ?? ''));
        }
        $code = trim((string) ($cert['code'] ?? 'n/c'));
        $recipient = trim((string) ($view['fullName'] ?? 'Étudiant'));
        $courseTitle = trim((string) ($cert['course_title'] ?? 'Cours'));
        $examTitle = trim((string) ($cert['exam_title'] ?? 'Examen'));
        $scoreValue = $cert['score'] ?? '';
        $scoreLabel = is_numeric($scoreValue) ? ((string) ((int) $scoreValue) . '/100') : trim((string) $scoreValue);
        $hoursLabel = trim((string) ($view['hoursLabel'] ?? ''));
        $issuedLabel = trim((string) ($view['issuedLabel'] ?? 'n/c'));
        $teacherName = trim((string) ($view['teacherName'] ?? 'Formateur'));
        $directorName = trim((string) ($view['dgName'] ?? 'Direction générale'));
        $verifyUrl = trim((string) ($view['verifyUrl'] ?? ''));
        $supportLine = trim(implode(' · ', array_values(array_filter([
            trim((string) ($view['supportEmail'] ?? '')) !== '' ? trim((string) ($view['supportEmail'] ?? '')) : null,
            trim((string) ($view['supportPhone'] ?? '')) !== '' ? trim((string) ($view['supportPhone'] ?? '')) : null,
            trim((string) ($view['supportWhatsapp'] ?? '')) !== '' ? trim((string) ($view['supportWhatsapp'] ?? '')) : null,
        ], static fn ($value): bool => is_string($value) && trim($value) !== ''))));
        $footerNote = trim((string) ($view['footerText'] ?? ''));
        if ($footerNote === '') {
            $footerNote = trim((string) ($view['siteAddress'] ?? ''));
        }

        return [
            'siteName' => $siteName !== '' ? $siteName : 'École Numérique',
            'brandSubtitle' => $brandSubtitle,
            'title' => 'Certificat de réussite',
            'note' => 'Version de secours générée côté serveur.',
            'code' => $code !== '' ? $code : 'n/c',
            'recipient' => $recipient !== '' ? $recipient : 'Étudiant',
            'courseTitle' => $courseTitle !== '' ? $courseTitle : 'Cours',
            'examTitle' => $examTitle !== '' ? $examTitle : 'Examen',
            'scoreLabel' => $scoreLabel,
            'hoursLabel' => $hoursLabel,
            'issuedLabel' => $issuedLabel !== '' ? $issuedLabel : 'n/c',
            'teacherName' => $teacherName !== '' ? $teacherName : 'Formateur',
            'directorName' => $directorName !== '' ? $directorName : 'Direction générale',
            'verifyUrl' => $verifyUrl,
            'supportLine' => $supportLine,
            'footerNote' => $footerNote,
        ];
    }
}
