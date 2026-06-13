<?php
declare(strict_types=1);

$previewMode = (string) ($previewMode ?? 'teacher');
$isTeacherPreview = $previewMode === 'teacher';
?>
<section class="rounded-3xl border border-slate-200 bg-white/90 p-3 shadow-sm dark:border-slate-800 dark:bg-slate-950/50 sm:p-4">
    <div class="grid gap-3 xl:grid-cols-[minmax(0,1.95fr)_minmax(16rem,0.72fr)]">
        <div class="space-y-3 min-w-0">
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-200">
                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('book-open'); ?></span>
                </div>
                <div class="min-w-0">
                    <p class="text-[9px] uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Cours #<?php echo e((string) $courseId); ?></p>
                    <h2 class="mt-1 break-all text-sm font-display font-semibold text-slate-900 dark:text-slate-100 sm:text-base"><?php echo e($courseTitle); ?></h2>
                    <p class="mt-2 max-w-full break-all text-[10px] leading-5 text-slate-600 dark:text-slate-300">
                        <?php echo e($courseDescription !== '' ? $courseDescription : 'Ajoutez une description claire pour présenter le contenu et la promesse pédagogique du cours.'); ?>
                    </p>
                </div>
            </div> 

            <div class="flex flex-wrap gap-1.5">
                <span class="admin-preview-meta admin-preview-meta--status">
                    <span class="app-icon text-blue-600 dark:text-blue-300" aria-hidden="true"><?php echo dashboard_icon_svg('shield'); ?></span>
                    <?php echo e($courseStatusLabel); ?>
                </span>
                <span class="admin-preview-meta admin-preview-meta--level">
                    <span class="app-icon text-blue-600 dark:text-blue-300" aria-hidden="true"><?php echo dashboard_icon_svg('award'); ?></span>
                    Niveau : <?php echo e($courseLevelLabel); ?>
                </span>
                <?php if (!is_null($durationLabel) && trim((string) $durationLabel) !== ''): ?>
                    <span class="admin-preview-meta admin-preview-meta--duration">
                        <span class="app-icon text-blue-600 dark:text-blue-300" aria-hidden="true"><?php echo dashboard_icon_svg('clock'); ?></span>
                        Durée : <?php echo e((string) $durationLabel); ?>
                    </span>
                <?php endif; ?>
                <span class="admin-preview-meta admin-preview-meta--price">
                    <span class="app-icon text-blue-600 dark:text-blue-300" aria-hidden="true"><?php echo dashboard_icon_svg('wallet'); ?></span>
                    Prix : <?php echo e($formatMoney($coursePrice)); ?>
                </span>
                <span class="admin-preview-meta admin-preview-meta--resources">
                    <span class="app-icon text-blue-600 dark:text-blue-300" aria-hidden="true"><?php echo dashboard_icon_svg('folder'); ?></span>
                    Ressources : <?php echo e($formatNumber($resourceCount)); ?>
                </span>
                <?php if (!$isTeacherPreview && $categoryName !== ''): ?>
                    <span class="admin-preview-meta admin-preview-meta--category">
                        <span class="app-icon text-blue-600 dark:text-blue-300" aria-hidden="true"><?php echo dashboard_icon_svg('layout'); ?></span>
                        Catégorie : <?php echo e($categoryName); ?>
                    </span>
                <?php endif; ?>
            </div>

            <div class="grid gap-2.5 sm:grid-cols-2 xl:grid-cols-4">
                <article class="app-kpi-card app-kpi-card--blue rounded-2xl border p-2.5">
                    <p class="app-kpi-card__head text-[9px]">
                        <span class="app-kpi-card__icon" aria-hidden="true"><?php echo dashboard_icon_svg('book-open'); ?></span>
                        <span>Chapitres</span>
                    </p>
                    <p class="app-kpi-card__value text-xs"><?php echo e((string) $chapterCount); ?></p>
                    <p class="app-kpi-card__meta text-[9px]">Structure générale</p>
                </article>
                <article class="app-kpi-card app-kpi-card--indigo rounded-2xl border p-2.5">
                    <p class="app-kpi-card__head text-[9px]">
                        <span class="app-kpi-card__icon" aria-hidden="true"><?php echo dashboard_icon_svg('play-circle'); ?></span>
                        <span>Leçons</span>
                    </p>
                    <p class="app-kpi-card__value text-xs"><?php echo e((string) $lessonCount); ?></p>
                    <p class="app-kpi-card__meta text-[9px]"><?php echo e((string) $videoCount); ?> vidéo(s)</p>
                </article>
                <article class="app-kpi-card app-kpi-card--emerald rounded-2xl border p-2.5">
                    <p class="app-kpi-card__head text-[9px]">
                        <span class="app-kpi-card__icon" aria-hidden="true"><?php echo dashboard_icon_svg('calendar'); ?></span>
                        <span>Rendez-vous</span>
                    </p>
                    <p class="app-kpi-card__value text-xs"><?php echo e((string) $meetingCount); ?></p>
                    <p class="app-kpi-card__meta text-[9px]">Sessions programmées</p>
                </article>
                <article class="app-kpi-card app-kpi-card--sky rounded-2xl border p-2.5">
                    <p class="app-kpi-card__head text-[9px]">
                        <span class="app-kpi-card__icon" aria-hidden="true"><?php echo dashboard_icon_svg('file-text'); ?></span>
                        <span>Examens</span>
                    </p>
                    <p class="app-kpi-card__value text-xs"><?php echo e((string) $examCount); ?></p>
                    <p class="app-kpi-card__meta text-[9px]">Évaluations associées</p>
                </article>
            </div>
        </div>

        <aside>
            <section class="rounded-3xl border border-slate-200 bg-slate-50/80 p-2.5 dark:border-slate-800 dark:bg-slate-900/50">
                <?php if ($isTeacherPreview): ?>
                    <div class="flex items-start gap-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
                            <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg($isLocked ? 'lock' : 'check-circle'); ?></span>
                        </div>
                        <div>
                            <h3 class="text-[11px] font-semibold text-slate-900 dark:text-slate-100">État de soumission</h3>
                            <p class="mt-1 text-[10px] leading-5 text-slate-600 dark:text-slate-300">
                                <?php echo $isLocked ? "Le cours est verrouillé car il a déjà été soumis ou approuvé." : "Le cours reste modifiable. Vous pouvez encore ajuster le contenu avant l'envoi."; ?>
                            </p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <div class="flex items-start gap-3">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-200">
                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('user'); ?></span>
                            </div>
                            <div>
                                <h3 class="text-[11px] font-semibold text-slate-900 dark:text-slate-100">Contexte enseignant</h3>
                                <p class="mt-1 text-[10px] text-slate-600 dark:text-slate-300"><?php echo e($teacherName); ?></p>
                                <?php if ($categoryName !== ''): ?>
                                    <p class="mt-1 text-[9px] text-slate-500 dark:text-slate-400">Catégorie : <?php echo e($categoryName); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!empty($course['last_rejection_reason'])): ?>
                            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-[11px] text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                                Dernier motif de rejet : <?php echo e((string) $course['last_rejection_reason']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>
        </aside>
    </div>
</section>

<div class="grid gap-3 lg:grid-cols-2">
<section class="rounded-3xl border border-slate-200 bg-white/90 p-3 shadow-sm dark:border-slate-800 dark:bg-slate-950/50 sm:p-4">
    <div class="flex items-center justify-between gap-3">
        <div>
            <p class="text-[9px] uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Couverture</p>
            <h3 class="mt-1 text-[10px] font-semibold text-slate-900 dark:text-slate-100">Visuel du cours</h3>
        </div>
        <span class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-2 py-0.5 text-[9px] font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-950/60 dark:text-slate-300">
            <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('book-open'); ?></span>
            Interne
        </span>
    </div>
    <?php if ($coverUrl !== ''): ?>
        <img src="<?php echo e($coverUrl); ?>" alt="<?php echo e($coverAlt); ?>" class="mt-2.5 h-52 sm:h-64 lg:h-72 w-full rounded-2xl border border-slate-200 object-cover dark:border-slate-700">
    <?php else: ?>
        <div class="mt-2.5 flex h-44 sm:h-56 lg:h-64 items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white text-[11px] text-slate-500 dark:border-slate-700 dark:bg-slate-950/40 dark:text-slate-300">
            Aucune miniature n'est définie.
        </div>
    <?php endif; ?>
    <p class="mt-2.5 text-[9px] text-slate-500 dark:text-slate-400">
        <?php echo $isTeacherPreview ? "Aperçu réservé à l'enseignant pour vérifier le rendu avant publication." : "Aperçu réservé à l'administration pour le contrôle qualité et la validation."; ?>
    </p>
</section>

<section class="rounded-3xl border border-slate-200 bg-white/90 p-3 shadow-sm dark:border-slate-800 dark:bg-slate-950/50 sm:p-4">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-[9px] uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Repères pédagogiques</p>
            <h2 class="mt-1.5 text-[10px] font-display font-semibold text-slate-900 dark:text-slate-100 sm:text-xs">Objectifs, prérequis et matériel</h2>
            <p class="mt-1.5 text-[10px] text-slate-600 dark:text-slate-300">Vérifiez que les informations utiles aux apprenants sont complètes et compréhensibles.</p>
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[9px] font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300">
            <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('check-circle'); ?></span>
            <?php echo $hasPedagogicalInfo ? 'Informations disponibles' : 'À compléter'; ?>
        </span>
    </div>

    <?php if (!$hasPedagogicalInfo): ?>
        <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-3 text-[11px] text-slate-600 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-300">
            Ajoutez des objectifs pédagogiques, des prérequis ou une ressource générale pour renforcer la compréhension du cours.
        </div>
    <?php else: ?>
        <div class="mt-3 grid gap-2.5 lg:grid-cols-2">
            <?php if (!empty($course['objectives'])): ?>
                <article class="rounded-2xl border border-slate-200 bg-slate-50/80 p-2.5 dark:border-slate-800 dark:bg-slate-900/55">
                    <div class="flex items-center gap-2.5">
                        <span class="app-icon text-blue-600 dark:text-blue-300" aria-hidden="true"><?php echo dashboard_icon_svg('award'); ?></span>
                        <h3 class="text-[11px] font-semibold text-slate-900 dark:text-slate-100">Objectifs pédagogiques</h3>
                    </div>
                    <div class="mt-2 text-[10px] leading-5 text-slate-600 dark:text-slate-300"><?php echo $formatCourseList($course['objectives']); ?></div>
                </article>
            <?php endif; ?>

            <?php if (!empty($course['prerequisites'])): ?>
                <article class="rounded-2xl border border-slate-200 bg-slate-50/80 p-2.5 dark:border-slate-800 dark:bg-slate-900/55">
                    <div class="flex items-center gap-2.5">
                        <span class="app-icon text-blue-600 dark:text-blue-300" aria-hidden="true"><?php echo dashboard_icon_svg('check-circle'); ?></span>
                        <h3 class="text-[11px] font-semibold text-slate-900 dark:text-slate-100">Prérequis</h3>
                    </div>
                    <div class="mt-2 text-[10px] leading-5 text-slate-600 dark:text-slate-300"><?php echo $formatCourseList($course['prerequisites']); ?></div>
                </article>
            <?php endif; ?>

            <?php if (!empty($course['equipment'])): ?>
                <article class="rounded-2xl border border-slate-200 bg-slate-50/80 p-2.5 dark:border-slate-800 dark:bg-slate-900/55">
                    <div class="flex items-center gap-2.5">
                        <span class="app-icon text-blue-600 dark:text-blue-300" aria-hidden="true"><?php echo dashboard_icon_svg('settings'); ?></span>
                        <h3 class="text-[11px] font-semibold text-slate-900 dark:text-slate-100">Équipement recommandé</h3>
                    </div>
                    <div class="mt-2 text-[10px] leading-5 text-slate-600 dark:text-slate-300"><?php echo $formatCourseList($course['equipment']); ?></div>
                </article>
            <?php endif; ?>

            <?php if ($generalResourceUrl !== ''): ?>
                <article class="rounded-2xl border border-slate-200 bg-slate-50/80 p-2.5 dark:border-slate-800 dark:bg-slate-900/55">
                    <div class="flex items-center gap-2.5">
                        <span class="app-icon text-blue-600 dark:text-blue-300" aria-hidden="true"><?php echo dashboard_icon_svg('external-link'); ?></span>
                        <h3 class="text-[11px] font-semibold text-slate-900 dark:text-slate-100">Ressource générale</h3>
                    </div>
                    <p class="mt-2 text-[10px] text-slate-600 dark:text-slate-300">Ouvrez ici le lien de référence associé au cours.</p>
                    <a href="<?php echo e($generalResourceUrl); ?>" target="_blank" rel="noopener" class="mt-3 inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-[10px] font-semibold text-slate-700 transition hover:border-slate-400 dark:border-slate-700 dark:bg-slate-950/70 dark:text-slate-100 dark:hover:border-slate-500">
                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('external-link'); ?></span>
                        <span>Ouvrir la ressource</span>
                    </a>
                </article>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
</div>
