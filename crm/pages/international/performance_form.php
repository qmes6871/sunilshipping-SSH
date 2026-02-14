<?php
/**
 * 국제물류 성과 등록
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pageTitle = '성과 등록';
$pageSubtitle = '부서별 실적 데이터 입력';

$pdo = getDB();

// 지역 목록 (동적 로드)
$regions = getIntlRegions();

// 기존 성과 조회
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('n');
// type 또는 period_type 파라미터 지원
$periodType = $_GET['period_type'] ?? $_GET['type'] ?? 'monthly';

$existingData = [];
$yearCol = 'year';
$monthCol = 'month';
$countCol = 'count';
$tableColumns = [];

try {
    // 테이블이 존재할 경우에만 조회
    $stmt = $pdo->query("SHOW TABLES LIKE '" . CRM_INTL_PERFORMANCE_TABLE . "'");
    if ($stmt->fetch()) {
        // 컬럼 구조 확인
        $colResult = $pdo->query("SHOW COLUMNS FROM " . CRM_INTL_PERFORMANCE_TABLE);
        while ($col = $colResult->fetch()) {
            $tableColumns[] = $col['Field'];
        }
        $yearCol = in_array('year', $tableColumns) ? 'year' : 'period_year';
        $monthCol = in_array('month', $tableColumns) ? 'month' : 'period_month';
        $countCol = in_array('count', $tableColumns) ? 'count' : 'performance_count';

        $stmt = $pdo->prepare("SELECT *, {$countCol} as performance_count FROM " . CRM_INTL_PERFORMANCE_TABLE . "
            WHERE {$yearCol} = ? AND {$monthCol} = ? AND (period_type = ? OR period_type IS NULL OR period_type = 'monthly')
            ORDER BY region");
        $stmt->execute([$year, $month, $periodType]);
        while ($row = $stmt->fetch()) {
            $existingData[$row['region']] = $row;
        }
    }
} catch (Exception $e) {
    // 테이블이 없으면 빈 배열 유지
    error_log("Performance table error: " . $e->getMessage());
}

// POST 처리
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrfToken) {
        $message = 'CSRF 토큰이 유효하지 않습니다.';
        $messageType = 'error';
    } else {
        $postYear = $_POST['year'] ?? date('Y');
        $postMonth = $_POST['month'] ?? date('n');
        $postPeriodType = $_POST['period_type'] ?? 'monthly';
        $performances = $_POST['performance'] ?? [];

        try {
            // 테이블 존재 확인
            $tableExists = $pdo->query("SHOW TABLES LIKE '" . CRM_INTL_PERFORMANCE_TABLE . "'")->fetch();

            // 컬럼명 매핑
            $yearCol = 'period_year';
            $monthCol = 'period_month';
            $countCol = 'performance_count';
            $typeCol = 'period_type';

            if (!$tableExists) {
                // 테이블 생성
                $pdo->exec("CREATE TABLE " . CRM_INTL_PERFORMANCE_TABLE . " (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    period_type VARCHAR(20) NOT NULL DEFAULT 'monthly',
                    year INT NOT NULL,
                    month INT DEFAULT NULL,
                    region VARCHAR(50) NOT NULL,
                    count INT DEFAULT 0,
                    recorded_by INT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT NULL,
                    INDEX idx_period (year, month),
                    INDEX idx_region (region),
                    INDEX idx_type (period_type)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                $yearCol = 'year';
                $monthCol = 'month';
                $countCol = 'count';
            } else {
                // 테이블이 존재하면 컬럼 구조 확인
                $columns = [];
                $colResult = $pdo->query("SHOW COLUMNS FROM " . CRM_INTL_PERFORMANCE_TABLE);
                while ($col = $colResult->fetch()) {
                    $columns[] = $col['Field'];
                }

                // 컬럼명 매핑 (기존 구조에 맞춤)
                $yearCol = in_array('year', $columns) ? 'year' : 'period_year';
                $monthCol = in_array('month', $columns) ? 'month' : 'period_month';
                $countCol = in_array('count', $columns) ? 'count' : 'performance_count';

                // 누락된 컬럼 추가
                if (!in_array('period_type', $columns)) {
                    $pdo->exec("ALTER TABLE " . CRM_INTL_PERFORMANCE_TABLE . " ADD COLUMN period_type VARCHAR(20) NOT NULL DEFAULT 'monthly'");
                }
                if (!in_array('year', $columns) && !in_array('period_year', $columns)) {
                    $pdo->exec("ALTER TABLE " . CRM_INTL_PERFORMANCE_TABLE . " ADD COLUMN year INT NOT NULL DEFAULT " . date('Y'));
                    $yearCol = 'year';
                }
                if (!in_array('month', $columns) && !in_array('period_month', $columns)) {
                    $pdo->exec("ALTER TABLE " . CRM_INTL_PERFORMANCE_TABLE . " ADD COLUMN month INT DEFAULT " . date('n'));
                    $monthCol = 'month';
                }
                if (!in_array('region', $columns)) {
                    $pdo->exec("ALTER TABLE " . CRM_INTL_PERFORMANCE_TABLE . " ADD COLUMN region VARCHAR(50) NOT NULL DEFAULT ''");
                }
                if (!in_array('count', $columns) && !in_array('performance_count', $columns)) {
                    $pdo->exec("ALTER TABLE " . CRM_INTL_PERFORMANCE_TABLE . " ADD COLUMN count INT DEFAULT 0");
                    $countCol = 'count';
                }
                if (!in_array('recorded_by', $columns)) {
                    $pdo->exec("ALTER TABLE " . CRM_INTL_PERFORMANCE_TABLE . " ADD COLUMN recorded_by INT DEFAULT NULL");
                }
                if (!in_array('created_at', $columns)) {
                    $pdo->exec("ALTER TABLE " . CRM_INTL_PERFORMANCE_TABLE . " ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
                }
                if (!in_array('updated_at', $columns)) {
                    $pdo->exec("ALTER TABLE " . CRM_INTL_PERFORMANCE_TABLE . " ADD COLUMN updated_at DATETIME DEFAULT NULL");
                }
            }

            $pdo->beginTransaction();

            foreach ($regions as $region) {
                $count = intval($performances[$region] ?? 0);

                // 기존 데이터 확인
                $stmt = $pdo->prepare("SELECT id FROM " . CRM_INTL_PERFORMANCE_TABLE . "
                    WHERE {$yearCol} = ? AND {$monthCol} = ? AND region = ? AND period_type = ?");
                $stmt->execute([$postYear, $postMonth, $region, $postPeriodType]);
                $existing = $stmt->fetch();

                if ($existing) {
                    $stmt = $pdo->prepare("UPDATE " . CRM_INTL_PERFORMANCE_TABLE . "
                        SET {$countCol} = ? WHERE id = ?");
                    $stmt->execute([$count, $existing['id']]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO " . CRM_INTL_PERFORMANCE_TABLE . "
                        (period_type, {$yearCol}, {$monthCol}, region, {$countCol}, recorded_by, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$postPeriodType, $postYear, $postMonth, $region, $count, $currentUser['crm_user_id'] ?? null]);
                }
            }

            $pdo->commit();
            $message = '성과가 저장되었습니다.';
            $messageType = 'success';

            // 데이터 다시 조회
            $year = $postYear;
            $month = $postMonth;
            $periodType = $postPeriodType;
            $stmt = $pdo->prepare("SELECT *, {$countCol} as performance_count FROM " . CRM_INTL_PERFORMANCE_TABLE . "
                WHERE {$yearCol} = ? AND {$monthCol} = ? AND (period_type = ? OR period_type IS NULL OR period_type = 'monthly')
                ORDER BY region");
            $stmt->execute([$year, $month, $periodType]);
            $existingData = [];
            while ($row = $stmt->fetch()) {
                $existingData[$row['region']] = $row;
            }

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = '저장 중 오류가 발생했습니다: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

.container { max-width: 1200px; margin: 0 auto; padding: 20px; }

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
    transition: all 0.2s;
}

.btn-back:hover { background: #f8f9fa; }

/* 카드 */
.card {
    background: white;
    padding: 24px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 24px;
}

