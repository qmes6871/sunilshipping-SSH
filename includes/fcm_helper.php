<?php
/**
 * FCM (Firebase Cloud Messaging) 헬퍼 함수
 * FCM HTTP v1 API 및 Legacy API 지원
 */

require_once __DIR__ . '/fcm_config.php';

// 데이터베이스 설정 (notification_helper.php와 동일)
if (!defined('G5_MYSQL_HOST')) define('G5_MYSQL_HOST', 'localhost');
if (!defined('G5_MYSQL_USER')) define('G5_MYSQL_USER', 'sunilshipping');
if (!defined('G5_MYSQL_PASSWORD')) define('G5_MYSQL_PASSWORD', 'sunil123!');
if (!defined('G5_MYSQL_DB')) define('G5_MYSQL_DB', 'sunilshipping');

/**
 * PDO 연결 생성
 */
function getFCMPDO() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . G5_MYSQL_HOST . ";dbname=" . G5_MYSQL_DB . ";charset=utf8mb4",
                G5_MYSQL_USER,
                G5_MYSQL_PASSWORD,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException $e) {
            fcmLog("Database connection error: " . $e->getMessage(), 'error');
            return null;
        }
    }

    return $pdo;
}

/**
 * fcm_tokens 테이블 생성
 */
function createFCMTokensTableIfNotExists() {
    try {
        $pdo = getFCMPDO();
        if (!$pdo) return false;

        $sql = "
        CREATE TABLE IF NOT EXISTS fcm_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL,
            token VARCHAR(255) NOT NULL,
            device_type ENUM('android', 'ios', 'web') DEFAULT 'android',
            device_id VARCHAR(255) NULL,
            app_version VARCHAR(20) NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_used_at TIMESTAMP NULL,
            UNIQUE KEY unique_token (token),
            KEY idx_username (username),
            KEY idx_active (is_active),
            FOREIGN KEY (username) REFERENCES customer_management(username) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        $pdo->exec($sql);
        fcmLog("fcm_tokens table created/verified successfully");
        return true;

    } catch (PDOException $e) {
        fcmLog("Error creating fcm_tokens table: " . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * FCM 토큰 저장/업데이트
 */
function saveFCMToken($username, $token, $deviceType = 'android', $deviceId = null, $appVersion = null) {
    try {
        $pdo = getFCMPDO();
        if (!$pdo) return false;

        // 토큰이 이미 존재하는지 확인
        $stmt = $pdo->prepare("
            SELECT id, username FROM fcm_tokens WHERE token = ?
        ");
        $stmt->execute([$token]);
        $existing = $stmt->fetch();

        if ($existing) {
            // 기존 토큰 업데이트
            $stmt = $pdo->prepare("
                UPDATE fcm_tokens
                SET username = ?, device_type = ?, device_id = ?, app_version = ?,
                    is_active = 1, updated_at = NOW(), last_used_at = NOW()
                WHERE token = ?
            ");
            $stmt->execute([$username, $deviceType, $deviceId, $appVersion, $token]);
            fcmLog("FCM token updated for user: $username");
        } else {
            // 새 토큰 삽입
            $stmt = $pdo->prepare("
                INSERT INTO fcm_tokens (username, token, device_type, device_id, app_version, last_used_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$username, $token, $deviceType, $deviceId, $appVersion]);
            fcmLog("FCM token saved for user: $username");
        }

        return true;

    } catch (PDOException $e) {
        fcmLog("Error saving FCM token: " . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * 사용자의 FCM 토큰 조회
 */
function getUserFCMTokens($username, $activeOnly = true) {
    try {
        $pdo = getFCMPDO();
        if (!$pdo) return [];

        $sql = "SELECT token, device_type, device_id FROM fcm_tokens WHERE username = ?";
        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }
        $sql .= " ORDER BY last_used_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username]);

        return $stmt->fetchAll();

    } catch (PDOException $e) {
        fcmLog("Error getting FCM tokens: " . $e->getMessage(), 'error');
        return [];
    }
}

/**
 * FCM 토큰 비활성화
 */
function deactivateFCMToken($token) {
    try {
        $pdo = getFCMPDO();
        if (!$pdo) return false;

        $stmt = $pdo->prepare("UPDATE fcm_tokens SET is_active = 0 WHERE token = ?");
        $stmt->execute([$token]);

        fcmLog("FCM token deactivated: " . substr($token, 0, 20) . "...");
        return true;

    } catch (PDOException $e) {
        fcmLog("Error deactivating FCM token: " . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * FCM HTTP v1 API용 OAuth2 액세스 토큰 획득
 */
function getFCMAccessToken() {
    try {
        // 캐시된 토큰이 있는지 확인
        if (defined('FCM_TOKEN_CACHE_FILE') && file_exists(FCM_TOKEN_CACHE_FILE)) {
            $cacheData = json_decode(file_get_contents(FCM_TOKEN_CACHE_FILE), true);
            if ($cacheData && isset($cacheData['token']) && isset($cacheData['expires_at'])) {
                // 만료 시간 5분 전까지 유효
                if (time() < ($cacheData['expires_at'] - 300)) {
                    return $cacheData['token'];
                }
            }
        }

        // 서비스 계정 JSON 파일 읽기
        if (!file_exists(FCM_SERVICE_ACCOUNT_JSON)) {
            fcmLog("Service account JSON file not found: " . FCM_SERVICE_ACCOUNT_JSON, 'error');
            return null;
        }

        $serviceAccount = json_decode(file_get_contents(FCM_SERVICE_ACCOUNT_JSON), true);
        if (!$serviceAccount) {
            fcmLog("Invalid service account JSON", 'error');
            return null;
        }

        // JWT 생성
        $now = time();
        $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode([
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600
        ]));

        // 서명 생성
        $signature = '';
        openssl_sign(
            $header . '.' . $payload,
            $signature,
            $serviceAccount['private_key'],
            'SHA256'
        );
        $signature = base64_encode($signature);

        $jwt = $header . '.' . $payload . '.' . $signature;

        // 액세스 토큰 요청
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ])
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            fcmLog("Failed to get access token. HTTP Code: $httpCode, Response: $response", 'error');
            return null;
        }

        $data = json_decode($response, true);
        if (!isset($data['access_token'])) {
            fcmLog("No access token in response", 'error');
            return null;
        }

        // 토큰 캐싱
        if (defined('FCM_TOKEN_CACHE_FILE')) {
            $cacheDir = dirname(FCM_TOKEN_CACHE_FILE);
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }
            file_put_contents(FCM_TOKEN_CACHE_FILE, json_encode([
                'token' => $data['access_token'],
                'expires_at' => $now + $data['expires_in']
            ]));
        }

        return $data['access_token'];

    } catch (Exception $e) {
        fcmLog("Error getting access token: " . $e->getMessage(), 'error');
        return null;
    }
}

/**
 * FCM HTTP v1 API로 푸시 알림 발송
 */
function sendFCMNotificationV1($token, $title, $body, $data = []) {
    try {
        $accessToken = getFCMAccessToken();
        if (!$accessToken) {
            fcmLog("Failed to get access token", 'error');
            return false;
        }

        $message = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body
                ],
                'data' => array_merge([
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'sound' => FCM_DEFAULT_SOUND
                ], $data),
                'android' => [
                    'priority' => FCM_DEFAULT_PRIORITY,
                    'notification' => [
                        'sound' => FCM_DEFAULT_SOUND,
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
                    ]
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => FCM_DEFAULT_SOUND,
                            'badge' => 1
                        ]
                    ]
                ]
            ]
        ];

        $ch = curl_init(FCM_API_URL_V1);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($message)
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            fcmLog("FCM notification sent successfully (v1 API). Token: " . substr($token, 0, 20) . "...");
            return true;
        } else {
            fcmLog("FCM notification failed (v1 API). HTTP Code: $httpCode, Response: $response", 'error');

            // 토큰이 유효하지 않으면 비활성화
            if ($httpCode === 404 || strpos($response, 'NOT_FOUND') !== false) {
                deactivateFCMToken($token);
            }

            return false;
        }

    } catch (Exception $e) {
        fcmLog("Error sending FCM notification (v1 API): " . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Legacy FCM API로 푸시 알림 발송
 */
function sendFCMNotificationLegacy($token, $title, $body, $data = []) {
    try {
        $message = [
            'to' => $token,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => FCM_DEFAULT_SOUND,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
            ],
            'data' => array_merge([
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
            ], $data),
            'priority' => FCM_DEFAULT_PRIORITY
        ];

        $ch = curl_init(FCM_API_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: key=' . FCM_SERVER_KEY,
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($message)
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $result = json_decode($response, true);
            if (isset($result['success']) && $result['success'] > 0) {
                fcmLog("FCM notification sent successfully (Legacy API). Token: " . substr($token, 0, 20) . "...");
                return true;
            }
        }

        fcmLog("FCM notification failed (Legacy API). HTTP Code: $httpCode, Response: $response", 'error');

        // 토큰이 유효하지 않으면 비활성화
        $result = json_decode($response, true);
        if (isset($result['results'][0]['error']) &&
            in_array($result['results'][0]['error'], ['NotRegistered', 'InvalidRegistration'])) {
            deactivateFCMToken($token);
        }

        return false;

    } catch (Exception $e) {
        fcmLog("Error sending FCM notification (Legacy API): " . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * FCM 푸시 알림 발송 (자동으로 v1 또는 Legacy API 선택)
 */
function sendFCMNotification($token, $title, $body, $data = []) {
    if (defined('FCM_USE_V1_API') && FCM_USE_V1_API) {
        return sendFCMNotificationV1($token, $title, $body, $data);
    } else {
        return sendFCMNotificationLegacy($token, $title, $body, $data);
    }
}

/**
 * 사용자에게 푸시 알림 발송 (모든 기기)
 */
function sendPushToUser($username, $title, $body, $data = []) {
    $tokens = getUserFCMTokens($username);

    if (empty($tokens)) {
        fcmLog("No FCM tokens found for user: $username", 'warning');
        return 0;
    }

    $successCount = 0;
    foreach ($tokens as $tokenData) {
        if (sendFCMNotification($tokenData['token'], $title, $body, $data)) {
            $successCount++;
        }
    }

    fcmLog("Push sent to $successCount/" . count($tokens) . " devices for user: $username");
    return $successCount;
}

/**
 * 여러 사용자에게 푸시 알림 발송
 */
function sendPushToMultipleUsers($usernames, $title, $body, $data = []) {
    $totalSuccess = 0;
    $totalFailed = 0;

    foreach ($usernames as $username) {
        $result = sendPushToUser($username, $title, $body, $data);
        if ($result > 0) {
            $totalSuccess++;
        } else {
            $totalFailed++;
        }
    }

    fcmLog("Bulk push completed. Success: $totalSuccess users, Failed: $totalFailed users");
    return ['success' => $totalSuccess, 'failed' => $totalFailed];
}

/**
 * FCM 로그 기록
 */
function fcmLog($message, $level = 'info') {
    if (!defined('FCM_ENABLE_LOGGING') || !FCM_ENABLE_LOGGING) {
        return;
    }

    $logFile = __DIR__ . '/../logs/fcm.log';
    $logDir = dirname($logFile);

    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;

    file_put_contents($logFile, $logMessage, FILE_APPEND);

    // 디버그 모드에서는 화면에도 출력
    if (defined('FCM_DEBUG_MODE') && FCM_DEBUG_MODE) {
        echo "<div style='font-size:11px; color:#666;'>FCM Log [$level]: $message</div>";
    }
}

/**
 * 오래된 비활성 토큰 정리 (크론잡으로 실행 권장)
 */
function cleanupInactiveFCMTokens($daysOld = 90) {
    try {
        $pdo = getFCMPDO();
        if (!$pdo) return 0;

        $stmt = $pdo->prepare("
            DELETE FROM fcm_tokens
            WHERE is_active = 0
            AND updated_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$daysOld]);

        $deletedCount = $stmt->rowCount();
        fcmLog("Cleaned up $deletedCount inactive FCM tokens older than $daysOld days");

        return $deletedCount;

    } catch (PDOException $e) {
        fcmLog("Error cleaning up FCM tokens: " . $e->getMessage(), 'error');
        return 0;
    }
}
