<?php
$adminPermissions = is_array($adminPermissions ?? null) ? $adminPermissions : [];
$isGeneralAdmin = !empty($isGeneralAdmin);
$errors = is_array($errors ?? null) ? $errors : [];
$announcements = is_array($announcements ?? null) ? $announcements : [];
$announcementEvents = is_array($announcementEvents ?? null) ? $announcementEvents : [];
$successMessage = (string) ($successMessage ?? '');
$totalAnnouncements = (int) ($totalAnnouncements ?? count($announcements));
$filters = is_array($filters ?? null) ? $filters : [];
$viewMode = (string) ($viewMode ?? ($_GET['view'] ?? ''));
$isCreateView = $viewMode === 'create';
$page = max(1, (int) ($page ?? 1));
$perPage = max(1, (int) ($perPage ?? 20));
$totalPages = max(1, (int) ($totalPages ?? 1));
$notificationsUrl = (string) ($notificationsUrl ?? site_url('admin/notifications?scope=admin'));
$adminDashboardUrl = (string) ($adminDashboardUrl ?? site_url('admin/dashboard'));
$hasActiveFilters = trim((string) ($filters['search'] ?? '')) !== ''
    || trim((string) ($filters['status'] ?? '')) !== ''
    || trim((string) ($filters['audience'] ?? '')) !== ''
    || trim((string) ($filters['category'] ?? '')) !== ''
    || trim((string) ($filters['importance'] ?? '')) !== '';
$openAnnouncementId = max(0, (int) ($_GET['open'] ?? 0));

$adminNavActive = 'announcements';
$adminTopTitle = 'Gestion des annonces';
$adminTopSubtitle = 'Communication';
$adminTopBadge = $totalAnnouncements > 0
    ? $totalAnnouncements . ' ' . ($totalAnnouncements === 1 ? 'annonce' : 'annonces')
    : 'Aucune annonce';
$adminTopBadgeTone = $totalAnnouncements > 0 ? 'active' : 'done';
$adminTopShowSearch = false;
$adminPageScripts = ['admin_announcements.js'];

$audienceOptions = [
    'all' => 'Tout le monde',
    'student' => 'Étudiants',
    'teacher' => 'Enseignants',
];
$categoryOptions = [
    'general' => 'Actualité',
    'system' => 'Système',
    'learning' => 'Pédagogie',
    'support' => 'Support',
    'promo' => 'Promotion',
];
$categoryIcons = [
    'general' => 'fa-newspaper',
    'system' => 'fa-cogs',
    'learning' => 'fa-graduation-cap',
    'support' => 'fa-life-ring',
    'promo' => 'fa-tag',
];
$importanceOptions = [
    'info' => 'Info',
    'important' => 'Important',
    'urgent' => 'Urgent',
];
$statusLabels = [
    'draft' => 'Brouillon',
    'pending_review' => 'En validation',
    'published' => 'Publiée',
    'archived' => 'Archivée',
];
$eventLabels = [
    'created' => 'Créée',
    'draft_saved' => 'Brouillon enregistré',
    'submitted' => 'Soumise',
    'published' => 'Publiée',
    'archived' => 'Archivée',
    'restored' => 'Restaurée',
];
$audienceToneClasses = [
    'all' => 'bg-teal-100 text-teal-700 dark:bg-teal-500/15 dark:text-teal-200',
    'student' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-200',
    'teacher' => 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-200',
];
$statusToneClasses = [
    'draft' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
    'pending_review' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-200',
    'published' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200',
    'archived' => 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
];
$importanceToneClasses = [
    'info' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-200',
    'important' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-200',
    'urgent' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-200',
];
$thumbToneClasses = [
    'general' => 'bg-sky-500/10 text-sky-700 ring-1 ring-sky-200 dark:bg-sky-500/10 dark:text-sky-200 dark:ring-sky-400/20',
    'system' => 'bg-slate-500/10 text-slate-700 ring-1 ring-slate-200 dark:bg-slate-500/10 dark:text-slate-200 dark:ring-slate-400/20',
    'learning' => 'bg-violet-500/10 text-violet-700 ring-1 ring-violet-200 dark:bg-violet-500/10 dark:text-violet-200 dark:ring-violet-400/20',
    'support' => 'bg-emerald-500/10 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20',
    'promo' => 'bg-amber-500/10 text-amber-700 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-400/20',
];
$cardAccentToneClasses = [
    'general' => 'border-l-sky-500 dark:border-l-sky-400',
    'system' => 'border-l-slate-500 dark:border-l-slate-400',
    'learning' => 'border-l-violet-500 dark:border-l-violet-400',
    'support' => 'border-l-emerald-500 dark:border-l-emerald-400',
    'promo' => 'border-l-amber-500 dark:border-l-amber-400',
];

