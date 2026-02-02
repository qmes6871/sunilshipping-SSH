<?php
/**
 * QnA 게시글 작성 페이지
 */
require_once 'config.php';

// 로그인 체크
if (!isLoggedIn()) {
    $_SESSION['error'] = '로그인이 필요합니다.';
    header('Location: ../login/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user_id = $_SESSION['username'];
$user_name = $_SESSION['name'] ?? $_SESSION['username'];

// 수정 모드 확인
$edit_mode = false;
$qna = null;

if (isset($_GET['id'])) {
    $edit_mode = true;
    $id = intval($_GET['id']);

    $stmt = $pdo->prepare("SELECT * FROM qna WHERE id = ? AND writer_id = ?");
    $stmt->execute([$id, $user_id]);
    $qna = $stmt->fetch();

    if (!$qna) {
        $_SESSION['error'] = '권한이 없거나 존재하지 않는 게시글입니다.';
        header('Location: index.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $edit_mode ? '게시글 수정' : '게시글 작성' ?> - Q&A 게시판</title>
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

        .header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            padding: 2rem;
            margin-bottom: 2rem;
            border-radius: 8px;
            text-align: center;
        }

        .header h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }

        .form-group label .required {
            color: #dc2626;
        }

        .form-group input[type="text"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 0.95rem;
            font-family: inherit;
        }

        .form-group input[type="text"]:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #2563eb;
        }

        .form-group textarea {
            min-height: 300px;
            resize: vertical;
        }

        .form-info {
            background: #f0f9ff;
            border-left: 4px solid #2563eb;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
            color: #1e40af;
            font-size: 0.9rem;
        }

        .button-group {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
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

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .header h1 {
                font-size: 1.5rem;
            }

            .form-container {
                padding: 1.5rem;
            }

            .button-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-pen"></i> <?= $edit_mode ? '게시글 수정' : '게시글 작성' ?></h1>
            <p>궁금한 사항을 자유롭게 질문해주세요</p>
        </div>

        <div class="form-container">
            <div class="form-info">
                <i class="fas fa-info-circle"></i>
                작성하신 내용은 관리자가 확인 후 답변드립니다. 답변은 이메일로도 발송됩니다.
            </div>

            <form method="POST" action="process.php" onsubmit="return validateForm()">
                <?php if ($edit_mode): ?>
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?= $qna['id'] ?>">
                <?php else: ?>
                    <input type="hidden" name="action" value="write">
                <?php endif; ?>

                <div class="form-group">
                    <label for="category">
                        카테고리 <span class="required">*</span>
                    </label>
                    <select name="category" id="category" required>
                        <option value="">선택하세요</option>
                        <option value="shipping" <?= ($qna['category'] ?? '') === 'shipping' ? 'selected' : '' ?>>운송 문의</option>
                        <option value="payment" <?= ($qna['category'] ?? '') === 'payment' ? 'selected' : '' ?>>결제 문의</option>
                        <option value="tracking" <?= ($qna['category'] ?? '') === 'tracking' ? 'selected' : '' ?>>트래킹 문의</option>
                        <option value="document" <?= ($qna['category'] ?? '') === 'document' ? 'selected' : '' ?>>서류 문의</option>
                        <option value="other" <?= ($qna['category'] ?? '') === 'other' ? 'selected' : '' ?>>기타 문의</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="title">
                        제목 <span class="required">*</span>
                    </label>
                    <input type="text"
                           name="title"
                           id="title"
                           placeholder="제목을 입력하세요"
                           value="<?= escape($qna['title'] ?? '') ?>"
                           required
                           maxlength="200">
                </div>

                <div class="form-group">
                    <label for="content">
                        내용 <span class="required">*</span>
                    </label>
                    <textarea name="content"
                              id="content"
                              placeholder="질문 내용을 자세히 입력해주세요"
                              required><?= escape($qna['content'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="email">
                        답변 받을 이메일
                    </label>
                    <input type="text"
                           name="email"
                           id="email"
                           placeholder="답변을 받을 이메일 주소 (선택사항)"
                           value="<?= escape($qna['email'] ?? '') ?>">
                </div>

                <div class="button-group">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> 취소
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> <?= $edit_mode ? '수정' : '등록' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function validateForm() {
            const category = document.getElementById('category').value;
            const title = document.getElementById('title').value.trim();
            const content = document.getElementById('content').value.trim();

            if (!category) {
                alert('카테고리를 선택해주세요.');
                return false;
            }

            if (!title) {
                alert('제목을 입력해주세요.');
                return false;
            }

            if (title.length < 2) {
                alert('제목은 2자 이상 입력해주세요.');
                return false;
            }

            if (!content) {
                alert('내용을 입력해주세요.');
                return false;
            }

            if (content.length < 10) {
                alert('내용은 10자 이상 입력해주세요.');
                return false;
            }

            return true;
        }
    </script>
</body>
</html>
