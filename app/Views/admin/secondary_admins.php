<?php

$errors = is_array($errors ?? null) ? $errors : [];
$successMessage = (string) ($successMessage ?? '');
$permissionsCatalog = is_array($permissionsCatalog ?? null) ? $permissionsCatalog : [];
$secondaryAdmins = is_array($secondaryAdmins ?? null) ? $secondaryAdmins : [];
$pagination = is_array($pagination ?? null) ? $pagination : [];

$adminDashboardUrl = (string) ($adminDashboardUrl ?? site_url('admin/dashboard'));
$adminSupportAgentsUrl = (string) ($adminSupportAgentsUrl ?? site_url('admin/support_agents'));
$secondaryLoginUrl = (string) site_url('admin/secondary_login');
$secondaryLoginDisplay = preg_replace('#^https?://#', '', $secondaryLoginUrl) ?: $secondaryLoginUrl;

$secondaryTotal = max(0, (int) ($pagination['total'] ?? count($secondaryAdmins)));
$paginationPage = max(1, (int) ($pagination['page'] ?? 1));
$paginationTotalPages = max(1, (int) ($pagination['total_pages'] ?? 1));
$secondaryCountLabel = $secondaryTotal === 1 ? 'compte' : 'comptes';
$permissionsTotal = count($permissionsCatalog);

$activeSecondaryCount = 0;
foreach ($secondaryAdmins as $adminRow) {
    if (!empty($adminRow['is_active'])) {
        $activeSecondaryCount++;
    }
}
$inactiveSecondaryCount = max(0, count($secondaryAdmins) - $activeSecondaryCount);

$adminNavActive = 'secondary_admins';
$adminTopTitle = 'Administrateurs secondaires';
$adminTopSubtitle = 'Gestion des droits';
$adminTopBadge = (string) $secondaryTotal . ' ' . $secondaryCountLabel;
$adminTopBadgeTone = $secondaryTotal > 0 ? 'active' : 'pending';
$adminTopActions = [
    ['label' => 'Tableau de bord', 'url' => $adminDashboardUrl, 'tone' => 'neutral'],
    ['label' => 'Équipe support', 'url' => $adminSupportAgentsUrl, 'tone' => 'neutral'],
];

$icon = static function (string $name): string {
    return function_exists('dashboard_icon_svg') ? (string) dashboard_icon_svg($name) : '';
};

$statusLabel = static function (string $status): string {
    $normalized = strtolower(trim($status));
    $map = [
        'active' => 'Actif',
        'inactive' => 'Inactif',
        'pending' => 'En attente',
        'suspended' => 'Suspendu',
    ];

    return $map[$normalized] ?? ($status !== '' ? ucfirst($status) : 'Actif');
};

$statusIcon = static function (string $status): string {
    $normalized = strtolower(trim($status));
    $map = [
        'active' => 'check-circle',
        'inactive' => 'alert-circle',
        'pending' => 'clock',
        'suspended' => 'lock',
    ];

    return $map[$normalized] ?? 'check-circle';
};

$permissionIconMap = [
    'dashboard' => 'layout',
    'support' => 'lifebuoy',
    'support_agents' => 'users',
    'courses' => 'book-open',
    'teacher_applications' => 'chalkboard-user',
    'sales' => 'receipt',
    'categories' => 'folder',
    'announcements' => 'megaphone',
    'site_settings' => 'settings',
];

$permissionIcon = static function (string $permissionKey) use ($permissionIconMap, $icon): string {
    return $icon($permissionIconMap[$permissionKey] ?? 'check-circle');
};

