<?php
declare(strict_types=1);

$course = is_array($course ?? null) ? $course : [];
$listQuery = (string) ($listQuery ?? '');
$backUrl = trim((string) ($backUrl ?? site_url('teacher/courses')));

$decodeLabel = static function (string $label): string {
    return html_entity_decode($label, ENT_QUOTES, 'UTF-8');
}; 
$doneLabel = $decodeLabel('Termin&eacute;');
$doneLabelFeminine = $decodeLabel('Termin&eacute;e');
$urgentLabel = 'Urgent';
$urgentLabelFeminine = 'Urgente'; 
$stateBadgeMap = [
    'Actif' => 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-200',
    'En attente' => 'border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200',
    $urgentLabel => 'border-rose-300 bg-rose-50 text-rose-700 dark:border-rose-500/40 dark:bg-rose-500/10 dark:text-rose-200',
    $urgentLabelFeminine => 'border-rose-300 bg-rose-50 text-rose-700 dark:border-rose-500/40 dark:bg-rose-500/10 dark:text-rose-200',
    $doneLabel => 'border-slate-300 bg-slate-50 text-slate-700 dark:border-slate-600 dark:bg-slate-800/40 dark:text-slate-200',
    $doneLabelFeminine => 'border-slate-300 bg-slate-50 text-slate-700 dark:border-slate-600 dark:bg-slate-800/40 dark:text-slate-200',
    'Brouillon' => 'border-slate-300 bg-slate-50 text-slate-700 dark:border-slate-600 dark:bg-slate-800/40 dark:text-slate-200',
    $decodeLabel('Rejet&eacute;') => 'border-rose-300 bg-rose-50 text-rose-700 dark:border-rose-500/40 dark:bg-rose-500/10 dark:text-rose-200',
    $decodeLabel('Archiv&eacute;') => 'border-slate-300 bg-slate-100 text-slate-700 dark:border-slate-600 dark:bg-slate-800/70 dark:text-slate-200',
];
$workflowLabels = [
    'draft' => 'Brouillon',
    'submitted' => 'En attente',
    'approved' => 'Actif',
    'rejected' => $decodeLabel('Rejet&eacute;'),
    'archived' => $decodeLabel('Archiv&eacute;'),
];
$levelLabels = [
    'beginner' => 'D&eacute;butant',
    'intermediate' => 'Interm&eacute;diaire',
    'advanced' => 'Avanc&eacute;',
];
$workflow = (string) ($course['workflow_status'] ?? ($course['status'] ?? 'draft'));
if (!isset($workflowLabels[$workflow])) {
    $workflow = 'draft';
}
$workflowLabel = $workflowLabels[$workflow] ?? 'Brouillon';
$workflowBadgeClass = $stateBadgeMap[$workflowLabel] ?? $stateBadgeMap['Brouillon'];

$submission = is_array($course['submission'] ?? null) ? $course['submission'] : [
    'chapters_cnt' => 0,
    'lessons_cnt' => 0,
    'meetings_cnt' => 0,
    'exams_cnt' => 0,
    'adv_exams_cnt' => 0,
];
$submissionReady = !empty($course['submission_ready']);
$submissionToneLabel = $submissionReady ? $doneLabelFeminine : $urgentLabelFeminine;
$submissionStateLabel = $submissionReady ? $decodeLabel('Pr&ecirc;te') : $decodeLabel('&Agrave; compl&eacute;ter');
$submissionBadgeClass = $stateBadgeMap[$submissionToneLabel] ?? $stateBadgeMap[$urgentLabel];
$submissionIcon = $submissionReady ? 'check-circle' : 'alert-circle';
$chaptersCount = max(0, (int) ($submission['chapters_cnt'] ?? 0));
$lessonsCount = max(0, (int) ($submission['lessons_cnt'] ?? 0));
$meetingsCount = max(0, (int) ($submission['meetings_cnt'] ?? 0));
$chaptersLabel = $chaptersCount === 1 ? 'chapitre' : 'chapitres';
$lessonsLabel = $lessonsCount === 1 ? $decodeLabel('le&ccedil;on') : $decodeLabel('le&ccedil;ons');
$meetingsLabel = $meetingsCount > 1 ? 'rendez-vous' : 'rendez-vous';

$stats = is_array($course['stats'] ?? null) ? $course['stats'] : [
    'views' => 0,
    'enrollments' => 0,
    'paid_sales' => 0,
    'paid_amount_xof' => 0,
];

