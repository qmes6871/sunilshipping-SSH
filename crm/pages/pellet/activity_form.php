<?php
/**
 * 우드펠렛 활동 작성
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pdo = getDB();

$traderId = $_GET['trader_id'] ?? null;
$trader = null;

if ($traderId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM " . CRM_PELLET_TRADERS_TABLE . " WHERE id = ?");
        $stmt->execute([$traderId]);
        $trader = $stmt->fetch();
    } catch (Exception $e) {}
}

$pageTitle = '활동 작성';
$pageSubtitle = '우드펠렛 거래처 활동 기록';

// POST 처리
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrfToken) {
        $message = 'CSRF 토큰이 유효하지 않습니다.';
        $messageType = 'error';
    } else {
        $data = [
            'trader_id' => $_POST['trader_id'] ?? $traderId,
            'activity_date' => $_POST['activity_date'] ?? date('Y-m-d'),
            'activity_type' => $_POST['activity_type'] ?? '',
            'meeting_purpose' => trim($_POST['meeting_purpose'] ?? ''),
            'content' => trim($_POST['content'] ?? ''),
            'result' => trim($_POST['result'] ?? ''),
            'followup' => trim($_POST['followup'] ?? ''),
            'meeting_points' => trim($_POST['meeting_points'] ?? ''),
            'next_action' => trim($_POST['next_action'] ?? ''),
            'proposal_price' => trim($_POST['proposal_price'] ?? ''),
            'proposal_conditions' => trim($_POST['proposal_conditions'] ?? ''),
            'validity_period' => $_POST['validity_period'] ?? null,
            'quantity' => trim($_POST['quantity'] ?? ''),
            'unit_price' => trim($_POST['unit_price'] ?? ''),
            'delivery_date' => $_POST['delivery_date'] ?? null,
            'payment' => trim($_POST['payment'] ?? ''),
            'shipping' => trim($_POST['shipping'] ?? '')
        ];

        if (empty($data['trader_id'])) {
            $message = '거래처를 선택해주세요.';
            $messageType = 'error';
        } elseif (empty($data['activity_type'])) {
            $message = '활동 유형을 선택해주세요.';
            $messageType = 'error';
        } else {
            try {
                // 활동 테이블 생성 (없으면)
                $pdo->exec("CREATE TABLE IF NOT EXISTS crm_pellet_activities (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    trader_id INT NOT NULL,
                    activity_date DATE NOT NULL,
                    activity_type VARCHAR(50) NOT NULL,
                    description TEXT,
                    meeting_purpose TEXT,
                    result TEXT,
                    followup TEXT,
                    amount DECIMAL(15,2) DEFAULT NULL,
                    details JSON,
                    created_by INT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT NULL,
                    INDEX idx_trader (trader_id),
                    INDEX idx_date (activity_date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                // 활동 유형별 상세 정보 JSON
                $details = [];
                if ($data['activity_type'] === 'sales') {
                    $details = ['meeting_points' => $data['meeting_points'], 'next_action' => $data['next_action']];
                } elseif ($data['activity_type'] === 'quotation') {
                    $details = ['proposal_price' => $data['proposal_price'], 'proposal_conditions' => $data['proposal_conditions'], 'validity_period' => $data['validity_period']];
                } elseif ($data['activity_type'] === 'contract') {
                    $details = ['quantity' => $data['quantity'], 'unit_price' => $data['unit_price'], 'delivery_date' => $data['delivery_date'], 'payment' => $data['payment'], 'shipping' => $data['shipping']];
                }

                // 활동 유형 한글 변환
                $activityTypes = [
                    'sales' => '영업활동',
                    'meeting' => '미팅',
                    'contract' => '계약',
                    'quotation' => '견적',
                    'other' => '기타'
                ];
                $activityTypeKr = $activityTypes[$data['activity_type']] ?? $data['activity_type'];

                // 설명 텍스트 조합
                $description = '';
                if (!empty($data['meeting_purpose'])) {
                    $description .= $data['meeting_purpose'];
                }
                if (!empty($data['content'])) {
                    $description .= ($description ? "\n" : "") . $data['content'];
                }

                $stmt = $pdo->prepare("INSERT INTO crm_pellet_activities
                    (trader_id, activity_date, activity_type, description, meeting_purpose, result, followup, details, created_by, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

                $stmt->execute([
                    $data['trader_id'],
                    $data['activity_date'],
                    $activityTypeKr,
                    $description,
                    $data['meeting_purpose'],
                    $data['result'],
                    $data['followup'],
                    json_encode($details, JSON_UNESCAPED_UNICODE),
                    $currentUser['crm_user_id']
                ]);

                header('Location: trader_detail.php?id=' . $data['trader_id']);
                exit;
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

.container { max-width: 800px; margin: 0 auto; padding: 20px; }

.page-header { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; }
.btn-back { padding: 8px 16px; border: 1px solid #dee2e6; border-radius: 4px; background: white; color: #495057; cursor: pointer; font-size: 14px; text-decoration: none; }
.btn-back:hover { background: #f8f9fa; }
.page-title { font-size: 24px; font-weight: 600; color: #212529; }

.card { background: white; padding: 24px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
.section-title { font-size: 16px; font-weight: 600; color: #fd7e14; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid #e9ecef; }

.form-group { margin-bottom: 24px; }
.form-label { display: block; font-size: 14px; font-weight: 500; color: #495057; margin-bottom: 8px; }
.form-input, .form-select, .form-textarea {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    font-size: 14px;
    color: #212529;
    background: white;
}
.form-input:focus, .form-select:focus, .form-textarea:focus {
    outline: none;
    border-color: #fd7e14;
    box-shadow: 0 0 0 3px rgba(253, 126, 20, 0.1);
}
.form-textarea { min-height: 120px; resize: vertical; font-family: inherit; line-height: 1.6; }

.button-group { display: flex; gap: 12px; margin-top: 32px; }
.btn { flex: 1; padding: 14px 24px; border: none; border-radius: 6px; font-size: 15px; font-weight: 500; cursor: pointer; text-align: center; text-decoration: none; }
.btn-cancel { background: #f8f9fa; color: #495057; border: 1px solid #dee2e6; }
.btn-cancel:hover { background: #e9ecef; }
.btn-save { background: #fd7e14; color: white; }
.btn-save:hover { background: #e8590c; }

.hidden { display: none; }

.alert { padding: 16px; border-radius: 8px; margin-bottom: 24px; }
.alert-error { background: #f8d7da; color: #842029; }

@media (max-width: 768px) {
    .container { padding: 16px; }
    .button-group { flex-direction: column; }
}
</style>

<div class="container">
    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>"><?= h($message) ?></div>
    <?php endif; ?>

    <div class="page-header">
        <a href="<?= $traderId ? 'trader_detail.php?id=' . $traderId : 'traders.php' ?>" class="btn-back">← 뒤로</a>
        <div class="page-title">활동 작성</div>
    </div>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="trader_id" value="<?= h($traderId) ?>">

        <div class="card">
            <div class="section-title">기본 정보</div>

            <div class="form-group">
                <label class="form-label">거래처</label>
                <input type="text" class="form-input" value="<?= h($trader['company_name'] ?? '') ?>" readonly placeholder="거래처명">
            </div>

            <div class="form-group">
                <label class="form-label">날짜</label>
                <input type="date" class="form-input" name="activity_date" value="<?= date('Y-m-d') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">활동 유형</label>
                <select class="form-select" name="activity_type" id="activityType" onchange="toggleActivityForms()">
                    <option value="">유형 선택</option>
                    <option value="sales">영업활동</option>
                    <option value="meeting">미팅</option>
                    <option value="contract">계약</option>
                    <option value="quotation">견적</option>
                    <option value="other">기타</option>
                </select>
            </div>
        </div>

        <!-- 영업활동 전용 폼 -->
        <div class="card hidden" id="salesActivityForm">
            <div class="section-title">영업활동 상세</div>
            <div class="form-group">
                <label class="form-label">미팅요점</label>
                <textarea class="form-textarea" name="meeting_points" placeholder="미팅의 핵심 요점을 입력하세요"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">다음액션</label>
                <textarea class="form-textarea" name="next_action" placeholder="다음에 취할 액션을 입력하세요"></textarea>
            </div>
        </div>

        <!-- 견적 전용 폼 -->
        <div class="card hidden" id="quotationForm">
            <div class="section-title">견적 상세</div>
            <div class="form-group">
                <label class="form-label">제안가</label>
                <input type="text" class="form-input" name="proposal_price" placeholder="제안 금액 입력">
            </div>
            <div class="form-group">
                <label class="form-label">조건</label>
                <textarea class="form-textarea" name="proposal_conditions" placeholder="제안 조건을 입력하세요"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">유효기간</label>
                <input type="date" class="form-input" name="validity_period">
            </div>
        </div>

        <!-- 계약 전용 폼 -->
        <div class="card hidden" id="contractForm">
            <div class="section-title">계약 상세</div>
            <div class="form-group">
                <label class="form-label">수량</label>
                <input type="text" class="form-input" name="quantity" placeholder="수량 입력">
            </div>
            <div class="form-group">
                <label class="form-label">단가</label>
                <input type="text" class="form-input" name="unit_price" placeholder="단가 입력">
            </div>
            <div class="form-group">
                <label class="form-label">납기</label>
                <input type="date" class="form-input" name="delivery_date">
            </div>
            <div class="form-group">
                <label class="form-label">결제</label>
                <input type="text" class="form-input" name="payment" placeholder="결제 조건 입력">
            </div>
            <div class="form-group">
                <label class="form-label">배송</label>
                <input type="text" class="form-input" name="shipping" placeholder="배송 정보 입력">
            </div>
        </div>

        <!-- 활동 내용 -->
        <div class="card">
            <div class="section-title">활동 내용</div>
            <div class="form-group">
                <label class="form-label">미팅목적</label>
                <textarea class="form-textarea" name="meeting_purpose" placeholder="미팅의 목적을 입력하세요"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">내용</label>
                <textarea class="form-textarea" name="content" placeholder="미팅 내용을 상세히 입력하세요"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">결과</label>
                <textarea class="form-textarea" name="result" placeholder="미팅 결과를 입력하세요"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">후속조치</label>
                <textarea class="form-textarea" name="followup" placeholder="필요한 후속조치를 입력하세요"></textarea>
            </div>
        </div>

        <div class="button-group">
            <a href="<?= $traderId ? 'trader_detail.php?id=' . $traderId : 'traders.php' ?>" class="btn btn-cancel">취소</a>
            <button type="submit" class="btn btn-save">저장</button>
        </div>
    </form>
</div>

<?php
$pageScripts = <<<'SCRIPT'
<script>
function toggleActivityForms() {
    const activityType = document.getElementById('activityType').value;

    document.getElementById('salesActivityForm').classList.add('hidden');
    document.getElementById('quotationForm').classList.add('hidden');
    document.getElementById('contractForm').classList.add('hidden');

    if (activityType === 'sales') {
        document.getElementById('salesActivityForm').classList.remove('hidden');
    } else if (activityType === 'quotation') {
        document.getElementById('quotationForm').classList.remove('hidden');
    } else if (activityType === 'contract') {
        document.getElementById('contractForm').classList.remove('hidden');
    }
}
</script>
SCRIPT;

include dirname(dirname(__DIR__)) . '/includes/footer.php';
?>
