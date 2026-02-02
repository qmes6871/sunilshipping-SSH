<?php
/**
 * 고객 후기 목록 페이지
 */
session_start();

// 데이터베이스 연결 설정 파일 포함
require_once 'config.php';

// Check if database connection is successful
if ($conn === null) {
    die('
    <!DOCTYPE html>
    <html lang="ko">
    <head>
        <meta charset="UTF-8">
        <title>Database Error</title>
        <style>
            body { font-family: Arial; padding: 50px; text-align: center; }
            .error { background: #fee; border: 1px solid #fcc; padding: 20px; border-radius: 8px; max-width: 500px; margin: 0 auto; }
        </style>
    </head>
    <body>
        <div class="error">
            <h1>Database Connection Failed</h1>
            <p>Unable to connect to database.</p>
            <p><strong>DB:</strong> sunilshipping<br><strong>User:</strong> sunilshipping</p>
            <a href="../index.php">Go to Home</a>
        </div>
    </body>
    </html>
    ');
}

// reviews 테이블 생성 (존재하지 않을 경우)
$create_table_sql = "CREATE TABLE IF NOT EXISTS reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id VARCHAR(50) NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    service_type ENUM('shipping', 'customs', 'warehouse', 'consulting', 'other') DEFAULT 'shipping',
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    rating INT NOT NULL DEFAULT 5,
    views INT DEFAULT 0,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_customer (customer_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    $conn->exec($create_table_sql);
} catch (PDOException $e) {
    // 테이블이 이미 존재하면 무시
}

// 로그인 여부 확인
$is_logged_in = isset($_SESSION['username']) && !empty($_SESSION['username']);
$customer_id = $_SESSION['username'] ?? '';
$customer_name = $_SESSION['name'] ?? '';
$user_name = $_SESSION['name'] ?? $_SESSION['username'] ?? 'Guest';

// 페이지네이션 설정
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// 전체 후기 수 조회
$count_sql = "SELECT COUNT(*) as total FROM reviews WHERE status = 'approved'";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->execute();
$count_row = $count_stmt->fetch();
$total_reviews = $count_row['total'];
$total_pages = ceil($total_reviews / $per_page);

// 후기 목록 조회
$sql = "SELECT * FROM reviews
        WHERE status = 'approved'
        ORDER BY created_at DESC
        LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($sql);
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$reviews = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>고객 후기 - SUNIL SHIPPING</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans KR', Arial, sans-serif;
            background: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }

        /* 헤더 스타일 - index.php와 동일 */
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
            transition: color 0.2s;
        }

        .nav-menu a:hover {
            color: #2563eb;
        }

        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
        }

        .mobile-menu-toggle span {
            width: 25px;
            height: 3px;
            background: #4b5563;
            transition: 0.3s;
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

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-info {
            color: #6b7280;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .mobile-nav {
            display: none;
            background: white;
            border-top: 1px solid #e5e7eb;
            padding: 1rem;
        }

        .mobile-nav ul {
            list-style: none;
        }

        .mobile-nav li {
            margin-bottom: 1rem;
        }

        .mobile-nav a {
            color: #4b5563;
            text-decoration: none;
            font-weight: 500;
            display: block;
            padding: 0.5rem;
        }

        .mobile-nav-actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            background: white;
            padding: 30px 20px;
            border-bottom: 2px solid #e5e7eb;
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 1.8rem;
            margin-bottom: 8px;
            color: #1f2937;
        }

        .page-header p {
            font-size: 0.95rem;
            color: #6b7280;
        }

        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px 20px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
        }

        .stats {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #6b7280;
            font-size: 0.9rem;
        }

        .stat-item i {
            color: #6b7280;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
        }

        .btn-outline {
            background: white;
            color: #4b5563;
            border: 1px solid #d1d5db;
        }

        .btn-outline:hover {
            background: #f9fafb;
            border-color: #9ca3af;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .btn-danger {
            background: #dc2626;
            color: white;
            border: none;
        }

        .btn-danger:hover {
            background: #b91c1c;
        }

        .btn-secondary {
            background: white;
            color: #374151;
            border: 1px solid #d1d5db;
        }

        .btn-secondary:hover {
            background: #f9fafb;
        }

        .reviews-grid {
            display: grid;
            gap: 20px;
        }

        .review-card {
            background: white;
            padding: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            transition: all 0.2s;
            cursor: pointer;
        }

        .review-card:hover {
            border-color: #2563eb;
            background: #f9fafb;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .review-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .review-meta {
            display: flex;
            gap: 15px;
            font-size: 0.9rem;
            color: #6b7280;
            margin-bottom: 15px;
        }

        .review-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .rating {
            display: flex;
            gap: 3px;
        }

        .rating i {
            color: #fbbf24;
            font-size: 0.9rem;
        }

        .review-content {
            color: #4b5563;
            line-height: 1.8;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .review-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #f3f4f6;
        }

        .author-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .author-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 40px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 16px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            text-decoration: none;
            color: #374151;
            transition: all 0.3s;
        }

        .pagination a:hover {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
        }

        .pagination .current {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 8px;
        }

        .empty-state i {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #6b7280;
            margin-bottom: 10px;
        }

        .service-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
            background: #e0e7ff;
            color: #4f46e5;
        }

        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }

            .nav-actions {
                display: none;
            }

            .mobile-menu-toggle {
                display: flex;
            }

            .nav-container {
                padding: 0 16px;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }

            .action-bar {
                flex-direction: column;
                gap: 15px;
            }

            .stats {
                flex-direction: column;
                gap: 10px;
            }

            .review-header {
                flex-direction: column;
            }

            .review-meta {
                flex-direction: column;
                gap: 8px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav-container">
            <a href="../index.php" class="logo">SUNIL SHIPPING</a>

            <!-- 데스크톱 메뉴 -->
            <ul class="nav-menu">
                <li><a href="../index.php#home">홈</a></li>
                <li><a href="../reserve/index.php">LOGISTIC</a></li>
                <li><a href="../review/index.php">REVIEW</a></li>
                <li><a href="../mypage/index.php">MY PAGE</a></li>
            </ul>

            <!-- 햄버거 메뉴 버튼 -->
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <!-- 데스크톱 액션 버튼 -->
            <div class="nav-actions">
                <?php if ($is_logged_in): ?>
                    <span class="user-info">
                        <i class="fas fa-user"></i><?= htmlspecialchars($user_name) ?>
                    </span>
                    <a href="../login/edit.php" class="btn btn-outline">회원정보수정</a>
                    <a href="../login/logout.php" class="btn btn-danger">로그아웃</a>
                <?php else: ?>
                    <a href="../login/login.php" class="btn btn-outline">로그인</a>
                    <a href="../login/signup.php" class="btn btn-primary">회원가입</a>
                <?php endif; ?>
            </div>
        </nav>

        <!-- 모바일 메뉴 -->
        <nav class="mobile-nav" id="mobileNav">
            <ul>
                <li><a href="../index.php#home" onclick="closeMobileMenu()">홈</a></li>
                <li><a href="../hotitem/index.php" onclick="closeMobileMenu()">HOT ITEM</a></li>
                <li><a href="../review/index.php" onclick="closeMobileMenu()">REVIEW</a></li>
                <li><a href="../mypage/index.php" onclick="closeMobileMenu()">MY PAGE</a></li>
            </ul>

            <div class="mobile-nav-actions">
                <?php if ($is_logged_in): ?>
                    <div style="color: #4a5568; margin-bottom: 1rem; text-align: center;">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($user_name) ?>
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

    <script>
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
    </script>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-star"></i> Customer review</h1>
            <p>Customer reviews for SUNIL SHIPPING</p>
        </div>

        <div class="action-bar">
            <div class="stats">
                <div class="stat-item">
                    <i class="fas fa-comments"></i>
                    <span>Overall review <strong><?= number_format($total_reviews) ?></strong>inquiries</span>
                </div>
            </div>
            <div>
                <?php if ($is_logged_in): ?>
                    <a href="write.php" class="btn btn-primary">
                        <i class="fas fa-pen"></i> Write a Review
                    </a>
                <?php else: ?>
                    <a href="../login/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-secondary">
                        <i class="fas fa-sign-in-alt"></i> Write after logging in.
                    </a>
                <?php endif; ?>
                <a href="../mypage/index.php" class="btn btn-secondary">
                    <i class="fas fa-home"></i> MYPAGE
                </a>
            </div>
        </div>

        <?php if (empty($reviews)): ?>
            <div class="empty-state">
                <i class="fas fa-comments"></i>
                <h3>등록된 후기가 없습니다</h3>
                <p>첫 번째 후기를 작성해주세요!</p>
                <?php if ($is_logged_in): ?>
                    <a href="write.php" class="btn btn-primary" style="margin-top: 20px;">
                        <i class="fas fa-pen"></i> 후기 작성하기
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="reviews-grid">
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card" onclick="location.href='view.php?id=<?= $review['review_id'] ?>'">
                        <div class="review-header">
                            <div>
                                <div class="review-title"><?= htmlspecialchars($review['title']) ?></div>
                                <div class="review-meta">
                                    <span>
                                        <div class="rating">
                                            <?php for ($i = 0; $i < 5; $i++): ?>
                                                <i class="fas fa-star<?= $i < $review['rating'] ? '' : '-o' ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </span>
                                    <span><i class="fas fa-calendar"></i> <?= date('Y-m-d', strtotime($review['created_at'])) ?></span>
                                    <span><i class="fas fa-eye"></i> <?= number_format($review['views']) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="review-content">
                            <?= nl2br(htmlspecialchars($review['content'])) ?>
                        </div>

                        <div class="review-footer">
                            <div class="author-info">
                                <div class="author-avatar">
                                    <?= mb_substr($review['customer_name'], 0, 1) ?>
                                </div>
                                <div>
                                    <strong><?= htmlspecialchars($review['customer_name']) ?></strong>
                                    <div style="font-size: 0.85rem; color: #6b7280;">
                                        <?= htmlspecialchars($review['customer_id']) ?>
                                    </div>
                                </div>
                            </div>
                            <span class="service-badge">
                                <?php
                                $serviceTypes = [
                                    'shipping' => '해운 서비스',
                                    'customs' => '통관 서비스',
                                    'warehouse' => '창고 서비스',
                                    'consulting' => '컨설팅'
                                ];
                                echo $serviceTypes[$review['service_type']] ?? '기타';
                                ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- 페이지네이션 -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>"><i class="fas fa-chevron-left"></i> 이전</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?page=<?= $i ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>">다음 <i class="fas fa-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
