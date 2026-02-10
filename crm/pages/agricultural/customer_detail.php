<?php
/**
 * 농산물 고객 상세
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pdo = getDB();

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: customers.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT c.*, u.name as sales_name
        FROM " . CRM_AGRI_CUSTOMERS_TABLE . " c
        LEFT JOIN " . CRM_USERS_TABLE . " u ON c.assigned_sales = u.id
        WHERE c.id = ?");
    $stmt->execute([$id]);
    $customer = $stmt->fetch();
    if (!$customer) {
        header('Location: customers.php');
        exit;
    }
} catch (Exception $e) {
    header('Location: customers.php');
    exit;
}

$pageTitle = $customer['company_name'];
$pageSubtitle = '고객 상세 정보';

// 활동 이력
try {
    $stmt = $pdo->prepare("SELECT a.*, u.name as user_name
        FROM " . CRM_AGRI_ACTIVITIES_TABLE . " a
        LEFT JOIN " . CRM_USERS_TABLE . " u ON a.created_by = u.id
        WHERE a.customer_id = ?
        ORDER BY a.activity_date DESC LIMIT 20");
    $stmt->execute([$id]);
    $activities = $stmt->fetchAll();
} catch (Exception $e) {
    $activities = [];
}

// 댓글 조회 (활동별)
function getActivityComments($pdo, $activityId) {
    try {
        $stmt = $pdo->prepare("SELECT c.*, u.name as user_name
            FROM crm_agri_activity_comments c
            LEFT JOIN " . CRM_USERS_TABLE . " u ON c.created_by = u.id
            WHERE c.activity_id = ?
            ORDER BY c.created_at ASC");
        $stmt->execute([$activityId]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

$productCategories = json_decode($customer['product_categories'] ?? '[]', true) ?: [];

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<style>
    .detail-container {
        display: flex;
        gap: 20px;
        min-height: calc(100vh - 200px);
    }

    /* 왼쪽 상세 정보 영역 */
    .left-panel {
        flex: 1;
        min-width: 350px;
    }

    .detail-card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }

    .detail-card-inner {
        padding: 24px;
    }

    /* 오른쪽 활동 이력 영역 */
    .right-panel {
        flex: 1;
        background: white;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    /* 상단 탭 메뉴 */
    .tab-menu {
        border-bottom: 2px solid #e9ecef;
        margin-bottom: 20px;
        padding-bottom: 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .right-panel .tab-menu {
        padding: 0 24px;
    }

    .tab-item {
        display: inline-block;
        padding: 12px 20px;
        font-size: 15px;
        font-weight: 600;
        color: #495057;
        border-bottom: 3px solid transparent;
        cursor: pointer;
        margin-bottom: -2px;
    }

    .tab-item.active {
        color: #10b981;
        border-bottom-color: #10b981;
    }

    .detail-toggle-btn, .search-toggle-btn {
        padding: 8px 12px;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        font-size: 13px;
        color: #495057;
        cursor: pointer;
        margin-bottom: -2px;
    }

    .detail-toggle-btn:hover, .search-toggle-btn:hover {
        background: #f8f9fa;
    }

    .detail-sections {
        transition: all 0.3s ease;
    }

    .detail-sections.collapsed {
        display: none;
    }

    /* 액션 버튼 그룹 */
    .action-buttons {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-bottom: 20px;
    }

    .btn-action {
        padding: 10px 24px;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
    }

    .btn-edit {
        background: #fd7e14;
        color: white;
    }

    .btn-edit:hover {
        background: #e8590c;
    }

    .btn-delete {
        background: #dc3545;
        color: white;
    }

    .btn-delete:hover {
        background: #c82333;
    }

    .btn-list {
        background: #10b981;
        color: white;
    }

    .btn-list:hover {
        background: #059669;
    }

    /* 섹션 스타일 */
    .section {
        margin-bottom: 30px;
    }

    .section-title {
        font-size: 16px;
        font-weight: 600;
        color: #10b981;
        margin-bottom: 16px;
        padding-bottom: 8px;
        border-bottom: 2px solid #e9ecef;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }

    .info-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .info-field.full-width {
        grid-column: 1 / -1;
    }

    .field-label {
        font-size: 12px;
        font-weight: 500;
        color: #6c757d;
    }

    .field-value {
        padding: 10px 12px;
        background: #f8f9fa;
        border-radius: 6px;
        font-size: 14px;
        color: #212529;
        min-height: 40px;
        display: flex;
        align-items: center;
    }

    .field-value.empty {
        color: #adb5bd;
        font-style: italic;
    }

    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        background: #d1e7dd;
        color: #0f5132;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 500;
    }

    .status-badge.inactive {
        background: #fee2e2;
        color: #dc2626;
    }

    /* 품목 태그 */
    .category-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        padding: 10px 12px;
        background: #f8f9fa;
        border-radius: 6px;
        min-height: 40px;
    }

    .category-tag {
        background: #d1fae5;
        color: #059669;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 12px;
    }

    /* 검색 영역 */
    .activity-search {
        padding: 12px 16px;
        background: white;
        border-bottom: 1px solid #e9ecef;
        display: none;
    }

    .activity-search.show {
        display: block;
    }

    .search-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px 12px;
        align-items: center;
    }

    .search-field {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .search-label {
        font-size: 12px;
        color: #6c757d;
        min-width: 60px;
        font-weight: 500;
    }

    .search-input {
        flex: 1;
        padding: 6px 10px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        font-size: 13px;
        background: white;
    }

    .search-input:focus {
        outline: none;
        border-color: #10b981;
    }

    .date-range {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .date-range input {
        flex: 1;
        padding: 6px 8px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        font-size: 12px;
    }

    .date-range span {
        color: #adb5bd;
        font-size: 12px;
    }

    .search-actions {
        grid-column: 1 / -1;
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-top: 8px;
    }

    .btn-search {
        padding: 6px 20px;
        border: none;
        border-radius: 4px;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: all .2s;
    }

    .btn-search.primary {
        background: #10b981;
        color: white;
    }

    .btn-search.primary:hover {
        background: #059669;
    }

    .btn-search.secondary {
        background: #6c757d;
        color: white;
    }

    .btn-search.secondary:hover {
        background: #5c636a;
    }

    /* 활동 리스트 스타일 */
    .activity-header {
        display: flex;
        align-items: center;
        padding: 12px 16px;
        background: white;
        border-bottom: 1px solid #e9ecef;
        gap: 8px;
    }

    .filter-select {
        padding: 8px 28px 8px 12px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        font-size: 13px;
        background: white;
        appearance: none;
        min-width: 80px;
    }

    .btn-register {
        padding: 8px 16px;
        background: #10b981;
        color: white;
        border: none;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        white-space: nowrap;
        margin-left: auto;
    }

    .btn-register:hover {
        background: #059669;
    }

    .activity-list {
        padding: 8px 0;
        max-height: calc(100vh - 350px);
        overflow-y: auto;
    }

    .activity-item-wrapper {
        margin-bottom: 10px;
    }

    .activity-item {
        display: flex;
        gap: 12px;
        padding: 14px 16px;
        border-bottom: 1px solid #e9ecef;
        background: white;
        cursor: pointer;
    }

    .activity-item:hover {
        background: #f8f9fa;
    }

    .activity-item.selected {
        background-color: #ecfdf5;
        border-left: 3px solid #10b981;
    }

    .activity-icon {
        width: 32px;
        height: 32px;
        min-width: 32px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        background: #f8f9fa;
    }

    .activity-content {
        flex: 1;
        min-width: 0;
    }

    .activity-content-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 6px;
    }

    .activity-title {
        font-size: 14px;
        font-weight: 500;
        color: #212529;
        line-height: 1.4;
    }

    .activity-date {
        font-size: 12px;
        color: #6c757d;
        white-space: nowrap;
        margin-left: 8px;
    }

    .activity-meta {
        font-size: 12px;
        color: #6c757d;
        line-height: 1.5;
    }

    .activity-meta div {
        margin-bottom: 2px;
    }

    /* 활동 상세 내용 */
    .activity-detail {
        display: none;
        padding: 16px;
        background: #ecfdf5;
        border-top: 2px solid #10b981;
    }

    .activity-item-wrapper.open .activity-detail { display: block; }
    .activity-item-wrapper.open .activity-comments { display: block; }

    .activity-detail-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .activity-detail-field {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .activity-detail-label {
        font-size: 12px;
        font-weight: 600;
        color: #10b981;
    }

    .activity-detail-value {
        padding: 10px 12px;
        background: white;
        border-radius: 6px;
        font-size: 13px;
        color: #212529;
        line-height: 1.6;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .activity-detail-value.empty {
        color: #adb5bd;
        font-style: italic;
    }

    .type-detail-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        margin-top: 8px;
    }

    .type-detail-grid .activity-detail-field.full-width {
        grid-column: 1 / -1;
    }

    @media (max-width: 768px) {
        .type-detail-grid { grid-template-columns: 1fr; }
    }

    /* 댓글 섹션 */
    .activity-comments {
        display: none;
        padding: 16px;
        background-color: #f8f9fa;
        border-top: 1px solid #e9ecef;
    }

    .activity-comments.show {
        display: block;
    }

    .activity-comments-title {
        font-size: 14px;
        font-weight: 600;
        color: #212529;
        margin-bottom: 12px;
    }

    .comment-input-area {
        margin-bottom: 16px;
    }

    .comment-input-area textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        resize: vertical;
        min-height: 60px;
        font-size: 13px;
    }

    .comment-input-area textarea:focus {
        outline: none;
        border-color: #10b981;
    }

    .comment-input-controls {
        display: flex;
        gap: 8px;
        margin-top: 8px;
        align-items: center;
    }

    .image-upload-btn {
        padding: 6px 12px;
        background-color: #6c757d;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
    }

    .image-upload-btn:hover {
        background-color: #5c636a;
    }

    .comment-image-preview {
        margin-top: 8px;
        position: relative;
        display: inline-block;
    }

    .comment-image-preview img {
        max-width: 150px;
        max-height: 100px;
        border-radius: 4px;
        border: 1px solid #dee2e6;
    }

    .comment-image-preview button {
        position: absolute;
        top: -8px;
        right: -8px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #dc3545;
        color: white;
        border: none;
        cursor: pointer;
        font-size: 12px;
        line-height: 1;
    }

    .comment-image {
        margin-top: 8px;
    }

    .comment-image img {
        max-width: 200px;
        max-height: 150px;
        border-radius: 4px;
        cursor: pointer;
    }

    .comment-submit-btn {
        padding: 6px 16px;
        background-color: #10b981;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
    }

    .comment-submit-btn:hover {
        background-color: #059669;
    }

    .comment-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .comment-item {
        background-color: white;
        padding: 12px;
        border-radius: 4px;
        border-left: 3px solid #10b981;
    }

    .comment-item.reply {
        margin-left: 24px;
        border-left-color: #198754;
        background-color: #f0f8ff;
    }

    .comment-item.reply-2 {
        margin-left: 48px;
        border-left-color: #ffc107;
        background-color: #fffef0;
    }

    .comment-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }

    .comment-author {
        font-size: 13px;
        font-weight: 600;
        color: #212529;
    }

    .comment-date-text {
        font-size: 11px;
        color: #6c757d;
    }

    .comment-content {
        font-size: 13px;
        color: #495057;
        margin-bottom: 8px;
        line-height: 1.5;
    }

    .comment-actions {
        display: flex;
        gap: 12px;
    }

    .comment-action-btn {
        background: none;
        border: none;
        color: #10b981;
        cursor: pointer;
        font-size: 11px;
        padding: 0;
    }

    .comment-action-btn:hover {
        text-decoration: underline;
    }

    .comment-reply-area {
        margin-top: 10px;
        padding: 10px;
        background-color: #f8f9fa;
        border-radius: 4px;
        display: none;
    }

    .comment-reply-area.show {
        display: block;
    }

    .comment-reply-area textarea {
        width: 100%;
        padding: 8px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        resize: vertical;
        min-height: 50px;
        font-size: 12px;
    }

    .comment-reply-area textarea:focus {
        outline: none;
        border-color: #10b981;
    }

    .comment-reply-controls {
        display: flex;
        gap: 8px;
        align-items: center;
        margin-top: 8px;
    }

    .comment-reply-submit {
        padding: 5px 12px;
        background-color: #198754;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
    }

    .comment-reply-submit:hover {
        background-color: #157347;
    }

    .comment-reply-cancel {
        padding: 5px 12px;
        background-color: #6c757d;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
    }

    .comment-reply-cancel:hover {
        background-color: #5c636a;
    }

    .empty-state {
        text-align: center;
        color: #999;
        padding: 40px;
    }

    @media (max-width: 1200px) {
        .detail-container {
            flex-direction: column;
        }

        .left-panel,
        .right-panel {
            flex: 0 0 100%;
            max-width: 100%;
            width: 100%;
        }

        .search-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .info-grid {
            grid-template-columns: 1fr;
        }

        .search-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="detail-container">
    <!-- 왼쪽 패널: 고객 상세 정보 -->
    <div class="left-panel">
        <div class="detail-card">
            <div class="detail-card-inner">
                <!-- 상단 탭 메뉴 -->
                <div class="tab-menu">
                    <span class="tab-item active">고객 상세 정보</span>
                    <button class="detail-toggle-btn" id="toggleDetailBtn">상세 정보 접기</button>
                </div>

                <div class="detail-sections">
                    <!-- 기본 정보 섹션 -->
                    <div class="section">
                        <h2 class="section-title">기본 정보</h2>
                        <div class="info-grid">
                            <div class="info-field">
                                <label class="field-label">상호명</label>
                                <div class="field-value"><?= htmlspecialchars($customer['company_name'] ?? '') ?></div>
                            </div>

                            <div class="info-field">
                                <label class="field-label">사업자등록번호</label>
                                <div class="field-value <?= empty($customer['business_number']) ? 'empty' : '' ?>">
                                    <?= !empty($customer['business_number']) ? htmlspecialchars($customer['business_number']) : '(미입력)' ?>
                                </div>
                            </div>

                            <div class="info-field">
                                <label class="field-label">대표자명</label>
                                <div class="field-value <?= empty($customer['representative_name']) ? 'empty' : '' ?>">
                                    <?= !empty($customer['representative_name']) ? htmlspecialchars($customer['representative_name']) : '(미입력)' ?>
                                </div>
                            </div>

                            <div class="info-field">
                                <label class="field-label">전화번호</label>
                                <div class="field-value <?= empty($customer['phone']) ? 'empty' : '' ?>">
                                    <?= !empty($customer['phone']) ? htmlspecialchars($customer['phone']) : '(미입력)' ?>
                                </div>
                            </div>

                            <div class="info-field full-width">
                                <label class="field-label">주소</label>
                                <div class="field-value <?= empty($customer['address']) ? 'empty' : '' ?>">
                                    <?= !empty($customer['address']) ? nl2br(htmlspecialchars($customer['address'])) : '(미입력)' ?>
                                </div>
                            </div>

                            <div class="info-field">
                                <label class="field-label">담당 영업</label>
                                <div class="field-value <?= empty($customer['sales_name']) ? 'empty' : '' ?>">
                                    <?= !empty($customer['sales_name']) ? htmlspecialchars($customer['sales_name']) : '(미배정)' ?>
                                </div>
                            </div>

                            <div class="info-field">
                                <label class="field-label">상태</label>
                                <div class="field-value">
                                    <span class="status-badge <?= $customer['status'] !== 'active' ? 'inactive' : '' ?>">
                                        <?= $customer['status'] === 'active' ? '활성' : '비활성' ?>
                                    </span>
                                </div>
                            </div>

                            <div class="info-field">
                                <label class="field-label">등록일</label>
                                <div class="field-value">
                                    <?= !empty($customer['created_at']) ? date('Y-m-d', strtotime($customer['created_at'])) : '-' ?>
                                </div>
                            </div>

                            <div class="info-field">
                                <label class="field-label">최종 수정일</label>
                                <div class="field-value">
                                    <?= !empty($customer['updated_at']) ? date('Y-m-d', strtotime($customer['updated_at'])) : '-' ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 취급 품목 섹션 -->
                    <?php if (!empty($productCategories)): ?>
                    <div class="section">
                        <h2 class="section-title">취급 품목</h2>
                        <div class="category-tags">
                            <?php foreach ($productCategories as $cat): ?>
                                <span class="category-tag"><?= htmlspecialchars($cat) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- 계좌 정보 섹션 -->
                    <div class="section">
                        <h2 class="section-title">계좌 정보</h2>
                        <div class="info-grid">
                            <div class="info-field">
                                <label class="field-label">은행명</label>
                                <div class="field-value <?= empty($customer['bank_name']) ? 'empty' : '' ?>">
                                    <?= !empty($customer['bank_name']) ? htmlspecialchars($customer['bank_name']) : '(미입력)' ?>
                                </div>
                            </div>

                            <div class="info-field">
                                <label class="field-label">계좌번호</label>
                                <div class="field-value <?= empty($customer['account_number']) ? 'empty' : '' ?>">
                                    <?= !empty($customer['account_number']) ? htmlspecialchars($customer['account_number']) : '(미입력)' ?>
                                </div>
                            </div>

                            <div class="info-field">
                                <label class="field-label">예금주</label>
                                <div class="field-value <?= empty($customer['account_holder']) ? 'empty' : '' ?>">
                                    <?= !empty($customer['account_holder']) ? htmlspecialchars($customer['account_holder']) : '(미입력)' ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 액션 버튼 -->
                    <div class="action-buttons">
                        <a href="customer_form.php?id=<?= $id ?>" class="btn-action btn-edit">수정</a>
                        <button class="btn-action btn-delete" onclick="deleteCustomer()">삭제</button>
                        <a href="customers.php" class="btn-action btn-list">목록</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 오른쪽 패널: 활동 이력 -->
    <div class="right-panel">
        <!-- 검색 탭 메뉴 -->
        <div class="tab-menu">
            <span class="tab-item active">검색</span>
            <button class="search-toggle-btn" id="toggleSearchBtn">검색 옵션 펼치기</button>
        </div>

        <!-- 검색 영역 -->
        <div class="activity-search">
            <div class="search-grid">
                <div class="search-field">
                    <label class="search-label">고객명</label>
                    <input type="text" class="search-input" placeholder="고객명 검색" id="searchCustomer">
                </div>
                <div class="search-field">
                    <label class="search-label">품목</label>
                    <input type="text" class="search-input" placeholder="품목 검색" id="searchProduct">
                </div>
                <div class="search-field">
                    <label class="search-label">지역</label>
                    <input type="text" class="search-input" placeholder="지역 검색" id="searchRegion">
                </div>

                <div class="search-field">
                    <label class="search-label">댓글 작성자</label>
                    <input type="text" class="search-input" placeholder="작성자 검색" id="searchAuthor">
                </div>
                <div class="search-field">
                    <label class="search-label">댓글 내용</label>
                    <input type="text" class="search-input" placeholder="댓글 내용 검색" id="searchComment">
                </div>
                <div class="search-field">
                    <label class="search-label">담당자</label>
                    <input type="text" class="search-input" placeholder="담당자 검색" id="searchManager">
                </div>

                <div class="search-field">
                    <label class="search-label">기간</label>
                    <div class="date-range">
                        <input type="date" class="search-input" id="searchDateFrom">
                        <span>~</span>
                        <input type="date" class="search-input" id="searchDateTo">
                    </div>
                </div>

                <div class="search-actions">
                    <button class="btn-search primary" onclick="searchActivities()">검색</button>
                    <button class="btn-search secondary" onclick="resetSearch()">초기화</button>
                </div>
            </div>
        </div>

        <!-- 헤더 -->
        <div class="activity-header">
            <select class="filter-select" id="sortFilter">
                <option value="date">날짜</option>
                <option value="type">활동 유형</option>
                <option value="manager">담당자</option>
            </select>
            <select class="filter-select" id="typeFilter">
                <option value="">전체</option>
                <option value="영업활동">영업활동</option>
                <option value="미팅">미팅</option>
                <option value="전화">전화</option>
                <option value="이메일">이메일</option>
            </select>
            <button class="btn-register" onclick="location.href='activity_form.php?customer_id=<?= $id ?>'">등록하기</button>
        </div>

        <!-- 활동 목록 -->
        <div class="activity-list">
            <?php if (empty($activities)): ?>
                <div class="empty-state">등록된 활동이 없습니다.</div>
            <?php else: ?>
                <?php foreach ($activities as $activity): ?>
                    <?php
                    $activityId = $activity['id'];
                    $actDetails = !empty($activity['details']) ? (is_string($activity['details']) ? json_decode($activity['details'], true) : $activity['details']) : [];
                    ?>
                    <div class="activity-item-wrapper" id="aw-<?= $activityId ?>"
                         data-date="<?= $activity['activity_date'] ?? '' ?>"
                         data-type="<?= htmlspecialchars($activity['activity_type'] ?? '') ?>"
                         data-description="<?= htmlspecialchars($activity['description'] ?? '') ?>"
                         data-manager="<?= htmlspecialchars($activity['user_name'] ?? '') ?>"
                         data-product="<?= htmlspecialchars($actDetails['product'] ?? ($actDetails['product_type'] ?? '')) ?>"
                         data-region="<?= htmlspecialchars($actDetails['region'] ?? '') ?>">
                        <div class="activity-item" data-activity-id="<?= $activityId ?>" onclick="toggleActivity(<?= $activityId ?>)">
                            <div class="activity-icon">
                                <?php
                                $icons = [
                                    '영업활동' => '📄',
                                    '미팅' => '🎤',
                                    '전화' => '📞',
                                    '이메일' => '📧',
                                    '계약' => '📋',
                                    '매출' => '🚚'
                                ];
                                echo $icons[$activity['activity_type'] ?? ''] ?? '📄';
                                ?>
                            </div>
                            <div class="activity-content">
                                <div class="activity-content-header">
                                    <div class="activity-title"><?= htmlspecialchars($activity['activity_type'] ?? '활동') ?></div>
                                    <span class="activity-date"><?= date('Y.m.d', strtotime($activity['activity_date'])) ?></span>
                                </div>
                                <div class="activity-meta">
                                    <div><?= nl2br(htmlspecialchars($activity['description'] ?? '')) ?></div>
                                    <?php if (!empty($activity['user_name'])): ?>
                                        <div>담당: <?= htmlspecialchars($activity['user_name']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="activity-detail" id="detail-<?= $activityId ?>">
                            <div class="activity-detail-grid">
                                <?php if (!empty($activity['meeting_purpose'])): ?>
                                <div class="activity-detail-field">
                                    <span class="activity-detail-label">미팅목적</span>
                                    <div class="activity-detail-value"><?= nl2br(htmlspecialchars($activity['meeting_purpose'])) ?></div>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($activity['content'])): ?>
                                <div class="activity-detail-field">
                                    <span class="activity-detail-label">내용</span>
                                    <div class="activity-detail-value"><?= nl2br(htmlspecialchars($activity['content'])) ?></div>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($activity['result'])): ?>
                                <div class="activity-detail-field">
                                    <span class="activity-detail-label">결과</span>
                                    <div class="activity-detail-value"><?= nl2br(htmlspecialchars($activity['result'])) ?></div>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($activity['followup'])): ?>
                                <div class="activity-detail-field">
                                    <span class="activity-detail-label">후속조치</span>
                                    <div class="activity-detail-value"><?= nl2br(htmlspecialchars($activity['followup'])) ?></div>
                                </div>
                                <?php endif; ?>

                                <?php
                                $actDetails = null;
                                if (!empty($activity['details'])) {
                                    $actDetails = is_string($activity['details']) ? json_decode($activity['details'], true) : $activity['details'];
                                }
                                if ($actDetails): ?>
                                <div class="activity-detail-field">
                                    <span class="activity-detail-label">유형별 상세</span>
                                    <div class="type-detail-grid">
                                        <?php if (!empty($actDetails['meeting_points'])): ?>
                                        <div class="activity-detail-field full-width">
                                            <span class="activity-detail-label" style="color: #495057;">미팅요점</span>
                                            <div class="activity-detail-value"><?= nl2br(htmlspecialchars($actDetails['meeting_points'])) ?></div>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($actDetails['next_action'])): ?>
                                        <div class="activity-detail-field full-width">
                                            <span class="activity-detail-label" style="color: #495057;">다음액션</span>
                                            <div class="activity-detail-value"><?= nl2br(htmlspecialchars($actDetails['next_action'])) ?></div>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($actDetails['proposal_price'])): ?>
                                        <div class="activity-detail-field">
                                            <span class="activity-detail-label" style="color: #495057;">제안가</span>
                                            <div class="activity-detail-value"><?= htmlspecialchars($actDetails['proposal_price']) ?></div>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($actDetails['proposal_conditions'])): ?>
                                        <div class="activity-detail-field">
                                            <span class="activity-detail-label" style="color: #495057;">조건</span>
                                            <div class="activity-detail-value"><?= nl2br(htmlspecialchars($actDetails['proposal_conditions'])) ?></div>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($actDetails['validity_period'])): ?>
                                        <div class="activity-detail-field">
                                            <span class="activity-detail-label" style="color: #495057;">유효기간</span>
                                            <div class="activity-detail-value"><?= htmlspecialchars($actDetails['validity_period']) ?></div>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($actDetails['quantity'])): ?>
                                        <div class="activity-detail-field">
                                            <span class="activity-detail-label" style="color: #495057;">수량</span>
                                            <div class="activity-detail-value"><?= htmlspecialchars($actDetails['quantity']) ?></div>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($actDetails['unit_price'])): ?>
                                        <div class="activity-detail-field">
                                            <span class="activity-detail-label" style="color: #495057;">단가</span>
                                            <div class="activity-detail-value"><?= htmlspecialchars($actDetails['unit_price']) ?></div>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($actDetails['delivery_date'])): ?>
                                        <div class="activity-detail-field">
                                            <span class="activity-detail-label" style="color: #495057;">납기</span>
                                            <div class="activity-detail-value"><?= htmlspecialchars($actDetails['delivery_date']) ?></div>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($actDetails['payment'])): ?>
                                        <div class="activity-detail-field">
                                            <span class="activity-detail-label" style="color: #495057;">결제</span>
                                            <div class="activity-detail-value"><?= htmlspecialchars($actDetails['payment']) ?></div>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($actDetails['shipping'])): ?>
                                        <div class="activity-detail-field">
                                            <span class="activity-detail-label" style="color: #495057;">배송</span>
                                            <div class="activity-detail-value"><?= htmlspecialchars($actDetails['shipping']) ?></div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if (empty($activity['meeting_purpose']) && empty($activity['content']) && empty($activity['result']) && empty($activity['followup']) && !$actDetails): ?>
                                <div class="activity-detail-field">
                                    <div class="activity-detail-value empty">등록된 상세 내용이 없습니다.</div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="activity-comments" id="comments-<?= $activityId ?>">
                            <div class="activity-comments-title">댓글</div>
                            <div class="comment-input-area">
                                <textarea placeholder="댓글을 입력하세요..." id="comment-text-<?= $activityId ?>"></textarea>
                                <div class="comment-input-controls">
                                    <label class="image-upload-btn">
                                        <input type="file" accept="image/*" id="comment-image-<?= $activityId ?>" onchange="previewCommentImage(<?= $activityId ?>)" style="display:none;">
                                        <span>이미지 첨부</span>
                                    </label>
                                    <button class="comment-submit-btn" onclick="submitComment(<?= $activityId ?>)">댓글 등록</button>
                                </div>
                                <div class="comment-image-preview" id="comment-image-preview-<?= $activityId ?>" style="display:none;">
                                    <img id="comment-image-thumb-<?= $activityId ?>" src="" alt="미리보기">
                                    <button type="button" onclick="removeCommentImage(<?= $activityId ?>)">X</button>
                                </div>
                            </div>
                            <div class="comment-list" id="comment-list-<?= $activityId ?>">
                                <!-- 댓글은 JavaScript로 로드 -->
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleActivity(activityId) {
    var allWrappers = document.querySelectorAll('.activity-item-wrapper');
    var allItems = document.querySelectorAll('.activity-item');
    var target = document.getElementById('aw-' + activityId);
    var isOpen = target && target.classList.contains('open');

    for (var i = 0; i < allItems.length; i++) allItems[i].classList.remove('selected');
    for (var i = 0; i < allWrappers.length; i++) allWrappers[i].classList.remove('open');

    if (!isOpen && target) {
        target.querySelector('.activity-item').classList.add('selected');
        target.classList.add('open');
        if (typeof loadComments === 'function') loadComments(activityId);
    }
}
</script>

