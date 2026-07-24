<?php

$errors = is_array($errors ?? null) ? $errors : [];
$successMessage = (string) ($successMessage ?? '');
$permissionsCatalog = is_array($permissionsCatalog ?? null) ? $permissionsCatalog : [];
$formMeta = is_array($formMeta ?? null) ? $formMeta : [];
$formState = is_array($formState ?? null) ? $formState : [];
$listUrl = (string) ($listUrl ?? site_url('admin/secondary_admins'));
$adminDashboardUrl = (string) ($adminDashboardUrl ?? site_url('admin/dashboard'));
$adminSupportAgentsUrl = (string) ($adminSupportAgentsUrl ?? site_url('admin/support_agents'));
$permissionsTotal = (int) ($permissionsTotal ?? count($permissionsCatalog));
$paginationPage = max(1, (int) ($_GET['page'] ?? ($_POST['page'] ?? 1)));

$mode = in_array((string) ($pageMode ?? ''), ['create', 'grant', 'edit'], true) ? (string) $pageMode : 'create';
$formValues = is_array($formState['formValues'] ?? null) ? $formState['formValues'] : [];
$selectedPermissions = is_array($formState['selectedPermissions'] ?? null) ? $formState['selectedPermissions'] : [];
$targetUser = is_array($formState['targetUser'] ?? null) ? $formState['targetUser'] : null;
$formNotice = trim((string) ($formState['formNotice'] ?? ''));

$title = (string) ($formMeta['title'] ?? 'Administrateurs secondaires');
$subtitle = (string) ($formMeta['subtitle'] ?? 'Gestion des droits');
$badge = (string) ($formMeta['badge'] ?? 'Formulaire');
$badgeTone = (string) ($formMeta['badgeTone'] ?? 'active');
$heroTitle = (string) ($formMeta['heroTitle'] ?? $title);
$heroLead = (string) ($formMeta['heroLead'] ?? '');
$submitLabel = (string) ($formMeta['submitLabel'] ?? 'Enregistrer');
$submitIcon = (string) ($formMeta['submitIcon'] ?? 'check');
$formActionUrl = (string) ($formMeta['formActionUrl'] ?? $listUrl);
$showIdentityFields = !empty($formMeta['showIdentityFields']);
$showPasswordField = !empty($formMeta['showPasswordField']);
$showEmailField = !empty($formMeta['showEmailField']);
$showTargetSummary = !empty($formMeta['showTargetSummary']);
$notice = trim((string) ($formMeta['notice'] ?? ''));

$adminNavActive = 'secondary_admins';
$adminTopTitle = 'Administrateurs secondaires';
$adminTopSubtitle = $subtitle;
$adminTopBadge = $badge;
$adminTopBadgeTone = $badgeTone;
$adminTopSearchAction = $listUrl;
$adminTopSearchValue = '';
$adminTopSearchPlaceholder = 'Rechercher un administrateur...';
$adminTopActions = [
    ['label' => 'Retour à la liste', 'url' => $listUrl, 'tone' => 'neutral'],
    ['label' => 'Équipe support', 'url' => $adminSupportAgentsUrl, 'tone' => 'neutral'],
    ['label' => 'Tableau de bord', 'url' => $adminDashboardUrl, 'tone' => 'neutral'],
];

$icon = static function (string $name): string {
    return function_exists('dashboard_icon_svg') ? (string) dashboard_icon_svg($name) : '';
};

$permissionIconMap = [
    'dashboard' => 'layout',
    'support' => 'lifebuoy',
    'support_agents' => 'users',
    'courses' => 'book-open',
    'teacher_applications' => 'chalkboard-user',
    'sales' => 'receipt',
    'categories' => 'folder',
    'site_settings' => 'settings',
    'error_logs' => 'bug',
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

    if ($has('courses') || $has('categories') || $has('teacher_applications')) {
        return ['key' => 'moderateur', 'label' => 'Modérateur', 'icon' => 'fa-user-shield'];
    }

    return ['key' => 'support', 'label' => 'Support', 'icon' => 'fa-headset'];
};

$selectedPermissionLabels = [];
foreach ($selectedPermissions as $permissionKey) {
    if (isset($permissionsCatalog[$permissionKey])) {
        $selectedPermissionLabels[] = (string) $permissionsCatalog[$permissionKey];
    }
}
$selectedRole = $deriveRole($selectedPermissions, max(1, $permissionsTotal));

