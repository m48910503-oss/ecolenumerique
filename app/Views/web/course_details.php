<?php
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
$doneByChapter = is_array($doneByChapter ?? null) ? $doneByChapter : [];
$videoPlaylist = is_array($videoPlaylist ?? null) ? $videoPlaylist : [];
$meetingErrors = is_array($meetingErrors ?? null) ? $meetingErrors : [];
$meetingSuccess = trim((string) ($meetingSuccess ?? ''));
$myMeetingsByKey = is_array($myMeetingsByKey ?? null) ? $myMeetingsByKey : [];
$ratingStats = is_array($ratingStats ?? null) ? $ratingStats : ['avg' => null, 'count' => 0];
$reviews = is_array($reviews ?? null) ? $reviews : [];
$shareLinks = array_merge(
    ['whatsapp' => '', 'facebook' => '', 'x' => '', 'linkedin' => ''],
    is_array($shareLinks ?? null) ? $shareLinks : []
);

$siteLogoUrl = trim((string) ($siteLogoUrl ?? ''));
$csp = trim((string) ($csp ?? ''));
$platformName = trim((string) platform_setting_get('site_name', defined('APP_FROM_NAME') ? (string) APP_FROM_NAME : 'École Numérique'));
if ($platformName === '') {
    $platformName = defined('APP_FROM_NAME') ? (string) APP_FROM_NAME : 'École Numérique';
}

$homeUrl = site_url('');
$catalogueUrl = site_url('catalogue');
$supportUrl = site_url('support');
$teacherApplyUrl = site_url('teacher/apply');
$privacyUrl = site_url('privacy');
$cguUrl = site_url('cgu');
$loginUrl = site_url('login');
$registerUrl = site_url('register');
$notificationsUrl = site_url('notifications');
$dashboardUrl = site_url('student/dashboard');

if ($isLoggedIn = !empty($currentUser)) {
    $roles = json_decode((string) ($currentUser['roles_json'] ?? '[]'), true);
    if (!is_array($roles)) {
        $roles = [];
    }
    if (in_array('admin', $roles, true) || in_array('admin_secondary', $roles, true)) {
        $dashboardUrl = site_url('admin/dashboard');
    } elseif (in_array('teacher', $roles, true)) {
        $dashboardUrl = site_url('teacher/dashboard');
    } elseif (in_array('company_admin', $roles, true) || in_array('company_member', $roles, true)) {
        $dashboardUrl = site_url('company/dashboard');
    }
}

$courseTitle = trim((string) ($courseTitle ?? ($course['title'] ?? 'Cours')));
if ($courseTitle === '') {
    $courseTitle = 'Cours';
}
$courseDescriptionDisplay = trim((string) ($courseDescriptionDisplay ?? ($course['description'] ?? '')));
if ($courseDescriptionDisplay === '') {
    $courseDescriptionDisplay = "La présentation détaillée de ce cours n'a pas encore été publiée.";
}
$teacherUser = is_array($teacherUser ?? null) ? $teacherUser : [];
$teacherProfile = is_array($teacherProfile ?? null) ? $teacherProfile : [];
$relatedCourses = is_array($relatedCourses ?? null) ? $relatedCourses : [];
$relatedCoursesLabel = trim((string) ($relatedCoursesLabel ?? ''));
$resourceCount = max(0, (int) ($resourceCount ?? 0));
$teacherFullName = trim((string) ($teacherFullName ?? ''));
if ($teacherFullName === '') {
    $teacherFullName = trim((string) (
        ($teacherUser['first_name'] ?? $course['first_name'] ?? '') . ' ' . ($teacherUser['last_name'] ?? $course['last_name'] ?? '')
    ));
}
if ($teacherFullName === '') {
    $teacherFullName = 'Formateur';
}
$teacherEmailDisplay = trim((string) ($teacherEmailDisplay ?? ''));
if ($teacherEmailDisplay === '') {
    $teacherEmailDisplay = 'Adresse e-mail non renseignée';
}
$categoryLabel = trim((string) ($categoryLabel ?? ($course['category_name'] ?? 'Catalogue')));
if ($categoryLabel === '') {
    $categoryLabel = 'Catalogue';
}
$levelLabel = trim((string) ($levelLabel ?? ''));
if ($levelLabel === '') {
    $level = strtolower(trim((string) ($course['level'] ?? '')));
    $levelLabels = [
        'beginner' => 'Débutant',
        'debutant' => 'Débutant',
        'intermediate' => 'Intermédiaire',
        'intermediaire' => 'Intermédiaire',
        'advanced' => 'Avancé',
        'avance' => 'Avancé',
    ];
    $levelLabel = $levelLabels[$level] ?? ($level !== '' ? ucfirst($level) : 'Tous niveaux');
}
$durationDisplay = trim((string) ($durationDisplay ?? ''));
if ($durationDisplay === '') {
    $durationDisplay = 'Non définie';
}
$courseUpdatedDisplay = trim((string) ($courseUpdatedDisplay ?? ''));
if ($courseUpdatedDisplay === '') {
    $courseUpdatedDisplay = 'Non défini';
}
$courseCoverUrl = trim((string) ($coverUrl ?? ''));
$courseCoverAlt = trim((string) ($coverAlt ?? ''));
if ($courseCoverAlt === '') {
    $courseCoverAlt = 'Visuel du cours ' . $courseTitle;
}
$courseShareUrl = trim((string) ($courseShareUrl ?? ''));
$referralCode = trim((string) ($referralCode ?? ''));
$referralLink = trim((string) ($referralLink ?? ''));
$ctaHref = trim((string) ($ctaHref ?? $loginUrl));
$publicPreviewAvailable = !empty($publicPreviewAvailable);
$publicPreviewOpenUrl = trim((string) ($publicPreviewOpenUrl ?? ''));
$publicPreviewTitle = trim((string) ($publicPreviewTitle ?? ''));
$publicPreviewEmbedUrl = trim((string) ($publicPreviewEmbedUrl ?? ''));
$publicPreviewLessonId = isset($publicPreviewLessonId) && $publicPreviewLessonId !== null ? (int) $publicPreviewLessonId : null;
$firstEmbedUrl = trim((string) ($firstEmbedUrl ?? ''));
$firstEmbedTitle = trim((string) ($firstEmbedTitle ?? ''));
$firstVideoId = isset($firstVideoId) && $firstVideoId !== null ? (int) $firstVideoId : null;
$progressPercent = max(0, (int) ($progressPercent ?? 0));
$doneCount = max(0, (int) ($doneCount ?? 0));
$totalLessons = max(0, (int) ($totalLessons ?? 0));
$totalLessonsAll = max(0, (int) ($totalLessonsAll ?? 0));
$buyersCount = max(0, (int) ($buyersCount ?? 0));
$courseViewsCount = max(0, (int) ($courseViewsCount ?? 0));
$priceFinal = max(0, (int) ($priceFinal ?? 0));
$priceBase = max(0, (int) ($priceBase ?? 0));
$promoActive = !empty($promoActive);
$promoEndLabel = trim((string) ($promoEndLabel ?? ''));
$promoDiscountLabel = trim((string) ($promoDiscountLabel ?? ''));
$examLocked = !empty($examLocked);
$referralEnabled = !empty($referralEnabled);
$referralL1Label = trim((string) ($referralL1Label ?? '0.00'));
$referralL2Label = trim((string) ($referralL2Label ?? '0.00'));
$referralL2Enabled = !empty($referralL2Enabled);
$userRating = is_array($userRating ?? null) ? $userRating : null;
$avgRating = max(0.0, (float) ($avgRating ?? 0));
$ratingCount = max(0, (int) ($ratingCount ?? 0));
$ratingAverageLabel = $ratingCount > 0 ? number_format($avgRating, 1, ',', ' ') . '/5' : "Pas encore d'avis";
$reviewsCount = max(0, (int) ($ratingStats['count'] ?? 0));
$reviewsAverage = isset($ratingStats['avg']) && $ratingStats['avg'] !== null
    ? number_format((float) $ratingStats['avg'], 1, ',', ' ')
    : null;
$meetingCount = 0;
foreach ($meetingsByChapter as $meetingGroup) {
    if (is_array($meetingGroup)) {
        $meetingCount += count($meetingGroup);
    }
}
$chapterCount = count($chapters);
$examCount = count($examSessions);
$doneByLessonCount = count($doneByLesson);
$previewTitle = $publicPreviewTitle !== '' ? $publicPreviewTitle : 'Extrait du cours';

$normalizeLabel = static function (string $value, string $fallback = ''): string {
    $value = trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'))) ?? '');
    return $value !== '' ? $value : $fallback;
};

$extractInitials = static function (string $label): string {
    $label = trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode($label, ENT_QUOTES | ENT_HTML5, 'UTF-8'))) ?? '');
    if ($label === '') {
        return 'EN';
    }
    $parts = preg_split('/\s+/u', $label) ?: [];
    $initials = '';
    foreach ($parts as $part) {
        if ($part === '') {
            continue;
        }
        $char = function_exists('mb_substr') ? mb_substr($part, 0, 1, 'UTF-8') : substr($part, 0, 1);
        $initials .= strtoupper($char);
        if (strlen($initials) >= 2) {
            break;
        }
    }
    return $initials !== '' ? $initials : 'EN';
};

$makeAvatarPlaceholder = static function (string $label) use ($extractInitials): string {
    $initials = htmlspecialchars($extractInitials($label), ENT_QUOTES, 'UTF-8');
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120">
  <defs>
    <linearGradient id="avatarGradient" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#2563eb"/>
      <stop offset="100%" stop-color="#06b6d4"/>
    </linearGradient>
  </defs>
  <rect width="120" height="120" rx="60" fill="url(#avatarGradient)"/>
  <circle cx="36" cy="34" r="18" fill="rgba(255,255,255,0.12)"/>
  <circle cx="88" cy="84" r="22" fill="rgba(255,255,255,0.10)"/>
  <text x="60" y="72" text-anchor="middle" fill="#ffffff" font-size="42" font-family="Plus Jakarta Sans, Inter, Arial, sans-serif" font-weight="700">{$initials}</text>
</svg>
SVG;

    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
};

$makeCoverPlaceholder = static function (string $title, string $subtitle): string {
    $safeTitle = htmlspecialchars($normalize = trim($title) !== '' ? trim($title) : 'Cours numérique', ENT_QUOTES, 'UTF-8');
    $safeSubtitle = htmlspecialchars($normalizeSubtitle = trim($subtitle) !== '' ? trim($subtitle) : 'Formation en ligne', ENT_QUOTES, 'UTF-8');
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 480">
  <defs>
    <linearGradient id="coverGradient" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#1d4ed8"/>
      <stop offset="100%" stop-color="#06b6d4"/>
    </linearGradient>
  </defs>
  <rect width="800" height="480" rx="36" fill="url(#coverGradient)"/>
  <circle cx="640" cy="120" r="110" fill="rgba(255,255,255,0.08)"/>
  <circle cx="120" cy="400" r="130" fill="rgba(255,255,255,0.06)"/>
  <text x="56" y="92" fill="#dbeafe" font-size="28" font-family="Plus Jakarta Sans, Inter, Arial, sans-serif">{$safeSubtitle}</text>
  <text x="56" y="180" fill="#ffffff" font-size="46" font-weight="700" font-family="Plus Jakarta Sans, Inter, Arial, sans-serif">{$safeTitle}</text>
</svg>
SVG;

    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
};

