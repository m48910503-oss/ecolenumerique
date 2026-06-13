<?php
declare(strict_types=1);

$resolveUrl = static function (string $value): string {
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }
if (preg_match('#^https?://#i', $value) === 1) {
        return $value;
    }
return site_url(ltrim($value, '/'));
};

$formatNumber = static function (int $value): string {
    return number_format(max(0, $value), 0, ',', ' ');
}; 

$formatMoney = static function (int $value) use ($formatNumber): string {
    if ($value <= 0) {
        return 'Gratuit';
    }
return $formatNumber($value) . ' F CFA';
};

$formatShortDuration = static function ($minutes): string {
    if ($minutes === null || $minutes === '') {
        return 'Durée non précisée';
    }
$minutes = (int) $minutes;
    if ($minutes <= 0) {
        return 'Durée non précisée';
    }
return format_duration_minutes_hm($minutes);
};

$formatDate = static function (string $value): string {
    if ($value === null || trim($value) === '') {
        return '';
    }
$timestamp = strtotime($value);
    if ($timestamp === false) {
        return (string) $value;
    }
return date('d/m/Y à H:i', $timestamp);
};

$statusMap = [
    'draft' => 'Brouillon',
    'submitted' => 'Soumis',
    'approved' => 'Approuvé',
    'rejected' => 'Rejeté',
    'archived' => 'Archivé',
];

$levelMap = [
    'beginner' => 'Débutant',
    'intermediate' => 'Intermédiaire',
    'advanced' => 'Avancé',
];

$statusLabels = array_merge(is_array($statusLabels ?? null) ? $statusLabels : [], $statusMap);
$levelLabels = array_merge(is_array($levelLabels ?? null) ? $levelLabels : [], $levelMap);

$courseTitle = trim((string) ($course['title'] ?? ''));
$courseTitle = $courseTitle !== '' ? $courseTitle : 'Cours sans titre';
$courseDescription = trim((string) ($course['description'] ?? ''));
$courseStatus = trim((string) ($course['status'] ?? 'draft'));
$courseStatusLabel = (string) ($statusLabels[$courseStatus] ?? $statusMap[$courseStatus] ?? 'Brouillon');
$courseLevel = trim((string) ($course['level'] ?? ''));
$courseLevelLabel = (string) ($levelLabels[$courseLevel] ?? $levelMap[$courseLevel] ?? 'Non précisé');
$coursePrice = max(0, (int) ($course['price_xof'] ?? 0));
$generalResourceUrl = $resolveUrl((string) ($course['general_file_path'] ?? ''));
$coverUrl = $resolveUrl((string) ($course['cover_path'] ?? ''));
$coverAlt = trim((string) ($course['cover_alt'] ?? 'Illustration du cours'));
$resourceCount = max(0, (int) ($docTotals['all'] ?? 0));
$chapterCount = count($chapters);
$meetingCount = count($meetings);
$examCount = count($exams);
$videoCount = count($videoPlaylist);
$lessonCount = 0;
foreach ($lessonsByChapter as $lessonRows) {
    $lessonCount += count(is_array($lessonRows) ? $lessonRows : []);
}
$hasPedagogicalInfo = !empty($course['objectives'])
    || !empty($course['prerequisites'])
    || !empty($course['equipment'])
    || $generalResourceUrl !== '';

$meetingsByChapter = [];
foreach ($meetings as $meetingRow) {
    $chapterId = (int) ($meetingRow['chapter_id'] ?? 0);
    if (!isset($meetingsByChapter[$chapterId])) {
        $meetingsByChapter[$chapterId] = [];
    }
    $meetingsByChapter[$chapterId][] = $meetingRow;
}
$teacherName = trim((string) (($course['first_name'] ?? '') . ' ' . ($course['last_name'] ?? '')));
$teacherName = $teacherName !== '' ? $teacherName : 'Enseignant non précisé';
$categoryName = trim((string) ($course['category_name'] ?? ''));
