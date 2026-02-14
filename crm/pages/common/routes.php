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
$periodFilter = $_GET['period'] ?? '';
$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;

$where = ["1=1"];
$params = [];

// 상태(유형) 필터
if ($statusFilter !== '') {
    $where[] = "status = ?";
    $params[] = $statusFilter;
}

// 루트 필터
if ($routeFilter !== '') {
    $where[] = "route_name = ?";
    $params[] = $routeFilter;
}

// 기간 필터
if ($periodFilter === '7days') {
    $where[] = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($periodFilter === '1month') {
    $where[] = "created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
} elseif ($periodFilter === '3months') {
    $where[] = "created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
}

// 키워드 검색
if ($search !== '') {
    $where[] = "(title LIKE ? OR content LIKE ? OR section LIKE ?)";
    $params[] = "%{$search}%";
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
    display:grid;
    grid-template-columns:repeat(4, 1fr);
    gap:16px;
    margin-bottom:24px;
}
.stat-item {
    padding:20px 16px;
    background:linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
    border-radius:12px;
    text-align:center;
    border:1px solid #e9ecef;
    transition:all 0.2s ease;
}
.stat-item:hover {
    transform:translateY(-2px);
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
}
.stat-label {
    font-size:13px;
    color:#6c757d;
    margin-bottom:8px;
    font-weight:500;
}
.stat-value {
    font-size:32px;
    font-weight:800;
    color:#212529;
    line-height:1;
}
.stat-item.urgent {
    background:linear-gradient(135deg, #fff5f5 0%, #fff 100%);
    border-color:#ffc9c9;
}
.stat-item.urgent .stat-value {
    color:#c92a2a;
}
.stat-item.important {
    background:linear-gradient(135deg, #fff9db 0%, #fff 100%);
    border-color:#ffe066;
}
.stat-item.important .stat-value {
    color:#d9480f;
}
.stat-item.normal {
    background:linear-gradient(135deg, #e7f5ff 0%, #fff 100%);
    border-color:#a5d8ff;
}
.stat-item.normal .stat-value {
    color:#1c7ed6;
}

/* 게시판 툴바 */
.board-toolbar {
    display:grid;
    grid-template-columns:1fr auto;
    gap:16px;
    align-items:center;
    margin-bottom:24px;
    padding-bottom:20px;
    border-bottom:1px solid #e9ecef;
}
.board-filters {
    display:flex;
    gap:12px;
    flex-wrap:wrap;
    align-items:center;
}
.board-filters select,
.board-filters input {
    height:42px;
    border:1px solid #ced4da;
    border-radius:8px;
    padding:0 14px;
    font-size:14px;
    background:#fff;
    transition:all 0.2s;
}
.board-filters select:focus,
.board-filters input:focus {
    outline:none;
    border-color:#4a90e2;
    box-shadow:0 0 0 3px rgba(74, 144, 226, 0.1);
}
.board-filters select {
    min-width:130px;
    padding-right:32px;
    appearance:none;
    background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23666' d='M6 8L1 3h10z'/%3E%3C/svg%3E") no-repeat right 12px center;
    cursor:pointer;
}
.board-filters input {
    min-width:220px;
}
.board-filters input::placeholder {
    color:#adb5bd;
}
.board-actions {
    display:flex;
    gap:10px;
}
.btn-filter {
    height:42px;
    padding:0 20px;
    border:1px solid #ced4da;
    border-radius:8px;
    background:#fff;
    font-size:14px;
    font-weight:600;
    cursor:pointer;
    transition:all 0.2s;
    white-space:nowrap;
}
.btn-filter:hover {
    background:#f8f9fa;
    border-color:#adb5bd;
}
.btn-search {
    height:42px;
    padding:0 24px;
    background:#4a90e2;
    color:#fff;
    border:none;
    border-radius:8px;
    font-size:14px;
    font-weight:600;
    cursor:pointer;
    transition:all 0.2s;
    white-space:nowrap;
}
.btn-search:hover {
    background:#3a7bc8;
}

/* 게시판 테이블 */
.board-table-wrapper {
    overflow-x:auto;
    margin:0 -4px;
}
.board-table {
    width:100%;
    border-collapse:collapse;
    font-size:14px;
    table-layout:fixed;
}
.board-table thead {
    background:#f8f9fa;
}
.board-table th {
    text-align:left;
    padding:14px 16px;
    font-size:12px;
    color:#6c757d;
    text-transform:uppercase;
    letter-spacing:0.05em;
    font-weight:600;
    border-bottom:2px solid #e9ecef;
}
.board-table th:first-child {
    width:80px;
    text-align:center;
}
.board-table th:nth-child(2) {
    width:110px;
}
.board-table th:nth-child(3) {
    width:auto;
}
.board-table th:nth-child(4) {
    width:140px;
}
.board-table th:nth-child(5) {
    width:90px;
}
.board-table th:nth-child(6) {
    width:100px;
}
.board-table th:nth-child(7) {
    width:80px;
    text-align:center;
}
.board-table td {
    padding:16px;
    border-bottom:1px solid #f1f3f5;
    background:#fff;
    vertical-align:middle;
}
.board-table td:first-child {
    text-align:center;
}
.board-table tbody tr {
    cursor:pointer;
    transition:background 0.15s;
}
.board-table tbody tr:hover td {
    background:#f8fbff;
}
.board-status {
    padding:6px 14px;
    border-radius:20px;
    font-size:12px;
    font-weight:600;
    display:inline-block;
    text-align:center;
    white-space:nowrap;
    line-height:1.2;
    min-width:50px;
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
    padding:6px 12px;
    background:#f1f3f5;
    border-radius:6px;
    font-size:13px;
    font-weight:600;
    color:#495057;
}
.board-title {
    font-weight:600;
    color:#212529;
    margin-bottom:6px;
    font-size:14px;
    line-height:1.4;
}
.board-desc {
    font-size:13px;
    color:#868e96;
    line-height:1.5;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
    max-width:400px;
}
.board-attachment a {
    color:#4a90e2;
    text-decoration:none;
    font-weight:600;
    font-size:13px;
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
@media (max-width:992px) {
    .stats {
        grid-template-columns:repeat(2, 1fr);
    }
    .board-toolbar {
        grid-template-columns:1fr;
    }
    .board-actions {
        justify-content:flex-end;
    }
}
@media (max-width:768px) {
    .page-header {
        flex-direction:column;
        gap:12px;
        align-items:flex-start;
    }
    .stats {
        grid-template-columns:1fr 1fr;
    }
    .board-filters {
        width:100%;
    }
    .board-filters select,
    .board-filters input {
        flex:1;
        min-width:0;
    }
    .board-table {
        min-width:900px;
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
        <div class="stat-item urgent">
            <div class="stat-label">긴급</div>
            <div class="stat-value"><?= number_format($totalUrgent) ?></div>
        </div>
        <div class="stat-item important">
            <div class="stat-label">중요</div>
            <div class="stat-value"><?= number_format($totalImportant) ?></div>
        </div>
        <div class="stat-item normal">
            <div class="stat-label">안내</div>
            <div class="stat-value"><?= number_format($totalNormal) ?></div>
        </div>
    </div>

    <!-- 게시판 툴바 -->
    <form class="board-toolbar" method="GET" id="filterForm">
        <div class="board-filters">
            <select name="status" id="statusFilter">
                <option value="">전체 유형</option>
                <option value="urgent" <?= $statusFilter === 'urgent' ? 'selected' : '' ?>>긴급</option>
                <option value="important" <?= $statusFilter === 'important' ? 'selected' : '' ?>>중요</option>
                <option value="normal" <?= $statusFilter === 'normal' ? 'selected' : '' ?>>안내</option>
            </select>
            <select name="route" id="routeFilter">
                <option value="">전체 루트</option>
                <option value="중앙아시아" <?= $routeFilter === '중앙아시아' ? 'selected' : '' ?>>중앙아시아</option>
                <option value="중동아프리카" <?= $routeFilter === '중동아프리카' ? 'selected' : '' ?>>중동·아프리카</option>
                <option value="러시아" <?= $routeFilter === '러시아' ? 'selected' : '' ?>>러시아</option>
                <option value="유럽" <?= $routeFilter === '유럽' ? 'selected' : '' ?>>유럽</option>
                <option value="동남아시아" <?= $routeFilter === '동남아시아' ? 'selected' : '' ?>>동남아시아</option>
                <option value="국내" <?= $routeFilter === '국내' ? 'selected' : '' ?>>국내 물류</option>
            </select>
            <select name="period" id="periodFilter">
                <option value="">전체 기간</option>
                <option value="7days" <?= $periodFilter === '7days' ? 'selected' : '' ?>>최근 7일</option>
                <option value="1month" <?= $periodFilter === '1month' ? 'selected' : '' ?>>최근 1개월</option>
                <option value="3months" <?= $periodFilter === '3months' ? 'selected' : '' ?>>최근 3개월</option>
            </select>
            <input type="text" name="search" id="searchInput" placeholder="제목, 내용, 구간 검색" value="<?= h($search) ?>">
        </div>
        <div class="board-actions">
            <button type="button" class="btn-filter" onclick="resetFilters()">초기화</button>
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
    document.getElementById('periodFilter').value = '';
    document.getElementById('searchInput').value = '';
    location.href = 'routes.php';
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
