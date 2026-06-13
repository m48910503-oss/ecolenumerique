<?php
$auth = require __DIR__ . '/_shared.php';
$authIcon = $auth['icon'];
$renderFlash = $auth['renderFlash'];
$renderPanelHeader = $auth['renderPanelHeader'];
$renderAside = $auth['renderAside'];
$renderPage = $auth['renderPage'];
$authIconClasses = 'h-5 w-5';

ob_start();
?>
<div class="auth-suite__utility-group">
    <a class="auth-suite__ghost-link" href="<?php echo e(site_url('login')); ?>">Connexion</a>
    <button id="theme-toggle" type="button" class="auth-suite__theme-toggle" aria-label="Changer le thème" aria-pressed="false">
        <span data-theme-label aria-hidden="true"></span>
    </button>
</div> 
<?php
$utilityHtml = (string) ob_get_clean();

ob_start();
echo $renderPanelHeader([
    'siteLogoUrl' => (string) $siteLogoUrl,
    'logoFallback' => 'EN',
    'eyebrow' => 'Vérification',
    'title' => 'Validation de l’adresse e-mail',
    'subtitle' => 'Cette étape confirme votre adresse avant la première connexion.',
    'utilityHtml' => $utilityHtml,
]);

if (!empty($error)) {
    echo $renderFlash('danger', [(string) $error], [
        'title' => 'Vérification impossible',
        'role' => 'alert',
    ]);
}

if (!empty($message)) {
    echo $renderFlash('success', [(string) $message], [
        'title' => 'Adresse confirmée',
        'role' => 'status',
    ]);
}
?>
<div class="auth-empty-state-v2 auth-empty-state-v2--soft">
    <p class="auth-empty-state-v2__title">Prochaine étape</p>
    <p class="auth-empty-state-v2__text">Connectez-vous pour accéder à votre espace et reprendre votre parcours d’apprentissage.</p>
    <a class="auth-form-v2__submit auth-form-v2__submit--primary auth-form-v2__submit--inline" href="<?php echo e(site_url('login')); ?>">
        <?php echo $authIcon('spark', $authIconClasses); ?>
        <span>Aller à la connexion</span>
    </a>
</div>
<?php
$panelHtml = (string) ob_get_clean();

$asideHtml = $renderAside([
    'eyebrow' => 'Compte sécurisé',
    'title' => 'Une dernière vérification avant d’ouvrir votre espace.',
    'text' => 'Le parcours d’inscription reste crédible en production : validation claire, message lisible et action suivante immédiate.',
    'highlights' => [
        ['title' => 'Lecture directe', 'text' => 'Le statut de la vérification ressort immédiatement sans écran vide passif.'],
        ['title' => 'Action suivante', 'text' => 'La connexion reste visible sans voler l’attention au message principal.'],
        ['title' => 'Ton cohérent', 'text' => 'Le wording reste sobre, clair et professionnel.'],
    ],
    'badges' => ['Étape rassurante', 'UI cohérente', 'Responsive propre'],
]);

$renderPage([
    'title' => 'Vérification de l’adresse e-mail - École Numérique',
    'pageClass' => 'auth-page--verify',
    'variant' => 'default',
    'layout' => 'compact',
    'asideHtml' => $asideHtml,
    'panelHtml' => $panelHtml,
]);