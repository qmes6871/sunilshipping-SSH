<?php
/**
 * KMS 문서 등록
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pageTitle = 'KMS 문서 등록';
$pageSubtitle = '파트, 분류, 태그와 함께 문서를 등록합니다';

$pdo = getDB();

$id = $_GET['id'] ?? null;
$doc = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM " . CRM_KMS_TABLE . " WHERE id = ?");
    $stmt->execute([$id]);
    $doc = $stmt->fetch();

    if ($doc) {
        $pageTitle = 'KMS 문서 수정';
        $pageSubtitle = '문서를 수정합니다';
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
.kms-card { background: #fff; border-radius: 10px; box-shadow: 0 8px 28px rgba(33,37,41,.06); overflow: hidden; }
.card-body { padding: 20px; }

/* Form */
.form-grid { display: grid; grid-template-columns: 1fr; gap: 16px; }
.row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.label { font-size: 13px; color: #6c757d; margin-bottom: 6px; display: block; font-weight: 600; }
.input, .select, .textarea { width: 100%; border: 1px solid #dee2e6; border-radius: 8px; padding: 10px 12px; font-size: 14px; background: #fff; font-family: inherit; }
.input:focus, .select:focus, .textarea:focus { outline: none; border-color: #0d6efd; }
.textarea { min-height: 220px; resize: vertical; line-height: 1.6; }
.help { font-size: 12px; color: #adb5bd; margin-top: 6px; }
.file { padding: 10px 12px; border: 1px solid #dee2e6; border-radius: 8px; background: #fff; width: 100%; }

.actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 16px; }
.header-right { display: flex; gap: 10px; }

@media (max-width: 700px) {
  .row-2 { grid-template-columns: 1fr; }
  .actions { flex-direction: column-reverse; }
  .actions .btn { width: 100%; }
}
</style>

<div class="container">
    <div class="page-header">
        <div class="header-left">
            <a href="kms.php" class="btn btn-secondary">← 목록으로</a>
            <div class="title-wrap">
                <div class="page-title"><?= $doc ? 'KMS 문서 수정' : 'KMS 문서 등록' ?></div>
                <div class="page-sub">파트, 분류, 태그와 함께 문서를 등록합니다</div>
            </div>
        </div>
        <div class="header-right">
            <button type="button" class="btn btn-secondary" onclick="resetForm()">초기화</button>
            <button type="button" class="btn btn-primary" onclick="document.getElementById('kmsForm').dispatchEvent(new Event('submit'))"><?= $doc ? '수정' : '저장' ?></button>
        </div>
    </div>

    <div class="kms-card">
        <div class="card-body">
            <form id="kmsForm" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $doc['id'] ?? '' ?>">

                <div class="form-grid">
                    <div>
                        <label class="label" for="title">제목</label>
                        <input class="input" id="title" name="title" placeholder="문서 제목을 입력하세요" value="<?= h($doc['title'] ?? '') ?>" required />
                    </div>

                    <div class="row-2">
                        <div>
                            <label class="label" for="part">파트</label>
                            <select class="select" id="part" name="part" required>
                                <option value="">선택</option>
                                <option value="logi" <?= ($doc['part'] ?? '') === 'logi' ? 'selected' : '' ?>>국제물류</option>
                                <option value="agri" <?= ($doc['part'] ?? '') === 'agri' ? 'selected' : '' ?>>농산물</option>
                                <option value="pellet" <?= ($doc['part'] ?? '') === 'pellet' ? 'selected' : '' ?>>우드펠렛</option>
                                <option value="trade" <?= ($doc['part'] ?? '') === 'trade' ? 'selected' : '' ?>>무역</option>
                            </select>
                        </div>
                        <div>
                            <label class="label" for="classification">분류</label>
                            <select class="select" id="classification" name="classification" required>
                                <option value="">선택</option>
                                <option value="guide" <?= ($doc['classification'] ?? '') === 'guide' ? 'selected' : '' ?>>가이드</option>
                                <option value="check" <?= ($doc['classification'] ?? '') === 'check' ? 'selected' : '' ?>>체크리스트</option>
                                <option value="notice" <?= ($doc['classification'] ?? '') === 'notice' ? 'selected' : '' ?>>공지</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="label" for="tags">태그</label>
                        <input class="input" id="tags" name="tags" placeholder="쉼표(,)로 구분해 입력 (예: 철도, 운송, 서류)" value="<?= h($doc['tags'] ?? '') ?>" />
                        <div class="help">예시: 철도, 운송, 서류</div>
                    </div>

                    <div>
                        <label class="label" for="content">내용</label>
                        <textarea class="textarea" id="content" name="content" placeholder="문서 내용을 입력하세요" required><?= h($doc['content'] ?? '') ?></textarea>
                    </div>

                    <div>
                        <label class="label" for="files">첨부파일</label>
                        <?php if (!empty($doc['attachment_path'])):
                            $ext = strtolower(pathinfo($doc['attachment_path'], PATHINFO_EXTENSION));
                            $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']);
                        ?>
                            <div style="margin-bottom: 12px; padding: 12px; background: #f8f9fa; border-radius: 8px;">
                                <?php if ($isImage): ?>
                                    <div style="margin-bottom: 8px;">
                                        <img src="<?= CRM_UPLOAD_URL ?>/<?= h($doc['attachment_path']) ?>" style="max-width: 100%; max-height: 200px; border-radius: 6px; cursor: pointer;" onclick="window.open('<?= CRM_UPLOAD_URL ?>/<?= h($doc['attachment_path']) ?>', '_blank')" />
                                    </div>
                                <?php endif; ?>
                                <p style="color: #666; font-size: 13px; display: flex; align-items: center; gap: 8px;">
                                    <span>현재 파일:</span>
                                    <a href="<?= CRM_UPLOAD_URL ?>/<?= h($doc['attachment_path']) ?>" target="_blank" style="color: #0d6efd;"><?= basename($doc['attachment_path']) ?></a>
                                </p>
                            </div>
                        <?php endif; ?>
                        <input type="file" class="file" id="files" name="attachment" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt" />
                        <div class="help">이미지, PDF, Office 문서 등을 첨부할 수 있습니다.</div>
                    </div>

                    <div class="actions">
                        <a href="kms.php" class="btn btn-secondary">취소</a>
                        <?php if ($doc): ?>
                            <button type="button" class="btn btn-danger" onclick="deleteDoc()">삭제</button>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary"><?= $doc ? '수정' : '저장' ?></button>
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
    document.getElementById('kmsForm').reset();
}

// 폼 제출
document.getElementById('kmsForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    try {
        const response = await fetch(CRM_URL + '/api/common/kms.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showToast(result.message, 'success');
            setTimeout(() => {
                location.href = 'kms.php';
            }, 1000);
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        showToast('저장 중 오류가 발생했습니다.', 'error');
    }
});

// 삭제
async function deleteDoc() {
    if (!confirm('정말 삭제하시겠습니까?')) return;

    const id = document.querySelector('input[name="id"]').value;

    try {
        const response = await apiPost(CRM_URL + '/api/common/kms.php', {
            action: 'delete',
            id: id
        });

        showToast('삭제되었습니다.', 'success');
        setTimeout(() => {
            location.href = 'kms.php';
        }, 1000);
    } catch (error) {
        showToast('삭제 중 오류가 발생했습니다.', 'error');
    }
}
</script>
SCRIPT;

include dirname(dirname(__DIR__)) . '/includes/footer.php';
?>
