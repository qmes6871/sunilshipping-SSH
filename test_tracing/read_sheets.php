<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Google\Client;
use Google\Service\Sheets;

// 구글 시트 ID와 범위 설정
$spreadsheetId = '1wABaGqguNcEDO3Vw7BMo8InlEnK2EqC9ma5GOR_pDpE';
$range = 'Sheet1!A1:Z1000'; // 읽을 범위 (필요에 따라 수정)

try {
    // Google Client 초기화
    $client = new Client();
    $client->setApplicationName('Web Tracing Sheets Reader');
    $client->setScopes([Sheets::SPREADSHEETS]);
    $client->setAuthConfig(__DIR__ . '/webtracing-service-account.json');

    // Sheets 서비스 생성
    $service = new Sheets($client);

    // 시트 데이터 읽기
    $response = $service->spreadsheets_values->get($spreadsheetId, $range);
    $values = $response->getValues();

    if (empty($values)) {
        echo "데이터가 없습니다.\n";
    } else {
        echo "총 " . count($values) . "행의 데이터를 가져왔습니다.\n\n";

        // 첫 10행 출력 (예시)
        foreach (array_slice($values, 0, 10) as $index => $row) {
            echo "Row " . ($index + 1) . ": " . implode(", ", $row) . "\n";
        }

        // 전체 데이터를 JSON으로 저장
        file_put_contents(__DIR__ . '/sheets_data.json', json_encode($values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "\n전체 데이터가 sheets_data.json 파일에 저장되었습니다.\n";
    }

} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
}
