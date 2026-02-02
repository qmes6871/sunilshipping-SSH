<?php
// 에러 표시 (디버깅용)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// 알림 헬퍼 불러오기
require_once __DIR__ . '/../notice/notification_helper.php';

// 경매 데이터베이스 연결 (auction 전용)
try {
    $conn = new mysqli('localhost', 'sunilshipping', 'sunil123!', 'sunilshipping');
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    error_log('Auction DB connection failed: ' . $e->getMessage());
    die('데이터베이스 연결 실패');
}

// 로그인 상태 확인
$is_logged_in = isset($_SESSION['user_id']) && isset($_SESSION['username']);
$user_name = $is_logged_in ? $_SESSION['name'] : '';

// 안읽은 알림 개수 조회
$unread_count = 0;
if ($is_logged_in) {
    $unread_count = getUnreadNotificationCount($_SESSION['username']);
}

// 등급 조회 (로그인한 경우)
$user_grade = 'basic'; // 기본값
if ($is_logged_in) {
    try {
        $db_conn = new PDO(
            "mysql:host=localhost;dbname=sunilshipping;charset=utf8mb4",
            "sunilshipping",
            "sunil123!",
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );

        $stmt = $db_conn->prepare("SELECT grade FROM customer_management WHERE username = ? LIMIT 1");
        $stmt->execute([$_SESSION['username']]);
        $user_data = $stmt->fetch();
        if ($user_data && !empty($user_data['grade'])) {
            $user_grade = $user_data['grade'];
        }
    } catch (PDOException $e) {
        error_log('Grade fetch failed: ' . $e->getMessage());
        $user_grade = 'basic';
    }
}

// 등급별 색상 및 한글명 매핑
$grade_colors = [
    'basic' => '#6B7280',
    'silver' => '#C0C0C0',
    'gold' => '#FFD700',
    'vip' => '#8B5CF6',
    'vvip' => '#FFC107'
];

$grade_labels = [
    'basic' => 'BASIC',
    'silver' => 'SILVER',
    'gold' => 'GOLD',
    'vip' => 'VIP',
    'vvip' => 'VVIP'
];

$current_grade_color = $grade_colors[$user_grade] ?? $grade_colors['basic'];
$current_grade_label = $grade_labels[$user_grade] ?? $grade_labels['basic'];

// 경매 상태 업데이트
$update_sql = "UPDATE auctions SET status = 'ended' WHERE status = 'active' AND end_time < NOW()";
$conn->query($update_sql);

// 모든 경매 가져오기 (최신순)
$result = $conn->query("SELECT * FROM auctions ORDER BY created_at DESC");
$auctions = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $auctions[] = $row;
    }
}

