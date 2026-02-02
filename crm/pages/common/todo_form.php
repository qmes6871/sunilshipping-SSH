<?php
/**
 * 할일 등록/수정
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pageTitle = '할 일 등록';
$pageSubtitle = '새로운 할 일을 등록합니다';

$pdo = getDB();

$id = $_GET['id'] ?? null;
$todo = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM " . CRM_TODOS_TABLE . " WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $currentUser['crm_user_id']]);
    $todo = $stmt->fetch();

    if ($todo) {
        $pageTitle = '할 일 수정';
        $pageSubtitle = '할 일 수정';
    }
}

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

.container { max-width: 800px; margin: 0 auto; padding: 20px; }

/* 페이지 헤더 */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}
.header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}
.page-title {
    font-size: 28px;
    font-weight: 700;
    color: #212529;
    margin-bottom: 4px;
}
.page-subtitle {
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
.btn-back:hover {
    background: #f8f9fa;
}

/* 카드 */
.todo-card {
    background: white;
    padding: 24px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.card-header-row {
    margin-bottom: 20px;
}
.card-title {
    font-size: 18px;
    font-weight: 600;
    color: #212529;
}

/* 폼 요소 */
.form-group {
    margin-bottom: 20px;
}
.form-label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    color: #212529;
    margin-bottom: 8px;
}
.form-label.required::after {
    content: " *";
    color: #dc3545;
}
.form-input {
    width: 100%;
    padding: 10px 16px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    font-size: 14px;
}
.form-input:focus {
    outline: none;
    border-color: #0d6efd;
}
.form-textarea {
    width: 100%;
    min-height: 120px;
    padding: 12px 16px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    font-size: 14px;
    resize: vertical;
    font-family: inherit;
}
.form-textarea:focus {
    outline: none;
    border-color: #0d6efd;
}
.form-select {
    width: 100%;
    padding: 10px 16px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    font-size: 14px;
    background: white;
}
.help-text {
    font-size: 12px;
    color: #6c757d;
    margin-top: 4px;
}

/* 우선순위 선택 */
.priority-options {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}
.priority-option {
    padding: 12px;
    border: 2px solid #dee2e6;
    border-radius: 4px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    background: white;
}
.priority-option:hover {
    border-color: #0d6efd;
}
.priority-option.selected {
    border-color: #0d6efd;
    background: #e7f1ff;
}
.priority-option.high {
    border-color: #dc3545;
    background: #fff5f5;
}
.priority-option.high.selected {
    background: #ffe0e0;
}
.priority-option.medium {
    border-color: #f0ad4e;
    background: #fff9e6;
}
.priority-option.medium.selected {
    background: #fff3cd;
}
.priority-option.low {
    border-color: #20c997;
    background: #f0fdf7;
}
.priority-option.low.selected {
    background: #d1f4e8;
}
.priority-option input { display: none; }
.priority-icon {
    font-size: 20px;
    margin-bottom: 4px;
}
.priority-text {
    font-size: 13px;
    font-weight: 500;
}

/* 버튼 그룹 */
.btn-group {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 24px;
}

/* 반응형 */
@media (max-width: 768px) {
    .priority-options {
        grid-template-columns: 1fr;
    }
    .btn-group {
        flex-direction: column-reverse;
    }
    .btn-group .btn {
        width: 100%;
    }
}
</style>

<div class="container">
    <!-- 페이지 헤더 -->
    <div class="page-header">
        <div class="header-left">
            <a href="todos.php" class="btn-back">← 뒤로가기</a>
            <div>
                <div class="page-title"><?= $todo ? '할 일 수정' : '할 일 등록' ?></div>
                <div class="page-subtitle"><?= $todo ? '할 일 수정' : '새로운 할 일을 등록합니다' ?></div>
            </div>
        </div>
    </div>

    <!-- 등록 폼 -->
    <div class="todo-card">
        <div class="card-header-row" style="border: none; padding-bottom: 0; margin-bottom: 20px;">
            <div class="card-title">할 일 정보</div>
        </div>

        <form id="todoForm">
            <input type="hidden" name="id" value="<?= $todo['id'] ?? '' ?>">

            <!-- 할 일 제목 -->
            <div class="form-group">
                <label class="form-label required">할 일 제목</label>
                <input type="text" name="title" class="form-input" placeholder="할 일 제목을 입력하세요" value="<?= h($todo['title'] ?? '') ?>" required>
                <div class="help-text">예: Q4 실적 리포트 작성, 고객사 미팅 준비 등</div>
            </div>

            <!-- 마감일 -->
            <div class="form-group">
                <label class="form-label required">마감일</label>
                <input type="date" name="deadline" class="form-input" value="<?= $todo['deadline'] ?? '' ?>" required>
            </div>

            <!-- 우선순위 -->
            <div class="form-group">
                <label class="form-label">우선순위</label>
                <div class="priority-options">
                    <label class="priority-option high <?= ($todo['priority'] ?? '') === 'high' ? 'selected' : '' ?>">
                        <input type="radio" name="priority" value="high" <?= ($todo['priority'] ?? '') === 'high' ? 'checked' : '' ?>>
                        <div class="priority-icon">🔴</div>
                        <div class="priority-text">높음</div>
                    </label>
                    <label class="priority-option medium <?= ($todo['priority'] ?? 'medium') === 'medium' ? 'selected' : '' ?>">
                        <input type="radio" name="priority" value="medium" <?= ($todo['priority'] ?? 'medium') === 'medium' ? 'checked' : '' ?>>
                        <div class="priority-icon">🟡</div>
                        <div class="priority-text">보통</div>
                    </label>
                    <label class="priority-option low <?= ($todo['priority'] ?? '') === 'low' ? 'selected' : '' ?>">
                        <input type="radio" name="priority" value="low" <?= ($todo['priority'] ?? '') === 'low' ? 'checked' : '' ?>>
                        <div class="priority-icon">🟢</div>
                        <div class="priority-text">낮음</div>
                    </label>
                </div>
            </div>

            <!-- 카테고리 -->
            <div class="form-group">
                <label class="form-label">카테고리</label>
                <select name="category" class="form-select">
                    <option value="업무" <?= ($todo['category'] ?? '') === '업무' ? 'selected' : '' ?>>업무</option>
                    <option value="회의" <?= ($todo['category'] ?? '') === '회의' ? 'selected' : '' ?>>회의</option>
                    <option value="보고서" <?= ($todo['category'] ?? '') === '보고서' ? 'selected' : '' ?>>보고서</option>
                    <option value="미팅" <?= ($todo['category'] ?? '') === '미팅' ? 'selected' : '' ?>>미팅</option>
                    <option value="기타" <?= ($todo['category'] ?? '') === '기타' ? 'selected' : '' ?>>기타</option>
                </select>
            </div>

            <!-- 상세 설명 -->
            <div class="form-group">
                <label class="form-label">상세 설명</label>
                <textarea name="description" class="form-textarea" placeholder="할 일에 대한 상세 설명을 입력하세요 (선택사항)"><?= h($todo['description'] ?? '') ?></textarea>
            </div>

            <!-- 담당자 -->
            <div class="form-group">
                <label class="form-label">담당자</label>
                <input type="text" class="form-input" value="<?= h($currentUser['mb_name'] ?? '사용자') ?>" readonly style="background: #f8f9fa;">
            </div>

            <!-- 버튼 그룹 -->
            <div class="btn-group">
                <a href="todos.php" class="btn btn-secondary">취소</a>
                <?php if ($todo): ?>
                    <button type="button" class="btn btn-danger" onclick="deleteTodo()">삭제</button>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary"><?= $todo ? '수정' : '등록하기' ?></button>
            </div>
        </form>
    </div>
</div>

<?php
$pageScripts = <<<SCRIPT
<script>
// 우선순위 선택
document.querySelectorAll('.priority-option').forEach(option => {
    option.addEventListener('click', function() {
        document.querySelectorAll('.priority-option').forEach(o => o.classList.remove('selected'));
        this.classList.add('selected');
        this.querySelector('input').checked = true;
    });
});

// 폼 제출
document.getElementById('todoForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const id = formData.get('id');

    const data = {
        action: id ? 'update' : 'create',
        id: id || undefined,
        title: formData.get('title'),
        description: formData.get('description'),
        deadline: formData.get('deadline') || null,
        category: formData.get('category'),
        priority: formData.get('priority')
    };

    try {
        const response = await apiPost(CRM_URL + '/api/common/todos.php', data);
        showToast(response.message, 'success');
        setTimeout(() => location.href = 'todos.php', 1000);
    } catch (error) {
        showToast(error.message || '저장에 실패했습니다.', 'error');
    }
});

async function deleteTodo() {
    if (!confirm('정말 삭제하시겠습니까?')) return;

    const id = document.querySelector('input[name="id"]').value;

    try {
        await apiPost(CRM_URL + '/api/common/todos.php', { action: 'delete', id: id });
        showToast('삭제되었습니다.', 'success');
        setTimeout(() => location.href = 'todos.php', 1000);
    } catch (error) {
        showToast('삭제에 실패했습니다.', 'error');
    }
}
</script>
SCRIPT;

include dirname(dirname(__DIR__)) . '/includes/footer.php';
?>
