<?php
/**
 * 전체 공지 관리
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pageTitle = '전체 공지 관리';
$pageSubtitle = '미확인 공지, 팝업, 푸시 알림, KMS 및 루트별 주의사항';

$pdo = getDB();

// 최신 공지 조회
$latestNotices = [];
try {
    // 테이블 존재 확인
    $tableCheck = $pdo->query("SHOW TABLES LIKE '" . CRM_NOTICES_TABLE . "'");
    if ($tableCheck->fetch()) {
        // 컬럼 확인
        $columns = [];
        $colResult = $pdo->query("SHOW COLUMNS FROM " . CRM_NOTICES_TABLE);
        while ($col = $colResult->fetch()) {
            $columns[] = $col['Field'];
        }

        $hasIsImportant = in_array('is_important', $columns);
        $orderClause = $hasIsImportant ? "ORDER BY n.is_important DESC, n.created_at DESC" : "ORDER BY n.created_at DESC";

        $stmt = $pdo->prepare("SELECT n.*, u.name as creator_name
            FROM " . CRM_NOTICES_TABLE . " n
            LEFT JOIN " . CRM_USERS_TABLE . " u ON n.created_by = u.id
            {$orderClause}
            LIMIT 5");
        $stmt->execute();
        $latestNotices = $stmt->fetchAll();
    }
} catch (Exception $e) {
    $latestNotices = [];
}

// 주의사항 조회
$status = $_GET['status'] ?? '';
$route = $_GET['route'] ?? '';
$search = $_GET['search'] ?? '';

$where = ["1=1"];
$params = [];

if ($status) {
    $where[] = "status = ?";
    $params[] = $status;
}
if ($route) {
    $where[] = "route_name LIKE ?";
    $params[] = "%{$route}%";
}
if ($search) {
    $where[] = "(title LIKE ? OR content LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereClause = implode(' AND ', $where);

try {
    $stmt = $pdo->prepare("SELECT w.*, u.name as creator_name
        FROM " . CRM_ROUTES_TABLE . " w
        LEFT JOIN " . CRM_USERS_TABLE . " u ON w.created_by = u.id
        WHERE {$whereClause}
        ORDER BY FIELD(status, 'urgent', 'important', 'normal'), w.created_at DESC
        LIMIT 10");
    $stmt->execute($params);
    $warnings = $stmt->fetchAll();
} catch (Exception $e) {
    $warnings = [];
}

// 푸시 알림 조회
try {
    $stmt = $pdo->prepare("SELECT * FROM " . CRM_NOTICES_TABLE . "
        WHERE notice_type = 'push'
        ORDER BY created_at DESC
        LIMIT 5");
    $stmt->execute();
    $pushNotices = $stmt->fetchAll();
} catch (Exception $e) {
    $pushNotices = [];
}

// KMS 최신 문서 조회
try {
    $stmt = $pdo->prepare("SELECT k.*, u.name as creator_name
        FROM " . CRM_KMS_TABLE . " k
        LEFT JOIN " . CRM_USERS_TABLE . " u ON k.created_by = u.id
        ORDER BY k.created_at DESC
        LIMIT 4");
    $stmt->execute();
    $kmsDocuments = $stmt->fetchAll();
} catch (Exception $e) {
    $kmsDocuments = [];
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

/* 그리드 레이아웃 */
.notice-grid {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:20px;
    margin-bottom:20px;
}
.full-width {
    grid-column:1 / -1;
}

/* 카드 공통 */
.notice-card {
    background:#fff;
    padding:20px;
    border-radius:8px;
    box-shadow:0 1px 3px rgba(0,0,0,0.1);
}
.card-header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:16px;
    padding-bottom:12px;
    border-bottom:1px solid #e9ecef;
}
.card-title {
    font-size:18px;
    font-weight:600;
    display:flex;
    align-items:center;
    gap:8px;
}
.card-badge {
    padding:3px 10px;
    border-radius:12px;
    font-size:12px;
    font-weight:600;
    background:#5a9fd4;
    color:#fff;
}
.card-link {
    font-size:13px;
    color:#5a9fd4;
    cursor:pointer;
    text-decoration:none;
}
.card-link:hover { text-decoration:underline; }

