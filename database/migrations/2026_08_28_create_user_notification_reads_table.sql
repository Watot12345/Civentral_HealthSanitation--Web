-- Migration: Create user_notification_reads table for account-level notification read synchronization
-- Execution: Run in Supabase SQL Editor

CREATE TABLE IF NOT EXISTS public.user_notification_reads (
    id BIGSERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES public.employees(id) ON DELETE CASCADE,
    notification_id VARCHAR(100) NOT NULL,
    is_read BOOLEAN DEFAULT TRUE,
    read_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_user_notification_read UNIQUE (user_id, notification_id)
);

-- Index for fast user-level lookups
CREATE INDEX IF NOT EXISTS idx_user_notification_reads_user_id ON public.user_notification_reads(user_id);