$buildPermissionChip = static function (string $permissionLabel, string $tone = 'gray'): string {
    $base = 'inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-medium';
    $toneClass = match ($tone) {
        'green' => ' bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300',
        'blue' => ' bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
        'amber' => ' bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
        default => ' bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
    };

    return $base . $toneClass;
};

$formAction = (string) ($formMeta['formActionUrl'] ?? $listUrl);
$csrf = view_csrf_token();
$userIdValue = (string) ($formValues['user_id'] ?? ($targetUser['id'] ?? ''));
$selectedPermissionCount = count($selectedPermissions);
$targetPermissionLabels = is_array($targetUser['permission_labels'] ?? null) ? $targetUser['permission_labels'] : [];
$targetDisplayName = (string) ($targetUser['display_name'] ?? 'Administrateur secondaire');
$targetEmail = (string) ($targetUser['email'] ?? '');
$targetRole = is_array($targetUser['role'] ?? null) ? $targetUser['role'] : $selectedRole;
$targetStatus = (string) ($targetUser['status_label'] ?? '');
$targetIsActive = !empty($targetUser['is_active'] ?? false);
$targetCreatedAt = trim((string) ($targetUser['created_at'] ?? ''));

if ($targetStatus === '' && $showTargetSummary && $targetUser !== []) {
    $targetStatus = $targetIsActive ? 'Actif' : 'Suspendu';
}

$currentModeLabel = match ($mode) {
    'grant' => 'Attribution',
    'edit' => 'Modification',
    default => 'Création',
};

$adminPageScripts = [];
?>

