<?php
declare(strict_types=1);

$errors = is_array($errors ?? null) ? $errors : [];
$successMessage = trim((string) ($successMessage ?? ''));
$inviteLink = trim((string) ($inviteLink ?? ''));
?>
<?php if ($errors !== []): ?>
  <section class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200">
    <?php foreach ($errors as $error): ?>
      <p><?php echo e((string) $error); ?></p>
    <?php endforeach; ?>
  </section>
<?php endif; ?>

<?php if ($successMessage !== ''): ?>
  <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-200">
    <p><?php echo e($successMessage); ?></p>
    <?php if ($inviteLink !== ''): ?>
      <div class="mt-3 flex flex-col gap-2 sm:flex-row">
        <input id="companyInviteLink" type="text" value="<?php echo e($inviteLink); ?>" readonly class="min-w-0 flex-1 rounded-xl border border-emerald-200 bg-white px-3 py-2 text-xs text-gray-700 outline-none dark:border-emerald-900/50 dark:bg-gray-900 dark:text-gray-100">
        <button type="button" onclick="navigator.clipboard && navigator.clipboard.writeText(document.getElementById('companyInviteLink').value)" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700">Copier le lien</button>
      </div>
    <?php endif; ?>
  </section>
<?php endif; ?>
