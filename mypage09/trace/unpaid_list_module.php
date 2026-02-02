<?php
// www/mypage/unpaid_list.php
error_reporting(E_ALL);
ini_set('display_errors', '0');
if (session_status() === PHP_SESSION_NONE) session_start();

// DB 설정
if (!defined('G5_MYSQL_HOST')) define('G5_MYSQL_HOST', 'localhost');
if (!defined('G5_MYSQL_USER')) define('G5_MYSQL_USER', 'sunilshipping');
if (!defined('G5_MYSQL_PASSWORD')) define('G5_MYSQL_PASSWORD', 'sunil123!');
if (!defined('G5_MYSQL_DB')) define('G5_MYSQL_DB', 'sunilshipping');

// PDO 연결
function getDbConnection() {
    try {
        return new PDO(
            "mysql:host=" . G5_MYSQL_HOST . ";dbname=" . G5_MYSQL_DB . ";charset=utf8mb4",
            G5_MYSQL_USER,
            G5_MYSQL_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    } catch (PDOException $e) {
        exit("DB Connection Failed: " . $e->getMessage());
    }
}

// 로그인한 고객 정보 조회
function getLoggedInCustomer($pdo) {
    $username = $_SESSION['ss_mb_id'] ?? $_SESSION['username'] ?? null;

    if (!$username) {
        return null;
    }

    $sql = "SELECT korean_name, customer_type
            FROM customer_management
            WHERE username = :username
              AND status = 'active'
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':username' => $username]);
    return $stmt->fetch();
}

