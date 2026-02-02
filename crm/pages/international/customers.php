<?php
/**
 * 국제물류 바이어 관리
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pageTitle = '바이어 관리';
$pageSubtitle = '국제물류 바이어 목록';

$pdo = getDB();

// 검색/필터 파라미터
$search = $_GET['search'] ?? '';
$nationality = $_GET['nationality'] ?? '';
$status = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;

// 쿼리 빌드
$where = ["1=1"];
$params = [];

if ($search) {
    $where[] = "(c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($nationality) {
    $where[] = "c.nationality = ?";
    $params[] = $nationality;
}

if ($status) {
    $where[] = "c.status = ?";
    $params[] = $status;
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

// 바이어 목록 조회
try {
    $stmt = $pdo->prepare("SELECT c.*, u.name as sales_name
        FROM " . CRM_INTL_CUSTOMERS_TABLE . " c
        LEFT JOIN " . CRM_USERS_TABLE . " u ON c.assigned_sales = u.id
        WHERE {$whereClause}
        ORDER BY c.created_at DESC
        LIMIT {$perPage} OFFSET {$offset}");
    $stmt->execute($params);
    $customers = $stmt->fetchAll();
} catch (Exception $e) {
    $customers = [];
}

// 국적 목록
try {
    $stmt = $pdo->query("SELECT DISTINCT nationality FROM " . CRM_INTL_CUSTOMERS_TABLE . " WHERE nationality IS NOT NULL AND nationality != '' ORDER BY nationality");
    $nationalities = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $nationalities = [];
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

    .search-box input {
        flex: 1;
    }

    .filter-selects {
        display: flex;
        gap: 8px;
    }

    .filter-selects select {
        min-width: 120px;
    }

    .customer-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }

    .customer-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        cursor: pointer;
        transition: all 0.2s;
    }

    .customer-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        transform: translateY(-2px);
    }

    .customer-header {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 16px;
    }

    .customer-photo {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: #f5f5f5;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        overflow: hidden;
    }

    .customer-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .customer-name {
        font-size: 18px;
        font-weight: 600;
        color: var(--text-dark);
    }

    .customer-nationality {
        font-size: 13px;
        color: #666;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .customer-info {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 16px;
    }

    .info-row {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: #666;
    }

    .info-row .icon {
        width: 20px;
        text-align: center;
    }

    .customer-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 12px;
        border-top: 1px solid #f0f0f0;
    }

    .customer-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }

    .status-active { background: #d1fae5; color: #059669; }
    .status-inactive { background: #fee2e2; color: #dc2626; }
    .status-pending { background: #fef3c7; color: #d97706; }

    .customer-sales {
        font-size: 13px;
        color: #888;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #999;
        grid-column: 1 / -1;
    }

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

    .pagination a {
        background: #f5f5f5;
        color: #666;
    }

    .pagination a:hover {
        background: #e0e0e0;
    }

    .pagination .current {
        background: var(--primary);
        color: white;
    }
</style>

<!-- 필터 & 검색 -->
<div class="card" style="padding: 16px; margin-bottom: 24px;">
    <form class="filter-bar" method="GET">
        <div class="search-box">
            <input type="text" name="search" class="form-control" placeholder="이름, 이메일, 연락처 검색" value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-secondary">검색</button>
        </div>

        <div class="filter-selects">
            <select name="nationality" class="form-control" onchange="this.form.submit()">
                <option value="">전체 국적</option>
                <?php foreach ($nationalities as $nat): ?>
                    <option value="<?= htmlspecialchars($nat) ?>" <?= $nationality === $nat ? 'selected' : '' ?>><?= htmlspecialchars($nat) ?></option>
                <?php endforeach; ?>
            </select>

            <select name="status" class="form-control" onchange="this.form.submit()">
                <option value="">전체 상태</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>활성</option>
                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>비활성</option>
                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>대기</option>
            </select>
        </div>

        <a href="<?= CRM_URL ?>/pages/international/customer_form.php" class="btn btn-primary">+ 바이어 등록</a>
    </form>
</div>

<!-- 결과 카운트 -->
<p style="margin-bottom: 16px; color: #666;">총 <strong><?= number_format($totalCount) ?></strong>명의 바이어</p>

<!-- 바이어 목록 -->
<div class="customer-grid">
    <?php if (empty($customers)): ?>
        <div class="empty-state">
            <p style="font-size: 48px; margin-bottom: 16px;">👥</p>
            <p>등록된 바이어가 없습니다.</p>
        </div>
    <?php else: ?>
        <?php foreach ($customers as $customer): ?>
            <div class="customer-card" onclick="location.href='<?= CRM_URL ?>/pages/international/customer_detail.php?id=<?= $customer['id'] ?>'">
                <div class="customer-header">
                    <div class="customer-photo">
                        <?php if ($customer['photo']): ?>
                            <img src="<?= CRM_UPLOAD_URL ?>/<?= htmlspecialchars($customer['photo']) ?>" alt="">
                        <?php else: ?>
                            👤
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="customer-name"><?= htmlspecialchars($customer['name']) ?></div>
                        <div class="customer-nationality">
                            🌍 <?= htmlspecialchars($customer['nationality'] ?? '-') ?>
                            <?php if ($customer['export_country']): ?>
                                → <?= htmlspecialchars($customer['export_country']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="customer-info">
                    <?php if ($customer['phone'] || $customer['whatsapp']): ?>
                        <div class="info-row">
                            <span class="icon">📱</span>
                            <?= htmlspecialchars($customer['phone'] ?: $customer['whatsapp']) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($customer['email']): ?>
                        <div class="info-row">
                            <span class="icon">📧</span>
                            <?= htmlspecialchars($customer['email']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="customer-footer">
                    <span class="customer-status status-<?= $customer['status'] ?>">
                        <?php
                        $statusLabels = ['active' => '활성', 'inactive' => '비활성', 'pending' => '대기'];
                        echo $statusLabels[$customer['status']] ?? $customer['status'];
                        ?>
                    </span>
                    <?php if ($customer['sales_name']): ?>
                        <span class="customer-sales">담당: <?= htmlspecialchars($customer['sales_name']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- 페이지네이션 -->
<?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">이전</a>
        <?php endif; ?>

        <?php
        $start = max(1, $page - 2);
        $end = min($totalPages, $page + 2);
        for ($i = $start; $i <= $end; $i++):
        ?>
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
