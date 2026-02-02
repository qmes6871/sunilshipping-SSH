<?php
// 에러 표시 (디버깅용)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once('db_config.php');

$auction_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

try {
    $conn = get_db_connection();
    
    // 데이터베이스 연결 확인
    if (!$conn) {
        die("데이터베이스 연결 실패");
    }
    
    // 경매 상태 업데이트 (종료된 경매 체크)
    $update_sql = "UPDATE auctions SET status = 'ended' WHERE status = 'active' AND end_time < NOW()";
    $conn->query($update_sql);
    
    // 디버깅: 전체 경매 개수 확인
    $count_result = $conn->query("SELECT COUNT(*) as total FROM auctions");
    $count_row = $count_result->fetch_assoc();
    $debug_total = $count_row['total'];
    
    // 경매 정보 가져오기
    $stmt = $conn->prepare("SELECT * FROM auctions WHERE id = ?");
    if (!$stmt) {
        die("쿼리 준비 실패: " . $conn->error);
    }
    
    $stmt->bind_param("i", $auction_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $auction = $result->fetch_assoc();
    
    if (!$auction) {
        // 디버깅 정보 출력
        echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; margin: 20px; border-radius: 10px;'>";
        echo "<h3>❌ 경매를 찾을 수 없습니다</h3>";
        echo "<p><strong>요청한 경매 ID:</strong> " . $auction_id . "</p>";
        echo "<p><strong>데이터베이스:</strong> " . DB_NAME . "</p>";
        echo "<p><strong>전체 경매 개수:</strong> " . $debug_total . "</p>";
        
        // 최근 경매 목록 표시
        $recent = $conn->query("SELECT id, title FROM auctions ORDER BY id DESC LIMIT 5");
        if ($recent && $recent->num_rows > 0) {
            echo "<p><strong>최근 경매 목록:</strong></p><ul>";
            while ($row = $recent->fetch_assoc()) {
                echo "<li><a href='view.php?id=" . $row['id'] . "'>ID: " . $row['id'] . " - " . htmlspecialchars($row['title']) . "</a></li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color: #dc3545;'>데이터베이스에 경매 데이터가 없습니다.</p>";
            echo "<p><a href='create.php' style='color: #007bff;'>새 경매 만들기</a></p>";
        }
        
        echo "<p><a href='index.php'>← 경매 목록으로 돌아가기</a></p>";
        echo "</div>";
        exit;
    }
    
} catch (Exception $e) {
    die("오류 발생: " . $e->getMessage());
}

// 입찰 내역 가져오기 (최고가 순으로 정렬)
$stmt = $conn->prepare("
    SELECT * FROM auction_bids 
    WHERE auction_id = ? 
    ORDER BY bid_amount DESC, bid_date DESC
");
$stmt->bind_param("i", $auction_id);
$stmt->execute();
$result = $stmt->get_result();
$bid_history = [];
while ($row = $result->fetch_assoc()) {
    $bid_history[] = $row;
}

// 현재 사용자 ID
$current_user_id = $_SESSION['user_id'] ?? null;

// 경매 상태 확인
$is_active = $auction['status'] === 'active' && strtotime($auction['end_time']) > time();

// 현재 최고 입찰가
$current_max_bid = $auction['current_price'];
$bid_increment = 1000;
$min_bid = $current_max_bid + $bid_increment;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($auction['title']); ?> - 경매 상세</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Malgun Gothic', sans-serif;
            background: #fafafa;
            min-height: 100vh;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
        }
        
        .header {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            height: 70px;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2563eb;
            text-decoration: none;
        }
        
        .nav-menu {
            display: flex;
            list-style: none;
            gap: 2.5rem;
        }
        
        .nav-menu a {
            color: #4b5563;
            text-decoration: none;
            font-weight: 500;
        }
        
        .nav-menu a:hover {
            color: #2563eb;
        }
        
        .page-header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 40px 30px;
        }
        
        .page-header h1 {
            font-size: 1.8em;
            margin-bottom: 5px;
            font-weight: 600;
            color: #111;
        }
        
        .page-header p {
            color: #666;
            font-size: 0.95em;
        }
        
        .content {
            padding: 30px;
        }
        
        .auction-detail {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 768px) {
            .auction-detail {
                grid-template-columns: 1fr;
            }
        }
        
        .auction-image {
            width: 100%;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
            background: #f9fafb;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 400px;
        }
        
        .auction-image img {
            width: 100%;
            height: auto;
            object-fit: cover;
        }
        
        .auction-image .no-image {
            color: #9ca3af;
            font-size: 1em;
        }
        
        .auction-info {
            background: white;
            padding: 0;
        }
        
        .auction-info h2 {
            color: #111;
            margin-bottom: 20px;
            font-size: 1.6em;
            font-weight: 600;
            padding-bottom: 15px;
            border-bottom: 2px solid #111;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 14px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 500;
            color: #666;
            font-size: 0.95em;
        }
        
        .info-value {
            color: #111;
            font-weight: 500;
            text-align: right;
        }
        
        .price-highlight {
            color: #111;
            font-size: 1.5em;
            font-weight: 600;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: 500;
            border: 1px solid;
        }
        
        .status-active {
            background: white;
            color: #16a34a;
            border-color: #16a34a;
        }
        
        .status-ended {
            background: white;
            color: #dc2626;
            border-color: #dc2626;
        }
        
        .description-section {
            background: #fff;
            padding: 25px;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            margin-bottom: 30px;
        }
        
        .description-section h3 {
            color: #111;
            margin-bottom: 15px;
            font-size: 1.2em;
            font-weight: 600;
        }
        
        .description-section p {
            color: #444;
            line-height: 1.7;
            white-space: pre-wrap;
            font-size: 0.95em;
        }
        
        .vehicle-specs {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1px;
            margin-bottom: 30px;
            background: #e5e7eb;
            border: 1px solid #e5e7eb;
        }
        
        .spec-item {
            background: white;
            padding: 20px;
            text-align: center;
        }
        
        .spec-label {
            color: #666;
            font-size: 0.85em;
            margin-bottom: 8px;
        }
        
        .spec-value {
            color: #111;
            font-weight: 600;
            font-size: 1em;
        }
        
        .bid-history {
            background: white;
            padding: 0;
            margin-bottom: 30px;
        }
        
        .bid-history h3 {
            color: #111;
            margin-bottom: 20px;
            font-size: 1.3em;
            font-weight: 600;
            padding-bottom: 15px;
            border-bottom: 2px solid #111;
        }
        
        .bid-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border: 1px solid #e5e7eb;
        }
        
        .bid-table th {
            background: #f9fafb;
            color: #111;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9em;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .bid-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 0.95em;
        }
        
        .bid-table tr:last-child td {
            border-bottom: none;
        }
        
        .bid-table tr:hover {
            background: #fafafa;
        }
        
        .bid-rank-1 {
            background: #fafafa !important;
            font-weight: 600;
        }
        
        .buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            padding-top: 20px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 32px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 1em;
            font-weight: 500;
            transition: all 0.2s;
            border: 1px solid;
            cursor: pointer;
        }
        
        .btn-primary {
            background: #111;
            color: white;
            border-color: #111;
        }
        
        .btn-primary:hover {
            background: #000;
            border-color: #000;
        }
        
        .btn-primary:disabled {
            background: #d1d5db;
            border-color: #d1d5db;
            cursor: not-allowed;
        }
        
        .btn-secondary {
            background: white;
            color: #111;
            border-color: #d1d5db;
        }
        
        .btn-secondary:hover {
            border-color: #111;
        }
        
        .timer {
            font-size: 1em;
            color: #dc2626;
            font-weight: 500;
        }
        
        .user-masked {
            color: #9ca3af;
        }
        
        .user-highlight {
            color: #111;
            font-weight: 600;
        }
        
        /* 모바일 메뉴 */
        .mobile-menu-toggle {
            display: none;
            flex-direction: column;
            cursor: pointer;
            padding: 0.5rem;
            background: none;
            border: none;
        }
        .mobile-menu-toggle span {
            width: 25px;
            height: 2px;
            background: #333;
            margin: 3px 0;
            transition: 0.3s;
            display: block;
        }
        .mobile-menu-toggle.active span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }
        .mobile-menu-toggle.active span:nth-child(2) {
            opacity: 0;
        }
        .mobile-menu-toggle.active span:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -6px);
        }
        .mobile-nav {
            display: none;
            position: fixed;
            top: 70px;
            left: 0;
            width: 100%;
            height: calc(100vh - 70px);
            background: #fff;
            overflow-y: auto;
            z-index: 999;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }
        .mobile-nav.active {
            display: block;
            transform: translateX(0);
        }
        .mobile-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .mobile-nav ul li a {
            display: block;
            padding: 1rem 1.5rem;
            color: #333;
            text-decoration: none;
            font-weight: 500;
            border-bottom: 1px solid #e5e7eb;
        }
        .mobile-nav ul li a:hover {
            background: #f9fafb;
            color: #2563eb;
        }
        
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }
            .mobile-menu-toggle {
                display: flex;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="nav-container">
            <a href="/" class="logo">SUNIL SHIPPING</a>
            
            <!-- 데스크톱 메뉴 -->
            <nav>
                <ul class="nav-menu">
                    <li><a href="/">HOME</a></li>
                    <li><a href="/reserve/">LOGISTIC</a></li>
                    <li><a href="/auction/" style="color:#2563eb;font-weight:700">AUCTION</a></li>
                    <li><a href="/tradecar/">TRADE CAR</a></li>
                    <li><a href="/mypage/">MYPAGE</a></li>
                </ul>
            </nav>
            
            <!-- 햄버거 메뉴 버튼 -->
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
        
        <!-- 모바일 메뉴 -->
        <nav class="mobile-nav" id="mobileNav">
            <ul>
                <li><a href="/" onclick="closeMobileMenu()">HOME</a></li>
                <li><a href="/reserve/" onclick="closeMobileMenu()">LOGISTIC</a></li>
                <li><a href="/auction/" onclick="closeMobileMenu()">AUCTION</a></li>
                <li><a href="/hotitem/" onclick="closeMobileMenu()">TRADE CAR</a></li>
                <li><a href="/mypage/" onclick="closeMobileMenu()">MYPAGE</a></li>
            </ul>
        </nav>
    </header>
    
    <div class="container">
        <div class="page-header">
            <h1>Auction Details</h1>
            <p>Check the details and place your bids</p>
        </div>
        
        <div class="content">
            <div class="auction-detail">
                <div class="auction-image">
                    <?php if (!empty($auction['image'])): ?>
                        <img src="../<?php echo htmlspecialchars($auction['image']); ?>" alt="<?php echo htmlspecialchars($auction['title']); ?>" onerror="this.parentElement.innerHTML='<div class=\'no-image\'>이미지를 불러올 수 없습니다</div>'">
                    <?php else: ?>
                        <div class="no-image">No image</div>
                    <?php endif; ?>
                </div>
                
                <div class="auction-info">
                    <h2><?php echo htmlspecialchars($auction['title']); ?></h2>
                    
                    <div class="info-row">
                        <span class="info-label">Status:</span>
                        <span class="info-value">
                            <span class="status-badge <?php echo $is_active ? 'status-active' : 'status-ended'; ?>">
                                <?php echo $is_active ? 'Active' : 'Ended'; ?>
                            </span>
                        </span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Starting price:</span>
                        <span class="info-value">$ <?php echo number_format($auction['start_price']); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Current highest bid:</span>
                        <span class="info-value price-highlight">$ <?php echo number_format($current_max_bid); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Number of bids:</span>
                        <span class="info-value"><?php echo number_format($auction['bid_count']); ?> times</span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Minimum bid:</span>
                        <span class="info-value">$ <?php echo number_format($min_bid); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">End time:</span>
                        <span class="info-value timer" id="countdown">
                            <?php echo $auction['end_time']; ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($auction['manufacturer']) || !empty($auction['model']) || !empty($auction['year'])): ?>
            <div class="vehicle-specs">
                <?php if (!empty($auction['manufacturer'])): ?>
                <div class="spec-item">
                    <div class="spec-label">Manufacturer</div>
                    <div class="spec-value"><?php echo htmlspecialchars($auction['manufacturer']); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($auction['model'])): ?>
                <div class="spec-item">
                    <div class="spec-label">Model</div>
                    <div class="spec-value"><?php echo htmlspecialchars($auction['model']); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($auction['year'])): ?>
                <div class="spec-item">
                    <div class="spec-label">Year</div>
                    <div class="spec-value"><?php echo htmlspecialchars($auction['year']); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($auction['mileage'])): ?>
                <div class="spec-item">
                    <div class="spec-label">Mileage</div>
                    <div class="spec-value"><?php echo number_format($auction['mileage']); ?>km</div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($auction['transmission'])): ?>
                <div class="spec-item">
                    <div class="spec-label">Transmission</div>
                    <div class="spec-value"><?php echo htmlspecialchars($auction['transmission']); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($auction['fuel'])): ?>
                <div class="spec-item">
                    <div class="spec-label">Fuel</div>
                    <div class="spec-value"><?php echo htmlspecialchars($auction['fuel']); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($auction['accident'])): ?>
                <div class="spec-item">
                    <div class="spec-label">Accident history</div>
                    <div class="spec-value"><?php echo htmlspecialchars($auction['accident']); ?></div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($auction['description'])): ?>
            <div class="description-section">
                <h3>Detailed Description</h3>
                <p><?php echo nl2br(htmlspecialchars($auction['description'])); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($auction['accident_detail'])): ?>
            <div class="description-section">
                <h3>Accident Details</h3>
                <p><?php echo nl2br(htmlspecialchars($auction['accident_detail'])); ?></p>
            </div>
            <?php endif; ?>
            
            <div class="bid-history">
                <h3>Bid History</h3>
                <?php if (!empty($bid_history)): ?>
                <table class="bid-table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Bidder</th>
                            <th>Bid Amount</th>
                            <th>Bid Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = 1;
                        foreach ($bid_history as $bid): 
                            $is_my_bid = ($current_user_id && $bid['user_id'] == $current_user_id);
                            $row_class = ($rank === 1) ? 'bid-rank-1' : '';
                        ?>
                        <tr class="<?php echo $row_class; ?>">
                            <td><strong><?php echo $rank++; ?>위</strong></td>
                            <td>
                                <?php 
                                if ($is_my_bid) {
                                    echo '<span class="user-highlight">' . htmlspecialchars($bid['user_name'] ?? '익명') . ' (나)</span>';
                                } else {
                                    // 입찰자 아이디를 ***로 마스킹 처리
                                    $username = $bid['user_name'] ?? '익명';
                                    $masked_name = mb_substr($username, 0, 2) . '***';
                                    echo '<span class="user-masked">' . htmlspecialchars($masked_name) . '</span>';
                                }
                                ?>
                            </td>
                            <td style="color: #111; font-weight: 600; font-size: 1em;">
                                <?php echo number_format($bid['bid_amount']); ?>$
                            </td>
                            <td><?php echo $bid['bid_date']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p style="text-align: center; color: #666; padding: 40px;">
                    No bids yet. Be the first bidder!
                </p>
                <?php endif; ?>
            </div>
            
            <div class="buttons">
                <?php if ($is_active): ?>
                    <?php if ($current_user_id): ?>
                    <a href="purchase.php?id=<?php echo $auction_id; ?>" class="btn btn-primary">Place Bid</a>
                    <?php else: ?>
                    <a href="../login/login.php?redirect=auction/view.php?id=<?php echo $auction_id; ?>" class="btn btn-primary">Login to Bid</a>
                    <?php endif; ?>
                <?php else: ?>
                    <button class="btn btn-primary" disabled>Auction Ended</button>
                <?php endif; ?>
                <a href="index.php" class="btn btn-secondary">← Back to List</a>
            </div>
        </div>
    </div>
    
    <script>
        // 카운트다운 타이머
        const endDate = new Date("<?php echo $auction['end_time']; ?>").getTime();
        
        function updateCountdown() {
            const now = new Date().getTime();
            const distance = endDate - now;
            
            if (distance < 0) {
                document.getElementById('countdown').innerHTML = "경매 종료";
                const bidBtn = document.querySelector('.btn-primary');
                if (bidBtn && !bidBtn.innerHTML.includes('목록')) {
                    bidBtn.setAttribute('disabled', 'disabled');
                    bidBtn.innerHTML = '경매 종료됨';
                }
                return;
            }
            
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            document.getElementById('countdown').innerHTML = 
                days + " days " + hours + " hours " + minutes + " minutes " + seconds + " seconds left";
        }
        
        updateCountdown();
        setInterval(updateCountdown, 1000);
        
        // 자동 새로고침 (1분마다 입찰 내역 업데이트)
        <?php if ($is_active): ?>
        setInterval(function() {
            location.reload();
        }, 60000); // 60초
        <?php endif; ?>
        
        // 모바일 메뉴 토글
        function toggleMobileMenu() {
            const mobileNav = document.getElementById('mobileNav');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            mobileNav.classList.toggle('active');
            toggle.classList.toggle('active');
        }

        function closeMobileMenu() {
            const mobileNav = document.getElementById('mobileNav');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            mobileNav.classList.remove('active');
            toggle.classList.remove('active');
        }

        // 윈도우 리사이즈 시 모바일 메뉴 처리
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                closeMobileMenu();
            }
        });
    </script>
</body>
</html>

