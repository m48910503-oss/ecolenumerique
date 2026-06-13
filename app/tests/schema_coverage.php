<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only\n";
    exit(1);
}

$rootDir = dirname(__DIR__, 2);
chdir($rootDir);

$config = require $rootDir . '/includes/config.php';
require_once $rootDir . '/includes/db.php';

/** @var PDO $pdo */
try {
    $pdo = db($config['db']);
} catch (Throwable $e) {
    fwrite(STDERR, "Schema coverage FAILED\n");
    fwrite(STDERR, "Database connection error: " . $e->getMessage() . "\n");
    fwrite(STDERR, "Check MySQL service and includes/config.php db settings.\n");
    exit(1);
}

/**
 * @param list<string> $items
 */
function format_list(array $items): string
{
    if ($items === []) {
        return "  - none\n";
    }
    sort($items);
    $out = '';
    foreach ($items as $item) {
        $out .= "  - {$item}\n";
    }
    return $out;
}

function has_table(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table LIMIT 1'
    );
    $stmt->execute([':table' => $table]);
    return (bool) $stmt->fetchColumn();
}

function has_column(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column
         LIMIT 1'
    );
    $stmt->execute([
        ':table' => $table,
        ':column' => $column,
    ]);
    return (bool) $stmt->fetchColumn();
}

function has_index(PDO $pdo, string $table, string $indexName): bool
{
    $stmt = $pdo->prepare(
        'SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND INDEX_NAME = :index_name
         LIMIT 1'
    );
    $stmt->execute([
        ':table' => $table,
        ':index_name' => $indexName,
    ]);
    return (bool) $stmt->fetchColumn();
}

function has_auto_increment_primary_id(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT 1
         FROM INFORMATION_SCHEMA.COLUMNS c
         JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE k
           ON k.TABLE_SCHEMA = c.TABLE_SCHEMA
          AND k.TABLE_NAME = c.TABLE_NAME
          AND k.COLUMN_NAME = c.COLUMN_NAME
          AND k.CONSTRAINT_NAME = \'PRIMARY\'
         WHERE c.TABLE_SCHEMA = DATABASE()
           AND c.TABLE_NAME = :table
           AND c.COLUMN_NAME = \'id\'
           AND c.EXTRA LIKE \'%auto_increment%\'
         LIMIT 1'
    );
    $stmt->execute([':table' => $table]);
    return (bool) $stmt->fetchColumn();
}

$requiredTables = [
    'users',
    'courses',
    'course_views',
    'course_chapters',
    'course_lessons',
    'chapter_documents',
    'enrollments',
    'notifications',
    'push_subscriptions',
    'push_notification_queue',
    'orders',
    'order_items',
    'payments',
    'payment_webhook_events',
    'exam_sessions',
    'exam_questions',
    'exam_options',
    'exam_attempts',
    'exam_answers',
    'exam_files',
    'course_meetings',
    'referrals',
    'teacher_referral_settings',
    'referral_commissions',
    'course_promotions',
    'course_promotion_targets',
    'course_promotion_company_targets',
    'course_promotion_company_uses',
    'companies',
    'company_members',
    'company_invites',
    'company_teams',
    'company_team_members',
    'company_onboarding_tasks',
    'company_sso_configs',
    'company_activity_events',
    'company_metrics_daily',
    'company_success_reviews',
    'admin_secondary_permissions',
    'certificates',
    'platform_settings',
    'teacher_payouts',
    'withdrawals',
    'audit_logs',
];

$requiredColumns = [
    ['course_meetings', 'duration_min'],
    ['courses', 'update_in_progress'],
    ['courses', 'share_token'],
    ['courses', 'referral_override'],
    ['courses', 'referral_l1_rate'],
    ['courses', 'referral_l2_rate'],
    ['courses', 'referral_l2_enabled'],
    ['courses', 'promo_type'],
    ['courses', 'promo_value'],
    ['courses', 'promo_start_at'],
    ['courses', 'promo_end_at'],
    ['course_promotions', 'promo_mode'],
    ['course_promotions', 'coupon_code'],
    ['course_ratings', 'is_hidden_by_teacher'],
    ['course_ratings', 'hidden_by_user_id'],
    ['course_ratings', 'hidden_at'],
    ['course_ratings', 'teacher_reply_text'],
    ['course_ratings', 'teacher_reply_user_id'],
    ['course_ratings', 'teacher_reply_at'],
    ['course_views', 'viewer_key'],
    ['users', 'auth_provider'],
    ['users', 'mfa_enabled'],
    ['users', 'mfa_enabled_at'],
    ['users', 'referral_code'],
    ['users', 'signature_path'],
    ['company_teams', 'name'],
    ['company_team_members', 'member_id'],
    ['company_onboarding_tasks', 'task_key'],
    ['company_onboarding_tasks', 'status'],
    ['company_sso_configs', 'is_enabled'],
    ['company_activity_events', 'event_type'],
    ['company_metrics_daily', 'metric_date'],
    ['company_success_reviews', 'review_date'],
    ['company_success_reviews', 'risk_level'],
    ['exam_attempts', 'meeting_id'],
    ['exam_attempts', 'variant_index'],
    ['exam_questions', 'variant'],
    ['exam_files', 'drive_file_id'],
    ['exam_files', 'drive_view_link'],
    ['exam_files', 'original_name'],
    ['exam_files', 'mime_type'],
    ['exam_files', 'size_bytes'],
    ['exam_sessions', 'drive_subject_link'],
    ['exam_sessions', 'drive_subject_name'],
    ['exam_sessions', 'settings_json'],
    ['platform_settings', 'setting_key'],
    ['platform_settings', 'setting_value'],
    ['teacher_referral_settings', 'teacher_id'],
    ['teacher_referral_settings', 'l1_rate'],
    ['teacher_referral_settings', 'l2_rate'],
    ['teacher_referral_settings', 'l2_enabled'],
    ['admin_secondary_permissions', 'user_id'],
    ['admin_secondary_permissions', 'permissions_json'],
    ['admin_secondary_permissions', 'is_active'],
    ['payments', 'provider'],
    ['payments', 'reference'],
    ['payments', 'status'],
    ['order_items', 'promo_scope'],
    ['order_items', 'promotion_id'],
    ['order_items', 'coupon_code'],
    ['payment_webhook_events', 'provider'],
    ['payment_webhook_events', 'event_key'],
    ['payment_webhook_events', 'processed_at'],
    ['teacher_payouts', 'teacher_id'],
    ['teacher_payouts', 'status'],
    ['withdrawals', 'reason'],
    ['certificates', 'code'],
    ['certificates', 'status'],
];

