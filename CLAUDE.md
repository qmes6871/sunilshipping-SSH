# 선일쉬핑 프로젝트 작업 기록

## 클라이언트 호스팅 정보
- 호스트: sunilshipping.mycafe24.com
- FTP ID: sunilshipping
- FTP PW: sunil123!
- 웹 URL: https://sunilshipping.mycafe24.com

## 작업 이력

### 2026-03-04: 우드펠렛 대시보드 거래처 목록 오류 수정
**파일**: `/crm/pages/pellet/dashboard.php`

**문제**:
- "총 3개의 거래처가 검색되었습니다" 표시되지만 목록은 "등록된 거래처가 없습니다" 표시

**원인**:
- 거래처 개수 쿼리(COUNT)는 `crm_pellet_traders` 테이블만 조회하여 성공
- 거래처 목록 쿼리(SELECT)는 존재하지 않는 `crm_pellet_activity_comments`, `crm_pellet_activities` 테이블을 서브쿼리로 참조하여 오류 발생
- catch 블록에서 `$traders = []`로 설정되어 빈 목록 표시

**수정 내용** (91-145번 줄):
- 테이블 존재 여부를 먼저 확인 (`SHOW TABLES LIKE`)
- 테이블이 있으면 기존 서브쿼리 사용
- 테이블이 없으면 기본 거래처 정보만 조회 (댓글 관련 필드는 0/NULL)

**배포**: 로컬 서버에서 수정 후 FTP로 클라이언트 호스팅에 업로드
