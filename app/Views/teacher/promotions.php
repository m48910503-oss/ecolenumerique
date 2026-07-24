<?php
declare(strict_types=1);

$teacherUser = is_array($user ?? null) ? $user : [];
$teacherDisplayName = function_exists('dashboard_user_display_name')
    ? dashboard_user_display_name($teacherUser, 'Enseignant')
    : trim((string) (($teacherUser['first_name'] ?? '') . ' ' . ($teacherUser['last_name'] ?? '')));
$teacherDisplayName = $teacherDisplayName !== '' ? $teacherDisplayName : 'Enseignant';
$teacherEmail = trim((string) ($teacherUser['email'] ?? ''));
$teacherAvatarUrl = function_exists('dashboard_user_avatar_url') ? dashboard_user_avatar_url($teacherUser) : '';
$teacherInitials = function_exists('dashboard_user_initials')
    ? dashboard_user_initials($teacherUser, 'EN')
    : strtoupper(substr($teacherDisplayName, 0, 2));
$csrfToken = function_exists('view_csrf_token') ? view_csrf_token() : '';

$errors = array_values(is_array($errors ?? null) ? $errors : []);
$success = trim((string) ($success ?? ''));
$courses = array_values(is_array($courses ?? null) ? $courses : []);
$promotionRows = array_values(is_array($promotionRows ?? null) ? $promotionRows : []);
$couponRows = array_values(is_array($couponRows ?? null) ? $couponRows : []);
$promotionTotal = max(0, (int) ($promotionTotal ?? count($promotionRows)));
$couponTotal = max(0, (int) ($couponTotal ?? count($couponRows)));
$promotionActiveCount = max(0, (int) ($promotionActiveCount ?? 0));
$couponActiveCount = max(0, (int) ($couponActiveCount ?? 0));
$promoInputs = is_array($promoInputs ?? null) ? $promoInputs : [];
$canTargetPromos = (bool) ($canTargetPromos ?? false);
$couponCodeSupported = (bool) ($couponCodeSupported ?? false);
$promoTargetCandidates = array_values(is_array($promoTargetCandidates ?? null) ? $promoTargetCandidates : []);
$promoTargetEmails = is_array($promoTargetEmails ?? null) ? $promoTargetEmails : [];
$promoCompanyCandidates = array_values(is_array($promoCompanyCandidates ?? null) ? $promoCompanyCandidates : []);
$promoCompanyTargets = is_array($promoCompanyTargets ?? null) ? $promoCompanyTargets : [];
$couponUsageSummary = is_array($couponUsageSummary ?? null) ? $couponUsageSummary : [
    'uses_count' => 0,
    'codes_count' => 0,
    'revenue_xof' => 0,
    'last_paid_at' => null,
];
$expiringOfferRows = array_values(is_array($expiringOfferRows ?? null) ? $expiringOfferRows : (is_array($couponExpiringRows ?? null) ? $couponExpiringRows : []));
$couponPerformanceRows = array_values(is_array($couponPerformanceRows ?? null) ? $couponPerformanceRows : []);
$promoComparison = is_array($promoComparison ?? null) ? $promoComparison : [
    'coupon' => ['revenue_xof' => 0, 'inscriptions' => 0, 'orders_count' => 0, 'avg_basket_xof' => 0],
    'promo' => ['revenue_xof' => 0, 'inscriptions' => 0, 'orders_count' => 0, 'avg_basket_xof' => 0],
];
$listingState = is_array($listingState ?? null) ? $listingState : [
    'q' => '',
    'courseId' => 0,
    'state' => 'all',
    'offer' => 'all',
    'perPage' => 10,
    'promotionPage' => 1,
    'couponPage' => 1,
];
$refreshedAtIso = trim((string) ($refreshedAtIso ?? ''));
$courseStatusLabels = is_array($courseStatusLabels ?? null) ? $courseStatusLabels : [
    'draft' => 'Brouillon',
    'submitted' => 'Soumis',
    'approved' => 'Publié',
    'rejected' => 'Rejeté',
];
$successToastMessage = $success !== '' ? $success : '';
$toastKind = '';
$toastTitle = '';
$toastMessages = [];
if ($errors !== []) {
    $toastKind = 'error';
    $toastTitle = 'Erreur';
    $toastMessages = $errors;
}
$normalizeScheduleMode = static function (?string $value, string $default = 'duration'): string {
    $mode = strtolower(trim((string) $value));
    if (in_array($mode, ['duration', 'custom'], true)) {
        return $mode;
    }
    return $default;
};
$formatDateInput = static function (?string $value): string {
    $raw = trim((string) $value);
    if ($raw === '') {
        return '';
    }
    $ts = strtotime($raw);
    if ($ts === false) {
        return '';
    }
    return date('Y-m-d', $ts);
};
$formatDateTimeLocalInput = static function (?string $value): string {
    $raw = trim((string) $value);
    if ($raw === '') {
        return '';
    }
    $ts = strtotime($raw);
    if ($ts === false) {
        return '';
    }
    return date('Y-m-d\TH:i', $ts);
};
$buildEditDefaults = static function (array $row) use ($formatDateTimeLocalInput, $normalizeScheduleMode, $promoTargetEmails, $promoCompanyTargets): array {
    $promoId = (int) ($row['id'] ?? 0);
    $startAt = trim((string) ($row['promo_start_at'] ?? ''));
    $endAt = trim((string) ($row['promo_end_at'] ?? ''));
    $startTs = $startAt !== '' ? strtotime($startAt) : false;
    $endTs = $endAt !== '' ? strtotime($endAt) : false;
    $durationDays = 1;
    if ($startTs !== false && $endTs !== false && $endTs > $startTs) {
        $durationDays = max(1, (int) ceil(($endTs - $startTs) / 86400));
    }

    $emails = array_values(array_filter(array_map(static fn ($value) => trim((string) $value), $promoTargetEmails[$promoId] ?? [])));
    $company = is_array($promoCompanyTargets[$promoId] ?? null) ? $promoCompanyTargets[$promoId] : [];
    $targetType = 'public';
    $targetCompanyId = '';
    $targetCompanySeats = '';
    if ($company !== []) {
        $targetType = 'company';
        $targetCompanyId = (string) ((int) ($company['company_id'] ?? 0));
        $targetCompanySeats = (string) ((int) ($company['seat_limit'] ?? 0));
    } elseif ($emails !== []) {
        $targetType = 'individual';
    }

    return [
        'promo_type' => (string) ($row['promo_type'] ?? 'percent'),
        'promo_amount' => (string) ($row['promo_value'] ?? ''),
        'promo_percent' => (string) ($row['promo_value'] ?? ''),
        'promo_duration_days' => (string) $durationDays,
        'promo_schedule_mode' => 'custom',
        'promo_start_at' => $formatDateTimeLocalInput($startAt),
        'promo_end_at' => $formatDateTimeLocalInput($endAt),
        'promo_target_type' => $targetType,
        'promo_targets' => implode(', ', $emails),
        'promo_company_id' => $targetCompanyId,
        'promo_company_seats' => $targetCompanySeats,
        'coupon_code_mode' => 'manual',
        'coupon_code' => (string) ($row['coupon_code'] ?? ''),
    ];
};
$promotionPerfById = [];
foreach ($couponPerformanceRows as $performanceRow) {
    $performanceId = (int) ($performanceRow['id'] ?? 0);
    if ($performanceId > 0) {
        $promotionPerfById[$performanceId] = $performanceRow;
    }
}

$courseMap = [];
foreach ($courses as $course) {
    $courseId = (int) ($course['id'] ?? 0);
    if ($courseId > 0) {
        $courseMap[$courseId] = $course;
    }
}

$frMonths = [
    1 => 'janvier',
    2 => 'février',
    3 => 'mars',
    4 => 'avril',
    5 => 'mai',
    6 => 'juin',
    7 => 'juillet',
    8 => 'août',
    9 => 'septembre',
    10 => 'octobre',
    11 => 'novembre',
    12 => 'décembre',
];

$formatMoney = static function (int|float $value): string {
    return number_format((float) $value, 0, ',', ' ') . ' F CFA';
};

$formatDate = static function (?string $value) use ($frMonths): string {
    $raw = trim((string) $value);
    if ($raw === '') {
        return '—';
    }
    $ts = strtotime($raw);
    if ($ts === false) {
        return '—';
    }
    $day = date('j', $ts);
    $month = (string) ($frMonths[(int) date('n', $ts)] ?? date('m', $ts));
    $year = date('Y', $ts);
    return $day . ' ' . $month . ' ' . $year;
};

$formatDateTime = static function (?string $value) use ($frMonths): string {
    $raw = trim((string) $value);
    if ($raw === '') {
        return '—';
    }
    $ts = strtotime($raw);
    if ($ts === false) {
        return '—';
    }
    $day = date('j', $ts);
    $month = (string) ($frMonths[(int) date('n', $ts)] ?? date('m', $ts));
    $year = date('Y', $ts);
    $time = date('H:i', $ts);
    return $day . ' ' . $month . ' ' . $year . ' ' . $time;
};

$buildQuery = static function (array $overrides = []) use ($listingState): string {
    $params = [];

    $search = trim((string) ($listingState['q'] ?? ''));
    if ($search !== '') {
        $params['q'] = $search;
    }

    $courseId = (int) ($listingState['courseId'] ?? 0);
    if ($courseId > 0) {
        $params['course_id'] = $courseId;
    }

    $state = (string) ($listingState['state'] ?? 'all');
    if ($state !== 'all') {
        $params['state'] = $state;
    }

    $offer = (string) ($listingState['offer'] ?? 'all');
    if ($offer !== 'all') {
        $params['offer'] = $offer;
    }

    $perPage = (int) ($listingState['perPage'] ?? 10);
    if ($perPage !== 10) {
        $params['size'] = $perPage;
    }

    $promotionPage = (int) ($listingState['promotionPage'] ?? 1);
    if ($promotionPage > 1) {
        $params['pp'] = $promotionPage;
    }

    $couponPage = (int) ($listingState['couponPage'] ?? 1);
    if ($couponPage > 1) {
        $params['cp'] = $couponPage;
    }

    foreach ($overrides as $key => $value) {
        if ($value === null || $value === '') {
            unset($params[$key]);
            continue;
        }
        $params[$key] = $value;
    }

    $query = http_build_query($params);
    return $query !== '' ? ('?' . $query) : '';
};

