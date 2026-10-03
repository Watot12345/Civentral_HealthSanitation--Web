-- database/migrations/2026_10_03_performance_composite_indexes.sql
-- High-Performance Composite B-Tree Indexes for Supabase / PostgREST Query Optimization

DO $$ 
BEGIN
    -- 1. Wastewater & Septic Tank Optimization
    IF EXISTS (SELECT FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'septic_tanks') THEN
        CREATE INDEX IF NOT EXISTS idx_septic_tanks_tank_id ON public.septic_tanks (tank_id);
        CREATE INDEX IF NOT EXISTS idx_septic_tanks_barangay ON public.septic_tanks (barangay);
    END IF;

    IF EXISTS (SELECT FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'service_requests') THEN
        CREATE INDEX IF NOT EXISTS idx_service_requests_status_created ON public.service_requests (status, created_at DESC);
        CREATE INDEX IF NOT EXISTS idx_service_requests_tank_id ON public.service_requests (tank_id);
    END IF;

    IF EXISTS (SELECT FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'maintenance_records') THEN
        CREATE INDEX IF NOT EXISTS idx_maintenance_service_req ON public.maintenance_records (service_request_id);
        CREATE INDEX IF NOT EXISTS idx_maintenance_status ON public.maintenance_records (status);
    END IF;

    -- 2. Health Center & Triage Optimization
    IF EXISTS (SELECT FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'assessment') THEN
        CREATE INDEX IF NOT EXISTS idx_assessment_patient_created ON public.assessment (patient_id, created_at DESC);
    END IF;

    IF EXISTS (SELECT FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'triage_queue') THEN
        CREATE INDEX IF NOT EXISTS idx_triage_queue_status_created ON public.triage_queue (status, created_at ASC);
    END IF;

    IF EXISTS (SELECT FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'patients') THEN
        CREATE INDEX IF NOT EXISTS idx_patients_created_desc ON public.patients (created_at DESC);
        CREATE INDEX IF NOT EXISTS idx_patients_patient_id ON public.patients (patient_id);
    END IF;

    -- 3. Immunization & Nutrition Optimization
    IF EXISTS (SELECT FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'children') THEN
        CREATE INDEX IF NOT EXISTS idx_children_birth_date ON public.children (birth_date);
        CREATE INDEX IF NOT EXISTS idx_children_status_compliance ON public.children (status, vaccine_compliance);
        CREATE INDEX IF NOT EXISTS idx_children_child_id ON public.children (child_id);
    END IF;

    IF EXISTS (SELECT FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'immunizations') THEN
        CREATE INDEX IF NOT EXISTS idx_immunizations_child_vax ON public.immunizations (child_id, vaccine_id);
    END IF;

    IF EXISTS (SELECT FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'nutrition_assessments') THEN
        CREATE INDEX IF NOT EXISTS idx_nutrition_child_date ON public.nutrition_assessments (child_id, assessment_date DESC);
    END IF;

    -- 4. Sanitation Permits & Invoicing
    IF EXISTS (SELECT FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'payments') THEN
        CREATE INDEX IF NOT EXISTS idx_payments_status_type ON public.payments (payment_status, payment_type);
        CREATE INDEX IF NOT EXISTS idx_payments_created ON public.payments (created_at DESC);
    END IF;
END $$;
