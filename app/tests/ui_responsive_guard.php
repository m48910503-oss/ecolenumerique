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
function require_snippet(string $file, string $snippet, array &$errors, string $label): void
{
    $content = @file_get_contents($file);
    if ($content === false) {
        $errors[] = "Cannot read {$file}";
        return;
    }
    if (strpos($content, $snippet) === false) {
        $errors[] = "{$label} ({$file})";
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
        $errors[] = "{$message} ({$file})";
    }
}

$dashboardPages = [
    $rootDir . '/app/Views/teacher/dashboard.php',
    $rootDir . '/app/Views/teacher/courses.php',
    $rootDir . '/app/Views/teacher/promotions.php',
    $rootDir . '/app/Views/teacher/withdrawals.php',
    $rootDir . '/app/Views/teacher/meetings.php',
    $rootDir . '/app/Views/teacher/referrals.php',
    $rootDir . '/app/Views/teacher/course_edit.php',
    $rootDir . '/app/Views/teacher/course_updates.php',
    $rootDir . '/app/Views/teacher/course_preview.php',
    $rootDir . '/app/Views/teacher/exams_create.php',
    $rootDir . '/app/Views/teacher/exams_manage.php',
    $rootDir . '/app/Views/teacher/exam_preview.php',
    $rootDir . '/app/Views/student/dashboard.php',
    $rootDir . '/app/Views/student/my_courses.php',
    $rootDir . '/app/Views/student/orders.php',
    $rootDir . '/app/Views/student/certificates.php',
    $rootDir . '/app/Views/student/referrals.php',
    $rootDir . '/app/Views/student/meetings.php',
];

foreach ($dashboardPages as $page) {
    require_snippet($page, 'sidebar_toggle.js', $errors, 'Missing sidebar toggle script for mobile menu');
    require_snippet($page, 'js-sidebar-backdrop', $errors, 'Missing mobile sidebar backdrop');

    $content = @file_get_contents($page);
    if ($content === false) {
        $errors[] = "Cannot read {$page}";
        continue;
    }

    if (strpos($content, 'student-dashboard-layout') !== false) {
        require_snippet($page, 'student-dashboard-layout', $errors, 'Missing fixed student shell layout');
        require_snippet($page, 'student-dashboard-main', $errors, 'Missing student main scroll container');
    }
}

require_snippet(
    $rootDir . '/app/Views/teacher/reviews.php',
    'meta name="app-global-assets" content="off"',
    $errors,
    'Teacher reviews page should opt out of global assets'
);
require_snippet(
    $rootDir . '/app/Views/teacher/reviews.php',
    'data-view="teacher/reviews"',
    $errors,
    'Teacher reviews page should expose the reviews view id'
);
require_snippet(
    $rootDir . '/app/Views/teacher/reviews.php',
    'tailwind_theme_bootstrap.js',
    $errors,
    'Teacher reviews page should load the shared Tailwind bootstrap script'
);
require_snippet(
    $rootDir . '/app/Views/teacher/reviews.php',
    'teacher-dashboard.css',
    $errors,
    'Teacher reviews page should load the shared teacher dashboard stylesheet'
);
require_snippet(
    $rootDir . '/app/Views/teacher/reviews.php',
    'sidebar_toggle.js',
    $errors,
    'Teacher reviews page should load the sidebar toggle script'
);
require_snippet(
    $rootDir . '/app/Views/teacher/reviews.php',
    'teacher_dashboard.js',
    $errors,
    'Teacher reviews page should load the shared teacher dashboard script'
);
require_snippet(
    $rootDir . '/app/Views/teacher/reviews.php',
    'teacher_reviews_page.js',
    $errors,
    'Teacher reviews page should load the dedicated page script'
);
require_snippet(
    $rootDir . '/app/Views/teacher/reviews.php',
    'teacher-dashboard-layout',
    $errors,
    'Teacher reviews page should use the shared teacher dashboard layout'
);
require_snippet(
    $rootDir . '/app/Views/teacher/reviews.php',
    "require __DIR__ . '/_sidebar.php'",
    $errors,
    'Teacher reviews page should use the shared teacher sidebar partial'
);
require_snippet(
    $rootDir . '/app/Views/teacher/reviews.php',
    "require __DIR__ . '/_topbar.php'",
    $errors,
    'Teacher reviews page should use the shared teacher topbar partial'
);
ensure_not_contains(
    $rootDir . '/app/Views/teacher/reviews.php',
    'assets/css/global-theme.css',
    $errors,
    'Teacher reviews page should not include the global theme stylesheet'
);
ensure_not_contains(
    $rootDir . '/app/Views/teacher/reviews.php',
    'assets/js/theme.js',
    $errors,
    'Teacher reviews page should not include the legacy theme script'
);
ensure_not_contains(
    $rootDir . '/app/Views/teacher/reviews.php',
    'tailwind.config =',
    $errors,
    'Teacher reviews page should not use inline Tailwind configuration'
);

