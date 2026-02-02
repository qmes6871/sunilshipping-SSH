<?php
/**
 * 전체 루트 주의사항
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pageTitle = '전체 루트 주의사항';
$pageSubtitle = '모든 운송 루트의 주의사항 및 안내사항을 확인하세요';

$pdo = getDB();

$statusFilter = $_GET['status'] ?? '';
$routeFilter = $_GET['route'] ?? '';
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
if ($search) {
    $where[] = "(title LIKE ? OR content LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereClause = implode(' AND ', $where);

// 통계 조회
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM " . CRM_ROUTES_TABLE . " WHERE 1=1");
    $stmt->execute();
    $totalAll = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM " . CRM_ROUTES_TABLE . " WHERE status = 'urgent'");
    $stmt->execute();
    $totalUrgent = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM " . CRM_ROUTES_TABLE . " WHERE status = 'important'");
    $stmt->execute();
    $totalImportant = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM " . CRM_ROUTES_TABLE . " WHERE status = 'normal'");
    $stmt->execute();
    $totalNormal = $stmt->fetchColumn();
} catch (Exception $e) {
    $totalAll = $totalUrgent = $totalImportant = $totalNormal = 0;
}

// 목록 조회
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM " . CRM_ROUTES_TABLE . " WHERE {$whereClause}");
    $stmt->execute($params);
    $totalCount = $stmt->fetchColumn();
} catch (Exception $e) {
    $totalCount = 0;
}

$totalPages = ceil($totalCount / $perPage);
$offset = ($page - 1) * $perPage;

try {
    $stmt = $pdo->prepare("SELECT w.*, u.name as creator_name
        FROM " . CRM_ROUTES_TABLE . " w
        LEFT JOIN " . CRM_USERS_TABLE . " u ON w.created_by = u.id
        WHERE {$whereClause}
        ORDER BY FIELD(status, 'urgent', 'important', 'normal'), w.created_at DESC
        LIMIT {$perPage} OFFSET {$offset}");
    $stmt->execute($params);
    $warnings = $stmt->fetchAll();
} catch (Exception $e) {
    $warnings = [];
}

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<style>
/* 페이지 헤더 */
.page-header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:24px;
}
.page-title {
    font-size:28px;
    font-weight:700;
    margin-bottom:4px;
}
.page-subtitle {
    font-size:14px;
    color:#6c757d;
}
.header-buttons {
    display:flex;
    gap:10px;
}
.btn-back {
    padding:10px 20px;
    background:#fff;
    color:#4a90e2;
    border:1px solid #4a90e2;
    border-radius:6px;
    font-size:14px;
    font-weight:600;
    cursor:pointer;
}
.btn-back:hover { background:#e7f3ff; }
.btn-write {
    padding:10px 20px;
    background:#4a90e2;
    color:#fff;
    border:none;
    border-radius:6px;
    font-size:14px;
    font-weight:600;
    cursor:pointer;
}
.btn-write:hover { background:#3a7bc8; }

/* 카드 */
.routes-card {
    background:#fff;
    padding:24px;
    border-radius:8px;
    box-shadow:0 1px 3px rgba(0,0,0,0.1);
}

/* 통계 */
.stats {
    display:flex;
    gap:16px;
    margin-bottom:20px;
}
.stat-item {
    flex:1;
    padding:16px;
    background:#f8f9fa;
    border-radius:6px;
    text-align:center;
}
.stat-label {
    font-size:12px;
    color:#6c757d;
    margin-bottom:4px;
}
.stat-value {
    font-size:24px;
    font-weight:700;
    color:#212529;
}
.stat-value.urgent {
    color:#c92a2a;
}
.stat-value.important {
    color:#d9480f;
}

/* 게시판 툴바 */
.board-toolbar {
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
    padding-bottom:16px;
    border-bottom:1px solid #e9ecef;
    flex-wrap:wrap;
    gap:12px;
}
.board-filters {
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}
.board-filters select,
.board-filters input {
    border:1px solid #ced4da;
    border-radius:6px;
    padding:8px 12px;
    font-size:13px;
    background:#fff;
}
.board-filters select {
    min-width:140px;
}
.board-filters input {
    min-width:200px;
}
.board-actions {
    display:flex;
    gap:8px;
}
.btn-filter {
    padding:8px 16px;
    border:1px solid #ced4da;
    border-radius:6px;
    background:#fff;
    font-size:13px;
    font-weight:600;
    cursor:pointer;
    transition:all 0.2s;
}
.btn-filter:hover {
    background:#f8f9fa;
}
.btn-search {
    padding:8px 16px;
    background:#4a90e2;
    color:#fff;
    border:none;
    border-radius:6px;
    font-size:13px;
    font-weight:600;
    cursor:pointer;
}
.btn-search:hover {
    background:#3a7bc8;
}

/* 게시판 테이블 */
.board-table-wrapper {
    overflow-x:auto;
}
.board-table {
    width:100%;
    border-collapse:collapse;
    font-size:13px;
    table-layout:auto;
}
.board-table thead {
    background:#f1f3f5;
}
.board-table th {
    text-align:left;
    padding:12px 14px;
    font-size:12px;
    color:#6c757d;
    text-transform:uppercase;
    letter-spacing:0.05em;
    font-weight:600;
}
.board-table th:first-child,
.board-table td:first-child {
    text-align:center;
    white-space:nowrap;
    padding:12px 8px;
}
.board-table th:nth-child(2) {
    width:120px;
}
.board-table th:nth-child(4) {
    width:160px;
}
.board-table th:nth-child(5) {
    width:100px;
}
.board-table th:nth-child(6) {
    width:100px;
}
.board-table th:nth-child(7) {
    width:80px;
    text-align:center;
}
.board-table td {
    padding:14px;
    border-top:1px solid #e9ecef;
    background:#fff;
}
.board-table tbody tr {
    cursor:pointer;
}
.board-table tbody tr:hover td {
    background:#f8fbff;
}
.board-status {
    padding:5px 14px;
    border-radius:999px;
    font-size:12px;
    font-weight:600;
    display:inline-block;
    text-align:center;
    white-space:nowrap;
    line-height:1;
    min-width:45px;
}
.board-status.urgent {
    background:#ffe3e3;
    color:#c92a2a;
}
.board-status.important {
    background:#fff4e6;
    color:#d9480f;
}
.board-status.normal {
    background:#e7f5ff;
    color:#1c7ed6;
}
.board-route {
    display:inline-block;
    padding:4px 10px;
    background:#f1f3f5;
    border-radius:4px;
    font-size:12px;
    font-weight:600;
    color:#495057;
}
.board-title {
    font-weight:600;
    color:#212529;
    margin-bottom:4px;
}
.board-desc {
    font-size:12px;
    color:#868e96;
    line-height:1.5;
}
.board-attachment a {
    color:#4a90e2;
    text-decoration:none;
    font-weight:600;
    font-size:12px;
}
.board-attachment a:hover {
    text-decoration:underline;
}

/* 페이지네이션 */
.pagination {
    display:flex;
    justify-content:center;
    align-items:center;
    gap:8px;
    margin-top:24px;
}
.pagination a, .pagination span {
    padding:8px 12px;
    border:1px solid #ced4da;
    border-radius:6px;
    background:#fff;
    font-size:13px;
    text-decoration:none;
    color:#333;
}
.pagination a:hover {
    background:#f8f9fa;
}
.pagination .current {
    background:#4a90e2;
    color:#fff;
    border-color:#4a90e2;
}
.pagination .disabled {
    opacity:0.5;
    cursor:not-allowed;
}

/* 반응형 */
@media (max-width:768px) {
    .page-header {
        flex-direction:column;
        gap:12px;
        align-items:flex-start;
    }
    .board-toolbar {
        flex-direction:column;
        align-items:stretch;
    }
    .board-filters {
        width:100%;
    }
    .board-filters select,
    .board-filters input {
        flex:1;
        min-width:0;
    }
    .stats {
        flex-direction:column;
    }
}
</style>

<!-- 페이지 헤더 -->
<div class="page-header">
    <div>
        <div class="page-title">전체 루트 주의사항</div>
        <div class="page-subtitle">모든 운송 루트의 주의사항 및 안내사항을 확인하세요</div>
    </div>
    <div class="header-buttons">
        <button class="btn-back" onclick="location.href='notices.php'">← 돌아가기</button>
        <button class="btn-write" onclick="location.href='route_form.php'">글쓰기</button>
    </div>
</div>

<div class="routes-card">
    <!-- 통계 -->
    <div class="stats">
        <div class="stat-item">
            <div class="stat-label">전체 공지</div>
            <div class="stat-value"><?= number_format($totalAll) ?></div>
        </div>
        <div class="stat-item">
            <div class="stat-label">긴급</div>
            <div class="stat-value urgent"><?= number_format($totalUrgent) ?></div>
        </div>
        <div class="stat-item">
            <div class="stat-label">중요</div>
            <div class="stat-value important"><?= number_format($totalImportant) ?></div>
        </div>
        <div class="stat-item">
            <div class="stat-label">안내</div>
            <div class="stat-value"><?= number_format($totalNormal) ?></div>
        </div>
    </div>

    <!-- 게시판 툴바 -->
    <form class="board-toolbar" method="GET" id="filterForm">
        <div class="board-filters">
            <select name="status" id="statusFilter" onchange="this.form.submit()">
                <option value="">전체 상태</option>
                <option value="urgent" <?= $statusFilter === 'urgent' ? 'selected' : '' ?>>긴급</option>
                <option value="important" <?= $statusFilter === 'important' ? 'selected' : '' ?>>중요</option>
                <option value="normal" <?= $statusFilter === 'normal' ? 'selected' : '' ?>>안내</option>
            </select>
            <select name="route" id="routeFilter" onchange="this.form.submit()">
                <option value="">전체 루트</option>
                <option value="중앙아시아" <?= $routeFilter === '중앙아시아' ? 'selected' : '' ?>>중앙아시아</option>
                <option value="중동아프리카" <?= $routeFilter === '중동아프리카' ? 'selected' : '' ?>>중동·아프리카</option>
                <option value="러시아" <?= $routeFilter === '러시아' ? 'selected' : '' ?>>러시아</option>
                <option value="유럽" <?= $routeFilter === '유럽' ? 'selected' : '' ?>>유럽</option>
                <option value="동남아시아" <?= $routeFilter === '동남아시아' ? 'selected' : '' ?>>동남아시아</option>
                <option value="국내" <?= $routeFilter === '국내' ? 'selected' : '' ?>>국내 물류</option>
            </select>
            <input type="text" name="search" id="searchInput" placeholder="키워드 검색" value="<?= h($search) ?>">
        </div>
        <div class="board-actions">
            <button type="button" class="btn-filter" onclick="resetFilters()">필터 초기화</button>
            <button type="submit" class="btn-search">검색</button>
        </div>
    </form>

    <!-- 게시판 테이블 -->
    <div class="board-table-wrapper">
        <table class="board-table">
            <thead>
            <tr>
                <th>상태</th>
                <th>루트</th>
                <th>제목 / 내용</th>
                <th>구간 · 지역</th>
                <th>등록자</th>
                <th>등록일</th>
                <th>첨부</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($warnings)): ?>
                <tr><td colspan="7" style="text-align:center; padding:40px; color:#999;">등록된 주의사항이 없습니다.</td></tr>
            <?php else: ?>
                <?php foreach ($warnings as $warning): ?>
                    <tr onclick="viewDetail(<?= $warning['id'] ?>)">
                        <td style="text-align:center;"><span class="board-status <?= $warning['status'] ?>"><?php $statusLabels = ['urgent' => '긴급', 'important' => '중요', 'normal' => '안내']; echo $statusLabels[$warning['status']] ?? '안내'; ?></span></td>
                        <td><span class="board-route"><?= h($warning['route_name']) ?></span></td>
                        <td>
                            <div class="board-title"><?= h($warning['title']) ?></div>
                            <div class="board-desc"><?= h(mb_substr($warning['content'] ?? '', 0, 60)) ?>...</div>
                        </td>
                        <td><?= h($warning['section'] ?? $warning['route_name']) ?></td>
                        <td><?= h($warning['creator_name'] ?? '관리자') ?></td>
                        <td><?= formatDate($warning['created_at'], 'Y-m-d') ?></td>
                        <td style="text-align:center;" class="board-attachment">
                            <?php if ($warning['attachment_path']): ?>
                                <a href="<?= CRM_UPLOAD_URL ?>/<?= h($warning['attachment_path']) ?>" target="_blank" onclick="event.stopPropagation()">다운로드</a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
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
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">« 이전</a>
        <?php else: ?>
            <span class="disabled">« 이전</span>
        <?php endif; ?>

        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <?php if ($i == $page): ?>
                <span class="current"><?= $i ?></span>
            <?php else: ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">다음 »</a>
        <?php else: ?>
            <span class="disabled">다음 »</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- 상세 모달 -->
<div class="modal-overlay" id="detailModal">
    <div class="modal" style="max-width: 700px;">
        <div class="modal-header">
            <h3 id="detailTitle">주의사항</h3>
            <button class="modal-close" onclick="closeModal('detailModal')">&times;</button>
        </div>
        <div class="modal-body" id="detailContent" style="min-height: 200px;"></div>
        <div class="modal-footer">
            <?php if (isAdmin()): ?>
            <button class="btn btn-primary" id="btnEditRoute" onclick="editRoute()">수정</button>
            <button class="btn btn-danger" id="btnDeleteRoute" onclick="deleteRoute()">삭제</button>
            <?php endif; ?>
            <button class="btn btn-secondary" onclick="closeModal('detailModal')">닫기</button>
        </div>
    </div>
</div>

<?php
$pageScripts = <<<SCRIPT
<script>
let currentRouteId = null;

function resetFilters() {
    document.getElementById('statusFilter').value = '';
    document.getElementById('routeFilter').value = '';
    document.getElementById('searchInput').value = '';
    document.getElementById('filterForm').submit();
}

async function viewDetail(id) {
    try {
        const response = await apiGet(CRM_URL + '/api/common/routes.php?id=' + id);
        const data = response.data;
        currentRouteId = id;

        const statusLabels = {urgent: '긴급', important: '중요', normal: '안내'};
        const statusColors = {urgent: '#c92a2a', important: '#d9480f', normal: '#1c7ed6'};

        document.getElementById('detailTitle').textContent = data.title;
        document.getElementById('detailContent').innerHTML = `
            <div style="margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid #eee; display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                <span style="background: #f1f3f5; padding: 4px 10px; border-radius: 4px; font-size: 13px; font-weight: 600;">
                    \${data.route_name}
                </span>
                <span style="color: \${statusColors[data.status] || '#666'}; font-size: 13px; font-weight: 600;">
                    \${statusLabels[data.status] || data.status}
                </span>
                <span style="font-size: 13px; color: #666;">
                    작성: \${data.creator_name || '관리자'} · \${data.created_at?.substring(0, 10)}
                </span>
            </div>
            <div style="line-height: 1.8; white-space: pre-wrap;">\${data.content || '(내용 없음)'}</div>
            \${data.attachment_path ? '<div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #eee;"><a href="' + CRM_UPLOAD_URL + '/' + data.attachment_path + '" target="_blank" style="color: #4a90e2;">📎 첨부파일 다운로드</a></div>' : ''}
        `;

        openModal('detailModal');
    } catch (error) {
        showToast('데이터를 불러올 수 없습니다.', 'error');
    }
}

function editRoute() {
    if (currentRouteId) {
        location.href = 'route_form.php?id=' + currentRouteId;
    }
}

async function deleteRoute() {
    if (!currentRouteId) return;
    if (!confirm('정말 삭제하시겠습니까?')) return;

    try {
        const response = await apiPost(CRM_URL + '/api/common/routes.php', {
            action: 'delete',
            id: currentRouteId
        });

        if (response.success) {
            showToast('삭제되었습니다.', 'success');
            closeModal('detailModal');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(response.message || '삭제 중 오류가 발생했습니다.', 'error');
        }
    } catch (error) {
        showToast('삭제 중 오류가 발생했습니다.', 'error');
    }
}
</script>
SCRIPT;

include dirname(dirname(__DIR__)) . '/includes/footer.php';
?>
