<?php
// 데이터베이스 설정
define('DB_HOST', 'localhost');
define('DB_USER', 'sunilshipping');
define('DB_PASS', 'sunil123!');
define('DB_NAME', 'sunilshipping');

// 데이터베이스 연결 함수
function getDbConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");

    return $conn;
}
?>
