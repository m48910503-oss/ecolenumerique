<?php
declare(strict_types=1);

$companySimplePage = 'billing';
$companySimpleTitle = 'Factures';
$companySimpleSubtitle = 'Consultez les achats effectués en mode entreprise et le nombre de places achetées.';
$billingRows = is_array($billingRows ?? null) ? $billingRows : [];
$billingSummary = is_array($billingSummary ?? null) ? $billingSummary : [];
$seatSummary = is_array($seatSummary ?? null) ? $seatSummary : [];
$money = static fn (int $amount): string => number_format($amount, 0, ',', ' ') . ' F CFA';
require __DIR__ . '/_simple_shell_start.php';
require __DIR__ . '/_simple_flash.php';
?>

<div class="student-dashboard-stats-grid grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-5">
  <article class="rounded-2xl border border-gray-100 bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800">
    <p class="text-sm text-gray-500 dark:text-gray-400">Commandes</p>
    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"><?php echo e((string) ((int) ($billingSummary['orders'] ?? 0))); ?></p>
  </article>
  <article class="rounded-2xl border border-gray-100 bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800">
    <p class="text-sm text-gray-500 dark:text-gray-400">Places achetées</p>
    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"><?php echo e((string) ((int) ($billingSummary['seats_total'] ?? 0))); ?></p>
  </article>
  <article class="rounded-2xl border border-gray-100 bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800">
    <p class="text-sm text-gray-500 dark:text-gray-400">Montant total</p>
    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"><?php echo e($money((int) ($billingSummary['total_xof'] ?? 0))); ?></p>
  </article>
</div>

<section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800">
  <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div>
      <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-600 dark:text-blue-300">Historique</p>
      <h3 class="text-lg font-bold text-gray-900 dark:text-white">Achats entreprise</h3>
    </div>
    <a href="<?php echo e(site_url('catalogue')); ?>" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">Acheter des places</a>
  </div>

  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-100 text-sm dark:divide-gray-700">
      <thead>
        <tr class="text-left text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
          <th class="px-3 py-3">Commande</th>
          <th class="px-3 py-3">Cours</th>
          <th class="px-3 py-3">Places</th>
          <th class="px-3 py-3">Montant</th>
          <th class="px-3 py-3">Statut</th>
          <th class="px-3 py-3">Date</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
        <?php foreach ($billingRows as $row): ?>
          <?php
          $status = trim((string) ($row['payment_status'] ?? ''));
          if ($status === '') {
              $status = trim((string) ($row['order_status'] ?? ''));
          }
          $statusLabel = $status !== '' ? ucfirst($status) : '-';
          ?>
          <tr>
            <td class="px-3 py-4 font-semibold text-gray-900 dark:text-white"><?php echo e((string) ($row['order_reference'] ?? '-')); ?></td>
            <td class="px-3 py-4 text-gray-600 dark:text-gray-300"><?php echo e((string) ($row['course_title'] ?? 'Cours')); ?></td>
            <td class="px-3 py-4 text-gray-600 dark:text-gray-300"><?php echo e((string) ((int) ($row['seats_total'] ?? 0))); ?></td>
            <td class="px-3 py-4 text-gray-600 dark:text-gray-300"><?php echo e($money((int) ($row['total_xof'] ?? 0))); ?></td>
            <td class="px-3 py-4">
              <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-200"><?php echo e($statusLabel); ?></span>
            </td>
            <td class="px-3 py-4 text-gray-500 dark:text-gray-400"><?php echo e((string) ($row['created_at_label'] ?? '-')); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($billingRows === []): ?>
    <p class="mt-4 rounded-2xl bg-gray-50 p-4 text-sm text-gray-500 dark:bg-gray-900/40 dark:text-gray-400">Aucun achat entreprise pour le moment.</p>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/_simple_shell_end.php'; ?>
