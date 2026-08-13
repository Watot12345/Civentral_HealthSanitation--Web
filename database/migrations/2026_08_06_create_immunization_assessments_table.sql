-- Migration: Create public.immunization_assessments table for pre-vaccination screening audit trail
CREATE TABLE IF NOT EXISTS public.immunization_assessments (
    id SERIAL PRIMARY KEY,
    patient_id INTEGER NOT NULL,
    weight NUMERIC(5,2) NULL,
    temperature NUMERIC(4,1) NULL,
    health_status VARCHAR(50) NULL DEFAULT 'Healthy',
    contraindications TEXT NULL DEFAULT 'None',
    vaccine_due VARCHAR(100) NULL,
    notes TEXT NULL,
    ai_guidance TEXT NULL,
    assessment_result VARCHAR(50) NULL DEFAULT 'Eligible',
    assessed_by VARCHAR(100) NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
