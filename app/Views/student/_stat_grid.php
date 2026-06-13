<?php

$studentStatGridClass = trim((string) ($studentStatGridClass ?? ''));
$studentStatCards = is_array($studentStatCards ?? null) ? $studentStatCards : [];
if ($studentStatCards === []) {
    return;
}
?>
<section class="student-stat-grid<?php echo $studentStatGridClass !== '' ? ' ' . e($studentStatGridClass) : ''; ?>">
    <?php foreach ($studentStatCards as $card): ?>
        <?php
        $label = trim((string) ($card['label'] ?? ''));
        $value = trim((string) ($card['value'] ?? ''));
        $meta = trim((string) ($card['meta'] ?? ''));
        $cardIcon = trim((string) ($card['icon'] ?? ''));
        $tone = preg_replace('/[^a-z0-9_-]/i', '', (string) ($card['tone'] ?? 'neutral')) ?: 'neutral';
        ?>
        <article class="student-stat-card student-stat-card--<?php echo e($tone); ?>">
            <?php if ($label !== ''): ?>
                <p class="student-stat-card__label">
                    <?php if ($cardIcon !== ''): ?><span class="app-icon" aria-hidden="true"><i class="<?php echo e(dashboard_icon_fa_class($cardIcon)); ?>"></i></span><?php endif; ?>
                    <span><?php echo e($label); ?></span>
                </p>
            <?php endif; ?>
            <?php if ($value !== ''): ?><p class="student-stat-card__value"><?php echo e($value); ?></p><?php endif; ?>
            <?php if ($meta !== ''): ?><p class="student-stat-card__meta"><?php echo e($meta); ?></p><?php endif; ?>
        </article>
    <?php endforeach; ?>
</section>
