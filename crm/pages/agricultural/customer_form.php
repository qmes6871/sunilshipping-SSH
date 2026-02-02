<?php
/**
 * 농산물 신규 고객 등록
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pdo = getDB();

$id = $_GET['id'] ?? null;
$isEdit = !empty($id);
$customer = null;

if ($isEdit) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM " . CRM_AGRI_CUSTOMERS_TABLE . " WHERE id = ?");
        $stmt->execute([$id]);
        $customer = $stmt->fetch();
        if (!$customer) {
            header('Location: dashboard.php');
            exit;
        }
    } catch (Exception $e) {
        header('Location: dashboard.php');
        exit;
    }
}

$pageTitle = $isEdit ? '고객 수정' : '농산물 신규 고객 등록';
$pageSubtitle = $isEdit ? $customer['company_name'] : '새 고객을 등록합니다';

// 담당자 목록
try {
    $stmt = $pdo->query("SELECT id, name FROM " . CRM_USERS_TABLE . " WHERE is_active = 1 ORDER BY name");
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
            'company_name' => trim($_POST['company_name'] ?? ''),
            'business_number' => trim($_POST['business_number'] ?? ''),
            'representative_name' => trim($_POST['representative_name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'bank_name' => trim($_POST['bank_name'] ?? ''),
            'account_number' => trim($_POST['account_number'] ?? ''),
            'account_holder' => trim($_POST['account_holder'] ?? ''),
            'product_categories' => json_encode($_POST['product_categories'] ?? []),
            'assigned_sales' => $_POST['assigned_sales'] ?: null,
            'status' => $_POST['status'] ?? 'active'
        ];

        if (empty($data['company_name'])) {
            $message = '상호명을 입력해주세요.';
            $messageType = 'error';
        } else {
            try {
                // 파일 업로드 처리
                $cardPath = $customer['card_image'] ?? null;
                $bizCertPath = $customer['biz_cert_image'] ?? null;
                $bankbookPath = $customer['bankbook_image'] ?? null;

                if (!empty($_FILES['card_image']['name'])) {
                    $result = uploadFile($_FILES['card_image'], 'agricultural/cards', ['image/jpeg', 'image/png', 'application/pdf']);
                    if ($result['success']) {
                        if ($cardPath) deleteFile($cardPath);
                        $cardPath = $result['path'];
                    }
                }
                if (!empty($_FILES['biz_cert_image']['name'])) {
                    $result = uploadFile($_FILES['biz_cert_image'], 'agricultural/bizcert', ['image/jpeg', 'image/png', 'application/pdf']);
                    if ($result['success']) {
                        if ($bizCertPath) deleteFile($bizCertPath);
                        $bizCertPath = $result['path'];
                    }
                }
                if (!empty($_FILES['bankbook_image']['name'])) {
                    $result = uploadFile($_FILES['bankbook_image'], 'agricultural/bankbook', ['image/jpeg', 'image/png', 'application/pdf']);
                    if ($result['success']) {
                        if ($bankbookPath) deleteFile($bankbookPath);
                        $bankbookPath = $result['path'];
                    }
                }

                if ($isEdit) {
                    $stmt = $pdo->prepare("UPDATE " . CRM_AGRI_CUSTOMERS_TABLE . " SET
                        company_name = ?, business_number = ?, representative_name = ?, phone = ?,
                        address = ?, bank_name = ?, account_number = ?, account_holder = ?,
                        product_categories = ?, card_image = ?, biz_cert_image = ?, bankbook_image = ?,
                        assigned_sales = ?, status = ?, updated_at = NOW()
                        WHERE id = ?");
                    $stmt->execute([
                        $data['company_name'], $data['business_number'], $data['representative_name'], $data['phone'],
                        $data['address'], $data['bank_name'], $data['account_number'], $data['account_holder'],
                        $data['product_categories'], $cardPath, $bizCertPath, $bankbookPath,
                        $data['assigned_sales'], $data['status'], $id
                    ]);
                    $message = '고객 정보가 수정되었습니다.';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO " . CRM_AGRI_CUSTOMERS_TABLE . "
                        (company_name, business_number, representative_name, phone, address,
                        bank_name, account_number, account_holder, product_categories,
                        card_image, biz_cert_image, bankbook_image, assigned_sales, status, created_by, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([
                        $data['company_name'], $data['business_number'], $data['representative_name'], $data['phone'],
                        $data['address'], $data['bank_name'], $data['account_number'], $data['account_holder'],
                        $data['product_categories'], $cardPath, $bizCertPath, $bankbookPath,
                        $data['assigned_sales'], $data['status'], $currentUser['crm_user_id']
                    ]);
                    header('Location: dashboard.php');
                    exit;
                }
                $messageType = 'success';

                // 데이터 새로고침
                $stmt = $pdo->prepare("SELECT * FROM " . CRM_AGRI_CUSTOMERS_TABLE . " WHERE id = ?");
                $stmt->execute([$id]);
                $customer = $stmt->fetch();

            } catch (Exception $e) {
                $message = '저장 중 오류가 발생했습니다.';
                $messageType = 'error';
            }
        }
    }
}

$productCategories = json_decode($customer['product_categories'] ?? '[]', true) ?: [];

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

.container { max-width: 1200px; margin: 0 auto; padding: 24px; }
.customer-card { background: #ffffff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.card-inner { padding: 24px; }

.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.page-title { font-size: 20px; font-weight: 700; color: #0d6efd; }

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
                    <h1 class="page-title"><?= $isEdit ? '고객 수정' : '농산물 신규 고객 등록' ?></h1>
                </div>

                <div class="section">
                    <h2 class="section-title">기본 및 계좌 정보</h2>
                    <div class="form-grid">
                        <div class="form-row">
                            <label class="label" for="company_name">상호명</label>
                            <input class="input" id="company_name" name="company_name" placeholder="상호명을 입력하세요" value="<?= h($customer['company_name'] ?? '') ?>" required />
                        </div>
                        <div class="form-row">
                            <label class="label" for="business_number">사업자등록번호</label>
                            <input class="input" id="business_number" name="business_number" placeholder="예) 123-45-67890" value="<?= h($customer['business_number'] ?? '') ?>" />
                        </div>
                        <div class="form-row">
                            <label class="label" for="representative_name">대표자이름</label>
                            <input class="input" id="representative_name" name="representative_name" placeholder="대표자명을 입력하세요" value="<?= h($customer['representative_name'] ?? '') ?>" />
                        </div>
                        <div class="form-row">
                            <label class="label" for="phone">전화번호</label>
                            <input class="input" id="phone" name="phone" placeholder="예) 010-1234-5678" value="<?= h($customer['phone'] ?? '') ?>" />
                        </div>
                        <div class="form-row full">
                            <label class="label" for="address">주소</label>
                            <input class="input" id="address" name="address" placeholder="주소를 입력하세요" value="<?= h($customer['address'] ?? '') ?>" />
                        </div>
                        <div class="form-row">
                            <label class="label" for="bank_name">은행명</label>
                            <input class="input" id="bank_name" name="bank_name" placeholder="예) 농협은행" value="<?= h($customer['bank_name'] ?? '') ?>" />
                        </div>
                        <div class="form-row">
                            <label class="label" for="account_number">계좌번호</label>
                            <input class="input" id="account_number" name="account_number" placeholder="예) 351-1234-5678-90" value="<?= h($customer['account_number'] ?? '') ?>" />
                        </div>
                        <div class="form-row">
                            <label class="label" for="account_holder">예금주</label>
                            <input class="input" id="account_holder" name="account_holder" placeholder="예금주명을 입력하세요" value="<?= h($customer['account_holder'] ?? '') ?>" />
                        </div>
                        <div class="form-row">
                            <label class="label" for="assigned_sales">담당자</label>
                            <select class="select" id="assigned_sales" name="assigned_sales">
                                <option value="">선택하세요</option>
                                <?php foreach ($salesList as $sales): ?>
                                    <option value="<?= $sales['id'] ?>" <?= ($customer['assigned_sales'] ?? '') == $sales['id'] ? 'selected' : '' ?>><?= h($sales['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <h2 class="section-title">첨부 파일</h2>
                    <div class="form-grid">
                        <div class="form-row">
                            <label class="label" for="card_image">명함</label>
                            <input class="file" id="card_image" name="card_image" type="file" accept="image/*,.pdf" />
                            <div class="helper">이미지 또는 PDF 업로드</div>
                            <?php if (!empty($customer['card_image'])): ?>
                                <div style="margin-top: 8px; font-size: 12px; color: #666;">현재 파일: <?= basename($customer['card_image']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-row">
                            <label class="label" for="biz_cert_image">사업자등록증 사본</label>
                            <input class="file" id="biz_cert_image" name="biz_cert_image" type="file" accept="image/*,.pdf" />
                            <?php if (!empty($customer['biz_cert_image'])): ?>
                                <div style="margin-top: 8px; font-size: 12px; color: #666;">현재 파일: <?= basename($customer['biz_cert_image']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-row">
                            <label class="label" for="bankbook_image">통장 사본</label>
                            <input class="file" id="bankbook_image" name="bankbook_image" type="file" accept="image/*,.pdf" />
                            <?php if (!empty($customer['bankbook_image'])): ?>
                                <div style="margin-top: 8px; font-size: 12px; color: #666;">현재 파일: <?= basename($customer['bankbook_image']) ?></div>
                            <?php endif; ?>
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
