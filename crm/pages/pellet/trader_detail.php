<?php
/**
 * 우드펠렛 거래처 상세
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pdo = getDB();

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: traders.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT t.*, u.name as sales_name
        FROM " . CRM_PELLET_TRADERS_TABLE . " t
        LEFT JOIN " . CRM_USERS_TABLE . " u ON t.assigned_sales = u.id
        WHERE t.id = ?");
    $stmt->execute([$id]);
    $trader = $stmt->fetch();
    if (!$trader) {
        header('Location: traders.php');
        exit;
    }
} catch (Exception $e) {
    header('Location: traders.php');
    exit;
}

$pageTitle = $trader['company_name'];
$pageSubtitle = '거래처 상세 정보';

$typeLabels = [
    'online' => '온라인',
    'offline_wholesale' => '오프라인(도매)',
    'offline_retail' => '오프라인(소매)',
    'bulk' => '벌크'
];

// 활동 이력
try {
    $stmt = $pdo->prepare("SELECT a.*, u.name as user_name
        FROM crm_pellet_activities a
        LEFT JOIN " . CRM_USERS_TABLE . " u ON a.created_by = u.id
        WHERE a.trader_id = ?
        ORDER BY a.activity_date DESC LIMIT 20");
    $stmt->execute([$id]);
    $activities = $stmt->fetchAll();
} catch (Exception $e) {
    $activities = [];
}

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
        flex: 3;
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
        flex: 7;
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
        color: #f97316;
        border-bottom-color: #f97316;
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
        background: #f97316;
        color: white;
    }

    .btn-list:hover {
        background: #ea580c;
    }

    /* 섹션 스타일 */
    .section {
        margin-bottom: 30px;
    }

    .section-title {
        font-size: 16px;
        font-weight: 600;
        color: #f97316;
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

    .status-badge.pending {
        background: #fef3c7;
        color: #d97706;
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
        border-color: #f97316;
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
        background: #f97316;
        color: white;
    }

    .btn-search.primary:hover {
        background: #ea580c;
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
        background: #f97316;
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
        background: #ea580c;
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
        background-color: #fff7ed;
        border-left: 3px solid #f97316;
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

    .activity-amount {
        font-size: 13px;
        font-weight: 500;
        color: #212529;
        margin-top: 4px;
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
        border-color: #f97316;
    }

    .comment-submit-btn {
        padding: 6px 16px;
        background-color: #f97316;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
    }

    .comment-submit-btn:hover {
        background-color: #ea580c;
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
        border-left: 3px solid #f97316;
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
        color: #f97316;
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
        border-color: #f97316;
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
    <!-- 왼쪽 패널: 거래처 상세 정보 -->
    <div class="left-panel">
        <div class="detail-card">
            <div class="detail-card-inner">
                <!-- 상단 탭 메뉴 -->
                <div class="tab-menu">
                    <span class="tab-item active">거래처 상세 정보</span>
                    <button class="detail-toggle-btn" id="toggleDetailBtn">상세 정보 접기</button>
                </div>

                <div class="detail-sections">
                    <!-- 기본 정보 섹션 -->
                    <div class="section">
                        <h2 class="section-title">기본 정보</h2>
                        <div class="info-grid">
                            <div class="info-field">
                                <label class="field-label">상호명</label>
                                <div class="field-value"><?= htmlspecialchars($trader['company_name'] ?? '') ?></div>
                            </div>

                            <div class="info-field">
                                <label class="field-label">사업자등록번호</label>
                                <div class="field-value <?= empty($trader['business_number']) ? 'empty' : '' ?>">
                                    <?= !empty($trader['business_number']) ? htmlspecialchars($trader['business_number']) : '(미입력)' ?>
                                </div>
                            </div>

                            <div class="info-field">
                                <label class="field-label">대표자명</label>
                                <div class="field-value <?= empty($trader['representative_name']) ? 'empty' : '' ?>">
                                    <?= !empty($trader['representative_name']) ? htmlspecialchars($trader['representative_name']) : '(미입력)' ?>
                                </div>
                            </div>

                            <div class="info-field">
                                <label class="field-label">전화번호</label>
                                <div class="field-value <?= empty($trader['phone']) ? 'empty' : '' ?>">
                                    <?= !empty($trader['phone']) ? htmlspecialchars($trader['phone']) : '(미입력)' ?>
                                </div>
                            </div>

                            <div class="info-field">
                                <label class="field-label">이메일</label>
                                <div class="field-value <?= empty($trader['email']) ? 'empty' : '' ?>">
                                    <?= !empty($trader['email']) ? htmlspecialchars($trader['email']) : '(미입력)' ?>
                                </div>
                            </div>

                            <div class="info-field">
                                <label class="field-label">거래 유형</label>
                                <div class="field-value">
                                    <?= $typeLabels[$trader['trade_type']] ?? $trader['trade_type'] ?? '(미입력)' ?>
                                </div>
                            </div>

                            <div class="info-field full-width">
                                <label class="field-label">주소</label>
                                <div class="field-value <?= empty($trader['address']) ? 'empty' : '' ?>">
                                    <?= !empty($trader['address']) ? nl2br(htmlspecialchars($trader['address'])) : '(미입력)' ?>
                                </div>
                            </div>

                            <div class="info-field">
                                <label class="field-label">담당자</label>
                                <div class="field-value <?= empty($trader['contact_person']) ? 'empty' : '' ?>">
                                    <?= !empty($trader['contact_person']) ? htmlspecialchars($trader['contact_person']) : '(미입력)' ?>
                                </div>
                            </div>

                            <div class="info-field">
                                <label class="field-label">담당 영업</label>
                                <div class="field-value <?= empty($trader['sales_name']) ? 'empty' : '' ?>">
                                    <?= !empty($trader['sales_name']) ? htmlspecialchars($trader['sales_name']) : '(미배정)' ?>
                                </div>
                            </div>

                            <div class="info-field">
                                <label class="field-label">상태</label>
                                <div class="field-value">
                                    <?php
                                    $statusClass = '';
                                    $statusText = '';
                                    switch ($trader['status']) {
                                        case 'active':
                                            $statusClass = '';
                                            $statusText = '활성';
                                            break;
                                        case 'inactive':
                                            $statusClass = 'inactive';
                                            $statusText = '비활성';
                                            break;
                                        case 'pending':
                                            $statusClass = 'pending';
                                            $statusText = '대기';
                                            break;
                                        default:
                                            $statusText = $trader['status'] ?? '-';
                                    }
                                    ?>
                                    <span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span>
                                </div>
                            </div>

                            <div class="info-field">
                                <label class="field-label">연간 물량</label>
                                <div class="field-value">
                                    <?= !empty($trader['annual_volume']) ? number_format($trader['annual_volume'], 1) . ' 톤' : '-' ?>
                                </div>
                            </div>

                            <div class="info-field">
                                <label class="field-label">등록일</label>
                                <div class="field-value">
                                    <?= !empty($trader['created_at']) ? date('Y-m-d', strtotime($trader['created_at'])) : '-' ?>
                                </div>
                            </div>

                            <div class="info-field">
                                <label class="field-label">최종 수정일</label>
                                <div class="field-value">
                                    <?= !empty($trader['updated_at']) ? date('Y-m-d', strtotime($trader['updated_at'])) : '-' ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 계좌 정보 섹션 -->
                    <div class="section">
                        <h2 class="section-title">계좌 정보</h2>
                        <div class="info-grid">
                            <div class="info-field">
                                <label class="field-label">은행명</label>
                                <div class="field-value <?= empty($trader['bank_name']) ? 'empty' : '' ?>">
                                    <?= !empty($trader['bank_name']) ? htmlspecialchars($trader['bank_name']) : '(미입력)' ?>
                                </div>
                            </div>

                            <div class="info-field">
                                <label class="field-label">계좌번호</label>
                                <div class="field-value <?= empty($trader['account_number']) ? 'empty' : '' ?>">
                                    <?= !empty($trader['account_number']) ? htmlspecialchars($trader['account_number']) : '(미입력)' ?>
                                </div>
                            </div>

                            <div class="info-field">
                                <label class="field-label">예금주</label>
                                <div class="field-value <?= empty($trader['account_holder']) ? 'empty' : '' ?>">
                                    <?= !empty($trader['account_holder']) ? htmlspecialchars($trader['account_holder']) : '(미입력)' ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 계약 정보 섹션 -->
                    <div class="section">
                        <h2 class="section-title">계약 정보</h2>
                        <div class="info-grid">
                            <div class="info-field">
                                <label class="field-label">계약일</label>
                                <div class="field-value">
                                    <?= !empty($trader['contract_date']) ? date('Y-m-d', strtotime($trader['contract_date'])) : '-' ?>
                                </div>
                            </div>

                            <div class="info-field">
                                <label class="field-label">계약 기간</label>
                                <div class="field-value <?= empty($trader['contract_period']) ? 'empty' : '' ?>">
                                    <?= !empty($trader['contract_period']) ? htmlspecialchars($trader['contract_period']) : '(미입력)' ?>
                                </div>
                            </div>

                            <div class="info-field">
                                <label class="field-label">결제 방식</label>
                                <div class="field-value <?= empty($trader['payment_method']) ? 'empty' : '' ?>">
                                    <?= !empty($trader['payment_method']) ? htmlspecialchars($trader['payment_method']) : '(미입력)' ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 액션 버튼 -->
                    <div class="action-buttons">
                        <a href="trader_form.php?id=<?= $id ?>" class="btn-action btn-edit">수정</a>
                        <button class="btn-action btn-delete" onclick="deleteTrader()">삭제</button>
                        <a href="traders.php" class="btn-action btn-list">목록</a>
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
                    <label class="search-label">거래처명</label>
                    <input type="text" class="search-input" placeholder="거래처명 검색" id="searchTrader">
                </div>
                <div class="search-field">
                    <label class="search-label">제품유형</label>
                    <input type="text" class="search-input" placeholder="제품유형 검색" id="searchProduct">
                </div>
                <div class="search-field">
                    <label class="search-label">국가</label>
                    <input type="text" class="search-input" placeholder="국가 검색" id="searchCountry">
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
                <option value="계약">계약</option>
                <option value="매출">매출</option>
                <option value="견적">견적</option>
            </select>
            <button class="btn-register" onclick="location.href='activity_form.php?trader_id=<?= $id ?>'">등록하기</button>
        </div>

        <!-- 활동 목록 -->
        <div class="activity-list">
            <?php if (empty($activities)): ?>
                <div class="empty-state">등록된 활동이 없습니다.</div>
            <?php else: ?>
                <?php foreach ($activities as $activity): ?>
                    <?php $activityId = $activity['id']; ?>
                    <div class="activity-item-wrapper">
                        <div class="activity-item" data-activity-id="<?= $activityId ?>">
                            <div class="activity-icon">
                                <?php
                                $icons = [
                                    '영업활동' => '📄',
                                    '계약' => '🎤',
                                    '매출' => '🚚',
                                    '견적' => '📋',
                                    '미팅' => '🎤',
                                    '전화' => '📞',
                                    '이메일' => '📧'
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
                                <?php if (!empty($activity['amount'])): ?>
                                    <div class="activity-amount">KRW <?= number_format($activity['amount']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="activity-comments" id="comments-<?= $activityId ?>">
                            <div class="activity-comments-title">댓글</div>
                            <div class="comment-input-area">
                                <textarea placeholder="댓글을 입력하세요..." id="comment-text-<?= $activityId ?>"></textarea>
                                <button class="comment-submit-btn" onclick="submitComment(<?= $activityId ?>)">댓글 등록</button>
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

<?php
$pageScripts = <<<SCRIPT
<script>
const traderId = {$id};

// 삭제 기능
async function deleteTrader() {
    if (!confirm('정말 이 거래처를 삭제하시겠습니까?')) return;

    try {
        await apiPost(CRM_URL + '/api/pellet/traders.php', {
            action: 'delete',
            id: traderId
        });
        showToast('삭제되었습니다.', 'success');
        location.href = 'traders.php';
    } catch (error) {
        showToast('삭제 중 오류가 발생했습니다.', 'error');
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

// 활동 아이템 클릭하여 댓글 표시/숨기기
(function(){
    const activityItems = document.querySelectorAll('.activity-item');
    activityItems.forEach(function(item) {
        item.addEventListener('click', function(e) {
            const wrapper = this.closest('.activity-item-wrapper');
            const commentSection = wrapper.querySelector('.activity-comments');
            const isOpen = commentSection.classList.contains('show');

            // 다른 모든 댓글 섹션 닫기
            document.querySelectorAll('.activity-item').forEach(function(ai) {
                ai.classList.remove('selected');
            });
            document.querySelectorAll('.activity-comments').forEach(function(ac) {
                ac.classList.remove('show');
            });

            // 현재 댓글 섹션 토글
            if (!isOpen) {
                this.classList.add('selected');
                commentSection.classList.add('show');
                // 댓글 로드
                loadComments(this.dataset.activityId);
            }
        });
    });
})();

// 댓글 로드
async function loadComments(activityId) {
    const listEl = document.getElementById('comment-list-' + activityId);
    if (!listEl) return;

    try {
        const response = await apiGet(CRM_URL + '/api/pellet/comments.php?activity_id=' + activityId);
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

        const html = '<div class="comment-item ' + levelClass + '" data-comment-id="' + comment.id + '">' +
            '<div class="comment-header">' +
                '<span class="comment-author">' + (comment.user_name || '익명') + '</span>' +
                '<span class="comment-date-text">' + (comment.created_at || '') + '</span>' +
            '</div>' +
            '<div class="comment-content">' + (comment.content || '').replace(/\n/g, '<br>') + '</div>' +
            '<div class="comment-actions">' +
                '<button class="comment-action-btn reply-btn" onclick="showReplyForm(' + comment.id + ')">답글</button>' +
            '</div>' +
            '<div class="comment-reply-area" id="reply-area-' + comment.id + '">' +
                '<textarea placeholder="답글을 입력하세요..." id="reply-text-' + comment.id + '"></textarea>' +
                '<div class="comment-reply-controls">' +
                    '<button class="comment-reply-submit" onclick="submitReply(' + comment.id + ', ' + comment.activity_id + ')">답글 등록</button>' +
                    '<button class="comment-reply-cancel" onclick="hideReplyForm(' + comment.id + ')">취소</button>' +
                '</div>' +
            '</div>' +
        '</div>';

        container.innerHTML += html;
    });
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
    const textarea = document.getElementById('comment-text-' + activityId);
    const content = textarea.value.trim();

    if (!content) {
        showToast('내용을 입력해주세요.', 'warning');
        return;
    }

    try {
        const response = await apiPost(CRM_URL + '/api/pellet/comments.php', {
            action: 'create',
            activity_id: activityId,
            content: content
        });

        if (response.success) {
            textarea.value = '';
            loadComments(activityId);
            showToast('댓글이 등록되었습니다.', 'success');
        } else {
            showToast(response.message || '댓글 등록에 실패했습니다.', 'error');
        }
    } catch (error) {
        showToast('댓글 등록 중 오류가 발생했습니다.', 'error');
    }
}

// 답글 등록
async function submitReply(parentId, activityId) {
    const textarea = document.getElementById('reply-text-' + parentId);
    const content = textarea.value.trim();

    if (!content) {
        showToast('내용을 입력해주세요.', 'warning');
        return;
    }

    try {
        const response = await apiPost(CRM_URL + '/api/pellet/comments.php', {
            action: 'create',
            activity_id: activityId,
            parent_id: parentId,
            content: content
        });

        if (response.success) {
            textarea.value = '';
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
    showToast('검색 기능은 준비 중입니다.', 'info');
}

function resetSearch() {
    document.querySelectorAll('.search-input').forEach(function(input) {
        input.value = '';
    });
}
</script>
SCRIPT;

include dirname(dirname(__DIR__)) . '/includes/footer.php';
?>
