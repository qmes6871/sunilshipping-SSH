<?php
/**
 * 공지사항 API
 */

require_once dirname(dirname(__DIR__)) . '/config.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => '로그인이 필요합니다.'], 401);
}

$currentUser = getCurrentUser();
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = $_GET['id'] ?? null;

    if ($id) {
        try {
            $stmt = $pdo->prepare("SELECT n.*, u.name as creator_name
                FROM " . CRM_NOTICES_TABLE . " n
                LEFT JOIN " . CRM_USERS_TABLE . " u ON n.created_by = u.id
                WHERE n.id = ?");
            $stmt->execute([$id]);
            $notice = $stmt->fetch();

            if ($notice) {
                successResponse($notice);
            } else {
                errorResponse('공지를 찾을 수 없습니다.', 404);
            }
        } catch (Exception $e) {
            errorResponse('조회 중 오류가 발생했습니다.');
        }
    } else {
        try {
            $stmt = $pdo->query("SELECT * FROM " . CRM_NOTICES_TABLE . " ORDER BY is_important DESC, created_at DESC LIMIT 20");
            $notices = $stmt->fetchAll();
            successResponse($notices);
        } catch (Exception $e) {
            errorResponse('조회 중 오류가 발생했습니다.');
        }
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? 'create';

    switch ($action) {
        case 'mark_read':
            // 읽음 처리는 모든 로그인 사용자 가능
            $id = $input['id'] ?? null;
            if (!$id) errorResponse('공지 ID가 필요합니다.');

            try {
                // is_read 컬럼이 있는지 확인
                $colCheck = $pdo->query("SHOW COLUMNS FROM " . CRM_NOTICES_TABLE . " LIKE 'is_read'");
                if (!$colCheck->fetch()) {
                    // is_read 컬럼이 없으면 추가
                    $pdo->exec("ALTER TABLE " . CRM_NOTICES_TABLE . " ADD COLUMN is_read TINYINT(1) DEFAULT 0");
                }
                $stmt = $pdo->prepare("UPDATE " . CRM_NOTICES_TABLE . " SET is_read = 1 WHERE id = ?");
                $stmt->execute([$id]);
                successResponse(null, '읽음 처리되었습니다.');
            } catch (Exception $e) {
                successResponse(null, '읽음 처리되었습니다.'); // 실패해도 성공 응답
            }
            break;

        case 'create':
            if (!isAdmin()) errorResponse('관리자 권한이 필요합니다.', 403);

            $title = trim($input['title'] ?? '');
            $content = trim($input['content'] ?? '');
            $noticeType = $input['notice_type'] ?? 'company';
            $department = $input['department'] ?? null;
            $isImportant = $input['is_important'] ?? 0;

            if (empty($title)) errorResponse('제목을 입력해주세요.');

            try {
                // 테이블 컬럼 확인
                $columns = [];
                $colResult = $pdo->query("SHOW COLUMNS FROM " . CRM_NOTICES_TABLE);
                while ($col = $colResult->fetch()) {
                    $columns[] = $col['Field'];
                }

                // 동적 INSERT 생성
                $insertCols = ['title', 'content', 'created_at'];
                $insertVals = ['?', '?', 'NOW()'];
                $insertParams = [$title, $content];

                if (in_array('notice_type', $columns)) {
                    $insertCols[] = 'notice_type';
                    $insertVals[] = '?';
                    $insertParams[] = $noticeType;
                }
                if (in_array('department', $columns)) {
                    $insertCols[] = 'department';
                    $insertVals[] = '?';
                    $insertParams[] = $department;
                }
                if (in_array('is_important', $columns)) {
                    $insertCols[] = 'is_important';
                    $insertVals[] = '?';
                    $insertParams[] = $isImportant;
                }
                if (in_array('created_by', $columns) && !empty($currentUser['crm_user_id'])) {
                    $insertCols[] = 'created_by';
                    $insertVals[] = '?';
                    $insertParams[] = $currentUser['crm_user_id'];
                }

                $sql = "INSERT INTO " . CRM_NOTICES_TABLE . " (" . implode(', ', $insertCols) . ") VALUES (" . implode(', ', $insertVals) . ")";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($insertParams);
                successResponse(['id' => $pdo->lastInsertId()], '공지가 등록되었습니다.');
            } catch (Exception $e) {
                errorResponse('등록 중 오류가 발생했습니다: ' . $e->getMessage());
            }
            break;

        case 'update':
            if (!isAdmin()) errorResponse('관리자 권한이 필요합니다.', 403);

            $id = $input['id'] ?? null;
            if (!$id) errorResponse('공지 ID가 필요합니다.');

            $title = trim($input['title'] ?? '');
            $content = trim($input['content'] ?? '');
            $noticeType = $input['notice_type'] ?? 'company';
            $department = $input['department'] ?? null;
            $isImportant = $input['is_important'] ?? 0;

            if (empty($title)) errorResponse('제목을 입력해주세요.');

            try {
                // 테이블 컬럼 확인
                $columns = [];
                $colResult = $pdo->query("SHOW COLUMNS FROM " . CRM_NOTICES_TABLE);
                while ($col = $colResult->fetch()) {
                    $columns[] = $col['Field'];
                }

                // 동적 UPDATE 생성
                $updateParts = ['title = ?', 'content = ?'];
                $updateParams = [$title, $content];

                if (in_array('notice_type', $columns)) {
                    $updateParts[] = 'notice_type = ?';
                    $updateParams[] = $noticeType;
                }
                if (in_array('department', $columns)) {
                    $updateParts[] = 'department = ?';
                    $updateParams[] = $department;
                }
                if (in_array('is_important', $columns)) {
                    $updateParts[] = 'is_important = ?';
                    $updateParams[] = $isImportant;
                }
                if (in_array('updated_at', $columns)) {
                    $updateParts[] = 'updated_at = NOW()';
                }

                $updateParams[] = $id;
                $sql = "UPDATE " . CRM_NOTICES_TABLE . " SET " . implode(', ', $updateParts) . " WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($updateParams);
                successResponse(['id' => $id], '공지가 수정되었습니다.');
            } catch (Exception $e) {
                errorResponse('수정 중 오류가 발생했습니다: ' . $e->getMessage());
            }
            break;

        case 'delete':
            if (!isAdmin()) errorResponse('관리자 권한이 필요합니다.', 403);

            $id = $input['id'] ?? null;
            if (!$id) errorResponse('공지 ID가 필요합니다.');

            try {
                $stmt = $pdo->prepare("DELETE FROM " . CRM_NOTICES_TABLE . " WHERE id = ?");
                $stmt->execute([$id]);
                successResponse(null, '공지가 삭제되었습니다.');
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
