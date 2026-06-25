<?php
declare(strict_types=1);

$companySimplePage = 'courses';
$companySimpleTitle = 'Mes cours';
$companySimpleSubtitle = 'Retrouvez les formations accessibles dans votre espace entreprise.';
$companySimpleMemberMode = !empty($companySimpleMemberMode);
$assignedCourses = is_array($assignedCourses ?? null) ? $assignedCourses : [];
$otherCourses = is_array($otherCourses ?? null) ? $otherCourses : [];
$seatCourseRows = is_array($seatCourseRows ?? null) ? $seatCourseRows : [];
$seatSummary = is_array($seatSummary ?? null) ? $seatSummary : [];
$companyAdmins = is_array($companyAdmins ?? null) ? $companyAdmins : [];
$companyCourseNote = trim((string) ($companyCourseNote ?? ''));
$money = static fn (int $amount): string => number_format($amount, 0, ',', ' ') . ' F CFA';
$resolveWatchUrl = static function (array $course): string {
    $slug = trim((string) ($course['slug'] ?? ''));
    if ($slug !== '') {
        return site_url('course/watch?slug=' . urlencode($slug));
    }

    $courseId = max(0, (int) ($course['course_id'] ?? $course['id'] ?? 0));
    if ($courseId > 0) {
        return site_url('course/watch?id=' . $courseId);
    }

    return site_url('company/seats');
};

require __DIR__ . '/_simple_shell_start.php';
require __DIR__ . '/_simple_flash.php';
?>

<section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800">
  <div class="flex flex-wrap items-start justify-between gap-4">
    <div class="min-w-0">
      <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-600 dark:text-blue-300">Accès lecture</p>
      <h3 class="app-inline-title mt-1 text-lg font-bold text-gray-900 dark:text-white"><?php echo dashboard_title_icon('book-open'); ?><span>Vos cours</span></h3>
      <?php if ($companyCourseNote !== ''): ?>
        <p class="mt-2 max-w-2xl text-sm text-gray-500 dark:text-gray-400"><?php echo e($companyCourseNote); ?></p>
      <?php endif; ?>
    </div>
    <div class="flex flex-wrap items-center gap-3">
      <a href="<?php echo e(site_url('company/seats')); ?>" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
        <i class="fas fa-layer-group" aria-hidden="true"></i>
        Gérer les places
      </a>
      <a href="<?php echo e(site_url('catalogue')); ?>" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800">
        <i class="fas fa-search" aria-hidden="true"></i>
        Catalogue
      </a>
    </div>
  </div>
</section>

