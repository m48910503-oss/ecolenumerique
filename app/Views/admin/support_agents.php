<?php
$errors = is_array($errors ?? null) ? $errors : [];
$successMessage = (string) ($successMessage ?? '');
$agents = is_array($agents ?? null) ? $agents : [];
$loginUrl = (string) ($loginUrl ?? site_url('login'));
$supportConsoleUrl = (string) ($supportConsoleUrl ?? site_url('admin/support'));
$isGeneralAdmin = !empty($isGeneralAdmin);
$agentSearchQuery = trim((string) ($_GET['q'] ?? ''));

$adminNavActive = 'support_agents';
$adminTopTitle = 'Équipe support';
$adminTopSubtitle = 'Support';
$adminTopBadge = (string) count($agents) . ' agents';
$adminTopBadgeTone = count($agents) > 0 ? 'active' : 'pending';
$adminTopSearchAction = site_url('admin/support_agents');
$adminTopSearchValue = $agentSearchQuery;
$adminTopSearchPlaceholder = 'Rechercher un agent support...';
$adminTopActions = [
    ['label' => 'Tickets support', 'url' => site_url('admin/support'), 'tone' => 'neutral'],
    ['label' => 'Tableau de bord', 'url' => site_url('admin/dashboard'), 'tone' => 'neutral'],
];
if ($isGeneralAdmin) {
    $adminTopActions[] = ['label' => 'Admins secondaires', 'url' => site_url('admin/secondary_admins'), 'tone' => 'neutral'];
}

$normalizeStatus = static function (mixed $status): string {
    $normalized = strtolower(trim((string) $status));
    if ($normalized === '' || $normalized === '0' || $normalized === 'false') {
        return 'inactive';
    }
    if ($normalized === 'pending_email') {
        return 'pending';
    }
    if (in_array($normalized, ['disabled', 'blocked', 'banned'], true)) {
        return 'suspended';
    }

    return $normalized;
};

$statusLabel = static function (string $status): string {
    $map = [
        'active' => 'Actif',
        'inactive' => 'Inactif',
        'pending' => 'En attente',
        'suspended' => 'Suspendu',
    ];

    return $map[$status] ?? ($status !== '' ? ucfirst($status) : 'Inactif');
};

$statusTone = static function (string $status): string {
    $map = [
        'active' => 'support-agent-status support-agent-status--active',
        'inactive' => 'support-agent-status support-agent-status--inactive',
        'pending' => 'support-agent-status support-agent-status--pending',
        'suspended' => 'support-agent-status support-agent-status--suspended',
    ];

    return $map[$status] ?? 'support-agent-status support-agent-status--inactive';
};

$statusIcon = static function (string $status): string {
    return match ($status) {
        'active' => 'fa-check-circle',
        'pending' => 'fa-hourglass-half',
        'suspended' => 'fa-ban',
        default => 'fa-pause-circle',
    };
};

$initialsFromName = static function (string $name): string {
    $clean = trim(preg_replace('/\s+/', ' ', $name) ?? $name);
    if ($clean === '') {
        return 'SP';
    }
    $parts = explode(' ', $clean);
    $initials = '';
    foreach ($parts as $part) {
        if ($part === '') {
            continue;
        }
        $initials .= function_exists('mb_strtoupper')
            ? mb_strtoupper((string) mb_substr($part, 0, 1))
            : strtoupper(substr($part, 0, 1));
        if (strlen($initials) >= 2) {
            break;
        }
    }

    return $initials !== '' ? $initials : 'SP';
};

$formatDate = static function (mixed $value): string {
    $raw = trim((string) $value);
    if ($raw === '') {
        return 'Date inconnue';
    }
    try {
        return (new DateTimeImmutable($raw))->format('d/m/Y H:i');
    } catch (Throwable) {
        return $raw;
    }
};

