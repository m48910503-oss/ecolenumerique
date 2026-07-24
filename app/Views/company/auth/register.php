<?php
$auth = require dirname(__DIR__, 2) . '/auth/_shared.php';
$authIcon = $auth['icon'];
$renderFlash = $auth['renderFlash'];
$renderPanelHeader = $auth['renderPanelHeader'];
$renderAside = $auth['renderAside'];
$renderPage = $auth['renderPage'];
$authIconClasses = 'h-5 w-5';
$isAuthenticated = !empty($isAuthenticated);
$isGoogleOnboarding = !empty($isGoogleOnboarding);
$companyGoogleHref = site_url('oauth/google?src=company_register&return=' . rawurlencode('/company/register?google=1'));
$showDebugVerificationLink = !empty($verificationLink)
    && function_exists('is_local_environment')
    && is_local_environment();

ob_start();
?>
<div class="auth-suite__utility-group">
    <?php if ($isAuthenticated): ?>
        <a class="auth-suite__ghost-link" href="<?php echo e(site_url('')); ?>">Accueil</a>
    <?php else: ?>
        <a class="auth-suite__ghost-link" href="<?php echo e(site_url('login')); ?>">Connexion</a>
    <?php endif; ?>
    <button id="theme-toggle" type="button" class="auth-suite__theme-toggle" aria-label="Changer le thème" aria-pressed="false">
        <span data-theme-label aria-hidden="true"></span>
    </button>
</div>
<?php
$utilityHtml = (string) ob_get_clean();

ob_start();
echo $renderPanelHeader([
    'siteLogoUrl' => (string) $siteLogoUrl,
    'logoFallback' => 'CO',
    'logoAlt' => 'Logo École Numérique',
    'eyebrow' => 'Compte entreprise',
    'title' => $isAuthenticated ? 'Activez votre espace entreprise' : 'Créez votre espace entreprise',
    'subtitle' => $isAuthenticated
        ? 'Finalisez votre structure pour inviter vos collaborateurs et gérer vos accès.'
        : 'Créez un compte entreprise clair et adapté au B2B.',
    'utilityHtml' => $utilityHtml,
]);

if (!empty($errors)) {
    echo $renderFlash('danger', array_map(static fn ($error): string => (string) $error, $errors), [
        'title' => 'Vérifiez les informations saisies',
        'role' => 'alert',
    ]);
}

if (!empty($successMessage)) {
    echo $renderFlash('success', [(string) $successMessage], [
        'title' => 'Entreprise créée',
        'role' => 'status',
    ]);
    if ($showDebugVerificationLink) {
        ?>
        <p class="auth-form-v2__footer-note auth-form-v2__footer-note--inline">
            Lien de test local :
            <a href="<?php echo e((string) $verificationLink); ?>"><?php echo e((string) $verificationLink); ?></a>
        </p>
        <?php
    }
}

