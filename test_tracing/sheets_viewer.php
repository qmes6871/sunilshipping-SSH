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
    die("Error: Cannot find vendor/autoload.php. Searched paths: " . implode(', ', $possiblePaths));
}

require_once $autoloadPath;

// Google Sheets 설정
$serviceAccountFile = __DIR__ . '/webtracing-service-account.json';
$spreadsheetId = '1wABaGqguNcEDO3Vw7BMo8InlEnK2EqC9ma5GOR_pDpE';
$sheetName = "25'TCR #";

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>TCR 데이터</title>";
echo "<style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
.loading { text-align: center; padding: 50px; font-size: 18px; }
table { width: 100%; border-collapse: collapse; background: white; }
th { background: #2196F3; color: white; padding: 10px; border: 1px solid #ddd; position: sticky; top: 0; }
td { padding: 8px; border: 1px solid #ddd; font-size: 13px; }
tr:nth-child(even) { background: #f9f9f9; }
tr:hover { background: #e3f2fd; }
.info { background: white; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
.error { background: #ffebee; color: red; padding: 15px; margin: 20px 0; }
input { padding: 8px; width: 300px; margin-bottom: 15px; }
</style></head><body>";

echo "<div class='loading'>데이터 로딩 중...</div>";
flush();

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

    // 전체 범위 한번에 가져오기 (더 빠름)
    $columns = ['B', 'F', 'G', 'H', 'I', 'O', 'Y', 'Z', 'AA', 'AB', 'AC', 'AF', 'AG', 'AH', 'AI', 'AJ'];
    $startCol = 'B';
    $endCol = 'AJ';
    $range = "'{$sheetName}'!{$startCol}3:{$endCol}{$lastRow}";

    $response = $service->spreadsheets_values->get($spreadsheetId, $range);
    $values = $response->getValues();

    $columnMap = [
        0 => 'B', 5 => 'F', 6 => 'G', 7 => 'H', 8 => 'I', 14 => 'O',
        24 => 'Y', 25 => 'Z', 26 => 'AA', 27 => 'AB', 28 => 'AC',
        31 => 'AF', 32 => 'AG', 33 => 'AH', 34 => 'AI', 35 => 'AJ'
    ];

    $headers = [
        'B' => '순번', 'F' => 'CNTR NO', 'G' => '이름1', 'H' => '이름2',
        'I' => '전화번호', 'O' => '금액', 'Y' => 'Y', 'Z' => 'Z',
        'AA' => 'AA', 'AB' => 'AB', 'AC' => 'AC', 'AF' => 'AF',
        'AG' => 'AG', 'AH' => 'AH', 'AI' => 'AI', 'AJ' => 'AJ'
    ];

    // 로딩 메시지 제거
    echo "<script>document.querySelector('.loading').remove();</script>";

    echo "<div class='info'>";
    echo "<strong>총 데이터:</strong> " . count($values) . " 건 | ";
    echo "<strong>마지막 행:</strong> {$lastRow}";
    echo "</div>";

    echo "<input type='text' id='search' placeholder='검색...' onkeyup='search()'>";

    echo "<table id='table'><thead><tr><th>행</th>";
    foreach ($columns as $col) {
        echo "<th>{$headers[$col]}<br><small>({$col})</small></th>";
    }
    echo "</tr></thead><tbody>";

    foreach ($values as $idx => $row) {
        $rowNum = $idx + 3;
        echo "<tr><td style='text-align:center;background:#f5f5f5;font-weight:bold'>{$rowNum}</td>";

        // B 컬럼
        echo "<td>" . (isset($row[0]) ? htmlspecialchars($row[0]) : '') . "</td>";
        // F 컬럼 (인덱스 4 = 6번째 컬럼 - B부터 시작했으므로 F는 5번째 차이 = 인덱스 4)
        echo "<td>" . (isset($row[4]) ? htmlspecialchars($row[4]) : '') . "</td>";
        // G 컬럼
        echo "<td>" . (isset($row[5]) ? htmlspecialchars($row[5]) : '') . "</td>";
        // H 컬럼
        echo "<td>" . (isset($row[6]) ? htmlspecialchars($row[6]) : '') . "</td>";
        // I 컬럼
        echo "<td>" . (isset($row[7]) ? htmlspecialchars($row[7]) : '') . "</td>";
        // O 컬럼 (인덱스 13)
        echo "<td>" . (isset($row[13]) ? htmlspecialchars($row[13]) : '') . "</td>";
        // Y 컬럼 (인덱스 23)
        echo "<td>" . (isset($row[23]) ? htmlspecialchars($row[23]) : '') . "</td>";
        // Z 컬럼 (인덱스 24)
        echo "<td>" . (isset($row[24]) ? htmlspecialchars($row[24]) : '') . "</td>";
        // AA 컬럼 (인덱스 25)
        echo "<td>" . (isset($row[25]) ? htmlspecialchars($row[25]) : '') . "</td>";
        // AB 컬럼 (인덱스 26)
        echo "<td>" . (isset($row[26]) ? htmlspecialchars($row[26]) : '') . "</td>";
        // AC 컬럼 (인덱스 27)
        echo "<td>" . (isset($row[27]) ? htmlspecialchars($row[27]) : '') . "</td>";
        // AF 컬럼 (인덱스 30)
        echo "<td>" . (isset($row[30]) ? htmlspecialchars($row[30]) : '') . "</td>";
        // AG 컬럼 (인덱스 31)
        echo "<td>" . (isset($row[31]) ? htmlspecialchars($row[31]) : '') . "</td>";
        // AH 컬럼 (인덱스 32)
        echo "<td>" . (isset($row[32]) ? htmlspecialchars($row[32]) : '') . "</td>";
        // AI 컬럼 (인덱스 33)
        echo "<td>" . (isset($row[33]) ? htmlspecialchars($row[33]) : '') . "</td>";
        // AJ 컬럼 (인덱스 34)
        echo "<td>" . (isset($row[34]) ? htmlspecialchars($row[34]) : '') . "</td>";

        echo "</tr>";
    }

    echo "</tbody></table>";

} catch (Exception $e) {
    echo "<div class='error'>오류: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<script>
function search() {
    var input = document.getElementById('search').value.toUpperCase();
    var table = document.getElementById('table');
    var tr = table.getElementsByTagName('tr');
    for (var i = 1; i < tr.length; i++) {
        var txt = tr[i].textContent || tr[i].innerText;
        tr[i].style.display = txt.toUpperCase().indexOf(input) > -1 ? '' : 'none';
    }
}
</script></body></html>";
?>
