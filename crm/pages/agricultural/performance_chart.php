<?php
/**
 * 농산물 성과 차트 전체보기
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pageTitle = '성과 차트 (농산물)';
$pageSubtitle = '품목별 수출 실적 현황';

$pdo = getDB();

$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('n');
$period = $_GET['period'] ?? 'monthly';

// 개인별 실적 데이터 조회
$personalData = [];
try {
    $tableCheck = $pdo->query("SHOW TABLES LIKE '" . CRM_AGRI_PERSONAL_PERFORMANCE_TABLE . "'");
    if ($tableCheck->fetch()) {
        // 컬럼명 확인
        $columns = [];
        $colResult = $pdo->query("SHOW COLUMNS FROM " . CRM_AGRI_PERSONAL_PERFORMANCE_TABLE);
        while ($col = $colResult->fetch()) {
            $columns[] = $col['Field'];
        }
        $yearCol = in_array('year', $columns) ? 'year' : 'period_year';
        $monthCol = in_array('month', $columns) ? 'month' : 'period_month';
        $rateCol = in_array('achievement_rate', $columns) ? 'achievement_rate' : 'rate';

        $periodTypeCol = in_array('period_type', $columns) ? 'period_type' : null;
        $periodCondition = $periodTypeCol ? " AND ({$periodTypeCol} = ? OR {$periodTypeCol} IS NULL)" : "";

        $stmt = $pdo->prepare("SELECT * FROM " . CRM_AGRI_PERSONAL_PERFORMANCE_TABLE . "
            WHERE {$yearCol} = ? AND ({$monthCol} = ? OR ? IN ('yearly', 'quarterly'))" . $periodCondition . "
            ORDER BY {$rateCol} DESC");
        $params = [$year, $month, $period];
        if ($periodTypeCol) $params[] = $period;
        $stmt->execute($params);
        $personalData = $stmt->fetchAll();
    }
} catch (Exception $e) {
    // 오류 시 빈 배열 유지
}

// 전체 실적 데이터 조회
$performanceData = [];
try {
    $tableCheck = $pdo->query("SHOW TABLES LIKE '" . CRM_AGRI_PERFORMANCE_TABLE . "'");
    if ($tableCheck->fetch()) {
        // 컬럼명 확인
        $columns = [];
        $colResult = $pdo->query("SHOW COLUMNS FROM " . CRM_AGRI_PERFORMANCE_TABLE);
        while ($col = $colResult->fetch()) {
            $columns[] = $col['Field'];
        }
        $yearCol = in_array('year', $columns) ? 'year' : 'period_year';
        $monthCol = in_array('month', $columns) ? 'month' : 'period_month';

        $periodTypeCol = in_array('period_type', $columns) ? 'period_type' : null;
        $periodCondition = $periodTypeCol ? " AND ({$periodTypeCol} = ? OR {$periodTypeCol} IS NULL)" : "";

        $stmt = $pdo->prepare("SELECT * FROM " . CRM_AGRI_PERFORMANCE_TABLE . "
            WHERE {$yearCol} = ? AND ({$monthCol} = ? OR ? IN ('yearly', 'quarterly'))" . $periodCondition . "
            ORDER BY id DESC");
        $params = [$year, $month, $period];
        if ($periodTypeCol) $params[] = $period;
        $stmt->execute($params);
        $performanceData = $stmt->fetchAll();
    }
} catch (Exception $e) {
    // 오류 시 빈 배열 유지
}

// 통계 계산
$totalExport = array_sum(array_column($personalData, 'actual_amount'));
$totalTarget = array_sum(array_column($personalData, 'target_amount'));
$avgAchievement = $totalTarget > 0 ? round(($totalExport / $totalTarget) * 100) : 0;

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
    transition: all 0.2s;
    text-decoration: none;
}
.btn-back:hover { background: #f8f9fa; }

.btn-register {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
    color: white;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.3s;
    box-shadow: 0 2px 8px rgba(13, 110, 253, 0.2);
    text-decoration: none;
}
.btn-register:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
}

.btn-register-personal {
    padding: 10px 20px;
    border: 1px solid #0d6efd;
    border-radius: 6px;
    background: white;
    color: #0d6efd;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.3s;
    text-decoration: none;
}
.btn-register-personal:hover {
    background: #0d6efd;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
}

.header-right { display: flex; gap: 12px; }

/* 카드 */
.card {
    background: white;
    padding: 24px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 24px;
}
.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid #e9ecef;
}
.card-title {
    font-size: 18px;
    font-weight: 600;
    color: #212529;
}

