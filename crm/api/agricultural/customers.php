<?php
/**
 * 농산물 고객 API
 */

require_once dirname(dirname(__DIR__)) . '/config.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => '로그인이 필요합니다.'], 401);
}

$currentUser = getCurrentUser();
$pdo = getDB();

// GET: 고객 조회
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = $_GET['id'] ?? null;

    if ($id) {
        // 단일 고객 조회
        try {
            $stmt = $pdo->prepare("SELECT c.*, u.name as sales_name
                FROM " . CRM_AGRI_CUSTOMERS_TABLE . " c
                LEFT JOIN " . CRM_USERS_TABLE . " u ON c.assigned_sales = u.id
                WHERE c.id = ?");
            $stmt->execute([$id]);
            $customer = $stmt->fetch();

            if ($customer) {
                successResponse($customer);
            } else {
                errorResponse('고객을 찾을 수 없습니다.', 404);
            }
        } catch (Exception $e) {
            errorResponse('조회 중 오류가 발생했습니다.');
        }
    } else {
        // 목록 조회
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';

        $where = ["1=1"];
        $params = [];

        if ($search) {
            $where[] = "(company_name LIKE ? OR representative_name LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        if ($status) {
            $where[] = "status = ?";
            $params[] = $status;
        }

        $whereClause = implode(' AND ', $where);

        try {
            $stmt = $pdo->prepare("SELECT id, company_name, representative_name, phone, status
                FROM " . CRM_AGRI_CUSTOMERS_TABLE . "
                WHERE {$whereClause} ORDER BY company_name LIMIT 100");
            $stmt->execute($params);
            $customers = $stmt->fetchAll();
            successResponse($customers);
        } catch (Exception $e) {
            errorResponse('조회 중 오류가 발생했습니다.');
        }
    }
    exit;
}

// POST: 고객 생성/수정/삭제
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? 'create';

    switch ($action) {
        case 'create':
            $companyName = trim($input['company_name'] ?? '');
            if (empty($companyName)) {
                errorResponse('상호명을 입력해주세요.');
            }

            try {
                $stmt = $pdo->prepare("INSERT INTO " . CRM_AGRI_CUSTOMERS_TABLE . "
                    (company_name, business_number, representative_name, phone, address,
                     bank_name, account_number, account_holder, product_categories,
                     assigned_sales, status, created_by, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([
                    $companyName,
                    $input['business_number'] ?? null,
                    $input['representative_name'] ?? null,
                    $input['phone'] ?? null,
                    $input['address'] ?? null,
                    $input['bank_name'] ?? null,
                    $input['account_number'] ?? null,
                    $input['account_holder'] ?? null,
                    isset($input['product_categories']) ? json_encode($input['product_categories']) : null,
                    $input['assigned_sales'] ?? null,
                    $input['status'] ?? 'active',
                    $currentUser['crm_user_id'] ?? null
                ]);

                $newId = $pdo->lastInsertId();
                successResponse(['id' => $newId], '고객이 등록되었습니다.');
            } catch (Exception $e) {
                error_log("Agricultural customer create error: " . $e->getMessage());
                errorResponse('등록 중 오류가 발생했습니다: ' . $e->getMessage());
            }
            break;

        case 'update':
            $id = $input['id'] ?? null;
            $companyName = trim($input['company_name'] ?? '');

            if (!$id) {
                errorResponse('고객 ID가 필요합니다.');
            }
            if (empty($companyName)) {
                errorResponse('상호명을 입력해주세요.');
            }

            try {
                $stmt = $pdo->prepare("UPDATE " . CRM_AGRI_CUSTOMERS_TABLE . " SET
                    company_name = ?, business_number = ?, representative_name = ?,
                    phone = ?, address = ?, bank_name = ?, account_number = ?,
                    account_holder = ?, product_categories = ?,
                    assigned_sales = ?, status = ?, updated_at = NOW()
                    WHERE id = ?");
                $stmt->execute([
                    $companyName,
                    $input['business_number'] ?? null,
                    $input['representative_name'] ?? null,
                    $input['phone'] ?? null,
                    $input['address'] ?? null,
                    $input['bank_name'] ?? null,
                    $input['account_number'] ?? null,
                    $input['account_holder'] ?? null,
                    isset($input['product_categories']) ? json_encode($input['product_categories']) : null,
                    $input['assigned_sales'] ?? null,
                    $input['status'] ?? 'active',
                    $id
                ]);

                successResponse(null, '고객 정보가 수정되었습니다.');
            } catch (Exception $e) {
                error_log("Agricultural customer update error: " . $e->getMessage());
                errorResponse('수정 중 오류가 발생했습니다.');
            }
            break;

        case 'delete':
            $id = $input['id'] ?? null;
            if (!$id) {
                errorResponse('고객 ID가 필요합니다.');
            }

            try {
                $pdo->beginTransaction();

                // 관련 활동 삭제
                $stmt = $pdo->prepare("DELETE FROM " . CRM_AGRI_ACTIVITIES_TABLE . " WHERE customer_id = ?");
                $stmt->execute([$id]);

                // 고객 삭제
                $stmt = $pdo->prepare("DELETE FROM " . CRM_AGRI_CUSTOMERS_TABLE . " WHERE id = ?");
                $stmt->execute([$id]);

                $pdo->commit();
                successResponse(null, '고객이 삭제되었습니다.');
            } catch (Exception $e) {
                $pdo->rollBack();
                errorResponse('삭제 중 오류가 발생했습니다.');
            }
            break;

        default:
            errorResponse('잘못된 요청입니다.');
    }
    exit;
}

errorResponse('지원하지 않는 메서드입니다.', 405);
