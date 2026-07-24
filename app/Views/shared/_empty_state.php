<?php

$emptyStateTone = strtolower(trim((string) ($emptyStateTone ?? 'info')));
if (!in_array($emptyStateTone, ['info', 'active', 'pending', 'urgent', 'done'], true)) {
    $emptyStateTone = 'info';
}
$emptyStateTitle = trim((string) ($emptyStateTitle ?? 'Aucune donnée disponible'));
$emptyStateMessage = trim((string) ($emptyStateMessage ?? ''));
$emptyStateIcon = trim((string) ($emptyStateIcon ?? 'help-circle'));
$emptyStateCompact = !empty($emptyStateCompact);
$emptyStateActions = is_array($emptyStateActions ?? null) ? $emptyStateActions : [];
$emptyActionClass = static function (string $variant): string {
    return match ($variant) {
        'primary' => 'dash-btn inline-flex items-center gap-2',
        'danger' => 'app-btn-soft app-btn-soft--danger inline-flex items-center gap-2',
        default => 'dash-btn dash-btn--ghost inline-flex items-center gap-2',
    };
};
?>
<section class="app-empty-state app-empty-state--<?php echo e($emptyStateTone); ?><?php echo $emptyStateCompact ? ' app-empty-state--compact' : ''; ?>">
    <div class="app-empty-state__icon" aria-hidden="true"><?php echo dashboard_icon_svg($emptyStateIcon); ?></div>
    <div class="app-empty-state__copy">
        <h2 class="app-empty-state__title"><?php echo e($emptyStateTitle); ?></h2>
        <?php if ($emptyStateMessage !== ''): ?><p class="app-empty-state__body"><?php echo e($emptyStateMessage); ?></p><?php endif; ?>
    </div>
    <?php if ($emptyStateActions !== []): ?>
        <div class="app-empty-state__actions">
            <?php foreach ($emptyStateActions as $action): ?>
                <?php
                $label = trim((string) ($action['label'] ?? ''));
                $url = (string) ($action['url'] ?? '#');
                $actionIcon = trim((string) ($action['icon'] ?? ''));
                $variant = trim((string) ($action['variant'] ?? 'secondary'));
                if ($label === '') {
                    continue;
                }
                ?>
                <a href="<?php echo e($url); ?>" class="<?php echo e($emptyActionClass($variant)); ?>">
                    <?php if ($actionIcon !== ''): ?><span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg($actionIcon); ?></span><?php endif; ?>
                    <span><?php echo e($label); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
