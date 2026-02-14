<?php
/**
 * 국제물류 바이어 상세
 * other/2.2.1 국제물류 바이어 상세.html 기반
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pdo = getDB();
$customerId = intval($_GET['id'] ?? 0);

if (!$customerId) {
    header('Location: ' . CRM_URL . '/pages/international/dashboard.php');
    exit;
}

// 고객 정보 조회
try {
    $stmt = $pdo->prepare("SELECT c.*, u.name as sales_name
        FROM " . CRM_INTL_CUSTOMERS_TABLE . " c
        LEFT JOIN " . CRM_USERS_TABLE . " u ON c.assigned_sales = u.id
        WHERE c.id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch();

    if (!$customer) {
        header('Location: ' . CRM_URL . '/pages/international/dashboard.php');
        exit;
    }
} catch (Exception $e) {
    header('Location: ' . CRM_URL . '/pages/international/dashboard.php');
    exit;
}

// 활동 이력 조회
try {
    $stmt = $pdo->prepare("SELECT a.*, u.name as author_name
        FROM " . CRM_INTL_ACTIVITIES_TABLE . " a
        LEFT JOIN " . CRM_USERS_TABLE . " u ON a.created_by = u.id
        WHERE a.customer_id = ?
        ORDER BY a.activity_date DESC, a.created_at DESC");
    $stmt->execute([$customerId]);
    $activities = $stmt->fetchAll();
} catch (Exception $e) {
    $activities = [];
}

// 댓글 조회 (활동별로 그룹화)
$activityComments = [];
try {
    $activityIds = array_column($activities, 'id');
    if (!empty($activityIds)) {
        $placeholders = implode(',', array_fill(0, count($activityIds), '?'));
        $stmt = $pdo->prepare("SELECT c.*, u.name as author_name
            FROM " . CRM_COMMENTS_TABLE . " c
            LEFT JOIN " . CRM_USERS_TABLE . " u ON c.created_by = u.id
            WHERE c.entity_type = 'intl_activity' AND c.entity_id IN ({$placeholders}) AND (c.is_deleted = 0 OR c.is_deleted IS NULL)
            ORDER BY c.created_at ASC");
        $stmt->execute($activityIds);
        while ($row = $stmt->fetch()) {
            $activityComments[$row['entity_id']][] = $row;
        }
    }
} catch (Exception $e) {
    // ignore
}

$pageTitle = '바이어 상세 정보';

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<style>
    .detail-container {
        display: flex;
        gap: 20px;
        min-height: calc(100vh - 200px);
    }

    .left-panel {
        flex: 0 0 40%;
        max-width: 40%;
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

    .right-panel {
        flex: 0 0 60%;
        max-width: 60%;
        background: white;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .tab-menu {
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .right-panel .tab-menu {
        padding: 0 24px;
        padding-top: 16px;
    }

    .tab-item {
        display: inline-block;
        padding: 12px 20px;
        font-size: 15px;
        font-weight: 600;
        color: #495057;
        border-bottom: 3px solid transparent;
        cursor: pointer;
    }

    .tab-item.active {
        color: #0d6efd;
        border-bottom-color: #0d6efd;
    }

    .action-buttons-top {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-bottom: 30px;
    }

    .btn-estimate { background: #fd7e14; color: white; }
    .btn-estimate:hover { background: #e8590c; }
    .btn-delete { background: #dc3545; color: white; }
    .btn-delete:hover { background: #c82333; }
    .btn-list { background: #0d6efd; color: white; }
    .btn-list:hover { background: #0b5ed7; }

    .section { margin-bottom: 40px; }
    .section-title {
        font-size: 18px;
        font-weight: 600;
        color: #0d6efd;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e9ecef;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }

    .info-field {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .info-field.full-width { grid-column: 1 / -1; }

    .field-label {
        font-size: 13px;
        font-weight: 500;
        color: #6c757d;
    }

    .field-value {
        padding: 12px;
        background: #f8f9fa;
        border-radius: 6px;
        font-size: 14px;
        color: #212529;
        min-height: 44px;
        display: flex;
        align-items: center;
    }

    .field-value.empty {
        color: #adb5bd;
        font-style: italic;
    }

    .detail-toggle-btn, .search-toggle-btn {
        padding: 8px 12px;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        font-size: 13px;
        color: #495057;
        cursor: pointer;
    }

    .detail-sections.collapsed { display: none; }

    .activity-header {
        display: flex;
        align-items: center;
        padding: 12px 16px;
        background: white;
        border-bottom: 1px solid #e9ecef;
        gap: 8px;
    }

    .activity-search {
        padding: 12px 16px;
        background: white;
        border-bottom: 1px solid #e9ecef;
    }

    .search-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px 12px;
    }

    .search-field {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .search-label { font-size: 12px; color: #6c757d; }

    .search-input, .search-date {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        font-size: 13px;
        background: #fff;
    }

    .date-range {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    .search-actions {
        display: flex;
        justify-content: flex-end;
        margin-top: 10px;
        gap: 8px;
    }

    .filter-select {
        padding: 8px 28px 8px 12px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        font-size: 13px;
        background: white;
    }

    .btn-register {
        padding: 8px 16px;
        background: #0d6efd;
        color: white;
        border: none;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        margin-left: auto;
        text-decoration: none;
    }

    .activity-list {
        padding: 8px 0;
        max-height: 600px;
        overflow-y: auto;
    }

    .activity-item-wrapper { border-bottom: 1px solid #e9ecef; }

    .activity-item {
        display: flex;
        gap: 12px;
        padding: 14px 16px;
        background: white;
        cursor: pointer;
        transition: all 0.2s;
    }

    .activity-item:hover { background: #f8f9fa; }
    .activity-item.selected {
        background: #e7f1ff;
        border-left: 4px solid #0d6efd;
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

    .activity-content { flex: 1; min-width: 0; }

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
    }

    .activity-date {
        font-size: 12px;
        color: #6c757d;
        white-space: nowrap;
    }

    .activity-header-right {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .activity-delete-btn {
        width: 20px;
        height: 20px;
        border: none;
        background: #dc3545;
        color: white;
        border-radius: 50%;
        font-size: 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        opacity: 0.7;
        transition: opacity 0.2s;
    }

    .activity-delete-btn:hover {
        opacity: 1;
    }

    .activity-meta {
        font-size: 12px;
        color: #6c757d;
        line-height: 1.5;
    }

    .activity-amount {
        font-size: 13px;
        font-weight: 500;
        color: #212529;
        margin-top: 2px;
    }

    .badge-new {
        display: inline-block;
        padding: 1px 6px;
        background: #dc3545;
        color: white;
        border-radius: 3px;
        font-size: 11px;
        margin-left: 4px;
    }

    /* 활동 상세 내용 */
    .activity-detail {
        display: none;
        padding: 16px;
        background: #f0f4ff;
        border-top: 2px solid #0d6efd;
    }

    .activity-detail.show { display: block; }

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
        color: #0d6efd;
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

    .booking-detail-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        margin-top: 8px;
    }

    .booking-detail-grid .activity-detail-field.full-width {
        grid-column: 1 / -1;
    }

    @media (max-width: 768px) {
        .booking-detail-grid { grid-template-columns: 1fr; }
    }

    .activity-comments {
        display: none;
        padding: 16px;
        background: #f8f9fa;
        border-top: 2px solid #dee2e6;
    }

    .activity-comments.show { display: block; }

    .activity-comments-title {
        font-size: 14px;
        font-weight: 600;
        color: #495057;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 1px solid #dee2e6;
    }

    .comment-input-area { margin-bottom: 16px; }

    .comment-input-area textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        resize: vertical;
        min-height: 60px;
        font-size: 13px;
    }

    .comment-input-controls {
        display: flex;
        gap: 8px;
        align-items: center;
        margin-top: 8px;
    }

    .comment-image-upload { position: relative; display: inline-block; }
    .comment-image-upload input[type="file"] { display: none; }

    .comment-image-upload-btn {
        padding: 6px 12px;
        background-color: #6c757d;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 13px;
    }

    .comment-submit-btn {
        padding: 6px 16px;
        background-color: #0d6efd;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 500;
    }

    .comment-list { margin-top: 12px; }

    .comment-item {
        margin-bottom: 12px;
        padding: 10px;
        background-color: white;
        border-radius: 6px;
        border-left: 3px solid #0d6efd;
    }

    .comment-item.reply {
        margin-left: 24px;
        border-left-color: #198754;
        background-color: #f0f8ff;
    }

    .comment-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 6px;
    }

    .comment-author { font-weight: 600; color: #212529; font-size: 13px; }
    .comment-date { color: #6c757d; font-size: 11px; }
    .comment-content { color: #495057; line-height: 1.5; font-size: 13px; margin-bottom: 6px; }

    .comment-image { margin-top: 8px; margin-bottom: 8px; }
    .comment-image img {
        max-width: 100%;
        max-height: 300px;
        border-radius: 6px;
        border: 1px solid #dee2e6;
        cursor: pointer;
    }

    .comment-actions { display: flex; gap: 10px; }

    .comment-action-btn {
        background: none;
        border: none;
        color: #0d6efd;
        cursor: pointer;
        font-size: 11px;
        padding: 0;
    }

    .comment-action-btn.edit-btn { color: #198754; }
    .comment-action-btn.delete-btn { color: #dc3545; }

    .comment-edit-area {
        margin-top: 10px;
        display: none;
    }

    .comment-edit-area.show { display: block; }

    .comment-edit-area textarea {
        width: 100%;
        padding: 8px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        resize: vertical;
        min-height: 50px;
        font-size: 12px;
    }

    .comment-edit-controls {
        display: flex;
        gap: 8px;
        align-items: center;
        margin-top: 8px;
    }

    .comment-edit-save {
        padding: 5px 12px;
        background-color: #198754;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
    }

    .comment-edit-cancel {
        padding: 5px 12px;
        background-color: #6c757d;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
    }

    .comment-badge {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 10px;
        font-size: 10px;
        margin-left: 6px;
    }

    .comment-badge-reply { background-color: #198754; color: white; }

    .comment-reply-area {
        margin-top: 10px;
        padding: 10px;
        background-color: #f8f9fa;
        border-radius: 4px;
        display: none;
    }

    .comment-reply-area.show { display: block; }

    .comment-reply-area textarea {
        width: 100%;
        padding: 8px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        resize: vertical;
        min-height: 50px;
        font-size: 12px;
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

    .comment-reply-cancel {
        padding: 5px 12px;
        background-color: #6c757d;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
    }

    .comment-image-preview {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 10px;
        background: #e7f3ff;
        border-radius: 4px;
        font-size: 12px;
        color: #0d6efd;
    }

    .comment-image-preview .selected-file-name {
        flex: 1;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .comment-image-preview .remove-image-btn {
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        font-size: 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }

    @media (max-width: 1200px) {
        .detail-container { flex-direction: column; }
        .left-panel, .right-panel { flex: 0 0 100%; max-width: 100%; }
    }

    @media (max-width: 768px) {
        .info-grid { grid-template-columns: 1fr; }
        .search-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="detail-container">
    <!-- 왼쪽 패널: 바이어 상세 정보 -->
    <div class="left-panel">
        <div class="detail-card">
            <div class="detail-card-inner">
                <div class="tab-menu">
                    <span class="tab-item active">바이어 상세 정보</span>
                    <button class="detail-toggle-btn" id="toggleDetailBtn">상세 정보 접기</button>
                </div>

                <div class="detail-sections" id="detailSections">
                    <div class="section">
                        <h2 class="section-title">기본 정보</h2>
                        <div class="info-grid">
                            <div class="info-field">
                                <label class="field-label">회사명</label>
                                <div class="field-value <?= empty($customer['name']) ? 'empty' : '' ?>">
                                    <?= h($customer['name'] ?? '미입력') ?>
                                </div>
                            </div>
                            <div class="info-field">
                                <label class="field-label">담당자명</label>
                                <div class="field-value <?= empty($customer['name']) ? 'empty' : '' ?>">
                                    <?= h($customer['name'] ?: '미입력') ?>
                                </div>
                            </div>
                            <div class="info-field">
                                <label class="field-label">전화번호</label>
                                <div class="field-value <?= empty($customer['phone']) ? 'empty' : '' ?>">
                                    <?= h($customer['phone'] ?: '미입력') ?>
                                </div>
                            </div>
                            <div class="info-field">
                                <label class="field-label">이메일</label>
                                <div class="field-value <?= empty($customer['email']) ? 'empty' : '' ?>">
                                    <?= h($customer['email'] ?: '미입력') ?>
                                </div>
                            </div>
                            <div class="info-field">
                                <label class="field-label">국가</label>
                                <div class="field-value <?= empty($customer['nationality']) ? 'empty' : '' ?>">
                                    <?= h($customer['nationality'] ?: '미입력') ?>
                                </div>
                            </div>
                            <div class="info-field">
                                <label class="field-label">고객유형</label>
                                <div class="field-value <?= empty($customer['customer_type']) ? 'empty' : '' ?>">
                                    <?= h($customer['customer_type'] ?: '미입력') ?>
                                </div>
                            </div>
                            <div class="info-field">
                                <label class="field-label">왓츠앱</label>
                                <div class="field-value <?= empty($customer['whatsapp']) ? 'empty' : '' ?>">
                                    <?= h($customer['whatsapp'] ?: '미입력') ?>
                                </div>
                            </div>
                            <div class="info-field">
                                <label class="field-label">최종 수출국</label>
                                <div class="field-value <?= empty($customer['export_country']) ? 'empty' : '' ?>">
                                    <?= h($customer['export_country'] ?: '미입력') ?>
                                </div>
                            </div>
                            <div class="info-field full-width">
                                <label class="field-label">주소</label>
                                <div class="field-value <?= empty($customer['address']) ? 'empty' : '' ?>">
                                    <?= h($customer['address'] ?: '미입력') ?>
                                </div>
                            </div>
                            <div class="info-field">
                                <label class="field-label">등록일</label>
                                <div class="field-value">
                                    <?= $customer['created_at'] ? date('Y-m-d', strtotime($customer['created_at'])) : '-' ?>
                                </div>
                            </div>
                            <div class="info-field">
                                <label class="field-label">최종 수정일</label>
                                <div class="field-value">
                                    <?= $customer['updated_at'] ? date('Y-m-d', strtotime($customer['updated_at'])) : '-' ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="section">
                        <h2 class="section-title">계좌 정보</h2>
                        <div class="info-grid">
                            <div class="info-field">
                                <label class="field-label">은행명</label>
                                <div class="field-value <?= empty($customer['bank_name']) ? 'empty' : '' ?>">
                                    <?= h($customer['bank_name'] ?: '미입력') ?>
                                </div>
                            </div>
                            <div class="info-field">
                                <label class="field-label">계좌번호</label>
                                <div class="field-value <?= empty($customer['account_number']) ? 'empty' : '' ?>">
                                    <?= h($customer['account_number'] ?: '미입력') ?>
                                </div>
                            </div>
                            <div class="info-field">
                                <label class="field-label">예금주</label>
                                <div class="field-value <?= empty($customer['account_holder']) ? 'empty' : '' ?>">
                                    <?= h($customer['account_holder'] ?: '미입력') ?>
                                </div>
                            </div>
                            <div class="info-field">
                                <label class="field-label">SWIFT 코드</label>
                                <div class="field-value <?= empty($customer['swift_code']) ? 'empty' : '' ?>">
                                    <?= h($customer['swift_code'] ?: '미입력') ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="action-buttons-top">
                        <a href="<?= CRM_URL ?>/pages/international/customer_form.php?id=<?= $customerId ?>" class="btn btn-estimate">수정</a>
                        <button class="btn btn-delete" onclick="deleteCustomer(<?= $customerId ?>)">삭제</button>
                        <a href="<?= CRM_URL ?>/pages/international/dashboard.php" class="btn btn-list">목록</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 오른쪽 패널: 활동 이력 -->
    <div class="right-panel">
        <div class="tab-menu">
            <span class="tab-item active">활동 이력</span>
            <button class="search-toggle-btn" id="toggleSearchBtn">검색 옵션 접기</button>
        </div>

        <div class="activity-search" id="activitySearch">
            <div class="search-grid">
                <div class="search-field">
                    <label class="search-label">활동 유형</label>
                    <select class="search-input" id="qActivityType">
                        <option value="">전체</option>
                        <option value="영업활동">영업활동</option>
                        <option value="계약">계약</option>
                        <option value="견적">견적</option>
                        <option value="매출">매출</option>
                    </select>
                </div>
                <div class="search-field">
                    <label class="search-label">담당자</label>
                    <input type="text" class="search-input" id="qManager" placeholder="담당자명">
                </div>
                <div class="search-field">
                    <label class="search-label">기간</label>
                    <div class="date-range">
                        <input type="date" class="search-date" id="qFromDate">
                        <input type="date" class="search-date" id="qToDate">
                    </div>
                </div>
            </div>
            <div class="search-actions">
                <button class="btn btn-sm btn-outline" onclick="resetActivitySearch()">초기화</button>
                <button class="btn btn-sm btn-primary" onclick="performActivitySearch()">검색</button>
            </div>
        </div>

        <div class="activity-header">
            <select class="filter-select" id="sortSelect">
                <option value="date">날짜순</option>
                <option value="type">유형순</option>
            </select>
            <a href="<?= CRM_URL ?>/pages/international/activity_form.php?customer_id=<?= $customerId ?>" class="btn-register">등록하기</a>
        </div>

        <div class="activity-list" id="activityList">
            <?php if (empty($activities)): ?>
            <div style="padding: 40px; text-align: center; color: #999;">
                등록된 활동 이력이 없습니다.
            </div>
            <?php else: ?>
                <?php foreach ($activities as $activity):
                    $comments = $activityComments[$activity['id']] ?? [];
                    $activityType = $activity['activity_type'] ?? '';
                    $activityTypeLabel = getActivityTypeLabel($activityType);
                    $iconMap = [
                        '영업활동' => '📄', 'sales' => '📄',
                        '계약' => '🎤', 'contract' => '🎤',
                        '견적' => '📋', 'quotation' => '📋',
                        '매출' => '🚚', 'sale' => '🚚',
                        '영업기회등록' => '📊',
                        '미팅' => '🎤', 'meeting' => '🎤',
                        '전화' => '📞', 'call' => '📞', 'phone' => '📞',
                        '이메일' => '📧', 'email' => '📧',
                        '제안' => '💼', 'proposal' => '💼',
                        '방문' => '🚗', 'visit' => '🚗',
                        '문의' => '❓', 'inquiry' => '❓',
                    ];
                    $icon = $iconMap[$activityType] ?? $iconMap[strtolower($activityType)] ?? '📄';
                    $isActivityOwner = ($activity['created_by'] == $currentUser['crm_user_id']);
                ?>
                <div class="activity-item-wrapper">
                    <div class="activity-item" data-activity-id="<?= $activity['id'] ?>">
                        <div class="activity-icon"><?= $icon ?></div>
                        <div class="activity-content">
                            <div class="activity-content-header">
                                <div class="activity-title">
                                    <?= h($activityTypeLabel) ?>
                                    <?php if (strtotime($activity['created_at']) > strtotime('-3 days')): ?>
                                    <span class="badge-new">N</span>
                                    <?php endif; ?>
                                </div>
                                <div class="activity-header-right">
                                    <span class="activity-date"><?= date('Y.m.d', strtotime($activity['activity_date'])) ?></span>
                                    <?php if ($isActivityOwner): ?>
                                    <button class="activity-delete-btn" onclick="event.stopPropagation(); deleteActivity(<?= $activity['id'] ?>)" title="삭제">×</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="activity-meta">
                                <div><?= h($activity['title'] ?? $activity['activity_content'] ?? '') ?></div>
                                <div><?= h($activity['author_name'] ?? '') ?></div>
                            </div>
                            <?php if (!empty($activity['amount'])): ?>
                            <div class="activity-amount">KRW <?= number_format($activity['amount']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="activity-detail" id="detail-<?= $activity['id'] ?>">
                        <div class="activity-detail-grid">
                            <?php if (!empty($activity['meeting_purpose'])): ?>
                            <div class="activity-detail-field">
                                <span class="activity-detail-label">미팅목적</span>
                                <div class="activity-detail-value"><?= nl2br(h($activity['meeting_purpose'])) ?></div>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($activity['activity_content'])): ?>
                            <div class="activity-detail-field">
                                <span class="activity-detail-label">내용</span>
                                <div class="activity-detail-value"><?= nl2br(h($activity['activity_content'])) ?></div>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($activity['activity_result'])): ?>
                            <div class="activity-detail-field">
                                <span class="activity-detail-label">결과</span>
                                <div class="activity-detail-value"><?= nl2br(h($activity['activity_result'])) ?></div>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($activity['followup_items'])): ?>
                            <div class="activity-detail-field">
                                <span class="activity-detail-label">후속조치</span>
                                <div class="activity-detail-value"><?= nl2br(h($activity['followup_items'])) ?></div>
                            </div>
                            <?php endif; ?>

                            <?php
                            // 부킹완료 상세 정보
                            $bookingDetails = null;
                            if (!empty($activity['details'])) {
                                $bookingDetails = json_decode($activity['details'], true);
                            }
                            if ($activity['activity_type'] === 'booking_completed' && $bookingDetails): ?>
                            <div class="activity-detail-field">
                                <span class="activity-detail-label">부킹 상세 정보</span>
                                <div class="booking-detail-grid">
                                    <?php if (!empty($bookingDetails['buyer_name'])): ?>
                                    <div class="activity-detail-field">
                                        <span class="activity-detail-label" style="color: #495057;">바이어</span>
                                        <div class="activity-detail-value"><?= h($bookingDetails['buyer_name']) ?></div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($bookingDetails['destination'])): ?>
                                    <div class="activity-detail-field">
                                        <span class="activity-detail-label" style="color: #495057;">목적지</span>
                                        <div class="activity-detail-value"><?= h($bookingDetails['destination']) ?></div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($bookingDetails['container_type'])): ?>
                                    <div class="activity-detail-field">
                                        <span class="activity-detail-label" style="color: #495057;">컨테이너</span>
                                        <div class="activity-detail-value"><?= h($bookingDetails['container_type']) ?></div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($bookingDetails['loading_place'])): ?>
                                    <div class="activity-detail-field">
                                        <span class="activity-detail-label" style="color: #495057;">쇼링장/DOOR</span>
                                        <div class="activity-detail-value"><?= h($bookingDetails['loading_place']) ?></div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($bookingDetails['expected_date'])): ?>
                                    <div class="activity-detail-field">
                                        <span class="activity-detail-label" style="color: #495057;">작업 예상일</span>
                                        <div class="activity-detail-value"><?= h($bookingDetails['expected_date']) ?></div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($bookingDetails['freight_offer'])): ?>
                                    <div class="activity-detail-field">
                                        <span class="activity-detail-label" style="color: #495057;">운임 오퍼</span>
                                        <div class="activity-detail-value"><?= h($bookingDetails['freight_offer']) ?></div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($bookingDetails['cargo_items'])): ?>
                                    <div class="activity-detail-field full-width">
                                        <span class="activity-detail-label" style="color: #495057;">적입 아이템</span>
                                        <div class="activity-detail-value"><?= nl2br(h($bookingDetails['cargo_items'])) ?></div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($bookingDetails['booking_notes'])): ?>
                                    <div class="activity-detail-field full-width">
                                        <span class="activity-detail-label" style="color: #495057;">기타</span>
                                        <div class="activity-detail-value"><?= nl2br(h($bookingDetails['booking_notes'])) ?></div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if (empty($activity['meeting_purpose']) && empty($activity['activity_content']) && empty($activity['activity_result']) && empty($activity['followup_items']) && !$bookingDetails): ?>
                            <div class="activity-detail-field">
                                <div class="activity-detail-value empty">등록된 상세 내용이 없습니다.</div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="activity-comments" id="comments-<?= $activity['id'] ?>">
                        <div class="activity-comments-title">댓글 (<?= count($comments) ?>)</div>
                        <div class="comment-input-area">
                            <textarea placeholder="댓글을 입력하세요..." id="commentText-<?= $activity['id'] ?>"></textarea>
                            <div class="comment-image-preview" id="imagePreview-<?= $activity['id'] ?>" style="display: none; margin-top: 8px;">
                                <span class="selected-file-name"></span>
                                <button type="button" class="remove-image-btn" onclick="removeImage(<?= $activity['id'] ?>)">&times;</button>
                            </div>
                            <div class="comment-input-controls">
                                <div class="comment-image-upload">
                                    <input type="file" accept="image/*" id="commentImage-<?= $activity['id'] ?>" onchange="showImagePreview(<?= $activity['id'] ?>, this)">
                                    <label for="commentImage-<?= $activity['id'] ?>" class="comment-image-upload-btn">이미지 첨부</label>
                                </div>
                                <button class="comment-submit-btn" onclick="submitComment(<?= $activity['id'] ?>)">댓글 등록</button>
                            </div>
                        </div>
                        <div class="comment-list" id="commentList-<?= $activity['id'] ?>">
                            <?php foreach ($comments as $comment):
                                $replyClass = !empty($comment['parent_id']) ? 'reply' : '';
                                $isOwner = ($comment['created_by'] == $currentUser['crm_user_id']);
                            ?>
                            <div class="comment-item <?= $replyClass ?>" data-comment-id="<?= $comment['id'] ?>">
                                <div class="comment-header">
                                    <span class="comment-author">
                                        <?= h($comment['author_name'] ?? $comment['user_name'] ?? '익명') ?>
                                        <?php if ($replyClass): ?>
                                        <span class="comment-badge comment-badge-reply">대댓글</span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="comment-date"><?= date('Y.m.d H:i', strtotime($comment['created_at'])) ?></span>
                                </div>
                                <div class="comment-content" id="commentContent-<?= $comment['id'] ?>"><?= nl2br(h($comment['content'])) ?></div>
                                <?php if (!empty($comment['image'])): ?>
                                <div class="comment-image">
                                    <img src="<?= CRM_UPLOAD_URL ?>/<?= h($comment['image']) ?>" alt="" onclick="window.open(this.src, '_blank')">
                                </div>
                                <?php endif; ?>
                                <div class="comment-actions">
                                    <?php if (empty($comment['parent_id'])): ?>
                                    <button class="comment-action-btn" onclick="toggleReplyForm(<?= $comment['id'] ?>)">답글</button>
                                    <?php endif; ?>
                                    <?php if ($isOwner): ?>
                                    <button class="comment-action-btn edit-btn" onclick="toggleEditForm(<?= $comment['id'] ?>, '<?= addslashes(h($comment['content'])) ?>')">수정</button>
                                    <button class="comment-action-btn delete-btn" onclick="deleteComment(<?= $comment['id'] ?>)">삭제</button>
                                    <?php endif; ?>
                                </div>
                                <div class="comment-edit-area" id="editArea-<?= $comment['id'] ?>">
                                    <textarea id="editText-<?= $comment['id'] ?>"></textarea>
                                    <div class="comment-edit-controls">
                                        <button class="comment-edit-save" onclick="saveComment(<?= $comment['id'] ?>)">저장</button>
                                        <button class="comment-edit-cancel" onclick="toggleEditForm(<?= $comment['id'] ?>)">취소</button>
                                    </div>
                                </div>
                                <?php if (empty($comment['parent_id'])): ?>
                                <div class="comment-reply-area" id="replyArea-<?= $comment['id'] ?>">
                                    <textarea placeholder="답글을 입력하세요..." id="replyText-<?= $comment['id'] ?>"></textarea>
                                    <div class="comment-image-preview" id="replyImagePreview-<?= $comment['id'] ?>" style="display: none; margin-top: 6px; margin-bottom: 6px;">
                                        <span class="selected-file-name"></span>
                                        <button type="button" class="remove-image-btn" onclick="removeReplyImage(<?= $comment['id'] ?>)">&times;</button>
                                    </div>
                                    <div class="comment-reply-controls">
                                        <div class="comment-image-upload">
                                            <input type="file" accept="image/*" id="replyImage-<?= $comment['id'] ?>" onchange="showReplyImagePreview(<?= $comment['id'] ?>, this)">
                                            <label for="replyImage-<?= $comment['id'] ?>" class="comment-image-upload-btn" style="font-size: 11px; padding: 4px 8px;">이미지</label>
                                        </div>
                                        <button class="comment-reply-submit" onclick="submitReply(<?= $activity['id'] ?>, <?= $comment['id'] ?>)">답글 등록</button>
                                        <button class="comment-reply-cancel" onclick="toggleReplyForm(<?= $comment['id'] ?>)">취소</button>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const customerId = <?= $customerId ?>;

