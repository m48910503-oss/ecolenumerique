<?php

$docLabel = isset($docLabel) && is_callable($docLabel) ? $docLabel : static function (string $type, string $mime): string {

        if ($type === 'exercise') {

            return 'Exercice';

        }

if ($type === 'exam') {
            return 'Examen';
        }  
if ($type === 'resource') {

            if (is_string($mime) && stripos($mime, 'url_document') !== false) {

                return "Document d'accompagnement";

            }

if (is_string($mime) && stripos($mime, 'url_file') !== false) {

                return 'Fichier';

            }

return 'Ressource';

        }

return 'Ressource';

    };



$courseData = is_array($course ?? null) ? $course : [];

$chaptersList = is_array($chapters ?? null) ? $chapters : [];

$lessonsCatalog = is_array($lessonsList ?? null) ? $lessonsList : [];

$meetingsList = is_array($meetings ?? null) ? $meetings : [];

$chapterDocs = is_array($docsByChapter ?? null) ? $docsByChapter : [];

$lessonDocs = is_array($docsByLesson ?? null) ? $docsByLesson : [];

$categoriesList = is_array($categories ?? null) ? $categories : [];

$objectives = is_array($objectives ?? null) ? $objectives : [];

$prerequisites = is_array($prerequisites ?? null) ? $prerequisites : [];

$equipment = is_array($equipment ?? null) ? $equipment : [];

$errors = is_array($errors ?? null) ? $errors : [];

$success = trim((string) ($success ?? ''));

$isLocked = !empty($isLocked);
$driveReady = !empty($driveReady);
$driveNeedsConnect = !empty($driveNeedsConnect);
$driveConnectRecommended = !empty($driveConnectRecommended);
$driveUploadNote = trim((string) ($driveUploadNote ?? ''));
$driveMaxMb = max(1, (int) ($driveMaxMb ?? 25));



$courseStatus = trim((string) ($courseData['status'] ?? 'draft'));
$lastRejectionReason = trim((string) ($courseData['last_rejection_reason'] ?? ''));

$courseStatusLabel = match ($courseStatus) {

    'approved' => 'Approuvé',

    'submitted' => 'Soumis',

    'rejected' => 'Rejeté',

    'archived' => 'Archivé',

    default => 'Brouillon',

};



$categoryName = 'Non catégorisé';

foreach ($categoriesList as $cat) {

    if ((string) ($cat['id'] ?? '') === (string) ($categoryId ?? '')) {

        $categoryName = trim((string) ($cat['name'] ?? 'Non catégorisé'));

        break;

    }

}

$pedagogicalChapterCount = 0;
$contentChaptersList = [];

foreach ($chaptersList as $chapterRow) {

    if (($chapterRow['title'] ?? '') !== 'Examens') {

        $pedagogicalChapterCount++;
        $contentChaptersList[] = $chapterRow;

    }

}
$firstContentChapterId = (int) (($contentChaptersList[0]['id'] ?? 0));

$chapterResourceCount = 0;

foreach ($chapterDocs as $items) {

    if (is_array($items)) {

        $chapterResourceCount += count($items);

    }

}

$lessonResourceCount = 0;

foreach ($lessonDocs as $items) {

    if (is_array($items)) {

        $lessonResourceCount += count($items);

    }

}

$resourceCount = $chapterResourceCount + $lessonResourceCount;

$lessonCount = count($lessonsCatalog);

$meetingCount = count($meetingsList);

$objectiveCount = count(array_filter($objectives, static fn($value): bool => trim((string) $value) !== ''));

$prerequisiteCount = count(array_filter($prerequisites, static fn($value): bool => trim((string) $value) !== ''));

$equipmentCount = count(array_filter($equipment, static fn($value): bool => trim((string) $value) !== ''));

$hasAdvancedExam = !empty($advExam);

$hasCover = !empty($courseData['cover_path']);

$hasGeneralResource = !empty($generalFilePath);

$updateNoticeActive = ($courseData['status'] ?? '') === 'approved' && !empty($courseData['update_in_progress']);



$courseIdentityReady = trim((string) ($title ?? '')) !== '' && trim((string) ($description ?? '')) !== '' && trim((string) ($categoryId ?? '')) !== '';

$courseStructureHasOutline = $pedagogicalChapterCount > 0 && $lessonCount > 0;

$courseStructureHasMeeting = $meetingCount > 0;

$coursePedagogyReady = $courseStructureHasOutline && $courseStructureHasMeeting;

$courseAssetsReady = $objectiveCount > 0 && $prerequisiteCount > 0 && $equipmentCount > 0;

$coursePublicationReady = !$isLocked;



$courseReadyCount = 0;

foreach ([$courseIdentityReady, $coursePedagogyReady, $courseAssetsReady, $coursePublicationReady] as $flag) {

    if ($flag) {

        $courseReadyCount++;

    }

}

$courseProgressPct = (int) round(($courseReadyCount / 4) * 100);



$courseSummaryNotes = [];

if (!$courseIdentityReady) {

    $courseSummaryNotes[] = 'Complétez le titre, la catégorie et la description du cours.';

}

if (!$coursePedagogyReady) {

    if (!$courseStructureHasOutline) {

        $courseSummaryNotes[] = 'Ajoutez au moins un chapitre et une leçon pour structurer le cours.';

    }

if (!$courseStructureHasMeeting) {

        $courseSummaryNotes[] = 'Ajoutez au moins un rendez-vous avant la soumission.';

    }

}

if (!$courseAssetsReady) {

    $courseSummaryNotes[] = 'Renseignez les objectifs, les prérequis et l’équipement attendu.';

}

if ($isLocked) {

    $courseSummaryNotes[] = 'Le cours est verrouillé tant qu’il reste soumis à validation.';

}

$examStatusLabel = $hasAdvancedExam ? 'Examen configuré' : 'Aucun examen';

$generalResourceLabel = $hasGeneralResource ? 'Ressource générale disponible' : 'Aucune ressource générale';

$levelLabel = match ((string) ($level ?? 'beginner')) {

    'intermediate' => 'Intermédiaire',

    'advanced' => 'Avancé',

    default => 'Débutant',

};

$descriptionCount = function_exists('mb_strlen') ? mb_strlen((string) ($description ?? ''), 'UTF-8') : strlen((string) ($description ?? ''));

$courseCoverPath = trim((string) ($courseData['cover_path'] ?? ''));

$courseCoverUrl = '';

if ($courseCoverPath !== '') {

    $courseCoverUrl = preg_match('~^https://~i', $courseCoverPath) ? $courseCoverPath : site_url(ltrim($courseCoverPath, '/'));

}

?>

<!doctype html>

<html lang="fr"> 

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="app-global-assets" content="off">

    <title>Modifier un cours - École Numérique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo e(site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()))); ?>"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/teacher-dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/teacher-dashboard.css') ?: time()))); ?>">
    <script src="<?php echo e(site_url('assets/js/course_create_fields.js')); ?>" defer></script>

    <script src="<?php echo e(site_url('assets/js/course_content.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/course_content.js') ?: time()))); ?>" defer></script>

    <script src="<?php echo e(site_url('assets/js/referral_toggle.js')); ?>" defer></script>

    <script src="<?php echo e(site_url('assets/js/sidebar_toggle.js')); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/teacher_dashboard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/teacher_dashboard.js') ?: time()))); ?>" defer></script>

<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="app-view app-role-teacher min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50 text-slate-900 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950 dark:text-slate-100 font-body" data-view="teacher/course_edit">

        <div class="teacher-dashboard-layout relative h-screen overflow-hidden pt-16 sm:pt-[4.5rem] md:pl-72 md:flex-row">

        <?php $teacherNavActive = 'courses'; ?>

        <?php require __DIR__ . '/_sidebar.php'; ?>

        <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>



        <div class="flex-1">

            <?php $teacherTopTitle = 'Modifier le cours'; ?>

            <?php $teacherTopSubtitle = 'Espace enseignant'; ?>

            <?php require __DIR__ . '/_topbar.php'; ?>

<main class="teacher-workflow-main max-w-7xl mx-auto px-6 py-8 space-y-6">
        <?php $teacherWelcomeTitle = 'Modifier le cours'; ?>
        <?php $teacherWelcomeSummary = 'Modifiez le cours avec les données déjà chargées.'; ?>
        <?php $teacherWelcomeActionUrl = site_url('teacher/courses'); ?>
        <?php $teacherWelcomeActionLabel = 'Mes cours'; ?>
        <?php $teacherWelcomeActionIcon = 'arrow-left'; ?>
        <?php require __DIR__ . '/_welcome_hero.php'; ?>

        <section class="rounded-2xl border border-slate-800 bg-slate-900/40 p-6 space-y-4">

            <div class="flex flex-wrap items-start justify-between gap-4">

                <div class="flex items-start gap-3">

                    <span class="app-icon text-emerald-300" aria-hidden="true"><?php echo dashboard_icon_svg('file-text'); ?></span>

                    <div>

                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Cours existant</p>

                        <h1 class="text-2xl font-semibold text-slate-100">Mettez à jour votre cours sans changer le flux</h1>

                        <p class="mt-1 text-sm text-slate-300">Modifiez le cours avec les données déjà chargées.</p>

                    </div>

                </div>

                <div class="course-meta">

                    <div class="course-meta-row">

                        <span class="course-meta-item">

                            <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('check-circle'); ?></span>

                            <?php echo e($courseStatusLabel); ?>

                        </span>

                        <span class="course-meta-item">

                            <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('folder'); ?></span>

                            <?php echo e($categoryName); ?>

                        </span>

                        <span class="course-meta-item">

                            <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('layers'); ?></span>

                            <?php echo e($examStatusLabel); ?>

                        </span>

                    </div>

                    <div class="course-meta-row">

                        <span id="course-create-local-status" class="course-create-local-status course-create-local-status--info">Brouillon local sauvegardé.</span>

                        <?php if ($updateNoticeActive): ?>

                            <span class="course-meta-item">

                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('alert-circle'); ?></span>

                                Mise à jour signalée

                            </span>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </section>



        <?php if ($errors): ?>

    <div class="course-content-alert course-content-alert--danger">

        <span class="course-content-alert__icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('alert-circle'); ?></span>

        <div class="course-content-alert__body">

            <p class="course-content-alert__title">Corrections requises</p>

            <?php foreach ($errors as $err): ?>

                <p><?php echo e($err); ?></p>

            <?php endforeach; ?>

        </div>

    </div>

<?php endif; ?>

<?php if ($success): ?>

    <div class="course-content-alert course-content-alert--success">

        <span class="course-content-alert__icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('check-circle'); ?></span>

        <div class="course-content-alert__body">

            <p class="course-content-alert__title">Modification enregistr&eacute;e</p>

            <p><?php echo e($success); ?></p>

        </div>

    </div>

<?php endif; ?>

<?php if ($courseStatus === 'rejected' && $lastRejectionReason !== ''): ?>

    <div class="course-content-alert course-content-alert--warning course-content-alert--inline">

        <span class="course-content-alert__icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('alert-triangle'); ?></span>

        <div class="course-content-alert__body">

            <p class="course-content-alert__title">Retour de modération</p>

            <p><?php echo e($lastRejectionReason); ?></p>

        </div>

    </div>

<?php endif; ?>

