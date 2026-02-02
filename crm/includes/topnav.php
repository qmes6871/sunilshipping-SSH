<?php
/**
 * CRM 상단 네비게이션 바
 * 심플 헤더 스타일 (LOGO + 메뉴)
 */

// 현재 페이지 경로
$currentPath = $_SERVER['REQUEST_URI'] ?? '';

// 활성 메뉴 체크 함수
function isActiveNav($paths) {
    global $currentPath;
    if (!is_array($paths)) $paths = [$paths];
    foreach ($paths as $path) {
        if (strpos($currentPath, $path) !== false) return true;
    }
    return false;
}

// 현재 사용자 정보
$userName = $currentUser['mb_name'] ?? '사용자';
$userInitial = mb_substr($userName, 0, 1);
?>

<header class="topnav">
    <div class="topnav-inner">
        <a class="brand" href="<?= CRM_URL ?>/pages/main.php">
            <span class="logo-text">선일쉬핑 CRM</span>
        </a>

        <div class="spacer"></div>

        <label class="nav-toggle" for="nav-toggle-checkbox" aria-label="메뉴 열기/닫기">☰</label>
        <input type="checkbox" id="nav-toggle-checkbox" hidden>

        <nav class="nav-links" aria-label="주요 메뉴">
            <a href="<?= CRM_URL ?>/pages/main.php" class="<?= isActiveNav(['/main.php', '/crm/index.php']) ? 'active' : '' ?>">홈</a>
            <a href="<?= CRM_URL ?>/pages/international/dashboard.php" class="<?= isActiveNav('/international') ? 'active' : '' ?>">국제물류</a>
            <a href="<?= CRM_URL ?>/pages/agricultural/dashboard.php" class="<?= isActiveNav('/agricultural') ? 'active' : '' ?>">농산물</a>
            <a href="<?= CRM_URL ?>/pages/pellet/dashboard.php" class="<?= isActiveNav('/pellet') ? 'active' : '' ?>">우드펠렛</a>
            <a href="<?= CRM_URL ?>/pages/common/notices.php" class="<?= isActiveNav('/notices') ? 'active' : '' ?>">전체공지</a>

            <?php if (isset($member['mb_id']) && $member['mb_id']): ?>
            <div class="user-menu">
                <button class="user-btn" onclick="toggleUserMenu()">
                    <span class="user-avatar"><?= h($userInitial) ?></span>
                    <span class="user-name"><?= h($userName) ?></span>
                    <span class="dropdown-arrow">▼</span>
                </button>
                <div class="user-dropdown" id="userDropdown">
                    <a href="<?= CRM_URL ?>/pages/profile.php">개인정보 수정</a>
                    <a href="<?= CRM_URL ?>/pages/common/todos.php">할일 관리</a>
                    <a href="<?= CRM_URL ?>/pages/common/meetings.php">회의록</a>
                    <a href="<?= CRM_URL ?>/pages/common/routes.php">루트별 주의사항</a>
                    <a href="<?= CRM_URL ?>/pages/common/kms.php">KMS 게시판</a>
                    <hr>
                    <a href="/gnuboard5/bbs/logout.php" class="logout">로그아웃</a>
                </div>
            </div>
            <?php else: ?>
            <a href="<?= CRM_URL ?>/" class="btn-login">로그인</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<style>
/* 상단 네비게이션 */
.topnav {
    background: #fff;
    border-bottom: 1px solid #e0e0e0;
    position: sticky;
    top: 0;
    z-index: 1000;
}

.topnav-inner {
    max-width: 1400px;
    margin: 0 auto;
    padding: 12px 24px;
    display: flex;
    align-items: center;
    gap: 16px;
}

.brand {
    font-weight: 600;
    color: #333;
    text-decoration: none;
    font-size: 18px;
    display: flex;
    align-items: center;
}

.brand .logo-img {
    height: 32px;
    width: auto;
}

.brand .logo-text {
    font-weight: 700;
    letter-spacing: 0.5px;
    font-size: 18px;
    color: #0d6efd;
}

.spacer {
    flex: 1;
}

.nav-links {
    display: flex;
    gap: 4px;
    align-items: center;
}

.nav-links > a {
    display: inline-flex;
    align-items: center;
    padding: 10px 16px;
    color: #555;
    text-decoration: none;
    font-size: 15px;
    border-radius: 4px;
    transition: all 0.2s;
}

.nav-links > a:hover {
    color: #000;
    background: #f5f5f5;
}

.nav-links > a.active {
    color: #0d6efd;
    font-weight: 600;
}

/* 사용자 메뉴 */
.user-menu {
    position: relative;
    margin-left: 8px;
}

.user-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    background: #f8f9fa;
    border: 1px solid #e0e0e0;
    border-radius: 24px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s;
}

