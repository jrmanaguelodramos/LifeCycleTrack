<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/api_auth.php';
require_once __DIR__ . '/../../includes/api_response.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    sendApiError(
        405,
        'METHOD_NOT_ALLOWED',
        'Use POST to send a work order.'
    );
}

requireApiToken();

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (!str_contains(strtolower($contentType), 'application/json')) {
    sendApiError(
        415,
        'UNSUPPORTED_MEDIA_TYPE',
        'Send the request as application/json.'
    );
} 

$body = file_get_contents('php://input');
$workOrder = json_decode($body ?: '', true);

if (!is_array($workOrder) || json_last_error() !== JSON_ERROR_NONE) {
    sendApiError(
        400,
        'INVALID_JSON',
        'The request body must contain valid JSON.'
    );
}

$requiredFields = [
    'work_order_id',
    'asset_id',
    'status',
    'description',
    'updated_at',
];

foreach ($requiredFields as $field) {
    if (!isset($workOrder[$field]) || !is_string($workOrder[$field]) || trim($workOrder[$field]) === '') {
        sendApiError(
            422,
            'INVALID_FIELD',
            'The field ' . $field . ' is required and must be a non-empty string.'
        );
    }
}

$allowedStatuses = ['open', 'in_progress', 'completed', 'cancelled'];
if (!in_array($workOrder['status'], $allowedStatuses, true)) {
    sendApiError(
        422,
        'INVALID_STATUS',
        'Status must be open, in_progress, completed, or cancelled.'
    );
}

try {
    $updatedAt = new DateTimeImmutable($workOrder['updated_at']);
} catch (Exception) {
    sendApiError(
        422,
        'INVALID_UPDATED_AT',
        'updated_at must be a valid date and time.'
    );
}

if (!is_file(__DIR__ . '/../../.env')) {
    sendApiError(
        503,
        'DATABASE_NOT_CONFIGURED',
        'LifecycleTrack database settings are not configured.'
    );
}

require __DIR__ . '/../../config/database.php';

try {
    $statement = $pdo->prepare(
        'INSERT INTO maintenance_work_order_sync
            (work_order_id, asset_id, status, description, source_updated_at, synced_at)
         VALUES
            (:work_order_id, :asset_id, :status, :description, :source_updated_at, NOW())
         ON CONFLICT (work_order_id) DO UPDATE SET
            asset_id = EXCLUDED.asset_id,
            status = EXCLUDED.status,
            description = EXCLUDED.description,
            source_updated_at = EXCLUDED.source_updated_at,
            synced_at = NOW()
         WHERE EXCLUDED.source_updated_at >= maintenance_work_order_sync.source_updated_at'
    );

    $statement->execute([
        ':work_order_id' => $workOrder['work_order_id'],
        ':asset_id' => $workOrder['asset_id'],
        ':status' => $workOrder['status'],
        ':description' => $workOrder['description'],
        ':source_updated_at' => $updatedAt->format('Y-m-d H:i:sP'),
    ]);

    $wasSaved = $statement->rowCount() > 0;
} catch (PDOException $exception) {
    error_log('Maintenance work order sync failed: ' . $exception->getMessage());
    sendApiError(
        500,
        'DATABASE_WRITE_FAILED',
        'LifecycleTrack could not save the work order.'
    );
}

http_response_code(200);
echo json_encode([
    'data' => [
        'work_order_id' => $workOrder['work_order_id'],
        'asset_id' => $workOrder['asset_id'],
        'status' => $workOrder['status'],
        'saved_to_database' => $wasSaved,
        'message' => $wasSaved
            ? 'The synchronized work order was saved.'
            : 'This update was older than the copy already saved, so the database was left unchanged.',
    ],
    'meta' => [
        'request_id' => bin2hex(random_bytes(8)),
    ],
], JSON_UNESCAPED_SLASHES);
