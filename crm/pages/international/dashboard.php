<?php
/**
 * 국제물류 대시보드
 * other 폴더 디자인 기반으로 업데이트
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pageTitle = '국제물류 대시보드';
$pageSubtitle = '실시간 물류 현황 및 성과 확인';

$pdo = getDB();

// 현재 연도/월
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('n');

// 검색 파라미터
$searchName = $_GET['name'] ?? '';
$searchCountry = $_GET['country'] ?? '';
$searchCustomerType = $_GET['customer_type'] ?? '';
$searchExportCountry = $_GET['export_country'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;

// 지역 목록 (설정에서 가져오기)
$regions = getIntlRegions();
$countries = getIntlCountries();

// 월별 성과 데이터 조회
$monthlyData = [];
try {
    // 테이블 존재 확인
    $tableCheck = $pdo->query("SHOW TABLES LIKE '" . CRM_INTL_PERFORMANCE_TABLE . "'");
    if ($tableCheck->fetch()) {
        // 컬럼명 확인
        $columns = [];
        $colResult = $pdo->query("SHOW COLUMNS FROM " . CRM_INTL_PERFORMANCE_TABLE);
        while ($col = $colResult->fetch()) {
            $columns[] = $col['Field'];
        }
        $yearCol = in_array('year', $columns) ? 'year' : 'period_year';
        $monthCol = in_array('month', $columns) ? 'month' : 'period_month';
        $countCol = in_array('count', $columns) ? 'count' : 'performance_count';

        $stmt = $pdo->prepare("SELECT region, SUM({$countCol}) as total
            FROM " . CRM_INTL_PERFORMANCE_TABLE . "
            WHERE {$yearCol} = ? AND ({$monthCol} = ? OR {$monthCol} IS NULL)
            GROUP BY region");
        $stmt->execute([$year, $month]);
        while ($row = $stmt->fetch()) {
            $monthlyData[$row['region']] = $row['total'];
        }
    }
} catch (Exception $e) {
    // 오류 시 빈 배열 유지
}

// 바이어 검색 쿼리
$where = ["1=1"];
$params = [];

if ($searchName) {
    $where[] = "(c.name LIKE ? OR c.company LIKE ?)";
    $params[] = "%{$searchName}%";
    $params[] = "%{$searchName}%";
}
if ($searchCountry) {
    $where[] = "c.nationality LIKE ?";
    $params[] = "%{$searchCountry}%";
}
if ($searchCustomerType) {
    $where[] = "c.customer_type = ?";
    $params[] = $searchCustomerType;
}
if ($searchExportCountry) {
    $where[] = "c.export_country LIKE ?";
    $params[] = "%{$searchExportCountry}%";
}

$whereClause = implode(' AND ', $where);

// 전체 카운트
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM " . CRM_INTL_CUSTOMERS_TABLE . " c WHERE {$whereClause}");
    $stmt->execute($params);
    $totalCount = $stmt->fetchColumn();
} catch (Exception $e) {
    $totalCount = 0;
}

$totalPages = ceil($totalCount / $perPage);
$offset = ($page - 1) * $perPage;

// 바이어 목록 조회 (최신 댓글 포함 - 활동을 통해 조회)
try {
    $stmt = $pdo->prepare("SELECT c.*,
        (SELECT cm.content FROM " . CRM_COMMENTS_TABLE . " cm
         INNER JOIN " . CRM_INTL_ACTIVITIES_TABLE . " a ON cm.entity_type = 'intl_activity' AND cm.entity_id = a.id
         WHERE a.customer_id = c.id
         ORDER BY cm.created_at DESC LIMIT 1) as latest_comment,
        (SELECT cm.created_at FROM " . CRM_COMMENTS_TABLE . " cm
         INNER JOIN " . CRM_INTL_ACTIVITIES_TABLE . " a ON cm.entity_type = 'intl_activity' AND cm.entity_id = a.id
         WHERE a.customer_id = c.id
         ORDER BY cm.created_at DESC LIMIT 1) as comment_date,
        (SELECT u.name FROM " . CRM_COMMENTS_TABLE . " cm
         INNER JOIN " . CRM_INTL_ACTIVITIES_TABLE . " a ON cm.entity_type = 'intl_activity' AND cm.entity_id = a.id
         LEFT JOIN " . CRM_USERS_TABLE . " u ON cm.created_by = u.id
         WHERE a.customer_id = c.id
         ORDER BY cm.created_at DESC LIMIT 1) as comment_author
        FROM " . CRM_INTL_CUSTOMERS_TABLE . " c
        WHERE {$whereClause}
        ORDER BY c.created_at DESC
        LIMIT {$perPage} OFFSET {$offset}");
    $stmt->execute($params);
    $customers = $stmt->fetchAll();
} catch (Exception $e) {
    $customers = [];
}

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<style>
    /* 대시보드 그리드 */
    .dashboard-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    .full-width {
        grid-column: 1 / -1;
    }

    /* 카드 액션 링크 */
    .card-action {
        font-size: 13px;
        color: #0d6efd;
        cursor: pointer;
        text-decoration: none;
    }

    .card-action:hover {
        text-decoration: underline;
    }

    /* 차트 컨테이너 */
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

    .chart-title {
        font-size: 14px;
        font-weight: 600;
        color: #495057;
    }

    .chart-filter {
        display: flex;
        gap: 6px;
    }

    .filter-btn {
        padding: 6px 12px;
        border: 1px solid #dee2e6;
        background: #fff;
        border-radius: 4px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.2s;
        color: #495057;
    }

    .filter-btn:hover {
        background: #f8f9fa;
    }

    .filter-btn.active {
        background: #0d6efd;
        color: #fff;
        border-color: #0d6efd;
    }

    /* 막대 그래프 */
    .bar-chart {
        background: #fff;
        padding: 24px;
        border-radius: 6px;
    }

    .bar-item {
        margin-bottom: 24px;
    }

    .bar-item:last-child {
        margin-bottom: 0;
    }

    .bar-label {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }

    .bar-name {
        font-size: 15px;
        font-weight: 500;
        color: #212529;
    }

    .bar-value {
        font-size: 14px;
        font-weight: 600;
        color: #0d6efd;
    }

    .bar-track {
        width: 100%;
        height: 32px;
        background: #e9ecef;
        border-radius: 6px;
        overflow: hidden;
        position: relative;
    }

    .bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #0d6efd 0%, #0dcaf0 100%);
        border-radius: 6px;
        transition: width 0.8s ease;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        padding-right: 12px;
        color: #fff;
        font-size: 13px;
        font-weight: 600;
    }

    /* 섹션 구분선 */
    .section-divider {
        margin: 40px 0 32px 0;
        border: none;
        border-top: 2px solid #dee2e6;
    }

    /* 검색 옵션 */
    .search-options {
        display: none;
        margin-top: 20px;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 6px;
    }

    .search-options.active {
        display: block;
    }

    .date-range {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .date-range input {
        flex: 1;
    }

    .date-separator {
        color: #6c757d;
        font-weight: 500;
    }

    /* WhatsApp 아이콘 */
    .whatsapp-icon {
        display: inline-block;
        width: 28px;
        height: 28px;
        background: #25D366;
        color: #fff;
        border-radius: 50%;
        text-align: center;
        line-height: 28px;
        cursor: pointer;
        transition: transform 0.2s;
        font-size: 14px;
    }

    .whatsapp-icon:hover {
        transform: scale(1.1);
    }

    /* 고객 사진 */
    .customer-photo {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #dee2e6;
        overflow: hidden;
    }

    .customer-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* 반응형 */
    @media (max-width: 1200px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .table-container {
            overflow-x: auto;
        }

        .table-container table {
            min-width: 1100px;
        }

        .chart-filter {
            flex-wrap: wrap;
        }
    }
</style>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-start;">
    <div>
        <div class="page-title">국제물류 대시보드</div>
        <div class="page-subtitle">실시간 물류 현황 및 성과 확인</div>
    </div>
    <?php if (isAdmin()): ?>
    <a href="<?= CRM_URL ?>/pages/international/settings.php" class="btn btn-secondary" style="font-size: 13px;">설정 관리</a>
    <?php endif; ?>
</div>

<!-- 대시보드 그리드 -->
<div class="dashboard-grid">
    <!-- 성과 차트 -->
    <div class="card full-width">
        <div class="card-header">
            <div class="card-title">성과 차트</div>
            <a href="<?= CRM_URL ?>/pages/international/performance_chart.php" class="card-action">전체보기 →</a>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <div class="chart-header">
                    <div class="chart-title">월별 수출 실적</div>
                    <div class="chart-filter">
                        <button class="filter-btn active" data-period="1">1개월</button>
                        <button class="filter-btn" data-period="3">3개월</button>
                        <button class="filter-btn" data-period="6">6개월</button>
                        <button class="filter-btn" data-period="12">1년</button>
                    </div>
                </div>

                <!-- 막대 그래프 -->
                <div class="bar-chart">
                    <?php
                    $maxValue = max(array_values($monthlyData) ?: [1]);
                    foreach ($regions as $region):
                        $value = $monthlyData[$region] ?? 0;
                        $percentage = $maxValue > 0 ? ($value / $maxValue) * 100 : 0;
                    ?>
                    <div class="bar-item">
                        <div class="bar-label">
                            <span class="bar-name"><?= h($region) ?></span>
                            <span class="bar-value"><?= number_format($value) ?>건</span>
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

<!-- 바이어 리스트 섹션 -->
<hr class="section-divider">

<div class="page-header">
    <div class="page-title">바이어 리스트</div>
    <div class="page-subtitle">국제물류 바이어 관리</div>
</div>

<!-- 상단 필터 버튼 -->
<div class="filter-section">
    <button class="filter-button" id="toggleSearchBtn">검색 옵션 펼치기</button>

    <!-- 검색 옵션 폼 -->
    <form class="search-options" id="searchOptions" method="GET">
        <div class="search-grid">
            <div class="search-field">
                <label>이름</label>
                <input type="text" name="name" placeholder="회사명 또는 담당자명" value="<?= h($searchName) ?>">
            </div>
            <div class="search-field">
                <label>국가</label>
                <select name="country">
                    <option value="">전체</option>
                    <?php foreach ($countries as $country): ?>
                    <option value="<?= h($country) ?>" <?= $searchCountry === $country ? 'selected' : '' ?>><?= h($country) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="search-field">
                <label>고객 유형</label>
                <select name="customer_type">
                    <option value="">전체</option>
                    <option value="buyer" <?= $searchCustomerType === 'buyer' ? 'selected' : '' ?>>buyer</option>
                    <option value="partner" <?= $searchCustomerType === 'partner' ? 'selected' : '' ?>>partner</option>
                    <option value="VIP" <?= $searchCustomerType === 'VIP' ? 'selected' : '' ?>>VIP</option>
                </select>
            </div>
            <div class="search-field">
                <label>최종 수출국</label>
                <input type="text" name="export_country" placeholder="수출국 입력" value="<?= h($searchExportCountry) ?>">
            </div>
            <div class="search-field">
                <label>댓글 내용</label>
                <input type="text" name="comment_content" placeholder="댓글 내용 검색">
            </div>
            <div class="search-field">
                <label>댓글 작성자</label>
                <input type="text" name="comment_author" placeholder="작성자명 입력">
            </div>
            <div class="search-field">
                <label>댓글 작성 기간</label>
                <div class="date-range">
                    <input type="date" name="comment_start_date">
                    <span class="date-separator">~</span>
                    <input type="date" name="comment_end_date">
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
    <a href="<?= CRM_URL ?>/pages/international/customer_form.php" class="btn btn-primary">고객 등록</a>
</div>

<!-- 테이블 -->
<div class="table-container">
    <table class="table">
        <thead>
            <tr>
                <th>사진</th>
                <th>이름</th>
                <th>전화번호</th>
                <th>국가</th>
                <th>고객유형</th>
                <th>왓츠앱</th>
                <th>최종 수출국</th>
                <th>최신댓글</th>
                <th>최신댓글 작성자</th>
                <th>관리</th>
            </tr>
        </thead>
        <tbody id="customerTableBody">
            <?php if (empty($customers)): ?>
            <tr>
                <td colspan="10" style="text-align: center; padding: 40px; color: #999;">
                    등록된 바이어가 없습니다.
                </td>
            </tr>
            <?php else: ?>
                <?php foreach ($customers as $customer): ?>
                <tr data-id="<?= $customer['id'] ?>">
                    <td>
                        <div class="customer-photo">
                            <?php if (!empty($customer['photo'])): ?>
                            <img src="<?= CRM_UPLOAD_URL ?>/<?= h($customer['photo']) ?>" alt="">
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="text-left"><?= h($customer['name']) ?></td>
                    <td><?= h($customer['phone'] ?? '') ?></td>
                    <td><?= h($customer['nationality'] ?? '') ?></td>
                    <td><?= h($customer['customer_type'] ?? '') ?></td>
                    <td>
                        <?php if (!empty($customer['whatsapp'])): ?>
                        <span class="whatsapp-icon" onclick="openWhatsApp('<?= h($customer['whatsapp']) ?>')">W</span>
                        <?php endif; ?>
                    </td>
                    <td><?= h($customer['export_country'] ?? '') ?></td>
                    <td title="<?= h($customer['latest_comment'] ?? '') ?>"><?= !empty($customer['latest_comment']) ? mb_substr(h($customer['latest_comment']), 0, 20) . (mb_strlen($customer['latest_comment']) > 20 ? '...' : '') : '' ?></td>
                    <td><?= h($customer['comment_author'] ?? '') ?></td>
                    <td>
                        <button class="detail-btn" onclick="location.href='<?= CRM_URL ?>/pages/international/customer_detail.php?id=<?= $customer['id'] ?>'">상세보기</button>
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
// 필터 버튼 클릭 (차트)
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        // TODO: 차트 데이터 업데이트
    });
});

