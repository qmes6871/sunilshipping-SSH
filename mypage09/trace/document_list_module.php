<?php
// Document List Module (safe include, no login required)
error_reporting(E_ALL);
ini_set('display_errors', '0');
if (session_status() === PHP_SESSION_NONE) session_start();


if (!defined('G5_MYSQL_HOST')) define('G5_MYSQL_HOST', 'localhost');
if (!defined('G5_MYSQL_USER')) define('G5_MYSQL_USER', 'sunilshipping');
if (!defined('G5_MYSQL_PASSWORD')) define('G5_MYSQL_PASSWORD', 'sunil123!');
if (!defined('G5_MYSQL_DB')) define('G5_MYSQL_DB', 'sunilshipping');


function dl_get_pdo() {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;
  try {
    $pdo = new PDO(
      'mysql:host=' . G5_MYSQL_HOST . ';dbname=' . G5_MYSQL_DB . ';charset=utf8mb4',
      G5_MYSQL_USER,
      G5_MYSQL_PASSWORD,
      [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
  } catch (Throwable $e) {
    echo '<div style="background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px;">DB 연결 실패: ' . htmlspecialchars($e->getMessage()) . '</div>';
    return null;
  }
  return $pdo;
}


function dl_get_logged_in_korean_name(PDO $pdo = null) {
  $username = $_SESSION['ss_mb_id'] ?? $_SESSION['username'] ?? null;
  if (!$username) return null;
  try {
    $stmt = ($pdo ?: dl_get_pdo())->prepare('SELECT korean_name FROM customer_management WHERE username = :u AND status = "active" LIMIT 1');
    $stmt->execute([':u' => $username]);
    $row = $stmt->fetch();
    return $row['korean_name'] ?? null;
  } catch (Throwable $e) { return null; }
}


$pdo = dl_get_pdo();
if (!$pdo) return;


$koreanName = dl_get_logged_in_korean_name($pdo);
$searchCntr = isset($_GET['search_cntr']) ? trim((string)$_GET['search_cntr']) : '';


// Build query
$where = ['shipper = :korean_name'];
$params = [':korean_name' => $koreanName];
if ($searchCntr !== '') { $where[] = 'cntr_no LIKE :search_cntr'; $params[':search_cntr'] = '%' . $searchCntr . '%'; }


// AJAX 요청 처리
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
  header('Content-Type: application/json');
  $rows = [];
  if ($koreanName !== '') {
    try {
      $sql = 'SELECT id, cntr_no, doc_type, doc_no, status, booking, shipper, invoice_file, bl_file, myunjang_file, created_at, updated_at
              FROM trace_document
              WHERE ' . implode(' AND ', $where) . '
              ORDER BY updated_at DESC';
      $stmt = $pdo->prepare($sql);
      $stmt->execute($params);
      $rows = $stmt->fetchAll();
    } catch (Throwable $e) {
      echo json_encode(['error' => $e->getMessage()]);
      exit;
    }
  }
  echo json_encode(['success' => true, 'data' => $rows, 'koreanName' => $koreanName]);
  exit;
}

// 일반 페이지 로드
$rows = [];
if ($koreanName !== '') {
  try {
    $sql = 'SELECT id, cntr_no, doc_type, doc_no, status, booking, shipper, invoice_file, bl_file, myunjang_file, created_at, updated_at
            FROM trace_document
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY updated_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
  } catch (Throwable $e) {
    echo '<div style="background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px;">조회 오류: ' . htmlspecialchars($e->getMessage()) . '</div>';
  }
}


?>
<style>
    .doclist-wrap { font-family: Arial, sans-serif; }
    .doclist-wrap .container { border: 1px solid #e5e7eb; border-radius: 12px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: visible; height: auto; }
    .doclist-wrap h1 { color: #2c3e50; margin-bottom: 8px; font-size: 20px; }
    .doclist-wrap .subtitle { color: #7f8c8d; margin-bottom: 15px; font-size: 13px; }
    .doclist-wrap .customer-info { background: #f8f9fa; padding: 10px 15px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #6b7280; }
    .doclist-wrap .customer-name { font-size: 16px; font-weight: bold; color: #2c3e50; }
    .doclist-wrap .search-section { border-radius: 8px; margin: 12px 0; }
    .doclist-wrap table { width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: fixed; }
    .doclist-wrap th, .doclist-wrap td { padding: 8px 5px; text-align: center; border-bottom: 1px solid #ecf0f1; font-size: 13px; }
    .doclist-wrap th { background: #f8f9fa; font-weight: 600; color: #2c3e50; font-size: 12px; white-space: nowrap; }
    .doclist-wrap th:nth-child(1) { width: 40px; }
    .doclist-wrap th:nth-child(2) { width: auto; }
    .doclist-wrap td:nth-child(2) { word-break: break-all; }
    .doclist-wrap th:nth-child(3) { width: auto; }
    .doclist-wrap th:nth-child(4), .doclist-wrap th:nth-child(5), .doclist-wrap th:nth-child(6) { width: 50px; }
    .doclist-wrap tr:hover { background: #f8f9fa; }
    .doclist-wrap .no-data { text-align: center; padding: 20px; color: #95a5a6; font-style: italic; background: #f8f9fa; border-radius: 8px; }
    .doclist-wrap .btn { padding: 4px; border: none; background: transparent; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
    .doclist-wrap .btn-download:hover { opacity: 0.6; }
    .doclist-wrap .download-icon { width: 20px; height: 20px; fill: #2c3e50; }
    .doclist-wrap .data-row { display: none; }
    .doclist-wrap .data-row.visible { display: table-row; }
    .doclist-wrap .more-btn-container { text-align: center; margin-top: 20px; }
    .doclist-wrap .btn-more { background: #6b7280; color: white; padding: 10px 30px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; transition: background 0.3s; }
    .doclist-wrap .btn-more:hover { background: #4b5563; }
    .doclist-wrap .btn-more:disabled { background: #95a5a6; cursor: not-allowed; }

    @media (max-width: 1024px) {
        .doclist-wrap .container { padding: 10px; }
    }

    @media (max-width: 768px) {
        .doclist-wrap .container { padding: 5px; }
        .doclist-wrap h1 { font-size: 18px; margin-bottom: 5px; }
        .doclist-wrap .subtitle { font-size: 12px; margin-bottom: 10px; }
        .doclist-wrap .customer-info { padding: 8px 12px; margin-bottom: 10px; }
        .doclist-wrap .search-section { margin: 10px 0; }
    }
</style>


<div class="doclist-wrap">
  <div class="container" id="documentListContainer">
    <h1>고객 서류 목록</h1>
    <p class="subtitle">업로드된 서류를 확인하고 다운로드하세요.</p>


    <div class="search-section">
      <form id="searchForm" onsubmit="return searchDocuments(event)" style="display:flex; gap:10px; align-items:center; max-width:500px;">
        <input type="text" id="searchInput" name="search_cntr" placeholder="CNTR NO 검색" value="<?= htmlspecialchars($searchCntr) ?>" style="flex:1; padding:10px; border:1px solid #ddd; border-radius:6px;" />
        <button type="submit" class="btn btn-download" style="background:#6b7280; color:white; padding:10px 20px; border-radius:6px;">검색</button>
      </form>
    </div>


    <?php if ($koreanName === ''): ?>
      <div class="no-data">로그인이 필요합니다.</div>
    <?php else: ?>
      <div class="customer-info">
        <div class="customer-name"><?= htmlspecialchars($koreanName) ?></div>
      </div>


      <div id="documentContent">
      <?php if (empty($rows)): ?>
        <div class="no-data">등록된 서류가 없습니다.</div>
      <?php else: ?>
        <div>
          <table>
            <thead>
              <tr>
                <th>NO</th>
                <th>CNTR NO</th>
                <th>Booking</th>
                <th>Invoice</th>
                <th>B/L</th>
                <th>면장</th>
              </tr>
            </thead>
            <tbody id="documentTableBody">
              <?php foreach ($rows as $idx => $doc): ?>
                <tr class="data-row <?= $idx < 5 ? 'visible' : '' ?>">
                  <td><?= $idx + 1 ?></td>
                  <td><strong><?= htmlspecialchars($doc['cntr_no']) ?></strong></td>
                  <td><?= htmlspecialchars($doc['booking'] ?? '-') ?></td>
                  <td>
                    <?php if (!empty($doc['invoice_file'])): ?>
                      <a class="btn btn-download" href="/admin/document/download.php?id=<?= $doc['id'] ?>&type=invoice" title="Invoice 다운로드">
                        <svg class="download-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                          <path d="M12 15.575c-.2 0-.383-.058-.55-.175a.876.876 0 0 1-.3-.45L7.4 11.2a.88.88 0 0 1-.2-.55c0-.417.192-.717.575-.9.383-.2.733-.175 1.05.075L11 11.75V5c0-.283.096-.52.288-.712A.968.968 0 0 1 12 4c.283 0 .521.096.713.288A.967.967 0 0 1 13 5v6.75l2.175-1.925c.317-.25.667-.275 1.05-.075.383.183.575.483.575.9 0 .2-.067.383-.2.55l-3.75 3.75a.876.876 0 0 1-.3.45.877.877 0 0 1-.55.175zM6 20c-.55 0-1.02-.196-1.412-.587A1.927 1.927 0 0 1 4 18v-2c0-.283.096-.521.288-.713A.967.967 0 0 1 5 15c.283 0 .521.096.713.287.191.192.287.43.287.713v2h12v-2c0-.283.096-.521.288-.713A.967.967 0 0 1 19 15c.283 0 .521.096.712.287.192.192.288.43.288.713v2c0 .55-.196 1.021-.587 1.413A1.928 1.928 0 0 1 18 20H6z"/>
                        </svg>
                      </a>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if (!empty($doc['bl_file'])): ?>
                      <a class="btn btn-download" href="/admin/document/download.php?id=<?= $doc['id'] ?>&type=bl" title="B/L 다운로드">
                        <svg class="download-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                          <path d="M12 15.575c-.2 0-.383-.058-.55-.175a.876.876 0 0 1-.3-.45L7.4 11.2a.88.88 0 0 1-.2-.55c0-.417.192-.717.575-.9.383-.2.733-.175 1.05.075L11 11.75V5c0-.283.096-.52.288-.712A.968.968 0 0 1 12 4c.283 0 .521.096.713.288A.967.967 0 0 1 13 5v6.75l2.175-1.925c.317-.25.667-.275 1.05-.075.383.183.575.483.575.9 0 .2-.067.383-.2.55l-3.75 3.75a.876.876 0 0 1-.3.45.877.877 0 0 1-.55.175zM6 20c-.55 0-1.02-.196-1.412-.587A1.927 1.927 0 0 1 4 18v-2c0-.283.096-.521.288-.713A.967.967 0 0 1 5 15c.283 0 .521.096.713.287.191.192.287.43.287.713v2h12v-2c0-.283.096-.521.288-.713A.967.967 0 0 1 19 15c.283 0 .521.096.712.287.192.192.288.43.288.713v2c0 .55-.196 1.021-.587 1.413A1.928 1.928 0 0 1 18 20H6z"/>
                        </svg>
                      </a>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if (!empty($doc['myunjang_file'])): ?>
                      <a class="btn btn-download" href="/admin/document/download.php?id=<?= $doc['id'] ?>&type=myunjang" title="면장 다운로드">
                        <svg class="download-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                          <path d="M12 15.575c-.2 0-.383-.058-.55-.175a.876.876 0 0 1-.3-.45L7.4 11.2a.88.88 0 0 1-.2-.55c0-.417.192-.717.575-.9.383-.2.733-.175 1.05.075L11 11.75V5c0-.283.096-.52.288-.712A.968.968 0 0 1 12 4c.283 0 .521.096.713.288A.967.967 0 0 1 13 5v6.75l2.175-1.925c.317-.25.667-.275 1.05-.075.383.183.575.483.575.9 0 .2-.067.383-.2.55l-3.75 3.75a.876.876 0 0 1-.3.45.877.877 0 0 1-.55.175zM6 20c-.55 0-1.02-.196-1.412-.587A1.927 1.927 0 0 1 4 18v-2c0-.283.096-.521.288-.713A.967.967 0 0 1 5 15c.283 0 .521.096.713.287.191.192.287.43.287.713v2h12v-2c0-.283.096-.521.288-.713A.967.967 0 0 1 19 15c.283 0 .521.096.712.287.192.192.288.43.288.713v2c0 .55-.196 1.021-.587 1.413A1.928 1.928 0 0 1 18 20H6z"/>
                        </svg>
                      </a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php if (count($rows) > 5): ?>
        <div class="more-btn-container">
          <button class="btn-more" onclick="loadMoreDocuments()">MORE</button>
        </div>
        <?php endif; ?>
      <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
let currentVisible = 5;
let totalRows = <?= count($rows) ?>;

function loadMoreDocuments() {
  const rows = document.querySelectorAll('#documentTableBody .data-row');
  const moreBtn = document.querySelector('#documentContent .btn-more');

  let count = 0;
  for (let i = currentVisible; i < rows.length && count < 5; i++) {
    rows[i].classList.add('visible');
    count++;
  }

  currentVisible += count;

  if (currentVisible >= totalRows) {
    moreBtn.disabled = true;
    moreBtn.textContent = '전체 목록 표시됨';
  }
}

function searchDocuments(event) {
  event.preventDefault();

  const searchValue = document.getElementById('searchInput').value;
  const params = new URLSearchParams();
  params.set('ajax', '1');
  if (searchValue) {
    params.set('search_cntr', searchValue);
  }

  const url = '/mypage/trace/document_list_module.php?' + params.toString();

  fetch(url)
    .then(response => {
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      return response.json();
    })
    .then(data => {
      if (data.error) {
        alert('검색 오류: ' + data.error);
        return;
      }

      renderDocuments(data.data);
    })
    .catch(error => {
      console.error('Error:', error);
      alert('검색 중 오류가 발생했습니다: ' + error.message);
    });

  return false;
}

function renderDocuments(documents) {
  const contentDiv = document.getElementById('documentContent');
  totalRows = documents.length;
  currentVisible = 5;

  if (documents.length === 0) {
    contentDiv.innerHTML = '<div class="no-data">등록된 서류가 없습니다.</div>';
    return;
  }

  const downloadIcon = `<svg class="download-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
    <path d="M12 15.575c-.2 0-.383-.058-.55-.175a.876.876 0 0 1-.3-.45L7.4 11.2a.88.88 0 0 1-.2-.55c0-.417.192-.717.575-.9.383-.2.733-.175 1.05.075L11 11.75V5c0-.283.096-.52.288-.712A.968.968 0 0 1 12 4c.283 0 .521.096.713.288A.967.967 0 0 1 13 5v6.75l2.175-1.925c.317-.25.667-.275 1.05-.075.383.183.575.483.575.9 0 .2-.067.383-.2.55l-3.75 3.75a.876.876 0 0 1-.3.45.877.877 0 0 1-.55.175zM6 20c-.55 0-1.02-.196-1.412-.587A1.927 1.927 0 0 1 4 18v-2c0-.283.096-.521.288-.713A.967.967 0 0 1 5 15c.283 0 .521.096.713.287.191.192.287.43.287.713v2h12v-2c0-.283.096-.521.288-.713A.967.967 0 0 1 19 15c.283 0 .521.096.712.287.192.192.288.43.288.713v2c0 .55-.196 1.021-.587 1.413A1.928 1.928 0 0 1 18 20H6z"/>
  </svg>`;

  let html = `<div><table>
    <thead>
      <tr>
        <th>NO</th>
        <th>CNTR NO</th>
        <th>Booking</th>
        <th>Invoice</th>
        <th>B/L</th>
        <th>면장</th>
      </tr>
    </thead>
    <tbody id="documentTableBody">`;

  documents.forEach((doc, idx) => {
    const visibleClass = idx < 5 ? 'visible' : '';
    const booking = doc.booking || '-';

    html += `<tr class="data-row ${visibleClass}">
      <td>${idx + 1}</td>
      <td><strong>${escapeHtml(doc.cntr_no)}</strong></td>
      <td>${escapeHtml(booking)}</td>
      <td>
        ${doc.invoice_file ? `<a class="btn btn-download" href="/admin/document/download.php?id=${doc.id}&type=invoice" title="Invoice 다운로드">${downloadIcon}</a>` : ''}
      </td>
      <td>
        ${doc.bl_file ? `<a class="btn btn-download" href="/admin/document/download.php?id=${doc.id}&type=bl" title="B/L 다운로드">${downloadIcon}</a>` : ''}
      </td>
      <td>
        ${doc.myunjang_file ? `<a class="btn btn-download" href="/admin/document/download.php?id=${doc.id}&type=myunjang" title="면장 다운로드">${downloadIcon}</a>` : ''}
      </td>
    </tr>`;
  });

  html += `</tbody></table></div>`;

  if (documents.length > 5) {
    html += `<div class="more-btn-container">
      <button class="btn-more" onclick="loadMoreDocuments()">MORE</button>
    </div>`;
  }

  contentDiv.innerHTML = html;
}

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}
</script>
