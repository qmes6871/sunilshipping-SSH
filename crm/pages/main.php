<?php
/**
 * CRM 메인페이지 (마이페이지)
 */

require_once dirname(__DIR__) . '/includes/auth_check.php';

$pageTitle = 'MyPage';
$pageSubtitle = getDepartmentName($currentUser['department'] ?? '');

// 데이터 로드
$pdo = getDB();

// 내 할일 목록 (개인 업무 - source='personal' 또는 미지정)
try {
    $stmt = $pdo->prepare("SELECT * FROM " . CRM_TODOS_TABLE . " WHERE user_id = ? AND (source = 'personal' OR source IS NULL) ORDER BY is_completed ASC, deadline ASC LIMIT 5");
    $stmt->execute([$currentUser['crm_user_id'] ?? 0]);
    $todos = $stmt->fetchAll();
} catch (Exception $e) {
    $todos = [];
}

// 회의록 목록 (최근 5개)
try {
    $stmt = $pdo->prepare("SELECT * FROM " . CRM_MEETINGS_TABLE . " WHERE created_by = ? ORDER BY meeting_date DESC LIMIT 5");
    $stmt->execute([$currentUser['crm_user_id'] ?? 0]);
    $meetings = $stmt->fetchAll();
} catch (Exception $e) {
    $meetings = [];
}

// 전체 공지 (최근 4개)
try {
    $stmt = $pdo->prepare("SELECT * FROM " . CRM_NOTICES_TABLE . " WHERE notice_type IN ('company', 'urgent') ORDER BY created_at DESC LIMIT 4");
    $stmt->execute();
    $companyNotices = $stmt->fetchAll();
} catch (Exception $e) {
    $companyNotices = [];
}

// 부서 공지 (최근 3개)
try {
    $dept = $currentUser['department'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM " . CRM_NOTICES_TABLE . " WHERE notice_type = 'department' AND (department = ? OR department IS NULL) ORDER BY created_at DESC LIMIT 3");
    $stmt->execute([$dept]);
    $deptNotices = $stmt->fetchAll();
} catch (Exception $e) {
    $deptNotices = [];
}

// 주의사항 (최근 4개) - CRM_ROUTES_TABLE에서 조회
try {
    $stmt = $pdo->prepare("SELECT * FROM " . CRM_ROUTES_TABLE . " ORDER BY created_at DESC LIMIT 4");
    $stmt->execute();
    $warnings = $stmt->fetchAll();
} catch (Exception $e) {
    $warnings = [];
}

// KMS 최신 문서 수
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM " . CRM_KMS_TABLE . " WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stmt->execute();
    $kmsNewCount = $stmt->fetch()['cnt'] ?? 0;
} catch (Exception $e) {
    $kmsNewCount = 0;
}

