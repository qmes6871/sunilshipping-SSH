<?php
/**
 * 우드펠렛 거래처 API
 */

require_once dirname(dirname(__DIR__)) . '/config.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => '로그인이 필요합니다.'], 401);
}

$currentUser = getCurrentUser();
$pdo = getDB();

// GET: 거래처 조회
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = $_GET['id'] ?? null;

    if ($id) {
        // 단일 거래처 조회
        try {
            $stmt = $pdo->prepare("SELECT t.*, u.name as sales_name
                FROM " . CRM_PELLET_TRADERS_TABLE . " t
                LEFT JOIN " . CRM_USERS_TABLE . " u ON t.assigned_sales = u.id
                WHERE t.id = ?");
            $stmt->execute([$id]);
            $trader = $stmt->fetch();

            if ($trader) {
                successResponse($trader);
            } else {
                errorResponse('거래처를 찾을 수 없습니다.', 404);
            }
        } catch (Exception $e) {
            errorResponse('조회 중 오류가 발생했습니다.');
        }
    } else {
        // 목록 조회
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $tradeType = $_GET['trade_type'] ?? '';

        $where = ["1=1"];
        $params = [];

        if ($search) {
            $where[] = "(company_name LIKE ? OR contact_person LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        if ($status) {
            $where[] = "status = ?";
            $params[] = $status;
        }
        if ($tradeType) {
            $where[] = "trade_type = ?";
            $params[] = $tradeType;
        }

        $whereClause = implode(' AND ', $where);

        try {
            $stmt = $pdo->prepare("SELECT id, company_name, contact_person, phone, trade_type, status
                FROM " . CRM_PELLET_TRADERS_TABLE . "
                WHERE {$whereClause} ORDER BY company_name LIMIT 100");
            $stmt->execute($params);
            $traders = $stmt->fetchAll();
            successResponse($traders);
        } catch (Exception $e) {
            errorResponse('조회 중 오류가 발생했습니다.');
        }
    }
    exit;
}

// POST: 거래처 생성/수정/삭제
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? 'create';

    switch ($action) {
        case 'create':
            $companyName = trim($input['company_name'] ?? '');
            if (empty($companyName)) {
                errorResponse('거래처명을 입력해주세요.');
            }

            try {
                $stmt = $pdo->prepare("INSERT INTO " . CRM_PELLET_TRADERS_TABLE . "
                    (company_name, business_number, representative_name, contact_person,
                     phone, email, address, trade_type, annual_volume,
                     bank_name, account_number, payment_method, contract_date, contract_period,
                     assigned_sales, status, created_by, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([
                    $companyName,
                    $input['business_number'] ?? null,
                    $input['representative_name'] ?? null,
                    $input['contact_person'] ?? null,
                    $input['phone'] ?? null,
                    $input['email'] ?? null,
                    $input['address'] ?? null,
                    $input['trade_type'] ?? 'offline_retail',
                    $input['annual_volume'] ?? null,
                    $input['bank_name'] ?? null,
                    $input['account_number'] ?? null,
                    $input['payment_method'] ?? null,
                    $input['contract_date'] ?? null,
                    $input['contract_period'] ?? null,
                    $input['assigned_sales'] ?? null,
                    $input['status'] ?? 'active',
                    $currentUser['crm_user_id'] ?? null
                ]);

                $newId = $pdo->lastInsertId();
                successResponse(['id' => $newId], '거래처가 등록되었습니다.');
            } catch (Exception $e) {
                error_log("Pellet trader create error: " . $e->getMessage());
                errorResponse('등록 중 오류가 발생했습니다.');
            }
            break;

        case 'update':
            $id = $input['id'] ?? null;
            $companyName = trim($input['company_name'] ?? '');

            if (!$id) {
                errorResponse('거래처 ID가 필요합니다.');
            }
            if (empty($companyName)) {
                errorResponse('거래처명을 입력해주세요.');
            }

            try {
                $stmt = $pdo->prepare("UPDATE " . CRM_PELLET_TRADERS_TABLE . " SET
                    company_name = ?, business_number = ?, representative_name = ?,
                    contact_person = ?, phone = ?, email = ?, address = ?,
                    trade_type = ?, annual_volume = ?, bank_name = ?, account_number = ?,
                    payment_method = ?, contract_date = ?, contract_period = ?,
                    assigned_sales = ?, status = ?, updated_at = NOW()
                    WHERE id = ?");
                $stmt->execute([
                    $companyName,
                    $input['business_number'] ?? null,
                    $input['representative_name'] ?? null,
                    $input['contact_person'] ?? null,
                    $input['phone'] ?? null,
                    $input['email'] ?? null,
                    $input['address'] ?? null,
                    $input['trade_type'] ?? 'offline_retail',
                    $input['annual_volume'] ?? null,
                    $input['bank_name'] ?? null,
                    $input['account_number'] ?? null,
                    $input['payment_method'] ?? null,
                    $input['contract_date'] ?? null,
                    $input['contract_period'] ?? null,
                    $input['assigned_sales'] ?? null,
                    $input['status'] ?? 'active',
                    $id
                ]);

                successResponse(null, '거래처 정보가 수정되었습니다.');
            } catch (Exception $e) {
                error_log("Pellet trader update error: " . $e->getMessage());
                errorResponse('수정 중 오류가 발생했습니다.');
            }
            break;

        case 'delete':
            $id = $input['id'] ?? null;
            if (!$id) {
                errorResponse('거래처 ID가 필요합니다.');
            }

            try {
                $stmt = $pdo->prepare("DELETE FROM " . CRM_PELLET_TRADERS_TABLE . " WHERE id = ?");
                $stmt->execute([$id]);
                successResponse(null, '거래처가 삭제되었습니다.');
            } catch (Exception $e) {
                errorResponse('삭제 중 오류가 발생했습니다.');
            }
            break;

        default:
            errorResponse('잘못된 요청입니다.');
    }
    exit;
}

errorResponse('지원하지 않는 메서드입니다.', 405);
