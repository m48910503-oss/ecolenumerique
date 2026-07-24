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
$certificateAutoPrint = !empty($certificateAutoPrint);

$adminPageScripts = ['assets/vendor/qrcode-generator/qrcode.js', 'admin_certificate_preview.js'];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="app-global-assets" content="off">
    <title>Certificat · Export</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@500;700&family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <?php require __DIR__ . '/_head_assets.php'; ?>
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/certificate-model.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/certificate-model.css') ?: time()))); ?>">
    <style>
        html, body {
            width: 100%;
            height: 100%;
            margin: 0;
            overflow: hidden;
            background: #ffffff;
        }

        @page {
            size: A4 landscape;
            margin: 0;
        }

        body {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
        }

        .certificate-export-canvas {
            width: 100vw;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
            padding: 0;
        }

        .certificate-export-stage {
            width: min(100%, 2970px);
            max-width: none;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border: 0;
            background: #ffffff;
            box-shadow: none;
        }

        html.dark .certificate-export-stage.admin-certificate-stage,
        html[data-theme="dark"] .certificate-export-stage.admin-certificate-stage {
            background: #ffffff !important;
            border: 0 !important;
            box-shadow: none !important;
        }

        .certificate-export-stage .certificate-design {
            width: 297mm !important;
            min-width: 297mm !important;
            max-width: none !important;
            height: 210mm !important;
            box-shadow: none !important;
        }

        @media screen {
            .certificate-export-stage .certificate-design {
                width: min(calc(100vw - 3.5rem), calc((100vh - 3.5rem) * 1.414)) !important;
                min-width: 0 !important;
                max-width: none !important;
                height: auto !important;
            }
        }
    </style>
</head>
<body class="app-role-admin min-h-screen bg-white text-slate-900 font-body" data-view="admin/certificate_preview">
    <main class="certificate-export-canvas">
        <section class="certificate-export-stage admin-certificate-stage">
            <?php require __DIR__ . '/_certificate_card.php'; ?>
        </section>
    </main>
    <?php if ($certificateAutoPrint): ?>
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
