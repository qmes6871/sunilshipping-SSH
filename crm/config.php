<?php
/**
 * 선일쉬핑 CRM 설정 파일
 * 그누보드 연동 및 CRM 전용 설정
 */

// 에러 리포팅 (개발 중에만 활성화)
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// 타임존 설정
date_default_timezone_set('Asia/Seoul');

// CRM 경로 상수 (그누보드 로드 전에 정의)
if (!defined('CRM_PATH')) {
    define('CRM_PATH', __DIR__);
}
if (!defined('CRM_URL')) {
    define('CRM_URL', '/sunilshipping/crm');
}

// 그누보드 common.php 로드 (세션 포함) - 먼저 로드!
$g5_common_path = dirname(__DIR__) . '/gnuboard5/common.php';
if (file_exists($g5_common_path)) {
    include_once $g5_common_path;
}

// 그누보드가 로드되지 않았을 경우 직접 설정
if (!defined('_GNUBOARD_')) {
    define('_GNUBOARD_', true);

    // 세션 시작
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 그누보드 경로 (그누보드가 로드되지 않은 경우에만 정의)
    if (!defined('G5_PATH')) {
        define('G5_PATH', dirname(__DIR__) . '/gnuboard5');
    }
    if (!defined('G5_DATA_PATH')) {
        define('G5_DATA_PATH', G5_PATH . '/data');
    }
    if (!defined('G5_URL')) {
        define('G5_URL', '/sunilshipping/gnuboard5');
    }

    // DB 설정 로드
    $dbconfig_path = G5_DATA_PATH . '/dbconfig.php';
    if (file_exists($dbconfig_path)) {
        include_once $dbconfig_path;
    }
}

// DB 연결 정보 (그누보드 설정 사용 또는 직접 설정)
if (!defined('CRM_DB_HOST')) {
    define('CRM_DB_HOST', defined('G5_MYSQL_HOST') ? G5_MYSQL_HOST : 'localhost');
}
if (!defined('CRM_DB_USER')) {
    define('CRM_DB_USER', defined('G5_MYSQL_USER') ? G5_MYSQL_USER : 'sunilshipping');
}
if (!defined('CRM_DB_PASS')) {
    define('CRM_DB_PASS', defined('G5_MYSQL_PASSWORD') ? G5_MYSQL_PASSWORD : 'sunil123!');
}
if (!defined('CRM_DB_NAME')) {
    define('CRM_DB_NAME', defined('G5_MYSQL_DB') ? G5_MYSQL_DB : 'sunilshipping');
}
if (!defined('CRM_DB_CHARSET')) {
    define('CRM_DB_CHARSET', 'utf8mb4');
}

// 테이블 프리픽스
if (!defined('G5_TABLE_PREFIX')) {
    define('G5_TABLE_PREFIX', defined('G5_MYSQL_PREFIX') ? G5_MYSQL_PREFIX : 'g5_');
}
if (!defined('CRM_TABLE_PREFIX')) {
    define('CRM_TABLE_PREFIX', 'crm_');
}

// 그누보드 테이블
if (!defined('G5_MEMBER_TABLE')) {
    define('G5_MEMBER_TABLE', G5_TABLE_PREFIX . 'member');
}

// CRM 테이블
if (!defined('CRM_USERS_TABLE')) {
    define('CRM_USERS_TABLE', CRM_TABLE_PREFIX . 'users');
}
if (!defined('CRM_FILES_TABLE')) {
    define('CRM_FILES_TABLE', CRM_TABLE_PREFIX . 'files');
}
if (!defined('CRM_COMMENTS_TABLE')) {
    define('CRM_COMMENTS_TABLE', CRM_TABLE_PREFIX . 'comments');
}
if (!defined('CRM_NOTICES_TABLE')) {
    define('CRM_NOTICES_TABLE', CRM_TABLE_PREFIX . 'notices');
}
if (!defined('CRM_DEPT_NOTICES_TABLE')) {
    define('CRM_DEPT_NOTICES_TABLE', CRM_TABLE_PREFIX . 'dept_notices');
}
if (!defined('CRM_TODOS_TABLE')) {
    define('CRM_TODOS_TABLE', CRM_TABLE_PREFIX . 'todos');
}
if (!defined('CRM_MEETINGS_TABLE')) {
    define('CRM_MEETINGS_TABLE', CRM_TABLE_PREFIX . 'meetings');
}
if (!defined('CRM_MEETING_ATTENDEES_TABLE')) {
    define('CRM_MEETING_ATTENDEES_TABLE', CRM_TABLE_PREFIX . 'meeting_attendees');
}
if (!defined('CRM_KMS_TABLE')) {
    define('CRM_KMS_TABLE', CRM_TABLE_PREFIX . 'kms_documents');
}
if (!defined('CRM_PUSH_TABLE')) {
    define('CRM_PUSH_TABLE', CRM_TABLE_PREFIX . 'push_notifications');
}
if (!defined('CRM_ROUTES_TABLE')) {
    define('CRM_ROUTES_TABLE', CRM_TABLE_PREFIX . 'route_warnings');
}
if (!defined('CRM_USER_MEMOS_TABLE')) {
    define('CRM_USER_MEMOS_TABLE', CRM_TABLE_PREFIX . 'user_memos');
}
if (!defined('CRM_USER_FILES_TABLE')) {
    define('CRM_USER_FILES_TABLE', CRM_TABLE_PREFIX . 'user_files');
}
if (!defined('CRM_SETTINGS_TABLE')) {
    define('CRM_SETTINGS_TABLE', CRM_TABLE_PREFIX . 'settings');
}

