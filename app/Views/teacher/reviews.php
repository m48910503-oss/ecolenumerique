<?php
declare(strict_types=1);

$teacherUser = is_array($user ?? null) ? $user : [];
$teacherDisplayName = dashboard_user_display_name($teacherUser, 'Enseignant');
$teacherEmail = trim((string) ($teacherUser['email'] ?? ''));
$teacherAvatarUrl = dashboard_user_avatar_url($teacherUser);
$teacherInitials = dashboard_user_initials($teacherUser, 'EN');

$summary = is_array($summary ?? null) ? $summary : [
    'total' => 0,
    'visible' => 0,
    'hidden' => 0,
    'avg' => 0.0,
    'reply_count' => 0,
    'response_rate' => 0,
    'pending' => 0,
];
$courseCards = array_values(is_array($courseCards ?? null) ? $courseCards : []);
$allVisibleReviews = array_values(is_array($allVisibleReviews ?? null) ? $allVisibleReviews : []);
$detailRows = array_values(is_array($detailRows ?? null) ? $detailRows : []);
$ratingDistribution = array_values(is_array($ratingDistribution ?? null) ? $ratingDistribution : []);
$filters = is_array($filters ?? null) ? $filters : [];
$errors = array_values(is_array($errors ?? null) ? $errors : []);
$successMessage = trim((string) ($successMessage ?? ''));
$teacherReviewsNotice = trim((string) ($teacherReviewsNotice ?? ''));
$moderationReady = !empty($moderationReady);
$replyReady = !empty($replyReady);
$detailMode = !empty($detailMode);
$selectedCourseCard = is_array($selectedCourseCard ?? null) ? $selectedCourseCard : null;
$courseId = max(0, (int) ($filters['courseId'] ?? 0));
$overviewUrl = site_url('teacher/reviews');
$selectedCourseTitle = trim((string) ($selectedCourseCard['course_title'] ?? ''));
if ($selectedCourseTitle === '' && $detailMode && $courseId > 0) {
    $selectedCourseTitle = 'Cours #' . $courseId;
}

$summaryTotal = max(0, (int) ($summary['total'] ?? count($allVisibleReviews)));
$summaryResponseRate = max(0, min(100, (int) ($summary['response_rate'] ?? 0)));
$summaryPending = max(0, (int) ($summary['pending'] ?? 0));
$ratingFilterValue = max(0, min(5, (int) ($filters['ratingMin'] ?? 0)));
$searchFilterValue = trim((string) ($filters['search'] ?? ''));

$formatCount = static fn (int|float $value): string => number_format((float) $value, 0, ',', ' ');
$formatDateLabel = static function (string $value, string $fallback = '—'): string {
    $value = trim($value);
    if ($value === '') {
        return $fallback;
    }

    $ts = strtotime($value);
    if ($ts === false) {
        return $fallback;
    }

    $months = [
        1 => 'janv.',
        2 => 'févr.',
        3 => 'mars',
        4 => 'avr.',
        5 => 'mai',
        6 => 'juin',
        7 => 'juil.',
        8 => 'août',
        9 => 'sept.',
        10 => 'oct.',
        11 => 'nov.',
        12 => 'déc.',
    ];

    $monthIndex = (int) date('n', $ts);
    $month = (string) ($months[$monthIndex] ?? date('m', $ts));
    return date('j', $ts) . ' ' . $month . ' ' . date('Y', $ts);
};

$renderStars = static function (float $rating, string $wrapperClass = 'text-yellow-400 text-sm') : string {
    $rating = max(0.0, min(5.0, $rating));
    $fullStars = (int) floor($rating);
    $hasHalf = ($rating - $fullStars) >= 0.5 && $fullStars < 5;
    $html = '<span class="' . e($wrapperClass) . '">';

    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $fullStars) {
            $html .= '<i class="fas fa-star" aria-hidden="true"></i>';
            continue;
        }

        if ($hasHalf && $i === $fullStars + 1) {
            $html .= '<i class="fas fa-star-half-alt" aria-hidden="true"></i>';
            continue;
        }

        $html .= '<i class="far fa-star text-gray-300" aria-hidden="true"></i>';
    }

    $html .= '</span>';
    return $html;
};

$makeSearchBlob = static function (array $review): string {
    $parts = [
        trim((string) ($review['reviewer_label'] ?? '')),
        trim((string) ($review['course_label'] ?? '')),
        trim((string) ($review['comment'] ?? '')),
    ];
    $blob = strtolower(trim(implode(' ', array_filter($parts, static fn (string $value): bool => $value !== ''))));
    return preg_replace('/\s+/', ' ', $blob) ?? $blob;
};

$sidebarLinks = [
    [
        'label' => 'Tableau de bord',
        'url' => site_url('teacher/dashboard'),
        'icon' => 'home',
        'active' => false,
    ],
    [
        'label' => 'Mes formations',
        'url' => site_url('teacher/courses'),
        'icon' => 'book',
        'active' => false,
    ],
    [
        'label' => 'Avis & Notes',
        'url' => site_url('teacher/reviews'),
        'icon' => 'star',
        'active' => true,
    ],
    [
        'label' => 'Paramètres',
        'url' => site_url('profile?space=teacher'),
        'icon' => 'cog',
        'active' => false,
    ],
];

