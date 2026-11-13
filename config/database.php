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
$hostAddress = trim((string)($env['DB_HOSTADDR'] ?? ''));
if ($hostAddress === '' && filter_var($host, FILTER_VALIDATE_IP) === false) {
    // Prefer one resolved IPv4 address when available. On networks with
    // synthetic IPv6 DNS records, libpq can spend several connect timeouts
    // trying unreachable addresses before reaching Supabase over IPv4.
    $ipv4Addresses = gethostbynamel($host);
    if (is_array($ipv4Addresses) && $ipv4Addresses !== []) {
        $addressIndex = getmypid() % count($ipv4Addresses);
        $hostAddress = $ipv4Addresses[$addressIndex];
    }
}
$port = (string)($env['DB_PORT'] ?? '5432');
$dbname = (string)($env['DB_DATABASE'] ?? 'postgres');
$username = (string)$env['DB_USERNAME'];
$password = (string)$env['DB_PASSWORD'];

try {
    $addressOption = $hostAddress !== '' ? ";hostaddr=$hostAddress" : '';
    $dsn = "pgsql:host=$host$addressOption;port=$port;dbname=$dbname;sslmode=require;connect_timeout=5";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5,
        // Reuse the PostgreSQL connection within an Apache worker. The
        // remote Supabase handshake is much slower than the page queries.
        PDO::ATTR_PERSISTENT => true,
    ];

    try {
        $pdo = new PDO($dsn, $username, $password, $options);
    } catch (PDOException $directError) {
        if ($addressOption === '' || !str_starts_with((string)$directError->getCode(), '08')) {
            throw $directError;
        }
        // Fall back to normal address selection if this network cannot reach
        // IPv4 directly but can reach the database through its configured DNS.
        $fallbackDsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require;connect_timeout=5";
        $pdo = new PDO($fallbackDsn, $username, $password, $options);
    }

} catch (PDOException $e) {
    error_log('LifeCycleTrack database connection failed: ' . $e->getMessage());

    http_response_code(500);
    exit('The database is temporarily unavailable. Please try again later.');
}
