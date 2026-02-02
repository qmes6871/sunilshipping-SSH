<?php
/**
 * 공지사항 상세보기
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pdo = getDB();

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: notices.php');
    exit;
}

// 공지사항 조회
try {
    $stmt = $pdo->prepare("SELECT n.*, u.name as creator_name
        FROM " . CRM_NOTICES_TABLE . " n
        LEFT JOIN " . CRM_USERS_TABLE . " u ON n.created_by = u.id
        WHERE n.id = ?");
    $stmt->execute([$id]);
    $notice = $stmt->fetch();
} catch (Exception $e) {
    $notice = null;
}

if (!$notice) {
    header('Location: notices.php');
    exit;
}

$pageTitle = $notice['title'];
$pageSubtitle = '공지사항 상세';

// 공지 유형 레이블
$typeLabels = ['company' => '전체공지', 'department' => '부서공지', 'urgent' => '긴급공지'];
$typeLabel = $typeLabels[$notice['notice_type'] ?? ''] ?? '공지';

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

/* 타입 배지 */
.type-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
.type-badge.company { background: #e7f5ff; color: #1c7ed6; }
.type-badge.urgent { background: #ffe3e3; color: #c92a2a; }
.type-badge.department { background: #fff4e6; color: #d9480f; }

/* 중요 배지 */
.important-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    background: #ffe3e3;
    color: #c92a2a;
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
            <a href="notices.php" class="btn btn-secondary">&larr; 목록으로</a>
            <div>
                <div class="page-title"><?= h($notice['title']) ?></div>
                <div class="page-subtitle">공지사항 상세</div>
            </div>
        </div>
        <?php if (isAdmin()): ?>
        <div class="header-actions">
            <a href="notice_form.php?id=<?= $notice['id'] ?>" class="btn btn-primary">수정</a>
            <button type="button" class="btn btn-danger" onclick="deleteNotice(<?= $notice['id'] ?>)">삭제</button>
        </div>
        <?php endif; ?>
    </div>

    <!-- 상세 카드 -->
    <div class="detail-card">
        <!-- 메타 정보 -->
        <div class="detail-meta">
            <div class="meta-item">
                <span class="meta-label">유형</span>
                <span class="type-badge <?= $notice['notice_type'] ?? '' ?>"><?= $typeLabel ?></span>
            </div>
            <?php if (!empty($notice['is_important'])): ?>
            <div class="meta-item">
                <span class="important-badge">중요</span>
            </div>
            <?php endif; ?>
            <div class="meta-item">
                <span class="meta-label">작성자</span>
                <span class="meta-value"><?= h($notice['creator_name'] ?? '관리자') ?></span>
            </div>
            <div class="meta-item">
                <span class="meta-label">등록일</span>
                <span class="meta-value"><?= formatDate($notice['created_at'] ?? '', 'Y-m-d H:i') ?></span>
            </div>
            <?php if (!empty($notice['updated_at'])): ?>
            <div class="meta-item">
                <span class="meta-label">수정일</span>
                <span class="meta-value"><?= formatDate($notice['updated_at'], 'Y-m-d H:i') ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- 본문 -->
        <div class="detail-content">
            <div class="content-body"><?= nl2br(h($notice['content'] ?? '(내용 없음)')) ?></div>
        </div>
    </div>
</div>

<?php
$pageScripts = <<<SCRIPT
<script>
async function deleteNotice(id) {
    if (!confirm('정말 삭제하시겠습니까?')) return;

    try {
        const response = await apiPost(CRM_URL + '/api/common/notices.php', {
            action: 'delete',
            id: id
        });

        if (response.success) {
            showToast('삭제되었습니다.', 'success');
            setTimeout(() => {
                location.href = 'notices.php';
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
