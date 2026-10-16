<?php
session_start();

$envFile = __DIR__ . '/../.env';
$env = is_file($envFile) ? parse_ini_file($envFile, false, INI_SCANNER_RAW) : false;
$supabaseUrl = rtrim((string)($env['SUPABASE_URL'] ?? ''), '/');
$supabaseKey = (string)($env['SUPABASE_KEY'] ?? '');
$message = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($supabaseUrl === '' || $supabaseKey === '') {
        $message = 'Authentication configuration is missing.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $message = 'Please enter a valid email and password.';
    } elseif (!function_exists('curl_init')) {
        $message = 'The authentication service is unavailable. Please try again later.';
    } else {
        $ch = curl_init($supabaseUrl . '/auth/v1/token?grant_type=password');

        if ($ch === false) {
            $message = 'Unable to initialize the authentication service.';
        } else {
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
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            $data = json_decode($response ?: '', true);
            $data = is_array($data) ? $data : [];

            if ($response !== false && $httpCode >= 200 && $httpCode < 300 && !empty($data['access_token'])) {
                session_regenerate_id(true);
                $_SESSION['access_token'] = $data['access_token'];
                $_SESSION['refresh_token'] = $data['refresh_token'] ?? '';
                $_SESSION['user'] = $data['user'] ?? [];

                header('Location: ../main/dashboard.php');
                exit;
            }

            //para sa test connection sa supabase
            if ($response === false) {
                error_log('Supabase login connection error: ' . $curlError);
                $message = 'Unable to connect to the authentication service. Please try again.';
            } else {
                $message = 'Login failed: ' . (
                    $data['msg'] ??
                    $data['error_description'] ??
                    $data['message'] ??
                    'Invalid email or password.'
                );
            }
        }
    }
}

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LifeCycle Track | Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style type="text/tailwindcss">
        @theme {
            --font-ibm: "IBM Plex Sans", sans-serif;
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: .01ms !important;
                transition-duration: .01ms !important;
            }
        }
    </style>