$formatRelativeDays = static function (int $days, bool $future = true): string {
    $days = max(0, $days);
    if ($days === 0) {
        return "aujourd'hui";
    }

    if ($days === 1) {
        return $future ? 'demain' : 'hier';
    }

    return $future ? ('dans ' . $days . ' jours') : ('il y a ' . $days . ' jours');
};

$promoLifecycleState = static function (array $row): string {
    $now = time();
    $startAt = trim((string) ($row['promo_start_at'] ?? ''));
    $endAt = trim((string) ($row['promo_end_at'] ?? ''));
    $startTs = $startAt !== '' ? strtotime($startAt) : false;
    $endTs = $endAt !== '' ? strtotime($endAt) : false;

    if ($startTs !== false && $startTs > $now) {
        return 'scheduled';
    }

    if ($endTs !== false && $endTs <= $now) {
        return 'ended';
    }

    if ($endTs !== false && $endTs <= strtotime('+7 days', $now)) {
        return 'expiring';
    }

    return 'active';
};

$promoTimeHint = static function (array $row) use ($formatRelativeDays): string {
    $now = time();
    $todayTs = strtotime(date('Y-m-d', $now)) ?: $now;
    $startAt = trim((string) ($row['promo_start_at'] ?? ''));
    $endAt = trim((string) ($row['promo_end_at'] ?? ''));
    $startTs = $startAt !== '' ? strtotime($startAt) : false;
    $endTs = $endAt !== '' ? strtotime($endAt) : false;

    if ($startTs !== false && $startTs > $now) {
        $days = max(0, (int) floor(($startTs - $todayTs) / 86400));
        return 'Démarre ' . $formatRelativeDays($days, true);
    }

    if ($endTs !== false && $endTs <= $now) {
        $endDayTs = strtotime(date('Y-m-d', $endTs)) ?: $endTs;
        $days = max(0, (int) floor(($todayTs - $endDayTs) / 86400));
        return 'Terminé ' . $formatRelativeDays($days, false);
    }

    if ($endTs !== false) {
        $days = max(0, (int) floor(($endTs - $todayTs) / 86400));
        return 'Se termine ' . $formatRelativeDays($days, true);
    }

    return 'Sans échéance';
};

$currentActionUrl = site_url('teacher/promotions' . $buildQuery());
$activeTab = (string) ($listingState['offer'] ?? 'all');
if (!in_array($activeTab, ['all', 'promotion', 'coupon'], true)) {
    $activeTab = 'all';
}
$promotionsTabActive = $activeTab !== 'coupon';
$couponsTabActive = $activeTab === 'coupon';
$expiringOfferMeta = match ($activeTab) {
    'coupon' => [
        'title' => 'Codes promo expirant bientôt',
    ],
    'promotion' => [
        'title' => 'Promotions expirant bientôt',
    ],
    default => [
        'title' => 'Offres expirant bientôt',
    ],
};

$allOfferRows = array_merge($promotionRows, $couponRows);
$promotionRevenue = (int) (($promoComparison['promo']['revenue_xof'] ?? 0));
$couponRevenue = (int) (($promoComparison['coupon']['revenue_xof'] ?? 0));
$comparisonRevenueTotal = max(1, $promotionRevenue + $couponRevenue);
$comparisonPromotionShare = (int) round(($promotionRevenue / $comparisonRevenueTotal) * 100);
$comparisonCouponShare = (int) round(($couponRevenue / $comparisonRevenueTotal) * 100);
$overviewActiveCount = $promotionActiveCount + $couponActiveCount;

$promoDiscountLabel = static function (array $row) use ($formatMoney): array {
    $type = strtolower(trim((string) ($row['promo_type'] ?? '')));
    $value = is_numeric($row['promo_value'] ?? null) ? (float) $row['promo_value'] : 0.0;
    if ($type === 'amount') {
        return [
            'main' => $formatMoney((int) round($value)),
            'sub' => 'Prix final',
        ];
    }
    if ($type === 'percent') {
        $percent = rtrim(rtrim(number_format($value, 2, ',', ' '), '0'), ',');
        return [
            'main' => '-' . $percent . '%',
            'sub' => 'Réduction',
        ];
    }
    return [
        'main' => '—',
        'sub' => 'Réduction',
    ];
};

$promoStatusMeta = static function (array $row): array {
    $startAt = trim((string) ($row['promo_start_at'] ?? ''));
    $endAt = trim((string) ($row['promo_end_at'] ?? ''));
    $now = time();
    $startTs = $startAt !== '' ? strtotime($startAt) : false;
    $endTs = $endAt !== '' ? strtotime($endAt) : false;

    if ($startTs !== false && $startTs > $now) {
        return [
            'label' => 'Programmé',
            'class' => 'border border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-800/40 dark:bg-amber-900/30 dark:text-amber-200',
            'icon' => 'fa-clock',
            'iconClass' => 'text-amber-600 dark:text-amber-300',
            'iconBg' => 'bg-amber-100 dark:bg-amber-900/50',
        ];
    }

    if ($endTs !== false && $endTs <= $now) {
        return [
            'label' => 'Terminé',
            'class' => 'border border-slate-200 bg-slate-100 text-slate-700 dark:border-slate-700 dark:bg-slate-700/80 dark:text-slate-200',
            'icon' => 'fa-flag-checkered',
            'iconClass' => 'text-slate-500 dark:text-slate-200',
            'iconBg' => 'bg-slate-200 dark:bg-slate-600/80',
        ];
    }

    if ($endTs !== false && $endTs <= strtotime('+7 days', $now)) {
        return [
            'label' => 'Expire bientôt',
            'class' => 'border border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-800/40 dark:bg-amber-900/30 dark:text-amber-200',
            'icon' => 'fa-hourglass-half',
            'iconClass' => 'text-amber-600 dark:text-amber-300',
            'iconBg' => 'bg-amber-100 dark:bg-amber-900/50',
        ];
    }

    return [
        'label' => 'Actif',
        'class' => 'border border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-800/40 dark:bg-emerald-900/30 dark:text-emerald-200',
        'icon' => 'fa-check-circle',
        'iconClass' => 'text-emerald-600 dark:text-emerald-300',
        'iconBg' => 'bg-emerald-100 dark:bg-emerald-900/50',
    ];
};

$promoTargetMeta = static function (array $row) use ($promoTargetEmails, $promoCompanyTargets): array {
    $promoId = (int) ($row['id'] ?? 0);
    $emails = array_values(array_filter(array_map(static fn ($value) => trim((string) $value), $promoTargetEmails[$promoId] ?? [])));
    $company = is_array($promoCompanyTargets[$promoId] ?? null) ? $promoCompanyTargets[$promoId] : [];
    if ($company !== []) {
        $companyName = trim((string) ($company['company_name'] ?? ''));
        $seatLimit = (int) ($company['seat_limit'] ?? 0);
        return [
            'label' => $companyName !== '' ? $companyName : 'Entreprise ciblée',
            'detail' => $seatLimit > 0 ? ($seatLimit . ' places réservées') : 'Ciblage entreprise',
            'class' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
        ];
    }

    if ($emails !== []) {
        return [
            'label' => count($emails) . ' étudiants',
            'detail' => 'Ciblage individuel',
            'class' => 'bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-300',
        ];
    }

    return [
        'label' => 'Tout le monde',
        'detail' => 'Promotion publique',
        'class' => 'bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-300',
    ];
};

$buyerLabel = static function (array $row): string {
    $first = trim((string) ($row['buyer_first_name'] ?? ''));
    $last = trim((string) ($row['buyer_last_name'] ?? ''));
    $email = trim((string) ($row['buyer_email'] ?? ''));
    $name = trim($first . ' ' . $last);
    if ($name !== '') {
        return $name;
    }
    if ($email !== '') {
        $prefix = trim((string) explode('@', $email, 2)[0]);
        $prefix = str_replace(['.', '_', '-'], ' ', $prefix);
        return $prefix !== '' ? ucwords($prefix) : $email;
    }
    return 'Client';
};

$courseOptionLabel = static function (array $course) use ($formatMoney): string {
    $title = trim((string) ($course['title'] ?? ''));
    $price = (int) ($course['price_xof'] ?? 0);
    return $title !== '' ? ($title . ' · ' . $formatMoney($price)) : 'Cours sans titre';
};

$courseStatusLabel = static function (string $status) use ($courseStatusLabels): string {
    $status = strtolower(trim($status));
    return (string) ($courseStatusLabels[$status] ?? $status ?: '—');
};

