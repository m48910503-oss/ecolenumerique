<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class WebCheckoutModel extends Model
{
    public function findApprovedCoursesByIds(array $courseIds): array
    {
        if ($courseIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($courseIds), '?'));
        $courseContentSelect = '
                c.duration_hours,
                COALESCE((
                    SELECT SUM(cl.duration_estimate_min)
                    FROM course_chapters cc
                    JOIN course_lessons cl ON cl.chapter_id = cc.id
                    WHERE cc.course_id = c.id
                ), 0) AS total_min,
                COALESCE((
                    SELECT COUNT(*)
                    FROM course_chapters cc2
                    JOIN course_lessons cl2 ON cl2.chapter_id = cc2.id
                    WHERE cc2.course_id = c.id
                ), 0) AS lessons_count,
                (
                    COALESCE((SELECT COUNT(*) FROM exam_sessions es WHERE es.course_id = c.id), 0)
                    + COALESCE((
                        SELECT COUNT(*)
                        FROM chapter_documents cd
                        JOIN course_chapters cc3 ON cc3.id = cd.chapter_id
                        WHERE cc3.course_id = c.id AND cd.type = \'exam\'
                    ), 0)
                ) AS exam_count';
        try {
            $stmt = $this->pdo->prepare(
                "SELECT c.id, c.teacher_id, c.title, c.price_xof, c.enterprise_price_xof, c.enterprise_discount_percent, c.promo_type, c.promo_value, c.promo_start_at, c.promo_end_at, c.level, c.cover_path, c.cover_alt, c.referral_override, c.referral_l2_enabled,
                        $courseContentSelect
                 FROM courses c
                 WHERE c.id IN ($placeholders) AND c.status = 'approved' AND c.deleted_at IS NULL"
            );
            $stmt->execute($courseIds);
            return $stmt->fetchAll() ?: [];
        } catch (\Throwable $e) {
            try {
                $stmt = $this->pdo->prepare(
                    "SELECT c.id, c.teacher_id, c.title, c.price_xof, NULL AS enterprise_price_xof, NULL AS enterprise_discount_percent, c.promo_type, c.promo_value, c.promo_start_at, c.promo_end_at, c.level, c.cover_path, c.cover_alt, c.referral_override, c.referral_l2_enabled,
                            $courseContentSelect
                     FROM courses c
                     WHERE c.id IN ($placeholders) AND c.status = 'approved' AND c.deleted_at IS NULL"
                );
                $stmt->execute($courseIds);
                return $stmt->fetchAll() ?: [];
            } catch (\Throwable $e) {
                $stmt = $this->pdo->prepare(
                    "SELECT id, teacher_id, title, price_xof, NULL AS enterprise_price_xof, NULL AS enterprise_discount_percent, promo_type, promo_value, promo_start_at, promo_end_at, level, cover_path, cover_alt, referral_override, referral_l2_enabled
                     FROM courses
                     WHERE id IN ($placeholders) AND status = 'approved' AND deleted_at IS NULL"
                );
                $stmt->execute($courseIds);
                return $stmt->fetchAll() ?: [];
            }
        }
    }

    public function findActiveEnrollmentCourseIds(int $userId, array $courseIds): array
    {
        if ($userId < 1 || $courseIds === []) {
            return [];
        }
        $placeholders = [];
        $params = [':uid' => $userId];
        foreach ($courseIds as $index => $courseId) {
            $placeholder = ':c' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = (int) $courseId;
        }
        $stmt = $this->pdo->prepare(
            'SELECT course_id
             FROM enrollments
             WHERE user_id = :uid
               AND status = "active"
               AND course_id IN (' . implode(',', $placeholders) . ')'
        );
        $stmt->execute($params);
        return array_map('intval', array_column($stmt->fetchAll() ?: [], 'course_id'));
    }

    public function findActiveCompanyAdminContext(int $userId): ?array
    {
        if ($userId < 1) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            "SELECT c.id AS company_id, c.name AS company_name, cm.role
             FROM company_members cm
             JOIN companies c ON c.id = cm.company_id
             WHERE cm.user_id = :uid
               AND cm.status = 'active'
               AND cm.role IN ('owner','admin')
               AND c.status = 'active'
             ORDER BY cm.id DESC
             LIMIT 1"
        );
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
