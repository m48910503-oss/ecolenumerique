<?php
declare(strict_types=1);

$teacherUser = is_array($user ?? null) ? $user : [];
$teacherDisplayName = function_exists('dashboard_user_display_name')
    ? dashboard_user_display_name($teacherUser, 'Enseignant')
    : 'Enseignant';
$teacherEmail = trim((string) ($teacherUser['email'] ?? ''));
$teacherAvatarUrl = function_exists('dashboard_user_avatar_url') ? dashboard_user_avatar_url($teacherUser) : '';
$teacherInitials = function_exists('dashboard_user_initials') ? dashboard_user_initials($teacherUser, 'EN') : 'EN';
$teacherFirstName = trim((string) ($teacherUser['first_name'] ?? ''));
if ($teacherFirstName === '') {
    $displayNameParts = preg_split('/\s+/', trim($teacherDisplayName)) ?: [];
    $teacherFirstName = trim((string) ($displayNameParts[0] ?? ''));
}
if ($teacherFirstName === '' && $teacherEmail !== '') {
    $emailPrefix = trim((string) explode('@', $teacherEmail, 2)[0]);
    $emailPrefix = str_replace(['.', '_', '-'], ' ', $emailPrefix);
    $emailParts = preg_split('/\s+/', $emailPrefix) ?: [];
    $teacherFirstName = trim((string) ($emailParts[0] ?? ''));
}
$teacherFirstName = $teacherFirstName !== '' ? $teacherFirstName : 'Enseignant';

$siteUrl = static function (string $path): string {
    return site_url($path);
};

$errors = array_values(is_array($errors ?? null) ? $errors : []);
$success = trim((string) ($success ?? ''));
$warning = trim((string) ($warning ?? ''));
$toastItems = [];
if ($success !== '') {
    $toastItems[] = ['type' => 'success', 'message' => $success];
} elseif ($errors !== []) {
    foreach ($errors as $error) {
        $toastItems[] = ['type' => 'error', 'message' => (string) $error];
    }
} elseif ($warning !== '') {
    $toastItems[] = ['type' => 'warning', 'message' => $warning];
}
$toastPayload = [
    'user_id' => (int) ($teacherUser['id'] ?? 0),
    'items' => $toastItems,
];
$toastJson = json_encode(
    $toastPayload,
    JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
    | JSON_HEX_TAG
    | JSON_HEX_AMP
    | JSON_HEX_APOS
    | JSON_HEX_QUOT
    | JSON_INVALID_UTF8_SUBSTITUTE
);
if (!is_string($toastJson) || $toastJson === '') {
    $toastJson = '{"user_id":0,"items":[]}';
}

$referralCodeValue = trim((string) ($referralCode ?? ''));
$referralLinkValue = trim((string) ($referralLink ?? ''));
$platformReferral = is_array($platformReferral ?? null) ? $platformReferral : [];
$referralsTableReady = !empty($referralsTableReady);
$commissionsTableReady = !empty($commissionsTableReady);
$canShareReferral = !empty($canShareReferral);
$referralCount = max(0, (int) ($referralCount ?? 0));
$referralEarnings = max(0, (int) ($referralEarnings ?? 0));
$commissionCount = max(0, (int) ($commissionCount ?? 0));
$activeAffiliateCount = max(0, (int) ($activeAffiliateCount ?? 0));
$latestCommissionAt = trim((string) ($latestCommissionAt ?? ''));
$referralMinWithdrawal = max(0, (int) ($referralMinWithdrawal ?? 0));
$referralsPageMode = in_array((string) ($referralsPageMode ?? 'overview'), ['overview', 'referees'], true)
    ? (string) $referralsPageMode
    : 'overview';
$earnedByLevel = is_array($earnedByLevel ?? null) ? $earnedByLevel : ['1' => 0, '2' => 0];
$referralCommissions = array_values(is_array($referralCommissions ?? null) ? $referralCommissions : []);
$referralRows = is_array($referralRows ?? null) ? array_values($referralRows) : [];
if ($referralsPageMode !== 'referees' && $referralRows === [] && is_array($referralByUser ?? null)) {
    $referralRows = array_values($referralByUser);
}
if ($referralsPageMode !== 'referees') {
    $referralRows = array_slice($referralRows, 0, 8);
}

$fmtDt = is_callable($fmtDt ?? null)
    ? $fmtDt
    : static function (?string $value): string {
        $raw = trim((string) $value);
        if ($raw === '') {
            return '';
        }
        $ts = strtotime($raw);
        return $ts ? date('d/m/Y H:i', $ts) : $raw;
    };

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

$pluralize = static function (int $count, string $singular, ?string $plural = null): string {
    if ($count === 1) {
        return $singular;
    }

    if ($plural !== null && $plural !== '') {
        return $plural;
    }

    return $singular . 's';
};

$formatPercentLabel = static function (float $value): string {
    $precision = abs($value - round($value)) < 0.00001 ? 0 : 2;
    $formatted = number_format($value, $precision, ',', ' ');
    $formatted = rtrim(rtrim($formatted, '0'), ',');

    return $formatted . ' %';
};

