<?php
declare(strict_types=1);

namespace App\Services\Support;

use App\Core\Service;

final class SupportGuestContextService extends Service
{
    private const SESSION_KEY = 'support_guest_context';

    /**
     * @return array{viewer_key:string,guest_name:string,guest_email:string,active_conversation_id:int}
     */
    public function ensure(): array
    {
        $state = $this->loadState();
        $_SESSION[self::SESSION_KEY] = $state;

        return $state;
    }

    /**
     * @param array<string,mixed> $data
     * @return array{viewer_key:string,guest_name:string,guest_email:string,active_conversation_id:int}
     */
    public function updateFromRequest(array $data): array
    {
        $state = $this->ensure();

        if (array_key_exists('guest_name', $data)) {
            $state['guest_name'] = $this->sanitizeName((string) $data['guest_name']);
        }

        if (array_key_exists('guest_email', $data)) {
            $state['guest_email'] = $this->sanitizeEmail((string) $data['guest_email']);
        }

        $_SESSION[self::SESSION_KEY] = $state;

        return $state;
    }

    public function setActiveConversationId(int $conversationId): void
    {
        $state = $this->ensure();
        $state['active_conversation_id'] = max(0, $conversationId);
        $_SESSION[self::SESSION_KEY] = $state;
    }

    public function setProfile(string $name, string $email = ''): void
    {
        $state = $this->ensure();
        $state['guest_name'] = $this->sanitizeName($name);
        $state['guest_email'] = $this->sanitizeEmail($email);
        $_SESSION[self::SESSION_KEY] = $state;
    }

    public function viewerKey(): string
    {
        return $this->ensure()['viewer_key'];
    }

    public function guestName(): string
    {
        return $this->ensure()['guest_name'];
    }

    public function guestEmail(): string
    {
        return $this->ensure()['guest_email'];
    }

    public function activeConversationId(): int
    {
        return $this->ensure()['active_conversation_id'];
    }

    public function sanitizeName(string $name): string
    {
        $name = trim(preg_replace('/\s+/', ' ', $name) ?? '');
        if ($name === '') {
            return 'Visiteur';
        }

        if (function_exists('mb_substr')) {
            return (string) mb_substr($name, 0, 120);
        }

        return substr($name, 0, 120);
    }

    public function sanitizeEmail(string $email): string
    {
        $email = trim(strtolower($email));
        if ($email === '') {
            return '';
        }

        return (string) (filter_var($email, FILTER_VALIDATE_EMAIL) ?: '');
    }

    /**
     * @return array{viewer_key:string,guest_name:string,guest_email:string,active_conversation_id:int}
     */
    private function loadState(): array
    {
        $state = $_SESSION[self::SESSION_KEY] ?? [];
        if (!is_array($state)) {
            $state = [];
        }

        $viewerKey = trim((string) ($state['viewer_key'] ?? ''));
        if ($viewerKey === '') {
            try {
                $viewerKey = bin2hex(random_bytes(16));
            } catch (\Throwable $e) {
                $viewerKey = hash('sha256', (string) microtime(true) . ':' . (string) mt_rand());
            }
        }

        $guestName = $this->sanitizeName((string) ($state['guest_name'] ?? ''));
        $guestEmail = $this->sanitizeEmail((string) ($state['guest_email'] ?? ''));
        $activeConversationId = max(0, (int) ($state['active_conversation_id'] ?? 0));

        return [
            'viewer_key' => $viewerKey,
            'guest_name' => $guestName,
            'guest_email' => $guestEmail,
            'active_conversation_id' => $activeConversationId,
        ];
    }
}
