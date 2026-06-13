<?php
// Helpers support technique : roles, stockage, notifications, uploads.

declare(strict_types=1);

if (!function_exists('support_roles_from_user')) {
    function support_roles_from_user(?array $user): array
    {
        if (!$user) {
            return [];
        }
        $roles = json_decode($user['roles_json'] ?? '[]', true);
        return is_array($roles) ? $roles : [];
    }
}

if (!function_exists('support_is_staff')) {
    function support_is_staff(array $roles): bool
    {
        return in_array('admin', $roles, true) || in_array('support', $roles, true);
    }
}

if (!function_exists('support_storage_dir')) {
    function support_storage_dir(): string
    {
        $configured = trim((string) getenv('APP_SUPPORT_STORAGE_DIR'));
        if ($configured !== '') {
            return rtrim($configured, "\\/");
        }

        return dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'app-private' . DIRECTORY_SEPARATOR . 'ecolenumerique' . DIRECTORY_SEPARATOR . 'support';
    }
}

if (!function_exists('support_ensure_storage')) {
    function support_ensure_storage(): bool
    {
        $dir = support_storage_dir();
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0700, true) && !is_dir($dir)) {
                return false;
            }
        }
        $htaccess = $dir . '/.htaccess';
        if (!is_file($htaccess)) {
            $content = "Options -Indexes\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Deny from all\n</IfModule>\n";
            @file_put_contents($htaccess, $content);
        }
        return true;
    }
}

if (!function_exists('support_allowed_upload_map')) {
    function support_allowed_upload_map(): array
    {
        return [
            'pdf' => ['application/pdf'],
            'png' => ['image/png'],
            'jpg' => ['image/jpeg', 'image/pjpeg'],
            'jpeg' => ['image/jpeg', 'image/pjpeg'],
            'webp' => ['image/webp'],
            'txt' => ['text/plain'],
            'csv' => ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel'],
        ];
    }
}

if (!function_exists('support_normalize_files')) {
    function support_normalize_files(array $files): array
    {
        if (!isset($files['name'])) {
            return [];
        }
        if (!is_array($files['name'])) {
            return [$files];
        }
        $out = [];
        foreach ($files['name'] as $idx => $name) {
            $out[] = [
                'name' => $name,
                'type' => $files['type'][$idx] ?? '',
                'tmp_name' => $files['tmp_name'][$idx] ?? '',
                'error' => $files['error'][$idx] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$idx] ?? 0,
            ];
        }
        return $out;
    }
}

if (!function_exists('support_sanitize_filename')) {
    function support_sanitize_filename(string $name): string
    {
        $name = basename($name);
        $name = preg_replace('/[\x00-\x1F\x7F]+/', '', $name);
        $name = trim((string) $name);
        if ($name === '') {
            $name = 'fichier';
        }
        if (strlen($name) > 180) {
            $name = substr($name, 0, 180);
        }
        return $name;
    }
}

if (!function_exists('support_storage_reference')) {
    function support_storage_reference(string $stored): string
    {
        return 'support://' . ltrim($stored, '/\\');
    }
}

if (!function_exists('support_resolve_stored_path')) {
    function support_resolve_stored_path(string $storedPath): ?string
    {
        $storedPath = trim($storedPath);
        if ($storedPath === '') {
            return null;
        }

        $allowedBases = [];
        $currentBase = realpath(support_storage_dir());
        if (is_string($currentBase) && $currentBase !== '') {
            $allowedBases[] = $currentBase;
        }
        $legacyBase = realpath(__DIR__ . '/../storage/support');
        if (is_string($legacyBase) && $legacyBase !== '') {
            $allowedBases[] = $legacyBase;
        }

        $candidates = [];
        if (str_starts_with($storedPath, 'support://')) {
            $candidates[] = support_storage_dir() . DIRECTORY_SEPARATOR . basename(substr($storedPath, strlen('support://')));
        } elseif (preg_match('/^[A-Za-z]:\\\\|^\//', $storedPath) === 1) {
            $candidates[] = $storedPath;
        } elseif (str_starts_with(str_replace('\\', '/', $storedPath), 'storage/support/')) {
            $candidates[] = __DIR__ . '/../' . ltrim(str_replace('\\', '/', $storedPath), '/');
        } else {
            $candidates[] = support_storage_dir() . DIRECTORY_SEPARATOR . basename($storedPath);
        }

        foreach ($candidates as $candidate) {
            $realPath = realpath($candidate);
            if (!is_string($realPath) || $realPath === '' || !is_file($realPath)) {
                continue;
            }

            foreach ($allowedBases as $baseDir) {
                $basePrefix = rtrim($baseDir, "\\/") . DIRECTORY_SEPARATOR;
                if ($realPath === $baseDir || str_starts_with($realPath, $basePrefix)) {
                    return $realPath;
                }
            }
        }

        return null;
    }
}

