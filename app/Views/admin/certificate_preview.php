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

$studentName = trim((string) ($sampleCertificate['studentName'] ?? 'Aïcha Mensah'));
$courseTitle = trim((string) ($sampleCertificate['courseTitle'] ?? 'Développement Web 2026'));
$examTitle = trim((string) ($sampleCertificate['examTitle'] ?? 'Évaluation finale'));
$teacherName = trim((string) ($sampleCertificate['teacherName'] ?? 'Michaël Ouwade'));
$score = trim((string) ($sampleCertificate['score'] ?? '94% (Excellent)'));
$hours = trim((string) ($sampleCertificate['hours'] ?? '42 h'));
$description = trim((string) ($sampleCertificate['description'] ?? 'Une formation intensive couvrant les compétences clés du parcours et validée par une évaluation finale.'));
$code = trim((string) ($sampleCertificate['code'] ?? 'CERT-DEMO-2026-0001'));
$issuedLabel = trim((string) ($sampleCertificate['issuedLabel'] ?? date('d/m/Y')));
$verifyUrl = trim((string) ($sampleCertificate['verifyUrl'] ?? site_url('certificate?code=' . urlencode($code))));
$publicHost = parse_url($verifyUrl, PHP_URL_HOST);
$publicHost = is_string($publicHost) && $publicHost !== '' ? $publicHost : 'ecolenumerique.local';
$adminSupportLine = trim(implode(' · ', array_values(array_filter([
    $supportEmail !== '' ? $supportEmail : null,
    $supportPhone !== '' ? $supportPhone : null,
    $supportWhatsapp !== '' ? $supportWhatsapp : null,
]))));
$adminFooterNote = trim($footerText !== '' ? $footerText : $siteAddress);

$adminNavActive = 'certificate_preview';
$adminTopTitle = 'Exemplaire de certificat';
$adminTopSubtitle = 'Certificats';
$adminTopBadge = 'Aperçu';
$adminTopBadgeTone = 'active';
$adminTopSearchAction = site_url('admin/certificate_preview');
$adminTopSearchPlaceholder = 'Rechercher un paramètre certificat...';
$adminTopActions = [
    ['label' => 'Paramètres', 'url' => site_url('admin/site_settings?tab=certificate'), 'tone' => 'neutral'],
    ['label' => 'Tableau de bord', 'url' => site_url('admin/dashboard'), 'tone' => 'neutral'],
];
$adminPageScripts = ['assets/vendor/qrcode-generator/qrcode.js', 'admin_certificate_preview.js'];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exemplaire de certificat · Administration</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <?php require __DIR__ . '/_head_assets.php'; ?>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="app-view app-role-admin min-h-screen bg-slate-50 text-slate-900 font-body dark:bg-slate-950 dark:text-slate-100" data-view="admin/certificate_preview" data-sidebar-desktop-toggle="1">
    <div class="admin-shell-backdrop" aria-hidden="true">
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--one"></div>
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--two"></div>
    </div>

    <div class="admin-shell-root relative z-10 flex min-h-screen">
        <?php require __DIR__ . '/_sidebar.php'; ?>
        <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

        <div class="admin-shell-main flex min-w-0 flex-1 flex-col">
            <?php require __DIR__ . '/_topbar.php'; ?>

            <main class="admin-shell-content admin-shell-dense admin-certificate-preview-page px-4 py-4 sm:px-6 sm:py-5 space-y-3">
                <section class="admin-certificate-stage rounded-3xl p-2.5 sm:p-3.5">
                    <div class="mb-3 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h3 class="text-lg font-black text-slate-950 dark:text-white">
                                <i class="fas fa-award mr-2 text-primary-600 dark:text-primary-300" aria-hidden="true"></i>
                                Aperçu visuel
                            </h3>
                        </div>
                        <div class="admin-certificate-toolbar">
                            <details class="admin-certificate-download-menu" data-certificate-download-menu data-certificate-download-base="<?php echo e(site_url('admin/certificate_preview')); ?>">
                                <summary class="admin-certificate-download" aria-haspopup="menu">
                                    <i class="fas fa-download" aria-hidden="true"></i>
                                    Télécharger
                                    <i class="fas fa-chevron-down admin-certificate-download__chevron" aria-hidden="true"></i>
                                </summary>
                                <div class="admin-certificate-download-menu__panel">
                                    <a href="<?php echo e(site_url('admin/certificate_preview?export=pdf')); ?>" class="admin-certificate-download-menu__item" data-certificate-download-link="pdf" data-certificate-download-action>
                                        <i class="fas fa-file-pdf" aria-hidden="true"></i>
                                        <span>PDF</span>
                                    </a>
                                    <a href="<?php echo e(site_url('admin/certificate_preview?export=word')); ?>" class="admin-certificate-download-menu__item" data-certificate-download-link="word" data-certificate-download-action>
                                        <i class="fas fa-file-word" aria-hidden="true"></i>
                                        <span>Word</span>
                                    </a>
                                    <button type="button" class="admin-certificate-download-menu__item" data-certificate-download-action data-certificate-print>
                                        <i class="fas fa-print" aria-hidden="true"></i>
                                        <span>Imprimer</span>
                                    </button>
                                </div>
                            </details>
                        </div>
                    </div>

                    <?php require __DIR__ . '/_certificate_card.php'; ?>

                    <p class="mt-5 text-center text-sm text-slate-500 dark:text-slate-400">
                        <i class="fas fa-info-circle mr-1 text-primary-600 dark:text-primary-300" aria-hidden="true"></i>
                        Les vrais certificats gardent leur code unique et leur lien de vérification.
                    </p>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
