<?php
$adminPageScripts = is_array($adminPageScripts ?? null) ? $adminPageScripts : [];
$faviconUrl = '';
if (function_exists('site_favicon_url')) {
    $faviconUrl = trim((string) site_favicon_url());
}
if ($faviconUrl === '' && function_exists('site_logo_url')) {
    $faviconUrl = trim((string) site_logo_url());
}

$adminAssetUrl = static function (string $relativePath): string {
    $relativePath = ltrim($relativePath, '/');
    $absolutePath = dirname(__DIR__, 3) . '/' . $relativePath;
    $version = is_file($absolutePath) ? (string) filemtime($absolutePath) : (string) time();

    return site_url($relativePath . '?v=' . $version);
};
?>
<?php if ($faviconUrl !== ''): ?>
<link rel="icon" href="<?php echo e($faviconUrl); ?>">
<link rel="apple-touch-icon" href="<?php echo e($faviconUrl); ?>">
<?php endif; ?>
<meta name="app-global-assets" content="off">
<script src="https://cdn.tailwindcss.com"></script>
<script src="<?php echo e($adminAssetUrl('assets/js/tailwind_theme_bootstrap.js')); ?>"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo e($adminAssetUrl('assets/vendor/fontawesome/all.min.css')); ?>">
<link rel="stylesheet" href="<?php echo e($adminAssetUrl('assets/css/admin-dashboard.css')); ?>">
<script src="<?php echo e($adminAssetUrl('assets/js/sidebar_toggle.js')); ?>" defer></script>
<script src="<?php echo e($adminAssetUrl('assets/js/admin_dashboard.js')); ?>" defer></script>
<?php foreach ($adminPageScripts as $adminPageScript): ?>
    <?php
    $adminPageScript = ltrim((string) $adminPageScript, '/');
    if ($adminPageScript === '') {
        continue;
    }
    if (strpos($adminPageScript, 'assets/') !== 0) {
        $adminPageScript = 'assets/js/' . $adminPageScript;
    }
    ?>
    <script src="<?php echo e($adminAssetUrl($adminPageScript)); ?>" defer></script>
<?php endforeach; ?>
