<?php
/**
 * 국제물류 바이어 등록/수정
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pdo = getDB();

$id = $_GET['id'] ?? null;
$isEdit = !empty($id);

// 수정 모드일 경우 기존 데이터 조회
$customer = null;
if ($isEdit) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM " . CRM_INTL_CUSTOMERS_TABLE . " WHERE id = ?");
        $stmt->execute([$id]);
        $customer = $stmt->fetch();

        if (!$customer) {
            header('Location: ' . CRM_URL . '/pages/international/dashboard.php');
            exit;
        }
    } catch (Exception $e) {
        header('Location: ' . CRM_URL . '/pages/international/dashboard.php');
        exit;
    }
}

$pageTitle = $isEdit ? '바이어 수정' : '신규 고객 등록';
$pageSubtitle = $isEdit ? $customer['name'] : '새 바이어를 등록합니다';

// 담당 영업사원 목록
try {
    $stmt = $pdo->query("SELECT id, name, department FROM " . CRM_USERS_TABLE . " WHERE is_active = 1 ORDER BY name");
    $salesList = $stmt->fetchAll();
} catch (Exception $e) {
    $salesList = [];
}

// POST 처리
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrfToken) {
        $message = 'CSRF 토큰이 유효하지 않습니다.';
        $messageType = 'error';
    } else {
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'customer_type' => !empty($_POST['customer_type']) ? $_POST['customer_type'] : 'buyer',
            'phone' => trim($_POST['phone'] ?? ''),
            'whatsapp' => trim($_POST['whatsapp'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'nationality' => trim($_POST['nationality'] ?? ''),
            'export_country' => trim($_POST['export_country'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'passport_info' => trim($_POST['passport_info'] ?? ''),
            'bank_name' => trim($_POST['bank_name'] ?? ''),
            'account_number' => trim($_POST['account_number'] ?? ''),
            'account_holder' => trim($_POST['account_holder'] ?? ''),
            'swift_code' => trim($_POST['swift_code'] ?? ''),
            'assigned_sales' => $_POST['assigned_sales'] ?: null,
            'status' => $_POST['status'] ?? 'active'
        ];

        if (empty($data['name'])) {
            $message = '이름을 입력해주세요.';
            $messageType = 'error';
        } else {
            try {
                // 프로필 사진 처리
                $photoPath = $customer['photo'] ?? null;
                if (!empty($_FILES['photo']['name'])) {
                    $result = uploadFile($_FILES['photo'], 'customers/photos', ['image/jpeg', 'image/png', 'image/gif']);
                    if ($result['success']) {
                        if ($photoPath) deleteFile($photoPath);
                        $photoPath = $result['path'];
                    }
                }

                // 여권 사진 처리
                $passportPath = $customer['passport_photo'] ?? null;
                if (!empty($_FILES['passport_photo']['name'])) {
                    $result = uploadFile($_FILES['passport_photo'], 'customers/passports', ['image/jpeg', 'image/png', 'image/gif', 'application/pdf']);
                    if ($result['success']) {
                        if ($passportPath) deleteFile($passportPath);
                        $passportPath = $result['path'];
                    }
                }

                if ($isEdit) {
                    $stmt = $pdo->prepare("UPDATE " . CRM_INTL_CUSTOMERS_TABLE . " SET
                        name = ?, customer_type = ?, phone = ?, whatsapp = ?, email = ?,
                        nationality = ?, export_country = ?, address = ?, passport_info = ?,
                        photo = ?, passport_photo = ?,
                        bank_name = ?, account_number = ?, account_holder = ?, swift_code = ?,
                        assigned_sales = ?, status = ?, updated_at = NOW()
                        WHERE id = ?");
                    $stmt->execute([
                        $data['name'], $data['customer_type'], $data['phone'], $data['whatsapp'], $data['email'],
                        $data['nationality'], $data['export_country'], $data['address'], $data['passport_info'],
                        $photoPath, $passportPath,
                        $data['bank_name'], $data['account_number'], $data['account_holder'], $data['swift_code'],
                        $data['assigned_sales'], $data['status'], $id
                    ]);
                    $message = '바이어 정보가 수정되었습니다.';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO " . CRM_INTL_CUSTOMERS_TABLE . "
                        (name, customer_type, phone, whatsapp, email, nationality, export_country, address, passport_info,
                        photo, passport_photo, bank_name, account_number, account_holder, swift_code,
                        assigned_sales, status, created_by, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([
                        $data['name'], $data['customer_type'], $data['phone'], $data['whatsapp'], $data['email'],
                        $data['nationality'], $data['export_country'], $data['address'], $data['passport_info'],
                        $photoPath, $passportPath,
                        $data['bank_name'], $data['account_number'], $data['account_holder'], $data['swift_code'],
                        $data['assigned_sales'], $data['status'], $currentUser['crm_user_id']
                    ]);
                    $newId = $pdo->lastInsertId();
                    header('Location: ' . CRM_URL . '/pages/international/customer_detail.php?id=' . $newId);
                    exit;
                }
                $messageType = 'success';

                // 수정된 데이터 다시 로드
                $stmt = $pdo->prepare("SELECT * FROM " . CRM_INTL_CUSTOMERS_TABLE . " WHERE id = ?");
                $stmt->execute([$id]);
                $customer = $stmt->fetch();

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

.container { max-width: 1200px; margin: 0 auto; padding: 24px; }

.customer-card { background: #ffffff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.card-inner { padding: 24px; }

.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.page-title { font-size: 20px; font-weight: 700; color: #0d6efd; }
.action-buttons { display: flex; gap: 10px; }

.section { margin-bottom: 28px; }
.section-title { font-size: 16px; font-weight: 600; color: #0d6efd; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 2px solid #e9ecef; }

.form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
.form-row { display: flex; flex-direction: column; gap: 6px; }
.form-row.full { grid-column: 1 / -1; }
.label { font-size: 13px; color: #6c757d; font-weight: 500; }
.input, .select, .textarea { width: 100%; padding: 10px 12px; border: 1px solid #dee2e6; border-radius: 6px; background: #fff; font-size: 14px; color: #212529; }
.textarea { min-height: 96px; resize: vertical; }
.file { padding: 10px 12px; border: 1px dashed #ced4da; background: #fff; border-radius: 6px; }
.helper { font-size: 12px; color: #adb5bd; }

.footer-actions { margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px; }

.alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; }
.alert-success { background: #d1e7dd; color: #0f5132; }
.alert-error { background: #f8d7da; color: #842029; }

@media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } }
</style>

<div class="container">
    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>"><?= h($message) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <div class="customer-card">
            <div class="card-inner">
                <div class="page-header">
                    <h1 class="page-title"><?= $isEdit ? '바이어 수정' : '신규 고객 등록' ?></h1>
                </div>

                <div class="section">
                    <h2 class="section-title">기본 정보</h2>
                    <div class="form-grid">
                        <div class="form-row">
                            <label class="label" for="name">이름</label>
                            <input class="input" id="name" name="name" placeholder="이름을 입력하세요" value="<?= h($customer['name'] ?? '') ?>" required />
                        </div>
                        <div class="form-row">
                            <label class="label" for="customer_type">고객유형</label>
                            <select class="select" id="customer_type" name="customer_type">
                                <option value="">선택하세요</option>
                                <option value="buyer" <?= ($customer['customer_type'] ?? '') === 'buyer' ? 'selected' : '' ?>>바이어</option>
                                <option value="partner" <?= ($customer['customer_type'] ?? '') === 'partner' ? 'selected' : '' ?>>파트너</option>
                                <option value="agent" <?= ($customer['customer_type'] ?? '') === 'agent' ? 'selected' : '' ?>>에이전트</option>
                            </select>
                        </div>

                        <div class="form-row">
                            <label class="label" for="phone">전화번호</label>
                            <input class="input" id="phone" name="phone" placeholder="예) +82-10-1234-5678" value="<?= h($customer['phone'] ?? '') ?>" />
                        </div>
                        <div class="form-row">
                            <label class="label" for="whatsapp">와츠앱</label>
                            <input class="input" id="whatsapp" name="whatsapp" placeholder="예) +82-10-1234-5678" value="<?= h($customer['whatsapp'] ?? '') ?>" />
                        </div>

                        <div class="form-row">
                            <label class="label" for="nationality">국적</label>
                            <select class="select" id="nationality" name="nationality">
                                <option value="">선택하세요</option>
                                <?php foreach (getIntlCountries() as $country): ?>
                                <option value="<?= h($country) ?>" <?= ($customer['nationality'] ?? '') === $country ? 'selected' : '' ?>><?= h($country) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-row">
                            <label class="label" for="export_country">수출국</label>
                            <select class="select" id="export_country" name="export_country">
                                <option value="">선택하세요</option>
                                <?php foreach (getIntlCountries() as $country): ?>
                                <option value="<?= h($country) ?>" <?= ($customer['export_country'] ?? '') === $country ? 'selected' : '' ?>><?= h($country) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-row">
                            <label class="label" for="email">이메일</label>
                            <input class="input" id="email" name="email" type="email" placeholder="예) email@example.com" value="<?= h($customer['email'] ?? '') ?>" />
                        </div>
                        <div class="form-row">
                            <label class="label" for="status">상태</label>
                            <select class="select" id="status" name="status">
                                <option value="active" <?= ($customer['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>활성</option>
                                <option value="pending" <?= ($customer['status'] ?? '') === 'pending' ? 'selected' : '' ?>>대기</option>
                                <option value="inactive" <?= ($customer['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>비활성</option>
                            </select>
                        </div>

                        <div class="form-row full">
                            <label class="label" for="passport_info">여권정보</label>
                            <textarea class="textarea" id="passport_info" name="passport_info" placeholder="예) 여권번호, 발급일, 만료일"><?= h($customer['passport_info'] ?? '') ?></textarea>
                            <div class="helper">필요 시 번호/만료일 등 상세 내용을 입력하세요.</div>
                        </div>

                        <div class="form-row full">
                            <label class="label" for="address">주소</label>
                            <textarea class="textarea" id="address" name="address" placeholder="주소를 입력하세요" style="min-height: 60px;"><?= h($customer['address'] ?? '') ?></textarea>
                        </div>

                        <div class="form-row full">
                            <label class="label" for="assigned_sales">담당영업사원</label>
                            <select class="select" id="assigned_sales" name="assigned_sales">
                                <option value="">선택하세요</option>
                                <?php foreach ($salesList as $sales): ?>
                                    <option value="<?= $sales['id'] ?>" <?= ($customer['assigned_sales'] ?? '') == $sales['id'] ? 'selected' : '' ?>>
                                        <?= h($sales['name']) ?>
                                        <?php if ($sales['department']): ?>
                                            (<?= getDepartmentName($sales['department']) ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <h2 class="section-title">이미지 업로드</h2>
                    <div class="form-grid">
                        <div class="form-row">
                            <label class="label" for="photo">고객사진</label>
                            <input class="file" id="photo" name="photo" type="file" accept="image/*" />
                            <div class="helper">JPG/PNG 권장 (최대 10MB)</div>
                            <?php if (!empty($customer['photo'])): ?>
                                <div style="margin-top: 8px;">
                                    <img src="<?= CRM_UPLOAD_URL ?>/<?= h($customer['photo']) ?>" style="max-width: 100px; max-height: 100px; border-radius: 6px;">
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="form-row">
                            <label class="label" for="passport_photo">여권사진</label>
                            <input class="file" id="passport_photo" name="passport_photo" type="file" accept="image/*" />
                            <div class="helper">여권 사진면 이미지 업로드</div>
                            <?php if (!empty($customer['passport_photo'])): ?>
                                <div style="margin-top: 8px;">
                                    <img src="<?= CRM_UPLOAD_URL ?>/<?= h($customer['passport_photo']) ?>" style="max-width: 100px; max-height: 100px; border-radius: 6px;">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <h2 class="section-title">계좌 정보</h2>
                    <div class="form-grid">
                        <div class="form-row">
                            <label class="label" for="bank_name">은행명</label>
                            <input class="input" id="bank_name" name="bank_name" placeholder="예) 국민은행" value="<?= h($customer['bank_name'] ?? '') ?>" />
                        </div>
                        <div class="form-row">
                            <label class="label" for="account_number">계좌번호</label>
                            <input class="input" id="account_number" name="account_number" placeholder="예) 123-456-789012" value="<?= h($customer['account_number'] ?? '') ?>" />
                        </div>
                        <div class="form-row">
                            <label class="label" for="account_holder">예금주</label>
                            <input class="input" id="account_holder" name="account_holder" placeholder="예) 홍길동" value="<?= h($customer['account_holder'] ?? '') ?>" />
                        </div>
                        <div class="form-row">
                            <label class="label" for="swift_code">SWIFT 코드</label>
                            <input class="input" id="swift_code" name="swift_code" placeholder="예) CZNBKRSE" value="<?= h($customer['swift_code'] ?? '') ?>" />
                        </div>
                    </div>
                </div>

                <div class="footer-actions">
                    <button type="submit" class="btn btn-primary"><?= $isEdit ? '수정' : '등록' ?></button>
                    <button type="button" class="btn btn-secondary" onclick="history.back()">취소</button>
                </div>
            </div>
        </div>
    </form>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
