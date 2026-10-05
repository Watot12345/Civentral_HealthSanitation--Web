-- ============================================================
-- Migration: Integrate Health Center Services with Immunization & Nutrition
-- Target Platform: Civentral Public Health & Municipal Sanitation
-- Date: 2026-10-10
-- ============================================================

-- 1. Enable immunizations table to link to main patients table (supporting children, adults, seniors)
ALTER TABLE public.immunizations 
  ADD COLUMN IF NOT EXISTS patient_id INTEGER NULL REFERENCES public.patients(id) ON DELETE CASCADE,
  ADD COLUMN IF NOT EXISTS patient_type VARCHAR(20) NOT NULL DEFAULT 'child',
  ALTER COLUMN child_id DROP NOT NULL;

CREATE INDEX IF NOT EXISTS idx_immunizations_patient_id 
  ON public.immunizations(patient_id);

CREATE INDEX IF NOT EXISTS idx_immunizations_patient_type 
  ON public.immunizations(patient_type);

-- 2. Enable nutrition_assessments to link to main patients table (supporting adult BMI and senior MNA)
ALTER TABLE public.nutrition_assessments
  ADD COLUMN IF NOT EXISTS patient_id INTEGER NULL REFERENCES public.patients(id) ON DELETE CASCADE,
  ADD COLUMN IF NOT EXISTS patient_type VARCHAR(20) NOT NULL DEFAULT 'child',
  ADD COLUMN IF NOT EXISTS assessment_type VARCHAR(30) NOT NULL DEFAULT 'pediatric_who',
  ADD COLUMN IF NOT EXISTS mna_score INTEGER NULL,
  ADD COLUMN IF NOT EXISTS weight_loss_3m NUMERIC(5,2) NULL,
  ALTER COLUMN child_id DROP NOT NULL;

CREATE INDEX IF NOT EXISTS idx_nutrition_patient_id 
  ON public.nutrition_assessments(patient_id);

CREATE INDEX IF NOT EXISTS idx_nutrition_patient_type 
  ON public.nutrition_assessments(patient_type);

-- 3. Immunization Queue / Referral Bridge from Doctor Consultations
CREATE TABLE IF NOT EXISTS public.immunization_referrals (
    id                  SERIAL PRIMARY KEY,
    patient_id          INTEGER NULL REFERENCES public.patients(id) ON DELETE CASCADE,
    child_id            INTEGER NULL REFERENCES public.children(id) ON DELETE CASCADE,
    patient_name        VARCHAR(255) NOT NULL,
    patient_type        VARCHAR(20) NOT NULL DEFAULT 'child',
    referred_by         VARCHAR(150) NOT NULL,
    consultation_id     INTEGER NULL REFERENCES public.consultations(id) ON DELETE SET NULL,
    vaccine_requested   VARCHAR(150) NOT NULL,
    urgency             VARCHAR(20) NOT NULL DEFAULT 'routine',
    notes               TEXT NULL,
    status              VARCHAR(30) NOT NULL DEFAULT 'pending',
    created_at          TIMESTAMPTZ DEFAULT NOW(),
    updated_at          TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_immunization_referrals_status 
  ON public.immunization_referrals(status);

CREATE INDEX IF NOT EXISTS idx_immunization_referrals_patient 
  ON public.immunization_referrals(patient_id);

CREATE INDEX IF NOT EXISTS idx_immunization_referrals_child 
  ON public.immunization_referrals(child_id);

-- 4. Security & Permissions for immunization_referrals
ALTER TABLE public.immunization_referrals ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "service_role_full_access" ON public.immunization_referrals;
CREATE POLICY "service_role_full_access" ON public.immunization_referrals FOR ALL TO service_role USING (true) WITH CHECK (true);

DROP POLICY IF EXISTS "anon_full_access" ON public.immunization_referrals;
CREATE POLICY "anon_full_access" ON public.immunization_referrals FOR ALL TO anon USING (true) WITH CHECK (true);

GRANT ALL ON TABLE public.immunization_referrals TO anon, authenticated, service_role;
GRANT USAGE, SELECT ON SEQUENCE public.immunization_referrals_id_seq TO anon, authenticated, service_role;
