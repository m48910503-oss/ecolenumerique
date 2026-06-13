<?php
$errors = is_array($errors ?? null) ? $errors : [];
$success = (string) ($success ?? '');
$categories = is_array($categories ?? null) ? $categories : [];
$isGeneralAdmin = !empty($isGeneralAdmin);

$adminNavActive = 'categories';
$categoryCount = count($categories);
$activeCategoryCount = 0;
$inactiveCategoryCount = 0;
$linkedCourseCount = 0;
foreach ($categories as $categoryRow) {
    $isActiveRow = (int) ($categoryRow['is_deleted'] ?? 0) === 0;
    $activeCategoryCount += $isActiveRow ? 1 : 0;
    $inactiveCategoryCount += $isActiveRow ? 0 : 1;
    $linkedCourseCount += (int) ($categoryRow['courses_count'] ?? 0);
}

$adminTopTitle = 'Gestion des catégories';
$adminTopSubtitle = 'Administration';
$adminTopBadge = (string) $activeCategoryCount . ' ' . ($activeCategoryCount === 1 ? 'catégorie active' : 'catégories actives');
$adminTopBadgeTone = 'active';
$adminTopActions = [
    ['label' => 'Tableau de bord', 'url' => site_url('admin/dashboard'), 'tone' => 'neutral'],
    ['label' => 'Ajouter une catégorie', 'url' => '#category-create-modal', 'tone' => 'primary', 'modal' => 'category-create-modal'],
];
if ($isGeneralAdmin) {
    $adminTopActions[] = ['label' => 'Admins secondaires', 'url' => site_url('admin/secondary_admins'), 'tone' => 'neutral'];
}

$icon = static function (string $name): string {
    return function_exists('dashboard_icon_svg') ? (string) dashboard_icon_svg($name) : '';
};
$formatDate = static function (?string $date): string {
    $date = trim((string) $date);
    if ($date === '') {
        return '-';
    }

    $timestamp = strtotime($date);
    return $timestamp !== false ? date('d/m/Y H:i', $timestamp) : '-';
};
$entryLabel = $categoryCount === 1 ? 'entrée' : 'entrées';

