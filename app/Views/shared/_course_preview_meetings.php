<?php
declare(strict_types=1);

if ($meetings === []) {
    return;
}
?>
<section class="rounded-3xl border border-slate-200 bg-white/90 p-3 shadow-sm dark:border-slate-800 dark:bg-slate-950/50 sm:p-4">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-[9px] uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Sessions</p>
            <h2 class="mt-1.5 text-[10px] font-display font-semibold text-slate-900 dark:text-slate-100 sm:text-xs">Rendez-vous du cours</h2>
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[9px] font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300">
            <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('calendar'); ?></span>
            <?php echo e((string) $meetingCount); ?> rendez-vous
        </span>
    </div>
 
    <div class="mt-3 grid gap-2.5 lg:grid-cols-2">
        <?php foreach ($meetings as $meeting): ?>
            <article class="rounded-2xl border border-slate-200 bg-slate-50/80 p-2.5 dark:border-slate-800 dark:bg-slate-900/55">
                <div class="flex items-start gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-200">
                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('calendar'); ?></span>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-[11px] font-semibold text-slate-900 dark:text-slate-100"><?php echo e((string) ($meeting['title'] ?? 'Rendez-vous')); ?></h3>
                        <p class="mt-1 text-[10px] text-slate-600 dark:text-slate-300">
                            <?php echo e((string) (($meeting['mode'] ?? '') === 'online' ? 'En ligne' : 'En présentiel')); ?>
                            <?php if (!empty($meeting['chapter_title'])): ?>
                                · Chapitre : <?php echo e((string) $meeting['chapter_title']); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>