/* 통계 요약 */
.stats-summary {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
.stat-box {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    text-align: center;
}
.stat-label {
    font-size: 13px;
    color: #6c757d;
    margin-bottom: 8px;
}
.stat-value {
    font-size: 28px;
    font-weight: 700;
    color: #212529;
    margin-bottom: 4px;
}
.stat-change {
    font-size: 12px;
    font-weight: 500;
}
.stat-change.up { color: #198754; }
.stat-change.down { color: #dc3545; }

/* 필터 바 */
.filter-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.filter-left {
    display: flex;
    gap: 12px;
    align-items: center;
}
.filter-right {
    display: flex;
    gap: 12px;
}
.filter-btn {
    padding: 8px 16px;
    border: 1px solid #dee2e6;
    background: white;
    border-radius: 4px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
    color: #495057;
}
.filter-btn:hover { background: #f8f9fa; }
.filter-btn.active {
    background: #0d6efd;
    color: white;
    border-color: #0d6efd;
}
.filter-select {
    padding: 8px 16px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    font-size: 13px;
    background: white;
    cursor: pointer;
}
.filter-select:focus {
    outline: none;
    border-color: #0d6efd;
}

/* 차트 컨테이너 */
.chart-container {
    background: #f8f9fa;
    padding: 24px;
    border-radius: 6px;
    margin-bottom: 20px;
}
.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}
.chart-title {
    font-size: 16px;
    font-weight: 600;
    color: #495057;
}

/* 막대 그래프 */
.bar-chart {
    background: white;
    padding: 24px;
    border-radius: 6px;
}
.bar-item { margin-bottom: 24px; }
.bar-item:last-child { margin-bottom: 0; }
.bar-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}
.bar-name {
    font-size: 15px;
    font-weight: 500;
    color: #212529;
}
.bar-value {
    font-size: 14px;
    font-weight: 600;
    color: #0d6efd;
}
.bar-track {
    width: 100%;
    height: 32px;
    background: #e9ecef;
    border-radius: 6px;
    overflow: hidden;
    position: relative;
}
.bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #0d6efd 0%, #0dcaf0 100%);
    border-radius: 6px;
    transition: width 0.8s ease;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding-right: 12px;
    color: white;
    font-size: 13px;
    font-weight: 600;
}

/* 데이터 테이블 */
.data-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
}
.data-table thead {
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}
.data-table th {
    padding: 12px 16px;
    text-align: left;
    font-size: 13px;
    font-weight: 600;
    color: #495057;
}
.data-table th:last-child,
.data-table td:last-child { text-align: center; }
.data-table tbody tr {
    border-bottom: 1px solid #e9ecef;
    transition: background 0.2s;
}
.data-table tbody tr:hover { background: #f8f9fa; }
.data-table td {
    padding: 14px 16px;
    font-size: 14px;
}

.progress-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}
.progress-bar {
    flex: 1;
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
}
.progress-fill {
    height: 100%;
    background: #0d6efd;
    border-radius: 4px;
}
.progress-text {
    font-size: 12px;
    font-weight: 600;
    color: #495057;
    min-width: 40px;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}
