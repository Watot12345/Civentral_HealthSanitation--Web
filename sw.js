const CACHE_NAME = 'civentral-cache-v3';
const ASSETS_TO_CACHE = [
    './',
    './manifest.json',
    './offline.html',
    './assets/js/offline-sync.js',
    './assets/css/style.css',
    
    // Core Pages
    './pages/dashboard.php',
    './pages/ai_insights.php',
    './management/system_logs.php',
    './management/settings.php',
    
    // Health Services Module
    './modules/healthservices/medical_records.php',
    './modules/healthservices/appointments.php',
    './modules/healthservices/consultations.php',
    './modules/healthservices/patients.php',
    './modules/healthservices/triage.php',
    './modules/healthservices/prescriptions.php',
    './modules/healthservices/referrals.php',
    
    // Sanitation Module
    './modules/sanitation/documents.php',
    './modules/sanitation/renewals.php',
    './modules/sanitation/permit_certificate.php',
    './modules/sanitation/payments.php',
    './modules/sanitation/inspections.php',
    './modules/sanitation/permit_applications.php',
    './modules/sanitation/verify_permit.php',
    './modules/sanitation/permit_records.php',
    
    // Immunization Module
    './modules/immunization/nutrition_assessment.php',
    './modules/immunization/child_records.php',
    './modules/immunization/vaccine_inventory.php',
    './modules/immunization/vaccination_tracking.php',
    './modules/immunization/growth_charts.php',
    
    // Surveillance Module
    './modules/surveillence/outbreak_detection.php',
    './modules/surveillence/alerts.php',
    './modules/surveillence/case_reports.php',
    './modules/surveillence/mapping.php',
    './modules/surveillence/outbreak_command.php',
    
    // Services Module
    './modules/services/maintenance.php',
    './modules/services/providers.php',
    './modules/services/service_requests.php',
    './modules/services/wastewater_billing.php',
    './modules/services/septic_tanks.php'
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
        caches.match(event.request, { ignoreSearch: true })
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
                    // If network fails and no cache, return the beautiful fallback offline page
                    return caches.match('./offline.html');
                });

                // Return cached response immediately if available, while network fetch updates cache
                return cachedResponse || networkFetch;
            })
    );
});
