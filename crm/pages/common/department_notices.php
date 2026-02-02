<?php
/**
 * 부서 공지
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pageTitle = '부서 공지';
$pageSubtitle = '부서별 공지사항';

$pdo = getDB();

// 필터
$partFilter = $_GET['part'] ?? '';
$priorityFilter = $_GET['priority'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;

$where = ["1=1"];
$params = [];

if ($partFilter) {
    $where[] = "part = ?";
    $params[] = $partFilter;
}
if ($priorityFilter) {
    $where[] = "priority = ?";
    $params[] = $priorityFilter;
}
if ($search) {
    $where[] = "(title LIKE ? OR content LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereClause = implode(' AND ', $where);

// 총 개수
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM " . CRM_DEPT_NOTICES_TABLE . " WHERE {$whereClause}");
    $stmt->execute($params);
    $totalCount = $stmt->fetchColumn();
} catch (Exception $e) {
    $totalCount = 0;
}

$totalPages = ceil($totalCount / $perPage);
$offset = ($page - 1) * $perPage;

// 목록 조회
try {
    $stmt = $pdo->prepare("SELECT n.*, u.name as author_name
        FROM " . CRM_DEPT_NOTICES_TABLE . " n
        LEFT JOIN " . CRM_USERS_TABLE . " u ON n.created_by = u.id
        WHERE {$whereClause}
        ORDER BY n.priority = 'important' DESC, n.created_at DESC
        LIMIT {$perPage} OFFSET {$offset}");
    $stmt->execute($params);
    $notices = $stmt->fetchAll();
} catch (Exception $e) {
    $notices = [];
}

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

.container { max-width: 1000px; margin: 0 auto; padding: 20px; }

.page-header { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; }
.btn-back { padding: 8px 16px; border: 1px solid #dee2e6; border-radius: 4px; background: white; color: #495057; cursor: pointer; font-size: 14px; text-decoration: none; }
.btn-back:hover { background: #f8f9fa; }
.page-title { font-size: 24px; font-weight: 600; color: #212529; }
.page-info { font-size: 14px; color: #6c757d; }
.btn-write { margin-left: auto; padding: 8px 16px; background: #0d6efd; color: white; border: none; border-radius: 4px; font-size: 14px; font-weight: 500; cursor: pointer; text-decoration: none; }
.btn-write:hover { background: #0b5ed7; }

.filter-bar { background: white; padding: 16px 24px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
.filter-select { padding: 8px 12px; border: 1px solid #dee2e6; border-radius: 4px; font-size: 14px; background: white; }

.part-menu { display: flex; gap: 8px; flex-wrap: wrap; }
.part-btn { padding: 8px 12px; border: 1px solid #dee2e6; background: white; color: #495057; border-radius: 999px; font-size: 13px; cursor: pointer; text-decoration: none; }
.part-btn.active { background: #0d6efd; color: #fff; border-color: #0d6efd; }

.search-box { flex: 1; min-width: 200px; display: flex; gap: 8px; }
.search-input { flex: 1; padding: 8px 12px; border: 1px solid #dee2e6; border-radius: 4px; font-size: 14px; }
.btn-search { padding: 8px 16px; background: #0d6efd; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }

.notice-list { background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; }
.notice-item { border-bottom: 1px solid #e9ecef; transition: background 0.2s; }
.notice-item:last-child { border-bottom: none; }
.notice-item:hover { background: #f8f9fa; }

.notice-link { display: block; padding: 20px 24px; text-decoration: none; color: inherit; }
.notice-header { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }

.badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: 600; }
.badge-important { background: #dc3545; color: white; }
.badge-new { background: #0d6efd; color: white; }
.badge-normal { background: #6c757d; color: white; }
.badge-part { background: #e9ecef; color: #495057; }

.notice-title { font-size: 16px; font-weight: 500; color: #212529; line-height: 1.4; flex: 1; }
.notice-content { font-size: 14px; color: #6c757d; margin-bottom: 8px; line-height: 1.5; }
.notice-meta { display: flex; gap: 16px; font-size: 13px; color: #adb5bd; }

.empty-state { text-align: center; padding: 60px 20px; color: #6c757d; background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }

.pagination { display: flex; justify-content: center; gap: 8px; margin-top: 24px; }
.page-btn { padding: 8px 12px; border: 1px solid #dee2e6; border-radius: 4px; background: white; color: #495057; cursor: pointer; font-size: 14px; min-width: 40px; text-decoration: none; text-align: center; }
.page-btn:hover { background: #f8f9fa; }
.page-btn.active { background: #0d6efd; color: white; border-color: #0d6efd; }
.page-btn.disabled { opacity: 0.5; cursor: not-allowed; }

@media (max-width: 768px) {
    .page-header { flex-wrap: wrap; }
    .filter-bar { padding: 12px 16px; }
    .notice-link { padding: 16px; }
    .notice-meta { flex-direction: column; gap: 4px; }
}
</style>

<div class="container">
    <div class="page-header">
        <a href="<?= CRM_URL ?>/pages/main.php" class="btn-back">← 뒤로</a>
        <div class="page-title">부서 공지</div>
        <div class="page-info">총 <strong><?= number_format($totalCount) ?></strong>건</div>
        <a href="department_notice_form.php" class="btn-write">+ 글쓰기</a>
    </div>

    <form class="filter-bar" method="GET">
        <select class="filter-select" name="priority" onchange="this.form.submit()">
            <option value="">전체</option>
            <option value="important" <?= $priorityFilter === 'important' ? 'selected' : '' ?>>중요</option>
            <option value="normal" <?= $priorityFilter === 'normal' ? 'selected' : '' ?>>일반</option>
        </select>

        <div class="part-menu">
            <a href="?<?= http_build_query(array_merge($_GET, ['part' => ''])) ?>" class="part-btn <?= !$partFilter ? 'active' : '' ?>">전체</a>
            <a href="?<?= http_build_query(array_merge($_GET, ['part' => '국제물류'])) ?>" class="part-btn <?= $partFilter === '국제물류' ? 'active' : '' ?>">국제물류</a>
            <a href="?<?= http_build_query(array_merge($_GET, ['part' => '농산물'])) ?>" class="part-btn <?= $partFilter === '농산물' ? 'active' : '' ?>">농산물</a>
            <a href="?<?= http_build_query(array_merge($_GET, ['part' => '우드펠렛'])) ?>" class="part-btn <?= $partFilter === '우드펠렛' ? 'active' : '' ?>">우드펠렛</a>
            <a href="?<?= http_build_query(array_merge($_GET, ['part' => '물류'])) ?>" class="part-btn <?= $partFilter === '물류' ? 'active' : '' ?>">물류</a>
        </div>

        <div class="search-box">
            <input type="text" class="search-input" name="search" placeholder="제목 또는 내용으로 검색" value="<?= h($search) ?>">
            <button type="submit" class="btn-search">검색</button>
        </div>
    </form>

    <?php if (empty($notices)): ?>
        <div class="empty-state">
            <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;">📋</div>
            <p>등록된 공지가 없습니다.</p>
        </div>
    <?php else: ?>
        <div class="notice-list">
            <?php foreach ($notices as $notice): ?>
                <div class="notice-item">
                    <a href="department_notice_form.php?id=<?= $notice['id'] ?>" class="notice-link">
                        <div class="notice-header">
                            <?php if ($notice['priority'] === 'important'): ?>
                                <span class="badge badge-important">중요</span>
                            <?php else: ?>
                                <span class="badge badge-normal">일반</span>
                            <?php endif; ?>
                            <?php if (strtotime($notice['created_at']) > strtotime('-3 days')): ?>
                                <span class="badge badge-new">NEW</span>
                            <?php endif; ?>
                            <?php if ($notice['part']): ?>
                                <span class="badge badge-part"><?= h($notice['part']) ?></span>
                            <?php endif; ?>
                            <div class="notice-title"><?= h($notice['title']) ?></div>
                        </div>
                        <div class="notice-content"><?= h(mb_substr($notice['content'] ?? '', 0, 100)) ?>...</div>
                        <div class="notice-meta">
                            <span><?= formatDate($notice['created_at'], 'Y.m.d') ?></span>
                            <span><?= h($notice['author_name'] ?? '관리자') ?></span>
                            <span>조회 <?= number_format($notice['view_count'] ?? 0) ?></span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="page-btn">◀</a>
                <?php endif; ?>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="page-btn <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="page-btn">▶</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
