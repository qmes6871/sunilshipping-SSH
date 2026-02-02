<?php
require_once __DIR__ . '/db_config.php';

echo "=== 데이터베이스 설정 시작 ===\n\n";

try {
    echo "1. 데이터베이스 연결 중...\n";
    $conn = getDbConnection();
    echo "   ✓ 연결 성공!\n\n";

    echo "2. 테이블 생성 중...\n";
    $sql = file_get_contents(__DIR__ . '/create_table.sql');

    if ($conn->multi_query($sql)) {
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->next_result());

        echo "   ✓ 테이블 생성 완료!\n\n";
    } else {
        echo "   ✗ 오류: " . $conn->error . "\n\n";
    }

    echo "3. 테이블 확인 중...\n";
    $result = $conn->query("SHOW TABLES LIKE 'tcr_tracking'");
    if ($result->num_rows > 0) {
        echo "   ✓ tcr_tracking 테이블이 존재합니다.\n\n";

        // 테이블 구조 확인
        echo "4. 테이블 구조:\n";
        $columns = $conn->query("DESCRIBE tcr_tracking");
        while ($col = $columns->fetch_assoc()) {
            echo "   - {$col['Field']} ({$col['Type']})\n";
        }
    } else {
        echo "   ✗ 테이블이 생성되지 않았습니다.\n";
    }

    $conn->close();
    echo "\n=== 설정 완료 ===\n";

} catch (Exception $e) {
    echo "오류: " . $e->getMessage() . "\n";
}
?>