$formatShortDate = static function (?string $value) use ($frMonths): string {
    $raw = trim((string) $value);
    if ($raw === '') {
        return '—';
    }

    $ts = strtotime($raw);
    if ($ts === false) {
        return '—';
    }

    $day = date('j', $ts);
    $monthIndex = (int) date('n', $ts);
    $year = date('Y', $ts);
    $month = (string) ($frMonths[$monthIndex] ?? date('m', $ts));

    return $day . ' ' . $month . ' ' . $year;
};

$formatFullDate = static function (?string $value) use ($formatShortDate): string {
    $raw = trim((string) $value);
    if ($raw === '') {
        return '—';
    }
    $ts = strtotime($raw);
    if ($ts === false) {
        return '—';
    }
    return $formatShortDate($raw) . ' ' . date('H:i', $ts);
};

$makeInitials = static function (string $first, string $last, string $fallback = 'FL'): string {
    $name = trim($first . ' ' . $last);
    if ($name === '') {
        return $fallback;
    }

    $parts = preg_split('/\s+/', $name) ?: [];
    $initials = '';
    foreach ($parts as $part) {
        if ($part === '') {
            continue;
        }
        $initials .= function_exists('mb_substr') ? mb_substr($part, 0, 1) : substr($part, 0, 1);
        if (strlen($initials) >= 2) {
            break;
        }
    }

    return strtoupper($initials !== '' ? $initials : $fallback);
};

$referralStatusBadge = static function (int $purchaseCount): array {
    if ($purchaseCount > 0) {
        return [
            'label' => 'Actif',
            'class' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
            'dot' => 'bg-green-500',
            'icon' => 'fa-check-circle',
        ];
    }

    return [
        'label' => 'Inactif',
        'class' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
        'dot' => 'bg-gray-400',
        'icon' => 'fa-minus-circle',
    ];
};

$monthlyEarnings = 0;
$currentMonthKey = date('Y-m');
foreach ($referralCommissions as $row) {
    $createdAt = trim((string) ($row['created_at'] ?? ''));
    if ($createdAt !== '' && date('Y-m', strtotime($createdAt)) === $currentMonthKey) {
        $monthlyEarnings += (int) ($row['amount_xof'] ?? 0);
    }
}

$conversionRate = $referralCount > 0 ? (int) round(min(100, ($activeAffiliateCount / $referralCount) * 100)) : 0;
$conversionDetailLabel = $referralCount > 0
    ? number_format($activeAffiliateCount, 0, ',', ' ') . ' ' . $pluralize($activeAffiliateCount, 'inscription active', 'inscriptions actives')
        . ' sur ' . number_format($referralCount, 0, ',', ' ') . ' ' . $pluralize($referralCount, 'inscription référée', 'inscriptions référées')
    : 'Aucune inscription référée pour le moment';
$latestCommissionLabel = $latestCommissionAt !== '' ? $formatFullDate($latestCommissionAt) : 'Aucune activité récente';
$l1Rate = (float) ($platformReferral['l1_rate'] ?? 0);
$l2Rate = (float) ($platformReferral['l2_rate'] ?? 0);
$referralCountLabel = number_format($referralCount, 0, ',', ' ') . ' ' . $pluralize($referralCount, 'filleul', 'filleuls');
$activeAffiliateCountLabel = number_format($activeAffiliateCount, 0, ',', ' ') . ' ' . $pluralize($activeAffiliateCount, 'filleul actif', 'filleuls actifs');
$commissionCountLabel = number_format($commissionCount, 0, ',', ' ') . ' ' . $pluralize($commissionCount, 'commission', 'commissions');
$displayedReferralsLabel = number_format(count($referralRows), 0, ',', ' ') . ' ' . $pluralize(count($referralRows), 'filleul affiché', 'filleuls affichés');
$withdrawalMinimumLabel = $referralMinWithdrawal > 0 ? $formatMoney($referralMinWithdrawal) : 'Aucun minimum défini';
$referralCodeBadge = $referralCount > 0 ? (string) min(99, $referralCount) : '';
$notificationCount = max(0, (int) ($unreadCount ?? 0) + (int) ($supportUnreadCount ?? 0) + (int) ($pendingMeetingsCount ?? 0));
$notificationBadge = $notificationCount > 0 ? (string) min(99, $notificationCount) : '';

$shareText = 'Découvrez mon lien de parrainage sur École Numérique';
$shareUrl = $referralLinkValue !== '' ? rawurlencode($referralLinkValue) : '';
$facebookShareUrl = $shareUrl !== '' ? 'https://www.facebook.com/sharer/sharer.php?u=' . $shareUrl : '';
$twitterShareUrl = $shareUrl !== '' ? 'https://twitter.com/intent/tweet?text=' . rawurlencode($shareText) . '&url=' . $shareUrl : '';
$whatsappShareUrl = $referralLinkValue !== '' ? 'https://wa.me/?text=' . rawurlencode($shareText . ' : ' . $referralLinkValue) : '';
$linkedinShareUrl = $shareUrl !== '' ? 'https://www.linkedin.com/sharing/share-offsite/?url=' . $shareUrl : '';

