<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 서비스 계정으로 Google Sheets 데이터 가져오기
$serviceAccountFile = __DIR__ . '/webtracing-service-account.json';
$sheetId = '1wABaGqguNcEDO3Vw7BMo8InlEnK2EqC9ma5GOR_pDpE';
$sheetName = "25'TCR #";

echo "<h2>Google Sheets API (서비스 계정) - CSV 저장</h2>";

// 서비스 계정 파일 확인
if (!file_exists($serviceAccountFile)) {
    die("<p style='color:red;'>서비스 계정 파일을 찾을 수 없습니다!</p>");
}

$serviceAccount = json_decode(file_get_contents($serviceAccountFile), true);
echo "<p>✓ 서비스 계정: {$serviceAccount['client_email']}</p>";

// JWT 토큰 생성 함수
function createJWT($serviceAccount) {
    $now = time();

    $header = json_encode([
        'alg' => 'RS256',
        'typ' => 'JWT'
    ]);

    $claim = json_encode([
        'iss' => $serviceAccount['client_email'],
        'scope' => 'https://www.googleapis.com/auth/spreadsheets.readonly',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => $now + 3600,
        'iat' => $now
    ]);

    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlClaim = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($claim));

    $signature = '';
    openssl_sign(
        $base64UrlHeader . "." . $base64UrlClaim,
        $signature,
        $serviceAccount['private_key'],
        'SHA256'
    );

    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    return $base64UrlHeader . "." . $base64UrlClaim . "." . $base64UrlSignature;
}

// 액세스 토큰 가져오기
function getAccessToken($serviceAccount) {
    $jwt = createJWT($serviceAccount);

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        echo "<p style='color:red;'>토큰 가져오기 실패! HTTP {$httpCode}</p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
        return null;
    }

    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}

echo "<p>액세스 토큰 가져오는 중...</p>";
$accessToken = getAccessToken($serviceAccount);

if (!$accessToken) {
    die("<p style='color:red;'>액세스 토큰을 가져올 수 없습니다!</p>");
}

echo "<p style='color:green;'>✓ 액세스 토큰 획득 성공!</p>";

// Google Sheets API로 데이터 가져오기
$url = "https://sheets.googleapis.com/v4/spreadsheets/{$sheetId}/values/" . urlencode($sheetName);

echo "<p>데이터 가져오는 중...</p>";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$accessToken}"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "<p style='color:red;'>데이터 가져오기 실패! HTTP {$httpCode}</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    exit;
}

$data = json_decode($response, true);

if (!isset($data['values'])) {
    die("<p style='color:red;'>데이터가 없습니다!</p>");
}

$allRows = $data['values'];
echo "<p style='color:green;'>✓ 총 {count($allRows)}행 가져오기 성공!</p>";

// 필요한 컬럼만 추출 (B, F, G, H, I, O, Y, Z, AA, AB, AC, AF, AG, AH, AI, AJ, BS)
// B=1, F=5, G=6, H=7, I=8, O=14, Y=24, Z=25, AA=26, AB=27, AC=28, AF=31, AG=32, AH=33, AI=34, AJ=35, BS=70
$columnIndexes = [1, 5, 6, 7, 8, 14, 24, 25, 26, 27, 28, 31, 32, 33, 34, 35, 70];
$columnNames = ['B', 'F', 'G', 'H', 'I', 'O', 'Y', 'Z', 'AA', 'AB', 'AC', 'AF', 'AG', 'AH', 'AI', 'AJ', 'BS'];

// 필터링된 데이터
$filteredRows = [];
$filteredRows[] = $columnNames;

// 3행부터 데이터 추출
for ($i = 2; $i < count($allRows); $i++) {
    $row = [];
    foreach ($columnIndexes as $colIdx) {
        $row[] = isset($allRows[$i][$colIdx]) ? trim($allRows[$i][$colIdx]) : '';
    }
    if (!empty(array_filter($row))) {
        $filteredRows[] = $row;
    }
}

echo "<p>필터링된 데이터: " . (count($filteredRows) - 1) . "행</p>";

// DB에 삽입
require_once __DIR__ . '/db_config.php';

