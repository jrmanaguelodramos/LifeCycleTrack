<?php
declare(strict_types=1);

function lifecycle_central_config(): array
{
    static $config = null;
    if (is_array($config)) return $config;
    $values = [];
    $path = dirname(__DIR__) . '/.env';
    if (is_readable($path)) {
        $parsed = parse_ini_file($path, false, INI_SCANNER_RAW);
        if (is_array($parsed)) $values = $parsed;
    }
    foreach (['CENTRAL_API_BASE_URL','CENTRAL_API_TOKEN','CENTRAL_API_ENABLED','CENTRAL_API_TIMEOUT'] as $key) {
        $processValue = getenv($key);
        if ($processValue !== false && $processValue !== '') $values[$key] = $processValue;
    }
    $timeout = (int) ($values['CENTRAL_API_TIMEOUT'] ?? 8);
    $config = [
        'enabled' => strtolower(trim((string) ($values['CENTRAL_API_ENABLED'] ?? 'false'))) === 'true',
        'base_url' => rtrim(trim((string) ($values['CENTRAL_API_BASE_URL'] ?? '')), '/'),
        'token' => trim((string) ($values['CENTRAL_API_TOKEN'] ?? '')),
        'timeout' => max(1, min(30, $timeout > 0 ? $timeout : 8)),
    ];
    return $config;
}

function lifecycle_integration_uuid(): string
{
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    $hex = bin2hex($bytes);
    return substr($hex,0,8) . '-' . substr($hex,8,4) . '-' . substr($hex,12,4) . '-' . substr($hex,16,4) . '-' . substr($hex,20);
}