$sidebarLinks = [
    ['label' => 'Tableau de bord', 'url' => $siteUrl('teacher/dashboard'), 'icon' => 'fa-home', 'active' => false, 'badge' => ''],
    ['label' => 'Mes formations', 'url' => $siteUrl('teacher/courses'), 'icon' => 'fa-book-open', 'active' => false, 'badge' => ''],
    ['label' => 'Créer un cours', 'url' => $siteUrl('teacher/course_create'), 'icon' => 'fa-plus-circle', 'active' => false, 'badge' => ''],
    ['label' => 'Revenus', 'url' => $siteUrl('teacher/withdrawals'), 'icon' => 'fa-wallet', 'active' => false, 'badge' => ''],
    ['label' => 'Parrainage', 'url' => $siteUrl('teacher/referrals'), 'icon' => 'fa-gift', 'active' => true, 'badge' => $referralCodeBadge],
    ['label' => 'Paramètres', 'url' => $siteUrl('profile?space=teacher'), 'icon' => 'fa-cog', 'active' => false, 'badge' => ''],
];

$logoUrl = trim((string) ($siteLogoUrl ?? ''));
$logoutUrl = $siteUrl('logout');
$referralOverviewUrl = $siteUrl('teacher/referrals');
$referralFilleulsUrl = $siteUrl('teacher/referrals/filleuls');
?>
<!doctype html>
<html lang="fr" class="light" data-default-theme="light" data-view="teacher/referrals">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="app-global-assets" content="off">
    <title>Parrainage · Enseignant · École Numérique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo e($siteUrl('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()))); ?>"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?php echo e($siteUrl('assets/css/teacher-dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/teacher-dashboard.css') ?: time()))); ?>">
    <script src="<?php echo e($siteUrl('assets/js/web_theme.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/web_theme.js') ?: time()))); ?>" defer></script>
    <script src="<?php echo e($siteUrl('assets/js/global_popups.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/global_popups.js') ?: time()))); ?>" defer></script>
    <script src="<?php echo e($siteUrl('assets/js/referral_copy.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/referral_copy.js') ?: time()))); ?>" defer></script>
    <script src="<?php echo e($siteUrl('assets/js/referral_rules_modal.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/referral_rules_modal.js') ?: time()))); ?>" defer></script>
    <script src="<?php echo e($siteUrl('assets/js/sidebar_toggle.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/sidebar_toggle.js') ?: time()))); ?>" defer></script>
    <script src="<?php echo e($siteUrl('assets/js/teacher_dashboard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/teacher_dashboard.js') ?: time()))); ?>" defer></script>
    <script src="<?php echo e($siteUrl('assets/js/hash_nav_clean.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/hash_nav_clean.js') ?: time()))); ?>" defer></script>
    <style>
        .hover-lift {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .hover-lift:hover {
            transform: translateY(-2px);
        }
    </style>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body data-view="teacher/referrals" class="app-view app-role-teacher teacher-dashboard-page teacher-referrals-page font-sans antialiased text-gray-800 bg-gray-50 dark:bg-gray-900 dark:text-gray-200 transition-colors duration-300 overflow-hidden overflow-x-hidden min-h-screen">
    <div class="teacher-dashboard-layout relative h-screen overflow-hidden pt-16 sm:pt-[4.5rem] md:pl-72 md:flex-row">
        <?php $teacherNavActive = 'referrals'; ?>
        <?php require __DIR__ . '/_sidebar.php'; ?>

        <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

        <main class="teacher-dashboard-main h-full overflow-y-auto bg-gray-50 dark:bg-gray-900" data-scroll-top-root="1">
            <?php $teacherTopTitle = $referralsPageMode === 'referees' ? 'Mes filleuls' : 'Programme de parrainage'; ?>
            <?php $teacherTopSubtitle = 'Espace formateur'; ?>
            <?php require __DIR__ . '/_topbar.php'; ?>

                <?php $referralContentClass = $referralsPageMode === 'referees'
                    ? 'teacher-dashboard-content p-4 pb-24 space-y-6 animate-fade-up sm:p-6 sm:pb-24'
                    : 'teacher-dashboard-content p-4 pb-24 space-y-6 animate-fade-up sm:p-6 sm:pb-24'; ?>
                <div class="<?php echo e($referralContentClass); ?>">
                    <script id="global-popup-data" type="application/json"><?php echo $toastJson; ?></script>

                    <?php $teacherWelcomeTitle = $referralsPageMode === 'referees' ? 'Mes filleuls' : 'Programme de parrainage'; ?>
                    <?php $teacherWelcomeSummary = $referralsPageMode === 'referees'
                        ? ($referralCountLabel . ' · ' . $commissionCountLabel)
                        : ('Partagez votre lien, suivez vos filleuls et vos gains validés.'); ?>
                    <?php $teacherWelcomeActionUrl = $referralsPageMode === 'referees' ? $referralOverviewUrl : $referralFilleulsUrl; ?>
                    <?php $teacherWelcomeActionLabel = $referralsPageMode === 'referees' ? 'Retour au programme' : 'Voir mes filleuls'; ?>
                    <?php $teacherWelcomeActionIcon = $referralsPageMode === 'referees' ? 'arrow-left' : 'users'; ?>
                    <?php require __DIR__ . '/_welcome_hero.php'; ?>

                    <?php if ($referralsPageMode === 'referees'): ?>
                        <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-card dark:border-gray-700 dark:bg-gray-800 sm:p-7">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <p class="text-xs font-bold uppercase tracking-[0.28em] text-primary-500 dark:text-primary-300">Mes filleuls</p>
                                    <h2 class="mt-2 text-2xl font-black tracking-tight text-gray-900 dark:text-white sm:text-3xl">Référés depuis votre code</h2>
                                    <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-500 dark:text-gray-400">
                                        Parcourez la liste paginée des utilisateurs référés depuis votre code et suivez leurs achats associés.
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span class="inline-flex items-center gap-2 rounded-full bg-blue-100 px-3 py-1.5 text-xs font-semibold text-blue-700 dark:bg-blue-500/15 dark:text-blue-300"><i class="fas fa-users" aria-hidden="true"></i><?php echo e($referralCountLabel); ?></span>
                                    <span class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300"><i class="fas fa-user-check" aria-hidden="true"></i><?php echo e($activeAffiliateCountLabel); ?></span>
                                    <span class="inline-flex items-center gap-2 rounded-full bg-amber-100 px-3 py-1.5 text-xs font-semibold text-amber-700 dark:bg-amber-500/15 dark:text-amber-300"><i class="fas fa-coins" aria-hidden="true"></i><?php echo e($commissionCountLabel); ?></span>
                                </div>
                            </div>

                            <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                
                                <a href="<?php echo e($referralOverviewUrl); ?>" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-primary-200 bg-primary-50 px-4 py-2.5 text-sm font-bold text-primary-700 shadow-sm transition hover:bg-primary-100 dark:border-primary-500/20 dark:bg-primary-500/10 dark:text-primary-200 dark:hover:bg-primary-500/15">
                                    <i class="fas fa-arrow-left text-xs" aria-hidden="true"></i>
                                    Retour au programme
                                </a>
                            </div>
                        </section>

                        <section id="mes-filleuls" class="rounded-2xl border border-gray-100 bg-white p-6 shadow-card dark:border-gray-700 dark:bg-gray-800 sm:p-7">
                            <div class="flex flex-col gap-3 border-b border-gray-100 pb-5 dark:border-gray-700 sm:flex-row sm:items-end sm:justify-between">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.28em] text-primary-500 dark:text-primary-300">Liste détaillée</p>
                                    <h3 class="mt-2 text-2xl font-black tracking-tight text-gray-900 dark:text-white">Filleuls référés</h3>
                                    <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-500 dark:text-gray-400">
                                        Inscriptions, dates, statut, achats et gains associés.
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1.5 text-xs font-semibold text-blue-700 dark:bg-blue-500/15 dark:text-blue-300"><?php echo e($displayedReferralsLabel); ?></span>
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300"><?php echo e($referralCountLabel); ?></span>
                                </div>
                            </div>

                            <div class="overflow-x-auto pt-5">
                                <table class="w-full min-w-[760px] text-sm">
                                    <thead class="border-b border-slate-200 text-slate-500 dark:border-slate-700 dark:text-slate-400">
                                        <tr class="text-left">
                                            <th class="pb-4 font-semibold uppercase tracking-[0.22em]">Nom</th>
                                            <th class="pb-4 font-semibold uppercase tracking-[0.22em]">Date d'inscription</th>
                                            <th class="pb-4 font-semibold uppercase tracking-[0.22em]">Statut</th>
                                            <th class="pb-4 text-right font-semibold uppercase tracking-[0.22em]">Achats</th>
                                            <th class="pb-4 text-right font-semibold uppercase tracking-[0.22em]">Gains</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                        <?php if ($referralRows === []): ?>
                                            <tr>
                                                <td colspan="5" class="px-0 py-12">
                                                    <div class="mx-auto flex max-w-md flex-col items-center gap-3 rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-6 py-8 text-center dark:border-slate-700 dark:bg-slate-900/70">
                                                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-primary-100 text-primary-600 dark:bg-primary-500/15 dark:text-primary-200">
                                                            <i class="fas fa-users" aria-hidden="true"></i>
                                                        </span>
                                                        <p class="text-sm font-semibold text-slate-900 dark:text-white">Aucun filleul pour le moment.</p>
                                                        <p class="text-sm leading-6 text-slate-500 dark:text-slate-400">
                                                            Partagez votre code pour faire apparaître vos premiers filleuls ici.
                                                        </p>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($referralRows as $row): ?>
                                                <?php
                                                    $referredUserId = max(0, (int) ($row['referred_user_id'] ?? $row['buyer_id'] ?? 0));
                                                    $firstName = trim((string) ($row['referred_first_name'] ?? $row['buyer_first_name'] ?? $row['buyer_first'] ?? ''));
                                                    $lastName = trim((string) ($row['referred_last_name'] ?? $row['buyer_last_name'] ?? $row['buyer_last'] ?? ''));
                                                    $displayName = trim($firstName . ' ' . $lastName);
                                                    if ($displayName === '') {
                                                        $displayName = $referredUserId > 0 ? 'Filleul #' . $referredUserId : 'Filleul';
                                                    }
                                                    $purchaseCount = max(0, (int) ($row['purchase_count'] ?? $row['total_count'] ?? 0));
                                                    $earningsValue = max(0, (int) ($row['earnings_xof'] ?? $row['total_amount'] ?? 0));
                                                    $referralDate = trim((string) ($row['referral_created_at'] ?? $row['created_at'] ?? ''));
                                                    if ($referralDate === '') {
                                                        $referralDate = trim((string) ($row['last_at'] ?? ''));
                                                    }
                                                    $statusMeta = $referralStatusBadge($purchaseCount);
                                                    $initials = $makeInitials($firstName, $lastName);
                                                ?>
                                                <tr class="transition hover:bg-slate-50/80 dark:hover:bg-slate-800/60">
                                                    <td class="py-4 pr-4">
                                                        <div class="flex items-center gap-3">
                                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-cyan-500 text-xs font-bold text-white shadow-sm">
                                                                <?php echo e($initials); ?>
                                                            </div>
                                                            <div class="min-w-0">
                                                                <p class="truncate font-semibold text-slate-900 dark:text-white"><?php echo e($displayName); ?></p>
                                                                <p class="truncate text-xs text-slate-500 dark:text-slate-400">Référé depuis votre lien</p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="py-4 pr-4 text-slate-600 dark:text-slate-300"><?php echo e($formatShortDate($referralDate)); ?></td>
                                                    <td class="py-4 pr-4">
                                                        <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold <?php echo e($statusMeta['class']); ?>">
                                                            <span class="h-2 w-2 rounded-full <?php echo e($statusMeta['dot']); ?>"></span>
                                                            <i class="fas <?php echo e($statusMeta['icon']); ?>" aria-hidden="true"></i>
                                                            <?php echo e($statusMeta['label']); ?>
                                                        </span>
                                                    </td>
                                                    <td class="py-4 pr-4 text-right font-semibold text-slate-900 dark:text-white"><?php echo e(number_format($purchaseCount, 0, ',', ' ')); ?></td>
                                                    <td class="py-4 text-right font-semibold text-green-600 dark:text-green-300"><?php echo e($formatMoney($earningsValue)); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-5 text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                                <p>
                                    <?php echo e($displayedReferralsLabel); ?> sur <?php echo e($referralCountLabel); ?> au total.
                                </p>
                                <?php if ($referralCount > count($referralRows)): ?>
                                    <p class="font-medium text-primary-600 dark:text-primary-300">
                                        <?php echo e(number_format($referralCount - count($referralRows), 0, ',', ' ') . ' ' . $pluralize($referralCount - count($referralRows), 'autre filleul non affiché ici', 'autres filleuls non affichés ici')); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </section>
                        <?php
                        $pagination = is_array($referralRowsPagination ?? null) ? $referralRowsPagination : [];
                        $basePath = 'teacher/referrals/filleuls';
                        $queryKey = 'page';
                        $baseQuery = [];
                        require __DIR__ . '/_pagination.php';
                        ?>
                    <?php else: ?>
                    <div class="space-y-6 sm:space-y-8">
                        <section class="grid gap-6 lg:grid-cols-[1.35fr_0.92fr] lg:items-start">
                            <article id="partage-parrainage" class="hover-lift relative isolate self-start overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 text-slate-900 shadow-[0_24px_60px_-30px_rgba(15,23,42,0.18)] dark:border-slate-800 dark:bg-slate-950 dark:text-white sm:p-7">
                                <div aria-hidden="true" class="pointer-events-none absolute inset-0">
                                    <div class="absolute -right-12 top-0 h-44 w-44 rounded-full bg-primary-500/8 blur-3xl dark:bg-cyan-500/10"></div>
                                    <div class="absolute -left-12 bottom-0 h-44 w-44 rounded-full bg-cyan-500/8 blur-3xl dark:bg-blue-500/10"></div>
                                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-primary-500 via-cyan-500 to-emerald-400"></div>
                                </div>

                                <div class="relative z-10 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold uppercase tracking-[0.28em] text-primary-600/90 dark:text-sky-200/90">Lien &amp; code</p>
                                        <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-900 dark:text-white sm:text-3xl">Partagez votre parrainage</h2>
                                        
                                    </div>
                                    <div class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-primary-600 to-cyan-500 text-white shadow-lg shadow-primary-600/20 ring-1 ring-white/10">
                                        <i class="fas fa-link text-xl" aria-hidden="true"></i>
                                    </div>
                                </div>

                                <div class="relative z-10 mt-6 grid gap-4 xl:grid-cols-2" data-referral-copy-wrap>
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
                                        <label class="text-[11px] font-semibold uppercase tracking-[0.24em] text-primary-600/90 dark:text-sky-200/90">Lien d'affiliation</label>
                                        <?php if ($canShareReferral && $referralLinkValue !== ''): ?>
                                            <div class="mt-3 flex flex-col gap-3 sm:flex-row">
                                                <div class="relative flex-1">
                                                    <i class="fas fa-paperclip pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500"></i>
                                                    <input type="text" readonly value="<?php echo e($referralLinkValue); ?>" class="w-full rounded-2xl border border-slate-200 bg-white py-3.5 pl-11 pr-4 text-sm font-medium text-slate-900 placeholder-slate-400 shadow-sm outline-none backdrop-blur-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:placeholder-slate-500">
                                                </div>
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-primary-200 bg-primary-50 px-5 py-3.5 text-sm font-bold text-primary-700 shadow-sm transition hover:bg-primary-100 dark:border-primary-500/20 dark:bg-primary-500/10 dark:text-primary-200 dark:hover:bg-primary-500/15"
                                                    data-referral-copy
                                                    data-referral-value="<?php echo e($referralLinkValue); ?>"
                                                    data-referral-copy-success="Lien d'affiliation copié."
                                                    data-referral-copy-error="Copie impossible. Utilisez Ctrl+C."
                                                >
                                                    <i class="fas fa-copy" aria-hidden="true"></i>
                                                    Copier le lien
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <div class="mt-3 rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-4 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-950/70 dark:text-slate-400">
                                                Le lien d'affiliation n'est pas disponible pour le moment.
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
                                        <label class="text-[11px] font-semibold uppercase tracking-[0.24em] text-primary-600/90 dark:text-sky-200/90">Code de parrainage</label>
                                        <?php if ($referralCodeValue !== ''): ?>
                                            <div class="mt-3 flex flex-col gap-3 sm:flex-row">
                                                <div class="relative flex-1">
                                                    <i class="fas fa-tag pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500"></i>
                                                    <input type="text" readonly value="<?php echo e($referralCodeValue); ?>" class="w-full rounded-2xl border border-slate-200 bg-white py-3.5 pl-11 pr-4 font-mono text-sm font-semibold uppercase tracking-[0.16em] text-slate-900 shadow-sm outline-none backdrop-blur-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                                                </div>
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-primary-200 bg-primary-50 px-5 py-3.5 text-sm font-bold text-primary-700 shadow-sm transition hover:bg-primary-100 dark:border-primary-500/20 dark:bg-primary-500/10 dark:text-primary-200 dark:hover:bg-primary-500/15"
                                                    data-referral-copy
                                                    data-referral-value="<?php echo e($referralCodeValue); ?>"
                                                    data-referral-copy-success="Code de parrainage copié."
                                                    data-referral-copy-error="Copie impossible. Utilisez Ctrl+C."
                                                >
                                                    <i class="fas fa-copy" aria-hidden="true"></i>
                                                    Copier le code
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <div class="mt-3 rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-4 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-950/70 dark:text-slate-400">
                                                Votre code sera généré automatiquement dès que votre compte est chargé.
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="xl:col-span-2 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
                                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">Partager :</span>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <?php if ($referralLinkValue !== ''): ?>
                                                <a href="<?php echo e($facebookShareUrl); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-[#1877F2] text-white shadow-sm shadow-[#1877F2]/25 transition hover:-translate-y-0.5 hover:bg-[#166FE5]" data-referral-share data-referral-value="<?php echo e($referralLinkValue); ?>" data-referral-copy-success="Lien partagé sur Facebook." data-referral-copy-fallback-success="Lien copié." data-referral-copy-error="Partage impossible." aria-label="Partager sur Facebook" title="Partager sur Facebook">
                                                    <i class="fab fa-facebook-f" aria-hidden="true"></i>
                                                </a>
                                                <a href="<?php echo e($twitterShareUrl); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-[#111111] text-white shadow-sm shadow-black/20 transition hover:-translate-y-0.5 hover:bg-black" data-referral-share data-referral-value="<?php echo e($referralLinkValue); ?>" data-referral-copy-success="Lien partagé sur X." data-referral-copy-fallback-success="Lien copié." data-referral-copy-error="Partage impossible." aria-label="Partager sur X" title="Partager sur X">
                                                    <i class="fab fa-twitter" aria-hidden="true"></i>
                                                </a>
                                                <a href="<?php echo e($whatsappShareUrl); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-[#25D366] text-white shadow-sm shadow-[#25D366]/25 transition hover:-translate-y-0.5 hover:bg-[#1EBE57]" data-referral-share data-referral-value="<?php echo e($referralLinkValue); ?>" data-referral-copy-success="Lien partagé sur WhatsApp." data-referral-copy-fallback-success="Lien copié." data-referral-copy-error="Partage impossible." aria-label="Partager sur WhatsApp" title="Partager sur WhatsApp">
                                                    <i class="fab fa-whatsapp" aria-hidden="true"></i>
                                                </a>
                                                <a href="<?php echo e($linkedinShareUrl); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-[#0A66C2] text-white shadow-sm shadow-[#0A66C2]/25 transition hover:-translate-y-0.5 hover:bg-[#0956A4]" data-referral-share data-referral-value="<?php echo e($referralLinkValue); ?>" data-referral-copy-success="Lien partagé sur LinkedIn." data-referral-copy-fallback-success="Lien copié." data-referral-copy-error="Partage impossible." aria-label="Partager sur LinkedIn" title="Partager sur LinkedIn">
                                                    <i class="fab fa-linkedin-in" aria-hidden="true"></i>
                                                </a>
                                                <a href="<?php echo e($referralLinkValue); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:hover:bg-slate-900">
                                                    <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                                                    Ouvrir
                                                </a>
                                                <button type="button" class="inline-flex items-center justify-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:hover:bg-slate-900" data-referral-rules-open aria-haspopup="dialog" aria-controls="referral-rules-modal">
                                                    <i class="fas fa-question-circle" aria-hidden="true"></i>
                                                    Règles du programme
                                                </button>
                                            <?php else: ?>
                                                <span class="rounded-full bg-slate-900/5 px-3 py-1.5 text-xs font-semibold text-slate-500 dark:bg-white/5 dark:text-slate-300">Chargement du partage en cours</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="xl:col-span-2 flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
                                        <span class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                                            <i class="fas fa-thumbtack text-slate-400 dark:text-slate-500" aria-hidden="true"></i>
                                            Épinglé
                                        </span>
                                        <span class="inline-flex items-center rounded-full bg-blue-500/15 px-3 py-1.5 text-xs font-semibold text-blue-700 dark:bg-blue-500/20 dark:text-blue-200">L1 : <?php echo e(number_format($l1Rate, 0, ',', ' ')); ?>%</span>
                                    <span class="inline-flex items-center rounded-full bg-cyan-500/15 px-3 py-1.5 text-xs font-semibold text-cyan-700 dark:bg-cyan-500/20 dark:text-cyan-200">L2 : <?php echo e(number_format($l2Rate, 0, ',', ' ')); ?>%</span>
                                    <span class="inline-flex items-center rounded-full bg-emerald-500/15 px-3 py-1.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200"><?php echo e($referralCountLabel); ?></span>
                                    </div>

                                    <div class="xl:col-span-2 grid gap-3 sm:grid-cols-3">
                                        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 shadow-sm dark:border-blue-500/20 dark:bg-blue-500/10">
                                            <div class="flex items-center justify-between gap-3">
                                                <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-700 dark:text-blue-200">Filleuls</p>
                                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-blue-600/10 text-blue-600 dark:bg-blue-400/15 dark:text-blue-200">
                                                    <i class="fas fa-users text-sm" aria-hidden="true"></i>
                                                </span>
                                            </div>
                                            <p class="mt-3 text-2xl font-black text-slate-900 dark:text-white"><?php echo e(number_format($referralCount, 0, ',', ' ')); ?></p>
                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Référés via votre code</p>
                                        </div>

                                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
                                            <div class="flex items-center justify-between gap-3">
                                                <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-emerald-700 dark:text-emerald-200">Actifs</p>
                                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-emerald-600/10 text-emerald-600 dark:bg-emerald-400/15 dark:text-emerald-200">
                                                    <i class="fas fa-user-check text-sm" aria-hidden="true"></i>
                                                </span>
                                            </div>
                                            <p class="mt-3 text-2xl font-black text-slate-900 dark:text-white"><?php echo e(number_format($activeAffiliateCount, 0, ',', ' ')); ?></p>
                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Filleuls actifs</p>
                                        </div>

                                        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-sm dark:border-amber-500/20 dark:bg-amber-500/10">
                                            <div class="flex items-center justify-between gap-3">
                                                <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-amber-700 dark:text-amber-200">Commissions</p>
                                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-amber-600/10 text-amber-600 dark:bg-amber-400/15 dark:text-amber-200">
                                                    <i class="fas fa-coins text-sm" aria-hidden="true"></i>
                                                </span>
                                            </div>
                                            <p class="mt-3 text-2xl font-black text-slate-900 dark:text-white"><?php echo e(number_format($commissionCount, 0, ',', ' ')); ?></p>
                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Achats / versements suivis</p>
                                        </div>
                                    </div>
                                </div>
                            </article>

                            <article class="hover-lift rounded-2xl border border-gray-100 bg-white p-6 shadow-card dark:border-gray-700 dark:bg-gray-800">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-[0.28em] text-yellow-500 dark:text-yellow-300">Mes gains</p>
                                        <h3 class="mt-2 text-2xl font-black tracking-tight text-gray-900 dark:text-white">Performance du parrainage</h3>
                                    </div>
                                    <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-yellow-100 text-yellow-600 dark:bg-yellow-500/15 dark:text-yellow-300">
                                        <i class="fas fa-coins text-lg" aria-hidden="true"></i>
                                    </div>
                                </div>

                                <dl class="mt-6 space-y-4">
                                    <div class="flex items-center justify-between gap-4 rounded-2xl border border-gray-100 bg-white px-4 py-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                                        <dt class="text-sm text-gray-500 dark:text-gray-400">Total gagné</dt>
                                        <dd class="text-lg font-extrabold text-gray-900 dark:text-white"><?php echo e($formatMoney($referralEarnings)); ?></dd>
                                    </div>
                                    <div class="flex items-center justify-between gap-4 rounded-2xl border border-gray-100 bg-white px-4 py-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                                        <dt class="text-sm text-gray-500 dark:text-gray-400">Ce mois</dt>
                                        <dd class="text-lg font-extrabold text-green-600 dark:text-green-300"><?php echo e($formatMoney($monthlyEarnings)); ?></dd>
                                    </div>
                                    <div class="flex items-center justify-between gap-4 rounded-2xl border border-gray-100 bg-white px-4 py-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                                        <dt class="text-sm text-gray-500 dark:text-gray-400">Filleuls actifs</dt>
                                        <dd class="text-lg font-extrabold text-gray-900 dark:text-white"><?php echo e(number_format($activeAffiliateCount, 0, ',', ' ')); ?></dd>
                                    </div>
                                    <div class="flex items-start justify-between gap-4 rounded-2xl border border-gray-100 bg-white px-4 py-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                                        <div class="min-w-0">
                                            <dt class="text-sm text-gray-500 dark:text-gray-400">Taux de conversion</dt>
                                            <p class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400"><?php echo e($conversionDetailLabel); ?></p>
                                        </div>
                                        <dd class="shrink-0 text-lg font-extrabold text-primary-600 dark:text-primary-300"><?php echo e(number_format($conversionRate, 0, ',', ' ')); ?>%</dd>
                                    </div>
                                </dl>

                                <div class="mt-6 grid grid-cols-2 gap-3">
                                    <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-card dark:border-gray-700 dark:bg-gray-800">
                                        <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">Commissions</p>
                                        <p class="mt-2 text-2xl font-black text-gray-900 dark:text-white"><?php echo e(number_format($commissionCount, 0, ',', ' ')); ?></p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Achats / versements suivis</p>
                                    </div>
                                    <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-card dark:border-gray-700 dark:bg-gray-800">
                                        <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">Dernière activité</p>
                                        <p class="mt-2 text-lg font-black text-gray-900 dark:text-white"><?php echo e($latestCommissionLabel); ?></p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Dernière commission validée</p>
                                    </div>
                                </div>

                                <a href="<?php echo e($siteUrl('teacher/withdrawals')); ?>" class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-primary-600 px-5 py-3.5 text-sm font-bold text-white shadow-lg shadow-primary-600/20 transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-300 dark:bg-primary-500 dark:hover:bg-primary-600">
                                    <i class="fas fa-wallet" aria-hidden="true"></i>
                                    Voir les retraits
                                </a>
                            </article>
                        </section>

                        <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-card dark:border-gray-700 dark:bg-gray-800 sm:p-7" id="mes-filleuls">
                            <div class="flex flex-col gap-3 border-b border-gray-100 pb-5 dark:border-gray-700 sm:flex-row sm:items-end sm:justify-between">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.28em] text-primary-500 dark:text-primary-300">Mes filleuls</p>
                                    <h2 class="mt-2 text-2xl font-black tracking-tight text-gray-900 dark:text-white">Référés depuis votre code</h2>
                                    
                                </div>
                                <a href="<?php echo e($referralFilleulsUrl); ?>" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-primary-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-primary-600/20 transition hover:bg-primary-700">
                                    <i class="fas fa-users" aria-hidden="true"></i>
                                    Voir les filleuls
                                </a>
                            </div>

                            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                                <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 shadow-sm dark:border-blue-500/20 dark:bg-blue-500/10">
                                    <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-700 dark:text-blue-200">Filleuls</p>
                                    <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white"><?php echo e($referralCountLabel); ?></p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Référés via votre code</p>
                                </div>
                                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
                                    <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-emerald-700 dark:text-emerald-200">Actifs</p>
                                    <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white"><?php echo e($activeAffiliateCountLabel); ?></p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Filleuls actifs</p>
                                </div>
                                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-sm dark:border-amber-500/20 dark:bg-amber-500/10">
                                    <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-amber-700 dark:text-amber-200">Commissions</p>
                                    <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white"><?php echo e(number_format($commissionCount, 0, ',', ' ')); ?></p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Achats / versements suivis</p>
                                </div>
                            </div>
                        </section>

                    </div>
                    <div id="referral-rules-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4 opacity-0 transition duration-200 backdrop-blur-sm" data-referral-rules-modal aria-hidden="true">
                        <section class="w-full max-w-2xl max-h-[calc(100vh-2rem)] overflow-y-auto rounded-2xl bg-white p-5 shadow-2xl transition duration-200 opacity-0 translate-y-3 scale-95 dark:bg-slate-900" data-modal-panel role="dialog" aria-modal="true" aria-labelledby="referral-rules-modal-title">
                            <div class="mb-6 flex items-center justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="text-xs font-bold uppercase tracking-[0.28em] text-sky-600 dark:text-sky-300">Règles du programme</p>
                                    <h3 id="referral-rules-modal-title" class="mt-2 text-xl font-bold text-slate-900 dark:text-white sm:text-2xl">Niveaux, validation et retrait</h3>
                                    <p class="mt-1 text-sm font-medium text-sky-600 dark:text-sky-300">Le fonctionnement du parrainage est présenté dans un format simple et lisible.</p>
                                </div>
                                <button type="button" class="close-modal inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white" data-referral-rules-close aria-label="Fermer">
                                    <i class="fas fa-times" aria-hidden="true"></i>
                                </button>
                            </div>

                            <div class="space-y-3">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800/70">
                                    <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Niveau 1</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-700 dark:text-slate-200"><?php echo e($formatPercentLabel($l1Rate)); ?> sur les achats directs de vos filleuls.</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800/70">
                                    <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Niveau 2</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-700 dark:text-slate-200"><?php echo e($formatPercentLabel($l2Rate)); ?> sur les achats des personnes invitées par vos filleuls.</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800/70">
                                    <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Validation</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-700 dark:text-slate-200">Commissions validées après paiement confirmé.</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800/70">
                                    <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Retrait minimum</p>
                                    <p class="mt-2 text-sm font-bold text-slate-900 dark:text-white"><?php echo e($withdrawalMinimumLabel); ?></p>
                                    <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">Le suivi des retraits de parrainage reste disponible dans votre espace Revenus.</p>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end">
                                <button type="button" class="close-modal inline-flex items-center justify-center gap-2 rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-600" data-referral-rules-close>
                                    Fermer
                                </button>
                            </div>
                        </section>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
