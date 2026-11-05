<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    echo json_encode([
        'error' => [
            'code' => 'METHOD_NOT_ALLOWED',
            'message' => 'Use GET to check LifecycleTrack API connectivity.',
        ],
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

http_response_code(200);
echo json_encode([
    'data' => [
        'service' => 'LifecycleTrack API',
        'status' => 'online',
        'message' => 'LifecycleTrack API can be reached.',
    ],
    'meta' => [
        'checked_at' => gmdate('Y-m-d\TH:i:s\Z'),
    ],
], JSON_UNESCAPED_SLASHES);
