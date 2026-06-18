<?php

$studentPageClass = trim((string) ($studentPageClass ?? ''));
$studentPageEyebrow = trim((string) ($studentPageEyebrow ?? ''));
$studentPageTitle = trim((string) ($studentPageTitle ?? ''));
$studentPageSummary = trim((string) ($studentPageSummary ?? ''));
$studentPageIcon = trim((string) ($studentPageIcon ?? ''));
$studentPageAsideHtml = trim((string) ($studentPageAsideHtml ?? ''));
$studentPageActions = is_array($studentPageActions ?? null) ? $studentPageActions : [];
$studentPageStats = is_array($studentPageStats ?? null) ? $studentPageStats : [];

$studentActionClass = static function (string $variant): string {
    return match ($variant) {
        'primary' => 'app-btn-primary inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition',
        'ghost' => 'dash-btn dash-btn--ghost inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition',
        default => 'app-segmented-link inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition',
    };
};

ob_start();
foreach ($studentPageActions as $action) {
    $label = trim((string) ($action['label'] ?? ''));
    $url = trim((string) ($action['url'] ?? '#'));
    $actionIcon = trim((string) ($action['icon'] ?? ''));
    $variant = trim((string) ($action['variant'] ?? 'secondary'));
    $attrs = trim((string) ($action['attrs'] ?? ''));
    if ($label === '') {
        continue;
    }
    ?>
    <a href="<?php echo e($url); ?>" class="<?php echo e($studentActionClass($variant)); ?>"<?php echo $attrs !== '' ? ' ' . $attrs : ''; ?>>
        <?php if ($actionIcon !== ''): ?><span class="app-icon" aria-hidden="true"><i class="<?php echo e(dashboard_icon_fa_class($actionIcon)); ?>"></i></span><?php endif; ?>
        <span><?php echo e($label); ?></span>
    </a>
    <?php
}
$pageHeroActionsHtml = trim((string) ob_get_clean());

ob_start();
if ($studentPageStats !== []) {
    ?>
    <div class="student-hero-stats">
        <?php foreach ($studentPageStats as $stat): ?>
            <?php
            $label = trim((string) ($stat['label'] ?? ''));
            $value = trim((string) ($stat['value'] ?? ''));
            $meta = trim((string) ($stat['meta'] ?? ''));
            $statIcon = trim((string) ($stat['icon'] ?? ''));
            $statClass = trim((string) ($stat['class'] ?? ''));
            $valueClass = trim((string) ($stat['value_class'] ?? ''));
            $valueTitle = trim((string) ($stat['value_title'] ?? ''));
            ?>
            <article class="student-hero-stat<?php echo $statClass !== '' ? ' ' . e($statClass) : ''; ?>">
                <?php if ($label !== ''): ?>
                    <p class="student-hero-stat__label">
                        <?php if ($statIcon !== ''): ?><span class="app-icon" aria-hidden="true"><i class="<?php echo e(dashboard_icon_fa_class($statIcon)); ?>"></i></span><?php endif; ?>
                        <span><?php echo e($label); ?></span>
                    </p>
                <?php endif; ?>
                <?php if ($value !== ''): ?><p class="student-hero-stat__value<?php echo $valueClass !== '' ? ' ' . e($valueClass) : ''; ?>"<?php echo $valueTitle !== '' ? ' title="' . e($valueTitle) . '"' : ''; ?>><?php echo e($value); ?></p><?php endif; ?>
                <?php if ($meta !== ''): ?><p class="student-hero-stat__meta"><?php echo e($meta); ?></p><?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
    <?php
}
$pageHeroFooterHtml = trim((string) ob_get_clean());

$pageHeroClass = trim('student-page-header ' . $studentPageClass);
$pageHeroEyebrow = $studentPageEyebrow;
$pageHeroTitle = $studentPageTitle;
$pageHeroSummary = $studentPageSummary;
$pageHeroIcon = $studentPageIcon;
$pageHeroAsideHtml = $studentPageAsideHtml;

require __DIR__ . '/../shared/_page_hero.php';
?>
