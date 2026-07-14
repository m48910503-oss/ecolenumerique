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
    $rootDir . '/app/Views/web/catalogue.php',
    'data-filter-close',
    $errors,
    'Catalogue page should expose a mobile filter close button'
);
require_snippet(
    $rootDir . '/assets/js/web_catalogue.js',
    'data-filter-close',
    $errors,
    'Catalogue page should wire the mobile filter close button'
);

require_snippet(
    $rootDir . '/assets/js/sidebar_toggle.js',
    'const desktopBreakpoint = () =>',
    $errors,
    'Shared sidebar toggle should support a configurable desktop breakpoint'
);
require_snippet(
    $rootDir . '/assets/css/teacher-dashboard.css',
    '@media (max-width: 1023.98px)',
    $errors,
    'Teacher dashboard shell should keep a compact drawer layout on medium widths'
);
require_snippet(
    $rootDir . '/assets/css/teacher-dashboard.css',
    'body.app-role-teacher[data-view="teacher/course_create"] .teacher-dashboard-layout',
    $errors,
    'Course creation and edition pages should remove the sidebar offset'
);
require_snippet(
    $rootDir . '/assets/css/teacher-dashboard.css',
    'body.app-role-teacher[data-view="teacher/course_create"] #teacher-dashboard-sidebar',
    $errors,
    'Course creation and edition pages should hide the sidebar panel'
);
require_snippet(
    $rootDir . '/assets/css/teacher-dashboard.css',
    'body.app-role-teacher[data-view="teacher/course_create"] .teacher-dashboard-topbar .js-sidebar-toggle',
    $errors,
    'Course creation and edition pages should hide the sidebar toggle'
);
require_snippet(
    $rootDir . '/assets/css/student_dashboard.css',
    '@media (max-width: 1023.98px)',
    $errors,
    'Student dashboard shell should keep a compact drawer layout on medium widths'
);
require_snippet(
    $rootDir . '/assets/css/admin-dashboard.css',
    '@media (max-width: 1023.98px)',
    $errors,
    'Admin dashboard shell should keep a compact drawer layout on medium widths'
);
require_snippet(
    $rootDir . '/assets/js/course_complete_builder.js',
    'indexedDB.open("ecole-course-drafts", 1)',
    $errors,
    'Course create builder should persist thumbnail drafts in IndexedDB'
);
require_snippet(
    $rootDir . '/assets/js/course_complete_builder.js',
    'has_cover_file',
    $errors,
    'Course create builder should track whether a thumbnail file is present in the local draft'
);
require_snippet(
    $rootDir . '/assets/js/course_complete_builder.js',
    'restoreCoverDraftStorage(form);',
    $errors,
    'Course create builder should restore the locally saved thumbnail draft'
);
require_snippet(
    $rootDir . '/assets/js/course_complete_builder.js',
    'localDraftCoverPreviewKey(form)',
    $errors,
    'Course create builder should keep a separate preview draft key for refresh recovery'
);
require_snippet(
    $rootDir . '/assets/js/course_complete_builder.js',
    'restoreCoverPreviewStorage(form)',
    $errors,
    'Course create builder should restore the thumbnail preview from the browser draft when possible'
);
require_snippet(
    $rootDir . '/assets/js/course_complete_builder.js',
    'captureCoverPreviewDraft(form, file)',
    $errors,
    'Course create builder should capture the thumbnail preview as soon as a file is selected'
);
require_snippet(
    $rootDir . '/assets/js/course_complete_builder.js',
    'var durationValue = duration > 0 ? String(duration) : "";',
    $errors,
    'Course create builder should not prefill a fake lesson duration'
);
require_snippet(
    $rootDir . '/assets/js/course_complete_builder.js',
    'window.importQcmFile = async function (button)',
    $errors,
    'Course create builder should support QCM CSV/Excel import'
);
require_snippet(
    $rootDir . '/assets/js/course_complete_builder.js',
    'qcmOptionLabel(index)',
    $errors,
    'Course create builder should label QCM proposals with letters'
);
require_snippet(
    $rootDir . '/app/Services/Teacher/CourseCreateService.php',
    'Durée obligatoire pour la leçon "',
    $errors,
    'Course create service should reject lessons without a duration'
);
require_snippet(
    $rootDir . '/app/Views/teacher/course_create.php',
    'data-course-create-draft-prompt',
    $errors,
    'Course create page should expose the local draft prompt flag'
);
require_snippet(
    $rootDir . '/app/Views/teacher/course_create.php',
    'course-create-draft-modal',
    $errors,
    'Course create page should render the draft choice modal'
);
require_snippet(
    $rootDir . '/app/Views/teacher/course_create.php',
    'Choisissez un brouillon à ouvrir',
    $errors,
    'Course create page should show the draft selection list in the modal'
);
require_snippet(
    $rootDir . '/app/Views/teacher/course_create.php',
    'data-course-create-draft-list-panel',
    $errors,
    'Course create page should keep the draft list hidden until the second step'
);
require_snippet(
    $rootDir . '/app/Views/teacher/course_create.php',
    'max-h-[calc(100vh-2rem)] overflow-y-auto',
    $errors,
    'Course create modal should stay scrollable on smaller screens'
);
require_snippet(
    $rootDir . '/assets/js/course_complete_builder.js',
    'showDraftPrompt(form, pendingDraft)',
    $errors,
    'Course create builder should prompt before restoring a local draft'
);
require_snippet(
    $rootDir . '/assets/js/course_complete_builder.js',
    'restoreDraftIntoForm(form, draft)',
    $errors,
    'Course create builder should restore the selected draft after confirmation'
);
require_snippet(
    $rootDir . '/assets/js/course_complete_builder.js',
    'if (draftPrompt.enabled && pendingDraft)',
    $errors,
    'Course create builder should prompt whenever a local draft exists'
);
require_snippet(
    $rootDir . '/app/Views/teacher/course_create.php',
    'course-builder-referral-switch',
    $errors,
    'Course create referral control should use the refreshed switch markup'
);
require_snippet(
    $rootDir . '/assets/css/course_create_builder.css',
    '.course-builder-referral-switch.is-active',
    $errors,
    'Course create referral control should style the active state clearly'
);
require_snippet(
    $rootDir . '/assets/css/course_create_builder.css',
    '.course-builder-workflow__step--complete',
    $errors,
    'Course create workflow should style completed steps in blue'
);
require_snippet(
    $rootDir . '/assets/css/course_create_builder.css',
    '.course-builder-workflow__step--pending',
    $errors,
    'Course create workflow should style remaining steps as warning'
);
ensure_not_contains(
    $rootDir . '/app/Views/teacher/course_create.php',
    'À compléter',
    $errors,
    'Course create workflow should not use the old "À compléter" label'
);

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
    'app-typography.css',
    $errors,
    'Admin shared head should load the shared typography stylesheet'
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
    'categories' => $rootDir . '/app/Views/admin/categories.php',
    'course_insights' => $rootDir . '/app/Views/admin/course_insights.php',
    'course_preview' => $rootDir . '/app/Views/admin/course_preview.php',
    'courses' => $rootDir . '/app/Views/admin/courses.php',
    'error_logs' => $rootDir . '/app/Views/admin/error_logs.php',
    'exam_preview' => $rootDir . '/app/Views/admin/exam_preview.php',
    'announcements' => $rootDir . '/app/Views/admin/announcements.php',
    'notifications' => $rootDir . '/app/Views/admin/notifications.php',
    'sales' => $rootDir . '/app/Views/admin/sales.php',
    'secondary_admins' => $rootDir . '/app/Views/admin/secondary_admins.php',
    'secondary_admins_form' => $rootDir . '/app/Views/admin/secondary_admins_form.php',
    'site_settings' => $rootDir . '/app/Views/admin/site_settings.php',
    'statistics' => $rootDir . '/app/Views/admin/statistics.php',
    'support_agents' => $rootDir . '/app/Views/admin/support_agents.php',
    'support' => $rootDir . '/app/Views/admin/support.php',
    'teacher_dossier' => $rootDir . '/app/Views/admin/teacher_dossier.php',
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

