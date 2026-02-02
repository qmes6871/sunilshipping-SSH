<?php
/**
 * Review Detail View Page
 */
session_start();

// Include database connection
require_once 'config.php';

// Check database connection
if ($conn === null) {
    die('Database connection failed. Please check config.php');
}

// Get review ID from URL
$review_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($review_id <= 0) {
    header('Location: index.php');
    exit;
}

// Update view count
$update_views_sql = "UPDATE reviews SET views = views + 1 WHERE review_id = :review_id";
$update_stmt = $conn->prepare($update_views_sql);
$update_stmt->execute([':review_id' => $review_id]);

// Get review details
$sql = "SELECT * FROM reviews WHERE review_id = :review_id AND status = 'approved'";
$stmt = $conn->prepare($sql);
$stmt->execute([':review_id' => $review_id]);
$review = $stmt->fetch();

// If review not found, redirect
if (!$review) {
    header('Location: index.php');
    exit;
}

// Check login status
$is_logged_in = isset($_SESSION['user_id']) && isset($_SESSION['username']);
$current_user_id = $_SESSION['username'] ?? '';

// Check if current user is the author
$is_author = $is_logged_in && ($current_user_id === $review['customer_id']);
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($review['title']) ?> - SUNIL SHIPPING</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans KR', Arial, sans-serif;
            background: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        .nav-bar {
            background: white;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn {
            padding: 10px 20px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-secondary {
            background: white;
            color: #374151;
            border-color: #d1d5db;
        }

        .btn-secondary:hover {
            background: #f9fafb;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .btn-danger {
            background: #dc2626;
            color: white;
            border-color: #dc2626;
        }

        .btn-danger:hover {
            background: #b91c1c;
        }

        .review-container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .review-header {
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .review-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 15px;
        }

        .review-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            color: #6b7280;
            font-size: 0.95rem;
        }

        .review-meta span {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .rating {
            display: flex;
            gap: 4px;
        }

        .rating i {
            color: #fbbf24;
            font-size: 1.1rem;
        }

        .author-section {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            background: #f9fafb;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .author-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.5rem;
        }

        .author-info h3 {
            font-size: 1.1rem;
            color: #1f2937;
            margin-bottom: 4px;
        }

        .author-info p {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .service-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            background: #e0e7ff;
            color: #4f46e5;
            margin-top: 8px;
        }

        .review-content {
            font-size: 1.1rem;
            line-height: 1.9;
            color: #374151;
            margin-bottom: 40px;
            white-space: pre-wrap;
        }

        .review-stats {
            display: flex;
            gap: 30px;
            padding: 20px;
            background: #f9fafb;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stat-item i {
            font-size: 1.2rem;
            color: #6b7280;
        }

        .stat-item span {
            font-size: 0.95rem;
            color: #6b7280;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .review-container {
                padding: 20px;
            }

            .review-title {
                font-size: 1.5rem;
            }

            .review-meta {
                flex-direction: column;
                gap: 10px;
            }

            .review-stats {
                flex-direction: column;
                gap: 15px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-bar">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            <a href="../index.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Home
            </a>
        </div>

        <div class="review-container">
            <div class="review-header">
                <h1 class="review-title"><?= htmlspecialchars($review['title']) ?></h1>
                <div class="review-meta">
                    <span>
                        <div class="rating">
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <i class="fas fa-star<?= $i < $review['rating'] ? '' : ' ' ?>"></i>
                            <?php endfor; ?>
                        </div>
                    </span>
                    <span><i class="fas fa-calendar"></i> <?= date('Y-m-d', strtotime($review['created_at'])) ?></span>
                    <span><i class="fas fa-eye"></i> Views <?= number_format($review['views']) ?></span>
                </div>
            </div>

            <div class="author-section">
                <div class="author-avatar">
                    <?= mb_substr($review['customer_name'], 0, 1) ?>
                </div>
                <div class="author-info">
                    <h3><?= htmlspecialchars($review['customer_name']) ?></h3>
                    <p><?= htmlspecialchars($review['customer_id']) ?></p>
                    <span class="service-badge">
                        <?php
                        $serviceTypes = [
                            'shipping' => 'Shipping Service',
                            'customs' => 'Customs Service',
                            'warehouse' => 'Warehouse Service',
                            'consulting' => 'Consulting',
                            'other' => 'Other'
                        ];
                        echo $serviceTypes[$review['service_type']] ?? 'Other';
                        ?>
                    </span>
                </div>
            </div>

            <div class="review-content">
                <?= nl2br(htmlspecialchars($review['content'])) ?>
            </div>

            <div class="review-stats">
                <div class="stat-item">
                    <i class="fas fa-star"></i>
                    <span>Rating: <strong><?= $review['rating'] ?></strong>/5</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-clock"></i>
                    <span>Created: <?= date('Y-m-d H:i', strtotime($review['created_at'])) ?></span>
                </div>
                <?php if ($review['updated_at'] != $review['created_at']): ?>
                <div class="stat-item">
                    <i class="fas fa-edit"></i>
                    <span>Updated: <?= date('Y-m-d H:i', strtotime($review['updated_at'])) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($is_author): ?>
            <div class="action-buttons">
                <a href="edit.php?id=<?= $review['review_id'] ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <a href="delete.php?id=<?= $review['review_id'] ?>" class="btn btn-danger"
                   onclick="return confirm('Are you sure you want to delete this review?')">
                    <i class="fas fa-trash"></i> Delete
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