// 국제물류 테이블
if (!defined('CRM_INTL_CUSTOMERS_TABLE')) {
    define('CRM_INTL_CUSTOMERS_TABLE', CRM_TABLE_PREFIX . 'intl_customers');
}
if (!defined('CRM_INTL_ACTIVITIES_TABLE')) {
    define('CRM_INTL_ACTIVITIES_TABLE', CRM_TABLE_PREFIX . 'intl_activities');
}
if (!defined('CRM_INTL_PERFORMANCE_TABLE')) {
    define('CRM_INTL_PERFORMANCE_TABLE', CRM_TABLE_PREFIX . 'intl_performance');
}
if (!defined('CRM_INTL_PERSONAL_PERFORMANCE_TABLE')) {
    define('CRM_INTL_PERSONAL_PERFORMANCE_TABLE', CRM_TABLE_PREFIX . 'intl_personal_performance');
}

// 농산물 테이블
if (!defined('CRM_AGRI_CUSTOMERS_TABLE')) {
    define('CRM_AGRI_CUSTOMERS_TABLE', CRM_TABLE_PREFIX . 'agri_customers');
}
if (!defined('CRM_AGRI_ACTIVITIES_TABLE')) {
    define('CRM_AGRI_ACTIVITIES_TABLE', CRM_TABLE_PREFIX . 'agri_activities');
}
if (!defined('CRM_AGRI_PERFORMANCE_TABLE')) {
    define('CRM_AGRI_PERFORMANCE_TABLE', CRM_TABLE_PREFIX . 'agri_performance');
}
if (!defined('CRM_AGRI_PERSONAL_PERFORMANCE_TABLE')) {
    define('CRM_AGRI_PERSONAL_PERFORMANCE_TABLE', CRM_TABLE_PREFIX . 'agri_personal_performance');
}

// 우드펠렛 테이블
if (!defined('CRM_PELLET_TRADERS_TABLE')) {
    define('CRM_PELLET_TRADERS_TABLE', CRM_TABLE_PREFIX . 'pellet_traders');
}
if (!defined('CRM_PELLET_PERFORMANCE_TABLE')) {
    define('CRM_PELLET_PERFORMANCE_TABLE', CRM_TABLE_PREFIX . 'pellet_performance');
}
if (!defined('CRM_PELLET_PERSONAL_PERFORMANCE_TABLE')) {
    define('CRM_PELLET_PERSONAL_PERFORMANCE_TABLE', CRM_TABLE_PREFIX . 'pellet_personal_performance');
}

// 업로드 경로
if (!defined('CRM_UPLOAD_PATH')) {
    define('CRM_UPLOAD_PATH', CRM_PATH . '/uploads');
}
if (!defined('CRM_UPLOAD_URL')) {
    define('CRM_UPLOAD_URL', CRM_URL . '/uploads');
}

// 파일 업로드 제한
if (!defined('CRM_MAX_UPLOAD_SIZE')) {
    define('CRM_MAX_UPLOAD_SIZE', 50 * 1024 * 1024); // 50MB
}
if (!defined('CRM_ALLOWED_IMAGE_TYPES')) {
    define('CRM_ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
}
if (!defined('CRM_ALLOWED_DOC_TYPES')) {
    define('CRM_ALLOWED_DOC_TYPES', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'hwp']);
}
if (!defined('CRM_ALLOWED_AUDIO_TYPES')) {
    define('CRM_ALLOWED_AUDIO_TYPES', ['mp3', 'wav', 'm4a', 'ogg', 'webm']);
}

// 부서 목록
if (!defined('CRM_DEPARTMENTS')) {
    define('CRM_DEPARTMENTS', [
        'logistics' => '물류사업부',
        'agricultural' => '농산물사업부',
        'pellet' => '우드펠렛사업부',
        'support' => '경영지원',
        'admin' => '관리자'
    ]);
}

// 직급 목록
if (!defined('CRM_POSITIONS')) {
    define('CRM_POSITIONS', [
        'staff' => '사원',
        'assistant' => '대리',
        'manager' => '과장',
        'deputy' => '차장',
        'director' => '부장',
        'executive' => '임원'
    ]);
}

// PDO 데이터베이스 연결
if (!function_exists('getDB')) {
    function getDB() {
        static $pdo = null;

        if ($pdo === null) {
            try {
                $dsn = "mysql:host=" . CRM_DB_HOST . ";dbname=" . CRM_DB_NAME . ";charset=" . CRM_DB_CHARSET;
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                $pdo = new PDO($dsn, CRM_DB_USER, CRM_DB_PASS, $options);
            } catch (PDOException $e) {
                error_log("CRM DB Connection Error: " . $e->getMessage());
                die("데이터베이스 연결에 실패했습니다.");
            }
        }

        return $pdo;
    }
}

// 공통 함수 로드
require_once CRM_PATH . '/common.php';
