<?php
declare(strict_types=1);

namespace App\Services\Shared;

use App\Core\Service;
 
final class FedapayService extends Service
{
    private function platformSetting(string $key, string $default = ''): string
    {
        try {
            if (function_exists('platform_setting_get')) {
                $value = trim((string) \platform_setting_get($key, $default));
                return $value !== '' || $default === '' ? $value : trim($default);
            }
            if (function_exists('shared_platform_settings_service')) {
                $value = trim((string) \shared_platform_settings_service()->get($key, $default));
                return $value !== '' || $default === '' ? $value : trim($default);
            }
        } catch (\Throwable $e) {
            return trim($default);
        }

        return trim($default);
    }

    public function publicKey(): string
    {
        return $this->platformSetting(
            'payment_fedapay_public_key',
            defined('PAY_FEDAPAY_PUBLIC_KEY') ? (string) PAY_FEDAPAY_PUBLIC_KEY : ''
        );
    }

    public function secretKey(): string
    {
        return $this->platformSetting(
            'payment_fedapay_secret_key',
            defined('PAY_FEDAPAY_SECRET_KEY') ? (string) PAY_FEDAPAY_SECRET_KEY : ''
        );
    }

    public function environment(): string
    {
        $environment = strtolower($this->platformSetting(
            'payment_fedapay_environment',
            defined('PAY_FEDAPAY_ENV') ? (string) PAY_FEDAPAY_ENV : 'sandbox'
        ));

        if ($environment === 'production') {
            return 'live';
        }
        if ($environment === 'development') {
            return 'dev';
        }
        if (!in_array($environment, ['sandbox', 'live', 'dev'], true)) {
            return 'sandbox';
        }

        return $environment;
    }

    public function webhookSecret(): string
    {
        return $this->platformSetting(
            'payment_fedapay_webhook_secret',
            defined('PAY_FEDAPAY_WEBHOOK_SECRET') ? (string) PAY_FEDAPAY_WEBHOOK_SECRET : ''
        );
    }

    public function isEnabled(): bool
    {
        return $this->platformSetting('payment_fedapay_enabled', '1') !== '0';
    }

    public function isConfigured(): bool
    {
        return $this->isEnabled() && $this->publicKey() !== '' && $this->secretKey() !== '';
    }

    private function logError(string $message, array $context = []): void
    {
        if (function_exists('app_log_error')) {
            \app_log_error('fedapay.api', $message, $context);
        }
    }

    private function logWarning(string $message, array $context = []): void
    {
        if (function_exists('app_log')) {
            \app_log('warning', 'fedapay.api', $message, $context);
        }
    }

    private function caInfoPath(): string
    {
        $candidates = [
            trim((string) ini_get('curl.cainfo')),
            trim((string) ini_get('openssl.cafile')),
            'C:\\xampp\\phpMyAdmin\\vendor\\composer\\ca-bundle\\res\\cacert.pem',
            'C:\\xampp\\apache\\bin\\curl-ca-bundle.crt',
            'C:\\xampp\\perl\\vendor\\lib\\Mozilla\\CA\\cacert.pem',
        ];

        foreach ($candidates as $path) {
            if ($path !== '' && is_file($path)) {
                return $path;
            }
        }

        return '';
    }

