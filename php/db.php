<?php
declare(strict_types=1);

/** @return array<string, mixed> */
function portfolio_config(): array
{
    $configPath = __DIR__ . '/config.local.php';
    if (!is_file($configPath)) {
        throw new RuntimeException('Missing php/config.local.php. Copy config.example.php and add your local credentials.');
    }

    $config = require $configPath;
    if (!is_array($config) || !isset($config['database']) || !is_array($config['database'])) {
        throw new RuntimeException('The local configuration file is invalid.');
    }
    return $config;
}

function portfolio_database(): PDO
{
    $database = portfolio_config()['database'];
    $required = ['host', 'port', 'name', 'user', 'password'];
    foreach ($required as $key) {
        if (!array_key_exists($key, $database)) {
            throw new RuntimeException('The database configuration is incomplete.');
        }
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        (string) $database['host'],
        (int) $database['port'],
        (string) $database['name']
    );

    return new PDO($dsn, (string) $database['user'], (string) $database['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
