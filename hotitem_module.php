<?php
/**
* Hot Item module for the main page.
*
* Pulls active hot items and renders them in a reusable section.
*/

if (!function_exists('displayHotItemsSection')) {
    /**
    * Render the hot item showcase from the hot_items table.
    *
    * @param int|null $limit Optional number of items to display (null shows all)
    * @return string
    */
    function displayHotItemsSection($limit = null)
    {
        $items = [];
        
        $mysqli = @new mysqli('localhost', 'sunilshipping', 'sunil123!', 'sunilshipping');
        if ($mysqli->connect_errno) {
            error_log('Hot item module DB connection failed: ' . $mysqli->connect_error);
            $mysqli = null;
        } else {
            $mysqli->set_charset('utf8mb4');
        }
        
        if ($mysqli) {
            $sql = "SELECT id, title, description, image_path, category, original_price, sale_price, created_at
                    FROM hot_items
                    WHERE is_active = 1
                    ORDER BY created_at DESC";
            if ($limit !== null) {
                $sql .= " LIMIT ?";
            }
            
            $stmt = $mysqli->prepare($sql);
            if ($stmt) {
                if ($limit !== null) {
                    $safeLimit = (int)$limit;
                    $stmt->bind_param('i', $safeLimit);
                }
                if ($stmt->execute()) {
                    $result = $stmt->get_result();
                    if ($result) {
                        while ($row = $result->fetch_assoc()) {
                            $items[] = $row;
                        }
                        $result->free();
                    }
                }
                $stmt->close();
            }
            
            $mysqli->close();
        }
        
        ob_start();
        ?>
        <section id="hot-items" style="background: #ffffff; padding: 100px 0 200px 0;">
        <div class="hot-items-container" style="max-width: 1440px; margin: 0 auto; ;">
<div class="hot-items-top">
    <div class="hot-items-header">
        <h2>TRADE CAR</h2>
        <p>
            New items are uploaded every day.<br />
            We deliver popular items fast at fair prices.<br />
            Looking for something else? Contact us anytime.
        </p>
    </div>

</div>
            
            <?php if (empty($items)): ?>
                <div style="text-align: center; padding: 4rem 2rem; border: 1px solid #e5e7eb; border-radius: 8px; background: #f9fafb;">
                <h3 style="font-size: 1.5rem; font-weight: 600; color: #4b5563; margin-bottom: 0.75rem;">No Hot Items Available</h3>
                <p style="color: #6b7280; font-size: 1rem; margin: 0;">New items will be updated soon.</p>
                </div>
                <?php else: ?>
     <div class="hot-items-grid" style="padding: 0 20px;display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
                    <?php foreach ($items as $item): ?>
                        <?php
                        $originalPrice = isset($item['original_price']) ? (float)$item['original_price'] : 0;
                        $salePrice = isset($item['sale_price']) ? (float)$item['sale_price'] : 0;
                        $discount = 0;
                        
                        if ($originalPrice > 0 && $salePrice > 0 && $salePrice < $originalPrice) {
                            $discount = round((($originalPrice - $salePrice) / $originalPrice) * 100);
                        }
                        
                        $imageUrl = isset($item['image_path']) ? trim($item['image_path']) : '';
                        if ($imageUrl !== '' && strpos($imageUrl, 'http') !== 0 && strpos($imageUrl, '/') !== 0) {
                            $imageUrl = '/' . $imageUrl;
                        }
                        
                        // 오늘 등록된 상품인지 확인
                        $isToday = false;
                        if (!empty($item['created_at'])) {
                            $createdDate = date('Y-m-d', strtotime($item['created_at']));
                            $todayDate = date('Y-m-d');
                            $isToday = ($createdDate === $todayDate);
                        }
                        ?>
                        <article class="hot-item-card" style="background: #ffffff; border: 1px solid #C6C6C6; border-radius: 3px; overflow: hidden; transition: all 0.25s ease; display: flex; flex-direction: column;">
<div style="position: relative; width: 100%; padding-bottom: 100%; background: #f3f4f6; overflow: hidden;">
    <?php if ($imageUrl !== ''): ?>
        <img src="<?= htmlspecialchars($imageUrl) ?>"
             alt="<?= htmlspecialchars(isset($item['title']) ? $item['title'] : '') ?>"
             style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
    <?php endif; ?>
    
    <!-- 할인율 배지 -->
    <?php if ($discount > 0): ?>
    <div style="position: absolute; bottom: 0; right: 0; background-color: #2563eb; z-index: 2; padding: 10px 15px; color: #fff; font-size: 18px; font-weight: 600; border-radius: 20px 0 20px 0;">
    <?= $discount ?>%
</div>
    <?php endif; ?>
</div>
                                
<div style="padding: 10px; flex: 1; display: flex; flex-direction: column;">
    <h3 style="font-size: 18px; font-weight: 600; color: #111; margin-bottom: 0.75rem; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
        <?= htmlspecialchars(isset($item['title']) ? $item['title'] : '') ?>
    </h3>
    
<div style="margin-bottom: 1.5rem; text-align: right;">
    <span style="display: block; color: #c6c6c6; font-size: 18px; text-decoration: line-through; margin-bottom: 4px; font-weight: 400; height: 1.2em; <?= ($originalPrice > $salePrice && $salePrice > 0) ? '' : 'visibility: hidden;' ?>">
        $<?= ($originalPrice > $salePrice && $salePrice > 0) ? number_format($originalPrice) : '0' ?>
    </span>
    <span style="color: #111; font-size: 20px; font-weight: 500;">
        <?= $salePrice > 0 ? '$' . number_format($salePrice) : '가격 문의' ?>
    </span>
</div>
    
    <a href="tradecar/inquiry.php?item_id=<?= isset($item['id']) ? (int)$item['id'] : 0 ?>"
       style="margin-top: auto; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 7px 70px; background: #F9FAFB; color: #121212; text-decoration: none; border-radius: 30px; font-size: 15px; font-weight: 600; transition: background 0.2s;">
        INQUIRY
    </a>
</div>
                                                </article>
                                                <?php endforeach; ?>
                                                </div>
                                                <?php endif; ?>
                                                </div>


    <?php if (count($items) > 0): ?>
        <button id="hot-items-load-more" type="button">
            more
        </button>
    <?php endif; ?>
                                                </section>
                                                
                                                
                                                <style>
                                                    .hot-items-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    padding:0 20px;
    margin-bottom: 110px;
}

.hot-items-header {
    text-align: left;
}

.hot-items-header h2 {
    font-size: 32px;
    font-weight: 600;
    color: #111;
    margin-bottom: 40px;
}

.hot-items-header p {
    font-size: 16px;
    color: #505050;
    font-weight: 400;
}

#hot-items-load-more {
    display: flex;
    width: 150px;
    height: 40px;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 7px 20px;
    background: #fff;
    color: #505050;
    border: 1px solid #505050;
    border-radius: 9999px;
    font-size: 15px;
    font-weight: 600;
    letter-spacing: 0.05em;
    cursor: pointer;
    transition: background 0.2s;
    flex-shrink: 0;
    margin: 50px auto 0 auto;
}

