<!doctype html>
<html lang="fr" class="light" data-default-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="app-global-assets" content="off">
    <title><?php echo e($title); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo e(site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()))); ?>"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/standalone-dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/standalone-dashboard.css') ?: time()))); ?>">
    <script src="<?php echo e(site_url('assets/js/web_theme.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/web_theme.js') ?: time()))); ?>" defer></script>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="standalone-dashboard-page min-h-screen text-slate-900 dark:text-slate-100 font-body flex items-center justify-center px-4 py-8" data-view="drive/oauth_result">
    <div class="w-full max-w-xl rounded-2xl border border-slate-200 bg-white/90 p-8 shadow-2xl shadow-slate-200/70 dark:border-slate-800 dark:bg-slate-900/80 dark:shadow-slate-950/60 space-y-5">
        <h1 class="text-2xl font-display font-semibold"><?php echo e($title); ?></h1>
        <p class="text-sm <?php echo $ok ? 'text-emerald-600 dark:text-emerald-300' : 'text-rose-600 dark:text-rose-300'; ?>"><?php echo e($message); ?></p>
        <a href="<?php echo e($dashboardUrl); ?>" class="app-segmented-link inline-flex items-center rounded-lg border border-slate-200 px-4 py-2 text-sm dark:border-slate-700">
            Retour dashboard
        </a>
    </div>
</body> 
</html>
