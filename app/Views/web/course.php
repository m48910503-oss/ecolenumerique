<?php
$siteLogoUrl = trim((string) ($siteLogoUrl ?? ''));
$csp = trim((string) ($csp ?? ''));
$course = is_array($course ?? null) ? $course : [];
$chapters = is_array($chapters ?? null) ? $chapters : [];
$lessonsByChapter = is_array($lessonsByChapter ?? null) ? $lessonsByChapter : [];
$docsByChapter = is_array($docsByChapter ?? null) ? $docsByChapter : [];
$docsByLesson = is_array($docsByLesson ?? null) ? $docsByLesson : [];
$meetingsByChapter = is_array($meetingsByChapter ?? null) ? $meetingsByChapter : [];
$chapterLessonCount = is_array($chapterLessonCount ?? null) ? $chapterLessonCount : [];
$chapterDuration = is_array($chapterDuration ?? null) ? $chapterDuration : [];
$examSessions = is_array($examSessions ?? null) ? $examSessions : [];
$objectivesList = is_array($objectivesList ?? null) ? $objectivesList : [];
$prerequisitesList = is_array($prerequisitesList ?? null) ? $prerequisitesList : [];
$equipmentList = is_array($equipmentList ?? null) ? $equipmentList : [];
$currentUser = is_array($currentUser ?? null) ? $currentUser : null;
$isEnrolled = !empty($isEnrolled); 
$doneByLesson = is_array($doneByLesson ?? null) ? $doneByLesson : [];
$progressPercent = max(0, (int) ($progressPercent ?? 0));
$totalLessons = max(0, (int) ($totalLessons ?? 0));
$firstVideoId = isset($firstVideoId) && $firstVideoId !== null ? (int) $firstVideoId : null;
$firstEmbedUrl = trim((string) ($firstEmbedUrl ?? ''));
$firstEmbedTitle = trim((string) ($firstEmbedTitle ?? ''));
$publicPreviewAvailable = !empty($publicPreviewAvailable);
$publicPreviewLessonId = isset($publicPreviewLessonId) && $publicPreviewLessonId !== null ? (int) $publicPreviewLessonId : null;
$publicPreviewEmbedUrl = trim((string) ($publicPreviewEmbedUrl ?? ''));
$publicPreviewTitle = trim((string) ($publicPreviewTitle ?? ''));
$publicPreviewOpenUrl = trim((string) ($publicPreviewOpenUrl ?? ''));
$videoPlaylist = is_array($videoPlaylist ?? null) ? $videoPlaylist : [];
$meetingErrors = is_array($meetingErrors ?? null) ? $meetingErrors : [];
$meetingSuccess = trim((string) ($meetingSuccess ?? ''));
$doneCount = max(0, (int) ($doneCount ?? 0));
$totalLessonsAll = max(0, (int) ($totalLessonsAll ?? 0));
$doneByChapter = is_array($doneByChapter ?? null) ? $doneByChapter : [];
$examLocked = !empty($examLocked);
$myMeetingsByKey = is_array($myMeetingsByKey ?? null) ? $myMeetingsByKey : [];
$ctaHref = trim((string) ($ctaHref ?? site_url('login')));
$levelLabels = array_merge(
    [
        'beginner' => 'Débutant',
        'intermediate' => 'Intermédiaire',
        'advanced' => 'Avancé',
    ],
    is_array($levelLabels ?? null) ? $levelLabels : []
); 
$avgRating = (float) ($avgRating ?? 0);
$ratingCount = max(0, (int) ($ratingCount ?? 0));
$courseViewsCount = max(0, (int) ($courseViewsCount ?? 0));
$userRating = is_array($userRating ?? null) ? $userRating : null;
$buyersCount = max(0, (int) ($buyersCount ?? 0));
$ratingStats = is_array($ratingStats ?? null) ? $ratingStats : ['avg' => null, 'count' => 0];
$reviews = is_array($reviews ?? null) ? $reviews : [];
$durationLabel = isset($durationLabel) && trim((string) $durationLabel) !== '' ? (string) $durationLabel : null;
$priceFinal = max(0, (int) ($priceFinal ?? 0));
$priceBase = max(0, (int) ($priceBase ?? 0));
$promoActive = !empty($promoActive);
$promoEndLabel = trim((string) ($promoEndLabel ?? ''));
$promoDiscountLabel = trim((string) ($promoDiscountLabel ?? ''));
$referralEnabled = !empty($referralEnabled);
$referralL1Label = trim((string) ($referralL1Label ?? '0.00'));
$referralL2Label = trim((string) ($referralL2Label ?? '0.00'));
$referralL2Enabled = !empty($referralL2Enabled);
$courseShareUrl = trim((string) ($courseShareUrl ?? ''));
$shareLinks = array_merge(
    ['whatsapp' => '', 'facebook' => '', 'x' => '', 'linkedin' => ''],
    is_array($shareLinks ?? null) ? $shareLinks : []
);
$referralCode = trim((string) ($referralCode ?? ''));
$referralLink = trim((string) ($referralLink ?? ''));
$seoCanonicalUrl = trim((string) ($seoCanonicalUrl ?? ''));
$seoDescription = trim((string) ($seoDescription ?? ''));
$seoImageUrl = trim((string) ($seoImageUrl ?? ''));
$csrfToken = view_csrf_token();

if ($csp !== '' && !headers_sent()) {
    header('Content-Security-Policy: ' . $csp);
}
$resolveMediaUrl = static function ($path) : string {
    $value = trim((string) $path);
    if ($value === '') {
        return '';
    }
if (preg_match('~^https?://~i', $value) === 1) {
        return $value;
    }
return site_url(ltrim($value, '/'));
};


$buildLessonLabel = static function (?array $item): string {
    $item = is_array($item) ? $item : [];
    if ($item === []) {
        return 'Aucune leçon chargée';
    }
$parts = [];
    if (!empty($item['chapter_pos'])) {
        $parts[] = 'Chapitre ' . $item['chapter_pos'];
    }
if (!empty($item['lesson_pos'])) {
        $parts[] = 'Leçon ' . $item['lesson_pos'];
    }
$prefix = $parts === [] ? '' : implode(' • ', $parts) . ' • ';
    return $prefix . trim((string) ($item['title'] ?? 'Leçon'));
};

$icon = static function (string $name): string {
    return function_exists('dashboard_icon_svg') ? (string) dashboard_icon_svg($name) : '';
};

