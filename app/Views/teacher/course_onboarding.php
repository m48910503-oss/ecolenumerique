<?php
declare(strict_types=1);

$errors = array_values(is_array($errors ?? null) ? $errors : []);
$title = trim((string) ($title ?? ''));
$description = trim((string) ($description ?? ''));
$categoryId = trim((string) ($categoryId ?? ''));
$pace = trim((string) ($pace ?? 'regular'));
$paceOptions = is_array($paceOptions ?? null) ? $paceOptions : [];
$categories = array_values(is_array($categories ?? null) ? $categories : []);
$initialStep = trim((string) ($initialStep ?? 'welcome'));
$coursesIndexUrl = trim((string) ($coursesIndexUrl ?? site_url('teacher/courses')));
$siteLogoUrl = trim((string) ($siteLogoUrl ?? ''));

$stepMeta = [
    'welcome' => ['badge' => '', 'title' => 'Bienvenue dans la creation de votre cours'],
    'title' => ['badge' => 'Etape 1 sur 4', 'title' => 'Pourquoi ne pas mettre un titre provisoire ?'],
    'description' => ['badge' => 'Etape 2 sur 4', 'title' => 'Decrivez votre cours en quelques phrases'],
    'category' => ['badge' => 'Etape 3 sur 4', 'title' => 'Quelle categorie decrit le mieux votre cours ?'],
    'availability' => ['badge' => 'Etape 4 sur 4', 'title' => 'Combien de temps par semaine pouvez-vous consacrer a la creation de votre cours ?'],
];
if (!isset($stepMeta[$initialStep])) {
    $initialStep = 'welcome';
}
?>
<!doctype html>
<html lang="fr" class="light" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google" content="notranslate">
    <title>Creation du cours · Enseignant · Ecole Numerique</title>
    <?php if ($siteLogoUrl !== ''): ?>
        <link rel="icon" href="<?php echo e($siteLogoUrl); ?>">
        <link rel="apple-touch-icon" href="<?php echo e($siteLogoUrl); ?>">
    <?php endif; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo e(site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()))); ?>"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/teacher-course-builder.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/teacher-course-builder.css') ?: time()))); ?>">
    <script src="<?php echo e(site_url('assets/js/course_onboarding.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/course_onboarding.js') ?: time()))); ?>" defer></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900" data-view="teacher/course_onboarding">
    <div class="min-h-screen">
        <header class="course-onboarding-topbar border-b border-slate-200 bg-white">
            <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-4 py-5 sm:px-6 lg:px-8">
                <a href="<?php echo e($coursesIndexUrl); ?>" class="inline-flex items-center gap-3 text-slate-900">
                    <?php if ($siteLogoUrl !== ''): ?>
                        <img src="<?php echo e($siteLogoUrl); ?>" alt="Logo" class="h-10 w-auto">
                    <?php else: ?>
                        <span class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white"><?php echo site_brand_name_html('Ecole Numerique'); ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?php echo e($coursesIndexUrl); ?>" class="text-sm font-semibold text-blue-600 transition hover:text-blue-700">Quitter</a>
            </div>
            <div class="h-1 w-full bg-slate-100">
                <div data-onboarding-progress-bar class="h-full w-0 bg-blue-600 transition-all duration-300"></div>
            </div>
        </header>

        <main class="course-onboarding-shell mx-auto flex w-full max-w-7xl flex-col px-4 py-10 sm:px-6 lg:px-8">
            <section class="course-onboarding-hero overflow-hidden rounded-[2rem] border border-slate-200 shadow-sm">
                <div class="course-onboarding-hero__grid">
                    <div class="course-onboarding-hero__copy">
                        <span class="course-onboarding-hero__eyebrow">Brouillon intelligent</span>
                        <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl">On lance un vrai point de depart, pas une copie de plateforme.</h1>
                        <p class="mt-4 max-w-3xl text-sm leading-7 text-slate-600">
                            Le parcours garde la logique que vous vouliez, mais il s'appuie ici sur vos categories, vos champs et votre flux enseignant actuel.
                        </p>
                    </div>
                    <div class="course-onboarding-hero__summary">
                        <div class="course-onboarding-mini-card">
                            <p class="course-onboarding-mini-card__label">Titre</p>
                            <p class="course-onboarding-mini-card__value"><?php echo e($title !== '' ? $title : 'A definir'); ?></p>
                        </div>
                        <div class="course-onboarding-mini-card">
                            <p class="course-onboarding-mini-card__label">Categorie</p>
                            <p class="course-onboarding-mini-card__value"><?php echo e($categoryId !== '' ? 'Selection en cours' : 'A choisir'); ?></p>
                        </div>
                        <div class="course-onboarding-mini-card">
                            <p class="course-onboarding-mini-card__label">Rythme</p>
                            <p class="course-onboarding-mini-card__value"><?php echo e((string) ($paceOptions[$pace]['title'] ?? 'Regular')); ?></p>
                        </div>
                    </div>
                </div>
                <div class="course-onboarding-roadmap">
                    <span class="course-onboarding-roadmap__step">01 · Titre</span>
                    <span class="course-onboarding-roadmap__step">02 · Description</span>
                    <span class="course-onboarding-roadmap__step">03 · Categorie</span>
                    <span class="course-onboarding-roadmap__step">04 · Rythme</span>
                </div>
            </section>

            <?php if ($errors !== []): ?>
                <section class="mx-auto mb-6 w-full max-w-3xl rounded-3xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
                    <p class="font-semibold">Certains champs doivent etre corriges.</p>
                    <ul class="mt-2 space-y-1">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo e((string) $error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>

            <form method="post" data-onboarding-root data-initial-step="<?php echo e($initialStep); ?>" class="course-onboarding-flow mx-auto w-full max-w-5xl">
                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">

                <section data-onboarding-step="welcome" class="course-onboarding-step space-y-10">
                    <div class="mx-auto max-w-4xl text-center">
                        <h1 class="text-4xl font-extrabold tracking-tight text-slate-950 sm:text-5xl">Bienvenue dans la creation de votre cours !</h1>
                        <p class="mx-auto mt-5 max-w-3xl text-lg leading-8 text-slate-600">
                            Vous etes sur le point de partager vos connaissances avec des milliers d'apprenants. Nous allons preparer un brouillon simple, puis vous poursuivrez le parcours complet.
                        </p>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <article class="course-onboarding-info-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                            <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-2xl text-blue-600">+</div>
                            <h2 class="text-xl font-bold text-slate-950">Partagez votre expertise</h2>
                            <p class="mt-3 text-sm leading-7 text-slate-600">Votre experience a de la valeur. Vous pourrez commencer simplement, puis enrichir chaque etape ensuite.</p>
                        </article>
                        <article class="course-onboarding-info-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                            <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-2xl text-emerald-600">✓</div>
                            <h2 class="text-xl font-bold text-slate-950">Definissez un cadre clair</h2>
                            <p class="mt-3 text-sm leading-7 text-slate-600">Nous allons d'abord poser le titre, la description, la categorie et votre rythme de production.</p>
                        </article>
                        <article class="course-onboarding-info-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                            <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-2xl text-amber-600">→</div>
                            <h2 class="text-xl font-bold text-slate-950">Construisez ensuite le parcours complet</h2>
                            <p class="mt-3 text-sm leading-7 text-slate-600">Une fois le brouillon cree, vous passerez sur les participants cibles, le programme, les quiz, la tarification et la soumission.</p>
                        </article>
                        <article class="course-onboarding-info-card rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                            <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-fuchsia-50 text-2xl text-fuchsia-600">★</div>
                            <h2 class="text-xl font-bold text-slate-950">Restez sur vos donnees actuelles</h2>
                            <p class="mt-3 text-sm leading-7 text-slate-600">Le formulaire est adapte a votre projet actuel. On reutilise vos champs existants sans imposer ceux de Lambdy.</p>
                        </article>
                    </div>

                    <div class="flex justify-end">
                        <button type="button" data-onboarding-next="title" class="inline-flex items-center justify-center rounded-2xl bg-blue-600 px-6 py-4 text-sm font-semibold text-white transition hover:bg-blue-700">
                            Continuer
                        </button>
                    </div>
                </section>

                <section data-onboarding-step="title" class="course-onboarding-step hidden space-y-8">
                    <div class="text-center">
                        <p data-onboarding-step-badge class="text-sm font-semibold text-slate-500">Etape 1 sur 4</p>
                        <h1 class="mt-4 text-4xl font-extrabold tracking-tight text-slate-950 sm:text-5xl">Pourquoi ne pas mettre un titre provisoire ?</h1>
                        <p class="mx-auto mt-4 max-w-3xl text-lg leading-8 text-slate-600">Si vous n'avez pas encore le titre parfait, ce n'est pas grave. Vous pourrez le modifier plus tard.</p>
                    </div>

                    <div class="course-onboarding-stage-card mx-auto max-w-4xl rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
                        <label for="title" class="mb-3 block text-sm font-semibold text-slate-700">Titre du cours</label>
                        <input
                            id="title"
                            name="title"
                            value="<?php echo e($title); ?>"
                            placeholder="Ex : React js"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-lg font-medium text-slate-900 outline-none transition focus:border-blue-500 focus:bg-white"
                        >
                        <p class="mt-4 text-sm text-slate-500">Un bon titre est clair, descriptif et attire l'attention des futurs apprenants.</p>
                    </div>

                    <div class="course-onboarding-note-card mx-auto max-w-4xl rounded-[2rem] border border-blue-100 bg-blue-50 p-6">
                        <h2 class="text-lg font-bold text-slate-950">Conseils pour un bon titre</h2>
                        <ul class="mt-4 space-y-2 text-sm leading-7 text-slate-600">
                            <li>Incluez le sujet principal et le benefice cle.</li>
                            <li>Soyez specifique plutot que vague.</li>
                            <li>Evitez le jargon inutilement technique.</li>
                            <li>Pensez a ce que rechercheraient vos apprenants.</li>
                        </ul>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <button type="button" data-onboarding-prev="welcome" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-6 py-4 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100">
                            Precedent
                        </button>
                        <button type="button" data-onboarding-next="description" class="inline-flex items-center justify-center rounded-2xl bg-blue-600 px-6 py-4 text-sm font-semibold text-white transition hover:bg-blue-700">
                            Continuer
                        </button>
                    </div>
                </section>

                <section data-onboarding-step="description" class="course-onboarding-step hidden space-y-8">
                    <div class="text-center">
                        <p class="text-sm font-semibold text-slate-500">Etape 2 sur 4</p>
                        <h1 class="mt-4 text-4xl font-extrabold tracking-tight text-slate-950 sm:text-5xl">Decrivez votre cours en quelques phrases</h1>
                        <p class="mx-auto mt-4 max-w-3xl text-lg leading-8 text-slate-600">Expliquez ce que les apprenants vont apprendre et pourquoi ce cours leur est utile.</p>
                    </div>

                    <div class="course-onboarding-stage-card mx-auto max-w-4xl rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
                        <label for="description" class="mb-3 block text-sm font-semibold text-slate-700">Description du cours</label>
                        <textarea
                            id="description"
                            name="description"
                            rows="7"
                            placeholder="Expliquez clairement ce que l'apprenant pourra realiser a la fin du cours."
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-base leading-7 text-slate-900 outline-none transition focus:border-blue-500 focus:bg-white"
                        ><?php echo e($description); ?></textarea>
                        <div class="mt-3 flex items-center justify-between text-sm text-slate-500">
                            <span>Minimum recommande : 40 caracteres.</span>
                            <span data-description-counter><?php echo e((string) (function_exists('mb_strlen') ? mb_strlen($description, 'UTF-8') : strlen($description))); ?></span>
                        </div>
                    </div>

                    <div class="course-onboarding-note-card mx-auto max-w-4xl rounded-[2rem] border border-emerald-100 bg-emerald-50 p-6">
                        <h2 class="text-lg font-bold text-slate-950">Conseils pour une bonne description</h2>
                        <ul class="mt-4 space-y-2 text-sm leading-7 text-slate-600">
                            <li>Expliquez ce que les etudiants sauront faire a la fin.</li>
                            <li>Precisez pour quel niveau le cours est pense.</li>
                            <li>Montrez rapidement l'interet concret du contenu.</li>
                        </ul>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <button type="button" data-onboarding-prev="title" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-6 py-4 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100">
                            Precedent
                        </button>
                        <button type="button" data-onboarding-next="category" class="inline-flex items-center justify-center rounded-2xl bg-blue-600 px-6 py-4 text-sm font-semibold text-white transition hover:bg-blue-700">
                            Continuer
                        </button>
                    </div>
                </section>

                <section data-onboarding-step="category" class="course-onboarding-step hidden space-y-8">
                    <div class="text-center">
                        <p class="text-sm font-semibold text-slate-500">Etape 3 sur 4</p>
                        <h1 class="mt-4 text-4xl font-extrabold tracking-tight text-slate-950 sm:text-5xl">Quelle categorie decrit le mieux votre cours ?</h1>
                        <p class="mx-auto mt-4 max-w-3xl text-lg leading-8 text-slate-600">Choisissez la categorie principale. Elle servira de base pour le brouillon et pourra etre modifiee plus tard.</p>
                    </div>

                    <div class="course-onboarding-stage-card mx-auto max-w-4xl rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
                        <label for="category_id" class="mb-3 block text-sm font-semibold text-slate-700">Categorie du cours</label>
                        <select
                            id="category_id"
                            name="category_id"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-lg font-medium text-slate-900 outline-none transition focus:border-blue-500 focus:bg-white"
                        >
                            <option value="">Selectionnez une categorie</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo e((string) ($category['id'] ?? '')); ?>" <?php echo $categoryId === (string) ($category['id'] ?? '') ? 'selected' : ''; ?>>
                                    <?php echo e((string) ($category['name'] ?? 'Categorie')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="mt-4 text-sm text-slate-500">La categorie guide la creation du brouillon et la future fiche publique.</p>
                    </div>

                    <div class="mx-auto grid max-w-4xl gap-4 md:grid-cols-2">
                        <article class="course-onboarding-note-card rounded-[2rem] border border-fuchsia-100 bg-fuchsia-50 p-6">
                            <h2 class="text-lg font-bold text-slate-950">Meilleure visibilite</h2>
                            <p class="mt-3 text-sm leading-7 text-slate-600">Les apprenants pourront mieux filtrer et retrouver votre cours par domaine.</p>
                        </article>
                        <article class="course-onboarding-note-card rounded-[2rem] border border-emerald-100 bg-emerald-50 p-6">
                            <h2 class="text-lg font-bold text-slate-950">Recommandations ciblees</h2>
                            <p class="mt-3 text-sm leading-7 text-slate-600">Votre cours sera plus facile a rattacher a des parcours et des suggestions pertinentes.</p>
                        </article>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <button type="button" data-onboarding-prev="description" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-6 py-4 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100">
                            Precedent
                        </button>
                        <button type="button" data-onboarding-next="availability" class="inline-flex items-center justify-center rounded-2xl bg-blue-600 px-6 py-4 text-sm font-semibold text-white transition hover:bg-blue-700">
                            Continuer
                        </button>
                    </div>
                </section>

                <section data-onboarding-step="availability" class="course-onboarding-step hidden space-y-8">
                    <div class="text-center">
                        <p class="text-sm font-semibold text-slate-500">Etape 4 sur 4</p>
                        <h1 class="mt-4 text-4xl font-extrabold tracking-tight text-slate-950 sm:text-5xl">Combien de temps par semaine pouvez-vous consacrer a la creation de votre cours ?</h1>
                        <p class="mx-auto mt-4 max-w-3xl text-lg leading-8 text-slate-600">Il n'y a pas de mauvaise reponse. Cette etape sert surtout a calibrer votre depart avant le parcours complet.</p>
                    </div>

                    <div class="course-onboarding-stage-card mx-auto max-w-5xl rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="space-y-4">
                            <?php foreach ($paceOptions as $paceKey => $paceOption): ?>
                                <label class="flex cursor-pointer items-start gap-4 rounded-[1.5rem] border border-slate-200 px-5 py-5 transition hover:border-blue-300 hover:bg-blue-50/60">
                                    <input type="radio" name="pace" value="<?php echo e($paceKey); ?>" <?php echo $pace === $paceKey ? 'checked' : ''; ?> class="mt-1 h-5 w-5 border-slate-300 text-blue-600 focus:ring-blue-600">
                                    <span>
                                        <span class="block text-lg font-semibold text-slate-950"><?php echo e((string) ($paceOption['title'] ?? 'Option')); ?></span>
                                        <span class="mt-1 block text-sm leading-7 text-slate-600"><?php echo e((string) ($paceOption['subtitle'] ?? '')); ?></span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mx-auto grid max-w-5xl gap-4 md:grid-cols-3">
                        <article class="course-onboarding-summary-card rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Titre</p>
                            <p data-summary-title class="mt-3 text-lg font-bold text-slate-950"><?php echo e($title !== '' ? $title : 'Non renseigne'); ?></p>
                        </article>
                        <article class="course-onboarding-summary-card rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Categorie</p>
                            <?php
                            $selectedCategoryLabel = 'Non selectionnee';
                            foreach ($categories as $category) {
                                if ($categoryId === (string) ($category['id'] ?? '')) {
                                    $selectedCategoryLabel = (string) ($category['name'] ?? 'Categorie');
                                    break;
                                }
                            }
                            ?>
                            <p data-summary-category class="mt-3 text-lg font-bold text-slate-950"><?php echo e($selectedCategoryLabel); ?></p>
                        </article>
                        <article class="course-onboarding-summary-card rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Rythme</p>
                            <p data-summary-pace class="mt-3 text-lg font-bold text-slate-950"><?php echo e((string) ($paceOptions[$pace]['title'] ?? 'Non defini')); ?></p>
                        </article>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <button type="button" data-onboarding-prev="category" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-6 py-4 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100">
                            Precedent
                        </button>
                        <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-blue-600 px-6 py-4 text-sm font-semibold text-white transition hover:bg-blue-700">
                            Creer un brouillon
                        </button>
                    </div>
                </section>
            </form>
        </main>
    </div>
</body>
</html>
