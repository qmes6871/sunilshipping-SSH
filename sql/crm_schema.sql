-- ================================================
-- 선일쉬핑 CRM 데이터베이스 스키마
-- 생성일: 2026-01-06
-- 기준: 그누보드 g5_member 테이블 연동
-- ================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ================================================
-- 1. 공통 테이블
-- ================================================

-- CRM 사용자 확장 정보 (그누보드 회원과 연동)
CREATE TABLE IF NOT EXISTS `crm_users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `mb_id` VARCHAR(20) NOT NULL COMMENT '그누보드 회원 아이디',
    `name` VARCHAR(100) NOT NULL COMMENT '이름',
    `department` ENUM('logistics', 'agricultural', 'pellet', 'support', 'admin') NOT NULL DEFAULT 'support' COMMENT '부서',
    `position` VARCHAR(50) COMMENT '직급',
    `phone` VARCHAR(20) COMMENT '전화번호',
    `email` VARCHAR(100) COMMENT '이메일',
    `profile_photo` VARCHAR(255) COMMENT '프로필 사진 경로',
    `memo` TEXT COMMENT '메모',
    `is_active` TINYINT(1) DEFAULT 1 COMMENT '활성 여부',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `idx_mb_id` (`mb_id`),
    INDEX `idx_department` (`department`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='CRM 사용자 확장 정보';

-- 파일 업로드 통합 테이블
CREATE TABLE IF NOT EXISTS `crm_files` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `entity_type` VARCHAR(50) NOT NULL COMMENT '연관 엔티티 타입 (customer, activity, meeting, kms 등)',
    `entity_id` INT NOT NULL COMMENT '연관 엔티티 ID',
    `file_name` VARCHAR(255) NOT NULL COMMENT '원본 파일명',
    `file_path` VARCHAR(500) NOT NULL COMMENT '저장 경로',
    `file_size` INT COMMENT '파일 크기 (bytes)',
    `file_type` VARCHAR(100) COMMENT 'MIME 타입',
    `uploaded_by` INT COMMENT '업로드한 사용자',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_entity` (`entity_type`, `entity_id`),
    INDEX `idx_uploaded_by` (`uploaded_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='파일 업로드';

-- 댓글 테이블
CREATE TABLE IF NOT EXISTS `crm_comments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `entity_type` VARCHAR(50) NOT NULL COMMENT '연관 엔티티 타입',
    `entity_id` INT NOT NULL COMMENT '연관 엔티티 ID',
    `parent_id` INT DEFAULT NULL COMMENT '부모 댓글 ID (대댓글용)',
    `content` TEXT NOT NULL COMMENT '댓글 내용',
    `comment_type` ENUM('comment', 'hint', 'feedback') DEFAULT 'comment' COMMENT '댓글 유형',
    `created_by` INT NOT NULL COMMENT '작성자',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `is_deleted` TINYINT(1) DEFAULT 0 COMMENT '삭제 여부',
    INDEX `idx_entity` (`entity_type`, `entity_id`),
    INDEX `idx_parent` (`parent_id`),
    INDEX `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='댓글';

-- 활동 로그 테이블
CREATE TABLE IF NOT EXISTS `crm_activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `entity_type` VARCHAR(50) NOT NULL COMMENT '엔티티 타입',
    `entity_id` INT NOT NULL COMMENT '엔티티 ID',
    `action` VARCHAR(50) NOT NULL COMMENT '액션 (create, update, delete 등)',
    `details` JSON COMMENT '상세 정보',
    `user_id` INT COMMENT '수행한 사용자',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_entity` (`entity_type`, `entity_id`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='활동 로그';

-- ================================================
-- 2. 국제물류 모듈
-- ================================================

-- 국제물류 바이어/고객 테이블
CREATE TABLE IF NOT EXISTS `crm_intl_customers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(200) NOT NULL COMMENT '고객/회사명',
    `customer_type` ENUM('buyer', 'partner', 'agent') DEFAULT 'buyer' COMMENT '고객 유형',
    `phone` VARCHAR(50) COMMENT '전화번호',
    `whatsapp` VARCHAR(50) COMMENT '왓츠앱',
    `email` VARCHAR(100) COMMENT '이메일',
    `nationality` VARCHAR(100) COMMENT '국적',
    `export_country` VARCHAR(200) COMMENT '최종 수출국',
    `address` TEXT COMMENT '주소',
    `passport_info` TEXT COMMENT '여권 정보',
    `photo` VARCHAR(255) COMMENT '고객 사진',
    `passport_photo` VARCHAR(255) COMMENT '여권 사진',
    `bank_name` VARCHAR(100) COMMENT '은행명',
    `account_number` VARCHAR(100) COMMENT '계좌번호',
    `account_holder` VARCHAR(100) COMMENT '예금주',
    `swift_code` VARCHAR(50) COMMENT 'SWIFT 코드',
    `assigned_sales` INT COMMENT '담당 영업사원',
    `status` ENUM('active', 'inactive', 'pending') DEFAULT 'active' COMMENT '상태',
    `created_by` INT COMMENT '등록자',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_name` (`name`),
    INDEX `idx_nationality` (`nationality`),
    INDEX `idx_status` (`status`),
    INDEX `idx_assigned` (`assigned_sales`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='국제물류 바이어/고객';

-- 국제물류 영업활동 테이블
CREATE TABLE IF NOT EXISTS `crm_intl_activities` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT NOT NULL COMMENT '고객 ID',
    `activity_date` DATE NOT NULL COMMENT '활동 날짜',
    `activity_type` ENUM('lead', 'contact', 'proposal', 'negotiation', 'progress', 'completed') NOT NULL COMMENT '활동 유형',
    `booking_completed` TINYINT(1) DEFAULT 0 COMMENT '부킹 완료 여부',
    `meeting_purpose` TEXT COMMENT '미팅 목적',
    `activity_content` TEXT COMMENT '활동 내용',
    `activity_result` TEXT COMMENT '활동 결과',
    `followup_items` TEXT COMMENT '후속 조치',
    `created_by` INT NOT NULL COMMENT '작성자',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_customer` (`customer_id`),
    INDEX `idx_date` (`activity_date`),
    INDEX `idx_type` (`activity_type`),
    FOREIGN KEY (`customer_id`) REFERENCES `crm_intl_customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='국제물류 영업활동';

-- 국제물류 성과 테이블
CREATE TABLE IF NOT EXISTS `crm_intl_performance` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `period_type` ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly') NOT NULL COMMENT '기간 유형',
    `period_year` YEAR NOT NULL COMMENT '년도',
    `period_month` TINYINT COMMENT '월',
    `period_quarter` TINYINT COMMENT '분기',
    `period_week` TINYINT COMMENT '주차',
    `period_date` DATE COMMENT '일자',
    `region` VARCHAR(100) NOT NULL COMMENT '지역 (쿠잔트, 알마티 등)',
    `performance_count` INT DEFAULT 0 COMMENT '실적 건수',
    `recorded_by` INT NOT NULL COMMENT '기록자',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_period` (`period_type`, `period_year`, `period_month`),
    INDEX `idx_region` (`region`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='국제물류 성과';

-- ================================================
-- 3. 농산물 모듈
-- ================================================

-- 농산물 고객 테이블
CREATE TABLE IF NOT EXISTS `crm_agri_customers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_name` VARCHAR(200) NOT NULL COMMENT '상호명',
    `business_number` VARCHAR(20) COMMENT '사업자등록번호',
    `representative_name` VARCHAR(100) COMMENT '대표자명',
    `phone` VARCHAR(50) COMMENT '전화번호',
    `address` TEXT COMMENT '주소',
    `bank_name` VARCHAR(100) COMMENT '은행명',
    `account_number` VARCHAR(100) COMMENT '계좌번호',
    `account_holder` VARCHAR(100) COMMENT '예금주',
    `product_categories` JSON COMMENT '취급 품목',
    `assigned_sales` INT COMMENT '담당 영업사원',
    `status` ENUM('active', 'inactive') DEFAULT 'active' COMMENT '상태',
    `created_by` INT COMMENT '등록자',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_company` (`company_name`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='농산물 고객';

-- 농산물 활동 테이블
CREATE TABLE IF NOT EXISTS `crm_agri_activities` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT NOT NULL COMMENT '고객 ID',
    `activity_type` VARCHAR(50) NOT NULL COMMENT '활동 유형',
    `activity_date` DATE NOT NULL COMMENT '활동 날짜',
    `description` TEXT COMMENT '설명',
    `meeting_purpose` TEXT COMMENT '미팅 목적',
    `content` TEXT COMMENT '내용',
    `result` TEXT COMMENT '결과',
    `followup` TEXT COMMENT '후속 조치',
    `amount` DECIMAL(15,2) COMMENT '금액',
    `created_by` INT NOT NULL COMMENT '작성자',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_customer` (`customer_id`),
    INDEX `idx_date` (`activity_date`),
    FOREIGN KEY (`customer_id`) REFERENCES `crm_agri_customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='농산물 활동';

-- 농산물 성과 테이블
CREATE TABLE IF NOT EXISTS `crm_agri_performance` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `period_type` ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly') NOT NULL COMMENT '기간 유형',
    `period_year` YEAR NOT NULL COMMENT '년도',
    `period_month` TINYINT COMMENT '월',
    `product_category` VARCHAR(100) COMMENT '품목',
    `quantity` DECIMAL(10,2) COMMENT '수량',
    `unit` VARCHAR(20) DEFAULT '톤' COMMENT '단위',
    `recorded_by` INT NOT NULL COMMENT '기록자',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_period` (`period_type`, `period_year`, `period_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='농산물 성과';

-- ================================================
-- 4. 우드펠렛 모듈
-- ================================================

-- 우드펠렛 거래처 테이블
CREATE TABLE IF NOT EXISTS `crm_pellet_traders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_name` VARCHAR(200) NOT NULL COMMENT '거래처명',
    `business_number` VARCHAR(20) COMMENT '사업자등록번호',
    `representative_name` VARCHAR(100) COMMENT '대표자명',
    `contact_person` VARCHAR(100) COMMENT '담당자',
    `phone` VARCHAR(50) COMMENT '전화번호',
    `email` VARCHAR(100) COMMENT '이메일',
    `address` TEXT COMMENT '주소',
    `trade_type` ENUM('online', 'offline_wholesale', 'offline_retail', 'bulk') DEFAULT 'offline_retail' COMMENT '거래 유형',
    `annual_volume` DECIMAL(10,2) COMMENT '연간 거래량',
    `bank_name` VARCHAR(100) COMMENT '은행명',
    `account_number` VARCHAR(100) COMMENT '계좌번호',
    `payment_method` VARCHAR(100) COMMENT '결제 방식',
    `contract_date` DATE COMMENT '계약일',
    `contract_period` VARCHAR(50) COMMENT '계약 기간',
    `assigned_sales` INT COMMENT '담당 영업사원',
    `status` ENUM('active', 'inactive', 'pending') DEFAULT 'active' COMMENT '상태',
    `created_by` INT COMMENT '등록자',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_company` (`company_name`),
    INDEX `idx_trade_type` (`trade_type`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='우드펠렛 거래처';

-- 우드펠렛 성과 테이블
CREATE TABLE IF NOT EXISTS `crm_pellet_performance` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `period_type` ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly') NOT NULL COMMENT '기간 유형',
    `period_year` YEAR NOT NULL COMMENT '년도',
    `period_month` TINYINT COMMENT '월',
    `trade_type` VARCHAR(50) COMMENT '거래 유형',
    `quantity` DECIMAL(10,2) COMMENT '수량',
    `unit` VARCHAR(20) DEFAULT '톤' COMMENT '단위',
    `recorded_by` INT NOT NULL COMMENT '기록자',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_period` (`period_type`, `period_year`, `period_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='우드펠렛 성과';

-- ================================================
-- 5. 공통 기능 테이블
-- ================================================

-- 공지사항 테이블
CREATE TABLE IF NOT EXISTS `crm_notices` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `notice_type` ENUM('company', 'department', 'warning', 'urgent') NOT NULL COMMENT '공지 유형',
    `department` VARCHAR(50) COMMENT '대상 부서 (NULL이면 전체)',
    `title` VARCHAR(255) NOT NULL COMMENT '제목',
    `content` TEXT COMMENT '내용',
    `is_important` TINYINT(1) DEFAULT 0 COMMENT '중요 여부',
    `view_count` INT DEFAULT 0 COMMENT '조회수',
    `created_by` INT NOT NULL COMMENT '작성자',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_type` (`notice_type`),
    INDEX `idx_department` (`department`),
    INDEX `idx_created` (`created_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='공지사항';

-- 공지사항 읽음 확인 테이블
CREATE TABLE IF NOT EXISTS `crm_notice_reads` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `notice_id` INT NOT NULL COMMENT '공지 ID',
    `user_id` INT NOT NULL COMMENT '사용자 ID',
    `read_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `idx_notice_user` (`notice_id`, `user_id`),
    FOREIGN KEY (`notice_id`) REFERENCES `crm_notices`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='공지 읽음 확인';

-- 할일 테이블
CREATE TABLE IF NOT EXISTS `crm_todos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL COMMENT '사용자 ID',
    `title` VARCHAR(255) NOT NULL COMMENT '제목',
    `description` TEXT COMMENT '설명',
    `priority` ENUM('high', 'medium', 'low') DEFAULT 'medium' COMMENT '우선순위',
    `category` VARCHAR(50) COMMENT '카테고리',
    `deadline` DATE COMMENT '마감일',
    `is_completed` TINYINT(1) DEFAULT 0 COMMENT '완료 여부',
    `completed_at` TIMESTAMP NULL COMMENT '완료 시간',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_deadline` (`deadline`),
    INDEX `idx_completed` (`is_completed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='할일';

-- 회의록 테이블
CREATE TABLE IF NOT EXISTS `crm_meetings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL COMMENT '회의 제목',
    `meeting_date` DATE NOT NULL COMMENT '회의 날짜',
    `meeting_time` TIME COMMENT '회의 시간',
    `location` VARCHAR(200) COMMENT '회의 장소',
    `meeting_type` VARCHAR(50) COMMENT '회의 유형',
    `agenda` TEXT COMMENT '안건',
    `content` LONGTEXT COMMENT '회의 내용',
    `decisions` TEXT COMMENT '결정 사항',
    `action_items` TEXT COMMENT '액션 아이템',
    `next_meeting_date` DATE COMMENT '다음 회의 일정',
    `created_by` INT NOT NULL COMMENT '작성자',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_date` (`meeting_date`),
    INDEX `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='회의록';

-- 회의 참석자 테이블
CREATE TABLE IF NOT EXISTS `crm_meeting_attendees` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `meeting_id` INT NOT NULL COMMENT '회의 ID',
    `attendee_name` VARCHAR(100) NOT NULL COMMENT '참석자명',
    `is_creator` TINYINT(1) DEFAULT 0 COMMENT '작성자 여부',
    FOREIGN KEY (`meeting_id`) REFERENCES `crm_meetings`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='회의 참석자';

-- KMS 문서 테이블
CREATE TABLE IF NOT EXISTS `crm_kms_documents` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL COMMENT '문서 제목',
    `part` ENUM('international', 'agricultural', 'pellet', 'trade') NOT NULL COMMENT '파트',
    `classification` ENUM('guide', 'checklist', 'notice') NOT NULL COMMENT '분류',
    `content` LONGTEXT COMMENT '문서 내용',
    `tags` VARCHAR(500) COMMENT '태그 (쉼표 구분)',
    `view_count` INT DEFAULT 0 COMMENT '조회수',
    `created_by` INT NOT NULL COMMENT '작성자',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_part` (`part`),
    INDEX `idx_classification` (`classification`),
    INDEX `idx_created` (`created_at` DESC),
    FULLTEXT INDEX `idx_search` (`title`, `content`, `tags`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='KMS 문서';

-- 푸시 알림 테이블
CREATE TABLE IF NOT EXISTS `crm_push_notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `campaign_name` VARCHAR(200) COMMENT '캠페인명',
    `notification_type` ENUM('immediate', 'scheduled', 'draft') NOT NULL COMMENT '발송 유형',
    `target_audience` VARCHAR(100) COMMENT '대상',
    `channel` VARCHAR(50) COMMENT '채널 (app, web, sms)',
    `title` VARCHAR(255) NOT NULL COMMENT '제목',
    `message` TEXT COMMENT '메시지',
    `link_path` VARCHAR(500) COMMENT '링크 경로',
    `scheduled_time` TIMESTAMP NULL COMMENT '예약 시간',
    `status` ENUM('draft', 'scheduled', 'sent', 'failed') DEFAULT 'draft' COMMENT '상태',
    `target_count` INT DEFAULT 0 COMMENT '대상 수',
    `success_count` INT DEFAULT 0 COMMENT '성공 수',
    `failure_count` INT DEFAULT 0 COMMENT '실패 수',
    `created_by` INT NOT NULL COMMENT '작성자',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `sent_at` TIMESTAMP NULL COMMENT '발송 시간',
    INDEX `idx_status` (`status`),
    INDEX `idx_scheduled` (`scheduled_time`),
    INDEX `idx_created` (`created_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='푸시 알림';

-- 루트별 주의사항 테이블
CREATE TABLE IF NOT EXISTS `crm_route_warnings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `route_name` VARCHAR(100) NOT NULL COMMENT '루트명',
    `status` ENUM('urgent', 'important', 'normal') DEFAULT 'normal' COMMENT '상태',
    `title` VARCHAR(255) NOT NULL COMMENT '제목',
    `content` TEXT COMMENT '내용',
    `attachment_path` VARCHAR(500) COMMENT '첨부파일 경로',
    `created_by` INT NOT NULL COMMENT '작성자',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_route` (`route_name`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='루트별 주의사항';

-- 개인 메모 테이블
CREATE TABLE IF NOT EXISTS `crm_user_memos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL COMMENT '사용자 ID',
    `content` TEXT COMMENT '메모 내용',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='개인 메모';

-- 개인 파일 테이블
CREATE TABLE IF NOT EXISTS `crm_user_files` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL COMMENT '사용자 ID',
    `file_name` VARCHAR(255) NOT NULL COMMENT '파일명',
    `file_path` VARCHAR(500) NOT NULL COMMENT '파일 경로',
    `file_size` INT COMMENT '파일 크기',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='개인 파일';

-- ================================================
-- 6. 기본 데이터 삽입
-- ================================================

-- 기본 루트 설정
INSERT INTO `crm_route_warnings` (`route_name`, `status`, `title`, `content`, `created_by`) VALUES
('중앙아시아 철도', 'normal', '중앙아시아 철도 운송 기본 안내', '타슈켄트, 알마티, 비슈케크 노선 운송 시 기본 주의사항입니다.', 1),
('중동·아프리카 해상', 'normal', '중동·아프리카 해상 운송 기본 안내', '리비아, 이집트, 두바이 등 해상 운송 시 기본 주의사항입니다.', 1),
('러시아 육로', 'normal', '러시아 육로 운송 기본 안내', '블라디보스토크, 모스크바 노선 운송 시 기본 주의사항입니다.', 1),
('유럽 항공', 'normal', '유럽 항공 운송 기본 안내', '독일, 폴란드, 체코 등 항공 운송 시 기본 주의사항입니다.', 1),
('동남아시아', 'normal', '동남아시아 운송 기본 안내', '베트남, 태국, 필리핀 등 운송 시 기본 주의사항입니다.', 1),
('국내 물류', 'normal', '국내 물류 기본 안내', '전국 택배 및 화물 운송 시 기본 주의사항입니다.', 1);

SET FOREIGN_KEY_CHECKS = 1;

-- ================================================
-- 완료
-- ================================================
