<?php
declare(strict_types=1);
 
$errors = is_array($errors ?? null) ? $errors : [];
$success = trim((string) ($success ?? ''));
$course = is_array($course ?? null) ? $course : [];
$courseId = (int) ($courseId ?? 0);
$statusLabels = is_array($statusLabels ?? null) ? $statusLabels : [];
$levelLabels = is_array($levelLabels ?? null) ? $levelLabels : [];
$chapters = is_array($chapters ?? null) ? $chapters : [];
$lessonsByChapter = is_array($lessonsByChapter ?? null) ? $lessonsByChapter : [];
$docsByChapter = is_array($docsByChapter ?? null) ? $docsByChapter : [];
$docsByLesson = is_array($docsByLesson ?? null) ? $docsByLesson : [];
$meetings = is_array($meetings ?? null) ? $meetings : [];
$exams = is_array($exams ?? null) ? $exams : [];
$durationLabel = $durationLabel ?? null;
$videoPlaylist = is_array($videoPlaylist ?? null) ? $videoPlaylist : [];
$docTotals = is_array($docTotals ?? null) ? $docTotals : ['exercise' => 0, 'resource' => 0, 'exam' => 0, 'all' => 0];
$isGeneralAdmin = !empty($isGeneralAdmin);

$extractYoutubeId = isset($extractYoutubeId) && is_callable($extractYoutubeId) ? $extractYoutubeId : static function (string $url): ?string {
        $parts = parse_url($url);
        if (!$parts || empty($parts['host'])) {
            return null;
        }
$host = strtolower((string) $parts['host']);
        if ($host === 'youtu.be') {
            return ltrim((string) ($parts['path'] ?? ''), '/');
        }
if (str_contains($host, 'youtube.com') && !empty($parts['query'])) {
            parse_str((string) $parts['query'], $query);
            return is_string($query['v'] ?? null) ? $query['v'] : null;
        }
return null;
    };

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
        }
return 'Ressource';
    };

$formatCourseList = isset($formatCourseList) && is_callable($formatCourseList) ? $formatCourseList : static function ($value): string {
        return nl2br(e((string) $value));
    };

$previewMode = 'admin';
require __DIR__ . '/../shared/_course_preview_bootstrap.php';

$courseStatusTone = match ((string) ($course['status'] ?? '')) {
    'submitted' => 'pending',
    'rejected' => 'urgent',
    'approved' => 'active',
    default => 'done',
};

$adminNavActive = 'courses';
$adminTopTitle = 'Aperçu du cours';
$adminTopSubtitle = 'Modération';
$adminTopBadge = $courseStatusLabel;
$adminTopBadgeTone = $courseStatusTone;
$adminTopActions = [
    ['label' => 'Retour à la modération', 'url' => site_url('admin/courses?course_id=' . $courseId), 'tone' => 'neutral'],
    ['label' => 'Liste des cours', 'url' => site_url('admin/courses'), 'tone' => 'neutral'],
];
if ($isGeneralAdmin) {
    $adminTopActions[] = ['label' => 'Admins secondaires', 'url' => site_url('admin/secondary_admins'), 'tone' => 'neutral'];
}

$courseShortDescription = trim(strip_tags(html_entity_decode($courseDescription, ENT_QUOTES, 'UTF-8')));
if ($courseShortDescription === '') {
    $courseShortDescription = 'Aucune description n’a encore été renseignée pour ce cours.';
}
if (function_exists('mb_strlen') && function_exists('mb_substr') && mb_strlen($courseShortDescription) > 240) {
    $courseShortDescription = rtrim(mb_substr($courseShortDescription, 0, 237)) . '...';
} elseif (strlen($courseShortDescription) > 240) {
    $courseShortDescription = rtrim(substr($courseShortDescription, 0, 237)) . '...';
}
$courseSubmittedAt = trim((string) ($course['submitted_at'] ?? ''));
$courseSubmittedLabel = $courseSubmittedAt !== '' ? $formatDate($courseSubmittedAt) : 'Date de soumission non précisée';
$courseCreatedAt = trim((string) ($course['created_at'] ?? ''));
$courseCreatedLabel = $courseCreatedAt !== '' ? $formatDate($courseCreatedAt) : 'Date de création non précisée';
$courseTimelineLabel = $courseSubmittedAt !== ''
    ? 'Soumis le ' . $courseSubmittedLabel
    : 'Créé le ' . $courseCreatedLabel;
