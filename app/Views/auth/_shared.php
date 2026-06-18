<?php
declare(strict_types=1);

$authUi = require __DIR__ . '/_auth_ui.php';
$iconFactory = $authUi['icon'] ?? null;
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

$icon = static function (string $name, string $classes = 'h-5 w-5') use ($iconFactory): string {
    return is_callable($iconFactory) ? (string) $iconFactory($name, $classes) : '';
};

$renderLogo = static function (
    string $siteLogoUrl,
    string $fallback = 'EN',
    string $alt = 'Logo École Numérique'
) use ($escape): string {
    ob_start();
    ?>
    <div class="auth-suite__logo-shell">
        <div class="auth-suite__logo-mark">
            <?php if ($siteLogoUrl !== ''): ?>
                <img src="<?php echo $escape($siteLogoUrl); ?>" alt="<?php echo $escape($alt); ?>" loading="lazy">
            <?php else: ?>
                <span><?php echo $escape($fallback); ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php

    return trim((string) ob_get_clean());
};

$renderFlash = static function (string $tone, array $messages, array $options = []) use ($icon, $escape): string {
    $messages = array_values(array_filter(array_map(static fn ($item): string => trim((string) $item), $messages), static fn (string $item): bool => $item !== ''));
    if ($messages === []) {
        return '';
    }

    $tone = in_array($tone, ['success', 'danger', 'warning', 'info'], true) ? $tone : 'info';
    $iconName = match ($tone) {
        'success' => 'badge',
        'danger' => 'shield',
        'warning' => 'bell',
        default => 'mail',
    };

    $title = trim((string) ($options['title'] ?? ''));
    $role = trim((string) ($options['role'] ?? ($tone === 'danger' ? 'alert' : 'status')));
    $live = trim((string) ($options['live'] ?? 'polite'));

    ob_start();
    ?>
    <div class="auth-message auth-message--<?php echo $escape($tone); ?>" role="<?php echo $escape($role); ?>" aria-live="<?php echo $escape($live); ?>">
        <div class="auth-message__icon"><?php echo $icon($iconName, 'h-5 w-5'); ?></div>
        <div class="auth-message__body">
            <?php if ($title !== ''): ?>
                <p class="auth-message__title"><?php echo $escape($title); ?></p>
            <?php endif; ?>
            <?php if (count($messages) === 1): ?>
                <p class="auth-message__text"><?php echo $escape($messages[0]); ?></p>
            <?php else: ?>
                <ul class="auth-message__list">
                    <?php foreach ($messages as $message): ?>
                        <li><?php echo $escape($message); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    <?php

    return trim((string) ob_get_clean());
};

$renderPanelHeader = static function (array $config) use ($renderLogo, $escape): string {
    $siteLogoUrl = trim((string) ($config['siteLogoUrl'] ?? ''));
    $logoFallback = trim((string) ($config['logoFallback'] ?? 'EN'));
    $logoAlt = trim((string) ($config['logoAlt'] ?? 'Logo École Numérique'));
    $eyebrow = trim((string) ($config['eyebrow'] ?? ''));
    $title = trim((string) ($config['title'] ?? ''));
    $subtitle = trim((string) ($config['subtitle'] ?? ''));
    $utilityHtml = (string) ($config['utilityHtml'] ?? '');

    ob_start();
    ?>
    <header class="auth-suite__panel-header">
        <div class="auth-suite__panel-brand">
            <?php echo $renderLogo($siteLogoUrl, $logoFallback, $logoAlt); ?>
            <div class="auth-suite__panel-copy">
                <?php if ($eyebrow !== ''): ?>
                    <span class="auth-suite__eyebrow"><?php echo $escape($eyebrow); ?></span>
                <?php endif; ?>
                <?php if ($title !== ''): ?>
                    <h1 class="auth-suite__panel-title"><?php echo $escape($title); ?></h1>
                <?php endif; ?>
                <?php if ($subtitle !== ''): ?>
                    <p class="auth-suite__panel-subtitle"><?php echo $escape($subtitle); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($utilityHtml !== ''): ?>
            <div class="auth-suite__panel-utility">
                <?php echo $utilityHtml; ?>
            </div>
        <?php endif; ?>
    </header>
    <?php

    return trim((string) ob_get_clean());
};

