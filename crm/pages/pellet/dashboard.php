<?php
/**
 * 우드펠렛 대시보드
 * other/4.1 우드펠렛 메인 데시보드.html 기반
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pageTitle = '우드펠렛 대시보드';
$pageSubtitle = '우드펠렛 생산 및 판매 현황';

$pdo = getDB();

$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('n');

// 검색 파라미터
$searchCompany = $_GET['company'] ?? '';
$searchRep = $_GET['rep'] ?? '';
$searchPhone = $_GET['phone'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;

// 거래 유형별 성과
$tradeTypes = ['온라인', '오프라인', '벌크'];
$monthlyData = [];

try {
    // 테이블 존재 확인
    $tableCheck = $pdo->query("SHOW TABLES LIKE '" . CRM_PELLET_PERFORMANCE_TABLE . "'");
    if ($tableCheck->fetch()) {
        // 컬럼명 확인
        $columns = [];
        $colResult = $pdo->query("SHOW COLUMNS FROM " . CRM_PELLET_PERFORMANCE_TABLE);
        while ($col = $colResult->fetch()) {
            $columns[] = $col['Field'];
        }
        $yearCol = in_array('year', $columns) ? 'year' : 'period_year';
        $monthCol = in_array('month', $columns) ? 'month' : 'period_month';
        $tradeTypeCol = in_array('trade_type', $columns) ? 'trade_type' : (in_array('channel', $columns) ? 'channel' : 'type');
        $quantityCol = in_array('actual', $columns) ? 'actual' : (in_array('quantity', $columns) ? 'quantity' : 'target');

        $stmt = $pdo->prepare("SELECT {$tradeTypeCol} as trade_type, SUM({$quantityCol}) as total
            FROM " . CRM_PELLET_PERFORMANCE_TABLE . "
            WHERE {$yearCol} = ? AND ({$monthCol} = ? OR {$monthCol} IS NULL)
            GROUP BY {$tradeTypeCol}");
        $stmt->execute([$year, $month]);
        while ($row = $stmt->fetch()) {
            $monthlyData[$row['trade_type']] = $row['total'];
        }
    }
} catch (Exception $e) {
    // 오류 시 빈 배열 유지
}

// 거래처 검색 쿼리
$where = ["1=1"];
$params = [];

if ($searchCompany) {
    $where[] = "t.company_name LIKE ?";
    $params[] = "%{$searchCompany}%";
}
if ($searchRep) {
    $where[] = "t.representative LIKE ?";
    $params[] = "%{$searchRep}%";
}
if ($searchPhone) {
    $where[] = "t.phone LIKE ?";
    $params[] = "%{$searchPhone}%";
}

$whereClause = implode(' AND ', $where);

// 전체 카운트
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM " . CRM_PELLET_TRADERS_TABLE . " t WHERE {$whereClause}");
    $stmt->execute($params);
    $totalCount = $stmt->fetchColumn();
} catch (Exception $e) {
    $totalCount = 0;
}

$totalPages = ceil($totalCount / $perPage);
$offset = ($page - 1) * $perPage;

// 거래처 목록 조회 (최신 댓글 포함 - 활동을 통해 조회)
try {
    $stmt = $pdo->prepare("SELECT t.*,
        (SELECT COUNT(*) FROM crm_pellet_activity_comments cm
         INNER JOIN crm_pellet_activities a ON cm.activity_id = a.id
         WHERE a.trader_id = t.id) as comment_count,
        (SELECT cm.content FROM crm_pellet_activity_comments cm
         INNER JOIN crm_pellet_activities a ON cm.activity_id = a.id
         WHERE a.trader_id = t.id
         ORDER BY cm.created_at DESC LIMIT 1) as latest_comment,
        (SELECT cm.created_at FROM crm_pellet_activity_comments cm
         INNER JOIN crm_pellet_activities a ON cm.activity_id = a.id
         WHERE a.trader_id = t.id
         ORDER BY cm.created_at DESC LIMIT 1) as comment_date,
        (SELECT u.name FROM crm_pellet_activity_comments cm
         INNER JOIN crm_pellet_activities a ON cm.activity_id = a.id
         LEFT JOIN " . CRM_USERS_TABLE . " u ON cm.created_by = u.id
         WHERE a.trader_id = t.id
         ORDER BY cm.created_at DESC LIMIT 1) as comment_author
        FROM " . CRM_PELLET_TRADERS_TABLE . " t
        WHERE {$whereClause}
        ORDER BY t.created_at DESC
        LIMIT {$perPage} OFFSET {$offset}");
    $stmt->execute($params);
    $traders = $stmt->fetchAll();
} catch (Exception $e) {
    $traders = [];
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
        color: #fd7e14;
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
    <div class="page-title">우드펠렛 대시보드</div>
    <div class="page-subtitle">우드펠렛 생산 및 판매 현황</div>
</div>

<!-- 성과 차트 -->
<div class="dashboard-grid">
    <div class="card full-width">
        <div class="card-header">
            <div class="card-title">성과 차트 (우드펠렛)</div>
            <a href="<?= CRM_URL ?>/pages/pellet/performance_chart.php" class="card-action">전체보기 →</a>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <div class="chart-header">
                    <div class="chart-title">제품별 판매 실적</div>
                    <div class="chart-filter">
                        <button class="filter-btn active" data-period="1">1개월</button>
                        <button class="filter-btn" data-period="3">3개월</button>
                        <button class="filter-btn" data-period="6">6개월</button>
                        <button class="filter-btn" data-period="12">1년</button>
                    </div>
                </div>

                <div class="bar-chart">
                    <?php
                    $maxValue = max(array_values($monthlyData) ?: [1]);
                    foreach ($tradeTypes as $type):
                        $value = $monthlyData[$type] ?? 0;
                        $percentage = $maxValue > 0 ? ($value / $maxValue) * 100 : 0;
                    ?>
                    <div class="bar-item">
                        <div class="bar-label">
                            <span class="bar-name"><?= h($type) ?></span>
                            <span class="bar-value"><?= number_format($value) ?>톤</span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: <?= $percentage ?>%;"><?= round($percentage) ?>%</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 거래처 리스트 섹션 -->
<hr class="section-divider">

<div class="page-header">
    <div class="page-title">거래처 리스트</div>
    <div class="page-subtitle">우드펠렛 거래처 관리</div>
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
        총 <strong><?= number_format($totalCount) ?></strong>개의 거래처가 검색되었습니다.
    </div>
    <a href="<?= CRM_URL ?>/pages/pellet/trader_form.php" class="btn btn-primary">거래처 등록</a>
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
        <tbody id="traderTableBody">
            <?php if (empty($traders)): ?>
            <tr>
                <td colspan="8" style="text-align: center; padding: 40px; color: #999;">
                    등록된 거래처가 없습니다.
                </td>
            </tr>
            <?php else: ?>
                <?php foreach ($traders as $trader): ?>
                <tr data-id="<?= $trader['id'] ?>">
                    <td class="text-left"><?= h($trader['company_name'] ?? '') ?></td>
                    <td><?= h($trader['business_number'] ?? '') ?></td>
                    <td><?= h($trader['representative'] ?? '') ?></td>
                    <td><?= h($trader['phone'] ?? '') ?></td>
                    <td><?= intval($trader['comment_count'] ?? 0) ?></td>
                    <td title="<?= h($trader['latest_comment'] ?? '') ?>"><?= !empty($trader['latest_comment']) ? mb_substr(h($trader['latest_comment']), 0, 20) . (mb_strlen($trader['latest_comment']) > 20 ? '...' : '') : '' ?></td>
                    <td><?= h($trader['comment_author'] ?? '') ?></td>
                    <td>
                        <button class="detail-btn" onclick="location.href='<?= CRM_URL ?>/pages/pellet/trader_detail.php?id=<?= $trader['id'] ?>'">상세보기</button>
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
