<?php
session_start();

// 예약 완료 세션 확인
if (empty($_SESSION['reservation_success'])) {
    header('Location: index.php');
    exit;
}

// 문서 헤더 + 폰트어썸
?><!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>예약 완료 - SUNIL SHIPPING</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    body { font-family: 'Apple SD Gothic Neo','Malgun Gothic',sans-serif; background:#f3f4f6; margin:0; }
    .wrap { max-width: 980px; margin: 28px auto; padding: 0 16px; }
    .title { font-size: 22px; font-weight: 800; color:#111827; margin: 8px 0 14px; }
    .sub { color:#6b7280; margin-bottom: 12px; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="title">예약 완료</div>
    <div class="sub">접수된 예약 정보를 안내드립니다.</div>
    <?php include __DIR__.'/reservation_success.php'; ?>
  </div>
</body>
</html>

