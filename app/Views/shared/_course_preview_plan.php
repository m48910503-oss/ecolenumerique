<?php
declare(strict_types=1);
?>
<section class="rounded-3xl border border-slate-200 bg-white/90 p-3 shadow-sm dark:border-slate-800 dark:bg-slate-950/50 sm:p-4">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-[9px] uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Structure détaillée</p>
            <h2 class="mt-1.5 text-[10px] font-display font-semibold text-slate-900 dark:text-slate-100 sm:text-xs">Plan du cours</h2>
            <p class="mt-1.5 text-[10px] text-slate-600 dark:text-slate-300">Ouvrez un chapitre pour vérifier son contenu.</p>
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[9px] font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300">
            <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('folder'); ?></span>
            <?php echo e((string) $chapterCount); ?> chapitre(s)
        </span>
    </div>
 
    <?php if ($chapters === []): ?>
        <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-3 text-[11px] text-slate-600 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-300">
            Aucun chapitre n'est encore défini pour ce cours.
        </div>
    <?php else: ?>
        <div class="mt-3 space-y-3">
            <?php foreach ($chapters as $chapter): ?>
                <?php
                $chapterId = (int) ($chapter['id'] ?? 0);
                $chapterLessons = is_array($lessonsByChapter[$chapterId] ?? null) ? $lessonsByChapter[$chapterId] : [];
                $chapterDocs = is_array($docsByChapter[$chapterId] ?? null) ? $docsByChapter[$chapterId] : [];
                $chapterMeetings = is_array($meetingsByChapter[$chapterId] ?? null) ? $meetingsByChapter[$chapterId] : [];
                $chapterResourceCount = count($chapterDocs);
                foreach ($chapterLessons as $lessonRow) {
                    $chapterResourceCount += count(is_array($docsByLesson[(int) ($lessonRow['id'] ?? 0)] ?? null) ? $docsByLesson[(int) ($lessonRow['id'] ?? 0)] : []);
                }
                ?>
                <details class="group rounded-3xl border border-slate-200 bg-slate-50/80 p-3 dark:border-slate-800 dark:bg-slate-900/55">
                    <summary class="list-none cursor-pointer select-none [&::-webkit-details-marker]:hidden">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <p class="text-[9px] uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Chapitre <?php echo e((string) ((int) ($chapter['position'] ?? 0))); ?></p>
                                <h3 class="mt-1.5 text-[11px] font-semibold text-slate-900 dark:text-slate-100 sm:text-xs"><?php echo e((string) ($chapter['title'] ?? 'Chapitre')); ?></h3>
                                <?php if (!empty($chapter['description'])): ?>
                                    <p class="mt-1.5 text-[10px] leading-5 text-slate-600 dark:text-slate-300"><?php echo e((string) $chapter['description']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="flex flex-wrap gap-1.5">
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-2 py-0.5 text-[9px] font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-950/60 dark:text-slate-300">
                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('play-circle'); ?></span>
                                    <?php echo e((string) count($chapterLessons)); ?> leçon(s)
                                </span>
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-2 py-0.5 text-[9px] font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-950/60 dark:text-slate-300">
                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('folder'); ?></span>
                                    <?php echo e((string) $chapterResourceCount); ?> ressource(s)
                                </span>
                                <?php if ($chapterMeetings !== []): ?>
                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-2 py-0.5 text-[9px] font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-950/60 dark:text-slate-300">
                                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('calendar'); ?></span>
                                        <?php echo e((string) count($chapterMeetings)); ?> rendez-vous
                                    </span>
                                <?php endif; ?>
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 px-2 py-0.5 text-[9px] font-semibold text-blue-700 dark:border-blue-500/40 dark:bg-blue-500/10 dark:text-blue-200">
                                    <span class="app-icon transition-transform duration-200 group-open:rotate-180" aria-hidden="true"><?php echo dashboard_icon_svg('chevron-down'); ?></span>
                                    <span class="group-open:hidden">Ouvrir</span>
                                    <span class="hidden group-open:inline">Masquer</span>
                                </span>
                            </div>
                        </div>
                    </summary>

                    <div class="mt-3 space-y-2.5 border-t border-slate-200/80 pt-3 dark:border-slate-800/80">
                        <?php if ($chapterLessons === []): ?>
                            <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-3 py-2.5 text-[11px] text-slate-600 dark:border-slate-700 dark:bg-slate-950/40 dark:text-slate-300">
                                Aucune leçon n'est encore associée à ce chapitre.
                            </div>
                        <?php else: ?>
                            <?php foreach ($chapterLessons as $lesson): ?>
                                <?php
                                $lessonId = (int) ($lesson['id'] ?? 0);
                                $lessonType = (string) ($lesson['lesson_type'] ?? 'document');
                                $lessonTypeLabel = $lessonType === 'video' ? 'Vidéo YouTube' : 'Document';
                                $lessonIcon = $lessonType === 'video' ? 'play-circle' : 'file-text';
                                $youtubeUrl = trim((string) ($lesson['youtube_url'] ?? ''));
                                $youtubeId = $youtubeUrl !== '' ? $extractYoutubeId($youtubeUrl) : null;
                                $lessonDocumentUrl = $resolveUrl((string) ($lesson['document_path'] ?? ''));
                                $lessonDocs = is_array($docsByLesson[$lessonId] ?? null) ? $docsByLesson[$lessonId] : [];
                                ?>
                                <article class="rounded-2xl border border-slate-200 bg-white p-2.5 dark:border-slate-700 dark:bg-slate-950/60">
                                    <div class="flex flex-col gap-3">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[9px] font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300">
                                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg($lessonIcon); ?></span>
                                                    Leçon <?php echo e((string) ((int) ($lesson['position'] ?? 0))); ?> · <?php echo e($lessonTypeLabel); ?>
                                                </span>
                                                <?php if (!is_null($lesson['duration_estimate_min'] ?? null) && (int) ($lesson['duration_estimate_min'] ?? 0) > 0) : ?>
                                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[9px] font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300">
                                                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('clock'); ?></span>
                                                        <?php echo e($formatShortDuration($lesson['duration_estimate_min'] ?? null)); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <h4 class="mt-1.5 text-[10px] font-semibold text-slate-900 dark:text-slate-100 sm:text-[11px]"><?php echo e((string) ($lesson['title'] ?? 'Leçon')); ?></h4>
                                            <?php if (!empty($lesson['description'])): ?>
                                                <p class="mt-1.5 text-[10px] leading-5 text-slate-600 dark:text-slate-300"><?php echo e((string) $lesson['description']); ?></p>
                                            <?php endif; ?>
                                        </div>

                                        <?php if ($lessonType === 'video' && $youtubeId): ?>
                                            <div class="max-w-3xl overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700">
                                                <div class="aspect-video max-h-[24rem]">
                                                    <iframe class="h-full w-full" src="https://www.youtube.com/embed/<?php echo e((string) $youtubeId); ?>" title="Aperçu de la vidéo" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen></iframe>
                                                </div>
                                            </div>
                                        <?php elseif ($lessonType === 'video' && $youtubeUrl !== ''): ?>
                                            <a href="<?php echo e($youtubeUrl); ?>" target="_blank" rel="noopener" class="inline-flex w-fit items-center gap-2 rounded-xl border border-slate-300 bg-slate-50 px-3 py-1.5 text-[10px] font-semibold text-slate-700 transition hover:border-slate-400 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100 dark:hover:border-slate-500">
                                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('external-link'); ?></span>
                                                <span>Ouvrir la vidéo</span>
                                            </a>
                                        <?php elseif ($lessonType === 'document' && $lessonDocumentUrl !== ''): ?>
                                            <a href="<?php echo e($lessonDocumentUrl); ?>" target="_blank" rel="noopener" class="inline-flex w-fit items-center gap-2 rounded-xl border border-slate-300 bg-slate-50 px-3 py-1.5 text-[10px] font-semibold text-slate-700 transition hover:border-slate-400 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100 dark:hover:border-slate-500">
                                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('external-link'); ?></span>
                                                <span>Ouvrir le document</span>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($lessonDocs !== []): ?>
                                            <div class="space-y-2">
                                                <p class="text-[9px] uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Ressources liées à la leçon</p>
                                                <?php foreach ($lessonDocs as $doc): ?>
                                                    <?php $lessonDocUrl = $resolveUrl((string) ($doc['file_path'] ?? '')); ?>
                                                    <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-2.5 dark:border-slate-800 dark:bg-slate-900/55">
                                                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                                            <div class="min-w-0">
                                                                <p class="text-[9px] uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400"><?php echo e($docLabel($doc['type'] ?? null, $doc['mime'] ?? null)); ?></p>
                                                                <p class="mt-1 text-[10px] font-semibold text-slate-900 dark:text-slate-100"><?php echo e((string) ($doc['title'] ?? 'Ressource')); ?></p>
                                                            </div>
                                                            <?php if ($lessonDocUrl !== ''): ?>
                                                                <a href="<?php echo e($lessonDocUrl); ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-2.5 py-1.5 text-[9px] font-semibold text-slate-700 transition hover:border-slate-400 dark:border-slate-700 dark:bg-slate-950/60 dark:text-slate-100 dark:hover:border-slate-500">
                                                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('external-link'); ?></span>
                                                                    <span>Ouvrir</span>
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if ($chapterDocs !== []): ?>
                            <div class="space-y-2">
                                <p class="text-[9px] uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Ressources du chapitre</p>
                                <?php foreach ($chapterDocs as $doc): ?>
                                    <?php $chapterDocUrl = $resolveUrl((string) ($doc['file_path'] ?? '')); ?>
                                    <div class="rounded-2xl border border-slate-200 bg-white p-2.5 dark:border-slate-700 dark:bg-slate-950/60">
                                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                            <div class="min-w-0">
                                                <p class="text-[9px] uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400"><?php echo e($docLabel($doc['type'] ?? null, $doc['mime'] ?? null)); ?></p>
                                                <p class="mt-1 text-[10px] font-semibold text-slate-900 dark:text-slate-100"><?php echo e((string) ($doc['title'] ?? 'Ressource')); ?></p>
                                            </div>
                                            <?php if ($chapterDocUrl !== ''): ?>
                                                <a href="<?php echo e($chapterDocUrl); ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-slate-50 px-2.5 py-1.5 text-[9px] font-semibold text-slate-700 transition hover:border-slate-400 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100 dark:hover:border-slate-500">
                                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('external-link'); ?></span>
                                                    <span>Ouvrir</span>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>


