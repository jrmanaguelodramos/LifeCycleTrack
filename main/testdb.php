<?php

require_once __DIR__ . '/../config/database.php';

try {
    $stmt = $pdo->query("
        SELECT
            current_database() AS database_name,
            current_user AS database_user,
            version() AS postgres_version
    ");

    $result = $stmt->fetch();

    echo "<h1>Supabase Connected!</h1>";
    echo "<p>Database: " . htmlspecialchars($result['database_name']) . "</p>";
    echo "<p>User: " . htmlspecialchars($result['database_user']) . "</p>";
    echo "<p>PostgreSQL connection is working.</p>";

} catch (PDOException $e) {
    echo "<h1>Database Error</h1>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}