$reviewableCourseCards = array_values(array_filter(
    $courseCards,
    static fn (array $card): bool => strtolower(trim((string) ($card['course_status'] ?? ''))) === 'approved'
));
$activeCourseCards = array_values(array_filter(
    $reviewableCourseCards,
    static fn (array $card): bool => trim((string) ($card['deleted_at'] ?? '')) === '' && empty($card['is_archived'])
));
$archivedCourseCards = array_values(array_filter(
    $reviewableCourseCards,
    static fn (array $card): bool => trim((string) ($card['deleted_at'] ?? '')) !== '' || !empty($card['is_archived'])
));
$activeReviewedCards = array_values(array_filter(
    $activeCourseCards,
    static fn (array $card): bool => max(0, (int) ($card['review_count'] ?? 0)) > 0
));
$activePendingCards = array_values(array_filter(
    $activeCourseCards,
    static fn (array $card): bool => max(0, (int) ($card['review_count'] ?? 0)) === 0
));
$archivedPendingCards = array_values(array_filter(
    $archivedCourseCards,
    static fn (array $card): bool => max(0, (int) ($card['review_count'] ?? 0)) === 0
));
$coursesWithoutReviewsCount = count($activePendingCards) + count($archivedPendingCards);
$archivedCourseCount = count($archivedCourseCards);
$formatEntityCount = static function (int $count, string $singular, ?string $plural = null) use ($formatCount): string {
    return $formatCount($count) . ' ' . ($count > 1 ? ($plural ?? ($singular . 's')) : $singular);
};
$pendingReviewsByCourseId = [];
$latestReviewByCourseId = [];
foreach ($allVisibleReviews as $reviewRow) {
    $reviewCourseId = max(0, (int) ($reviewRow['course_id'] ?? 0));
    if ($reviewCourseId < 1) {
        continue;
    }
    if (trim((string) ($reviewRow['teacher_reply_text'] ?? '')) === '') {
        $pendingReviewsByCourseId[$reviewCourseId] = max(0, (int) ($pendingReviewsByCourseId[$reviewCourseId] ?? 0)) + 1;
    }
    $reviewCreatedAt = trim((string) ($reviewRow['created_at'] ?? ''));
    if (
        $reviewCreatedAt !== ''
        && (
            !isset($latestReviewByCourseId[$reviewCourseId])
            || strtotime($reviewCreatedAt) > strtotime((string) ($latestReviewByCourseId[$reviewCourseId]['created_at'] ?? ''))
        )
    ) {
        $latestReviewByCourseId[$reviewCourseId] = $reviewRow;
    }
}
$firstCourseWithoutReview = $activePendingCards[0] ?? null;
$firstCourseWithPendingReply = null;
foreach ($activeReviewedCards as $card) {
    $candidateCourseId = max(0, (int) ($card['course_id'] ?? 0));
    if ($candidateCourseId > 0 && max(0, (int) ($pendingReviewsByCourseId[$candidateCourseId] ?? 0)) > 0) {
        $firstCourseWithPendingReply = $card;
        break;
    }
}
$quickReadText = $coursesWithoutReviewsCount > 0
    ? $formatEntityCount($coursesWithoutReviewsCount, 'cours attend', 'cours attendent') . ' encore leur premier avis. Priorité : encourager les étudiants actifs à laisser un retour.'
    : ($summaryPending > 0
        ? $formatEntityCount($summaryPending, 'avis reste', 'avis restent') . ' sans réponse. Priorité : traiter les retours récents.'
        : 'Tous les avis visibles ont reçu une réponse. Continuez à suivre les nouveaux retours.');
