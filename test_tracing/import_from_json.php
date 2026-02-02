<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db_config.php';

// JSON 파일에서 데이터 읽기
$jsonFile = __DIR__ . '/tcr_data.json';

if (!file_exists($jsonFile)) {
    die("오류: tcr_data.json 파일이 없습니다.\n먼저 로컬에서 'php sheets_manager.php'를 실행하여 JSON 파일을 생성하세요.");
}

echo "=== JSON 데이터 DB 임포트 시작 ===\n\n";

try {
    echo "1. JSON 파일 읽는 중...\n";
    $jsonData = json_decode(file_get_contents($jsonFile), true);
    echo "   총 " . count($jsonData) . "개 레코드 발견\n\n";

    echo "2. 데이터베이스 연결 중...\n";
    $conn = getDbConnection();
    echo "   연결 성공!\n\n";

    echo "3. 기존 데이터 삭제 중...\n";
    $conn->query("TRUNCATE TABLE tcr_tracking");
    echo "   완료!\n\n";

    echo "4. 데이터 삽입 중...\n";
    $insertCount = 0;
    $errorCount = 0;

    $stmt = $conn->prepare("INSERT INTO tcr_tracking
        (`NO`, CNTR_NO, SHIPPER, BUYER, HP, WEIGHT, POL_ETD, CHINA_PORT_ETA, PORT, ETD, WAGON, ALTYNKOL_ETA, ALTYNKOL_ETD, CIS_WAGON, FINAL_ETA, FINAL_DEST)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($jsonData as $idx => $row) {
        $stmt->bind_param("ssssssssssssssss",
            $row['B'],
            $row['F'],
            $row['G'],
            $row['H'],
            $row['I'],
            $row['O'],
            $row['Y'],
            $row['Z'],
            $row['AA'],
            $row['AB'],
            $row['AC'],
            $row['AF'],
            $row['AG'],
            $row['AH'],
            $row['AI'],
            $row['AJ']
        );

        if ($stmt->execute()) {
            $insertCount++;
            if ($insertCount % 100 == 0) {
                echo "   진행 중... {$insertCount}개 삽입됨\n";
            }
        } else {
            $errorCount++;
            echo "   오류 (행 " . $row['row_num'] . "): " . $stmt->error . "\n";
        }
    }

    $stmt->close();
    $conn->close();

    echo "\n=== 임포트 완료 ===\n";
    echo "성공: {$insertCount}개\n";
    echo "실패: {$errorCount}개\n";

} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
}
?>