$requiredIndexes = [
    ['course_views', 'uq_course_views_course_viewer'],
    ['users', 'idx_users_auth_provider'],
    ['users', 'idx_users_mfa_enabled'],
    ['users', 'uq_users_referral_code'],
    ['courses', 'uq_courses_share_token'],
    ['company_members', 'idx_company_members_role'],
    ['company_teams', 'uq_company_teams_name'],
    ['company_team_members', 'uq_company_team_member'],
    ['company_onboarding_tasks', 'uq_company_onboarding_task'],
    ['company_sso_configs', 'uq_company_sso_company'],
    ['company_activity_events', 'idx_company_activity_company_created'],
    ['company_metrics_daily', 'uq_company_metrics_daily'],
    ['company_success_reviews', 'idx_company_success_reviews_company_date'],
    ['course_ratings', 'idx_rating_course_hidden'],
    ['course_ratings', 'idx_rating_course_reply'],
    ['admin_secondary_permissions', 'uq_admin_secondary_permissions_user'],
    ['admin_secondary_permissions', 'idx_admin_secondary_permissions_active'],
    ['exam_questions', 'idx_exam_questions_variant'],
    ['exam_attempts', 'idx_exam_attempts_variant'],
    ['exam_attempts', 'idx_exam_attempts_meeting_id'],
    ['certificates', 'uq_cert_code'],
    ['certificates', 'uq_cert_user_course'],
    ['teacher_payouts', 'idx_teacher_payouts_teacher'],
    ['teacher_payouts', 'idx_teacher_payouts_status'],
    ['push_subscriptions', 'uq_push_subscriptions_endpoint_hash'],
    ['push_subscriptions', 'idx_push_subscriptions_user_active'],
    ['push_notification_queue', 'idx_push_queue_status_next'],
    ['payment_webhook_events', 'uq_payment_webhook_events_provider_key'],
    ['course_promotions', 'uq_course_promotions_coupon_code'],
    ['order_items', 'idx_order_items_promo_scope'],
    ['order_items', 'idx_order_items_coupon_code'],
    ['order_items', 'idx_order_items_promotion_id'],
];

$requiredAutoIncrementIds = [
    'courses',
    'course_promotions',
    'course_chapters',
    'course_lessons',
    'chapter_documents',
    'course_meetings',
    'exam_sessions',
    'exam_questions',
    'exam_options',
    'exam_attempts',
    'exam_answers',
    'exam_files',
];

$missingTables = [];
foreach ($requiredTables as $table) {
    if (!has_table($pdo, $table)) {
        $missingTables[] = $table;
    }
}

$missingColumns = [];
foreach ($requiredColumns as [$table, $column]) {
    if (!has_column($pdo, $table, $column)) {
        $missingColumns[] = $table . '.' . $column;
    }
}

$missingIndexes = [];
foreach ($requiredIndexes as [$table, $indexName]) {
    if (!has_index($pdo, $table, $indexName)) {
        $missingIndexes[] = $table . '.' . $indexName;
    }
}

$missingAutoIncrementIds = [];
foreach ($requiredAutoIncrementIds as $table) {
    if (!has_auto_increment_primary_id($pdo, $table)) {
        $missingAutoIncrementIds[] = $table . '.id';
    }
}

$hasIssues = ($missingTables !== []) || ($missingColumns !== []) || ($missingIndexes !== []) || ($missingAutoIncrementIds !== []);
if (!$hasIssues) {
    echo "Schema coverage OK\n";
    exit(0);
}

echo "Schema coverage FAILED\n";
echo "Missing tables:\n";
echo format_list($missingTables);
echo "Missing columns:\n";
echo format_list($missingColumns);
echo "Missing indexes:\n";
echo format_list($missingIndexes);
echo "Missing AUTO_INCREMENT primary ids:\n";
echo format_list($missingAutoIncrementIds);
echo "\nApply all SQL migrations in /database (schema.sql + schema_v*.sql) then rerun this check.\n";
exit(1);
