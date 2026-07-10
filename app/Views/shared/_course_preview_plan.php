<?php
declare(strict_types=1);

$truncateText = isset($truncateText) && is_callable($truncateText) ? $truncateText : static function (string $value, int $limit = 120): string {
    $value = trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode($value, ENT_QUOTES, 'UTF-8'))) ?? '');
    if ($value === '') {
        return '';
    }

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($value) <= $limit) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, max(0, $limit - 3))) . '...';
    }

    if (strlen($value) <= $limit) {
        return $value;
    }

    return rtrim(substr($value, 0, max(0, $limit - 3))) . '...';
};
?>
<section id="structure" class="admin-course-panel admin-course-anchor-target p-4 sm:p-5">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-[0.72rem] font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Structure détaillée</p>
            <h2 class="mt-2 text-lg font-black text-slate-900 dark:text-slate-100">Plan du cours</h2>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Ouvrez un chapitre.</p>
        </div>
        <span class="admin-course-badge admin-course-badge--neutral">
            <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('folder'); ?></span>
            <?php echo e((string) $chapterCount); ?> chapitre(s)
        </span>
    </div>
 
    <?php if ($chapters === []): ?>
        <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-3 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-300">
            Aucun chapitre.
        </div>
    <?php else: ?>
        <div class="mt-4 space-y-3">
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
                <details class="group rounded-2xl border border-slate-200 bg-slate-50/80 p-3 dark:border-slate-800 dark:bg-slate-900/55">
                    <summary class="list-none cursor-pointer select-none [&::-webkit-details-marker]:hidden">
                        <div class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
                            <div class="min-w-0">
                                <p class="text-[0.72rem] font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Chapitre <?php echo e((string) ((int) ($chapter['position'] ?? 0))); ?></p>
                                <h3 class="mt-1.5 text-base font-black text-slate-900 dark:text-slate-100"><?php echo e((string) ($chapter['title'] ?? 'Chapitre')); ?></h3>
                                <?php if (!empty($chapter['description'])): ?>
                                    <p class="mt-1.5 text-sm leading-6 text-slate-600 dark:text-slate-300"><?php echo e((string) $chapter['description']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <span class="admin-course-badge admin-course-badge--neutral">
                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('play-circle'); ?></span>
                                    <?php echo e((string) count($chapterLessons)); ?> leçon(s)
                                </span>
                                <span class="admin-course-badge admin-course-badge--neutral">
                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('folder'); ?></span>
                                    <?php echo e((string) $chapterResourceCount); ?> ressource(s)
                                </span>
                                <?php if ($chapterMeetings !== []): ?>
                                    <span class="admin-course-badge admin-course-badge--neutral">
                                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('calendar'); ?></span>
                                        <?php echo e((string) count($chapterMeetings)); ?> rendez-vous
                                    </span>
                                <?php endif; ?>
                                <span class="admin-course-badge admin-course-badge--pending">
                                    <span class="app-icon transition-transform duration-200 group-open:rotate-180" aria-hidden="true"><?php echo dashboard_icon_svg('chevron-down'); ?></span>
                                    <span class="group-open:hidden">Ouvrir</span>
                                    <span class="hidden group-open:inline">Masquer</span>
                                </span>
                            </div>
                        </div>
                    </summary>

                    <div class="mt-4 space-y-4 border-t border-slate-200/80 pt-4 dark:border-slate-800/80">
                        <?php if ($chapterLessons === []): ?>
                            <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-950/40 dark:text-slate-300">
                                Aucune leçon associée.
                            </div>
                        <?php else: ?>
                            <div class="space-y-2.5">
                                <?php foreach ($chapterLessons as $lesson): ?>
                                    <?php
                                    $lessonId = (int) ($lesson['id'] ?? 0);
                                    $lessonType = (string) ($lesson['lesson_type'] ?? 'document');
                                    $lessonTypeLabel = $lessonType === 'video' ? 'Vidéo YouTube' : 'Document';
                                    $lessonIcon = $lessonType === 'video' ? 'play-circle' : 'file-text';
                                    $youtubeUrl = trim((string) ($lesson['youtube_url'] ?? ''));
                                    $lessonDocumentUrl = $resolveUrl((string) ($lesson['document_path'] ?? ''));
                                    $lessonDocs = is_array($docsByLesson[$lessonId] ?? null) ? $docsByLesson[$lessonId] : [];
                                    ?>
                                    <article class="rounded-2xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-950/60">
                                        <div class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="admin-course-badge admin-course-badge--neutral">
                                                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg($lessonIcon); ?></span>
                                                        Leçon <?php echo e((string) ((int) ($lesson['position'] ?? 0))); ?> · <?php echo e($lessonTypeLabel); ?>
                                                    </span>
                                                    <?php if (!is_null($lesson['duration_estimate_min'] ?? null) && (int) ($lesson['duration_estimate_min'] ?? 0) > 0) : ?>
                                                        <span class="admin-course-badge admin-course-badge--neutral">
                                                            <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('clock'); ?></span>
                                                            <?php echo e($formatShortDuration($lesson['duration_estimate_min'] ?? null)); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>

                                                <h4 class="mt-2 text-sm font-black text-slate-900 dark:text-slate-100"><?php echo e((string) ($lesson['title'] ?? 'Leçon')); ?></h4>
                                                <?php if (!empty($lesson['description'])): ?>
                                                    <p class="mt-1.5 text-sm leading-6 text-slate-600 dark:text-slate-300"><?php echo e($truncateText((string) $lesson['description'], 110)); ?></p>
                                                <?php endif; ?>

                                                <?php if ($lessonDocs !== []): ?>
                                                    <div class="mt-3 flex flex-wrap gap-2">
                                                        <?php foreach ($lessonDocs as $doc): ?>
                                                            <?php $lessonDocUrl = $resolveUrl((string) ($doc['file_path'] ?? '')); ?>
                                                            <?php if ($lessonDocUrl === ''): ?>
                                                                <?php continue; ?>
                                                            <?php endif; ?>
                                                            <a href="<?php echo e($lessonDocUrl); ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-slate-300 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100 dark:hover:border-slate-500">
                                                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('external-link'); ?></span>
                                                                <span><?php echo e((string) ($doc['title'] ?? $docLabel($doc['type'] ?? null, $doc['mime'] ?? null))); ?></span>
                                                            </a>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="flex shrink-0 flex-wrap gap-2 xl:justify-end">
                                                <?php if ($lessonType === 'video' && $youtubeUrl !== ''): ?>
                                                    <a href="<?php echo e($youtubeUrl); ?>" target="_blank" rel="noopener" class="admin-course-preview-button inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm font-semibold transition">
                                                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('external-link'); ?></span>
                                                        <span>Voir la vidéo</span>
                                                    </a>
                                                <?php elseif ($lessonType === 'document' && $lessonDocumentUrl !== ''): ?>
                                                    <a href="<?php echo e($lessonDocumentUrl); ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-400 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100 dark:hover:border-slate-500">
                                                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('external-link'); ?></span>
                                                        <span>Ouvrir le document</span>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($chapterDocs !== [] || $chapterMeetings !== []): ?>
                            <div class="grid gap-3 lg:grid-cols-2">
                                <?php if ($chapterDocs !== []): ?>
                                    <section class="rounded-2xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-950/60">
                                        <p class="text-[0.72rem] font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Ressources du chapitre</p>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <?php foreach ($chapterDocs as $doc): ?>
                                                <?php $chapterDocUrl = $resolveUrl((string) ($doc['file_path'] ?? '')); ?>
                                                <?php if ($chapterDocUrl === ''): ?>
                                                    <?php continue; ?>
                                                <?php endif; ?>
                                                <a href="<?php echo e($chapterDocUrl); ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-slate-300 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100 dark:hover:border-slate-500">
                                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('external-link'); ?></span>
                                                    <span><?php echo e((string) ($doc['title'] ?? $docLabel($doc['type'] ?? null, $doc['mime'] ?? null))); ?></span>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </section>
                                <?php endif; ?>

                                <?php if ($chapterMeetings !== []): ?>
                                    <section class="rounded-2xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-950/60">
                                        <p class="text-[0.72rem] font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Rendez-vous liés</p>
                                        <div class="mt-3 space-y-2">
                                            <?php foreach ($chapterMeetings as $meeting): ?>
                                                <div class="rounded-xl border border-slate-200 bg-slate-50/85 px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-200">
                                                    <p class="font-semibold text-slate-900 dark:text-white"><?php echo e((string) ($meeting['title'] ?? 'Rendez-vous')); ?></p>
                                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400"><?php echo e((string) (($meeting['mode'] ?? '') === 'online' ? 'En ligne' : 'En présentiel')); ?></p>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </section>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>


