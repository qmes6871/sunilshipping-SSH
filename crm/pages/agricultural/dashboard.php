<?php
/**
 * 농산물 대시보드
 * other/3.1 농산물 메인 데시보드.html 기반
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pageTitle = '농산물 대시보드';
$pageSubtitle = '농산물 수출 현황 및 성과';

$pdo = getDB();

$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('n');

// 검색 파라미터
$searchCompany = $_GET['company'] ?? '';
$searchRep = $_GET['rep'] ?? '';
$searchPhone = $_GET['phone'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;

// 품목별 성과 (개인실적 테이블에서 목표/실적 조회)
$categories = ['배', '사과', '감귤', '딸기', '수박', '포도', '기타'];
$monthlyData = [];

try {
    // 개인실적 테이블에서 품목별 목표/실적 합계 조회 (성과 차트 페이지와 동일한 로직)
    $tableCheck = $pdo->query("SHOW TABLES LIKE '" . CRM_AGRI_PERSONAL_PERFORMANCE_TABLE . "'");
    if ($tableCheck->fetch()) {
        // 컬럼명 확인
        $columns = [];
        $colResult = $pdo->query("SHOW COLUMNS FROM " . CRM_AGRI_PERSONAL_PERFORMANCE_TABLE);
        while ($col = $colResult->fetch()) {
            $columns[] = $col['Field'];
        }
        $yearCol = in_array('year', $columns) ? 'year' : 'period_year';
        $monthCol = in_array('month', $columns) ? 'month' : 'period_month';
        $itemCol = in_array('item_name', $columns) ? 'item_name' : 'crop';

        $stmt = $pdo->prepare("SELECT {$itemCol} as item,
            SUM(target_amount) as target_total,
            SUM(actual_amount) as actual_total
            FROM " . CRM_AGRI_PERSONAL_PERFORMANCE_TABLE . "
            WHERE {$yearCol} = ? AND {$monthCol} = ?
            GROUP BY {$itemCol}");
        $stmt->execute([$year, $month]);
        while ($row = $stmt->fetch()) {
            $monthlyData[$row['item']] = [
                'target' => floatval($row['target_total']),
                'actual' => floatval($row['actual_total'])
            ];
        }
    }
} catch (Exception $e) {
    // 오류 시 빈 배열 유지
}

// 바이어 검색 쿼리
$where = ["1=1"];
$params = [];

if ($searchCompany) {
    $where[] = "c.company_name LIKE ?";
    $params[] = "%{$searchCompany}%";
}
if ($searchRep) {
    $where[] = "c.representative LIKE ?";
    $params[] = "%{$searchRep}%";
}
if ($searchPhone) {
    $where[] = "c.phone LIKE ?";
    $params[] = "%{$searchPhone}%";
}

$whereClause = implode(' AND ', $where);

// 전체 카운트
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM " . CRM_AGRI_CUSTOMERS_TABLE . " c WHERE {$whereClause}");
    $stmt->execute($params);
    $totalCount = $stmt->fetchColumn();
} catch (Exception $e) {
    $totalCount = 0;
}

$totalPages = ceil($totalCount / $perPage);
$offset = ($page - 1) * $perPage;

// 바이어 목록 조회 (최신 댓글 포함 - 활동을 통해 조회)
try {
    // 활동 테이블과 댓글 테이블 존재 여부 확인
    $actTableExists = $pdo->query("SHOW TABLES LIKE '" . CRM_AGRI_ACTIVITIES_TABLE . "'")->fetch();
    $cmtTableExists = $pdo->query("SHOW TABLES LIKE 'crm_agri_activity_comments'")->fetch();

    if ($actTableExists && $cmtTableExists) {
        $stmt = $pdo->prepare("SELECT c.*,
            (SELECT COUNT(*) FROM crm_agri_activity_comments cm
             INNER JOIN " . CRM_AGRI_ACTIVITIES_TABLE . " a ON cm.activity_id = a.id
             WHERE a.customer_id = c.id) as comment_count,
            (SELECT cm.content FROM crm_agri_activity_comments cm
             INNER JOIN " . CRM_AGRI_ACTIVITIES_TABLE . " a ON cm.activity_id = a.id
             WHERE a.customer_id = c.id
             ORDER BY cm.created_at DESC LIMIT 1) as latest_comment,
            (SELECT cm.created_at FROM crm_agri_activity_comments cm
             INNER JOIN " . CRM_AGRI_ACTIVITIES_TABLE . " a ON cm.activity_id = a.id
             WHERE a.customer_id = c.id
             ORDER BY cm.created_at DESC LIMIT 1) as comment_date,
            (SELECT u.name FROM crm_agri_activity_comments cm
             INNER JOIN " . CRM_AGRI_ACTIVITIES_TABLE . " a ON cm.activity_id = a.id
             LEFT JOIN " . CRM_USERS_TABLE . " u ON cm.created_by = u.id
             WHERE a.customer_id = c.id
             ORDER BY cm.created_at DESC LIMIT 1) as comment_author
            FROM " . CRM_AGRI_CUSTOMERS_TABLE . " c
            WHERE {$whereClause}
            ORDER BY c.created_at DESC
            LIMIT {$perPage} OFFSET {$offset}");
    } else {
        $stmt = $pdo->prepare("SELECT c.*, 0 as comment_count, NULL as latest_comment, NULL as comment_date, NULL as comment_author
            FROM " . CRM_AGRI_CUSTOMERS_TABLE . " c
            WHERE {$whereClause}
            ORDER BY c.created_at DESC
            LIMIT {$perPage} OFFSET {$offset}");
    }
    $stmt->execute($params);
    $customers = $stmt->fetchAll();
} catch (Exception $e) {
    $customers = [];
}

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<style>
    .dashboard-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    .full-width { grid-column: 1 / -1; }

    /* 카드 액션 링크 */
    .card-action {
        font-size: 13px;
        color: #198754;
        cursor: pointer;
        text-decoration: none;
    }

    .card-action:hover {
        text-decoration: underline;
    }

    .chart-container {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 6px;
    }

    .chart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .chart-title { font-size: 14px; font-weight: 600; color: #495057; }

    .chart-filter { display: flex; gap: 6px; }

    .filter-btn {
        padding: 6px 12px;
        border: 1px solid #dee2e6;
        background: #fff;
        border-radius: 4px;
        font-size: 12px;
        cursor: pointer;
        color: #495057;
    }

    .filter-btn:hover { background: #f8f9fa; }
    .filter-btn.active { background: #0d6efd; color: #fff; border-color: #0d6efd; }

    .bar-chart { background: #fff; padding: 24px; border-radius: 6px; }
    .bar-item { margin-bottom: 24px; }
    .bar-item:last-child { margin-bottom: 0; }

    .bar-label {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }

    .bar-name { font-size: 15px; font-weight: 500; }
    .bar-value { font-size: 14px; font-weight: 600; color: #0d6efd; }

    .bar-track {
        width: 100%;
        height: 32px;
        background: #e9ecef;
        border-radius: 6px;
        overflow: hidden;
    }

    .bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #0d6efd 0%, #0dcaf0 100%);
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        padding-right: 12px;
        color: #fff;
        font-size: 13px;
        font-weight: 600;
    }

    .section-divider {
        margin: 40px 0 32px 0;
        border: none;
        border-top: 2px solid #dee2e6;
    }

    .filter-section {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }

    .filter-button {
        width: 100%;
        padding: 14px;
        background: #0d6efd;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
    }

    .filter-button:hover { background: #0b5ed7; }

    .search-options {
        display: none;
        margin-top: 20px;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 6px;
    }

    .search-options.active { display: block; }

    .search-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
        margin-bottom: 16px;
    }

    .search-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .search-field label { font-size: 13px; font-weight: 500; color: #495057; }

    .search-field input, .search-field select {
        padding: 10px 12px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        font-size: 14px;
        background: white;
    }

    .date-range {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .date-range input { flex: 1; }
    .date-separator { color: #6c757d; font-weight: 500; }

    .search-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }

    .btn-search {
        padding: 10px 24px;
        background: #0d6efd;
        color: white;
        border: none;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
    }

    .btn-reset {
        padding: 10px 24px;
        background: white;
        color: #6c757d;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        font-size: 14px;
        cursor: pointer;
    }

    .result-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 0;
        margin-bottom: 16px;
    }

    .result-count { font-size: 15px; color: #212529; }
    .result-count strong { color: #0d6efd; font-weight: 700; }

    .table-container {
        background: white;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
    }

    .table th {
        background: #f8f9fa;
        padding: 14px 12px;
        text-align: center;
        font-size: 13px;
        font-weight: 600;
        color: #495057;
        border-bottom: 2px solid #dee2e6;
    }

    .table td {
        padding: 14px 12px;
        border-bottom: 1px solid #e9ecef;
        font-size: 14px;
        text-align: center;
    }

    .table td.text-left { text-align: left; }
    .table tbody tr:hover { background: #f8f9fa; }

    .detail-btn {
        padding: 6px 16px;
        background: #17a2b8;
        color: white;
        border: none;
        border-radius: 4px;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
    }

    .detail-btn:hover { background: #138496; }

    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        padding: 24px 0;
    }

    .page-btn {
        padding: 8px 12px;
        border: 1px solid #dee2e6;
        background: white;
        color: #495057;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
    }

    .page-btn:hover { background: #f8f9fa; }
    .page-btn.active { background: #0d6efd; color: white; border-color: #0d6efd; }
    .page-btn:disabled { opacity: 0.5; cursor: not-allowed; }

    @media (max-width: 1200px) {
        .dashboard-grid { grid-template-columns: 1fr; }
    }

    @media (max-width: 768px) {
        .search-grid { grid-template-columns: 1fr; }
        .table-container { overflow-x: auto; }
        .table { min-width: 900px; }
    }
</style>

<div class="page-header">
    <div class="page-title">농산물 대시보드</div>
    <div class="page-subtitle">농산물 수출 현황 및 성과</div>
</div>

<!-- 성과 차트 -->
<div class="dashboard-grid">
    <div class="card full-width">
        <div class="card-header">
            <div class="card-title">성과 차트 (농산물)</div>
            <a href="<?= CRM_URL ?>/pages/agricultural/performance_chart.php" class="card-action">전체보기 →</a>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <div class="chart-header">
                    <div class="chart-title">품목별 수출 실적</div>
                    <div class="chart-filter">
                        <button class="filter-btn active" data-period="1">1개월</button>
                        <button class="filter-btn" data-period="3">3개월</button>
                        <button class="filter-btn" data-period="6">6개월</button>
                        <button class="filter-btn" data-period="12">1년</button>
                    </div>
                </div>

                <div class="bar-chart">
                    <?php
                    foreach ($categories as $category):
                        $data = $monthlyData[$category] ?? ['target' => 0, 'actual' => 0];
                        $target = $data['target'] ?? 0;
                        $actual = $data['actual'] ?? 0;
                        $percentage = $target > 0 ? round(($actual / $target) * 100) : 0;
                    ?>
                    <div class="bar-item">
                        <div class="bar-label">
                            <span class="bar-name"><?= h($category) ?></span>
                            <span class="bar-value"><?= number_format($actual) ?>톤 / 목표 <?= number_format($target) ?>톤</span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: <?= min($percentage, 100) ?>%;"><?= $percentage ?>%</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 바이어 리스트 섹션 -->
<hr class="section-divider">

<div class="page-header">
    <div class="page-title">바이어 리스트</div>
    <div class="page-subtitle">농산물 바이어 관리</div>
</div>

<!-- 검색 옵션 -->
<div class="filter-section">
    <button class="filter-button" id="toggleSearchBtn">검색 옵션 펼치기</button>

    <form class="search-options" id="searchOptions" method="GET">
        <div class="search-grid">
            <div class="search-field">
                <label>상호명</label>
                <input type="text" name="company" placeholder="상호명 입력" value="<?= h($searchCompany) ?>">
            </div>
            <div class="search-field">
                <label>대표자명</label>
                <input type="text" name="rep" placeholder="대표자명 입력" value="<?= h($searchRep) ?>">
            </div>
            <div class="search-field">
                <label>전화번호</label>
                <input type="text" name="phone" placeholder="전화번호 입력" value="<?= h($searchPhone) ?>">
            </div>
            <div class="search-field">
                <label>댓글 작성자</label>
                <input type="text" name="comment_author" placeholder="작성자명 입력">
            </div>
            <div class="search-field" style="grid-column: 1 / -1;">
                <label>미팅 작성기간</label>
                <div class="date-range">
                    <input type="date" name="start_date">
                    <span class="date-separator">~</span>
                    <input type="date" name="end_date">
                </div>
            </div>
        </div>
        <div class="search-actions">
            <button type="button" class="btn-reset" onclick="resetSearch()">초기화</button>
            <button type="submit" class="btn-search">검색</button>
        </div>
    </form>
</div>

<!-- 결과 헤더 -->
<div class="result-header">
    <div class="result-count">
        총 <strong><?= number_format($totalCount) ?></strong>명의 고객이 검색되었습니다.
    </div>
    <a href="<?= CRM_URL ?>/pages/agricultural/customer_form.php" class="btn btn-primary">고객 등록</a>
</div>

<!-- 테이블 -->
<div class="table-container">
    <table class="table">
        <thead>
            <tr>
                <th>상호명</th>
                <th>사업자등록번호</th>
                <th>대표자명</th>
                <th>전화번호</th>
                <th>댓글수</th>
                <th>최근 댓글</th>
                <th>최근 작성자</th>
                <th>관리</th>
            </tr>
        </thead>
        <tbody id="customerTableBody">
            <?php if (empty($customers)): ?>
            <tr>
                <td colspan="8" style="text-align: center; padding: 40px; color: #999;">
                    등록된 바이어가 없습니다.
                </td>
            </tr>
            <?php else: ?>
                <?php foreach ($customers as $customer): ?>
                <tr data-id="<?= $customer['id'] ?>">
                    <td class="text-left"><?= h($customer['company_name'] ?? '') ?></td>
                    <td><?= h($customer['business_number'] ?? '') ?></td>
                    <td><?= h($customer['representative'] ?? '') ?></td>
                    <td><?= h($customer['phone'] ?? '') ?></td>
                    <td><?= intval($customer['comment_count'] ?? 0) ?></td>
                    <td title="<?= h($customer['latest_comment'] ?? '') ?>"><?= !empty($customer['latest_comment']) ? mb_substr(h($customer['latest_comment']), 0, 20) . (mb_strlen($customer['latest_comment']) > 20 ? '...' : '') : '' ?></td>
                    <td><?= h($customer['comment_author'] ?? '') ?></td>
                    <td>
                        <button class="detail-btn" onclick="location.href='<?= CRM_URL ?>/pages/agricultural/customer_detail.php?id=<?= $customer['id'] ?>'">상세보기</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- 페이지네이션 -->
<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php if ($page > 1): ?>
    <button class="page-btn" onclick="goToPage(<?= $page - 1 ?>)">이전</button>
    <?php else: ?>
    <button class="page-btn" disabled>이전</button>
    <?php endif; ?>

    <?php
    $start = max(1, $page - 2);
    $end = min($totalPages, $page + 2);
    for ($i = $start; $i <= $end; $i++):
    ?>
    <button class="page-btn <?= $i === $page ? 'active' : '' ?>" onclick="goToPage(<?= $i ?>)"><?= $i ?></button>
    <?php endfor; ?>

    <?php if ($end < $totalPages): ?>
    <button class="page-btn" disabled>...</button>
    <button class="page-btn" onclick="goToPage(<?= $totalPages ?>)"><?= $totalPages ?></button>
    <?php endif; ?>

    <?php if ($page < $totalPages): ?>
    <button class="page-btn" onclick="goToPage(<?= $page + 1 ?>)">다음</button>
    <?php else: ?>
    <button class="page-btn" disabled>다음</button>
    <?php endif; ?>
</div>
<?php endif; ?>

<script>
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
    });
});

const toggleBtn = document.getElementById('toggleSearchBtn');
const searchOptions = document.getElementById('searchOptions');

toggleBtn.addEventListener('click', function() {
    searchOptions.classList.toggle('active');
    this.textContent = searchOptions.classList.contains('active') ? '검색 옵션 접기' : '검색 옵션 펼치기';
});

function resetSearch() {
    searchOptions.querySelectorAll('input, select').forEach(el => el.value = '');
}

function goToPage(page) {
    const url = new URL(window.location.href);
    url.searchParams.set('page', page);
    window.location.href = url.toString();
}
</script>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
