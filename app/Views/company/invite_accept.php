<!doctype html>
<html lang="fr" class="light" data-default-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="app-global-assets" content="off">
    <title>Invitation entreprise</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo e(site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()))); ?>"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/standalone-dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/standalone-dashboard.css') ?: time()))); ?>">
    <script src="<?php echo e(site_url('assets/js/web_theme.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/web_theme.js') ?: time()))); ?>" defer></script>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="standalone-dashboard-page app-auth-page app-role-company min-h-screen text-slate-900 dark:text-slate-100 font-body flex items-center justify-center px-4 py-8" data-view="company/invite_accept">
    <?php
        $invite = is_array($invite ?? null) ? $invite : null;
        $inviteMode = strtolower(trim((string) (($invite['invite_mode'] ?? 'email'))));
        if (!in_array($inviteMode, ['email', 'link'], true)) {
            $inviteMode = 'email';
        }
        $approvalRequired = (!empty($invite['approval_required']) || $inviteMode === 'link');
        $guestEmail = (string) ($guestEmail ?? '');
        $siteLogoUrl = trim((string) ($siteLogoUrl ?? ''));
        $errors = is_array($errors ?? null) ? $errors : [];
        $successMessage = trim((string) ($successMessage ?? ''));
        $isLoggedIn = !empty($isLoggedIn);
        $firstName = (string) ($firstName ?? '');
        $lastName = (string) ($lastName ?? '');
        $token = (string) ($token ?? '');
    ?>
    <div class="relative z-10 w-full max-w-md bg-white/90 backdrop-blur border border-slate-200 rounded-2xl p-8 shadow-2xl shadow-slate-200/70 dark:bg-slate-900/80 dark:border-slate-800 dark:shadow-slate-950/60 space-y-4">
        <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-xl border border-slate-200 bg-white flex items-center justify-center font-bold text-slate-900 dark:bg-slate-800 dark:border-slate-700 dark:text-white">
                <?php if ($siteLogoUrl !== ''): ?>
                    <img src="<?php echo e($siteLogoUrl); ?>" alt="Logo" class="h-full w-full object-contain rounded-lg">
                <?php else: ?>
                    INV
                <?php endif; ?>
            </div>
            <div>
                <p class="text-lg font-display font-semibold">Invitation entreprise</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Rejoindre une entreprise</p>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="app-flash app-flash--danger">
                <?php foreach ($errors as $err): ?>
                    <p>- <?php echo e((string) $err); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($successMessage !== ''): ?>
            <div class="app-flash app-flash--success">
                <p><?php echo e($successMessage); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($invite && $successMessage === '' && empty($errors)): ?>
            <div class="text-sm text-slate-600 dark:text-slate-300">
                <p>Entreprise : <span class="font-semibold"><?php echo e((string) ($invite['company_name'] ?? '')); ?></span></p>
                <p>Mode d’invitation : <?php echo e($inviteMode === 'link' ? 'Lien unique' : 'E-mail'); ?></p>
                <?php if (!empty($invite['email'])): ?>
                    <p>E-mail invité : <?php echo e((string) ($invite['email'] ?? '')); ?></p>
                <?php endif; ?>
                <?php if ($approvalRequired): ?>
                    <p class="mt-1 text-xs">Validation administrateur requise après acceptation.</p>
                <?php endif; ?>
            </div>

            <?php if ($isLoggedIn): ?>
                <form method="post" class="space-y-3">
                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                    <button class="app-auth-submit w-full py-3 rounded-xl font-semibold transition" type="submit">
                        <?php echo e($approvalRequired ? 'Envoyer la demande d\'ajout' : 'Accepter l\'invitation'); ?>
                    </button>
                </form>
            <?php else: ?>
                <form method="post" class="space-y-3">
                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                    <div>
                        <label class="block text-sm mb-1" for="first_name">Prénom</label>
                        <input class="app-auth-input w-full px-4 py-3 rounded-lg bg-white border border-slate-300 outline-none dark:bg-slate-800 dark:border-slate-700" type="text" id="first_name" name="first_name" value="<?php echo e($firstName); ?>" required>
                    </div>
                    <div>
                        <label class="block text-sm mb-1" for="last_name">Nom</label>
                        <input class="app-auth-input w-full px-4 py-3 rounded-lg bg-white border border-slate-300 outline-none dark:bg-slate-800 dark:border-slate-700" type="text" id="last_name" name="last_name" value="<?php echo e($lastName); ?>" required>
                    </div>
                    <?php if ($inviteMode === 'link'): ?>
                        <div>
                            <label class="block text-sm mb-1" for="email">E-mail</label>
                            <input class="app-auth-input w-full px-4 py-3 rounded-lg bg-white border border-slate-300 outline-none dark:bg-slate-800 dark:border-slate-700" type="email" id="email" name="email" value="<?php echo e($guestEmail); ?>" required>
                        </div>
                    <?php else: ?>
                        <div>
                            <label class="block text-sm mb-1" for="email">E-mail invité</label>
                            <input class="w-full px-4 py-3 rounded-lg bg-slate-100 border border-slate-300 text-slate-600 outline-none dark:bg-slate-800/60 dark:border-slate-700 dark:text-slate-300" type="email" id="email" value="<?php echo e((string) ($invite['email'] ?? '')); ?>" readonly>
                        </div>
                    <?php endif; ?>
                    <div>
                        <label class="block text-sm mb-1" for="password">Mot de passe</label>
                        <input class="app-auth-input w-full px-4 py-3 rounded-lg bg-white border border-slate-300 outline-none dark:bg-slate-800 dark:border-slate-700" type="password" id="password" name="password" required>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">8+ caractères, 1 majuscule, 1 chiffre, 1 caractère spécial.</p>
                    </div>
                    <div>
                        <label class="block text-sm mb-1" for="password_confirm">Confirmer le mot de passe</label>
                        <input class="app-auth-input w-full px-4 py-3 rounded-lg bg-white border border-slate-300 outline-none dark:bg-slate-800 dark:border-slate-700" type="password" id="password_confirm" name="password_confirm" required>
                    </div>
                    <button class="app-auth-submit w-full py-3 rounded-xl font-semibold transition" type="submit">
                        <?php echo e($approvalRequired ? 'Créer mon compte et demander l\'ajout' : 'Créer mon compte et rejoindre'); ?>
                    </button>
                </form>
                <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                    <div class="h-px flex-1 bg-slate-200 dark:bg-slate-800"></div>
                    <span>ou</span>
                    <div class="h-px flex-1 bg-slate-200 dark:bg-slate-800"></div>
                </div>
                <a href="<?php echo e(site_url('oauth/google?src=company_invite&invite_token=' . urlencode((string) $token))); ?>" class="w-full inline-flex items-center justify-center gap-2 py-3 rounded-xl border border-slate-200 text-sm font-semibold text-slate-800 hover:border-slate-400 bg-white dark:border-slate-700 dark:text-slate-100 dark:hover:border-slate-500 dark:bg-slate-900">
                    <span>Continuer avec Google</span>
                </a>
                <p class="text-xs text-slate-500">Déjà un compte ? Connectez-vous puis finalisez l’invitation.</p>
            <?php endif; ?>
        <?php endif; ?>

        <p class="text-sm text-slate-600 dark:text-slate-300"><a class="app-link-primary underline" href="<?php echo e(site_url('login')); ?>">Aller à la connexion</a></p>
    </div>
</body>
</html>

