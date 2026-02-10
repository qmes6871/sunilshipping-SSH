<?php
/**
 * 회의록 등록/수정
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pageTitle = '회의록 등록';
$pageSubtitle = '새로운 회의록을 작성합니다';

$pdo = getDB();

$id = $_GET['id'] ?? null;
$meeting = null;
$attendees = '';

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM " . CRM_MEETINGS_TABLE . " WHERE id = ?");
    $stmt->execute([$id]);
    $meeting = $stmt->fetch();

    if ($meeting) {
        $pageTitle = '회의록 수정';
        $pageSubtitle = '회의록 수정';

        // 참석자 조회
        $stmt = $pdo->prepare("SELECT attendee_name FROM " . CRM_MEETING_ATTENDEES_TABLE . " WHERE meeting_id = ? AND is_creator = 0");
        $stmt->execute([$id]);
        $attendeeList = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $attendees = implode(', ', $attendeeList);
    }
}

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

.container { max-width: 900px; margin: 0 auto; padding: 20px; }

/* 페이지 헤더 */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}
.header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}
.page-title {
    font-size: 28px;
    font-weight: 700;
    color: #212529;
    margin-bottom: 4px;
}
.page-subtitle {
    font-size: 14px;
    color: #6c757d;
}
.btn-back {
    padding: 8px 16px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    background: white;
    color: #495057;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
}
.btn-back:hover {
    background: #f8f9fa;
}

/* 카드 */
.meeting-card {
    background: white;
    padding: 24px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.card-header-row {
    margin-bottom: 20px;
}
.card-title {
    font-size: 18px;
    font-weight: 600;
    color: #212529;
}

/* 폼 요소 */
.form-group {
    margin-bottom: 20px;
}
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
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

/* 참석자 추가 */
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

/* 녹음 섹션 */
.recording-section {
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    text-align: center;
}
.recording-controls {
    display: flex;
    gap: 12px;
    justify-content: center;
    align-items: center;
    flex-wrap: wrap;
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
}
.btn-record.start {
    background: #dc3545;
    color: white;
}
.btn-record.start:hover {
    background: #bb2d3b;
}
.btn-record.stop {
    background: #6c757d;
    color: white;
}
.btn-record.stop:hover {
    background: #5c636a;
}
.btn-record:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* 버튼 그룹 */
.btn-group {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 24px;
}
.btn-small {
    padding: 8px 16px;
    font-size: 13px;
}

/* 반응형 */
@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    .btn-group {
        flex-direction: column-reverse;
    }
    .btn-group .btn {
        width: 100%;
    }
    .attendee-input-group {
        flex-direction: column;
    }
}
</style>

