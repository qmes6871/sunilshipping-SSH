<?php
/**
 * Review Write Page
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
$customer_name = $_SESSION['name'] ?? '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $service_type = $_POST['service_type'] ?? 'shipping';
    $rating = (int)($_POST['rating'] ?? 5);

    $errors = [];

    // Validation
    if (empty($title)) {
        $errors[] = 'Title is required.';
    } elseif (mb_strlen($title) < 5) {
        $errors[] = 'Title must be at least 5 characters.';
    } elseif (mb_strlen($title) > 200) {
        $errors[] = 'Title cannot exceed 200 characters.';
    }

    if (empty($content)) {
        $errors[] = 'Content is required.';
    } elseif (mb_strlen($content) < 10) {
        $errors[] = 'Content must be at least 10 characters.';
    }

    if ($rating < 1 || $rating > 5) {
        $errors[] = 'Rating must be between 1 and 5.';
    }

    // If no errors, insert review
    if (empty($errors)) {
        $sql = "INSERT INTO reviews (customer_id, customer_name, service_type, title, content, rating, status)
                VALUES (:customer_id, :customer_name, :service_type, :title, :content, :rating, 'approved')";

        try {
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':customer_id' => $customer_id,
                ':customer_name' => $customer_name,
                ':service_type' => $service_type,
                ':title' => $title,
                ':content' => $content,
                ':rating' => $rating
            ]);

            // Redirect to review list with success message
            header('Location: index.php?success=1');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Failed to save review: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Write Review - SUNIL SHIPPING</title>
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
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 1.8rem;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .header p {
            color: #6b7280;
            font-size: 0.95rem;
        }

        .form-container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .form-group label .required {
            color: #dc2626;
            margin-left: 4px;
        }

        .form-group input[type="text"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 1rem;
            font-family: 'Noto Sans KR', Arial, sans-serif;
            transition: all 0.2s;
        }

        .form-group input[type="text"]:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-group textarea {
            min-height: 200px;
            resize: vertical;
        }

        .rating-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .rating-stars {
            display: flex;
            gap: 5px;
        }

        .rating-stars input[type="radio"] {
            display: none;
        }

        .rating-stars label {
            font-size: 2rem;
            color: #d1d5db;
            cursor: pointer;
            transition: all 0.2s;
            margin: 0;
        }

        .rating-stars input[type="radio"]:checked ~ label,
        .rating-stars label:hover,
        .rating-stars label:hover ~ label {
            color: #fbbf24;
        }

        .rating-stars {
            flex-direction: row-reverse;
            justify-content: flex-end;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-error {
            background: #fee;
            border: 1px solid #fcc;
            color: #c00;
        }

        .alert-error ul {
            margin: 10px 0 0 20px;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        .btn {
            padding: 12px 24px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
        }

        .btn-primary:hover {
            background: #1d4ed8;
            border-color: #1d4ed8;
        }

        .btn-secondary {
            background: white;
            color: #374151;
            border-color: #d1d5db;
        }

        .btn-secondary:hover {
            background: #f9fafb;
        }

        .char-count {
            font-size: 0.85rem;
            color: #6b7280;
            margin-top: 5px;
            text-align: right;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .form-container {
                padding: 20px;
            }

            .header {
                padding: 20px;
            }

            .form-actions {
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
        <div class="header">
            <h1><i class="fas fa-pen"></i> Write Review</h1>
            <p>Share your experience with SUNIL SHIPPING</p>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <strong>Please correct the following errors:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="POST" action="" class="form-container">
            <div class="form-group">
                <label for="service_type">
                    Service Type
                    <span class="required">*</span>
                </label>
                <select name="service_type" id="service_type" required>
                    <option value="shipping" <?= (isset($_POST['service_type']) && $_POST['service_type'] === 'shipping') ? 'selected' : '' ?>>Shipping Service</option>
                    <option value="customs" <?= (isset($_POST['service_type']) && $_POST['service_type'] === 'customs') ? 'selected' : '' ?>>Customs Service</option>
                    <option value="warehouse" <?= (isset($_POST['service_type']) && $_POST['service_type'] === 'warehouse') ? 'selected' : '' ?>>Warehouse Service</option>
                    <option value="consulting" <?= (isset($_POST['service_type']) && $_POST['service_type'] === 'consulting') ? 'selected' : '' ?>>Consulting</option>
                    <option value="other" <?= (isset($_POST['service_type']) && $_POST['service_type'] === 'other') ? 'selected' : '' ?>>Other</option>
                </select>
            </div>

            <div class="form-group">
                <label for="rating">
                    Rating
                    <span class="required">*</span>
                </label>
                <div class="rating-group">
                    <div class="rating-stars">
                        <input type="radio" name="rating" id="rating5" value="5" <?= (!isset($_POST['rating']) || $_POST['rating'] == 5) ? 'checked' : '' ?>>
                        <label for="rating5"><i class="fas fa-star"></i></label>

                        <input type="radio" name="rating" id="rating4" value="4" <?= (isset($_POST['rating']) && $_POST['rating'] == 4) ? 'checked' : '' ?>>
                        <label for="rating4"><i class="fas fa-star"></i></label>

                        <input type="radio" name="rating" id="rating3" value="3" <?= (isset($_POST['rating']) && $_POST['rating'] == 3) ? 'checked' : '' ?>>
                        <label for="rating3"><i class="fas fa-star"></i></label>

                        <input type="radio" name="rating" id="rating2" value="2" <?= (isset($_POST['rating']) && $_POST['rating'] == 2) ? 'checked' : '' ?>>
                        <label for="rating2"><i class="fas fa-star"></i></label>

                        <input type="radio" name="rating" id="rating1" value="1" <?= (isset($_POST['rating']) && $_POST['rating'] == 1) ? 'checked' : '' ?>>
                        <label for="rating1"><i class="fas fa-star"></i></label>
                    </div>
                    <span id="ratingText" style="margin-left: 15px; color: #6b7280;"></span>
                </div>
            </div>

            <div class="form-group">
                <label for="title">
                    Title
                    <span class="required">*</span>
                </label>
                <input type="text" name="title" id="title"
                       value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                       placeholder="Enter review title (min 5 characters)"
                       maxlength="200"
                       required>
                <div class="char-count"><span id="titleCount">0</span>/200</div>
            </div>

            <div class="form-group">
                <label for="content">
                    Review Content
                    <span class="required">*</span>
                </label>
                <textarea name="content" id="content"
                          placeholder="Share your detailed experience (min 10 characters)"
                          required><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                <div class="char-count"><span id="contentCount">0</span> characters</div>
            </div>

            <div class="form-actions">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Submit Review
                </button>
            </div>
        </form>
    </div>

    <script>
        // Character counter for title
        const titleInput = document.getElementById('title');
        const titleCount = document.getElementById('titleCount');

        titleInput.addEventListener('input', function() {
            titleCount.textContent = this.value.length;
        });

        // Initialize count
        titleCount.textContent = titleInput.value.length;

        // Character counter for content
        const contentInput = document.getElementById('content');
        const contentCount = document.getElementById('contentCount');

        contentInput.addEventListener('input', function() {
            contentCount.textContent = this.value.length;
        });

        // Initialize count
        contentCount.textContent = contentInput.value.length;

        // Rating text display
        const ratingInputs = document.querySelectorAll('input[name="rating"]');
        const ratingText = document.getElementById('ratingText');

        const ratingTexts = {
            '5': 'Excellent',
            '4': 'Good',
            '3': 'Average',
            '2': 'Poor',
            '1': 'Very Poor'
        };

        function updateRatingText() {
            const checked = document.querySelector('input[name="rating"]:checked');
            if (checked) {
                ratingText.textContent = ratingTexts[checked.value];
            }
        }

        ratingInputs.forEach(input => {
            input.addEventListener('change', updateRatingText);
        });

        // Initialize rating text
        updateRatingText();
    </script>
</body>
</html>