$courseHeaderBadgeTone = match ((string) ($course['status'] ?? '')) {
    'submitted' => 'pending',
    'approved' => 'active',
    'rejected' => 'urgent',
    default => 'done',
};
$courseModerationTitle = match ((string) ($course['status'] ?? '')) {
    'submitted' => 'Décision en attente',
    'approved' => 'Cours publié',
    'rejected' => 'Cours rejeté',
    default => 'Cours non soumis',
};
$courseModerationText = match ((string) ($course['status'] ?? '')) {
    'submitted' => 'Vérifiez la structure avant validation.',
    'approved' => 'Le cours est déjà publié.',
    'rejected' => 'Le cours est hors ligne.',
    default => 'Cours en attente de validation.',
};
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aperçu du cours (administration) - <?php echo e($courseTitle); ?></title>
    <?php require __DIR__ . '/_head_assets.php'; ?>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="app-view app-role-admin admin-course-preview-page min-h-screen bg-slate-50 text-slate-900 font-body dark:bg-slate-950 dark:text-slate-100" data-view="admin/course_preview" data-sidebar-desktop-toggle="1">
    <div class="admin-shell-backdrop">
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--one"></div>
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--two"></div>
    </div>

    <div class="admin-shell-root relative z-10 flex min-h-screen">
        <?php require __DIR__ . '/_sidebar.php'; ?>
        <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

        <div class="admin-shell-main flex min-w-0 flex-1 flex-col">
            <?php require __DIR__ . '/_topbar.php'; ?>

            <main class="admin-shell-content admin-shell-dense w-full max-w-none space-y-5 px-4 py-5 sm:px-6 sm:py-6">
                <?php if ($errors !== []): ?>
                    <div class="admin-shell-alert admin-shell-alert--error">
                        <?php foreach ($errors as $error): ?>
                            <p>- <?php echo e((string) $error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success !== ''): ?>
                    <div class="admin-shell-alert admin-shell-alert--success">
                        <?php echo e($success); ?>
                    </div>
                <?php endif; ?>

                <section class="admin-course-panel p-4 sm:p-5 xl:p-6">
                    <div class="admin-course-header-grid">
                        <div class="admin-course-cover-frame">
                            <?php if ($coverUrl !== ''): ?>
                                <img src="<?php echo e($coverUrl); ?>" alt="<?php echo e($coverAlt); ?>" class="h-full w-full object-cover">
                            <?php else: ?>
                                <div class="flex h-full min-h-[8rem] items-center justify-center text-4xl text-slate-400 dark:text-slate-600">
                                    <i class="fas fa-book-open" aria-hidden="true"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="min-w-0">
                            <div class="admin-course-heading-badges">
                                <span class="admin-course-badge admin-course-badge--<?php echo e($courseHeaderBadgeTone); ?>"><?php echo e($courseStatusLabel); ?></span>
                                <span class="admin-course-badge admin-course-badge--neutral">Cours #<?php echo e((string) $courseId); ?></span>
                            </div>
                            <h2 class="mt-3 text-2xl font-black tracking-tight text-slate-900 dark:text-white sm:text-[2rem]"><?php echo e($courseTitle); ?></h2>
                            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300"><?php echo e($courseShortDescription); ?></p>

                            <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold">
                                <span class="admin-course-mini admin-course-mini--teacher"><i class="fas fa-chalkboard-teacher" aria-hidden="true"></i><?php echo e($teacherName); ?></span>
                                <?php if ($categoryName !== ''): ?>
                                    <span class="admin-course-mini admin-course-mini--category"><i class="fas fa-tag" aria-hidden="true"></i><?php echo e($categoryName); ?></span>
                                <?php endif; ?>
                                <span class="admin-course-mini admin-course-mini--date"><i class="far fa-calendar-alt" aria-hidden="true"></i><?php echo e($courseTimelineLabel); ?></span>
                                <span class="admin-course-chip admin-course-chip--level"><i class="fas fa-signal" aria-hidden="true"></i><?php echo e($courseLevelLabel); ?></span>
                                <?php if (!is_null($durationLabel) && trim((string) $durationLabel) !== ''): ?>
                                    <span class="admin-course-chip admin-course-chip--duration"><i class="fas fa-hourglass-half" aria-hidden="true"></i><?php echo e((string) $durationLabel); ?></span>
                                <?php endif; ?>
                                <span class="admin-course-chip admin-course-chip--price"><i class="fas fa-money-bill-wave" aria-hidden="true"></i><?php echo e($formatMoney($coursePrice)); ?></span>
                            </div>

                            <nav class="admin-course-section-nav mt-4" aria-label="Navigation du cours">
                                <a href="#pedagogie">Pédagogie</a>
                                <a href="#structure">Structure</a>
                                <?php if ($meetings !== []): ?>
                                    <a href="#sessions">Sessions</a>
                                <?php endif; ?>
                                <?php if ($exams !== []): ?>
                                    <a href="#evaluations">Évaluations</a>
                                <?php endif; ?>
                            </nav>
                        </div>

                        <aside class="admin-course-header-actions">
                            <a href="<?php echo e(site_url('admin/courses?course_id=' . $courseId)); ?>" class="admin-shell-button admin-shell-button--primary inline-flex items-center justify-center gap-2">
                                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                Retour à la modération
                            </a>
                            <a href="<?php echo e(site_url('admin/courses')); ?>" class="admin-shell-button admin-shell-button--secondary inline-flex items-center justify-center gap-2">
                                <i class="fas fa-list" aria-hidden="true"></i>
                                Liste des cours
                            </a>
                            <div class="admin-course-quick-grid">
                                <article class="admin-course-kpi-compact">
                                    <span class="admin-course-kpi-compact__label">Chapitres</span>
                                    <strong class="admin-course-kpi-compact__value"><?php echo e((string) $chapterCount); ?></strong>
                                </article>
                                <article class="admin-course-kpi-compact">
                                    <span class="admin-course-kpi-compact__label">Leçons</span>
                                    <strong class="admin-course-kpi-compact__value"><?php echo e((string) $lessonCount); ?></strong>
                                </article>
                                <article class="admin-course-kpi-compact">
                                    <span class="admin-course-kpi-compact__label">Vidéos</span>
                                    <strong class="admin-course-kpi-compact__value"><?php echo e((string) $videoCount); ?></strong>
                                </article>
                                <article class="admin-course-kpi-compact">
                                    <span class="admin-course-kpi-compact__label">Ressources</span>
                                    <strong class="admin-course-kpi-compact__value"><?php echo e((string) $resourceCount); ?></strong>
                                </article>
                            </div>
                        </aside>
                    </div>
                </section>

                <div class="admin-course-preview-grid">
                    <div class="admin-course-preview-content space-y-5 min-w-0">
                        <?php require __DIR__ . '/../shared/_course_preview_plan.php'; ?>
                        <?php require __DIR__ . '/../shared/_course_preview_meetings.php'; ?>
                        <?php require __DIR__ . '/../shared/_course_preview_exams.php'; ?>
                    </div>

                    <aside class="admin-course-sticky-column space-y-5">
                        <?php if ((string) ($course['status'] ?? '') === 'submitted') : ?>
                            <section class="admin-course-panel p-4 sm:p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-[0.72rem] font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Décision rapide</p>
                                        <h3 class="mt-2 text-lg font-black text-slate-900 dark:text-white">Ce cours attend votre validation</h3>
                                        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Validez-le si tout est cohérent. Sinon, rejetez avec un motif.</p>
                                    </div>
                                    <span class="admin-course-badge admin-course-badge--pending">Soumis</span>
                                </div>

                                <div class="mt-4 space-y-3">
                                    <form method="post" action="<?php echo e(site_url('admin/course_preview?id=' . $courseId)); ?>" data-confirm-message="Approuver ce cours et le publier sur la plateforme ?">
                                        <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="admin-course-action admin-course-action--approve">
                                            <i class="fas fa-check-circle" aria-hidden="true"></i>
                                            Approuver le cours
                                        </button>
                                    </form>

                                    <form method="post" action="<?php echo e(site_url('admin/course_preview?id=' . $courseId)); ?>" class="space-y-3">
                                        <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="text" name="reason" required class="admin-shell-input" placeholder="Motif du rejet obligatoire">
                                        <button type="submit" class="admin-course-action admin-course-action--reject">
                                            <i class="fas fa-times-circle" aria-hidden="true"></i>
                                            Rejeter le cours
                                        </button>
                                    </form>
                                </div>
                            </section>
                        <?php endif; ?>

                        <section id="pedagogie" class="admin-course-panel admin-course-anchor-target p-4 sm:p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-[0.72rem] font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Repères pédagogiques</p>
                                    <h3 class="mt-2 text-lg font-black text-slate-900 dark:text-white">Objectifs, prérequis et matériel</h3>
                                    <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Points clés avant publication.</p>
                                </div>
                                <span class="admin-course-badge admin-course-badge--<?php echo e($hasPedagogicalInfo ? 'active' : 'neutral'); ?>">
                                    <?php echo e($hasPedagogicalInfo ? 'Complet' : 'À compléter'); ?>
                                </span>
                            </div>

                            <?php if (!$hasPedagogicalInfo): ?>
                                    <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-3 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-300">
                                    Ajoutez des objectifs, prérequis ou une ressource.
                                </div>
                            <?php else: ?>
                                <div class="mt-4 space-y-3">
                                    <?php if (!empty($course['objectives'])): ?>
                                        <article class="rounded-2xl border border-slate-200/80 bg-slate-50/85 p-3 dark:border-slate-800 dark:bg-slate-950/45">
                                            <p class="text-xs font-black uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Objectifs pédagogiques</p>
                                            <div class="mt-2 text-sm leading-6 text-slate-700 dark:text-slate-200"><?php echo $formatCourseList($course['objectives']); ?></div>
                                        </article>
                                    <?php endif; ?>

                                    <?php if (!empty($course['prerequisites'])): ?>
                                        <article class="rounded-2xl border border-slate-200/80 bg-slate-50/85 p-3 dark:border-slate-800 dark:bg-slate-950/45">
                                            <p class="text-xs font-black uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Prérequis</p>
                                            <div class="mt-2 text-sm leading-6 text-slate-700 dark:text-slate-200"><?php echo $formatCourseList($course['prerequisites']); ?></div>
                                        </article>
                                    <?php endif; ?>

                                    <?php if (!empty($course['equipment'])): ?>
                                        <article class="rounded-2xl border border-slate-200/80 bg-slate-50/85 p-3 dark:border-slate-800 dark:bg-slate-950/45">
                                            <p class="text-xs font-black uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Équipement recommandé</p>
                                            <div class="mt-2 text-sm leading-6 text-slate-700 dark:text-slate-200"><?php echo $formatCourseList($course['equipment']); ?></div>
                                        </article>
                                    <?php endif; ?>

                                    <?php if ($generalResourceUrl !== ''): ?>
                                        <article class="rounded-2xl border border-slate-200/80 bg-slate-50/85 p-3 dark:border-slate-800 dark:bg-slate-950/45">
                                            <p class="text-xs font-black uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Ressource générale</p>
                                            <a href="<?php echo e($generalResourceUrl); ?>" target="_blank" rel="noopener" class="mt-3 inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-400 dark:border-slate-700 dark:bg-slate-950/70 dark:text-slate-100 dark:hover:border-slate-500">
                                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('external-link'); ?></span>
                                                <span>Ouvrir la ressource</span>
                                            </a>
                                        </article>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </section>

                        <section class="admin-course-panel p-4 sm:p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-[0.72rem] font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Contexte et suivi</p>
                                    <h3 class="mt-2 text-lg font-black text-slate-900 dark:text-white"><?php echo e($courseModerationTitle); ?></h3>
                                    <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300"><?php echo e($courseModerationText); ?></p>
                                </div>
                                <span class="admin-course-badge admin-course-badge--<?php echo e($courseHeaderBadgeTone); ?>"><?php echo e($courseStatusLabel); ?></span>
                            </div>

                            <div class="mt-4 space-y-2.5">
                                <div class="admin-course-summary-item">
                                    <span>Enseignant</span>
                                    <strong><?php echo e($teacherName); ?></strong>
                                </div>
                                <div class="admin-course-summary-item">
                                    <span>Catégorie</span>
                                    <strong><?php echo e($categoryName !== '' ? $categoryName : 'Non renseignée'); ?></strong>
                                </div>
                                <div class="admin-course-summary-item">
                                    <span>Création</span>
                                    <strong><?php echo e($courseCreatedLabel); ?></strong>
                                </div>
                                <div class="admin-course-summary-item">
                                    <span>Soumission</span>
                                    <strong><?php echo e($courseSubmittedLabel); ?></strong>
                                </div>
                            </div>

                            <?php if (!empty($course['last_rejection_reason'])): ?>
                                <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-3 py-3 text-sm leading-6 text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                                    <span class="font-bold">Dernier motif de rejet :</span>
                                    <?php echo e((string) $course['last_rejection_reason']); ?>
                                </div>
                            <?php endif; ?>
                        </section>

                        <section class="admin-course-panel p-4 sm:p-5">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-[0.72rem] font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Couverture</p>
                                    <h3 class="mt-2 text-lg font-black text-slate-900 dark:text-white">Visuel du cours</h3>
                                </div>
                                <span class="admin-course-badge admin-course-badge--neutral">Contrôle visuel</span>
                            </div>

                            <?php if ($coverUrl !== ''): ?>
                                <img src="<?php echo e($coverUrl); ?>" alt="<?php echo e($coverAlt); ?>" class="mt-4 h-56 w-full rounded-2xl border border-slate-200/80 object-cover dark:border-slate-800">
                            <?php else: ?>
                                <div class="mt-4 flex h-48 items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-300">
                                    Aucune miniature n'est définie.
                                </div>
                            <?php endif; ?>
                        </section>
                    </aside>
                </div>
            </main>
        </div>
    </div>
</body>
</html>


