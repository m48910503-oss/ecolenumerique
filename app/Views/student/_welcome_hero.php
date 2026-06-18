<?php
$studentWelcomeUser = is_array($studentWelcomeUser ?? null)
    ? $studentWelcomeUser
    : (is_array($dashboardUser ?? null)
        ? $dashboardUser
        : (is_array($studentUser ?? null)
            ? $studentUser
            : (is_array($user ?? null) ? $user : [])));

$studentWelcomeDisplayName = function_exists('dashboard_user_display_name')
    ? dashboard_user_display_name($studentWelcomeUser, 'Utilisateur')
    : 'Utilisateur';
$studentWelcomeEmail = trim((string) ($studentWelcomeUser['email'] ?? ''));
$studentWelcomeFirstName = trim((string) ($studentWelcomeUser['first_name'] ?? ''));

if ($studentWelcomeFirstName === '') {
    $studentWelcomeParts = preg_split('/\s+/', $studentWelcomeDisplayName) ?: [];
    $studentWelcomeCandidate = trim((string) ($studentWelcomeParts[0] ?? ''));

    if ($studentWelcomeCandidate === '' && $studentWelcomeEmail !== '') {
        $studentWelcomePrefix = trim((string) explode('@', $studentWelcomeEmail, 2)[0]);
        $studentWelcomePrefix = str_replace(['.', '_', '-'], ' ', $studentWelcomePrefix);
        $studentWelcomePrefixParts = preg_split('/\s+/', $studentWelcomePrefix) ?: [];
        $studentWelcomeCandidate = trim((string) ($studentWelcomePrefixParts[0] ?? ''));
    }

    $studentWelcomeFirstName = $studentWelcomeCandidate !== '' ? $studentWelcomeCandidate : 'Etudiant';
}

$studentWelcomeTitle = trim((string) ($studentWelcomeTitle ?? ''));
if ($studentWelcomeTitle === '') {
    $studentWelcomeTitle = 'Bon retour, ' . $studentWelcomeFirstName . ' !';
}

$studentWelcomeSummary = trim((string) ($studentWelcomeSummary ?? ''));
$studentWelcomeActionType = trim((string) ($studentWelcomeActionType ?? 'link'));
if (!in_array($studentWelcomeActionType, ['button', 'link'], true)) {
    $studentWelcomeActionType = 'link';
}

$studentWelcomeActionLabel = trim((string) ($studentWelcomeActionLabel ?? ''));
$studentWelcomeActionUrl = trim((string) ($studentWelcomeActionUrl ?? '#'));
$studentWelcomeActionIcon = trim((string) ($studentWelcomeActionIcon ?? 'book-open'));
$studentWelcomeActionAttrs = trim((string) ($studentWelcomeActionAttrs ?? ''));
?>
<section class="student-dashboard-hero rounded-[1.5rem] bg-gradient-to-r from-blue-600 via-blue-500 to-cyan-500 p-4 text-white shadow-glow sm:rounded-2xl sm:p-6">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div class="min-w-0">
      <h2 class="text-xl font-bold sm:text-2xl"><?php echo e($studentWelcomeTitle); ?></h2>
      <?php if ($studentWelcomeSummary !== ''): ?>
        <p class="mt-1 text-sm text-blue-100 sm:text-base"><?php echo e($studentWelcomeSummary); ?></p>
      <?php endif; ?>
    </div>
    <?php if ($studentWelcomeActionLabel !== ''): ?>
      <?php if ($studentWelcomeActionType === 'button'): ?>
        <button type="button" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-semibold text-blue-700 shadow-sm transition hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-white/70"<?php echo $studentWelcomeActionAttrs !== '' ? ' ' . $studentWelcomeActionAttrs : ''; ?>>
          <?php if ($studentWelcomeActionIcon !== ''): ?>
            <i class="<?php echo e(dashboard_icon_fa_class($studentWelcomeActionIcon)); ?>" aria-hidden="true"></i>
          <?php endif; ?>
          <span><?php echo e($studentWelcomeActionLabel); ?></span>
        </button>
      <?php else: ?>
        <a href="<?php echo e($studentWelcomeActionUrl); ?>" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-semibold text-blue-700 shadow-sm transition hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-white/70"<?php echo $studentWelcomeActionAttrs !== '' ? ' ' . $studentWelcomeActionAttrs : ''; ?>>
          <?php if ($studentWelcomeActionIcon !== ''): ?>
            <i class="<?php echo e(dashboard_icon_fa_class($studentWelcomeActionIcon)); ?>" aria-hidden="true"></i>
          <?php endif; ?>
          <span><?php echo e($studentWelcomeActionLabel); ?></span>
        </a>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
