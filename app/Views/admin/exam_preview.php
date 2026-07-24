<?php
declare(strict_types=1);

$exam = is_array($exam ?? null) ? $exam : [];
$examId = (int) ($examId ?? 0);
$backUrl = trim((string) ($backUrl ?? site_url('admin/courses')));
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

$courseId = (int) ($exam['course_id'] ?? 0);
$courseTitle = trim((string) ($exam['course_title'] ?? 'Cours'));
$examTitle = trim((string) ($exam['title'] ?? 'Examen'));
$examDescription = trim((string) ($exam['description'] ?? ''));
$coursePreviewUrl = $courseId > 0 ? site_url('admin/course_preview?id=' . $courseId) : site_url('admin/courses');
$durationLabel = !empty($exam['duration_min']) ? format_duration_minutes_hm((int) $exam['duration_min']) : 'Durée libre';
$attemptsLabel = (string) max(1, (int) ($exam['max_attempts'] ?? 1));
$minScoreLabel = (string) max(0, (int) ($exam['min_score'] ?? 0));
$questionTotal = count($questions);
$pointsTotal = 0;
foreach ($pointsByVariant as $variantPoints) {
    $pointsTotal += max(0, (int) $variantPoints);
}
$variantCount = 0;
foreach ($questionsByVariant as $variantQuestions) {
    if ($variantQuestions !== []) {
        $variantCount++;
    }
}
if ($variantCount < 1) {
    $variantCount = $hasVariant ? 3 : ($questionTotal > 0 ? 1 : 0);
}
$contentCount = $examType === 'meeting' ? count($meetingSlots) : ($examType === 'drive' ? 1 : $questionTotal);

$examQuestionTypeLabel = isset($examQuestionTypeLabel) && is_callable($examQuestionTypeLabel) ? $examQuestionTypeLabel : static function (string $type): string {
    $labels = [
        'single' => 'Choix unique',
        'multiple' => 'Choix multiple',
        'text' => 'Réponse texte',
    ];

    return $labels[$type] ?? 'Choix unique';
};

$fmtDtShort = isset($fmtDtShort) && is_callable($fmtDtShort) ? $fmtDtShort : static function (string $dbValue): string {
    if ($dbValue === '') {
        return '';
    }

    $ts = strtotime($dbValue);
    if (!$ts) {
        return $dbValue;
    }

    return date('d/m/Y H:i', $ts);
};

$truncateText = isset($truncateText) && is_callable($truncateText) ? $truncateText : static function (string $value, int $limit = 120): string {
    $value = trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode($value, ENT_QUOTES, 'UTF-8'))) ?? '');
    if ($value === '') {
        return '';
    }

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($value) <= $limit) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, max(0, $limit - 3))) . '...';
    }

    if (strlen($value) <= $limit) {
        return $value;
    }

    return rtrim(substr($value, 0, max(0, $limit - 3))) . '...';
};

$adminNavActive = 'courses';
$adminTopTitle = "Aperçu de l'examen";
$adminTopSearchAction = site_url('admin/courses');
$adminTopSearchPlaceholder = 'Rechercher un cours...';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aperçu de l'examen · Administration</title>
    <?php require __DIR__ . '/_head_assets.php'; ?>
