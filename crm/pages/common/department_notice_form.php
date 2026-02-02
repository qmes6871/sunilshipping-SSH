<?php
/**
 * 부서 공지 작성
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pdo = getDB();

$id = $_GET['id'] ?? null;
$notice = null;
$isEdit = false;

if ($id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM " . CRM_DEPT_NOTICES_TABLE . " WHERE id = ?");
        $stmt->execute([$id]);
        $notice = $stmt->fetch();
        if ($notice) {
            $isEdit = true;
            // 조회수 증가
            $stmt = $pdo->prepare("UPDATE " . CRM_DEPT_NOTICES_TABLE . " SET view_count = view_count + 1 WHERE id = ?");
            $stmt->execute([$id]);
        }
    } catch (Exception $e) {}
}

$pageTitle = $isEdit ? '공지 수정' : '부서 공지 작성';

// POST 처리
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrfToken) {
        $message = 'CSRF 토큰이 유효하지 않습니다.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? 'save';

        if ($action === 'delete' && $isEdit) {
            try {
                $stmt = $pdo->prepare("DELETE FROM " . CRM_DEPT_NOTICES_TABLE . " WHERE id = ?");
                $stmt->execute([$id]);
                header('Location: department_notices.php');
                exit;
            } catch (Exception $e) {
                $message = '삭제 중 오류가 발생했습니다.';
                $messageType = 'error';
            }
        } else {
            $data = [
                'part' => $_POST['part'] ?? '',
                'priority' => $_POST['priority'] ?? 'normal',
                'title' => trim($_POST['title'] ?? ''),
                'content' => trim($_POST['content'] ?? '')
            ];

            if (empty($data['title'])) {
                $message = '제목을 입력해주세요.';
                $messageType = 'error';
            } elseif (empty($data['content'])) {
                $message = '내용을 입력해주세요.';
                $messageType = 'error';
            } else {
                try {
                    // 이미지 업로드 처리
                    $imagePath = $notice['image_path'] ?? null;
                    if (!empty($_FILES['image']['name'])) {
                        $result = uploadFile($_FILES['image'], 'dept_notices', ['image/jpeg', 'image/png', 'image/gif']);
                        if ($result['success']) {
                            if ($imagePath) deleteFile($imagePath);
                            $imagePath = $result['path'];
                        }
                    }

                    if ($isEdit) {
                        $stmt = $pdo->prepare("UPDATE " . CRM_DEPT_NOTICES_TABLE . "
                            SET part = ?, priority = ?, title = ?, content = ?, image_path = ?, updated_at = NOW()
                            WHERE id = ?");
                        $stmt->execute([$data['part'], $data['priority'], $data['title'], $data['content'], $imagePath, $id]);
                        $message = '공지가 수정되었습니다.';
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO " . CRM_DEPT_NOTICES_TABLE . "
                            (part, priority, title, content, image_path, created_by, created_at)
                            VALUES (?, ?, ?, ?, ?, ?, NOW())");
                        $stmt->execute([$data['part'], $data['priority'], $data['title'], $data['content'], $imagePath, $currentUser['crm_user_id']]);
                        header('Location: department_notices.php');
                        exit;
                    }
                    $messageType = 'success';

                    // 데이터 새로고침
                    $stmt = $pdo->prepare("SELECT * FROM " . CRM_DEPT_NOTICES_TABLE . " WHERE id = ?");
                    $stmt->execute([$id]);
                    $notice = $stmt->fetch();

                } catch (Exception $e) {
                    $message = '저장 중 오류가 발생했습니다.';
                    $messageType = 'error';
                }
            }
        }
    }
}

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

.container { max-width: 800px; margin: 0 auto; padding: 20px; }

.page-header { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; }
.btn-back { padding: 8px 16px; border: 1px solid #dee2e6; border-radius: 4px; background: white; color: #495057; cursor: pointer; font-size: 14px; text-decoration: none; }
.btn-back:hover { background: #f8f9fa; }
.page-title { font-size: 24px; font-weight: 600; color: #212529; }

.card { background: white; padding: 24px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }

.form-group { margin-bottom: 20px; }
.form-label { display: block; font-size: 14px; font-weight: 500; color: #495057; margin-bottom: 8px; }
.required { color: #dc3545; margin-left: 4px; }

.form-input, .form-select, .form-textarea {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    font-size: 14px;
    color: #212529;
    background: white;
    font-family: inherit;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
    outline: none;
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}

.form-textarea { min-height: 300px; resize: vertical; line-height: 1.6; }

.priority-options { display: flex; gap: 12px; }
.priority-btn {
    flex: 1;
    padding: 12px;
    border: 2px solid #dee2e6;
    border-radius: 6px;
    background: white;
    cursor: pointer;
    text-align: center;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
}
.priority-btn:hover { border-color: #0d6efd; background: #f8f9ff; }
.priority-btn.active { border-color: #0d6efd; background: #e7f1ff; color: #0d6efd; }
.priority-btn.important.active { border-color: #dc3545; background: #fff5f5; color: #dc3545; }

.category-buttons { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
.category-btn {
    padding: 12px;
    border: 2px solid #dee2e6;
    border-radius: 6px;
    background: white;
    cursor: pointer;
    text-align: center;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
}
.category-btn:hover { border-color: #0d6efd; background: #f8f9ff; }
.category-btn.active { border-color: #0d6efd; background: #e7f1ff; color: #0d6efd; }

.info-box { background: #f8f9fa; padding: 12px 16px; border-radius: 6px; font-size: 13px; color: #6c757d; }

.file-upload-area {
    border: 2px dashed #dee2e6;
    border-radius: 6px;
    padding: 20px;
    text-align: center;
    background: #f8f9fa;
    cursor: pointer;
    transition: all 0.2s;
}
.file-upload-area:hover { border-color: #0d6efd; background: #f8f9ff; }

.button-group { display: flex; gap: 12px; margin-top: 32px; }
.btn { flex: 1; padding: 14px 24px; border: none; border-radius: 6px; font-size: 15px; font-weight: 500; cursor: pointer; text-align: center; text-decoration: none; }
.btn-cancel { background: #f8f9fa; color: #495057; border: 1px solid #dee2e6; }
.btn-cancel:hover { background: #e9ecef; }
.btn-delete { background: #dc3545; color: white; }
.btn-delete:hover { background: #bb2d3b; }
.btn-save { background: #0d6efd; color: white; }
.btn-save:hover { background: #0b5ed7; }

.alert { padding: 16px; border-radius: 8px; margin-bottom: 24px; }
.alert-success { background: #d1e7dd; color: #0f5132; }
.alert-error { background: #f8d7da; color: #842029; }

@media (max-width: 768px) {
    .button-group { flex-direction: column; }
    .priority-options { flex-direction: column; }
    .category-buttons { grid-template-columns: repeat(2, 1fr); }
}
</style>

<div class="container">
    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>"><?= h($message) ?></div>
    <?php endif; ?>

    <div class="page-header">
        <a href="department_notices.php" class="btn-back">← 뒤로</a>
        <div class="page-title"><?= $isEdit ? '공지 수정' : '부서 공지 작성' ?></div>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <div class="card">
            <div class="form-group">
                <label class="form-label">카테고리<span class="required">*</span></label>
                <div class="category-buttons">
                    <label class="category-btn <?= ($notice['part'] ?? '') === '국제물류' || !$notice ? 'active' : '' ?>">
                        <input type="radio" name="part" value="국제물류" <?= ($notice['part'] ?? '국제물류') === '국제물류' ? 'checked' : '' ?> style="display:none;">
                        국제물류
                    </label>
                    <label class="category-btn <?= ($notice['part'] ?? '') === '농산물' ? 'active' : '' ?>">
                        <input type="radio" name="part" value="농산물" <?= ($notice['part'] ?? '') === '농산물' ? 'checked' : '' ?> style="display:none;">
                        농산물
                    </label>
                    <label class="category-btn <?= ($notice['part'] ?? '') === '우드펠렛' ? 'active' : '' ?>">
                        <input type="radio" name="part" value="우드펠렛" <?= ($notice['part'] ?? '') === '우드펠렛' ? 'checked' : '' ?> style="display:none;">
                        우드펠렛
                    </label>
                    <label class="category-btn <?= ($notice['part'] ?? '') === '무역' ? 'active' : '' ?>">
                        <input type="radio" name="part" value="무역" <?= ($notice['part'] ?? '') === '무역' ? 'checked' : '' ?> style="display:none;">
                        무역
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">중요도<span class="required">*</span></label>
                <div class="priority-options">
                    <label class="priority-btn important <?= ($notice['priority'] ?? '') === 'important' ? 'active' : '' ?>">
                        <input type="radio" name="priority" value="important" <?= ($notice['priority'] ?? '') === 'important' ? 'checked' : '' ?> style="display:none;">
                        중요
                    </label>
                    <label class="priority-btn <?= ($notice['priority'] ?? 'normal') === 'normal' ? 'active' : '' ?>">
                        <input type="radio" name="priority" value="normal" <?= ($notice['priority'] ?? 'normal') === 'normal' ? 'checked' : '' ?> style="display:none;">
                        일반
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">제목<span class="required">*</span></label>
                <input type="text" class="form-input" name="title" placeholder="공지 제목을 입력하세요" value="<?= h($notice['title'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">내용<span class="required">*</span></label>
                <textarea class="form-textarea" name="content" placeholder="공지 내용을 입력하세요" required><?= h($notice['content'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">사진 첨부</label>
                <?php if (!empty($notice['image_path'])): ?>
                    <p style="margin-bottom: 8px; font-size: 13px; color: #666;">
                        현재 이미지: <a href="<?= CRM_UPLOAD_URL ?>/<?= h($notice['image_path']) ?>" target="_blank" style="color: #0d6efd;">보기</a>
                    </p>
                <?php endif; ?>
                <div class="file-upload-area" onclick="document.getElementById('imageInput').click()">
                    <input type="file" id="imageInput" name="image" accept="image/*" style="display:none;">
                    <div style="font-size: 32px; margin-bottom: 8px;">📷</div>
                    <div style="font-size: 14px; color: #495057; margin-bottom: 4px;">클릭하여 사진 업로드</div>
                    <div style="font-size: 12px; color: #6c757d;">JPG, PNG, GIF 파일 지원 (최대 10MB)</div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">작성자</label>
                <div class="info-box"><?= h($currentUser['mb_name'] ?? $currentUser['mb_nick'] ?? '관리자') ?></div>
            </div>

            <div class="form-group">
                <label class="form-label">작성일</label>
                <div class="info-box"><?= date('Y.m.d') ?></div>
            </div>
        </div>

        <div class="button-group">
            <a href="department_notices.php" class="btn btn-cancel">취소</a>
            <?php if ($isEdit): ?>
                <button type="submit" name="action" value="delete" class="btn btn-delete" onclick="return confirm('정말 삭제하시겠습니까?')">삭제</button>
            <?php endif; ?>
            <button type="submit" name="action" value="save" class="btn btn-save"><?= $isEdit ? '수정하기' : '등록하기' ?></button>
        </div>
    </form>
</div>

<?php
$pageScripts = <<<'SCRIPT'
<script>
// 카테고리/중요도 버튼 클릭
document.querySelectorAll('.category-btn, .priority-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const group = this.closest('.category-buttons, .priority-options');
        group.querySelectorAll('.category-btn, .priority-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
    });
});
</script>
SCRIPT;

include dirname(dirname(__DIR__)) . '/includes/footer.php';
?>
