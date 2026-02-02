<?php
require_once __DIR__ . '/db_config.php';

$conn = getDbConnection();

// 검색 기능
$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchCondition = '';
$params = [];

if (!empty($search)) {
    $searchCondition = " WHERE
        `NO` LIKE ? OR
        CNTR_NO LIKE ? OR
        SHIPPER LIKE ? OR
        BUYER LIKE ? OR
        HP LIKE ? OR
        FINAL_DEST LIKE ?";
    $searchParam = "%{$search}%";
    $params = array_fill(0, 6, $searchParam);
}

// 전체 카운트
$countSql = "SELECT COUNT(*) as total FROM tcr_tracking" . $searchCondition;
$countStmt = $conn->prepare($countSql);
if (!empty($params)) {
    $countStmt->bind_param(str_repeat('s', count($params)), ...$params);
}
$countStmt->execute();
$totalCount = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

// 데이터 조회
$sql = "SELECT * FROM tcr_tracking" . $searchCondition . " ORDER BY id ASC";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCR Tracking - Database</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Malgun Gothic', Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .stats {
            display: flex;
            gap: 20px;
            margin-top: 15px;
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
        .controls {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .search-form {
            display: flex;
            gap: 10px;
            flex: 1;
        }
        input[type="text"] {
            padding: 10px;
            flex: 1;
            max-width: 400px;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        input[type="text"]:focus {
            outline: none;
            border-color: #2196F3;
        }
        button {
            padding: 10px 20px;
            background: #2196F3;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        button:hover {
            background: #1976d2;
        }
        .sync-btn {
            background: #4CAF50;
        }
        .sync-btn:hover {
            background: #45a049;
        }
        .table-container {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .table-wrapper {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1600px;
        }
        thead {
            background: #2196F3;
            color: white;
        }
        thead tr {
            position: sticky;
            top: 0;
            z-index: 10;
        }
        th {
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #1976d2;
            font-size: 12px;
            white-space: nowrap;
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
        .no-data {
            text-align: center;
            padding: 50px;
            color: #999;
        }
        .id-col {
            text-align: center;
            background: #f5f5f5 !important;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 TCR Tracking Database</h1>
        <div class="stats">
            <div class="stat-card">
                <div class="stat-label">총 레코드</div>
                <div class="stat-value"><?= number_format($totalCount) ?></div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <div class="stat-label">마지막 업데이트</div>
                <div class="stat-value" style="font-size: 16px;">
                    <?php
                    $updateStmt = $conn->prepare("SELECT MAX(updated_at) as last_update FROM tcr_tracking");
                    $updateStmt->execute();
                    $lastUpdate = $updateStmt->get_result()->fetch_assoc()['last_update'];
                    echo $lastUpdate ? date('Y-m-d H:i', strtotime($lastUpdate)) : 'N/A';
                    $updateStmt->close();
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="controls">
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="검색어 입력..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit">검색</button>
            <?php if (!empty($search)): ?>
                <button type="button" onclick="location.href='view_db.php'">초기화</button>
            <?php endif; ?>
        </form>
        <button class="sync-btn" onclick="if(confirm('Google Sheets에서 데이터를 동기화하시겠습니까?')) location.href='sync_to_db.php'">데이터 동기화</button>
    </div>

    <div class="table-container">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>NO</th>
                        <th>CNTR NO</th>
                        <th>SHIPPER</th>
                        <th>BUYER</th>
                        <th>HP</th>
                        <th>WEIGHT</th>
                        <th>P.O.L_ETD</th>
                        <th>CHINA PORT_ETA</th>
                        <th>PORT</th>
                        <th>ETD</th>
                        <th>WAGON</th>
                        <th>ALTYNKOL_ETA</th>
                        <th>ALTYNKOL_ETD</th>
                        <th>CIS_WAGON</th>
                        <th>FINAL_ETA</th>
                        <th>FINAL_DEST</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows == 0): ?>
                        <tr>
                            <td colspan="17" class="no-data">
                                <?= !empty($search) ? '검색 결과가 없습니다.' : '데이터가 없습니다.' ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="id-col"><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['NO']) ?></td>
                                <td><?= htmlspecialchars($row['CNTR_NO']) ?></td>
                                <td><?= htmlspecialchars($row['SHIPPER']) ?></td>
                                <td><?= htmlspecialchars($row['BUYER']) ?></td>
                                <td><?= htmlspecialchars($row['HP']) ?></td>
                                <td><?= htmlspecialchars($row['WEIGHT']) ?></td>
                                <td><?= htmlspecialchars($row['POL_ETD']) ?></td>
                                <td><?= htmlspecialchars($row['CHINA_PORT_ETA']) ?></td>
                                <td><?= htmlspecialchars($row['PORT']) ?></td>
                                <td><?= htmlspecialchars($row['ETD']) ?></td>
                                <td><?= htmlspecialchars($row['WAGON']) ?></td>
                                <td><?= htmlspecialchars($row['ALTYNKOL_ETA']) ?></td>
                                <td><?= htmlspecialchars($row['ALTYNKOL_ETD']) ?></td>
                                <td><?= htmlspecialchars($row['CIS_WAGON']) ?></td>
                                <td><?= htmlspecialchars($row['FINAL_ETA']) ?></td>
                                <td><?= htmlspecialchars($row['FINAL_DEST']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php
    $stmt->close();
    $conn->close();
    ?>
</body>
</html>
