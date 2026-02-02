<?php
/**
 * 국제물류 개인실적 등록
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pageTitle = '개인실적 등록';
$pageSubtitle = '국제물류 월별 개인 실적 등록';

$pdo = getDB();

// 사용자 목록
$users = [];
try {
    // 테이블 존재 확인 후 조회
    $stmt = $pdo->query("SHOW TABLES LIKE '" . CRM_USERS_TABLE . "'");
    if ($stmt->fetch()) {
        // is_active 컬럼 존재 여부 확인
        $stmt = $pdo->query("SHOW COLUMNS FROM " . CRM_USERS_TABLE . " LIKE 'is_active'");
        if ($stmt->fetch()) {
            $stmt = $pdo->query("SELECT id, name FROM " . CRM_USERS_TABLE . " WHERE is_active = 1 ORDER BY name");
        } else {
            $stmt = $pdo->query("SELECT id, name FROM " . CRM_USERS_TABLE . " ORDER BY name");
        }
        $users = $stmt->fetchAll();
    }
} catch (Exception $e) {
    // 오류 시 빈 배열 유지
}

// 지역/항로 목록
$routes = ['동남아', '중국', '일본', '미주', '유럽', '중앙아시아', '중동', '아프리카', '기타'];

// POST 처리
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrfToken) {
        $message = 'CSRF 토큰이 유효하지 않습니다.';
        $messageType = 'error';
    } else {
        // user_id 처리 - 빈 값이면 현재 사용자 ID 사용
        $userId = !empty($_POST['user_id']) ? intval($_POST['user_id']) : ($currentUser['crm_user_id'] ?? 0);

        $data = [
            'user_id' => $userId,
            'period_year' => intval($_POST['year'] ?? date('Y')),
            'period_month' => intval($_POST['month'] ?? date('n')),
            'region' => $_POST['route'] ?? '',
            'target_count' => intval($_POST['target'] ?? 0),
            'actual_count' => intval($_POST['actual'] ?? 0),
            'notes' => trim($_POST['notes'] ?? '')
        ];

        if (empty($data['user_id'])) {
            $message = '담당자를 선택해주세요.';
            $messageType = 'error';
        } elseif (empty($data['region'])) {
            $message = '항로를 선택해주세요.';
            $messageType = 'error';
        } else {
            try {
                // 테이블 존재 확인
                $tableExists = $pdo->query("SHOW TABLES LIKE '" . CRM_INTL_PERSONAL_PERFORMANCE_TABLE . "'")->fetch();

                // 컬럼명 매핑 (기존 테이블 구조 확인)
                $yearCol = 'period_year';
                $monthCol = 'period_month';
                $targetCol = 'target_count';
                $actualCol = 'actual_count';

                if (!$tableExists) {
                    // 테이블 생성
                    $pdo->exec("CREATE TABLE " . CRM_INTL_PERSONAL_PERFORMANCE_TABLE . " (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        year INT NOT NULL,
                        month INT NOT NULL,
                        region VARCHAR(50) NOT NULL,
                        target INT DEFAULT 0,
                        actual INT DEFAULT 0,
                        notes TEXT,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        updated_at DATETIME DEFAULT NULL,
                        INDEX idx_user (user_id),
                        INDEX idx_period (year, month),
                        INDEX idx_region (region)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                    $yearCol = 'year';
                    $monthCol = 'month';
                    $targetCol = 'target';
                    $actualCol = 'actual';
                } else {
                    // 테이블이 존재하면 컬럼 구조 확인
                    $columns = [];
                    $colResult = $pdo->query("SHOW COLUMNS FROM " . CRM_INTL_PERSONAL_PERFORMANCE_TABLE);
                    while ($col = $colResult->fetch()) {
                        $columns[] = $col['Field'];
                    }

                    // 컬럼명 매핑 (기존 구조에 맞춤)
                    $yearCol = in_array('year', $columns) ? 'year' : 'period_year';
                    $monthCol = in_array('month', $columns) ? 'month' : 'period_month';
                    $targetCol = in_array('target', $columns) ? 'target' : 'target_count';
                    $actualCol = in_array('actual', $columns) ? 'actual' : 'actual_count';

                    // 누락된 컬럼 추가
                    if (!in_array('year', $columns) && !in_array('period_year', $columns)) {
                        $pdo->exec("ALTER TABLE " . CRM_INTL_PERSONAL_PERFORMANCE_TABLE . " ADD COLUMN year INT NOT NULL DEFAULT " . date('Y'));
                        $yearCol = 'year';
                    }
                    if (!in_array('month', $columns) && !in_array('period_month', $columns)) {
                        $pdo->exec("ALTER TABLE " . CRM_INTL_PERSONAL_PERFORMANCE_TABLE . " ADD COLUMN month INT NOT NULL DEFAULT " . date('n'));
                        $monthCol = 'month';
                    }
                    if (!in_array('region', $columns)) {
                        $pdo->exec("ALTER TABLE " . CRM_INTL_PERSONAL_PERFORMANCE_TABLE . " ADD COLUMN region VARCHAR(50) NOT NULL DEFAULT ''");
                    }
                    if (!in_array('target', $columns) && !in_array('target_count', $columns)) {
                        $pdo->exec("ALTER TABLE " . CRM_INTL_PERSONAL_PERFORMANCE_TABLE . " ADD COLUMN target INT DEFAULT 0");
                        $targetCol = 'target';
                    }
                    if (!in_array('actual', $columns) && !in_array('actual_count', $columns)) {
                        $pdo->exec("ALTER TABLE " . CRM_INTL_PERSONAL_PERFORMANCE_TABLE . " ADD COLUMN actual INT DEFAULT 0");
                        $actualCol = 'actual';
                    }
                    if (!in_array('user_id', $columns)) {
                        $pdo->exec("ALTER TABLE " . CRM_INTL_PERSONAL_PERFORMANCE_TABLE . " ADD COLUMN user_id INT NOT NULL DEFAULT 0");
                    }
                    if (!in_array('notes', $columns)) {
                        $pdo->exec("ALTER TABLE " . CRM_INTL_PERSONAL_PERFORMANCE_TABLE . " ADD COLUMN notes TEXT");
                    }
                    if (!in_array('created_at', $columns)) {
                        $pdo->exec("ALTER TABLE " . CRM_INTL_PERSONAL_PERFORMANCE_TABLE . " ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
                    }
                    if (!in_array('updated_at', $columns)) {
                        $pdo->exec("ALTER TABLE " . CRM_INTL_PERSONAL_PERFORMANCE_TABLE . " ADD COLUMN updated_at DATETIME DEFAULT NULL");
                    }
                }

                // 기존 데이터 확인
                $stmt = $pdo->prepare("SELECT id FROM " . CRM_INTL_PERSONAL_PERFORMANCE_TABLE . "
                    WHERE user_id = ? AND {$yearCol} = ? AND {$monthCol} = ? AND region = ?");
                $stmt->execute([$data['user_id'], $data['period_year'], $data['period_month'], $data['region']]);
                $existing = $stmt->fetch();

                if ($existing) {
                    $stmt = $pdo->prepare("UPDATE " . CRM_INTL_PERSONAL_PERFORMANCE_TABLE . "
                        SET {$targetCol} = ?, {$actualCol} = ?, notes = ?, updated_at = NOW()
                        WHERE id = ?");
                    $stmt->execute([$data['target_count'], $data['actual_count'], $data['notes'], $existing['id']]);
                    $message = '실적이 수정되었습니다.';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO " . CRM_INTL_PERSONAL_PERFORMANCE_TABLE . "
                        (user_id, {$yearCol}, {$monthCol}, region, {$targetCol}, {$actualCol}, notes, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([
                        $data['user_id'],
                        $data['period_year'],
                        $data['period_month'],
                        $data['region'],
                        $data['target_count'],
                        $data['actual_count'],
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

h1 {
    font-size: 24px;
    margin-bottom: 8px;
    color: #212529;
}

.subtitle {
    color: #6c757d;
    font-size: 14px;
    margin-bottom: 32px;
}

.form-group { margin-bottom: 20px; }

label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #212529;
    font-size: 14px;
}

input, select, textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    font-size: 14px;
    font-family: inherit;
}

input:focus, select:focus, textarea:focus {
    outline: none;
    border-color: #0d6efd;
}

textarea {
    resize: vertical;
    min-height: 80px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

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
    text-align: center;
}

.btn-cancel {
    background: #f8f9fa;
    color: #495057;
    border: 1px solid #dee2e6;
}

.btn-submit {
    background: #0d6efd;
    color: white;
}

.btn-submit:hover { background: #0b5ed7; }

.alert {
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 24px;
}

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

    <h1>개인실적 등록</h1>
    <p class="subtitle">국제물류 월별 개인 실적 등록</p>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <div class="form-row">
            <div class="form-group">
                <label for="year">년도</label>
                <select id="year" name="year">
                    <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                        <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?>년</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="month">월</label>
                <select id="month" name="month">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $m == date('n') ? 'selected' : '' ?>><?= $m ?>월</option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="user_id">담당자</label>
                <select id="user_id" name="user_id">
                    <option value="">본인 (<?= h($currentUser['name'] ?? '') ?>)</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id'] ?>"><?= h($user['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="route">항로</label>
                <select id="route" name="route" required>
                    <option value="">선택</option>
                    <?php foreach ($routes as $route): ?>
                        <option value="<?= $route ?>"><?= $route ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="target">목표 (건)</label>
                <input type="number" id="target" name="target" placeholder="0" min="0">
            </div>
            <div class="form-group">
                <label for="actual">실적 (건)</label>
                <input type="number" id="actual" name="actual" placeholder="0" min="0">
            </div>
        </div>

        <div class="form-group">
            <label for="notes">비고</label>
            <textarea id="notes" name="notes" placeholder="메모"></textarea>
        </div>

        <div class="btn-group">
            <a href="performance_chart.php" class="btn btn-cancel">취소</a>
            <button type="submit" class="btn btn-submit">등록</button>
        </div>
    </form>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
