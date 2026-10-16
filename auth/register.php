<?php
session_start();

$envFile = __DIR__ . '/../.env';
$env = is_file($envFile) ? parse_ini_file($envFile, false, INI_SCANNER_RAW) : false;
$supabaseUrl = rtrim((string)($env['SUPABASE_URL'] ?? ''), '/');
$supabaseKey = (string)($env['SUPABASE_KEY'] ?? '');
$message = $success = '';
$name = $email = '';

function passwordError(string $password): string
{
    if (strlen($password) < 8) return 'Password must be at least 8 characters.';
    if (!preg_match('/[A-Z]/', $password)) return 'Password must include an uppercase letter.';
    if (!preg_match('/[a-z]/', $password)) return 'Password must include a lowercase letter.';
    if (!preg_match('/[0-9]/', $password)) return 'Password must include a number.';
    if (!preg_match('/[^A-Za-z0-9]/', $password)) return 'Password must include a special character (e.g. ! @ # $ %).';
    return '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if ($name === '') $message = 'Please enter your full name.';
    elseif (strlen($name) > 150) $message = 'Full name must not exceed 150 characters.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) $message = 'Please enter a valid email address.';
    elseif (($pwError = passwordError($password)) !== '') $message = $pwError;
    elseif ($password !== $confirmPassword) $message = 'Passwords do not match.';
    elseif ($supabaseUrl === '' || $supabaseKey === '') $message = 'Supabase configuration is missing.';
    elseif (!function_exists('curl_init')) $message = 'The PHP cURL extension is not enabled.';
    else {
        $ch = curl_init($supabaseUrl . '/auth/v1/signup');

        if ($ch === false) {
            $message = 'Unable to initialize the Supabase connection.';
        } else {
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_HTTPHEADER => [
                    'apikey: ' . $supabaseKey,
                    'Authorization: Bearer ' . $supabaseKey,
                    'Content-Type: application/json'
                ],
                CURLOPT_POSTFIELDS => json_encode([
                    'email' => $email,
                    'password' => $password,
                    'data' => ['name' => $name]
                ])
            ]);

            $response = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response === false) {
                error_log('Supabase signup connection error: ' . $curlError);
                $message = 'Unable to connect to Supabase. Please try again later.';
            } else {
                $data = json_decode($response, true);
                $data = is_array($data) ? $data : [];

                if ($httpCode >= 200 && $httpCode < 300) {
                    $success = 'Your account has been created successfully. Please log in using your registered email and password.';
                } else {
                    $errorMessage = $data['msg'] ?? $data['message'] ?? $data['error_description'] ?? $data['error'] ?? 'Unable to create your account.';
                    $message = 'Registration failed: ' . $errorMessage;
                }
            }
        }
    }
}

function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - LifeCycle Track</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style type="text/tailwindcss">
        @theme {
            --font-ibm: "IBM Plex Sans", sans-serif;
        }
    </style>
