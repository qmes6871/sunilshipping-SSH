<?php
/**
 * FCM 토큰 등록 API
 * 앱에서 FCM 토큰을 서버에 등록
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once dirname(dirname(__DIR__)) . '/config.php';

$input = json_decode(file_get_contents('php://input'), true);
$token = $input['token'] ?? null;
$userId = $input['user_id'] ?? null;

if (!$token) {
    echo json_encode(['success' => false, 'message' => '토큰이 필요합니다.']);
    exit;
}

$pdo = getDB();

try {
    // 기존 토큰이 있는지 확인
    $stmt = $pdo->prepare("SELECT id FROM crm_fcm_tokens WHERE token = ?");
    $stmt->execute([$token]);
    $existing = $stmt->fetch();

    if ($existing) {
        // 토큰 업데이트 (마지막 활성 시간)
        $stmt = $pdo->prepare("UPDATE crm_fcm_tokens SET updated_at = NOW(), user_id = COALESCE(?, user_id) WHERE token = ?");
        $stmt->execute([$userId, $token]);
    } else {
        // 새 토큰 등록
        $stmt = $pdo->prepare("INSERT INTO crm_fcm_tokens (token, user_id, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
        $stmt->execute([$token, $userId]);
    }

    echo json_encode(['success' => true, 'message' => '토큰이 등록되었습니다.']);

} catch (Exception $e) {
    error_log('FCM token registration error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '토큰 등록 중 오류가 발생했습니다.']);
}
