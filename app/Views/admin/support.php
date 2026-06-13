<?php
$adminPermissions = is_array($adminPermissions ?? null) ? $adminPermissions : [];
$isGeneralAdmin = !empty($isGeneralAdmin);
$hasAdminAccess = array_key_exists('hasAdminAccess', get_defined_vars()) ? (bool) $hasAdminAccess : ($isGeneralAdmin || $adminPermissions !== []);
$user = is_array($user ?? null) ? $user : [];
$siteLogoUrl = (string) ($siteLogoUrl ?? '');
$basePath = (string) ($basePath ?? rtrim(BASE_PATH, '/'));
$initialConv = (int) ($initialConv ?? 0);
$userId = (int) ($userId ?? ($user['id'] ?? 0));
$supportSearchQuery = trim((string) ($_GET['q'] ?? ''));

$can = static function (string $permission) use ($adminPermissions, $isGeneralAdmin, $hasAdminAccess): bool {
    if (!$hasAdminAccess) {
        return false;
    }

    return $isGeneralAdmin || in_array($permission, $adminPermissions, true);
};

$adminNavActive = 'support';
$adminTopTitle = 'Tickets support';
$adminTopSubtitle = 'Support';
$adminTopBadge = 'Console';
$adminTopBadgeTone = 'active';
$adminTopSearchAction = site_url('admin/support');
$adminTopSearchValue = $supportSearchQuery;
$adminTopSearchPlaceholder = 'Rechercher un ticket support...';
$adminTopActions = [
    ['label' => 'Notifications', 'url' => site_url('admin/notifications'), 'tone' => 'neutral', 'icon' => 'bell'],
];
if ($can('support_agents')) {
    $adminTopActions[] = ['label' => 'Équipe support', 'url' => site_url('admin/support_agents'), 'tone' => 'neutral'];
}
if ($can('dashboard')) {
    $adminTopActions[] = ['label' => 'Tableau de bord', 'url' => site_url('admin/dashboard'), 'tone' => 'neutral'];
}
$adminPageScripts = ['support_chat.js'];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrateur - Tickets support</title>
    <meta name="csrf-token" content="<?php echo e(view_csrf_token()); ?>">
    <?php require __DIR__ . '/_head_assets.php'; ?>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="app-view app-role-admin min-h-screen bg-slate-50 text-slate-900 font-body dark:bg-slate-950 dark:text-slate-100" data-view="admin/support" data-sidebar-desktop-toggle="1">
    <div class="admin-shell-backdrop">
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--one"></div>
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--two"></div>
    </div>

    <div class="admin-shell-root relative z-10 flex min-h-screen">
        <?php require __DIR__ . '/_sidebar.php'; ?>
        <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

        <div class="admin-shell-main flex min-w-0 flex-1 flex-col">
            <?php require __DIR__ . '/_topbar.php'; ?>
            <main class="admin-shell-content admin-shell-dense support-admin-console px-4 py-5 sm:px-6 sm:py-6 space-y-6"
                data-support-chat
                data-support-mode="staff"
                data-base-path="<?php echo e($basePath); ?>"
                data-user-id="<?php echo e((string) $userId); ?>"
                data-initial-conv="<?php echo e((string) $initialConv); ?>"
            >
                <section class="bg-gradient-to-r from-primary-600 to-accent-600 rounded-2xl p-6 text-white shadow-glow overflow-hidden relative">
                    <div class="absolute -right-8 -top-10 h-36 w-36 rounded-full bg-white/10"></div>
                    <div class="absolute bottom-0 right-24 h-24 w-24 rounded-full bg-white/10 blur-sm"></div>
                    <div class="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                        <div class="max-w-3xl">
                            <p class="text-xs font-bold uppercase tracking-[0.22em] text-primary-100">
                                <i class="fas fa-headset mr-2"></i>Centre support
                            </p>
                            <h2 class="mt-2 text-2xl font-bold sm:text-3xl">Traiter les demandes en temps réel</h2>
                            <p class="mt-2 text-sm text-primary-100 sm:text-base">File de tickets, assignation, réponses et clôture depuis une seule console.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button id="support-new-chat-btn" type="button" class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-4 py-3 text-sm font-bold text-white ring-1 ring-white/25 transition hover:bg-white/25">
                                <i class="fas fa-undo" aria-hidden="true"></i>
                                Réinitialiser
                            </button>
                            <button id="support-reset-filters" type="button" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-3 text-sm font-bold text-primary-700 shadow-sm transition hover:bg-primary-50">
                                <i class="fas fa-filter" aria-hidden="true"></i>
                                Effacer les filtres
                            </button>
                        </div>
                    </div>
                </section>

                <section class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                    <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Tickets ouverts</p>
                                <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white" data-support-stat-value="open">0</p>
                            </div>
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-100 text-primary-600 dark:bg-blue-900/30 dark:text-blue-300">
                                <i class="fas fa-ticket-alt"></i>
                            </span>
                        </div>
                        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">Demandes non clôturées</p>
                    </article>

                    <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">À traiter</p>
                                <p class="mt-1 text-3xl font-bold text-amber-600 dark:text-amber-300" data-support-stat-value="waiting_support">0</p>
                            </div>
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-300">
                                <i class="fas fa-clock"></i>
                            </span>
                        </div>
                        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">Réponse support attendue</p>
                    </article>

                    <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Non assignés</p>
                                <p class="mt-1 text-3xl font-bold text-cyan-600 dark:text-cyan-300" data-support-stat-value="unassigned">0</p>
                            </div>
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-cyan-100 text-cyan-600 dark:bg-cyan-900/30 dark:text-cyan-300">
                                <i class="fas fa-user-plus"></i>
                            </span>
                        </div>
                        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">Tickets sans agent</p>
                    </article>

                    <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Terminés</p>
                                <p class="mt-1 text-3xl font-bold text-green-600 dark:text-green-300" data-support-stat-value="closed">0</p>
                            </div>
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-300">
                                <i class="fas fa-check-circle"></i>
                            </span>
                        </div>
                        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">Historique conservé</p>
                    </article>
                </section>

                <section class="grid gap-6 xl:grid-cols-[minmax(320px,420px)_minmax(0,1fr)]">
                    <aside class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                    <i class="fas fa-inbox mr-2 text-primary-600 dark:text-primary-300"></i>File de tickets
                                </h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Sélectionnez un ticket pour ouvrir l’historique.</p>
                            </div>
                            <span class="rounded-full bg-primary-50 px-3 py-1 text-xs font-bold text-primary-700 dark:bg-primary-900/30 dark:text-primary-300" data-support-stat-label="total">0</span>
                        </div>

                        <div class="mt-5 space-y-3">
                            <label for="support-search" class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                <i class="fas fa-search mr-1"></i>Recherche
                            </label>
                            <div class="relative">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input id="support-search" type="text" value="<?php echo e($supportSearchQuery); ?>" placeholder="Sujet, nom ou mot-clé" class="support-input w-full rounded-xl border bg-gray-50 py-3 pl-10 pr-3 text-sm dark:bg-gray-900/60">
                            </div>
                        </div>

                        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
                            <div>
                                <label for="support-status-filter" class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-flag mr-1"></i>Statut
                                </label>
                                <select id="support-status-filter" class="support-input mt-2 w-full rounded-xl border bg-gray-50 px-3 py-3 text-sm dark:bg-gray-900/60">
                                    <option value="">Tous</option>
                                    <option value="open">Ouvert</option>
                                    <option value="waiting_support">En attente support</option>
                                    <option value="waiting_user">En attente utilisateur</option>
                                    <option value="closed">Terminé</option>
                                </select>
                            </div>
                            <div>
                                <label for="support-assigned-filter" class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-user-tag mr-1"></i>Assignation
                                </label>
                                <select id="support-assigned-filter" class="support-input mt-2 w-full rounded-xl border bg-gray-50 px-3 py-3 text-sm dark:bg-gray-900/60">
                                    <option value="">Toutes</option>
                                    <option value="me">À moi</option>
                                    <option value="none">Non assignées</option>
                                </select>
                            </div>
                        </div>

                        <div id="support-conv-list" class="support-admin-conversation-list mt-5 space-y-2"></div>
                    </aside>

                    <section class="bg-white dark:bg-gray-800 rounded-2xl shadow-card border border-gray-100 dark:border-gray-700 overflow-hidden flex min-h-[42rem] flex-col">
                        <div class="border-b border-gray-100 p-5 dark:border-gray-700">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div id="support-active-avatar" class="support-avatar h-12 w-12 rounded-full text-sm">SP</div>
                                    <div class="min-w-0">
                                        <h3 id="support-active-title" class="truncate text-lg font-bold text-gray-900 dark:text-white">Conversation support</h3>
                                        <p id="support-active-meta" class="text-sm text-gray-500 dark:text-gray-400">Sélectionnez une conversation.</p>
                                        <div id="support-active-status" class="mt-2 flex flex-wrap gap-2 text-[11px]"></div>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <button id="support-assign-btn" type="button" class="support-chip-btn inline-flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-bold">
                                        <i class="fas fa-user-check" aria-hidden="true"></i>
                                        Prendre
                                    </button>
                                    <button id="support-close-btn" type="button" class="support-chip-btn inline-flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-bold">
                                        <i class="fas fa-check" aria-hidden="true"></i>
                                        Clôturer
                                    </button>
                                    <button id="support-reopen-btn" type="button" class="support-chip-btn inline-flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-bold">
                                        <i class="fas fa-undo" aria-hidden="true"></i>
                                        Rouvrir
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-4 p-5 lg:grid-cols-[minmax(0,1fr)_18rem]">
                            <div class="min-w-0">
                                <div id="support-empty" class="support-empty-card rounded-2xl border border-dashed border-gray-200 bg-gray-50 p-6 text-center dark:border-gray-700 dark:bg-gray-900/40">
                                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-100 text-primary-600 dark:bg-primary-900/30 dark:text-primary-300">
                                        <i class="fas fa-comments"></i>
                                    </span>
                                    <p class="mt-3 text-sm font-bold text-gray-900 dark:text-white">Aucun ticket sélectionné</p>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Choisissez une conversation dans la file.</p>
                                    <button id="support-empty-cta" type="button" class="support-empty-action mt-4 inline-flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-bold">
                                        <i class="fas fa-list" aria-hidden="true"></i>
                                        Voir la file
                                    </button>
                                </div>

                                <div id="support-messages" class="support-thread-messages hidden max-h-[58vh] min-h-[26rem] overflow-y-auto rounded-2xl border border-gray-100 bg-gray-50 p-4 space-y-3 dark:border-gray-700 dark:bg-gray-900/40"></div>
                            </div>

                            <aside class="rounded-2xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        <i class="fas fa-info-circle mr-1"></i>Détails
                                    </p>
                                    <span id="support-detail-badge" class="support-status-pill">Sans statut</span>
                                </div>
                                <h4 id="support-detail-title" class="mt-4 text-base font-bold text-gray-900 dark:text-white">Aucune conversation active</h4>
                                <p id="support-detail-preview" class="mt-2 text-sm text-gray-500 dark:text-gray-400">Le résumé du ticket apparaît ici.</p>
                                <div class="mt-5 space-y-3 text-sm">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-gray-500 dark:text-gray-400"><i class="fas fa-user mr-2"></i>Utilisateur</span>
                                        <span id="support-detail-owner" class="min-w-0 truncate font-semibold text-gray-900 dark:text-white">Support</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-gray-500 dark:text-gray-400"><i class="fas fa-clock mr-2"></i>Activité</span>
                                        <span id="support-detail-updated" class="font-semibold text-gray-900 dark:text-white">Aucune</span>
                                    </div>
                                </div>
                            </aside>
                        </div>

                        <div class="mt-auto border-t border-gray-100 p-5 dark:border-gray-700">
                            <div id="support-status" class="support-status text-sm"></div>
                            <form id="support-message-form" class="mt-3 hidden space-y-3" method="post" action="<?php echo e(site_url('api/support_messages')); ?>" enctype="multipart/form-data">
                                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                <input type="hidden" name="conversation_id" id="support-conv-id" value="">

                                <label for="support-reply" class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-reply mr-1"></i>Réponse
                                </label>
                                <textarea id="support-reply" name="body" rows="3" placeholder="Répondez avec des informations précises..." class="support-input w-full resize-none rounded-xl border bg-gray-50 px-4 py-3 text-sm dark:bg-gray-900/60"></textarea>

                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <label class="support-file-btn inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl px-3 py-2 text-xs font-bold">
                                        <i class="fas fa-paperclip" aria-hidden="true"></i>
                                        Joindre un fichier
                                        <input id="support-reply-files" name="attachments[]" type="file" class="hidden" multiple>
                                    </label>
                                    <p id="support-files-hint" class="text-xs text-gray-500 dark:text-gray-400">Pièces jointes : max. 5 Mo.</p>
                                    <button type="submit" class="support-send-btn inline-flex items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-bold">
                                        <i class="fas fa-paper-plane" aria-hidden="true"></i>
                                        Envoyer
                                    </button>
                                </div>
                            </form>
                        </div>
                    </section>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