.status-badge.excellent { background: #d1e7dd; color: #0f5132; }
.status-badge.good { background: #cff4fc; color: #055160; }
.status-badge.fair { background: #fff3cd; color: #664d03; }
.status-badge.poor { background: #f8d7da; color: #842029; }

/* 액션 버튼 */
.btn-action {
    padding: 4px 8px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    background: transparent;
    transition: all 0.2s;
}
.btn-action:hover { background: #f1f3f5; }
.btn-action.edit:hover { background: #e7f5ff; }
.btn-action.delete:hover { background: #ffe3e3; }

/* 모달 */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    animation: fadeIn 0.3s;
}
.modal.active {
    display: flex;
    align-items: center;
    justify-content: center;
}
.modal-content {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    animation: slideUp 0.3s;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}
.modal-header {
    background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
    color: white;
    padding: 24px 28px;
    border-radius: 12px 12px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-header h2 {
    font-size: 20px;
    margin: 0;
    font-weight: 600;
}
.close-btn {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    font-size: 28px;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
    line-height: 1;
}
.close-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}
.modal-body { padding: 28px; }
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 16px;
}
.form-group-full { margin-bottom: 16px; }
.form-group-full label,
.form-row .form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #212529;
    font-size: 14px;
}
.form-group-full input,
.form-group-full select,
.form-group-full textarea,
.form-row .form-group input,
.form-row .form-group select {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.2s;
    font-family: inherit;
}
.form-group-full textarea {
    resize: vertical;
    min-height: 80px;
}
.form-group-full input:focus,
.form-group-full select:focus,
.form-group-full textarea:focus,
.form-row .form-group input:focus,
.form-row .form-group select:focus {
    outline: none;
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}
.modal-footer {
    padding: 16px 28px 24px;
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}
.btn-cancel {
    padding: 10px 24px;
    border: 1px solid #dee2e6;
    background: white;
    border-radius: 6px;
    color: #495057;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-cancel:hover { background: #f8f9fa; }
.btn-submit {
    padding: 10px 24px;
    border: none;
    border-radius: 6px;
    background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
    color: white;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}
.btn-submit:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes slideUp {
    from { transform: translateY(50px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

/* 반응형 */
@media (max-width: 1200px) {
    .stats-summary { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
    .stats-summary { grid-template-columns: 1fr; }
    .page-header { flex-direction: column; align-items: stretch; gap: 16px; }
    .header-right { flex-direction: column; }
    .btn-register, .btn-register-personal { width: 100%; text-align: center; }
    .filter-bar { flex-direction: column; align-items: stretch; }
    .filter-left, .filter-right { flex-wrap: wrap; }
    .form-row { grid-template-columns: 1fr; }
    .modal-content { width: 95%; }
}
</style>

<div class="container">
    <!-- 페이지 헤더 -->
    <div class="page-header">
        <div class="header-left">
            <a href="dashboard.php" class="btn-back">← 뒤로가기</a>
            <div>
                <div class="page-title">성과 차트 (농산물)</div>
                <div class="page-subtitle">품목별 수출 실적 현황</div>
            </div>
        </div>
        <div class="header-right">
            <a href="personal_performance_form.php" class="btn-register-personal">+ 개인실적 등록</a>
            <button class="btn-register" onclick="openChartModal()">+ 차트 등록하기</button>
        </div>
    </div>

    <!-- 통계 요약 -->
    <div class="stats-summary" style="grid-template-columns: repeat(2, 1fr);">
        <div class="stat-box">
            <div class="stat-label">총 수출량</div>
            <div class="stat-value"><?= number_format($totalExport) ?>톤</div>
            <div class="stat-change up">▲ 18.3% 전월 대비</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">목표 달성률</div>
            <div class="stat-value"><?= $avgAchievement ?>%</div>
            <div class="stat-change up">▲ 7.5% 전월 대비</div>
        </div>
    </div>

    <!-- 차트 카드 -->
    <div class="card">
        <!-- 필터 바 -->
        <div class="filter-bar">
            <div class="filter-left">
                <button class="filter-btn <?= $period === 'daily' ? 'active' : '' ?>" onclick="changePeriod('daily')">일간</button>
                <button class="filter-btn <?= $period === 'weekly' ? 'active' : '' ?>" onclick="changePeriod('weekly')">주간</button>
                <button class="filter-btn <?= $period === 'monthly' ? 'active' : '' ?>" onclick="changePeriod('monthly')">월간</button>
                <button class="filter-btn <?= $period === 'quarterly' ? 'active' : '' ?>" onclick="changePeriod('quarterly')">분기</button>
                <button class="filter-btn <?= $period === 'yearly' ? 'active' : '' ?>" onclick="changePeriod('yearly')">연간</button>
            </div>
            <div class="filter-right">
                <select class="filter-select" id="businessFilter">
                    <option value="all">전체 사업</option>
                    <option value="agricultural">농산물 수출</option>
                    <option value="pellet">우드펠렛</option>
                    <option value="logistics">국제물류</option>
                    <option value="resource">자원개발</option>
                </select>
                <select class="filter-select" id="yearFilter" onchange="changeFilter()">
                    <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                        <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?>년</option>
                    <?php endfor; ?>
                </select>
                <select class="filter-select" id="monthFilter" onchange="changeFilter()">
                    <?php for ($m = 12; $m >= 1; $m--): ?>
                        <option value="<?= $m ?>" <?= $month == $m ? 'selected' : '' ?>><?= $m ?>월</option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>

        <!-- 차트 영역 -->
        <div class="chart-container">
            <div class="chart-header">
                <div class="chart-title">사업별 월간 실적 (<?= $year ?>년 <?= $month ?>월)</div>
            </div>

            <!-- 막대 그래프 -->
            <div class="bar-chart">
                <?php
                // 샘플 차트 데이터 (실제로는 DB에서 가져옴)
                $chartData = [
                    ['name' => '농산물 수출', 'actual' => 715, 'target' => 800, 'unit' => '톤'],
                    ['name' => '우드펠렛', 'actual' => 2450, 'target' => 2500, 'unit' => '톤'],
                    ['name' => '국제물류', 'actual' => 510, 'target' => 600, 'unit' => '건'],
                    ['name' => '자원개발', 'actual' => 168, 'target' => 200, 'unit' => '톤'],
                    ['name' => '컨설팅', 'actual' => 42, 'target' => 50, 'unit' => '건'],
                    ['name' => '유통/판매', 'actual' => 365, 'target' => 400, 'unit' => '건'],
                ];

                foreach ($chartData as $item):
                    $percentage = $item['target'] > 0 ? round(($item['actual'] / $item['target']) * 100) : 0;
                ?>
                <div class="bar-item">
                    <div class="bar-label">
                        <span class="bar-name"><?= $item['name'] ?></span>
                        <span class="bar-value"><?= number_format($item['actual']) ?><?= $item['unit'] ?> / 목표 <?= number_format($item['target']) ?><?= $item['unit'] ?></span>
                    </div>
                    <div class="bar-track">
                        <div class="bar-fill" style="width: <?= min($percentage, 100) ?>%;"><?= $percentage ?>%</div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- 개인별 상세 데이터 테이블 -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">개인별 상세 실적 데이터</div>
        </div>

        <!-- 년/월 필터 -->
        <div class="filter-bar" style="margin-bottom: 20px;">
            <div class="filter-left">
                <select class="filter-select" id="personalYearFilter" onchange="changePersonalFilter()">
                    <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                        <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?>년</option>
                    <?php endfor; ?>
                </select>
                <select class="filter-select" id="personalMonthFilter" onchange="changePersonalFilter()">
                    <option value="all">전체 월</option>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $month == $m ? 'selected' : '' ?>><?= $m ?>월</option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>순위</th>
                    <th>이름</th>
                    <th>담당품목</th>
                    <th>목표</th>
                    <th>실적</th>
                    <th>달성률</th>
                    <th>평가</th>
                    <th style="width: 100px;">관리</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($personalData)): ?>
                    <?php
                    // 샘플 데이터
                    $sampleData = [
                        ['rank' => 1, 'name' => '김민수', 'item' => '배', 'target' => 50, 'actual' => 62, 'rate' => 124],
                        ['rank' => 2, 'name' => '이서연', 'item' => '사과', 'target' => 45, 'actual' => 51, 'rate' => 113],
                        ['rank' => 3, 'name' => '박준호', 'item' => '감귤', 'target' => 60, 'actual' => 65, 'rate' => 108],
                        ['rank' => 4, 'name' => '정하은', 'item' => '딸기', 'target' => 35, 'actual' => 36, 'rate' => 103],
                        ['rank' => 5, 'name' => '최지훈', 'item' => '포도', 'target' => 40, 'actual' => 38, 'rate' => 95],
                        ['rank' => 6, 'name' => '강수빈', 'item' => '토마토', 'target' => 55, 'actual' => 50, 'rate' => 91],
                        ['rank' => 7, 'name' => '윤태영', 'item' => '파프리카', 'target' => 48, 'actual' => 42, 'rate' => 87],
                        ['rank' => 8, 'name' => '임소정', 'item' => '버섯', 'target' => 30, 'actual' => 25, 'rate' => 83],
                        ['rank' => 9, 'name' => '한재민', 'item' => '인삼', 'target' => 25, 'actual' => 19, 'rate' => 76],
                        ['rank' => 10, 'name' => '조예린', 'item' => '고추', 'target' => 42, 'actual' => 30, 'rate' => 71],
                    ];
                    foreach ($sampleData as $data):
                        $badgeClass = $data['rate'] >= 100 ? 'excellent' : ($data['rate'] >= 85 ? 'good' : ($data['rate'] >= 70 ? 'fair' : 'poor'));
                        $badgeText = $data['rate'] >= 100 ? '우수' : ($data['rate'] >= 85 ? '양호' : ($data['rate'] >= 70 ? '보통' : '미흡'));
                    ?>
                    <tr>
                        <td><strong><?= $data['rank'] ?></strong></td>
                        <td><?= $data['name'] ?></td>
                        <td><?= $data['item'] ?></td>
                        <td><?= $data['target'] ?>톤</td>
                        <td><?= $data['actual'] ?>톤</td>
                        <td>
                            <div class="progress-cell">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= min($data['rate'], 100) ?>%;"></div>
                                </div>
                                <span class="progress-text"><?= $data['rate'] ?>%</span>
                            </div>
                        </td>
                        <td><span class="status-badge <?= $badgeClass ?>"><?= $badgeText ?></span></td>
                        <td>-</td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php foreach ($personalData as $idx => $data):
                        $rate = $data['achievement_rate'] ?? 0;
                        $badgeClass = $rate >= 100 ? 'excellent' : ($rate >= 85 ? 'good' : ($rate >= 70 ? 'fair' : 'poor'));
                        $badgeText = $rate >= 100 ? '우수' : ($rate >= 85 ? '양호' : ($rate >= 70 ? '보통' : '미흡'));
                    ?>
                    <tr>
                        <td><strong><?= $idx + 1 ?></strong></td>
                        <td><?= h($data['employee_name'] ?? '') ?></td>
                        <td><?= h($data['item_name'] ?? '') ?></td>
                        <td><?= number_format($data['target_amount'] ?? 0) ?>톤</td>
                        <td><?= number_format($data['actual_amount'] ?? 0) ?>톤</td>
                        <td>
                            <div class="progress-cell">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= min($rate, 100) ?>%;"></div>
                                </div>
                                <span class="progress-text"><?= $rate ?>%</span>
                            </div>
                        </td>
                        <td><span class="status-badge <?= $badgeClass ?>"><?= $badgeText ?></span></td>
                        <td>
                            <button class="btn-action edit" onclick='editPersonalPerformance(<?= json_encode($data, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' title="수정">✏️</button>
                            <button class="btn-action delete" onclick="deletePersonalPerformance(<?= $data['id'] ?? 0 ?>)" title="삭제">🗑️</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 차트 등록/수정 모달 -->
<div id="chartModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">농산물 성과 데이터 등록</h2>
            <button class="close-btn" onclick="closeChartModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="chartForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="formId" value="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="chartYear">년도 *</label>
                        <select id="chartYear" name="year" required>
                            <option value="">선택</option>
                            <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                                <option value="<?= $y ?>"><?= $y ?>년</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="chartMonth">월 *</label>
                        <select id="chartMonth" name="month" required>
                            <option value="">선택</option>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>"><?= $m ?>월</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group-full">
                    <label for="chartBusiness">사업 *</label>
                    <select id="chartBusiness" name="business" required>
                        <option value="">선택하세요</option>
                        <option value="농산물 수출">농산물 수출</option>
                        <option value="우드펠렛">우드펠렛</option>
                        <option value="국제물류">국제물류</option>
                        <option value="자원개발">자원개발</option>
                        <option value="컨설팅">컨설팅</option>
                        <option value="유통/판매">유통/판매</option>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="chartActual">실제 수출량 (톤) *</label>
                        <input type="number" id="chartActual" name="actual" placeholder="0" min="0" step="0.1" required>
                    </div>
                    <div class="form-group">
                        <label for="chartTarget">목표 수출량 (톤) *</label>
                        <input type="number" id="chartTarget" name="target" placeholder="0" min="0" step="0.1" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="chartFreshness">신선도 유지율 (%)</label>
                        <input type="number" id="chartFreshness" name="freshness" placeholder="0" min="0" max="100" step="0.1">
                    </div>
                    <div class="form-group">
                        <label for="chartQuality">품질 적합률 (%)</label>
                        <input type="number" id="chartQuality" name="quality" placeholder="0" min="0" max="100" step="0.1">
                    </div>
                </div>

                <div class="form-group-full">
                    <label for="chartNote">비고</label>
                    <textarea id="chartNote" name="note" placeholder="추가 메모사항 (선택사항)"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-cancel" onclick="closeChartModal()">취소</button>
            <button type="button" class="btn-submit" onclick="submitChart()">등록하기</button>
        </div>
    </div>
</div>

<?php
$pageScripts = <<<SCRIPT
<script>
// 필터 버튼 클릭
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const parent = this.parentElement;
        parent.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
    });
});

function changePeriod(period) {
    const year = document.getElementById('yearFilter').value;
    const month = document.getElementById('monthFilter').value;
    location.href = '?year=' + year + '&month=' + month + '&period=' + period;
}

function changeFilter() {
    const year = document.getElementById('yearFilter').value;
    const month = document.getElementById('monthFilter').value;
    location.href = '?year=' + year + '&month=' + month;
}

function changePersonalFilter() {
    const year = document.getElementById('personalYearFilter').value;
    const month = document.getElementById('personalMonthFilter').value;
    location.href = '?year=' + year + '&month=' + month;
}

// 차트 모달 열기 (신규 등록)
function openChartModal() {
    document.getElementById('modalTitle').textContent = '농산물 성과 데이터 등록';
    document.getElementById('formAction').value = 'create';
    document.getElementById('formId').value = '';
    document.getElementById('chartForm').reset();

    const modal = document.getElementById('chartModal');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

// 차트 수정 모달 열기
function editPerformance(data) {
    document.getElementById('modalTitle').textContent = '농산물 성과 데이터 수정';
    document.getElementById('formAction').value = 'update';
    document.getElementById('formId').value = data.id;
    document.getElementById('chartYear').value = data.year || '';
    document.getElementById('chartMonth').value = data.month || '';
    document.getElementById('chartBusiness').value = data.business || '';
    document.getElementById('chartActual').value = data.actual || 0;
    document.getElementById('chartTarget').value = data.target || 0;
    document.getElementById('chartFreshness').value = data.freshness || '';
    document.getElementById('chartQuality').value = data.quality || '';
    document.getElementById('chartNote').value = data.note || '';

    const modal = document.getElementById('chartModal');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

// 개인 실적 수정
function editPersonalPerformance(data) {
    // personal_performance_form.php로 이동 (수정 모드)
    location.href = 'personal_performance_form.php?id=' + data.id;
}

// 개인 실적 삭제
async function deletePersonalPerformance(id) {
    if (!id || !confirm('정말 삭제하시겠습니까?')) return;

    try {
        const response = await apiPost(CRM_URL + '/api/agricultural/personal_performance.php', {
            action: 'delete',
            id: id
        });

        if (response.success) {
            showToast('삭제되었습니다.', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(response.message || '삭제에 실패했습니다.', 'error');
        }
    } catch (error) {
        showToast('삭제 중 오류가 발생했습니다.', 'error');
    }
}

// 성과 삭제
async function deletePerformance(id) {
    if (!id || !confirm('정말 삭제하시겠습니까?')) return;

    try {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);

        const response = await fetch(CRM_URL + '/api/agricultural/performance.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.success) {
            showToast('삭제되었습니다.', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(result.message || '삭제에 실패했습니다.', 'error');
        }
    } catch (error) {
        showToast('삭제 중 오류가 발생했습니다.', 'error');
    }
}

// 차트 모달 닫기
function closeChartModal() {
    const modal = document.getElementById('chartModal');
    modal.classList.remove('active');
    document.body.style.overflow = 'auto';
    document.getElementById('chartForm').reset();
    document.getElementById('formAction').value = 'create';
    document.getElementById('formId').value = '';
}

// 모달 외부 클릭시 닫기
window.onclick = function(event) {
    const modal = document.getElementById('chartModal');
    if (event.target === modal) {
        closeChartModal();
    }
}

// ESC 키로 모달 닫기
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modal = document.getElementById('chartModal');
        if (modal.classList.contains('active')) {
            closeChartModal();
        }
    }
});

// 폼 제출
async function submitChart() {
    const form = document.getElementById('chartForm');

    // 유효성 검사
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);

    try {
        const response = await fetch(CRM_URL + '/api/agricultural/performance.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showToast('차트 데이터가 성공적으로 등록되었습니다!', 'success');
            closeChartModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(result.message || '등록 중 오류가 발생했습니다.', 'error');
        }
    } catch (error) {
        showToast('등록 중 오류가 발생했습니다.', 'error');
    }
}
</script>
SCRIPT;

include dirname(dirname(__DIR__)) . '/includes/footer.php';
?>
