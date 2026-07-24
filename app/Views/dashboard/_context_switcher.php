<?php

$dashboardContextOptions = is_array($dashboardContextOptions ?? null) ? $dashboardContextOptions : [];
if ($dashboardContextOptions === []) {
    return;
}
?>
<div class="flex flex-wrap items-center gap-2">
    <?php foreach ($dashboardContextOptions as $option): ?>
        <?php
            $optionLabel = (string) ($option['label'] ?? '');
            $optionUrl = (string) ($option['url'] ?? '#');
            $optionIsActive = !empty($option['active']);
            $baseClass = 'inline-flex items-center rounded-full px-3 py-1.5 text-xs font-semibold transition';
            $activeClass = 'app-status-pill app-status-pill--info';
            $idleClass = 'app-segmented-link';
        ?>
        <a class="<?php echo e($baseClass . ' ' . ($optionIsActive ? $activeClass : $idleClass)); ?>" href="<?php echo e($optionUrl); ?>">
            <?php echo e($optionLabel); ?>
        </a> 
    <?php endforeach; ?>
</div>
 
