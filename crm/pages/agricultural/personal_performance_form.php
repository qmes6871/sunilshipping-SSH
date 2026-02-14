<?php
/**
 * 농산물 개인실적 등록/수정
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pdo = getDB();

// 수정 모드 확인
$editId = $_GET['id'] ?? null;
$editData = null;
$isEdit = false;

if ($editId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM " . CRM_AGRI_PERSONAL_PERFORMANCE_TABLE . " WHERE id = ?");
        $stmt->execute([$editId]);
        $editData = $stmt->fetch();
        if ($editData) {
            $isEdit = true;
        }
    } catch (Exception $e) {
        // 오류 시 신규 등록 모드
    }
}

$pageTitle = $isEdit ? '개인실적 수정' : '개인실적 등록';
$pageSubtitle = '농산물 월별 개인 실적 ' . ($isEdit ? '수정' : '등록');

// 품목 목록
$crops = ['배', '사과', '감귤', '딸기', '수박', '포도', '기타'];

// POST 처리
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrfToken) {
        $message = 'CSRF 토큰이 유효하지 않습니다.';
        $messageType = 'error';
    } else {
        $data = [
            'user_id' => $currentUser['crm_user_id'],
            'period_year' => $_POST['year'] ?? date('Y'),
            'period_month' => $_POST['month'] ?? date('n'),
            'crop' => $_POST['crop'] ?? '',
            'target_ton' => floatval($_POST['target'] ?? 0),
            'actual_ton' => floatval($_POST['actual'] ?? 0),
            'notes' => trim($_POST['notes'] ?? '')
        ];

        if (empty($data['crop'])) {
            $message = '품목을 선택해주세요.';
            $messageType = 'error';
        } else {
            try {
                // 달성률 계산 (DECIMAL(5,2) 범위 내로 제한: 최대 999.99)
                $achievementRate = $data['target_ton'] > 0 ? round(($data['actual_ton'] / $data['target_ton']) * 100, 2) : 0;
                $achievementRate = min($achievementRate, 999.99); // 최대값 제한

                // 직원명 가져오기
                $employeeName = $currentUser['mb_name'] ?? $currentUser['mb_nick'] ?? '';

                $postId = $_POST['id'] ?? null;

                if ($postId) {
                    // 수정 모드
                    $stmt = $pdo->prepare("UPDATE " . CRM_AGRI_PERSONAL_PERFORMANCE_TABLE . "
                        SET year = ?, month = ?, item_name = ?, target_amount = ?, actual_amount = ?, achievement_rate = ?, notes = ?, updated_at = NOW()
                        WHERE id = ?");
                    $stmt->execute([
                        $data['period_year'],
                        $data['period_month'],
                        $data['crop'],
                        $data['target_ton'],
                        $data['actual_ton'],
                        $achievementRate,
                        $data['notes'],
                        $postId
                    ]);
                    $message = '실적이 수정되었습니다.';
                } else {
                    // 신규 등록 모드
                    $stmt = $pdo->prepare("INSERT INTO " . CRM_AGRI_PERSONAL_PERFORMANCE_TABLE . "
                        (user_id, employee_name, year, month, item_name, target_amount, actual_amount, achievement_rate, notes, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                        ON DUPLICATE KEY UPDATE
                        target_amount = VALUES(target_amount),
                        actual_amount = VALUES(actual_amount),
                        achievement_rate = VALUES(achievement_rate),
                        notes = VALUES(notes),
                        updated_at = NOW()");
                    $stmt->execute([
                        $data['user_id'],
                        $employeeName,
                        $data['period_year'],
                        $data['period_month'],
                        $data['crop'],
                        $data['target_ton'],
                        $data['actual_ton'],
                        $achievementRate,
                        $data['notes']
                    ]);
                    $message = '실적이 등록되었습니다.';
                }

                $messageType = 'success';
            } catch (Exception $e) {
                $message = '저장 중 오류가 발생했습니다: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

.container {
    max-width: 800px;
    margin: 0 auto;
    background: white;
    padding: 40px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

h1 { font-size: 24px; margin-bottom: 8px; color: #212529; }
.subtitle { color: #6c757d; font-size: 14px; margin-bottom: 32px; }

.form-group { margin-bottom: 20px; }
label { display: block; margin-bottom: 8px; font-weight: 500; color: #212529; font-size: 14px; }

input, select, textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    font-size: 14px;
    font-family: inherit;
}

input:focus, select:focus, textarea:focus { outline: none; border-color: #fd7e14; }
textarea { resize: vertical; min-height: 80px; }

.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

.btn-group {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid #e9ecef;
}

.btn {
    padding: 10px 24px;
    border-radius: 4px;
    font-size: 14px;
    cursor: pointer;
    border: none;
    text-decoration: none;
}

.btn-cancel { background: #f8f9fa; color: #495057; border: 1px solid #dee2e6; }
.btn-submit { background: #fd7e14; color: white; }
.btn-submit:hover { background: #e8590c; }

.alert { padding: 16px; border-radius: 8px; margin-bottom: 24px; }
.alert-success { background: #d1e7dd; color: #0f5132; }
.alert-error { background: #f8d7da; color: #842029; }

@media (max-width: 768px) {
    .container { padding: 24px; }
    .form-row { grid-template-columns: 1fr; }
}
</style>

<div class="container">
    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>"><?= h($message) ?></div>
    <?php endif; ?>

    <h1><?= $isEdit ? '개인실적 수정' : '개인실적 등록' ?></h1>
    <p class="subtitle">농산물 월별 개인 실적 <?= $isEdit ? '수정' : '등록' ?></p>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= $editData['id'] ?>">
        <?php endif; ?>

        <div class="form-row">
            <div class="form-group">
                <label for="year">년도</label>
                <select id="year" name="year">
                    <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                        <option value="<?= $y ?>" <?= ($editData['year'] ?? date('Y')) == $y ? 'selected' : '' ?>><?= $y ?>년</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="month">월</label>
                <select id="month" name="month">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= ($editData['month'] ?? date('n')) == $m ? 'selected' : '' ?>><?= $m ?>월</option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="name">이름</label>
                <input type="text" id="name" value="<?= h($editData['employee_name'] ?? $currentUser['mb_name'] ?? $currentUser['mb_nick'] ?? '') ?>" readonly>
            </div>
            <div class="form-group">
                <label for="crop">품목</label>
                <select id="crop" name="crop" required>
                    <option value="">선택</option>
                    <?php foreach ($crops as $crop): ?>
                        <option value="<?= $crop ?>" <?= ($editData['item_name'] ?? '') === $crop ? 'selected' : '' ?>><?= $crop ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="target">목표 (톤)</label>
                <input type="number" id="target" name="target" placeholder="0" min="0" step="0.1" value="<?= $editData['target_amount'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label for="actual">실적 (톤)</label>
                <input type="number" id="actual" name="actual" placeholder="0" min="0" step="0.1" value="<?= $editData['actual_amount'] ?? '' ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="notes">비고</label>
            <textarea id="notes" name="notes" placeholder="메모"><?= h($editData['notes'] ?? '') ?></textarea>
        </div>

        <div class="btn-group">
            <a href="performance_chart.php" class="btn btn-cancel">취소</a>
            <button type="submit" class="btn btn-submit"><?= $isEdit ? '수정' : '등록' ?></button>
        </div>
    </form>
</div>

<script>
// 폼 제출 전 처리 - 빈 값을 0으로 변환
document.querySelector('form').addEventListener('submit', function(e) {
    const targetInput = document.getElementById('target');
    const actualInput = document.getElementById('actual');

    // 빈 값을 0으로 설정 (0도 정상적으로 저장됨)
    if (targetInput.value === '' || targetInput.value === null) {
        targetInput.value = 0;
    }
    if (actualInput.value === '' || actualInput.value === null) {
        actualInput.value = 0;
    }

    return true;
});
</script>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