<?php
$pageScripts = <<<SCRIPT
<script>
const customerId = {$id};

// 삭제 기능
async function deleteCustomer() {
    if (!confirm('정말 이 고객을 삭제하시겠습니까?')) return;

    try {
        const response = await apiPost(CRM_URL + '/api/agricultural/customers.php', {
            action: 'delete',
            id: customerId
        });
        console.log('Delete response:', response);
        if (response && response.success) {
            showToast('삭제되었습니다.', 'success');
            location.href = 'customers.php';
        } else {
            showToast(response?.message || '삭제에 실패했습니다.', 'error');
        }
    } catch (error) {
        console.error('Delete error:', error);
        showToast('삭제 중 오류가 발생했습니다: ' + error.message, 'error');
    }
}

// 상세 정보 접기/펼치기
(function(){
    const toggleBtn = document.getElementById('toggleDetailBtn');
    const detailSections = document.querySelector('.detail-sections');
    if (toggleBtn && detailSections) {
        toggleBtn.addEventListener('click', function(){
            const isCollapsed = detailSections.classList.contains('collapsed');
            if (isCollapsed) {
                detailSections.classList.remove('collapsed');
                toggleBtn.textContent = '상세 정보 접기';
            } else {
                detailSections.classList.add('collapsed');
                toggleBtn.textContent = '상세 정보 펼치기';
            }
        });
    }
})();