// 열려있는 activity ID 저장/복원
function saveOpenActivity(activityId) {
    sessionStorage.setItem('openActivityId_' + customerId, activityId);
}

function clearOpenActivity() {
    sessionStorage.removeItem('openActivityId_' + customerId);
}

function restoreOpenActivity() {
    const activityId = sessionStorage.getItem('openActivityId_' + customerId);
    if (activityId) {
        const activityItem = document.querySelector('.activity-item[data-activity-id="' + activityId + '"]');
        if (activityItem) {
            const wrapper = activityItem.closest('.activity-item-wrapper');
            const detailSection = wrapper.querySelector('.activity-detail');
            const commentSection = wrapper.querySelector('.activity-comments');
            activityItem.classList.add('selected');
            if (detailSection) detailSection.classList.add('show');
            commentSection.classList.add('show');
        }
    }
}

document.getElementById('toggleDetailBtn').addEventListener('click', function() {
    const sections = document.getElementById('detailSections');
    const isCollapsed = sections.classList.contains('collapsed');
    sections.classList.toggle('collapsed');
    this.textContent = isCollapsed ? '상세 정보 접기' : '상세 정보 펼치기';
});

document.getElementById('toggleSearchBtn').addEventListener('click', function() {
    const search = document.getElementById('activitySearch');
    const isHidden = search.style.display === 'none';
    search.style.display = isHidden ? '' : 'none';
    this.textContent = isHidden ? '검색 옵션 접기' : '검색 옵션 펼치기';
});

