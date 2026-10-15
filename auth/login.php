<?php
session_start();

$env = parse_ini_file(__DIR__ . '/../.env');

$supabaseUrl = rtrim($env['SUPABASE_URL'] ?? '', '/');
$supabaseKey = $env['SUPABASE_KEY'] ?? '';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($supabaseUrl) || empty($supabaseKey)) {
        $message = 'Authentication configuration is missing.';
    } else {

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
            ]),
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        curl_close($ch);

        $data = json_decode($response ?: '', true);

        if (
            $response !== false &&
            $httpCode >= 200 &&
            $httpCode < 300 &&
            !empty($data['access_token'])
        ) {
            $_SESSION['access_token'] = $data['access_token'];
            $_SESSION['refresh_token'] = $data['refresh_token'] ?? '';
            $_SESSION['user'] = $data['user'] ?? [];

            header('Location: ../main/dashboard.php');
            exit;

        } else {
            $message = $curlError
                ? 'Unable to connect to the authentication service. Please try again.'
                : 'Login failed: ' .
                    ($data['msg'] ??
                     $data['error_description'] ??
                     $data['message'] ??
                     'Invalid email or password.');
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LifeCycle Track | Login</title>
    <link rel="stylesheet"href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../dist/output.css">

    <style>
        #loginLoading {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background:white;
            font-family: 'Nunito', 'Segoe UI', Roboto, Arial, sans-serif;
        }

        #loginLoading .loading-card {
            width: 100%;
            max-width: 372px;
            min-height: 200px;
            padding: 48px 28px 36px;
            background: #ffffff;
            border-left: 6px solid #10375f;
            border-radius: 18px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        #loginLoading .loading-title {
            margin: 0 0 38px;
            color: #10375f;
            font-size: 20px;
            line-height: 1.3;
            font-weight: 800;
        }

        #loginLoading .loading-spinner {
            width: 50px;
            height: 50px;
            margin: 0 auto 30px;
            border: 5px solid #e5e7eb;
            border-right-color: #17598f;
            border-bottom-color: #17598f;
            border-radius: 50%;
            animation: loginSpin 0.8s linear infinite;
        }

        #loginLoading .loading-message {
            margin: 0 0 6px;
            color: #111827;
            font-size: 1.25rem;
            line-height: 1.3;
            font-weight: 800;
        }

        #loginLoading .loading-description {
            margin: 0;
            color: #475569;
            font-size: 0.875rem;
            line-height: 1.5;
        }

        @keyframes loginSpin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 480px) {
            #loginLoading .loading-card {
                min-height: auto;
                padding: 40px 20px 32px;
            }

            #loginLoading .loading-title {
                font-size: 23px;
            }

            #loginLoading .loading-message {
                font-size: 21px;
            }

            #loginLoading .loading-description {
                font-size: 14px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            #loginLoading .loading-spinner {
                animation-duration: 2s;
            }
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-6 bg-cover bg-center bg-no-repeat"
      style="background-image: url('../img/image.png');">

    <div id="loginLoading">
        <main class="loading-card"
              role="status"
              aria-live="polite">

            <h1 class="loading-title">
                LifeCycle Track
            </h1>

            <div class="loading-spinner" aria-hidden="true"></div>

            <h2 class="loading-message">
                Signing in...
            </h2>

            <p class="loading-description">
                Please wait while we verify your account.
            </p>
        </main>
    </div>

    <div class="w-full max-w-md backdrop-blur-3xl shadow-2xl px-10 py-9"
         style="border-radius:10px; background-color: rgba(255, 255, 255, 0.85); border: 1.5px solid rgba(255, 255, 255, 0.95); box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.6), 0 20px 25px -5px rgba(0, 0, 0, 0.1);">

       <div class="text-center text-[10px] font-bold tracking-[0.25em] uppercase mb-7 text-[#2862d3]">
            <img src="../img/logo.png" alt="Logo"class="block w-28 mx-auto mb-2">
            LifeCycle Track
        </div>

        <h2 class="text-center text-3xl font-extrabold mb-1"style="color: #0E3A63;">
            Welcome!
        </h2>

        <p class="text-center text-sm text-slate-500 mb-6">
            Sign in to continue to your account.
        </p>

        <?php if ($message): ?>
            <p class="text-red-500 text-sm text-center mb-4">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form method="POST" id="loginForm">

            <label class="block mb-2 text-sm font-bold text-gray-900">
                Email
            </label>

            <div class="relative mb-5">
                <i class="fa-solid fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
                <input
                    type="email"
                    name="email"
                    required
                    autocomplete="email"
                    placeholder="Enter your email"
                    class="w-full h-11 pl-11 pr-4 border border-slate-200 rounded-xl bg-white text-sm text-gray-900 placeholder:text-slate-400 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15">
            </div>

            <label class="block mb-2 text-sm font-bold text-gray-900">
                Password
            </label>

            <div class="relative mb-4">
                <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>

                <input
                    type="password"
                    name="password"
                    id="password"
                    required
                    autocomplete="current-password"
                    placeholder="Enter your password"
                    class="w-full h-11 pl-11 pr-11 border border-slate-200 rounded-xl bg-white text-sm text-gray-900 placeholder:text-slate-400 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15">

                <button
                    type="button"
                    id="togglePassword"
                    aria-label="Show password"
                    class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-700">
                    <i class="fa-regular fa-eye"></i>
                </button>
            </div>

            <div class="flex items-center justify-between text-xs mb-6">

                <label class="flex items-center gap-2 text-slate-500 cursor-pointer">
                    <input type="checkbox"
                           name="remember"
                           class="rounded border-slate-300 text-blue-600">
                    Remember me
                </label>

                <a href="#"
                   class="font-bold text-red-500 hover:underline">
                    Forgot password?
                </a>
            </div>

            <button
                type="submit"
                id="loginButton"
                class="w-full h-12 rounded-xl text-white text-sm font-bold bg-gradient-to-b from-[#2f80d1] to-[#1b6bb5] shadow-lg shadow-blue-700/30 hover:shadow-xl hover:shadow-blue-700/40 active:translate-y-px transition cursor-pointer">

                <i class="fa-solid fa-right-to-bracket mr-2"></i>
                Log In
            </button>
        </form>

        <div class="text-center my-4 text-xs text-slate-400">
            OR
        </div>

        <a href="google.php"
           id="googleLogin"
           class="w-full h-11 flex items-center justify-center gap-2 border border-slate-200 rounded-xl bg-white text-sm text-gray-900 hover:bg-slate-50 transition">
            <i class="fab fa-google"></i>
            Sign in with Google
        </a>

        <p class="text-center text-sm text-slate-500 mt-5">
            Don't have an account?

            <a href="register.php"
               class="font-bold text-[#155B92] hover:underline">
                Create Account
            </a>
        </p>

        <div class="text-center text-[11px] leading-relaxed text-slate-500 mt-6">
            LifeCycle Track Management System<br>
            <strong class="text-gray-900 font-bold">
                Facility and Equipment Lifecycle Management
            </strong>
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

            eye.setAttribute(
                'aria-label',
                show ? 'Hide password' : 'Show password'
            );
        });

        const loginForm = document.getElementById('loginForm');
        const loginLoading = document.getElementById('loginLoading');
        const loginButton = document.getElementById('loginButton');

        loginForm.addEventListener('submit', function (event) {
            if (!loginForm.checkValidity()) {
                return;
            }

            loginLoading.style.display = 'flex';

            loginButton.disabled = true;
            loginButton.innerHTML =
                '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Signing in...';
        });
    </script>
</body>
</html>
