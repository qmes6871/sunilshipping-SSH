<?php
/**
 * CRM 댓글/코멘트 API
 */

require_once dirname(dirname(__DIR__)) . '/config.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => '로그인이 필요합니다.'], 401);
}

$currentUser = getCurrentUser();
$pdo = getDB();

// GET: 댓글 조회
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $entityType = $_GET['entity_type'] ?? '';
    $entityId = $_GET['entity_id'] ?? null;

    if (!$entityType || !$entityId) {
        errorResponse('엔터티 정보가 필요합니다.');
    }

    try {
        $stmt = $pdo->prepare("SELECT c.*, u.name as user_name
            FROM " . CRM_COMMENTS_TABLE . " c
            LEFT JOIN " . CRM_USERS_TABLE . " u ON c.created_by = u.id
            WHERE c.entity_type = ? AND c.entity_id = ? AND c.is_deleted = 0
            ORDER BY c.created_at DESC");
        $stmt->execute([$entityType, $entityId]);
        $comments = $stmt->fetchAll();
        successResponse($comments);
    } catch (Exception $e) {
        errorResponse('조회 중 오류가 발생했습니다.');
    }
    exit;
}

// POST: 댓글 생성/수정/삭제
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // FormData 또는 JSON 요청 처리
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'multipart/form-data') !== false) {
        // FormData로 전송된 경우 (이미지 포함)
        $input = $_POST;
    } else {
        // JSON으로 전송된 경우
        $input = json_decode(file_get_contents('php://input'), true);
    }
    $action = $input['action'] ?? 'create';

    switch ($action) {
        case 'create':
            $entityType = $input['entity_type'] ?? '';
            $entityId = $input['entity_id'] ?? null;
            $content = trim($input['content'] ?? '');
            $commentType = $input['comment_type'] ?? 'comment';
            $parentId = $input['parent_id'] ?? null;

            if (!$entityType || !$entityId) {
                errorResponse('엔터티 정보가 필요합니다.');
            }

            // 이미지 업로드 처리
            $imagePath = null;
            $hasImage = isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK;
            if ($hasImage) {
                $uploadResult = uploadFile($_FILES['image'], 'comments', CRM_ALLOWED_IMAGE_TYPES);
                if ($uploadResult['success']) {
                    $imagePath = $uploadResult['file_path'];
                } else {
                    errorResponse($uploadResult['message'] ?? '이미지 업로드에 실패했습니다.');
                }
            }

            // 내용 또는 이미지 중 하나는 있어야 함
            if (empty($content) && empty($imagePath)) {
                errorResponse('내용 또는 이미지를 입력해주세요.');
            }

            try {
                $stmt = $pdo->prepare("INSERT INTO " . CRM_COMMENTS_TABLE . "
                    (entity_type, entity_id, parent_id, content, comment_type, image, created_by, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([
                    $entityType,
                    $entityId,
                    $parentId,
                    $content,
                    $commentType,
                    $imagePath,
                    $currentUser['crm_user_id']
                ]);

                $newId = $pdo->lastInsertId();
                successResponse(['id' => $newId], '댓글이 등록되었습니다.');
            } catch (Exception $e) {
                errorResponse('등록 중 오류가 발생했습니다: ' . $e->getMessage());
            }
            break;

        case 'update':
            $id = $input['id'] ?? null;
            $content = trim($input['content'] ?? '');

            if (!$id) {
                errorResponse('댓글 ID가 필요합니다.');
            }
            if (empty($content)) {
                errorResponse('내용을 입력해주세요.');
            }

            try {
                $stmt = $pdo->prepare("UPDATE " . CRM_COMMENTS_TABLE . "
                    SET content = ?, updated_at = NOW()
                    WHERE id = ? AND created_by = ?");
                $stmt->execute([$content, $id, $currentUser['crm_user_id']]);

                if ($stmt->rowCount() === 0) {
                    errorResponse('댓글을 찾을 수 없거나 권한이 없습니다.');
                }

                successResponse(null, '댓글이 수정되었습니다.');
            } catch (Exception $e) {
                errorResponse('수정 중 오류가 발생했습니다.');
            }
            break;

        case 'delete':
            $id = $input['id'] ?? null;

            if (!$id) {
                errorResponse('댓글 ID가 필요합니다.');
            }

            try {
                $stmt = $pdo->prepare("UPDATE " . CRM_COMMENTS_TABLE . "
                    SET is_deleted = 1, updated_at = NOW()
                    WHERE id = ? AND created_by = ?");
                $stmt->execute([$id, $currentUser['crm_user_id']]);

                if ($stmt->rowCount() === 0) {
                    errorResponse('댓글을 찾을 수 없거나 권한이 없습니다.');
                }

                successResponse(null, '댓글이 삭제되었습니다.');
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
