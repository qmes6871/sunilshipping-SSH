<?php
session_start();
require_once 'config.php';

// 로그인 체크 - username을 customer_id로 사용
if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
    header('Location: ../login/login.php');
    exit;
}

$customer_id = $_SESSION['username'];

// GET 파라미터 확인
if (!isset($_GET['doc_id'])) {
    header('Location: index.php');
    exit;
}

$doc_id = (int)$_GET['doc_id'];

try {
    global $pdo;
    if (!$pdo) {
        throw new Exception('DB 연결 오류');
    }

    // 서류 정보 조회
    $stmt = $pdo->prepare("
        SELECT doc_id, file_name, file_path, file_size, original_name
        FROM customer_documents
        WHERE doc_id = ? AND customer_id = ?
    ");
    $stmt->execute([$doc_id, $customer_id]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$document) {
        throw new Exception('서류를 찾을 수 없거나 권한이 없습니다.');
    }

    $file_path = __DIR__ . '/' . $document['file_path'];

    // 파일 존재 확인
    if (!file_exists($file_path)) {
        throw new Exception('파일이 서버에 존재하지 않습니다.');
    }

    // 파일 다운로드 헤더 설정
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $document['original_name'] . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Expires: 0');

    // 파일 출력
    readfile($file_path);
    exit;

} catch (Exception $e) {
    // 오류 페이지로 리다이렉트 또는 오류 메시지 표시
    $_SESSION['upload_message'] = '다운로드 중 오류가 발생했습니다: ' . $e->getMessage();
    $_SESSION['upload_status'] = 'error';
    header('Location: index.php');
    exit;
}
?>
