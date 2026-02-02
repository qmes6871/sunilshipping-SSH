<?php
/**
 * 사용자 개인 파일 API
 */

require_once dirname(dirname(__DIR__)) . '/config.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => '로그인이 필요합니다.'], 401);
}

$currentUser = getCurrentUser();
$pdo = getDB();

// 테이블 생성 (없으면)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS " . CRM_USER_FILES_TABLE . " (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_size INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {
    // 테이블이 이미 존재하면 무시
}

// user_id 가져오기 (crm_user_id 또는 mb_id 사용)
$userId = $currentUser['crm_user_id'] ?? null;
if (!$userId) {
    // crm_user_id가 없으면 mb_id의 해시값 사용
    $userId = crc32($currentUser['mb_id'] ?? 'guest');
}

// GET: 파일 목록 조회
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->prepare("SELECT * FROM " . CRM_USER_FILES_TABLE . " WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        $files = $stmt->fetchAll();

        successResponse($files);
    } catch (Exception $e) {
        errorResponse('조회 중 오류가 발생했습니다.');
    }
    exit;
}

// POST: 파일 업로드
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 삭제 요청 확인
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $fileId = $_POST['id'] ?? null;

        if (!$fileId) {
            errorResponse('파일 ID가 필요합니다.');
        }

        try {
            // 파일 정보 조회
            $stmt = $pdo->prepare("SELECT * FROM " . CRM_USER_FILES_TABLE . " WHERE id = ? AND user_id = ?");
            $stmt->execute([$fileId, $userId]);
            $file = $stmt->fetch();

            if (!$file) {
                errorResponse('파일을 찾을 수 없습니다.');
            }

            // 실제 파일 삭제
            $filePath = CRM_UPLOAD_PATH . '/' . $file['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // DB에서 삭제
            $stmt = $pdo->prepare("DELETE FROM " . CRM_USER_FILES_TABLE . " WHERE id = ?");
            $stmt->execute([$fileId]);

            successResponse(null, '파일이 삭제되었습니다.');
        } catch (Exception $e) {
            errorResponse('삭제 중 오류가 발생했습니다.');
        }
        exit;
    }

    // 파일 업로드
    if (!isset($_FILES['file'])) {
        errorResponse('파일이 전송되지 않았습니다.');
    }

    if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => '파일 크기가 서버 제한을 초과했습니다.',
            UPLOAD_ERR_FORM_SIZE => '파일 크기가 폼 제한을 초과했습니다.',
            UPLOAD_ERR_PARTIAL => '파일이 부분적으로만 업로드되었습니다.',
            UPLOAD_ERR_NO_FILE => '파일이 선택되지 않았습니다.',
            UPLOAD_ERR_NO_TMP_DIR => '임시 폴더가 없습니다.',
            UPLOAD_ERR_CANT_WRITE => '디스크에 쓸 수 없습니다.',
            UPLOAD_ERR_EXTENSION => '확장 프로그램에 의해 업로드가 중지되었습니다.'
        ];
        $errorMsg = $uploadErrors[$_FILES['file']['error']] ?? '파일 업로드에 실패했습니다.';
        errorResponse($errorMsg);
    }

    $file = $_FILES['file'];

    // 파일 크기 체크
    if ($file['size'] > CRM_MAX_UPLOAD_SIZE) {
        errorResponse('파일 크기가 너무 큽니다. (최대 50MB)');
    }

    // 파일 확장자 체크
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedTypes = array_merge(CRM_ALLOWED_IMAGE_TYPES, CRM_ALLOWED_DOC_TYPES);

    if (!in_array($ext, $allowedTypes)) {
        errorResponse('허용되지 않는 파일 형식입니다.');
    }

    // 업로드 디렉토리 생성
    $uploadDir = 'user_files/' . $userId;
    $fullUploadDir = CRM_UPLOAD_PATH . '/' . $uploadDir;

    if (!is_dir($fullUploadDir)) {
        mkdir($fullUploadDir, 0755, true);
    }

    // 파일명 생성
    $newFileName = date('Ymd_His') . '_' . uniqid() . '.' . $ext;
    $uploadPath = $fullUploadDir . '/' . $newFileName;

    // 파일 이동
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        errorResponse('파일 저장에 실패했습니다.');
    }

    try {
        // DB에 저장
        $stmt = $pdo->prepare("INSERT INTO " . CRM_USER_FILES_TABLE . "
            (user_id, file_name, file_path, file_size, created_at)
            VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([
            $userId,
            $file['name'],
            $uploadDir . '/' . $newFileName,
            $file['size']
        ]);

        $newId = $pdo->lastInsertId();

        successResponse([
            'id' => $newId,
            'file_name' => $file['name'],
            'file_path' => $uploadDir . '/' . $newFileName,
            'file_size' => $file['size']
        ], '파일이 업로드되었습니다.');
    } catch (Exception $e) {
        // 업로드된 파일 삭제
        if (file_exists($uploadPath)) {
            unlink($uploadPath);
        }
        errorResponse('파일 정보 저장에 실패했습니다.');
    }
    exit;
}

errorResponse('지원하지 않는 메서드입니다.', 405);
