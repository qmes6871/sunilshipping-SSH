<?php
/**
 * CRM 할일 관리
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pageTitle = '할일 관리';
$pageSubtitle = '나의 할일 목록을 관리합니다';

$pdo = getDB();

// 필터 파라미터
$filter = $_GET['filter'] ?? 'all'; // all, pending, completed
$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

// 쿼리 빌드
$where = ["user_id = ?"];
$params = [$currentUser['crm_user_id']];

if ($filter === 'pending') {
    $where[] = "is_completed = 0";
} elseif ($filter === 'completed') {
    $where[] = "is_completed = 1";
}

if ($category) {
    $where[] = "category = ?";
    $params[] = $category;
}

if ($search) {
    $where[] = "(title LIKE ? OR description LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereClause = implode(' AND ', $where);

// 할일 목록 조회
try {
    $stmt = $pdo->prepare("SELECT * FROM " . CRM_TODOS_TABLE . " WHERE {$whereClause} ORDER BY is_completed ASC, CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 END, deadline ASC");
    $stmt->execute($params);
    $todos = $stmt->fetchAll();
} catch (Exception $e) {
    $todos = [];
}

// 카테고리 목록
try {
    $stmt = $pdo->prepare("SELECT DISTINCT category FROM " . CRM_TODOS_TABLE . " WHERE user_id = ? AND category IS NOT NULL AND category != '' ORDER BY category");
    $stmt->execute([$currentUser['crm_user_id']]);
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $categories = [];
}

// 통계
try {
    $stmt = $pdo->prepare("SELECT
        COUNT(*) as total,
        SUM(CASE WHEN is_completed = 0 THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN is_completed = 0 AND deadline < CURDATE() THEN 1 ELSE 0 END) as overdue
        FROM " . CRM_TODOS_TABLE . " WHERE user_id = ?");
    $stmt->execute([$currentUser['crm_user_id']]);
    $stats = $stmt->fetch();
} catch (Exception $e) {
    $stats = ['total' => 0, 'pending' => 0, 'completed' => 0, 'overdue' => 0];
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
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
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
        color: var(--primary);
    }

    .stat-value.pending { color: #f59e0b; }
    .stat-value.completed { color: #10b981; }
    .stat-value.overdue { color: #ef4444; }

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
        transition: all 0.2s;
    }

    .filter-tab:hover {
        background: #e0e0e0;
    }

    .filter-tab.active {
        background: var(--primary);
        color: white;
    }

    .search-box {
        flex: 1;
        min-width: 200px;
        display: flex;
        gap: 8px;
    }

    .search-box input {
        flex: 1;
    }

    .todo-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .todo-item {
        background: white;
        border-radius: 12px;
        padding: 16px 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        display: flex;
        align-items: center;
        gap: 16px;
        transition: all 0.2s;
    }

    .todo-item:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }

    .todo-item.completed {
        opacity: 0.6;
        background: #f9f9f9;
    }

    .todo-item.completed .todo-title {
        text-decoration: line-through;
        color: #999;
    }

    .todo-checkbox {
        width: 24px;
        height: 24px;
        border: 2px solid #ddd;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: all 0.2s;
    }

    .todo-checkbox:hover {
        border-color: var(--primary);
    }

    .todo-checkbox.checked {
        background: #10b981;
        border-color: #10b981;
        color: white;
    }

    .todo-content {
        flex: 1;
        min-width: 0;
    }

    .todo-title {
        font-size: 16px;
        font-weight: 500;
        color: var(--text-dark);
        margin-bottom: 4px;
    }

    .todo-meta {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        font-size: 13px;
        color: #666;
    }

    .todo-meta span {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .priority-badge {
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
    }

    .priority-high {
        background: #fee2e2;
        color: #dc2626;
    }

    .priority-medium {
        background: #fef3c7;
        color: #d97706;
    }

    .priority-low {
        background: #dbeafe;
        color: #2563eb;
    }

    .deadline-overdue {
        color: #ef4444 !important;
        font-weight: 600;
    }

    .todo-actions {
        display: flex;
        gap: 8px;
    }

    .todo-actions button {
        padding: 6px 12px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
        transition: all 0.2s;
    }

    .btn-edit {
        background: #f5f5f5;
        color: #666;
    }

    .btn-edit:hover {
        background: #e0e0e0;
    }

    .btn-delete {
        background: #fee2e2;
        color: #dc2626;
    }

    .btn-delete:hover {
        background: #fecaca;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }

    .empty-state .icon {
        font-size: 48px;
        margin-bottom: 16px;
    }
</style>

<!-- 통계 카드 -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value"><?= $stats['total'] ?></div>
        <div class="stat-label">전체</div>
    </div>
    <div class="stat-card">
        <div class="stat-value pending"><?= $stats['pending'] ?></div>
        <div class="stat-label">진행중</div>
    </div>
    <div class="stat-card">
        <div class="stat-value completed"><?= $stats['completed'] ?></div>
        <div class="stat-label">완료</div>
    </div>
    <div class="stat-card">
        <div class="stat-value overdue"><?= $stats['overdue'] ?></div>
        <div class="stat-label">기한초과</div>
    </div>
</div>

<!-- 필터 & 검색 -->
<div class="card" style="padding: 16px; margin-bottom: 24px;">
    <div class="filter-bar">
        <div class="filter-tabs">
            <a href="?filter=all" class="filter-tab <?= $filter === 'all' ? 'active' : '' ?>">전체</a>
            <a href="?filter=pending" class="filter-tab <?= $filter === 'pending' ? 'active' : '' ?>">진행중</a>
            <a href="?filter=completed" class="filter-tab <?= $filter === 'completed' ? 'active' : '' ?>">완료</a>
        </div>

        <form class="search-box" method="GET">
            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
            <select name="category" class="form-control" style="width: auto;" onchange="this.form.submit()">
                <option value="">전체 카테고리</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="search" class="form-control" placeholder="검색어 입력" value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-secondary">검색</button>
        </form>

        <button class="btn btn-primary" onclick="openModal('todoModal')">+ 할일 추가</button>
    </div>
</div>

<!-- 할일 목록 -->
<div class="todo-list">
    <?php if (empty($todos)): ?>
        <div class="card empty-state">
            <div class="icon">📝</div>
            <p>등록된 할일이 없습니다.</p>
        </div>
    <?php else: ?>
        <?php foreach ($todos as $todo): ?>
            <?php
            $isCompleted = $todo['is_completed'];
            $isOverdue = !$isCompleted && $todo['deadline'] && strtotime($todo['deadline']) < strtotime('today');
            ?>
            <div class="todo-item <?= $isCompleted ? 'completed' : '' ?>" data-id="<?= $todo['id'] ?>">
                <div class="todo-checkbox <?= $isCompleted ? 'checked' : '' ?>" onclick="toggleTodo(<?= $todo['id'] ?>, <?= $isCompleted ? 0 : 1 ?>)">
                    <?= $isCompleted ? '✓' : '' ?>
                </div>

                <div class="todo-content">
                    <div class="todo-title"><?= htmlspecialchars($todo['title']) ?></div>
                    <div class="todo-meta">
                        <?php if ($todo['priority']): ?>
                            <span class="priority-badge priority-<?= $todo['priority'] ?>">
                                <?= $todo['priority'] === 'high' ? '높음' : ($todo['priority'] === 'medium' ? '보통' : '낮음') ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($todo['category']): ?>
                            <span>📁 <?= htmlspecialchars($todo['category']) ?></span>
                        <?php endif; ?>
                        <?php if ($todo['deadline']): ?>
                            <span class="<?= $isOverdue ? 'deadline-overdue' : '' ?>">
                                📅 <?= formatDate($todo['deadline'], 'Y-m-d') ?>
                                <?= $isOverdue ? '(기한초과)' : '' ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="todo-actions">
                    <button class="btn-edit" onclick="editTodo(<?= $todo['id'] ?>)">수정</button>
                    <button class="btn-delete" onclick="deleteTodo(<?= $todo['id'] ?>)">삭제</button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- 할일 추가/수정 모달 -->
<div class="modal-overlay" id="todoModal">
    <div class="modal" style="max-width: 600px;">
        <div class="modal-header" style="background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%); color: white; padding: 20px 24px; border-radius: 12px 12px 0 0;">
            <h3 id="modalTitle" style="margin: 0; font-size: 20px; font-weight: 600;">할 일 등록</h3>
            <button class="modal-close" onclick="closeModal('todoModal')" style="background: rgba(255,255,255,0.2); border: none; color: white; font-size: 24px; width: 32px; height: 32px; border-radius: 50%; cursor: pointer;">&times;</button>
        </div>
        <div class="modal-body" style="padding: 24px;">
            <form id="todoForm">
                <input type="hidden" name="id" id="todoId">

                <!-- 할 일 제목 -->
                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label" style="display: block; font-size: 14px; font-weight: 500; color: #212529; margin-bottom: 8px;">할 일 제목 <span style="color: #dc3545;">*</span></label>
                    <input type="text" class="form-control" name="title" id="todoTitle" placeholder="할 일 제목을 입력하세요" required style="width: 100%; padding: 10px 16px; border: 1px solid #dee2e6; border-radius: 4px; font-size: 14px;">
                    <div style="font-size: 12px; color: #6c757d; margin-top: 4px;">예: Q4 실적 리포트 작성, 고객사 미팅 준비 등</div>
                </div>

                <!-- 마감일 -->
                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label" style="display: block; font-size: 14px; font-weight: 500; color: #212529; margin-bottom: 8px;">마감일 <span style="color: #dc3545;">*</span></label>
                    <input type="date" class="form-control" name="deadline" id="todoDeadline" required style="width: 100%; padding: 10px 16px; border: 1px solid #dee2e6; border-radius: 4px; font-size: 14px;">
                </div>

                <!-- 우선순위 -->
                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label" style="display: block; font-size: 14px; font-weight: 500; color: #212529; margin-bottom: 8px;">우선순위</label>
                    <div id="priorityOptions" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                        <label class="priority-option" data-priority="high" style="padding: 12px; border: 2px solid #dc3545; background: #fff5f5; border-radius: 4px; text-align: center; cursor: pointer;">
                            <input type="radio" name="priority" value="high" style="display: none;">
                            <div style="font-size: 20px; margin-bottom: 4px;">🔴</div>
                            <div style="font-size: 13px; font-weight: 500;">높음</div>
                        </label>
                        <label class="priority-option selected" data-priority="medium" style="padding: 12px; border: 2px solid #0d6efd; background: #e7f1ff; border-radius: 4px; text-align: center; cursor: pointer;">
                            <input type="radio" name="priority" value="medium" checked style="display: none;">
                            <div style="font-size: 20px; margin-bottom: 4px;">🟡</div>
                            <div style="font-size: 13px; font-weight: 500;">보통</div>
                        </label>
                        <label class="priority-option" data-priority="low" style="padding: 12px; border: 2px solid #20c997; background: #f0fdf7; border-radius: 4px; text-align: center; cursor: pointer;">
                            <input type="radio" name="priority" value="low" style="display: none;">
                            <div style="font-size: 20px; margin-bottom: 4px;">🟢</div>
                            <div style="font-size: 13px; font-weight: 500;">낮음</div>
                        </label>
                    </div>
                </div>

                <!-- 카테고리 -->
                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label" style="display: block; font-size: 14px; font-weight: 500; color: #212529; margin-bottom: 8px;">카테고리</label>
                    <select class="form-control" name="category" id="todoCategory" style="width: 100%; padding: 10px 16px; border: 1px solid #dee2e6; border-radius: 4px; font-size: 14px; background: white;">
                        <option value="업무">업무</option>
                        <option value="회의">회의</option>
                        <option value="보고서">보고서</option>
                        <option value="미팅">미팅</option>
                        <option value="기타">기타</option>
                    </select>
                </div>

                <!-- 상세 설명 -->
                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label" style="display: block; font-size: 14px; font-weight: 500; color: #212529; margin-bottom: 8px;">상세 설명</label>
                    <textarea class="form-control" name="description" id="todoDescription" rows="4" placeholder="할 일에 대한 상세 설명을 입력하세요 (선택사항)" style="width: 100%; min-height: 100px; padding: 12px 16px; border: 1px solid #dee2e6; border-radius: 4px; font-size: 14px; resize: vertical; font-family: inherit;"></textarea>
                </div>

                <!-- 담당자 (읽기 전용) -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" style="display: block; font-size: 14px; font-weight: 500; color: #212529; margin-bottom: 8px;">담당자</label>
                    <input type="text" class="form-control" value="<?= h($currentUser['mb_name'] ?? '사용자') ?>" readonly style="width: 100%; padding: 10px 16px; border: 1px solid #dee2e6; border-radius: 4px; font-size: 14px; background: #f8f9fa;">
                </div>
            </form>
        </div>
        <div class="modal-footer" style="padding: 16px 24px; border-top: 1px solid #e9ecef; display: flex; gap: 12px; justify-content: flex-end;">
            <button class="btn btn-secondary" onclick="closeModal('todoModal')">취소</button>
            <button class="btn btn-primary" onclick="saveTodo()">등록하기</button>
        </div>
    </div>
</div>

<?php
$pageScripts = <<<SCRIPT
<script>
    // 우선순위 옵션 선택
    document.querySelectorAll('.priority-option').forEach(option => {
        option.addEventListener('click', function() {
            document.querySelectorAll('.priority-option').forEach(opt => {
                opt.classList.remove('selected');
                opt.style.borderColor = '';
                opt.style.background = '';
                const priority = opt.dataset.priority;
                if (priority === 'high') {
                    opt.style.borderColor = '#dc3545';
                    opt.style.background = '#fff5f5';
                } else if (priority === 'medium') {
                    opt.style.borderColor = '#f0ad4e';
                    opt.style.background = '#fff9e6';
                } else if (priority === 'low') {
                    opt.style.borderColor = '#20c997';
                    opt.style.background = '#f0fdf7';
                }
            });
            this.classList.add('selected');
            this.style.borderColor = '#0d6efd';
            this.style.background = '#e7f1ff';
            this.querySelector('input').checked = true;
        });
    });

    // 할일 완료 토글
    async function toggleTodo(id, completed) {
        try {
            await apiPost(CRM_URL + '/api/common/todos.php', {
                action: 'toggle',
                id: id,
                is_completed: completed
            });

            // UI 업데이트
            const item = document.querySelector('.todo-item[data-id="' + id + '"]');
            const checkbox = item.querySelector('.todo-checkbox');

            if (completed) {
                item.classList.add('completed');
                checkbox.classList.add('checked');
                checkbox.textContent = '✓';
                checkbox.setAttribute('onclick', 'toggleTodo(' + id + ', 0)');
            } else {
                item.classList.remove('completed');
                checkbox.classList.remove('checked');
                checkbox.textContent = '';
                checkbox.setAttribute('onclick', 'toggleTodo(' + id + ', 1)');
            }

            showToast(completed ? '완료 처리되었습니다.' : '진행중으로 변경되었습니다.', 'success');
        } catch (error) {
            showToast('처리 중 오류가 발생했습니다.', 'error');
        }
    }

    // 할일 수정 모달 열기
    async function editTodo(id) {
        try {
            const response = await apiGet(CRM_URL + '/api/common/todos.php?id=' + id);
            const todo = response.data;

            document.getElementById('modalTitle').textContent = '할 일 수정';
            document.getElementById('todoId').value = todo.id;
            document.getElementById('todoTitle').value = todo.title || '';
            document.getElementById('todoDescription').value = todo.description || '';
            document.getElementById('todoDeadline').value = todo.deadline || '';
            document.getElementById('todoCategory').value = todo.category || '업무';

            // 우선순위 선택 업데이트
            const priority = todo.priority || 'medium';
            document.querySelectorAll('.priority-option').forEach(opt => {
                opt.classList.remove('selected');
                const p = opt.dataset.priority;
                opt.querySelector('input').checked = (p === priority);
                if (p === priority) {
                    opt.classList.add('selected');
                    opt.style.borderColor = '#0d6efd';
                    opt.style.background = '#e7f1ff';
                } else {
                    if (p === 'high') {
                        opt.style.borderColor = '#dc3545';
                        opt.style.background = '#fff5f5';
                    } else if (p === 'medium') {
                        opt.style.borderColor = '#f0ad4e';
                        opt.style.background = '#fff9e6';
                    } else if (p === 'low') {
                        opt.style.borderColor = '#20c997';
                        opt.style.background = '#f0fdf7';
                    }
                }
            });

            // 버튼 텍스트 변경
            document.querySelector('#todoModal .modal-footer .btn-primary').textContent = '수정하기';

            openModal('todoModal');
        } catch (error) {
            showToast('데이터를 불러올 수 없습니다.', 'error');
        }
    }

    // 할일 저장
    async function saveTodo() {
        const form = document.getElementById('todoForm');
        const formData = new FormData(form);
        const data = {
            action: formData.get('id') ? 'update' : 'create',
            id: formData.get('id') || null,
            title: formData.get('title'),
            description: formData.get('description'),
            priority: formData.get('priority'),
            deadline: formData.get('deadline'),
            category: formData.get('category')
        };

        if (!data.title.trim()) {
            showToast('제목을 입력해주세요.', 'error');
            return;
        }

        try {
            await apiPost(CRM_URL + '/api/common/todos.php', data);
            showToast('저장되었습니다.', 'success');
            closeModal('todoModal');
            location.reload();
        } catch (error) {
            showToast('저장 중 오류가 발생했습니다.', 'error');
        }
    }

    // 할일 삭제
    async function deleteTodo(id) {
        if (!confirm('이 할일을 삭제하시겠습니까?')) return;

        try {
            await apiPost(CRM_URL + '/api/common/todos.php', {
                action: 'delete',
                id: id
            });

            document.querySelector('.todo-item[data-id="' + id + '"]').remove();
            showToast('삭제되었습니다.', 'success');
        } catch (error) {
            showToast('삭제 중 오류가 발생했습니다.', 'error');
        }
    }

    // 새 할일 모달 초기화
    document.getElementById('todoModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal('todoModal');
    });

    // 모달 열 때 초기화
    const originalOpenModal = openModal;
    openModal = function(id) {
        if (id === 'todoModal') {
            document.getElementById('modalTitle').textContent = '할 일 등록';
            document.getElementById('todoForm').reset();
            document.getElementById('todoId').value = '';
            document.querySelector('#todoModal .modal-footer .btn-primary').textContent = '등록하기';

            // 우선순위 기본값 설정 (보통)
            document.querySelectorAll('.priority-option').forEach(opt => {
                opt.classList.remove('selected');
                const p = opt.dataset.priority;
                if (p === 'medium') {
                    opt.classList.add('selected');
                    opt.style.borderColor = '#0d6efd';
                    opt.style.background = '#e7f1ff';
                    opt.querySelector('input').checked = true;
                } else if (p === 'high') {
                    opt.style.borderColor = '#dc3545';
                    opt.style.background = '#fff5f5';
                } else if (p === 'low') {
                    opt.style.borderColor = '#20c997';
                    opt.style.background = '#f0fdf7';
                }
            });
        }
        originalOpenModal(id);
    };
</script>
SCRIPT;

include dirname(dirname(__DIR__)) . '/includes/footer.php';
?>
