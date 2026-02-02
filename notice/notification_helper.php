<?php
/**
 * 알림 헬퍼 함수
 */

// 데이터베이스 설정
if (!defined('G5_MYSQL_HOST')) define('G5_MYSQL_HOST', 'localhost');
if (!defined('G5_MYSQL_USER')) define('G5_MYSQL_USER', 'sunilshipping');
if (!defined('G5_MYSQL_PASSWORD')) define('G5_MYSQL_PASSWORD', 'sunil123!');
if (!defined('G5_MYSQL_DB')) define('G5_MYSQL_DB', 'sunilshipping');

/**
 * 전체 회원에게 알림 생성
 */
function createNotificationForAllUsers($type, $title, $message, $link = null, $reference_id = null) {
    try {
        $pdo = new PDO(
            "mysql:host=" . G5_MYSQL_HOST . ";dbname=" . G5_MYSQL_DB . ";charset=utf8mb4",
            G5_MYSQL_USER,
            G5_MYSQL_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );

        // 1. 알림 생성
        $stmt = $pdo->prepare("
            INSERT INTO notifications (type, title, message, link, reference_id)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$type, $title, $message, $link, $reference_id]);
        $notification_id = $pdo->lastInsertId();

        // 2. 모든 활성 회원 조회
        $stmt = $pdo->prepare("
            SELECT username
            FROM customer_management
            WHERE status = 'active'
        ");
        $stmt->execute();
        $users = $stmt->fetchAll();

        // 3. 각 회원에게 알림 배정
        $stmt = $pdo->prepare("
            INSERT INTO user_notifications (notification_id, username)
            VALUES (?, ?)
        ");

        foreach ($users as $user) {
            $stmt->execute([$notification_id, $user['username']]);
        }

        return [
            'success' => true,
            'notification_id' => $notification_id,
            'users_count' => count($users)
        ];

    } catch (PDOException $e) {
        error_log('Notification creation failed: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * 사용자의 안읽은 알림 개수 조회
 */
function getUnreadNotificationCount($username) {
    try {
        $pdo = new PDO(
            "mysql:host=" . G5_MYSQL_HOST . ";dbname=" . G5_MYSQL_DB . ";charset=utf8mb4",
            G5_MYSQL_USER,
            G5_MYSQL_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );

        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM user_notifications
            WHERE username = ? AND is_read = 0
        ");
        $stmt->execute([$username]);
        $result = $stmt->fetch();

        return (int)$result['count'];

    } catch (PDOException $e) {
        error_log('Get unread count failed: ' . $e->getMessage());
        return 0;
    }
}

/**
 * 사용자의 알림 목록 조회
 */
function getUserNotifications($username, $limit = 10) {
    try {
        $pdo = new PDO(
            "mysql:host=" . G5_MYSQL_HOST . ";dbname=" . G5_MYSQL_DB . ";charset=utf8mb4",
            G5_MYSQL_USER,
            G5_MYSQL_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );

        $stmt = $pdo->prepare("
            SELECT
                n.id,
                n.type,
                n.title,
                n.message,
                n.link,
                n.reference_id,
                n.created_at,
                un.is_read,
                un.read_at
            FROM notifications n
            INNER JOIN user_notifications un ON n.id = un.notification_id
            WHERE un.username = ?
            ORDER BY n.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$username, $limit]);

        return $stmt->fetchAll();

    } catch (PDOException $e) {
        error_log('Get notifications failed: ' . $e->getMessage());
        return [];
    }
}

/**
 * 알림을 읽음으로 표시
 */
function markNotificationAsRead($notification_id, $username) {
    try {
        $pdo = new PDO(
            "mysql:host=" . G5_MYSQL_HOST . ";dbname=" . G5_MYSQL_DB . ";charset=utf8mb4",
            G5_MYSQL_USER,
            G5_MYSQL_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );

        $stmt = $pdo->prepare("
            UPDATE user_notifications
            SET is_read = 1, read_at = NOW()
            WHERE notification_id = ? AND username = ?
        ");
        $stmt->execute([$notification_id, $username]);

        return true;

    } catch (PDOException $e) {
        error_log('Mark as read failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * 모든 알림을 읽음으로 표시
 */
function markAllNotificationsAsRead($username) {
    try {
        $pdo = new PDO(
            "mysql:host=" . G5_MYSQL_HOST . ";dbname=" . G5_MYSQL_DB . ";charset=utf8mb4",
            G5_MYSQL_USER,
            G5_MYSQL_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );

        $stmt = $pdo->prepare("
            UPDATE user_notifications
            SET is_read = 1, read_at = NOW()
            WHERE username = ? AND is_read = 0
        ");
        $stmt->execute([$username]);

        return true;

    } catch (PDOException $e) {
        error_log('Mark all as read failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * notifications 테이블 생성 (존재하지 않는 경우)
 */
function createNotificationsTableIfNotExists() {
    try {
        $pdo = new PDO(
            "mysql:host=" . G5_MYSQL_HOST . ";dbname=" . G5_MYSQL_DB . ";charset=utf8mb4",
            G5_MYSQL_USER,
            G5_MYSQL_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );

        // notifications 테이블 생성
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                type VARCHAR(50) NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                link VARCHAR(255) DEFAULT NULL,
                reference_id VARCHAR(100) DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_type (type),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // user_notifications 테이블 생성
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                notification_id INT NOT NULL,
                username VARCHAR(50) NOT NULL,
                is_read TINYINT(1) DEFAULT 0,
                read_at DATETIME DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_notification_id (notification_id),
                INDEX idx_username (username),
                INDEX idx_is_read (is_read),
                UNIQUE KEY unique_user_notification (notification_id, username),
                FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        return true;

    } catch (PDOException $e) {
        error_log('Table creation failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * 단일 사용자에게 알림 생성 (테스트용)
 */
function createNotification($username, $title, $message, $type = 'system', $link = null) {
    try {
        $pdo = new PDO(
            "mysql:host=" . G5_MYSQL_HOST . ";dbname=" . G5_MYSQL_DB . ";charset=utf8mb4",
            G5_MYSQL_USER,
            G5_MYSQL_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );

        // 1. 알림 생성
        $stmt = $pdo->prepare("
            INSERT INTO notifications (type, title, message, link)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$type, $title, $message, $link]);
        $notification_id = $pdo->lastInsertId();

        // 2. 사용자에게 알림 배정
        $stmt = $pdo->prepare("
            INSERT INTO user_notifications (notification_id, username)
            VALUES (?, ?)
        ");
        $stmt->execute([$notification_id, $username]);

        return $notification_id;

    } catch (PDOException $e) {
        error_log('Notification creation failed: ' . $e->getMessage());
        return false;
    }
}
