<?php
/**
 * KMS API
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
            // 조회수 증가
            $stmt = $pdo->prepare("UPDATE " . CRM_KMS_TABLE . " SET view_count = view_count + 1 WHERE id = ?");
            $stmt->execute([$id]);

            $stmt = $pdo->prepare("SELECT k.*, u.name as creator_name
                FROM " . CRM_KMS_TABLE . " k
                LEFT JOIN " . CRM_USERS_TABLE . " u ON k.created_by = u.id
                WHERE k.id = ?");
            $stmt->execute([$id]);
            $doc = $stmt->fetch();

            if ($doc) {
                successResponse($doc);
            } else {
                errorResponse('문서를 찾을 수 없습니다.', 404);
            }
        } catch (Exception $e) {
            errorResponse('조회 중 오류가 발생했습니다.');
        }
    } else {
        try {
            $stmt = $pdo->query("SELECT * FROM " . CRM_KMS_TABLE . " ORDER BY created_at DESC LIMIT 20");
            $docs = $stmt->fetchAll();
            successResponse($docs);
        } catch (Exception $e) {
            errorResponse('조회 중 오류가 발생했습니다.');
        }
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isAdmin()) {
        errorResponse('관리자 권한이 필요합니다.', 403);
    }

    // FormData 또는 JSON 데이터 처리
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
    } else {
        $input = $_POST;
    }

    $action = $input['action'] ?? 'create';
    $id = $input['id'] ?? null;

    // id가 있으면 update로 처리
    if ($id && $action === 'create') {
        $action = 'update';
    }

    switch ($action) {
        case 'create':
            $title = trim($input['title'] ?? '');
            $part = $input['part'] ?? 'logi';
            $classification = $input['classification'] ?? 'guide';
            $content = trim($input['content'] ?? '');
            $tags = trim($input['tags'] ?? '');

            if (empty($title)) errorResponse('제목을 입력해주세요.');

            try {
                // 테이블 컬럼 확인
                $columns = [];
                $colResult = $pdo->query("SHOW COLUMNS FROM " . CRM_KMS_TABLE);
                while ($col = $colResult->fetch()) {
                    $columns[] = $col['Field'];
                }

                // 동적 INSERT 생성
                $insertCols = ['title', 'content', 'created_at'];
                $insertVals = ['?', '?', 'NOW()'];
                $insertParams = [$title, $content];

                // part 컬럼 ENUM 값 확인 후 삽입
                if (in_array('part', $columns) && !empty($part)) {
                    // ENUM 타입인 경우 허용 값 확인
                    $colInfo = $pdo->query("SHOW COLUMNS FROM " . CRM_KMS_TABLE . " WHERE Field = 'part'")->fetch();
                    if ($colInfo && strpos($colInfo['Type'], 'enum') !== false) {
                        // ENUM 값 추출
                        preg_match("/enum\((.*)\)/", $colInfo['Type'], $matches);
                        if (!empty($matches[1])) {
                            $enumValues = str_getcsv($matches[1], ',', "'");
                            if (in_array($part, $enumValues)) {
                                $insertCols[] = 'part';
                                $insertVals[] = '?';
                                $insertParams[] = $part;
                            }
                        }
                    } else {
                        // VARCHAR 등 다른 타입이면 그냥 삽입
                        $insertCols[] = 'part';
                        $insertVals[] = '?';
                        $insertParams[] = $part;
                    }
                }
                // classification 컬럼 ENUM 값 확인 후 삽입
                if (in_array('classification', $columns) && !empty($classification)) {
                    $colInfo = $pdo->query("SHOW COLUMNS FROM " . CRM_KMS_TABLE . " WHERE Field = 'classification'")->fetch();
                    if ($colInfo && strpos($colInfo['Type'], 'enum') !== false) {
                        preg_match("/enum\((.*)\)/", $colInfo['Type'], $matches);
                        if (!empty($matches[1])) {
                            $enumValues = str_getcsv($matches[1], ',', "'");
                            if (in_array($classification, $enumValues)) {
                                $insertCols[] = 'classification';
                                $insertVals[] = '?';
                                $insertParams[] = $classification;
                            }
                        }
                    } else {
                        $insertCols[] = 'classification';
                        $insertVals[] = '?';
                        $insertParams[] = $classification;
                    }
                }
                if (in_array('tags', $columns)) {
                    $insertCols[] = 'tags';
                    $insertVals[] = '?';
                    $insertParams[] = $tags;
                }
                if (in_array('created_by', $columns) && !empty($currentUser['crm_user_id'])) {
                    $insertCols[] = 'created_by';
                    $insertVals[] = '?';
                    $insertParams[] = $currentUser['crm_user_id'];
                }

                // 첨부파일 처리
                if (in_array('attachment_path', $columns) && !empty($_FILES['attachment']['name'])) {
                    $uploadDir = dirname(dirname(__DIR__)) . '/uploads/kms/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $fileName = time() . '_' . basename($_FILES['attachment']['name']);
                    $targetPath = $uploadDir . $fileName;
                    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetPath)) {
                        $insertCols[] = 'attachment_path';
                        $insertVals[] = '?';
                        $insertParams[] = 'kms/' . $fileName;
                    }
                }

                $sql = "INSERT INTO " . CRM_KMS_TABLE . " (" . implode(', ', $insertCols) . ") VALUES (" . implode(', ', $insertVals) . ")";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($insertParams);
                successResponse(['id' => $pdo->lastInsertId()], '문서가 등록되었습니다.');
            } catch (Exception $e) {
                errorResponse('등록 중 오류가 발생했습니다: ' . $e->getMessage());
            }
            break;

        case 'update':
            if (!$id) errorResponse('문서 ID가 필요합니다.');

            $title = trim($input['title'] ?? '');
            $part = $input['part'] ?? 'logi';
            $classification = $input['classification'] ?? 'guide';
            $content = trim($input['content'] ?? '');
            $tags = trim($input['tags'] ?? '');

            if (empty($title)) errorResponse('제목을 입력해주세요.');

            try {
                // 테이블 컬럼 확인
                $columns = [];
                $colResult = $pdo->query("SHOW COLUMNS FROM " . CRM_KMS_TABLE);
                while ($col = $colResult->fetch()) {
                    $columns[] = $col['Field'];
                }

                // 동적 UPDATE 생성
                $updateParts = ['title = ?', 'content = ?'];
                $updateParams = [$title, $content];

                // part 컬럼 ENUM 값 확인 후 업데이트
                if (in_array('part', $columns) && !empty($part)) {
                    $colInfo = $pdo->query("SHOW COLUMNS FROM " . CRM_KMS_TABLE . " WHERE Field = 'part'")->fetch();
                    if ($colInfo && strpos($colInfo['Type'], 'enum') !== false) {
                        preg_match("/enum\((.*)\)/", $colInfo['Type'], $matches);
                        if (!empty($matches[1])) {
                            $enumValues = str_getcsv($matches[1], ',', "'");
                            if (in_array($part, $enumValues)) {
                                $updateParts[] = 'part = ?';
                                $updateParams[] = $part;
                            }
                        }
                    } else {
                        $updateParts[] = 'part = ?';
                        $updateParams[] = $part;
                    }
                }
                // classification 컬럼 ENUM 값 확인 후 업데이트
                if (in_array('classification', $columns) && !empty($classification)) {
                    $colInfo = $pdo->query("SHOW COLUMNS FROM " . CRM_KMS_TABLE . " WHERE Field = 'classification'")->fetch();
                    if ($colInfo && strpos($colInfo['Type'], 'enum') !== false) {
                        preg_match("/enum\((.*)\)/", $colInfo['Type'], $matches);
                        if (!empty($matches[1])) {
                            $enumValues = str_getcsv($matches[1], ',', "'");
                            if (in_array($classification, $enumValues)) {
                                $updateParts[] = 'classification = ?';
                                $updateParams[] = $classification;
                            }
                        }
                    } else {
                        $updateParts[] = 'classification = ?';
                        $updateParams[] = $classification;
                    }
                }
                if (in_array('tags', $columns)) {
                    $updateParts[] = 'tags = ?';
                    $updateParams[] = $tags;
                }
                if (in_array('updated_at', $columns)) {
                    $updateParts[] = 'updated_at = NOW()';
                }

                // 첨부파일 처리
                if (in_array('attachment_path', $columns) && !empty($_FILES['attachment']['name'])) {
                    $uploadDir = dirname(dirname(__DIR__)) . '/uploads/kms/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $fileName = time() . '_' . basename($_FILES['attachment']['name']);
                    $targetPath = $uploadDir . $fileName;
                    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetPath)) {
                        $updateParts[] = 'attachment_path = ?';
                        $updateParams[] = 'kms/' . $fileName;
                    }
                }

                $updateParams[] = $id;
                $sql = "UPDATE " . CRM_KMS_TABLE . " SET " . implode(', ', $updateParts) . " WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($updateParams);
                successResponse(['id' => $id], '문서가 수정되었습니다.');
            } catch (Exception $e) {
                errorResponse('수정 중 오류가 발생했습니다: ' . $e->getMessage());
            }
            break;

        case 'delete':
            $id = $input['id'] ?? null;
            if (!$id) errorResponse('문서 ID가 필요합니다.');

            try {
                $stmt = $pdo->prepare("DELETE FROM " . CRM_KMS_TABLE . " WHERE id = ?");
                $stmt->execute([$id]);
                successResponse(null, '문서가 삭제되었습니다.');
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
