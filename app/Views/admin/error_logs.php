<?php
$logOptions = is_array($logOptions ?? null) ? $logOptions : [];
$activeLog = (string) ($activeLog ?? '');
$limit = max(50, (int) ($limit ?? 300));
$logEntries = is_array($logEntries ?? null) ? $logEntries : [];
$logWarnings = is_array($logWarnings ?? null) ? $logWarnings : [];
$siteLogoUrl = (string) ($siteLogoUrl ?? '');
$quickFilter = (string) ($quickFilter ?? 'all');
$quickFilterOptions = is_array($quickFilterOptions ?? null) ? $quickFilterOptions : [];
$searchQuery = trim((string) ($searchQuery ?? ''));
$rawLineCount = max(0, (int) ($rawLineCount ?? count($logEntries)));
$filteredLineCount = max(0, (int) ($filteredLineCount ?? count($logEntries)));
$severitySummary = is_array($severitySummary ?? null) ? $severitySummary : [];
$logMeta = is_array($logMeta ?? null) ? $logMeta : [];

$logPath = (string) ($logMeta['path'] ?? '');
$logReadable = !empty($logMeta['readable']);
$sizeLabel = (string) ($logMeta['size_label'] ?? '');
$updatedAt = (string) ($logMeta['updated_at'] ?? '');
$sourceLabel = (string) ($logOptions[$activeLog]['label'] ?? 'Journal');
$displayedCount = max(0, $filteredLineCount);
$logPage = \App\Support\Pagination::pageFromQuery($_GET, 'page', (int) ($page ?? 1));
$logPagination = \App\Support\Pagination::build($displayedCount, $logPage, $limit);
$logEntries = \App\Support\Pagination::slice($logEntries, $logPagination);
$criticalCount = (int) ($severitySummary['critical'] ?? 0);
$errorCount = (int) ($severitySummary['error'] ?? 0);
$warningCount = (int) ($severitySummary['warning'] ?? 0);
$infoCount = (int) ($severitySummary['info'] ?? 0);

$optionLabel = static function (array $option): string {
    $label = (string) ($option['label'] ?? 'Journal');
    $path = (string) ($option['path'] ?? '');
    return $path === '' ? $label . ' (indisponible)' : $label;
};

$buildUrl = static function (array $overrides = []) use ($activeLog, $limit, $quickFilter, $searchQuery): string {
    $params = [
        'log' => $activeLog,
        'limit' => $limit,
        'filter' => $quickFilter,
    ];
    if ($searchQuery !== '') {
        $params['q'] = $searchQuery;
    }
    foreach ($overrides as $key => $value) {
        if ($value === null) {
            unset($params[$key]);
            continue;
        }
        $params[(string) $key] = $value;
    }
    $query = http_build_query($params);
    return site_url('admin/error_logs') . ($query !== '' ? '?' . $query : '');
};

$severityBadgeClass = static function (string $tone): string {
    return match ($tone) {
        'critical' => 'admin-log-badge admin-log-badge--critical',
        'danger' => 'admin-log-badge admin-log-badge--error',
        'warning' => 'admin-log-badge admin-log-badge--warning',
        'info' => 'admin-log-badge admin-log-badge--info',
        default => 'admin-log-badge admin-log-badge--unknown',
    };
};

$severityRowClass = static function (string $key): string {
    return match ($key) {
        'critical' => 'admin-log-row--critical',
        'error' => 'admin-log-row--error',
        'warning' => 'admin-log-row--warning',
        'info' => 'admin-log-row--info',
        default => 'admin-log-row--unknown',
    };
};

$severityIcon = static function (string $key): string {
    return match ($key) {
        'critical' => 'fa-skull-crossbones',
        'error' => 'fa-exclamation-circle',
        'warning' => 'fa-exclamation-triangle',
        'info' => 'fa-info-circle',
        default => 'fa-question-circle',
    };
};

$filterIcon = static function (string $key): string {
    return match ($key) {
        'google' => 'fa-key',
        'drive' => 'fa-cloud',
        'fedapay' => 'fa-credit-card',
        'mail' => 'fa-envelope',
        'error' => 'fa-bug',
        'warning' => 'fa-exclamation-triangle',
        default => 'fa-layer-group',
    };
};

