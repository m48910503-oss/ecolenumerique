<?php
declare(strict_types=1);

$exam = is_array($exam ?? null) ? $exam : [];
$examId = (int) ($examId ?? 0);
$backUrl = trim((string) ($backUrl ?? site_url('teacher/courses')));
$examType = trim((string) ($examType ?? 'qcm'));
$typeLabel = trim((string) ($typeLabel ?? 'Examen'));
$questions = is_array($questions ?? null) ? $questions : [];
$questionsByVariant = is_array($questionsByVariant ?? null) ? $questionsByVariant : [1 => [], 2 => [], 3 => []];
$optionsByQuestion = is_array($optionsByQuestion ?? null) ? $optionsByQuestion : [];
$pointsByVariant = is_array($pointsByVariant ?? null) ? $pointsByVariant : [1 => 0, 2 => 0, 3 => 0];
$hasVariant = !empty($hasVariant);
$meetingSlots = is_array($meetingSlots ?? null) ? $meetingSlots : [];
$variantLabels = is_array($variantLabels ?? null) ? $variantLabels : [1 => 'Version A', 2 => 'Version B', 3 => 'Version C'];
$statusLabels = is_array($statusLabels ?? null) ? $statusLabels : [];
$siteLogoUrl = trim((string) ($siteLogoUrl ?? ''));
  
$examQuestionTypeLabel = isset($examQuestionTypeLabel) && is_callable($examQuestionTypeLabel) ? $examQuestionTypeLabel : static function (string $type): string {
        $labels = [
            'single' => 'Choix unique',
            'multiple' => 'Choix multiple',
            'text' => 'Réponse texte',
        ];
        return $labels[$type] ?? 'Choix unique';
    };
$fmtDtShort = isset($fmtDtShort) && is_callable($fmtDtShort) ? $fmtDtShort : static function (string $dbValue): string {
        if (!$dbValue) {
            return '';
        }
$ts = strtotime($dbValue);
        if (!$ts) {
            return (string) $dbValue;
        }
return date('d/m/Y H:i', $ts);
    };

