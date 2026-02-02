<?php
/**
 * CRM 사용자 개인 데이터 API (메모, 파일)
 */

require_once dirname(dirname(__DIR__)) . '/config.php';

// 로그인 확인
if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => '로그인이 필요합니다.'], 401);
}

$currentUser = getCurrentUser();
$pdo = getDB();

// GET: 조회
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $type = $_GET['type'] ?? 'memo';

    if ($type === 'memo') {
        try {
            $stmt = $pdo->prepare("SELECT content FROM " . CRM_USER_MEMOS_TABLE . " WHERE user_id = ?");
            $stmt->execute([$currentUser['crm_user_id']]);
            $memo = $stmt->fetch();
            successResponse(['content' => $memo['content'] ?? '']);
        } catch (Exception $e) {
            errorResponse('조회 중 오류가 발생했습니다.');
        }
    } elseif ($type === 'files') {
        try {
            $stmt = $pdo->prepare("SELECT * FROM " . CRM_USER_FILES_TABLE . " WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$currentUser['crm_user_id']]);
            $files = $stmt->fetchAll();
            successResponse($files);
        } catch (Exception $e) {
            errorResponse('조회 중 오류가 발생했습니다.');
        }
    } else {
        errorResponse('잘못된 요청입니다.');
    }
    exit;
}

// POST: 저장/업로드
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    // JSON 요청 (메모 저장)
    if (strpos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? 'save_memo';

        if ($action === 'save_memo') {
            $content = trim($input['content'] ?? '');

            try {
                // UPSERT 처리
                $stmt = $pdo->prepare("INSERT INTO " . CRM_USER_MEMOS_TABLE . " (user_id, content, updated_at)
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE content = VALUES(content), updated_at = NOW()");
                $stmt->execute([$currentUser['crm_user_id'], $content]);

                successResponse(null, '메모가 저장되었습니다.');
            } catch (Exception $e) {
                errorResponse('저장 중 오류가 발생했습니다.');
            }
        } elseif ($action === 'delete_file') {
            $fileId = $input['id'] ?? null;

            if (!$fileId) {
                errorResponse('파일 ID가 필요합니다.');
            }

            try {
                // 파일 정보 조회
                $stmt = $pdo->prepare("SELECT * FROM " . CRM_USER_FILES_TABLE . " WHERE id = ? AND user_id = ?");
                $stmt->execute([$fileId, $currentUser['crm_user_id']]);
                $file = $stmt->fetch();

                if (!$file) {
                    errorResponse('파일을 찾을 수 없습니다.');
                }

                // 물리적 파일 삭제
                deleteFile($file['file_path']);

                // DB에서 삭제
                $stmt = $pdo->prepare("DELETE FROM " . CRM_USER_FILES_TABLE . " WHERE id = ?");
                $stmt->execute([$fileId]);

                successResponse(null, '파일이 삭제되었습니다.');
            } catch (Exception $e) {
                errorResponse('삭제 중 오류가 발생했습니다.');
            }
        } else {
            errorResponse('잘못된 요청입니다.');
        }
    }
    // 파일 업로드 (multipart/form-data)
    else {
        if (empty($_FILES['file'])) {
            errorResponse('파일을 선택해주세요.');
        }

        try {
            $result = uploadFile($_FILES['file'], 'user_files');

            if (!$result['success']) {
                errorResponse($result['message']);
            }

            // DB에 저장
            $stmt = $pdo->prepare("INSERT INTO " . CRM_USER_FILES_TABLE . "
                (user_id, file_name, file_path, file_size, created_at)
                VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([
                $currentUser['crm_user_id'],
                $result['original_name'],
                $result['path'],
                $result['size']
            ]);

            $newId = $pdo->lastInsertId();

            successResponse([
                'id' => $newId,
                'file_name' => $result['original_name'],
                'file_path' => $result['path'],
                'file_size' => $result['size']
            ], '파일이 업로드되었습니다.');
        } catch (Exception $e) {
            errorResponse('업로드 중 오류가 발생했습니다.');
        }
    }
    exit;
}

errorResponse('지원하지 않는 메서드입니다.', 405);
