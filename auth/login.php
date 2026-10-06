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
<html>

<head>
    <title>Document</title>
</head>

<body>

<h2>Login</h2>

<?php if ($message): ?>
    <p><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<form method="POST">

    <label>Email</label><br>
    <input type="email" name="email" required>

    <br><br>

    <label>Password</label><br>
    <input type="password" name="password" required>

    <br><br>

    <button type="submit">Login</button>

    <a href="google.php" class="google-btn">
        <i class="fab fa-google"></i>
        Sign in with Google
    </a>

</form>

</body>
</html>