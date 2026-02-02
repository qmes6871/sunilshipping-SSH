<?php
/**
 * Reviews Display Module for Main Page
 */

/**
 * Display customer reviews section
 *
 * @param int $limit Number of reviews to display (default: 6)
 * @return string HTML output
 */
function displayReviewsSection($limit = 6) {
    // Database connection
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
        error_log('Reviews module DB connection failed: ' . $e->getMessage());
        return '';
    }

    // Get approved reviews
    $sql = "SELECT * FROM reviews
            WHERE status = 'approved'
            ORDER BY created_at DESC
            LIMIT :limit";

    try {
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        $reviews = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Failed to fetch reviews: ' . $e->getMessage());
        return '';
    }

    // Get average rating
    $avg_sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_count
                FROM reviews WHERE status = 'approved'";
    $avg_stmt = $conn->query($avg_sql);
    $stats = $avg_stmt->fetch();
    $avg_rating = round($stats['avg_rating'], 1);
    $total_reviews = $stats['total_count'];

    // Generate HTML
    ob_start();
    ?>

    <!-- Customer Reviews Section -->
    <section class="reviews-section">
        <div class="reviews-container">
            <!-- Section Header -->
            <div class="section-header">
    <h2>Customer Reviews</h2>
    <p>What our customers say about SUNIL SHIPPING</p>
</div>

            <?php if (empty($reviews)): ?>
                <!-- No Reviews -->
                <div style="text-align: center; padding: 4rem 2rem; background: white; border-radius: 12px;">
                    <i class="fas fa-comments" style="font-size: 4rem; color: #d1d5db; margin-bottom: 1rem;"></i>
                    <h3 style="color: #6b7280; margin-bottom: 0.5rem;">No reviews yet</h3>
                    <p style="color: #9ca3af;">Be the first to share your experience!</p>
                </div>
            <?php else: ?>

                <!-- Reviews Grid -->
          <div class="reviews-wrapper">
        <div class="reviews-grid">
      <?php 
        $reviewsDouble = array_merge($reviews, $reviews);
        foreach ($reviewsDouble as $review): 
        ?>
            <div class="review-card"
                 onclick="window.location.href='review/view.php?id=<?= $review['review_id'] ?>'"
                 onmouseover="this.style.borderColor='#505050'; this.style.boxShadow='0 10px 20px rgba(0,0,0,0.1)'"
                 onmouseout="this.style.borderColor='#C6C6C6'; this.style.boxShadow='2px 2px 10px 0 rgba(0, 0, 0, 0.1)'">

            <!-- Review Header - 서비스 타입만 -->
            <div class="review-service-type">
    <span class="service-badge">
        <?php
        $serviceTypes = [
            'shipping' => 'SHIPPING',
            'customs' => 'CUSTOMS',
            'warehouse' => 'WAREHOUSE',
            'consulting' => 'CONSULTING',
            'other' => 'OTHER'
        ];
        echo $serviceTypes[$review['service_type']] ?? 'OTHER';
        ?>
    </span>
</div>

            <!-- Review Title -->
            <h3 style="font-size: 20px; font-weight: 500; color: #121212; margin-bottom: 20px; line-height: 1.4;">
                <?= htmlspecialchars(mb_substr($review['title'], 0, 60)) ?><?= mb_strlen($review['title']) > 60 ? '...' : '' ?>
            </h3>

            <!-- Review Content -->
            <p style="color: #505050; font-weight: 400; font-size: 16px; line-height: 1.7; margin-bottom: 1.5rem; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                <?= htmlspecialchars($review['content']) ?>
            </p>

            <!-- Review Footer -->
            <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 1rem; border-top: 1px solid #c6c6c6;">
                <div>
                    <div style="font-weight: 500; color: #121212; font-size: 18px;">
                        <?= htmlspecialchars($review['customer_name']) ?>
                    </div>
                    <div style="color: #505050; font-size: 16px; font-weight: 400;">
                        <?= date('Y-m-d', strtotime($review['created_at'])) ?>
                    </div>
                </div>
                <div style="display: flex; gap: 0.3rem;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star" style="color: <?= $i <= $review['rating'] ? '#fbbf24' : '#d1d5db' ?>; font-size: 1rem;"></i>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
  </div>

                <!-- View All Button -->
                     <div class="view-all-wrap">
    <a href="review/index.php" class="view-all-btn">
        View All Reviews
        <i class="fas fa-arrow-right"></i>
    </a>