</head>
<body class="app-view app-role-admin admin-exam-preview-page min-h-screen overflow-hidden overflow-x-hidden bg-slate-50 text-slate-900 font-body dark:bg-slate-950 dark:text-slate-100" data-view="admin/exam_preview" data-sidebar-desktop-toggle="1">
    <div class="admin-shell-backdrop" aria-hidden="true">
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--one"></div>
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--two"></div>
    </div>

    <div class="admin-shell-root relative z-10 flex h-[100dvh] overflow-hidden">
        <?php require __DIR__ . '/_sidebar.php'; ?>
        <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition lg:hidden"></div>

        <div class="admin-shell-main flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden">
            <?php require __DIR__ . '/_topbar.php'; ?>

            <main class="admin-shell-content admin-shell-dense w-full max-w-none flex-1 min-h-0 space-y-5 overflow-y-auto overscroll-contain px-4 py-5 sm:px-6 sm:py-6" data-scroll-top-root="1">
                <section class="admin-course-panel p-4 sm:p-5 xl:p-6">
                    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_19rem]">
                        <div class="min-w-0">
                            <div class="flex items-start gap-4">
                                <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-200">
                                    <?php if ($siteLogoUrl !== ''): ?>
                                        <img src="<?php echo e($siteLogoUrl); ?>" alt="Logo" class="h-full w-full object-contain">
                                    <?php else: ?>
                                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('clipboard-list'); ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap gap-2">
                                        <span class="admin-course-badge admin-course-badge--active"><?php echo e($typeLabel); ?></span>
                                        <span class="admin-course-badge admin-course-badge--neutral">Examen #<?php echo e((string) $examId); ?></span>
                                        <?php if ($courseId > 0): ?>
                                            <span class="admin-course-badge admin-course-badge--neutral">Cours #<?php echo e((string) $courseId); ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <h1 class="mt-3 text-2xl font-black tracking-tight text-slate-900 dark:text-white sm:text-[2rem]"><?php echo e($examTitle); ?></h1>
                                    <p class="mt-2 text-sm font-semibold text-blue-600 dark:text-blue-300"><?php echo e($courseTitle); ?></p>
                                    <?php if ($examDescription !== ''): ?>
                                    <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300"><?php echo e($truncateText($examDescription, 120)); ?></p>
                                    <?php endif; ?>

                                    <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold">
                                        <span class="admin-course-mini admin-course-mini--teacher">
                                            <i class="fas fa-clipboard-list" aria-hidden="true"></i>
                                            <?php echo e($typeLabel); ?>
                                        </span>
                                        <span class="admin-course-mini admin-course-mini--date">
                                            <i class="far fa-clock" aria-hidden="true"></i>
                                            <?php echo e($durationLabel); ?>
                                        </span>
                                        <span class="admin-course-chip admin-course-chip--duration">
                                            <i class="fas fa-award" aria-hidden="true"></i>
                                            Note min <?php echo e($minScoreLabel); ?>
                                        </span>
                                        <span class="admin-course-chip admin-course-chip--level">
                                            <i class="fas fa-layer-group" aria-hidden="true"></i>
                                            <?php echo e($attemptsLabel); ?> tentative(s)
                                        </span>
                                        <span class="admin-course-chip admin-course-chip--price">
                                            <i class="fas fa-signal" aria-hidden="true"></i>
                                            <?php echo e((string) $contentCount); ?> élément(s)
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <aside class="space-y-3">
                            <a href="<?php echo e($backUrl); ?>" class="admin-shell-button admin-shell-button--primary inline-flex w-full items-center justify-center gap-2">
                                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                Retour au cours
                            </a>
                            <a href="<?php echo e(site_url('admin/courses')); ?>" class="admin-shell-button admin-shell-button--secondary inline-flex w-full items-center justify-center gap-2">
                                <i class="fas fa-list" aria-hidden="true"></i>
                                Liste des cours
                            </a>
                            <?php if ($courseId > 0): ?>
                                <a href="<?php echo e($coursePreviewUrl); ?>" class="admin-shell-button admin-shell-button--secondary inline-flex w-full items-center justify-center gap-2">
                                    <i class="fas fa-book-open" aria-hidden="true"></i>
                                    Voir le cours
                                </a>
                            <?php endif; ?>
                        </aside>
                    </div>
                </section>

                <div class="grid gap-5 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.8fr)]">
                    <div class="min-w-0 space-y-5">
                        <section id="resume" class="admin-course-panel p-4 sm:p-5">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                        <p class="text-[0.72rem] font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Résumé</p>
                                        <h2 class="mt-2 text-lg font-black text-slate-900 dark:text-slate-100">Détails</h2>
                                </div>
                                <span class="admin-course-badge admin-course-badge--neutral"><?php echo e((string) $variantCount); ?> version(s)</span>
                            </div>

                            <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                <article class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/55">
                                    <p class="text-sm text-slate-600 dark:text-slate-300">Type</p>
                                    <p class="mt-1 text-2xl font-black text-slate-950 dark:text-white"><?php echo e($typeLabel); ?></p>
                                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Format de l'examen</p>
                                </article>
                                <article class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/55">
                                    <p class="text-sm text-slate-600 dark:text-slate-300">Durée</p>
                                    <p class="mt-1 text-2xl font-black text-slate-950 dark:text-white"><?php echo e($durationLabel); ?></p>
                                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Temps alloué</p>
                                </article>
                                <article class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/55">
                                    <p class="text-sm text-slate-600 dark:text-slate-300">Note minimale</p>
                                    <p class="mt-1 text-2xl font-black text-slate-950 dark:text-white"><?php echo e($minScoreLabel); ?></p>
                                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Seuil de validation</p>
                                </article>
                                <article class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/55">
                                    <p class="text-sm text-slate-600 dark:text-slate-300">Contenu</p>
                                    <p class="mt-1 text-2xl font-black text-slate-950 dark:text-white"><?php echo e((string) $contentCount); ?></p>
                                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                        <?php if ($examType === 'meeting'): ?>
                                            Créneau(x)
                                        <?php elseif ($examType === 'drive'): ?>
                                            Dépôt attendu
                                        <?php else: ?>
                                            Question(s)
                                        <?php endif; ?>
                                    </p>
                                </article>
                            </div>
                        </section>

                        <?php if ($examType === 'qcm'): ?>
                            <section id="questions" class="admin-course-panel p-4 sm:p-5">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-[0.72rem] font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Structure</p>
                                    <h2 class="mt-2 text-lg font-black text-slate-900 dark:text-slate-100">Questions</h2>
                                    </div>
                                    <span class="admin-course-badge admin-course-badge--neutral"><?php echo e($hasVariant ? (string) $variantCount . ' versions' : '1 version'); ?></span>
                                </div>

                                <?php if ($questions === []): ?>
                                    <div class="mt-5 rounded-2xl border border-dashed border-slate-300 bg-slate-50/80 p-4 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-900/45 dark:text-slate-300">
                                        Aucune question.
                                    </div>
                                <?php else: ?>
                                    <div class="mt-5 space-y-5">
                                        <?php foreach ($questionsByVariant as $variant => $items): ?>
                                            <?php if ($items === []): ?>
                                                <?php continue; ?>
                                            <?php endif; ?>
                                            <article class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 dark:border-slate-800 dark:bg-slate-900/55">
                                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                                    <div>
                                                        <h3 class="text-lg font-black text-slate-900 dark:text-slate-100"><?php echo e((string) ($variantLabels[$variant] ?? ('Version ' . (string) $variant))); ?></h3>
                                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?php echo e((string) count($items)); ?> question(s)</p>
                                                    </div>
                                                    <span class="admin-course-badge admin-course-badge--active"><?php echo e((string) max(0, (int) ($pointsByVariant[$variant] ?? 0))); ?> pts</span>
                                                </div>

                                                <div class="mt-4 space-y-3">
                                                    <?php foreach ($items as $idx => $q): ?>
                                                        <?php
                                                        $qType = (string) ($q['question_type'] ?? 'single');
                                                        $qid = (int) ($q['id'] ?? 0);
                                                        $qNumber = (int) ($q['order_index'] ?? ($idx + 1));
                                                        $opts = is_array($optionsByQuestion[$qid] ?? null) ? $optionsByQuestion[$qid] : [];
                                                        ?>
                                                        <article class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-950/65">
                                                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                                                <div class="min-w-0">
                                                                    <p class="text-base font-semibold text-slate-900 dark:text-slate-100">
                                                                        Q<?php echo e((string) $qNumber); ?>. <?php echo e((string) ($q['question_text'] ?? '')); ?>
                                                                    </p>
                                                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400"><?php echo e($examQuestionTypeLabel($qType)); ?></p>
                                                                </div>
                                                                <span class="admin-course-badge admin-course-badge--neutral"><?php echo e((string) max(0, (int) ($q['points'] ?? 0))); ?> pt</span>
                                                            </div>

                                                            <?php if ($qType === 'text'): ?>
                                                                <div class="mt-3 rounded-2xl border border-dashed border-slate-200 bg-slate-50/80 px-4 py-3 text-sm text-slate-600 dark:border-slate-800 dark:bg-slate-900/55 dark:text-slate-300">
                                                                    Réponse libre.
                                                                </div>
                                                            <?php elseif ($opts !== []): ?>
                                                                <ul class="mt-3 space-y-2">
                                                                    <?php foreach ($opts as $optIndex => $opt): ?>
                                                                        <?php $isCorrect = !empty($opt['is_correct']); ?>
                                                                        <li class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-900/55">
                                                                            <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-slate-300 text-xs font-semibold text-slate-500 dark:border-slate-700 dark:text-slate-300">
                                                                                <?php echo e(chr(65 + $optIndex)); ?>
                                                                            </span>
                                                                            <div class="min-w-0 flex-1">
                                                                                <p class="text-slate-800 dark:text-slate-200"><?php echo e((string) ($opt['option_text'] ?? '')); ?></p>
                                                                            </div>
                                                                            <?php if ($isCorrect): ?>
                                                                                <span class="admin-course-badge admin-course-badge--active">Correcte</span>
                                                                            <?php endif; ?>
                                                                        </li>
                                                                    <?php endforeach; ?>
                                                                </ul>
                                                            <?php else: ?>
                                                                <div class="mt-3 rounded-2xl border border-dashed border-slate-200 bg-slate-50/80 px-4 py-3 text-sm text-slate-600 dark:border-slate-800 dark:bg-slate-900/55 dark:text-slate-300">
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
                            <section id="drive" class="admin-course-panel p-4 sm:p-5">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-[0.72rem] font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Remise</p>
                                        <h2 class="mt-2 text-lg font-black text-slate-900 dark:text-slate-100">Dépôt Drive</h2>
                                    </div>
                                    <span class="admin-course-badge admin-course-badge--pending">Sujet Drive</span>
                                </div>

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

                                <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50/80 p-5 dark:border-slate-800 dark:bg-slate-900/55">
                                    <p class="text-sm text-slate-600 dark:text-slate-300">
                                        Les apprenants déposent leur fichier directement pour correction dans l'espace prévu.
                                    </p>

                                    <?php if ($subjectLink !== ''): ?>
                                        <a class="admin-shell-button admin-shell-button--primary mt-4 inline-flex items-center gap-2" href="<?php echo e($subjectLink); ?>" target="_blank" rel="noopener">
                                            <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                                            Ouvrir le sujet<?php echo $subjectName !== '' ? ' : ' . e($subjectName) : ''; ?>
                                        </a>
                                    <?php else: ?>
                                        <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-3 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-950/50 dark:text-slate-300">
                                            Aucun lien de sujet n'est défini pour cet examen.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </section>
                        <?php elseif ($examType === 'meeting'): ?>
                            <section id="meeting" class="admin-course-panel p-4 sm:p-5">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-[0.72rem] font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Organisation</p>
                                        <h2 class="mt-2 text-lg font-black text-slate-900 dark:text-slate-100">Créneaux proposés</h2>
                                    </div>
                                    <span class="admin-course-badge admin-course-badge--neutral"><?php echo e((string) count($meetingSlots)); ?> créneau(x)</span>
                                </div>

                                <?php if ($meetingSlots === []): ?>
                                    <div class="mt-5 rounded-2xl border border-dashed border-slate-300 bg-slate-50/80 p-4 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-900/45 dark:text-slate-300">
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
                                                            <?php echo e($fmtDtShort((string) ($slot['start_at'] ?? ''))); ?>
                                                            <?php if (!empty($slot['end_at'])): ?>
                                                                &middot; fin <?php echo e($fmtDtShort((string) ($slot['end_at'] ?? ''))); ?>
                                                            <?php endif; ?>
                                                        </p>
                                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Mode : <?php echo e($modeLabel); ?></p>
                                                        <?php if ($mode === 'online' && !empty($slot['link'])): ?>
                                                            <p class="mt-2 break-all text-sm text-slate-600 dark:text-slate-300">Lien : <?php echo e((string) $slot['link']); ?></p>
                                                        <?php endif; ?>
                                                        <?php if ($mode === 'onsite' && !empty($slot['place'])): ?>
                                                            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Lieu : <?php echo e((string) $slot['place']); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                    <span class="admin-course-badge admin-course-badge--neutral"><?php echo e($statusLabel); ?></span>
                                                </div>
                                            </article>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </section>
                        <?php else: ?>
                            <section class="admin-course-panel p-4 sm:p-5">
                                <p class="text-[0.72rem] font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Format non supporté</p>
                                <h2 class="mt-2 text-lg font-black text-slate-900 dark:text-slate-100">Format inhabituel</h2>
                                <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                    L'aperçu admin n'a pas encore de rendu spécialisé pour ce type d'examen.
                                </p>
                            </section>
                        <?php endif; ?>
                    </div>

                    <aside class="space-y-5 lg:sticky lg:top-24 h-fit">
                        <section class="admin-course-panel p-4 sm:p-5">
                            <p class="text-[0.72rem] font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Navigation</p>
                            <div class="mt-4 flex flex-col gap-2">
                                <a href="#resume" class="admin-shell-button admin-shell-button--secondary inline-flex items-center justify-center gap-2">
                                    <i class="fas fa-chart-pie" aria-hidden="true"></i>
                                    Résumé
                                </a>
                                <?php if ($examType === 'qcm'): ?>
                                    <a href="#questions" class="admin-shell-button admin-shell-button--secondary inline-flex items-center justify-center gap-2">
                                        <i class="fas fa-list" aria-hidden="true"></i>
                                        Questions
                                    </a>
                                <?php elseif ($examType === 'drive'): ?>
                                    <a href="#drive" class="admin-shell-button admin-shell-button--secondary inline-flex items-center justify-center gap-2">
                                        <i class="fas fa-cloud-upload-alt" aria-hidden="true"></i>
                                        Sujet Drive
                                    </a>
                                <?php elseif ($examType === 'meeting'): ?>
                                    <a href="#meeting" class="admin-shell-button admin-shell-button--secondary inline-flex items-center justify-center gap-2">
                                        <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                                        Créneaux
                                    </a>
                                <?php endif; ?>
                            </div>
                        </section>

                        <section class="admin-course-panel p-4 sm:p-5">
                            <p class="text-[0.72rem] font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Infos</p>
                            <div class="mt-4 space-y-3 text-sm">
                                <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50/80 px-3 py-2.5 text-slate-600 dark:border-slate-800 dark:bg-slate-900/55 dark:text-slate-300">
                                    <span>Type</span>
                                    <strong class="font-black text-slate-900 dark:text-slate-100"><?php echo e($typeLabel); ?></strong>
                                </div>
                                <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50/80 px-3 py-2.5 text-slate-600 dark:border-slate-800 dark:bg-slate-900/55 dark:text-slate-300">
                                    <span>Durée</span>
                                    <strong class="font-black text-slate-900 dark:text-slate-100"><?php echo e($durationLabel); ?></strong>
                                </div>
                                <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50/80 px-3 py-2.5 text-slate-600 dark:border-slate-800 dark:bg-slate-900/55 dark:text-slate-300">
                                    <span>Note minimale</span>
                                    <strong class="font-black text-slate-900 dark:text-slate-100"><?php echo e($minScoreLabel); ?></strong>
                                </div>
                                <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50/80 px-3 py-2.5 text-slate-600 dark:border-slate-800 dark:bg-slate-900/55 dark:text-slate-300">
                                    <span>Tentatives</span>
                                    <strong class="font-black text-slate-900 dark:text-slate-100"><?php echo e($attemptsLabel); ?></strong>
                                </div>
                                <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50/80 px-3 py-2.5 text-slate-600 dark:border-slate-800 dark:bg-slate-900/55 dark:text-slate-300">
                                    <span>Points totaux</span>
                                    <strong class="font-black text-slate-900 dark:text-slate-100"><?php echo e((string) $pointsTotal); ?></strong>
                                </div>
                            </div>
                        </section>

                        <section class="admin-course-panel p-4 sm:p-5">
                            <p class="text-[0.72rem] font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Accès rapide</p>
                            <div class="mt-4 space-y-2">
                                <a href="<?php echo e($coursePreviewUrl); ?>" class="admin-shell-button admin-shell-button--primary inline-flex w-full items-center justify-center gap-2">
                                    <i class="fas fa-book-open" aria-hidden="true"></i>
                                    Voir la modération du cours
                                </a>
                                <a href="<?php echo e($backUrl); ?>" class="admin-shell-button admin-shell-button--secondary inline-flex w-full items-center justify-center gap-2">
                                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                    Retour
                                </a>
                            </div>
                        </section>
                    </aside>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
