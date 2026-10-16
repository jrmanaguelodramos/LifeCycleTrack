<?php

$envFile = __DIR__ . '/../.env';

if (!is_file($envFile)) {
    error_log('LifeCycleTrack: .env file not found.');
    http_response_code(500);
    exit('Application configuration is incomplete. Contact the system administrator.');
}

$env = parse_ini_file($envFile, false, INI_SCANNER_RAW);
if (!is_array($env)) {
    error_log('LifeCycleTrack: unable to parse .env file.');
    http_response_code(500);
    exit('Application configuration is invalid. Contact the system administrator.');
}

$required = ['DB_HOST', 'DB_USERNAME', 'DB_PASSWORD'];
foreach ($required as $key) {
    if (!isset($env[$key]) || trim((string)$env[$key]) === '') {
        error_log('LifeCycleTrack: missing required database configuration key ' . $key . '.');
        http_response_code(500);
        exit('Application configuration is incomplete. Contact the system administrator.');
    }
}

$host = (string)$env['DB_HOST'];
$port = (string)($env['DB_PORT'] ?? '5432');
$dbname = (string)($env['DB_DATABASE'] ?? 'postgres');
$username = (string)$env['DB_USERNAME'];
$password = (string)$env['DB_PASSWORD'];

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
    error_log('LifeCycleTrack database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('The database is temporarily unavailable. Please try again later.');
}