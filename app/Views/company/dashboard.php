<?php
declare(strict_types=1);

$companySimplePage = 'dashboard';
$companySimpleTitle = 'Tableau de bord entreprise';
$companySimpleSubtitle = 'Invitez, achetez des places, attribuez-les et suivez les accès.';
$memberSummary = is_array($memberSummary ?? null) ? $memberSummary : [];
$inviteSummary = is_array($inviteSummary ?? null) ? $inviteSummary : [];
$seatSummary = is_array($seatSummary ?? null) ? $seatSummary : [];
$billingSummary = is_array($billingSummary ?? null) ? $billingSummary : [];
$memberRows = is_array($memberRows ?? null) ? $memberRows : [];
$inviteRows = is_array($inviteRows ?? null) ? $inviteRows : [];
$seatCourseRows = is_array($seatCourseRows ?? null) ? $seatCourseRows : [];
$csrfToken = function_exists('view_csrf_token') ? view_csrf_token() : '';
$money = static fn (int $amount): string => number_format($amount, 0, ',', ' ') . ' F CFA';
require __DIR__ . '/_simple_shell_start.php';
require __DIR__ . '/_simple_flash.php';
?>

<?php if (!$company): ?>
  <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800">
    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Entreprise introuvable</h3>
    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Votre compte n’est pas encore rattaché à une entreprise active.</p>
    <a href="<?php echo e(site_url('company/register')); ?>" class="mt-4 inline-flex rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">Créer une entreprise</a>
  </section>
