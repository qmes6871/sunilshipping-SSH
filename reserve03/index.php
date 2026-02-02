<?php

session_start();

// 로그인 필요: 미로그인 사용자는 로그인 페이지로 이동
if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
    $current = $_SERVER['REQUEST_URI'] ?? '/reserve/index.php';
    header('Location: /login/login.php?redirect=' . urlencode($current));
    exit;
}

// 알림 헬퍼 불러오기
require_once '../notice/notification_helper.php';

// 데이터베이스 연결
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
    $conn = null;
}

// 로그인 상태 확인 및 사용자 등급 조회
$is_logged_in = isset($_SESSION['username']) && !empty($_SESSION['username']);
$user_name = $_SESSION['name'] ?? $_SESSION['username'] ?? 'Guest';
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
        // 에러 시 기본값 유지
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

// 상품 데이터 조회
$products = [];
try {
    if ($conn) {
        $products = getShippingProducts(12, false);

        // 데이터베이스에 상품이 없으면 더미 데이터 추가
        if (empty($products)) {
            $products = createDummyProducts();
        }
    } else {
        // 데이터베이스 연결이 없으면 더미 데이터 사용
        $products = createDummyProducts();
    }
} catch (Exception $e) {
    $products = createDummyProducts();
}

// 더미 상품 데이터 생성 함수
function createDummyProducts() {
    return [
        [
            'id' => 1,
            'product_name' => '부산 → 상하이 직항',
            'departure_port' => '부산',
            'arrival_port' => '상하이',
            'vessel_name' => 'SUNIL EXPRESS',
            'ship_name' => 'SUNIL EXPRESS',
            'shipping_company' => 'SUNIL SHIPPING',
            'transit_time' => '2',
            'price' => 850.00,
            'price_vvip' => 750.00,
            'price_vip' => 800.00,
            'price_gold' => 820.00,
            'price_silver' => 840.00,
            'weekly_schedule' => '월, 수, 금',
            'description' => '부산-상하이 직항 서비스',
            'status' => 'active',
            'vessel_type' => '컨테이너선',
            'operator_company' => 'SUNIL SHIPPING',
            'available_slots' => 25,
            'is_featured' => 1,
            'image_url' => 'https://flagcdn.com/w320/cn.png',
            'staff_id' => null,
            'sub_staff_id_1' => null,
            'sub_staff_id_2' => null,
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 2,
            'product_name' => '인천 → 칭다오',
            'departure_port' => '인천',
            'arrival_port' => '칭다오',
            'vessel_name' => 'PACIFIC STAR',
            'ship_name' => 'PACIFIC STAR',
            'shipping_company' => 'SUNIL SHIPPING',
            'transit_time' => '1',
            'price' => 720.00,
            'price_vvip' => 620.00,
            'price_vip' => 670.00,
            'price_gold' => 690.00,
            'price_silver' => 710.00,
            'weekly_schedule' => '화, 목, 토',
            'description' => '인천-칭다오 고속 서비스',
            'status' => 'active',
            'vessel_type' => '고속 컨테이너선',
            'operator_company' => 'SUNIL SHIPPING',
            'available_slots' => 8,
            'is_featured' => 0,
            'image_url' => 'https://flagcdn.com/w320/cn.png',
            'staff_id' => null,
            'sub_staff_id_1' => null,
            'sub_staff_id_2' => null,
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 3,
            'product_name' => '부산 → 홍콩',
            'departure_port' => '부산',
            'arrival_port' => '홍콩',
            'vessel_name' => 'ASIA MARINE',
            'ship_name' => 'ASIA MARINE',
            'shipping_company' => 'SUNIL SHIPPING',
            'transit_time' => '3',
            'price' => 950.00,
            'price_vvip' => 850.00,
            'price_vip' => 900.00,
            'price_gold' => 920.00,
            'price_silver' => 940.00,
            'weekly_schedule' => '월, 목',
            'description' => '부산-홍콩 안정적 서비스',
            'status' => 'active',
            'vessel_type' => '컨테이너선',
            'operator_company' => 'SUNIL SHIPPING',
            'available_slots' => 0,
            'is_featured' => 1,
            'image_url' => 'https://flagcdn.com/w320/hk.png',
            'staff_id' => null,
            'sub_staff_id_1' => null,
            'sub_staff_id_2' => null,
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 4,
            'product_name' => '인천 → 상하이',
            'departure_port' => '인천',
            'arrival_port' => '상하이',
            'vessel_name' => 'KOREA CHINA',
            'ship_name' => 'KOREA CHINA',
            'shipping_company' => 'SUNIL SHIPPING',
            'transit_time' => '2',
            'price' => 800.00,
            'price_vvip' => 700.00,
            'price_vip' => 750.00,
            'price_gold' => 770.00,
            'price_silver' => 790.00,
            'weekly_schedule' => '월, 수, 금',
            'description' => '인천-상하이 정기 서비스',
            'status' => 'active',
            'vessel_type' => '컨테이너선',
            'operator_company' => 'SUNIL SHIPPING',
            'available_slots' => 15,
            'is_featured' => 0,
            'image_url' => 'https://flagcdn.com/w320/cn.png',
            'staff_id' => null,
            'sub_staff_id_1' => null,
            'sub_staff_id_2' => null,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ];
}

/**
 * 상품 관련 기능 모듈
 * products.php
 */

// 상품 데이터 조회 함수
function getShippingProducts($limit = 12, $featured_only = false) {
    global $conn;

    if (!$conn) {
        return [];
    }

    try {
        // helpers: fetch column list
        $colsStmt = $conn->query("SHOW COLUMNS FROM shipping_products");
        $existing = [];
        foreach ($colsStmt->fetchAll(PDO::FETCH_ASSOC) as $c) { $existing[$c['Field']] = true; }

        // dynamic select parts based on existing columns
        $select = [];
        $want = ['id','product_name','departure_port','arrival_port','transit_time','price','price_vvip','price_vip','price_gold','price_silver','weekly_schedule','departure_time','description','vessel_type','operator_company','available_slots','is_featured','staff_id','sub_staff_id_1','sub_staff_id_2','created_at','updated_at','image_url','nationality','route','transit_time_detailed','weight','cargo_type','payment_terms','rate','valid_until','additional_info'];
        foreach ($want as $w) { if (isset($existing[$w])) { $select[] = $w; } }

        // vessel_name alias from any available source columns
        $vesselSources = array_values(array_filter(['vessel_name'=>isset($existing['vessel_name'])?'vessel_name':null, 'ship_name'=>isset($existing['ship_name'])?'ship_name':null, 'shipping_company'=>isset($existing['shipping_company'])?'shipping_company':null]));
        if (!empty($vesselSources)) {
            $select[] = 'COALESCE(' . implode(', ', $vesselSources) . ') as vessel_name';
        } else {
            $select[] = 'NULL as vessel_name';
        }

        if (empty($select)) { $select[] = 'id'; }

        $where = [];
        if (isset($existing['status'])) { $where[] = "status = 'active'"; }
        if (!empty($featured_only) && isset($existing['is_featured'])) { $where[] = 'is_featured = 1'; }

        // Derive nationality from session and filter only if column exists
        $nationalityParam = null;
        if (session_status() === PHP_SESSION_NONE) { @session_start(); }
        $usernameForNat = $_SESSION['username'] ?? null;
        if (!empty($usernameForNat) && isset($existing['nationality'])) {
            try {
                $stmtNat = $conn->prepare("SELECT nationality FROM customer_management WHERE username = ? LIMIT 1");
                $stmtNat->execute([$usernameForNat]);
                $natRow = $stmtNat->fetch();
                if ($natRow && !empty($natRow['nationality'])) { $nationalityParam = $natRow['nationality']; }
            } catch (Exception $ign) {}
        }
        if (!empty($nationalityParam) && isset($existing['nationality'])) { $where[] = 'nationality = :nationality'; }

        $where_clause = 'WHERE ' . (empty($where) ? '1=1' : implode(' AND ', $where));

        // order by
        $order = [];
        if (isset($existing['is_featured'])) { $order[] = 'is_featured DESC'; }
        if (isset($existing['created_at'])) { $order[] = 'created_at DESC'; }
        elseif (isset($existing['id'])) { $order[] = 'id DESC'; }
        $orderBy = 'ORDER BY ' . (empty($order) ? '1' : implode(', ', $order));

        $sql = 'SELECT ' . implode(', ', $select) . ' FROM shipping_products ' . $where_clause . ' ' . $orderBy;
        if ($limit > 0) { $sql .= ' LIMIT :limit'; }

        $stmt = $conn->prepare($sql);
        if ($limit > 0) { $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT); }
        if (!empty($nationalityParam) && isset($existing['nationality'])) { $stmt->bindValue(':nationality', $nationalityParam, PDO::PARAM_STR); }

        $stmt->execute();
        return $stmt->fetchAll();

    } catch (PDOException $e) {
        return [];
    }
}

