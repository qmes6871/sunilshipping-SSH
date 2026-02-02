<?php
/**
 * 개인 정보 수정
 */

require_once dirname(__DIR__) . '/includes/auth_check.php';

$pageTitle = '개인 정보 수정';
$pageSubtitle = '회원님의 개인 정보를 안전하게 관리하세요';

$pdo = getDB();

// 처리 결과 메시지
$message = '';
$messageType = '';

// POST 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrfToken) {
        $message = 'CSRF 토큰이 유효하지 않습니다.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_profile') {
            try {
                $name = trim($_POST['name'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $extension = trim($_POST['extension'] ?? '');
                $memo = trim($_POST['memo'] ?? '');
                $department = $_POST['department'] ?? $currentUser['department'];
                $position = $_POST['position'] ?? $currentUser['position'];

                $stmt = $pdo->prepare("UPDATE " . CRM_USERS_TABLE . "
                    SET name = ?, phone = ?, email = ?, department = ?, position = ?, memo = ?, updated_at = NOW()
                    WHERE id = ?");
                $stmt->execute([$name, $phone, $email, $department, $position, $memo, $currentUser['crm_user_id']]);

                // 프로필 사진 업로드
                if (!empty($_FILES['profile_photo']['name'])) {
                    $uploadResult = uploadFile($_FILES['profile_photo'], 'profiles', ['image/jpeg', 'image/png', 'image/gif']);
                    if ($uploadResult['success']) {
                        if (!empty($currentUser['profile_photo'])) {
                            deleteFile($currentUser['profile_photo']);
                        }
                        $stmt = $pdo->prepare("UPDATE " . CRM_USERS_TABLE . " SET profile_photo = ? WHERE id = ?");
                        $stmt->execute([$uploadResult['path'], $currentUser['crm_user_id']]);
                    }
                }

                $message = '정보가 성공적으로 수정되었습니다.';
                $messageType = 'success';

                // 현재 사용자 정보 갱신
                $stmt = $pdo->prepare("SELECT * FROM " . CRM_USERS_TABLE . " WHERE id = ?");
                $stmt->execute([$currentUser['crm_user_id']]);
                $updatedUser = $stmt->fetch();
                if ($updatedUser) {
                    $currentUser = array_merge($currentUser, $updatedUser);
                }

            } catch (Exception $e) {
                $message = '개인정보 수정 중 오류가 발생했습니다.';
                $messageType = 'error';
            }
        } elseif ($action === 'change_password') {
            $currentPw = $_POST['current_password'] ?? '';
            $newPw = $_POST['new_password'] ?? '';
            $confirmPw = $_POST['confirm_password'] ?? '';

            if (empty($currentPw) || empty($newPw) || empty($confirmPw)) {
                $message = '모든 비밀번호 필드를 입력해주세요.';
                $messageType = 'error';
            } elseif ($newPw !== $confirmPw) {
                $message = '새 비밀번호가 일치하지 않습니다.';
                $messageType = 'error';
            } elseif (strlen($newPw) < 8) {
                $message = '비밀번호는 8자 이상이어야 합니다.';
                $messageType = 'error';
            } else {
                try {
                    $stmt = $pdo->prepare("SELECT mb_password FROM " . G5_MEMBER_TABLE . " WHERE mb_id = ?");
                    $stmt->execute([$currentUser['mb_id']]);
                    $member = $stmt->fetch();

                    if ($member && password_verify($currentPw, $member['mb_password'])) {
                        $newHash = password_hash($newPw, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE " . G5_MEMBER_TABLE . " SET mb_password = ? WHERE mb_id = ?");
                        $stmt->execute([$newHash, $currentUser['mb_id']]);

                        $message = '비밀번호가 변경되었습니다.';
                        $messageType = 'success';
                    } else {
                        $message = '현재 비밀번호가 일치하지 않습니다.';
                        $messageType = 'error';
                    }
                } catch (Exception $e) {
                    $message = '비밀번호 변경 중 오류가 발생했습니다.';
                    $messageType = 'error';
                }
            }
        } elseif ($action === 'delete_photo') {
            try {
                if (!empty($currentUser['profile_photo'])) {
                    deleteFile($currentUser['profile_photo']);
                }
                $stmt = $pdo->prepare("UPDATE " . CRM_USERS_TABLE . " SET profile_photo = NULL WHERE id = ?");
                $stmt->execute([$currentUser['crm_user_id']]);
                $currentUser['profile_photo'] = null;
                $message = '사진이 삭제되었습니다.';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = '사진 삭제 중 오류가 발생했습니다.';
                $messageType = 'error';
            }
        }
    }
}

include dirname(__DIR__) . '/includes/header.php';
?>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

.container {
    max-width: 600px;
    margin: 0 auto;
    padding: 40px 20px;
}

.header {
    text-align: center;
    margin-bottom: 40px;
}

.header h1 {
    font-size: 28px;
    font-weight: 700;
    color: #212529;
    margin-bottom: 8px;
}

.header p {
    font-size: 14px;
    color: #6c757d;
}

.card {
    background: white;
    padding: 40px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.profile-photo-section {
    text-align: center;
    margin-bottom: 40px;
    padding-bottom: 30px;
    border-bottom: 1px solid #e9ecef;
}

.avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: #6c757d;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    font-weight: bold;
    margin: 0 auto 20px;
    overflow: hidden;
}

.avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.photo-buttons {
    display: flex;
    gap: 12px;
    justify-content: center;
}

.form-group { margin-bottom: 24px; }

.form-label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #212529;
    margin-bottom: 8px;
}

.form-label .required {
    color: #dc3545;
    margin-left: 2px;
}

.form-input {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    font-size: 15px;
    transition: border-color 0.2s;
}

.form-input:focus {
    outline: none;
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}

.form-input:disabled {
    background: #e9ecef;
    cursor: not-allowed;
}

.form-helper {
    font-size: 12px;
    color: #6c757d;
    margin-top: 6px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.btn {
    padding: 12px 24px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    background: white;
    color: #495057;
    cursor: pointer;
    font-size: 15px;
    font-weight: 500;
    transition: all 0.2s;
}

.btn:hover { background: #f8f9fa; }

.btn-primary {
    background: #0d6efd;
    color: white;
    border-color: #0d6efd;
}

.btn-primary:hover { background: #0b5ed7; }

.btn-danger {
    background: white;
    color: #dc3545;
    border-color: #dee2e6;
}

.btn-danger:hover {
    background: #dc3545;
    color: white;
    border-color: #dc3545;
}

.action-buttons {
    display: flex;
    gap: 12px;
    margin-top: 40px;
    padding-top: 30px;
    border-top: 1px solid #e9ecef;
}

.action-buttons .btn { flex: 1; }

.password-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 6px;
    margin-bottom: 24px;
}

.password-section h3 {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 16px;
}

.alert {
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 24px;
}

.alert-success { background: #d4edda; color: #155724; }
.alert-error { background: #f8d7da; color: #721c24; }

@media (max-width: 768px) {
    .card { padding: 24px; }
    .form-row { grid-template-columns: 1fr; }
    .action-buttons { flex-direction: column; }
    .photo-buttons { flex-direction: column; }
}
</style>

<div class="container">
    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>"><?= h($message) ?></div>
    <?php endif; ?>

    <div class="header">
        <h1>개인 정보 수정</h1>
        <p>회원님의 개인 정보를 안전하게 관리하세요</p>
    </div>

    <div class="card">
        <!-- 프로필 사진 -->
        <div class="profile-photo-section">
            <div class="avatar">
                <?php if (!empty($currentUser['profile_photo'])): ?>
                    <img src="<?= CRM_UPLOAD_URL ?>/<?= h($currentUser['profile_photo']) ?>" alt="프로필" id="previewPhoto">
                <?php else: ?>
                    <span id="avatarText"><?= mb_substr($currentUser['mb_name'] ?? $currentUser['name'] ?? 'U', 0, 1) ?></span>
                    <img src="" alt="프로필" id="previewPhoto" style="display:none;">
                <?php endif; ?>
            </div>
            <div class="photo-buttons">
                <label class="btn" style="cursor:pointer;">
                    사진 변경
                    <input type="file" id="photoInput" accept="image/*" style="display:none;">
                </label>
                <form method="POST" style="display:inline;" onsubmit="return confirm('프로필 사진을 삭제하시겠습니까?')">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="action" value="delete_photo">
                    <button type="submit" class="btn btn-danger">사진 삭제</button>
                </form>
            </div>
        </div>

        <!-- 기본 정보 -->
        <form method="POST" enctype="multipart/form-data" id="profileForm">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="action" value="update_profile">
            <input type="file" name="profile_photo" id="hiddenPhotoInput" style="display:none;">

            <div class="form-group">
                <label class="form-label">이름 <span class="required">*</span></label>
                <input type="text" class="form-input" name="name" value="<?= h($currentUser['name'] ?? $currentUser['mb_name'] ?? '') ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">부서 <span class="required">*</span></label>
                    <select class="form-input" name="department" required>
                        <option value="">부서 선택</option>
                        <option value="logistics" <?= ($currentUser['department'] ?? '') === 'logistics' ? 'selected' : '' ?>>물류사업부</option>
                        <option value="agricultural" <?= ($currentUser['department'] ?? '') === 'agricultural' ? 'selected' : '' ?>>농산물사업부</option>
                        <option value="pellet" <?= ($currentUser['department'] ?? '') === 'pellet' ? 'selected' : '' ?>>우드펠렛사업부</option>
                        <option value="support" <?= ($currentUser['department'] ?? '') === 'support' ? 'selected' : '' ?>>경영지원</option>
                        <option value="admin" <?= ($currentUser['department'] ?? '') === 'admin' ? 'selected' : '' ?>>관리자</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">직급 <span class="required">*</span></label>
                    <select class="form-input" name="position" required>
                        <option value="">직급 선택</option>
                        <option value="staff" <?= ($currentUser['position'] ?? '') === 'staff' ? 'selected' : '' ?>>사원</option>
                        <option value="assistant" <?= ($currentUser['position'] ?? '') === 'assistant' ? 'selected' : '' ?>>대리</option>
                        <option value="manager" <?= ($currentUser['position'] ?? '') === 'manager' ? 'selected' : '' ?>>과장</option>
                        <option value="deputy" <?= ($currentUser['position'] ?? '') === 'deputy' ? 'selected' : '' ?>>차장</option>
                        <option value="director" <?= ($currentUser['position'] ?? '') === 'director' ? 'selected' : '' ?>>부장</option>
                        <option value="executive" <?= ($currentUser['position'] ?? '') === 'executive' ? 'selected' : '' ?>>임원</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">이메일 <span class="required">*</span></label>
                <input type="email" class="form-input" name="email" value="<?= h($currentUser['email'] ?? $currentUser['mb_email'] ?? '') ?>" required>
                <div class="form-helper">업무 연락을 받을 이메일 주소를 입력하세요</div>
            </div>

            <div class="form-group">
                <label class="form-label">휴대폰 번호 <span class="required">*</span></label>
                <input type="tel" class="form-input" name="phone" value="<?= h($currentUser['phone'] ?? '') ?>" placeholder="010-1234-5678" required>
                <div class="form-helper">'-' 포함하여 입력하세요</div>
            </div>

            <div class="form-group">
                <label class="form-label">내선번호</label>
                <input type="text" class="form-input" name="extension" value="<?= h($currentUser['extension'] ?? '') ?>" placeholder="예: 1234">
            </div>

            <!-- 비밀번호 변경 -->
            <div class="password-section">
                <h3>비밀번호 변경</h3>

                <div class="form-group">
                    <label class="form-label">현재 비밀번호</label>
                    <input type="password" class="form-input" id="currentPassword" placeholder="현재 비밀번호 입력">
                </div>

                <div class="form-group">
                    <label class="form-label">새 비밀번호</label>
                    <input type="password" class="form-input" id="newPassword" placeholder="8자 이상, 영문/숫자/특수문자 조합">
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">새 비밀번호 확인</label>
                    <input type="password" class="form-input" id="confirmPassword" placeholder="새 비밀번호 재입력">
                </div>
            </div>

            <!-- 추가 정보 -->
            <div class="form-group">
                <label class="form-label">자기소개</label>
                <textarea class="form-input" name="memo" rows="4" placeholder="간단한 자기소개를 입력하세요 (선택사항)"><?= h($currentUser['memo'] ?? '') ?></textarea>
            </div>

            <!-- 버튼 -->
            <div class="action-buttons">
                <button type="button" class="btn" onclick="history.back()">취소</button>
                <button type="submit" class="btn btn-primary">저장하기</button>
            </div>
        </form>
    </div>
</div>

<!-- 비밀번호 변경용 숨겨진 폼 -->
<form method="POST" id="passwordForm" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    <input type="hidden" name="action" value="change_password">
    <input type="hidden" name="current_password" id="hiddenCurrentPw">
    <input type="hidden" name="new_password" id="hiddenNewPw">
    <input type="hidden" name="confirm_password" id="hiddenConfirmPw">
</form>

<?php
$pageScripts = <<<'SCRIPT'
<script>
// 프로필 사진 미리보기 및 폼에 추가
document.getElementById('photoInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // 숨겨진 input에 파일 복사
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        document.getElementById('hiddenPhotoInput').files = dataTransfer.files;

        // 미리보기
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('previewPhoto');
            const avatarText = document.getElementById('avatarText');
            preview.src = e.target.result;
            preview.style.display = 'block';
            if (avatarText) avatarText.style.display = 'none';
        };
        reader.readAsDataURL(file);
    }
});

// 비밀번호 변경 (입력값이 있을 때만)
document.getElementById('profileForm').addEventListener('submit', function(e) {
    const currentPw = document.getElementById('currentPassword').value;
    const newPw = document.getElementById('newPassword').value;
    const confirmPw = document.getElementById('confirmPassword').value;

    // 비밀번호 필드 중 하나라도 입력되어 있으면 검증
    if (currentPw || newPw || confirmPw) {
        if (!currentPw || !newPw || !confirmPw) {
            e.preventDefault();
            alert('비밀번호를 변경하려면 모든 필드를 입력해주세요.');
            return;
        }
        if (newPw !== confirmPw) {
            e.preventDefault();
            alert('새 비밀번호가 일치하지 않습니다.');
            return;
        }
        if (newPw.length < 8) {
            e.preventDefault();
            alert('비밀번호는 8자 이상이어야 합니다.');
            return;
        }

        // 비밀번호도 함께 변경
        document.getElementById('hiddenCurrentPw').value = currentPw;
        document.getElementById('hiddenNewPw').value = newPw;
        document.getElementById('hiddenConfirmPw').value = confirmPw;

        // 비밀번호 폼 제출
        setTimeout(function() {
            document.getElementById('passwordForm').submit();
        }, 100);
    }
});
</script>
SCRIPT;

include dirname(__DIR__) . '/includes/footer.php';
?>
