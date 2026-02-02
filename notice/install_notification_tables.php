<?php
/**
 * 알림 시스템 데이터베이스 테이블 설치
 */

// 데이터베이스 설정
define('G5_MYSQL_HOST', 'localhost');
define('G5_MYSQL_USER', 'sunilshipping');
define('G5_MYSQL_PASSWORD', 'sunil123!');
define('G5_MYSQL_DB', 'sunilshipping');

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

    // 1. notifications 테이블 생성
    $sql1 = "
    CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(50) NOT NULL COMMENT '알림 타입',
        title VARCHAR(255) NOT NULL COMMENT '알림 제목',
        message TEXT NOT NULL COMMENT '알림 내용',
        link VARCHAR(500) NULL COMMENT '연결 링크',
        reference_id VARCHAR(100) NULL COMMENT '참조 ID',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_type (type),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    $pdo->exec($sql1);
    echo "✓ notifications 테이블이 생성되었습니다.<br>";

    // 2. user_notifications 테이블 생성
    $sql2 = "
    CREATE TABLE IF NOT EXISTS user_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        notification_id INT NOT NULL,
        username VARCHAR(100) NOT NULL COMMENT '사용자 ID',
        is_read TINYINT(1) DEFAULT 0 COMMENT '읽음 여부',
        read_at TIMESTAMP NULL COMMENT '읽은 시간',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE,
        INDEX idx_username (username),
        INDEX idx_is_read (is_read),
        INDEX idx_notification_user (notification_id, username),
        UNIQUE KEY unique_notification_user (notification_id, username)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    $pdo->exec($sql2);
    echo "✓ user_notifications 테이블이 생성되었습니다.<br>";

    echo "<br><strong>알림 시스템 테이블 설치가 완료되었습니다!</strong><br>";
    echo "<br><a href='test_notification.php'>테스트 페이지로 이동</a>";

} catch (PDOException $e) {
    echo "오류 발생: " . $e->getMessage();
}
?>
