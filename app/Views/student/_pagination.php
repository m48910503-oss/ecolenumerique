<?php
 
$paginationData = is_array($pagination ?? null) ? $pagination : [];
$total = max(0, (int) ($paginationData['total'] ?? 0));
$totalPages = max(1, (int) ($paginationData['total_pages'] ?? 1));
if ($totalPages < 2) {
    return;
}
$currentPage = max(1, min($totalPages, (int) ($paginationData['page'] ?? 1)));
$queryKey = trim((string) ($queryKey ?? 'page'));
if ($queryKey === '') {
    $queryKey = 'page';
}
$basePath = trim((string) ($basePath ?? ''), '/');
$baseQuery = is_array($baseQuery ?? null) ? $baseQuery : [];
$window = 2;
$pageStart = max(1, $currentPage - $window);
$pageEnd = min($totalPages, $currentPage + $window);

$buildPageUrl = static function (int $page) use ($basePath, $baseQuery, $queryKey): string {
    $params = $baseQuery;
    $params[$queryKey] = $page;
    $params = array_filter($params, static fn ($value): bool => $value !== null && $value !== '');
    $query = http_build_query($params);
    return site_url($basePath) . ($query !== '' ? ('?' . $query) : '');
};
?>
<section class="app-page-section-card app-pagination-card">
    <div class="app-pagination-card__inner">
        <p class="app-pagination-card__summary">
            Affichage <?php echo e((string) ($paginationData['start'] ?? 0)); ?> &agrave; <?php echo e((string) ($paginationData['end'] ?? 0)); ?> sur <?php echo e((string) $total); ?> &eacute;l&eacute;ment(s)
        </p>
        <nav class="app-pagination" aria-label="Pagination">
            <a href="<?php echo e($buildPageUrl((int) ($paginationData['prev_page'] ?? max(1, $currentPage - 1)))); ?>" class="app-pagination__link<?php echo !empty($paginationData['has_prev']) ? '' : ' is-disabled'; ?>">Pr&eacute;c&eacute;dent</a>
            <?php if ($pageStart > 1): ?>
                <a href="<?php echo e($buildPageUrl(1)); ?>" class="app-pagination__link">1</a>
                <?php if ($pageStart > 2): ?><span class="app-pagination__ellipsis" aria-hidden="true">...</span><?php endif; ?>
            <?php endif; ?>
            <?php for ($pageNo = $pageStart; $pageNo <= $pageEnd; $pageNo++): ?>
                <?php if ($pageNo === $currentPage): ?>
                    <span class="app-pagination__link is-current" aria-current="page"><?php echo e((string) $pageNo); ?></span>
                <?php else: ?>
                    <a href="<?php echo e($buildPageUrl($pageNo)); ?>" class="app-pagination__link"><?php echo e((string) $pageNo); ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($pageEnd < $totalPages): ?>
                <?php if ($pageEnd < $totalPages - 1): ?><span class="app-pagination__ellipsis" aria-hidden="true">...</span><?php endif; ?>
                <a href="<?php echo e($buildPageUrl($totalPages)); ?>" class="app-pagination__link"><?php echo e((string) $totalPages); ?></a>
            <?php endif; ?>
            <a href="<?php echo e($buildPageUrl((int) ($paginationData['next_page'] ?? min($totalPages, $currentPage + 1)))); ?>" class="app-pagination__link<?php echo !empty($paginationData['has_next']) ? '' : ' is-disabled'; ?>">Suivant</a>
        </nav>
    </div>
</section>