</head>
<body class="flex min-h-screen items-center justify-center bg-[#1d4f80] bg-[url('../img/image.png')] bg-cover bg-center bg-fixed p-5 font-ibm text-[#12263f] max-sm:p-3">
    <div class="grid w-full max-w-[900px] grid-cols-1 overflow-hidden rounded-3xl bg-white/[.94] shadow-[0_40px_80px_-30px_rgba(5,22,50,.65)] lg:grid-cols-[370px_1fr]">
        <aside class="relative flex flex-col items-center justify-center gap-1.5 overflow-hidden bg-[linear-gradient(180deg,rgba(12,45,94,.93),rgba(16,57,122,.9)),url('../img/image.png')] bg-cover bg-center px-6 py-7 text-center text-white lg:px-7">
            <img class="mb-2 block h-24 w-24 rounded-full bg-white object-contain shadow-[0_0_0_5px_rgba(255,255,255,.16),0_12px_26px_rgba(0,0,0,.28)] lg:h-[150px] lg:w-[150px]" src="../img/logo.png" alt="LifeCycle Track Logo">
            <div class="text-[11px] font-bold uppercase tracking-[.25em] text-blue-200">BARANGAY MANAGEMENT SYSTEM</div>
            <h1 class="mt-3.5 text-[26px] font-bold leading-tight tracking-tight text-white lg:text-3xl">Welcome Back!</h1>
            <p class="mt-1 text-sm leading-relaxed text-white/80">Sign in to manage your facilities and equipment lifecycle.</p>
        </aside>

        <main class="relative px-5 py-6 sm:px-8 lg:px-10 lg:py-[34px]">
            <div class="mb-3.5 text-right lg:absolute lg:right-6 lg:top-[22px] lg:mb-0">
                <span class="inline-block rounded-full bg-white px-[18px] py-2.5 text-xs font-bold text-[#0c2d5e] shadow-[0_6px_18px_rgba(12,45,94,.16)]">Barangay Gulod</span>
            </div>

            <h2 class="text-3xl font-bold leading-tight tracking-tight text-[#0c2d5e]">Sign In</h2>
            <p class="mb-[18px] mt-2 max-w-[46ch] text-sm leading-relaxed text-[#5b6b7c]">Enter your email and password to access your account.</p>

            <?php if ($message !== ''): ?>
                <div class="mb-4 flex items-start gap-2.5 rounded-[10px] border-l-4 border-[#b42318] bg-[#fdecea] px-3.5 py-3 text-[13.5px] leading-relaxed text-[#b42318]" role="alert">
                    <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                    <span><?= e($message) ?></span>
                </div>
            <?php endif; ?>

            <section class="mx-auto w-full max-w-md rounded-[18px] border border-[#d5dfea] bg-white p-5 shadow-[0_10px_30px_-18px_rgba(12,45,94,.35)]">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-[#0c2d5e]">Account Details</h2>
                        <p class="mt-0.5 text-[12.5px] text-[#5b6b7c]">Enter your registered login credentials.</p>
                    </div>
                </div>

                <form method="POST" action="" autocomplete="on" id="loginForm">
                    <div class="mb-3.5 grid grid-cols-1 gap-x-4 gap-y-3.5">
                        <div>
                            <label for="email" class="mb-1.5 block text-[13px] font-bold">Email</label>
                            <div class="group relative">
                                <i class="fa-solid fa-envelope pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-[13px] text-[#7a8b9c] group-focus-within:text-[#2f80d1]"></i>
                                <input class="h-11 w-full rounded-[10px] border-[1.5px] border-[#d5dfea] bg-[#f7fafc] py-0 pl-10 pr-3 text-sm outline-none transition hover:border-[#b9c8d8] focus:border-[#2f80d1] focus:bg-white focus:ring-4 focus:ring-[#2f80d1]/15" type="email" id="email" name="email" value="<?= e($email) ?>" required maxlength="254" autocomplete="email" placeholder="Enter your email">
                            </div>
                        </div>

                        <div>
                            <label for="password" class="mb-1.5 block text-[13px] font-bold">Password</label>
                            <div class="group relative">
                                <i class="fa-solid fa-lock pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-[13px] text-[#7a8b9c] group-focus-within:text-[#2f80d1]"></i>
                                <input class="h-11 w-full rounded-[10px] border-[1.5px] border-[#d5dfea] bg-[#f7fafc] py-0 pl-10 pr-11 text-sm outline-none transition hover:border-[#b9c8d8] focus:border-[#2f80d1] focus:bg-white focus:ring-4 focus:ring-[#2f80d1]/15" type="password" id="password" name="password" required autocomplete="current-password" placeholder="Enter your password">
                                <button type="button" id="togglePassword" class="absolute right-1.5 top-1/2 grid h-[34px] w-[34px] -translate-y-1/2 place-items-center rounded-lg text-[#6b7c8d] hover:bg-slate-100 hover:text-[#0c2d5e] focus-visible:outline-2 focus-visible:outline-[#2f80d1]" aria-label="Show password" aria-pressed="false"><i class="fa-regular fa-eye"></i></button>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 text-[13px]">
                        <label class="flex cursor-pointer items-center gap-2 text-[#5b6b7c]">
                            <input type="checkbox" name="remember" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            Remember me
                        </label>
                        <a href="#" class="font-bold text-[#1d4fb8] hover:underline">Forgot password?</a>
                    </div>

                    <button type="submit" class="cursor-pointer mx-auto flex h-12 w-3/5 items-center justify-center gap-2.5 rounded-xl bg-gradient-to-b from-[#2f80d1] to-[#1b6bb5] text-sm font-bold text-white shadow-[0_10px_20px_-8px_rgba(27,107,181,.7)] transition hover:brightness-105 hover:shadow-[0_14px_24px_-8px_rgba(27,107,181,.75)] active:translate-y-px disabled:cursor-not-allowed disabled:opacity-50 disabled:shadow-none focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-[#2f80d1]/50" id="loginButton"><i class="fa-solid fa-right-to-bracket"></i> Log In</button>
                </form>

                <div class="my-4 flex items-center gap-3">
                    <span class="h-px flex-1 bg-[#d5dfea]"></span>
                    <span class="text-xs text-slate-400">OR</span>
                    <span class="h-px flex-1 bg-[#d5dfea]"></span>
                </div>

                <a href="google.php" id="googleLogin" class="flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-[#d5dfea] bg-white text-sm font-semibold text-[#12263f] transition hover:bg-slate-50">
                    <i class="fab fa-google"></i>
                    Sign in with Google
                </a>

                <p class="mt-3.5 text-center text-[13.5px] text-[#5b6b7c]">Don't have an account? <a href="register.php" class="font-bold text-[#1d4fb8] hover:underline">Create Account</a></p>
            </section>
        </main>
    </div>

    <div id="loginLoading" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-[#0c2d5e]/65 p-5 backdrop-blur-md">
        <main role="status" aria-live="polite" class="w-full max-w-sm rounded-2xl border-l-[5px] border-[#10375f] bg-white p-8 text-center shadow-lg">
            <div class="mb-6 flex items-center justify-center gap-3">
                <img src="../img/logo.png" alt="LifeCycle Track" class="h-11 w-11 shrink-0 object-contain">
                <span class="text-xl font-extrabold text-[#10375f]">LifeCycle Track</span>
            </div>

            <div class="mx-auto mb-5 h-10 w-10 animate-spin rounded-full border-4 border-gray-200 border-t-[#17598f] motion-reduce:animate-none" aria-hidden="true"></div>

            <h2 class="mb-1 text-xl font-extrabold text-gray-900">Signing in...</h2>
            <p class="text-sm text-gray-600">Please wait while we verify your account.</p>
        </main>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const togglePassword = document.getElementById('togglePassword');

        togglePassword.addEventListener('click', () => {
            const show = passwordInput.type === 'password';
            passwordInput.type = show ? 'text' : 'password';
            togglePassword.innerHTML = show
                ? '<i class="fa-regular fa-eye-slash"></i>'
                : '<i class="fa-regular fa-eye"></i>';
            togglePassword.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            togglePassword.setAttribute('aria-pressed', String(show));
        });

        const loginForm = document.getElementById('loginForm');
        const loginLoading = document.getElementById('loginLoading');
        const loginButton = document.getElementById('loginButton');

        loginForm.addEventListener('submit', event => {
            if (!loginForm.checkValidity()) {
                event.preventDefault();
                loginForm.reportValidity();
                return;
            }

            loginLoading.classList.remove('hidden');
            loginLoading.classList.add('flex');
            loginButton.disabled = true;
            loginButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Signing in...';
        });
    </script>
</body>
</html>
