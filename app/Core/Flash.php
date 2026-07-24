<?php
declare(strict_types=1);

namespace App\Core;

final class Flash
{
    private Session $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function set(string $key, mixed $value): void
    {
        $flash = $this->session->get('_flash', []);
        if (!is_array($flash)) {
            $flash = [];
        }
        $flash[$key] = $value;
        $this->session->set('_flash', $flash);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $flash = $this->session->get('_flash', []);
        if (!is_array($flash) || !array_key_exists($key, $flash)) {
            return $default;
        }
        $value = $flash[$key];
        unset($flash[$key]);
        if ($flash === []) {
            $this->session->remove('_flash');
        } else {
            $this->session->set('_flash', $flash);
        }
        return $value;
    }
}

