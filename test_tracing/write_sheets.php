<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Google\Client;
use Google\Service\Sheets;

// 구글 시트 ID 설정
$spreadsheetId = '1wABaGqguNcEDO3Vw7BMo8InlEnK2EqC9ma5GOR_pDpE';

try {
    // Google Client 초기화
    $client = new Client();
    $client->setApplicationName('Web Tracing Sheets Writer');
    $client->setScopes([Sheets::SPREADSHEETS]);
    $client->setAuthConfig(__DIR__ . '/webtracing-service-account.json');

    // Sheets 서비스 생성
    $service = new Sheets($client);

    // 예시: A1 셀에 데이터 쓰기
    $range = 'Sheet1!A1';
    $values = [
        ['테스트 데이터', '작성 시간: ' . date('Y-m-d H:i:s')]
    ];

    $body = new Google\Service\Sheets\ValueRange([
        'values' => $values
    ]);

    $params = [
        'valueInputOption' => 'RAW'  // 또는 'USER_ENTERED'
    ];

    $result = $service->spreadsheets_values->update(
        $spreadsheetId,
        $range,
        $body,
        $params
    );

    echo "성공적으로 데이터를 작성했습니다.\n";
    echo "업데이트된 셀 수: " . $result->getUpdatedCells() . "\n";

} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
}
