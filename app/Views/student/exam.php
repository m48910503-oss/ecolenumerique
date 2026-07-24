<?php
declare(strict_types=1);
 
$exam = is_array($exam ?? null) ? $exam : [];
$errors = is_array($errors ?? null) ? $errors : [];
$success = trim((string) ($success ?? ''));
$examType = trim((string) ($examType ?? 'qcm'));
$maxAttempts = max(1, (int) ($maxAttempts ?? 1));
$minScore = max(0, (int) ($minScore ?? 0));
$driveReady = !empty($driveReady);
$driveUploadNote = trim((string) ($driveUploadNote ?? ''));
$driveMaxMb = max(1, (int) ($driveMaxMb ?? 10));
$attemptsHasMeetingId = !empty($attemptsHasMeetingId);
$attemptCount = max(0, (int) ($attemptCount ?? 0)); 
$hasPassedAttempt = !empty($hasPassedAttempt);
$certificateCode = trim((string) ($certificateCode ?? ''));
$maxScore = max(0, (int) ($maxScore ?? 0));
$activeVariant = max(1, min(3, (int) ($activeVariant ?? 1)));
$variantLabels = is_array($variantLabels ?? null) ? $variantLabels : [1 => 'Version A', 2 => 'Version B', 3 => 'Version C'];
$progressLocked = !empty($progressLocked);
$progressTotal = max(0, (int) ($progressTotal ?? 0));
$progressDone = max(0, (int) ($progressDone ?? 0));
$progressPercent = max(0, min(100, (int) ($progressPercent ?? 0)));
$isAdmin = !empty($isAdmin);
$isCourseOwner = !empty($isCourseOwner);
$courseUrl = trim((string) ($courseUrl ?? site_url('student/my_courses')));
$canAttempt = !empty($canAttempt);
$remainingAttempts = max(0, (int) ($remainingAttempts ?? 0));
$typeLabel = trim((string) ($typeLabel ?? 'Examen'));
$u = static fn(string $text): string => html_entity_decode($text, ENT_QUOTES, 'UTF-8');
$durationLabel = trim((string) ($durationLabel ?? $u('Dur&eacute;e libre')));
$driveSubjectLink = trim((string) ($driveSubjectLink ?? ''));
$driveSubjectText = trim((string) ($driveSubjectText ?? ''));
$showRetry = !empty($showRetry);
$lastAttemptUi = is_array($lastAttemptUi ?? null) ? $lastAttemptUi : null;
$questionsUi = is_array($questionsUi ?? null) ? $questionsUi : [];
$antiCheatSettings = is_array($antiCheatSettings ?? null) ? $antiCheatSettings : [];
$availabilityBlocked = !empty($availabilityBlocked);
$availabilityBlockedReason = trim((string) ($availabilityBlockedReason ?? ''));
$meetingMyUi = is_array($meetingMyUi ?? null) ? $meetingMyUi : null;
$meetingCanBook = !empty($meetingCanBook);
$meetingSlotsUi = is_array($meetingSlotsUi ?? null) ? $meetingSlotsUi : [];
$attemptHistoryUi = is_array($attemptHistoryUi ?? null) ? $attemptHistoryUi : [];
$siteLogoUrl = trim((string) ($siteLogoUrl ?? ''));
$user = is_array($user ?? null) ? $user : [];
$csrfToken = view_csrf_token();
$certificateViewUrl = $certificateCode !== '' ? site_url('certificate?code=' . urlencode($certificateCode)) : '';
$certificatePdfUrl = $certificateCode !== '' ? site_url('certificate?code=' . urlencode($certificateCode) . '&format=pdf') : '';

$surfaceClass = 'rounded-3xl border border-slate-300 bg-white/95 shadow-[0_22px_48px_-34px_rgba(15,23,42,0.26)] dark:border-slate-800 dark:bg-slate-950/50 dark:shadow-none';
$softCardClass = 'rounded-2xl border border-slate-300 bg-slate-50 shadow-sm dark:border-slate-800 dark:bg-slate-900/55 dark:shadow-none';
$panelCardClass = 'rounded-2xl border border-slate-300 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/65 dark:shadow-none';

$courseTitle = trim((string) ($exam['course_title'] ?? 'Cours'));
$examTitle = trim((string) ($exam['title'] ?? 'Examen'));
$examDescription = trim((string) ($exam['description'] ?? ''));
$questionCount = count($questionsUi);
$slotCount = count($meetingSlotsUi);
$studentUserId = (int) ($user['id'] ?? 0);

$antiRules = [];
if (!empty($antiCheatSettings['fullscreen_required'])) {
    $antiRules[] = $u('Plein &eacute;cran requis');
}
if (!empty($antiCheatSettings['copy_paste_block'])) {
    $antiRules[] = $u('Copier/coller bloqu&eacute;');
}
if (!empty($antiCheatSettings['tab_switch_warning'])) {
    $antiRules[] = $u('Alerte en cas de changement d&rsquo;onglet');
}
if (!empty($antiCheatSettings['camera_required'])) {
    $antiRules[] = $u('Cam&eacute;ra requise');
}
$statusTone = static function (string $status) : array {
    $value = trim((string) $status);

    if ($value === 'graded') {
        return [
            'badge' => 'app-status-pill app-status-pill--active',
            'text' => 'app-inline-note',
        ];
    }
if ($value === 'submitted') {
        return [
            'badge' => 'app-status-pill app-status-pill--pending',
            'text' => 'app-inline-note app-inline-note--warning',
        ];
    }
return [
        'badge' => 'app-status-pill app-status-pill--done',
        'text' => 'text-slate-700 dark:text-slate-200',
    ];
};

$lastAttemptTone = $statusTone((string) ($lastAttemptUi['status'] ?? ''));
$lastAttemptStatusLabel = trim((string) ($lastAttemptUi['status_label'] ?? 'Aucune tentative'));
$lastAttemptDateLabel = trim((string) ($lastAttemptUi['submitted_at_label'] ?? ''));
$lastAttemptVariantLabel = trim((string) ($lastAttemptUi['variant_label'] ?? ''));
$lastAttemptScoreLabel = '--';
if ($lastAttemptUi && ($lastAttemptUi['status'] ?? '') === 'graded' && ($lastAttemptUi['score'] ?? null) !== null) {
    $lastAttemptScoreLabel = (string) ((int) $lastAttemptUi['score']);
    if ($examType === 'qcm' && (int) ($lastAttemptUi['max_score'] ?? 0) > 0) {
        $lastAttemptScoreLabel .= '/' . (string) ((int) $lastAttemptUi['max_score']);
    }
}
$scoreTargetValue = $examType === 'qcm' && $maxScore > 0 ? ((string) $minScore . '/' . (string) $maxScore) : ($minScore > 0 ? (string) $minScore : 'Libre');
$scoreTargetMeta = $examType === 'qcm' && $maxScore > 0 ? 'Note minimale / maximale' : 'Seuil de validation';
$attemptsValue = (string) $attemptCount . '/' . (string) $maxAttempts;
$attemptsMeta = $showRetry ? (string) $remainingAttempts . ' tentative(s) restante(s)' : 'Historique conservé';
$progressValue = $progressTotal > 0 ? (string) $progressPercent . '%' : '--';
$attemptsMeta = $showRetry ? (string) $remainingAttempts . ' tentative(s) restante(s)' : 'Historique conserve';
if (($lastAttemptUi['status'] ?? '') === 'submitted') {
    $attemptsMeta = 'Correction en cours';
} elseif (!$showRetry && !$canAttempt && $remainingAttempts === 0 && !$hasPassedAttempt) {
    $attemptsMeta = 'Limite atteinte';
}
$progressMeta = $progressTotal > 0 ? (string) $progressDone . ' ' . $u('le&ccedil;on(s) termin&eacute;e(s) sur') . ' ' . (string) $progressTotal : 'Non applicable';
$modeValue = $examType === 'meeting' ? 'Rendez-vous' : ($examType === 'drive' ? $u('D&eacute;p&ocirc;t Drive') : 'QCM');
$modeMeta = $examType === 'meeting' ? (string) $slotCount . ' ' . $u('cr&eacute;neau(x) disponible(s)') : ($examType === 'drive' ? 'Remise de fichier' : (string) $questionCount . ' question(s)');
$actionAvailable = $examType === 'meeting' ? ($meetingMyUi !== null || $meetingCanBook) : $canAttempt;

