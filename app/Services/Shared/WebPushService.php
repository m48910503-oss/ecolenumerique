<?php
declare(strict_types=1);

namespace App\Services\Shared;

use App\Core\Service;
use App\Models\SharedWebPushModel;
use Throwable; 

final class WebPushService extends Service
{
    private const DEFAULT_TTL_SECONDS = 60;
    private const MAX_QUEUE_ATTEMPTS = 5;

    private SharedWebPushModel $model;

    public function __construct(SharedWebPushModel $model)
    {
        $this->model = $model;
    }

    public function isEnabled(): bool
    {
        if (!defined('WEBPUSH_ENABLED') || !WEBPUSH_ENABLED) {
            return false;
        }
        if (!$this->model->schemaReady()) {
            return false;
        }
        if (!$this->hasVapidConfig()) {
            return false;
        }
        return function_exists('curl_init') && function_exists('openssl_sign');
    }

    public function publicKey(): string
    {
        return trim((string) (defined('WEBPUSH_VAPID_PUBLIC_KEY') ? WEBPUSH_VAPID_PUBLIC_KEY : ''));
    }

    /**
     * @param array<string,mixed> $subscription
     */
    public function subscribe(int $userId, array $subscription, ?string $userAgent = null): bool
    {
        if ($userId < 1 || !$this->isEnabled()) {
            return false;
        }

        $normalized = $this->normalizeSubscription($subscription);
        if ($normalized === null) {
            return false;
        }

        $this->model->upsertSubscription(
            $userId,
            $normalized['endpoint_hash'],
            $normalized['endpoint'],
            $normalized['p256dh'],
            $normalized['auth'],
            $normalized['content_encoding'],
            $userAgent
        );
        return true;
    }

    /**
     * @param array<string,mixed> $subscription
     */
    public function unsubscribe(int $userId, array $subscription): void
    {
        if ($userId < 1 || !$this->model->schemaReady()) {
            return;
        }
        $endpoint = trim((string) ($subscription['endpoint'] ?? ''));
        if ($endpoint === '') {
            return;
        }
        $this->model->deactivateSubscriptionByHash($userId, hash('sha256', $endpoint));
    }

    public function processQueue(int $limit = 20): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $jobs = $this->model->claimPendingQueueJobs($limit);
        if ($jobs === []) {
            return;
        }

