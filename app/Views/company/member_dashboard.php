<?php
declare(strict_types=1);

$companySimpleMemberMode = true;
$companySimplePage = 'dashboard';
$companySimpleTitle = 'Mon espace entreprise';
$companySimpleSubtitle = 'Accédez aux formations attribuées par votre entreprise.';
$assignedCourses = is_array($assignedCourses ?? null) ? $assignedCourses : [];
$otherCourses = is_array($otherCourses ?? null) ? $otherCourses : [];
$companyAdmins = is_array($companyAdmins ?? null) ? $companyAdmins : [];
$kpiCards = is_array($kpiCards ?? null) ? $kpiCards : [];
require __DIR__ . '/_simple_shell_start.php';
require __DIR__ . '/_simple_flash.php';
?>

<div class="student-dashboard-stats-grid grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 sm:gap-5">
  <?php
  $stats = [
      ['label' => 'Formations entreprise', 'value' => count($assignedCourses), 'note' => 'Places attribuées', 'icon' => 'book-open'],
      ['label' => 'Autres cours', 'value' => count($otherCourses), 'note' => 'Accès personnels', 'icon' => 'graduation-cap'],
      ['label' => 'Notifications', 'value' => max(0, (int) ($unreadCount ?? 0)), 'note' => 'Non lues', 'icon' => 'bell'],
      ['label' => 'Rendez-vous', 'value' => max(0, (int) ($upcomingMeetingsCount ?? 0)), 'note' => 'A venir', 'icon' => 'calendar'],
  ];
  ?>
  <?php foreach ($stats as $stat): ?>
    <article class="rounded-2xl border border-gray-100 bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo e((string) $stat['label']); ?></p>
          <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white"><?php echo e((string) $stat['value']); ?></p>
        </div>
        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300">
          <i class="fas fa-<?php echo e((string) $stat['icon']); ?>"></i>
        </div>
      </div>
      <p class="mt-2 text-xs text-gray-500 dark:text-gray-400"><?php echo e((string) $stat['note']); ?></p>
    </article>
  <?php endforeach; ?>
</div>

<section class="grid grid-cols-1 gap-4 lg:grid-cols-[1.2fr,0.8fr] sm:gap-6">
  <article class="rounded-2xl border border-gray-100 bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800">
    <div class="mb-5 flex items-center justify-between gap-3">
      <div>
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-600 dark:text-blue-300">A suivre</p>
        <h3 class="app-inline-title text-lg font-bold text-gray-900 dark:text-white"><?php echo dashboard_title_icon('book-open'); ?><span>Formations attribuées par l’entreprise</span></h3>
      </div>
      <a href="<?php echo e(site_url('student/my_courses')); ?>" class="text-sm font-semibold text-blue-600 hover:underline">Mes cours</a>
    </div>

    <div class="space-y-3">
      <?php foreach ($assignedCourses as $course): ?>
        <?php $progress = max(0, min(100, (int) ($course['progress_pct'] ?? 0))); ?>
        <a href="<?php echo e((string) ($course['course_url'] ?? site_url('student/my_courses'))); ?>" class="block rounded-2xl bg-gray-50 p-4 transition hover:bg-blue-50 dark:bg-gray-900/40 dark:hover:bg-blue-500/10">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="truncate font-semibold text-gray-900 dark:text-white"><?php echo e((string) ($course['title'] ?? 'Cours')); ?></p>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Attribuée le <?php echo e((string) ($course['assigned_at_label'] ?? '-')); ?></p>
            </div>
            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-500/10 dark:text-blue-300"><?php echo e((string) $progress); ?>%</span>
          </div>
          <div class="mt-3 h-1.5 rounded-full bg-gray-200 dark:bg-gray-700">
            <div class="h-1.5 rounded-full bg-blue-600" style="width: <?php echo e((string) $progress); ?>%"></div>
          </div>
        </a>
      <?php endforeach; ?>
      <?php if ($assignedCourses === []): ?>
        <p class="rounded-2xl bg-gray-50 p-4 text-sm text-gray-500 dark:bg-gray-900/40 dark:text-gray-400">Aucune formation entreprise ne vous a encore été attribuée.</p>
      <?php endif; ?>
    </div>
  </article>

  <aside class="space-y-4">
    <article class="rounded-2xl border border-gray-100 bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800">
      <h3 class="app-inline-title font-bold text-gray-900 dark:text-white"><?php echo dashboard_title_icon('building'); ?><span>Votre rattachement</span></h3>
      <dl class="mt-4 space-y-3 text-sm">
        <div class="flex justify-between gap-3">
          <dt class="text-gray-500 dark:text-gray-400">Entreprise</dt>
          <dd class="font-semibold text-gray-900 dark:text-white"><?php echo e((string) ($company['name'] ?? 'Entreprise')); ?></dd>
        </div>
        <div class="flex justify-between gap-3">
          <dt class="text-gray-500 dark:text-gray-400">Rôle</dt>
          <dd class="font-semibold text-gray-900 dark:text-white"><?php echo e((string) ($member['role_label'] ?? 'Collaborateur')); ?></dd>
        </div>
        <div class="flex justify-between gap-3">
          <dt class="text-gray-500 dark:text-gray-400">Statut</dt>
          <dd class="font-semibold text-gray-900 dark:text-white"><?php echo e((string) ($member['status_label'] ?? 'Actif')); ?></dd>
        </div>
      </dl>
    </article>

    <article class="rounded-2xl border border-gray-100 bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800">
      <h3 class="app-inline-title font-bold text-gray-900 dark:text-white"><?php echo dashboard_title_icon('shield'); ?><span>Administrateurs entreprise</span></h3>
      <div class="mt-4 space-y-3">
        <?php foreach ($companyAdmins as $admin): ?>
          <div class="rounded-2xl bg-gray-50 p-3 dark:bg-gray-900/40">
            <p class="font-medium text-gray-900 dark:text-white"><?php echo e((string) ($admin['full_name'] ?? 'Administrateur')); ?></p>
            <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo e((string) ($admin['email'] ?? '')); ?></p>
          </div>
        <?php endforeach; ?>
        <?php if ($companyAdmins === []): ?>
          <p class="text-sm text-gray-500 dark:text-gray-400">Aucun autre administrateur affiché.</p>
        <?php endif; ?>
      </div>
    </article>
  </aside>
</section>

<?php require __DIR__ . '/_simple_shell_end.php'; ?>
