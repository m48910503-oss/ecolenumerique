<?php
declare(strict_types=1);

namespace App\Security;

final class LoginThrottle
{
    private string $storageDir;

    public function __construct(?string $storageDir = null)
    {
        $baseDir = $storageDir ?: dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'login-throttle';
        $this->storageDir = rtrim($baseDir, "\\/");
    }

    public function isBlocked(string $scope, string $email, ?string $ip = null, int $maxAttempts = 5, int $windowSeconds = 900): bool
    {
        foreach ($this->keysFor($scope, $email, $ip) as $key) {
            if (count($this->readAttempts($key, $windowSeconds)) >= $maxAttempts) {
                return true;
            }
        }

        return false;
    }

    public function recordFailure(string $scope, string $email, ?string $ip = null, int $windowSeconds = 900): void
    {
        foreach ($this->keysFor($scope, $email, $ip) as $key) {
            $attempts = $this->readAttempts($key, $windowSeconds);
            $attempts[] = time();
            $this->writeAttempts($key, $attempts);
        }
    }

    public function clear(string $scope, string $email, ?string $ip = null): void
    {
        foreach ($this->keysFor($scope, $email, $ip) as $key) {
            $path = $this->pathForKey($key);
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    /**
     * @return array<int,string>
     */
    private function keysFor(string $scope, string $email, ?string $ip): array
    {
        $safeScope = trim(strtolower($scope)) !== '' ? trim(strtolower($scope)) : 'login';
        $safeEmail = strtolower(trim($email));
        $safeIp = trim((string) ($ip ?? ''));

        $keys = [];
        if (filter_var($safeIp, FILTER_VALIDATE_IP)) {
            $keys[] = $safeScope . '|ip|' . $safeIp;
            if ($safeEmail !== '') {
                $keys[] = $safeScope . '|combo|' . $safeEmail . '|' . $safeIp;
            }
        } elseif ($safeEmail !== '') {
            $keys[] = $safeScope . '|email|' . $safeEmail;
        }

        return array_values(array_unique($keys));
    }

    /**
     * @return array<int,int>
     */
    private function readAttempts(string $key, int $windowSeconds): array
    {
        $path = $this->pathForKey($key);
        if (!is_file($path)) {
            return [];
        }

        $raw = @file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        $attempts = is_array($decoded['attempts'] ?? null) ? $decoded['attempts'] : [];
        $cutoff = time() - max(60, $windowSeconds);
        $filtered = [];
        foreach ($attempts as $timestamp) {
            $value = (int) $timestamp;
            if ($value >= $cutoff) {
                $filtered[] = $value;
            }
        }

        return $filtered;
    }

    /**
     * @param array<int,int> $attempts
     */
    private function writeAttempts(string $key, array $attempts): void
    {
        if (!$this->ensureStorageDir()) {
            return;
        }

        $payload = json_encode(['attempts' => array_values($attempts)], JSON_PRETTY_PRINT);
        if (!is_string($payload) || $payload === '') {
            return;
        }

        $path = $this->pathForKey($key);
        @file_put_contents($path, $payload, LOCK_EX);
        @chmod($path, 0600);
    }

    private function ensureStorageDir(): bool
    {
        if (is_dir($this->storageDir)) {
            return true;
        }

        return mkdir($this->storageDir, 0700, true) || is_dir($this->storageDir);
    }

    private function pathForKey(string $key): string
    {
        return $this->storageDir . DIRECTORY_SEPARATOR . hash('sha256', $key) . '.json';
    }
}