/* 공지 리스트 */
.notice-list {
    display:flex;
    flex-direction:column;
    gap:10px;
}
.notice-item {
    padding:14px;
    background:#f8f9fa;
    border-radius:6px;
    cursor:pointer;
    transition:all 0.2s;
}
.notice-item:hover {
    background:#e7f3ff;
}
.notice-item.unread {
    background:#fff;
    border:1px solid #d0e7ff;
}
.notice-item.important {
    background:#fff8f0;
}
.notice-top {
    display:flex;
    justify-content:space-between;
    align-items:start;
    margin-bottom:6px;
}
.notice-title {
    font-size:14px;
    font-weight:500;
    flex:1;
}
.notice-badge {
    padding:3px 8px;
    border-radius:10px;
    font-size:11px;
    font-weight:600;
    margin-left:8px;
}
.notice-badge.urgent {
    background:#ff6b6b;
    color:#fff;
}
.notice-badge.important {
    background:#ffa94d;
    color:#fff;
}
.notice-badge.normal {
    background:#e9ecef;
    color:#495057;
}
.notice-badge.new {
    background:#51cf66;
    color:#fff;
}
.notice-content {
    font-size:13px;
    color:#6c757d;
    margin-bottom:6px;
    line-height:1.5;
}
.notice-meta {
    display:flex;
    gap:12px;
    font-size:12px;
    color:#adb5bd;
}

