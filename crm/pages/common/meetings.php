<?php
/**
 * CRM 회의록 관리
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pageTitle = '회의록';
$pageSubtitle = '회의 기록을 관리합니다';

$pdo = getDB();

// 필터 파라미터
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? '';
$search = $_GET['search'] ?? '';

// 쿼리 빌드
$where = ["1=1"];
$params = [];

if ($year) {
    $where[] = "YEAR(meeting_date) = ?";
    $params[] = $year;
}

if ($month) {
    $where[] = "MONTH(meeting_date) = ?";
    $params[] = $month;
}

if ($search) {
    $where[] = "(title LIKE ? OR content LIKE ? OR agenda LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereClause = implode(' AND ', $where);

// 회의록 목록 조회
try {
    $stmt = $pdo->prepare("SELECT m.*, u.name as creator_name
        FROM " . CRM_MEETINGS_TABLE . " m
        LEFT JOIN " . CRM_USERS_TABLE . " u ON m.created_by = u.id
        WHERE {$whereClause}
        ORDER BY meeting_date DESC, meeting_time DESC");
    $stmt->execute($params);
    $meetings = $stmt->fetchAll();
} catch (Exception $e) {
    $meetings = [];
}

// 연도 목록
try {
    $stmt = $pdo->query("SELECT DISTINCT YEAR(meeting_date) as y FROM " . CRM_MEETINGS_TABLE . " ORDER BY y DESC");
    $years = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (empty($years)) {
        $years = [date('Y')];
    }
} catch (Exception $e) {
    $years = [date('Y')];
}

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<style>
    .filter-bar {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
        align-items: center;
        margin-bottom: 24px;
    }

    .filter-group {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .filter-group label {
        font-size: 14px;
        color: #666;
    }

    .filter-group select {
        width: auto;
        min-width: 100px;
    }

    .search-box {
        flex: 1;
        min-width: 200px;
        display: flex;
        gap: 8px;
    }

    .search-box input {
        flex: 1;
    }

    .meetings-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 20px;
    }

    .meeting-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        transition: all 0.2s;
        cursor: pointer;
    }

    .meeting-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        transform: translateY(-2px);
    }

    .meeting-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 12px;
    }

    .meeting-date {
        font-size: 14px;
        color: #666;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .meeting-date .date {
        font-weight: 600;
        color: var(--primary);
    }

    .meeting-title {
        font-size: 18px;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 8px;
        line-height: 1.4;
    }

    .meeting-info {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
        font-size: 13px;
        color: #666;
        margin-bottom: 12px;
    }

    .meeting-info span {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .meeting-preview {
        font-size: 14px;
        color: #888;
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .meeting-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 16px;
        padding-top: 12px;
        border-top: 1px solid #f0f0f0;
    }

    .meeting-creator {
        font-size: 13px;
        color: #999;
    }

    .meeting-actions {
        display: flex;
        gap: 8px;
    }

    .meeting-actions button {
        padding: 6px 12px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 12px;
        transition: all 0.2s;
    }

    .btn-view {
        background: var(--primary);
        color: white;
    }

    .btn-view:hover {
        background: var(--primary-dark);
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #999;
        grid-column: 1 / -1;
    }

    .empty-state .icon {
        font-size: 48px;
        margin-bottom: 16px;
    }

    /* 회의록 상세 모달 */
    .meeting-detail-modal {
        max-width: 800px;
    }

    .detail-section {
        margin-bottom: 24px;
    }

    .detail-section-title {
        font-size: 14px;
        font-weight: 600;
        color: #666;
        margin-bottom: 8px;
        padding-bottom: 8px;
        border-bottom: 1px solid #eee;
    }

    .detail-content {
        font-size: 15px;
        line-height: 1.8;
        white-space: pre-wrap;
    }

    .attendee-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .attendee-tag {
        background: #f0f0f0;
        padding: 4px 12px;
        border-radius: 16px;
        font-size: 13px;
    }

    .attendee-tag.creator {
        background: var(--primary);
        color: white;
    }

    /* 회의록 폼 모달 */
    .meeting-form-modal {
        max-width: 900px;
        width: 90%;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 500;
        color: #212529;
        margin-bottom: 8px;
    }

    .form-label.required::after {
        content: " *";
        color: #dc3545;
    }

    .form-input {
        width: 100%;
        padding: 10px 16px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        font-size: 14px;
    }

    .form-input:focus {
        outline: none;
        border-color: #0d6efd;
    }

    .form-textarea {
        width: 100%;
        min-height: 100px;
        padding: 12px 16px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        font-size: 14px;
        resize: vertical;
        font-family: inherit;
    }

    .form-textarea:focus {
        outline: none;
        border-color: #0d6efd;
    }

    .form-textarea.large {
        min-height: 150px;
    }

    .form-select {
        width: 100%;
        padding: 10px 16px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        font-size: 14px;
        background: white;
    }

    .help-text {
        font-size: 12px;
        color: #6c757d;
        margin-top: 4px;
    }

    /* 참석자 태그 */
    .attendee-input-group {
        display: flex;
        gap: 8px;
        margin-bottom: 12px;
    }

    .attendee-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 8px;
    }

    .tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: #e7f1ff;
        border: 1px solid #0d6efd;
        border-radius: 16px;
        font-size: 13px;
        color: #0d6efd;
    }

    .tag-remove {
        cursor: pointer;
        font-weight: bold;
    }

    .tag-remove:hover {
        color: #dc3545;
    }

    /* 섹션 구분 */
    .section-divider {
        border: none;
        border-top: 1px solid #e9ecef;
        margin: 24px 0;
    }

    .section-title {
        font-size: 16px;
        font-weight: 600;
        color: #212529;
        margin-bottom: 16px;
    }

    /* 녹음 섹션 */
    .recording-section {
        background: #f8f9fa;
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        text-align: center;
    }

    .btn-record {
        padding: 12px 24px;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #dc3545;
        color: white;
    }

    .btn-record:hover {
        background: #bb2d3b;
    }

    .btn-small {
        padding: 8px 16px;
        font-size: 13px;
    }

    @media (max-width: 600px) {
        .form-row {
            grid-template-columns: 1fr;
        }
        .attendee-input-group {
            flex-direction: column;
        }
    }
