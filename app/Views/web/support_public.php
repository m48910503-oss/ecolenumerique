<?php
$guestProfile = is_array($guestProfile ?? null) ? $guestProfile : [];
$basePath = (string) ($basePath ?? rtrim(BASE_PATH, '/'));
$initialConv = max(0, (int) ($initialConv ?? 0));
$guestName = trim((string) ($guestProfile['name'] ?? ''));
$guestEmail = trim((string) ($guestProfile['email'] ?? ''));
$loginUrl = trim((string) ($loginUrl ?? site_url('login')));
$siteIdentity = function_exists('site_public_identity') ? site_public_identity() : [];
$siteName = trim((string) ($siteIdentity['siteName'] ?? (defined('APP_FROM_NAME') ? (string) APP_FROM_NAME : 'Ecole Numerique')));
if ($siteName === '') {
    $siteName = 'Ecole Numerique';
}
$siteLogoUrl = trim((string) ($siteLogoUrl ?? ''));
$guestGreeting = 'Bonjour ! Racontez votre besoin, nous vous repondons ici meme sans compte.';
?>
<!doctype html>
<html lang="fr" class="light" data-default-theme="light" data-view="web/support">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support en direct · <?php echo e($siteName); ?></title>
    <meta name="csrf-token" content="<?php echo e(view_csrf_token()); ?>">
    <meta name="app-global-assets" content="off">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo e(site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()))); ?>"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/vendor/fontawesome/all.min.css?v=' . (@filemtime(__DIR__ . '/../../../assets/vendor/fontawesome/all.min.css') ?: time()))); ?>">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/support.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/support.css') ?: time()))); ?>">
    <script src="<?php echo e(site_url('assets/js/web_theme.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/web_theme.js') ?: time()))); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/support_page.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/support_page.js') ?: time()))); ?>" defer></script>
    <style>
        body.guest-support-page #chat-widget {
            position: static !important;
            inset: auto !important;
            width: 100% !important;
            max-width: 100% !important;
            max-height: none !important;
        }

        body.guest-support-page .chat-widget.hidden {
            display: none !important;
        }
    </style>
</head>
<body
    class="support-page guest-support-page min-h-screen overflow-x-hidden bg-slate-950 font-sans text-slate-100 antialiased"
    data-view="web/support"
    data-support-space="guest"
    data-base-path="<?php echo e($basePath); ?>"
    data-user-id="0"
    data-initial-conv="<?php echo e((string) $initialConv); ?>"
