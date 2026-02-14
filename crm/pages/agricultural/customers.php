<?php
/**
 * 농산물 고객 관리
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pageTitle = '고객 관리';
$pageSubtitle = '농산물 사업 고객';

$pdo = getDB();

// 검색/필터
$search = $_GET['search'] ?? '';
$searchProduct = $_GET['search_product'] ?? '';
$searchRegister = $_GET['search_register'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$status = $_GET['status'] ?? '';
$sortBy = $_GET['sort'] ?? 'newest';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;

$where = ["1=1"];
$params = [];

// 기존 검색 (회사명, 대표자, 연락처)
if ($search) {
    $where[] = "(c.company_name LIKE ? OR c.representative_name LIKE ? OR c.phone LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

// 품목명 검색
if ($searchProduct) {
    $where[] = "c.product_categories LIKE ?";
    $params[] = "%{$searchProduct}%";
}

// 등록자 검색
if ($searchRegister) {
    $where[] = "creator.name LIKE ?";
    $params[] = "%{$searchRegister}%";
}

// 날짜 범위 검색
if ($dateFrom) {
    $where[] = "DATE(c.created_at) >= ?";
    $params[] = $dateFrom;
}
if ($dateTo) {
    $where[] = "DATE(c.created_at) <= ?";
    $params[] = $dateTo;
}

if ($status) {
    $where[] = "c.status = ?";
    $params[] = $status;
}

$whereClause = implode(' AND ', $where);

// 디버깅: 검색 조건 확인 (개발 중에만 사용, 나중에 제거)
$debugMode = isset($_GET['debug']) && $_GET['debug'] === '1';
if ($debugMode) {
    echo "<pre style='background:#f0f0f0;padding:10px;margin:10px 0;font-size:12px;'>";
    echo "검색 파라미터:\n";
    echo "- search: " . htmlspecialchars($search) . "\n";
    echo "- search_product: " . htmlspecialchars($searchProduct) . "\n";
    echo "- search_register: " . htmlspecialchars($searchRegister) . "\n";
    echo "- date_from: " . htmlspecialchars($dateFrom) . "\n";
    echo "- date_to: " . htmlspecialchars($dateTo) . "\n";
    echo "- status: " . htmlspecialchars($status) . "\n";
    echo "- sort: " . htmlspecialchars($sortBy) . "\n";
    echo "\nWHERE 절: " . htmlspecialchars($whereClause) . "\n";
    echo "파라미터: " . print_r($params, true);
    echo "</pre>";
}

// 정렬
switch ($sortBy) {
    case 'oldest':
        $orderBy = 'c.created_at ASC';
        break;
    case 'name_asc':
        $orderBy = 'c.company_name ASC';
        break;
    case 'name_desc':
        $orderBy = 'c.company_name DESC';
        break;
    case 'newest':
    default:
        $orderBy = 'c.created_at DESC';
        break;
}

// 카운트
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM " . CRM_AGRI_CUSTOMERS_TABLE . " c
        LEFT JOIN " . CRM_USERS_TABLE . " creator ON c.created_by = creator.id
        WHERE {$whereClause}");
    $stmt->execute($params);
    $totalCount = $stmt->fetchColumn();
} catch (Exception $e) {
    $totalCount = 0;
}

$totalPages = ceil($totalCount / $perPage);
$offset = ($page - 1) * $perPage;

// 목록 조회
try {
    $stmt = $pdo->prepare("SELECT c.*, u.name as sales_name, creator.name as creator_name
        FROM " . CRM_AGRI_CUSTOMERS_TABLE . " c
        LEFT JOIN " . CRM_USERS_TABLE . " u ON c.assigned_sales = u.id
        LEFT JOIN " . CRM_USERS_TABLE . " creator ON c.created_by = creator.id
        WHERE {$whereClause}
        ORDER BY {$orderBy}
        LIMIT {$perPage} OFFSET {$offset}");
    $stmt->execute($params);
    $customers = $stmt->fetchAll();
} catch (Exception $e) {
    $customers = [];
}

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<style>
    .filter-bar {
        display: grid;
        grid-template-columns: 1fr auto auto auto;
        gap: 12px;
        align-items: center;
    }

    .search-box {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .search-box input {
        flex: 1;
        min-width: 200px;
        height: 40px;
        padding: 0 14px;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        font-size: 14px;
    }

    .search-box input:focus {
        outline: none;
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }

    .filter-bar .btn {
        height: 40px;
        padding: 0 20px;
        font-size: 14px;
        font-weight: 500;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .filter-bar .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .filter-bar .btn-secondary:hover {
        background: #5c636a;
    }

    .filter-bar .btn-primary {
        background: #10b981;
        color: white;
    }

    .filter-bar .btn-primary:hover {
        background: #059669;
    }

    .filter-bar select {
        height: 40px;
        padding: 0 32px 0 14px;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        font-size: 14px;
        background: white url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23666' d='M6 8L1 3h10z'/%3E%3C/svg%3E") no-repeat right 12px center;
        appearance: none;
        cursor: pointer;
        min-width: 120px;
    }

    .filter-bar select:focus {
        outline: none;
        border-color: #10b981;
    }

    @media (max-width: 768px) {
        .filter-bar {
            grid-template-columns: 1fr;
        }

        .search-box {
            order: 1;
        }

        .filter-bar select {
            order: 2;
            width: 100%;
        }

        .filter-bar .btn-primary {
            order: 3;
            width: 100%;
        }
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
    }

    .data-table th, .data-table td {
        padding: 14px 16px;
        text-align: left;
        border-bottom: 1px solid #f0f0f0;
    }

    .data-table th {
        font-weight: 600;
        color: #666;
        background: #f8f9fa;
    }

    .data-table tr:hover {
        background: #f8f9fa;
    }

    .company-cell {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .company-icon {
        width: 40px;
        height: 40px;
        background: #d1fae5;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .company-name {
        font-weight: 500;
        color: var(--text-dark);
    }

    .company-rep {
        font-size: 13px;
        color: #666;
    }

    .status-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 12px;
    }

    .status-active { background: #d1fae5; color: #059669; }
    .status-inactive { background: #fee2e2; color: #dc2626; }

    .action-btns {
        display: flex;
        gap: 8px;
    }

    .action-btns button {
        padding: 6px 12px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 12px;
    }

    .btn-view { background: #e0e7ff; color: #4338ca; }
    .btn-edit { background: #f5f5f5; color: #666; }

    .pagination {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-top: 32px;
    }

    .pagination a, .pagination span {
        padding: 8px 14px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 14px;
    }

    .pagination a { background: #f5f5f5; color: #666; }
    .pagination a:hover { background: #e0e0e0; }
    .pagination .current { background: var(--primary); color: white; }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }

    /* 상세 검색 영역 */
    .btn-outline {
        height: 40px;
        padding: 0 16px;
        font-size: 14px;
        font-weight: 500;
        border-radius: 6px;
        border: 1px solid #dee2e6;
        background: white;
        color: #495057;
        cursor: pointer;
        white-space: nowrap;
    }

    .btn-outline:hover {
        background: #f8f9fa;
        border-color: #10b981;
        color: #10b981;
    }

    .advanced-search {
        display: none;
        padding: 16px;
        background: #f8f9fa;
        border-radius: 8px;
        margin-top: 12px;
    }

    .advanced-search.show {
        display: block;
    }

    .search-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 16px;
    }

    .search-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .search-label {
        font-size: 12px;
        font-weight: 500;
        color: #6c757d;
    }

    .search-input {
        height: 38px;
        padding: 0 12px;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        font-size: 14px;
        background: white;
    }

    .search-input:focus {
        outline: none;
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }

    .date-range {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .date-range input {
        flex: 1;
        height: 38px;
        padding: 0 8px;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        font-size: 13px;
    }

    .date-range span {
        color: #adb5bd;
    }

    .search-actions {
        display: flex;
        justify-content: center;
        gap: 12px;
    }

    .btn-search-primary {
        padding: 10px 32px;
        background: #10b981;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
    }

    .btn-search-primary:hover {
        background: #059669;
    }

    .btn-search-secondary {
        padding: 10px 32px;
        background: #6c757d;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
    }

    .btn-search-secondary:hover {
        background: #5c636a;
    }

    @media (max-width: 1024px) {
        .search-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .search-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<!-- 필터 & 검색 -->
<div class="card" style="padding: 16px; margin-bottom: 24px;">
    <form method="GET" id="searchForm">
        <!-- 기본 검색 영역 -->
        <div class="filter-bar" style="margin-bottom: 12px;">
            <div class="search-box">
                <input type="text" name="search" class="form-control" placeholder="회사명, 대표자, 연락처 검색" value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-secondary">검색</button>
            </div>

            <select name="status" class="form-control" style="width: auto;">
                <option value="">전체 상태</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>활성</option>
                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>비활성</option>
            </select>

            <button type="button" class="btn btn-outline" onclick="toggleAdvancedSearch()" id="advancedSearchToggle">
                상세 검색 ▼
            </button>

            <a href="<?= CRM_URL ?>/pages/agricultural/customer_form.php" class="btn btn-primary">+ 고객 등록</a>
        </div>

        <!-- 상세 검색 영역 -->
        <div id="advancedSearchArea" class="advanced-search <?= ($searchProduct || $searchRegister || $dateFrom || $dateTo || $sortBy !== 'newest') ? 'show' : '' ?>">
            <div class="search-grid">
                <div class="search-field">
                    <label class="search-label">품목명</label>
                    <input type="text" name="search_product" class="search-input" placeholder="품목명 검색" value="<?= htmlspecialchars($searchProduct) ?>">
                </div>
                <div class="search-field">
                    <label class="search-label">등록자</label>
                    <input type="text" name="search_register" class="search-input" placeholder="등록자명 검색" value="<?= htmlspecialchars($searchRegister) ?>">
                </div>
                <div class="search-field">
                    <label class="search-label">등록일</label>
                    <div class="date-range">
                        <input type="date" name="date_from" class="search-input" value="<?= htmlspecialchars($dateFrom) ?>">
                        <span>~</span>
                        <input type="date" name="date_to" class="search-input" value="<?= htmlspecialchars($dateTo) ?>">
                    </div>
                </div>
                <div class="search-field">
                    <label class="search-label">정렬</label>
                    <select name="sort" class="search-input">
                        <option value="newest" <?= $sortBy === 'newest' ? 'selected' : '' ?>>등록일 최신순</option>
                        <option value="oldest" <?= $sortBy === 'oldest' ? 'selected' : '' ?>>등록일 오래된순</option>
                        <option value="name_asc" <?= $sortBy === 'name_asc' ? 'selected' : '' ?>>이름 가나다순</option>
                        <option value="name_desc" <?= $sortBy === 'name_desc' ? 'selected' : '' ?>>이름 역순</option>
                    </select>
                </div>
            </div>
            <div class="search-actions">
                <button type="submit" class="btn btn-search-primary">검색</button>
                <button type="button" class="btn btn-search-secondary" onclick="resetSearch()">초기화</button>
            </div>
        </div>
    </form>
</div>

<script>
function toggleAdvancedSearch() {
    var area = document.getElementById('advancedSearchArea');
    var btn = document.getElementById('advancedSearchToggle');
    if (area.classList.contains('show')) {
        area.classList.remove('show');
        btn.innerHTML = '상세 검색 ▼';
    } else {
        area.classList.add('show');
        btn.innerHTML = '상세 검색 ▲';
    }
}

function resetSearch() {
    window.location.href = '<?= CRM_URL ?>/pages/agricultural/customers.php';
}

// 상세 검색이 열려있으면 버튼 텍스트 변경
document.addEventListener('DOMContentLoaded', function() {
    var area = document.getElementById('advancedSearchArea');
    var btn = document.getElementById('advancedSearchToggle');
    if (area && area.classList.contains('show')) {
        btn.innerHTML = '상세 검색 ▲';
    }

    // 폼 제출 시 디버깅
    var form = document.getElementById('searchForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('검색 폼 제출됨');
            console.log('검색어:', form.querySelector('[name="search"]').value);
            console.log('상태:', form.querySelector('[name="status"]').value);
            console.log('품목명:', form.querySelector('[name="search_product"]').value);
            console.log('등록자:', form.querySelector('[name="search_register"]').value);
            console.log('시작일:', form.querySelector('[name="date_from"]').value);
            console.log('종료일:', form.querySelector('[name="date_to"]').value);
            console.log('정렬:', form.querySelector('[name="sort"]').value);
        });
    }
});
</script>

