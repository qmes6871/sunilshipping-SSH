<?php
session_start();
require_once '../config.php';

// 알림 헬퍼 불러오기
require_once '../notice/notification_helper.php';

// 로그인 상태 확인
$is_logged_in = isset($_SESSION['username']) && !empty($_SESSION['username']);
$user_name = $_SESSION['name'] ?? $_SESSION['username'] ?? 'Guest';

// 안읽은 알림 개수 조회
$unread_count = 0;
if ($is_logged_in) {
    $unread_count = getUnreadNotificationCount($_SESSION['username']);
}

// 등급 조회
$user_grade = 'basic'; // 기본값
if ($is_logged_in) {
    try {
        $stmt = $conn->prepare("SELECT grade FROM customer_management WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $_SESSION['username']);
        $stmt->execute();
        $result_grade = $stmt->get_result();
        $user_data = $result_grade->fetch_assoc();
        if ($user_data && !empty($user_data['grade'])) {
            $user_grade = $user_data['grade'];
        }
        $stmt->close();
    } catch (Exception $e) {
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

$query = "SELECT * FROM hot_items WHERE is_active = 1 ORDER BY created_at DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOT ITEM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background: #fafafa; line-height: 1.6; }

        /* Header Styles */
        .header {
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0.75rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
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
            display:none;
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

        /* Mobile Styles */
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
                flex-direction: column;
                gap: 4px;
                background: none;
                border: none;
                cursor: pointer;
                padding: 0.5rem;
            }

            .mobile-menu-toggle span {
                display: block;
                width: 25px;
                height: 3px;
                background: #1a202c;
                border-radius: 2px;
                transition: all 0.3s ease;
            }

            .mobile-menu-toggle.active span:nth-child(1) {
                transform: rotate(45deg) translate(8px, 8px);
            }

            .mobile-menu-toggle.active span:nth-child(2) {
                opacity: 0;
            }

            .mobile-menu-toggle.active span:nth-child(3) {
                transform: rotate(-45deg) translate(8px, -8px);
            }

            .mobile-nav {
                display: none;
                background: #ffffff;
                padding: 1rem;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            }

            .mobile-nav.active {
                display: block;
            }

            .mobile-nav ul {
                list-style: none;
                padding: 0;
                margin: 0 0 1rem 0;
            }

            .mobile-nav ul li {
                border-bottom: 1px solid #e5e7eb;
            }

            .mobile-nav ul li a {
                display: block;
                padding: 0.75rem 0;
                color: #4a5568;
                text-decoration: none;
                font-weight: 400;
            }

            .mobile-nav-actions {
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
            }

            .mobile-nav-actions .btn {
                width: 100%;
                text-align: center;
            }

            .grade-badge {
                margin-top: 0.5rem;
            }
        }

        .container { max-width: 1200px; margin: 0 auto; padding: 48px 24px; }

        .page-header { margin-bottom: 40px; }
        .page-title { font-size: 28px; font-weight: 600; color: #111; letter-spacing: -0.5px; }

        .items-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 24px; }
        .item-card { background: #fff; border-radius: 12px; overflow: hidden; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; }
        .item-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }

        .item-image-wrapper { position: relative; width: 100%; padding-bottom: 100%; background: #f5f5f5; overflow: hidden; }
        .item-image { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; }

        .item-content { padding: 20px; }
        .item-category { color: #888; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; font-weight: 500; }
        .item-title { font-size: 15px; font-weight: 500; color: #111; margin-bottom: 8px; line-height: 1.4; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
        .item-description { color: #666; font-size: 12px; line-height: 1.5; margin-bottom: 16px; height: 36px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }

        .item-prices { margin-bottom: 16px; display: flex; align-items: baseline; gap: 6px; flex-wrap: wrap; }
        .original-price { text-decoration: line-through; color: #aaa; font-size: 12px; }
        .sale-price { color: #111; font-size: 18px; font-weight: 600; letter-spacing: -0.3px; }
        .discount { color: #ff4444; font-size: 12px; font-weight: 600; }

        .btn-inquiry { display: block; width: 100%; padding: 11px; background: #111; color: #fff; text-align: center; text-decoration: none; border-radius: 8px; font-size: 13px; font-weight: 500; transition: background 0.2s; border: none; }
        .btn-inquiry:hover { background: #333; }

        .no-items { text-align: center; padding: 120px 20px; color: #aaa; }
        .no-items h2 { font-size: 20px; font-weight: 500; margin-bottom: 12px; color: #666; }
        .no-items p { font-size: 14px; }

        @media (max-width: 768px) {
            .container { padding: 32px 16px; }
            .page-title { font-size: 24px; }
            .items-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 16px; }
            .item-content { padding: 16px; }
        }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav-container">
            <a href="../index.php" class="logo">SUNIL SHIPPING</a>

            <!-- 데스크톱 메뉴 -->
            <ul class="nav-menu">
                <li><a href="../index.php">HOME</a></li>
                <li><a href="../reserve/index.php">LOGISTIC</a></li>
                <li><a href="../tradecar/index.php">TRADE CAR</a></li>
                <li><a href="../auction/index.php">AUCTION</a></li>
                <li><a href="../mypage/index.php">MY PAGE</a></li>
            </ul>

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
                    <a href="../login/edit.php" class="btn btn-outline">Edit Profile</a>
                    <a href="../login/logout.php" class="btn btn-danger">Logout</a>
                <?php else: ?>
                    <a href="../login/login.php" class="btn btn-outline">Login</a>
                    <a href="../login/signup.php" class="btn btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>
        </nav>

        <!-- 모바일 메뉴 -->
        <nav class="mobile-nav" id="mobileNav">
            <ul>
                <li><a href="../index.php#home" onclick="closeMobileMenu()">HOME</a></li>
                <li><a href="../reserve/index.php" onclick="closeMobileMenu()">LOGISTIC</a></li>
                <li><a href="../tradecar/index.php" onclick="closeMobileMenu()">TRADE CAR</a></li>
                <li><a href="../auction/index.php" onclick="closeMobileMenu()">AUCTION</a></li>
                <li><a href="../mypage/index.php" onclick="closeMobileMenu()">MY PAGE</a></li>
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
                    <a href="../login/edit.php" class="btn btn-outline">Edit Profile</a>
                    <a href="../login/logout.php" class="btn btn-danger">Logout</a>
                <?php else: ?>
                    <a href="../login/login.php" class="btn btn-outline">Login</a>
                    <a href="../login/signup.php" class="btn btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <script>
        // 모바일 메뉴 토글 함수
        function toggleMobileMenu() {
            const mobileNav = document.getElementById('mobileNav');
            const toggle = document.querySelector('.mobile-menu-toggle');

            if (mobileNav.style.display === 'block') {
                mobileNav.style.display = 'none';
                toggle.classList.remove('active');
            } else {
                mobileNav.style.display = 'block';
                toggle.classList.add('active');
            }
        }

        // 모바일 메뉴 닫기 함수
        function closeMobileMenu() {
            const mobileNav = document.getElementById('mobileNav');
            const toggle = document.querySelector('.mobile-menu-toggle');

            mobileNav.style.display = 'none';
            toggle.classList.remove('active');
        }

        // 윈도우 리사이즈 시 모바일 메뉴 처리
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                closeMobileMenu();
            }
        });

        // 모바일 메뉴 외부 클릭 시 닫기
        document.addEventListener('click', function(event) {
            const mobileNav = document.getElementById('mobileNav');
            const toggle = document.querySelector('.mobile-menu-toggle');
            const header = document.querySelector('.header');

            if (!header.contains(event.target) && mobileNav.style.display === 'block') {
                closeMobileMenu();
            }
        });

        // Ensure mobile menu starts closed on initial load
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof closeMobileMenu === 'function') {
                closeMobileMenu();
            } else {
                const mobileNav = document.getElementById('mobileNav');
                if (mobileNav) mobileNav.style.display = 'none';
                const toggle = document.querySelector('.mobile-menu-toggle');
                if (toggle) toggle.classList.remove('active');
            }
        });
    </script>

    <div class="container">
        <div class="page-header" style="text-align: center;">
            <h1 class="page-title">TRADE CAR</h1>
            <p style="color: #666; font-size: 14px; margin-top: 12px; line-height: 1.6;">
                We deliver popular items fast at fair prices. If you're looking for something else, feel free to contact us anytime.
            </p>
        </div>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class="items-grid">
                <?php while ($item = $result->fetch_assoc()): ?>
                    <?php
                    $discount_rate = 0;
                    if ($item['original_price'] > 0 && $item['sale_price'] < $item['original_price']) {
                        $discount_rate = round((($item['original_price'] - $item['sale_price']) / $item['original_price']) * 100);
                    }
                    ?>
                    <div class="item-card">
                        <div class="item-image-wrapper">
                            <?php if (!empty($item['image_path'])): ?>
                                <?php
                                $image_url = $item['image_path'];
                                if (strpos($image_url, 'http') !== 0 && strpos($image_url, '/') !== 0) {
                                    $image_url = '/' . $image_url;
                                }
                                ?>
                                <img src="<?php echo htmlspecialchars($image_url); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="item-image" onerror="this.style.display='none'">
                            <?php endif; ?>
                        </div>

                        <div class="item-content">
                            <?php if (!empty($item['category'])): ?>
                                <div class="item-category"><?php echo htmlspecialchars($item['category']); ?></div>
                            <?php endif; ?>

                            <h3 class="item-title"><?php echo htmlspecialchars($item['title']); ?></h3>

                            <?php if (!empty($item['description'])): ?>
                                <p class="item-description"><?php echo htmlspecialchars($item['description']); ?></p>
                            <?php endif; ?>

                            <div class="item-prices">
                                <?php if ($item['original_price'] > $item['sale_price']): ?>
                                    <span class="original-price">$<?php echo number_format($item['original_price']); ?></span>
                                <?php endif; ?>
                                <span class="sale-price">$<?php echo number_format($item['sale_price']); ?></span>
                                <?php if ($discount_rate > 0): ?>
                                    <span class="discount"><?php echo $discount_rate; ?>%</span>
                                <?php endif; ?>
                            </div>

                            <a href="inquiry.php?item_id=<?php echo $item['id']; ?>" class="btn-inquiry">INQUIRY</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-items">
                <h2>No Hot Items Available</h2>
                <p>New items will be updated soon</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
