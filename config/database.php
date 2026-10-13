<?php

$envFile = __DIR__ . '/../.env';

if (!file_exists($envFile)) {
    die('.env file not found at: ' . $envFile);
}

$env = parse_ini_file($envFile);

if ($env === false) {
    die('Failed to read .env file. Check its syntax.');
}

$host = $env['DB_HOST'] ?? '';
$port = $env['DB_PORT'] ?? '5432';
$dbname = $env['DB_DATABASE'] ?? 'postgres';
$username = $env['DB_USERNAME'] ?? '';
$password = $env['DB_PASSWORD'] ?? '';

if (
    $host === '' ||
    $username === '' ||
    $password === ''
) {
    die('Missing DB_HOST, DB_USERNAME, or DB_PASSWORD in .env.');
}

try {
    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => true,
            PDO::ATTR_TIMEOUT => 10,
        ]
    );

} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());

    if (($env['APP_ENV'] ?? '') === 'development') {
        die('Database connection failed: ' .
            htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
    }

    die('Database connection failed. Check the server logs.');
}
