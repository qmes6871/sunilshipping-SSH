<?php
/**
 * 국제물류 성과 차트 전체보기
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pageTitle = '성과 차트 전체보기';
$pageSubtitle = '국제물류 부서별 실적 현황';

$pdo = getDB();

// 필터
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('n');
// type 또는 period_type 파라미터 지원
$periodType = $_GET['period_type'] ?? $_GET['type'] ?? 'monthly';

// 월별 성과 데이터 조회 (대시보드와 동일한 로직 - 개인실적 테이블에서 목표/실적 조회)
$monthlyData = [];
$targets = [];
$performanceData = [];
$regions = []; // DB에서 실제 사용된 region을 수집

try {
    // 개인실적 테이블에서 지역별 목표/실적 합계 조회
    $tableCheck = $pdo->query("SHOW TABLES LIKE '" . CRM_INTL_PERSONAL_PERFORMANCE_TABLE . "'");
    if ($tableCheck->fetch()) {
        // 컬럼명 확인
        $columns = [];
        $colResult = $pdo->query("SHOW COLUMNS FROM " . CRM_INTL_PERSONAL_PERFORMANCE_TABLE);
        while ($col = $colResult->fetch()) {
            $columns[] = $col['Field'];
        }
        $yearCol = in_array('year', $columns) ? 'year' : 'period_year';
        $monthCol = in_array('month', $columns) ? 'month' : 'period_month';
        $targetCol = in_array('target', $columns) ? 'target' : 'target_count';
        $actualCol = in_array('actual', $columns) ? 'actual' : 'actual_count';

        // 기간 타입에 따른 조건 분기
        if ($periodType === 'yearly') {
            $stmt = $pdo->prepare("SELECT region,
                SUM({$targetCol}) as target_total,
                SUM({$actualCol}) as actual_total
                FROM " . CRM_INTL_PERSONAL_PERFORMANCE_TABLE . "
                WHERE {$yearCol} = ?
                GROUP BY region");
            $stmt->execute([$year]);
        } else {
            $stmt = $pdo->prepare("SELECT region,
                SUM({$targetCol}) as target_total,
                SUM({$actualCol}) as actual_total
                FROM " . CRM_INTL_PERSONAL_PERFORMANCE_TABLE . "
                WHERE {$yearCol} = ? AND {$monthCol} = ?
                GROUP BY region");
            $stmt->execute([$year, $month]);
        }
        while ($row = $stmt->fetch()) {
            $region = $row['region'] ?: '미지정';
            if (!in_array($region, $regions)) {
                $regions[] = $region;
            }
            $monthlyData[$region] = [
                'target' => intval($row['target_total']),
                'actual' => intval($row['actual_total'])
            ];
            $targets[$region] = intval($row['target_total']);
            $performanceData[$region] = intval($row['actual_total']);
        }
    }
} catch (Exception $e) {
    // 오류 시 빈 배열 유지
}

// 부서실적 테이블에서도 데이터 조회하여 병합
try {
    $tableCheck = $pdo->query("SHOW TABLES LIKE '" . CRM_INTL_PERFORMANCE_TABLE . "'");
    if ($tableCheck->fetch()) {
        $columns = [];
        $colResult = $pdo->query("SHOW COLUMNS FROM " . CRM_INTL_PERFORMANCE_TABLE);
        while ($col = $colResult->fetch()) {
            $columns[] = $col['Field'];
        }
        $yearCol = in_array('year', $columns) ? 'year' : 'period_year';
        $monthCol = in_array('month', $columns) ? 'month' : 'period_month';
        // performance_count를 실적으로 사용 (target/actual 값이 0인 경우가 많음)
        $countCol = in_array('performance_count', $columns) ? 'performance_count' : (in_array('count', $columns) ? 'count' : '0');
        // target 컬럼이 있으면 사용, 없으면 performance_count 사용
        $targetCol = in_array('target', $columns) ? "GREATEST(COALESCE(target, 0), COALESCE({$countCol}, 0))" : $countCol;
        // actual 컬럼이 0이면 performance_count를 사용
        $actualCol = "GREATEST(COALESCE(actual, 0), COALESCE({$countCol}, 0))";

        if ($periodType === 'yearly') {
            $stmt = $pdo->prepare("SELECT region,
                SUM({$targetCol}) as target_total,
                SUM({$actualCol}) as actual_total
                FROM " . CRM_INTL_PERFORMANCE_TABLE . "
                WHERE {$yearCol} = ?
                GROUP BY region");
            $stmt->execute([$year]);
        } else {
            $stmt = $pdo->prepare("SELECT region,
                SUM({$targetCol}) as target_total,
                SUM({$actualCol}) as actual_total
                FROM " . CRM_INTL_PERFORMANCE_TABLE . "
                WHERE {$yearCol} = ? AND {$monthCol} = ?
                GROUP BY region");
            $stmt->execute([$year, $month]);
        }
        while ($row = $stmt->fetch()) {
            $region = $row['region'] ?: '미지정';
            if (!empty($region)) {
                // region 배열에 추가
                if (!in_array($region, $regions)) {
                    $regions[] = $region;
                }
                // 기존 데이터에 추가
                if (!isset($monthlyData[$region])) {
                    $monthlyData[$region] = ['target' => 0, 'actual' => 0];
                }
                $monthlyData[$region]['target'] += intval($row['target_total']);
                $monthlyData[$region]['actual'] += intval($row['actual_total']);
                $targets[$region] = ($targets[$region] ?? 0) + intval($row['target_total']);
                $performanceData[$region] = ($performanceData[$region] ?? 0) + intval($row['actual_total']);
            }
        }
    }
} catch (Exception $e) {
    // 오류 시 무시
}

// 데이터가 없으면 기본 지역 목록 사용
if (empty($regions)) {
    $regions = getIntlRegions();
}

// 지역 목록에 있지만 데이터가 없는 경우 기본값 설정
foreach ($regions as $region) {
    if (!isset($targets[$region])) {
        $targets[$region] = 0;
    }
    if (!isset($performanceData[$region])) {
        $performanceData[$region] = 0;
    }
    if (!isset($monthlyData[$region])) {
        $monthlyData[$region] = ['target' => 0, 'actual' => 0];
    }
}

// 개인별 성과 조회
$personalData = [];
try {
    // 테이블 존재 확인
    $tableCheck = $pdo->query("SHOW TABLES LIKE '" . CRM_INTL_PERSONAL_PERFORMANCE_TABLE . "'");
    if ($tableCheck->fetch()) {
        // 컬럼명 확인
        $columns = [];
        $colResult = $pdo->query("SHOW COLUMNS FROM " . CRM_INTL_PERSONAL_PERFORMANCE_TABLE);
        while ($col = $colResult->fetch()) {
            $columns[] = $col['Field'];
        }
        $yearCol = in_array('year', $columns) ? 'year' : 'period_year';
        $monthCol = in_array('month', $columns) ? 'month' : 'period_month';
        $targetCol = in_array('target', $columns) ? 'target' : 'target_count';
        $actualCol = in_array('actual', $columns) ? 'actual' : 'actual_count';

        $stmt = $pdo->prepare("SELECT p.*, u.name as user_name, u.department,
            p.{$targetCol} as target_count, p.{$actualCol} as actual_count
            FROM " . CRM_INTL_PERSONAL_PERFORMANCE_TABLE . " p
            LEFT JOIN " . CRM_USERS_TABLE . " u ON p.user_id = u.id
            WHERE p.{$yearCol} = ? AND p.{$monthCol} = ?
            ORDER BY p.{$actualCol} DESC
            LIMIT 10");
        $stmt->execute([$year, $month]);
        $personalData = $stmt->fetchAll();
    }
} catch (Exception $e) {
    // 오류 시 빈 배열 유지
}

// 통계 계산
$totalCount = array_sum($performanceData);
$totalTarget = array_sum($targets);
$achievementRate = $totalTarget > 0 ? round(($totalCount / $totalTarget) * 100) : 0;

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

.container { max-width: 1400px; margin: 0 auto; padding: 20px; }

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

.header-right {
    display: flex;
    gap: 12px;
}

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

.filter-left, .filter-right {
    display: flex;
    gap: 12px;
    align-items: center;
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

/* 탭 메뉴 */
.tab-menu {
    display: flex;
    gap: 8px;
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 20px;
}

