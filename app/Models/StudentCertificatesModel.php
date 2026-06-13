<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class StudentCertificatesModel extends Model
{
    public function countIssuedCertificates(int $userId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM certificates cert
             WHERE cert.user_id = :uid
               AND cert.status = 'issued'"
        );
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchIssuedCertificatesPage(int $userId, int $offset, int $limit = 50): array
    {
        $offset = max(0, $offset);
        $limit = max(1, min(500, $limit));
        $stmt = $this->pdo->prepare(
            "SELECT
                cert.code,
                cert.issued_at,
                cert.score,
                cert.status,
                c.id AS course_id,
                c.slug AS course_slug,
                c.title AS course_title
             FROM certificates cert
             JOIN courses c ON c.id = cert.course_id
             WHERE cert.user_id = :uid
               AND cert.status = 'issued'
             ORDER BY cert.issued_at DESC, cert.id DESC
             LIMIT {$limit} OFFSET {$offset}"
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchIssuedCertificates(int $userId, int $limit = 200): array
    {
        return $this->fetchIssuedCertificatesPage($userId, 0, $limit);
    }
}
