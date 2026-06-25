<?php
declare(strict_types=1);

namespace App\Services\Web;

use App\Core\Service;

use App\Models\ApiCatalogModel;
use App\Models\EnrollmentModel;
use App\Models\WebHomeModel;

final class HomeService extends Service
{
    private WebHomeModel $model;
    private EnrollmentModel $enrollments;

    public function __construct(WebHomeModel $model)
    {
        $this->model = $model;
        $this->enrollments = new EnrollmentModel($this->pdo());
    }

    /**
     * @return array<string,mixed>
     */
    public function buildViewData(int $limit = 12, string $canonicalPath = ''): array
    {
        $isLoggedIn = $this->isAuthenticated();
        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $current = $isLoggedIn ? $this->currentUser() : null;
        $currentUserId = null;
        if ($isLoggedIn && is_array($current)) {
            $candidateUserId = (int) ($current['id'] ?? 0);
            if ($candidateUserId > 0) {
                $currentUserId = $candidateUserId;
            }
        }
        $cartCount = count($_SESSION['cart']);
        $cartLookup = [];
        foreach ($_SESSION['cart'] as $cartId) {
            $courseId = (int) $cartId;
            if ($courseId > 0) {
                $cartLookup[$courseId] = true;
            }
        }
        $favoriteLookup = [];
        $basePath = rtrim(BASE_PATH, '/');
        $notifCount = 0;
        $supportUnreadCount = 0;
        $dashboardUrl = $this->siteUrl('student/dashboard');

        if ($isLoggedIn && is_array($current)) {
            $roles = json_decode($current['roles_json'] ?? '[]', true) ?: [];
            if (in_array('admin', $roles, true) || in_array('admin_secondary', $roles, true)) {
                $dashboardUrl = $this->siteUrl('admin/dashboard');
            } elseif (in_array('teacher', $roles, true)) {
                $dashboardUrl = $this->siteUrl('teacher/dashboard');
            } elseif (in_array('company_admin', $roles, true) || in_array('company_member', $roles, true)) {
                $dashboardUrl = $this->siteUrl('company/dashboard');
            }
            $notifCount = $this->model->countUnreadNotifications((int) $current['id']);
            $supportUnreadMessages = 0;
            $supportUnreadNotifications = 0;
            try {
                $supportUnreadMessages = $this->model->countUnreadSupportReplies((int) $current['id']);
            } catch (\Throwable $e) {
                $supportUnreadMessages = 0;
            }
            try {
                $supportUnreadNotifications = $this->model->countUnreadSupportNotifications((int) $current['id']);
            } catch (\Throwable $e) {
                $supportUnreadNotifications = 0;
            }
            $supportUnreadCount = max($supportUnreadMessages, $supportUnreadNotifications);

            try {
                $catalogModel = new ApiCatalogModel($this->pdo());
                if ($catalogModel->courseFavoritesTableReady()) {
                    foreach ($catalogModel->fetchFavoriteCourseIds((int) $current['id']) as $favoriteId) {
                        $courseId = (int) $favoriteId;
                        if ($courseId > 0) {
                            $favoriteLookup[$courseId] = true;
                        }
                    }
                }
            } catch (\Throwable $e) {
                $favoriteLookup = [];
            }
        }

        $siteLogoUrl = $this->siteLogoUrl();
        $footerText = $this->siteFooterText();
        if ($footerText === '') {
            $footerText = "© {year} \u{00c9}cole Num\u{00e9}rique - Catalogue public accessible à tous.";
        }
        $footerText = str_replace('{year}', date('Y'), $footerText);
        $publicAnnouncement = $this->formatPublicAnnouncement($this->model->fetchLatestPublicAnnouncement());
        $ssrCourses = $this->formatSsrCourses(
            $this->model->fetchPublicCoursesForHome($limit, $currentUserId),
            $cartLookup,
            $favoriteLookup,
            $isLoggedIn
        );
        $activeEnrollmentCount = $this->model->countActivePublicEnrollments();
        $homeTestimonials = $this->formatHomeTestimonials($this->model->fetchPublicTestimonials(6));
        $continueLearningItems = [];

        if ($currentUserId !== null && $currentUserId > 0) {
            try {
                $continueLearningItems = $this->formatContinueLearningItems(
                    $this->enrollments->findWithCourseProgressByUserId($currentUserId)
                );
            } catch (\Throwable $e) {
                $continueLearningItems = [];
            }
        }

        return [
            'isLoggedIn' => $isLoggedIn,
            'cartCount' => $cartCount,
            'basePath' => $basePath,
            'notifCount' => $notifCount,
            'supportUnreadCount' => $supportUnreadCount,
            'dashboardUrl' => $dashboardUrl,
            'siteLogoUrl' => $siteLogoUrl,
            'footerText' => $footerText,
            'publicAnnouncement' => $publicAnnouncement,
            'ssrCourses' => $ssrCourses,
            'continueLearningItems' => $continueLearningItems,
            'activeEnrollmentCount' => $activeEnrollmentCount,
            'homeTestimonials' => $homeTestimonials,
            'seoCanonicalUrl' => $this->absUrl($canonicalPath),
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function formatSsrCourses(
        array $rows,
        array $cartLookup = [],
        array $favoriteLookup = [],
        bool $isLoggedIn = false
    ): array
    {
        $items = [];
        foreach ($rows as $row) {
            $courseId = (int) ($row['id'] ?? 0);
            if ($courseId < 1) {
                continue;
            }

            $slug = trim((string) ($row['slug'] ?? ''));
            $courseUrl = $slug !== ''
                ? $this->siteUrl('course?slug=' . rawurlencode($slug))
                : $this->siteUrl('course?id=' . $courseId);
            $checkoutUrl = $this->siteUrl('checkout?course=' . $courseId);
            $buyUrl = $isLoggedIn
                ? $checkoutUrl
                : $this->siteUrl('login?return=' . urlencode($checkoutUrl));

            $title = $this->normalizeLabel((string) ($row['title'] ?? ''), 'Cours numérique');
            $teacherFirstName = $this->normalizePersonName((string) ($row['first_name'] ?? ''));
            $teacherLastName = $this->normalizePersonName((string) ($row['last_name'] ?? ''));
            $teacherName = $this->normalizePersonName((string) (($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')));
            if ($teacherName === '') {
                $teacherName = 'Formateur';
            }

            [$categoryLabel, $categoryKey] = $this->resolveDisplayCategory(
                (string) ($row['category_name'] ?? ''),
                $teacherName,
                $teacherFirstName,
                $teacherLastName,
                $title,
                (string) ($row['description'] ?? '')
            );

            $coverPath = trim((string) ($row['cover_path'] ?? ''));
            if ($coverPath !== '' && !preg_match('#^https?://#i', $coverPath)) {
                $coverPath = $this->siteUrl(ltrim($coverPath, '/'));
            }

            $priceBaseXof = max(0, (int) ($row['price_xof'] ?? 0));
            $promoState = $this->resolvePromoState(
                $priceBaseXof,
                (string) ($row['promo_type'] ?? ''),
                is_numeric($row['promo_value'] ?? null) ? (float) $row['promo_value'] : 0.0,
                (string) ($row['promo_start_at'] ?? ''),
                (string) ($row['promo_end_at'] ?? '')
            );

            $totalMinutes = (int) ($row['total_min'] ?? 0);
            $durationHours = (float) ($row['duration_hours'] ?? 0);
            $durationMinutes = $totalMinutes > 0 ? $totalMinutes : (int) round(max(0, $durationHours) * 60);
            $objectives = $this->courseFieldAsList($row['objectives'] ?? null);
            $prerequisites = $this->courseFieldAsList($row['prerequisites'] ?? null);
            $equipment = $this->courseFieldAsList($row['equipment'] ?? null);
            $deliveryMode = strtolower(trim((string) ($row['delivery_mode'] ?? '')));
            if (!in_array($deliveryMode, ['online', 'presential', 'onsite'], true)) {
                $deliveryMode = '';
            }
            $courseLocation = $this->cleanDisplayText((string) ($row['course_location'] ?? ''));

            $items[] = [
                'id' => $courseId,
                'title' => $title,
                'url' => $courseUrl,
                'buyUrl' => $buyUrl,
                'isInCart' => isset($cartLookup[$courseId]) ? 1 : 0,
                'isFavorite' => isset($favoriteLookup[$courseId]) ? 1 : 0,
                'description' => $this->shortText((string) ($row['description'] ?? ''), 170),
                'cover' => $coverPath,
                'coverAlt' => $this->cleanDisplayText((string) ($row['cover_alt'] ?? ''), 'Couverture du cours ' . $title),
                'teacher' => $teacherName,
                'category' => $categoryLabel,
                'categoryKey' => $categoryKey,
                'enrolledCount' => max(0, (int) ($row['enrolled_count'] ?? 0)),
                'rating' => round((float) ($row['avg_rating'] ?? 0), 1),
                'ratingCount' => (int) ($row['rating_count'] ?? 0),
                'priceXof' => (int) ($promoState['finalPriceXof'] ?? $priceBaseXof),
                'basePriceXof' => $priceBaseXof,
                'promoActive' => !empty($promoState['active']),
                'promoDiscountLabel' => (string) ($promoState['discountLabel'] ?? ''),
                'promoEndAt' => (string) ($row['promo_end_at'] ?? ''),
                'couponAvailable' => !empty($row['has_coupon']),
                'durationLabel' => $this->durationLabel($totalMinutes, $durationHours),
                'durationMin' => $durationMinutes,
                'level' => strtolower(trim((string) ($row['level'] ?? ''))),
                'objectives' => $objectives,
                'prerequisites' => $prerequisites,
                'equipment' => $equipment,
                'deliveryMode' => $deliveryMode,
                'courseLocation' => $courseLocation,
                'createdAt' => (string) ($row['created_at'] ?? ''),
            ];
        }

        return $items;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function formatContinueLearningItems(array $rows): array
    {
        $items = [];

        foreach ($rows as $row) {
            if (trim((string) ($row['enroll_status'] ?? '')) !== 'active') {
                continue;
            }

            $title = $this->normalizeLabel((string) ($row['title'] ?? ''), 'Cours');
            $teacherName = $this->normalizePersonName((string) (($row['teacher_first_name'] ?? '') . ' ' . ($row['teacher_last_name'] ?? '')));
            if ($teacherName === '') {
                $teacherName = 'Formateur';
            }

            $totalLessons = max(0, (int) ($row['total_lessons'] ?? 0));
            $doneLessons = max(0, (int) ($row['done_lessons'] ?? 0));
            $progressPct = $totalLessons > 0
                ? (int) round(($doneLessons / max(1, $totalLessons)) * 100)
                : 0;
            $progressPct = max(0, min(100, $progressPct));
            $remainingLessons = max(0, $totalLessons - $doneLessons);

            $cover = trim((string) ($row['cover_path'] ?? ''));
            if ($cover !== '' && !preg_match('#^https?://#i', $cover)) {
                $cover = $this->siteUrl(ltrim($cover, '/'));
            }

            $levelLabel = match (strtolower(trim((string) ($row['level'] ?? '')))) {
                'beginner' => 'Débutant',
                'intermediate' => 'Intermédiaire',
                'advanced' => 'Avancé',
                default => 'Tous niveaux',
            };

            $summary = $totalLessons > 0
                ? $doneLessons . '/' . $totalLessons . ' leçons terminées'
                : 'Progression disponible après démarrage';

            if ($progressPct >= 100) {
                $statusLabel = 'Terminé';
            } elseif ($progressPct > 0) {
                $statusLabel = 'À reprendre';
            } else {
                $statusLabel = 'À commencer';
            }

            $items[] = [
                'title' => $title,
                'teacher' => $teacherName,
                'progressPct' => $progressPct,
                'doneLessons' => $doneLessons,
                'totalLessons' => $totalLessons,
                'remainingLessons' => $remainingLessons,
                'summary' => $summary,
                'statusLabel' => $statusLabel,
                'levelLabel' => $levelLabel,
                'url' => $this->resolveWatchCourseUrl($row),
                'cover' => $cover,
                'coverAlt' => $this->cleanDisplayText((string) ($row['cover_alt'] ?? ''), 'Cours ' . $title),
            ];

            if (count($items) >= 3) {
                break;
            }
        }

        return $items;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function formatHomeTestimonials(array $rows): array
    {
        $items = [];

        foreach ($rows as $row) {
            $quote = $this->cleanDisplayText((string) ($row['comment'] ?? ''));
            $rating = max(0.0, min(5.0, (float) ($row['rating'] ?? 0)));
            $name = $this->normalizePersonName((string) (($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')));
            $courseTitle = $this->normalizeLabel((string) ($row['course_title'] ?? ''), '');

            if ($quote === '' || $rating <= 0 || $name === '') {
                continue;
            }

            $items[] = [
                'name' => $name,
                'role' => $courseTitle !== '' ? 'Avis sur ' . $courseTitle : 'Avis vérifié',
                'quote' => $quote,
                'rating' => $rating,
                'initials' => $this->buildInitials($name),
            ];
        }

        return $items;
    }

    /**
     * @return list<string>
     */
    private function courseFieldAsList(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        if (is_array($value)) {
            $items = $value;
        } else {
            $raw = trim((string) $value);
            if ($raw === '') {
                return [];
            }
            $decoded = json_decode($raw, true);
            $items = is_array($decoded) ? $decoded : preg_split('/\r\n|\r|\n/', $raw);
            if (!is_array($items)) {
                return [];
            }
        }

        $items = array_map(static fn($item): string => trim((string) $item), $items);
        return array_values(array_filter($items, static fn(string $item): bool => $item !== ''));
    }

    /**
     * @return array{active:bool,finalPriceXof:int,discountLabel:string}
     */
    private function resolvePromoState(
        int $basePriceXof,
        string $promoType,
        float $promoValue,
        string $promoStartAt,
        string $promoEndAt
    ): array {
        $promoType = strtolower(trim($promoType));
        $startTs = $promoStartAt !== '' ? (int) strtotime($promoStartAt) : 0;
        $endTs = $promoEndAt !== '' ? (int) strtotime($promoEndAt) : 0;
        $now = time();

        $hasValue = false;
        if ($promoType === 'amount') {
            $hasValue = $promoValue >= 0;
        } elseif ($promoType === 'percent') {
            $hasValue = $promoValue > 0;
        }

        $isActive = $promoType !== ''
            && $hasValue
            && $endTs > $now
            && ($startTs === 0 || $startTs <= $now)
            && $basePriceXof > 0;

        $finalPriceXof = $basePriceXof;
        if ($isActive) {
            if ($promoType === 'amount') {
                $finalPriceXof = (int) round($promoValue);
            } elseif ($promoType === 'percent') {
                $finalPriceXof = (int) round($basePriceXof * (1 - ($promoValue / 100)));
            }
            if ($finalPriceXof < 0) {
                $finalPriceXof = 0;
            }
        }

        if (!$isActive || $finalPriceXof >= $basePriceXof) {
            return [
                'active' => false,
                'finalPriceXof' => $basePriceXof,
                'discountLabel' => '',
            ];
        }

        $discountLabel = '';
        if ($promoType === 'percent') {
            $discountLabel = '-' . rtrim(rtrim(number_format($promoValue, 2, '.', ''), '0'), '.') . '%';
        } elseif ($promoType === 'amount') {
            $discountLabel = '-' . number_format($basePriceXof - $finalPriceXof, 0, ',', ' ') . ' FCFA';
        }

        return [
            'active' => true,
            'finalPriceXof' => $finalPriceXof,
            'discountLabel' => $discountLabel,
        ];
    }

    private function shortText(string $text, int $maxLength): string
    {
        $normalized = $this->cleanDisplayText($text);
        if ($normalized === '') {
            return 'Découvrez ce cours et commencez à progresser dès maintenant.';
        }
        if (\function_exists('mb_strlen') && \function_exists('mb_substr')) {
            if (mb_strlen($normalized) <= $maxLength) {
                return $normalized;
            }
            return rtrim(mb_substr($normalized, 0, $maxLength - 3)) . '...';
        }
        if (strlen($normalized) <= $maxLength) {
            return $normalized;
        }
        return rtrim(substr($normalized, 0, $maxLength - 3)) . '...';
    }

    /**
     * @param array<string,mixed>|null $row
     * @return array<string,mixed>
     */
    private function formatPublicAnnouncement(?array $row): array
    {
        if (!is_array($row) || $row === []) {
            return [];
        }

        $id = max(0, (int) ($row['id'] ?? 0));
        $title = $this->cleanDisplayText((string) ($row['title'] ?? ''), 'Annonce');
        $summary = $this->cleanDisplayText((string) ($row['summary'] ?? ''));
        $body = $this->cleanDisplayText((string) ($row['body'] ?? ''));
        $message = $summary !== '' ? $summary : $this->shortText($body, 180);
        $url = trim((string) ($row['action_url'] ?? ''));
        if ($this->isNonPublicAnnouncementUrl($url)) {
            $url = '';
        }

        if ($title === '' && $message === '') {
            return [];
        }

        return [
            'id' => $id,
            'title' => $title !== '' ? $title : 'Annonce',
            'message' => $message,
            'url' => $url,
            'linkLabel' => 'En savoir plus',
        ];
    }

    private function durationLabel(int $totalMinutes, float $durationHours): ?string
    {
        $minutes = $totalMinutes > 0 ? $totalMinutes : (int) round(max(0, $durationHours) * 60);
        if ($minutes <= 0) {
            return null;
        }
        return \format_duration_minutes_hm($minutes);
    }

    private function cleanDisplayText(string $text, string $fallback = ''): string
    {
        $decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalized = trim((string) preg_replace('/\s+/u', ' ', strip_tags($decoded)));
        return $normalized !== '' ? $normalized : $fallback;
    }

    private function normalizeLabel(string $text, string $fallback = ''): string
    {
        $normalized = $this->cleanDisplayText($text, $fallback);
        if ($normalized === '') {
            return '';
        }

        if (\function_exists('mb_strtolower') && \function_exists('mb_convert_case')) {
            if ($normalized === mb_strtolower($normalized, 'UTF-8')) {
                return mb_convert_case($normalized, MB_CASE_TITLE, 'UTF-8');
            }
            return $normalized;
        }

        if ($normalized === strtolower($normalized)) {
            return ucwords($normalized);
        }

        return $normalized;
    }

    private function normalizePersonName(string $text): string
    {
        return $this->normalizeLabel($text);
    }

    private function buildInitials(string $text): string
    {
        $text = trim($this->cleanDisplayText($text));
        if ($text === '') {
            return 'EN';
        }

        $parts = preg_split('/\s+/u', $text) ?: [];
        $initials = '';
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            $initial = function_exists('mb_substr')
                ? mb_substr($part, 0, 1, 'UTF-8')
                : substr($part, 0, 1);
            $initials .= strtoupper($initial);
            if (strlen($initials) >= 2) {
                break;
            }
        }

        return $initials !== '' ? $initials : 'EN';
    }

    /**
     * @return array{0:string,1:string}
     */
    private function resolveDisplayCategory(
        string $rawCategoryLabel,
        string $teacherName,
        string $teacherFirstName,
        string $teacherLastName,
        string $title,
        string $description = ''
    ): array {
        $rawCategoryLabel = $this->normalizeLabel($rawCategoryLabel, '');
        $categoryKey = trim($this->normalizedKey($rawCategoryLabel));

        if (
            $categoryKey !== ''
            && !$this->looksLikeTeacherNameCategory($categoryKey, [$teacherName, $teacherFirstName, $teacherLastName])
            && $this->looksLikeUsableCategory($rawCategoryLabel)
        ) {
            return [$rawCategoryLabel, $categoryKey];
        }

        $derivedCategory = $this->inferCategoryFromCourseText($title . ' ' . $description);
        return [$derivedCategory, trim($this->normalizedKey($derivedCategory))];
    }

    private function looksLikeTeacherNameCategory(string $categoryKey, array $teacherNames): bool
    {
        foreach ($teacherNames as $teacherName) {
            $teacherKey = trim($this->normalizedKey((string) $teacherName));
            if ($teacherKey === '' || $categoryKey === '') {
                continue;
            }

            if ($categoryKey === $teacherKey || str_contains($teacherKey, $categoryKey) || str_contains($categoryKey, $teacherKey)) {
                return true;
            }

            similar_text($categoryKey, $teacherKey, $similarity);
            if ($similarity >= 72.0) {
                return true;
            }
        }

        return false;
    }

    private function looksLikeUsableCategory(string $label): bool
    {
        $key = trim($this->normalizedKey($label));
        if ($key === '') {
            return false;
        }

        foreach ($this->categoryKeywordGroups() as $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($key, $keyword)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function inferCategoryFromCourseText(string $text): string
    {
        $key = trim($this->normalizedKey($text));
        if ($key === '') {
            return 'Formation';
        }

        foreach ($this->categoryKeywordGroups() as $label => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($key, $keyword)) {
                    return $label;
                }
            }
        }

        return 'Formation';
    }

    /**
     * @return array<string,list<string>>
     */
    private function categoryKeywordGroups(): array
    {
        return [
            'Développement' => ['php', 'python', 'javascript', 'java', 'html', 'css', 'sql', 'code', 'programm', 'develop', 'web', 'laravel'],
            'Bureautique' => ['excel', 'word', 'powerpoint', 'office', 'bureau', 'document', 'tableur'],
            'Marketing' => ['marketing', 'copy', 'email', 'seo', 'vente', 'affiliation', 'publicit', 'communication', 'digital'],
            'Comptabilité' => ['compta', 'comptabil', 'finance', 'facturation', 'gestion'],
            'Design' => ['design', 'canva', 'graph', 'ui', 'ux', 'maquette', 'creatif'],
            'Journalisme' => ['journal', 'redaction', 'presse', 'media'],
            'Data' => ['data', 'analyse', 'dashboard', 'bi', 'statist', 'machine learning', 'intelligence artificielle', 'ia'],
        ];
    }

    private function normalizedKey(string $text): string
    {
        $value = $this->cleanDisplayText($text);
        if ($value === '') {
            return '';
        }

        if (\function_exists('iconv')) {
            $asciiValue = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if (is_string($asciiValue) && $asciiValue !== '') {
                $value = $asciiValue;
            }
        }

        $value = \function_exists('mb_strtolower')
            ? mb_strtolower($value, 'UTF-8')
            : strtolower($value);

        return trim((string) preg_replace('/[^a-z0-9]+/i', ' ', $value));
    }

    /**
     * @param array<string,mixed> $course
     */
    private function resolveWatchCourseUrl(array $course): string
    {
        $slug = trim((string) ($course['slug'] ?? ''));
        if ($slug !== '') {
            return $this->siteUrl('course/watch?slug=' . urlencode($slug));
        }

        $courseId = max(0, (int) ($course['id'] ?? $course['course_id'] ?? 0));
        if ($courseId > 0) {
            return $this->siteUrl('course/watch?id=' . $courseId);
        }

        return $this->siteUrl('student/my_courses');
    }

    private function isNonPublicAnnouncementUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '') {
            return true;
        }

        $normalized = str_replace('&amp;', '&', strtolower($url));
        return str_contains($normalized, '/notifications?scope=admin')
            || str_contains($normalized, '/admin/notifications')
            || str_contains($normalized, '/admin/');
    }
}

