<?php
/**
 * 푸시알림 운영
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pageTitle = '푸시알림 운영';
$pageSubtitle = '알림 발송 관리';

$pdo = getDB();

$status = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;

$where = ["1=1"];
$params = [];

if ($status) {
    $where[] = "status = ?";
    $params[] = $status;
}

$whereClause = implode(' AND ', $where);

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM " . CRM_PUSH_TABLE . " WHERE {$whereClause}");
    $stmt->execute($params);
    $totalCount = $stmt->fetchColumn();
} catch (Exception $e) {
    $totalCount = 0;
}

$totalPages = ceil($totalCount / $perPage);
$offset = ($page - 1) * $perPage;

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
        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft
        FROM " . CRM_PUSH_TABLE);
    $stats = $stmt->fetch();
} catch (Exception $e) {
    $stats = ['total' => 0, 'sent' => 0, 'scheduled' => 0, 'draft' => 0];
}

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }

    @media (max-width: 768px) {
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
    }

    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 12px;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }

    .stat-value {
        font-size: 32px;
        font-weight: 700;
    }

    .stat-value.sent { color: #10b981; }
    .stat-value.scheduled { color: #3b82f6; }
    .stat-value.draft { color: #6b7280; }

    .stat-label {
        font-size: 13px;
        color: #666;
        margin-top: 4px;
    }

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
    }

    .filter-tab:hover { background: #e0e0e0; }
    .filter-tab.active { background: var(--primary); color: white; }

    .notification-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .notification-item {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }

    .notification-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 12px;
    }

    .notification-status {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }

    .status-sent { background: #d1fae5; color: #059669; }
    .status-scheduled { background: #dbeafe; color: #1d4ed8; }
    .status-draft { background: #f5f5f5; color: #666; }
    .status-failed { background: #fee2e2; color: #dc2626; }

    .notification-title {
        font-size: 18px;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 8px;
    }

    .notification-message {
        font-size: 14px;
        color: #666;
        line-height: 1.5;
        margin-bottom: 12px;
    }

    .notification-meta {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
        font-size: 13px;
        color: #888;
    }

    .notification-stats {
        display: flex;
        gap: 16px;
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #f0f0f0;
    }

    .notification-stat {
        text-align: center;
    }

    .notification-stat .value {
        font-size: 20px;
        font-weight: 700;
        color: var(--primary);
    }

    .notification-stat .label {
        font-size: 11px;
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
    }

    .notice-box {
        background: #fef3c7;
        border: 1px solid #fcd34d;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 24px;
        color: #92400e;
    }
</style>

<div class="notice-box">
    <strong>안내:</strong> 푸시 알림 기능은 현재 UI만 구현되어 있습니다. 실제 알림 발송은 추후 Firebase/SMS 연동 후 가능합니다.
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value"><?= $stats['total'] ?></div>
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
</div>

<div class="card" style="padding: 16px; margin-bottom: 24px;">
    <div class="filter-bar">
        <div class="filter-tabs">
            <a href="?status=" class="filter-tab <?= $status === '' ? 'active' : '' ?>">전체</a>
            <a href="?status=sent" class="filter-tab <?= $status === 'sent' ? 'active' : '' ?>">발송완료</a>
            <a href="?status=scheduled" class="filter-tab <?= $status === 'scheduled' ? 'active' : '' ?>">예약됨</a>
            <a href="?status=draft" class="filter-tab <?= $status === 'draft' ? 'active' : '' ?>">임시저장</a>
        </div>

        <?php if (isAdmin()): ?>
            <a href="push_form.php" class="btn btn-primary" style="margin-left: auto;">+ 알림 작성</a>
        <?php endif; ?>
    </div>
</div>

<div class="notification-list">
    <?php if (empty($notifications)): ?>
        <div class="card empty-state">
            <p style="font-size: 48px; margin-bottom: 16px;">🔔</p>
            <p>등록된 알림이 없습니다.</p>
        </div>
    <?php else: ?>
        <?php foreach ($notifications as $noti): ?>
            <div class="notification-item">
                <div class="notification-header">
                    <div>
                        <span class="notification-status status-<?= $noti['status'] ?>">
                            <?php
                            $statusLabels = ['sent' => '발송완료', 'scheduled' => '예약됨', 'draft' => '임시저장', 'failed' => '실패'];
                            echo $statusLabels[$noti['status']] ?? $noti['status'];
                            ?>
                        </span>
                        <?php if ($noti['campaign_name']): ?>
                            <span style="font-size: 12px; color: #666; margin-left: 8px;"><?= htmlspecialchars($noti['campaign_name']) ?></span>
                        <?php endif; ?>
                    </div>
                    <span style="font-size: 13px; color: #888;"><?= formatDate($noti['created_at'], 'Y-m-d H:i') ?></span>
                </div>

                <div class="notification-title"><?= htmlspecialchars($noti['title']) ?></div>
                <div class="notification-message"><?= htmlspecialchars($noti['message']) ?></div>

                <div class="notification-meta">
                    <span>채널: <?= htmlspecialchars($noti['channel'] ?? 'app') ?></span>
                    <span>대상: <?= htmlspecialchars($noti['target_audience'] ?? '전체') ?></span>
                    <?php if ($noti['scheduled_time']): ?>
                        <span>예약: <?= formatDate($noti['scheduled_time'], 'Y-m-d H:i') ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($noti['status'] === 'sent'): ?>
                    <div class="notification-stats">
                        <div class="notification-stat">
                            <div class="value"><?= number_format($noti['target_count']) ?></div>
                            <div class="label">발송대상</div>
                        </div>
                        <div class="notification-stat">
                            <div class="value"><?= number_format($noti['success_count']) ?></div>
                            <div class="label">성공</div>
                        </div>
                        <div class="notification-stat">
                            <div class="value"><?= number_format($noti['failure_count']) ?></div>
                            <div class="label">실패</div>
                        </div>
                    </div>
                <?php endif; ?>
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
