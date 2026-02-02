<?php
// 에러 표시 (디버깅용)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once('db_config.php');

// 로그인 확인
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/login.php?redirect=auction/purchase.php?id=' . (intval($_GET['id'] ?? 0)));
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['username'] ?? $_SESSION['name'] ?? '익명';
$auction_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$error = '';
$conn = get_db_connection();

// 경매 상태 업데이트
$update_sql = "UPDATE auctions SET status = 'ended' WHERE status = 'active' AND end_time < NOW()";
$conn->query($update_sql);

// 입찰 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bid_amount'])) {
    $bid_amount = floatval($_POST['bid_amount']);
    
    // 경매 정보 확인
    $stmt = $conn->prepare("SELECT * FROM auctions WHERE id = ?");
    $stmt->bind_param("i", $auction_id);
    $stmt->execute();
    $auction = $stmt->get_result()->fetch_assoc();
    
    if (!$auction) {
        $error = "경매를 찾을 수 없습니다.";
    } elseif ($auction['status'] !== 'active') {
        $error = "경매가 종료되었습니다.";
    } elseif (strtotime($auction['end_time']) <= time()) {
        $error = "경매 시간이 만료되었습니다.";
    } else {
        // 현재 최고 입찰가 확인
        $current_max_bid = $auction['current_price'];
        
        // 입찰 단위 (기본 1000원)
        $bid_increment = 1000;
        
        // 입찰가 검증
        $min_bid = $current_max_bid + $bid_increment;
        
        if ($bid_amount < $min_bid) {
            $error = "입찰 금액은 최소 " . number_format($min_bid) . "원 이상이어야 합니다.";
        } else {
            // 트랜잭션 시작
            $conn->begin_transaction();
            
            try {
                // 입찰 저장
                $stmt = $conn->prepare("INSERT INTO auction_bids (auction_id, user_id, user_name, bid_amount) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iisd", $auction_id, $user_id, $user_name, $bid_amount);
                $stmt->execute();
                
                // 경매 정보 업데이트 (현재가, 최고 입찰자, 입찰 횟수)
                $stmt = $conn->prepare("UPDATE auctions SET current_price = ?, high_bidder_name = ?, bid_count = bid_count + 1 WHERE id = ?");
                $stmt->bind_param("dsi", $bid_amount, $user_name, $auction_id);
                $stmt->execute();
                
                $conn->commit();
                $message = "입찰이 성공적으로 완료되었습니다!";
            } catch (Exception $e) {
                $conn->rollback();
                $error = "입찰 처리 중 오류가 발생했습니다: " . $e->getMessage();
            }
        }
    }
}

// 경매 정보 가져오기
$stmt = $conn->prepare("SELECT * FROM auctions WHERE id = ?");
$stmt->bind_param("i", $auction_id);
$stmt->execute();
$auction = $stmt->get_result()->fetch_assoc();

if (!$auction) {
    die("경매를 찾을 수 없습니다. <a href='index.php'>경매 목록으로 돌아가기</a>");
}

// 현재 최고 입찰가
$current_max_bid = $auction['current_price'];

// 입찰 단위 (기본 1000원)
$bid_increment = 1000;

// 최소 입찰 금액
$min_bid = $current_max_bid + $bid_increment;

