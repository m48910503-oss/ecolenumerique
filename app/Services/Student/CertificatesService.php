<?php
declare(strict_types=1);

namespace App\Services\Student;

use App\Core\Service;
use App\Models\StudentCertificatesModel;
use App\Services\Shared\CertificateOpsService;

final class CertificatesService extends Service
{
    private const CERTIFICATES_PER_PAGE = 8;

    private StudentCertificatesModel $model;
    private CertificateOpsService $certificates;

    public function __construct(StudentCertificatesModel $model, CertificateOpsService $certificates)
    {
        $this->model = $model;
        $this->certificates = $certificates;
    }

    /**
     * @param array<int,string> $roles
     * @param array<string,mixed> $query
     * @return array<string,mixed>
     */
    public function buildPageData(int $userId, array $roles, array $query = []): array
    {
        $dashboardUrl = $this->siteUrl('student/dashboard');
        if (in_array('admin', $roles, true) || in_array('admin_secondary', $roles, true)) {
            $dashboardUrl = $this->siteUrl('admin/dashboard');
        } elseif (in_array('teacher', $roles, true)) {
            $dashboardUrl = $this->siteUrl('teacher/dashboard');
        } elseif (in_array('company_admin', $roles, true) || in_array('company_member', $roles, true)) {
            $dashboardUrl = $this->siteUrl('company/dashboard');
        }

        $schemaReady = $this->certificates->hasCertificatesTable();
        $rows = [];
        $certTotal = 0;
        $pagination = Pagination::build(0, 1, self::CERTIFICATES_PER_PAGE);
        if ($schemaReady) {
            try {
                $certTotal = $this->model->countIssuedCertificates($userId);
                $page = Pagination::pageFromQuery($query, 'page');
                $pagination = Pagination::build($certTotal, $page, self::CERTIFICATES_PER_PAGE);
                $rows = $this->model->fetchIssuedCertificatesPage(
                    $userId,
                    (int) $pagination['offset'],
                    (int) $pagination['per_page']
                );
            } catch (\Throwable $e) {
                $rows = [];
                $certTotal = 0;
                $pagination = Pagination::build(0, 1, self::CERTIFICATES_PER_PAGE);
            }
        }

        return [
            'dashboardUrl' => $dashboardUrl,
            'schemaReady' => $schemaReady,
            'certificates' => $rows,
            'certCount' => $certTotal,
            'pagination' => $pagination,
            'fmtDtShort' => static function (?string $dbValue): string {
                if (!$dbValue) {
                    return '';
                }
                $ts = strtotime($dbValue);
                if (!$ts) {
                    return (string) $dbValue;
                }
                return date('d/m/Y H:i', $ts);
            },
        ];
    }
}