$courseTitle = trim((string) ($course['title'] ?? 'Cours'));
$courseDescription = trim((string) ($course['description'] ?? ''));
$teacherFullName = trim(((string) ($course['first_name'] ?? '')) . ' ' . ((string) ($course['last_name'] ?? '')));
if ($teacherFullName === '') {
    $teacherFullName = 'Formateur';
}
$teacherInitials = strtoupper(substr((string) ($course['first_name'] ?? 'F'), 0, 1) . substr((string) ($course['last_name'] ?? 'T'), 0, 1));
$coverUrl = $resolveMediaUrl($course['cover_path'] ?? '');
$coverAlt = trim((string) ($course['cover_alt'] ?? 'Visuel du cours'));
$levelLabel = $levelLabels[(string) ($course['level'] ?? '')] ?? ((string) ($course['level'] ?? 'Niveau libre'));
$currentQueueItem = $videoPlaylist[0] ?? null;
$currentLessonLabel = $buildLessonLabel($currentQueueItem);
$currentPlayerTitle = $firstEmbedTitle !== '' ? $firstEmbedTitle : trim((string) ($currentQueueItem['title'] ?? ''));
$ratingAverageLabel = $ratingCount > 0 ? number_format($avgRating, 1, ',', ' ') . '/5' : "Pas encore d'avis";
$reviewsCount = max(0, (int) ($ratingStats['count'] ?? 0));
$reviewsAverage = isset($ratingStats['avg']) && $ratingStats['avg'] !== null ? number_format((float) $ratingStats['avg'], 1, ',', ' ') : null;
$courseUpdatedLabel = view_format_date_label($course['updated_at'] ?? ($course['created_at'] ?? null));
$bodyCurrentLessonId = $firstVideoId ? (string) $firstVideoId : '';
$basePath = defined('BASE_PATH') ? rtrim((string) BASE_PATH, '/') : '';
$chapterCount = count($chapters);
$examCount = count($examSessions);
$meetingCount = 0;
foreach ($meetingsByChapter as $meetingGroup) {
    if (is_array($meetingGroup)) {
        $meetingCount += count($meetingGroup);
    }
}
$categoryLabel = trim((string) ($course['category_name'] ?? 'Catalogue'));
if ($categoryLabel === '') {
    $categoryLabel = 'Catalogue';
}
$teacherEmail = trim((string) ($course['email'] ?? ''));
$courseStatusLabel = $isEnrolled ? 'Accès complet' : 'Accès après inscription';
$shareSummary = $referralEnabled ? 'Lien public, réseaux sociaux et parrainage.' : 'Lien public et réseaux sociaux.';
$programmeSummary = implode(' • ', array_filter([
    view_pluralize($chapterCount, 'chapitre'),
    view_pluralize((int) $totalLessonsAll, 'leçon'),
    $durationLabel,
]));
$durationDisplay = $durationLabel ?? 'Non définie';
$courseUpdatedDisplay = $courseUpdatedLabel !== '' ? $courseUpdatedLabel : 'Non défini';
$courseDescriptionDisplay = $courseDescription !== '' ? $courseDescription : "Présentation à venir.";
$teacherEmailDisplay = $teacherEmail !== '' ? $teacherEmail : 'Adresse e-mail non renseignée';
$previewTitle = $publicPreviewTitle !== '' ? $publicPreviewTitle : 'Extrait du cours';
require __DIR__ . '/course_details.php';
return;
?>
<!doctype html>
<html lang="fr" class="light" data-default-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="app-global-assets" content="off">
    <title><?php echo e($courseTitle); ?> - École Numérique</title>
    <?php if ($seoDescription !== ''): ?><meta name="description" content="<?php echo e($seoDescription); ?>"><?php endif; ?>
    <?php if ($seoCanonicalUrl !== ''): ?>
        <link rel="canonical" href="<?php echo e($seoCanonicalUrl); ?>">
        <meta property="og:url" content="<?php echo e($seoCanonicalUrl); ?>">
    <?php endif; ?>
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo e($courseTitle); ?>">
    <?php if ($seoDescription !== ''): ?><meta property="og:description" content="<?php echo e($seoDescription); ?>"><?php endif; ?>
    <?php if ($seoImageUrl !== ''): ?><meta property="og:image" content="<?php echo e($seoImageUrl); ?>"><?php endif; ?>
    <meta http-equiv="Content-Security-Policy" content="<?php echo e($csp); ?>">
    <meta name="csrf-token" content="<?php echo e($csrfToken); ?>">
    <script src="<?php echo e(site_url('assets/js/web_theme.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/web_theme.js') ?: time()))); ?>" defer></script>
    
    <script src="https://www.youtube.com/iframe_api" defer></script>
    <script src="<?php echo e(site_url('assets/js/course_progress.js')); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/course_notes.js')); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/course_player.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/course_player.js') ?: time()))); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/fullscreen_guard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/fullscreen_guard.js') ?: time()))); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/course_player_guard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/course_player_guard.js') ?: time()))); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/course_public_preview.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/course_public_preview.js') ?: time()))); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/course_rating.js')); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/course_share.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/course_share.js') ?: time()))); ?>" defer></script>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body
    class="course-reading-page"
    data-view="web/course"
    data-base-path="<?php echo e($basePath); ?>"
    data-course-id="<?php echo e((string) ($course['id'] ?? '')); ?>"
    data-total-lessons="<?php echo e((string) ($isEnrolled ? $totalLessons : 0)); ?>"
    data-done-lessons="<?php echo e((string) $doneCount); ?>"
    data-base-progress="<?php echo e((string) $progressPercent); ?>"
    data-current-lesson-id="<?php echo e($bodyCurrentLessonId); ?>"
    <?php if (!$isEnrolled && $publicPreviewOpenUrl !== ''): ?>data-preview-guard="1"<?php endif; ?>