// 검색 옵션 접기/펼치기
(function(){
    const toggleBtn = document.getElementById('toggleSearchBtn');
    const searchPanel = document.querySelector('.activity-search');
    if (toggleBtn && searchPanel) {
        toggleBtn.addEventListener('click', function(){
            const isOpen = searchPanel.classList.contains('show');
            if (isOpen) {
                searchPanel.classList.remove('show');
                toggleBtn.textContent = '검색 옵션 펼치기';
            } else {
                searchPanel.classList.add('show');
                toggleBtn.textContent = '검색 옵션 접기';
            }
        });
    }
})();

// 활동 아이템 클릭은 HTML onclick="toggleActivity(id)"로 처리됨

// 댓글 로드
async function loadComments(activityId) {
    const listEl = document.getElementById('comment-list-' + activityId);
    if (!listEl) return;

    try {
        const response = await apiGet(CRM_URL + '/api/agricultural/comments.php?activity_id=' + activityId);
        if (response.success && response.data) {
            renderComments(listEl, response.data);
        }
    } catch (error) {
        console.log('댓글 로드 실패');
    }
}

// 댓글 렌더링
function renderComments(container, comments) {
    container.innerHTML = '';
    if (!comments || comments.length === 0) {
        container.innerHTML = '<div class="empty-state" style="padding: 20px;">댓글이 없습니다.</div>';
        return;
    }

    comments.forEach(function(comment) {
        const level = comment.depth || 0;
        const levelClass = level === 1 ? 'reply' : (level >= 2 ? 'reply-2' : '');

        let imageHtml = '';
        if (comment.image) {
            imageHtml = '<div class="comment-image"><img src="' + CRM_URL + '/uploads/' + comment.image + '" onclick="window.open(this.src)" alt="첨부이미지"></div>';
        }

        const html = '<div class="comment-item ' + levelClass + '" data-comment-id="' + comment.id + '">' +
            '<div class="comment-header">' +
                '<span class="comment-author">' + (comment.user_name || '익명') + '</span>' +
                '<span class="comment-date-text">' + (comment.created_at || '') + '</span>' +
            '</div>' +
            '<div class="comment-content">' + (comment.content || '').replace(/\\n/g, '<br>') + '</div>' +
            imageHtml +
            '<div class="comment-actions">' +
                '<button class="comment-action-btn reply-btn" onclick="showReplyForm(' + comment.id + ')">답글</button>' +
            '</div>' +
            '<div class="comment-reply-area" id="reply-area-' + comment.id + '">' +
                '<textarea placeholder="답글을 입력하세요..." id="reply-text-' + comment.id + '"></textarea>' +
                '<div class="comment-input-controls">' +
                    '<label class="image-upload-btn">' +
                        '<input type="file" accept="image/*" id="reply-image-' + comment.id + '" onchange="previewReplyImage(' + comment.id + ')" style="display:none;">' +
                        '<span>이미지</span>' +
                    '</label>' +
                    '<button class="comment-reply-submit" onclick="submitReply(' + comment.id + ', ' + comment.activity_id + ')">답글 등록</button>' +
                    '<button class="comment-reply-cancel" onclick="hideReplyForm(' + comment.id + ')">취소</button>' +
                '</div>' +
                '<div class="comment-image-preview" id="reply-image-preview-' + comment.id + '" style="display:none;">' +
                    '<img id="reply-image-thumb-' + comment.id + '" src="" alt="미리보기">' +
                    '<button type="button" onclick="removeReplyImage(' + comment.id + ')">X</button>' +
                '</div>' +
            '</div>' +
        '</div>';

        container.innerHTML += html;
    });
}