<?php
$hasSearchCondition = $search || $searchProduct || $searchRegister || $dateFrom || $dateTo || $status;
?>
<p style="margin-bottom: 16px; color: #666;">
    <?php if ($hasSearchCondition): ?>
        검색 결과: <strong><?= number_format($totalCount) ?></strong>개 고객사
        <a href="<?= CRM_URL ?>/pages/agricultural/customers.php" style="margin-left: 10px; color: #10b981; text-decoration: none;">[검색 초기화]</a>
    <?php else: ?>
        총 <strong><?= number_format($totalCount) ?></strong>개 고객사
    <?php endif; ?>
</p>

<!-- 고객 목록 테이블 -->
<div class="card" style="padding: 0; overflow: hidden;">
    <?php if (empty($customers)): ?>
        <div class="empty-state">
            <p style="font-size: 48px; margin-bottom: 16px;">🏪</p>
            <p>등록된 고객이 없습니다.</p>
        </div>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>고객사</th>
                    <th>연락처</th>
                    <th>담당자</th>
                    <th>상태</th>
                    <th>관리</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td>
                            <div class="company-cell">
                                <div class="company-icon">🏪</div>
                                <div>
                                    <div class="company-name"><?= htmlspecialchars($customer['company_name']) ?></div>
                                    <?php if ($customer['representative_name']): ?>
                                        <div class="company-rep">대표: <?= htmlspecialchars($customer['representative_name']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($customer['phone'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($customer['sales_name'] ?? '-') ?></td>
                        <td>
                            <span class="status-badge status-<?= $customer['status'] ?>">
                                <?= $customer['status'] === 'active' ? '활성' : '비활성' ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-btns">
                                <button class="btn-view" onclick="location.href='customer_detail.php?id=<?= $customer['id'] ?>'">상세</button>
                                <button class="btn-edit" onclick="location.href='customer_form.php?id=<?= $customer['id'] ?>'">수정</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- 페이지네이션 -->
<?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">이전</a>
        <?php endif; ?>

        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <?php if ($i == $page): ?>
                <span class="current"><?= $i ?></span>
            <?php else: ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">다음</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