.user-btn:hover {
    background: #e9ecef;
}

.user-avatar {
    width: 28px;
    height: 28px;
    background: #0d6efd;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 13px;
}

.user-name {
    color: #333;
    font-weight: 500;
}

.dropdown-arrow {
    font-size: 10px;
    color: #666;
}

.user-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    min-width: 180px;
    display: none;
    z-index: 1001;
}

.user-dropdown.show {
    display: block;
}

.user-dropdown a {
    display: block;
    padding: 12px 16px;
    color: #333;
    text-decoration: none;
    font-size: 14px;
    transition: background 0.2s;
}

.user-dropdown a:hover {
    background: #f8f9fa;
}

.user-dropdown a.logout {
    color: #dc3545;
}

.user-dropdown hr {
    margin: 4px 0;
    border: none;
    border-top: 1px solid #e9ecef;
}

/* 로그인 버튼 */
.btn-login {
    padding: 8px 20px !important;
    background: #0d6efd !important;
    color: #fff !important;
    border-radius: 4px !important;
    font-weight: 500 !important;
}

.btn-login:hover {
    background: #0b5ed7 !important;
}

/* 모바일 토글 */
.nav-toggle {
    display: none;
    width: 40px;
    height: 40px;
    align-items: center;
    justify-content: center;
    border: 1px solid #ddd;
    background: #fff;
    color: #333;
    border-radius: 4px;
    cursor: pointer;
    font-size: 20px;
}

/* 반응형 */
@media (max-width: 900px) {
    .topnav-inner {
        padding: 10px 16px;
        flex-wrap: wrap;
        position: relative;
    }

    .brand .logo-text {
        font-size: 16px;
    }

    .brand .logo-img {
        height: 28px;
    }

    .nav-toggle {
        display: inline-flex;
        width: 36px;
        height: 36px;
        font-size: 18px;
    }

    .nav-links {
        display: none;
        flex-direction: column;
        gap: 0;
        width: 100%;
        padding: 8px 0;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: #fff;
        border-bottom: 1px solid #e0e0e0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    #nav-toggle-checkbox:checked ~ .nav-links,
    .topnav-inner:has(#nav-toggle-checkbox:checked) .nav-links {
        display: flex;
    }

    .spacer {
        flex: 1;
    }

    .nav-links > a {
        width: 100%;
        justify-content: flex-start;
        padding: 14px 20px;
        font-size: 15px;
        border-bottom: 1px solid #f0f0f0;
    }

    .nav-links > a:last-of-type {
        border-bottom: none;
    }

    .nav-links > a.active {
        background: #f0f7ff;
    }

    .user-menu {
        width: 100%;
        margin-left: 0;
        border-top: 1px solid #e9ecef;
        padding-top: 8px;
    }

    .user-btn {
        width: 100%;
        justify-content: center;
        padding: 12px 16px;
    }

    .user-dropdown {
        position: static;
        box-shadow: none;
        border: none;
        border-top: 1px solid #e9ecef;
        margin-top: 8px;
        border-radius: 0;
    }

    .user-dropdown a {
        padding: 14px 20px;
        text-align: center;
    }

    .btn-login {
        width: calc(100% - 32px);
        margin: 8px 16px;
        text-align: center;
        justify-content: center;
    }
}

/* 더 작은 화면 */
@media (max-width: 480px) {
    .topnav-inner {
        padding: 8px 12px;
    }

    .brand .logo-text {
        font-size: 14px;
    }

    .brand .logo-img {
        height: 24px;
    }

    .nav-toggle {
        width: 32px;
        height: 32px;
        font-size: 16px;
    }

    .nav-links > a {
        padding: 12px 16px;
        font-size: 14px;
    }

    .user-btn {
        padding: 10px 14px;
    }

    .user-avatar {
        width: 24px;
        height: 24px;
        font-size: 11px;
    }

    .user-name {
        font-size: 13px;
    }
}
</style>

<script>
function toggleUserMenu() {
    const dropdown = document.getElementById('userDropdown');
    dropdown.classList.toggle('show');
}

// 외부 클릭시 드롭다운 닫기
document.addEventListener('click', function(e) {
    const userMenu = document.querySelector('.user-menu');
    const dropdown = document.getElementById('userDropdown');
    if (userMenu && dropdown && !userMenu.contains(e.target)) {
        dropdown.classList.remove('show');
    }
});

// 모바일 메뉴 토글 (체크박스 기반)
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('nav-toggle-checkbox');
    const navLinks = document.querySelector('.nav-links');

    if (toggle && navLinks) {
        toggle.addEventListener('change', function() {
            if (this.checked) {
                navLinks.style.display = 'flex';
            } else {
                navLinks.style.display = '';
            }
        });
    }
});
</script>
