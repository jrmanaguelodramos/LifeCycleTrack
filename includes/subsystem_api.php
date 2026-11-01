<?php
declare(strict_types=1);

function subsystemGet(
    string $baseUrl,
    string $token,
    string $endpoint
): array {
    if ($token === '') {
        throw new RuntimeException(
            'Subsystem API token is not configured.'
        );
    }

    // Only allow known relative endpoint paths.
    if (
        $endpoint === '' ||
        str_starts_with($endpoint, '/') ||
        str_contains($endpoint, '..') ||
        preg_match('/[\r\n]/', $endpoint)
    ) {
        throw new InvalidArgumentException(
            'Invalid API endpoint.'
        );
    }

    $url = rtrim($baseUrl, '/') . '/' . $endpoint;
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
        ],
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => false,
    ]);

    $body = curl_exec($ch);
    $status = (int) curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );

    $curlError = curl_error($ch);
    // curl_close($ch);

    if ($body === false) {
        throw new RuntimeException(
            'Subsystem connection failed: ' . $curlError
        );
    }

    if ($status < 200 || $status >= 300) {
        throw new RuntimeException(
            'Subsystem API returned HTTP ' . $status
        );
    }

    $decoded = json_decode($body, true);

    if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
        throw new RuntimeException(
            'Subsystem returned invalid JSON.'
        );
    }

    return $decoded;
}