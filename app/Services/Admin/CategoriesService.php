<?php
declare(strict_types=1);

namespace App\Services\Admin;

use App\Core\Service;
use App\Models\AdminCategoriesModel;
use App\Models\AdminDashboardModel;

final class CategoriesService extends Service
{
    private \PDO $pdo;
    private AdminCategoriesModel $model;
    private AdminDashboardModel $auditModel;

    public function __construct(\PDO $pdo, AdminCategoriesModel $model, AdminDashboardModel $auditModel)
    {
        $this->pdo = $pdo;
        $this->model = $model;
        $this->auditModel = $auditModel;
    }

    /**
     * @param array<string,mixed> $post
     * @param array<string,mixed> $currentUser
     * @return array{
     *   redirectTo:?string,
     *   view:array<string,mixed>
     * }
     */
    public function handle(string $method, array $post, array $currentUser): array
    {
        $errors = [];
        $success = (string) $this->flashGet('success', '');
        $currentPage = max(1, (int) ($post['page'] ?? 1));

        if ($method === 'POST') {
            $action = (string) ($post['action'] ?? '');

            if ($action === 'create') {
                $this->handleCreate($post, $currentUser, $errors, $success);
            } elseif ($action === 'update') {
                $this->handleUpdate($post, $currentUser, $errors, $success);
            } elseif ($action === 'delete') {
                $this->handleDelete($post, $currentUser, $errors, $success);
            }
        }

        if ($method === 'POST' && $errors === [] && $success !== '') {
            $this->flashSet('success', $success);
            $query = [];
            if ($currentPage > 1) {
                $query['page'] = $currentPage;
            }
            return [
                'redirectTo' => $this->siteUrl('admin/categories' . ($query !== [] ? ('?' . http_build_query($query)) : '')),
                'view' => [],
            ];
        }

        return [
            'redirectTo' => null,
            'view' => [
                'errors' => $errors,
                'success' => $success,
                'categories' => $this->model->fetchCategoriesForAdmin(),
            ],
        ];
    }

    /**
     * @param array<string,mixed> $post
     * @param array<string,mixed> $currentUser
     * @param array<int,string> $errors
     */
    private function handleCreate(array $post, array $currentUser, array &$errors, string &$success): void
    {
        $name = trim((string) ($post['name'] ?? ''));
        $slug = trim((string) ($post['slug'] ?? ''));
        $active = $this->resolveActiveFlag($post);

        if ($name === '') {
            $errors[] = 'Le nom est obligatoire.';
        }
        if ($slug === '' && $name !== '') {
            $slug = $this->generateCategorySlug($name, null);
        }

        if ($errors !== []) {
            return;
        }

        try {
            $this->pdo->beginTransaction();
            $categoryId = $this->model->createCategory($name, $slug);
            if (!$active) {
                $this->model->setCategoryActive($categoryId, false);
            }
            $this->recordAudit((int) ($currentUser['id'] ?? 0), 'category_create', $categoryId, [
                'name' => $name,
                'slug' => $slug,
                'active' => $active ? '1' : '0',
            ]);
            $this->pdo->commit();
            $success = 'Catégorie créée.';
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $errors[] = 'Erreur lors de la création : le slug est peut-être déjà utilisé.';
        }
    }

    /**
     * @param array<string,mixed> $post
     * @param array<string,mixed> $currentUser
     * @param array<int,string> $errors
     */
    private function handleUpdate(array $post, array $currentUser, array &$errors, string &$success): void
    {
        $id = (int) ($post['id'] ?? 0);
        $name = trim((string) ($post['name'] ?? ''));
        $slug = trim((string) ($post['slug'] ?? ''));
        $active = $this->resolveActiveFlag($post);

        if ($id <= 0) {
            $errors[] = 'Catégorie inconnue.';
        }
        if ($name === '') {
            $errors[] = 'Le nom est obligatoire.';
        }
        if ($slug === '' && $name !== '') {
            $slug = $this->generateCategorySlug($name, $id);
        }

        if ($errors !== []) {
            return;
        }

        try {
            $this->pdo->beginTransaction();
            $this->model->updateCategory($id, $name, $slug, $active);
            $this->recordAudit((int) ($currentUser['id'] ?? 0), 'category_update', $id, [
                'name' => $name,
                'slug' => $slug,
                'active' => $active ? '1' : '0',
            ]);
            $this->pdo->commit();
            $success = 'Catégorie mise à jour.';
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $errors[] = 'Erreur lors de la mise à jour : le slug est peut-être déjà utilisé.';
        }
    }

    /**
     * @param array<string,mixed> $post
     * @param array<string,mixed> $currentUser
     * @param array<int,string> $errors
     */
    private function handleDelete(array $post, array $currentUser, array &$errors, string &$success): void
    {
        $id = (int) ($post['id'] ?? 0);
        if ($id <= 0) {
            $errors[] = 'Catégorie inconnue.';
            return;
        }

        $coursesCount = $this->model->countCoursesByCategory($id);
        if ($coursesCount > 0) {
            $errors[] = 'Impossible de supprimer cette catégorie : des cours y sont liés.';
            return;
        }

        try {
            $category = $this->model->findCategoryById($id);
            $this->model->deleteCategory($id);
            $this->recordAudit((int) ($currentUser['id'] ?? 0), 'category_delete', $id, [
                'name' => (string) ($category['name'] ?? ''),
                'slug' => (string) ($category['slug'] ?? ''),
                'active' => (int) ($category['is_deleted'] ?? 0) === 0 ? '1' : '0',
            ]);
            $success = 'Catégorie supprimée.';
        } catch (\Throwable $e) {
            $errors[] = 'Impossible de supprimer cette catégorie pour le moment.';
        }
    }

    private function generateCategorySlug(string $name, ?int $ignoreId): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name;
        $base = strtolower(trim((string) preg_replace('/[^A-Za-z0-9-]+/', '-', $ascii), '-'));
        if ($base === '') {
            $base = 'categorie';
        }

        $slug = $base;
        $i = 2;
        while ($this->model->slugExists($slug, $ignoreId)) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    /**
     * @param array<string,mixed> $post
     */
    private function resolveActiveFlag(array $post): bool
    {
        if (!array_key_exists('active', $post)) {
            return true;
        }

        return in_array((string) $post['active'], ['1', 'on', 'yes', 'true'], true);
    }

    /**
     * @param array<string,mixed> $meta
     */
    private function recordAudit(int $actorId, string $action, int $categoryId, array $meta = []): void
    {
        $this->auditModel->insertAuditLog($actorId > 0 ? $actorId : null, $action, 'category', $categoryId, $meta);
    }
}