// 이미지 미리보기
function previewCommentImage(activityId) {
    const input = document.getElementById('comment-image-' + activityId);
    const preview = document.getElementById('comment-image-preview-' + activityId);
    const thumb = document.getElementById('comment-image-thumb-' + activityId);

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            thumb.src = e.target.result;
            preview.style.display = 'inline-block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function removeCommentImage(activityId) {
    document.getElementById('comment-image-' + activityId).value = '';
    document.getElementById('comment-image-preview-' + activityId).style.display = 'none';
}

function previewReplyImage(commentId) {
    const input = document.getElementById('reply-image-' + commentId);
    const preview = document.getElementById('reply-image-preview-' + commentId);
    const thumb = document.getElementById('reply-image-thumb-' + commentId);

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            thumb.src = e.target.result;
            preview.style.display = 'inline-block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function removeReplyImage(commentId) {
    document.getElementById('reply-image-' + commentId).value = '';
    document.getElementById('reply-image-preview-' + commentId).style.display = 'none';
}

// 답글 폼 표시
function showReplyForm(commentId) {
    document.querySelectorAll('.comment-reply-area').forEach(function(area) {
        area.classList.remove('show');
    });
    const replyArea = document.getElementById('reply-area-' + commentId);
    if (replyArea) {
        replyArea.classList.add('show');
    }
}

