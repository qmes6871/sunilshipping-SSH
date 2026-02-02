<?php
session_start();

// 알림 헬퍼 불러오기
require_once 'notice/notification_helper.php';
// 모듈 불러오기 (products_slider.php 제거)
require_once 'banners_module.php';
require_once 'shipping_products_module.php';
//require_once 'tracking_report_module.php';
require_once 'hotitem_module.php';
require_once 'reviews_module.php';
require_once 'facebook_link_module.php';
require_once 'about_sunil_shipping_module.php';
//require_once 'promotion_module.php';
require_once 'consultant_module.php';

// 데이터베이스 연결 (config.php 또는 직접 연결)
try {
    $conn = new PDO(
        "mysql:host=localhost;dbname=sunilshipping;charset=utf8mb4",
        "sunilshipping",
        "sunil123!",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    } catch (PDOException $e) {
        error_log('Database connection failed: ' . $e->getMessage());
        $conn = null;
    }
    
    // 로그인 상태 확인
    $is_logged_in = isset($_SESSION['user_id']) && isset($_SESSION['username']);
    $user_name = $is_logged_in ? $_SESSION['name'] : '';
    $customer_id = $is_logged_in ? $_SESSION['username'] : null;
    $user_grade = 'basic'; // 기본값
    
    // 안읽은 알림 개수 조회
    $unread_count = 0;
    if ($is_logged_in) {
        $unread_count = getUnreadNotificationCount($_SESSION['username']);
    }
    
    // 로그인한 사용자의 등급 조회
    if ($is_logged_in && $conn) {
        try {
            $stmt = $conn->prepare("SELECT grade FROM customer_management WHERE username = ? LIMIT 1");
            $stmt->execute([$_SESSION['username']]);
            $user_data = $stmt->fetch();
            if ($user_data && !empty($user_data['grade'])) {
                $user_grade = $user_data['grade']; // silver, gold, vip, vvip
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
    ?>
    <!DOCTYPE html>
    <html lang="ko">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SUNIL SHIPPING - 전문 물류 서비스</title>
    
    <!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.css" />

    <!-- External CSS -->
<link href="css/style.css" rel="stylesheet">
    
    <!-- Header & Grade Badge Styles -->
    <style>
    /* Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Pretendard', 'Poppins', -apple-system, BlinkMacSystemFont, system-ui, sans-serif;
}

body {
    margin: 0 !important;
    padding: 0 !important;
    font-family: 'Pretendard', 'Poppins', -apple-system, BlinkMacSystemFont, system-ui, sans-serif;
}

.header + section {
    margin-top: 0 !important;
}

/* Header Styles */

section {
    position: relative;
    z-index: 1;
}

.header {
    border: 1px solid #C6C6C6;
    background: #ffffff;
    width: 1400px;
    margin: 0 auto;
    position: fixed; 
    border-radius: 50px;
    top: 10px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 1000;
       box-sizing: border-box;
}

.nav-container {
    max-width: 1400px;
    margin: 0 auto;
   padding: 20px 30px;
        border-radius: 50px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.5rem;
    height: 60px;
      box-sizing: border-box;
}

.nav-menu {
    display: flex;
    list-style: none;
    gap: 2rem;
    margin: 0;
    padding: 0;
    align-items: center;
}

.nav-menu li a {
    text-decoration: none;
    color: #121212;
    font-weight: 500;
    font-size: 18px;
    transition: all 0.2s;
}

.logo {
    display: block;
}

.logo img {
    width: 150px;
    height: auto;
    display: block;
}

.dropdown {
    position: relative;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    background: white;
    list-style: none;
    padding: 0.5rem 0;
    margin: 0.5rem 0 0 0;
    min-width: 150px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
}

.dropdown:hover .dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-menu li {
    padding: 0;
}

.dropdown-menu li a {
    display: block;
    padding: 0.75rem 1.5rem;
    color: #505050;
    font-size: 15px;
    transition: all 0.2s;
}

.dropdown-menu li a:hover {
    background: #f3f4f6;
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

.mobile-nav {
    display: none;
    background: #ffffff;
    padding: 1rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    width: 100%;
    z-index: 999;
}

.mobile-nav.active {
    display: block;
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

   /* 페이스북 숨김 처리 */
    #facebook-link {
        display: none !important;
    }

/* footer */

.footer {
    background: #111;color: #fff;   
    padding: 100px;     }

    .footer-content{
        display: flex;
        justify-content: space-between;
            align-items: center; 
        max-width: 1400px;
        margin: 0 auto 50px auto;
        flex-wrap: wrap;
        gap: 2rem;
    }
    
    .footer-title{
        color: #505050;
        font-size: 35px;
        font-weight: 700;
    }

    .footer-description{
        margin-top: 40px;
        font-size: 20px;
        line-height: 1.6;
    }

    .footer-bottom{
        border: none !important; 
        margin-top: 150px;
        padding: 0;
    }
    
    .footer-bottom p {
        margin: 0;
        font-size: 15px;
        color: #767676;
        font-weight: 400;
        
    }

    .company-info{
        font-size: 17px;
        color: #fff !important;
        font-weight: 500 !important;
        margin-bottom: 15px;
    }

    .footer-right{
        display: flex;
        gap: 70px;
    }

    .signup-button,
    .signin-button{
        border: none;
        background: transparent;
        color: #121212;
        font-weight: 500;
        font-size: 18px;
        text-decoration: none;
    }

.main-banner {
    width: 100%;
    max-width: 1920px;
    height: 900px;
    margin: 0 auto;
    overflow: hidden;
    background-image: url('images/main-banner.png');
    background-size: cover;
    background-position: center;
}


.main-banner img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.tracking-section {
    background: #fff;
    padding: 100px 0 200px 0;
}

.tracking-container {
    max-width: 1400px;
    margin: 0 auto;
    background: #fff;
}

.desk-only {
    display: inline;
}

    @media (max-width: 1024px) {
        .footer{
            padding: 40px 20px;
        }

    .main-banner {
        height: auto; 
        min-height: 600px;
        background-image: url('images/main-banner-2.png');
    }

        .desk-only {
    display: none;
}

 .footer-section h3 {
            margin-bottom: 0px !important;
 }

  .footer-bottom{
            margin-top: 80px;
  }
        .footer-section ul {
            display: flex;
            gap: 8px;
        }
        .footer-content {
            gap: 50px;
        }

        .footer-title {
            font-size: 28px;
        }

        .footer-description {
            font-size: 15px;
            margin-top: 10px;
        }

        .footer-right {
            flex-direction: column;
            gap: 10px;
        }

                    .header {
        width: calc(100% - 10px); 
        max-width: calc(100% - 10px);
        border-radius: 30px; 
    }

        .nav-container {
        padding: 15px 20px; 
        height: 60px; 
    }

        .nav-menu {
            gap: 1.5rem;
        }
        
        .nav-menu li a {
            font-size: 0.85rem;
        }
        
        .nav-actions {
            gap: 0.6rem;
            font-size: 13px !important;
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

    .footer-logo{
        width: 150px; margin-bottom: 40px;
    }

    .footer-info {
    font-weight: 400;
    font-size: 15px;
    margin-bottom: 10px;
}

.footer-info.no-margin {
    margin-bottom: 0;
}
    
    @media (max-width: 768px) {
    .main-banner {
        min-height: 400px;
        height: auto; 
        background-image: url('images/main-banner-3.png');
    }

        .footer-info {
        margin-bottom: 0;
    }

            .tracking-section {
        padding: 50px 20px 100px 20px;
    }

            .header {
        width: calc(100% - 10px); 
        border-radius: 30px; 
    }
    
    .footer-logo  {
        width: 150px;
    }
    .nav-container {
        padding: 10px;
        height: 60px; 
    }
        .logo img {
        width: 150px; 
        padding-left: 5px;
    }
    
        .nav-menu,
        .nav-actions {
            display: none;
        }
        
        .footer-logo {
            margin-bottom: 0px;
        }
        .mobile-actions {
            display: flex !important;
        }
        
        .mobile-menu-toggle {
            display: flex;
            flex-direction: column;
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
            font-weight: 500;
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
    </style>
    
    </head>
    <body>
    <!-- 헤더 -->
    <header class="header">
    <nav class="nav-container">
<a href="#" class="logo">
    <img src="images/sunil-logo-black.svg" alt="SUNIL SHIPPING Logo">
</a>
    
    <!-- 데스크톱 메뉴 -->
<ul class="nav-menu">
    <li><a href="#">HOME</a></li>              
    <li><a href="reserve/index.php">LOGISTIC</a></li>
    <li class="dropdown">
        <a href="#" class="dropdown-toggle">TRADE CAR</a>
        <ul class="dropdown-menu">
            <li><a href="tradecar/index.php">TRADE CAR</a></li>
            <li><a href="auction/index.php">AUCTION</a></li>
        </ul>
    </li>
    <li><a href="mypage/index.php">MY PAGE</a></li>
</ul>
    
    <!-- 모바일 알림 + 햄버거 메뉴 -->
    <div style="display: none; align-items: center; gap: 0.75rem;" class="mobile-actions">
    <?php if ($is_logged_in): ?>
        <!-- 모바일 알림 버튼 -->
        <button class="notification-bell" onclick="window.location.href='notice/notifications.php'">
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
                <button class="notification-bell" onclick="window.location.href='notice/notifications.php'">
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
                    <a href="login/edit.php" class="btn btn-outline">회원정보수정</a>
                    <a href="login/logout.php" class="btn btn-danger">로그아웃</a>
                    <?php else: ?>
                        <a href="login/login.php" class="signin-button">LOGIN</a>
                        <a href="login/signup.php" class="signup-button">JOIN</a>
                        <?php endif; ?>
                        </div>
                        </nav>
                        
                        <!-- 모바일 메뉴 -->
                        <nav class="mobile-nav" id="mobileNav">
                        <ul>
                        <li><a href="#home" onclick="closeMobileMenu()">HOME</a></li>
                        <!-- <li><a href="#products" onclick="closeMobileMenu()">상품</a></li> -->
                        <!-- <li><a href="tracing/index.php" onclick="return checkLoginForMenu(event, 'tracking')">트래킹</a></li> -->
                        <li><a href="reserve/index.php" onclick="closeMobileMenu()">LOGISTIC</a></li>
                        <li><a href="tradecar/index.php" onclick="closeMobileMenu()">TRADE CAR</a></li>
                        <li><a href="auction/index.php" onclick="closeMobileMenu()">AUCTION</a></li>
                        <!-- <li><a href="reserve/index.php" onclick="closeMobileMenu()">예약</a></li> -->
                        <li><a href="mypage/index.php" onclick="closeMobileMenu()">MY PAGE</a></li>
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
                            <a href="login/edit.php" class="btn btn-outline">회원정보수정</a>
                            <a href="login/logout.php" class="btn btn-danger">로그아웃</a>
                            <?php else: ?>
                                <a href="login/login.php" class="btn btn-outline">로그인</a>
                                <a href="login/signup.php" class="btn btn-primary">회원가입</a>
                                <?php endif; ?>
                                </div>
                                </nav>
                                </header>
                                
                                <?php
                                // 로그인 성공 메시지 표시
                                if (isset($_SESSION['login_success']) && $_SESSION['login_success']) {
                                    echo '<div id="loginSuccessAlert" style="position: fixed; top: 100px; right: 20px; z-index: 9999; background: #10b981; color: white; padding: 1rem 1.5rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
            <i class="fas fa-check-circle me-2"></i>로그인 성공! 환영합니다.
        </div>';
                                    unset($_SESSION['login_success']);
                                    echo '<script>setTimeout(() => {
            const alert = document.getElementById("loginSuccessAlert");
            if(alert) alert.style.display = "none";
        }, 3000);</script>';
                                }
                                
                                // 로그아웃 성공 메시지 표시
                                if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
                                    echo '<div id="logoutSuccessAlert" style="position: fixed; top: 100px; right: 20px; z-index: 9999; background: #3b82f6; color: white; padding: 1rem 1.5rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
            <i class="fas fa-sign-out-alt me-2"></i>로그아웃되었습니다.
        </div>';
                                    echo '<script>setTimeout(() => {
            const alert = document.getElementById("logoutSuccessAlert");
            if(alert) alert.style.display = "none";
        }, 3000);</script>';
                                }
                                ?>
                                
                                <!-- 동적 배너 슬라이더 -->
                                <!-- <?= displayBannerSlider($is_logged_in) ?> -->
<section class="main-banner">
</section>
                                <!-- 프로모션 섹션 -->
                                <?php // render_promotion_section() ?>
                                
                                <!-- HOT ITEM 섹션 -->
                                <?= displayHotItemsSection() ?>
                                
                                <!-- 운항 상품 섹션 (shipping_products_module.php 사용) -->
                                <?= displayShippingProductsSection($is_logged_in) ?>
                                
                                <!-- 트래킹 리포트 섹션 숨김 처리 -->
                                <?php // displayTrackingReportSection($is_logged_in, $customer_id) ?>
                                
                                <!-- 고객 트래킹 모듈 섹션 -->
                           <section class="tracking-section">
    <div class="tracking-container">
        <?php include 'tracking_module.php'; ?>
    </div>
</section>
                                
                                <!-- 고객 후기 섹션 -->
                                <?= displayReviewsSection(6) ?>
                                
                                
                                
                                <!-- 회사소개서 섹션 -->
                                <section style="background: white; padding: 3rem 0;">
                                <?php include 'company_info_module.php'; ?>
                                </section>
                                
                                <!-- 회사소개(About) 섹션 - 페이스북 링크 바로 위에 배치 -->
                                <?= displayAboutSunilShippingSection() ?>
                                
                                <?= render_consultant_section() ?>
                                
                                <!-- 푸터 -->
                                <?= displayFacebookLinkSection() ?>
                                
                                <footer class="footer">
                                <div class="footer-content">
                                <div class="footer-section">
                                <img src="images/sunil-logo.svg" alt="SUNIL SHIPPING" class='footer-logo'>
                                <h2 class="footer-title">A trusted logistics partner<br class="desk-only"/> connecting the world.</h2>
                                <p class="footer-description">With 15 years of experience and <br /> expertise, we provide the best service.</p>
                                </div>
                                
                                <div class="footer-right">
                                <div class="footer-section">
                                <h3 class="company-info">고객지원</h3>
                                <ul class="footer-links" style="color:#fff !important; ">
                                <li ><a href="tel:+82-10-8815-8333" style="color:#fff !important;  font-size: 15px;"> +82-10-8815-8333</a></li>
                                <li><a href="mailto:info@sunilshipping.net" style="color:#fff !important;  font-size: 15px;"> sjkim@cntrbulk.com</a></li>
                                <!-- <li><a href="#"><i class="fas fa-comments me-2"></i>온라인 상담</a></li> -->
                                <li><a href="/qna" style="color:#fff !important;  font-size: 15px;">Q&A</a></li>
                                </ul>
                                </div>
                                
                                <div class="footer-section">
                                <h3 class="company-info">회사정보</h3>
                         
<p class="footer-info">사업자등록번호 202-81-43701</p>
<p class="footer-info">대표이사 송병찬</p>
<p class="footer-info no-margin">주소 <br /> 인천광역시 연수구 능허대로 227-10 1Fㆍ3F</p>
                                <!-- <p>통신판매업신고: 2024-부산해운-0123</p> -->
                         
                                </div>
                            </div>
                                </div>
                                
                                <div class="footer-bottom">
                                <p>&copy; <?= date('Y') ?> SUNIL SHIPPING. All rights reserved.</p>
                                </div>
                                </footer>
                                
                                <!-- External JavaScript -->
                                <script src="js/script.js"></script>
                                
                                <!-- 동적 배너 슬라이더 JavaScript -->
                                <?= getBannerSliderScript() ?>
                                
                                <!-- 모바일 메뉴 토글 스크립트 -->
                                <script>
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
                                