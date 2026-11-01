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
            'message' => 'Use GET for this endpoint.'
        ]
    ]);
    exit;
}

// Temporary connectivity test.
// Replace with real database queries and authentication
// before exposing actual maintenance data.
http_response_code(200);

echo json_encode([
    'data' => [
        [
            'work_order_id' => 'WO-2026-0042',
            'asset_id' => 'AST-0015',
            'status' => 'in_progress',
            'maintenance_cost' => 1250.00,
            'updated_at' => '2026-10-18T06:30:00Z'
        ]
    ],
    'meta' => [
        'next_cursor' => null
    ]
], JSON_UNESCAPED_SLASHES);