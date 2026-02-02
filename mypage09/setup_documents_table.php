<?php
/**
 * customer_documents 테이블 자동 생성 스크립트
 * 인코딩: UTF-8
 */
header('Content-Type: text/html; charset=utf-8');

require_once 'config.php';

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>테이블 생성</title></head><body>";
echo "<h2>customer_documents 테이블 생성 스크립트</h2>";

try {
    if (!$pdo) {
        throw new Exception("데이터베이스 연결 실패");
    }

    // 테이블 생성 SQL
    $sql = "CREATE TABLE IF NOT EXISTS customer_documents (
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
    echo "<p style='color: green;'>✓ customer_documents 테이블이 성공적으로 생성되었습니다.</p>";

    // 테이블 구조 확인
    $stmt = $pdo->query("DESCRIBE customer_documents");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>테이블 구조:</h3>";
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

    // 업로드 폴더 생성
    $upload_base_dir = __DIR__ . '/uploads/customer_documents';
    if (!file_exists($upload_base_dir)) {
        if (mkdir($upload_base_dir, 0755, true)) {
            echo "<p style='color: green;'>✓ 업로드 폴더가 생성되었습니다: " . htmlspecialchars($upload_base_dir) . "</p>";
        } else {
            echo "<p style='color: red;'>✗ 업로드 폴더 생성 실패: " . htmlspecialchars($upload_base_dir) . "</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ 업로드 폴더가 이미 존재합니다: " . htmlspecialchars($upload_base_dir) . "</p>";
    }

    echo "<br><a href='index.php'>← 메인 페이지로 돌아가기</a>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ DB 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>
