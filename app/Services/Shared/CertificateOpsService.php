<?php
declare(strict_types=1);

namespace App\Services\Shared;

use App\Core\Service;

use App\Models\SharedCertificateModel;
use PDOException;
use Throwable;

final class CertificateOpsService extends Service
{
    private SharedCertificateModel $model;
    private ?bool $certificatesTableCached = null;
    private ?bool $usersSignatureColumnCached = null;

    public function __construct(SharedCertificateModel $model)
    {
        $this->model = $model;
    }

    public function hasCertificatesTable(bool $refresh = false): bool
    {
        if ($refresh) {
            $this->certificatesTableCached = null;
        }
        if ($this->certificatesTableCached !== null) {
            return (bool) $this->certificatesTableCached;
        }
        try {
            $this->certificatesTableCached = $this->model->certificatesTableExists();
        } catch (Throwable $e) {
            $this->certificatesTableCached = false;
        }
        return (bool) $this->certificatesTableCached;
    }

    public function ensureCertificatesTable(): bool
    {
        return $this->hasCertificatesTable();
    }

    public function hasUsersSignatureColumn(bool $refresh = false): bool
    {
        if ($refresh) {
            $this->usersSignatureColumnCached = null;
        }
        if ($this->usersSignatureColumnCached !== null) {
            return (bool) $this->usersSignatureColumnCached;
        }
        try {
            $this->usersSignatureColumnCached = $this->model->usersSignatureColumnExists();
        } catch (Throwable $e) {
            $this->usersSignatureColumnCached = false;
        }
        return (bool) $this->usersSignatureColumnCached;
    }

    public function ensureUsersSignatureColumn(): bool
    {
        return $this->hasUsersSignatureColumn();
    }

    public function issueCertificate(
        int $userId,
        int $courseId,
        int $examId,
        ?int $attemptId,
        int $score,
        int $minScore
    ): ?string {
        if ($score < $minScore) {
            return null;
        }
        if (!$this->ensureCertificatesTable()) {
            return null;
        }

        try {
            $existing = $this->model->findCertificateByUserCourse($userId, $courseId);
            if ($existing) {
                return ($existing['status'] ?? '') === 'issued' ? (string) $existing['code'] : null;
            }
        } catch (Throwable $e) {
            return null;
        }

        for ($i = 0; $i < 5; $i++) {
            $code = $this->generateCertificateCode();
            try {
                $this->model->insertIssuedCertificate($userId, $courseId, $examId, $attemptId, $score, $code);
                return $code;
            } catch (PDOException $e) {
                try {
                    $existing = $this->model->findCertificateByUserCourse($userId, $courseId);
                    if ($existing) {
                        return ($existing['status'] ?? '') === 'issued' ? (string) $existing['code'] : null;
                    }
                } catch (Throwable $e2) {
                    return null;
                }
            } catch (Throwable $e) {
                return null;
            }
        }

        return null;
    }

    private function generateCertificateCode(int $bytes = 12): string
    {
        $raw = bin2hex(random_bytes(max(6, $bytes)));
        $token = strtoupper(substr($raw, 0, 16));
        return 'CERT-' . $token;
    }
}

