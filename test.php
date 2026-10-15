<?php

echo '<pre>';

echo "PHP version: " . PHP_VERSION . PHP_EOL;

echo "PDO: ";
var_dump(class_exists('PDO'));

echo "PDO PostgreSQL: ";
var_dump(in_array('pgsql', PDO::getAvailableDrivers(), true));

echo "Available PDO drivers:" . PHP_EOL;
print_r(PDO::getAvailableDrivers());

echo '</pre>';

require_once __DIR__ . '/_app.php';
require_login();

try {
    $stmt = $pdo->query("
        SELECT
            current_database() AS database_name
    ");

    $result = $stmt->fetch();

    echo "<h1>Supabase Connected!</h1>";
    echo "<p>Database connection is working.</p>";

} catch (PDOException $e) {
    error_log('LifeCycleTrack database test failed: ' . $e->getMessage());
    http_response_code(500);
    echo "<p>Database connection failed. Check the server logs.</p>";
}