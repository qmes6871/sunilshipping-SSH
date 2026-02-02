<?php
/**
 * 예약 내역 모듈
 * booking_requests 테이블에서 고객의 과거 예약 내역을 조회하고 표시
 */

/**
 * 고객의 예약 내역 조회
 */
function getBookingHistory($customerEmail, $limit = 50, $offset = 0) {
    global $pdo;
    try {
        if (!$pdo) return [];

        $stmt = $pdo->prepare("
            SELECT
                id,
                product_id,
                ship_id,
                name,
                email,
                phone,
                status,
                DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') as created_at,
                customer_name,
                customer_email,
                customer_phone,
                nationality,
                special_requirements,
                booking_reference,
                payment_status
            FROM booking_requests
            WHERE customer_email = ? OR email = ?
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");

        $stmt->execute([$customerEmail, $customerEmail, $limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("예약 내역 조회 오류: " . $e->getMessage());
        return [];
    }
}

/**
 * 예약 총 개수 조회
 */
function getBookingCount($customerEmail) {
    global $pdo;
    try {
        if (!$pdo) return 0;

        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM booking_requests
            WHERE customer_email = ? OR email = ?
        ");

        $stmt->execute([$customerEmail, $customerEmail]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    } catch (PDOException $e) {
        error_log("예약 개수 조회 오류: " . $e->getMessage());
        return 0;
    }
}

/**
 * 상태별 통계 조회
 */
function getBookingStats($customerEmail) {
    global $pdo;
    try {
        if (!$pdo) return [];

        $stmt = $pdo->prepare("
            SELECT
                status,
                COUNT(*) as count
            FROM booking_requests
            WHERE customer_email = ? OR email = ?
            GROUP BY status
        ");

        $stmt->execute([$customerEmail, $customerEmail]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("예약 통계 조회 오류: " . $e->getMessage());
        return [];
    }
}

/**
 * 예약 내역 섹션 HTML 생성
 */
function displayBookingHistorySection($customerEmail) {
    if (!$customerEmail) {
        return '<div class="alert alert-error">고객 정보를 찾을 수 없습니다.</div>';
    }

    // 데이터 조회
    $bookings = getBookingHistory($customerEmail);
    $totalCount = getBookingCount($customerEmail);
    $stats = getBookingStats($customerEmail);

    // 상태별 색상 매핑
    $statusColors = [
        'pending' => '#f59e0b',
        'confirmed' => '#10b981',
        'cancelled' => '#ef4444',
        'completed' => '#6366f1'
    ];

    $statusLabels = [
        'pending' => '대기중',
        'confirmed' => '확정',
        'cancelled' => '취소',
        'completed' => '완료'
    ];

    $paymentStatusColors = [
        'pending' => '#f59e0b',
        'paid' => '#10b981',
        'failed' => '#ef4444',
        'refunded' => '#6b7280'
    ];

    $paymentStatusLabels = [
        'pending' => '결제대기',
        'paid' => '결제완료',
        'failed' => '결제실패',
        'refunded' => '환불완료'
    ];

    ob_start();
    ?>
    <div class="booking-history-section" style="margin-top: 40px;">
        <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h2 style="color: #1f2937; margin-bottom: 10px;"><i class="fas fa-calendar-check"></i> 예약 내역</h2>
                <p style="color: #6b7280; margin: 0;">총 <?= $totalCount ?>개의 예약 내역이 있습니다.</p>
            </div>
            <button id="toggleBookingHistory" onclick="toggleBookingHistorySection()" style="background: #6b7280; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 500; transition: background 0.3s;">
                <i class="fas fa-chevron-down" id="toggleBookingIcon"></i> <span id="toggleBookingText">펼치기</span>
            </button>
        </div>

        <div id="bookingHistoryContent" class="booking-container collapsed" style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">

            <?php if (!empty($stats)): ?>
            <!-- 통계 카드 -->
            <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 30px;">
                <?php foreach ($stats as $stat): ?>
                    <?php
                    $status = $stat['status'];
                    $count = $stat['count'];
                    $color = $statusColors[$status] ?? '#6b7280';
                    $label = $statusLabels[$status] ?? $status;
                    ?>
                    <div class="stat-card" style="background: <?= $color ?>15; border-left: 4px solid <?= $color ?>; padding: 15px; border-radius: 6px;">
                        <div style="font-size: 24px; font-weight: bold; color: <?= $color ?>; margin-bottom: 5px;"><?= $count ?></div>
                        <div style="color: #6b7280; font-size: 13px;"><?= $label ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (empty($bookings)): ?>
                <!-- 예약 내역 없음 -->
                <div style="text-align: center; padding: 60px 20px; background: #f9fafb; border-radius: 8px;">
                    <i class="fas fa-calendar-times" style="font-size: 48px; color: #d1d5db; margin-bottom: 15px;"></i>
                    <p style="color: #6b7280; font-size: 16px; margin: 0;">아직 예약 내역이 없습니다.</p>
                </div>
            <?php else: ?>
                <!-- 예약 목록 -->
                <div class="bookings-list">
                    <?php foreach ($bookings as $booking): ?>
                        <?php
                        $status = $booking['status'] ?? 'pending';
                        $statusColor = $statusColors[$status] ?? '#6b7280';
                        $statusLabel = $statusLabels[$status] ?? $status;

                        $paymentStatus = $booking['payment_status'] ?? 'pending';
                        $paymentColor = $paymentStatusColors[$paymentStatus] ?? '#6b7280';
                        $paymentLabel = $paymentStatusLabels[$paymentStatus] ?? $paymentStatus;
                        ?>
                        <div class="booking-card" style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin-bottom: 15px; background: #fafafa;">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
                                <div>
                                    <h3 style="color: #1f2937; margin: 0 0 5px 0; font-size: 16px;">
                                        <?php if ($booking['booking_reference']): ?>
                                            예약번호: <strong><?= htmlspecialchars($booking['booking_reference']) ?></strong>
                                        <?php else: ?>
                                            예약 ID: <strong>#<?= $booking['id'] ?></strong>
                                        <?php endif; ?>
                                    </h3>
                                    <p style="color: #6b7280; margin: 0; font-size: 13px;">
                                        <i class="fas fa-clock"></i> <?= htmlspecialchars($booking['created_at']) ?>
                                    </p>
                                </div>
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    <span style="background: <?= $statusColor ?>; color: white; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; white-space: nowrap;">
                                        <?= $statusLabel ?>
                                    </span>
                                    <span style="background: <?= $paymentColor ?>; color: white; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; white-space: nowrap;">
                                        <?= $paymentLabel ?>
                                    </span>
                                </div>
                            </div>

                            <div class="booking-details" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                                <div>
                                    <p style="color: #6b7280; font-size: 12px; margin: 0 0 5px 0;">예약자 정보</p>
                                    <p style="color: #1f2937; margin: 0; font-size: 14px;">
                                        <strong><?= htmlspecialchars($booking['customer_name'] ?? $booking['name'] ?? '-') ?></strong>
                                    </p>
                                    <?php if ($booking['customer_phone'] || $booking['phone']): ?>
                                        <p style="color: #6b7280; margin: 3px 0 0 0; font-size: 13px;">
                                            <i class="fas fa-phone"></i> <?= htmlspecialchars($booking['customer_phone'] ?? $booking['phone']) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <?php if ($booking['nationality']): ?>
                                <div>
                                    <p style="color: #6b7280; font-size: 12px; margin: 0 0 5px 0;">국적</p>
                                    <p style="color: #1f2937; margin: 0; font-size: 14px;">
                                        <?= htmlspecialchars($booking['nationality']) ?>
                                    </p>
                                </div>
                                <?php endif; ?>

                                <?php if ($booking['product_id']): ?>
                                <div>
                                    <p style="color: #6b7280; font-size: 12px; margin: 0 0 5px 0;">상품 ID</p>
                                    <p style="color: #1f2937; margin: 0; font-size: 14px;">
                                        #<?= htmlspecialchars($booking['product_id']) ?>
                                    </p>
                                </div>
                                <?php endif; ?>

                                <?php if ($booking['ship_id']): ?>
                                <div>
                                    <p style="color: #6b7280; font-size: 12px; margin: 0 0 5px 0;">선박 ID</p>
                                    <p style="color: #1f2937; margin: 0; font-size: 14px;">
                                        #<?= htmlspecialchars($booking['ship_id']) ?>
                                    </p>
                                </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($booking['special_requirements']): ?>
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e5e7eb;">
                                <p style="color: #6b7280; font-size: 12px; margin: 0 0 5px 0;">
                                    <i class="fas fa-sticky-note"></i> 특별 요청사항
                                </p>
                                <p style="color: #1f2937; margin: 0; font-size: 13px; line-height: 1.6;">
                                    <?= nl2br(htmlspecialchars($booking['special_requirements'])) ?>
                                </p>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
        /* 모바일 반응형 */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr) !important;
            }

            .booking-details {
                grid-template-columns: 1fr !important;
            }
        }

        #toggleBookingHistory:hover {
            background: #4b5563 !important;
        }

        #bookingHistoryContent {
            transition: all 0.3s ease;
            overflow: hidden;
        }

        #bookingHistoryContent.collapsed {
            max-height: 0 !important;
            padding: 0 30px !important;
            opacity: 0;
        }

        .booking-card {
            transition: all 0.3s ease;
        }

        .booking-card:hover {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
    </style>

    <script>
        function toggleBookingHistorySection() {
            const content = document.getElementById('bookingHistoryContent');
            const icon = document.getElementById('toggleBookingIcon');
            const text = document.getElementById('toggleBookingText');

            if (content.classList.contains('collapsed')) {
                // 펼치기
                content.classList.remove('collapsed');
                icon.className = 'fas fa-chevron-up';
                text.textContent = '접기';
            } else {
                // 접기
                content.classList.add('collapsed');
                icon.className = 'fas fa-chevron-down';
                text.textContent = '펼치기';
            }
        }
    </script>

    <?php
    return ob_get_clean();
}
?>
