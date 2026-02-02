<?php
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = intval($data['id'] ?? 0);
    $status = $data['status'] ?? '';

    $valid_status = ['pending', 'confirmed', 'cancelled', 'completed'];
    if (!in_array($status, $valid_status)) {
        throw new Exception('유효하지 않은 상태값입니다.');
    }

    $conn = new PDO("mysql:host=localhost;dbname=sunilshipping;charset=utf8mb4", "sunilshipping", "sunil123!");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // updated_at 없이 status만 업데이트
    $stmt = $conn->prepare("UPDATE booking_requests SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);

    $response['success'] = true;
    $response['message'] = '상태가 변경되었습니다.';
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
