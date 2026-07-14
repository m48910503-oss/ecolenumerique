<?php
$publicPage = '';
$publicBrandSubtitle = 'Des formations numeriques claires, utiles et accessibles.';
$publicHeaderMetaLabel = $isLoggedIn ? 'Compte connecte' : 'Deja membre ?';
$publicHeaderSecondaryLabel = $isLoggedIn ? 'Tableau de bord' : 'Se connecter';
$publicHeaderSecondaryUrl = $isLoggedIn ? (string) ($dashboardUrl ?? site_url('student/dashboard')) : site_url('login');
$publicHeaderPrimaryLabel = $isLoggedIn ? 'Acceder a mon espace' : 'Creer un compte';
$publicHeaderPrimaryUrl = $isLoggedIn ? (string) ($dashboardUrl ?? site_url('student/dashboard')) : site_url('register');
$publicFooterNote = "Si un lien ne fonctionne plus, revenez au catalogue ou a l'accueil pour retrouver un parcours disponible.";

$errorTitle = trim((string) ($errorTitle ?? 'Contenu indisponible'));
$errorMessage = trim((string) ($errorMessage ?? "Le contenu demande n'est pas accessible pour le moment."));
$errorActions = is_array($errorActions ?? null) ? $errorActions : [];
$primaryErrorActions = array_slice($errorActions, 0, 2);
$secondaryErrorActions = array_slice($errorActions, 2);

$errorActionClass = static function (string $variant): string {
    return match ($variant) {
        'primary' => 'mk-btn mk-btn--primary',
        'danger' => 'mk-btn mk-btn--secondary',
        default => 'mk-btn mk-btn--secondary',
    };
};

require __DIR__ . '/_public_bootstrap.php';

$marketingCssUrl = site_url('assets/css/web-marketing.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/web-marketing.css') ?: time()));
$publicScaleCssUrl = site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()));
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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo e($marketingCssUrl); ?>">
    <link rel="stylesheet" href="<?php echo e($publicScaleCssUrl); ?>">
</head>
<body class="mk-body mk-body--public-error" data-view="web/public_error">
    <?php require __DIR__ . '/_public_header.php'; ?>

    <main class="mk-public-main">
        <section class="mk-shell mk-public-error">
            <article class="mk-public-error__card mk-reveal" style="--mk-delay:0.03s" aria-labelledby="public-error-title">
                <div class="mk-public-error__icon" aria-hidden="true">
                    <?php echo dashboard_icon_svg('alert-triangle'); ?>
                </div>

                <div class="mk-public-error__copy">
                    <h1 id="public-error-title" class="mk-public-error__title"><?php echo e($errorTitle); ?></h1>
                    <?php if ($errorMessage !== ''): ?>
                        <p class="mk-public-error__message"><?php echo e($errorMessage); ?></p>
                    <?php endif; ?>
                </div>

                <?php if ($primaryErrorActions !== []): ?>
                    <div class="mk-public-error__actions">
                        <?php foreach ($primaryErrorActions as $action): ?>
                            <?php
                            $label = trim((string) ($action['label'] ?? ''));
                            $url = (string) ($action['url'] ?? '#');
                            $actionIcon = trim((string) ($action['icon'] ?? ''));
                            $variant = trim((string) ($action['variant'] ?? 'secondary'));
                            if ($label === '') {
                                continue;
                            }
                            ?>
                            <a href="<?php echo e($url); ?>" class="<?php echo e($errorActionClass($variant)); ?>">
                                <?php if ($actionIcon !== ''): ?>
                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg($actionIcon); ?></span>
                                <?php endif; ?>
                                <span><?php echo e($label); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($secondaryErrorActions !== []): ?>
                    <div class="mk-public-error__links" aria-label="Actions secondaires">
                        <?php foreach ($secondaryErrorActions as $action): ?>
                            <?php
                            $label = trim((string) ($action['label'] ?? ''));
                            $url = (string) ($action['url'] ?? '#');
                            $actionIcon = trim((string) ($action['icon'] ?? ''));
                            if ($label === '') {
                                continue;
                            }
                            ?>
                            <a href="<?php echo e($url); ?>" class="mk-public-error__link">
                                <?php if ($actionIcon !== ''): ?>
                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg($actionIcon); ?></span>
                                <?php endif; ?>
                                <span><?php echo e($label); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>
        </section>
    </main>

    <?php require __DIR__ . '/_public_footer.php'; ?>
</body>
</html>