$recommendedActionUrl = '';
$recommendedActionLabel = '';
if (is_array($firstCourseWithPendingReply)) {
    $recommendedCourseId = max(0, (int) ($firstCourseWithPendingReply['course_id'] ?? 0));
    $recommendedActionUrl = $overviewUrl . '?course_id=' . $recommendedCourseId . '&detail=1';
    $recommendedActionLabel = 'Répondre aux avis';
} elseif (is_array($firstCourseWithoutReview)) {
    $recommendedCourseId = max(0, (int) ($firstCourseWithoutReview['course_id'] ?? 0));
    $recommendedActionUrl = site_url('teacher/course_preview?id=' . $recommendedCourseId);
    $recommendedActionLabel = 'Voir le cours';
}
$renderCourseCard = static function (array $courseCard) use ($overviewUrl, $formatCount, $formatDateLabel, $pendingReviewsByCourseId): string {
    $courseCardId = max(0, (int) ($courseCard['course_id'] ?? 0));
    $courseTitle = trim((string) ($courseCard['course_title'] ?? ('Cours #' . $courseCardId)));
    $courseAvg = number_format((float) ($courseCard['avg_rating'] ?? 0.0), 1, '.', '');
    $courseReviews = max(0, (int) ($courseCard['review_count'] ?? 0));
    $courseStudents = max(0, (int) ($courseCard['student_count'] ?? 0));
    $pendingReplies = max(0, (int) ($pendingReviewsByCourseId[$courseCardId] ?? 0));
    $courseArchived = !empty($courseCard['is_archived']) || trim((string) ($courseCard['deleted_at'] ?? '')) !== '';
    $courseLatestLabel = $formatDateLabel(trim((string) ($courseCard['latest_review_at'] ?? '')), 'Aucun avis');
    $courseDetailUrl = $overviewUrl . '?course_id=' . $courseCardId . '&detail=1';
    $coursePreviewUrl = site_url('teacher/course_preview?id=' . $courseCardId);
    $coursePrimaryUrl = $courseReviews > 0 ? $courseDetailUrl : $coursePreviewUrl;
    $coursePrimaryLabel = $courseReviews > 0 ? 'Voir les avis' : 'Voir le cours';
    $coursePrimaryExtraClass = $courseReviews > 0 ? ' view-reviews-btn' : '';
    $courseMetaLabel = '';
    if ($courseReviews > 0) {
        $courseMetaLabel = 'Dernier avis le ' . $courseLatestLabel;
    } elseif ($courseArchived) {
        $courseMetaLabel = 'Archivé sans avis';
    }
    $courseShellClass = 'bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border hover-lift cursor-pointer';
    if ($courseArchived) {
        $courseShellClass .= ' border-slate-200 bg-slate-50/70 dark:border-slate-700 dark:bg-gray-800/70';
    } elseif ($courseReviews > 0) {
        $courseShellClass .= ' border-gray-100 dark:border-gray-700';
    } else {
        $courseShellClass .= ' border-dashed border-gray-200 dark:border-gray-700';
    }

    ob_start();
    ?>
    <article
      class="<?php echo e($courseShellClass); ?> review-course-card"
      data-course-card
      data-course-id="<?php echo e((string) $courseCardId); ?>"
      data-course-review-count="<?php echo e((string) $courseReviews); ?>"
      data-course-pending-replies="<?php echo e((string) $pendingReplies); ?>"
      data-course-avg-rating="<?php echo e($courseAvg); ?>"
      data-course-search-text="<?php echo e(strtolower($courseTitle)); ?>"
    >
      <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
          <h3 class="font-semibold text-gray-900 dark:text-white truncate"><?php echo e($courseTitle); ?></h3>
          <?php if ($courseMetaLabel !== ''): ?>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><?php echo e($courseMetaLabel); ?></p>
          <?php endif; ?>
        </div>
        <?php if ($courseArchived): ?>
          <span class="shrink-0 inline-flex items-center rounded-full bg-slate-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:bg-slate-700/60 dark:text-slate-300">Archivé</span>
        <?php elseif ($courseReviews === 0): ?>
          <span class="shrink-0 inline-flex items-center rounded-full bg-amber-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-amber-700 dark:bg-amber-900/30 dark:text-amber-200">À encourager</span>
        <?php elseif ($pendingReplies > 0): ?>
          <span class="shrink-0 inline-flex items-center rounded-full bg-orange-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-orange-700 dark:bg-orange-900/30 dark:text-orange-200"><?php echo e($formatCount($pendingReplies)); ?> sans réponse</span>
        <?php endif; ?>
      </div>
      <div class="flex items-center justify-between mt-4">
        <div class="flex items-end gap-2">
          <?php if ($courseReviews > 0): ?>
            <span class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo e($courseAvg); ?></span>
            <span class="text-yellow-400 mb-1" aria-hidden="true">★</span>
          <?php else: ?>
            <span class="text-2xl font-bold text-gray-900 dark:text-white">0</span>
            <span class="mb-1 text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">avis</span>
          <?php endif; ?>
        </div>
        <span class="text-xs text-gray-500 dark:text-gray-400"><?php echo e($courseReviews > 0 ? $formatCount($courseReviews) . ' avis' : 'Aucun avis'); ?></span>
      </div>
      <div class="mt-3 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
        <span><i class="fas fa-user-graduate mr-1" aria-hidden="true"></i><?php echo e($formatCount($courseStudents)); ?> étudiants</span>
      </div>
      <div class="mt-4">
        <a
          href="<?php echo e($coursePrimaryUrl); ?>"
          class="w-full inline-flex items-center justify-center py-2 border border-primary-200 dark:border-primary-800 text-primary-600 dark:text-primary-400 rounded-lg text-sm font-medium hover:bg-primary-50 dark:hover:bg-primary-900/20 transition<?php echo $coursePrimaryExtraClass; ?>"
          data-course-id="<?php echo e((string) $courseCardId); ?>"
        >
          <?php echo e($coursePrimaryLabel); ?>
        </a>
      </div>
    </article>
    <?php
    return (string) ob_get_clean();
};

$clientCourses = array_map(
    static function (array $card) use ($overviewUrl, $pendingReviewsByCourseId): array {
        $courseId = max(0, (int) ($card['course_id'] ?? 0));
        $deletedAt = trim((string) ($card['deleted_at'] ?? ''));
        return [
            'course_id' => $courseId,
            'course_title' => trim((string) ($card['course_title'] ?? ('Cours #' . $courseId))),
            'course_status' => trim((string) ($card['course_status'] ?? '')),
            'review_count' => max(0, (int) ($card['review_count'] ?? 0)),
            'avg_rating' => (float) ($card['avg_rating'] ?? 0.0),
            'pending_reply_count' => max(0, (int) ($pendingReviewsByCourseId[$courseId] ?? 0)),
            'latest_review_at' => trim((string) ($card['latest_review_at'] ?? '')),
            'student_count' => max(0, (int) ($card['student_count'] ?? 0)),
            'deleted_at' => $deletedAt,
            'is_archived' => !empty($card['is_archived']) || $deletedAt !== '',
            'detail_url' => $overviewUrl . '?course_id=' . $courseId . '&detail=1',
        ];
    },
    $reviewableCourseCards
);

$clientReviews = array_map(
    static function (array $review) use ($makeSearchBlob): array {
        return [
            'review_id' => max(0, (int) ($review['id'] ?? 0)),
            'course_id' => max(0, (int) ($review['course_id'] ?? 0)),
            'course_title' => trim((string) ($review['course_label'] ?? '')),
            'reviewer_label' => trim((string) ($review['reviewer_label'] ?? '')),
            'reviewer_initials' => trim((string) ($review['reviewer_initials'] ?? '')),
            'reviewer_avatar_url' => trim((string) ($review['reviewer_avatar_url'] ?? '')),
            'created_at_label' => trim((string) ($review['created_at_label'] ?? '')),
            'rating_value' => max(1, min(5, (int) ($review['rating_value'] ?? 0))),
            'comment' => trim((string) ($review['comment'] ?? '')),
            'teacher_reply_text' => trim((string) ($review['teacher_reply_text'] ?? '')),
            'teacher_reply_at_label' => trim((string) ($review['teacher_reply_at_label'] ?? '')),
            'teacher_reply_by_label' => trim((string) ($review['teacher_reply_by_label'] ?? '')),
            'search_blob' => $makeSearchBlob($review),
        ];
    },
    $allVisibleReviews
);

