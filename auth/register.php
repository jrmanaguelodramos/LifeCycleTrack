<?php
session_start();

$envFile = __DIR__ . '/../.env';
$env = is_file($envFile)
    ? parse_ini_file($envFile, false, INI_SCANNER_RAW)
    : false;

$supabaseUrl = rtrim((string)($env['SUPABASE_URL'] ?? ''), '/');
$supabaseKey = (string)($env['SUPABASE_KEY'] ?? '');

$message = '';
$success = '';

if ($supabaseUrl === '' || $supabaseKey === '') {
    $message = 'Supabase configuration is missing. Check the .env file.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $message === '') {

    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    /*
     * Validate input
     */
    if ($name === '') {
        $message = 'Please enter your name.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirmPassword) {
        $message = 'Passwords do not match.';
    }

    /*
     * Create Supabase user
     */
    if ($message === '') {

        $payload = [
            'email' => $email,
            'password' => $password,
            'data' => [
                'name' => $name
            ]
        ];

        $ch = curl_init($supabaseUrl . '/auth/v1/signup');

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 15,

            CURLOPT_HTTPHEADER => [
                'apikey: ' . $supabaseKey,
                'Authorization: Bearer ' . $supabaseKey,
                'Content-Type: application/json'
            ],

            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        /*
         * Connection error
         */
        if ($response === false) {

            $message = 'Unable to connect to Supabase: ' . $curlError;

        } else {

            $data = json_decode($response, true) ?: [];

            /*
             * Successful registration
             */
            if ($httpCode >= 200 && $httpCode < 300) {

                /*
                 * If email confirmation is disabled,
                 * Supabase may return an access token immediately.
                 */
                if (!empty($data['access_token'])) {

                    session_regenerate_id(true);

                    $_SESSION['access_token'] = $data['access_token'];
                    $_SESSION['refresh_token'] = $data['refresh_token'] ?? '';
                    $_SESSION['user'] = $data['user'] ?? [];

                    header('Location: ../main/dashboard.php');
                    exit;
                }

                /*
                 * If email confirmation is enabled,
                 * send the user back to login.
                 */
                $success =
                    'Account created successfully. ' .
                    'Please check your email to verify your account before logging in.';

            } else {

                $message =
                    'Registration failed: ' .
                    ($data['error_description']
                        ?? $data['msg']
                        ?? $data['message']
                        ?? 'Unable to create account.');
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Account - LifeCycle Track</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../dist/output.css">
</head>

<body class="min-h-screen flex items-center justify-center p-6 bg-cover bg-center bg-no-repeat"
      style="background-image: url('../img/image.png');">

    <div class="w-full max-w-md backdrop-blur-3xl shadow-2xl px-10 py-9" style="border-radius:10px; background-color: rgba(255, 255, 255, 0.85); border: 1.5px solid rgba(255, 255, 255, 0.95); box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.6), 0 20px 25px -5px rgba(0, 0, 0, 0.1);">

        <img src="../img/logo.png" alt="Logo" class="block w-28 mx-auto mb-2">
        <div class="text-center text-[10px] font-bold tracking-[0.25em] uppercase mb-7" style="color: #2862d3;">
            Barangay Management System
        </div>

        <h2 class="text-center text-3xl font-extrabold mb-1" style="color: #0E3A63;">Create Account</h2>
        <p class="text-center text-sm text-slate-500 mb-6">Fill in your details to get started.</p>

        <?php if ($message): ?>
            <div class="bg-red-100 text-red-700 text-sm rounded-xl p-3 mb-4 text-center">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-100 text-green-700 text-sm rounded-xl p-3 mb-4 text-center">
                <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>

                <div class="mt-3">
                    <a href="login.php" class="font-bold underline">Go to Login</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>

        <form method="POST">

            <label class="block mb-2 text-sm font-bold text-gray-900">Full Name</label>
            <div class="relative mb-5">
                <i class="fa-solid fa-user absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
                <input
                    type="text"
                    name="name"
                    value="<?= htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    required
                    autocomplete="name"
                    placeholder="Enter your full name"
                    class="w-full h-11 pl-11 pr-4 border border-slate-200 rounded-xl bg-white text-sm text-gray-900
                        placeholder:text-slate-400 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15">
            </div>

            <label class="block mb-2 text-sm font-bold text-gray-900">Email</label>
            <div class="relative mb-5">
                <i class="fa-solid fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
                <input
                    type="email"
                    name="email"
                    value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    required
                    autocomplete="email"
                    placeholder="Enter your email"
                    class="w-full h-11 pl-11 pr-4 border border-slate-200 rounded-xl bg-white text-sm text-gray-900
                        placeholder:text-slate-400 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15">
            </div>

            <label class="block mb-2 text-sm font-bold text-gray-900">Password</label>
            <div class="relative mb-5">
                <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
                <input
                    type="password"
                    name="password"
                    id="password"
                    required
                    minlength="6"
                    autocomplete="new-password"
                    placeholder="At least 6 characters"
                    class="w-full h-11 pl-11 pr-11 border border-slate-200 rounded-xl bg-white text-sm text-gray-900
                        placeholder:text-slate-400 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15">
                <button type="button" data-toggle="password" aria-label="Show password"
                        class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-700">
                    <i class="fa-regular fa-eye"></i>
                </button>
            </div>

            <label class="block mb-2 text-sm font-bold text-gray-900">Confirm Password</label>
            <div class="relative mb-6">
                <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
                <input
                    type="password"
                    name="confirm_password"
                    id="confirm_password"
                    required
                    minlength="6"
                    autocomplete="new-password"
                    placeholder="Re-enter your password"
                    class="w-full h-11 pl-11 pr-11 border border-slate-200 rounded-xl bg-white text-sm text-gray-900
                        placeholder:text-slate-400 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15">
                <button type="button" data-toggle="confirm_password" aria-label="Show password"
                        class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-700">
                    <i class="fa-regular fa-eye"></i>
                </button>
            </div>

            <button type="submit"
                    class="w-full h-12 rounded-xl text-white text-sm font-bold bg-gradient-to-b from-[#2f80d1] to-[#1b6bb5]
                        shadow-lg shadow-blue-700/30 hover:shadow-xl hover:shadow-blue-700/40 active:translate-y-px transition cursor-pointer">
                Create Account
            </button>

            <p class="text-center text-sm text-slate-500 mt-5">
                Already have an account?
                <a href="login.php" class="font-bold text-[#155B92] hover:underline">Login</a>
            </p>

        </form>

        <?php endif; ?>

        <div class="text-center text-[11px] leading-relaxed text-slate-500 mt-6">
            Barangay GULOD Management System<br>
            <strong class="text-gray-900 font-bold">Better Services for a Stronger Barangay</strong>
        </div>
    </div>

    <script>
        document.querySelectorAll('[data-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const input = document.getElementById(btn.dataset.toggle);
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                btn.innerHTML = show
                    ? '<i class="fa-regular fa-eye-slash"></i>'
                    : '<i class="fa-regular fa-eye"></i>';
            });
        });
    </script>
</body>
</html>