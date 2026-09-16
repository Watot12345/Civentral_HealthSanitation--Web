-- ==============================================================================
-- RLS ENABLEMENT FOR NEW PRIVACY & CONSENT TABLES
-- Project: Civentral LGU Capstone
-- Purpose: Secure the 4 newly created tables (Data Privacy Act compliance)
-- Execution: Copy and paste directly into the Supabase SQL Editor and click RUN
-- ==============================================================================

-- 1. Enable Row Level Security (RLS)
ALTER TABLE public.consent_logs ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.data_deletion_requests ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.deletion_audit_logs ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.patient_consents ENABLE ROW LEVEL SECURITY;

-- 2. Drop existing service_role policies if they somehow exist
DROP POLICY IF EXISTS "service_role_full_access" ON public.consent_logs;
DROP POLICY IF EXISTS "service_role_full_access" ON public.data_deletion_requests;
DROP POLICY IF EXISTS "service_role_full_access" ON public.deletion_audit_logs;
DROP POLICY IF EXISTS "service_role_full_access" ON public.patient_consents;

-- 3. Create permissive policy for service_role (PHP Backend)
CREATE POLICY "service_role_full_access" ON public.consent_logs FOR ALL TO service_role USING (true) WITH CHECK (true);
CREATE POLICY "service_role_full_access" ON public.data_deletion_requests FOR ALL TO service_role USING (true) WITH CHECK (true);
CREATE POLICY "service_role_full_access" ON public.deletion_audit_logs FOR ALL TO service_role USING (true) WITH CHECK (true);
CREATE POLICY "service_role_full_access" ON public.patient_consents FOR ALL TO service_role USING (true) WITH CHECK (true);

-- 4. Lockdown: Ensure public/anon has absolutely no access
DROP POLICY IF EXISTS "anon_read" ON public.consent_logs;
DROP POLICY IF EXISTS "anon_read" ON public.data_deletion_requests;
DROP POLICY IF EXISTS "anon_read" ON public.deletion_audit_logs;
DROP POLICY IF EXISTS "anon_read" ON public.patient_consents;
