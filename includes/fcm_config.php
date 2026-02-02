<?php
/**
 * FCM (Firebase Cloud Messaging) 설정 파일
 * FCM HTTP v1 API 사용 (OAuth2 인증)
 */

// ===== FCM HTTP v1 API 설정 (최신 - 권장) =====
// Firebase 프로젝트 ID (Firebase Console > Project Settings)
define('FCM_PROJECT_ID', 'your-firebase-project-id');

// Firebase 서비스 계정 JSON 파일 경로
// Firebase Console > Project Settings > Service Accounts > Generate New Private Key
define('FCM_SERVICE_ACCOUNT_JSON', __DIR__ . '/firebase-service-account.json');

// FCM HTTP v1 API URL
define('FCM_API_URL_V1', 'https://fcm.googleapis.com/v1/projects/' . FCM_PROJECT_ID . '/messages:send');

// 액세스 토큰 캐시 파일 경로 (성능 향상용)
define('FCM_TOKEN_CACHE_FILE', __DIR__ . '/../cache/fcm_access_token.cache');

// ===== Legacy API 설정 (기존 호환성 유지) =====
// FCM Server Key (Firebase Console > Project Settings > Cloud Messaging)
define('FCM_SERVER_KEY', 'AAAABGevLoF:APA91bFi43y5XpTZ02QVGEfRW7S_OyVYGqdPodjH4jSVJolD3HESWz1iyC8eNk5cVjJ7yY3gF0XRwf73ADQ5P1c');

// FCM Legacy API URL
define('FCM_API_URL', 'https://fcm.googleapis.com/fcm/send');

// ===== 공통 설정 =====
// 푸시 알림 기본 설정
define('FCM_DEFAULT_TOPIC', 'all_users'); // 전체 사용자 토픽
define('FCM_DEFAULT_PRIORITY', 'high'); // high, normal
define('FCM_DEFAULT_SOUND', 'default');

// 알림 로그 저장 여부
define('FCM_ENABLE_LOGGING', true);

// 디버그 모드
define('FCM_DEBUG_MODE', true); // 운영 환경에서는 false로 설정

// 기본 사용 API (v1 또는 legacy)
define('FCM_USE_V1_API', true); // true: v1 API 사용, false: Legacy API 사용

?>
