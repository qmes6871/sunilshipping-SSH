<?php
/**
 * 서류 다운로드 모듈
 * 컨테이너 운송 서류(인보이스, BL, 면장) 다운로드 기능 제공
 */

/**
 * 서류 다운로드 섹션 표시
 * @param array $trackingData 트래킹 데이터 배열
 * @return string HTML 출력
 */
function displayDocumentDownloadSection($trackingData) {
    // 서류가 있는 데이터만 필터링
    $documentsData = array_filter($trackingData, function($row) {
        return !empty($row['invoice_file']) || !empty($row['bl_file']) || !empty($row['packing_list_file']);
    });
    
    ob_start(); // 출력 버퍼링 시작
    ?>
    
    <!-- 서류 다운로드 모듈 -->
    <div class="tracking-table" style="margin-bottom: 30px;">
        <div class="table-header">
            <div class="table-header-actions">
                <div class="table-header-info">
                    <h3><i class="fas fa-file-download"></i> 운송 서류 다운로드</h3>
                    <div class="table-info">
                        인보이스, BL(선하증권), 면장(Packing List) 다운로드
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (empty($documentsData)): ?>
            <div class="empty-state">
                <i class="fas fa-folder-open" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3; color: #9ca3af;"></i>
                <h3>다운로드 가능한 서류가 없습니다</h3>
                <p>운송 서류가 준비되면 이곳에서 다운로드하실 수 있습니다.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>컨테이너 번호</th>
                            <th>구매자</th>
                            <th>항로</th>
                            <th style="text-align: center;">인보이스</th>
                            <th style="text-align: center;">BL</th>
                            <th style="text-align: center;">면장</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documentsData as $row): ?>
                            <tr>
                                <td><strong class="container-no"><?= htmlspecialchars($row['cntr_no']) ?></strong></td>
                                <td><?= htmlspecialchars($row['buyer']) ?></td>
                                <td style="font-size: 13px;">
                                    <?= htmlspecialchars($row['port_1']) ?> → <?= htmlspecialchars($row['port_2']) ?>
                                </td>
                                <td style="text-align: center;">
                                    <?= renderDownloadIcon($row['invoice_file'] ?? '', 'invoice', $row['id'] ?? 0) ?>
                                </td>
                                <td style="text-align: center;">
                                    <?= renderDownloadIcon($row['bl_file'] ?? '', 'bl', $row['id'] ?? 0) ?>
                                </td>
                                <td style="text-align: center;">
                                    <?= renderDownloadIcon($row['packing_list_file'] ?? '', 'packing', $row['id'] ?? 0) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <?php
    return ob_get_clean();
}

/**
 * 다운로드 아이콘 렌더링
 * @param string $fileName 파일명
 * @param string $type 파일 타입 (invoice, bl, packing)
 * @param int $trackingId 트래킹 ID
 * @return string HTML 아이콘
 */
function renderDownloadIcon($fileName, $type, $trackingId) {
    if (empty($fileName)) {
        return '<span style="color: #d1d5db; font-size: 22px;">-</span>';
    }

    $url = 'download.php?file=' . urlencode($fileName) . '&type=' . urlencode($type) . '&id=' . intval($trackingId);

    return '<a href="' . htmlspecialchars($url) . '" title="다운로드" style="display: inline-block; color: #2563eb; font-size: 24px; text-decoration: none; transition: all 0.2s; padding: 4px;" onmouseover="this.style.color=\'#1d4ed8\'; this.style.transform=\'scale(1.1)\';" onmouseout="this.style.color=\'#2563eb\'; this.style.transform=\'scale(1)\';"><i class="fas fa-download"></i></a>';
}

/**
 * 다운로드 버튼 렌더링 (하위 호환성)
 * @param string $fileName 파일명
 * @param string $type 파일 타입 (invoice, bl, packing)
 * @param int $trackingId 트래킹 ID
 * @return string HTML 버튼
 */
function renderDownloadButton($fileName, $type, $trackingId) {
    if (empty($fileName)) {
        return '<span style="color: #9ca3af;">-</span>';
    }

    $url = 'download.php?file=' . urlencode($fileName) . '&type=' . urlencode($type) . '&id=' . intval($trackingId);

    return '<a href="' . htmlspecialchars($url) . '" class="btn" style="padding: 6px 12px; font-size: 13px; background: #2563eb; color: white; text-decoration: none; border-radius: 4px; display: inline-block;"><i class="fas fa-download"></i> 다운로드</a>';
}

/**
 * 서류 통계 정보 조회
 * @param string $customerId 고객 ID
 * @param PDO $pdo 데이터베이스 연결 객체
 * @return array 서류 통계 정보
 */
function getDocumentStats($customerId, $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_containers,
                SUM(CASE WHEN invoice_file IS NOT NULL AND invoice_file != '' THEN 1 ELSE 0 END) as has_invoice,
                SUM(CASE WHEN bl_file IS NOT NULL AND bl_file != '' THEN 1 ELSE 0 END) as has_bl,
                SUM(CASE WHEN packing_list_file IS NOT NULL AND packing_list_file != '' THEN 1 ELSE 0 END) as has_packing
            FROM shipping_tracking
            WHERE customer_id = ?
        ");
        $stmt->execute([$customerId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Document stats error: " . $e->getMessage());
        return [
            'total_containers' => 0,
            'has_invoice' => 0,
            'has_bl' => 0,
            'has_packing' => 0
        ];
    }
}

/**
 * 고객의 서류가 있는 컨테이너만 조회
 * @param string $customerId 고객 ID
 * @param PDO $pdo 데이터베이스 연결 객체
 * @return array 서류가 있는 컨테이너 목록
 */
function getContainersWithDocuments($customerId, $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                id,
                cntr_no,
                buyer,
                port_1,
                port_2,
                invoice_file,
                bl_file,
                packing_list_file,
                reg_date
            FROM shipping_tracking
            WHERE customer_id = ?
            AND (
                (invoice_file IS NOT NULL AND invoice_file != '')
                OR (bl_file IS NOT NULL AND bl_file != '')
                OR (packing_list_file IS NOT NULL AND packing_list_file != '')
            )
            ORDER BY reg_date DESC
        ");
        $stmt->execute([$customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get containers with documents error: " . $e->getMessage());
        return [];
    }
}

/**
 * 파일 타입에 따른 아이콘 반환
 * @param string $type 파일 타입
 * @return string Font Awesome 아이콘 클래스
 */
function getDocumentIcon($type) {
    $icons = [
        'invoice' => 'fa-file-invoice',
        'bl' => 'fa-file-alt',
        'packing' => 'fa-file-pdf'
    ];
    return $icons[$type] ?? 'fa-file';
}

/**
 * 파일 타입에 따른 한글 이름 반환
 * @param string $type 파일 타입
 * @return string 한글 이름
 */
function getDocumentTypeName($type) {
    $names = [
        'invoice' => '인보이스',
        'bl' => 'BL (선하증권)',
        'packing' => '면장 (Packing List)'
    ];
    return $names[$type] ?? '서류';
}
?>