        foreach ($jobs as $job) {
            $queueId = (int) ($job['id'] ?? 0);
            $userId = (int) ($job['user_id'] ?? 0);
            $attemptCount = (int) ($job['attempt_count'] ?? 0);
            if ($queueId < 1 || $userId < 1) {
                continue;
            }

            $subscriptions = $this->model->findActiveSubscriptionsByUser($userId);
            if ($subscriptions === []) {
                $this->model->markQueueSent($queueId);
                continue;
            }

            $hasSuccess = false;
            $shouldRetry = false;
            $errors = [];

            foreach ($subscriptions as $sub) {
                $result = $this->sendPushToSubscription($sub);
                $status = (string) ($result['status'] ?? 'failed');
                $error = (string) ($result['error'] ?? '');
                $subId = (int) ($sub['id'] ?? 0);

                if ($status === 'success') {
                    $hasSuccess = true;
                    $this->model->markSubscriptionSuccess($subId);
                    continue;
                }
                if ($status === 'gone') {
                    $this->model->markSubscriptionFailure($subId, true);
                    continue;
                }
                if ($status === 'retry') {
                    $shouldRetry = true;
                    $this->model->markSubscriptionFailure($subId, false);
                    if ($error !== '') {
                        $errors[] = $error;
                    }
                    continue;
                }

                $this->model->markSubscriptionFailure($subId, false);
                if ($error !== '') {
                    $errors[] = $error;
                }
            }

            if ($hasSuccess || !$shouldRetry) {
                $this->model->markQueueSent($queueId);
                continue;
            }

            if ($attemptCount + 1 >= self::MAX_QUEUE_ATTEMPTS) {
                $this->model->markQueueFailed($queueId, $this->joinErrors($errors));
                continue;
            }

            $delay = min(3600, max(20, (int) pow(2, max(1, $attemptCount + 1)) * 15));
            $this->model->markQueueRetry($queueId, $delay, $this->joinErrors($errors));
        }
    }

    /**
     * @param array<string,mixed> $subscription
     * @return array{status:string,error:string}
     */
    private function sendPushToSubscription(array $subscription): array
    {
        $endpoint = trim((string) ($subscription['endpoint'] ?? ''));
        if ($endpoint === '') {
            return ['status' => 'failed', 'error' => 'missing_endpoint'];
        }

        $audience = $this->extractAudience($endpoint);
        if ($audience === '') {
            return ['status' => 'failed', 'error' => 'invalid_endpoint'];
        }

        $jwt = $this->buildVapidJwt($audience);
        if ($jwt === '') {
            return ['status' => 'failed', 'error' => 'vapid_jwt_error'];
        }

        $headers = [
            'TTL: ' . self::DEFAULT_TTL_SECONDS,
            'Authorization: vapid t=' . $jwt . ', k=' . $this->publicKey(),
            'Crypto-Key: p256ecdsa=' . $this->publicKey(),
            'Content-Length: 0',
        ];

        [$responseBody, $httpCode, $curlError] = $this->executePushRequest($endpoint, $headers, false);
        if ($responseBody === false && $this->shouldRetryWithoutSslVerification($curlError)) {
            [$responseBody, $httpCode, $curlError] = $this->executePushRequest($endpoint, $headers, true);
        }

        if ($responseBody === false) {
            return ['status' => 'retry', 'error' => $curlError !== '' ? $curlError : 'curl_exec_error'];
        }

        if ($httpCode === 200 || $httpCode === 201 || $httpCode === 202) {
            return ['status' => 'success', 'error' => ''];
        }
        if ($httpCode === 404 || $httpCode === 410) {
            return ['status' => 'gone', 'error' => 'subscription_gone_' . $httpCode];
        }
        if ($httpCode === 408 || $httpCode === 425 || $httpCode === 429 || $httpCode >= 500) {
            return ['status' => 'retry', 'error' => 'push_http_' . $httpCode];
        }
        return ['status' => 'failed', 'error' => 'push_http_' . $httpCode];
    }

    /**
     * @param array<int,string> $headers
     * @return array{0:string|false,1:int,2:string}
     */
    private function executePushRequest(string $endpoint, array $headers, bool $disableSslVerification): array
    {
        $ch = curl_init($endpoint);
        if ($ch === false) {
            return [false, 0, 'curl_init_error'];
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_POSTFIELDS => '',
        ]);

        if ($disableSslVerification) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        } else {
            $caInfo = $this->caInfoPath();
            if ($caInfo !== '') {
                curl_setopt($ch, CURLOPT_CAINFO, $caInfo);
            }
        }

        $responseBody = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$responseBody, $httpCode, $curlError];
    }

    private function shouldRetryWithoutSslVerification(string $error): bool
    {
        if (!$this->localInsecureSslAllowed()) {
            return false;
        }

        $error = strtolower($error);
        return str_contains($error, 'ssl certificate')
            || str_contains($error, 'certificate problem')
            || str_contains($error, 'self-signed certificate')
            || str_contains($error, 'unable to get local issuer certificate');
    }

    private function localInsecureSslAllowed(): bool
    {
        if (function_exists('is_local_environment') && \is_local_environment()) {
            return true;
        }

        $host = strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? '')));
        return in_array($host, ['localhost', '127.0.0.1', '[::1]'], true);
    }

    /**
     * @param array<string,mixed> $subscription
     * @return array{endpoint_hash:string,endpoint:string,p256dh:string,auth:string,content_encoding:string}|null
     */
    private function normalizeSubscription(array $subscription): ?array
    {
        $endpoint = trim((string) ($subscription['endpoint'] ?? ''));
        if ($endpoint === '' || strlen($endpoint) > 2000) {
            return null;
        }

        $keys = $subscription['keys'] ?? null;
        if (!is_array($keys)) {
            return null;
        }
        $p256dh = trim((string) ($keys['p256dh'] ?? ''));
        $auth = trim((string) ($keys['auth'] ?? ''));
        if ($p256dh === '' || $auth === '') {
            return null;
        }
        $encoding = trim((string) ($subscription['contentEncoding'] ?? 'aes128gcm'));
        if ($encoding === '') {
            $encoding = 'aes128gcm';
        }

        return [
            'endpoint_hash' => hash('sha256', $endpoint),
            'endpoint' => $endpoint,
            'p256dh' => $p256dh,
            'auth' => $auth,
            'content_encoding' => $encoding,
        ];
    }

    private function caInfoPath(): string
    {
        $candidates = [
            trim((string) ini_get('curl.cainfo')),
            trim((string) ini_get('openssl.cafile')),
            'C:\\xampp\\apache\\bin\\curl-ca-bundle.crt',
        ];

        foreach ($candidates as $path) {
            if ($path !== '' && is_file($path)) {
                return $path;
            }
        }

        return '';
    }

    private function hasVapidConfig(): bool
    {
        $publicKey = $this->publicKey();
        $privatePath = trim((string) (defined('WEBPUSH_VAPID_PRIVATE_KEY_PATH') ? WEBPUSH_VAPID_PRIVATE_KEY_PATH : ''));
        $subject = trim((string) (defined('WEBPUSH_VAPID_SUBJECT') ? WEBPUSH_VAPID_SUBJECT : ''));
        if ($publicKey === '' || $privatePath === '' || $subject === '') {
            return false;
        }
        return is_file($privatePath);
    }

    private function extractAudience(string $endpoint): string
    {
        $parts = parse_url($endpoint);
        if (!is_array($parts)) {
            return '';
        }
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($scheme !== 'https' || $host === '') {
            return '';
        }
        $port = isset($parts['port']) ? (int) $parts['port'] : 0;
        $aud = $scheme . '://' . $host;
        if ($port > 0 && $port !== 443) {
            $aud .= ':' . $port;
        }
        return $aud;
    }

    private function buildVapidJwt(string $audience): string
    {
        $privatePem = $this->readPrivateKeyPem();
        if ($privatePem === '') {
            return '';
        }

        $header = ['typ' => 'JWT', 'alg' => 'ES256'];
        $subject = trim((string) (defined('WEBPUSH_VAPID_SUBJECT') ? WEBPUSH_VAPID_SUBJECT : ''));
        $payload = [
            'aud' => $audience,
            'exp' => time() + 43200,
            'sub' => $subject,
        ];

        $headerB64 = $this->base64UrlEncode((string) json_encode($header, JSON_UNESCAPED_SLASHES));
        $payloadB64 = $this->base64UrlEncode((string) json_encode($payload, JSON_UNESCAPED_SLASHES));
        $signInput = $headerB64 . '.' . $payloadB64;

        $pkey = openssl_pkey_get_private($privatePem);
        if ($pkey === false) {
            return '';
        }

        $signatureDer = '';
        $ok = openssl_sign($signInput, $signatureDer, $pkey, OPENSSL_ALGO_SHA256);
        if (is_object($pkey) && $pkey instanceof \OpenSSLAsymmetricKey) {
            // No explicit free needed in modern PHP.
        } elseif (is_resource($pkey)) {
            openssl_free_key($pkey);
        }
        if (!$ok || $signatureDer === '') {
            return '';
        }

        $signatureRaw = $this->ecdsaDerToJose($signatureDer, 32);
        if ($signatureRaw === '') {
            return '';
        }

        return $signInput . '.' . $this->base64UrlEncode($signatureRaw);
    }

    private function readPrivateKeyPem(): string
    {
        $path = trim((string) (defined('WEBPUSH_VAPID_PRIVATE_KEY_PATH') ? WEBPUSH_VAPID_PRIVATE_KEY_PATH : ''));
        if ($path === '' || !is_file($path)) {
            return '';
        }
        $pem = @file_get_contents($path);
        if (!is_string($pem)) {
            return '';
        }
        return trim($pem);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function ecdsaDerToJose(string $der, int $partLength): string
    {
        $offset = 0;
        if ($this->readByte($der, $offset) !== 0x30) {
            return '';
        }
        $seqLength = $this->readAsn1Length($der, $offset);
        if ($seqLength === null || ($offset + $seqLength) > strlen($der)) {
            return '';
        }
        $r = $this->readAsn1Integer($der, $offset);
        $s = $this->readAsn1Integer($der, $offset);
        if ($r === null || $s === null) {
            return '';
        }

        $r = ltrim($r, "\x00");
        $s = ltrim($s, "\x00");
        $r = str_pad($r, $partLength, "\x00", STR_PAD_LEFT);
        $s = str_pad($s, $partLength, "\x00", STR_PAD_LEFT);

        if (strlen($r) > $partLength) {
            $r = substr($r, -$partLength);
        }
        if (strlen($s) > $partLength) {
            $s = substr($s, -$partLength);
        }

        return $r . $s;
    }

    private function readAsn1Integer(string $der, int &$offset): ?string
    {
        if ($this->readByte($der, $offset) !== 0x02) {
            return null;
        }
        $len = $this->readAsn1Length($der, $offset);
        if ($len === null || $len < 1 || ($offset + $len) > strlen($der)) {
            return null;
        }
        $value = substr($der, $offset, $len);
        $offset += $len;
        return $value;
    }

    private function readAsn1Length(string $der, int &$offset): ?int
    {
        $first = $this->readByte($der, $offset);
        if ($first === null) {
            return null;
        }
        if (($first & 0x80) === 0) {
            return $first;
        }
        $numBytes = $first & 0x7f;
        if ($numBytes < 1 || $numBytes > 4 || ($offset + $numBytes) > strlen($der)) {
            return null;
        }
        $len = 0;
        for ($i = 0; $i < $numBytes; $i++) {
            $byte = ord($der[$offset]);
            $offset++;
            $len = ($len << 8) | $byte;
        }
        return $len;
    }

    private function readByte(string $der, int &$offset): ?int
    {
        if ($offset >= strlen($der)) {
            return null;
        }
        $byte = ord($der[$offset]);
        $offset++;
        return $byte;
    }

    /**
     * @param array<int,string> $errors
     */
    private function joinErrors(array $errors): string
    {
        $errors = array_values(array_filter(array_map('trim', $errors), static fn(string $v): bool => $v !== ''));
        if ($errors === []) {
            return 'push_retry_error';
        }
        $joined = implode('; ', array_unique($errors));
        if (function_exists('mb_substr')) {
            return (string) mb_substr($joined, 0, 250);
        }
        return substr($joined, 0, 250);
    }
}
