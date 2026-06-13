<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private Session $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function token(): string
    {
        $token = $this->session->get('csrf_token');
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $this->session->set('csrf_token', $token);
        }
        return $token;
    }

    public function requireFromPost(): void
    {
        if ((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return;
        }

        $token = (string) ($_POST['_csrf'] ?? '');
        $sessionToken = (string) $this->session->get('csrf_token', '');
        if ($token === '' || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
            http_response_code(400);
            exit('Jeton CSRF invalide.');
        }
    }
}

