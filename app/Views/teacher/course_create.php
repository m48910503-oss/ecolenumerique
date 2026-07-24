<?php
declare(strict_types=1);

$categories = array_values(is_array($categories ?? null) ? $categories : []);
$errors = array_values(is_array($errors ?? null) ? $errors : []);
$successMessage = trim((string) ($successMessage ?? ''));
$createdCourseId = max(0, (int) ($createdCourseId ?? 0));
$title = trim((string) ($title ?? ''));
$categoryId = trim((string) ($categoryId ?? ''));
$level = trim((string) ($level ?? 'beginner'));
$level = in_array($level, ['beginner', 'intermediate', 'advanced'], true) ? $level : 'beginner';
$description = trim((string) ($description ?? ''));
$objectives = array_values(is_array($objectives ?? null) ? $objectives : []);
$prerequisites = array_values(is_array($prerequisites ?? null) ? $prerequisites : []);
$equipment = array_values(is_array($equipment ?? null) ? $equipment : []);
$durationHours = trim((string) ($durationHours ?? ''));
$priceXof = trim((string) ($priceXof ?? ''));
$enterprisePriceXof = trim((string) ($enterprisePriceXof ?? ''));
$enterpriseDiscountPercent = trim((string) ($enterpriseDiscountPercent ?? ''));
$referralOverride = !empty($referralOverride);
$referralL2Enabled = !empty($referralL2Enabled);
$contentBuilderPayload = trim((string) ($contentBuilderPayload ?? ''));
$examBuilderPayload = trim((string) ($examBuilderPayload ?? ''));
$coverPathExisting = trim((string) ($coverPathExisting ?? ''));
$teacherId = max(0, (int) ($teacherId ?? 0));
$editMode = !empty($editMode);
$courseId = max(0, (int) ($courseId ?? 0));
$updatedCourseId = max(0, (int) ($updatedCourseId ?? 0));
$courseStatus = strtolower(trim((string) (($course['status'] ?? 'draft'))));
$teacherUser = is_array($user ?? null) ? $user : [];
$formatCountLabel = static function (int $count, string $singular, ?string $plural = null): string {
    if ($count <= 0) {
        return 'Aucun ' . $singular;
    }

    return $count . ' ' . ($count === 1 ? $singular : ($plural ?? ($singular . 's')));
};
$draftLevelLabels = [
    'beginner' => 'Débutant',
    'intermediate' => 'Intermédiaire',
    'advanced' => 'Avancé',
];
$formatDraftDate = static function ($value): string {
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    try {
        $date = is_numeric($value)
            ? new \DateTimeImmutable('@' . (int) $value)
            : new \DateTimeImmutable($value);
        $date = $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        return $date->format('d/m/Y à H:i');
    } catch (\Throwable $e) {
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return '';
        }

        return date('d/m/Y à H:i', $timestamp);
    }
};
$teacherDisplayName = function_exists('dashboard_user_display_name')
    ? dashboard_user_display_name($teacherUser, 'Enseignant')
    : trim((string) (($teacherUser['first_name'] ?? '') . ' ' . ($teacherUser['last_name'] ?? '')));
$teacherDisplayName = $teacherDisplayName !== '' ? $teacherDisplayName : 'Enseignant';
$teacherEmail = trim((string) ($teacherUser['email'] ?? ''));
$teacherAvatarUrl = function_exists('dashboard_user_avatar_url') ? dashboard_user_avatar_url($teacherUser) : '';
$teacherInitials = function_exists('dashboard_user_initials')
    ? dashboard_user_initials($teacherUser, 'EN')
    : strtoupper(substr($teacherDisplayName, 0, 2));
$csrfToken = function_exists('view_csrf_token') ? view_csrf_token() : '';
$driveReady = !empty($driveReady);
$driveRemoteReady = !empty($driveRemoteReady);
$driveLocalReady = !empty($driveLocalReady);
$driveConnectRecommended = !empty($driveConnectRecommended);
$driveUploadNote = trim((string) ($driveUploadNote ?? ''));
$driveMaxMb = max(1, (int) ($driveMaxMb ?? 25));
$platformReferral = is_array($platformReferral ?? null) ? $platformReferral : ['l1_rate' => 0, 'l2_rate' => 0];
$teacherDraftCourses = array_values(is_array($teacherDraftCourses ?? null) ? $teacherDraftCourses : []);
$generalFileCacheName = trim((string) ($generalFileCacheName ?? ''));
$generalFileCacheSize = max(0, (int) ($generalFileCacheSize ?? 0));

$coverExistingUrl = $coverPathExisting !== '' ? site_url(ltrim($coverPathExisting, '/')) : '';
$courseCoverUrl = $coverExistingUrl;
$formAction = $editMode && $courseId > 0 ? site_url('teacher/course_edit?id=' . $courseId) : site_url('teacher/course_create');
$autosaveKey = $editMode && $courseId > 0 ? 'course_edit_' . $courseId : 'course_create_' . $teacherId;
$autosaveClear = ($createdCourseId > 0 || $updatedCourseId > 0) ? '1' : '0';
$draftPromptEnabled = !$editMode && $successMessage === '';
$pageTitle = $editMode ? 'Modifier le cours complet' : 'Créer un cours complet';
$pageSubtitle = $editMode
    ? 'Modifiez les informations, le contenu et les évaluations depuis le même formulaire.'
    : 'Un seul brouillon pour les informations, le contenu et les évaluations.';
$draftButtonLabel = $editMode ? 'Enregistrer' : 'Brouillon';
$submitButtonLabel = $editMode
    ? ($courseStatus === 'approved' ? 'Enregistrer les modifications' : 'Soumettre les modifications')
    : 'Publier le cours';