$teacherExamsManageView = $rootDir . '/app/Views/teacher/exams_manage.php';
require_snippet(
    $teacherExamsManageView,
    'meta name="app-global-assets" content="off"',
    $errors,
    'Teacher exams management page should opt out of global assets'
);
require_snippet(
    $teacherExamsManageView,
    'data-view="teacher/exams_manage"',
    $errors,
    'Teacher exams management page should expose the exams view id'
);
require_snippet(
    $teacherExamsManageView,
    'tailwind_theme_bootstrap.js',
    $errors,
    'Teacher exams management page should load the shared Tailwind bootstrap script'
);
require_snippet(
    $teacherExamsManageView,
    'teacher-dashboard.css',
    $errors,
    'Teacher exams management page should load the shared teacher dashboard stylesheet'
);
require_snippet(
    $teacherExamsManageView,
    'sidebar_toggle.js',
    $errors,
    'Teacher exams management page should load the sidebar toggle script'
);
require_snippet(
    $teacherExamsManageView,
    'teacher_dashboard.js',
    $errors,
    'Teacher exams management page should load the shared teacher dashboard script'
);
require_snippet(
    $teacherExamsManageView,
    'teacher_exams_manage.js',
    $errors,
    'Teacher exams management page should load the dedicated page script'
);
require_snippet(
    $teacherExamsManageView,
    'teacher-dashboard-layout',
    $errors,
    'Teacher exams management page should use the shared teacher dashboard layout'
);
require_snippet(
    $teacherExamsManageView,
    "require __DIR__ . '/_sidebar.php'",
    $errors,
    'Teacher exams management page should use the shared teacher sidebar partial'
);
require_snippet(
    $teacherExamsManageView,
    "require __DIR__ . '/_topbar.php'",
    $errors,
    'Teacher exams management page should use the shared teacher topbar partial'
);
ensure_not_contains(
    $teacherExamsManageView,
    'assets/css/global-theme.css',
    $errors,
    'Teacher exams management page should not include the global theme stylesheet'
);
ensure_not_contains(
    $teacherExamsManageView,
    'assets/js/theme.js',
    $errors,
    'Teacher exams management page should not include the legacy theme script'
);
ensure_not_contains(
    $teacherExamsManageView,
    'tailwind.config =',
    $errors,
    'Teacher exams management page should not use inline Tailwind configuration'
);
ensure_not_contains(
    $teacherExamsManageView,
    'onsubmit="return confirm',
    $errors,
    'Teacher exams management page should not use inline confirmation handlers'
);

