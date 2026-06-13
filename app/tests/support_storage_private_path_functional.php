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
require_once $rootDir . '/includes/support.php';

/**
 * @param array<int,string> $errors
 */
function assert_true(bool $condition, string $message, array &$errors): void
{
    if (!$condition) {
        $errors[] = $message;
    }
}

$errors = [];
$tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'support-private-' . bin2hex(random_bytes(8));
$filename = 'attachment-test.txt';
$filePath = $tempDir . DIRECTORY_SEPARATOR . $filename;
$legacyDir = $rootDir . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'support';
$legacyFilename = 'legacy-support-' . bin2hex(random_bytes(6)) . '.txt';
$legacyFilePath = $legacyDir . DIRECTORY_SEPARATOR . $legacyFilename;
$originalSupportDir = getenv('APP_SUPPORT_STORAGE_DIR');

putenv('APP_SUPPORT_STORAGE_DIR=' . $tempDir);

$service = new \App\Services\Support\SupportStorageService();
assert_true($service->storageDir() === $tempDir, 'Support storage service should honor APP_SUPPORT_STORAGE_DIR.', $errors);
assert_true($service->ensureStorage() === true, 'Support storage directory should be created.', $errors);

file_put_contents($filePath, 'private attachment');
$resolved = $service->resolveStoredPath('support://' . $filename);
assert_true($resolved === realpath($filePath), 'support:// reference should resolve to the private directory.', $errors);

if (!is_dir($legacyDir)) {
    mkdir($legacyDir, 0755, true);
}
file_put_contents($legacyFilePath, 'legacy attachment');
$legacyResolved = $service->resolveStoredPath('storage/support/' . $legacyFilename);
assert_true($legacyResolved === realpath($legacyFilePath), 'Legacy support paths should remain readable during migration.', $errors);
assert_true(support_storage_reference($filename) === 'support://' . $filename, 'Helper should emit support:// references.', $errors);
assert_true(support_resolve_stored_path('support://' . $filename) === realpath($filePath), 'Helper should resolve support:// references.', $errors);

@unlink($filePath);
@unlink($legacyFilePath);
cleanup_tree($tempDir);
putenv($originalSupportDir === false ? 'APP_SUPPORT_STORAGE_DIR' : 'APP_SUPPORT_STORAGE_DIR=' . $originalSupportDir);

if ($errors !== []) {
    echo "Support storage private path functional FAILED\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "Support storage private path functional OK\n";
exit(0);

function cleanup_tree(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $items = scandir($dir);
    if (!is_array($items)) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            cleanup_tree($path);
        } elseif (is_file($path)) {
            @unlink($path);
        }
    }

    @rmdir($dir);
}
