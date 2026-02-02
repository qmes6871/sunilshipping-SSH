<?php
session_start();
require_once 'config.php';
require_once 'customer_document_upload_module.php';

// 로그인 체크 - username을 customer_id로 사용
if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
    header('Location: ../login/login.php');
    exit;
}

$customer_id = $_SESSION['username'];

// GET 파라미터 확인
if (!isset($_GET['doc_id'])) {
    header('Location: index.php');
    exit;
}

$doc_id = (int)$_GET['doc_id'];

// 서류 삭제 처리
$result = deleteCustomerDocument($customer_id, $doc_id);

if ($result['success']) {
    $_SESSION['upload_message'] = $result['message'];
    $_SESSION['upload_status'] = 'success';
} else {
    $_SESSION['upload_message'] = $result['message'];
    $_SESSION['upload_status'] = 'error';
}

// 서류 목록 페이지로 리다이렉트
header('Location: index.php');
exit;
?>

