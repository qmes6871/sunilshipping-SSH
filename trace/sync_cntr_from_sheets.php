<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 서비스 계정으로 Google Sheets 데이터 가져오기
$serviceAccountFile = __DIR__ . '/webtracing-service-account.json';
$sheetId = '1wABaGqguNcEDO3Vw7BMo8InlEnK2EqC9ma5GOR_pDpE';

echo "<h2>Google Sheets에서 CNTR NO 가져와서 trace_document 테이블에 저장</h2>";

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
        return null;
    }

    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}

// 구글 시트에서 특정 시트의 데이터 가져오기
function getSheetData($accessToken, $sheetId, $sheetName) {
    $url = "https://sheets.googleapis.com/v4/spreadsheets/{$sheetId}/values/" . urlencode($sheetName);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$accessToken}"
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        echo "<p style='color:red;'>데이터 가져오기 실패! HTTP {$httpCode} - {$sheetName}</p>";
        return null;
    }

    $data = json_decode($response, true);
    return $data['values'] ?? null;
}

echo "<p>액세스 토큰 가져오는 중...</p>";
$accessToken = getAccessToken($serviceAccount);

if (!$accessToken) {
    die("<p style='color:red;'>액세스 토큰을 가져올 수 없습니다!</p>");
}

echo "<p style='color:green;'>✓ 액세스 토큰 획득 성공!</p>";

// 세 개의 시트 설정
$sheets = [
    [
        'name' => "25'TCR #",
        'booking_column' => 3,  // D열 (0-based: D=3)
        'cntr_column' => 5,     // F열 (0-based: F=5)
        'shipper_column' => 6,  // G열 (0-based: G=6)
        'start_row' => 2        // 3행부터 (0-based: 3행=2)
    ],
    [
        'name' => 'TURKIYE, SYRIA_SJ',
        'booking_column' => 2,  // C열 (0-based: C=2)
        'cntr_column' => 4,     // E열 (0-based: E=4)
        'shipper_column' => 5,  // F열 (0-based: F=5)
        'start_row' => 2        // 3행부터
    ],
    [
        'name' => "25'W/W",
        'booking_column' => 2,  // C열 (0-based: C=2)
        'cntr_column' => 4,     // E열 (0-based: E=4)
        'shipper_column' => 5,  // F열 (0-based: F=5)
        'start_row' => 2        // 3행부터
    ]
];

$allCntrNos = [];

// 각 시트에서 CNTR NO, BOOKING, SHIPPER 가져오기
foreach ($sheets as $sheet) {
    echo "<h3>{$sheet['name']} 시트 처리 중...</h3>";

    $rows = getSheetData($accessToken, $sheetId, $sheet['name']);

    if (!$rows) {
        echo "<p style='color:orange;'>시트 데이터를 가져올 수 없습니다.</p>";
        continue;
    }

    $count = 0;

    // 시작 행부터 데이터 추출
    for ($i = $sheet['start_row']; $i < count($rows); $i++) {
        $booking = isset($rows[$i][$sheet['booking_column']]) ? trim($rows[$i][$sheet['booking_column']]) : '';
        $cntrNo = isset($rows[$i][$sheet['cntr_column']]) ? trim($rows[$i][$sheet['cntr_column']]) : '';
        $shipper = isset($rows[$i][$sheet['shipper_column']]) ? trim($rows[$i][$sheet['shipper_column']]) : '';

        // CNTR NO가 있는 경우에만 추가
        if (!empty($cntrNo)) {
            $allCntrNos[] = [
                'booking' => $booking,
                'cntr_no' => $cntrNo,
                'shipper' => $shipper,
                'source' => $sheet['name']
            ];
            $count++;
        }
    }

    echo "<p style='color:green;'>✓ {$count}개의 데이터 추출 완료</p>";
}

echo "<h3>총 " . count($allCntrNos) . "개의 CNTR NO 추출됨</h3>";

// DB 설정
define('G5_MYSQL_HOST', 'localhost');
define('G5_MYSQL_USER', 'sunilshipping');
define('G5_MYSQL_PASSWORD', 'sunil123!');
define('G5_MYSQL_DB', 'sunilshipping');

