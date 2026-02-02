<?php
/**
 * CRM 할일 관리 API
 */

require_once dirname(dirname(__DIR__)) . '/config.php';

// 로그인 확인
if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => '로그인이 필요합니다.'], 401);
}

$currentUser = getCurrentUser();
$pdo = getDB();

// GET: 할일 조회
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = $_GET['id'] ?? null;

    if ($id) {
        // 단일 할일 조회
        try {
            $stmt = $pdo->prepare("SELECT * FROM " . CRM_TODOS_TABLE . " WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $currentUser['crm_user_id']]);
            $todo = $stmt->fetch();

            if ($todo) {
                successResponse($todo);
            } else {
                errorResponse('할일을 찾을 수 없습니다.', 404);
            }
        } catch (Exception $e) {
            errorResponse('조회 중 오류가 발생했습니다.');
        }
    } else {
        // 목록 조회
        try {
            $stmt = $pdo->prepare("SELECT * FROM " . CRM_TODOS_TABLE . " WHERE user_id = ? ORDER BY is_completed ASC, deadline ASC");
            $stmt->execute([$currentUser['crm_user_id']]);
            $todos = $stmt->fetchAll();
            successResponse($todos);
        } catch (Exception $e) {
            errorResponse('조회 중 오류가 발생했습니다.');
        }
    }
    exit;
}

// POST: 할일 생성/수정/삭제/토글
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? 'create';

    switch ($action) {
        case 'create':
            // 할일 생성
            $title = trim($input['title'] ?? '');
            $description = trim($input['description'] ?? '');
            $priority = $input['priority'] ?? 'medium';
            $deadline = $input['deadline'] ?: null;
            $category = trim($input['category'] ?? '');

            if (empty($title)) {
                errorResponse('제목을 입력해주세요.');
            }

            try {
                $stmt = $pdo->prepare("INSERT INTO " . CRM_TODOS_TABLE . "
                    (user_id, title, description, priority, deadline, category, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([
                    $currentUser['crm_user_id'],
                    $title,
                    $description,
                    $priority,
                    $deadline,
                    $category
                ]);

                $newId = $pdo->lastInsertId();
                successResponse(['id' => $newId], '할일이 등록되었습니다.');
            } catch (Exception $e) {
                errorResponse('등록 중 오류가 발생했습니다.');
            }
            break;

        case 'update':
            // 할일 수정
            $id = $input['id'] ?? null;
            $title = trim($input['title'] ?? '');
            $description = trim($input['description'] ?? '');
            $priority = $input['priority'] ?? 'medium';
            $deadline = $input['deadline'] ?: null;
            $category = trim($input['category'] ?? '');

            if (!$id) {
                errorResponse('할일 ID가 필요합니다.');
            }
            if (empty($title)) {
                errorResponse('제목을 입력해주세요.');
            }

            try {
                $stmt = $pdo->prepare("UPDATE " . CRM_TODOS_TABLE . "
                    SET title = ?, description = ?, priority = ?, deadline = ?, category = ?, updated_at = NOW()
                    WHERE id = ? AND user_id = ?");
                $stmt->execute([
                    $title,
                    $description,
                    $priority,
                    $deadline,
                    $category,
                    $id,
                    $currentUser['crm_user_id']
                ]);

                if ($stmt->rowCount() === 0) {
                    errorResponse('할일을 찾을 수 없거나 권한이 없습니다.');
                }

                successResponse(null, '할일이 수정되었습니다.');
            } catch (Exception $e) {
                errorResponse('수정 중 오류가 발생했습니다.');
            }
            break;

        case 'toggle':
            // 완료 토글
            $id = $input['id'] ?? null;
            $isCompleted = $input['is_completed'] ?? 0;

            if (!$id) {
                errorResponse('할일 ID가 필요합니다.');
            }

            try {
                $stmt = $pdo->prepare("UPDATE " . CRM_TODOS_TABLE . "
                    SET is_completed = ?, completed_at = ?, updated_at = NOW()
                    WHERE id = ? AND user_id = ?");
                $stmt->execute([
                    $isCompleted ? 1 : 0,
                    $isCompleted ? date('Y-m-d H:i:s') : null,
                    $id,
                    $currentUser['crm_user_id']
                ]);

                if ($stmt->rowCount() === 0) {
                    errorResponse('할일을 찾을 수 없거나 권한이 없습니다.');
                }

                successResponse(null, $isCompleted ? '완료 처리되었습니다.' : '진행중으로 변경되었습니다.');
            } catch (Exception $e) {
                errorResponse('처리 중 오류가 발생했습니다.');
            }
            break;

        case 'delete':
            // 할일 삭제
            $id = $input['id'] ?? null;

            if (!$id) {
                errorResponse('할일 ID가 필요합니다.');
            }

            try {
                $stmt = $pdo->prepare("DELETE FROM " . CRM_TODOS_TABLE . " WHERE id = ? AND user_id = ?");
                $stmt->execute([$id, $currentUser['crm_user_id']]);

                if ($stmt->rowCount() === 0) {
                    errorResponse('할일을 찾을 수 없거나 권한이 없습니다.');
                }

                successResponse(null, '할일이 삭제되었습니다.');
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