$deriveRole = static function (array $permissions, int $availablePermissions): array {
    $permissions = array_values(array_unique(array_map('strval', $permissions)));
    $permissionCount = count($permissions);
    $has = static fn(string $permission): bool => in_array($permission, $permissions, true);

    if (
        $permissionCount >= max(1, $availablePermissions - 1)
        || $has('dashboard')
        || $has('site_settings')
        || $has('sales')
    ) {
        return ['key' => 'superviseur', 'label' => 'Superviseur', 'icon' => 'fa-crown'];
    }

    if ($has('courses') || $has('categories') || $has('teacher_applications') || $has('announcements')) {
        return ['key' => 'moderateur', 'label' => 'Modérateur', 'icon' => 'fa-user-shield'];
    }

    return ['key' => 'support', 'label' => 'Support', 'icon' => 'fa-headset'];
};

$formatDate = static function (string $date): string {
    $date = trim($date);
    if ($date === '') {
        return 'Date non disponible';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }

    return date('d/m/Y H:i', $timestamp);
};

$buildPageUrl = static function (int $targetPage): string {
    $page = max(1, $targetPage);

    return site_url('admin/secondary_admins' . ($page > 1 ? ('?page=' . $page) : ''));
};

$modalToAutoOpen = '';
if ($errors !== [] && strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
    $postedAction = (string) ($_POST['action'] ?? '');
    if ($postedAction === 'create') {
        $modalToAutoOpen = 'secondary-create-modal';
    } elseif ($postedAction === 'grant') {
        $modalToAutoOpen = 'secondary-grant-modal';
    }
}

$adminPageScripts = ['admin_secondary_admins.js'];
?>

