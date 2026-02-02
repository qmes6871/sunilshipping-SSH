<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$conn = new mysqli('localhost', 'sunilshipping', 'sunil123!', 'sunilshipping');

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Get HOT ITEM ID (support both 'id' and 'item_id' parameters)
$item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

if ($item_id <= 0) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Error</title></head><body>';
    echo '<h2>Error: Invalid Access</h2>';
    echo '<p>Item ID received: ' . htmlspecialchars($item_id) . '</p>';
    echo '<p>You need to access this page with ?id=NUMBER or ?item_id=NUMBER</p>';
    echo '<p>Example: inquiry.php?id=1 or inquiry.php?item_id=1</p>';
    echo '<p><a href="index.php">Go to HOT ITEM list</a></p>';
    echo '<p><a href="/admin/hotitemupload/index.php">Go to Admin (add items)</a></p>';
    echo '</body></html>';
    exit;
}

// Query HOT ITEM information
$query = "SELECT hi.*,
          s1.name as primary_staff_name, s1.phone as primary_staff_phone, s1.mobile as primary_staff_mobile, s1.email as primary_staff_email,
          s2.name as secondary_staff_name, s2.phone as secondary_staff_phone, s2.mobile as secondary_staff_mobile, s2.email as secondary_staff_email,
          s3.name as secondary_staff_name_3, s3.phone as secondary_staff_phone_3, s3.mobile as secondary_staff_mobile_3, s3.email as secondary_staff_email_3
          FROM hot_items hi
          LEFT JOIN staff_management s1 ON hi.primary_staff_id = s1.id
          LEFT JOIN staff_management s2 ON hi.secondary_staff_id = s2.id
          LEFT JOIN staff_management s3 ON hi.secondary_staff_id_3 = s3.id
          WHERE hi.id = ? AND hi.is_active = 1";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Product Not Found</title></head><body>';
    echo '<h2>Product Not Found</h2>';
    echo '<p>Item ID ' . htmlspecialchars($item_id) . ' does not exist or is not active.</p>';

    // Show available items
    $available_query = "SELECT id, product_name FROM hot_items WHERE is_active = 1 ORDER BY id DESC LIMIT 5";
    $available_result = $conn->query($available_query);

    if ($available_result && $available_result->num_rows > 0) {
        echo '<h3>Available items:</h3><ul>';
        while ($avail = $available_result->fetch_assoc()) {
            echo '<li><a href="inquiry.php?id=' . $avail['id'] . '">' . htmlspecialchars($avail['product_name']) . ' (ID: ' . $avail['id'] . ')</a></li>';
        }
        echo '</ul>';
    } else {
        echo '<p>No active items found. Please <a href="/admin/hotitemupload/upload.php">add items first</a>.</p>';
    }

    echo '<p><a href="index.php">Go to HOT ITEM list</a></p>';
    echo '</body></html>';
    $stmt->close();
    $conn->close();
    exit;
}

