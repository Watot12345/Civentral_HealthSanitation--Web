-- ============================================================
-- Civentral Health & Sanitation Management Information System
-- Migration: Create scheduler_logs table for background processing
-- File: database/migrations/2026_09_04_create_scheduler_logs_table.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS public.scheduler_logs (
    id BIGSERIAL PRIMARY KEY,
    job_name VARCHAR(100) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'running', -- 'running', 'success', 'failed'
    triggered_by VARCHAR(100) NOT NULL DEFAULT 'cron', -- 'cron', 'cli', 'manual', 'system'
    output TEXT NULL,
    error_message TEXT NULL,
    started_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMPTZ NULL,
    duration_ms INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Performance Indexes
CREATE INDEX IF NOT EXISTS idx_scheduler_logs_job_name ON public.scheduler_logs(job_name);
CREATE INDEX IF NOT EXISTS idx_scheduler_logs_status ON public.scheduler_logs(status);
CREATE INDEX IF NOT EXISTS idx_scheduler_logs_created_at ON public.scheduler_logs(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_scheduler_logs_job_date ON public.scheduler_logs(job_name, created_at DESC);

-- Enable Row Level Security (RLS)
ALTER TABLE public.scheduler_logs ENABLE ROW LEVEL SECURITY;

-- Allow authenticated users to view scheduler logs
DROP POLICY IF EXISTS "Allow authenticated users to read scheduler logs" ON public.scheduler_logs;
CREATE POLICY "Allow authenticated users to read scheduler logs"
    ON public.scheduler_logs
    FOR SELECT
    TO authenticated, anon
    USING (true);

-- Allow system and authenticated users to insert scheduler logs
DROP POLICY IF EXISTS "Allow system and authenticated users to insert scheduler logs" ON public.scheduler_logs;
CREATE POLICY "Allow system and authenticated users to insert scheduler logs"
    ON public.scheduler_logs
    FOR INSERT
    TO authenticated, anon
    WITH CHECK (true);

DROP POLICY IF EXISTS "Allow system and authenticated users to update scheduler logs" ON public.scheduler_logs;
CREATE POLICY "Allow system and authenticated users to update scheduler logs"
    ON public.scheduler_logs
    FOR UPDATE
    TO authenticated, anon
    USING (true)
    WITH CHECK (true);

COMMENT ON TABLE public.scheduler_logs IS 'Audit trail and execution history for automated background jobs and scheduled tasks.';