document.querySelectorAll('.activity-item').forEach(item => {
    item.addEventListener('click', function(e) {
        if (e.target.closest('.comment-action-btn')) return;
        const wrapper = this.closest('.activity-item-wrapper');
        const detailSection = wrapper.querySelector('.activity-detail');
        const commentSection = wrapper.querySelector('.activity-comments');
        const isOpen = detailSection ? detailSection.classList.contains('show') : commentSection.classList.contains('show');
        const activityId = this.dataset.activityId;

        document.querySelectorAll('.activity-item').forEach(ai => ai.classList.remove('selected'));
        document.querySelectorAll('.activity-detail').forEach(ad => ad.classList.remove('show'));
        document.querySelectorAll('.activity-comments').forEach(ac => ac.classList.remove('show'));

        if (!isOpen) {
            this.classList.add('selected');
            if (detailSection) detailSection.classList.add('show');
            commentSection.classList.add('show');
            saveOpenActivity(activityId);
        } else {
            clearOpenActivity();
        }
    });
});

// 페이지 로드 시 이전에 열려있던 댓글창 복원
document.addEventListener('DOMContentLoaded', restoreOpenActivity);

// 이미지 미리보기
function showImagePreview(activityId, input) {
    const preview = document.getElementById('imagePreview-' + activityId);
    if (input.files && input.files[0]) {
        preview.querySelector('.selected-file-name').textContent = input.files[0].name;
        preview.style.display = 'flex';
    } else {
        preview.style.display = 'none';
    }
}