if (!function_exists('support_handle_uploads')) {
    function support_handle_uploads(?array $files, int $maxBytes = 5242880): array
    {
        $normalized = $files ? support_normalize_files($files) : [];
        if (!$normalized) {
            return ['ok' => true, 'attachments' => [], 'errors' => []];
        }
        if (!support_ensure_storage()) {
            return ['ok' => false, 'attachments' => [], 'errors' => ['Stockage indisponible.']];
        }

        $attachments = [];
        $errors = [];
        $finfo = function_exists('finfo_open') ? new finfo(FILEINFO_MIME_TYPE) : null;
        $allowedMap = support_allowed_upload_map();

        foreach ($normalized as $file) {
            $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($err === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($err !== UPLOAD_ERR_OK) {
                $errors[] = 'Erreur upload (code ' . (string) $err . ').';
                continue;
            }
            $size = (int) ($file['size'] ?? 0);
            if ($size > $maxBytes) {
                $errors[] = 'Fichier trop volumineux (max 5 Mo).';
                continue;
            }
            $tmp = (string) ($file['tmp_name'] ?? '');
            if ($tmp === '' || !is_uploaded_file($tmp)) {
                $errors[] = 'Upload invalide.';
                continue;
            }
            $orig = support_sanitize_filename((string) ($file['name'] ?? ''));
            $ext = strtolower((string) pathinfo($orig, PATHINFO_EXTENSION));
            $ext = preg_replace('/[^A-Za-z0-9]+/', '', (string) $ext);
            $mime = '';
            if ($finfo) {
                $mime = (string) ($finfo->file($tmp) ?: '');
            }
            if ($mime === '') {
                $mime = (string) ($file['type'] ?? '');
            }
            $mime = strtolower(trim($mime));
            if ($ext === '' || !isset($allowedMap[$ext]) || !in_array($mime, $allowedMap[$ext], true)) {
                $errors[] = 'Type de fichier interdit. Formats autorises: PDF, PNG, JPG, JPEG, WEBP, TXT, CSV.';
                continue;
            }

            $stored = 'support_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $dest = support_storage_dir() . DIRECTORY_SEPARATOR . $stored;
            if (!move_uploaded_file($tmp, $dest)) {
                $errors[] = 'Echec de l upload.';
                continue;
            }
            @chmod($dest, 0600);
            $attachments[] = [
                'file_path' => support_storage_reference($stored),
                'original_name' => $orig,
                'mime' => $mime !== '' ? $mime : 'application/octet-stream',
                'size' => $size,
            ];
        }

        if ($errors) {
            foreach ($attachments as $att) {
                $path = support_resolve_stored_path((string) $att['file_path']);
                if ($path !== null && is_file($path)) {
                    @unlink($path);
                }
            }
            return ['ok' => false, 'attachments' => [], 'errors' => $errors];
        }

        return ['ok' => true, 'attachments' => $attachments, 'errors' => []];
    }
}

if (!function_exists('support_notify_users')) {
    function support_notify_users(array $userIds, string $title, string $body, ?string $url = null): void
    {
        if (!$userIds) {
            return;
        }
        $unique = [];
        foreach ($userIds as $uid) {
            $uid = (int) $uid;
            if ($uid > 0) {
                $unique[$uid] = true;
            }
        }
        if (!$unique) {
            return;
        }
        global $pdo;
        $stmt = $pdo->prepare('INSERT INTO notifications (user_id, title, body, url, created_at) VALUES (:uid, :title, :body, :url, NOW())');
        foreach (array_keys($unique) as $uid) {
            $stmt->execute([
                ':uid' => $uid,
                ':title' => $title,
                ':body' => $body,
                ':url' => $url,
            ]);
        }
    }
}

if (!function_exists('support_staff_ids')) {
    function support_staff_ids(): array
    {
        global $pdo;
        try {
            $rows = $pdo->query('SELECT id FROM users WHERE roles_json LIKE \'%"admin"%\' OR roles_json LIKE \'%"support"%\'')->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
        $ids = [];
        foreach ($rows ?: [] as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        return $ids;
    }
}
