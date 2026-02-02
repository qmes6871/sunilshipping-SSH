<?php
session_start();

// 데이터베이스 연결
try {
    $conn = new PDO(
        "mysql:host=localhost;dbname=sunilshipping;charset=utf8mb4",
        "sunilshipping",
        "sunil123!",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("데이터베이스 연결 실패: " . $e->getMessage());
}

$message = '';
$success = false;

// 상품 ID 확인
$product_id = $_GET['product_id'] ?? null;
if (!$product_id) {
    header('Location: index.php');
    exit;
}

// 상품 정보 및 담당자 정보 조회 (주 담당자 + 부담당자 1, 2)
try {
    $stmt = $conn->prepare("
        SELECT sp.*,
               sm.id AS staff_id,
               sm.name AS staff_name,
               sm.email AS staff_email,
               sm.phone AS staff_phone,
               sm.mobile AS staff_mobile,
               sm.position AS staff_position,
               sm.department AS staff_department,
               sm.office_location AS staff_office_location,
               sm.photo_url AS staff_photo_url,
               sm2.id AS sub_staff_id_1,
               sm2.name AS sub_staff_name_1,
               sm2.email AS sub_staff_email_1,
               sm2.phone AS sub_staff_phone_1,
               sm2.mobile AS sub_staff_mobile_1,
               sm2.position AS sub_staff_position_1,
               sm2.department AS sub_staff_department_1,
               sm2.office_location AS sub_staff_office_location_1,
               sm2.photo_url AS sub_staff_photo_url_1,
               sm3.id AS sub_staff_id_2,
               sm3.name AS sub_staff_name_2,
               sm3.email AS sub_staff_email_2,
               sm3.phone AS sub_staff_phone_2,
               sm3.mobile AS sub_staff_mobile_2,
               sm3.position AS sub_staff_position_2,
               sm3.department AS sub_staff_department_2,
               sm3.office_location AS sub_staff_office_location_2,
               sm3.photo_url AS sub_staff_photo_url_2
        FROM shipping_products sp
        LEFT JOIN staff_management sm ON sp.staff_id = sm.id
        LEFT JOIN staff_management sm2 ON sp.sub_staff_id_1 = sm2.id
        LEFT JOIN staff_management sm3 ON sp.sub_staff_id_2 = sm3.id
        WHERE sp.id = ? AND sp.status = 'active'
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header('Location: index.php');
        exit;
    }

    // 주 담당자 정보 추출
    $staff = null;
    if (!empty($product['staff_id'])) {
        $staff = [
            'id' => $product['staff_id'],
            'name' => $product['staff_name'] ?? '',
            'email' => $product['staff_email'] ?? '',
            'phone' => $product['staff_phone'] ?? '',
            'mobile' => $product['staff_mobile'] ?? '',
            'position' => $product['staff_position'] ?? '',
            'department' => $product['staff_department'] ?? '',
            'office_location' => $product['staff_office_location'] ?? '',
            'photo_url' => $product['staff_photo_url'] ?? ''
        ];
    }

    // 부담당자 1 정보 추출
    $sub_staff_1 = null;
    if (!empty($product['sub_staff_id_1'])) {
        $sub_staff_1 = [
            'id' => $product['sub_staff_id_1'],
            'name' => $product['sub_staff_name_1'] ?? '',
            'email' => $product['sub_staff_email_1'] ?? '',
            'phone' => $product['sub_staff_phone_1'] ?? '',
            'mobile' => $product['sub_staff_mobile_1'] ?? '',
            'position' => $product['sub_staff_position_1'] ?? '',
            'department' => $product['sub_staff_department_1'] ?? '',
            'office_location' => $product['sub_staff_office_location_1'] ?? '',
            'photo_url' => $product['sub_staff_photo_url_1'] ?? ''
        ];
    }

    // 부담당자 2 정보 추출
    $sub_staff_2 = null;
    if (!empty($product['sub_staff_id_2'])) {
        $sub_staff_2 = [
            'id' => $product['sub_staff_id_2'],
            'name' => $product['sub_staff_name_2'] ?? '',
            'email' => $product['sub_staff_email_2'] ?? '',
            'phone' => $product['sub_staff_phone_2'] ?? '',
            'mobile' => $product['sub_staff_mobile_2'] ?? '',
            'position' => $product['sub_staff_position_2'] ?? '',
            'department' => $product['sub_staff_department_2'] ?? '',
            'office_location' => $product['sub_staff_office_location_2'] ?? '',
            'photo_url' => $product['sub_staff_photo_url_2'] ?? ''
        ];
    }
} catch (Exception $e) {
    die("상품 조회 실패: " . $e->getMessage());
}

// 예약 처리 - booking_requests 테이블의 정확한 컬럼에 맞춤
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // 폼 데이터 수집
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $nationality = trim($_POST['nationality'] ?? '');
        $special_requirements = trim($_POST['special_requirements'] ?? '');


        // 필수 필드 검증
        if (empty($name) || empty($email) || empty($phone)) {
            throw new Exception('이름, 이메일, 전화번호는 필수 항목입니다.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('올바른 이메일 주소를 입력해주세요.');
        }

        // 추가 필드 검증 (선택사항)
        if (!empty($valid_until) && !strtotime($valid_until)) {
            throw new Exception('올바른 유효기간 날짜를 입력해주세요.');
        }

        // 예약 참조번호 생성
        $booking_reference = 'BK' . date('Ymd') . sprintf('%04d', rand(1000, 9999));

        // booking_requests 테이블에 예약 저장
        $stmt = $conn->prepare("
            INSERT INTO booking_requests
            (product_id, ship_id, name, email, phone, customer_name, customer_email,
             customer_phone, nationality, special_requirements, booking_reference, status, payment_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')
        ");

        // name, email, phone을 customer_name, customer_email, customer_phone에도 동일하게 저장
        $stmt->execute([
            $product_id,           // product_id
            $product_id,           // ship_id (동일하게 설정)
            $name,                 // name
            $email,                // email
            $phone,                // phone
            $name,                 // customer_name (동일한 값)
            $email,                // customer_email (동일한 값)
            $phone,                // customer_phone (동일한 값)
            $nationality,          // nationality (국적/회사)
            $special_requirements, // special_requirements (특이사항)
            $booking_reference     // booking_reference (예약번호)
        ]);

        // 등급 적용가 저장 (total_price 컬럼이 있을 경우)
        try {
            $chk = $conn->query("SHOW COLUMNS FROM booking_requests LIKE 'total_price'");
            if ($chk->fetch(PDO::FETCH_ASSOC)) {
                $stmt2 = $conn->prepare("UPDATE booking_requests SET total_price = ? WHERE booking_reference = ?");
                $stmt2->execute([$display_price, $booking_reference]);
            }
        } catch (Exception $e5) { /* ignore */ }
        
        // 성공 처리
        $success = true;
        $message = '예약이 성공적으로 완료되었습니다!';
        $booking_ref = $booking_reference;

        // 성공 페이지에서 담당자 정보와 함께 결과 표시
        // 리디렉션 없이 현재 페이지에서 성공 모달 표시

    } catch (Exception $e) {
        $message = $e->getMessage();
    }
}
// 로그인 상태 확인 및 사용자 정보 가져오기
$is_logged_in = isset($_SESSION['username']) && !empty($_SESSION['username']);
$user_info = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'nationality' => ''
];

if ($is_logged_in) {
    $username = $_SESSION['username'];
    $user_info['name'] = $_SESSION['name'] ?? $username;

    // customer_management 테이블에서 사용자 정보 가져오기
    // customer_id 또는 username으로 매칭
    try {
        $stmt_customer = $conn->prepare("
            SELECT name, email, phone, nationality
            FROM customer_management
            WHERE customer_id = ? OR username = ?
            LIMIT 1
        ");
        $stmt_customer->execute([$username, $username]);
        $customer_data = $stmt_customer->fetch(PDO::FETCH_ASSOC);

        if ($customer_data) {
            // customer_management 테이블의 정확한 컬럼명 사용
            if (!empty($customer_data['name'])) {
                $user_info['name'] = $customer_data['name'];
            }
            if (!empty($customer_data['email'])) {
                $user_info['email'] = $customer_data['email'];
            }
            if (!empty($customer_data['phone'])) {
                $user_info['phone'] = $customer_data['phone'];
            }
            if (!empty($customer_data['nationality'])) {
                $user_info['nationality'] = $customer_data['nationality'];
            }
        }
    } catch (Exception $e) {
        // customer_management 테이블 실패 시 무시
    }

    // g5_member 테이블에서도 정보 가져오기 (보조)
    if (empty($user_info['email']) || empty($user_info['phone'])) {
        try {
            $stmt_user = $conn->prepare("
                SELECT mb_email, mb_hp, mb_tel, mb_phone
                FROM g5_member
                WHERE mb_id = ?
                LIMIT 1
            ");
            $stmt_user->execute([$username]);
            $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);

            if ($user_data) {
                if (empty($user_info['email']) && !empty($user_data['mb_email'])) {
                    $user_info['email'] = $user_data['mb_email'];
                }
                if (empty($user_info['phone'])) {
                    $user_info['phone'] = $user_data['mb_hp'] ?? $user_data['mb_tel'] ?? $user_data['mb_phone'] ?? '';
                }
            }
        } catch (Exception $e) {
            // g5_member 테이블 실패 시 무시
        }
    }
}

// 등급별 가격 산정 (렌더링 전에 계산)
$user_grade = 'silver';
try {
    $memberEmail = $user_info['email'] ?: ($_SESSION['mb_email'] ?? ($_SESSION['customer_email'] ?? null));
    $memberId = $_SESSION['ss_mb_id'] ?? ($_SESSION['username'] ?? null);
    if (isset($GLOBALS['member']) && is_array($GLOBALS['member'])) {
        $memberEmail = $GLOBALS['member']['mb_email'] ?? $memberEmail;
        $memberId = $GLOBALS['member']['mb_id'] ?? $memberId;
    }
    if (!$memberEmail && $memberId) {
        try { $stmtTmp = $conn->prepare("SELECT mb_email FROM g5_member WHERE mb_id = ? LIMIT 1"); $stmtTmp->execute([$memberId]); $memberEmail = $stmtTmp->fetchColumn() ?: null; } catch (Exception $ign) {}
    }
    if ($memberEmail) {
        try { $stmtGrade = $conn->prepare("SELECT grade FROM customer_management WHERE email = ? LIMIT 1"); $stmtGrade->execute([$memberEmail]); $g = $stmtGrade->fetchColumn(); if ($g) { $user_grade = strtolower($g); } } catch (Exception $ign2) {}
    }
} catch (Exception $ignore) {}

$display_price = (float)($product['price'] ?? 0);
switch ($user_grade) {
    case 'vvip': if (!empty($product['price_vvip'])) $display_price = (float)$product['price_vvip']; break;
    case 'vip': if (!empty($product['price_vip'])) $display_price = (float)$product['price_vip']; break;
    case 'gold': if (!empty($product['price_gold'])) $display_price = (float)$product['price_gold']; break;
    case 'silver': if (!empty($product['price_silver'])) $display_price = (float)$product['price_silver']; break;
}

// 렌더링 일관성을 위해 기본 가격에 덮어쓰기
$product['price'] = $display_price;

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>예약하기 - SUNIL SHIPPING</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Apple SD Gothic Neo', 'Malgun Gothic', sans-serif; background: #f8f9fa; }
        
        /* 헤더 스타일 */
        .header {
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid #e5e7eb;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem 2rem;
        }

        .logo {
            font-size: 1.6rem;
            font-weight: 700;
            color: #2563eb;
            text-decoration: none;
            letter-spacing: -0.5px;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 3rem;
            margin: 0;
            padding: 0;
        }

        .nav-menu a {
            color: #6b7280;
            text-decoration: none;
            font-weight: 500;
            font-size: 1rem;
            padding: 0.5rem 0;
            transition: all 0.3s ease;
        }

        .nav-menu a:hover { color: #374151; }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .user-info {
            color: #6b7280;
            font-weight: 500;
            font-size: 0.9rem;
            margin-right: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .btn {
            padding: 0.6rem 1.4rem;
            border: 1.5px solid #cbd5e1;
            background: white;
            color: #64748b;
            text-decoration: none;
            font-weight: 500;
            border-radius: 25px;
            transition: all 0.3s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            font-size: 0.9rem;
            white-space: nowrap;
            min-width: fit-content;
        }

        .btn:hover {
            background: #f8fafc;
            border-color: #94a3b8;
            color: #475569;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn-primary {
            background: #dc2626;
            border-color: #dc2626;
            color: white;
            font-weight: 600;
        }

        .btn-primary:hover {
            background: #b91c1c;
            border-color: #b91c1c;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 3px 6px rgba(220, 38, 38, 0.3);
        }
        
        .page-header {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            color: white;
            padding: 2rem 0;
            text-align: center;
        }
        
        .page-header h1 { 
            font-size: 2rem; 
            margin-bottom: 0.5rem; 
        }
        
        .container {
            max-width: 800px;
            margin: -20px auto 40px;
            padding: 0 20px;
        }
        
        .back-btn {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 25px;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-1px);
        }
        
        .product-info {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .product-title {
            font-size: 1.5rem;
            color: #2c3e50;
            margin-bottom: 1rem;
            font-weight: 700;
        }
        
        .product-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .detail-item {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .detail-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        
        .detail-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-container {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .form-title {
            font-size: 1.3rem;
            color: #2c3e50;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 600;
        }
        
        .form-section {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            background: #f9fafb;
        }
        
        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 1rem;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 0.5rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }
        
        .required { color: #dc2626; }
        
        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        /* 읽기 전용 필드 스타일 */
        .readonly-field {
            background-color: #f8f9fa;
            border-color: #e9ecef;
            color: #495057;
            cursor: not-allowed;
            opacity: 0.8;
        }

        .readonly-field:focus {
            border-color: #e9ecef;
            box-shadow: none;
            cursor: not-allowed;
        }
        
        .submit-btn {
            width: 100%;
            background: linear-gradient(135deg, #2563eb, #1e40af);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .submit-btn:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
            font-weight: 600;
        }
        
        .success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        /* 예약 완료 모달 스타일 */
        .success-modal {
            background: white;
            border-radius: 12px;
            padding: 2.5rem;
            margin: 2rem auto;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 600px;
            text-align: center;
            border-top: 5px solid #10b981;
        }

        .success-icon {
            font-size: 4rem;
            color: #10b981;
            margin-bottom: 1rem;
            animation: checkmark 0.5s ease-in-out;
        }

        @keyframes checkmark {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); opacity: 1; }
        }

        .success-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1rem;
        }

        .booking-reference {
            background: #f0fdf4;
            border: 2px solid #86efac;
            border-radius: 8px;
            padding: 1rem;
            margin: 1.5rem 0;
        }

        .booking-reference-label {
            font-size: 0.9rem;
            color: #6b7280;
            margin-bottom: 0.3rem;
        }

        .booking-reference-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #059669;
            letter-spacing: 2px;
        }

        .contact-info {
            background: #f9fafb;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 1.5rem;
            text-align: left;
        }

        .contact-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 1rem;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            background: white;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
        }

        .contact-icon {
            width: 40px;
            height: 40px;
            background: #2563eb;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .contact-details {
            flex: 1;
        }

        .contact-label {
            font-size: 0.85rem;
            color: #6b7280;
            margin-bottom: 0.2rem;
        }

        .contact-value {
            font-size: 1rem;
            font-weight: 600;
            color: #1f2937;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn-action {
            flex: 1;
            padding: 0.9rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-primary-action {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            color: white;
            border: none;
        }

        .btn-primary-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .btn-secondary-action {
            background: white;
            color: #6b7280;
            border: 2px solid #e5e7eb;
        }

        .btn-secondary-action:hover {
            background: #f9fafb;
            border-color: #d1d5db;
            transform: translateY(-1px);
        }

        .success-note {
            margin-top: 1.5rem;
            padding: 1rem;
            background: #fffbeb;
            border: 1px solid #fbbf24;
            border-radius: 8px;
            font-size: 0.9rem;
            color: #92400e;
            line-height: 1.6;
        }

        /* 햄버거 메뉴 버튼 */
        .mobile-menu-toggle {
            display: none;
            flex-direction: column;
            justify-content: space-around;
            width: 30px;
            height: 25px;
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 0;
            z-index: 10;
        }

        .mobile-menu-toggle span {
            width: 30px;
            height: 3px;
            background: #2563eb;
            border-radius: 3px;
            transition: all 0.3s ease;
        }

        .mobile-menu-toggle.active span:nth-child(1) {
            transform: rotate(45deg) translate(8px, 8px);
        }

        .mobile-menu-toggle.active span:nth-child(2) {
            opacity: 0;
        }

        .mobile-menu-toggle.active span:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -7px);
        }

        /* 모바일 메뉴 */
        .mobile-nav {
            display: none;
            position: fixed;
            top: 60px;
            left: 0;
            width: 100%;
            background: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 999;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .mobile-nav.active {
            max-height: 500px;
            padding: 1rem 0;
        }

        .mobile-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .mobile-nav ul li {
            border-bottom: 1px solid #e5e7eb;
        }

        .mobile-nav ul li a {
            display: block;
            padding: 1rem 2rem;
            color: #374151;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .mobile-nav ul li a:hover {
            background: #f3f4f6;
            color: #2563eb;
        }

        .mobile-nav-actions {
            padding: 1rem 2rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .mobile-nav-actions .btn {
            width: 100%;
            text-align: center;
        }

        /* 버튼 스타일 추가 */
        .btn-outline {
            padding: 0.6rem 1.4rem;
            border: 1.5px solid #cbd5e1;
            background: white;
            color: #64748b;
            text-decoration: none;
            font-weight: 500;
            border-radius: 25px;
            transition: all 0.3s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            font-size: 0.9rem;
        }

        .btn-outline:hover {
            background: #f8fafc;
            border-color: #94a3b8;
            color: #475569;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn-danger {
            padding: 0.6rem 1.4rem;
            background: #dc2626;
            border: 1.5px solid #dc2626;
            color: white;
            text-decoration: none;
            font-weight: 600;
            border-radius: 25px;
            transition: all 0.3s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            font-size: 0.9rem;
        }

        .btn-danger:hover {
            background: #b91c1c;
            border-color: #b91c1c;
            transform: translateY(-1px);
            box-shadow: 0 3px 6px rgba(220, 38, 38, 0.3);
        }

        .me-2 {
            margin-right: 0.5rem;
        }

        /* 모바일 반응형 */
        @media (max-width: 768px) {
            .nav-menu, .nav-actions { display: none; }
            .mobile-menu-toggle { display: flex; }
            .mobile-nav { display: block; }
            .nav-container { padding: 0.8rem 1rem; justify-content: space-between; }
            .logo { font-size: 1.4rem; }
            .container { padding: 0 15px; }
            .product-details { grid-template-columns: 1fr; }
            .form-row { grid-template-columns: 1fr; }
            .page-header { padding: 1.5rem 0; }
            .page-header h1 { font-size: 1.6rem; }
            .success-modal { padding: 1.5rem; }
            .success-icon { font-size: 3rem; }
            .success-title { font-size: 1.4rem; }
            .booking-reference-value { font-size: 1.2rem; }
            .action-buttons { flex-direction: column; }
        }

        @media (min-width: 1200px) {
            .nav-menu { gap: 4rem; }
            .nav-container { padding: 0.8rem 3rem; }
        }
    </style>
</head>
<body>
    <!-- 헤더 -->
    <header class="header">
        <nav class="nav-container">
            <a href="https://sunilshipping.mycafe24.com" class="logo">SUNIL SHIPPING</a>

            <!-- 데스크톱 메뉴 -->
            <ul class="nav-menu">
                <li><a href="https://sunilshipping.mycafe24.com">HOME</a></li>
                <li><a href="../reserve/index.php">LOGISTIC</a></li>
                <li><a href="../tradecar/index.php">TRADE CAR</a></li>
                <li><a href="../auction/index.php">AUCTION</a></li>
                <li><a href="../mypage/index.php">MY PAGE</a></li>
            </ul>

            <!-- 햄버거 메뉴 버튼 -->
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <!-- 데스크톱 액션 버튼 -->
            <div class="nav-actions">
                <?php if ($is_logged_in): ?>
                    <span class="user-info">
                        <i class="fas fa-user me-2"></i><?= htmlspecialchars($user_info['name']) ?>
                    </span>
                    <a href="../login/edit.php" class="btn btn-outline">회원정보수정</a>
                    <a href="../login/logout.php" class="btn btn-danger">로그아웃</a>
                <?php else: ?>
                    <a href="../login/login.php" class="btn btn-outline">로그인</a>
                    <a href="../login/signup.php" class="btn btn-primary">회원가입</a>
                <?php endif; ?>
            </div>
        </nav>

        <!-- 모바일 메뉴 -->
        <nav class="mobile-nav" id="mobileNav">
            <ul>
                <li><a href="https://sunilshipping.mycafe24.com" onclick="closeMobileMenu()">HOME</a></li>
                <li><a href="../reserve/index.php" onclick="closeMobileMenu()">LOGISTIC</a></li>
                <li><a href="../tradecar/index.php" onclick="closeMobileMenu()">TRADE CAR</a></li>
                <li><a href="../auction/index.php" onclick="closeMobileMenu()">AUCTION</a></li>
                <li><a href="../mypage/index.php" onclick="closeMobileMenu()">MY PAGE</a></li>
            </ul>

            <div class="mobile-nav-actions">
                <?php if ($is_logged_in): ?>
                    <div style="color: #4a5568; margin-bottom: 1rem; text-align: center;">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($user_info['name']) ?>
                    </div>
                    <a href="../login/edit.php" class="btn btn-outline">회원정보수정</a>
                    <a href="../login/logout.php" class="btn btn-danger">로그아웃</a>
                <?php else: ?>
                    <a href="../login/login.php" class="btn btn-outline">로그인</a>
                    <a href="../login/signup.php" class="btn btn-primary">회원가입</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <div class="page-header">
        <div class="container">
            <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> 목록으로</a>
            <h1>운송 예약</h1>
            <p>안전하고 신속한 해상운송 서비스</p>
        </div>
    </div>

    <div class="container">
        <?php if ($success && isset($booking_ref)): ?>
            <!-- 예약 완료 모달 -->
            <div class="success-modal">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="success-title">예약이 완료되었습니다!</div>
                <p style="color: #6b7280; margin-bottom: 1rem;">
                    예약 신청이 성공적으로 접수되었습니다.<br>
                    담당자가 확인 후 연락드리겠습니다.
                </p>

                <!-- 예약번호 -->
                <div class="booking-reference">
                    <div class="booking-reference-label">예약 참조번호</div>
                    <div class="booking-reference-value"><?= htmlspecialchars($booking_ref) ?></div>
                </div>

                <!-- 담당자 정보 (명함 스타일) -->
                <div style="margin-top: 2rem;">
                    <div style="font-size: 1.1rem; font-weight: 700; color: #1f2937; margin-bottom: 1rem;">
                        <i class="fas fa-address-card"></i> 담당자 연락처
                    </div>

                    <?php if ($staff && !empty($staff['name'])): ?>
                        <!-- 담당자 - 명함 스타일 -->
                        <div style="background: white; border-radius: 8px; padding: 1rem; margin-bottom: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 3px solid #2563eb; display: flex; align-items: center; gap: 1rem;">
                            <?php if (!empty($staff['photo_url'])): ?>
                            <div style="flex-shrink: 0;">
                                <img src="../<?= htmlspecialchars($staff['photo_url']) ?>"
                                     alt="<?= htmlspecialchars($staff['name']) ?>"
                                     style="width: 60px; height: 60px; border-radius: 8px; object-fit: cover; border: 2px solid #e5e7eb;">
                            </div>
                            <?php endif; ?>
                            <div style="flex-grow: 1; min-width: 0;">
                                <div style="font-weight: 700; font-size: 0.95rem; color: #1f2937; margin-bottom: 0.25rem;">
                                    <?= htmlspecialchars($staff['name']) ?>
                                    <?php if (!empty($staff['position'])): ?>
                                        <span style="font-weight: 500; color: #6b7280; font-size: 0.85rem;">  |  <?= htmlspecialchars($staff['position']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size: 0.8rem; color: #4b5563; margin-bottom: 0.25rem;">
                                    <?php if (!empty($staff['email'])): ?>
                                        <i class="fas fa-envelope" style="width: 14px;"></i>
                                        <a href="mailto:<?= htmlspecialchars($staff['email']) ?>" style="color: #2563eb; text-decoration: none;">
                                            <?= htmlspecialchars($staff['email']) ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size: 0.8rem; color: #4b5563;">
                                    <?php if (!empty($staff['mobile'])): ?>
                                        <i class="fas fa-mobile-alt" style="width: 14px;"></i> <?= htmlspecialchars($staff['mobile']) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($staff['phone'])): ?>
                                        <?php if (!empty($staff['mobile'])): ?> <span style="color: #d1d5db;">|</span> <?php endif; ?>
                                        <i class="fas fa-phone" style="width: 14px;"></i> <?= htmlspecialchars($staff['phone']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- 부담당자 -->
                        <?php if (($sub_staff_1 && !empty($sub_staff_1['name'])) || ($sub_staff_2 && !empty($sub_staff_2['name']))): ?>
                            <div style="margin-top: 1.5rem; margin-bottom: 0.5rem;">
                                <div style="font-size: 0.9rem; font-weight: 600; color: #047857;">
                                    <i class="fas fa-users"></i> 부 담당자
                                </div>
                            </div>

                            <?php if ($sub_staff_1 && !empty($sub_staff_1['name'])): ?>
                            <div style="background: white; border-radius: 8px; padding: 1rem; margin-bottom: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 3px solid #10b981; display: flex; align-items: center; gap: 1rem;">
                                <?php if (!empty($sub_staff_1['photo_url'])): ?>
                                <div style="flex-shrink: 0;">
                                    <img src="../<?= htmlspecialchars($sub_staff_1['photo_url']) ?>"
                                         alt="<?= htmlspecialchars($sub_staff_1['name']) ?>"
                                         style="width: 55px; height: 55px; border-radius: 8px; object-fit: cover; border: 2px solid #e5e7eb;">
                                </div>
                                <?php endif; ?>
                                <div style="flex-grow: 1; min-width: 0;">
                                    <div style="font-weight: 700; font-size: 0.9rem; color: #1f2937; margin-bottom: 0.25rem;">
                                        <?= htmlspecialchars($sub_staff_1['name']) ?>
                                        <?php if (!empty($sub_staff_1['position'])): ?>
                                            <span style="font-weight: 500; color: #6b7280; font-size: 0.8rem;">  |  <?= htmlspecialchars($sub_staff_1['position']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: #4b5563; margin-bottom: 0.25rem;">
                                        <?php if (!empty($sub_staff_1['email'])): ?>
                                            <i class="fas fa-envelope" style="width: 14px;"></i>
                                            <a href="mailto:<?= htmlspecialchars($sub_staff_1['email']) ?>" style="color: #2563eb; text-decoration: none;">
                                                <?= htmlspecialchars($sub_staff_1['email']) ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: #4b5563;">
                                        <?php if (!empty($sub_staff_1['mobile'])): ?>
                                            <i class="fas fa-mobile-alt" style="width: 14px;"></i> <?= htmlspecialchars($sub_staff_1['mobile']) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($sub_staff_1['phone'])): ?>
                                            <?php if (!empty($sub_staff_1['mobile'])): ?> <span style="color: #d1d5db;">|</span> <?php endif; ?>
                                            <i class="fas fa-phone" style="width: 14px;"></i> <?= htmlspecialchars($sub_staff_1['phone']) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($sub_staff_2 && !empty($sub_staff_2['name'])): ?>
                            <div style="background: white; border-radius: 8px; padding: 1rem; margin-bottom: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 3px solid #10b981; display: flex; align-items: center; gap: 1rem;">
                                <?php if (!empty($sub_staff_2['photo_url'])): ?>
                                <div style="flex-shrink: 0;">
                                    <img src="../<?= htmlspecialchars($sub_staff_2['photo_url']) ?>"
                                         alt="<?= htmlspecialchars($sub_staff_2['name']) ?>"
                                         style="width: 55px; height: 55px; border-radius: 8px; object-fit: cover; border: 2px solid #e5e7eb;">
                                </div>
                                <?php endif; ?>
                                <div style="flex-grow: 1; min-width: 0;">
                                    <div style="font-weight: 700; font-size: 0.9rem; color: #1f2937; margin-bottom: 0.25rem;">
                                        <?= htmlspecialchars($sub_staff_2['name']) ?>
                                        <?php if (!empty($sub_staff_2['position'])): ?>
                                            <span style="font-weight: 500; color: #6b7280; font-size: 0.8rem;">  |  <?= htmlspecialchars($sub_staff_2['position']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: #4b5563; margin-bottom: 0.25rem;">
                                        <?php if (!empty($sub_staff_2['email'])): ?>
                                            <i class="fas fa-envelope" style="width: 14px;"></i>
                                            <a href="mailto:<?= htmlspecialchars($sub_staff_2['email']) ?>" style="color: #2563eb; text-decoration: none;">
                                                <?= htmlspecialchars($sub_staff_2['email']) ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: #4b5563;">
                                        <?php if (!empty($sub_staff_2['mobile'])): ?>
                                            <i class="fas fa-mobile-alt" style="width: 14px;"></i> <?= htmlspecialchars($sub_staff_2['mobile']) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($sub_staff_2['phone'])): ?>
                                            <?php if (!empty($sub_staff_2['mobile'])): ?> <span style="color: #d1d5db;">|</span> <?php endif; ?>
                                            <i class="fas fa-phone" style="width: 14px;"></i> <?= htmlspecialchars($sub_staff_2['phone']) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <!-- 운영시간 -->
                        <div style="background: #f9fafb; border-radius: 6px; padding: 0.75rem 1rem; margin-top: 1rem; text-align: center;">
                            <span style="font-size: 0.85rem; color: #6b7280;">
                                <i class="fas fa-clock"></i> 운영시간: <strong style="color: #1f2937;">평일 09:00 - 18:00</strong>
                            </span>
                        </div>
                    <?php else: ?>
                        <!-- 담당자가 배정되지 않은 경우 기본 정보 표시 -->
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="contact-details">
                                <div class="contact-label">담당자</div>
                                <div class="contact-value">선일해운 예약팀</div>
                            </div>
                        </div>

                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="contact-details">
                                <div class="contact-label">전화번호</div>
                                <div class="contact-value">02-1234-5678</div>
                            </div>
                        </div>

                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-details">
                                <div class="contact-label">이메일</div>
                                <div class="contact-value">
                                    <a href="mailto:booking@sunilshipping.com"
                                       style="color: #2563eb; text-decoration: none;">
                                        booking@sunilshipping.com
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="contact-details">
                                <div class="contact-label">운영시간</div>
                                <div class="contact-value">평일 09:00 - 18:00</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="success-note">
                    <i class="fas fa-info-circle"></i>
                    <strong>안내사항:</strong> 예약 확인 및 결제 안내는 영업일 기준 1-2일 이내에 연락드립니다.
                    급하신 경우 위 연락처로 문의해주세요.
                </div>

                <!-- 액션 버튼 -->
                <div class="action-buttons">
                    <a href="index.php" class="btn-action btn-primary-action">
                        <i class="fas fa-list"></i> 예약 목록으로
                    </a>
                    <a href="https://sunilshipping.mycafe24.com/" class="btn-action btn-secondary-action">
                        <i class="fas fa-home"></i> 홈으로
                    </a>
                </div>
            </div>
        <?php elseif ($message && !$success): ?>
            <div class="message error">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- 디버그 정보 (임시) -->
        <?php if ($is_logged_in && isset($_GET['debug'])): ?>
            <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 1rem; margin-bottom: 1rem; border-radius: 8px;">
                <strong>디버그 정보:</strong><br>
                로그인 상태: <?= $is_logged_in ? '예' : '아니오' ?><br>
                사용자명: <?= htmlspecialchars($username ?? 'N/A') ?><br>
                이름: <?= htmlspecialchars($user_info['name']) ?><br>
                이메일: <?= htmlspecialchars($user_info['email']) ?: '비어있음' ?><br>
                전화번호: <?= htmlspecialchars($user_info['phone']) ?: '비어있음' ?><br>
                국적/회사: <?= htmlspecialchars($user_info['nationality']) ?: '비어있음' ?><br>
            </div>
        <?php endif; ?>

        <?php if (!$success || !isset($booking_ref)): ?>
        <!-- 상품 정보 표시 -->
        <div class="product-info">
            <?php if (!empty($product['image_url'])): ?>
                <div style="text-align: center; margin-bottom: 1.5rem;">
                    <img src="../<?= htmlspecialchars($product['image_url']) ?>"
                         alt="<?= htmlspecialchars($product['vessel_name']) ?>"
                         style="max-width: 100%; max-height: 400px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); object-fit: cover;">
                </div>
            <?php endif; ?>

            <div class="product-title"><?= htmlspecialchars($product['product_name']) ?></div>

            <div class="product-details">
                <div class="detail-item">
                    <div class="detail-label">선박명</div>
                    <div class="detail-value"><?= htmlspecialchars($product['vessel_name']) ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">항로</div>
                    <div class="detail-value"><?= htmlspecialchars($product['departure_port']) ?> → <?= htmlspecialchars($product['arrival_port']) ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">소요시간</div>
                    <div class="detail-value"><?= $product['transit_time'] ?>일</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">운송료</div>
                    <div class="detail-value">$<?= number_format($product['price'], 2) ?></div>
                </div>
            </div>

            <?php if ($product['description']): ?>
                <p style="color: #6c757d; margin-top: 1rem;"><?= htmlspecialchars($product['description']) ?></p>
            <?php endif; ?>
        </div>

        <!-- 예약 폼 - 테이블 컬럼에 맞게 수정 -->
        <div class="form-container">
            <div class="form-title">예약 신청서</div>
            
            <form method="POST">
                <!-- 예약자 정보 섹션 (통합) -->
                <div class="form-section">
                    <div class="section-title">예약자 정보</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">이름 <span class="required">*</span></label>
                            <input type="text" name="name" class="form-input" required
                                   value="<?= htmlspecialchars($_POST['name'] ?? $user_info['name']) ?>"
                                   placeholder="이름을 입력하세요">
                        </div>
                        <div class="form-group">
                            <label class="form-label">이메일 <span class="required">*</span></label>
                            <input type="email" name="email" class="form-input" required
                                   value="<?= htmlspecialchars($_POST['email'] ?? $user_info['email']) ?>"
                                   placeholder="example@email.com">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">전화번호 <span class="required">*</span></label>
                            <input type="tel" name="phone" class="form-input" required
                                   value="<?= htmlspecialchars($_POST['phone'] ?? $user_info['phone']) ?>"
                                   placeholder="010-1234-5678">
                        </div>
                        <div class="form-group">
                            <label class="form-label">국적/회사명</label>
                            <input type="text" name="nationality" class="form-input"
                                   value="<?= htmlspecialchars($_POST['nationality'] ?? $user_info['nationality']) ?>"
                                   placeholder="국적 또는 회사명">
                        </div>
                    </div>
                </div>

                <!-- 특별 요청사항 -->
                <div class="form-section">
                    <div class="section-title">특별 요구사항</div>
                    <div class="form-group">
                        <label class="form-label">특이사항 및 요청사항</label>
                        <textarea name="special_requirements" class="form-input" rows="4"
                                  placeholder="화물 종류, 특별 취급 사항, 기타 요청사항을 입력해주세요"><?= htmlspecialchars($_POST['special_requirements'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- 화물 정보 섹션 (읽기 전용) -->
                <div class="form-section">
                    <div class="section-title">화물 상세 정보 <span style="font-size: 0.8em; color: #6b7280; font-weight: normal;">(상품 정보에서 자동 입력)</span></div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">경로 (ROUTE)</label>
                            <input type="text" name="route" class="form-input readonly-field"
                                   value="<?= htmlspecialchars($_POST['route'] ?? $product['route'] ?? '') ?>"
                                   readonly placeholder="출발지 → 도착지">
                        </div>
                        <div class="form-group">
                            <label class="form-label">소요시간 (T/T)</label>
                            <input type="text" name="transit_time" class="form-input readonly-field"
                                   value="<?= htmlspecialchars($_POST['transit_time'] ?? $product['transit_time'] ?? '') ?>"
                                   readonly placeholder="예: 3일">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">무게</label>
                            <input type="text" name="weight" class="form-input readonly-field"
                                   value="<?= htmlspecialchars($_POST['weight'] ?? $product['weight'] ?? '') ?>"
                                   readonly placeholder="예: 20kg, 1CBM">
                        </div>
                        <div class="form-group">
                            <label class="form-label">화물 타입 (TYPE)</label>
                            <input type="text" name="cargo_type" class="form-input readonly-field"
                                   value="<?= htmlspecialchars($_POST['cargo_type'] ?? $product['cargo_type'] ?? '') ?>"
                                   readonly placeholder="FCL, LCL, BULK 등">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">결제 조건 (결제TERM)</label>
                            <input type="text" name="payment_terms" class="form-input readonly-field"
                                   value="<?= htmlspecialchars($_POST['payment_terms'] ?? $product['payment_terms'] ?? '') ?>"
                                   readonly placeholder="예: PREPAID, COLLECT, CREDIT 등">
                        </div>
                        <div class="form-group">
                            <label class="form-label">요율 (RATE)</label>
                            <input type="text" name="rate" class="form-input readonly-field"
                                   value="<?= htmlspecialchars($_POST['rate'] ?? $product['rate'] ?? '') ?>"
                                   readonly placeholder="예: $3,500/20FT">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">유효기간</label>
                            <input type="date" name="valid_until" class="form-input readonly-field"
                                   value="<?= htmlspecialchars($_POST['valid_until'] ?? preg_replace('/\s*\([^)]*\)/', '', $product['valid_until'] ?? '')) ?>"
                                   readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">추가 정보 (기타)</label>
                            <textarea name="additional_info" class="form-input readonly-field" rows="2"
                                      readonly placeholder="추가 정보 및 특이사항을 입력해주세요"><?= htmlspecialchars($_POST['additional_info'] ?? preg_replace('/\s*\([^)]*\)/', '', $product['additional_info'] ?? '')) ?></textarea>
                        </div>
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-paper-plane"></i> INQUIRY
                </button>
            </form>
        </div>

        <!-- 담당자 연락처 (명함 스타일) -->
        <div style="margin-top: 3rem; padding: 0 1rem;">
            <h3 style="font-size: 1.25rem; font-weight: 700; color: #1f2937; margin-bottom: 1rem;">
                <i class="fas fa-address-card"></i> 담당자 연락처
            </h3>

            <?php if ($staff && !empty($staff['name'])): ?>
                <!-- 담당자 1명 - 명함 스타일 -->
                <div style="background: white; border-radius: 8px; padding: 1rem; margin-bottom: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 3px solid #2563eb; display: flex; align-items: center; gap: 1rem;">
                    <!-- 사진 -->
                    <?php if (!empty($staff['photo_url'])): ?>
                    <div style="flex-shrink: 0;">
                        <img src="../<?= htmlspecialchars($staff['photo_url']) ?>"
                             alt="<?= htmlspecialchars($staff['name']) ?>"
                             style="width: 60px; height: 60px; border-radius: 8px; object-fit: cover; border: 2px solid #e5e7eb;">
                    </div>
                    <?php endif; ?>

                    <!-- 정보 -->
                    <div style="flex-grow: 1; min-width: 0;">
                        <div style="font-weight: 700; font-size: 0.95rem; color: #1f2937; margin-bottom: 0.25rem;">
                            <?= htmlspecialchars($staff['name']) ?>
                            <?php if (!empty($staff['position'])): ?>
                                <span style="font-weight: 500; color: #6b7280; font-size: 0.85rem;">  |  <?= htmlspecialchars($staff['position']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size: 0.8rem; color: #4b5563; margin-bottom: 0.25rem;">
                            <?php if (!empty($staff['email'])): ?>
                                <i class="fas fa-envelope" style="width: 14px;"></i>
                                <a href="mailto:<?= htmlspecialchars($staff['email']) ?>" style="color: #2563eb; text-decoration: none;">
                                    <?= htmlspecialchars($staff['email']) ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div style="font-size: 0.8rem; color: #4b5563;">
                            <?php if (!empty($staff['mobile'])): ?>
                                <i class="fas fa-mobile-alt" style="width: 14px;"></i> <?= htmlspecialchars($staff['mobile']) ?>
                            <?php endif; ?>
                            <?php if (!empty($staff['phone'])): ?>
                                <?php if (!empty($staff['mobile'])): ?> <span style="color: #d1d5db;">|</span> <?php endif; ?>
                                <i class="fas fa-phone" style="width: 14px;"></i> <?= htmlspecialchars($staff['phone']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- 다담당자 2명 - 명함 스타일 -->
                <?php if (($sub_staff_1 && !empty($sub_staff_1['name'])) || ($sub_staff_2 && !empty($sub_staff_2['name']))): ?>
                    <div style="margin-top: 1.5rem; margin-bottom: 0.5rem;">
                        <h4 style="font-size: 1rem; font-weight: 600; color: #047857;">
                            <i class="fas fa-users"></i> 다담당자
                        </h4>
                    </div>

                    <!-- 부담당자 1 -->
                    <?php if ($sub_staff_1 && !empty($sub_staff_1['name'])): ?>
                    <div style="background: white; border-radius: 8px; padding: 1rem; margin-bottom: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 3px solid #10b981; display: flex; align-items: center; gap: 1rem;">
                        <!-- 사진 -->
                        <?php if (!empty($sub_staff_1['photo_url'])): ?>
                        <div style="flex-shrink: 0;">
                            <img src="../<?= htmlspecialchars($sub_staff_1['photo_url']) ?>"
                                 alt="<?= htmlspecialchars($sub_staff_1['name']) ?>"
                                 style="width: 55px; height: 55px; border-radius: 8px; object-fit: cover; border: 2px solid #e5e7eb;">
                        </div>
                        <?php endif; ?>

                        <!-- 정보 -->
                        <div style="flex-grow: 1; min-width: 0;">
                            <div style="font-weight: 700; font-size: 0.9rem; color: #1f2937; margin-bottom: 0.25rem;">
                                <?= htmlspecialchars($sub_staff_1['name']) ?>
                                <?php if (!empty($sub_staff_1['position'])): ?>
                                    <span style="font-weight: 500; color: #6b7280; font-size: 0.8rem;">  |  <?= htmlspecialchars($sub_staff_1['position']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size: 0.75rem; color: #4b5563; margin-bottom: 0.25rem;">
                                <?php if (!empty($sub_staff_1['email'])): ?>
                                    <i class="fas fa-envelope" style="width: 14px;"></i>
                                    <a href="mailto:<?= htmlspecialchars($sub_staff_1['email']) ?>" style="color: #2563eb; text-decoration: none;">
                                        <?= htmlspecialchars($sub_staff_1['email']) ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div style="font-size: 0.75rem; color: #4b5563;">
                                <?php if (!empty($sub_staff_1['mobile'])): ?>
                                    <i class="fas fa-mobile-alt" style="width: 14px;"></i> <?= htmlspecialchars($sub_staff_1['mobile']) ?>
                                <?php endif; ?>
                                <?php if (!empty($sub_staff_1['phone'])): ?>
                                    <?php if (!empty($sub_staff_1['mobile'])): ?> <span style="color: #d1d5db;">|</span> <?php endif; ?>
                                    <i class="fas fa-phone" style="width: 14px;"></i> <?= htmlspecialchars($sub_staff_1['phone']) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- 부담당자 2 -->
                    <?php if ($sub_staff_2 && !empty($sub_staff_2['name'])): ?>
                    <div style="background: white; border-radius: 8px; padding: 1rem; margin-bottom: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 3px solid #10b981; display: flex; align-items: center; gap: 1rem;">
                        <!-- 사진 -->
                        <?php if (!empty($sub_staff_2['photo_url'])): ?>
                        <div style="flex-shrink: 0;">
                            <img src="../<?= htmlspecialchars($sub_staff_2['photo_url']) ?>"
                                 alt="<?= htmlspecialchars($sub_staff_2['name']) ?>"
                                 style="width: 55px; height: 55px; border-radius: 8px; object-fit: cover; border: 2px solid #e5e7eb;">
                        </div>
                        <?php endif; ?>

                        <!-- 정보 -->
                        <div style="flex-grow: 1; min-width: 0;">
                            <div style="font-weight: 700; font-size: 0.9rem; color: #1f2937; margin-bottom: 0.25rem;">
                                <?= htmlspecialchars($sub_staff_2['name']) ?>
                                <?php if (!empty($sub_staff_2['position'])): ?>
                                    <span style="font-weight: 500; color: #6b7280; font-size: 0.8rem;">  |  <?= htmlspecialchars($sub_staff_2['position']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div style="font-size: 0.75rem; color: #4b5563; margin-bottom: 0.25rem;">
                                <?php if (!empty($sub_staff_2['email'])): ?>
                                    <i class="fas fa-envelope" style="width: 14px;"></i>
                                    <a href="mailto:<?= htmlspecialchars($sub_staff_2['email']) ?>" style="color: #2563eb; text-decoration: none;">
                                        <?= htmlspecialchars($sub_staff_2['email']) ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div style="font-size: 0.75rem; color: #4b5563;">
                                <?php if (!empty($sub_staff_2['mobile'])): ?>
                                    <i class="fas fa-mobile-alt" style="width: 14px;"></i> <?= htmlspecialchars($sub_staff_2['mobile']) ?>
                                <?php endif; ?>
                                <?php if (!empty($sub_staff_2['phone'])): ?>
                                    <?php if (!empty($sub_staff_2['mobile'])): ?> <span style="color: #d1d5db;">|</span> <?php endif; ?>
                                    <i class="fas fa-phone" style="width: 14px;"></i> <?= htmlspecialchars($sub_staff_2['phone']) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- 운영시간 -->
                <div style="background: #f9fafb; border-radius: 6px; padding: 0.75rem 1rem; margin-top: 1rem; text-align: center;">
                    <span style="font-size: 0.85rem; color: #6b7280;">
                        <i class="fas fa-clock"></i> 운영시간: <strong style="color: #1f2937;">평일 09:00 - 18:00</strong>
                    </span>
                </div>
            <?php else: ?>
                <!-- 담당자가 없을 경우 기본 정보 -->
                <div style="background: white; border-radius: 8px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center;">
                    <div style="margin-bottom: 1rem;">
                        <i class="fas fa-building" style="font-size: 2rem; color: #9ca3af;"></i>
                    </div>
                    <div style="font-weight: 600; font-size: 1rem; color: #1f2937; margin-bottom: 0.5rem;">선일해운 예약팀</div>
                    <div style="font-size: 0.85rem; color: #6b7280; margin-bottom: 0.5rem;">
                        <i class="fas fa-phone"></i> 02-1234-5678
                    </div>
                    <div style="font-size: 0.85rem; color: #6b7280; margin-bottom: 0.5rem;">
                        <i class="fas fa-envelope"></i>
                        <a href="mailto:booking@sunilshipping.com" style="color: #2563eb; text-decoration: none;">
                            booking@sunilshipping.com
                        </a>
                    </div>
                    <div style="font-size: 0.85rem; color: #6b7280;">
                        <i class="fas fa-clock"></i> 평일 09:00 - 18:00
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php endif; ?>
    </div>

    <script>
        // 모바일 메뉴 토글
        function toggleMobileMenu() {
            const mobileNav = document.getElementById('mobileNav');
            const menuToggle = document.querySelector('.mobile-menu-toggle');

            mobileNav.classList.toggle('active');
            menuToggle.classList.toggle('active');
        }

        // 모바일 메뉴 닫기
        function closeMobileMenu() {
            const mobileNav = document.getElementById('mobileNav');
            const menuToggle = document.querySelector('.mobile-menu-toggle');

            mobileNav.classList.remove('active');
            menuToggle.classList.remove('active');
        }

        // 화면 크기 변경 시 모바일 메뉴 자동 닫기
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                closeMobileMenu();
            }
        });

        // 폼 유효성 검사
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const name = document.querySelector('input[name="name"]');
                    const email = document.querySelector('input[name="email"]');
                    const phone = document.querySelector('input[name="phone"]');

                    if (name && !name.value.trim()) {
                        alert('이름을 입력해주세요.');
                        e.preventDefault();
                        name.focus();
                        return false;
                    }

                    if (email && !email.value.trim()) {
                        alert('이메일을 입력해주세요.');
                        e.preventDefault();
                        email.focus();
                        return false;
                    }

                    if (phone && !phone.value.trim()) {
                        alert('전화번호를 입력해주세요.');
                        e.preventDefault();
                        phone.focus();
                        return false;
                    }
                });
            }
        });
    </script>
</body>
</html>
