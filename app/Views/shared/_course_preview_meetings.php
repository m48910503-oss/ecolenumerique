<?php
declare(strict_types=1);

if ($meetings === []) {
    return;
}
?>
<section id="sessions" class="admin-course-panel admin-course-anchor-target p-4 sm:p-5">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-[0.72rem] font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Sessions</p>
            <h2 class="mt-2 text-lg font-black text-slate-900 dark:text-slate-100">Rendez-vous du cours</h2>
        </div>
        <span class="admin-course-badge admin-course-badge--neutral">
            <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('calendar'); ?></span>
            <?php echo e((string) $meetingCount); ?> rendez-vous
        </span>
    </div>
 
    <div class="mt-4 grid gap-3 lg:grid-cols-2">
        <?php foreach ($meetings as $meeting): ?>
            <article class="rounded-2xl border border-slate-200 bg-slate-50/80 p-3 dark:border-slate-800 dark:bg-slate-900/55">
                <div class="flex items-start gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-200">
                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('calendar'); ?></span>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm font-black text-slate-900 dark:text-slate-100"><?php echo e((string) ($meeting['title'] ?? 'Rendez-vous')); ?></h3>
                        <p class="mt-1.5 text-sm text-slate-600 dark:text-slate-300">
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


