-- ==============================================================================
-- DATABASE MIGRATION: PATIENT CONSENTS TABLE (DPA COMPLIANCE - RA 10173)
-- Project: Civentral LGU Capstone
-- Purpose: Patient data privacy consent audit ledger
-- Execution: Run in Supabase SQL Editor
-- ==============================================================================

CREATE TABLE IF NOT EXISTS public.patient_consents (
    id BIGSERIAL PRIMARY KEY,
    user_id VARCHAR(100) NOT NULL,
    consent_type VARCHAR(100) NOT NULL,            -- e.g. 'registration_dpa', 'medical_records_sharing', 'telehealth_consent'
    granted_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revoked_at TIMESTAMP WITH TIME ZONE NULL,
    ip VARCHAR(45) NOT NULL,
    method VARCHAR(50) NOT NULL DEFAULT 'web_form', -- 'web_form', 'otp_checkbox', 'in_person_signature'
    status VARCHAR(20) NOT NULL DEFAULT 'active',   -- 'active', 'revoked'
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_patient_consents_user ON public.patient_consents (user_id);
CREATE INDEX IF NOT EXISTS idx_patient_consents_status ON public.patient_consents (status);

GRANT SELECT, INSERT, UPDATE ON TABLE public.patient_consents TO authenticated, service_role, anon;
