<?php
/**
 * 운항 상품 모듈 - 심플한 디자인
 */

function displayShippingProductsSection($is_logged_in = false) {
    // 독립적인 DB 연결
    try {
        $conn = new PDO(
            "mysql:host=localhost;dbname=sunilshipping;charset=utf8mb4",
            "sunilshipping",
            "sunil123!",
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    } catch (PDOException $e) {
        $conn = null;
        error_log('Shipping module DB connection failed: ' . $e->getMessage());
    }
    
    // 가격 마스킹 함수
    function maskPrice($price) {
        if (!is_numeric($price) || $price <= 0) {
            return '$***';
        }
        
        $formattedPrice = number_format($price, 2);
        $parts = explode('.', $formattedPrice);
        $integerPart = $parts[0];
        
        $segments = explode(',', $integerPart);
        $maskedSegments = [];
        
        foreach ($segments as $segment) {
            $maskedSegments[] = str_repeat('*', strlen($segment));
        }
        
        return '$' . implode(',', $maskedSegments) . '.**';
    }
    
    ob_start();
    ?>
    <section id="products" style="padding: 20px; background: #ffffff;">
        <div class="container">
            <div style="text-align: center; margin-bottom: 2rem;">
                <h2 style="color: #333; font-size: 1.5rem; font-weight: 600; margin-bottom: 0.5rem;">LOGISTIC PRODUCT</h2>
                <p style="color: #666; font-size: 0.9rem;">Container Transport Service</p>
            </div>

            <?php if (!$conn): ?>
                <div style="text-align: center; padding: 2rem; background: #f5f5f5; border-radius: 4px; color: #666; border: 1px solid #ddd;">
                    <h3 style="margin-bottom: 0.5rem;">데이터베이스 연결 오류</h3>
                    <p style="margin: 0;">현재 데이터베이스에 연결할 수 없습니다.</p>
                </div>
            <?php else: ?>
                <?php
                try {
                    // Determine user grade (default silver) and nationality
                    $user_grade = 'silver';
                    $user_nationality = null;
                    if ($is_logged_in) {
                        try {
                            if (session_status() === PHP_SESSION_NONE) { @session_start(); }
                            $username = $_SESSION['username'] ?? null;

                            if ($username) {
                                $stmtGrade = $conn->prepare("SELECT grade, nationality FROM customer_management WHERE username = ? LIMIT 1");
                                $stmtGrade->execute([$username]);
                                $userData = $stmtGrade->fetch();
                                if ($userData) {
                                    $user_grade = strtolower($userData['grade'] ?? 'silver');
                                    $user_nationality = $userData['nationality'] ?? null;
                                }
                            }
                        } catch (Exception $ign2) {}
                    }

                    // nationality 조건 추가
                    if ($user_nationality) {
                        $stmt = $conn->prepare("
                            SELECT id, product_name, departure_port, arrival_port, vessel_name,
                                   transit_time, price, price_vvip, price_vip, price_gold, price_silver, weekly_schedule, description,
                                   vessel_type, operator_company, available_slots, nationality
                            FROM shipping_products
                            WHERE status = 'active' AND nationality = ?
                            ORDER BY created_at DESC
                            LIMIT 6
                        ");
                        $stmt->execute([$user_nationality]);
                    } else {
                        $stmt = $conn->prepare("
                            SELECT id, product_name, departure_port, arrival_port, vessel_name,
                                   transit_time, price, price_vvip, price_vip, price_gold, price_silver, weekly_schedule, description,
                                   vessel_type, operator_company, available_slots, nationality
                            FROM shipping_products
                            WHERE status = 'active'
                            ORDER BY created_at DESC
                            LIMIT 6
                        ");
                        $stmt->execute();
                    }
                    $products = $stmt->fetchAll();
                } catch (Exception $e) {
                    $products = [];
                }
                ?>
                
                <div id="productsGrid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
                    <?php if (empty($products)): ?>
                        <div style="text-align: center; padding: 2rem; color: #666; grid-column: 1 / -1; background: #f9f9f9; border-radius: 4px;">
                            <i class="fas fa-ship" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.3; color: #999;"></i>
                            <h3 style="margin-bottom: 0.5rem; color: #333;">운항 상품이 없습니다</h3>
                            <p style="margin: 0;">현재 등록된 운항 상품이 없습니다.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($products as $index => $product): ?>
                        <div class="product-card" data-index="<?= $index ?>" style="background: #fff; border: 1px solid #ddd; border-radius: 4px; overflow: hidden;">
                            <!-- 헤더 -->
                            <div style="background: #f5f5f5; padding: 1rem; border-bottom: 1px solid #ddd;">
                                <h3 style="margin: 0; font-size: 1rem; font-weight: 600; color: #333; margin-bottom: 0.3rem;">
                                    <?= htmlspecialchars($product['product_name'] ?? '') ?>
                                </h3>
                                <div style="font-size: 0.85rem; color: #666;">
                                    <?= htmlspecialchars($product['vessel_name'] ?? '') ?>
                                </div>
                            </div>

                            <!-- 본문 -->
                            <div style="padding: 1rem;">
                                <!-- 항로 -->
                                <div style="padding: 0.8rem; margin-bottom: 0.8rem; background: #f9f9f9; border-radius: 4px; text-align: center;">
                                    <div style="font-size: 0.85rem; font-weight: 500; color: #333;">
                                        <?= htmlspecialchars($product['departure_port'] ?? '') ?>
                                        <i class="fas fa-arrow-right" style="color: #666; margin: 0 0.5rem; font-size: 0.7rem;"></i>
                                        <?= htmlspecialchars($product['arrival_port'] ?? '') ?>
                                    </div>
                                </div>

                                <!-- 운송시간 -->
                                <div style="padding: 0.8rem; margin-bottom: 0.8rem; text-align: center; background: #fafafa; border-radius: 4px;">
                                    <div style="font-size: 0.75rem; color: #666; margin-bottom: 0.2rem;">운송시간</div>
                                    <div style="font-size: 1.1rem; font-weight: 600; color: #333;">
                                        <?= htmlspecialchars($product['transit_time'] ?? '0') ?>일
                                    </div>
                                </div>

                                <!-- 가격 -->
                                <div style="padding: 0.8rem; margin-bottom: 1rem; text-align: center; background: #fafafa; border-radius: 4px;">
                                    <div style="font-size: 1.3rem; font-weight: 600; color: #333; margin-bottom: 0.2rem;">
                                        <?php if ($is_logged_in): ?>
                                            <?php
                                            // Pick price by user's grade; fallback to base price
                                            $displayPrice = $product['price'] ?? 0;
                                            switch ($user_grade ?? 'silver') {
                                                case 'vvip': if (!empty($product['price_vvip'])) $displayPrice = $product['price_vvip']; break;
                                                case 'vip': if (!empty($product['price_vip'])) $displayPrice = $product['price_vip']; break;
                                                case 'gold': if (!empty($product['price_gold'])) $displayPrice = $product['price_gold']; break;
                                                case 'silver': if (!empty($product['price_silver'])) $displayPrice = $product['price_silver']; break;
                                            }
                                            echo is_numeric($displayPrice) ? '$' . number_format($displayPrice, 2) : htmlspecialchars($displayPrice);
                                            ?>
                                        <?php else: ?>
                                            <?= maskPrice($product['price'] ?? 0) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: #666;">
                                        <?= $is_logged_in ? '운송료' : '가격 문의' ?>
                                    </div>
                                </div>

                                <!-- 예약 버튼 -->
                                <?php if ($is_logged_in): ?>
                                    <?php if (($product['available_slots'] ?? 0) > 0): ?>
                                        <a href="../reserve/reservation.php?product_id=<?= $product['id'] ?? 0 ?>"
                                           style="width: 100%; background: #333; color: white; padding: 0.7rem; border-radius: 4px; font-weight: 500; display: block; text-align: center; font-size: 0.9rem; text-decoration: none;">
                                            INQUIRY
                                        </a>
                                    <?php else: ?>
                                        <button disabled
                                                style="width: 100%; background: #ccc; color: white; border: none; padding: 0.7rem; border-radius: 4px; font-weight: 500; cursor: not-allowed; font-size: 0.9rem;">
                                            Booking Closed
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="../login/login.php"
                                       style="width: 100%; background: #333; color: white; padding: 0.7rem; border-radius: 4px; font-weight: 500; display: block; text-align: center; font-size: 0.9rem; text-decoration: none;">
                                        로그인 후 예약
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- MORE 버튼 (모바일에서만 표시) -->
                <?php if (!empty($products) && count($products) > 3): ?>
                <div id="moreButtonContainer" style="text-align: center; margin-top: 1.5rem; display: none;">
                    <button id="moreButton" style="background: #333; color: white; padding: 0.8rem 2rem; border: none; border-radius: 4px; font-weight: 500; font-size: 0.95rem; cursor: pointer;">
                        MORE
                    </button>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <style>
        /* 카드 호버 효과 */
        #products [style*="border: 1px solid #ddd"]:hover {
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        /* 버튼 호버 효과 */
        #products a[style*="background: #333"]:hover {
            background: #555 !important;
        }

        #products #moreButton:hover {
            background: #555 !important;
        }

        /* 반응형 */
        @media (max-width: 768px) {
            #products {
                padding: 15px !important;
            }

            #products .container {
                max-width: 370px !important;
                margin: 0 auto !important;
                padding: 0 15px !important;
            }

            #products [style*="grid-template-columns"] {
                grid-template-columns: 1fr !important;
                gap: 1rem !important;
            }

            #products h2 {
                font-size: 1.3rem !important;
            }

            /* 모바일에서 3개 이상의 상품이 있을 때 숨기기 */
            #products .product-card {
                display: block;
            }

            #products .product-card.hidden-mobile {
                display: none;
            }

            /* 모바일에서 MORE 버튼 표시 */
            #products #moreButtonContainer {
                display: block !important;
            }
        }

        /* 데스크탑에서는 모든 카드 표시 */
        @media (min-width: 769px) {
            #products .product-card {
                display: block !important;
            }

            #products #moreButtonContainer {
                display: none !important;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const productCards = document.querySelectorAll('#products .product-card');
            const moreButton = document.getElementById('moreButton');
            const moreButtonContainer = document.getElementById('moreButtonContainer');

            function handleMobileView() {
                // 모바일인지 확인 (768px 이하)
                const isMobile = window.innerWidth <= 768;

                if (isMobile && productCards.length > 3) {
                    // 모바일: 처음 3개만 표시
                    productCards.forEach((card, index) => {
                        if (index >= 3) {
                            card.classList.add('hidden-mobile');
                        }
                    });

                    if (moreButtonContainer) {
                        moreButtonContainer.style.display = 'block';
                    }
                } else {
                    // 데스크탑: 모든 카드 표시
                    productCards.forEach(card => {
                        card.classList.remove('hidden-mobile');
                    });

                    if (moreButtonContainer) {
                        moreButtonContainer.style.display = 'none';
                    }
                }
            }

            // MORE 버튼 클릭 이벤트
            if (moreButton) {
                moreButton.addEventListener('click', function() {
                    // 숨겨진 카드 모두 표시
                    productCards.forEach(card => {
                        card.classList.remove('hidden-mobile');
                    });

                    // MORE 버튼 숨기기
                    if (moreButtonContainer) {
                        moreButtonContainer.style.display = 'none';
                    }
                });
            }

            // 초기 실행
            handleMobileView();

            // 화면 크기 변경 시 재실행
            window.addEventListener('resize', handleMobileView);
        });
    </script>
    
    <?php
    return ob_get_clean();
}
?>