.tab-btn {
    padding: 12px 24px;
    border: none;
    background: none;
    font-size: 14px;
    font-weight: 500;
    color: #6c757d;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s;
}

.tab-btn:hover { color: #212529; }

.tab-btn.active {
    color: #0d6efd;
    border-bottom-color: #0d6efd;
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
}
</style>

<div class="container">
    <!-- 페이지 헤더 -->
    <div class="page-header">
        <div class="header-left">
            <a href="dashboard.php" class="btn-back">← 뒤로가기</a>
            <div>
                <div class="page-title">성과 차트 전체보기</div>
                <div class="page-subtitle">국제물류 부서별 실적 현황</div>
            </div>
        </div>
        <div class="header-right">
            <a href="personal_performance_form.php" class="btn-register-personal">+ 개인실적 등록</a>
            <a href="performance_form.php" class="btn-register">+ 부서실적 등록</a>
        </div>
    </div>

    <!-- 통계 요약 -->
    <div class="stats-summary" style="grid-template-columns: repeat(2, 1fr);">
        <div class="stat-box">
            <div class="stat-label">총 수출 건수</div>
            <div class="stat-value"><?= number_format($totalCount) ?>건</div>
            <div class="stat-change up">▲ 12.5% 전월 대비</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">목표 달성률</div>
            <div class="stat-value"><?= $achievementRate ?>%</div>
            <div class="stat-change up">▲ 5.2% 전월 대비</div>
        </div>
    </div>

    <!-- 차트 카드 -->
    <div class="card">
        <!-- 차트 보기 제목 -->
        <div class="card-header" style="border-bottom: none; margin-bottom: 0; padding-bottom: 0;">
            <div class="card-title" style="margin-bottom: 0;">차트 보기</div>
        </div>

        <!-- 필터 바 -->
        <form class="filter-bar" method="GET" id="filterForm">
            <input type="hidden" name="period_type" id="periodTypeInput" value="<?= h($periodType) ?>">
            <div class="filter-left">
                <button type="button" class="filter-btn <?= $periodType === 'monthly' ? 'active' : '' ?>" data-period="monthly">월간</button>
                <button type="button" class="filter-btn <?= $periodType === 'yearly' ? 'active' : '' ?>" data-period="yearly">연간</button>
            </div>
            <div class="filter-right">
                <select class="filter-select" name="region">
                    <option value="">전체 부서</option>
                    <?php foreach ($regions as $region): ?>
                        <option value="<?= $region ?>"><?= $region ?></option>
                    <?php endforeach; ?>
                </select>
                <select class="filter-select" name="year" onchange="this.form.submit()">
                    <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                        <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?>년</option>
                    <?php endfor; ?>
                </select>
                <select class="filter-select" name="month" onchange="this.form.submit()">
                    <?php for ($m = 12; $m >= 1; $m--): ?>
                        <option value="<?= $m ?>" <?= $month == $m ? 'selected' : '' ?>><?= $m ?>월</option>
                    <?php endfor; ?>
                </select>
            </div>
        </form>

        <!-- 차트 영역 -->
        <?php
        $periodLabels = ['daily' => '일간', 'weekly' => '주간', 'monthly' => '월간', 'quarterly' => '분기', 'yearly' => '연간'];
        $periodLabel = $periodLabels[$periodType] ?? '월간';
        ?>
        <div class="chart-container">
            <div class="chart-header">
                <div class="chart-title">부서별 <?= $periodLabel ?> 실적 (<?= $year ?>년<?= $periodType !== 'yearly' ? " {$month}월" : '' ?>)</div>
            </div>

            <!-- 막대 그래프 -->
            <div class="bar-chart">
                <?php if (empty($performanceData) || array_sum($performanceData) === 0): ?>
                    <div style="text-align: center; padding: 60px 20px; color: #6c757d;">
                        <div style="font-size: 48px; margin-bottom: 16px;">📊</div>
                        <div style="font-size: 16px; font-weight: 500; margin-bottom: 8px;">데이터 없음</div>
                        <div style="font-size: 14px;">선택한 기간에 등록된 실적이 없습니다.</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($regions as $region):
                        $actual = $performanceData[$region] ?? 0;
                        $target = $targets[$region] ?? 100;
                        $percent = $target > 0 ? round(($actual / $target) * 100) : 0;
                        $percent = min($percent, 100);
                    ?>
                    <div class="bar-item">
                        <div class="bar-label">
                            <span class="bar-name"><?= $region ?></span>
                            <span class="bar-value"><?= number_format($actual) ?>건 / 목표 <?= number_format($target) ?>건</span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: <?= $percent ?>%;"><?= $percent ?>%</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 상세 데이터 테이블 (숨김 처리) -->
    <div class="card" style="display: none;">
        <div class="card-header">
            <div class="card-title">상세 실적 데이터</div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>부서명</th>
                    <th>목표</th>
                    <th>실적</th>
                    <th>달성률</th>
                    <th>평가</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($regions as $region):
                    $actual = $performanceData[$region] ?? 0;
                    $target = $targets[$region] ?? 100;
                    $percent = $target > 0 ? round(($actual / $target) * 100) : 0;

                    if ($percent >= 90) { $badge = 'excellent'; $label = '우수'; }
                    elseif ($percent >= 75) { $badge = 'good'; $label = '양호'; }
                    elseif ($percent >= 60) { $badge = 'fair'; $label = '보통'; }
                    else { $badge = 'poor'; $label = '개선 필요'; }
                ?>
                <tr>
                    <td><strong><?= $region ?></strong></td>
                    <td><?= number_format($target) ?>건</td>
                    <td><?= number_format($actual) ?>건</td>
                    <td>
                        <div class="progress-cell">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?= min($percent, 100) ?>%;"></div>
                            </div>
                            <span class="progress-text"><?= $percent ?>%</span>
                        </div>
                    </td>
                    <td><span class="status-badge <?= $badge ?>"><?= $label ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- 개인별 상세 데이터 테이블 (숨김 처리) -->
    <div class="card" style="display: none;">
        <div class="card-header">
            <div class="card-title">개인별 상세 실적 데이터</div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>순위</th>
                    <th>이름</th>
                    <th>부서</th>
                    <th>목표</th>
                    <th>실적</th>
                    <th>달성률</th>
                    <th>평가</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($personalData)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 40px; color: #6c757d;">등록된 개인 실적이 없습니다.</td></tr>
                <?php else: ?>
                    <?php $rank = 1; foreach ($personalData as $person):
                        $percent = $person['target_count'] > 0 ? round(($person['actual_count'] / $person['target_count']) * 100) : 0;

                        if ($percent >= 90) { $badge = 'excellent'; $label = '우수'; }
                        elseif ($percent >= 75) { $badge = 'good'; $label = '양호'; }
                        elseif ($percent >= 60) { $badge = 'fair'; $label = '보통'; }
                        else { $badge = 'poor'; $label = '개선 필요'; }
                    ?>
                    <tr>
                        <td><strong><?= $rank++ ?></strong></td>
                        <td><?= h($person['user_name'] ?? '미지정') ?></td>
                        <td><?= h($person['department'] ?? '-') ?></td>
                        <td><?= number_format($person['target_count'] ?? 0) ?>건</td>
                        <td><?= number_format($person['actual_count'] ?? 0) ?>건</td>
                        <td>
                            <div class="progress-cell">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= min($percent, 100) ?>%;"></div>
                                </div>
                                <span class="progress-text"><?= $percent ?>%</span>
                            </div>
                        </td>
                        <td><span class="status-badge <?= $badge ?>"><?= $label ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$pageScripts = <<<'SCRIPT'
<script>
// 필터 버튼 클릭 (일간/주간/월간/분기/연간)
document.querySelectorAll('.filter-left .filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        // 활성 버튼 변경
        document.querySelectorAll('.filter-left .filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');

        // 기간 타입 설정 및 폼 제출
        const periodType = this.dataset.period;
        document.getElementById('periodTypeInput').value = periodType;
        document.getElementById('filterForm').submit();
    });
});

// 지역 필터 변경 시 자동 제출
document.querySelector('select[name="region"]').addEventListener('change', function() {
    document.getElementById('filterForm').submit();
});
</script>
SCRIPT;

include dirname(dirname(__DIR__)) . '/includes/footer.php';
?>
