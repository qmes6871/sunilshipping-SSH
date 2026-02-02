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
.ct-wrap { background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; padding:20px; }
.ct-title { margin:0 0 10px; color:#111827; font-size:18px; font-weight:700; }
.ct-sub { color:#6b7280; font-size:12px; margin-bottom:15px; }
.ct-search { display:flex; gap:8px; margin-bottom:12px; }
.ct-input { flex:1; padding:8px 10px; border:1px solid #d1d5db; border-radius:6px; }
.ct-btn { padding:8px 12px; border-radius:6px; border:1px solid #6b7280; background:#6b7280; color:#fff; cursor:pointer; font-weight:600; }
.ct-table { width:100%; border-collapse:collapse; }
.ct-table th, .ct-table td { padding:10px; border-bottom:1px solid #e5e7eb; text-align:left; font-size:13px; }
.ct-table th { background:#f9fafb; color:#374151; font-weight:700; white-space:nowrap; }
.ct-empty { background:#f8fafc; color:#64748b; padding:16px; border-radius:8px; text-align:center; }
.ct-badge { display:inline-block; font-size:11px; padding:2px 8px; border-radius:9999px; background:#eef2ff; color:#3730a3; }
.ct-export-btn { padding:6px 12px; border-radius:4px; border:1px solid #d1d5db; background:#fff; color:#374151; cursor:pointer; font-size:12px; margin-left:8px; }
.ct-export-btn:hover { background:#f3f4f6; }
.ct-title-row { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
.ct-table-row { display: table-row; }
.ct-table-row.hidden { display: none; }
.ct-more-btn { background:#6b7280; color:white; padding:10px 30px; border:none; border-radius:6px; cursor:pointer; font-size:14px; font-weight:600; transition:background 0.3s; margin-top:12px; display:block; margin-left:auto; margin-right:auto; }
.ct-more-btn:hover { background:#4b5563; }
</style>';

// html2canvas library
echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>';

echo '<div class="ct-wrap">';
echo '<h3 class="ct-title">고객 트래킹 내역</h3>';

if (!$pdo) {
    echo '</div>'; // close wrapper
    return;
}

$searchCntr = isset($_GET['search_cntr']) ? trim((string)$_GET['search_cntr']) : '';

// 로그인된 사용자의 고객명만 사용
$koreanName = ct_get_logged_in_korean_name($pdo);

if ($koreanName === '') {
    echo '<div class="ct-empty">로그인이 필요합니다.</div>';
    echo '</div>';
    return;
}

$rows = [];
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
}

echo '<div class="ct-sub">고객명: <strong>' . htmlspecialchars($koreanName) . '</strong> (turkiye: ' . count($r1) . '건, tcr: ' . count($r2) . '건, ww: ' . count($r3) . '건)</div>';

// Search form
echo '<form method="get" class="ct-search">'
   . '<input class="ct-input" type="text" name="search_cntr" value="' . htmlspecialchars($searchCntr) . '" placeholder="CNTR NO로 검색" />'
   . '<button class="ct-btn" type="submit">검색</button>'
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

// Section: turkiye_syria_tracking
echo '<div class="ct-title-row" style="margin-top:16px;">
    <h4 class="ct-title" style="margin:0;">turkiye_syria_tracking</h4>
    <div>
        <button class="ct-export-btn" onclick="viewTableAsImage(\'table1\')">이미지 보기</button>
        <button class="ct-export-btn" onclick="exportTableToImage(\'table1\', \'turkiye_syria_tracking\')">이미지 다운로드</button>
    </div>
</div>';
if (empty($r1)) {
    echo '<div class="ct-empty">데이터 없음</div>';
} else {
    echo '<div style="overflow-x:auto;"><table id="table1" class="ct-table"><thead><tr>'
       . '<th>#</th><th>Booking</th><th>CNTR NO</th><th>Shipper</th><th>Buyer</th><th>POL Port</th><th>POL ETD</th><th>ETA</th><th>Port</th><th>Amount</th>'
       . '</tr></thead><tbody>';
    foreach ($r1 as $i => $row) {
        $rowClass = ($i >= 5) ? 'ct-table-row hidden' : 'ct-table-row';
        echo '<tr class="' . $rowClass . '" data-table="table1">'
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
    if (count($r1) > 5) {
        echo '<button class="ct-more-btn" onclick="showMore(\'table1\')">MORE</button>';
    }
}

// Section: tcr_tracking
echo '<div class="ct-title-row" style="margin-top:24px;">
    <h4 class="ct-title" style="margin:0;">tcr_tracking</h4>
    <div>
        <button class="ct-export-btn" onclick="viewTableAsImage(\'table2\')">이미지 보기</button>
        <button class="ct-export-btn" onclick="exportTableToImage(\'table2\', \'tcr_tracking\')">이미지 다운로드</button>
    </div>
</div>';
if (empty($r2)) {
    echo '<div class="ct-empty">데이터 없음</div>';
} else {
    echo '<div style="overflow-x:auto;"><table id="table2" class="ct-table"><thead><tr>'
       . '<th>#</th><th>ID</th><th>NO</th><th>CNTR NO</th><th>SHIPPER</th><th>BUYER</th><th>HP</th><th>WEIGHT</th><th>POL ETD</th><th>CHINA PORT ETA</th><th>RAIL ETD</th><th>ETD</th><th>WAGON</th><th>BORDER ETA</th><th>BORDER ETD</th><th>CIS WAGON</th><th>FINAL ETA</th><th>FINAL DEST</th><th>Amount</th>'
       . '</tr></thead><tbody>';
    foreach ($r2 as $i => $row) {
        $rowClass = ($i >= 5) ? 'ct-table-row hidden' : 'ct-table-row';
        echo '<tr class="' . $rowClass . '" data-table="table2">'
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
    if (count($r2) > 5) {
        echo '<button class="ct-more-btn" onclick="showMore(\'table2\')">MORE</button>';
    }
}

// Section: ww_tracking
echo '<div class="ct-title-row" style="margin-top:24px;">
    <h4 class="ct-title" style="margin:0;">ww_tracking</h4>
    <div>
        <button class="ct-export-btn" onclick="viewTableAsImage(\'table3\')">이미지 보기</button>
        <button class="ct-export-btn" onclick="exportTableToImage(\'table3\', \'ww_tracking\')">이미지 다운로드</button>
    </div>
</div>';
if (empty($r3)) {
    echo '<div class="ct-empty">데이터 없음</div>';
} else {
    echo '<div style="overflow-x:auto;"><table id="table3" class="ct-table"><thead><tr>'
       . '<th>id</th><th>BOOKING</th><th>CNTR NO</th><th>SHIPPER</th><th>BUYER</th><th>POL PORT</th><th>POL ETD</th><th>ETA</th><th>PORT</th><th>AMOUNT</th>'
       . '</tr></thead><tbody>';
    foreach ($r3 as $i => $row) {
        $rowClass = ($i >= 5) ? 'ct-table-row hidden' : 'ct-table-row';
        echo '<tr class="' . $rowClass . '" data-table="table3">'
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
    if (count($r3) > 5) {
        echo '<button class="ct-more-btn" onclick="showMore(\'table3\')">MORE</button>';
    }
}

echo '</div>'; // close wrapper

// JavaScript
echo '<script>
function showMore(tableId) {
    const rows = document.querySelectorAll("tr[data-table=\"" + tableId + "\"].hidden");
    const btn = event.target;
    let shown = 0;

    rows.forEach((row, index) => {
        if (shown < 5) {
            row.classList.remove("hidden");
            shown++;
        }
    });

    // 더 이상 숨겨진 행이 없으면 버튼 숨기기
    const remainingHidden = document.querySelectorAll("tr[data-table=\"" + tableId + "\"].hidden");
    if (remainingHidden.length === 0) {
        btn.style.display = "none";
    }
}

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