$teacherMeetingsView = $rootDir . '/app/Views/teacher/meetings.php';
require_snippet(
    $teacherMeetingsView,
    'meta name="app-global-assets" content="off"',
    $errors,
    'Teacher meetings page should opt out of global assets'
);
require_snippet(
    $teacherMeetingsView,
    'data-view="teacher/meetings"',
    $errors,
    'Teacher meetings page should expose the meetings view id'
);
require_snippet(
    $teacherMeetingsView,
    'tailwind_theme_bootstrap.js',
    $errors,
    'Teacher meetings page should load the shared Tailwind bootstrap script'
);
require_snippet(
    $teacherMeetingsView,
    'teacher-dashboard.css',
    $errors,
    'Teacher meetings page should load the shared teacher dashboard stylesheet'
);
require_snippet(
    $teacherMeetingsView,
    'sidebar_toggle.js',
    $errors,
    'Teacher meetings page should load the sidebar toggle script'
);
require_snippet(
    $teacherMeetingsView,
    'teacher_dashboard.js',
    $errors,
    'Teacher meetings page should load the shared teacher dashboard script'
);
require_snippet(
    $teacherMeetingsView,
    'teacher_meetings_filters.js',
    $errors,
    'Teacher meetings page should load the dedicated page script'
);
require_snippet(
    $teacherMeetingsView,
    'teacher-dashboard-layout',
    $errors,
    'Teacher meetings page should use the shared teacher dashboard layout'
);
require_snippet(
    $teacherMeetingsView,
    "require __DIR__ . '/_sidebar.php'",
    $errors,
    'Teacher meetings page should use the shared teacher sidebar partial'
);
require_snippet(
    $teacherMeetingsView,
    "require __DIR__ . '/_topbar.php'",
    $errors,
    'Teacher meetings page should use the shared teacher topbar partial'
);
ensure_not_contains(
    $teacherMeetingsView,
    'assets/css/global-theme.css',
    $errors,
    'Teacher meetings page should not include the global theme stylesheet'
);
ensure_not_contains(
    $teacherMeetingsView,
    'assets/js/theme.js',
    $errors,
    'Teacher meetings page should not include the legacy theme script'
);
ensure_not_contains(
    $teacherMeetingsView,
    'tailwind.config =',
    $errors,
    'Teacher meetings page should not use inline Tailwind configuration'
);
ensure_not_contains(
    $teacherMeetingsView,
    'onsubmit="return confirm',
    $errors,
    'Teacher meetings page should not use inline confirmation handlers'
);

$adminHeadAssets = $rootDir . '/app/Views/admin/_head_assets.php';
require_snippet(
    $adminHeadAssets,
    'meta name="app-global-assets" content="off"',
    $errors,
    'Admin shared head should opt out of global assets'
);
require_snippet(
    $adminHeadAssets,
    'tailwind_theme_bootstrap.js',
    $errors,
    'Admin shared head should load the shared Tailwind bootstrap script'
);
require_snippet(
    $adminHeadAssets,
    'admin-dashboard.css',
    $errors,
    'Admin shared head should load the dedicated admin stylesheet'
);
require_snippet(
    $adminHeadAssets,
    'sidebar_toggle.js',
    $errors,
    'Admin shared head should load the sidebar toggle script'
);
require_snippet(
    $adminHeadAssets,
    'admin_dashboard.js',
    $errors,
    'Admin shared head should load the dedicated admin shell script'
);

$adminViews = [
    'announcements' => $rootDir . '/app/Views/admin/announcements.php',
    'categories' => $rootDir . '/app/Views/admin/categories.php',
    'course_insights' => $rootDir . '/app/Views/admin/course_insights.php',
    'course_preview' => $rootDir . '/app/Views/admin/course_preview.php',
    'courses' => $rootDir . '/app/Views/admin/courses.php',
    'error_logs' => $rootDir . '/app/Views/admin/error_logs.php',
    'notifications' => $rootDir . '/app/Views/admin/notifications.php',
    'sales' => $rootDir . '/app/Views/admin/sales.php',
    'secondary_admins' => $rootDir . '/app/Views/admin/secondary_admins.php',
    'site_settings' => $rootDir . '/app/Views/admin/site_settings.php',
    'statistics' => $rootDir . '/app/Views/admin/statistics.php',
    'support_agents' => $rootDir . '/app/Views/admin/support_agents.php',
    'support' => $rootDir . '/app/Views/admin/support.php',
    'teacher_applications' => $rootDir . '/app/Views/admin/teacher_applications.php',
];

foreach ($adminViews as $viewName => $viewFile) {
    require_snippet(
        $viewFile,
        "require __DIR__ . '/_head_assets.php'",
        $errors,
        "Admin {$viewName} page should use the shared admin head assets"
    );
    require_snippet(
        $viewFile,
        'data-view="admin/' . $viewName . '"',
        $errors,
        "Admin {$viewName} page should expose its admin view id"
    );
    require_snippet(
        $viewFile,
        "require __DIR__ . '/_sidebar.php'",
        $errors,
        "Admin {$viewName} page should use the shared admin sidebar partial"
    );
    require_snippet(
        $viewFile,
        "require __DIR__ . '/_topbar.php'",
        $errors,
        "Admin {$viewName} page should use the shared admin topbar partial"
    );
    require_snippet(
        $viewFile,
        'js-sidebar-backdrop',
        $errors,
        "Admin {$viewName} page should include the mobile sidebar backdrop"
    );
    ensure_not_contains(
        $viewFile,
        'assets/css/global-theme.css',
        $errors,
        "Admin {$viewName} page should not include the global theme stylesheet"
    );
    ensure_not_contains(
        $viewFile,
        'assets/js/theme.js',
        $errors,
        "Admin {$viewName} page should not include the legacy theme script"
    );
    ensure_not_contains(
        $viewFile,
        'tailwind.config =',
        $errors,
        "Admin {$viewName} page should not use inline Tailwind configuration"
    );
    ensure_not_contains(
        $viewFile,
        '<script>',
        $errors,
        "Admin {$viewName} page should not include inline scripts"
    );
}

