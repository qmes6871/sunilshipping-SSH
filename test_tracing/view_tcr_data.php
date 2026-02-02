<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Google Sheets 설정
$serviceAccountFile = __DIR__ . '/webtracing-service-account.json';
$spreadsheetId = '1wABaGqguNcEDO3Vw7BMo8InlEnK2EqC9ma5GOR_pDpE';
$sheetName = "25'TCR #";

$allData = [];
$error = null;

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
            $lastRow = $index + 1;
        }
    }

    // B, F, G, H, I, O, Y, Z, AA, AB, AC, AF, AG, AH, AI, AJ 컬럼 데이터 가져오기
    $columns = ['B', 'F', 'G', 'H', 'I', 'O', 'Y', 'Z', 'AA', 'AB', 'AC', 'AF', 'AG', 'AH', 'AI', 'AJ'];
    $columnData = [];

    // 각 컬럼별로 데이터 가져오기
    foreach ($columns as $col) {
        $range = "'{$sheetName}'!{$col}3:{$col}{$lastRow}";
        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues();
        $columnData[$col] = $values;
    }

    // 데이터를 행 단위로 재구성
    for ($i = 0; $i < ($lastRow - 2); $i++) {
        $row = ['row_num' => $i + 3];
        foreach ($columns as $col) {
            $row[$col] = isset($columnData[$col][$i][0]) ? $columnData[$col][$i][0] : '';
        }
        $allData[] = $row;
    }

} catch (Exception $e) {
    $error = $e->getMessage();
}

// 컬럼 헤더 이름 매핑
$columnHeaders = [
    'B' => '순번',
    'F' => 'CNTR NO',
    'G' => '이름1',
    'H' => '이름2',
    'I' => '전화번호',
    'O' => '금액',
    'Y' => 'Y',
    'Z' => 'Z',
    'AA' => 'AA',
    'AB' => 'AB',
    'AC' => 'AC',
    'AF' => 'AF',
    'AG' => 'AG',
    'AH' => 'AH',
    'AI' => 'AI',
    'AJ' => 'AJ'
];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCR 데이터 조회</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Malgun Gothic', Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 100%;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
        }
        .info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            color: #1976d2;
        }
        .error {
            background: #ffebee;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            color: #c62828;
        }
        .search-box {
            margin-bottom: 20px;
        }
        .search-box input {
            padding: 10px;
            width: 300px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .table-wrapper {
            overflow-x: auto;
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
        }
        thead {
            background: #2196F3;
            color: white;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        th {
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #1976d2;
            font-size: 13px;
        }
        td {
            padding: 10px 8px;
            border: 1px solid #ddd;
            font-size: 12px;
        }
        tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        tbody tr:hover {
            background: #e3f2fd;
        }
        .row-num {
            background: #f5f5f5;
            font-weight: bold;
            text-align: center;
        }
        .stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            flex: 1;
        }
        .stat-label {
            font-size: 12px;
            opacity: 0.9;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 25'TCR # 데이터 조회</h1>

        <?php if ($error): ?>
            <div class="error">
                <strong>오류 발생:</strong> <?= htmlspecialchars($error) ?>
            </div>
        <?php else: ?>
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-label">총 데이터 수</div>
                    <div class="stat-value"><?= number_format(count($allData)) ?> 건</div>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="stat-label">조회 컬럼</div>
                    <div class="stat-value"><?= count($columnHeaders) ?> 개</div>
                </div>
            </div>

            <div class="search-box">
                <input type="text" id="searchInput" placeholder="검색어를 입력하세요..." onkeyup="filterTable()">
            </div>

            <div class="table-wrapper">
                <table id="dataTable">
                    <thead>
                        <tr>
                            <th>행번호</th>
                            <?php foreach ($columnHeaders as $col => $header): ?>
                                <th><?= htmlspecialchars($header) ?><br><small>(<?= $col ?>)</small></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allData as $row): ?>
                            <tr>
                                <td class="row-num"><?= $row['row_num'] ?></td>
                                <?php foreach (array_keys($columnHeaders) as $col): ?>
                                    <td><?= htmlspecialchars($row[$col]) ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function filterTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('dataTable');
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                let txtValue = tr[i].textContent || tr[i].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = '';
                } else {
                    tr[i].style.display = 'none';
                }
            }
        }
    </script>
</body>
</html>
