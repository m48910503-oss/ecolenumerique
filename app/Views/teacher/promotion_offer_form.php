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
$promoTargetCandidates = array_values(is_array($promoTargetCandidates ?? null) ? $promoTargetCandidates : []);
$promoCompanyCandidates = array_values(is_array($promoCompanyCandidates ?? null) ? $promoCompanyCandidates : []);
$couponCodeSupported = (bool) ($couponCodeSupported ?? false);
$canTargetPromos = (bool) ($canTargetPromos ?? false);
$formMode = strtolower(trim((string) ($formMode ?? 'promotion')));
$formMode = $formMode === 'coupon' ? 'coupon' : 'promotion';
$isCouponMode = $formMode === 'coupon';
$formTitle = $isCouponMode ? 'Nouveau code promo' : 'Nouvelle promotion';
$formLead = $isCouponMode
    ? 'Configurez le code promo en trois étapes, puis validez le récapitulatif.'
    : 'Configurez la promotion en trois étapes, puis validez le récapitulatif.';
$heroToneClasses = $isCouponMode
    ? 'border-violet-200 bg-violet-50/80 dark:border-violet-900/30 dark:bg-violet-900/15'
    : 'border-sky-200 bg-sky-50/80 dark:border-sky-900/30 dark:bg-sky-900/15';
$heroChipClasses = $isCouponMode
    ? 'bg-violet-100 text-violet-700 dark:bg-violet-900/35 dark:text-violet-200'
    : 'bg-sky-100 text-sky-700 dark:bg-sky-900/35 dark:text-sky-200';
$teacherTopTitle = $formTitle;
$teacherTopSubtitle = 'Parcours en 3 étapes';
$showCatalogue = false;
$showSupportFab = true;

$wizardStep = max(1, min(3, (int) ($wizardStep ?? 1)));
$wizardBaseUrl = trim((string) ($wizardBaseUrl ?? site_url('teacher/promotions?form=' . $formMode)));
$wizardStepUrl = is_callable($wizardStepUrl ?? null)
    ? $wizardStepUrl
    : static function (int $step) use ($wizardBaseUrl): string {
        $step = max(1, min(3, $step));
        return $wizardBaseUrl . '&step=' . $step;
    };
$wizardDraft = array_merge([
    'course_id' => '',
    'promo_type' => 'percent',
    'promo_amount' => '',
    'promo_percent' => '',
    'promo_schedule_mode' => 'duration',
    'promo_duration_days' => '',
    'promo_start_at' => '',
    'promo_end_at' => '',
    'promo_target_type' => 'public',
    'promo_targets' => '',
    'promo_company_id' => '',
    'promo_company_seats' => '',
    'coupon_code_mode' => 'auto',
    'coupon_code' => '',
], is_array($wizardDraft ?? null) ? $wizardDraft : []);

$normalizeScheduleMode = static function (?string $value, string $default = 'duration'): string {
    $mode = strtolower(trim((string) $value));
    if (in_array($mode, ['duration', 'custom'], true)) {
        return $mode;
    }
    return $default;
};

$formatMoney = static function (int|float $value): string {
    return number_format((float) $value, 0, ',', ' ') . ' F CFA';
};

$compactNumber = static function (float $value): string {
    $formatted = number_format($value, 2, '.', '');
    return rtrim(rtrim($formatted, '0'), '.');
};

$courseOptionLabel = static function (array $course) use ($formatMoney): string {
    $title = trim((string) ($course['title'] ?? ''));
    $price = (int) ($course['price_xof'] ?? 0);
    return $title !== '' ? ($title . ' · ' . $formatMoney($price)) : 'Cours sans titre';
};

$formatDateFn = is_callable($fmtDt ?? null) ? $fmtDt : null;
$formatWizardDate = static function (?string $value) use ($formatDateFn): string {
    $value = trim((string) $value);
    if ($value === '') {
        return '—';
    }
    if (is_callable($formatDateFn)) {
        try {
            return (string) $formatDateFn($value);
        } catch (Throwable $e) {
            // Fall through to a local formatter.
        }
    }
    $ts = strtotime($value);
    return $ts !== false ? date('d/m/Y H:i', $ts) : $value;
};

$courseMap = [];
foreach ($courses as $course) {
    $courseId = (int) ($course['id'] ?? 0);
    if ($courseId > 0) {
        $courseMap[$courseId] = $course;
    }
}

$selectedCourseId = (int) ($wizardDraft['course_id'] ?? 0);
$selectedCourse = $courseMap[$selectedCourseId] ?? null;
$selectedCourseTitle = trim((string) ($selectedCourse['title'] ?? ''));
$selectedCoursePrice = (int) ($selectedCourse['price_xof'] ?? 0);
$selectedCourseLabel = $selectedCourseTitle !== ''
    ? ($selectedCourseTitle . ' · ' . $formatMoney($selectedCoursePrice))
    : 'Aucun cours sélectionné';