$agentCards = [];
$agentStats = [
    'total' => count($agents),
    'active' => 0,
    'pending' => 0,
    'inactive' => 0,
    'suspended' => 0,
];
foreach ($agents as $agent) {
    if (!is_array($agent)) {
        continue;
    }
    $name = trim((string) ($agent['first_name'] ?? '') . ' ' . (string) ($agent['last_name'] ?? ''));
    if ($name === '') {
        $name = 'Agent support';
    }
    $email = trim((string) ($agent['email'] ?? ''));
    $statusKey = $normalizeStatus($agent['status'] ?? '');
    if (!array_key_exists($statusKey, $agentStats)) {
        $agentStats[$statusKey] = 0;
    }
    $agentStats[$statusKey]++;
    $statusText = $statusLabel($statusKey);
    $agentCards[] = [
        'id' => (int) ($agent['id'] ?? 0),
        'name' => $name,
        'email' => $email,
        'initials' => $initialsFromName($name),
        'status' => $statusKey,
        'status_label' => $statusText,
        'status_icon' => $statusIcon($statusKey),
        'status_class' => $statusTone($statusKey),
        'created_at' => $formatDate($agent['created_at'] ?? ''),
        'search' => trim(strtolower($name . ' ' . $email . ' ' . $statusText)),
    ];
}

