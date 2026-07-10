<?php
declare(strict_types=1);

$root = dirname(__DIR__);

/**
 * @return string
 */
function readFileOrEmpty(string $path): string
{
    if (!is_file($path)) {
        return '';
    }
    $content = file_get_contents($path);
    return is_string($content) ? $content : '';
}

/**
 * @param array<int,string> $needles
 */
function assertContainsAll(string $content, string $relativePath, array $needles, array &$errors): void
{
    foreach ($needles as $needle) {
        if (strpos($content, $needle) === false) {
            $errors[] = $relativePath . ' manque: ' . $needle;
        }
    }
}

/**
 * @param array<int,string> $files
 */
function assertNoBrokenUtf8(array $files, string $root, array &$errors): void
{
    foreach ($files as $relativePath) {
        $content = readFileOrEmpty($root . DIRECTORY_SEPARATOR . $relativePath);
        if ($content === '') {
            $errors[] = $relativePath . ' introuvable ou vide.';
            continue;
        }
        if (strpos($content, 'Ã') !== false) {
            $errors[] = $relativePath . ' contient des caractères corrompus (ex: Ã).';
        }
    }
}

$studentPages = [
    'app/Views/student/dashboard.php',
    'app/Views/student/my_courses.php',
    'app/Views/student/orders.php',
    'app/Views/student/favorites.php',
    'app/Views/student/meetings.php',
    'app/Views/student/referrals.php',
    'app/Views/student/certificates.php',
    'app/Views/student/exam.php',
];

$teacherPages = [
    'app/Views/teacher/dashboard.php',
    'app/Views/teacher/courses.php',
    'app/Views/teacher/course_content.php',
    'app/Views/teacher/course_create.php',
    'app/Views/teacher/course_edit.php',
    'app/Views/teacher/course_preview.php',
    'app/Views/teacher/course_updates.php',
    'app/Views/teacher/exams_create.php',
    'app/Views/teacher/exams_manage.php',
    'app/Views/teacher/exam_preview.php',
    'app/Views/teacher/meetings.php',
    'app/Views/teacher/promotions.php',
    'app/Views/teacher/referrals.php',
    'app/Views/teacher/withdrawals.php',
    'app/Views/teacher/campaigns.php',
];

$sharedPages = [
    'app/Views/web/notifications.php',
    'app/Views/web/profile.php',
    'app/Views/web/support.php',
];

$errors = [];

foreach ($studentPages as $relativePath) {
    $content = readFileOrEmpty($root . DIRECTORY_SEPARATOR . $relativePath);
    if ($content === '') {
        $errors[] = $relativePath . ' introuvable ou vide.';
        continue;
    }
    assertContainsAll($content, $relativePath, ['/_sidebar.php', '/_topbar.php'], $errors);
}

foreach ($teacherPages as $relativePath) {
    $content = readFileOrEmpty($root . DIRECTORY_SEPARATOR . $relativePath);
    if ($content === '') {
        $errors[] = $relativePath . ' introuvable ou vide.';
        continue;
    }
    assertContainsAll($content, $relativePath, ['/_sidebar.php', '/_topbar.php'], $errors);
}

foreach ($sharedPages as $relativePath) {
    $content = readFileOrEmpty($root . DIRECTORY_SEPARATOR . $relativePath);
    if ($content === '') {
        $errors[] = $relativePath . ' introuvable ou vide.';
        continue;
    }
    assertContainsAll(
        $content,
        $relativePath,
        ['/../dashboard/_shell_start.php', '/../dashboard/_shell_end.php', '/../dashboard/_context_switcher.php'],
        $errors
    );
}

$sidebarFiles = [
    'app/Views/student/_sidebar.php',
    'app/Views/teacher/_sidebar.php',
];
foreach ($sidebarFiles as $relativePath) {
    $content = readFileOrEmpty($root . DIRECTORY_SEPARATOR . $relativePath);
    if ($content === '') {
        $errors[] = $relativePath . ' introuvable ou vide.';
        continue;
    }
    assertContainsAll($content, $relativePath, ['dashboard_menu_config('], $errors);
    if ($relativePath === 'app/Views/teacher/_sidebar.php' && strpos($content, 'teacherDashboardVisibleSections') !== false) {
        $errors[] = $relativePath . ' ne doit pas masquer des sections avec une whitelist figée.';
    }
}

$topbarFiles = [
    'app/Views/student/_topbar.php',
    'app/Views/teacher/_topbar.php',
];
foreach ($topbarFiles as $relativePath) {
    $content = readFileOrEmpty($root . DIRECTORY_SEPARATOR . $relativePath);
    if ($content === '') {
        $errors[] = $relativePath . ' introuvable ou vide.';
        continue;
    }
    assertContainsAll($content, $relativePath, ['data-theme-label', 'Tableau de bord'], $errors);
}

assertNoBrokenUtf8(
    array_merge($studentPages, $teacherPages, $sharedPages, $sidebarFiles, $topbarFiles),
    $root,
    $errors
);

if ($errors !== []) {
    fwrite(STDERR, "QA dashboard UI: ECHEC\n");
    foreach ($errors as $error) {
        fwrite(STDERR, '- ' . $error . PHP_EOL);
    }
    exit(1);
}

fwrite(STDOUT, "QA dashboard UI: OK (" . (count($studentPages) + count($teacherPages) + count($sharedPages)) . " vues verifiees)\n");
exit(0);
