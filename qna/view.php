<?php
/**
 * QnA 게시글 상세 페이지
 */
require_once 'config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// 조회수 증가
$stmt = $pdo->prepare("UPDATE qna SET view_count = view_count + 1 WHERE id = ?");
$stmt->execute([$id]);

// 게시글 조회
$stmt = $pdo->prepare("
    SELECT *,
           DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') as created_date,
           DATE_FORMAT(updated_at, '%Y-%m-%d %H:%i') as updated_date
    FROM qna
    WHERE id = ?
");
$stmt->execute([$id]);
$qna = $stmt->fetch();

if (!$qna) {
    $_SESSION['error'] = '존재하지 않는 게시글입니다.';
    header('Location: index.php');
    exit;
}

// 답변 조회
$stmt = $pdo->prepare("
    SELECT *,
           DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') as created_date
    FROM qna_answers
    WHERE qna_id = ?
    ORDER BY created_at ASC
");
$stmt->execute([$id]);
$answers = $stmt->fetchAll();

// 작성자 확인
$is_writer = isLoggedIn() && ($_SESSION['username'] === $qna['writer_id']);
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escape($qna['title']) ?> - Q&A 게시판</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans KR', sans-serif;
            background: #f5f5f5;
            line-height: 1.6;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 1rem;
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .post-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .post-header {
            padding: 2rem;
            border-bottom: 2px solid #e5e7eb;
        }

        .post-category {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            background: #dbeafe;
            color: #1e40af;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .post-title {
            font-size: 1.8rem;
            color: #1f2937;
            margin-bottom: 1rem;
        }

        .post-meta {
            display: flex;
            gap: 1.5rem;
            color: #6b7280;
            font-size: 0.9rem;
            flex-wrap: wrap;
        }

        .post-meta span {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .post-content {
            padding: 2rem;
            min-height: 200px;
            line-height: 1.8;
            color: #374151;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .post-footer {
            padding: 1.5rem 2rem;
            background: #f8fafc;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .button-group {
            display: flex;
            gap: 0.5rem;
        }

        .btn {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .btn-danger {
            background: #dc2626;
            color: white;
        }

        .btn-danger:hover {
            background: #b91c1c;
        }

        .answer-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .answer-header {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .answer-item {
            background: #f0f9ff;
            border-left: 4px solid #2563eb;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }

        .answer-admin {
            background: #fef3c7;
            border-left-color: #f59e0b;
        }

        .answer-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 0.85rem;
            color: #6b7280;
        }

        .answer-author {
            font-weight: 600;
            color: #2563eb;
        }

        .answer-author-admin {
            color: #f59e0b;
        }

        .answer-content {
            line-height: 1.8;
            color: #374151;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .no-answer {
            text-align: center;
            padding: 2rem;
            color: #6b7280;
        }

        .answer-form {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 4px;
            margin-top: 1.5rem;
        }

        .answer-form textarea {
            width: 100%;
            min-height: 150px;
            padding: 1rem;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            resize: vertical;
            font-family: inherit;
        }

        .answer-form textarea:focus {
            outline: none;
            border-color: #2563eb;
        }

        .status-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-answered {
            background: #d1fae5;
            color: #065f46;
        }

        .status-waiting {
            background: #fef3c7;
            color: #92400e;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .post-header, .post-content {
                padding: 1.5rem;
            }

            .post-title {
                font-size: 1.3rem;
            }

            .post-footer {
                flex-direction: column;
                align-items: stretch;
            }

            .button-group {
                width: 100%;
                justify-content: space-between;
            }

            .btn {
                flex: 1;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">
            <i class="fas fa-arrow-left"></i> 목록으로
        </a>

        <div class="post-container">
            <div class="post-header">
                <span class="post-category"><?= escape($qna['category']) ?></span>
                <h1 class="post-title"><?= escape($qna['title']) ?></h1>
                <div class="post-meta">
                    <span><i class="fas fa-user"></i> <?= escape($qna['writer']) ?></span>
                    <span><i class="fas fa-calendar"></i> <?= $qna['created_date'] ?></span>
                    <span><i class="fas fa-eye"></i> <?= number_format($qna['view_count']) ?></span>
                    <span>
                        <?php if ($qna['is_answered']): ?>
                            <span class="status-badge status-answered">답변완료</span>
                        <?php else: ?>
                            <span class="status-badge status-waiting">답변대기</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>

            <div class="post-content">
                <?= escape($qna['content']) ?>
            </div>

            <div class="post-footer">
                <div class="button-group">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-list"></i> 목록
                    </a>
                </div>
                <?php if ($is_writer): ?>
                    <div class="button-group">
                        <a href="write.php?id=<?= $qna['id'] ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> 수정
                        </a>
                        <button onclick="deletePost()" class="btn btn-danger">
                            <i class="fas fa-trash"></i> 삭제
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="answer-section">
            <div class="answer-header">
                <i class="fas fa-comments"></i>
                답변 (<?= count($answers) ?>)
            </div>

            <?php if (empty($answers)): ?>
                <div class="no-answer">
                    <i class="fas fa-comment-slash" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.3;"></i>
                    <p>아직 답변이 없습니다. 관리자가 확인 후 답변드립니다.</p>
                </div>
            <?php else: ?>
                <?php foreach ($answers as $answer): ?>
                    <div class="answer-item <?= $answer['is_admin'] ? 'answer-admin' : '' ?>">
                        <div class="answer-meta">
                            <span class="<?= $answer['is_admin'] ? 'answer-author-admin' : 'answer-author' ?>">
                                <?= $answer['is_admin'] ? '관리자' : escape($answer['writer']) ?>
                            </span>
                            <span><?= $answer['created_date'] ?></span>
                        </div>
                        <div class="answer-content">
                            <?= escape($answer['content']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (isAdmin()): ?>
                <div class="answer-form">
                    <form method="POST" action="process.php" onsubmit="return validateAnswer()">
                        <input type="hidden" name="action" value="answer">
                        <input type="hidden" name="qna_id" value="<?= $qna['id'] ?>">
                        <textarea name="content" id="answer_content" placeholder="답변 내용을 입력하세요" required></textarea>
                        <div style="text-align: right; margin-top: 1rem;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-reply"></i> 답변 등록
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function deletePost() {
            if (confirm('정말 삭제하시겠습니까?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'process.php';

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete';

                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = '<?= $qna['id'] ?>';

                form.appendChild(actionInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function validateAnswer() {
            const content = document.getElementById('answer_content').value.trim();
            if (!content) {
                alert('답변 내용을 입력해주세요.');
                return false;
            }
            if (content.length < 10) {
                alert('답변은 10자 이상 입력해주세요.');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>
