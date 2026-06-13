<?php
declare(strict_types=1);

namespace App\Services\Support;

use App\Core\Service;
use finfo;

final class SupportStorageService extends Service
{
    public function storageDir(): string
    {
        $configured = trim((string) getenv('APP_SUPPORT_STORAGE_DIR'));
        if ($configured !== '') {
            return rtrim($configured, "\\/");
        }

        return dirname(__DIR__, 5) . DIRECTORY_SEPARATOR . 'app-private' . DIRECTORY_SEPARATOR . 'ecolenumerique' . DIRECTORY_SEPARATOR . 'support';
    }

    public function ensureStorage(): bool
    {
        $dir = $this->storageDir();
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

    /**
     * @param array<string,mixed> $files
     * @return array<int,array{name:mixed,type:mixed,tmp_name:mixed,error:mixed,size:mixed}>
     */
    public function normalizeFiles(array $files): array
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

    public function sanitizeFilename(string $name): string
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

    /**
     * @param array<string,mixed>|null $files
     * @return array{ok:bool,attachments:array<int,array{file_path:string,original_name:string,mime:string,size:int}>,errors:array<int,string>}
     */
    public function handleUploads(?array $files, int $maxBytes = 5242880): array
    {
        $normalized = $files ? $this->normalizeFiles($files) : [];
        if (!$normalized) {
            return ['ok' => true, 'attachments' => [], 'errors' => []];
        }
        if (!$this->ensureStorage()) {
            return ['ok' => false, 'attachments' => [], 'errors' => ['Stockage indisponible.']];
        }

        $attachments = [];
        $errors = [];
        $finfo = function_exists('finfo_open') ? new finfo(FILEINFO_MIME_TYPE) : null;

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

            $orig = $this->sanitizeFilename((string) ($file['name'] ?? ''));
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

            if ($ext === '' || !$this->isAllowedUpload($ext, $mime)) {
                $errors[] = 'Type de fichier interdit. Formats autorises: PDF, PNG, JPG, JPEG, WEBP, TXT, CSV.';
                continue;
            }

            $stored = 'support_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8));
            $stored .= '.' . $ext;
            $dest = $this->storageDir() . DIRECTORY_SEPARATOR . $stored;

            if (!move_uploaded_file($tmp, $dest)) {
                $errors[] = 'Echec de l upload.';
                continue;
            }

            @chmod($dest, 0600);

            $attachments[] = [
                'file_path' => $this->storageReference($stored),
                'original_name' => $orig,
                'mime' => $mime !== '' ? $mime : 'application/octet-stream',
                'size' => $size,
            ];
        }

        if ($errors) {
            foreach ($attachments as $attachment) {
                $absolute = $this->resolveStoredPath((string) ($attachment['file_path'] ?? ''));
                if ($absolute !== null && is_file($absolute)) {
                    @unlink($absolute);
                }
            }
            return ['ok' => false, 'attachments' => [], 'errors' => $errors];
        }

        return ['ok' => true, 'attachments' => $attachments, 'errors' => []];
    }

    public function resolveStoredPath(string $storedRelativePath): ?string
    {
        $storedPath = trim($storedRelativePath);
        if ($storedPath === '') {
            return null;
        }

        $allowedBases = $this->allowedBaseDirs();
        $candidates = [];
        if (str_starts_with($storedPath, 'support://')) {
            $candidates[] = $this->storageDir() . DIRECTORY_SEPARATOR . basename(substr($storedPath, strlen('support://')));
        } elseif (preg_match('/^[A-Za-z]:\\\\|^\//', $storedPath) === 1) {
            $candidates[] = $storedPath;
        } elseif (str_starts_with(str_replace('\\', '/', $storedPath), 'storage/support/')) {
            $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $storedPath);
            $candidates[] = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $normalized;
        } else {
            $candidates[] = $this->storageDir() . DIRECTORY_SEPARATOR . basename($storedPath);
        }

        foreach ($candidates as $candidate) {
            $realPath = realpath($candidate);
            if (!is_string($realPath) || $realPath === '' || !is_file($realPath)) {
                continue;
            }
            if ($this->isWithinAllowedBase($realPath, $allowedBases)) {
                return $realPath;
            }
        }

        return null;
    }

    private function storageReference(string $storedFilename): string
    {
        return 'support://' . ltrim($storedFilename, '/\\');
    }

    /**
     * @return array<string,array<int,string>>
     */
    private function allowedMimeMap(): array
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

    private function isAllowedUpload(string $extension, string $mime): bool
    {
        $map = $this->allowedMimeMap();
        if (!isset($map[$extension])) {
            return false;
        }

        return in_array($mime, $map[$extension], true);
    }

    /**
     * @return array<int,string>
     */
    private function allowedBaseDirs(): array
    {
        $bases = [];
        $currentBase = realpath($this->storageDir());
        if (is_string($currentBase) && $currentBase !== '') {
            $bases[] = $currentBase;
        }

        $legacyBase = realpath(dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'support');
        if (is_string($legacyBase) && $legacyBase !== '') {
            $bases[] = $legacyBase;
        }

        return array_values(array_unique($bases));
    }

    /**
     * @param array<int,string> $allowedBases
     */
    private function isWithinAllowedBase(string $realPath, array $allowedBases): bool
    {
        foreach ($allowedBases as $baseDir) {
            $basePrefix = rtrim($baseDir, "\\/") . DIRECTORY_SEPARATOR;
            if ($realPath === $baseDir || str_starts_with($realPath, $basePrefix)) {
                return true;
            }
        }

        return false;
    }
}