// 답글 폼 숨기기
function hideReplyForm(commentId) {
    const replyArea = document.getElementById('reply-area-' + commentId);
    if (replyArea) {
        replyArea.classList.remove('show');
    }
}

// 댓글 등록
async function submitComment(activityId) {
    console.log('submitComment called with activityId:', activityId);
    const textarea = document.getElementById('comment-text-' + activityId);
    const imageInput = document.getElementById('comment-image-' + activityId);
    const content = textarea ? textarea.value.trim() : '';
    const hasImage = imageInput && imageInput.files && imageInput.files[0];

    console.log('content:', content, 'hasImage:', hasImage);

    if (!content && !hasImage) {
        showToast('내용 또는 이미지를 입력해주세요.', 'warning');
        return;
    }

    try {
        const formData = new FormData();
        formData.append('action', 'create');
        formData.append('activity_id', activityId);
        formData.append('content', content);
        if (hasImage) {
            formData.append('image', imageInput.files[0]);
        }

        console.log('Calling apiPostForm...');
        const response = await apiPostForm(CRM_URL + '/api/agricultural/comments.php', formData);
        console.log('Comment response:', response);

        if (response && response.success) {
            textarea.value = '';
            removeCommentImage(activityId);
            loadComments(activityId);
            showToast('댓글이 등록되었습니다.', 'success');
        } else {
            showToast(response?.message || '댓글 등록에 실패했습니다.', 'error');
        }
    } catch (error) {
        console.error('Comment error:', error);
        showToast('댓글 등록 중 오류가 발생했습니다: ' + error.message, 'error');
    }
}

