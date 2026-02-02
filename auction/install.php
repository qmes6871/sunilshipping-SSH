<?php
/**
 * 경매 시스템 데이터베이스 테이블 설치 스크립트
 * 
 * 사용법: 브라우저에서 auction/install.php 접속
 */

require_once('db_config.php');

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>경매 시스템 설치</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Malgun Gothic', '맑은 고딕', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            max-width: 600px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
        }
        
        h1 {
            color: #667eea;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .info {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            color: #155724;
        }
        
        .error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            color: #721c24;
        }
        
        .config-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .config-info h3 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .config-item {
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .config-item:last-child {
            border-bottom: none;
        }
        
        .config-label {
            font-weight: bold;
            color: #666;
            display: inline-block;
            width: 120px;
        }



        
        
        .sql-code {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            overflow-x: auto;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚗 경매 시스템 설치</h1>
        
        <div class="config-info">
            <h3>현재 데이터베이스 설정</h3>
            <div class="config-item">
                <span class="config-label">호스트:</span>
                <span><?php echo DB_HOST; ?></span>
            </div>
            <div class="config-item">
                <span class="config-label">데이터베이스:</span>
                <span><?php echo DB_NAME; ?></span>
            </div>
            <div class="config-item">
                <span class="config-label">사용자:</span>
                <span><?php echo DB_USER; ?></span>
            </div>
            <div class="config-item">
                <span class="config-label">문자셋:</span>
                <span><?php echo DB_CHARSET; ?></span>
            </div>
        </div>
        
        <?php
        if (isset($_POST['install'])) {
            try {
                $conn = get_db_connection();
                
                // auctions 테이블 생성
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
                
                // auction_bids 테이블 생성
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
                
                echo '<div class="success">';
                echo '<strong>✅ 설치 완료!</strong><br>';
                echo '경매 시스템 테이블이 성공적으로 생성되었습니다.<br><br>';
                echo '<strong>생성된 테이블:</strong><br>';
                echo '- auctions (경매 정보)<br>';
                echo '- auction_bids (입찰 내역)';
                echo '</div>';
                
                echo '<a href="index.php" class="btn">경매 목록으로 이동</a>';
                
            } catch (Exception $e) {
                echo '<div class="error">';
                echo '<strong>❌ 설치 실패!</strong><br>';
                echo '오류 메시지: ' . htmlspecialchars($e->getMessage()) . '<br><br>';
                echo '<strong>해결 방법:</strong><br>';
                echo '1. db_config.php 파일의 데이터베이스 설정을 확인하세요.<br>';
                echo '2. 데이터베이스가 존재하는지 확인하세요.<br>';
                echo '3. 데이터베이스 사용자 권한을 확인하세요.';
                echo '</div>';
            }
        } else {
        ?>
        
        <div class="info">
            <strong>📋 설치 안내</strong><br><br>
            이 스크립트는 경매 시스템에 필요한 데이터베이스 테이블을 생성합니다.<br><br>
            <strong>생성될 테이블:</strong><br>
            1. <strong>auctions</strong> - 경매 상품 정보<br>
            2. <strong>auction_bids</strong> - 입찰 내역<br><br>
            <strong>주의사항:</strong><br>
            - 이미 테이블이 존재하는 경우 건너뜁니다.<br>
            - 데이터베이스 설정이 올바른지 확인하세요.
        </div>
        
        <form method="POST">
            <button type="submit" name="install" class="btn">📦 테이블 설치하기</button>
        </form>
        
        <div class="info" style="margin-top: 20px;">
            <strong>⚙️ 데이터베이스 설정 변경</strong><br>
            <code>auction/db_config.php</code> 파일을 수정하세요.
        </div>
        
        <?php } ?>
    </div>
</body>
</html>

