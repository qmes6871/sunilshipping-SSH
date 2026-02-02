<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db_config.php';

$sqlFile = __DIR__ . '/tcr_data.sql';

if (!file_exists($sqlFile)) {
    die("오류: tcr_data.sql 파일이 없습니다.");
}

echo "=== SQL 실행 시작 ===\n\n";

try {
    echo "1. 데이터베이스 연결 중...\n";
    $conn = getDbConnection();
    echo "   연결 성공!\n\n";

    echo "2. SQL 파일 읽는 중...\n";
    $sql = file_get_contents($sqlFile);
    echo "   파일 크기: " . number_format(strlen($sql)) . " bytes\n\n";

    echo "3. SQL 실행 중...\n";

    // multi_query로 여러 쿼리 실행
    if ($conn->multi_query($sql)) {
        do {
            // 결과가 있으면 처리
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->next_result());

        echo "   ✓ SQL 실행 완료!\n\n";
    } else {
        echo "   ✗ 오류: " . $conn->error . "\n\n";
    }

    // 결과 확인
    echo "4. 결과 확인 중...\n";
    $result = $conn->query("SELECT COUNT(*) as total FROM tcr_tracking");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "   총 레코드 수: " . number_format($row['total']) . " 개\n";
        $result->free();
    }

    $conn->close();

    echo "\n=== 완료 ===\n";
    echo "이제 view_db.php에서 데이터를 확인하세요.\n";

} catch (Exception $e) {
    echo "오류: " . $e->getMessage() . "\n";
}
?>
