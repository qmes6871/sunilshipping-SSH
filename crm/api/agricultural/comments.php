<?php
/**
 * 농산물 활동 댓글 API
 */

require_once dirname(dirname(__DIR__)) . '/config.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => '로그인이 필요합니다.'], 401);
}

$currentUser = getCurrentUser();
$pdo = getDB();

// 테이블 존재 확인 및 생성
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_agri_activity_comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        activity_id INT NOT NULL,
        parent_id INT DEFAULT NULL,
        content TEXT NOT NULL,
        depth INT DEFAULT 0,
        created_by INT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT NULL,
        INDEX idx_activity (activity_id),
        INDEX idx_parent (parent_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {
    // 테이블이 이미 존재하면 무시
}

// GET: 댓글 조회
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $activityId = $_GET['activity_id'] ?? null;

    if (!$activityId) {
        errorResponse('활동 ID가 필요합니다.');
    }

    try {
        $stmt = $pdo->prepare("SELECT c.*, u.name as user_name
            FROM crm_agri_activity_comments c
            LEFT JOIN " . CRM_USERS_TABLE . " u ON c.created_by = u.id
            WHERE c.activity_id = ?
            ORDER BY c.created_at ASC");
        $stmt->execute([$activityId]);
        $comments = $stmt->fetchAll();
        successResponse($comments);
    } catch (Exception $e) {
        errorResponse('조회 중 오류가 발생했습니다: ' . $e->getMessage());
    }
    exit;
}

// POST: 댓글 생성/수정/삭제
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // FormData 또는 JSON 요청 처리
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'multipart/form-data') !== false) {
        $input = $_POST;
    } else {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }
    }
    $action = $input['action'] ?? 'create';

    switch ($action) {
        case 'create':
            $activityId = $input['activity_id'] ?? null;
            $content = trim($input['content'] ?? '');
            $parentId = $input['parent_id'] ?? null;

            if (!$activityId) {
                errorResponse('활동 ID가 필요합니다.');
            }

            // 이미지 업로드 처리
            $imagePath = null;
            $hasImage = isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK;
            if ($hasImage) {
                $uploadResult = uploadFile($_FILES['image'], 'agricultural/comments', CRM_ALLOWED_IMAGE_TYPES);
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

            // 부모 댓글의 depth 확인
            $depth = 0;
            if ($parentId) {
                $stmt = $pdo->prepare("SELECT depth FROM crm_agri_activity_comments WHERE id = ?");
                $stmt->execute([$parentId]);
                $parent = $stmt->fetch();
                if ($parent) {
                    $depth = min($parent['depth'] + 1, 2); // 최대 2단계까지
                }
            }

            try {
                $stmt = $pdo->prepare("INSERT INTO crm_agri_activity_comments
                    (activity_id, parent_id, content, depth, image, created_by, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([
                    $activityId,
                    $parentId,
                    $content,
                    $depth,
                    $imagePath,
                    $currentUser['crm_user_id']
                ]);

                $newId = $pdo->lastInsertId();
                successResponse(['id' => $newId, 'image' => $imagePath], '댓글이 등록되었습니다.');
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
                $stmt = $pdo->prepare("UPDATE crm_agri_activity_comments
                    SET content = ?, updated_at = NOW()
                    WHERE id = ?");
                $stmt->execute([$content, $id]);

                successResponse(null, '댓글이 수정되었습니다.');
            } catch (Exception $e) {
                errorResponse('수정 중 오류가 발생했습니다: ' . $e->getMessage());
            }
            break;

        case 'delete':
            $id = $input['id'] ?? null;

            if (!$id) {
                errorResponse('댓글 ID가 필요합니다.');
            }

            try {
                $stmt = $pdo->prepare("DELETE FROM crm_agri_activity_comments WHERE id = ?");
                $stmt->execute([$id]);

                successResponse(null, '댓글이 삭제되었습니다.');
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