/* 주의사항 게시판 */
.notice-board {
    display:flex;
    flex-direction:column;
    gap:14px;
}
.board-toolbar {
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    gap:10px;
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
    padding:6px 10px;
    font-size:13px;
    background:#fff;
}
.board-table-wrapper {
    overflow-x:auto;
}
.board-table {
    width:100%;
    border-collapse:collapse;
    font-size:13px;
}
.board-table thead {
    background:#f1f3f5;
}
.board-table th {
    text-align:left;
    padding:10px 12px;
    font-size:12px;
    color:#6c757d;
    text-transform:uppercase;
    letter-spacing:0.05em;
}
.board-table td {
    padding:12px;
    border-top:1px solid #e9ecef;
    background:#fff;
}
.board-table tbody tr:hover td {
    background:#f8fbff;
}
.board-title-cell {
    font-weight:600;
    color:#212529;
}
.board-desc {
    margin-top:4px;
    font-size:12px;
    color:#868e96;
}
.board-status {
    padding:4px 10px;
    border-radius:999px;
    font-size:11px;
    font-weight:600;
    display:inline-flex;
    align-items:center;
    justify-content:center;
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
.board-attachment a {
    color:#4a90e2;
    text-decoration:none;
    font-weight:600;
}
.board-attachment a:hover {
    text-decoration:underline;
}

/* KMS 리스트 */
.kms-list {
    display:flex;
    flex-direction:column;
    gap:10px;
}
.kms-item {
    padding:14px;
    background:#f8f9fa;
    border-radius:6px;
    cursor:pointer;
    transition:all 0.2s;
}
.kms-item:hover {
    background:#e7f3ff;
}
.kms-top {
    display:flex;
    justify-content:space-between;
    align-items:start;
    margin-bottom:6px;
}
.kms-title {
    font-size:14px;
    font-weight:500;
    flex:1;
}
.kms-content {
    font-size:13px;
    color:#6c757d;
    margin-bottom:6px;
}
.kms-meta {
    display:flex;
    gap:12px;
    font-size:12px;
    color:#adb5bd;
}

/* 루트별 바로가기 그리드 */
.shortcut-grid {
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:12px;
}
.shortcut-card {
    padding:16px;
    background:#f8f9fa;
    border-radius:8px;
    border:1px solid #e9ecef;
    cursor:pointer;
    transition:all 0.2s;
    text-align:center;
}
.shortcut-card:hover {
    background:#e7f3ff;
    border-color:#5a9fd4;
}
.shortcut-title {
    font-size:14px;
    font-weight:600;
    margin-bottom:4px;
}
.shortcut-desc {
    font-size:12px;
    color:#6c757d;
}

/* 반응형 */
@media (max-width:1200px) {
    .notice-grid {
        grid-template-columns:1fr;
    }
    .shortcut-grid {
        grid-template-columns:repeat(2,1fr);
    }
}
@media (max-width:768px) {
    .page-header {
        flex-direction:column;
        gap:12px;
        align-items:flex-start;
    }
    .shortcut-grid {
        grid-template-columns:1fr;
    }
}
</style>

<!-- 페이지 헤더 -->
<div class="page-header">
    <div>
        <div class="page-title">전체 공지 관리</div>
        <div class="page-subtitle">미확인 공지, 팝업, 푸시 알림, KMS 및 루트별 주의사항</div>
    </div>
    <?php if (isAdmin()): ?>
    <button class="btn btn-primary" onclick="location.href='notice_form.php'">새 공지 작성</button>
    <?php endif; ?>
</div>

<div class="notice-grid">
    <!-- 미확인 공지 -->
    <div class="notice-card">
        <div class="card-header">
            <div class="card-title">
                공지사항
            </div>
            <a href="notices_all.php" class="card-link">전체보기 →</a>
        </div>
        <div class="notice-list">
            <?php if (empty($latestNotices)): ?>
                <div style="text-align:center; padding:20px; color:#999;">등록된 공지사항이 없습니다.</div>
            <?php else: ?>
                <?php foreach ($latestNotices as $notice): ?>
                    <?php
                    $badgeClass = 'normal';
                    $badgeText = '일반';
                    $noticeType = $notice['notice_type'] ?? '';
                    if ($noticeType === 'urgent') {
                        $badgeClass = 'urgent';
                        $badgeText = '긴급';
                    } else if ($noticeType === 'important' || ($notice['is_important'] ?? 0)) {
                        $badgeClass = 'important';
                        $badgeText = '중요';
                    }
                    ?>
                    <div class="notice-item unread" onclick="viewNotice(<?= $notice['id'] ?>)">
                        <div class="notice-top">
                            <div class="notice-title"><?= h($notice['title']) ?></div>
                            <span class="notice-badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                        </div>
                        <div class="notice-content">
                            <?= h(mb_substr(strip_tags($notice['content'] ?? ''), 0, 80)) ?>...
                        </div>
                        <div class="notice-meta">
                            <span><?= formatDate($notice['created_at'] ?? '', 'Y-m-d H:i') ?></span>
                            <span><?= h($notice['creator_name'] ?? '관리자') ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- 주의사항 -->
    <div class="notice-card">
        <div class="card-header">
            <div class="card-title">주의사항</div>
            <a href="routes.php" class="card-link">전체보기 →</a>
        </div>
        <div class="notice-board">
            <form class="board-toolbar" method="GET">
                <div class="board-filters">
                    <select name="status" onchange="this.form.submit()">
                        <option value="">전체 상태</option>
                        <option value="urgent" <?= $status === 'urgent' ? 'selected' : '' ?>>긴급</option>
                        <option value="important" <?= $status === 'important' ? 'selected' : '' ?>>중요</option>
                        <option value="normal" <?= $status === 'normal' ? 'selected' : '' ?>>안내</option>
                    </select>
                    <select name="route" onchange="this.form.submit()">
                        <option value="">전체 루트</option>
                        <option value="중앙아시아" <?= $route === '중앙아시아' ? 'selected' : '' ?>>중앙아시아</option>
                        <option value="중동아프리카" <?= $route === '중동아프리카' ? 'selected' : '' ?>>중동·아프리카</option>
                        <option value="유럽" <?= $route === '유럽' ? 'selected' : '' ?>>유럽</option>
                    </select>
                    <input type="text" name="search" placeholder="키워드 검색" value="<?= h($search) ?>">
                </div>
            </form>
            <div class="board-table-wrapper">
                <table class="board-table">
                    <thead>
                    <tr>
                        <th>상태</th>
                        <th>제목 / 내용</th>
                        <th>루트 · 구간</th>
                        <th>등록자</th>
                        <th>등록일</th>
                        <th>첨부</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($warnings)): ?>
                        <tr><td colspan="6" style="text-align:center; padding:20px; color:#999;">등록된 주의사항이 없습니다.</td></tr>
                    <?php else: ?>
                        <?php foreach ($warnings as $warning): ?>
                            <tr onclick="location.href='route_detail.php?id=<?= $warning['id'] ?>'" style="cursor:pointer;">
                                <td>
                                    <span class="board-status <?= $warning['status'] ?>">
                                        <?php
                                        $statusLabels = ['urgent' => '긴급', 'important' => '중요', 'normal' => '안내'];
                                        echo $statusLabels[$warning['status']] ?? '안내';
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="board-title-cell"><?= h($warning['title']) ?></div>
                                    <div class="board-desc"><?= h(mb_substr($warning['content'] ?? '', 0, 50)) ?>...</div>
                                </td>
                                <td><?= h($warning['route_name']) ?></td>
                                <td><?= h($warning['creator_name'] ?? '관리자') ?></td>
                                <td><?= formatDate($warning['created_at'], 'Y-m-d') ?></td>
                                <td class="board-attachment">
                                    <?php if ($warning['attachment_path']): ?>
                                        <a href="<?= CRM_UPLOAD_URL ?>/<?= h($warning['attachment_path']) ?>" target="_blank">다운로드</a>
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
        </div>
    </div>

    <!-- 푸시 알림 운영 -->
    <div class="notice-card">
        <div class="card-header">
            <div class="card-title">푸시 알림 운영</div>
            <a href="push_history.php" class="card-link">발송 내역 →</a>
        </div>
        <div class="notice-list">
            <?php if (empty($pushNotices)): ?>
                <div style="text-align:center; padding:20px; color:#999;">푸시 알림 내역이 없습니다.</div>
            <?php else: ?>
                <?php foreach ($pushNotices as $push): ?>
                    <div class="notice-item">
                        <div class="notice-top">
                            <div class="notice-title"><?= h($push['title']) ?></div>
                            <span class="notice-badge normal">발송완료</span>
                        </div>
                        <div class="notice-content"><?= h(mb_substr(strip_tags($push['content'] ?? ''), 0, 80)) ?>...</div>
                        <div class="notice-meta">
                            <span><?= formatDate($push['created_at'], 'Y-m-d H:i') ?></span>
                            <span>대상: 전체 고객</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- KMS (지식 관리) -->
    <div class="notice-card">
        <div class="card-header">
            <div class="card-title">KMS (지식 관리)</div>
            <a href="kms.php" class="card-link">전체 문서 →</a>
        </div>
        <div class="kms-list">
            <?php if (empty($kmsDocuments)): ?>
                <div style="text-align:center; padding:20px; color:#999;">등록된 문서가 없습니다.</div>
            <?php else: ?>
                <?php foreach ($kmsDocuments as $doc): ?>
                    <?php
                    $isNew = (strtotime($doc['created_at']) > strtotime('-7 days'));
                    $tags = array_filter(array_map('trim', explode(',', $doc['tags'] ?? '')));
                    ?>
                    <div class="kms-item" onclick="location.href='kms.php?id=<?= $doc['id'] ?>'">
                        <div class="kms-top">
                            <div class="kms-title"><?= h($doc['title']) ?></div>
                            <?php if ($isNew): ?>
                                <span class="notice-badge new">NEW</span>
                            <?php endif; ?>
                        </div>
                        <div class="kms-content"><?= h(mb_substr(strip_tags($doc['content'] ?? ''), 0, 80)) ?>...</div>
                        <div class="kms-meta">
                            <span><?= formatDate($doc['created_at'], 'Y-m-d') ?> 업데이트</span>
                            <span><?= h($doc['creator_name'] ?? '관리자') ?></span>
                            <span>조회 <?= number_format($doc['view_count'] ?? 0) ?>회</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- 루트별 주의사항 바로가기 -->
    <div class="notice-card full-width">
        <div class="card-header">
            <div class="card-title">루트별 주의사항 바로가기</div>
            <a href="routes.php" class="card-link">전체 루트 보기 →</a>
        </div>
        <div class="shortcut-grid">
            <div class="shortcut-card" onclick="location.href='routes.php?route=중앙아시아'">
                <div class="shortcut-title">중앙아시아 철도</div>
                <div class="shortcut-desc">타슈켄트, 알마티, 비슈케크</div>
            </div>
            <div class="shortcut-card" onclick="location.href='routes.php?route=중동아프리카'">
                <div class="shortcut-title">중동·아프리카 해상</div>
                <div class="shortcut-desc">리비아, 이집트, 두바이</div>
            </div>
            <div class="shortcut-card" onclick="location.href='routes.php?route=러시아'">
                <div class="shortcut-title">러시아 육로</div>
                <div class="shortcut-desc">블라디보스토크, 모스크바</div>
            </div>
            <div class="shortcut-card" onclick="location.href='routes.php?route=유럽'">
                <div class="shortcut-title">유럽 항공</div>
                <div class="shortcut-desc">독일, 폴란드, 체코</div>
            </div>
            <div class="shortcut-card" onclick="location.href='routes.php?route=동남아시아'">
                <div class="shortcut-title">동남아시아</div>
                <div class="shortcut-desc">베트남, 태국, 필리핀</div>
            </div>
            <div class="shortcut-card" onclick="location.href='routes.php?route=국내'">
                <div class="shortcut-title">국내 물류</div>
                <div class="shortcut-desc">전국 택배 및 화물</div>
            </div>
        </div>
    </div>
