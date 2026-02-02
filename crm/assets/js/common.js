/**
 * CRM 공통 JavaScript
 */

// CRM URL
const CRM_URL = window.CRM_URL || '/crm';

// 사이드바 토글
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (sidebar) sidebar.classList.toggle('show');
    if (overlay) overlay.classList.toggle('show');
}

// 날짜 포맷
function formatDateKR(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

// 숫자 포맷
function formatNumberKR(num) {
    return num ? num.toLocaleString('ko-KR') : '0';
}

// 파일 크기 포맷
function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

// 디바운스
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// 로컬 스토리지 헬퍼
const storage = {
    get: (key) => {
        try {
            return JSON.parse(localStorage.getItem(key));
        } catch {
            return null;
        }
    },
    set: (key, value) => {
        localStorage.setItem(key, JSON.stringify(value));
    },
    remove: (key) => {
        localStorage.removeItem(key);
    }
};

// 페이지 로드 완료 시
document.addEventListener('DOMContentLoaded', function() {
    // 외부 링크 새 탭에서 열기
    document.querySelectorAll('a[href^="http"]').forEach(link => {
        if (!link.href.includes(window.location.hostname)) {
            link.setAttribute('target', '_blank');
            link.setAttribute('rel', 'noopener noreferrer');
        }
    });

    // 폼 자동저장 경고
    const forms = document.querySelectorAll('form[data-warn-unsaved]');
    forms.forEach(form => {
        let hasChanges = false;

        form.addEventListener('change', () => {
            hasChanges = true;
        });

        form.addEventListener('submit', () => {
            hasChanges = false;
        });

        window.addEventListener('beforeunload', (e) => {
            if (hasChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    });
});

// 에러 핸들링
window.onerror = function(msg, url, lineNo, columnNo, error) {
    console.error('Error: ', msg, '\nURL: ', url, '\nLine: ', lineNo);
    return false;
};

console.log('CRM Common JS loaded');
