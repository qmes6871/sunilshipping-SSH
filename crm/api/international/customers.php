<?php
/**
 * 국제물류 바이어 API
 */

require_once dirname(dirname(__DIR__)) . '/config.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => '로그인이 필요합니다.'], 401);
}

$currentUser = getCurrentUser();
$pdo = getDB();

// GET: 바이어 조회
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = $_GET['id'] ?? null;

    if ($id) {
        try {
            $stmt = $pdo->prepare("SELECT c.*, u.name as sales_name
                FROM " . CRM_INTL_CUSTOMERS_TABLE . " c
                LEFT JOIN " . CRM_USERS_TABLE . " u ON c.assigned_sales = u.id
                WHERE c.id = ?");
            $stmt->execute([$id]);
            $customer = $stmt->fetch();

            if ($customer) {
                successResponse($customer);
            } else {
                errorResponse('바이어를 찾을 수 없습니다.', 404);
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
            $where[] = "(name LIKE ? OR email LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        if ($status) {
            $where[] = "status = ?";
            $params[] = $status;
        }

        $whereClause = implode(' AND ', $where);

        try {
            $stmt = $pdo->prepare("SELECT id, name, nationality, status FROM " . CRM_INTL_CUSTOMERS_TABLE . " WHERE {$whereClause} ORDER BY name LIMIT 100");
            $stmt->execute($params);
            $customers = $stmt->fetchAll();
            successResponse($customers);
        } catch (Exception $e) {
            errorResponse('조회 중 오류가 발생했습니다.');
        }
    }
    exit;
}

// POST: 바이어 생성/수정/삭제
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? 'create';

    switch ($action) {
        case 'create':
            // 바이어 등록
            $name = trim($input['name'] ?? '');
            if (empty($name)) {
                errorResponse('고객명을 입력해주세요.');
            }

            try {
                $stmt = $pdo->prepare("INSERT INTO " . CRM_INTL_CUSTOMERS_TABLE . "
                    (name, customer_type, phone, whatsapp, email, nationality, export_country,
                     address, passport_info, bank_name, account_number, account_holder, swift_code,
                     assigned_sales, status, created_by, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([
                    $name,
                    $input['customer_type'] ?? 'buyer',
                    $input['phone'] ?? null,
                    $input['whatsapp'] ?? null,
                    $input['email'] ?? null,
                    $input['nationality'] ?? null,
                    $input['export_country'] ?? null,
                    $input['address'] ?? null,
                    $input['passport_info'] ?? null,
                    $input['bank_name'] ?? null,
                    $input['account_number'] ?? null,
                    $input['account_holder'] ?? null,
                    $input['swift_code'] ?? null,
                    $input['assigned_sales'] ?? null,
                    $input['status'] ?? 'active',
                    $currentUser['crm_user_id'] ?? null
                ]);

                $newId = $pdo->lastInsertId();
                successResponse(['id' => $newId], '바이어가 등록되었습니다.');
            } catch (Exception $e) {
                error_log("Customer create error: " . $e->getMessage());
                errorResponse('등록 중 오류가 발생했습니다.');
            }
            break;

        case 'update':
            // 바이어 수정
            $id = $input['id'] ?? null;
            $name = trim($input['name'] ?? '');

            if (!$id) {
                errorResponse('바이어 ID가 필요합니다.');
            }
            if (empty($name)) {
                errorResponse('고객명을 입력해주세요.');
            }

            try {
                $stmt = $pdo->prepare("UPDATE " . CRM_INTL_CUSTOMERS_TABLE . " SET
                    name = ?, customer_type = ?, phone = ?, whatsapp = ?, email = ?,
                    nationality = ?, export_country = ?, address = ?, passport_info = ?,
                    bank_name = ?, account_number = ?, account_holder = ?, swift_code = ?,
                    assigned_sales = ?, status = ?, updated_at = NOW()
                    WHERE id = ?");
                $stmt->execute([
                    $name,
                    $input['customer_type'] ?? 'buyer',
                    $input['phone'] ?? null,
                    $input['whatsapp'] ?? null,
                    $input['email'] ?? null,
                    $input['nationality'] ?? null,
                    $input['export_country'] ?? null,
                    $input['address'] ?? null,
                    $input['passport_info'] ?? null,
                    $input['bank_name'] ?? null,
                    $input['account_number'] ?? null,
                    $input['account_holder'] ?? null,
                    $input['swift_code'] ?? null,
                    $input['assigned_sales'] ?? null,
                    $input['status'] ?? 'active',
                    $id
                ]);

                successResponse(null, '바이어 정보가 수정되었습니다.');
            } catch (Exception $e) {
                error_log("Customer update error: " . $e->getMessage());
                errorResponse('수정 중 오류가 발생했습니다.');
            }
            break;

        case 'delete':
            $id = $input['id'] ?? null;
            if (!$id) {
                errorResponse('바이어 ID가 필요합니다.');
            }

            try {
                $pdo->beginTransaction();

                // 관련 활동 삭제
                $stmt = $pdo->prepare("DELETE FROM " . CRM_INTL_ACTIVITIES_TABLE . " WHERE customer_id = ?");
                $stmt->execute([$id]);

                // 바이어 삭제
                $stmt = $pdo->prepare("DELETE FROM " . CRM_INTL_CUSTOMERS_TABLE . " WHERE id = ?");
                $stmt->execute([$id]);

                $pdo->commit();
                successResponse(null, '바이어가 삭제되었습니다.');
            } catch (Exception $e) {
                $pdo->rollBack();
                errorResponse('삭제 중 오류가 발생했습니다.');
            }
            break;

        case 'delete_activity':
            $id = $input['id'] ?? null;
            if (!$id) {
                errorResponse('활동 ID가 필요합니다.');
            }

            try {
                // 본인이 작성한 활동만 삭제 가능
                $stmt = $pdo->prepare("DELETE FROM " . CRM_INTL_ACTIVITIES_TABLE . " WHERE id = ? AND created_by = ?");
                $stmt->execute([$id, $currentUser['crm_user_id']]);

                if ($stmt->rowCount() === 0) {
                    errorResponse('활동을 찾을 수 없거나 삭제 권한이 없습니다.');
                }

                successResponse(null, '활동이 삭제되었습니다.');
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
