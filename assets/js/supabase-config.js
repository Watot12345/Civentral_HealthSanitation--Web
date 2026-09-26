/**
 * assets/js/supabase-config.js
 * Supabase Realtime Client for Civentral
 * 
 * Replace YOUR_SUPABASE_URL and YOUR_SUPABASE_ANON_KEY with your actual project details.
 */

(function(window) {
    'use strict';

    const SUPABASE_URL = window.SESSION_CONFIG?.supabaseUrl || '';
    const SUPABASE_KEY = window.SESSION_CONFIG?.supabaseKey || '';

    // Skip initialization if placeholders aren't replaced
    if (!SUPABASE_URL || !SUPABASE_KEY) {
        console.warn('Supabase is not configured. Realtime features are disabled.');
        return;
    }

    // Initialize Supabase Client
    const supabase = window.supabase.createClient(SUPABASE_URL, SUPABASE_KEY);
    
    // Create a broadcast channel for table updates
    const channel = supabase.channel('table_updates');

    // 1. Listen for Broadcasts from OTHER users
    channel
        .on('broadcast', { event: 'crud_success' }, (payload) => {
            console.log('Received real-time update:', payload);
            const { tableBodyId, record, action } = payload.payload;
            
            // Re-use our existing UI updater, but we need the renderRow function.
            // Since this is a generic listener, we must look up the renderRow function
            // from a global registry or dispatch an event that the specific page listens to.
            
            // Easiest way: Dispatch an event that individual pages can listen to if they are open.
            window.dispatchEvent(new CustomEvent('realtimeUpdate', {
                detail: { tableBodyId, record, action }
            }));
        })
        .on('broadcast', { event: 'crud_delete' }, (payload) => {
            console.log('Received real-time delete:', payload);
            const { tableBodyId, recordId } = payload.payload;
            
            if (window.CrudAjax && window.CrudAjax.deleteRow) {
                window.CrudAjax.deleteRow(tableBodyId, recordId);
            }
        })
        .subscribe();

    // 2. Broadcast our OWN updates to other users
    window.addEventListener('crudSuccess', (e) => {
        channel.send({
            type: 'broadcast',
            event: 'crud_success',
            payload: e.detail
        });
    });

    window.addEventListener('crudDelete', (e) => {
        channel.send({
            type: 'broadcast',
            event: 'crud_delete',
            payload: e.detail
        });
    });

    window.supabaseClient = supabase;
})(window);
