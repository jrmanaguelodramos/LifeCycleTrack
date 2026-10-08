<?php

$env = parse_ini_file(__DIR__ . '/../.env');

$supabaseUrl = rtrim($env['SUPABASE_URL'], '/');

// Codespaces sits behind a proxy, so check the forwarded headers too
$scheme = $_SERVER['HTTP_X_FORWARDED_PROTO']
    ?? ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
$host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'];

$redirectTo = $scheme . '://' . $host
    . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/callback.php';

$url = $supabaseUrl
    . '/auth/v1/authorize'
    . '?provider=google'
    . '&redirect_to=' . urlencode($redirectTo);

header("Location: " . $url);
exit;