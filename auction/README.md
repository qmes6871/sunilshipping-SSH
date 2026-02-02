# 🚗 경매 시스템 (MySQL 버전)

경매 입찰 시스템 - MySQL 데이터베이스 연동

## 📋 주요 기능

- ✅ 경매 상품 목록 및 상세 정보
- ✅ 실시간 입찰 기능
- ✅ 입찰 내역 조회 (입찰자 익명 처리)
- ✅ 실시간 카운트다운 타이머
- ✅ 최고 입찰자 표시
- ✅ 트랜잭션 처리로 데이터 무결성 보장

## 🗄️ 데이터베이스 테이블

### auctions (경매 정보)
| 컬럼명 | 타입 | 설명 |
|--------|------|------|
| id | INT | 고유 ID (자동증가) |
| title | VARCHAR(255) | 경매 제목 |
| description | TEXT | 상세 설명 |
| manufacturer | VARCHAR(100) | 제조사 |
| model | VARCHAR(100) | 모델 |
| year | INT | 연식 |
| mileage | INT | 주행거리 |
| transmission | VARCHAR(50) | 변속기 |
| fuel | VARCHAR(50) | 연료 |
| accident | VARCHAR(50) | 사고여부 |
| accident_detail | TEXT | 사고 상세 |
| start_price | DECIMAL(15,2) | 시작가 |
| current_price | DECIMAL(15,2) | 현재가 |
| high_bidder_name | VARCHAR(100) | 최고 입찰자 |
| image | VARCHAR(255) | 이미지 경로 |
| created_at | TIMESTAMP | 생성일시 |
| end_time | DATETIME | 종료일시 |
| status | VARCHAR(20) | 상태 (active/ended) |
| bid_count | INT | 입찰 횟수 |

### auction_bids (입찰 내역)
| 컬럼명 | 타입 | 설명 |
|--------|------|------|
| id | INT | 고유 ID (자동증가) |
| auction_id | INT | 경매 ID (FK) |
| user_id | INT | 사용자 ID |
| user_name | VARCHAR(100) | 사용자 이름 |
| bid_amount | DECIMAL(15,2) | 입찰 금액 |
| bid_date | TIMESTAMP | 입찰 일시 |

## 🔧 설치 방법

### 1단계: 데이터베이스 설정
`auction/db_config.php` 파일을 열어 데이터베이스 정보를 수정하세요:

```php
define('DB_HOST', 'localhost');      // 데이터베이스 호스트
define('DB_USER', 'root');           // 데이터베이스 사용자
define('DB_PASS', '');               // 데이터베이스 비밀번호
define('DB_NAME', 'auction_db');     // 데이터베이스 이름
```

### 2단계: 데이터베이스 생성
MySQL에서 데이터베이스를 생성하세요:

```sql
CREATE DATABASE auction_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3단계: 테이블 설치
브라우저에서 다음 URL에 접속:

```
http://your-domain.com/auction/install.php
```

"테이블 설치하기" 버튼을 클릭하면 필요한 테이블이 자동으로 생성됩니다.

## 📁 파일 구조

```
auction/
├── db_config.php       # 데이터베이스 설정 및 연결
├── install.php         # 테이블 설치 스크립트
├── index.php           # 경매 목록 (작성 필요)
├── view.php            # 경매 상세 보기
├── purchase.php        # 입찰하기
├── create.php          # 경매 생성 (작성 필요)
└── storage.php         # (JSON 버전, 미사용)
```

## 🚀 사용 방법

### 경매 상세 보기
```
auction/view.php?id={경매ID}
```

### 입찰하기
```
auction/purchase.php?id={경매ID}
```

로그인이 필요합니다. 로그인하지 않은 경우 자동으로 로그인 페이지로 이동합니다.

## 🔐 보안 기능

- ✅ Prepared Statement 사용 (SQL Injection 방지)
- ✅ 트랜잭션 처리 (데이터 무결성)
- ✅ 입찰자 정보 마스킹 (앞 2글자만 표시)
- ✅ XSS 방지 (htmlspecialchars 사용)

## 🎨 주요 특징

### 입찰자 익명 처리
- 다른 사용자: `홍길***`, `김철***`
- 본인: `홍길동 (나)` - 파란색 표시

### 실시간 업데이트
- 경매 종료 시간 카운트다운
- 1분마다 자동 새로고침 (진행중인 경매)
- 경매 종료 시 자동으로 입찰 버튼 비활성화

### 입찰 검증
- 최소 입찰 금액 확인
- 경매 상태 확인 (활성/종료)
- 경매 시간 확인

## 💡 테스트 데이터 삽입

```sql
-- 샘플 경매 데이터
INSERT INTO auctions (
    title, description, manufacturer, model, year, mileage,
    transmission, fuel, accident, start_price, current_price,
    end_time, status
) VALUES (
    '2020 현대 소나타',
    '깨끗한 차량입니다.',
    '현대',
    '소나타',
    2020,
    30000,
    '자동',
    '가솔린',
    '무사고',
    15000000,
    15000000,
    DATE_ADD(NOW(), INTERVAL 7 DAY),
    'active'
);
```

## 📞 문제 해결

### 데이터베이스 연결 실패
1. `db_config.php`의 설정 확인
2. MySQL 서버 실행 확인
3. 데이터베이스 존재 여부 확인
4. 사용자 권한 확인

### 테이블 생성 실패
1. 데이터베이스 사용자에게 CREATE TABLE 권한 확인
2. 데이터베이스 이름 확인
3. `install.php`의 에러 메시지 확인

## 🔄 JSON에서 MySQL로 마이그레이션

기존 JSON 파일 데이터를 MySQL로 이전하려면:

1. `data/auction/auctions.json` 파일 읽기
2. 각 경매를 INSERT문으로 변환
3. `data/auction/bids.json` 파일의 입찰 내역도 동일하게 처리

## 📝 라이선스

MIT License

---

**개발 완료 항목:**
- ✅ MySQL 데이터베이스 연동
- ✅ 경매 상세 보기 (view.php)
- ✅ 입찰하기 (purchase.php)
- ✅ 입찰자 익명 처리
- ✅ 실시간 카운트다운
- ✅ 테이블 설치 스크립트

**추가 개발 필요:**
- ⏳ 경매 목록 (index.php)
- ⏳ 경매 생성 (create.php)
- ⏳ 관리자 기능

