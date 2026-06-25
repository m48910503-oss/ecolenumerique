<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only\n";
    exit(1);
}

$rootDir = dirname(__DIR__, 2);
chdir($rootDir);

$errors = [];

/**
 * @param array<int,string> $errors
 */
function ensure_contains(string $file, string $needle, array &$errors, string $message): void
{
    $content = @file_get_contents($file);
    if ($content === false) {
        $errors[] = "Cannot read {$file}";
        return;
    }

    if (strpos($content, $needle) === false) {
        $errors[] = $message . " ({$file})";
    }
}

/**
 * @param array<int,string> $errors
 */
function ensure_not_contains(string $file, string $needle, array &$errors, string $message): void
{
    $content = @file_get_contents($file);
    if ($content === false) {
        $errors[] = "Cannot read {$file}";
        return;
    }

    if (strpos($content, $needle) !== false) {
        $errors[] = $message . " ({$file})";
    }
}

$adminShellViews = [
    $rootDir . '/app/Views/admin/categories.php',
    $rootDir . '/app/Views/admin/certificate_preview.php',
    $rootDir . '/app/Views/admin/course_insights.php',
    $rootDir . '/app/Views/admin/course_preview.php',
    $rootDir . '/app/Views/admin/courses.php',
    $rootDir . '/app/Views/admin/dashboard.php',
    $rootDir . '/app/Views/admin/error_logs.php',
    $rootDir . '/app/Views/admin/announcements.php',
    $rootDir . '/app/Views/admin/notifications.php',
    $rootDir . '/app/Views/admin/sales.php',
    $rootDir . '/app/Views/admin/secondary_admins.php',
    $rootDir . '/app/Views/admin/secondary_admins_form.php',
    $rootDir . '/app/Views/admin/site_settings.php',
    $rootDir . '/app/Views/admin/statistics.php',
    $rootDir . '/app/Views/admin/support.php',
    $rootDir . '/app/Views/admin/support_agents.php',
    $rootDir . '/app/Views/admin/teacher_dossier.php',
    $rootDir . '/app/Views/admin/teacher_applications.php',
];

foreach ($adminShellViews as $viewFile) {
    ensure_contains(
        $viewFile,
        "require __DIR__ . '/_sidebar.php'",
        $errors,
        'Admin pages should use the shared sidebar partial'
    );
    ensure_contains(
        $viewFile,
        "require __DIR__ . '/_topbar.php'",
        $errors,
        'Admin pages should use the shared topbar partial'
    );
    ensure_contains(
        $viewFile,
        'js-sidebar-backdrop',
        $errors,
        'Admin pages should expose the shared mobile sidebar backdrop'
    );
    ensure_contains(
        $viewFile,
        'data-sidebar-desktop-toggle="1"',
        $errors,
        'Admin pages should enable the shared desktop sidebar toggle'
    );
}

$dashboardView = $rootDir . '/app/Views/admin/dashboard.php';
ensure_not_contains(
    $dashboardView,
    'admin-dashboard-sidebar',
    $errors,
    'Dashboard should not keep a dedicated admin sidebar shell'
);
ensure_not_contains(
    $dashboardView,
    'data-admin-menu-toggle',
    $errors,
    'Dashboard should not keep dedicated mobile menu toggles'
);
ensure_not_contains(
    $dashboardView,
    'data-admin-menu-close',
    $errors,
    'Dashboard should not keep dedicated mobile menu close hooks'
);
ensure_not_contains(
    $dashboardView,
    'data-admin-theme-toggle',
    $errors,
    'Dashboard should not keep a dedicated theme toggle hook'
);

$dashboardScript = $rootDir . '/assets/js/admin_dashboard_page.js';
ensure_not_contains(
    $dashboardScript,
    'data-admin-menu-toggle',
    $errors,
    'Dashboard page script should not manage a dedicated menu toggle anymore'
);
ensure_not_contains(
    $dashboardScript,
    'data-admin-menu-close',
    $errors,
    'Dashboard page script should not manage a dedicated menu close hook anymore'
);
ensure_not_contains(
    $dashboardScript,
    'data-admin-theme-toggle',
    $errors,
    'Dashboard page script should not manage a dedicated theme toggle anymore'
);

$adminTopbar = $rootDir . '/app/Views/admin/_topbar.php';
ensure_contains(
    $adminTopbar,
    "require __DIR__ . '/../dashboard/_user_menu.php';",
    $errors,
    'Admin topbar should use the shared user menu partial'
);
ensure_contains(
    $adminTopbar,
    'data-theme-toggle',
    $errors,
    'Admin topbar should expose a theme toggle button'
);
ensure_contains(
    $adminTopbar,
    'admin-topbar-account-trigger',
    $errors,
    'Admin topbar should expose the shared account dropdown trigger'
);

ensure_not_contains(
    $rootDir . '/app/Views/admin/secondary_admins.php',
    'secondary-create-modal',
    $errors,
    'Secondary admins list should not keep the create modal'
);
ensure_not_contains(
    $rootDir . '/app/Views/admin/secondary_admins.php',
    'secondary-grant-modal',
    $errors,
    'Secondary admins list should not keep the grant modal'
);
ensure_not_contains(
    $rootDir . '/app/Views/admin/secondary_admins.php',
    'data-secondary-open-modal',
    $errors,
    'Secondary admins list should not auto-open removed modals'
);

if ($errors !== []) {
    echo "Admin UI uniformity FAILED\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "Admin UI uniformity OK\n";
exit(0);