// PDO 연결
function getDbConnection() {
    try {
        return new PDO(
            "mysql:host=" . G5_MYSQL_HOST . ";dbname=" . G5_MYSQL_DB . ";charset=utf8mb4",
            G5_MYSQL_USER,
            G5_MYSQL_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    } catch (PDOException $e) {
        exit("DB Connection Failed: " . $e->getMessage());
    }
}

try {
    $pdo = getDbConnection();
    echo "<p style='color:green;'>✓ 데이터베이스 연결 성공!</p>";

    // 기존 cntr_no 목록 가져오기
    echo "<p>기존 데이터 확인 중...</p>";
    $existingCntrs = [];
    $result = $pdo->query("SELECT cntr_no FROM trace_document");
    while ($row = $result->fetch()) {
        $existingCntrs[] = $row['cntr_no'];
    }
    echo "<p>기존 데이터: " . count($existingCntrs) . "개</p>";

    $insertedCount = 0;
    $skippedCount = 0;
    $updatedCount = 0;
    $errors = [];

    // 트랜잭션 시작
    $pdo->beginTransaction();

    // INSERT 준비 (새로운 데이터)
    $insertStmt = $pdo->prepare("INSERT INTO trace_document (booking, cntr_no, shipper, doc_type, doc_no, status, created_at, updated_at)
                                 VALUES (:booking, :cntr_no, :shipper, :doc_type, :doc_no, :status, NOW(), NOW())");

    // UPDATE 준비 (기존 데이터)
    $updateStmt = $pdo->prepare("UPDATE trace_document
                                 SET booking = :booking, shipper = :shipper, updated_at = NOW()
                                 WHERE cntr_no = :cntr_no");

    foreach ($allCntrNos as $data) {
        try {
            // 중복 체크
            if (in_array($data['cntr_no'], $existingCntrs)) {
                // 기존 데이터 업데이트
                $updateStmt->execute([
                    ':booking' => $data['booking'],
                    ':cntr_no' => $data['cntr_no'],
                    ':shipper' => $data['shipper']
                ]);
                $updatedCount++;
            } else {
                // 새로운 데이터 삽입
                $insertStmt->execute([
                    ':booking' => $data['booking'],
                    ':cntr_no' => $data['cntr_no'],
                    ':shipper' => $data['shipper'],
                    ':doc_type' => null,      // 기본값 NULL
                    ':doc_no' => null,        // 기본값 NULL
                    ':status' => 'pending'    // 기본 상태값
                ]);
                $insertedCount++;
            }
        } catch (PDOException $e) {
            $errors[] = "CNTR NO '{$data['cntr_no']}' 처리 실패: " . $e->getMessage();
        }
    }

    // 커밋
    $pdo->commit();

    // 총 레코드 수 확인
    $result = $pdo->query("SELECT COUNT(*) as total FROM trace_document");
    $totalCount = $result->fetchColumn();

    echo "<p style='color:green; font-weight:bold; font-size:18px;'>✓ 데이터베이스 처리 완료!</p>";
    echo "<p>새로 삽입된 레코드: {$insertedCount}개</p>";
    echo "<p>업데이트된 레코드: {$updatedCount}개</p>";
    echo "<p>총 레코드 수: {$totalCount}개</p>";

    if (!empty($errors)) {
        echo "<h4 style='color:orange;'>오류 목록 (최대 20개):</h4>";
        echo "<ul>";
        foreach (array_slice($errors, 0, 20) as $error) {
            echo "<li>{$error}</li>";
        }
        if (count($errors) > 20) {
            echo "<li>... 외 " . (count($errors) - 20) . "개 오류</li>";
        }
        echo "</ul>";
    }

} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo "<p style='color:red;'>오류: " . $e->getMessage() . "</p>";
}

// 미리보기
echo "<h3>데이터 미리보기 (처음 20개):</h3>";
echo "<table border='1' style='border-collapse: collapse; width:100%;'>";
echo "<tr><th style='background:#4CAF50; color:white; padding:10px;'>NO</th>";
echo "<th style='background:#4CAF50; color:white; padding:10px;'>BOOKING</th>";
echo "<th style='background:#4CAF50; color:white; padding:10px;'>CNTR NO</th>";
echo "<th style='background:#4CAF50; color:white; padding:10px;'>SHIPPER</th>";
echo "<th style='background:#4CAF50; color:white; padding:10px;'>Source Sheet</th></tr>";

for ($i = 0; $i < min(20, count($allCntrNos)); $i++) {
    echo "<tr>";
    echo "<td style='padding:8px;'>" . ($i + 1) . "</td>";
    echo "<td style='padding:8px;'>" . htmlspecialchars($allCntrNos[$i]['booking']) . "</td>";
    echo "<td style='padding:8px;'>" . htmlspecialchars($allCntrNos[$i]['cntr_no']) . "</td>";
    echo "<td style='padding:8px;'>" . htmlspecialchars($allCntrNos[$i]['shipper']) . "</td>";
    echo "<td style='padding:8px;'>" . htmlspecialchars($allCntrNos[$i]['source']) . "</td>";
    echo "</tr>";
}

echo "</table>";
?>
