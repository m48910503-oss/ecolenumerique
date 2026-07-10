<?php
$courseCard = is_array($courseCard ?? null) ? $courseCard : [];
$cardContext = trim((string) ($cardContext ?? 'catalogue'));
$cardAnimation = trim((string) ($cardAnimation ?? ''));
$withCatalogData = !empty($withCatalogData);
$cardStyle = $cardAnimation !== '' ? ' style="--mk-delay:' . e($cardAnimation) . '"' : '';
?>
<article
    class="mk-course-card<?php echo $cardAnimation !== '' ? ' mk-reveal' : ''; ?>"
    <?php echo $cardStyle; ?>
    <?php if ($withCatalogData): ?>
        data-course-card
        data-search="<?php echo e((string) ($courseCard['searchText'] ?? '')); ?>"
        data-category="<?php echo e((string) ($courseCard['categoryKey'] ?? '')); ?>"
        data-level="<?php echo e((string) ($courseCard['levelKey'] ?? '')); ?>"
        data-price="<?php echo e((string) ($courseCard['priceRaw'] ?? 0)); ?>"
        data-rating="<?php echo e((string) ($courseCard['rating'] ?? 0)); ?>"
        data-created="<?php echo e((string) ($courseCard['createdTs'] ?? 0)); ?>"
        data-duration="<?php echo e((string) ($courseCard['durationMin'] ?? 0)); ?>"
    <?php endif; ?>
>
    <a class="mk-course-card__link" href="<?php echo e((string) ($courseCard['url'] ?? '#')); ?>">
        <div class="mk-course-card__media">
            <div class="mk-course-card__media-top">
                <span class="mk-badge mk-badge--surface"><?php echo e((string) ($courseCard['category'] ?? 'Formation')); ?></span>
                <span class="mk-badge mk-badge--accent"><?php echo e((string) ($courseCard['levelLabel'] ?? 'Tous niveaux')); ?></span>
            </div>
            <?php if ((string) ($courseCard['cover'] ?? '') !== ''): ?>
                <img
                    src="<?php echo e((string) ($courseCard['cover'] ?? '')); ?>"
                    alt="<?php echo e((string) ($courseCard['coverAlt'] ?? 'Couverture du cours')); ?>"
                    loading="lazy"
                    decoding="async"
                >
            <?php else: ?>
                <div class="mk-course-card__fallback">
                    <span class="mk-course-card__fallback-kicker"><?php echo e((string) ($courseCard['statusLabel'] ?? 'Accès premium')); ?></span>
                    <strong><?php echo e((string) ($courseCard['title'] ?? 'Cours numérique')); ?></strong>
                </div>
            <?php endif; ?>
        </div>

        <div class="mk-course-card__body">
            <div class="mk-course-card__signals">
                <span class="mk-course-card__signal<?php echo ((int) ($courseCard['ratingCount'] ?? 0) === 0) ? ' mk-course-card__signal--new' : ''; ?>"><?php echo e((string) ($courseCard['ratingMeta'] ?? 'Nouveau')); ?></span>
                <span class="mk-course-card__signal mk-course-card__signal--duration"><?php echo e((string) ($courseCard['durationLabel'] ?? 'À votre rythme')); ?></span>
            </div>

            <div class="mk-course-card__content">
                <h3 class="mk-course-card__title"><?php echo e((string) ($courseCard['title'] ?? 'Cours numérique')); ?></h3>
                <p class="mk-course-card__description"><?php echo e((string) ($courseCard['description'] ?? '')); ?></p>
            </div>

            <div class="mk-course-card__meta">
                <span>Par <?php echo e((string) ($courseCard['teacher'] ?? 'Formateur')); ?></span>
                <span><?php echo e((string) ($courseCard['statusLabel'] ?? 'Accès premium')); ?></span>
            </div>

            <div class="mk-course-card__footer">
                <div class="mk-course-card__pricing">
                    <span class="mk-course-card__price-label"><?php echo e((string) ($courseCard['priceHint'] ?? 'Paiement unique')); ?></span>
                    <strong class="mk-course-card__price app-price<?php echo !empty($courseCard['promoActive']) ? ' app-price--promo' : ''; ?>"><?php echo e((string) ($courseCard['priceLabel'] ?? 'Gratuit')); ?></strong>
                </div>
                <span class="mk-course-card__cta">
                    <?php echo e($cardContext === 'home' ? 'Voir le détail' : (string) ($courseCard['ctaLabel'] ?? 'Découvrir')); ?>
                </span>
            </div>
        </div>
    </a>
</article>
