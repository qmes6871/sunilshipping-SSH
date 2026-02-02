<?php
require_once __DIR__ . '/../vendor/autoload.php';

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

    // SSL 검증 비활성화 (개발 환경용)
    $httpClient = new \GuzzleHttp\Client(['verify' => false]);
    $client->setHttpClient($httpClient);

    // Sheets 서비스 생성
    $service = new Google_Service_Sheets($client);

    // 먼저 B 컬럼 전체를 가져와서 마지막 데이터 행 찾기
    $bColumnRange = "'{$sheetName}'!B:B";
    $bResponse = $service->spreadsheets_values->get($spreadsheetId, $bColumnRange);
    $bValues = $bResponse->getValues();

    // B 컬럼의 마지막 데이터 행 찾기
    $lastRow = 0;
    foreach ($bValues as $index => $row) {
        if (!empty($row) && !empty($row[0])) {
            $lastRow = $index + 1; // 1-based row number
        }
    }

    echo "B 컬럼의 마지막 데이터 행: {$lastRow}\n\n";

    // B, F, G, H, I, O, Y, Z, AA, AB, AC, AF, AG, AH, AI, AJ 컬럼 데이터 가져오기
    // 3행부터 마지막 행까지
    $columns = ['B', 'F', 'G', 'H', 'I', 'O', 'Y', 'Z', 'AA', 'AB', 'AC', 'AF', 'AG', 'AH', 'AI', 'AJ'];
    $allData = [];

    // 각 컬럼별로 데이터 가져오기
    foreach ($columns as $col) {
        $range = "'{$sheetName}'!{$col}3:{$col}{$lastRow}";
        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues();
        $allData[$col] = $values;
    }

    // 데이터 출력
    echo "=== 데이터 조회 결과 ===\n";
    echo "컬럼: " . implode(', ', $columns) . "\n";
    echo "총 " . ($lastRow - 2) . "개의 데이터 행\n\n";

    // 처음 10개 행만 출력
    for ($i = 0; $i < min(10, $lastRow - 2); $i++) {
        $rowNum = $i + 3; // 3행부터 시작
        echo "--- 행 {$rowNum} ---\n";
        foreach ($columns as $col) {
            $value = isset($allData[$col][$i][0]) ? $allData[$col][$i][0] : '(비어있음)';
            echo "{$col}: {$value}\n";
        }
        echo "\n";
    }

    if ($lastRow - 2 > 10) {
        echo "... (총 " . ($lastRow - 2) . "개 행 중 10개만 표시)\n";
    }

    // JSON 형식으로도 저장
    echo "\n=== JSON 형식 데이터 (처음 5개) ===\n";
    $jsonData = [];
    for ($i = 0; $i < min(5, $lastRow - 2); $i++) {
        $row = [];
        foreach ($columns as $col) {
            $row[$col] = isset($allData[$col][$i][0]) ? $allData[$col][$i][0] : '';
        }
        $jsonData[] = $row;
    }
    echo json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
    echo "스택 트레이스:\n" . $e->getTraceAsString() . "\n";
}
