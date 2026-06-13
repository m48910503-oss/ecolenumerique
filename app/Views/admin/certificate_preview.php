<?php
$siteLogoUrl = trim((string) ($siteLogoUrl ?? ''));
$siteName = trim((string) ($siteName ?? 'École Numérique'));
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
$adminPageScripts = ['admin_certificate_preview.js'];
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

            <main class="admin-shell-content admin-shell-dense admin-certificate-preview-page px-4 py-5 sm:px-6 sm:py-6 space-y-6">
                <section class="bg-gradient-to-r from-primary-600 to-accent-600 rounded-2xl p-6 text-white shadow-glow overflow-hidden relative">
                    <div class="absolute -right-10 -top-12 h-40 w-40 rounded-full bg-white/10"></div>
                    <div class="absolute bottom-0 right-28 h-24 w-24 rounded-full bg-white/10 blur-sm"></div>
                    <div class="relative flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                        <div class="max-w-3xl">
                            <p class="text-xs font-bold uppercase tracking-[0.22em] text-primary-100">
                                <i class="fas fa-certificate mr-2" aria-hidden="true"></i>Certificat officiel
                            </p>
                            <h2 class="mt-2 text-2xl font-bold sm:text-3xl">Exemplaire du certificat généré</h2>
                            <p class="mt-2 text-sm text-primary-100 sm:text-base">Aperçu basé sur le nouveau modèle visuel, avec les paramètres actuels de la plateforme.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="<?php echo e(site_url('admin/site_settings?tab=certificate')); ?>" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-3 text-sm font-bold text-primary-700 shadow-sm transition hover:bg-primary-50">
                                <i class="fas fa-cog" aria-hidden="true"></i>
                                Configurer
                            </a>
                            <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-4 py-3 text-sm font-bold text-white ring-1 ring-white/25 transition hover:bg-white/25" data-certificate-print>
                                <i class="fas fa-print" aria-hidden="true"></i>
                                Imprimer
                            </button>
                        </div>
                    </div>
                </section>

                <section class="grid grid-cols-1 gap-5 md:grid-cols-3">
                    <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Statut</p>
                                <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">Exemplaire</p>
                            </div>
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-300">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                            </span>
                        </div>
                    </article>

                    <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Code de démonstration</p>
                                <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white"><?php echo e($code); ?></p>
                            </div>
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-100 text-primary-600 dark:bg-blue-900/30 dark:text-blue-300">
                                <i class="fas fa-qrcode" aria-hidden="true"></i>
                            </span>
                        </div>
                    </article>

                    <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Signature DG</p>
                                <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white"><?php echo $dgSignatureUrl !== '' ? 'Configurée' : 'À compléter'; ?></p>
                            </div>
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-300">
                                <i class="fas fa-signature" aria-hidden="true"></i>
                            </span>
                        </div>
                    </article>
                </section>

                <section class="admin-certificate-stage rounded-3xl p-4 sm:p-6">
                    <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h3 class="text-lg font-black text-slate-950 dark:text-white">
                                <i class="fas fa-award mr-2 text-primary-600 dark:text-primary-300" aria-hidden="true"></i>
                                Aperçu visuel
                            </h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Ce rendu reprend le modèle fourni. Il reste un exemplaire non valide.</p>
                        </div>
                        <div class="admin-certificate-toolbar">
                            <div class="admin-certificate-orientation" aria-label="Orientation du certificat">
                                <button type="button" class="is-active" data-certificate-orientation="portrait">
                                    <i class="fas fa-portrait" aria-hidden="true"></i>
                                    Portrait
                                </button>
                                <button type="button" data-certificate-orientation="landscape">
                                    <i class="fas fa-image" aria-hidden="true"></i>
                                    Paysage
                                </button>
                            </div>
                            <button type="button" class="admin-certificate-download" data-certificate-print>
                                <i class="fas fa-download" aria-hidden="true"></i>
                                Télécharger PDF
                            </button>
                        </div>
                    </div>

                    <article id="certificate-container" class="admin-certificate-card admin-certificate-card--portrait">
                        <span class="admin-certificate-corner admin-certificate-corner--tl" aria-hidden="true"></span>
                        <span class="admin-certificate-corner admin-certificate-corner--tr" aria-hidden="true"></span>
                        <span class="admin-certificate-corner admin-certificate-corner--bl" aria-hidden="true"></span>
                        <span class="admin-certificate-corner admin-certificate-corner--br" aria-hidden="true"></span>

                        <div class="admin-certificate-bg-icon" aria-hidden="true">
                            <i class="fas fa-graduation-cap"></i>
                        </div>

                        <div class="admin-certificate-content">
                            <header class="admin-certificate-top">
                                <div class="admin-certificate-brand-main">
                                    <span class="admin-certificate-brand-icon">
                                        <?php if ($siteLogoUrl !== ''): ?>
                                            <img src="<?php echo e($siteLogoUrl); ?>" alt="Logo <?php echo e($siteName); ?>">
                                        <?php else: ?>
                                            <i class="fas fa-graduation-cap" aria-hidden="true"></i>
                                        <?php endif; ?>
                                    </span>
                                    <span class="admin-certificate-brand-name">
                                        <?php echo e($siteName); ?>
                                    </span>
                                </div>
                                <div class="admin-certificate-number">
                                    <p>Certificat N°</p>
                                    <strong><?php echo e($code); ?></strong>
                                </div>
                            </header>

                            <section class="admin-certificate-title-block">
                                <span class="admin-certificate-congrats">
                                    <i class="fas fa-crown" aria-hidden="true"></i>
                                    Félicitations
                                </span>
                                <h1>Certificat de Réussite</h1>
                                <div class="admin-certificate-title-rule" aria-hidden="true">
                                    <span></span>
                                    <i class="fas fa-star"></i>
                                    <span></span>
                                </div>
                                <p>Ce document officiel est décerné à</p>
                            </section>

                            <section class="admin-certificate-recipient">
                                <h2><?php echo e($studentName); ?></h2>
                                <span></span>
                            </section>

                            <p class="admin-certificate-course-intro">pour avoir complété avec succès la formation</p>
                            <h3 class="admin-certificate-course-title"><?php echo e($courseTitle); ?></h3>
                            <p class="admin-certificate-course-description"><?php echo e($description); ?></p>

                            <section class="admin-certificate-meta">
                                <span>
                                    <i class="fas fa-calendar-check" aria-hidden="true"></i>
                                    Obtenu le <strong><?php echo e($issuedLabel); ?></strong>
                                </span>
                                <span>
                                    <i class="fas fa-trophy" aria-hidden="true"></i>
                                    Note : <strong><?php echo e($score); ?></strong>
                                </span>
                                <span>
                                    <i class="fas fa-hourglass-half" aria-hidden="true"></i>
                                    Heures validées : <strong><?php echo e($hours); ?></strong>
                                </span>
                            </section>

                            <section class="admin-certificate-seal-row">
                                <div class="admin-certificate-sign-block">
                                    <p class="admin-certificate-signature-text"><?php echo e($teacherName); ?></p>
                                    <span class="admin-certificate-sign-line"></span>
                                    <small>Instructeur principal</small>
                                </div>

                                <div class="admin-certificate-seal">
                                    <i class="fas fa-certificate" aria-hidden="true"></i>
                                    <strong>Certifié</strong>
                                    <span><?php echo e($siteName); ?></span>
                                    <i class="fas fa-check-circle" aria-hidden="true"></i>
                                </div>

                                <div class="admin-certificate-sign-block">
                                    <div class="admin-certificate-signature-media">
                                        <?php if ($dgSignatureUrl !== ''): ?>
                                            <img src="<?php echo e($dgSignatureUrl); ?>" alt="Signature <?php echo e($dgName); ?>">
                                        <?php else: ?>
                                            <p class="admin-certificate-signature-text"><?php echo e($dgName); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <span class="admin-certificate-sign-line"></span>
                                    <small>Direction générale</small>
                                </div>
                            </section>

                            <footer class="admin-certificate-footer">
                                <div class="admin-certificate-verify">
                                    <span aria-hidden="true"><i class="fas fa-qrcode"></i></span>
                                    <div>
                                        <p>Vérification en ligne :</p>
                                        <strong><?php echo e($verifyUrl); ?></strong>
                                    </div>
                                </div>
                                <div class="admin-certificate-site">
                                    <p><i class="fas fa-globe" aria-hidden="true"></i> <?php echo e($publicHost); ?></p>
                                    <p><i class="far fa-copyright" aria-hidden="true"></i> <?php echo e(date('Y')); ?> <?php echo e($siteName); ?></p>
                                </div>
                            </footer>
                        </div>
                    </article>

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