// 활성 경매와 종료된 경매 분리
$active_auctions = array_filter($auctions, function($a) {
    return $a['status'] === 'active';
});
$ended_auctions = array_filter($auctions, function($a) {
    return $a['status'] !== 'active';
});
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>경매 목록 - AUCTION</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; 
            background: #fafafa; 
            line-height: 1.6; 
        }
        .header {
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }



        .logo {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1a202c;
            text-decoration: none;
            white-space: nowrap;
            letter-spacing: -0.5px;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 2rem;
            margin: 0;
            padding: 0;
            flex: 1;
            justify-content: center;
        }

        .nav-menu li a {
            color: #6b7280;
            text-decoration: none;
            font-weight: 400;
            font-size: 0.9rem;
            transition: color 0.3s ease;
            white-space: nowrap;
            letter-spacing: 0.3px;
        }

        .nav-menu li a:hover {
            color: #1a202c;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            color: #1a202c;
            font-weight: 400;
            font-size: 0.85rem;
        }

        .grade-badge {
            display: inline-block;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .grade-badge:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 400;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            white-space: nowrap;
            border: none;
            cursor: pointer;
            display: inline-block;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid #d1d5db;
            color: #6b7280;
        }

        .btn-outline:hover {
            background: #f9fafb;
            border-color: #9ca3af;
            color: #1a202c;
        }

        .btn-danger {
            background: #4b5563;
            color: white;
        }

        .btn-danger:hover {
            background: #374151;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        .mobile-menu-toggle {
            display: none;
        }

        .mobile-actions {
            display: none !important;
        }

        /* Notification Bell */
        .notification-bell {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: transparent;
            border: none;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .notification-bell:hover {
            background: #f3f4f6;
        }

        .notification-bell i {
            font-size: 1.2rem;
            color: #6b7280;
        }

        .notification-badge {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #ef4444;
            color: white;
            font-size: 0.65rem;
            font-weight: 700;
            min-width: 18px;
            height: 18px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }



        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 2rem;
            height: 70px;
            position: relative;
        }

        .auction-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
            margin-bottom: 48px;
        }
        .auction-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .auction-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .auction-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #e5e7eb;
        }
        .auction-body {
            padding: 20px;
        }
        .auction-title {
            font-size: 18px;
            font-weight: 600;
            color: #111;
            margin-bottom: 8px;
        }
        .auction-info {
            font-size: 14px;
            color: #666;
            margin-bottom: 12px;
        }
        .auction-price {
            font-size: 24px;
            font-weight: 700;
            color: #2563eb;
            margin-bottom: 8px;
        }
        .auction-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: #666;
        }
        .time-left {
            color: #ef4444;
            font-weight: 600;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-active {
            background: #dcfce7;
            color: #16a34a;
        }
        .status-ended {
            background: #fee;
            color: #dc2626;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: #fff;
            border-radius: 12px;
            color: #666;
        }
        .empty-state i {
            font-size: 48px;
            color: #d1d5db;
            margin-bottom: 16px;
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
        /* 모바일 메뉴 액션 버튼 */
        .mobile-nav-actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
        }

        .mobile-nav-actions .btn {
            width: 100%;
            text-align: center;
        }

        .mobile-nav-actions .grade-badge {
            margin-top: 0.5rem;
        }

        /* 컨테이너 및 페이지 헤더 */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 48px 24px;
        }

        .page-header {
            margin-bottom: 40px;
            text-align: center;
        }

        .page-title {
            font-size: 28px;
            font-weight: 600;
            color: #111;
            letter-spacing: -0.5px;
            margin-bottom: 12px;
        }

        .page-subtitle {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            margin-top: 12px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #111;
            margin-bottom: 20px;
        }

        /* 모바일 스타일 - 태블릿 */
        @media (max-width: 1024px) {
            .nav-menu {
                gap: 1.5rem;
            }

            .nav-menu li a {
                font-size: 0.85rem;
            }

            .nav-actions {
                gap: 0.6rem;
            }

            .btn {
                padding: 0.45rem 0.9rem;
                font-size: 0.8rem;
            }

            .user-info {
                font-size: 0.8rem;
                gap: 0.35rem;
            }
        }

        /* 모바일 스타일 - 스마트폰 */
        @media (max-width: 768px) {
            .nav-container {
                padding: 1rem;
            }

            .nav-menu,
            .nav-actions {
                display: none;
            }

            .mobile-actions {
                display: flex !important;
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
                    <li><a href="/tradecar/">TRADE CAR</a></li>
                    <li><a href="/auction/">AUCTION</a></li>
                    <li><a href="/mypage/">MYPAGE</a></li>
                </ul>
            </nav>

            <!-- 모바일 알림 + 햄버거 메뉴 -->
            <div style="display: none; align-items: center; gap: 0.75rem;" class="mobile-actions">
                <?php if ($is_logged_in): ?>
                    <!-- 모바일 알림 버튼 -->
                    <button class="notification-bell" onclick="window.location.href='../notice/notifications.php'">
                        <i class="fas fa-bell"></i>
                        <?php if ($unread_count > 0): ?>
                            <span class="notification-badge"><?= $unread_count > 99 ? '99+' : $unread_count ?></span>
                        <?php endif; ?>
                    </button>
                <?php endif; ?>

                <!-- 햄버거 메뉴 버튼 -->
                <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>

            <!-- 데스크톱 액션 버튼 -->
            <div class="nav-actions">
                <?php if ($is_logged_in): ?>
                    <!-- 알림 벨 아이콘 -->
                    <button class="notification-bell" onclick="window.location.href='../notice/notifications.php'">
                        <i class="fas fa-bell"></i>
                        <?php if ($unread_count > 0): ?>
                            <span class="notification-badge"><?= $unread_count > 99 ? '99+' : $unread_count ?></span>
                        <?php endif; ?>
                    </button>

                    <span class="user-info">
                        <i class="fas fa-user" style="font-size: 0.8rem;"></i>
                        <span><?= htmlspecialchars($user_name) ?></span>
                        <span class="grade-badge" style="background: <?= $current_grade_color ?>; color: white; padding: 0.25rem 0.65rem; border-radius: 10px; font-size: 0.7rem; font-weight: 600;">
                            <?= $current_grade_label ?>
                        </span>
                    </span>
                    <a href="../login/edit.php" class="btn btn-outline">회원정보수정</a>
                    <a href="../login/logout.php" class="btn btn-danger">로그아웃</a>
                <?php else: ?>
                    <a href="../login/login.php" class="btn btn-outline">로그인</a>
                    <a href="../login/signup.php" class="btn btn-primary">회원가입</a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 모바일 메뉴 -->
        <nav class="mobile-nav" id="mobileNav">
            <ul>
                <li><a href="../index.php" onclick="closeMobileMenu()">HOME</a></li>
                <li><a href="../reserve/index.php" onclick="closeMobileMenu()">LOGISTIC</a></li>
                <li><a href="../hotitem/index.php" onclick="closeMobileMenu()">TRADE CAR</a></li>
                <li><a href="../auction/index.php" onclick="closeMobileMenu()">AUCTION</a></li>
                <li><a href="../mypage/index.php" onclick="closeMobileMenu()">MYPAGE</a></li>
            </ul>

            <div class="mobile-nav-actions">
                <?php if ($is_logged_in): ?>
                    <div style="margin-bottom: 1rem; text-align: center;">
                        <div style="display: flex; align-items: center; justify-content: center; gap: 0.4rem; margin-bottom: 0.5rem;">
                            <i class="fas fa-user" style="font-size: 0.85rem; color: #6b7280;"></i>
                            <span style="color: #1a202c; font-weight: 400; font-size: 0.9rem;"><?= htmlspecialchars($user_name) ?></span>
                        </div>
                        <span class="grade-badge" style="background: <?= $current_grade_color ?>; color: white; padding: 0.35rem 0.9rem; border-radius: 10px; font-size: 0.75rem; font-weight: 600; display: inline-block;">
                            <?= $current_grade_label ?>
                        </span>
                    </div>
                    <a href="../login/edit.php" class="btn btn-outline">회원정보수정</a>
                    <a href="../login/logout.php" class="btn btn-danger">로그아웃</a>
                <?php else: ?>
                    <a href="../login/login.php" class="btn btn-outline">로그인</a>
                    <a href="../login/signup.php" class="btn btn-primary">회원가입</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">AUCTION</h1>
            <p class="page-subtitle">
                Save thousands on quality pre-owned vehicles through transparent auction pricing. Get dealer-grade cars at wholesale prices, verified and ready to drive.
            </p>
        </div>

        <?php if (!empty($active_auctions)): ?>
            <h2 class="section-title">Active Auctions</h2>
            <div class="auction-grid">
                <?php foreach ($active_auctions as $auction): ?>
                    <a href="view.php?id=<?= $auction['id'] ?>" class="auction-card">
                        <?php if ($auction['image']): ?>
                            <img src="../<?= htmlspecialchars($auction['image']) ?>" alt="<?= htmlspecialchars($auction['title']) ?>" class="auction-image" onerror="this.style.display='none'">
                        <?php else: ?>
                            <div class="auction-image"></div>
                        <?php endif; ?>
                        <div class="auction-body">
                            <h3 class="auction-title"><?= htmlspecialchars($auction['title']) ?></h3>
                            <div class="auction-info">
                                <?php if ($auction['manufacturer'] || $auction['model']): ?>
                                    <?= htmlspecialchars($auction['manufacturer']) ?> 
                                    <?= htmlspecialchars($auction['model']) ?>
                                    <?php if ($auction['year']): ?>
                                        | <?= $auction['year'] ?>년식
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <div class="auction-price">
                                <?= number_format($auction['current_price']) ?>원
                            </div>
                            <div class="auction-meta">
                                <span class="time-left countdown" data-endtime="<?= strtotime($auction['end_time']) ?>">
                                    <i class="far fa-clock"></i>
                                    <span class="countdown-text">계산 중...</span>
                                </span>
                                <span>
                                    <i class="fas fa-gavel"></i> <?= $auction['bid_count'] ?>회 입찰
                                </span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-gavel"></i>
                <p>진행 중인 경매가 없습니다.</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($ended_auctions)): ?>
            <h2 class="section-title">Ended Auctions</h2>
            <div class="auction-grid">
                <?php foreach ($ended_auctions as $auction): ?>
                    <a href="view.php?id=<?= $auction['id'] ?>" class="auction-card">
                        <?php if ($auction['image']): ?>
                            <img src="../<?= htmlspecialchars($auction['image']) ?>" alt="<?= htmlspecialchars($auction['title']) ?>" class="auction-image" onerror="this.style.display='none'">
                        <?php else: ?>
                            <div class="auction-image"></div>
                        <?php endif; ?>
                        <div class="auction-body">
                            <h3 class="auction-title">
                                <?= htmlspecialchars($auction['title']) ?>
                                <span class="status-badge status-ended">종료</span>
                            </h3>
                            <div class="auction-info">
                                <?php if ($auction['manufacturer'] || $auction['model']): ?>
                                    <?= htmlspecialchars($auction['manufacturer']) ?> 
                                    <?= htmlspecialchars($auction['model']) ?>
                                    <?php if ($auction['year']): ?>
                                        | <?= $auction['year'] ?>년식
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <div class="auction-price">
                                <?= number_format($auction['current_price']) ?>원
                            </div>
                            <div class="auction-meta">
                                <span>종료됨</span>
                                <span>
                                    <i class="fas fa-gavel"></i> <?= $auction['bid_count'] ?>회 입찰
                                </span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
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
    
        // 실시간 카운트다운 업데이트
        function updateCountdowns() {
            const countdowns = document.querySelectorAll('.countdown');
            
            countdowns.forEach(countdown => {
                const endTime = parseInt(countdown.dataset.endtime) * 1000; // 초를 밀리초로 변환
                const now = new Date().getTime();
                const distance = endTime - now;
                
                const textElement = countdown.querySelector('.countdown-text');
                
                if (distance < 0) {
                    textElement.textContent = '종료됨';
                    countdown.style.color = '#dc2626';
                    return;
                }
                
                // 시간 계산
                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                
                // 표시 형식 결정
                let displayText = '';
                
                if (days > 0) {
                    displayText = `${days}일 ${hours}시간 ${minutes}분 ${seconds}초`;
                } else if (hours > 0) {
                    displayText = `${hours}시간 ${minutes}분 ${seconds}초`;
                } else if (minutes > 0) {
                    displayText = `${minutes}분 ${seconds}초`;
                } else {
                    displayText = `${seconds}초`;
                    countdown.style.color = '#ef4444';
                    countdown.style.fontWeight = 'bold';
                }
                
                textElement.textContent = displayText;
            });
        }
        
        // 페이지 로드 시 즉시 실행
        updateCountdowns();
        
        // 1초마다 업데이트
        setInterval(updateCountdowns, 1000);
    </script>
</body>
</html>

