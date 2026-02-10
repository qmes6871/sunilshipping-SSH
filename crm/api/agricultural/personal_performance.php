<?php
/**
 * 농산물 개인실적 API
 */

require_once dirname(dirname(__DIR__)) . '/config.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => '로그인이 필요합니다.'], 401);
}

$currentUser = getCurrentUser();
$pdo = getDB();

// GET: 개인실적 조회
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $year = $_GET['year'] ?? date('Y');
    $month = $_GET['month'] ?? null;

    try {
        if ($month && $month !== 'all') {
            $stmt = $pdo->prepare("SELECT * FROM " . CRM_AGRI_PERSONAL_PERFORMANCE_TABLE . "
                WHERE year = ? AND month = ?
                ORDER BY achievement_rate DESC");
            $stmt->execute([$year, $month]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM " . CRM_AGRI_PERSONAL_PERFORMANCE_TABLE . "
                WHERE year = ?
                ORDER BY month DESC, achievement_rate DESC");
            $stmt->execute([$year]);
        }
        $data = $stmt->fetchAll();
        successResponse($data);
    } catch (Exception $e) {
        errorResponse('조회 중 오류가 발생했습니다: ' . $e->getMessage());
    }
    exit;
}

// POST: 개인실적 등록/수정/삭제
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // JSON 또는 POST 입력 처리
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
    } else {
        $input = $_POST;
    }

    $action = $input['action'] ?? 'create';

    switch ($action) {
        case 'create':
            $year = $input['year'] ?? date('Y');
            $month = $input['month'] ?? date('n');
            $itemName = trim($input['item_name'] ?? $input['crop'] ?? '');
            $targetAmount = floatval($input['target_amount'] ?? $input['target'] ?? 0);
            $actualAmount = floatval($input['actual_amount'] ?? $input['actual'] ?? 0);
            $notes = trim($input['notes'] ?? '');

            // 달성률 계산
            $achievementRate = $targetAmount > 0 ? round(($actualAmount / $targetAmount) * 100, 2) : 0;

            // 직원명 가져오기
            $employeeName = $currentUser['mb_name'] ?? $currentUser['mb_nick'] ?? '';

            if (empty($itemName)) {
                errorResponse('품목을 선택해주세요.');
            }

            try {
                $stmt = $pdo->prepare("INSERT INTO " . CRM_AGRI_PERSONAL_PERFORMANCE_TABLE . "
                    (user_id, employee_name, year, month, item_name, target_amount, actual_amount, achievement_rate, notes, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                    target_amount = VALUES(target_amount),
                    actual_amount = VALUES(actual_amount),
                    achievement_rate = VALUES(achievement_rate),
                    notes = VALUES(notes),
                    updated_at = NOW()");
                $stmt->execute([
                    $currentUser['crm_user_id'],
                    $employeeName,
                    $year,
                    $month,
                    $itemName,
                    $targetAmount,
                    $actualAmount,
                    $achievementRate,
                    $notes
                ]);

                $newId = $pdo->lastInsertId();
                successResponse(['id' => $newId], '실적이 등록되었습니다.');
            } catch (Exception $e) {
                error_log("Agricultural personal performance create error: " . $e->getMessage());
                errorResponse('등록 중 오류가 발생했습니다: ' . $e->getMessage());
            }
            break;

        case 'update':
            $id = $input['id'] ?? null;
            if (!$id) {
                errorResponse('ID가 필요합니다.');
            }

            $targetAmount = floatval($input['target_amount'] ?? 0);
            $actualAmount = floatval($input['actual_amount'] ?? 0);
            $achievementRate = $targetAmount > 0 ? round(($actualAmount / $targetAmount) * 100, 2) : 0;

            try {
                $stmt = $pdo->prepare("UPDATE " . CRM_AGRI_PERSONAL_PERFORMANCE_TABLE . " SET
                    year = ?, month = ?, item_name = ?, target_amount = ?,
                    actual_amount = ?, achievement_rate = ?, notes = ?, updated_at = NOW()
                    WHERE id = ?");
                $stmt->execute([
                    $input['year'] ?? date('Y'),
                    $input['month'] ?? date('n'),
                    trim($input['item_name'] ?? ''),
                    $targetAmount,
                    $actualAmount,
                    $achievementRate,
                    trim($input['notes'] ?? ''),
                    $id
                ]);

                successResponse(null, '실적이 수정되었습니다.');
            } catch (Exception $e) {
                errorResponse('수정 중 오류가 발생했습니다: ' . $e->getMessage());
            }
            break;

        case 'delete':
            $id = $input['id'] ?? null;
            if (!$id) {
                errorResponse('ID가 필요합니다.');
            }

            try {
                $stmt = $pdo->prepare("DELETE FROM " . CRM_AGRI_PERSONAL_PERFORMANCE_TABLE . " WHERE id = ?");
                $stmt->execute([$id]);
                successResponse(null, '삭제되었습니다.');
            } catch (Exception $e) {
                errorResponse('삭제 중 오류가 발생했습니다: ' . $e->getMessage());
            }
            break;

        default:
            errorResponse('잘못된 요청입니다.');
    }
    exit;
}

errorResponse('지원하지 않는 메서드입니다.', 405);
