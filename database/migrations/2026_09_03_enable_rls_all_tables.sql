-- ==============================================================================
-- MASTER ROW LEVEL SECURITY (RLS) MIGRATION
-- Project: Civentral LGU Capstone
-- Purpose: Enable RLS across all tables in public schema for custom PHP Auth
-- Execution: Copy and paste directly into the Supabase SQL Editor and click RUN
-- ==============================================================================

-- ==============================================================================
-- SECTION 1: ENABLE ROW LEVEL SECURITY (RLS) ON ALL CURRENT PUBLIC TABLES
-- ==============================================================================

DO $$
DECLARE
    tbl RECORD;
BEGIN
    FOR tbl IN (
        SELECT tablename 
        FROM pg_tables 
        WHERE schemaname = 'public'
    ) LOOP
        EXECUTE format('ALTER TABLE public.%I ENABLE ROW LEVEL SECURITY;', tbl.tablename);
        RAISE NOTICE 'RLS Enabled on: %', tbl.tablename;
    END LOOP;
END $$;


-- ==============================================================================
-- SECTION 2: GRANT FULL PERMISSIONS TO service_role (Used by PHP Backend)
-- In Supabase, service_role has BYPASSRLS, but explicit policies ensure complete
-- full-stack access even if FORCE ROW LEVEL SECURITY is enabled.
-- ==============================================================================

DO $$
DECLARE
    tbl RECORD;
BEGIN
    FOR tbl IN (
        SELECT tablename 
        FROM pg_tables 
        WHERE schemaname = 'public'
    ) LOOP
        -- Remove old/duplicate service_role policy if exists
        EXECUTE format('DROP POLICY IF EXISTS "service_role_full_access" ON public.%I;', tbl.tablename);
        
        -- Create permissive policy for service_role
        EXECUTE format('CREATE POLICY "service_role_full_access" ON public.%I FOR ALL TO service_role USING (true) WITH CHECK (true);', tbl.tablename);
    END LOOP;
END $$;


-- ==============================================================================
-- SECTION 3: EXPLICIT LOCKDOWN OF SENSITIVE TABLES FOR anon ROLE
-- All direct public requests (using the public anon key) to sensitive clinical,
-- staff, and financial tables are completely denied.
-- ==============================================================================

DO $$
DECLARE
    tbl RECORD;
BEGIN
    -- Drop any unintended public anon policies on sensitive tables
    FOR tbl IN (
        SELECT tablename 
        FROM pg_tables 
        WHERE schemaname = 'public'
          AND tablename NOT IN ('announcements', 'barangays', 'setting_categories', 'surveillance_alerts')
    ) LOOP
        EXECUTE format('DROP POLICY IF EXISTS "anon_read" ON public.%I;', tbl.tablename);
        EXECUTE format('DROP POLICY IF EXISTS "anon_all" ON public.%I;', tbl.tablename);
        EXECUTE format('DROP POLICY IF EXISTS "public_read" ON public.%I;', tbl.tablename);
        EXECUTE format('DROP POLICY IF EXISTS "public_all" ON public.%I;', tbl.tablename);
    END LOOP;
END $$;


-- ==============================================================================
-- SECTION 4: SAFE READ-ONLY POLICIES FOR PUBLIC BROADCAST TABLES
-- Allows Supabase Realtime listeners (e.g. on Dashboard) using the anon key
-- to receive live push notifications and general public reference data safely.
-- ==============================================================================

-- 1. Announcements: Active announcements are readable
DROP POLICY IF EXISTS "anon_read_active_announcements" ON public.announcements;
CREATE POLICY "anon_read_active_announcements" ON public.announcements
    FOR SELECT TO anon
    USING (is_active = true);

-- 2. Barangays: Public geographic reference data is readable
DROP POLICY IF EXISTS "anon_read_barangays" ON public.barangays;
CREATE POLICY "anon_read_barangays" ON public.barangays
    FOR SELECT TO anon
    USING (true);

-- 3. Surveillance Alerts: Active public health warning alerts are readable
DROP POLICY IF EXISTS "anon_read_active_alerts" ON public.surveillance_alerts;
CREATE POLICY "anon_read_active_alerts" ON public.surveillance_alerts
    FOR SELECT TO anon
    USING (status = 'Active');

-- 4. Setting Categories: Public category labels are readable
DROP POLICY IF EXISTS "anon_read_setting_categories" ON public.setting_categories;
CREATE POLICY "anon_read_setting_categories" ON public.setting_categories
    FOR SELECT TO anon
    USING (true);


-- ==============================================================================
-- SECTION 5: VERIFICATION SUMMARY
-- ==============================================================================
SELECT 
    schemaname,
    tablename,
    rowsecurity AS rls_enabled
FROM pg_tables
WHERE schemaname = 'public'
ORDER BY tablename ASC;
