<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class TeacherCourseUpdatesModel extends Model
{
    public function fetchUpdates(bool $isAdmin, int $teacherId, int $limit = 100): array
    {
        $sql = 'SELECT al.*, c.title AS course_title, c.id AS course_id, u.first_name, u.last_name
            FROM audit_logs al
            JOIN courses c ON c.id = al.target_id
            LEFT JOIN users u ON u.id = al.user_id
            WHERE al.target_type = "course"
              AND al.action IN ("course_update","course_update_started","course_update_finished")';
        $params = [];
        if (!$isAdmin) {
            $sql .= ' AND c.teacher_id = :tid';
            $params[':tid'] = $teacherId;
        }
        $sql .= ' ORDER BY al.created_at DESC LIMIT ' . max(1, (int) $limit);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }
}
