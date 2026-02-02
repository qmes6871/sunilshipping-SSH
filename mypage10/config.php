<?php
/**
 * 마이페이지 설정 파일
 * 데이터베이스 연결 및 기본 설정
 */

// 데이터베이스 설정
$db_host = 'localhost';
$db_user = 'sunilshipping';
$db_pass = 'sunil123!';
$db_name = 'sunilshipping';

// PDO 데이터베이스 연결
try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    // PHP 버전 호환성을 위해 MYSQL_ATTR_INIT_COMMAND 조건부 추가
    if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
        $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci";
    }

    $pdo = new PDO(
        "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
        $db_user,
        $db_pass,
        $options
    );

    // PDO 전역 변수로 설정
    $GLOBALS['pdo'] = $pdo;

} catch (PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("데이터베이스 연결에 실패했습니다. 잠시 후 다시 시도해주세요.");
}

// 세션 설정
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 기본 설정
date_default_timezone_set('Asia/Seoul');

// 업로드 디렉토리 생성 (자동 생성 안됨)
$upload_dirs = [
    __DIR__ . '/uploads/customer_documents/',
    __DIR__ . '/uploads/tracing/',
    __DIR__ . '/uploads/staff/',
    __DIR__ . '/uploads/vessels/',
    __DIR__ . '/uploads/banners/',
    __DIR__ . '/uploads/customers/'
];

foreach ($upload_dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}
?>

