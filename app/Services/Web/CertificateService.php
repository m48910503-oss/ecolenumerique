<?php
declare(strict_types=1);

namespace App\Services\Web;

use App\Core\Service;

use App\Models\WebCertificateModel;
use App\Services\Shared\CertificateOpsService;
use App\Services\Shared\PlatformSettingsService;

final class CertificateService extends Service
{
    private WebCertificateModel $model;
    private PlatformSettingsService $settings;
    private CertificateOpsService $certificates;

    public function __construct(
        WebCertificateModel $model,
        PlatformSettingsService $settings,
        CertificateOpsService $certificates
    )
    {
        $this->model = $model;
        $this->settings = $settings;
        $this->certificates = $certificates;
    }

    /**
     * @return array{httpStatus:?int,abortTitle:?string,abortMessage:?string,view:array<string,mixed>}
     */
    public function handle(string $code, string $format = 'html'): array
    {
        if ($code === '') {
            return $this->abort(
                404,
                'Certificat introuvable',
                "Le lien du certificat est incomplet ou le code demande n'existe pas."
            );
        }
        if (!$this->certificates->hasCertificatesTable()) {
            return $this->abort(
                404,
                'Certificat indisponible',
                "Le service de verification des certificats n'est pas disponible pour le moment."
            );
        }

        $hasSignatureColumn = $this->certificates->ensureUsersSignatureColumn();
        $cert = $this->model->findIssuedByCode($code, $hasSignatureColumn);
        if (!$cert || (string) ($cert['status'] ?? '') !== 'issued') {
            return $this->abort(
                404,
                'Certificat introuvable',
                "Aucun certificat valide ne correspond a ce code de verification."
            );
        }

        $fullName = trim((string) ($cert['certificate_display_name'] ?? ''));
        if ($fullName === '') {
            $fullName = trim((string) ($cert['first_name'] ?? '') . ' ' . (string) ($cert['last_name'] ?? ''));
        }
        $teacherName = trim((string) ($cert['teacher_first_name'] ?? '') . ' ' . (string) ($cert['teacher_last_name'] ?? ''));
        if ($teacherName === '') {
            $teacherName = 'Formateur';
        }

        $siteLogoUrl = $this->siteLogoUrl();
        $teacherSignaturePath = $hasSignatureColumn ? (string) ($cert['teacher_signature_path'] ?? '') : '';
        $teacherSignatureUrl = $this->toAssetUrl($teacherSignaturePath);
        $dgSignaturePath = trim($this->settings->get('platform_dg_signature_path', ''));
        $dgSignatureUrl = $this->toAssetUrl($dgSignaturePath);
        $dgName = trim($this->settings->get('platform_dg_name', 'Directeur general'));

        $issuedAt = (string) ($cert['issued_at'] ?? '');
        $issuedLabel = '';
        if ($issuedAt !== '') {
            $timestamp = strtotime($issuedAt);
            $issuedLabel = $timestamp ? date('d/m/Y', $timestamp) : $issuedAt;
        }

        $verifyUrl = $this->absUrl('certificate?code=' . urlencode((string) ($cert['code'] ?? '')));
        $qrVerifyUrl = '';
        if (!in_array($format, ['html', 'pdf', 'word'], true)) {
            $format = 'html';
        }

        return [
            'httpStatus' => null,
            'abortTitle' => null,
            'abortMessage' => null,
            'view' => [
                'cert' => $cert,
                'fullName' => $fullName,
                'teacherName' => $teacherName,
                'dgName' => $dgName,
                'siteLogoUrl' => $siteLogoUrl,
                'teacherSignatureUrl' => $teacherSignatureUrl,
                'dgSignatureUrl' => $dgSignatureUrl,
                'issuedLabel' => $issuedLabel,
                'verifyUrl' => $verifyUrl,
                'qrVerifyUrl' => $qrVerifyUrl,
                'exportFormat' => $format,
                'isWordExport' => $format === 'word',
                'isPdfExport' => $format === 'pdf',
            ],
        ];
    }

    /**
     * @return array{httpStatus:int,abortTitle:string,abortMessage:string,view:array<string,mixed>}
     */
    private function abort(int $httpStatus, string $title, string $message): array
    {
        return [
            'httpStatus' => $httpStatus,
            'abortTitle' => $title,
            'abortMessage' => $message,
            'view' => [],
        ];
    }

    private function toAssetUrl(string $path): string
    {
        if ($path === '') {
            return '';
        }
        if (preg_match('/^https?:\\/\\//i', $path) === 1) {
            return $path;
        }
        return $this->siteUrl(ltrim($path, '/'));
    }
}

