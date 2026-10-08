<?php
// declare(strict_types=1);

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

    <link rel="stylesheet" href="../dist/output.css">
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center">

<div class="bg-white p-8 rounded-lg shadow-md w-full max-w-sm">

    <h2 class="text-2xl font-bold text-center mb-6">
        Create Account
    </h2>

    <?php if ($message): ?>

        <div class="bg-red-100 text-red-700 text-sm rounded p-3 mb-4">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>

    <?php endif; ?>


    <?php if ($success): ?>

        <div class="bg-green-100 text-green-700 text-sm rounded p-3 mb-4">
            <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>

            <div class="mt-3">
                <a
                    href="login.php"
                    class="font-semibold underline"
                >
                    Go to Login
                </a>
            </div>
        </div>

    <?php endif; ?>


    <?php if (!$success): ?>

    <form method="POST">

        <!-- Name -->

        <label class="block mb-1 text-sm font-medium">
            Full Name
        </label>

        <input
            type="text"
            name="name"
            value="<?= htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            required
            autocomplete="name"
            class="w-full border border-gray-300 rounded px-3 py-2 mb-4 focus:outline-none focus:border-blue-500"
        >


        <!-- Email -->

        <label class="block mb-1 text-sm font-medium">
            Email
        </label>

        <input
            type="email"
            name="email"
            value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            required
            autocomplete="email"
            class="w-full border border-gray-300 rounded px-3 py-2 mb-4 focus:outline-none focus:border-blue-500"
        >


        <!-- Password -->

        <label class="block mb-1 text-sm font-medium">
            Password
        </label>

        <input
            type="password"
            name="password"
            required
            minlength="6"
            autocomplete="new-password"
            class="w-full border border-gray-300 rounded px-3 py-2 mb-4 focus:outline-none focus:border-blue-500"
        >


        <!-- Confirm Password -->

        <label class="block mb-1 text-sm font-medium">
            Confirm Password
        </label>

        <input
            type="password"
            name="confirm_password"
            required
            minlength="6"
            autocomplete="new-password"
            class="w-full border border-gray-300 rounded px-3 py-2 mb-5 focus:outline-none focus:border-blue-500"
        >


        <!-- Submit -->

        <button
            type="submit"
            class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700"
        >
            Create Account
        </button>


        <!-- Login -->

        <p class="text-center text-sm text-gray-500 mt-5">

            Already have an account?

            <a
                href="login.php"
                class="text-blue-600 hover:underline font-medium"
            >
                Login
            </a>

        </p>

    </form>

    <?php endif; ?>

</div>

</body>
</html>