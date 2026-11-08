<?php
declare(strict_types=1);

function lifecycle_central_config(): array
{
    static $config;
    if (isset($config)) return $config;
    $values = parse_ini_file(dirname(__DIR__) . '/.env', false, INI_SCANNER_RAW) ?: [];
    foreach (['CENTRAL_API_BASE_URL', 'CENTRAL_API_TOKEN', 'CENTRAL_API_ENABLED', 'CENTRAL_API_TIMEOUT'] as $key) {
        $envValue = getenv($key);
        if ($envValue !== false && $envValue !== '') $values[$key] = $envValue;
    }
    $timeout = (int) ($values['CENTRAL_API_TIMEOUT'] ?? 8);
    $config = [
        'enabled' => strtolower(trim((string) ($values['CENTRAL_API_ENABLED'] ?? 'false'))) === 'true',
        'base_url' => rtrim(trim((string) ($values['CENTRAL_API_BASE_URL'] ?? '')), '/'),
        'token' => trim((string) ($values['CENTRAL_API_TOKEN'] ?? '')),
        'timeout' => $timeout > 0 ? min(30, $timeout) : 8,
    ];
    return $config;
}

function lifecycle_central_payload(array $row, string $entityType): array
{
    $row['lifecycle_status'] = $row['lifecycle_status'] ?? $row['status'] ?? null;
    $row['cost'] = $row['cost'] ?? $row['acquisition_cost'] ?? null;
    $row['basis'] = $row['basis'] ?? $row['cost_type'] ?? null;
    $row['schedule'] = $row['schedule'] ?? $row['usage_schedule'] ?? null;
    $allowed = [
        'id', 'entity_type', 'asset_tag', 'name', 'brand', 'model', 'category', 'location',
        'date_acquired', 'acquisition_year', 'is_estimated_year', 'condition', 'lifecycle_status',
        'quantity', 'basis', 'cost', 'schedule', 'hours_per_day', 'created_at', 'updated_at',
    ];
    $payload = ['entity_type' => $entityType];
    foreach ($allowed as $field) if (array_key_exists($field, $row)) $payload[$field] = $row[$field];
    $payload['source_updated_at'] = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.u\Z');
    return $payload;
}

/** Write the outbox row inside the source-record transaction. */
function lifecycle_central_enqueue(PDO $pdo, array $payload): void
{
    $config = lifecycle_central_config();
    if (!$config['enabled'] || $config['base_url'] === '' || $config['token'] === '' || empty($payload['id'])) return;
    $stmt = $pdo->prepare('INSERT INTO public.central_sync_outbox (endpoint, source_id, payload) VALUES (:endpoint, :source_id, CAST(:payload AS jsonb))');
    $stmt->execute([
        'endpoint' => 'api/v1/ingest/lifecycle_asset',
        'source_id' => (string) $payload['id'],
        'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR),
    ]);
}

/** Flush queued snapshots after the source transaction commits; failures remain replayable. */
function lifecycle_central_flush(PDO $pdo, int $limit = 20): void
{
    $config = lifecycle_central_config();
    if (!$config['enabled'] || $config['base_url'] === '' || $config['token'] === '' || !function_exists('curl_init')) return;
    $stmt = $pdo->prepare('SELECT id, endpoint, payload, attempts, next_attempt_at FROM public.central_sync_outbox WHERE delivered_at IS NULL ORDER BY created_at, id LIMIT :limit');
    $stmt->bindValue(':limit', max(1, min(50, $limit)), PDO::PARAM_INT);
    $stmt->execute();
    foreach ($stmt->fetchAll() as $row) {
        if (!empty($row['next_attempt_at']) && strtotime((string) $row['next_attempt_at']) > time()) break;
        $handle = curl_init($config['base_url'] . '/' . ltrim((string) $row['endpoint'], '/'));
        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => (string) $row['payload'],
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json', 'Authorization: Bearer ' . $config['token']],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => min(5, $config['timeout']),
            CURLOPT_TIMEOUT => $config['timeout'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $error = curl_error($handle);
        curl_close($handle);
        if ($body !== false && $status >= 200 && $status < 300) {
            $done = $pdo->prepare('UPDATE public.central_sync_outbox SET delivered_at = now(), attempts = attempts + 1, last_error = NULL, next_attempt_at = NULL WHERE id = :id');
            $done->execute(['id' => $row['id']]);
            continue;
        }
        $attempts = (int) $row['attempts'] + 1;
        $delay = min(900, 15 * (2 ** min(6, max(0, $attempts - 1))));
        $failed = $pdo->prepare('UPDATE public.central_sync_outbox SET attempts = :attempts, last_error = :error, next_attempt_at = now() + (:delay * interval \'1 second\') WHERE id = :id');
        $failed->execute(['id' => $row['id'], 'attempts' => $attempts, 'delay' => $delay, 'error' => substr($error !== '' ? $error : 'Central API returned HTTP ' . $status, 0, 500)]);
        error_log('LifeCycleTrack Central API delivery failed with HTTP ' . $status . ': ' . $error);
        break;
    }
}
