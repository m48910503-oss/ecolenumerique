<?php
declare(strict_types=1);

if ($videoPlaylist === []) {
    return;
}
$featuredVideo = $videoPlaylist[0];
?>
<section class="rounded-3xl border border-slate-200 bg-white/90 p-3 shadow-sm dark:border-slate-800 dark:bg-slate-950/50 sm:p-4">
    <div class="flex justify-end">
        <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[10px] font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300">
            <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('play-circle'); ?></span>
            <?php echo e((string) $videoCount); ?> vidéo(s)
        </span>
    </div>
 
    <div class="mt-3 grid gap-3 xl:grid-cols-[minmax(0,1fr)_minmax(12rem,0.72fr)]">
        <div class="max-w-3xl space-y-2.5">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-black shadow-sm dark:border-slate-800">
                <div class="aspect-video">
                    <iframe class="h-full w-full" src="https://www.youtube.com/embed/<?php echo e((string) ($featuredVideo['yt_id'] ?? '')); ?>>modestbranding=1&rel=0&playsinline=1" title="Aperçu vidéo du cours" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen"</iframe>
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-2.5 text-[12px] text-slate-600 dark:border-slate-800 dark:bg-slate-900/55 dark:text-slate-300">
                <p class="text-[13px] font-semibold text-slate-900 dark:text-slate-100"><?php echo e((string) ($featuredVideo['title'] ?? 'Vidéo')); ?></p>
                <p class="mt-1 text-[11px]">Chapitre <?php echo e((string) ((int) ($featuredVideo['chapter'] ?? 0))); ?> · <?php echo e($formatShortDuration($featuredVideo['duration'] ?? null)); ?></p>
            </div>
        </div>

        <div class="space-y-2">
            <?php foreach ($videoPlaylist as $video): ?>
                <a href="<?php echo e('https://www.youtube.com/watchv=' . (string) ($video['yt_id'] ?? '')); ?>" target="_blank" rel="noopener" class="flex items-start gap-2.5 rounded-2xl border border-slate-200 bg-slate-50/80 p-2.5 transition hover:border-blue-300 hover:bg-white dark:border-slate-800 dark:bg-slate-900/55 dark:hover:border-blue-500/40 dark:hover:bg-slate-900/75">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-200">
                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('play-circle'); ?></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[12px] font-semibold text-slate-900 dark:text-slate-100"><?php echo e((string) ($video['title'] ?? 'Vidéo')); ?></p>
                        <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Chapitre <?php echo e((string) ((int) ($video['chapter'] ?? 0))); ?> · <?php echo e($formatShortDuration($video['duration'] ?? null)); ?></p>
                    </div>
                    <span class="app-icon shrink-0 text-slate-400 dark:text-slate-500" aria-hidden="true"><?php echo dashboard_icon_svg('external-link'); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
