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

<<<<<<< HEAD
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
=======
<body class="min-h-screen flex items-center justify-center p-6 bg-cover bg-center bg-no-repeat"
      style="background-image: url('../img/image.png');">

    <div class="w-full max-w-md backdrop-blur-3xl shadow-2xl px-10 py-9" style="border-radius:10px; background-color: rgba(255, 255, 255, 0.85); border: 1.5px solid rgba(255, 255, 255, 0.95); box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.6), 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
        <img src="../img/logo.png" alt="Logo" class="block w-28 mx-auto mb-2">
        <div class="text-center text-[10px] font-bold tracking-[0.25em] uppercase mb-7" style="color: #2862d3;">
            Barangay Management System
        </div>

        <h2 class="text-center text-3xl font-extrabold mb-1" style="color: #0E3A63;">Welcome!</h2>
        <p class="text-center text-sm text-slate-500 mb-6">Sign in to continue to your account.</p>
            <!--add test branch-->
            <?php if ($message): ?>
                <p class="text-red-500 text-sm text-center mb-4">
                    <?= htmlspecialchars($message) ?>
                </p>
            <?php endif; ?>

            <form method="POST">
                <label class="block mb-2 text-sm font-bold text-gray-900">Email</label>
                <div class="relative mb-5">
                    <i class="fa-solid fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
                    <input
                        type="email"
                        name="email"
                        required
                        placeholder="Enter your email"
                        class="w-full h-11 pl-11 pr-4 border border-slate-200 rounded-xl bg-white text-sm text-gray-900
                            placeholder:text-slate-400 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15">
                </div>

                <label class="block mb-2 text-sm font-bold text-gray-900">Password</label>
                <div class="relative mb-4">
                    <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
                    <input
                        type="password"
                        name="password"
                        id="password"
                        required
                        placeholder="Enter your password"
                        class="w-full h-11 pl-11 pr-11 border border-slate-200 rounded-xl bg-white text-sm text-gray-900
                            placeholder:text-slate-400 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15">
                    <button type="button" id="togglePassword" aria-label="Show password"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-700">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>

                <div class="flex items-center justify-between text-xs mb-6">
                    <label class="flex items-center gap-2 text-slate-500 cursor-pointer">
                        <input type="checkbox" class="rounded border-slate-300 text"> Remember me
                    </label>
                    <a href="#" class="font-bold text-red-500 hover:underline">Forgot password?</a>
                </div>

                <button type="submit"
                        class="w-full h-12 rounded-xl text-white text-sm font-bold bg-gradient-to-b from-[#2f80d1] to-[#1b6bb5]
                            shadow-lg shadow-blue-700/30 hover:shadow-xl hover:shadow-blue-700/40 active:translate-y-px transition cursor-pointer">
                    Log In
                </button>

                <div class="text-center my-4 text-xs text-slate-400">OR</div>

                <a href="google.php"
                class="w-full h-11 flex items-center justify-center gap-2 border border-slate-200 rounded-xl bg-white
                        text-sm text-gray-900 hover:bg-slate-50 transition">
                    <i class="fab fa-google"></i>Sign in with Google </a>
>>>>>>> aa7369e06954f28d1d5b67ad5ad4def0aa614def

            </form>
                <div class="text-center text-[11px] leading-relaxed text-slate-500 mt-6">
                    Barangay GULOD Management System<br>
                    <strong class="text-gray-900 font-bold">Better Services for a Stronger Barangay</strong>
                </div>
            </div>

            <script>
                const pw = document.getElementById('password');
                const eye = document.getElementById('togglePassword');
                eye.addEventListener('click', () => {
                    const show = pw.type === 'password';
                    pw.type = show ? 'text' : 'password';
                    eye.innerHTML = show
                        ? '<i class="fa-regular fa-eye-slash"></i>'
                        : '<i class="fa-regular fa-eye"></i>';
                });
            </script>
        </body>
</html> 