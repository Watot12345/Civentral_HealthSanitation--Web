/**
 * offline-sync.js — Offline Transaction Queue & Auto-Synchronization Manager
 * Civentral Health Services & Public Health Portal
 *
 * Features:
 * 1. IndexedDB persistence for offline transactions (preserves queue across tab closes/reloads)
 * 2. Automatic reconnection listener ('online') with heart-beat ping verification
 * 3. Transparent API fetch wrapper & global mutation interceptor
 * 4. Real-time UI indicator in navbar with pending count and manual Sync Now button
 * 5. Toast alerts on queueing & successful synchronization
 */

const CiventralOfflineSync = (function() {
    'use strict';

    const DB_NAME = 'CiventralOfflineDB';
    const DB_VERSION = 1;
    const STORE_NAME = 'sync_queue';
    const PING_INTERVAL_MS = 20000;

    let dbInstance = null;
    let isSyncing = false;
    let isOnlineStatus = navigator.onLine;
    let syncListeners = [];

    // ============================================================
    // 1. IndexedDB Setup
    // ============================================================
    function openDB() {
        if (dbInstance) return Promise.resolve(dbInstance);

        return new Promise((resolve, reject) => {
            if (!window.indexedDB) {
                console.warn('IndexedDB not supported on this browser.');
                return resolve(null);
            }

            const request = window.indexedDB.open(DB_NAME, DB_VERSION);

            request.onupgradeneeded = function(e) {
                const db = e.target.result;
                if (!db.objectStoreNames.contains(STORE_NAME)) {
                    const store = db.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
                    store.createIndex('status', 'status', { unique: false });
                    store.createIndex('timestamp', 'timestamp', { unique: false });
                }
            };

            request.onsuccess = function(e) {
                dbInstance = e.target.result;
                resolve(dbInstance);
            };

            request.onerror = function(e) {
                console.error('Failed to open IndexedDB:', e.target.error);
                resolve(null);
            };
        });
    }

    // ============================================================
    // 2. Queue Storage Operations
    // ============================================================
    async function enqueue(item) {
        const db = await openDB();
        const queueItem = {
            url: item.url,
            method: item.method || 'POST',
            headers: item.headers || {},
            body: item.body || null,
            entityType: item.entityType || detectEntityType(item.url),
            entityLabel: item.entityLabel || 'Transaction',
            timestamp: Date.now(),
            status: 'pending',
            retryCount: 0,
            error: null
        };

        if (!db) {
            const fallbackQueue = JSON.parse(localStorage.getItem('civentral_offline_queue') || '[]');
            queueItem.id = Date.now() + Math.floor(Math.random() * 1000);
            fallbackQueue.push(queueItem);
            localStorage.setItem('civentral_offline_queue', JSON.stringify(fallbackQueue));
            notifyStateChange();
            return queueItem;
        }

        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE_NAME, 'readwrite');
            const store = tx.objectStore(STORE_NAME);
            const req = store.add(queueItem);

            req.onsuccess = function(e) {
                queueItem.id = e.target.result;
                notifyStateChange();
                resolve(queueItem);
            };

            req.onerror = function(e) {
                console.error('Error queueing offline transaction:', e.target.error);
                reject(e.target.error);
            };
        });
    }

    async function getPendingQueue() {
        const db = await openDB();
        if (!db) {
            const fallbackQueue = JSON.parse(localStorage.getItem('civentral_offline_queue') || '[]');
            return fallbackQueue.filter(item => item.status === 'pending');
        }

        return new Promise((resolve) => {
            const tx = db.transaction(STORE_NAME, 'readonly');
            const store = tx.objectStore(STORE_NAME);
            const req = store.getAll();

            req.onsuccess = function(e) {
                const all = e.target.result || [];
                resolve(all.filter(item => item.status === 'pending'));
            };

            req.onerror = function() {
                resolve([]);
            };
        });
    }

    async function removeQueueItem(id) {
        const db = await openDB();
        if (!db) {
            let fallbackQueue = JSON.parse(localStorage.getItem('civentral_offline_queue') || '[]');
            fallbackQueue = fallbackQueue.filter(item => item.id !== id);
            localStorage.setItem('civentral_offline_queue', JSON.stringify(fallbackQueue));
            notifyStateChange();
            return;
        }

        return new Promise((resolve) => {
            const tx = db.transaction(STORE_NAME, 'readwrite');
            const store = tx.objectStore(STORE_NAME);
            const req = store.delete(id);
            req.onsuccess = () => {
                notifyStateChange();
                resolve();
            };
            req.onerror = () => resolve();
        });
    }

    async function updateQueueItem(item) {
        const db = await openDB();
        if (!db) {
            const fallbackQueue = JSON.parse(localStorage.getItem('civentral_offline_queue') || '[]');
            const idx = fallbackQueue.findIndex(i => i.id === item.id);
            if (idx >= 0) fallbackQueue[idx] = item;
            localStorage.setItem('civentral_offline_queue', JSON.stringify(fallbackQueue));
            notifyStateChange();
            return;
        }

        return new Promise((resolve) => {
            const tx = db.transaction(STORE_NAME, 'readwrite');
            const store = tx.objectStore(STORE_NAME);
            const req = store.put(item);
            req.onsuccess = () => {
                notifyStateChange();
                resolve();
            };
            req.onerror = () => resolve();
        });
    }

    function detectEntityType(url) {
        if (!url) return 'Data';
        // Health Services
        if (url.includes('patients.php')) return 'Patient';
        if (url.includes('appointments.php')) return 'Appointment';
        if (url.includes('consultations.php')) return 'Consultation';
        if (url.includes('triage-queue.php')) return 'Triage Queue';
        if (url.includes('triage.php')) return 'Triage Record';
        if (url.includes('drugs.php') || url.includes('prescriptions')) return 'Prescription';
        if (url.includes('referrals')) return 'Referral';
        // Sanitation
        if (url.includes('permits') || url.includes('permit_applications')) return 'Permit Application';
        if (url.includes('inspections')) return 'Inspection Record';
        if (url.includes('payments')) return 'Payment Record';
        if (url.includes('renewals')) return 'Renewal Record';
        // Immunization
        if (url.includes('immunizations') || url.includes('vaccination')) return 'Vaccination Record';
        if (url.includes('growth') || url.includes('nutrition')) return 'Growth / Nutrition';
        if (url.includes('inventory')) return 'Vaccine Inventory';
        // Surveillance
        if (url.includes('surveillence') || url.includes('cases')) return 'Surveillance Case';
        // Services
        if (url.includes('service_requests')) return 'Service Request';
        if (url.includes('maintenance')) return 'Maintenance Record';
        if (url.includes('invoices') || url.includes('billing')) return 'Invoice';
        if (url.includes('tanks')) return 'Septic Tank';
        return 'Record';
    }

    // ============================================================
    // 3. Network Health & Connectivity
    // ============================================================
    async function checkConnectivity() {
        if (!navigator.onLine) {
            setOnlineState(false);
            return false;
        }

        try {
            const pingUrl = (window.location.origin || '') + '/favicon.ico?_t=' + Date.now();
            const res = await fetch(pingUrl, { method: 'HEAD', cache: 'no-store' });
            const online = res.status < 500;
            setOnlineState(online);
            return online;
        } catch (e) {
            setOnlineState(false);
            return false;
        }
    }

    function setOnlineState(online) {
        const wasOnline = isOnlineStatus;
        isOnlineStatus = online;

        if (!wasOnline && online) {
            console.log('🌐 Connection restored! Initiating automatic sync...');
            showOnlineToast();
            triggerSync();
        } else if (wasOnline && !online) {
            console.warn('⚠️ Network connection lost. Offline queueing engaged.');
            showOfflineToast();
        }
        notifyStateChange();
    }

    // ============================================================
    // 4. Sync Replay Processor (FIFO)
    // ============================================================
    async function triggerSync() {
        if (isSyncing) return;
        const pending = await getPendingQueue();
        if (!pending || pending.length === 0) {
            updateUIBadge(0, false);
            return;
        }

        isSyncing = true;
        updateUIBadge(pending.length, true);

        let successCount = 0;
        let failCount = 0;

        for (const item of pending) {
            try {
                const headers = Object.assign({}, item.headers);
                if (typeof getCsrfToken === 'function') {
                    const token = getCsrfToken();
                    if (token) headers['X-CSRF-TOKEN'] = token;
                }

                // Call raw native fetch to avoid recursive interception
                const res = await (window._nativeFetch || fetch)(item.url, {
                    method: item.method,
                    headers: headers,
                    body: item.body
                });

                if (res.ok) {
                    await removeQueueItem(item.id);
                    successCount++;
                } else if (res.status === 401 || res.status === 403) {
                    console.warn(`Item ${item.id} rejected by server with ${res.status} (Unauthorized). Pausing sync. Please log in.`);
                    isSyncing = false;
                    const remaining = await getPendingQueue();
                    updateUIBadge(remaining.length, false);
                    if (typeof ModalSystem !== 'undefined' && ModalSystem.toast) {
                        ModalSystem.toast.error('Session expired. Please log in to sync offline data.');
                    } else if (typeof showToast === 'function') {
                        showToast('Session expired. Please log in to sync offline data.', 'error');
                    }
                    return;
                } else if (res.status >= 400 && res.status < 500 && res.status !== 408 && res.status !== 429) {
                    console.warn(`Item ${item.id} rejected by server with ${res.status}. Removing from sync queue.`);
                    await removeQueueItem(item.id);
                    failCount++;
                } else {
                    item.retryCount = (item.retryCount || 0) + 1;
                    item.error = `HTTP ${res.status}`;
                    await updateQueueItem(item);
                    failCount++;
                }
            } catch (err) {
                console.error(`Sync failure for item ${item.id}:`, err);
                item.retryCount = (item.retryCount || 0) + 1;
                item.error = err.message;
                await updateQueueItem(item);
                failCount++;
                break;
            }
        }

        isSyncing = false;
        const remaining = await getPendingQueue();
        updateUIBadge(remaining.length, false);

        if (successCount > 0) {
            const msg = `Synced ${successCount} offline transaction(s) successfully!`;
            if (typeof ModalSystem !== 'undefined' && ModalSystem.toast) {
                ModalSystem.toast.success(msg);
            } else if (typeof showToast === 'function') {
                showToast(msg, 'success');
            }
        }

        if (failCount > 0 && remaining.length > 0) {
            if (typeof ModalSystem !== 'undefined' && ModalSystem.toast) {
                ModalSystem.toast.warning(`${remaining.length} transaction(s) still pending sync.`);
            }
        }
    }

    // ============================================================
    // 5. Intercept / Wrap Fetch
    // ============================================================
    async function request(url, options = {}, metadata = {}) {
        const method = (options.method || 'GET').toUpperCase();
        const isMutation = ['POST', 'PUT', 'DELETE', 'PATCH'].includes(method);

        if (!navigator.onLine && isMutation) {
            const queued = await enqueue({
                url: url,
                method: method,
                headers: options.headers || {},
                body: options.body,
                entityType: metadata.entityType,
                entityLabel: metadata.entityLabel
            });

            showQueuedToast(queued.entityLabel || queued.entityType);

            return {
                ok: true,
                status: 200,
                offline: true,
                queued: true,
                json: async () => ({
                    success: true,
                    offline: true,
                    message: 'Saved offline. Changes will automatically synchronize when connection is restored.',
                    data: { id: 'temp_' + queued.id }
                }),
                text: async () => JSON.stringify({
                    success: true,
                    offline: true,
                    message: 'Saved offline. Changes will automatically synchronize when connection is restored.'
                })
            };
        }

        try {
            const response = await (window._nativeFetch || fetch)(url, options);
            return response;
        } catch (error) {
            const isNetworkError = error instanceof TypeError || error.name === 'AbortError';
            if (isNetworkError && isMutation) {
                console.warn('Network request failed. Diverting to offline transaction queue:', url);
                setOnlineState(false);

                const queued = await enqueue({
                    url: url,
                    method: method,
                    headers: options.headers || {},
                    body: options.body,
                    entityType: metadata.entityType,
                    entityLabel: metadata.entityLabel
                });

                showQueuedToast(queued.entityLabel || queued.entityType);

                return {
                    ok: true,
                    status: 200,
                    offline: true,
                    queued: true,
                    json: async () => ({
                        success: true,
                        offline: true,
                        message: 'Network unavailable. Transaction saved locally and queued for automatic synchronization.',
                        data: { id: 'temp_' + queued.id }
                    }),
                    text: async () => JSON.stringify({
                        success: true,
                        offline: true,
                        message: 'Network unavailable. Transaction saved locally and queued for automatic synchronization.'
                    })
                };
            }
            throw error;
        }
    }

    // ============================================================
    // 6. UI & Toast Notifications
    // ============================================================
    function showOfflineToast() {
        if (typeof ModalSystem !== 'undefined' && ModalSystem.toast) {
            ModalSystem.toast.warning('Working in Offline Mode. Transactions will be saved locally.');
        } else if (typeof showToast === 'function') {
            showToast('Working in Offline Mode. Transactions will be saved locally.', 'warning');
        }
    }

    function showOnlineToast() {
        if (typeof ModalSystem !== 'undefined' && ModalSystem.toast) {
            ModalSystem.toast.info('Network connection restored. Reconnecting...');
        } else if (typeof showToast === 'function') {
            showToast('Network connection restored. Reconnecting...', 'info');
        }
    }

    function showQueuedToast(label) {
        const msg = `${label || 'Record'} saved offline! Will auto-sync once connected.`;
        if (typeof ModalSystem !== 'undefined' && ModalSystem.toast) {
            ModalSystem.toast.info(msg);
        } else if (typeof showToast === 'function') {
            showToast(msg, 'info');
        }
    }

    async function notifyStateChange() {
        const pending = await getPendingQueue();
        updateUIBadge(pending.length, isSyncing);
        for (const cb of syncListeners) {
            try { cb({ online: isOnlineStatus, pendingCount: pending.length, isSyncing }); } catch (e) {}
        }
    }

    function updateUIBadge(count, syncing) {
        const container = document.getElementById('offlineSyncWidget');
        if (!container) return;

        const textEl = document.getElementById('offlineSyncText');
        const iconEl = document.getElementById('offlineSyncIcon');
        const indicatorDot = document.getElementById('offlineIndicatorDot');

        if (syncing) {
            container.classList.remove('hidden');
            container.className = 'flex items-center gap-2 px-3 py-1.5 rounded-xl border text-xs font-bold transition-all shadow-xs cursor-pointer bg-blue-50 text-blue-700 border-blue-200';
            if (iconEl) iconEl.className = 'fa-solid fa-rotate fa-spin text-xs';
            if (textEl) textEl.textContent = `Syncing (${count})...`;
            if (indicatorDot) indicatorDot.className = 'w-2 h-2 rounded-full bg-blue-500 animate-ping';
        } else if (!isOnlineStatus || count > 0) {
            container.classList.remove('hidden');
            const isOffline = !isOnlineStatus;
            container.className = 'flex items-center gap-2 px-3 py-1.5 rounded-xl border text-xs font-bold transition-all shadow-xs cursor-pointer ' + 
                (isOffline ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-amber-50 text-amber-700 border-amber-200');
            if (iconEl) iconEl.className = isOffline ? 'fa-solid fa-wifi-slash text-xs' : 'fa-solid fa-cloud-arrow-up text-xs';
            if (textEl) textEl.textContent = isOffline ? `Offline (${count} pending)` : `${count} Pending Sync`;
            if (indicatorDot) indicatorDot.className = isOffline ? 'w-2 h-2 rounded-full bg-rose-500 animate-pulse' : 'w-2 h-2 rounded-full bg-amber-500';
        } else {
            container.classList.add('hidden');
            container.classList.remove('flex');
        }
    }

    // ============================================================
    // 7. Lifecycle Initialization & Event Binding
    // ============================================================
    let deferredPrompt;
    function initPWAInstallPrompt() {
        const promptEl = document.getElementById('pwa-install-prompt');
        const installBtn = document.getElementById('pwa-install-btn');
        const dismissBtn = document.getElementById('pwa-dismiss-btn');

        if (!promptEl || !installBtn || !dismissBtn) return;

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            if (localStorage.getItem('pwa_prompt_dismissed') !== 'true') {
                promptEl.classList.remove('hidden');
            }
        });

        installBtn.addEventListener('click', () => {
            promptEl.classList.add('hidden');
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    deferredPrompt = null;
                });
            }
        });

        dismissBtn.addEventListener('click', () => {
            promptEl.classList.add('hidden');
            localStorage.setItem('pwa_prompt_dismissed', 'true');
        });
    }

    function init() {
        initPWAInstallPrompt();
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').then(registration => {
                    console.log('ServiceWorker registration successful with scope: ', registration.scope);
                }).catch(err => {
                    console.log('ServiceWorker registration failed: ', err);
                });
            });
        }

        window.addEventListener('online', () => {
            console.log('Browser online event caught');
            checkConnectivity();
        });

        window.addEventListener('offline', () => {
            console.warn('Browser offline event caught');
            setOnlineState(false);
        });

        setInterval(checkConnectivity, PING_INTERVAL_MS);

        setTimeout(async () => {
            await checkConnectivity();
            const pending = await getPendingQueue();
            updateUIBadge(pending.length, false);
            if (pending.length > 0 && navigator.onLine) {
                triggerSync();
            }
        }, 600);

        patchGlobalFetch();
    }

    function patchGlobalFetch() {
        if (window._nativeFetch) return; // Prevent double patch
        window._nativeFetch = window.fetch;

        window.fetch = async function(resource, init) {
            const url = typeof resource === 'string' ? resource : (resource ? resource.url : '');
            const method = ((init && init.method) || 'GET').toUpperCase();

            // Intercept mutations for all 5 modules (Health, Sanitation, Immunization, Surveillance, Services)
            const isApiMutation = url && (
                url.includes('/api/') ||
                url.includes('/modules/healthservices/api/') ||
                url.includes('/modules/sanitation/api/') ||
                url.includes('/modules/immunization/api/') ||
                url.includes('/modules/surveillence/api/') ||
                url.includes('/modules/services/api/')
            ) && ['POST', 'PUT', 'DELETE'].includes(method);

            if (isApiMutation) {
                return request(resource, init);
            }

            return window._nativeFetch.apply(this, arguments);
        };
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    return {
        request: request,
        enqueue: enqueue,
        syncNow: triggerSync,
        getPending: getPendingQueue,
        isOnline: () => isOnlineStatus,
        subscribe: (fn) => syncListeners.push(fn)
    };
})();
