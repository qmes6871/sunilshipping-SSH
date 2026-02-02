<?php
/**
 * customer_documents 테이블 재생성
 * 인코딩: UTF-8
 */
header('Content-Type: text/html; charset=utf-8');

require_once 'config.php';

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>테이블 수정</title></head><body>";
echo "<h2>customer_documents 테이블 재생성</h2>";

try {
    if (!$pdo) {
        throw new Exception("데이터베이스 연결 실패");
    }

    // 기존 테이블 삭제
    echo "<p>기존 테이블 삭제 중...</p>";
    $pdo->exec("DROP TABLE IF EXISTS customer_documents");
    echo "<p style='color: green;'>✓ 기존 테이블 삭제 완료</p>";

    // 새 테이블 생성
    echo "<p>새 테이블 생성 중...</p>";
    $sql = "CREATE TABLE customer_documents (
        doc_id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id VARCHAR(50) NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_size INT NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        upload_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_customer (customer_id),
        INDEX idx_upload_date (upload_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $pdo->exec($sql);
    echo "<p style='color: green;'>✓ 새 테이블 생성 완료</p>";

    // 테이블 구조 확인
    $stmt = $pdo->query("DESCRIBE customer_documents");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>새 테이블 구조:</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>필드명</th><th>타입</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<br><p style='color: green; font-weight: bold;'>✓ 완료! 이제 파일 업로드를 시도해보세요.</p>";
    echo "<br><a href='test_upload.php'>테스트 페이지로 이동</a>";
    echo "<br><a href='index.php'>메인 페이지로 이동</a>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ DB 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>
