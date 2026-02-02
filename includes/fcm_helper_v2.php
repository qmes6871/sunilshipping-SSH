<?php
/**
 * FCM (Firebase Cloud Messaging) HTTP v1 API Helper
 * 최신 HTTP v1 API 사용
 */

class FCMHelperV2 {
    private $serviceAccountPath;
    private $projectId;
    private $accessToken;
    private $tokenExpiry;

    public function __construct($serviceAccountPath = null) {
        if ($serviceAccountPath === null) {
            $serviceAccountPath = __DIR__ . '/firebase-service-account.json';
        }

        $this->serviceAccountPath = $serviceAccountPath;

        if (!file_exists($this->serviceAccountPath)) {
            throw new Exception('서비스 계정 JSON 파일을 찾을 수 없습니다: ' . $this->serviceAccountPath);
        }

        $serviceAccount = json_decode(file_get_contents($this->serviceAccountPath), true);
        $this->projectId = $serviceAccount['project_id'];
    }

    /**
     * 서버 시간 보정
     * - Google OAuth JWT iat/exp는 현재 시간과 근접해야 합니다.
     * - 고정된 과거/미래 시간을 쓰면 invalid_grant 오류가 납니다.
     * - 필요 시 환경변수 FCM_TIME_OFFSET(초)로 소규모 오프셋만 적용하세요.
     */
    private function getAdjustedTime() {
        $now = time();
        $offset = getenv('FCM_TIME_OFFSET') !== false ? intval(getenv('FCM_TIME_OFFSET')) : 0;
        return $now + $offset;
    }

    /**
     * Google OAuth 2.0 액세스 토큰 가져오기
     */
    private function getAccessToken() {
        if ($this->accessToken && $this->tokenExpiry && time() < $this->tokenExpiry) {
            return $this->accessToken;
        }

        $serviceAccount = json_decode(file_get_contents($this->serviceAccountPath), true);

        // JWT 생성 (시간 보정 사용)
        $now = $this->getAdjustedTime();
        // 안전장치: 시스템 시간과 5분 이상 차이나면 현재 시간으로 보정
        $sysNow = time();
        if (abs($now - $sysNow) > 300) {
            $now = $sysNow;
        }
        $exp = $now + 3600;

        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT'
        ];

        $payload = [
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $exp
        ];

        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        $signatureInput = $headerEncoded . '.' . $payloadEncoded;

        // 서명 생성
        $privateKey = openssl_pkey_get_private($serviceAccount['private_key']);
        if ($privateKey === false) {
            throw new Exception('Private key 로드 실패');
        }

        $success = openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        if (!$success) {
            throw new Exception('서명 생성 실패');
        }

        $signatureEncoded = $this->base64UrlEncode($signature);
        $jwt = $signatureInput . '.' . $signatureEncoded;

        // 액세스 토큰 요청
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]));

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $err = $result;
            $decoded = json_decode($result, true);
            if (is_array($decoded)) {
                if (isset($decoded['error_description'])) {
                    $err = $decoded['error_description'];
                } elseif (isset($decoded['error'])) {
                    if (is_array($decoded['error'])) {
                        $err = ($decoded['error']['message'] ?? json_encode($decoded['error']));
                    } else {
                        $err = (string)$decoded['error'];
                    }
                }
            }
            throw new Exception('액세스 토큰 발급 실패 (HTTP ' . $httpCode . '): ' . $err);
        }

        $response = json_decode($result, true);
        $this->accessToken = $response['access_token'];
        $this->tokenExpiry = time() + ($response['expires_in'] - 60);

        return $this->accessToken;
    }

    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public function sendToTopic($topic, $title, $body, $data = []) {
        $accessToken = $this->getAccessToken();

        $message = [
            'message' => [
                'topic' => $topic,
                'notification' => [
                    'title' => $title,
                    'body' => $body
                ],
                'data' => $data,
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'sound' => 'default',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
                    ]
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'badge' => 1
                        ]
                    ]
                ]
            ]
        ];

        $url = 'https://fcm.googleapis.com/v1/projects/' . $this->projectId . '/messages:send';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($result === false) {
            return [
                'success' => false,
                'message' => 'CURL 오류: ' . $error,
                'http_code' => $httpCode
            ];
        }

        $response = json_decode($result, true);

        return [
            'success' => ($httpCode == 200 && isset($response['name'])),
            'message' => $result,
            'http_code' => $httpCode,
            'response' => $response
        ];
    }

    public function sendHotItemNotification($itemData) {
        $title = "지금 새로운 HOT ITEM!";
        $body = $itemData['product_name'] ?? '새로운 상품이 등록되었어요';

        $discount = 0;
        if (!empty($itemData['original_price']) && !empty($itemData['sale_price'])) {
            $original = floatval($itemData['original_price']);
            $sale = floatval($itemData['sale_price']);
            if ($original > 0) {
                $discount = round((($original - $sale) / $original) * 100);
                if ($discount > 0) {
                    $body .= " (최대 {$discount}% 할인)";
                }
            }
        }

        $data = [
            'type' => 'hot_item',
            'item_id' => (string)($itemData['id'] ?? ''),
            'product_name' => $itemData['product_name'] ?? '',
            'original_price' => (string)($itemData['original_price'] ?? '0'),
            'sale_price' => (string)($itemData['sale_price'] ?? '0'),
            'discount' => (string)$discount,
            'image_path' => $itemData['image_path'] ?? '',
            'category' => $itemData['category'] ?? '',
            'badge_type' => $itemData['badge_type'] ?? ''
        ];

        return $this->sendToTopic('all_users', $title, $body, $data);
    }
}

function sendHotItemPushNotification($itemData, $conn = null) {
    try {
        $fcm = new FCMHelperV2();
        $result = $fcm->sendHotItemNotification($itemData);

        if ($conn) {
            $logData = json_encode([
                'item_id' => $itemData['id'] ?? null,
                'result' => $result,
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_UNICODE);

            try {
                $stmt = $conn->prepare("INSERT INTO notification_logs (type, data, created_at) VALUES (?, ?, NOW())");
                if ($stmt) {
                    $type = 'hot_item_push_v2';
                    $stmt->bind_param("ss", $type, $logData);
                    $stmt->execute();
                    $stmt->close();
                }
            } catch (Exception $e) {
                // 로그 테이블이 없어도 계속 진행
            }
        }

        return $result;

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => '알림 전송 오류: ' . $e->getMessage()
        ];
    }
}
?>