// 개인 파일 (최근 5개)
try {
    $stmt = $pdo->prepare("SELECT * FROM " . CRM_USER_FILES_TABLE . " WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$currentUser['crm_user_id'] ?? 0]);
    $userFiles = $stmt->fetchAll();
} catch (Exception $e) {
    $userFiles = [];
}

// 개인 메모
try {
    $stmt = $pdo->prepare("SELECT content FROM " . CRM_USER_MEMOS_TABLE . " WHERE user_id = ?");
    $stmt->execute([$currentUser['crm_user_id'] ?? 0]);
    $userMemo = $stmt->fetch()['content'] ?? '';
} catch (Exception $e) {
    $userMemo = '';
}

// 회사 설정 로드 (사명, 미션, 우선순위 업무)
$companyMotto = '우리는 고객들의 모든 길을 선일로 통하게 하기 위해 존재한다.';
$companyMission = '글로벌 물류 혁신을 통해 고객의 비즈니스 성장을 가속화합니다';
$priorityTasksText = '고객 만족을 최우선으로 생각하며, 신속하고 정확한 업무 처리를 목표로 합니다.';
try {
    // 설정 테이블 존재 확인 및 생성
    $pdo->exec("CREATE TABLE IF NOT EXISTS " . CRM_SETTINGS_TABLE . " (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $stmt = $pdo->query("SELECT setting_key, setting_value FROM " . CRM_SETTINGS_TABLE . " WHERE setting_key IN ('company_motto', 'company_mission', 'priority_tasks')");
    while ($row = $stmt->fetch()) {
        if ($row['setting_key'] === 'company_motto' && !empty($row['setting_value'])) {
            $companyMotto = $row['setting_value'];
        }
        if ($row['setting_key'] === 'company_mission' && !empty($row['setting_value'])) {
            $companyMission = $row['setting_value'];
        }
        if ($row['setting_key'] === 'priority_tasks' && !empty($row['setting_value'])) {
            $priorityTasksText = $row['setting_value'];
        }
    }
} catch (Exception $e) {
    // 기본값 사용
}

include dirname(__DIR__) . '/includes/header.php';
?>

<style>
    /* 메인페이지 전용 스타일 */
    .header-fixed {
        background: white;
        padding: 24px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        margin-bottom: 24px;
    }

    .top-sections-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }

    .section-box {
        padding-right: 20px;
        border-right: 1px solid #e9ecef;
    }

    .section-box:last-child {
        border-right: none;
        padding-right: 0;
    }

    .section-label {
        font-size: 11px;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .edit-btn {
        background: none;
        border: none;
        cursor: pointer;
        font-size: 12px;
        opacity: 0.5;
        transition: opacity 0.2s;
        padding: 2px 4px;
    }
    .edit-btn:hover {
        opacity: 1;
    }

    .section-text {
        font-size: 15px;
        font-weight: 500;
        color: #212529;
        line-height: 1.5;
    }

    .priority-grid {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .priority-card {
        background: #fff9e6;
        border-left: 3px solid #f0ad4e;
        padding: 10px 12px;
        border-radius: 4px;
        display: flex;
        align-items: start;
        gap: 8px;
    }

    .priority-icon {
        font-size: 16px;
    }

    .priority-label {
        font-size: 10px;
        color: #856404;
        margin-bottom: 2px;
    }

    .priority-task {
        font-size: 13px;
        font-weight: 500;
        color: #212529;
    }

    .main-grid {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 24px;
    }

    /* 프로필 섹션 */
    .profile-section {
        text-align: center;
        margin-bottom: 20px;
    }

    .avatar-large {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: #4a90e2;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        font-weight: bold;
        margin: 0 auto 12px;
    }

    .profile-name {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 4px;
    }

    .profile-info {
        font-size: 14px;
        color: #6c757d;
        margin-bottom: 2px;
    }

    /* 파일 업로드 */
    .file-upload {
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        padding: 32px 16px;
        text-align: center;
        cursor: pointer;
        margin-bottom: 16px;
        transition: all 0.2s;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .file-upload:hover {
        border-color: #4a90e2;
        background: #f8f9ff;
    }

    .file-icon {
        font-size: 32px;
        margin-bottom: 8px;
    }

    .file-text {
        font-size: 13px;
        color: #6c757d;
    }

    .file-list-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 12px;
        background: #f8f9fa;
        border-radius: 6px;
        margin-bottom: 8px;
        font-size: 13px;
    }

    .file-list-item:hover {
        background: #e9ecef;
    }

    /* 체크리스트 */
    .checklist-item {
        display: flex;
        align-items: start;
        gap: 12px;
        padding: 14px;
        background: #f8f9fa;
        border-radius: 6px;
        margin-bottom: 10px;
        cursor: pointer;
        transition: background 0.2s;
    }

    .checklist-item:hover {
        background: #e9ecef;
    }

    .checklist-item input[type="checkbox"] {
        width: 20px;
        height: 20px;
        margin-top: 2px;
        cursor: pointer;
    }

    .checklist-content {
        flex: 1;
    }

    .checklist-title {
        font-size: 15px;
        font-weight: 500;
        margin-bottom: 4px;
        color: #212529;
    }

    .checklist-meta {
        font-size: 12px;
        color: #6c757d;
    }

    .checklist-item.completed .checklist-title {
        text-decoration: line-through;
        opacity: 0.6;
    }

    /* 공지 아이템 */
    .notice-item {
        padding: 12px;
        border-left: 4px solid #4a90e2;
        background: #f8f9fa;
        border-radius: 4px;
        margin-bottom: 8px;
    }

    .notice-item.important {
        border-left-color: #dc3545;
        background: #fff5f5;
    }

    .notice-item.company {
        border-left-color: #6610f2;
        background: #f8f5ff;
    }

    .notice-item.warning {
        border-left-color: #fd7e14;
        background: #fff8f0;
    }

    .notice-title {
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 4px;
    }

    .notice-content {
        font-size: 13px;
        color: #6c757d;
        margin-bottom: 4px;
    }

    .notice-date {
        font-size: 11px;
        color: #adb5bd;
    }

    /* KMS 박스 */
    .kms-box {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px;
        background: #d1ecf1;
        border-left: 4px solid #0dcaf0;
        border-radius: 6px;
    }

    .kms-info {
        font-size: 14px;
        font-weight: 500;
    }

    .kms-date {
        font-size: 12px;
        color: #6c757d;
        margin-top: 4px;
    }

    /* 메모 */
    .memo-area {
        width: 100%;
        min-height: 120px;
        padding: 12px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        font-size: 14px;
        resize: vertical;
    }

    .memo-area:focus {
        outline: none;
        border-color: #4a90e2;
    }

    /* 반응형 */
    @media (max-width: 1024px) {
        .main-grid {
            grid-template-columns: 1fr;
        }

        .top-sections-grid {
            grid-template-columns: 1fr;
        }

        .section-box {
            padding-right: 0;
            padding-bottom: 16px;
            border-right: none;
            border-bottom: 1px solid #e9ecef;
        }

        .section-box:last-child {
            border-bottom: none;
        }
    }
</style>

<!-- 상단 고정 영역 -->
<div class="header-fixed">
    <div class="top-sections-grid">
        <!-- 회사 사명 -->
        <div class="section-box">
            <div class="section-label">
                회사 사명
                <?php if (isAdmin()): ?>
                <button class="edit-btn" onclick="openSettingModal('motto')" title="수정">✏️</button>
                <?php endif; ?>
            </div>
            <div class="section-text" id="companyMotto" style="white-space: pre-line;"><?= h($companyMotto) ?></div>
        </div>

        <!-- 미션 -->
        <div class="section-box">
            <div class="section-label">
                Mission
                <?php if (isAdmin()): ?>
                <button class="edit-btn" onclick="openSettingModal('mission')" title="수정">✏️</button>
                <?php endif; ?>
            </div>
            <div class="section-text" id="companyMission" style="white-space: pre-line;"><?= h($companyMission) ?></div>
        </div>

        <!-- 우선순위 업무 -->
        <div class="section-box">
            <div class="section-label">
                우선순위 업무
                <?php if (isAdmin()): ?>
                <button class="edit-btn" onclick="openSettingModal('priority')" title="수정">✏️</button>
                <?php endif; ?>
            </div>
            <div class="section-text" id="priorityTasks" style="white-space: pre-line;"><?= h($priorityTasksText) ?></div>
        </div>
    </div>
</div>

<!-- 메인 그리드 -->
<div class="main-grid">
    <!-- 왼쪽: 개인 영역 -->
    <div>
        <!-- 개인 정보 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">개인 정보</h3>
            </div>
            <div class="card-body">
                <div class="profile-section">
                    <div class="avatar-large">
                        <?php if (!empty($currentUser['profile_photo'])): ?>
                            <img src="<?= CRM_UPLOAD_URL ?>/<?= h($currentUser['profile_photo']) ?>" alt="프로필" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                        <?php else: ?>
                            <?= mb_substr($currentUser['mb_name'] ?? 'U', 0, 1) ?>
                        <?php endif; ?>
                    </div>
                    <div class="profile-name"><?= h($currentUser['mb_name'] ?? '사용자') ?></div>
                    <div class="profile-info"><?= h(getDepartmentName($currentUser['department'] ?? '')) ?> · <?= h(getPositionName($currentUser['position'] ?? '')) ?></div>
                    <div class="profile-info"><?= h($currentUser['mb_email'] ?? '') ?></div>
                    <div class="profile-info"><?= h($currentUser['phone'] ?? '') ?></div>
                </div>
                <a href="<?= CRM_URL ?>/pages/profile.php" class="btn btn-outline" style="width: 100%;">정보 수정</a>
            </div>
        </div>

        <!-- 개인 파일 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">내 파일</h3>
                <a href="<?= CRM_URL ?>/pages/common/my_files.php" class="btn btn-sm btn-outline">더보기</a>
            </div>
            <div class="card-body">
                <div class="file-upload" onclick="document.getElementById('fileInput').click()">
                    <div class="file-icon">📁</div>
                    <div class="file-text">파일을 드래그하거나 클릭</div>
                </div>
                <input type="file" id="fileInput" style="display:none" onchange="uploadUserFile(this)">

                <?php if (!empty($userFiles)): ?>
                <div style="margin-top: 16px; border-top: 1px solid #e9ecef; padding-top: 16px;">
                    <div style="font-size: 13px; color: #6c757d; margin-bottom: 10px;">최근 파일</div>
                    <?php
                    $fileCount = 0;
                    foreach ($userFiles as $file):
                        if ($fileCount >= 3) break;
                        $fileCount++;
                    ?>
                    <div class="file-list-item">
                        <div style="display: flex; align-items: center; gap: 8px; flex: 1; overflow: hidden;">
                            <span style="font-size: 16px;">📄</span>
                            <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= h($file['original_name'] ?? $file['file_name'] ?? '파일') ?></span>
                        </div>
                        <a href="<?= CRM_UPLOAD_URL ?>/<?= h($file['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline" style="padding: 4px 8px; font-size: 11px;">보기</a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 파일 업로드 성공 모달 -->
    <div class="modal-overlay" id="fileUploadModal">
        <div class="modal" style="max-width: 400px;">
            <div class="modal-header">
                <h3 class="modal-title">파일 업로드</h3>
                <button class="modal-close" onclick="closeModal('fileUploadModal')">&times;</button>
            </div>
            <div class="modal-body" style="text-align: center; padding: 32px 24px;">
                <div style="font-size: 48px; margin-bottom: 16px;">✅</div>
                <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">파일 업로드 성공!</div>
                <div style="color: #6c757d; margin-bottom: 24px;">파일이 정상적으로 업로드되었습니다.</div>
                <a href="<?= CRM_URL ?>/pages/common/my_files.php" class="btn btn-primary" style="width: 100%;">자세히 보러가기</a>
            </div>
        </div>
    </div>

    <!-- 오른쪽: 업무/공지 -->
    <div>
        <!-- 내 할 일 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">내 할 일</h3>
                <div style="display: flex; gap: 8px;">
                    <a href="<?= CRM_URL ?>/pages/common/todos.php" class="btn btn-sm btn-outline">더보기</a>
                    <a href="<?= CRM_URL ?>/pages/common/todo_form.php" class="btn btn-sm btn-primary">등록하기</a>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($todos)): ?>
                    <?php foreach ($todos as $todo): ?>
                    <div class="checklist-item <?= $todo['is_completed'] ? 'completed' : '' ?>" data-id="<?= $todo['id'] ?>">
                        <input type="checkbox" <?= $todo['is_completed'] ? 'checked' : '' ?> onchange="toggleTodo(<?= $todo['id'] ?>, this.checked)">
                        <div class="checklist-content">
                            <div class="checklist-title"><?= h($todo['title']) ?></div>
                            <div class="checklist-meta">
                                <?= $todo['is_completed'] ? '완료: ' . formatDate($todo['completed_at']) : '마감: ' . formatDate($todo['deadline']) ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted" style="padding: 24px;">
                        등록된 할일이 없습니다.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 회의록 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">회의록</h3>
                <div style="display: flex; gap: 8px;">
                    <a href="<?= CRM_URL ?>/pages/common/meetings.php" class="btn btn-sm btn-outline">더보기</a>
                    <a href="<?= CRM_URL ?>/pages/common/meeting_form.php" class="btn btn-sm btn-primary">등록하기</a>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($meetings)): ?>
                    <?php foreach ($meetings as $meeting): ?>
                    <div class="checklist-item" onclick="location.href='<?= CRM_URL ?>/pages/common/meeting_detail.php?id=<?= $meeting['id'] ?>'">
                        <div class="checklist-content">
                            <div class="checklist-title"><?= h($meeting['title']) ?></div>
                            <div class="checklist-meta">작성: <?= formatDate($meeting['meeting_date']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted" style="padding: 24px;">
                        작성된 회의록이 없습니다.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 메모 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">메모</h3>
            </div>
            <div class="card-body">
                <textarea class="memo-area" id="userMemo" placeholder="자유롭게 메모하세요..."><?= h($userMemo) ?></textarea>
                <button class="btn btn-primary mt-3" onclick="saveMemo()">저장</button>
            </div>
        </div>

        <!-- 전체 공지 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">전체 공지</h3>
                <a href="<?= CRM_URL ?>/pages/common/notices.php" class="btn btn-sm btn-outline">더보기</a>
            </div>
            <div class="card-body">
                <?php if (!empty($companyNotices)): ?>
                    <?php foreach ($companyNotices as $notice): ?>
                    <a href="<?= CRM_URL ?>/pages/common/notice_detail.php?id=<?= $notice['id'] ?>" class="notice-item company" style="display: block; text-decoration: none; cursor: pointer;">
                        <div class="notice-title"><?= h($notice['title']) ?></div>
                        <div class="notice-content"><?= h(mb_substr($notice['content'] ?? '', 0, 80)) ?><?= strlen($notice['content'] ?? '') > 80 ? '...' : '' ?></div>
                        <div class="notice-date"><?= formatDate($notice['created_at'] ?? '') ?></div>
                    </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted" style="padding: 16px;">공지사항이 없습니다.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 주의사항 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">주의사항</h3>
                <a href="<?= CRM_URL ?>/pages/common/routes.php" class="btn btn-sm btn-outline">더보기</a>
            </div>
            <div class="card-body">
                <?php if (!empty($warnings)): ?>
                    <?php foreach ($warnings as $warning): ?>
                    <a href="<?= CRM_URL ?>/pages/common/route_detail.php?id=<?= $warning['id'] ?>" class="notice-item warning" style="display: block; text-decoration: none; cursor: pointer;">
                        <div class="notice-title"><?= h($warning['title']) ?></div>
                        <div class="notice-content"><?= h(mb_substr($warning['content'] ?? '', 0, 80)) ?><?= strlen($warning['content'] ?? '') > 80 ? '...' : '' ?></div>
                        <div class="notice-date"><?= formatDate($warning['created_at'] ?? '') ?></div>
                    </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted" style="padding: 16px;">주의사항이 없습니다.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- KMS 지식관리 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">KMS 지식관리</h3>
                <a href="<?= CRM_URL ?>/pages/common/kms.php" class="btn btn-sm btn-outline">더보기</a>
            </div>
            <div class="card-body">
                <a href="<?= CRM_URL ?>/pages/common/kms.php" class="kms-box">
                    <div>
                        <div class="kms-info">📚 KMS 바로가기</div>
                        <div class="kms-date">최근 업데이트: <?= formatDate(date('Y-m-d')) ?></div>
                    </div>
                    <?php if ($kmsNewCount > 0): ?>
                    <span class="badge badge-info">NEW <?= $kmsNewCount ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// 할일 토글
async function toggleTodo(id, completed) {
    try {
        const response = await apiPost('<?= CRM_URL ?>/api/common/todos.php', {
            action: 'toggle',
            id: id,
            is_completed: completed ? 1 : 0
        });
        if (response.success) {
            showToast(completed ? '할일을 완료했습니다.' : '할일을 미완료로 변경했습니다.', 'success');
        }
    } catch (error) {
        showToast('오류가 발생했습니다.', 'error');
    }
}

// 메모 저장
async function saveMemo() {
    const content = document.getElementById('userMemo').value;
    try {
        const response = await apiPost('<?= CRM_URL ?>/api/users/memo.php', {
            content: content
        });
        if (response.success) {
            showToast('메모가 저장되었습니다.', 'success');
        }
    } catch (error) {
        showToast('메모 저장에 실패했습니다.', 'error');
    }
}

// 파일 업로드
async function uploadUserFile(input) {
    if (!input.files.length) return;

    const formData = new FormData();
    formData.append('file', input.files[0]);

    try {
        const response = await apiPostForm('<?= CRM_URL ?>/api/users/files.php', formData);
        if (response.success) {
            openModal('fileUploadModal');
        } else {
            showToast(response.message || '파일 업로드에 실패했습니다.', 'error');
        }
    } catch (error) {
        console.error('Upload error:', error);
        showToast('파일 업로드에 실패했습니다.', 'error');
    }

    // 입력 초기화
    input.value = '';
}

// 설정 수정 모달
let currentSettingType = null;

function openSettingModal(type) {
    currentSettingType = type;
    const modal = document.getElementById('settingModal');
    const title = document.getElementById('settingModalTitle');
    const input = document.getElementById('settingInput');

    if (type === 'motto') {
        title.textContent = '회사 사명 수정';
        input.value = document.getElementById('companyMotto').textContent;
    } else if (type === 'mission') {
        title.textContent = 'Mission 수정';
        input.value = document.getElementById('companyMission').textContent;
    } else if (type === 'priority') {
        title.textContent = '우선순위 업무 수정';
        input.value = document.getElementById('priorityTasks').textContent;
    }

    openModal('settingModal');
}

async function saveSetting() {
    const value = document.getElementById('settingInput').value.trim();
    if (!value) {
        showToast('내용을 입력해주세요.', 'error');
        return;
    }

    let key;
    if (currentSettingType === 'motto') {
        key = 'company_motto';
    } else if (currentSettingType === 'mission') {
        key = 'company_mission';
    } else if (currentSettingType === 'priority') {
        key = 'priority_tasks';
    }

    try {
        const response = await apiPost('<?= CRM_URL ?>/api/common/settings.php', {
            key: key,
            value: value
        });

        if (response.success) {
            showToast('저장되었습니다.', 'success');
            closeModal('settingModal');

            // UI 업데이트
            if (currentSettingType === 'motto') {
                document.getElementById('companyMotto').textContent = value;
            } else if (currentSettingType === 'mission') {
                document.getElementById('companyMission').textContent = value;
            } else if (currentSettingType === 'priority') {
                document.getElementById('priorityTasks').textContent = value;
            }
        } else {
            showToast(response.message || '저장 중 오류가 발생했습니다.', 'error');
        }
    } catch (error) {
        showToast('저장 중 오류가 발생했습니다.', 'error');
    }
}
</script>

<!-- 설정 수정 모달 -->
<?php if (isAdmin()): ?>
<div class="modal-overlay" id="settingModal">
    <div class="modal" style="max-width: 500px;">
        <div class="modal-header">
            <h3 id="settingModalTitle">설정 수정</h3>
            <button class="modal-close" onclick="closeModal('settingModal')">&times;</button>
        </div>
        <div class="modal-body">
            <textarea id="settingInput" class="form-control" rows="6" style="width: 100%; resize: vertical; min-height: 120px;"></textarea>
            <p style="font-size: 12px; color: #888; margin-top: 8px;">* Enter키로 줄바꿈이 가능합니다.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('settingModal')">취소</button>
            <button class="btn btn-primary" onclick="saveSetting()">저장</button>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
