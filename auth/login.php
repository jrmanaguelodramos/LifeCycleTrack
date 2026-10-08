<?php

declare(strict_types=1);
session_start();

$envFile = __DIR__ . '/../.env';
$env = is_file($envFile) ? parse_ini_file($envFile, false, INI_SCANNER_RAW) : false;
$supabaseUrl = rtrim((string)($env['SUPABASE_URL'] ?? ''), '/');
$supabaseKey = (string)($env['SUPABASE_KEY'] ?? '');

$message = '';

if ($supabaseUrl === '' || $supabaseKey === '') {
    $message = 'Supabase configuration is missing. Check the .env file.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $message === '') {
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    $ch = curl_init($supabaseUrl . '/auth/v1/token?grant_type=password');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => [
            'apikey: ' . $supabaseKey,
            'Authorization: Bearer ' . $supabaseKey,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode(['email' => $email, 'password' => $password]),
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        $message = 'Unable to connect to Supabase: ' . $curlError;
    } else {
        $data = json_decode($response, true) ?: [];
        if ($httpCode >= 200 && $httpCode < 300 && !empty($data['access_token'])) {
            session_regenerate_id(true);
            $_SESSION['access_token'] = $data['access_token'];
            $_SESSION['refresh_token'] = $data['refresh_token'] ?? '';
            $_SESSION['user'] = $data['user'] ?? [];
            header('Location: ../main/dashboard.php');
            exit;
        }
        $message = 'Login failed: ' . ($data['error_description'] ?? $data['msg'] ?? 'Invalid email or password.');
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LifeCycle Track</title>
    <link rel="stylesheet" href="../dist/output.css">
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center bg-cover bg-no-repeat">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-sm">
        <h2 class="text-2xl font-bold text-center mb-6">Login</h2>
        <?php if ($message): ?><p class="text-red-500 text-sm mb-4"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
        <form method="POST">
            <label class="block mb-1 text-sm font-medium">Email</label>
            <input type="email" name="email" required class="w-full border border-gray-300 rounded px-3 py-2 mb-4">
            <label class="block mb-1 text-sm font-medium">Password</label>
            <input type="password" name="password" required class="w-full border border-gray-300 rounded px-3 py-2 mb-5">
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">Login</button>
            <div class="text-center my-4 text-gray-400">OR</div>
            <a href="google.php" class="w-full flex items-center justify-center gap-2 border border-gray-300 py-2 rounded hover:bg-gray-100">Sign in with Google</a>
        </form>
        <p class="text-center text-sm text-gray-500 mt-5">
            Don't have an account?
            <a href="register.php"
                class="text-blue-600 hover:underline font-medium">
                Create Account
            </a>
        </p>
    </div>
</body>

</html>