$adminDashboardView = $rootDir . '/app/Views/admin/dashboard.php';
require_snippet(
    $adminDashboardView,
    'meta name="app-global-assets" content="off"',
    $errors,
    'Admin dashboard page should opt out of global assets'
);
require_snippet(
    $adminDashboardView,
    'data-view="admin/dashboard"',
    $errors,
    'Admin dashboard page should expose its admin view id'
);
require_snippet(
    $adminDashboardView,
    'tailwind_theme_bootstrap.js',
    $errors,
    'Admin dashboard page should load the shared Tailwind bootstrap script'
);
require_snippet(
    $adminDashboardView,
    'cdn.tailwindcss.com',
    $errors,
    'Admin dashboard page should load Tailwind from the approved CDN'
);
require_snippet(
    $adminDashboardView,
    'admin_dashboard_page.css',
    $errors,
    'Admin dashboard page should load its dedicated stylesheet'
);
require_snippet(
    $adminDashboardView,
    'admin_dashboard_page.js',
    $errors,
    'Admin dashboard page should load its dedicated script'
);
ensure_not_contains(
    $adminDashboardView,
    "require __DIR__ . '/_head_assets.php'",
    $errors,
    'Admin dashboard page should not use the shared admin head assets'
);
ensure_not_contains(
    $adminDashboardView,
    "require __DIR__ . '/_sidebar.php'",
    $errors,
    'Admin dashboard page should not use the shared admin sidebar partial'
);
ensure_not_contains(
    $adminDashboardView,
    "require __DIR__ . '/_topbar.php'",
    $errors,
    'Admin dashboard page should not use the shared admin topbar partial'
);
ensure_not_contains(
    $adminDashboardView,
    'assets/css/admin-dashboard.css',
    $errors,
    'Admin dashboard page should not include the shared admin stylesheet'
);
ensure_not_contains(
    $adminDashboardView,
    'assets/js/admin_dashboard.js',
    $errors,
    'Admin dashboard page should not include the shared admin shell script'
);
ensure_not_contains(
    $adminDashboardView,
    'assets/css/global-theme.css',
    $errors,
    'Admin dashboard page should not include the global theme stylesheet'
);
ensure_not_contains(
    $adminDashboardView,
    'assets/js/theme.js',
    $errors,
    'Admin dashboard page should not include the legacy theme script'
);
ensure_not_contains(
    $adminDashboardView,
    'tailwind.config =',
    $errors,
    'Admin dashboard page should not use inline Tailwind configuration'
);
ensure_not_contains(
    $adminDashboardView,
    '<script>',
    $errors,
    'Admin dashboard page should not include inline scripts'
);

ensure_not_contains(
    $rootDir . '/app/Views/admin/_topbar.php',
    '<script>',
    $errors,
    'Admin topbar should not include inline scripts'
);
$courseContentView = $rootDir . '/app/Views/teacher/course_content.php';
if (is_file($courseContentView)) {
    ensure_not_contains(
        $courseContentView,
        'assets/css/global-theme.css',
        $errors,
        'Teacher course content page should not include the global theme stylesheet'
    );
    ensure_not_contains(
        $courseContentView,
        'assets/js/theme.js',
        $errors,
        'Teacher course content page should not include the legacy theme script'
    );
}

require_snippet(
    $rootDir . '/app/Views/teacher/_topbar.php',
    'js-sidebar-toggle md:hidden',
    $errors,
    'Teacher topbar missing mobile menu button'
);
require_snippet(
    $rootDir . '/app/Views/student/_topbar.php',
    'js-sidebar-toggle md:hidden',
    $errors,
    'Student topbar missing mobile menu button'
);

if ($errors !== []) {
    echo "UI responsive guard FAILED\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "UI responsive guard OK\n";
exit(0);
