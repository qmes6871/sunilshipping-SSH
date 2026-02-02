<?php
/**
 * 루트 주의사항 상세보기
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pdo = getDB();

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: routes.php');
    exit;
}

// 주의사항 조회
try {
    $stmt = $pdo->prepare("SELECT w.*, u.name as creator_name
        FROM " . CRM_ROUTES_TABLE . " w
        LEFT JOIN " . CRM_USERS_TABLE . " u ON w.created_by = u.id
        WHERE w.id = ?");
    $stmt->execute([$id]);
    $warning = $stmt->fetch();
} catch (Exception $e) {
    $warning = null;
}

if (!$warning) {
    header('Location: routes.php');
    exit;
}

$pageTitle = $warning['title'];
$pageSubtitle = '루트 주의사항 상세';

// 상태 레이블
$statusLabels = ['urgent' => '긴급', 'important' => '중요', 'normal' => '안내'];
$statusLabel = $statusLabels[$warning['status']] ?? '안내';

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

.container {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
}

/* 페이지 헤더 */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 24px;
    gap: 16px;
}
.header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}
.page-title {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 4px;
    word-break: keep-all;
}
.page-subtitle {
    font-size: 14px;
    color: #6c757d;
}
.header-actions {
    display: flex;
    gap: 8px;
    flex-shrink: 0;
}

/* 카드 */
.detail-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow: hidden;
}

/* 상단 메타 정보 */
.detail-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    padding: 20px 24px;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
}
.meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
}
.meta-label {
    font-size: 13px;
    color: #6c757d;
}
.meta-value {
    font-size: 14px;
    font-weight: 500;
    color: #212529;
}

/* 상태 배지 */
.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
.status-badge.urgent {
    background: #ffe3e3;
    color: #c92a2a;
}
.status-badge.important {
    background: #fff4e6;
    color: #d9480f;
}
.status-badge.normal {
    background: #e7f5ff;
    color: #1c7ed6;
}

/* 본문 */
.detail-content {
    padding: 24px;
}
.content-body {
    font-size: 15px;
    line-height: 1.8;
    color: #333;
    white-space: pre-wrap;
    word-break: keep-all;
}

/* 첨부파일 */
.attachment-section {
    padding: 20px 24px;
    border-top: 1px solid #e9ecef;
    background: #fafbfc;
}
.attachment-title {
    font-size: 14px;
    font-weight: 600;
    color: #495057;
    margin-bottom: 12px;
}
.attachment-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    color: #4a90e2;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.2s;
}
.attachment-link:hover {
    background: #f8f9fa;
    border-color: #4a90e2;
}

/* 반응형 */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }
    .header-actions {
        width: 100%;
    }
    .header-actions .btn {
        flex: 1;
    }
    .detail-meta {
        flex-direction: column;
        gap: 12px;
    }
}
</style>

<div class="container">
    <!-- 페이지 헤더 -->
    <div class="page-header">
        <div class="header-left">
            <a href="routes.php" class="btn btn-secondary">&larr; 목록으로</a>
            <div>
                <div class="page-title"><?= h($warning['title']) ?></div>
                <div class="page-subtitle">루트 주의사항 상세</div>
            </div>
        </div>
        <?php if (isAdmin()): ?>
        <div class="header-actions">
            <a href="route_form.php?id=<?= $warning['id'] ?>" class="btn btn-primary">수정</a>
            <button type="button" class="btn btn-danger" onclick="deleteWarning(<?= $warning['id'] ?>)">삭제</button>
        </div>
        <?php endif; ?>
    </div>

    <!-- 상세 카드 -->
    <div class="detail-card">
        <!-- 메타 정보 -->
        <div class="detail-meta">
            <div class="meta-item">
                <span class="meta-label">상태</span>
                <span class="status-badge <?= $warning['status'] ?>"><?= $statusLabel ?></span>
            </div>
            <div class="meta-item">
                <span class="meta-label">루트</span>
                <span class="meta-value"><?= h($warning['route_name'] ?? '-') ?></span>
            </div>
            <div class="meta-item">
                <span class="meta-label">등록자</span>
                <span class="meta-value"><?= h($warning['creator_name'] ?? '관리자') ?></span>
            </div>
            <div class="meta-item">
                <span class="meta-label">등록일</span>
                <span class="meta-value"><?= formatDate($warning['created_at'] ?? '', 'Y-m-d H:i') ?></span>
            </div>
            <?php if (!empty($warning['updated_at'])): ?>
            <div class="meta-item">
                <span class="meta-label">수정일</span>
                <span class="meta-value"><?= formatDate($warning['updated_at'], 'Y-m-d H:i') ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- 본문 -->
        <div class="detail-content">
            <div class="content-body"><?= nl2br(h($warning['content'] ?? '(내용 없음)')) ?></div>
        </div>

        <!-- 첨부파일 -->
        <?php if (!empty($warning['attachment_path'])): ?>
        <div class="attachment-section">
            <div class="attachment-title">첨부파일</div>
            <a href="<?= CRM_UPLOAD_URL ?>/<?= h($warning['attachment_path']) ?>" class="attachment-link" target="_blank" download>
                <span>📎</span>
                <span><?= basename($warning['attachment_path']) ?></span>
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$pageScripts = <<<SCRIPT
<script>
async function deleteWarning(id) {
    if (!confirm('정말 삭제하시겠습니까?')) return;

    try {
        const response = await apiPost(CRM_URL + '/api/common/routes.php', {
            action: 'delete',
            id: id
        });

        if (response.success) {
            showToast('삭제되었습니다.', 'success');
            setTimeout(() => {
                location.href = 'routes.php';
            }, 1000);
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