$meetingReservationStatusLabel = $u('R&eacute;serv&eacute;');
if ($meetingMyUi) {
    $meetingAttemptStatus = trim((string) ($meetingMyUi['attempt_status'] ?? ''));
    if ($meetingAttemptStatus === 'graded') {
        $meetingReservationStatusLabel = !empty($meetingMyUi['attempt_passed']) ? $u('Valid&eacute;') : $u('&Eacute;valu&eacute;');
    } elseif ($meetingAttemptStatus === 'submitted') {
        $meetingReservationStatusLabel = 'En attente';
    } elseif (!empty($meetingMyUi['can_cancel'])) {
        $meetingReservationStatusLabel = $u('R&eacute;serv&eacute;');
    } else {
        $meetingReservationStatusLabel = 'Programme';
    }
}
$heroStateLabel = 'Disponible';
$heroStateClass = 'app-status-pill app-status-pill--info';
$heroStateSummary = 'La session est accessible depuis cette page.';
if ($isAdmin || $isCourseOwner) {
    $heroStateLabel = 'Supervision';
    $heroStateClass = 'app-status-pill app-status-pill--sky';
    $heroStateSummary = $u('Les restrictions standards sont lev&eacute;es pour ce compte.');
} elseif ($meetingMyUi) {
    $heroStateLabel = $u('R&eacute;servation active');
    $heroStateClass = 'app-status-pill app-status-pill--sky';
    $heroStateSummary = $u('Votre cr&eacute;neau est d&eacute;j&agrave; enregistr&eacute;. Retrouvez ici les d&eacute;tails et le suivi.');
} elseif ($certificateCode !== '' || $hasPassedAttempt) {
    $heroStateLabel = $u('Valid&eacute;');
    $heroStateClass = 'app-status-pill app-status-pill--active';
    $heroStateSummary = $u('Cet examen a d&eacute;j&agrave; &eacute;t&eacute; valid&eacute;.');
} elseif ($progressLocked) {
    $heroStateLabel = 'Cours incomplet';
    $heroStateClass = 'app-status-pill app-status-pill--pending';
    $heroStateSummary = $u('Terminez toutes les le&ccedil;ons du cours pour d&eacute;bloquer cette session.');
} elseif ($availabilityBlocked) {
    $heroStateLabel = 'Indisponible';
    $heroStateClass = 'app-status-pill app-status-pill--urgent';
    $heroStateSummary = $availabilityBlockedReason !== '' ? $availabilityBlockedReason : $u('Cette session n&rsquo;est pas ouverte.');
} elseif ($examType === 'qcm' && ($lastAttemptUi['status'] ?? '') === 'submitted') {
    $heroStateLabel = 'Correction en cours';
    $heroStateClass = 'app-status-pill app-status-pill--pending';
    $heroStateSummary = $u('Votre derni&egrave;re tentative doit &ecirc;tre corrig&eacute;e avant une nouvelle session.');
} elseif ($examType === 'meeting' && !$meetingMyUi && !$meetingCanBook) {
    $heroStateLabel = 'Quota atteint';
    $heroStateClass = 'app-status-pill app-status-pill--urgent';
    $heroStateSummary = $u('Aucune r&eacute;servation suppl&eacute;mentaire n&rsquo;est disponible.');
} elseif (!$canAttempt) {
    $heroStateLabel = 'Limite atteinte';
    $heroStateClass = 'app-status-pill app-status-pill--urgent';
    $heroStateSummary = $u('Le nombre maximum de tentatives a &eacute;t&eacute; utilis&eacute;.');
}
$headerCtaLabel = 'Voir le statut';
$headerCtaIcon = 'check-circle';
if ($meetingMyUi) {
    $headerCtaLabel = $u('Voir mon cr&eacute;neau');
    $headerCtaIcon = 'calendar';
} elseif ($showRetry) {
    $headerCtaLabel = 'Repasser';
    $headerCtaIcon = 'repeat';
} elseif ($examType === 'meeting') {
    $headerCtaLabel = $u('Choisir un cr&eacute;neau');
    $headerCtaIcon = 'calendar';
} elseif ($examType === 'drive') {
    $headerCtaLabel = $u('D&eacute;poser mon travail');
    $headerCtaIcon = 'file-text';
} elseif ($canAttempt) {
    $headerCtaLabel = 'Commencer';
    $headerCtaIcon = 'check-circle';
}
$studentWelcomeSummary = trim((string) html_entity_decode(strip_tags($heroStateSummary), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
$studentWelcomeTitle = $examTitle;
$studentWelcomeActionUrl = '#exam-action';
$studentWelcomeActionLabel = $headerCtaLabel;
$studentWelcomeActionIcon = $headerCtaIcon;
$actionTitle = $u('&Eacute;tat de la session');
$actionSummary = $u('Consultez le statut actuel et les tentatives enregistr&eacute;es.');
if ($examType === 'meeting' && $meetingMyUi) {
    $actionTitle = $u('R&eacute;servation confirm&eacute;e');
    $actionSummary = $u('Retrouvez votre cr&eacute;neau, ses modalit&eacute;s et le suivi de correction.');
} elseif ($actionAvailable && $examType === 'qcm') {
    $actionTitle = 'Composer le QCM';
    $actionSummary = $u('R&eacute;pondez &agrave; chaque question puis envoyez une seule fois votre copie pour cette tentative.');
} elseif ($actionAvailable && $examType === 'drive') {
    $actionTitle = $u('D&eacute;poser mon travail');
    $actionSummary = $u('Chargez votre fichier, ajoutez un commentaire si besoin puis envoyez votre d&eacute;p&ocirc;t pour correction.');
} elseif ($actionAvailable && $examType === 'meeting') {
    $actionTitle = $u('R&eacute;server mon cr&eacute;neau');
    $actionSummary = $u('S&eacute;lectionnez une disponibilit&eacute; puis confirmez votre participation.');
}
$actionPrimaryCard = ['label' => 'Tentatives', 'value' => $attemptsValue, 'meta' => $attemptsMeta];
$actionSecondaryCard = ['label' => 'Seuil', 'value' => $scoreTargetValue, 'meta' => $scoreTargetMeta];
if ($examType === 'qcm') {
    $actionPrimaryCard = [
        'label' => 'Questions',
        'value' => (string) $questionCount,
        'meta' => $questionCount > 0 ? ($variantLabels[$activeVariant] ?? 'Version A') : 'Aucune question',
    ];
    $actionSecondaryCard = ['label' => 'Seuil', 'value' => $scoreTargetValue, 'meta' => $scoreTargetMeta];
} elseif ($examType === 'drive') {
    $actionPrimaryCard = [
        'label' => 'Depot',
        'value' => 'Depot pret',
        'meta' => 'Taille max ' . (string) $driveMaxMb . ' Mo',
    ];
    $actionSecondaryCard = [
        'label' => 'Sujet',
        'value' => $driveSubjectLink !== '' ? 'Disponible' : 'A verifier',
        'meta' => $driveSubjectText !== '' ? $driveSubjectText : 'Aucun lien de sujet fourni',
    ];
} elseif ($examType === 'meeting') {
    $actionPrimaryCard = [
        'label' => 'Créneaux',
        'value' => $meetingMyUi ? '1 réservé' : (string) $slotCount,
        'meta' => $meetingMyUi ? 'Réservation enregistrée' : ($slotCount > 0 ? 'Disponibilités ouvertes' : 'Aucun créneau'),
    ];
    $actionSecondaryCard = [
        'label' => 'Statut',
        'value' => $meetingMyUi ? $meetingReservationStatusLabel : ($meetingCanBook ? 'Réservation ouverte' : 'Fermée'),
        'meta' => $meetingMyUi ? trim((string) ($meetingMyUi['start_label'] ?? '')) : ($attemptsHasMeetingId ? 'Choisissez un horaire' : 'Migration requise'),
    ];
}
$prepItems = [];
if ($examType === 'qcm') {
    $prepItems = [
        ['title' => 'Version active', 'text' => $questionCount > 0 ? (($variantLabels[$activeVariant] ?? 'Version A') . ' avec ' . (string) $questionCount . ' question(s).') : 'Le questionnaire n’est pas encore configuré.'],
        ['title' => 'Bareme', 'text' => $scoreTargetValue !== 'Libre' ? 'Seuil vise : ' . $scoreTargetValue . '.' : 'Aucun seuil renseigne pour cette session.'],
        ['title' => 'Soumission', 'text' => 'Vérifiez vos réponses avant l’envoi. Une soumission enregistre la tentative en cours.'],
    ];
} elseif ($examType === 'drive') {
    $prepItems = [
        ['title' => 'Sujet', 'text' => $driveSubjectLink !== '' ? 'Le sujet est disponible dans les ressources de la session.' : 'Confirmez le sujet attendu avant l’envoi.'],
        ['title' => 'Fichier', 'text' => 'Chargez un fichier compatible, dans la limite de ' . (string) $driveMaxMb . ' Mo.'],
        ['title' => 'Correction', 'text' => 'Ajoutez un commentaire utile si le correcteur a besoin de contexte supplementaire.'],
    ];
} else {
    $meetingModeSummary = 'Vérifiez le mode du créneau choisi.';
    if ($meetingMyUi && ($meetingMyUi['mode_label'] ?? '') !== '') {
        $meetingModeSummary = 'Mode retenu: ' . (string) $meetingMyUi['mode_label'] . '.';
    } elseif ($meetingSlotsUi !== [] && ($meetingSlotsUi[0]['mode_label'] ?? '') !== '') {
        $meetingModeSummary = 'Mode propose: ' . (string) $meetingSlotsUi[0]['mode_label'] . '.';
    }
$prepItems = [
        ['title' => 'Réservation', 'text' => $meetingMyUi ? 'Votre créneau est déjà confirmé.' : 'Choisissez un créneau libre puis validez votre présence.'],
        ['title' => 'Modalites', 'text' => $meetingModeSummary],
        ['title' => 'Organisation', 'text' => 'Annulez avant le début si besoin. Une réservation compte dans le suivi des tentatives.'],
    ];
}
$blockedMessage = 'Vous avez atteint le nombre maximum de tentatives.';
$blockedClass = 'app-feedback app-feedback--info';
if ($progressLocked) {
    $blockedMessage = 'Vous devez terminer 100% du cours avant de passer cet examen.';
    if ($progressTotal > 0) {
        $blockedMessage .= ' Progression actuelle: ' . (string) $progressDone . ' / ' . (string) $progressTotal . ' (' . (string) $progressPercent . '%).';
    }
$blockedClass = 'app-feedback app-feedback--warning';
} elseif ($availabilityBlocked) {
    $blockedMessage = $availabilityBlockedReason !== '' ? $availabilityBlockedReason : 'Examen indisponible pour le moment.';
    $blockedClass = 'app-feedback app-feedback--warning';
} elseif ($certificateCode !== '' || $hasPassedAttempt) {
    $blockedMessage = 'Examen d&eacute;j&agrave; r&eacute;ussi. Aucune nouvelle tentative n&rsquo;est n&eacute;cessaire.';
    $blockedClass = 'app-feedback app-feedback--success';
} elseif ($examType === 'qcm' && (($lastAttemptUi['status'] ?? '') === 'submitted')) {
    $blockedMessage = 'Votre derni&egrave;re tentative est en cours de correction. Une nouvelle tentative sera rouverte ensuite si le quota le permet.';
    $blockedClass = 'app-feedback app-feedback--warning';
} elseif ($examType === 'meeting' && !$meetingMyUi && !$meetingCanBook) {
    $blockedMessage = 'Vous ne pouvez plus réserver de créneau supplémentaire.';
    $blockedClass = 'app-feedback app-feedback--warning';
}
$onsiteDisclaimerRequired = false;
if (($meetingMyUi['mode'] ?? '') === 'onsite') {
    $onsiteDisclaimerRequired = true;
}
if (!$onsiteDisclaimerRequired) {
    foreach ($meetingSlotsUi as $slotUi) {
        if (($slotUi['mode'] ?? '') === 'onsite') {
            $onsiteDisclaimerRequired = true;
            break;
        }
    }
}
?>
<!doctype html>
<html lang="fr" class="light" data-default-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="app-global-assets" content="off">
    <title>Examen - <?php echo e($courseTitle); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo e(site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()))); ?>"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/vendor/fontawesome/all.min.css?v=' . (@filemtime(__DIR__ . '/../../../assets/vendor/fontawesome/all.min.css') ?: time()))); ?>">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/student_dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/student_dashboard.css') ?: time()))); ?>">
    <script src="<?php echo e(site_url('assets/js/web_theme.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/web_theme.js') ?: time()))); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/sidebar_toggle.js')); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/student_dashboard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/student_dashboard.js') ?: time()))); ?>" defer></script>
    
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body data-view="student/exam" class="app-view app-role-student min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100 font-body">
    <div class="min-h-screen flex flex-col md:flex-row">
        <?php $studentNavActive = 'my_courses'; ?>
        <?php require __DIR__ . '/_sidebar.php'; ?>
        <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

        <div class="flex-1 min-w-0">
            <?php $studentTopTitle = 'Examen'; ?>
            <?php $studentTopSubtitle = 'Espace etudiant'; ?>
            <?php require __DIR__ . '/_topbar.php'; ?>

            <main class="student-content-main max-w-7xl mx-auto px-4 py-6 md:px-6 xl:px-8 space-y-6 pb-10">
                <?php require __DIR__ . '/_welcome_hero.php'; ?>

                <section class="app-page-hero student-exam-hero">
                    <div class="student-exam-orb student-exam-orb--one" aria-hidden="true"></div>
                    <div class="student-exam-orb student-exam-orb--two" aria-hidden="true"></div>

                    <div class="app-page-hero__header relative z-[1] gap-6 xl:grid xl:grid-cols-[minmax(0,1.3fr)_minmax(320px,0.72fr)] xl:items-start">
                        <div class="app-page-hero__intro">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="<?php echo e($heroStateClass); ?>"><?php echo e($heroStateLabel); ?></span>
                                <span class="app-status-pill app-status-pill--done"><?php echo e($typeLabel); ?></span>
                                <?php if ($isAdmin || $isCourseOwner): ?>
                                    <span class="app-status-pill app-status-pill--teacher">Accès avancé</span>
                                <?php endif; ?>
                            </div>

                            <div class="mt-5 flex items-start gap-4">
                                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl border border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-200">
                                    <?php if ($siteLogoUrl !== ''): ?>
                                        <img src="<?php echo e($siteLogoUrl); ?>" alt="Logo" class="h-full w-full rounded-2xl object-contain">
                                    <?php else: ?>
                                        <i class="<?php echo e(dashboard_icon_fa_class('award')); ?>" aria-hidden="true"></i>
                                    <?php endif; ?>
                                </div>

                                <div class="min-w-0">
                                    <p class="app-page-hero__eyebrow"><?php echo e($courseTitle); ?></p>
                                    <h1 class="app-page-hero__title"><?php echo e($examTitle); ?></h1>
                                    <p class="app-page-hero__summary">
                                        <?php echo e($examDescription !== '' ? $examDescription : 'Cette page centralise la composition, le dépôt ou la réservation, ainsi que l’historique de vos tentatives.'); ?>
                                    </p>
                                </div>
                            </div>

                            <div class="mt-5 flex flex-wrap gap-2">
                                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white/85 px-3 py-1.5 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200">
                                    <span class="app-icon text-blue-600 dark:text-blue-300" aria-hidden="true"><i class="<?php echo e(dashboard_icon_fa_class('clock')); ?>"></i></span>
                                    <?php echo e($durationLabel); ?>
                                </span>
                                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white/85 px-3 py-1.5 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200">
                                    <span class="app-icon text-blue-600 dark:text-blue-300" aria-hidden="true"><i class="<?php echo e(dashboard_icon_fa_class('layers')); ?>"></i></span>
                                    Tentatives <?php echo e($attemptsValue); ?>
                                </span>
                                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white/85 px-3 py-1.5 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200">
                                    <span class="app-icon text-blue-600 dark:text-blue-300" aria-hidden="true"><i class="<?php echo e(dashboard_icon_fa_class('award')); ?>"></i></span>
                                    Seuil <?php echo e($scoreTargetValue); ?>
                                </span>
                                <?php if ($examType === 'qcm' && $questionCount > 0): ?>
                                    <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white/85 px-3 py-1.5 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200">
                                        <span class="app-icon text-blue-600 dark:text-blue-300" aria-hidden="true"><i class="<?php echo e(dashboard_icon_fa_class('clipboard-list')); ?>"></i></span>
                                        <?php echo e($variantLabels[$activeVariant] ?? 'Version A'); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($progressTotal > 0): ?>
                                    <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white/85 px-3 py-1.5 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200">
                                        <span class="app-icon text-blue-600 dark:text-blue-300" aria-hidden="true"><i class="<?php echo e(dashboard_icon_fa_class('trending-up')); ?>"></i></span>
                                        Progression <?php echo e((string) $progressPercent); ?>%
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="mt-6 flex flex-wrap gap-3">
                                <a href="#exam-action" class="app-btn-primary inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition">
                                    <span class="app-icon" aria-hidden="true"><i class="<?php echo e(dashboard_icon_fa_class((string) $headerCtaIcon)); ?>"></i></span>
                                    <span><?php echo e($headerCtaLabel); ?></span>
                                </a>
                                <?php if ($certificateViewUrl !== ''): ?>
                                    <a href="<?php echo e($certificateViewUrl); ?>" target="_blank" rel="noopener" class="app-btn-soft app-btn-soft--success inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition">
                                        <span class="app-icon" aria-hidden="true"><i class="<?php echo e(dashboard_icon_fa_class('award')); ?>"></i></span>
                                        <span>Voir le certificat</span>
                                    </a>
                                    <a href="<?php echo e($certificatePdfUrl); ?>" class="app-btn-soft inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition">
                                        <span class="app-icon" aria-hidden="true"><i class="<?php echo e(dashboard_icon_fa_class('download')); ?>"></i></span>
                                        <span>Télécharger le PDF</span>
                                    </a>
                                <?php endif; ?>
                                <a href="<?php echo e($courseUrl); ?>" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white/85 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-400 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100 dark:hover:border-slate-500">
                                    <span class="app-icon" aria-hidden="true"><i class="<?php echo e(dashboard_icon_fa_class('book-open')); ?>"></i></span>
                                    <span>Retour au cours</span>
                                </a>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-1">
                            <article class="app-page-subcard border p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Statut</p>
                                        <h2 class="mt-2 text-lg font-semibold text-slate-900 dark:text-slate-100"><?php echo e($heroStateLabel); ?></h2>
                                    </div>
                                    <span class="<?php echo e($heroStateClass); ?>"><?php echo e($heroStateLabel); ?></span>
                                </div>
                                <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300"><?php echo e($heroStateSummary); ?></p>
                                <div class="mt-4 flex flex-wrap gap-2 text-[11px]">
                                    <span class="app-status-pill app-status-pill--done"><?php echo e($modeValue); ?></span>
                                    <span class="app-status-pill app-status-pill--info"><?php echo e((string) $remainingAttempts); ?> restante(s)</span>
                                    <?php if ($lastAttemptUi): ?>
                                        <span class="app-status-pill app-status-pill--sky"><?php echo e($lastAttemptStatusLabel); ?></span>
                                    <?php endif; ?>
                                </div>
                            </article>

                            <article class="app-page-subcard border p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Parcours</p>
                                        <h2 class="mt-2 text-lg font-semibold text-slate-900 dark:text-slate-100"><?php echo e($progressValue); ?></h2>
                                    </div>
                                    <span class="app-status-pill app-status-pill--done"><?php echo e($durationLabel); ?></span>
                                </div>

                                <?php if ($progressTotal > 0): ?>
                                    <div class="dash-progress mt-4">
                                        <div class="dash-progress__fill" style="width: <?php echo e((string) $progressPercent); ?>%;"></div>
                                    </div>
                                    <p class="mt-3 text-sm text-slate-600 dark:text-slate-300"><?php echo e($progressMeta); ?></p>
                                <?php else: ?>
                                    <p class="mt-4 text-sm text-slate-600 dark:text-slate-300">Le suivi d&eacute;taill&eacute; du cours n&rsquo;est pas requis pour cette session.</p>
                                <?php endif; ?>

                                <div class="mt-4 grid gap-2 sm:grid-cols-2 xl:grid-cols-2">
                                    <div class="rounded-2xl border border-slate-200 bg-white/85 px-4 py-3 dark:border-slate-800 dark:bg-slate-950/70">
                                        <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Format</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100"><?php echo e($modeValue); ?></p>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200 bg-white/85 px-4 py-3 dark:border-slate-800 dark:bg-slate-950/70">
                                        <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Contenu</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100"><?php echo e($modeMeta); ?></p>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </div>
                </section>

                <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <article class="app-kpi-card app-kpi-card--blue rounded-2xl border p-4">
                        <p class="app-kpi-card__head">
                            <span class="app-kpi-card__icon" aria-hidden="true"><i class="<?php echo e(dashboard_icon_fa_class('layers')); ?>"></i></span>
                            <span>Tentatives</span>
                        </p>
                        <p class="app-kpi-card__value text-2xl"><?php echo e($attemptsValue); ?></p>
                        <p class="app-kpi-card__meta"><?php echo e($attemptsMeta); ?></p>
                    </article>
                    <article class="app-kpi-card app-kpi-card--indigo rounded-2xl border p-4">
                        <p class="app-kpi-card__head">
                            <span class="app-kpi-card__icon" aria-hidden="true"><i class="<?php echo e(dashboard_icon_fa_class('check-circle')); ?>"></i></span>
                            <span>Dernière copie</span>
                        </p>
                        <p class="app-kpi-card__value text-2xl"><?php echo e($lastAttemptScoreLabel); ?></p>
                        <p class="app-kpi-card__meta"><?php echo e($lastAttemptStatusLabel); ?></p>
                    </article>
                    <article class="app-kpi-card app-kpi-card--emerald rounded-2xl border p-4">
                        <p class="app-kpi-card__head">
                            <span class="app-kpi-card__icon" aria-hidden="true"><i class="<?php echo e(dashboard_icon_fa_class('award')); ?>"></i></span>
                            <span>Seuil</span>
                        </p>
                        <p class="app-kpi-card__value text-2xl"><?php echo e($scoreTargetValue); ?></p>
                        <p class="app-kpi-card__meta"><?php echo e($scoreTargetMeta); ?></p>
                    </article>
                    <article class="app-kpi-card app-kpi-card--sky rounded-2xl border p-4">
                        <p class="app-kpi-card__head">
                            <span class="app-kpi-card__icon" aria-hidden="true"><i class="<?php echo e(dashboard_icon_fa_class('book-open')); ?>"></i></span>
                            <span>Format</span>
                        </p>
                        <p class="app-kpi-card__value text-2xl"><?php echo e($modeValue); ?></p>
                        <p class="app-kpi-card__meta"><?php echo e($modeMeta); ?></p>
                    </article>
                </section>

                <?php if ($success !== ''): ?>
                    <section class="app-feedback app-feedback--success text-sm" role="status" aria-live="polite">
                        <p><?php echo e($success); ?></p>
                    </section>
                <?php endif; ?>

                <?php if ($errors !== []): ?>
                    <section class="app-feedback app-feedback--error text-sm" role="alert" aria-live="assertive">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo e((string) $error); ?></p>
                        <?php endforeach; ?>
                    </section>
                <?php endif; ?>
                <div class="grid gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(320px,0.82fr)]">
                    <div class="space-y-6">
                        <section id="exam-action" class="<?php echo e($surfaceClass); ?> p-5 md:p-6">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0">
                                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Action</p>
                                    <h2 class="mt-2 text-2xl font-display font-semibold text-slate-900 dark:text-slate-100"><?php echo e($actionTitle); ?></h2>
                                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300"><?php echo e($actionSummary); ?></p>
                                </div>

                                <div class="grid gap-3 sm:grid-cols-2 lg:w-full lg:max-w-xl">
                                    <article class="<?php echo e($softCardClass); ?> px-4 py-3">
                                        <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400"><?php echo e($actionPrimaryCard['label']); ?></p>
                                        <p class="mt-2 text-lg font-semibold text-slate-900 dark:text-slate-100"><?php echo e($actionPrimaryCard['value']); ?></p>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400"><?php echo e($actionPrimaryCard['meta']); ?></p>
                                    </article>
                                    <article class="<?php echo e($softCardClass); ?> px-4 py-3">
                                        <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400"><?php echo e($actionSecondaryCard['label']); ?></p>
                                        <p class="mt-2 text-lg font-semibold text-slate-900 dark:text-slate-100"><?php echo e($actionSecondaryCard['value']); ?></p>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400"><?php echo e($actionSecondaryCard['meta']); ?></p>
                                    </article>
                                </div>
                            </div>

                            <?php if (!$actionAvailable): ?>
                                <div class="mt-6 space-y-4">
                                    <div class="<?php echo e($blockedClass); ?> text-sm">
                                        <p><?php echo e($blockedMessage); ?></p>
                                    </div>

                                    <div class="grid gap-3 md:grid-cols-2">
                                        <article class="<?php echo e($softCardClass); ?> p-4">
                                            <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Progression</p>
                                            <p class="mt-2 text-lg font-semibold text-slate-900 dark:text-slate-100"><?php echo e($progressValue); ?></p>
                                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300"><?php echo e($progressMeta); ?></p>
                                        </article>
                                        <article class="<?php echo e($softCardClass); ?> p-4">
                                            <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Tentatives</p>
                                            <p class="mt-2 text-lg font-semibold text-slate-900 dark:text-slate-100"><?php echo e($attemptsValue); ?></p>
                                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300"><?php echo e($attemptsMeta); ?></p>
                                        </article>
                                    </div>
                                </div>
                            <?php elseif ($examType === 'qcm'): ?>
                                <?php if ($questionsUi === []): ?>
                                    <div class="mt-6 app-feedback app-feedback--warning text-sm">
                                        <p>Aucune question n’a encore été configurée pour cet examen.</p>
                                    </div>
                                <?php else: ?>
                                    <form method="post" class="mt-6 space-y-5">
                                        <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">
                                        <input type="hidden" name="action" value="submit_qcm">

                                        <?php foreach ($questionsUi as $questionUi): ?>
                                            <article class="<?php echo e($softCardClass); ?> p-4 md:p-5 space-y-4">
                                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                    <div class="min-w-0">
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-blue-200 bg-blue-50 text-sm font-semibold text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-200">
                                                                <?php echo e((string) $questionUi['index']); ?>
                                                            </span>
                                                            <span class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                                                <?php echo !empty($questionUi['is_text']) ? 'Réponse libre' : 'Choix'; ?>
                                                            </span>
                                                        </div>
                                                        <p class="mt-3 text-base font-semibold leading-7 text-slate-900 dark:text-slate-100"><?php echo e((string) $questionUi['text']); ?></p>
                                                    </div>

                                                    <span class="inline-flex rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-950/70 dark:text-slate-300">
                                                        <?php echo e((string) $questionUi['points']); ?> pt
                                                    </span>
                                                </div>

                                                <?php if (!empty($questionUi['is_text'])): ?>
                                                    <textarea
                                                        name="<?php echo e((string) $questionUi['text_input_name']); ?>"
                                                        rows="5"
                                                        required
                                                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-400/20 dark:border-slate-700 dark:bg-slate-950/70 dark:text-slate-100"
                                                        placeholder="Saisissez votre réponse ici..."
                                                    ></textarea>
                                                <?php else: ?>
                                                    <div class="space-y-3">
                                                        <?php if (empty($questionUi['options'])): ?>
                                                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-950/70 dark:text-slate-300">
                                                                Aucune option n’est configurée pour cette question.
                                                            </div>
                                                        <?php else: ?>
                                                            <?php foreach ($questionUi['options'] as $optionIndex => $optUi): ?>
                                                                <label class="exam-answer-option flex items-start gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm transition hover:border-slate-400 dark:border-slate-700 dark:bg-slate-950/70 dark:hover:border-slate-500">
                                                                    <input
                                                                        class="mt-1 shrink-0"
                                                                        <?php echo !empty($optUi['required']) ? 'required' : ''; ?>
                                                                        type="<?php echo e((string) $optUi['input_type']); ?>"
                                                                        name="<?php echo e((string) $optUi['input_name']); ?>"
                                                                        value="<?php echo e((string) $optUi['id']); ?>"
                                                                    >
                                                                    <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-slate-300 text-[11px] font-semibold text-slate-500 dark:border-slate-700 dark:text-slate-300">
                                                                        <?php echo e(chr(65 + $optionIndex)); ?>
                                                                    </span>
                                                                    <span class="min-w-0 text-slate-800 dark:text-slate-200"><?php echo e((string) $optUi['text']); ?></span>
                                                                </label>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </article>
                                        <?php endforeach; ?>

                                        <div class="rounded-3xl border border-blue-200 bg-blue-50/80 p-4 dark:border-blue-500/20 dark:bg-blue-500/10">
                                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                                <div>
                                                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Prêt à envoyer votre copie </p>
                                                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                                        <?php echo e($variantLabels[$activeVariant] ?? 'Version A'); ?>,
                                                        <?php echo e((string) $questionCount); ?> question(s),
                                                        seuil <?php echo e($scoreTargetValue); ?>.
                                                    </p>
                                                </div>
                                                <button type="submit" class="app-btn-primary inline-flex items-center gap-2 rounded-xl px-5 py-3 text-sm font-semibold transition">
                                                    <span class="app-icon" aria-hidden="true"><i class="<?php echo e(dashboard_icon_fa_class('check-circle')); ?>"></i></span>
                                                    <span>Envoyer l’examen</span>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            <?php elseif ($examType === 'drive'): ?>
                                <div class="mt-6 space-y-4">
                                    <div class="<?php echo e($softCardClass); ?> p-4 text-sm text-slate-600 dark:text-slate-300">
                                        Déposez votre fichier de travail depuis cette page. La remise sera transmise pour correction à l’espace prévu sur la plateforme.
                                    </div>

                                    <?php if ($driveSubjectLink !== ''): ?>
                                        <div class="<?php echo e($softCardClass); ?> p-4">
                                            <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Sujet</p>
                                            <a href="<?php echo e($driveSubjectLink); ?>" target="_blank" rel="noopener" class="mt-2 inline-block break-all text-sm font-semibold app-link-primary underline">
                                                <?php echo e($driveSubjectText !== '' ? $driveSubjectText : $driveSubjectLink); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!$driveReady): ?>
                                        <div class="app-feedback app-feedback--warning text-sm">
                                            <p>Le dépôt Drive est indisponible pour le moment. Merci de contacter le support.</p>
                                        </div>
                                    <?php else: ?>
                                        <form method="post" enctype="multipart/form-data" class="space-y-4">
                                            <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">
                                            <input type="hidden" name="action" value="submit_drive">

                                            <div class="<?php echo e($softCardClass); ?> p-4">
                                                <label class="block text-sm font-semibold text-slate-800 dark:text-slate-100">Fichier *</label>
                                                <input
                                                    type="file"
                                                    name="drive_file"
                                                    required
                                                    class="mt-3 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-400/20 dark:border-slate-700 dark:bg-slate-950/70 dark:text-slate-100"
                                                >
                                                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Taille maximale autorisée : <?php echo e((string) $driveMaxMb); ?> Mo. <?php echo e($driveUploadNote !== '' ? $driveUploadNote : "Google Drive n'est pas configure."); ?></p>
                                            </div>

                                            <div class="<?php echo e($softCardClass); ?> p-4">
                                                <label class="block text-sm font-semibold text-slate-800 dark:text-slate-100">Commentaire</label>
                                                <textarea
                                                    name="comment"
                                                    rows="5"
                                                    class="mt-3 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-400/20 dark:border-slate-700 dark:bg-slate-950/70 dark:text-slate-100"
                                                    placeholder="Ajoutez des précisions utiles pour le correcteur."
                                                ></textarea>
                                            </div>

                                            <div class="rounded-3xl border border-blue-200 bg-blue-50/80 p-4 dark:border-blue-500/20 dark:bg-blue-500/10">
                                                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                                    <div>
                                                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Dépôt prêt à être envoyé</p>
                                                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Vérifiez le fichier chargé avant validation définitive.</p>
                                                    </div>
                                                    <button type="submit" class="app-btn-primary inline-flex items-center gap-2 rounded-xl px-5 py-3 text-sm font-semibold transition">
                                                        <span class="app-icon" aria-hidden="true"><i class="<?php echo e(dashboard_icon_fa_class('file-text')); ?>"></i></span>
                                                        <span>Envoyer le dépôt</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($examType === 'meeting'): ?>
                                <div class="mt-6 space-y-4">
                                    <div class="<?php echo e($softCardClass); ?> p-4 text-sm text-slate-600 dark:text-slate-300">
                                        Choisissez un créneau puis confirmez votre participation. Les modalités s’affichent selon le mode retenu.
                                    </div>

                                    <?php if (!$attemptsHasMeetingId): ?>
                                        <div class="app-feedback app-feedback--warning text-sm">
                                            <p>Une mise à jour de la base est requise avant d’activer la réservation de créneaux.</p>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($meetingMyUi): ?>
                                        <article class="<?php echo e($softCardClass); ?> p-5 space-y-4">
                                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                <div>
                                                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Réservation</p>
                                                    <h3 class="mt-2 text-xl font-semibold text-slate-900 dark:text-slate-100">Votre créneau</h3>
                                                </div>
                                                <span class="app-status-pill app-status-pill--sky"><?php echo e($meetingReservationStatusLabel); ?></span>
                                            </div>

                                            <div class="grid gap-3 md:grid-cols-2">
                                                <div class="<?php echo e($panelCardClass); ?> px-4 py-3">
                                                    <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Date</p>
                                                    <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100"><?php echo e((string) ($meetingMyUi['start_label'] ?? '')); ?></p>
                                                    <?php if (($meetingMyUi['end_label'] ?? '') !== '') : ?>
                                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Fin: <?php echo e((string) $meetingMyUi['end_label']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="<?php echo e($panelCardClass); ?> px-4 py-3">
                                                    <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Mode</p>
                                                    <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100"><?php echo e((string) ($meetingMyUi['mode_label'] ?? '')); ?></p>
                                                    <?php if ((int) ($meetingMyUi['duration_min'] ?? 0) > 0) : ?>
                                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400"><?php echo e(format_duration_minutes_hm((int) $meetingMyUi['duration_min'])); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <?php if (($meetingMyUi['mode'] ?? '') === 'online' && ($meetingMyUi['link'] ?? '') !== '') : ?>
                                                <div class="<?php echo e($panelCardClass); ?> px-4 py-3">
                                                    <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Lien</p>
                                                    <a href="<?php echo e((string) $meetingMyUi['link']); ?>" target="_blank" rel="noopener" class="mt-1 inline-block break-all text-sm font-semibold app-link-primary underline">
                                                        <?php echo e((string) $meetingMyUi['link']); ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (($meetingMyUi['mode'] ?? '') === 'onsite' && ($meetingMyUi['place'] ?? '') !== '') : ?>
                                                <div class="<?php echo e($panelCardClass); ?> px-4 py-3">
                                                    <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Lieu</p>
                                                    <p class="mt-1 text-sm text-slate-700 dark:text-slate-200"><?php echo e((string) $meetingMyUi['place']); ?></p>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (($meetingMyUi['attempt_status'] ?? '') === 'graded' && ($meetingMyUi['attempt_score'] ?? null) !== null) : ?>
                                                <div class="<?php echo e($panelCardClass); ?> px-4 py-3">
                                                    <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Résultat</p>
                                                    <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">Note: <?php echo e((string) ((int) $meetingMyUi['attempt_score'])); ?></p>
                                                    <p class="mt-2 text-xs <?php echo !empty($meetingMyUi['attempt_passed']) ? 'app-inline-note' : 'app-inline-note app-inline-note--danger'; ?>">
                                                        <?php echo e(!empty($meetingMyUi['attempt_passed']) ? 'Session validée' : 'Session non validée'); ?>
                                                    </p>
                                                </div>
                                            <?php elseif (($meetingMyUi['attempt_status'] ?? '') === 'submitted') : ?>
                                                <div class="app-feedback app-feedback--warning text-sm">
                                                    <p>Votre rendez-vous est termin&eacute;. La correction est en attente.</p>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($meetingMyUi['can_cancel'])): ?>
                                                <form method="post" class="flex justify-end" onsubmit="return confirm('Annuler ce créneau ');">
                                                    <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">
                                                    <input type="hidden" name="action" value="cancel_meeting_booking">
                                                    <input type="hidden" name="meeting_id" value="<?php echo e((string) ($meetingMyUi['id'] ?? '0')); ?>">
                                                    <button type="submit" class="app-btn-soft app-btn-soft--danger inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition">
                                                        <span class="app-icon" aria-hidden="true"><i class="<?php echo e(dashboard_icon_fa_class('x')); ?>"></i></span>
                                                        <span>Annuler mon créneau</span>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </article>
                                    <?php else: ?>
                                        <?php if (!$meetingCanBook): ?>
                                            <div class="app-feedback app-feedback--warning text-sm">
                                                <p>Vous avez atteint le nombre maximum de tentatives.</p>
                                            </div>
                                        <?php elseif ($meetingSlotsUi === []): ?>
                                            <div class="app-feedback app-feedback--info text-sm">
                                                <p>Aucun cr&eacute;neau n&rsquo;est disponible pour le moment.</p>
                                            </div>
                                        <?php else: ?>
                                            <form method="post" class="space-y-4">
                                                <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">
                                                <input type="hidden" name="action" value="book_meeting_slot">

                                                <div class="space-y-3">
                                                    <?php foreach ($meetingSlotsUi as $slotUi): ?>
                                                        <label class="exam-slot-option block rounded-2xl border border-slate-200 bg-white px-4 py-4 transition hover:border-slate-400 dark:border-slate-700 dark:bg-slate-950/70 dark:hover:border-slate-500">
                                                            <div class="flex items-start gap-3">
                                                                <input
                                                                    class="mt-1 shrink-0"
                                                                    <?php echo !empty($slotUi['required']) ? 'required' : ''; ?>
                                                                    type="radio"
                                                                    name="meeting_id"
                                                                    value="<?php echo e((string) ($slotUi['id'] ?? 0)); ?>"
                                                                    data-meeting-mode="<?php echo e((string) ($slotUi['mode'] ?? 'online')); ?>"
                                                                >
                                                                <div class="min-w-0 space-y-1">
                                                                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                                                                        <?php echo e((string) ($slotUi['start_label'] ?? '')); ?>
                                                                        <?php if (($slotUi['end_label'] ?? '') !== '') : ?>
                                                                            &middot; fin <?php echo e((string) $slotUi['end_label']); ?>
                                                                        <?php endif; ?>
                                                                        <?php if ((int) ($slotUi['duration_min'] ?? 0) > 0) : ?>
                                                                            &middot; <?php echo e(format_duration_minutes_hm((int) $slotUi['duration_min'])); ?>
                                                                        <?php endif; ?>
                                                                    </p>
                                                                    <p class="text-xs text-slate-500 dark:text-slate-400">Mode: <?php echo e((string) ($slotUi['mode_label'] ?? '')); ?></p>
                                                                    <?php if (($slotUi['mode'] ?? '') === 'online' && ($slotUi['link'] ?? '') !== '') : ?>
                                                                        <p class="break-all text-xs text-slate-600 dark:text-slate-300">Lien: <?php echo e((string) $slotUi['link']); ?></p>
                                                                    <?php endif; ?>
                                                                    <?php if (($slotUi['mode'] ?? '') === 'onsite' && ($slotUi['place'] ?? '') !== '') : ?>
                                                                        <p class="text-xs text-slate-600 dark:text-slate-300">Lieu: <?php echo e((string) $slotUi['place']); ?></p>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </label>
                                                    <?php endforeach; ?>
                                                </div>

                                                <div class="<?php echo e($softCardClass); ?> p-4">
                                                    <label class="block text-sm font-semibold text-slate-800 dark:text-slate-100">Message</label>
                                                    <textarea
                                                        name="comment"
                                                        rows="4"
                                                        class="mt-3 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-400/20 dark:border-slate-700 dark:bg-slate-950/70 dark:text-slate-100"
                                                        placeholder="Ajoutez un commentaire utile si besoin."
                                                    ></textarea>
                                                </div>

                                                <div class="rounded-3xl border border-blue-200 bg-blue-50/80 p-4 dark:border-blue-500/20 dark:bg-blue-500/10">
                                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                                        <div>
                                                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Réservation prête à être envoyée</p>
                                                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Vérifiez le mode, l’horaire et le lieu avant validation.</p>
                                                        </div>
                                                        <button type="submit" class="app-btn-primary inline-flex items-center gap-2 rounded-xl px-5 py-3 text-sm font-semibold transition">
                                                            <span class="app-icon" aria-hidden="true"><i class="<?php echo e(dashboard_icon_fa_class('calendar')); ?>"></i></span>
                                                            <span>Réserver ce créneau</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="mt-6 app-feedback app-feedback--info text-sm">
                                    <p>Type d examen non pris en charge sur cette interface.</p>
                                </div>
                            <?php endif; ?>
                        </section>
                        <section class="<?php echo e($surfaceClass); ?> p-5 md:p-6">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Historique</p>
                                    <h2 class="mt-2 text-2xl font-display font-semibold text-slate-900 dark:text-slate-100">Tentatives précédentes</h2>
                                </div>
                                <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300">
                                    <?php echo e((string) count($attemptHistoryUi)); ?> tentative(s)
                                </span>
                            </div>

                            <?php if ($attemptHistoryUi === []): ?>
                                <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50/80 p-4 text-sm text-slate-600 dark:border-slate-800 dark:bg-slate-900/55 dark:text-slate-300">
                                    Aucune tentative enregistrée pour le moment.
                                </div>
                            <?php else: ?>
                                <div class="mt-6 space-y-4">
                                    <?php foreach ($attemptHistoryUi as $attemptIndex => $attemptUi): ?>
                                        <?php $attemptTone = $statusTone((string) ($attemptUi['status'] ?? '')); ?>
                                        <details class="rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900/55" <?php echo $attemptIndex === 0 ? 'open' : ''; ?>>
                                            <summary class="cursor-pointer px-4 py-4 md:px-5">
                                                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                                    <div class="min-w-0">
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">Tentative <?php echo e((string) ($attemptUi['index'] ?? '')); ?></h3>
                                                            <span class="<?php echo e($attemptTone['badge']); ?>"><?php echo e((string) ($attemptUi['status_label'] ?? '')); ?></span>
                                                            <?php if ($examType === 'qcm' && ($attemptUi['variant_label'] ?? '') !== '') : ?>
                                                                <span class="app-status-pill app-status-pill--done"><?php echo e((string) $attemptUi['variant_label']); ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php if (($attemptUi['when_label'] ?? '') !== '') : ?>
                                                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400"><?php echo e((string) $attemptUi['when_label']); ?></p>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div class="text-left lg:text-right">
                                                        <?php if (($attemptUi['score'] ?? null) !== null) : ?>
                                                            <p class="text-xl font-semibold text-slate-900 dark:text-slate-100">
                                                                <?php echo e((string) ((int) $attemptUi['score'])); ?><?php if ($examType === 'qcm' && (int) ($attemptUi['max_score'] ?? 0) > 0) : ?>/<?php echo e((string) ((int) $attemptUi['max_score'])); ?><?php endif; ?>
                                                            </p>
                                                            <p class="text-xs text-slate-500 dark:text-slate-400">Score final</p>
                                                        <?php elseif (($attemptUi['status'] ?? '') === 'submitted') : ?>
                                                            <p class="text-sm font-semibold <?php echo e($attemptTone['text']); ?>">En attente</p>
                                                            <p class="text-xs text-slate-500 dark:text-slate-400">Correction en cours</p>
                                                        <?php else: ?>
                                                            <p class="text-sm text-slate-500 dark:text-slate-400">Consulter les détails</p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </summary>

                                            <div class="border-t border-slate-200 px-4 py-4 dark:border-slate-800 md:px-5">
                                                <?php if ($examType === 'qcm'): ?>
                                                    <div class="grid gap-3 sm:grid-cols-2">
                                                        <?php if (($attemptUi['auto_score'] ?? null) !== null) : ?>
                                                            <div class="<?php echo e($softCardClass); ?> px-4 py-3 text-sm">
                                                                <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Score auto</p>
                                                                <p class="mt-1 font-semibold text-slate-900 dark:text-slate-100"><?php echo e((string) ((int) $attemptUi['auto_score'])); ?></p>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if (($attemptUi['manual_score'] ?? null) !== null) : ?>
                                                            <div class="<?php echo e($softCardClass); ?> px-4 py-3 text-sm">
                                                                <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Score manuel</p>
                                                                <p class="mt-1 font-semibold text-slate-900 dark:text-slate-100"><?php echo e((string) ((int) $attemptUi['manual_score'])); ?></p>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>

                                                    <?php if (!empty($attemptUi['qcm_questions'])): ?>
                                                        <div class="mt-4 space-y-3">
                                                            <?php foreach ($attemptUi['qcm_questions'] as $qUi): ?>
                                                                <article class="<?php echo e($softCardClass); ?> px-4 py-3">
                                                                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                                                                        <?php echo e((string) ($qUi['index'] ?? '')); ?>. <?php echo e((string) ($qUi['text'] ?? '')); ?>
                                                                    </p>
                                                                    <p class="mt-2 text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                                                        <?php echo !empty($qUi['is_text']) ? 'Votre réponse' : 'Votre sélection'; ?>
                                                                    </p>
                                                                    <p class="mt-1 whitespace-pre-wrap text-sm text-slate-700 dark:text-slate-200"><?php echo e((string) ($qUi['value'] ?? '')); ?></p>
                                                                </article>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php elseif ($examType === 'drive'): ?>
                                                    <div class="space-y-3">
                                                        <?php if (!empty($attemptUi['drive_file']['link'])): ?>
                                                            <div class="<?php echo e($softCardClass); ?> px-4 py-3">
                                                                <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Fichier</p>
                                                                <a href="<?php echo e((string) $attemptUi['drive_file']['link']); ?>" target="_blank" rel="noopener" class="mt-1 inline-block break-all text-sm font-semibold app-link-primary underline">
                                                                    <?php echo e((string) (($attemptUi['drive_file']['name'] ?? '') !== '' ? $attemptUi['drive_file']['name'] : $attemptUi['drive_file']['link'])); ?>
                                                                </a>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="<?php echo e($softCardClass); ?> px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                                                                Aucun fichier conservé pour cette tentative.
                                                            </div>
                                                        <?php endif; ?>

                                                        <?php if (($attemptUi['drive_file']['comment'] ?? '') !== '') : ?>
                                                            <div class="<?php echo e($softCardClass); ?> px-4 py-3">
                                                                <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Commentaire</p>
                                                                <p class="mt-1 whitespace-pre-wrap text-sm text-slate-700 dark:text-slate-200"><?php echo e((string) $attemptUi['drive_file']['comment']); ?></p>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php elseif ($examType === 'meeting'): ?>
                                                    <div class="space-y-3">
                                                        <div class="<?php echo e($softCardClass); ?> px-4 py-3 text-sm text-slate-700 dark:text-slate-200">
                                                            <p>
                                                                <span class="text-slate-500 dark:text-slate-400">Créneau :</span>
                                                                <?php if (($attemptUi['meeting_info']['start_label'] ?? '') !== '') : ?>
                                                                    <?php echo e((string) $attemptUi['meeting_info']['start_label']); ?>
                                                                    <?php if (($attemptUi['meeting_info']['end_label'] ?? '') !== '') : ?>
                                                                        &middot; fin <?php echo e((string) $attemptUi['meeting_info']['end_label']); ?>
                                                                    <?php endif; ?>
                                                                <?php else: ?>
                                                                    Non associé
                                                                <?php endif; ?>
                                                            </p>
                                                            <?php if (($attemptUi['meeting_info']['status_label'] ?? '') !== '') : ?>
                                                                <p class="mt-2"><span class="text-slate-500 dark:text-slate-400">Statut:</span> <?php echo e((string) $attemptUi['meeting_info']['status_label']); ?></p>
                                                            <?php endif; ?>
                                                            <p class="mt-2"><span class="text-slate-500 dark:text-slate-400">Mode:</span> <?php echo e((string) ($attemptUi['meeting_info']['mode_label'] ?? '')); ?></p>
                                                            <?php if (($attemptUi['meeting_info']['mode'] ?? '') === 'online' && ($attemptUi['meeting_info']['link'] ?? '') !== '') : ?>
                                                                <p class="mt-2 break-all"><span class="text-slate-500 dark:text-slate-400">Lien:</span> <a href="<?php echo e((string) $attemptUi['meeting_info']['link']); ?>" target="_blank" rel="noopener" class="font-semibold app-link-primary underline"><?php echo e((string) $attemptUi['meeting_info']['link']); ?></a></p>
                                                            <?php endif; ?>
                                                            <?php if (($attemptUi['meeting_info']['mode'] ?? '') === 'onsite' && ($attemptUi['meeting_info']['place'] ?? '') !== '') : ?>
                                                                <p class="mt-2"><span class="text-slate-500 dark:text-slate-400">Lieu:</span> <?php echo e((string) $attemptUi['meeting_info']['place']); ?></p>
                                                            <?php endif; ?>
                                                        </div>

                                                        <?php if (($attemptUi['meeting_info']['comment'] ?? '') !== '') : ?>
                                                            <div class="<?php echo e($softCardClass); ?> px-4 py-3">
                                                                <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Message</p>
                                                                <p class="mt-1 whitespace-pre-wrap text-sm text-slate-700 dark:text-slate-200"><?php echo e((string) $attemptUi['meeting_info']['comment']); ?></p>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </details>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </section>
                    </div>
                    <aside class="space-y-6 student-exam-sticky">
                        <section class="<?php echo e($surfaceClass); ?> p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Synthèse</p>
                                    <h2 class="mt-2 text-lg font-display font-semibold text-slate-900 dark:text-slate-100">Ce qu&rsquo;il faut retenir</h2>
                                </div>
                                <span class="<?php echo e($heroStateClass); ?>"><?php echo e($heroStateLabel); ?></span>
                            </div>

                            <div class="mt-4 space-y-3">
                                <div class="<?php echo e($softCardClass); ?> px-4 py-3">
                                    <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Cours</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100"><?php echo e($courseTitle); ?></p>
                                </div>
                                <div class="<?php echo e($softCardClass); ?> px-4 py-3">
                                    <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Type</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100"><?php echo e($typeLabel); ?></p>
                                </div>
                                <div class="<?php echo e($softCardClass); ?> px-4 py-3">
                                    <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Dur&eacute;e</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100"><?php echo e($durationLabel); ?></p>
                                </div>
                                <div class="<?php echo e($softCardClass); ?> px-4 py-3">
                                    <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Tentatives</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100"><?php echo e($attemptsValue); ?></p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400"><?php echo e($attemptsMeta); ?></p>
                                </div>
                                <div class="<?php echo e($softCardClass); ?> px-4 py-3">
                                    <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Progression du cours</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100"><?php echo e($progressValue); ?></p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400"><?php echo e($progressMeta); ?></p>
                                </div>
                            </div>

                            <?php if ($progressTotal > 0): ?>
                                <div class="dash-progress mt-4">
                                    <div class="dash-progress__fill" style="width: <?php echo e((string) $progressPercent); ?>%;"></div>
                                </div>
                            <?php endif; ?>

                            <?php if ($availabilityBlockedReason !== ''): ?>
                                <div class="mt-4 app-feedback app-feedback--warning text-sm">
                                    <p><?php echo e($availabilityBlockedReason); ?></p>
                                </div>
                            <?php endif; ?>
                        </section>

                        <section class="<?php echo e($surfaceClass); ?> p-5">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Mode d&rsquo;emploi</p>
                            <h2 class="mt-2 text-lg font-display font-semibold text-slate-900 dark:text-slate-100">Avant de continuer</h2>

                            <div class="mt-4 space-y-3">
                                <?php foreach ($prepItems as $item): ?>
                                    <article class="<?php echo e($softCardClass); ?> px-4 py-3">
                                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100"><?php echo e((string) ($item['title'] ?? 'Point')); ?></p>
                                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300"><?php echo e((string) ($item['text'] ?? '')); ?></p>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <?php if ($lastAttemptUi): ?>
                            <section class="<?php echo e($surfaceClass); ?> p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Derni&egrave;re tentative</p>
                                        <h2 class="mt-2 text-lg font-display font-semibold text-slate-900 dark:text-slate-100"><?php echo e($lastAttemptStatusLabel); ?></h2>
                                    </div>
                                    <span class="<?php echo e($lastAttemptTone['badge']); ?>"><?php echo e($lastAttemptStatusLabel); ?></span>
                                </div>

                                <div class="mt-4 space-y-3">
                                    <?php if ($lastAttemptVariantLabel !== '' && $examType === 'qcm'): ?>
                                        <div class="<?php echo e($softCardClass); ?> px-4 py-3 text-sm">
                                            <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Version</p>
                                            <p class="mt-1 font-semibold text-slate-900 dark:text-slate-100"><?php echo e($lastAttemptVariantLabel); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($lastAttemptDateLabel !== ''): ?>
                                        <div class="<?php echo e($softCardClass); ?> px-4 py-3 text-sm">
                                            <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Date</p>
                                            <p class="mt-1 font-semibold text-slate-900 dark:text-slate-100"><?php echo e($lastAttemptDateLabel); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <div class="<?php echo e($softCardClass); ?> px-4 py-3 text-sm">
                                        <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Score</p>
                                        <p class="mt-1 font-semibold text-slate-900 dark:text-slate-100"><?php echo e($lastAttemptScoreLabel); ?></p>
                                    </div>

                                    <?php if (($lastAttemptUi['status'] ?? '') === 'graded' && ($lastAttemptUi['is_passed'] ?? false)) : ?>
                                        <div class="app-feedback app-feedback--success text-sm">
                                            <p>Cette tentative a validé l’examen.</p>
                                        </div>
                                    <?php elseif (($lastAttemptUi['status'] ?? '') === 'submitted') : ?>
                                        <div class="app-feedback app-feedback--warning text-sm">
                                            <p>La correction est encore en attente.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </section>
                        <?php endif; ?>

                        <?php if ($antiRules !== [] || $driveSubjectLink !== '' || $certificateCode !== ''): ?>
                            <section class="<?php echo e($surfaceClass); ?> p-5">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Ressources</p>
                                <h2 class="mt-2 text-lg font-display font-semibold text-slate-900 dark:text-slate-100">Acc&egrave;s et r&egrave;gles</h2>

                                <?php if ($antiRules !== []): ?>
                                    <div class="mt-4 <?php echo e($softCardClass); ?> p-4">
                                        <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Consignes d'examen</p>
                                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Ces consignes sont rappelées ici comme cadre de passage communiqué par l'enseignant.</p>
                                        <ul class="mt-3 space-y-2 text-sm text-slate-700 dark:text-slate-200">
                                            <?php foreach ($antiRules as $rule): ?>
                                                <li class="flex items-start gap-2">
                                                    <span class="mt-0.5 text-slate-600 dark:text-slate-300">&#8226;</span>
                                                    <span><?php echo e($rule); ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>

                                <?php if ($driveSubjectLink !== ''): ?>
                                    <div class="mt-4 <?php echo e($softCardClass); ?> p-4">
                                        <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Sujet</p>
                                        <a href="<?php echo e($driveSubjectLink); ?>" target="_blank" rel="noopener" class="mt-2 inline-block break-all text-sm font-semibold app-link-primary underline">
                                            <?php echo e($driveSubjectText !== '' ? $driveSubjectText : $driveSubjectLink); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <?php if ($certificateViewUrl !== ''): ?>
                                    <div class="mt-4 <?php echo e($softCardClass); ?> p-4">
                                        <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Certificat associé</p>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            <a href="<?php echo e($certificateViewUrl); ?>" target="_blank" rel="noopener" class="app-btn-primary inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition">
                                                <span class="app-icon" aria-hidden="true"><i class="<?php echo e(dashboard_icon_fa_class('external-link')); ?>"></i></span>
                                                <span>Voir le certificat</span>
                                            </a>
                                            <a href="<?php echo e($certificatePdfUrl); ?>" class="app-btn-soft inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition">
                                                <span class="app-icon" aria-hidden="true"><i class="<?php echo e(dashboard_icon_fa_class('download')); ?>"></i></span>
                                                <span>Télécharger le PDF</span>
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </section>
                        <?php endif; ?>
                    </aside>
                </div>
            </main>
        </div>
    </div>

    <?php if ($onsiteDisclaimerRequired): ?>
        <div id="onsite-disclaimer-modal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-slate-950/70 px-4">
            <div class="w-full max-w-xl rounded-3xl border border-slate-300 bg-white p-6 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                <p class="text-xs uppercase tracking-[0.2em] app-inline-note app-inline-note--warning">Information importante</p>
                <h2 class="mt-2 text-xl font-display font-semibold text-slate-900 dark:text-slate-100">Examen en présentiel</h2>
                <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                    Si vous choisissez un examen en pr&eacute;sentiel, vous reconnaissez que la plateforme &Eacute;cole Num&eacute;rique n&rsquo;est pas responsable des incidents, dommages ou litiges qui pourraient survenir hors ligne.
                </p>
                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                    Vérifiez l’adresse, les conditions de sécurité et l’identité de votre interlocuteur avant de confirmer votre participation.
                </p>
                <div class="mt-5 flex flex-wrap justify-end gap-3">
                    <button type="button" id="onsite-disclaimer-later" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-400 dark:border-slate-700 dark:bg-slate-950/70 dark:text-slate-100 dark:hover:border-slate-500">Plus tard</button>
                    <button type="button" id="onsite-disclaimer-ack" class="app-btn-primary inline-flex items-center rounded-xl px-4 py-2.5 text-sm font-semibold transition">J’ai compris</button>
                </div>
            </div>
        </div>
        <script>
        (function () {
            var userId = <?php echo (int) $studentUserId; ?>;
            if (userId < 1) return;

            var key = 'onsite_disclaimer_ack_u' + String(userId);
            var modal = document.getElementById('onsite-disclaimer-modal');
            var btnLater = document.getElementById('onsite-disclaimer-later');
            var btnAck = document.getElementById('onsite-disclaimer-ack');
            var pendingFormSubmit = null;

            if (!modal) return;

            function openModal() {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            function hasAck() {
                try {
                    return window.localStorage.getItem(key) === '1';
                } catch (e) {
                    return false;
                }
            }

            function setAck() {
                try {
                    window.localStorage.setItem(key, '1');
                } catch (e) {}
            }

            var onsiteAlreadyBooked = <?php echo (($meetingMyUi['mode'] ?? '') === 'onsite') ? 'true' : 'false'; ?>;
            if (onsiteAlreadyBooked && !hasAck()) {
                openModal();
            }

            var bookingFormMarker = document.querySelector('form input[name="action"][value="book_meeting_slot"]');
            if (bookingFormMarker && bookingFormMarker.form) {
                bookingFormMarker.form.addEventListener('submit', function (event) {
                    var selectedSlot = bookingFormMarker.form.querySelector('input[name="meeting_id"]:checked');
                    var selectedMode = selectedSlot ? (selectedSlot.getAttribute('data-meeting-mode') || '') : '';
                    if (selectedMode === 'onsite' && !hasAck()) {
                        event.preventDefault();
                        pendingFormSubmit = bookingFormMarker.form;
                        openModal();
                    }
                });
            }

            if (btnLater) {
                btnLater.addEventListener('click', function () {
                    pendingFormSubmit = null;
                    closeModal();
                });
            }

            if (btnAck) {
                btnAck.addEventListener('click', function () {
                    setAck();
                    closeModal();
                    if (pendingFormSubmit) {
                        var submitTarget = pendingFormSubmit;
                        pendingFormSubmit = null;
                        submitTarget.submit();
                    }
                });
            }
        })();
        </script>
    <?php endif; ?>
</body>
</html>


