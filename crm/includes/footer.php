            </div><!-- .page-content -->
        </div><!-- .main-content -->
    </div><!-- .app-container -->

    <!-- 공통 JavaScript -->
    <script src="<?= CRM_URL ?>/assets/js/common.js"></script>

    <script>
        // CSRF 토큰
        const CSRF_TOKEN = '<?= $csrfToken ?? '' ?>';

        // API 요청 헬퍼
        async function apiRequest(url, options = {}) {
            const isFormData = options.body instanceof FormData;

            const defaultHeaders = {
                'X-CSRF-Token': CSRF_TOKEN
            };

            // FormData가 아닌 경우에만 Content-Type 설정
            if (!isFormData) {
                defaultHeaders['Content-Type'] = 'application/json';
            }

            const mergedOptions = {
                ...options,
                headers: {
                    ...defaultHeaders,
                    ...(options.headers || {})
                }
            };

            // FormData인 경우 Content-Type 제거 (브라우저가 자동 설정)
            if (isFormData) {
                delete mergedOptions.headers['Content-Type'];
            }

            try {
                const response = await fetch(url, mergedOptions);
                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || '요청 처리 중 오류가 발생했습니다.');
                }

                return data;
            } catch (error) {
                console.error('API Error:', error);
                throw error;
            }
        }

        // GET 요청
        async function apiGet(url) {
            return apiRequest(url, { method: 'GET' });
        }

        // POST 요청
        async function apiPost(url, data) {
            return apiRequest(url, {
                method: 'POST',
                body: JSON.stringify(data)
            });
        }

        // PUT 요청
        async function apiPut(url, data) {
            return apiRequest(url, {
                method: 'PUT',
                body: JSON.stringify(data)
            });
        }

        // DELETE 요청
        async function apiDelete(url) {
            return apiRequest(url, { method: 'DELETE' });
        }

        // 폼 데이터 POST (파일 업로드 포함)
        async function apiPostForm(url, formData) {
            formData.append('csrf_token', CSRF_TOKEN);
            return apiRequest(url, {
                method: 'POST',
                headers: {}, // Content-Type 자동 설정
                body: formData
            });
        }

        // 토스트 알림
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = message;
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 16px 24px;
                border-radius: 8px;
                color: #fff;
                font-size: 14px;
                z-index: 9999;
                animation: slideIn 0.3s ease;
            `;

            const colors = {
                success: '#28a745',
                error: '#dc3545',
                warning: '#ffc107',
                info: '#17a2b8'
            };
            toast.style.background = colors[type] || colors.info;

            document.body.appendChild(toast);

            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // 확인 다이얼로그
        function confirmDialog(message) {
            return confirm(message);
        }

        // 로딩 표시
        function showLoading(container) {
            if (typeof container === 'string') {
                container = document.querySelector(container);
            }
            if (container) {
                container.innerHTML = '<div class="loading"><div class="spinner"></div></div>';
            }
        }

        // 날짜 포맷
        function formatDate(dateString, format = 'YYYY-MM-DD') {
            if (!dateString) return '';
            const date = new Date(dateString);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');

            return format
                .replace('YYYY', year)
                .replace('MM', month)
                .replace('DD', day)
                .replace('HH', hours)
                .replace('mm', minutes);
        }

        // 숫자 포맷 (천 단위 콤마)
        function formatNumber(num) {
            return num ? num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',') : '0';
        }

        // 스타일 추가
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);

        // 모달 열기
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
        }

        // 모달 닫기
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('show');
                document.body.style.overflow = '';
            }
        }

        // ESC 키로 모달 닫기
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.show').forEach(modal => {
                    modal.classList.remove('show');
                });
                document.body.style.overflow = '';
            }
        });
    </script>

    <?php if (isset($pageScripts)): ?>
    <?= $pageScripts ?>
    <?php endif; ?>
</body>
</html>