require_snippet(
    $rootDir . '/app/Views/admin/support.php',
    'support-conv-pagination',
    $errors,
    'Admin support page should expose pagination for the history list'
);
require_snippet(
    $rootDir . '/app/Views/admin/support.php',
    'support-mobile-back',
    $errors,
    'Admin support page should expose the mobile back link for the conversation view'
);
require_snippet(
    $rootDir . '/assets/js/support_chat.js',
    'support-mobile-history-view',
    $errors,
    'Support chat script should support the mobile history-only mode'
);
require_snippet(
    $rootDir . '/assets/css/admin-dashboard.css',
    'support-mobile-thread-view',
    $errors,
    'Admin dashboard stylesheet should support the mobile conversation-only mode'
);

require_snippet(
    $rootDir . '/app/Views/admin/course_preview.php',
    'data-scroll-top-root="1"',
    $errors,
    'Admin course preview page should expose an internal scroll root for the mobile shell'
);

$adminDashboardView = $rootDir . '/app/Views/admin/dashboard.php';
require_snippet(
    $adminDashboardView,
    'data-view="admin/dashboard"',
    $errors,
    'Admin dashboard page should expose its admin view id'
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
    'assets/css/global-theme.css',
    $errors,
    'Admin dashboard page should not include the global theme stylesheet'
);
require_snippet(
    $adminDashboardView,
    "require __DIR__ . '/_head_assets.php'",
    $errors,
    'Admin dashboard page should use the shared admin head assets'
);
require_snippet(
    $adminDashboardView,
    "require __DIR__ . '/_sidebar.php'",
    $errors,
    'Admin dashboard page should use the shared admin sidebar partial'
);
require_snippet(
    $adminDashboardView,
    "require __DIR__ . '/_topbar.php'",
    $errors,
    'Admin dashboard page should use the shared admin topbar partial'
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
