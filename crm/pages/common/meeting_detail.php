<?php
/**
 * 회의록 상세보기
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pdo = getDB();

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: meetings.php');
    exit;
}

// 회의록 조회
try {
    $stmt = $pdo->prepare("SELECT m.*, u.name as creator_name
        FROM " . CRM_MEETINGS_TABLE . " m
        LEFT JOIN " . CRM_USERS_TABLE . " u ON m.created_by = u.id
        WHERE m.id = ?");
    $stmt->execute([$id]);
    $meeting = $stmt->fetch();

    if ($meeting) {
        // 참석자 조회
        try {
            $stmt = $pdo->prepare("SELECT * FROM " . CRM_MEETING_ATTENDEES_TABLE . " WHERE meeting_id = ? ORDER BY is_creator DESC, attendee_name ASC");
            $stmt->execute([$id]);
            $meeting['attendees'] = $stmt->fetchAll();
        } catch (Exception $e) {
            $meeting['attendees'] = [];
        }
    }
} catch (Exception $e) {
    $meeting = null;
}

if (!$meeting) {
    header('Location: meetings.php');
    exit;
}

$pageTitle = $meeting['title'];
$pageSubtitle = '회의록 상세';

// 회의 유형 레이블
$typeLabels = ['regular' => '정기회의', 'emergency' => '긴급회의', 'project' => '프로젝트', 'other' => '기타'];
$typeLabel = $typeLabels[$meeting['meeting_type'] ?? ''] ?? ($meeting['meeting_type'] ?? '기타');

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
    margin-bottom: 20px;
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
    background: #e7f5ff;
    color: #1c7ed6;
}
.type-badge.regular { background: #e7f5ff; color: #1c7ed6; }
.type-badge.emergency { background: #ffe3e3; color: #c92a2a; }
.type-badge.project { background: #fff4e6; color: #d9480f; }

/* 본문 섹션 */
.detail-section {
    padding: 20px 24px;
    border-bottom: 1px solid #e9ecef;
}
.detail-section:last-child {
    border-bottom: none;
}
.section-title {
    font-size: 14px;
    font-weight: 600;
    color: #495057;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.section-content {
    font-size: 15px;
    line-height: 1.8;
    color: #333;
    white-space: pre-wrap;
    word-break: keep-all;
}

