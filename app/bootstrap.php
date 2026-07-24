<?php
declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

if (!defined('APP_KEY')) {
    $envAppKey = getenv('APP_KEY');
    if (!is_string($envAppKey) || trim($envAppKey) === '') {
        $envAppKey = PHP_SAPI === 'cli' ? hash('sha256', __FILE__) : '';
    }
    define('APP_KEY', $envAppKey);
}

 