$courseId = (int) ($course['id'] ?? 0);
$courseTitle = trim((string) ($course['title'] ?? 'Cours sans titre'));
$courseDescription = trim((string) ($course['description'] ?? ''));
$courseUpdatedRaw = trim((string) ($course['updated_at'] ?? ($course['created_at'] ?? '')));
$courseUpdatedTs = $courseUpdatedRaw !== '' ? strtotime($courseUpdatedRaw) : false;
$courseUpdated = $courseUpdatedTs ? date('d/m/Y', $courseUpdatedTs) : $decodeLabel('Date non renseign&eacute;e');
$courseLevel = trim((string) ($course['level'] ?? ''));
$courseLevelLabel = $levelLabels[$courseLevel] ?? ($courseLevel !== '' ? $courseLevel : $decodeLabel('Niveau non renseign&eacute;'));
$coursePrice = (int) ($course['price_xof'] ?? 0);
$coursePriceLabel = $coursePrice > 0 ? number_format($coursePrice, 0, ',', ' ') . ' F CFA' : 'Gratuit';
$durationHours = (float) ($course['duration_hours'] ?? 0);
$durationLabel = $durationHours > 0 ? format_duration_hours_hm($durationHours, true) : 'Dur&eacute;e non d&eacute;finie';
$coverPath = trim((string) ($course['cover_path'] ?? ''));
$coverUrl = '';
if ($coverPath !== '') {
    $coverUrl = preg_match('~^https://~i', $coverPath) === 1 ? $coverPath : site_url(ltrim($coverPath, '/'));
}
$coverAlt = trim((string) ($course['cover_alt'] ?? 'Miniature du cours'));

