<?php
$cert = is_array($cert ?? null) ? $cert : [];
$siteName = trim((string) ($siteName ?? 'École Numérique'));
$siteTagline = trim((string) ($siteTagline ?? ''));
$siteDescription = trim((string) ($siteDescription ?? ''));
$supportEmail = trim((string) ($supportEmail ?? ''));
$supportPhone = trim((string) ($supportPhone ?? ''));
$supportWhatsapp = trim((string) ($supportWhatsapp ?? ''));
$siteAddress = trim((string) ($siteAddress ?? ''));
$footerText = trim((string) ($footerText ?? ''));
$fullName = trim((string) ($fullName ?? ''));
$teacherName = trim((string) ($teacherName ?? 'Formateur'));
$dgName = trim((string) ($dgName ?? 'Direction générale'));
$siteLogoUrl = trim((string) ($siteLogoUrl ?? ''));
$teacherSignatureUrl = trim((string) ($teacherSignatureUrl ?? ''));
$dgSignatureUrl = trim((string) ($dgSignatureUrl ?? ''));
$issuedLabel = trim((string) ($issuedLabel ?? ''));
$verifyUrl = trim((string) ($verifyUrl ?? site_url('')));
$qrVerifyUrl = trim((string) ($qrVerifyUrl ?? ''));
$hoursLabel = trim((string) ($hoursLabel ?? ''));
$certificateDescription = trim((string) ($certificateDescription ?? ''));
$exportFormat = strtolower(trim((string) ($exportFormat ?? 'html')));
$isPdfExport = !empty($isPdfExport);

$certificateCode = trim((string) ($cert['code'] ?? ''));
$courseTitle = trim((string) ($cert['course_title'] ?? 'Cours'));
$examTitle = trim((string) ($cert['exam_title'] ?? 'Examen'));
$scoreValue = $cert['score'] ?? null;
$scoreLabel = '';
if (is_numeric($scoreValue)) {
    $scoreLabel = (string) (int) $scoreValue . '/100';
} else {
    $scoreLabel = trim((string) $scoreValue);
}

if ($hoursLabel === '' && is_numeric($cert['duration_hours'] ?? null) && function_exists('format_duration_hours_hm')) {
    $hoursLabel = format_duration_hours_hm((float) $cert['duration_hours']);
}

if ($siteName === '') {
    $siteName = 'École Numérique';
}
if ($fullName === '') {
    $fullName = 'Étudiant';
}
if ($teacherName === '') {
    $teacherName = 'Formateur';
}
if ($dgName === '') {
    $dgName = 'Direction générale';
}
if ($issuedLabel === '') {
    $issuedLabel = 'n/c';
}
if ($courseTitle === '') {
    $courseTitle = 'Cours';
}
if ($examTitle === '') {
    $examTitle = 'Examen';
}

$brandSubtitle = $siteTagline !== '' ? $siteTagline : $siteDescription;
$supportParts = array_values(array_filter([
    $supportEmail !== '' ? $supportEmail : null,
    $supportPhone !== '' ? $supportPhone : null,
    $supportWhatsapp !== '' ? $supportWhatsapp : null,
], static fn ($value): bool => is_string($value) && trim($value) !== ''));
$supportLine = implode(' · ', $supportParts);
$publicHost = parse_url($verifyUrl, PHP_URL_HOST);
$publicHost = is_string($publicHost) && $publicHost !== '' ? $publicHost : 'ecolenumerique.local';
$htmlUrl = site_url('certificate?code=' . urlencode($certificateCode));
$pdfUrl = site_url('certificate?code=' . urlencode($certificateCode) . '&format=pdf');

$certificateSiteName = $siteName;
$certificateBrandSubtitle = $brandSubtitle;
$certificateSiteLogoUrl = $siteLogoUrl;
$certificateRecipient = $fullName;
$certificateCourseTitle = $courseTitle;
$certificateCourseDescription = $certificateDescription;
$certificateExamTitle = $examTitle;
$certificateIssuedLabel = $issuedLabel;
$certificateScoreLabel = $scoreLabel;
$certificateHoursLabel = $hoursLabel;
$certificateTeacherName = $teacherName;
$certificateTeacherSignatureUrl = $teacherSignatureUrl;
$certificateDirectorName = $dgName;
$certificateDirectorSignatureUrl = $dgSignatureUrl;
$certificateVerifyUrl = $verifyUrl;
$certificateQrUrl = $qrVerifyUrl !== '' ? $qrVerifyUrl : $verifyUrl;
$certificateSupportLine = $supportLine;
$certificateFooterNote = trim($footerText !== '' ? $footerText : $siteAddress);
$certificateHostLabel = $publicHost;
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="app-global-assets" content="off">
    <title>Certificat - <?php echo e($fullName); ?> · <?php echo e($siteName); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@500;700&family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&family=Playfair+Display:wght@700;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/admin-dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/admin-dashboard.css') ?: time()))); ?>">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/standalone-dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/standalone-dashboard.css') ?: time()))); ?>">
    <script src="<?php echo e(site_url('assets/vendor/qrcode-generator/qrcode.js')); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/vendor/html2pdf/html2pdf.bundle.min.js?v=' . (@filemtime(__DIR__ . '/../../../assets/vendor/html2pdf/html2pdf.bundle.min.js') ?: time()))); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/web_certificate.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/web_certificate.js') ?: time()))); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/certificate_pdf_download.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/certificate_pdf_download.js') ?: time()))); ?>" defer></script>
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/certificate-model.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/certificate-model.css') ?: time()))); ?>">
</head>
<body class="certificate-page" data-view="web/certificate" data-export="<?php echo e($exportFormat); ?>">
    <main class="cert-page-shell">
        <header class="cert-toolbar no-print" aria-label="Actions du certificat">
            <div class="cert-toolbar__copy">
                <p class="cert-toolbar__eyebrow">Certificat professionnel</p>
                <h1>Certificat de réussite</h1>
                <p>Code <strong><?php echo e($certificateCode !== '' ? $certificateCode : 'n/c'); ?></strong></p>
            </div>
            <nav class="cert-toolbar__actions" aria-label="Exports">
                <a href="<?php echo e($htmlUrl); ?>" class="cert-action">Vue web</a>
                <a
                    href="<?php echo e($pdfUrl); ?>"
                    class="cert-action cert-action--primary"
                    data-certificate-pdf-download
                    data-certificate-filename="certificat-<?php echo e(strtolower($certificateCode)); ?>.pdf"
                >PDF</a>
                <button type="button" onclick="window.print()" class="cert-action">Imprimer</button>
            </nav>
        </header>

        <section class="cert-sheet cert-model-stage" aria-label="Certificat">
            <?php require __DIR__ . '/../shared/_certificate_premium_model.php'; ?>
        </section>
    </main>

    <?php if ($isPdfExport): ?>
        <script>
            window.addEventListener('load', function () {
                window.setTimeout(function () {
                    window.print();
                }, 180);
            });
        </script>
    <?php endif; ?>
</body>
</html>