</div>

<!-- 공지 상세 모달 -->
<div class="modal-overlay" id="noticeModal">
    <div class="modal" style="max-width: 700px;">
        <div class="modal-header">
            <h3 id="noticeTitle">공지사항</h3>
            <button class="modal-close" onclick="closeModal('noticeModal')">&times;</button>
        </div>
        <div class="modal-body" id="noticeContent" style="min-height: 200px;"></div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('noticeModal')">닫기</button>
        </div>
    </div>
</div>

<?php
$pageScripts = <<<SCRIPT
<script>
async function viewNotice(id) {
    try {
        const response = await apiGet(CRM_URL + '/api/common/notices.php?id=' + id);
        const notice = response.data;

        document.getElementById('noticeTitle').textContent = notice.title;
        document.getElementById('noticeContent').innerHTML = `
            <div style="margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid #eee;">
                <span style="font-size: 13px; color: #666;">
                    작성자: \${notice.creator_name || '관리자'}
                    · \${notice.created_at?.substring(0, 10)}
                </span>
            </div>
            <div style="line-height: 1.8; white-space: pre-wrap;">\${notice.content || '(내용 없음)'}</div>
        `;

        openModal('noticeModal');
    } catch (error) {
        showToast('데이터를 불러올 수 없습니다.', 'error');
    }
}
</script>
SCRIPT;

include dirname(dirname(__DIR__)) . '/includes/footer.php';
?>
