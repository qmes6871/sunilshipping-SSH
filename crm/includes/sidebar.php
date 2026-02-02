<?php
/**
 * CRM 사이드바 네비게이션
 */

// 현재 페이지 경로
$currentPath = $_SERVER['REQUEST_URI'] ?? '';
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// 활성 메뉴 체크 함수
function isActive($paths) {
    global $currentPath;
    if (!is_array($paths)) $paths = [$paths];
    foreach ($paths as $path) {
        if (strpos($currentPath, $path) !== false) return true;
    }
    return false;
}
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="<?= CRM_URL ?>" class="sidebar-logo">
            선일<span>CRM</span>
        </a>
    </div>

    <nav class="sidebar-nav">
        <!-- 마이페이지 -->
        <div class="nav-section">
            <div class="nav-section-title">마이페이지</div>
            <a href="<?= CRM_URL ?>/pages/main.php" class="nav-item <?= isActive('/main') ? 'active' : '' ?>">
                <span class="nav-icon">🏠</span>
                메인
            </a>
            <a href="<?= CRM_URL ?>/pages/profile.php" class="nav-item <?= isActive('/profile') ? 'active' : '' ?>">
                <span class="nav-icon">👤</span>
                개인정보 수정
            </a>
            <a href="<?= CRM_URL ?>/pages/common/todos.php" class="nav-item <?= isActive('/todos') ? 'active' : '' ?>">
                <span class="nav-icon">✅</span>
                할일 관리
            </a>
            <a href="<?= CRM_URL ?>/pages/common/meetings.php" class="nav-item <?= isActive('/meetings') ? 'active' : '' ?>">
                <span class="nav-icon">📝</span>
                회의록
            </a>
        </div>

        <!-- 국제물류 -->
        <div class="nav-section">
            <div class="nav-section-title">국제물류</div>
            <a href="<?= CRM_URL ?>/pages/international/dashboard.php" class="nav-item <?= isActive('/international/dashboard') ? 'active' : '' ?>">
                <span class="nav-icon">📊</span>
                대시보드
            </a>
            <a href="<?= CRM_URL ?>/pages/international/customers.php" class="nav-item <?= isActive('/international/customer') ? 'active' : '' ?>">
                <span class="nav-icon">👥</span>
                바이어 관리
            </a>
            <a href="<?= CRM_URL ?>/pages/international/activity_form.php" class="nav-item <?= isActive('/international/activity') ? 'active' : '' ?>">
                <span class="nav-icon">📋</span>
                활동 작성
            </a>
            <a href="<?= CRM_URL ?>/pages/international/performance_form.php" class="nav-item <?= isActive('/international/performance') ? 'active' : '' ?>">
                <span class="nav-icon">📈</span>
                성과 등록
            </a>
        </div>

        <!-- 농산물 -->
        <div class="nav-section">
            <div class="nav-section-title">농산물</div>
            <a href="<?= CRM_URL ?>/pages/agricultural/dashboard.php" class="nav-item <?= isActive('/agricultural/dashboard') ? 'active' : '' ?>">
                <span class="nav-icon">📊</span>
                대시보드
            </a>
            <a href="<?= CRM_URL ?>/pages/agricultural/customers.php" class="nav-item <?= isActive('/agricultural/customer') ? 'active' : '' ?>">
                <span class="nav-icon">🏪</span>
                고객 관리
            </a>
        </div>

        <!-- 우드펠렛 -->
        <div class="nav-section">
            <div class="nav-section-title">우드펠렛</div>
            <a href="<?= CRM_URL ?>/pages/pellet/dashboard.php" class="nav-item <?= isActive('/pellet/dashboard') ? 'active' : '' ?>">
                <span class="nav-icon">📊</span>
                대시보드
            </a>
            <a href="<?= CRM_URL ?>/pages/pellet/traders.php" class="nav-item <?= isActive('/pellet/trader') ? 'active' : '' ?>">
                <span class="nav-icon">🏭</span>
                거래처 관리
            </a>
        </div>

        <!-- 공통 -->
        <div class="nav-section">
            <div class="nav-section-title">공통</div>
            <a href="<?= CRM_URL ?>/pages/common/notices.php" class="nav-item <?= isActive('/notices') ? 'active' : '' ?>">
                <span class="nav-icon">📢</span>
                전체 공지
                <span class="nav-badge">5</span>
            </a>
            <a href="<?= CRM_URL ?>/pages/common/routes.php" class="nav-item <?= isActive('/routes') ? 'active' : '' ?>">
                <span class="nav-icon">🚢</span>
                루트별 주의사항
            </a>
            <a href="<?= CRM_URL ?>/pages/common/kms.php" class="nav-item <?= isActive('/kms') ? 'active' : '' ?>">
                <span class="nav-icon">📚</span>
                KMS 게시판
            </a>
            <a href="<?= CRM_URL ?>/pages/common/push.php" class="nav-item <?= isActive('/push') ? 'active' : '' ?>">
                <span class="nav-icon">🔔</span>
                푸시알림 운영
            </a>
        </div>

        <!-- 로그아웃 -->
        <div class="nav-section" style="margin-top: auto; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
            <a href="/gnuboard5/bbs/logout.php" class="nav-item">
                <span class="nav-icon">🚪</span>
                로그아웃
            </a>
        </div>
    </nav>
</aside>

<!-- 모바일 오버레이 -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<style>
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 999;
    }

    @media (max-width: 1024px) {
        .sidebar-overlay.show {
            display: block;
        }
    }
</style>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
}
</script>
