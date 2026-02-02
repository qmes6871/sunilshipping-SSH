<?php
/**
 * KMS 게시판
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pageTitle = 'KMS 게시판';
$pageSubtitle = '국제물류 · 농산물 · 우드펠렛 · 무역 지식/문서 공유';

$pdo = getDB();

$part = $_GET['part'] ?? '';
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 12;

$where = ["1=1"];
$params = [];

if ($part && $part !== 'all') {
    $where[] = "part = ?";
    $params[] = $part;
}
if ($status && $status !== 'all') {
    $where[] = "classification = ?";
    $params[] = $status;
}
if ($search) {
    $where[] = "(title LIKE ? OR content LIKE ? OR tags LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereClause = implode(' AND ', $where);

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM " . CRM_KMS_TABLE . " WHERE {$whereClause}");
    $stmt->execute($params);
    $totalCount = $stmt->fetchColumn();
} catch (Exception $e) {
    $totalCount = 0;
}

$totalPages = ceil($totalCount / $perPage);
$offset = ($page - 1) * $perPage;

try {
    $stmt = $pdo->prepare("SELECT k.*, u.name as creator_name
        FROM " . CRM_KMS_TABLE . " k
        LEFT JOIN " . CRM_USERS_TABLE . " u ON k.created_by = u.id
        WHERE {$whereClause}
        ORDER BY k.created_at DESC
        LIMIT {$perPage} OFFSET {$offset}");
    $stmt->execute($params);
    $documents = $stmt->fetchAll();
} catch (Exception $e) {
    $documents = [];
}

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<style>
a { text-decoration: none; color: inherit; }

/* Header */
.page-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 20px; flex-wrap: wrap; }
.title-wrap { display: grid; gap: 6px; }
.page-title { font-size: 28px; font-weight: 800; }
.page-sub { font-size: 13px; color: #6c757d; }
.page-actions { display: flex; gap: 10px; flex-wrap: wrap; }

/* Tabs */
.tabs { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 14px; }
.tab { padding: 8px 14px; border-radius: 999px; background: #e9ecef; color: #495057; font-size: 13px; font-weight: 700; cursor: pointer; user-select: none; transition: all 0.2s; }
.tab:hover { background: #dee2e6; }
.tab.active { background: #0d6efd; color: #fff; }

/* Toolbar */
.toolbar { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 12px; }
.filters { display: flex; gap: 10px; flex-wrap: wrap; }
.select, .input { border: 1px solid #dee2e6; border-radius: 8px; padding: 10px 12px; font-size: 14px; background: #fff; }
.input { min-width: 240px; }

/* Table */
.kms-card { background: #fff; border-radius: 10px; box-shadow: 0 8px 28px rgba(33,37,41,.06); overflow: hidden; }
.kms-table { width: 100%; border-collapse: collapse; font-size: 14px; }
.kms-table thead { background: #f1f3f5; }
.kms-table th, .kms-table td { padding: 12px 14px; text-align: left; }
.kms-table th { font-size: 12px; color: #6c757d; letter-spacing: .04em; text-transform: uppercase; }
.kms-table tbody tr { border-top: 1px solid #edf2f7; background: #fff; cursor: pointer; }
.kms-table tbody tr:hover { background: #f8fbff; }

.part-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
.p-logi { background: #e7f5ff; color: #1c7ed6; }
.p-agri { background: #e6fcf5; color: #087f5b; }
.p-pellet { background: #fff4e6; color: #d9480f; }
.p-trade { background: #f3f0ff; color: #5f3dc4; }

.tags { display: flex; gap: 6px; flex-wrap: wrap; }
.tag { padding: 4px 8px; background: #f1f3f5; color: #495057; font-size: 12px; border-radius: 999px; }
.muted { color: #adb5bd; font-size: 12px; }

/* Empty state */
.empty { text-align: center; padding: 40px 16px; color: #6c757d; }

/* Pagination */
.pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; }
.pagination a, .pagination span { padding: 8px 14px; border-radius: 6px; text-decoration: none; font-size: 14px; }
.pagination a { background: #f5f5f5; color: #666; }
.pagination a:hover { background: #e0e0e0; }
.pagination .current { background: #0d6efd; color: white; }

/* Responsive */
@media (max-width: 900px) {
  .page-header { align-items: center; }
  .kms-table { font-size: 13px; }
  .kms-table th, .kms-table td { padding: 10px 12px; }
}
@media (max-width: 640px) {
  .input { min-width: 0; width: 100%; }
  .toolbar { flex-direction: column; align-items: stretch; }
  .filters { width: 100%; }
}
</style>

<div class="page-header">
    <div class="title-wrap">
        <div class="page-title">KMS 게시판</div>
        <div class="page-sub">국제물류 · 농산물 · 우드펠렛 · 무역 지식/문서 공유</div>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="location.href='kms_form.php'">새 문서 등록</button>
    </div>
</div>

<!-- Tabs -->
<div class="tabs" id="tabs">
    <div class="tab <?= $part === '' || $part === 'all' ? 'active' : '' ?>" data-part="all" onclick="filterPart('all')">전체</div>
    <div class="tab <?= $part === 'logi' ? 'active' : '' ?>" data-part="logi" onclick="filterPart('logi')">국제물류</div>
    <div class="tab <?= $part === 'agri' ? 'active' : '' ?>" data-part="agri" onclick="filterPart('agri')">농산물</div>
    <div class="tab <?= $part === 'pellet' ? 'active' : '' ?>" data-part="pellet" onclick="filterPart('pellet')">우드펠렛</div>
    <div class="tab <?= $part === 'trade' ? 'active' : '' ?>" data-part="trade" onclick="filterPart('trade')">무역</div>
</div>

<!-- Toolbar -->
<form class="toolbar" method="GET" id="filterForm">
    <div class="filters">
        <select class="select" name="part" id="partSelect" onchange="this.form.submit()">
            <option value="all">전체 파트</option>
            <option value="logi" <?= $part === 'logi' ? 'selected' : '' ?>>국제물류</option>
            <option value="agri" <?= $part === 'agri' ? 'selected' : '' ?>>농산물</option>
            <option value="pellet" <?= $part === 'pellet' ? 'selected' : '' ?>>우드펠렛</option>
            <option value="trade" <?= $part === 'trade' ? 'selected' : '' ?>>무역</option>
        </select>
        <select class="select" name="status" id="statusSelect" onchange="this.form.submit()">
            <option value="all">전체 상태</option>
            <option value="guide" <?= $status === 'guide' ? 'selected' : '' ?>>가이드</option>
            <option value="check" <?= $status === 'check' ? 'selected' : '' ?>>체크리스트</option>
            <option value="notice" <?= $status === 'notice' ? 'selected' : '' ?>>공지</option>
        </select>
    </div>
    <input class="input" name="search" id="keyword" placeholder="키워드 검색 (제목 / 내용 / 태그)" value="<?= h($search) ?>">
    <button class="btn btn-primary" type="submit">검색</button>
</form>

<!-- List -->
<div class="kms-card">
    <table class="kms-table" id="table">
        <thead>
            <tr>
                <th style="width:140px;">파트</th>
                <th>제목 / 내용</th>
                <th style="width:220px;">태그</th>
                <th style="width:120px;">작성일</th>
                <th style="width:110px;">작성자</th>
                <th style="width:90px;">조회</th>
            </tr>
        </thead>
        <tbody id="tbody">
            <?php if (empty($documents)): ?>
                <tr><td colspan="6" class="empty">표시할 문서가 없습니다.</td></tr>
            <?php else: ?>
                <?php foreach ($documents as $doc): ?>
                    <?php
                    $partLabels = ['international' => '국제물류', 'logi' => '국제물류', 'agricultural' => '농산물', 'agri' => '농산물', 'pellet' => '우드펠렛', 'trade' => '무역'];
                    $partClasses = ['international' => 'p-logi', 'logi' => 'p-logi', 'agricultural' => 'p-agri', 'agri' => 'p-agri', 'pellet' => 'p-pellet', 'trade' => 'p-trade'];
                    $tags = array_filter(array_map('trim', explode(',', $doc['tags'] ?? '')));
                    ?>
                    <tr onclick="viewDocument(<?= $doc['id'] ?>)">
                        <td><span class="part-badge <?= $partClasses[$doc['part']] ?? 'p-logi' ?>"><?= $partLabels[$doc['part']] ?? $doc['part'] ?></span></td>
                        <td>
                            <div style="font-weight:700;"><?= h($doc['title']) ?></div>
                            <div class="muted"><?= h(mb_substr(strip_tags($doc['content'] ?? ''), 0, 60)) ?>...</div>
                        </td>
                        <td>
                            <div class="tags">
                                <?php foreach (array_slice($tags, 0, 3) as $tag): ?>
                                    <span class="tag"><?= h($tag) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td class="muted"><?= formatDate($doc['created_at'], 'Y-m-d') ?></td>
                        <td class="muted"><?= h($doc['creator_name'] ?? '관리자') ?></td>
                        <td class="muted"><?= number_format($doc['view_count'] ?? 0) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">« 이전</a>
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
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- 문서 상세 모달 -->
<div class="modal-overlay" id="docModal">
    <div class="modal" style="max-width: 800px;">
        <div class="modal-header">
            <h3 id="docTitle">문서</h3>
            <button class="modal-close" onclick="closeModal('docModal')">&times;</button>
        </div>
        <div class="modal-body" id="docContent" style="min-height: 300px;"></div>
        <div class="modal-footer">
            <?php if (isAdmin()): ?>
            <button class="btn btn-primary" onclick="editDocument()">수정</button>
            <button class="btn btn-danger" onclick="deleteDocument()">삭제</button>
            <?php endif; ?>
            <button class="btn btn-secondary" onclick="closeModal('docModal')">닫기</button>
        </div>
    </div>
</div>

<?php
$pageScripts = <<<SCRIPT
<script>
let currentDocId = null;

function filterPart(part) {
    document.getElementById('partSelect').value = part;
    document.getElementById('filterForm').submit();
}

async function viewDocument(id) {
    try {
        const response = await apiGet(CRM_URL + '/api/common/kms.php?id=' + id);
        const doc = response.data;
        currentDocId = id;

        const partLabels = {international: '국제물류', logi: '국제물류', agricultural: '농산물', agri: '농산물', pellet: '우드펠렛', trade: '무역'};
        const classLabels = {guide: '가이드', checklist: '체크리스트', check: '체크리스트', notice: '공지'};

        document.getElementById('docTitle').textContent = doc.title;
        document.getElementById('docContent').innerHTML = `
            <div style="margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid #eee; display: flex; gap: 12px; flex-wrap: wrap;">
                <span style="background: #f0f0f0; padding: 4px 10px; border-radius: 12px; font-size: 13px;">
                    \${partLabels[doc.part] || doc.part}
                </span>
                <span style="background: #e0e7ff; color: #4338ca; padding: 4px 10px; border-radius: 12px; font-size: 13px;">
                    \${classLabels[doc.classification] || doc.classification}
                </span>
                <span style="font-size: 13px; color: #666;">
                    작성: \${doc.creator_name || '관리자'} · \${doc.created_at?.substring(0, 10)}
                </span>
            </div>
            <div style="line-height: 1.8; white-space: pre-wrap;">\${doc.content || '(내용 없음)'}</div>
        `;

        openModal('docModal');
    } catch (error) {
        showToast('데이터를 불러올 수 없습니다.', 'error');
    }
}

function editDocument() {
    if (currentDocId) {
        location.href = 'kms_form.php?id=' + currentDocId;
    }
}

async function deleteDocument() {
    if (!currentDocId) return;
    if (!confirm('정말 삭제하시겠습니까?')) return;

    try {
        const response = await apiPost(CRM_URL + '/api/common/kms.php', {
            action: 'delete',
            id: currentDocId
        });

        if (response.success) {
            showToast('삭제되었습니다.', 'success');
            closeModal('docModal');
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
