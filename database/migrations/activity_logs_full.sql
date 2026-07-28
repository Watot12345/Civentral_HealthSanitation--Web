-- ============================================================
-- MIGRATION: activity_logs — Full Real-World Audit Table
-- File: database/migrations/activity_logs_full.sql
-- Run this in your Supabase SQL Editor.
-- ============================================================

-- 1. Create the table if it does not exist
CREATE TABLE IF NOT EXISTS public.activity_logs (
  id         serial              NOT NULL,
  user_id    integer             NULL,
  user_name  character varying(100)  NULL DEFAULT 'System',
  role       character varying(100)  NULL DEFAULT 'System Administrator',
  action     text                NOT NULL,
  module     character varying(100)  NULL DEFAULT 'System Management',
  details    text                NULL,
  ip_address character varying(45)   NULL,
  device     character varying(255)  NULL DEFAULT 'Desktop • Chrome (Linux)',
  status     text                NULL DEFAULT 'Success',
  created_at timestamp with time zone NULL DEFAULT now(),
  CONSTRAINT activity_logs_pkey PRIMARY KEY (id),
  CONSTRAINT activity_logs_status_check CHECK (
    status = ANY (ARRAY['Success'::text, 'Failed'::text])
  )
) TABLESPACE pg_default;

-- 2. Add missing columns for existing installs (safe to re-run)
ALTER TABLE public.activity_logs
  ADD COLUMN IF NOT EXISTS role       character varying(100)  DEFAULT 'System Administrator',
  ADD COLUMN IF NOT EXISTS device     character varying(255)  DEFAULT 'Desktop • Chrome (Linux)',
  ADD COLUMN IF NOT EXISTS module     character varying(100)  DEFAULT 'System Management',
  ADD COLUMN IF NOT EXISTS user_name  character varying(100)  DEFAULT 'System';

-- 3. Performance indexes
CREATE INDEX IF NOT EXISTS idx_activity_logs_user_id    ON public.activity_logs USING btree (user_id)    TABLESPACE pg_default;
CREATE INDEX IF NOT EXISTS idx_activity_logs_created_at ON public.activity_logs USING btree (created_at DESC) TABLESPACE pg_default;
CREATE INDEX IF NOT EXISTS idx_activity_logs_status     ON public.activity_logs USING btree (status)     TABLESPACE pg_default;
CREATE INDEX IF NOT EXISTS idx_activity_logs_role       ON public.activity_logs USING btree (role)       TABLESPACE pg_default;
CREATE INDEX IF NOT EXISTS idx_activity_logs_ip         ON public.activity_logs USING btree (ip_address) TABLESPACE pg_default;
CREATE INDEX IF NOT EXISTS idx_activity_logs_module     ON public.activity_logs USING btree (module)     TABLESPACE pg_default;

-- ============================================================
-- DONE. The activity_logs table is now ready.
-- All PHP writes go through app/Models/ActivityLog::log()
-- which auto-detects IP address and device from the request.
-- ============================================================
