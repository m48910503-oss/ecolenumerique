<?php

$dashboardShellSpace = (string) ($dashboardShellSpace ?? 'student');
if (!in_array($dashboardShellSpace, ['teacher', 'student'], true)) {
    $dashboardShellSpace = 'student';
}
$dashboardShellNavActive = (string) ($dashboardShellNavActive ?? 'dashboard');
$dashboardShellTopTitle = trim((string) ($dashboardShellTopTitle ?? 'Tableau de bord'));
$dashboardShellTopSubtitle = trim((string) ($dashboardShellTopSubtitle ?? dashboard_space_label($dashboardShellSpace)));
$dashboardShellBodyClass = (string) ($dashboardShellBodyClass ?? 'min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100 text-slate-900 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950 dark:text-slate-100 font-body');
$dashboardShellWithGlow = isset($dashboardShellWithGlow) ? (bool) $dashboardShellWithGlow : false;
$dashboardShellShowCatalogue = !isset($dashboardShellShowCatalogue) || (bool) $dashboardShellShowCatalogue;
$dashboardShellShowThemeToggle = !isset($dashboardShellShowThemeToggle) || (bool) $dashboardShellShowThemeToggle;
$dashboardShellCatalogueUrl = (string) ($dashboardShellCatalogueUrl ?? site_url('catalogue'));
$dashboardShellDataView = trim((string) ($dashboardShellDataView ?? ''));
if ($dashboardShellDataView === '' && $dashboardShellNavActive !== '') {
    $dashboardShellDataView = $dashboardShellSpace . '/' . $dashboardShellNavActive;
} 
$dashboardShellBodyAttr = $dashboardShellDataView !== '' ? ' data-view="' . e($dashboardShellDataView) . '"' : '';
?>
<body class="<?php echo e($dashboardShellBodyClass); ?>"<?php echo $dashboardShellBodyAttr; ?>>
    <?php if ($dashboardShellWithGlow): ?>
        <div class="pointer-events-none fixed inset-0 overflow-hidden">
            <div class="app-shell-glow app-shell-glow--primary -left-20 top-16 h-72 w-72"></div>
            <div class="app-shell-glow app-shell-glow--info right-10 top-12 h-64 w-64"></div>
            <div class="app-shell-glow app-shell-glow--warning right-1/3 bottom-10 h-80 w-80"></div>
        </div>
    <?php endif; ?>
    <div class="app-dashboard-shell relative z-10 min-h-screen flex flex-col md:flex-row">
        <?php if ($dashboardShellSpace === 'teacher'): ?>
            <?php $teacherNavActive = $dashboardShellNavActive; ?>
            <?php require __DIR__ . '/../teacher/_sidebar.php'; ?>
        <?php else: ?>
            <?php $studentNavActive = $dashboardShellNavActive; ?>
            <?php require __DIR__ . '/../student/_sidebar.php'; ?>
        <?php endif; ?>
        <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>
        <div class="app-dashboard-shell__content flex-1">
            <?php if ($dashboardShellSpace === 'teacher'): ?>
                <?php $teacherTopTitle = $dashboardShellTopTitle; ?>
                <?php $teacherTopSubtitle = $dashboardShellTopSubtitle; ?>
                <?php $showCatalogue = $dashboardShellShowCatalogue; ?>
                <?php $showThemeToggle = $dashboardShellShowThemeToggle; ?>
                <?php $catalogueUrl = $dashboardShellCatalogueUrl; ?>
                <?php require __DIR__ . '/../teacher/_topbar.php'; ?>
            <?php else: ?>
                <?php $studentTopTitle = $dashboardShellTopTitle; ?>
                <?php $studentTopSubtitle = $dashboardShellTopSubtitle; ?>
                <?php $showCatalogue = $dashboardShellShowCatalogue; ?>
                <?php $showThemeToggle = $dashboardShellShowThemeToggle; ?>
                <?php $catalogueUrl = $dashboardShellCatalogueUrl; ?>
                <?php require __DIR__ . '/../student/_topbar.php'; ?>
            <?php endif; ?>
 
