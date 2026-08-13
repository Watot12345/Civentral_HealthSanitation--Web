-- database/migrations/2026_08_10_create_report_indexes.sql
-- High-Performance Fail-Safe Database B-Tree Indexes for Department Reports & Analytics Query Acceleration

DO $$ 
BEGIN
    -- Consultations Report Indexes
    IF EXISTS (SELECT FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'consultations' AND column_name = 'status') THEN
        CREATE INDEX IF NOT EXISTS idx_consultations_status ON public.consultations (status);
    END IF;
    IF EXISTS (SELECT FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'consultations' AND column_name = 'created_at') THEN
        CREATE INDEX IF NOT EXISTS idx_consultations_created_at ON public.consultations (created_at);
    END IF;

    -- Appointments Report Indexes
    IF EXISTS (SELECT FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'appointments' AND column_name = 'status') THEN
        CREATE INDEX IF NOT EXISTS idx_appointments_status_created ON public.appointments (status, created_at);
    END IF;

    -- Triage Report Indexes
    IF EXISTS (SELECT FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'triage' AND column_name = 'priority') THEN
        CREATE INDEX IF NOT EXISTS idx_triage_status_priority ON public.triage (status, priority);
    ELSIF EXISTS (SELECT FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'triage') THEN
        CREATE INDEX IF NOT EXISTS idx_triage_status ON public.triage (status);
    END IF;

    -- Sanitation Permits Indexes
    IF EXISTS (SELECT FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'permits' AND column_name = 'status') THEN
        CREATE INDEX IF NOT EXISTS idx_permits_status_created ON public.permits (status, created_at);
    END IF;

    -- Sanitation Inspections Indexes (overall_status column)
    IF EXISTS (SELECT FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'inspections' AND column_name = 'overall_status') THEN
        CREATE INDEX IF NOT EXISTS idx_inspections_status_overall ON public.inspections (status, overall_status);
    ELSIF EXISTS (SELECT FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'inspections') THEN
        CREATE INDEX IF NOT EXISTS idx_inspections_status ON public.inspections (status);
    END IF;

    -- Disease Surveillance Cases Indexes (disease column)
    IF EXISTS (SELECT FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'surveillance_cases' AND column_name = 'disease') THEN
        CREATE INDEX IF NOT EXISTS idx_surveillance_cases_status_disease ON public.surveillance_cases (status, disease);
    ELSIF EXISTS (SELECT FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'surveillance_cases') THEN
        CREATE INDEX IF NOT EXISTS idx_surveillance_cases_status ON public.surveillance_cases (status);
    END IF;

    -- Disease Surveillance Alerts Indexes (severity column)
    IF EXISTS (SELECT FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'surveillance_alerts' AND column_name = 'severity') THEN
        CREATE INDEX IF NOT EXISTS idx_surveillance_alerts_status_severity ON public.surveillance_alerts (status, severity);
    ELSIF EXISTS (SELECT FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'surveillance_alerts') THEN
        CREATE INDEX IF NOT EXISTS idx_surveillance_alerts_status ON public.surveillance_alerts (status);
    END IF;

    -- Immunization Assessments Indexes
    IF EXISTS (SELECT FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'immunization_assessments' AND column_name = 'created_at') THEN
        CREATE INDEX IF NOT EXISTS idx_immunization_created ON public.immunization_assessments (created_at);
    END IF;
END $$;