try {
    $conn = getDbConnection();
    echo "<p style='color:green;'>✓ 데이터베이스 연결 성공!</p>";

    // 기존 데이터 초기화 (TRUNCATE)
    echo "<p>기존 데이터 초기화 중...</p>";
    $conn->query("TRUNCATE TABLE tcr_tracking");
    echo "<p style='color:green;'>✓ 테이블 초기화 완료!</p>";

    // 테이블 컬럼명 (구글 시트 컬럼 순서대로 매핑)
    $dbColumns = [
        'NO',
        'CNTR_NO',
        'SHIPPER',
        'BUYER',
        'HP',
        'WEIGHT',
        'POL_ETD',
        'CHINA_PORT_ETA',
        'PORT',
        'ETD',
        'WAGON',
        'ALTYNKOL_ETA',
        'ALTYNKOL_ETD',
        'CIS_WAGON',
        'FINAL_ETA',
        'FINAL_DEST',
        'amount'
    ];

    $insertedCount = 0;
    $errors = [];

    // 트랜잭션 시작
    $conn->begin_transaction();

    // 헤더 제외하고 데이터 삽입 (첫 번째 행은 컬럼명이므로 건너뜀)
    for ($idx = 1; $idx < count($filteredRows); $idx++) {
        $row = $filteredRows[$idx];

        // 빈 행 건너뛰기
        if (empty(array_filter($row))) {
            continue;
        }

        // INSERT 쿼리 생성
        $columns = [];
        $placeholders = [];
        $values = [];

        for ($i = 0; $i < min(count($dbColumns), count($row)); $i++) {
            $columns[] = "`{$dbColumns[$i]}`";
            $placeholders[] = '?';
            $values[] = trim($row[$i]);
        }

        if (empty($columns)) {
            continue;
        }

        $sql = "INSERT INTO tcr_tracking (" . implode(', ', $columns) . ")
                VALUES (" . implode(', ', $placeholders) . ")";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $errors[] = "Prepare 실패 (행 " . ($idx + 1) . "): " . $conn->error;
            continue;
        }

        // 동적 바인딩
        $types = str_repeat('s', count($values));
        $stmt->bind_param($types, ...$values);

        if ($stmt->execute()) {
            $insertedCount++;
        } else {
            $errors[] = "행 " . ($idx + 1) . ": " . $stmt->error;
        }

        $stmt->close();
    }

    // 커밋
    $conn->commit();

    // 총 레코드 수 확인
    $result = $conn->query("SELECT COUNT(*) as total FROM tcr_tracking");
    $totalCount = 0;
    if ($result) {
        $r = $result->fetch_assoc();
        $totalCount = $r['total'];
        $result->free();
    }

    $conn->close();

    echo "<p style='color:green; font-weight:bold; font-size:18px;'>✓ 데이터베이스 삽입 완료!</p>";
    echo "<p>삽입된 레코드: {$insertedCount}개</p>";
    echo "<p>총 레코드 수: {$totalCount}개</p>";

    if (!empty($errors)) {
        echo "<h4 style='color:orange;'>오류 목록:</h4>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li>{$error}</li>";
        }
        echo "</ul>";
    }

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
        $conn->close();
    }
    echo "<p style='color:red;'>오류: " . $e->getMessage() . "</p>";
}

// 미리보기
echo "<h3>데이터 미리보기 (처음 10행):</h3>";
echo "<table border='1' style='border-collapse: collapse; width:100%;'>";

for ($i = 0; $i < min(11, count($filteredRows)); $i++) {
    echo "<tr>";
    if ($i === 0) {
        echo "<th style='background:#4CAF50; color:white; padding:10px;'>NO</th>";
        foreach ($filteredRows[$i] as $cell) {
            echo "<th style='background:#4CAF50; color:white; padding:10px;'>" . htmlspecialchars($cell) . "</th>";
        }
    } else {
        echo "<td style='padding:8px;'><strong>{$i}</strong></td>";
        foreach ($filteredRows[$i] as $cell) {
            echo "<td style='padding:8px;'>" . htmlspecialchars($cell) . "</td>";
        }
    }
    echo "</tr>";
}

echo "</table>";
?>
