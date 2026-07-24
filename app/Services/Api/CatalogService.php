<?php
declare(strict_types=1);

namespace App\Services\Api;

use App\Core\Service;

use App\Models\ApiCatalogModel;
use App\Services\Shared\PromotionsService;

final class CatalogService extends Service
{
    private ApiCatalogModel $model;
    private PromotionsService $promotions;

    public function __construct(ApiCatalogModel $model, PromotionsService $promotions)
    {
        $this->model = $model;
        $this->promotions = $promotions;
    }

    public function listCourses(?array $user, array $query): array
    {
        $this->promotions->ensureCoursesPromoColumns();

        $levelLabels = [
            'beginner' => 'Débutant',
            'intermediate' => 'Intermédiaire',
            'advanced' => 'Avancé',
        ];

        $includePurchased = isset($query['include_purchased']) && (int) $query['include_purchased'] === 1;
        $onlyOwned = isset($query['only_owned']) && (int) $query['only_owned'] === 1;
        $userId = !empty($user['id']) ? (int) $user['id'] : null;
        $cartIds = [];
        $sessionCart = $_SESSION['cart'] ?? [];
        if (is_array($sessionCart)) {
            foreach ($sessionCart as $cartId) {
                $cartCourseId = (int) $cartId;
                if ($cartCourseId > 0) {
                    $cartIds[$cartCourseId] = true;
                }
            }
        }

        $rows = $this->model->fetchCatalogRows($userId, $includePurchased, $onlyOwned);
        $courseIds = array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $rows);
        $previewByCourse = $this->model->fetchPreviewLessonsForCourseIds($courseIds);

        $data = [];
        foreach ($rows as $row) {
            $promo = $this->promotions->coursePromoData($row, null, $userId);

            $thumbRaw = trim((string) ($row['cover_path'] ?? ''));
            if ($thumbRaw === '') {
                $thumb = 'https://i.ytimg.com/vi_webp/ysz5S6PUM-U/maxresdefault.webp';
            } elseif (preg_match('#^https?://#i', $thumbRaw) === 1) {
                $thumb = $thumbRaw;
            } else {
                $thumb = $this->siteUrl(ltrim($thumbRaw, '/'));
            }
            $teacher = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));
            $courseId = (int) ($row['id'] ?? 0);
            $courseUrl = !empty($row['slug'])
                ? $this->siteUrl('course?slug=' . (string) $row['slug'])
                : $this->siteUrl('course?id=' . $courseId);

            $preview = $previewByCourse[$courseId] ?? null;
            $previewLessonTitle = is_array($preview) ? trim((string) ($preview['lesson_title'] ?? '')) : '';
            $previewYoutubeUrl = is_array($preview) ? trim((string) ($preview['youtube_url'] ?? '')) : '';
            $previewVideoId = $this->youtubeVideoId($previewYoutubeUrl);
            $previewEmbedUrl = $this->youtubeEmbedUrl($previewYoutubeUrl);

            $totalMin = isset($row['total_min']) ? (int) $row['total_min'] : 0;
            $durationHours = $totalMin > 0
                ? round($totalMin / 60, 2)
                : ($row['duration_hours'] !== null ? (float) $row['duration_hours'] : null);

            $durationLabel = null;
            if ($totalMin > 0) {
                $durationLabel = \format_duration_minutes_hm($totalMin);
            } elseif ($durationHours !== null) {
                $minutes = (int) round($durationHours * 60);
                if ($minutes > 0) {
                    $durationLabel = \format_duration_minutes_hm($minutes);
                }
            }

            $rawLevel = trim((string) ($row['level'] ?? ''));
            $levelKey = isset($levelLabels[$rawLevel]) ? $rawLevel : '';
            $levelLabel = $levelKey !== '' ? $levelLabels[$levelKey] : ($rawLevel !== '' ? $rawLevel : 'Niveau');

            $data[] = [
                'id' => $courseId,
                'title' => (string) $row['title'],
                'is_owned' => !empty($row['is_owned']) ? 1 : 0,
                'is_favorite' => !empty($row['is_favorite']) ? 1 : 0,
                'is_in_cart' => isset($cartIds[$courseId]) ? 1 : 0,
                'created_at' => $row['created_at'] ?? null,
                'teacher' => $teacher !== '' ? $teacher : 'Enseignant',
                'price' => (int) $promo['price_xof'],
                'base_price' => (int) $promo['base_price_xof'],
                'promo_active' => !empty($promo['active']) ? 1 : 0,
                'promo_type' => (string) ($promo['promo_type'] ?? ''),
                'promo_value' => (float) ($promo['promo_value'] ?? 0),
                'promo_end_at' => $promo['end_at'] ?? null,
                'promo_end_ts' => !empty($promo['end_ts']) ? (int) $promo['end_ts'] : 0,
                'sales_count' => (int) ($row['total_sales'] ?? 0),
                'level' => $levelKey,
                'level_label' => $levelLabel,
                'rating' => $row['avg_rating'] !== null ? (float) $row['avg_rating'] : 0,
                'rating_count' => (int) ($row['rating_count'] ?? 0),
                'thumbnail' => $thumb,
                'category' => (string) ($row['category_name'] ?? 'Autre'),
                'description' => (string) ($row['description'] ?? ''),
                'duration_hours' => $durationHours,
                'duration_label' => $durationLabel,
                'url' => $courseUrl,
                'preview_url' => $courseUrl,
                'preview_lesson_title' => $previewLessonTitle !== '' ? $previewLessonTitle : null,
                'preview_youtube_url' => $previewYoutubeUrl !== '' ? $previewYoutubeUrl : null,
                'preview_video_id' => $previewVideoId,
                'preview_embed_url' => $previewEmbedUrl,
            ];
        }

        return [
            'status' => 200,
            'payload' => [
                'data' => $data,
                'meta' => ['count' => count($data)],
            ],
        ];
    }

    private function youtubeVideoId(?string $url): ?string
    {
        if (!$url) {
            return null;
        }
        $parts = parse_url($url);
        if (!$parts || empty($parts['host'])) {
            return null;
        }
        $host = (string) $parts['host'];
        if (str_contains($host, 'youtu.be')) {
            $videoId = trim((string) ($parts['path'] ?? ''), '/');
            return $videoId !== '' ? $videoId : null;
        }
        if (str_contains($host, 'youtube.com') && !empty($parts['query'])) {
            parse_str((string) $parts['query'], $query);
            if (!empty($query['v']) && is_string($query['v'])) {
                return $query['v'];
            }
        }
        return null;
    }

    private function youtubeEmbedUrl(?string $url): ?string
    {
        $videoId = $this->youtubeVideoId($url);
        if ($videoId === null || $videoId === '') {
            return null;
        }
        $safeVideoId = rawurlencode($videoId);
        return 'https://www.youtube-nocookie.com/embed/' . $safeVideoId
            . '?controls=1&modestbranding=1&rel=0&playsinline=1';
    }

}