    /**
     * @return array{0:string|false,1:int,2:string}
     */
    private function executeCurl(string $method, string $url, ?string $body, string $secretKey): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ]);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        $caInfo = $this->caInfoPath();
        if ($caInfo !== '') {
            curl_setopt($ch, CURLOPT_CAINFO, $caInfo);
        }

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        return [$resp, $code, $err];
    }

    public function apiBase(): string
    {
        $env = $this->environment();
        if ($env === 'live') {
            return 'https://api.fedapay.com';
        }
        if ($env === 'dev') {
            return 'https://dev-api.fedapay.com';
        }
        return 'https://sandbox-api.fedapay.com';
    }

    public function request(string $method, string $path, ?array $payload = null): array
    {
        $method = strtoupper($method);
        $environment = $this->environment();
        $secretKey = $this->secretKey();
        $caInfo = $this->caInfoPath();
        $logContext = [
            'method' => $method,
            'path' => $path,
            'fedapay_env' => $environment,
        ];
        if ($caInfo !== '') {
            $logContext['ca_bundle'] = $caInfo;
        }

        if (!function_exists('curl_init')) {
            $this->logError('cURL indisponible pour FedaPay.', $logContext);
            return ['ok' => false, 'error' => 'curl_unavailable'];
        }
        if ($secretKey === '') {
            $this->logError('Cle secrete FedaPay manquante.', $logContext);
            return ['ok' => false, 'error' => 'missing_key'];
        }

        $url = rtrim($this->apiBase(), '/') . '/v1' . $path;
        $body = null;

        if ($method === 'GET' && $payload) {
            $url .= '?' . http_build_query($payload);
        } elseif ($payload !== null) {
            $body = json_encode($payload);
        }

        [$resp, $code, $err] = $this->executeCurl($method, $url, $body, $secretKey);

        if ($resp === false) {
            $this->logError('Echec reseau lors d un appel FedaPay.', $logContext + [
                'status' => $code,
                'detail' => $err,
            ]);
            return ['ok' => false, 'error' => 'curl_error', 'detail' => $err];
        }

        $json = json_decode((string) $resp, true);
        if (!is_array($json)) {
            $this->logError('Reponse JSON invalide depuis FedaPay.', $logContext + [
                'status' => $code,
                'raw' => (string) $resp,
            ]);
            return ['ok' => false, 'error' => 'invalid_json', 'status' => $code, 'raw' => (string) $resp];
        }
        if ($code < 200 || $code >= 300) {
            $this->logWarning('FedaPay a retourne une erreur API.', $logContext + [
                'status' => $code,
                'data' => $json,
            ]);
            return ['ok' => false, 'error' => 'api_error', 'status' => $code, 'data' => $json];
        }
        return ['ok' => true, 'status' => $code, 'data' => $json];
    }

    public function extractTransaction(array $payload): ?array
    {
        if (isset($payload['v1/transaction']) && is_array($payload['v1/transaction'])) {
            return $payload['v1/transaction'];
        }
        if (isset($payload['transaction']) && is_array($payload['transaction'])) {
            return $payload['transaction'];
        }
        if (isset($payload['data']) && is_array($payload['data']) && isset($payload['data']['id'])) {
            return $payload['data'];
        }
        if (isset($payload['data']['transaction']) && is_array($payload['data']['transaction'])) {
            return $payload['data']['transaction'];
        }
        return null;
    }

    public function createTransaction(array $payload): array
    {
        return $this->request('POST', '/transactions', $payload);
    }

    public function generateToken(int $transactionId): array
    {
        return $this->request('POST', '/transactions/' . $transactionId . '/token', []);
    }

    public function getTransaction(int $transactionId): array
    {
        return $this->request('GET', '/transactions/' . $transactionId, null);
    }

    public function isPaidStatus(string $status): bool
    {
        $paid = [
            'approved',
            'transferred',
            'refunded',
            'approved_partially_refunded',
            'transferred_partially_refunded',
        ];
        return in_array($status, $paid, true);
    }

    public function isRefundedStatus(string $status): bool
    {
        $refunded = [
            'refunded',
            'approved_partially_refunded',
            'transferred_partially_refunded',
        ];
        return in_array($status, $refunded, true);
    }

    public function isFailedStatus(string $status): bool
    {
        $failed = [
            'canceled',
            'cancelled',
            'declined',
            'failed',
            'expired',
        ];
        return in_array($status, $failed, true);
    }

    public function toPaymentStatus(string $status): string
    {
        if ($this->isRefundedStatus($status)) {
            return 'refunded';
        }
        if ($this->isPaidStatus($status)) {
            return 'paid';
        }
        if ($this->isFailedStatus($status)) {
            return 'failed';
        }
        return 'pending';
    }

    public function toOrderStatus(string $status): string
    {
        if ($this->isRefundedStatus($status)) {
            return 'refunded';
        }
        if ($this->isPaidStatus($status)) {
            return 'paid';
        }
        if ($status === 'canceled' || $status === 'cancelled') {
            return 'canceled';
        }
        if ($this->isFailedStatus($status)) {
            return 'failed';
        }
        return 'pending';
    }

    public function parseSignatureHeader(string $header): array
    {
        $timestamp = -1;
        $signatures = [];
        $items = array_map('trim', explode(',', $header));
        foreach ($items as $item) {
            if ($item === '') {
                continue;
            }
            $parts = explode('=', $item, 2);
            if (count($parts) !== 2) {
                continue;
            }
            if ($parts[0] === 't') {
                $timestamp = is_numeric($parts[1]) ? (int) $parts[1] : -1;
            } elseif ($parts[0] === 's') {
                $signatures[] = $parts[1];
            }
        }
        return ['timestamp' => $timestamp, 'signatures' => $signatures];
    }

    public function verifySignature(string $payload, string $header, string $secret, int $tolerance = 300): bool
    {
        if ($secret === '') {
            return true;
        }
        $parsed = $this->parseSignatureHeader($header);
        $timestamp = (int) ($parsed['timestamp'] ?? -1);
        $signatures = $parsed['signatures'] ?? [];
        if ($timestamp < 0 || !$signatures) {
            return false;
        }
        $signedPayload = $timestamp . '.' . $payload;
        $expected = hash_hmac('sha256', $signedPayload, $secret);

        $valid = false;
        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                $valid = true;
                break;
            }
        }
        if (!$valid) {
            return false;
        }
        if ($tolerance > 0 && abs(time() - $timestamp) > $tolerance) {
            return false;
        }
        return true;
    }

    public function extractEventTransactionId(array $payload): ?int
    {
        $candidates = [];
        if (isset($payload['object_id'])) {
            $candidates[] = $payload['object_id'];
        }
        if (isset($payload['data']['id'])) {
            $candidates[] = $payload['data']['id'];
        }
        if (isset($payload['data']['object']['id'])) {
            $candidates[] = $payload['data']['object']['id'];
        }
        if (isset($payload['object']['id'])) {
            $candidates[] = $payload['object']['id'];
        }
        if (isset($payload['entity']['id'])) {
            $candidates[] = $payload['entity']['id'];
        }

        foreach ($candidates as $candidate) {
            if (is_numeric($candidate)) {
                return (int) $candidate;
            }
        }
        return null;
    }
}

