<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only\n";
    exit(1);
}

$rootDir = dirname(__DIR__, 2);
chdir($rootDir);

require_once $rootDir . '/app/bootstrap.php';
$config = require $rootDir . '/includes/config.php';
require_once $rootDir . '/includes/db.php';

$commit = false;
$limit = 1000;
$keepLegacy = false;

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--commit') {
        $commit = true;
    } elseif ($arg === '--keep-legacy') {
        $keepLegacy = true;
    } elseif (str_starts_with($arg, '--limit=')) {
        $limit = (int) substr($arg, 8);
    }
}

$limit = max(1, min(10000, $limit));

/** @var PDO $pdo */
$pdo = db($config['db']);
\App\Core\PdoRegistry::set($pdo);

$storage = new \App\Services\Support\SupportStorageService();
$privateDir = $storage->storageDir();

$result = [
    'mode' => $commit ? 'commit' : 'dry-run',
    'keep_legacy' => $keepLegacy ? 1 : 0,
    'private_dir' => $privateDir,
    'rows_selected' => 0,
    'rows_updated' => 0,
    'files_moved' => 0,
    'files_missing' => 0,
    'errors' => [],
    'moves' => [],
];

if (!$storage->ensureStorage()) {
    $result['errors'][] = 'Private support storage is unavailable.';
    echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit(2);
}

$stmt = $pdo->prepare(
    'SELECT id, file_path
     FROM support_message_attachments
     WHERE file_path LIKE :legacy
     ORDER BY id ASC
     LIMIT :lim'
);
$stmt->bindValue(':legacy', 'storage/support/%', PDO::PARAM_STR);
$stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$result['rows_selected'] = count($rows);
if ($rows === []) {
    echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit(0);
}

$groups = [];
foreach ($rows as $row) {
    $legacyPath = (string) ($row['file_path'] ?? '');
    if ($legacyPath === '') {
        continue;
    }
    if (!isset($groups[$legacyPath])) {
        $groups[$legacyPath] = [];
    }
    $groups[$legacyPath][] = (int) ($row['id'] ?? 0);
}

$plannedMoves = [];
foreach ($groups as $legacyPath => $ids) {
    $source = $storage->resolveStoredPath($legacyPath);
    if ($source === null) {
        $result['files_missing']++;
        $result['errors'][] = 'Missing legacy file: ' . $legacyPath;
        continue;
    }

    $targetFilename = build_target_filename($privateDir, basename($legacyPath));
    $targetPath = $privateDir . DIRECTORY_SEPARATOR . $targetFilename;
    $newReference = 'support://' . $targetFilename;

    $plannedMoves[] = [
        'legacy_path' => $legacyPath,
        'source' => $source,
        'target_path' => $targetPath,
        'target_reference' => $newReference,
        'attachment_ids' => $ids,
    ];
}

foreach ($plannedMoves as $move) {
    $result['moves'][] = [
        'legacy_path' => $move['legacy_path'],
        'target_reference' => $move['target_reference'],
        'attachment_ids' => $move['attachment_ids'],
    ];
}

if (!$commit) {
    echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit($result['errors'] === [] ? 0 : 2);
}

$copiedTargets = [];
$deleteLegacy = [];

try {
    foreach ($plannedMoves as $move) {
        if (!copy_support_file($move['source'], $move['target_path'])) {
            throw new RuntimeException('Failed to copy ' . $move['legacy_path']);
        }
        $copiedTargets[] = $move['target_path'];
        if (!$keepLegacy) {
            $deleteLegacy[] = $move['source'];
        }
    }

    $pdo->beginTransaction();
    $update = $pdo->prepare(
        'UPDATE support_message_attachments
         SET file_path = :new_path
         WHERE file_path = :old_path'
    );

    foreach ($plannedMoves as $move) {
        $update->execute([
            ':new_path' => $move['target_reference'],
            ':old_path' => $move['legacy_path'],
        ]);
        $result['rows_updated'] += $update->rowCount();
        $result['files_moved']++;
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    foreach ($copiedTargets as $copiedTarget) {
        if (is_file($copiedTarget)) {
            @unlink($copiedTarget);
        }
    }
    $result['errors'][] = $e->getMessage();
    echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit(2);
}

if (!$keepLegacy) {
    foreach (array_values(array_unique($deleteLegacy)) as $legacySource) {
        if (is_file($legacySource)) {
            @unlink($legacySource);
        }
    }
}

echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
exit($result['errors'] === [] ? 0 : 2);

function build_target_filename(string $privateDir, string $originalName): string
{
    $filename = sanitize_target_filename($originalName);
    $base = pathinfo($filename, PATHINFO_FILENAME);
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $candidate = $filename;
    $counter = 1;

    while (is_file($privateDir . DIRECTORY_SEPARATOR . $candidate)) {
        $suffix = '_' . $counter;
        $candidate = $base . $suffix . ($ext !== '' ? '.' . $ext : '');
        $counter++;
    }

    return $candidate;
}

function sanitize_target_filename(string $name): string
{
    $name = basename($name);
    $name = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name);
    $name = trim((string) $name, '._');
    if ($name === '') {
        $name = 'attachment';
    }
    if (strlen($name) > 180) {
        $name = substr($name, 0, 180);
    }
    return $name;
}

function copy_support_file(string $source, string $target): bool
{
    $targetDir = dirname($target);
    if (!is_dir($targetDir) && !mkdir($targetDir, 0700, true) && !is_dir($targetDir)) {
        return false;
    }

    if (!copy($source, $target)) {
        return false;
    }

    @chmod($target, 0600);
    return true;
}