// 입찰 내역 가져오기 (최고가 순으로 정렬)
$stmt = $conn->prepare("
    SELECT * FROM auction_bids 
    WHERE auction_id = ? 
    ORDER BY bid_amount DESC, bid_date DESC 
    LIMIT 10
");
$stmt->bind_param("i", $auction_id);
$stmt->execute();
$result = $stmt->get_result();
$bid_history = [];
while ($row = $result->fetch_assoc()) {
    $bid_history[] = $row;
}

// 경매 상태 확인
$is_active = $auction['status'] === 'active' && strtotime($auction['end_time']) > time();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>입찰하기 - <?php echo htmlspecialchars($auction['title']); ?></title>
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
            max-width: 1000px;
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
        
        .auction-info {
            background: white;
            padding: 0;
            margin-bottom: 30px;
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
        
        .bid-form {
            background: #fff;
            padding: 25px;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            margin-bottom: 30px;
        }
        
        .bid-form h3 {
            color: #111;
            margin-bottom: 20px;
            font-size: 1.2em;
            font-weight: 600;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #111;
            font-weight: 600;
            font-size: 0.95em;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            font-size: 1em;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            transition: all 0.2s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #111;
        }
        
        .btn-bid {
            width: 100%;
            padding: 14px;
            background: #111;
            color: white;
            border: 1px solid #111;
            border-radius: 4px;
            font-size: 1em;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-bid:hover {
            background: #000;
        }
        
        .btn-bid:disabled {
            background: #d1d5db;
            border-color: #d1d5db;
            cursor: not-allowed;
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
        
        .bid-table tr:hover {
            background: #fafafa;
        }
        
        .message {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 4px;
            border: 1px solid;
            font-size: 0.95em;
        }
        
        .message.success {
            background: #f0fdf4;
            color: #166534;
            border-color: #86efac;
        }
        
        .message.error {
            background: #fef2f2;
            color: #991b1b;
            border-color: #fca5a5;
        }
        
        .timer {
            font-size: 1em;
            color: #dc2626;
            font-weight: 500;
        }
        
        .back-btn {
            display: inline-block;
            padding: 12px 32px;
            background: white;
            color: #111;
            text-decoration: none;
            border-radius: 4px;
            border: 1px solid #d1d5db;
            margin-top: 20px;
            transition: all 0.2s;
            font-weight: 500;
        }
        
        .back-btn:hover {
            border-color: #111;
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
                    <li><a href="/hotitem/">TRADE CAR</a></li>
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
            <h1>경매 입찰</h1>
            <p>원하는 금액으로 입찰하세요</p>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="auction-info">
                <h2><?php echo htmlspecialchars($auction['title']); ?></h2>
                <div class="info-row">
                    <span class="info-label">상태:</span>
                    <span class="info-value">
                        <span class="status-badge <?php echo $is_active ? 'status-active' : 'status-ended'; ?>">
                            <?php echo $is_active ? '진행중' : '종료됨'; ?>
                        </span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">시작가:</span>
                    <span class="info-value"><?php echo number_format($auction['start_price']); ?>원</span>
                </div>
                <div class="info-row">
                    <span class="info-label">현재 최고가:</span>
                    <span class="info-value price-highlight"><?php echo number_format($current_max_bid); ?>원</span>
                </div>
                <div class="info-row">
                    <span class="info-label">현재 입찰 수:</span>
                    <span class="info-value"><?php echo number_format($auction['bid_count']); ?>회</span>
                </div>
                <div class="info-row">
                    <span class="info-label">최소 입찰 금액:</span>
                    <span class="info-value"><?php echo number_format($min_bid); ?>원</span>
                </div>
                <div class="info-row">
                    <span class="info-label">입찰 단위:</span>
                    <span class="info-value"><?php echo number_format($bid_increment); ?>원</span>
                </div>
                <div class="info-row">
                    <span class="info-label">종료 일시:</span>
                    <span class="info-value timer" id="countdown">
                        <?php echo $auction['end_time']; ?>
                    </span>
                </div>
            </div>
            
            <?php if ($is_active): ?>
            <div class="bid-form">
                <h3>입찰하기</h3>
                <form method="POST" onsubmit="return confirmBid()">
                    <div class="form-group">
                        <label for="bid_amount">입찰 금액 (원)</label>
                        <input 
                            type="number" 
                            id="bid_amount" 
                            name="bid_amount" 
                            min="<?php echo $min_bid; ?>" 
                            step="<?php echo $bid_increment; ?>"
                            value="<?php echo $min_bid; ?>"
                            required
                        >
                        <small style="color: #666; display: block; margin-top: 5px;">
                            최소 입찰 금액: <?php echo number_format($min_bid); ?>원
                        </small>
                    </div>
                    <button type="submit" class="btn-bid">입찰하기</button>
                </form>
            </div>
            <?php else: ?>
            <div class="message error">
                이 경매는 종료되었습니다.
            </div>
            <?php endif; ?>
            
            <div class="bid-history">
                <h3>입찰 내역</h3>
                <?php if (!empty($bid_history)): ?>
                <table class="bid-table">
                    <thead>
                        <tr>
                            <th>순위</th>
                            <th>입찰자</th>
                            <th>입찰 금액</th>
                            <th>입찰 시간</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = 1;
                        foreach ($bid_history as $bid): 
                        ?>
                        <tr style="<?php echo $bid['user_id'] == $user_id ? 'background: #fff3cd;' : ''; ?>">
                            <td><?php echo $rank++; ?></td>
                            <td>
                                <?php 
                                if ($bid['user_id'] == $user_id) {
                                    echo htmlspecialchars($bid['user_name'] ?? '익명') . ' (나)';
                                } else {
                                    $username = $bid['user_name'] ?? '익명';
                                    echo htmlspecialchars(mb_substr($username, 0, 2)) . '***';
                                }
                                ?>
                            </td>
                            <td style="color: #111; font-weight: 600; font-size: 1em;">
                                <?php echo number_format($bid['bid_amount']); ?>원
                            </td>
                            <td><?php echo $bid['bid_date']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p style="text-align: center; color: #666; padding: 20px;">
                    아직 입찰 내역이 없습니다. 첫 입찰자가 되어보세요!
                </p>
                <?php endif; ?>
            </div>
            
            <a href="index.php" class="back-btn">← 경매 목록으로</a>
        </div>
    </div>
    
    <script>
        // 입찰 확인
        function confirmBid() {
            const amount = document.getElementById('bid_amount').value;
            return confirm(number_format(amount) + '원으로 입찰하시겠습니까?');
        }
        
        // 숫자 포맷팅
        function number_format(number) {
            return new Intl.NumberFormat('ko-KR').format(number);
        }
        
        // 카운트다운 타이머
        const endDate = new Date("<?php echo $auction['end_time']; ?>").getTime();
        
        function updateCountdown() {
            const now = new Date().getTime();
            const distance = endDate - now;
            
            if (distance < 0) {
                document.getElementById('countdown').innerHTML = "경매 종료";
                document.querySelector('.btn-bid')?.setAttribute('disabled', 'disabled');
                return;
            }
            
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            document.getElementById('countdown').innerHTML = 
                days + "일 " + hours + "시간 " + minutes + "분 " + seconds + "초 남음";
        }
        
        updateCountdown();
        setInterval(updateCountdown, 1000);
        
        // 입찰 금액 자동 조정
        const bidInput = document.getElementById('bid_amount');
        const increment = <?php echo $bid_increment; ?>;
        
        if (bidInput) {
            bidInput.addEventListener('input', function() {
                const value = parseInt(this.value);
                const min = parseInt(this.min);
                
                if (value < min) {
                    this.value = min;
                }
            });
        }
        
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