$modalToAutoOpen = '';
if ($errors !== [] && strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
    $postedAction = (string) ($_POST['action'] ?? '');
    if ($postedAction === 'create') {
        $modalToAutoOpen = 'support-create-modal';
    } elseif ($postedAction === 'grant') {
        $modalToAutoOpen = 'support-grant-modal';
    } elseif ($postedAction === 'revoke') {
        $modalToAutoOpen = 'support-remove-modal';
    }
}
$adminPageScripts = ['admin_support_agents.js'];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrateur - Équipe support</title>
    <?php require __DIR__ . '/_head_assets.php'; ?>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="app-view app-role-admin min-h-screen bg-slate-50 text-slate-900 font-body dark:bg-slate-950 dark:text-slate-100" data-view="admin/support_agents" data-sidebar-desktop-toggle="1" data-support-open-modal="<?php echo e($modalToAutoOpen); ?>">
    <div class="admin-shell-backdrop">
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--one"></div>
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--two"></div>
    </div>

    <div class="admin-shell-root relative z-10 flex min-h-screen">
        <?php require __DIR__ . '/_sidebar.php'; ?>
        <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

        <div class="admin-shell-main flex min-w-0 flex-1 flex-col">
            <?php require __DIR__ . '/_topbar.php'; ?>
            <main class="admin-shell-content admin-shell-dense support-agents-page px-4 py-5 sm:px-6 sm:py-6 space-y-6">
                <?php if ($errors): ?>
                    <div class="admin-shell-alert admin-shell-alert--error">
                        <?php foreach ($errors as $err): ?>
                            <p class="inline-flex items-center gap-2"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i><?php echo e((string) $err); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ($successMessage !== ''): ?>
                    <div class="admin-shell-alert admin-shell-alert--success">
                        <p class="inline-flex items-center gap-2"><i class="fas fa-check-circle" aria-hidden="true"></i><?php echo e($successMessage); ?></p>
                    </div>
                <?php endif; ?>

                <section class="bg-gradient-to-r from-primary-600 to-accent-600 rounded-2xl p-6 text-white shadow-glow overflow-hidden relative">
                    <div class="absolute -right-10 -top-12 h-40 w-40 rounded-full bg-white/10"></div>
                    <div class="absolute bottom-0 right-28 h-20 w-20 rounded-full bg-white/10 blur-sm"></div>
                    <div class="relative flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                        <div class="max-w-3xl">
                            <p class="text-xs font-bold uppercase tracking-[0.22em] text-primary-100">
                                <i class="fas fa-user-shield mr-2"></i>Accès support
                            </p>
                            <h2 class="mt-2 text-2xl font-bold sm:text-3xl">Gérer les agents de support</h2>
                            <p class="mt-2 text-sm text-primary-100 sm:text-base">Créez, attribuez ou retirez l’accès aux personnes qui traitent les tickets.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="js-modal-open inline-flex items-center gap-2 rounded-xl bg-white px-4 py-3 text-sm font-bold text-primary-700 shadow-sm transition hover:bg-primary-50" data-modal-target="support-create-modal">
                                <i class="fas fa-user-plus" aria-hidden="true"></i>
                                Nouveau compte
                            </button>
                            <button type="button" class="js-modal-open inline-flex items-center gap-2 rounded-xl bg-white/15 px-4 py-3 text-sm font-bold text-white ring-1 ring-white/25 transition hover:bg-white/25" data-modal-target="support-grant-modal">
                                <i class="fas fa-key" aria-hidden="true"></i>
                                Attribuer un rôle
                            </button>
                            <a href="<?php echo e($supportConsoleUrl); ?>" class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-4 py-3 text-sm font-bold text-white ring-1 ring-white/25 transition hover:bg-white/25">
                                <i class="fas fa-inbox" aria-hidden="true"></i>
                                Tickets
                            </a>
                        </div>
                    </div>
                </section>

                <section class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                    <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Agents</p>
                                <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white"><?php echo e((string) $agentStats['total']); ?></p>
                            </div>
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-100 text-primary-600 dark:bg-blue-900/30 dark:text-blue-300">
                                <i class="fas fa-headset"></i>
                            </span>
                        </div>
                    </article>

                    <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Actifs</p>
                                <p class="mt-1 text-3xl font-bold text-green-600 dark:text-green-300"><?php echo e((string) ($agentStats['active'] ?? 0)); ?></p>
                            </div>
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-300">
                                <i class="fas fa-check-circle"></i>
                            </span>
                        </div>
                    </article>

                    <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">En attente</p>
                                <p class="mt-1 text-3xl font-bold text-amber-600 dark:text-amber-300"><?php echo e((string) ($agentStats['pending'] ?? 0)); ?></p>
                            </div>
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-300">
                                <i class="fas fa-hourglass-half"></i>
                            </span>
                        </div>
                    </article>

                    <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Bloqués</p>
                                <p class="mt-1 text-3xl font-bold text-red-600 dark:text-red-300"><?php echo e((string) (($agentStats['suspended'] ?? 0) + ($agentStats['inactive'] ?? 0))); ?></p>
                            </div>
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-300">
                                <i class="fas fa-ban"></i>
                            </span>
                        </div>
                    </article>
                </section>

                <section class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                <i class="fas fa-users-cog mr-2 text-primary-600 dark:text-primary-300"></i>Comptes support
                            </h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Filtrez l’équipe et retirez uniquement le rôle support si nécessaire.</p>
                        </div>
                        <div class="grid w-full gap-3 sm:grid-cols-[minmax(0,1fr)_12rem] xl:w-auto xl:min-w-[34rem]">
                            <div class="relative">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input id="support-agent-search" type="search" value="<?php echo e($agentSearchQuery); ?>" placeholder="Rechercher un agent..." class="admin-shell-input w-full py-3 pl-10 pr-3 text-sm">
                            </div>
                            <select id="support-agent-status-filter" class="admin-shell-input w-full px-3 py-3 text-sm">
                                <option value="">Tous les statuts</option>
                                <option value="active">Actifs</option>
                                <option value="pending">En attente</option>
                                <option value="inactive">Inactifs</option>
                                <option value="suspended">Suspendus</option>
                            </select>
                        </div>
                    </div>

                    <?php if ($agentCards === []): ?>
                        <div class="mt-5 rounded-2xl border border-dashed border-gray-200 bg-gray-50 p-8 text-center dark:border-gray-700 dark:bg-gray-900/40">
                            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-100 text-primary-600 dark:bg-primary-900/30 dark:text-primary-300">
                                <i class="fas fa-headset"></i>
                            </span>
                            <h4 class="mt-3 text-base font-bold text-gray-900 dark:text-white">Aucun agent support</h4>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Ajoutez un compte pour commencer à traiter les tickets.</p>
                        </div>
                    <?php else: ?>
                        <div id="support-agents-grid" class="mt-5 grid gap-4 xl:grid-cols-2">
                            <?php foreach ($agentCards as $agent): ?>
                                <article class="js-support-agent-card support-agent-card rounded-2xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40"
                                    data-agent-status="<?php echo e($agent['status']); ?>"
                                    data-agent-search="<?php echo e($agent['search']); ?>"
                                >
                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="flex min-w-0 items-start gap-3">
                                            <span class="support-avatar h-12 w-12 rounded-full text-sm"><?php echo e($agent['initials']); ?></span>
                                            <div class="min-w-0">
                                                <h4 class="truncate text-base font-bold text-gray-900 dark:text-white"><?php echo e($agent['name']); ?></h4>
                                                <p class="truncate text-sm text-gray-500 dark:text-gray-400"><?php echo e($agent['email'] !== '' ? $agent['email'] : 'Adresse e-mail absente'); ?></p>
                                                <div class="mt-3 flex flex-wrap gap-2">
                                                    <span class="<?php echo e($agent['status_class']); ?>">
                                                        <i class="fas <?php echo e($agent['status_icon']); ?>" aria-hidden="true"></i>
                                                        <?php echo e($agent['status_label']); ?>
                                                    </span>
                                                    <span class="support-agent-meta-pill">
                                                        <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                                                        <?php echo e($agent['created_at']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex shrink-0 gap-2 sm:flex-col">
                                            <a href="<?php echo e($supportConsoleUrl); ?>" class="support-agent-action support-agent-action--primary" title="Ouvrir les tickets">
                                                <i class="fas fa-inbox" aria-hidden="true"></i>
                                                <span class="sm:sr-only">Tickets</span>
                                            </a>
                                            <button
                                                type="button"
                                                class="js-support-remove support-agent-action support-agent-action--danger"
                                                data-user-id="<?php echo e((string) $agent['id']); ?>"
                                                title="Retirer le rôle support"
                                            >
                                                <i class="fas fa-user-minus" aria-hidden="true"></i>
                                                <span class="sm:sr-only">Retirer</span>
                                            </button>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <div id="support-agent-empty-state" class="mt-5 hidden rounded-2xl border border-dashed border-gray-200 bg-gray-50 p-8 text-center dark:border-gray-700 dark:bg-gray-900/40">
                            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-300">
                                <i class="fas fa-search"></i>
                            </span>
                            <h4 class="mt-3 text-base font-bold text-gray-900 dark:text-white">Aucun agent trouvé</h4>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Modifiez la recherche ou le filtre de statut.</p>
                        </div>
                    <?php endif; ?>
                </section>
            </main>
        </div>
    </div>

    <div id="support-create-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4 js-admin-modal" role="dialog" aria-modal="true" aria-labelledby="support-create-modal-title">
        <div class="admin-shell-modal admin-support-modal-panel w-full max-w-xl rounded-2xl p-5">
            <div class="flex items-start justify-between gap-4">
                <h3 id="support-create-modal-title" class="text-lg font-semibold text-slate-900 dark:text-slate-100 inline-flex items-center gap-2">
                    <i class="fas fa-user-plus admin-inline-accent admin-inline-accent--info" aria-hidden="true"></i>
                    Créer un compte support
                </h3>
                <button type="button" class="admin-shell-button admin-shell-button--ghost js-modal-close inline-flex items-center gap-1.5" data-modal-target="support-create-modal">
                    <i class="fas fa-times" aria-hidden="true"></i>
                    Fermer
                </button>
            </div>

            <form class="mt-4 admin-shell-form-grid" method="post" action="<?php echo e(site_url('admin/support_agents')); ?>">
                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                <input type="hidden" name="action" value="create">

                <div class="admin-shell-field">
                    <label class="admin-shell-label inline-flex items-center gap-2" for="support-create-first-name"><i class="fas fa-user" aria-hidden="true"></i>Prénom</label>
                    <input id="support-create-first-name" name="first_name" type="text" autocomplete="given-name" placeholder="Ex. : Awa" required class="admin-shell-input">
                </div>

                <div class="admin-shell-field">
                    <label class="admin-shell-label inline-flex items-center gap-2" for="support-create-last-name"><i class="fas fa-id-card" aria-hidden="true"></i>Nom</label>
                    <input id="support-create-last-name" name="last_name" type="text" autocomplete="family-name" placeholder="Ex. : Kouassi" required class="admin-shell-input">
                </div>

                <div class="admin-shell-field">
                    <label class="admin-shell-label inline-flex items-center gap-2" for="support-create-email"><i class="fas fa-envelope" aria-hidden="true"></i>E-mail</label>
                    <input id="support-create-email" name="email" type="email" autocomplete="email" placeholder="agent@domaine.com" required class="admin-shell-input">
                </div>

                <div class="admin-shell-field">
                    <label class="admin-shell-label inline-flex items-center gap-2" for="support-create-password"><i class="fas fa-lock" aria-hidden="true"></i>Mot de passe</label>
                    <input id="support-create-password" name="password" type="password" minlength="8" placeholder="8 caractères minimum" required class="admin-shell-input">
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Communiquez ce mot de passe via un canal sécurisé.</p>
                </div>

                <div class="mt-1 flex flex-wrap items-center justify-end gap-2">
                    <button type="button" class="admin-shell-button admin-shell-button--secondary js-modal-close inline-flex items-center gap-2" data-modal-target="support-create-modal"><i class="fas fa-times" aria-hidden="true"></i>Annuler</button>
                    <button type="submit" class="admin-shell-button admin-shell-button--primary inline-flex items-center gap-2">
                        <i class="fas fa-check" aria-hidden="true"></i>
                        Créer le compte
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="support-grant-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4 js-admin-modal" role="dialog" aria-modal="true" aria-labelledby="support-grant-modal-title">
        <div class="admin-shell-modal admin-support-modal-panel w-full max-w-xl rounded-2xl p-5">
            <div class="flex items-start justify-between gap-4">
                <h3 id="support-grant-modal-title" class="text-lg font-semibold text-slate-900 dark:text-slate-100 inline-flex items-center gap-2">
                    <i class="fas fa-key admin-inline-accent admin-inline-accent--info" aria-hidden="true"></i>
                    Attribuer le rôle support
                </h3>
                <button type="button" class="admin-shell-button admin-shell-button--ghost js-modal-close inline-flex items-center gap-1.5" data-modal-target="support-grant-modal">
                    <i class="fas fa-times" aria-hidden="true"></i>
                    Fermer
                </button>
            </div>

            <form class="mt-4 admin-shell-form-grid" method="post" action="<?php echo e(site_url('admin/support_agents')); ?>">
                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                <input type="hidden" name="action" value="grant">

                <div class="admin-shell-field">
                    <label class="admin-shell-label inline-flex items-center gap-2" for="support-grant-email"><i class="fas fa-envelope" aria-hidden="true"></i>E-mail utilisateur</label>
                    <input id="support-grant-email" name="email" type="email" autocomplete="email" placeholder="agent@domaine.com" required class="admin-shell-input">
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">L’agent doit se reconnecter pour voir la console support.</p>
                </div>

                <div class="mt-1 flex flex-wrap items-center justify-end gap-2">
                    <button type="button" class="admin-shell-button admin-shell-button--secondary js-modal-close inline-flex items-center gap-2" data-modal-target="support-grant-modal"><i class="fas fa-times" aria-hidden="true"></i>Annuler</button>
                    <button type="submit" class="admin-shell-button admin-shell-button--primary inline-flex items-center gap-2">
                        <i class="fas fa-check" aria-hidden="true"></i>
                        Ajouter le rôle
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="support-remove-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4 js-admin-modal" role="dialog" aria-modal="true" aria-labelledby="support-remove-modal-title">
        <div class="admin-shell-modal admin-support-modal-panel w-full max-w-md rounded-2xl p-5">
            <h3 id="support-remove-modal-title" class="text-lg font-semibold text-slate-900 dark:text-slate-100 inline-flex items-center gap-2">
                <i class="fas fa-exclamation-triangle admin-inline-accent admin-inline-accent--danger" aria-hidden="true"></i>
                Retirer le rôle support
            </h3>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Le compte utilisateur reste conservé. Seul son accès à la console support sera retiré.</p>

            <div class="mt-5 flex items-center justify-end gap-2">
                <button type="button" class="admin-shell-button admin-shell-button--secondary js-modal-close inline-flex items-center gap-2" data-modal-target="support-remove-modal"><i class="fas fa-times" aria-hidden="true"></i>Annuler</button>
                <form id="support-remove-form" method="post" action="<?php echo e(site_url('admin/support_agents')); ?>">
                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                    <input type="hidden" name="action" value="revoke">
                    <input type="hidden" name="user_id" id="support-remove-user-id" value="">
                    <button type="submit" class="admin-shell-button admin-shell-button--danger inline-flex items-center gap-2">
                        <i class="fas fa-user-minus" aria-hidden="true"></i>
                        Confirmer
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
