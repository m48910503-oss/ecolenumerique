<?php
$errors = array_map(static fn ($error): string => (string) $error, (array) ($errors ?? []));
$successMessage = (string) ($successMessage ?? '');
$token = (string) ($token ?? '');
$tokenValid = (bool) ($tokenValid ?? false);
$assetUrl = static function (string $relativePath): string {
    $relativePath = ltrim($relativePath, '/');
    $absolutePath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    $version = is_file($absolutePath) ? (string) filemtime($absolutePath) : (string) time();

    return site_url($relativePath . '?v=' . rawurlencode($version));
};
$fontAwesomeUrl = $assetUrl('assets/vendor/fontawesome/all.min.css');
$tailwindBootstrapUrl = $assetUrl('assets/js/tailwind_theme_bootstrap.js');
$authTeacherCssUrl = $assetUrl('assets/css/auth-teacher.css');
$resetJsUrl = $assetUrl('assets/js/auth_reset.js');
?>
<!DOCTYPE html>
<html lang="fr" class="light" data-default-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="app-global-assets" content="off">
    <meta name="color-scheme" content="light dark">
    <title>Réinitialisation du mot de passe | École Numérique</title>
    <script src="<?php echo e($tailwindBootstrapUrl); ?>"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo e($fontAwesomeUrl); ?>">
    <link rel="stylesheet" href="<?php echo e($authTeacherCssUrl); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            transition: background-color 0.3s ease, color 0.2s ease;
        }
        input:focus { outline: none; ring: none; }
        .fade-transition { transition: opacity 0.25s ease, transform 0.2s ease; }
    </style>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body data-view="auth/reset" class="bg-gray-50 dark:bg-gray-900 font-sans antialiased transition-colors duration-300">
    <div class="fixed bottom-5 right-5 z-50 flex">
        <button id="darkModeToggle" class="bg-white dark:bg-gray-800 shadow-lg rounded-full w-11 h-11 flex items-center justify-center text-gray-700 dark:text-yellow-300 hover:scale-105 transition" type="button" aria-label="Changer le thème" aria-pressed="false" data-reset-theme-toggle>
            <i id="darkIcon" class="fas fa-moon text-xl"></i>
        </button>
    </div>

    <div class="min-h-screen flex items-center justify-center p-4 md:p-6">
        <div class="w-full max-w-md transition-all duration-300">
            <?php if ($tokenValid && $successMessage === ''): ?>
            <div id="stepResetForm" class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
                <div class="p-6 md:p-8">
                    <div class="text-center mb-6">
                        <div class="mx-auto w-16 h-16 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-lock text-blue-600 dark:text-blue-400 text-2xl"></i>
                        </div>
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white">Nouveau mot de passe</h1>
                        <p class="text-gray-500 dark:text-gray-400 text-sm mt-2">Choisissez un mot de passe robuste et sécurisé.</p>
                    </div>

                    <?php if ($errors !== []): ?>
                    <div class="auth-notice auth-notice--soft-danger mb-5" role="alert">
                        <div class="auth-notice__row">
                            <span class="auth-notice__icon" aria-hidden="true">
                                <i class="fas fa-triangle-exclamation"></i>
                            </span>
                            <div class="auth-notice__copy">
                                <p class="auth-notice__eyebrow">Attention</p>
                                <p class="auth-notice__title">Réinitialisation impossible</p>
                                <ul class="auth-notice__list space-y-1">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo e($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div id="resetClientNotice" class="auth-notice auth-notice--soft-danger mb-5 hidden" role="alert" aria-live="polite"></div>

                    <form id="resetPasswordForm" action="<?php echo e(site_url('reset')); ?>" method="POST">
                        <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                        <input type="hidden" name="token" value="<?php echo e($token); ?>">

                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1" for="newPassword">
                                <span class="auth-label-with-icon"><i class="fas fa-lock text-amber-500 dark:text-amber-300"></i><span>Nouveau mot de passe</span></span>
                            </label>
                            <div class="relative">
                                <input type="password" id="newPassword" name="password" autocomplete="new-password" required placeholder="Créez un mot de passe robuste" class="w-full px-4 py-3 pr-24 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-blue-400">
                                <button type="button" id="toggleNewPwd" class="auth-password-toggle absolute right-2 top-1/2 -translate-y-1/2">Afficher</button>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1" for="confirmPassword">
                                <span class="auth-label-with-icon"><i class="fas fa-lock text-amber-500 dark:text-amber-300"></i><span>Confirmer le mot de passe</span></span>
                            </label>
                            <div class="relative">
                                <input type="password" id="confirmPassword" name="confirm" autocomplete="new-password" required placeholder="Retapez le mot de passe" class="w-full px-4 py-3 pr-24 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-blue-400">
                                <button type="button" id="toggleConfirmPwd" class="auth-password-toggle absolute right-2 top-1/2 -translate-y-1/2">Afficher</button>
                            </div>
                            <p id="matchMessage" class="text-xs mt-1 hidden"></p>
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
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-2"><i class="fas fa-info-circle"></i> Évitez les mots de passe déjà utilisés ailleurs.</p>
                        </div>

                        <button type="submit" class="auth-primary-button w-full font-semibold py-3 transition flex items-center justify-center gap-2" data-loading-label="Mise à jour en cours...">
                            Réinitialiser le mot de passe <i class="fas fa-check-circle"></i>
                        </button>

                        <div class="text-center mt-6">
                            <a href="<?php echo e(site_url('login')); ?>" id="backToLoginReset" class="text-sm text-blue-600 dark:text-blue-400 hover:underline flex items-center justify-center gap-1">
                                <i class="fas fa-arrow-left text-xs"></i> Retour à la connexion
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            <?php elseif ($successMessage !== ''): ?>
            <div id="stepSuccess" class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700 fade-transition">
                <div class="p-6 md:p-8 text-center">
                    <div class="mx-auto w-20 h-20 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-5">
                        <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-4xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Mot de passe réinitialisé !</h2>
                    <p class="text-gray-500 dark:text-gray-400 mt-2 text-sm">
                        <?php echo e($successMessage); ?><br>Vous pouvez maintenant vous connecter avec vos nouveaux identifiants.
                    </p>
                    <div class="mt-8">
                        <a href="<?php echo e(site_url('login')); ?>" id="successGoToLogin" class="auth-primary-button block w-full font-semibold py-3 transition text-center">
                            Se connecter
                        </a>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div id="stepResetForm" class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
                <div class="p-6 md:p-8 text-center">
                    <div class="mx-auto w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-triangle-exclamation text-red-600 dark:text-red-400 text-2xl"></i>
                    </div>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white">Lien invalide</h1>
                    <div class="auth-notice auth-notice--soft-danger mt-4 text-left" role="alert">
                        <div class="auth-notice__row">
                            <span class="auth-notice__icon" aria-hidden="true">
                                <i class="fas fa-link-slash"></i>
                            </span>
                            <div class="auth-notice__copy">
                                <p class="auth-notice__eyebrow">Lien expiré</p>
                                <p class="auth-notice__title">Ce lien n’est plus utilisable</p>
                                <?php if ($errors !== []): ?>
                                    <div class="auth-notice__message">
                                        <?php foreach ($errors as $error): ?>
                                            <p><?php echo e($error); ?></p>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="auth-notice__message">Le lien n’est plus utilisable.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm mt-4">Demandez un nouveau lien pour reprendre la réinitialisation.</p>
                    <div class="mt-8">
                        <a href="<?php echo e(site_url('forgot')); ?>" class="auth-primary-button block w-full font-semibold py-3 transition text-center">
                            Demander un nouveau lien
                        </a>
                    </div>
                    <div class="text-center mt-6">
                        <a href="<?php echo e(site_url('login')); ?>" class="text-sm text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center justify-center gap-1">
                            <i class="fas fa-arrow-left text-xs"></i> Retour à la connexion
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="<?php echo e($resetJsUrl); ?>" defer></script>
</body>
</html>
