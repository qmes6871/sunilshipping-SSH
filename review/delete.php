<?php
/**
 * Review Delete Page
 */
session_start();

// Check login status
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: ../login/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Include database connection
require_once 'config.php';

// Check database connection
if ($conn === null) {
    die('Database connection failed. Please check config.php');
}

$customer_id = $_SESSION['username'];

// Get review ID
$review_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($review_id <= 0) {
    header('Location: index.php');
    exit;
}

// Get review details and check ownership
$sql = "SELECT * FROM reviews WHERE review_id = :review_id";
$stmt = $conn->prepare($sql);
$stmt->execute([':review_id' => $review_id]);
$review = $stmt->fetch();

// Check if review exists
if (!$review) {
    header('Location: index.php?error=notfound');
    exit;
}

// Check if user is the author
if ($review['customer_id'] !== $customer_id) {
    header('Location: index.php?error=unauthorized');
    exit;
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirm = $_POST['confirm'] ?? '';

    if ($confirm === 'yes') {
        try {
            $delete_sql = "DELETE FROM reviews WHERE review_id = :review_id AND customer_id = :customer_id";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->execute([
                ':review_id' => $review_id,
                ':customer_id' => $customer_id
            ]);

            header('Location: index.php?deleted=1');
            exit;
        } catch (PDOException $e) {
            $error = 'Failed to delete review: ' . $e->getMessage();
        }
    } else {
        header('Location: view.php?id=' . $review_id);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Review - SUNIL SHIPPING</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            max-width: 500px;
            width: 100%;
            padding: 20px;
        }

        .delete-card {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
        }

        .warning-icon {
            font-size: 4rem;
            color: #dc2626;
            margin-bottom: 20px;
        }

        h1 {
            font-size: 1.8rem;
            color: #1f2937;
            margin-bottom: 15px;
        }

        .review-info {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
        }

        .review-info h3 {
            font-size: 1.1rem;
            color: #374151;
            margin-bottom: 10px;
        }

        .review-info p {
            color: #6b7280;
            font-size: 0.9rem;
            margin: 5px 0;
        }

        .warning-message {
            background: #fee;
            border: 1px solid #fcc;
            color: #991b1b;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            font-size: 0.95rem;
        }

        .warning-message strong {
            display: block;
            margin-bottom: 5px;
        }

        .button-group {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-danger {
            background: #dc2626;
            color: white;
        }

        .btn-danger:hover {
            background: #b91c1c;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(220, 38, 38, 0.3);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .error-message {
            background: #fee;
            border: 1px solid #fcc;
            color: #991b1b;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .delete-card {
                padding: 30px 20px;
            }

            .button-group {
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
        <div class="delete-card">
            <div class="warning-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>

            <h1>Delete Review</h1>

            <?php if (isset($error)): ?>
            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <div class="review-info">
                <h3><?= htmlspecialchars($review['title']) ?></h3>
                <p><i class="fas fa-calendar"></i> <?= date('Y-m-d', strtotime($review['created_at'])) ?></p>
                <p><i class="fas fa-star"></i> Rating: <?= $review['rating'] ?>/5</p>
            </div>

            <div class="warning-message">
                <strong>Warning!</strong>
                Are you sure you want to delete this review? This action cannot be undone.
            </div>

            <form method="POST" action="">
                <div class="button-group">
                    <a href="view.php?id=<?= $review_id ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" name="confirm" value="yes" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete Review
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