<?php if ($updateNoticeActive): ?>

    <div class="course-content-alert course-content-alert--info course-content-alert--inline">

        <span class="course-content-alert__icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('help-circle'); ?></span>

        <div class="course-content-alert__body">

            <p class="course-content-alert__title">Mise &agrave; jour visible c&ocirc;t&eacute; apprenant</p>

            <p>Cours signal&eacute; en mise &agrave; jour.</p>

        </div>

        <form method="post">

            <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">

            <input type="hidden" name="action" value="clear_update_notice">

            <button type="submit" class="course-content-inline-btn app-btn-success">

                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('check-circle'); ?></span>

                Terminer

            </button>

        </form>

    </div>

<?php endif; ?>



<div class="grid gap-6 lg:grid-cols-3 lg:items-start">

    <div class="lg:col-span-2 space-y-6">

        <form id="course-info-form" class="space-y-6" method="post" action="<?php echo e(site_url('teacher/course_edit?id=' . $courseId)); ?>" enctype="multipart/form-data" data-course-create-form data-course-create-lite data-autosave-key="<?php echo e('course_edit_' . $courseId); ?>">

            <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">

            <input type="hidden" name="action" value="update_course">



            <div id="course-create-restore-banner" class="course-restore-banner hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-900 dark:border-red-500/40 dark:bg-red-500/10 dark:text-red-100 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between" data-feedback-inline="1">

                <div>

                    <p class="font-semibold">Un brouillon local est disponible.</p>

                    <p class="text-[11px] text-red-700/80 dark:text-red-100/80">Vous pouvez restaurer les champs tels qu'ils étaient.</p>

                </div>

                <div class="flex gap-2">

                    <button type="button" id="course-create-restore-btn" class="course-restore-btn px-3 py-1 rounded border border-red-300 text-red-900 hover:border-red-400 dark:border-red-300/60 dark:text-red-100 dark:hover:border-red-200">Restaurer</button>

                    <button type="button" id="course-create-ignore-restore-btn" class="course-restore-btn px-3 py-1 rounded border border-red-300 text-red-900 hover:border-red-400 dark:border-red-300/60 dark:text-red-100 dark:hover:border-red-200">Ignorer</button>

                </div>

            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900/40 p-5 space-y-2">

                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Édition complète</p>

                <p class="text-sm text-slate-300">Les sections sont réunies sur une seule page.</p>

            </div>



    <section class="rounded-2xl border border-slate-800 bg-slate-900/40 p-6 space-y-4">

        <div class="flex items-center gap-2">

            <span class="app-icon text-sky-300" aria-hidden="true"><?php echo dashboard_icon_svg('book-open'); ?></span>

            <h2 class="text-lg font-semibold text-slate-100">Informations du cours</h2>

            <span class="course-section-step">Section 1</span>

        </div>



        <div class="grid gap-4 md:grid-cols-2">

            <div>

                <label class="block text-sm mb-1" for="title">Titre du cours *</label>

                <input class="w-full px-4 py-3 rounded-lg bg-slate-900 border border-slate-700 focus:border-emerald-400 outline-none"

                    type="text"

                    id="title"

                    name="title"

                    required

                    value="<?php echo e($title); ?>"

                    placeholder="Ex: Ma&icirc;triser Excel pour la finance"

                >

                <p class="mt-2 course-create-hint course-create-hint--info hidden" data-hint-for="title"></p>

            </div>

            <div>

                <label class="block text-sm mb-1" for="category_id">Cat&eacute;gorie <span class="text-xs text-slate-400">(requise pour la soumission)</span></label>

                <select class="w-full px-4 py-3 rounded-lg bg-slate-900 border border-slate-700 focus:border-emerald-400 outline-none" id="category_id" name="category_id">

                    <option value="">-- S&eacute;lectionner --</option>

                    <?php foreach ($categories as $cat): ?>

                        <option value="<?php echo e($cat['id']); ?>" <?php echo (string) $categoryId === (string) $cat['id'] ? 'selected' : ''; ?>>

                            <?php echo e($cat['name']); ?>

                        </option>

                    <?php endforeach; ?>

                    <option value="other" <?php echo (string) $categoryId === 'other' ? 'selected' : ''; ?>>Autre</option>

                </select>

                <p class="mt-2 course-create-hint course-create-hint--info hidden" data-hint-for="category"></p>

                <p class="mt-2 text-xs text-slate-400">Si votre catégorie n’apparaît pas, choisissez Autre.</p>

            </div>

        </div>



        <div class="grid gap-4 md:grid-cols-3">

            <div>

                <label class="block text-sm mb-1" for="level">Niveau *</label>

                <select class="w-full px-4 py-3 rounded-lg bg-slate-900 border border-slate-700 focus:border-emerald-400 outline-none" id="level" name="level" required>

                    <option value="beginner" <?php echo $level === 'beginner' ? 'selected' : ''; ?>>D&eacute;butant</option>

                    <option value="intermediate" <?php echo $level === 'intermediate' ? 'selected' : ''; ?>>Interm&eacute;diaire</option>

                    <option value="advanced" <?php echo $level === 'advanced' ? 'selected' : ''; ?>>Avanc&eacute;</option>

                </select>

            </div>

            <div>

                <label class="block text-sm mb-1" for="duration_hours">Dur&eacute;e totale (calcul&eacute;e) *</label>

                <input class="w-full px-4 py-3 rounded-lg bg-slate-900 border border-slate-700 focus:border-emerald-400 outline-none"

                    type="number"

                    step="0.01"

                    min="0"

                    id="duration_hours"

                    name="duration_hours"

                    required

                    readonly

                    value="<?php echo e($durationHours); ?>"

                    placeholder="Ex: 6"

                >

                <p class="mt-1 text-xs text-slate-400">Calcul automatique depuis les le&ccedil;ons.</p>

                <p class="mt-2 course-create-hint course-create-hint--info hidden" data-hint-for="duration"></p>

            </div>

            <div>

                <label class="block text-sm mb-1" for="price_xof">Prix (F CFA) - 0 = Gratuit *</label>

                <input class="w-full px-4 py-3 rounded-lg bg-slate-900 border border-slate-700 focus:border-emerald-400 outline-none"

                    type="number"

                    min="0"

                    step="1"

                    id="price_xof"

                    name="price_xof"

                    required

                    value="<?php echo e($priceXof); ?>"

                    placeholder="Ex: 15000"

                >

                <p class="mt-2 text-xs text-slate-400">Saisissez 0 pour laisser ce cours gratuit.</p>

                <p class="mt-2 course-create-hint course-create-hint--info hidden" data-hint-for="price"></p>

            </div>

        </div>



        <?php $enterpriseOfferMode = trim((string) ($enterprisePriceXof ?? '')) !== '' ? 'price' : (trim((string) ($enterpriseDiscountPercent ?? '')) !== '' ? 'discount' : 'none'); ?>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4 space-y-4" data-enterprise-offer-root>

            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">

                <div>

                    <p class="text-sm font-semibold text-slate-100">Tarification entreprise</p>

                    <p class="mt-1 text-xs text-slate-400">Choisissez un seul avantage entreprise pour garder un prix clair.</p>

                </div>

                <input type="hidden" id="enterprise_offer_mode" data-enterprise-mode-input value="<?php echo e($enterpriseOfferMode); ?>">

                <div class="flex flex-wrap gap-2">

                    <button type="button" class="inline-flex items-center justify-center rounded-full border border-slate-700 px-3 py-2 text-xs font-semibold text-slate-200 transition hover:border-sky-400 hover:text-white" data-enterprise-mode-btn="none">Aucune</button>

                    <button type="button" class="inline-flex items-center justify-center rounded-full border border-slate-700 px-3 py-2 text-xs font-semibold text-slate-200 transition hover:border-sky-400 hover:text-white" data-enterprise-mode-btn="price">Prix fixe</button>

                    <button type="button" class="inline-flex items-center justify-center rounded-full border border-slate-700 px-3 py-2 text-xs font-semibold text-slate-200 transition hover:border-sky-400 hover:text-white" data-enterprise-mode-btn="discount">R&eacute;duction</button>

                </div>

            </div>

            <div data-enterprise-price-panel class="<?php echo $enterpriseOfferMode === 'price' ? '' : 'hidden '; ?>rounded-xl border border-slate-800 bg-slate-950/70 p-4">

                <label class="block text-sm mb-1" for="enterprise_price_xof">Prix entreprise (F CFA)</label>

                <input class="w-full px-4 py-3 rounded-lg bg-slate-900 border border-slate-700 focus:border-sky-400 outline-none" type="number" min="1" step="1" id="enterprise_price_xof" name="enterprise_price_xof" value="<?php echo e((string) ($enterprisePriceXof ?? '')); ?>" placeholder="Ex: 25000">

                <p class="mt-1 text-xs text-slate-400">Montant fixe par place pour un compte entreprise.</p>

            </div>

            <div data-enterprise-discount-panel class="<?php echo $enterpriseOfferMode === 'discount' ? '' : 'hidden '; ?>rounded-xl border border-slate-800 bg-slate-950/70 p-4">

                <label class="block text-sm mb-1" for="enterprise_discount_percent">R&eacute;duction entreprise (%)</label>

                <input class="w-full px-4 py-3 rounded-lg bg-slate-900 border border-slate-700 focus:border-sky-400 outline-none" type="number" min="0.01" max="100" step="0.01" id="enterprise_discount_percent" name="enterprise_discount_percent" value="<?php echo e((string) ($enterpriseDiscountPercent ?? '')); ?>" placeholder="Ex: 15">

                <p class="mt-1 text-xs text-slate-400">Pourcentage de r&eacute;duction appliqu&eacute; aux achats entreprise.</p>

            </div>

        </div>

    </section>





    <section class="rounded-2xl border border-slate-800 bg-slate-900/40 p-6 space-y-4">

        <div class="flex items-center gap-2">

            <span class="app-icon text-indigo-300" aria-hidden="true"><?php echo dashboard_icon_svg('file-text'); ?></span>

            <h2 class="text-lg font-semibold text-slate-100">Description du cours</h2>

            <span class="course-section-step">Section 2</span>

        </div>

        <div>

            <div class="flex items-center justify-between">

                <label class="block text-sm mb-1" for="description">Description *</label>

                <span class="text-xs text-slate-400" data-description-counter><?php echo e((string) $descriptionCount); ?> / 3000</span>

            </div>

            <textarea class="w-full px-4 py-3 rounded-lg bg-slate-900 border border-slate-700 focus:border-emerald-400 outline-none" id="description" name="description" rows="5" required placeholder="Expliquez clairement ce que l'apprenant va obtenir en fin de cours."><?php echo e($description); ?></textarea>

            <p class="mt-2 course-create-hint course-create-hint--info hidden" data-hint-for="description"></p>

        </div>

    </section>



    <section class="rounded-2xl border border-slate-800 bg-slate-900/40 p-6 space-y-4">

        <div class="flex items-center gap-2">

            <span class="app-icon text-emerald-300" aria-hidden="true"><?php echo dashboard_icon_svg('check-circle'); ?></span>

            <h2 class="text-lg font-semibold text-slate-100">Objectifs et pr&eacute;requis</h2>

        </div>

        <div class="grid gap-4">

            <div class="course-list-card">

                <div class="course-list-head">

                    <div>

                        <label class="block text-sm font-semibold text-slate-100">Objectifs *</label>

                        <p class="course-list-hint">Ce que l'apprenant saura faire.</p>

                    </div>

                    <div class="course-list-meta">

                        <span class="course-list-count" data-list-count="objectives-list" data-list-max="6">0/6</span>

                    </div>

                </div>

                <div class="course-list-quick">

                    <input type="text" class="course-list-quick-input" data-list-quick-input data-list-target="objectives-list" placeholder="Ajouter un objectif puis Entr&eacute;e">

                    <button type="button" class="course-list-add-btn" data-add-target="objectives-list">Ajouter</button>

                </div>

                <div id="objectives-list" class="course-list course-list--checklist" data-initial="<?php echo e(json_encode($objectives, JSON_UNESCAPED_UNICODE)); ?>" data-name="objectives[]" data-placeholder="Ex: Comprendre les bases"></div>

                <div class="course-list-footer">

                    <span class="course-list-note">Glissez pour r&eacute;ordonner</span>

                </div>

            </div>

            <div class="course-list-card">

                <div class="course-list-head">

                    <div>

                        <label class="block text-sm font-semibold text-slate-100">Pr&eacute;requis *</label>

                        <p class="course-list-hint">Ce qu'il faut conna&icirc;tre avant de commencer.</p>

                    </div>

                    <div class="course-list-meta">

                        <span class="course-list-count" data-list-count="prerequisites-list" data-list-max="6">0/6</span>

                    </div>

                </div>

                <div class="course-list-quick">

                    <input type="text" class="course-list-quick-input" data-list-quick-input data-list-target="prerequisites-list" placeholder="Ajouter un pr&eacute;requis puis Entr&eacute;e">

                    <button type="button" class="course-list-add-btn" data-add-target="prerequisites-list">Ajouter</button>

                </div>

                <div id="prerequisites-list" class="course-list course-list--checklist" data-initial="<?php echo e(json_encode($prerequisites, JSON_UNESCAPED_UNICODE)); ?>" data-name="prerequisites[]" data-placeholder="Ex: Conna&icirc;tre Excel"></div>

                <div class="course-list-footer">

                    <span class="course-list-note">Glissez pour r&eacute;ordonner</span>

                </div>

            </div>

            <div class="course-list-card">

                <div class="course-list-head">

                    <div>

                        <label class="block text-sm font-semibold text-slate-100">&Eacute;quipement *</label>

                        <p class="course-list-hint">Mat&eacute;riel n&eacute;cessaire pour suivre le cours.</p>

                    </div>

                    <div class="course-list-meta">

                        <span class="course-list-count" data-list-count="equipment-list" data-list-max="6">0/6</span>

                    </div>

                </div>

                <div class="course-list-quick">

                    <input type="text" class="course-list-quick-input" data-list-quick-input data-list-target="equipment-list" placeholder="Ajouter un &eacute;quipement puis Entr&eacute;e">

                    <button type="button" class="course-list-add-btn" data-add-target="equipment-list">Ajouter</button>

                </div>

                <div id="equipment-list" class="course-list course-list--checklist" data-initial="<?php echo e(json_encode($equipment, JSON_UNESCAPED_UNICODE)); ?>" data-name="equipment[]" data-placeholder="Ex: Ordinateur portable"></div>

                <div class="course-list-footer">

                    <span class="course-list-note">Glissez pour r&eacute;ordonner</span>

                </div>

            </div>

        </div>

    </section>





    <section class="rounded-2xl border border-slate-800 bg-slate-900/40 p-6 space-y-4">

        <div class="flex items-center gap-2">

            <span class="app-icon text-sky-300" aria-hidden="true"><?php echo dashboard_icon_svg('upload'); ?></span>

            <h2 class="text-lg font-semibold text-slate-100">Fichiers du cours</h2>

            <span class="course-section-step">Section 3</span>

        </div>



        <div class="grid gap-4 lg:grid-cols-2">

            <div class="course-create-upload-panel rounded-xl border border-slate-800 bg-slate-900/60 p-4 space-y-3">

                <label class="block text-sm" for="cover_file">Miniature (JPG/PNG/WebP, max 5 Mo)</label>

                <input class="w-full px-4 py-3 rounded-lg bg-slate-900 border border-slate-700 focus:border-emerald-400 outline-none file:mr-3 file:py-2 file:px-3 file:border-0 file:text-sm file:bg-slate-800 file:text-slate-200" type="file" id="cover_file" name="cover_file" accept=".jpg,.jpeg,.png,.webp">

                <p class="text-xs text-slate-400">Ajoutez une image claire (ratio conseill&eacute; 16:9).</p>

                <p class="course-create-hint course-create-hint--info hidden" data-hint-for="cover"></p>

                <div id="cover-progress-wrap" class="hidden rounded-lg border border-slate-800 bg-slate-950/70 p-3">

                    <div class="flex items-center justify-between gap-3 text-xs">

                        <span class="font-medium text-slate-200">Miniature</span>

                        <span id="cover-progress-text" class="text-slate-400">0%</span>

                    </div>

                    <div class="mt-2 h-2 rounded bg-slate-800 border border-slate-700 overflow-hidden"><div id="cover-progress-bar" class="h-full w-0 bg-emerald-500 transition-all duration-150"></div></div>

                </div>

                <div class="rounded-lg border border-slate-800 bg-slate-950/60 overflow-hidden">

                    <img id="cover-preview" class="h-40 w-full object-cover" src="<?php echo e($courseCoverUrl !== '' ? $courseCoverUrl : 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw=='); ?>" alt="Aper&ccedil;u miniature" <?php echo $courseCoverUrl !== '' ? '' : 'data-cover-empty="1"'; ?> loading="lazy">

                </div>

                <?php if (!empty($course['cover_path'])): ?>

                    <p class="text-xs text-slate-400">Miniature actuelle : <?php echo e($course['cover_path']); ?></p>

                <?php endif; ?>

            </div>

            <div class="course-create-upload-panel rounded-xl border border-slate-800 bg-slate-900/60 p-4 space-y-3">

                <label class="block text-sm" for="general_file">Ressource g&eacute;n&eacute;rale (Drive, optionnel)</label>

                <input class="w-full px-4 py-3 rounded-lg bg-slate-900 border border-slate-700 focus:border-emerald-400 outline-none file:mr-3 file:py-2 file:px-3 file:border-0 file:text-sm file:bg-slate-800 file:text-slate-200" type="file" id="general_file" name="general_file">

                <p class="text-xs text-slate-400">

                    Taille max: <?php echo e((string) $driveMaxMb); ?> Mo.

                    <?php echo e($driveUploadNote !== '' ? $driveUploadNote : "Google Drive n'est pas configure."); ?>

                    <?php if ($driveConnectRecommended): ?><a class="underline ml-1" href="<?php echo e(site_url('drive/oauth_connect')); ?>">Connecter Drive</a><?php endif; ?>

                </p>

                <div id="general-progress-wrap" class="hidden rounded-lg border border-slate-800 bg-slate-950/70 p-3">

                    <div class="flex items-center justify-between gap-3 text-xs">

                        <span class="font-medium text-slate-200">Ressource g&eacute;n&eacute;rale</span>

                        <span id="general-progress-text" class="text-slate-400">0%</span>

                    </div>

                    <div class="mt-2 h-2 rounded bg-slate-800 border border-slate-700 overflow-hidden"><div id="general-progress-bar" class="h-full w-0 bg-emerald-500 transition-all duration-150"></div></div>

                </div>

                <?php if (!empty($generalFilePath)): ?>

                    <div class="rounded-lg border border-slate-800 bg-slate-950/70 px-3 py-3 text-xs text-slate-300">

                        <p>Fichier actuel : <a class="dash-link" href="<?php echo e($generalFilePath); ?>" target="_blank" rel="noopener">Ouvrir</a></p>

                        <label class="mt-3 inline-flex items-center gap-2 text-slate-300">

                            <input type="checkbox" name="general_file_clear" value="1" class="rounded border-slate-700 bg-slate-900">

                            Supprimer la ressource actuelle

                        </label>

                    </div>

                <?php else: ?>

                    <p class="text-xs text-slate-500">Aucune ressource g&eacute;n&eacute;rale n'est encore attach&eacute;e.</p>

                <?php endif; ?>

            </div>

        </div>

        <div id="upload-progress-wrap" class="hidden rounded-lg border border-slate-800 bg-slate-950/70 p-3">

            <div class="flex items-center justify-between gap-3 text-xs">

                <span class="font-medium text-slate-200">Envoi global</span>

                <span id="upload-progress-text" class="text-slate-400">0%</span>

            </div>

            <div class="mt-2 h-2 rounded bg-slate-800 border border-slate-700 overflow-hidden"><div id="upload-progress-bar" class="h-full w-0 bg-emerald-500 transition-all duration-150"></div></div>

        </div>

    </section>



    <section class="rounded-2xl border border-slate-800 bg-slate-900/40 p-6 space-y-4">

        <div class="flex items-center gap-2">

            <span class="app-icon text-cyan-300" aria-hidden="true"><?php echo dashboard_icon_svg('users'); ?></span>

            <h2 class="text-lg font-semibold text-slate-100">Param&egrave;tres du cours</h2>

        </div>

        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4 space-y-3">

            <div class="flex items-center gap-2 text-sm">

                <input type="checkbox" name="referral_override" value="1" class="rounded border-slate-700 bg-slate-900" data-referral-toggle <?php echo $referralOverride ? 'checked' : ''; ?>>

                <label>Activer le parrainage pour ce cours</label>

            </div>

            <div class="space-y-3<?php echo $referralOverride ? '' : ' hidden'; ?>" data-referral-panel>

                <p class="text-xs text-slate-400">Choisissez L1 seul ou L1+L2.</p>

                <p class="text-xs text-slate-500">Taux plateforme : L1 <?php echo e(number_format((float) $platformReferral['l1_rate'], 2, '.', '')); ?>% - L2 <?php echo e(number_format((float) $platformReferral['l2_rate'], 2, '.', '')); ?>%</p>

                <label class="inline-flex items-center gap-2 text-xs text-slate-400">

                    <input type="checkbox" name="referral_l2_enabled" value="1" class="rounded border-slate-700 bg-slate-900" <?php echo $referralL2Enabled ? 'checked' : ''; ?>>

                    Activer L2 (optionnel)

                </label>

            </div>

        </div>

    </section>

    <div class="flex justify-end pt-2">

        <button type="submit" class="course-content-action app-btn-success">

            <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('check-circle'); ?></span>

            Enregistrer les modifications

        </button>

    </div>

