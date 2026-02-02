<?php
/**
 * QnA 게시판 처리 로직
 */
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'write':
            handleWrite();
            break;
        case 'edit':
            handleEdit();
            break;
        case 'delete':
            handleDelete();
            break;
        case 'answer':
            handleAnswer();
            break;
        default:
            throw new Exception('잘못된 요청입니다.');
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: index.php');
    exit;
}

/**
 * 게시글 작성
 */
function handleWrite() {
    global $pdo;

    if (!isLoggedIn()) {
        throw new Exception('로그인이 필요합니다.');
    }

    $user_id = $_SESSION['username'];
    $user_name = $_SESSION['name'] ?? $_SESSION['username'];
    $category = $_POST['category'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $email = trim($_POST['email'] ?? '');

    // 유효성 검사
    if (empty($category)) {
        throw new Exception('카테고리를 선택해주세요.');
    }
    if (empty($title) || mb_strlen($title) < 2) {
        throw new Exception('제목은 2자 이상 입력해주세요.');
    }
    if (empty($content) || mb_strlen($content) < 10) {
        throw new Exception('내용은 10자 이상 입력해주세요.');
    }

    $stmt = $pdo->prepare("
        INSERT INTO qna (writer_id, writer, category, title, content, email, status, is_answered, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, 'active', 0, NOW(), NOW())
    ");

    if ($stmt->execute([$user_id, $user_name, $category, $title, $content, $email])) {
        $_SESSION['success'] = '게시글이 등록되었습니다.';
        header('Location: index.php');
    } else {
        throw new Exception('게시글 등록에 실패했습니다.');
    }
}

/**
 * 게시글 수정
 */
function handleEdit() {
    global $pdo;

    if (!isLoggedIn()) {
        throw new Exception('로그인이 필요합니다.');
    }

    $id = intval($_POST['id'] ?? 0);
    $user_id = $_SESSION['username'];
    $category = $_POST['category'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $email = trim($_POST['email'] ?? '');

    // 권한 확인
    $stmt = $pdo->prepare("SELECT writer_id FROM qna WHERE id = ?");
    $stmt->execute([$id]);
    $post = $stmt->fetch();

    if (!$post || $post['writer_id'] !== $user_id) {
        throw new Exception('수정 권한이 없습니다.');
    }

    // 유효성 검사
    if (empty($category)) {
        throw new Exception('카테고리를 선택해주세요.');
    }
    if (empty($title) || mb_strlen($title) < 2) {
        throw new Exception('제목은 2자 이상 입력해주세요.');
    }
    if (empty($content) || mb_strlen($content) < 10) {
        throw new Exception('내용은 10자 이상 입력해주세요.');
    }

    $stmt = $pdo->prepare("
        UPDATE qna
        SET category = ?, title = ?, content = ?, email = ?, updated_at = NOW()
        WHERE id = ? AND writer_id = ?
    ");

    if ($stmt->execute([$category, $title, $content, $email, $id, $user_id])) {
        $_SESSION['success'] = '게시글이 수정되었습니다.';
        header('Location: view.php?id=' . $id);
    } else {
        throw new Exception('게시글 수정에 실패했습니다.');
    }
}

/**
 * 게시글 삭제
 */
function handleDelete() {
    global $pdo;

    if (!isLoggedIn()) {
        throw new Exception('로그인이 필요합니다.');
    }

    $id = intval($_POST['id'] ?? 0);
    $user_id = $_SESSION['username'];

    // 권한 확인
    $stmt = $pdo->prepare("SELECT writer_id FROM qna WHERE id = ?");
    $stmt->execute([$id]);
    $post = $stmt->fetch();

    if (!$post || ($post['writer_id'] !== $user_id && !isAdmin())) {
        throw new Exception('삭제 권한이 없습니다.');
    }

    // 답변도 함께 삭제
    $pdo->prepare("DELETE FROM qna_answers WHERE qna_id = ?")->execute([$id]);

    $stmt = $pdo->prepare("DELETE FROM qna WHERE id = ?");

    if ($stmt->execute([$id])) {
        $_SESSION['success'] = '게시글이 삭제되었습니다.';
        header('Location: index.php');
    } else {
        throw new Exception('게시글 삭제에 실패했습니다.');
    }
}

/**
 * 답변 등록
 */
function handleAnswer() {
    global $pdo;

    if (!isAdmin()) {
        throw new Exception('답변 권한이 없습니다.');
    }

    $qna_id = intval($_POST['qna_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');
    $user_id = $_SESSION['username'];
    $user_name = $_SESSION['name'] ?? '관리자';

    // 유효성 검사
    if (empty($content) || mb_strlen($content) < 10) {
        throw new Exception('답변은 10자 이상 입력해주세요.');
    }

    // 답변 등록
    $stmt = $pdo->prepare("
        INSERT INTO qna_answers (qna_id, writer_id, writer, content, is_admin, created_at)
        VALUES (?, ?, ?, ?, 1, NOW())
    ");

    if (!$stmt->execute([$qna_id, $user_id, $user_name, $content])) {
        throw new Exception('답변 등록에 실패했습니다.');
    }

    // QnA 게시글 답변 완료 상태로 업데이트
    $stmt = $pdo->prepare("UPDATE qna SET is_answered = 1, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$qna_id]);

    $_SESSION['success'] = '답변이 등록되었습니다.';
    header('Location: view.php?id=' . $qna_id);
}
?>
