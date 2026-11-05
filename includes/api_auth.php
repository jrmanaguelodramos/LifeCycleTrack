<?php
declare(strict_types=1);

require_once __DIR__ . '/api_response.php';

function requireApiToken(): void
{
    $expectedToken = getenv('MAINTENANCE_API_TOKEN') ?: '';

    // Apache/XAMPP may not inherit environment variables set in PowerShell.
    // For local demos, also allow the token to be stored in the app's .env file.
    if ($expectedToken === '') {
        $envFile = dirname(__DIR__) . '/.env';
        if (is_file($envFile)) {
            $env = parse_ini_file($envFile, false, INI_SCANNER_RAW);
            $expectedToken = is_array($env)
                ? (string) ($env['MAINTENANCE_API_TOKEN'] ?? '')
                : '';
        }
    }

    if ($expectedToken === '') {
        error_log('Maintenance API token is not configured.');

        sendApiError(
            503,
            'API_NOT_CONFIGURED',
            'The API is temporarily unavailable.'
        );
    }

    $authorization = $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? '';

    // Some Apache/XAMPP setups expose the header through getallheaders().
    if ($authorization === '' && function_exists('getallheaders')) {
        foreach (getallheaders() as $name => $value) {
            if (strcasecmp((string) $name, 'Authorization') === 0) {
                $authorization = (string) $value;
                break;
            }
        }
    }

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
            'The Authorization header with a Bearer token was not received.'
        );
    }

    if (!hash_equals($expectedToken, $matches[1])) {
        sendApiError(
            401,
            'UNAUTHORIZED',
            'The Bearer token does not match MAINTENANCE_API_TOKEN.'
        );
    }
}
