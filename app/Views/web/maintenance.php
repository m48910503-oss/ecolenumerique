<?php
$siteName = trim((string) ($siteName ?? (defined('APP_FROM_NAME') ? (string) APP_FROM_NAME : 'École Numérique')));
if ($siteName === '') {
    $siteName = 'École Numérique';
}

$siteLogoUrl = trim((string) ($siteLogoUrl ?? ''));
$maintenanceMessage = trim((string) ($maintenanceMessage ?? ''));
if ($maintenanceMessage === '') {
    $maintenanceMessage = 'Maintenance temporaire en cours. Merci de revenir dans quelques minutes.';
}

$supportEmail = trim((string) ($supportEmail ?? ''));
$supportPhone = trim((string) ($supportPhone ?? ''));
$supportWhatsapp = trim((string) ($supportWhatsapp ?? ''));
$supportPhoneLink = preg_replace('/\D+/', '', $supportPhone) ?: '';
$supportWhatsappLink = preg_replace('/\D+/', '', $supportWhatsapp) ?: '';

$supportUrl = '';
if ($supportEmail !== '') {
    $supportUrl = 'mailto:' . $supportEmail;
} elseif ($supportPhoneLink !== '') {
    $supportUrl = 'tel:' . $supportPhoneLink;
} elseif ($supportWhatsappLink !== '') {
    $supportUrl = 'https://wa.me/' . $supportWhatsappLink;
}

$standaloneCssUrl = site_url('assets/css/standalone-dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/standalone-dashboard.css') ?: time()));
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e('Maintenance en cours · ' . $siteName); ?></title>
    <meta name="app-global-assets" content="off">
    <meta name="robots" content="noindex,nofollow">
    <meta name="description" content="<?php echo e($maintenanceMessage); ?>">
    <link rel="stylesheet" href="<?php echo e($standaloneCssUrl); ?>">
    <style>
        .maintenance-page {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 1rem;
            background:
                radial-gradient(circle at top, rgba(37, 99, 235, 0.10), transparent 30%),
                linear-gradient(180deg, #f8fbff 0%, #eef4ff 100%);
        }

        .maintenance-card {
            width: min(720px, 100%);
            border: 1px solid rgba(148, 163, 184, 0.30);
            border-radius: 1.75rem;
            background: rgba(255, 255, 255, 0.92);
            box-shadow: 0 24px 70px -50px rgba(15, 23, 42, 0.35);
            padding: clamp(1.25rem, 4vw, 2rem);
            text-align: center;
            backdrop-filter: blur(10px);
        }

        .maintenance-brand {
            display: inline-flex;
            align-items: center;
            gap: 0.85rem;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .maintenance-logo {
            width: 3.35rem;
            height: 3.35rem;
            border-radius: 1rem;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(37, 99, 235, 0.08);
            border: 1px solid rgba(37, 99, 235, 0.16);
            color: var(--standalone-primary);
            flex: 0 0 auto;
        }

        .maintenance-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 0.35rem;
        }

        .maintenance-logo svg {
            width: 1.55rem;
            height: 1.55rem;
        }

        .maintenance-brand__name {
            margin: 0;
            font-size: 0.78rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            color: var(--standalone-muted);
            text-align: left;
        }

        .maintenance-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 1.15rem;
            margin: 0 auto 1rem;
            color: #d97706;
            background: rgba(245, 158, 11, 0.14);
        }

        .maintenance-icon svg {
            width: 1.9rem;
            height: 1.9rem;
        }

        .maintenance-title {
            margin: 0;
            font-size: clamp(1.6rem, 4vw, 2.25rem);
            line-height: 1.15;
            font-weight: 900;
            color: var(--standalone-text);
        }

        .maintenance-message {
            max-width: 52ch;
            margin: 0.8rem auto 0;
            color: var(--standalone-muted);
            font-size: 1rem;
            line-height: 1.65;
        }

        .maintenance-actions {
            display: flex;
            justify-content: center;
            margin-top: 1.5rem;
        }

        .maintenance-button {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            min-height: 2.9rem;
            padding: 0.75rem 1.05rem;
            border-radius: 0.95rem;
            border: 1px solid transparent;
            background: #2563eb;
            color: #fff;
            text-decoration: none;
            font-weight: 800;
            box-shadow: 0 10px 24px -18px rgba(37, 99, 235, 0.55);
        }

        .maintenance-button:hover {
            filter: brightness(1.02);
            transform: translateY(-1px);
        }

        .maintenance-button svg {
            width: 1rem;
            height: 1rem;
        }

        @media (max-width: 520px) {
            .maintenance-brand {
                display: grid;
                justify-items: center;
            }

            .maintenance-brand__name {
                text-align: center;
            }
        }
    </style>
</head>
<body class="mk-body maintenance-page" data-view="web/maintenance">
    <main class="maintenance-card">
        <div class="maintenance-brand">
            <div class="maintenance-logo" aria-hidden="true">
                <?php if ($siteLogoUrl !== ''): ?>
                    <img src="<?php echo e($siteLogoUrl); ?>" alt="">
                <?php else: ?>
                    <?php echo dashboard_icon_svg('graduation-cap'); ?>
                <?php endif; ?>
            </div>
            <div>
                <p class="maintenance-brand__name"><?php echo site_brand_name_html($siteName); ?></p>
            </div>
        </div>

        <div class="maintenance-icon" aria-hidden="true"><?php echo dashboard_icon_svg('alert-triangle'); ?></div>

        <h1 class="maintenance-title">Maintenance en cours</h1>
        <p class="maintenance-message"><?php echo e($maintenanceMessage); ?></p>

        <?php if ($supportUrl !== ''): ?>
            <div class="maintenance-actions">
                <a class="maintenance-button" href="<?php echo e($supportUrl); ?>">
                    <?php echo dashboard_icon_svg($supportEmail !== '' ? 'mail' : ($supportPhoneLink !== '' ? 'phone' : 'message-circle')); ?>
                    <span>Contacter le support</span>
                </a>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
