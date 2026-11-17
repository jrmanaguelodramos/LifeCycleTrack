<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
http_response_code(410);
echo json_encode([
    'error' => [
        'code' => 'CENTRAL_ROUTING_REQUIRED',
        'message' => 'Direct subsystem delivery is retired. Publish and consume maintenance events through the Central Server.',
    ],
], JSON_UNESCAPED_SLASHES);
