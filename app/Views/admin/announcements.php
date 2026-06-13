<?php
$adminPermissions = is_array($adminPermissions ?? null) ? $adminPermissions : [];
$isGeneralAdmin = !empty($isGeneralAdmin);
$errors = is_array($errors ?? null) ? $errors : [];
$announcements = is_array($announcements ?? null) ? $announcements : [];
$announcementEvents = is_array($announcementEvents ?? null) ? $announcementEvents : [];
$successMessage = (string) ($successMessage ?? '');
$totalAnnouncements = (int) ($totalAnnouncements ?? count($announcements));
$totalSent = (int) ($totalSent ?? 0);
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

$adminNavActive = 'announcements';
$adminTopTitle = 'Gestion des annonces';
$adminTopSubtitle = 'Administration';
$adminTopBadge = (string) $totalAnnouncements . ' ' . ($totalAnnouncements === 1 ? 'annonce' : 'annonces');
$adminTopBadgeTone = $totalAnnouncements > 0 ? 'active' : 'done';
$adminTopActions = [
    ['label' => 'Tableau de bord', 'url' => $adminDashboardUrl, 'tone' => 'neutral'],
    ['label' => 'Notifications', 'url' => $notificationsUrl, 'tone' => 'neutral', 'icon' => 'bell'],
];

$fa = static function (string $classes): string {
    return '<i class="' . e(trim($classes)) . '" aria-hidden="true"></i>';
};

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
    'submitted' => 'Soumise à validation',
    'published' => 'Publiée',
    'archived' => 'Archivée',
    'restored' => 'Restaurée',
];

$formatDate = static function (?string $value): string {
    $value = trim((string) $value);
    if ($value === '') {
        return 'Non définie';
    }
    $timestamp = strtotime($value);
    return $timestamp !== false ? date('d/m/Y H:i', $timestamp) : $value;
};
$formatShortDate = static function (?string $value): string {
    $value = trim((string) $value);
    if ($value === '') {
        return 'Non définie';
    }
    $timestamp = strtotime($value);
    return $timestamp !== false ? date('d/m/Y', $timestamp) : $value;
};

$buildPageUrl = static function (int $targetPage) use ($filters): string {
    $params = [];
    foreach (['search' => 'q', 'status' => 'status', 'audience' => 'audience', 'category' => 'category', 'importance' => 'importance'] as $filterKey => $queryKey) {
        $value = trim((string) ($filters[$filterKey] ?? ''));
        if ($value !== '') {
            $params[$queryKey] = $value;
        }
    }
    if ($targetPage > 1) {
        $params['page'] = $targetPage;
    }
    $query = http_build_query($params);
    return site_url('admin/announcements') . ($query !== '' ? '?' . $query : '');
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
        if ($name === 'page' && (int) $value <= 1) {
            continue;
        }
        $value = trim((string) $value);
        if ($value === '') {
            continue;
        }
        $html .= '<input type="hidden" name="' . e((string) $name) . '" value="' . e($value) . '">' . "\n";
    }
    return $html;
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

$displayedPublished = 0;
$displayedDrafts = 0;
$displayedPending = 0;
foreach ($announcements as $announcementStatsRow) {
    $status = (string) ($announcementStatsRow['status'] ?? 'draft');
    $displayedPublished += $status === 'published' ? 1 : 0;
    $displayedDrafts += $status === 'draft' ? 1 : 0;
    $displayedPending += $status === 'pending_review' ? 1 : 0;
}