<div class="container">
    <!-- 페이지 헤더 -->
    <div class="page-header">
        <div class="header-left">
            <a href="meetings.php" class="btn-back">← 뒤로가기</a>
            <div>
                <div class="page-title"><?= $meeting ? '회의록 수정' : '회의록 등록' ?></div>
                <div class="page-subtitle"><?= $meeting ? '회의록 수정' : '새로운 회의록을 작성합니다' ?></div>
            </div>
        </div>
    </div>

    <!-- 등록 폼 -->
    <div class="meeting-card">
        <div class="card-header-row" style="border: none; padding-bottom: 0; margin-bottom: 20px;">
            <div class="card-title">회의 기본 정보</div>
        </div>

        <form id="meetingForm">
            <input type="hidden" name="id" value="<?= $meeting['id'] ?? '' ?>">

            <!-- 회의 제목 -->
            <div class="form-group">
                <label class="form-label required">회의 제목</label>
                <input type="text" name="title" class="form-input" placeholder="회의 제목을 입력하세요" value="<?= h($meeting['title'] ?? '') ?>" required>
                <div class="help-text">예: 12월 전략회의, 신규 파트너사 미팅 등</div>
            </div>

            <!-- 날짜 및 시간 -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label required">회의 날짜</label>
                    <input type="date" name="meeting_date" class="form-input" value="<?= $meeting['meeting_date'] ?? date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label required">회의 시간</label>
                    <input type="time" name="meeting_time" class="form-input" value="<?= $meeting['meeting_time'] ?? '' ?>" required>
                </div>
            </div>

            <!-- 장소 및 유형 -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">회의 장소</label>
                    <input type="text" name="location" class="form-input" placeholder="예: 본사 대회의실" value="<?= h($meeting['location'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">회의 유형</label>
                    <select name="meeting_type" class="form-select">
                        <option value="정기 회의" <?= ($meeting['meeting_type'] ?? '') === '정기 회의' ? 'selected' : '' ?>>정기 회의</option>
                        <option value="전략 회의" <?= ($meeting['meeting_type'] ?? '') === '전략 회의' ? 'selected' : '' ?>>전략 회의</option>
                        <option value="프로젝트 회의" <?= ($meeting['meeting_type'] ?? '') === '프로젝트 회의' ? 'selected' : '' ?>>프로젝트 회의</option>
                        <option value="팀 미팅" <?= ($meeting['meeting_type'] ?? '') === '팀 미팅' ? 'selected' : '' ?>>팀 미팅</option>
                        <option value="고객 미팅" <?= ($meeting['meeting_type'] ?? '') === '고객 미팅' ? 'selected' : '' ?>>고객 미팅</option>
                        <option value="기타" <?= ($meeting['meeting_type'] ?? '') === '기타' ? 'selected' : '' ?>>기타</option>
                    </select>
                </div>
            </div>

            <!-- 참석자 -->
            <div class="form-group">
                <label class="form-label">참석자</label>
                <div class="attendee-input-group">
                    <input type="text" class="form-input" id="attendeeInput" placeholder="참석자 이름 입력">
                    <button type="button" class="btn btn-secondary btn-small" onclick="addAttendee()">추가</button>
                </div>
                <input type="hidden" name="attendees" id="attendeesHidden" value="<?= h($attendees) ?>">
                <div class="attendee-tags" id="attendeeTags">
                    <div class="tag">
                        <span><?= h($currentUser['mb_name'] ?? '사용자') ?> (작성자)</span>
                    </div>
                </div>
            </div>

            <hr class="section-divider">

            <!-- 회의 내용 -->
            <div class="card-header-row" style="border: none; padding-bottom: 0; margin-bottom: 20px; margin-top: 24px;">
                <div class="card-title">회의 내용</div>
            </div>

            <!-- 회의 녹음 -->
            <div class="recording-section">
                <div class="recording-controls">
                    <label class="btn-record start" id="audioUploadLabel" style="cursor:pointer;">
                        <input type="file" name="audio_file" id="audioFileInput" accept="audio/*" style="display:none">
                        🎤 녹음 파일 등록
                    </label>
                </div>
                <div class="audio-list" id="audioList">
                    <?php if (!empty($meeting['audio_file'])): ?>
                    <div class="audio-item" style="margin-top: 12px; padding: 12px; background: #e7f5ff; border-radius: 6px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                            <span style="font-size: 14px;">🎵 기존 녹음파일</span>
                            <div style="display: flex; gap: 8px;">
                                <audio controls src="<?= CRM_UPLOAD_URL ?>/<?= h($meeting['audio_file']) ?>" style="height: 32px;"></audio>
                                <button type="button" class="btn btn-sm btn-danger" onclick="removeAudioFile()">삭제</button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div id="selectedAudioInfo" style="margin-top: 12px; display: none;">
                    <div style="padding: 12px; background: #d1e7dd; border-radius: 6px; display: flex; align-items: center; justify-content: space-between;">
                        <span id="selectedAudioName" style="font-size: 14px;">📎 선택된 파일</span>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="clearAudioFile()">취소</button>
                    </div>
                </div>
                <input type="hidden" name="remove_audio" id="removeAudioInput" value="0">
            </div>

            <!-- 회의 안건 -->
            <div class="form-group">
                <label class="form-label required">회의 안건</label>
                <textarea name="agenda" class="form-textarea" placeholder="회의 안건을 입력하세요" required><?= h($meeting['agenda'] ?? '') ?></textarea>
                <div class="help-text">회의에서 다룰 주요 안건을 작성하세요</div>
            </div>

            <!-- 회의 내용 -->
            <div class="form-group">
                <label class="form-label required">회의 내용</label>
                <textarea name="content" class="form-textarea large" placeholder="회의 내용을 상세히 작성하세요" required><?= h($meeting['content'] ?? '') ?></textarea>
                <div class="help-text">회의 중 논의된 내용을 자세히 기록하세요</div>
            </div>

            <!-- 결정 사항 -->
            <div class="form-group">
                <label class="form-label">결정 사항</label>
                <textarea name="decisions" class="form-textarea" placeholder="회의에서 결정된 사항을 작성하세요"><?= h($meeting['decisions'] ?? '') ?></textarea>
            </div>

            <!-- 액션 아이템 -->
            <div class="form-group">
                <label class="form-label">액션 아이템 (후속 조치)</label>
                <textarea name="action_items" class="form-textarea" placeholder="회의 후 진행할 액션 아이템을 작성하세요"><?= h($meeting['action_items'] ?? '') ?></textarea>
                <div class="help-text">담당자와 마감일을 함께 명시하면 좋습니다</div>
            </div>

            <!-- 다음 회의 일정 -->
            <div class="form-group">
                <label class="form-label">다음 회의 일정</label>
                <input type="date" name="next_meeting_date" class="form-input" value="<?= $meeting['next_meeting_date'] ?? '' ?>">
            </div>

            <!-- 첨부 파일 -->
            <div class="form-group">
                <label class="form-label">첨부 파일</label>
                <?php
                $existingAttachments = !empty($meeting['attachments']) ? json_decode($meeting['attachments'], true) : [];
                if (!empty($existingAttachments)):
                ?>
                <div id="existingAttachments" style="margin-bottom: 12px;">
                    <?php foreach ($existingAttachments as $index => $attachment): ?>
                    <div style="padding: 10px 14px; background: #e7f5ff; border-radius: 6px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 14px;">📎 <?= h($attachment['name']) ?></span>
                        <a href="<?= CRM_UPLOAD_URL ?>/<?= h($attachment['path']) ?>" download class="btn btn-sm btn-outline" style="padding: 4px 10px; font-size: 12px;">다운로드</a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <input type="file" name="attachments[]" class="form-input" multiple>
                <div class="help-text">회의 자료, 발표 자료 등을 첨부할 수 있습니다 (새 파일을 추가하면 기존 파일과 함께 저장됩니다)</div>
            </div>

            <!-- 버튼 그룹 -->
            <div class="btn-group">
                <a href="meetings.php" class="btn btn-secondary">취소</a>
                <?php if ($meeting): ?>
                    <button type="button" class="btn btn-danger" onclick="deleteMeeting()">삭제</button>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary"><?= $meeting ? '수정' : '등록하기' ?></button>
            </div>
        </form>
    </div>
