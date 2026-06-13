<?php
declare(strict_types=1);

namespace App\Services\Student;

use App\Core\Service;
use App\Models\ApiCatalogModel;

final class FavoritesService extends Service
{
    private const FAVORITES_PER_PAGE = 9;

    private ApiCatalogModel $catalog;

    public function __construct(ApiCatalogModel $catalog)
    {
        $this->catalog = $catalog;
    }

    /**
     * @param array<int,string> $roles
     * @param array<string,mixed> $post
     * @param array<string,mixed> $query
     * @return array<string,mixed>
     */
    public function buildPageData(int $userId, array $roles, string $method, array $post, array $query = []): array
    {
        $dashboardUrl = $this->siteUrl('student/dashboard');
        if (in_array('admin', $roles, true) || in_array('admin_secondary', $roles, true)) {
            $dashboardUrl = $this->siteUrl('admin/dashboard');
        } elseif (in_array('teacher', $roles, true)) {
            $dashboardUrl = $this->siteUrl('teacher/dashboard');
        } elseif (in_array('company_admin', $roles, true) || in_array('company_member', $roles, true)) {
            $dashboardUrl = $this->siteUrl('company/dashboard');
        }

        $schemaReady = $this->catalog->courseFavoritesTableReady();
        $errors = [];
        $success = '';

        if ($schemaReady && strtoupper($method) === 'POST') {
            $action = strtolower(trim((string) ($post['action'] ?? '')));
            if ($action === 'remove_favorite') {
                $courseId = (int) ($post['course_id'] ?? 0);
                if ($courseId > 0) {
                    try {
                        $this->catalog->removeCourseFavorite($userId, $courseId);
                        $success = 'Cours retire des favoris.';
                    } catch (\Throwable $e) {
                        $errors[] = 'Impossible de retirer ce cours des favoris.';
                    }
                } else {
                    $errors[] = 'Cours invalide.';
                }
            }
        }

        $purgedCount = 0;
        if ($schemaReady) {
            try {
                $purgedCount = $this->catalog->removeOwnedCourseFavorites($userId);
            } catch (\Throwable $e) {
                $purgedCount = 0;
            }
        }

        $favorites = [];
        $favoritesTotal = 0;
        $pagination = Pagination::build(0, 1, self::FAVORITES_PER_PAGE);
        if ($schemaReady) {
            try {
                $favoritesTotal = $this->catalog->countStudentFavorites($userId);
                $page = Pagination::pageFromQuery($query, 'page');
                $pagination = Pagination::build($favoritesTotal, $page, self::FAVORITES_PER_PAGE);
                $rows = $this->catalog->fetchStudentFavoritesPage(
                    $userId,
                    (int) $pagination['offset'],
                    (int) $pagination['per_page']
                );
                foreach ($rows as $row) {
                    $favorites[] = $this->mapFavoriteRow($row);
                }
            } catch (\Throwable $e) {
                $favorites = [];
                $favoritesTotal = 0;
                $pagination = Pagination::build(0, 1, self::FAVORITES_PER_PAGE);
                $errors[] = 'Impossible de charger vos favoris pour le moment.';
            }
        }

        return [
            'schemaReady' => $schemaReady,
            'favorites' => $favorites,
            'favoritesCount' => $favoritesTotal,
            'pagination' => $pagination,
            'purgedCount' => $purgedCount,
            'dashboardUrl' => $dashboardUrl,
            'success' => $success,
            'errors' => $errors,
        ];
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function mapFavoriteRow(array $row): array
    {
        $levelLabels = [
            'beginner' => 'Débutant',
            'intermediate' => 'Intermédiaire',
            'advanced' => 'Avancé',
        ];

        $courseId = (int) ($row['id'] ?? 0);
        $slug = trim((string) ($row['slug'] ?? ''));
        $coverPath = trim((string) ($row['cover_path'] ?? ''));
        $coverUrl = 'https://i.ytimg.com/vi_webp/ysz5S6PUM-U/maxresdefault.webp';
        if ($coverPath !== '') {
            if (preg_match('#^https?://#i', $coverPath) === 1) {
                $coverUrl = $coverPath;
            } else {
                $coverUrl = $this->siteUrl(ltrim($coverPath, '/'));
            }
        }

        $teacher = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));
        $level = trim((string) ($row['level'] ?? ''));
        $promo = $this->resolvePromoData($row);

        return [
            'id' => $courseId,
            'title' => (string) ($row['title'] ?? 'Cours'),
            'course_url' => $slug !== '' ? $this->siteUrl('course?slug=' . $slug) : $this->siteUrl('course?id=' . $courseId),
            'checkout_url' => $this->siteUrl('checkout?course=' . $courseId),
            'cover_url' => $coverUrl,
            'cover_alt' => (string) ($row['cover_alt'] ?? ''),
            'teacher' => $teacher !== '' ? $teacher : 'Enseignant',
            'category' => (string) ($row['category_name'] ?? 'Autre'),
            'level_label' => (string) ($levelLabels[$level] ?? ($level !== '' ? $level : 'Niveau')),
            'price_xof' => $promo['price_xof'],
            'base_price_xof' => $promo['base_price_xof'],
            'promo_active' => $promo['active'],
            'total_sales' => (int) ($row['total_sales'] ?? 0),
        ];
    }

    /**
     * @param array<string,mixed> $row
     * @return array{active:bool,price_xof:int,base_price_xof:int}
     */
    private function resolvePromoData(array $row): array
    {
        $basePrice = max(0, (int) ($row['price_xof'] ?? 0));
        $type = strtolower(trim((string) ($row['promo_type'] ?? '')));
        $value = is_numeric($row['promo_value'] ?? null) ? (float) $row['promo_value'] : 0.0;
        $startAt = trim((string) ($row['promo_start_at'] ?? ''));
        $endAt = trim((string) ($row['promo_end_at'] ?? ''));
        $startTs = $startAt !== '' ? (int) strtotime($startAt) : 0;
        $endTs = $endAt !== '' ? (int) strtotime($endAt) : 0;
        $now = time();

        $activeWindow = $endTs > $now && ($startTs === 0 || $startTs <= $now);
        if (!$activeWindow || $basePrice < 1) {
            return [
                'active' => false,
                'price_xof' => $basePrice,
                'base_price_xof' => $basePrice,
            ];
        }

        $price = $basePrice;
        if ($type === 'amount') {
            $price = max(0, (int) round($value));
        } elseif ($type === 'percent') {
            $ratio = max(0.0, min(100.0, $value));
            $price = max(0, (int) round($basePrice * (1 - ($ratio / 100))));
        }

        if ($price >= $basePrice) {
            return [
                'active' => false,
                'price_xof' => $basePrice,
                'base_price_xof' => $basePrice,
            ];
        }

        return [
            'active' => true,
            'price_xof' => $price,
            'base_price_xof' => $basePrice,
        ];
    }
}
