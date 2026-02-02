-- 고객 서류 업로드 테이블 생성 SQL

-- 기존 테이블이 있으면 삭제 (주의: 실제 운영 환경에서는 DROP TABLE 전에 백업 필요)
-- DROP TABLE IF EXISTS customer_documents;

-- 고객 서류 업로드 테이블 생성
CREATE TABLE IF NOT EXISTS customer_documents (
    doc_id INT AUTO_INCREMENT PRIMARY KEY COMMENT '서류 고유 ID',
    ct_id INT NOT NULL COMMENT '컨테이너 ID (container_tracking.ct_id 참조)',
    customer_id VARCHAR(50) NOT NULL COMMENT '고객 ID (고객 식별자)',
    doc_type VARCHAR(50) NOT NULL COMMENT '서류 유형 (invoice, bl, packing_list, certificate, etc.)',
    file_name VARCHAR(255) NOT NULL COMMENT '저장된 파일명',
    original_name VARCHAR(255) NOT NULL COMMENT '원본 파일명',
    file_path VARCHAR(500) NOT NULL COMMENT '파일 저장 경로',
    file_size INT NOT NULL COMMENT '파일 크기 (bytes)',
    upload_date DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '업로드 일시',
    upload_ip VARCHAR(45) DEFAULT NULL COMMENT '업로드 IP 주소',
    status ENUM('active', 'deleted') DEFAULT 'active' COMMENT '서류 상태',
    memo TEXT DEFAULT NULL COMMENT '메모',

    INDEX idx_ct_id (ct_id),
    INDEX idx_customer_id (customer_id),
    INDEX idx_doc_type (doc_type),
    INDEX idx_upload_date (upload_date),

    CONSTRAINT fk_customer_documents_ct_id
        FOREIGN KEY (ct_id)
        REFERENCES container_tracking(ct_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='고객 서류 업로드 테이블';

-- 초기 데이터 확인용 쿼리 (주석 처리)
-- SELECT * FROM customer_documents ORDER BY upload_date DESC;

-- 테이블 구조 확인
-- DESCRIBE customer_documents;

-- 인덱스 확인
-- SHOW INDEX FROM customer_documents;
