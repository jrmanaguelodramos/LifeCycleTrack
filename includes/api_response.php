<?php
declare(strict_types=1);

function sendApiError(
    int $status,
    string $code,
    string $message
): never {
    http_response_code($status);

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');

    echo json_encode([
        'error' => [
            'code' => $code,
            'message' => $message,
            'request_id' => bin2hex(random_bytes(8)),
        ],
    ]);

    exit;
}