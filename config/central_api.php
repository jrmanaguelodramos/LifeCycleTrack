<?php
declare(strict_types=1);

function lifecycle_central_config(): array
{
    static $config = null;
    if (is_array($config)) return $config;

    $values = [];
    $envPath = dirname(__DIR__) . '/.env';
    if (is_readable($envPath)) {
        $parsed = parse_ini_file($envPath, false, INI_SCANNER_RAW);
        if (is_array($parsed)) $values = $parsed;
    }
    $read = static function (string $key, string $default = '') use ($values): string {
        $fromProcess = getenv($key);
        return trim((string) ($fromProcess !== false && $fromProcess !== '' ? $fromProcess : ($values[$key] ?? $default)));
    };

    $config = [
        'base_url' => rtrim($read('CENTRAL_API_BASE_URL'), '/'),
        'token' => $read('CENTRAL_API_TOKEN'),
        'enabled' => strtolower($read('CENTRAL_API_ENABLED', 'false')) === 'true',
        'timeout' => max(1, (int) $read('CENTRAL_API_TIMEOUT', '8')),
        'ca_info' => $read('CURL_CAINFO'),
    ];
    return $config;
}

/** Deprecated compatibility hook. Active writes use central_sync.php's transactional outbox. */
function lifecycle_central_sync_asset(string $entityType, array $record): void
{
    error_log('Deprecated direct Central sync hook ignored; enqueue lifecycle changes through config/central_sync.php.');
}
