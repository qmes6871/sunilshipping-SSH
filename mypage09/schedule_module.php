<?php
/**
 * 스케줄 모듈 - 운항 스케줄 관리 (ss_sailing_schedule 테이블 기반)
 */

/**
 * 스케줄 데이터베이스 테이블 생성
 */
function createScheduleTable() {
    global $pdo;
    
    $sql = "CREATE TABLE IF NOT EXISTS ss_sailing_schedule (
        id INT(11) NOT NULL AUTO_INCREMENT,
        voyage_number VARCHAR(50) NOT NULL COLLATE utf8mb3_general_ci,
        vessel_id INT(11) NOT NULL,
        route_id INT(11) NOT NULL,
        departure_date DATE NOT NULL,
        arrival_date DATE NOT NULL,
        departure_time TIME NULL,
        arrival_time TIME NULL,
        available_capacity INT(11) NOT NULL,
        booking_deadline DATE NULL,
        discount_rate DECIMAL(5,2) NULL DEFAULT 0.00,
        special_offer TEXT NULL COLLATE utf8mb3_general_ci,
        status ENUM('scheduled', 'sailing', 'arrived', 'cancelled') NULL DEFAULT 'scheduled' COLLATE utf8mb3_general_ci,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX idx_voyage (voyage_number),
        INDEX idx_vessel (vessel_id),
        INDEX idx_route (route_id),
        INDEX idx_departure (departure_date),
        INDEX idx_arrival (arrival_date),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci";
    
    try {
        $pdo->exec($sql);
        return true;
    } catch (PDOException $e) {
        error_log("스케줄 테이블 생성 오류: " . $e->getMessage());
        return false;
    }
}

/**
 * 선박 정보 테이블 생성
 */
function createVesselTable() {
    global $pdo;
    
    $sql = "CREATE TABLE IF NOT EXISTS ss_vessels (
        id INT(11) NOT NULL AUTO_INCREMENT,
        vessel_name VARCHAR(100) NOT NULL,
        vessel_type VARCHAR(50) DEFAULT 'container',
        capacity INT(11) DEFAULT 0,
        status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci";
    
    try {
        $pdo->exec($sql);
        return true;
    } catch (PDOException $e) {
        error_log("선박 테이블 생성 오류: " . $e->getMessage());
        return false;
    }
}

/**
 * 노선 정보 테이블 생성
 */
function createRouteTable() {
    global $pdo;
    
    $sql = "CREATE TABLE IF NOT EXISTS ss_routes (
        id INT(11) NOT NULL AUTO_INCREMENT,
        route_name VARCHAR(100) NOT NULL,
        departure_port VARCHAR(100) NOT NULL,
        arrival_port VARCHAR(100) NOT NULL,
        transit_days INT(11) DEFAULT 0,
        service_type VARCHAR(50) DEFAULT 'regular',
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci";
    
    try {
        $pdo->exec($sql);
        return true;
    } catch (PDOException $e) {
        error_log("노선 테이블 생성 오류: " . $e->getMessage());
        return false;
    }
}

/**
 * 스케줄 데이터 조회 (단일 테이블: ss_sailing_schedule)
 */
function getScheduleData($filters = []) {
    global $pdo;
    
    $where = [];
    $params = [];
    
    if (!empty($filters['date_from'])) {
        $where[] = "departure_date >= :date_from";
        $params['date_from'] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $where[] = "departure_date <= :date_to";
        $params['date_to'] = $filters['date_to'];
    }
    
    if (!empty($filters['status'])) {
        $where[] = "status = :status";
        $params['status'] = $filters['status'];
    }
    
    if (!empty($filters['voyage_number'])) {
        $where[] = "voyage_number LIKE :voyage_number";
        $params['voyage_number'] = '%' . $filters['voyage_number'] . '%';
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $sql = "SELECT * FROM ss_sailing_schedule
            $whereClause
            ORDER BY departure_date ASC, created_at DESC";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("스케줄 데이터 조회 오류: " . $e->getMessage());
        return [];
    }
}

/**
 * 스케줄 상태 텍스트 변환
 */
function getScheduleStatusText($status) {
    $statusTexts = [
        'scheduled' => '<span style="color: #2563eb; font-weight: bold;">예정</span>',
        'sailing' => '<span style="color: #059669; font-weight: bold;">운항중</span>',
        'arrived' => '<span style="color: #0891b2; font-weight: bold;">도착</span>',
        'cancelled' => '<span style="color: #dc2626; font-weight: bold;">취소</span>'
    ];
    
    return $statusTexts[$status] ?? '<span style="color: #6b7280;">알 수 없음</span>';
}

/**
 * 서비스 타입 배지 생성
 */
