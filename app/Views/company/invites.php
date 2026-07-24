<?php
declare(strict_types=1);

$companySimplePage = 'invites';
$companySimpleTitle = 'Invitations';
$companySimpleSubtitle = 'Invitez par e-mail, puis attribuez une place.';
$inviteRows = is_array($inviteRows ?? null) ? $inviteRows : [];
$inviteSummary = is_array($inviteSummary ?? null) ? $inviteSummary : [];
$csrfToken = function_exists('view_csrf_token') ? view_csrf_token() : '';
require __DIR__ . '/_simple_shell_start.php';
require __DIR__ . '/_simple_flash.php';
?>

<section class="grid grid-cols-1 gap-4 lg:grid-cols-[0.8fr,1.2fr] sm:gap-6">
  <article id="invite-form" class="rounded-2xl border border-gray-100 bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800">
    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-600 dark:text-blue-300">Nouvelle invitation</p>
    <h3 class="app-inline-title mt-1 text-lg font-bold text-gray-900 dark:text-white"><?php echo dashboard_title_icon('user-plus'); ?><span>Ajouter un collaborateur</span></h3>
    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Invitez un membre, puis attribuez ses formations depuis Places.</p>
    <form method="post" class="mt-5 space-y-4">
      <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">
      <input type="hidden" name="action" value="invite_member">
      <label class="block">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">E-mail du collaborateur</span>
        <input type="email" name="email" required placeholder="collaborateur@entreprise.com" class="mt-1 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-blue-500/20">
      </label>
      <button type="submit" class="w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">Envoyer l’invitation</button>
    </form>
  </article>

  <article class="rounded-2xl border border-gray-100 bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800">
    <h3 class="app-inline-title text-lg font-bold text-gray-900 dark:text-white"><?php echo dashboard_title_icon('bell'); ?><span>Statuts des invitations</span></h3>
    <div class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-4">
      <?php
      $summaryCards = [
          ['label' => 'En attente', 'value' => (int) ($inviteSummary['pending'] ?? 0)],
          ['label' => 'Acceptées', 'value' => (int) ($inviteSummary['accepted'] ?? 0)],
          ['label' => 'Expirées', 'value' => (int) ($inviteSummary['expired'] ?? 0)],
          ['label' => 'Annulées', 'value' => (int) ($inviteSummary['revoked'] ?? 0)],
      ];
      ?>
      <?php foreach ($summaryCards as $card): ?>
        <div class="rounded-2xl bg-gray-50 p-4 dark:bg-gray-900/40">
          <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo e($card['label']); ?></p>
          <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"><?php echo e((string) $card['value']); ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </article>
</section>

<section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800">
  <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div>
      <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-600 dark:text-blue-300">Historique</p>
      <h3 class="app-inline-title text-lg font-bold text-gray-900 dark:text-white"><?php echo dashboard_title_icon('mail'); ?><span>Invitations envoyées</span></h3>
    </div>
  </div>

  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-100 text-sm dark:divide-gray-700">
      <thead>
        <tr class="text-left text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
          <th class="px-3 py-3">E-mail</th>
          <th class="px-3 py-3">Statut</th>
          <th class="px-3 py-3">Envoyée</th>
          <th class="px-3 py-3">Expiration</th>
          <th class="px-3 py-3 text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
        <?php foreach ($inviteRows as $invite): ?>
          <?php $status = (string) ($invite['status'] ?? 'pending'); ?>
          <tr>
            <td class="px-3 py-4 font-medium text-gray-900 dark:text-white"><?php echo e((string) ($invite['email'] ?? '')); ?></td>
            <td class="px-3 py-4">
              <span class="rounded-full px-3 py-1 text-xs font-semibold <?php echo $status === 'accepted' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : ($status === 'pending' ? 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300'); ?>">
                <?php echo e((string) ($invite['status_label'] ?? 'En attente')); ?>
              </span>
            </td>
            <td class="px-3 py-4 text-gray-500 dark:text-gray-400"><?php echo e((string) ($invite['created_at_label'] ?? '-')); ?></td>
            <td class="px-3 py-4 text-gray-500 dark:text-gray-400"><?php echo e((string) ($invite['expires_at_label'] ?? '-')); ?></td>
            <td class="px-3 py-4">
              <?php if (!empty($invite['can_manage'])): ?>
                <div class="flex justify-end gap-2">
                  <form method="post">
                    <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">
                    <input type="hidden" name="action" value="resend_invite">
                    <input type="hidden" name="invite_id" value="<?php echo e((string) ((int) ($invite['id'] ?? 0))); ?>">
                    <button type="submit" class="rounded-xl bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-100 dark:bg-blue-500/10 dark:text-blue-300">Renvoyer</button>
                  </form>
                  <form method="post">
                    <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">
                    <input type="hidden" name="action" value="revoke_invite">
                    <input type="hidden" name="invite_id" value="<?php echo e((string) ((int) ($invite['id'] ?? 0))); ?>">
                    <button type="submit" class="rounded-xl bg-red-50 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-100 dark:bg-red-500/10 dark:text-red-300">Annuler</button>
                  </form>
                </div>
              <?php else: ?>
                <span class="block text-right text-xs text-gray-400">-</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($inviteRows === []): ?>
    <p class="mt-4 rounded-2xl bg-gray-50 p-4 text-sm text-gray-500 dark:bg-gray-900/40 dark:text-gray-400">Aucune invitation envoyée.</p>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/_simple_shell_end.php'; ?>
