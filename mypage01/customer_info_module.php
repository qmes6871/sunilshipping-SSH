<?php
/**
 * 고객 정보 모듈
 * customer_management 테이블에서 고객 정보를 조회하고 표시
 */

/**
 * 고객 정보 조회
 */
function getCustomerInfo($username) {
    global $pdo;
    try {
        if (!$pdo) return null;
        $stmt = $pdo->prepare("
            SELECT username, name, email, phone, nationality, passport_number, grade,
                   customer_photo, passport_photo, status, terms_agreed,
                   DATE_FORMAT(created_at, '%Y-%m-%d') as created_at,
                   DATE_FORMAT(last_login, '%Y-%m-%d %H:%i') as last_login,
                   login_ip
            FROM customer_management
            WHERE username = ?
        ");
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("고객 정보 조회 오류: " . $e->getMessage());
        return null;
    }
}

/**
 * 고객 정보 섹션 HTML 생성
 */
function displayCustomerInfoSection($customer) {
    if (!$customer) {
        return '<div class="alert alert-error">고객 정보를 불러올 수 없습니다.</div>';
    }

    $gradeColors = [
        'silver' => '#94a3b8',
        'gold' => '#fbbf24',
        'vip' => '#a855f7',
        'vvip' => '#ec4899'
    ];
    $gradeColor = $gradeColors[$customer['grade'] ?? 'silver'] ?? '#94a3b8';

    ob_start();
    ?>
    <div class="customer-info-section" style="margin-top: 40px;">
        <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h2 style="color: #1f2937; margin-bottom: 10px;"><i class="fas fa-user-circle"></i> 내 정보</h2>
                <p style="color: #6b7280; margin: 0;">회원 정보를 확인하실 수 있습니다.</p>
            </div>
            <button id="toggleCustomerInfo" onclick="toggleCustomerInfoSection()" style="background: #3b82f6; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 500; transition: background 0.3s;">
                <i class="fas fa-chevron-up" id="toggleIcon"></i> <span id="toggleText">접기</span>
            </button>
        </div>

        <div id="customerInfoContent" class="info-container" style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div class="info-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                <div class="info-card" style="border: 1px solid #e5e7eb; padding: 20px; border-radius: 6px; background: #fafafa;">
                    <h3 style="color: #374151; margin-bottom: 15px; font-size: 1rem; border-bottom: 2px solid #000000; padding-bottom: 8px;">내 정보</h3>
                    <div class="info-row" style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e5e7eb;">
                        <span style="color: #6b7280; font-weight: 500;">이름</span>
                        <span style="color: #1f2937;"><?= htmlspecialchars($customer['name']) ?></span>
                    </div>
                    <div class="info-row" style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e5e7eb;">
                        <span style="color: #6b7280; font-weight: 500;">등급</span>
                        <span style="background: <?= $gradeColor ?>; color: white; padding: 4px 12px; border-radius: 12px; font-size: 13px; font-weight: 600;">
                            <?= strtoupper($customer['grade'] ?? 'SILVER') ?>
                        </span>
                    </div>
                    <div class="info-row" style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e5e7eb;">
                        <span style="color: #6b7280; font-weight: 500;">이메일</span>
                        <span style="color: #1f2937;"><?= htmlspecialchars($customer['email'] ?? '-') ?></span>
                    </div>
                    <div class="info-row" style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e5e7eb;">
                        <span style="color: #6b7280; font-weight: 500;">전화번호</span>
                        <span style="color: #1f2937;"><?= htmlspecialchars($customer['phone'] ?? '-') ?></span>
                    </div>
                    <div class="info-row" style="display: flex; justify-content: space-between; padding: 10px 0;">
                        <span style="color: #6b7280; font-weight: 500;">국적</span>
                        <span style="color: #1f2937;"><?= htmlspecialchars($customer['nationality'] ?? '-') ?></span>
                    </div>
                </div>

                <?php if ($customer['customer_photo'] || $customer['passport_photo']): ?>
                <div class="info-card" style="border: 1px solid #e5e7eb; padding: 20px; border-radius: 6px; background: #fafafa;">
                    <h3 style="color: #374151; margin-bottom: 15px; font-size: 1rem; border-bottom: 2px solid #000000; padding-bottom: 8px;">사진</h3>

                    <div style="display: flex; gap: 15px; flex-wrap: wrap; justify-content: center;">
                        <?php if ($customer['customer_photo']): ?>
                        <div style="flex: 1; min-width: 200px; text-align: center;">
                            <p style="color: #6b7280; font-size: 13px; margin-bottom: 8px;">프로필 사진</p>
                            <img src="../<?= htmlspecialchars($customer['customer_photo']) ?>" alt="고객 사진"
                                 style="max-width: 100%; max-height: 300px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                        <?php endif; ?>

                        <?php if ($customer['passport_photo']): ?>
                        <div style="flex: 1; min-width: 200px; text-align: center;">
                            <p style="color: #6b7280; font-size: 13px; margin-bottom: 8px;">여권 사진</p>
                            <img src="../<?= htmlspecialchars($customer['passport_photo']) ?>" alt="여권 사진"
                                 style="max-width: 100%; max-height: 300px; border: 1px solid #ddd; border-radius: 4px;">
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div style="margin-top: 25px; text-align: center;">
                <a href="../login/edit.php" class="btn" style="background: #2563eb; color: white; padding: 12px 30px; border-radius: 6px; text-decoration: none; display: inline-block; font-weight: 500;">
                    <i class="fas fa-edit"></i> 정보 수정
                </a>
            </div>
        </div>

     

    <style>
        /* 모바일 반응형 - 1열로 표시 */
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr !important;
            }
        }

        #toggleCustomerInfo:hover {
            background: #2563eb !important;
        }

        #customerInfoContent {
            transition: all 0.3s ease;
            overflow: hidden;
        }

        #customerInfoContent.collapsed {
            max-height: 0 !important;
            padding: 0 30px !important;
            opacity: 0;
        }
    </style>

    <script>
        function toggleCustomerInfoSection() {
            const content = document.getElementById('customerInfoContent');
            const icon = document.getElementById('toggleIcon');
            const text = document.getElementById('toggleText');

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
