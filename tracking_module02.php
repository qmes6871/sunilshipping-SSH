<?php
// Customer Tracking Module (echoes directly on include)
error_reporting(E_ALL);
ini_set('display_errors', '0');
if (session_status() === PHP_SESSION_NONE) session_start();

// Always emit a tiny marker so the caller detects output
echo "<!-- customer-tracking-module-loaded -->\n";

// DB config (define only if not already defined)
if (!defined('G5_MYSQL_HOST')) define('G5_MYSQL_HOST', 'localhost');
if (!defined('G5_MYSQL_USER')) define('G5_MYSQL_USER', 'sunilshipping');
if (!defined('G5_MYSQL_PASSWORD')) define('G5_MYSQL_PASSWORD', 'sunil123!');
if (!defined('G5_MYSQL_DB')) define('G5_MYSQL_DB', 'sunilshipping');

function ct_get_pdo() {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    try {
        $pdo = new PDO(
            'mysql:host=' . G5_MYSQL_HOST . ';dbname=' . G5_MYSQL_DB . ';charset=utf8mb4',
            G5_MYSQL_USER,
            G5_MYSQL_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    } catch (Throwable $e) {
        echo '<div style="background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px;">DB 연결 실패: ' . htmlspecialchars($e->getMessage()) . '</div>';
        return null;
    }
    return $pdo;
}

function ct_get_logged_in_korean_name(PDO $pdo = null) {
    $pdo = $pdo ?: ct_get_pdo();
    if (!$pdo) return null;
    $username = $_SESSION['ss_mb_id'] ?? $_SESSION['username'] ?? null;
    if (!$username) return null;
    $stmt = $pdo->prepare('SELECT korean_name FROM customer_management WHERE username = :u AND status = "active" LIMIT 1');
    $stmt->execute([':u' => $username]);
    $row = $stmt->fetch();
    return $row['korean_name'] ?? null;
}

$pdo = ct_get_pdo();

// Styles
echo '<style>
.ct-header { background-color: #fff; padding:3rem 20px; text-align:center; margin-bottom:2rem; }
.ct-header-title { font-size:32px; font-weight:600; color:#111; margin-bottom: 40px; text-align: left; }
.ct-header-subtitle { font-size:16px; color:#505050; text-align: left;  font-weight: 400; }
.ct-wrap { background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; padding: 20px; margin: 20px;}
.ct-title { margin:0 0 10px; color:#111827; font-size:18px; font-weight:700; }
.ct-sub { color:#6b7280; font-size:12px; margin-bottom:15px; }
.ct-search { display:flex; gap:8px; margin-bottom:12px; flex-wrap:wrap; }
.ct-input { flex:1; min-width:0; padding:8px 10px; border:1px solid #d1d5db; border-radius:6px; box-sizing:border-box; }
.ct-btn { padding:8px 12px; border-radius:6px; border:1px solid #2563eb; background:#2563eb; color:#fff; cursor:pointer; font-weight:600; white-space:nowrap; flex-shrink:0; }
.ct-table { width:100%; border-collapse:collapse; }
.ct-table th, .ct-table td { padding:10px; border-bottom:1px solid #e5e7eb; text-align:left; font-size:13px; }
.ct-table th { background:#f9fafb; color:#374151; font-weight:700; white-space:nowrap; }
.ct-empty { background:#f8fafc; color:#64748b; padding:16px; border-radius:8px; text-align:center; }
.ct-badge { display:inline-block; font-size:11px; padding:2px 8px; border-radius:9999px; background:#eef2ff; color:#3730a3; }
.ct-export-btn { padding:6px 12px; border-radius:4px; border:1px solid #d1d5db; background:#fff; color:#374151; cursor:pointer; font-size:12px; margin-left:8px; }
.ct-export-btn:hover { background:#f3f4f6; }
.ct-title-row { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
.ct-blur-overlay { position:relative; }
.ct-blur-content { pointer-events:none; user-select:none; }
.ct-login-overlay { position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); z-index:10; width:90%; max-width:500px; }
.ct-login-required { background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; padding:3rem 2rem; text-align:center; box-shadow:0 10px 25px rgba(0,0,0,0.1); }
.ct-login-icon { width:80px; height:80px; background:#2563eb; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1.5rem; }
.ct-login-icon svg { width:40px; height:40px; fill:#ffffff; }
.ct-login-title { font-size:1.5rem; font-weight:700; color:#111827; margin-bottom:1rem; }
.ct-login-desc { color:#6b7280; font-size:0.95rem; line-height:1.6; margin-bottom:2rem; max-width:400px; margin-left:auto; margin-right:auto; }
.ct-login-buttons { display:flex; gap:1rem; justify-content:center; align-items:center; }
.ct-login-btn { padding:0.75rem 2rem; border-radius:6px; font-weight:600; font-size:0.95rem; text-decoration:none; display:inline-flex; align-items:center; gap:0.5rem; transition:all 0.3s ease; }
.ct-login-btn-primary { background:#2563eb; color:#ffffff; border:2px solid #2563eb;   white-space: nowrap;}
.ct-login-btn-primary:hover { background:#2563eb; border-color:#2563eb; }
.ct-login-btn-secondary { background:#ffffff; color:#2563eb; border:2px solid #3b82f6;   white-space: nowrap;}
.ct-login-btn-secondary:hover { background:#eff6ff; }

@media (max-width: 768px) {
    .ct-header {
        margin-bottom: 1.5rem;E
    }
    
    .ct-header-title {
        font-size: 20px;
    }
    
    .ct-header-subtitle {
        font-size: 15px;
    }
            .ct-search { gap: 6px; }
    .ct-input { min-width: 120px; font-size: 14px; }
    .ct-btn { font-size: 14px; }
}
</style>';

// html2canvas library
echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>';

if (!$pdo) {
    echo '<div class="ct-wrap">';
    echo '<h3 class="ct-title">Tracking History</h3>';
    echo '</div>'; // close wrapper
    return;
}

$searchCntr = isset($_GET['search_cntr']) ? trim((string)$_GET['search_cntr']) : '';

// 로그인된 사용자의 고객명만 사용
$koreanName = ct_get_logged_in_korean_name($pdo);

// 비로그인 사용자를 위한 로그인 유도 화면
$isGuest = ($koreanName === null || $koreanName === '');

// 헤더 섹션 추가
echo '<div class="ct-header">';
echo '<h1 class="ct-header-title">Container Tracking</h1>';
echo '<p class="ct-header-subtitle">Check your real-time cargo tracking and shipment status.</p>';
echo '</div>';

// 비로그인 시 오버레이 컨테이너 시작
if ($isGuest) {
    echo '<div class="ct-blur-overlay">';
}

echo '<div class="ct-wrap' . ($isGuest ? ' ct-blur-content' : '') . '">';
echo '<h3 class="ct-title">Tracking History</h3>';

$r1 = [];
$r2 = [];
$r3 = [];
$rows = [];

if ($isGuest) {
    // 비로그인 상태: 실제 샘플 데이터 표시 (AMOUNT만 마스킹)
    $koreanName = 'Guest';
    $r1 = [];
    $r2 = [];
    $r3 = [
        [
            'id' => '1',
            'BOOKING' => 'BK20250101',
            'CNTR_NO' => 'MSKU1234567',
            'SHIPPER' => 'Incheon Port',
            'BUYER' => 'Sample Buyer 1',
            'POL_PORT' => 'Incheon',
            'POL_ETD' => '09-01',
            'ETA' => '09-15',
            'PORT' => 'Incheon → Los Angeles',
            'AMOUNT' => '****'
        ],
        [
            'id' => '2',
            'BOOKING' => 'BK20250102',
            'CNTR_NO' => 'TEMU9876543',
            'SHIPPER' => 'Incheon Port',
            'BUYER' => 'Sample Buyer 2',
            'POL_PORT' => 'Incheon',
            'POL_ETD' => '09-10',
            'ETA' => '09-25',
            'PORT' => 'Incheon → Long Beach',
            'AMOUNT' => '****'
        ],
        [
            'id' => '3',
            'BOOKING' => 'BK20250103',
            'CNTR_NO' => 'HLCU5554321',
            'SHIPPER' => 'Incheon Port',
            'BUYER' => 'Sample Buyer 3',
            'POL_PORT' => 'Incheon',
            'POL_ETD' => '09-10',
            'ETA' => '09-25',
            'PORT' => 'Incheon → Seattle',
            'AMOUNT' => '****'
        ],
        [
            'id' => '4',
            'BOOKING' => 'BK20250104',
            'CNTR_NO' => 'CSNU7778888',
            'SHIPPER' => 'Incheon Port',
            'BUYER' => 'Sample Buyer 4',
            'POL_PORT' => 'Incheon',
            'POL_ETD' => '09-10',
            'ETA' => '09-25',
            'PORT' => 'Incheon → Tacoma',
            'AMOUNT' => '****'
        ],
        [
            'id' => '5',
            'BOOKING' => 'BK20250105',
            'CNTR_NO' => 'OOLU2223456',
            'SHIPPER' => 'Incheon Port',
            'BUYER' => 'Sample Buyer 5',
            'POL_PORT' => 'Incheon',
            'POL_ETD' => '09-10',
            'ETA' => '09-25',
            'PORT' => 'Incheon → Oakland',
            'AMOUNT' => '****'
        ],
        [
            'id' => '6',
            'BOOKING' => 'BK20250106',
            'CNTR_NO' => 'MSCU6665432',
            'SHIPPER' => 'Incheon Port',
            'BUYER' => 'Sample Buyer 6',
            'POL_PORT' => 'Incheon',
            'POL_ETD' => '09-12',
            'ETA' => '09-27',
            'PORT' => 'Incheon → Vancouver',
            'AMOUNT' => '****'
        ]
    ];
} else {
    // 로그인 사용자의 실제 데이터
    try {
        $sql1 = 'SELECT *
                 FROM turkiye_syria_tracking
                 WHERE shipper = :name' . ($searchCntr !== '' ? ' AND CNTR_NO LIKE :cntr' : '') . '
                 ORDER BY Booking DESC';
        $stmt1 = $pdo->prepare($sql1);
        $params1 = [':name' => $koreanName];
        if ($searchCntr !== '') $params1[':cntr'] = '%' . $searchCntr . '%';
        $stmt1->execute($params1);
        $r1 = $stmt1->fetchAll();

        $sql2 = 'SELECT *
                 FROM tcr_tracking
                 WHERE SHIPPER = :name' . ($searchCntr !== '' ? ' AND CNTR_NO LIKE :cntr' : '') . '
                 ORDER BY NO DESC';
        $stmt2 = $pdo->prepare($sql2);
        $params2 = [':name' => $koreanName];
        if ($searchCntr !== '') $params2[':cntr'] = '%' . $searchCntr . '%';
        $stmt2->execute($params2);
        $r2 = $stmt2->fetchAll();

        $sql3 = 'SELECT *
                 FROM ww_tracking
                 WHERE SHIPPER = :name' . ($searchCntr !== '' ? ' AND CNTR_NO LIKE :cntr' : '') . '
                 ORDER BY BOOKING DESC';
        $stmt3 = $pdo->prepare($sql3);
        $params3 = [':name' => $koreanName];
        if ($searchCntr !== '') $params3[':cntr'] = '%' . $searchCntr . '%';
        $stmt3->execute($params3);
        $r3 = $stmt3->fetchAll();

        $rows = array_merge($r1, $r2, $r3);
    } catch (Throwable $e) {
        echo '<div style="background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px;">조회 중 오류: ' . htmlspecialchars($e->getMessage()) . '</div>';
        $r1 = [];
        $r2 = [];
        $r3 = [];
    }
}

echo '<div class="ct-sub">Customer Name: <strong>' . htmlspecialchars($koreanName) . '</strong> (turkiye: ' . count($r1) . '건, tcr: ' . count($r2) . '건, ww: ' . count($r3) . '건)</div>';

// Search form
echo '<form method="get" class="ct-search">'
   . '<input class="ct-input" type="text" name="search_cntr" value="' . htmlspecialchars($searchCntr) . '" placeholder="Search by CNTR No." />'
   . '<button class="ct-btn" type="submit">Search</button>'
   . ($searchCntr !== '' ? '<a href="?" style="padding:8px 12px;border-radius:6px;border:1px solid #9ca3af;color:#374151;text-decoration:none;">전체보기</a>' : '')
   . '</form>';

// helper: case-insensitive getter with fallbacks
$getv = function(array $row, array $keys) {
    foreach ($keys as $k) {
        if ($k === null || $k === '') continue;
        // try exact
        if (array_key_exists($k, $row)) return $row[$k];
        // try upper/lower
        $u = strtoupper($k); $l = strtolower($k);
        if (array_key_exists($u, $row)) return $row[$u];
        if (array_key_exists($l, $row)) return $row[$l];
    }
    return '';
};

// Section: turkiye_syria_tracking (로그인 사용자만 표시)
if (!$isGuest) {
    echo '<div class="ct-title-row" style="margin-top:16px;">
        <h4 class="ct-title" style="margin:0;">turkiye_syria_tracking</h4>
        <div>
            <button class="ct-export-btn" onclick="viewTableAsImage(\'table1\')">View Image</button>
            <button class="ct-export-btn" onclick="exportTableToImage(\'table1\', \'turkiye_syria_tracking\')">Download Image</button>
        </div>
    </div>';
    if (empty($r1)) {
        echo '<div class="ct-empty">No Data</div>';
    } else {
        echo '<div style="overflow-x:auto;"><table id="table1" class="ct-table"><thead><tr>'
           . '<th>#</th><th>Booking</th><th>CNTR NO</th><th>Shipper</th><th>Buyer</th><th>POL Port</th><th>POL ETD</th><th>ETA</th><th>Port</th><th>Amount</th>'
           . '</tr></thead><tbody>';
        foreach ($r1 as $i => $row) {
            echo '<tr>'
               . '<td>' . ($i+1) . '</td>'
               . '<td>' . htmlspecialchars($getv($row,['Booking'])) . '</td>'
               . '<td>' . htmlspecialchars($getv($row,['CNTR_NO','cntr_no'])) . '</td>'
               . '<td>' . htmlspecialchars($getv($row,['shipper','SHIPPER'])) . '</td>'
               . '<td>' . htmlspecialchars($getv($row,['BUYER','buyer'])) . '</td>'
               . '<td>' . htmlspecialchars($getv($row,['POL_PORT','pol_port'])) . '</td>'
               . '<td>' . htmlspecialchars($getv($row,['POL_ETD','pol_etd'])) . '</td>'
               . '<td>' . htmlspecialchars($getv($row,['ETA','eta'])) . '</td>'
               . '<td>' . htmlspecialchars($getv($row,['PORT','port'])) . '</td>'
               . '<td>' . htmlspecialchars($getv($row,['AMOUNT','amount'])) . '</td>'
               . '</tr>';
        }
        echo '</tbody></table></div>';
    }
}

// Section: tcr_tracking (로그인 사용자만 표시)
if (!$isGuest) {
    echo '<div class="ct-title-row" style="margin-top:24px;">
        <h4 class="ct-title" style="margin:0;">tcr_tracking</h4>
        <div>
            <button class="ct-export-btn" onclick="viewTableAsImage(\'table2\')">View Image</button>
            <button class="ct-export-btn" onclick="exportTableToImage(\'table2\', \'tcr_tracking\')">Download Image</button>
        </div>
    </div>';
    if (empty($r2)) {
        echo '<div class="ct-empty">No Data</div>';
    } else {
        echo '<div style="overflow-x:auto;"><table id="table2" class="ct-table"><thead><tr>'
           . '<th>#</th><th>ID</th><th>NO</th><th>CNTR NO</th><th>SHIPPER</th><th>BUYER</th><th>HP</th><th>WEIGHT</th><th>POL ETD</th><th>CHINA PORT ETA</th><th>RAIL ETD</th><th>ETD</th><th>WAGON</th><th>BORDER ETA</th><th>BORDER ETD</th><th>CIS WAGON</th><th>FINAL ETA</th><th>FINAL DEST</th><th>Amount</th>'
           . '</tr></thead><tbody>';
        foreach ($r2 as $i => $row) {
            echo '<tr>'
               . '<td>' . ($i+1) . '</td>'
               . '<td>' . htmlspecialchars($getv($row,['id','ID'])) . '</td>'
               . '<td>' . htmlspecialchars($getv($row,['NO'])) . '</td>'
               . '<td>' . htmlspecialchars($getv($row,['CNTR_NO'])) . '</td>'
               . '<td>' . htmlspecialchars($getv($row,['SHIPPER'])) . '</td>'
               . '<td>' . htmlspecialchars($getv($row,['BUYER'])) . '</td>'
               . '<td>' . htmlspecialchars($getv($row,['HP'])) . '</td>'
               . '<td>' . htmlspecialchars($getv($row,['WEIGHT'])) . '</td>'
               . '<td>' . htmlspecialchars($getv($row,['POL_ETD'])) . '</td>'
               . '<td>' . htmlspecialchars($getv($row,['CHINA_PORT_ETA'])) . '</td>'
               . '<td>' . htmlspecialchars($getv($row,['RAIL_ETD'])) . '</td>'
               . '<td>' . htmlspecialchars($getv($row,['ETD'])) . '</td>'
               . '<td>' . htmlspecialchars($getv($row,['WAGON'])) . '</td>'
               . '<td>' . htmlspecialchars($getv($row,['BORDER_ETA'])) . '</td>'
               . '<td>' . htmlspecialchars($getv($row,['BORDER_ETD'])) . '</td>'
               . '<td>' . htmlspecialchars($getv($row,['CIS_WAGON'])) . '</td>'
               . '<td>' . htmlspecialchars($getv($row,['FINAL_ETA'])) . '</td>'
               . '<td>' . htmlspecialchars($getv($row,['FINAL_DEST'])) . '</td>'
               . '<td>' . htmlspecialchars($getv($row,['amount','AMOUNT'])) . '</td>'
               . '</tr>';
        }
        echo '</tbody></table></div>';
    }
}

// Section: ww_tracking
echo '<div class="ct-title-row" style="margin-top:24px;">
    <h4 class="ct-title" style="margin:0;">ww_tracking</h4>
    <div>
        <button class="ct-export-btn" onclick="viewTableAsImage(\'table3\')">View Image</button>
        <button class="ct-export-btn" onclick="exportTableToImage(\'table3\', \'ww_tracking\')">Download Image</button>
    </div>
</div>';
if (empty($r3)) {
    echo '<div class="ct-empty">No Data</div>';
} else {
    echo '<div style="overflow-x:auto;"><table id="table3" class="ct-table"><thead><tr>'
       . '<th>id</th><th>BOOKING</th><th>CNTR NO</th><th>SHIPPER</th><th>BUYER</th><th>POL PORT</th><th>POL ETD</th><th>ETA</th><th>PORT</th><th>AMOUNT</th>'
       . '</tr></thead><tbody>';
    foreach ($r3 as $i => $row) {
        echo '<tr>'
           . '<td>' . htmlspecialchars($getv($row,['id','ID'])) . '</td>'
           . '<td>' . htmlspecialchars($getv($row,['BOOKING','Booking'])) . '</td>'
           . '<td>' . htmlspecialchars($getv($row,['CNTR_NO','cntr_no'])) . '</td>'
           . '<td>' . htmlspecialchars($getv($row,['SHIPPER','shipper'])) . '</td>'
           . '<td>' . htmlspecialchars($getv($row,['BUYER','buyer'])) . '</td>'
           . '<td>' . htmlspecialchars($getv($row,['POL_PORT','pol_port'])) . '</td>'
           . '<td>' . htmlspecialchars($getv($row,['POL_ETD','pol_etd','ETD'])) . '</td>'
           . '<td>' . htmlspecialchars($getv($row,['ETA','eta'])) . '</td>'
           . '<td>' . htmlspecialchars($getv($row,['PORT','port','ETA_PORT','eta_port'])) . '</td>'
           . '<td>' . htmlspecialchars($getv($row,['AMOUNT','amount'])) . '</td>'
           . '</tr>';
    }
    echo '</tbody></table></div>';
}

echo '</div>'; // close wrapper

// 비로그인 시 로그인 오버레이 추가
if ($isGuest) {
    echo '<div class="ct-login-overlay">';
    echo '<div class="ct-login-required">';
    echo '<div class="ct-login-icon">';
    echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"/></svg>';
    echo '</div>';
    echo '<h2 class="ct-login-title">24/7 Real-time transport data</h2>';
    echo '<p class="ct-login-desc">Log in to view real-time container locations<br>and detailed shipment information.</p>';
    echo '<div class="ct-login-buttons">';
    echo '<a href="login/login.php" class="ct-login-btn ct-login-btn-primary">';
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M11 7L9.6 8.4l2.6 2.6H2v2h10.2l-2.6 2.6L11 17l5-5-5-5zm9 12h-8v2h8c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-8v2h8v14z"/></svg>';
    echo 'LOGIN';
    echo '</a>';
    echo '<a href="login/signup.php" class="ct-login-btn ct-login-btn-secondary">';
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>';
    echo 'Sign up';
    echo '</a>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>'; // close ct-blur-overlay
}

// JavaScript
echo '<script>
function viewTableAsImage(tableId) {
    const element = document.getElementById(tableId);
    if (!element) {
        alert("테이블을 찾을 수 없습니다.");
        return;
    }

    // 스크롤 제거를 위해 임시로 overflow 변경
    const originalOverflow = element.style.overflow;
    const originalOverflowX = element.style.overflowX;
    element.style.overflow = "visible";
    element.style.overflowX = "visible";

    html2canvas(element, {
        scale: 2,
        backgroundColor: "#ffffff",
        logging: false,
        useCORS: true,
        scrollX: 0,
        scrollY: 0,
        windowWidth: element.scrollWidth,
        windowHeight: element.scrollHeight
    }).then(canvas => {
        // 원래 스타일로 복원
        element.style.overflow = originalOverflow;
        element.style.overflowX = originalOverflowX;

        const imageUrl = canvas.toDataURL("image/png");
        const newTab = window.open();
        newTab.document.write("<html><head><title>테이블 이미지</title></head><body style=\"margin:0;display:flex;justify-content:center;align-items:center;min-height:100vh;background:#f3f4f6;\"><img src=\"" + imageUrl + "\" style=\"max-width:100%;height:auto;\"/></body></html>");
        newTab.document.close();
    }).catch(err => {
        // 에러 시에도 원래 스타일로 복원
        element.style.overflow = originalOverflow;
        element.style.overflowX = originalOverflowX;
        console.error("이미지 변환 실패:", err);
        alert("이미지 변환에 실패했습니다.");
    });
}

function exportTableToImage(tableId, tableName) {
    const element = document.getElementById(tableId);
    if (!element) {
        alert("테이블을 찾을 수 없습니다.");
        return;
    }

    // 스크롤 제거를 위해 임시로 overflow 변경
    const originalOverflow = element.style.overflow;
    const originalOverflowX = element.style.overflowX;
    element.style.overflow = "visible";
    element.style.overflowX = "visible";

    html2canvas(element, {
        scale: 2,
        backgroundColor: "#ffffff",
        logging: false,
        useCORS: true,
        scrollX: 0,
        scrollY: 0,
        windowWidth: element.scrollWidth,
        windowHeight: element.scrollHeight
    }).then(canvas => {
        // 원래 스타일로 복원
        element.style.overflow = originalOverflow;
        element.style.overflowX = originalOverflowX;

        const link = document.createElement("a");
        link.download = tableName + "_" + new Date().toISOString().slice(0,10) + ".png";
        link.href = canvas.toDataURL("image/png");
        link.click();
    }).catch(err => {
        // 에러 시에도 원래 스타일로 복원
        element.style.overflow = originalOverflow;
        element.style.overflowX = originalOverflowX;
        console.error("이미지 변환 실패:", err);
        alert("이미지 변환에 실패했습니다.");
    });
}
</script>';