</form>

<section id="tabs-container" data-course-content-root data-course-id="<?php echo e((string) $courseId); ?>" data-initial-tab="chapter" class="course-content-card course-content-builder lg:col-span-2">

    <div class="course-content-card__header">

        <div>

            <h2 class="course-content-card__title">Construction du contenu</h2>

            <p class="course-content-card__subtitle">Ajoutez chapitres, le&ccedil;ons, ressources, rendez-vous et examen.</p>

        </div>

        <span class="course-content-pill course-content-pill--info">Cr&eacute;ation &amp; mise &agrave; jour</span>

    </div>

    <div class="course-content-tabs">

        <button type="button" class="tab-button course-content-tab" data-tab-button data-tab="chapter"><span class="course-content-tab__icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('layout'); ?></span><span class="course-content-tab__label">Chapitres</span><span class="course-content-tab__count"><?php echo e((string) $pedagogicalChapterCount); ?></span></button>

        <button type="button" class="tab-button course-content-tab" data-tab-button data-tab="lesson"><span class="course-content-tab__icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('book-open'); ?></span><span class="course-content-tab__label">Le&ccedil;ons</span><span class="course-content-tab__count"><?php echo e((string) $lessonCount); ?></span></button>

        <button type="button" class="tab-button course-content-tab" data-tab-button data-tab="doc"><span class="course-content-tab__icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('upload'); ?></span><span class="course-content-tab__label">Ressources</span><span class="course-content-tab__count"><?php echo e((string) $resourceCount); ?></span></button>

        <button type="button" class="tab-button course-content-tab" data-tab-button data-tab="meeting"><span class="course-content-tab__icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('calendar'); ?></span><span class="course-content-tab__label">Rendez-vous</span><span class="course-content-tab__count"><?php echo e((string) $meetingCount); ?></span></button>

        <button type="button" class="tab-button course-content-tab" data-tab-button data-tab="exam"><span class="course-content-tab__icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('layers'); ?></span><span class="course-content-tab__label">Examen</span><span class="course-content-tab__count"><?php echo $hasAdvancedExam ? '1' : '0'; ?></span></button>

    </div>



    <div id="tab-chapter" data-tab-panel="chapter" class="course-content-panel space-y-4">

        <div class="course-content-panel__intro">

            <span class="course-content-icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('layout'); ?></span>

            <div>

                <p class="course-content-panel__title">Chapitres</p>

                <p class="course-content-panel__subtitle">D&eacute;finissez la structure principale du cours avant d'ajouter le reste du contenu.</p>

            </div>

        </div>

        <form method="post" class="course-content-form grid gap-3 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] xl:items-end" id="form-add-chapter" data-autosave-form="chapter">

                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">

                    <input type="hidden" name="action" value="add_chapter">

                    <div class="md:col-span-1">

                        <label class="block text-sm text-slate-300 mb-1">Titre *</label>

                        <input name="chapter_title" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2" required>

                    </div>

                    <div class="md:col-span-1">

                        <label class="block text-sm text-slate-300 mb-1">Description</label>

                        <input name="chapter_description" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2">

                    </div>

                    <div class="xl:col-span-1 flex justify-end">

                        <button type="submit" class="course-content-action app-btn-success">Ajouter le chapitre</button>

                    </div>

                </form>

            </div>



            <div id="tab-lesson" data-tab-panel="lesson" class="course-content-panel hidden space-y-4">

                <div class="course-content-panel__intro"><span class="course-content-icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('book-open'); ?></span><div><p class="course-content-panel__title">Le&ccedil;ons</p><p class="course-content-panel__subtitle">Ajoutez la progression vid&eacute;o et l'ordre p&eacute;dagogique de chaque chapitre.</p></div></div><form method="post" class="course-content-form space-y-3">

                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">

                    <input type="hidden" name="action" value="add_lesson">

                    <div class="grid gap-3 md:grid-cols-2">

                        <div>

                            <label class="block text-sm text-slate-300 mb-1">Chapitre *</label>

                            <select name="lesson_chapter_id" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2" required>

                                <option value="">Choisir</option>

                                <?php foreach ($chapters as $ch): ?>

                                    <?php if ($ch['title'] !== 'Examens'): ?>

                                        <option value="<?php echo e($ch['id']); ?>"><?php echo e($ch['position']); ?>. <?php echo e($ch['title']); ?></option>

                                    <?php endif; ?>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div>

                            <label class="block text-sm text-slate-300 mb-1">Position (auto si vide)</label>

                            <input name="lesson_position" type="number" min="1" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2" placeholder="Auto">

                        </div>

                    </div>

                    <div class="grid gap-3 md:grid-cols-2">

                        <div>

                            <label class="block text-sm text-slate-300 mb-1">Titre *</label>

                            <input name="lesson_title" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2" required>

                        </div>

                        <div>

                            <label class="block text-sm text-slate-300 mb-1">Dur&eacute;e estim&eacute;e (minutes) *</label>

                            <input name="lesson_duration" type="number" min="1" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2" required>

                        </div>

                    </div>

                    <div>

                        <label class="block text-sm text-slate-300 mb-1">Description</label>

                        <textarea name="lesson_description" rows="3" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2"></textarea>

                    </div>

                    <div>

                        <label class="block text-sm text-slate-300 mb-1">Lien YouTube *</label>

                        <input name="youtube_url" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2" placeholder="https://www.youtube.com/..." required>

                    </div>

                    <div class="flex justify-end">

                        <button type="submit" class="course-content-action app-btn-success">Ajouter la leçon</button>

                    </div>

                </form>

            </div>



            <div id="tab-doc" data-tab-panel="doc" class="course-content-panel hidden space-y-4">

                <div class="course-content-panel__intro"><span class="course-content-icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('upload'); ?></span><div><p class="course-content-panel__title">Ressources</p><p class="course-content-panel__subtitle">Rattachez des supports Drive &agrave; un chapitre ou &agrave; une le&ccedil;on.</p></div></div><form method="post" class="course-content-form space-y-3" enctype="multipart/form-data">

                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">

                    <input type="hidden" name="action" value="add_doc">

                    <div class="grid gap-3 md:grid-cols-3">

                        <div>

                            <label class="block text-sm text-slate-300 mb-1">Type *</label>

                            <select name="doc_type" id="doc_type" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2" required>

                                <option value="document">Document d'accompagnement</option>

                                <option value="file">Fichier</option>

                                <option value="exercise">Exercice</option>

                            </select>

                        </div>

                        <div>

                            <label class="block text-sm text-slate-300 mb-1">Associer à *</label>

                            <select name="doc_scope" id="doc_scope" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2" required>

                                <option value="chapter">Chapitre</option>

                                <option value="lesson">Leçon</option>

                            </select>

                        </div>

                        <div id="doc_chapter_wrapper">

                            <label class="block text-sm text-slate-300 mb-1">Chapitre *</label>

                            <select name="doc_chapter_id" id="doc_chapter_id" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2" required>

                                <option value="">Choisir</option>

                                <?php foreach ($chapters as $ch): ?>

                                    <?php if ($ch['title'] !== 'Examens'): ?>

                                        <option value="<?php echo e($ch['id']); ?>"><?php echo e($ch['position']); ?>. <?php echo e($ch['title']); ?></option>

                                    <?php endif; ?>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div id="doc_lesson_wrapper" class="hidden">

                            <label class="block text-sm text-slate-300 mb-1">Leçon *</label>

                            <select name="doc_lesson_id" id="doc_lesson_id" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2">

                                <option value="">Choisir</option>

                                <?php foreach ($lessonsList as $ls): ?>

                                    <?php if (($ls['chapter_title'] ?? '') !== 'Examens') : ?>

                                        <option value="<?php echo e($ls['id']); ?>" data-chapter-id="<?php echo e($ls['chapter_id']); ?>"><?php echo e($ls['chap_pos']); ?>. <?php echo e($ls['chapter_title']); ?> - <?php echo e($ls['position']); ?>. <?php echo e($ls['title']); ?></option>

                                    <?php endif; ?>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div>

                            <label class="block text-sm text-slate-300 mb-1">Titre *</label>

                            <input name="doc_title" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2" required>

                        </div>

                    </div>

                    <div>

                        <label class="block text-sm text-slate-300 mb-1">Fichier (Drive) *</label>

                        <input type="file" name="doc_file" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2 file:mr-3 file:py-2 file:px-3 file:border-0 file:text-sm file:bg-slate-700 file:text-slate-200" required>

                        <p class="text-xs text-slate-400 mt-1">

                            Taille max: <?php echo e((string) $driveMaxMb); ?> Mo.

                            <?php echo e($driveUploadNote !== '' ? $driveUploadNote : "Google Drive n'est pas configure."); ?>

                            <?php if ($driveConnectRecommended): ?>

                                <a class="underline ml-1" href="<?php echo e(site_url('drive/oauth_connect')); ?>">Connecter Drive</a>

                            <?php endif; ?>

                        </p>

                    </div>

                    <div class="flex justify-end">

                        <button type="submit" class="course-content-action app-btn-success">Ajouter</button>

                    </div>

                </form>

            </div>



            <div id="tab-meeting" data-tab-panel="meeting" class="course-content-panel hidden space-y-4">

                <div class="course-content-panel__intro"><span class="course-content-icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('calendar'); ?></span><div><p class="course-content-panel__title">Rendez-vous</p><p class="course-content-panel__subtitle">Programmez les sessions d'accompagnement li&eacute;es au cours.</p></div></div><form method="post" class="course-content-form space-y-3">

                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">

                    <input type="hidden" name="action" value="add_meeting">

                    <div class="grid gap-3 md:grid-cols-4">

                        <div>

                            <label class="block text-sm text-slate-300 mb-1">Titre *</label>

                            <input name="meeting_title" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2" required>

                        </div>

                        <div>

                            <label class="block text-sm text-slate-300 mb-1">Mode *</label>

                            <select name="meeting_mode" id="meeting_mode" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2">

                                <option value="online">En ligne</option>

                                <option value="onsite">Présentiel</option>

                            </select>

                        </div>

                        <div>

                            <label class="block text-sm text-slate-300 mb-1">Dur&eacute;e (minutes) *</label>

                            <input name="meeting_duration_min" type="number" min="1" value="60" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2" required>

                        </div>

                        <div>

                            <label class="block text-sm text-slate-300 mb-1">Chapitre *</label>

                            <select name="meeting_chapter_id" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2" required>

                                <?php foreach ($contentChaptersList as $ch): ?>

                                    <option value="<?php echo e($ch['id']); ?>" <?php echo (int) ($ch['id'] ?? 0) === $firstContentChapterId ? 'selected' : ''; ?>><?php echo e($ch['position']); ?>. <?php echo e($ch['title']); ?></option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                    </div>

                    <p class="text-xs text-slate-400">L'étudiant proposera 3 créneaux (date/heure) et la fin sera calculée selon la durée.</p>

                    <div class="flex justify-end">

                        <button type="submit" class="course-content-action app-btn-success">Ajouter le rendez-vous</button>

                    </div>

                </form>

                <?php if ($meetings): ?>

                    <div class="space-y-2">

                        <?php foreach ($meetings as $m): ?>

                            <div class="rounded-lg border border-slate-800 bg-slate-900 px-3 py-2 text-sm flex flex-col md:flex-row md:items-center md:justify-between gap-2">

                                <div>

                                    <p class="font-semibold text-slate-100"><?php echo e($m['title']); ?> (<?php echo e($m['mode'] === 'online' ? 'En ligne' : 'Pr&eacute;sentiel'); ?><?php if (!empty($m['duration_min'])): ?> &middot; <?php echo e(format_duration_minutes_hm((int) $m['duration_min'])); ?><?php else: ?> &middot; Dur&eacute;e &agrave; d&eacute;finir<?php endif; ?>)</p>

                                    <p class="text-slate-300 text-xs">Chapitre : <?php echo e($m['chapter_title'] ?? ''); ?></p>

                                </div>

                                <?php if (!$isLocked): ?>

                                    <div class="flex flex-wrap items-center justify-end gap-2">

                                        <details class="group">

                                            <summary class="cursor-pointer text-xs px-3 py-1 rounded-lg border border-slate-700 hover:border-slate-500 select-none">Modifier</summary>

                                            <form method="post" class="mt-2 grid gap-2 min-w-[280px]">

                                                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">

                                                <input type="hidden" name="action" value="update_meeting">

                                                <input type="hidden" name="meeting_id" value="<?php echo e($m['id']); ?>">

                                                <div class="grid gap-2 md:grid-cols-4">

                                                    <div class="md:col-span-4">

                                                        <label class="block text-xs text-slate-300 mb-1">Titre *</label>

                                                        <input name="meeting_title" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2" required value="<?php echo e($m['title']); ?>">

                                                    </div>

                                                    <div>

                                                        <label class="block text-xs text-slate-300 mb-1">Mode *</label>

                                                        <select name="meeting_mode" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2">

                                                            <option value="online" <?php echo ($m['mode'] ?? '') === 'online' ? 'selected' : ''; ?>>En ligne</option>

                                                            <option value="onsite" <?php echo ($m['mode'] ?? '') === 'onsite' ? 'selected' : ''; ?>>Présentiel</option>

                                                        </select>

                                                    </div>

                                                    <div>

                                                        <label class="block text-xs text-slate-300 mb-1">Dur&eacute;e (minutes) *</label>

                                                        <input name="meeting_duration_min" type="number" min="1" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2" required value="<?php echo e((int)($m['duration_min'] ?? 60)); ?>">

                                                    </div>

                                                    <div class="md:col-span-2">

                                                        <label class="block text-xs text-slate-300 mb-1">Chapitre *</label>

                                                        <select name="meeting_chapter_id" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2" required>

                                                            <?php foreach ($contentChaptersList as $ch): ?>

                                                                <option value="<?php echo e($ch['id']); ?>" <?php echo (string)($m['chapter_id'] ?? '') === (string)$ch['id'] ? 'selected' : ''; ?>><?php echo e($ch['position']); ?>. <?php echo e($ch['title']); ?></option>

                                                            <?php endforeach; ?>

                                                        </select>

                                                    </div>

                                                </div>

                                                <div class="flex justify-end">

                                                    <button type="submit" class="course-content-action app-btn-success">Enregistrer</button>

                                                </div>

                                            </form>

                                        </details>

                                        <form method="post" class="inline-block" onsubmit="return confirm('Supprimer ce rendez-vous ');">

                                            <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">

                                            <input type="hidden" name="action" value="delete_meeting">

                                            <input type="hidden" name="meeting_id" value="<?php echo e($m['id']); ?>">

                                            <button type="submit" class="course-content-action app-btn-danger">Supprimer</button>

                                        </form>

                                    </div>

                                <?php endif; ?>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </div>



            <div id="tab-exam" data-tab-panel="exam" class="course-content-panel hidden space-y-4">

                <div class="course-content-panel__intro"><span class="course-content-icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('layers'); ?></span><div><p class="course-content-panel__title">Examen</p><p class="course-content-panel__subtitle">G&eacute;rez l'&eacute;valuation finale et ses tentatives.</p></div></div><div class="rounded-lg border border-slate-800 bg-slate-950 p-4 flex flex-col gap-2">

                    <p class="text-sm text-slate-300">Examen avancé : QCM, remise sur Drive ou rendez-vous oral.</p>

                    <?php if ($advExam): ?>

                        <div class="rounded-lg border border-slate-800 bg-slate-900 px-3 py-2">

                            <div class="flex flex-wrap items-start justify-between gap-2">

                                <div>

                                    <p class="font-semibold text-slate-100"><?php echo e($advExam['title']); ?></p>

                                    <p class="text-xs text-slate-400">

                                        Type :

                                        <?php echo e(($advExam['type'] ?? '') === 'qcm' ? 'QCM' : (($advExam['type'] ?? '') === 'drive' ? 'Google Drive' : 'Rendez-vous')); ?>

                                        <?php if (!empty($advExam['duration_min'])): ?>

                                            &middot; <?php echo e(format_duration_minutes_hm((int) $advExam['duration_min'])); ?>

                                        <?php endif; ?>

                                        &middot; Tentatives : <?php echo e((int) ($advExam['max_attempts'] ?? 1)); ?>

                                        &middot; Note min : <?php echo e((int) ($advExam['min_score'] ?? 0)); ?>

                                    </p>

                                </div>

                                <div class="flex flex-wrap items-center justify-end gap-2">

                                    <a href="<?php echo e(site_url('teacher/exams_manage?exam_id=' . (int) $advExam['id'])); ?>" class="course-content-action app-btn-soft">G&eacute;rer les tentatives</a>

                                    <a href="<?php echo e(site_url('teacher/exams_create?course_id=' . (int) $courseId . '&return=edit')); ?>" class="course-content-action app-btn-soft <?php echo $isLocked ? 'opacity-60 pointer-events-none' : ''; ?>" <?php echo $isLocked ? 'aria-disabled="true"' : ''; ?>>Modifier l'examen</a>

                                </div>

                            </div>

                            <?php if (!empty($advExam['description'])): ?>

                                <p class="mt-2 text-sm text-slate-300"><?php echo e($advExam['description']); ?></p>

                            <?php endif; ?>

                        </div>

                    <?php else: ?>

                        <div class="flex flex-wrap items-center justify-between gap-2">

                            <p class="text-sm text-slate-400">Aucun examen n’est encore configuré pour ce cours.</p>

                            <a href="<?php echo e(site_url('teacher/exams_create?course_id=' . (int) $courseId . '&return=edit')); ?>" class="course-content-action app-btn-success <?php echo $isLocked ? 'opacity-60 pointer-events-none' : ''; ?>" <?php echo $isLocked ? 'aria-disabled="true"' : ''; ?>>+ Cr&eacute;er un examen</a>

                        </div>

                    <?php endif; ?>

                    <?php if ($isLocked): ?>

                        <p class="text-xs text-amber-300">Cours verrouillé : la modification des examens est temporairement désactivée.</p>

                    <?php endif; ?>

                </div>

            </div>

        </section>

        <section class="course-content-card course-content-plan lg:col-span-2">

    <div class="course-content-card__header">

        <div class="course-content-plan__title">

            <span class="course-content-icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('layout'); ?></span>

            <div>

                <h2 class="course-content-card__title">Plan du cours</h2>

                <p class="course-content-card__subtitle">Contr&ocirc;lez la hi&eacute;rarchie et modifiez chaque &eacute;l&eacute;ment.</p>

            </div>

        </div>

        <div class="course-content-plan__actions">

            <span class="course-content-pill course-content-pill--info"><?php echo e((string) $pedagogicalChapterCount); ?> chapitre(s) - <?php echo e((string) $lessonCount); ?> le&ccedil;on(s) - <?php echo e((string) $resourceCount); ?> ressource(s)</span>

        </div>

    </div><?php if (!$chapters): ?>

                <p class="text-sm text-slate-400">Aucun chapitre n’est encore créé.</p>

            <?php else: ?>

                <div class="space-y-3">

                    <?php foreach ($chapters as $ch): ?>

                        <div class="rounded-xl border border-slate-800 bg-slate-950 p-4 space-y-2">

                            <div class="flex items-start justify-between gap-2">

                                <div>

                                    <p class="font-semibold"><?php echo e($ch['position']); ?>. <?php echo e($ch['title']); ?></p>

                                    <?php if ($ch['description']): ?>

                                        <p class="text-sm text-slate-400"><?php echo e($ch['description']); ?></p>

                                    <?php endif; ?>

                                </div>

                                <?php if ($ch['title'] === 'Examens' && !empty($advExam)): ?>

                                    <div class="flex flex-wrap items-center justify-end gap-2">

                                        <a href="<?php echo e(site_url('teacher/exam_preview?exam_id=' . (int) $advExam['id'])); ?>" class="course-content-action app-btn-soft">Voir l’examen</a>

                                    </div>

                                <?php elseif (!$isLocked && !$isSystemChapterTitle($ch['title'])): ?>

                                    <div class="flex flex-wrap items-center justify-end gap-2">

                                        <button

                                            type="button"

                                            class="course-content-action app-btn-soft"

                                            data-open-edit="chapter"

                                            data-chapter-id="<?php echo e($ch['id']); ?>"

                                            data-chapter-title="<?php echo e($ch['title']); ?>"

                                            data-chapter-position="<?php echo e($ch['position']); ?>"

                                            data-chapter-description="<?php echo e((string) ($ch['description'] ?? '')); ?>"

                                        >Modifier</button>

                                        <form method="post" class="inline-block" onsubmit="return confirm('Supprimer ce chapitre  Les leçons, ressources et rendez-vous associés seront aussi supprimés.');">

                                            <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">

                                            <input type="hidden" name="action" value="delete_chapter">

                                            <input type="hidden" name="chapter_id" value="<?php echo e($ch['id']); ?>">

                                            <button type="submit" class="course-content-action app-btn-danger">Supprimer</button>

                                        </form>

                                    </div>

                                <?php endif; ?>

                            </div>



                            <?php if (!empty($lessonsByChapter[$ch['id']])): ?>

                                <div class="space-y-2">

                                    <?php foreach ($lessonsByChapter[$ch['id']] as $lesson): ?>

                                        <div class="rounded-lg bg-slate-900 border border-slate-800 px-3 py-2">

                                            <div class="flex items-start justify-between gap-2">

                                                <div>

                                                    <p class="font-medium text-sm"><?php echo e($lesson['position']); ?>. <?php echo e($lesson['title']); ?> <span class="text-xs text-slate-400">(Video)</span></p>

                                                    <?php if ($lesson['description']): ?>

                                                        <p class="text-xs text-slate-400"><?php echo e($lesson['description']); ?></p>

                                                    <?php endif; ?>

                                                </div>

                                                <?php if (!$isLocked && !$isSystemChapterTitle($ch['title'])): ?>

                                                    <div class="flex flex-wrap items-center justify-end gap-2">

                                                        <button

                                                            type="button"

                                                            class="course-content-action app-btn-soft"

                                                            data-open-edit="lesson"

                                                            data-lesson-id="<?php echo e($lesson['id']); ?>"

                                                            data-lesson-title="<?php echo e($lesson['title']); ?>"

                                                            data-lesson-position="<?php echo e($lesson['position']); ?>"

                                                            data-lesson-description="<?php echo e((string) ($lesson['description'] ?? '')); ?>"

                                                            data-lesson-duration="<?php echo e((string) ($lesson['duration_estimate_min'] ?? '')); ?>"

                                                            data-lesson-youtube-url="<?php echo e((string) ($lesson['youtube_url'] ?? '')); ?>"

                                                        >Modifier</button>

                                                        <form method="post" class="inline-block" onsubmit="return confirm('Supprimer cette leçon  Les ressources liées à cette leçon seront aussi supprimées.');">

                                                            <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">

                                                            <input type="hidden" name="action" value="delete_lesson">

                                                            <input type="hidden" name="lesson_id" value="<?php echo e($lesson['id']); ?>">

                                                            <button type="submit" class="course-content-action app-btn-danger">Supprimer</button>

                                                        </form>

                                                    </div>

                                                <?php endif; ?>

                                            </div>

                                        </div>

                                        <?php if (!empty($docsByLesson[$lesson['id']])): ?>

                                            <div class="ml-4 space-y-1">

                                                <?php foreach ($docsByLesson[$lesson['id']] as $doc): ?>

                                                    <div class="rounded-lg bg-slate-950 border border-slate-800 px-3 py-2">

                                                        <div class="flex items-start justify-between gap-2">

                                                            <div>

                                                                <p class="text-sm"><?php echo e($doc['position']); ?>. <?php echo e($doc['title']); ?> <span class="text-xs text-slate-400">(<?php echo e($docLabel($doc['type'] ?? null, $doc['mime'] ?? null)); ?>)</span></p>

                                                            </div>

                                                            <?php if (!$isLocked && !$isSystemChapterTitle($ch['title']) && ($doc['type'] ?? '') !== 'exam') : ?>

                                                                <?php

                                                                $docLessonId = $docLessonIdFromMime($doc['mime'] ?? null);

                                                                $docScope = $docLessonId > 0 ? 'lesson' : 'chapter';

                                                                $docFormType = 'document';

                                                                if (($doc['type'] ?? '') === 'exercise') {

                                                                    $docFormType = 'exercise';

                                                                } elseif (($doc['type'] ?? '') === 'resource') {

                                                                    $docFormType = (is_string($doc['mime'] ?? null) && (stripos((string)$doc['mime'], '|doc :file') !== false || stripos((string)$doc['mime'], 'url_file') !== false)) ? 'file' : 'document';

                                                                }

                                                                ?>

                                                                <div class="flex flex-wrap items-center justify-end gap-2">

                                                                    <button

                                                                        type="button"

                                                                        class="course-content-action app-btn-soft"

                                                                        data-open-edit="doc"

                                                                        data-doc-id="<?php echo e($doc['id']); ?>"

                                                                        data-doc-title="<?php echo e($doc['title']); ?>"

                                                                        data-doc-type="<?php echo e($docFormType); ?>"

                                                                        data-doc-scope="<?php echo e($docScope); ?>"

                                                                        data-doc-chapter-id="<?php echo e($doc['chapter_id']); ?>"

                                                                        data-doc-lesson-id="<?php echo e((string) $docLessonId); ?>"

                                                                        data-doc-position="<?php echo e($doc['position']); ?>"

                                                                        data-doc-file-path="<?php echo e((string) ($doc['file_path'] ?? '')); ?>"

                                                                    >Modifier</button>

                                                                    <details class="hidden" hidden>

                                                                        <summary class="cursor-pointer text-xs px-3 py-1 rounded-lg border border-slate-700 hover:border-slate-500 select-none">Modifier</summary>

                                                                        <form method="post" class="mt-2 grid gap-2 min-w-[320px]" enctype="multipart/form-data">

                                                                            <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">

                                                                            <input type="hidden" name="action" value="update_doc">

                                                                            <input type="hidden" name="doc_id" value="<?php echo e($doc['id']); ?>">

                                                                            <div class="grid gap-2 md:grid-cols-3">

                                                                                <div>

                                                                                    <label class="block text-xs text-slate-300 mb-1">Type *</label>

                                                                                    <select name="doc_type" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2" required>

                                                                                        <option value="document" <?php echo $docFormType === 'document' ? 'selected' : ''; ?>>Document d'accompagnement</option>

                                                                                        <option value="file" <?php echo $docFormType === 'file' ? 'selected' : ''; ?>>Fichier</option>

                                                                                        <option value="exercise" <?php echo $docFormType === 'exercise' ? 'selected' : ''; ?>>Exercice</option>

                                                                                    </select>

                                                                                </div>

                                                                                <div>

                                                                                    <label class="block text-xs text-slate-300 mb-1">Associer à *</label>

                                                                                    <select name="doc_scope" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2" required>

                                                                                        <option value="chapter" <?php echo $docScope === 'chapter' ? 'selected' : ''; ?>>Chapitre</option>

                                                                                        <option value="lesson" <?php echo $docScope === 'lesson' ? 'selected' : ''; ?>>Leçon</option>

                                                                                    </select>

                                                                                </div>

                                                                                <div>

                                                                                    <label class="block text-xs text-slate-300 mb-1">Chapitre *</label>

                                                                                    <select name="doc_chapter_id" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2" required>

                                                                                        <option value="">Choisir</option>

                                                                                        <?php foreach ($chapters as $chOpt): ?>

                                                                                            <?php if ($chOpt['title'] !== 'Examens'): ?>

                                                                                                <option value="<?php echo e($chOpt['id']); ?>" <?php echo (string)$doc['chapter_id'] === (string)$chOpt['id'] ? 'selected' : ''; ?>?><?php echo e($chOpt['position']); ?>. <?php echo e($chOpt['title']); ?></option>

                                                                                            <?php endif; ?>

                                                                                        <?php endforeach; ?>

                                                                                    </select>

                                                                                </div>

                                                                                <div>

                                                                                    <label class="block text-xs text-slate-300 mb-1">Position</label>

                                                                                    <input name="doc_position" type="number" min="1" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2" value="<?php echo e($doc['position']); ?>">

                                                                                </div>

                                                                                <div class="md:col-span-3">

                                                                                    <label class="block text-xs text-slate-300 mb-1">Leçon (si portée Leçon)</label>

                                                                                    <select name="doc_lesson_id" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2">

                                                                                        <option value="">Choisir</option>

                                                                                        <?php foreach ($lessonsList as $ls): ?>

                                                                                            <?php if (($ls['chapter_title'] ?? '') !== 'Examens') : ?>

                                                                                                <option value="<?php echo e($ls['id']); ?>" <?php echo (string)$docLessonId === (string)$ls['id'] ? 'selected' : ''; ?>?><?php echo e($ls['chap_pos']); ?>. <?php echo e($ls['chapter_title']); ?> - <?php echo e($ls['position']); ?>. <?php echo e($ls['title']); ?></option>

                                                                                            <?php endif; ?>

                                                                                        <?php endforeach; ?>

                                                                                    </select>

                                                                                </div>

                                                                                <div class="md:col-span-3">

                                                                                    <label class="block text-xs text-slate-300 mb-1">Titre *</label>

                                                                                    <input name="doc_title" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2" required value="<?php echo e($doc['title']); ?>">

                                                                                </div>

                                                                            </div>

                                                                            <div>

                                                                                <label class="block text-xs text-slate-300 mb-1">Fichier (Drive)</label>

                                                                                <input type="file" name="doc_file" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2 file:mr-3 file:py-1 file:px-2 file:border-0 file:text-xs file:bg-slate-700 file:text-slate-200">

                                                                                <p class="text-xs text-slate-400 mt-1">

                                                                                    Laisser vide pour conserver.

                                                                                    <?php if ($driveUploadNote !== ''): ?> <?php echo e($driveUploadNote); ?><?php endif; ?>

                                                                                    <?php if ($driveConnectRecommended): ?>

                                                                                        <a class="underline ml-1" href="<?php echo e(site_url('drive/oauth_connect')); ?>">Connecter Drive</a>

                                                                                    <?php endif; ?>

                                                                                </p>

                                                                                <?php if (!empty($doc['file_path'])): ?>

                                                                                    <a class="text-xs text-emerald-300 underline" href="<?php echo e($doc['file_path']); ?>" target="_blank" rel="noopener">Fichier actuel</a>

                                                                                <?php endif; ?>

                                                                            </div>

                                                                            <div class="flex justify-end">

                                                                                <button type="submit" class="course-content-action app-btn-success">Enregistrer</button>

                                                                            </div>

                                                                        </form>

                                                                    </details>

                                                                    <form method="post" class="inline-block" onsubmit="return confirm('Supprimer cette ressource ');">

                                                                        <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">

                                                                        <input type="hidden" name="action" value="delete_doc">

                                                                        <input type="hidden" name="doc_id" value="<?php echo e($doc['id']); ?>">

                                                                        <button type="submit" class="course-content-action app-btn-danger">Supprimer</button>

                                                                    </form>

                                                                </div>

                                                            <?php endif; ?>

                                                        </div>

                                                    </div>

                                                <?php endforeach; ?>

                                            </div>

                                        <?php endif; ?>

                                    <?php endforeach; ?>

                                </div>

                            <?php else: ?>

                                <p class="text-xs text-slate-500">Aucune leçon.</p>

                            <?php endif; ?>



                            <?php if (!empty($docsByChapter[$ch['id']])): ?>

                                <div class="space-y-2">

                                    <?php foreach ($docsByChapter[$ch['id']] as $doc): ?>

                                        <div class="rounded-lg bg-slate-900 border border-slate-800 px-3 py-2">

                                            <div class="flex items-start justify-between gap-2">

                                                <div>

                                                    <p class="font-medium text-sm"><?php echo e($doc['position']); ?>. <?php echo e($doc['title']); ?> <span class="text-xs text-slate-400">(<?php echo e($docLabel($doc['type'] ?? null, $doc['mime'] ?? null)); ?>)</span></p>

                                                </div>

                                                <?php if (!$isLocked && !$isSystemChapterTitle($ch['title']) && ($doc['type'] ?? '') !== 'exam') : ?>

                                                    <?php

                                                    $docLessonId = $docLessonIdFromMime($doc['mime'] ?? null);

                                                    $docScope = $docLessonId > 0 ? 'lesson' : 'chapter';

                                                    $docFormType = 'document';

                                                    if (($doc['type'] ?? '') === 'exercise') {

                                                        $docFormType = 'exercise';

                                                    } elseif (($doc['type'] ?? '') === 'resource') {

                                                        $docFormType = (is_string($doc['mime'] ?? null) && (stripos((string)$doc['mime'], '|doc :file') !== false || stripos((string)$doc['mime'], 'url_file') !== false)) ? 'file' : 'document';

                                                    }

                                                    ?>

                                                    <div class="flex flex-wrap items-center justify-end gap-2">

                                                        <button

                                                            type="button"

                                                            class="course-content-action app-btn-soft"

                                                            data-open-edit="doc"

                                                            data-doc-id="<?php echo e($doc['id']); ?>"

                                                            data-doc-title="<?php echo e($doc['title']); ?>"

                                                            data-doc-type="<?php echo e($docFormType); ?>"

                                                            data-doc-scope="<?php echo e($docScope); ?>"

                                                            data-doc-chapter-id="<?php echo e($doc['chapter_id']); ?>"

                                                            data-doc-lesson-id="<?php echo e((string) $docLessonId); ?>"

                                                            data-doc-position="<?php echo e($doc['position']); ?>"

                                                            data-doc-file-path="<?php echo e((string) ($doc['file_path'] ?? '')); ?>"

                                                        >Modifier</button>

                                                        <details class="hidden" hidden>

                                                            <summary class="cursor-pointer text-xs px-3 py-1 rounded-lg border border-slate-700 hover:border-slate-500 select-none">Modifier</summary>

                                                            <form method="post" class="mt-2 grid gap-2 min-w-[320px]" enctype="multipart/form-data">

                                                                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">

                                                                <input type="hidden" name="action" value="update_doc">

                                                                <input type="hidden" name="doc_id" value="<?php echo e($doc['id']); ?>">

                                                                <div class="grid gap-2 md:grid-cols-3">

                                                                    <div>

                                                                        <label class="block text-xs text-slate-300 mb-1">Type *</label>

                                                                        <select name="doc_type" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2" required>

                                                                            <option value="document" <?php echo $docFormType === 'document' ? 'selected' : ''; ?>>Document d'accompagnement</option>

                                                                            <option value="file" <?php echo $docFormType === 'file' ? 'selected' : ''; ?>>Fichier</option>

                                                                            <option value="exercise" <?php echo $docFormType === 'exercise' ? 'selected' : ''; ?>>Exercice</option>

                                                                        </select>

                                                                    </div>

                                                                    <div>

                                                                        <label class="block text-xs text-slate-300 mb-1">Associer à *</label>

                                                                        <select name="doc_scope" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2" required>

                                                                            <option value="chapter" <?php echo $docScope === 'chapter' ? 'selected' : ''; ?>>Chapitre</option>

                                                                            <option value="lesson" <?php echo $docScope === 'lesson' ? 'selected' : ''; ?>>Leçon</option>

                                                                        </select>

                                                                    </div>

                                                                    <div>

                                                                        <label class="block text-xs text-slate-300 mb-1">Chapitre *</label>

                                                                        <select name="doc_chapter_id" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2" required>

                                                                            <option value="">Choisir</option>

                                                                            <?php foreach ($chapters as $chOpt): ?>

                                                                                <?php if ($chOpt['title'] !== 'Examens'): ?>

                                                                                    <option value="<?php echo e($chOpt['id']); ?>" <?php echo (string)$doc['chapter_id'] === (string)$chOpt['id'] ? 'selected' : ''; ?>?><?php echo e($chOpt['position']); ?>. <?php echo e($chOpt['title']); ?></option>

                                                                                <?php endif; ?>

                                                                            <?php endforeach; ?>

                                                                        </select>

                                                                    </div>

                                                                    <div>

                                                                        <label class="block text-xs text-slate-300 mb-1">Position</label>

                                                                        <input name="doc_position" type="number" min="1" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2" value="<?php echo e($doc['position']); ?>">

                                                                    </div>

                                                                    <div class="md:col-span-3">

                                                                        <label class="block text-xs text-slate-300 mb-1">Leçon (si portée Leçon)</label>

                                                                        <select name="doc_lesson_id" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2">

                                                                            <option value="">Choisir</option>

                                                                            <?php foreach ($lessonsList as $ls): ?>

                                                                                <?php if (($ls['chapter_title'] ?? '') !== 'Examens') : ?>

                                                                                    <option value="<?php echo e($ls['id']); ?>" <?php echo (string)$docLessonId === (string)$ls['id'] ? 'selected' : ''; ?>?><?php echo e($ls['chap_pos']); ?>. <?php echo e($ls['chapter_title']); ?> - <?php echo e($ls['position']); ?>. <?php echo e($ls['title']); ?></option>

                                                                                <?php endif; ?>

                                                                            <?php endforeach; ?>

                                                                        </select>

                                                                    </div>

                                                                    <div class="md:col-span-3">

                                                                        <label class="block text-xs text-slate-300 mb-1">Titre *</label>

                                                                        <input name="doc_title" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2" required value="<?php echo e($doc['title']); ?>">

                                                                    </div>

                                                                </div>

                                                                <div>

                                                                    <label class="block text-xs text-slate-300 mb-1">Fichier (Drive)</label>

                                                                    <input type="file" name="doc_file" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2 file:mr-3 file:py-1 file:px-2 file:border-0 file:text-xs file:bg-slate-700 file:text-slate-200">

                                                                    <p class="text-xs text-slate-400 mt-1">

                                                                        Laisser vide pour conserver.

                                                                        <?php if ($driveUploadNote !== ''): ?> <?php echo e($driveUploadNote); ?><?php endif; ?>

                                                                        <?php if ($driveConnectRecommended): ?>

                                                                            <a class="underline ml-1" href="<?php echo e(site_url('drive/oauth_connect')); ?>">Connecter Drive</a>

                                                                        <?php endif; ?>

                                                                    </p>

                                                                    <?php if (!empty($doc['file_path'])): ?>

                                                                        <a class="text-xs text-emerald-300 underline" href="<?php echo e($doc['file_path']); ?>" target="_blank" rel="noopener">Fichier actuel</a>

                                                                    <?php endif; ?>

                                                                </div>

                                                                <div class="flex justify-end">

                                                                    <button type="submit" class="course-content-action app-btn-success">Enregistrer</button>

                                                                </div>

                                                            </form>

                                                        </details>

                                                        <form method="post" class="inline-block" onsubmit="return confirm('Supprimer cette ressource ');">

                                                            <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">

                                                            <input type="hidden" name="action" value="delete_doc">

                                                            <input type="hidden" name="doc_id" value="<?php echo e($doc['id']); ?>">

                                                            <button type="submit" class="course-content-action app-btn-danger">Supprimer</button>

                                                        </form>

                                                    </div>

                                                <?php endif; ?>

                                            </div>

                                        </div>

                                    <?php endforeach; ?>

                                </div>

                            <?php else: ?>

                                <p class="text-xs text-slate-500">Aucune ressource rattachée directement au chapitre.</p>

                            <?php endif; ?>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>



                </div>



        <aside class="space-y-6 self-start lg:sticky lg:top-24">

    <?php $courseSubmitReady = $courseIdentityReady && $coursePedagogyReady && $courseAssetsReady && !$isLocked; ?>

    <section class="course-content-card course-content-actions">

        <div class="course-content-card__header">

            <div>

                <h2 class="course-content-card__title">Actions du cours</h2>

                <p class="course-content-card__subtitle">Mettez &agrave; jour, pr&eacute;visualisez et soumettez sans quitter cette page.</p>

            </div>

            <span class="course-content-pill course-content-pill--info">Statut : <?php echo e($courseStatusLabel); ?></span>

        </div>

        <div class="course-content-action-grid">

            <button type="submit" form="course-info-form" class="course-content-action app-btn-success">

                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('save'); ?></span>

                Enregistrer les modifications

            </button>

            <a href="<?php echo e(site_url('teacher/course_preview?id=' . $courseId)); ?>" class="course-content-action app-btn-soft">

                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('play-circle'); ?></span>

                Aper&ccedil;u

            </a>

            <?php if (in_array($courseStatus, ['draft', 'rejected'], true)): ?>

                <form method="post" class="course-content-action-form">

                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">

                    <input type="hidden" name="action" value="save_draft">

                    <button type="submit" class="course-content-action app-btn-soft">

                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('save'); ?></span>

                        Enregistrer brouillon

                    </button>

                </form>

                <?php if ($coursePedagogyReady): ?>
                    <form method="post" class="course-content-action-form">

                        <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">

                        <input type="hidden" name="action" value="submit_course">

                        <button type="submit" class="course-content-action app-btn-success <?php echo $courseSubmitReady ? '' : 'opacity-60 cursor-not-allowed'; ?>" <?php echo $courseSubmitReady ? '' : 'disabled'; ?> data-submit-course-btn>

                            <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('check-circle'); ?></span>

                            Soumettre

                        </button>

                    </form>
                <?php endif; ?>

            <?php endif; ?>

        </div>

    </section>



    <section class="course-content-card course-content-live-preview">

        <div class="course-content-card__header">

            <div class="course-content-preview__title">

                <span class="course-content-icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('play-circle'); ?></span>

                <div>

                    <h2 class="course-content-card__title">Aper&ccedil;u &eacute;tudiant</h2>

                    <p class="course-content-card__subtitle">Version compacte du rendu visible par l'apprenant.</p>

                </div>

            </div>

        </div>

        <div class="course-content-preview-media">

            <?php if ($courseCoverUrl !== ''): ?>

                <img id="preview-cover" class="course-content-preview-cover" src="<?php echo e($courseCoverUrl); ?>" alt="Miniature du cours">

            <?php else: ?>

                <div class="course-content-preview-placeholder">

                    <span class="course-content-icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('file-text'); ?></span>

                    <p>Miniature du cours</p>

                </div>

            <?php endif; ?>

        </div>

        <div class="course-content-preview-body">

            <div>

                <p id="preview-title" class="course-content-preview-heading"><?php echo e($title !== '' ? $title : 'Titre du cours'); ?></p>

                <p id="preview-description" class="course-content-preview-description"><?php echo e(trim((string) $description) !== '' ? $description : 'La description du cours appara&icirc;tra ici pour l\'apprenant.'); ?></p>

            </div>

            <div class="course-content-preview-stats">

                <div class="course-content-preview-pill"><span>Cat&eacute;gorie</span><strong id="preview-category"><?php echo e($categoryName); ?></strong></div>

                <div class="course-content-preview-pill"><span>Niveau</span><strong id="preview-level"><?php echo e($levelLabel); ?></strong></div>

                <div class="course-content-preview-pill"><span>Dur&eacute;e</span><strong id="preview-duration"><?php echo e(((float) $durationHours) > 0 ? format_duration_hours_hm((float) $durationHours, true) : '0 min'); ?></strong></div>

                <div class="course-content-preview-pill"><span>Tarif</span><strong id="preview-price"><?php echo trim((string) $priceXof) !== '' && (float) $priceXof > 0 ? e(number_format((float) $priceXof, 0, ',', ' ') . ' F CFA') : 'Gratuit'; ?></strong></div>

            </div>

            <div class="course-content-preview-section">

                <div class="course-content-preview-section__head">

                    <p class="course-content-preview-section__label">Objectifs cl&eacute;s</p>

                    <span class="course-content-preview-badge"><?php echo e((string) $objectiveCount); ?></span>

                </div>

                <ul id="preview-objectives" class="student-preview-list"></ul>

            </div>

            <div class="course-content-preview-section">

                <div class="course-content-preview-section__head">

                    <p class="course-content-preview-section__label">Pr&eacute;requis</p>

                    <span class="course-content-preview-badge"><?php echo e((string) $prerequisiteCount); ?></span>

                </div>

                <ul id="preview-prerequisites" class="student-preview-list"></ul>

            </div>

            <div class="course-content-preview-section">

                <div class="course-content-preview-section__head">

                    <p class="course-content-preview-section__label">&Eacute;quipement</p>

                    <span class="course-content-preview-badge"><?php echo e((string) $equipmentCount); ?></span>

                </div>

                <ul id="preview-equipment" class="student-preview-list"></ul>

            </div>

            <div class="course-content-preview-note">

                <p class="course-content-preview-section__label">Rep&egrave;res rapides</p>

                <p><?php echo e($generalResourceLabel); ?> - <?php echo e($examStatusLabel); ?></p>

            </div>

        </div>

    </section>



    <section

        class="course-content-card course-content-summary"

        data-course-summary

        data-chapter-count="<?php echo e((string) $pedagogicalChapterCount); ?>"

        data-lesson-count="<?php echo e((string) $lessonCount); ?>"

        data-meeting-count="<?php echo e((string) $meetingCount); ?>"

        data-is-locked="<?php echo $isLocked ? '1' : '0'; ?>"

    >

        <div class="course-content-card__header">

            <div>

                <h2 class="course-content-card__title">R&eacute;sum&eacute; de soumission</h2>

                <p class="course-content-card__subtitle">Suivez les rep&egrave;res essentiels avant validation.</p>

            </div>

            <span class="course-content-pill course-content-pill--info" data-summary-percent><?php echo e((string) $courseProgressPct); ?>% pr&ecirc;t</span>

        </div>

        <div class="course-content-summary__progress">

            <div class="dash-progress">

                <div class="dash-progress__fill" data-summary-progress-fill style="width: <?php echo e((string) $courseProgressPct); ?>>%;"</div>

            </div>

            <p class="course-content-progress__meta" data-summary-meta><?php echo e((string) $courseReadyCount); ?>/4 rep&egrave;res valid&eacute;s.</p>

        </div>

        <div class="course-content-checklist grid gap-3">

            <article class="course-content-check-item <?php echo $courseIdentityReady ? 'is-done' : ''; ?>" data-summary-item="identity">

                <p class="font-semibold">Identit&eacute;</p>

                <p data-summary-item-text="identity"><?php echo $courseIdentityReady ? 'Titre, cat&eacute;gorie et description pr&ecirc;ts.' : 'Compl&eacute;tez les informations de base.'; ?></p>

            </article>

            <article class="course-content-check-item <?php echo $coursePedagogyReady ? 'is-done' : ''; ?>" data-summary-item="structure">

                <p class="font-semibold">Structure</p>

                <p data-summary-item-text="structure"><?php echo e((string) $pedagogicalChapterCount); ?> chapitre(s), <?php echo e((string) $lessonCount); ?> le&ccedil;on(s), <?php echo e((string) $meetingCount); ?> rendez-vous.</p>

            </article>

            <article class="course-content-check-item <?php echo $courseAssetsReady ? 'is-done' : ''; ?>" data-summary-item="pedagogy">

                <p class="font-semibold">P&eacute;dagogie</p>

                <p data-summary-item-text="pedagogy"><?php echo e((string) $objectiveCount); ?> objectif(s), <?php echo e((string) $prerequisiteCount); ?> pr&eacute;requis, <?php echo e((string) $equipmentCount); ?> &eacute;quipement(s).</p>

            </article>

            <article class="course-content-check-item <?php echo !$isLocked ? 'is-done' : ''; ?>" data-summary-item="publication">

                <p class="font-semibold">Publication</p>

                <p data-summary-item-text="publication"><?php echo $isLocked ? 'Le cours est temporairement verrouill&eacute;.' : 'Le cours peut &ecirc;tre soumis.'; ?></p>

            </article>

        </div>

        <div class="course-content-alert course-content-alert--warning <?php echo $courseSummaryNotes === [] ? 'hidden' : ''; ?>" data-summary-notes-alert aria-hidden="<?php echo $courseSummaryNotes === [] ? 'true' : 'false'; ?>">

            <span class="course-content-alert__icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('alert-circle'); ?></span>

            <div class="course-content-alert__body">

                <p class="course-content-alert__title">Points &agrave; finaliser</p>

                <div data-summary-notes>

                    <?php foreach ($courseSummaryNotes as $note): ?>

                        <p><?php echo e($note); ?></p>

                    <?php endforeach; ?>

                </div>

            </div>

        </div>

    </section>

