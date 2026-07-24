<?php
$auth = require dirname(__DIR__, 2) . '/auth/_shared.php';
$authIcon = $auth['icon'];
$renderFlash = $auth['renderFlash'];
$renderPanelHeader = $auth['renderPanelHeader'];
$renderAside = $auth['renderAside'];
$renderPage = $auth['renderPage'];
$authIconClasses = 'h-5 w-5';

ob_start(); 
?>
<div class="auth-suite__utility-group">
    <a class="auth-suite__ghost-link" href="<?php echo e((string) ($adminLoginUrl ?? site_url('admin/login'))); ?>">Accès principal</a>
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
    'eyebrow' => 'Accès délégué',
    'title' => 'Connexion administrateur secondaire',
    'subtitle' => 'Entrée réservée aux comptes limités aux modules explicitement autorisés.',
    'utilityHtml' => $utilityHtml,
]);

if (!empty($errors)) {
    echo $renderFlash('danger', array_map(static fn ($error): string => (string) $error, $errors), [
        'title' => 'Connexion impossible',
        'role' => 'alert',
    ]);
}
?>
<form method="post" action="<?php echo e(site_url('admin/secondary_login')); ?>" class="auth-form-v2 auth-form-v2--stack" data-auth-form>
    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">

    <section class="auth-admin-entry auth-admin-entry--secondary" aria-label="Contexte de connexion secondaire">
        <div class="auth-admin-entry__copy">
            <span class="auth-admin-entry__eyebrow">Accès ciblé</span>
            <h2 class="auth-admin-entry__title">Un parcours distinct pour les équipes qui n’ont pas besoin du back-office complet.</h2>
            <p class="auth-admin-entry__text">Après connexion, le compte rejoint son premier module autorisé.</p>
        </div>
        <div class="auth-admin-entry__chips" aria-label="Principes d'accès">
            <span class="auth-admin-entry__chip"><?php echo $authIcon('badge', $authIconClasses); ?>Permissions par module</span>
            <span class="auth-admin-entry__chip"><?php echo $authIcon('arrow-right', $authIconClasses); ?>Redirection automatique</span>
            <span class="auth-admin-entry__chip"><?php echo $authIcon('shield', $authIconClasses); ?>Portée réduite</span>
        </div>
    </section>

    <div class="auth-form-v2__field" data-auth-field>
        <label class="auth-form-v2__label" for="email">
            <?php echo $authIcon('mail', $authIconClasses); ?>
            <span>Adresse e-mail</span>
        </label>
        <div class="auth-form-v2__input-wrap">
            <span class="auth-form-v2__input-icon"><?php echo $authIcon('mail', $authIconClasses); ?></span>
            <input class="auth-form-v2__control" type="email" id="email" name="email" value="<?php echo e((string) ($oldEmail ?? '')); ?>" autocomplete="username" inputmode="email" autocapitalize="off" spellcheck="false" enterkeyhint="next" placeholder="admin.secondaire@domaine.com" required autofocus>
        </div>
        <p class="auth-form-v2__hint">Utilisez ici un compte limité à un ou plusieurs modules précis.</p>
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
        <p class="auth-form-v2__hint">Les modules visibles dépendront des permissions réellement attribuées à ce compte.</p>
        <p class="auth-form-v2__error" data-field-error hidden></p>
    </div>

    <button class="auth-form-v2__submit auth-form-v2__submit--primary" type="submit" data-loading-label="Vérification des permissions...">
        <?php echo $authIcon('spark', $authIconClasses); ?>
        <span>Entrer avec un accès limité</span>
    </button>
</form>

<section class="auth-admin-access-grid" aria-label="Spécificités de l'accès secondaire">
    <article class="auth-admin-access-card">
        <div class="auth-admin-access-card__icon"><?php echo $authIcon('badge', $authIconClasses); ?></div>
        <strong>Permissions fines</strong>
        <p>Chaque compte ne voit que les modules explicitement activés côté administration.</p>
    </article>
    <article class="auth-admin-access-card">
        <div class="auth-admin-access-card__icon"><?php echo $authIcon('arrow-right', $authIconClasses); ?></div>
        <strong>Arrivée ciblée</strong>
        <p>La redirection après connexion mène directement au premier écran réellement autorisé.</p>
    </article>
    <article class="auth-admin-access-card">
        <div class="auth-admin-access-card__icon"><?php echo $authIcon('shield', $authIconClasses); ?></div>
        <strong>Risque réduit</strong>
        <p>Accès secondaire limité aux modules autorisés.</p>
    </article>
</section>

<div class="auth-form-v2__footer-note">
    Besoin du compte administrateur principal ?
    <a href="<?php echo e((string) ($adminLoginUrl ?? site_url('admin/login'))); ?>">Revenir à la connexion principale</a>
</div>
<?php
$panelHtml = (string) ob_get_clean();

$asideHtml = $renderAside([
    'eyebrow' => 'Equipe operationnelle',
    'title' => 'Donnez un accès utile sans brouiller la frontière avec le compte principal.',
    'text' => 'Le compte secondaire reste orienté mission: un périmètre réduit, une redirection claire et moins de friction pour les équipes support, vente ou modération.',
    'stats' => [
        ['value' => '1', 'label' => 'compte délégué'],
        ['value' => 'N', 'label' => 'modules activés'],
    ],
    'highlights' => [
        ['title' => 'Contexte explicite', 'text' => 'Le rôle secondaire est rappelé dès l’en-tête et dans le panneau latéral.'],
        ['title' => 'Portée maîtrisée', 'text' => 'Le parcours insiste sur les droits réels du compte au lieu de vendre une fausse impression d’accès global.'],
        ['title' => 'Base commune', 'text' => 'Le module reste cohérent avec l’auth principal tout en assumant une identité plus ciblée.'],
    ],
    'badges' => ['Accès délégué', 'Permissions par module', 'Redirection ciblée'],
]);

$renderPage([
    'title' => 'Administration secondaire - Connexion',
    'pageClass' => 'auth-page--admin-secondary-login',
    'variant' => 'admin',
    'layout' => 'split',
    'asideHtml' => $asideHtml,
    'panelHtml' => $panelHtml,
    'scripts' => [
        'assets/js/togglePassword.js',
        'assets/js/authForms.js',
    ],
]);
