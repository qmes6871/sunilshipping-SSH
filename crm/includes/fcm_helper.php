<?php
/**
 * Firebase Cloud Messaging 헬퍼
 * FCM HTTP v1 API 사용
 */

// FCM 서비스 계정 키 파일 경로
define('FCM_SERVICE_ACCOUNT_PATH', CRM_PATH . '/config/firebase-service-account.json');

/**
 * Firebase 액세스 토큰 가져오기
 */
function getFirebaseAccessToken() {
    if (!file_exists(FCM_SERVICE_ACCOUNT_PATH)) {
        error_log('Firebase service account file not found: ' . FCM_SERVICE_ACCOUNT_PATH);
        return null;
    }

    $serviceAccount = json_decode(file_get_contents(FCM_SERVICE_ACCOUNT_PATH), true);

    if (!$serviceAccount) {
        error_log('Invalid Firebase service account JSON');
        return null;
    }

    // JWT 생성
    $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));

    $now = time();
    $payload = base64_encode(json_encode([
        'iss' => $serviceAccount['client_email'],
        'sub' => $serviceAccount['client_email'],
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $now,
        'exp' => $now + 3600,
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging'
    ]));

    // 서명
    $signature = '';
    $privateKey = openssl_pkey_get_private($serviceAccount['private_key']);
    openssl_sign("$header.$payload", $signature, $privateKey, 'SHA256');
    $signature = base64_encode($signature);

    $jwt = "$header.$payload.$signature";

    // 액세스 토큰 요청
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);

    if (isset($result['access_token'])) {
        return $result['access_token'];
    }

    error_log('Failed to get Firebase access token: ' . $response);
    return null;
}

/**
 * FCM 토픽으로 푸시 알림 발송
 * @param string $topic 토픽 이름 (예: 'all_users')
 * @param string $title 알림 제목
 * @param string $body 알림 내용
 * @param array $data 추가 데이터
 */
function sendFCMToTopic($topic, $title, $body, $data = []) {
    if (!file_exists(FCM_SERVICE_ACCOUNT_PATH)) {
        return ['success' => false, 'message' => 'Firebase 서비스 계정이 설정되지 않았습니다.'];
    }

    $serviceAccount = json_decode(file_get_contents(FCM_SERVICE_ACCOUNT_PATH), true);
    $projectId = $serviceAccount['project_id'] ?? null;

    if (!$projectId) {
        return ['success' => false, 'message' => 'Firebase 프로젝트 ID를 찾을 수 없습니다.'];
    }

    $accessToken = getFirebaseAccessToken();
    if (!$accessToken) {
        return ['success' => false, 'message' => 'Firebase 인증에 실패했습니다.'];
    }

    $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

    $message = [
        'message' => [
            'topic' => $topic,
            'notification' => [
                'title' => $title,
                'body' => $body
            ],
            'data' => array_merge($data, [
                'title' => $title,
                'body' => $body,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
            ]),
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'channel_id' => 'crm_notifications',
                    'sound' => 'default'
                ]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);

    if ($httpCode === 200) {
        writeLog("FCM sent to topic '{$topic}': {$title}", 'fcm');
        return ['success' => true, 'message' => '알림이 발송되었습니다.', 'response' => $result];
    } else {
        error_log("FCM send failed: " . $response);
        return ['success' => false, 'message' => 'FCM 발송에 실패했습니다.', 'error' => $result];
    }
}

/**
 * 전체 사용자에게 FCM 푸시 발송
 */
function sendFCMToAll($title, $body, $data = []) {
    return sendFCMToTopic('all_users', $title, $body, $data);
}

/**
 * 국제물류 활동 알림을 FCM으로 발송
 */
function sendIntlActivityFCM($activityType, $customerName, $activityDate) {
    // 알림 대상 활동 유형 확인
    $notifyTypes = ['progress', 'booking_completed', 'settlement_completed'];
    if (!in_array($activityType, $notifyTypes)) {
        return ['success' => false, 'message' => '알림 대상 활동 유형이 아닙니다.'];
    }

    // 활동 유형 한글 변환
    $typeLabels = [
        'progress' => '진행',
        'booking_completed' => '부킹완료',
        'settlement_completed' => '정산완료'
    ];
    $typeLabel = $typeLabels[$activityType] ?? $activityType;

    // 알림 메시지 생성
    $title = '국제물류 활동 알림';
    $body = "{$customerName}님 {$activityDate}에 {$typeLabel}되었습니다.";

    // FCM 발송
    $fcmResult = sendFCMToAll($title, $body, [
        'type' => 'intl_activity',
        'activity_type' => $activityType,
        'url' => CRM_URL . '/pages/international/dashboard.php'
    ]);

    // DB에도 저장 (이력 관리용)
    $dbResult = createIntlActivityNotification($activityType, $customerName, $activityDate);

    return [
        'success' => $fcmResult['success'] || $dbResult['success'],
        'fcm' => $fcmResult,
        'db' => $dbResult
    ];
}