$isArchived = $workflow === 'archived';
$canSubmit = in_array($workflow, ['draft', 'rejected'], true) && $submissionReady;
$lastRejectionReason = trim((string) ($course['last_rejection_reason'] ?? ''));
?>
<!doctype html>
<html lang="fr" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="app-global-assets" content="off">
    <meta name="google" content="notranslate">
    <title><?php echo e($decodeLabel('G&eacute;rer le cours')); ?> - Enseignant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo e(site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()))); ?>"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/teacher-dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/teacher-dashboard.css') ?: time()))); ?>">
    <script src="<?php echo e(site_url('assets/js/sidebar_toggle.js')); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/teacher_dashboard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/teacher_dashboard.js') ?: time()))); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/teacher_courses.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/teacher_courses.js') ?: time()))); ?>" defer></script>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="app-view app-role-teacher min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50 text-slate-900 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950 dark:text-slate-100 font-body notranslate" data-view="teacher/courses">
    <div class="teacher-dashboard-layout relative h-screen overflow-hidden pt-16 sm:pt-[4.5rem] md:pl-72 md:flex-row">
        <?php $teacherNavActive = 'courses'; ?>
        <?php require __DIR__ . '/_sidebar.php'; ?>
        <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

        <div class="flex-1 min-w-0">
            <?php $teacherTopTitle = $decodeLabel('G&eacute;rer le cours'); ?>
            <?php $teacherTopSubtitle = 'Espace enseignant'; ?>
            <?php require __DIR__ . '/_topbar.php'; ?>

            <main class="teacher-courses-main mx-auto px-4 md:px-6 py-6 space-y-5">
                <?php $teacherWelcomeTitle = $courseTitle; ?>
                <?php $teacherWelcomeSummary = 'Choisissez une action, puis revenez aux cours.'; ?>
                <?php $teacherWelcomeActionUrl = $backUrl; ?>
                <?php $teacherWelcomeActionLabel = 'Retour à la liste'; ?>
                <?php $teacherWelcomeActionIcon = 'layout'; ?>
                <?php require __DIR__ . '/_welcome_hero.php'; ?>

                <section class="teacher-courses-panel rounded-2xl border border-slate-200/80 bg-white/90 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/40">
                    <div class="teacher-courses-card__stack space-y-3">
                        <div class="teacher-courses-card__intro">
                            <div class="teacher-courses-card__top flex flex-wrap items-start gap-3">
                                <div class="teacher-courses-card__cover h-16 w-20 shrink-0 overflow-hidden rounded-xl border border-slate-200 bg-slate-100 dark:border-slate-800 dark:bg-slate-900">
                                    <?php if ($coverUrl !== ''): ?>
                                        <img src="<?php echo e($coverUrl); ?>" alt="<?php echo e($coverAlt); ?>" loading="lazy" decoding="async" class="h-full w-full object-cover">
                                    <?php else: ?>
                                        <div class="teacher-courses-card__cover-fallback flex h-full w-full items-center justify-center text-xs text-slate-400">Aucune image</div>
                                    <?php endif; ?>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="teacher-courses-card__heading flex flex-wrap items-start justify-between gap-2">
                                        <div>
                                            <p class="teacher-courses-card__title text-sm font-semibold text-slate-900 dark:text-slate-100">
                                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('book-open'); ?></span>
                                                <span><?php echo e($courseTitle); ?></span>
                                            </p>
                                            <p class="teacher-courses-card__subline text-xs text-slate-500 dark:text-slate-300">Mise &agrave; jour le <?php echo e($courseUpdated); ?></p>
                                        </div>
                                        <span class="inline-flex items-center rounded-full border px-2 py-1 text-xs font-semibold <?php echo e($workflowBadgeClass); ?>"><?php echo e($workflowLabel); ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php if ($courseDescription !== ''): ?>
                                <p class="teacher-courses-card__description teacher-courses-card__description--split text-xs text-slate-600 line-clamp-3 dark:text-slate-300"><?php echo e($courseDescription); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="teacher-courses-card__chips text-xs text-slate-500 dark:text-slate-300">
                            <span class="teacher-courses-chip inline-flex items-center gap-1 rounded-full border border-slate-200 px-2 py-1 dark:border-slate-700">
                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('award'); ?></span>
                                <span>Niveau : <?php echo e($courseLevelLabel); ?></span>
                            </span>
                            <span class="teacher-courses-chip inline-flex items-center gap-1 rounded-full border border-slate-200 px-2 py-1 dark:border-slate-700">
                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('clock'); ?></span>
                                <span>Dur&eacute;e : <?php echo e($durationLabel); ?></span>
                            </span>
                            <span class="teacher-courses-chip inline-flex items-center gap-1 rounded-full border border-slate-200 px-2 py-1 dark:border-slate-700">
                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('wallet'); ?></span>
                                <span>Prix : <span class="app-price"><?php echo e($coursePriceLabel); ?></span></span>
                            </span>
                        </div>

                        <div class="teacher-courses-card__metrics grid gap-2 sm:grid-cols-3 text-xs" data-course-kpi-group>
                            <div class="teacher-courses-kpi rounded-xl border border-slate-200 bg-white/80 px-3 py-2 dark:border-slate-700 dark:bg-slate-900/70">
                                <p class="teacher-courses-kpi__label text-slate-500 dark:text-slate-400">
                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('layout'); ?></span>
                                    <span>Vues</span>
                                </p>
                                <p class="teacher-courses-kpi__value text-sm font-semibold text-slate-900 dark:text-slate-100"><?php echo e((string) ((int) ($stats['views'] ?? 0))); ?></p>
                            </div>
                            <div class="teacher-courses-kpi rounded-xl border border-slate-200 bg-white/80 px-3 py-2 dark:border-slate-700 dark:bg-slate-900/70">
                                <p class="teacher-courses-kpi__label text-slate-500 dark:text-slate-400">
                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('users'); ?></span>
                                    <span>Inscriptions</span>
                                </p>
                                <p class="teacher-courses-kpi__value text-sm font-semibold text-slate-900 dark:text-slate-100"><?php echo e((string) ((int) ($stats['enrollments'] ?? 0))); ?></p>
                            </div>
                            <div class="teacher-courses-kpi rounded-xl border border-slate-200 bg-white/80 px-3 py-2 dark:border-slate-700 dark:bg-slate-900/70">
                                <p class="teacher-courses-kpi__label text-slate-500 dark:text-slate-400">
                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('wallet'); ?></span>
                                    <span>Ventes</span>
                                </p>
                                <p class="teacher-courses-kpi__value text-sm font-semibold text-slate-900 dark:text-slate-100"><?php echo e((string) ((int) ($stats['paid_sales'] ?? 0))); ?></p>
                            </div>
                        </div>

                        <div class="teacher-courses-card__meta flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500 dark:text-slate-300">
                            <div class="teacher-courses-card__meta-main flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center gap-1 rounded-full border px-2 py-1 font-semibold <?php echo e($submissionBadgeClass); ?>">
                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg($submissionIcon); ?></span>
                                    <span>Soumission : <?php echo e($submissionStateLabel); ?></span>
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('layout'); ?></span>
                                    <span>Plan : <?php echo e((string) $chaptersCount); ?> <?php echo e($chaptersLabel); ?> &middot; <?php echo e((string) $lessonsCount); ?> <?php echo e($lessonsLabel); ?> &middot; <?php echo e((string) $meetingsCount); ?> <?php echo e($meetingsLabel); ?></span>
                                </span>
                            </div>
                        </div>

                        <div class="teacher-courses-card__actions teacher-courses-card__actions--manager" data-course-actions="card">
                            <section class="teacher-courses-card__actions-group teacher-courses-card__actions-group--main">
                                <p class="teacher-courses-card__actions-title">Actions principales</p>
                                <div class="teacher-courses-card__actions-grid">
                                    <a href="<?php echo e(site_url('teacher/course_edit?id=' . $courseId)); ?>" class="teacher-courses-card__button teacher-courses-card__button--secondary inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-400 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100" data-action-tone="secondary">
                                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('settings'); ?></span>
                                        <span>Modifier</span>
                                    </a>
                                    <a href="<?php echo e(site_url('teacher/course_content?id=' . $courseId)); ?>" class="teacher-courses-card__button teacher-courses-card__button--secondary inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-400 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100" data-action-tone="secondary">
                                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('book-open'); ?></span>
                                        <span>Contenu</span>
                                    </a>
                                    <a href="<?php echo e(site_url('teacher/course_preview?id=' . $courseId)); ?>" class="teacher-courses-card__button teacher-courses-card__button--secondary inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-400 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100" data-action-tone="secondary">
                                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('external-link'); ?></span>
                                        <span>Aper&ccedil;u</span>
                                    </a>
                                </div>
                            </section>

                            <section class="teacher-courses-card__actions-group teacher-courses-card__actions-group--ops">
                                <p class="teacher-courses-card__actions-title">Publication et gestion</p>
                                <div class="teacher-courses-card__actions-grid">
                                    <?php if (in_array($workflow, ['draft', 'rejected'], true)): ?>
                                        <form method="post" action="<?php echo e(site_url('teacher/courses')); ?>" class="inline-flex teacher-courses-card__action-span-2">
                                            <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                            <input type="hidden" name="action" value="submit">
                                            <input type="hidden" name="course_id" value="<?php echo e((string) $courseId); ?>">
                                            <input type="hidden" name="list_query" value="<?php echo e($listQuery); ?>">
                                            <button type="submit" class="teacher-courses-card__button teacher-courses-card__button--primary inline-flex items-center justify-center rounded-xl border border-emerald-500 bg-emerald-500 px-3 py-2 text-xs font-semibold text-slate-950 transition hover:bg-emerald-400 <?php echo $canSubmit ? '' : 'opacity-50 cursor-not-allowed'; ?>" data-action-tone="primary" <?php echo $canSubmit ? '' : 'disabled'; ?> data-confirm-action="form" data-confirm-title="Soumettre ce cours " data-confirm-message="Le cours sera envoy&eacute; &agrave; l'administration." data-confirm-label="Soumettre" data-confirm-variant="success">
                                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('check-circle'); ?></span>
                                                <span>Soumettre</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($workflow === 'approved'): ?>
                                        <form method="post" action="<?php echo e(site_url('teacher/courses')); ?>" class="inline-flex">
                                            <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                            <input type="hidden" name="action" value="new_version">
                                            <input type="hidden" name="course_id" value="<?php echo e((string) $courseId); ?>">
                                            <input type="hidden" name="list_query" value="<?php echo e($listQuery); ?>">
                                            <button type="submit" class="teacher-courses-card__button teacher-courses-card__button--secondary inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-400 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100" data-action-tone="secondary" data-confirm-action="form" data-confirm-title="Cr&eacute;er une nouvelle version " data-confirm-message="Une copie sera cr&eacute;&eacute;e en brouillon." data-confirm-label="Cr&eacute;er" data-confirm-variant="default">
                                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('repeat'); ?></span>
                                                <span>Nouvelle version</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($workflow === 'rejected'): ?>
                                        <form method="post" action="<?php echo e(site_url('teacher/courses')); ?>" class="inline-flex">
                                            <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                            <input type="hidden" name="action" value="save_draft">
                                            <input type="hidden" name="course_id" value="<?php echo e((string) $courseId); ?>">
                                            <input type="hidden" name="list_query" value="<?php echo e($listQuery); ?>">
                                            <button type="submit" class="teacher-courses-card__button teacher-courses-card__button--secondary inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-400 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100" data-action-tone="secondary" data-confirm-action="form" data-confirm-title="Passer en brouillon " data-confirm-message="Le cours redeviendra totalement modifiable." data-confirm-label="Confirmer" data-confirm-variant="default">
                                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('file-text'); ?></span>
                                                <span>Passer en brouillon</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="post" action="<?php echo e(site_url('teacher/courses')); ?>" class="inline-flex teacher-courses-card__action-span-2">
                                        <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                        <input type="hidden" name="action" value="duplicate">
                                        <input type="hidden" name="course_id" value="<?php echo e((string) $courseId); ?>">
                                        <input type="hidden" name="list_query" value="<?php echo e($listQuery); ?>">
                                        <button type="submit" class="teacher-courses-card__button teacher-courses-card__button--secondary inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-400 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100" data-action-tone="secondary" data-confirm-action="form" data-confirm-title="Dupliquer ce cours " data-confirm-message="Une copie sera cr&eacute;&eacute;e en brouillon." data-confirm-label="Dupliquer" data-confirm-variant="default">
                                            <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('repeat'); ?></span>
                                            <span>Dupliquer</span>
                                        </button>
                                    </form>

                                    <form method="post" action="<?php echo e(site_url('teacher/courses')); ?>" class="inline-flex teacher-courses-card__action-span-2">
                                        <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                        <input type="hidden" name="action" value="<?php echo $isArchived ? 'restore' : 'archive'; ?>">
                                        <input type="hidden" name="course_id" value="<?php echo e((string) $courseId); ?>">
                                        <input type="hidden" name="list_query" value="<?php echo e($listQuery); ?>">
                                        <button type="submit" class="teacher-courses-card__button <?php echo $isArchived ? 'teacher-courses-card__button--primary border-emerald-500 bg-emerald-500 text-slate-950 hover:bg-emerald-400' : 'teacher-courses-card__button--danger border-rose-500 text-rose-600 hover:border-rose-400 dark:text-rose-200'; ?>" data-action-tone="danger" data-confirm-action="form" data-confirm-title="<?php echo $isArchived ? 'Restaurer ce cours ' : 'Archiver ce cours '; ?>" data-confirm-message="<?php echo $isArchived ? 'Le cours redeviendra visible dans votre liste.' : 'Le cours sera retir&eacute; du catalogue.'; ?>" data-confirm-label="<?php echo $isArchived ? 'Restaurer' : 'Archiver'; ?>" data-confirm-variant="<?php echo $isArchived ? 'success' : 'danger'; ?>">
                                            <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg($isArchived ? 'check-circle' : 'x'); ?></span>
                                            <span><?php echo $isArchived ? 'Restaurer' : 'Archiver'; ?></span>
                                        </button>
                                    </form>
                                </div>
                            </section>
                        </div>
                    </div>
                </section>

                <?php if ($workflow === 'rejected' && $lastRejectionReason !== ''): ?>
                    <section class="teacher-courses-alert teacher-courses-alert--warning rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200">
                        <span class="teacher-courses-alert__icon app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('alert-triangle'); ?></span>
                        <div class="teacher-courses-alert__body">
                            <p class="teacher-courses-alert__title">Retour de modération</p>
                            <p><?php echo e($lastRejectionReason); ?></p>
                        </div>
                    </section>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <div id="course-action-modal" class="teacher-courses-modal fixed inset-0 z-40 hidden items-center justify-center bg-slate-950/60 p-4" aria-hidden="true">
        <div class="teacher-courses-modal__panel w-full max-w-md rounded-2xl border p-5 shadow-xl" role="dialog" aria-modal="true" aria-labelledby="course-action-modal-title">
            <h2 id="course-action-modal-title" class="text-lg font-semibold text-slate-900 dark:text-slate-100">Confirmation</h2>
            <p id="course-action-modal-message" class="mt-2 text-sm text-slate-600 dark:text-slate-300">Confirmez-vous cette action </p>
            <div class="mt-4 flex flex-wrap justify-end gap-2">
                <button type="button" id="course-action-modal-cancel" class="teacher-courses-btn teacher-courses-btn--secondary inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-400 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100" data-modal-close>Annuler</button>
                <button type="button" id="course-action-modal-confirm" class="teacher-courses-btn teacher-courses-btn--primary inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-emerald-500">Confirmer</button>
            </div>
        </div>
    </div>
</body>
</html>