<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($title); ?> - Administration</title>
    <?php require __DIR__ . '/_head_assets.php'; ?>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="app-view app-role-admin min-h-screen bg-slate-50 text-slate-900 font-body dark:bg-slate-950 dark:text-slate-100" data-view="admin/secondary_admins_form" data-sidebar-desktop-toggle="1">
    <div class="admin-shell-backdrop" aria-hidden="true">
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--one"></div>
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--two"></div>
    </div>

    <div class="admin-shell-root relative z-10 flex min-h-screen">
        <?php require __DIR__ . '/_sidebar.php'; ?>
        <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

        <div class="admin-shell-main flex min-w-0 flex-1 flex-col">
            <?php require __DIR__ . '/_topbar.php'; ?>

            <main class="admin-shell-content admin-shell-dense px-4 py-5 sm:px-6 sm:py-6">
                <div class="space-y-6 animate-fade-up">
                    <?php if ($errors !== []): ?>
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

                    <section class="bg-gradient-to-r from-primary-600 to-accent-600 rounded-2xl p-6 text-white shadow-glow overflow-hidden relative">
                        <div class="absolute -right-10 -top-12 h-40 w-40 rounded-full bg-white/10"></div>
                        <div class="absolute bottom-0 right-28 h-20 w-20 rounded-full bg-white/10 blur-sm"></div>
                        <div class="relative flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                            <div class="max-w-3xl">
                                <p class="text-xs font-bold uppercase tracking-[0.22em] text-primary-100">
                                    <i class="fas fa-user-shield mr-2"></i><?php echo e($currentModeLabel); ?>
                                </p>
                                <h2 class="mt-2 text-2xl font-bold sm:text-3xl"><?php echo e($heroTitle); ?></h2>
                                <p class="mt-2 text-sm text-primary-100 sm:text-base"><?php echo e($heroLead); ?></p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a href="<?php echo e($listUrl); ?>" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-3 text-sm font-bold text-primary-700 shadow-sm transition hover:bg-primary-50">
                                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                    Retour à la liste
                                </a>
                                <a href="<?php echo e($adminSupportAgentsUrl); ?>" class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-4 py-3 text-sm font-bold text-white ring-1 ring-white/25 transition hover:bg-white/25">
                                    <i class="fas fa-headset" aria-hidden="true"></i>
                                    Équipe support
                                </a>
                                <a href="<?php echo e(site_url('admin/secondary_admins/activity')); ?>" class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-4 py-3 text-sm font-bold text-white ring-1 ring-white/25 transition hover:bg-white/25">
                                    <i class="fas fa-history" aria-hidden="true"></i>
                                    Journal d'activité
                                </a>
                            </div>
                        </div>
                    </section>

                    <section class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(18rem,0.85fr)]">
                        <article class="admin-shell-card rounded-3xl p-5 sm:p-6">
                            <?php if ($showTargetSummary && $targetUser === null && $formNotice !== ''): ?>
                                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
                                    <p class="font-bold"><?php echo e($formNotice); ?></p>
                                    <p class="mt-1">Revenez à la liste pour ouvrir un administrateur secondaire existant.</p>
                                </div>
                                <div class="mt-5 flex flex-wrap gap-2">
                                    <a href="<?php echo e($listUrl); ?>" class="admin-shell-button admin-shell-button--secondary inline-flex items-center gap-2">
                                        <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                        Retour à la liste
                                    </a>
                                    <a href="<?php echo e($mode === 'grant' ? site_url('admin/secondary_admins/grant' . ($paginationPage > 1 ? ('?page=' . $paginationPage) : '')) : site_url('admin/secondary_admins/create' . ($paginationPage > 1 ? ('?page=' . $paginationPage) : ''))); ?>" class="admin-shell-button admin-shell-button--primary inline-flex items-center gap-2">
                                        <i class="fas fa-redo" aria-hidden="true"></i>
                                        Ouvrir un autre formulaire
                                    </a>
                                </div>
                            <?php else: ?>
                                <form class="space-y-6" method="post" action="<?php echo e($formAction); ?>">
                                    <input type="hidden" name="_csrf" value="<?php echo e($csrf); ?>">
                                    <input type="hidden" name="action" value="<?php echo e($mode === 'grant' ? 'grant' : ($mode === 'edit' ? 'update_permissions' : 'create')); ?>">
                                    <input type="hidden" name="page" value="<?php echo e((string) $paginationPage); ?>">
                                    <?php if ($mode === 'edit'): ?>
                                        <input type="hidden" name="user_id" value="<?php echo e($userIdValue !== '' ? $userIdValue : (string) ($targetUser['id'] ?? '')); ?>">
                                    <?php endif; ?>

                                    <?php if ($showTargetSummary && $targetUser !== null): ?>
                                        <section class="rounded-2xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                                <div class="flex min-w-0 items-center gap-3">
                                                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-primary-600 to-accent-500 text-sm font-bold text-white shadow-md">
                                                        <?php echo e(strtoupper(substr((string) ($targetUser['first_name'] ?? ''), 0, 1) . substr((string) ($targetUser['last_name'] ?? ''), 0, 1)) ?: 'AD'); ?>
                                                    </span>
                                                    <div class="min-w-0">
                                                        <h3 class="truncate text-base font-bold text-gray-900 dark:text-white"><?php echo e($targetDisplayName); ?></h3>
                                                        <p class="truncate text-sm text-gray-500 dark:text-gray-400"><?php echo e($targetEmail !== '' ? $targetEmail : 'Adresse e-mail absente'); ?></p>
                                                        <div class="mt-3 flex flex-wrap gap-2">
                                                            <span class="<?php echo $targetRole['key'] === 'superviseur' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300' : ($targetRole['key'] === 'moderateur' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'); ?> inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold">
                                                                <i class="fas <?php echo e((string) $targetRole['icon']); ?>" aria-hidden="true"></i>
                                                                <?php echo e((string) $targetRole['label']); ?>
                                                            </span>
                                                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold <?php echo $targetIsActive ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>">
                                                                <i class="fas <?php echo $targetIsActive ? 'fa-circle text-[6px]' : 'fa-ban'; ?>" aria-hidden="true"></i>
                                                                <?php echo e($targetStatus !== '' ? $targetStatus : ($targetIsActive ? 'Actif' : 'Suspendu')); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex flex-wrap gap-2">
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                        <i class="far fa-clock" aria-hidden="true"></i>
                                                        Créé le <?php echo e(trim($targetCreatedAt) !== '' ? date('d/m/Y H:i', strtotime($targetCreatedAt) ?: time()) : 'Date non disponible'); ?>
                                                    </span>
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-primary-50 px-2 py-1 text-xs font-bold text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">
                                                        <?php echo e((string) count($targetPermissionLabels)); ?> permission<?php echo count($targetPermissionLabels) > 1 ? 's' : ''; ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="mt-4 flex flex-wrap gap-2">
                                                <?php if ($targetPermissionLabels !== []): ?>
                                                    <?php foreach ($targetPermissionLabels as $permissionLabel): ?>
                                                        <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300"><i class="fas fa-check text-green-500" aria-hidden="true"></i><?php echo e((string) $permissionLabel); ?></span>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300">
                                                        <i class="fas fa-exclamation-circle" aria-hidden="true"></i>Aucune permission active
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </section>
                                    <?php endif; ?>

                                    <?php if ($showIdentityFields): ?>
                                        <div class="grid gap-3 sm:grid-cols-2">
                                            <div class="admin-shell-field">
                                                <label class="admin-shell-label" for="secondary-create-first">Prénom</label>
                                                <input id="secondary-create-first" name="first_name" type="text" autocomplete="given-name" required class="admin-shell-input" placeholder="Jean" value="<?php echo e((string) ($formValues['first_name'] ?? '')); ?>">
                                            </div>
                                            <div class="admin-shell-field">
                                                <label class="admin-shell-label" for="secondary-create-last">Nom</label>
                                                <input id="secondary-create-last" name="last_name" type="text" autocomplete="family-name" required class="admin-shell-input" placeholder="Dupont" value="<?php echo e((string) ($formValues['last_name'] ?? '')); ?>">
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($showEmailField): ?>
                                        <div class="admin-shell-field">
                                            <label class="admin-shell-label" for="secondary-form-email">Adresse e-mail<?php echo $mode === 'grant' ? ' du compte' : ''; ?></label>
                                            <input id="secondary-form-email" name="email" type="email" autocomplete="email" required class="admin-shell-input" placeholder="<?php echo e($mode === 'grant' ? 'utilisateur@ecole.fr' : 'jean@ecole.fr'); ?>" value="<?php echo e((string) ($formValues['email'] ?? '')); ?>" <?php echo $mode === 'edit' ? 'readonly tabindex="-1"' : ''; ?>>
                                            <?php if ($mode === 'grant'): ?>
                                                <p class="admin-secondary-template-help">Le compte doit déjà exister dans la base utilisateurs.</p>
                                            <?php elseif ($mode === 'create'): ?>
                                                <p class="admin-secondary-template-help">L’adresse doit être unique.</p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($showPasswordField): ?>
                                        <div class="admin-shell-field">
                                            <label class="admin-shell-label" for="secondary-create-password">Mot de passe temporaire</label>
                                            <input id="secondary-create-password" name="password" type="password" minlength="8" required class="admin-shell-input" placeholder="Au moins 8 caractères">
                                            <p class="admin-secondary-template-help">8 caractères minimum, avec une majuscule, un chiffre et un caractère spécial.</p>
                                        </div>
                                    <?php endif; ?>

                                    <div class="admin-shell-field">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <p class="admin-shell-label">Fonctionnalités autorisées</p>
                                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400"><?php echo e((string) $selectedPermissionCount); ?> sélectionnée<?php echo $selectedPermissionCount > 1 ? 's' : ''; ?></span>
                                        </div>
                                        <div class="admin-secondary-template-permission-grid mt-3">
                                            <?php foreach ($permissionsCatalog as $permissionKey => $permissionLabel): ?>
                                                <label class="admin-secondary-permission-option">
                                                    <input type="checkbox" name="permissions[]" value="<?php echo e((string) $permissionKey); ?>" <?php echo in_array((string) $permissionKey, $selectedPermissions, true) ? 'checked' : ''; ?> class="app-checkbox rounded border-slate-300">
                                                    <span class="app-icon" aria-hidden="true"><?php echo $permissionIcon((string) $permissionKey); ?></span>
                                                    <span><?php echo e((string) $permissionLabel); ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-4 dark:border-gray-700">
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            <p class="font-semibold text-gray-700 dark:text-gray-200"><?php echo e($notice !== '' ? $notice : 'Complétez le formulaire avant validation.'); ?></p>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <a href="<?php echo e($listUrl); ?>" class="admin-shell-button admin-shell-button--secondary inline-flex items-center gap-2">
                                                <i class="fas fa-times" aria-hidden="true"></i>
                                                Annuler
                                            </a>
                                            <button type="submit" class="admin-shell-button admin-shell-button--primary inline-flex items-center gap-2">
                                                <i class="fas fa-<?php echo e($submitIcon); ?>" aria-hidden="true"></i>
                                                <?php echo e($submitLabel); ?>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </article>

                        <aside class="space-y-4">
                            <article class="admin-shell-card rounded-3xl p-5 space-y-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 class="text-lg font-black inline-flex items-center gap-2">
                                            <i class="fas fa-sliders-h text-primary-600 dark:text-primary-300" aria-hidden="true"></i>
                                            Aperçu des droits
                                        </h3>
                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Le formulaire utilise le même catalogue que la liste.</p>
                                    </div>
                                    <span class="admin-dashboard-count <?php echo count($selectedPermissionLabels) > 0 ? 'admin-dashboard-count--success' : 'admin-dashboard-count--warning'; ?>">
                                        <?php echo e((string) count($selectedPermissionLabels)); ?>/<?php echo e((string) $permissionsTotal); ?>
                                    </span>
                                </div>

                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm leading-6 text-slate-600 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-300">
                                    <p class="font-bold text-slate-900 dark:text-white">Mode <?php echo e($currentModeLabel); ?></p>
                                    <p class="mt-1"><?php echo e($notice !== '' ? $notice : 'Les changements sont validés immédiatement après soumission.'); ?></p>
                                </div>

                                <div class="grid gap-3">
                                    <div class="rounded-2xl border border-gray-100 bg-white p-4 dark:border-gray-700 dark:bg-gray-900/40">
                                        <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Permissions sélectionnées</p>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <?php if ($selectedPermissionLabels !== []): ?>
                                                <?php foreach ($selectedPermissionLabels as $permissionLabel): ?>
                                                    <span class="<?php echo e($buildPermissionChip($permissionLabel, 'blue')); ?>"><?php echo e((string) $permissionLabel); ?></span>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="<?php echo e($buildPermissionChip('Aucune permission', 'amber')); ?>">Aucune permission</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="rounded-2xl border border-gray-100 bg-white p-4 dark:border-gray-700 dark:bg-gray-900/40">
                                        <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Flux rapide</p>
                                        <div class="mt-3 grid gap-2">
                                            <a href="<?php echo e($listUrl); ?>" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                                <i class="fas fa-list" aria-hidden="true"></i>
                                                Revenir à la liste
                                            </a>
                                            <a href="<?php echo e($adminSupportAgentsUrl); ?>" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                                <i class="fas fa-headset" aria-hidden="true"></i>
                                                Voir l’équipe support
                                            </a>
                                            <a href="<?php echo e($adminDashboardUrl); ?>" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                                <i class="fas fa-tachometer-alt" aria-hidden="true"></i>
                                                Tableau de bord
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </article>

                            <?php if ($showTargetSummary && $targetUser !== null): ?>
                                <article class="admin-shell-card rounded-3xl p-5 space-y-4">
                                    <div>
                                        <h3 class="text-lg font-black inline-flex items-center gap-2">
                                            <i class="fas fa-user-check text-primary-600 dark:text-primary-300" aria-hidden="true"></i>
                                            Compte ciblé
                                        </h3>
                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Modification des permissions uniquement.</p>
                                    </div>
                                    <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                                        <p class="font-bold text-slate-900 dark:text-white"><?php echo e($targetDisplayName); ?></p>
                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?php echo e($targetEmail !== '' ? $targetEmail : 'Adresse e-mail absente'); ?></p>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <span class="<?php echo $targetRole['key'] === 'superviseur' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300' : ($targetRole['key'] === 'moderateur' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'); ?> inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold">
                                                <i class="fas <?php echo e((string) $targetRole['icon']); ?>" aria-hidden="true"></i>
                                                <?php echo e((string) $targetRole['label']); ?>
                                            </span>
                                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold <?php echo $targetIsActive ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>">
                                                <i class="fas <?php echo $targetIsActive ? 'fa-circle text-[6px]' : 'fa-ban'; ?>" aria-hidden="true"></i>
                                                <?php echo e($targetStatus !== '' ? $targetStatus : ($targetIsActive ? 'Actif' : 'Suspendu')); ?>
                                            </span>
                                        </div>
                                    </div>
                                </article>
                            <?php endif; ?>

                            <article class="admin-shell-card rounded-3xl p-5 space-y-4">
                                <div>
                                    <h3 class="text-lg font-black inline-flex items-center gap-2">
                                        <i class="fas fa-info-circle text-amber-600 dark:text-amber-300" aria-hidden="true"></i>
                                        Conseils
                                    </h3>
                                </div>
                                <ul class="space-y-3 text-sm text-slate-600 dark:text-slate-300">
                                    <li class="flex gap-2">
                                        <i class="fas fa-check mt-0.5 text-green-500" aria-hidden="true"></i>
                                        Utilisez la page de création pour un nouveau compte complet.
                                    </li>
                                    <li class="flex gap-2">
                                        <i class="fas fa-check mt-0.5 text-green-500" aria-hidden="true"></i>
                                        Utilisez la page d’attribution pour un compte déjà existant.
                                    </li>
                                    <li class="flex gap-2">
                                        <i class="fas fa-check mt-0.5 text-green-500" aria-hidden="true"></i>
                                        La liste conserve les actions rapides de suspension et de retrait.
                                    </li>
                                </ul>
                            </article>
                        </aside>
                    </section>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
