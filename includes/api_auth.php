<?php
declare(strict_types=1);

require_once __DIR__ . '/api-response.php';

function requireApiToken(): void
{
    $expectedToken = getenv('MAINTENANCE_API_TOKEN') ?: '';

    if ($expectedToken === '') {
        error_log('Maintenance API token is not configured.');

        sendApiError(
            503,
            'API_NOT_CONFIGURED',
            'The API is temporarily unavailable.'
        );
    }

    $authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    if (
        !preg_match(
            '/^Bearer\s+(\S+)$/i',
            $authorization,
            $matches
        )
    ) {
        sendApiError(
            401,
            'UNAUTHORIZED',
            'A valid bearer token is required.'
        );
    }

    if (!hash_equals($expectedToken, $matches[1])) {
        sendApiError(
            401,
            'UNAUTHORIZED',
            'A valid bearer token is required.'
        );
    }
}