</style>

<!-- 필터 & 검색 -->
<div class="card" style="padding: 16px; margin-bottom: 24px;">
    <div class="filter-bar">
        <form class="filter-group" method="GET" id="filterForm">
            <label>연도</label>
            <select name="year" class="form-control" onchange="this.form.submit()">
                <?php foreach ($years as $y): ?>
                    <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?>년</option>
                <?php endforeach; ?>
            </select>

            <label>월</label>
            <select name="month" class="form-control" onchange="this.form.submit()">
                <option value="">전체</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $month == $m ? 'selected' : '' ?>><?= $m ?>월</option>
                <?php endfor; ?>
            </select>

            <input type="text" name="search" class="form-control" placeholder="검색어" value="<?= htmlspecialchars($search) ?>" style="width: 200px;">
            <button type="submit" class="btn btn-secondary">검색</button>
        </form>

        <button class="btn btn-primary" onclick="openMeetingForm()">+ 회의록 작성</button>
    </div>
</div>

<!-- 회의록 목록 -->
<div class="meetings-grid">
    <?php if (empty($meetings)): ?>
        <div class="empty-state">
            <div class="icon">📝</div>
            <p>등록된 회의록이 없습니다.</p>
        </div>
    <?php else: ?>
        <?php foreach ($meetings as $meeting): ?>
            <div class="meeting-card" onclick="viewMeeting(<?= $meeting['id'] ?>)">
                <div class="meeting-header">
                    <div class="meeting-date">
                        <span class="date"><?= formatDate($meeting['meeting_date'], 'Y.m.d') ?></span>
                        <?php if ($meeting['meeting_time']): ?>
                            <span><?= substr($meeting['meeting_time'], 0, 5) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="meeting-title"><?= htmlspecialchars($meeting['title']) ?></div>

                <div class="meeting-info">
                    <?php if ($meeting['location']): ?>
                        <span>📍 <?= htmlspecialchars($meeting['location']) ?></span>
                    <?php endif; ?>
                    <?php if ($meeting['meeting_type']): ?>
                        <span>📋 <?= htmlspecialchars($meeting['meeting_type']) ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($meeting['agenda']): ?>
                    <div class="meeting-preview"><?= htmlspecialchars($meeting['agenda']) ?></div>
                <?php endif; ?>

                <div class="meeting-footer">
                    <span class="meeting-creator">작성: <?= htmlspecialchars($meeting['creator_name'] ?? '알 수 없음') ?></span>
                    <div class="meeting-actions" onclick="event.stopPropagation()">
                        <button class="btn-view" onclick="viewMeeting(<?= $meeting['id'] ?>)">보기</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- 회의록 상세 모달 -->
