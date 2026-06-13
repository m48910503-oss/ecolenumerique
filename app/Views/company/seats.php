<?php
declare(strict_types=1);

$companySimplePage = 'seats';
$companySimpleTitle = 'Formations / Places';
$companySimpleSubtitle = 'Attribuez les places achetées aux collaborateurs actifs. Une place = un collaborateur = une formation.';
$seatCourseRows = is_array($seatCourseRows ?? null) ? $seatCourseRows : [];
$seatSummary = is_array($seatSummary ?? null) ? $seatSummary : [];
$seatMemberOptions = is_array($seatMemberOptions ?? null) ? $seatMemberOptions : [];
$csrfToken = function_exists('view_csrf_token') ? view_csrf_token() : '';
$money = static fn (int $amount): string => number_format($amount, 0, ',', ' ') . ' F CFA';
require __DIR__ . '/_simple_shell_start.php';
require __DIR__ . '/_simple_flash.php';
?>

<div class="student-dashboard-stats-grid grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 sm:gap-5">
  <?php
  $cards = [
      ['label' => 'Formations achetées', 'value' => (int) ($seatSummary['courses'] ?? 0), 'icon' => 'book-open'],
      ['label' => 'Places achetées', 'value' => (int) ($seatSummary['seats_total'] ?? 0), 'icon' => 'ticket'],
      ['label' => 'Attribuées', 'value' => (int) ($seatSummary['seats_assigned'] ?? 0), 'icon' => 'user-check'],
      ['label' => 'Disponibles', 'value' => (int) ($seatSummary['seats_available'] ?? 0), 'icon' => 'circle-plus'],
  ];
  ?>
  <?php foreach ($cards as $card): ?>
    <article class="rounded-2xl border border-gray-100 bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo e($card['label']); ?></p>
          <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"><?php echo e((string) $card['value']); ?></p>
        </div>
        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300">
          <i class="fas fa-<?php echo e($card['icon']); ?>"></i>
        </div>
      </div>
    </article>
  <?php endforeach; ?>
</div>

<?php if ($seatCourseRows === []): ?>
  <section class="rounded-2xl border border-gray-100 bg-white p-6 text-center shadow-card dark:border-gray-700 dark:bg-gray-800">
    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-100 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300">
      <i class="fas fa-shopping-cart"></i>
    </div>
    <h3 class="mt-4 text-lg font-bold text-gray-900 dark:text-white">Aucune place entreprise achetée</h3>
    <p class="mx-auto mt-2 max-w-xl text-sm text-gray-500 dark:text-gray-400">Achetez en mode entreprise, puis choisissez les places.</p>
    <a href="<?php echo e(site_url('catalogue')); ?>" class="mt-5 inline-flex rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">Aller au catalogue</a>
  </section>
<?php endif; ?>

<section class="space-y-4">
  <?php foreach ($seatCourseRows as $course): ?>
    <?php
    $available = max(0, (int) ($course['seats_available'] ?? 0));
    $assignedUsers = is_array($course['assigned_users'] ?? null) ? $course['assigned_users'] : [];
    ?>
    <article class="rounded-2xl border border-gray-100 bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
          <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-600 dark:text-blue-300">Formation</p>
          <h3 class="mt-1 text-lg font-bold text-gray-900 dark:text-white"><?php echo e((string) ($course['course_title'] ?? 'Cours')); ?></h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            <?php echo e((string) ((int) ($course['seats_assigned'] ?? 0))); ?> attribuées sur <?php echo e((string) ((int) ($course['seats_total'] ?? 0))); ?> places.
            Total acheté : <?php echo e($money((int) ($course['total_xof'] ?? 0))); ?>.
          </p>
        </div>
        <span class="rounded-full px-3 py-1 text-xs font-semibold <?php echo $available > 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300'; ?>">
          <?php echo e((string) $available); ?> disponible<?php echo $available > 1 ? 's' : ''; ?>
        </span>
      </div>

      <div class="mt-5 grid gap-4 lg:grid-cols-[0.85fr,1.15fr]">
        <div class="rounded-2xl bg-gray-50 p-4 dark:bg-gray-900/40">
          <h4 class="font-semibold text-gray-900 dark:text-white">Attribuer une place</h4>
          <?php if ($available < 1): ?>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Toutes les places de cette formation sont déjà attribuées.</p>
          <?php elseif ($seatMemberOptions === []): ?>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Invitez d’abord un collaborateur actif.</p>
          <?php else: ?>
            <form method="post" class="mt-3 space-y-3">
              <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">
              <input type="hidden" name="action" value="assign_course_seat">
              <input type="hidden" name="course_id" value="<?php echo e((string) ((int) ($course['course_id'] ?? 0))); ?>">
              <select name="member_user_id" required class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-blue-500/20">
                <option value="">Choisir un collaborateur</option>
                <?php foreach ($seatMemberOptions as $member): ?>
                  <option value="<?php echo e((string) ((int) ($member['user_id'] ?? 0))); ?>"><?php echo e((string) ($member['label'] ?? 'Collaborateur')); ?> - <?php echo e((string) ($member['email'] ?? '')); ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">Attribuer</button>
            </form>
          <?php endif; ?>
        </div>

        <div class="rounded-2xl bg-gray-50 p-4 dark:bg-gray-900/40">
          <h4 class="font-semibold text-gray-900 dark:text-white">Places déjà attribuées</h4>
          <div class="mt-3 space-y-2">
            <?php foreach ($assignedUsers as $assigned): ?>
              <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-white p-3 dark:bg-gray-800">
                <div class="min-w-0">
                  <p class="truncate font-medium text-gray-900 dark:text-white"><?php echo e((string) ($assigned['full_name'] ?? 'Collaborateur')); ?></p>
                  <p class="truncate text-xs text-gray-500 dark:text-gray-400"><?php echo e((string) ($assigned['email'] ?? '')); ?> · <?php echo e((string) ($assigned['assigned_at_label'] ?? '-')); ?></p>
                </div>
                <form method="post" onsubmit="return confirm('Retirer cette place ?');">
                  <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">
                  <input type="hidden" name="action" value="revoke_course_seat">
                  <input type="hidden" name="course_id" value="<?php echo e((string) ((int) ($course['course_id'] ?? 0))); ?>">
                  <input type="hidden" name="member_user_id" value="<?php echo e((string) ((int) ($assigned['member_user_id'] ?? 0))); ?>">
                  <button type="submit" class="rounded-xl bg-red-50 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-100 dark:bg-red-500/10 dark:text-red-300">Retirer</button>
                </form>
              </div>
            <?php endforeach; ?>
            <?php if ($assignedUsers === []): ?>
              <p class="text-sm text-gray-500 dark:text-gray-400">Aucune place attribuée pour cette formation.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </article>
  <?php endforeach; ?>
</section>

<?php require __DIR__ . '/_simple_shell_end.php'; ?>
