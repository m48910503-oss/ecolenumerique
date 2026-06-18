<?php
$cert = is_array($cert ?? null) ? $cert : [];
$siteName = trim((string) ($siteName ?? 'École Numérique'));
$fullName = trim((string) ($fullName ?? ''));
$teacherName = trim((string) ($teacherName ?? 'Formateur'));
$dgName = trim((string) ($dgName ?? 'Direction générale'));
$siteLogoUrl = trim((string) ($siteLogoUrl ?? ''));
$teacherSignatureUrl = trim((string) ($teacherSignatureUrl ?? ''));
$dgSignatureUrl = trim((string) ($dgSignatureUrl ?? ''));
$issuedLabel = trim((string) ($issuedLabel ?? ''));
$verifyUrl = trim((string) ($verifyUrl ?? site_url('')));
$qrVerifyUrl = trim((string) ($qrVerifyUrl ?? ''));
$exportFormat = strtolower(trim((string) ($exportFormat ?? 'html')));
$isWordExport = !empty($isWordExport);
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

$publicHost = parse_url($verifyUrl, PHP_URL_HOST);
$publicHost = is_string($publicHost) && $publicHost !== '' ? $publicHost : 'ecolenumerique.local';
$htmlUrl = site_url('certificate?code=' . urlencode($certificateCode));
$pdfUrl = site_url('certificate?code=' . urlencode($certificateCode) . '&format=pdf');
$wordUrl = site_url('certificate?code=' . urlencode($certificateCode) . '&format=docx');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="app-global-assets" content="off">
    <title>Certificat - <?php echo e($fullName); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/standalone-dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/standalone-dashboard.css') ?: time()))); ?>">
    <?php if (!$isWordExport): ?>
        <script src="<?php echo e(site_url('assets/js/web_theme.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/web_theme.js') ?: time()))); ?>" defer></script>
    <?php endif; ?>
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="certificate-page" data-view="web/certificate" data-export="<?php echo e($exportFormat); ?>">
    <main class="cert-page-shell">
        <?php if (!$isWordExport): ?>
            <header class="cert-toolbar no-print" aria-label="Actions du certificat">
                <div class="cert-toolbar__copy">
                    <p class="cert-toolbar__eyebrow">Certificat professionnel</p>
                    <h1>Certificat de réussite</h1>
                    <p>Code <strong><?php echo e($certificateCode !== '' ? $certificateCode : 'n/c'); ?></strong></p>
                </div>
                <nav class="cert-toolbar__actions" aria-label="Exports">
                    <a href="<?php echo e($htmlUrl); ?>" class="cert-action">Vue web</a>
                    <a href="<?php echo e($wordUrl); ?>" class="cert-action">Word</a>
                    <a href="<?php echo e($pdfUrl); ?>" class="cert-action cert-action--primary">PDF</a>
                    <button type="button" onclick="window.print()" class="cert-action">Imprimer</button>
                </nav>
            </header>
        <?php endif; ?>

        <section class="cert-sheet" aria-label="Certificat">
            <article class="cert-document">
                <span class="cert-corner cert-corner--top-left" aria-hidden="true"></span>
                <span class="cert-corner cert-corner--top-right" aria-hidden="true"></span>
                <span class="cert-corner cert-corner--bottom-left" aria-hidden="true"></span>
                <span class="cert-corner cert-corner--bottom-right" aria-hidden="true"></span>

                <header class="cert-document__header">
                    <div class="cert-brand">
                        <span class="cert-brand__mark">
                            <?php if ($siteLogoUrl !== ''): ?>
                                <img src="<?php echo e($siteLogoUrl); ?>" alt="Logo <?php echo e($siteName); ?>">
                            <?php else: ?>
                                <span><?php echo e(mb_substr($siteName, 0, 1)); ?></span>
                            <?php endif; ?>
                        </span>
                        <span>
                            <strong><?php echo e($siteName); ?></strong>
                            <small>Attestation officielle de réussite</small>
                        </span>
                    </div>
                    <div class="cert-number">
                        <span>Certificat N°</span>
                        <strong><?php echo e($certificateCode !== '' ? $certificateCode : 'n/c'); ?></strong>
                    </div>
                </header>

                <section class="cert-hero">
                    <p class="cert-overline">Certification académique</p>
                    <h2>Certificat de réussite</h2>
                    <p class="cert-intro">Ce document officiel est décerné à</p>
                    <p class="cert-recipient"><?php echo e($fullName); ?></p>
                    <p class="cert-course-intro">pour avoir validé avec succès la formation</p>
                    <h3><?php echo e($courseTitle); ?></h3>
                </section>

                <section class="cert-meta-grid" aria-label="Détails de validation">
                    <div class="cert-meta-card">
                        <span>Examen</span>
                        <strong><?php echo e($examTitle); ?></strong>
                    </div>
                    <div class="cert-meta-card">
                        <span>Date d’émission</span>
                        <strong><?php echo e($issuedLabel); ?></strong>
                    </div>
                    <?php if ($scoreLabel !== ''): ?>
                        <div class="cert-meta-card">
                            <span>Score</span>
                            <strong><?php echo e($scoreLabel); ?></strong>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="cert-verification">
                    <div class="cert-verification__text">
                        <p class="cert-section-label">Vérification</p>
                        <p>Authenticité contrôlable uniquement via le lien officiel ci-dessous.</p>
                        <strong><?php echo e($verifyUrl); ?></strong>
                    </div>
                    <div class="cert-qr" aria-label="Vérification locale">
                        <?php if ($qrVerifyUrl !== ''): ?>
                            <img src="<?php echo e($qrVerifyUrl); ?>" alt="QR de vérification">
                        <?php else: ?>
                            <span>Vérification locale uniquement</span>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="cert-signatures" aria-label="Signatures">
                    <div class="cert-signature">
                        <div class="cert-signature__media">
                            <?php if ($teacherSignatureUrl !== ''): ?>
                                <img src="<?php echo e($teacherSignatureUrl); ?>" alt="Signature <?php echo e($teacherName); ?>">
                            <?php else: ?>
                                <span><?php echo e($teacherName); ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="cert-signature__line"></span>
                        <p>Signature enseignant</p>
                        <strong><?php echo e($teacherName); ?></strong>
                    </div>

                    <div class="cert-seal" aria-label="Certificat vérifié">
                        <span>Certifié</span>
                        <strong><?php echo e($siteName); ?></strong>
                        <small><?php echo e($publicHost); ?></small>
                    </div>

                    <div class="cert-signature">
                        <div class="cert-signature__media">
                            <?php if ($dgSignatureUrl !== ''): ?>
                                <img src="<?php echo e($dgSignatureUrl); ?>" alt="Signature <?php echo e($dgName); ?>">
                            <?php else: ?>
                                <span><?php echo e($dgName); ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="cert-signature__line"></span>
                        <p>Direction générale</p>
                        <strong><?php echo e($dgName); ?></strong>
                    </div>
                </section>

                <footer class="cert-footer">
                    <span>Document délivré par <?php echo e($siteName); ?></span>
                    <span><?php echo e($verifyUrl); ?></span>
                </footer>
            </article>
        </section>
    </main>

    <?php if ($isPdfExport && !$isWordExport): ?>
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