</head>
<body class="flex min-h-screen items-center justify-center bg-[#1d4f80] bg-[url('../img/image.png')] bg-cover bg-center bg-fixed p-5 font-ibm text-[#12263f] max-sm:p-3">
    <div class="grid w-full max-w-[1120px] grid-cols-1 overflow-hidden rounded-3xl bg-white/[.94] shadow-[0_40px_80px_-30px_rgba(5,22,50,.65)] lg:grid-cols-[370px_1fr]">
        <aside class="relative flex flex-col items-center justify-center gap-1.5 overflow-hidden bg-[linear-gradient(180deg,rgba(12,45,94,.93),rgba(16,57,122,.9)),url('../img/image.png')] bg-cover bg-center px-6 py-7 text-center text-white lg:px-7">
            <img class="mb-2 block h-24 w-24 rounded-full bg-white object-contain shadow-[0_0_0_5px_rgba(255,255,255,.16),0_12px_26px_rgba(0,0,0,.28)] lg:h-[150px] lg:w-[150px]" src="../img/logo.png" alt="Barangay Gulod logo">
            <div class="text-[11px] font-bold uppercase tracking-[.25em] text-blue-200">Barangay Management System</div>
            <h1 class="mt-3.5 text-[26px] font-bold leading-tight tracking-tight text-white lg:text-3xl">Create Aaccount</h1>
            <p class="mt-1 text-sm leading-relaxed text-white/80">Fill in your details to get started.</p>
        </aside>

        <main class="relative px-5 py-6 sm:px-8 lg:px-10 lg:py-[34px]">
            <div class="mb-3.5 text-right lg:absolute lg:right-6 lg:top-[22px] lg:mb-0">
                <a href="#" class="inline-block rounded-full bg-white px-[18px] py-2.5 text-xs font-bold text-[#0c2d5e] shadow-[0_6px_18px_rgba(12,45,94,.16)] hover:shadow-[0_8px_22px_rgba(12,45,94,.26)]">Barangay Gulod</a>
            </div>

            <h2 class="text-3xl font-bold leading-tight tracking-tight text-[#0c2d5e]">Create An Account</h2>
            <p class="mb-[18px] mt-2 max-w-[46ch] text-sm leading-relaxed text-[#5b6b7c]">Fill in your details below.</p>

            <?php if ($message !== ''): ?>
                <div class="mb-4 flex items-start gap-2.5 rounded-[10px] border-l-4 border-[#b42318] bg-[#fdecea] px-3.5 py-3 text-[13.5px] leading-relaxed text-[#b42318]" role="alert">
                    <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                    <span><?= e($message) ?></span>
                </div>
            <?php endif; ?>

            <section class="rounded-[18px] border border-[#d5dfea] bg-white p-5 shadow-[0_10px_30px_-18px_rgba(12,45,94,.35)]">
                <div class="mb-4">
                    <h2 class="text-base font-bold text-[#0c2d5e]">Account Details</h2>
                    <p class="mt-0.5 text-[12.5px] text-[#5b6b7c]">Please input your name and login details.</p>
                </div>

                <form method="POST" action="" autocomplete="on" id="registerForm">
                    <div class="mb-3.5 grid grid-cols-1 gap-x-4 gap-y-3.5 sm:grid-cols-2">
                        <div>
                            <label for="name" class="mb-1.5 block text-[13px] font-bold">Full name</label>
                            <div class="group relative">
                                <i class="fa-solid fa-user pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-[13px] text-[#7a8b9c] group-focus-within:text-[#2f80d1]"></i>
                                <input class="h-11 w-full rounded-[10px] border-[1.5px] border-[#d5dfea] bg-[#f7fafc] py-0 pl-10 pr-3 text-sm outline-none transition hover:border-[#b9c8d8] focus:border-[#2f80d1] focus:bg-white focus:ring-4 focus:ring-[#2f80d1]/15" type="text" id="name" name="name" value="<?= e($name) ?>" required maxlength="150" autocomplete="name" placeholder="Enter your full name" <?= $success !== '' ? 'readonly' : '' ?>>
                            </div>
                        </div>

                        <div>
                            <label for="email" class="mb-1.5 block text-[13px] font-bold">Email</label>
                            <div class="group relative">
                                <i class="fa-solid fa-envelope pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-[13px] text-[#7a8b9c] group-focus-within:text-[#2f80d1]"></i>
                                <input class="h-11 w-full rounded-[10px] border-[1.5px] border-[#d5dfea] bg-[#f7fafc] py-0 pl-10 pr-3 text-sm outline-none transition hover:border-[#b9c8d8] focus:border-[#2f80d1] focus:bg-white focus:ring-4 focus:ring-[#2f80d1]/15" type="email" id="email" name="email" value="<?= e($email) ?>" required maxlength="254" autocomplete="email" placeholder="Enter your email" <?= $success !== '' ? 'readonly' : '' ?>>
                            </div>
                        </div>

                        <div>
                            <label for="password" class="mb-1.5 block text-[13px] font-bold">Password</label>
                            <div class="group relative">
                                <i class="fa-solid fa-lock pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-[13px] text-[#7a8b9c] group-focus-within:text-[#2f80d1]"></i>
                                <input class="h-11 w-full rounded-[10px] border-[1.5px] border-[#d5dfea] bg-[#f7fafc] py-0 pl-10 pr-11 text-sm outline-none transition hover:border-[#b9c8d8] focus:border-[#2f80d1] focus:bg-white focus:ring-4 focus:ring-[#2f80d1]/15" type="password" id="password" name="password" required minlength="8" autocomplete="new-password" placeholder="Create a password" aria-describedby="password-guide" <?= $success !== '' ? 'disabled' : '' ?>>
                                <button type="button" class="absolute right-1.5 top-1/2 grid h-[34px] w-[34px] -translate-y-1/2 place-items-center rounded-lg text-[#6b7c8d] hover:bg-slate-100 hover:text-[#0c2d5e] focus-visible:outline-2 focus-visible:outline-[#2f80d1]" data-toggle="password" aria-label="Show password" aria-pressed="false" <?= $success !== '' ? 'disabled' : '' ?>><i class="fa-regular fa-eye"></i></button>
                            </div>
                        </div>

                        <div>
                            <label for="confirm_password" class="mb-1.5 block text-[13px] font-bold">Confirm password</label>
                            <div class="group relative">
                                <i class="fa-solid fa-lock pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-[13px] text-[#7a8b9c] group-focus-within:text-[#2f80d1]"></i>
                                <input class="h-11 w-full rounded-[10px] border-[1.5px] border-[#d5dfea] bg-[#f7fafc] py-0 pl-10 pr-11 text-sm outline-none transition hover:border-[#b9c8d8] focus:border-[#2f80d1] focus:bg-white focus:ring-4 focus:ring-[#2f80d1]/15" type="password" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password" placeholder="Re-enter your password" <?= $success !== '' ? 'disabled' : '' ?>>
                                <button type="button" class="absolute right-1.5 top-1/2 grid h-[34px] w-[34px] -translate-y-1/2 place-items-center rounded-lg text-[#6b7c8d] hover:bg-slate-100 hover:text-[#0c2d5e] focus-visible:outline-2 focus-visible:outline-[#2f80d1]" data-toggle="confirm_password" aria-label="Show password" aria-pressed="false" <?= $success !== '' ? 'disabled' : '' ?>><i class="fa-regular fa-eye"></i></button>
                            </div>
                            <div class="mt-1.5 min-h-4 px-0.5 text-xs font-semibold" id="matchHint" aria-live="polite"></div>
                        </div>
                    </div>

                    <div class="mb-4 rounded-xl border-[1.5px] border-[#d5dfea] bg-[#f7fafc] p-3 sm:px-3.5" id="password-guide">
                        <div class="mb-2.5 flex items-center gap-3">
                            <div class="grid flex-1 grid-cols-5 gap-1" id="meter" data-level="0">
                                <span class="h-[5px] rounded-sm bg-[#dbe4ee]"></span><span class="h-[5px] rounded-sm bg-[#dbe4ee]"></span><span class="h-[5px] rounded-sm bg-[#dbe4ee]"></span><span class="h-[5px] rounded-sm bg-[#dbe4ee]"></span><span class="h-[5px] rounded-sm bg-[#dbe4ee]"></span>
                            </div>
                            <div class="min-w-[74px] text-right text-xs font-bold text-[#5b6b7c]" id="strengthLabel" aria-live="polite">Not set</div>
                        </div>
                        <ul class="grid list-none grid-cols-1 gap-x-3.5 gap-y-1.5 p-0 text-[12.5px] text-[#5b6b7c] sm:grid-cols-2">
                            <li class="flex items-center gap-2" data-rule="length"><i class="fa-regular fa-circle w-4 text-center text-[#a7b5c4]"></i>At least 8 characters</li>
                            <li class="flex items-center gap-2" data-rule="upper"><i class="fa-regular fa-circle w-4 text-center text-[#a7b5c4]"></i>One uppercase letter (A-Z)</li>
                            <li class="flex items-center gap-2" data-rule="lower"><i class="fa-regular fa-circle w-4 text-center text-[#a7b5c4]"></i>One lowercase letter (a-z)</li>
                            <li class="flex items-center gap-2" data-rule="number"><i class="fa-regular fa-circle w-4 text-center text-[#a7b5c4]"></i>One number (0-9)</li>
                            <li class="flex items-center gap-2 sm:col-span-2" data-rule="special"><i class="fa-regular fa-circle w-4 text-center text-[#a7b5c4]"></i>One special character (! @ # $ %)</li>
                        </ul>
                    </div>

                    <button type="submit" class="cursor-pointer mx-auto flex h-12 w-3/5 items-center justify-center gap-2.5 rounded-xl bg-gradient-to-b from-[#2f80d1] to-[#1b6bb5] text-sm font-bold text-white shadow-[0_10px_20px_-8px_rgba(27,107,181,.7)] transition hover:brightness-105 hover:shadow-[0_14px_24px_-8px_rgba(27,107,181,.75)] active:translate-y-px disabled:cursor-not-allowed disabled:opacity-50 disabled:shadow-none focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-[#2f80d1]/50" id="submitBtn" disabled><i class="fa-solid fa-user-plus"></i> Create account</button>
                    <p class="mt-3.5 text-center text-[13.5px] text-[#5b6b7c]">Already have an account? <a href="login.php" class="font-bold text-[#1d4fb8] hover:underline">Login</a></p>
                </form>
            </section>
        </main>
    </div>

    <?php if ($success !== ''): ?>
        <div class="fixed inset-0 z-[9999] flex animate-[fadeIn_.25s_ease] items-center justify-center bg-[#051632]/55 p-5" role="presentation">
            <div class="w-full max-w-[420px] animate-[modalIn_.3s_ease] rounded-[22px] bg-white px-[26px] py-8 text-center shadow-[0_25px_70px_rgba(0,0,0,.25)]" role="dialog" aria-modal="true" aria-labelledby="successTitle">
                <div class="mx-auto mb-[18px] grid h-[76px] w-[76px] place-items-center rounded-full bg-[#e7f6ec] text-[34px] text-[#15803d]"><i class="fa-solid fa-check"></i></div>
                <h2 class="mb-2.5 text-[23px] font-bold text-[#0c2d5e]" id="successTitle">Registration Successful!</h2>
                <p class="text-sm leading-relaxed text-[#5b6b7c]"><?= e($success) ?></p>
                <a href="login.php" class="mt-6 flex w-full items-center justify-center gap-2 rounded-[11px] bg-gradient-to-b from-[#2f80d1] to-[#1b6bb5] px-5 py-3.5 text-sm font-bold text-white transition hover:-translate-y-px hover:brightness-105"><i class="fa-solid fa-right-to-bracket"></i> Go to Login</a>
            </div>
        </div>
    <?php endif; ?>

    <style type="text/tailwindcss">
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes modalIn { from { opacity: 0; transform: translateY(12px) scale(.97); } to { opacity: 1; transform: translateY(0) scale(1); } }
        #meter[data-level="1"] span:nth-child(-n+1), #meter[data-level="2"] span:nth-child(-n+2) { @apply bg-[#b42318]; }
        #meter[data-level="3"] span:nth-child(-n+3), #meter[data-level="4"] span:nth-child(-n+4) { @apply bg-[#e0a100]; }
        #meter[data-level="5"] span:nth-child(-n+5) { @apply bg-[#15803d]; }
        [data-rule].met { @apply font-semibold text-[#15803d]; }
        [data-rule].met i { @apply text-[#15803d]; }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation-duration: .01ms !important; transition-duration: .01ms !important; } }
    </style>

    <script>
        document.querySelectorAll('[data-toggle]').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = document.getElementById(btn.dataset.toggle);
                if (!input) return;
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                btn.setAttribute('aria-pressed', String(show));
                btn.innerHTML = `<i class="fa-regular fa-eye${show ? '-slash' : ''}"></i>`;
            });
        });

        const pw = document.getElementById('password');
        const confirmPw = document.getElementById('confirm_password');
        const meter = document.getElementById('meter');
        const strengthLabel = document.getElementById('strengthLabel');
        const matchHint = document.getElementById('matchHint');
        const submitBtn = document.getElementById('submitBtn');
        const form = document.getElementById('registerForm');
        const rules = {
            length: v => v.length >= 8,
            upper: v => /[A-Z]/.test(v),
            lower: v => /[a-z]/.test(v),
            number: v => /[0-9]/.test(v),
            special: v => /[^A-Za-z0-9]/.test(v)
        };
        const labels = ['Not set', 'Weak', 'Weak', 'Fair', 'Good', 'Strong'];
        const colors = ['#5b6b7c', '#b42318', '#b42318', '#b7791f', '#b7791f', '#15803d'];

        function update() {
            const v = pw.value;
            let score = 0;

            document.querySelectorAll('[data-rule]').forEach(li => {
                const ok = rules[li.dataset.rule](v);
                if (ok) score++;
                li.classList.toggle('met', ok);
                li.querySelector('i').className = ok ? 'fa-solid fa-circle-check w-4 text-center' : 'fa-regular fa-circle w-4 text-center';
            });

            meter.dataset.level = v ? score : 0;
            strengthLabel.textContent = v ? labels[score] : labels[0];
            strengthLabel.style.color = v ? colors[score] : colors[0];

            const c = confirmPw.value;
            matchHint.textContent = !c ? '' : c === v ? 'Passwords match' : 'Passwords do not match yet';
            matchHint.className = 'mt-1.5 min-h-4 px-0.5 text-xs font-semibold ' + (!c ? '' : c === v ? 'text-[#15803d]' : 'text-[#b42318]');

            const valid = score === 5 && v === c && c !== '';
            submitBtn.disabled = !valid;
            submitBtn.title = valid ? '' : 'Meet all password requirements and confirm your password';
        }

        pw.addEventListener('input', update);
        confirmPw.addEventListener('input', update);

        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                form.reportValidity();
                return;
            }

            if (pw.value !== confirmPw.value || Object.values(rules).some(rule => !rule(pw.value))) {
                event.preventDefault();
                update();
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Creating account...';
        });

        update();
    </script>
</body>
</html>
