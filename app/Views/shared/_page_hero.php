<?php

$pageHeroClass = trim((string) ($pageHeroClass ?? ''));
$pageHeroEyebrow = trim((string) ($pageHeroEyebrow ?? ''));
$pageHeroTitle = trim((string) ($pageHeroTitle ?? ''));
$pageHeroSummary = trim((string) ($pageHeroSummary ?? ''));
$pageHeroActionsHtml = trim((string) ($pageHeroActionsHtml ?? ''));
$pageHeroAsideHtml = trim((string) ($pageHeroAsideHtml ?? ''));
$pageHeroFooterHtml = trim((string) ($pageHeroFooterHtml ?? ''));
?> 
<section class="app-page-hero<?php echo $pageHeroClass !== '' ? ' ' . e($pageHeroClass) : ''; ?>">
    <div class="app-page-hero__header">
        <div class="app-page-hero__intro">
            <?php if ($pageHeroEyebrow !== ''): ?>
                <p class="app-page-hero__eyebrow"><?php echo e($pageHeroEyebrow); ?></p>
            <?php endif; ?>
            <?php if ($pageHeroTitle !== ''): ?>
                <h1 class="app-page-hero__title"><?php echo e($pageHeroTitle); ?></h1>
            <?php endif; ?>
            <?php if ($pageHeroSummary !== ''): ?>
                <p class="app-page-hero__summary"><?php echo e($pageHeroSummary); ?></p>
            <?php endif; ?>
        </div>

        <?php if ($pageHeroAsideHtml !== ''): ?>
            <div class="app-page-hero__aside">
                <?php echo $pageHeroAsideHtml; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($pageHeroActionsHtml !== '' || $pageHeroFooterHtml !== ''): ?>
        <div class="app-page-hero__footer">
            <?php if ($pageHeroFooterHtml !== ''): ?>
                <div class="app-page-hero__footer-copy">
                    <?php echo $pageHeroFooterHtml; ?>
                </div>
            <?php endif; ?>
            <?php if ($pageHeroActionsHtml !== ''): ?>
                <div class="app-page-hero__actions">
                    <?php echo $pageHeroActionsHtml; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
