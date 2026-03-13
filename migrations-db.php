<?php

declare(strict_types=1);

$root = __DIR__;

if (file_exists($root . '/vendor/autoload.php')) {
    require_once $root . '/vendor/autoload.php';
}

if (class_exists(\Dotenv\Dotenv::class) && file_exists($root . '/.env')) {
    \Dotenv\Dotenv::createImmutable($root)->safeLoad();
}

$env = static function (string $key, mixed $default = null): mixed {
    return $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default;
};

return [
    'driver' => 'pdo_mysql',
    'host' => (string) $env('DB_HOST', '127.0.0.1'),
    'port' => (int) $env('DB_PORT', 3306),
    'dbname' => (string) $env('DB_NAME', 'bolakaz'),
    'user' => (string) $env('DB_USER', 'root'),
    'password' => (string) $env('DB_PASS', ''),
    'charset' => (string) $env('DB_CHARSET', 'utf8mb4'),
];
