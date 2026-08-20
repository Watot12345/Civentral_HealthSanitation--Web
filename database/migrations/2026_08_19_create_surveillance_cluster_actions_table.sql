-- database/migrations/2026_08_19_create_surveillance_cluster_actions_table.sql
-- ============================================================
-- SURVEILLANCE CLUSTER ACTIONS / RESPONSE AUDIT TABLE
-- Stage 4: Action & Intervention in the Surveillance Lifecycle
-- Links strictly to authenticated employees (RBAC foreign key)
-- ============================================================

CREATE TABLE IF NOT EXISTS public.surveillance_cluster_actions (
    id SERIAL PRIMARY KEY,
    cluster_key VARCHAR(100) NOT NULL UNIQUE, -- Unique cluster identifier e.g. "148|Dengue"
    disease VARCHAR(100) NOT NULL,
    barangay VARCHAR(100) NOT NULL,
    case_count INT NOT NULL DEFAULT 0,
    status VARCHAR(50) NOT NULL DEFAULT 'under_investigation', -- 'detected', 'under_investigation', 'verified_outbreak', 'resolved'
    action_notes TEXT,
    updated_by INT REFERENCES public.employees(id) ON DELETE SET NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_cluster_actions_key ON public.surveillance_cluster_actions (cluster_key);
CREATE INDEX IF NOT EXISTS idx_cluster_actions_status ON public.surveillance_cluster_actions (status);
CREATE INDEX IF NOT EXISTS idx_cluster_actions_updated_by ON public.surveillance_cluster_actions (updated_by);
