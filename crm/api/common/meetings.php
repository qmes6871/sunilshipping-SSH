<?php
/**
 * CRM 회의록 관리 API
 */

require_once dirname(dirname(__DIR__)) . '/config.php';

// 로그인 확인
if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => '로그인이 필요합니다.'], 401);
}

$currentUser = getCurrentUser();
$pdo = getDB();

// GET: 회의록 조회
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = $_GET['id'] ?? null;

    if ($id) {
        // 단일 회의록 조회
        try {
            $stmt = $pdo->prepare("SELECT m.*, u.name as creator_name
                FROM " . CRM_MEETINGS_TABLE . " m
                LEFT JOIN " . CRM_USERS_TABLE . " u ON m.created_by = u.id
                WHERE m.id = ?");
            $stmt->execute([$id]);
            $meeting = $stmt->fetch();

            if ($meeting) {
                // 참석자 조회
                $stmt = $pdo->prepare("SELECT * FROM " . CRM_MEETING_ATTENDEES_TABLE . " WHERE meeting_id = ? ORDER BY is_creator DESC, attendee_name ASC");
                $stmt->execute([$id]);
                $meeting['attendees'] = $stmt->fetchAll();

                successResponse($meeting);
            } else {
                errorResponse('회의록을 찾을 수 없습니다.', 404);
            }
        } catch (Exception $e) {
            errorResponse('조회 중 오류가 발생했습니다.');
        }
    } else {
        // 목록 조회
        try {
            $stmt = $pdo->prepare("SELECT m.*, u.name as creator_name
                FROM " . CRM_MEETINGS_TABLE . " m
                LEFT JOIN " . CRM_USERS_TABLE . " u ON m.created_by = u.id
                ORDER BY meeting_date DESC");
            $stmt->execute();
            $meetings = $stmt->fetchAll();
            successResponse($meetings);
        } catch (Exception $e) {
            errorResponse('조회 중 오류가 발생했습니다.');
        }
    }
    exit;
}

// POST: 회의록 생성/수정/삭제
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? 'create';

    switch ($action) {
        case 'create':
            // 회의록 생성
            $title = trim($input['title'] ?? '');
            $meetingDate = $input['meeting_date'] ?? null;
            $meetingTime = $input['meeting_time'] ?: null;
            $location = trim($input['location'] ?? '');
            $meetingType = trim($input['meeting_type'] ?? '');
            $agenda = trim($input['agenda'] ?? '');
            $content = trim($input['content'] ?? '');
            $decisions = trim($input['decisions'] ?? '');
            $actionItems = trim($input['action_items'] ?? '');
            $nextMeetingDate = $input['next_meeting_date'] ?: null;
            $attendees = trim($input['attendees'] ?? '');

            if (empty($title)) {
                errorResponse('제목을 입력해주세요.');
            }
            if (empty($meetingDate)) {
                errorResponse('회의 일자를 입력해주세요.');
            }

            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("INSERT INTO " . CRM_MEETINGS_TABLE . "
                    (title, meeting_date, meeting_time, location, meeting_type, agenda, content, decisions, action_items, next_meeting_date, created_by, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([
                    $title,
                    $meetingDate,
                    $meetingTime,
                    $location,
                    $meetingType,
                    $agenda,
                    $content,
                    $decisions,
                    $actionItems,
                    $nextMeetingDate,
                    $currentUser['crm_user_id']
                ]);

                $meetingId = $pdo->lastInsertId();

                // 참석자 저장
                if ($attendees) {
                    $attendeeList = array_filter(array_map('trim', explode(',', $attendees)));
                    $stmt = $pdo->prepare("INSERT INTO " . CRM_MEETING_ATTENDEES_TABLE . " (meeting_id, attendee_name, is_creator) VALUES (?, ?, ?)");

                    // 작성자 추가
                    $creatorName = $currentUser['name'] ?? $currentUser['mb_name'] ?? '';
                    $stmt->execute([$meetingId, $creatorName, 1]);

                    foreach ($attendeeList as $attendee) {
                        if ($attendee !== $creatorName) {
                            $stmt->execute([$meetingId, $attendee, 0]);
                        }
                    }
                }

                $pdo->commit();
                successResponse(['id' => $meetingId], '회의록이 등록되었습니다.');
            } catch (Exception $e) {
                $pdo->rollBack();
                errorResponse('등록 중 오류가 발생했습니다.');
            }
            break;

        case 'update':
            // 회의록 수정
            $id = $input['id'] ?? null;
            $title = trim($input['title'] ?? '');
            $meetingDate = $input['meeting_date'] ?? null;
            $meetingTime = $input['meeting_time'] ?: null;
            $location = trim($input['location'] ?? '');
            $meetingType = trim($input['meeting_type'] ?? '');
            $agenda = trim($input['agenda'] ?? '');
            $content = trim($input['content'] ?? '');
            $decisions = trim($input['decisions'] ?? '');
            $actionItems = trim($input['action_items'] ?? '');
            $nextMeetingDate = $input['next_meeting_date'] ?: null;
            $attendees = trim($input['attendees'] ?? '');

            if (!$id) {
                errorResponse('회의록 ID가 필요합니다.');
            }
            if (empty($title)) {
                errorResponse('제목을 입력해주세요.');
            }
            if (empty($meetingDate)) {
                errorResponse('회의 일자를 입력해주세요.');
            }

            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("UPDATE " . CRM_MEETINGS_TABLE . "
                    SET title = ?, meeting_date = ?, meeting_time = ?, location = ?, meeting_type = ?,
                        agenda = ?, content = ?, decisions = ?, action_items = ?, next_meeting_date = ?, updated_at = NOW()
                    WHERE id = ?");
                $stmt->execute([
                    $title,
                    $meetingDate,
                    $meetingTime,
                    $location,
                    $meetingType,
                    $agenda,
                    $content,
                    $decisions,
                    $actionItems,
                    $nextMeetingDate,
                    $id
                ]);

                // 기존 참석자 삭제 후 재입력
                $stmt = $pdo->prepare("DELETE FROM " . CRM_MEETING_ATTENDEES_TABLE . " WHERE meeting_id = ?");
                $stmt->execute([$id]);

                if ($attendees) {
                    $attendeeList = array_filter(array_map('trim', explode(',', $attendees)));
                    $stmt = $pdo->prepare("INSERT INTO " . CRM_MEETING_ATTENDEES_TABLE . " (meeting_id, attendee_name, is_creator) VALUES (?, ?, ?)");

                    // 작성자 추가
                    $creatorName = $currentUser['name'] ?? $currentUser['mb_name'] ?? '';
                    $stmt->execute([$id, $creatorName, 1]);

                    foreach ($attendeeList as $attendee) {
                        if ($attendee !== $creatorName) {
                            $stmt->execute([$id, $attendee, 0]);
                        }
                    }
                }

                $pdo->commit();
                successResponse(null, '회의록이 수정되었습니다.');
            } catch (Exception $e) {
                $pdo->rollBack();
                errorResponse('수정 중 오류가 발생했습니다.');
            }
            break;

        case 'delete':
            // 회의록 삭제
            $id = $input['id'] ?? null;

            if (!$id) {
                errorResponse('회의록 ID가 필요합니다.');
            }

            try {
                $pdo->beginTransaction();

                // 참석자 삭제
                $stmt = $pdo->prepare("DELETE FROM " . CRM_MEETING_ATTENDEES_TABLE . " WHERE meeting_id = ?");
                $stmt->execute([$id]);

                // 회의록 삭제
                $stmt = $pdo->prepare("DELETE FROM " . CRM_MEETINGS_TABLE . " WHERE id = ?");
                $stmt->execute([$id]);

                $pdo->commit();
                successResponse(null, '회의록이 삭제되었습니다.');
            } catch (Exception $e) {
                $pdo->rollBack();
                errorResponse('삭제 중 오류가 발생했습니다.');
            }
            break;

        default:
            errorResponse('잘못된 요청입니다.');
    }
    exit;
}

errorResponse('지원하지 않는 메서드입니다.', 405);
