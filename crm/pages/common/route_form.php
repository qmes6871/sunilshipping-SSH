<?php
/**
 * 루트 주의사항 작성
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pageTitle = '루트 주의사항 작성';
$pageSubtitle = '새로운 루트별 주의사항을 작성합니다';

$pdo = getDB();

$id = $_GET['id'] ?? null;
$warning = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM " . CRM_ROUTES_TABLE . " WHERE id = ?");
    $stmt->execute([$id]);
    $warning = $stmt->fetch();

    if ($warning) {
        $pageTitle = '주의사항 수정';
        $pageSubtitle = '루트별 주의사항 수정';
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
.route-card {
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
    min-height:200px;
    resize:vertical;
}
.form-file {
    padding:10px 14px;
    border:1px solid #ced4da;
    border-radius:6px;
    background:#fff;
    cursor:pointer;
    width:100%;
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

/* 버튼 */
.form-actions {
    display:flex;
    gap:12px;
    justify-content:flex-end;
    margin-top:32px;
    padding-top:24px;
    border-top:1px solid #e9ecef;
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
        <div class="page-title"><?= $warning ? '주의사항 수정' : '루트 주의사항 작성' ?></div>
        <div class="page-subtitle"><?= $warning ? '루트별 주의사항 수정' : '새로운 루트별 주의사항을 작성합니다' ?></div>
    </div>

    <div class="route-card">
        <form id="routeForm" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $warning['id'] ?? '' ?>">

            <!-- 제목 -->
            <div class="form-group">
                <label class="form-label">제목<span class="required">*</span></label>
                <input type="text" name="title" class="form-input" placeholder="주의사항 제목을 입력하세요" value="<?= h($warning['title'] ?? '') ?>" required>
            </div>

            <!-- 상태 & 루트 -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">상태<span class="required">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="">선택하세요</option>
                        <option value="urgent" <?= ($warning['status'] ?? '') === 'urgent' ? 'selected' : '' ?>>긴급</option>
                        <option value="important" <?= ($warning['status'] ?? '') === 'important' ? 'selected' : '' ?>>중요</option>
                        <option value="normal" <?= ($warning['status'] ?? 'normal') === 'normal' ? 'selected' : '' ?>>안내</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">루트<span class="required">*</span></label>
                    <select name="route_name" class="form-select" required>
                        <option value="">선택하세요</option>
                        <option value="중앙아시아" <?= ($warning['route_name'] ?? '') === '중앙아시아' ? 'selected' : '' ?>>중앙아시아</option>
                        <option value="중동아프리카" <?= ($warning['route_name'] ?? '') === '중동아프리카' ? 'selected' : '' ?>>중동·아프리카</option>
                        <option value="러시아" <?= ($warning['route_name'] ?? '') === '러시아' ? 'selected' : '' ?>>러시아</option>
                        <option value="유럽" <?= ($warning['route_name'] ?? '') === '유럽' ? 'selected' : '' ?>>유럽</option>
                        <option value="동남아시아" <?= ($warning['route_name'] ?? '') === '동남아시아' ? 'selected' : '' ?>>동남아시아</option>
                        <option value="국내" <?= ($warning['route_name'] ?? '') === '국내' ? 'selected' : '' ?>>국내 물류</option>
                    </select>
                </div>
            </div>

            <!-- 구간/지역 -->
            <div class="form-group">
                <label class="form-label">구간 · 지역<span class="required">*</span></label>
                <input type="text" name="section" class="form-input" placeholder="예: 타슈켄트, 알마티 철도" value="<?= h($warning['section'] ?? '') ?>" required>
                <div class="form-help">구체적인 운송 구간이나 지역을 입력하세요</div>
            </div>

            <!-- 내용 -->
            <div class="form-group">
                <label class="form-label">내용<span class="required">*</span></label>
                <textarea name="content" class="form-textarea" placeholder="주의사항 내용을 자세히 작성하세요" required><?= h($warning['content'] ?? '') ?></textarea>
            </div>

            <!-- 첨부파일 -->
            <div class="form-group">
                <label class="form-label">첨부파일</label>
                <?php if (!empty($warning['attachment_path'])):
                    $ext = strtolower(pathinfo($warning['attachment_path'], PATHINFO_EXTENSION));
                    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']);
                ?>
                    <div style="margin-bottom: 12px; padding: 12px; background: #f8f9fa; border-radius: 8px;">
                        <?php if ($isImage): ?>
                            <div style="margin-bottom: 8px;">
                                <img src="<?= CRM_UPLOAD_URL ?>/<?= h($warning['attachment_path']) ?>" style="max-width: 100%; max-height: 200px; border-radius: 6px; cursor: pointer;" onclick="window.open('<?= CRM_UPLOAD_URL ?>/<?= h($warning['attachment_path']) ?>', '_blank')" />
                            </div>
                        <?php endif; ?>
                        <p style="color: #666; font-size: 13px; display: flex; align-items: center; gap: 8px;">
                            <span>현재 파일:</span>
                            <a href="<?= CRM_UPLOAD_URL ?>/<?= h($warning['attachment_path']) ?>" target="_blank" style="color: #4a90e2;"><?= basename($warning['attachment_path']) ?></a>
                        </p>
                    </div>
                <?php endif; ?>
                <input type="file" name="attachment" class="form-file" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx">
                <div class="form-help">PDF, DOC, XLS, 이미지 파일 등을 첨부할 수 있습니다 (최대 10MB)</div>
            </div>

            <!-- 버튼 -->
            <div class="form-actions">
                <a href="routes.php" class="btn btn-secondary">취소</a>
                <?php if ($warning): ?>
                    <button type="button" class="btn btn-danger" onclick="deleteWarning()">삭제</button>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary"><?= $warning ? '수정' : '작성 완료' ?></button>
            </div>
        </form>
    </div>
</div>

<?php
$pageScripts = <<<SCRIPT
<script>
// 폼 제출
document.getElementById('routeForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const id = formData.get('id');

    try {
        const response = await fetch(CRM_URL + '/api/common/routes.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showToast(result.message, 'success');
            setTimeout(() => {
                location.href = 'routes.php';
            }, 1000);
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        showToast('저장 중 오류가 발생했습니다.', 'error');
    }
});

// 삭제
async function deleteWarning() {
    if (!confirm('정말 삭제하시겠습니까?')) return;

    const id = document.querySelector('input[name="id"]').value;

    try {
        const response = await apiPost(CRM_URL + '/api/common/routes.php', {
            action: 'delete',
            id: id
        });

        showToast('삭제되었습니다.', 'success');
        setTimeout(() => {
            location.href = 'routes.php';
        }, 1000);
    } catch (error) {
        showToast('삭제 중 오류가 발생했습니다.', 'error');
    }
}
</script>
SCRIPT;

include dirname(dirname(__DIR__)) . '/includes/footer.php';
?>
