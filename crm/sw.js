/**
 * 선일쉬핑 CRM Service Worker
 * PWA 오프라인 지원 및 캐싱
 */

const CACHE_NAME = 'sunilcrm-v1';
const OFFLINE_URL = '/sunilshipping/crm/offline.html';

// 캐시할 정적 리소스
const STATIC_ASSETS = [
    '/sunilshipping/crm/',
    '/sunilshipping/crm/assets/css/style.css',
    '/sunilshipping/crm/assets/images/icon-192.png',
    '/sunilshipping/crm/assets/images/icon-512.png',
    '/sunilshipping/crm/assets/images/favicon.ico',
    '/sunilshipping/crm/manifest.json'
];

// 설치 이벤트
self.addEventListener('install', (event) => {
    console.log('[SW] Installing Service Worker');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[SW] Caching static assets');
                return cache.addAll(STATIC_ASSETS);
            })
            .then(() => {
                return self.skipWaiting();
            })
    );
});

// 활성화 이벤트 - 오래된 캐시 삭제
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating Service Worker');
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((name) => name !== CACHE_NAME)
                        .map((name) => caches.delete(name))
                );
            })
            .then(() => {
                return self.clients.claim();
            })
    );
});

// Fetch 이벤트 - 네트워크 우선, 실패시 캐시
self.addEventListener('fetch', (event) => {
    // API 요청은 항상 네트워크에서 가져옴
    if (event.request.url.includes('/api/')) {
        event.respondWith(
            fetch(event.request)
                .catch(() => {
                    return new Response(
                        JSON.stringify({ error: '오프라인 상태입니다.' }),
                        {
                            status: 503,
                            headers: { 'Content-Type': 'application/json' }
                        }
                    );
                })
        );
        return;
    }

    // 정적 리소스는 캐시 우선
    if (event.request.destination === 'style' ||
        event.request.destination === 'script' ||
        event.request.destination === 'image') {
        event.respondWith(
            caches.match(event.request)
                .then((cachedResponse) => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    return fetch(event.request)
                        .then((response) => {
                            if (!response || response.status !== 200) {
                                return response;
                            }
                            const responseToCache = response.clone();
                            caches.open(CACHE_NAME)
                                .then((cache) => {
                                    cache.put(event.request, responseToCache);
                                });
                            return response;
                        });
                })
        );
        return;
    }

    // 페이지 요청은 네트워크 우선
    event.respondWith(
        fetch(event.request)
            .then((response) => {
                // 성공적인 응답은 캐시
                if (response && response.status === 200) {
                    const responseToCache = response.clone();
                    caches.open(CACHE_NAME)
                        .then((cache) => {
                            cache.put(event.request, responseToCache);
                        });
                }
                return response;
            })
            .catch(() => {
                // 오프라인일 때 캐시된 페이지 반환
                return caches.match(event.request)
                    .then((cachedResponse) => {
                        if (cachedResponse) {
                            return cachedResponse;
                        }
                        // 캐시도 없으면 오프라인 페이지
                        if (event.request.mode === 'navigate') {
                            return caches.match(OFFLINE_URL);
                        }
                    });
            })
    );
});

// 푸시 알림 이벤트
self.addEventListener('push', (event) => {
    if (!event.data) return;

    const data = event.data.json();
    const options = {
        body: data.body || '',
        icon: '/sunilshipping/crm/assets/images/icon-192.png',
        badge: '/sunilshipping/crm/assets/images/icon-192.png',
        vibrate: [100, 50, 100],
        data: {
            url: data.url || '/sunilshipping/crm/'
        }
    };

    event.waitUntil(
        self.registration.showNotification(data.title || '선일쉬핑 CRM', options)
    );
});

// 알림 클릭 이벤트
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    event.waitUntil(
        clients.openWindow(event.notification.data.url)
    );
});