$normalizeText = static function (string $value): string {
    $value = trim(strip_tags($value));
    if ($value === '') {
        return '';
    }
    $value = (string) preg_replace('/\s+/u', ' ', $value);
    return trim($value);
};

$summarizeText = static function (string $value, int $limit = 160) use ($normalizeText): string {
    $value = $normalizeText($value);
    if ($value === '') {
        return '';
    }

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($value, 'UTF-8') <= $limit) {
            return $value;
        }
        return (string) mb_substr($value, 0, $limit, 'UTF-8') . '…';
    }

    if (strlen($value) <= $limit) {
        return $value;
    }

    return substr($value, 0, $limit) . '…';
};

$formatDateTime = static function (?string $value): string {
    $value = trim((string) $value);
    if ($value === '') {
        return 'Non définie';
    }
    $timestamp = strtotime($value);
    return $timestamp !== false ? date('d/m/Y H:i', $timestamp) : $value;
};

$formatDate = static function (?string $value): string {
    $value = trim((string) $value);
    if ($value === '') {
        return 'Non définie';
    }
    $timestamp = strtotime($value);
    return $timestamp !== false ? date('d/m/Y', $timestamp) : $value;
};

$buildQuery = static function (array $state, bool $includePage = false, int $pageValue = 1): string {
    $params = [];
    foreach (['q' => 'search', 'status' => 'status', 'audience' => 'audience', 'category' => 'category', 'importance' => 'importance'] as $queryKey => $stateKey) {
        $value = trim((string) ($state[$stateKey] ?? ''));
        if ($value !== '') {
            $params[$queryKey] = $value;
        }
    }
    if ($includePage && $pageValue > 1) {
        $params['page'] = $pageValue;
    }
    return http_build_query($params);
};

$buildListUrl = static function (int $pageValue = 1) use ($buildQuery, $filters): string {
    $query = $buildQuery($filters, true, $pageValue);
    return site_url('admin/announcements') . ($query !== '' ? '?' . $query : '');
};

$buildCreateUrl = static function () use ($buildQuery, $filters): string {
    $query = $buildQuery($filters, false);
    return site_url('admin/announcements/create') . ($query !== '' ? '?' . $query : '');
};

$actionState = [
    'page' => $page,
    'q' => (string) ($filters['search'] ?? ''),
    'status' => (string) ($filters['status'] ?? ''),
    'audience' => (string) ($filters['audience'] ?? ''),
    'category' => (string) ($filters['category'] ?? ''),
    'importance' => (string) ($filters['importance'] ?? ''),
];
$renderActionStateInputs = static function (array $state): string {
    $html = '';
    foreach ($state as $name => $value) {
        $value = trim((string) $value);
        if ($name === 'page' && (int) $value <= 1) {
            continue;
        }
        if ($value === '') {
            continue;
        }
        $html .= '<input type="hidden" name="' . e((string) $name) . '" value="' . e($value) . '">' . "\n";
    }
    return $html;
};

$old = static function (string $key, string $default = ''): string {
    return (string) ($_POST[$key] ?? $default);
};
$selected = static function (string $current, string $expected): string {
    return $current === $expected ? 'selected' : '';
};

