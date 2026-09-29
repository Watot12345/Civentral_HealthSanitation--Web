/**
 * assets/js/supabase-config.js
 * Supabase Realtime Client for Civentral
 * Provides WebSocket Change Data Capture (CDC) & Peer-to-Peer Broadcast
 */

(function(window) {
    'use strict';

    const SUPABASE_URL = window.SESSION_CONFIG?.supabaseUrl || '';
    const SUPABASE_KEY = window.SESSION_CONFIG?.supabaseKey || '';

    // Broadcast change helper exposed globally
    window.broadcastSanitationChange = function(module, payload = {}) {
        // 1. Dispatch locally on current window
        window.dispatchEvent(new CustomEvent('sanitation' + capitalize(module) + 'Updated', { detail: payload }));
        window.dispatchEvent(new CustomEvent('realtimeUpdate', { detail: { module, ...payload } }));

        // 2. Broadcast via Supabase channel if active
        if (window.sanitationBroadcastChannel && typeof window.sanitationBroadcastChannel.send === 'function') {
            try {
                window.sanitationBroadcastChannel.send({
                    type: 'broadcast',
                    event: 'sanitation_update',
                    payload: { module, ...payload }
                });
            } catch(e) {
                console.warn('Supabase broadcast failed:', e);
            }
        }
    };

    function capitalize(s) {
        if (!s) return '';
        return s.charAt(0).toUpperCase() + s.slice(1);
    }

    // Skip initialization if credentials are missing
    if (!SUPABASE_URL || !SUPABASE_KEY) {
        console.warn('Supabase is not configured. Realtime fallback mode active.');
        return;
    }

    // Guard: Skip if Supabase library failed to load (CDN offline or blocked)
    if (!window.supabase || typeof window.supabase.createClient !== 'function') {
        console.warn('Supabase JS library is not loaded. Realtime features fallback active.');
        return;
    }

    try {
        // Initialize Supabase Client
        const supabase = window.supabase.createClient(SUPABASE_URL, SUPABASE_KEY);
        window.supabaseClient = supabase;

        // 1. Unified Broadcast & CDC Channel for Sanitation
        const sanitationChannel = supabase.channel('sanitation_realtime_hub');
        window.sanitationBroadcastChannel = sanitationChannel;

        sanitationChannel
            // Broadcast from other users
            .on('broadcast', { event: 'sanitation_update' }, (msg) => {
                const data = msg.payload || {};
                if (data.module) {
                    window.dispatchEvent(new CustomEvent('sanitation' + capitalize(data.module) + 'Updated', { detail: data }));
                }
                window.dispatchEvent(new CustomEvent('realtimeUpdate', { detail: data }));
            })
            // PostgreSQL CDC: Listen directly to Database table INSERT / UPDATE / DELETE
            .on('postgres_changes', { event: '*', schema: 'public', table: 'inspections' }, (payload) => {
                console.log('⚡ Supabase DB Change [inspections]:', payload.eventType, payload.new || payload.old);
                window.dispatchEvent(new CustomEvent('sanitationInspectionsUpdated', { detail: payload }));
                window.dispatchEvent(new CustomEvent('realtimeUpdate', { detail: { module: 'inspections', payload } }));
            })
            .on('postgres_changes', { event: '*', schema: 'public', table: 'permits' }, (payload) => {
                console.log('⚡ Supabase DB Change [permits]:', payload.eventType, payload.new || payload.old);
                window.dispatchEvent(new CustomEvent('sanitationPermitsUpdated', { detail: payload }));
                window.dispatchEvent(new CustomEvent('realtimeUpdate', { detail: { module: 'permits', payload } }));
            })
            .subscribe((status) => {
                if (status === 'SUBSCRIBED') {
                    console.log('🟢 Supabase Realtime connected for Sanitation & Health modules');
                }
            });

        // 2. Generic Table Updates Channel (Full Legacy Compatibility)
        const legacyChannel = supabase.channel('table_updates');
        legacyChannel
            .on('broadcast', { event: 'crud_success' }, (payload) => {
                console.log('⚡ Realtime table_updates [crud_success] received:', payload);
                window.dispatchEvent(new CustomEvent('realtimeUpdate', { detail: payload.payload }));
            })
            .on('broadcast', { event: 'crud_delete' }, (payload) => {
                console.log('⚡ Realtime table_updates [crud_delete] received:', payload);
                const { tableBodyId, recordId } = payload.payload;
                if (window.CrudAjax && window.CrudAjax.deleteRow) {
                    window.CrudAjax.deleteRow(tableBodyId, recordId);
                }
                window.dispatchEvent(new CustomEvent('realtimeDelete', { detail: payload.payload }));
            })
            .subscribe();

        // Broadcast out on crudSuccess
        window.addEventListener('crudSuccess', (e) => {
            legacyChannel.send({
                type: 'broadcast',
                event: 'crud_success',
                payload: e.detail
            });
            // Also notify sanitation if this CRUD action specifies a sanitation module
            if (e.detail?.module && sanitationChannel) {
                sanitationChannel.send({
                    type: 'broadcast',
                    event: 'sanitation_update',
                    payload: e.detail
                });
            }
        });

        // Broadcast out on crudDelete (preserves multi-user delete sync)
        window.addEventListener('crudDelete', (e) => {
            legacyChannel.send({
                type: 'broadcast',
                event: 'crud_delete',
                payload: e.detail
            });
        });

    } catch (err) {
        console.warn('Supabase Realtime initialization warning:', err);
    }
})(window);