$courseTitle = trim((string) ($exam['course_title'] ?? 'Cours'));
$examTitle = trim((string) ($exam['title'] ?? 'Examen'));
$examDescription = trim((string) ($exam['description'] ?? ''));
$durationLabel = !empty($exam['duration_min']) ? format_duration_minutes_hm((int) $exam['duration_min']) : 'Durée libre';
$attemptsLabel = (string) max(1, (int) ($exam['max_attempts'] ?? 1));
$minScoreLabel = (string) max(0, (int) ($exam['min_score'] ?? 0));
$questionTotal = count($questions);
$variantCount = 0;
foreach ($questionsByVariant as $variantQuestions) {
    if ($variantQuestions !== []) {
        $variantCount++;
    }
}
if ($variantCount < 1) {
    $variantCount = $hasVariant ? 3 : ($questionTotal > 0 ? 1 : 0);
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="app-global-assets" content="off">
    <title>Aperçu de l'examen - École Numérique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo e(site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()))); ?>"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/teacher-dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/teacher-dashboard.css') ?: time()))); ?>">
    <script src="<?php echo e(site_url('assets/js/sidebar_toggle.js')); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/teacher_dashboard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/teacher_dashboard.js') ?: time()))); ?>" defer></script>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="app-view app-role-teacher min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100 font-body" data-view="teacher/exam_preview">
    <div class="teacher-dashboard-layout relative h-screen overflow-hidden pt-16 sm:pt-[4.5rem] md:pl-72 md:flex-row">
        <?php $teacherNavActive = 'courses'; ?>
        <?php require __DIR__ . '/_sidebar.php'; ?>
        <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

        <div class="flex-1 min-w-0">
            <?php $teacherTopTitle = 'Aperçu de l\'examen'; ?>
            <?php $teacherTopSubtitle = 'Espace enseignant'; ?>
            <?php require __DIR__ . '/_topbar.php'; ?>

            <main class="teacher-hub-main mx-auto max-w-7xl px-4 py-6 md:px-6 xl:px-8 space-y-6">
                <section class="rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/50">
                    <div class="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start gap-4">
                                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-200">
                                    <?php if ($siteLogoUrl !== ''): ?>
                                        <img src="<?php echo e($siteLogoUrl); ?>" alt="Logo" class="h-full w-full rounded-2xl object-contain">
                                    <?php else: ?>
                                        <?php echo dashboard_icon_svg('clipboard-list'); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400"><?php echo e($courseTitle); ?></p>
                                    <h1 class="mt-2 text-3xl font-display font-semibold text-slate-900 dark:text-slate-100"><?php echo e($examTitle); ?></h1>
                                    <?php if ($examDescription !== ''): ?>
                                        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300"><?php echo e($examDescription); ?></p>
                                    <?php endif; ?>
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200">
                                            <span class="app-icon text-blue-600 dark:text-blue-300" aria-hidden="true"><?php echo dashboard_icon_svg('clipboard-list'); ?></span>
                                            <?php echo e($typeLabel); ?>
                                        </span>
                                        <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200">
                                            <span class="app-icon text-blue-600 dark:text-blue-300" aria-hidden="true"><?php echo dashboard_icon_svg('clock'); ?></span>
                                            <?php echo e($durationLabel); ?>
                                        </span>
                                        <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200">
                                            <span class="app-icon text-blue-600 dark:text-blue-300" aria-hidden="true"><?php echo dashboard_icon_svg('layers'); ?></span>
                                            <?php echo e($attemptsLabel); ?> tentative(s)
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 xl:justify-end">
                            <a href="<?php echo e(site_url('teacher/exams_manage?exam_id=' . $examId)); ?>" class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-500">
                                Gérer les tentatives
                            </a>
                            <a href="<?php echo e($backUrl); ?>" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-400 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100 dark:hover:border-slate-500">
                                Retour
                            </a>
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white/90 p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/50">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Validation enseignant</p>
                            <h2 class="mt-2 text-xl font-display font-semibold text-slate-900 dark:text-slate-100">Vue de contr&ocirc;le avant diffusion</h2>
                            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">V&eacute;rifiez contenu, versions et consignes.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200">
                                <?php echo e((string) $variantCount); ?> version(s)
                            </span>
                            <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200">
                                <?php echo e((string) ($examType === 'meeting' ? count($meetingSlots) : $questionTotal)); ?> &eacute;l&eacute;ment(s)
                            </span>
                        </div>
                    </div>
                </section>

                <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <article class="app-kpi-card app-kpi-card--blue rounded-2xl border p-4">
                        <p class="app-kpi-card__head">
                            <span class="app-kpi-card__icon" aria-hidden="true"><?php echo dashboard_icon_svg('clipboard-list'); ?></span>
                            <span>Type</span>
                        </p>
                        <p class="app-kpi-card__value text-2xl"><?php echo e($typeLabel); ?></p>
                        <p class="app-kpi-card__meta">Format de l'examen</p>
                    </article>
                    <article class="app-kpi-card app-kpi-card--indigo rounded-2xl border p-4">
                        <p class="app-kpi-card__head">
                            <span class="app-kpi-card__icon" aria-hidden="true"><?php echo dashboard_icon_svg('clock'); ?></span>
                            <span>Durée</span>
                        </p>
                        <p class="app-kpi-card__value text-2xl"><?php echo e($durationLabel); ?></p>
                        <p class="app-kpi-card__meta">Temps alloué</p>
                    </article>
                    <article class="app-kpi-card app-kpi-card--emerald rounded-2xl border p-4">
                        <p class="app-kpi-card__head">
                            <span class="app-kpi-card__icon" aria-hidden="true"><?php echo dashboard_icon_svg('award'); ?></span>
                            <span>Note min</span>
                        </p>
                        <p class="app-kpi-card__value text-2xl"><?php echo e($minScoreLabel); ?></p>
                        <p class="app-kpi-card__meta">Seuil de validation</p>
                    </article>
                    <article class="app-kpi-card app-kpi-card--sky rounded-2xl border p-4">
                        <p class="app-kpi-card__head">
                            <span class="app-kpi-card__icon" aria-hidden="true"><?php echo dashboard_icon_svg('layers'); ?></span>
                            <span>Contenu</span>
                        </p>
                        <p class="app-kpi-card__value text-2xl"><?php echo e($examType === 'meeting' ? (string) count($meetingSlots) : (string) $questionTotal); ?></p>
                        <p class="app-kpi-card__meta">
                            <?php if ($examType === 'meeting'): ?>
                                Créneau(x) ? <?php elseif ($examType === 'drive') : ?>
                                Dépôt attendu ? <?php else : ?>
                                Question(s)
                            <?php endif; ?>
                        </p>
                    </article>
                </section>

                <?php if ($examType === 'qcm'): ?>
                    <section class="rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/50">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Structure</p>
                                <h2 class="mt-2 text-xl font-display font-semibold text-slate-900 dark:text-slate-100">Questions et versions</h2>
                            </div>
                            <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300">
                                <?php echo e($hasVariant ? (string) $variantCount . ' versions' : '1 version'); ?>
                            </span>
                        </div>

                        <?php if ($questions === []): ?>
                            <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50/80 p-4 text-sm text-slate-600 dark:border-slate-800 dark:bg-slate-900/55 dark:text-slate-300">
                                Aucune question n'a encore été ajoutée à cet examen.
                            </div>
                        <?php else: ?>
                            <div class="mt-5 space-y-5">
                                <?php foreach ($questionsByVariant as $variant => $items): ?>
                                    <?php if ($items === []) continue; ?>
                                    <article class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 dark:border-slate-800 dark:bg-slate-900/55">
                                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                            <div>
                                                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100"><?php echo e((string) ($variantLabels[$variant] ?? ('Version ' . (string) $variant))); ?></h3>
                                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?php echo e((string) count($items)); ?> question(s)</p>
                                            </div>
                                            <span class="inline-flex rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-200">
                                                <?php echo e((string) ((int) ($pointsByVariant[$variant] ?? 0))); ?> pts
                                            </span>
                                        </div>

                                        <div class="mt-4 space-y-3">
                                            <?php foreach ($items as $idx => $q): ?>
                                                <?php
                                                $qType = (string) ($q['question_type'] ?? 'single');
                                                $qid = (int) ($q['id'] ?? 0);
                                                $qNumber = (int) ($q['order_index'] ?? ($idx + 1));
                                                $opts = $optionsByQuestion[$qid] ?? [];
                                                ?>
                                                <article class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-950/65">
                                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                                        <div>
                                                            <p class="text-base font-semibold text-slate-900 dark:text-slate-100">Q<?php echo e((string) $qNumber); ?>. <?php echo e((string) ($q['question_text'] ?? '')); ?></p>
                                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400"><?php echo e($examQuestionTypeLabel($qType)); ?></p>
                                                        </div>
                                                        <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300">
                                                            <?php echo e((string) ((int) ($q['points'] ?? 0))); ?> pt
                                                        </span>
                                                    </div>

                                                    <?php if ($qType === 'text'): ?>
                                                        <div class="mt-3 rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm text-slate-600 dark:border-slate-800 dark:bg-slate-900/55 dark:text-slate-300">
                                                            Réponse libre attendue.
                                                        </div>
                                                    <?php elseif ($opts !== []): ?>
                                                        <ul class="mt-3 space-y-2">
                                                            <?php foreach ($opts as $optIndex => $opt): ?>
                                                                <?php $isCorrect = !empty($opt['is_correct']); ?>
                                                                <li class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-900/55">
                                                                    <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-slate-300 text-xs font-semibold text-slate-500 dark:border-slate-700 dark:text-slate-300"><?php echo e(chr(65 + $optIndex)); ?></span>
                                                                    <div class="min-w-0 flex-1">
                                                                        <p class="text-slate-800 dark:text-slate-200"><?php echo e((string) ($opt['option_text'] ?? '')); ?></p>
                                                                    </div>
                                                                    <?php if ($isCorrect): ?>
                                                                        <span class="inline-flex rounded-full border border-emerald-300 bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">Correcte</span>
                                                                    <?php endif; ?>
                                                                </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    <?php else: ?>
                                                        <div class="mt-3 rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm text-slate-600 dark:border-slate-800 dark:bg-slate-900/55 dark:text-slate-300">
                                                            Aucune option n'est configurée pour cette question.
                                                        </div>
                                                    <?php endif; ?>
                                                </article>
                                            <?php endforeach; ?>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php elseif ($examType === 'drive'): ?>
                    <section class="rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/50">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Remise</p>
                        <h2 class="mt-2 text-xl font-display font-semibold text-slate-900 dark:text-slate-100">Dépôt Drive</h2>
                        <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50/70 p-5 dark:border-slate-800 dark:bg-slate-900/55">
                            <p class="text-sm text-slate-600 dark:text-slate-300">Les apprenants déposent leur fichier directement pour correction dans l’espace prévu.</p>
                            <?php
                            $subjectLink = (string) ($exam['drive_subject_link'] ?? '');
                            if ($subjectLink === '' && !empty($exam['drive_expected'])) {
                                $legacy = (string) $exam['drive_expected'];
                                if (str_starts_with($legacy, 'http')) {
                                    $subjectLink = $legacy;
                                }
                            }
