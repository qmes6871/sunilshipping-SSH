<?php
/**
 * 내 파일 관리
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pageTitle = '내 파일';
$pageSubtitle = '개인 파일 관리';

$pdo = getDB();

// 테이블 생성 (없으면)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS " . CRM_USER_FILES_TABLE . " (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_size INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {
    // 테이블이 이미 존재하면 무시
}

// user_id 가져오기 (crm_user_id 또는 mb_id 해시값 사용) - API와 동일하게
$userId = $currentUser['crm_user_id'] ?? null;
if (!$userId) {
    $userId = crc32($currentUser['mb_id'] ?? 'guest');
}

// 필터
$typeFilter = $_GET['type'] ?? '';
$sort = $_GET['sort'] ?? 'recent';
$search = $_GET['search'] ?? '';

$where = ["user_id = ?"];
$params = [$userId];

// 확장자로 필터링
if ($typeFilter) {
    $extMap = [
        'doc' => ['pdf', 'doc', 'docx', 'txt', 'hwp'],
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'],
        'excel' => ['xls', 'xlsx', 'csv']
    ];
    if (isset($extMap[$typeFilter])) {
        $extConditions = [];
        foreach ($extMap[$typeFilter] as $ext) {
            $extConditions[] = "file_name LIKE ?";
            $params[] = "%.{$ext}";
        }
        $where[] = "(" . implode(' OR ', $extConditions) . ")";
    }
}
if ($search) {
    $where[] = "file_name LIKE ?";
    $params[] = "%{$search}%";
}

$whereClause = implode(' AND ', $where);

$orderBy = "created_at DESC";
if ($sort === 'name') $orderBy = "file_name ASC";
elseif ($sort === 'size') $orderBy = "file_size DESC";

// 통계
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(file_size), 0) as total_size FROM " . CRM_USER_FILES_TABLE . " WHERE user_id = ?");
    $stmt->execute([$userId]);
    $stats = $stmt->fetch();
    $totalFiles = $stats['cnt'] ?? 0;
    $totalSize = $stats['total_size'] ?? 0;
} catch (Exception $e) {
    $totalFiles = 0;
    $totalSize = 0;
}

// 파일 목록
try {
    $stmt = $pdo->prepare("SELECT * FROM " . CRM_USER_FILES_TABLE . " WHERE {$whereClause} ORDER BY {$orderBy}");
    $stmt->execute($params);
    $files = $stmt->fetchAll();
} catch (Exception $e) {
    $files = [];
}

// 파일 크기 포맷
function formatSize($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}

// 파일 아이콘
function getFileIcon($type) {
    $type = strtolower($type ?? '');
    // MIME 타입 처리
    if (strpos($type, 'image/') === 0) return '🖼️';
    if (strpos($type, 'application/pdf') === 0) return '📄';
    if (strpos($type, 'application/vnd.ms-excel') === 0 || strpos($type, 'spreadsheet') !== false) return '📊';
    if (strpos($type, 'application/msword') === 0 || strpos($type, 'document') !== false) return '📝';
    // 확장자 처리
    if (in_array($type, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'])) return '🖼️';
    if ($type === 'pdf') return '📄';
    if (in_array($type, ['xls', 'xlsx', 'csv'])) return '📊';
    if (in_array($type, ['doc', 'docx', 'hwp', 'txt'])) return '📝';
    if (in_array($type, ['zip', 'rar', '7z'])) return '🗜️';
    return '📄';
}

// POST 처리 (삭제만 - 업로드는 API 사용)
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrfToken) {
        $message = 'CSRF 토큰이 유효하지 않습니다.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'delete') {
            $fileId = $_POST['file_id'] ?? 0;
            try {
                $stmt = $pdo->prepare("SELECT file_path FROM " . CRM_USER_FILES_TABLE . " WHERE id = ? AND user_id = ?");
                $stmt->execute([$fileId, $userId]);
                $file = $stmt->fetch();
                if ($file) {
                    deleteFile($file['file_path']);
                    $stmt = $pdo->prepare("DELETE FROM " . CRM_USER_FILES_TABLE . " WHERE id = ?");
                    $stmt->execute([$fileId]);
                    $message = '파일이 삭제되었습니다.';
                    $messageType = 'success';
                    header('Location: my_files.php');
                    exit;
                }
            } catch (Exception $e) {
                $message = '삭제 중 오류가 발생했습니다.';
                $messageType = 'error';
            }
        } elseif ($action === 'delete_selected') {
            $ids = $_POST['selected_ids'] ?? [];
            if (!empty($ids)) {
                try {
                    foreach ($ids as $fileId) {
                        $stmt = $pdo->prepare("SELECT file_path FROM " . CRM_USER_FILES_TABLE . " WHERE id = ? AND user_id = ?");
                        $stmt->execute([$fileId, $userId]);
                        $file = $stmt->fetch();
                        if ($file) {
                            deleteFile($file['file_path']);
                            $stmt = $pdo->prepare("DELETE FROM " . CRM_USER_FILES_TABLE . " WHERE id = ?");
                            $stmt->execute([$fileId]);
                        }
                    }
                    $message = count($ids) . '개 파일이 삭제되었습니다.';
                    $messageType = 'success';
                    header('Location: my_files.php');
                    exit;
                } catch (Exception $e) {
                    $message = '삭제 중 오류가 발생했습니다.';
                    $messageType = 'error';
                }
            }
        }
        // 오류가 없는 경우에만 리다이렉트 (오류 메시지 표시 위해)
    }
}

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

.container { max-width: 1200px; margin: 0 auto; padding: 20px; }

.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.header-left { display: flex; align-items: center; gap: 16px; }
.page-title { font-size: 28px; font-weight: 700; color: #212529; margin-bottom: 4px; }
.page-subtitle { font-size: 14px; color: #6c757d; }
.btn-back { padding: 8px 16px; border: 1px solid #dee2e6; border-radius: 4px; background: white; color: #495057; font-size: 14px; text-decoration: none; }
.btn-back:hover { background: #f8f9fa; }

.card { background: white; padding: 24px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 24px; }
.card-header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #e9ecef; }
.card-title { font-size: 18px; font-weight: 600; color: #212529; }

.btn { padding: 8px 16px; border: 1px solid #dee2e6; border-radius: 4px; background: white; color: #495057; cursor: pointer; font-size: 14px; }
.btn:hover { background: #f8f9fa; }
.btn-primary { background: #0d6efd; color: white; border-color: #0d6efd; }
.btn-primary:hover { background: #0b5ed7; }
.btn-danger { background: #dc3545; color: white; border-color: #dc3545; }
.btn-danger:hover { background: #bb2d3b; }
.btn-small { padding: 6px 12px; font-size: 12px; }

.file-upload { border: 2px dashed #dee2e6; border-radius: 6px; padding: 32px 16px; text-align: center; cursor: pointer; margin-bottom: 16px; background: #fafbfc; display: flex; flex-direction: column; align-items: center; justify-content: center; }
.file-upload:hover { border-color: #0d6efd; background: #f8f9ff; }

.stats-bar { display: flex; gap: 24px; padding: 16px; background: #f8f9fa; border-radius: 6px; margin-bottom: 20px; }
.stat-item { display: flex; align-items: center; gap: 8px; }
.stat-label { font-size: 13px; color: #6c757d; }
.stat-value { font-size: 16px; font-weight: 600; color: #212529; }

.filter-bar { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 20px; flex-wrap: wrap; }
.search-box { display: flex; align-items: center; gap: 8px; flex: 1; max-width: 400px; }
.search-input { flex: 1; padding: 10px 16px; border: 1px solid #dee2e6; border-radius: 4px; font-size: 14px; }
.filter-options { display: flex; gap: 12px; align-items: center; }

.file-list-item { display: flex; justify-content: space-between; align-items: center; padding: 14px 16px; background: #f8f9fa; border-radius: 4px; margin-bottom: 10px; font-size: 14px; }
.file-list-item:hover { background: #e9ecef; }
.file-info { display: flex; align-items: center; gap: 12px; flex: 1; }
.file-checkbox { width: 18px; height: 18px; cursor: pointer; }
.file-icon-text { font-size: 20px; }
.file-details { flex: 1; }
.file-name { font-weight: 500; color: #212529; margin-bottom: 2px; }
.file-meta { font-size: 12px; color: #6c757d; }
.file-actions { display: flex; gap: 8px; }

.empty-state { text-align: center; padding: 64px 24px; color: #6c757d; }

.alert { padding: 16px; border-radius: 8px; margin-bottom: 24px; }
.alert-success { background: #d1e7dd; color: #0f5132; }
.alert-error { background: #f8d7da; color: #842029; }

@media (max-width: 768px) {
    .filter-bar { flex-direction: column; align-items: stretch; }
    .search-box { max-width: 100%; }
    .stats-bar { flex-direction: column; gap: 12px; }
    .file-list-item { flex-direction: column; align-items: flex-start; gap: 12px; }
    .file-actions { width: 100%; justify-content: flex-end; }
}
</style>

<div class="container">
    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>"><?= h($message) ?></div>
    <?php endif; ?>

    <div class="page-header">
        <div class="header-left">
            <a href="<?= CRM_URL ?>/pages/main.php" class="btn-back">← 뒤로가기</a>
            <div>
                <div class="page-title">내 파일</div>
                <div class="page-subtitle">개인 파일 관리</div>
            </div>
        </div>
    </div>

    <!-- 파일 업로드 -->
    <div class="card">
        <div class="card-title" style="margin-bottom: 16px;">파일 업로드</div>
        <div class="file-upload" onclick="document.getElementById('fileInput').click()">
            <input type="file" id="fileInput" style="display:none;" onchange="uploadFile(this)">
            <div style="font-size: 32px; margin-bottom: 8px;">📁</div>
            <div style="font-size: 14px; color: #495057; margin-bottom: 4px;">파일을 드래그하거나 클릭하여 업로드</div>
        </div>
    </div>

    <!-- 파일 목록 -->
    <div class="card">
        <div class="card-header-row">
            <div class="card-title">파일 목록</div>
            <button type="button" class="btn btn-danger btn-small" onclick="deleteSelected()">선택 삭제</button>
        </div>

        <div class="stats-bar">
            <div class="stat-item">
                <span class="stat-label">전체 파일</span>
                <span class="stat-value"><?= number_format($totalFiles) ?>개</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">총 용량</span>
                <span class="stat-value"><?= formatSize($totalSize) ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">사용 가능</span>
                <span class="stat-value"><?= formatSize(1073741824 - $totalSize) ?></span>
            </div>
        </div>

        <form method="GET" class="filter-bar">
            <div class="search-box">
                <input type="text" class="search-input" name="search" placeholder="파일 검색..." value="<?= h($search) ?>">
                <button type="submit" class="btn">검색</button>
            </div>
            <div class="filter-options">
                <select class="btn" name="type" onchange="this.form.submit()">
                    <option value="">전체 파일</option>
                    <option value="doc" <?= $typeFilter === 'doc' ? 'selected' : '' ?>>문서</option>
                    <option value="image" <?= $typeFilter === 'image' ? 'selected' : '' ?>>이미지</option>
                    <option value="excel" <?= $typeFilter === 'excel' ? 'selected' : '' ?>>스프레드시트</option>
                </select>
                <select class="btn" name="sort" onchange="this.form.submit()">
                    <option value="recent" <?= $sort === 'recent' ? 'selected' : '' ?>>최근 업로드순</option>
                    <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>이름순</option>
                    <option value="size" <?= $sort === 'size' ? 'selected' : '' ?>>크기순</option>
                </select>
            </div>
        </form>

        <form method="POST" id="fileListForm">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="action" value="delete_selected">

            <?php if (empty($files)): ?>
                <div class="empty-state">
                    <div style="font-size: 64px; margin-bottom: 16px; opacity: 0.5;">📂</div>
                    <div style="font-size: 16px; margin-bottom: 8px;">업로드된 파일이 없습니다</div>
                    <div style="font-size: 14px; color: #adb5bd;">파일을 업로드해보세요</div>
                </div>
            <?php else: ?>
                <?php foreach ($files as $file): ?>
                    <div class="file-list-item">
                        <div class="file-info">
                            <input type="checkbox" class="file-checkbox" name="selected_ids[]" value="<?= $file['id'] ?>">
                            <span class="file-icon-text"><?= getFileIcon($file['file_type'] ?? pathinfo($file['file_name'], PATHINFO_EXTENSION)) ?></span>
                            <div class="file-details">
                                <div class="file-name"><?= h($file['file_name']) ?></div>
                                <div class="file-meta"><?= formatSize($file['file_size']) ?> · <?= formatDate($file['created_at'], 'Y.m.d') ?></div>
                            </div>
                        </div>
                        <div class="file-actions">
                            <a href="<?= CRM_UPLOAD_URL ?>/<?= h($file['file_path']) ?>" class="btn btn-small" download>다운로드</a>
                            <button type="button" class="btn btn-small" onclick="deleteFile(<?= $file['id'] ?>)">삭제</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- 삭제 폼 -->
<form method="POST" id="deleteForm" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="file_id" id="deleteFileId">
</form>

<?php
$crmUrl = CRM_URL;
$pageScripts = <<<SCRIPT
<script>
// 파일 업로드 (API 사용)
async function uploadFile(input) {
    console.log('uploadFile called');
    if (!input.files.length) {
        console.log('No files selected');
        return;
    }

    console.log('File:', input.files[0].name, input.files[0].size);

    const formData = new FormData();
    formData.append('file', input.files[0]);

    const apiUrl = '$crmUrl/api/users/files.php';
    console.log('API URL:', apiUrl);
    console.log('CSRF Token:', typeof CSRF_TOKEN !== 'undefined' ? CSRF_TOKEN : 'NOT DEFINED');

    try {
        if (typeof apiPostForm !== 'function') {
            console.error('apiPostForm is not defined!');
            alert('apiPostForm 함수가 정의되지 않았습니다. 페이지를 새로고침 해주세요.');
            return;
        }

        const response = await apiPostForm(apiUrl, formData);
        console.log('Response:', response);
        if (response.success) {
            showToast('파일이 업로드되었습니다.', 'success');
            location.reload();
        } else {
            showToast(response.message || '파일 업로드에 실패했습니다.', 'error');
        }
    } catch (error) {
        console.error('Upload error:', error);
        showToast(error.message || '파일 업로드에 실패했습니다.', 'error');
    }

    // 입력 초기화
    input.value = '';
}

function deleteFile(id) {
    if (confirm('파일을 삭제하시겠습니까?')) {
        document.getElementById('deleteFileId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

function deleteSelected() {
    const checked = document.querySelectorAll('.file-checkbox:checked');
    if (checked.length === 0) {
        alert('삭제할 파일을 선택해주세요.');
        return;
    }
    if (confirm(checked.length + '개의 파일을 삭제하시겠습니까?')) {
        document.getElementById('fileListForm').submit();
    }
}
</script>
SCRIPT;

include dirname(dirname(__DIR__)) . '/includes/footer.php';
?>
