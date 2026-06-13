<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class ApiCatalogModel extends Model
{
    private ?bool $ratingsModerationReady = null;

    public function fetchCatalogRows(?int $userId, bool $includePurchased, bool $onlyOwned): array
    {
        $params = [];
        $excludePurchasedSql = '';
        $purchasedJoinSql = '';
        $ownedSelectSql = '';
        $favoriteJoinSql = '';
        $favoriteSelectSql = ', 0 AS is_favorite';
        $favoritesReady = $this->courseFavoritesTableReady();
        $ratingHiddenWhere = $this->ratingsModerationReady() ? ' WHERE is_hidden_by_teacher = 0' : '';

        if ($userId !== null && $userId > 0) {
            $params[':uid_owned'] = $userId;
            $ownedSelectSql = ", CASE WHEN pe.group_id IS NULL THEN 0 ELSE 1 END AS is_owned";
            if ($favoritesReady) {
                $favoriteSelectSql = ", CASE WHEN cf.course_id IS NULL THEN 0 ELSE 1 END AS is_favorite";
                $favoriteJoinSql = "\n            LEFT JOIN course_favorites cf ON cf.user_id = :uid_fav AND cf.course_id = c.id";
                $params[':uid_fav'] = $userId;
            }
            $purchasedJoinSql = "\n        LEFT JOIN (\n            SELECT DISTINCT COALESCE(c2.version_of, c2.id) AS group_id\n            FROM enrollments e\n            JOIN courses c2 ON c2.id = e.course_id\n            WHERE e.user_id = :uid_owned AND e.status = 'active'\n        ) pe ON pe.group_id = COALESCE(c.version_of, c.id)";

            if ($onlyOwned) {
                $excludePurchasedSql = "\n          AND pe.group_id IS NOT NULL";
            } elseif (!$includePurchased) {
                $excludePurchasedSql = "\n          AND pe.group_id IS NULL";
            }
        }

        $sql = "
            SELECT
                c.id,
                c.title,
                c.level,
                c.price_xof,
                c.promo_type,
                c.promo_value,
                c.promo_start_at,
                c.promo_end_at,
                c.duration_hours,
                c.cover_path,
                c.cover_alt,
                c.slug,
                c.description,
                c.created_at,
                u.first_name,
                u.last_name,
                cat.name AS category_name,
                r.avg_rating,
                r.rating_count,
                d.total_min,
                COALESCE(s.total_sales, 0) AS total_sales" . $ownedSelectSql . $favoriteSelectSql . "
            FROM courses c
            LEFT JOIN users u ON u.id = c.teacher_id
            LEFT JOIN categories cat ON cat.id = c.category_id
            LEFT JOIN (
                SELECT course_id, AVG(rating) AS avg_rating, COUNT(*) AS rating_count
                FROM course_ratings
                " . $ratingHiddenWhere . "
                GROUP BY course_id
            ) r ON r.course_id = c.id
            LEFT JOIN (
                SELECT cc.course_id, SUM(cl.duration_estimate_min) AS total_min
                FROM course_chapters cc
                JOIN course_lessons cl ON cl.chapter_id = cc.id
                GROUP BY cc.course_id
            ) d ON d.course_id = c.id
            LEFT JOIN (
                SELECT COALESCE(c2.version_of, c2.id) AS group_id, SUM(oi.quantity) AS total_sales
                FROM order_items oi
                JOIN orders o ON o.id = oi.order_id AND o.status = 'paid'
                JOIN courses c2 ON c2.id = oi.course_id
                WHERE c2.deleted_at IS NULL
                GROUP BY COALESCE(c2.version_of, c2.id)
                ) s ON s.group_id = COALESCE(c.version_of, c.id)" . $purchasedJoinSql . "
            " . $favoriteJoinSql . "
            WHERE c.status = 'approved'
              AND c.deleted_at IS NULL" . $excludePurchasedSql . "
              AND NOT EXISTS (
                  SELECT 1
                  FROM courses c2
                  WHERE c2.status = 'approved'
                    AND c2.deleted_at IS NULL
                    AND COALESCE(c2.version_of, c2.id) = COALESCE(c.version_of, c.id)
                    AND (c2.created_at > c.created_at OR (c2.created_at = c.created_at AND c2.id > c.id))
              )
            ORDER BY c.created_at DESC
            LIMIT 100
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @param array<int,int> $courseIds
     * @return array<int,array<string,mixed>>
     */
    public function fetchPreviewLessonsForCourseIds(array $courseIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $courseIds)));
        $ids = array_values(array_filter($ids, static fn(int $id): bool => $id > 0));
        if ($ids === []) {
            return [];
        }

        $in = implode(',', array_fill(0, count($ids), '?'));
        $sql = 'SELECT
                    cc.course_id,
                    cl.id AS lesson_id,
                    cl.title AS lesson_title,
                    cl.youtube_url
                FROM course_chapters cc
                JOIN course_lessons cl ON cl.chapter_id = cc.id
                WHERE cc.course_id IN (' . $in . ')
                  AND cl.lesson_type = "video"
                  AND cl.youtube_url IS NOT NULL
                  AND cl.youtube_url <> ""
                  AND CONCAT(
                        LPAD(CAST(cc.position AS CHAR), 6, "0"),
                        "-",
                        LPAD(CAST(cl.position AS CHAR), 6, "0"),
                        "-",
                        LPAD(CAST(cl.id AS CHAR), 12, "0")
                  ) = (
                        SELECT MIN(
                            CONCAT(
                                LPAD(CAST(cc2.position AS CHAR), 6, "0"),
                                "-",
                                LPAD(CAST(cl2.position AS CHAR), 6, "0"),
                                "-",
                                LPAD(CAST(cl2.id AS CHAR), 12, "0")
                            )
                        )
                        FROM course_chapters cc2
                        JOIN course_lessons cl2 ON cl2.chapter_id = cc2.id
                        WHERE cc2.course_id = cc.course_id
                          AND cl2.lesson_type = "video"
                          AND cl2.youtube_url IS NOT NULL
                          AND cl2.youtube_url <> ""
                  )';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($ids);
        $rows = $stmt->fetchAll() ?: [];
        $out = [];
        foreach ($rows as $row) {
            $courseId = (int) ($row['course_id'] ?? 0);
            if ($courseId > 0 && !isset($out[$courseId])) {
                $out[$courseId] = $row;
            }
        }
        return $out;
    }

    public function findApprovedCourseForFavorite(int $courseId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id
             FROM courses
             WHERE id = :cid
               AND status = "approved"
               AND deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([':cid' => $courseId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function hasActiveEnrollmentForCourseGroup(int $userId, int $courseId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1
             FROM enrollments e
             JOIN courses ec ON ec.id = e.course_id
             JOIN courses tc ON tc.id = :cid
             WHERE e.user_id = :uid
               AND e.status = "active"
               AND COALESCE(ec.version_of, ec.id) = COALESCE(tc.version_of, tc.id)
             LIMIT 1'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':cid' => $courseId,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    public function isCourseFavorite(int $userId, int $courseId): bool
    {
        if (!$this->courseFavoritesTableReady()) {
            return false;
        }
        $stmt = $this->pdo->prepare(
            'SELECT 1
             FROM course_favorites
             WHERE user_id = :uid
               AND course_id = :cid
             LIMIT 1'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':cid' => $courseId,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    public function addCourseFavorite(int $userId, int $courseId): void
    {
        if (!$this->courseFavoritesTableReady()) {
            return;
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO course_favorites (user_id, course_id, created_at)
             VALUES (:uid, :cid, NOW())
             ON DUPLICATE KEY UPDATE
                created_at = created_at'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':cid' => $courseId,
        ]);
    }

    public function removeCourseFavorite(int $userId, int $courseId): void
    {
        if (!$this->courseFavoritesTableReady()) {
            return;
        }
        $stmt = $this->pdo->prepare(
            'DELETE FROM course_favorites
             WHERE user_id = :uid
               AND course_id = :cid'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':cid' => $courseId,
        ]);
    }

    public function removeOwnedCourseFavorites(int $userId): int
    {
        if (!$this->courseFavoritesTableReady()) {
            return 0;
        }

        $stmt = $this->pdo->prepare(
            'DELETE cf
             FROM course_favorites cf
             JOIN courses fc ON fc.id = cf.course_id
             JOIN enrollments e
               ON e.user_id = :uid_enroll
              AND e.status = "active"
             JOIN courses ec ON ec.id = e.course_id
             WHERE cf.user_id = :uid_fav
               AND COALESCE(ec.version_of, ec.id) = COALESCE(fc.version_of, fc.id)'
        );
        $stmt->execute([
            ':uid_enroll' => $userId,
            ':uid_fav' => $userId,
        ]);
        return (int) $stmt->rowCount();
    }

    /**
     * @return array<int,int>
     */
    public function fetchFavoriteCourseIds(int $userId): array
    {
        if (!$this->courseFavoritesTableReady()) {
            return [];
        }
        $stmt = $this->pdo->prepare(
            'SELECT course_id
             FROM course_favorites
             WHERE user_id = :uid
             ORDER BY created_at DESC'
        );
        $stmt->execute([':uid' => $userId]);
        $rows = $stmt->fetchAll();
        if (!is_array($rows)) {
            return [];
        }
        $ids = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $cid = (int) ($row['course_id'] ?? 0);
            if ($cid > 0) {
                $ids[] = $cid;
            }
        }
        return $ids;
    }

    public function countCourseFavorites(int $userId): int
    {
        if (!$this->courseFavoritesTableReady()) {
            return 0;
        }
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM course_favorites
             WHERE user_id = :uid'
        );
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function countStudentFavorites(int $userId): int
    {
        if (!$this->courseFavoritesTableReady()) {
            return 0;
        }

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM course_favorites cf
             JOIN courses c ON c.id = cf.course_id
             WHERE cf.user_id = :uid
               AND c.status = "approved"
               AND c.deleted_at IS NULL'
        );
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchStudentFavoritesPage(int $userId, int $offset, int $limit): array
    {
        if (!$this->courseFavoritesTableReady()) {
            return [];
        }

        $offset = max(0, $offset);
        $limit = max(1, min(100, $limit));

        $stmt = $this->pdo->prepare(
            'SELECT
                c.id,
                c.title,
                c.level,
                c.price_xof,
                c.promo_type,
                c.promo_value,
                c.promo_start_at,
                c.promo_end_at,
                c.cover_path,
                c.cover_alt,
                c.slug,
                u.first_name,
                u.last_name,
                cat.name AS category_name,
                COALESCE(s.total_sales, 0) AS total_sales
             FROM course_favorites cf
             JOIN courses c ON c.id = cf.course_id
             LEFT JOIN users u ON u.id = c.teacher_id
             LEFT JOIN categories cat ON cat.id = c.category_id
             LEFT JOIN (
                SELECT COALESCE(c2.version_of, c2.id) AS group_id, SUM(oi.quantity) AS total_sales
                FROM order_items oi
                JOIN orders o ON o.id = oi.order_id AND o.status = "paid"
                JOIN courses c2 ON c2.id = oi.course_id
                WHERE c2.deleted_at IS NULL
                GROUP BY COALESCE(c2.version_of, c2.id)
             ) s ON s.group_id = COALESCE(c.version_of, c.id)
             WHERE cf.user_id = :uid
               AND c.status = "approved"
               AND c.deleted_at IS NULL
             ORDER BY cf.created_at DESC, cf.course_id DESC
             LIMIT ' . $limit . ' OFFSET ' . $offset
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    public function courseFavoritesTableReady(): bool
    {
        static $cached = null;
        if (is_bool($cached)) {
            return $cached;
        }

        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'course_favorites'");
            $cached = (bool) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            $cached = false;
        }

        return $cached;
    }

    private function ratingsModerationReady(): bool
    {
        if (is_bool($this->ratingsModerationReady)) {
            return $this->ratingsModerationReady;
        }
        $this->ratingsModerationReady = $this->columnExists('course_ratings', 'is_hidden_by_teacher');
        return $this->ratingsModerationReady;
    }
}
