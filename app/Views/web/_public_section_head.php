<?php
$sectionTitle = trim((string) ($sectionTitle ?? ''));
$sectionDescription = trim((string) ($sectionDescription ?? ''));
$sectionEyebrow = trim((string) ($sectionEyebrow ?? ''));
$sectionHeadingTag = trim((string) ($sectionHeadingTag ?? 'h2'));
$sectionHeadingId = trim((string) ($sectionHeadingId ?? ''));
$sectionActionHtml = (string) ($sectionActionHtml ?? '');
?>
<div class="mk-section__head">
    <div class="mk-section__copy">
        <?php if ($sectionEyebrow !== ''): ?>
            <p class="mk-section__eyebrow"><?php echo e($sectionEyebrow); ?></p>
        <?php endif; ?>
        <<?php echo e($sectionHeadingTag); ?><?php if ($sectionHeadingId !== ''): ?> id="<?php echo e($sectionHeadingId); ?>"<?php endif; ?> class="mk-section__title"><?php echo e($sectionTitle); ?></<?php echo e($sectionHeadingTag); ?>>
        <?php if ($sectionDescription !== ''): ?>
            <p class="mk-section__description"><?php echo e($sectionDescription); ?></p>
        <?php endif; ?>
    </div>
    <?php if ($sectionActionHtml !== ''): ?>
        <div class="mk-section__action">
            <?php echo $sectionActionHtml; ?>
        </div>
    <?php endif; ?>
</div>