function getServiceTypeBadge($serviceType) {
    $badges = [
        'express' => '<span class="service-badge service-express">Express</span>',
        'regular' => '<span class="service-badge service-regular">Regular</span>',
        'premium' => '<span class="service-badge service-premium">Premium</span>'
    ];
    
    return $badges[$serviceType] ?? '<span class="service-badge service-regular">Regular</span>';
}

/**
 * 할인율 표시
 */
function getDiscountDisplay($discountRate) {
    if ($discountRate && $discountRate > 0) {
        return '<span style="color: #dc2626; font-weight: bold;">-' . number_format($discountRate, 1) . '%</span>';
    }
    return '-';
}

/**
 * 스케줄 섹션 표시
 */
function displayScheduleSection($is_logged_in = false) {
    if (!$is_logged_in) {
        return '';
    }
    
    // 필요한 테이블 생성 (메인 테이블만 사용)
    createScheduleTable();

    // 샘플 데이터 삽입 (테이블이 비어있는 경우)
    global $pdo;
    try {
        $checkStmt = $pdo->query("SELECT COUNT(*) FROM ss_sailing_schedule");
        $count = $checkStmt->fetchColumn();
        if ($count == 0) {
            insertSampleScheduleData();
        }
    } catch (PDOException $e) {
        error_log("데이터 확인 오류: " . $e->getMessage());
    }

    // 검색 필터
    $filters = [
        'date_from' => $_GET['sched_date_from'] ?? '',
        'date_to' => $_GET['sched_date_to'] ?? '',
        'status' => $_GET['sched_status'] ?? '',
        'voyage_number' => $_GET['voyage_number'] ?? ''
    ];
    
    // 스케줄 데이터 조회
    $scheduleData = getScheduleData($filters);
    
    ob_start();
    ?>
    <div class="schedule-module">
    <div class="schedule-section">
        <div class="section-header">
            <h2>운항 스케줄</h2>
            <p>실시간 운항 스케줄을 확인하세요</p>
        </div>
        
        <!-- 스케줄 검색 폼 -->
        <div class="schedule-search-form">
            <form method="GET" id="scheduleForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="voyage_number">항차번호</label>
                        <input type="text" 
                               id="voyage_number" 
                               name="voyage_number" 
                               value="<?= htmlspecialchars($filters['voyage_number']) ?>"
                               placeholder="예: 001E">
                    </div>
                    <div class="form-group">
                        <label for="sched_date_from">출발일 (시작)</label>
                        <input type="date" 
                               id="sched_date_from" 
                               name="sched_date_from" 
                               value="<?= htmlspecialchars($filters['date_from']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="sched_date_to">출발일 (종료)</label>
                        <input type="date" 
                               id="sched_date_to" 
                               name="sched_date_to" 
                               value="<?= htmlspecialchars($filters['date_to']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="sched_status">상태</label>
                        <select id="sched_status" name="sched_status">
                            <option value="">전체 상태</option>
                            <option value="scheduled" <?= $filters['status'] === 'scheduled' ? 'selected' : '' ?>>예정</option>
                            <option value="sailing" <?= $filters['status'] === 'sailing' ? 'selected' : '' ?>>운항중</option>
                            <option value="arrived" <?= $filters['status'] === 'arrived' ? 'selected' : '' ?>>도착</option>
                            <option value="cancelled" <?= $filters['status'] === 'cancelled' ? 'selected' : '' ?>>취소</option>
                        </select>
                    </div>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> 검색
                    </button>
                    <button type="button" onclick="resetScheduleForm()" class="btn">
                        <i class="fas fa-redo"></i> 초기화
                    </button>
                </div>
            </form>
        </div>
        
        <!-- 스케줄 테이블 -->
        <div class="schedule-table">
            <div class="table-header">
                <div class="table-header-info">
                    <h3>운항 스케줄 현황</h3>
                    <div class="table-info">
                        총 <?= count($scheduleData) ?>건의 운항 스케줄 | 업데이트: <?= date('Y-m-d H:i') ?>
                    </div>
                </div>
                <div class="table-header-buttons">
                    <?php if (!empty($scheduleData)): ?>
                        <button onclick="exportScheduleToExcel()" class="btn btn-excel">
                            <i class="fas fa-file-excel"></i> 엑셀 다운로드
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (empty($scheduleData)): ?>
                <div class="empty-state">
                    <i class="fas fa-ship" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3; color: #9ca3af;"></i>
                    <h3>🚢 운항 스케줄이 없습니다</h3>
                    <p>검색 조건을 변경하거나 새로운 스케줄을 등록해주세요.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table id="scheduleTable">
                        <thead>
                            <tr>
                                <th>항차번호</th>
                                <th>출발일시</th>
                                <th>도착일시</th>
                                <th>가용용량</th>
                                <th>예약마감</th>
                                <th>할인율</th>
                                <th>상태</th>
                                <th>특별제안</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($scheduleData as $row): ?>
                                <tr>
                                    <td>
                                        <span style="color: #2563eb; font-weight: bold;">
                                            <?= htmlspecialchars($row['voyage_number']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= date('Y-m-d', strtotime($row['departure_date'])) ?>
                                        <?php if ($row['departure_time']): ?>
                                            <br><small style="color: #6b7280;"><?= $row['departure_time'] ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= date('Y-m-d', strtotime($row['arrival_date'])) ?>
                                        <?php if ($row['arrival_time']): ?>
                                            <br><small style="color: #6b7280;"><?= $row['arrival_time'] ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span style="color: #059669; font-weight: bold;">
                                            <?= number_format($row['available_capacity']) ?> TEU
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($row['booking_deadline']): ?>
                                            <?= date('Y-m-d', strtotime($row['booking_deadline'])) ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?= getDiscountDisplay($row['discount_rate']) ?></td>
                                    <td><?= getScheduleStatusText($row['status']) ?></td>
                                    <td>
                                        <?php if ($row['special_offer']): ?>
                                            <span style="color: #dc2626; font-weight: bold;" title="<?= htmlspecialchars($row['special_offer']) ?>">
                                                특가
                                            </span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </div>
    
    <script>
        // 스케줄 폼 초기화
        function resetScheduleForm() {
            document.getElementById('voyage_number').value = '';
            document.getElementById('sched_date_from').value = '';
            document.getElementById('sched_date_to').value = '';
            document.getElementById('sched_status').value = '';
            document.getElementById('scheduleForm').submit();
        }
        
        // 스케줄 엑셀 다운로드
        function exportScheduleToExcel() {
            const table = document.getElementById('scheduleTable');
            if (!table) {
                alert('다운로드할 데이터가 없습니다.');
                return;
            }
            
            let csv = '';
            const rows = table.querySelectorAll('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].querySelectorAll('th, td');
                let row = [];
                
                for (let j = 0; j < cells.length; j++) {
                    let cellText = cells[j].innerText.trim();
                    if (cellText.includes(',') || cellText.includes('"') || cellText.includes('\n')) {
                        cellText = '"' + cellText.replace(/"/g, '""') + '"';
                    }
                    row.push(cellText);
                }
                csv += row.join(',') + '\n';
            }
            
            const BOM = '\uFEFF';
            const csvWithBOM = BOM + csv;
            
            const blob = new Blob([csvWithBOM], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            
            if (link.download !== undefined) {
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', '운항스케줄_' + new Date().toISOString().slice(0,10) + '.csv');
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } else {
                alert('브라우저에서 파일 다운로드를 지원하지 않습니다.');
            }
        }
    </script>
    <?php
    return ob_get_clean();
}

/**
 * 샘플 데이터 삽입
 */
function insertSampleScheduleData() {
    global $pdo;
    
    try {
        // 선박 데이터 삽입
        $vesselSql = "INSERT IGNORE INTO ss_vessels (id, vessel_name, vessel_type, capacity) VALUES 
                      (1, 'SUNIL EXPRESS', 'container', 8000),
                      (2, 'SUNIL STAR', 'container', 12000),
                      (3, 'SUNIL OCEAN', 'container', 10000)";
        $pdo->exec($vesselSql);
        
        // 노선 데이터 삽입
        $routeSql = "INSERT IGNORE INTO ss_routes (id, route_name, departure_port, arrival_port, transit_days, service_type) VALUES 
                     (1, '부산-로스앤젤레스', '부산항', '로스앤젤레스항', 15, 'express'),
                     (2, '부산-롱비치', '부산항', '롱비치항', 15, 'regular'),
                     (3, '부산-오클랜드', '부산항', '오클랜드항', 16, 'regular')";
        $pdo->exec($routeSql);
        
        // 스케줄 데이터 삽입
        $scheduleSql = "INSERT IGNORE INTO ss_sailing_schedule 
                        (voyage_number, vessel_id, route_id, departure_date, arrival_date, departure_time, arrival_time, 
                         available_capacity, booking_deadline, discount_rate, special_offer, status) VALUES 
                        ('001E', 1, 1, '2024-01-15', '2024-01-30', '14:00:00', '08:00:00', 7500, '2024-01-10', 5.00, '조기 예약 할인', 'scheduled'),
                        ('002W', 2, 2, '2024-01-18', '2024-02-02', '10:00:00', '16:00:00', 11000, '2024-01-13', 0.00, NULL, 'sailing'),
                        ('003E', 3, 3, '2024-01-20', '2024-02-05', '16:00:00', '12:00:00', 9500, '2024-01-15', 3.00, '신규 노선 특가', 'scheduled')";
        $pdo->exec($scheduleSql);
        
        return true;
    } catch (PDOException $e) {
        error_log("샘플 데이터 삽입 오류: " . $e->getMessage());
        return false;
    }
}
?>