$generalFileRemoveLabel = $editMode ? 'Retirer cette ressource' : 'Retirer cette ressource temporaire';
$teacherNavActive = $editMode ? 'courses' : 'course_create';
$teacherTopTitle = $pageTitle;
$teacherTopSubtitle = 'Cours, contenu et examens';
$showCatalogue = false;
$showSupportFab = true;
$driveStatusLabel = $driveRemoteReady ? 'Drive prêt' : ($driveLocalReady ? 'Stockage local' : 'À vérifier');
$driveStatusClass = $driveRemoteReady
    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'
    : ($driveLocalReady
        ? 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300'
        : 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300');
$enterprisePricingMode = 'none';
if ($enterprisePriceXof !== '') {
    $enterprisePricingMode = 'price';
} elseif ($enterpriseDiscountPercent !== '') {
    $enterprisePricingMode = 'discount';
}

$formatBytes = static function (int $bytes): string {
    if ($bytes <= 0) {
        return '0 Ko';
    }
    if ($bytes >= 1024 * 1024) {
        return number_format($bytes / (1024 * 1024), 2, ',', ' ') . ' Mo';
    }
    return number_format($bytes / 1024, 0, ',', ' ') . ' Ko';
};

$listValues = static function (array $items): array {
    $clean = [];
    foreach ($items as $item) {
        $value = trim((string) $item);
        if ($value !== '') {
            $clean[] = $value;
        }
    }
    return $clean !== [] ? $clean : [''];
};

$countFilledList = static function (array $items): int {
    $count = 0;
    foreach ($items as $item) {
        if (trim((string) $item) !== '') {
            $count++;
        }
    }
    return $count;
};

$requiredChecks = [
    $title !== '',
    $level !== '',
    $description !== '',
    $priceXof !== '',
    $categoryId !== '',
    $coverPathExisting !== '',
];
$requiredDone = count(array_filter($requiredChecks));
$requiredTotal = count($requiredChecks);
$requiredRemaining = max(0, $requiredTotal - $requiredDone);
$draftProgressPct = $requiredTotal > 0 ? (int) round(($requiredDone / $requiredTotal) * 100) : 0;
$pedagogyCount = $countFilledList($objectives) + $countFilledList($prerequisites) + $countFilledList($equipment);
$pedagogyOpen = $pedagogyCount > 0;
$commercialReady = $priceXof !== '' && $categoryId !== '' && $coverPathExisting !== '';

$decodeBuilderCollection = static function (string $payload, string $rootKey): array {
    $decoded = json_decode($payload, true);
    if (!is_array($decoded)) {
        return [];
    }
    $items = $decoded[$rootKey] ?? [];
    return array_values(is_array($items) ? $items : []);
};

$draftModules = $decodeBuilderCollection($contentBuilderPayload, 'modules');
$draftModuleCount = count($draftModules);
$draftLessonCount = 0;
$draftMeetingCount = 0;
foreach ($draftModules as $moduleDraft) {
    $items = array_values(is_array($moduleDraft['items'] ?? null) ? $moduleDraft['items'] : []);
    $draftLessonCount += count($items);
    foreach ($items as $itemDraft) {
        if (trim((string) ($itemDraft['type'] ?? '')) === 'meeting') {
            $draftMeetingCount++;
        }
    }
}

$draftExams = $decodeBuilderCollection($examBuilderPayload, 'exams');
$draftExamCount = count($draftExams);
$presentationDone = count(array_filter([
    $title !== '',
    $level !== '',
    $description !== '',
]));
$commercialDone = count(array_filter([
    $coverPathExisting !== '',
    $priceXof !== '',
    $categoryId !== '',
]));
$normalizeTone = static function (string $tone): string {
    $tone = strtolower(trim($tone));
    return in_array($tone, ['blue', 'indigo', 'amber', 'emerald', 'violet', 'rose'], true) ? $tone : 'blue';
};
$journeyCards = [
    [
        'step' => 1,
        'title' => 'Présentation',
        'icon' => 'fa-compass',
        'complete' => $presentationDone === 3,
        'state' => $presentationDone === 3 ? 'Prêt' : 'À faire',
    ],
    [
        'step' => 2,
        'title' => 'Offre & médias',
        'icon' => 'fa-store',
        'complete' => $commercialReady,
        'state' => $commercialReady ? 'Prêt' : 'À faire',
    ],
    [
        'step' => 3,
        'title' => 'Contenu',
        'icon' => 'fa-layer-group',
        'complete' => $draftModuleCount > 0,
        'state' => $formatCountLabel($draftModuleCount, 'module', 'modules'),
    ],
    [
        'step' => 4,
        'title' => 'Évaluation',
        'icon' => 'fa-clipboard-check',
        'complete' => $draftExamCount > 0,
        'state' => $formatCountLabel($draftExamCount, 'examen', 'examens'),
    ],
];

$phaseIntroductions = [
    1 => [
        'eyebrow' => 'Étape 1',
        'title' => 'Posez une promesse de cours claire',
        'icon' => 'fa-compass',
        'tone' => 'blue',
        'copy' => 'Dites en une phrase le sujet, le niveau et le résultat attendu.',
        'focus' => 'Le but est simple : faire comprendre le cours en quelques secondes.',
        'stats' => [
            ['label' => 'Base', 'value' => $presentationDone . '/3 remplis'],
            ['label' => 'Pédagogie', 'value' => $pedagogyCount > 0 ? $pedagogyCount . ' repères' : 'À préciser'],
            ['label' => 'Objectif', 'value' => 'Donner envie'],
        ],
        'tips' => [
            ['icon' => 'fa-eye', 'tone' => 'blue', 'title' => 'Visible', 'copy' => 'Titre, niveau et description font la première impression.'],
            ['icon' => 'fa-bullseye', 'tone' => 'amber', 'title' => 'Résultat', 'copy' => 'Les objectifs disent ce que l’apprenant saura faire.'],
            ['icon' => 'fa-toolbox', 'tone' => 'emerald', 'title' => 'Préparation', 'copy' => 'Gardez seulement le nécessaire pour démarrer.'],
        ],
        'fieldLead' => 'Les cartes ci-dessous résument chaque champ.',
        'fieldCopy' => 'Commencez par le visible, puis ajoutez le pédagogique.',
        'fields' => [
            ['label' => 'Titre', 'help' => 'Annoncez le sujet et la promesse.', 'detail' => 'Exemple : Excel débutant.', 'icon' => 'fa-heading', 'tone' => 'blue', 'badge' => 'Public'],
            ['label' => 'Niveau', 'help' => 'Choisissez le vrai niveau visé.', 'detail' => 'Débutant, intermédiaire ou avancé.', 'icon' => 'fa-signal', 'tone' => 'indigo', 'badge' => 'Orientation'],
            ['label' => 'Description', 'help' => 'Résumez le bénéfice du cours.', 'detail' => 'Dites d’abord ce qu’on apprend.', 'icon' => 'fa-align-left', 'tone' => 'violet', 'badge' => 'Essentiel'],
            ['label' => 'Objectifs', 'help' => 'Listez ce que l’apprenant saura faire.', 'detail' => 'Verbes d’action : créer, analyser, présenter.', 'icon' => 'fa-bullseye', 'tone' => 'amber', 'badge' => 'Pédagogie'],
            ['label' => 'Prérequis', 'help' => 'Indiquez le minimum nécessaire.', 'detail' => 'Uniquement l’essentiel.', 'icon' => 'fa-list-check', 'tone' => 'rose', 'badge' => 'Préparation'],
            ['label' => 'Équipement', 'help' => 'Citez les outils utiles.', 'detail' => 'Ordinateur, logiciel, connexion.', 'icon' => 'fa-toolbox', 'tone' => 'emerald', 'badge' => 'Matériel'],
        ],
        'cta' => 'Commencer',
    ],
    2 => [
        'eyebrow' => 'Étape 2',
        'title' => 'Présentez le cours comme une vraie offre',
        'icon' => 'fa-store',
        'tone' => 'rose',
        'copy' => 'Ajoutez le visuel, le prix et la catégorie.',
        'focus' => 'Cette étape rend la fiche claire dans le catalogue.',
        'stats' => [
            ['label' => 'Offre', 'value' => $commercialDone . '/3 prêts'],
            ['label' => 'Stockage', 'value' => $driveStatusLabel],
            ['label' => 'Objectif', 'value' => 'Mettre en avant'],
        ],
        'tips' => [
            ['icon' => 'fa-image', 'tone' => 'rose', 'title' => 'Visuel', 'copy' => 'Une miniature nette suffit.'],
            ['icon' => 'fa-coins', 'tone' => 'amber', 'title' => 'Prix', 'copy' => 'Gardez un tarif cohérent.'],
            ['icon' => 'fa-folder-tree', 'tone' => 'indigo', 'title' => 'Catégorie', 'copy' => 'Choisissez le bon classement.'],
        ],
        'fieldLead' => 'Ces champs structurent la fiche publique.',
        'fieldCopy' => 'Gardez l’essentiel, sans trop charger.',
        'fields' => [
            ['label' => 'Miniature', 'help' => 'Image visible dans le catalogue.', 'detail' => 'Simple, nette, contrastée.', 'icon' => 'fa-image', 'tone' => 'rose', 'badge' => 'Catalogue'],
            ['label' => 'Prix public', 'help' => 'Tarif pour un apprenant.', 'detail' => 'Choisissez un montant lisible.', 'icon' => 'fa-coins', 'tone' => 'amber', 'badge' => 'Obligatoire'],
            ['label' => 'Offre entreprise', 'help' => 'Prix ou remise dédiée.', 'detail' => 'Ne gardez qu’un seul mode.', 'icon' => 'fa-building', 'tone' => 'indigo', 'badge' => 'Optionnel'],
            ['label' => 'Catégorie', 'help' => 'Aide au classement.', 'detail' => 'Prenez le sujet le plus proche.', 'icon' => 'fa-folder-tree', 'tone' => 'blue', 'badge' => 'Visibilité'],
            ['label' => 'Parrainage', 'help' => 'Activez si utile.', 'detail' => 'Sinon, laissez désactivé.', 'icon' => 'fa-share-nodes', 'tone' => 'emerald', 'badge' => 'Optionnel'],
            ['label' => 'Ressource générale', 'help' => 'Document partagé à tous.', 'detail' => 'Guide, feuille de route, PDF.', 'icon' => 'fa-file-arrow-up', 'tone' => 'violet', 'badge' => 'Bonus'],
        ],
        'cta' => 'Continuer',
    ],
    3 => [
        'eyebrow' => 'Étape 3',
        'title' => 'Construisez un parcours simple à suivre',
        'icon' => 'fa-layer-group',
        'tone' => 'indigo',
        'copy' => 'Structurez le parcours en modules et leçons.',
        'focus' => 'Une bonne structure aide l’apprenant à avancer sans se perdre.',
        'stats' => [
            ['label' => 'Modules', 'value' => $formatCountLabel($draftModuleCount, 'module', 'modules')],
            ['label' => 'Leçons', 'value' => $formatCountLabel($draftLessonCount, 'leçon', 'leçons')],
            ['label' => 'Objectif', 'value' => 'Progression claire'],
        ],
        'tips' => [
            ['icon' => 'fa-layer-group', 'tone' => 'indigo', 'title' => 'Modules', 'copy' => 'Un module = une grande idée.'],
            ['icon' => 'fa-play-circle', 'tone' => 'blue', 'title' => 'Leçons', 'copy' => 'Mieux vaut court et clair.'],
            ['icon' => 'fa-shapes', 'tone' => 'amber', 'title' => 'Format', 'copy' => 'Choisissez le support adapté.'],
        ],
        'fieldLead' => 'Les cartes aident à garder un rythme simple.',
        'fieldCopy' => 'Allez à l’essentiel pour rester lisible.',
        'fields' => [
            ['label' => 'Modules', 'help' => 'Les grands chapitres du cours.', 'detail' => 'Visez 3 à 6 modules au départ.', 'icon' => 'fa-layer-group', 'tone' => 'indigo', 'badge' => 'Structure'],
            ['label' => 'Leçons', 'help' => 'Une idée principale par leçon.', 'detail' => 'Gardez un contenu ciblé.', 'icon' => 'fa-book-open', 'tone' => 'blue', 'badge' => 'Progression'],
            ['label' => 'Type de leçon', 'help' => 'Vidéo, document ou rendez-vous.', 'detail' => 'Choisissez le bon format.', 'icon' => 'fa-shapes', 'tone' => 'amber', 'badge' => 'Format'],
            ['label' => 'Durée', 'help' => 'Estimation du temps.', 'detail' => 'Restez réaliste.', 'icon' => 'fa-clock', 'tone' => 'emerald', 'badge' => 'Cadence'],
            ['label' => 'Liens / fichiers', 'help' => 'Ressource associée à la leçon.', 'detail' => 'Vérifiez chaque fichier ou lien.', 'icon' => 'fa-link', 'tone' => 'rose', 'badge' => 'Ressource'],
        ],
        'cta' => 'Continuer',
    ],
    4 => [
        'eyebrow' => 'Étape 4',
        'title' => 'Validez les acquis sans compliquer la fin du parcours',
        'icon' => 'fa-clipboard-check',
        'tone' => 'emerald',
        'copy' => 'Vérifiez l’essentiel avec une évaluation simple.',
        'focus' => 'L’évaluation doit rester alignée sur les objectifs du cours.',
        'stats' => [
            ['label' => 'Évaluations', 'value' => $formatCountLabel($draftExamCount, 'évaluation', 'évaluations')],
            ['label' => 'Formats', 'value' => '3 options'],
            ['label' => 'Objectif', 'value' => 'Vérifier les acquis'],
        ],
        'tips' => [
            ['icon' => 'fa-link', 'tone' => 'blue', 'title' => 'Alignement', 'copy' => 'Testez un objectif déjà annoncé.'],
            ['icon' => 'fa-stopwatch', 'tone' => 'amber', 'title' => 'Temps', 'copy' => 'Laissez une marge correcte.'],
            ['icon' => 'fa-check-double', 'tone' => 'emerald', 'title' => 'Clarté', 'copy' => 'Questions courtes, réponses nettes.'],
        ],
        'fieldLead' => 'Gardez une évaluation courte et lisible.',
        'fieldCopy' => 'Simple, claire et liée au contenu.',
        'fields' => [
            ['label' => 'Titre', 'help' => 'Nom clair et rassurant.', 'detail' => 'Exemple : évaluation finale.', 'icon' => 'fa-heading', 'tone' => 'emerald', 'badge' => 'Visible'],
            ['label' => 'Type', 'help' => 'QCM, document ou rendez-vous.', 'detail' => 'Choisissez selon l’objectif.', 'icon' => 'fa-list-check', 'tone' => 'indigo', 'badge' => 'Format'],
            ['label' => 'Durée', 'help' => 'Cadre simple et prévisible.', 'detail' => 'Gardez une marge confortable.', 'icon' => 'fa-hourglass-half', 'tone' => 'amber', 'badge' => 'Temps'],
            ['label' => 'Questions', 'help' => 'Une question, une compétence.', 'detail' => 'Restez précis.', 'icon' => 'fa-circle-question', 'tone' => 'blue', 'badge' => 'Clarté'],
            ['label' => 'Réponses', 'help' => 'Correction simple et nette.', 'detail' => 'Évitez les ambiguïtés.', 'icon' => 'fa-check-double', 'tone' => 'emerald', 'badge' => 'Correction'],
            ['label' => 'Sujet', 'help' => 'Support externe si besoin.', 'detail' => 'PDF, brief, fichier source.', 'icon' => 'fa-file-circle-plus', 'tone' => 'violet', 'badge' => 'Support'],
        ],
        'cta' => 'Continuer',
    ],
];

$renderIntroStats = static function (array $items): void {
    if ($items === []) {
        return;
    }
    ?>
    <div class="course-step-intro__stats">
        <?php foreach ($items as $item): ?>
            <?php
            $label = trim((string) ($item['label'] ?? 'Repère'));
            $value = trim((string) ($item['value'] ?? ''));
            ?>
            <article class="course-step-intro__stat">
                <span class="course-step-intro__stat-label"><?php echo e($label); ?></span>
                <strong class="course-step-intro__stat-value"><?php echo e($value); ?></strong>
            </article>
        <?php endforeach; ?>
    </div>
    <?php
};

$renderGuideCards = static function (array $items, string $extraClass = '') use ($normalizeTone): void {
    if ($items === []) {
        return;
    }
    $className = trim('course-step-guide ' . $extraClass);
    ?>
    <div class="<?php echo e($className); ?>">
        <?php foreach ($items as $item): ?>
            <?php
            $title = trim((string) ($item['title'] ?? 'Repère'));
            $copy = trim((string) ($item['copy'] ?? ''));
            $icon = trim((string) ($item['icon'] ?? 'fa-circle-info'));
            $tone = $normalizeTone((string) ($item['tone'] ?? 'blue'));
            ?>
            <article class="course-step-guide__card course-step-guide__card--<?php echo e($tone); ?>">
                <span class="course-step-guide__icon" aria-hidden="true">
                    <i class="fas <?php echo e($icon); ?>"></i>
                </span>
                <div class="course-step-guide__copy">
                    <h3 class="course-step-guide__title"><?php echo e($title); ?></h3>
                    <?php if ($copy !== ''): ?>
                        <p class="course-step-guide__text"><?php echo e($copy); ?></p>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php
};

$renderIntroFields = static function (array $fields) use ($normalizeTone): void {
    if ($fields === []) {
        return;
    }
    ?>
    <div class="course-step-intro__fields">
        <?php foreach ($fields as $field): ?>
            <?php
            $label = is_array($field) ? trim((string) ($field['label'] ?? '')) : trim((string) $field);
            $help = is_array($field) ? trim((string) ($field['help'] ?? '')) : '';
            $detail = is_array($field) ? trim((string) ($field['detail'] ?? '')) : '';
            $badge = is_array($field) ? trim((string) ($field['badge'] ?? '')) : '';
            $icon = is_array($field) ? trim((string) ($field['icon'] ?? 'fa-circle-info')) : 'fa-circle-info';
            $tone = is_array($field) ? $normalizeTone((string) ($field['tone'] ?? 'blue')) : 'blue';
            ?>
            <article class="course-step-intro__field-card course-step-intro__field-card--<?php echo e($tone); ?>">
                <span class="course-step-intro__field-icon" aria-hidden="true">
                    <i class="fas <?php echo e($icon); ?>"></i>
                </span>
                <div class="course-step-intro__field-copy">
                    <span class="course-step-intro__field-top">
                        <span class="course-step-intro__field-label"><?php echo e($label !== '' ? $label : 'Champ'); ?></span>
                        <?php if ($badge !== ''): ?>
                            <span class="course-step-intro__field-badge"><?php echo e($badge); ?></span>
                        <?php endif; ?>
                    </span>
                    <?php if ($help !== ''): ?>
                        <p class="course-step-intro__field-help"><?php echo e($help); ?></p>
                    <?php endif; ?>
                    <?php if ($detail !== ''): ?>
                        <p class="course-step-intro__field-detail"><?php echo e($detail); ?></p>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php
};

$renderSimpleRows = static function (array $items, string $name, string $placeholder) use ($listValues): void {
    foreach ($listValues($items) as $value) {
        ?>
        <div class="flex items-center gap-2">
            <input type="text" name="<?php echo e($name); ?>[]" value="<?php echo e($value); ?>" placeholder="<?php echo e($placeholder); ?>" class="flex-1 px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
            <button type="button" data-remove-simple-row class="text-slate-400 hover:text-red-500 transition" aria-label="Supprimer">
                <i class="fas fa-trash-alt"></i>
            </button>
        </div>
        <?php
    }
};
?>
<!doctype html>
<html lang="fr" class="light" data-default-theme="light" data-view="<?php echo $editMode ? 'teacher/course_edit' : 'teacher/course_create'; ?>" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google" content="notranslate">
    <meta name="app-global-assets" content="off">
    <title><?php echo e($pageTitle); ?> · Enseignant · École Numérique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo e(site_url('assets/js/tailwind_theme_bootstrap.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/tailwind_theme_bootstrap.js') ?: time()))); ?>"></script>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/teacher-dashboard.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/teacher-dashboard.css') ?: time()))); ?>">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/course_create_builder.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/course_create_builder.css') ?: time()))); ?>">
    <script src="<?php echo e(site_url('assets/js/sidebar_toggle.js')); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/teacher_dashboard.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/teacher_dashboard.js') ?: time()))); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/vendor/xlsx.full.min.js')); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/course_complete_builder.js?v=' . (@filemtime(__DIR__ . '/../../../assets/js/course_complete_builder.js') ?: time()))); ?>" defer></script>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="app-view app-role-teacher min-h-screen bg-gray-50 text-gray-800 antialiased transition-colors duration-300 dark:bg-gray-900 dark:text-gray-200 font-sans" data-view="<?php echo $editMode ? 'teacher/course_edit' : 'teacher/course_create'; ?>">
    <div class="teacher-dashboard-layout relative min-h-screen overflow-x-hidden pt-16 sm:pt-[4.5rem] md:pl-72 md:flex-row">
        <?php require __DIR__ . '/_sidebar.php'; ?>
        <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

        <main class="teacher-dashboard-main min-h-screen bg-gray-50 dark:bg-gray-900" data-scroll-top-root="1">
            <?php require __DIR__ . '/_topbar.php'; ?>

            <div class="course-builder-page p-4 sm:p-5 lg:p-6 animate-fade-up">
                <div class="course-builder-shell grid gap-5 xl:grid-cols-1 2xl:items-start">
                    <form method="post" action="<?php echo e($formAction); ?>" enctype="multipart/form-data" novalidate data-course-create-form data-autosave-key="<?php echo e($autosaveKey); ?>" data-autosave-clear="<?php echo e($autosaveClear); ?>" data-course-create-draft-prompt="<?php echo $draftPromptEnabled ? '1' : '0'; ?>" class="course-complete-builder-form min-w-0">
        <input type="hidden" name="_csrf" value="<?php echo e($csrfToken); ?>">
        <input type="hidden" id="content_payload" name="content_payload" value="<?php echo e($contentBuilderPayload); ?>">
        <input type="hidden" id="exams_payload" name="exams_payload" value="<?php echo e($examBuilderPayload); ?>">
        <input type="hidden" id="cover_path_existing" name="cover_path_existing" value="<?php echo e($coverPathExisting); ?>">
        <input type="hidden" id="referral_override" name="referral_override" value="<?php echo $referralOverride ? '1' : '0'; ?>">
        <input type="hidden" id="referral_l2_enabled" name="referral_l2_enabled" value="<?php echo $referralL2Enabled ? '1' : '0'; ?>">
        <input type="hidden" id="duration_hours" name="duration_hours" value="<?php echo e($durationHours !== '' ? $durationHours : '1'); ?>">

                        <section class="mb-4 flex flex-col gap-3 rounded-[1.6rem] border border-slate-200 bg-white p-5 shadow-card dark:border-gray-700 dark:bg-gray-800 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400"><?php echo $editMode ? 'Édition' : 'Création'; ?></p>
                                <h1 class="mt-1 text-2xl font-black text-slate-900 dark:text-white"><?php echo e($pageTitle); ?></h1>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a href="<?php echo e(site_url('teacher/dashboard')); ?>" class="inline-flex items-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 shadow-sm transition hover:border-blue-300 hover:bg-blue-100 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-200 dark:hover:bg-blue-500/20">
                                    <i class="fas fa-arrow-left"></i> Retour au tableau de bord
                                </a>
                                <button type="button" data-preview-course class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 shadow-sm transition hover:border-emerald-300 hover:bg-emerald-100 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200 dark:hover:bg-emerald-500/20">
                                    <i class="far fa-eye"></i> Aperçu complet
                                </button>
                                <button type="submit" name="submit_action" value="draft" formnovalidate class="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-800 shadow-sm transition hover:border-amber-300 hover:bg-amber-100 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200 dark:hover:bg-amber-500/20">
                                    <i class="far fa-save"></i> <?php echo e($draftButtonLabel); ?>
                                </button>
                            </div>
                        </section>

            <?php if ($errors !== []): ?>
                <section class="course-builder-error-banner mb-6 rounded-2xl px-5 py-4 text-sm shadow-card">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-exclamation-triangle mt-0.5"></i>
                        <div class="space-y-1">
                            <p class="font-semibold">Le cours n’a pas été enregistré.</p>
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo e((string) $error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($successMessage !== ''): ?>
                <div
                    class="teacher-withdrawal-toast teacher-withdrawal-toast--success"
                    role="status"
                    aria-live="polite"
                    data-notification-toast="1"
                    data-notification-toast-type="success"
                    data-notification-toast-message="<?php echo e($successMessage); ?>"
                >
                    <div class="teacher-withdrawal-toast__copy">
                        <span class="teacher-withdrawal-toast__icon" aria-hidden="true">
                            <i class="fas fa-check-circle"></i>
                        </span>
                        <div class="min-w-0">
                            <p class="teacher-withdrawal-toast__title">Succès</p>
                            <p class="teacher-withdrawal-toast__message"><?php echo e($successMessage); ?></p>
                        </div>
                    </div>
                    <span class="teacher-withdrawal-toast__bar" aria-hidden="true"></span>
                </div>
            <?php endif; ?>

            <section class="course-builder-workflow rounded-[1.8rem] border border-slate-200 bg-white p-4 shadow-card dark:border-gray-700 dark:bg-gray-800 sm:p-5">
                <div class="course-builder-workflow__head">
                    <div>
                        <p class="course-builder-workflow__eyebrow">Parcours guidé</p>
                        <h2 class="course-builder-workflow__title">4 étapes pour construire un cours clair</h2>
                        <p class="course-builder-workflow__note">Chaque étape commence par une introduction visuelle qui explique les champs, puis vous passez à la saisie du formulaire.</p>
                    </div>
                    <div class="course-builder-workflow__legend" aria-hidden="true">
                        <span class="course-builder-workflow__legend-chip course-builder-workflow__legend-chip--complete">
                            <i class="fas fa-circle-check"></i> Prêt
                        </span>
                        <span class="course-builder-workflow__legend-chip course-builder-workflow__legend-chip--pending">
                            <i class="fas fa-circle-half-stroke"></i> À faire
                        </span>
                    </div>
                </div>
                <div class="course-builder-workflow__grid">
                    <?php foreach ($journeyCards as $journeyCard): ?>
                        <?php $stepNumber = (int) ($journeyCard['step'] ?? 1); ?>
                        <?php $isComplete = !empty($journeyCard['complete']); ?>
                        <button type="button" data-step-target="<?php echo e((string) (($stepNumber - 1) * 2 + 1)); ?>" data-step-complete="<?php echo $isComplete ? '1' : '0'; ?>" id="stepBtn<?php echo e((string) $stepNumber); ?>" class="course-builder-workflow__step course-builder-workflow__step--<?php echo $isComplete ? 'complete' : 'pending'; ?> rounded-2xl px-4 py-3 text-left text-sm font-semibold transition-all duration-300">
                            <span class="course-builder-workflow__step-index"><?php echo e((string) $stepNumber); ?></span>
                            <span class="course-builder-workflow__step-copy">
                                <strong>
                                    <i class="fas <?php echo e((string) ($journeyCard['icon'] ?? 'fa-circle-info')); ?>" aria-hidden="true"></i>
                                    <span><?php echo e((string) ($journeyCard['title'] ?? 'Étape')); ?></span>
                                </strong>
                                <?php if (trim((string) ($journeyCard['hint'] ?? '')) !== ''): ?>
                                    <small><?php echo e((string) ($journeyCard['hint'] ?? '')); ?></small>
                                <?php endif; ?>
                            </span>
                            <em class="course-builder-workflow__step-state"><?php echo e((string) ($journeyCard['state'] ?? '')); ?></em>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="course-builder-workflow__progress">
                    <div class="course-builder-workflow__progress-track">
                        <div id="progressBar" class="course-create-progress-fill course-builder-workflow__progress-fill"></div>
                    </div>
                    <div class="course-builder-workflow__progress-track">
                        <div id="progressBar2" class="course-create-progress-fill course-builder-workflow__progress-fill"></div>
                    </div>
                    <div class="course-builder-workflow__progress-track">
                        <div id="progressBar3" class="course-create-progress-fill course-builder-workflow__progress-fill"></div>
                    </div>
                </div>
            </section>

            <section id="step1Intro" class="course-step-intro rounded-[1.8rem] border border-slate-200 bg-white p-6 shadow-card dark:border-gray-700 dark:bg-gray-800 sm:p-8">
                <?php $phase = $phaseIntroductions[1]; ?>
                <div class="course-step-intro__hero">
                    <p class="course-step-intro__eyebrow"><?php echo e($phase['eyebrow']); ?></p>
                    <div class="course-step-intro__title-row">
                        <span class="course-step-intro__hero-icon course-step-intro__hero-icon--<?php echo e((string) ($phase['tone'] ?? 'blue')); ?>" aria-hidden="true">
                            <i class="fas <?php echo e((string) ($phase['icon'] ?? 'fa-circle-info')); ?>"></i>
                        </span>
                        <div class="course-step-intro__title-copy">
                            <h2 class="course-step-intro__title"><?php echo e($phase['title']); ?></h2>
                            <p class="course-step-intro__copy"><?php echo e($phase['copy']); ?></p>
                        </div>
                    </div>
                    <p class="course-step-intro__focus">
                        <i class="fas fa-lightbulb" aria-hidden="true"></i>
                        <span><?php echo e((string) ($phase['focus'] ?? '')); ?></span>
                    </p>
                    <?php $renderIntroStats($phase['stats'] ?? []); ?>
                </div>
                <div class="course-step-intro__support">
                    <div>
                        <div class="course-step-intro__section-head">
                            <div>
                                <p class="course-step-intro__section-eyebrow">Guide rapide</p>
                                <h3 class="course-step-intro__section-title">Ce que vous allez clarifier avant de remplir le formulaire</h3>
                            </div>
                            <p class="course-step-intro__section-copy">Ces repères vous aident à écrire une fiche simple, rassurante et compréhensible pour l’apprenant.</p>
                        </div>
                        <?php $renderGuideCards($phase['tips'] ?? []); ?>
                    </div>
                </div>
                <div class="course-step-intro__field-block">
                    <div class="course-step-intro__section-head">
                        <div>
                            <p class="course-step-intro__section-eyebrow">Champs à préparer</p>
                            <h3 class="course-step-intro__section-title"><?php echo e((string) ($phase['fieldLead'] ?? '')); ?></h3>
                        </div>
                        <p class="course-step-intro__section-copy"><?php echo e((string) ($phase['fieldCopy'] ?? '')); ?></p>
                    </div>
                    <?php $renderIntroFields($phase['fields'] ?? []); ?>
                </div>
                <div class="course-step-intro__actions">
                    <button type="button" data-step-target="2" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-6 py-3 text-sm font-semibold text-white shadow-lg transition hover:bg-primary-700">
                        <?php echo e($phase['cta']); ?> <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </section>

            <div id="step1Content" class="hidden course-step-content space-y-5">
                <?php $phase = $phaseIntroductions[1]; ?>
                <section class="course-step-panel">
                    <div class="course-step-panel__head">
                        <div>
                            <p class="course-step-panel__eyebrow">Pendant la saisie</p>
                            <h3 class="course-step-panel__title">Gardez les repères clés sous les yeux</h3>
                        </div>
                        <p class="course-step-panel__copy">Simple et honnête vaut mieux que trop chargé.</p>
                    </div>
                    <?php $renderGuideCards($phase['tips'] ?? [], 'course-step-guide--compact'); ?>
                </section>
                <div class="course-form-card bg-white rounded-2xl shadow-card p-5 sm:p-6">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-10 h-10 bg-primary-100 rounded-xl flex items-center justify-center">
                            <i class="fas fa-info-circle text-primary-600"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold">Informations générales</h2>
                            <p class="course-builder-field-note">Commencez par le visible, puis complétez.</p>
                        </div>
                    </div>
                    <div class="grid gap-4 lg:grid-cols-2">
                        <div>
                            <label for="title" class="block text-sm font-semibold mb-1.5">Titre <span class="text-red-500">*</span></label>
                            <p class="course-builder-field-note">Un titre court, explicite et facile à retenir.</p>
                            <input id="title" name="title" type="text" value="<?php echo e($title); ?>" placeholder="Ex: Maîtriser React JS" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                        </div>
                        <div>
                            <label for="level" class="block text-sm font-semibold mb-1.5">Niveau <span class="text-red-500">*</span></label>
                            <p class="course-builder-field-note">Choisissez le vrai niveau d’entrée pour éviter de tromper l’apprenant.</p>
                            <select id="level" name="level" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                                <option value="beginner" <?php echo $level === 'beginner' ? 'selected' : ''; ?>>Débutant</option>
                                <option value="intermediate" <?php echo $level === 'intermediate' ? 'selected' : ''; ?>>Intermédiaire</option>
                                <option value="advanced" <?php echo $level === 'advanced' ? 'selected' : ''; ?>>Avancé</option>
                            </select>
                        </div>
                        <div class="lg:col-span-2">
                            <label for="description" class="block text-sm font-semibold mb-1.5">Description <span class="text-red-500">*</span></label>
                            <p class="course-builder-field-note">Expliquez le bénéfice concret du cours avant les détails techniques.</p>
                            <textarea id="description" name="description" rows="4" placeholder="Décrivez le cours..." required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm resize-none focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"><?php echo e($description); ?></textarea>
                        </div>
                    </div>

                    <details class="course-builder-details mt-5 rounded-2xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-900/50" <?php echo $pedagogyOpen ? 'open' : ''; ?>>
                        <summary class="course-builder-details-summary flex cursor-pointer items-center justify-between gap-3 px-4 py-3">
                            <span class="font-semibold text-slate-900 dark:text-white">Pédagogie</span>
                            <span class="flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-300">
                                <span data-pedagogy-count><?php echo e((string) ($countFilledList($objectives) + $countFilledList($prerequisites) + $countFilledList($equipment))); ?> élément(s)</span>
                                <i class="fas fa-chevron-down"></i>
                            </span>
                        </summary>
                        <div class="course-builder-details-body space-y-5 border-t border-slate-200 p-4 dark:border-slate-700">
                        <div>
                            <label class="block text-sm font-semibold mb-1.5">Objectifs pédagogiques</label>
                            <p class="course-builder-field-note">Décrivez ce que l’apprenant saura faire à la fin du cours.</p>
                            <div id="objectives-container" class="space-y-2">
                                <?php $renderSimpleRows($objectives, 'objectives', 'Objectif'); ?>
                            </div>
                            <button type="button" data-add-simple-row data-container="objectives-container" data-name="objectives" data-placeholder="Objectif" class="mt-2 text-primary-600 text-sm font-medium flex items-center gap-1">
                                <i class="fas fa-plus text-xs"></i> Ajouter
                            </button>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1.5">Prérequis</label>
                            <p class="course-builder-field-note">Indiquez uniquement ce qui est vraiment nécessaire avant de commencer.</p>
                            <div id="prerequisites-container" class="space-y-2">
                                <?php $renderSimpleRows($prerequisites, 'prerequisites', 'Prérequis'); ?>
                            </div>
                            <button type="button" data-add-simple-row data-container="prerequisites-container" data-name="prerequisites" data-placeholder="Prérequis" class="mt-2 text-amber-600 text-sm font-medium flex items-center gap-1">
                                <i class="fas fa-plus text-xs"></i> Ajouter
                            </button>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1.5">Équipement nécessaire</label>
                            <p class="course-builder-field-note">Listez le matériel ou les logiciels à préparer en amont.</p>
                            <div id="equipment-container" class="space-y-2">
                                <?php $renderSimpleRows($equipment, 'equipment', 'Équipement'); ?>
                            </div>
                            <button type="button" data-add-simple-row data-container="equipment-container" data-name="equipment" data-placeholder="Équipement" class="mt-2 text-teal-600 text-sm font-medium flex items-center gap-1">
                                <i class="fas fa-plus text-xs"></i> Ajouter
                            </button>
                        </div>
                        </div>
                    </details>
                </div>

                <div class="course-step-actions flex justify-between">
                    <button type="button" data-step-target="1" class="flex items-center gap-2 px-6 py-3 bg-white border border-slate-200 text-slate-600 rounded-xl text-sm font-semibold shadow-sm hover:bg-slate-50 transition">
                        <i class="fas fa-arrow-left"></i> Retour
                    </button>
                    <button type="button" data-step-target="3" class="flex items-center gap-2 px-6 py-3 bg-primary-600 text-white rounded-xl text-sm font-semibold shadow-lg hover:bg-primary-700 transition">
                        Continuer <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <section id="step2Intro" class="hidden course-step-intro rounded-[1.8rem] border border-slate-200 bg-white p-6 shadow-card dark:border-gray-700 dark:bg-gray-800 sm:p-8">
                <?php $phase = $phaseIntroductions[2]; ?>
                <div class="course-step-intro__hero">
                    <p class="course-step-intro__eyebrow"><?php echo e($phase['eyebrow']); ?></p>
                    <div class="course-step-intro__title-row">
                        <span class="course-step-intro__hero-icon course-step-intro__hero-icon--<?php echo e((string) ($phase['tone'] ?? 'blue')); ?>" aria-hidden="true">
                            <i class="fas <?php echo e((string) ($phase['icon'] ?? 'fa-circle-info')); ?>"></i>
                        </span>
                        <div class="course-step-intro__title-copy">
                            <h2 class="course-step-intro__title"><?php echo e($phase['title']); ?></h2>
                            <p class="course-step-intro__copy"><?php echo e($phase['copy']); ?></p>
                        </div>
                    </div>
                    <p class="course-step-intro__focus">
                        <i class="fas fa-lightbulb" aria-hidden="true"></i>
                        <span><?php echo e((string) ($phase['focus'] ?? '')); ?></span>
                    </p>
                    <?php $renderIntroStats($phase['stats'] ?? []); ?>
                </div>
                <div class="course-step-intro__support">
                    <div>
                        <div class="course-step-intro__section-head">
                            <div>
                                <p class="course-step-intro__section-eyebrow">Guide rapide</p>
                                <h3 class="course-step-intro__section-title">Ce qui rend votre offre plus lisible</h3>
                            </div>
                            <p class="course-step-intro__section-copy">Travaillez le visuel, le prix et le bon classement avant d’ajouter les options avancées.</p>
                        </div>
                        <?php $renderGuideCards($phase['tips'] ?? []); ?>
                    </div>
                </div>
                <div class="course-step-intro__field-block">
                    <div class="course-step-intro__section-head">
                        <div>
                            <p class="course-step-intro__section-eyebrow">Champs à préparer</p>
                            <h3 class="course-step-intro__section-title"><?php echo e((string) ($phase['fieldLead'] ?? '')); ?></h3>
                        </div>
                        <p class="course-step-intro__section-copy"><?php echo e((string) ($phase['fieldCopy'] ?? '')); ?></p>
                    </div>
                    <?php $renderIntroFields($phase['fields'] ?? []); ?>
                </div>
                <div class="course-step-intro__actions">
                    <button type="button" data-step-target="2" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
                        <i class="fas fa-arrow-left"></i> Retour
                    </button>
                    <button type="button" data-step-target="4" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-6 py-3 text-sm font-semibold text-white shadow-lg transition hover:bg-primary-700">
                        <?php echo e($phase['cta']); ?> <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </section>

            <div id="step2Content" class="hidden space-y-6">
                <?php $phase = $phaseIntroductions[2]; ?>
                <section class="course-step-panel">
                    <div class="course-step-panel__head">
                        <div>
                            <p class="course-step-panel__eyebrow">Pendant la saisie</p>
                            <h3 class="course-step-panel__title">Gardez une offre simple et crédible</h3>
                        </div>
                        <p class="course-step-panel__copy">Remplissez d’abord les indispensables, puis activez les options utiles à votre modèle de vente.</p>
                    </div>
                    <?php $renderGuideCards($phase['tips'] ?? [], 'course-step-guide--compact'); ?>
                </section>
                <div class="course-form-card bg-white rounded-2xl shadow-card p-5 sm:p-6">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-10 h-10 bg-accent-100 rounded-xl flex items-center justify-center">
                            <i class="fas fa-image text-accent-600"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold">Médias & tarification</h2>
                            <p class="course-builder-field-note">Ces champs servent à présenter le cours, fixer son prix et préparer ses ressources communes.</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold mb-2">Miniature <span class="text-red-500">*</span></label>
                            <p class="text-xs text-slate-500 mb-2">Format recommandé : image lisible en paysage, JPG / PNG / WebP.</p>
                            <p class="course-builder-field-note">Aucune dimension précise n'est imposée. Choisissez simplement une image nette et compréhensible même dans une petite vignette.</p>
                            <div id="thumbnail-zone" data-thumbnail-zone class="border-2 border-dashed border-slate-300 rounded-2xl p-6 text-center cursor-pointer hover:border-primary-400 transition">
                                <input type="file" id="cover_file" name="cover_file" accept=".jpg,.jpeg,.png,.webp" <?php echo $coverPathExisting === '' ? 'required' : ''; ?> class="hidden">
                                <div id="thumb-placeholder" class="<?php echo $coverExistingUrl !== '' ? 'hidden' : ''; ?>">
                                    <i class="fas fa-cloud-upload-alt text-2xl text-slate-400 mb-2"></i>
                                    <p class="text-sm text-slate-700">Choisir une image</p>
                                    <p class="text-xs text-slate-400 mt-1">JPG, PNG, WebP - dimensions libres</p>
                                </div>
                                <div id="thumb-preview" class="<?php echo $coverExistingUrl !== '' ? '' : 'hidden'; ?> relative">
                                    <img id="thumb-img" src="<?php echo e($coverExistingUrl); ?>" alt="Miniature du cours" class="w-full h-32 object-cover rounded-lg">
                                    <button type="button" data-remove-thumb class="absolute top-2 right-2 bg-white p-1 rounded-full shadow text-slate-700 hover:text-red-500" aria-label="Retirer la miniature">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label for="price_xof" class="block text-sm font-semibold">Prix public (F CFA) <span class="text-red-500">*</span></label>
                                <p class="course-builder-field-note">Le tarif individuel doit refléter la valeur perçue du cours.</p>
                                <input id="price_xof" name="price_xof" type="number" min="0" step="1" value="<?php echo e($priceXof); ?>" placeholder="49000" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                            </div>
                            <div class="space-y-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div>
                                    <label for="enterprise_pricing_mode" class="block text-sm font-semibold">Offre entreprise</label>
                                    <p class="course-builder-field-note">Optionnel : appliquez soit un prix fixe, soit une remise dédiée aux entreprises.</p>
                                    <select id="enterprise_pricing_mode" name="enterprise_pricing_mode" class="mt-2 w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                                        <option value="none" <?php echo $enterprisePricingMode === 'none' ? 'selected' : ''; ?>>Aucune offre entreprise</option>
                                        <option value="price" <?php echo $enterprisePricingMode === 'price' ? 'selected' : ''; ?>>Prix fixe entreprise</option>
                                        <option value="discount" <?php echo $enterprisePricingMode === 'discount' ? 'selected' : ''; ?>>Réduction entreprise</option>
                                    </select>
                                </div>
                                <div data-enterprise-panel="price" class="<?php echo $enterprisePricingMode === 'price' ? '' : 'hidden'; ?>">
                                    <label for="enterprise_price_xof" class="block text-sm font-semibold">Prix entreprise (F CFA)</label>
                                    <input id="enterprise_price_xof" name="enterprise_price_xof" type="number" min="1" step="1" value="<?php echo e($enterprisePriceXof); ?>" placeholder="25000" class="mt-2 w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                                </div>
                                <div data-enterprise-panel="discount" class="<?php echo $enterprisePricingMode === 'discount' ? '' : 'hidden'; ?>">
                                    <label for="enterprise_discount_percent" class="block text-sm font-semibold">Réduction entreprise (%)</label>
                                    <input id="enterprise_discount_percent" name="enterprise_discount_percent" type="number" min="0.01" max="100" step="0.01" value="<?php echo e($enterpriseDiscountPercent); ?>" placeholder="15" class="mt-2 w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                                </div>
                                <p class="hidden text-xs font-semibold text-amber-600" data-enterprise-warning>Choisissez soit un prix entreprise, soit une réduction, pas les deux.</p>
                            </div>
                            <div>
                                <label for="category_id" class="block text-sm font-semibold">Catégorie <span class="text-red-500">*</span></label>
                                <p class="course-builder-field-note">Choisissez la catégorie principale qui décrit le mieux le sujet du cours.</p>
                                <select id="category_id" name="category_id" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                                    <option value="">Sélectionnez une catégorie</option>
                                    <?php foreach ($categories as $category): ?>
                                        <?php
                                        $catId = (string) ($category['id'] ?? '');
                                        $catName = trim((string) ($category['name'] ?? 'Catégorie'));
                                        ?>
                                        <option value="<?php echo e($catId); ?>" <?php echo $categoryId === $catId ? 'selected' : ''; ?>><?php echo e($catName); ?></option>
                                    <?php endforeach; ?>
                                    <option value="other" <?php echo $categoryId === 'other' ? 'selected' : ''; ?>>Autre</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-2">Parrainage</label>
                                <p class="course-builder-field-note">Activez cette option uniquement si vous comptez utiliser la recommandation ou l’affiliation.</p>
                                <button
                                    type="button"
                                    id="referral-toggle"
                                    data-referral-toggle
                                    aria-pressed="<?php echo $referralOverride ? 'true' : 'false'; ?>"
                                    aria-controls="referral-options"
                                    aria-label="<?php echo $referralOverride ? 'Désactiver le parrainage' : 'Activer le parrainage'; ?>"
                                    class="course-builder-referral-switch <?php echo $referralOverride ? 'is-active' : 'is-inactive'; ?>"
                                >
                                    <span class="course-builder-referral-switch__track" aria-hidden="true">
                                        <span id="ref-dot" class="course-builder-referral-switch__thumb"></span>
                                    </span>
                                    <span id="ref-status" class="course-builder-referral-switch__badge" aria-live="polite"><?php echo $referralOverride ? 'Activé' : 'Désactivé'; ?></span>
                                </button>
                                <div id="referral-options" class="<?php echo $referralOverride ? '' : 'hidden'; ?> mt-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <label class="inline-flex items-start gap-3 text-sm font-semibold text-slate-700">
                                        <input type="checkbox" id="referral-l2-toggle" data-referral-l2-toggle <?php echo $referralL2Enabled ? 'checked' : ''; ?> class="mt-1 h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                                        <span>
                                            Appliquer aussi au niveau 2
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <details class="course-builder-details mt-6 rounded-2xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-900/50" <?php echo $generalFileCacheName !== '' ? 'open' : ''; ?>>
                        <summary class="course-builder-details-summary flex cursor-pointer items-center justify-between gap-3 px-4 py-3">
                            <span class="font-semibold text-slate-900 dark:text-white"><i class="fas fa-cloud-upload-alt mr-1 text-emerald-500"></i>Ressource générale</span>
                            <span class="flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-300"><i class="fas fa-chevron-down"></i></span>
                        </summary>
                        <div class="course-builder-details-body border-t border-slate-200 p-4 dark:border-slate-700">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <p class="text-xs text-slate-500 dark:text-slate-300">Max <?php echo e((string) $driveMaxMb); ?> Mo. Partagez ici un document commun à tout le cours.</p>
                            <?php if ($driveConnectRecommended): ?>
                                <a href="<?php echo e(site_url('drive/oauth_connect')); ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 hover:text-blue-700">
                                    Connecter Drive <i class="fas fa-arrow-right"></i>
                                </a>
                            <?php endif; ?>
                            </div>
                            <input id="general_file" name="general_file" type="file" class="mt-4 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                        <?php if ($generalFileCacheName !== ''): ?>
                            <div class="mt-3 rounded-xl border border-blue-100 bg-blue-50 p-3 text-sm text-blue-700">
                                <p class="font-semibold"><?php echo e($generalFileCacheName); ?></p>
                                <p class="text-xs"><?php echo e($formatBytes($generalFileCacheSize)); ?></p>
                                <label class="mt-2 inline-flex items-center gap-2 text-xs font-semibold text-blue-700">
                                    <input type="checkbox" name="general_file_clear" value="1" class="rounded border-blue-200">
                                    <?php echo e($generalFileRemoveLabel); ?>
                                </label>
                            </div>
                        <?php endif; ?>
                        </div>
                    </details>
                </div>
                <div class="course-step-actions flex justify-between">
                    <button type="button" data-step-target="3" class="flex items-center gap-2 px-6 py-3 bg-white border border-slate-200 text-slate-600 rounded-xl text-sm font-semibold shadow-sm hover:bg-slate-50 transition">
                        <i class="fas fa-arrow-left"></i> Retour
                    </button>
                    <button type="button" data-step-target="5" class="flex items-center gap-2 px-6 py-3 bg-primary-600 text-white rounded-xl text-sm font-semibold shadow-lg hover:bg-primary-700 transition">
                        Continuer <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <section id="step3Intro" class="hidden course-step-intro rounded-[1.8rem] border border-slate-200 bg-white p-6 shadow-card dark:border-gray-700 dark:bg-gray-800 sm:p-8">
                <?php $phase = $phaseIntroductions[3]; ?>
                <div class="course-step-intro__hero">
                    <p class="course-step-intro__eyebrow"><?php echo e($phase['eyebrow']); ?></p>
                    <div class="course-step-intro__title-row">
                        <span class="course-step-intro__hero-icon course-step-intro__hero-icon--<?php echo e((string) ($phase['tone'] ?? 'blue')); ?>" aria-hidden="true">
                            <i class="fas <?php echo e((string) ($phase['icon'] ?? 'fa-circle-info')); ?>"></i>
                        </span>
                        <div class="course-step-intro__title-copy">
                            <h2 class="course-step-intro__title"><?php echo e($phase['title']); ?></h2>
                            <p class="course-step-intro__copy"><?php echo e($phase['copy']); ?></p>
                        </div>
                    </div>
                    <p class="course-step-intro__focus">
                        <i class="fas fa-lightbulb" aria-hidden="true"></i>
                        <span><?php echo e((string) ($phase['focus'] ?? '')); ?></span>
                    </p>
                    <?php $renderIntroStats($phase['stats'] ?? []); ?>
                </div>
                <div class="course-step-intro__support">
                    <div>
                        <div class="course-step-intro__section-head">
                            <div>
                                <p class="course-step-intro__section-eyebrow">Guide rapide</p>
                                <h3 class="course-step-intro__section-title">Comment rendre le parcours fluide</h3>
                            </div>
                            <p class="course-step-intro__section-copy">Structurez d’abord les grands chapitres, puis complétez chaque module avec des leçons courtes et utiles.</p>
                        </div>
                        <?php $renderGuideCards($phase['tips'] ?? []); ?>
                    </div>
                </div>
                <div class="course-step-intro__field-block">
                    <div class="course-step-intro__section-head">
                        <div>
                            <p class="course-step-intro__section-eyebrow">Champs à préparer</p>
                            <h3 class="course-step-intro__section-title"><?php echo e((string) ($phase['fieldLead'] ?? '')); ?></h3>
                        </div>
                        <p class="course-step-intro__section-copy"><?php echo e((string) ($phase['fieldCopy'] ?? '')); ?></p>
                    </div>
                    <?php $renderIntroFields($phase['fields'] ?? []); ?>
                </div>
                <div class="course-step-intro__actions">
                    <button type="button" data-step-target="4" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
                        <i class="fas fa-arrow-left"></i> Retour
                    </button>
                    <button type="button" data-step-target="6" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-6 py-3 text-sm font-semibold text-white shadow-lg transition hover:bg-primary-700">
                        <?php echo e($phase['cta']); ?> <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </section>

            <div id="step3Content" class="hidden space-y-6">
                <?php $phase = $phaseIntroductions[3]; ?>
                <section class="course-step-panel">
                    <div class="course-step-panel__head">
                        <div>
                            <p class="course-step-panel__eyebrow">Pendant la saisie</p>
                            <h3 class="course-step-panel__title">Construisez un parcours avant d’ajouter les détails</h3>
                        </div>
                        <p class="course-step-panel__copy">Commencez par l’ordre logique des modules, puis ajoutez les leçons et les ressources qui servent vraiment l’objectif.</p>
                    </div>
                    <?php $renderGuideCards($phase['tips'] ?? [], 'course-step-guide--compact'); ?>
                </section>
                <div class="bg-white rounded-2xl shadow-card p-6 sm:p-8">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center">
                                <i class="fas fa-layer-group text-indigo-600"></i>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold">Contenu du cours</h2>
                                <p class="course-builder-field-note">Ajoutez au moins un module, puis des leçons cohérentes avec la promesse annoncée au début du cours.</p>
                            </div>
                        </div>
                        <button type="button" data-add-module class="flex items-center gap-2 px-4 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-semibold shadow">
                            <i class="fas fa-plus text-xs"></i> Nouveau module
                        </button>
                    </div>
                    <div id="modules-container" class="space-y-4"></div>
                </div>
                <div class="course-step-actions flex justify-between">
                    <button type="button" data-step-target="5" class="flex items-center gap-2 px-6 py-3 bg-white border border-slate-200 text-slate-600 rounded-xl text-sm font-semibold shadow-sm hover:bg-slate-50 transition">
                        <i class="fas fa-arrow-left"></i> Retour
                    </button>
                    <button type="button" data-step-target="7" class="flex items-center gap-2 px-6 py-3 bg-primary-600 text-white rounded-xl text-sm font-semibold shadow-lg hover:bg-primary-700 transition">
                        Continuer <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <section id="step4Intro" class="hidden course-step-intro rounded-[1.8rem] border border-slate-200 bg-white p-6 shadow-card dark:border-gray-700 dark:bg-gray-800 sm:p-8">
                <?php $phase = $phaseIntroductions[4]; ?>
                <div class="course-step-intro__hero">
                    <p class="course-step-intro__eyebrow"><?php echo e($phase['eyebrow']); ?></p>
                    <div class="course-step-intro__title-row">
                        <span class="course-step-intro__hero-icon course-step-intro__hero-icon--<?php echo e((string) ($phase['tone'] ?? 'blue')); ?>" aria-hidden="true">
                            <i class="fas <?php echo e((string) ($phase['icon'] ?? 'fa-circle-info')); ?>"></i>
                        </span>
                        <div class="course-step-intro__title-copy">
                            <h2 class="course-step-intro__title"><?php echo e($phase['title']); ?></h2>
                            <p class="course-step-intro__copy"><?php echo e($phase['copy']); ?></p>
                        </div>
                    </div>
                    <p class="course-step-intro__focus">
                        <i class="fas fa-lightbulb" aria-hidden="true"></i>
                        <span><?php echo e((string) ($phase['focus'] ?? '')); ?></span>
                    </p>
                    <?php $renderIntroStats($phase['stats'] ?? []); ?>
                </div>
                <div class="course-step-intro__support">
                    <div>
                        <div class="course-step-intro__section-head">
                            <div>
                                <p class="course-step-intro__section-eyebrow">Guide rapide</p>
                                <h3 class="course-step-intro__section-title">Comment garder une évaluation juste</h3>
                            </div>
                            <p class="course-step-intro__section-copy">Mesurez les acquis essentiels, sans multiplier les pièges ni les consignes ambiguës.</p>
                        </div>
                        <?php $renderGuideCards($phase['tips'] ?? []); ?>
                    </div>
                </div>
                <div class="course-step-intro__field-block">
                    <div class="course-step-intro__section-head">
                        <div>
                            <p class="course-step-intro__section-eyebrow">Champs à préparer</p>
                            <h3 class="course-step-intro__section-title"><?php echo e((string) ($phase['fieldLead'] ?? '')); ?></h3>
                        </div>
                        <p class="course-step-intro__section-copy"><?php echo e((string) ($phase['fieldCopy'] ?? '')); ?></p>
                    </div>
                    <?php $renderIntroFields($phase['fields'] ?? []); ?>
                </div>
                <div class="course-step-intro__actions">
                    <button type="button" data-step-target="6" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
                        <i class="fas fa-arrow-left"></i> Retour
                    </button>
                    <button type="button" data-step-target="8" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-6 py-3 text-sm font-semibold text-white shadow-lg transition hover:bg-primary-700">
                        <?php echo e($phase['cta']); ?> <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </section>

            <div id="step4Content" class="hidden space-y-6">
                <?php $phase = $phaseIntroductions[4]; ?>
                <section class="course-step-panel">
                    <div class="course-step-panel__head">
                        <div>
                            <p class="course-step-panel__eyebrow">Pendant la saisie</p>
                            <h3 class="course-step-panel__title">Vérifiez l’essentiel, pas autre chose</h3>
                        </div>
                        <p class="course-step-panel__copy">Gardez une évaluation claire, alignée avec vos objectifs et suffisamment simple pour être comprise rapidement.</p>
                    </div>
                    <?php $renderGuideCards($phase['tips'] ?? [], 'course-step-guide--compact'); ?>
                </section>
                <div class="bg-white rounded-2xl shadow-card p-6 sm:p-8">
                    <div class="flex flex-col gap-4 mb-6 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                                <i class="fas fa-clipboard-check text-emerald-600"></i>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold">Examens & Évaluations</h2>
                                <p class="course-builder-field-note">Préparez une validation finale cohérente avec les compétences annoncées dans la présentation du cours.</p>
                            </div>
                        </div>
                    </div>
                    <div id="exams-container" class="space-y-4"></div>
                </div>
                <div class="course-step-actions flex justify-between">
                    <button type="button" data-step-target="7" class="flex items-center gap-2 px-6 py-3 bg-white border border-slate-200 text-slate-600 rounded-xl text-sm font-semibold shadow-sm hover:bg-slate-50 transition">
                        <i class="fas fa-arrow-left"></i> Retour
                    </button>
                    <button type="submit" name="submit_action" value="submit" class="flex items-center gap-2 px-8 py-3.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl text-sm font-bold shadow-lg hover:from-primary-700 transition">
                        <i class="fas fa-rocket"></i> <?php echo e($submitButtonLabel); ?>
                    </button>
                </div>
            </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <div id="course-create-draft-modal" hidden role="dialog" aria-modal="true" aria-labelledby="course-create-draft-title" aria-describedby="course-create-draft-desc" class="fixed inset-0 z-[90] flex items-center justify-center px-4 py-6" style="z-index: 90; display: none;">
        <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm" data-course-create-draft-backdrop aria-hidden="true"></div>
        <div class="relative z-20 w-full max-w-2xl max-h-[calc(100vh-2rem)] overflow-y-auto rounded-[2rem] border border-slate-200 bg-white shadow-2xl pointer-events-auto dark:border-slate-700 dark:bg-gray-900">
            <section data-course-create-draft-choice-panel>
                <div class="flex items-start gap-4 p-6 sm:p-7">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300">
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-amber-600 dark:text-amber-300">Brouillon trouvé</p>
                        <h2 id="course-create-draft-title" class="mt-1 text-2xl font-black text-slate-900 dark:text-white">Reprendre le brouillon ?</h2>
                        <p id="course-create-draft-desc" class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">Un brouillon local est disponible sur cet appareil.</p>
                        <p data-course-create-draft-meta class="mt-2 text-xs font-semibold text-slate-500 dark:text-slate-400">Choisissez une action pour continuer.</p>
                    </div>
                </div>
                <div class="grid gap-3 border-t border-slate-200 bg-slate-50 p-4 sm:grid-cols-2 dark:border-slate-800 dark:bg-slate-950/40 sm:p-5">
                    <button type="button" id="course-create-continue-draft-btn" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-primary-600 px-5 py-3.5 text-sm font-semibold text-white shadow-lg transition hover:bg-primary-700">
                        <i class="fas fa-play"></i> Continuer avec un brouillon
                    </button>
                    <button type="button" id="course-create-new-course-btn" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                        <i class="fas fa-file-circle-plus"></i> Créer un nouveau cours
                    </button>
                </div>
            </section>

            <section data-course-create-draft-list-panel hidden class="border-t border-slate-200 px-6 pb-5 pt-4 dark:border-slate-800 sm:px-7">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Brouillons disponibles</p>
                        <h3 class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">Choisissez un brouillon à ouvrir</h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="<?php echo e(site_url('teacher/courses?status=draft')); ?>" class="text-xs font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-300">Voir tous les brouillons</a>
                        <button type="button" id="course-create-back-to-choice-btn" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                            <i class="fas fa-arrow-left text-[10px]"></i> Retour
                        </button>
                    </div>
                </div>
                <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50/80 p-4 dark:border-amber-500/20 dark:bg-amber-500/10">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-amber-700 dark:text-amber-200">Brouillon local</p>
                    <p class="mt-1 text-sm text-amber-900/80 dark:text-amber-100/80">Le brouillon enregistré sur cet appareil est prêt à être repris.</p>
                    <button type="button" id="course-create-restore-local-draft-btn" class="mt-3 inline-flex items-center justify-center gap-2 rounded-2xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-700">
                        <i class="fas fa-rotate-right"></i> Reprendre ce brouillon local
                    </button>
                </div>
                <?php if ($teacherDraftCourses !== []): ?>
                    <div class="mt-4 space-y-3">
                        <?php foreach (array_slice($teacherDraftCourses, 0, 6) as $draftCourse): ?>
                            <?php
                            $draftId = max(0, (int) ($draftCourse['id'] ?? 0));
                            if ($draftId < 1) {
                                continue;
                            }
                            $draftTitle = trim((string) ($draftCourse['title'] ?? 'Cours sans titre'));
                            $draftCategory = trim((string) ($draftCourse['category_name'] ?? ''));
                            $draftLevel = trim((string) ($draftCourse['level'] ?? 'beginner'));
                            $draftLevelLabel = $draftLevelLabels[$draftLevel] ?? 'Débutant';
                            $draftUpdated = $formatDraftDate($draftCourse['updated_at'] ?? ($draftCourse['created_at'] ?? null));
                            $draftChapters = max(0, (int) ($draftCourse['chapters_cnt'] ?? 0));
                            $draftLessons = max(0, (int) ($draftCourse['lessons_cnt'] ?? 0));
                            $draftMeetings = max(0, (int) ($draftCourse['meetings_cnt'] ?? 0));
                            $draftStatus = strtolower(trim((string) ($draftCourse['status'] ?? 'draft')));
                            $draftStatusLabel = $draftStatus === 'rejected' ? 'Refusé' : 'Brouillon';
                            ?>
                            <a href="<?php echo e(site_url('teacher/course_edit?id=' . $draftId)); ?>" class="group block rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:border-blue-200 hover:bg-blue-50 dark:border-slate-700 dark:bg-slate-800/60 dark:hover:border-blue-500/40 dark:hover:bg-blue-500/10">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-slate-900 dark:text-white"><?php echo e($draftTitle); ?></p>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                            <?php if ($draftCategory !== ''): ?>
                                                <span><?php echo e($draftCategory); ?></span>
                                                <span class="mx-1">•</span>
                                            <?php endif; ?>
                                            <span><?php echo e($draftLevelLabel); ?></span>
                                            <?php if ($draftUpdated !== ''): ?>
                                                <span class="mx-1">•</span>
                                                <span>Mis à jour le <?php echo e($draftUpdated); ?></span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <span class="shrink-0 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-[11px] font-bold text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-200"><?php echo e($draftStatusLabel); ?></span>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-500 dark:text-slate-400">
                                    <span class="rounded-full bg-white px-2.5 py-1 font-medium shadow-sm dark:bg-slate-900/70"><?php echo e($formatCountLabel($draftChapters, 'chapitre', 'chapitres')); ?></span>
                                    <span class="rounded-full bg-white px-2.5 py-1 font-medium shadow-sm dark:bg-slate-900/70"><?php echo e($formatCountLabel($draftLessons, 'leçon', 'leçons')); ?></span>
                                    <span class="rounded-full bg-white px-2.5 py-1 font-medium shadow-sm dark:bg-slate-900/70"><?php echo e($formatCountLabel($draftMeetings, 'rendez-vous', 'rendez-vous')); ?></span>
                                </div>
                                <div class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-blue-600 transition group-hover:text-blue-700 dark:text-blue-300">
                                    <span>Ouvrir ce brouillon</span>
                                    <i class="fas fa-arrow-right text-xs"></i>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($teacherDraftCourses) > 6): ?>
                        <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">Seuls les 6 brouillons les plus récents sont affichés ici.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">Aucun brouillon enregistré n’est disponible pour le moment.</p>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <div id="course-preview-modal" hidden role="dialog" aria-modal="true" aria-labelledby="course-preview-modal-title" aria-describedby="course-preview-modal-desc" class="fixed inset-0 z-[95] flex items-start justify-center overflow-y-auto px-4 py-6" style="z-index: 95; display: none;">
        <div class="absolute inset-0 bg-slate-950/75 backdrop-blur-sm" data-course-preview-backdrop aria-hidden="true"></div>
        <div class="relative z-20 my-0 w-full max-w-7xl min-h-[calc(100vh-2rem)] overflow-visible rounded-[2rem] border border-slate-200 bg-white shadow-2xl pointer-events-auto dark:border-slate-700 dark:bg-gray-900">
            <div class="sticky top-0 z-30 flex flex-col border-b border-slate-200 bg-white/95 px-5 py-5 backdrop-blur-sm sm:px-6 dark:border-slate-800 dark:bg-gray-900/95 lg:flex-row lg:items-start lg:justify-between lg:gap-4">
                <div class="min-w-0">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-sky-600 dark:text-sky-300">Aperçu complet</p>
                    <h2 id="course-preview-modal-title" class="mt-1 text-2xl font-black text-slate-900 dark:text-white">Prévisualisation du cours</h2>
                    <p id="course-preview-modal-desc" class="mt-2 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300">Voici une vue d’ensemble du cours tel qu’il sera perçu à l’étape actuelle. Les éléments incomplets restent visibles pour que vous puissiez les corriger avant publication.</p>
                </div>
                <div class="mt-4 flex flex-wrap items-center gap-2 lg:mt-0">
                    <button type="button" data-preview-publish class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700">
                        <i class="fas fa-rocket"></i> Publier
                    </button>
                    <button type="button" data-course-preview-close class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                        <i class="fas fa-xmark"></i> Fermer
                    </button>
                </div>
            </div>

            <div class="p-5 sm:p-6">
                <div data-course-preview-alert class="mb-4 hidden rounded-2xl border border-amber-200 bg-amber-50/90 p-4 text-sm text-amber-900 shadow-sm dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-100">
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-200" aria-hidden="true">
                            <i class="fas fa-triangle-exclamation"></i>
                        </span>
                        <div class="min-w-0">
                            <p class="font-semibold" data-course-preview-alert-title>Quelques éléments sont encore à compléter</p>
                            <p class="mt-1 text-sm leading-6 text-amber-900/80 dark:text-amber-100/80" data-course-preview-alert-message></p>
                        </div>
                    </div>
                </div>

                <div class="mb-4 grid gap-3 md:grid-cols-2">
                    <article class="rounded-[1.35rem] border border-violet-200 bg-violet-50/90 p-4 shadow-sm dark:border-violet-500/20 dark:bg-violet-500/10">
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-violet-600 dark:text-violet-200">Contenu du cours</p>
                        <p class="mt-2 text-lg font-black text-violet-950 dark:text-violet-50" data-course-preview-content-summary>Structure à afficher</p>
                        <p class="mt-1 text-sm text-violet-900/80 dark:text-violet-100/80">Modules, leçons et progression du parcours.</p>
                    </article>
                    <article class="rounded-[1.35rem] border border-rose-200 bg-rose-50/90 p-4 shadow-sm dark:border-rose-500/20 dark:bg-rose-500/10">
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-rose-600 dark:text-rose-200">Examen</p>
                        <p class="mt-2 text-lg font-black text-rose-950 dark:text-rose-50" data-course-preview-exam-summary>Évaluation à afficher</p>
                        <p class="mt-1 text-sm text-rose-900/80 dark:text-rose-100/80">Format, durée et niveau de contrôle final.</p>
                    </article>
                </div>

                <div class="grid gap-4 xl:grid-cols-[minmax(0,1.08fr)_minmax(0,0.92fr)]">
                    <section class="space-y-4">
                        <article class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-slate-50 shadow-sm dark:border-slate-800 dark:bg-slate-950/40">
                            <div class="relative">
                                <img id="course-preview-cover" class="h-64 w-full object-cover sm:h-72" src="<?php echo e($courseCoverUrl !== '' ? $courseCoverUrl : 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw=='); ?>" alt="Aperçu miniature du cours" <?php echo $courseCoverUrl !== '' ? '' : 'data-course-preview-cover-empty="1"'; ?>>
                                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/70 via-slate-950/10 to-transparent"></div>
                                <div class="absolute left-4 right-4 top-4 flex flex-wrap gap-2">
                                    <span class="rounded-full bg-white/95 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.14em] text-slate-700 shadow-sm dark:bg-slate-900/90 dark:text-slate-200" data-course-preview-status>Brouillon</span>
                                    <span class="rounded-full bg-sky-500/90 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.14em] text-white shadow-sm" data-course-preview-stage>Vue globale</span>
                                </div>
                                <div class="absolute bottom-0 left-0 right-0 p-4 sm:p-5">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-100/80">Ce que verra l’apprenant</p>
                                    <p id="course-preview-modal-subtitle" class="mt-1 text-lg font-black text-white sm:text-xl">Miniature du cours</p>
                                </div>
                            </div>
                            <div class="space-y-4 p-4 sm:p-5">
                                <div>
                                    <p id="course-preview-title" class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">Titre du cours</p>
                                    <p id="course-preview-description" class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">La description du cours apparaîtra ici.</p>
                                </div>

                                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">Catégorie</p>
                                        <p id="course-preview-category" class="mt-2 text-sm font-semibold text-slate-900 dark:text-white">Non catégorisé</p>
                                    </article>
                                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">Niveau</p>
                                        <p id="course-preview-level" class="mt-2 text-sm font-semibold text-slate-900 dark:text-white">Débutant</p>
                                    </article>
                                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">Durée</p>
                                        <p id="course-preview-duration" class="mt-2 text-sm font-semibold text-slate-900 dark:text-white">0 min</p>
                                    </article>
                                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">Prix</p>
                                        <p id="course-preview-price" class="mt-2 text-sm font-semibold text-slate-900 dark:text-white">Gratuit</p>
                                    </article>
                                </div>

                                <article class="rounded-[1.5rem] border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-fuchsia-500 dark:text-fuchsia-300">Tarification entreprise</p>
                                            <p id="course-preview-enterprise" class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">Aucune offre dédiée</p>
                                        </div>
                                        <span class="rounded-full bg-fuchsia-100 px-3 py-1 text-[11px] font-bold text-fuchsia-700 dark:bg-fuchsia-500/15 dark:text-fuchsia-200">Optionnel</span>
                                    </div>
                                </article>
                            </div>
                        </article>

                        <div class="grid gap-4 lg:grid-cols-3">
                            <article class="rounded-[1.5rem] border border-blue-200 bg-blue-50/90 p-4 shadow-sm dark:border-blue-500/20 dark:bg-blue-500/10">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-blue-900 dark:text-blue-100">Objectifs</p>
                                    <span class="rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-bold text-blue-700 dark:bg-slate-900/80 dark:text-blue-100" data-course-preview-objectives-count>0</span>
                                </div>
                                <ul id="course-preview-objectives" class="mt-3 space-y-2 text-sm text-blue-950/90 dark:text-blue-50"></ul>
                            </article>
                            <article class="rounded-[1.5rem] border border-amber-200 bg-amber-50/90 p-4 shadow-sm dark:border-amber-500/20 dark:bg-amber-500/10">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-amber-900 dark:text-amber-100">Prérequis</p>
                                    <span class="rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-bold text-amber-700 dark:bg-slate-900/80 dark:text-amber-100" data-course-preview-prerequisites-count>0</span>
                                </div>
                                <ul id="course-preview-prerequisites" class="mt-3 space-y-2 text-sm text-amber-950/90 dark:text-amber-50"></ul>
                            </article>
                            <article class="rounded-[1.5rem] border border-teal-200 bg-teal-50/90 p-4 shadow-sm dark:border-teal-500/20 dark:bg-teal-500/10">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-teal-900 dark:text-teal-100">Équipement</p>
                                    <span class="rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-bold text-teal-700 dark:bg-slate-900/80 dark:text-teal-100" data-course-preview-equipment-count>0</span>
                                </div>
                                <ul id="course-preview-equipment" class="mt-3 space-y-2 text-sm text-teal-950/90 dark:text-teal-50"></ul>
                            </article>
                        </div>
                    </section>

                    <aside class="space-y-4">
                        <article class="rounded-[1.5rem] border border-violet-200 bg-violet-50/80 p-4 shadow-sm dark:border-violet-500/20 dark:bg-violet-500/10">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-violet-600 dark:text-violet-200">Contenu</p>
                                    <h3 class="mt-1 text-base font-black text-violet-950 dark:text-violet-50">Modules & leçons</h3>
                                </div>
                                <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-bold text-violet-700 shadow-sm dark:bg-violet-500/15 dark:text-violet-100" data-course-preview-module-count>0 module</span>
                            </div>
                            <div id="course-preview-modules" class="mt-4 space-y-3"></div>
                        </article>

                        <article class="rounded-[1.5rem] border border-rose-200 bg-rose-50/80 p-4 shadow-sm dark:border-rose-500/20 dark:bg-rose-500/10">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-rose-600 dark:text-rose-200">Évaluation</p>
                                    <h3 class="mt-1 text-base font-black text-rose-950 dark:text-rose-50">Contrôle final</h3>
                                </div>
                                <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-bold text-rose-700 shadow-sm dark:bg-rose-500/15 dark:text-rose-100" data-course-preview-exam-count>0 évaluation</span>
                            </div>
                            <div id="course-preview-exams" class="mt-4 space-y-3"></div>
                        </article>

                        <article class="rounded-[1.5rem] border border-cyan-200 bg-gradient-to-br from-cyan-50 to-sky-50 p-4 shadow-sm dark:border-cyan-500/20 dark:from-cyan-500/10 dark:to-slate-900/80">
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-cyan-600 dark:text-cyan-200">Repères rapides</p>
                            <div class="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-1">
                                <div class="rounded-2xl border border-slate-200 bg-white/90 p-3 text-sm text-slate-700 shadow-sm dark:border-slate-800 dark:bg-slate-900/80 dark:text-slate-200">
                                    <span class="block text-[11px] font-bold uppercase tracking-[0.16em] text-cyan-500 dark:text-cyan-300">Ressource générale</span>
                                    <span id="course-preview-general-resource" class="mt-1 block font-semibold text-slate-900 dark:text-white">Aucune ressource générale</span>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-white/90 p-3 text-sm text-slate-700 shadow-sm dark:border-slate-800 dark:bg-slate-900/80 dark:text-slate-200">
                                    <span class="block text-[11px] font-bold uppercase tracking-[0.16em] text-cyan-500 dark:text-cyan-300">État</span>
                                    <span id="course-preview-readiness" class="mt-1 block font-semibold text-slate-900 dark:text-white">En cours de préparation</span>
                                </div>
                            </div>
                        </article>
                    </aside>
                </div>
            </div>
        </div>
    </div>

    <div id="toast" class="fixed bottom-6 right-6 bg-slate-900 text-white px-5 py-3 rounded-xl shadow-elevated text-sm font-medium transform translate-y-20 opacity-0 transition-all duration-300 z-50 pointer-events-none flex items-center gap-2">
        <i id="toast-icon" class="fas fa-check-circle text-emerald-400"></i>
        <span id="toast-msg"></span>
    </div>
</body>
</html>
