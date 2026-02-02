<?php
/**
 * QnA 게시판 목록 페이지
 */
require_once 'config.php';

// 데이터베이스 테이블 확인 및 데이터 조회
$qna_list = [];
$total_count = 0;
$total_pages = 1;
$db_error = false;

try {
    // 페이지 번호
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $offset = ($page - 1) * ITEMS_PER_PAGE;

    // 검색 조건
    $search_keyword = $_GET['search'] ?? '';
    $search_type = $_GET['search_type'] ?? 'all';

    // 쿼리 작성
    $where = "WHERE 1=1";
    $params = [];

    if (!empty($search_keyword)) {
        if ($search_type === 'title') {
            $where .= " AND title LIKE :keyword";
            $params[':keyword'] = '%' . $search_keyword . '%';
        } elseif ($search_type === 'content') {
            $where .= " AND content LIKE :keyword";
            $params[':keyword'] = '%' . $search_keyword . '%';
        } elseif ($search_type === 'writer') {
            $where .= " AND writer LIKE :keyword";
            $params[':keyword'] = '%' . $search_keyword . '%';
        } else {
            $where .= " AND (title LIKE :keyword OR content LIKE :keyword OR writer LIKE :keyword)";
            $params[':keyword'] = '%' . $search_keyword . '%';
        }
    }

    // 전체 게시글 수
    $count_sql = "SELECT COUNT(*) as total FROM qna $where";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_count = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_count / ITEMS_PER_PAGE);

    // 게시글 목록 조회
    $sql = "SELECT id, title, writer, view_count, status, is_answered,
                   DATE_FORMAT(created_at, '%Y-%m-%d') as created_date
            FROM qna
            $where
            ORDER BY id DESC
            LIMIT :offset, :limit";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', ITEMS_PER_PAGE, PDO::PARAM_INT);
    $stmt->execute();
    $qna_list = $stmt->fetchAll();
} catch (PDOException $e) {
    $db_error = true;
    error_log("QnA Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Q&A 게시판 - SUNIL SHIPPING</title>
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
            max-width: 1200px;
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
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .header p {
            opacity: 0.9;
        }

        .stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .stats-info {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .stats-info strong {
            color: #2563eb;
            font-size: 1.1rem;
        }

        .search-box {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .search-form {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .search-form select,
        .search-form input {
            padding: 0.7rem;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .search-form select {
            min-width: 120px;
        }

        .search-form input[type="text"] {
            flex: 1;
            min-width: 200px;
        }

        .search-form button {
            padding: 0.7rem 1.5rem;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s;
        }

        .search-form button:hover {
            background: #1d4ed8;
        }

        .qna-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8fafc;
            border-bottom: 2px solid #e5e7eb;
        }

        th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 0.9rem;
        }

        th:first-child {
            width: 80px;
            text-align: center;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
        }

        td:first-child {
            text-align: center;
            color: #6b7280;
            font-weight: 500;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        .title-cell {
            flex: 1;
        }

        .title-link {
            color: #1f2937;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .title-link:hover {
            color: #2563eb;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.6rem;
            border-radius: 12px;
            font-size: 0.75rem;
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

        .write-btn {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: background 0.2s;
        }

        .write-btn:hover {
            background: #1d4ed8;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .pagination a,
        .pagination span {
            padding: 0.5rem 0.8rem;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            text-decoration: none;
            color: #374151;
            transition: all 0.2s;
        }

        .pagination a:hover {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
        }

        .pagination .current {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
            font-weight: 600;
        }

        .empty-message {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }

        .empty-message i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        .back-btn {
            display: inline-block;
            padding: 0.7rem 1.2rem;
            background: #6b7280;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            margin-right: 1rem;
        }

        .back-btn:hover {
            background: #4b5563;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .header h1 {
                font-size: 1.5rem;
            }

            .search-form {
                flex-direction: column;
            }

            .search-form select,
            .search-form input[type="text"],
            .search-form button {
                width: 100%;
            }

            table {
                font-size: 0.85rem;
            }

            th, td {
                padding: 0.7rem 0.5rem;
            }

            th:nth-child(3),
            th:nth-child(4),
            td:nth-child(3),
            td:nth-child(4) {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-question-circle"></i> Q&A 게시판</h1>
            <p>궁금한 사항을 질문해주세요. 빠르게 답변드리겠습니다.</p>
        </div>

        <div class="stats">
            <div class="stats-info">
                전체 <strong><?= number_format($total_count) ?></strong>건
            </div>
            <div>
                <a href="../index.php" class="back-btn"><i class="fas fa-home"></i> 홈으로</a>
                <?php if (isLoggedIn()): ?>
                    <a href="write.php" class="write-btn"><i class="fas fa-pen"></i> 글쓰기</a>
                <?php else: ?>
                    <a href="../login/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="write-btn">
                        <i class="fas fa-sign-in-alt"></i> 로그인하고 글쓰기
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="search-box">
            <form method="GET" class="search-form">
                <select name="search_type">
                    <option value="all" <?= $search_type === 'all' ? 'selected' : '' ?>>전체</option>
                    <option value="title" <?= $search_type === 'title' ? 'selected' : '' ?>>제목</option>
                    <option value="content" <?= $search_type === 'content' ? 'selected' : '' ?>>내용</option>
                    <option value="writer" <?= $search_type === 'writer' ? 'selected' : '' ?>>작성자</option>
                </select>
                <input type="text" name="search" placeholder="검색어를 입력하세요" value="<?= escape($search_keyword) ?>">
                <button type="submit"><i class="fas fa-search"></i> 검색</button>
            </form>
        </div>

        <div class="qna-table">
            <?php if ($db_error): ?>
                <div class="empty-message">
                    <i class="fas fa-exclamation-triangle" style="color: #dc2626;"></i>
                    <h3>데이터베이스 오류</h3>
                    <p>게시판 테이블이 생성되지 않았습니다.</p>
                    <p style="margin-top: 1rem; font-size: 0.9rem; color: #6b7280;">
                        <strong>setup.sql</strong> 파일을 실행하여 테이블을 생성해주세요.
                    </p>
                    <div style="margin-top: 1.5rem;">
                        <code style="background: #f3f4f6; padding: 0.5rem 1rem; border-radius: 4px; display: inline-block;">
                            mysql -u sunilshipping -p sunilshipping < setup.sql
                        </code>
                    </div>
                </div>
            <?php elseif (empty($qna_list)): ?>
                <div class="empty-message">
                    <i class="fas fa-inbox"></i>
                    <h3>등록된 게시글이 없습니다</h3>
                    <p>첫 번째 질문을 남겨주세요!</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>번호</th>
                            <th>제목</th>
                            <th>작성자</th>
                            <th>조회</th>
                            <th>상태</th>
                            <th>작성일</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($qna_list as $qna): ?>
                            <tr>
                                <td><?= $qna['id'] ?></td>
                                <td>
                                    <a href="view.php?id=<?= $qna['id'] ?>" class="title-link">
                                        <?= escape($qna['title']) ?>
                                        <?php if ($qna['is_answered']): ?>
                                            <i class="fas fa-check-circle" style="color: #10b981;"></i>
                                        <?php endif; ?>
                                    </a>
                                </td>
                                <td><?= escape($qna['writer']) ?></td>
                                <td><?= number_format($qna['view_count']) ?></td>
                                <td>
                                    <?php if ($qna['is_answered']): ?>
                                        <span class="status-badge status-answered">답변완료</span>
                                    <?php else: ?>
                                        <span class="status-badge status-waiting">대기중</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $qna['created_date'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- 페이지네이션 -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search_keyword) ?>&search_type=<?= $search_type ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>

                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?page=<?= $i ?>&search=<?= urlencode($search_keyword) ?>&search_type=<?= $search_type ?>">
                                    <?= $i ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search_keyword) ?>&search_type=<?= $search_type ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
