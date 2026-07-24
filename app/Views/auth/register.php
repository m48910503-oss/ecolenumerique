<?php
$currentStep = (int) ($uiStep ?? 1) === 2 ? 2 : 1;
$accountType = (string) ($accountType ?? 'personal') === 'company' ? 'company' : 'personal';
$showDebugVerificationLink = !empty($verificationLink)
    && function_exists('is_local_environment')
    && is_local_environment();

$firstNameValue = (string) ($old['first_name'] ?? '');
$lastNameValue = (string) ($old['last_name'] ?? '');
$emailValue = (string) ($old['email'] ?? '');
$companyNameValue = (string) ($companyName ?? '');
$referralValue = (string) ($referralInput ?? '');
$termsChecked = !empty($old['terms']);

$authAsset = static function (string $path): string {
    $path = ltrim($path, '/');
    $fullPath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    $version = is_file($fullPath) ? (string) filemtime($fullPath) : (string) time();

    return site_url($path . '?v=' . rawurlencode($version));
};

$renderNotice = static function (string $tone, string $title, array $messages): void {
    $messages = array_values(array_filter(array_map(static fn ($message): string => trim((string) $message), $messages)));
    if ($messages === []) {
        return;
    }

    $toneClasses = [
        'danger' => [
            'class' => 'auth-notice--soft-danger',
            'icon' => 'fa-triangle-exclamation',
            'eyebrow' => 'Attention',
        ],
        'success' => [
            'class' => 'auth-notice--soft-success',
            'icon' => 'fa-check-circle',
            'eyebrow' => 'Confirmation',
        ],
        'info' => [
            'class' => 'auth-notice--soft-info',
            'icon' => 'fa-circle-info',
            'eyebrow' => 'Information',
        ],
    ];
    $config = $toneClasses[$tone] ?? $toneClasses['info'];
    ?>
    <div class="auth-notice <?php echo e($config['class']); ?> mb-5" role="<?php echo $tone === 'danger' ? 'alert' : 'status'; ?>">
        <div class="auth-notice__row">
            <span class="auth-notice__icon" aria-hidden="true">
                <i class="fas <?php echo e($config['icon']); ?>"></i>
            </span>
            <div class="auth-notice__copy">
                <p class="auth-notice__eyebrow"><?php echo e($config['eyebrow']); ?></p>
                <p class="auth-notice__title"><?php echo e($title); ?></p>
                <?php if (count($messages) === 1): ?>
                    <p class="auth-notice__message"><?php echo e($messages[0]); ?></p>
                <?php else: ?>
                    <ul class="auth-notice__list list-disc space-y-1">
                        <?php foreach ($messages as $message): ?>
                            <li><?php echo e($message); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="app-global-assets" content="off">
    <meta name="robots" content="noindex, nofollow">
    <title>Inscription | Plateforme</title>
    <script src="<?php echo e($authAsset('assets/js/register_tailwind_config.js')); ?>"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo e($authAsset('assets/vendor/fontawesome/all.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e($authAsset('assets/css/auth-teacher.css')); ?>">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        @font-face {
            font-family: "Font Awesome 6 Free";
            font-style: normal;
            font-weight: 900;
            font-display: block;
            src: url("<?php echo e($authAsset('assets/vendor/fontawesome/webfonts/fa-solid-900.woff2')); ?>") format("woff2");
        }
        @font-face {
            font-family: "Font Awesome 6 Free";
            font-style: normal;
            font-weight: 400;
            font-display: block;
            src: url("<?php echo e($authAsset('assets/vendor/fontawesome/webfonts/fa-regular-400.woff2')); ?>") format("woff2");
        }
        @font-face {
            font-family: "Font Awesome 6 Brands";
            font-style: normal;
            font-weight: 400;
            font-display: block;
            src: url("<?php echo e($authAsset('assets/vendor/fontawesome/webfonts/fa-brands-400.woff2')); ?>") format("woff2");
        }
        @font-face {
            font-family: "Font Awesome 5 Brands";
            font-style: normal;
            font-weight: 400;
            font-display: block;
            src: url("<?php echo e($authAsset('assets/vendor/fontawesome/webfonts/fa-brands-400.woff2')); ?>") format("woff2");
        }
        body { transition: background-color 0.3s ease, color 0.2s ease; }
        .fade-transition { transition: opacity 0.25s ease, transform 0.2s ease; }
        input:focus { outline: none; ring: none; }
    </style>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body data-view="auth/register" class="bg-gray-50 dark:bg-gray-900 font-sans antialiased transition-colors duration-300 overflow-x-hidden">

    <div class="fixed bottom-5 right-5 z-50 flex max-sm:top-3 max-sm:bottom-auto max-sm:right-3">
        <button id="darkModeToggle" type="button" class="bg-white dark:bg-gray-800 shadow-lg rounded-full w-11 h-11 max-sm:w-10 max-sm:h-10 flex items-center justify-center text-gray-700 dark:text-yellow-300 hover:scale-105 transition" aria-label="Changer le thème">
            <i id="darkIcon" class="fas fa-moon text-xl"></i>
        </button>
    </div>

    <form id="registerForm" method="post" action="<?php echo e(site_url('register')); ?>" class="min-h-screen w-full flex items-center justify-center p-4 pb-28 sm:pb-6 md:p-6">
        <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
        <input type="hidden" name="_ui_step" id="uiStep" value="<?php echo e((string) $currentStep); ?>">

        <div id="stepProfile" class="<?php echo $currentStep === 2 ? 'hidden ' : ''; ?>w-full max-w-2xl transition-all duration-300">
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
                <div class="p-6 md:p-8">
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-2 mb-5">
                        <div>
                            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white">Inscription</h1>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="auth-step-pill">Étape 1 / 2</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">Profil</span>
                            </div>
                        </div>
                        <a href="<?php echo e(site_url('login')); ?>" class="auth-secondary-button inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold whitespace-nowrap">
                            <i class="fas fa-right-to-bracket text-sm"></i>
                            <span>J’ai déjà un compte</span>
                        </a>
                    </div>

                    <?php
                    if (!empty($errors)) {
                        $renderNotice('danger', 'Vérifiez les informations saisies', array_map(static fn ($error): string => (string) $error, (array) $errors));
                    }
                    if (!empty($successMessage)) {
                        $messages = [(string) $successMessage];
                        if ($showDebugVerificationLink) {
                            $messages[] = 'Lien de test local : ' . (string) $verificationLink;
                        }
                        $renderNotice('success', 'Inscription enregistrée', $messages);
                    }
                    if (!empty($referralDetected) && $referralValue !== '') {
                        $renderNotice('info', 'Parrainage pris en compte', [
                            'Code de parrainage détecté : ' . $referralValue,
                            'Il sera conservé si vous choisissez une inscription avec Google.',
                        ]);
                    }
                    ?>
                    <div id="step1ClientNotice" class="auth-notice auth-notice--soft-danger mb-5 hidden" role="alert" aria-live="polite"></div>

                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            <span class="auth-label-with-icon"><i class="fas fa-layer-group text-sky-600 dark:text-sky-300"></i><span>Type de compte</span></span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="auth-choice-card flex items-center gap-3 p-4 cursor-pointer">
                                <input type="radio" name="account_type" value="personal" <?php echo $accountType === 'personal' ? 'checked' : ''; ?> class="w-4 h-4 text-blue-600 focus:ring-blue-500 accent-blue-600">
                                <div>
                                    <div class="auth-choice-card__title auth-label-with-icon"><i class="fas fa-user text-emerald-500 dark:text-emerald-300"></i><span>Compte personnel</span></div>
                                    <div class="auth-choice-card__meta text-xs">Suivi individuel</div>
                                </div>
                            </label>
                            <label class="auth-choice-card flex items-center gap-3 p-4 cursor-pointer">
                                <input type="radio" name="account_type" value="company" <?php echo $accountType === 'company' ? 'checked' : ''; ?> class="w-4 h-4 text-blue-600 focus:ring-blue-500 accent-blue-600">
                                <div>
                                    <div class="auth-choice-card__title auth-label-with-icon"><i class="fas fa-building text-cyan-600 dark:text-cyan-300"></i><span>Compte entreprise</span></div>
                                    <div class="auth-choice-card__meta text-xs">Gestion équipe</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div id="companyField" class="<?php echo $accountType === 'company' ? '' : 'hidden '; ?>mb-5 transition-all">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1" for="companyName">
                            <span class="auth-label-with-icon"><i class="fas fa-building text-cyan-600 dark:text-cyan-300"></i><span>Nom de l’entreprise</span></span>
                        </label>
                        <input type="text" id="companyName" name="company_name" value="<?php echo e($companyNameValue); ?>" placeholder="Nom de votre structure" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-blue-400" autocomplete="organization" <?php echo $accountType === 'company' ? 'required' : 'disabled'; ?>>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1" for="firstName">
                                <span class="auth-label-with-icon"><i class="fas fa-user text-emerald-500 dark:text-emerald-300"></i><span>Prénom</span></span>
                            </label>
                            <input type="text" id="firstName" name="first_name" value="<?php echo e($firstNameValue); ?>" placeholder="Prénom" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-blue-400" autocomplete="given-name" required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1" for="lastName">
                                <span class="auth-label-with-icon"><i class="fas fa-id-card text-orange-500 dark:text-orange-300"></i><span>Nom</span></span>
                            </label>
                            <input type="text" id="lastName" name="last_name" value="<?php echo e($lastNameValue); ?>" placeholder="Nom" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-blue-400" autocomplete="family-name" required>
                        </div>
                    </div>

                    <div class="mb-7">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1" for="email">
                            <span class="auth-label-with-icon"><i class="fas fa-envelope text-sky-600 dark:text-sky-300"></i><span>Adresse e-mail</span></span>
                        </label>
                        <input type="email" id="email" name="email" value="<?php echo e($emailValue); ?>" placeholder="vous@exemple.com" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-blue-400" autocomplete="email" inputmode="email" required>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1 flex items-center gap-1"><i class="fas fa-envelope text-[10px]"></i> Vérification e-mail incluse</p>
                    </div>

                    <div class="flex justify-end">
                        <button id="nextToSecurity" type="button" class="auth-primary-button font-semibold py-3 px-8 transition flex items-center gap-2">
                            Continuer <i class="fas fa-arrow-right text-sm"></i>
                        </button>
                    </div>

                    <div class="relative my-6">
                        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-200 dark:border-gray-700"></div></div>
                        <div class="relative flex justify-center text-sm"><span class="px-3 bg-white dark:bg-gray-800 text-gray-400 dark:text-gray-500">OU</span></div>
                    </div>
                    <a href="<?php echo e(site_url('oauth/google?src=register')); ?>" class="auth-social-button w-full inline-flex items-center justify-center gap-3 py-3 font-semibold transition">
                        <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white shadow-sm ring-1 ring-gray-200 dark:ring-gray-300 overflow-hidden">
                            <svg viewBox="0 0 48 48" aria-hidden="true" class="h-5 w-5" focusable="false">
                                <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303C33.654 32.657 29.292 36 24 36c-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.038l5.657-5.657C34.067 6.053 29.392 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z"></path>
                                <path fill="#FF3D00" d="M6.306 14.691l6.571 4.818C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.038l5.657-5.657C34.067 6.053 29.392 4 24 4 16.318 4 9.656 8.337 6.306 14.691z"></path>
                                <path fill="#4CAF50" d="M24 44c5.293 0 9.87-1.987 13.48-5.22l-6.22-5.225C29.147 35.091 26.715 36 24 36c-5.271 0-9.618-3.317-11.278-7.946l-6.533 5.025C9.498 39.556 16.227 44 24 44z"></path>
                                <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303a12.024 12.024 0 0 1-4.043 5.555l.003-.002 6.22 5.225C36.914 40.871 44 36 44 24c0-1.341-.138-2.65-.389-3.917z"></path>
                            </svg>
                        </span>
                        <span>Continuer avec Google</span>
                    </a>
                    <p class="text-center text-sm text-gray-500 dark:text-gray-400 mt-6">
                        Déjà un compte ? <a href="<?php echo e(site_url('login')); ?>" class="text-blue-600 dark:text-blue-400 font-semibold hover:underline">Connexion</a>
                    </p>
                </div>
            </div>
        </div>

        <div id="stepSecurity" class="<?php echo $currentStep === 2 ? '' : 'hidden '; ?>w-full max-w-2xl transition-all duration-300">
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
                <div class="p-6 md:p-8">
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-2 mb-5">
                        <div>
                            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white">Finalisez vos accès</h1>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="auth-step-pill">Étape 2 / 2</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">Sécurité</span>
                            </div>
                        </div>
                        <a href="<?php echo e(site_url('login')); ?>" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">Se connecter</a>
                    </div>

                    <?php
                    if (!empty($errors)) {
                        $renderNotice('danger', 'Vérifiez les informations saisies', array_map(static fn ($error): string => (string) $error, (array) $errors));
                    }
                    ?>
                    <div id="step2ClientNotice" class="auth-notice auth-notice--soft-danger mb-5 hidden" role="alert" aria-live="polite"></div>

                    <div class="auth-panel mb-6 p-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="referralCode"><i class="fas fa-gift text-gray-400 mr-1"></i> Code de parrainage <span class="text-gray-400 text-xs">(optionnel)</span></label>
                        <input type="text" id="referralCode" name="referral_code" value="<?php echo e($referralValue); ?>" placeholder="Ex: BIENVENUE2025" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 focus:ring-1 focus:ring-blue-400" autocomplete="off">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1" for="password">
                            <span class="auth-label-with-icon"><i class="fas fa-lock text-amber-500 dark:text-amber-300"></i><span>Mot de passe</span></span>
                            </label>
                            <div class="relative">
                                <input type="password" id="password" name="password" placeholder="Créez un mot de passe" class="w-full px-4 py-3 pr-24 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-blue-400" autocomplete="new-password" required>
                                <button type="button" id="togglePwd" class="auth-password-toggle absolute right-2 top-1/2 -translate-y-1/2">Afficher</button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1" for="confirmPassword">
                            <span class="auth-label-with-icon"><i class="fas fa-lock text-amber-500 dark:text-amber-300"></i><span>Confirmer le mot de passe</span></span>
                            </label>
                            <div class="relative">
                                <input type="password" id="confirmPassword" name="password_confirm" placeholder="Retapez le mot de passe" class="w-full px-4 py-3 pr-24 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-blue-400" autocomplete="new-password" required>
                                <button type="button" id="toggleConfirm" class="auth-password-toggle absolute right-2 top-1/2 -translate-y-1/2">Afficher</button>
                            </div>
                            <p id="matchMessage" class="text-xs mt-1 hidden"></p>
                        </div>
                    </div>

                    <div class="auth-panel rounded-xl p-4 mb-6">
                        <div class="flex justify-between items-center flex-wrap gap-2 mb-3">
                            <span class="font-semibold text-gray-700 dark:text-gray-300 text-sm"><i class="fas fa-shield-alt text-green-600 dark:text-green-400 mr-1"></i> Robustesse</span>
                            <span id="strengthBadge" class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300">À renforcer</span>
                        </div>
                        <div class="grid grid-cols-2 gap-y-2 gap-x-3 text-xs">
                            <div id="critLength" class="flex items-center gap-2 p-1 rounded"><i class="fas fa-circle text-gray-300 dark:text-gray-500 text-[8px]"></i> <span>8 caractères</span> <i class="check-icon fas fa-check-circle text-green-500 ml-auto hidden"></i></div>
                            <div id="critUpper" class="flex items-center gap-2 p-1 rounded"><i class="fas fa-circle text-gray-300 dark:text-gray-500 text-[8px]"></i> <span>1 majuscule</span> <i class="check-icon fas fa-check-circle text-green-500 ml-auto hidden"></i></div>
                            <div id="critDigit" class="flex items-center gap-2 p-1 rounded"><i class="fas fa-circle text-gray-300 dark:text-gray-500 text-[8px]"></i> <span>1 chiffre</span> <i class="check-icon fas fa-check-circle text-green-500 ml-auto hidden"></i></div>
                            <div id="critSpecial" class="flex items-center gap-2 p-1 rounded"><i class="fas fa-circle text-gray-300 dark:text-gray-500 text-[8px]"></i> <span>1 caractère spécial</span> <i class="check-icon fas fa-check-circle text-green-500 ml-auto hidden"></i></div>
                        </div>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-2"><i class="fas fa-info-circle"></i> Évitez les mots de passe déjà utilisés</p>
                    </div>

                    <div class="mb-7 flex items-start gap-3">
                        <input type="checkbox" id="acceptTerms" name="terms" value="1" class="mt-1 w-4 h-4 text-blue-600 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-400" required <?php echo $termsChecked ? 'checked' : ''; ?>>
                        <label for="acceptTerms" class="text-sm text-gray-700 dark:text-gray-300">
                            <span class="auth-label-with-icon">
                                <i class="fas fa-file-contract text-rose-500 dark:text-rose-300"></i>
                                <span>J’accepte les <a href="<?php echo e(site_url('cgu')); ?>" class="text-blue-600 underline">CGU</a> et la <a href="<?php echo e(site_url('privacy')); ?>" class="text-blue-600 underline">politique de confidentialité</a>.</span>
                            </span>
                        </label>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-4 items-center justify-between mb-6">
                        <button id="backToProfileStep" type="button" class="auth-secondary-button w-full sm:w-auto text-center px-5 py-2.5 font-medium transition flex items-center justify-center gap-2"><i class="fas fa-arrow-left text-sm"></i> Revenir au profil</button>
                        <button id="submitAccount" type="submit" class="auth-primary-button w-full sm:w-auto font-bold py-2.5 px-8 transition flex items-center justify-center gap-2">Créer mon compte <i class="fas fa-check-circle"></i></button>
                    </div>

                    <div class="relative my-4">
                        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-200 dark:border-gray-700"></div></div>
                        <div class="relative flex justify-center text-sm"><span class="px-3 bg-white dark:bg-gray-800 text-gray-400">OU</span></div>
                    </div>
                    <a href="<?php echo e(site_url('oauth/google?src=register')); ?>" class="auth-social-button w-full flex items-center justify-center gap-3 py-2.5 font-medium transition">
                        <i class="fab fa-google text-[#DB4437] text-lg"></i> Continuer avec Google
                    </a>
                    <p class="text-center text-xs text-gray-400 dark:text-gray-500 mt-6">Vérification e-mail incluse • Parrainage facultatif</p>
                </div>
            </div>
        </div>
    </form>

    <script src="<?php echo e($authAsset('assets/js/register.js')); ?>" defer></script>
</body>
</html>