if ($isAuthenticated) {
    $infoMessages = ['Session active : ' . (string) ($old['email'] ?? '')];
    if ($isGoogleOnboarding) {
        $infoMessages[] = 'Compte Google détecté. Il ne reste qu’à nommer votre entreprise.';
    }
    echo $renderFlash('info', $infoMessages, [
        'title' => 'Profil déjà connecté',
        'role' => 'status',
    ]);
}
?>
<form method="post" action="<?php echo e(site_url('company/register')); ?>" class="auth-form-v2 auth-form-v2--stack" data-auth-form>
    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">

    <div class="auth-form-v2__field" data-auth-field>
        <label class="auth-form-v2__label" for="company_name">
            <?php echo $authIcon('building', $authIconClasses); ?>
            <span>Nom de l’entreprise</span>
        </label>
        <div class="auth-form-v2__input-wrap">
            <span class="auth-form-v2__input-icon"><?php echo $authIcon('building', $authIconClasses); ?></span>
            <input
                class="auth-form-v2__control"
                type="text"
                id="company_name"
                name="company_name"
                value="<?php echo e((string) ($old['company_name'] ?? '')); ?>"
                placeholder="Nom de votre structure"
                autocomplete="organization"
                required
            >
        </div>
        <p class="auth-form-v2__hint">Nom visible dans l’administration et les invitations.</p>
        <p class="auth-form-v2__error" data-field-error hidden></p>
    </div>

    <?php if (!$isAuthenticated): ?>
        <div class="auth-form-v2__grid auth-form-v2__grid--2">
            <div class="auth-form-v2__field" data-auth-field>
                <label class="auth-form-v2__label" for="first_name">
                    <?php echo $authIcon('user', $authIconClasses); ?>
                    <span>Prénom administrateur</span>
                </label>
                <div class="auth-form-v2__input-wrap">
                    <span class="auth-form-v2__input-icon"><?php echo $authIcon('user', $authIconClasses); ?></span>
                    <input class="auth-form-v2__control" type="text" id="first_name" name="first_name" value="<?php echo e((string) ($old['first_name'] ?? '')); ?>" autocomplete="given-name" placeholder="Votre prénom" required>
                </div>
                <p class="auth-form-v2__error" data-field-error hidden></p>
            </div>

            <div class="auth-form-v2__field" data-auth-field>
                <label class="auth-form-v2__label" for="last_name">
                    <?php echo $authIcon('user', $authIconClasses); ?>
                    <span>Nom administrateur</span>
                </label>
                <div class="auth-form-v2__input-wrap">
                    <span class="auth-form-v2__input-icon"><?php echo $authIcon('user', $authIconClasses); ?></span>
                    <input class="auth-form-v2__control" type="text" id="last_name" name="last_name" value="<?php echo e((string) ($old['last_name'] ?? '')); ?>" autocomplete="family-name" placeholder="Votre nom" required>
                </div>
                <p class="auth-form-v2__error" data-field-error hidden></p>
            </div>
        </div>

        <div class="auth-form-v2__field" data-auth-field>
            <label class="auth-form-v2__label" for="email">
                <?php echo $authIcon('mail', $authIconClasses); ?>
                <span>Adresse e-mail administrateur</span>
            </label>
            <div class="auth-form-v2__input-wrap">
                <span class="auth-form-v2__input-icon"><?php echo $authIcon('mail', $authIconClasses); ?></span>
                <input class="auth-form-v2__control" type="email" id="email" name="email" value="<?php echo e((string) ($old['email'] ?? '')); ?>" autocomplete="email" inputmode="email" placeholder="vous@entreprise.com" required>
            </div>
            <p class="auth-form-v2__error" data-field-error hidden></p>
        </div>

        <div class="auth-form-v2__grid auth-form-v2__grid--2">
            <div class="auth-form-v2__field" data-auth-field>
                <label class="auth-form-v2__label" for="password">
                    <?php echo $authIcon('lock', $authIconClasses); ?>
                    <span>Mot de passe</span>
                </label>
                <div class="auth-form-v2__input-wrap auth-form-v2__input-wrap--password">
                    <span class="auth-form-v2__input-icon"><?php echo $authIcon('lock', $authIconClasses); ?></span>
                    <input class="auth-form-v2__control" type="password" id="password" name="password" autocomplete="new-password" placeholder="Créez un mot de passe robuste" data-password-source required>
                    <button type="button" class="auth-form-v2__toggle" data-password-toggle="#password" data-label-show="Afficher" data-label-hide="Masquer">
                        <span data-password-toggle-label>Afficher</span>
                    </button>
                </div>
                <p class="auth-form-v2__error" data-field-error hidden></p>
            </div>

            <div class="auth-form-v2__field" data-auth-field>
                <label class="auth-form-v2__label" for="password_confirm">
                    <?php echo $authIcon('shield', $authIconClasses); ?>
                    <span>Confirmer le mot de passe</span>
                </label>
                <div class="auth-form-v2__input-wrap auth-form-v2__input-wrap--password">
                    <span class="auth-form-v2__input-icon"><?php echo $authIcon('shield', $authIconClasses); ?></span>
                    <input class="auth-form-v2__control" type="password" id="password_confirm" name="password_confirm" autocomplete="new-password" placeholder="Retapez le mot de passe" data-password-confirm required>
                    <button type="button" class="auth-form-v2__toggle" data-password-toggle="#password_confirm" data-label-show="Afficher" data-label-hide="Masquer">
                        <span data-password-toggle-label>Afficher</span>
                    </button>
                </div>
                <p class="auth-form-v2__error" data-field-error hidden></p>
                <p class="auth-form-v2__match" data-password-match hidden></p>
            </div>
        </div>

        <div class="auth-password-panel-v2" data-password-policy data-password-enforce="strong">
            <div class="auth-strength-v2" aria-live="polite">
                <div>
                    <p class="auth-strength-v2__title">Robustesse du mot de passe</p>
                    <p class="auth-strength-v2__status" data-password-strength-score>Ajoutez un mot de passe robuste.</p>
                </div>
                <strong class="auth-strength-v2__label" data-password-strength-label>À définir</strong>
            </div>
            <div class="auth-strength-v2__meter"><span data-password-strength-fill data-strength-tone="weak"></span></div>
            <ul class="auth-rules-v2" aria-label="Critères du mot de passe">
                <li class="auth-rules-v2__item" data-password-rule="length"><span>8 caractères minimum</span><span class="auth-rules-v2__badge" data-password-rule-status="length">À faire</span></li>
                <li class="auth-rules-v2__item" data-password-rule="upper"><span>1 lettre majuscule</span><span class="auth-rules-v2__badge" data-password-rule-status="upper">À faire</span></li>
                <li class="auth-rules-v2__item" data-password-rule="digit"><span>1 chiffre</span><span class="auth-rules-v2__badge" data-password-rule-status="digit">À faire</span></li>
                <li class="auth-rules-v2__item" data-password-rule="special"><span>1 caractère spécial</span><span class="auth-rules-v2__badge" data-password-rule-status="special">À faire</span></li>
            </ul>
            <p class="auth-form-v2__hint">Préférez un mot de passe unique et distinct de vos autres outils métier.</p>
        </div>
    <?php endif; ?>

    <div class="auth-form-v2__field">
        <label class="auth-form-v2__checkbox auth-form-v2__checkbox--terms" for="terms">
            <input class="app-checkbox" type="checkbox" id="terms" name="terms" value="1" required <?php echo !empty($old['terms']) ? 'checked' : ''; ?>>
            <span>J’accepte les <a href="<?php echo e(site_url('cgu')); ?>">CGU</a> et la <a href="<?php echo e(site_url('privacy')); ?>">politique de confidentialité</a>.</span>
        </label>
    </div>

    <button class="auth-form-v2__submit auth-form-v2__submit--primary" type="submit" data-loading-label="Création de l’espace en cours...">
        <?php echo $authIcon('building', $authIconClasses); ?>
        <span><?php echo $isAuthenticated ? 'Créer mon entreprise' : 'Créer le compte entreprise'; ?></span>
    </button>
