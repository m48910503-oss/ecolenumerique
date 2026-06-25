<?php
$publicPage = '';
$publicBrandSubtitle = 'Des formations numeriques claires, utiles et accessibles.';
$publicHeaderMetaLabel = $isLoggedIn ? 'Compte connecte' : 'Deja membre ?';
$publicHeaderSecondaryLabel = $isLoggedIn ? 'Mon profil' : 'Se connecter';
$publicHeaderSecondaryUrl = $isLoggedIn ? site_url('profile') : site_url('login');
$publicHeaderPrimaryLabel = $isLoggedIn ? 'Acceder a mon espace' : 'Creer un compte';
$publicHeaderPrimaryUrl = $isLoggedIn ? (string) ($dashboardUrl ?? site_url('student/dashboard')) : site_url('register');
$publicFooterNote = "Si un lien ne fonctionne plus, revenez au catalogue ou a l'accueil pour retrouver un parcours disponible.";

$errorTitle = trim((string) ($errorTitle ?? 'Contenu indisponible'));
$errorMessage = trim((string) ($errorMessage ?? "Le contenu demande n'est pas accessible pour le moment."));
$errorActions = is_array($errorActions ?? null) ? $errorActions : [];

require __DIR__ . '/_public_bootstrap.php';

$marketingCssUrl = site_url('assets/css/web-marketing.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/web-marketing.css') ?: time()));
$standaloneCssUrl = site_url('assets/css/standalone-dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/standalone-dashboard.css') ?: time()));
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($errorTitle); ?> | <?php echo e($siteName); ?></title>
    <meta name="app-global-assets" content="off">
    <meta name="robots" content="noindex,follow">
    <meta name="description" content="<?php echo e($errorMessage); ?>">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e($standaloneCssUrl); ?>">
    <link rel="stylesheet" href="<?php echo e($marketingCssUrl); ?>">
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="mk-body" data-view="web/public_error">
    <?php require __DIR__ . '/_public_header.php'; ?>

    <main class="mk-public-main">
        <section class="mk-shell" style="padding-top:clamp(3rem,7vw,6rem);padding-bottom:clamp(3rem,7vw,6rem);">
            <div class="mk-reveal" style="--mk-delay:0.03s">
                <?php
                $emptyStateTone = 'urgent';
                $emptyStateTitle = $errorTitle;
                $emptyStateMessage = $errorMessage;
                $emptyStateIcon = 'alert-circle';
                $emptyStateActions = $errorActions;
                require __DIR__ . '/../shared/_empty_state.php';
                ?>
            </div>
        </section>
    </main>

    <?php require __DIR__ . '/_public_footer.php'; ?>
</body>
</html>