$old = static function (string $key, string $default = ''): string {
    return (string) ($_POST[$key] ?? $default);
};
$selected = static function (string $current, string $expected): string {
    return $current === $expected ? 'selected' : '';
};
$adminPageScripts = ['admin_announcements.js'];
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
<body class="app-view app-role-admin min-h-screen bg-slate-50 text-slate-900 font-body dark:bg-slate-950 dark:text-slate-100" data-view="admin/announcements" data-sidebar-desktop-toggle="1">
    <div class="admin-shell-backdrop">
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--one"></div>
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--two"></div>
    </div>

    <div class="admin-shell-root relative z-10 flex min-h-screen">
        <?php require __DIR__ . '/_sidebar.php'; ?>
        <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

        <div class="admin-shell-main flex min-w-0 flex-1 flex-col">
            <?php require __DIR__ . '/_topbar.php'; ?>
            <main class="admin-shell-content admin-shell-dense admin-announcements-page px-4 py-5 sm:px-6 sm:py-6 space-y-6">
                <?php if ($errors !== []): ?>
                    <section class="admin-shell-alert admin-shell-alert--error">
                        <?php foreach ($errors as $error): ?>
                            <p class="inline-flex items-center gap-2"><?php echo $fa('fas fa-exclamation-circle'); ?><?php echo e((string) $error); ?></p>
                        <?php endforeach; ?>
                    </section>
                <?php endif; ?>

                <?php if ($successMessage !== ''): ?>
                    <section class="admin-shell-alert admin-shell-alert--success inline-flex items-center gap-2">
                        <?php echo $fa('fas fa-check-circle'); ?>
                        <?php echo e($successMessage); ?>
                    </section>
                <?php endif; ?>

                <?php if ($isCreateView): ?>
                    <section class="admin-announcement-hero rounded-3xl p-5 sm:p-6">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <p class="admin-announcement-eyebrow inline-flex items-center gap-2"><?php echo $fa('fas fa-pen-nib'); ?>Rédaction</p>
                                <h1 class="mt-2 inline-flex items-center gap-3 text-2xl font-display font-semibold text-white sm:text-3xl"><?php echo $fa('fas fa-bullhorn'); ?>Nouvelle annonce</h1>
                                <p class="mt-2 max-w-2xl text-sm font-medium text-blue-50/90">Rédigez un message clair, choisissez l’audience puis publiez ou enregistrez en brouillon.</p>
                            </div>
                            <a href="<?php echo e(site_url('admin/announcements')); ?>" class="admin-announcement-hero-button">
                                <?php echo $fa('fas fa-arrow-left'); ?>
                                Retour aux annonces
                            </a>
                        </div>
                    </section>

                    <section class="admin-announcement-editor rounded-3xl p-5 sm:p-6">
                        <form method="post" action="<?php echo e(site_url('admin/announcements')); ?>" class="grid gap-5" data-announcement-form>
                            <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                            <input type="hidden" name="view" value="create">

                            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_18rem]">
                                <div class="grid gap-4">
                                    <div class="admin-shell-field">
                                        <label for="announcement-title" class="admin-shell-label inline-flex items-center gap-2"><?php echo $fa('fas fa-heading'); ?>Titre</label>
                                        <input id="announcement-title" name="title" type="text" required maxlength="190" class="admin-shell-input" placeholder="Ex : Nouvelle formation disponible" value="<?php echo e($old('title')); ?>">
                                    </div>
                                    <div class="admin-shell-field">
                                        <label for="announcement-summary" class="admin-shell-label inline-flex items-center gap-2"><?php echo $fa('fas fa-align-left'); ?>Résumé</label>
                                        <textarea id="announcement-summary" name="summary" rows="2" maxlength="140" class="admin-shell-input" placeholder="Texte court affiché dans les notifications."><?php echo e($old('summary')); ?></textarea>
                                    </div>
                                    <div class="admin-shell-field">
                                        <label for="announcement-body" class="admin-shell-label inline-flex items-center gap-2"><?php echo $fa('fas fa-comment-alt'); ?>Message</label>
                                        <textarea id="announcement-body" name="body" rows="9" required class="admin-shell-input" placeholder="Rédigez le contenu de l’annonce."><?php echo e($old('body')); ?></textarea>
                                    </div>
                                    <div class="admin-shell-field">
                                        <label for="announcement-action-url" class="admin-shell-label inline-flex items-center gap-2"><?php echo $fa('fas fa-link'); ?>Lien d’action</label>
                                        <input id="announcement-action-url" name="action_url" type="text" class="admin-shell-input" placeholder="catalogue ou https://..." value="<?php echo e($old('action_url')); ?>">
                                    </div>
                                </div>

                                <aside class="admin-announcement-editor-side">
                                    <div class="admin-shell-field">
                                        <label for="announcement-audience" class="admin-shell-label inline-flex items-center gap-2"><?php echo $fa('fas fa-users'); ?>Audience</label>
                                        <select id="announcement-audience" name="audience" class="admin-shell-input">
                                            <?php foreach ($audienceOptions as $value => $label): ?>
                                                <option value="<?php echo e($value); ?>" <?php echo $selected($old('audience', 'all'), $value); ?>><?php echo e($label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="admin-shell-field">
                                        <label for="announcement-category" class="admin-shell-label inline-flex items-center gap-2"><?php echo $fa('fas fa-layer-group'); ?>Type</label>
                                        <select id="announcement-category" name="category" class="admin-shell-input">
                                            <?php foreach ($categoryOptions as $value => $label): ?>
                                                <option value="<?php echo e($value); ?>" <?php echo $selected($old('category', 'general'), $value); ?>><?php echo e($label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="admin-shell-field">
                                        <label for="announcement-importance" class="admin-shell-label inline-flex items-center gap-2"><?php echo $fa('fas fa-exclamation-triangle'); ?>Importance</label>
                                        <select id="announcement-importance" name="importance" class="admin-shell-input">
                                            <?php foreach ($importanceOptions as $value => $label): ?>
                                                <option value="<?php echo e($value); ?>" <?php echo $selected($old('importance', 'info'), $value); ?>><?php echo e($label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="admin-announcement-editor-note">
                                        <?php echo $fa('fas fa-bell'); ?>
                                        <p>La publication envoie une notification aux utilisateurs ciblés.</p>
                                    </div>

                                    <div class="grid gap-2">
                                        <button type="submit" name="action" value="save_draft" class="admin-shell-button admin-shell-button--secondary inline-flex items-center justify-center gap-2">
                                            <?php echo $fa('fas fa-save'); ?>
                                            Enregistrer en brouillon
                                        </button>
                                        <?php if ($isGeneralAdmin): ?>
                                            <button type="submit" name="action" value="publish" class="admin-shell-button admin-shell-button--primary inline-flex items-center justify-center gap-2">
                                                <?php echo $fa('fas fa-paper-plane'); ?>
                                                Publier maintenant
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" name="action" value="submit_review" class="admin-shell-button admin-shell-button--primary inline-flex items-center justify-center gap-2">
                                                <?php echo $fa('fas fa-check-circle'); ?>
                                                Soumettre pour validation
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </aside>
                            </div>
                        </form>
                    </section>
                <?php else: ?>
                    <section class="admin-announcement-hero rounded-3xl p-5 sm:p-6">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <p class="admin-announcement-eyebrow inline-flex items-center gap-2"><?php echo $fa('fas fa-satellite-dish'); ?>Communication</p>
                                <h1 class="mt-2 inline-flex items-center gap-3 text-2xl font-display font-semibold text-white sm:text-3xl"><?php echo $fa('fas fa-bullhorn'); ?>Communication & actualités</h1>
                                <p class="mt-2 max-w-2xl text-sm font-medium text-blue-50/90">Créez, publiez et suivez les annonces visibles dans les notifications de la plateforme.</p>
                            </div>
                            <a href="<?php echo e(site_url('admin/announcements/create')); ?>" class="admin-announcement-hero-button">
                                <?php echo $fa('fas fa-plus-circle'); ?>
                                Nouvelle annonce
                            </a>
                        </div>
                    </section>

                    <section class="grid gap-4 md:grid-cols-4">
                        <article class="admin-announcement-stat admin-announcement-stat--blue">
                            <span class="admin-announcement-stat__icon" aria-hidden="true"><?php echo $fa('fas fa-bullhorn'); ?></span>
                            <p>Total annonces</p>
                            <strong><?php echo e((string) $totalAnnouncements); ?></strong>
                        </article>
                        <article class="admin-announcement-stat admin-announcement-stat--green">
                            <span class="admin-announcement-stat__icon" aria-hidden="true"><?php echo $fa('fas fa-bell'); ?></span>
                            <p>Notifications</p>
                            <strong><?php echo e((string) $totalSent); ?></strong>
                        </article>
                        <article class="admin-announcement-stat admin-announcement-stat--amber">
                            <span class="admin-announcement-stat__icon" aria-hidden="true"><?php echo $fa('fas fa-file-alt'); ?></span>
                            <p>Brouillons affichés</p>
                            <strong><?php echo e((string) $displayedDrafts); ?></strong>
                        </article>
                        <article class="admin-announcement-stat admin-announcement-stat--violet">
                            <span class="admin-announcement-stat__icon" aria-hidden="true"><?php echo $fa('fas fa-check-circle'); ?></span>
                            <p>Publiées affichées</p>
                            <strong><?php echo e((string) $displayedPublished); ?></strong>
                        </article>
                    </section>

                    <section class="admin-announcement-filter-card rounded-3xl p-4 sm:p-5">
                        <form method="get" action="<?php echo e(site_url('admin/announcements')); ?>" class="grid gap-3 xl:grid-cols-[minmax(18rem,1fr)_12rem_12rem_12rem_12rem_auto]">
                            <label class="admin-announcement-search">
                                <?php echo $fa('fas fa-search'); ?>
                                <input type="search" name="q" placeholder="Rechercher une annonce..." value="<?php echo e((string) ($filters['search'] ?? '')); ?>">
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
                            <div class="flex gap-2">
                                <button type="submit" class="admin-shell-button admin-shell-button--primary inline-flex flex-1 items-center justify-center gap-2">
                                    <?php echo $fa('fas fa-filter'); ?>
                                    Filtrer
                                </button>
                                <?php if ($hasActiveFilters): ?>
                                    <a href="<?php echo e(site_url('admin/announcements')); ?>" class="admin-shell-button admin-shell-button--secondary inline-flex items-center justify-center gap-2">
                                        <?php echo $fa('fas fa-undo'); ?>
                                        Réinitialiser
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </section>

                    <section class="admin-announcement-list">
                        <?php if ($announcements === []): ?>
                            <div class="admin-announcement-empty rounded-3xl p-8 text-center">
                                <span class="admin-announcement-empty__icon" aria-hidden="true"><?php echo $fa('fas fa-bullhorn'); ?></span>
                                <h2>Aucune annonce trouvée</h2>
                                <p>Créez une nouvelle annonce ou modifiez les filtres.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($announcements as $announcement): ?>
                                <?php
                                $announcementId = (int) ($announcement['id'] ?? 0);
                                $status = (string) ($announcement['status'] ?? 'draft');
                                $audience = (string) ($announcement['audience'] ?? 'all');
                                $importance = (string) ($announcement['importance'] ?? 'info');
                                $category = (string) ($announcement['category'] ?? 'general');
                                $sentCount = (int) ($announcement['sent_count'] ?? 0);
                                $sentCountB = (int) ($announcement['sent_count_b'] ?? 0);
                                $readCount = (int) ($announcement['read_count'] ?? 0) + (int) ($announcement['read_count_b'] ?? 0);
                                $clickCount = (int) ($announcement['click_count'] ?? 0) + (int) ($announcement['click_count_b'] ?? 0);
                                $readRate = $sentCount > 0 ? round(($readCount / $sentCount) * 100, 1) : 0;
                                $clickRate = $sentCount > 0 ? round(($clickCount / $sentCount) * 100, 1) : 0;
                                $events = $announcementEvents[$announcementId] ?? [];
                                $author = trim((string) (($announcement['admin_first_name'] ?? '') . ' ' . ($announcement['admin_last_name'] ?? '')));
                                if ($author === '') {
                                    $author = 'Administrateur';
                                }
                                $statusLabel = $statusLabels[$status] ?? 'Brouillon';
                                $dateValue = $status === 'published'
                                    ? (string) ($announcement['published_at'] ?? '')
                                    : (string) ($announcement['created_at'] ?? '');
                                $dateLabel = $status === 'published'
                                    ? $formatDate((string) ($announcement['published_at'] ?? ''))
                                    : $formatDate((string) ($announcement['created_at'] ?? ''));
                                $shortDateLabel = $formatShortDate($dateValue);
                                $categoryIcon = $categoryIcons[$category] ?? 'fa-bullhorn';
                                ?>
                                <article class="admin-announcement-card admin-announcement-card--list rounded-3xl">
                                    <div class="admin-announcement-thumb admin-announcement-thumb--<?php echo e($category); ?>">
                                        <span aria-hidden="true"><?php echo $fa('fas ' . $categoryIcon); ?></span>
                                    </div>
                                    <div class="admin-announcement-card__body">
                                        <h2 class="admin-announcement-title inline-flex items-center gap-2">
                                            <?php echo $fa('fas fa-bullhorn'); ?>
                                            <?php echo e((string) ($announcement['title'] ?? 'Annonce')); ?>
                                        </h2>
                                        <div class="mt-2 flex flex-wrap items-center gap-2">
                                            <span class="admin-announcement-pill admin-announcement-pill--category">
                                                <?php echo $fa('fas ' . $categoryIcon); ?>
                                                <?php echo e($categoryOptions[$category] ?? 'Actualité'); ?>
                                            </span>
                                            <span class="admin-announcement-pill admin-announcement-pill--status-<?php echo e($status); ?>">
                                                <?php echo $fa($status === 'published' ? 'fas fa-check-circle' : ($status === 'archived' ? 'fas fa-archive' : ($status === 'pending_review' ? 'fas fa-hourglass-half' : 'fas fa-pen'))); ?>
                                                <?php echo e($statusLabel); ?>
                                            </span>
                                            <span class="admin-announcement-pill admin-announcement-pill--importance-<?php echo e($importance); ?>">
                                                <?php echo $fa($importance === 'urgent' ? 'fas fa-exclamation-triangle' : ($importance === 'important' ? 'fas fa-exclamation-circle' : 'fas fa-info-circle')); ?>
                                                <?php echo e($importanceOptions[$importance] ?? 'Info'); ?>
                                            </span>
                                        </div>
                                        <p class="mt-2 inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                            <?php echo $fa('fas fa-user-shield'); ?>
                                            Par <?php echo e($author); ?>
                                        </p>

                                        <?php if (trim((string) ($announcement['summary'] ?? '')) !== ''): ?>
                                            <p class="admin-announcement-summary inline-flex items-start gap-2">
                                                <?php echo $fa('fas fa-quote-left'); ?>
                                                <span><?php echo e((string) $announcement['summary']); ?></span>
                                            </p>
                                        <?php endif; ?>
                                        <p class="admin-announcement-message">
                                            <span class="admin-announcement-message__icon"><?php echo $fa('fas fa-align-left'); ?></span>
                                            <?php echo e((string) ($announcement['body'] ?? '')); ?>
                                        </p>
                                    </div>

                                    <aside class="admin-announcement-side">
                                        <span class="admin-announcement-date">
                                            <?php echo $fa('far fa-calendar-alt'); ?>
                                            <?php echo e($shortDateLabel); ?>
                                        </span>

                                        <div class="admin-announcement-side-actions">
                                            <?php if (!empty($announcement['action_url'])): ?>
                                                <a class="admin-announcement-card-action admin-announcement-card-action--neutral" href="<?php echo e((string) $announcement['action_url']); ?>">
                                                    <?php echo $fa('fas fa-external-link-alt'); ?>
                                                    Ouvrir
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($status === 'draft'): ?>
                                                <form method="post" action="<?php echo e(site_url('admin/announcements')); ?>">
                                                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                                    <input type="hidden" name="announcement_id" value="<?php echo e((string) $announcementId); ?>">
                                                    <?php echo $renderActionStateInputs($actionState); ?>
                                                    <button type="submit" name="action" value="<?php echo $isGeneralAdmin ? 'publish_existing' : 'submit_draft'; ?>" class="admin-announcement-card-action admin-announcement-card-action--primary">
                                                        <?php echo $fa($isGeneralAdmin ? 'fas fa-paper-plane' : 'fas fa-share-square'); ?>
                                                        <?php echo $isGeneralAdmin ? 'Publier' : 'Soumettre'; ?>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($status === 'pending_review' && $isGeneralAdmin): ?>
                                                <form method="post" action="<?php echo e(site_url('admin/announcements')); ?>">
                                                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                                    <input type="hidden" name="announcement_id" value="<?php echo e((string) $announcementId); ?>">
                                                    <?php echo $renderActionStateInputs($actionState); ?>
                                                    <button type="submit" name="action" value="approve_publish" class="admin-announcement-card-action admin-announcement-card-action--primary">
                                                        <?php echo $fa('fas fa-check-circle'); ?>
                                                        Valider
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($status === 'published'): ?>
                                                <form method="post" action="<?php echo e(site_url('admin/announcements')); ?>">
                                                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                                    <input type="hidden" name="announcement_id" value="<?php echo e((string) $announcementId); ?>">
                                                    <?php echo $renderActionStateInputs($actionState); ?>
                                                    <button type="submit" name="action" value="archive" class="admin-announcement-card-action admin-announcement-card-action--neutral">
                                                        <?php echo $fa('fas fa-archive'); ?>
                                                        Archiver
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($status === 'archived'): ?>
                                                <form method="post" action="<?php echo e(site_url('admin/announcements')); ?>">
                                                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                                                    <input type="hidden" name="announcement_id" value="<?php echo e((string) $announcementId); ?>">
                                                    <?php echo $renderActionStateInputs($actionState); ?>
                                                    <button type="submit" name="action" value="restore" class="admin-announcement-card-action admin-announcement-card-action--neutral">
                                                        <?php echo $fa('fas fa-undo'); ?>
                                                        Restaurer
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($isGeneralAdmin): ?>
                                                <button type="button"
                                                    class="admin-announcement-card-action admin-announcement-card-action--danger"
                                                    data-announcement-delete-id="<?php echo e((string) $announcementId); ?>"
                                                    data-announcement-delete-title="<?php echo e((string) ($announcement['title'] ?? 'Annonce')); ?>">
                                                    <?php echo $fa('fas fa-trash-alt'); ?>
                                                    Supprimer
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </aside>

                                    <?php if (!empty($events)): ?>
                                        <div class="admin-announcement-events-wrap">
                                            <details class="admin-announcement-events">
                                                <summary class="inline-flex items-center gap-2"><?php echo $fa('fas fa-history'); ?>Journal d’envoi</summary>
                                                <ul>
                                                    <?php foreach ($events as $event): ?>
                                                        <?php
                                                        $actor = trim((string) (($event['actor_first_name'] ?? '') . ' ' . ($event['actor_last_name'] ?? '')));
                                                        $actorLabel = $actor !== '' ? $actor : 'Système';
                                                        $eventKey = (string) ($event['action'] ?? '');
                                                        $eventLabel = $eventLabels[$eventKey] ?? ucfirst($eventKey);
                                                        ?>
                                                        <li>
                                                            <?php echo $fa('fas fa-circle'); ?>
                                                            <?php echo e($eventLabel); ?> · <?php echo e($actorLabel); ?> · <?php echo e($formatDate((string) ($event['created_at'] ?? ''))); ?>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </details>
                                        </div>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>

                            <?php if ($totalPages > 1): ?>
                                <nav class="admin-announcement-pagination rounded-2xl" aria-label="Pagination des annonces">
                                    <p class="inline-flex items-center gap-2">
                                        <?php echo $fa('fas fa-list-ol'); ?>
                                        Affichage de <?php echo e((string) $visibleFrom); ?> à <?php echo e((string) $visibleTo); ?> sur <?php echo e((string) $totalAnnouncements); ?> annonce(s)
                                    </p>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="<?php echo e($buildPageUrl(max(1, $page - 1))); ?>" class="admin-shell-button admin-shell-button--secondary inline-flex items-center gap-2 <?php echo $page <= 1 ? 'opacity-50 pointer-events-none' : ''; ?>">
                                            <?php echo $fa('fas fa-chevron-left'); ?>
                                            Précédent
                                        </a>
                                        <?php foreach ($paginationItems as $pageItem): ?>
                                            <?php if ($pageItem === null): ?>
                                                <span class="px-1 text-slate-400 dark:text-slate-500">...</span>
                                            <?php else: ?>
                                                <?php $isActivePage = $pageItem === $page; ?>
                                                <a href="<?php echo e($buildPageUrl((int) $pageItem)); ?>" class="admin-shell-button <?php echo $isActivePage ? 'admin-shell-button--primary' : 'admin-shell-button--secondary'; ?>"><?php echo e((string) $pageItem); ?></a>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                        <a href="<?php echo e($buildPageUrl(min($totalPages, $page + 1))); ?>" class="admin-shell-button admin-shell-button--secondary inline-flex items-center gap-2 <?php echo $page >= $totalPages ? 'opacity-50 pointer-events-none' : ''; ?>">
                                            Suivant
                                            <?php echo $fa('fas fa-chevron-right'); ?>
                                        </a>
                                    </div>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <?php if ($isGeneralAdmin): ?>
        <div id="announcement-delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 backdrop-blur-sm p-4 js-admin-modal" role="dialog" aria-modal="true" aria-labelledby="announcement-delete-modal-title" aria-hidden="true">
            <div class="admin-shell-modal w-full max-w-md rounded-3xl p-6">
                <div class="flex items-start justify-between gap-4">
                    <h3 id="announcement-delete-modal-title" class="text-lg font-semibold text-slate-950 dark:text-white inline-flex items-center gap-2">
                        <span class="admin-inline-accent admin-inline-accent--danger" aria-hidden="true"><?php echo $fa('fas fa-exclamation-triangle'); ?></span>
                        Confirmer la suppression
                    </h3>
                    <button type="button" class="admin-shell-button admin-shell-button--ghost js-modal-close inline-flex items-center gap-1.5" data-modal-target="announcement-delete-modal">
                        <?php echo $fa('fas fa-times'); ?>
                        Fermer
                    </button>
                </div>
                <p id="announcement-delete-message" class="mt-2 text-sm text-slate-600 dark:text-slate-300">Supprimer cette annonce ?</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Cette action est définitive et retire l’annonce de la liste d’administration.</p>
                <form id="announcement-delete-form" method="post" action="<?php echo e(site_url('admin/announcements')); ?>" class="mt-5 flex flex-wrap justify-end gap-3">
                    <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="announcement_id" id="announcement-delete-id" value="">
                    <?php echo $renderActionStateInputs($actionState); ?>
                    <button type="button" class="admin-shell-button admin-shell-button--secondary inline-flex items-center gap-2 js-modal-close" data-modal-target="announcement-delete-modal">
                        <?php echo $fa('fas fa-times'); ?>
                        Annuler
                    </button>
                    <button type="submit" class="admin-shell-button admin-shell-button--danger inline-flex items-center gap-2">
                        <?php echo $fa('fas fa-trash-alt'); ?>
                        Supprimer
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>
