<?php
declare(strict_types=1);

namespace App\Models;

final class AdminCategoriesModel extends SharedCategoriesModel
{
    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchCategoriesForAdmin(): array
    {
        $stmt = $this->pdo->query(
            'SELECT
                cat.id,
                cat.parent_id,
                cat.name,
                cat.slug,
                cat.is_deleted,
                cat.created_at,
                cat.updated_at,
                COUNT(c.id) AS courses_count
             FROM categories cat
             LEFT JOIN courses c ON c.category_id = cat.id AND c.deleted_at IS NULL
             GROUP BY cat.id, cat.parent_id, cat.name, cat.slug, cat.is_deleted, cat.created_at, cat.updated_at
             ORDER BY cat.is_deleted ASC, cat.name ASC'
        );

        return $stmt->fetchAll() ?: [];
    }

    public function updateCategory(int $id, string $name, string $slug, bool $active = true): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE categories
             SET parent_id = NULL, name = :name, slug = :slug, is_deleted = :is_deleted, updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            ':name' => $name,
            ':slug' => $slug,
            ':is_deleted' => $active ? 0 : 1,
            ':id' => $id,
        ]);
    }

    public function setCategoryActive(int $categoryId, bool $active): void
    {
        $stmt = $this->pdo->prepare('UPDATE categories SET is_deleted = :is_deleted, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':is_deleted' => $active ? 0 : 1,
            ':id' => $categoryId,
        ]);
    }

    public function countCoursesByCategory(int $categoryId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) AS cnt FROM courses WHERE category_id = :id AND deleted_at IS NULL');
        $stmt->execute([':id' => $categoryId]);
        $row = $stmt->fetch();
        return (int) ($row['cnt'] ?? 0);
    }

    public function deleteCategory(int $categoryId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM categories WHERE id = :id');
        $stmt->execute([':id' => $categoryId]);
    }
}
