<?php
declare(strict_types=1);

namespace App\Services\Api;

use App\Core\Service;
use App\Services\Shared\WebPushService;

final class PushSubscriptionService extends Service
{
    private WebPushService $push;

    public function __construct(WebPushService $push)
    {
        $this->push = $push;
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $data
     * @return array{status:int,payload:array<string,mixed>}
     */
    public function handle(array $user, string $method, array $data, array $server): array
    {
        $userId = (int) ($user['id'] ?? 0);
        if ($userId < 1) {
            return ['status' => 401, 'payload' => ['ok' => false, 'error' => 'auth']];
        }

        if (strtoupper($method) === 'GET') {
            return [
                'status' => 200,
                'payload' => [
                    'ok' => true,
                    'enabled' => $this->push->isEnabled(),
                    'public_key' => $this->push->publicKey(),
                ],
            ];
        }

        if (strtoupper($method) !== 'POST') {
            return ['status' => 405, 'payload' => ['ok' => false, 'error' => 'method_not_allowed']];
        }

        $action = trim((string) ($data['action'] ?? 'subscribe'));
        $subscription = $data['subscription'] ?? null;
        if (!is_array($subscription)) {
            return ['status' => 400, 'payload' => ['ok' => false, 'error' => 'subscription_required']];
        }

        if ($action === 'unsubscribe') {
            $this->push->unsubscribe($userId, $subscription);
            return ['status' => 200, 'payload' => ['ok' => true, 'action' => 'unsubscribe']];
        }

        $userAgent = trim((string) ($server['HTTP_USER_AGENT'] ?? ''));
        $ok = $this->push->subscribe($userId, $subscription, $userAgent !== '' ? $userAgent : null);
        if (!$ok) {
            return ['status' => 400, 'payload' => ['ok' => false, 'error' => 'subscription_invalid_or_push_disabled']];
        }

        return ['status' => 200, 'payload' => ['ok' => true, 'action' => 'subscribe']];
    }
}
