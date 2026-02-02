<?php
/**
 * 푸시 알림 발송 내역
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pageTitle = '푸시 알림 발송 내역';
$pageSubtitle = '발송 채널, 예약 상태, 타겟 고객을 관리하고 발송 내역을 모니터링합니다.';

$pdo = getDB();

// 필터
$channel = $_GET['channel'] ?? '';
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;

$where = ["1=1"];
$params = [];

if ($channel) {
    $where[] = "channel = ?";
    $params[] = $channel;
}
if ($status) {
    $where[] = "status = ?";
    $params[] = $status;
}
if ($search) {
    $where[] = "(title LIKE ? OR message LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereClause = implode(' AND ', $where);

// 총 개수
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM " . CRM_PUSH_TABLE . " WHERE {$whereClause}");
    $stmt->execute($params);
    $totalCount = $stmt->fetchColumn();
} catch (Exception $e) {
    $totalCount = 0;
}

$totalPages = ceil($totalCount / $perPage);
$offset = ($page - 1) * $perPage;

// 알림 목록 조회
try {
    $stmt = $pdo->prepare("SELECT p.*, u.name as creator_name
        FROM " . CRM_PUSH_TABLE . " p
        LEFT JOIN " . CRM_USERS_TABLE . " u ON p.created_by = u.id
        WHERE {$whereClause}
        ORDER BY p.created_at DESC
        LIMIT {$perPage} OFFSET {$offset}");
    $stmt->execute($params);
    $notifications = $stmt->fetchAll();
} catch (Exception $e) {
    $notifications = [];
}

// 통계
try {
    $stmt = $pdo->query("SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
        SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
        FROM " . CRM_PUSH_TABLE);
    $stats = $stmt->fetch();
} catch (Exception $e) {
    $stats = ['total' => 0, 'sent' => 0, 'scheduled' => 0, 'draft' => 0, 'failed' => 0];
}

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

.container { max-width: 1400px; margin: 0 auto; padding: 20px; }

/* 페이지 헤더 */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}
.header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}
.page-title {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 6px;
}
.page-sub {
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

.page-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

/* 통계 */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
.stat-card {
    background: white;
    padding: 20px;
    border-radius: 14px;
    text-align: center;
    box-shadow: 0 10px 35px rgba(15,23,42,0.06);
}
.stat-value {
    font-size: 32px;
    font-weight: 700;
}
.stat-value.total { color: #4a6ee0; }
.stat-value.sent { color: #16803d; }
.stat-value.scheduled { color: #a06000; }
.stat-value.draft { color: #868e96; }
.stat-value.failed { color: #dc2626; }
.stat-label {
    font-size: 13px;
    color: #666;
    margin-top: 4px;
}

/* 필터 */
.filter-panel {
    background: white;
    border-radius: 14px;
    box-shadow: 0 10px 35px rgba(15,23,42,0.06);
    padding: 20px;
    margin-bottom: 24px;
}
.filter-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}
.tab-group {
    display: flex;
    gap: 8px;
}
.tab {
    padding: 8px 14px;
    border-radius: 20px;
    border: 1px solid #dee2e6;
    font-weight: 600;
    font-size: 13px;
    color: #6c757d;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
}
.tab:hover { background: #f8f9fa; }
.tab.active {
    background: #4a6ee0;
    border-color: #4a6ee0;
    color: #fff;
}
.filter-group {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.filter-group select,
.filter-group input {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 8px 12px;
    background: #fff;
    min-width: 150px;
    font-size: 14px;
}

/* 테이블 */
.table-panel {
    background: white;
    border-radius: 14px;
    box-shadow: 0 10px 35px rgba(15,23,42,0.06);
    overflow: hidden;
}
.table-wrapper {
    overflow-x: auto;
}
table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}
thead {
    background: #f1f3f5;
}
th {
    text-align: left;
    padding: 14px 16px;
    font-size: 12px;
    color: #6c757d;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    font-weight: 600;
}
td {
    padding: 16px;
    border-top: 1px solid #edf0f2;
    background: #fff;
    vertical-align: middle;
}
tbody tr:hover td {
    background: #f8fbff;
}

/* 상태 뱃지 */
.status {
    display: inline-flex;
    align-items: center;
    padding: 5px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
}
.status.sent { background: #e8fff3; color: #16803d; }
.status.scheduled { background: #fff6db; color: #a06000; }
.status.draft { background: #f1f3f5; color: #868e96; }
.status.failed { background: #fee2e2; color: #dc2626; }

/* 내용 셀 */
.content-title {
    font-weight: 600;
    color: #212529;
    margin-bottom: 4px;
}
.content-sub {
    font-size: 13px;
    color: #6c757d;
    line-height: 1.4;
}

/* 액션 버튼 */
.actions button {
    border: 1px solid #dee2e6;
    background: #fff;
    border-radius: 6px;
    padding: 6px 10px;
    font-size: 12px;
    margin-right: 6px;
    cursor: pointer;
    transition: all 0.2s;
}
.actions button:hover {
    background: #f8f9fa;
}

/* 페이지네이션 */
.pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    padding: 20px;
}
.pagination a, .pagination span {
    border: 1px solid #dee2e6;
    background: #fff;
    border-radius: 6px;
    padding: 8px 14px;
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    color: #495057;
}
.pagination a:hover { background: #f8f9fa; }
.pagination .active {
    background: #4a6ee0;
    border-color: #4a6ee0;
    color: #fff;
}

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
@media (max-width: 1200px) {
    .stats-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 768px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .page-header { flex-direction: column; align-items: stretch; }
    .filter-toolbar { flex-direction: column; align-items: stretch; }
    table { min-width: 900px; }
}
</style>

<div class="container">
    <!-- 페이지 헤더 -->
    <div class="page-header">
        <div class="header-left">
            <a href="notices.php" class="btn-back">← 뒤로가기</a>
            <div>
                <div class="page-title">푸시 알림 발송 내역</div>
                <div class="page-sub">발송 채널, 예약 상태, 타겟 고객을 관리하고 발송 내역을 모니터링합니다.</div>
            </div>
        </div>
        <div class="page-actions">
            <?php if (isAdmin()): ?>
            <a href="push.php" class="btn btn-secondary">푸시 알림 운영</a>
            <a href="push_form.php" class="btn btn-primary">+ 새 알림 만들기</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- 통계 -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value total"><?= $stats['total'] ?></div>
            <div class="stat-label">전체</div>
        </div>
        <div class="stat-card">
            <div class="stat-value sent"><?= $stats['sent'] ?></div>
            <div class="stat-label">발송완료</div>
        </div>
        <div class="stat-card">
            <div class="stat-value scheduled"><?= $stats['scheduled'] ?></div>
            <div class="stat-label">예약됨</div>
        </div>
        <div class="stat-card">
            <div class="stat-value draft"><?= $stats['draft'] ?></div>
            <div class="stat-label">임시저장</div>
        </div>
        <div class="stat-card">
            <div class="stat-value failed"><?= $stats['failed'] ?></div>
            <div class="stat-label">실패</div>
        </div>
    </div>

    <!-- 필터 -->
    <div class="filter-panel">
        <div class="filter-toolbar">
            <div class="tab-group">
                <a href="?" class="tab <?= $status === '' ? 'active' : '' ?>">전체</a>
                <a href="?status=sent" class="tab <?= $status === 'sent' ? 'active' : '' ?>">발송완료</a>
                <a href="?status=scheduled" class="tab <?= $status === 'scheduled' ? 'active' : '' ?>">예약</a>
                <a href="?status=failed" class="tab <?= $status === 'failed' ? 'active' : '' ?>">실패/재발송</a>
            </div>
            <form class="filter-group" method="GET">
                <input type="hidden" name="status" value="<?= h($status) ?>">
                <select name="channel">
                    <option value="">채널 전체</option>
                    <option value="app" <?= $channel === 'app' ? 'selected' : '' ?>>모바일 앱</option>
                    <option value="web" <?= $channel === 'web' ? 'selected' : '' ?>>웹 알림</option>
                    <option value="sms" <?= $channel === 'sms' ? 'selected' : '' ?>>SMS</option>
                </select>
                <input type="text" name="search" placeholder="검색 (제목/작성자)" value="<?= h($search) ?>">
                <button type="submit" class="btn btn-primary">검색</button>
            </form>
        </div>
    </div>

    <!-- 테이블 -->
    <div class="table-panel">
        <div class="table-wrapper">
            <table>
                <thead>
                <tr>
                    <th>상태</th>
                    <th>제목 / 메시지</th>
                    <th>대상 · 채널</th>
                    <th>발송/예약</th>
                    <th>전송 통계</th>
                    <th>관리</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($notifications)): ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <div class="icon">🔔</div>
                                <p>등록된 알림이 없습니다.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($notifications as $noti): ?>
                        <tr>
                            <td>
                                <span class="status <?= $noti['status'] ?>">
                                    <?php
                                    $statusLabels = ['sent' => '발송완료', 'scheduled' => '예약', 'draft' => '임시저장', 'failed' => '실패'];
                                    echo $statusLabels[$noti['status']] ?? $noti['status'];
                                    ?>
                                </span>
                            </td>
                            <td>
                                <div class="content-title"><?= h($noti['title']) ?></div>
                                <div class="content-sub"><?= h(mb_substr($noti['message'] ?? '', 0, 60)) ?>...</div>
                            </td>
                            <td>
                                <?= h($noti['target_audience'] ?? '전체 고객') ?><br>
                                <?php
                                $channelLabels = ['app' => '모바일 앱', 'web' => '웹 알림', 'sms' => 'SMS'];
                                echo $channelLabels[$noti['channel']] ?? ($noti['channel'] ?? '앱');
                                ?>
                            </td>
                            <td>
                                <?php if ($noti['status'] === 'scheduled' && $noti['scheduled_time']): ?>
                                    예약 <?= formatDate($noti['scheduled_time'], 'Y-m-d H:i') ?>
                                <?php elseif ($noti['sent_at']): ?>
                                    <?= formatDate($noti['sent_at'], 'Y-m-d H:i') ?>
                                <?php else: ?>
                                    <?= formatDate($noti['created_at'], 'Y-m-d H:i') ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($noti['status'] === 'sent'): ?>
                                    대상 <?= number_format($noti['target_count'] ?? 0) ?>명<br>
                                    성공 <?= number_format($noti['success_count'] ?? 0) ?>명
                                    (<?= ($noti['target_count'] > 0) ? round(($noti['success_count'] / $noti['target_count']) * 100, 1) : 0 ?>%)
                                <?php elseif ($noti['status'] === 'scheduled'): ?>
                                    예상 대상 <?= number_format($noti['target_count'] ?? 0) ?>명
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <button onclick="viewDetail(<?= $noti['id'] ?>)">상세</button>
                                <?php if ($noti['status'] === 'scheduled'): ?>
                                    <button onclick="cancelSchedule(<?= $noti['id'] ?>)">취소</button>
                                <?php elseif ($noti['status'] === 'draft'): ?>
                                    <button onclick="location.href='push_form.php?id=<?= $noti['id'] ?>'">수정</button>
                                    <button onclick="deleteNotification(<?= $noti['id'] ?>)">삭제</button>
                                <?php else: ?>
                                    <button onclick="copyNotification(<?= $noti['id'] ?>)">복제</button>
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
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">«</a>
                <?php endif; ?>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">»</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$pageScripts = <<<SCRIPT
<script>
function viewDetail(id) {
    showToast('상세 보기 기능은 추후 구현 예정입니다.', 'info');
}

function cancelSchedule(id) {
    if (!confirm('예약을 취소하시겠습니까?')) return;
    showToast('예약 취소 기능은 추후 구현 예정입니다.', 'info');
}

function deleteNotification(id) {
    if (!confirm('이 알림을 삭제하시겠습니까?')) return;
    showToast('삭제 기능은 추후 구현 예정입니다.', 'info');
}

function copyNotification(id) {
    showToast('복제 기능은 추후 구현 예정입니다.', 'info');
}
</script>
SCRIPT;

include dirname(dirname(__DIR__)) . '/includes/footer.php';
?>
