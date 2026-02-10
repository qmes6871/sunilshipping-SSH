<?php
/**
 * 국제물류 설정 관리
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

// 관리자만 접근 가능
if (!isAdmin()) {
    header('Location: ' . CRM_URL . '/pages/international/dashboard.php');
    exit;
}

$pdo = getDB();

$pageTitle = '국제물류 설정';
$pageSubtitle = '국가/지역 관리';

// 현재 설정 로드
$countries = getIntlCountries();
$regions = getIntlRegions();

// POST 처리
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_countries') {
        $countriesInput = trim($_POST['countries'] ?? '');
        $countriesList = array_filter(array_map('trim', explode("\n", $countriesInput)));

        if (!empty($countriesList)) {
            setSetting('intl_countries', json_encode($countriesList, JSON_UNESCAPED_UNICODE));
            $countries = $countriesList;
            $message = '국가 목록이 저장되었습니다.';
            $messageType = 'success';
        } else {
            $message = '최소 1개 이상의 국가를 입력해주세요.';
            $messageType = 'error';
        }
    } elseif ($action === 'update_regions') {
        $regionsInput = trim($_POST['regions'] ?? '');
        $regionsList = array_filter(array_map('trim', explode("\n", $regionsInput)));

        if (!empty($regionsList)) {
            setSetting('intl_regions', json_encode($regionsList, JSON_UNESCAPED_UNICODE));
            $regions = $regionsList;
            $message = '지역 목록이 저장되었습니다.';
            $messageType = 'success';
        } else {
            $message = '최소 1개 이상의 지역을 입력해주세요.';
            $messageType = 'error';
        }
    }
}

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<style>
.settings-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.page-title {
    font-size: 24px;
    font-weight: 700;
}

.page-subtitle {
    font-size: 14px;
    color: #6c757d;
    margin-top: 4px;
}

.settings-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
}

.settings-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    padding: 24px;
}

.card-title {
    font-size: 16px;
    font-weight: 600;
    color: #212529;
    margin-bottom: 8px;
}

.card-desc {
    font-size: 13px;
    color: #6c757d;
    margin-bottom: 16px;
}

.form-textarea {
    width: 100%;
    min-height: 300px;
    padding: 12px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    font-size: 14px;
    line-height: 1.6;
    resize: vertical;
}

.form-textarea:focus {
    outline: none;
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}

.form-actions {
    margin-top: 16px;
    display: flex;
    justify-content: flex-end;
}

.alert {
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 20px;
    font-size: 14px;
}

.alert-success {
    background: #d1e7dd;
    color: #0f5132;
}

.alert-error {
    background: #f8d7da;
    color: #842029;
}

.helper-text {
    font-size: 12px;
    color: #6c757d;
    margin-top: 8px;
}

@media (max-width: 992px) {
    .settings-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="settings-container">
    <div class="page-header">
        <div>
            <div class="page-title">국제물류 설정</div>
            <div class="page-subtitle">국가/지역 목록 관리</div>
        </div>
        <a href="<?= CRM_URL ?>/pages/international/dashboard.php" class="btn btn-secondary">&larr; 대시보드로</a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>"><?= h($message) ?></div>
    <?php endif; ?>

    <div class="settings-grid">
        <!-- 국가 목록 관리 -->
        <div class="settings-card">
            <div class="card-title">국가 목록</div>
            <div class="card-desc">바이어 등록 시 선택할 수 있는 국가 목록입니다. 한 줄에 하나씩 입력하세요.</div>

            <form method="POST">
                <input type="hidden" name="action" value="update_countries">
                <textarea name="countries" class="form-textarea" placeholder="국가명을 한 줄에 하나씩 입력하세요"><?= h(implode("\n", $countries)) ?></textarea>
                <div class="helper-text">총 <?= count($countries) ?>개 국가</div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">국가 목록 저장</button>
                </div>
            </form>
        </div>

        <!-- 지역 목록 관리 -->
        <div class="settings-card">
            <div class="card-title">지역 목록 (실적 차트용)</div>
            <div class="card-desc">성과 차트에서 사용하는 지역 목록입니다. 한 줄에 하나씩 입력하세요.</div>

            <form method="POST">
                <input type="hidden" name="action" value="update_regions">
                <textarea name="regions" class="form-textarea" placeholder="지역명을 한 줄에 하나씩 입력하세요"><?= h(implode("\n", $regions)) ?></textarea>
                <div class="helper-text">총 <?= count($regions) ?>개 지역</div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">지역 목록 저장</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