?>
<!DOCTYPE html>
<html lang="fr" class="light" data-default-theme="light" data-view="teacher/promotions">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="app-global-assets" content="off">
  <title>Promotions et codes promo · Enseignant · École Numérique</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="<?php echo e(site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()))); ?>"></script>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="<?php echo e(site_url('assets/css/teacher-dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/teacher-dashboard.css') ?: time()))); ?>">
  <script src="<?php echo e(site_url('assets/js/sidebar_toggle.js')); ?>" defer></script>
  <script src="<?php echo e(site_url('assets/js/teacher_dashboard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/teacher_dashboard.js') ?: time()))); ?>" defer></script>
  <script src="<?php echo e(site_url('assets/js/promotions_page.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/promotions_page.js') ?: time()))); ?>" defer></script>
  <style>
    .hover-lift {
      transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .hover-lift:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 20px -8px rgba(0, 0, 0, 0.08);
    }

    .tab-btn {
      position: relative;
      border: 1px solid rgb(226 232 240);
      background: rgb(255 255 255);
      color: rgb(71 85 105);
      box-shadow: none;
    }

    .tab-btn:hover {
      background: rgb(248 250 252);
    }

    .tab-btn.is-active {
      transform: translateY(-1px);
      color: rgb(15 23 42);
    }

    #tab-promotions.is-active {
      border-color: rgb(125 211 252);
      background: linear-gradient(180deg, rgb(224 242 254) 0%, rgb(240 249 255) 100%);
      box-shadow: inset 0 0 0 1px rgb(186 230 253 / 0.9), 0 10px 22px -16px rgb(14 165 233 / 0.55);
    }

    #tab-coupons.is-active {
      border-color: rgb(192 132 252);
      background: linear-gradient(180deg, rgb(243 232 255) 0%, rgb(250 245 255) 100%);
      box-shadow: inset 0 0 0 1px rgb(221 214 254 / 0.95), 0 10px 22px -16px rgb(168 85 247 / 0.5);
    }

    .tab-chip {
      transition: background-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
    }

    #tab-promotions .tab-chip {
      background: rgb(224 242 254);
      color: rgb(2 132 199);
    }

    #tab-coupons .tab-chip {
      background: rgb(243 232 255);
      color: rgb(124 58 237);
    }

    #tab-promotions.is-active .tab-chip {
      background: rgb(2 132 199);
      color: rgb(255 255 255);
      box-shadow: inset 0 0 0 1px rgb(255 255 255 / 0.25);
    }

    #tab-coupons.is-active .tab-chip {
      background: rgb(124 58 237);
      color: rgb(255 255 255);
      box-shadow: inset 0 0 0 1px rgb(255 255 255 / 0.25);
    }

    .tab-count {
      transition: background-color 0.2s ease, color 0.2s ease;
    }

    #tab-promotions .tab-count {
      background: rgb(224 242 254);
      color: rgb(3 105 161);
    }

    #tab-coupons .tab-count {
      background: rgb(243 232 255);
      color: rgb(107 33 168);
    }

    #tab-promotions.is-active .tab-count,
    #tab-coupons.is-active .tab-count {
      background: rgb(255 255 255 / 0.88);
    }

    .tab-active-pill {
      display: none;
      align-items: center;
      border-radius: 9999px;
      padding: 0.3rem 0.6rem;
      font-size: 0.625rem;
      font-weight: 700;
      letter-spacing: 0;
      text-transform: none;
    }

    #tab-promotions .tab-active-pill {
      background: rgb(2 132 199);
      color: rgb(255 255 255);
    }

    #tab-coupons .tab-active-pill {
      background: rgb(124 58 237);
      color: rgb(255 255 255);
    }

    .tab-btn.is-active .tab-active-pill {
      display: inline-flex;
    }

    .teacher-promotions-metric-icon,
    .teacher-promotions-tab-icon {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      border: 1px solid rgba(148, 163, 184, 0.2);
      background: rgba(148, 163, 184, 0.12);
      color: rgb(71, 85, 105);
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.65);
    }

    .teacher-promotions-metric-icon {
      width: 2.5rem;
      height: 2.5rem;
      border-radius: 0.875rem;
    }

    .teacher-promotions-tab-icon {
      width: 2rem;
      height: 2rem;
      border-radius: 0.75rem;
    }

    .teacher-promotions-metric-icon :is(i, svg),
    .teacher-promotions-tab-icon :is(i, svg) {
      --app-icon-color: currentColor;
      color: inherit;
    }

    html.dark .teacher-promotions-metric-icon,
    html[data-theme="dark"] .teacher-promotions-metric-icon,
    html.dark .teacher-promotions-tab-icon,
    html[data-theme="dark"] .teacher-promotions-tab-icon {
      border-color: rgba(148, 163, 184, 0.22);
      background: rgba(15, 23, 42, 0.45);
      color: rgb(226, 232, 240);
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.06);
    }

    .teacher-promotions-action-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      width: 2.5rem;
      height: 2.5rem;
      border: 1px solid transparent;
      border-radius: 0.875rem;
      color: #fff;
      line-height: 1;
      box-shadow: 0 12px 22px -16px rgba(15, 23, 42, 0.45);
      transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease, border-color 0.18s ease;
    }

    .teacher-promotions-action-btn :is(i, svg) {
      color: #fff !important;
      fill: currentColor;
      stroke: currentColor;
      --app-icon-color: #fff;
    }

    .teacher-promotions-action-btn:hover {
      transform: translateY(-1px);
    }

    .teacher-promotions-action-btn:focus-visible {
      outline: 2px solid rgba(148, 163, 184, 0.8);
      outline-offset: 2px;
    }

    .teacher-promotions-action-btn--copy {
      border-color: rgb(71 85 105);
      background: rgb(71 85 105);
    }

    .teacher-promotions-action-btn--copy:hover {
      border-color: rgb(51 65 85);
      background: rgb(51 65 85);
    }

    .teacher-promotions-action-btn--edit {
      border-color: rgb(2 132 199);
      background: rgb(2 132 199);
    }

    .teacher-promotions-action-btn--edit:hover {
      border-color: rgb(3 105 161);
      background: rgb(3 105 161);
    }

    .teacher-promotions-action-btn--delete {
      border-color: rgb(225 29 72);
      background: rgb(225 29 72);
    }

    .teacher-promotions-action-btn--delete:hover {
      border-color: rgb(190 18 60);
      background: rgb(190 18 60);
    }

    html.dark .teacher-promotions-action-btn--copy,
    html[data-theme="dark"] .teacher-promotions-action-btn--copy {
      border-color: rgb(100 116 139);
      background: rgb(100 116 139);
    }

    html.dark .teacher-promotions-action-btn--copy:hover,
    html[data-theme="dark"] .teacher-promotions-action-btn--copy:hover {
      border-color: rgb(71 85 105);
      background: rgb(71 85 105);
    }

    html.dark .teacher-promotions-action-btn--edit,
    html[data-theme="dark"] .teacher-promotions-action-btn--edit {
      border-color: rgb(14 165 233);
      background: rgb(14 165 233);
    }

    html.dark .teacher-promotions-action-btn--edit:hover,
    html[data-theme="dark"] .teacher-promotions-action-btn--edit:hover {
      border-color: rgb(2 132 199);
      background: rgb(2 132 199);
    }

    html.dark .teacher-promotions-action-btn--delete,
    html[data-theme="dark"] .teacher-promotions-action-btn--delete {
      border-color: rgb(244 63 94);
      background: rgb(244 63 94);
    }

    html.dark .teacher-promotions-action-btn--delete:hover,
    html[data-theme="dark"] .teacher-promotions-action-btn--delete:hover {
      border-color: rgb(225 29 72);
      background: rgb(225 29 72);
    }
  </style>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body data-view="teacher/promotions" class="app-role-teacher teacher-promotions-page font-sans antialiased text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-900 transition-colors duration-300 min-h-screen overflow-hidden overflow-x-hidden">
  <div class="teacher-dashboard-layout relative h-screen overflow-hidden pt-16 sm:pt-[4.5rem] md:pl-72 md:flex-row">
    <?php $teacherNavActive = 'promotions'; ?>
    <?php require __DIR__ . '/_sidebar.php'; ?>
    <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

    <main class="teacher-dashboard-main h-full overflow-y-auto bg-gray-50 dark:bg-gray-900" data-scroll-top-root="1">
      <?php $teacherTopTitle = 'Promotions et codes promo'; ?>
      <?php $teacherTopSubtitle = 'Espace formateur'; ?>
      <?php $showCatalogue = false; ?>
      <?php require __DIR__ . '/_topbar.php'; ?>

      <div class="p-4 pb-24 space-y-6 animate-fade-up sm:p-6 sm:pb-24">
      <?php $teacherWelcomeTitle = 'Offres commerciales'; ?>
      <?php $teacherWelcomeSummary = (string) ($promotionTotal + $couponTotal) . ' offres · ' . (string) ($promotionActiveCount + $couponActiveCount) . ' actives · ' . (string) count($courses) . ' cours publiés.'; ?>
      <?php $teacherWelcomeActionType = $courses === [] ? 'button' : 'link'; ?>
      <?php $teacherWelcomeActionUrl = $courses === [] ? '#' : site_url('teacher/promotions' . $buildQuery(['form' => 'promotion', 'step' => 1])); ?>
      <?php $teacherWelcomeActionLabel = 'Créer une promotion'; ?>
      <?php $teacherWelcomeActionIcon = 'plus-square'; ?>
      <?php $teacherWelcomeActionAttrs = $courses === [] ? 'disabled aria-disabled="true"' : ''; ?>
      <?php require __DIR__ . '/_welcome_hero.php'; ?>
      <?php $teacherWelcomeActionType = $teacherWelcomeActionUrl = $teacherWelcomeActionLabel = $teacherWelcomeActionIcon = $teacherWelcomeActionAttrs = null; ?>

      <div class="flex flex-wrap justify-end gap-3">
        <?php if ($courses === [] || !$couponCodeSupported): ?>
          <button type="button" disabled class="inline-flex items-center justify-center gap-2 rounded-xl border border-violet-200 bg-violet-50 px-4 py-2.5 text-sm font-semibold text-violet-700 transition cursor-not-allowed opacity-50 hover:border-violet-300 hover:bg-violet-50 dark:border-violet-900/50 dark:bg-violet-900/20 dark:text-violet-200 dark:hover:bg-violet-900/20">
            <i class="fas fa-ticket-alt"></i>
            <span>Créer un code promo</span>
          </button>
        <?php else: ?>
          <a href="<?php echo e(site_url('teacher/promotions' . $buildQuery(['form' => 'coupon', 'step' => 1]))); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-violet-200 bg-violet-50 px-4 py-2.5 text-sm font-semibold text-violet-700 transition hover:border-violet-300 hover:bg-violet-100 dark:border-violet-900/50 dark:bg-violet-900/20 dark:text-violet-200 dark:hover:bg-violet-900/35">
            <i class="fas fa-ticket-alt"></i>
            <span>Créer un code promo</span>
          </a>
        <?php endif; ?>
      </div>

      <?php if ($successToastMessage !== ''): ?>
        <div
          hidden
          data-notification-toast="1"
          data-notification-toast-type="success"
          data-notification-toast-message="<?php echo e($successToastMessage); ?>"
        ></div>
      <?php endif; ?>

      <?php if ($toastKind === 'error'): ?>
        <div
          data-promotion-toast="1"
          role="alert"
          aria-live="assertive"
          class="teacher-withdrawal-toast teacher-withdrawal-toast--<?php echo e($toastKind); ?>"
        >
          <div class="teacher-withdrawal-toast__copy">
            <span class="teacher-withdrawal-toast__icon" aria-hidden="true">
              <i class="<?php echo $toastKind === 'error' ? 'fas fa-exclamation-circle' : 'fas fa-check-circle'; ?>"></i>
            </span>
            <div class="min-w-0">
              <p class="teacher-withdrawal-toast__title"><?php echo e($toastTitle); ?></p>
              <?php if (count($toastMessages) === 1): ?>
                <p class="teacher-withdrawal-toast__message"><?php echo e((string) $toastMessages[0]); ?></p>
              <?php else: ?>
                <ul class="teacher-withdrawal-toast__list">
                  <?php foreach ($toastMessages as $toastMessage): ?>
                    <li><?php echo e((string) $toastMessage); ?></li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>
          </div>
          <span class="teacher-withdrawal-toast__bar" aria-hidden="true"></span>
        </div>
      <?php endif; ?>

      <?php if (!$couponCodeSupported): ?>
        <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sky-800 shadow-soft dark:border-sky-900/40 dark:bg-sky-900/20 dark:text-sky-200">
          <div class="flex items-start gap-3">
            <i class="fas fa-info-circle mt-0.5"></i>
            <p class="text-sm">Module code promo désactivé.</p>
          </div>
        </div>
      <?php endif; ?>

      <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800 hover-lift">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-sm font-semibold text-slate-600 dark:text-slate-300">Promotions</p>
              <p class="mt-2 text-3xl font-extrabold text-slate-950 dark:text-white"><?php echo e((string) $promotionTotal); ?></p>
            </div>
            <span class="teacher-promotions-metric-icon">
              <i class="fas fa-percent"></i>
            </span>
          </div>
          <p class="mt-3 text-sm font-medium text-slate-600 dark:text-slate-300"><?php echo e((string) $promotionActiveCount); ?> actives</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800 hover-lift">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-sm font-semibold text-slate-600 dark:text-slate-300">Codes promo</p>
              <p class="mt-2 text-3xl font-extrabold text-slate-950 dark:text-white"><?php echo e((string) $couponTotal); ?></p>
            </div>
            <span class="teacher-promotions-metric-icon">
              <i class="fas fa-ticket-alt"></i>
            </span>
          </div>
          <p class="mt-3 text-sm font-medium text-slate-600 dark:text-slate-300"><?php echo e((string) $couponActiveCount); ?> actifs</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800 hover-lift">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-sm font-semibold text-slate-600 dark:text-slate-300">Utilisations</p>
              <p class="mt-2 text-3xl font-extrabold text-slate-950 dark:text-white"><?php echo e((string) ((int) ($couponUsageSummary['uses_count'] ?? 0))); ?></p>
            </div>
            <span class="teacher-promotions-metric-icon">
              <i class="fas fa-chart-line"></i>
            </span>
          </div>
          <p class="mt-3 text-sm font-medium text-slate-600 dark:text-slate-300"><?php echo e((string) ((int) ($couponUsageSummary['codes_count'] ?? 0))); ?> code(s) unique(s)</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800 hover-lift">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-sm font-semibold text-slate-600 dark:text-slate-300">Revenus codes</p>
              <p class="mt-2 text-3xl font-extrabold text-slate-950 dark:text-white"><?php echo e($formatMoney((int) ($couponUsageSummary['revenue_xof'] ?? 0))); ?></p>
            </div>
            <span class="teacher-promotions-metric-icon">
              <i class="fas fa-wallet"></i>
            </span>
          </div>
          <p class="mt-3 text-sm font-medium text-slate-600 dark:text-slate-300">
            Dernier paiement : <?php echo e($formatDateTime((string) ($couponUsageSummary['last_paid_at'] ?? ''))); ?>
          </p>
        </article>
      </section>

      <section class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-2 shadow-sm dark:border-slate-700 dark:bg-slate-800 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap gap-2">
          <button id="tab-promotions" class="tab-btn inline-flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition <?php echo $promotionsTabActive ? 'is-active' : ''; ?>" aria-pressed="<?php echo $promotionsTabActive ? 'true' : 'false'; ?>"<?php echo $promotionsTabActive ? ' aria-current="page"' : ''; ?>>
            <span class="tab-chip teacher-promotions-tab-icon">
              <i class="fas fa-percent text-sm"></i>
            </span>
            <span>Promotions publiques</span>
            <span class="tab-active-pill">Actif</span>
            <span class="tab-count ml-1 inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold"><?php echo e((string) $promotionTotal); ?></span>
          </button>
          <button id="tab-coupons" class="tab-btn inline-flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition <?php echo $couponsTabActive ? 'is-active' : ''; ?>" aria-pressed="<?php echo $couponsTabActive ? 'true' : 'false'; ?>"<?php echo $couponsTabActive ? ' aria-current="page"' : ''; ?>>
            <span class="tab-chip teacher-promotions-tab-icon">
              <i class="fas fa-ticket-alt text-sm"></i>
            </span>
            <span>Codes promo privés</span>
            <span class="tab-active-pill">Actif</span>
            <span class="tab-count ml-1 inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold"><?php echo e((string) $couponTotal); ?></span>
          </button>
        </div>
      </section>

      <div>
        <div id="content-promotions" class="space-y-5 <?php echo $promotionsTabActive ? '' : 'hidden'; ?>">
          <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card dark:border-slate-700 dark:bg-slate-800">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
              <div class="flex items-center justify-between gap-4">
                <div>
                  <h2 class="text-base font-bold text-gray-900 dark:text-white">Promotions publiques</h2>
                </div>
                <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                  <?php echo e((string) $promotionActiveCount); ?> actives
                </span>
              </div>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="border-b border-gray-200 text-gray-600 dark:border-gray-700 dark:text-gray-400">
                  <tr>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Référence</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Cours</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Réduction</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Public cible</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Période</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Statut</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold">Actions</th>
                  </tr>
                </thead>
                <tbody id="promotions-table-body" class="divide-y divide-gray-100 dark:divide-gray-700">
                  <?php if ($promotionRows !== []): ?>
                    <?php foreach ($promotionRows as $row): ?>
                      <?php
                        $promoId = (int) ($row['id'] ?? 0);
                        $courseTitle = trim((string) ($row['title'] ?? ''));
                        $promoType = strtolower(trim((string) ($row['promo_type'] ?? '')));
                        $promoValue = is_numeric($row['promo_value'] ?? null) ? (float) $row['promo_value'] : 0.0;
                        $reference = trim((string) ($row['coupon_code'] ?? ''));
                        $statusMeta = $promoStatusMeta($row);
                        $discountMeta = $promoDiscountLabel($row);
                        $targetMeta = $promoTargetMeta($row);
                        $startAt = $formatDateTime((string) ($row['promo_start_at'] ?? ''));
                        $endAt = $formatDateTime((string) ($row['promo_end_at'] ?? ''));
                        $course = is_array($courseMap[(int) ($row['course_id'] ?? 0)] ?? null) ? $courseMap[(int) ($row['course_id'] ?? 0)] : [];
                        $coursePrice = (int) ($course['price_xof'] ?? ($row['price_xof'] ?? 0));
                        $promoEditInputs = is_array($promoInputs[$promoId] ?? null) ? $promoInputs[$promoId] : [];
                        $promoFormValues = array_merge($buildEditDefaults($row), $promoEditInputs);
                        $promoEditScheduleMode = $normalizeScheduleMode((string) ($promoFormValues['promo_schedule_mode'] ?? 'custom'), 'custom');
                        $promoEditTargetType = (string) ($promoFormValues['promo_target_type'] ?? 'public');
                        $promoEditCouponModeRaw = strtolower(trim((string) ($promoFormValues['coupon_code_mode'] ?? 'manual')));
                        $promoEditCouponMode = in_array($promoEditCouponModeRaw, ['auto', 'manual'], true) ? $promoEditCouponModeRaw : 'manual';
                        $promoTimelineState = $promoLifecycleState($row);
                        $promoTimelineHint = $promoTimeHint($row);
                      ?>
                      <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/25">
                        <td class="px-6 py-4">
                          <div class="font-semibold text-gray-900 dark:text-white"><?php echo e($reference !== '' ? $reference : ('Promotion #' . $promoId)); ?></div>
                          <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo e($promoType !== '' ? ucfirst($promoType) : 'Promotion'); ?></div>
                        </td>
                        <td class="px-6 py-4">
                          <div class="font-medium text-gray-900 dark:text-white"><?php echo e($courseTitle !== '' ? $courseTitle : 'Cours sans titre'); ?></div>
                          <div class="app-price text-xs"><?php echo e($formatMoney($coursePrice)); ?></div>
                        </td>
                        <td class="px-6 py-4">
                          <div class="font-semibold text-gray-900 dark:text-white"><?php echo e($discountMeta['main']); ?></div>
                          <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo e($discountMeta['sub']); ?></div>
                        </td>
                        <td class="px-6 py-4">
                          <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold <?php echo e($targetMeta['class']); ?>">
                            <?php echo e($targetMeta['label']); ?>
                          </span>
                          <div class="mt-1 text-xs text-gray-500 dark:text-gray-400"><?php echo e($targetMeta['detail']); ?></div>
                        </td>
                        <td class="px-6 py-4">
                          <div class="text-xs font-medium text-gray-900 dark:text-white"><?php echo e($startAt); ?></div>
                          <div class="text-xs text-gray-500 dark:text-gray-400">→ <?php echo e($endAt); ?></div>
                          <div class="text-[11px] font-medium <?php echo e($promoTimelineState === 'scheduled' ? 'text-amber-600 dark:text-amber-300' : ($promoTimelineState === 'ended' ? 'text-slate-500 dark:text-slate-400' : 'text-emerald-600 dark:text-emerald-300')); ?>"><?php echo e($promoTimelineHint); ?></div>
                        </td>
                        <td class="px-6 py-4">
                          <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold <?php echo e($statusMeta['class']); ?>">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full <?php echo e((string) ($statusMeta['iconBg'] ?? 'bg-gray-100 dark:bg-gray-700')); ?>">
                              <i class="fas <?php echo e((string) ($statusMeta['icon'] ?? 'fa-circle')); ?> text-[10px] <?php echo e((string) ($statusMeta['iconClass'] ?? 'text-gray-500 dark:text-gray-300')); ?>"></i>
                            </span>
                            <span><?php echo e($statusMeta['label']); ?></span>
                          </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                          <div class="inline-flex items-center gap-2">
                            <button
                              type="button"
                              data-edit-toggle="promo-edit-row-<?php echo e((string) $promoId); ?>"
                              class="teacher-promotions-action-btn teacher-promotions-action-btn--edit"
                              aria-label="Modifier cette promotion"
                              title="Modifier"
                            >
                              <i class="fas fa-pen"></i>
                            </button>
                            <form
                              method="post"
                              action="<?php echo e($currentActionUrl); ?>"
                              class="inline-flex"
                              data-delete-confirm
                              data-delete-action="Archiver"
                              data-delete-label="cette promotion"
                              data-delete-title="Archiver cette promotion ?"
                              data-delete-message="<?php echo e(($reference !== '' ? $reference : ('Promotion #' . $promoId)) . ($courseTitle !== '' ? (' sur ' . $courseTitle) : '') . ' sera archivée et retirée des offres actives.'); ?>"
                              data-delete-confirm-label="Archiver"
                            >
                              <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">
                              <input type="hidden" name="action" value="delete_promo">
                              <input type="hidden" name="promo_mode" value="promotion">
                              <input type="hidden" name="offer" value="promotion">
                              <input type="hidden" name="promo_id" value="<?php echo e((string) $promoId); ?>">
                              <button type="submit" class="teacher-promotions-action-btn teacher-promotions-action-btn--delete" aria-label="Archiver cette promotion" title="Archiver">
                                <i class="fas fa-archive"></i>
                              </button>
                            </form>
                          </div>
                        </td>
                      </tr>
                      <tr id="promo-edit-row-<?php echo e((string) $promoId); ?>" class="hidden" data-edit-modal="1"<?php echo $promoEditInputs !== [] ? ' data-edit-open-on-load="1"' : ''; ?>>
                        <td colspan="7" class="h-0 border-0 p-0">
                          <div class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm" data-edit-backdrop>
                            <form method="post" action="<?php echo e($currentActionUrl); ?>" class="flex max-h-[calc(100vh-2rem)] w-full max-w-5xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900" data-promo-form data-promo-edit-form data-edit-panel>
                              <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">
                              <input type="hidden" name="action" value="update_promo">
                              <input type="hidden" name="promo_mode" value="promotion">
                              <input type="hidden" name="offer" value="promotion">
                              <input type="hidden" name="promo_id" value="<?php echo e((string) $promoId); ?>">

                              <div class="border-b border-slate-200 px-6 py-5 dark:border-slate-800">
                                <div class="flex items-start justify-between gap-4">
                                  <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-600 dark:text-sky-300">Édition</p>
                                    <h3 class="mt-2 text-xl font-bold text-slate-950 dark:text-white">Modifier la promotion</h3>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?php echo e($reference !== '' ? $reference : ('Promotion #' . $promoId)); ?> sur <?php echo e($courseTitle !== '' ? $courseTitle : 'cours sans titre'); ?></p>
                                  </div>
                                  <button type="button" data-edit-toggle="promo-edit-row-<?php echo e((string) $promoId); ?>" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-500 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Fermer">
                                    <i class="fas fa-times"></i>
                                  </button>
                                </div>
                                <div class="mt-4 grid gap-3 md:grid-cols-3">
                                  <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/70">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Cours</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-white"><?php echo e($courseTitle !== '' ? $courseTitle : 'Cours sans titre'); ?></p>
                                    <p class="mt-1 app-price text-xs"><?php echo e($formatMoney($coursePrice)); ?></p>
                                  </div>
                                  <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/70">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Réduction actuelle</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-white"><?php echo e($discountMeta['main']); ?></p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400"><?php echo e($discountMeta['sub']); ?></p>
                                  </div>
                                  <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/70">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Statut</p>
                                    <div class="mt-2">
                                      <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold <?php echo e($statusMeta['class']); ?>">
                                        <span class="flex h-5 w-5 items-center justify-center rounded-full <?php echo e((string) ($statusMeta['iconBg'] ?? 'bg-gray-100 dark:bg-gray-700')); ?>">
                                          <i class="fas <?php echo e((string) ($statusMeta['icon'] ?? 'fa-circle')); ?> text-[10px] <?php echo e((string) ($statusMeta['iconClass'] ?? 'text-gray-500 dark:text-gray-300')); ?>"></i>
                                        </span>
                                        <span><?php echo e($statusMeta['label']); ?></span>
                                      </span>
                                    </div>
                                  </div>
                                </div>
                              </div>

                              <div class="flex-1 overflow-y-auto px-6 py-6">
                                <div class="space-y-5">
                              <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                  <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Type de réduction <span class="text-red-500">*</span></label>
                                  <select name="promo_type" data-promo-type class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                    <option value="percent" <?php echo ($promoFormValues['promo_type'] ?? 'percent') === 'percent' ? 'selected' : ''; ?>>Pourcentage (%)</option>
                                    <option value="amount" <?php echo ($promoFormValues['promo_type'] ?? '') === 'amount' ? 'selected' : ''; ?>>Montant fixe (nouveau prix)</option>
                                  </select>
                                </div>
                                <div data-promo-amount class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                                  <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Nouveau prix <span class="text-red-500">*</span></label>
                                  <input type="number" name="promo_amount" min="1" step="1" value="<?php echo e((string) ($promoFormValues['promo_amount'] ?? '')); ?>" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                </div>
                                <div data-promo-percent class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                                  <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Réduction (%) <span class="text-red-500">*</span></label>
                                  <input type="number" name="promo_percent" min="1" max="100" step="0.01" value="<?php echo e((string) ($promoFormValues['promo_percent'] ?? '')); ?>" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                </div>
                                <div class="space-y-4 md:col-span-2">
                                  <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Mode de période <span class="text-red-500">*</span></label>
                                    <select name="promo_schedule_mode" data-promo-schedule-mode class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                      <option value="duration" <?php echo $promoEditScheduleMode === 'duration' ? 'selected' : ''; ?>>Durée en jours</option>
                                      <option value="custom" <?php echo $promoEditScheduleMode === 'custom' ? 'selected' : ''; ?>>Dates exactes</option>
                                    </select>
                                  </div>
                                  <div data-promo-schedule-duration class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40 <?php echo $promoEditScheduleMode === 'custom' ? 'hidden' : ''; ?>">
                                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Durée en jours <span class="text-red-500">*</span></label>
                                    <input type="number" name="promo_duration_days" min="1" step="1" value="<?php echo e((string) ($promoFormValues['promo_duration_days'] ?? '1')); ?>" <?php echo $promoEditScheduleMode === 'custom' ? 'disabled' : 'required'; ?> class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                  </div>
                                  <div data-promo-schedule-custom class="grid gap-4 md:grid-cols-2 <?php echo $promoEditScheduleMode === 'custom' ? '' : 'hidden'; ?>">
                                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                                      <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Date de début <span class="text-red-500">*</span></label>
                                      <input type="datetime-local" name="promo_start_at" step="60" value="<?php echo e((string) ($promoFormValues['promo_start_at'] ?? '')); ?>" <?php echo $promoEditScheduleMode === 'custom' ? 'required' : 'disabled'; ?> class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                    </div>
                                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                                      <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Date de fin <span class="text-red-500">*</span></label>
                                      <input type="datetime-local" name="promo_end_at" step="60" value="<?php echo e((string) ($promoFormValues['promo_end_at'] ?? '')); ?>" <?php echo $promoEditScheduleMode === 'custom' ? 'required' : 'disabled'; ?> class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                    </div>
                                  </div>
                                </div>
                              </div>

                              <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Ciblage</label>
                                <select name="promo_target_type" data-promo-target-type class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                  <option value="public" <?php echo $promoEditTargetType === 'public' ? 'selected' : ''; ?>>Tout le monde</option>
                                  <?php if ($canTargetPromos): ?>
                                    <option value="individual" <?php echo $promoEditTargetType === 'individual' ? 'selected' : ''; ?>>Étudiants spécifiques</option>
                                    <option value="company" <?php echo $promoEditTargetType === 'company' ? 'selected' : ''; ?>>Entreprise</option>
                                  <?php endif; ?>
                                </select>
                              </div>

                              <div data-promo-target-scope="public" class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Cible publique</p>
                              </div>

                              <div data-promo-target-scope="individual" class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Emails ciblés</label>
                                <textarea name="promo_targets" rows="3" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" placeholder="etudiant1@email.com, etudiant2@email.com"><?php echo e((string) ($promoFormValues['promo_targets'] ?? '')); ?></textarea>
                              </div>

                              <div data-promo-target-scope="company" class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                                <div class="grid gap-4 md:grid-cols-2">
                                  <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Entreprise cible</label>
                                    <select name="promo_company_id" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                      <option value="">Sélectionnez une entreprise</option>
                                      <?php foreach ($promoCompanyCandidates as $company): ?>
                                        <?php $companyId = (int) ($company['id'] ?? 0); ?>
                                        <option value="<?php echo e((string) $companyId); ?>" <?php echo (string) ($promoFormValues['promo_company_id'] ?? '') === (string) $companyId ? 'selected' : ''; ?>>
                                          <?php
                                            $companyName = trim((string) ($company['name'] ?? ''));
                                            $memberCount = (int) ($company['member_count'] ?? 0);
                                          ?>
                                          <?php echo e($companyName !== '' ? ($companyName . ' · ' . $memberCount . ' membres') : ('Entreprise #' . $companyId)); ?>
                                        </option>
                                      <?php endforeach; ?>
                                    </select>
                                  </div>
                                  <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Places réservées</label>
                                    <input type="number" name="promo_company_seats" min="1" step="1" value="<?php echo e((string) ($promoFormValues['promo_company_seats'] ?? '')); ?>" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                  </div>
                                </div>
                              </div>

                                </div>
                              </div>

                              <div class="border-t border-slate-200 bg-white px-6 py-4 dark:border-slate-800 dark:bg-slate-900">
                                <div class="flex items-center justify-end gap-3">
                                <button type="button" data-edit-toggle="promo-edit-row-<?php echo e((string) $promoId); ?>" class="rounded-xl border border-gray-300 px-4 py-2.5 font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">Annuler</button>
                                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-5 py-2.5 font-medium text-white shadow-md transition hover:bg-sky-700">
                                  <i class="fas fa-save"></i> Mettre à jour
                                </button>
                                </div>
                              </div>
                            </form>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="7" class="px-6 py-12 text-center">
                        <div class="mx-auto flex max-w-md flex-col items-center">
                          <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-50 text-sky-600 dark:bg-sky-900/30 dark:text-sky-200">
                            <i class="fas fa-percent"></i>
                          </span>
                          <p class="mt-4 text-sm font-semibold text-slate-900 dark:text-white">Aucune promotion enregistrée.</p>
                          <?php if ($courses === []): ?>
                            <button type="button" disabled class="mt-4 inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition cursor-not-allowed opacity-50 hover:bg-primary-600">
                              <i class="fas fa-plus-circle"></i>
                              <span>Créer une promotion</span>
                            </button>
                          <?php else: ?>
                            <a href="<?php echo e(site_url('teacher/promotions' . $buildQuery(['form' => 'promotion', 'step' => 1]))); ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700">
                              <i class="fas fa-plus-circle"></i>
                              <span>Créer une promotion</span>
                            </a>
                          <?php endif; ?>
                        </div>
                      </td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div id="content-coupons" class="space-y-5 <?php echo $couponsTabActive ? '' : 'hidden'; ?>">
          <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card dark:border-slate-700 dark:bg-slate-800">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
              <div class="flex items-center justify-between gap-4">
                <div>
                  <h2 class="text-base font-bold text-gray-900 dark:text-white">Codes promo privés</h2>
                </div>
                <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                  <?php echo e((string) $couponActiveCount); ?> actifs
                </span>
              </div>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="border-b border-gray-200 text-gray-600 dark:border-gray-700 dark:text-gray-400">
                  <tr>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Code promo</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Cours</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Réduction</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Public cible</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Expiration</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Utilisations</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Statut</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold">Actions</th>
                  </tr>
                </thead>
                <tbody id="coupons-table-body" class="divide-y divide-gray-100 dark:divide-gray-700">
                  <?php if ($couponRows !== []): ?>
                    <?php foreach ($couponRows as $row): ?>
                      <?php
                        $promoId = (int) ($row['id'] ?? 0);
                        $courseTitle = trim((string) ($row['title'] ?? ''));
                        $promoType = strtolower(trim((string) ($row['promo_type'] ?? '')));
                        $promoCode = trim((string) ($row['coupon_code'] ?? ''));
                        $statusMeta = $promoStatusMeta($row);
                        $discountMeta = $promoDiscountLabel($row);
                        $targetMeta = $promoTargetMeta($row);
                        $endAt = $formatDateTime((string) ($row['promo_end_at'] ?? ''));
                        $performanceRow = is_array($promotionPerfById[$promoId] ?? null) ? $promotionPerfById[$promoId] : [];
                        $usesCount = (int) ($performanceRow['uses_count'] ?? 0);
                        $conversionRate = isset($performanceRow['conversion_rate']) && $performanceRow['conversion_rate'] !== null ? (float) $performanceRow['conversion_rate'] : null;
                        $lastUsedAt = $formatDateTime((string) ($performanceRow['last_used_at'] ?? ''));
                        $couponEditInputs = is_array($promoInputs[$promoId] ?? null) ? $promoInputs[$promoId] : [];
                        $couponFormValues = array_merge($buildEditDefaults($row), $couponEditInputs);
                        $couponEditScheduleMode = $normalizeScheduleMode((string) ($couponFormValues['promo_schedule_mode'] ?? 'custom'), 'custom');
                        $couponEditTargetType = (string) ($couponFormValues['promo_target_type'] ?? 'public');
                        $couponEditCouponModeRaw = strtolower(trim((string) ($couponFormValues['coupon_code_mode'] ?? 'manual')));
                        $couponEditCouponMode = in_array($couponEditCouponModeRaw, ['auto', 'manual'], true) ? $couponEditCouponModeRaw : 'manual';
                        $couponTimelineState = $promoLifecycleState($row);
                        $couponTimelineHint = $promoTimeHint($row);
                        $couponCopyText = $promoCode !== '' ? $promoCode : ('Code promo #' . $promoId);
                      ?>
                      <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/25">
                        <td class="px-6 py-4">
                          <div class="font-mono font-semibold text-gray-900 dark:text-white"><?php echo e($promoCode !== '' ? $promoCode : ('Code promo #' . $promoId)); ?></div>
                          <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo e($promoType !== '' ? ucfirst($promoType) : 'Code promo'); ?></div>
                        </td>
                        <td class="px-6 py-4">
                          <div class="font-medium text-gray-900 dark:text-white"><?php echo e($courseTitle !== '' ? $courseTitle : 'Cours sans titre'); ?></div>
                          <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo e($courseTitle !== '' ? 'Offre liée au cours' : 'Cours supprimé'); ?></div>
                        </td>
                        <td class="px-6 py-4">
                          <div class="font-semibold text-gray-900 dark:text-white"><?php echo e($discountMeta['main']); ?></div>
                          <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo e($discountMeta['sub']); ?></div>
                        </td>
                        <td class="px-6 py-4">
                          <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold <?php echo e($targetMeta['class']); ?>">
                            <?php echo e($targetMeta['label']); ?>
                          </span>
                          <div class="mt-1 text-xs text-gray-500 dark:text-gray-400"><?php echo e($targetMeta['detail']); ?></div>
                        </td>
                        <td class="px-6 py-4">
                          <div class="text-xs font-medium text-gray-900 dark:text-white"><?php echo e($endAt); ?></div>
                          <div class="text-xs text-gray-500 dark:text-gray-400">Fin de validité</div>
                          <div class="text-[11px] font-medium <?php echo e($couponTimelineState === 'scheduled' ? 'text-amber-600 dark:text-amber-300' : ($couponTimelineState === 'ended' ? 'text-slate-500 dark:text-slate-400' : 'text-violet-600 dark:text-violet-300')); ?>"><?php echo e($couponTimelineHint); ?></div>
                        </td>
                        <td class="px-6 py-4">
                          <div class="font-semibold text-gray-900 dark:text-white"><?php echo e((string) $usesCount); ?></div>
                          <div class="text-xs text-gray-500 dark:text-gray-400">
                            <?php echo $conversionRate !== null ? e(number_format($conversionRate, 2, ',', ' ') . '% diffusion') : 'Sans données'; ?>
                            <?php if ($lastUsedAt !== '—'): ?>
                              <br>
                              Dernier usage : <?php echo e($lastUsedAt); ?>
                            <?php endif; ?>
                          </div>
                        </td>
                        <td class="px-6 py-4">
                          <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold <?php echo e($statusMeta['class']); ?>">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full <?php echo e((string) ($statusMeta['iconBg'] ?? 'bg-gray-100 dark:bg-gray-700')); ?>">
                              <i class="fas <?php echo e((string) ($statusMeta['icon'] ?? 'fa-circle')); ?> text-[10px] <?php echo e((string) ($statusMeta['iconClass'] ?? 'text-gray-500 dark:text-gray-300')); ?>"></i>
                            </span>
                            <span><?php echo e($statusMeta['label']); ?></span>
                          </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                          <div class="inline-flex items-center gap-2">
                            <button
                              type="button"
                              class="teacher-promotions-action-btn teacher-promotions-action-btn--copy"
                              data-copy-text="<?php echo e($couponCopyText); ?>"
                              data-copy-success="Code copié"
                              aria-label="Copier le code"
                              title="Copier"
                            >
                              <i class="fas fa-copy"></i>
                            </button>
                            <button
                              type="button"
                              data-edit-toggle="coupon-edit-row-<?php echo e((string) $promoId); ?>"
                              class="teacher-promotions-action-btn teacher-promotions-action-btn--edit"
                              aria-label="Modifier ce code promo"
                              title="Modifier"
                            >
                              <i class="fas fa-pen"></i>
                            </button>
                            <form
                              method="post"
                              action="<?php echo e($currentActionUrl); ?>"
                              class="inline-flex"
                              data-delete-confirm
                              data-delete-action="Archiver"
                              data-delete-label="ce code promo"
                              data-delete-title="Archiver ce code promo ?"
                              data-delete-message="<?php echo e(($promoCode !== '' ? $promoCode : ('Code promo #' . $promoId)) . ($courseTitle !== '' ? (' sur ' . $courseTitle) : '') . ' sera archivé et retiré des offres actives.'); ?>"
                              data-delete-confirm-label="Archiver"
                            >
                              <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">
                              <input type="hidden" name="action" value="delete_promo">
                              <input type="hidden" name="promo_mode" value="coupon">
                              <input type="hidden" name="offer" value="coupon">
                              <input type="hidden" name="promo_id" value="<?php echo e((string) $promoId); ?>">
                              <button type="submit" class="teacher-promotions-action-btn teacher-promotions-action-btn--delete" aria-label="Archiver ce code promo" title="Archiver">
                                <i class="fas fa-archive"></i>
                              </button>
                            </form>
                          </div>
                        </td>
                      </tr>
                      <tr id="coupon-edit-row-<?php echo e((string) $promoId); ?>" class="hidden" data-edit-modal="1"<?php echo $couponEditInputs !== [] ? ' data-edit-open-on-load="1"' : ''; ?>>
                        <td colspan="8" class="h-0 border-0 p-0">
                          <div class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm" data-edit-backdrop>
                            <form method="post" action="<?php echo e($currentActionUrl); ?>" class="flex max-h-[calc(100vh-2rem)] w-full max-w-5xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900" data-promo-form data-promo-edit-form data-edit-panel>
                              <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">
                              <input type="hidden" name="action" value="update_promo">
                              <input type="hidden" name="promo_mode" value="coupon">
                              <input type="hidden" name="offer" value="coupon">
                              <input type="hidden" name="promo_id" value="<?php echo e((string) $promoId); ?>">

                              <div class="border-b border-slate-200 px-6 py-5 dark:border-slate-800">
                                <div class="flex items-start justify-between gap-4">
                                  <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-violet-600 dark:text-violet-300">Édition</p>
                                    <h3 class="mt-2 text-xl font-bold text-slate-950 dark:text-white">Modifier le code promo</h3>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?php echo e($promoCode !== '' ? $promoCode : ('Code promo #' . $promoId)); ?> sur <?php echo e($courseTitle !== '' ? $courseTitle : 'cours sans titre'); ?></p>
                                  </div>
                                  <button type="button" data-edit-toggle="coupon-edit-row-<?php echo e((string) $promoId); ?>" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-500 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Fermer">
                                    <i class="fas fa-times"></i>
                                  </button>
                                </div>
                                <div class="mt-4 grid gap-3 md:grid-cols-4">
                                  <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/70">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Code</p>
                                    <p class="mt-2 font-mono text-sm font-semibold text-slate-900 dark:text-white"><?php echo e($promoCode !== '' ? $promoCode : ('Code promo #' . $promoId)); ?></p>
                                  </div>
                                  <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/70">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Cours</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-white"><?php echo e($courseTitle !== '' ? $courseTitle : 'Cours sans titre'); ?></p>
                                  </div>
                                  <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/70">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Utilisations</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-white"><?php echo e((string) $usesCount); ?></p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400"><?php echo $conversionRate !== null ? e(number_format($conversionRate, 2, ',', ' ') . '% diffusion') : 'Sans données'; ?></p>
                                  </div>
                                  <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/70">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Statut</p>
                                    <div class="mt-2">
                                      <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold <?php echo e($statusMeta['class']); ?>">
                                        <span class="flex h-5 w-5 items-center justify-center rounded-full <?php echo e((string) ($statusMeta['iconBg'] ?? 'bg-gray-100 dark:bg-gray-700')); ?>">
                                          <i class="fas <?php echo e((string) ($statusMeta['icon'] ?? 'fa-circle')); ?> text-[10px] <?php echo e((string) ($statusMeta['iconClass'] ?? 'text-gray-500 dark:text-gray-300')); ?>"></i>
                                        </span>
                                        <span><?php echo e($statusMeta['label']); ?></span>
                                      </span>
                                    </div>
                                  </div>
                                </div>
                              </div>

                              <div class="flex-1 overflow-y-auto px-6 py-6">
                                <div class="space-y-5">
                              <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                  <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Type de réduction <span class="text-red-500">*</span></label>
                                  <select name="promo_type" data-promo-type class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                    <option value="percent" <?php echo ($couponFormValues['promo_type'] ?? 'percent') === 'percent' ? 'selected' : ''; ?>>Pourcentage (%)</option>
                                    <option value="amount" <?php echo ($couponFormValues['promo_type'] ?? '') === 'amount' ? 'selected' : ''; ?>>Montant fixe (nouveau prix)</option>
                                  </select>
                                </div>
                                <div data-promo-amount class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                                  <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Nouveau prix <span class="text-red-500">*</span></label>
                                  <input type="number" name="promo_amount" min="1" step="1" value="<?php echo e((string) ($couponFormValues['promo_amount'] ?? '')); ?>" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                </div>
                                <div data-promo-percent class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                                  <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Réduction (%) <span class="text-red-500">*</span></label>
                                  <input type="number" name="promo_percent" min="1" max="100" step="0.01" value="<?php echo e((string) ($couponFormValues['promo_percent'] ?? '')); ?>" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                </div>
                                <div class="space-y-4 md:col-span-2">
                                  <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Mode de période <span class="text-red-500">*</span></label>
                                    <select name="promo_schedule_mode" data-promo-schedule-mode class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                      <option value="duration" <?php echo $couponEditScheduleMode === 'duration' ? 'selected' : ''; ?>>Durée en jours</option>
                                      <option value="custom" <?php echo $couponEditScheduleMode === 'custom' ? 'selected' : ''; ?>>Dates exactes</option>
                                    </select>
                                  </div>
                                  <div data-promo-schedule-duration class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40 <?php echo $couponEditScheduleMode === 'custom' ? 'hidden' : ''; ?>">
                                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Durée en jours <span class="text-red-500">*</span></label>
                                    <input type="number" name="promo_duration_days" min="1" step="1" value="<?php echo e((string) ($couponFormValues['promo_duration_days'] ?? '1')); ?>" <?php echo $couponEditScheduleMode === 'custom' ? 'disabled' : 'required'; ?> class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                  </div>
                                  <div data-promo-schedule-custom class="grid gap-4 md:grid-cols-2 <?php echo $couponEditScheduleMode === 'custom' ? '' : 'hidden'; ?>">
                                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                                      <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Date de début <span class="text-red-500">*</span></label>
                                      <input type="datetime-local" name="promo_start_at" step="60" value="<?php echo e((string) ($couponFormValues['promo_start_at'] ?? '')); ?>" <?php echo $couponEditScheduleMode === 'custom' ? 'required' : 'disabled'; ?> class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                    </div>
                                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                                      <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Date de fin <span class="text-red-500">*</span></label>
                                      <input type="datetime-local" name="promo_end_at" step="60" value="<?php echo e((string) ($couponFormValues['promo_end_at'] ?? '')); ?>" <?php echo $couponEditScheduleMode === 'custom' ? 'required' : 'disabled'; ?> class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                    </div>
                                  </div>
                                </div>
                              </div>

                              <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                                <div class="space-y-4">
                                  <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Code promo</label>
                                    <select name="coupon_code_mode" data-coupon-code-mode class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                      <option value="auto" <?php echo $couponEditCouponMode === 'auto' ? 'selected' : ''; ?>>Générer automatiquement</option>
                                      <option value="manual" <?php echo $couponEditCouponMode === 'manual' ? 'selected' : ''; ?>>Saisir un code personnalisé</option>
                                    </select>
                                  </div>
                                  <div data-coupon-code-wrap class="rounded-xl border border-dashed border-gray-300 bg-white p-4 dark:border-gray-600 dark:bg-gray-800 <?php echo $couponEditCouponMode === 'manual' ? '' : 'hidden'; ?>">
                                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Votre code promo</label>
                                    <input
                                      type="text"
                                      name="coupon_code"
                                      data-coupon-code-input
                                      value="<?php echo e((string) ($couponFormValues['coupon_code'] ?? '')); ?>"
                                      <?php echo $couponEditCouponMode === 'manual' ? 'required' : 'disabled'; ?>
                                      maxlength="32"
                                      pattern="[A-Za-z0-9-]{3,32}"
                                      placeholder="PROMO-2026"
                                      autocomplete="off"
                                      spellcheck="false"
                                      class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 uppercase tracking-wide outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    >
                                  </div>
                                </div>
                              </div>

                              <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Ciblage</label>
                                <select name="promo_target_type" data-promo-target-type class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                  <option value="public" <?php echo $couponEditTargetType === 'public' ? 'selected' : ''; ?>>Tout le monde</option>
                                  <?php if ($canTargetPromos): ?>
                                    <option value="individual" <?php echo $couponEditTargetType === 'individual' ? 'selected' : ''; ?>>Étudiants spécifiques</option>
                                    <option value="company" <?php echo $couponEditTargetType === 'company' ? 'selected' : ''; ?>>Entreprise</option>
                                  <?php endif; ?>
                                </select>
                              </div>

                              <div data-promo-target-scope="public" class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Cible publique</p>
                              </div>

                              <div data-promo-target-scope="individual" class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Emails ciblés</label>
                                <textarea name="promo_targets" rows="3" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" placeholder="etudiant1@email.com, etudiant2@email.com"><?php echo e((string) ($couponFormValues['promo_targets'] ?? '')); ?></textarea>
                              </div>

                              <div data-promo-target-scope="company" class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                                <div class="grid gap-4 md:grid-cols-2">
                                  <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Entreprise cible</label>
                                    <select name="promo_company_id" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                      <option value="">Sélectionnez une entreprise</option>
                                      <?php foreach ($promoCompanyCandidates as $company): ?>
                                        <?php $companyId = (int) ($company['id'] ?? 0); ?>
                                        <option value="<?php echo e((string) $companyId); ?>" <?php echo (string) ($couponFormValues['promo_company_id'] ?? '') === (string) $companyId ? 'selected' : ''; ?>>
                                          <?php
                                            $companyName = trim((string) ($company['name'] ?? ''));
                                            $memberCount = (int) ($company['member_count'] ?? 0);
                                          ?>
                                          <?php echo e($companyName !== '' ? ($companyName . ' · ' . $memberCount . ' membres') : ('Entreprise #' . $companyId)); ?>
                                        </option>
                                      <?php endforeach; ?>
                                    </select>
                                  </div>
                                  <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Places réservées</label>
                                    <input type="number" name="promo_company_seats" min="1" step="1" value="<?php echo e((string) ($couponFormValues['promo_company_seats'] ?? '')); ?>" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                  </div>
                                </div>
                              </div>

                                </div>
                              </div>

                              <div class="border-t border-slate-200 bg-white px-6 py-4 dark:border-slate-800 dark:bg-slate-900">
                                <div class="flex items-center justify-end gap-3">
                                <button type="button" data-edit-toggle="coupon-edit-row-<?php echo e((string) $promoId); ?>" class="rounded-xl border border-gray-300 px-4 py-2.5 font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">Annuler</button>
                                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-violet-600 px-5 py-2.5 font-medium text-white shadow-md transition hover:bg-violet-700">
                                  <i class="fas fa-save"></i> Mettre à jour
                                </button>
                                </div>
                              </div>
                            </form>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="8" class="px-6 py-12 text-center">
                        <div class="mx-auto flex max-w-md flex-col items-center">
                          <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-50 text-violet-600 dark:bg-violet-900/30 dark:text-violet-200">
                            <i class="fas fa-ticket-alt"></i>
                          </span>
                          <p class="mt-4 text-sm font-semibold text-slate-900 dark:text-white">Aucun code promo enregistré.</p>
                          <?php if ($courses === [] || !$couponCodeSupported): ?>
                            <button type="button" disabled class="mt-4 inline-flex items-center gap-2 rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition cursor-not-allowed opacity-50 hover:bg-violet-600">
                              <i class="fas fa-ticket-alt"></i>
                              <span>Créer un code promo</span>
                            </button>
                          <?php else: ?>
                            <a href="<?php echo e(site_url('teacher/promotions' . $buildQuery(['form' => 'coupon', 'step' => 1]))); ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700">
                              <i class="fas fa-ticket-alt"></i>
                              <span>Créer un code promo</span>
                            </a>
                          <?php endif; ?>
                        </div>
                      </td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <section class="grid gap-6 xl:grid-cols-2">
        <article class="rounded-2xl border border-sky-100 bg-sky-50/70 p-5 shadow-soft dark:border-sky-900/30 dark:bg-sky-950/20">
          <div class="flex items-center justify-between gap-4 border-b border-sky-100 pb-4 dark:border-sky-900/30">
            <div>
              <h3 class="text-base font-bold text-slate-950 dark:text-white">Comparaison des ventes</h3>
            </div>
          </div>
          <div class="mt-4 grid gap-4 md:grid-cols-2">
            <?php foreach (['coupon' => 'Codes promo', 'promo' => 'Promotions'] as $comparisonKey => $comparisonLabel): ?>
              <?php $comparisonRow = is_array($promoComparison[$comparisonKey] ?? null) ? $promoComparison[$comparisonKey] : []; ?>
              <?php $comparisonRevenue = (int) ($comparisonRow['revenue_xof'] ?? 0); ?>
              <?php $comparisonShare = $comparisonKey === 'coupon' ? $comparisonCouponShare : $comparisonPromotionShare; ?>
              <div class="rounded-2xl border border-white/70 bg-white/85 p-4 shadow-sm dark:border-sky-900/30 dark:bg-slate-900/60">
                <p class="text-sm font-semibold text-slate-600 dark:text-slate-300"><?php echo e($comparisonLabel); ?></p>
                <p class="mt-2 text-2xl font-extrabold text-slate-950 dark:text-white"><?php echo e($formatMoney($comparisonRevenue)); ?></p>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                  <div class="h-full rounded-full <?php echo e($comparisonKey === 'coupon' ? 'bg-violet-500' : 'bg-sky-500'); ?>" style="width: <?php echo e((string) max(0, min(100, $comparisonShare))); ?>%;"></div>
                </div>
                <div class="mt-2 flex items-center justify-between text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                  <span>Part du revenu</span>
                  <span><?php echo e((string) max(0, min(100, $comparisonShare))); ?>%</span>
                </div>
                <div class="mt-2 grid grid-cols-3 gap-2 text-xs text-slate-500 dark:text-slate-400">
                  <div>
                    <p class="font-semibold text-slate-700 dark:text-slate-200"><?php echo e((string) ((int) ($comparisonRow['inscriptions'] ?? 0))); ?></p>
                    <p>inscriptions</p>
                  </div>
                  <div>
                    <p class="font-semibold text-slate-700 dark:text-slate-200"><?php echo e((string) ((int) ($comparisonRow['orders_count'] ?? 0))); ?></p>
                    <p>commandes</p>
                  </div>
                  <div>
                    <p class="font-semibold text-slate-700 dark:text-slate-200"><?php echo e($formatMoney((int) ($comparisonRow['avg_basket_xof'] ?? 0))); ?></p>
                    <p>panier moyen</p>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </article>

        <article class="rounded-2xl border border-amber-100 bg-amber-50/70 p-5 shadow-soft dark:border-amber-900/30 dark:bg-amber-950/20">
          <div class="flex items-center justify-between gap-4 border-b border-amber-100 pb-4 dark:border-amber-900/30">
            <div>
              <h3 class="text-base font-bold text-slate-950 dark:text-white"><?php echo e((string) ($expiringOfferMeta['title'] ?? 'Offres expirant bientôt')); ?></h3>
            </div>
            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-300"><?php echo e((string) count($expiringOfferRows)); ?></span>
          </div>
          <div class="mt-4 space-y-3">
            <?php if ($expiringOfferRows !== []): ?>
              <?php foreach (array_slice($expiringOfferRows, 0, 6) as $row): ?>
                <?php
                  $offerMode = strtolower(trim((string) ($row['promo_mode'] ?? 'promotion')));
                  $isCouponOffer = $offerMode === 'coupon';
                  $code = trim((string) ($row['coupon_code'] ?? ''));
                  $courseTitle = trim((string) ($row['course_title'] ?? ''));
                  $endAt = $formatDateTime((string) ($row['promo_end_at'] ?? ''));
                  $offerId = (int) ($row['id'] ?? 0);
                  $offerLabel = $isCouponOffer
                    ? ($code !== '' ? $code : 'Code promo')
                    : ('Promotion #' . ($offerId > 0 ? $offerId : '—'));
                $offerBadgeLabel = $isCouponOffer ? 'Code promo' : 'Promotion';
                $offerBadgeClass = $isCouponOffer
                    ? 'bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-200'
                    : 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200';
                $offerHint = $promoTimeHint($row);
                $offerHintClass = $isCouponOffer
                    ? 'text-violet-600 dark:text-violet-300'
                    : 'text-sky-600 dark:text-sky-300';
                ?>
                <div class="flex items-center justify-between gap-4 rounded-xl border border-white/70 bg-white/85 px-4 py-3 shadow-sm dark:border-amber-900/30 dark:bg-slate-900/60">
                  <div>
                    <div class="flex flex-wrap items-center gap-2">
                      <p class="<?php echo e($isCouponOffer ? 'font-mono ' : ''); ?>font-semibold text-slate-950 dark:text-white"><?php echo e($offerLabel); ?></p>
                      <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold <?php echo e($offerBadgeClass); ?>"><?php echo e($offerBadgeLabel); ?></span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400"><?php echo e($courseTitle !== '' ? $courseTitle : 'Cours sans titre'); ?></p>
                    <p class="mt-1 text-[11px] font-medium <?php echo e($offerHintClass); ?>"><?php echo e($offerHint); ?></p>
                  </div>
                  <div class="text-right">
                    <p class="text-sm font-semibold text-slate-950 dark:text-white"><?php echo e($endAt); ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Expiration</p>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="rounded-xl border border-dashed border-amber-200 px-4 py-8 text-center dark:border-amber-900/40">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-200">
                  <i class="fas fa-hourglass-half"></i>
                </span>
                <p class="mt-3 text-sm font-semibold text-slate-800 dark:text-slate-100">Aucune expiration proche.</p>
              </div>
            <?php endif; ?>
          </div>
        </article>
      </section>

      </div>
    </main>
  </div>

