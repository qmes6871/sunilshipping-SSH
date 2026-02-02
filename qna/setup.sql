-- QnA 게시판 테이블 생성 SQL

-- QnA 게시글 테이블
CREATE TABLE IF NOT EXISTS `qna` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `writer_id` varchar(50) NOT NULL COMMENT '작성자 ID',
  `writer` varchar(100) NOT NULL COMMENT '작성자 이름',
  `category` varchar(50) NOT NULL COMMENT '카테고리',
  `title` varchar(200) NOT NULL COMMENT '제목',
  `content` text NOT NULL COMMENT '내용',
  `email` varchar(100) DEFAULT NULL COMMENT '답변받을 이메일',
  `view_count` int(11) DEFAULT 0 COMMENT '조회수',
  `status` varchar(20) DEFAULT 'active' COMMENT '상태 (active/deleted)',
  `is_answered` tinyint(1) DEFAULT 0 COMMENT '답변 여부',
  `created_at` datetime NOT NULL COMMENT '작성일',
  `updated_at` datetime NOT NULL COMMENT '수정일',
  PRIMARY KEY (`id`),
  KEY `idx_writer_id` (`writer_id`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='QnA 게시판';

-- QnA 답변 테이블
CREATE TABLE IF NOT EXISTS `qna_answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `qna_id` int(11) NOT NULL COMMENT 'QnA 게시글 ID',
  `writer_id` varchar(50) NOT NULL COMMENT '답변자 ID',
  `writer` varchar(100) NOT NULL COMMENT '답변자 이름',
  `content` text NOT NULL COMMENT '답변 내용',
  `is_admin` tinyint(1) DEFAULT 0 COMMENT '관리자 답변 여부',
  `created_at` datetime NOT NULL COMMENT '작성일',
  PRIMARY KEY (`id`),
  KEY `idx_qna_id` (`qna_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_qna_answers_qna` FOREIGN KEY (`qna_id`) REFERENCES `qna` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='QnA 답변';

-- 테스트 데이터 삽입 (선택사항)
INSERT INTO `qna` (`writer_id`, `writer`, `category`, `title`, `content`, `email`, `view_count`, `status`, `is_answered`, `created_at`, `updated_at`)
VALUES
('test_user', '테스트유저', 'shipping', '운송 기간은 얼마나 걸리나요?', '미국으로 컨테이너를 보낼 예정인데 운송 기간이 얼마나 걸리는지 궁금합니다.', 'test@example.com', 15, 'active', 1, NOW(), NOW()),
('test_user2', '김철수', 'payment', '결제 방법 문의', '신용카드로 결제할 수 있나요? 다른 결제 방법도 있나요?', 'kim@example.com', 8, 'active', 0, NOW(), NOW()),
('test_user3', '이영희', 'tracking', '트래킹 번호가 안보여요', '제 컨테이너 트래킹 번호가 마이페이지에 표시되지 않는데 확인 부탁드립니다.', 'lee@example.com', 12, 'active', 1, NOW(), NOW());

-- 테스트 답변 데이터
INSERT INTO `qna_answers` (`qna_id`, `writer_id`, `writer`, `content`, `is_admin`, `created_at`)
VALUES
(1, 'admin', '관리자', '안녕하세요. 미국 서부(LA, 롱비치)까지는 평균 14-16일 정도 소요됩니다. 서부 항구 이외 지역은 20-25일 정도 예상하시면 됩니다. 자세한 사항은 예약 시 안내드리겠습니다.', 1, NOW()),
(3, 'admin', '관리자', '안녕하세요. 트래킹 번호는 출항 후 1-2일 이내에 등록됩니다. 아직 등록되지 않으셨다면 고객센터로 문의 부탁드립니다. 감사합니다.', 1, NOW());