$teacherReviewsClientData = [
    'overviewUrl' => $overviewUrl,
    'detailBaseUrl' => $overviewUrl . '?detail=1&course_id=',
    'initialCourseId' => $detailMode ? $courseId : 0,
    'detailMode' => $detailMode,
    'moderationReady' => $moderationReady,
    'replyReady' => $replyReady,
    'csrfToken' => view_csrf_token(),
    'ratingFilter' => $ratingFilterValue,
    'searchFilter' => $searchFilterValue,
    'summary' => [
        'total' => $summaryTotal,
        'avg' => (float) ($summary['avg'] ?? 0.0),
        'response_rate' => $summaryResponseRate,
        'pending' => $summaryPending,
    ],
    'courses' => $clientCourses,
    'reviews' => $clientReviews,
];

$teacherReviewsClientDataJson = json_encode(
    $teacherReviewsClientData,
    JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
    | JSON_HEX_TAG
    | JSON_HEX_AMP
    | JSON_HEX_APOS
    | JSON_HEX_QUOT
    | JSON_INVALID_UTF8_SUBSTITUTE
);
if (!is_string($teacherReviewsClientDataJson) || $teacherReviewsClientDataJson === '') {
    $teacherReviewsClientDataJson = '{}';
}

?>
<!DOCTYPE html>
<html lang="fr" class="light" data-default-theme="light" data-view="teacher/reviews">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="app-global-assets" content="off">
  <title>Avis & Notes · Enseignant · École Numérique</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="<?php echo e(site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()))); ?>"></script>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="<?php echo e(site_url('assets/css/teacher-dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/teacher-dashboard.css') ?: time()))); ?>">
  <script src="<?php echo e(site_url('assets/js/sidebar_toggle.js')); ?>" defer></script>
  <script src="<?php echo e(site_url('assets/js/teacher_dashboard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/teacher_dashboard.js') ?: time()))); ?>" defer></script>
  <style>
    .hover-lift { transition: transform 0.25s ease, box-shadow 0.25s ease; }
    .hover-lift:hover { transform: translateY(-2px); box-shadow: 0 12px 20px -8px rgba(0, 0, 0, 0.08); }
    .review-filter-chip {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border: 1px solid rgba(148, 163, 184, 0.38);
      border-radius: 999px;
      color: #475569;
      font-size: 0.82rem;
      font-weight: 700;
      min-height: 2.25rem;
      padding: 0.45rem 0.85rem;
      transition: background-color 0.18s ease, border-color 0.18s ease, color 0.18s ease;
    }
    .review-filter-chip:hover,
    .review-filter-chip.is-active {
      border-color: rgba(37, 99, 235, 0.45);
      background: rgba(37, 99, 235, 0.1);
      color: #1d4ed8;
    }
    .review-action-menu { position: relative; }
    .review-action-menu > summary::-webkit-details-marker { display: none; }
    .review-action-menu__list {
      position: absolute;
      right: 0;
      top: calc(100% + 0.45rem);
      z-index: 20;
      min-width: 11rem;
      border: 1px solid rgba(148, 163, 184, 0.34);
      border-radius: 0.85rem;
      background: #ffffff;
      box-shadow: 0 18px 38px -24px rgba(15, 23, 42, 0.34);
      padding: 0.35rem;
    }
    .review-action-menu__item {
      align-items: center;
      border-radius: 0.65rem;
      display: flex;
      gap: 0.5rem;
      min-height: 2.15rem;
      padding: 0.45rem 0.65rem;
      text-align: left;
      width: 100%;
    }
    .review-action-menu__item:hover { background: rgba(248, 250, 252, 0.94); }
    .review-detail-surface {
      background: rgba(255, 255, 255, 0.98) !important;
      border-color: rgba(148, 163, 184, 0.34) !important;
      box-shadow: 0 18px 40px -30px rgba(15, 23, 42, 0.38);
    }
    .review-reply-textarea {
      background: #ffffff;
      border: 1px solid rgba(148, 163, 184, 0.34);
      border-radius: 0.75rem;
      color: #0f172a;
      min-height: 3.5rem;
      outline: none;
      resize: vertical;
      transition: border-color 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
      width: 100%;
    }
    .review-reply-textarea:focus {
      border-color: rgba(37, 99, 235, 0.62);
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.14);
      outline: none;
    }
    .review-reply-textarea::placeholder {
      color: #94a3b8;
    }
    html.dark .review-filter-chip,
    html[data-theme="dark"] .review-filter-chip {
      border-color: rgba(100, 116, 139, 0.62);
      color: #cbd5e1;
    }
    html.dark .review-filter-chip:hover,
    html.dark .review-filter-chip.is-active,
    html[data-theme="dark"] .review-filter-chip:hover,
    html[data-theme="dark"] .review-filter-chip.is-active {
      border-color: rgba(96, 165, 250, 0.56);
      background: rgba(37, 99, 235, 0.18);
      color: #bfdbfe;
    }
    @media (max-width: 640px) {
      [data-review-overview-filters] {
        padding: 0.9rem;
      }
      [data-review-overview-filters] .review-filter-chip-list {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.45rem;
      }
      [data-review-overview-filters] .review-filter-chip {
        width: 100%;
        min-height: 2rem;
        padding: 0.35rem 0.55rem;
        font-size: 0.72rem;
        font-weight: 600;
        line-height: 1.1;
      }
      [data-review-overview-filters] .overview-course-search {
        min-height: 2.45rem;
        font-size: 0.875rem;
      }
    }
    html.dark .review-action-menu__list,
    html[data-theme="dark"] .review-action-menu__list {
      border-color: rgba(100, 116, 139, 0.62);
      background: #1e293b;
      box-shadow: 0 22px 42px -28px rgba(2, 6, 23, 0.86);
    }
    html.dark .review-action-menu__item:hover,
    html[data-theme="dark"] .review-action-menu__item:hover {
      background: rgba(15, 23, 42, 0.58);
    }
    html.dark .review-detail-surface,
    html[data-theme="dark"] .review-detail-surface {
      background: rgba(30, 41, 59, 0.72) !important;
      border-color: rgba(100, 116, 139, 0.42) !important;
      box-shadow: 0 24px 48px -36px rgba(2, 6, 23, 0.82);
    }
    html.dark .review-reply-textarea,
    html[data-theme="dark"] .review-reply-textarea {
      background: rgba(15, 23, 42, 0.62);
      border-color: rgba(100, 116, 139, 0.62);
      color: #f8fafc;
    }
    html.dark .review-reply-textarea:focus,
    html[data-theme="dark"] .review-reply-textarea:focus {
      border-color: rgba(96, 165, 250, 0.7);
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
    }
    html.dark .review-reply-textarea::placeholder,
    html[data-theme="dark"] .review-reply-textarea::placeholder {
      color: #94a3b8;
    }
  </style>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="app-role-teacher teacher-dashboard-page font-sans antialiased text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-900 transition-colors duration-300 overflow-hidden overflow-x-hidden min-h-screen" data-view="teacher/reviews">