<div id="delete-confirm-modal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/55 backdrop-blur-sm p-4 opacity-0 transition duration-200" aria-hidden="true">
  <div data-modal-panel class="w-full max-w-lg rounded-2xl bg-white p-5 shadow-2xl transition duration-200 opacity-0 translate-y-3 scale-95 dark:bg-gray-800">
    <div class="flex items-start justify-between gap-4">
      <div class="flex items-start gap-3">
        <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 dark:bg-rose-900/25 dark:text-rose-300">
          <i class="fas fa-archive"></i>
        </span>
        <div>
          <h3 data-delete-modal-title class="text-lg font-bold text-gray-900 dark:text-white">Archiver cette offre ?</h3>
        </div>
      </div>
      <button type="button" class="close-modal inline-flex h-9 w-9 items-center justify-center rounded-xl border border-gray-200 text-gray-500 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700" aria-label="Fermer">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <div class="mt-5 rounded-2xl border border-rose-100 bg-rose-50/80 p-4 text-sm text-rose-900 dark:border-rose-900/35 dark:bg-rose-900/15 dark:text-rose-100">
      <p data-delete-modal-message class="leading-6">Confirmez l'archivage de cette offre.</p>
    </div>

    <div class="mt-6 flex flex-wrap items-center justify-end gap-3">
      <button type="button" class="close-modal rounded-xl border border-gray-300 px-4 py-2.5 font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">Annuler</button>
      <button type="button" data-delete-modal-confirm class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-5 py-2.5 font-semibold text-white shadow-md shadow-rose-600/20 transition hover:bg-rose-700">
        <i class="fas fa-archive"></i>
        <span data-delete-modal-confirm-label>Archiver</span>
      </button>
    </div>
  </div>
</div>

</body>
</html>
