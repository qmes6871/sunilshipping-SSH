<?php
/**
 * 트래킹 메인 페이지 - CSS 분리 버전
 */
session_start();

// 알림 헬퍼 불러오기
require_once '../notice/notification_helper.php';
//require_once 'tracking_module.php'; // 트래킹 섹션 삭제로 불필요
require_once 'shipping_products_module.php';
require_once 'document_download_module.php';
//require_once 'schedule_module.php'; // 운항 스케줄 섹션 삭제로 불필요
require_once 'customer_info_module.php';
//require_once 'customer_document_upload_module.php';
require_once 'tradecar_module.php';
require_once 'facebook_module.php';
require_once 'booking_history_module.php';


// 로그인 상태 확인
$is_logged_in = isset($_SESSION['username']) && !empty($_SESSION['username']);

// 로그인하지 않은 경우 로그인 페이지로 리다이렉트
if (!$is_logged_in) {
    header('Location: ../login/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$customer_id = $_SESSION['username'];
$user_name = $_SESSION['name'] ?? $_SESSION['username'];

// 안읽은 알림 개수 조회
$unread_count = 0;
if ($is_logged_in) {
    $unread_count = getUnreadNotificationCount($_SESSION['username']);
}

// 데이터베이스 연결 및 등급 조회
$user_grade = 'basic'; // 기본값
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

    // booking_history_module에서 사용할 수 있도록 $pdo로도 할당
    $pdo = $conn;
    $GLOBALS['pdo'] = $pdo;

    // 로그인한 사용자의 등급 조회
    if ($is_logged_in) {
        $stmt = $conn->prepare("SELECT grade FROM customer_management WHERE username = ? LIMIT 1");
        $stmt->execute([$_SESSION['username']]);
        $user_data = $stmt->fetch();
        if ($user_data && !empty($user_data['grade'])) {
            $user_grade = $user_data['grade'];
        }
    }
} catch (PDOException $e) {
    error_log('Database connection or grade fetch failed: ' . $e->getMessage());
    $user_grade = 'basic';
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

// 고객 정보 조회
$customerInfo = getCustomerInfo($customer_id);

// 검색 필터 - 트래킹 모듈 비활성화로 주석처리
/*
$filters = [
    'cntr_no' => $_GET['cntr_no'] ?? '',
    'status' => $_GET['status'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'port_1' => $_GET['port_1'] ?? '',
    'port_2' => $_GET['port_2'] ?? ''
];

// 데이터 조회
$trackingData = getCustomerTrackingData($customer_id, $filters);
$stats = getCustomerTrackingStats($customer_id);
$dbTest = testDatabaseConnection();
*/
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>나의 컨테이너 트래킹 - SUNIL SHIPPING</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- 외부 CSS 파일 -->
    <link href="style/mypage.css" rel="stylesheet">
    <!-- 운항스케줄 스타일 비활성화
    <link href="style/schedule.css" rel="stylesheet">
    -->
    <link href="style/tracking.css" rel="stylesheet">
    <style>
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
        }class="nav-actions"


      /* Schedule wrapper box */
      .schedule-box {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
      }

      /* 섹션 패딩 */
      .section-padding {
        padding: 30px;
      }

      /* 미수금 섹션 */
      .unpaid-section {
        padding: 30px;
        margin: 20px 0;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
      }

      /* 모바일에서 패딩 조정 */
      @media (max-width: 1024px) {
        .section-padding,
        .unpaid-section,
        body > div[style*="padding: 30px"],
        .container > div[style*="padding: 30px"] {
          padding: 15px !important;
        }
      }

      @media (max-width: 768px) {
        .section-padding,
        .unpaid-section,
        body > div[style*="padding: 30px"],
        .container > div[style*="padding: 30px"] {
          padding: 10px !important;
        }
      }

      @media (max-width: 480px) {
        .section-padding,
        .unpaid-section,
        body > div[style*="padding: 30px"],
        .container > div[style*="padding: 30px"] {
          padding: 5px !important;
        }
      }
    </style>
</head>
<body>
    <!-- 헤더 -->
    <header class="header">
        <nav class="nav-container">
            <a href="https://sunilshipping.mycafe24.com/" class="logo">SUNIL SHIPPING</a>

            <!-- 데스크톱 메뉴 -->
            <ul class="nav-menu">
                <li><a href="../index.php">HOME</a></li>
                <li><a href="../reserve/index.php">LOGISTIC</a></li>
                <li><a href="../tradecar/index.php">TRADE CAR</a></li>
                <li><a href="../auction/index.php">AUCTION</a></li>
                <li><a href="../mypage/index.php" class="active">MY PAGE</a></li>
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
                    <a href="../login/edit.php" class="btn btn-outline">회원정보수정</a>
                    <a href="../login/logout.php" class="btn btn-danger">로그아웃</a>
                <?php else: ?>
                    <a href="../login/login.php" class="btn btn-outline">로그인</a>
                    <a href="../login/register.php" class="btn btn-primary">회원가입</a>
                <?php endif; ?>
            </div>
        </nav>

        <!-- 모바일 메뉴 -->
        <nav class="mobile-nav" id="mobileNav">
            <ul>
                <li><a href="../index.php#home" onclick="closeMobileMenu()">HOME</a></li>
                <!-- <li><a href="../schedule/index.php" onclick="closeMobileMenu()">운항스케줄</a></li> -->
                <li><a href="../reserve/index.php" onclick="closeMobileMenu()">LOGISTIC</a></li>
                <li><a href="../hotitem/index.php" onclick="closeMobileMenu()">TRADE CAR</a></li>
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
                    <a href="../login/edit.php" class="btn btn-outline">회원정보수정</a>
                    <a href="../login/logout.php" class="btn btn-danger">로그아웃</a>
                <?php else: ?>
                    <a href="../login/login.php" class="btn btn-outline">로그인</a>
                    <a href="../login/register.php" class="btn btn-primary">회원가입</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

        <!-- 고객 정보 섹션 -->
        <div class="section-padding">
        <?php echo displayCustomerInfoSection($customerInfo); ?>
        </div>

        <!-- 예약 내역 섹션 -->
        <div class="section-padding">
        <?php
        $customerEmail = $customerInfo['email'] ?? $_SESSION['username'] ?? '';
        if ($customerEmail) {
            echo displayBookingHistorySection($customerEmail);
        }
        ?>
        </div>

        <!-- 서류 업로드 섹션 -->
        <?php
        /*
        <div id="document-upload" style="padding: 30px;">
            <?php
            // 업로드 성공/실패 메시지 표시
            if (isset($_SESSION['upload_success'])) {
                echo '<div class="alert alert-success" style="background: #d1fae5; color: #065f46; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #10b981;">';
                echo '<i class="fas fa-check-circle"></i> ' . htmlspecialchars($_SESSION['upload_success']);
                echo '</div>';
                unset($_SESSION['upload_success']);
            }
            if (isset($_SESSION['upload_error'])) {
                echo '<div class="alert alert-error" style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #dc2626;">';
                echo '<i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($_SESSION['upload_error']);
                echo '</div>';
                unset($_SESSION['upload_error']);
            }

            echo displayDocumentUploadSection($customer_id);
            ?>
        </div>
        */
        ?>

        <!-- 고객 트래킹 모듈 -->
        <!-- <div style="padding: 30px; background: #f0f9ff; border: 2px solid #3b82f6; margin: 20px 0;"> -->
            <?php
            $trackingFile = __DIR__ . '/trace/customer_tracking_module.php';
            // 모듈을 바로 출력하고, 아래 디버그 블록은 주석 처리합니다.
            if (file_exists($trackingFile)) { include $trackingFile; }
            if (false) {
            echo '<div style="padding: 20px; background: #fff; border-radius: 8px; margin-bottom: 20px;">';
            echo '<h3 style="color: #1f2937; margin-bottom: 10px;">🚢 고객 트래킹 모듈</h3>';

            if (file_exists($trackingFile)) {
                $fileSize = filesize($trackingFile);
                echo '<p style="color: #059669; margin-bottom: 10px;">✓ 파일 존재: ' . htmlspecialchars($trackingFile) . '</p>';
                echo '<p style="color: #6b7280; margin-bottom: 15px; font-size: 14px;">파일 크기: ' . $fileSize . ' bytes</p>';

                if ($fileSize > 0) {
                    echo '<div style="border-top: 2px solid #e5e7eb; padding-top: 20px; margin-top: 15px;">';

                    // 파일 내용 미리보기 (처음 500자)
                    $content = file_get_contents($trackingFile);
                    echo '<details style="margin-bottom: 15px;"><summary style="cursor: pointer; color: #3b82f6; font-weight: 600;">파일 내용 미리보기 (처음 500자)</summary>';
                    echo '<pre style="background: #f9fafb; padding: 15px; border-radius: 6px; overflow: auto; font-size: 12px; margin-top: 10px;">' . htmlspecialchars(substr($content, 0, 500)) . '</pre>';
                    echo '</details>';

                    // 파일 실행
                    ob_start();
                    include $trackingFile;
                    $output = ob_get_clean();

                    if (empty($output)) {
                        echo '<div style="background: #fef3c7; padding: 15px; border-radius: 8px; color: #92400e;">';
                        echo '⚠️ 파일이 실행되었지만 출력이 없습니다. 파일이 함수만 정의하고 있거나, 직접 출력하는 코드가 없을 수 있습니다.';
                        echo '</div>';
                    } else {
                        echo $output;
                    }

                    echo '</div>';
                } else {
                    echo '<div style="background: #fee2e2; padding: 15px; border-radius: 8px; color: #991b1b;">';
                    echo '⚠️ 파일이 비어있습니다 (0 bytes)';
                    echo '</div>';
                }
            } else {
                echo '<p style="color: #dc2626;">✗ 파일을 찾을 수 없습니다: ' . htmlspecialchars($trackingFile) . '</p>';
            }
            echo '</div>';
            }
            ?>
        <!-- </div> -->

        <!-- 고객 서류 목록 모듈 -->
        <div style="margin: 20px 0;">
            <?php
            $docListFile = __DIR__ . '/trace/document_list_module.php';
            if (false) {
                // iframe로 렌더링하여 모듈 CSS/HTML을 격리
                echo '<iframe src="trace/document_list_module.php" style="width:100%; height:900px; border:0; background:#fff;" loading="lazy"></iframe>';
            } else {
            if (file_exists($docListFile)) {
                include $docListFile; // 모듈이 직접 출력
            } else {
                echo '<div style="background: #fee2e2; padding: 15px; border-radius: 8px; color: #991b1b;">문서를 찾을 수 없습니다: ' . htmlspecialchars($docListFile) . '</div>';
            }
            }
            ?>
        </div>

        <!-- 미수금 내역 모듈 -->
        <div class="unpaid-section">
            <h3 style="color:#1f2937; margin:0 0 10px;">미수금 내역</h3>
            <?php
            $unpaidFile = __DIR__ . '/trace/unpaid_list_module.php';
            if (file_exists($unpaidFile)) {
                include $unpaidFile;
            } else {
                echo '<div style="background: #fee2e2; padding: 15px; border-radius: 8px; color: #991b1b;">파일을 찾을 수 없습니다: ' . htmlspecialchars($unpaidFile) . '</div>';
            }
            ?>
        </div>

        <div class="container">
            <!-- 운항 상품 섹션 -->
            <div style="padding: 30px; background: #f0f9ff; margin: 20px 0; border: 2px solid #3b82f6;">
            <?php
            if (function_exists('displayShippingProductsSection')) {
                echo displayShippingProductsSection($is_logged_in);
            } else {
                echo '<div style="padding: 20px; background: #fee2e2; color: #991b1b; border-radius: 8px;">⚠️ displayShippingProductsSection 함수를 찾을 수 없습니다.</div>';
            }
            ?>
            </div>

            <!-- HOT ITEM 섹션 -->
            <div style="padding: 30px; background: #fef3c7; margin: 20px 0; border: 2px solid #f59e0b;">
            <?php
            if (function_exists('displayHotItemSectionSimple')) {
                echo displayHotItemSectionSimple(3);
            } else {
                echo '<div style="padding: 20px; background: #fee2e2; color: #991b1b; border-radius: 8px;">⚠️ displayHotItemSectionSimple 함수를 찾을 수 없습니다.</div>';
            }
            ?>
            </div>

            <!-- Facebook 섹션 -->
            <div style="padding: 30px; background: #ffffff; margin: 20px 0; border: 1px solid #e5e7eb;">
            <?php
            if (function_exists('print_facebook_plugin')) {
                echo '<h2 style="color: #1f2937; margin-bottom: 20px; text-align: center;"><i class="fab fa-facebook"></i> Facebook</h2>';
                print_facebook_plugin();
            } else {
                echo '<div style="padding: 20px; background: #fee2e2; color: #991b1b; border-radius: 8px;">⚠️ print_facebook_plugin 함수를 찾을 수 없습니다.</div>';
            }
            ?>
            </div>


        <!-- 푸터 -->
        <div class="footer">
            <p>
                <strong>SUNIL SHIPPING</strong> | 컨테이너 트래킹 시스템<br>
                문의: +82-51-1234-5678 | tracking@sunilshipping.net
            </p>
        </div>
    </div>

    <!-- JavaScript -->
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

        // 검색 폼 자동 포커스
        document.addEventListener('DOMContentLoaded', function() {
            const containerInput = document.querySelector('#cntr_no');
            if (containerInput && !containerInput.value) {
                containerInput.focus();
            }

            // 표 행 클릭 시 상세 페이지로 이동
            const clickableRows = document.querySelectorAll('.clickable-row');
            clickableRows.forEach(row => {
                row.addEventListener('click', function(e) {
                    // 서류 다운로드 버튼 클릭 시에는 이동하지 않음
                    if (e.target.closest('.doc-btn') || e.target.closest('a')) {
                        return;
                    }

                    const href = this.getAttribute('data-href');
                    if (href) {
                        window.location.href = href;
                    }
                });

                // 호버 효과 추가
                row.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = '#f0f9ff';
                });

                row.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = '';
                });
            });
        });

        // 엑셀 다운로드 함수
        function exportToExcel() {
            const table = document.getElementById('trackingTable');
            if (!table) {
                alert('다운로드할 데이터가 없습니다.');
                return;
            }

            // 테이블을 CSV 형식으로 변환
            let csv = '';
            const rows = table.querySelectorAll('tr');

            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].querySelectorAll('th, td');
                let row = [];

                for (let j = 0; j < cells.length; j++) {
                    let cellText = cells[j].innerText.trim();
                    // CSV에서 쉼표와 따옴표 처리
                    if (cellText.includes(',') || cellText.includes('"') || cellText.includes('\n')) {
                        cellText = '"' + cellText.replace(/"/g, '""') + '"';
                    }
                    row.push(cellText);
                }
                csv += row.join(',') + '\n';
            }

            // BOM 추가 (한글 인코딩을 위해)
            const BOM = '\uFEFF';
            const csvWithBOM = BOM + csv;

            // 파일 다운로드
            const blob = new Blob([csvWithBOM], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');

            if (link.download !== undefined) {
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', '컨테이너_트래킹_' + new Date().toISOString().slice(0,10) + '.csv');
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } else {
                alert('브라우저에서 파일 다운로드를 지원하지 않습니다.');
            }
        }

        // 인쇄 함수
        function printTable() {
            const table = document.getElementById('trackingTable');
            if (!table) {
                alert('인쇄할 데이터가 없습니다.');
                return;
            }

            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>컨테이너 트래킹 리포트</title>
                    <style>
                        body { font-family: 'Noto Sans KR', Arial, sans-serif; margin: 20px; }
                        h1 { color: #2563eb; margin-bottom: 20px; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f5f5f5; font-weight: bold; }
                        tr:nth-child(even) { background-color: #f9f9f9; }
                        .print-info { margin-bottom: 20px; font-size: 14px; color: #666; }
                    </style>
                </head>
                <body>
                    <h1>🚢 SUNIL SHIPPING - 컨테이너 트래킹 리포트</h1>
                    <div class="print-info">
                        <p>생성일: ${new Date().toLocaleString('ko-KR')}</p>
                        <p>고객: ${document.querySelector('.status-item strong').nextSibling.textContent.trim()}</p>
                    </div>
                    ${table.outerHTML}
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }
    </script>
    <script>
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

    <?php
    // Append modules at the very end of the page
    echo "\n<!-- Bottom Modules Section -->\n";
    echo '<div id="bottom-modules" style="margin: 30px 0;">';

    // Shipping products (render only if the function exists in the module)
    if (function_exists('displayShippingProductsSection')) {
        echo '<div style="padding: 30px; background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 8px; margin-bottom: 20px;">';
        echo displayShippingProductsSection();
        echo '</div>';
    }

    // Hot items (simple section)
    if (function_exists('displayHotItemSectionSimple')) {
        echo '<div style="padding: 30px; background: #fef3c7; border: 2px solid #f59e0b; border-radius: 8px;">';
        echo displayHotItemSectionSimple(3);
        echo '</div>';
    }

    // Facebook page plugin
    if (function_exists('print_facebook_plugin')) {
        echo '<div style="padding: 30px; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; margin-top: 20px;">';
        echo '<h2 style="color: #1f2937; margin-bottom: 20px; text-align: center;"><i class="fab fa-facebook"></i> Facebook</h2>';
        print_facebook_plugin();
        echo '</div>';
    }

    echo '</div>';
    ?>
</body>
</html>
