<?php
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $_ENV[trim($name)] = trim($value, " \t\n\r\0\x0B\"'");
        }
    }
}

return [
    'api_key' => $_ENV['OPENROUTER_API_KEY'] ?? getenv('OPENROUTER_API_KEY') ?? '',

    'api_url' => 'https://openrouter.ai/api/v1/chat/completions',

    'model' => 'openrouter/free',

    'site_url' => 'http://localhost',

    'site_name' => 'Anomaly Analytics',

    'timeout' => 30,

    'max_tokens' => 600,

    'temperature' => 0.3,

    'cache_seconds' => 21600
];
