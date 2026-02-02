<?php
/**
 * 고객 후기 등록 처리
 */
session_start();

// 그누보드 common.php 파일 찾기
$possible_paths = [
    '../common.php',
    '../../common.php',
    './common.php',
];

$common_loaded = false;
foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        include_once($path);
        $common_loaded = true;
        break;
    }
}

if (!$common_loaded) {
    die('common.php 파일을 찾을 수 없습니다.');
}

// 로그인 체크
if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
    header('Location: ../login/login.php');
    exit;
}

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: write.php');
    exit;
}

$customer_id = $_SESSION['username'];
$customer_name = $_SESSION['name'] ?? $customer_id;

// 입력값 받기
$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');
$rating = (int)($_POST['rating'] ?? 5);
$service_type = $_POST['service_type'] ?? 'shipping';

// 유효성 검사
$errors = [];

if (empty($title)) {
    $errors[] = '제목을 입력해주세요.';
}

if (mb_strlen($title) > 200) {
    $errors[] = '제목은 200자를 초과할 수 없습니다.';
}

if (empty($content)) {
    $errors[] = '내용을 입력해주세요.';
}

if (mb_strlen($content) < 10) {
    $errors[] = '내용은 10자 이상 입력해주세요.';
}

if ($rating < 1 || $rating > 5) {
    $errors[] = '평점은 1~5 사이의 값이어야 합니다.';
}

$allowed_service_types = ['shipping', 'customs', 'warehouse', 'consulting', 'other'];
if (!in_array($service_type, $allowed_service_types)) {
    $errors[] = '올바른 서비스 유형을 선택해주세요.';
}

// 에러가 있으면 돌아가기
if (!empty($errors)) {
    $_SESSION['review_error'] = implode('<br>', $errors);
    header('Location: write.php');
    exit;
}

// SQL Injection 방지
$title = addslashes($title);
$content = addslashes($content);
$customer_id_escaped = addslashes($customer_id);
$customer_name_escaped = addslashes($customer_name);
$service_type_escaped = addslashes($service_type);

// 데이터베이스에 저장
$sql = "INSERT INTO reviews (
            customer_id,
            customer_name,
            title,
            content,
            rating,
            service_type,
            status,
            created_at
        ) VALUES (
            '{$customer_id_escaped}',
            '{$customer_name_escaped}',
            '{$title}',
            '{$content}',
            {$rating},
            '{$service_type_escaped}',
            'approved',
            NOW()
        )";

$result = sql_query($sql);

if ($result) {
    // 등록된 리뷰 ID 가져오기
    $review_id = sql_insert_id();

    $_SESSION['review_message'] = '후기가 성공적으로 등록되었습니다.';
    header('Location: view.php?id=' . $review_id);
} else {
    $_SESSION['review_error'] = '후기 등록 중 오류가 발생했습니다. 다시 시도해주세요.';
    header('Location: write.php');
}
exit;
