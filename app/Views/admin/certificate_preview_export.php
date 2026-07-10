<?php
$siteLogoUrl = trim((string) ($siteLogoUrl ?? ''));
$siteName = trim((string) ($siteName ?? 'École Numérique'));
$siteTagline = trim((string) ($siteTagline ?? ''));
$siteDescription = trim((string) ($siteDescription ?? ''));
$supportEmail = trim((string) ($supportEmail ?? ''));
$supportPhone = trim((string) ($supportPhone ?? ''));
$supportWhatsapp = trim((string) ($supportWhatsapp ?? ''));
$siteAddress = trim((string) ($siteAddress ?? ''));
$footerText = trim((string) ($footerText ?? ''));
$dgName = trim((string) ($dgName ?? 'Directeur général'));
$dgSignatureUrl = trim((string) ($dgSignatureUrl ?? ''));
$sampleCertificate = is_array($sampleCertificate ?? null) ? $sampleCertificate : [];

$adminPageScripts = ['assets/vendor/qrcode-generator/qrcode.js', 'admin_certificate_preview.js'];
?>
<!doctype html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="dark">
    <meta name="app-global-assets" content="off">
    <title>Certificat · Export</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <?php require __DIR__ . '/_head_assets.php'; ?>
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
    <style>
        html, body {
            width: 100%;
            height: 100%;
            margin: 0;
            overflow: hidden;
        }

        @page {
            size: A4 landscape;
            margin: 0;
        }

        body {
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                radial-gradient(circle at top, rgba(59, 130, 246, 0.18), transparent 34%),
                linear-gradient(180deg, #0f172a 0%, #111827 100%);
        }

        .certificate-export-canvas {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(16px, 2.2vw, 36px);
            box-sizing: border-box;
        }

        .certificate-export-stage {
            width: min(100%, 1400px);
        }
    </style>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 font-body" data-view="admin/certificate_preview">
    <main class="certificate-export-canvas">
        <section class="certificate-export-stage admin-certificate-stage rounded-3xl p-2.5 sm:p-3.5">
            <?php require __DIR__ . '/_certificate_card.php'; ?>
        </section>
    </main>
</body>
</html>
