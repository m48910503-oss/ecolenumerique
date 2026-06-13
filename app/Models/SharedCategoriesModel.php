<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class SharedCategoriesModel extends Model
{
    public function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT id FROM categories WHERE slug = :slug';
        $params = [':slug' => $slug];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> :ignore';
            $params[':ignore'] = $ignoreId;
        }

        $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);

        return (bool) $stmt->fetch();
    }

    public function createCategory(string $name, string $slug): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO categories (parent_id, name, slug, is_deleted, created_at, updated_at)
             VALUES (NULL, :name, :slug, 0, NOW(), NOW())'
        );
        $stmt->execute([
            ':name' => $name,
            ':slug' => $slug,
        ]);

        $categoryId = (int) $this->pdo->lastInsertId();
        if ($categoryId > 0) {
            return $categoryId;
        }

        $fallback = $this->findCategoryBySlug($slug);
        if ($fallback !== null) {
            return (int) ($fallback['id'] ?? 0);
        }

        throw new \RuntimeException('Impossible de recuperer la categorie apres creation.');
    }

    public function fetchActiveCategories(): array
    {
        $stmt = $this->pdo->query('SELECT id, parent_id, name, slug FROM categories WHERE is_deleted = 0 ORDER BY name');
        return $stmt->fetchAll() ?: [];
    }

    public function findActiveCategoryByName(string $name): ?array
    {
        $normalized = $this->normalizeName($name);
        if ($normalized === '') {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT id, name, slug
             FROM categories
             WHERE is_deleted = 0
               AND LOWER(TRIM(name)) = LOWER(TRIM(:name))
             LIMIT 1'
        );
        $stmt->execute([':name' => $normalized]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function findCategoryBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, slug FROM categories WHERE slug = :slug LIMIT 1');
        $stmt->execute([':slug' => $slug]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function normalizeName(string $name): string
    {
        $name = trim($name);
        $name = (string) preg_replace('/\s+/', ' ', $name);

        return $name;
    }
}