try {
    $pdo = getDbConnection();
    $customer = getLoggedInCustomer($pdo);

    if (!$customer) {
        throw new Exception("로그인이 필요합니다.");
    }

    $koreanName = $customer['korean_name'];

    echo "<!-- ========== 디버깅 정보 ========== -->";
    echo "<!-- 로그인한 고객 korean_name: '" . $koreanName . "' -->";

    // 각 테이블에서 shipper = korean_name이고 amount가 있는 데이터 조회

    // 1. turkiye_syria_tracking 테이블
    echo "<!-- [1] turkiye_syria_tracking 테이블 조회 중... -->";
    $debugSql1 = "SELECT DISTINCT shipper FROM turkiye_syria_tracking WHERE shipper IS NOT NULL AND shipper <> '' LIMIT 10";
    $debugStmt1 = $pdo->query($debugSql1);
    $shippers1 = $debugStmt1->fetchAll(PDO::FETCH_COLUMN);
    echo "<!-- Turkey/Syria shipper 샘플: " . implode(', ', $shippers1) . " -->";

    // 해당 shipper의 전체 데이터 개수 확인
    $debugSql1_total = "SELECT COUNT(*) FROM turkiye_syria_tracking WHERE shipper = :korean_name";
    $debugStmt1_total = $pdo->prepare($debugSql1_total);
    $debugStmt1_total->execute([':korean_name' => $koreanName]);
    $turkey_total = $debugStmt1_total->fetchColumn();
    echo "<!-- Turkey/Syria 해당 shipper 전체 데이터: " . $turkey_total . "건 -->";

    $sql1 = "SELECT 'turkiye_syria_tracking' AS source, Booking, CNTR_NO, shipper, amount
      FROM turkiye_syria_tracking
      WHERE shipper = :korean_name";
    $stmt1 = $pdo->prepare($sql1);
    $stmt1->execute([':korean_name' => $koreanName]);
    $rows1 = $stmt1->fetchAll();
    echo "<!-- Turkey/Syria rows count: " . count($rows1) . " -->";
    if (count($rows1) > 0) {
        echo "<!-- Turkey/Syria 첫번째 데이터 - shipper: '" . ($rows1[0]['shipper'] ?? 'NULL') . "', amount: '" . ($rows1[0]['amount'] ?? 'NULL') . "' -->";
    }

    // 2. tcr_tracking 테이블
    echo "<!-- [2] tcr_tracking 테이블 조회 중... -->";
    $debugSql2 = "SELECT DISTINCT SHIPPER FROM tcr_tracking WHERE SHIPPER IS NOT NULL AND SHIPPER <> '' LIMIT 10";
    $debugStmt2 = $pdo->query($debugSql2);
    $shippers2 = $debugStmt2->fetchAll(PDO::FETCH_COLUMN);
    echo "<!-- TCR SHIPPER 샘플: " . implode(', ', $shippers2) . " -->";

    // 해당 SHIPPER의 전체 데이터 개수 확인
    $debugSql2_total = "SELECT COUNT(*) FROM tcr_tracking WHERE SHIPPER = :korean_name";
    $debugStmt2_total = $pdo->prepare($debugSql2_total);
    $debugStmt2_total->execute([':korean_name' => $koreanName]);
    $tcr_total = $debugStmt2_total->fetchColumn();
    echo "<!-- TCR 해당 SHIPPER 전체 데이터: " . $tcr_total . "건 -->";

    $sql2 = "SELECT 'tcr_tracking' AS source, NO as Booking, CNTR_NO, SHIPPER as shipper, amount
      FROM tcr_tracking
      WHERE SHIPPER = :korean_name";
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute([':korean_name' => $koreanName]);
    $rows2 = $stmt2->fetchAll();
    echo "<!-- TCR rows count: " . count($rows2) . " -->";
    if (count($rows2) > 0) {
        echo "<!-- TCR 첫번째 데이터 - SHIPPER: '" . ($rows2[0]['shipper'] ?? 'NULL') . "', amount: '" . ($rows2[0]['amount'] ?? 'NULL') . "' -->";
    }

    // 3. ww_tracking 테이블
    echo "<!-- [3] ww_tracking 테이블 조회 중... -->";
    $debugSql3 = "SELECT DISTINCT SHIPPER FROM ww_tracking WHERE SHIPPER IS NOT NULL AND SHIPPER <> '' LIMIT 10";
    $debugStmt3 = $pdo->query($debugSql3);
    $shippers3 = $debugStmt3->fetchAll(PDO::FETCH_COLUMN);
    echo "<!-- WW SHIPPER 샘플: " . implode(', ', $shippers3) . " -->";

    // AMOUNT가 NULL이 아닌 데이터 개수 확인
    $debugSql3_amount = "SELECT COUNT(*) FROM ww_tracking WHERE SHIPPER = :korean_name";
    $debugStmt3_amount = $pdo->prepare($debugSql3_amount);
    $debugStmt3_amount->execute([':korean_name' => $koreanName]);
    $ww_total = $debugStmt3_amount->fetchColumn();
    echo "<!-- WW 해당 SHIPPER 전체 데이터: " . $ww_total . "건 -->";

    $sql3 = "SELECT 'ww_tracking' AS source, BOOKING as Booking, CNTR_NO, SHIPPER as shipper, AMOUNT as amount
      FROM ww_tracking
      WHERE SHIPPER = :korean_name";
    $stmt3 = $pdo->prepare($sql3);
    $stmt3->execute([':korean_name' => $koreanName]);
    $rows3 = $stmt3->fetchAll();
    echo "<!-- WW rows count: " . count($rows3) . " -->";
    if (count($rows3) > 0) {
        echo "<!-- WW 첫번째 데이터 - SHIPPER: '" . ($rows3[0]['shipper'] ?? 'NULL') . "', AMOUNT: '" . ($rows3[0]['amount'] ?? 'NULL') . "' -->";
    }

    // 모든 결과를 합침
    $rows = array_merge($rows1, $rows2, $rows3);
    echo "<!-- 전체 합친 rows count: " . count($rows) . " -->";

    // amount 값 정리 및 필터링
    $filteredRows = [];
    foreach ($rows as $row) {
        if (!isset($row['amount']) || $row['amount'] === '' || $row['amount'] === null) {
            continue;
        }

        // amount에서 $, 콤마 등 제거하고 숫자만 추출
        $cleanAmount = preg_replace('/[^0-9.-]/', '', $row['amount']);

        // 숫자로 변환 가능하고 0이 아닌 경우만
        if (is_numeric($cleanAmount) && (float)$cleanAmount != 0) {
            $row['amount_numeric'] = (float)$cleanAmount; // 숫자 값 저장
            $filteredRows[] = $row;
        }
    }
    $rows = $filteredRows;

    echo "<!-- 필터링 후 rows count: " . count($rows) . " -->";
    echo "<!-- ========== 디버깅 정보 끝 ========== -->";

    // 전체 amount 합계 계산
    $totalAmount = 0;
    foreach ($rows as $row) {
        if (isset($row['amount_numeric'])) {
            $totalAmount += $row['amount_numeric'];
        }
    }

} catch (Exception $e) {
    http_response_code(500);
    echo "<p style='color:red'>에러: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}
?>
<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<title>미수금 내역</title>
<style>
  body { font-family: system-ui, Arial, sans-serif; font-size: 13px; }
  h2 { font-size: 18px; margin: 10px 0; }
  table { border-collapse: collapse; width: 100%; font-size: 12px; }
  th, td { border: 1px solid #ddd; padding: 6px 8px; }
  th { background: #f5f5f5; text-align: left; font-size: 11px; }
  .right { text-align: right; }
  .src { font-size: 10px; color: #666; }
  .unpaid-row { display: none !important; }
  .unpaid-row.visible { display: table-row !important; }
  .more-btn-container { text-align: center; margin-top: 15px; }
  .btn-more { background: #6b7280; color: white; padding: 10px 30px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; transition: background 0.3s; }
  .btn-more:hover { background: #4b5563; }
  .btn-more:disabled { background: #95a5a6; cursor: not-allowed; }
</style>
</head>
<body>
  <h2>미수금 내역 - <?= htmlspecialchars($customer['korean_name']) ?></h2>
  <p style="color:#666; font-size:12px; margin:5px 0;">총 <?= count($rows) ?>건</p>
  <p style="color:#333; font-size:15px; font-weight:bold; margin:10px 0;">
    전체 미수금 합계: $<?= number_format($totalAmount, 2) ?>
  </p>
  <table id="unpaidTable">
    <thead>
      <tr>
        <th>Source</th>
        <th>BOOKING</th>
        <th>CNTR_NO</th>
        <th>SHIPPER</th>
        <th class="right">AMOUNT</th>
      </tr>
    </thead>
    <tbody id="unpaidTableBody">
      <?php if (empty($rows)): ?>
        <tr><td colspan="5" class="src">미수금 데이터가 없습니다.</td></tr>
      <?php else: foreach ($rows as $idx => $r): ?>
        <tr class="unpaid-row <?= $idx < 5 ? 'visible' : '' ?>">
          <td class="src"><?= htmlspecialchars($r['source'] ?? '') ?></td>
          <td><?= htmlspecialchars($r['Booking'] ?? '') ?></td>
          <td><?= htmlspecialchars($r['CNTR_NO'] ?? '') ?></td>
          <td><?= htmlspecialchars($r['shipper'] ?? '') ?></td>
          <td class="right"><?= htmlspecialchars($r['amount']) ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>

  <?php if (count($rows) > 5): ?>
  <div class="more-btn-container">
    <button class="btn-more" onclick="loadMoreUnpaid()">MORE</button>
  </div>
  <?php endif; ?>

  <script>
  (function() {
    let currentVisibleUnpaid = 5;
    const totalRowsUnpaid = <?= count($rows) ?>;

    window.loadMoreUnpaid = function() {
      // unpaidTableBody 내의 unpaid-row만 선택
      const allRows = document.querySelectorAll('#unpaidTableBody tr.unpaid-row');

      if (allRows.length === 0) {
        alert('미수금 데이터 행을 찾을 수 없습니다. (총 행 수: ' + allRows.length + ')');
        return;
      }

      let count = 0;
      for (let i = 0; i < allRows.length; i++) {
        const row = allRows[i];
        // visible 클래스가 없는 첫 5개만 표시
        if (!row.classList.contains('visible') && count < 5) {
          row.classList.add('visible');
          count++;
        }
      }

      currentVisibleUnpaid += count;

      // 버튼 업데이트
      const moreBtn = document.querySelector('#unpaidTable + .more-btn-container .btn-more');
      if (moreBtn && currentVisibleUnpaid >= totalRowsUnpaid) {
        moreBtn.disabled = true;
        moreBtn.textContent = '전체 목록 표시됨';
      }
    };
  })();
  </script>
</body>
</html>