// 답글 등록
async function submitReply(parentId, activityId) {
    const textarea = document.getElementById('reply-text-' + parentId);
    const imageInput = document.getElementById('reply-image-' + parentId);
    const content = textarea.value.trim();
    const hasImage = imageInput && imageInput.files && imageInput.files[0];

    if (!content && !hasImage) {
        showToast('내용 또는 이미지를 입력해주세요.', 'warning');
        return;
    }

    try {
        const formData = new FormData();
        formData.append('action', 'create');
        formData.append('activity_id', activityId);
        formData.append('parent_id', parentId);
        formData.append('content', content);
        if (hasImage) {
            formData.append('image', imageInput.files[0]);
        }

        const response = await apiPostForm(CRM_URL + '/api/agricultural/comments.php', formData);

        if (response.success) {
            textarea.value = '';
            removeReplyImage(parentId);
            hideReplyForm(parentId);
            loadComments(activityId);
            showToast('답글이 등록되었습니다.', 'success');
        } else {
            showToast(response.message || '답글 등록에 실패했습니다.', 'error');
        }
    } catch (error) {
        showToast('답글 등록 중 오류가 발생했습니다.', 'error');
    }
}

// 검색 기능
function searchActivities() {
    const searchProduct = (document.getElementById('searchProduct')?.value || '').toLowerCase();
    const searchRegion = (document.getElementById('searchRegion')?.value || '').toLowerCase();
    const searchManager = (document.getElementById('searchManager')?.value || '').toLowerCase();
    const searchDateFrom = document.getElementById('searchDateFrom')?.value || '';
    const searchDateTo = document.getElementById('searchDateTo')?.value || '';

    const wrappers = document.querySelectorAll('.activity-item-wrapper');
    let visibleCount = 0;

    wrappers.forEach(function(wrapper) {
        const date = wrapper.dataset.date || '';
        const description = (wrapper.dataset.description || '').toLowerCase();
        const manager = (wrapper.dataset.manager || '').toLowerCase();
        const product = (wrapper.dataset.product || '').toLowerCase();
        const region = (wrapper.dataset.region || '').toLowerCase();

        let show = true;

        // 품목 검색
        if (searchProduct && !product.includes(searchProduct) && !description.includes(searchProduct)) {
            show = false;
        }

        // 지역 검색
        if (searchRegion && !region.includes(searchRegion) && !description.includes(searchRegion)) {
            show = false;
        }

        // 담당자 검색
        if (searchManager && !manager.includes(searchManager)) {
            show = false;
        }

        // 기간 검색
        if (searchDateFrom && date < searchDateFrom) {
            show = false;
        }
        if (searchDateTo && date > searchDateTo) {
            show = false;
        }

        wrapper.style.display = show ? '' : 'none';
        if (show) visibleCount++;
    });

    showToast(visibleCount + '개의 활동이 검색되었습니다.', 'info');
}

function resetSearch() {
    document.querySelectorAll('.search-input').forEach(function(input) {
        input.value = '';
    });

    // 모든 활동 다시 표시
    document.querySelectorAll('.activity-item-wrapper').forEach(function(wrapper) {
        wrapper.style.display = '';
    });

    showToast('검색이 초기화되었습니다.', 'info');
}
</script>
SCRIPT;

include dirname(dirname(__DIR__)) . '/includes/footer.php';
?>
