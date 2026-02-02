<?php
/**
 * 루트별 주의사항 전체보기
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pageTitle = '루트별 주의사항';
$pageSubtitle = '긴급 이슈부터 일반 안내까지 전체 주의사항을 한눈에 확인합니다.';

$pdo = getDB();

// 필터
$statusFilter = $_GET['status'] ?? '';
$routeFilter = $_GET['route'] ?? '';
$periodFilter = $_GET['period'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;

$where = ["1=1"];
$params = [];

if ($statusFilter) {
    $where[] = "status = ?";
    $params[] = $statusFilter;
}
if ($routeFilter) {
    $where[] = "route_name LIKE ?";
    $params[] = "%{$routeFilter}%";
}
if ($periodFilter === '7days') {
    $where[] = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($periodFilter === '1month') {
    $where[] = "created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
}
if ($search) {
    $where[] = "(title LIKE ? OR content LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereClause = implode(' AND ', $where);

// 총 개수
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM " . CRM_ROUTES_TABLE . " WHERE {$whereClause}");
    $stmt->execute($params);
    $totalCount = $stmt->fetchColumn();
} catch (Exception $e) {
    $totalCount = 0;
}

$totalPages = ceil($totalCount / $perPage);
$offset = ($page - 1) * $perPage;

// 목록 조회
try {
    $stmt = $pdo->prepare("SELECT r.*, u.name as author_name
        FROM " . CRM_ROUTES_TABLE . " r
        LEFT JOIN " . CRM_USERS_TABLE . " u ON r.created_by = u.id
        WHERE {$whereClause}
        ORDER BY
            CASE r.status
                WHEN 'urgent' THEN 1
                WHEN 'important' THEN 2
                ELSE 3
            END,
            r.created_at DESC
        LIMIT {$perPage} OFFSET {$offset}");
    $stmt->execute($params);
    $warnings = $stmt->fetchAll();
} catch (Exception $e) {
    $warnings = [];
}

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
a { text-decoration: none; color: inherit; }

.container { max-width: 1400px; margin: 0 auto; padding: 32px 24px 80px; }

.page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; gap: 16px; }
.page-title { font-size: 32px; font-weight: 700; margin-bottom: 6px; }
.page-sub { font-size: 14px; color: #6c757d; }
.page-actions { display: flex; gap: 10px; }

.btn { border-radius: 8px; padding: 10px 18px; font-size: 14px; font-weight: 600; border: 1px solid transparent; cursor: pointer; text-decoration: none; }
.btn-primary { background: #4a90e2; color: #fff; }
.btn-primary:hover { background: #3a7bc8; }
.btn-outline { border: 1px solid #ced4da; background: #fff; color: #495057; }
.btn-outline:hover { background: #f8f9fa; }

.board { background: #fff; border-radius: 14px; box-shadow: 0 10px 35px rgba(0,0,0,0.08); padding: 24px; }

.board-toolbar { display: flex; flex-wrap: wrap; gap: 14px; padding-bottom: 20px; border-bottom: 1px solid #e9ecef; margin-bottom: 20px; }
.filter-group { display: flex; gap: 10px; flex-wrap: wrap; }
.filter-group select, .filter-group input {
    border: 1px solid #ced4da;
    border-radius: 8px;
    padding: 10px 12px;
    font-size: 14px;
    background: #fff;
    min-width: 150px;
}
.filter-group input { min-width: 260px; }
.filter-actions { margin-left: auto; display: flex; gap: 10px; flex-wrap: wrap; }

.board-table-wrapper { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: 14px; }
thead { background: #f1f3f5; }
th { text-align: left; padding: 12px; font-size: 12px; letter-spacing: 0.04em; text-transform: uppercase; color: #6c757d; }
td { padding: 16px 12px; border-top: 1px solid #edf0f2; background: #fff; }
tbody tr:hover td { background: #f8fbff; }
tbody tr { cursor: pointer; }

.status { padding: 5px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; }
.status.urgent { background: #ffe3e3; color: #c92a2a; }
.status.important { background: #fff4e6; color: #d9480f; }
.status.normal { background: #e7f5ff; color: #1c7ed6; }

.title { font-weight: 600; margin-bottom: 6px; color: #212529; }
.desc { font-size: 13px; color: #868e96; line-height: 1.5; }

.pill { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 999px; font-size: 12px; background: #f1f3f5; color: #495057; margin-right: 6px; }

.attachment a { color: #4a90e2; font-weight: 600; }
.attachment a:hover { text-decoration: underline; }

.pagination { display: flex; justify-content: center; gap: 8px; margin-top: 24px; flex-wrap: wrap; }
.pagination a, .pagination span {
    border: 1px solid #dee2e6;
    background: #fff;
    padding: 8px 14px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    text-decoration: none;
    color: #495057;
}
.pagination a:hover { background: #f8f9fa; }
.pagination .active { background: #4a90e2; border-color: #4a90e2; color: #fff; }

.empty-state { text-align: center; padding: 60px 20px; color: #6c757d; }

@media (max-width: 768px) {
    .page-header { flex-direction: column; align-items: flex-start; }
    .filter-group input { min-width: 160px; }
    table { min-width: 800px; }
}
</style>

<div class="container">
    <div class="page-header">
        <div>
            <div class="page-title">루트별 주의사항</div>
            <div class="page-sub">긴급 이슈부터 일반 안내까지 전체 주의사항을 한눈에 확인합니다.</div>
        </div>
        <div class="page-actions">
            <a href="routes.php" class="btn btn-outline">카드뷰</a>
            <a href="route_form.php" class="btn btn-primary">새 주의사항 등록</a>
        </div>
    </div>

    <div class="board">
        <form class="board-toolbar" method="GET">
            <div class="filter-group">
                <select name="status" onchange="this.form.submit()">
                    <option value="">상태 전체</option>
                    <option value="urgent" <?= $statusFilter === 'urgent' ? 'selected' : '' ?>>긴급</option>
                    <option value="important" <?= $statusFilter === 'important' ? 'selected' : '' ?>>중요</option>
                    <option value="normal" <?= $statusFilter === 'normal' ? 'selected' : '' ?>>안내</option>
                </select>
                <select name="route" onchange="this.form.submit()">
                    <option value="">루트 전체</option>
                    <option value="중앙아시아" <?= $routeFilter === '중앙아시아' ? 'selected' : '' ?>>중앙아시아 철도</option>
                    <option value="중동" <?= $routeFilter === '중동' ? 'selected' : '' ?>>중동·아프리카 해상</option>
                    <option value="국내" <?= $routeFilter === '국내' ? 'selected' : '' ?>>국내 물류</option>
                    <option value="러시아" <?= $routeFilter === '러시아' ? 'selected' : '' ?>>러시아 육로</option>
                    <option value="유럽" <?= $routeFilter === '유럽' ? 'selected' : '' ?>>유럽 항공</option>
                </select>
                <select name="period" onchange="this.form.submit()">
                    <option value="">등록 기간</option>
                    <option value="7days" <?= $periodFilter === '7days' ? 'selected' : '' ?>>최근 7일</option>
                    <option value="1month" <?= $periodFilter === '1month' ? 'selected' : '' ?>>최근 1개월</option>
                </select>
                <input type="text" name="search" placeholder="제목, 내용 검색" value="<?= h($search) ?>">
            </div>
            <div class="filter-actions">
                <a href="warnings.php" class="btn btn-outline">초기화</a>
                <button type="submit" class="btn btn-primary">검색</button>
            </div>
        </form>

        <?php if (empty($warnings)): ?>
            <div class="empty-state">
                <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;">📋</div>
                <p>등록된 주의사항이 없습니다.</p>
            </div>
        <?php else: ?>
            <div class="board-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>상태</th>
                            <th>제목 / 내용</th>
                            <th>루트</th>
                            <th>등록자</th>
                            <th>등록일</th>
                            <th>첨부</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($warnings as $warning): ?>
                            <tr onclick="location.href='route_form.php?id=<?= $warning['id'] ?>'">
                                <td>
                                    <?php
                                    $statusClass = 'normal';
                                    $statusText = '안내';
                                    if ($warning['status'] === 'urgent') {
                                        $statusClass = 'urgent';
                                        $statusText = '긴급';
                                    } elseif ($warning['status'] === 'important') {
                                        $statusClass = 'important';
                                        $statusText = '중요';
                                    }
                                    ?>
                                    <span class="status <?= $statusClass ?>"><?= $statusText ?></span>
                                </td>
                                <td>
                                    <div class="title"><?= h($warning['title']) ?></div>
                                    <div class="desc"><?= h(mb_substr($warning['content'] ?? '', 0, 80)) ?>...</div>
                                </td>
                                <td>
                                    <span class="pill"><?= h($warning['route_name']) ?></span>
                                </td>
                                <td><?= h($warning['author_name'] ?? '관리자') ?></td>
                                <td><?= formatDate($warning['created_at'], 'Y-m-d') ?></td>
                                <td class="attachment">
                                    <?php if (!empty($warning['attachment_path'])): ?>
                                        <a href="<?= CRM_UPLOAD_URL ?>/<?= h($warning['attachment_path']) ?>" target="_blank" onclick="event.stopPropagation()">파일보기</a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">«</a>
                    <?php endif; ?>
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">»</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
