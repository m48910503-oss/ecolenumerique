<?php
declare(strict_types=1);

$companySimplePage = 'members';
$companySimpleTitle = 'Collaborateurs';
$companySimpleSubtitle = 'Gérez l’équipe et les accès.';
$memberRows = is_array($memberRows ?? null) ? $memberRows : [];
$memberSummary = is_array($memberSummary ?? null) ? $memberSummary : [];
$csrfToken = function_exists('view_csrf_token') ? view_csrf_token() : '';
require __DIR__ . '/_simple_shell_start.php';
require __DIR__ . '/_simple_flash.php';
?>

<div class="student-dashboard-stats-grid grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-5">
  <?php
  $cards = [
      ['label' => 'Total', 'value' => (int) ($memberSummary['total'] ?? 0), 'icon' => 'users'],
      ['label' => 'Actifs', 'value' => (int) ($memberSummary['active'] ?? 0), 'icon' => 'check-circle'],
      ['label' => 'Bloqués', 'value' => (int) ($memberSummary['blocked'] ?? 0), 'icon' => 'ban'],
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

<section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800">
  <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div>
      <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-600 dark:text-blue-300">Equipe</p>
      <h3 class="app-inline-title text-lg font-bold text-gray-900 dark:text-white"><?php echo dashboard_title_icon('users'); ?><span>Liste des collaborateurs</span></h3>
    </div>
    <a href="<?php echo e(site_url('company/invites#invite-form')); ?>" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">Inviter</a>
  </div>

  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-100 text-sm dark:divide-gray-700">
      <thead>
        <tr class="text-left text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
          <th class="px-3 py-3">Collaborateur</th>
          <th class="px-3 py-3">Role</th>
          <th class="px-3 py-3">Statut</th>
          <th class="px-3 py-3">Arrivee</th>
          <th class="px-3 py-3 text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
        <?php foreach ($memberRows as $member): ?>
          <?php $isActive = (string) ($member['status'] ?? '') === 'active'; ?>
          <tr>
            <td class="px-3 py-4">
              <p class="font-semibold text-gray-900 dark:text-white"><?php echo e((string) ($member['full_name'] ?? 'Collaborateur')); ?></p>
              <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo e((string) ($member['email'] ?? '')); ?></p>
            </td>
            <td class="px-3 py-4 text-gray-600 dark:text-gray-300"><?php echo e((string) ($member['role_label'] ?? 'Collaborateur')); ?></td>
            <td class="px-3 py-4">
              <span class="rounded-full px-3 py-1 text-xs font-semibold <?php echo $isActive ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300'; ?>">
                <?php echo e((string) ($member['status_label'] ?? '')); ?>
              </span>
            </td>
            <td class="px-3 py-4 text-gray-500 dark:text-gray-400"><?php echo e((string) ($member['joined_at_label'] ?? '-')); ?></td>
            <td class="px-3 py-4">
              <?php if (($member['role'] ?? '') === 'owner'): ?>
                <span class="block text-right text-xs text-gray-400">Propriétaire</span>
              <?php else: ?>
                <div class="flex justify-end gap-2">
                  <form method="post">
                    <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">
                    <input type="hidden" name="action" value="set_member_status">
                    <input type="hidden" name="member_id" value="<?php echo e((string) ((int) ($member['id'] ?? 0))); ?>">
                    <input type="hidden" name="status" value="<?php echo $isActive ? 'blocked' : 'active'; ?>">
                    <button type="submit" class="rounded-xl bg-gray-100 px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200"><?php echo $isActive ? 'Bloquer' : 'Reactiver'; ?></button>
                  </form>
                  <form method="post" onsubmit="return confirm('Retirer ce collaborateur de l’entreprise ?');">
                    <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">
                    <input type="hidden" name="action" value="remove_member">
                    <input type="hidden" name="member_id" value="<?php echo e((string) ((int) ($member['id'] ?? 0))); ?>">
                    <button type="submit" class="rounded-xl bg-red-50 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-100 dark:bg-red-500/10 dark:text-red-300">Retirer</button>
                  </form>
                </div>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($memberRows === []): ?>
    <p class="mt-4 rounded-2xl bg-gray-50 p-4 text-sm text-gray-500 dark:bg-gray-900/40 dark:text-gray-400">Aucun collaborateur pour le moment.</p>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/_simple_shell_end.php'; ?>
