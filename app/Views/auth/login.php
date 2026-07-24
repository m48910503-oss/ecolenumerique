<?php
$errors = is_array($errors ?? null) ? array_values($errors) : [];
$successMessage = trim((string) ($successMessage ?? ''));
$return = (string) ($return ?? site_url(''));
$oldEmail = (string) ($oldEmail ?? '');
$googleUrl = site_url('oauth/google?src=login&return=' . rawurlencode($return));
$assetUrl = static function (string $relativePath): string {
    $relativePath = ltrim($relativePath, '/');
    $absolutePath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    $version = is_file($absolutePath) ? (string) filemtime($absolutePath) : (string) time();

    return site_url($relativePath . '?v=' . rawurlencode($version));
};
?>
<!DOCTYPE html>
<html lang="fr" class="light" data-default-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="app-global-assets" content="off">
    <meta name="color-scheme" content="light dark">
    <title>Connexion | École Numérique</title>
    <script src="<?php echo e(site_url('assets/js/tailwind_theme_bootstrap.js')); ?>"></script>
    <!-- Tailwind CSS v3 + Font Awesome 6 + Google Fonts -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo e($assetUrl('assets/vendor/fontawesome/all.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e($assetUrl('assets/css/auth-teacher.css')); ?>">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { transition: background-color 0.3s ease, color 0.2s ease; }
        input:focus { outline: none; ring: none; }
    </style>
    <script src="<?php echo e(site_url('assets/js/web_theme.js')); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/togglePassword.js')); ?>" defer></script>
    <script src="<?php echo e($assetUrl('assets/js/auth_login.js')); ?>" defer></script>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body data-view="auth/login" class="bg-gray-50 dark:bg-gray-900 font-sans antialiased transition-colors duration-300">

    <div class="fixed bottom-5 right-5 z-50 flex">
        <button id="theme-toggle" data-theme-toggle class="bg-white dark:bg-gray-800 shadow-lg rounded-full w-11 h-11 flex items-center justify-center text-gray-700 dark:text-yellow-300 hover:scale-105 transition" type="button" aria-label="Changer le thème" aria-pressed="false">
            <i class="fas fa-moon text-xl" data-theme-icon-dark aria-hidden="true"></i>
            <i class="fas fa-sun hidden text-xl" data-theme-icon-light aria-hidden="true"></i>
        </button>
    </div>

    <!-- Conteneur principal centré -->
    <div class="min-h-screen flex items-center justify-center p-4 md:p-6">
        <div class="w-full max-w-md transition-all duration-300">
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
                <div class="p-6 md:p-8">
                    <!-- En-tête -->
                    <div class="text-center mb-8">
                        <h1 class="text-3xl font-bold text-gray-800 dark:text-white">Connexion</h1>
                        <p class="text-gray-500 dark:text-gray-400 text-sm mt-2">Accédez à votre espace d'apprentissage</p>
                    </div>

                    <?php if ($successMessage !== ''): ?>
                        <div class="auth-notice auth-notice--soft-success mb-5" role="status">
                            <div class="auth-notice__row">
                                <span class="auth-notice__icon" aria-hidden="true">
                                    <i class="fas fa-check-circle"></i>
                                </span>
                                <div class="auth-notice__copy">
                                    <p class="auth-notice__eyebrow">Confirmation</p>
                                    <p class="auth-notice__title">Action effectuée</p>
                                    <p class="auth-notice__message"><?php echo e($successMessage); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($errors !== []): ?>
                        <div class="auth-notice auth-notice--soft-danger mb-5" role="alert">
                            <div class="auth-notice__row">
                                <span class="auth-notice__icon" aria-hidden="true">
                                    <i class="fas fa-shield-alt"></i>
                                </span>
                                <div class="auth-notice__copy">
                                    <p class="auth-notice__title">Connexion impossible</p>
                                    <?php if (count($errors) === 1): ?>
                                        <p class="auth-notice__message"><?php echo e((string) $errors[0]); ?></p>
                                    <?php else: ?>
                                        <ul class="auth-notice__list list-disc space-y-1">
                                            <?php foreach ($errors as $error): ?>
                                                <li><?php echo e((string) $error); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Formulaire -->
                    <form id="loginForm" action="<?php echo e(site_url('login')); ?>" method="POST" novalidate>
                        <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                        <input type="hidden" name="return" value="<?php echo e($return); ?>">

                        <!-- Champ email -->
                        <div class="mb-5">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2" for="email">
                                <span class="auth-label-with-icon"><i class="fas fa-envelope text-sky-600 dark:text-sky-300"></i><span>Adresse e-mail</span></span>
                            </label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                autocomplete="email"
                                inputmode="email"
                                placeholder="vous@exemple.com"
                                value="<?php echo e($oldEmail); ?>"
                                class="w-full px-5 py-3 border border-gray-300 dark:border-gray-600 rounded-2xl bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-blue-400 transition"
                                required
                                <?php echo $oldEmail === '' && $errors === [] ? 'autofocus' : ''; ?>
                            >
                            <p id="emailError" class="auth-field-message auth-field-message--error" aria-live="polite"></p>
                        </div>

                        <!-- Champ mot de passe -->
                        <div class="mb-3">
                            <div class="flex items-center justify-between gap-3 mb-2">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300" for="password">
                                    <span class="auth-label-with-icon"><i class="fas fa-lock text-amber-500 dark:text-amber-300"></i><span>Mot de passe</span></span>
                                </label>
                                <a href="<?php echo e(site_url('forgot')); ?>" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">Mot de passe oublié ?</a>
                            </div>
                            <div class="relative">
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    autocomplete="current-password"
                                    placeholder="Votre mot de passe"
                                    class="w-full px-5 py-3 pr-24 border border-gray-300 dark:border-gray-600 rounded-2xl bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-blue-400 transition"
                                    required
                                >
                                <button
                                    type="button"
                                    class="auth-password-toggle absolute right-2 top-1/2 -translate-y-1/2"
                                    data-password-toggle="#password"
                                    data-label-show="Afficher"
                                    data-label-hide="Masquer"
                                >
                                    <span data-password-toggle-label>Afficher</span>
                                </button>
                            </div>
                            <p id="passwordError" class="auth-field-message auth-field-message--error" aria-live="polite"></p>
                        </div>

                        <!-- Ligne : Rester connecté + Mot de passe oublié -->
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
                            <label class="flex items-center gap-2 cursor-pointer" for="rememberMe">
                                <input type="checkbox" id="rememberMe" name="remember" value="1" class="w-4 h-4 text-blue-600 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500">
                                <span class="auth-label-with-icon text-sm text-gray-700 dark:text-gray-300"><i class="fas fa-user-check text-emerald-500 dark:text-emerald-300"></i><span>Rester connecté</span></span>
                            </label>
                        </div>

                        <!-- Bouton connexion -->
                        <button type="submit" class="auth-primary-button w-full font-semibold py-3 transition flex items-center justify-center gap-2" data-no-loader="1">
                            Se connecter <i class="fas fa-arrow-right text-sm"></i>
                        </button>

                        <!-- Séparateur -->
                        <div class="relative my-6">
                            <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-200 dark:border-gray-700"></div></div>
                            <div class="relative flex justify-center text-sm"><span class="px-3 bg-white dark:bg-gray-800 text-gray-400 dark:text-gray-500">OU</span></div>
                        </div>

                        <!-- Bouton Google -->
                        <a href="<?php echo e($googleUrl); ?>" class="auth-social-button w-full inline-flex items-center justify-center gap-3 py-3 font-semibold transition">
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

                        <!-- Lien vers inscription -->
                        <p class="text-center text-sm text-gray-500 dark:text-gray-400 mt-6">
                            Pas encore de compte ? <a href="<?php echo e(site_url('register')); ?>" class="text-blue-600 dark:text-blue-400 font-semibold hover:underline">Inscription</a>
                        </p>
                    </form>

                    <!-- Mention sécurité -->
                    <p class="text-center text-xs text-gray-400 dark:text-gray-500 mt-5 flex items-center justify-center gap-1">
                        <i class="fas fa-shield-alt"></i> Connexion sécurisée
                    </p>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
