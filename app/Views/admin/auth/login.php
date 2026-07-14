<?php
$auth = require dirname(__DIR__, 2) . '/auth/_shared.php';
$authIcon = $auth['icon'];
$renderFlash = $auth['renderFlash'];
$renderPanelHeader = $auth['renderPanelHeader'];
$renderAside = $auth['renderAside'];
$renderPage = $auth['renderPage'];
$authIconClasses = 'h-5 w-5';
$successMessage = trim((string) ($successMessage ?? ''));

ob_start(); 
?>
<div class="auth-suite__utility-group">
    <a class="auth-suite__ghost-link" href="<?php echo e(site_url('admin/secondary_login')); ?>">Accès secondaire</a>
    <button id="theme-toggle" type="button" class="auth-suite__theme-toggle" aria-label="Changer le thème" aria-pressed="false">
        <span data-theme-label aria-hidden="true"></span>
    </button>
</div>
<?php
$utilityHtml = (string) ob_get_clean();

ob_start();
echo $renderPanelHeader([
    'siteLogoUrl' => (string) $siteLogoUrl,
    'logoFallback' => 'ADM',
    'logoAlt' => 'Logo administration École Numérique',
    'eyebrow' => 'Back-office central',
    'title' => 'Connexion administrateur',
    'subtitle' => 'Accès principal aux modules sensibles.',
    'utilityHtml' => $utilityHtml,
]);

if (!empty($errors)) {
    echo $renderFlash('danger', array_map(static fn ($error): string => (string) $error, $errors), [
        'title' => 'Connexion impossible',
        'role' => 'alert',
    ]);
}

if ($successMessage !== '') {
    echo $renderFlash('success', [$successMessage], [
        'title' => 'Confirmation',
        'role' => 'status',
    ]);
}
?>
<form method="post" action="<?php echo e(site_url('admin/login')); ?>" class="auth-form-v2 auth-form-v2--stack" data-auth-form>
    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">

    <section class="auth-admin-entry auth-admin-entry--primary" aria-label="Contexte de connexion administrateur">
        <div class="auth-admin-entry__copy">
            <span class="auth-admin-entry__eyebrow">Accès principal</span>
            <h2 class="auth-admin-entry__title">Un seul formulaire pour ouvrir l’ensemble du back-office.</h2>
            <p class="auth-admin-entry__text">Réservé au compte administrateur général.</p>
        </div>
        <div class="auth-admin-entry__chips" aria-label="Modules clés">
            <span class="auth-admin-entry__chip"><?php echo $authIcon('shield', $authIconClasses); ?>Pilotage global</span>
            <span class="auth-admin-entry__chip"><?php echo $authIcon('book', $authIconClasses); ?>Catalogue</span>
            <span class="auth-admin-entry__chip"><?php echo $authIcon('bell', $authIconClasses); ?>Support</span>
        </div>
    </section>

    <div class="auth-form-v2__field" data-auth-field>
        <label class="auth-form-v2__label" for="email">
            <?php echo $authIcon('mail', $authIconClasses); ?>
            <span>Adresse e-mail</span>
        </label>
        <div class="auth-form-v2__input-wrap">
            <span class="auth-form-v2__input-icon"><?php echo $authIcon('mail', $authIconClasses); ?></span>
            <input class="auth-form-v2__control" type="email" id="email" name="email" value="<?php echo e((string) $oldEmail); ?>" autocomplete="username" inputmode="email" autocapitalize="off" spellcheck="false" enterkeyhint="next" placeholder="admin@domaine.com" required autofocus>
        </div>
        <p class="auth-form-v2__hint">Adresse du compte administrateur principal uniquement.</p>
        <p class="auth-form-v2__error" data-field-error hidden></p>
    </div>

    <div class="auth-form-v2__field" data-auth-field>
        <label class="auth-form-v2__label" for="password">
            <?php echo $authIcon('lock', $authIconClasses); ?>
            <span>Mot de passe</span>
        </label>
        <div class="auth-form-v2__input-wrap auth-form-v2__input-wrap--password">
            <span class="auth-form-v2__input-icon"><?php echo $authIcon('lock', $authIconClasses); ?></span>
            <input class="auth-form-v2__control" type="password" id="password" name="password" autocomplete="current-password" enterkeyhint="go" placeholder="Votre mot de passe" required>
            <button type="button" class="auth-form-v2__toggle" data-password-toggle="#password" data-label-show="Afficher" data-label-hide="Masquer">
                <span data-password-toggle-label>Afficher</span>
            </button>
        </div>
        <p class="auth-form-v2__hint">Le compte doit être actif et l’adresse e-mail déjà vérifiée.</p>
        <p class="auth-form-v2__error" data-field-error hidden></p>
    </div>

    <button class="auth-form-v2__submit auth-form-v2__submit--primary" type="submit" data-loading-label="Ouverture du back-office...">
        <?php echo $authIcon('spark', $authIconClasses); ?>
        <span>Ouvrir le back-office</span>
    </button>
</form>

<section class="auth-admin-access-grid" aria-label="Ce que permet cet accès">
    <article class="auth-admin-access-card">
        <div class="auth-admin-access-card__icon"><?php echo $authIcon('shield', $authIconClasses); ?></div>
        <strong>Accès complet</strong>
        <p>Tableaux de bord, modération, ventes et réglages critiques au même endroit.</p>
    </article>
    <article class="auth-admin-access-card">
        <div class="auth-admin-access-card__icon"><?php echo $authIcon('badge', $authIconClasses); ?></div>
        <strong>Rôle contrôlé</strong>
        <p>Seuls les profils marqués administrateurs peuvent franchir cette entrée.</p>
    </article>
    <article class="auth-admin-access-card">
        <div class="auth-admin-access-card__icon"><?php echo $authIcon('bell', $authIconClasses); ?></div>
        <strong>Flux séparé</strong>
        <p>Les accès délégués restent sur une page dédiée pour garder un parcours plus net.</p>
    </article>
</section>

<div class="auth-form-v2__footer-note">
    Besoin d’un accès limité ?
    <a href="<?php echo e(site_url('admin/secondary_login')); ?>">Connexion administrateur secondaire</a>
</div>
<?php
$panelHtml = (string) ob_get_clean();

$asideHtml = $renderAside([
    'eyebrow' => 'Direction plateforme',
    'title' => 'Parcours principal court et clair.',
    'text' => 'Accès central aux modules sensibles.',
    'stats' => [
        ['value' => '1', 'label' => 'compte principal'],
        ['value' => '4', 'label' => 'zones critiques pilotées'],
    ],
    'highlights' => [
        ['title' => 'Hiérarchie nette', 'text' => 'Le CTA principal domine sans mettre les accès délégués au même niveau visuel.'],
        ['title' => 'Lecture rapide', 'text' => 'Le contexte, les prérequis et la portée de l’accès se comprennent en quelques secondes.'],
        ['title' => 'Même base UI', 'text' => 'Le module admin reste aligné avec le socle auth tout en ayant un ton plus directionnel.'],
    ],
    'badges' => ['Pilotage global', 'Support', 'Catalogue', 'Paramètres'],
]);

$renderPage([
    'title' => 'Administration - Connexion',
    'pageClass' => 'auth-page--admin-login',
    'variant' => 'admin',
    'layout' => 'split',
    'asideHtml' => $asideHtml,
    'panelHtml' => $panelHtml,
    'scripts' => [
        'assets/js/togglePassword.js',
        'assets/js/authForms.js',
    ],
]);
