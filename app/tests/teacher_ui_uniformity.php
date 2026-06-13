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
 * @param array<int,string> $needles
 * @param array<int,string> $errors
 */
function ensure_contains_any(string $file, array $needles, array &$errors, string $message): void
{
    $content = @file_get_contents($file);
    if ($content === false) {
        $errors[] = "Cannot read {$file}";
        return;
    }
    foreach ($needles as $needle) {
        if (strpos($content, $needle) !== false) {
            return;
        }
    }
    $errors[] = $message . " ({$file})";
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

$teacherViews = [
    $rootDir . '/app/Views/teacher/courses.php',
    $rootDir . '/app/Views/teacher/withdrawals.php',
];

foreach ($teacherViews as $viewFile) {
    ensure_contains($viewFile, 'sticky top-2', $errors, 'Missing sticky period bar');
    ensure_contains($viewFile, 'name="period"', $errors, 'Missing period selector');
    ensure_contains($viewFile, "'7d' => '7j'", $errors, 'Missing 7j/7d period option');
    ensure_contains($viewFile, "'30d' => '30j'", $errors, 'Missing 30j/30d period option');
    ensure_contains($viewFile, "'90d' => '90j'", $errors, 'Missing 90j/90d period option');
    ensure_contains($viewFile, 'value="custom"', $errors, 'Missing custom period option');
    ensure_contains($viewFile, 'Actif', $errors, 'Missing normalized badge label: Actif');
    ensure_contains($viewFile, 'En attente', $errors, 'Missing normalized badge label: En attente');
    ensure_contains($viewFile, 'Urgent', $errors, 'Missing normalized badge label: Urgent');
    ensure_contains_any(
        $viewFile,
        ['TerminÃ©', 'Termin&eacute;', 'Terminé'],
        $errors,
        'Missing normalized badge label: Termin&eacute;'
    );
}

// POST forms must carry period context for redirect consistency.
ensure_contains(
    $rootDir . '/app/Views/teacher/withdrawals.php',
    'type="hidden" name="period"',
    $errors,
    'Withdrawals POST forms should include hidden period'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/courses.php',
    'name="list_query"',
    $errors,
    'Courses POST actions should include list query state'
);

// Period propagation after POST actions
ensure_contains(
    $rootDir . '/app/Services/Teacher/WithdrawalsService.php',
    "teacher/withdrawals' . \$this->buildPeriodQuery(",
    $errors,
    'Withdrawals redirect should preserve period query'
);
ensure_contains(
    $rootDir . '/app/Services/Teacher/WithdrawalsService.php',
    '$periodInput = $query;',
    $errors,
    'Withdrawals should resolve period from query/post context'
);
ensure_contains(
    $rootDir . '/app/Services/Teacher/WithdrawalsService.php',
    "foreach (['period', 'from', 'to'] as \$periodField)",
    $errors,
    'Withdrawals should merge period fields from POST'
);
ensure_contains(
    $rootDir . '/app/Services/Teacher/WithdrawalsService.php',
    "if (isset(\$post[\$periodField]) && (string) \$post[\$periodField] !== '')",
    $errors,
    'Withdrawals should validate POST period values'
);

ensure_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'Créer une promotion',
    $errors,
    'Promotions French labels should be harmonized'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotion_offer_form.php',
    'data-promo-summary-course',
    $errors,
    'Promotion wizard should expose a live summary course field'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotion_offer_form.php',
    'data-promo-summary-final',
    $errors,
    'Promotion wizard should expose a live summary final-price field'
);
ensure_contains(
    $rootDir . '/assets/js/promotions_page.js',
    'data-promo-summary-course',
    $errors,
    'Promotion wizard JS should keep the summary card in sync'
);
ensure_contains(
    $rootDir . '/app/Controllers/Teacher/PromotionsController.php',
    "header('Location: ' . \$this->siteUrl('teacher/promotions'));",
    $errors,
    'Promotion wizard should return to the unfiltered main listing after success'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/withdrawals.php',
    'M&eacute;thode',
    $errors,
    'Withdrawals French labels should be harmonized'
);

