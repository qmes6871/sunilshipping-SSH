<?php
$host = 'localhost';
$user = 'sunilshipping';
$pass = 'sunil123!';
$dbname = 'sunilshipping';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(Exception $e) {
    die('DB 연결 실패: ' . $e->getMessage());
}
?>