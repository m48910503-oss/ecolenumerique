<?php
$auth = require dirname(__DIR__, 2) . '/auth/_shared.php';
$authIcon = $auth['icon'];
$renderFlash = $auth['renderFlash'];
$renderPanelHeader = $auth['renderPanelHeader'];
$renderAside = $auth['renderAside'];
$renderPage = $auth['renderPage'];
$authIconClasses = 'h-5 w-5';
$registerToken = trim((string) ($registerToken ?? ''));
 
ob_start();
?>
<div class="auth-suite__utility-group">
    <a class="auth-suite__ghost-link" href="<?php echo e(site_url('admin/login')); ?>">Connexion admin</a>
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
    'eyebrow' => 'Création administrateur',
    'title' => 'Créer un compte administrateur',
    'subtitle' => 'À réserver à l’ouverture initiale ou à un cadre strictement contrôlé.',
    'utilityHtml' => $utilityHtml,
]);

if (!empty($errors)) {
    echo $renderFlash('danger', array_map(static fn ($error): string => (string) $error, $errors), [
        'title' => 'Création impossible',
        'role' => 'alert',
    ]);
}

if (!empty($successMessage)) {
    echo $renderFlash('success', [(string) $successMessage], [
        'title' => 'Compte créé',
        'role' => 'status',
    ]);
}

echo $renderFlash('warning', [
    'Désactivez ou restreignez cette page après la création des comptes nécessaires.',
], [
    'title' => 'Point de vigilance',
    'role' => 'status',
]);
?>
<form method="post" action="<?php echo e(site_url('admin/register')); ?>" class="auth-form-v2 auth-form-v2--stack" data-auth-form>
    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
    <input type="hidden" name="token" value="<?php echo e($registerToken); ?>">

    <div class="auth-form-v2__grid auth-form-v2__grid--2">
        <div class="auth-form-v2__field" data-auth-field>
            <label class="auth-form-v2__label" for="first_name">
                <?php echo $authIcon('user', $authIconClasses); ?>
                <span>Prénom</span>
            </label>
            <div class="auth-form-v2__input-wrap">
                <span class="auth-form-v2__input-icon"><?php echo $authIcon('user', $authIconClasses); ?></span>
                <input class="auth-form-v2__control" type="text" id="first_name" name="first_name" value="<?php echo e((string) ($old['first_name'] ?? '')); ?>" autocomplete="given-name" placeholder="Prénom" required>
            </div>
            <p class="auth-form-v2__error" data-field-error hidden></p>
        </div>

        <div class="auth-form-v2__field" data-auth-field>
            <label class="auth-form-v2__label" for="last_name">
                <?php echo $authIcon('user', $authIconClasses); ?>
                <span>Nom</span>
            </label>
            <div class="auth-form-v2__input-wrap">
                <span class="auth-form-v2__input-icon"><?php echo $authIcon('user', $authIconClasses); ?></span>
                <input class="auth-form-v2__control" type="text" id="last_name" name="last_name" value="<?php echo e((string) ($old['last_name'] ?? '')); ?>" autocomplete="family-name" placeholder="Nom" required>
            </div>
            <p class="auth-form-v2__error" data-field-error hidden></p>
        </div>
    </div>

    <div class="auth-form-v2__field" data-auth-field>
        <label class="auth-form-v2__label" for="email">
            <?php echo $authIcon('mail', $authIconClasses); ?>
            <span>Adresse e-mail</span>
        </label>
        <div class="auth-form-v2__input-wrap">
            <span class="auth-form-v2__input-icon"><?php echo $authIcon('mail', $authIconClasses); ?></span>
            <input class="auth-form-v2__control" type="email" id="email" name="email" value="<?php echo e((string) ($old['email'] ?? '')); ?>" autocomplete="email" inputmode="email" placeholder="admin@domaine.com" required>
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
                <input class="auth-form-v2__control" type="password" id="password" name="password" autocomplete="new-password" placeholder="Mot de passe robuste" data-password-source required>
                <button type="button" class="auth-form-v2__toggle" data-password-toggle="#password" data-label-show="Afficher" data-label-hide="Masquer">
                    <span data-password-toggle-label>Afficher</span>
                </button>
            </div>
            <p class="auth-form-v2__error" data-field-error hidden></p>
        </div>

        <div class="auth-form-v2__field auth-form-v2__field--supporting">
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
            </div>
        </div>
    </div>

    <button class="auth-form-v2__submit auth-form-v2__submit--primary" type="submit" data-loading-label="Création du compte administrateur...">
        <?php echo $authIcon('shield', $authIconClasses); ?>
        <span>Créer le compte administrateur</span>
    </button>
</form>
<?php
$panelHtml = (string) ob_get_clean();

$asideHtml = $renderAside([
    'eyebrow' => 'Usage restreint',
    'title' => 'Une page d’inscription admin plus crédible et plus encadrée.',
    'text' => 'Le message de prudence reste visible, mais sans transformer l’écran en mur de texte. La sécurité du mot de passe est intégrée directement au formulaire.',
    'stats' => [
        ['value' => '1', 'label' => 'compte à créer'],
        ['value' => '0', 'label' => 'champ superflu'],
    ],
    'highlights' => [
        ['title' => 'Mot de passe renforcé', 'text' => 'Le front et les validations serveur convergent vers une exigence plus sérieuse.'],
        ['title' => 'Lecture immédiate', 'text' => 'Les champs principaux passent avant les précautions opérationnelles.'],
        ['title' => 'Base homogène', 'text' => 'Le même socle auth sert désormais la connexion et l’inscription admin.'],
    ],
    'badges' => ['Accès restreint', 'Validation claire', 'UI unifiée'],
]);

$renderPage([
    'title' => 'Administration - Inscription',
    'pageClass' => 'auth-page--admin-register',
    'variant' => 'admin',
    'layout' => 'split',
    'asideHtml' => $asideHtml,
    'panelHtml' => $panelHtml,
    'scripts' => [
        'assets/js/togglePassword.js',
        'assets/js/authForms.js',
    ],
]);