$renderAside = static function (array $config) use ($escape): string {
    $eyebrow = trim((string) ($config['eyebrow'] ?? ''));
    $title = trim((string) ($config['title'] ?? ''));
    $text = trim((string) ($config['text'] ?? ''));
    $highlights = is_array($config['highlights'] ?? null) ? array_values($config['highlights']) : [];
    $stats = is_array($config['stats'] ?? null) ? array_values($config['stats']) : [];
    $badges = is_array($config['badges'] ?? null) ? array_values($config['badges']) : [];

    ob_start();
    ?>
    <div class="auth-suite__aside-body">
        <?php if ($eyebrow !== ''): ?>
            <span class="auth-suite__eyebrow"><?php echo $escape($eyebrow); ?></span>
        <?php endif; ?>
        <?php if ($title !== ''): ?>
            <h2 class="auth-suite__aside-title"><?php echo $escape($title); ?></h2>
        <?php endif; ?>
        <?php if ($text !== ''): ?>
            <p class="auth-suite__aside-text"><?php echo $escape($text); ?></p>
        <?php endif; ?>

        <?php if ($stats !== []): ?>
            <div class="auth-suite__stats" aria-label="Repères rapides">
                <?php foreach ($stats as $stat): ?>
                    <?php $value = trim((string) ($stat['value'] ?? '')); ?>
                    <?php $label = trim((string) ($stat['label'] ?? '')); ?>
                    <?php if ($value === '' || $label === '') { continue; } ?>
                    <div class="auth-suite__stat">
                        <strong><?php echo $escape($value); ?></strong>
                        <span><?php echo $escape($label); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($highlights !== []): ?>
            <div class="auth-suite__highlights">
                <?php foreach ($highlights as $highlight): ?>
                    <?php $highlightTitle = trim((string) ($highlight['title'] ?? '')); ?>
                    <?php $highlightText = trim((string) ($highlight['text'] ?? '')); ?>
                    <?php if ($highlightTitle === '' && $highlightText === '') { continue; } ?>
                    <article class="auth-suite__highlight">
                        <?php if ($highlightTitle !== ''): ?>
                            <p class="auth-suite__highlight-title"><?php echo $escape($highlightTitle); ?></p>
                        <?php endif; ?>
                        <?php if ($highlightText !== ''): ?>
                            <p class="auth-suite__highlight-text"><?php echo $escape($highlightText); ?></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($badges !== []): ?>
            <div class="auth-suite__badge-list" aria-label="Garanties produit">
                <?php foreach ($badges as $badge): ?>
                    <?php $badge = trim((string) $badge); ?>
                    <?php if ($badge === '') { continue; } ?>
                    <span class="auth-suite__badge"><?php echo $escape($badge); ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php

    return trim((string) ob_get_clean());
};

$renderPage = static function (array $config) use ($escape): void {
    $title = trim((string) ($config['title'] ?? 'Authentification - École Numérique'));
    $pageClass = trim((string) ($config['pageClass'] ?? ''));
    $variant = trim((string) ($config['variant'] ?? 'default'));
    $layout = trim((string) ($config['layout'] ?? 'split'));
    $asideHtml = (string) ($config['asideHtml'] ?? '');
    $panelHtml = (string) ($config['panelHtml'] ?? '');
    $scripts = is_array($config['scripts'] ?? null) ? array_values($config['scripts']) : [];
    $inlineScripts = is_array($config['inlineScripts'] ?? null) ? array_values($config['inlineScripts']) : [];
    $asset = static function (string $relativePath): string {
        $relativePath = ltrim($relativePath, '/');
        $absolutePath = dirname(__DIR__, 3) . '/' . $relativePath;
        $version = is_file($absolutePath) ? (string) filemtime($absolutePath) : (string) time();

        return site_url($relativePath . '?v=' . $version);
    };
    $authCssUrl = $asset('assets/css/auth-dashboard.css');
    $tailwindBootstrapUrl = $asset('assets/js/tailwind_theme_bootstrap.js');
    $webThemeUrl = $asset('assets/js/web_theme.js');
    ?>
<!doctype html>
<html lang="fr" class="light" data-default-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="app-global-assets" content="off">
    <meta name="color-scheme" content="light dark">
    <title><?php echo $escape($title); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo $escape($tailwindBootstrapUrl); ?>"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $escape($authCssUrl); ?>">
    <script src="<?php echo $escape($webThemeUrl); ?>" defer></script>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body data-view="<?php echo $escape($pageClass !== '' ? $pageClass : 'auth'); ?>" class="app-auth-page <?php echo $escape($pageClass); ?> font-body text-surface-900 dark:text-slate-100">
    <div class="auth-suite__scene" aria-hidden="true">
        <div class="auth-suite__orb auth-suite__orb--primary"></div>
        <div class="auth-suite__orb auth-suite__orb--secondary"></div>
        <div class="auth-suite__grid"></div>
    </div>

    <main class="auth-suite auth-suite--<?php echo $escape($variant); ?> auth-suite--<?php echo $escape($layout); ?>">
        <div class="auth-suite__layout">
            <aside class="auth-suite__aside" data-animate="slide-up" data-delay="40">
                <?php echo $asideHtml; ?>
            </aside>
            <section class="auth-suite__panel" data-animate="scale-in" data-delay="120">
                <?php echo $panelHtml; ?>
            </section>
        </div>
    </main>

    <?php foreach ($scripts as $script): ?>
        <?php $script = trim((string) $script); ?>
        <?php if ($script === '') { continue; } ?>
        <script src="<?php echo $escape(site_url($script)); ?>" defer></script>
    <?php endforeach; ?>
    <?php foreach ($inlineScripts as $script): ?>
        <?php $script = trim((string) $script); ?>
        <?php if ($script === '') { continue; } ?>
        <script><?php echo $script; ?></script>
    <?php endforeach; ?>
</body>
</html>
    <?php
};

return [
    'icon' => $icon,
    'renderLogo' => $renderLogo,
    'renderFlash' => $renderFlash,
    'renderPanelHeader' => $renderPanelHeader,
    'renderAside' => $renderAside,
    'renderPage' => $renderPage,
];
