<?php
$cert = is_array($cert ?? null) ? $cert : [];
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
$scoreLabel = trim((string) ($cert['score'] ?? ''));

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
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/standalone-dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/standalone-dashboard.css') ?: time()))); ?>">
    <?php if (!$isWordExport): ?>
        <script src="<?php echo e(site_url('assets/js/web_theme.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/web_theme.js') ?: time()))); ?>" defer></script>
    <?php endif; ?>
    
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="certificate-page" data-view="web/certificate">
    <div class="wrap">
        <?php if (!$isWordExport): ?>
            <header class="toolbar no-print">
                <div>
                    <h1>Certificat professionnel</h1>
                    <p>Version exportable Word/PDF. Code: <strong><?php echo e($certificateCode !== '' ? $certificateCode : 'n/c'); ?></strong></p>
                </div>
                <div class="toolbar-actions">
                    <a href="<?php echo e($htmlUrl); ?>" class="btn">Vue web</a>
                    <a href="<?php echo e($wordUrl); ?>" class="btn">Word</a>
                    <a href="<?php echo e($pdfUrl); ?>" class="btn btn-primary">PDF</a>
                    <button type="button" onclick="window.print()" class="btn">Imprimer</button>
                </div>
            </header>
        <?php endif; ?>

        <section class="sheet">
            <article class="sheet-inner">
                <div class="head">
                    <div class="brand">
                        <div class="logo">
                            <?php if ($siteLogoUrl !== ''): ?>
                                <img src="<?php echo e($siteLogoUrl); ?>" alt="Logo">
                            <?php else: ?>
                                <span style="font-weight:700;font-size:12px;">LOGO</span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <p class="brand-kicker">École Numérique</p>
                            <p class="brand-title">Attestation officielle de réussite</p>
                        </div>
                    </div>
                    <div class="meta">
                        <p class="badge">Vérifié</p>
                        <p style="margin:8px 0 0;">Émis le <?php echo e($issuedLabel); ?></p>
                    </div>
                </div>

                <div class="hero">
                    <p class="hero-kicker">Certification académique</p>
                    <h2 class="hero-title">Certificat de r&eacute;ussite</h2>
                    <p class="hero-subtitle">Ce certificat est attribu&eacute; &agrave;</p>
                    <p class="hero-name"><?php echo e($fullName); ?></p>
                </div>

                <div class="body-grid">
                    <section class="panel">
                        <h3>Détails de la validation</h3>
                        <p><strong>Examen:</strong> <?php echo e((string) ($cert['exam_title'] ?? 'Examen')); ?></p>
                        <p><strong>Cours:</strong> <?php echo e((string) ($cert['course_title'] ?? 'Cours')); ?></p>
                        <p><strong>Code certificat:</strong> <?php echo e($certificateCode !== '' ? $certificateCode : 'n/c'); ?></p>
                        <?php if ($scoreLabel !== ''): ?>
                            <p><strong>Score:</strong> <?php echo e($scoreLabel); ?></p>
                        <?php endif; ?>
                    </section>

                    <section class="panel panel-verify">
                        <div>
                            <h3>Vérification</h3>
                            <p>Utilisez uniquement le lien local ci-dessous pour contrôler l'authenticité.</p>
                            <p style="font-size:12px;margin-top:10px;word-break:break-all;"><strong><?php echo e($verifyUrl); ?></strong></p>
                        </div>
                        <div class="qr">
                            <?php if ($qrVerifyUrl !== ''): ?>
                                <img src="<?php echo e($qrVerifyUrl); ?>" alt="QR de vérification">
                            <?php else: ?>
                                <span style="font-size:11px;color:#6b7f9d;">Vérification locale uniquement</span>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>

                <div class="signatures">
                    <section class="signature-block">
                        <div class="signature-image">
                            <?php if ($teacherSignatureUrl !== ''): ?>
                                <img src="<?php echo e($teacherSignatureUrl); ?>" alt="Signature formateur">
                            <?php endif; ?>
                        </div>
                        <div class="signature-line"></div>
                        <p class="signature-role">Signature enseignant</p>
                        <p class="signature-name"><?php echo e($teacherName); ?></p>
                    </section>

                    <section class="signature-block">
                        <div class="signature-image">
                            <?php if ($dgSignatureUrl !== ''): ?>
                                <img src="<?php echo e($dgSignatureUrl); ?>" alt="Signature direction">
                            <?php endif; ?>
                        </div>
                        <div class="signature-line"></div>
                        <p class="signature-role">Direction générale</p>
                        <p class="signature-name"><?php echo e($dgName); ?></p>
                    </section>
                </div>

                <p class="foot">
                    Toute vérification doit s'effectuer via ce lien: <?php echo e($verifyUrl); ?>
                </p>
            </article>
        </section>
    </div>

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