ensure_contains(
    $rootDir . '/app/Services/Teacher/CoursesService.php',
    "'period' => (string) (\$periodState['key'] ?? 'all')",
    $errors,
    'Courses list state should carry period key'
);

// Teacher feedback should be normalized to toast-capable scripts.
ensure_contains(
    $rootDir . '/app/Views/teacher/_topbar.php',
    'assets/js/global_popups.js',
    $errors,
    'Teacher topbar should load global popup toasts'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/_topbar.php',
    'assets/js/feedback_visibility.js',
    $errors,
    'Teacher topbar should load feedback visibility normalizer'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/apply.php',
    'assets/js/global_popups.js',
    $errors,
    'Teacher apply page should load global popup toasts'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/apply.php',
    'assets/js/feedback_visibility.js',
    $errors,
    'Teacher apply page should load feedback visibility normalizer'
);
ensure_contains(
    $rootDir . '/app/Views/web/support.php',
    "\$supportDataView = \$useTeacherShell ? 'teacher/support' : 'student/support';",
    $errors,
    'Support page should expose a teacher support shell'
);
ensure_contains(
    $rootDir . '/app/Views/web/support.php',
    "require __DIR__ . '/../teacher/_sidebar.php'",
    $errors,
    'Support page should reuse the teacher sidebar in teacher mode'
);
ensure_contains(
    $rootDir . '/app/Views/web/support.php',
    "require __DIR__ . '/../teacher/_topbar.php'",
    $errors,
    'Support page should reuse the teacher topbar in teacher mode'
);
ensure_contains(
    $rootDir . '/app/Views/web/support.php',
    'teacher-dashboard.css',
    $errors,
    'Support page should load the teacher dashboard stylesheet in teacher mode'
);
ensure_contains(
    $rootDir . '/app/Views/web/support.php',
    'teacher_dashboard.js',
    $errors,
    'Support page should load the shared teacher dashboard script in teacher mode'
);
ensure_contains(
    $rootDir . '/app/Views/web/support.php',
    'teacher-dashboard-layout',
    $errors,
    'Support page should keep the teacher dashboard shell in teacher mode'
);
ensure_contains(
    $rootDir . '/app/Views/web/support.php',
    'teacher-dashboard-page teacher-support-page',
    $errors,
    'Support page should carry the standard teacher page class in teacher mode'
);
ensure_contains(
    $rootDir . '/app/Views/web/support.php',
    '$showSupportFab = false;',
    $errors,
    'Support page should hide the teacher dashboard support FAB in teacher mode'
);
ensure_contains(
    $rootDir . '/assets/js/support_page.js',
    "['student/support', 'teacher/support', 'web/support'].includes(body.dataset.view || '')",
    $errors,
    'Support page script should accept the teacher support view'
);

foreach ([
    $rootDir . '/app/Views/teacher/course_actions.php',
    $rootDir . '/app/Views/teacher/course_edit.php',
    $rootDir . '/app/Views/teacher/course_preview.php',
    $rootDir . '/app/Views/teacher/course_updates.php',
    $rootDir . '/app/Views/teacher/exam_preview.php',
    $rootDir . '/app/Views/teacher/exams_create.php',
] as $teacherWorkflowView) {
    ensure_contains(
        $teacherWorkflowView,
        'teacher-dashboard.css',
        $errors,
        'Teacher workflow pages should load the shared teacher dashboard stylesheet'
    );
}

