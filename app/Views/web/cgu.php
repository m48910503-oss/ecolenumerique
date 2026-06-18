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
    <title>CGU - École Numérique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo e(site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()))); ?>"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/standalone-dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/standalone-dashboard.css') ?: time()))); ?>">
    <script src="<?php echo e(site_url('assets/js/web_theme.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/web_theme.js') ?: time()))); ?>" defer></script>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="standalone-dashboard-page app-public-page min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100 font-body" data-view="web/cgu">
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
                        <p class="text-lg font-display font-semibold">CGU</p>
                        <p class="text-xs text-slate-500 dark:text-slate-300">Conditions Générales d'Utilisation</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 text-xs font-semibold">
                    <a class="px-3 py-2 rounded-lg border border-slate-200 text-slate-700 hover:border-slate-300 dark:border-slate-700 dark:text-slate-200 dark:hover:border-slate-500" href="<?php echo e($backHref); ?>">Retour</a>
                    <a class="px-3 py-2 rounded-lg border border-slate-200 text-slate-700 hover:border-slate-300 dark:border-slate-700 dark:text-slate-200 dark:hover:border-slate-500" href="<?php echo e(site_url('privacy')); ?>">Politique de confidentialité</a>
                    <a class="px-3 py-2 rounded-lg border border-slate-200 text-slate-700 hover:border-slate-300 dark:border-slate-700 dark:text-slate-200 dark:hover:border-slate-500" href="<?php echo e(site_url('')); ?>">Accueil</a>
                </div>
            </div>
        </header>

        <main class="max-w-5xl mx-auto px-6 py-10">
            <div class="rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-soft shadow-slate-200/70 backdrop-blur dark:border-slate-800 dark:bg-slate-900/80 dark:shadow-slate-950/60 md:p-8">
                <div class="space-y-2">
                    <h1 class="text-2xl font-display font-semibold">Conditions Générales d'Utilisation</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Dernière mise à jour : <?php echo e($lastUpdated); ?></p>
                </div>

                <div class="mt-6 space-y-6 text-sm text-slate-700 dark:text-slate-200">
                    <section id="cgu" class="space-y-2">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">1. Objet et champ d'application</h2>
                        <p>Les présentes conditions générales d'utilisation régissent l'accès et l'usage de la plateforme École Numérique pour les étudiants, enseignants, comptes entreprise et administrateurs.</p>
                    </section>

                    <section class="space-y-2">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">2. Acceptation des conditions</h2>
                        <p>En créant un compte, en achetant un cours, en publiant un contenu ou en utilisant une fonctionnalité de la plateforme, l'utilisateur accepte intégralement les présentes conditions.</p>
                    </section>

                    <section class="space-y-2">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">3. Compte utilisateur et sécurité</h2>
                        <p>Chaque compte est personnel. L'utilisateur s'engage à protéger ses identifiants, à fournir des informations exactes et à notifier rapidement toute utilisation non autorisée de son compte.</p>
                    </section>

                    <section class="space-y-2">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">4. Règles de conduite</h2>
                        <p>Sont interdits: la fraude, l'usurpation d'identité, les contenus illégaux, le harcèlement, la copie non autorisée de cours, les tentatives d'intrusion et tout usage contraire à la loi ou aux présentes CGU.</p>
                    </section>

                    <section class="space-y-2">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">5. Cours, contenus et licences</h2>
                        <p>L'accès à un cours donne un droit personnel, non exclusif et non transférable. Le téléchargement, la revente, la republication ou la diffusion d'un contenu pédagogique sans autorisation écrite est interdit.</p>
                    </section>

                    <section class="space-y-2">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">6. Paiements, promotions et retraits</h2>
                        <p>Les prix sont affichés en F CFA. Les remises et coupons sont soumis à leurs règles de validité. Les retraits enseignant et parrainage sont soumis aux seuils configurés par l'administration et à validation interne.</p>
                    </section>

                    <section class="space-y-2">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">7. Comptes entreprise</h2>
                        <p>Les invitations entreprise sont nominatives ou liées à un token unique. L'administration entreprise est responsable des accès attribués, du nombre de places achetées et de l'usage des comptes membres.</p>
                    </section>

                    <section class="space-y-2">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">8. Rendez-vous présentiel</h2>
                        <p>Pour les activités en présentiel, la plateforme agit comme intermédiaire technique. La responsabilité des déplacements, des conditions de sécurité et des interactions physiques incombe aux participants.</p>
                    </section>

                    <section class="space-y-2">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">9. Propriété intellectuelle</h2>
                        <p>Les marques, éléments graphiques, vidéos, exercices, certificats et documents restent protégés par le droit applicable. Toute reproduction non autorisée expose à des sanctions contractuelles et légales.</p>
                    </section>

                    <section class="space-y-2">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">10. Disponibilité et maintenance</h2>
                        <p>La plateforme vise une disponibilité continue, sans garantie absolue. Des interruptions temporaires peuvent survenir pour maintenance, mise à jour, sécurité ou incident technique.</p>
                    </section>

                    <section class="space-y-2">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">11. Suspension et suppression de compte</h2>
                        <p>En cas de non-respect des règles, la plateforme peut restreindre, suspendre ou fermer un compte, de façon provisoire ou définitive, avec conservation des traces nécessaires à la conformité et à la sécurité.</p>
                    </section>

                    <section class="space-y-2">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">12. Évolution des conditions</h2>
                        <p>Les CGU peuvent être modifiées à tout moment. La version en vigueur est celle publiée sur cette page. En continuant d'utiliser le service après mise à jour, l'utilisateur accepte les nouvelles conditions.</p>
                    </section>

                    <section class="space-y-2">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">13. Contact</h2>
                        <p>Pour toute question contractuelle ou demande de support, contactez l'équipe à l'adresse <span class="font-semibold"><?php echo e(APP_FROM_EMAIL); ?></span>.</p>
                    </section>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