// 검색 옵션 토글
const toggleBtn = document.getElementById('toggleSearchBtn');
const searchOptions = document.getElementById('searchOptions');

toggleBtn.addEventListener('click', function() {
    searchOptions.classList.toggle('active');
    if (searchOptions.classList.contains('active')) {
        toggleBtn.textContent = '검색 옵션 접기';
    } else {
        toggleBtn.textContent = '검색 옵션 펼치기';
    }
});

// 검색 초기화
function resetSearch() {
    searchOptions.querySelectorAll('input, select').forEach(el => {
        el.value = '';
    });
}

// 페이지 이동
function goToPage(page) {
    const url = new URL(window.location.href);
    url.searchParams.set('page', page);
    window.location.href = url.toString();
}

// WhatsApp 열기
function openWhatsApp(number) {
    // 숫자만 추출
    const cleanNumber = number.replace(/[^0-9]/g, '');
    window.open(`https://wa.me/${cleanNumber}`, '_blank');
}

// 테이블 행 클릭 이벤트 (상세보기 버튼 제외)
document.querySelectorAll('#customerTableBody tr[data-id]').forEach(row => {
    row.addEventListener('click', function(e) {
        if (!e.target.closest('.detail-btn') && !e.target.closest('.whatsapp-icon')) {
            const id = this.dataset.id;
            // window.location.href = '<?= CRM_URL ?>/pages/international/customer_detail.php?id=' + id;
        }
    });
});

