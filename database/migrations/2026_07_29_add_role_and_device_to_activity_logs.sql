-- ============================================================
-- DATABASE MIGRATION: ADD ROLE & DEVICE TO ACTIVITY_LOGS
-- File: database/migrations/2026_07_29_add_role_and_device_to_activity_logs.sql
-- Description: Adds role and device columns for real-world audit trail.
-- Execute this migration in your Supabase SQL Editor.
-- ============================================================

ALTER TABLE public.activity_logs
  ADD COLUMN IF NOT EXISTS role character varying(100) DEFAULT 'System Administrator',
  ADD COLUMN IF NOT EXISTS device character varying(255) DEFAULT 'Desktop • Chrome (Linux)';

-- Create indexes for performance filtering
CREATE INDEX IF NOT EXISTS idx_activity_logs_role ON public.activity_logs USING btree (role) TABLESPACE pg_default;
CREATE INDEX IF NOT EXISTS idx_activity_logs_ip ON public.activity_logs USING btree (ip_address) TABLESPACE pg_default;
