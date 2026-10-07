<?php

session_start();

$env = parse_ini_file(__DIR__ . '/../.env');

$supabaseUrl = rtrim($env['SUPABASE_URL'], '/');
$supabaseKey = $env['SUPABASE_KEY'];

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $ch = curl_init(
        $supabaseUrl . '/auth/v1/token?grant_type=password'
    );

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'apikey: ' . $supabaseKey,
            'Authorization: Bearer ' . $supabaseKey,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'email' => $email,
            'password' => $password
        ])
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    $data = json_decode($response, true);

   if ($httpCode >= 200 && $httpCode < 300) {

    $_SESSION['access_token'] = $data['access_token'];
    $_SESSION['user'] = $data['user'];

    header("Location: ../main/dashboard.php");
    exit;

    } else {

        $message = "Login failed: " .
            ($data['error_description'] ?? 'Invalid email or password.');

    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../dist/output.css">
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center bg-[url('../image.png')] bg-cover bg-no-repeat">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-sm">
        <h2 class="text-2xl font-bold text-center mb-6">Login</h2>
<!--add test branch-->
    <?php if ($message): ?>
        <p class="text-red-500 text-sm mb-4">
            <?= htmlspecialchars($message) ?>
        </p>
    <?php endif; ?>

    <form method="POST">
        <label class="block mb-1 text-sm font-medium">Email</label>
        <input
            type="email"
            name="email"
            required
            class="w-full border border-gray-300 rounded px-3 py-2 mb-4
                   focus:outline-none focus:border-blue-500">

        <label class="block mb-1 text-sm font-medium"> Password </label>

        <input
            type="password"
            name="password"
            required
            class="w-full border border-gray-300 rounded px-3 py-2 mb-5
                   focus:outline-none focus:border-blue-500">

        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">Login</button>

        <div class="text-center my-4 text-gray-400">OR</div>

        <a href="google.php" class="w-full flex items-center justify-center gap-2 border border-gray-300 py-2 rounded hover:bg-gray-100">
            <i class="fab fa-google"></i>Sign in with Google </a>

    </form>
</div>
</body>

</html>