$modalToAutoOpen = '';
$editModalPayload = [
    'id' => '',
    'name' => '',
    'slug' => '',
    'active' => '1',
];
$createModalPayload = [
    'name' => '',
    'slug' => '',
    'active' => '1',
];
if ($errors !== [] && strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
    $postedAction = (string) ($_POST['action'] ?? '');
    if ($postedAction === 'create') {
        $modalToAutoOpen = 'category-create-modal';
        $createModalPayload = [
            'name' => (string) ($_POST['name'] ?? ''),
            'slug' => (string) ($_POST['slug'] ?? ''),
            'active' => in_array((string) ($_POST['active'] ?? '0'), ['1', 'on', 'yes', 'true'], true) ? '1' : '0',
        ];
    } elseif ($postedAction === 'update') {
        $modalToAutoOpen = 'category-edit-modal';
        $editModalPayload = [
            'id' => (string) ($_POST['id'] ?? ''),
            'name' => (string) ($_POST['name'] ?? ''),
            'slug' => (string) ($_POST['slug'] ?? ''),
            'active' => in_array((string) ($_POST['active'] ?? '0'), ['1', 'on', 'yes', 'true'], true) ? '1' : '0',
        ];
    }
}
$adminPageScripts = ['admin_categories.js'];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des catégories - Administrateur</title>
    <?php require __DIR__ . '/_head_assets.php'; ?>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="app-view app-role-admin min-h-screen bg-slate-50 text-slate-900 font-body dark:bg-slate-950 dark:text-slate-100"
    data-view="admin/categories"
    data-sidebar-desktop-toggle="1"
    data-category-open-modal="<?php echo e($modalToAutoOpen); ?>"
    data-category-edit-id="<?php echo e($editModalPayload['id']); ?>"
    data-category-edit-name="<?php echo e($editModalPayload['name']); ?>"
    data-category-edit-slug="<?php echo e($editModalPayload['slug']); ?>"
    data-category-edit-active="<?php echo e($editModalPayload['active']); ?>"
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
            <main class="admin-shell-content admin-shell-dense admin-categories-page px-4 py-5 sm:px-6 sm:py-6 space-y-6">
                <?php if ($errors): ?>
                    <div class="admin-shell-alert admin-shell-alert--error">
                        <?php foreach ($errors as $err): ?>
                            <p>- <?php echo e((string) $err); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success !== ''): ?>
                    <div class="admin-shell-alert admin-shell-alert--success">
                        <?php echo e($success); ?>
                    </div>
                <?php endif; ?>

                <section class="admin-categories-hero rounded-3xl p-5 sm:p-6">
                    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                        <div class="max-w-3xl">
                            <p class="admin-categories-eyebrow inline-flex items-center gap-2"><i class="fas fa-sitemap" aria-hidden="true"></i>Structure du catalogue</p>
                            <h1 class="mt-2 inline-flex items-center gap-3 text-2xl font-display font-semibold text-white sm:text-3xl"><i class="fas fa-tags" aria-hidden="true"></i>Organisation des catégories</h1>
                            <p class="mt-2 max-w-2xl text-sm font-medium text-blue-50/90">Classez les cours et gérez leur visibilité.</p>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <span class="admin-categories-hero-pill">
                                    <i class="fas fa-check-circle" aria-hidden="true"></i>
                                    <?php echo e((string) $activeCategoryCount); ?> actives
                                </span>
                                <span class="admin-categories-hero-pill">
                                    <i class="fas fa-folder-open" aria-hidden="true"></i>
                                    <?php echo e((string) $linkedCourseCount); ?> cours liés
                                </span>
                            </div>
                        </div>
                        <button class="admin-categories-new-button js-modal-open" type="button" data-modal-target="category-create-modal">
                            <i class="fas fa-plus-circle" aria-hidden="true"></i>
                            Nouvelle catégorie
                        </button>
                    </div>
                </section>

                <section class="grid gap-4 md:grid-cols-3">
                    <article class="admin-category-stat admin-category-stat--blue">
                        <span class="admin-category-stat__icon" aria-hidden="true"><i class="fas fa-layer-group"></i></span>
                        <p class="admin-category-stat__label">Total</p>
                        <p class="admin-category-stat__value"><?php echo e((string) $categoryCount); ?></p>
                    </article>
                    <article class="admin-category-stat admin-category-stat--green">
                        <span class="admin-category-stat__icon" aria-hidden="true"><i class="fas fa-check-circle"></i></span>
                        <p class="admin-category-stat__label">Actives</p>
                        <p class="admin-category-stat__value"><?php echo e((string) $activeCategoryCount); ?></p>
                    </article>
                    <article class="admin-category-stat admin-category-stat--slate">
                        <span class="admin-category-stat__icon" aria-hidden="true"><i class="fas fa-eye-slash"></i></span>
                        <p class="admin-category-stat__label">Inactives</p>
                        <p class="admin-category-stat__value"><?php echo e((string) $inactiveCategoryCount); ?></p>
                    </article>
                </section>

                <section class="admin-category-simple">
                    <div class="admin-category-simple__scroll">
                        <table class="admin-category-simple-table">
                            <thead>
                                <tr>
                                    <th>Ordre</th>
                                    <th>Nom</th>
                                    <th>Statut</th>
                                    <th>Cours liés</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $cat): ?>
                                    <?php
                                    $catId = (int) ($cat['id'] ?? 0);
                                    $catName = (string) ($cat['name'] ?? '');
                                    $catSlug = (string) ($cat['slug'] ?? '');
                                    $catCourses = (int) ($cat['courses_count'] ?? 0);
                                    $isActive = (int) ($cat['is_deleted'] ?? 0) === 0;
                                    $statusValue = $isActive ? 'active' : 'inactive';
                                    ?>
                                    <tr data-category-row
                                        data-category-name="<?php echo e($catName); ?>"
                                        data-category-slug="<?php echo e($catSlug); ?>"
                                        data-category-status="<?php echo e($statusValue); ?>">
                                        <td>
                                            <span class="admin-category-order" aria-hidden="true">
                                                <i class="fas fa-grip-vertical"></i>
                                            </span>
                                        </td>
                                        <td>
                                            <strong class="admin-category-simple-name"><?php echo e($catName); ?></strong>
                                        </td>
                                        <td>
                                            <span class="admin-category-simple-status admin-category-simple-status--<?php echo e($statusValue); ?>">
                                                <i class="fas <?php echo $isActive ? 'fa-check-circle' : 'fa-eye-slash'; ?>" aria-hidden="true"></i>
                                                <?php echo $isActive ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="admin-category-simple-count"><?php echo e((string) $catCourses); ?> cours</span>
                                        </td>
                                        <td>
                                            <div class="admin-category-simple-actions">
                                                <button class="admin-category-simple-icon admin-category-simple-icon--edit js-category-edit"
                                                    type="button"
                                                    aria-label="Modifier <?php echo e($catName); ?>"
                                                    data-edit-id="<?php echo e((string) $catId); ?>"
                                                    data-edit-name="<?php echo e($catName); ?>"
                                                    data-edit-slug="<?php echo e($catSlug); ?>"
                                                    data-edit-active="<?php echo $isActive ? '1' : '0'; ?>">
                                                    <i class="fas fa-edit" aria-hidden="true"></i>
                                                </button>
                                                <?php if ($catCourses > 0): ?>
                                                    <button class="admin-category-simple-icon admin-category-simple-icon--disabled"
                                                        type="button"
                                                        disabled
                                                        aria-label="Suppression indisponible pour <?php echo e($catName); ?>"
                                                        title="Cette catégorie contient des cours.">
                                                        <i class="fas fa-lock" aria-hidden="true"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="admin-category-simple-icon admin-category-simple-icon--delete"
                                                        type="button"
                                                        aria-label="Supprimer <?php echo e($catName); ?>"
                                                        data-delete-id="<?php echo e((string) $catId); ?>"
                                                        data-delete-name="<?php echo e($catName); ?>">
                                                        <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div id="admin-category-empty" class="admin-category-empty <?php echo $categoryCount > 0 ? 'hidden' : ''; ?>">
                        <i class="fas fa-folder-open" aria-hidden="true"></i>
                        <span>Aucune catégorie trouvée.</span>
                    </div>
                </section>

            </main>
        </div>
    </div>

    <div id="category-create-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm js-admin-modal" role="dialog" aria-modal="true" aria-labelledby="category-create-modal-title">
        <div class="admin-shell-modal w-full max-w-xl rounded-3xl p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="admin-shell-section-title inline-flex items-center gap-2"><i class="fas fa-plus-circle" aria-hidden="true"></i>Nouvelle catégorie</p>
                    <h3 id="category-create-modal-title" class="mt-2 text-lg font-semibold text-slate-950 dark:text-white inline-flex items-center gap-2">
                        <i class="fas fa-folder-plus admin-inline-accent admin-inline-accent--info" aria-hidden="true"></i>
                        Ajouter une catégorie
                    </h3>
                </div>
                <button type="button" class="admin-shell-button admin-shell-button--ghost js-modal-close inline-flex items-center gap-1.5" data-modal-target="category-create-modal">
                    <i class="fas fa-times" aria-hidden="true"></i>
                    Fermer
                </button>
            </div>

            <form class="mt-5 admin-shell-form-grid" method="post" action="<?php echo e(site_url('admin/categories')); ?>">
                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="active" value="0">

                <div class="admin-shell-field">
                    <label class="admin-shell-label inline-flex items-center gap-2" for="category-create-name"><i class="fas fa-tag" aria-hidden="true"></i>Nom</label>
                    <input id="category-create-name" name="name" type="text" placeholder="Ex : Développement web" required class="admin-shell-input" value="<?php echo e($createModalPayload['name']); ?>">
                </div>

                <div class="admin-shell-field">
                    <label class="admin-shell-label inline-flex items-center gap-2" for="category-create-slug"><i class="fas fa-link" aria-hidden="true"></i>Slug</label>
                    <input id="category-create-slug" name="slug" type="text" placeholder="Auto si laissé vide" class="admin-shell-input" value="<?php echo e($createModalPayload['slug']); ?>">
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Le slug est généré automatiquement si le champ reste vide.</p>
                </div>

                <label class="admin-category-toggle">
                    <input id="category-create-active" name="active" type="checkbox" value="1" <?php echo $createModalPayload['active'] === '1' ? 'checked' : ''; ?>>
                    <span>
                        <strong>Catégorie active</strong>
                        <small>Visible dans le catalogue public.</small>
                    </span>
                </label>

                <div class="mt-1 flex flex-wrap items-center justify-end gap-2">
                    <button type="button" class="admin-shell-button admin-shell-button--secondary js-modal-close inline-flex items-center gap-2" data-modal-target="category-create-modal"><i class="fas fa-times" aria-hidden="true"></i>Annuler</button>
                    <button type="submit" class="admin-shell-button admin-shell-button--primary inline-flex items-center gap-2">
                        <i class="fas fa-plus-circle" aria-hidden="true"></i>
                        Créer la catégorie
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="category-edit-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm js-admin-modal" role="dialog" aria-modal="true" aria-labelledby="category-edit-modal-title">
        <div class="admin-shell-modal w-full max-w-xl rounded-3xl p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="admin-shell-section-title inline-flex items-center gap-2"><i class="fas fa-edit" aria-hidden="true"></i>Modification</p>
                    <h3 id="category-edit-modal-title" class="mt-2 text-lg font-semibold text-slate-950 dark:text-white inline-flex items-center gap-2">
                        <i class="fas fa-pen admin-inline-accent admin-inline-accent--info" aria-hidden="true"></i>
                        Modifier la catégorie
                    </h3>
                </div>
                <button type="button" class="admin-shell-button admin-shell-button--ghost js-modal-close inline-flex items-center gap-1.5" data-modal-target="category-edit-modal">
                    <i class="fas fa-times" aria-hidden="true"></i>
                    Fermer
                </button>
            </div>

            <form class="mt-5 admin-shell-form-grid" method="post" action="<?php echo e(site_url('admin/categories')); ?>">
                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="category-edit-id" value="<?php echo e($editModalPayload['id']); ?>">
                <input type="hidden" name="active" value="0">

                <div class="admin-shell-field">
                    <label class="admin-shell-label inline-flex items-center gap-2" for="category-edit-name"><i class="fas fa-tag" aria-hidden="true"></i>Nom</label>
                    <input id="category-edit-name" name="name" type="text" required class="admin-shell-input" value="<?php echo e($editModalPayload['name']); ?>">
                </div>

                <div class="admin-shell-field">
                    <label class="admin-shell-label inline-flex items-center gap-2" for="category-edit-slug"><i class="fas fa-link" aria-hidden="true"></i>Slug</label>
                    <input id="category-edit-slug" name="slug" type="text" required class="admin-shell-input" value="<?php echo e($editModalPayload['slug']); ?>">
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Le slug doit rester unique.</p>
                </div>

                <label class="admin-category-toggle">
                    <input id="category-edit-active" name="active" type="checkbox" value="1" <?php echo $editModalPayload['active'] === '1' ? 'checked' : ''; ?>>
                    <span>
                        <strong>Catégorie active</strong>
                        <small>Décochez pour masquer la catégorie sans perdre l’historique.</small>
                    </span>
                </label>

                <div class="mt-1 flex flex-wrap items-center justify-end gap-2">
                    <button type="button" class="admin-shell-button admin-shell-button--secondary js-modal-close inline-flex items-center gap-2" data-modal-target="category-edit-modal"><i class="fas fa-times" aria-hidden="true"></i>Annuler</button>
                    <button type="submit" class="admin-shell-button admin-shell-button--primary inline-flex items-center gap-2">
                        <i class="fas fa-save" aria-hidden="true"></i>
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm js-admin-modal" role="dialog" aria-modal="true" aria-labelledby="delete-modal-title">
        <div class="admin-shell-modal w-full max-w-md rounded-3xl p-6">
            <h3 id="delete-modal-title" class="text-lg font-semibold text-slate-950 dark:text-white inline-flex items-center gap-2">
                <i class="fas fa-exclamation-triangle admin-inline-accent admin-inline-accent--danger" aria-hidden="true"></i>
                Confirmer la suppression
            </h3>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300" id="delete-message">Supprimer cette catégorie ?</p>
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Seules les catégories sans cours lié peuvent être supprimées.</p>
            <form id="delete-form" method="post" action="<?php echo e(site_url('admin/categories')); ?>" class="mt-5 flex justify-end gap-3">
                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete-id" value="">
                <button type="button" class="admin-shell-button admin-shell-button--secondary inline-flex items-center gap-2 js-modal-close" data-modal-target="delete-modal">
                    <i class="fas fa-times" aria-hidden="true"></i>
                    Annuler
                </button>
                <button type="submit" class="admin-shell-button admin-shell-button--danger inline-flex items-center gap-2">
                    <i class="fas fa-trash-alt" aria-hidden="true"></i>
                    Supprimer
                </button>
            </form>
        </div>
    </div>
</body>
</html>
