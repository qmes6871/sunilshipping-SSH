<?php
// Database connection settings
$db_host = 'localhost';
$db_user = 'sunilshipping';
$db_pass = 'sunil123!';
$db_name = 'sunilshipping';

// Connect to database
try {
    $conn = new PDO(
        "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    $conn = null;
}
?>
