/**
 * ============================================================================
 * SERVICE WORKER (TEMPORARILY DISABLED)
 * ============================================================================
 * Disabled to prevent slow initial page loads and network interception while
 * offline mode is still under development.
 *
 * When loaded by browsers that still have a cached service worker, this script
 * immediately purges caches, unregisters itself, and lets all requests bypass
 * directly to the network.
 */

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
            .then(keys => Promise.all(keys.map(k => caches.delete(k))))
            .then(() => self.registration.unregister())
            .then(() => self.clients.matchAll())
            .then(clients => {
                clients.forEach(client => {
                    // Claim and release clients so network requests flow normally
                    if (client && client.url) {
                        console.info('[sw.js] ServiceWorker deactivated and unregistered.');
                    }
                });
            })
    );
});

// NOTE: Fetch listener is intentionally omitted so NO network requests are intercepted.

/*
// ============================================================================
// ORIGINAL SERVICE WORKER CODE (Uncomment when offline mode is ready)
// ============================================================================

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

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                return cache.addAll(STATIC_ASSETS).catch(err => console.warn('Cache addAll failed:', err));
            })
    );
    self.skipWaiting();
});

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

self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    if (event.request.method !== 'GET') {
        return;
    }

    if (url.pathname.includes('/api/') || url.pathname.endsWith('_api.php')) {
        return;
    }

    if (url.pathname.endsWith('.php')) {
        event.respondWith(
            fetch(event.request).catch(() => {
                return caches.match('./offline.html');
            })
        );
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then(cachedResponse => {
                if (cachedResponse) {
                    fetch(event.request).then(response => {
                        if (response.ok && response.type === 'basic') {
                            caches.open(CACHE_NAME).then(cache => {
                                cache.put(event.request, response);
                            });
                        }
                    }).catch(() => {});
                    return cachedResponse;
                }
                return fetch(event.request).then(response => {
                    if (response.ok && response.type === 'basic') {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then(cache => {
                            cache.put(event.request, clone);
                        });
                    }
                    return response;
                }).catch(() => {
                    return caches.match('./offline.html');
                });
            })
    );
});
*/