function lifecycle_central_payload(array $row, string $entityType): array
{
    $row['entity_type'] = $entityType;
    $row['lifecycle_status'] = $row['lifecycle_status'] ?? $row['status'] ?? null;
    $row['cost'] = $row['cost'] ?? $row['acquisition_cost'] ?? null;
    $row['basis'] = $row['basis'] ?? $row['cost_type'] ?? null;
    $row['schedule'] = $row['schedule'] ?? $row['usage_schedule'] ?? null;
    $allowed = [
        'id','entity_type','asset_tag','name','brand','model','category','location','date_acquired',
        'acquisition_year','is_estimated_year','condition','lifecycle_status','quantity','basis','cost',
        'schedule','hours_per_day','asset_type','power_source','retired_at','disposed_at','created_at','updated_at',
    ];
    $payload = array_intersect_key($row, array_flip($allowed));
    $payload['id'] = (string) ($row['id'] ?? '');
    $sourceTime = $row['updated_at'] ?? $row['source_updated_at'] ?? $row['created_at'] ?? null;
    $payload['source_updated_at'] = $sourceTime
        ? (new DateTimeImmutable((string) $sourceTime))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.u\Z')
        : (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.u\Z');
    return $payload;
}

/** Insert the canonical event atomically with the source facility/equipment update. */
function lifecycle_central_enqueue(PDO $pdo, array $payload): void
{
    $config = lifecycle_central_config();
    if (!$config['enabled'] || $config['base_url'] === '' || $config['token'] === '' || empty($payload['id'])) return;
    $entityType = (string) ($payload['entity_type'] ?? '');
    if (!in_array($entityType, ['equipment','facility'], true)) return;

    $table = $entityType === 'equipment' ? 'equipment' : 'facilities';
    $sourceTime = $pdo->prepare("SELECT updated_at FROM public.{$table} WHERE id = :id");
    $sourceTime->execute(['id' => $payload['id']]);
    $updatedAt = $sourceTime->fetchColumn();
    if ($updatedAt !== false && $updatedAt !== null) $payload['updated_at'] = (string) $updatedAt;
    $payload = lifecycle_central_payload($payload, $entityType);

    $eventId = lifecycle_integration_uuid();
    $correlationId = lifecycle_integration_uuid();
    $event = [
        'event_id' => $eventId,
        'event_type' => 'lifecycle.asset.upserted',
        'source' => 'LifeCycleTrack',
        'destination' => 'MaintainTrack',
        'version' => '1.0',
        'correlation_id' => $correlationId,
        'occurred_at' => $payload['source_updated_at'],
        'source_id' => (string) $payload['id'],
        'record_type' => 'lifecycle_asset',
        'payload' => $payload,
    ];
    $stmt = $pdo->prepare(
        'INSERT INTO public.central_sync_outbox
            (endpoint, source_id, payload, event_id, event_type, status, source_updated_at, record_type, event_version, correlation_id)
         VALUES
            (:endpoint, :source_id, CAST(:payload AS jsonb), :event_id, :event_type, :status, :source_updated_at, :record_type, :event_version, :correlation_id)'
    );
    $stmt->execute([
        'endpoint' => 'api/v1/events',
        'source_id' => (string) $payload['id'],
        'payload' => json_encode($event, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        'event_id' => $eventId,
        'event_type' => $event['event_type'],
        'status' => 'pending',
        'source_updated_at' => $payload['source_updated_at'],
        'record_type' => 'lifecycle_asset',
        'event_version' => '1.0',
        'correlation_id' => $correlationId,
    ]);
}

/**
 * CLI outbox publisher. Web requests call this legacy helper too, so they
 * return immediately; workers claim rows with expiring leases and can restart
 * safely after a crash.
 */
function lifecycle_central_flush(PDO $pdo, int $limit = 20): void
{
    if (PHP_SAPI !== 'cli') return;
    $config = lifecycle_central_config();
    if (!$config['enabled'] || $config['base_url'] === '' || $config['token'] === '' || !function_exists('curl_init')) {
        throw new RuntimeException('Central publisher configuration or cURL is unavailable');
    }
    if (!str_starts_with($config['base_url'], 'https://')) throw new RuntimeException('Central API must use HTTPS');

    $workerId = gethostname() . ':' . getmypid() . ':' . bin2hex(random_bytes(4));
    $pdo->beginTransaction();
    try {
        $claim = $pdo->prepare(
            "WITH eligible AS (
                SELECT id FROM public.central_sync_outbox
                 WHERE delivered_at IS NULL
                   AND (next_attempt_at IS NULL OR next_attempt_at <= now())
                   AND (lease_until IS NULL OR lease_until <= now())
                 ORDER BY created_at,id
                 FOR UPDATE SKIP LOCKED LIMIT :limit
             )
             UPDATE public.central_sync_outbox o
                SET lease_until=now()+interval '2 minutes', locked_by=:worker, status='processing'
               FROM eligible e WHERE o.id=e.id
             RETURNING o.id,o.endpoint,o.payload,o.attempts"
        );
        $claim->bindValue(':limit', max(1, min(50, $limit)), PDO::PARAM_INT);
        $claim->bindValue(':worker', $workerId, PDO::PARAM_STR);
        $claim->execute();
        $rows = $claim->fetchAll(PDO::FETCH_ASSOC);
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }

    foreach ($rows as $row) {
        $endpoint = ltrim((string) $row['endpoint'], '/');
        $url = $config['base_url'] . '/' . $endpoint;
        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => (string) $row['payload'],
            CURLOPT_HTTPHEADER => ['Accept: application/json','Content-Type: application/json','Authorization: Bearer ' . $config['token']],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => min(5, $config['timeout']),
            CURLOPT_TIMEOUT => $config['timeout'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $response = curl_exec($handle);
        $statusCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $error = curl_error($handle);
        curl_close($handle);

        if ($response !== false && $statusCode >= 200 && $statusCode < 300) {
            $done = $pdo->prepare("UPDATE public.central_sync_outbox SET delivered_at=now(),attempts=attempts+1,last_error=NULL,next_attempt_at=NULL,lease_until=NULL,locked_by=NULL,status='published' WHERE id=:id AND locked_by=:worker");
            $done->execute(['id' => $row['id'], 'worker' => $workerId]);
            continue;
        }

        $attempts = (int) $row['attempts'] + 1;
        $delay = min(3600, 15 * (2 ** min(7, max(0, $attempts - 1))));
        $failure = $error !== '' ? $error : 'Central API returned HTTP ' . $statusCode;
        $failed = $pdo->prepare("UPDATE public.central_sync_outbox SET attempts=:attempts,last_error=:error,next_attempt_at=now()+(:delay * interval '1 second'),lease_until=NULL,locked_by=NULL,status='pending' WHERE id=:id AND locked_by=:worker");
        $failed->execute(['attempts' => $attempts, 'error' => substr($failure,0,500), 'delay' => $delay, 'id' => $row['id'], 'worker' => $workerId]);
        error_log('LifeCycleTrack Central outbox publish failed: HTTP ' . $statusCode . ' ' . $error);
    }
}
