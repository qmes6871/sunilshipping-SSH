<?php
// 에러 표시 (디버깅용)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/db_config.php';

// 로그인 체크 (옵션)
// if (!isset($_SESSION['username'])) {
//     header('Location: /login/login.php');
//     exit;
// }

$error = '';
$success = '';
$conn = get_db_connection();

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 입력값 받기
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $manufacturer = trim($_POST['manufacturer'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $year = intval($_POST['year'] ?? 0);
    $mileage = intval($_POST['mileage'] ?? 0);
    $transmission = trim($_POST['transmission'] ?? '');
    $fuel = trim($_POST['fuel'] ?? '');
    $accident = trim($_POST['accident'] ?? '');
    $accident_detail = trim($_POST['accident_detail'] ?? '');
    $start_price = intval($_POST['start_price'] ?? 0);
    $auction_days = intval($_POST['auction_days'] ?? 7);
    
    // 이미지 업로드 처리
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_ext, $allowed_exts)) {
            $new_filename = 'auction_' . time() . '_' . uniqid() . '.' . $file_ext;
            $target_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $image_path = 'uploads/' . $new_filename;
            } else {
                $error = '이미지 업로드에 실패했습니다.';
            }
        } else {
            $error = '허용되지 않는 이미지 형식입니다. (jpg, jpeg, png, gif, webp만 가능)';
        }
    }
    
    // 유효성 검사
    if (empty($error)) {
        if (empty($title)) {
            $error = '제목을 입력해주세요.';
        } elseif ($start_price <= 0) {
            $error = '시작가를 입력해주세요.';
        } else {
            // 경매 종료 시간 계산 (MySQL DATETIME 형식)
            $end_time = date('Y-m-d H:i:s', time() + ($auction_days * 86400));
            
            try {
                // MySQL INSERT 쿼리
                $stmt = $conn->prepare("
                    INSERT INTO auctions (
                        title, description, manufacturer, model, year, mileage,
                        transmission, fuel, accident, accident_detail,
                        start_price, current_price, image, end_time, status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
                ");
                
                $stmt->bind_param(
                    "ssssiissssddss",
                    $title,
                    $description,
                    $manufacturer,
                    $model,
                    $year,
                    $mileage,
                    $transmission,
                    $fuel,
                    $accident,
                    $accident_detail,
                    $start_price,
                    $start_price, // current_price = start_price
                    $image_path,
                    $end_time
                );
                
                if ($stmt->execute()) {
                    $auction_id = $conn->insert_id;
                    $success = '경매가 성공적으로 생성되었습니다. (ID: ' . $auction_id . ')';
                    
                    // 3초 후 리다이렉트
                    echo '<script>';
                    echo 'setTimeout(function() {';
                    echo '  window.location.href = "view.php?id=' . $auction_id . '";';
                    echo '}, 3000);';
                    echo '</script>';
                } else {
                    $error = '경매 생성에 실패했습니다: ' . $stmt->error;
                }
                
            } catch (Exception $e) {
                $error = '오류 발생: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>경매 생성 - AUCTION</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; 
            background: #fafafa; 
            line-height: 1.6; 
        }
        .header { 
            background: #fff; 
            border-bottom: 1px solid #e5e7eb; 
            position: sticky; 
            top: 0; 
            z-index: 1000; 
        }
        .nav-container { 
            max-width: 1400px; 
            margin: 0 auto; 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            padding: 0 2rem; 
            height: 70px; 
        }
        .logo { 
            font-size: 1.5rem; 
            font-weight: 700; 
            color: #2563eb; 
            text-decoration: none; 
        }
        .nav-menu { 
            display: flex; 
            list-style: none; 
            gap: 2.5rem; 
        }
        .nav-menu a { 
            color: #4b5563; 
            text-decoration: none; 
            font-weight: 500; 
        }
        .nav-menu a:hover { 
            color: #2563eb; 
        }
        .container { 
            max-width: 800px; 
            margin: 0 auto; 
            padding: 48px 24px; 
        }
        .page-header { 
            margin-bottom: 40px; 
        }
        .page-title { 
            font-size: 32px; 
            font-weight: 700; 
            color: #111; 
            margin-bottom: 8px; 
        }
        .page-subtitle { 
            color: #666; 
            font-size: 15px; 
        }
        .form-card { 
            background: #fff; 
            border-radius: 16px; 
            padding: 40px; 
            box-shadow: 0 1px 3px rgba(0,0,0,0.1); 
        }
        .form-section { 
            margin-bottom: 32px; 
        }
        .form-section-title { 
            font-size: 18px; 
            font-weight: 600; 
            color: #111; 
            margin-bottom: 16px; 
            padding-bottom: 8px; 
            border-bottom: 2px solid #e5e7eb; 
        }
        .form-group { 
            margin-bottom: 20px; 
        }
        .form-row { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 16px; 
        }
        label { 
            display: block; 
            font-weight: 600; 
            color: #374151; 
            margin-bottom: 8px; 
            font-size: 14px; 
        }
        label .required { 
            color: #ef4444; 
            margin-left: 2px; 
        }
        label .optional { 
            color: #9ca3af; 
            font-weight: 400; 
            font-size: 12px; 
            margin-left: 4px; 
        }
        input[type="text"],
        input[type="number"],
        input[type="file"],
        select,
        textarea { 
            width: 100%; 
            padding: 12px 16px; 
            border: 1px solid #d1d5db; 
            border-radius: 8px; 
            font-size: 15px; 
            transition: border-color 0.2s; 
            font-family: inherit; 
        }
        input:focus,
        select:focus,
        textarea:focus { 
            outline: none; 
            border-color: #2563eb; 
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); 
        }
        textarea { 
            resize: vertical; 
            min-height: 120px; 
        }
        .input-hint { 
            font-size: 12px; 
            color: #6b7280; 
            margin-top: 4px; 
        }
        .image-preview { 
            margin-top: 12px; 
            display: none; 
        }
        .image-preview img { 
            max-width: 300px; 
            max-height: 200px; 
            border-radius: 8px; 
            border: 1px solid #e5e7eb; 
        }
        .alert { 
            padding: 12px 16px; 
            border-radius: 8px; 
            margin-bottom: 24px; 
            font-size: 14px; 
        }
        .alert-error { 
            background: #fee; 
            color: #c00; 
            border: 1px solid #fcc; 
        }
        .alert-success { 
            background: #efe; 
            color: #080; 
            border: 1px solid #cfc; 
        }
        .form-actions { 
            display: flex; 
            gap: 12px; 
            justify-content: flex-end; 
            padding-top: 24px; 
            border-top: 1px solid #e5e7eb; 
        }
        .btn { 
            padding: 12px 24px; 
            border: none; 
            border-radius: 8px; 
            font-size: 15px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: all 0.2s; 
            text-decoration: none; 
            display: inline-block; 
        }
        .btn-primary { 
            background: #2563eb; 
            color: white; 
        }
        .btn-primary:hover { 
            background: #1d4ed8; 
        }
        .btn-secondary { 
            background: #f3f4f6; 
            color: #374151; 
        }
        .btn-secondary:hover { 
            background: #e5e7eb; 
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="nav-container">
            <a href="/" class="logo">MY SHOP</a>
            <nav>
                <ul class="nav-menu">
                    <li><a href="/">HOME</a></li>
                    <li><a href="/hotitem/">HOT ITEM</a></li>
                    <li><a href="/auction_1/" style="color:#2563eb;font-weight:700">AUCTION</a></li>
                    <li><a href="/reserve/">RESERVE</a></li>
                    <li><a href="/review/">REVIEW</a></li>
                    <li><a href="/tracing/">TRACING</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">새 경매 생성</h1>
            <p class="page-subtitle">경매할 차량의 상세 정보를 입력해주세요.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="form-card">
            <!-- 기본 정보 -->
            <div class="form-section">
                <h2 class="form-section-title">기본 정보</h2>
                
                <div class="form-group">
                    <label for="title">
                        경매 제목<span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="title" 
                        name="title" 
                        placeholder="예: 2020년식 현대 아반떼 1.6 가솔린" 
                        required
                        value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="description">
                        상세 설명<span class="optional">(선택사항)</span>
                    </label>
                    <textarea 
                        id="description" 
                        name="description" 
                        placeholder="차량의 상태, 특징, 주요 옵션 등을 자세히 설명해주세요."
                    ><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="image">
                        대표 이미지<span class="optional">(선택사항)</span>
                    </label>
                    <input 
                        type="file" 
                        id="image" 
                        name="image" 
                        accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                        onchange="previewImage(this)"
                    >
                    <p class="input-hint">JPG, JPEG, PNG, GIF, WEBP 파일만 업로드 가능합니다.</p>
                    <div class="image-preview" id="imagePreview">
                        <img id="previewImg" src="" alt="이미지 미리보기">
                    </div>
                </div>
            </div>

            <!-- 차량 정보 -->
            <div class="form-section">
                <h2 class="form-section-title">차량 정보</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="manufacturer">
                            제조사<span class="optional">(선택사항)</span>
                        </label>
                        <input 
                            type="text" 
                            id="manufacturer" 
                            name="manufacturer" 
                            placeholder="예: 현대, 기아, 벤츠"
                            value="<?= htmlspecialchars($_POST['manufacturer'] ?? '') ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="model">
                            차종<span class="optional">(선택사항)</span>
                        </label>
                        <input 
                            type="text" 
                            id="model" 
                            name="model" 
                            placeholder="예: 아반떼, K5, E-Class"
                            value="<?= htmlspecialchars($_POST['model'] ?? '') ?>"
                        >
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="year">
                            연식<span class="optional">(선택사항)</span>
                        </label>
                        <input 
                            type="number" 
                            id="year" 
                            name="year" 
                            placeholder="예: 2020" 
                            min="1900" 
                            max="<?= date('Y') + 1 ?>"
                            value="<?= htmlspecialchars($_POST['year'] ?? '') ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="mileage">
                            주행거리 (km)<span class="optional">(선택사항)</span>
                        </label>
                        <input 
                            type="number" 
                            id="mileage" 
                            name="mileage" 
                            placeholder="예: 50000" 
                            min="0"
                            value="<?= htmlspecialchars($_POST['mileage'] ?? '') ?>"
                        >
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="transmission">
                            변속기<span class="optional">(선택사항)</span>
                        </label>
                        <select id="transmission" name="transmission">
                            <option value="">선택하세요</option>
                            <option value="자동" <?= ($_POST['transmission'] ?? '') === '자동' ? 'selected' : '' ?>>자동</option>
                            <option value="수동" <?= ($_POST['transmission'] ?? '') === '수동' ? 'selected' : '' ?>>수동</option>
                            <option value="CVT" <?= ($_POST['transmission'] ?? '') === 'CVT' ? 'selected' : '' ?>>CVT</option>
                            <option value="DCT" <?= ($_POST['transmission'] ?? '') === 'DCT' ? 'selected' : '' ?>>DCT</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="fuel">
                            연료<span class="optional">(선택사항)</span>
                        </label>
                        <select id="fuel" name="fuel">
                            <option value="">선택하세요</option>
                            <option value="가솔린" <?= ($_POST['fuel'] ?? '') === '가솔린' ? 'selected' : '' ?>>가솔린</option>
                            <option value="디젤" <?= ($_POST['fuel'] ?? '') === '디젤' ? 'selected' : '' ?>>디젤</option>
                            <option value="LPG" <?= ($_POST['fuel'] ?? '') === 'LPG' ? 'selected' : '' ?>>LPG</option>
                            <option value="하이브리드" <?= ($_POST['fuel'] ?? '') === '하이브리드' ? 'selected' : '' ?>>하이브리드</option>
                            <option value="전기" <?= ($_POST['fuel'] ?? '') === '전기' ? 'selected' : '' ?>>전기</option>
                            <option value="수소" <?= ($_POST['fuel'] ?? '') === '수소' ? 'selected' : '' ?>>수소</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="accident">
                        사고 이력<span class="optional">(선택사항)</span>
                    </label>
                    <select id="accident" name="accident" onchange="toggleAccidentDetail(this)">
                        <option value="">선택하세요</option>
                        <option value="무사고" <?= ($_POST['accident'] ?? '') === '무사고' ? 'selected' : '' ?>>무사고</option>
                        <option value="단순수리" <?= ($_POST['accident'] ?? '') === '단순수리' ? 'selected' : '' ?>>단순수리</option>
                        <option value="사고" <?= ($_POST['accident'] ?? '') === '사고' ? 'selected' : '' ?>>사고</option>
                    </select>
                </div>

                <div class="form-group" id="accidentDetailGroup" style="display: none;">
                    <label for="accident_detail">
                        사고 상세 내용<span class="optional">(선택사항)</span>
                    </label>
                    <textarea 
                        id="accident_detail" 
                        name="accident_detail" 
                        placeholder="사고 이력에 대한 상세 설명을 입력해주세요."
                    ><?= htmlspecialchars($_POST['accident_detail'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- 경매 설정 -->
            <div class="form-section">
                <h2 class="form-section-title">경매 설정</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="start_price">
                            시작가 (원)<span class="required">*</span>
                        </label>
                        <input 
                            type="number" 
                            id="start_price" 
                            name="start_price" 
                            placeholder="예: 15000000" 
                            min="0" 
                            step="10000"
                            required
                            value="<?= htmlspecialchars($_POST['start_price'] ?? '') ?>"
                        >
                        <p class="input-hint">경매 시작 가격을 입력해주세요.</p>
                    </div>

                    <div class="form-group">
                        <label for="auction_days">
                            경매 기간 (일)<span class="required">*</span>
                        </label>
                        <select id="auction_days" name="auction_days" required>
                            <option value="1" <?= ($_POST['auction_days'] ?? '7') == '1' ? 'selected' : '' ?>>1일</option>
                            <option value="3" <?= ($_POST['auction_days'] ?? '7') == '3' ? 'selected' : '' ?>>3일</option>
                            <option value="5" <?= ($_POST['auction_days'] ?? '7') == '5' ? 'selected' : '' ?>>5일</option>
                            <option value="7" <?= ($_POST['auction_days'] ?? '7') == '7' ? 'selected' : '' ?>>7일 (추천)</option>
                            <option value="10" <?= ($_POST['auction_days'] ?? '7') == '10' ? 'selected' : '' ?>>10일</option>
                            <option value="14" <?= ($_POST['auction_days'] ?? '7') == '14' ? 'selected' : '' ?>>14일</option>
                            <option value="30" <?= ($_POST['auction_days'] ?? '7') == '30' ? 'selected' : '' ?>>30일</option>
                        </select>
                        <p class="input-hint">경매가 진행될 기간을 선택해주세요.</p>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="index.php" class="btn btn-secondary">취소</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-gavel"></i> 경매 생성
                </button>
            </div>
        </form>
    </div>

    <script>
        // 이미지 미리보기
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            const previewImg = document.getElementById('previewImg');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                };
                
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
            }
        }

        // 사고 이력에 따른 상세 내용 표시/숨김
        function toggleAccidentDetail(select) {
            const detailGroup = document.getElementById('accidentDetailGroup');
            if (select.value === '단순수리' || select.value === '사고') {
                detailGroup.style.display = 'block';
            } else {
                detailGroup.style.display = 'none';
            }
        }

        // 페이지 로드 시 사고 이력 상태 확인
        document.addEventListener('DOMContentLoaded', function() {
            const accidentSelect = document.getElementById('accident');
            toggleAccidentDetail(accidentSelect);
        });
    </script>
</body>
</html>
