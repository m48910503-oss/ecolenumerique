<?php
$fmtDtLocal = isset($fmtDtLocal) && is_callable($fmtDtLocal) ? $fmtDtLocal : static function (string $dbValue): string {
        if (!$dbValue) {
            return '';
        }
$ts = strtotime($dbValue);
        if (!$ts) {
            return (string) $dbValue;
        }
return date('d/m/Y H:i', $ts);
    };
?>  
<!doctype html>
<html lang="fr">
<head> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="app-global-assets" content="off">
    <title>Historique des mises &agrave; jour - &Eacute;cole Num&eacute;rique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo e(site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()))); ?>"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/teacher-dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/teacher-dashboard.css') ?: time()))); ?>">
    <script src="<?php echo e(site_url('assets/js/sidebar_toggle.js')); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/teacher_dashboard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/teacher_dashboard.js') ?: time()))); ?>" defer></script>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="app-view app-role-teacher min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50 text-slate-900 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950 dark:text-slate-100 font-body" data-view="teacher/course_updates">
        <div class="teacher-dashboard-layout relative h-screen overflow-hidden pt-16 sm:pt-[4.5rem] md:pl-72 md:flex-row">
        <?php $teacherNavActive = 'courses'; ?>
        <?php require __DIR__ . '/_sidebar.php'; ?>
        <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

        <div class="flex-1">
            <?php $teacherTopTitle = 'Historique des mises &agrave; jour'; ?>
            <?php $teacherTopSubtitle = 'Espace enseignant'; ?>
            <?php require __DIR__ . '/_topbar.php'; ?>
<main class="teacher-workflow-main max-w-5xl mx-auto px-6 py-6 space-y-4">
        <?php $teacherWelcomeTitle = 'Historique des mises à jour'; ?>
        <?php $teacherWelcomeSummary = 'Retrouvez les dernières modifications effectuées sur vos cours, avec la source et la date de chaque action.'; ?>
        <?php $teacherWelcomeActionUrl = site_url('teacher/courses'); ?>
        <?php $teacherWelcomeActionLabel = 'Mes cours'; ?>
        <?php $teacherWelcomeActionIcon = 'arrow-left'; ?>
        <?php require __DIR__ . '/_welcome_hero.php'; ?>
        <?php if (!$updates): ?>
            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 text-sm text-slate-400">
                Aucune mise &agrave; jour enregistr&eacute;e pour vos cours.
            </div>
        <?php else: ?>
            <div class="space-y-3" data-course-updates-list data-pagination-list data-pagination-item="[data-course-update-item]" data-pagination-size-mobile="6" data-pagination-size-desktop="10">
                <?php foreach ($updates as $row): ?>
                    <?php $meta = json_decode($row['meta_json'] ?? '', true) ?? [];?>
                        $label = $meta['label'] ?? ($row['action'] ?? 'Mise &agrave; jour');
                        $source = $meta['source'] ?? '';
                        $who = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                        $who = $who !== '' ? $who : 'Syst&egrave;me';
                        $when = $fmtDtLocal($row['created_at'] ?? null);
                    ?>
                    <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4" data-course-update-item>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <p class="text-sm font-semibold"><?php echo e($row['course_title'] ?? 'Cours'); ?></p>
                                <p class="text-xs text-slate-400"><?php echo e($label); ?><?php if ($source): ?> &middot; <?php echo e($source); ?><?php endif; ?></p>
                            </div>
                            <div class="text-xs text-slate-400"><?php echo e($when); ?></div>
                        </div>
                        <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-400">
                            <span>Par: <?php echo e($who); ?></span>
                            <a class="dash-link--inline text-xs" href="<?php echo e(site_url('teacher/course_preview?id=' . (int) ($row['course_id'] ?? 0))); ?>">Ouvrir le cours</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
        </div>
    </div>
</body>
</html>