$item = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>문의하기 - <?= htmlspecialchars($item['product_name'] ?? '') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #fafafa;
            padding: 20px;
            line-height: 1.5;
            color: #333;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border: 1px solid #e0e0e0;
        }

        .header {
            background: #333;
            color: white;
            padding: 24px;
            border-bottom: 3px solid #000;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 600;
        }

        .header p {
            font-size: 14px;
            color: #ccc;
            margin-top: 5px;
        }

        .content {
            padding: 30px;
        }

        .product-info {
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 30px;
            margin-bottom: 30px;
        }

        .product-info h2 {
            color: #000;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #000;
        }

        .product-images {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }

        .product-images img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border: 1px solid #ddd;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 100px 1fr;
            gap: 10px 15px;
            margin-bottom: 10px;
        }

        .info-label {
            font-weight: 600;
            color: #666;
            font-size: 14px;
        }

        .info-value {
            color: #000;
            font-size: 14px;
        }

        .price-info {
            display: flex;
            gap: 30px;
            margin: 20px 0;
            padding: 20px;
            background: #f9f9f9;
            border-left: 3px solid #000;
        }

        .price-item {
            flex: 1;
        }

        .price-label {
            font-size: 13px;
            color: #666;
            margin-bottom: 5px;
        }

        .price-value {
            font-size: 22px;
            font-weight: 700;
            color: #000;
        }

        .price-value.sale {
            color: #e53e3e;
        }

        .price-value.original {
            text-decoration: line-through;
            color: #999;
            font-size: 16px;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 8px;
            background: #000;
            color: white;
        }

        .inquiry-form {
            border-top: 2px solid #000;
            padding-top: 30px;
        }

        .form-title {
            font-size: 18px;
            font-weight: 600;
            color: #000;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            color: #000;
            font-weight: 500;
            font-size: 14px;
        }

        .required {
            color: #e53e3e;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #d0d0d0;
            font-size: 14px;
            font-family: inherit;
        }

        input:focus,
        textarea:focus {
            outline: none;
            border-color: #333;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: opacity 0.2s;
        }

        .btn:hover {
            opacity: 0.8;
        }

        .btn-primary {
            background: #000;
            color: white;
            width: 100%;
        }

        .btn-secondary {
            background: #666;
            color: white;
        }

        .staff-info {
            background: #f9f9f9;
            padding: 15px;
            margin-top: 20px;
            border-left: 3px solid #000;
        }

        .staff-title {
            font-size: 15px;
            font-weight: 600;
            color: #000;
            margin-bottom: 12px;
        }

        .staff-item {
            padding: 10px;
            background: white;
            border: 1px solid #e0e0e0;
            margin-bottom: 8px;
        }

        .staff-name {
            font-weight: 600;
            color: #000;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .contact-item {
            font-size: 13px;
            color: #555;
            margin: 3px 0;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        @media (max-width: 768px) {
            .price-info {
                flex-direction: column;
                gap: 15px;
            }

            .button-group {
                flex-direction: column;
            }

            body {
                padding: 0;
            }

            .container {
                border: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Product Inquiry</h1>
            <p>Please leave any questions you have about the product.</p>
        </div>

        <div class="content">
            <!-- 상품 정보 -->
            <div class="product-info">
                <h2>
                    <?= htmlspecialchars($item['product_name'] ?? '') ?>
                    <?php if (!empty($item['badge_type'])): ?>
                        <span class="badge">
                            <?= htmlspecialchars($item['badge_type'] ?? '') ?>
                        </span>
                    <?php endif; ?>
                </h2>

                <!-- 상품 이미지들 -->
                <?php
                $images = [];
                if (!empty($item['image_path_1'])) $images[] = $item['image_path_1'];
                if (!empty($item['image_path_2'])) $images[] = $item['image_path_2'];
                if (!empty($item['image_path_3'])) $images[] = $item['image_path_3'];
                if (!empty($item['image_path_4'])) $images[] = $item['image_path_4'];
                ?>

                <?php if (!empty($images)): ?>
                    <div class="product-images">
                        <?php foreach ($images as $img): ?>
                            <img src="<?= htmlspecialchars($img ?? '') ?>" alt="상품 이미지">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- 가격 정보 -->
                <?php if ($item['original_price'] > 0 || $item['sale_price'] > 0): ?>
                    <div class="price-info">
                        <?php if ($item['original_price'] > 0): ?>
                            <div class="price-item">
                                <div class="price-label">regular price</div>
                                <div class="price-value original">$<?= number_format($item['original_price']) ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if ($item['sale_price'] > 0): ?>
                            <div class="price-item">
                                <div class="price-label">sale price</div>
                                <div class="price-value sale">$<?= number_format($item['sale_price']) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- 상품 상세 정보 -->
                <div class="info-grid">
                    <?php if (!empty($item['content'])): ?>
                        <div class="info-label">detail</div>
                        <div class="info-value"><?= nl2br(htmlspecialchars($item['content'] ?? '')) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($item['description'])): ?>
                        <div class="info-label">Detailed description</div>
                        <div class="info-value"><?= nl2br(htmlspecialchars($item['description'] ?? '')) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($item['special_notes'])): ?>
                        <div class="info-label">significant</div>
                        <div class="info-value"><?= nl2br(htmlspecialchars($item['special_notes'] ?? '')) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($item['deadline'])): ?>
                        <div class="info-label">deadline</div>
                        <div class="info-value"><?= htmlspecialchars($item['deadline'] ?? '') ?></div>
                    <?php endif; ?>
                </div>

                <!-- 담당자 정보 -->
                <?php if (!empty($item['primary_staff_name']) || !empty($item['secondary_staff_name']) || !empty($item['secondary_staff_name_3'])): ?>
                    <div class="staff-info">
                        <div class="staff-title">Contact information</div>

                        <?php if (!empty($item['primary_staff_name'])): ?>
                            <div class="staff-item">
                                <div class="staff-name"><?= htmlspecialchars($item['primary_staff_name'] ?? '') ?> (Main Manager)</div>
                                <?php if (!empty($item['primary_staff_phone'])): ?>
                                    <div class="contact-item">Telephone: <?= htmlspecialchars($item['primary_staff_phone'] ?? '') ?></div>
                                <?php endif; ?>
                                <?php if (!empty($item['primary_staff_mobile'])): ?>
                                    <div class="contact-item">Mobile: <?= htmlspecialchars($item['primary_staff_mobile'] ?? '') ?></div>
                                <?php endif; ?>
                                <?php if (!empty($item['primary_staff_email'])): ?>
                                    <div class="contact-item">Email: <?= htmlspecialchars($item['primary_staff_email'] ?? '') ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($item['secondary_staff_name'])): ?>
                            <div class="staff-item">
                                <div class="staff-name"><?= htmlspecialchars($item['secondary_staff_name'] ?? '') ?> (Sub Manager)</div>
                                <?php if (!empty($item['secondary_staff_phone'])): ?>
                                    <div class="contact-item">Telephone: <?= htmlspecialchars($item['secondary_staff_phone'] ?? '') ?></div>
                                <?php endif; ?>
                                <?php if (!empty($item['secondary_staff_mobile'])): ?>
                                    <div class="contact-item">Mobile: <?= htmlspecialchars($item['secondary_staff_mobile'] ?? '') ?></div>
                                <?php endif; ?>
                                <?php if (!empty($item['secondary_staff_email'])): ?>
                                    <div class="contact-item">Email: <?= htmlspecialchars($item['secondary_staff_email'] ?? '') ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($item['secondary_staff_name_3'])): ?>
                            <div class="staff-item">
                                <div class="staff-name"><?= htmlspecialchars($item['secondary_staff_name_3'] ?? '') ?> (Sub Manager)</div>
                                <?php if (!empty($item['secondary_staff_phone_3'])): ?>
                                    <div class="contact-item">Telephone: <?= htmlspecialchars($item['secondary_staff_phone_3'] ?? '') ?></div>
                                <?php endif; ?>
                                <?php if (!empty($item['secondary_staff_mobile_3'])): ?>
                                    <div class="contact-item">Mobile: <?= htmlspecialchars($item['secondary_staff_mobile_3'] ?? '') ?></div>
                                <?php endif; ?>
                                <?php if (!empty($item['secondary_staff_email_3'])): ?>
                                    <div class="contact-item">Email: <?= htmlspecialchars($item['secondary_staff_email_3'] ?? '') ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 문의 폼 -->
            <div class="inquiry-form">
                <h3 class="form-title">Contact Us</h3>
                <form id="inquiryForm" method="POST" action="inquiry_submit.php">
                    <input type="hidden" name="item_id" value="<?= $item_id ?>">
                    <input type="hidden" name="product_name" value="<?= htmlspecialchars($item['product_name'] ?? '') ?>">

                    <div class="form-group">
                        <label>Name <span class="required">*</span></label>
                        <input type="text" name="customer_name" required placeholder="Name: Please enter your name.">
                    </div>

                    <div class="form-group">
                        <label>Email <span class="required">*</span></label>
                        <input type="email" name="customer_email" required placeholder="example@email.com">
                    </div>

                    <div class="form-group">
                        <label>Contact <span class="required">*</span></label>
                        <input type="tel" name="customer_phone" required placeholder="010-1234-5678">
                    </div>

                    <div class="form-group">
                        <label>Message <span class="required">*</span></label>
                        <textarea name="inquiry_message" required placeholder="Inquiry content: Please enter the details of your inquiry."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Send Inquiry</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('inquiryForm').addEventListener('submit', function(e) {
            const name = document.querySelector('input[name="customer_name"]').value.trim();
            const email = document.querySelector('input[name="customer_email"]').value.trim();
            const phone = document.querySelector('input[name="customer_phone"]').value.trim();
            const message = document.querySelector('textarea[name="inquiry_message"]').value.trim();

            if (!name || !email || !phone || !message) {
                e.preventDefault();
                alert('모든 필수 항목을 입력해주세요.');
                return false;
            }

            if (!email.includes('@')) {
                e.preventDefault();
                alert('올바른 이메일 형식을 입력해주세요.');
                return false;
            }
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>