<?php
$teacherNavActive = 'reviews';
$teacherDashboardShell = true;
$showCatalogue = false;
$teacherTopTitle = 'Avis & Notes';
$teacherTopSubtitle = 'Espace formateur';
$teacherTopContextSection = $detailMode ? 'Cours sélectionné' : 'Vue globale';
$teacherTopContextLabel = $detailMode ? $selectedCourseTitle : 'Par cours';
?>
<div class="teacher-dashboard-layout relative h-screen overflow-hidden pt-16 sm:pt-[4.5rem] md:pl-72 md:flex-row">
  <?php require __DIR__ . '/_sidebar.php'; ?>
  <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

  <main class="teacher-dashboard-main h-full overflow-y-auto bg-gray-50 dark:bg-gray-900" data-scroll-top-root="1">
    <?php require __DIR__ . '/_topbar.php'; ?>

    <div class="p-4 pb-24 space-y-8 animate-fade-up sm:p-6 sm:pb-24">
      <?php $teacherWelcomeTitle = $detailMode ? ('Avis du cours : ' . $selectedCourseTitle) : 'Avis & Notes'; ?>
      <?php $teacherWelcomeSummary = $detailMode ? 'Consultez les retours étudiants et répondez aux avis du cours sélectionné.' : $quickReadText; ?>
      <?php $teacherWelcomeActionUrl = $detailMode ? $overviewUrl : $recommendedActionUrl; ?>
      <?php $teacherWelcomeActionLabel = $detailMode ? 'Retour aux avis' : $recommendedActionLabel; ?>
      <?php $teacherWelcomeActionIcon = $detailMode ? 'arrow-left' : 'chevron-right'; ?>
      <?php if ($teacherWelcomeActionUrl === ''): ?>
        <?php $teacherWelcomeActionLabel = ''; ?>
      <?php endif; ?>
      <?php require __DIR__ . '/_welcome_hero.php'; ?>

      <?php if ($successMessage !== ''): ?>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-100">
          <?php echo e($successMessage); ?>
        </div>
      <?php endif; ?>

      <?php if ($teacherReviewsNotice !== ''): ?>
        <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-100">
          <?php echo e($teacherReviewsNotice); ?>
        </div>
      <?php endif; ?>

      <?php if ($errors !== []): ?>
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-100">
          <?php foreach ($errors as $error): ?>
            <p><?php echo e((string) $error); ?></p>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <section id="overview-view" class="<?php echo $detailMode ? 'hidden ' : ''; ?>space-y-8">
        <div class="rounded-3xl border border-gray-200 bg-white p-4 shadow-card dark:border-gray-700 dark:bg-gray-800" data-review-overview-filters>
          <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div>
              <p class="text-xs uppercase tracking-[0.22em] text-gray-500 dark:text-gray-400">Filtres</p>
              <div class="review-filter-chip-list mt-3 flex flex-wrap gap-2" role="group" aria-label="Filtres des cours par avis">
                <button type="button" class="review-filter-chip is-active" data-review-course-filter="all">Tous les cours</button>
                <button type="button" class="review-filter-chip" data-review-course-filter="reviewed">Avec avis</button>
                <button type="button" class="review-filter-chip" data-review-course-filter="no_reviews">Sans avis</button>
                <button type="button" class="review-filter-chip" data-review-course-filter="unanswered">Non répondus</button>
                <button type="button" class="review-filter-chip" data-review-course-filter="low_rating">Note faible</button>
              </div>
            </div>
            <label class="relative block w-full xl:max-w-sm">
              <span class="sr-only">Rechercher un cours</span>
              <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" aria-hidden="true"></i>
              <input id="overview-course-search" type="text" class="overview-course-search w-full rounded-xl border border-gray-200 bg-white py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-400 dark:border-gray-700 dark:bg-gray-800 dark:text-white" placeholder="Rechercher un cours...">
            </label>
          </div>
        </div>

        <div id="courses-grid" class="space-y-8">
          <section class="space-y-4" data-course-section data-section-key="reviewed">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
              <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Cours avec avis</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">À consulter en priorité.</p>
              </div>
              <span class="inline-flex items-center rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700 dark:bg-primary-500/10 dark:text-primary-300"><?php echo e($formatCount(count($activeReviewedCards))); ?> cours</span>
            </div>
            <?php if ($activeReviewedCards === []): ?>
              <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-6 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                Aucun cours avec avis pour le moment.
              </div>
            <?php else: ?>
              <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" data-course-grid>
                <?php foreach ($activeReviewedCards as $courseCard): ?>
                  <?php echo $renderCourseCard($courseCard); ?>
                <?php endforeach; ?>
              </div>
              <p class="hidden rounded-2xl border border-dashed border-gray-300 bg-white p-6 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300" data-section-empty-filter>Aucun cours ne correspond aux filtres.</p>
              <div class="hidden pt-2" data-course-pagination></div>
            <?php endif; ?>
          </section>

          <section class="space-y-4" data-course-section data-section-key="no_reviews">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
              <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Cours sans avis</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">À suivre si besoin.</p>
              </div>
              <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-200"><?php echo e($formatCount(count($activePendingCards))); ?> cours</span>
            </div>
            <?php if ($activePendingCards === []): ?>
              <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-6 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                Tous les cours actifs ont déjà reçu au moins un avis.
              </div>
            <?php else: ?>
              <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" data-course-grid>
                <?php foreach ($activePendingCards as $courseCard): ?>
                  <?php echo $renderCourseCard($courseCard); ?>
                <?php endforeach; ?>
              </div>
              <p class="hidden rounded-2xl border border-dashed border-gray-300 bg-white p-6 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300" data-section-empty-filter>Aucun cours ne correspond aux filtres.</p>
              <div class="hidden pt-2" data-course-pagination></div>
            <?php endif; ?>
          </section>

          <section class="space-y-4" data-course-section data-section-key="archived">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
              <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Historique archivé</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Avis conservés pour consultation.</p>
              </div>
              <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-700/60 dark:text-slate-300"><?php echo e($formatCount($archivedCourseCount)); ?> cours</span>
            </div>
            <?php if ($archivedCourseCards === []): ?>
              <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-6 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                Aucun cours archivé à afficher.
              </div>
            <?php else: ?>
              <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" data-course-grid>
                <?php foreach ($archivedCourseCards as $courseCard): ?>
                  <?php echo $renderCourseCard($courseCard); ?>
                <?php endforeach; ?>
              </div>
              <p class="hidden rounded-2xl border border-dashed border-gray-300 bg-white p-6 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300" data-section-empty-filter>Aucun cours ne correspond aux filtres.</p>
              <div class="hidden pt-2" data-course-pagination></div>
            <?php endif; ?>
          </section>
        </div>
      </section>

      <section id="detail-view" class="<?php echo $detailMode ? '' : 'hidden '; ?>space-y-6">
        <div class="flex items-center gap-4">
          <a href="<?php echo e($overviewUrl); ?>" id="back-btn" class="<?php echo $detailMode ? '' : 'hidden '; ?>p-2 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition" aria-label="Retour à la vue globale">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
          </a>
          <div class="min-w-0">
            <p class="text-xs uppercase tracking-[0.28em] text-gray-500 dark:text-gray-400">Cours sélectionné</p>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white" id="page-title"><?php echo e($detailMode ? ('Avis du cours : ' . $selectedCourseTitle) : 'Avis du cours'); ?></h1>
            <p class="mt-2 max-w-3xl text-sm text-gray-500 dark:text-gray-400" id="detail-subtitle">
              <?php if ($detailMode): ?>
                <?php
                  $detailHeaderReviewCount = count($detailRows);
                  $detailHeaderAvg = number_format((float) ($selectedCourseCard['avg_rating'] ?? 0.0), 1, '.', '');
                ?>
                <?php echo e($formatCount($detailHeaderReviewCount) . ' avis reçu' . ($detailHeaderReviewCount > 1 ? 's' : '') . ' — note moyenne ' . $detailHeaderAvg . '/5.'); ?>
              <?php else: ?>
                Sélectionnez un cours pour consulter ses avis.
              <?php endif; ?>
            </p>
          </div>
        </div>

        <div id="detail-content">
          <?php if ($detailMode): ?>
            <?php
              $selectedCourseAvg = number_format((float) ($selectedCourseCard['avg_rating'] ?? 0.0), 1, '.', '');
              $selectedCourseReviews = max(0, count($detailRows));
              $selectedCoursePendingReplies = 0;
              $selectedLatestReviewLabel = 'Aucun avis';
              foreach ($detailRows as $detailReviewRow) {
                  if (trim((string) ($detailReviewRow['teacher_reply_text'] ?? '')) === '') {
                      $selectedCoursePendingReplies++;
                  }
              }
              if ($detailRows !== []) {
                  $selectedLatestReviewLabel = trim((string) ($detailRows[0]['created_at_label'] ?? '')) ?: 'Aucun avis';
              }
            ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
              <div class="review-detail-surface rounded-2xl p-5 shadow-card border">
                <p class="text-sm text-gray-500 dark:text-gray-400">Note moyenne</p>
                <div class="flex items-center gap-1 mt-2">
                  <span class="text-3xl font-extrabold text-gray-900 dark:text-white"><?php echo e($selectedCourseAvg); ?></span>
                  <span class="text-yellow-400 text-xl" aria-hidden="true">★</span>
                </div>
                <div class="mt-1 text-sm">
                  <?php echo $renderStars((float) ($selectedCourseCard['avg_rating'] ?? 0.0)); ?>
                </div>
              </div>

              <div class="review-detail-surface rounded-2xl p-5 shadow-card border">
                <p class="text-sm text-gray-500 dark:text-gray-400">Nombre d’avis</p>
                <p class="mt-2 text-3xl font-extrabold text-gray-900 dark:text-white"><?php echo e($formatCount($selectedCourseReviews)); ?></p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><?php echo e($selectedCourseReviews === 1 ? 'avis reçu' : 'avis reçus'); ?></p>
              </div>

              <div class="review-detail-surface rounded-2xl p-5 shadow-card border">
                <p class="text-sm text-gray-500 dark:text-gray-400">Avis sans réponse</p>
                <p class="mt-2 text-3xl font-extrabold text-gray-900 dark:text-white"><?php echo e($formatCount($selectedCoursePendingReplies)); ?></p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><?php echo e($selectedCoursePendingReplies > 0 ? 'à traiter en priorité' : 'tout est traité'); ?></p>
              </div>

              <div class="review-detail-surface rounded-2xl p-5 shadow-card border">
                <p class="text-sm text-gray-500 dark:text-gray-400">Dernier avis reçu</p>
                <p class="mt-2 text-lg font-bold text-gray-900 dark:text-white"><?php echo e($selectedLatestReviewLabel); ?></p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">historique du cours</p>
              </div>
            </div>

            <div class="review-detail-surface rounded-2xl p-6 shadow-card border mb-6">
                <h4 class="font-semibold mb-3 text-gray-900 dark:text-white">Distribution des notes</h4>
                <div class="space-y-2">
                  <?php foreach ($ratingDistribution as $bucket): ?>
                    <?php
                      $bucketRating = max(1, min(5, (int) ($bucket['rating'] ?? 0)));
                      $bucketPercent = max(0, min(100, (int) ($bucket['percent'] ?? 0)));
                    ?>
                    <div class="flex items-center gap-2">
                      <span class="text-xs w-8 text-gray-600 dark:text-gray-300"><?php echo e((string) $bucketRating); ?> ★</span>
                      <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
                        <div class="bg-yellow-400 h-2.5 rounded-full" style="width: <?php echo e((string) $bucketPercent); ?>%"></div>
                      </div>
                      <span class="text-xs text-gray-500 w-10 text-right"><?php echo e((string) $bucketPercent); ?>%</span>
                    </div>
                  <?php endforeach; ?>
                </div>
            </div>

            <div class="flex flex-wrap gap-4 items-center mb-4">
              <select id="rating-filter" class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm">
                <option value="all"<?php echo $ratingFilterValue === 0 ? ' selected' : ''; ?>>Toutes les notes</option>
                <option value="5"<?php echo $ratingFilterValue === 5 ? ' selected' : ''; ?>>5 étoiles</option>
                <option value="4"<?php echo $ratingFilterValue === 4 ? ' selected' : ''; ?>>4 étoiles</option>
                <option value="3"<?php echo $ratingFilterValue === 3 ? ' selected' : ''; ?>>3 étoiles</option>
                <option value="2"<?php echo $ratingFilterValue === 2 ? ' selected' : ''; ?>>2 étoiles</option>
                <option value="1"<?php echo $ratingFilterValue === 1 ? ' selected' : ''; ?>>1 étoile</option>
              </select>
              <input
                type="text"
                id="search-review"
                value="<?php echo e($searchFilterValue); ?>"
                placeholder="Rechercher un avis..."
                class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm w-64"
              >
            </div>

            <div class="max-w-5xl" id="reviews-list" data-review-list>
              <?php if ($detailRows === []): ?>
                <p class="rounded-2xl border border-dashed border-gray-300 bg-white p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300" data-review-empty-state>Aucun avis visible pour le moment.</p>
              <?php else: ?>
                <p class="hidden rounded-2xl border border-dashed border-gray-300 bg-white p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300" data-review-empty-state>Aucun avis trouvé.</p>
                <div class="space-y-4">
                  <?php foreach ($detailRows as $review): ?>
                    <?php
                      $reviewId = max(0, (int) ($review['id'] ?? 0));
                      $reviewerName = trim((string) ($review['reviewer_label'] ?? 'Utilisateur')) ?: 'Utilisateur';
                      $reviewerInitials = trim((string) ($review['reviewer_initials'] ?? 'ET')) ?: 'ET';
                      $reviewerAvatarUrl = trim((string) ($review['reviewer_avatar_url'] ?? ''));
                      $reviewCourseLabel = trim((string) ($review['course_label'] ?? $selectedCourseTitle)) ?: $selectedCourseTitle;
                      $reviewDate = trim((string) ($review['created_at_label'] ?? ''));
                      $reviewRating = max(1, min(5, (int) ($review['rating_value'] ?? $review['rating'] ?? 0)));
                      $reviewComment = trim((string) ($review['comment'] ?? ''));
                      $replyText = trim((string) ($review['teacher_reply_text'] ?? ''));
                      $replyAtLabel = trim((string) ($review['teacher_reply_at_label'] ?? ''));
                      $replyByLabel = trim((string) ($review['teacher_reply_by_label'] ?? ''));
                      $reviewSearchBlob = strtolower(trim($reviewerName . ' ' . $reviewCourseLabel . ' ' . $reviewComment));
                      $replyFormId = 'review-reply-' . $reviewId;
                    ?>
                    <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800" data-review-card data-review-rating="<?php echo e((string) $reviewRating); ?>" data-review-search-text="<?php echo e($reviewSearchBlob); ?>">
                      <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_14rem]">
                        <div class="min-w-0">
                          <div class="flex items-start gap-3">
                            <?php if ($reviewerAvatarUrl !== ''): ?>
                              <img src="<?php echo e($reviewerAvatarUrl); ?>" alt="" class="w-10 h-10 rounded-full object-cover">
                            <?php else: ?>
                              <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary-600 to-accent-500 flex items-center justify-center text-white text-xs font-bold">
                                <?php echo e($reviewerInitials); ?>
                              </div>
                            <?php endif; ?>
                            <div class="min-w-0">
                              <p class="font-semibold text-sm text-gray-900 dark:text-white truncate"><?php echo e($reviewerName); ?></p>
                              <p class="mt-1 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600 dark:bg-slate-700/60 dark:text-slate-200"><?php echo e($reviewCourseLabel); ?></p>
                            </div>
                          </div>

                          <div class="mt-3 flex text-yellow-400 text-sm">
                            <?php echo $renderStars((float) $reviewRating); ?>
                          </div>

                          <p class="mt-3 text-sm leading-6 text-gray-700 dark:text-gray-300"><?php echo nl2br(e($reviewComment !== '' ? $reviewComment : 'Aucun commentaire.')); ?></p>

                          <?php if ($replyText !== ''): ?>
                            <div class="mt-4 rounded-xl border border-primary-100 bg-primary-50/70 p-3 dark:border-primary-500/30 dark:bg-primary-500/10">
                              <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-xs font-semibold text-primary-700 dark:text-primary-200">Votre réponse</p>
                                <?php if ($replyAtLabel !== ''): ?>
                                  <span class="text-[11px] text-gray-500 dark:text-gray-400">
                                    <?php echo e($replyAtLabel); ?>
                                    <?php if ($replyByLabel !== ''): ?> · <?php echo e($replyByLabel); ?><?php endif; ?>
                                  </span>
                                <?php endif; ?>
                              </div>
                              <p class="mt-2 text-sm leading-6 text-gray-700 dark:text-gray-300"><?php echo nl2br(e($replyText)); ?></p>
                            </div>
                          <?php endif; ?>
                        </div>

                        <aside class="flex flex-col gap-3 border-t border-gray-100 pt-4 text-sm dark:border-gray-700 lg:border-l lg:border-t-0 lg:pl-5 lg:pt-0">
                          <div>
                            <p class="text-xs uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">Date</p>
                            <p class="mt-1 font-medium text-gray-800 dark:text-gray-100"><?php echo e($reviewDate !== '' ? $reviewDate : '—'); ?></p>
                          </div>
                          <div>
                            <p class="text-xs uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">Statut</p>
                            <?php if ($replyText !== ''): ?>
                              <span class="mt-1 inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200">Répondu</span>
                            <?php else: ?>
                              <span class="mt-1 inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-500/15 dark:text-amber-200">Non répondu</span>
                            <?php endif; ?>
                          </div>
                          <div class="mt-auto flex flex-wrap gap-2">
                            <?php if ($replyText === ''): ?>
                              <button type="button" class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary-600 px-3 py-2 text-xs font-semibold text-white hover:bg-primary-700 reply-btn" data-review-reply-toggle aria-controls="<?php echo e($replyFormId); ?>" aria-expanded="false">
                                <i class="fas fa-reply" aria-hidden="true"></i>
                                Répondre
                              </button>
                            <?php endif; ?>

                            <details class="review-action-menu">
                              <summary class="inline-flex cursor-pointer list-none items-center justify-center rounded-xl border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700/60">
                                Actions
                              </summary>
                              <div class="review-action-menu__list">
                                <?php if ($moderationReady): ?>
                                  <form method="post">
                                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                    <input type="hidden" name="action" value="hide_review">
                                    <input type="hidden" name="review_id" value="<?php echo e((string) $reviewId); ?>">
                                    <input type="hidden" name="course_id" value="<?php echo e((string) $courseId); ?>">
                                    <button type="submit" class="review-action-menu__item text-rose-600 dark:text-rose-300">
                                      <i class="fas fa-eye-slash" aria-hidden="true"></i>
                                      Masquer l’avis
                                    </button>
                                  </form>
                                <?php else: ?>
                                  <span class="review-action-menu__item text-gray-400">
                                    <i class="fas fa-eye-slash" aria-hidden="true"></i>
                                    Masquage indisponible
                                  </span>
                                <?php endif; ?>
                              </div>
                            </details>
                          </div>
                        </aside>
                      </div>

                      <?php if ($replyText === ''): ?>
                        <form method="post" id="<?php echo e($replyFormId); ?>" class="mt-4 rounded-xl border border-gray-100 bg-gray-50 p-3 hidden reply-form dark:border-gray-700 dark:bg-gray-700/30" data-review-reply-form>
                          <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                          <input type="hidden" name="action" value="save_reply">
                          <input type="hidden" name="review_id" value="<?php echo e((string) $reviewId); ?>">
                          <input type="hidden" name="course_id" value="<?php echo e((string) $courseId); ?>">
                          <textarea class="review-reply-textarea px-3 py-2 text-xs" name="reply_text" rows="2" placeholder="Votre réponse..."></textarea>
                          <div class="flex flex-wrap justify-end gap-2 mt-2">
                            <button type="button" class="text-xs text-gray-500 cancel-reply" data-review-reply-cancel>Annuler</button>
                            <?php if ($replyReady): ?>
                              <button type="submit" class="text-xs bg-primary-600 text-white px-3 py-1 rounded-lg">Envoyer</button>
                            <?php else: ?>
                              <span class="text-xs text-gray-400">Réponses indisponibles</span>
                            <?php endif; ?>
                          </div>
                        </form>
                      <?php endif; ?>
                    </article>
                  <?php endforeach; ?>
                </div>
                <div class="mt-5 hidden" data-review-pagination></div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </section>
    </div>
  </main>
</div>

<script type="application/json" id="teacher-reviews-data"><?php echo $teacherReviewsClientDataJson; ?></script>
<script src="<?php echo e(site_url('assets/js/teacher_reviews_page.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/teacher_reviews_page.js') ?: time()))); ?>" defer></script>
</body>
</html>
