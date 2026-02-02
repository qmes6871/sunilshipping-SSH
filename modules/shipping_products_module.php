<?php
/**
 * 운항 상품 모듈 - 깔끔한 카드 스타일 (가격 부분 마스킹 버전)
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
        $decimalPart = isset($parts[1]) ? $parts[1] : '00';
        
        // 콤마를 기준으로 분리
        $segments = explode(',', $integerPart);
        $maskedSegments = [];
        
        foreach ($segments as $segment) {
            $maskedSegments[] = str_repeat('*', strlen($segment));
        }
        
        return '$' . implode(',', $maskedSegments) . '.**';
    }

    // 노선별 국기 이미지 매핑 함수
function getRouteFlag($departure, $arrival) {
    $departure = strtoupper(trim($departure));
    $arrival = strtoupper(trim($arrival));
    
    // 미국 항구들
    $usaPorts = ['LA', 'LOS ANGELES', 'NEW YORK', 'SEATTLE'];
    // 우즈베키스탄 항구들
    $uzbekPorts = ['TASHKENT', '타슈켄트'];
    
    // 미국 노선
    foreach ($usaPorts as $port) {
        if (stripos($arrival, $port) !== false || stripos($departure, $port) !== false) {
            return 'images/korea-usa.png';
        }
    }
    
    // 우즈베키스탄 노선
    foreach ($uzbekPorts as $port) {
        if (stripos($arrival, $port) !== false || stripos($departure, $port) !== false) {
            return 'images/korea-uzbekistan.png';
        }
    }
    
    // 기본값 (미국)
    return 'images/korea-usa.png';
}
    
    ob_start();
    ?>
    <section id="products" style="padding: 100px 0 200px 0; background: #f5f5f5;">
        <div class="container">
            <div style="text-align: center; margin-bottom: 3rem;">
                <h2 style="color: #111; font-size: 50px; font-weight: 600; margin-bottom: 40px; text-align: left; ">LOGISTIC</h2>
                <p style="color: #505050; font-size: 20px; font-weight: 400; text-align: left;">We provide professional container <br /> shipping services across diverse routes.</p>
            </div>

            <?php if (!$conn): ?>
                <div style="text-align: center; padding: 2rem; background: #fee2e2; border-radius: 8px; color: #991b1b; border: 1px solid #fecaca;">
                    <h3 style="margin-bottom: 0.5rem;">데이터베이스 연결 오류</h3>
                    <p style="margin: 0;">현재 데이터베이스에 연결할 수 없습니다.</p>
                </div>
            <?php else: ?>
                <?php
                try {
                    $stmt = $conn->prepare("
                        SELECT id, product_name, departure_port, arrival_port, vessel_name,
                               transit_time, price, weekly_schedule, description,
                               vessel_type, operator_company, available_slots, image_url, departure_time
                        FROM shipping_products
                        WHERE status = 'active'
                        ORDER BY created_at DESC
                    ");
                    $stmt->execute();
                    $products = $stmt->fetchAll();
                } catch (Exception $e) {
                    $products = [];
                }
                ?>
                
                <div class="shipping-products-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem;">
                    <?php if (empty($products)): ?>
                        <div style="text-align: center; padding: 3rem; color: #6b7280; grid-column: 1 / -1; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <i class="fas fa-ship" style="font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.3; color: #9ca3af;"></i>
                            <h3 style="margin-bottom: 0.5rem; color: #374151;">운항 상품이 없습니다</h3>
                            <p style="margin: 0;">현재 등록된 운항 상품이 없습니다.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                        <div class="shipping-product-card" style="background: white; border-radius: 5px; overflow: hidden;  transition: transform 0.3s ease, box-shadow 0.3s ease; border: 1px solid #c6c6c6;">
                            <!-- 썸네일 이미지 -->
                            <?php if (!empty($product['image_url'])): ?>
<div style="width: 100%; height: 80px; display: flex; align-items: center; justify-content: center; background: #ffffff;">
    <img src="<?= getRouteFlag($product['departure_port'] ?? '', $product['arrival_port'] ?? '') ?>"
         alt="Route"
         style="height: 50px; width: auto;">
</div>
                            <?php endif; ?>

                            <!-- 본문 -->
                            <div style="padding: 1rem;">
                                <!-- 항로 정보 -->
                                <div style="padding: 0.6rem; margin-bottom: 0.6rem; text-align: center; border-bottom: 1px solid #e5e7eb;">
                                    <div style="display: flex; align-items: center; justify-content: space-around; gap: 0.8rem; font-weight: 600; color: #111827; font-size: 0.95rem;">
                                        <span><?= htmlspecialchars($product['departure_port'] ?? '') ?></span>
                                        <i class="fas fa-arrow-right" style="color: #6b7280; font-size: 0.75rem;"></i>
                                        <span><?= htmlspecialchars($product['arrival_port'] ?? '') ?></span>
                                    </div>
                                </div>

                                <!-- 출발시간 -->
                                <?php if (!empty($product['departure_time'])): ?>
                                <div style="padding: 0.5rem; text-align: left; margin-bottom: 0.5rem; border-bottom: 1px solid #e5e7eb;">
                                    <div style="font-size: 18px; color: #111; font-weight: 400; display: flex; justify-content: space-between; align-items: center;">
                                        <span style="color: #505050;">Departure Time:</span>
                                        <?php
                                        $departure = $product['departure_time'];
                                        if (strtotime($departure)) {
                                            echo date('Y-m-d H:i', strtotime($departure));
                                        } else {
                                            echo htmlspecialchars($departure);
                                        }
                                        ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- 운송시간 -->
                                <div style="padding: 0.5rem; text-align: left; margin-bottom: 0.5rem; border-bottom: 1px solid #e5e7eb;">
                                    <div style="font-size: 0.9rem; color: #374151;">
                                        <span style="font-size: 0.85rem; color: #6b7280;">Shipping Time:</span>
                                        <span style="font-weight: 600;"><?= htmlspecialchars($product['transit_time'] ?? '0') ?>일</span>
                                    </div>
                                </div>

                                <!-- 가격 (부분 마스킹 처리) -->
                                <div style="padding: 0.6rem; text-align: left; margin-bottom: 0.6rem; border-bottom: 1px solid #e5e7eb;">
                                    <div style="font-size: 1.3rem; font-weight: 700; color: #111827;">
                                        <span style="font-size: 0.85rem; color: #6b7280; font-weight: 400;"><?= $is_logged_in ? '운송료:' : '가격 문의:' ?></span>
                                        <?php if ($is_logged_in): ?>
                                            <?php
                                            $price = $product['price'] ?? 0;
                                            echo is_numeric($price) ? '$' . number_format($price, 2) : htmlspecialchars($price);
                                            ?>
                                        <?php else: ?>
                                            <?= maskPrice($product['price'] ?? 0) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- 예약남은 시간 -->
                                <?php if (!empty($product['departure_time'])): ?>
                                <div style="padding: 0.5rem; text-align: center; margin-bottom: 0.8rem;">
                                    <div style="font-size: 0.9rem; font-weight: 600; color: #374151;">
                                        <span style="font-size: 0.85rem; color: #6b7280; font-weight: 400;">남은시간:</span>
                                        <span class="countdown-timer" data-departure="<?= date('c', strtotime($product['departure_time'])) ?>" data-product-id="<?= $product['id'] ?>">
                                            계산 중...
                                        </span>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- 예약 버튼 -->
                                <?php if ($is_logged_in): ?>
                                    <?php if (($product['available_slots'] ?? 0) > 0): ?>
                                        <button onclick="reserveShipping(<?= $product['id'] ?? 0 ?>)" 
                                                style="width: 100%; background: #2563eb; color: white; border: none; padding: 0.8rem; border-radius: 6px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem; font-size: 0.9rem; transition: background-color 0.3s ease;">
                                            <i class="fas fa-calendar-check" style="font-size: 0.8rem;"></i>
                                            INQUIRY
                                        </button>
                                    <?php else: ?>
                                        <button disabled 
                                                style="width: 100%; background: #9ca3af; color: white; border: none; padding: 0.8rem; border-radius: 6px; font-weight: 600; cursor: not-allowed; display: flex; align-items: center; justify-content: center; gap: 0.5rem; font-size: 0.9rem;">
                                            <i class="fas fa-times-circle" style="font-size: 0.8rem;"></i>
                                            INQUIRY
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button onclick="location.href='login/login.php'" 
                                            style="width: 100%; background: #2563eb; color: white; border: none; padding: 0.8rem; border-radius: 6px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem; font-size: 0.9rem; transition: background-color 0.3s ease;">
                                        <i class="fas fa-lock" style="font-size: 0.8rem;"></i>
                                        Login to reserve
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php if (!empty($products)): ?>
                    <div class="shipping-products-actions" style="margin-top: 2.5rem; text-align: center;">
                        <button id="shipping-products-load-more"
                                type="button"
                                style="display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.85rem 2.75rem; background: #1f2937; color: #ffffff; border: none; border-radius: 9999px; font-size: 0.95rem; font-weight: 600; letter-spacing: 0.05em; cursor: pointer; transition: background 0.2s;">
                            MORE
                        </button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <style>
        #products .shipping-product-card.is-hidden {
            display: none !important;
        }

        #products .shipping-products-actions .is-hidden {
            display: none !important;
        }

        #shipping-products-load-more:hover {
            background: #111827 !important;
        }

        /* 카드 호버 효과 */
        #products [style*="background: white"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
        }
        
        /* 버튼 호버 효과 */
        #products button[style*="background: #2563eb"]:hover {
            background: #1d4ed8 !important;
        }
        
        /* 800px 이하에서 container 최대 너비 제한 */
        @media (max-width: 800px) {
            #products .container {
                max-width: 370px !important;
                margin: 0 auto !important;
                padding: 0 15px !important;
            }
        }
        
        /* 기존 반응형 */
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
                font-size: 1.5rem !important;
            }
            
            #products [style*="padding: 1.5rem"] {
                padding: 1rem !important;
            }
        }
        
        @media (max-width: 400px) {
            #products .container {
                max-width: 350px !important;
                padding: 0 10px !important;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var section = document.getElementById('products');
            if (!section) {
                return;
            }

            var grid = section.querySelector('.shipping-products-grid');
            var cards = grid ? Array.prototype.slice.call(grid.querySelectorAll('.shipping-product-card')) : [];
            var moreButton = section.querySelector('#shipping-products-load-more');

            if (!grid || cards.length === 0 || !moreButton) {
                if (moreButton) {
                    moreButton.classList.add('is-hidden');
                    moreButton.setAttribute('aria-hidden', 'true');
                }
                return;
            }

            var minCardWidth = 320;

            function calculateBatchSize() {
                if (window.innerWidth < 640) {
                    return 1;
                }
                var gridWidth = grid.clientWidth || section.clientWidth || window.innerWidth;
                return Math.max(1, Math.floor(gridWidth / minCardWidth));
            }

            var itemsPerBatch = calculateBatchSize();
            var displayedCount = 0;

            function updateVisibility(targetCount) {
                cards.forEach(function (card, index) {
                    if (index < targetCount) {
                        card.classList.remove('is-hidden');
                    } else {
                        card.classList.add('is-hidden');
                    }
                });
            }

            function updateButton() {
                if (!moreButton) {
                    return;
                }
                if (displayedCount >= cards.length) {
                    moreButton.classList.add('is-hidden');
                    moreButton.setAttribute('aria-hidden', 'true');
                    moreButton.setAttribute('disabled', 'disabled');
                } else {
                    moreButton.classList.remove('is-hidden');
                    moreButton.removeAttribute('aria-hidden');
                    moreButton.removeAttribute('disabled');
                }
            }

            function showNextBatch() {
                displayedCount = Math.min(displayedCount + itemsPerBatch, cards.length);
                updateVisibility(displayedCount);
                updateButton();
            }

            function initializeVisibility() {
                cards.forEach(function (card) {
                    card.classList.add('is-hidden');
                });
                displayedCount = 0;
                showNextBatch();
            }

            moreButton.addEventListener('click', function (event) {
                event.preventDefault();
                showNextBatch();
            });

            window.addEventListener('resize', function () {
                var previousBatchSize = itemsPerBatch;
                var newBatchSize = calculateBatchSize();
                if (newBatchSize === previousBatchSize) {
                    return;
                }
                itemsPerBatch = newBatchSize;

                if (displayedCount < cards.length) {
                    var batchesShown = Math.max(1, Math.ceil(displayedCount / previousBatchSize));
                    displayedCount = Math.min(batchesShown * itemsPerBatch, cards.length);
                }

                updateVisibility(displayedCount);
                updateButton();
            });

            initializeVisibility();
        });
    </script>

    <script>
        function reserveShipping(productId) {
            // 확인 대화상자 없이 바로 reservation.php 페이지로 이동
            window.location.href = 'reserve/reservation.php?product_id=' + productId;
        }
    </script>

    <script>
        // 카운트다운 타이머
        document.addEventListener('DOMContentLoaded', function() {
            const countdownTimers = document.querySelectorAll('.countdown-timer');

            function updateCountdown(element) {
                const departureTime = new Date(element.getAttribute('data-departure'));
                const now = new Date();
                const diff = departureTime - now;

                if (diff <= 0) {
                    element.textContent = '출발 완료';
                    element.style.color = '#6b7280';
                    return;
                }

                const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);

                let timeString = '';
                if (days > 0) {
                    timeString += days + '일 ';
                }
                timeString += String(hours).padStart(2, '0') + ':'
                           + String(minutes).padStart(2, '0') + ':'
                           + String(seconds).padStart(2, '0');

                element.textContent = timeString;
            }

            // 초기 업데이트
            countdownTimers.forEach(timer => {
                updateCountdown(timer);
            });

            // 1초마다 업데이트
            setInterval(function() {
                countdownTimers.forEach(timer => {
                    updateCountdown(timer);
                });
            }, 1000);
        });
    </script>

    <?php
    return ob_get_clean();
}
?>
