<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class WebHomeModel extends Model
{
    /**
     * @return array<string,mixed>|null
     */
    public function fetchLatestPublicAnnouncement(): ?array
    {
        try {
            if (!$this->tableExists('admin_announcements')) {
                return null;
            }
            if (!$this->columnExists('admin_announcements', 'status') || !$this->columnExists('admin_announcements', 'audience')) {
                return null;
            }

            $summarySql = $this->columnExists('admin_announcements', 'summary')
                ? 'aa.summary'
                : 'NULL AS summary';
            $hasPublishedAt = $this->columnExists('admin_announcements', 'published_at');
            $publishedAtSql = $hasPublishedAt
                ? 'aa.published_at'
                : 'NULL AS published_at';
            $orderBySql = $hasPublishedAt
                ? 'COALESCE(aa.published_at, aa.created_at)'
                : 'aa.created_at';

            $stmt = $this->pdo->prepare(
                "SELECT
                    aa.id,
                    aa.title,
                    aa.body,
                    {$summarySql},
                    aa.action_url,
                    aa.created_at,
                    {$publishedAtSql}
                 FROM admin_announcements aa
                 WHERE aa.status = 'published'
                   AND aa.audience = 'all'
                 ORDER BY {$orderBySql} DESC, aa.id DESC
                 LIMIT 1"
            );
            $stmt->execute();
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchPublicCoursesForHome(int $limit = 12, ?int $userId = null): array
    {
        $limit = max(1, min(120, $limit));
        $couponSelectSql = '0 AS has_coupon';
        $enrollmentSelectSql = '0 AS enrolled_count';
        $enrollmentJoinSql = '';
        $ownedJoinSql = '';
        $ownedWhereSql = '';
        $objectivesSelectSql = 'NULL AS objectives';
        $prerequisitesSelectSql = 'NULL AS prerequisites';
        $equipmentSelectSql = 'NULL AS equipment';
        $deliveryModeSelectSql = 'NULL AS delivery_mode';
        $courseLocationSelectSql = 'NULL AS course_location';
        try {
            if (
                $this->tableExists('course_promotions')
                && $this->columnExists('course_promotions', 'course_id')
                && $this->columnExists('course_promotions', 'promo_mode')
                && $this->columnExists('course_promotions', 'coupon_code')
                && $this->columnExists('course_promotions', 'promo_start_at')
                && $this->columnExists('course_promotions', 'promo_end_at')
                && $this->columnExists('course_promotions', 'deleted_at')
            ) {
                $couponSelectSql = 'EXISTS (
                    SELECT 1
                    FROM course_promotions cp_coupon
                    WHERE cp_coupon.course_id = c.id
                      AND cp_coupon.deleted_at IS NULL
                      AND cp_coupon.promo_mode = "coupon"
                      AND cp_coupon.coupon_code IS NOT NULL
                      AND cp_coupon.coupon_code <> ""
                      AND cp_coupon.promo_start_at <= NOW()
                      AND cp_coupon.promo_end_at > NOW()
                    LIMIT 1
                ) AS has_coupon';
            }
        } catch (\Throwable $e) {
            $couponSelectSql = '0 AS has_coupon';
        }
        try {
            if ($this->tableExists('enrollments')) {
                $enrollmentSelectSql = 'COALESCE(enr.enrolled_count, 0) AS enrolled_count';
                $enrollmentJoinSql = '
             LEFT JOIN (
                SELECT e.course_id, COUNT(*) AS enrolled_count
                FROM enrollments e
                WHERE e.status = "active"
                GROUP BY e.course_id
             ) enr ON enr.course_id = c.id';
            }
        } catch (\Throwable $e) {
            $enrollmentSelectSql = '0 AS enrolled_count';
            $enrollmentJoinSql = '';
        }
        try {
            if ($userId !== null && $userId > 0 && $this->tableExists('enrollments')) {
                $ownedJoinSql = '
             LEFT JOIN (
                SELECT DISTINCT COALESCE(ec.version_of, ec.id) AS group_id
                FROM enrollments e
                JOIN courses ec ON ec.id = e.course_id
                WHERE e.user_id = :uid_owned
                  AND e.status = "active"
             ) owned ON owned.group_id = COALESCE(c.version_of, c.id)';
                $ownedWhereSql = '
               AND owned.group_id IS NULL';
            }
        } catch (\Throwable $e) {
            $ownedJoinSql = '';
            $ownedWhereSql = '';
        }
        try {
            if ($this->columnExists('courses', 'objectives')) {
                $objectivesSelectSql = 'c.objectives';
            }
            if ($this->columnExists('courses', 'prerequisites')) {
                $prerequisitesSelectSql = 'c.prerequisites';
            }
            if ($this->columnExists('courses', 'equipment')) {
                $equipmentSelectSql = 'c.equipment';
            }
            if ($this->columnExists('courses', 'delivery_mode')) {
                $deliveryModeSelectSql = 'c.delivery_mode';
            }
            if ($this->columnExists('courses', 'course_location')) {
                $courseLocationSelectSql = 'c.course_location';
            }
        } catch (\Throwable $e) {
            $objectivesSelectSql = 'NULL AS objectives';
            $prerequisitesSelectSql = 'NULL AS prerequisites';
            $equipmentSelectSql = 'NULL AS equipment';
            $deliveryModeSelectSql = 'NULL AS delivery_mode';
            $courseLocationSelectSql = 'NULL AS course_location';
        }

        $params = [];
        if ($userId !== null && $userId > 0 && $ownedJoinSql !== '') {
            $params[':uid_owned'] = $userId;
        }

        $stmt = $this->pdo->prepare(
            "SELECT
                c.id,
                c.slug,
                c.title,
                c.description,
                c.cover_path,
                c.cover_alt,
                c.price_xof,
                c.level,
                c.promo_type,
                c.promo_value,
                c.promo_start_at,
                c.promo_end_at,
                c.duration_hours,
                c.created_at,
                c.updated_at,
                {$objectivesSelectSql},
                {$prerequisitesSelectSql},
                {$equipmentSelectSql},
                {$deliveryModeSelectSql},
                {$courseLocationSelectSql},
                {$couponSelectSql},
                u.first_name,
                u.last_name,
                cat.name AS category_name,
                {$enrollmentSelectSql},
                COALESCE(r.avg_rating, 0) AS avg_rating,
                COALESCE(r.rating_count, 0) AS rating_count,
                COALESCE(d.total_min, 0) AS total_min
             FROM courses c
             LEFT JOIN users u ON u.id = c.teacher_id
             LEFT JOIN categories cat ON cat.id = c.category_id
             {$enrollmentJoinSql}
             {$ownedJoinSql}
             LEFT JOIN (
                SELECT course_id, AVG(rating) AS avg_rating, COUNT(*) AS rating_count
                FROM course_ratings
                GROUP BY course_id
             ) r ON r.course_id = c.id
             LEFT JOIN (
                SELECT cc.course_id, SUM(cl.duration_estimate_min) AS total_min
                FROM course_chapters cc
                JOIN course_lessons cl ON cl.chapter_id = cc.id
                GROUP BY cc.course_id
             ) d ON d.course_id = c.id
             WHERE c.status = 'approved'
               AND c.deleted_at IS NULL
               {$ownedWhereSql}
               AND NOT EXISTS (
                    SELECT 1
                    FROM courses c2
                    WHERE c2.status = 'approved'
                      AND c2.deleted_at IS NULL
                      AND COALESCE(c2.version_of, c2.id) = COALESCE(c.version_of, c.id)
                      AND (c2.created_at > c.created_at OR (c2.created_at = c.created_at AND c2.id > c.id))
               )
             ORDER BY c.created_at DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value, \PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public function countActivePublicEnrollments(): int
    {
        try {
            if (!$this->tableExists('enrollments')) {
                return 0;
            }

            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*)
                 FROM enrollments e
                 JOIN courses c ON c.id = e.course_id
                 WHERE e.status = "active"
                   AND c.status = "approved"
                   AND c.deleted_at IS NULL'
            );
            $stmt->execute();

            return max(0, (int) $stmt->fetchColumn());
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchPublicTestimonials(int $limit = 6): array
    {
        $limit = max(1, min(12, $limit));

        try {
            if (!$this->tableExists('course_ratings')) {
                return [];
            }

            $hiddenWhere = $this->columnExists('course_ratings', 'is_hidden_by_teacher')
                ? ' AND COALESCE(cr.is_hidden_by_teacher, 0) = 0'
                : '';

            $stmt = $this->pdo->prepare(
                'SELECT
                    cr.rating,
                    cr.comment,
                    cr.created_at,
                    u.first_name,
                    u.last_name,
                    c.title AS course_title
                 FROM course_ratings cr
                 JOIN users u ON u.id = cr.user_id
                 JOIN courses c ON c.id = cr.course_id
                 WHERE c.status = "approved"
                   AND c.deleted_at IS NULL
                   AND cr.rating > 0
                   AND TRIM(COALESCE(cr.comment, "")) <> ""' . $hiddenWhere . '
                 ORDER BY cr.created_at DESC
                 LIMIT ' . $limit
            );
            $stmt->execute();

            return $stmt->fetchAll() ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function countUnreadNotifications(int $userId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS unread
             FROM notifications
             WHERE user_id = :uid
               AND is_read = 0'
        );
        $stmt->execute([':uid' => $userId]);
        return (int) ($stmt->fetch()['unread'] ?? 0);
    }

    public function countUnreadSupportReplies(int $userId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS unread
             FROM support_messages m
             JOIN support_conversations c ON c.id = m.conversation_id
             LEFT JOIN support_conversation_reads r
                ON r.conversation_id = c.id
               AND r.user_id = :uid_read
             WHERE c.created_by = :uid_owner
               AND m.id > COALESCE(r.last_read_message_id, 0)
               AND m.sender_id <> :uid_sender'
        );
        $stmt->execute([
            ':uid_read' => $userId,
            ':uid_owner' => $userId,
            ':uid_sender' => $userId,
        ]);
        return (int) ($stmt->fetch()['unread'] ?? 0);
    }

    public function countUnreadSupportNotifications(int $userId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS unread
             FROM notifications
             WHERE user_id = :uid
               AND is_read = 0
               AND (
                    LOWER(title) LIKE :support
                    OR LOWER(body) LIKE :support
               )'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':support' => '%support%',
        ]);
        return (int) ($stmt->fetch()['unread'] ?? 0);
    }
}
