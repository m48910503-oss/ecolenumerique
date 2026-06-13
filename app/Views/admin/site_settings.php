<?php
$errors = is_array($errors ?? null) ? $errors : [];
$success = trim((string) ($success ?? ''));
$siteName = (string) ($siteName ?? '');
$siteTagline = (string) ($siteTagline ?? '');
$siteDescription = (string) ($siteDescription ?? '');
$supportEmail = (string) ($supportEmail ?? '');
$supportPhone = (string) ($supportPhone ?? '');
$supportWhatsapp = (string) ($supportWhatsapp ?? '');
$siteAddress = (string) ($siteAddress ?? '');
$siteLogoUrl = (string) ($siteLogoUrl ?? '');
$siteLogoPath = (string) ($siteLogoPath ?? '');
$logoMaxMb = (int) ($logoMaxMb ?? 5);
$siteFaviconUrl = (string) ($siteFaviconUrl ?? '');
$siteFaviconPath = (string) ($siteFaviconPath ?? '');
$faviconMaxMb = (int) ($faviconMaxMb ?? 2);
$seoTitle = (string) ($seoTitle ?? '');
$seoDescription = (string) ($seoDescription ?? '');
$seoKeywords = (string) ($seoKeywords ?? '');
$socialImageUrl = (string) ($socialImageUrl ?? '');
$socialImagePath = (string) ($socialImagePath ?? '');
$socialImageMaxMb = (int) ($socialImageMaxMb ?? 3);
$maintenanceEnabled = !empty($maintenanceEnabled);
$maintenanceMessage = (string) ($maintenanceMessage ?? '');
$registrationsEnabled = !empty($registrationsEnabled);
$teacherApplicationsEnabled = !empty($teacherApplicationsEnabled);
$fedapayEnabled = !empty($fedapayEnabled);
$fedapayPublicKey = (string) ($fedapayPublicKey ?? '');
$fedapaySecretKey = (string) ($fedapaySecretKey ?? '');
$fedapayEnvironment = (string) ($fedapayEnvironment ?? 'sandbox');
$fedapayWebhookSecret = (string) ($fedapayWebhookSecret ?? '');
$fedapayConfigured = $fedapayEnabled && trim($fedapayPublicKey) !== '' && trim($fedapaySecretKey) !== '';
$fedapaySecretStored = trim($fedapaySecretKey) !== '';
$fedapayWebhookSecretStored = trim($fedapayWebhookSecret) !== '';
$dgName = (string) ($dgName ?? '');
$footerText = (string) ($footerText ?? '');
$dgSignatureUrl = (string) ($dgSignatureUrl ?? '');
$dgSignaturePath = (string) ($dgSignaturePath ?? '');
$dgSignatureMaxMb = (int) ($dgSignatureMaxMb ?? 2);
$isGeneralAdmin = !empty($isGeneralAdmin);

$selectedTab = strtolower(trim((string) ($selectedTab ?? ($_POST['settings_tab'] ?? ($_GET['tab'] ?? 'identity')))));
if (!in_array($selectedTab, ['identity', 'brand', 'seo', 'access', 'payments', 'certificate'], true)) {
    $selectedTab = 'identity';
}

$tabClass = static function (string $tab) use ($selectedTab): string {
    return 'admin-settings-tab' . ($selectedTab === $tab ? ' admin-settings-tab--active' : '');
};

$panelClass = static function (string $tab) use ($selectedTab): string {
    return $selectedTab === $tab ? 'admin-settings-panel' : 'admin-settings-panel hidden';
};

$assetStatus = static function (string $value): string {
    return trim($value) !== '' ? 'Configuré' : 'À compléter';
};

$assetTone = static function (string $value): string {
    return trim($value) !== '' ? 'admin-dashboard-count--success' : 'admin-dashboard-count--warning';
};

$maskSecret = static function (string $value): string {
    $value = trim($value);
    if ($value === '') {
        return 'Non renseigné';
    }
    $tail = substr($value, -4);
    return '********' . $tail;
};

