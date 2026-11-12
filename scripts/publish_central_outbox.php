<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/central_sync.php';

try {
    $limit = isset($argv[1]) ? (int) $argv[1] : 20;
    lifecycle_central_flush($pdo, max(1, min(50, $limit)));
    fwrite(STDOUT, "LifecycleTrack Central outbox batch completed.\n");
} catch (Throwable $error) {
    error_log('LifecycleTrack Central outbox worker failed: ' . $error->getMessage());
    fwrite(STDERR, "LifecycleTrack Central outbox worker failed; see the PHP error log.\n");
    exit(1);
}
