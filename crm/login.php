<?php
/**
 * 선일쉬핑 CRM 로그인 페이지
 * gnuboard5 로그인으로 리다이렉트
 */

require_once __DIR__ . '/config.php';

// 이미 로그인된 경우 메인페이지로
if (isLoggedIn()) {
    header('Location: ' . CRM_URL . '/pages/main.php');
    exit;
}

// 로그인 페이지로 리다이렉트
$returnUrl = isset($_GET['url']) ? $_GET['url'] : CRM_URL . '/';
header('Location: ' . G5_URL . '/bbs/login.php?url=' . urlencode($returnUrl));
exit;
