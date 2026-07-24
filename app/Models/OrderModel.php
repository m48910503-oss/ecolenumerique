<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class OrderModel extends Model
{
    public function countByUserId(int $userId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM orders
             WHERE user_id = :uid'
        );
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function findByUserIdPage(int $userId, int $offset, int $limit): array
    {
        $offset = max(0, $offset);
        $limit = max(1, min(200, $limit));

        $stmt = $this->pdo->prepare(
            'SELECT o.*,
                    (SELECT GROUP_CONCAT(ci.course_id)
                     FROM order_items ci
                     WHERE ci.order_id = o.id) AS course_ids
             FROM orders o
             WHERE o.user_id = :uid
             ORDER BY o.created_at DESC
             LIMIT ' . $limit . ' OFFSET ' . $offset
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    public function findByUserId(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT o.*,
                    (SELECT GROUP_CONCAT(ci.course_id)
                     FROM order_items ci
                     WHERE ci.order_id = o.id) AS course_ids
             FROM orders o
             WHERE o.user_id = :uid
             ORDER BY o.created_at DESC'
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    public function findByIdForUser(int $userId, int $orderId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT o.*,
                    (SELECT GROUP_CONCAT(ci.course_id)
                     FROM order_items ci
                     WHERE ci.order_id = o.id) AS course_ids
             FROM orders o
             WHERE o.user_id = :uid
               AND o.id = :oid
             LIMIT 1'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':oid' => $orderId,
        ]);
        $row = $stmt->fetch();
        return is_array($row) && $row !== [] ? $row : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findItemsByOrderId(int $orderId): array
    {
        $hasCoursesTable = $this->tableExists('courses');
        $selectTitle = $hasCoursesTable
            ? 'c.title AS course_title'
            : 'NULL AS course_title';
        $joinCourses = $hasCoursesTable
            ? 'LEFT JOIN courses c ON c.id = oi.course_id'
            : '';

        $stmt = $this->pdo->prepare(
            'SELECT oi.id AS order_item_id,
                    oi.course_id,
                    oi.quantity,
                    oi.unit_price_xof,
                    ' . $selectTitle . '
             FROM order_items oi
             ' . $joinCourses . '
             WHERE oi.order_id = :oid
             ORDER BY oi.id ASC'
        );
        $stmt->execute([':oid' => $orderId]);
        return $stmt->fetchAll() ?: [];
    }

    public function findCourseTitlesByIds(array $courseIds): array
    {
        if ($courseIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($courseIds), '?'));
        $stmt = $this->pdo->prepare("SELECT id, title FROM courses WHERE id IN ($placeholders)");
        $stmt->execute($courseIds);

        $titles = [];
        foreach ($stmt->fetchAll() ?: [] as $row) {
            $titles[(int) $row['id']] = (string) ($row['title'] ?? '');
        }
        return $titles;
    }
}
