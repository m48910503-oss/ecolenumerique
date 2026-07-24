<?php
declare(strict_types=1);
?>
<section id="reviews-moderation" class="course-preview-anchor space-y-4 rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-950/45">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
            <p class="text-xs font-bold uppercase tracking-[0.24em] text-slate-400">Avis</p>
            <h2 class="mt-1 text-xl font-black text-slate-950 dark:text-white">Avis et modération</h2>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                Contrôlez les retours apprenants visibles sur la fiche publique du cours.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('star'); ?></span>
                Total <?php echo e((string) count($ratingsForModeration)); ?>
            </span>
            <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('check-circle'); ?></span>
                Visibles <?php echo e((string) $visibleReviewCount); ?>
            </span>
            <span class="inline-flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('alert-circle'); ?></span>
                Masqués <?php echo e((string) $hiddenReviewCount); ?>
            </span>
        </div>
    </div>

    <?php if ($moderationSuccess !== ''): ?>
        <div class="rounded-[1.25rem] border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
            <?php echo e($moderationSuccess); ?>
        </div>
    <?php endif; ?>

    <?php if ($moderationErrors !== []): ?>
        <div class="rounded-[1.25rem] border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200">
            <?php foreach ($moderationErrors as $error): ?>
                <p><?php echo e((string) $error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!$ratingsModerationReady): ?>
        <div class="rounded-[1.25rem] border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
            La modération des avis nécessite la migration <code>database/schema_v42.sql</code>.
        </div>
    <?php endif; ?>

    <?php if ($ratingsForModeration === []): ?>
        <div class="rounded-[1.25rem] border border-dashed border-slate-300 bg-white p-5 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-400">
            Aucun avis n'a encore été publié pour ce cours.
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($ratingsForModeration as $review): ?>
                <?php
                $isHidden = (int) ($review['is_hidden_by_teacher'] ?? 0) === 1;
                $reviewerName = trim((string) (($review['first_name'] ?? '') . ' ' . ($review['last_name'] ?? '')));
                $reviewDate = $formatDate((string) ($review['created_at'] ?? ''));
                $hiddenDate = $formatDate((string) ($review['hidden_at'] ?? ''));
                ?>
                <article class="rounded-[1.25rem] border bg-white p-4 shadow-sm dark:bg-slate-900/70 <?php echo $isHidden ? 'border-rose-200 dark:border-rose-500/30' : 'border-slate-200 dark:border-slate-800'; ?>">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-black text-slate-950 dark:text-white"><?php echo e($reviewerName !== '' ? $reviewerName : 'Utilisateur'); ?></p>
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('star'); ?></span>
                                    <?php echo e((string) ((int) ($review['rating'] ?? 0))); ?>/5
                                </span>
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold <?php echo $isHidden ? 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-200' : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200'; ?>">
                                    <?php echo $isHidden ? 'Masqué' : 'Visible'; ?>
                                </span>
                            </div>
                            <p class="mt-2 text-xs font-semibold text-slate-400">
                                Publié le <?php echo e($reviewDate !== '' ? $reviewDate : 'date indisponible'); ?>
                                <?php if ($isHidden && $hiddenDate !== ''): ?>
                                    · Masqué le <?php echo e($hiddenDate); ?>
                                <?php endif; ?>
                            </p>
                            <div class="mt-3 text-sm leading-6 text-slate-700 dark:text-slate-200">
                                <?php if (!empty($review['comment'])): ?>
                                    <?php echo nl2br(e((string) $review['comment'])); ?>
                                <?php else: ?>
                                    <span class="text-slate-500 dark:text-slate-400">Aucun commentaire associé à cette note.</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($ratingsModerationReady): ?>
                            <form method="post" class="shrink-0">
                                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                <input type="hidden" name="rating_id" value="<?php echo e((string) ((int) ($review['id'] ?? 0))); ?>">
                                <input type="hidden" name="action" value="<?php echo $isHidden ? 'unhide_review' : 'hide_review'; ?>">
                                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-2xl border px-4 py-2 text-sm font-bold shadow-sm transition hover:-translate-y-0.5 <?php echo $isHidden ? 'border-emerald-200 bg-white text-emerald-700 hover:border-emerald-300 dark:border-emerald-500/30 dark:bg-slate-950/60 dark:text-emerald-200' : 'border-rose-200 bg-white text-rose-700 hover:border-rose-300 dark:border-rose-500/30 dark:bg-slate-950/60 dark:text-rose-200'; ?>">
                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg($isHidden ? 'check-circle' : 'x'); ?></span>
                                    <span><?php echo $isHidden ? 'Rendre visible' : 'Masquer'; ?></span>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