$promoType = (string) ($wizardDraft['promo_type'] ?? 'percent');
if (!in_array($promoType, ['amount', 'percent'], true)) {
    $promoType = 'percent';
}
$promoAmountRaw = trim((string) ($wizardDraft['promo_amount'] ?? ''));
$promoPercentRaw = str_replace(',', '.', trim((string) ($wizardDraft['promo_percent'] ?? '')));
$previewFinalPrice = null;
$previewDiscountLabel = '—';
$previewSavingsLabel = 'Choisissez un cours et une valeur de réduction.';
if ($selectedCoursePrice > 0) {
    if ($promoType === 'amount' && $promoAmountRaw !== '' && is_numeric($promoAmountRaw)) {
        $previewFinalPrice = max(0, (int) $promoAmountRaw);
        $previewDiscountLabel = 'Prix fixe';
        $previewSavingsLabel = $previewFinalPrice <= $selectedCoursePrice
            ? 'Économie estimée : ' . $formatMoney(max(0, $selectedCoursePrice - (int) $previewFinalPrice)) . '.'
            : 'Le nouveau prix est supérieur au tarif actuel.';
    } elseif ($promoType === 'percent' && $promoPercentRaw !== '' && is_numeric($promoPercentRaw)) {
        $percentValue = (float) $promoPercentRaw;
        $previewFinalPrice = max(0, (int) round($selectedCoursePrice * (1 - ($percentValue / 100))));
        $previewDiscountLabel = '-' . $compactNumber($percentValue) . '%';
        $previewSavingsLabel = $previewFinalPrice === 0
            ? 'Le cours sera offert gratuitement pendant cette période.'
            : 'Économie estimée : ' . $formatMoney(max(0, $selectedCoursePrice - (int) $previewFinalPrice)) . '.';
    }
}
$previewBadgeLabel = $previewFinalPrice === null
    ? 'En attente'
    : ($previewFinalPrice === 0 ? 'Offert' : 'Aperçu prêt');

$scheduleMode = $normalizeScheduleMode((string) ($wizardDraft['promo_schedule_mode'] ?? 'duration'));
$durationDays = trim((string) ($wizardDraft['promo_duration_days'] ?? ''));
$scheduleSummary = $scheduleMode === 'custom'
    ? $formatWizardDate((string) ($wizardDraft['promo_start_at'] ?? '')) . ' → ' . $formatWizardDate((string) ($wizardDraft['promo_end_at'] ?? ''))
    : ($durationDays !== '' ? ($durationDays . ' jours') : '—');
$scheduleModeLabel = $scheduleMode === 'custom' ? 'Dates exactes' : 'Durée en jours';

