<?php
/**
 * 농산물 성과 API
 */

require_once dirname(dirname(__DIR__)) . '/config.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => '로그인이 필요합니다.'], 401);
}

$currentUser = getCurrentUser();
$pdo = getDB();

// GET: 성과 조회
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $year = $_GET['year'] ?? date('Y');
    $month = $_GET['month'] ?? date('n');

    try {
        $stmt = $pdo->prepare("SELECT * FROM " . CRM_AGRI_PERFORMANCE_TABLE . "
            WHERE year = ? AND month = ?
            ORDER BY id DESC");
        $stmt->execute([$year, $month]);
        $data = $stmt->fetchAll();
        successResponse($data);
    } catch (Exception $e) {
        errorResponse('조회 중 오류가 발생했습니다: ' . $e->getMessage());
    }
    exit;
}

// POST: 성과 등록/수정/삭제
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
            $business = trim($input['business'] ?? '');
            $actual = floatval($input['actual'] ?? 0);
            $target = floatval($input['target'] ?? 0);
            $freshness = !empty($input['freshness']) ? floatval($input['freshness']) : null;
            $quality = !empty($input['quality']) ? floatval($input['quality']) : null;
            $note = trim($input['note'] ?? '');

            if (empty($business)) {
                errorResponse('사업 구분을 선택해주세요.');
            }

            try {
                $stmt = $pdo->prepare("INSERT INTO " . CRM_AGRI_PERFORMANCE_TABLE . "
                    (user_id, year, month, business, actual, target, freshness, quality, note, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([
                    $currentUser['crm_user_id'],
                    $year,
                    $month,
                    $business,
                    $actual,
                    $target,
                    $freshness,
                    $quality,
                    $note
                ]);

                $newId = $pdo->lastInsertId();
                successResponse(['id' => $newId], '성과가 등록되었습니다.');
            } catch (Exception $e) {
                error_log("Agricultural performance create error: " . $e->getMessage());
                errorResponse('등록 중 오류가 발생했습니다: ' . $e->getMessage());
            }
            break;

        case 'update':
            $id = $input['id'] ?? null;
            if (!$id) {
                errorResponse('ID가 필요합니다.');
            }

            try {
                $stmt = $pdo->prepare("UPDATE " . CRM_AGRI_PERFORMANCE_TABLE . " SET
                    year = ?, month = ?, business = ?, actual = ?, target = ?,
                    freshness = ?, quality = ?, note = ?, updated_at = NOW()
                    WHERE id = ?");
                $stmt->execute([
                    $input['year'] ?? date('Y'),
                    $input['month'] ?? date('n'),
                    trim($input['business'] ?? ''),
                    floatval($input['actual'] ?? 0),
                    floatval($input['target'] ?? 0),
                    !empty($input['freshness']) ? floatval($input['freshness']) : null,
                    !empty($input['quality']) ? floatval($input['quality']) : null,
                    trim($input['note'] ?? ''),
                    $id
                ]);

                successResponse(null, '성과가 수정되었습니다.');
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
                $stmt = $pdo->prepare("DELETE FROM " . CRM_AGRI_PERFORMANCE_TABLE . " WHERE id = ?");
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