>
    <header class="course-topbar">
        <div class="course-shell course-topbar__inner">
            <div class="course-brand">
                <div class="course-brand__logo">
                    <?php if ($siteLogoUrl !== ''): ?>
                        <img src="<?php echo e($siteLogoUrl); ?>" alt="Logo" class="h-full w-full object-contain">
                    <?php else: ?>
                        <span class="app-icon" aria-hidden="true"><?php echo $icon('book-open'); ?></span>
                    <?php endif; ?>
                </div>
                <div class="min-w-0">
                    <p class="course-brand__title course-display">Fiche du cours</p>
                    <p class="course-brand__subtitle"><?php echo e($courseTitle); ?></p>
                </div>
            </div>
            <nav class="course-topbar__actions">
                <a href="<?php echo e(site_url('')); ?>" class="course-topbar-link">
                    <span class="app-icon" aria-hidden="true"><?php echo $icon('home'); ?></span>
                    <span>Accueil</span>
                </a>
                <?php if ($currentUser): ?>
                    <form method="post" action="<?php echo e(site_url('logout')); ?>" class="course-topbar-link">
                        <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                        <button type="submit" class="course-topbar-link">
                            <span class="app-icon" aria-hidden="true"><?php echo $icon('log-out'); ?></span>
                            <span>Se déconnecter</span>
                        </button>
                    </form>
                <?php else: ?>
                    <a href="<?php echo e(site_url('login')); ?>" class="course-topbar-link">
                        <span class="app-icon" aria-hidden="true"><?php echo $icon('user'); ?></span>
                        <span>Se connecter</span>
                    </a>
                <?php endif; ?>
                <button
                    type="button"
                    id="theme-toggle"
                    data-theme-toggle="1"
                    aria-label="Changer le thème"
                    class="course-topbar-link course-topbar-theme"
                >
                    <span data-theme-label aria-hidden="true"></span>
                </button>
            </nav>
        </div>
    </header>
    <main class="course-page">
        <div class="course-shell">
            <div class="course-flash-stack">
                <?php if ($meetingErrors !== []): ?>
                    <section class="course-card">
                        <div class="course-card__body">
                            <div class="app-flash app-flash--danger rounded-2xl px-4 py-3 text-sm">
                                <?php foreach ($meetingErrors as $error): ?>
                                    <p>- <?php echo e($error); ?></p>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>
                <?php if ($meetingSuccess !== ''): ?>
                    <section class="course-card">
                        <div class="course-card__body">
                            <div class="app-flash app-flash--success rounded-2xl px-4 py-3 text-sm">
                                <?php echo e($meetingSuccess); ?>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>
                <?php if (!empty($course['update_in_progress'])): ?>
                    <section class="course-card">
                        <div class="course-card__body">
                            <div class="app-flash app-flash--warning rounded-2xl px-4 py-3 text-sm">
                                Ce cours est en cours de mise à jour. Certaines leçons et certaines ressources peuvent encore évoluer.
                            </div>
                        </div>
                    </section>
                <?php endif; ?>
            </div>

            <section class="course-layout">
                <aside class="course-sticky">
                    <section class="course-card">
                        <div class="course-card__body">
                            <div class="course-sidebar__top">
                                <span class="course-tag course-tag--accent">
                                    <span class="app-icon" aria-hidden="true"><?php echo $icon('layout'); ?></span>
                                    <span><?php echo e($categoryLabel); ?></span>
                                </span>
                                <span class="course-count-badge">
                                    <span class="app-icon" aria-hidden="true"><?php echo $icon('check-circle'); ?></span>
                                    <span><?php echo e($courseStatusLabel); ?></span>
                                </span>
                            </div>

                            <div class="course-sidebar__cover">
                                <?php if ($coverUrl !== ''): ?>
                                    <img src="<?php echo e($coverUrl); ?>" alt="<?php echo e($coverAlt); ?>">
                                <?php else: ?>
                                    <div class="course-sidebar__placeholder">
                                        <div>
                                            <p class="course-display" style="margin: 0; color: var(--course-title); font-size: 1.12rem; font-weight: 700;"><?php echo e($courseTitle); ?></p>
                                            <p class="course-text" style="margin-top: 0.55rem;">Aucun visuel n’est disponible pour le moment.</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="course-sidebar__content">
                                <div>
                                    <h1 class="course-sidebar__title course-display"><?php echo e($courseTitle); ?></h1>
                                    <p class="course-sidebar__meta">Par <strong><?php echo e($teacherFullName); ?></strong> • Niveau : <?php echo e($levelLabel); ?></p>
                                    <p class="course-sidebar__meta">Durée estimée : <?php echo e($durationDisplay); ?></p>
                                </div>

                                <div class="course-stats-grid">
                                    <article class="course-stat">
                                        <div class="course-stat__head">
                                            <span class="course-stat__icon app-icon" aria-hidden="true"><?php echo $icon('book-open'); ?></span>
                                            <p class="course-stat__label">Chapitres</p>
                                        </div>
                                        <p class="course-stat__value"><?php echo e((string) $chapterCount); ?></p>
                                    </article>
                                    <article class="course-stat">
                                        <div class="course-stat__head">
                                            <span class="course-stat__icon app-icon" aria-hidden="true"><?php echo $icon('play-circle'); ?></span>
                                            <p class="course-stat__label">Leçons</p>
                                        </div>
                                        <p class="course-stat__value"><?php echo e((string) $totalLessonsAll); ?></p>
                                    </article>
                                    <article class="course-stat">
                                        <div class="course-stat__head">
                                            <span class="course-stat__icon app-icon" aria-hidden="true"><?php echo $icon('users'); ?></span>
                                            <p class="course-stat__label">Acheteurs</p>
                                        </div>
                                        <p class="course-stat__value"><?php echo e((string) $buyersCount); ?></p>
                                    </article>
                                    <article class="course-stat">
                                        <div class="course-stat__head">
                                            <span class="course-stat__icon app-icon" aria-hidden="true"><?php echo $icon('activity'); ?></span>
                                            <p class="course-stat__label">Vues</p>
                                        </div>
                                        <p class="course-stat__value"><?php echo e((string) $courseViewsCount); ?></p>
                                    </article>
                                </div>

                                <?php if ($isEnrolled): ?>
                                    <section class="course-progress-card">
                                        <div class="course-inline-row" style="align-items: center;">
                                            <p class="course-kicker" style="letter-spacing: 0.18em;">Progression</p>
                                            <span id="progress-percent" style="color: var(--course-title); font-weight: 700;"><?php echo e((string) $progressPercent); ?>%</span>
                                        </div>
                                        <div class="course-bar" style="margin-top: 0.8rem;">
                                            <div id="progress-bar" class="course-bar__fill" style="width: <?php echo e((string) $progressPercent); ?>%;"></div>
                                        </div>
                                        <div class="course-inline-row" style="margin-top: 0.95rem; align-items: center;">
                                            <div>
                                                <p style="margin: 0; color: var(--course-title); font-weight: 700;">Accès actif</p>
                                                <p class="course-text" style="margin-top: 0.28rem;"><?php echo e((string) $doneCount); ?> / <?php echo e((string) $totalLessons); ?> leçons terminées</p>
                                            </div>
                                            <a href="#programme" class="course-primary-btn course-btn--sm">
                                                <span class="app-icon" aria-hidden="true"><?php echo $icon('check-circle'); ?></span>
                                                <span>Continuer</span>
                                            </a>
                                        </div>
                                    </section>
                                <?php else: ?>
                                    <section class="course-price-card">
                                        <div class="course-section-label">
                                            <span class="course-icon app-icon" aria-hidden="true"><?php echo $icon('wallet'); ?></span>
                                            <div>
                                                <p class="course-kicker">Tarif du cours</p>
                                                <p class="course-price__value"><?php echo e(view_format_money_xof($priceFinal)); ?></p>
                                            </div>
                                        </div>
                                        <?php if ($promoActive): ?>
                                            <p class="course-sidebar__meta" style="margin-top: 0.6rem;">
                                                <span class="course-price__base"><?php echo e(view_format_money_xof($priceBase)); ?></span>
                                                <?php if ($promoDiscountLabel !== ''): ?> • Réduction : <?php echo e($promoDiscountLabel); ?><?php endif; ?>
                                            </p>
                                        <?php endif; ?>
                                        <?php if ($promoActive && $promoEndLabel !== ''): ?>
                                            <p class="course-sidebar__meta" style="margin-top: 0.35rem;">Promotion valable jusqu’au <?php echo e($promoEndLabel); ?>.</p>
                                        <?php endif; ?>
                                        <div style="margin-top: 1rem;">
                                            <a href="<?php echo e($ctaHref); ?>" class="course-primary-btn">
                                                <span class="app-icon" aria-hidden="true"><?php echo $icon('ticket'); ?></span>
                                                <span>Acheter ce cours</span>
                                            </a>
                                        </div>
                                    </section>
                                <?php endif; ?>

                                <section id="course-share" class="course-share-card" data-share-url="<?php echo e($courseShareUrl); ?>" data-share-title="<?php echo e($courseTitle !== '' ? $courseTitle : 'Cours'); ?>">
                                    <div class="course-section-head" style="align-items: center;">
                                        <div class="course-section-label">
                                            <span class="course-icon app-icon" aria-hidden="true"><?php echo $icon('megaphone'); ?></span>
                                            <div>
                                                <p class="course-kicker">Partage</p>
                                                <h2 class="course-title" style="font-size: 1.08rem;">Partager ce cours</h2>
                                            </div>
                                        </div>
                                        <button type="button" id="btn-toggle-share" aria-controls="course-share-panel" aria-expanded="false" class="course-secondary-btn course-btn--sm">
                                            <span class="app-icon" aria-hidden="true"><?php echo $icon('external-link'); ?></span>
                                            <span>Partager</span>
                                        </button>
                                    </div>
                                    <p class="course-text" style="margin-top: 0.8rem;"><?php echo e($shareSummary); ?></p>
                                    <div id="course-share-panel" class="hidden" style="margin-top: 1rem;">
                                        <button type="button" id="btn-native-share" class="course-secondary-btn course-btn--sm hidden">
                                            <span class="app-icon" aria-hidden="true"><?php echo $icon('external-link'); ?></span>
                                            <span>Partager avec l’appareil</span>
                                        </button>
                                        <div class="course-field" style="margin-top: 1rem;">
                                            <label for="course-share-url" class="course-field__label">Lien public</label>
                                            <div class="course-inline-actions">
                                                <input id="course-share-url" class="course-input" readonly value="<?php echo e($courseShareUrl); ?>">
                                                <button type="button" id="btn-copy-course-link" class="course-secondary-btn course-btn--sm">Copier</button>
                                            </div>
                                        </div>
                                        <?php if ($referralEnabled): ?>
                                            <div class="course-note-box" style="margin-top: 1rem;">
                                                <p style="color: var(--course-title); font-weight: 700;">Parrainage actif</p>
                                                <p style="margin-top: 0.35rem;">Niveau 1 : <?php echo e($referralL1Label); ?><?php if ($referralL2Enabled): ?> • Niveau 2 : <?php echo e($referralL2Label); ?><?php endif; ?></p>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($referralCode !== ''): ?>
                                            <div class="course-field" style="margin-top: 1rem;">
                                                <label for="course-ref-code" class="course-field__label">Code de parrainage</label>
                                                <div class="course-inline-actions">
                                                    <input id="course-ref-code" class="course-input" readonly value="<?php echo e($referralCode); ?>">
                                                    <button type="button" id="btn-copy-ref-code" class="course-secondary-btn course-btn--sm">Copier</button>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($referralLink !== ''): ?>
                                            <div class="course-field" style="margin-top: 1rem;">
                                                <label for="course-ref-link" class="course-field__label">Lien de parrainage</label>
                                                <div class="course-inline-actions">
                                                    <input id="course-ref-link" class="course-input" readonly value="<?php echo e($referralLink); ?>">
                                                    <button type="button" id="btn-copy-ref-link" class="course-secondary-btn course-btn--sm">Copier le lien</button>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <div class="course-chip-row" style="margin-top: 1rem;">
                                            <?php if ($shareLinks['whatsapp'] !== ''): ?><a href="<?php echo e($shareLinks['whatsapp']); ?>" target="_blank" rel="noopener noreferrer" class="course-secondary-btn course-btn--sm">WhatsApp</a><?php endif; ?>
                                            <?php if ($shareLinks['facebook'] !== ''): ?><a href="<?php echo e($shareLinks['facebook']); ?>" target="_blank" rel="noopener noreferrer" class="course-secondary-btn course-btn--sm">Facebook</a><?php endif; ?>
                                            <?php if ($shareLinks['x'] !== ''): ?><a href="<?php echo e($shareLinks['x']); ?>" target="_blank" rel="noopener noreferrer" class="course-secondary-btn course-btn--sm">X</a><?php endif; ?>
                                            <?php if ($shareLinks['linkedin'] !== ''): ?><a href="<?php echo e($shareLinks['linkedin']); ?>" target="_blank" rel="noopener noreferrer" class="course-secondary-btn course-btn--sm">LinkedIn</a><?php endif; ?>
                                        </div>
                                        <p id="course-share-status" class="course-text" style="margin-top: 0.85rem;"></p>
                                    </div>
                                </section>

                                <?php if ($isEnrolled): ?>
                                    <section data-rating-panel class="course-rating-card">
                                        <div class="course-section-label">
                                            <span class="course-icon app-icon" aria-hidden="true"><?php echo $icon('star'); ?></span>
                                            <div>
                                                <p class="course-kicker">Évaluation</p>
                                                <h2 class="course-title" style="font-size: 1.08rem;">Donner votre avis</h2>
                                            </div>
                                        </div>
                                        <?php if ($userRating): ?><p class="course-text" style="margin-top: 0.75rem;">Votre note actuelle : <?php echo e((string) ($userRating['rating'] ?? '')); ?>/5.</p><?php endif; ?>
                                        <form id="rating-form" action="<?php echo e(site_url('api/rating')); ?>" style="margin-top: 1rem; display: grid; gap: 0.9rem;">
                                            <div class="course-field">
                                                <label for="rating-select" class="course-field__label">Note</label>
                                                <select name="rating" id="rating-select"><?php for ($i = 1; $i <= 5; $i++): ?><option value="<?php echo $i; ?>" <?php echo ($userRating && (int) ($userRating['rating'] ?? 0) === $i) ? 'selected' : ''; ?>?><?php echo $i; ?>/5</option><?php endfor; ?></select>
                                            </div>
                                            <div class="course-field">
                                                <label for="rating-comment" class="course-field__label">Commentaire</label>
                                                <textarea name="comment" id="rating-comment" rows="4" placeholder="Expliquez ce qui vous a aidé et ce qui pourrait encore être amélioré."><?php echo e((string) ($userRating['comment'] ?? '')); ?></textarea>
                                            </div>
                                            <div class="course-inline-actions">
                                                <span id="rating-status" class="course-text"></span>
                                                <button type="submit" class="course-primary-btn course-btn--sm">Enregistrer mon avis</button>
                                            </div>
                                            <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">
                                            <input type="hidden" name="course_id" value="<?php echo e((string) ($course['id'] ?? '0')); ?>">
                                        </form>
                                    </section>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>
                </aside>

                <div class="course-main">
                    <section class="course-card course-card--hero">
                        <div class="course-card__body">
                            <div class="course-hero-grid">
                                <div style="display: grid; gap: 1.2rem;">
                                    <div class="course-chip-row">
                                        <span class="course-chip course-chip--accent"><span class="app-icon" aria-hidden="true"><?php echo $icon('award'); ?></span><span><?php echo e($levelLabel); ?></span></span>
                                        <span class="course-chip"><span class="app-icon" aria-hidden="true"><?php echo $icon('star'); ?></span><span><?php echo e($ratingAverageLabel); ?></span></span>
                                        <span class="course-chip"><span class="app-icon" aria-hidden="true"><?php echo $icon('users'); ?></span><span><?php echo e($buyersCount); ?> acheteurs</span></span>
                                        <span class="course-chip"><span class="app-icon" aria-hidden="true"><?php echo $icon('activity'); ?></span><span><?php echo e($courseViewsCount); ?> vues</span></span>
                                        <?php if ($promoActive && $promoDiscountLabel !== ''): ?><span class="course-chip"><span class="app-icon" aria-hidden="true"><?php echo $icon('ticket'); ?></span><span>Offre : <?php echo e($promoDiscountLabel); ?></span></span><?php endif; ?>
                                    </div>

                                    <div>
                                        <div class="course-section-label">
                                            <span class="course-icon app-icon" aria-hidden="true"><?php echo $icon('book-open'); ?></span>
                                            <div>
                                                <p class="course-kicker">Présentation</p>
                                                <h2 class="course-title"><?php echo e($courseTitle); ?></h2>
                                            </div>
                                        </div>
                                        <p class="course-text" style="margin-top: 1rem; max-width: 58rem;"><?php echo e($courseDescriptionDisplay); ?></p>
                                    </div>

                                    <div class="course-chip-row">
                                        <?php if ($isEnrolled): ?>
                                            <a href="#programme" class="course-primary-btn"><span class="app-icon" aria-hidden="true"><?php echo $icon('check-circle'); ?></span><span>Continuer le programme</span></a>
                                            <?php if ($examCount > 0): ?><a href="#evaluations" class="course-secondary-btn"><span class="app-icon" aria-hidden="true"><?php echo $icon('clipboard-list'); ?></span><span>Voir les examens</span></a><?php endif; ?>
                                        <?php else: ?>
                                            <a href="<?php echo e($ctaHref); ?>" class="course-primary-btn"><span class="app-icon" aria-hidden="true"><?php echo $icon('ticket'); ?></span><span>Acheter ce cours</span></a>
                                            <?php if ($publicPreviewAvailable && $publicPreviewOpenUrl !== ''): ?><a href="<?php echo e($publicPreviewOpenUrl); ?>" target="_blank" rel="noopener noreferrer" class="course-secondary-btn"><span class="app-icon" aria-hidden="true"><?php echo $icon('play-circle'); ?></span><span>Ouvrir l’aperçu protégé</span></a><?php endif; ?>
                                        <?php endif; ?>
                                    </div>

                                    <div class="course-story-grid">
                                        <section class="course-list-card">
                                            <div class="course-section-label">
                                                <span class="course-icon app-icon" aria-hidden="true"><?php echo $icon('check-circle'); ?></span>
                                                <div>
                                                    <p class="course-kicker">Objectifs</p>
                                                    <h3 class="course-title" style="font-size: 1.02rem;">Ce que vous allez maîtriser</h3>
                                                </div>
                                            </div>
                                            <?php if ($objectivesList !== []): ?>
                                                <ul class="course-list"><?php foreach ($objectivesList as $objective): ?><li><span class="course-list__bullet app-icon" aria-hidden="true"><?php echo $icon('check'); ?></span><span><?php echo e($objective); ?></span></li><?php endforeach; ?></ul>
                                            <?php else: ?>
                                                <p class="course-list__empty">Les objectifs pédagogiques seront ajoutés prochainement.</p>
                                            <?php endif; ?>
                                        </section>

                                        <section class="course-list-card">
                                            <div class="course-section-label">
                                                <span class="course-icon app-icon" aria-hidden="true"><?php echo $icon('shield'); ?></span>
                                                <div>
                                                    <p class="course-kicker">Prérequis</p>
                                                    <h3 class="course-title" style="font-size: 1.02rem;">Avant de commencer</h3>
                                                </div>
                                            </div>
                                            <?php if ($prerequisitesList !== []): ?>
                                                <ul class="course-list"><?php foreach ($prerequisitesList as $prerequisite): ?><li><span class="course-list__bullet app-icon" aria-hidden="true"><?php echo $icon('check'); ?></span><span><?php echo e($prerequisite); ?></span></li><?php endforeach; ?></ul>
                                            <?php else: ?>
                                                <p class="course-list__empty">Aucun prérequis obligatoire n’a été renseigné.</p>
                                            <?php endif; ?>
                                        </section>

                                        <section class="course-list-card">
                                            <div class="course-section-label">
                                                <span class="course-icon app-icon" aria-hidden="true"><?php echo $icon('layout'); ?></span>
                                                <div>
                                                    <p class="course-kicker">Équipement</p>
                                                    <h3 class="course-title" style="font-size: 1.02rem;">Matériel recommandé</h3>
                                                </div>
                                            </div>
                                            <?php if ($equipmentList !== []): ?>
                                                <ul class="course-list"><?php foreach ($equipmentList as $equipment): ?><li><span class="course-list__bullet app-icon" aria-hidden="true"><?php echo $icon('check'); ?></span><span><?php echo e($equipment); ?></span></li><?php endforeach; ?></ul>
                                            <?php else: ?>
                                                <p class="course-list__empty">Le matériel recommandé n’a pas encore été précisé.</p>
                                            <?php endif; ?>
                                        </section>
                                    </div>
                                </div>

                                <div class="course-metric-grid">
                                    <article class="course-metric" style="padding: 1rem;">
                                        <div class="course-section-label"><span class="course-icon app-icon" aria-hidden="true"><?php echo $icon('clock'); ?></span><div><p class="course-kicker">Durée</p><h3 class="course-title" style="font-size: 1.12rem;"><?php echo e($durationDisplay); ?></h3></div></div>
                                        <p class="course-text" style="margin-top: 0.85rem;">Temps estimé pour suivre l’ensemble du parcours.</p>
                                    </article>
                                    <article class="course-metric" style="padding: 1rem;">
                                        <div class="course-section-label"><span class="course-icon app-icon" aria-hidden="true"><?php echo $icon('repeat'); ?></span><div><p class="course-kicker">Dernière mise à jour</p><h3 class="course-title" style="font-size: 1.12rem;"><?php echo e($courseUpdatedDisplay); ?></h3></div></div>
                                        <p class="course-text" style="margin-top: 0.85rem;">Date de la dernière actualisation du contenu.</p>
                                    </article>
                                    <article class="course-metric" style="padding: 1rem;">
                                        <div class="course-section-label"><span class="course-icon app-icon" aria-hidden="true"><?php echo $icon('play-circle'); ?></span><div><p class="course-kicker">Leçons</p><h3 class="course-title" style="font-size: 1.12rem;"><?php echo e((string) $totalLessonsAll); ?></h3></div></div>
                                        <p class="course-text" style="margin-top: 0.85rem;">Vidéos, ressources et séquences pédagogiques publiées.</p>
                                    </article>
                                    <article class="course-metric" style="padding: 1rem;">
                                        <div class="course-section-label"><span class="course-icon app-icon" aria-hidden="true"><?php echo $icon('clipboard-list'); ?></span><div><p class="course-kicker">Évaluations</p><h3 class="course-title" style="font-size: 1.12rem;"><?php echo e((string) $examCount); ?></h3></div></div>
                                        <p class="course-text" style="margin-top: 0.85rem;">Examens ou évaluations associés à ce cours.</p>
                                    </article>
                                </div>
                            </div>
                        </div>
                    </section>

                    <?php if ($isEnrolled && $firstEmbedUrl !== ''): ?>
                        <section id="player-card" class="course-card">
                            <div class="course-card__body">
                                <div id="main-player-card" data-current-lesson-id="<?php echo e($bodyCurrentLessonId); ?>">
                                    <div class="course-player-head">
                                        <div>
                                            <div class="course-section-label">
                                                <span class="course-icon app-icon" aria-hidden="true"><?php echo $icon('play-circle'); ?></span>
                                                <div>
                                                    <p class="course-kicker">Lecture en cours</p>
                                                    <h2 class="course-title" style="font-size: 1.22rem;">Suivi du cours</h2>
                                                </div>
                                            </div>
                                            <p id="current-lesson-meta" class="course-text" style="margin-top: 0.9rem; color: var(--course-title); font-weight: 600;"><?php echo e($currentLessonLabel); ?></p>
                                            <p class="course-text" style="margin-top: 0.25rem;">Lecteur vidéo • <span id="player-title"><?php echo e($currentPlayerTitle !== '' ? $currentPlayerTitle : 'Vidéo'); ?></span></p>
                                        </div>
                                        <div class="course-chip-row" style="justify-content: flex-end;">
                                            <div id="progress-pill" class="course-progress-pill"><span>Progression</span><span id="progress-counter"><?php echo e((string) $doneCount); ?> / <?php echo e((string) $totalLessons); ?></span></div>
                                            <button id="btn-prev" type="button" class="course-secondary-btn course-btn--sm">Précédent</button>
                                            <button id="btn-next" type="button" class="course-secondary-btn course-btn--sm">Suivant</button>
                                            <button id="btn-mark" type="button" data-lesson-id="<?php echo e($bodyCurrentLessonId); ?>" data-done="<?php echo $firstVideoId && in_array($firstVideoId, $doneByLesson, true) ? '1' : '0'; ?>" class="course-secondary-btn course-btn--sm"><?php echo $firstVideoId && in_array($firstVideoId, $doneByLesson, true) ? 'Marquée comme vue' : 'Marquer comme vue'; ?></button>
                                        </div>
                                    </div>

                                    <div class="course-player-grid">
                                        <div>
                                            <div id="player-wrapper" class="course-video-shell" aria-label="Lecteur principal">
                                                <div style="aspect-ratio: 16 / 9;"><iframe id="main-player" src="<?php echo e($firstEmbedUrl); ?>" allow="autoplay; encrypted-media; picture-in-picture; fullscreen" title="Lecteur vidéo principal"></iframe></div>
                                                <div id="player-overlay" class="course-player-overlay" aria-hidden="true"></div>
                                            </div>

                                            <div class="course-block" style="margin-top: 1rem; padding: 1rem;" data-guard-allow="1">
                                                <div class="course-toolbar" data-guard-allow="1">
                                                    <div class="course-toolbar__row">
                                                        <div class="course-chip-row">
                                                            <button data-action="back10" type="button" class="course-secondary-btn course-btn--sm">-10 s</button>
                                                            <button id="btn-play" type="button" class="course-primary-btn course-btn--sm">Lecture / pause</button>
                                                            <button data-action="fwd10" type="button" class="course-secondary-btn course-btn--sm">+10 s</button>
                                                        </div>
                                                        <div style="display: flex; align-items: center; gap: 0.8rem; flex: 1; min-width: 260px;">
                                                            <input id="progress-range" type="range" min="0" max="1000" value="0" class="course-range">
                                                            <span id="time-label" style="min-width: 96px; text-align: right; color: var(--course-text-soft); font-size: 0.84rem;">00:00 / 00:00</span>
                                                        </div>
                                                    </div>

                                                    <div class="course-toolbar__row">
                                                        <div class="course-chip-row">
                                                            <?php foreach (['0.75', '1', '1.25', '1.5', '2'] as $speed): ?>
                                                                <button type="button" class="speed-chip course-secondary-btn course-btn--sm <?php echo $speed === '1' ? 'app-segmented-link--active' : ''; ?>" data-speed="<?php echo e($speed); ?>"><?php echo e($speed); ?>x</button>
                                                            <?php endforeach; ?>
                                                            <span id="player-speed-status" class="course-count-badge">Vitesse active : 1.00x</span>
                                                        </div>
                                                        <div class="course-chip-row">
                                                            <input id="volume-range" type="range" min="0" max="100" value="80" class="course-range" style="width: 120px;">
                                                            <button id="btn-mute" type="button" class="course-secondary-btn course-btn--sm">Couper le son</button>
                                                            <button id="btn-pip" type="button" class="course-secondary-btn course-btn--sm">Mini-lecteur</button>
                                                            <button id="btn-fullscreen" type="button" class="course-secondary-btn course-btn--sm">Plein écran</button>
                                                            <button data-action="mark-done" type="button" class="course-secondary-btn course-btn--sm">Marquer comme vue</button>
                                                        </div>
                                                    </div>

                                                    <div class="course-toolbar__footer">
                                                        <span id="progress-label" class="course-text"><?php echo e((string) $progressPercent); ?>% du parcours complet</span>
                                                        <span id="remaining-label" class="course-text">Temps restant : --</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div style="display: grid; gap: 1rem;">
                                            <section class="course-note-card">
                                                <div class="course-section-label">
                                                    <span class="course-icon app-icon" aria-hidden="true"><?php echo $icon('book-open'); ?></span>
                                                    <div><p class="course-kicker">Notes</p><h3 class="course-title" style="font-size: 1.02rem;">Notes de la leçon</h3></div>
                                                </div>
                                                <p class="course-text" style="margin-top: 0.75rem;">Vos notes sont enregistrées localement dans ce navigateur.</p>
                                                <span id="notes-status" class="course-text" style="display: block; margin-top: 0.35rem;"></span>
                                                <textarea id="lesson-notes" placeholder="Écrivez ici les points importants de cette leçon." style="margin-top: 1rem;"></textarea>
                                                <div class="course-chip-row" style="margin-top: 1rem;">
                                                    <button id="btn-notes-doc" type="button" class="course-secondary-btn course-btn--sm">Exporter en Word</button>
                                                    <button id="btn-notes-pdf" type="button" class="course-secondary-btn course-btn--sm">Exporter en PDF</button>
                                                </div>
                                            </section>

                                            <section class="course-queue-card">
                                                <div class="course-queue__head">
                                                    <div class="course-section-label">
                                                        <span class="course-icon app-icon" aria-hidden="true"><?php echo $icon('layers'); ?></span>
                                                        <div><p class="course-kicker">Playlist</p><h3 class="course-title" style="font-size: 1.02rem;">Liste des vidéos</h3></div>
                                                    </div>
                                                    <span class="course-count-badge"><?php echo e(view_pluralize((int) count($videoPlaylist), 'vidéo')); ?></span>
                                                </div>
                                                <div id="video-queue" style="display: grid; gap: 0.75rem; margin-top: 1rem;">
                                                    <?php foreach ($videoPlaylist as $index => $video): ?>
                                                        <?php $isDone = !empty($video['done']); ?>
                                                        <button type="button" class="queue-item <?php echo $index === 0 ? 'active' : ''; ?>" data-embed-url="<?php echo e((string) ($video['embed'] ?? '')); ?>" data-video-id="<?php echo e((string) ($video['video_id'] ?? '')); ?>" data-lesson-id="<?php echo e((string) ($video['id'] ?? '')); ?>" data-lesson-title="<?php echo e((string) ($video['title'] ?? '')); ?>" data-lesson-pos="<?php echo e((string) ($video['lesson_pos'] ?? '')); ?>" data-chapter-title="<?php echo e((string) ($video['chapter_title'] ?? '')); ?>" data-chapter-pos="<?php echo e((string) ($video['chapter_pos'] ?? '')); ?>" data-index="<?php echo e((string) $index); ?>" data-done="<?php echo $isDone ? '1' : '0'; ?>">
                                                            <div class="course-item__row" style="align-items: center;">
                                                                <div style="min-width: 0;"><p class="course-item__title" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo e((string) ($video['title'] ?? 'Vidéo')); ?></p><p class="course-item__meta"><?php echo e($buildLessonLabel($video)); ?></p></div>
                                                                <span class="course-count-badge"><?php echo $isDone ? 'Vue' : 'À voir'; ?></span>
                                                            </div>
                                                        </button>
                                                    <?php endforeach; ?>
                                                </div>
                                            </section>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    <?php elseif ($publicPreviewAvailable && $publicPreviewOpenUrl !== ''): ?>
                        <section class="course-card">
                            <div class="course-card__body">
                                <div class="course-hero-grid">
                                    <div>
                                        <div class="course-section-label">
                                            <span class="course-icon app-icon" aria-hidden="true"><?php echo $icon('play-circle'); ?></span>
                                            <div><p class="course-kicker">Aperçu public</p><h2 class="course-title" style="font-size: 1.28rem;"><?php echo e($previewTitle); ?></h2></div>
                                        </div>
                                        <p class="course-text" style="margin-top: 0.95rem;">Visionnez un extrait avant inscription.</p>
                                        <div class="course-video-shell" style="margin-top: 1rem;">
                                            <div style="aspect-ratio: 16 / 9;">
                                                <iframe
                                                    id="public-preview-player"
                                                    data-preview-open-url="<?php echo e($publicPreviewOpenUrl); ?>"
                                                    src=""
                                                    allow="autoplay; encrypted-media; picture-in-picture; fullscreen"
                                                    title="Aperçu public du cours"
                                                ></iframe>
                                            </div>
                                        </div>
                                    </div>
                                    <aside class="course-note-box">
                                        <p style="color: var(--course-title); font-weight: 700; font-size: 1.05rem;">Pourquoi passer à la version complète </p>
                                        <ul class="course-list">
                                            <li><span class="course-list__bullet app-icon" aria-hidden="true"><?php echo $icon('check'); ?></span><span>Accès à toutes les leçons et à toutes les ressources du cours.</span></li>
                                            <li><span class="course-list__bullet app-icon" aria-hidden="true"><?php echo $icon('check'); ?></span><span>Suivi de progression, évaluations et certification lorsqu’ils sont proposés.</span></li>
                                            <li><span class="course-list__bullet app-icon" aria-hidden="true"><?php echo $icon('check'); ?></span><span>Possibilité de réserver un rendez-vous avec le formateur lorsque des sessions sont disponibles.</span></li>
                                        </ul>
                                        <div class="course-chip-row" style="margin-top: 1rem;">
                                            <a href="<?php echo e($ctaHref); ?>" class="course-primary-btn">Acheter le cours</a>
                                            <?php if ($publicPreviewOpenUrl !== ''): ?>
                                                <button type="button" class="course-secondary-btn" data-public-preview-play>Ouvrir l’aperçu protégé</button>
                                            <?php endif; ?>
                                        </div>
                                    </aside>
                                </div>
                            </div>
                        </section>
                    <?php else: ?>
                        <section class="course-card"><div class="course-card__body"><div class="course-empty">Aucune vidéo exploitable n’est encore disponible pour ce cours.</div></div></section>
                    <?php endif; ?>
                    <section id="programme" class="course-card">
                        <div class="course-card__body">
                            <div class="course-section-head">
                                <div class="course-section-label">
                                    <span class="course-icon app-icon" aria-hidden="true"><?php echo $icon('layers'); ?></span>
                                    <div><p class="course-kicker">Programme</p><h2 class="course-title" style="font-size: 1.28rem;">Contenu du cours</h2></div>
                                </div>
                                <div class="course-chip-row" style="justify-content: flex-end;">
                                    <span class="course-count-badge"><?php echo e($programmeSummary !== '' ? $programmeSummary : 'Programme en cours de publication'); ?></span>
                                    <?php if ($isEnrolled && $totalLessons > 0): ?><span class="course-count-badge"><?php echo e((string) $doneCount); ?> / <?php echo e((string) $totalLessons); ?> suivies</span><?php endif; ?>
                                </div>
                            </div>

                            <?php if ($chapters === []): ?>
                                <div class="course-empty" style="margin-top: 1rem;">Aucun chapitre n’a encore été publié pour ce cours.</div>
                            <?php else: ?>
                                <div style="display: grid; gap: 0.9rem; margin-top: 1rem;">
                                    <?php foreach ($chapters as $chapterIndex => $chapter): ?>
                                        <?php
                                        $chapterId = (int) ($chapter['id'] ?? 0);
                                        $countLessons = (int) ($chapterLessonCount[$chapterId] ?? 0);
                                        $durationMinutes = (int) ($chapterDuration[$chapterId] ?? 0);
                                        $chapterDurationLabel = $durationMinutes > 0 ? format_duration_minutes_hm($durationMinutes, true) : 'Durée non définie';
                                        $chapterDoneCount = (int) ($doneByChapter[$chapterId] ?? 0);
                                        $chapterPercent = ($countLessons > 0 && $isEnrolled) ? (int) floor(($chapterDoneCount / $countLessons) * 100) : 0;
                                        ?>
                                        <details class="course-chapter" <?php echo $chapterIndex === 0 ? 'open' : ''; ?>>
                                            <summary>
                                                <div class="course-chapter__summary">
                                                    <div class="course-chapter__lead">
                                                        <span class="course-chapter__number"><?php echo e((string) ($chapter['position'] ?? ($chapterIndex + 1))); ?></span>
                                                        <div style="min-width: 0;">
                                                            <p class="course-chapter__title"><?php echo e((string) ($chapter['title'] ?? 'Chapitre')); ?></p>
                                                            <?php if (!empty($chapter['description'])): ?><p class="course-chapter__desc"><?php echo e((string) $chapter['description']); ?></p><?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div style="text-align: right;">
                                                        <span class="course-count-badge"><?php echo e(view_pluralize($countLessons, 'leçon')); ?></span>
                                                        <p class="course-text" style="margin-top: 0.55rem;"><?php echo e($chapterDurationLabel); ?></p>
                                                        <?php if ($isEnrolled && $countLessons > 0): ?>
                                                            <div class="course-bar" style="margin-top: 0.75rem;"><div class="course-bar__fill" style="width: <?php echo e((string) $chapterPercent); ?>%;"></div></div>
                                                            <p class="course-text" style="margin-top: 0.35rem;"><?php echo e((string) $chapterDoneCount); ?> / <?php echo e((string) $countLessons); ?> terminées</p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </summary>

                                            <div class="course-items">
                                                <?php foreach (($lessonsByChapter[$chapterId] ?? []) as $lesson) : ?>
                                                    <?php
                                                    $isVideoLesson = (string) ($lesson['lesson_type'] ?? '') === 'video';
                                                    $lessonPath = trim((string) ($lesson['document_path'] ?? ''));
                                                    $lessonResourceUrl = $lessonPath !== '' ? $resolveMediaUrl($lessonPath) : '';
                                                    $isPreviewLesson = $publicPreviewLessonId > 0 && (int) ($lesson['id'] ?? 0) === $publicPreviewLessonId;
                                                    ?>
                                                    <div class="course-item">
                                                        <div class="course-item__row" style="align-items: center;">
                                                            <div style="min-width: 0;">
                                                                <p class="course-item__title"><?php echo e((string) ($lesson['position'] ?? '')); ?>. <?php echo e((string) ($lesson['title'] ?? 'Leçon')); ?></p>
                                                                <p class="course-item__meta"><?php echo $isVideoLesson ? 'Vidéo YouTube' : 'Ressource complémentaire'; ?><?php if ($lesson['duration_estimate_min'] !== null && $isVideoLesson): ?> • <?php echo e(format_duration_minutes_hm((int) $lesson['duration_estimate_min'])); ?><?php endif; ?><?php if ($isPreviewLesson && !$isEnrolled): ?> • Aperçu disponible<?php endif; ?></p>
                                                            </div>
                                                            <div class="course-chip-row" style="justify-content: flex-end;">
                                                                <span class="course-count-badge"><?php echo $isVideoLesson ? 'Vidéo' : 'Ressource'; ?></span>
                                                                <?php if (!$isVideoLesson && $lessonResourceUrl !== '' && $isEnrolled): ?>
                                                                    <a href="<?php echo e($lessonResourceUrl); ?>" target="_blank" rel="noopener noreferrer" class="course-secondary-btn course-btn--sm">Ouvrir</a>
                                                                <?php elseif (!$isEnrolled): ?>
                                                                    <span class="course-count-badge">Accessible après inscription</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </details>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <?php if ($meetingCount > 0): ?>
                        <section class="course-card" id="rendez-vous">
                            <div class="course-card__body">
                                <div class="course-section-head">
                                    <div class="course-section-label">
                                        <span class="course-icon app-icon" aria-hidden="true"><?php echo $icon('calendar'); ?></span>
                                        <div><p class="course-kicker">Rendez-vous</p><h2 class="course-title" style="font-size: 1.28rem;">Sessions proposées</h2></div>
                                    </div>
                                    <span class="course-count-badge"><?php echo e(view_pluralize($meetingCount, 'session')); ?></span>
                                </div>

                                <div style="display: grid; gap: 0.9rem; margin-top: 1rem;">
                                    <?php foreach ($chapters as $chapter): ?>
                                        <?php
                                        $chapterId = (int) ($chapter['id'] ?? 0);
                                        $chapterMeetings = $meetingsByChapter[$chapterId] ?? [];
                                        if ($chapterMeetings === []) {
                                            continue;
                                        }
                                        ?>
                                        <section class="course-chapter">
                                            <div class="course-section-label">
                                                <span class="course-icon app-icon" aria-hidden="true"><?php echo $icon('book-open'); ?></span>
                                                <div><p class="course-kicker">Chapitre concerné</p><h3 class="course-title" style="font-size: 1.02rem;"><?php echo e((string) ($chapter['title'] ?? 'Chapitre')); ?></h3></div>
                                            </div>

                                            <div class="course-items">
                                                <?php foreach ($chapterMeetings as $meeting): ?>
                                                    <?php
                                                    $meetingTitle = (string) ($meeting['title'] ?? 'Rendez-vous');
                                                    $meetingMode = (string) ($meeting['mode'] ?? 'online');
                                                    $meetingDurationMin = (int) ($meeting['duration_min'] ?? 0);
                                                    $meetingHasDuration = $meetingDurationMin > 0;
                                                    $meetingKey = $chapterId . '|' . $meetingTitle . '|' . $meetingMode;
                                                    $requests = $myMeetingsByKey[$meetingKey] ?? [];
                                                    $confirmedMeeting = null;
                                                    $pendingMeetings = [];
                                                    foreach ($requests as $request) {
                                                        if (($request['status'] ?? '') === 'confirmed' && $confirmedMeeting === null) {
                                                            $confirmedMeeting = $request;
                                                        } elseif (($request['status'] ?? '') === 'pending') {
                                                            $pendingMeetings[] = $request;
                                                        }
                                                    }
                                                    ?>
                                                    <article class="course-item">
                                                        <div class="course-item__row">
                                                            <div><p class="course-item__title"><?php echo e($meetingTitle); ?></p><p class="course-item__meta"><?php echo $meetingMode === 'online' ? 'En ligne' : 'Présentiel'; ?><?php if ($meetingHasDuration): ?> • <?php echo e(format_duration_minutes_hm($meetingDurationMin)); ?><?php else: ?> • Durée à définir<?php endif; ?></p></div>
                                                            <span class="course-count-badge">Rendez-vous</span>
                                                        </div>

                                                        <?php if ($isEnrolled): ?>
                                                            <div style="display: grid; gap: 0.85rem; margin-top: 1rem;">
                                                                <?php if ($confirmedMeeting): ?>
                                                                    <div class="app-flash app-flash--success rounded-2xl px-4 py-3 text-sm"><p class="font-semibold">Rendez-vous confirmé</p><p class="mt-1"><?php echo e(view_format_date_label($confirmedMeeting['start_at'] ?? null, true)); ?> • <?php echo e(view_format_date_label($confirmedMeeting['end_at'] ?? null, true)); ?></p></div>
                                                                <?php else: ?>
                                                                    <?php if ($pendingMeetings !== []): ?>
                                                                        <div class="app-flash app-flash--warning rounded-2xl px-4 py-3 text-sm"><p class="font-semibold">Demandes en attente</p><?php foreach (array_slice($pendingMeetings, 0, 3) as $pendingMeeting): ?><p class="mt-1">- <?php echo e(view_format_date_label($pendingMeeting['start_at'] ?? null, true)); ?> • <?php echo e(view_format_date_label($pendingMeeting['end_at'] ?? null, true)); ?></p><?php endforeach; ?></div>
                                                                    <?php endif; ?>
                                                                    <?php if ($meetingHasDuration): ?>
                                                                        <?php if ($pendingMeetings !== []): ?>
                                                                            <form method="post" style="display: flex; justify-content: flex-end;">
                                                                                <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">
                                                                                <input type="hidden" name="action" value="cancel_meeting_requests">
                                                                                <input type="hidden" name="meeting_template_id" value="<?php echo e((string) ($meeting['id'] ?? '0')); ?>">
                                                                                <button type="submit" class="course-secondary-btn course-btn--sm course-btn--danger">Annuler mes demandes</button>
                                                                            </form>
                                                                        <?php endif; ?>
                                                                        <form method="post" style="display: grid; gap: 0.9rem;">
                                                                            <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">
                                                                            <input type="hidden" name="action" value="request_meeting">
                                                                            <input type="hidden" name="meeting_template_id" value="<?php echo e((string) ($meeting['id'] ?? '0')); ?>">
                                                                            <div class="course-field-grid">
                                                                                <?php for ($slotIndex = 0; $slotIndex < 3; $slotIndex++): ?><label class="course-field"><span class="course-field__label">Créneau <?php echo e((string) ($slotIndex + 1)); ?></span><input type="datetime-local" name="slot_start[]" required value="<?php echo e((string) ($pendingMeetings[$slotIndex]['start_local'] ?? '')); ?>"></label><?php endfor; ?>
                                                                            </div>
                                                                            <div style="display: flex; justify-content: flex-end;"><button type="submit" class="course-primary-btn course-btn--sm">Envoyer ma demande</button></div>
                                                                        </form>
                                                                    <?php else: ?>
                                                                        <div class="course-note-box"><p>La durée de cette session n’est pas encore définie par le formateur.</p></div>
                                                                    <?php endif; ?>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="course-note-box" style="margin-top: 1rem;"><p>Cette session sera accessible après votre inscription au cours.</p></div>
                                                        <?php endif; ?>
                                                    </article>
                                                <?php endforeach; ?>
                                            </div>
                                        </section>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </section>
                    <?php endif; ?>
                    <section class="course-duo">
                        <section id="evaluations" class="course-card">
                            <div class="course-card__body">
                                <div class="course-section-head">
                                    <div class="course-section-label">
                                        <span class="course-icon app-icon" aria-hidden="true"><?php echo $icon('clipboard-list'); ?></span>
                                        <div><p class="course-kicker">Évaluation</p><h2 class="course-title" style="font-size: 1.02rem;">Examens</h2></div>
                                    </div>
                                    <span class="course-count-badge"><?php echo e(view_pluralize($examCount, 'session')); ?></span>
                                </div>

                                <?php if ($examSessions !== []): ?>
                                    <div style="display: grid; gap: 0.8rem; margin-top: 1rem;">
                                        <?php foreach ($examSessions as $exam): ?>
                                            <?php
                                            $examTypeLabel = (string) ($exam['type'] ?? '') === 'qcm'
                                                ? 'QCM'
                                                : ((string) ($exam['type'] ?? '') === 'meeting' ? 'Rendez-vous' : 'Lien Drive');
                                            $examDurationLabel = !empty($exam['duration_min'])
                                                ? format_duration_minutes_hm((int) $exam['duration_min'])
                                                : 'Duree non definie';
                                            ?>
                                            <article class="course-item">
                                                <div class="course-item__row" style="align-items: center;">
                                                    <div><p class="course-item__title"><?php echo e((string) ($exam['title'] ?? 'Examen')); ?></p><p class="course-item__meta"><?php echo e($examTypeLabel); ?> • <?php echo e($examDurationLabel); ?></p></div>
                                                    <div class="course-chip-row" style="justify-content: flex-end;">
                                                        <?php if ($isEnrolled): ?>
                                                            <?php if ($examLocked): ?>
                                                                <button type="button" disabled class="course-secondary-btn course-btn--sm" title="Terminez toutes les leçons pour débloquer cet examen">Bloqué</button>
                                                            <?php else: ?>
                                                                <a href="<?php echo e(site_url('student/exam?exam_id=' . (int) ($exam['id'] ?? 0))); ?>" class="course-primary-btn course-btn--sm">Passer l’examen</a>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="course-count-badge">Accessible après inscription</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </article>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="course-empty" style="margin-top: 1rem;">Aucun examen n’est publié pour le moment.</div>
                                <?php endif; ?>
                            </div>
                        </section>

                        <section class="course-card">
                            <div class="course-card__body">
                                <div class="course-teacher__head">
                                    <div class="course-section-label">
                                        <span class="course-teacher__avatar"><?php echo e($teacherInitials !== '' ? $teacherInitials : 'FR'); ?></span>
                                        <div>
                                            <p class="course-kicker">Formateur</p>
                                            <h2 class="course-title" style="font-size: 1.02rem;"><?php echo e($teacherFullName); ?></h2>
                                            <p class="course-teacher__meta"><?php echo e($teacherEmailDisplay); ?></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="course-teacher__chips">
                                    <article class="course-mini-card"><div class="course-mini__head"><span class="course-mini__icon app-icon" aria-hidden="true"><?php echo $icon('layout'); ?></span><p class="course-mini__label">Catégorie</p></div><p class="course-mini__value"><?php echo e($categoryLabel); ?></p></article>
                                    <article class="course-mini-card"><div class="course-mini__head"><span class="course-mini__icon app-icon" aria-hidden="true"><?php echo $icon('repeat'); ?></span><p class="course-mini__label">Dernière mise à jour</p></div><p class="course-mini__value"><?php echo e($courseUpdatedDisplay); ?></p></article>
                                    <article class="course-mini-card"><div class="course-mini__head"><span class="course-mini__icon app-icon" aria-hidden="true"><?php echo $icon('award'); ?></span><p class="course-mini__label">Niveau</p></div><p class="course-mini__value"><?php echo e($levelLabel); ?></p></article>
                                    <article class="course-mini-card"><div class="course-mini__head"><span class="course-mini__icon app-icon" aria-hidden="true"><?php echo $icon('calendar'); ?></span><p class="course-mini__label">Rendez-vous</p></div><p class="course-mini__value"><?php echo e(view_pluralize($meetingCount, 'session')); ?></p></article>
                                </div>
                            </div>
                        </section>
                    </section>

                    <section class="course-card">
                        <div class="course-card__body">
                            <div class="course-section-head">
                                <div class="course-section-label">
                                    <span class="course-icon app-icon" aria-hidden="true"><?php echo $icon('star'); ?></span>
                                    <div><p class="course-kicker">Qualité perçue</p><h2 class="course-title" style="font-size: 1.22rem;">Avis des utilisateurs</h2></div>
                                </div>
                                <span class="course-count-badge"><?php echo e(view_pluralize($reviewsCount, 'avis', 'avis')); ?></span>
                            </div>

                            <?php if ($reviewsCount > 0 && $reviewsAverage !== null): ?>
                                <div class="course-note-box" style="margin-top: 1rem;">
                                    <p style="color: var(--course-title); font-weight: 700;">Note moyenne : <?php echo e($reviewsAverage); ?>/5</p>
                                    <p style="margin-top: 0.25rem;">Cette moyenne est calculée sur l’ensemble des avis publiés pour ce cours.</p>
                                </div>
                                <div class="course-review-list" style="margin-top: 1rem;">
                                    <?php foreach ($reviews as $review): ?>
                                        <article class="course-review">
                                            <div class="course-review__head">
                                                <p class="course-review__title"><?php echo e(trim(((string) ($review['first_name'] ?? '')) . ' ' . ((string) ($review['last_name'] ?? ''))) ?: 'Utilisateur'); ?></p>
                                                <span class="course-count-badge"><?php echo e((string) ($review['rating'] ?? '0')); ?>/5</span>
                                            </div>
                                            <?php if (!empty($review['comment'])): ?><p class="course-review__comment"><?php echo e((string) $review['comment']); ?></p><?php endif; ?>
                                            <p class="course-review__date">Publié le <?php echo e(view_format_date_label($review['created_at'] ?? null)); ?></p>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="course-empty" style="margin-top: 1rem;">Aucun avis n’a encore été déposé pour ce cours.</div>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
            </section>
        </div>
    </main>
</body>
</html>


