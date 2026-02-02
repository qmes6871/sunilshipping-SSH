<?php
/**
 * FCM (Firebase Cloud Messaging) 설정 파일
 *
 * 사용 방법:
 * 1. 이 파일을 fcm_config.php로 복사
 * 2. Firebase Console에서 Server Key 가져오기:
 *    - Firebase Console (https://console.firebase.google.com/) 접속
 *    - 프로젝트 선택
 *    - 설정(톱니바퀴) > 프로젝트 설정
 *    - 'Cloud Messaging' 탭
 *    - '서버 키' 복사
 * 3. 아래 FCM_SERVER_KEY에 붙여넣기
 */

// FCM Server Key (Firebase Console > Project Settings > Cloud Messaging)
define('FCM_SERVER_KEY', 'YOUR_FCM_SERVER_KEY_HERE');

// FCM API URL (기본값 - 변경 불필요)
define('FCM_API_URL', 'https://fcm.googleapis.com/fcm/send');

// 푸시 알림 기본 설정
define('FCM_DEFAULT_TOPIC', 'all_users'); // 전체 사용자 토픽

// 알림 로그 저장 여부
define('FCM_ENABLE_LOGGING', true);

?>
