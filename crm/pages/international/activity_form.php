<?php
/**
 * 국제물류 영업활동 작성
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pageTitle = '활동 작성';
$pageSubtitle = '영업 활동을 기록합니다';

$pdo = getDB();

$customerId = $_GET['customer_id'] ?? null;
$customer = null;

// 바이어 선택된 경우 정보 조회
if ($customerId) {
    try {
        $stmt = $pdo->prepare("SELECT id, name, nationality FROM " . CRM_INTL_CUSTOMERS_TABLE . " WHERE id = ?");
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch();
    } catch (Exception $e) {
        $customer = null;
    }
}

// 바이어 목록 조회
try {
    $stmt = $pdo->query("SELECT id, name, nationality FROM " . CRM_INTL_CUSTOMERS_TABLE . " WHERE status = 'active' ORDER BY name");
    $customers = $stmt->fetchAll();
} catch (Exception $e) {
    $customers = [];
}

// POST 처리
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrfToken) {
        $message = 'CSRF 토큰이 유효하지 않습니다.';
        $messageType = 'error';
    } else {
        $activityType = $_POST['activity_type'] ?? 'contact';

        $data = [
            'customer_id' => $_POST['customer_id'] ?: null,
            'activity_date' => $_POST['activity_date'] ?? date('Y-m-d'),
            'activity_type' => $activityType,
            'booking_completed' => ($activityType === 'booking_completed') ? 1 : 0,
            'meeting_purpose' => trim($_POST['meeting_purpose'] ?? ''),
            'activity_content' => trim($_POST['activity_content'] ?? ''),
            'activity_result' => trim($_POST['activity_result'] ?? ''),
            'followup_items' => trim($_POST['followup_items'] ?? '')
        ];

        // 부킹완료일 경우 추가 정보
        if ($activityType === 'booking_completed') {
            $bookingInfo = json_encode([
                'buyer_name' => trim($_POST['buyer_name'] ?? ''),
                'destination' => trim($_POST['destination'] ?? ''),
                'container_type' => trim($_POST['container_type'] ?? ''),
                'cargo_items' => trim($_POST['cargo_items'] ?? ''),
                'loading_place' => trim($_POST['loading_place'] ?? ''),
                'expected_date' => $_POST['expected_date'] ?? '',
                'freight_offer' => trim($_POST['freight_offer'] ?? ''),
                'booking_notes' => trim($_POST['booking_notes'] ?? '')
            ]);
            $data['booking_info'] = $bookingInfo;
        }

        try {
                // 부킹완료/정산완료일 경우 details JSON 준비
                $detailsJson = null;
                if ($activityType === 'booking_completed' && isset($data['booking_info'])) {
                    $detailsJson = $data['booking_info'];
                }

                $stmt = $pdo->prepare("INSERT INTO " . CRM_INTL_ACTIVITIES_TABLE . "
                    (customer_id, activity_date, activity_type, booking_completed, meeting_purpose,
                    activity_content, activity_result, followup_items, details, created_by, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([
                    $data['customer_id'],
                    $data['activity_date'],
                    $data['activity_type'],
                    $data['booking_completed'],
                    $data['meeting_purpose'],
                    $data['activity_content'],
                    $data['activity_result'],
                    $data['followup_items'],
                    $detailsJson,
                    $currentUser['crm_user_id']
                ]);

                // 파일 업로드 처리
                if (!empty($_FILES['recording']['name'])) {
                    $activityId = $pdo->lastInsertId();
                    $result = uploadFile($_FILES['recording'], 'activities/recordings', CRM_ALLOWED_AUDIO_TYPES);
                    if ($result['success']) {
                        $stmt = $pdo->prepare("INSERT INTO " . CRM_FILES_TABLE . "
                            (entity_type, entity_id, file_name, file_path, file_size, file_type, uploaded_by, created_at)
                            VALUES ('intl_activity', ?, ?, ?, ?, ?, ?, NOW())");
                        $stmt->execute([$activityId, $result['original_name'], $result['path'], $result['size'], $_FILES['recording']['type'], $currentUser['crm_user_id']]);
                    }
                }

                $message = '활동이 등록되었습니다.';
                $messageType = 'success';

                // 바이어 상세로 이동
                if ($data['customer_id']) {
                    header('Location: ' . CRM_URL . '/pages/international/customer_detail.php?id=' . $data['customer_id']);
                    exit;
                }
            } catch (Exception $e) {
                $message = '저장 중 오류가 발생했습니다.';
                $messageType = 'error';
            }
    }
}

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

.container { max-width: 800px; margin: 0 auto; padding: 20px; }

/* 페이지 헤더 */
.page-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
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
}