ensure_contains(
    $rootDir . '/app/Views/teacher/dashboard.php',
    '<option value="all"',
    $errors,
    'Teacher dashboard chart should expose the all-time range option'
);
ensure_contains(
    $rootDir . '/app/Services/Teacher/DashboardService.php',
    "['30d', '3m', 'all']",
    $errors,
    'Teacher dashboard service should accept the all-time chart range'
);
ensure_contains(
    $rootDir . '/app/Models/TeacherDashboardModel.php',
    'fetchTeacherMetricFirstDate',
    $errors,
    'Teacher dashboard model should expose the first metric date for all-time charts'
);

// Promotions/coupons merged page
ensure_contains(
    $rootDir . '/app/View/helpers.php',
    "'label' => 'Promotions et coupons'",
    $errors,
    'Teacher menu should expose merged Promotions et coupons entry'
);
ensure_contains(
    $rootDir . '/app/View/helpers.php',
    "'label' => 'Avis & Notes'",
    $errors,
    'Teacher menu should expose Avis & Notes entry'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/_sidebar.php',
    "dashboard_menu_config('teacher')",
    $errors,
    'Teacher sidebar should use the shared teacher menu configuration'
);
ensure_contains(
    $rootDir . '/app/Controllers/Teacher/PromotionsController.php',
    "'all'",
    $errors,
    'Promotions controller should render merged promotions/coupons mode'
);
ensure_contains(
    $rootDir . '/app/Controllers/Teacher/CouponsController.php',
    "siteUrl('teacher/promotions')",
    $errors,
    'Coupons alias route should redirect to merged promotions page'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'meta name="app-global-assets" content="off"',
    $errors,
    'Promotions page should opt out of global assets'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'teacher-dashboard.css',
    $errors,
    'Promotions page should load the teacher dashboard stylesheet'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'teacher_dashboard.js',
    $errors,
    'Promotions page should load the shared teacher dashboard script'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'sidebar_toggle.js',
    $errors,
    'Promotions page should load the sidebar toggle script'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'js-sidebar-backdrop',
    $errors,
    'Promotions page should expose a mobile sidebar backdrop'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'teacher-dashboard-layout',
    $errors,
    'Promotions page should keep the responsive dashboard shell'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'tab-promotions',
    $errors,
    'Promotions page should expose the promotions tab'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'tab-coupons',
    $errors,
    'Promotions page should expose the coupons tab'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    "site_url('teacher/promotions' . \$buildQuery(['form' => 'promotion', 'step' => 1]))",
    $errors,
    'Promotions page should link to the promotion form page'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    "site_url('teacher/promotions' . \$buildQuery(['form' => 'coupon', 'step' => 1]))",
    $errors,
    'Promotions page should link to the coupon form page'
);
ensure_not_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'open-promotion-modal',
    $errors,
    'Promotions page should not keep the old promotion modal trigger'
);
ensure_not_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'open-coupon-modal',
    $errors,
    'Promotions page should not keep the old coupon modal trigger'
);
ensure_not_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'promotion-modal',
    $errors,
    'Promotions page should not render the old promotion modal'
);
ensure_not_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'coupon-modal',
    $errors,
    'Promotions page should not render the old coupon modal'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    "require __DIR__ . '/_sidebar.php'",
    $errors,
    'Promotions page should use the shared dashboard sidebar partial'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    "require __DIR__ . '/_topbar.php'",
    $errors,
    'Promotions page should use the shared dashboard topbar partial'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'teacher-dashboard-main',
    $errors,
    'Promotions page should keep the dashboard main shell'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'promotionRows',
    $errors,
    'Promotions page should render backend promotion rows'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'couponRows',
    $errors,
    'Promotions page should render backend coupon rows'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'couponUsageSummary',
    $errors,
    'Promotions page should expose coupon usage summary data'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'couponExpiringRows',
    $errors,
    'Promotions page should expose expiring coupon data'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'coupon_code_mode',
    $errors,
    'Promotions page should allow choosing between manual and generated coupon codes'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'data-coupon-code-mode',
    $errors,
    'Promotions page should expose the coupon code mode toggle'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'data-coupon-code-input',
    $errors,
    'Promotions page should expose the manual coupon code input'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotion_offer_form.php',
    'wizardBaseUrl',
    $errors,
    'Promotion form page should initialize the wizard base URL'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotion_offer_form.php',
    'wizardStep1Url',
    $errors,
    'Promotion form page should expose the first wizard step'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotion_offer_form.php',
    'wizardStep2Url',
    $errors,
    'Promotion form page should expose the second wizard step'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotion_offer_form.php',
    'wizardStep3Url',
    $errors,
    'Promotion form page should expose the recap step'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotion_offer_form.php',
    'data-promo-create-page',
    $errors,
    'Promotion form page should expose the dedicated creation form'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotion_offer_form.php',
    'Récapitulatif avant validation',
    $errors,
    'Promotion form page should expose the final recap section'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'name="_csrf"',
    $errors,
    'Promotions page should include CSRF tokens on POST forms'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'teacher-withdrawal-toast',
    $errors,
    'Promotions page should reuse the withdrawals toast component'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'promo_schedule_mode',
    $errors,
    'Promotions page should expose the schedule mode selector'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'data-promo-schedule-mode',
    $errors,
    'Promotions page should expose schedule mode toggles'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'data-promo-schedule-duration',
    $errors,
    'Promotions page should expose schedule duration fields'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'data-promo-schedule-custom',
    $errors,
    'Promotions page should expose custom schedule fields'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'data-edit-toggle="promo-edit-row-',
    $errors,
    'Promotions page should render promotion edit toggles'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'data-edit-toggle="coupon-edit-row-',
    $errors,
    'Promotions page should render coupon edit toggles'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'action" value="update_promo"',
    $errors,
    'Promotions page should render update forms'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'data-promo-form',
    $errors,
    'Promotions page should render real backend forms'
);
ensure_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'data-delete-confirm',
    $errors,
    'Promotions page should render delete confirmation forms'
);
ensure_not_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'Soldes d\'été',
    $errors,
    'Promotions page should no longer contain demo promotion names'
);
ensure_not_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'BIENVENUE10',
    $errors,
    'Promotions page should no longer contain demo coupon codes'
);
ensure_not_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'DEV50',
    $errors,
    'Promotions page should no longer contain demo coupon rows'
);
ensure_not_contains(
    $rootDir . '/app/Views/teacher/promotions.php',
    'Sophie Durand',
    $errors,
    'Promotions page should no longer contain hard-coded teacher identity'
);
ensure_contains(
    $rootDir . '/app/Services/Teacher/PromotionsService.php',
    'database/schema_v31.sql',
    $errors,
    'Promotions service should expose actionable migration for user targeting'
);
ensure_contains(
    $rootDir . '/app/Services/Teacher/PromotionsService.php',
    'database/schema_v32.sql',
    $errors,
    'Promotions service should expose actionable migration for company targeting'
);

// Drive degraded flows must keep a clear connect CTA on upload pages.
foreach ([
    $rootDir . '/app/Views/teacher/apply.php',
    $rootDir . '/app/Views/teacher/course_edit.php',
    $rootDir . '/app/Views/teacher/exams_create.php',
] as $driveView) {
    ensure_contains($driveView, 'Connecter Drive', $errors, 'Drive upload flow should expose connect CTA');
}

// SQL optimization guard: no PHP filtering helpers for period on promotions list.
ensure_contains(
    $rootDir . '/app/Models/TeacherPromotionsModel.php',
    'promo_end_at >= :period_start_at',
    $errors,
    'Promotions SQL should filter by overlap start'
);
ensure_contains(
    $rootDir . '/app/Models/TeacherPromotionsModel.php',
    'promo_start_at < :period_end_at',
    $errors,
    'Promotions SQL should filter by overlap end'
);

if ($errors !== []) {
    echo "Teacher UI uniformity FAILED\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "Teacher UI uniformity OK\n";
exit(0);