// AJAX로 데이터 로드 (선택적)
async function loadCustomers(filters = {}) {
    try {
        const params = new URLSearchParams(filters);
        const response = await apiGet('<?= CRM_URL ?>/api/international/customers.php?' + params.toString());
        if (response.success) {
            renderCustomerTable(response.data);
        }
    } catch (error) {
        console.error('데이터 로드 실패:', error);
    }
}

// 테이블 렌더링
function renderCustomerTable(customers) {
    const tbody = document.getElementById('customerTableBody');
    if (customers.length === 0) {
        tbody.innerHTML = `<tr><td colspan="10" style="text-align: center; padding: 40px; color: #999;">등록된 바이어가 없습니다.</td></tr>`;
        return;
    }

    tbody.innerHTML = customers.map(c => `
        <tr data-id="${c.id}">
            <td><div class="customer-photo">${c.photo ? `<img src="<?= CRM_UPLOAD_URL ?>/${c.photo}" alt="">` : ''}</div></td>
            <td class="text-left">${escapeHtml(c.name || '')}</td>
            <td>${escapeHtml(c.phone || '')}</td>
            <td>${escapeHtml(c.nationality || '')}</td>
            <td>${escapeHtml(c.customer_type || '')}</td>
            <td>${c.whatsapp ? `<span class="whatsapp-icon" onclick="openWhatsApp('${escapeHtml(c.whatsapp)}')">W</span>` : ''}</td>
            <td>${escapeHtml(c.export_country || '')}</td>
            <td title="${escapeHtml(c.latest_comment || '')}">${c.latest_comment ? escapeHtml(c.latest_comment.substring(0, 20)) + (c.latest_comment.length > 20 ? '...' : '') : ''}</td>
            <td>${escapeHtml(c.comment_author || '')}</td>
            <td><button class="detail-btn" onclick="location.href='<?= CRM_URL ?>/pages/international/customer_detail.php?id=${c.id}'">상세보기</button></td>
        </tr>
    `).join('');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