</form>

<?php if (!$isAuthenticated): ?>
    <div class="auth-form-v2__divider"><span>ou</span></div>
    <a href="<?php echo e($companyGoogleHref); ?>" class="auth-form-v2__secondary-action auth-form-v2__secondary-action--full">
        <?php echo $authIcon('google', $authIconClasses); ?>
        <span>Continuer avec Google</span>
    </a>
<?php endif; ?>

<div class="auth-form-v2__footer-note">
    Vous êtes un particulier ?
    <a href="<?php echo e(site_url('register')); ?>">Revenir à l’inscription classique</a>
</div>
<?php
$panelHtml = (string) ob_get_clean();

$asideHtml = $renderAside([
    'eyebrow' => 'Organisation et pilotage',
    'title' => 'Un accès entreprise plus clair.',
    'text' => 'Compte administrateur, contexte métier et actions essentielles.',
    'stats' => [
        ['value' => '1', 'label' => 'espace entreprise'],
        ['value' => 'N', 'label' => 'collaborateurs à inviter'],
    ],
    'highlights' => [
        ['title' => 'Création lisible', 'text' => 'Entreprise et administrateur bien identifiés.'],
        ['title' => 'Google conservé', 'text' => 'L’entrée Google reste disponible sans concurrencer le CTA principal.'],
        ['title' => 'Base maintenable', 'text' => 'Socle visuel partagé avec le module auth.'],
    ],
    'badges' => ['Responsive renforcé', 'Texte harmonisé', 'Sécurité front soignée'],
]);

$renderPage([
    'title' => 'Compte entreprise - École Numérique',
    'pageClass' => 'auth-page--company-register',
    'variant' => 'company',
    'layout' => 'split',
    'asideHtml' => $asideHtml,
    'panelHtml' => $panelHtml,
    'scripts' => [
        'assets/js/togglePassword.js',
        'assets/js/authForms.js',
    ],
]);