<div class="modal-overlay" id="viewModal">
    <div class="modal meeting-detail-modal">
        <div class="modal-header">
            <h3 id="viewTitle">회의록</h3>
            <button class="modal-close" onclick="closeModal('viewModal')">&times;</button>
        </div>
        <div class="modal-body" id="viewContent">
            <!-- 동적 로드 -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('viewModal')">닫기</button>
            <button class="btn btn-primary" id="editMeetingBtn" onclick="editMeeting()">수정</button>
            <button class="btn btn-danger" id="deleteMeetingBtn" onclick="deleteMeeting()">삭제</button>
        </div>
    </div>
</div>

<!-- 회의록 작성/수정 모달 -->
<div class="modal-overlay" id="formModal">
    <div class="modal meeting-form-modal">
        <div class="modal-header">
            <h3 id="formTitle">회의록 작성</h3>
            <button class="modal-close" onclick="closeModal('formModal')">&times;</button>
        </div>
        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
            <form id="meetingForm">
                <input type="hidden" name="id" id="meetingId">

                <!-- 회의 기본 정보 섹션 -->
                <div class="section-title">회의 기본 정보</div>

                <!-- 회의 제목 -->
                <div class="form-group">
                    <label class="form-label required">회의 제목</label>
                    <input type="text" class="form-input" name="title" id="meetingTitle" placeholder="회의 제목을 입력하세요" required>
                    <div class="help-text">예: 12월 전략회의, 신규 파트너사 미팅 등</div>
                </div>

                <!-- 날짜 및 시간 -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label required">회의 날짜</label>
                        <input type="date" class="form-input" name="meeting_date" id="meetingDate" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">회의 시간</label>
                        <input type="time" class="form-input" name="meeting_time" id="meetingTime" required>
                    </div>
                </div>

                <!-- 장소 및 유형 -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">회의 장소</label>
                        <input type="text" class="form-input" name="location" id="meetingLocation" placeholder="예: 본사 대회의실">
                    </div>
                    <div class="form-group">
                        <label class="form-label">회의 유형</label>
                        <select class="form-select" name="meeting_type" id="meetingType">
                            <option value="정기 회의">정기 회의</option>
                            <option value="전략 회의">전략 회의</option>
                            <option value="프로젝트 회의">프로젝트 회의</option>
                            <option value="팀 미팅">팀 미팅</option>
                            <option value="고객 미팅">고객 미팅</option>
                            <option value="기타">기타</option>
                        </select>
                    </div>
                </div>

                <!-- 참석자 -->
                <div class="form-group">
                    <label class="form-label">참석자</label>
                    <div class="attendee-input-group">
                        <input type="text" class="form-input" id="attendeeInput" placeholder="참석자 이름 입력">
                        <button type="button" class="btn btn-secondary btn-small" onclick="addAttendeeTag()">추가</button>
                    </div>
                    <input type="hidden" name="attendees" id="meetingAttendees">
                    <div class="attendee-tags" id="attendeeTags">
                        <div class="tag">
                            <span><?= h($currentUser['mb_name'] ?? '작성자') ?> (작성자)</span>
                        </div>
                    </div>
                </div>

                <hr class="section-divider">

                <!-- 회의 내용 섹션 -->
                <div class="section-title">회의 내용</div>

                <!-- 회의 녹음 -->
                <div class="recording-section">
                    <label class="btn-record">
                        <input type="file" name="audio_file" accept="audio/*" style="display:none">
                        ⏺ 녹음 파일 등록
                    </label>
                </div>

                <!-- 회의 안건 -->
                <div class="form-group">
                    <label class="form-label required">회의 안건</label>
                    <textarea class="form-textarea" name="agenda" id="meetingAgenda" placeholder="회의 안건을 입력하세요" required></textarea>
                    <div class="help-text">회의에서 다룰 주요 안건을 작성하세요</div>
                </div>

                <!-- 회의 내용 -->
                <div class="form-group">
                    <label class="form-label required">회의 내용</label>
                    <textarea class="form-textarea large" name="content" id="meetingContent" placeholder="회의 내용을 상세히 작성하세요" required></textarea>
                    <div class="help-text">회의 중 논의된 내용을 자세히 기록하세요</div>
                </div>

                <!-- 결정 사항 -->
                <div class="form-group">
                    <label class="form-label">결정 사항</label>
                    <textarea class="form-textarea" name="decisions" id="meetingDecisions" placeholder="회의에서 결정된 사항을 작성하세요"></textarea>
                </div>

                <!-- 액션 아이템 -->
                <div class="form-group">
                    <label class="form-label">액션 아이템 (후속 조치)</label>
                    <textarea class="form-textarea" name="action_items" id="meetingActions" placeholder="회의 후 진행할 액션 아이템을 작성하세요"></textarea>
                    <div class="help-text">담당자와 마감일을 함께 명시하면 좋습니다</div>
                </div>

                <!-- 다음 회의 일정 -->
                <div class="form-group">
                    <label class="form-label">다음 회의 일정</label>
                    <input type="date" class="form-input" name="next_meeting_date" id="nextMeetingDate">
                </div>

                <!-- 첨부 파일 -->
                <div class="form-group">
                    <label class="form-label">첨부 파일</label>
                    <input type="file" class="form-input" name="attachments[]" multiple>
                    <div class="help-text">회의 자료, 발표 자료 등을 첨부할 수 있습니다</div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('formModal')">취소</button>
            <button class="btn btn-primary" onclick="saveMeeting()">저장</button>
        </div>
    </div>