/* 참석자 */
.attendees-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.attendee-tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 6px 12px;
    background: #f1f3f4;
    border-radius: 20px;
    font-size: 13px;
    color: #495057;
}
.attendee-tag.creator {
    background: #4a90e2;
    color: #fff;
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
            <a href="meetings.php" class="btn btn-secondary">&larr; 목록으로</a>
            <div>
                <div class="page-title"><?= h($meeting['title']) ?></div>
                <div class="page-subtitle">회의록 상세</div>
            </div>
        </div>
        <?php if (isAdmin()): ?>
        <div class="header-actions">
            <a href="meeting_form.php?id=<?= $meeting['id'] ?>" class="btn btn-primary">수정</a>
            <button type="button" class="btn btn-danger" onclick="deleteMeeting(<?= $meeting['id'] ?>)">삭제</button>
        </div>
        <?php endif; ?>
    </div>

    <!-- 상세 카드 -->
    <div class="detail-card">
        <!-- 메타 정보 -->
        <div class="detail-meta">
            <div class="meta-item">
                <span class="meta-label">유형</span>
                <span class="type-badge <?= $meeting['meeting_type'] ?? '' ?>"><?= $typeLabel ?></span>
            </div>
            <div class="meta-item">
                <span class="meta-label">일시</span>
                <span class="meta-value">
                    <?= formatDate($meeting['meeting_date'] ?? '', 'Y-m-d') ?>
                    <?= !empty($meeting['meeting_time']) ? ' ' . substr($meeting['meeting_time'], 0, 5) : '' ?>
                </span>
            </div>
            <?php if (!empty($meeting['location'])): ?>
            <div class="meta-item">
                <span class="meta-label">장소</span>
                <span class="meta-value"><?= h($meeting['location']) ?></span>
            </div>
            <?php endif; ?>
            <div class="meta-item">
                <span class="meta-label">작성자</span>
                <span class="meta-value"><?= h($meeting['creator_name'] ?? '관리자') ?></span>
            </div>
            <div class="meta-item">
                <span class="meta-label">등록일</span>
                <span class="meta-value"><?= formatDate($meeting['created_at'] ?? '', 'Y-m-d H:i') ?></span>
            </div>
        </div>

        <!-- 참석자 -->
        <?php if (!empty($meeting['attendees'])): ?>
        <div class="detail-section">
            <div class="section-title">참석자</div>
            <div class="attendees-list">
                <?php foreach ($meeting['attendees'] as $attendee): ?>
                <span class="attendee-tag <?= $attendee['is_creator'] ? 'creator' : '' ?>">
                    <?= h($attendee['attendee_name']) ?>
                    <?= $attendee['is_creator'] ? '(작성자)' : '' ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- 녹음 파일 -->
        <?php if (!empty($meeting['audio_file'])): ?>
        <div class="detail-section">
            <div class="section-title">🎤 녹음 파일</div>
            <div style="padding: 16px; background: #f8f9fa; border-radius: 8px;">
                <audio controls style="width: 100%; max-width: 500px;">
                    <source src="<?= CRM_UPLOAD_URL ?>/<?= h($meeting['audio_file']) ?>" type="audio/mpeg">
                    브라우저가 오디오를 지원하지 않습니다.
                </audio>
                <div style="margin-top: 8px;">
                    <a href="<?= CRM_UPLOAD_URL ?>/<?= h($meeting['audio_file']) ?>" download class="btn btn-sm btn-outline" style="padding: 6px 12px; font-size: 13px;">
                        📥 다운로드
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 첨부 파일 -->
        <?php
        $attachments = !empty($meeting['attachments']) ? json_decode($meeting['attachments'], true) : [];
        if (!empty($attachments)):
        ?>
        <div class="detail-section">
            <div class="section-title">📎 첨부 파일</div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <?php foreach ($attachments as $attachment): ?>
                <div style="padding: 12px 16px; background: #f8f9fa; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 14px;"><?= h($attachment['name']) ?></span>
                    <a href="<?= CRM_UPLOAD_URL ?>/<?= h($attachment['path']) ?>" download class="btn btn-sm btn-outline" style="padding: 6px 12px; font-size: 13px;">
                        📥 다운로드
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- 안건 -->
        <?php if (!empty($meeting['agenda'])): ?>
        <div class="detail-section">
            <div class="section-title">안건</div>
            <div class="section-content"><?= nl2br(h($meeting['agenda'])) ?></div>
        </div>
        <?php endif; ?>

        <!-- 회의 내용 -->
        <?php if (!empty($meeting['content'])): ?>
        <div class="detail-section">
            <div class="section-title">회의 내용</div>
            <div class="section-content"><?= nl2br(h($meeting['content'])) ?></div>
        </div>
        <?php endif; ?>

        <!-- 결정 사항 -->
        <?php if (!empty($meeting['decisions'])): ?>
        <div class="detail-section">
            <div class="section-title">결정 사항</div>
            <div class="section-content"><?= nl2br(h($meeting['decisions'])) ?></div>
        </div>
        <?php endif; ?>

        <!-- 액션 아이템 -->
        <?php if (!empty($meeting['action_items'])): ?>
        <div class="detail-section">
            <div class="section-title">액션 아이템</div>
            <div class="section-content"><?= nl2br(h($meeting['action_items'])) ?></div>
        </div>
        <?php endif; ?>

        <!-- 다음 회의 -->
        <?php if (!empty($meeting['next_meeting_date'])): ?>
        <div class="detail-section">
            <div class="section-title">다음 회의 예정</div>
            <div class="section-content"><?= formatDate($meeting['next_meeting_date'], 'Y-m-d') ?></div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$pageScripts = <<<SCRIPT
<script>
async function deleteMeeting(id) {
    if (!confirm('정말 삭제하시겠습니까?')) return;

    try {
        const response = await apiPost(CRM_URL + '/api/common/meetings.php', {
            action: 'delete',
            id: id
        });

        if (response.success) {
            showToast('삭제되었습니다.', 'success');
            setTimeout(() => {
                location.href = 'meetings.php';
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
