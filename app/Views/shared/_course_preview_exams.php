<?php
declare(strict_types=1);

if ($exams === []) {
    return;
}
?>
<section class="rounded-3xl border border-slate-200 bg-white/90 p-3 shadow-sm dark:border-slate-800 dark:bg-slate-950/50 sm:p-4">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-[9px] uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Évaluations</p>
            <h2 class="mt-1.5 text-[10px] font-display font-semibold text-slate-900 dark:text-slate-100 sm:text-xs">Examens associés</h2>
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[9px] font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300">
            <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('file-text'); ?></span>
            <?php echo e((string) $examCount); ?> examen(s)
        </span>
    </div>
 
    <div class="mt-3 grid gap-2.5 lg:grid-cols-2">
        <?php foreach ($exams as $exam): ?>
            <?php
            $examType = (string) ($exam['type'] ?? '');
            $examPreviewUrl = site_url('teacher/exam_preview?exam_id=' . (int) ($exam['id'] ?? 0));
            ?>
            <article class="rounded-2xl border border-slate-200 bg-slate-50/80 p-2.5 dark:border-slate-800 dark:bg-slate-900/55">
                <div class="flex items-start gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-200">
                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('file-text'); ?></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-[11px] font-semibold text-slate-900 dark:text-slate-100"><?php echo e((string) ($exam['title'] ?? 'Examen')); ?></h3>
                            <span class="inline-flex rounded-full border border-slate-200 bg-white px-2 py-0.5 text-[9px] font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-950/60 dark:text-slate-300"><?php echo e(strtoupper($examType)); ?></span>
                        </div>
                        <?php if (!empty($exam['description'])): ?>
                            <p class="mt-1.5 text-[10px] leading-5 text-slate-600 dark:text-slate-300"><?php echo e((string) $exam['description']); ?></p>
                        <?php endif; ?>
                        <div class="mt-2 flex flex-wrap gap-2 text-[9px] text-slate-500 dark:text-slate-400">
                            <?php if (!empty($exam['duration_min'])): ?>
                                <span>Durée : <?php echo e(format_duration_minutes_hm((int) $exam['duration_min'])); ?></span>
                            <?php endif; ?>
                            <span>Note minimale : <?php echo e((string) ($exam['min_score'] ?? 0)); ?></span>
                            <span>Tentatives : <?php echo e((string) ($exam['max_attempts'] ?? 0)); ?></span>
                        </div>

                        <?php if ($examType === 'drive'): ?>
                            <p class="mt-2 text-[10px] text-slate-600 dark:text-slate-300">Remise de fichier prévue dans l'espace Drive de la plateforme.</p>
                        <?php elseif ($examType === 'meeting'): ?>
                            <p class="mt-2 text-[10px] text-slate-600 dark:text-slate-300">
                                Mode : <?php echo e((string) (($exam['meeting_mode'] ?? '') === 'online' ? 'En ligne' : 'Sur place')); ?>
                                <?php if (!empty($exam['meeting_link'])): ?>
                                    · Lien : <?php echo e((string) $exam['meeting_link']); ?>
                                <?php endif; ?>
                                <?php if (!empty($exam['meeting_place'])): ?>
                                    · Lieu : <?php echo e((string) $exam['meeting_place']); ?>
                                <?php endif; ?>
                            </p>
                        <?php elseif ($examType === 'qcm'): ?>
                            <p class="mt-2 text-[10px] text-slate-600 dark:text-slate-300">QCM configuré. Ouvrez l'aperçu détaillé pour vérifier les questions et les versions.</p>
                        <?php endif; ?>

                        <div class="mt-3">
                            <a href="<?php echo e($examPreviewUrl); ?>" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-[10px] font-semibold text-slate-700 transition hover:border-slate-400 dark:border-slate-700 dark:bg-slate-950/60 dark:text-slate-100 dark:hover:border-slate-500">
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


