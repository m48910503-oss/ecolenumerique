<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class SharedCertificateModel extends Model
{
    public function certificatesTableExists(): bool
    {
        return $this->tableExists('certificates');
    }

    public function usersSignatureColumnExists(): bool
    {
        return $this->columnExists('users', 'signature_path');
    }

    public function addUsersSignatureColumn(): bool
    {
        return $this->usersSignatureColumnExists();
    }

    public function createCertificatesTable(): bool
    {
        return $this->certificatesTableExists();
    }

    public function findCertificateByUserCourse(int $userId, int $courseId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT code, status FROM certificates WHERE user_id = :uid AND course_id = :cid LIMIT 1'
        );
        $stmt->execute([':uid' => $userId, ':cid' => $courseId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function insertIssuedCertificate(
        int $userId,
        int $courseId,
        int $examId,
        ?int $attemptId,
        int $score,
        string $code
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO certificates (user_id, course_id, exam_id, attempt_id, score, code, status, issued_at)
             VALUES (:uid, :cid, :eid, :aid, :score, :code, "issued", NOW())'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':cid' => $courseId,
            ':eid' => $examId,
            ':aid' => $attemptId,
            ':score' => $score,
            ':code' => $code,
        ]);
    }
}
