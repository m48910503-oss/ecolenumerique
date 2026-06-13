<?php

$sectionHeadClass = trim((string) ($sectionHeadClass ?? ''));
$sectionHeadEyebrow = trim((string) ($sectionHeadEyebrow ?? ''));
$sectionHeadTitle = trim((string) ($sectionHeadTitle ?? ''));
$sectionHeadDesc = trim((string) ($sectionHeadDesc ?? ''));
$sectionHeadMetaHtml = trim((string) ($sectionHeadMetaHtml ?? ''));
$sectionHeadActionsHtml = trim((string) ($sectionHeadActionsHtml ?? ''));
?>
<header class="app-section-head<?php echo $sectionHeadClass !== '' ? ' ' . e($sectionHeadClass) : ''; ?>">
    <div>
        <?php if ($sectionHeadEyebrow !== ''): ?>
            <p class="app-section-head__eyebrow"><?php echo e($sectionHeadEyebrow); ?></p>
        <?php endif; ?>
        <?php if ($sectionHeadTitle !== ''): ?>
            <h2 class="app-section-head__title"><?php echo e($sectionHeadTitle); ?></h2>
        <?php endif; ?>
        <?php if ($sectionHeadDesc !== ''): ?>
            <p class="app-section-head__desc"><?php echo e($sectionHeadDesc); ?></p>
        <?php endif; ?>
    </div> 

    <?php if ($sectionHeadMetaHtml !== '' || $sectionHeadActionsHtml !== ''): ?>
        <div class="app-section-head__actions">
            <?php if ($sectionHeadMetaHtml !== ''): ?>
                <div class="app-section-head__meta">
                    <?php echo $sectionHeadMetaHtml; ?>
                </div>
            <?php endif; ?>
            <?php if ($sectionHeadActionsHtml !== ''): ?>
                <?php echo $sectionHeadActionsHtml; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</header>
