<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class WebCertificateModel extends Model
{
    public function findIssuedByCode(string $code, bool $withTeacherSignature): ?array
    {
        $teacherSelect = $withTeacherSignature ? ', t.signature_path AS teacher_signature_path' : '';
        $studentJoin = '';
        $studentSelect = '';
        if ($this->tableExists('student_profiles')) {
            $studentJoin = ' LEFT JOIN student_profiles sp ON sp.user_id = cert.user_id';
            $studentSelect = ', sp.certificate_display_name';
        }
        $stmt = $this->pdo->prepare(
            'SELECT cert.*, u.first_name, u.last_name, c.title AS course_title, es.title AS exam_title,
                    t.first_name AS teacher_first_name, t.last_name AS teacher_last_name' . $teacherSelect . $studentSelect . '
             FROM certificates cert
             JOIN users u ON u.id = cert.user_id
             JOIN courses c ON c.id = cert.course_id
             JOIN exam_sessions es ON es.id = cert.exam_id
             LEFT JOIN users t ON t.id = c.teacher_id
             ' . $studentJoin . '
             WHERE cert.code = :code
             LIMIT 1'
        );
        $stmt->execute([':code' => $code]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
