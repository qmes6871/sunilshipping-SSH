<?php
// 예약 완료 안내 모듈: 예약 완료 표시 + 담당자 정보 출력
// 사용 방법:
//   include __DIR__.'/reservation_success.php';
//   세션에 'reservation_success', 'booking_reference', 'reserved_product_id' 값을 사용합니다.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$has_success = !empty($_SESSION['reservation_success']);
$booking_reference = $_SESSION['booking_reference'] ?? null;
$product_id = isset($_SESSION['reserved_product_id']) ? (int)$_SESSION['reserved_product_id'] : null;

if (!$has_success) {
    return; // 아무 것도 출력하지 않음
}

// DB 연결 (reservation.php와 동일한 접속 정보 사용)
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=sunilshipping;charset=utf8mb4',
        'sunilshipping',
        'sunil123!',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Throwable $e) {
    $pdo = null; // 연결 실패 시에도 완료 알림만 표시
}

// product_id 없으면 booking_reference로 조회 (booking_requests -> product_id)
if (!$product_id && $pdo && $booking_reference) {
    try {
        $stmt = $pdo->prepare('SELECT product_id FROM booking_requests WHERE booking_reference = ? LIMIT 1');
        $stmt->execute([$booking_reference]);
        $pid = $stmt->fetchColumn();
        if ($pid) { $product_id = (int)$pid; }
    } catch (Throwable $ignore) {}
}

$product = null;
$staff = null;
if ($pdo && $product_id) {
    try {
        $stmt = $pdo->prepare(
            'SELECT sp.*, sm.id AS staff_id, sm.name AS staff_name, sm.email AS staff_email, sm.phone AS staff_phone, sm.position AS staff_position, sm.department AS staff_department
             FROM shipping_products sp
             LEFT JOIN staff_management sm ON sp.staff_id = sm.id
             WHERE sp.id = ? LIMIT 1'
        );
        $stmt->execute([$product_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $product = $row;
            $staff = [
                'name' => $row['staff_name'] ?? '',
                'email' => $row['staff_email'] ?? '',
                'phone' => $row['staff_phone'] ?? '',
                'position' => $row['staff_position'] ?? '',
                'department' => $row['staff_department'] ?? '',
            ];
        }
    } catch (Throwable $ignore) {}
}

// 출력
?>
<div style="max-width: 860px; margin: 20px auto 32px; border: 1px solid #e5e7eb; background: #ffffff; border-radius: 12px; box-shadow: 0 10px 18px rgba(0,0,0,0.06); overflow: hidden;">
  <div style="background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; padding: 18px 22px; display:flex; align-items:center; gap:10px;">
    <i class="fas fa-check-circle" style="font-size:20px"></i>
    <div style="font-weight:700; font-size:16px;">예약 접수가 완료되었습니다.</div>
  </div>
  <div style="padding: 18px 22px;">
    <?php if (!empty($_SESSION['success_message'])): ?>
      <div style="background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; padding:10px 12px; border-radius:8px; font-weight:600; margin-bottom:12px;">
        <?= htmlspecialchars($_SESSION['success_message']) ?>
      </div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns: repeat(auto-fit,minmax(220px,1fr)); gap:12px;">
      <div style="background:#f8fafc; border:1px solid #e5e7eb; border-radius:8px; padding:12px;">
        <div style="color:#64748b; font-size:12px;">예약번호</div>
        <div style="font-weight:700; font-size:16px; color:#111827; margin-top:4px;">
          <?= htmlspecialchars($booking_reference ?? '-') ?>
        </div>
      </div>
      <?php if ($product): ?>
      <div style="background:#f8fafc; border:1px solid #e5e7eb; border-radius:8px; padding:12px;">
        <div style="color:#64748b; font-size:12px;">상품</div>
        <div style="font-weight:700; font-size:16px; color:#111827; margin-top:4px;">
          <?= htmlspecialchars($product['product_name'] ?? ($product['vessel_name'] ?? '상품')) ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <div style="margin-top:16px; border-top:1px dashed #e5e7eb; padding-top:16px; display:grid; grid-template-columns: 1fr; gap:12px;">
      <div style="font-weight:700; color:#111827; font-size:15px; display:flex; align-items:center; gap:8px;">
        <i class="fas fa-user-tie" style="color:#2563eb"></i> 담당자 정보
      </div>
      <div style="display:grid; grid-template-columns: repeat(auto-fit,minmax(220px,1fr)); gap:12px;">
        <div style="background:#f8fafc; border:1px solid #e5e7eb; border-radius:8px; padding:12px;">
          <div style="color:#64748b; font-size:12px;">담당자</div>
          <div style="font-weight:600; color:#111827; margin-top:4px;">
            <?= htmlspecialchars(($staff['name'] ?? '') ?: '지정된 담당자가 없습니다') ?>
            <?php if (!empty($staff['position'])): ?>
              <span style="color:#6b7280; font-weight:500;">(<?= htmlspecialchars($staff['position']) ?>)</span>
            <?php endif; ?>
          </div>
        </div>
        <div style="background:#f8fafc; border:1px solid #e5e7eb; border-radius:8px; padding:12px;">
          <div style="color:#64748b; font-size:12px;">이메일</div>
          <div style="font-weight:600; color:#111827; margin-top:4px;">
            <?= htmlspecialchars($staff['email'] ?? '-') ?>
          </div>
        </div>
        <div style="background:#f8fafc; border:1px solid #e5e7eb; border-radius:8px; padding:12px;">
          <div style="color:#64748b; font-size:12px;">연락처</div>
          <div style="font-weight:600; color:#111827; margin-top:4px;">
            <?= htmlspecialchars($staff['phone'] ?? '-') ?>
          </div>
        </div>
        <?php if (!empty($staff['department'])): ?>
        <div style="background:#f8fafc; border:1px solid #e5e7eb; border-radius:8px; padding:12px;">
          <div style="color:#64748b; font-size:12px;">부서</div>
          <div style="font-weight:600; color:#111827; margin-top:4px;">
            <?= htmlspecialchars($staff['department']) ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
      <div style="margin-top:6px; color:#6b7280; font-size:13px;">
        담당자 배정이 없거나 연락처가 비어 있는 경우, 대표 연락처로 문의해 주세요: <a href="mailto:info@sunilshipping.co.kr">info@sunilshipping.co.kr</a>
      </div>
    </div>

    <div style="margin-top:18px; display:flex; gap:10px; flex-wrap:wrap;">
      <a href="index.php" class="btn" style="display:inline-flex; align-items:center; gap:6px; background:#111827; color:#fff; padding:10px 16px; border-radius:8px; text-decoration:none;">
        <i class="fas fa-list"></i> 예약 목록으로
      </a>
      <a href="../index.php" class="btn" style="display:inline-flex; align-items:center; gap:6px; background:#2563eb; color:#fff; padding:10px 16px; border-radius:8px; text-decoration:none;">
        <i class="fas fa-home"></i> 홈으로 이동
      </a>
    </div>
  </div>
</div>

<?php
// 일회성 세션 플래그 정리
unset($_SESSION['reservation_success']);
// 성공 메시지는 다른 화면에서 재표시할 수 있도록 유지할 수도 있으나, 여기서는 함께 정리
unset($_SESSION['success_message']);
// 예약 식별자 유지 여부는 정책에 맞게. 여기서는 유지
// unset($_SESSION['booking_reference']);
// unset($_SESSION['reserved_product_id']);
?>

