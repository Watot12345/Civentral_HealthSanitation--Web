const CACHE_NAME = 'civentral-cache-v1';
const ASSETS_TO_CACHE = [
    './',
    './manifest.json',
    './assets/js/offline-sync.js',
    './assets/css/style.css' // fallback, adjust if needed
];

// Install Event: Cache essential assets
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                return cache.addAll(ASSETS_TO_CACHE).catch(err => console.warn('Cache addAll failed', err));
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

// Fetch Event: Stale-while-revalidate for assets, Network First for HTML
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // Skip API requests and mutations, let offline-sync.js handle them
    if (url.pathname.includes('/api/') || event.request.method !== 'GET') {
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then(cachedResponse => {
                const networkFetch = fetch(event.request).then(response => {
                    // Update cache dynamically
                    if (response.ok && response.type === 'basic') {
                        const responseClone = response.clone();
                        caches.open(CACHE_NAME).then(cache => {
                            cache.put(event.request, responseClone);
                        });
                    }
                    return response;
                }).catch(() => {
                    // If network fails and no cache, maybe return a fallback offline page
                    return new Response('Offline. Please check your connection.', {
                        status: 503,
                        statusText: 'Service Unavailable',
                        headers: new Headers({ 'Content-Type': 'text/plain' })
                    });
                });

                // Return cached response immediately if available, while network fetch updates cache
                return cachedResponse || networkFetch;
            })
    );
});
