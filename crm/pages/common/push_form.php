<?php
/**
 * 푸시알림 작성/수정
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

// 관리자만 접근 가능
if (!isAdmin()) {
    header('Location: push.php');
    exit;
}

$pageTitle = '알림 작성';
$pageSubtitle = '푸시 알림을 작성합니다';

$pdo = getDB();

$id = $_GET['id'] ?? null;
$push = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM " . CRM_PUSH_TABLE . " WHERE id = ?");
    $stmt->execute([$id]);
    $push = $stmt->fetch();

    if ($push) {
        $pageTitle = '알림 수정';
        $pageSubtitle = '푸시 알림을 수정합니다';
    }
}

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<style>
* { margin:0; padding:0; box-sizing:border-box; }

.container {
    max-width:900px;
    margin:0 auto;
    padding:20px;
}

/* 페이지 헤더 */
.page-header {
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

/* 카드 */
.push-card {
    background:#fff;
    padding:32px;
    border-radius:8px;
    box-shadow:0 1px 3px rgba(0,0,0,0.1);
}

/* 폼 */
.form-group {
    margin-bottom:24px;
}
.form-label {
    display:block;
    font-size:14px;
    font-weight:600;
    margin-bottom:8px;
    color:#212529;
}
.form-label .required {
    color:#ff6b6b;
    margin-left:2px;
}
.form-input,
.form-select,
.form-textarea {
    width:100%;
    padding:10px 14px;
    border:1px solid #ced4da;
    border-radius:6px;
    font-size:14px;
    font-family:inherit;
}
.form-input:focus,
.form-select:focus,
.form-textarea:focus {
    outline:none;
    border-color:#4a90e2;
    box-shadow:0 0 0 3px rgba(74,144,226,0.1);
}
.form-textarea {
    min-height:150px;
    resize:vertical;
}
.form-row {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:16px;
}
.form-help {
    font-size:12px;
    color:#6c757d;
    margin-top:6px;
}

/* 예약 발송 체크박스 */
.checkbox-group {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
}
.checkbox-group input[type="checkbox"] {
    width: 18px;
    height: 18px;
}
.schedule-section {
    display: none;
    padding: 16px;
    background: #f8f9fa;
    border-radius: 8px;
    margin-top: 12px;
}
.schedule-section.active {
    display: block;
}

/* 버튼 */
.form-actions {
    display:flex;
    gap:12px;
    justify-content:flex-end;
    margin-top:32px;
    padding-top:24px;
    border-top:1px solid #e9ecef;
}

.notice-box {
    background: #fef3c7;
    border: 1px solid #fcd34d;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 24px;
    color: #92400e;
}

/* 미리보기 */
.preview-card {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 20px;
    margin-top: 24px;
}
.preview-title {
    font-size: 14px;
    font-weight: 600;
    color: #666;
    margin-bottom: 16px;
}
.preview-notification {
    background: white;
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.preview-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}
.preview-icon {
    width: 40px;
    height: 40px;
    background: var(--primary);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
}
.preview-app {
    font-size: 14px;
    font-weight: 600;
    color: #333;
}
.preview-time {
    font-size: 12px;
    color: #999;
}
.preview-content-title {
    font-size: 15px;
    font-weight: 600;
    color: #212529;
    margin-bottom: 4px;
}
.preview-content-message {
    font-size: 14px;
    color: #666;
    line-height: 1.4;
}

/* 반응형 */
@media (max-width:768px) {
    .form-row {
        grid-template-columns:1fr;
    }
    .form-actions {
        flex-direction:column-reverse;
    }
    .form-actions .btn {
        width:100%;
    }
}
</style>

<div class="container">
    <!-- 페이지 헤더 -->
    <div class="page-header">
        <div class="page-title"><?= $push ? '알림 수정' : '알림 작성' ?></div>
        <div class="page-subtitle"><?= $push ? '푸시 알림을 수정합니다' : '새로운 푸시 알림을 작성합니다' ?></div>
    </div>

    <div class="notice-box">
        <strong>안내:</strong> 푸시 알림 기능은 현재 UI만 구현되어 있습니다. 실제 알림 발송은 추후 Firebase/SMS 연동 후 가능합니다.
    </div>

    <div class="push-card">
        <form id="pushForm">
            <input type="hidden" name="id" value="<?= $push['id'] ?? '' ?>">

            <!-- 제목 -->
            <div class="form-group">
                <label class="form-label">알림 제목<span class="required">*</span></label>
                <input type="text" name="title" id="titleInput" class="form-input" placeholder="알림 제목을 입력하세요" value="<?= h($push['title'] ?? '') ?>" required maxlength="100">
                <div class="form-help">최대 100자</div>
            </div>

            <!-- 메시지 -->
            <div class="form-group">
                <label class="form-label">알림 내용<span class="required">*</span></label>
                <textarea name="message" id="messageInput" class="form-textarea" placeholder="알림 내용을 입력하세요" required maxlength="500"><?= h($push['message'] ?? '') ?></textarea>
                <div class="form-help">최대 500자</div>
            </div>

            <!-- 채널 & 대상 -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">발송 채널<span class="required">*</span></label>
                    <select name="channel" class="form-select" required>
                        <option value="app" <?= ($push['channel'] ?? 'app') === 'app' ? 'selected' : '' ?>>앱 푸시</option>
                        <option value="sms" <?= ($push['channel'] ?? '') === 'sms' ? 'selected' : '' ?>>SMS</option>
                        <option value="email" <?= ($push['channel'] ?? '') === 'email' ? 'selected' : '' ?>>이메일</option>
                        <option value="all" <?= ($push['channel'] ?? '') === 'all' ? 'selected' : '' ?>>전체 (앱+SMS+이메일)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">발송 대상<span class="required">*</span></label>
                    <select name="target_audience" class="form-select" required>
                        <option value="all" <?= ($push['target_audience'] ?? 'all') === 'all' ? 'selected' : '' ?>>전체 사용자</option>
                        <option value="admin" <?= ($push['target_audience'] ?? '') === 'admin' ? 'selected' : '' ?>>관리자만</option>
                        <option value="international" <?= ($push['target_audience'] ?? '') === 'international' ? 'selected' : '' ?>>국제물류팀</option>
                        <option value="agriculture" <?= ($push['target_audience'] ?? '') === 'agriculture' ? 'selected' : '' ?>>농산물팀</option>
                        <option value="pellet" <?= ($push['target_audience'] ?? '') === 'pellet' ? 'selected' : '' ?>>우드펠렛팀</option>
                    </select>
                </div>
            </div>

            <!-- 캠페인명 -->
            <div class="form-group">
                <label class="form-label">캠페인명</label>
                <input type="text" name="campaign_name" class="form-input" placeholder="예: 2024년 신년 인사" value="<?= h($push['campaign_name'] ?? '') ?>" maxlength="100">
                <div class="form-help">관리용 캠페인명 (선택사항)</div>
            </div>

            <!-- 예약 발송 -->
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" id="scheduleCheck" <?= !empty($push['scheduled_time']) ? 'checked' : '' ?>>
                    <label for="scheduleCheck" style="font-weight: 500; cursor: pointer;">예약 발송</label>
                </div>
                <div class="schedule-section <?= !empty($push['scheduled_time']) ? 'active' : '' ?>" id="scheduleSection">
                    <label class="form-label">예약 시간</label>
                    <input type="datetime-local" name="scheduled_time" class="form-input" value="<?= $push['scheduled_time'] ? date('Y-m-d\TH:i', strtotime($push['scheduled_time'])) : '' ?>">
                </div>
            </div>

            <!-- 미리보기 -->
            <div class="preview-card">
                <div class="preview-title">알림 미리보기</div>
                <div class="preview-notification">
                    <div class="preview-header">
                        <div class="preview-icon">🔔</div>
                        <div>
                            <div class="preview-app">선일쉬핑 CRM</div>
                            <div class="preview-time">방금 전</div>
                        </div>
                    </div>
                    <div class="preview-content-title" id="previewTitle">알림 제목</div>
                    <div class="preview-content-message" id="previewMessage">알림 내용이 여기에 표시됩니다.</div>
                </div>
            </div>

            <!-- 버튼 -->
            <div class="form-actions">
                <a href="push.php" class="btn btn-secondary">취소</a>
                <?php if ($push): ?>
                    <button type="button" class="btn btn-danger" onclick="deletePush()">삭제</button>
                <?php endif; ?>
                <button type="button" class="btn btn-outline-primary" onclick="saveDraft()">임시저장</button>
                <button type="submit" class="btn btn-primary"><?= $push && $push['status'] === 'sent' ? '재발송' : '발송하기' ?></button>
            </div>
        </form>
    </div>
</div>

<?php
$pageScripts = <<<SCRIPT
<script>
// 미리보기 업데이트
const titleInput = document.getElementById('titleInput');
const messageInput = document.getElementById('messageInput');
const previewTitle = document.getElementById('previewTitle');
const previewMessage = document.getElementById('previewMessage');

titleInput.addEventListener('input', function() {
    previewTitle.textContent = this.value || '알림 제목';
});

messageInput.addEventListener('input', function() {
    previewMessage.textContent = this.value || '알림 내용이 여기에 표시됩니다.';
});

// 초기 미리보기 설정
if (titleInput.value) previewTitle.textContent = titleInput.value;
if (messageInput.value) previewMessage.textContent = messageInput.value;

// 예약 발송 토글
document.getElementById('scheduleCheck').addEventListener('change', function() {
    document.getElementById('scheduleSection').classList.toggle('active', this.checked);
});

// 폼 제출 (발송)
document.getElementById('pushForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    await submitForm('send');
});

// 임시저장
async function saveDraft() {
    await submitForm('draft');
}

async function submitForm(action) {
    const formData = new FormData(document.getElementById('pushForm'));
    formData.append('action', action);

    // 예약 발송 체크 안되어 있으면 scheduled_time 제거
    if (!document.getElementById('scheduleCheck').checked) {
        formData.delete('scheduled_time');
    }

    try {
        const response = await fetch(CRM_URL + '/api/common/push.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showToast(result.message, 'success');
            setTimeout(() => {
                location.href = 'push.php';
            }, 1000);
        } else {
            showToast(result.message || '저장 중 오류가 발생했습니다.', 'error');
        }
    } catch (error) {
        showToast('저장 중 오류가 발생했습니다.', 'error');
    }
}

// 삭제
async function deletePush() {
    if (!confirm('정말 삭제하시겠습니까?')) return;

    const id = document.querySelector('input[name="id"]').value;

    try {
        const response = await apiPost(CRM_URL + '/api/common/push.php', {
            action: 'delete',
            id: id
        });

        if (response.success) {
            showToast('삭제되었습니다.', 'success');
            setTimeout(() => {
                location.href = 'push.php';
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
