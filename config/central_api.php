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

/** Best-effort, privacy-filtered push; a central outage never blocks local asset changes. */
function lifecycle_central_sync_asset(string $entityType, array $record): void
{
    $config = lifecycle_central_config();
    if (!$config['enabled'] || $config['base_url'] === '' || $config['token'] === '' || !function_exists('curl_init')) return;
    if (!preg_match('#^https?://#i', $config['base_url'])) {
        error_log('Central API asset sync skipped because CENTRAL_API_BASE_URL must use HTTP or HTTPS.');
        return;
    }
    if (!in_array($entityType, ['equipment', 'facility'], true) || empty($record['id'])) return;

    $payload = [
        'id' => (string) $record['id'],
        'entity_type' => $entityType,
        'asset_tag' => $record['asset_tag'] ?? null,
        'name' => $record['name'] ?? null,
        'brand' => $record['brand'] ?? null,
        'model' => $record['model'] ?? null,
        'category' => $record['category'] ?? null,
        'location' => $record['location'] ?? null,
        'date_acquired' => $record['date_acquired'] ?? null,
        'acquisition_year' => $record['acquisition_year'] ?? null,
        'is_estimated_year' => $record['is_estimated_year'] ?? null,
        'condition' => $record['condition'] ?? null,
        'lifecycle_status' => $record['status'] ?? null,
        'quantity' => $record['quantity'] ?? null,
        'basis' => $record['cost_type'] ?? null,
        'cost' => $record['acquisition_cost'] ?? null,
        'schedule' => $record['usage_schedule'] ?? null,
        'hours_per_day' => $record['hours_per_day'] ?? null,
        'remarks' => $record['remarks'] ?? null,
        'updated_at' => $record['updated_at'] ?? null,
    ];
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($json === false) return;

    $curl = curl_init($config['base_url'] . '/api/v1/ingest/lifecycle_asset');
    $options = [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $json,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $config['timeout'],
        CURLOPT_CONNECTTIMEOUT => min(5, $config['timeout']),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $config['token'],
            'Content-Type: application/json',
            'Accept: application/json',
        ],
    ];
    if ($config['ca_info'] !== '') $options[CURLOPT_CAINFO] = $config['ca_info'];
    curl_setopt_array($curl, $options);
    curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    if ($status < 200 || $status >= 300) {
        error_log('Central API asset sync failed (HTTP ' . $status . ')' . ($error !== '' ? ': ' . $error : '.'));
    }
}
