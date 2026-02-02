<?php
/**
 * 테이블 생성 및 초기 설정 스크립트
 * 데이터베이스에 필요한 테이블들을 생성합니다.
 */

require_once 'config.php';

// SQL 파일 읽기
$sql_file = __DIR__ . '/../create_tables.sql';

if (file_exists($sql_file)) {
    $sql_content = file_get_contents($sql_file);

    try {
        // SQL 실행 (여러 쿼리이므로 분리 실행)
        $queries = array_filter(array_map('trim', explode(';', $sql_content)));

        foreach ($queries as $query) {
            if (!empty($query) && !preg_match('/^(SELECT|SHOW|DESCRIBE)/i', $query)) {
                $pdo->exec($query);
                echo "✅ 쿼리 실행 완료: " . substr($query, 0, 50) . "...<br>";
            }
        }

        echo "<h2 style='color: green;'>🎉 테이블 생성이 완료되었습니다!</h2>";
        echo "<p>이제 서류 업로드 기능을 사용할 수 있습니다.</p>";
        echo "<a href='index.php' style='background: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>서류 관리 페이지로 이동</a>";

    } catch (PDOException $e) {
        echo "<h2 style='color: red;'>❌ 테이블 생성 중 오류 발생:</h2>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "<p><a href='setup_tables.php' style='background: #dc2626; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>다시 시도</a></p>";
    }
} else {
    echo "<h2 style='color: red;'>❌ SQL 파일을 찾을 수 없습니다:</h2>";
    echo "<p>" . $sql_file . "</p>";
}

echo "<br><br>";
echo "<h3>현재 데이터베이스 상태:</h3>";

// 테이블 목록 확인
try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    echo "<ul>";
    foreach ($tables as $table) {
        // 각 테이블의 레코드 수 확인
        $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        echo "<li><strong>$table</strong> - $count 개 레코드</li>";
    }
    echo "</ul>";

} catch (Exception $e) {
    echo "<p>테이블 확인 중 오류: " . $e->getMessage() . "</p>";
}
?>

