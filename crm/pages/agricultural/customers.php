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
$status = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;

$where = ["1=1"];
$params = [];

if ($search) {
    $where[] = "(company_name LIKE ? OR representative_name LIKE ? OR phone LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($status) {
    $where[] = "status = ?";
    $params[] = $status;
}

$whereClause = implode(' AND ', $where);

// 카운트
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM " . CRM_AGRI_CUSTOMERS_TABLE . " WHERE {$whereClause}");
    $stmt->execute($params);
    $totalCount = $stmt->fetchColumn();
} catch (Exception $e) {
    $totalCount = 0;
}

$totalPages = ceil($totalCount / $perPage);
$offset = ($page - 1) * $perPage;

// 목록 조회
try {
    $stmt = $pdo->prepare("SELECT c.*, u.name as sales_name
        FROM " . CRM_AGRI_CUSTOMERS_TABLE . " c
        LEFT JOIN " . CRM_USERS_TABLE . " u ON c.assigned_sales = u.id
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
    .filter-bar {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
        align-items: center;
        margin-bottom: 24px;
    }

    .search-box {
        flex: 1;
        min-width: 250px;
        display: flex;
        gap: 8px;
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
</style>

<!-- 필터 & 검색 -->
<div class="card" style="padding: 16px; margin-bottom: 24px;">
    <form class="filter-bar" method="GET">
        <div class="search-box">
            <input type="text" name="search" class="form-control" placeholder="회사명, 대표자, 연락처 검색" value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-secondary">검색</button>
        </div>

        <select name="status" class="form-control" style="width: auto;" onchange="this.form.submit()">
            <option value="">전체 상태</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>활성</option>
            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>비활성</option>
        </select>

        <a href="<?= CRM_URL ?>/pages/agricultural/customer_form.php" class="btn btn-primary">+ 고객 등록</a>
    </form>
</div>

<p style="margin-bottom: 16px; color: #666;">총 <strong><?= number_format($totalCount) ?></strong>개 고객사</p>

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
