<?php
$teacherWelcomeUser = is_array($teacherWelcomeUser ?? null)
    ? $teacherWelcomeUser
    : (is_array($teacherUser ?? null)
        ? $teacherUser
        : (is_array($dashboardUser ?? null)
            ? $dashboardUser
            : (is_array($user ?? null) ? $user : [])));

$teacherWelcomeDisplayName = function_exists('dashboard_user_display_name')
    ? dashboard_user_display_name($teacherWelcomeUser, 'Enseignant')
    : trim((string) (($teacherWelcomeUser['first_name'] ?? '') . ' ' . ($teacherWelcomeUser['last_name'] ?? '')));
$teacherWelcomeDisplayName = $teacherWelcomeDisplayName !== '' ? $teacherWelcomeDisplayName : 'Enseignant';
$teacherWelcomeEmail = trim((string) ($teacherWelcomeUser['email'] ?? ''));
$teacherWelcomeFirstName = trim((string) ($teacherWelcomeUser['first_name'] ?? ''));

if ($teacherWelcomeFirstName === '') {
    $teacherWelcomeParts = preg_split('/\s+/', $teacherWelcomeDisplayName) ?: [];
    $teacherWelcomeCandidate = trim((string) ($teacherWelcomeParts[0] ?? ''));

    if ($teacherWelcomeCandidate === '' && $teacherWelcomeEmail !== '') {
        $teacherWelcomePrefix = trim((string) explode('@', $teacherWelcomeEmail, 2)[0]);
        $teacherWelcomePrefix = str_replace(['.', '_', '-'], ' ', $teacherWelcomePrefix);
        $teacherWelcomePrefixParts = preg_split('/\s+/', $teacherWelcomePrefix) ?: [];
        $teacherWelcomeCandidate = trim((string) ($teacherWelcomePrefixParts[0] ?? ''));
    }

    $teacherWelcomeFirstName = $teacherWelcomeCandidate !== '' ? $teacherWelcomeCandidate : 'Enseignant';
}

$teacherWelcomeTitle = trim((string) ($teacherWelcomeTitle ?? ''));
if ($teacherWelcomeTitle === '') {
    $teacherWelcomeTitle = 'Bon retour, ' . $teacherWelcomeFirstName . ' !';
}

$teacherWelcomeSummary = trim((string) ($teacherWelcomeSummary ?? ''));
$teacherWelcomeActionType = trim((string) ($teacherWelcomeActionType ?? 'link'));
if (!in_array($teacherWelcomeActionType, ['button', 'link'], true)) {
    $teacherWelcomeActionType = 'link';
}

$teacherWelcomeActionLabel = trim((string) ($teacherWelcomeActionLabel ?? ''));
$teacherWelcomeActionUrl = trim((string) ($teacherWelcomeActionUrl ?? '#'));
$teacherWelcomeActionIcon = trim((string) ($teacherWelcomeActionIcon ?? 'book-open'));
$teacherWelcomeActionAttrs = trim((string) ($teacherWelcomeActionAttrs ?? ''));
?>
<section class="teacher-dashboard-welcome student-dashboard-hero rounded-[1.5rem] bg-gradient-to-r from-blue-600 via-blue-500 to-cyan-500 p-4 text-white shadow-glow sm:rounded-2xl sm:p-6">
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div class="min-w-0">
      <h2 class="text-xl font-bold sm:text-2xl"><?php echo e($teacherWelcomeTitle); ?></h2>
      <?php if ($teacherWelcomeSummary !== ''): ?>
        <p class="mt-1 text-sm text-blue-100 sm:text-base"><?php echo e($teacherWelcomeSummary); ?></p>
      <?php endif; ?>
    </div>
    <?php if ($teacherWelcomeActionLabel !== ''): ?>
      <?php if ($teacherWelcomeActionType === 'button'): ?>
        <button type="button" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-semibold text-blue-700 shadow-sm transition hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-white/70"<?php echo $teacherWelcomeActionAttrs !== '' ? ' ' . $teacherWelcomeActionAttrs : ''; ?>>
          <?php if ($teacherWelcomeActionIcon !== ''): ?>
            <i class="<?php echo e(dashboard_icon_fa_class($teacherWelcomeActionIcon)); ?>" aria-hidden="true"></i>
          <?php endif; ?>
          <span><?php echo e($teacherWelcomeActionLabel); ?></span>
        </button>
      <?php else: ?>
        <a href="<?php echo e($teacherWelcomeActionUrl); ?>" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-semibold text-blue-700 shadow-sm transition hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-white/70"<?php echo $teacherWelcomeActionAttrs !== '' ? ' ' . $teacherWelcomeActionAttrs : ''; ?>>
          <?php if ($teacherWelcomeActionIcon !== ''): ?>
            <i class="<?php echo e(dashboard_icon_fa_class($teacherWelcomeActionIcon)); ?>" aria-hidden="true"></i>
          <?php endif; ?>
          <span><?php echo e($teacherWelcomeActionLabel); ?></span>
        </a>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
