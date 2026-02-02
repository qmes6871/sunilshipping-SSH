<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// 실행 모드 확인 (CLI or WEB)
$isCLI = php_sapi_name() === 'cli';

// Google Sheets 설정
$serviceAccountFile = __DIR__ . '/webtracing-service-account.json';
$spreadsheetId = '1wABaGqguNcEDO3Vw7BMo8InlEnK2EqC9ma5GOR_pDpE';
$sheetName = "25'TCR #";

try {
    // Google Client 초기화
    $client = new Google_Client();
    $client->setApplicationName('Web Tracing Sheets Reader');
    $client->setScopes([Google_Service_Sheets::SPREADSHEETS_READONLY]);
    $client->setAuthConfig($serviceAccountFile);

    $httpClient = new \GuzzleHttp\Client(['verify' => false]);
    $client->setHttpClient($httpClient);

    $service = new Google_Service_Sheets($client);

    // B 컬럼으로 마지막 행 찾기
    $bResponse = $service->spreadsheets_values->get($spreadsheetId, "'{$sheetName}'!B:B");
    $bValues = $bResponse->getValues();

    $lastRow = 0;
    foreach ($bValues as $index => $row) {
        if (!empty($row) && !empty($row[0])) {
            $lastRow = $index + 1;
        }
    }

    // 전체 범위 가져오기
    $columns = ['B', 'F', 'G', 'H', 'I', 'O', 'Y', 'Z', 'AA', 'AB', 'AC', 'AF', 'AG', 'AH', 'AI', 'AJ'];
    $range = "'{$sheetName}'!B3:AJ{$lastRow}";

    $response = $service->spreadsheets_values->get($spreadsheetId, $range);
    $values = $response->getValues();

    // JSON 파일로 저장
    $jsonData = [];
    foreach ($values as $idx => $row) {
        $rowData = [
            'row_num' => $idx + 3,
            'B' => isset($row[0]) ? $row[0] : '',
            'F' => isset($row[4]) ? $row[4] : '',
            'G' => isset($row[5]) ? $row[5] : '',
            'H' => isset($row[6]) ? $row[6] : '',
            'I' => isset($row[7]) ? $row[7] : '',
            'O' => isset($row[13]) ? $row[13] : '',
            'Y' => isset($row[23]) ? $row[23] : '',
            'Z' => isset($row[24]) ? $row[24] : '',
            'AA' => isset($row[25]) ? $row[25] : '',
            'AB' => isset($row[26]) ? $row[26] : '',
            'AC' => isset($row[27]) ? $row[27] : '',
            'AF' => isset($row[30]) ? $row[30] : '',
            'AG' => isset($row[31]) ? $row[31] : '',
            'AH' => isset($row[32]) ? $row[32] : '',
            'AI' => isset($row[33]) ? $row[33] : '',
            'AJ' => isset($row[34]) ? $row[34] : '',
        ];
        $jsonData[] = $rowData;
    }

    // JSON 파일 저장
    $jsonFile = __DIR__ . '/tcr_data.json';
    file_put_contents($jsonFile, json_encode($jsonData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    if ($isCLI) {
        echo "데이터 업데이트 완료!\n";
        echo "총 " . count($jsonData) . "개 행 저장됨\n";
        echo "파일: {$jsonFile}\n";
    } else {
        echo json_encode([
            'success' => true,
            'count' => count($jsonData),
            'message' => 'Data updated successfully'
        ]);
    }

} catch (Exception $e) {
    if ($isCLI) {
        echo "오류: " . $e->getMessage() . "\n";
    } else {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
?>
