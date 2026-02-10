<?php
/**
 * 선일쉬핑 CRM 공통 함수
 */

/**
 * 현재 로그인한 사용자 정보 가져오기
 */
function getCurrentUser() {
    global $member;

    // 그누보드 $member 변수 사용
    if (!isset($member['mb_id']) || !$member['mb_id']) {
        return null;
    }

    $pdo = getDB();
    $mb_id = $member['mb_id'];

    try {
        // 그누보드 회원 + CRM 사용자 정보 조인
        $sql = "SELECT m.mb_id, m.mb_name, m.mb_nick, m.mb_email, m.mb_level,
                       u.id as crm_user_id, u.department, u.position, u.phone, u.profile_photo, u.memo
                FROM " . G5_MEMBER_TABLE . " m
                LEFT JOIN " . CRM_USERS_TABLE . " u ON m.mb_id = u.mb_id
                WHERE m.mb_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$mb_id]);
        $user = $stmt->fetch();

        // 그누보드 정보 병합
        if ($user) {
            $user['mb_level'] = $member['mb_level'];
        }

        return $user;
    } catch (PDOException $e) {
        error_log("getCurrentUser Error: " . $e->getMessage());
        return null;
    }
}

/**
 * 로그인 체크
 */
function isLoggedIn() {
    global $member;
    return isset($member['mb_id']) && $member['mb_id'];
}

/**
 * 로그인 필수 체크 (리다이렉트)
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . G5_URL . '/bbs/login.php?url=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

/**
 * 관리자 권한 체크
 */
function isAdmin() {
    $user = getCurrentUser();
    // mb_level 2 이상이면 관리자로 인정 (기존: 10 이상)
    return $user && ($user['mb_level'] >= 2 || $user['department'] === 'admin');
}

/**
 * 부서별 권한 체크
 */
function hasDepartmentAccess($department) {
    $user = getCurrentUser();
    if (!$user) return false;
    if (isAdmin()) return true;
    return $user['department'] === $department;
}

/**
 * XSS 방지 출력
 */
function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * JSON 응답 반환
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 성공 응답
 */
function successResponse($data = null, $message = '성공') {
    jsonResponse([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
}

/**
 * 에러 응답
 */
function errorResponse($message = '오류가 발생했습니다.', $statusCode = 400) {
    jsonResponse([
        'success' => false,
        'message' => $message
    ], $statusCode);
}

/**
 * CSRF 토큰 생성
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF 토큰 검증
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * 파일 업로드 처리
 */
function uploadFile($file, $uploadDir, $allowedTypes = null) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'message' => '파일이 없습니다.'];
    }

    // 파일 크기 체크
    if ($file['size'] > CRM_MAX_UPLOAD_SIZE) {
        return ['success' => false, 'message' => '파일 크기가 너무 큽니다. (최대 50MB)'];
    }

    // 파일 확장자 체크
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $mimeType = $file['type'] ?? '';

    if ($allowedTypes === null) {
        $allowedTypes = array_merge(CRM_ALLOWED_IMAGE_TYPES, CRM_ALLOWED_DOC_TYPES, CRM_ALLOWED_AUDIO_TYPES);
    }

    // MIME 타입 배열인 경우 (image/jpeg 형식) 확장자 배열로 변환
    $isAllowed = false;
    if (!empty($allowedTypes)) {
        // MIME 타입인지 확인 (슬래시 포함)
        if (strpos($allowedTypes[0], '/') !== false) {
            // MIME 타입으로 체크
            $isAllowed = in_array($mimeType, $allowedTypes);
            // 또는 확장자로도 체크 (MIME 타입에서 확장자 추출)
            if (!$isAllowed) {
                $mimeToExt = [
                    'image/jpeg' => ['jpg', 'jpeg'],
                    'image/png' => ['png'],
                    'image/gif' => ['gif'],
                    'image/webp' => ['webp'],
                    'application/pdf' => ['pdf'],
                    'audio/mpeg' => ['mp3'],
                    'audio/wav' => ['wav'],
                    'audio/mp3' => ['mp3'],
                    'audio/webm' => ['webm'],
                    'audio/ogg' => ['ogg'],
                    'audio/m4a' => ['m4a']
                ];
                foreach ($allowedTypes as $mime) {
                    if (isset($mimeToExt[$mime]) && in_array($ext, $mimeToExt[$mime])) {
                        $isAllowed = true;
                        break;
                    }
                }
            }
        } else {
            // 확장자로 체크
            $isAllowed = in_array($ext, $allowedTypes);
        }
    } else {
        $isAllowed = true;
    }

    if (!$isAllowed) {
        return ['success' => false, 'message' => '허용되지 않는 파일 형식입니다.'];
    }

    // 업로드 디렉토리 확인
    $fullUploadDir = CRM_UPLOAD_PATH . '/' . $uploadDir;
    if (!is_dir($fullUploadDir)) {
        mkdir($fullUploadDir, 0777, true);
    }

    // 파일명 생성 (유니크)
    $newFileName = date('Ymd_His') . '_' . uniqid() . '.' . $ext;
    $uploadPath = $fullUploadDir . '/' . $newFileName;

    // 파일 이동
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        $filePath = $uploadDir . '/' . $newFileName;
        return [
            'success' => true,
            'file_name' => $file['name'],
            'original_name' => $file['name'],
            'stored_name' => $newFileName,
            'file_path' => $filePath,
            'path' => $filePath,
            'file_size' => $file['size'],
            'size' => $file['size'],
            'file_type' => $file['type']
        ];
    }

    return ['success' => false, 'message' => '파일 업로드에 실패했습니다.'];
}