.card-title {
    font-size: 18px;
    font-weight: 600;
    color: #212529;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid #e9ecef;
}

/* 기간 선택 탭 */
.period-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.period-tab {
    padding: 10px 20px;
    border: 1px solid #dee2e6;
    background: white;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    color: #495057;
}

.period-tab:hover { background: #f8f9fa; }

.period-tab.active {
    background: #0d6efd;
    color: white;
    border-color: #0d6efd;
}

/* 폼 그룹 */
.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-label {
    font-size: 14px;
    font-weight: 500;
    color: #495057;
    margin-bottom: 8px;
}

.form-label .required {
    color: #dc3545;
    margin-left: 4px;
}

.form-input,
.form-select {
    padding: 10px 12px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    font-size: 14px;
    color: #212529;
    background: white;
    transition: all 0.2s;
}

.form-input:focus,
.form-select:focus {
    outline: none;
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}

/* 성과 입력 테이블 */
.performance-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.performance-table th {
    background: #f8f9fa;
    padding: 12px;
    text-align: left;
    font-size: 14px;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
}

.performance-table td {
    padding: 12px;
    border-bottom: 1px solid #e9ecef;
}

.performance-table tr:last-child td { border-bottom: none; }

.performance-table .country-name {
    font-size: 15px;
    font-weight: 500;
    color: #212529;
}

.performance-input {
    width: 100%;
    max-width: 200px;
    padding: 8px 12px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    font-size: 14px;
    text-align: right;
}

.performance-input:focus {
    outline: none;
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}

.unit-label {
    display: inline-block;
    margin-left: 8px;
    color: #6c757d;
    font-size: 14px;
}

/* 버튼 영역 */
.button-group {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.btn-cancel {
    background: #6c757d;
    color: white;
}

.btn-cancel:hover { background: #5c636a; }

.btn-save {
    background: #0d6efd;
    color: white;
}

.btn-save:hover {
    background: #0b5ed7;
    transform: translateY(-1px);
}

/* 도움말 텍스트 */
.help-text {
    font-size: 13px;
    color: #6c757d;
    margin-top: 4px;
}

/* 알림 */
.alert {
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 24px;
}

.alert-success { background: #d1e7dd; color: #0f5132; }
.alert-error { background: #f8d7da; color: #842029; }

/* 반응형 */
@media (max-width: 768px) {
    .form-row { grid-template-columns: 1fr; }
    .performance-table { font-size: 13px; }
    .performance-input { max-width: 150px; }
    .button-group { flex-direction: column; }
    .btn { width: 100%; text-align: center; }
    .page-header { flex-direction: column; align-items: flex-start; gap: 16px; }
}
</style>

<div class="container">
    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>"><?= h($message) ?></div>
    <?php endif; ?>

    <!-- 페이지 헤더 -->
    <div class="page-header">
        <div class="header-left">
            <a href="<?= CRM_URL ?>/pages/international/dashboard.php" class="btn-back">← 뒤로가기</a>
            <div>
                <div class="page-title">성과 등록</div>
                <div class="page-subtitle">부서별 실적 데이터 입력</div>
            </div>
        </div>
    </div>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="period_type" id="periodTypeInput" value="<?= h($periodType) ?>">

        <!-- 기본 정보 카드 -->
        <div class="card">
            <div class="card-title">기간 설정</div>

            <!-- 기간 선택 탭 -->
            <div class="period-tabs">
                <button type="button" class="period-tab <?= $periodType === 'daily' ? 'active' : '' ?>" data-period="daily">일간</button>
                <button type="button" class="period-tab <?= $periodType === 'weekly' ? 'active' : '' ?>" data-period="weekly">주간</button>
                <button type="button" class="period-tab <?= $periodType === 'monthly' ? 'active' : '' ?>" data-period="monthly">월간</button>
                <button type="button" class="period-tab <?= $periodType === 'quarterly' ? 'active' : '' ?>" data-period="quarterly">분기</button>
                <button type="button" class="period-tab <?= $periodType === 'yearly' ? 'active' : '' ?>" data-period="yearly">연간</button>
            </div>

            <!-- 날짜 선택 -->
            <div class="form-row" id="dateInputArea">
                <div class="form-group">
                    <label class="form-label">
                        기준 년도<span class="required">*</span>
                    </label>
                    <input type="number" class="form-input" name="year" id="year" placeholder="2025" value="<?= h($year) ?>" min="2020" max="2030">
                </div>
                <div class="form-group" id="monthGroup" style="<?= in_array($periodType, ['yearly']) ? 'display:none;' : '' ?>">
                    <label class="form-label">
                        기준 월<span class="required">*</span>
                    </label>
                    <select class="form-select" name="month" id="month">
                        <option value="">월 선택</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $month == $m ? 'selected' : '' ?>><?= $m ?>월</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group" id="quarterGroup" style="<?= $periodType !== 'quarterly' ? 'display:none;' : '' ?>">
                    <label class="form-label">
                        분기<span class="required">*</span>
                    </label>
                    <select class="form-select" id="quarter">
                        <option value="">분기 선택</option>
                        <option value="1">1분기 (1-3월)</option>
                        <option value="2">2분기 (4-6월)</option>
                        <option value="3">3분기 (7-9월)</option>
                        <option value="4">4분기 (10-12월)</option>
                    </select>
                </div>
                <div class="form-group" id="weekGroup" style="<?= $periodType !== 'weekly' ? 'display:none;' : '' ?>">
                    <label class="form-label">
                        주차<span class="required">*</span>
                    </label>
                    <input type="number" class="form-input" id="week" placeholder="1-52" min="1" max="52">
                </div>
                <div class="form-group" id="dayGroup" style="<?= $periodType !== 'daily' ? 'display:none;' : '' ?>">
                    <label class="form-label">
                        일자<span class="required">*</span>
                    </label>
                    <input type="date" class="form-input" id="date" value="<?= date('Y-m-d') ?>">
                </div>
            </div>
        </div>

        <!-- 성과 입력 카드 -->
        <div class="card">
            <div class="card-title">부서별 성과 입력</div>
            <div class="help-text">각 부서의 실적을 건수로 입력해주세요.</div>

            <table class="performance-table">
                <thead>
                    <tr>
                        <th style="width: 40%;">부서명</th>
                        <th style="width: 60%;">실적 (건수)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($regions as $region):
                        $value = $existingData[$region]['performance_count'] ?? 0;
                    ?>
                        <tr>
                            <td class="country-name"><?= h($region) ?></td>
                            <td>
                                <input type="number" class="performance-input" name="performance[<?= h($region) ?>]"
                                       placeholder="0" min="0" value="<?= $value ?>" data-country="<?= h($region) ?>">
                                <span class="unit-label">건</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- 버튼 영역 -->
            <div class="button-group">
                <a href="<?= CRM_URL ?>/pages/international/dashboard.php" class="btn btn-cancel">취소</a>
                <button type="submit" class="btn btn-save">저장</button>
            </div>
        </div>
    </form>
</div>

<?php
$pageScripts = <<<SCRIPT
<script>
// 폼 제출 전 처리 - 빈 값을 0으로 변환
document.querySelector('form').addEventListener('submit', function(e) {
    const inputs = document.querySelectorAll('.performance-input');
    inputs.forEach(function(input) {
        // 빈 값이거나 NaN인 경우 0으로 설정
        if (input.value === '' || input.value === null || isNaN(input.value)) {
            input.value = 0;
        }
    });
    // 0값도 정상적으로 제출 (validation 없음)
    return true;
});

// 기간 탭 전환
const periodTabs = document.querySelectorAll('.period-tab');
const monthGroup = document.getElementById('monthGroup');
const quarterGroup = document.getElementById('quarterGroup');
const weekGroup = document.getElementById('weekGroup');
const dayGroup = document.getElementById('dayGroup');
const periodTypeInput = document.getElementById('periodTypeInput');

periodTabs.forEach(tab => {
    tab.addEventListener('click', function() {
        // 활성 탭 변경
        periodTabs.forEach(t => t.classList.remove('active'));
        this.classList.add('active');

        // 기간별 입력 필드 표시/숨김
        const period = this.dataset.period;
        periodTypeInput.value = period;

        monthGroup.style.display = 'none';
        quarterGroup.style.display = 'none';
        weekGroup.style.display = 'none';
        dayGroup.style.display = 'none';

        switch(period) {
            case 'daily':
                dayGroup.style.display = 'flex';
                break;
            case 'weekly':
                monthGroup.style.display = 'flex';
                weekGroup.style.display = 'flex';
                break;
            case 'monthly':
                monthGroup.style.display = 'flex';
                break;
            case 'quarterly':
                quarterGroup.style.display = 'flex';
                break;
            case 'yearly':
                // 연간은 년도만 필요
                break;
        }
    });
});
</script>
SCRIPT;

include dirname(dirname(__DIR__)) . '/includes/footer.php';
?>