>
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -left-20 top-[-6rem] h-72 w-72 rounded-full bg-cyan-500/20 blur-3xl"></div>
        <div class="absolute right-0 top-24 h-80 w-80 rounded-full bg-indigo-500/20 blur-3xl"></div>
        <div class="absolute bottom-0 left-1/3 h-96 w-96 rounded-full bg-emerald-500/10 blur-3xl"></div>
    </div>

    <main class="relative mx-auto flex min-h-screen w-full max-w-7xl flex-col gap-10 px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
        <header class="flex flex-col gap-6 rounded-[2rem] border border-white/10 bg-white/5 p-6 shadow-2xl shadow-slate-950/30 backdrop-blur-xl lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-3xl space-y-4">
                <div class="flex items-center gap-3">
                    <?php if ($siteLogoUrl !== ''): ?>
                        <img src="<?php echo e($siteLogoUrl); ?>" alt="" class="h-12 w-12 rounded-2xl bg-white/10 object-contain p-2 shadow-lg shadow-slate-950/20">
                    <?php else: ?>
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 text-lg text-cyan-200 shadow-lg shadow-slate-950/20">
                            <i class="fas fa-headset" aria-hidden="true"></i>
                        </div>
                    <?php endif; ?>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.24em] text-cyan-200">Assistance <?php echo e($siteName); ?></p>
                        <p class="text-sm text-slate-300">Point de contact visiteur</p>
                    </div>
                </div>
                <div class="inline-flex items-center gap-2 rounded-full border border-cyan-400/20 bg-cyan-400/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-cyan-100">
                    <i class="fas fa-headset" aria-hidden="true"></i>
                    Support instantane sans connexion
                </div>
                <div class="space-y-4">
                    <h1 class="text-4xl font-black tracking-tight text-white sm:text-5xl lg:text-6xl">
                        Parlez au support, sans creer de compte.
                    </h1>
                    <p class="max-w-2xl text-base leading-8 text-slate-300 sm:text-lg">
                        Ouvrez le chat, indiquez votre nom et votre adresse email si vous voulez etre recontacte, puis envoyez votre premiere demande. La conversation reste dans cette session de navigateur.
                    </p>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <button
                        id="chat-toggle-btn"
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-cyan-400 px-5 py-3 text-sm font-bold text-slate-950 shadow-[0_14px_36px_-18px_rgba(34,211,238,0.9)] transition hover:bg-cyan-300"
                    >
                        <i class="fas fa-comments" aria-hidden="true"></i>
                        Ouvrir le chat
                    </button>
                    <a
                        href="<?php echo e($loginUrl); ?>"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl border border-white/15 bg-white/5 px-5 py-3 text-sm font-semibold text-white transition hover:border-white/25 hover:bg-white/10"
                    >
                        <i class="fas fa-right-to-bracket" aria-hidden="true"></i>
                        Se connecter
                    </a>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-3 lg:w-[30rem]">
                <div class="rounded-2xl border border-white/10 bg-slate-900/70 p-4">
                    <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-cyan-200">Reponse rapide</p>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Une fois le chat ouvert, vous pouvez ecrire tout de suite sans passer par l'inscription.</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-slate-900/70 p-4">
                    <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-cyan-200">Pieces jointes</p>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Ajoutez une capture ou un PDF pour montrer le probleme plus vite.</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-slate-900/70 p-4">
                    <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-cyan-200">Historique</p>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Vos messages restent accessibles tant que cette session de navigateur est conservee.</p>
                </div>
            </div>
        </header>

        <section class="grid gap-6 lg:grid-cols-[1.1fr_minmax(360px,430px)]">
            <article class="space-y-6 rounded-[2rem] border border-white/10 bg-white/5 p-6 shadow-2xl shadow-slate-950/25 backdrop-blur-xl">
                <div class="space-y-4">
                    <p class="text-sm font-semibold uppercase tracking-[0.22em] text-cyan-200">Pourquoi ce mode ?</p>
                    <h2 class="text-2xl font-bold text-white sm:text-3xl">Le support sans compte sert aux visiteurs qui veulent aller droit au but.</h2>
                    <p class="max-w-2xl text-sm leading-7 text-slate-300 sm:text-base">
                        Si vous avez deja un compte, la connexion reste le meilleur choix pour retrouver tous vos echanges et vos tickets. Sinon, le chat invite vous permet de poser une question tout de suite.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-white/10 bg-slate-900/70 p-5">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-200">Simplicite</p>
                        <p class="mt-3 text-sm leading-6 text-slate-300">Un nom, un email si besoin, puis votre message. Rien de plus.</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-slate-900/70 p-5">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-200">Suivi</p>
                        <p class="mt-3 text-sm leading-6 text-slate-300">Les agents support reçoivent votre conversation dans leur console habituelle.</p>
                    </div>
                </div>

                <div class="rounded-[1.75rem] border border-cyan-400/15 bg-cyan-400/10 p-5">
                    <p class="text-sm font-semibold text-cyan-100">Conseil</p>
                    <p class="mt-2 text-sm leading-6 text-slate-200">
                        Pour un suivi plus complet, connectez-vous ensuite avec votre compte. Le chat visiteur restera utile pour un premier contact rapide.
                    </p>
                </div>
            </article>

            <section
                id="chat-widget"
                class="chat-widget fixed bottom-24 right-4 z-50 flex w-[calc(100vw-2rem)] flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl animate-bounce-in hidden dark:border-gray-700 dark:bg-gray-800 sm:right-6 sm:w-96 lg:static lg:z-auto lg:w-full"
            >
                <div class="flex items-center justify-between bg-primary-600 p-4 text-white">
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-full border-2 border-white bg-white/20">
                            <i class="fas fa-headset" aria-hidden="true"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold">Assistance <?php echo e($siteName); ?></p>
                            <p id="chat-thread-meta" class="text-xs text-primary-100">Chat visiteur actif. Vous pouvez ecrire sans compte.</p>
                        </div>
                    </div>
                    <div class="flex gap-1">
                        <button id="minimize-chat" type="button" class="rounded p-1 transition hover:bg-primary-700" aria-label="Minimiser">
                            <i class="fas fa-minus text-xs" aria-hidden="true"></i>
                        </button>
                        <button id="close-chat" type="button" class="rounded p-1 transition hover:bg-primary-700" aria-label="Fermer">
                            <i class="fas fa-times text-xs" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <div id="chat-messages" class="custom-scroll flex-1 space-y-3 overflow-y-auto p-3 max-h-[340px] min-h-[180px]">
                    <div class="flex justify-start">
                        <div class="max-w-[80%] px-4 py-2 text-sm chat-bubble-received">
                            <?php echo e($guestGreeting); ?>
                        </div>
                    </div>
                </div>

                <form id="support-message-form" class="border-t border-gray-100 p-3 dark:border-gray-700" enctype="multipart/form-data">
                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                    <input type="hidden" name="conversation_id" id="support-conv-id" value="">
                    <p id="chat-thread-status" class="hidden rounded-xl border px-3 py-2 text-sm"></p>

                    <div class="mb-3 grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="support-guest-name" class="mb-1 block text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">Nom</label>
                            <input
                                id="support-guest-name"
                                name="guest_name"
                                type="text"
                                value="<?php echo e($guestName); ?>"
                                placeholder="Votre nom"
                                class="w-full rounded-xl border border-gray-200 bg-gray-100 px-3 py-2 text-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500/20"
                                required
                            >
                        </div>
                        <div>
                            <label for="support-guest-email" class="mb-1 block text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">Email</label>
                            <input
                                id="support-guest-email"
                                name="guest_email"
                                type="email"
                                value="<?php echo e($guestEmail); ?>"
                                placeholder="Adresse email"
                                class="w-full rounded-xl border border-gray-200 bg-gray-100 px-3 py-2 text-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500/20"
                            >
                        </div>
                    </div>

                    <div class="mt-3 flex items-center gap-2">
                        <input
                            type="text"
                            id="chat-input"
                            name="body"
                            placeholder="Ecrivez votre message..."
                            class="flex-1 rounded-xl border-none bg-gray-100 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700"
                        >
                        <label for="chat-attachments" class="support-chat-attach inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-xl transition" aria-label="Joindre une capture" title="Joindre une capture">
                            <i class="fas fa-paperclip" aria-hidden="true"></i>
                        </label>
                        <input id="chat-attachments" name="attachments[]" type="file" accept="image/*,.pdf" multiple class="hidden">
                        <button id="send-chat-btn" type="submit" class="rounded-xl bg-primary-600 p-2 text-white transition hover:bg-primary-700" aria-label="Envoyer">
                            <i class="fas fa-paper-plane" aria-hidden="true"></i>
                        </button>
                    </div>
                    <p id="chat-files-hint" class="mt-2 hidden text-xs text-gray-500 dark:text-gray-400"></p>
                    <p class="mt-3 text-xs leading-6 text-gray-500 dark:text-gray-400">
                        Votre nom aide l'equipe support a vous reconnaitre. L'email est facultatif mais conseille pour une reprise de contact.
                    </p>
                </form>
            </section>
        </section>
    </main>
</body>
</html>