$adminNavActive = 'error_logs';
$adminTopTitle = 'Logs & erreurs';
$adminTopSubtitle = 'Supervision système';
$adminTopBadge = $logReadable ? ($displayedCount > 0 ? ($displayedCount . ' lignes') : 'Aucun log') : 'Indisponible';
$adminTopBadgeTone = $logReadable && ($criticalCount + $errorCount) > 0 ? 'urgent' : 'done';
$adminTopActions = [
    ['label' => 'Tableau de bord', 'url' => site_url('admin/dashboard'), 'tone' => 'neutral'],
    ['label' => 'Support', 'url' => site_url('admin/support'), 'tone' => 'neutral'],
];

$buildLogPageUrl = static function (int $targetPage) use ($buildUrl): string {
    return $buildUrl(['page' => $targetPage > 1 ? max(1, $targetPage) : null]);
};
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Journal des logs et erreurs - Administration</title>
    <?php require __DIR__ . '/_head_assets.php'; ?>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body class="app-view app-role-admin min-h-screen bg-slate-50 text-slate-900 font-body dark:bg-slate-950 dark:text-slate-100" data-view="admin/error_logs" data-sidebar-desktop-toggle="1">
    <div class="admin-shell-backdrop">
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--one"></div>
        <div class="admin-shell-backdrop__orb admin-shell-backdrop__orb--two"></div>
    </div>

    <div class="admin-shell-root relative z-10 flex min-h-screen">
        <?php require __DIR__ . '/_sidebar.php'; ?>
        <div class="js-sidebar-backdrop fixed inset-0 z-30 bg-slate-950/60 opacity-0 pointer-events-none transition md:hidden"></div>

        <div class="admin-shell-main flex min-w-0 flex-1 flex-col">
            <?php require __DIR__ . '/_topbar.php'; ?>

            <main class="admin-shell-content admin-error-logs-page px-4 py-5 sm:px-6">
                <section class="admin-error-logs-hero">
                    <div class="admin-error-logs-hero__copy">
                        <p class="admin-error-logs-kicker"><i class="fas fa-shield-alt" aria-hidden="true"></i> Surveillance</p>
                        <h2><i class="fas fa-bug" aria-hidden="true"></i> Journal des erreurs & logs système</h2>
                        <p>Filtrez les journaux et exportez l’état courant en JSON.</p>
                    </div>
                    <div class="admin-error-logs-hero__actions">
                        <a href="<?php echo e($buildUrl()); ?>" class="admin-error-logs-action">
                            <i class="fas fa-sync-alt" aria-hidden="true"></i>
                            <span>Rafraîchir</span>
                        </a>
                        <a href="<?php echo e($buildUrl(['export' => 'json'])); ?>" class="admin-error-logs-action">
                            <i class="fas fa-download" aria-hidden="true"></i>
                            <span>Exporter JSON</span>
                        </a>
                    </div>
                </section>

                <section class="admin-error-logs-summary" aria-label="Résumé des logs">
                    <article class="admin-error-log-stat admin-error-log-stat--blue">
                        <span><i class="fas fa-database" aria-hidden="true"></i></span>
                        <p>Total affiché</p>
                        <strong><?php echo e((string) $displayedCount); ?></strong>
                        <small><?php echo e((string) $rawLineCount); ?> lignes lues</small>
                    </article>
                    <article class="admin-error-log-stat admin-error-log-stat--rose">
                        <span><i class="fas fa-skull-crossbones" aria-hidden="true"></i></span>
                        <p>Critiques</p>
                        <strong><?php echo e((string) $criticalCount); ?></strong>
                        <small>À traiter en priorité</small>
                    </article>
                    <article class="admin-error-log-stat admin-error-log-stat--red">
                        <span><i class="fas fa-exclamation-circle" aria-hidden="true"></i></span>
                        <p>Erreurs</p>
                        <strong><?php echo e((string) $errorCount); ?></strong>
                        <small>Incidents détectés</small>
                    </article>
                    <article class="admin-error-log-stat admin-error-log-stat--amber">
                        <span><i class="fas fa-exclamation-triangle" aria-hidden="true"></i></span>
                        <p>Avertissements</p>
                        <strong><?php echo e((string) $warningCount); ?></strong>
                        <small>Points à surveiller</small>
                    </article>
                    <article class="admin-error-log-stat admin-error-log-stat--green">
                        <span><i class="fas fa-info-circle" aria-hidden="true"></i></span>
                        <p>Informations</p>
                        <strong><?php echo e((string) $infoCount); ?></strong>
                        <small>Événements système</small>
                    </article>
                </section>

                <section class="admin-error-logs-meta">
                    <div>
                        <span><i class="fas fa-folder-open" aria-hidden="true"></i> Source</span>
                        <strong><?php echo e($sourceLabel); ?></strong>
                    </div>
                    <div>
                        <span><i class="fas fa-weight-hanging" aria-hidden="true"></i> Taille</span>
                        <strong><?php echo e($sizeLabel !== '' ? $sizeLabel : '-'); ?></strong>
                    </div>
                    <div>
                        <span><i class="fas fa-clock" aria-hidden="true"></i> Mise à jour</span>
                        <strong><?php echo e($updatedAt !== '' ? $updatedAt : '-'); ?></strong>
                    </div>
                    <div>
                        <span><i class="fas fa-file-code" aria-hidden="true"></i> Fichier</span>
                        <strong class="admin-error-logs-meta__path"><?php echo e($logPath !== '' ? $logPath : '-'); ?></strong>
                    </div>
                </section>

                <section class="admin-error-logs-panel">
                    <form method="get" action="<?php echo e(site_url('admin/error_logs')); ?>" class="admin-error-logs-filter-form">
                        <label for="log">
                            <span><i class="fas fa-folder-open" aria-hidden="true"></i> Source</span>
                            <select id="log" name="log">
                                <?php foreach ($logOptions as $key => $option): ?>
                                    <option value="<?php echo e((string) $key); ?>" <?php echo $key === $activeLog ? 'selected' : ''; ?>>
                                        <?php echo e($optionLabel($option)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label for="filter">
                            <span><i class="fas fa-filter" aria-hidden="true"></i> Filtre</span>
                            <select id="filter" name="filter">
                                <?php foreach ($quickFilterOptions as $filterKey => $filterLabel): ?>
                                    <option value="<?php echo e((string) $filterKey); ?>" <?php echo $filterKey === $quickFilter ? 'selected' : ''; ?>>
                                        <?php echo e((string) $filterLabel); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label for="limit">
                            <span><i class="fas fa-list-ol" aria-hidden="true"></i> Lignes</span>
                            <input id="limit" name="limit" type="number" min="50" max="2000" value="<?php echo e((string) $limit); ?>">
                        </label>
                        <label for="q" class="admin-error-logs-filter-form__search">
                            <span><i class="fas fa-search" aria-hidden="true"></i> Recherche</span>
                            <input id="q" name="q" type="text" value="<?php echo e($searchQuery); ?>" placeholder="Rechercher une source, une erreur ou un message...">
                        </label>
                        <div class="admin-error-logs-filter-form__actions">
                            <button type="submit" class="admin-shell-button admin-shell-button--primary">
                                <i class="fas fa-check" aria-hidden="true"></i>
                                <span>Appliquer</span>
                            </button>
                            <a href="<?php echo e(site_url('admin/error_logs')); ?>" class="admin-shell-button admin-shell-button--secondary">
                                <i class="fas fa-undo" aria-hidden="true"></i>
                                <span>Réinitialiser</span>
                            </a>
                        </div>
                    </form>

                    <div class="admin-error-logs-quickfilters" aria-label="Filtres rapides">
                        <?php foreach ($quickFilterOptions as $filterKey => $filterLabel): ?>
                            <?php $isActiveFilter = $filterKey === $quickFilter; ?>
                            <a href="<?php echo e($buildUrl(['filter' => $filterKey])); ?>" class="<?php echo $isActiveFilter ? 'is-active' : ''; ?>">
                                <i class="fas <?php echo e($filterIcon((string) $filterKey)); ?>" aria-hidden="true"></i>
                                <?php echo e((string) $filterLabel); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($logWarnings !== []): ?>
                        <div class="admin-error-logs-warnings">
                            <?php foreach ($logWarnings as $warning): ?>
                                <p><i class="fas fa-exclamation-triangle" aria-hidden="true"></i><?php echo e((string) $warning); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="admin-error-logs-table-card">
                    <div class="admin-error-logs-table-card__head">
                        <div>
                            <p class="admin-error-logs-kicker"><i class="fas fa-terminal" aria-hidden="true"></i> Événements</p>
                            <h3>Lignes du journal</h3>
                        </div>
                        <p>
                            <?php echo e((string) $displayedCount); ?> résultat<?php echo $displayedCount > 1 ? 's' : ''; ?>
                            <?php if ($searchQuery !== ''): ?>
                                pour “<?php echo e($searchQuery); ?>”
                            <?php endif; ?>
                        </p>
                    </div>

                    <?php if ($logReadable && $logEntries !== []): ?>
                        <div class="admin-error-logs-table-wrap">
                            <table class="admin-error-logs-table">
                                <thead>
                                    <tr>
                                        <th>Niveau</th>
                                        <th>Horodatage</th>
                                        <th>Source</th>
                                        <th>Message</th>
                                        <th>Détails</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logEntries as $entry): ?>
                                        <?php
                                        $severityKey = (string) ($entry['severityKey'] ?? 'unknown');
                                        $severityTone = (string) ($entry['severityTone'] ?? 'neutral');
                                        $severityLabel = (string) ($entry['severityLabel'] ?? 'Inconnu');
                                        $channel = trim((string) ($entry['channel'] ?? ''));
                                        $message = trim((string) ($entry['message'] ?? ''));
                                        $timestamp = trim((string) ($entry['timestamp'] ?? ''));
                                        $context = trim((string) ($entry['context'] ?? ''));
                                        $raw = (string) ($entry['raw'] ?? '');
                                        ?>
                                        <tr class="admin-log-row <?php echo e($severityRowClass($severityKey)); ?>">
                                            <td>
                                                <span class="<?php echo e($severityBadgeClass($severityTone)); ?>">
                                                    <i class="fas <?php echo e($severityIcon($severityKey)); ?>" aria-hidden="true"></i>
                                                    <?php echo e($severityLabel); ?>
                                                </span>
                                            </td>
                                            <td class="admin-error-logs-table__date"><?php echo e($timestamp !== '' ? $timestamp : '-'); ?></td>
                                            <td>
                                                <span class="admin-log-source"><i class="fas fa-cube" aria-hidden="true"></i><?php echo e($channel !== '' ? $channel : 'system'); ?></span>
                                            </td>
                                            <td class="admin-error-logs-table__message">
                                                <?php echo e($message !== '' ? $message : $raw); ?>
                                                <?php if ($context !== ''): ?>
                                                    <pre><?php echo e($context); ?></pre>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <details class="admin-log-details">
                                                    <summary><i class="fas fa-code" aria-hidden="true"></i> Voir</summary>
                                                    <pre><?php echo e($raw); ?></pre>
                                                </details>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            <?php
                            $paginationData = $logPagination;
                            $paginationUrlBuilder = $buildLogPageUrl;
                            $paginationLabel = 'lignes';
                            $paginationAriaLabel = 'Pagination des logs';
                            require __DIR__ . '/_pagination.php';
                            ?>
                        </div>
                    <?php elseif ($logReadable): ?>
                        <div class="admin-error-logs-empty">
                            <i class="fas fa-check-circle" aria-hidden="true"></i>
                            <h3>Aucun log correspondant aux filtres</h3>
                            <p>Modifiez la recherche, le filtre ou la source sélectionnée.</p>
                        </div>
                    <?php else: ?>
                        <div class="admin-error-logs-empty admin-error-logs-empty--locked">
                            <i class="fas fa-lock" aria-hidden="true"></i>
                            <h3>Journal indisponible</h3>
                            <p>Le fichier sélectionné est introuvable, non configuré ou non lisible par PHP.</p>
                        </div>
                    <?php endif; ?>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
