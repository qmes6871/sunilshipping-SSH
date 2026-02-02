-- 알림 테이블 생성
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL COMMENT '알림 타입 (hotitem, tracking, etc)',
    title VARCHAR(255) NOT NULL COMMENT '알림 제목',
    message TEXT NOT NULL COMMENT '알림 내용',
    link VARCHAR(500) DEFAULT NULL COMMENT '알림 클릭 시 이동할 링크',
    reference_id INT DEFAULT NULL COMMENT '참조 ID (예: hotitem_id)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
    INDEX idx_type (type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='전체 알림 테이블';

-- 사용자별 알림 읽음 상태 테이블
CREATE TABLE IF NOT EXISTS user_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_id INT NOT NULL COMMENT '알림 ID',
    username VARCHAR(100) NOT NULL COMMENT '사용자 아이디',
    is_read TINYINT(1) DEFAULT 0 COMMENT '읽음 여부 (0: 안읽음, 1: 읽음)',
    read_at TIMESTAMP NULL DEFAULT NULL COMMENT '읽은 시간',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '알림 받은 시간',
    FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE,
    INDEX idx_username (username),
    INDEX idx_is_read (is_read),
    INDEX idx_notification_user (notification_id, username),
    UNIQUE KEY unique_notification_user (notification_id, username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='사용자별 알림 읽음 상태';