/**
 * 파일 삭제
 */
function deleteFile($filePath) {
    $fullPath = CRM_UPLOAD_PATH . '/' . $filePath;
    if (file_exists($fullPath)) {
        return unlink($fullPath);
    }
    return false;
}

/**
 * 페이지네이션 계산
 */
function getPagination($totalCount, $currentPage = 1, $perPage = 20) {
    $totalPages = max(1, ceil($totalCount / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;

    return [
        'total_count' => $totalCount,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'per_page' => $perPage,
        'offset' => $offset,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}

/**
 * 날짜 포맷
 */
function formatDate($date, $format = 'Y-m-d') {
    if (empty($date)) return '';
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date($format, $timestamp);
}

/**
 * 날짜/시간 포맷
 */
function formatDateTime($datetime, $format = 'Y-m-d H:i') {
    return formatDate($datetime, $format);
}

/**
 * 상대 시간 표시
 */
function timeAgo($datetime) {
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) return '방금 전';
    if ($diff < 3600) return floor($diff / 60) . '분 전';
    if ($diff < 86400) return floor($diff / 3600) . '시간 전';
    if ($diff < 604800) return floor($diff / 86400) . '일 전';

    return formatDate($timestamp);
}

/**
 * 파일 크기 포맷
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * 숫자 포맷 (천 단위 콤마)
 */
function formatNumber($number, $decimals = 0) {
    return number_format($number, $decimals);
}

/**
 * 금액 포맷
 */
function formatMoney($amount, $currency = 'KRW') {
    $formatted = number_format($amount);
    return $currency . ' ' . $formatted;
}

/**
 * 입력값 정리
 */
function cleanInput($input) {
    if (is_array($input)) {
        return array_map('cleanInput', $input);
    }
    return trim(strip_tags($input));
}

/**
 * 로그 기록
 */
function writeLog($message, $type = 'info') {
    $logDir = CRM_PATH . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $logFile = $logDir . '/' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $user = getCurrentUser();
    $userId = $user ? $user['mb_id'] : 'guest';

    $logMessage = "[$timestamp] [$type] [$userId] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

/**
 * 활동 로그 기록
 */
function logActivity($entityType, $entityId, $action, $details = null) {
    $pdo = getDB();
    $user = getCurrentUser();

    try {
        $sql = "INSERT INTO " . CRM_TABLE_PREFIX . "activity_logs
                (entity_type, entity_id, action, details, user_id, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $entityType,
            $entityId,
            $action,
            $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
            $user ? $user['crm_user_id'] : null
        ]);
    } catch (PDOException $e) {
        error_log("logActivity Error: " . $e->getMessage());
    }
}

/**
 * 알림 발생
 */
function createNotification($userId, $title, $message, $link = null) {
    // 나중에 구현 (푸시 알림 연동)
    writeLog("Notification to user $userId: $title - $message", 'notification');
}

/**
 * 이메일 발송 (나중에 구현)
 */
function sendEmail($to, $subject, $body) {
    // 나중에 구현
    writeLog("Email to $to: $subject", 'email');
    return true;
}

/**
 * 부서명 가져오기
 */
function getDepartmentName($key) {
    return CRM_DEPARTMENTS[$key] ?? $key;
}

/**
 * 직급명 가져오기
 */
function getPositionName($key) {
    return CRM_POSITIONS[$key] ?? $key;
}

/**
 * 활동 유형 라벨
 */
function getActivityTypeLabel($type) {
    $labels = [
        'lead' => '리드',
        'contact' => '접촉',
        'proposal' => '제안',
        'negotiation' => '협상',
        'progress' => '진행',
        'completed' => '정산완료'
    ];
    return $labels[$type] ?? $type;
}

/**
 * 우선순위 라벨
 */
function getPriorityLabel($priority) {
    $labels = [
        'high' => '높음',
        'medium' => '보통',
        'low' => '낮음'
    ];
    return $labels[$priority] ?? $priority;
}

/**
 * 상태 라벨
 */
function getStatusLabel($status) {
    $labels = [
        'active' => '활성',
        'inactive' => '비활성',
        'pending' => '대기',
        'draft' => '임시저장',
        'scheduled' => '예약',
        'sent' => '발송완료',
        'failed' => '실패'
    ];
    return $labels[$status] ?? $status;
}

/**
 * 슬러그 생성
 */
function createSlug($text) {
    $text = preg_replace('/[^a-zA-Z0-9가-힣\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

/**
 * 랜덤 문자열 생성
 */
function generateRandomString($length = 16) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * 배열에서 특정 키만 추출
 */
function arrayOnly($array, $keys) {
    return array_intersect_key($array, array_flip($keys));
}

/**
 * 배열에서 특정 키 제외
 */
function arrayExcept($array, $keys) {
    return array_diff_key($array, array_flip($keys));
}

/**
 * 검색 키워드 하이라이트
 */
function highlightKeyword($text, $keyword) {
    if (empty($keyword)) return h($text);
    $pattern = '/(' . preg_quote($keyword, '/') . ')/i';
    return preg_replace($pattern, '<mark>$1</mark>', h($text));
}

/**
 * 설정값 가져오기
 */
function getSetting($key, $default = null) {
    static $cache = [];

    if (isset($cache[$key])) {
        return $cache[$key];
    }

    $pdo = getDB();
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM " . CRM_SETTINGS_TABLE . " WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        $value = $result ? $result['setting_value'] : $default;
        $cache[$key] = $value;
        return $value;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * 설정값 저장하기
 */
function setSetting($key, $value) {
    $pdo = getDB();
    try {
        $stmt = $pdo->prepare("INSERT INTO " . CRM_SETTINGS_TABLE . " (setting_key, setting_value)
            VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute([$key, $value]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * 국가 목록 가져오기 (국제물류용)
 */
function getIntlCountries() {
    $countries = getSetting('intl_countries');
    if ($countries) {
        return json_decode($countries, true) ?: [];
    }

    // 기본 국가 목록
    $defaultCountries = [
        '우즈베키스탄', '카자흐스탄', '키르기스스탄', '타지키스탄', '투르크메니스탄',
        '리비아', '알제리', '튀니지', '이집트', '모로코',
        '사우디아라비아', 'UAE', '요르단', '이라크', '쿠웨이트',
        '러시아', '독일', '프랑스', '영국', '폴란드',
        '기타'
    ];

    // 초기 데이터 저장
    setSetting('intl_countries', json_encode($defaultCountries, JSON_UNESCAPED_UNICODE));

    return $defaultCountries;
}

/**
 * 지역 목록 가져오기 (국제물류용 - 실적 차트용)
 */
function getIntlRegions() {
    $regions = getSetting('intl_regions');
    if ($regions) {
        return json_decode($regions, true) ?: [];
    }

    // 기본 지역 목록
    $defaultRegions = [
        '쿠잔트', '알마티', '타쉬켄트', '리비아', '알제리', '튀니지', '이집트', '모로코', '기타'
    ];

    // 초기 데이터 저장
    setSetting('intl_regions', json_encode($defaultRegions, JSON_UNESCAPED_UNICODE));

    return $defaultRegions;
}