$heroCoverUrl = $courseCoverUrl !== '' ? $courseCoverUrl : $makeCoverPlaceholder($courseTitle, $categoryLabel);
$teacherAvatarUrl = trim((string) (($teacherUser['avatar_url'] ?? $course['teacher_avatar_url'] ?? '') ?? ''));
$teacherAvatarUrl = $teacherAvatarUrl !== '' && preg_match('~^(?:https?://|data:)~i', $teacherAvatarUrl) !== 1
    ? $resolveMediaUrl($teacherAvatarUrl)
    : $teacherAvatarUrl;
$teacherAvatarUrl = $teacherAvatarUrl !== '' ? $teacherAvatarUrl : $makeAvatarPlaceholder($teacherFullName);

$promoPercentDisplay = '';
if ($promoActive && $priceBase > 0 && $priceBase > $priceFinal) {
    $discount = (int) round(100 - (($priceFinal / $priceBase) * 100));
    if ($discount > 0) {
        $promoPercentDisplay = '-' . $discount . '% aujourd\'hui';
    }
}

$summaryActionLabel = $isEnrolled
    ? 'Continuer le programme'
    : ($priceFinal <= 0
        ? ($currentUser ? 'Commencer gratuitement' : 'Créer un compte pour commencer')
        : ($currentUser ? 'Acheter ce cours' : 'Se connecter pour acheter'));
$summaryActionHref = $isEnrolled ? '#programme' : $ctaHref;
$summarySecondaryLabel = $publicPreviewAvailable && $publicPreviewOpenUrl !== ''
    ? 'Voir l’aperçu gratuit'
    : 'Contacter le support';
$summarySecondaryHref = $publicPreviewAvailable && $publicPreviewOpenUrl !== ''
    ? $publicPreviewOpenUrl
    : $supportUrl;
$heroPreviewBadgeLabel = $publicPreviewAvailable && $publicPreviewOpenUrl !== ''
    ? 'Aperçu gratuit'
    : 'Visuel du cours';

$courseFacts = [
    [
        'icon' => 'layer-group',
        'label' => 'Chapitres publiés',
        'value' => $chapterCount > 0 ? view_pluralize($chapterCount, 'chapitre') : 'Aucun chapitre',
    ],
    [
        'icon' => 'book-open',
        'label' => 'Leçons publiées',
        'value' => $totalLessonsAll > 0 ? view_pluralize($totalLessonsAll, 'leçon') : 'Aucune leçon',
    ],
    [
        'icon' => 'file-alt',
        'label' => 'Ressources publiées',
        'value' => $resourceCount > 0 ? view_pluralize($resourceCount, 'ressource') : 'Aucune ressource',
    ],
    [
        'icon' => 'calendar-check',
        'label' => 'Sessions de rendez-vous',
        'value' => $meetingCount > 0 ? view_pluralize($meetingCount, 'session') : 'Aucune session',
    ],
    [
        'icon' => 'clipboard-list',
        'label' => 'Évaluations publiées',
        'value' => $examCount > 0 ? view_pluralize($examCount, 'évaluation') : 'Aucune évaluation',
    ],
    [
        'icon' => 'play-circle',
        'label' => 'Aperçu gratuit',
        'value' => $publicPreviewAvailable && $publicPreviewOpenUrl !== '' ? 'Disponible' : 'Non publié',
    ],
];

$platformLogoUrl = trim($siteLogoUrl);
$platformLogoAlt = 'Logo ' . $platformName;
$webThemeJsUrl = site_url('assets/js/web_theme.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/web_theme.js') ?: time()));
$tailwindThemeBootstrapJsUrl = site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()));

