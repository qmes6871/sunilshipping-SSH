<?php
/**
 * 스케줄 데이터베이스 설정 스크립트 (ss_sailing_schedule 테이블 기반)
 */

require_once 'config.php';
require_once 'schedule_module.php';

echo "<h2>스케줄 데이터베이스 설정 (ss_sailing_schedule)</h2>";

// 1. 선박 테이블 생성
echo "<h3>1. 선박 테이블 생성</h3>";
if (createVesselTable()) {
    echo "<p style='color: green;'>✅ 선박 테이블이 성공적으로 생성되었습니다.</p>";
} else {
    echo "<p style='color: red;'>❌ 선박 테이블 생성에 실패했습니다.</p>";
}

// 2. 노선 테이블 생성
echo "<h3>2. 노선 테이블 생성</h3>";
if (createRouteTable()) {
    echo "<p style='color: green;'>✅ 노선 테이블이 성공적으로 생성되었습니다.</p>";
} else {
    echo "<p style='color: red;'>❌ 노선 테이블 생성에 실패했습니다.</p>";
}

// 3. 스케줄 테이블 생성
echo "<h3>3. 스케줄 테이블 생성</h3>";
if (createScheduleTable()) {
    echo "<p style='color: green;'>✅ 스케줄 테이블이 성공적으로 생성되었습니다.</p>";
} else {
    echo "<p style='color: red;'>❌ 스케줄 테이블 생성에 실패했습니다.</p>";
}

// 4. 샘플 데이터 삽입
echo "<h3>4. 샘플 데이터 삽입</h3>";
if (insertSampleScheduleData()) {
    echo "<p style='color: green;'>✅ 샘플 데이터가 성공적으로 삽입되었습니다.</p>";
} else {
    echo "<p style='color: red;'>❌ 샘플 데이터 삽입에 실패했습니다.</p>";
}

// 5. 데이터 확인
echo "<h3>5. 삽입된 데이터 확인</h3>";
$scheduleData = getScheduleData();
echo "<p>총 " . count($scheduleData) . "건의 스케줄 데이터가 있습니다.</p>";

if (!empty($scheduleData)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-top: 20px;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>항차번호</th><th>선박명</th><th>노선</th><th>출발항</th><th>도착항</th><th>출발일시</th><th>도착일시</th><th>가용용량</th><th>상태</th>";
    echo "</tr>";
    
    foreach ($scheduleData as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['voyage_number']) . "</td>";
        echo "<td>" . htmlspecialchars($row['vessel_name'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['route_name'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['departure_port'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['arrival_port'] ?? 'N/A') . "</td>";
        echo "<td>" . date('Y-m-d', strtotime($row['departure_date'])) . 
             ($row['departure_time'] ? ' ' . $row['departure_time'] : '') . "</td>";
        echo "<td>" . date('Y-m-d', strtotime($row['arrival_date'])) . 
             ($row['arrival_time'] ? ' ' . $row['arrival_time'] : '') . "</td>";
        echo "<td>" . number_format($row['available_capacity']) . " TEU</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr>";
echo "<p><a href='index.php'>마이페이지로 이동</a></p>";
?>
