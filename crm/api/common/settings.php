<?php
/**
 * CRM 설정 API
 */

require_once dirname(dirname(__DIR__)) . '/config.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => '로그인이 필요합니다.'], 401);
}

$pdo = getDB();

// 설정 테이블 생성 (없으면)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS " . CRM_SETTINGS_TABLE . " (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {
    // 테이블이 이미 존재하면 무시
}

// GET: 설정 조회
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $key = $_GET['key'] ?? null;

    try {
        if ($key) {
            $stmt = $pdo->prepare("SELECT setting_value FROM " . CRM_SETTINGS_TABLE . " WHERE setting_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch();
            successResponse(['value' => $result['setting_value'] ?? null]);
        } else {
            // 모든 설정 조회
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM " . CRM_SETTINGS_TABLE);
            $settings = [];
            while ($row = $stmt->fetch()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            successResponse($settings);
        }
    } catch (Exception $e) {
        errorResponse('설정 조회 중 오류가 발생했습니다.');
    }
    exit;
}

// POST: 설정 저장
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isAdmin()) {
        errorResponse('관리자 권한이 필요합니다.', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $key = $input['key'] ?? null;
    $value = $input['value'] ?? '';

    if (empty($key)) {
        errorResponse('설정 키가 필요합니다.');
    }

    try {
        // UPSERT (INSERT ON DUPLICATE KEY UPDATE)
        $stmt = $pdo->prepare("INSERT INTO " . CRM_SETTINGS_TABLE . " (setting_key, setting_value)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute([$key, $value]);
        successResponse(null, '설정이 저장되었습니다.');
    } catch (Exception $e) {
        errorResponse('설정 저장 중 오류가 발생했습니다: ' . $e->getMessage());
    }
    exit;
}

errorResponse('지원하지 않는 메서드입니다.', 405);