</div>
            <?php endif; ?>
        </div>
    </section>

<style>
.reviews-section {
    background: #f9fafb; 
    padding: 100px 0 200px 0;
}

.reviews-wrapper {
    overflow: hidden;
    margin-bottom: 3rem;
}

.reviews-grid {
    display: flex;
    gap: 2rem;
        width: max-content;
    padding-bottom: 1rem;
    animation: scroll 30s linear infinite;
}

.reviews-grid:hover {
    animation-play-state: paused;
}

.review-card {
    background: white;
    max-width: 600px;
    flex-shrink: 0;
    padding: 24px 44px;
    border-radius: 10px;
    box-shadow: 2px 2px 10px 0 rgba(0, 0, 0, 0.1);
    transition: all 0.3s;
    cursor: pointer;
    border: 1px solid #C6C6C6;
}

.section-header {
    text-align: center;
    margin-bottom: 140px;
}

.section-header h2 {
    font-size: 50px;
    font-weight: 600;
    color: #111;
    margin-bottom: 40px;
}

.section-header p {
    font-size: 20px;
    color: #505050;
    font-weight: 400;
}

.view-all-wrap {
    text-align: center;
}

.view-all-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 7px 40px;
    background: #fff;
    color: #505050;
    border: 1px solid #505050;
    text-decoration: none;
    border-radius: 50px;
    font-weight: 600;
    font-size: 18px;
    transition: all 0.3s;
}

.view-all-btn:hover {
    background-color: #f9f9f9;
    color: #505050;
}

.review-service-type {
    text-align: right;
    margin-bottom: 1rem;
}

.service-badge {
    display: inline-block;
    padding: 6px 36px;
    background: #505050;
    color: #fff;
    border-radius: 30px;
    font-size: 16px;
    font-weight: 400;
}

@keyframes scroll {
    0% {
        transform: translateX(0);
    }
    100% {
        transform: translateX(-50%);
    }
}

/* 모바일만: 스와이프 추가 */
@media (max-width: 768px) {
    .reviews-section {
        padding: 100px 0;
    }
    
    .reviews-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        cursor: grab;
    }
    
    .reviews-wrapper::-webkit-scrollbar {
        display: none;
    }
    
    .reviews-wrapper:active {
        cursor: grabbing;
    }
    
    .review-card {
        padding: 20px 24px;
        max-width: 300px;
    }
    
    .section-header {
        margin-bottom: 60px;
        padding: 0 20px;
    }
    
    .section-header h2 {
        font-size: 30px;
        text-align: left;
    }
    
    .section-header p {
        font-size: 15px;
        text-align: left;
    }
    
    .view-all-btn {
        font-size: 15px;
        width: 200px;
        height: 40px;
        justify-content: center;
        white-space: nowrap;
    }
    
    .service-badge {
        font-size: 15px;
        padding: 5px 24px;
    }
}
</style>
    <?php
    return ob_get_clean();
}

/**
 * Get reviews statistics
 *
 * @return array Statistics array with avg_rating, total_count, etc.
 */
function getReviewsStats() {
    try {
        $conn = new PDO(
            "mysql:host=localhost;dbname=sunilshipping;charset=utf8mb4",
            "sunilshipping",
            "sunil123!",
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $sql = "SELECT
                    AVG(rating) as avg_rating,
                    COUNT(*) as total_count,
                    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
                FROM reviews
                WHERE status = 'approved'";

        $stmt = $conn->query($sql);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log('Failed to get reviews stats: ' . $e->getMessage());
        return [
            'avg_rating' => 0,
            'total_count' => 0,
            'five_star' => 0,
            'four_star' => 0,
            'three_star' => 0,
            'two_star' => 0,
            'one_star' => 0
        ];
    }
}
?>
