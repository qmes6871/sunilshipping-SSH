<?php
session_start();

// UTF-8 헤더 설정
header('Content-Type: text/html; charset=utf-8');

// 이미 로그인된 경우 관리자 페이지로 리다이렉트
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// 데이터베이스 연결
define('G5_MYSQL_HOST', 'localhost');
define('G5_MYSQL_USER', 'sunilshipping');
define('G5_MYSQL_PASSWORD', 'sunil123!');
define('G5_MYSQL_DB', 'sunilshipping');

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $dsn = "mysql:host=" . G5_MYSQL_HOST . ";dbname=" . G5_MYSQL_DB . ";charset=utf8mb4";
        $conn = new PDO($dsn, G5_MYSQL_USER, G5_MYSQL_PASSWORD);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_role'] = $admin['role'];
            
            // 마지막 로그인 시간 업데이트
            $updateStmt = $conn->prepare("UPDATE admins SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
            $updateStmt->execute([$admin['id']]);
            
            header('Location: index.php');
            exit;
        } else {
            $message = '로그인 정보가 올바르지 않습니다.';
        }
    } catch(PDOException $e) {
        $message = '데이터베이스 오류: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 로그인 - SUNIL SHIPPING</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo i {
            font-size: 3rem;
            color: #2563eb;
            margin-bottom: 1rem;
        }
        
        .logo h1 {
            color: #2563eb;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .logo p {
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }
        
        .form-input {
            width: 100%;
            padding: 1rem;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
        }
        
        .message {
            background: #fee2e2;
            color: #dc2626;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .default-account-info {
            background: #f0f9ff;
            color: #0369a1;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            text-align: center;
            font-size: 0.9rem;
            border: 1px solid #bae6fd;
        }

        .default-account-info strong {
            display: block;
            margin-bottom: 0.5rem;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 1.5rem;
                margin: 1rem;
            }
            
            .logo h1 {
                font-size: 1.3rem;
            }
            
            .logo i {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <i class="fas fa-ship"></i>
            <h1>SUNIL SHIPPING</h1>
            <p>관리자 시스템</p>
        </div>
        
        <?php if ($message): ?>
        <div class="message">
            <i class="fas fa-exclamation-triangle"></i>
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <!-- 기본 계정 정보 안내 -->
        <div class="default-account-info">
            <strong>테스트용 기본 계정:</strong>
            <div>ID: admin</div>
            <div>PW: admin123!</div>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label class="form-label">사용자명</label>
                <input type="text" class="form-input" name="username" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">비밀번호</label>
                <input type="password" class="form-input" name="password" required>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> 로그인
            </button>
        </form>
    </div>
</body>
</html>