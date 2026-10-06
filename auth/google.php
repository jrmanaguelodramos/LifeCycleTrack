<?php

$env = parse_ini_file(__DIR__ . '/../.env');

$supabaseUrl = rtrim($env['SUPABASE_URL'], '/');

$redirectTo = 'http://localhost/SIA101/auth/callback.php';

$url = $supabaseUrl
    . '/auth/v1/authorize'
    . '?provider=google'
    . '&redirect_to=' . urlencode($redirectTo);

header("Location: " . $url);
exit;