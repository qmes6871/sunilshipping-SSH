<?php
/**
 * 인증 체크 (모든 페이지에서 include)
 */

require_once dirname(__DIR__) . '/config.php';

// FCM 헬퍼 로드
if (file_exists(dirname(__FILE__) . '/fcm_helper.php')) {
    require_once dirname(__FILE__) . '/fcm_helper.php';
}

// 그누보드 로그인 체크 (그누보드 세션 변수 사용)
if (!isset($member['mb_id']) || !$member['mb_id']) {
    // 그누보드 세션이 없으면 로그인 페이지로
    $returnUrl = urlencode($_SERVER['REQUEST_URI']);
    header('Location: ' . G5_URL . '/bbs/login.php?url=' . $returnUrl);
    exit;
}

// 현재 사용자 정보
$currentUser = getCurrentUser();

// CSRF 토큰 생성
$csrfToken = generateCSRFToken();