if ($courseShareUrl === '') {
    $courseShareUrl = $homeUrl;
}
?>
<!DOCTYPE html>
<html lang="fr" class="light" data-view="web/course_details">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="app-global-assets" content="off">
  <meta name="csrf-token" content="<?php echo e(view_csrf_token()); ?>">
  <meta name="color-scheme" content="light dark">
  <title><?php echo e($courseTitle); ?> · <?php echo e($platformName); ?></title>
  <?php if ($seoDescription !== ''): ?><meta name="description" content="<?php echo e($seoDescription); ?>"><?php endif; ?>
  <?php if ($seoCanonicalUrl !== ''): ?>
    <link rel="canonical" href="<?php echo e($seoCanonicalUrl); ?>">
    <meta property="og:url" content="<?php echo e($seoCanonicalUrl); ?>">
  <?php endif; ?>
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?php echo e($courseTitle); ?>">
  <?php if ($seoDescription !== ''): ?><meta property="og:description" content="<?php echo e($seoDescription); ?>"><?php endif; ?>
  <?php if ($seoImageUrl !== ''): ?><meta property="og:image" content="<?php echo e($seoImageUrl); ?>"><?php endif; ?>
  <?php if ($csp !== ''): ?><meta http-equiv="Content-Security-Policy" content="<?php echo e($csp); ?>"><?php endif; ?>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="<?php echo e($tailwindThemeBootstrapJsUrl); ?>"></script>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
 <link rel="stylesheet" href="<?php echo e(site_url('assets/vendor/fontawesome/all.min.css?v=' . (@filemtime(__DIR__ . '/../../../assets/vendor/fontawesome/all.min.css') ?: time()))); ?>">
  <link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
  <script src="<?php echo e($webThemeJsUrl); ?>" defer></script>
  <script src="<?php echo e(site_url('assets/js/course_progress.js')); ?>" defer></script>
  <script src="<?php echo e(site_url('assets/js/course_notes.js')); ?>" defer></script>
  <script src="<?php echo e(site_url('assets/js/course_player.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/course_player.js') ?: time()))); ?>" defer></script>
  <script src="<?php echo e(site_url('assets/js/fullscreen_guard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/fullscreen_guard.js') ?: time()))); ?>" defer></script>
  <script src="<?php echo e(site_url('assets/js/course_player_guard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/course_player_guard.js') ?: time()))); ?>" defer></script>
  <script src="<?php echo e(site_url('assets/js/course_public_preview.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/course_public_preview.js') ?: time()))); ?>" defer></script>
  <script src="<?php echo e(site_url('assets/js/course_rating.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/course_rating.js') ?: time()))); ?>" defer></script>
  <script src="<?php echo e(site_url('assets/js/course_share.js')); ?>" defer></script>
  <style>
   
    .hover-lift {
      transition: transform 0.25s ease, box-shadow 0.25s ease;
    }
    .hover-lift:hover {
      transform: translateY(-4px);
      box-shadow: 0 20px 30px -10px rgba(0, 0, 0, 0.1);
    }
    .gradient-text {
      background: linear-gradient(135deg, #2563eb 0%, #06b6d4 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .dark .gradient-text {
      background: linear-gradient(135deg, #60a5fa 0%, #22d3ee 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .sticky-summary {
      position: sticky;
      top: 5rem;
    }
    body[data-view="web/course_details"] main.course-page-compact .course-hero-panel {
      background:
        radial-gradient(circle at 86% 14%, rgba(37, 99, 235, 0.24), transparent 30%),
        radial-gradient(circle at 14% 82%, rgba(6, 182, 212, 0.16), transparent 34%),
        linear-gradient(135deg, #0f172a 0%, #111827 56%, #0b1220 100%);
    }

    .dark body[data-view="web/course_details"] main.course-page-compact .course-hero-panel {
      background:
        radial-gradient(circle at 86% 14%, rgba(59, 130, 246, 0.3), transparent 30%),
        radial-gradient(circle at 14% 82%, rgba(6, 182, 212, 0.18), transparent 34%),
        linear-gradient(135deg, #081120 0%, #0f172a 56%, #030712 100%);
    }

    body[data-view="web/course_details"] main.course-page-compact .course-tab-bar {
      backdrop-filter: blur(18px);
    }

    body[data-view="web/course_details"] main.course-page-compact .course-preview-media {
      box-shadow: 0 28px 70px -34px rgba(15, 23, 42, 0.65);
    }

    .course-details summary::-webkit-details-marker {
      display: none;
    }
    .queue-item {
      transition: border-color 0.2s ease, background-color 0.2s ease, transform 0.2s ease;
    }
    .queue-item.active {
      border-color: rgba(37, 99, 235, 0.45);
      background: rgba(37, 99, 235, 0.06);
    }

    body[data-view="web/course_details"] main.course-page-compact {
      font-size: 0.92rem;
    }

    body[data-view="web/course_details"] main.course-page-compact > .container:first-of-type {
      padding-top: 0.5rem;
      padding-bottom: 0.5rem;
    }

    body[data-view="web/course_details"] main.course-page-compact > section {
      padding-top: 1rem !important;
      padding-bottom: 1rem !important;
    }

    body[data-view="web/course_details"] main.course-page-compact h1 {
      font-size: clamp(1.75rem, 2.4vw, 2.35rem) !important;
      line-height: 1.06;
    }

    body[data-view="web/course_details"] main.course-page-compact h2 {
      font-size: clamp(1.1rem, 1.8vw, 1.35rem) !important;
      line-height: 1.15;
    }

    body[data-view="web/course_details"] main.course-page-compact h3 {
      font-size: 0.95rem !important;
      line-height: 1.2;
    }

    body[data-view="web/course_details"] main.course-page-compact .text-4xl {
      font-size: 1.9rem !important;
      line-height: 1.05;
    }

    body[data-view="web/course_details"] main.course-page-compact .text-3xl {
      font-size: 1.6rem !important;
      line-height: 1.1;
    }

    body[data-view="web/course_details"] main.course-page-compact .text-2xl {
      font-size: 1.2rem !important;
      line-height: 1.15;
    }

    body[data-view="web/course_details"] main.course-page-compact .text-lg {
      font-size: 1rem !important;
      line-height: 1.55;
    }

    body[data-view="web/course_details"] main.course-page-compact .course-compact-shell {
      padding: 1rem !important;
      border-radius: 1.25rem !important;
    }

    body[data-view="web/course_details"] main.course-page-compact .course-compact-summary {
      padding: 0.9rem !important;
    }

    body[data-view="web/course_details"] main.course-page-compact .course-compact-hero {
      margin-bottom: 1rem;
    }

    body[data-view="web/course_details"] main.course-page-compact .course-compact-hero img {
      height: 14rem;
    }

    body[data-view="web/course_details"] main.course-page-compact .course-compact-summary .text-4xl {
      font-size: 1.8rem !important;
    }

    body[data-view="web/course_details"] main.course-page-compact .course-compact-shell .py-3\.5 {
      padding-top: 0.7rem !important;
      padding-bottom: 0.7rem !important;
    }

    body[data-view="web/course_details"] main.course-page-compact .course-compact-shell .px-4.py-3,
    body[data-view="web/course_details"] main.course-page-compact .course-compact-shell .px-4.py-4 {
      padding-top: 0.65rem !important;
      padding-bottom: 0.65rem !important;
    }

    @media (max-width: 640px) {
      body[data-view="web/course_details"] main.course-page-compact {
        font-size: 0.88rem;
      }

      body[data-view="web/course_details"] main.course-page-compact h1 {
        font-size: clamp(1.35rem, 5.8vw, 1.6rem) !important;
      }

      body[data-view="web/course_details"] main.course-page-compact h2 {
        font-size: clamp(1.05rem, 4.8vw, 1.25rem) !important;
      }

      body[data-view="web/course_details"] main.course-page-compact h3 {
        font-size: 0.92rem !important;
      }

      body[data-view="web/course_details"] main.course-page-compact .text-4xl {
        font-size: 1.7rem !important;
      }

      body[data-view="web/course_details"] main.course-page-compact .text-3xl {
        font-size: 1.35rem !important;
      }

      body[data-view="web/course_details"] main.course-page-compact .text-2xl {
        font-size: 1.08rem !important;
      }

      body[data-view="web/course_details"] main.course-page-compact .text-lg {
        font-size: 0.96rem !important;
      }

      body[data-view="web/course_details"] main.course-page-compact .course-compact-shell {
        padding: 0.9rem !important;
        border-radius: 1.1rem !important;
      }

      body[data-view="web/course_details"] main.course-page-compact .course-compact-summary {
        padding: 0.8rem !important;
      }

      body[data-view="web/course_details"] main.course-page-compact .course-compact-hero img {
        height: 12.5rem;
      }

      body[data-view="web/course_details"] main.course-page-compact .course-compact-shell .py-3\.5 {
        padding-top: 0.6rem !important;
        padding-bottom: 0.6rem !important;
      }

      body[data-view="web/course_details"] main.course-page-compact .course-compact-shell .px-4.py-3,
      body[data-view="web/course_details"] main.course-page-compact .course-compact-shell .px-4.py-4 {
        padding-top: 0.55rem !important;
        padding-bottom: 0.55rem !important;
      }

      body[data-view="web/course_details"] main.course-page-compact input,
      body[data-view="web/course_details"] main.course-page-compact select,
      body[data-view="web/course_details"] main.course-page-compact textarea {
        font-size: 16px;
      }
    }
  </style>
</head>
<body class="font-sans antialiased text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-900 transition-colors duration-300" data-view="web/course_details" data-course-id="<?php echo e((string) ($course['id'] ?? '')); ?>" data-current-lesson-id="<?php echo e($firstVideoId ? (string) $firstVideoId : ''); ?>">
  <?php require __DIR__ . '/_public_header_catalogue.php'; ?>

  <main class="pb-20 course-page-compact">
    <section class="container mx-auto px-4 sm:px-6 lg:px-8 pb-6">
      <div id="overview" class="course-hero-panel relative overflow-hidden rounded-[2rem] border border-slate-800/80 text-white shadow-2xl">
        <div class="absolute inset-0">
          <div class="absolute -right-24 top-10 h-72 w-72 rounded-full bg-primary-500/20 blur-3xl"></div>
          <div class="absolute -left-20 bottom-0 h-64 w-64 rounded-full bg-cyan-500/10 blur-3xl"></div>
        </div>

        <div class="relative grid gap-8 p-6 md:p-8 lg:p-10 xl:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.82fr)]">
          <div class="min-w-0 space-y-6">
            <div class="flex flex-wrap items-center gap-2 text-sm text-slate-300">
              <a href="<?php echo e($homeUrl); ?>" class="hover:text-white transition">Accueil</a>
              <i class="fas fa-chevron-right text-[10px] text-slate-500"></i>
              <a href="<?php echo e($catalogueUrl); ?>" class="hover:text-white transition">Catalogue</a>
              <i class="fas fa-chevron-right text-[10px] text-slate-500"></i>
              <span class="font-medium text-white/90"><?php echo e($courseTitle); ?></span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
              <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-200"><?php echo e($categoryLabel); ?></span>
              <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-slate-200"><?php echo e($levelLabel); ?></span>
              <span class="inline-flex items-center rounded-full <?php echo $publicPreviewAvailable && $publicPreviewOpenUrl !== '' ? 'bg-emerald-500/15 text-emerald-200' : 'bg-white/10 text-slate-200'; ?> px-3 py-1 text-xs font-semibold">
                <i class="fas <?php echo e($publicPreviewAvailable && $publicPreviewOpenUrl !== '' ? 'fa-play-circle' : 'fa-lock'); ?> mr-1"></i>
                <?php echo e($publicPreviewAvailable && $publicPreviewOpenUrl !== '' ? 'Aperçu gratuit disponible' : 'Accès complet après inscription'); ?>
              </span>
              <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-slate-200">
                <?php echo e(view_pluralize($chapterCount, 'chapitre')); ?>
              </span>
            </div>

            <div class="space-y-4">
              <h1 class="text-2xl font-black tracking-tight text-white md:text-4xl"><?php echo e($courseTitle); ?></h1>
              <p class="max-w-3xl text-base leading-7 text-slate-300 md:text-lg"><?php echo e($courseDescriptionDisplay); ?></p>
            </div>

            <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-slate-300">
              <?php if ($ratingCount > 0): ?>
                <div class="flex items-center gap-2 text-yellow-300">
                  <?php
                  $heroRating = max(0.0, min(5.0, $avgRating));
                  for ($star = 1; $star <= 5; $star++):
                  ?>
                    <i class="<?php echo e($heroRating >= $star ? 'fas' : ($heroRating >= ($star - 0.5) ? 'fas fa-star-half-alt' : 'far fa-star')); ?>"></i>
                  <?php endfor; ?>
                  <span class="font-semibold text-white/90"><?php echo e($ratingAverageLabel); ?></span>
                </div>
              <?php else: ?>
                <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-slate-200">Aucun avis pour le moment</span>
              <?php endif; ?>
              <div class="flex items-center"><i class="fas fa-user-graduate mr-2 text-primary-300"></i> <?php echo e(number_format($buyersCount, 0, ',', ' ')); ?> apprenants</div>
              <div class="flex items-center"><i class="fas fa-clock mr-2 text-primary-300"></i> <?php echo e($durationDisplay); ?></div>
              <div class="flex items-center"><i class="fas fa-layer-group mr-2 text-primary-300"></i> <?php echo e(view_pluralize($chapterCount, 'chapitre')); ?></div>
              <div class="flex items-center"><i class="fas fa-calendar-alt mr-2 text-primary-300"></i> Mis à jour : <?php echo e($courseUpdatedDisplay); ?></div>
            </div>
          </div>

          <div class="lg:pl-4 space-y-4">
            <div class="overflow-hidden rounded-[1.75rem] border border-white/10 bg-white/5 course-preview-media shadow-2xl">
              <div class="relative">
                <img src="<?php echo e($heroCoverUrl); ?>" alt="<?php echo e($courseCoverAlt); ?>" class="h-52 w-full object-cover md:h-64">
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/75 via-slate-950/10 to-transparent"></div>
                <?php if ($publicPreviewAvailable && $publicPreviewOpenUrl !== ''): ?>
                  <button type="button" data-public-preview-play class="absolute inset-0 flex flex-col items-center justify-center gap-3 text-white transition hover:bg-black/10">
                    <span class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-white text-slate-900 shadow-lg">
                      <i class="fas fa-play text-xl"></i>
                    </span>
                    <span class="rounded-full bg-black/55 px-4 py-2 text-sm font-semibold backdrop-blur-sm">Afficher un aperçu de ce cours</span>
                  </button>
                <?php else: ?>
                  <div class="absolute inset-x-0 bottom-0 p-4">
                    <span class="inline-flex items-center rounded-full bg-black/70 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur-sm">
                      <i class="fas fa-image mr-1"></i>
                      <?php echo e($heroPreviewBadgeLabel); ?>
                    </span>
                  </div>
                <?php endif; ?>
              </div>
              <div class="border-t border-white/10 bg-slate-950/35 px-4 py-3 text-xs text-slate-300">
                <?php echo e($publicPreviewAvailable && $publicPreviewOpenUrl !== '' ? 'L’aperçu est disponible juste au-dessus.' : 'Visuel de présentation du cours.'); ?>
              </div>
            </div>

            <div class="sticky-summary rounded-[1.75rem] border border-white/10 bg-white/95 p-4 shadow-2xl dark:bg-gray-800/70 md:p-5">
              <p class="text-center text-[11px] font-semibold uppercase tracking-[0.22em] text-gray-500 dark:text-gray-400">Achat unique</p>
              <div class="text-center mb-4">
                <?php if ($isEnrolled): ?>
                  <span class="text-2xl font-extrabold text-gray-900 dark:text-white"><?php echo e((string) $progressPercent); ?>%</span>
                  <span class="mt-1 block text-sm font-medium text-primary-600 dark:text-primary-400">Accès complet au cours</span>
                <?php else: ?>
                  <span class="text-2xl font-extrabold text-gray-900 dark:text-white"><?php echo e(view_format_money_xof($priceFinal)); ?></span>
                  <?php if ($priceBase > $priceFinal): ?>
                    <span class="ml-2 text-lg text-gray-400 line-through"><?php echo e(view_format_money_xof($priceBase)); ?></span>
                  <?php endif; ?>
                  <?php if ($promoPercentDisplay !== ''): ?>
                    <span class="mt-1 block text-sm font-medium text-green-600 dark:text-green-400"><?php echo e($promoPercentDisplay); ?></span>
                  <?php elseif ($promoActive && $promoDiscountLabel !== ''): ?>
                    <span class="mt-1 block text-sm font-medium text-green-600 dark:text-green-400"><?php echo e($promoDiscountLabel); ?></span>
                  <?php endif; ?>
                <?php endif; ?>
              </div>

              <?php if ($isEnrolled && $totalLessons > 0): ?>
                <div class="mb-5">
                  <div class="mb-2 flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                    <span>Progression</span>
                    <span><?php echo e((string) $doneCount); ?> / <?php echo e((string) $totalLessons); ?></span>
                  </div>
                  <div class="h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                    <div class="h-full rounded-full bg-gradient-to-r from-primary-600 to-accent-500" style="width: <?php echo e((string) $progressPercent); ?>%;"></div>
                  </div>
                </div>
              <?php endif; ?>

              <div class="space-y-3">
                <a href="<?php echo e($summaryActionHref); ?>" class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary-600 py-3.5 font-bold text-white shadow-md transition hover:bg-primary-700 hover:shadow-lg">
                  <i class="fas <?php echo e($isEnrolled ? 'fa-play' : 'fa-cart-plus'); ?>"></i> <?php echo e($summaryActionLabel); ?>
                </a>
                <a href="<?php echo e($summarySecondaryHref); ?>" <?php echo $summarySecondaryHref !== $supportUrl ? 'target="_blank" rel="noopener noreferrer"' : ''; ?> class="flex w-full items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white py-3.5 font-semibold text-gray-800 shadow-sm transition hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                  <i class="fas <?php echo e($publicPreviewAvailable && $publicPreviewOpenUrl !== '' ? 'fa-play-circle' : 'fa-headset'); ?>"></i> <?php echo e($summarySecondaryLabel); ?>
                </a>
              </div>

              <div class="mt-6 space-y-2 text-sm text-gray-600 dark:text-gray-400">
                <p class="flex items-center gap-2"><i class="fas <?php echo e($publicPreviewAvailable && $publicPreviewOpenUrl !== '' ? 'fa-play-circle' : 'fa-lock'); ?> text-primary-500 w-5"></i> <?php echo e($publicPreviewAvailable && $publicPreviewOpenUrl !== '' ? 'Aperçu gratuit disponible' : 'Aucun aperçu public publié'); ?></p>
                <p class="flex items-center gap-2"><i class="fas fa-layer-group text-primary-500 w-5"></i> <?php echo e(view_pluralize($chapterCount, 'chapitre')); ?> · <?php echo e(view_pluralize($totalLessonsAll, 'leçon')); ?></p>
                <p class="flex items-center gap-2"><i class="fas fa-file-alt text-primary-500 w-5"></i> <?php echo e($resourceCount > 0 ? view_pluralize($resourceCount, 'ressource') : 'Aucune ressource publiée'); ?></p>
                <p class="flex items-center gap-2"><i class="fas fa-calendar-check text-primary-500 w-5"></i> <?php echo e($meetingCount > 0 ? view_pluralize($meetingCount, 'session de rendez-vous') : 'Aucune session publiée'); ?></p>
                <p class="flex items-center gap-2"><i class="fas fa-clipboard-list text-primary-500 w-5"></i> <?php echo e($examCount > 0 ? view_pluralize($examCount, 'évaluation') : 'Aucune évaluation publiée'); ?></p>
              </div>

              <?php if ($promoActive && $promoEndLabel !== '' && !$isEnrolled): ?>
                <div class="mt-6 border-t border-gray-200 pt-4 text-center dark:border-gray-700">
                  <p class="text-xs text-gray-500">Promotion valable jusqu’au <?php echo e($promoEndLabel); ?>.</p>
                </div>
              <?php elseif ($isEnrolled): ?>
                <div class="mt-6 border-t border-gray-200 pt-4 text-center dark:border-gray-700">
                  <p class="text-xs text-gray-500">Votre progression est enregistrée automatiquement.</p>
                </div>
              <?php else: ?>
                <div class="mt-6 border-t border-gray-200 pt-4 text-center dark:border-gray-700">
                  <p class="text-xs text-gray-500">Conditions d’achat et modalités d’accès disponibles avant paiement.</p>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="container mx-auto px-4 sm:px-6 lg:px-8 pb-2">
      <div class="course-tab-bar sticky top-16 z-30 flex gap-2 overflow-x-auto rounded-[1.25rem] border border-gray-200 bg-white/90 px-3 py-3 shadow-sm dark:border-gray-700 dark:bg-gray-900/85 md:top-20">
        <a href="#overview" class="inline-flex shrink-0 items-center rounded-full bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700">Aperçu</a>
        <a href="#apercu" class="inline-flex shrink-0 items-center rounded-full border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:border-primary-300 hover:text-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">Objectifs</a>
        <a href="#programme" class="inline-flex shrink-0 items-center rounded-full border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:border-primary-300 hover:text-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">Programme</a>
        <a href="#formateur" class="inline-flex shrink-0 items-center rounded-full border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:border-primary-300 hover:text-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">Formateur</a>
        <a href="#avis" class="inline-flex shrink-0 items-center rounded-full border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:border-primary-300 hover:text-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">Avis</a>
        <a href="#sessions" class="inline-flex shrink-0 items-center rounded-full border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:border-primary-300 hover:text-primary-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">Sessions</a>
      </div>
    </section>

    <section id="apercu" class="container mx-auto px-4 sm:px-6 lg:px-8 py-6 scroll-mt-28">
      <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-card dark:border-gray-700 dark:bg-gray-800 course-compact-shell md:p-6">
        <h2 class="mb-6 flex items-center gap-2 text-2xl font-bold text-gray-900 dark:text-white">
          <i class="fas fa-bullseye text-primary-500"></i> Ce que vous apprendrez
        </h2>
        <?php if ($objectivesList !== []): ?>
          <div class="grid gap-3 sm:grid-cols-2">
            <?php foreach ($objectivesList as $objective): ?>
              <div class="flex items-start gap-2 text-gray-700 dark:text-gray-300">
                <i class="fas fa-check-circle mt-1 text-green-500"></i>
                <span><?php echo e((string) $objective); ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-5 py-6 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900/30 dark:text-gray-400">
            Aucun objectif n’a encore été renseigné pour ce cours.
          </div>
        <?php endif; ?>

        <?php if ($prerequisitesList !== [] || $equipmentList !== []): ?>
          <div class="mt-6 grid gap-4 md:grid-cols-2">
            <?php if ($prerequisitesList !== []): ?>
              <div class="rounded-2xl border border-gray-100 bg-gray-50/90 p-4 dark:border-gray-700 dark:bg-gray-900/30">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-500 dark:text-gray-400">Prérequis</p>
                <ul class="mt-3 space-y-2 text-sm text-gray-700 dark:text-gray-300">
                  <?php foreach ($prerequisitesList as $prerequisite): ?>
                    <li class="flex items-start gap-2">
                      <i class="fas fa-check-circle mt-1 text-emerald-500"></i>
                      <span><?php echo e((string) $prerequisite); ?></span>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>
            <?php if ($equipmentList !== []): ?>
              <div class="rounded-2xl border border-gray-100 bg-gray-50/90 p-4 dark:border-gray-700 dark:bg-gray-900/30">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-500 dark:text-gray-400">Équipement</p>
                <ul class="mt-3 space-y-2 text-sm text-gray-700 dark:text-gray-300">
                  <?php foreach ($equipmentList as $equipment): ?>
                    <li class="flex items-start gap-2">
                      <i class="fas fa-check-circle mt-1 text-emerald-500"></i>
                      <span><?php echo e((string) $equipment); ?></span>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <div class="mt-6 rounded-2xl border border-gray-100 bg-slate-50/90 p-4 dark:border-gray-700 dark:bg-gray-900/30 md:p-5">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-500 dark:text-gray-400">Ce cours comprend</p>
              <h3 class="mt-2 text-xl font-bold text-gray-900 dark:text-white">Tout ce qui est actuellement publié pour ce cours</h3>
            </div>
            <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-gray-600 shadow-sm dark:bg-gray-800 dark:text-gray-300">
              <?php echo e(view_pluralize($chapterCount, 'chapitre')); ?> · <?php echo e(view_pluralize($totalLessonsAll, 'leçon')); ?>
            </span>
          </div>

          <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <?php foreach ($courseFacts as $include): ?>
              <div class="flex items-start gap-3 rounded-2xl border border-white/70 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800/80">
                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-primary-600 text-white shadow-md">
                  <i class="fas <?php echo e((string) ($include['icon'] ?? 'check')); ?>"></i>
                </span>
                <div class="min-w-0">
                  <p class="font-semibold text-gray-900 dark:text-white"><?php echo e((string) ($include['label'] ?? 'Avantage')); ?></p>
                  <p class="mt-1 text-sm text-gray-600 dark:text-gray-400"><?php echo e((string) ($include['value'] ?? '')); ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="mt-6">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-500 dark:text-gray-400">
              <?php echo e($relatedCoursesLabel !== '' ? $relatedCoursesLabel : 'Cours similaires'); ?>
            </p>
          </div>
          <?php if ($relatedCourses !== []): ?>
            <div class="mt-3 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
              <?php foreach ($relatedCourses as $relatedCourse): ?>
                <?php
                $relatedTitle = trim((string) ($relatedCourse['title'] ?? 'Cours'));
                if ($relatedTitle === '') {
                    $relatedTitle = 'Cours';
                }
                $relatedDescription = trim((string) ($relatedCourse['description'] ?? ''));
                if ($relatedDescription === '') {
                    $relatedDescription = 'Présentation non renseignée.';
                }
                $relatedTeacher = trim(((string) ($relatedCourse['first_name'] ?? '')) . ' ' . ((string) ($relatedCourse['last_name'] ?? '')));
                if ($relatedTeacher === '') {
                    $relatedTeacher = 'Formateur';
                }
                $relatedCategory = trim((string) ($relatedCourse['category_name'] ?? 'Formation'));
                if ($relatedCategory === '') {
                    $relatedCategory = 'Formation';
                }
                $relatedSlug = trim((string) ($relatedCourse['slug'] ?? ''));
                $relatedUrl = $relatedSlug !== ''
                    ? site_url('course?slug=' . urlencode($relatedSlug))
                    : site_url('course?id=' . (int) ($relatedCourse['id'] ?? 0));
                $relatedCoverUrl = trim((string) ($relatedCourse['cover_path'] ?? ''));
                $relatedCoverUrl = $relatedCoverUrl !== '' && preg_match('~^(?:https?://|data:)~i', $relatedCoverUrl) !== 1
                    ? $resolveMediaUrl($relatedCoverUrl)
                    : $relatedCoverUrl;
                if ($relatedCoverUrl === '') {
                    $relatedCoverUrl = $makeCoverPlaceholder($relatedTitle, $relatedCategory);
                }
                $relatedCoverAlt = trim((string) ($relatedCourse['cover_alt'] ?? ''));
                if ($relatedCoverAlt === '') {
                    $relatedCoverAlt = 'Visuel du cours ' . $relatedTitle;
                }
                $relatedRatingCount = max(0, (int) ($relatedCourse['rating_count'] ?? 0));
                $relatedAvgRating = max(0.0, (float) ($relatedCourse['avg_rating'] ?? 0));
                $relatedRatingLabel = $relatedRatingCount > 0
                    ? number_format($relatedAvgRating, 1, ',', ' ')
                    : 'Pas encore d’avis';
                $relatedEnrollments = max(0, (int) ($relatedCourse['enrolled_count'] ?? 0));
                $relatedDurationMin = max(0, (int) ($relatedCourse['total_min'] ?? 0));
                $relatedDurationLabel = $relatedDurationMin > 0
                    ? format_duration_minutes_hm($relatedDurationMin, true)
                    : 'Durée non définie';
                $relatedPrice = max(0, (int) ($relatedCourse['price_xof'] ?? 0));
                $relatedPriceLabel = $relatedPrice > 0 ? view_format_money_xof($relatedPrice) : 'Gratuit';
                ?>
                <a href="<?php echo e($relatedUrl); ?>" class="group overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-lg dark:border-gray-700 dark:bg-gray-800">
                  <div class="relative aspect-[16/9] overflow-hidden bg-gray-100 dark:bg-gray-900">
                    <img src="<?php echo e($relatedCoverUrl); ?>" alt="<?php echo e($relatedCoverAlt); ?>" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/60 via-transparent to-transparent"></div>
                    <div class="absolute left-3 top-3 flex flex-wrap gap-2">
                      <span class="inline-flex items-center rounded-full bg-black/65 px-3 py-1 text-xs font-semibold text-white backdrop-blur">
                        <?php echo e($relatedCategory); ?>
                      </span>
                    </div>
                  </div>
                  <div class="space-y-3 p-4">
                    <div class="flex items-center justify-between gap-3 text-xs text-gray-500 dark:text-gray-400">
                      <span><?php echo e($relatedTeacher); ?></span>
                      <span><?php echo e($relatedDurationLabel); ?></span>
                    </div>
                    <h4 class="line-clamp-2 text-lg font-bold text-gray-900 dark:text-white"><?php echo e($relatedTitle); ?></h4>
                    <p class="line-clamp-3 text-sm leading-6 text-gray-600 dark:text-gray-400"><?php echo e($relatedDescription); ?></p>
                    <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
                      <span class="inline-flex items-center gap-1 font-semibold text-yellow-500">
                        <i class="fas fa-star"></i>
                        <?php echo e($relatedRatingLabel); ?>
                        <?php if ($relatedRatingCount > 0): ?>
                          <span class="font-medium text-gray-500 dark:text-gray-400">· <?php echo e(view_pluralize($relatedRatingCount, 'avis', 'avis')); ?></span>
                        <?php endif; ?>
                      </span>
                      <span class="font-extrabold text-gray-900 dark:text-white"><?php echo e($relatedPriceLabel); ?></span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo e($relatedEnrollments > 0 ? view_pluralize($relatedEnrollments, 'apprenant', 'apprenants') : 'Aucun apprenant publié'); ?></p>
                  </div>
                </a>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="mt-3 rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-5 py-6 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900/30 dark:text-gray-400">
              Aucun autre cours public n’a encore été publié dans ce périmètre.
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <section class="container mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-8">
          <div id="programme" class="scroll-mt-28 bg-white dark:bg-gray-800 rounded-2xl p-4 md:p-6 shadow-card border border-gray-100 dark:border-gray-700 course-compact-shell">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
              <i class="fas fa-list-ul text-primary-500"></i> Contenu du cours
            </h2>
            <?php if ($chapters === []): ?>
              <div class="rounded-2xl border border-dashed border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30 px-5 py-6 text-gray-600 dark:text-gray-400">
                Aucun chapitre n’a encore été publié pour ce cours.
              </div>
            <?php else: ?>
              <div class="space-y-3">
                <?php foreach ($chapters as $chapterIndex => $chapter): ?>
                  <?php
                  $chapterId = (int) ($chapter['id'] ?? 0);
                  $countLessons = (int) ($chapterLessonCount[$chapterId] ?? 0);
                  $durationMinutes = (int) ($chapterDuration[$chapterId] ?? 0);
                  $chapterDurationLabel = $durationMinutes > 0 ? format_duration_minutes_hm($durationMinutes, true) : 'Durée non définie';
                  $chapterDoneCount = (int) ($doneByChapter[$chapterId] ?? 0);
                  $chapterPercent = ($countLessons > 0 && $isEnrolled) ? (int) floor(($chapterDoneCount / $countLessons) * 100) : 0;
                  $chapterLessons = is_array($lessonsByChapter[$chapterId] ?? null) ? $lessonsByChapter[$chapterId] : [];
                  $chapterDocs = is_array($docsByChapter[$chapterId] ?? null) ? $docsByChapter[$chapterId] : [];
                  $chapterMeetings = is_array($meetingsByChapter[$chapterId] ?? null) ? $meetingsByChapter[$chapterId] : [];
                  ?>
                  <details class="course-details group rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-900/50 p-4 md:p-5" <?php echo $chapterIndex === 0 ? 'open' : ''; ?>>
                    <summary class="list-none cursor-pointer select-none">
                      <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3 min-w-0">
                          <span class="w-11 h-11 rounded-2xl bg-primary-600 text-white flex items-center justify-center font-bold shadow-md shrink-0"><?php echo e((string) ($chapter['position'] ?? ($chapterIndex + 1))); ?></span>
                          <div class="min-w-0">
                            <p class="font-semibold text-gray-900 dark:text-white truncate"><?php echo e((string) ($chapter['title'] ?? 'Chapitre')); ?></p>
                            <?php if (!empty($chapter['description'])): ?><p class="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-2"><?php echo e((string) $chapter['description']); ?></p><?php endif; ?>
                          </div>
                        </div>
                        <div class="text-right shrink-0">
                          <span class="inline-flex items-center rounded-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 px-3 py-1 text-xs font-semibold text-gray-600 dark:text-gray-300"><?php echo e(view_pluralize($countLessons, 'leçon')); ?></span>
                          <p class="text-sm text-gray-500 dark:text-gray-400 mt-2"><?php echo e($chapterDurationLabel); ?></p>
                          <?php if ($isEnrolled && $countLessons > 0): ?>
                            <div class="mt-3 h-2 w-32 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden ml-auto">
                              <div class="h-full rounded-full bg-gradient-to-r from-primary-600 to-accent-500" style="width: <?php echo e((string) $chapterPercent); ?>%;"></div>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2"><?php echo e((string) $chapterDoneCount); ?> / <?php echo e((string) $countLessons); ?> terminées</p>
                          <?php endif; ?>
                        </div>
                      </div>
                    </summary>

                    <div class="mt-4 pl-0 md:pl-14 space-y-3">
                      <?php if ($chapterDocs !== []): ?>
                        <div class="flex flex-wrap gap-2">
                          <span class="inline-flex items-center rounded-full bg-blue-50 dark:bg-blue-900/30 px-3 py-1 text-xs font-medium text-blue-700 dark:text-blue-300">
                            <i class="fas fa-file-alt mr-1"></i> <?php echo e(view_pluralize(count($chapterDocs), 'ressource')); ?>
                          </span>
                        </div>
                      <?php endif; ?>

                      <?php foreach ($chapterLessons as $lesson): ?>
                        <?php
                        $lessonId = (int) ($lesson['id'] ?? 0);
                        $lessonTitle = trim((string) ($lesson['title'] ?? 'Leçon'));
                        $lessonType = (string) ($lesson['lesson_type'] ?? '');
                        $lessonDone = $isEnrolled && in_array($lessonId, $doneByLesson, true);
                        $lessonDocs = is_array($docsByLesson[$lessonId] ?? null) ? $docsByLesson[$lessonId] : [];
                        $lessonDuration = !empty($lesson['duration_estimate_min'])
                            ? format_duration_minutes_hm((int) $lesson['duration_estimate_min'])
                            : '';
                        ?>
                        <div class="flex items-start gap-3 rounded-2xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800/60 px-4 py-3">
                          <div class="mt-0.5 w-10 h-10 rounded-xl bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-300 flex items-center justify-center shrink-0">
                            <i class="fas <?php echo e($lessonType === 'video' ? 'fa-play-circle' : 'fa-file-alt'); ?>"></i>
                          </div>
                          <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-3">
                              <div class="min-w-0">
                                <p class="font-medium text-gray-900 dark:text-white truncate"><?php echo e($lessonTitle); ?></p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                  <?php echo e($lessonType === 'video' ? 'Vidéo' : 'Leçon'); ?>
                                  <?php if ($lessonDuration !== ''): ?> · <?php echo e($lessonDuration); ?><?php endif; ?>
                                  <?php if ($publicPreviewAvailable && $publicPreviewOpenUrl !== '' && $publicPreviewLessonId !== null && $lessonId === $publicPreviewLessonId): ?> · <span class="text-primary-600 dark:text-primary-400">Aperçu gratuit</span><?php elseif (!empty($lesson['youtube_url'])): ?> · <span class="text-gray-500 dark:text-gray-400">Vidéo du cours</span><?php endif; ?>
                                </p>
                              </div>
                              <?php if ($isEnrolled): ?>
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold <?php echo $lessonDone ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300'; ?>">
                                  <?php echo $lessonDone ? 'Vue' : 'À voir'; ?>
                                </span>
                              <?php endif; ?>
                            </div>

                            <?php if ($lessonDocs !== []): ?>
                              <div class="mt-3 flex flex-wrap gap-2">
                                <?php foreach (array_slice($lessonDocs, 0, 3) as $document): ?>
                                  <a href="<?php echo e((string) ($document['url'] ?? '#')); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-full border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 px-3 py-1 text-xs font-medium text-gray-600 dark:text-gray-300 hover:border-primary-300 hover:text-primary-600 transition">
                                    <i class="fas fa-link mr-1"></i> <?php echo e((string) ($document['label'] ?? 'Ressource')); ?>
                                  </a>
                                <?php endforeach; ?>
                              </div>
                            <?php endif; ?>
                          </div>
                        </div>
                      <?php endforeach; ?>

                      <?php if ($chapterMeetings !== []): ?>
                        <div class="rounded-2xl border border-dashed border-gray-200 dark:border-gray-700 bg-white/70 dark:bg-gray-900/40 px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                          <?php echo e(view_pluralize(count($chapterMeetings), 'session')); ?> de rendez-vous sont associées à ce chapitre.
                        </div>
                      <?php endif; ?>
                    </div>
                  </details>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

          <?php if ($isEnrolled && $firstEmbedUrl !== ''): ?>
            <section id="player-card" class="scroll-mt-28 bg-white dark:bg-gray-800 rounded-2xl p-4 md:p-6 shadow-card border border-gray-100 dark:border-gray-700 course-compact-shell">
              <div id="main-player-card" data-current-lesson-id="<?php echo e($firstVideoId ? (string) $firstVideoId : ''); ?>">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
                  <div>
                    <div class="flex items-center gap-2 text-gray-900 dark:text-white">
                      <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-primary-600 text-white shadow-md"><i class="fas fa-play-circle"></i></span>
                      <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-500 dark:text-gray-400">Lecture en cours</p>
                        <h2 class="text-2xl font-bold">Suivi du cours</h2>
                      </div>
                    </div>
                    <p id="current-lesson-meta" class="mt-3 text-gray-600 dark:text-gray-400 font-medium"><?php echo e($firstEmbedTitle !== '' ? $firstEmbedTitle : 'Leçon en cours'); ?></p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Lecteur vidéo · <span id="player-title"><?php echo e($firstEmbedTitle !== '' ? $firstEmbedTitle : 'Vidéo'); ?></span></p>
                  </div>
                  <div class="flex flex-wrap items-center gap-2">
                    <div id="progress-pill" class="inline-flex items-center gap-2 rounded-full bg-gray-100 dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                      <span>Progression</span>
                      <span id="progress-counter"><?php echo e((string) $doneCount); ?> / <?php echo e((string) $totalLessons); ?></span>
                    </div>
                    <button id="btn-prev" type="button" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:border-primary-300 hover:text-primary-600 transition">Précédent</button>
                    <button id="btn-next" type="button" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:border-primary-300 hover:text-primary-600 transition">Suivant</button>
                    <button id="btn-mark" type="button" data-lesson-id="<?php echo e($firstVideoId ? (string) $firstVideoId : ''); ?>" data-done="<?php echo $firstVideoId && in_array($firstVideoId, $doneByLesson, true) ? '1' : '0'; ?>" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md hover:bg-primary-700 transition">
                      <?php echo $firstVideoId && in_array($firstVideoId, $doneByLesson, true) ? 'Marquée comme vue' : 'Marquer comme vue'; ?>
                    </button>
                  </div>
                </div>

                <div class="grid xl:grid-cols-3 gap-6">
                  <div class="xl:col-span-2 space-y-4">
                    <div id="player-wrapper" class="relative overflow-hidden rounded-2xl bg-slate-950 shadow-soft" aria-label="Lecteur principal">
                      <div class="aspect-video">
                        <iframe id="main-player" src="<?php echo e($firstEmbedUrl); ?>" allow="autoplay; encrypted-media; picture-in-picture; fullscreen" title="Lecteur vidéo principal" class="h-full w-full"></iframe>
                      </div>
                      <div id="player-overlay" class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/20 to-transparent" aria-hidden="true"></div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-4 md:p-5" data-guard-allow="1">
                      <div class="flex flex-wrap items-center gap-2">
                        <button data-action="back10" type="button" class="inline-flex items-center rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:border-primary-300 hover:text-primary-600 transition">-10 s</button>
                        <button id="btn-play" type="button" class="inline-flex items-center rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-md hover:bg-primary-700 transition">Lecture / pause</button>
                        <button data-action="fwd10" type="button" class="inline-flex items-center rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:border-primary-300 hover:text-primary-600 transition">+10 s</button>
                        <div class="flex-1 min-w-[260px] ml-0 lg:ml-3">
                          <input id="progress-range" type="range" min="0" max="1000" value="0" class="w-full accent-primary-600">
                        </div>
                        <span id="time-label" class="min-w-[96px] text-right text-xs text-gray-500 dark:text-gray-400">00:00 / 00:00</span>
                      </div>

                      <div class="mt-4 flex flex-wrap items-center gap-2">
                        <?php foreach (['0.75', '1', '1.25', '1.5', '2'] as $speed): ?>
                          <button type="button" class="speed-chip inline-flex items-center rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:border-primary-300 hover:text-primary-600 transition <?php echo $speed === '1' ? 'border-primary-500 bg-primary-500/10 text-primary-700 dark:text-primary-300' : ''; ?>" data-speed="<?php echo e($speed); ?>"><?php echo e($speed); ?>x</button>
                        <?php endforeach; ?>
                        <span id="player-speed-status" class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-3 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200">Vitesse active : 1.00x</span>
                      </div>

                      <div class="mt-4 flex flex-wrap items-center gap-2">
                        <input id="volume-range" type="range" min="0" max="100" value="80" class="w-32 accent-primary-600">
                        <button id="btn-mute" type="button" class="inline-flex items-center rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:border-primary-300 hover:text-primary-600 transition">Couper le son</button>
                        <button id="btn-pip" type="button" class="inline-flex items-center rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:border-primary-300 hover:text-primary-600 transition">Mini-lecteur</button>
                        <button id="btn-fullscreen" type="button" class="inline-flex items-center rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:border-primary-300 hover:text-primary-600 transition">Plein écran</button>
                        <button data-action="mark-done" type="button" class="inline-flex items-center rounded-xl bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-md hover:bg-black transition">Marquer comme vue</button>
                      </div>

                      <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                        <span id="progress-label" class="text-sm text-gray-600 dark:text-gray-400"><?php echo e((string) $progressPercent); ?>% du parcours complet</span>
                        <span id="remaining-label" class="text-sm text-gray-600 dark:text-gray-400">Temps restant : --</span>
                      </div>
                    </div>
                  </div>

                  <div class="space-y-4">
                    <section class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-4">
                      <div class="flex items-center gap-2">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-primary-600 text-white shadow-md"><i class="fas fa-book-open"></i></span>
                        <div>
                          <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-500 dark:text-gray-400">Notes</p>
                          <h3 class="font-bold text-gray-900 dark:text-white">Notes de la leçon</h3>
                        </div>
                      </div>
                      <p class="text-sm text-gray-600 dark:text-gray-400 mt-3">Vos notes sont enregistrées localement dans ce navigateur.</p>
                      <span id="notes-status" class="text-sm text-gray-500 dark:text-gray-400 block mt-1"></span>
                      <textarea id="lesson-notes" placeholder="Écrivez ici les points importants de cette leçon." class="mt-4 w-full min-h-[180px] rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 text-sm text-gray-800 dark:text-gray-200 outline-none focus:ring-2 focus:ring-primary-500"></textarea>
                      <div class="mt-4 flex flex-wrap gap-2">
                        <button id="btn-notes-doc" type="button" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:border-primary-300 hover:text-primary-600 transition"><i class="fas fa-file-word"></i> Exporter en Word</button>
                        <button id="btn-notes-pdf" type="button" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:border-primary-300 hover:text-primary-600 transition"><i class="fas fa-file-pdf"></i> Exporter en PDF</button>
                      </div>
                    </section>

                    <section class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                      <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                          <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-primary-600 text-white shadow-md"><i class="fas fa-layer-group"></i></span>
                          <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-500 dark:text-gray-400">Playlist</p>
                            <h3 class="font-bold text-gray-900 dark:text-white">Liste des vidéos</h3>
                          </div>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-3 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200"><?php echo e(view_pluralize((int) count($videoPlaylist), 'vidéo')); ?></span>
                      </div>
                      <div id="video-queue" class="mt-4 space-y-2">
                        <?php foreach ($videoPlaylist as $index => $video): ?>
                          <?php
                          $isDone = !empty($video['done']);
                          $queueTitle = trim((string) ($video['title'] ?? 'Vidéo'));
                          ?>
                          <button type="button" class="queue-item <?php echo $index === 0 ? 'active' : ''; ?> w-full rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 px-3 py-3 text-left hover:border-primary-300 hover:shadow-sm transition"
                            data-embed-url="<?php echo e((string) ($video['embed'] ?? '')); ?>"
                            data-video-id="<?php echo e((string) ($video['video_id'] ?? '')); ?>"
                            data-lesson-id="<?php echo e((string) ($video['id'] ?? '')); ?>"
                            data-lesson-title="<?php echo e($queueTitle); ?>"
                            data-lesson-pos="<?php echo e((string) ($video['lesson_pos'] ?? '')); ?>"
                            data-chapter-title="<?php echo e((string) ($video['chapter_title'] ?? '')); ?>"
                            data-chapter-pos="<?php echo e((string) ($video['chapter_pos'] ?? '')); ?>"
                            data-index="<?php echo e((string) $index); ?>"
                            data-done="<?php echo $isDone ? '1' : '0'; ?>">
                            <div class="flex items-start justify-between gap-3">
                              <div class="min-w-0">
                                <p class="font-medium text-gray-900 dark:text-white truncate"><?php echo e($queueTitle); ?></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1"><?php echo e(trim((string) ($video['chapter_title'] ?? '')) !== '' ? ('Chapitre ' . ($video['chapter_pos'] ?? '') . ' · ' . ($video['chapter_title'] ?? '')) : 'Leçon'); ?></p>
                              </div>
                              <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold <?php echo $isDone ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'; ?>">
                                <?php echo $isDone ? 'Vue' : 'À voir'; ?>
                              </span>
                            </div>
                          </button>
                        <?php endforeach; ?>
                      </div>
                    </section>
                  </div>
                </div>
              </div>
            </section>
          <?php elseif ($publicPreviewAvailable && $publicPreviewOpenUrl !== ''): ?>
            <section class="bg-white dark:bg-gray-800 rounded-2xl p-4 md:p-6 shadow-card border border-gray-100 dark:border-gray-700 course-compact-shell">
              <div class="flex items-start justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-2">
                  <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-primary-600 text-white shadow-md"><i class="fas fa-play-circle"></i></span>
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-500 dark:text-gray-400">Aperçu gratuit</p>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo e($previewTitle); ?></h2>
                  </div>
                </div>
                <a href="<?php echo e($ctaHref); ?>" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md hover:bg-primary-700 transition"><i class="fas fa-ticket-alt"></i> Acheter le cours</a>
              </div>
              <p class="text-gray-600 dark:text-gray-400 mt-4">Visionnez un extrait avant inscription.</p>
              <div class="mt-6 overflow-hidden rounded-2xl bg-slate-950 shadow-soft">
                <div class="aspect-video">
                  <iframe id="public-preview-player" data-preview-open-url="<?php echo e($publicPreviewOpenUrl); ?>" src="" allow="autoplay; encrypted-media; picture-in-picture; fullscreen" title="Aperçu public du cours" class="h-full w-full"></iframe>
                </div>
              </div>
              <div class="mt-6 grid md:grid-cols-2 gap-4">
                <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-4">
                  <p class="font-semibold text-gray-900 dark:text-white">Pourquoi passer à la version complète</p>
                  <ul class="mt-3 space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li class="flex items-start gap-2"><i class="fas fa-check-circle text-emerald-500 mt-1"></i><span>Accès à toutes les leçons et ressources du cours.</span></li>
                    <li class="flex items-start gap-2"><i class="fas fa-check-circle text-emerald-500 mt-1"></i><span>Suivi de progression, évaluations et certification lorsqu’ils sont proposés.</span></li>
                    <li class="flex items-start gap-2"><i class="fas fa-check-circle text-emerald-500 mt-1"></i><span>Possibilité de réserver un rendez-vous avec le formateur lorsque des sessions sont disponibles.</span></li>
                  </ul>
                </div>
                <div class="rounded-2xl border border-dashed border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-4">
                  <p class="font-semibold text-gray-900 dark:text-white">Raccourcis</p>
                  <div class="mt-3 flex flex-wrap gap-2">
                    <a href="<?php echo e($ctaHref); ?>" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-md hover:bg-primary-700 transition"><i class="fas fa-ticket-alt"></i> Acheter le cours</a>
                    <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:border-primary-300 hover:text-primary-600 transition" data-public-preview-play><i class="fas fa-external-link-alt"></i> Voir l’aperçu gratuit</button>
                  </div>
                </div>
              </div>
            </section>
          <?php else: ?>
            <section class="bg-white dark:bg-gray-800 rounded-2xl p-4 md:p-6 shadow-card border border-gray-100 dark:border-gray-700 course-compact-shell">
              <div class="rounded-2xl border border-dashed border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 px-4 py-6 text-center text-gray-600 dark:text-gray-400">
                <p class="font-medium text-gray-900 dark:text-white">Aucun aperçu vidéo gratuit n’est encore publié pour ce cours.</p>
                <p class="mt-2">Programme et sessions visibles avant inscription.</p>
              </div>
            </section>
          <?php endif; ?>

          <?php if ($meetingCount > 0): ?>
            <section id="sessions" class="scroll-mt-28 bg-white dark:bg-gray-800 rounded-2xl p-4 md:p-6 shadow-card border border-gray-100 dark:border-gray-700 course-compact-shell">
              <div class="flex items-center justify-between gap-4 mb-6">
                <div class="flex items-center gap-2">
                  <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-primary-600 text-white shadow-md"><i class="fas fa-calendar-alt"></i></span>
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-500 dark:text-gray-400">Rendez-vous</p>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Sessions proposées</h2>
                  </div>
                </div>
                <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-3 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200"><?php echo e(view_pluralize($meetingCount, 'session')); ?></span>
              </div>

              <div class="space-y-4">
                <?php foreach ($chapters as $chapter): ?>
                  <?php
                  $chapterId = (int) ($chapter['id'] ?? 0);
                  $chapterMeetings = is_array($meetingsByChapter[$chapterId] ?? null) ? $meetingsByChapter[$chapterId] : [];
                  if ($chapterMeetings === []) {
                      continue;
                  }
                  ?>
                  <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-900/40 p-4">
                    <div class="flex items-center gap-2">
                      <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-primary-600 text-white shadow-sm"><i class="fas fa-book-open"></i></span>
                      <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-500 dark:text-gray-400">Chapitre concerné</p>
                        <h3 class="font-bold text-gray-900 dark:text-white"><?php echo e((string) ($chapter['title'] ?? 'Chapitre')); ?></h3>
                      </div>
                    </div>

                    <div class="mt-4 space-y-3">
                      <?php foreach ($chapterMeetings as $meeting): ?>
                        <?php
                        $meetingTitle = (string) ($meeting['title'] ?? 'Rendez-vous');
                        $meetingMode = (string) ($meeting['mode'] ?? 'online');
                        $meetingDurationMin = (int) ($meeting['duration_min'] ?? 0);
                        $meetingHasDuration = $meetingDurationMin > 0;
                        $meetingKey = $chapterId . '|' . $meetingTitle . '|' . $meetingMode;
                        $requests = is_array($myMeetingsByKey[$meetingKey] ?? null) ? $myMeetingsByKey[$meetingKey] : [];
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
                        <article class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                          <div class="flex items-center justify-between gap-3">
                            <div>
                              <p class="font-semibold text-gray-900 dark:text-white"><?php echo e($meetingTitle); ?></p>
                              <p class="text-sm text-gray-500 dark:text-gray-400 mt-1"><?php echo $meetingMode === 'online' ? 'En ligne' : 'Présentiel'; ?><?php if ($meetingHasDuration): ?> · <?php echo e(format_duration_minutes_hm($meetingDurationMin)); ?><?php else: ?> · Durée à définir<?php endif; ?></p>
                            </div>
                            <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-3 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200">Rendez-vous</span>
                          </div>

                          <?php if ($isEnrolled): ?>
                            <div class="mt-4 space-y-3">
                              <?php if ($confirmedMeeting): ?>
                                <div class="rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-200">
                                  <p class="font-semibold">Rendez-vous confirmé</p>
                                  <p class="mt-1"><?php echo e(view_format_date_label($confirmedMeeting['start_at'] ?? null, true)); ?> · <?php echo e(view_format_date_label($confirmedMeeting['end_at'] ?? null, true)); ?></p>
                                </div>
                              <?php else: ?>
                                <?php if ($pendingMeetings !== []): ?>
                                  <div class="rounded-2xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 px-4 py-3 text-sm text-amber-800 dark:text-amber-200">
                                    <p class="font-semibold">Demandes en attente</p>
                                    <?php foreach (array_slice($pendingMeetings, 0, 3) as $pendingMeeting): ?>
                                      <p class="mt-1">- <?php echo e(view_format_date_label($pendingMeeting['start_at'] ?? null, true)); ?> · <?php echo e(view_format_date_label($pendingMeeting['end_at'] ?? null, true)); ?></p>
                                    <?php endforeach; ?>
                                  </div>
                                <?php endif; ?>
                                <?php if ($meetingHasDuration): ?>
                                  <?php if ($pendingMeetings !== []): ?>
                                    <form method="post" class="flex justify-end">
                                      <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                      <input type="hidden" name="action" value="cancel_meeting_requests">
                                      <input type="hidden" name="meeting_template_id" value="<?php echo e((string) ($meeting['id'] ?? '0')); ?>">
                                      <button type="submit" class="inline-flex items-center rounded-xl border border-red-300 dark:border-red-700 px-4 py-2 text-sm font-semibold text-red-700 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 transition">Annuler mes demandes</button>
                                    </form>
                                  <?php endif; ?>
                                  <form method="post" class="grid gap-3">
                                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                    <input type="hidden" name="action" value="request_meeting">
                                    <input type="hidden" name="meeting_template_id" value="<?php echo e((string) ($meeting['id'] ?? '0')); ?>">
                                    <div class="grid md:grid-cols-3 gap-3">
                                      <?php for ($slotIndex = 0; $slotIndex < 3; $slotIndex++): ?>
                                        <label class="block">
                                          <span class="block text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400 mb-2">Créneau <?php echo e((string) ($slotIndex + 1)); ?></span>
                                          <input type="datetime-local" name="slot_start[]" required value="<?php echo e((string) ($pendingMeetings[$slotIndex]['start_local'] ?? '')); ?>" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2.5 text-sm text-gray-800 dark:text-gray-200 outline-none focus:ring-2 focus:ring-primary-500">
                                        </label>
                                      <?php endfor; ?>
                                    </div>
                                    <div class="flex justify-end">
                                      <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md hover:bg-primary-700 transition">Envoyer ma demande</button>
                                    </div>
                                  </form>
                                <?php else: ?>
                                  <div class="rounded-2xl border border-dashed border-gray-200 dark:border-gray-700 px-4 py-3 text-sm text-gray-600 dark:text-gray-400">La durée de cette session n’est pas encore définie par le formateur.</div>
                                <?php endif; ?>
                              <?php endif; ?>
                            </div>
                          <?php else: ?>
                            <div class="mt-4 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700 px-4 py-3 text-sm text-gray-600 dark:text-gray-400">Cette session sera accessible après votre inscription au cours.</div>
                          <?php endif; ?>
                        </article>
                      <?php endforeach; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </section>
          <?php endif; ?>

          <section id="avis" class="scroll-mt-28 bg-white dark:bg-gray-800 rounded-2xl p-4 md:p-6 shadow-card border border-gray-100 dark:border-gray-700 course-compact-shell">
            <div class="flex items-center justify-between gap-4 mb-6">
              <div class="flex items-center gap-2">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-primary-600 text-white shadow-md"><i class="fas fa-clipboard-list"></i></span>
                <div>
                  <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-500 dark:text-gray-400">Évaluation</p>
                  <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Examens</h2>
                </div>
              </div>
              <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-3 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200"><?php echo e(view_pluralize($examCount, 'session')); ?></span>
            </div>

            <?php if ($examSessions !== []): ?>
              <div class="space-y-3">
                <?php foreach ($examSessions as $exam): ?>
                  <?php
                  $examTypeLabel = (string) ($exam['type'] ?? '') === 'qcm'
                      ? 'QCM'
                      : ((string) ($exam['type'] ?? '') === 'meeting' ? 'Rendez-vous' : 'Lien Drive');
                  $examDurationLabel = !empty($exam['duration_min'])
                      ? format_duration_minutes_hm((int) $exam['duration_min'])
                      : 'Durée non définie';
                  ?>
                  <article class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-900/40 p-4">
                    <div class="flex items-center justify-between gap-4">
                      <div class="min-w-0">
                        <p class="font-semibold text-gray-900 dark:text-white"><?php echo e((string) ($exam['title'] ?? 'Examen')); ?></p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1"><?php echo e($examTypeLabel); ?> · <?php echo e($examDurationLabel); ?></p>
                      </div>
                      <div class="shrink-0">
                        <?php if ($isEnrolled): ?>
                          <?php if ($examLocked): ?>
                            <button type="button" disabled class="inline-flex items-center rounded-xl border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm font-semibold text-gray-500 dark:text-gray-400" title="Terminez toutes les leçons pour débloquer cet examen">Bloqué</button>
                          <?php else: ?>
                            <a href="<?php echo e(site_url('student/exam?exam_id=' . (int) ($exam['id'] ?? 0))); ?>" class="inline-flex items-center rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-md hover:bg-primary-700 transition">Passer l’examen</a>
                          <?php endif; ?>
                        <?php else: ?>
                          <span class="inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-900/30 px-3 py-1 text-xs font-semibold text-blue-700 dark:text-blue-300">Accessible après inscription</span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </article>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="rounded-2xl border border-dashed border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30 px-5 py-6 text-gray-600 dark:text-gray-400">Aucun examen n’est publié pour le moment.</div>
            <?php endif; ?>
          </section>

          <section id="formateur" class="scroll-mt-28 bg-white dark:bg-gray-800 rounded-2xl p-4 md:p-6 shadow-card border border-gray-100 dark:border-gray-700 course-compact-shell">
            <div class="flex items-center justify-between gap-4 mb-6">
              <div class="flex items-center gap-2">
                  <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-primary-600 text-white shadow-md">
                    <?php if (trim($teacherAvatarUrl) !== ''): ?>
                      <img src="<?php echo e($teacherAvatarUrl); ?>" alt="<?php echo e($teacherFullName); ?>" class="h-full w-full rounded-xl object-cover">
                    <?php else: ?>
                      <i class="fas fa-user-tie"></i>
                    <?php endif; ?>
                  </span>
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-500 dark:text-gray-400">Formateur</p>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo e($teacherFullName); ?></h2>
                  </div>
                </div>
              </div>

            <?php
            $teacherProfessionalTitle = trim((string) ($teacherProfile['professional_title'] ?? ''));
            $teacherShortBio = trim((string) ($teacherProfile['short_bio'] ?? ''));
            $teacherPortfolioUrl = trim((string) ($teacherProfile['portfolio_url'] ?? ''));
            $teacherLinkedinUrl = trim((string) ($teacherProfile['linkedin_url'] ?? ''));
            ?>

            <div class="flex flex-col gap-5 lg:flex-row">
              <div class="flex shrink-0 items-start gap-4">
                <img src="<?php echo e($teacherAvatarUrl); ?>" alt="<?php echo e($teacherFullName); ?>" class="w-20 h-20 rounded-full object-cover border-4 border-white dark:border-gray-800 shadow shrink-0">
                <div class="min-w-0">
                  <h3 class="text-xl font-bold text-gray-900 dark:text-white"><?php echo e($teacherFullName); ?></h3>
                  <p class="text-primary-600 dark:text-primary-400 text-sm font-medium mb-2"><?php echo e($teacherProfessionalTitle !== '' ? $teacherProfessionalTitle : 'Profil public non complété'); ?></p>
                  <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <span class="inline-flex items-center gap-1"><i class="fas fa-star text-yellow-400"></i><?php echo e($ratingCount > 0 ? $ratingAverageLabel . ' moyenne du cours' : 'Aucun avis pour le moment'); ?></span>
                    <span class="inline-flex items-center gap-1"><i class="fas fa-user-friends ml-1"></i><?php echo e(number_format($buyersCount, 0, ',', ' ')); ?> apprenants</span>
                  </div>
                </div>
              </div>

              <div class="min-w-0 flex-1 space-y-4">
                <?php if ($teacherShortBio !== ''): ?>
                  <div class="rounded-2xl border border-gray-100 bg-gray-50/90 p-4 dark:border-gray-700 dark:bg-gray-900/30">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-500 dark:text-gray-400">Bio publique</p>
                    <p class="mt-2 text-gray-600 dark:text-gray-400 leading-7"><?php echo e($teacherShortBio); ?></p>
                  </div>
                <?php else: ?>
                  <div class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-5 py-4 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900/30 dark:text-gray-400">
                    Ce formateur n’a pas encore complété sa présentation publique.
                  </div>
                <?php endif; ?>

                <div class="flex flex-wrap gap-3">
                  <?php if ($teacherPortfolioUrl !== ''): ?>
                    <?php $teacherPortfolioHref = preg_match('~^(?:https?://|mailto:)~i', $teacherPortfolioUrl) === 1 ? $teacherPortfolioUrl : site_url(ltrim($teacherPortfolioUrl, '/')); ?>
                    <a href="<?php echo e($teacherPortfolioHref); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:border-primary-300 hover:text-primary-600 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-200">
                      <i class="fas fa-briefcase"></i> Portfolio
                    </a>
                  <?php endif; ?>
                  <?php if ($teacherLinkedinUrl !== ''): ?>
                    <?php $teacherLinkedinHref = preg_match('~^(?:https?://|mailto:)~i', $teacherLinkedinUrl) === 1 ? $teacherLinkedinUrl : site_url(ltrim($teacherLinkedinUrl, '/')); ?>
                    <a href="<?php echo e($teacherLinkedinHref); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:border-primary-300 hover:text-primary-600 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-200">
                      <i class="fab fa-linkedin"></i> LinkedIn
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </section>

          <section class="bg-white dark:bg-gray-800 rounded-2xl p-4 md:p-6 shadow-card border border-gray-100 dark:border-gray-700 course-compact-shell">
            <div class="flex items-center justify-between gap-4 mb-6">
              <div class="flex items-center gap-2">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-primary-600 text-white shadow-md"><i class="fas fa-comments"></i></span>
                <div>
                  <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-500 dark:text-gray-400">Qualité perçue</p>
                  <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Avis des apprenants</h2>
                </div>
              </div>
              <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-3 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200"><?php echo e(view_pluralize($reviewsCount, 'avis', 'avis')); ?></span>
            </div>

            <?php if ($reviewsCount > 0 && $reviewsAverage !== null): ?>
              <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-4">
                <p class="font-semibold text-gray-900 dark:text-white">Note moyenne : <?php echo e($reviewsAverage); ?>/5</p>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Cette moyenne est calculée sur l’ensemble des avis publiés pour ce cours.</p>
              </div>

              <div class="mt-6 space-y-5">
                <?php foreach (array_slice($reviews, 0, 3) as $review): ?>
                  <?php
                  $reviewName = trim(((string) ($review['first_name'] ?? '')) . ' ' . ((string) ($review['last_name'] ?? '')));
                  if ($reviewName === '') {
                      $reviewName = 'Utilisateur';
                  }
                  $reviewAvatarUrl = $makeAvatarPlaceholder($reviewName);
                  $reviewRating = max(0.0, min(5.0, (float) ($review['rating'] ?? 0)));
                  ?>
                  <article class="rounded-2xl border-b border-gray-100 dark:border-gray-700 pb-5 last:border-0 last:pb-0">
                    <div class="flex items-start gap-4">
                      <img src="<?php echo e($reviewAvatarUrl); ?>" alt="<?php echo e($reviewName); ?>" class="w-11 h-11 rounded-full object-cover">
                      <div class="flex-1">
                        <div class="flex items-center justify-between gap-3">
                          <h4 class="font-bold text-gray-900 dark:text-white"><?php echo e($reviewName); ?></h4>
                          <span class="text-xs text-gray-500 dark:text-gray-400"><?php echo e(view_format_date_label($review['created_at'] ?? null)); ?></span>
                        </div>
                        <div class="flex items-center gap-0.5 text-yellow-400 text-sm my-1">
                          <?php for ($star = 1; $star <= 5; $star++): ?>
                            <i class="<?php echo e($reviewRating >= $star ? 'fas' : 'far'); ?> fa-star"></i>
                          <?php endfor; ?>
                        </div>
                        <?php if (!empty($review['comment'])): ?><p class="text-gray-600 dark:text-gray-400 text-sm"><?php echo e((string) $review['comment']); ?></p><?php endif; ?>
                      </div>
                    </div>
                  </article>
                <?php endforeach; ?>
              </div>

                <?php if ($reviewsCount > 3): ?>
                  <a href="#avis" class="mt-6 inline-flex items-center gap-2 text-primary-600 dark:text-primary-400 font-medium text-sm hover:underline">
                  Voir les <?php echo e((string) $reviewsCount); ?> avis <i class="fas fa-arrow-right"></i>
                </a>
              <?php endif; ?>
            <?php else: ?>
              <div class="rounded-2xl border border-dashed border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30 px-5 py-6 text-gray-600 dark:text-gray-400">
                Aucun avis n’a encore été déposé pour ce cours.
              </div>
            <?php endif; ?>

            <?php if ($isEnrolled): ?>
              <details class="mt-6 rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-5">
                <summary class="cursor-pointer list-none flex items-center justify-between gap-3">
                  <span class="inline-flex items-center gap-2 text-lg font-bold text-gray-900 dark:text-white">
                    <i class="fas fa-pen-nib text-primary-500"></i>
                    Donner votre avis
                  </span>
                  <span class="text-sm text-gray-500 dark:text-gray-400">Ouvrir le formulaire</span>
                </summary>
                <div class="pt-4">
                  <?php if ($userRating): ?><p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Votre note actuelle : <?php echo e((string) ($userRating['rating'] ?? '')); ?>/5.</p><?php endif; ?>
                  <form id="rating-form" action="<?php echo e(site_url('api/rating')); ?>" class="grid gap-4">
                    <div>
                      <label for="rating-select" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Note</label>
                      <select name="rating" id="rating-select" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 text-sm text-gray-800 dark:text-gray-200 outline-none focus:ring-2 focus:ring-primary-500">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                          <option value="<?php echo $i; ?>" <?php echo ($userRating && (int) ($userRating['rating'] ?? 0) === $i) ? 'selected' : ''; ?>><?php echo $i; ?>/5</option>
                        <?php endfor; ?>
                      </select>
                    </div>
                    <div>
                      <label for="rating-comment" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Commentaire</label>
                      <textarea name="comment" id="rating-comment" rows="4" placeholder="Expliquez ce qui vous a aidé et ce qui pourrait encore être amélioré." class="w-full rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 text-sm text-gray-800 dark:text-gray-200 outline-none focus:ring-2 focus:ring-primary-500"><?php echo e((string) ($userRating['comment'] ?? '')); ?></textarea>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                      <span id="rating-status" class="text-sm text-gray-500 dark:text-gray-400"></span>
                      <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-5 py-3 text-sm font-semibold text-white shadow-md hover:bg-primary-700 transition">Enregistrer mon avis</button>
                    </div>
                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                    <input type="hidden" name="course_id" value="<?php echo e((string) ($course['id'] ?? '0')); ?>">
                  </form>
                </div>
              </details>
            <?php endif; ?>
          </section>
        </div>

        <div class="lg:col-span-1 space-y-6">
          <div id="course-share" class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-card border border-gray-100 dark:border-gray-700" data-share-url="<?php echo e($courseShareUrl); ?>" data-share-title="<?php echo e($courseTitle); ?>">
            <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2"><i class="fas fa-share-alt text-primary-500"></i> Partager</h3>
            <div class="flex gap-3 flex-wrap">
              <?php if ($shareLinks['whatsapp'] !== ''): ?><a href="<?php echo e($shareLinks['whatsapp']); ?>" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-primary-100 hover:text-primary-600 transition"><i class="fab fa-whatsapp"></i></a><?php endif; ?>
              <?php if ($shareLinks['facebook'] !== ''): ?><a href="<?php echo e($shareLinks['facebook']); ?>" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-primary-100 hover:text-primary-600 transition"><i class="fab fa-facebook-f"></i></a><?php endif; ?>
              <?php if ($shareLinks['x'] !== ''): ?><a href="<?php echo e($shareLinks['x']); ?>" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-primary-100 hover:text-primary-600 transition"><i class="fab fa-twitter"></i></a><?php endif; ?>
              <?php if ($shareLinks['linkedin'] !== ''): ?><a href="<?php echo e($shareLinks['linkedin']); ?>" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-primary-100 hover:text-primary-600 transition"><i class="fab fa-linkedin-in"></i></a><?php endif; ?>
              <button type="button" id="btn-toggle-share" aria-controls="course-share-panel" aria-expanded="false" class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-primary-100 hover:text-primary-600 transition"><i class="fas fa-link"></i></button>
            </div>

            <div id="course-share-panel" class="hidden mt-4">
              <button type="button" id="btn-native-share" class="hidden w-full inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 dark:border-gray-700 px-4 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:border-primary-300 hover:text-primary-600 transition">
                <i class="fas fa-external-link-alt"></i> Partager avec l’appareil
              </button>

              <div class="mt-4">
                <label for="course-share-url" class="block text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400 mb-2">Lien public</label>
                <div class="flex gap-2">
                  <input id="course-share-url" class="flex-1 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2.5 text-sm text-gray-800 dark:text-gray-200" readonly value="<?php echo e($courseShareUrl); ?>">
                  <button type="button" id="btn-copy-course-link" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:border-primary-300 hover:text-primary-600 transition">Copier</button>
                </div>
              </div>

              <?php if ($referralCode !== ''): ?>
                <div class="mt-4">
                  <label for="course-ref-code" class="block text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400 mb-2">Code de parrainage</label>
                  <div class="flex gap-2">
                    <input id="course-ref-code" class="flex-1 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2.5 text-sm text-gray-800 dark:text-gray-200" readonly value="<?php echo e($referralCode); ?>">
                    <button type="button" id="btn-copy-ref-code" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:border-primary-300 hover:text-primary-600 transition">Copier</button>
                  </div>
                </div>
              <?php endif; ?>

              <?php if ($referralLink !== ''): ?>
                <div class="mt-4">
                  <label for="course-ref-link" class="block text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400 mb-2">Lien de parrainage</label>
                  <div class="flex gap-2">
                    <input id="course-ref-link" class="flex-1 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2.5 text-sm text-gray-800 dark:text-gray-200" readonly value="<?php echo e($referralLink); ?>">
                    <button type="button" id="btn-copy-ref-link" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-4 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:border-primary-300 hover:text-primary-600 transition">Copier le lien</button>
                  </div>
                </div>
              <?php endif; ?>

              <p id="course-share-status" class="mt-3 text-sm text-gray-500 dark:text-gray-400"></p>
            </div>
          </div>

          <div class="bg-gradient-to-br from-primary-50 to-blue-50 dark:from-primary-900/20 dark:to-blue-900/20 rounded-2xl p-6 border border-primary-100 dark:border-primary-800">
            <h3 class="font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2"><i class="fas fa-gift text-primary-600"></i> Offre de lancement</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
              <?php if ($promoActive && $promoDiscountLabel !== ''): ?>
                Réduction active sur ce cours : <?php echo e($promoDiscountLabel); ?>.
              <?php else: ?>
                Ce cours publie <?php echo e(view_pluralize($chapterCount, 'chapitre')); ?>, <?php echo e(view_pluralize($totalLessonsAll, 'leçon')); ?> et <?php echo e($resourceCount > 0 ? view_pluralize($resourceCount, 'ressource') : 'aucune ressource'); ?>.
              <?php endif; ?>
            </p>
            <span class="inline-block bg-primary-600 text-white text-xs font-bold px-3 py-1 rounded-full">
              <?php echo e($publicPreviewAvailable && $publicPreviewOpenUrl !== '' ? 'Aperçu gratuit' : 'Contenu publié'); ?>
            </span>
          </div>
        </div>
      </div>
    </section>
  </main>

  <?php require __DIR__ . '/_public_footer_home.php'; ?>
</body>
</html>
