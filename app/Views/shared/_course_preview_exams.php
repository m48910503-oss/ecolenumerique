<?php
declare(strict_types=1);

if ($exams === []) {
    return;
}
?>
<section id="evaluations" class="admin-course-panel admin-course-anchor-target p-4 sm:p-5">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-[0.72rem] font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Évaluations</p>
            <h2 class="mt-2 text-lg font-black text-slate-900 dark:text-slate-100">Examens associés</h2>
        </div>
        <span class="admin-course-badge admin-course-badge--neutral">
            <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('file-text'); ?></span>
            <?php echo e((string) $examCount); ?> examen(s)
        </span>
    </div>
 
    <div class="mt-4 grid gap-3 lg:grid-cols-2">
        <?php foreach ($exams as $exam): ?>
            <?php
            $examType = (string) ($exam['type'] ?? '');
            $examPreviewUrl = site_url('teacher/exam_preview?exam_id=' . (int) ($exam['id'] ?? 0));
            ?>
            <article class="rounded-2xl border border-slate-200 bg-slate-50/80 p-3 dark:border-slate-800 dark:bg-slate-900/55">
                <div class="flex items-start gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-200">
                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('file-text'); ?></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-sm font-black text-slate-900 dark:text-slate-100"><?php echo e((string) ($exam['title'] ?? 'Examen')); ?></h3>
                            <span class="admin-course-badge admin-course-badge--neutral"><?php echo e(strtoupper($examType)); ?></span>
                        </div>
                        <?php if (!empty($exam['description'])): ?>
                            <p class="mt-1.5 text-sm leading-6 text-slate-600 dark:text-slate-300"><?php echo e((string) $exam['description']); ?></p>
                        <?php endif; ?>
                        <div class="mt-2 flex flex-wrap gap-2 text-xs text-slate-500 dark:text-slate-400">
                            <?php if (!empty($exam['duration_min'])): ?>
                                <span>Durée : <?php echo e(format_duration_minutes_hm((int) $exam['duration_min'])); ?></span>
                            <?php endif; ?>
                            <span>Note minimale : <?php echo e((string) ($exam['min_score'] ?? 0)); ?></span>
                            <span>Tentatives : <?php echo e((string) ($exam['max_attempts'] ?? 0)); ?></span>
                        </div>

                        <?php if ($examType === 'drive'): ?>
                            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Remise de fichier prévue dans l'espace Drive de la plateforme.</p>
                        <?php elseif ($examType === 'meeting'): ?>
                            <p class="mt-2 break-all text-sm text-slate-600 dark:text-slate-300">
                                Mode : <?php echo e((string) (($exam['meeting_mode'] ?? '') === 'online' ? 'En ligne' : 'Sur place')); ?>
                                <?php if (!empty($exam['meeting_link'])): ?>
                                    · Lien : <?php echo e((string) $exam['meeting_link']); ?>
                                <?php endif; ?>
                                <?php if (!empty($exam['meeting_place'])): ?>
                                    · Lieu : <?php echo e((string) $exam['meeting_place']); ?>
                                <?php endif; ?>
                            </p>
                        <?php elseif ($examType === 'qcm'): ?>
                            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">QCM prêt. Vérifiez les questions.</p>
                        <?php endif; ?>

                        <div class="mt-3">
                            <a href="<?php echo e($examPreviewUrl); ?>" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-400 dark:border-slate-700 dark:bg-slate-950/60 dark:text-slate-100 dark:hover:border-slate-500">
                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('external-link'); ?></span>
                                <span>Voir l'examen</span>
                            </a>
                        </div>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>