$targetType = (string) ($wizardDraft['promo_target_type'] ?? 'public');
if (!in_array($targetType, ['public', 'individual', 'company'], true)) {
    $targetType = 'public';
}
$targetTypeLabels = [
    'public' => 'Tout le monde',
    'individual' => 'Utilisateurs ciblés',
    'company' => 'Entreprise',
];
$targetLabel = $targetTypeLabels[$targetType] ?? 'Tout le monde';
$targetDetail = 'Aucun ciblage supplémentaire.';
$companyMap = [];
foreach ($promoCompanyCandidates as $company) {
    $companyId = (int) ($company['id'] ?? 0);
    if ($companyId > 0) {
        $companyMap[$companyId] = $company;
    }
}
if ($targetType === 'individual') {
    $targetEmails = array_values(array_filter(array_map('trim', preg_split('/[\s,;]+/', (string) ($wizardDraft['promo_targets'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [])));
    $targetDetail = $targetEmails !== [] ? implode(', ', $targetEmails) : 'Aucun utilisateur sélectionné.';
} elseif ($targetType === 'company') {
    $selectedCompanyId = (int) ($wizardDraft['promo_company_id'] ?? 0);
    $selectedCompany = $companyMap[$selectedCompanyId] ?? null;
    $companyName = trim((string) ($selectedCompany['name'] ?? ''));
    $companySeats = trim((string) ($wizardDraft['promo_company_seats'] ?? ''));
    $targetDetail = $companyName !== ''
        ? ($companyName . ($companySeats !== '' ? (' · ' . $companySeats . ' places') : ''))
        : 'Entreprise non sélectionnée.';
}

$couponMode = (string) ($wizardDraft['coupon_code_mode'] ?? 'auto');
if (!in_array($couponMode, ['auto', 'manual'], true)) {
    $couponMode = 'auto';
}
$couponModeLabel = $couponMode === 'manual' ? 'Code personnalisé' : 'Génération automatique';
$couponCodeInputValue = trim((string) ($wizardDraft['coupon_code'] ?? ''));
$couponValue = $couponMode === 'manual'
    ? ($couponCodeInputValue !== '' ? $couponCodeInputValue : '—')
    : 'Le code sera généré automatiquement.';

$wizardStepLabels = [
    1 => 'Base',
    2 => 'Période',
    3 => 'Récap',
];
$wizardStepStepClass = static function (int $step) use ($wizardStep): string {
    if ($wizardStep === $step) {
        return 'inline-flex items-center gap-2 rounded-full bg-primary-50 px-3 py-2 text-primary-700 dark:bg-primary-900/25 dark:text-primary-300';
    }
    if ($wizardStep > $step) {
        return 'inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-2 text-emerald-700 dark:bg-emerald-900/25 dark:text-emerald-300';
    }
    return 'inline-flex items-center gap-2 rounded-full bg-white px-3 py-2 text-gray-600 ring-1 ring-gray-200 dark:bg-gray-900 dark:text-gray-300 dark:ring-gray-700';
};
$wizardStepBadgeClass = static function (int $step) use ($wizardStep): string {
    if ($wizardStep === $step) {
        return 'bg-primary-600 text-white';
    }
    if ($wizardStep > $step) {
        return 'bg-emerald-600 text-white';
    }
    return 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300';
};

$wizardStep1Url = $wizardStepUrl(1);
$wizardStep2Url = $wizardStepUrl(2);
$wizardStep3Url = $wizardStepUrl(3);
$backUrl = site_url('teacher/promotions');

?>
<!DOCTYPE html>
<html lang="fr" class="light" data-default-theme="light" data-view="teacher/promotions-form">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="app-global-assets" content="off">
  <title><?php echo e($formTitle); ?> · Enseignant · École Numérique</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="<?php echo e(site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()))); ?>"></script>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="<?php echo e(site_url('assets/css/teacher-dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/teacher-dashboard.css') ?: time()))); ?>">
  <script src="<?php echo e(site_url('assets/js/sidebar_toggle.js')); ?>" defer></script>
  <script src="<?php echo e(site_url('assets/js/teacher_dashboard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/teacher_dashboard.js') ?: time()))); ?>" defer></script>
  <script src="<?php echo e(site_url('assets/js/promotions_page.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/promotions_page.js') ?: time()))); ?>" defer></script>
  <link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body data-view="teacher/promotions-form" class="app-role-teacher teacher-promotions-page font-sans antialiased text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-900 transition-colors duration-300 min-h-screen overflow-hidden overflow-x-hidden">
  <div class="teacher-dashboard-layout relative h-screen overflow-hidden pt-16 sm:pt-[4.5rem] md:pl-72 md:flex-row">
    <?php $teacherNavActive = 'promotions'; ?>
    <?php require __DIR__ . '/_sidebar.php'; ?>
    <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

    <main class="teacher-dashboard-main h-full overflow-y-auto bg-gray-50 dark:bg-gray-900" data-scroll-top-root="1">
      <?php require __DIR__ . '/_topbar.php'; ?>

      <div class="mx-auto max-w-7xl p-4 pb-24 space-y-6 animate-fade-up sm:p-6 sm:pb-24">
        <?php $teacherWelcomeTitle = $formTitle; ?>
        <?php $teacherWelcomeSummary = $formLead; ?>
        <?php $teacherWelcomeActionUrl = $backUrl; ?>
        <?php $teacherWelcomeActionLabel = 'Retour'; ?>
        <?php $teacherWelcomeActionIcon = 'arrow-left'; ?>
        <?php require __DIR__ . '/_welcome_hero.php'; ?>

        <section class="relative overflow-hidden rounded-[28px] border <?php echo e($heroToneClasses); ?> shadow-[0_28px_80px_-44px_rgba(15,23,42,0.32)]">
          <div class="flex flex-wrap items-start justify-between gap-5 border-b border-white/70 px-5 py-5 dark:border-white/10 sm:px-6">
            <div class="min-w-0">
              <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400"><?php echo $isCouponMode ? 'Code promo privé' : 'Promotion publique'; ?></p>
              <h1 class="mt-2 text-2xl font-extrabold text-slate-950 dark:text-white"><?php echo e($formTitle); ?></h1>
              <p class="mt-2 text-sm text-slate-600 dark:text-slate-300"><?php echo e($formLead); ?></p>
              <div class="mt-4 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold <?php echo e($heroChipClasses); ?>">
                  <i class="fas <?php echo $isCouponMode ? 'fa-ticket-alt' : 'fa-percent'; ?>"></i>
                  <?php echo $isCouponMode ? 'Code promo privé' : 'Promotion publique'; ?>
                </span>
                <span class="inline-flex items-center rounded-full bg-white/90 px-3 py-1.5 text-xs font-semibold text-slate-600 ring-1 ring-slate-200 dark:bg-slate-900/70 dark:text-slate-300 dark:ring-slate-700">
                  <?php echo e((string) count($courses)); ?> cours disponibles
                </span>
                <span class="inline-flex items-center rounded-full bg-white/90 px-3 py-1.5 text-xs font-semibold text-slate-600 ring-1 ring-slate-200 dark:bg-slate-900/70 dark:text-slate-300 dark:ring-slate-700">
                  <?php echo $isCouponMode ? ($couponCodeSupported ? 'Code promo activé' : 'Code promo désactivé') : 'Validation requise'; ?>
                </span>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <a href="<?php echo e($backUrl); ?>" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                <i class="fas fa-arrow-left"></i>
                Retour
              </a>
            </div>
          </div>

          <div class="px-5 py-5 sm:px-6">
            <div class="flex flex-wrap items-center gap-2 text-sm font-medium text-gray-500 dark:text-gray-400">
              <?php foreach ($wizardStepLabels as $step => $label): ?>
                <?php $stepIsActive = $wizardStep === $step; ?>
                <?php $stepClass = $wizardStepStepClass($step); ?>
                <?php $badgeClass = $wizardStepBadgeClass($step); ?>
                <span class="<?php echo e($stepClass); ?>"<?php echo $stepIsActive ? ' aria-current="step"' : ''; ?>>
                  <?php if ($wizardStep > $step): ?>
                    <a href="<?php echo e($wizardStepUrl($step)); ?>" class="inline-flex items-center gap-2">
                      <span class="flex h-7 w-7 items-center justify-center rounded-full <?php echo e($badgeClass); ?>"><?php echo e((string) $step); ?></span>
                      <span><?php echo e($label); ?></span>
                    </a>
                  <?php else: ?>
                    <span class="inline-flex items-center gap-2">
                      <span class="flex h-7 w-7 items-center justify-center rounded-full <?php echo e($badgeClass); ?>"><?php echo e((string) $step); ?></span>
                      <span><?php echo e($label); ?></span>
                    </span>
                  <?php endif; ?>
                </span>
                <?php if ($step < 3): ?>
                  <span class="text-gray-300 dark:text-gray-600"><i class="fas fa-chevron-right" aria-hidden="true"></i></span>
                <?php endif; ?>
              <?php endforeach; ?>
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-3">
              <div class="rounded-2xl border border-white/80 bg-white/85 px-4 py-3 shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/70 dark:ring-slate-700">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Étape active</p>
                <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white"><?php echo e($wizardStepLabels[$wizardStep] ?? 'Base'); ?></p>
              </div>
              <div class="rounded-2xl border border-white/80 bg-white/85 px-4 py-3 shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/70 dark:ring-slate-700">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Cours choisi</p>
                <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white"><?php echo e($selectedCourseLabel); ?></p>
              </div>
              <div class="rounded-2xl border border-white/80 bg-white/85 px-4 py-3 shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/70 dark:ring-slate-700">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Prix final</p>
                <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white"><?php echo e($previewFinalPrice !== null ? $formatMoney((int) $previewFinalPrice) : '—'); ?></p>
              </div>
            </div>
          </div>
        </section>

        <?php if ($errors !== []): ?>
          <section class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-rose-800 shadow-sm dark:border-rose-900/40 dark:bg-rose-900/20 dark:text-rose-200">
            <div class="flex items-start gap-3">
              <i class="fas fa-exclamation-circle mt-0.5"></i>
              <div class="min-w-0">
                <p class="font-semibold">Le formulaire n’a pas été enregistré.</p>
                <ul class="mt-2 space-y-1 text-sm">
                  <?php foreach ($errors as $error): ?>
                    <li><?php echo e((string) $error); ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            </div>
          </section>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
          <section class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-emerald-800 shadow-sm dark:border-emerald-900/40 dark:bg-emerald-900/20 dark:text-emerald-200">
            <div class="flex items-start gap-3">
              <i class="fas fa-check-circle mt-0.5"></i>
              <p class="font-semibold"><?php echo e($success); ?></p>
            </div>
          </section>
        <?php endif; ?>

        <?php if ($courses === []): ?>
          <section class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300">
              <i class="fas fa-book"></i>
            </div>
            <h2 class="mt-4 text-lg font-bold text-slate-900 dark:text-white">Aucun cours publié</h2>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Publiez d’abord un cours pour créer une promotion ou un code promo.</p>
            <a href="<?php echo e(site_url('teacher/courses')); ?>" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-primary-600 px-5 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-primary-700">
              <i class="fas fa-book-open"></i>
              Voir mes cours
            </a>
          </section>
        <?php elseif ($isCouponMode && !$couponCodeSupported): ?>
          <section class="rounded-2xl border border-amber-200 bg-amber-50 px-6 py-10 text-center shadow-sm dark:border-amber-900/40 dark:bg-amber-900/20">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 dark:bg-amber-900/35 dark:text-amber-200">
              <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2 class="mt-4 text-lg font-bold text-slate-900 dark:text-white">Module code promo indisponible</h2>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Appliquez la migration `database/schema_v44.sql`, puis rechargez cette page.</p>
            <a href="<?php echo e($backUrl); ?>" class="mt-6 inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
              <i class="fas fa-arrow-left"></i>
              Retour
            </a>
          </section>
        <?php else: ?>
          <?php if ($wizardStep === 1): ?>
            <form method="post" action="<?php echo e($wizardStep1Url); ?>" class="space-y-6" data-promo-form data-promo-create-page>
              <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">

              <section class="rounded-[28px] border border-slate-200 bg-white shadow-[0_18px_60px_-40px_rgba(15,23,42,0.35)] dark:border-slate-700 dark:bg-slate-900/80">
                <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 px-5 py-5 dark:border-slate-800 sm:px-6">
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Étape 1 / 3</p>
                    <h2 class="mt-2 text-xl font-bold text-slate-950 dark:text-white">Base de la promotion</h2>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Choisissez le cours, puis le type et la valeur de la réduction.</p>
                  </div>
                  <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold <?php echo e($heroChipClasses); ?>">
                    <i class="fas <?php echo $isCouponMode ? 'fa-ticket-alt' : 'fa-percent'; ?>"></i>
                    <?php echo $isCouponMode ? 'Code promo privé' : 'Promotion publique'; ?>
                  </span>
                </div>

                <div class="grid gap-6 px-5 py-5 sm:px-6 lg:grid-cols-[minmax(0,1.35fr)_minmax(0,0.95fr)]">
                  <div class="space-y-4">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5 dark:border-slate-700 dark:bg-slate-800/70">
                      <div class="flex items-start gap-3">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-sky-100 text-sky-600 dark:bg-sky-900/35 dark:text-sky-200">
                          <i class="fas fa-sliders-h"></i>
                        </span>
                        <div>
                          <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Base</p>
                          <h3 class="text-base font-bold text-slate-950 dark:text-white">Cours et réduction</h3>
                        </div>
                      </div>

                      <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <div>
                          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Cours concerné <span class="text-red-500">*</span></label>
                          <select name="course_id" required class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                            <option value="">Sélectionnez un cours</option>
                            <?php foreach ($courses as $course): ?>
                              <?php $courseId = (int) ($course['id'] ?? 0); ?>
                              <option value="<?php echo e((string) $courseId); ?>" data-course-price="<?php echo e((string) ((int) ($course['price_xof'] ?? 0))); ?>" <?php echo (string) ($wizardDraft['course_id'] ?? '') === (string) $courseId ? 'selected' : ''; ?>>
                                <?php echo e($courseOptionLabel($course)); ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>

                        <div>
                          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Type de réduction <span class="text-red-500">*</span></label>
                          <select name="promo_type" data-promo-type class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                            <option value="percent" <?php echo ($wizardDraft['promo_type'] ?? 'percent') === 'percent' ? 'selected' : ''; ?>>Pourcentage (%)</option>
                            <option value="amount" <?php echo ($wizardDraft['promo_type'] ?? '') === 'amount' ? 'selected' : ''; ?>>Montant fixe (nouveau prix)</option>
                          </select>
                        </div>

                        <div data-promo-amount class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900/50">
                          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Nouveau prix <span class="text-red-500">*</span></label>
                          <input type="number" name="promo_amount" min="0" step="1" value="<?php echo e((string) ($wizardDraft['promo_amount'] ?? '')); ?>" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        </div>
                        <div data-promo-percent class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900/50">
                          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Réduction (%) <span class="text-red-500">*</span></label>
                          <input type="number" name="promo_percent" min="1" max="100" step="0.01" value="<?php echo e((string) ($wizardDraft['promo_percent'] ?? '')); ?>" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        </div>
                      </div>
                    </div>

                    <div data-promo-preview class="rounded-2xl border <?php echo $isCouponMode ? 'border-violet-100 bg-violet-50' : 'border-sky-100 bg-sky-50'; ?> p-5 dark:border-slate-700 dark:bg-slate-900/70">
                      <div class="flex items-start justify-between gap-4">
                        <div>
                          <p class="text-sm font-semibold <?php echo $isCouponMode ? 'text-violet-900 dark:text-violet-100' : 'text-sky-900 dark:text-sky-100'; ?>">Aperçu du prix final</p>
                          <p data-promo-preview-course class="mt-2 text-sm text-slate-600 dark:text-slate-300"><?php echo e($selectedCourseLabel); ?></p>
                        </div>
                        <span data-promo-preview-badge class="inline-flex rounded-full bg-white/80 px-3 py-1 text-xs font-semibold <?php echo $isCouponMode ? 'text-violet-700 dark:bg-violet-950/40 dark:text-violet-200' : 'text-sky-700 dark:bg-sky-950/40 dark:text-sky-200'; ?>"><?php echo e($previewBadgeLabel); ?></span>
                      </div>
                      <div class="mt-4 grid gap-3 sm:grid-cols-3">
                        <div>
                          <p class="text-xs font-semibold uppercase tracking-[0.18em] <?php echo $isCouponMode ? 'text-violet-700/80 dark:text-violet-300/80' : 'text-sky-700/80 dark:text-sky-300/80'; ?>">Prix actuel</p>
                          <p data-promo-preview-current class="mt-1 text-sm font-semibold text-gray-900 dark:text-white"><?php echo e($selectedCoursePrice > 0 ? $formatMoney($selectedCoursePrice) : '—'); ?></p>
                        </div>
                        <div>
                          <p class="text-xs font-semibold uppercase tracking-[0.18em] <?php echo $isCouponMode ? 'text-violet-700/80 dark:text-violet-300/80' : 'text-sky-700/80 dark:text-sky-300/80'; ?>">Remise</p>
                          <p data-promo-preview-discount class="mt-1 text-sm font-semibold text-gray-900 dark:text-white"><?php echo e($previewDiscountLabel); ?></p>
                        </div>
                        <div>
                          <p class="text-xs font-semibold uppercase tracking-[0.18em] <?php echo $isCouponMode ? 'text-violet-700/80 dark:text-violet-300/80' : 'text-sky-700/80 dark:text-sky-300/80'; ?>">Prix final</p>
                          <p data-promo-preview-final class="mt-1 text-sm font-semibold text-gray-900 dark:text-white"><?php echo e($previewFinalPrice !== null ? $formatMoney((int) $previewFinalPrice) : '—'); ?></p>
                        </div>
                      </div>
                      <p data-promo-preview-savings class="mt-3 text-xs <?php echo $isCouponMode ? 'text-violet-700 dark:text-violet-300' : 'text-sky-700 dark:text-sky-300'; ?>"><?php echo e($previewSavingsLabel); ?></p>
                    </div>
                  </div>

                  <div class="space-y-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
                      <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Résumé</p>
                      <div class="mt-3 space-y-3">
                        <div class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/80">
                          <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Cours</p>
                          <p data-promo-summary-course class="mt-1 text-sm font-semibold text-slate-900 dark:text-white"><?php echo e($selectedCourseLabel); ?></p>
                        </div>
                        <div class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/80">
                          <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Réduction</p>
                          <p data-promo-summary-type class="mt-1 text-sm font-semibold text-slate-900 dark:text-white"><?php echo e($promoType === 'amount' ? 'Nouveau prix' : 'Pourcentage'); ?></p>
                        </div>
                        <div class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/80">
                          <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Prix final estimé</p>
                          <p data-promo-summary-final class="mt-1 text-sm font-semibold text-slate-900 dark:text-white"><?php echo e($previewFinalPrice !== null ? $formatMoney((int) $previewFinalPrice) : '—'); ?></p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="flex flex-col gap-3 border-t border-slate-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6 dark:border-slate-800">
                  <a href="<?php echo e($backUrl); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-5 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    <i class="fas fa-times"></i>
                    Annuler
                  </a>
                  <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary-600 px-5 py-3 text-sm font-semibold text-white shadow-md shadow-primary-600/20 transition hover:bg-primary-700">
                    Continuer
                    <i class="fas fa-arrow-right"></i>
                  </button>
                </div>
              </section>
            </form>
          <?php elseif ($wizardStep === 2): ?>
            <form method="post" action="<?php echo e($wizardStep2Url); ?>" class="space-y-6" data-promo-form data-promo-create-page>
              <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">

              <section class="rounded-[28px] border border-slate-200 bg-white shadow-[0_18px_60px_-40px_rgba(15,23,42,0.35)] dark:border-slate-700 dark:bg-slate-900/80">
                <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 px-5 py-5 dark:border-slate-800 sm:px-6">
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Étape 2 / 3</p>
                    <h2 class="mt-2 text-xl font-bold text-slate-950 dark:text-white">Période et ciblage</h2>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Définissez la période, puis le public visé et le code promo si nécessaire.</p>
                  </div>
                  <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold <?php echo e($heroChipClasses); ?>">
                    <i class="fas <?php echo $isCouponMode ? 'fa-ticket-alt' : 'fa-percent'; ?>"></i>
                    <?php echo $selectedCourseLabel; ?>
                  </span>
                </div>

                <div class="grid gap-6 px-5 py-5 sm:px-6 xl:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)]">
                  <div class="space-y-6">
                    <section class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5 dark:border-slate-700 dark:bg-slate-800/70">
                      <div class="flex items-start gap-3">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-600 dark:bg-amber-900/35 dark:text-amber-200">
                          <i class="fas fa-calendar-day"></i>
                        </span>
                        <div>
                          <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Période</p>
                          <h3 class="text-base font-bold text-slate-950 dark:text-white">Disponibilité</h3>
                        </div>
                      </div>
                      <div class="mt-5 space-y-4">
                        <div>
                          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Mode de période <span class="text-red-500">*</span></label>
                          <select name="promo_schedule_mode" data-promo-schedule-mode class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                            <option value="duration" <?php echo $scheduleMode === 'duration' ? 'selected' : ''; ?>>Durée en jours</option>
                            <option value="custom" <?php echo $scheduleMode === 'custom' ? 'selected' : ''; ?>>Dates exactes</option>
                          </select>
                        </div>
                        <div data-promo-schedule-duration class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900/50 <?php echo $scheduleMode === 'custom' ? 'hidden' : ''; ?>">
                          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Durée en jours <span class="text-red-500">*</span></label>
                          <input type="number" name="promo_duration_days" min="1" step="1" value="<?php echo e((string) ($wizardDraft['promo_duration_days'] ?? '')); ?>" <?php echo $scheduleMode === 'custom' ? 'disabled' : 'required'; ?> class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        </div>
                        <div data-promo-schedule-custom class="grid gap-4 md:grid-cols-2 <?php echo $scheduleMode === 'custom' ? '' : 'hidden'; ?>">
                          <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900/50">
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Date de début <span class="text-red-500">*</span></label>
                            <input type="datetime-local" name="promo_start_at" step="60" value="<?php echo e((string) ($wizardDraft['promo_start_at'] ?? '')); ?>" <?php echo $scheduleMode === 'custom' ? 'required' : 'disabled'; ?> class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                          </div>
                          <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900/50">
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Date de fin <span class="text-red-500">*</span></label>
                            <input type="datetime-local" name="promo_end_at" step="60" value="<?php echo e((string) ($wizardDraft['promo_end_at'] ?? '')); ?>" <?php echo $scheduleMode === 'custom' ? 'required' : 'disabled'; ?> class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                          </div>
                        </div>
                      </div>
                    </section>

                    <?php if ($isCouponMode): ?>
                      <section class="rounded-2xl border border-violet-200 bg-violet-50/70 p-5 shadow-sm dark:border-violet-900/35 dark:bg-violet-900/15">
                        <div class="flex items-start gap-3">
                          <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-violet-100 text-violet-600 dark:bg-violet-900/35 dark:text-violet-200">
                            <i class="fas fa-ticket-alt"></i>
                          </span>
                          <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Code promo</p>
                            <h3 class="text-base font-bold text-slate-950 dark:text-white">Mode de génération</h3>
                          </div>
                        </div>
                        <div class="mt-4 space-y-4">
                          <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Code promo</label>
                            <select name="coupon_code_mode" data-coupon-code-mode class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                              <option value="auto" <?php echo $couponMode === 'auto' ? 'selected' : ''; ?>>Générer automatiquement</option>
                              <option value="manual" <?php echo $couponMode === 'manual' ? 'selected' : ''; ?>>Saisir un code personnalisé</option>
                            </select>
                            <p class="mt-2 text-xs font-medium text-violet-600 dark:text-violet-300">Le mode automatique crée un code unique et le mode manuel vous laisse le définir.</p>
                          </div>
                          <div data-coupon-code-wrap class="rounded-xl border border-dashed border-gray-300 bg-white p-4 dark:border-gray-600 dark:bg-gray-800 <?php echo $couponMode === 'manual' ? '' : 'hidden'; ?>">
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Votre code promo</label>
                            <input
                              type="text"
                              name="coupon_code"
                              data-coupon-code-input
                              value="<?php echo e($couponCodeInputValue); ?>"
                              <?php echo $couponMode === 'manual' ? 'required' : 'disabled'; ?>
                              maxlength="32"
                              pattern="[A-Za-z0-9-]{3,32}"
                              placeholder="PROMO-2026"
                              autocomplete="off"
                              spellcheck="false"
                              class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 uppercase tracking-wide outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            >
                          </div>
                        </div>
                      </section>
                    <?php endif; ?>
                  </div>

                  <div class="space-y-6">
                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
                      <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Résumé actuel</p>
                      <h3 class="mt-2 text-lg font-bold text-slate-950 dark:text-white"><?php echo e($selectedCourseLabel); ?></h3>
                      <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/80">
                          <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Réduction</p>
                          <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white"><?php echo e($promoType === 'amount' ? 'Nouveau prix' : 'Pourcentage'); ?></p>
                        </div>
                        <div class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/80">
                          <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Prix final</p>
                          <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white"><?php echo e($previewFinalPrice !== null ? $formatMoney((int) $previewFinalPrice) : '—'); ?></p>
                        </div>
                        <div class="rounded-xl bg-slate-50 px-4 py-3 sm:col-span-2 dark:bg-slate-800/80">
                          <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Période</p>
                          <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white"><?php echo e($scheduleModeLabel . ' · ' . $scheduleSummary); ?></p>
                        </div>
                      </div>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
                      <div class="flex items-start gap-3">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600 dark:bg-emerald-900/35 dark:text-emerald-200">
                          <i class="fas fa-users"></i>
                        </span>
                        <div>
                          <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Ciblage</p>
                          <h3 class="text-base font-bold text-slate-950 dark:text-white">Public visé</h3>
                        </div>
                      </div>
                      <div class="mt-4">
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Ciblage</label>
                        <select name="promo_target_type" data-promo-target-type class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                          <option value="public" <?php echo ($wizardDraft['promo_target_type'] ?? 'public') === 'public' ? 'selected' : ''; ?>>Tout le monde</option>
                          <?php if ($canTargetPromos): ?>
                            <option value="individual" <?php echo ($wizardDraft['promo_target_type'] ?? '') === 'individual' ? 'selected' : ''; ?>>Utilisateurs ciblés</option>
                            <option value="company" <?php echo ($wizardDraft['promo_target_type'] ?? '') === 'company' ? 'selected' : ''; ?>>Entreprise</option>
                          <?php endif; ?>
                        </select>
                      </div>
                    </section>

                    <div data-promo-target-scope="public" class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/50">
                      <p class="text-sm font-semibold text-gray-900 dark:text-white">Aucun ciblage supplémentaire.</p>
                    </div>

                    <div data-promo-target-scope="individual" class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/50">
                      <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Emails ciblés</label>
                      <textarea name="promo_targets" rows="4" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100" placeholder="etudiant1@email.com, etudiant2@email.com"><?php echo e((string) ($wizardDraft['promo_targets'] ?? '')); ?></textarea>
                      <?php if ($promoTargetCandidates !== []): ?>
                        <details class="mt-3 rounded-xl border border-dashed border-gray-300 bg-white p-3 dark:border-gray-600 dark:bg-gray-800">
                          <summary class="cursor-pointer text-sm font-semibold text-gray-700 dark:text-gray-200">Utilisateurs éligibles</summary>
                          <div class="mt-3 max-h-40 space-y-2 overflow-y-auto pr-1 text-xs text-gray-600 dark:text-gray-400">
                            <?php foreach (array_slice($promoTargetCandidates, 0, 8) as $candidate): ?>
                              <?php
                                $candidateName = trim((string) (($candidate['first_name'] ?? '') . ' ' . ($candidate['last_name'] ?? '')));
                                $candidateEmail = trim((string) ($candidate['email'] ?? ''));
                                $candidateCourses = (int) ($candidate['course_count'] ?? 0);
                              ?>
                              <div class="flex items-center justify-between gap-3 rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-900/60">
                                <div>
                                  <p class="font-medium text-gray-900 dark:text-white"><?php echo e($candidateName !== '' ? $candidateName : 'Utilisateur'); ?></p>
                                  <p><?php echo e($candidateEmail !== '' ? $candidateEmail : '—'); ?></p>
                                </div>
                                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300"><?php echo e((string) $candidateCourses); ?> cours</span>
                              </div>
                            <?php endforeach; ?>
                          </div>
                        </details>
                      <?php endif; ?>
                    </div>

                    <div data-promo-target-scope="company" class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/50">
                      <div class="grid gap-4 md:grid-cols-2">
                        <div>
                          <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Entreprise cible</label>
                          <select name="promo_company_id" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                            <option value="">Sélectionnez une entreprise</option>
                            <?php foreach ($promoCompanyCandidates as $company): ?>
                              <?php $companyId = (int) ($company['id'] ?? 0); ?>
                              <option value="<?php echo e((string) $companyId); ?>" <?php echo (string) ($wizardDraft['promo_company_id'] ?? '') === (string) $companyId ? 'selected' : ''; ?>>
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
                          <input type="number" name="promo_company_seats" min="1" step="1" value="<?php echo e((string) ($wizardDraft['promo_company_seats'] ?? '')); ?>" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="flex flex-col gap-3 border-t border-slate-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6 dark:border-slate-800">
                  <a href="<?php echo e($wizardStep1Url); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-5 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    <i class="fas fa-arrow-left"></i>
                    Revenir
                  </a>
                  <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary-600 px-5 py-3 text-sm font-semibold text-white shadow-md shadow-primary-600/20 transition hover:bg-primary-700">
                    Continuer
                    <i class="fas fa-arrow-right"></i>
                  </button>
                </div>
              </section>
            </form>
          <?php else: ?>
            <form method="post" action="<?php echo e($wizardStep3Url); ?>" class="space-y-6" data-promo-form data-promo-create-page>
              <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">
              <input type="hidden" name="action" value="create_promo">
              <?php foreach ([
                'course_id',
                'promo_type',
                'promo_amount',
                'promo_percent',
                'promo_schedule_mode',
                'promo_duration_days',
                'promo_start_at',
                'promo_end_at',
                'promo_target_type',
                'promo_targets',
                'promo_company_id',
                'promo_company_seats',
                'coupon_code_mode',
                'coupon_code',
              ] as $hiddenName): ?>
                <input type="hidden" name="<?php echo e($hiddenName); ?>" value="<?php echo e((string) ($wizardDraft[$hiddenName] ?? '')); ?>">
              <?php endforeach; ?>

              <section class="rounded-[28px] border border-slate-200 bg-white shadow-[0_18px_60px_-40px_rgba(15,23,42,0.35)] dark:border-slate-700 dark:bg-slate-900/80">
                <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 px-5 py-5 dark:border-slate-800 sm:px-6">
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Étape 3 / 3</p>
                    <h2 class="mt-2 text-xl font-bold text-slate-950 dark:text-white">Récapitulatif avant validation</h2>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Vérifiez les blocs, puis validez.</p>
                  </div>
                  <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold <?php echo e($heroChipClasses); ?>">
                    <i class="fas fa-clipboard-check"></i>
                    Validation finale
                  </span>
                </div>

                <div class="space-y-5 px-5 py-5 sm:px-6">
                  <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5 dark:border-slate-700 dark:bg-slate-800/70">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                      <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Résumé actuel</p>
                        <h3 class="mt-2 text-lg font-bold text-slate-950 dark:text-white"><?php echo e($selectedCourseLabel); ?></h3>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300"><?php echo e($promoType === 'amount' ? 'Nouveau prix' : 'Réduction en pourcentage'); ?> · <?php echo e($previewSavingsLabel); ?></p>
                      </div>
                      <div class="rounded-2xl bg-white px-4 py-3 shadow-sm ring-1 ring-slate-100 dark:bg-slate-900 dark:ring-slate-700">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Prix final</p>
                        <p class="mt-1 text-xl font-extrabold text-slate-950 dark:text-white"><?php echo e($previewFinalPrice !== null ? $formatMoney((int) $previewFinalPrice) : '—'); ?></p>
                      </div>
                    </div>
                  </div>

                  <div class="grid gap-4 lg:grid-cols-2">
                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
                      <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Cours et réduction</p>
                      <dl class="mt-4 space-y-4">
                        <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3 dark:border-slate-800">
                          <dt class="text-sm text-slate-500 dark:text-slate-400">Cours</dt>
                          <dd class="text-sm font-semibold text-slate-900 dark:text-white text-right"><?php echo e($selectedCourseLabel); ?></dd>
                        </div>
                        <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3 dark:border-slate-800">
                          <dt class="text-sm text-slate-500 dark:text-slate-400">Réduction</dt>
                          <dd class="text-sm font-semibold text-slate-900 dark:text-white text-right">
                            <?php echo e($promoType === 'amount'
                                ? $formatMoney((int) $promoAmountRaw)
                                : ($compactNumber((float) $promoPercentRaw) . '%')); ?>
                          </dd>
                        </div>
                        <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3 dark:border-slate-800">
                          <dt class="text-sm text-slate-500 dark:text-slate-400">Prix actuel</dt>
                          <dd class="text-sm font-semibold text-slate-900 dark:text-white text-right"><?php echo e($selectedCoursePrice > 0 ? $formatMoney($selectedCoursePrice) : '—'); ?></dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                          <dt class="text-sm text-slate-500 dark:text-slate-400">Prix final</dt>
                          <dd class="text-sm font-semibold text-slate-900 dark:text-white text-right"><?php echo e($previewFinalPrice !== null ? $formatMoney((int) $previewFinalPrice) : '—'); ?></dd>
                        </div>
                      </dl>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
                      <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Période et ciblage</p>
                      <dl class="mt-4 space-y-4">
                        <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3 dark:border-slate-800">
                          <dt class="text-sm text-slate-500 dark:text-slate-400">Période</dt>
                          <dd class="text-sm font-semibold text-slate-900 dark:text-white text-right"><?php echo e($scheduleModeLabel); ?></dd>
                        </div>
                        <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3 dark:border-slate-800">
                          <dt class="text-sm text-slate-500 dark:text-slate-400">Disponibilité</dt>
                          <dd class="text-sm font-semibold text-slate-900 dark:text-white text-right"><?php echo e($scheduleSummary); ?></dd>
                        </div>
                        <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3 dark:border-slate-800">
                          <dt class="text-sm text-slate-500 dark:text-slate-400">Ciblage</dt>
                          <dd class="text-sm font-semibold text-slate-900 dark:text-white text-right"><?php echo e($targetLabel); ?></dd>
                        </div>
                        <div class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/80">
                          <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Détail ciblage</p>
                          <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white"><?php echo e($targetDetail); ?></p>
                        </div>
                      </dl>
                    </section>

                    <?php if ($isCouponMode): ?>
                      <section class="rounded-2xl border border-violet-200 bg-violet-50/70 p-5 shadow-sm dark:border-violet-900/35 dark:bg-violet-900/15 lg:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Code promo</p>
                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                          <div class="rounded-xl bg-white px-4 py-3 shadow-sm dark:bg-slate-900/80">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Mode</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white"><?php echo e($couponModeLabel); ?></p>
                          </div>
                          <div class="rounded-xl bg-white px-4 py-3 shadow-sm dark:bg-slate-900/80">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Valeur</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white"><?php echo e($couponValue !== '' ? $couponValue : '—'); ?></p>
                          </div>
                        </div>
                      </section>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="flex flex-col gap-3 border-t border-slate-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6 dark:border-slate-800">
                  <a href="<?php echo e($wizardStep2Url); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-5 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    <i class="fas fa-arrow-left"></i>
                    Revenir
                  </a>
                  <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary-600 px-5 py-3 text-sm font-semibold text-white shadow-md shadow-primary-600/20 transition hover:bg-primary-700">
                    Valider et créer
                    <i class="fas fa-check-circle"></i>
                  </button>
                </div>
              </section>
            </form>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </main>
  </div>
</body>
</html>
