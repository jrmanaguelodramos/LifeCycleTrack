<?php
declare(strict_types=1);

session_start();

if (!empty($_SESSION['access_token'])) {
    header('Location: main/dashboard.php');
    exit;
} else {
    header('Location: auth/login.php');
    exit;
}
?>