</div>

<?php
$pageScripts = <<<SCRIPT
<script>
// 기존 참석자 로드
const existingAttendees = document.getElementById('attendeesHidden').value;
if (existingAttendees) {
    existingAttendees.split(',').forEach(name => {
        name = name.trim();
        if (name) {
            const tagsContainer = document.getElementById('attendeeTags');
            const tag = document.createElement('div');
            tag.className = 'tag';
            tag.innerHTML = '<span>' + name + '</span><span class="tag-remove" onclick="this.parentElement.remove(); updateAttendees()">×</span>';
            tagsContainer.appendChild(tag);
        }
    });
}

// 참석자 추가
function addAttendee() {
    const input = document.getElementById('attendeeInput');
    const name = input.value.trim();

    if (name) {
        const tagsContainer = document.getElementById('attendeeTags');
        const tag = document.createElement('div');
        tag.className = 'tag';
        tag.innerHTML = '<span>' + name + '</span><span class="tag-remove" onclick="this.parentElement.remove(); updateAttendees()">×</span>';
        tagsContainer.appendChild(tag);
        input.value = '';
        updateAttendees();
    }
}

// 참석자 목록 업데이트
function updateAttendees() {
    const tags = document.querySelectorAll('#attendeeTags .tag span:first-child');
    const names = [];
    tags.forEach((tag, index) => {
        if (index > 0) { // 작성자 제외
            names.push(tag.textContent);
        }
    });
    document.getElementById('attendeesHidden').value = names.join(', ');
}

// Enter 키로 참석자 추가
document.getElementById('attendeeInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        addAttendee();
    }
});

// 오디오 파일 선택
document.getElementById('audioFileInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        document.getElementById('selectedAudioName').textContent = '📎 ' + file.name;
        document.getElementById('selectedAudioInfo').style.display = 'block';
    }
});

// 오디오 파일 선택 취소
function clearAudioFile() {
    document.getElementById('audioFileInput').value = '';
    document.getElementById('selectedAudioInfo').style.display = 'none';
}

// 기존 오디오 파일 삭제 표시
function removeAudioFile() {
    document.getElementById('removeAudioInput').value = '1';
    document.getElementById('audioList').innerHTML = '<div style="padding: 12px; background: #f8d7da; border-radius: 6px; color: #721c24; font-size: 14px;">기존 녹음파일이 삭제됩니다.</div>';
}

// 폼 제출 - FormData 사용
document.getElementById('meetingForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const id = formData.get('id');
    formData.append('action', id ? 'update' : 'create');

    try {
        const response = await fetch(CRM_URL + '/api/common/meetings.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.success) {
            showToast(result.message || '저장되었습니다.', 'success');
            setTimeout(() => location.href = 'meetings.php', 1000);
        } else {
            showToast(result.message || '저장에 실패했습니다.', 'error');
        }
    } catch (error) {
        showToast('저장에 실패했습니다.', 'error');
    }
});

async function deleteMeeting() {
    if (!confirm('정말 삭제하시겠습니까?')) return;

    const id = document.querySelector('input[name="id"]').value;

    try {
        await apiPost(CRM_URL + '/api/common/meetings.php', { action: 'delete', id: id });
        showToast('삭제되었습니다.', 'success');
        setTimeout(() => location.href = 'meetings.php', 1000);
    } catch (error) {
        showToast('삭제에 실패했습니다.', 'error');
    }
}
</script>
SCRIPT;

include dirname(dirname(__DIR__)) . '/includes/footer.php';
?>