</aside>

    </div>

    </main>

        </div>

    </div>



    <div id="edit-chapter-modal" class="course-edit-modal fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/75 p-4" aria-hidden="true" data-course-modal inert>

        <div class="course-content-card w-full max-w-2xl rounded-3xl border border-slate-700 bg-slate-900 p-5 md:p-6">

            <div class="flex items-center justify-between gap-3">

                <div>

                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-blue-300">Chapitre</p>

                    <h3 class="mt-2 text-xl font-semibold text-white">Modifier le chapitre</h3>

                </div>

                <button type="button" data-close-modal="edit-chapter-modal" class="text-sm text-slate-400 transition hover:text-slate-200">Fermer</button>

            </div>

            <form method="post" class="mt-5 course-content-form space-y-4">

                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">

                <input type="hidden" name="action" value="update_chapter">

                <input type="hidden" name="chapter_id" id="edit_chapter_id">

                <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_140px]">

                    <div>

                        <label class="block text-sm mb-1" for="edit_chapter_title">Titre *</label>

                        <input id="edit_chapter_title" name="chapter_title" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-4 py-3" required>

                    </div>

                    <div>

                        <label class="block text-sm mb-1" for="edit_chapter_position">Position</label>

                        <input id="edit_chapter_position" name="chapter_position" type="number" min="1" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-4 py-3">

                    </div>

                </div>

                <div>

                    <label class="block text-sm mb-1" for="edit_chapter_description">Description</label>

                    <textarea id="edit_chapter_description" name="chapter_description" rows="4" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-4 py-3"></textarea>

                </div>

                <div class="flex justify-end gap-3">

                    <button type="button" data-close-modal="edit-chapter-modal" class="course-content-action app-btn-soft">Annuler</button>

                    <button type="submit" class="course-content-action app-btn-success">Enregistrer</button>

                </div>

            </form>

        </div>

    </div>



    <div id="edit-lesson-modal" class="course-edit-modal fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/75 p-4" aria-hidden="true" data-course-modal inert>

        <div class="course-content-card w-full max-w-3xl rounded-3xl border border-slate-700 bg-slate-900 p-5 md:p-6">

            <div class="flex items-center justify-between gap-3">

                <div>

                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-blue-300">Leçon</p>

                    <h3 class="mt-2 text-xl font-semibold text-white">Modifier la leçon</h3>

                </div>

                <button type="button" data-close-modal="edit-lesson-modal" class="text-sm text-slate-400 transition hover:text-slate-200">Fermer</button>

            </div>

            <form method="post" class="mt-5 course-content-form space-y-4">

                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">

                <input type="hidden" name="action" value="update_lesson">

                <input type="hidden" name="lesson_id" id="edit_lesson_id">

                <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_140px_180px]">

                    <div>

                        <label class="block text-sm mb-1" for="edit_lesson_title">Titre *</label>

                        <input id="edit_lesson_title" name="lesson_title" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-4 py-3" required>

                    </div>

                    <div>

                        <label class="block text-sm mb-1" for="edit_lesson_position">Position</label>

                        <input id="edit_lesson_position" name="lesson_position" type="number" min="1" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-4 py-3">

                    </div>

                    <div>

                        <label class="block text-sm mb-1" for="edit_lesson_duration">Durée (minutes) *</label>

                        <input id="edit_lesson_duration" name="lesson_duration" type="number" min="1" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-4 py-3" required>

                    </div>

                </div>

                <div>

                    <label class="block text-sm mb-1" for="edit_lesson_description">Description</label>

                    <textarea id="edit_lesson_description" name="lesson_description" rows="4" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-4 py-3"></textarea>

                </div>

                <div>

                    <label class="block text-sm mb-1" for="edit_lesson_youtube_url">Lien YouTube *</label>

                    <input id="edit_lesson_youtube_url" name="youtube_url" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-4 py-3" required>

                </div>

                <div class="flex justify-end gap-3">

                    <button type="button" data-close-modal="edit-lesson-modal" class="course-content-action app-btn-soft">Annuler</button>

                    <button type="submit" class="course-content-action app-btn-success">Enregistrer</button>

                </div>

            </form>

        </div>

    </div>



    <div id="edit-doc-modal" class="course-edit-modal fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/75 p-4" aria-hidden="true" data-course-modal inert>

        <div class="course-content-card w-full max-w-4xl rounded-3xl border border-slate-700 bg-slate-900 p-5 md:p-6">

            <div class="flex items-center justify-between gap-3">

                <div>

                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-blue-300">Ressource</p>

                    <h3 class="mt-2 text-xl font-semibold text-white">Modifier la ressource</h3>

                </div>

                <button type="button" data-close-modal="edit-doc-modal" class="text-sm text-slate-400 transition hover:text-slate-200">Fermer</button>

            </div>

            <form method="post" enctype="multipart/form-data" class="mt-5 course-content-form space-y-4">

                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">

                <input type="hidden" name="action" value="update_doc">

                <input type="hidden" name="doc_id" id="edit_doc_id">

                <div class="grid gap-4 md:grid-cols-4">

                    <div>

                        <label class="block text-sm mb-1" for="edit_doc_type">Type *</label>

                        <select id="edit_doc_type" name="doc_type" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-4 py-3" required>

                            <option value="document">Document d'accompagnement</option>

                            <option value="file">Fichier</option>

                            <option value="exercise">Exercice</option>

                        </select>

                    </div>

                    <div id="edit_doc_scope_wrapper">

                        <label class="block text-sm mb-1" for="edit_doc_scope">Associer à *</label>

                        <select id="edit_doc_scope" name="doc_scope" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-4 py-3" required>

                            <option value="chapter">Chapitre</option>

                            <option value="lesson">Leçon</option>

                        </select>

                    </div>

                    <div>

                        <label class="block text-sm mb-1" for="edit_doc_chapter_id">Chapitre *</label>

                        <select id="edit_doc_chapter_id" name="doc_chapter_id" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-4 py-3" required>

                            <option value="">Choisir</option>

                            <?php foreach ($chapters as $chOpt): ?>

                                <?php if (($chOpt['title'] ?? '') !== 'Examens') : ?>

                                    <option value="<?php echo e($chOpt['id']); ?>"><?php echo e($chOpt['position']); ?>. <?php echo e($chOpt['title']); ?></option>

                                <?php endif; ?>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div>

                        <label class="block text-sm mb-1" for="edit_doc_position">Position</label>

                        <input id="edit_doc_position" name="doc_position" type="number" min="1" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-4 py-3">

                    </div>

                </div>

                <div id="edit_doc_lesson_wrapper" class="hidden">

                    <label class="block text-sm mb-1" for="edit_doc_lesson_id">Leçon</label>

                    <select id="edit_doc_lesson_id" name="doc_lesson_id" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-4 py-3">

                        <option value="">Choisir</option>

                        <?php foreach ($lessonsList as $ls): ?>

                            <?php if (($ls['chapter_title'] ?? '') !== 'Examens') : ?>

                                <option value="<?php echo e($ls['id']); ?>" data-chapter-id="<?php echo e($ls['chapter_id']); ?>"><?php echo e($ls['chap_pos']); ?>. <?php echo e($ls['chapter_title']); ?> - <?php echo e($ls['position']); ?>. <?php echo e($ls['title']); ?></option>

                            <?php endif; ?>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div>

                    <label class="block text-sm mb-1" for="edit_doc_title">Titre *</label>

                    <input id="edit_doc_title" name="doc_title" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-4 py-3" required>

                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4">

                    <label class="block text-sm mb-1" for="edit_doc_file">Fichier (Drive)</label>

                    <input id="edit_doc_file" type="file" name="doc_file" class="w-full rounded-lg bg-slate-950 border border-slate-700 px-4 py-3 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-800 file:px-3 file:py-2 file:text-sm file:text-slate-200">

                    <p class="mt-2 text-xs text-slate-400">

                        Laissez vide pour conserver le fichier actuel.

                        <?php if ($driveUploadNote !== ''): ?> <?php echo e($driveUploadNote); ?><?php endif; ?>

                        <?php if ($driveConnectRecommended): ?>

                            <a class="underline ml-1" href="<?php echo e(site_url('drive/oauth_connect')); ?>">Connecter Drive</a>

                        <?php endif; ?>

                    </p>

                    <p id="edit_doc_current_file_wrap" class="hidden mt-2 text-xs text-slate-300">Fichier actuel : <a id="edit_doc_current_file" class="text-emerald-300 underline" href="#" target="_blank" rel="noopener">Ouvrir</a></p>

                </div>

                <div class="flex justify-end gap-3">

                    <button type="button" data-close-modal="edit-doc-modal" class="course-content-action app-btn-soft">Annuler</button>

                    <button type="submit" class="course-content-action app-btn-success">Enregistrer</button>

                </div>

            </form>

        </div>

    </div>

</body>

</html>


