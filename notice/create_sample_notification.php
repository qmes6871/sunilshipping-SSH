<?php
/**
 * 샘플 알림 생성 스크립트
 */
session_start();
require_once 'notification_helper.php';

// 로그인 확인
if (!isset($_SESSION['username'])) {
    die('로그인이 필요합니다. <a href="../login/login.php">로그인</a>');
}

// 전체 회원에게 샘플 알림 생성
$result = createNotificationForAllUsers(
    'system',
    '환영합니다!',
    'SUNIL SHIPPING 알림 시스템이 정상적으로 작동하고 있습니다. 이제 중요한 업데이트와 공지사항을 실시간으로 받아보실 수 있습니다.',
    '/index.php',
    null
);

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>샘플 알림 생성</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 40px 20px;
            text-align: center;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success {
            color: #10b981;
            font-size: 48px;
            margin-bottom: 20px;
        }
        .error {
            color: #ef4444;
            font-size: 48px;
            margin-bottom: 20px;
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        a {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px;
        }
        a:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($result['success']): ?>
            <div class="success">✓</div>
            <h1>알림이 생성되었습니다!</h1>
            <p>
                <?php echo $result['users_count']; ?>명의 회원에게 알림이 전송되었습니다.<br>
                알림 ID: <?php echo $result['notification_id']; ?>
            </p>
        <?php else: ?>
            <div class="error">✗</div>
            <h1>알림 생성 실패</h1>
            <p>오류: <?php echo htmlspecialchars($result['error']); ?></p>
        <?php endif; ?>

        <a href="notifications.php">알림 확인하기</a>
        <a href="../index.php">홈으로</a>
    </div>
</body>
</html>
