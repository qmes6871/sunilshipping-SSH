<?php
/**
 * 알림 목록 페이지 (사용자용)
 */
// 오류 표시 활성화 (디버깅용)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'notification_helper.php';

// 로그인 확인
if (!isset($_SESSION['username'])) {
    echo '<!DOCTYPE html>
    <html lang="ko">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>로그인 필요</title>
        <style>
            body { font-family: sans-serif; text-align: center; padding: 100px; background: #f5f5f5; }
            .message { background: white; padding: 40px; border-radius: 10px; max-width: 500px; margin: 0 auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            a { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; }
            a:hover { background: #5568d3; }
        </style>
    </head>
    <body>
        <div class="message">
            <h2>🔒 로그인이 필요합니다</h2>
            <p>알림을 확인하려면 먼저 로그인해주세요.</p>
            <a href="../login/login.php">로그인하기</a>
            <a href="../index.php">홈으로</a>
        </div>
    </body>
    </html>';
    exit;
}

$username = $_SESSION['username'];

// 알림 목록 조회
$notifications = getUserNotifications($username, 50);
$unread_count = getUnreadNotificationCount($username);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>알림</title>
    <style>
      /* Minimal neutral styles */
      :root { --bg:#FFFFFF; --text:#111827; --muted:#6B7280; --border:#E5E7EB; }
      *{margin:0;padding:0;box-sizing:border-box}
      body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Noto Sans KR',Arial,sans-serif;background:#fff;color:var(--text);padding:16px}
      .container{max-width:800px;margin:0 auto;background:#fff;border:1px solid var(--border);border-radius:8px;overflow:hidden}
      .header{padding:14px 16px;border-bottom:1px solid var(--border);background:#fff;color:var(--text)}
      .header h1{font-size:18px;margin:0 0 2px}
      .header p{font-size:12px;color:var(--muted)}
      .notification-item{position:relative;padding:12px 16px;border-bottom:1px solid var(--border)}
      .notification-item:last-child{border-bottom:none}
      .notification-item.unread{background:#F9FAFB}
      .notification-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:6px}
      .notification-type{display:inline-block;padding:2px 8px;border:1px solid var(--border);border-radius:999px;font-size:11px;color:var(--muted);background:transparent;font-weight:500}
      .notification-time{font-size:12px;color:var(--muted)}
      .notification-title{font-size:14px;font-weight:600;margin:0 0 4px}
      .notification-message{font-size:13px;color:#374151;line-height:1.6}
      .notification-link{margin-top:8px}
      .notification-link a{color:inherit;text-decoration:underline;text-underline-offset:2px}
      .mark-read-btn{margin-top:8px;padding:6px 10px;background:transparent;color:var(--text);border:1px solid var(--border);border-radius:6px;cursor:pointer;font-size:12px}
      .mark-read-btn:hover{background:#F9FAFB}
      .empty-state{text-align:center;padding:40px 16px;color:var(--muted)}
      .empty-state svg{width:64px;height:64px;margin-bottom:12px;opacity:.25}
      .back-link{display:inline-block;margin:12px 16px;color:inherit;text-decoration:underline;text-underline-offset:2px}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>알림</h1>
            <p>안읽은 알림 <?php echo $unread_count; ?>개</p>
        </div>

        <a href="../index.php" class="back-link">← 홈으로 돌아가기</a>

        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                <p>알림이 없습니다</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notif): ?>
                <div class="notification-item unread">
                    <div class="notification-header">
                        <span class="notification-type type-<?php echo htmlspecialchars($notif['type']); ?>">
                            <?php echo htmlspecialchars($notif['type']); ?>
                        </span>
                        <span class="notification-time">
                            <?php echo date('Y-m-d H:i', strtotime($notif['created_at'])); ?>
                        </span>
                    </div>

                    <div class="notification-title">
                        <?php echo htmlspecialchars($notif['title']); ?>
                    </div>

                    <div class="notification-message">
                        <?php echo nl2br(htmlspecialchars($notif['message'])); ?>
                    </div>

                    <?php if ($notif['link']): ?>
                        <div class="notification-link">
                            <a href="<?php echo htmlspecialchars($notif['link']); ?>">자세히 보기 →</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