<div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-[1.2fr,0.8fr] sm:gap-6">
  <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800">
    <div class="mb-4 flex items-center justify-between gap-3">
      <div>
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-600 dark:text-blue-300">Lecture</p>
        <h3 class="app-inline-title mt-1 font-bold text-gray-900 dark:text-white"><?php echo dashboard_title_icon('play-circle'); ?><span>Cours accessibles</span></h3>
      </div>
      <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-500/10 dark:text-blue-300"><?php echo e((string) count($assignedCourses)); ?> accès</span>
    </div>

    <div class="space-y-3">
      <?php foreach ($assignedCourses as $course): ?>
        <?php
        $progress = max(0, min(100, (int) ($course['progress_pct'] ?? 0)));
        $courseUrl = $resolveWatchUrl($course);
        $courseTitle = trim((string) ($course['title'] ?? $course['course_title'] ?? 'Cours'));
        $teacher = trim((string) ($course['teacher_name'] ?? ''));
        $assignedAt = trim((string) ($course['assigned_at_label'] ?? $course['enrolled_at_label'] ?? '-'));
        ?>
        <article class="rounded-2xl bg-gray-50 p-4 dark:bg-gray-900/40">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="truncate text-base font-semibold text-gray-900 dark:text-white"><?php echo e($courseTitle); ?></p>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Attribué le <?php echo e($assignedAt); ?></p>
              <?php if ($teacher !== ''): ?>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Par <?php echo e($teacher); ?></p>
              <?php endif; ?>
            </div>
            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-500/10 dark:text-blue-300"><?php echo e((string) $progress); ?>%</span>
          </div>

          <div class="mt-3 h-1.5 rounded-full bg-gray-200 dark:bg-gray-700">
            <div class="h-1.5 rounded-full bg-blue-600" style="width: <?php echo e((string) $progress); ?>%"></div>
          </div>

          <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <p class="text-xs text-gray-500 dark:text-gray-400">
              <?php echo $progress > 0 ? 'Lecture en cours' : 'Prêt à ouvrir'; ?>
            </p>
            <a href="<?php echo e($courseUrl); ?>" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
              <i class="fas fa-book-open" aria-hidden="true"></i>
              Ouvrir
            </a>
          </div>
        </article>
      <?php endforeach; ?>

      <?php if ($assignedCourses === []): ?>
        <div class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 p-6 text-center dark:border-gray-700 dark:bg-gray-900/40">
          <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-100 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300">
            <i class="fas fa-book-open"></i>
          </div>
          <h4 class="mt-4 text-base font-semibold text-gray-900 dark:text-white">Aucun cours accessible pour le moment</h4>
          <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            Si vous êtes administrateur, attribuez d’abord une place depuis <a href="<?php echo e(site_url('company/seats')); ?>" class="font-semibold text-blue-600 hover:underline">Formations / Places</a>.
          </p>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <aside class="space-y-4">
    <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800">
      <div class="flex items-center justify-between gap-3">
        <h3 class="app-inline-title font-bold text-gray-900 dark:text-white"><?php echo dashboard_title_icon('receipt'); ?><span>Formations achetées</span></h3>
        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300"><?php echo e((string) ((int) ($seatSummary['courses'] ?? count($seatCourseRows)))); ?> cours</span>
      </div>
      <div class="mt-4 space-y-3">
        <?php foreach ($seatCourseRows as $course): ?>
          <?php
          $courseTitle = trim((string) ($course['course_title'] ?? 'Cours'));
          $seatsAssigned = max(0, (int) ($course['seats_assigned'] ?? 0));
          $seatsTotal = max(0, (int) ($course['seats_total'] ?? 0));
          $seatsAvailable = max(0, (int) ($course['seats_available'] ?? 0));
          ?>
          <article class="rounded-2xl bg-gray-50 p-4 dark:bg-gray-900/40">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="truncate font-semibold text-gray-900 dark:text-white"><?php echo e($courseTitle); ?></p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><?php echo e((string) $seatsAssigned); ?> attribuées sur <?php echo e((string) $seatsTotal); ?> places</p>
              </div>
              <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"><?php echo e((string) $seatsAvailable); ?> dispo.</span>
            </div>
            <div class="mt-3 flex items-center justify-end">
              <a href="<?php echo e(site_url('company/seats')); ?>" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                Attribuer
              </a>
            </div>
          </article>
        <?php endforeach; ?>

        <?php if ($seatCourseRows === []): ?>
          <p class="rounded-2xl bg-gray-50 p-4 text-sm text-gray-500 dark:bg-gray-900/40 dark:text-gray-400">Aucune formation achetée n’est disponible pour le moment.</p>
        <?php endif; ?>
      </div>
    </section>

    <?php if ($companyAdmins !== []): ?>
      <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800">
        <h3 class="app-inline-title font-bold text-gray-900 dark:text-white"><?php echo dashboard_title_icon('shield'); ?><span>Administrateurs entreprise</span></h3>
        <div class="mt-4 space-y-3">
          <?php foreach ($companyAdmins as $admin): ?>
            <div class="rounded-2xl bg-gray-50 p-3 dark:bg-gray-900/40">
              <p class="font-medium text-gray-900 dark:text-white"><?php echo e((string) ($admin['full_name'] ?? 'Administrateur')); ?></p>
              <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo e((string) ($admin['email'] ?? '')); ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>
  </aside>
</div>

<?php require __DIR__ . '/_simple_shell_end.php'; ?>
