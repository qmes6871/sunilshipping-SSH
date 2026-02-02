<?php
/**
 * 전체 공지 목록
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pageTitle = '전체 공지';
$pageSubtitle = '공지사항 전체 목록';

$pdo = getDB();

// 필터 파라미터
$filter = $_GET['filter'] ?? '';
$type = $_GET['type'] ?? '';
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15;

// 쿼리 빌드
$where = ["1=1"];
$params = [];

// 상단 통계 카드 필터
if ($filter === 'unread') {
    $where[] = "(is_read = 0 OR is_read IS NULL)";
} elseif ($filter === 'important') {
    $where[] = "(is_important = 1 OR notice_type IN ('important', 'urgent'))";
}

// 유형 필터
if ($type) {
    $where[] = "notice_type = ?";
    $params[] = $type;
}

// 읽음 상태 필터
if ($status === 'unread') {
    $where[] = "(is_read = 0 OR is_read IS NULL)";
} elseif ($status === 'read') {
    $where[] = "is_read = 1";
}

// 검색어 필터
if ($search) {
    $where[] = "(title LIKE ? OR content LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereClause = implode(' AND ', $where);

// 총 개수
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM " . CRM_NOTICES_TABLE . " WHERE {$whereClause}");
    $stmt->execute($params);
    $totalCount = $stmt->fetchColumn();
} catch (Exception $e) {
    $totalCount = 0;
}

$totalPages = ceil($totalCount / $perPage);
$offset = ($page - 1) * $perPage;

// 공지 목록 조회
try {
    $stmt = $pdo->prepare("SELECT n.*, u.name as creator_name
        FROM " . CRM_NOTICES_TABLE . " n
        LEFT JOIN " . CRM_USERS_TABLE . " u ON n.created_by = u.id
        WHERE {$whereClause}
        ORDER BY n.is_important DESC, n.created_at DESC
        LIMIT {$perPage} OFFSET {$offset}");
    $stmt->execute($params);
    $notices = $stmt->fetchAll();
} catch (Exception $e) {
    $notices = [];
}

// 통계
$stats = ['total' => 0, 'unread' => 0, 'important' => 0];
try {
    // 테이블 컬럼 확인
    $tableCheck = $pdo->query("SHOW TABLES LIKE '" . CRM_NOTICES_TABLE . "'");
    if ($tableCheck->fetch()) {
        $columns = [];
        $colResult = $pdo->query("SHOW COLUMNS FROM " . CRM_NOTICES_TABLE);
        while ($col = $colResult->fetch()) {
            $columns[] = $col['Field'];
        }

        $hasIsRead = in_array('is_read', $columns);
        $hasIsImportant = in_array('is_important', $columns);
        $hasNoticeType = in_array('notice_type', $columns);

        // 전체 카운트
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM " . CRM_NOTICES_TABLE);
        $stats['total'] = $stmt->fetch()['total'] ?? 0;

        // 미확인 카운트
        if ($hasIsRead) {
            $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM " . CRM_NOTICES_TABLE . " WHERE is_read = 0 OR is_read IS NULL");
            $stats['unread'] = $stmt->fetch()['cnt'] ?? 0;
        } else {
            $stats['unread'] = $stats['total']; // is_read 컬럼 없으면 전체가 미확인
        }

        // 중요 공지 카운트
        $importantConditions = [];
        if ($hasIsImportant) {
            $importantConditions[] = "is_important = 1";
        }
        if ($hasNoticeType) {
            $importantConditions[] = "notice_type = 'urgent'";
        }
        if (!empty($importantConditions)) {
            $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM " . CRM_NOTICES_TABLE . " WHERE " . implode(' OR ', $importantConditions));
            $stats['important'] = $stmt->fetch()['cnt'] ?? 0;
        }
    }
} catch (Exception $e) {
    // 오류 시 기본값 유지
}

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<style>
/* 페이지 헤더 */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}
.header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}
.page-title {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 4px;
}
.page-subtitle {
    font-size: 14px;
    color: #6c757d;
}
.btn-back {
    padding: 8px 16px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    background: white;
    color: #495057;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
}
.btn-back:hover { background: #f8f9fa; }

/* 통계 */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
.stat-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    text-decoration: none;
    color: inherit;
    cursor: pointer;
    transition: all 0.2s;
    border: 2px solid transparent;
}
.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.stat-card.active {
    border-color: #4a90e2;
    background: #f0f7ff;
}
.stat-value {
    font-size: 32px;
    font-weight: 700;
}
.stat-value.total { color: #4a90e2; }
.stat-value.unread { color: #f59e0b; }
.stat-value.important { color: #ef4444; }
.stat-label {
    font-size: 13px;
    color: #666;
    margin-top: 4px;
}

/* 필터 바 */
.filter-bar {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    align-items: center;
    margin-bottom: 24px;
}
.filter-tabs {
    display: flex;
    gap: 8px;
}
.filter-tab {
    padding: 8px 16px;
    border-radius: 20px;
    background: #f5f5f5;
    color: #666;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.2s;
}
.filter-tab:hover { background: #e0e0e0; }
.filter-tab.active { background: var(--primary); color: white; }

.search-box {
    flex: 1;
    min-width: 200px;
    display: flex;
    gap: 8px;
}
.search-box input {
    flex: 1;
}

/* 공지 리스트 */
.notice-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.notice-item {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    cursor: pointer;
    transition: all 0.2s;
}
.notice-item:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
}
.notice-item.unread {
    border-left: 4px solid #4a90e2;
}
.notice-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
}
.notice-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}
.badge-urgent { background: #fee2e2; color: #dc2626; }
.badge-important { background: #fff3cd; color: #d97706; }
.badge-normal { background: #e9ecef; color: #495057; }
.badge-new { background: #d1fae5; color: #059669; }

.notice-title {
    font-size: 18px;
    font-weight: 600;
    color: #212529;
    margin-bottom: 8px;
}
.notice-content {
    font-size: 14px;
    color: #666;
    line-height: 1.5;
    margin-bottom: 12px;
}
.notice-meta {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    font-size: 13px;
    color: #888;
}

/* 페이지네이션 */
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

/* 빈 상태 */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}
.empty-state .icon {
    font-size: 48px;
    margin-bottom: 16px;
}

/* 반응형 */
@media (max-width: 768px) {
    .stats-grid { grid-template-columns: 1fr; }
    .page-header { flex-direction: column; align-items: stretch; gap: 16px; }
    .filter-bar { flex-direction: column; align-items: stretch; }
}
</style>

<!-- 페이지 헤더 -->
<div class="page-header">
    <div class="header-left">
        <a href="notices.php" class="btn-back">← 뒤로가기</a>
        <div>
            <div class="page-title">전체 공지</div>
            <div class="page-subtitle">공지사항 전체 목록</div>
        </div>
    </div>
    <?php if (isAdmin()): ?>
    <a href="notice_form.php" class="btn btn-primary">+ 새 공지 작성</a>
    <?php endif; ?>
</div>

<!-- 통계 (클릭하여 필터링) -->
<div class="stats-grid">
    <a href="?filter=all" class="stat-card <?= ($_GET['filter'] ?? '') === '' || ($_GET['filter'] ?? '') === 'all' ? 'active' : '' ?>">
        <div class="stat-value total"><?= $stats['total'] ?? 0 ?></div>
        <div class="stat-label">전체 공지</div>
    </a>
    <a href="?filter=unread" class="stat-card <?= ($_GET['filter'] ?? '') === 'unread' ? 'active' : '' ?>">
        <div class="stat-value unread"><?= $stats['unread'] ?? 0 ?></div>
        <div class="stat-label">미확인</div>
    </a>
    <a href="?filter=important" class="stat-card <?= ($_GET['filter'] ?? '') === 'important' ? 'active' : '' ?>">
        <div class="stat-value important"><?= $stats['important'] ?? 0 ?></div>
        <div class="stat-label">중요 공지</div>
    </a>
</div>

<!-- 필터 & 검색 -->
<div class="card" style="padding: 16px; margin-bottom: 24px;">
    <div class="filter-bar">
        <div class="filter-tabs">
            <a href="?status=" class="filter-tab <?= $status === '' ? 'active' : '' ?>">전체</a>
            <a href="?status=unread" class="filter-tab <?= $status === 'unread' ? 'active' : '' ?>">미확인</a>
            <a href="?status=read" class="filter-tab <?= $status === 'read' ? 'active' : '' ?>">확인완료</a>
        </div>

        <form class="search-box" method="GET">
            <input type="hidden" name="status" value="<?= h($status) ?>">
            <select name="type" class="form-control" style="width: auto;">
                <option value="">전체 유형</option>
                <option value="urgent" <?= $type === 'urgent' ? 'selected' : '' ?>>긴급</option>
                <option value="important" <?= $type === 'important' ? 'selected' : '' ?>>중요</option>
                <option value="normal" <?= $type === 'normal' ? 'selected' : '' ?>>일반</option>
            </select>
            <input type="text" name="search" class="form-control" placeholder="검색어 입력" value="<?= h($search) ?>">
            <button type="submit" class="btn btn-secondary">검색</button>
        </form>
    </div>
</div>

<!-- 공지 목록 -->
<div class="notice-list">
    <?php if (empty($notices)): ?>
        <div class="card empty-state">
            <div class="icon">📢</div>
            <p>등록된 공지사항이 없습니다.</p>
        </div>
    <?php else: ?>
        <?php foreach ($notices as $notice): ?>
            <?php
            $isUnread = !($notice['is_read'] ?? 0);
            $isNew = (strtotime($notice['created_at'] ?? 'now') > strtotime('-3 days'));
            $badgeClass = 'badge-normal';
            $badgeText = '일반';
            $noticeType = $notice['notice_type'] ?? '';
            if ($noticeType === 'urgent') {
                $badgeClass = 'badge-urgent';
                $badgeText = '긴급';
            } elseif ($noticeType === 'important' || ($notice['is_important'] ?? 0)) {
                $badgeClass = 'badge-important';
                $badgeText = '중요';
            }
            ?>
            <div class="notice-item <?= $isUnread ? 'unread' : '' ?>" onclick="viewNotice(<?= $notice['id'] ?>)">
                <div class="notice-header">
                    <div>
                        <span class="notice-badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                        <?php if ($isNew): ?>
                            <span class="notice-badge badge-new">NEW</span>
                        <?php endif; ?>
                    </div>
                    <span style="font-size: 13px; color: #888;"><?= formatDate($notice['created_at'], 'Y-m-d H:i') ?></span>
                </div>

                <div class="notice-title"><?= h($notice['title']) ?></div>
                <div class="notice-content"><?= h(mb_substr(strip_tags($notice['content'] ?? ''), 0, 150)) ?>...</div>

                <div class="notice-meta">
                    <span>작성자: <?= h($notice['creator_name'] ?? '관리자') ?></span>
                    <?php if ($notice['department']): ?>
                        <span>부서: <?= h($notice['department']) ?></span>
                    <?php endif; ?>
                    <span><?= $isUnread ? '미확인' : '확인완료' ?></span>
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

<!-- 공지 상세 모달 -->
<div class="modal-overlay" id="noticeModal">
    <div class="modal" style="max-width: 700px;">
        <div class="modal-header">
            <h3 id="noticeModalTitle">공지사항</h3>
            <button class="modal-close" onclick="closeModal('noticeModal')">&times;</button>
        </div>
        <div class="modal-body" id="noticeModalContent" style="min-height: 200px;"></div>
        <div class="modal-footer">
            <?php if (isAdmin()): ?>
            <button class="btn btn-primary" id="btnEditNotice" onclick="editNotice()">수정</button>
            <button class="btn btn-danger" id="btnDeleteNotice" onclick="deleteNotice()">삭제</button>
            <?php endif; ?>
            <button class="btn btn-secondary" onclick="closeModal('noticeModal')">닫기</button>
        </div>
    </div>
</div>

<?php
$pageScripts = <<<SCRIPT
<script>
let currentNoticeId = null;

async function viewNotice(id) {
    try {
        const response = await apiGet(CRM_URL + '/api/common/notices.php?id=' + id);

        if (!response.success || !response.data) {
            showToast(response.message || '데이터를 불러올 수 없습니다.', 'error');
            return;
        }

        const notice = response.data;
        currentNoticeId = id;

        document.getElementById('noticeModalTitle').textContent = notice.title || '공지사항';
        document.getElementById('noticeModalContent').innerHTML = `
            <div style="margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid #eee;">
                <span style="font-size: 13px; color: #666;">
                    작성자: \${notice.creator_name || '관리자'}
                    · \${notice.created_at ? notice.created_at.substring(0, 10) : '-'}
                </span>
            </div>
            <div style="line-height: 1.8; white-space: pre-wrap;">\${notice.content || '(내용 없음)'}</div>
        `;

        openModal('noticeModal');

        // 읽음 처리 (실패해도 무시)
        try {
            await apiPost(CRM_URL + '/api/common/notices.php', {
                action: 'mark_read',
                id: id
            });
        } catch (e) {}

        // UI 업데이트
        const item = document.querySelector('.notice-item[onclick*="viewNotice(' + id + ')"]');
        if (item) {
            item.classList.remove('unread');
        }
    } catch (error) {
        console.error('viewNotice error:', error);
        showToast('데이터를 불러올 수 없습니다.', 'error');
    }
}

function editNotice() {
    if (currentNoticeId) {
        location.href = 'notice_form.php?id=' + currentNoticeId;
    }
}

async function deleteNotice() {
    if (!currentNoticeId) return;
    if (!confirm('정말 삭제하시겠습니까?')) return;

    try {
        const response = await apiPost(CRM_URL + '/api/common/notices.php', {
            action: 'delete',
            id: currentNoticeId
        });

        if (response.success) {
            showToast('삭제되었습니다.', 'success');
            closeModal('noticeModal');
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