#hot-items-load-more:hover {
    background: #f9f9f9 !important; 
}

/* 모바일 반응형 */
@media (max-width: 768px) {
    .hot-items-top {
        flex-direction: column;
        align-items: flex-start;
        gap: 1.5rem;
        margin-bottom: 60px;
    }

    .hot-items-header p {
        font-size: 14px;
    }
    
    .hot-items-header h2 {
        font-size: 20px;
    }
    
    #hot-items-load-more {
        width: 140px;
        font-size: 14px;
        height: 35px;
    }
}
                                                #hot-items .hot-item-card.is-hidden {
                                                    display: none !important;
                                                }
                                                
                                                #hot-items .hot-items-actions .is-hidden {
                                                    display: none !important;
                                                }
                                                
                                                #hot-items .hot-item-card:hover {
                                                    border-color: #d1d5db !important;
                                                    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.08);
                                                    transform: translateY(-4px);
                                                }
                                                
                                                @media (max-width: 768px) {
                                                    #hot-items {
                                                        padding: 50px 0 100px 0 !important;
                                                    }
                                                    
                                                    
                                                    #hot-items .hot-items-header h2 {
                                                        font-size: 20px !important;
                                                    }
                                                    
                                                    #hot-items .hot-items-grid {
                                                        gap: 1.5rem !important;
                                                        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)) !important;
                                                    }
                                                }
                                                </style>
                                                <script>
                                                document.addEventListener('DOMContentLoaded', function () {
                                                    var section = document.getElementById('hot-items');
                                                    if (!section) {
                                                        return;
                                                    }
                                                    
                                                    var grid = section.querySelector('.hot-items-grid');
                                                    var cards = grid ? Array.prototype.slice.call(grid.querySelectorAll('.hot-item-card')) : [];
                                                    var moreButton = section.querySelector('#hot-items-load-more');
                                                    
                                                    if (!grid || cards.length === 0 || !moreButton) {
                                                        if (moreButton) {
                                                            moreButton.classList.add('is-hidden');
                                                            moreButton.setAttribute('aria-hidden', 'true');
                                                        }
                                                        return;
                                                    }
                                                    
                                                 var minCardWidth = 280;

function calculateBatchSize() {
    if (window.innerWidth < 640) {
        return 2;
    }
    if (window.innerWidth < 1024) {
        return 3;
    }
    return 4; 
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
                                                    
                                                    // Normalize currency display: replace trailing 'WON' with '$' prefix
                                                    try {
                                                        var priceNodes = section.querySelectorAll('span[style*="font-weight: 700"][style*="font-size: 1.3rem"]');
                                                        priceNodes.forEach(function (el) {
                                                            var t = (el.textContent || '').trim();
                                                            var m = t.match(/^([0-9][0-9,]*)\s*WON$/i);
                                                            if (m) {
                                                                el.textContent = '$' + m[1];
                                                            }
                                                        });
                                                        var origNodes = section.querySelectorAll('span[style*="text-decoration: line-through"]');
                                                        origNodes.forEach(function (el) {
                                                            var t = (el.textContent || '').trim();
                                                            var m = t.match(/^([0-9][0-9,]*)\s*WON$/i);
                                                            if (m) {
                                                                el.textContent = '$' + m[1];
                                                            }
                                                        });
                                                    } catch (e) {
                                                        // ignore
                                                    }
                                                });
                                                </script>
                                                <?php
                                                return ob_get_clean();
                                            }
                                        }
                                        