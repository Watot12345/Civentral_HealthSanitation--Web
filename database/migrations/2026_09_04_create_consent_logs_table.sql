-- ==============================================================================
-- DATABASE MIGRATION: CONSENT LOGS TABLE (DPA COMPLIANCE - RA 10173)
-- Project: Civentral LGU Capstone
-- Purpose: Record and manage citizen/patient data processing consent and withdrawal
-- Execution: Run in Supabase SQL Editor
-- ==============================================================================

CREATE TABLE IF NOT EXISTS public.consent_logs (
    id BIGSERIAL PRIMARY KEY,
    subject_id VARCHAR(100) NOT NULL,              -- Target citizen/patient identifier (e.g., P-2026-001, C-104)
    subject_type VARCHAR(50) NOT NULL,             -- 'patient', 'child', 'permit_applicant', 'employee', 'citizen'
    consent_type VARCHAR(100) NOT NULL,            -- 'registration_terms_dpa', 'clinical_data_processing', 'telehealth_consent'
    consent_version VARCHAR(20) NOT NULL DEFAULT '1.0',
    ip_address VARCHAR(45) NOT NULL,               -- Client IP captured server-side
    user_agent TEXT NULL,                          -- Submitting client fingerprint
    status VARCHAR(20) NOT NULL DEFAULT 'active',  -- 'active', 'withdrawn'
    consented_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    withdrawn_at TIMESTAMP WITH TIME ZONE NULL,
    withdrawal_reason TEXT NULL,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Comments for documentation & data catalog
COMMENT ON TABLE public.consent_logs IS 'Tracks Data Privacy Act (RA 10173) consent records and withdrawals for citizens and patients.';
COMMENT ON COLUMN public.consent_logs.subject_id IS 'Unique external ID or primary key of the data subject (e.g., patient code or record ID).';
COMMENT ON COLUMN public.consent_logs.status IS 'Status of consent: active or withdrawn.';

-- Basic indexes for rapid filtering and lookup
CREATE INDEX IF NOT EXISTS idx_consent_logs_subject ON public.consent_logs (subject_type, subject_id);
CREATE INDEX IF NOT EXISTS idx_consent_logs_status ON public.consent_logs (status);

-- Partial Unique Index: Only ONE active consent allowed per (subject_id, consent_type)
-- Prevents duplicate active consents and eliminates ambiguity during withdrawal
CREATE UNIQUE INDEX IF NOT EXISTS idx_consent_active_unique 
    ON public.consent_logs (subject_id, consent_type) 
    WHERE status = 'active';

-- Trigger to keep updated_at in sync automatically
CREATE OR REPLACE FUNCTION public.update_consent_logs_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_consent_logs_updated_at ON public.consent_logs;
CREATE TRIGGER trg_consent_logs_updated_at
    BEFORE UPDATE ON public.consent_logs
    FOR EACH ROW
    EXECUTE FUNCTION public.update_consent_logs_timestamp();

-- Grants
GRANT SELECT, INSERT, UPDATE ON TABLE public.consent_logs TO authenticated, service_role;
