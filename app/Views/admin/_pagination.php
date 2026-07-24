<?php
$paginationData = is_array($paginationData ?? null) ? $paginationData : [];
$paginationUrlBuilder = is_callable($paginationUrlBuilder ?? null) ? $paginationUrlBuilder : null;
$paginationLabel = trim((string) ($paginationLabel ?? 'éléments'));
$paginationAriaLabel = trim((string) ($paginationAriaLabel ?? 'Pagination'));

$paginationPage = max(1, (int) ($paginationData['page'] ?? 1));
$paginationTotal = max(0, (int) ($paginationData['total'] ?? 0));
$paginationTotalPages = max(1, (int) ($paginationData['total_pages'] ?? $paginationData['totalPages'] ?? 1));
$paginationStart = max(0, (int) ($paginationData['start'] ?? 0));
$paginationEnd = max(0, (int) ($paginationData['end'] ?? 0));
$paginationHasPrev = !empty($paginationData['has_prev']) || !empty($paginationData['hasPrev']);
$paginationHasNext = !empty($paginationData['has_next']) || !empty($paginationData['hasNext']);

if ($paginationUrlBuilder === null || $paginationTotal <= 0) {
    return;
}

$startPage = max(1, $paginationPage - 2);
$endPage = min($paginationTotalPages, $paginationPage + 2);
?>
<div class="flex flex-col gap-3 rounded-2xl border border-slate-200/80 bg-white/80 px-4 py-3 shadow-sm dark:border-slate-700 dark:bg-slate-900/70 sm:flex-row sm:items-center sm:justify-between">
    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">
        Affichage de <?php echo e((string) $paginationStart); ?> à <?php echo e((string) $paginationEnd); ?>
        sur <?php echo e((string) $paginationTotal); ?> <?php echo e($paginationLabel); ?>
    </p>

    <?php if ($paginationTotalPages > 1): ?>
        <nav class="flex flex-wrap items-center gap-2" aria-label="<?php echo e($paginationAriaLabel); ?>">
            <a href="<?php echo e((string) $paginationUrlBuilder(max(1, $paginationPage - 1))); ?>" class="admin-shell-button admin-shell-button--secondary <?php echo $paginationHasPrev ? '' : 'pointer-events-none opacity-50'; ?>">
                Précédent
            </a>

            <?php for ($pageNumber = $startPage; $pageNumber <= $endPage; $pageNumber++): ?>
                <a href="<?php echo e((string) $paginationUrlBuilder($pageNumber)); ?>" class="admin-shell-button <?php echo $pageNumber === $paginationPage ? 'admin-shell-button--primary' : 'admin-shell-button--secondary'; ?>">
                    <?php echo e((string) $pageNumber); ?>
                </a>
            <?php endfor; ?>

            <a href="<?php echo e((string) $paginationUrlBuilder(min($paginationTotalPages, $paginationPage + 1))); ?>" class="admin-shell-button admin-shell-button--secondary <?php echo $paginationHasNext ? '' : 'pointer-events-none opacity-50'; ?>">
                Suivant
            </a>
        </nav>
    <?php endif; ?>
</div>
