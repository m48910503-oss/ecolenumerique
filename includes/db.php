<?php
// Connexion PDO centralisée avec cache de connexion et attributs sécurisés.

declare(strict_types=1);

/**
 * Retourne une instance PDO unique configurée pour les requêtes préparées.
 */
function db(array $config): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }
 
    $dsn = $config['dsn'] ?? '';
    $user = $config['user'] ?? '';
    $pass = $config['pass'] ?? '';
    $options = $config['options'] ?? [];

    // Ouverture de connexion unique avec options sécurisées.
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Mode strict sur le time zone pour cohérence avec PHP.
    $tzOffset = date('P');
    if (!preg_match('/^[+-]\d{2}:\d{2}$/', $tzOffset)) {
        $tzOffset = '+00:00';
    }
    $pdo->exec("SET time_zone = '{$tzOffset}'");

    return $pdo;
}
