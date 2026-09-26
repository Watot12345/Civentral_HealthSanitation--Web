const CACHE_NAME = 'civentral-cache-v6';
const STATIC_ASSETS = [
    './manifest.json',
    './offline.html',

    // Core JS
    './assets/js/offline-sync.js',
    './assets/js/modal-system.js',
    './assets/js/common.js',
    './assets/js/app.js',
    './assets/js/apexcharts.min.js',
    './assets/js/leaflet.js',
    './assets/js/leaflet-heat.js',
    './assets/js/export.js',

    // Core CSS
    './assets/css/style.css',
    './assets/css/output.css',
    './assets/css/leaflet.css'
];

// Install Event: Cache only static assets
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                return cache.addAll(STATIC_ASSETS).catch(err => console.warn('Cache addAll failed:', err));
            })
    );
    self.skipWaiting();
});

// Activate Event: Cleanup old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

// Fetch Event: Network-first for PHP/HTML/API, cache-first for static assets
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // Skip non-GET requests entirely — let offline-sync.js handle mutations
    if (event.request.method !== 'GET') {
        return;
    }

    // Skip API endpoints (both /api/ paths and *_api.php files)
    if (url.pathname.includes('/api/') || url.pathname.endsWith('_api.php')) {
        return;
    }

    // Skip PHP pages — they require auth/sessions and should never be served from cache
    if (url.pathname.endsWith('.php')) {
        event.respondWith(
            fetch(event.request).catch(() => {
                return caches.match('./offline.html');
            })
        );
        return;
    }

    // Static assets: cache-first with network fallback
    event.respondWith(
        caches.match(event.request)
            .then(cachedResponse => {
                if (cachedResponse) {
                    // Serve from cache, update in background
                    fetch(event.request).then(response => {
                        if (response.ok && response.type === 'basic') {
                            caches.open(CACHE_NAME).then(cache => {
                                cache.put(event.request, response);
                            });
                        }
                    }).catch(() => {});
                    return cachedResponse;
                }
                // Not in cache — fetch from network, cache if successful
                return fetch(event.request).then(response => {
                    if (response.ok && response.type === 'basic') {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then(cache => {
                            cache.put(event.request, clone);
                        });
                    }
                    return response;
                }).catch(() => {
                    // If network fails and no cache, return the offline fallback page
                    return caches.match('./offline.html');
                });
            })
    );
});
