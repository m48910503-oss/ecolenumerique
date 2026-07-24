<?php

$sectionHeadClass = trim((string) ($sectionHeadClass ?? ''));
$sectionHeadEyebrow = trim((string) ($sectionHeadEyebrow ?? ''));
$sectionHeadTitle = trim((string) ($sectionHeadTitle ?? ''));
$sectionHeadDesc = trim((string) ($sectionHeadDesc ?? ''));
$sectionHeadMetaHtml = trim((string) ($sectionHeadMetaHtml ?? ''));
$sectionHeadActionsHtml = trim((string) ($sectionHeadActionsHtml ?? ''));
$sectionHeadIcon = trim((string) ($sectionHeadIcon ?? ''));

if ($sectionHeadIcon === '' && function_exists('dashboard_icon_for_label')) {
    $sectionHeadIcon = dashboard_icon_for_label($sectionHeadTitle . ' ' . $sectionHeadEyebrow, '');
}
?>
<header class="app-section-head<?php echo $sectionHeadClass !== '' ? ' ' . e($sectionHeadClass) : ''; ?>">
    <div class="app-section-head__lead">
        <?php if ($sectionHeadIcon !== ''): ?>
            <span class="app-section-head__icon app-title-icon" aria-hidden="true"><?php echo dashboard_icon_svg($sectionHeadIcon); ?></span>
        <?php endif; ?>
        <div class="app-section-head__copy">
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