<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrateurs secondaires - Administration</title>
    <?php require __DIR__ . '/_head_assets.php'; ?>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="app-view app-role-admin min-h-screen bg-slate-50 text-slate-900 font-body dark:bg-slate-950 dark:text-slate-100" data-view="admin/secondary_admins" data-sidebar-desktop-toggle="1" data-secondary-open-modal="<?php echo e($modalToAutoOpen); ?>">
    <div class="admin-shell-backdrop">
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--one"></div>
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--two"></div>
    </div>

    <div class="admin-shell-root relative z-10 flex min-h-screen">
        <?php require __DIR__ . '/_sidebar.php'; ?>
        <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

        <div class="admin-shell-main flex min-w-0 flex-1 flex-col">
            <?php require __DIR__ . '/_topbar.php'; ?>

            <main class="admin-shell-content admin-shell-dense px-4 py-5 sm:px-6 sm:py-6">
                <div class="space-y-8 animate-fade-up">
                    <?php if ($errors): ?>
                        <div class="admin-shell-alert admin-shell-alert--error">
                            <?php foreach ($errors as $err): ?>
                                <p class="inline-flex items-center gap-2">
                                    <span class="app-icon" aria-hidden="true"><?php echo $icon('alert-triangle'); ?></span>
                                    <?php echo e((string) $err); ?>
                                </p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($successMessage !== ''): ?>
                        <div class="admin-shell-alert admin-shell-alert--success">
                            <p class="inline-flex items-center gap-2">
                                <i class="fas fa-check-circle" aria-hidden="true"></i>
                                <?php echo e($successMessage); ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <section class="bg-gradient-to-r from-primary-600 to-accent-600 rounded-2xl p-6 text-white shadow-glow">
                        <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                            <div class="max-w-3xl">
                                <p class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-bold uppercase tracking-wide text-primary-100">
                                    <i class="fas fa-shield-alt" aria-hidden="true"></i>
                                    Gouvernance
                                </p>
                                <h2 class="mt-3 text-2xl font-bold sm:text-3xl">Administrateurs secondaires</h2>
                                <p class="mt-2 text-primary-100">Rôles lisibles, actions rapides et permissions visibles.</p>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2 xl:min-w-[24rem]">
                                <button type="button" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-4 py-3 text-sm font-bold text-primary-700 shadow-card transition hover:bg-primary-50 js-modal-open" data-modal-target="secondary-create-modal">
                                    <i class="fas fa-plus" aria-hidden="true"></i>
                                    Nouvel administrateur
                                </button>
                                <button type="button" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white/15 px-4 py-3 text-sm font-bold text-white ring-1 ring-white/25 transition hover:bg-white/25 js-modal-open" data-modal-target="secondary-grant-modal">
                                    <i class="fas fa-key" aria-hidden="true"></i>
                                    Attribuer un compte
                                </button>
                            </div>
                        </div>
                    </section>

                    <section class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                        <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
                            <div class="flex items-center justify-between gap-4">
                                <div><p class="text-sm text-gray-500 dark:text-gray-400">Total</p><p class="text-2xl font-bold text-gray-900 dark:text-white mt-1"><?php echo e((string) $secondaryTotal); ?></p></div>
                                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center text-primary-600"><i class="fas fa-user-shield"></i></div>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2"><?php echo e($secondaryCountLabel); ?> administrateur<?php echo $secondaryTotal > 1 ? 's' : ''; ?></p>
                        </article>

                        <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
                            <div class="flex items-center justify-between gap-4">
                                <div><p class="text-sm text-gray-500 dark:text-gray-400">Actifs</p><p class="text-2xl font-bold text-gray-900 dark:text-white mt-1"><?php echo e((string) $activeSecondaryCount); ?></p></div>
                                <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center text-green-600"><i class="fas fa-check-circle"></i></div>
                            </div>
                            <p class="text-xs text-green-600 dark:text-green-400 mt-2 flex items-center"><i class="fas fa-lock-open mr-1"></i>Accès autorisés</p>
                        </article>

                        <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
                            <div class="flex items-center justify-between gap-4">
                                <div><p class="text-sm text-gray-500 dark:text-gray-400">Suspendus</p><p class="text-2xl font-bold text-gray-900 dark:text-white mt-1"><?php echo e((string) $inactiveSecondaryCount); ?></p></div>
                                <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center text-yellow-600"><i class="fas fa-ban"></i></div>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Compte conservé, accès bloqué</p>
                        </article>

                        <article class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift">
                            <div class="flex items-center justify-between gap-4">
                                <div><p class="text-sm text-gray-500 dark:text-gray-400">Permissions</p><p class="text-2xl font-bold text-gray-900 dark:text-white mt-1"><?php echo e((string) $permissionsTotal); ?></p></div>
                                <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center text-purple-600"><i class="fas fa-sliders-h"></i></div>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Fonctionnalités délégables</p>
                        </article>
                    </section>

                    <section class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-card border border-gray-100 dark:border-gray-700">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Filtres</p>
                                <h3 class="font-bold text-gray-900 dark:text-white">Liste des administrateurs</h3>
                            </div>
                            <div class="grid gap-3 md:grid-cols-[minmax(16rem,1fr)_13rem] xl:w-[34rem]">
                                <label class="relative block" for="secondary-admin-search">
                                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm" aria-hidden="true"></i>
                                    <input id="secondary-admin-search" type="search" placeholder="Rechercher par nom ou e-mail..." autocomplete="off" class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pl-9 pr-4 text-sm font-medium outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-700 dark:bg-gray-700 dark:text-white">
                                </label>
                                <select id="secondary-role-filter" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-semibold text-gray-700 outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-100" aria-label="Filtrer par rôle">
                                    <option value="all">Tous les rôles</option>
                                    <option value="superviseur">Superviseur</option>
                                    <option value="moderateur">Modérateur</option>
                                    <option value="support">Support</option>
                                </select>
                            </div>
                        </div>
                    </section>

                    <section id="secondary-admins-container" class="grid grid-cols-1 gap-5 lg:grid-cols-2 2xl:grid-cols-3" aria-label="Liste des administrateurs secondaires">
                        <?php foreach ($secondaryAdmins as $secondaryAdmin): ?>
                            <?php
                            $fullName = trim((string) ($secondaryAdmin['first_name'] ?? '') . ' ' . (string) ($secondaryAdmin['last_name'] ?? ''));
                            $displayName = $fullName !== '' ? $fullName : 'Administrateur secondaire';
                            $email = (string) ($secondaryAdmin['email'] ?? '');
                            $selectedPermissions = is_array($secondaryAdmin['permissions'] ?? null) ? $secondaryAdmin['permissions'] : [];
                            $permissionLabels = is_array($secondaryAdmin['permission_labels'] ?? null) ? $secondaryAdmin['permission_labels'] : [];
                            $permissionCount = count($selectedPermissions);
                            $role = $deriveRole($selectedPermissions, $permissionsTotal);
                            $isActive = !empty($secondaryAdmin['is_active']);
                            $visiblePermissionLabels = array_slice($permissionLabels, 0, 4);
                            $hiddenPermissionCount = max(0, count($permissionLabels) - count($visiblePermissionLabels));
                            $avatarLetters = strtoupper(substr((string) ($secondaryAdmin['first_name'] ?? ''), 0, 1) . substr((string) ($secondaryAdmin['last_name'] ?? ''), 0, 1));
                            if ($avatarLetters === '') {
                                $avatarLetters = strtoupper(substr($email !== '' ? $email : 'AD', 0, 2));
                            }
                            $cardSearch = strtolower(trim($displayName . ' ' . $email));
                            $detailsId = 'secondary-details-' . (string) ($secondaryAdmin['id'] ?? 0);
                            ?>
                            <article class="js-secondary-admin-card bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-card border border-gray-100 dark:border-gray-700 hover-lift" data-admin-name="<?php echo e($cardSearch); ?>" data-admin-email="<?php echo e(strtolower($email)); ?>" data-admin-role="<?php echo e((string) $role['key']); ?>">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-primary-600 to-accent-500 text-sm font-bold text-white shadow-md"><?php echo e($avatarLetters); ?></span>
                                        <div class="min-w-0">
                                            <h3 class="truncate font-bold text-gray-900 dark:text-white"><?php echo e($displayName); ?></h3>
                                            <p class="truncate text-sm text-gray-500 dark:text-gray-400"><?php echo e($email); ?></p>
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 gap-1">
                                        <button type="button" class="js-secondary-toggle rounded-lg p-2 text-gray-500 transition hover:bg-primary-50 hover:text-primary-600 dark:text-gray-400 dark:hover:bg-primary-900/20" aria-expanded="false" aria-controls="<?php echo e($detailsId); ?>" data-toggle-target="<?php echo e($detailsId); ?>" data-label-open="Masquer" data-label-closed="Modifier" title="Modifier les permissions">
                                            <i class="fas fa-edit js-secondary-toggle-icon" aria-hidden="true"></i>
                                            <span class="sr-only js-secondary-toggle-label">Modifier</span>
                                        </button>
                                        <button type="button" class="js-secondary-revoke rounded-lg p-2 text-gray-500 transition hover:bg-red-50 hover:text-red-600 dark:text-gray-400 dark:hover:bg-red-900/20" data-user-id="<?php echo e((string) ($secondaryAdmin['id'] ?? 0)); ?>" data-user-name="<?php echo e($displayName !== '' ? $displayName : $email); ?>" title="Retirer le rôle">
                                            <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-4 flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center gap-1.5 rounded-full <?php echo $role['key'] === 'superviseur' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300' : ($role['key'] === 'moderateur' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'); ?> px-2.5 py-1 text-xs font-semibold">
                                        <i class="fas <?php echo e((string) $role['icon']); ?>" aria-hidden="true"></i>
                                        <?php echo e((string) $role['label']); ?>
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold <?php echo $isActive ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>">
                                        <i class="fas <?php echo $isActive ? 'fa-circle text-[6px]' : 'fa-ban'; ?>" aria-hidden="true"></i>
                                        <?php echo $isActive ? 'Actif' : 'Suspendu'; ?>
                                    </span>
                                    <form method="post" action="<?php echo e(site_url('admin/secondary_admins')); ?>" class="ml-auto">
                                        <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                        <input type="hidden" name="action" value="toggle_permissions_active">
                                        <input type="hidden" name="page" value="<?php echo e((string) $paginationPage); ?>">
                                        <input type="hidden" name="user_id" value="<?php echo e((string) ($secondaryAdmin['id'] ?? 0)); ?>">
                                        <input type="hidden" name="set_active" value="<?php echo $isActive ? '0' : '1'; ?>">
                                        <button type="submit" class="text-xs font-bold text-yellow-600 transition hover:underline dark:text-yellow-400"><?php echo $isActive ? 'Suspendre' : 'Réactiver'; ?></button>
                                    </form>
                                </div>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    <?php if ($visiblePermissionLabels !== []): ?>
                                        <?php foreach ($visiblePermissionLabels as $permissionLabel): ?>
                                            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300"><i class="fas fa-check text-green-500" aria-hidden="true"></i><?php echo e((string) $permissionLabel); ?></span>
                                        <?php endforeach; ?>
                                        <?php if ($hiddenPermissionCount > 0): ?>
                                            <span class="inline-flex items-center rounded-full bg-primary-50 px-2 py-1 text-xs font-bold text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">+<?php echo e((string) $hiddenPermissionCount); ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 rounded-full bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300"><i class="fas fa-exclamation-circle" aria-hidden="true"></i>Aucune permission active</span>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-4 border-t border-gray-100 pt-3 text-xs text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                    <div class="flex flex-wrap gap-3">
                                        <span><i class="far fa-clock mr-1" aria-hidden="true"></i>Créé le <?php echo e($formatDate((string) ($secondaryAdmin['created_at'] ?? ''))); ?></span>
                                        <span><i class="fas fa-sliders-h mr-1" aria-hidden="true"></i><?php echo e((string) $permissionCount); ?> permission<?php echo $permissionCount > 1 ? 's' : ''; ?></span>
                                    </div>
                                    <?php if (!empty($secondaryAdmin['created_by_name'])): ?>
                                        <p class="mt-2"><i class="fas fa-user-check mr-1" aria-hidden="true"></i>Créé par <?php echo e((string) $secondaryAdmin['created_by_name']); ?></p>
                                    <?php endif; ?>
                                </div>

                                <div id="<?php echo e($detailsId); ?>" class="hidden mt-4 rounded-2xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-700/30">
                                    <form method="post" action="<?php echo e(site_url('admin/secondary_admins')); ?>" class="space-y-3">
                                        <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                        <input type="hidden" name="action" value="update_permissions">
                                        <input type="hidden" name="page" value="<?php echo e((string) $paginationPage); ?>">
                                        <input type="hidden" name="user_id" value="<?php echo e((string) ($secondaryAdmin['id'] ?? 0)); ?>">
                                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                            <?php foreach ($permissionsCatalog as $permissionKey => $permissionLabel): ?>
                                                <label class="admin-secondary-permission-option flex items-center gap-2">
                                                    <input type="checkbox" name="permissions[]" value="<?php echo e((string) $permissionKey); ?>" <?php echo in_array((string) $permissionKey, $selectedPermissions, true) ? 'checked' : ''; ?> class="app-checkbox rounded border-slate-300">
                                                    <span class="app-icon" aria-hidden="true"><?php echo $permissionIcon((string) $permissionKey); ?></span>
                                                    <span><?php echo e((string) $permissionLabel); ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="flex justify-end">
                                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-primary-700">
                                                <i class="fas fa-check" aria-hidden="true"></i>
                                                Enregistrer
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </section>

                    <div id="secondary-admin-empty-state" class="<?php echo $secondaryTotal === 0 ? '' : 'hidden'; ?> rounded-2xl border border-gray-100 bg-white p-10 text-center shadow-card dark:border-gray-700 dark:bg-gray-800">
                        <i class="fas fa-user-shield mb-3 text-5xl text-gray-300 dark:text-gray-600" aria-hidden="true"></i>
                        <p class="font-medium text-gray-500 dark:text-gray-400">Aucun administrateur trouvé</p>
                    </div>

                    <?php if ($paginationTotalPages > 1): ?>
                        <nav class="flex flex-wrap items-center gap-2" aria-label="Pagination des administrateurs secondaires">
                            <a href="<?php echo e($buildPageUrl(max(1, $paginationPage - 1))); ?>" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-bold text-gray-600 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 <?php echo $paginationPage <= 1 ? 'opacity-50 pointer-events-none' : ''; ?>">
                                <i class="fas fa-chevron-left" aria-hidden="true"></i>
                                Précédent
                            </a>

                            <?php
                            $startPage = max(1, $paginationPage - 2);
                            $endPage = min($paginationTotalPages, $paginationPage + 2);
                            for ($pageNumber = $startPage; $pageNumber <= $endPage; $pageNumber++):
                                $isCurrentPage = $pageNumber === $paginationPage;
                                ?>
                                <a href="<?php echo e($buildPageUrl($pageNumber)); ?>" class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border px-3 text-sm font-bold transition <?php echo $isCurrentPage ? 'border-primary-600 bg-primary-600 text-white' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300'; ?>"><?php echo e((string) $pageNumber); ?></a>
                            <?php endfor; ?>

                            <a href="<?php echo e($buildPageUrl(min($paginationTotalPages, $paginationPage + 1))); ?>" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-bold text-gray-600 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 <?php echo $paginationPage >= $paginationTotalPages ? 'opacity-50 pointer-events-none' : ''; ?>">
                                Suivant
                                <i class="fas fa-chevron-right" aria-hidden="true"></i>
                            </a>
                        </nav>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <div id="secondary-create-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4 js-admin-modal" role="dialog" aria-modal="true" aria-labelledby="secondary-create-modal-title">
        <div class="admin-shell-modal admin-secondary-modal-panel admin-secondary-template-modal w-full max-w-2xl rounded-2xl">
            <div class="admin-secondary-template-modal__head">
                <h3 id="secondary-create-modal-title">
                    <i class="fas fa-user-plus text-primary-500" aria-hidden="true"></i>
                    Nouvel administrateur
                </h3>
                <button type="button" class="admin-secondary-template-modal__close js-modal-close" data-modal-target="secondary-create-modal">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>

            <form method="post" action="<?php echo e(site_url('admin/secondary_admins')); ?>">
                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                <input type="hidden" name="action" value="create">

                <div class="admin-secondary-template-modal__body">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="admin-shell-field">
                            <label class="admin-shell-label" for="secondary-create-first">Prénom</label>
                            <input id="secondary-create-first" name="first_name" type="text" autocomplete="given-name" required class="admin-shell-input" placeholder="Jean">
                        </div>
                        <div class="admin-shell-field">
                            <label class="admin-shell-label" for="secondary-create-last">Nom</label>
                            <input id="secondary-create-last" name="last_name" type="text" autocomplete="family-name" required class="admin-shell-input" placeholder="Dupont">
                        </div>
                    </div>

                    <div class="admin-shell-field">
                        <label class="admin-shell-label" for="secondary-create-email">Adresse e-mail</label>
                        <input id="secondary-create-email" name="email" type="email" autocomplete="email" required class="admin-shell-input" placeholder="jean@ecole.fr">
                    </div>

                    <div class="admin-shell-field">
                        <label class="admin-shell-label" for="secondary-create-password">Mot de passe temporaire</label>
                        <input id="secondary-create-password" name="password" type="password" minlength="8" required class="admin-shell-input">
                        <p class="admin-secondary-template-help">8 caractères minimum, avec une majuscule, un chiffre et un caractère spécial.</p>
                    </div>

                    <div class="admin-shell-field">
                        <p class="admin-shell-label">Fonctionnalités autorisées</p>
                        <div class="admin-secondary-template-permission-grid">
                            <?php foreach ($permissionsCatalog as $permissionKey => $permissionLabel): ?>
                                <label class="admin-secondary-permission-option">
                                    <input type="checkbox" name="permissions[]" value="<?php echo e((string) $permissionKey); ?>" class="app-checkbox rounded border-slate-300">
                                    <span class="app-icon" aria-hidden="true"><?php echo $permissionIcon((string) $permissionKey); ?></span>
                                    <span><?php echo e((string) $permissionLabel); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="admin-secondary-template-modal__foot">
                    <button type="button" class="admin-secondary-template-modal__secondary js-modal-close" data-modal-target="secondary-create-modal">Annuler</button>
                    <button type="submit" class="admin-secondary-template-modal__primary">
                        <i class="fas fa-check" aria-hidden="true"></i>
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="secondary-grant-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4 js-admin-modal" role="dialog" aria-modal="true" aria-labelledby="secondary-grant-modal-title">
        <div class="admin-shell-modal admin-secondary-modal-panel admin-secondary-template-modal w-full max-w-2xl rounded-2xl">
            <div class="admin-secondary-template-modal__head">
                <h3 id="secondary-grant-modal-title">
                    <i class="fas fa-user-shield text-primary-500" aria-hidden="true"></i>
                    Attribuer le rôle à un compte existant
                </h3>
                <button type="button" class="admin-secondary-template-modal__close js-modal-close" data-modal-target="secondary-grant-modal">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>

            <form method="post" action="<?php echo e(site_url('admin/secondary_admins')); ?>">
                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                <input type="hidden" name="action" value="grant">

                <div class="admin-secondary-template-modal__body">
                    <div class="admin-shell-field">
                        <label class="admin-shell-label" for="secondary-grant-email">Adresse e-mail de l'utilisateur</label>
                        <input id="secondary-grant-email" name="email" type="email" autocomplete="email" required class="admin-shell-input" placeholder="utilisateur@ecole.fr">
                    </div>

                    <div class="admin-shell-field">
                        <p class="admin-shell-label">Fonctionnalités autorisées</p>
                        <div class="admin-secondary-template-permission-grid">
                            <?php foreach ($permissionsCatalog as $permissionKey => $permissionLabel): ?>
                                <label class="admin-secondary-permission-option">
                                    <input type="checkbox" name="permissions[]" value="<?php echo e((string) $permissionKey); ?>" class="app-checkbox rounded border-slate-300">
                                    <span class="app-icon" aria-hidden="true"><?php echo $permissionIcon((string) $permissionKey); ?></span>
                                    <span><?php echo e((string) $permissionLabel); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="admin-secondary-template-modal__foot">
                    <button type="button" class="admin-secondary-template-modal__secondary js-modal-close" data-modal-target="secondary-grant-modal">Annuler</button>
                    <button type="submit" class="admin-secondary-template-modal__primary">
                        <i class="fas fa-key" aria-hidden="true"></i>
                        Attribuer le rôle
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="secondary-revoke-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4 js-admin-modal" role="dialog" aria-modal="true" aria-labelledby="secondary-revoke-modal-title">
        <div class="admin-shell-modal admin-secondary-modal-panel admin-secondary-template-modal admin-secondary-template-modal--compact w-full max-w-md rounded-2xl">
            <div class="admin-secondary-template-modal__body">
                <h3 id="secondary-revoke-modal-title" class="admin-secondary-template-danger-title">
                    <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                    Confirmer la suppression des accès
                </h3>
                <p id="secondary-revoke-text" class="admin-secondary-template-danger-text">L'utilisateur perdra immédiatement ses accès d'administration secondaire.</p>
            </div>

            <div class="admin-secondary-template-modal__foot">
                <button type="button" class="admin-secondary-template-modal__secondary js-modal-close" data-modal-target="secondary-revoke-modal">Annuler</button>
                <form id="secondary-revoke-form" method="post" action="<?php echo e(site_url('admin/secondary_admins')); ?>">
                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                    <input type="hidden" name="action" value="revoke">
                    <input type="hidden" name="page" value="<?php echo e((string) $paginationPage); ?>">
                    <input type="hidden" name="user_id" id="secondary-revoke-user-id" value="">
                    <button type="submit" class="admin-secondary-template-modal__danger">
                        <i class="fas fa-trash-alt" aria-hidden="true"></i>
                        Supprimer l'accès
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