function removeImage(activityId) {
    const input = document.getElementById('commentImage-' + activityId);
    const preview = document.getElementById('imagePreview-' + activityId);
    input.value = '';
    preview.style.display = 'none';
}

function showReplyImagePreview(commentId, input) {
    const preview = document.getElementById('replyImagePreview-' + commentId);
    if (preview && input.files && input.files[0]) {
        preview.querySelector('.selected-file-name').textContent = input.files[0].name;
        preview.style.display = 'flex';
    }
}

function removeReplyImage(commentId) {
    const input = document.getElementById('replyImage-' + commentId);
    const preview = document.getElementById('replyImagePreview-' + commentId);
    if (input) input.value = '';
    if (preview) preview.style.display = 'none';
}

async function submitComment(activityId) {
    const textarea = document.getElementById('commentText-' + activityId);
    const imageInput = document.getElementById('commentImage-' + activityId);
    const content = textarea.value.trim();
    const hasImage = imageInput && imageInput.files && imageInput.files[0];
    if (!content && !hasImage) { alert('내용 또는 이미지를 입력해주세요.'); return; }

    try {
        const formData = new FormData();
        formData.append('action', 'create');
        formData.append('entity_type', 'intl_activity');
        formData.append('entity_id', activityId);
        formData.append('content', content);

        if (imageInput && imageInput.files[0]) {
            formData.append('image', imageInput.files[0]);
        }

        const response = await fetch('<?= CRM_URL ?>/api/common/comments.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        const result = await response.json();

        if (result.success) {
            saveOpenActivity(activityId);
            location.reload();
        } else {
            alert(result.message || '댓글 등록에 실패했습니다.');
        }
    } catch (error) {
        alert('댓글 등록 중 오류가 발생했습니다.');
    }
}

function toggleReplyForm(commentId) {
    const replyArea = document.getElementById('replyArea-' + commentId);
    replyArea.classList.toggle('show');
}

async function submitReply(activityId, parentId) {
    const textarea = document.getElementById('replyText-' + parentId);
    const imageInput = document.getElementById('replyImage-' + parentId);
    const content = textarea.value.trim();
    const hasImage = imageInput && imageInput.files && imageInput.files[0];
    if (!content && !hasImage) { alert('내용 또는 이미지를 입력해주세요.'); return; }

    try {
        const formData = new FormData();
        formData.append('action', 'create');
        formData.append('entity_type', 'intl_activity');
        formData.append('entity_id', activityId);
        formData.append('parent_id', parentId);
        formData.append('content', content);

        if (imageInput && imageInput.files[0]) {
            formData.append('image', imageInput.files[0]);
        }

        const response = await fetch('<?= CRM_URL ?>/api/common/comments.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        const result = await response.json();

        if (result.success) {
            saveOpenActivity(activityId);
            location.reload();
        } else {
            alert(result.message || '답글 등록에 실패했습니다.');
        }
    } catch (error) {
        alert('답글 등록 중 오류가 발생했습니다.');
    }
}

async function deleteCustomer(id) {
    if (!confirm('정말 삭제하시겠습니까?')) return;
    try {
        const response = await apiPost('<?= CRM_URL ?>/api/international/customers.php', {
            action: 'delete',
            id: id
        });
        if (response.success) {
            alert('삭제되었습니다.');
            location.href = '<?= CRM_URL ?>/pages/international/dashboard.php';
        } else {
            alert(response.message || '삭제에 실패했습니다.');
        }
    } catch (error) {
        alert('삭제 중 오류가 발생했습니다.');
    }
}

async function deleteActivity(id) {
    if (!confirm('이 활동을 삭제하시겠습니까?\n관련 댓글도 함께 삭제됩니다.')) return;
    try {
        const response = await apiPost('<?= CRM_URL ?>/api/international/customers.php', {
            action: 'delete_activity',
            id: id
        });
        if (response.success) {
            location.reload();
        } else {
            alert(response.message || '삭제에 실패했습니다.');
        }
    } catch (error) {
        alert('삭제 중 오류가 발생했습니다.');
    }
}

function performActivitySearch() {
    const type = document.getElementById('qActivityType').value;
    const manager = document.getElementById('qManager').value.toLowerCase();
    const fromDate = document.getElementById('qFromDate').value;
    const toDate = document.getElementById('qToDate').value;

    document.querySelectorAll('.activity-item-wrapper').forEach(wrapper => {
        const item = wrapper.querySelector('.activity-item');
        const text = item.textContent.toLowerCase();
        const dateEl = item.querySelector('.activity-date');
        const dateText = dateEl ? dateEl.textContent.replace(/\./g, '-') : '';
        let show = true;

        if (type && !text.includes(type.toLowerCase())) show = false;
        if (manager && !text.includes(manager)) show = false;
        if (fromDate && dateText && new Date(dateText) < new Date(fromDate)) show = false;
        if (toDate && dateText && new Date(dateText) > new Date(toDate)) show = false;

        wrapper.style.display = show ? '' : 'none';
    });
}

function resetActivitySearch() {
    document.getElementById('qActivityType').value = '';
    document.getElementById('qManager').value = '';
    document.getElementById('qFromDate').value = '';
    document.getElementById('qToDate').value = '';
    document.querySelectorAll('.activity-item-wrapper').forEach(w => w.style.display = '');
}

function toggleEditForm(commentId, content = '') {
    const editArea = document.getElementById('editArea-' + commentId);
    const textarea = document.getElementById('editText-' + commentId);
    const isShow = editArea.classList.contains('show');

    // 다른 수정 영역 닫기
    document.querySelectorAll('.comment-edit-area').forEach(area => area.classList.remove('show'));

    if (!isShow) {
        textarea.value = content.replace(/\\n/g, '\n');
        editArea.classList.add('show');
        textarea.focus();
    }
}

function getActivityIdFromComment(commentId) {
    const commentItem = document.querySelector('.comment-item[data-comment-id="' + commentId + '"]');
    if (commentItem) {
        const activityComments = commentItem.closest('.activity-comments');
        if (activityComments) {
            return activityComments.id.replace('comments-', '');
        }
    }
    return null;
}

async function saveComment(commentId) {
    const textarea = document.getElementById('editText-' + commentId);
    const content = textarea.value.trim();
    if (!content) { alert('내용을 입력해주세요.'); return; }

    const activityId = getActivityIdFromComment(commentId);

    try {
        const response = await apiPost('<?= CRM_URL ?>/api/common/comments.php', {
            action: 'update',
            id: commentId,
            content: content
        });
        if (response.success) {
            if (activityId) saveOpenActivity(activityId);
            location.reload();
        } else {
            alert(response.message || '수정에 실패했습니다.');
        }
    } catch (error) {
        alert('수정 중 오류가 발생했습니다.');
    }
}

async function deleteComment(commentId) {
    if (!confirm('정말 삭제하시겠습니까?')) return;

    const activityId = getActivityIdFromComment(commentId);

    try {
        const response = await apiPost('<?= CRM_URL ?>/api/common/comments.php', {
            action: 'delete',
            id: commentId
        });
        if (response.success) {
            if (activityId) saveOpenActivity(activityId);
            location.reload();
        } else {
            alert(response.message || '삭제에 실패했습니다.');
        }
    } catch (error) {
        alert('삭제 중 오류가 발생했습니다.');
    }
}
</script>

<?php include dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>
