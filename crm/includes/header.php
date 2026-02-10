<?php
/**
 * CRM 헤더 (모든 페이지 상단)
 * 심플 상단 네비게이션 레이아웃
 */

// 페이지 제목 기본값
$pageTitle = $pageTitle ?? '선일쉬핑 CRM';
$pageSubtitle = $pageSubtitle ?? '';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?> - 선일쉬핑 CRM</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= CRM_URL ?>/assets/images/favicon.ico">

    <!-- CSS -->
    <link rel="stylesheet" href="<?= CRM_URL ?>/assets/css/style.css">

    <!-- Chart.js (성과 차트용) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Global JavaScript Variables -->
    <script>
        window.CRM_URL = '<?= CRM_URL ?>';
        window.CRM_UPLOAD_URL = '<?= CRM_UPLOAD_URL ?>';
        var CRM_URL = window.CRM_URL;
        var CRM_UPLOAD_URL = window.CRM_UPLOAD_URL;
    </script>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Noto Sans KR', sans-serif;
            background: #f8f9fa;
            color: #212529;
            line-height: 1.6;
            min-height: 100vh;
        }

        a { text-decoration: none; color: inherit; }

        /* 메인 컨테이너 - 상단 헤더 레이아웃 */
        .app-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* 메인 콘텐츠 */
        .main-content {
            flex: 1;
            width: 100%;
        }

        /* 페이지 콘텐츠 */
        .page-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px;
        }

        /* 페이지 헤더 */
        .page-header {
            margin-bottom: 24px;
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

        /* 카드 */
        .card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #212529;
        }

        .card-body {
            padding: 24px;
        }

        /* 버튼 */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #0d6efd;
            color: #fff;
        }

        .btn-primary:hover {
            background: #0b5ed7;
        }

        .btn-secondary {
            background: #6c757d;
            color: #fff;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-success {
            background: #28a745;
            color: #fff;
        }

        .btn-danger {
            background: #dc3545;
            color: #fff;
        }

        .btn-outline {
            background: #fff;
            border: 1px solid #dee2e6;
            color: #495057;
        }

        .btn-outline:hover {
            background: #f8f9fa;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }

        .btn-lg {
            padding: 14px 28px;
            font-size: 16px;
        }

        /* 폼 요소 */
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

        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: #0d6efd;
            box-shadow: 0 0 0 3px rgba(13,110,253,0.1);
        }

        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 12px;
            padding-right: 36px;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .form-text {
            font-size: 12px;
            color: #6c757d;
            margin-top: 4px;
        }

        /* 테이블 */
        .table-responsive {
            overflow-x: auto;
        }

        .table-container {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background: #f8f9fa;
            padding: 14px 16px;
            text-align: center;
            font-size: 13px;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
        }

        .table td {
            padding: 14px 16px;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
            text-align: center;
        }

        .table td.text-left {
            text-align: left;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
        }

        /* 배지 */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-primary { background: #e7f1ff; color: #0d6efd; }
        .badge-success { background: #d1e7dd; color: #0f5132; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #842029; }
        .badge-info { background: #cff4fc; color: #055160; }
        .badge-secondary { background: #e9ecef; color: #495057; }

        /* 알림 */
        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success { background: #d1e7dd; color: #0f5132; }
        .alert-danger { background: #f8d7da; color: #842029; }
        .alert-warning { background: #fff3cd; color: #856404; }
        .alert-info { background: #cff4fc; color: #055160; }

        /* 모달 */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }

        .modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .modal {
            background: #fff;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            transform: scale(0.9);
            transition: transform 0.3s;
        }

        .modal-overlay.show .modal {
            transform: scale(1);
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #6c757d;
        }

        .modal-body {
            padding: 24px;
        }

        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        /* 로딩 */
        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #e9ecef;
            border-top-color: #0d6efd;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* 페이지네이션 */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            padding: 24px 0;
        }

        .page-btn {
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            background: #fff;
            color: #495057;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }

        .page-btn:hover {
            background: #f8f9fa;
        }

        .page-btn.active {
            background: #0d6efd;
            color: #fff;
            border-color: #0d6efd;
        }

        .page-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* 결과 헤더 */
        .result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
            margin-bottom: 16px;
        }

        .result-count {
            font-size: 15px;
            color: #212529;
        }

        .result-count strong {
            color: #0d6efd;
            font-weight: 700;
        }

        /* 검색/필터 섹션 */
        .filter-section {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .filter-button {
            width: 100%;
            padding: 14px;
            background: #0d6efd;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .filter-button:hover {
            background: #0b5ed7;
        }

        .search-options {
            display: none;
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 6px;
        }

        .search-options.active {
            display: block;
        }

        .search-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 16px;
        }

        .search-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .search-field label {
            font-size: 13px;
            font-weight: 500;
            color: #495057;
        }

        .search-field input,
        .search-field select {
            padding: 10px 12px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: 14px;
            background: #fff;
        }

        .search-field input:focus,
        .search-field select:focus {
            outline: none;
            border-color: #0d6efd;
        }

        .search-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn-search {
            padding: 10px 24px;
            background: #0d6efd;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
        }

        .btn-reset {
            padding: 10px 24px;
            background: #fff;
            color: #6c757d;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
        }

        /* 상세보기 버튼 */
        .detail-btn {
            padding: 6px 16px;
            background: #17a2b8;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }

        .detail-btn:hover {
            background: #138496;
        }

        /* 유틸리티 */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .text-muted { color: #6c757d; }
        .text-primary { color: #0d6efd; }
        .text-success { color: #28a745; }
        .text-danger { color: #dc3545; }
        .text-warning { color: #ffc107; }

        .mt-1 { margin-top: 4px; }
        .mt-2 { margin-top: 8px; }
        .mt-3 { margin-top: 16px; }
        .mt-4 { margin-top: 24px; }
        .mb-1 { margin-bottom: 4px; }
        .mb-2 { margin-bottom: 8px; }
        .mb-3 { margin-bottom: 16px; }
        .mb-4 { margin-bottom: 24px; }

        .d-flex { display: flex; }
        .align-center { align-items: center; }
        .justify-between { justify-content: space-between; }
        .gap-2 { gap: 8px; }
        .gap-3 { gap: 16px; }

        .hidden { display: none !important; }

        /* 반응형 */
        @media (max-width: 768px) {
            .page-content {
                padding: 16px;
            }

            .search-grid {
                grid-template-columns: 1fr;
            }

            .result-header {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
            }

            .card-header {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include __DIR__ . '/topnav.php'; ?>

        <div class="main-content">
            <div class="page-content">