// 특정 상품 조회
function getProductById($product_id) {
    global $conn;

    if (!$conn) {
        return null;
    }

    try {
        $stmt = $conn->prepare("SELECT * FROM shipping_products WHERE id = ? AND status = 'active'");
        $stmt->execute([$product_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

// 가용성 상태 확인 함수
function getAvailabilityStatus($available_slots = 100) {
    if ($available_slots === null || $available_slots === '') {
        $available_slots = 100;
    }

    if ($available_slots <= 0) {
        return [
            'status' => 'full',
            'class' => 'status-full',
            'text' => '예약마감',
            'icon' => 'fas fa-times',
            'color' => '#dc2626'
        ];
    } elseif ($available_slots <= 10) {
        return [
            'status' => 'shortage',
            'class' => 'status-shortage',
            'text' => '잔여 ' . $available_slots . '석',
            'icon' => 'fas fa-exclamation-triangle',
            'color' => '#f59e0b'
        ];
    } elseif ($available_slots <= 30) {
        return [
            'status' => 'adjusting',
            'class' => 'status-adjusting',
            'text' => '예약가능',
            'icon' => 'fas fa-check-circle',
            'color' => '#059669'
        ];
    } else {
        return [
            'status' => 'available',
            'class' => 'status-available',
            'text' => '여유있음',
            'icon' => 'fas fa-check-circle',
            'color' => '#10b981'
        ];
    }
}

// 가격 포맷팅 함수
function formatCurrency($price, $currency = 'USD') {
    if ($currency === 'KRW') {
        return number_format($price, 0) . '원';
    }
    return '$' . number_format($price, 2);
}

// 상품 카드 렌더링 함수
function renderProductCard($product, $is_logged_in = false, $card_style = 'default') {
    // available_slots 사용 (없으면 기본값 100)
    $available_slots = $product['available_slots'] ?? 100;
    $availability = getAvailabilityStatus($available_slots);

    if ($card_style === 'ship') {
        return renderShipCard($product, $availability, $is_logged_in);
    } else {
        return renderDefaultCard($product, $availability, $is_logged_in);
    }
}

// 기본 카드 스타일 렌더링
function renderDefaultCard($product, $availability, $is_logged_in) {
    global $user_grade;
    ob_start();

    // 이미지 처리
    $img = trim($product['image_url'] ?? '');
    if (!empty($img) && substr($img, 0, 1) !== '/' && substr($img, 0, 4) !== 'http') {
        $img = '/' . $img;
    }
    $hasImage = !empty($img);

    // 가격 정보
    $user_price = $is_logged_in ? getUserGradePrice($product, $user_grade) : ($product['price'] ?? 0);
    $show_price = $is_logged_in ? formatCurrency($user_price) : maskPrice($product['price'] ?? 0);

    // 출발시간 정보 (departure_time 컬럼 사용)
    $departure_time = $product['departure_time'] ?? ($product['weekly_schedule'] ?? '문의');
    
    // 남은좌석 정보
    $available_slots = $product['available_slots'] ?? 100;
    
    // 남은시간 계산 (예시)
    $remaining_time = '11일 00:13:49'; // 실제로는 계산 필요

    ?>
    <div class="product-card-simple">
        <!-- 이미지 영역 -->
        <div class="card-image-area">
            <?php if ($hasImage): ?>
                <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($product['product_name']) ?>" class="card-flag-image">
                <!-- 배지 -->
                <?php if ($product['is_featured'] ?? false): ?>
                    <div class="card-badge">Very Fast & Very Cheap</div>
                <?php endif; ?>
            <?php else: ?>
                <div class="card-no-image">
                    <i class="fas fa-ship"></i>
                </div>
            <?php endif; ?>
        </div>

        <!-- 카드 내용 -->
        <div class="card-content">
            <!-- 항로 -->
            <div class="card-route">
                <?= htmlspecialchars($product['departure_port']) ?>
                <span style="margin: 0 8px; color: #6b7280;">→</span>
                <?= htmlspecialchars($product['arrival_port']) ?>
            </div>

            <!-- 정보 리스트 -->
            <div class="card-info-list">
                <div class="info-row">
                    <span class="info-label">출발시간:</span>
                    <span class="info-value"><?= htmlspecialchars($departure_time) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">운송시간:</span>
                    <span class="info-value"><?= htmlspecialchars($product['transit_time']) ?>일</span>
                </div>
                <div class="info-row">
                    <span class="info-label">운송료:</span>
                    <span class="info-value info-price"><?= $show_price ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">남은시간:</span>
                    <span class="info-value" style="color: #dc2626;"><?= $remaining_time ?></span>
                </div>
            </div>

            <!-- INQUIRY 버튼 -->
            <?php if ($is_logged_in): ?>
                <?php if ($availability['status'] !== 'full' && !empty($product['id'])): ?>
                    <a class="card-inquiry-btn blue" href="/reserve/reservation.php?product_id=<?= (int)$product['id'] ?>">
                        <i class="fas fa-lock-open"></i> INQUIRY
                    </a>
                <?php else: ?>
                    <button class="card-inquiry-btn blue" disabled style="background: #94a3b8;">
                        예약마감
                    </button>
                <?php endif; ?>
            <?php else: ?>
                <a class="card-inquiry-btn blue" href="/login/login.php">
                    <i class="fas fa-lock-open"></i> INQUIRY
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// 기존 product-card 스타일을 위한 백업 함수
function renderDefaultCard_OLD($product, $availability, $is_logged_in) {
    ob_start();
    ?>
    <div class="product-card">
        <?php 
        $img = trim($product['image_url'] ?? ''); 
        $hasImage = !empty($img);
        $imageStyle = $hasImage ? "background-image:url('" . htmlspecialchars($img) . "');" : "background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);";
        ?>
        <div class="product-image" style="<?= $imageStyle ?>">
            <?php if (!$hasImage): ?>
                <div class="no-image-placeholder">
                    <i class="fas fa-ship" style="font-size: 3em; color: white; opacity: 0.5;"></i>
                </div>
            <?php endif; ?>
        </div>
        <div class="product-header">
            <h3 class="product-name"><?= htmlspecialchars($product['product_name']) ?></h3>
            <div class="vessel-name"><?= htmlspecialchars($product['vessel_name'] ?? $product['ship_name'] ?? $product['shipping_company']) ?></div>
        </div>

        <div class="route-section">
            <div class="route-display">
                <div class="port">
                    <div class="port-name"><?= htmlspecialchars($product['departure_port']) ?></div>
                </div>
                <div class="arrow">→</div>
                <div class="port">
                    <div class="port-name"><?= htmlspecialchars($product['arrival_port']) ?></div>
                </div>
            </div>

            <div class="transit-info">
                <div class="transit-label">운송시간</div>
                <div class="transit-days"><?= htmlspecialchars($product['transit_time']) ?>일</div>
            </div>

            <div class="availability-info">
                <i class="<?= $availability['icon'] ?>" style="color: <?= $availability['color'] ?>"></i>
                <span style="color: <?= $availability['color'] ?>; font-weight: 600;">
                    <?= $availability['text'] ?>
                </span>
            </div>
        </div>

        <div class="price-section">
            <?php if ($is_logged_in): ?>
                <?php $user_price = getUserGradePrice($product, $user_grade); $grade_label = getGradeLabel($user_grade); ?>
                <div class="price-display">
                    <div class="price-label"><?= $grade_label ?> 가격</div>
                    <div class="price-amount" style="color:#2563eb; font-size:1.2rem;">
                        <?= formatCurrency($user_price) ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="price-display">
                    <div class="price-label">배송가</div>
                    <div class="price-amount" style="color:#9ca3af;">
                        <?= maskPrice($product['price'] ?? 0) ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($product['weekly_schedule'])): ?>
            <div class="schedule"><i class="fas fa-calendar-week"></i> <?= htmlspecialchars($product['weekly_schedule']) ?></div>
            <?php endif; ?>

            <?php if (!empty($product['description'])): ?>
            <div class="desc"><?= nl2br(htmlspecialchars($product['description'])) ?></div>
            <?php endif; ?>

            <div class="badge-row">
                <?php if (!empty($product['cargo_type'])): ?>
                    <span class="badge"><i class="fas fa-box"></i> <?= htmlspecialchars($product['cargo_type']) ?></span>
                <?php endif; ?>
                <?php if (!empty($product['vessel_type'])): ?>
                    <span class="badge"><i class="fas fa-ship"></i> <?= htmlspecialchars($product['vessel_type']) ?></span>
                <?php endif; ?>
                <?php if (!empty($product['operator_company'])): ?>
                    <span class="badge"><i class="fas fa-building"></i> <?= htmlspecialchars($product['operator_company']) ?></span>
                <?php endif; ?>
                <?php if (!empty($product['payment_terms'])): ?>
                    <span class="badge"><i class="fas fa-file-invoice-dollar"></i> <?= htmlspecialchars($product['payment_terms']) ?></span>
                <?php endif; ?>
                <?php if (!empty($product['rate'])): ?>
                    <span class="badge"><i class="fas fa-percent"></i> <?= htmlspecialchars($product['rate']) ?></span>
                <?php endif; ?>
                <?php if (!empty($product['valid_until'])): ?>
                    <span class="badge"><i class="fas fa-calendar"></i> ~<?= htmlspecialchars($product['valid_until']) ?></span>
                <?php endif; ?>
            </div>

            <?php if ($is_logged_in): ?>
                <?php if ($availability['status'] !== 'full'): ?>
                    <?php if (!empty($product['id'])): ?>
                    <a class="reserve-btn" href="/reserve/reservation.php?product_id=<?= (int)$product['id'] ?>">
                        <i class="fas fa-calendar-check"></i>
                        예약하기
                    </a>
                    <?php else: ?>
                    <button class="reserve-btn" disabled>
                        <i class="fas fa-exclamation-triangle"></i>
                        상품 ID 없음
                    </button>
                    <?php endif; ?>
                <?php else: ?>
                    <button class="reserve-btn" disabled>
                        <i class="fas fa-times"></i>
                        예약마감
                    </button>
                <?php endif; ?>
            <?php else: ?>
                <a class="reserve-btn login-btn" href="/login/login.php">
                    <i class="fas fa-sign-in-alt"></i>
                    로그인 후 예약
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// 선박 카드 스타일 렌더링
function renderShipCard($product, $availability, $is_logged_in) {
    ob_start();
    ?>
    <div class="ship-card">
        <?php $img = trim($product['image_url'] ?? ''); if ($img) { ?>
        <div class="product-image" style="background-image:url('<?= htmlspecialchars($img) ?>');"></div>
        <?php } ?>
        <div class="ship-header">
            <div class="ship-name"><?= htmlspecialchars($product['vessel_name'] ?? $product['ship_name'] ?? $product['shipping_company']) ?></div>
            <div class="product-name"><?= htmlspecialchars($product['product_name']) ?></div>
        </div>

        <div class="ship-body">
            <div class="route-info">
                <span><?= htmlspecialchars($product['departure_port']) ?></span>
                <i class="fas fa-arrow-right"></i>
                <span><?= htmlspecialchars($product['arrival_port']) ?></span>
            </div>

            <div class="ship-details">
                <div class="detail-item">
                    <div class="detail-label">운송시간</div>
                    <div class="detail-value"><?= htmlspecialchars($product['transit_time']) ?>일</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">상태</div>
                    <div class="detail-value" style="color: <?= $availability['color'] ?>;">
                        <?= $availability['text'] ?>
                    </div>
                </div>
            </div>

            <div class="price-info">
                <div class="price-amount"><?= formatCurrency($product['price']) ?></div>
                <div class="price-label">기본 운송료</div>
            </div>

            <?php if ($is_logged_in): ?>
                <?php if ($availability['status'] !== 'full'): ?>
                    <a href="/reserve/reservation.php?product_id=<?= (int)($product['id'] ?? 0) ?>" class="btn btn-primary">
                        <i class="fas fa-calendar-plus"></i> 예약하기
                    </a>
                <?php else: ?>
                    <button class="btn btn-disabled" disabled>
                        <i class="fas fa-times"></i> 예약 불가
                    </button>
                <?php endif; ?>
            <?php else: ?>
                <a href="../login/login.php" class="btn btn-primary login-btn">
                    <i class="fas fa-sign-in-alt"></i> 로그인 후 예약
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// 상품 그리드 렌더링 함수
function renderProductsGrid($products, $is_logged_in = false, $style = 'default') {
    if (empty($products)) {
        return renderEmptyState();
    }

    $grid_class = ($style === 'ship') ? 'ships-grid' : 'products-grid';

    ob_start();
    ?>
    <div class="<?= $grid_class ?>">
        <?php foreach ($products as $product): ?>
            <?= renderProductCard($product, $is_logged_in, $style) ?>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}

// 빈 상태 렌더링
function renderEmptyState() {
    return '
    <div class="empty-state">
        <i class="fas fa-ship"></i>
        <h3>현재 이용 가능한 운송 서비스가 없습니다</h3>
        <p>새로운 운항 일정을 준비하고 있습니다.</p>
    </div>';
}

// 가격 마스킹 함수
function maskPrice($price) {
    if (!is_numeric($price) || $price <= 0) {
        return '$***';
    }

    $formattedPrice = number_format($price, 2);
    $parts = explode('.', $formattedPrice);
    $integerPart = $parts[0];
    $decimalPart = isset($parts[1]) ? $parts[1] : '00';

    // 콤마를 기준으로 분리
    $segments = explode(',', $integerPart);
    $maskedSegments = [];

    foreach ($segments as $segment) {
        $maskedSegments[] = str_repeat('*', strlen($segment));
    }

    return '$' . implode(',', $maskedSegments) . '.**';
}

// 사용자 등급에 따른 가격 가져오기
function getUserGradePrice($product, $user_grade = 'basic') {
    switch ($user_grade) {
        case 'vvip':
            return $product['price_vvip'] ?? $product['price'];
        case 'vip':
            return $product['price_vip'] ?? $product['price'];
        case 'gold':
            return $product['price_gold'] ?? $product['price'];
        case 'silver':
            return $product['price_silver'] ?? $product['price'];
        case 'basic':
        default:
            return $product['price'];
    }
}

// 등급명 한글 변환
function getGradeLabel($grade) {
    switch ($grade) {
        case 'vvip':
            return 'VVIP';
        case 'vip':
            return 'VIP';
        case 'gold':
            return 'GOLD';
        case 'silver':
            return 'SILVER';
        case 'basic':
        default:
            return '일반';
    }
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>물류 서비스 - SUNIL SHIPPING</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans KR', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #fafaf9;
            line-height: 1.6;
        }

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
        }

        /* 메인 컨텐츠 */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 48px 24px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .page-title {
            font-size: 2.3rem;
            font-weight: 500;
            color: #44403c;
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
        }

        .page-subtitle {
            font-size: 1rem;
            color: #78716c;
            font-weight: 400;
        }

        /* 상품 그리드 */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        @media (min-width: 1400px) {
            .products-grid {
                grid-template-columns: repeat(4, 1fr);
                max-width: 1200px;
            }
        }

        @media (min-width: 1024px) and (max-width: 1399px) {
            .products-grid {
                grid-template-columns: repeat(3, 1fr);
                max-width: 960px;
            }
        }

        @media (min-width: 768px) and (max-width: 1023px) {
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* 새로운 심플 카드 스타일 */
        .product-card-simple {
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
        }

        .product-card-simple:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .card-image-area {
            width: 100%;
            height: 180px;
            overflow: hidden;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .card-flag-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .card-badge {
            position: absolute;
            bottom: 16px;
            right: 16px;
            background: #dc2626;
            color: white;
            padding: 6px 14px;
            border-radius: 4px;
            font-size: 0.75em;
            font-weight: 600;
            letter-spacing: 0.02em;
            box-shadow: 0 2px 6px rgba(220, 38, 38, 0.3);
        }

        .card-no-image {
            font-size: 2.5em;
            color: #cbd5e1;
        }

        .card-content {
            padding: 20px 18px;
        }

        .card-route {
            font-size: 1em;
            font-weight: 500;
            color: #1e293b;
            text-align: center;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e2e8f0;
        }

        .card-info-list {
            margin-bottom: 16px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-size: 0.85em;
            color: #64748b;
            font-weight: 400;
        }

        .info-value {
            font-size: 0.85em;
            color: #1e293b;
            font-weight: 500;
        }

        .info-price {
            color: #0f172a;
            font-weight: 700;
            font-size: 1em;
        }

        .card-inquiry-btn {
            display: block;
            width: 100%;
            background: #3b82f6;
            color: white;
            text-align: center;
            padding: 11px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9em;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            letter-spacing: 0.02em;
        }

        .card-inquiry-btn:hover {
            background: #2563eb;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .card-inquiry-btn:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            box-shadow: none;
        }

        .card-inquiry-btn i {
            margin-right: 6px;
        }

        /* 모바일 반응형 */
        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
                max-width: 100%;
            }

            .card-image-area {
                height: 160px;
            }

            .card-badge {
                bottom: 10px;
                right: 10px;
                padding: 4px 10px;
                font-size: 0.65em;
            }

            .card-content {
                padding: 16px 14px;
            }

            .card-route {
                font-size: 0.95em;
                margin-bottom: 14px;
                padding-bottom: 10px;
            }

            .card-info-list {
                margin-bottom: 14px;
            }

            .info-row {
                padding: 7px 0;
            }

            .info-label {
                font-size: 0.8em;
            }

            .info-value {
                font-size: 0.8em;
            }

            .info-price {
                font-size: 0.95em;
            }

            .card-inquiry-btn {
                padding: 10px 14px;
                font-size: 0.85em;
            }
        }

        /* 기존 카드 스타일 (백업용) */
        .product-card {
            position: relative;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .product-image {
            width: 100%;
            height: 160px;
            background-size: cover;
            background-position: center;
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .featured-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: #f59e0b;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            z-index: 10;
        }

        .product-header {
            padding: 1.5rem 1.5rem 0 1.5rem;
            margin-bottom: 1rem;
        }

        .product-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .vessel-name {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .route-section {
            padding: 0 1.5rem;
            margin-bottom: 1.5rem;
        }

        .route-display {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 8px;
        }

        .port {
            flex: 1;
            text-align: center;
        }

        .port-name {
            font-weight: 600;
            color: #1f2937;
        }

        .arrow {
            font-size: 1.2rem;
            color: transparent; /* hide broken text inside */
            position: relative;
            width: 24px;
            text-align: center;
        }

        .arrow::before {
            content: "\f061"; /* fa-arrow-right */
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            color: #2563eb;
            position: absolute;
            left: 0;
            right: 0;
        }

        .route-desc {
            margin: 0 1rem 0.5rem 1rem;
            color: #4b5563;
            font-size: 0.9rem;
        }

        .badge-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .badge {
            background: #f3f4f6;
            color: #374151;
            border-radius: 9999px;
            padding: 0.25rem 0.6rem;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        }

        .transit-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 1rem;
            background: #e0f2fe;
            border-radius: 6px;
            margin-bottom: 1rem;
        }

        .transit-label {
            font-size: 0.9rem;
            color: #0369a1;
        }

        .transit-days {
            font-weight: 600;
            color: #0369a1;
        }

        .availability-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: #f0fdf4;
            border-radius: 6px;
            border: 1px solid #bbf7d0;
        }

        .price-section {
            padding: 1.5rem;
            border-top: 1px solid #e5e7eb;
        }

        .price-display {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .price-table {
            display: grid;
            grid-template-columns: 1fr auto;
            row-gap: 6px;
            column-gap: 12px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px 12px;
            margin-bottom: 12px;
        }
        .price-row .p-label {
            color: #6b7280;
            font-size: 0.85rem;
        }
        .price-row .p-val {
            font-weight: 600;
            color: #111827;
            text-align: right;
        }

        .schedule {
            color: #374151;
            font-size: 0.9rem;
            margin: 4px 0 8px 0;
        }
        .desc {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
        }

        .price-label {
            font-size: 0.9rem;
            color: #6b7280;
        }

        .price-amount {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
        }

        .reserve-btn {
            display: block;
            width: max-content; /* shrink to content width */
            padding: 0.75rem 1.25rem;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            margin: 12px auto 0; /* center horizontally */
            text-align: center;
        }

        .reserve-btn:hover:not(:disabled) {
            background: #1d4ed8;
        }

        .reserve-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }

        .reserve-btn.login-btn {
            background: #059669;
        }

        .reserve-btn.login-btn:hover {
            background: #047857;
        }

        /* Center reservation buttons in ship-card variant */
        .ship-card .btn-primary,
        .ship-card .btn-disabled,
        .ship-card .login-btn {
            display: block;
            width: max-content;
            margin: 12px auto 0;
            padding: 0.6rem 1rem;
            text-align: center;
        }

        /* 빈 상태 */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
            grid-column: 1 / -1;
        }

        .empty-state i {
            font-size: 4rem;
            color: #9ca3af;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: #6b7280;
        }

        /* 반응형 */
        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }

            .container {
                padding: 32px 16px;
            }

            .products-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- 헤더 -->
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
                    <a href="../login/edit.php" class="btn btn-outline">회원정보수정</a>
                    <a href="../login/logout.php" class="btn btn-danger">로그아웃</a>
                <?php else: ?>
                    <a href="../login/login.php" class="btn btn-outline">로그인</a>
                    <a href="../login/signup.php" class="btn btn-primary">회원가입</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <!-- 메인 컨텐츠 -->
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">LOGISTIC</h1>
            <p class="page-subtitle">We deliver your logistics quickly and easily.</p>
        </div>

        <?= renderProductsGrid($products, $is_logged_in, 'default') ?>
    </div>

    <script>
        // 예약 페이지로 이동하는 함수
        function handleReservation(productId) {
            if (!productId) {
                alert('상품 정보를 찾을 수 없습니다.');
                return;
            }

            window.location.href = 'reservation.php?product_id=' + productId;
        }

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
</body>
</html>