<?php else: ?>
  <div class="student-dashboard-stats-grid grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 sm:gap-5">
    <?php
    $stats = [
        ['label' => 'Collaborateurs actifs', 'value' => (int) ($memberSummary['active'] ?? 0), 'note' => (int) ($memberSummary['total'] ?? 0) . ' au total', 'icon' => 'users', 'class' => 'bg-blue-100 dark:bg-blue-500/10 text-blue-600 dark:text-blue-300'],
        ['label' => 'Invitations en attente', 'value' => (int) ($inviteSummary['pending'] ?? 0), 'note' => (int) ($inviteSummary['accepted'] ?? 0) . ' acceptées', 'icon' => 'envelope', 'class' => 'bg-cyan-100 dark:bg-cyan-500/10 text-cyan-600 dark:text-cyan-300'],
        ['label' => 'Places disponibles', 'value' => (int) ($seatSummary['seats_available'] ?? 0), 'note' => (int) ($seatSummary['seats_assigned'] ?? 0) . ' attribuées', 'icon' => 'book-open', 'class' => 'bg-green-100 dark:bg-green-900/30 text-green-600'],
        ['label' => 'Achats entreprise', 'value' => $money((int) ($billingSummary['total_xof'] ?? 0)), 'note' => (int) ($billingSummary['orders'] ?? 0) . ' commande(s)', 'icon' => 'receipt', 'class' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-600'],
    ];
    ?>
    <?php foreach ($stats as $stat): ?>
      <article class="student-dashboard-stat-card bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-card border border-gray-100 dark:border-gray-700 hover-lift sm:p-5">
        <div class="flex items-center justify-between gap-3">
          <div class="min-w-0">
            <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo e((string) $stat['label']); ?></p>
            <p class="mt-1 truncate text-xl font-bold text-gray-900 dark:text-white sm:text-2xl"><?php echo e((string) $stat['value']); ?></p>
          </div>
          <div class="flex h-10 w-10 items-center justify-center rounded-xl <?php echo e((string) $stat['class']); ?>">
            <i class="fas fa-<?php echo e((string) $stat['icon']); ?>"></i>
          </div>
        </div>
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400"><?php echo e((string) $stat['note']); ?></p>
      </article>
    <?php endforeach; ?>
  </div>

  <section class="grid grid-cols-1 gap-4 lg:grid-cols-3 sm:gap-6">
    <article class="lg:col-span-2 rounded-2xl border border-gray-100 bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-600 dark:text-blue-300">Procédure simple</p>
          <h3 class="app-inline-title mt-1 text-lg font-bold text-gray-900 dark:text-white"><?php echo dashboard_title_icon('building'); ?><span>Comment utiliser le compte entreprise</span></h3>
        </div>
        <a href="<?php echo e(site_url('catalogue')); ?>" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">Acheter des places</a>
      </div>
      <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <?php
        $steps = [
            ['title' => '1. Inviter', 'text' => 'Ajoutez les collaborateurs par e-mail.'],
            ['title' => '2. Acheter', 'text' => 'Choisissez un cours et le nombre de places.'],
            ['title' => '3. Attribuer', 'text' => 'Donnez une place disponible à un collaborateur.'],
            ['title' => '4. Suivre', 'text' => 'Le collaborateur voit le cours dans son espace.'],
        ];
        ?>
        <?php foreach ($steps as $step): ?>
          <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
            <p class="font-semibold text-gray-900 dark:text-white"><?php echo e($step['title']); ?></p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?php echo e($step['text']); ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </article>

    <article class="rounded-2xl border border-gray-100 bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800">
      <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-600 dark:text-blue-300">Invitation rapide</p>
      <h3 class="app-inline-title mt-1 text-lg font-bold text-gray-900 dark:text-white"><?php echo dashboard_title_icon('user-plus'); ?><span>Inviter un collaborateur</span></h3>
      <form method="post" class="mt-4 space-y-3">
        <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">
        <input type="hidden" name="action" value="invite_member">
        <label class="block">
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300">E-mail professionnel</span>
          <input type="email" name="email" required placeholder="paul@entreprise.com" class="mt-1 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-blue-500/20">
        </label>
        <button type="submit" class="w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">Envoyer l’invitation</button>
      </form>
      <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">L’invitation rattache la personne. La formation s’attribue ensuite.</p>
    </article>
  </section>

  <section class="grid grid-cols-1 gap-4 lg:grid-cols-2 sm:gap-6">
    <article class="rounded-2xl border border-gray-100 bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800">
      <div class="mb-4 flex items-center justify-between gap-3">
        <h3 class="app-inline-title font-bold text-gray-900 dark:text-white"><?php echo dashboard_title_icon('users'); ?><span>Collaborateurs récents</span></h3>
        <a href="<?php echo e(site_url('company/members')); ?>" class="text-sm font-semibold text-blue-600 hover:underline">Voir tout</a>
      </div>
      <div class="space-y-3">
        <?php foreach (array_slice($memberRows, 0, 5) as $member): ?>
          <div class="flex items-center justify-between gap-3 rounded-2xl bg-gray-50 p-3 dark:bg-gray-900/40">
            <div class="min-w-0">
              <p class="truncate font-semibold text-gray-900 dark:text-white"><?php echo e((string) ($member['full_name'] ?? 'Collaborateur')); ?></p>
              <p class="truncate text-xs text-gray-500 dark:text-gray-400"><?php echo e((string) ($member['email'] ?? '')); ?></p>
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-semibold <?php echo ($member['status'] ?? '') === 'active' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300'; ?>"><?php echo e((string) ($member['status_label'] ?? '')); ?></span>
          </div>
        <?php endforeach; ?>
        <?php if ($memberRows === []): ?>
          <p class="rounded-2xl bg-gray-50 p-4 text-sm text-gray-500 dark:bg-gray-900/40 dark:text-gray-400">Aucun collaborateur pour le moment.</p>
        <?php endif; ?>
      </div>
    </article>

    <article class="rounded-2xl border border-gray-100 bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800">
      <div class="mb-4 flex items-center justify-between gap-3">
        <h3 class="app-inline-title font-bold text-gray-900 dark:text-white"><?php echo dashboard_title_icon('book-open'); ?><span>Formations achetées</span></h3>
        <a href="<?php echo e(site_url('company/seats')); ?>" class="text-sm font-semibold text-blue-600 hover:underline">Attribuer</a>
      </div>
      <div class="space-y-3">
        <?php foreach (array_slice($seatCourseRows, 0, 5) as $course): ?>
          <div class="rounded-2xl bg-gray-50 p-3 dark:bg-gray-900/40">
            <div class="flex items-start justify-between gap-3">
              <p class="font-semibold text-gray-900 dark:text-white"><?php echo e((string) ($course['course_title'] ?? 'Cours')); ?></p>
              <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-500/10 dark:text-blue-300"><?php echo e((string) ((int) ($course['seats_available'] ?? 0))); ?> dispo.</span>
            </div>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><?php echo e((string) ((int) ($course['seats_assigned'] ?? 0))); ?> attribuées sur <?php echo e((string) ((int) ($course['seats_total'] ?? 0))); ?> places</p>
          </div>
        <?php endforeach; ?>
        <?php if ($seatCourseRows === []): ?>
          <p class="rounded-2xl bg-gray-50 p-4 text-sm text-gray-500 dark:bg-gray-900/40 dark:text-gray-400">Aucune place achetée. Passez par le catalogue et activez le mode entreprise au paiement.</p>
        <?php endif; ?>
      </div>
    </article>
  </section>
<?php endif; ?>

<?php require __DIR__ . '/_simple_shell_end.php'; ?>
