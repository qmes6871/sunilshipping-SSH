<?php
/**
 * 알림 시스템 테스트 파일
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'notification_helper.php';

echo "<h1>알림 시스템 테스트</h1>";

// 1. 테이블 존재 확인
echo "<h2>1. 테이블 확인</h2>";
try {
    $pdo = new PDO(
        "mysql:host=" . G5_MYSQL_HOST . ";dbname=" . G5_MYSQL_DB . ";charset=utf8mb4",
        G5_MYSQL_USER,
        G5_MYSQL_PASSWORD,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    if ($stmt->rowCount() > 0) {
        echo "✅ notifications 테이블 존재<br>";
    } else {
        echo "❌ notifications 테이블 없음<br>";
    }

    $stmt = $pdo->query("SHOW TABLES LIKE 'user_notifications'");
    if ($stmt->rowCount() > 0) {
        echo "✅ user_notifications 테이블 존재<br>";
    } else {
        echo "❌ user_notifications 테이블 없음<br>";
    }

} catch (PDOException $e) {
    echo "❌ DB 연결 실패: " . $e->getMessage() . "<br>";
}

// 2. 활성 회원 수 확인
echo "<h2>2. 활성 회원 수</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM customer_management WHERE status = 'active'");
    $result = $stmt->fetch();
    echo "활성 회원 수: " . $result['count'] . "명<br>";

    if ($result['count'] == 0) {
        echo "⚠️ 활성 회원이 없습니다!<br>";
    }

    // 회원 목록 출력
    $stmt = $pdo->query("SELECT username, name, status FROM customer_management LIMIT 5");
    echo "<br>샘플 회원 목록:<br>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Username</th><th>Name</th><th>Status</th></tr>";
    while ($user = $stmt->fetch()) {
        echo "<tr><td>{$user['username']}</td><td>{$user['name']}</td><td>{$user['status']}</td></tr>";
    }
    echo "</table>";

} catch (PDOException $e) {
    echo "❌ 회원 조회 실패: " . $e->getMessage() . "<br>";
}

// 3. 테스트 알림 생성
echo "<h2>3. 테스트 알림 생성</h2>";
$result = createNotificationForAllUsers(
    'test',
    '테스트 알림',
    '이것은 테스트 알림입니다.',
    '/test.php',
    999
);

if ($result['success']) {
    echo "✅ 알림 생성 성공<br>";
    echo "- 알림 ID: " . $result['notification_id'] . "<br>";
    echo "- 전송된 회원 수: " . $result['users_count'] . "명<br>";
} else {
    echo "❌ 알림 생성 실패<br>";
    echo "- 오류: " . ($result['error'] ?? '알 수 없음') . "<br>";
}

// 4. 생성된 알림 확인
echo "<h2>4. 생성된 알림 확인</h2>";
try {
    $stmt = $pdo->query("SELECT * FROM notifications ORDER BY id DESC LIMIT 5");
    echo "최근 알림 목록:<br>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Type</th><th>Title</th><th>Message</th><th>Created</th></tr>";
    while ($notif = $stmt->fetch()) {
        echo "<tr><td>{$notif['id']}</td><td>{$notif['type']}</td><td>{$notif['title']}</td><td>{$notif['message']}</td><td>{$notif['created_at']}</td></tr>";
    }
    echo "</table>";
} catch (PDOException $e) {
    echo "❌ 알림 조회 실패: " . $e->getMessage() . "<br>";
}

// 5. 사용자별 알림 확인
echo "<h2>5. 사용자별 알림 확인</h2>";
try {
    $stmt = $pdo->query("SELECT * FROM user_notifications ORDER BY id DESC LIMIT 10");
    echo "최근 사용자 알림 목록:<br>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Notification ID</th><th>Username</th><th>Is Read</th><th>Created</th></tr>";
    while ($un = $stmt->fetch()) {
        echo "<tr><td>{$un['id']}</td><td>{$un['notification_id']}</td><td>{$un['username']}</td><td>{$un['is_read']}</td><td>{$un['created_at']}</td></tr>";
    }
    echo "</table>";
} catch (PDOException $e) {
    echo "❌ 사용자 알림 조회 실패: " . $e->getMessage() . "<br>";
}

echo "<br><br><a href='index.php'>홈으로 돌아가기</a>";
