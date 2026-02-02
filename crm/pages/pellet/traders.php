<?php
/**
 * 우드펠렛 거래처 관리
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pageTitle = '거래처 관리';
$pageSubtitle = '우드펠렛 거래처';

$pdo = getDB();

$search = $_GET['search'] ?? '';
$tradeType = $_GET['trade_type'] ?? '';
$status = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;

$where = ["1=1"];
$params = [];

if ($search) {
    $where[] = "(company_name LIKE ? OR contact_person LIKE ? OR phone LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($tradeType) {
    $where[] = "trade_type = ?";
    $params[] = $tradeType;
}
if ($status) {
    $where[] = "status = ?";
    $params[] = $status;
}

$whereClause = implode(' AND ', $where);

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM " . CRM_PELLET_TRADERS_TABLE . " WHERE {$whereClause}");
    $stmt->execute($params);
    $totalCount = $stmt->fetchColumn();
} catch (Exception $e) {
    $totalCount = 0;
}

$totalPages = ceil($totalCount / $perPage);
$offset = ($page - 1) * $perPage;

try {
    $stmt = $pdo->prepare("SELECT t.*, u.name as sales_name
        FROM " . CRM_PELLET_TRADERS_TABLE . " t
        LEFT JOIN " . CRM_USERS_TABLE . " u ON t.assigned_sales = u.id
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

    .traders-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 20px;
    }

    .trader-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        cursor: pointer;
        transition: all 0.2s;
    }

    .trader-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        transform: translateY(-2px);
    }

    .trader-header {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 16px;
    }

    .trader-icon {
        width: 56px;
        height: 56px;
        background: #ffedd5;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .trader-name {
        font-size: 18px;
        font-weight: 600;
        color: var(--text-dark);
    }

    .trader-type {
        font-size: 13px;
        color: #666;
    }

    .trader-info {
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

    .trader-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 12px;
        border-top: 1px solid #f0f0f0;
    }

    .status-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 12px;
    }

    .status-active { background: #d1fae5; color: #059669; }
    .status-inactive { background: #fee2e2; color: #dc2626; }
    .status-pending { background: #fef3c7; color: #d97706; }

    .trader-volume {
        font-size: 13px;
        color: #888;
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

    .pagination a { background: #f5f5f5; color: #666; }
    .pagination a:hover { background: #e0e0e0; }
    .pagination .current { background: var(--primary); color: white; }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #999;
        grid-column: 1 / -1;
    }
</style>

<div class="card" style="padding: 16px; margin-bottom: 24px;">
    <form class="filter-bar" method="GET">
        <div class="search-box">
            <input type="text" name="search" class="form-control" placeholder="회사명, 담당자, 연락처 검색" value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-secondary">검색</button>
        </div>

        <select name="trade_type" class="form-control" style="width: auto;" onchange="this.form.submit()">
            <option value="">전체 유형</option>
            <option value="online" <?= $tradeType === 'online' ? 'selected' : '' ?>>온라인</option>
            <option value="offline_wholesale" <?= $tradeType === 'offline_wholesale' ? 'selected' : '' ?>>오프라인(도매)</option>
            <option value="offline_retail" <?= $tradeType === 'offline_retail' ? 'selected' : '' ?>>오프라인(소매)</option>
            <option value="bulk" <?= $tradeType === 'bulk' ? 'selected' : '' ?>>벌크</option>
        </select>

        <select name="status" class="form-control" style="width: auto;" onchange="this.form.submit()">
            <option value="">전체 상태</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>활성</option>
            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>비활성</option>
            <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>대기</option>
        </select>

        <a href="trader_form.php" class="btn btn-primary">+ 거래처 등록</a>
    </form>
</div>

<p style="margin-bottom: 16px; color: #666;">총 <strong><?= number_format($totalCount) ?></strong>개 거래처</p>

<div class="traders-grid">
    <?php if (empty($traders)): ?>
        <div class="empty-state">
            <p style="font-size: 48px; margin-bottom: 16px;">🏭</p>
            <p>등록된 거래처가 없습니다.</p>
        </div>
    <?php else: ?>
        <?php foreach ($traders as $trader): ?>
            <?php
            $typeLabels = [
                'online' => '온라인',
                'offline_wholesale' => '오프라인(도매)',
                'offline_retail' => '오프라인(소매)',
                'bulk' => '벌크'
            ];
            $typeIcons = [
                'online' => '🛒',
                'offline_wholesale' => '🏢',
                'offline_retail' => '🏪',
                'bulk' => '🚛'
            ];
            ?>
            <div class="trader-card" onclick="location.href='trader_detail.php?id=<?= $trader['id'] ?>'">
                <div class="trader-header">
                    <div class="trader-icon"><?= $typeIcons[$trader['trade_type']] ?? '🏭' ?></div>
                    <div>
                        <div class="trader-name"><?= htmlspecialchars($trader['company_name']) ?></div>
                        <div class="trader-type"><?= $typeLabels[$trader['trade_type']] ?? $trader['trade_type'] ?></div>
                    </div>
                </div>

                <div class="trader-info">
                    <?php if ($trader['contact_person']): ?>
                        <div class="info-row">
                            <span>👤</span> <?= htmlspecialchars($trader['contact_person']) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($trader['phone']): ?>
                        <div class="info-row">
                            <span>📱</span> <?= htmlspecialchars($trader['phone']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="trader-footer">
                    <span class="status-badge status-<?= $trader['status'] ?>">
                        <?php
                        $statusLabels = ['active' => '활성', 'inactive' => '비활성', 'pending' => '대기'];
                        echo $statusLabels[$trader['status']] ?? $trader['status'];
                        ?>
                    </span>
                    <?php if ($trader['annual_volume']): ?>
                        <span class="trader-volume">연간 <?= number_format($trader['annual_volume'], 1) ?>톤</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

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
