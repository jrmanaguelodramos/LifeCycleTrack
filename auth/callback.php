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
</head>

<body>

<p>Signing in with Google...</p>

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