.btn-back:hover { background: #f8f9fa; }

.page-title {
    font-size: 24px;
    font-weight: 600;
    color: #212529;
}

/* 카드 */
.card {
    background: white;
    padding: 24px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.section-title {
    font-size: 16px;
    font-weight: 600;
    color: #212529;
    margin-bottom: 16px;
    padding-bottom: 8px;
    border-bottom: 1px solid #e9ecef;
}

/* 폼 그룹 */
.form-group { margin-bottom: 24px; }

.form-label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    color: #495057;
    margin-bottom: 8px;
}

.form-input,
.form-select,
.form-textarea {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    font-size: 14px;
    color: #212529;
    background: white;
    font-family: inherit;
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
    outline: none;
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}

.form-textarea {
    min-height: 120px;
    resize: vertical;
    line-height: 1.6;
}

.form-input::placeholder,
.form-textarea::placeholder { color: #adb5bd; }

/* 녹음 버튼 */
.recording-section {
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    text-align: center;
}

.recording-controls {
    display: flex;
    gap: 12px;
    justify-content: center;
    align-items: center;
    flex-wrap: wrap;
}

.btn-record {
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #dc3545;
    color: white;
}

.btn-record:hover { background: #bb2d3b; }

.file-wrapper {
    position: relative;
    display: inline-block;
}

.file-wrapper input[type="file"] {
    position: absolute;
    left: 0;
    top: 0;
    opacity: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
}

.audio-list {
    margin-top: 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.audio-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 6px;
}

/* 버튼 영역 */
.button-group {
    display: flex;
    gap: 12px;
    margin-top: 32px;
}

.btn {
    flex: 1;
    padding: 14px 24px;
    border: none;
    border-radius: 6px;
    font-size: 15px;
    font-weight: 500;
    cursor: pointer;
    text-align: center;
    text-decoration: none;
}

.btn-cancel {
    background: #f8f9fa;
    color: #495057;
    border: 1px solid #dee2e6;
}

.btn-cancel:hover { background: #e9ecef; }

.btn-save {
    background: #0d6efd;
    color: white;
}

.btn-save:hover { background: #0b5ed7; }

/* 숨김 클래스 */
.hidden { display: none; }

/* 거래처 고정 표시 */
.customer-fixed {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: #e7f5ff;
    border: 1px solid #74c0fc;
    border-radius: 6px;
}

.customer-name {
    font-size: 15px;
    font-weight: 600;
    color: #1c7ed6;
}

.customer-badge {
    padding: 4px 10px;
    background: #1c7ed6;
    color: white;
    font-size: 12px;
    font-weight: 600;
    border-radius: 12px;
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
    .container { padding: 16px; }
    .button-group { flex-direction: column; }
    .recording-controls { flex-direction: column; }
    .btn-record { width: 100%; justify-content: center; }
}
</style>

<div class="container">
    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>"><?= h($message) ?></div>
    <?php endif; ?>

    <!-- 페이지 헤더 -->
    <div class="page-header">
        <a href="<?= $customerId ? CRM_URL . '/pages/international/customer_detail.php?id=' . $customerId : CRM_URL . '/pages/international/dashboard.php' ?>" class="btn-back">← 뒤로</a>
        <div class="page-title">활동 작성</div>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <!-- 기본 정보 -->
        <div class="card">
            <div class="section-title">기본 정보</div>

            <div class="form-group">
                <label class="form-label">거래처</label>
                <?php if ($customer): ?>
                    <!-- 거래처가 지정된 경우 고정 표시 -->
                    <input type="hidden" name="customer_id" value="<?= $customer['id'] ?>">
                    <div class="customer-fixed">
                        <span class="customer-name">
                            <?= h($customer['name']) ?>
                            <?php if (!empty($customer['nationality'])): ?>
                                <span style="font-weight: 400; color: #495057;"> (<?= h($customer['nationality']) ?>)</span>
                            <?php endif; ?>
                        </span>
                        <span class="customer-badge">고정</span>
                    </div>
                <?php else: ?>
                    <!-- 거래처가 지정되지 않은 경우 선택 가능 -->
                    <select name="customer_id" class="form-select" id="client">
                        <option value="">선택 (미지정)</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= $c['id'] ?>">
                                <?= h($c['name']) ?>
                                <?php if ($c['nationality']): ?>
                                    (<?= h($c['nationality']) ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label">날짜</label>
                <input type="date" name="activity_date" class="form-input" id="date" value="<?= date('Y-m-d') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">활동 유형</label>
                <select class="form-select" name="activity_type" id="activityType" onchange="toggleBookingForm()">
                    <option value="">유형 선택</option>
                    <option value="lead">리드</option>
                    <option value="contact" selected>접촉</option>
                    <option value="proposal">제안</option>
                    <option value="negotiation">협상</option>
                    <option value="progress">진행</option>
                    <option value="booking_completed">부킹완료</option>
                    <option value="settlement_completed">정산완료</option>
                </select>
            </div>
        </div>

        <!-- 부킹완료 전용 폼 -->
        <div class="card hidden" id="bookingForm">
            <div class="section-title">부킹 상세 정보</div>

            <div class="form-group">
                <label class="form-label">바이어 이름</label>
                <input type="text" name="buyer_name" class="form-input" placeholder="바이어 이름 입력" id="buyerName">
            </div>

            <div class="form-group">
                <label class="form-label">최종목적지 및 경유</label>
                <input type="text" name="destination" class="form-input" placeholder="최종목적지 및 경유지 입력" id="destination">
            </div>

            <div class="form-group">
                <label class="form-label">컨테이너 타입 및 수량</label>
                <input type="text" name="container_type" class="form-input" placeholder="예: 40ft HC x 2" id="containerType">
            </div>

            <div class="form-group">
                <label class="form-label">적입 아이템 및 예상 무게</label>
                <textarea name="cargo_items" class="form-textarea" placeholder="적입할 아이템과 예상 무게를 입력하세요" id="cargoItems"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">쇼링장 또는 DOOR</label>
                <input type="text" name="loading_place" class="form-input" placeholder="쇼링장 주소 또는 DOOR 입력" id="loadingPlace">
            </div>

            <div class="form-group">
                <label class="form-label">작업 예상 일자</label>
                <input type="date" name="expected_date" class="form-input" id="expectedDate">
            </div>

            <div class="form-group">
                <label class="form-label">운임 오퍼</label>
                <input type="text" name="freight_offer" class="form-input" placeholder="운임 금액 입력" id="freightOffer">
            </div>

            <div class="form-group">
                <label class="form-label">기타</label>
                <textarea name="booking_notes" class="form-textarea" placeholder="기타 특이사항을 입력하세요" id="bookingNotes"></textarea>
            </div>
        </div>

        <!-- 활동 내용 -->
        <div class="card">
            <div class="section-title">활동 내용</div>

            <!-- 녹음 파일 -->
            <div class="recording-section">
                <div class="recording-controls">
                    <div class="file-wrapper">
                        <button type="button" class="btn-record">녹음 파일 등록</button>
                        <input type="file" name="recording" accept="audio/*" id="recordingInput">
                    </div>
                </div>
                <div class="audio-list" id="audioList"></div>
            </div>

            <div class="form-group">
                <label class="form-label">미팅목적</label>
                <textarea name="meeting_purpose" class="form-textarea" placeholder="미팅의 목적을 입력하세요" id="purpose"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">내용</label>
                <textarea name="activity_content" class="form-textarea" placeholder="미팅 내용을 상세히 입력하세요" id="content"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">결과</label>
                <textarea name="activity_result" class="form-textarea" placeholder="미팅 결과를 입력하세요" id="result"></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">후속조치</label>
                <textarea name="followup_items" class="form-textarea" placeholder="필요한 후속조치를 입력하세요" id="followup"></textarea>
            </div>
        </div>

        <!-- 버튼 영역 -->
        <div class="button-group">
            <a href="<?= $customerId ? CRM_URL . '/pages/international/customer_detail.php?id=' . $customerId : CRM_URL . '/pages/international/dashboard.php' ?>" class="btn btn-cancel">취소</a>
            <button type="submit" class="btn btn-save">저장</button>
        </div>
    </form>
</div>

<?php
$pageScripts = <<<SCRIPT
<script>
// 부킹완료 폼 표시/숨김
function toggleBookingForm() {
    const activityType = document.getElementById('activityType').value;
    const bookingForm = document.getElementById('bookingForm');

    if (activityType === 'booking_completed') {
        bookingForm.classList.remove('hidden');
    } else {
        bookingForm.classList.add('hidden');
    }
}

// 녹음 파일 선택 시
document.getElementById('recordingInput').addEventListener('change', function() {
    const audioList = document.getElementById('audioList');
    audioList.innerHTML = '';

    if (this.files.length > 0) {
        const file = this.files[0];
        const item = document.createElement('div');
        item.className = 'audio-item';
        item.innerHTML = '<span style="flex:1;">' + file.name + ' (' + formatFileSize(file.size) + ')</span>' +
            '<button type="button" onclick="removeAudio()" style="padding:6px 12px;background:#dc3545;color:white;border:none;border-radius:4px;cursor:pointer;">삭제</button>';
        audioList.appendChild(item);
    }
});

function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

function removeAudio() {
    document.getElementById('recordingInput').value = '';
    document.getElementById('audioList').innerHTML = '';
}
</script>
SCRIPT;

include dirname(dirname(__DIR__)) . '/includes/footer.php';
?>
