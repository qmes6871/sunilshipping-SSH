<?php
/**
 * 데이터베이스 설정
 */

// 데이터베이스 설정
define('G5_MYSQL_HOST', 'localhost');
define('G5_MYSQL_USER', 'sunilshipping');
define('G5_MYSQL_PASSWORD', 'sunil123!');
define('G5_MYSQL_DB', 'sunilshipping');
define('G5_MYSQL_SET_MODE', true);

// 호환성을 위한 별칭
define('DB_HOST', G5_MYSQL_HOST);
define('DB_USER', G5_MYSQL_USER);
define('DB_PASS', G5_MYSQL_PASSWORD);
define('DB_NAME', G5_MYSQL_DB);
define('DB_CHARSET', 'utf8mb4');

/**
 * 데이터베이스 연결 함수
 */
function get_db_connection() {
    static $conn = null;
    
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            die("데이터베이스 연결 실패: " . $conn->connect_error);
        }
        
        $conn->set_charset(DB_CHARSET);
    }
    
    return $conn;
}

/**
 * 데이터베이스 테이블 생성 (초기 설정용)
 */
function create_auction_tables() {
    $conn = get_db_connection();
    
    // auctions 테이블
    $sql_auctions = "CREATE TABLE IF NOT EXISTS auctions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        manufacturer VARCHAR(100),
        model VARCHAR(100),
        year INT,
        mileage INT,
        transmission VARCHAR(50),
        fuel VARCHAR(50),
        accident VARCHAR(50),
        accident_detail TEXT,
        start_price DECIMAL(15, 2) NOT NULL,
        current_price DECIMAL(15, 2) NOT NULL,
        high_bidder_name VARCHAR(100),
        image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        end_time DATETIME NOT NULL,
        status VARCHAR(20) DEFAULT 'active',
        bid_count INT DEFAULT 0,
        INDEX idx_status (status),
        INDEX idx_end_time (end_time)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    // auction_bids 테이블
    $sql_bids = "CREATE TABLE IF NOT EXISTS auction_bids (
        id INT AUTO_INCREMENT PRIMARY KEY,
        auction_id INT NOT NULL,
        user_id INT NOT NULL,
        user_name VARCHAR(100),
        bid_amount DECIMAL(15, 2) NOT NULL,
        bid_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (auction_id) REFERENCES auctions(id) ON DELETE CASCADE,
        INDEX idx_auction_id (auction_id),
        INDEX idx_user_id (user_id),
        INDEX idx_bid_amount (bid_amount)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->query($sql_auctions);
    $conn->query($sql_bids);
    
    return true;
}
?>

