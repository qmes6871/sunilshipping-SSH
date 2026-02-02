<?php
/**
 * 루트별 주의사항 API
 */

require_once dirname(dirname(__DIR__)) . '/config.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => '로그인이 필요합니다.'], 401);
}

$currentUser = getCurrentUser();
$pdo = getDB();

// GET: 조회
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = $_GET['id'] ?? null;

    if ($id) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM " . CRM_ROUTES_TABLE . " WHERE id = ?");
            $stmt->execute([$id]);
            $warning = $stmt->fetch();

            if ($warning) {
                successResponse($warning);
            } else {
                errorResponse('주의사항을 찾을 수 없습니다.', 404);
            }
        } catch (Exception $e) {
            errorResponse('조회 중 오류가 발생했습니다.');
        }
    } else {
        try {
            $stmt = $pdo->query("SELECT * FROM " . CRM_ROUTES_TABLE . " ORDER BY created_at DESC");
            $warnings = $stmt->fetchAll();
            successResponse($warnings);
        } catch (Exception $e) {
            errorResponse('조회 중 오류가 발생했습니다.');
        }
    }
    exit;
}

// POST: 생성/수정/삭제
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isAdmin()) {
        errorResponse('관리자 권한이 필요합니다.', 403);
    }

    // JSON 또는 FormData 처리
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (strpos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
    } else {
        $input = $_POST;
    }

    $action = $input['action'] ?? 'create';

    switch ($action) {
        case 'create':
        case 'update':
            $id = $input['id'] ?? null;
            $routeName = trim($input['route_name'] ?? '');
            $status = $input['status'] ?? 'normal';
            $title = trim($input['title'] ?? '');
            $content = trim($input['content'] ?? '');

            if (empty($routeName)) errorResponse('루트를 선택해주세요.');
            if (empty($title)) errorResponse('제목을 입력해주세요.');
            if (empty($content)) errorResponse('내용을 입력해주세요.');

            // 파일 업로드 처리
            $attachmentPath = null;
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $result = uploadFile($_FILES['attachment'], 'routes');
                if ($result['success']) {
                    $attachmentPath = $result['file_path'];
                }
            }

            try {
                if ($id) {
                    // 수정
                    $sql = "UPDATE " . CRM_ROUTES_TABLE . " SET
                            route_name = ?, status = ?, title = ?, content = ?, updated_at = NOW()";
                    $params = [$routeName, $status, $title, $content];

                    if ($attachmentPath) {
                        $sql .= ", attachment_path = ?";
                        $params[] = $attachmentPath;
                    }

                    $sql .= " WHERE id = ?";
                    $params[] = $id;

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    successResponse(['id' => $id], '수정되었습니다.');
                } else {
                    // 생성
                    $stmt = $pdo->prepare("INSERT INTO " . CRM_ROUTES_TABLE . "
                        (route_name, status, title, content, attachment_path, created_by, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([
                        $routeName,
                        $status,
                        $title,
                        $content,
                        $attachmentPath,
                        $currentUser['crm_user_id']
                    ]);
                    successResponse(['id' => $pdo->lastInsertId()], '등록되었습니다.');
                }
            } catch (Exception $e) {
                errorResponse('저장 중 오류가 발생했습니다.');
            }
            break;

        case 'delete':
            $id = $input['id'] ?? null;
            if (!$id) errorResponse('ID가 필요합니다.');

            try {
                $stmt = $pdo->prepare("DELETE FROM " . CRM_ROUTES_TABLE . " WHERE id = ?");
                $stmt->execute([$id]);
                successResponse(null, '삭제되었습니다.');
            } catch (Exception $e) {
                errorResponse('삭제 중 오류가 발생했습니다.');
            }
            break;

        default:
            errorResponse('잘못된 요청입니다.');
    }
    exit;
}

errorResponse('지원하지 않는 메서드입니다.', 405);
