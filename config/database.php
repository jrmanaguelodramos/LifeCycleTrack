<?php

$envFile = __DIR__ . '/../.env';

if (!file_exists($envFile)) {
    die('.env file not found.');
}

$env = parse_ini_file($envFile);

$host = $env['DB_HOST'];
$port = $env['DB_PORT'] ?? '5432';
$dbname = $env['DB_DATABASE'] ?? 'postgres';
$username = $env['DB_USERNAME'];
$password = $env['DB_PASSWORD'];

try {
    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}