$subjectName = (string) ($exam['drive_subject_name'] ?? '');
                            ?>
                            <?php if ($subjectLink !== ''): ?>
                                <a class="mt-4 inline-flex items-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-500" href="<?php echo e($subjectLink); ?>" target="_blank" rel="noopener">
                                    Ouvrir le sujet<?php echo $subjectName !== '' ? ' : ' . e($subjectName) : ''; ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php elseif ($examType === 'meeting'): ?>
                    <section class="rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950/50">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Organisation</p>
                                <h2 class="mt-2 text-xl font-display font-semibold text-slate-900 dark:text-slate-100">Créneaux proposés</h2>
                            </div>
                            <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300">
                                <?php echo e((string) count($meetingSlots)); ?> créneau(x)
                            </span>
                        </div>

                        <?php if ($meetingSlots === []): ?>
                            <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50/80 p-4 text-sm text-slate-600 dark:border-slate-800 dark:bg-slate-900/55 dark:text-slate-300">
                                Aucun créneau n'a encore été ajouté.
                            </div>
                        <?php else: ?>
                            <div class="mt-5 space-y-3">
                                <?php foreach ($meetingSlots as $slot): ?>
                                    <?php
                                    $status = (string) ($slot['status'] ?? 'available');
                                    $statusLabel = $statusLabels[$status] ?? $status;
                                    $mode = (string) ($slot['mode'] ?? 'online');
                                    $modeLabel = $mode === 'onsite' ? 'Présentiel' : 'En ligne';
                                    ?>
                                    <article class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 dark:border-slate-800 dark:bg-slate-900/55">
                                        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                            <div>
                                                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                                                    <?php echo e($fmtDtShort($slot['start_at'] ?? '')); ?>
                                                    <?php if (!empty($slot['end_at'])): ?>
                                                        &middot; fin <?php echo e($fmtDtShort($slot['end_at'] ?? '')); ?>
                                                    <?php endif; ?>
                                                </p>
                                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Mode: <?php echo e($modeLabel); ?></p>
                                                <?php if ($mode === 'online' && !empty($slot['link'])): ?>
                                                    <p class="mt-2 break-all text-sm text-slate-600 dark:text-slate-300">Lien: <?php echo e((string) $slot['link']); ?></p>
                                                <?php endif; ?>
                                                <?php if ($mode === 'onsite' && !empty($slot['place'])): ?>
                                                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Lieu: <?php echo e((string) $slot['place']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <span class="inline-flex rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-950/70 dark:text-slate-300">
                                                <?php echo e($statusLabel); ?>
                                            </span>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>
            </main>
        </div>
    </div>
</body>
</html>



