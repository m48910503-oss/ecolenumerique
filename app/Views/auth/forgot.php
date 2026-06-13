<?php
$message = trim((string) ($message ?? ''));
$hasConfirmation = $message !== '';
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
    <title>Réinitialisation | École Numérique</title>
    <!-- Tailwind CSS v3 + Font Awesome 6 + Google Fonts -->
    <script src="<?php echo e($assetUrl('assets/js/auth_forgot_tailwind.js')); ?>"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo e($assetUrl('assets/vendor/fontawesome/all.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e($assetUrl('assets/css/auth-teacher.css')); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { transition: background-color 0.3s ease, color 0.2s ease; }
        input:focus { outline: none; ring: none; }
        .fade-transition { transition: opacity 0.25s ease, transform 0.2s ease; }
    </style>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body data-view="auth/forgot" class="bg-gray-50 dark:bg-gray-900 font-sans antialiased transition-colors duration-300">

    <div class="fixed bottom-5 right-5 z-50 flex">
        <button id="darkModeToggle" class="bg-white dark:bg-gray-800 shadow-lg rounded-full w-11 h-11 flex items-center justify-center text-gray-700 dark:text-yellow-300 hover:scale-105 transition" type="button" aria-label="Changer le thème" aria-pressed="false">
            <i id="darkIcon" class="fas fa-moon text-xl"></i>
        </button>
    </div>

    <!-- Conteneur principal centré -->
    <div class="min-h-screen flex items-center justify-center p-4 md:p-6">
        <div class="w-full max-w-md transition-all duration-300">
            <!-- ÉTAPE 1 : Formulaire de demande d'email -->
            <div id="stepRequest" class="<?php echo $hasConfirmation ? 'hidden ' : ''; ?>bg-white dark:bg-gray-800 rounded-3xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
                <div class="p-6 md:p-8">
                    <div class="text-center mb-6">
                        <div class="mx-auto w-16 h-16 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-key text-blue-600 dark:text-blue-400 text-2xl"></i>
                        </div>
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white">Mot de passe oublié ?</h1>
                        <p class="text-gray-500 dark:text-gray-400 text-sm mt-2">Entrez votre e-mail pour recevoir un lien de réinitialisation.</p>
                    </div>

                    <form id="forgotForm" action="<?php echo e(site_url('forgot')); ?>" method="POST">
                        <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">

                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1" for="resetEmail">Adresse e-mail</label>
                            <input
                                type="email"
                                id="resetEmail"
                                name="email"
                                autocomplete="email"
                                inputmode="email"
                                placeholder="vous@exemple.com"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-blue-400 transition"
                                required
                                <?php echo $hasConfirmation ? '' : 'autofocus'; ?>
                            >
                        </div>

                        <button type="submit" class="auth-primary-button w-full font-semibold py-3 transition flex items-center justify-center gap-2">
                            Envoyer le lien <i class="fas fa-paper-plane text-sm"></i>
                        </button>

                        <div class="relative my-6">
                            <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-200 dark:border-gray-700"></div></div>
                            <div class="relative flex justify-center text-sm"><span class="px-3 bg-white dark:bg-gray-800 text-gray-400 dark:text-gray-500">OU</span></div>
                        </div>

                        <a href="<?php echo e(site_url('login')); ?>" id="backToLogin" class="auth-secondary-button w-full flex items-center justify-center gap-2 py-2.5 font-medium transition">
                            <i class="fas fa-arrow-left text-sm"></i> Retour à la connexion
                        </a>
                    </form>
                </div>
            </div>

            <!-- ÉTAPE 2 : Confirmation d'envoi -->
            <div id="stepConfirm" class="<?php echo $hasConfirmation ? '' : 'hidden '; ?>bg-white dark:bg-gray-800 rounded-3xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700 fade-transition">
                <div class="p-6 md:p-8 text-center">
                    <div class="mx-auto w-20 h-20 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-5">
                        <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-4xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">E-mail envoyé !</h2>
                    <p class="text-gray-500 dark:text-gray-400 mt-2 text-sm" id="confirmMessage">
                        Un lien de réinitialisation a été envoyé à <span id="submittedEmail" class="font-semibold text-gray-700 dark:text-gray-300">votre adresse e-mail</span>.
                    </p>
                    <p class="text-gray-400 dark:text-gray-500 text-xs mt-3">Vérifiez vos spams si vous ne le recevez pas sous quelques minutes.</p>
                    <?php if ($message !== ''): ?>
                        <p class="sr-only" role="status"><?php echo e($message); ?></p>
                    <?php endif; ?>

                    <div class="mt-8 space-y-3">
                        <a href="<?php echo e(site_url('login')); ?>" id="confirmBackToLogin" class="auth-primary-button block w-full font-semibold py-3 transition text-center">
                            Retour à la connexion
                        </a>
                        <form id="resendForm" action="<?php echo e(site_url('forgot')); ?>" method="POST">
                            <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                            <input type="hidden" name="email" id="resendEmail" value="">
                            <button id="resendLink" type="submit" class="w-full text-blue-600 dark:text-blue-400 text-sm hover:underline">
                                Renvoyer le lien
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="<?php echo e($assetUrl('assets/js/auth_forgot.js')); ?>" defer></script>
</body>
</html>
