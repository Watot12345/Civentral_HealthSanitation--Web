-- ==============================================================================
-- DATABASE MIGRATION: DATA DELETION WORKFLOW & AUDIT TRAIL (RA 10173)
-- Project: Civentral LGU Capstone
-- Purpose: Right to Be Forgotten / Citizen Erasure Requests & Audit Trail
-- Execution: Run in Supabase SQL Editor
-- ==============================================================================

-- 1. Citizen Erasure Request Queue
CREATE TABLE IF NOT EXISTS public.data_deletion_requests (
    id BIGSERIAL PRIMARY KEY,
    user_id VARCHAR(100) NOT NULL,                -- Target citizen/patient ID or username
    subject_type VARCHAR(50) NOT NULL DEFAULT 'patient', -- 'patient', 'employee', 'permit_applicant'
    request_reason TEXT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending', -- 'pending', 'approved', 'rejected', 'executed'
    requested_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP WITH TIME ZONE NULL,
    reviewed_by VARCHAR(100) NULL,                -- Admin employee ID who reviewed request
    rejection_reason TEXT NULL,
    executed_at TIMESTAMP WITH TIME ZONE NULL,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_deletion_req_user ON public.data_deletion_requests(user_id);
CREATE INDEX IF NOT EXISTS idx_deletion_req_status ON public.data_deletion_requests(status);

-- 2. Data Deletion Audit Trail
CREATE TABLE IF NOT EXISTS public.deletion_audit_logs (
    id BIGSERIAL PRIMARY KEY,
    request_id BIGINT NULL REFERENCES public.data_deletion_requests(id) ON DELETE SET NULL,
    user_id VARCHAR(100) NOT NULL,
    subject_type VARCHAR(50) NOT NULL,
    deleted_records JSONB NOT NULL DEFAULT '{}'::jsonb, -- Summary of tables & record IDs purged/anonymized
    executed_by VARCHAR(100) NOT NULL DEFAULT 'system_scheduler', -- Admin user ID or 'system_scheduler'
    executed_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_deletion_audit_user ON public.deletion_audit_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_deletion_audit_executed ON public.deletion_audit_logs(executed_at DESC);

GRANT SELECT, INSERT, UPDATE ON TABLE public.data_deletion_requests TO authenticated, service_role, anon;
GRANT SELECT, INSERT, UPDATE ON TABLE public.deletion_audit_logs TO authenticated, service_role, anon;