</div>

<?php
$pageScripts = <<<SCRIPT
<script>
    let currentMeetingId = null;

    // 참석자 태그 추가
    function addAttendeeTag() {
        const input = document.getElementById('attendeeInput');
        const name = input.value.trim();

        if (name) {
            const tagsContainer = document.getElementById('attendeeTags');
            const tag = document.createElement('div');
            tag.className = 'tag';
            tag.innerHTML = '<span>' + name + '</span><span class="tag-remove" onclick="this.parentElement.remove(); updateAttendeesHidden()">×</span>';
            tagsContainer.appendChild(tag);
            input.value = '';
            updateAttendeesHidden();
        }
    }

    // 참석자 목록 업데이트
    function updateAttendeesHidden() {
        const tags = document.querySelectorAll('#attendeeTags .tag span:first-child');
        const names = [];
        tags.forEach((tag, index) => {
            if (index > 0) { // 작성자 제외
                names.push(tag.textContent);
            }
        });
        document.getElementById('meetingAttendees').value = names.join(', ');
    }

    // Enter 키로 참석자 추가
    document.getElementById('attendeeInput')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addAttendeeTag();
        }
    });

    // 회의록 상세 보기
    async function viewMeeting(id) {
        try {
            const response = await apiGet(CRM_URL + '/api/common/meetings.php?id=' + id);
            const meeting = response.data;
            currentMeetingId = id;

            let attendeesHtml = '';
            if (meeting.attendees && meeting.attendees.length > 0) {
                attendeesHtml = meeting.attendees.map(a =>
                    '<span class="attendee-tag ' + (a.is_creator ? 'creator' : '') + '">' + a.attendee_name + (a.is_creator ? ' (작성자)' : '') + '</span>'
                ).join('');
            }

            document.getElementById('viewTitle').textContent = meeting.title;
            document.getElementById('viewContent').innerHTML = `
                <div style="display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 24px; color: #666; font-size: 14px;">
                    <span>📅 \${meeting.meeting_date || '-'}</span>
                    \${meeting.meeting_time ? '<span>🕐 ' + meeting.meeting_time.substring(0,5) + '</span>' : ''}
                    \${meeting.location ? '<span>📍 ' + meeting.location + '</span>' : ''}
                    \${meeting.meeting_type ? '<span>📋 ' + meeting.meeting_type + '</span>' : ''}
                </div>

                \${attendeesHtml ? `
                <div class="detail-section">
                    <div class="detail-section-title">참석자</div>
                    <div class="attendee-list">\${attendeesHtml}</div>
                </div>
                ` : ''}

                \${meeting.agenda ? `
                <div class="detail-section">
                    <div class="detail-section-title">안건</div>
                    <div class="detail-content">\${meeting.agenda}</div>
                </div>
                ` : ''}

                <div class="detail-section">
                    <div class="detail-section-title">회의 내용</div>
                    <div class="detail-content">\${meeting.content || '(내용 없음)'}</div>
                </div>

                \${meeting.decisions ? `
                <div class="detail-section">
                    <div class="detail-section-title">결정 사항</div>
                    <div class="detail-content">\${meeting.decisions}</div>
                </div>
                ` : ''}

                \${meeting.action_items ? `
                <div class="detail-section">
                    <div class="detail-section-title">액션 아이템</div>
                    <div class="detail-content">\${meeting.action_items}</div>
                </div>
                ` : ''}

                \${meeting.next_meeting_date ? `
                <div class="detail-section">
                    <div class="detail-section-title">다음 회의</div>
                    <div class="detail-content">\${meeting.next_meeting_date}</div>
                </div>
                ` : ''}
            `;

            openModal('viewModal');
        } catch (error) {
            showToast('데이터를 불러올 수 없습니다.', 'error');
        }
    }

    // 회의록 작성 폼 열기
    function openMeetingForm() {
        document.getElementById('formTitle').textContent = '회의록 작성';
        document.getElementById('meetingForm').reset();
        document.getElementById('meetingId').value = '';
        document.getElementById('meetingDate').value = new Date().toISOString().split('T')[0];

        // 참석자 태그 초기화 (작성자만 남김)
        const tagsContainer = document.getElementById('attendeeTags');
        const creatorTag = tagsContainer.querySelector('.tag:first-child');
        tagsContainer.innerHTML = '';
        if (creatorTag) {
            tagsContainer.appendChild(creatorTag);
        }
        document.getElementById('meetingAttendees').value = '';

        openModal('formModal');
    }

    // 회의록 수정 폼 열기
    async function editMeeting() {
        if (!currentMeetingId) return;

        try {
            const response = await apiGet(CRM_URL + '/api/common/meetings.php?id=' + currentMeetingId);
            const meeting = response.data;

            document.getElementById('formTitle').textContent = '회의록 수정';
            document.getElementById('meetingId').value = meeting.id;
            document.getElementById('meetingTitle').value = meeting.title || '';
            document.getElementById('meetingDate').value = meeting.meeting_date || '';
            document.getElementById('meetingTime').value = meeting.meeting_time ? meeting.meeting_time.substring(0,5) : '';
            document.getElementById('meetingLocation').value = meeting.location || '';
            document.getElementById('meetingType').value = meeting.meeting_type || '';
            document.getElementById('meetingAgenda').value = meeting.agenda || '';
            document.getElementById('meetingContent').value = meeting.content || '';
            document.getElementById('meetingDecisions').value = meeting.decisions || '';
            document.getElementById('meetingActions').value = meeting.action_items || '';
            document.getElementById('nextMeetingDate').value = meeting.next_meeting_date || '';

            // 참석자 태그 로드
            const tagsContainer = document.getElementById('attendeeTags');
            const creatorTag = tagsContainer.querySelector('.tag:first-child');
            tagsContainer.innerHTML = '';
            if (creatorTag) {
                tagsContainer.appendChild(creatorTag);
            }

            if (meeting.attendees && meeting.attendees.length > 0) {
                meeting.attendees.forEach(a => {
                    if (!a.is_creator) {
                        const tag = document.createElement('div');
                        tag.className = 'tag';
                        tag.innerHTML = '<span>' + a.attendee_name + '</span><span class="tag-remove" onclick="this.parentElement.remove(); updateAttendeesHidden()">×</span>';
                        tagsContainer.appendChild(tag);
                    }
                });
                document.getElementById('meetingAttendees').value = meeting.attendees.filter(a => !a.is_creator).map(a => a.attendee_name).join(', ');
            } else {
                document.getElementById('meetingAttendees').value = '';
            }

            closeModal('viewModal');
            openModal('formModal');
        } catch (error) {
            showToast('데이터를 불러올 수 없습니다.', 'error');
        }
    }

    // 회의록 저장
    async function saveMeeting() {
        const form = document.getElementById('meetingForm');
        const formData = new FormData(form);

        const data = {
            action: formData.get('id') ? 'update' : 'create',
            id: formData.get('id') || null,
            title: formData.get('title'),
            meeting_date: formData.get('meeting_date'),
            meeting_time: formData.get('meeting_time'),
            location: formData.get('location'),
            meeting_type: formData.get('meeting_type'),
            agenda: formData.get('agenda'),
            content: formData.get('content'),
            decisions: formData.get('decisions'),
            action_items: formData.get('action_items'),
            next_meeting_date: formData.get('next_meeting_date'),
            attendees: formData.get('attendees')
        };

        if (!data.title.trim()) {
            showToast('제목을 입력해주세요.', 'error');
            return;
        }

        if (!data.meeting_date) {
            showToast('회의 일자를 입력해주세요.', 'error');
            return;
        }

        try {
            await apiPost(CRM_URL + '/api/common/meetings.php', data);
            showToast('저장되었습니다.', 'success');
            closeModal('formModal');
            location.reload();
        } catch (error) {
            showToast('저장 중 오류가 발생했습니다.', 'error');
        }
    }

    // 회의록 삭제
    async function deleteMeeting() {
        if (!currentMeetingId) return;
        if (!confirm('이 회의록을 삭제하시겠습니까?')) return;

        try {
            await apiPost(CRM_URL + '/api/common/meetings.php', {
                action: 'delete',
                id: currentMeetingId
            });

            showToast('삭제되었습니다.', 'success');
            closeModal('viewModal');
            location.reload();
        } catch (error) {
            showToast('삭제 중 오류가 발생했습니다.', 'error');
        }
    }
</script>
SCRIPT;

include dirname(dirname(__DIR__)) . '/includes/footer.php';
?>
