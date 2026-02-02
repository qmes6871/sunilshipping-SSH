<?php
/**
 * 사용자 메모 API
 */

require_once dirname(dirname(__DIR__)) . '/config.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => '로그인이 필요합니다.'], 401);
}

$currentUser = getCurrentUser();
$pdo = getDB();

$userId = $currentUser['crm_user_id'];

if (!$userId) {
    errorResponse('사용자 정보를 찾을 수 없습니다.');
}

// GET: 메모 조회
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->prepare("SELECT content FROM " . CRM_USER_MEMOS_TABLE . " WHERE user_id = ?");
        $stmt->execute([$userId]);
        $memo = $stmt->fetch();

        successResponse(['content' => $memo['content'] ?? '']);
    } catch (Exception $e) {
        errorResponse('조회 중 오류가 발생했습니다.');
    }
    exit;
}

// POST: 메모 저장
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $content = $input['content'] ?? '';

    try {
        // UPSERT (있으면 수정, 없으면 생성)
        $stmt = $pdo->prepare("SELECT id FROM " . CRM_USER_MEMOS_TABLE . " WHERE user_id = ?");
        $stmt->execute([$userId]);
        $exists = $stmt->fetch();

        if ($exists) {
            $stmt = $pdo->prepare("UPDATE " . CRM_USER_MEMOS_TABLE . " SET content = ?, updated_at = NOW() WHERE user_id = ?");
            $stmt->execute([$content, $userId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO " . CRM_USER_MEMOS_TABLE . " (user_id, content, updated_at) VALUES (?, ?, NOW())");
            $stmt->execute([$userId, $content]);
        }

        successResponse(null, '메모가 저장되었습니다.');
    } catch (Exception $e) {
        errorResponse('저장 중 오류가 발생했습니다.');
    }
    exit;
}

errorResponse('지원하지 않는 메서드입니다.', 405);
