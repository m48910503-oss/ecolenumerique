<?php
$siteLogoUrl = (string) ($siteLogoUrl ?? '');
$lastUpdated = (string) ($lastUpdated ?? '2026-03-09');
$backHref = (string) ($backHref ?? site_url(''));
?>
<!doctype html>
<html lang="fr" class="light" data-default-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="app-global-assets" content="off">
    <title>Politique de confidentialité - École Numérique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo e(site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()))); ?>"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/standalone-dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/standalone-dashboard.css') ?: time()))); ?>">
    <script src="<?php echo e(site_url('assets/js/web_theme.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/web_theme.js') ?: time()))); ?>" defer></script>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="standalone-dashboard-page app-public-page min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100 font-body" data-view="web/privacy">
    <div class="relative z-10 min-h-screen">
        <header class="border-b border-slate-200/70 bg-white/80 backdrop-blur dark:border-slate-800 dark:bg-slate-900/70">
            <div class="max-w-5xl mx-auto px-6 py-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-3">
                    <div class="h-11 w-11 rounded-xl border border-slate-200 bg-white flex items-center justify-center font-bold text-slate-900 dark:bg-slate-800 dark:border-slate-700 dark:text-white">
                        <?php if ($siteLogoUrl !== ''): ?>
                            <img src="<?php echo e($siteLogoUrl); ?>" alt="Logo" class="h-full w-full object-contain rounded-lg">
                        <?php else: ?>
                            EN
                        <?php endif; ?>
                    </div>
                    <div>
                        <p class="text-lg font-display font-semibold">Confidentialité</p>
                        <p class="text-xs text-slate-500 dark:text-slate-300">Politique de confidentialité</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 text-xs font-semibold">
                    <a class="px-3 py-2 rounded-lg border border-slate-200 text-slate-700 hover:border-slate-300 dark:border-slate-700 dark:text-slate-200 dark:hover:border-slate-500" href="<?php echo e($backHref); ?>">Retour</a>
                    <a class="px-3 py-2 rounded-lg border border-slate-200 text-slate-700 hover:border-slate-300 dark:border-slate-700 dark:text-slate-200 dark:hover:border-slate-500" href="<?php echo e(site_url('cgu')); ?>">CGU</a>
                    <a class="px-3 py-2 rounded-lg border border-slate-200 text-slate-700 hover:border-slate-300 dark:border-slate-700 dark:text-slate-200 dark:hover:border-slate-500" href="<?php echo e(site_url('')); ?>">Accueil</a>
                </div>
            </div>
        </header>

        <main class="max-w-5xl mx-auto px-6 py-10">
            <div class="rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-soft shadow-slate-200/70 backdrop-blur dark:border-slate-800 dark:bg-slate-900/80 dark:shadow-slate-950/60 md:p-8">
                <div class="space-y-2">
                    <h1 class="text-2xl font-display font-semibold">Politique de confidentialité</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Dernière mise à jour : <?php echo e($lastUpdated); ?></p>
                </div>

                <div class="mt-6 space-y-6 text-sm text-slate-700 dark:text-slate-200">
                    <section class="space-y-2">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">1. Responsable du traitement</h2>
                        <p>École Numérique est responsable du traitement des données personnelles collectées sur la plateforme pour la gestion des comptes, des cours, des paiements, des certifications et du support.</p>
                    </section>

                    <section class="space-y-2">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">2. Données collectées</h2>
                        <p>Nous collectons notamment: informations de profil (nom, email), données pédagogiques (progression, examens, certificats), données de transaction, données de support et journaux techniques de sécurité.</p>
                    </section>

                    <section class="space-y-2">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">3. Finalités et bases d'utilisation</h2>
                        <p>Les données sont traitées pour fournir le service, exécuter les contrats d'achat et de formation, prévenir la fraude, assurer la sécurité, produire des statistiques internes et respecter les obligations légales.</p>
                    </section>

                    <section class="space-y-2">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">4. Paiements et prestataires</h2>
                        <p>Les paiements sont gérés par des prestataires spécialisés. La plateforme ne stocke pas les informations bancaires complètes. Nous conservons les références de transaction utiles au suivi comptable et au support.</p>
                    </section>

                    <section class="space-y-2">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">5. Partage des données</h2>
                        <p>Les données peuvent être partagées avec des sous-traitants techniques (hébergement, email, notifications, paiement) strictement pour exploiter la plateforme, ou communiquées aux autorités lorsque la loi l'impose.</p>
                    </section>

                    <section class="space-y-2">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">6. Durée de conservation</h2>
                        <p>Les données sont conservées pendant la durée nécessaire à la relation de service, puis archivées selon les obligations légales, fiscales, comptables et de sécurité applicables.</p>
                    </section>

                    <section class="space-y-2">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">7. Sécurité des données</h2>
                        <p>La plateforme applique des mesures raisonnables de sécurité (contrôle d'accès, journalisation, sauvegardes, protection des sessions). Aucun système ne garantit un risque zéro, une vigilance utilisateur reste nécessaire.</p>
                    </section>

                    <section class="space-y-2">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">8. Cookies et technologies similaires</h2>
                        <p>Nous utilisons principalement des cookies techniques indispensables à l'authentification, à la session et aux préférences d'interface. Les cookies non essentiels peuvent être ajustés selon la configuration de la plateforme.</p>
                    </section>

                    <section class="space-y-2">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">9. Vos droits</h2>
                        <p>Vous pouvez solliciter l'accès, la correction, la limitation ou la suppression de vos données, dans les limites prévues par la loi et les obligations de conservation applicables.</p>
                    </section>

                    <section class="space-y-2">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">10. Exercice des droits et contact</h2>
                        <p>Pour toute demande relative à la confidentialité, contactez-nous à <span class="font-semibold"><?php echo e(APP_FROM_EMAIL); ?></span> en précisant l'objet de votre demande et votre compte concerné.</p>
                    </section>

                    <section class="space-y-2">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">11. Mises à jour de la politique</h2>
                        <p>Cette politique peut être mise à jour pour tenir compte des évolutions techniques, légales et organisationnelles. La date de dernière mise à jour indiquée en haut de page fait foi.</p>
                    </section>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
