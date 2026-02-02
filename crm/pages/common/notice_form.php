<?php
/**
 * 공지사항 등록/수정
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

// 관리자 권한 확인
if (!isAdmin()) {
    header('Location: notices.php');
    exit;
}

$pageTitle = '공지사항 등록';
$pageSubtitle = '새로운 공지사항을 작성합니다';

$pdo = getDB();

$id = $_GET['id'] ?? null;
$notice = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM " . CRM_NOTICES_TABLE . " WHERE id = ?");
    $stmt->execute([$id]);
    $notice = $stmt->fetch();

    if ($notice) {
        $pageTitle = '공지사항 수정';
        $pageSubtitle = '공지사항을 수정합니다';
    }
}

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
a { text-decoration: none; color: inherit; }

.container { max-width: 960px; margin: 0 auto; padding: 28px 20px 80px; }

/* Header */
.page-header { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 20px; flex-wrap: wrap; }
.header-left { display: flex; align-items: center; gap: 12px; }
.title-wrap { display: grid; gap: 6px; }
.page-title { font-size: 24px; font-weight: 800; }
.page-sub { font-size: 13px; color: #6c757d; }

/* Card */
.notice-card { background: #fff; border-radius: 10px; box-shadow: 0 8px 28px rgba(33,37,41,.06); overflow: hidden; }
.card-body { padding: 20px; }

/* Form */
.form-grid { display: grid; grid-template-columns: 1fr; gap: 16px; }
.row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
.label { font-size: 13px; color: #6c757d; margin-bottom: 6px; display: block; font-weight: 600; }
.input, .select, .textarea { width: 100%; border: 1px solid #dee2e6; border-radius: 8px; padding: 10px 12px; font-size: 14px; background: #fff; font-family: inherit; }
.input:focus, .select:focus, .textarea:focus { outline: none; border-color: #0d6efd; }
.textarea { min-height: 280px; resize: vertical; line-height: 1.6; }
.help { font-size: 12px; color: #adb5bd; margin-top: 6px; }

.checkbox-wrap { display: flex; align-items: center; gap: 8px; }
.checkbox-wrap input[type="checkbox"] { width: 18px; height: 18px; }
.checkbox-wrap label { font-size: 14px; color: #495057; cursor: pointer; }

.actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 16px; }
.header-right { display: flex; gap: 10px; }

@media (max-width: 700px) {
  .row-2, .row-3 { grid-template-columns: 1fr; }
  .actions { flex-direction: column-reverse; }
  .actions .btn { width: 100%; }
}
</style>

<div class="container">
    <div class="page-header">
        <div class="header-left">
            <a href="notices.php" class="btn btn-secondary">&larr; 목록으로</a>
            <div class="title-wrap">
                <div class="page-title"><?= $notice ? '공지사항 수정' : '공지사항 등록' ?></div>
                <div class="page-sub"><?= $notice ? '공지사항을 수정합니다' : '새로운 공지사항을 작성합니다' ?></div>
            </div>
        </div>
        <div class="header-right">
            <button type="button" class="btn btn-secondary" onclick="resetForm()">초기화</button>
            <button type="button" class="btn btn-primary" onclick="submitForm()"><?= $notice ? '수정' : '저장' ?></button>
        </div>
    </div>

    <div class="notice-card">
        <div class="card-body">
            <form id="noticeForm">
                <input type="hidden" name="id" value="<?= $notice['id'] ?? '' ?>">

                <div class="form-grid">
                    <div>
                        <label class="label" for="title">제목 <span style="color:#dc3545;">*</span></label>
                        <input class="input" id="title" name="title" placeholder="공지사항 제목을 입력하세요" value="<?= h($notice['title'] ?? '') ?>" required />
                    </div>

                    <div class="row-3">
                        <div>
                            <label class="label" for="priority">공지 분류 <span style="color:#dc3545;">*</span></label>
                            <select class="select" id="priority" name="priority">
                                <option value="normal" <?= (($notice['notice_type'] ?? '') !== 'urgent' && !($notice['is_important'] ?? 0)) ? 'selected' : '' ?>>일반</option>
                                <option value="important" <?= (($notice['notice_type'] ?? '') !== 'urgent' && ($notice['is_important'] ?? 0)) ? 'selected' : '' ?>>중요</option>
                                <option value="urgent" <?= ($notice['notice_type'] ?? '') === 'urgent' ? 'selected' : '' ?>>긴급</option>
                            </select>
                            <div class="help">긴급/중요 공지는 상단에 고정됩니다</div>
                        </div>
                        <div>
                            <label class="label" for="notice_type">공지 대상</label>
                            <select class="select" id="notice_type" name="notice_type">
                                <option value="company" <?= ($notice['notice_type'] ?? 'company') === 'company' || ($notice['notice_type'] ?? '') === '' ? 'selected' : '' ?>>전사 공지</option>
                                <option value="department" <?= ($notice['notice_type'] ?? '') === 'department' ? 'selected' : '' ?>>부서 공지</option>
                            </select>
                        </div>
                        <div>
                            <label class="label" for="department">대상 부서</label>
                            <select class="select" id="department" name="department">
                                <option value="">전체</option>
                                <option value="international" <?= ($notice['department'] ?? '') === 'international' ? 'selected' : '' ?>>국제물류</option>
                                <option value="agricultural" <?= ($notice['department'] ?? '') === 'agricultural' ? 'selected' : '' ?>>농산물</option>
                                <option value="pellet" <?= ($notice['department'] ?? '') === 'pellet' ? 'selected' : '' ?>>우드펠렛</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="label" for="content">내용 <span style="color:#dc3545;">*</span></label>
                        <textarea class="textarea" id="content" name="content" placeholder="공지사항 내용을 입력하세요" required><?= h($notice['content'] ?? '') ?></textarea>
                    </div>

                    <input type="hidden" id="is_important" name="is_important" value="<?= ($notice['is_important'] ?? 0) ?>">

                    <div class="actions">
                        <a href="notices.php" class="btn btn-secondary">취소</a>
                        <?php if ($notice): ?>
                            <button type="button" class="btn btn-danger" onclick="deleteNotice()">삭제</button>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary"><?= $notice ? '수정' : '저장' ?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$pageScripts = <<<SCRIPT
<script>
// 폼 초기화
function resetForm() {
    document.getElementById('noticeForm').reset();
}

// 폼 제출
function submitForm() {
    document.getElementById('noticeForm').dispatchEvent(new Event('submit'));
}

document.getElementById('noticeForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const title = document.getElementById('title').value.trim();
    const content = document.getElementById('content').value.trim();

    if (!title) {
        showToast('제목을 입력해주세요.', 'error');
        return;
    }
    if (!content) {
        showToast('내용을 입력해주세요.', 'error');
        return;
    }

    const id = document.querySelector('input[name="id"]').value;
    const priority = document.getElementById('priority').value;
    let noticeType = document.getElementById('notice_type').value;

    // 긴급인 경우 notice_type을 'urgent'로 설정
    if (priority === 'urgent') {
        noticeType = 'urgent';
    }

    // 중요/긴급 공지는 is_important = 1로 설정
    const isImportant = (priority === 'important' || priority === 'urgent') ? 1 : 0;

    const data = {
        action: id ? 'update' : 'create',
        id: id || undefined,
        title: title,
        content: content,
        notice_type: noticeType,
        department: document.getElementById('department').value || null,
        is_important: isImportant
    };

    try {
        const response = await apiPost(CRM_URL + '/api/common/notices.php', data);

        if (response.success) {
            showToast(response.message || '저장되었습니다.', 'success');
            setTimeout(() => {
                location.href = 'notices.php';
            }, 1000);
        } else {
            showToast(response.message || '저장 중 오류가 발생했습니다.', 'error');
        }
    } catch (error) {
        showToast('저장 중 오류가 발생했습니다.', 'error');
    }
});

// 삭제
async function deleteNotice() {
    if (!confirm('정말 삭제하시겠습니까?')) return;

    const id = document.querySelector('input[name="id"]').value;

    try {
        const response = await apiPost(CRM_URL + '/api/common/notices.php', {
            action: 'delete',
            id: id
        });

        showToast('삭제되었습니다.', 'success');
        setTimeout(() => {
            location.href = 'notices.php';
        }, 1000);
    } catch (error) {
        showToast('삭제 중 오류가 발생했습니다.', 'error');
    }
}
</script>
SCRIPT;

include dirname(dirname(__DIR__)) . '/includes/footer.php';
?>
