# Google Sheets API 사용 가이드

이 디렉토리에는 Google Sheets API를 사용하여 구글 시트 데이터를 읽고 쓰는 PHP 스크립트가 포함되어 있습니다.

## 설치

### 1. Composer 설치 (아직 설치하지 않은 경우)

Windows에서 Composer를 설치하려면:
- https://getcomposer.org/download/ 에서 다운로드
- 또는 chocolatey 사용: `choco install composer`

### 2. Google API Client 라이브러리 설치

```bash
cd c:\Users\Administrator\AppData\Roaming\Code\User\globalStorage\humy2833.ftp-simple\remote-workspace-temp\4bf14456ccad48ede66553ef3692feb5\www
composer require google/apiclient:"^2.0"
```

## 파일 설명

### 1. `webtracing-service-account.json`
- Google Cloud 서비스 계정 인증 파일
- 이 파일을 통해 구글 시트에 접근합니다

### 2. `read_sheets.php`
- 구글 시트 데이터를 읽어오는 간단한 스크립트
- 읽은 데이터를 `sheets_data.json` 파일로 저장합니다

**사용법:**
```bash
php read_sheets.php
```

### 3. `write_sheets.php`
- 구글 시트에 데이터를 쓰는 간단한 스크립트
- A1 셀에 테스트 데이터를 작성합니다

**사용법:**
```bash
php write_sheets.php
```

### 4. `sheets_manager.php` (권장)
- 구글 시트 관리를 위한 클래스 기반 스크립트
- 읽기, 쓰기, 추가, 삭제 등 다양한 기능 제공

**사용법:**
```bash
php sheets_manager.php
```

## SheetsManager 클래스 사용 예시

```php
<?php
require_once 'sheets_manager.php';

$manager = new SheetsManager();

// 1. 시트 정보 가져오기
$info = $manager->getSheetInfo();
print_r($info);

// 2. 데이터 읽기
$data = $manager->read('Sheet1!A1:E10');
print_r($data);

// 3. 데이터 쓰기 (기존 데이터 덮어쓰기)
$manager->write('Sheet1!A1:B2', [
    ['이름', '나이'],
    ['홍길동', '30']
]);

// 4. 데이터 추가 (기존 데이터 뒤에 추가)
$manager->append('Sheet1!A:B', [
    ['김철수', '25']
]);

// 5. 데이터 삭제
$manager->clear('Sheet1!A1:B10');
?>
```

## 구글 시트 정보

- **스프레드시트 ID:** `1wABaGqguNcEDO3Vw7BMo8InlEnK2EqC9ma5GOR_pDpE`
- **URL:** https://docs.google.com/spreadsheets/d/1wABaGqguNcEDO3Vw7BMo8InlEnK2EqC9ma5GOR_pDpE/
- **서비스 계정:** `trace-sheets-reader@webtracing.iam.gserviceaccount.com`

## 중요 사항

1. **권한 설정:** 구글 시트를 서비스 계정 이메일(`trace-sheets-reader@webtracing.iam.gserviceaccount.com`)과 공유해야 합니다.
   - 읽기만 필요한 경우: "뷰어" 권한
   - 편집도 필요한 경우: "편집자" 권한 ✅ (현재 부여됨)

2. **범위(Range) 지정 형식:**
   - `Sheet1!A1:B10` - Sheet1의 A1부터 B10까지
   - `Sheet1!A:A` - Sheet1의 A열 전체
   - `Sheet1` - Sheet1 전체

3. **보안:**
   - `webtracing-service-account.json` 파일은 민감한 정보입니다
   - Git에 커밋하지 마세요 (`.gitignore`에 추가 권장)

## 트러블슈팅

### 오류: "Class 'Google\Client' not found"
→ Composer로 google/apiclient 패키지를 설치하세요

### 오류: "The caller does not have permission"
→ 구글 시트가 서비스 계정과 공유되었는지 확인하세요

### 오류: "Invalid Credentials"
→ `webtracing-service-account.json` 파일 경로가 올바른지 확인하세요
