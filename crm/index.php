<?php
/**
 * 선일쉬핑 CRM 메인 진입점
 */

require_once __DIR__ . '/config.php';

// 로그인 체크
requireLogin();

// 메인페이지로 리다이렉트
header('Location: ' . CRM_URL . '/pages/main.php');
exit;