$adminNavActive = 'site_settings';
$adminTopTitle = 'Paramètres du site';
$adminTopSubtitle = 'Configuration';
$adminTopBadge = $maintenanceEnabled ? 'Maintenance active' : 'Site actif';
$adminTopBadgeTone = $maintenanceEnabled ? 'pending' : 'done';
$adminTopActions = [
    ['label' => 'Tableau de bord', 'url' => site_url('admin/dashboard'), 'tone' => 'neutral'],
];
if ($isGeneralAdmin) {
    $adminTopActions[] = ['label' => 'Admins secondaires', 'url' => site_url('admin/secondary_admins'), 'tone' => 'neutral'];
}
$adminTopSearchAction = site_url('admin/site_settings');
$adminTopSearchPlaceholder = 'Rechercher un paramètre...';
$adminPageScripts = ['admin_site_settings.js'];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres du site · Administration</title>
    <?php require __DIR__ . '/_head_assets.php'; ?>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="app-view app-role-admin min-h-screen bg-slate-50 text-slate-900 font-body dark:bg-slate-950 dark:text-slate-100" data-view="admin/site_settings" data-sidebar-desktop-toggle="1">
    <div class="admin-shell-backdrop" aria-hidden="true">
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--one"></div>
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--two"></div>
    </div>

    <div class="admin-shell-root relative z-10 flex min-h-screen">
        <?php require __DIR__ . '/_sidebar.php'; ?>
        <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

        <div class="admin-shell-main flex min-w-0 flex-1 flex-col">
            <?php require __DIR__ . '/_topbar.php'; ?>

            <main class="admin-shell-content admin-shell-dense admin-settings-page px-4 py-5 sm:px-6 sm:py-6 space-y-6" data-admin-settings-page data-default-tab="<?php echo e($selectedTab); ?>">
                <?php if ($errors !== []): ?>
                    <div class="admin-shell-alert admin-shell-alert--error">
                        <?php foreach ($errors as $err): ?>
                            <p class="inline-flex items-center gap-2"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i><?php echo e((string) $err); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success !== ''): ?>
                    <div class="admin-shell-alert admin-shell-alert--success">
                        <p class="inline-flex items-center gap-2"><i class="fas fa-check-circle" aria-hidden="true"></i><?php echo e($success); ?></p>
                    </div>
                <?php endif; ?>

                <section class="admin-settings-hero rounded-3xl p-6 text-white">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p class="inline-flex items-center gap-2 text-xs font-black uppercase tracking-[0.24em] text-white/75">
                                <i class="fas fa-cog" aria-hidden="true"></i>
                                Configuration globale
                            </p>
                            <h2 class="mt-2 text-2xl font-black sm:text-3xl">Paramètres du site</h2>
                            <p class="mt-2 max-w-3xl text-sm font-medium text-white/82">
                                Identité, SEO, paiements, certificats et pied de page.
                            </p>
                        </div>
                        <div class="admin-settings-hero__status">
                            <i class="fas <?php echo $maintenanceEnabled ? 'fa-tools' : 'fa-check-circle'; ?>" aria-hidden="true"></i>
                            <span><?php echo $maintenanceEnabled ? 'Maintenance activée' : 'Site disponible'; ?></span>
                        </div>
                    </div>
                </section>

                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                    <article class="admin-settings-kpi admin-settings-tone--blue rounded-2xl p-5">
                        <span class="admin-settings-kpi__icon"><i class="fas fa-id-card" aria-hidden="true"></i></span>
                        <p class="mt-4 text-sm font-bold text-slate-500 dark:text-slate-400">Identité</p>
                        <p class="mt-1 text-xl font-black text-slate-950 dark:text-white"><?php echo e($siteName !== '' ? $siteName : 'Non renseigné'); ?></p>
                    </article>
                    <article class="admin-settings-kpi admin-settings-tone--emerald rounded-2xl p-5">
                        <span class="admin-settings-kpi__icon"><i class="fas fa-image" aria-hidden="true"></i></span>
                        <p class="mt-4 text-sm font-bold text-slate-500 dark:text-slate-400">Marque</p>
                        <p class="mt-1 text-xl font-black text-slate-950 dark:text-white"><?php echo e($assetStatus($siteLogoPath)); ?></p>
                    </article>
                    <article class="admin-settings-kpi admin-settings-tone--indigo rounded-2xl p-5">
                        <span class="admin-settings-kpi__icon"><i class="fas fa-search" aria-hidden="true"></i></span>
                        <p class="mt-4 text-sm font-bold text-slate-500 dark:text-slate-400">SEO</p>
                        <p class="mt-1 text-xl font-black text-slate-950 dark:text-white"><?php echo e($seoTitle !== '' ? 'Configuré' : 'À compléter'); ?></p>
                    </article>
                    <article class="admin-settings-kpi admin-settings-tone--amber rounded-2xl p-5">
                        <span class="admin-settings-kpi__icon"><i class="fas fa-toggle-on" aria-hidden="true"></i></span>
                        <p class="mt-4 text-sm font-bold text-slate-500 dark:text-slate-400">Accès</p>
                        <p class="mt-1 text-xl font-black text-slate-950 dark:text-white"><?php echo $registrationsEnabled ? 'Inscriptions ouvertes' : 'Inscriptions fermées'; ?></p>
                    </article>
                    <article class="admin-settings-kpi admin-settings-tone--emerald rounded-2xl p-5">
                        <span class="admin-settings-kpi__icon"><i class="fas fa-credit-card" aria-hidden="true"></i></span>
                        <p class="mt-4 text-sm font-bold text-slate-500 dark:text-slate-400">Paiement</p>
                        <p class="mt-1 text-xl font-black text-slate-950 dark:text-white"><?php echo $fedapayConfigured ? 'FedaPay actif' : 'À configurer'; ?></p>
                    </article>
                </section>

                <form method="post" enctype="multipart/form-data" action="<?php echo e(site_url('admin/site_settings')); ?>" class="space-y-6">
                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                    <input type="hidden" name="action" value="save_site_settings">
                    <input type="hidden" name="settings_tab" value="<?php echo e($selectedTab); ?>" data-settings-active-tab>

                    <section class="admin-shell-card rounded-3xl p-4 sm:p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <h3 class="text-lg font-black inline-flex items-center gap-2">
                                    <i class="fas fa-sliders-h admin-inline-accent admin-inline-accent--info" aria-hidden="true"></i>
                                    Sections de configuration
                                </h3>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Chaque modification est enregistrée dans les paramètres globaux du site.</p>
                            </div>
                            <button type="submit" class="admin-shell-button admin-shell-button--primary inline-flex items-center gap-2">
                                <i class="fas fa-save" aria-hidden="true"></i>
                                Enregistrer les paramètres
                            </button>
                        </div>

                        <div class="admin-settings-tabs mt-5" role="tablist" aria-label="Sections des paramètres">
                            <button type="button" class="<?php echo e($tabClass('identity')); ?>" data-settings-tab="identity"><i class="fas fa-id-card" aria-hidden="true"></i>Identité</button>
                            <button type="button" class="<?php echo e($tabClass('brand')); ?>" data-settings-tab="brand"><i class="fas fa-palette" aria-hidden="true"></i>Marque</button>
                            <button type="button" class="<?php echo e($tabClass('seo')); ?>" data-settings-tab="seo"><i class="fas fa-search" aria-hidden="true"></i>SEO</button>
                            <button type="button" class="<?php echo e($tabClass('access')); ?>" data-settings-tab="access"><i class="fas fa-shield-alt" aria-hidden="true"></i>Accès</button>
                            <button type="button" class="<?php echo e($tabClass('payments')); ?>" data-settings-tab="payments"><i class="fas fa-credit-card" aria-hidden="true"></i>Paiements</button>
                            <button type="button" class="<?php echo e($tabClass('certificate')); ?>" data-settings-tab="certificate"><i class="fas fa-certificate" aria-hidden="true"></i>Certificats</button>
                        </div>
                    </section>

                    <section class="<?php echo e($panelClass('identity')); ?>" data-settings-panel="identity">
                        <div class="grid gap-5 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
                            <article class="admin-shell-card rounded-3xl p-5 space-y-5">
                                <div>
                                    <h3 class="text-lg font-black inline-flex items-center gap-2"><i class="fas fa-globe text-primary-600 dark:text-primary-300" aria-hidden="true"></i>Identité publique</h3>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Affichées sur les pages publiques et documents générés.</p>
                                </div>
                                <label class="admin-shell-field">
                                    <span class="admin-shell-label">Nom du site</span>
                                    <input name="site_name" value="<?php echo e($siteName); ?>" class="admin-shell-input" maxlength="120" required>
                                </label>
                                <label class="admin-shell-field">
                                    <span class="admin-shell-label">Slogan</span>
                                    <input name="site_tagline" value="<?php echo e($siteTagline); ?>" class="admin-shell-input" maxlength="180">
                                </label>
                                <label class="admin-shell-field">
                                    <span class="admin-shell-label">Description courte</span>
                                    <textarea name="site_description" class="admin-settings-textarea" maxlength="600" rows="5"><?php echo e($siteDescription); ?></textarea>
                                </label>
                                <label class="admin-shell-field">
                                    <span class="admin-shell-label">Texte du pied de page</span>
                                    <textarea name="footer_text" class="admin-settings-textarea" maxlength="2000" rows="5" placeholder="Ex. : © {year} École Numérique - Tous droits réservés."><?php echo e($footerText); ?></textarea>
                                </label>
                            </article>

                            <article class="admin-shell-card rounded-3xl p-5 space-y-5">
                                <div>
                                    <h3 class="text-lg font-black inline-flex items-center gap-2"><i class="fas fa-headset text-cyan-600 dark:text-cyan-300" aria-hidden="true"></i>Support & contact</h3>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Informations utiles pour les pages d’aide, les e-mails et les documents administratifs.</p>
                                </div>
                                <label class="admin-shell-field">
                                    <span class="admin-shell-label">E-mail de support</span>
                                    <input type="email" name="support_email" value="<?php echo e($supportEmail); ?>" class="admin-shell-input" placeholder="support@example.com">
                                </label>
                                <label class="admin-shell-field">
                                    <span class="admin-shell-label">Téléphone</span>
                                    <input name="support_phone" value="<?php echo e($supportPhone); ?>" class="admin-shell-input" maxlength="40">
                                </label>
                                <label class="admin-shell-field">
                                    <span class="admin-shell-label">WhatsApp</span>
                                    <input name="support_whatsapp" value="<?php echo e($supportWhatsapp); ?>" class="admin-shell-input" maxlength="40">
                                </label>
                                <label class="admin-shell-field">
                                    <span class="admin-shell-label">Adresse</span>
                                    <textarea name="site_address" class="admin-settings-textarea" maxlength="240" rows="4"><?php echo e($siteAddress); ?></textarea>
                                </label>
                            </article>
                        </div>
                    </section>

                    <section class="<?php echo e($panelClass('brand')); ?>" data-settings-panel="brand">
                        <div class="grid gap-5 xl:grid-cols-3">
                            <article class="admin-settings-upload-card rounded-3xl p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h3 class="text-lg font-black inline-flex items-center gap-2"><i class="fas fa-image text-primary-600 dark:text-primary-300" aria-hidden="true"></i>Logo</h3>
                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">PNG, JPG ou WebP. Taille maximale : <?php echo e((string) $logoMaxMb); ?> Mo.</p>
                                    </div>
                                    <span class="admin-dashboard-count <?php echo e($assetTone($siteLogoPath)); ?>"><?php echo e($assetStatus($siteLogoPath)); ?></span>
                                </div>
                                <div class="admin-settings-preview mt-5">
                                    <?php if ($siteLogoUrl !== ''): ?>
                                        <img src="<?php echo e($siteLogoUrl); ?>" alt="Logo actuel">
                                    <?php else: ?>
                                        <i class="fas fa-image" aria-hidden="true"></i>
                                    <?php endif; ?>
                                </div>
                                <label class="admin-settings-upload-control mt-4">
                                    <input type="file" name="site_logo" accept="image/png,image/jpeg,image/webp" data-settings-file="logo">
                                    <span class="admin-settings-upload-control__button"><i class="fas fa-upload" aria-hidden="true"></i>Choisir un fichier</span>
                                    <span class="admin-settings-upload-control__name" data-settings-file-name="logo">Aucun fichier choisi</span>
                                </label>
                                <?php if ($siteLogoPath !== ''): ?>
                                    <div class="admin-settings-remove-box mt-3" data-remove-box="logo">
                                        <input type="hidden" name="remove_logo" value="0" data-remove-input="logo">
                                        <button type="button" class="admin-settings-remove-button" data-remove-trigger="logo" data-remove-label="Supprimer" data-cancel-label="Annuler la suppression">
                                            <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                            <span>Supprimer</span>
                                        </button>
                                        <p class="admin-settings-remove-note hidden" data-remove-note="logo">Le logo sera supprimé uniquement après l’enregistrement.</p>
                                    </div>
                                <?php endif; ?>
                            </article>

                            <article class="admin-settings-upload-card rounded-3xl p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h3 class="text-lg font-black inline-flex items-center gap-2"><i class="fas fa-star text-amber-600 dark:text-amber-300" aria-hidden="true"></i>Favicon</h3>
                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Format carré recommandé. Taille maximale : <?php echo e((string) $faviconMaxMb); ?> Mo.</p>
                                    </div>
                                    <span class="admin-dashboard-count <?php echo e($assetTone($siteFaviconPath)); ?>"><?php echo e($assetStatus($siteFaviconPath)); ?></span>
                                </div>
                                <div class="admin-settings-preview admin-settings-preview--small mt-5">
                                    <?php if ($siteFaviconUrl !== ''): ?>
                                        <img src="<?php echo e($siteFaviconUrl); ?>" alt="Favicon actuel">
                                    <?php else: ?>
                                        <i class="fas fa-star" aria-hidden="true"></i>
                                    <?php endif; ?>
                                </div>
                                <label class="admin-settings-upload-control mt-4">
                                    <input type="file" name="site_favicon" accept="image/png,image/jpeg,image/webp" data-settings-file="favicon">
                                    <span class="admin-settings-upload-control__button"><i class="fas fa-upload" aria-hidden="true"></i>Choisir un fichier</span>
                                    <span class="admin-settings-upload-control__name" data-settings-file-name="favicon">Aucun fichier choisi</span>
                                </label>
                                <?php if ($siteFaviconPath !== ''): ?>
                                    <div class="admin-settings-remove-box mt-3" data-remove-box="favicon">
                                        <input type="hidden" name="remove_favicon" value="0" data-remove-input="favicon">
                                        <button type="button" class="admin-settings-remove-button" data-remove-trigger="favicon" data-remove-label="Supprimer" data-cancel-label="Annuler la suppression">
                                            <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                            <span>Supprimer</span>
                                        </button>
                                        <p class="admin-settings-remove-note hidden" data-remove-note="favicon">Le favicon sera supprimé uniquement après l’enregistrement.</p>
                                    </div>
                                <?php endif; ?>
                            </article>

                            <article class="admin-settings-upload-card rounded-3xl p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h3 class="text-lg font-black inline-flex items-center gap-2"><i class="fas fa-share-alt text-indigo-600 dark:text-indigo-300" aria-hidden="true"></i>Image sociale</h3>
                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Image utilisée pour les aperçus de partage. Max : <?php echo e((string) $socialImageMaxMb); ?> Mo.</p>
                                    </div>
                                    <span class="admin-dashboard-count <?php echo e($assetTone($socialImagePath)); ?>"><?php echo e($assetStatus($socialImagePath)); ?></span>
                                </div>
                                <div class="admin-settings-preview mt-5">
                                    <?php if ($socialImageUrl !== ''): ?>
                                        <img src="<?php echo e($socialImageUrl); ?>" alt="Image sociale actuelle">
                                    <?php else: ?>
                                        <i class="fas fa-share-alt" aria-hidden="true"></i>
                                    <?php endif; ?>
                                </div>
                                <label class="admin-settings-upload-control mt-4">
                                    <input type="file" name="seo_social_image" accept="image/png,image/jpeg,image/webp" data-settings-file="social">
                                    <span class="admin-settings-upload-control__button"><i class="fas fa-upload" aria-hidden="true"></i>Choisir un fichier</span>
                                    <span class="admin-settings-upload-control__name" data-settings-file-name="social">Aucun fichier choisi</span>
                                </label>
                                <?php if ($socialImagePath !== ''): ?>
                                    <div class="admin-settings-remove-box mt-3" data-remove-box="social">
                                        <input type="hidden" name="remove_social_image" value="0" data-remove-input="social">
                                        <button type="button" class="admin-settings-remove-button" data-remove-trigger="social" data-remove-label="Supprimer" data-cancel-label="Annuler la suppression">
                                            <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                            <span>Supprimer</span>
                                        </button>
                                        <p class="admin-settings-remove-note hidden" data-remove-note="social">L’image sociale sera supprimée uniquement après l’enregistrement.</p>
                                    </div>
                                <?php endif; ?>
                            </article>
                        </div>
                    </section>

                    <section class="<?php echo e($panelClass('seo')); ?>" data-settings-panel="seo">
                        <article class="admin-shell-card rounded-3xl p-5 space-y-5">
                            <div>
                                <h3 class="text-lg font-black inline-flex items-center gap-2"><i class="fas fa-search text-primary-600 dark:text-primary-300" aria-hidden="true"></i>Référencement naturel</h3>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Ces champs alimentent les métadonnées publiques quand les pages les utilisent.</p>
                            </div>
                            <div class="grid gap-4 xl:grid-cols-2">
                                <label class="admin-shell-field">
                                    <span class="admin-shell-label">Titre SEO</span>
                                    <input name="seo_title" value="<?php echo e($seoTitle); ?>" class="admin-shell-input" maxlength="160">
                                </label>
                                <label class="admin-shell-field">
                                    <span class="admin-shell-label">Mots-clés</span>
                                    <input name="seo_keywords" value="<?php echo e($seoKeywords); ?>" class="admin-shell-input" maxlength="300" placeholder="formation, numérique, cours en ligne">
                                </label>
                            </div>
                            <label class="admin-shell-field">
                                <span class="admin-shell-label">Description SEO</span>
                                <textarea name="seo_description" class="admin-settings-textarea" maxlength="320" rows="5"><?php echo e($seoDescription); ?></textarea>
                            </label>
                        </article>
                    </section>

                    <section class="<?php echo e($panelClass('access')); ?>" data-settings-panel="access">
                        <div class="grid gap-5 xl:grid-cols-3">
                            <article class="admin-settings-toggle-card rounded-3xl p-5">
                                <i class="fas fa-user-plus" aria-hidden="true"></i>
                                <div>
                                    <h3>Inscriptions apprenants</h3>
                                    <p>Autorise ou bloque les nouvelles inscriptions des apprenants.</p>
                                </div>
                                <label class="admin-settings-switch">
                                    <input type="checkbox" name="registrations_enabled" value="1" <?php echo $registrationsEnabled ? 'checked' : ''; ?>>
                                    <span></span>
                                </label>
                            </article>

                            <article class="admin-settings-toggle-card rounded-3xl p-5">
                                <i class="fas fa-chalkboard-teacher" aria-hidden="true"></i>
                                <div>
                                    <h3>Candidatures enseignants</h3>
                                    <p>Permet aux utilisateurs d’envoyer une candidature enseignant.</p>
                                </div>
                                <label class="admin-settings-switch">
                                    <input type="checkbox" name="teacher_applications_enabled" value="1" <?php echo $teacherApplicationsEnabled ? 'checked' : ''; ?>>
                                    <span></span>
                                </label>
                            </article>

                            <article class="admin-settings-toggle-card rounded-3xl p-5">
                                <i class="fas fa-tools" aria-hidden="true"></i>
                                <div>
                                    <h3>Mode maintenance</h3>
                                    <p>Indique que la plateforme est en maintenance dans les paramètres globaux.</p>
                                </div>
                                <label class="admin-settings-switch admin-settings-switch--warning">
                                    <input type="checkbox" name="maintenance_enabled" value="1" <?php echo $maintenanceEnabled ? 'checked' : ''; ?>>
                                    <span></span>
                                </label>
                            </article>
                        </div>

                        <article class="admin-shell-card rounded-3xl p-5 space-y-4">
                            <h3 class="text-lg font-black inline-flex items-center gap-2"><i class="fas fa-bullhorn text-amber-600 dark:text-amber-300" aria-hidden="true"></i>Message de maintenance</h3>
                            <textarea name="maintenance_message" class="admin-settings-textarea" maxlength="500" rows="5"><?php echo e($maintenanceMessage); ?></textarea>
                        </article>
                    </section>

                    <section class="<?php echo e($panelClass('payments')); ?>" data-settings-panel="payments">
                        <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
                            <article class="admin-shell-card rounded-3xl p-5 space-y-5">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h3 class="text-lg font-black inline-flex items-center gap-2"><i class="fas fa-mobile-alt text-primary-600 dark:text-primary-300" aria-hidden="true"></i>FedaPay Mobile Money</h3>
                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Active le paiement Mobile Money dans le checkout lorsque les clés sont valides.</p>
                                    </div>
                                    <span class="admin-dashboard-count <?php echo $fedapayConfigured ? 'admin-dashboard-count--success' : 'admin-dashboard-count--warning'; ?>">
                                        <?php echo $fedapayConfigured ? 'Actif' : ($fedapayEnabled ? 'Incomplet' : 'Désactivé'); ?>
                                    </span>
                                </div>

                                <article class="admin-settings-toggle-card rounded-3xl p-5">
                                    <i class="fas fa-toggle-on" aria-hidden="true"></i>
                                    <div>
                                        <h3>Activer FedaPay</h3>
                                        <p>Visible seulement avec clé publique et clé secrète.</p>
                                    </div>
                                    <label class="admin-settings-switch">
                                        <input type="checkbox" name="fedapay_enabled" value="1" <?php echo $fedapayEnabled ? 'checked' : ''; ?>>
                                        <span></span>
                                    </label>
                                </article>

                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm leading-6 text-slate-600 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-300">
                                    <p class="font-bold text-slate-900 dark:text-white">Impact checkout</p>
                                    <p class="mt-1">Sans configuration complète, le panier indiquera “Paiement indisponible”.</p>
                                </div>
                            </article>

                            <article class="admin-shell-card rounded-3xl p-5 space-y-5">
                                <div>
                                    <h3 class="text-lg font-black inline-flex items-center gap-2"><i class="fas fa-key text-cyan-600 dark:text-cyan-300" aria-hidden="true"></i>Clés API</h3>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Ces valeurs remplacent la configuration serveur.</p>
                                </div>

                                <label class="admin-shell-field">
                                    <span class="admin-shell-label">Environnement</span>
                                    <select name="fedapay_environment" class="admin-shell-input">
                                        <option value="sandbox" <?php echo $fedapayEnvironment === 'sandbox' ? 'selected' : ''; ?>>Sandbox</option>
                                        <option value="live" <?php echo $fedapayEnvironment === 'live' ? 'selected' : ''; ?>>Live / Production</option>
                                        <option value="dev" <?php echo $fedapayEnvironment === 'dev' ? 'selected' : ''; ?>>Développement</option>
                                    </select>
                                </label>

                                <label class="admin-shell-field">
                                    <span class="admin-shell-label">Clé publique</span>
                                    <input name="fedapay_public_key" value="<?php echo e($fedapayPublicKey); ?>" class="admin-shell-input" maxlength="255" autocomplete="off" placeholder="pk_sandbox_...">
                                </label>

                                <label class="admin-shell-field">
                                    <span class="admin-shell-label">Clé secrète</span>
                                    <input type="password" name="fedapay_secret_key" value="" class="admin-shell-input" maxlength="255" autocomplete="off" placeholder="<?php echo $fedapaySecretStored ? 'Nouvelle clé ou laisser vide' : 'sk_sandbox_...'; ?>">
                                    <span class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">
                                        <?php echo $fedapaySecretStored ? 'Clé enregistrée : ' . e($maskSecret($fedapaySecretKey)) . '. Laissez vide pour la conserver.' : 'Aucune clé secrète enregistrée.'; ?>
                                    </span>
                                </label>
                                <?php if ($fedapaySecretStored): ?>
                                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 dark:text-slate-300">
                                        <input type="checkbox" name="fedapay_secret_key_clear" value="1" class="h-4 w-4 rounded border-slate-300 text-primary-600">
                                        Supprimer la clé secrète enregistrée
                                    </label>
                                <?php endif; ?>

                                <label class="admin-shell-field">
                                    <span class="admin-shell-label">Secret webhook</span>
                                    <input type="password" name="fedapay_webhook_secret" value="" class="admin-shell-input" maxlength="255" autocomplete="off" placeholder="<?php echo $fedapayWebhookSecretStored ? 'Nouveau secret ou laisser vide' : 'whsec_...'; ?>">
                                    <span class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">
                                        <?php echo $fedapayWebhookSecretStored ? 'Secret enregistré : ' . e($maskSecret($fedapayWebhookSecret)) . '. Laissez vide pour le conserver.' : 'Aucun secret webhook enregistré.'; ?>
                                    </span>
                                </label>
                                <?php if ($fedapayWebhookSecretStored): ?>
                                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 dark:text-slate-300">
                                        <input type="checkbox" name="fedapay_webhook_secret_clear" value="1" class="h-4 w-4 rounded border-slate-300 text-primary-600">
                                        Supprimer le secret webhook enregistré
                                    </label>
                                <?php endif; ?>

                                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
                                    <p class="font-bold">À vérifier dans FedaPay</p>
                                    <p class="mt-1">Renseignez dans votre tableau de bord FedaPay l’URL webhook : <?php echo e(site_url('api/fedapay_webhook')); ?></p>
                                </div>
                            </article>
                        </div>
                    </section>

                    <section class="<?php echo e($panelClass('certificate')); ?>" data-settings-panel="certificate">
                        <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
                            <article class="admin-shell-card rounded-3xl p-5 space-y-5">
                                <div>
                                    <h3 class="text-lg font-black inline-flex items-center gap-2"><i class="fas fa-certificate text-primary-600 dark:text-primary-300" aria-hidden="true"></i>Certificats officiels</h3>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Nom et signature affichés sur les certificats générés par la plateforme.</p>
                                </div>
                                <a href="<?php echo e(site_url('admin/certificate_preview')); ?>" class="admin-shell-button admin-shell-button--secondary inline-flex items-center gap-2">
                                    <i class="fas fa-eye" aria-hidden="true"></i>
                                    Voir l’exemplaire
                                </a>
                                <label class="admin-shell-field">
                                    <span class="admin-shell-label">Nom du directeur général</span>
                                    <input name="dg_name" value="<?php echo e($dgName); ?>" class="admin-shell-input" maxlength="120" required>
                                </label>
                            </article>

                            <article class="admin-settings-upload-card rounded-3xl p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h3 class="text-lg font-black inline-flex items-center gap-2"><i class="fas fa-signature text-primary-600 dark:text-primary-300" aria-hidden="true"></i>Signature</h3>
                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">PNG, JPG ou WebP. Taille maximale : <?php echo e((string) $dgSignatureMaxMb); ?> Mo.</p>
                                    </div>
                                    <span class="admin-dashboard-count <?php echo e($assetTone($dgSignaturePath)); ?>"><?php echo e($assetStatus($dgSignaturePath)); ?></span>
                                </div>
                                <div class="admin-settings-preview admin-settings-preview--signature mt-5">
                                    <?php if ($dgSignatureUrl !== ''): ?>
                                        <img src="<?php echo e($dgSignatureUrl); ?>" alt="Signature actuelle">
                                    <?php else: ?>
                                        <i class="fas fa-signature" aria-hidden="true"></i>
                                    <?php endif; ?>
                                </div>
                                <label class="admin-settings-upload-control mt-4">
                                    <input type="file" name="dg_signature" accept="image/png,image/jpeg,image/webp" data-settings-file="signature">
                                    <span class="admin-settings-upload-control__button"><i class="fas fa-upload" aria-hidden="true"></i>Choisir un fichier</span>
                                    <span class="admin-settings-upload-control__name" data-settings-file-name="signature">Aucun fichier choisi</span>
                                </label>
                                <?php if ($dgSignaturePath !== ''): ?>
                                    <div class="admin-settings-remove-box mt-3" data-remove-box="signature">
                                        <input type="hidden" name="remove_dg_signature" value="0" data-remove-input="signature">
                                        <button type="button" class="admin-settings-remove-button" data-remove-trigger="signature" data-remove-label="Supprimer" data-cancel-label="Annuler la suppression">
                                            <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                            <span>Supprimer</span>
                                        </button>
                                        <p class="admin-settings-remove-note hidden" data-remove-note="signature">La signature sera supprimée uniquement après l’enregistrement.</p>
                                    </div>
                                <?php endif; ?>
                            </article>
                        </div>
                    </section>

                    <div class="admin-settings-savebar rounded-3xl p-4">
                        <div>
                            <p class="font-black text-slate-900 dark:text-white">Enregistrer la configuration</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Les paramètres sont appliqués après validation du formulaire.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a class="admin-shell-button admin-shell-button--secondary inline-flex items-center gap-2" href="<?php echo e(site_url('admin/dashboard')); ?>">
                                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                Retour
                            </a>
                            <button class="admin-shell-button admin-shell-button--primary inline-flex items-center gap-2" type="submit">
                                <i class="fas fa-save" aria-hidden="true"></i>
                                Enregistrer
                            </button>
                        </div>
                    </div>
                </form>
            </main>
        </div>
    </div>
</body>
</html>
