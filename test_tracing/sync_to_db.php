<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db_config.php';

// vendor 경로 찾기
$possiblePaths = [
    __DIR__ . '/../vendor/autoload.php',
    '/sunilshipping/www/vendor/autoload.php',
    dirname(__DIR__) . '/vendor/autoload.php',
];

$autoloadPath = null;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        $autoloadPath = $path;
        break;
    }
}

if (!$autoloadPath) {
    die("Error: Cannot find vendor/autoload.php");
}

require_once $autoloadPath;

// Google Sheets 설정
$serviceAccountFile = __DIR__ . '/webtracing-service-account.json';
$spreadsheetId = '1wABaGqguNcEDO3Vw7BMo8InlEnK2EqC9ma5GOR_pDpE';
$sheetName = "25'TCR #";

// 컬럼 매핑 (Google Sheets 컬럼 -> DB 컬럼)
$columnMapping = [
    'B' => 'NO',
    'F' => 'CNTR_NO',
    'G' => 'SHIPPER',
    'H' => 'BUYER',
    'I' => 'HP',
    'O' => 'WEIGHT',
    'Y' => 'POL_ETD',
    'Z' => 'CHINA_PORT_ETA',
    'AA' => 'PORT',
    'AB' => 'ETD',
    'AC' => 'WAGON',
    'AF' => 'ALTYNKOL_ETA',
    'AG' => 'ALTYNKOL_ETD',
    'AH' => 'CIS_WAGON',
    'AI' => 'FINAL_ETA',
    'AJ' => 'FINAL_DEST'
];

try {
    echo "=== TCR 데이터 동기화 시작 ===\n\n";

    // Google Client 초기화
    echo "1. Google Sheets API 연결 중...\n";
    $client = new Google_Client();
    $client->setApplicationName('Web Tracing Sheets Reader');
    $client->setScopes([Google_Service_Sheets::SPREADSHEETS_READONLY]);
    $client->setAuthConfig($serviceAccountFile);

    $httpClient = new \GuzzleHttp\Client(['verify' => false]);
    $client->setHttpClient($httpClient);

    $service = new Google_Service_Sheets($client);

    // B 컬럼으로 마지막 행 찾기
    echo "2. 데이터 범위 확인 중...\n";
    $bResponse = $service->spreadsheets_values->get($spreadsheetId, "'{$sheetName}'!B:B");
    $bValues = $bResponse->getValues();

    $lastRow = 0;
    foreach ($bValues as $index => $row) {
        if (!empty($row) && !empty($row[0])) {
            $lastRow = $index + 1;
        }
    }

    echo "   마지막 행: {$lastRow}\n";

    // 전체 범위 가져오기 (B부터 AJ까지)
    echo "3. Google Sheets 데이터 가져오는 중...\n";
    $range = "'{$sheetName}'!B3:AJ{$lastRow}";
    $response = $service->spreadsheets_values->get($spreadsheetId, $range);
    $values = $response->getValues();

    echo "   총 " . count($values) . "개 행 가져옴\n\n";

    // 데이터베이스 연결
    echo "4. 데이터베이스 연결 중...\n";
    $conn = getDbConnection();
    echo "   연결 성공!\n\n";

    // 기존 데이터 삭제 (선택사항)
    echo "5. 기존 데이터 삭제 중...\n";
    $conn->query("TRUNCATE TABLE tcr_tracking");
    echo "   완료!\n\n";

    // 데이터 삽입
    echo "6. 데이터 삽입 중...\n";
    $insertCount = 0;
    $errorCount = 0;

    $stmt = $conn->prepare("INSERT INTO tcr_tracking
        (`NO`, CNTR_NO, SHIPPER, BUYER, HP, WEIGHT, POL_ETD, CHINA_PORT_ETA, PORT, ETD, WAGON, ALTYNKOL_ETA, ALTYNKOL_ETD, CIS_WAGON, FINAL_ETA, FINAL_DEST)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($values as $idx => $row) {
        // 데이터 추출 (컬럼 인덱스는 0부터 시작)
        $no = isset($row[0]) ? $row[0] : ''; // B
        $cntr_no = isset($row[4]) ? $row[4] : ''; // F (B부터 0,1,2,3,4)
        $shipper = isset($row[5]) ? $row[5] : ''; // G
        $buyer = isset($row[6]) ? $row[6] : ''; // H
        $hp = isset($row[7]) ? $row[7] : ''; // I
        $weight = isset($row[13]) ? $row[13] : ''; // O
        $pol_etd = isset($row[23]) ? $row[23] : ''; // Y
        $china_port_eta = isset($row[24]) ? $row[24] : ''; // Z
        $port = isset($row[25]) ? $row[25] : ''; // AA
        $etd = isset($row[26]) ? $row[26] : ''; // AB
        $wagon = isset($row[27]) ? $row[27] : ''; // AC
        $altynkol_eta = isset($row[30]) ? $row[30] : ''; // AF
        $altynkol_etd = isset($row[31]) ? $row[31] : ''; // AG
        $cis_wagon = isset($row[32]) ? $row[32] : ''; // AH
        $final_eta = isset($row[33]) ? $row[33] : ''; // AI
        $final_dest = isset($row[34]) ? $row[34] : ''; // AJ

        $stmt->bind_param("ssssssssssssssss",
            $no, $cntr_no, $shipper, $buyer, $hp, $weight,
            $pol_etd, $china_port_eta, $port, $etd, $wagon,
            $altynkol_eta, $altynkol_etd, $cis_wagon, $final_eta, $final_dest
        );

        if ($stmt->execute()) {
            $insertCount++;
            if ($insertCount % 100 == 0) {
                echo "   진행 중... {$insertCount}개 삽입됨\n";
            }
        } else {
            $errorCount++;
            echo "   오류 (행 " . ($idx + 3) . "): " . $stmt->error . "\n";
        }
    }

    $stmt->close();
    $conn->close();

    echo "\n=== 동기화 완료 ===\n";
    echo "성공: {$insertCount}개\n";
    echo "실패: {$errorCount}개\n";

} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
    echo "스택 트레이스:\n" . $e->getTraceAsString() . "\n";
}
?>
