<?php
/**
 * 푸시알림 API
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

header('Content-Type: application/json');

// 관리자만 접근 가능
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

$pdo = getDB();

// JSON 또는 POST 입력 처리
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
} else {
    $input = $_POST;
}

$action = $input['action'] ?? 'save';

try {
    switch ($action) {
        case 'send':
        case 'draft':
            $id = $input['id'] ?? null;
            $title = trim($input['title'] ?? '');
            $message = trim($input['message'] ?? '');
            $channel = $input['channel'] ?? 'app';
            $targetAudience = $input['target_audience'] ?? 'all';
            $campaignName = trim($input['campaign_name'] ?? '');
            $scheduledTime = !empty($input['scheduled_time']) ? $input['scheduled_time'] : null;

            // 유효성 검사
            if (empty($title)) {
                throw new Exception('알림 제목을 입력해주세요.');
            }
            if (empty($message)) {
                throw new Exception('알림 내용을 입력해주세요.');
            }

            // 상태 결정
            if ($action === 'draft') {
                $status = 'draft';
            } elseif ($scheduledTime && strtotime($scheduledTime) > time()) {
                $status = 'scheduled';
            } else {
                $status = 'sent';
            }

            // 발송 대상 수 계산 (시뮬레이션)
            $targetCount = 0;
            if ($status === 'sent') {
                // 실제로는 Firebase/SMS API 연동 필요
                $targetCount = rand(10, 100); // 시뮬레이션
            }

            if ($id) {
                // 수정
                $stmt = $pdo->prepare("UPDATE " . CRM_PUSH_TABLE . " SET
                    title = ?,
                    message = ?,
                    channel = ?,
                    target_audience = ?,
                    campaign_name = ?,
                    scheduled_time = ?,
                    status = ?,
                    target_count = CASE WHEN ? = 'sent' THEN ? ELSE target_count END,
                    success_count = CASE WHEN ? = 'sent' THEN ? ELSE success_count END,
                    sent_at = CASE WHEN ? = 'sent' THEN NOW() ELSE sent_at END,
                    updated_at = NOW()
                    WHERE id = ?");
                $stmt->execute([
                    $title, $message, $channel, $targetAudience, $campaignName, $scheduledTime,
                    $status, $status, $targetCount, $status, $targetCount, $status, $id
                ]);

                $resultMessage = $action === 'draft' ? '임시저장되었습니다.' : ($status === 'scheduled' ? '예약되었습니다.' : '발송되었습니다.');
            } else {
                // 신규 등록
                $stmt = $pdo->prepare("INSERT INTO " . CRM_PUSH_TABLE . "
                    (title, message, channel, target_audience, campaign_name, scheduled_time, status, target_count, success_count, created_by, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([
                    $title, $message, $channel, $targetAudience, $campaignName, $scheduledTime,
                    $status, $targetCount, $targetCount, getCurrentUserId()
                ]);

                $resultMessage = $action === 'draft' ? '임시저장되었습니다.' : ($status === 'scheduled' ? '예약되었습니다.' : '발송되었습니다.');
            }

            echo json_encode(['success' => true, 'message' => $resultMessage]);
            break;

        case 'delete':
            $id = $input['id'] ?? null;
            if (!$id) {
                throw new Exception('삭제할 알림을 선택해주세요.');
            }

            $stmt = $pdo->prepare("DELETE FROM " . CRM_PUSH_TABLE . " WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['success' => true, 'message' => '삭제되었습니다.']);
            break;

        case 'get':
            $id = $input['id'] ?? $_GET['id'] ?? null;
            if (!$id) {
                throw new Exception('알림 ID가 필요합니다.');
            }

            $stmt = $pdo->prepare("SELECT * FROM " . CRM_PUSH_TABLE . " WHERE id = ?");
            $stmt->execute([$id]);
            $push = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$push) {
                throw new Exception('알림을 찾을 수 없습니다.');
            }

            echo json_encode(['success' => true, 'data' => $push]);
            break;

        default:
            throw new Exception('잘못된 요청입니다.');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
