<?php

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accessToken = $_POST['access_token'] ?? '';
    $refreshToken = $_POST['refresh_token'] ?? '';
    $userJson = $_POST['user'] ?? '';

    if (empty($accessToken)) {
        die("Google login failed: No access token.");
    }

    $_SESSION['access_token'] = $accessToken;
    $_SESSION['refresh_token'] = $refreshToken;

    if (!empty($userJson)) {
        $_SESSION['user'] = json_decode($userJson, true);
    }

    header("Location: ../main/dashboard.php");
    exit;
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Signing in...</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="flex min-h-screen items-center justify-center bg-[#1d4f80] bg-[url('../img/image.png')] bg-cover bg-center bg-fixed p-5 font-['Nunito','Segoe_UI',Roboto,Arial,sans-serif] max-sm:p-3">
    <main role="status" aria-live="polite" class="w-full max-w-sm rounded-2xl border-l-[5px] border-[#10375f] bg-white p-8 text-center shadow-[0_40px_80px_-30px_rgba(5,22,50,.65)]">
        <div class="mb-6 flex items-center justify-center gap-3">
            <img src="../img/logo.png" alt="LifeCycle Track" class="h-11 w-11 shrink-0 object-contain">
            <span class="text-xl font-extrabold text-[#10375f]">LifeCycle Track</span>
        </div>

        <div class="mx-auto mb-5 h-10 w-10 animate-spin rounded-full border-4 border-gray-200 border-t-[#17598f] motion-reduce:animate-none" aria-hidden="true"></div>

        <h1 class="mb-1 text-xl font-extrabold text-[#0c2d5e]">Signing in with Google...</h1>
        <p class="text-sm text-[#5b6b7c]">Please wait while we verify your account.</p>
    </main>
</div>
<script>

const hash = window.location.hash.substring(1);

if (!hash) {
    document.body.innerHTML = "<h3>Google login failed: No login data received.</h3>";
} else {

    const params = new URLSearchParams(hash);

    const accessToken = params.get('access_token');
    const refreshToken = params.get('refresh_token');

    if (!accessToken) {

        document.body.innerHTML =
            "<h3>Google login failed: No access token received.</h3>";

    } else {

        fetch("https://ukgusfmwmynuwdsiuzir.supabase.co/auth/v1/user", {
            method: "GET",
            headers: {
                "apikey": "sb_publishable_1PMD6FuARpl31ATA_acu9w_VHIpYMUk",
                "Authorization": "Bearer " + accessToken
            }
        })
        .then(response => response.json())
        .then(user => {
            const form = document.createElement("form");

            form.method = "POST";
            form.action = window.location.pathname;

            const accessInput = document.createElement("input");
            accessInput.type = "hidden";
            accessInput.name = "access_token";
            accessInput.value = accessToken;

            const refreshInput = document.createElement("input");
            refreshInput.type = "hidden";
            refreshInput.name = "refresh_token";
            refreshInput.value = refreshToken || "";

            const userInput = document.createElement("input");
            userInput.type = "hidden";
            userInput.name = "user";
            userInput.value = JSON.stringify(user);
            form.appendChild(accessInput);
            form.appendChild(refreshInput);
            form.appendChild(userInput);
            document.body.appendChild(form);
            form.submit();
        })

        .catch(error => {
            console.error(error);
            document.body.innerHTML =
                "<h3>Failed to retrieve Google user information.</h3>";
        });
    }
}

</script>

</body>
</html>