$visibleFrom = $totalAnnouncements > 0 ? (($page - 1) * $perPage) + 1 : 0;
$visibleTo = $totalAnnouncements > 0 ? min($totalAnnouncements, $page * $perPage) : 0;
$paginationItems = [];
if ($totalPages > 1) {
    $candidates = [1, 2, $page - 1, $page, $page + 1, $totalPages - 1, $totalPages];
    $pages = [];
    foreach ($candidates as $candidate) {
        $candidate = (int) $candidate;
        if ($candidate >= 1 && $candidate <= $totalPages) {
            $pages[$candidate] = $candidate;
        }
    }
    ksort($pages);
    $previousPage = null;
    foreach (array_values($pages) as $pageNumber) {
        if ($previousPage !== null && $pageNumber - $previousPage > 1) {
            $paginationItems[] = null;
        }
        $paginationItems[] = $pageNumber;
        $previousPage = $pageNumber;
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des annonces - Administrateur</title>
    <?php require __DIR__ . '/_head_assets.php'; ?>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="app-view app-role-admin min-h-screen bg-slate-50 text-slate-900 font-body dark:bg-slate-950 dark:text-slate-100"
    data-view="admin/announcements"
    data-sidebar-desktop-toggle="1"
    data-announcement-open-id="<?php echo e((string) $openAnnouncementId); ?>"
>
    <div class="admin-shell-backdrop">
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--one"></div>
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--two"></div>
    </div>

    <div class="admin-shell-root relative z-10 flex min-h-screen">
        <?php require __DIR__ . '/_sidebar.php'; ?>
        <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

        <div class="admin-shell-main flex min-w-0 flex-1 flex-col">
            <?php require __DIR__ . '/_topbar.php'; ?>
            <main class="admin-shell-content admin-shell-dense px-4 py-5 sm:px-6 sm:py-6 space-y-6">
                <?php if ($errors !== []): ?>
                    <section class="admin-shell-alert admin-shell-alert--error">
                        <?php foreach ($errors as $error): ?>
                            <p class="inline-flex items-center gap-2"><?php echo e((string) $error); ?></p>
                        <?php endforeach; ?>
                    </section>
                <?php endif; ?>

                <?php if ($successMessage !== ''): ?>
                    <section class="admin-shell-alert admin-shell-alert--success inline-flex items-center gap-2">
                        <?php echo e($successMessage); ?>
                    </section>
                <?php endif; ?>

                <?php if ($isCreateView): ?>
                    <section class="overflow-hidden rounded-2xl bg-gradient-to-r from-primary-600 to-accent-600 p-6 text-white shadow-glow">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="max-w-2xl">
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-white/80">Communication</p>
                                <h1 class="mt-2 inline-flex items-center gap-3 text-2xl font-bold text-white sm:text-3xl">
                                    <i class="fas fa-bullhorn" aria-hidden="true"></i>
                                    Nouvelle annonce
                                </h1>
                            </div>
                            <a href="<?php echo e($buildListUrl()); ?>" class="inline-flex items-center gap-2 rounded-2xl bg-white/95 px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-white">
                                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                Retour à la liste
                            </a>
                        </div>
                    </section>

                    <section class="rounded-3xl border border-slate-200/80 bg-white/95 p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80 sm:p-6">
                        <form method="post" action="<?php echo e($buildCreateUrl()); ?>" class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_19rem]">
                            <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">

                            <div class="grid gap-4">
                                <div class="admin-shell-field">
                                    <label for="announcement-title" class="admin-shell-label inline-flex items-center gap-2">
                                        <i class="fas fa-heading" aria-hidden="true"></i>Titre
                                    </label>
                                    <input id="announcement-title" name="title" type="text" required maxlength="190" class="admin-shell-input" placeholder="Ex : Nouvelle formation disponible" value="<?php echo e($old('title')); ?>">
                                </div>

                                <div class="admin-shell-field">
                                    <label for="announcement-summary" class="admin-shell-label inline-flex items-center gap-2">
                                        <i class="fas fa-align-left" aria-hidden="true"></i>Résumé
                                    </label>
                                    <textarea id="announcement-summary" name="summary" rows="2" maxlength="140" class="admin-shell-input" placeholder="Texte court affiché dans les notifications."><?php echo e($old('summary')); ?></textarea>
                                </div>

                                <div class="admin-shell-field">
                                    <label for="announcement-body" class="admin-shell-label inline-flex items-center gap-2">
                                        <i class="fas fa-comment-alt" aria-hidden="true"></i>Message
                                    </label>
                                    <textarea id="announcement-body" name="body" rows="9" required class="admin-shell-input" placeholder="Rédigez le contenu complet de l’annonce."><?php echo e($old('body')); ?></textarea>
                                </div>

                                <div class="admin-shell-field">
                                    <label for="announcement-action-url" class="admin-shell-label inline-flex items-center gap-2">
                                        <i class="fas fa-link" aria-hidden="true"></i>Lien d’action
                                    </label>
                                    <input id="announcement-action-url" name="action_url" type="text" class="admin-shell-input" placeholder="catalogue ou https://..." value="<?php echo e($old('action_url')); ?>">
                                </div>

                            </div>

                            <aside class="grid content-start gap-4 rounded-3xl border border-slate-200 bg-slate-50/90 p-4 dark:border-slate-700 dark:bg-slate-950/35">
                                <div class="admin-shell-field">
                                    <label for="announcement-audience" class="admin-shell-label inline-flex items-center gap-2">
                                        <i class="fas fa-users" aria-hidden="true"></i>Audience
                                    </label>
                                    <select id="announcement-audience" name="audience" class="admin-shell-input">
                                        <?php foreach ($audienceOptions as $value => $label): ?>
                                            <option value="<?php echo e($value); ?>" <?php echo $selected($old('audience', 'all'), $value); ?>><?php echo e($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="admin-shell-field">
                                    <label for="announcement-category" class="admin-shell-label inline-flex items-center gap-2">
                                        <i class="fas fa-layer-group" aria-hidden="true"></i>Type
                                    </label>
                                    <select id="announcement-category" name="category" class="admin-shell-input">
                                        <?php foreach ($categoryOptions as $value => $label): ?>
                                            <option value="<?php echo e($value); ?>" <?php echo $selected($old('category', 'general'), $value); ?>><?php echo e($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="admin-shell-field">
                                    <label for="announcement-importance" class="admin-shell-label inline-flex items-center gap-2">
                                        <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>Importance
                                    </label>
                                    <select id="announcement-importance" name="importance" class="admin-shell-input">
                                        <?php foreach ($importanceOptions as $value => $label): ?>
                                            <option value="<?php echo e($value); ?>" <?php echo $selected($old('importance', 'info'), $value); ?>><?php echo e($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="rounded-2xl border border-slate-200 bg-white p-4 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                                    <p class="font-semibold text-slate-900 dark:text-white">Conseil</p>
                                    <p class="mt-2 leading-6">
                                        Gardez le résumé court. Le message complet sera visible dans la modale de lecture et dans les notifications selon le contexte.
                                    </p>
                                </div>

                                <div class="flex flex-col gap-2 pt-1">
                                    <button type="submit" name="action" value="save_draft" class="admin-shell-button admin-shell-button--secondary inline-flex items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-semibold">
                                        <i class="fas fa-save" aria-hidden="true"></i>
                                        Enregistrer brouillon
                                    </button>
                                    <button type="submit" name="action" value="submit_review" class="admin-shell-button admin-shell-button--secondary inline-flex items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-semibold">
                                        <i class="fas fa-paper-plane" aria-hidden="true"></i>
                                        Soumettre
                                    </button>
                                    <?php if ($isGeneralAdmin): ?>
                                        <button type="submit" name="action" value="publish" class="admin-shell-button admin-shell-button--primary inline-flex items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-semibold">
                                            <i class="fas fa-bolt" aria-hidden="true"></i>
                                            Publier maintenant
                                        </button>
                                    <?php else: ?>
                                        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                                            La publication directe reste réservée aux administrateurs généraux.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </aside>
                        </form>
                    </section>
                <?php else: ?>
                    <section class="overflow-hidden rounded-2xl bg-gradient-to-r from-primary-600 to-accent-600 p-6 text-white shadow-glow">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="max-w-2xl">
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-white/80">Communication</p>
                                <h1 class="mt-2 inline-flex items-center gap-3 text-2xl font-bold text-white sm:text-3xl">
                                    <i class="fas fa-bullhorn" aria-hidden="true"></i>
                                    Communication & actualités
                                </h1>
                            </div>
                            <a href="<?php echo e($buildCreateUrl()); ?>" class="inline-flex items-center gap-2 rounded-2xl bg-white/95 px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-white">
                                <i class="fas fa-plus-circle" aria-hidden="true"></i>
                                Nouvelle annonce
                            </a>
                        </div>
                    </section>

                    <section class="rounded-3xl border border-slate-200/80 bg-white/95 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/80 sm:p-5">
                        <form method="get" action="<?php echo e(site_url('admin/announcements')); ?>" class="grid gap-3 xl:grid-cols-[minmax(18rem,1.2fr)_12rem_12rem_12rem_12rem_auto]">
                            <label class="relative block">
                                <span class="sr-only">Rechercher une annonce</span>
                                <i class="fas fa-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
                                <input type="search" name="q" value="<?php echo e((string) ($filters['search'] ?? '')); ?>" placeholder="Rechercher une annonce..." class="admin-shell-input w-full pl-10">
                            </label>
                            <select name="status" class="admin-shell-input">
                                <option value="">Tous les statuts</option>
                                <?php foreach ($statusLabels as $value => $label): ?>
                                    <option value="<?php echo e($value); ?>" <?php echo $selected((string) ($filters['status'] ?? ''), $value); ?>><?php echo e($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="category" class="admin-shell-input">
                                <option value="">Tous les types</option>
                                <?php foreach ($categoryOptions as $value => $label): ?>
                                    <option value="<?php echo e($value); ?>" <?php echo $selected((string) ($filters['category'] ?? ''), $value); ?>><?php echo e($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="audience" class="admin-shell-input">
                                <option value="">Toutes les audiences</option>
                                <?php foreach ($audienceOptions as $value => $label): ?>
                                    <option value="<?php echo e($value); ?>" <?php echo $selected((string) ($filters['audience'] ?? ''), $value); ?>><?php echo e($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="importance" class="admin-shell-input">
                                <option value="">Toutes les priorités</option>
                                <?php foreach ($importanceOptions as $value => $label): ?>
                                    <option value="<?php echo e($value); ?>" <?php echo $selected((string) ($filters['importance'] ?? ''), $value); ?>><?php echo e($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="flex items-end gap-2">
                                <button type="submit" class="admin-shell-button admin-shell-button--primary inline-flex items-center gap-2 rounded-2xl px-4 py-3 text-sm font-semibold">
                                    <i class="fas fa-filter" aria-hidden="true"></i>
                                    Filtrer
                                </button>
                                <?php if ($hasActiveFilters): ?>
                                    <a href="<?php echo e(site_url('admin/announcements')); ?>" class="admin-shell-button admin-shell-button--secondary inline-flex items-center gap-2 rounded-2xl px-4 py-3 text-sm font-semibold">
                                        <i class="fas fa-rotate-left" aria-hidden="true"></i>
                                        Réinitialiser
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </section>

                    <?php if ($announcements === []): ?>
                        <section class="rounded-3xl border border-dashed border-slate-300 bg-white/90 p-8 shadow-sm dark:border-slate-700 dark:bg-slate-950/40">
                            <div class="mx-auto max-w-xl text-center">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-100 text-blue-600 dark:bg-blue-500/15 dark:text-blue-300">
                                    <i class="fas fa-bullhorn text-xl" aria-hidden="true"></i>
                                </div>
                                <h2 class="mt-4 text-xl font-bold text-slate-900 dark:text-white">Aucune annonce</h2>
                                <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                                    Aucune annonce ne correspond à votre recherche. Créez une nouvelle annonce ou ajustez vos filtres.
                                </p>
                                <div class="mt-5 flex flex-wrap items-center justify-center gap-2">
                                    <a href="<?php echo e($buildCreateUrl()); ?>" class="admin-shell-button admin-shell-button--primary inline-flex items-center gap-2 rounded-2xl px-4 py-3 text-sm font-semibold">
                                        <i class="fas fa-plus-circle" aria-hidden="true"></i>
                                        Nouvelle annonce
                                    </a>
                                    <?php if ($hasActiveFilters): ?>
                                        <a href="<?php echo e(site_url('admin/announcements')); ?>" class="admin-shell-button admin-shell-button--secondary inline-flex items-center gap-2 rounded-2xl px-4 py-3 text-sm font-semibold">
                                            <i class="fas fa-rotate-left" aria-hidden="true"></i>
                                            Réinitialiser
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </section>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($announcements as $announcement): ?>
                                <?php
                                $announcementId = (int) ($announcement['id'] ?? 0);
                                if ($announcementId < 1) {
                                    continue;
                                }

                                $title = trim((string) ($announcement['title'] ?? ''));
                                $body = trim((string) ($announcement['body'] ?? ''));
                                $summary = trim((string) ($announcement['summary'] ?? ''));
                                $actionUrl = trim((string) ($announcement['action_url'] ?? ''));
                                $status = (string) ($announcement['status'] ?? 'draft');
                                $audience = (string) ($announcement['audience'] ?? 'all');
                                $category = (string) ($announcement['category'] ?? 'general');
                                $importance = (string) ($announcement['importance'] ?? 'info');
                                $sentCount = max(0, (int) ($announcement['sent_count'] ?? 0));
                                $readCount = max(0, (int) ($announcement['read_count'] ?? 0));
                                $clickCount = max(0, (int) ($announcement['click_count'] ?? 0));
                                $readRate = $sentCount > 0 ? round(($readCount / $sentCount) * 100, 1) : 0.0;
                                $clickRate = $sentCount > 0 ? round(($clickCount / $sentCount) * 100, 1) : 0.0;
                                $author = trim((string) (($announcement['admin_first_name'] ?? '') . ' ' . ($announcement['admin_last_name'] ?? '')));
                                $publishedBy = trim((string) (($announcement['published_first_name'] ?? '') . ' ' . ($announcement['published_last_name'] ?? '')));
                                $approvedBy = trim((string) (($announcement['approved_first_name'] ?? '') . ' ' . ($announcement['approved_last_name'] ?? '')));
                                $publishedAt = trim((string) ($announcement['published_at'] ?? ''));
                                $createdAt = trim((string) ($announcement['created_at'] ?? ''));
                                $updatedAt = trim((string) ($announcement['updated_at'] ?? ''));
                                $dateLabel = $publishedAt !== '' ? $formatDate($publishedAt) : $formatDate($createdAt);
                                $dateTimeLabel = $publishedAt !== '' ? $formatDateTime($publishedAt) : $formatDateTime($createdAt);
                                $categoryLabel = (string) ($categoryOptions[$category] ?? 'Actualité');
                                $audienceLabel = (string) ($audienceOptions[$audience] ?? 'Tout le monde');
                                $statusLabel = (string) ($statusLabels[$status] ?? ucfirst($status));
                                $importanceLabel = (string) ($importanceOptions[$importance] ?? 'Info');
                                $thumbClass = (string) ($thumbToneClasses[$category] ?? $thumbToneClasses['general']);
                                $cardAccentClass = (string) ($cardAccentToneClasses[$category] ?? $cardAccentToneClasses['general']);
                                $categoryIcon = (string) ($categoryIcons[$category] ?? 'fa-bullhorn');
                                $events = is_array($announcementEvents[$announcementId] ?? null) ? $announcementEvents[$announcementId] : [];
                                $detailSummary = $summary !== '' ? $summary : $summarizeText($body, 160);
                                $bodyText = $body !== '' ? $body : 'Message indisponible.';
                                $publishedLabel = $publishedBy !== '' ? $publishedBy : 'Non définie';
                                $approvedLabel = $approvedBy !== '' ? $approvedBy : 'Non définie';
                                $hasActionUrl = $actionUrl !== '';
                                $isExternalAction = $hasActionUrl && preg_match('~^https?://~i', $actionUrl) === 1;
                                ?>
                                <article class="overflow-hidden rounded-[1.65rem] border border-slate-200/80 border-l-4 <?php echo e($cardAccentClass); ?> bg-white/95 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900/80">
                                    <div class="grid gap-2.5 p-3 sm:p-3.5">
                                        <div class="flex items-start gap-3">
                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl <?php echo e($thumbClass); ?>">
                                                <i class="fas <?php echo e($categoryIcon); ?>" aria-hidden="true"></i>
                                            </div>

                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-start justify-between gap-2.5">
                                                    <div class="min-w-0 flex-1">
                                                        <p class="inline-flex items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">
                                                            <i class="fas <?php echo e($categoryIcon); ?>" aria-hidden="true"></i>
                                                            <?php echo e($categoryLabel); ?>
                                                        </p>
                                                        <h2 class="mt-1.5 text-base font-bold leading-6 text-slate-900 dark:text-white sm:text-lg">
                                                            <?php echo e($title !== '' ? $title : 'Annonce'); ?>
                                                        </h2>
                                                    </div>

                                                    <div class="flex shrink-0 flex-col items-end gap-2">
                                                        <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5 text-[11px] font-semibold text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                                            <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                                                            <?php echo e($dateLabel); ?>
                                                        </span>
                                                        <div class="flex flex-wrap justify-end gap-2">
                                                            <button
                                                                type="button"
                                                                class="admin-shell-button admin-shell-button--primary inline-flex items-center justify-center gap-2 rounded-2xl px-3.5 py-2.5 text-sm font-semibold shadow-sm"
                                                                data-announcement-detail-open="<?php echo e((string) $announcementId); ?>"
                                                                data-announcement-detail-title="<?php echo e($title !== '' ? $title : 'Annonce'); ?>"
                                                            >
                                                                <i class="fas fa-up-right-and-down-left-from-center" aria-hidden="true"></i>
                                                                Ouvrir
                                                            </button>
                                                            <?php if ($isGeneralAdmin): ?>
                                                                <button
                                                                    type="button"
                                                                    class="admin-shell-button admin-shell-button--danger inline-flex items-center justify-center gap-2 rounded-2xl px-3.5 py-2.5 text-sm font-semibold"
                                                                    data-announcement-delete-id="<?php echo e((string) $announcementId); ?>"
                                                                    data-announcement-delete-title="<?php echo e($title !== '' ? $title : 'Annonce'); ?>"
                                                                >
                                                                    <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                                                    Supprimer
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mt-3">
                                                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Aperçu</p>
                                                    <p class="truncate block text-sm leading-5 text-slate-700 dark:text-slate-300">
                                                        <?php echo e($detailSummary !== '' ? $detailSummary : 'Aucun résumé disponible.'); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                    </div>

                                    <div id="announcement-detail-content-<?php echo e((string) $announcementId); ?>" class="hidden">
                                        <div class="grid gap-5 lg:grid-cols-[minmax(0,1.35fr)_18rem]">
                                            <div class="grid gap-4">
                                                <section class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950/30">
                                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Résumé</p>
                                                    <p class="mt-2 text-sm leading-6 text-slate-700 dark:text-slate-200">
                                                        <?php echo e($detailSummary !== '' ? $detailSummary : 'Aucun résumé disponible.'); ?>
                                                    </p>
                                                </section>

                                                <section class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
                                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Message complet</p>
                                                    <div class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-700 dark:text-slate-200"><?php echo e($bodyText); ?></div>
                                                </section>

                                                <?php if ($hasActionUrl): ?>
                                                    <section class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
                                                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Lien d’action</p>
                                                        <a
                                                            href="<?php echo e($actionUrl); ?>"
                                                            class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-300 dark:hover:text-primary-200"
                                                            <?php echo $isExternalAction ? 'target="_blank" rel="noreferrer"' : ''; ?>
                                                        >
                                                            <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                                                            <?php echo e($actionUrl); ?>
                                                        </a>
                                                    </section>
                                                <?php endif; ?>

                                                <?php if ($events !== []): ?>
                                                    <section class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
                                                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Historique récent</p>
                                                        <ul class="mt-3 space-y-2">
                                                            <?php foreach ($events as $event): ?>
                                                                <?php
                                                                $eventAction = (string) ($event['action'] ?? '');
                                                                $eventLabel = (string) ($eventLabels[$eventAction] ?? ucfirst(str_replace('_', ' ', $eventAction)));
                                                                $eventActor = trim((string) (($event['actor_first_name'] ?? '') . ' ' . ($event['actor_last_name'] ?? '')));
                                                                $eventNote = trim((string) ($event['note'] ?? ''));
                                                                $eventDate = $formatDateTime((string) ($event['created_at'] ?? ''));
                                                                ?>
                                                                <li class="flex items-start gap-2 text-sm text-slate-600 dark:text-slate-300">
                                                                    <i class="fas fa-circle mt-2 text-[0.35rem] text-primary-500" aria-hidden="true"></i>
                                                                    <span>
                                                                        <?php echo e($eventLabel); ?>
                                                                        <?php if ($eventNote !== ''): ?> · <?php echo e($eventNote); ?><?php endif; ?>
                                                                        <?php if ($eventActor !== ''): ?> par <?php echo e($eventActor); ?><?php endif; ?>
                                                                        <span class="text-slate-400"> • <?php echo e($eventDate); ?></span>
                                                                    </span>
                                                                </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    </section>
                                                <?php endif; ?>
                                            </div>

                                            <aside class="grid gap-4">
                                                <section class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950/30">
                                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Détails</p>
                                                    <div class="mt-3 grid gap-3 text-sm">
                                                        <div class="flex items-center justify-between gap-3">
                                                            <span class="text-slate-500 dark:text-slate-400">Audience</span>
                                                            <strong class="text-slate-900 dark:text-white"><?php echo e($audienceLabel); ?></strong>
                                                        </div>
                                                        <div class="flex items-center justify-between gap-3">
                                                            <span class="text-slate-500 dark:text-slate-400">Type</span>
                                                            <strong class="text-slate-900 dark:text-white"><?php echo e($categoryLabel); ?></strong>
                                                        </div>
                                                        <div class="flex items-center justify-between gap-3">
                                                            <span class="text-slate-500 dark:text-slate-400">Importance</span>
                                                            <strong class="text-slate-900 dark:text-white"><?php echo e($importanceLabel); ?></strong>
                                                        </div>
                                                        <div class="flex items-center justify-between gap-3">
                                                            <span class="text-slate-500 dark:text-slate-400">Statut</span>
                                                            <strong class="text-slate-900 dark:text-white"><?php echo e($statusLabel); ?></strong>
                                                        </div>
                                                        <div class="flex items-center justify-between gap-3">
                                                            <span class="text-slate-500 dark:text-slate-400">Créée</span>
                                                            <strong class="text-slate-900 dark:text-white"><?php echo e($formatDateTime($createdAt)); ?></strong>
                                                        </div>
                                                        <div class="flex items-center justify-between gap-3">
                                                            <span class="text-slate-500 dark:text-slate-400">Publiée</span>
                                                            <strong class="text-slate-900 dark:text-white"><?php echo e($publishedAt !== '' ? $formatDateTime($publishedAt) : 'Non publiée'); ?></strong>
                                                        </div>
                                                        <div class="flex items-center justify-between gap-3">
                                                            <span class="text-slate-500 dark:text-slate-400">Mise à jour</span>
                                                            <strong class="text-slate-900 dark:text-white"><?php echo e($formatDateTime($updatedAt)); ?></strong>
                                                        </div>
                                                        <div class="flex items-center justify-between gap-3">
                                                            <span class="text-slate-500 dark:text-slate-400">Publié par</span>
                                                            <strong class="text-slate-900 dark:text-white"><?php echo e($publishedLabel); ?></strong>
                                                        </div>
                                                        <div class="flex items-center justify-between gap-3">
                                                            <span class="text-slate-500 dark:text-slate-400">Validé par</span>
                                                            <strong class="text-slate-900 dark:text-white"><?php echo e($approvedLabel); ?></strong>
                                                        </div>
                                                    </div>
                                                </section>

                                                <section class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
                                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Performance</p>
                                                    <div class="mt-3 grid grid-cols-3 gap-3">
                                                        <div class="rounded-xl bg-slate-50 p-3 text-center dark:bg-slate-950/40">
                                                            <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Envoyés</p>
                                                            <p class="mt-2 text-lg font-bold text-slate-900 dark:text-white"><?php echo e((string) $sentCount); ?></p>
                                                        </div>
                                                        <div class="rounded-xl bg-slate-50 p-3 text-center dark:bg-slate-950/40">
                                                            <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Lus</p>
                                                            <p class="mt-2 text-lg font-bold text-slate-900 dark:text-white"><?php echo e((string) $readCount); ?></p>
                                                        </div>
                                                        <div class="rounded-xl bg-slate-50 p-3 text-center dark:bg-slate-950/40">
                                                            <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Clics</p>
                                                            <p class="mt-2 text-lg font-bold text-slate-900 dark:text-white"><?php echo e((string) $clickCount); ?></p>
                                                        </div>
                                                    </div>
                                                    <div class="mt-3 grid gap-2 text-sm text-slate-600 dark:text-slate-300">
                                                        <div class="flex items-center justify-between gap-3">
                                                            <span>Taux de lecture</span>
                                                            <strong><?php echo e(number_format($readRate, 1, ',', ' ')); ?>%</strong>
                                                        </div>
                                                        <div class="flex items-center justify-between gap-3">
                                                            <span>Taux de clic</span>
                                                            <strong><?php echo e(number_format($clickRate, 1, ',', ' ')); ?>%</strong>
                                                        </div>
                                                    </div>
                                                </section>
                                            </aside>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($totalPages > 1): ?>
                            <nav class="flex flex-wrap items-center justify-between gap-3 rounded-3xl border border-slate-200/80 bg-white/90 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/80" aria-label="Pagination des annonces">
                                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">
                                    Affichage <?php echo e((string) $visibleFrom); ?>-<?php echo e((string) $visibleTo); ?> sur <?php echo e((string) $totalAnnouncements); ?>
                                </p>
                                <div class="flex flex-wrap items-center gap-2">
                                    <?php if ($page > 1): ?>
                                        <a href="<?php echo e($buildListUrl($page - 1)); ?>" class="admin-shell-button admin-shell-button--secondary inline-flex items-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-semibold">
                                            <i class="fas fa-chevron-left" aria-hidden="true"></i>
                                            Précédent
                                        </a>
                                    <?php endif; ?>

                                    <?php foreach ($paginationItems as $item): ?>
                                        <?php if ($item === null): ?>
                                            <span class="px-2 text-slate-400 dark:text-slate-600">…</span>
                                        <?php elseif ((int) $item === $page): ?>
                                            <span class="inline-flex items-center rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white">
                                                <?php echo e((string) $item); ?>
                                            </span>
                                        <?php else: ?>
                                            <a href="<?php echo e($buildListUrl((int) $item)); ?>" class="admin-shell-button admin-shell-button--secondary inline-flex items-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-semibold">
                                                <?php echo e((string) $item); ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>

                                    <?php if ($page < $totalPages): ?>
                                        <a href="<?php echo e($buildListUrl($page + 1)); ?>" class="admin-shell-button admin-shell-button--secondary inline-flex items-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-semibold">
                                            Suivant
                                            <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <div id="announcement-detail-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 backdrop-blur-sm p-4 js-admin-modal" role="dialog" aria-modal="true" aria-labelledby="announcement-detail-modal-title" aria-hidden="true">
        <div class="admin-shell-modal w-full max-w-5xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Consultation</p>
                    <h3 id="announcement-detail-modal-title" class="mt-1 text-xl font-bold text-slate-900 dark:text-white">Détails de l’annonce</h3>
                </div>
                <button type="button" class="admin-shell-button admin-shell-button--secondary js-modal-close inline-flex items-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-semibold" data-modal-target="announcement-detail-modal">
                    <i class="fas fa-times" aria-hidden="true"></i>
                    Fermer
                </button>
            </div>
            <div id="announcement-detail-modal-body" class="max-h-[80vh] overflow-y-auto p-5 sm:p-6"></div>
        </div>
    </div>

    <div id="announcement-delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 backdrop-blur-sm p-4 js-admin-modal" role="dialog" aria-modal="true" aria-labelledby="announcement-delete-modal-title" aria-hidden="true">
        <div class="admin-shell-modal w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 id="announcement-delete-modal-title" class="text-xl font-bold text-slate-900 dark:text-white inline-flex items-center gap-2">
                        <i class="fas fa-trash-alt text-red-500" aria-hidden="true"></i>
                        Supprimer l’annonce
                    </h3>
                    <p id="announcement-delete-message" class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                        Supprimer cette annonce ?
                    </p>
                </div>
                <button type="button" class="admin-shell-button admin-shell-button--secondary js-modal-close inline-flex items-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-semibold" data-modal-target="announcement-delete-modal">
                    <i class="fas fa-times" aria-hidden="true"></i>
                    Fermer
                </button>
            </div>
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Cette action est définitive.</p>
            <form id="announcement-delete-form" method="post" action="<?php echo e(site_url('admin/announcements')); ?>" class="mt-5 flex flex-wrap justify-end gap-3">
                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="announcement_id" id="announcement-delete-id" value="">
                <?php echo $renderActionStateInputs($actionState); ?>
                <button type="button" class="admin-shell-button admin-shell-button--secondary inline-flex items-center gap-2 rounded-2xl px-4 py-3 text-sm font-semibold js-modal-close" data-modal-target="announcement-delete-modal">
                    <i class="fas fa-times" aria-hidden="true"></i>
                    Annuler
                </button>
                <button type="submit" class="admin-shell-button admin-shell-button--danger inline-flex items-center gap-2 rounded-2xl px-4 py-3 text-sm font-semibold">
                    <i class="fas fa-trash-alt" aria-hidden="true"></i>
                    Supprimer
                </button>
            </form>
        </div>
    </div>
</body>
</html>
