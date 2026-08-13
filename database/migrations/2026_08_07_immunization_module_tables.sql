-- ============================================================
-- IMMUNIZATION & NUTRITION MODULE – MISSING TABLES
-- Run this in Supabase SQL Editor (or psql).
-- All tables use the existing pattern (serial PK, timestamptz).
-- ============================================================

-- ============================================================
-- 1. immunizations  (actual administered vaccine records)
-- ============================================================
CREATE TABLE IF NOT EXISTS public.immunizations (
    id                  serial          NOT NULL,
    child_id            integer         NOT NULL,
    vaccine             varchar(150)    NOT NULL,
    dose                integer         NOT NULL DEFAULT 1,
    date_administered   date            NOT NULL,
    next_due_date       date            NULL,
    batch_number        varchar(100)    NULL,
    administered_by     varchar(150)    NULL,
    health_center       varchar(150)    NULL,
    notes               text            NULL,
    created_at          timestamptz     NULL DEFAULT now(),
    updated_at          timestamptz     NULL DEFAULT now(),
    CONSTRAINT immunizations_pkey PRIMARY KEY (id),
    CONSTRAINT immunizations_child_id_fkey
        FOREIGN KEY (child_id) REFERENCES public.children(id) ON DELETE CASCADE,
    CONSTRAINT immunizations_dose_check CHECK (dose >= 1)
) TABLESPACE pg_default;

CREATE INDEX IF NOT EXISTS idx_immunizations_child_id
    ON public.immunizations USING btree (child_id) TABLESPACE pg_default;

CREATE INDEX IF NOT EXISTS idx_immunizations_vaccine
    ON public.immunizations USING btree (vaccine) TABLESPACE pg_default;

CREATE INDEX IF NOT EXISTS idx_immunizations_date_administered
    ON public.immunizations USING btree (date_administered) TABLESPACE pg_default;

-- ============================================================
-- 2. growth_measurements
-- ============================================================
CREATE TABLE IF NOT EXISTS public.growth_measurements (
    id                  serial          NOT NULL,
    child_id            integer         NOT NULL,
    measurement_date    date            NOT NULL,
    weight              numeric(6,2)    NOT NULL,     -- kg  (0.1 – 999.99)
    height              numeric(6,2)    NOT NULL,     -- cm  (20  – 999.99)
    head_circumference  numeric(5,2)    NULL,         -- cm  (optional)
    notes               text            NULL,
    created_at          timestamptz     NULL DEFAULT now(),
    updated_at          timestamptz     NULL DEFAULT now(),
    CONSTRAINT growth_measurements_pkey PRIMARY KEY (id),
    CONSTRAINT growth_measurements_child_id_fkey
        FOREIGN KEY (child_id) REFERENCES public.children(id) ON DELETE CASCADE,
    CONSTRAINT growth_measurements_weight_check
        CHECK (weight >= 0.1 AND weight <= 999.99),
    CONSTRAINT growth_measurements_height_check
        CHECK (height >= 20 AND height <= 999.99)
) TABLESPACE pg_default;

CREATE INDEX IF NOT EXISTS idx_growth_measurements_child_id
    ON public.growth_measurements USING btree (child_id) TABLESPACE pg_default;

CREATE INDEX IF NOT EXISTS idx_growth_measurements_date
    ON public.growth_measurements USING btree (measurement_date DESC) TABLESPACE pg_default;

-- ============================================================
-- 3. nutrition_assessments
-- ============================================================
CREATE TABLE IF NOT EXISTS public.nutrition_assessments (
    id                  serial          NOT NULL,
    child_id            integer         NOT NULL,
    assessment_date     date            NOT NULL,
    weight              numeric(6,2)    NOT NULL,
    height              numeric(6,2)    NOT NULL,
    bmi                 numeric(5,2)    NULL,
    weight_percentile   integer         NULL,
    height_percentile   integer         NULL,
    nutrition_status    text            NOT NULL DEFAULT 'normal',
    risk_level          text            NOT NULL DEFAULT 'low',
    assessment_notes    text            NULL,
    plan_of_action      text            NULL,
    supplements         text            NULL,   -- JSON array stored as text
    next_assessment_date date           NULL,
    assessed_by         varchar(150)    NULL,
    status              text            NOT NULL DEFAULT 'active',
    created_at          timestamptz     NULL DEFAULT now(),
    updated_at          timestamptz     NULL DEFAULT now(),
    CONSTRAINT nutrition_assessments_pkey PRIMARY KEY (id),
    CONSTRAINT nutrition_assessments_child_id_fkey
        FOREIGN KEY (child_id) REFERENCES public.children(id) ON DELETE CASCADE,
    CONSTRAINT nutrition_assessments_nutrition_status_check
        CHECK (nutrition_status IN ('normal', 'moderate', 'critical')),
    CONSTRAINT nutrition_assessments_risk_level_check
        CHECK (risk_level IN ('low', 'medium', 'high')),
    CONSTRAINT nutrition_assessments_status_check
        CHECK (status IN ('active', 'completed', 'critical'))
) TABLESPACE pg_default;

CREATE INDEX IF NOT EXISTS idx_nutrition_assessments_child_id
    ON public.nutrition_assessments USING btree (child_id) TABLESPACE pg_default;

CREATE INDEX IF NOT EXISTS idx_nutrition_assessments_date
    ON public.nutrition_assessments USING btree (assessment_date DESC) TABLESPACE pg_default;

CREATE INDEX IF NOT EXISTS idx_nutrition_assessments_status
    ON public.nutrition_assessments USING btree (nutrition_status) TABLESPACE pg_default;

-- ============================================================
-- 4. vaccine_inventory
-- ============================================================
CREATE TABLE IF NOT EXISTS public.vaccine_inventory (
    id                  serial          NOT NULL,
    vaccine_name        varchar(200)    NOT NULL,
    batch_number        varchar(100)    NOT NULL,
    quantity            integer         NOT NULL DEFAULT 0,
    minimum_stock       integer         NOT NULL DEFAULT 20,
    expiry_date         date            NOT NULL,
    temperature         numeric(5,2)    NOT NULL DEFAULT 4.0,  -- Celsius
    storage_location    varchar(100)    NOT NULL DEFAULT 'Refrigerator A1',
    supplier            varchar(200)    NULL,
    unit                varchar(50)     NOT NULL DEFAULT 'doses',
    received_date       date            NOT NULL DEFAULT CURRENT_DATE,
    created_at          timestamptz     NULL DEFAULT now(),
    updated_at          timestamptz     NULL DEFAULT now(),
    CONSTRAINT vaccine_inventory_pkey PRIMARY KEY (id),
    CONSTRAINT vaccine_inventory_quantity_check CHECK (quantity >= 0),
    CONSTRAINT vaccine_inventory_minimum_check CHECK (minimum_stock >= 0)
) TABLESPACE pg_default;

CREATE INDEX IF NOT EXISTS idx_vaccine_inventory_vaccine_name
    ON public.vaccine_inventory USING btree (vaccine_name) TABLESPACE pg_default;

CREATE INDEX IF NOT EXISTS idx_vaccine_inventory_expiry
    ON public.vaccine_inventory USING btree (expiry_date) TABLESPACE pg_default;

-- ============================================================
-- 5. inventory_log  (audit trail for stock adjustments)
-- ============================================================
CREATE TABLE IF NOT EXISTS public.inventory_log (
    id              serial          NOT NULL,
    inventory_id    integer         NOT NULL,
    adjustment_type varchar(20)     NOT NULL,  -- 'add' | 'remove' | 'set' | 'reorder'
    quantity        integer         NOT NULL,
    reason          text            NULL,
    created_at      timestamptz     NULL DEFAULT now(),
    CONSTRAINT inventory_log_pkey PRIMARY KEY (id),
    CONSTRAINT inventory_log_inventory_id_fkey
        FOREIGN KEY (inventory_id) REFERENCES public.vaccine_inventory(id) ON DELETE CASCADE,
    CONSTRAINT inventory_log_adjustment_type_check
        CHECK (adjustment_type IN ('add', 'remove', 'set', 'reorder'))
) TABLESPACE pg_default;

CREATE INDEX IF NOT EXISTS idx_inventory_log_inventory_id
    ON public.inventory_log USING btree (inventory_id) TABLESPACE pg_default;

-- ============================================================
-- Triggers – auto-update updated_at (uses existing function
--            handle_updated_at() defined in the DB already)
-- ============================================================
DO $$
BEGIN
    -- immunizations
    IF NOT EXISTS (
        SELECT 1 FROM pg_trigger WHERE tgname = 'handle_immunizations_updated_at'
    ) THEN
        CREATE TRIGGER handle_immunizations_updated_at
            BEFORE UPDATE ON public.immunizations
            FOR EACH ROW EXECUTE FUNCTION handle_updated_at();
    END IF;

    -- growth_measurements
    IF NOT EXISTS (
        SELECT 1 FROM pg_trigger WHERE tgname = 'handle_growth_measurements_updated_at'
    ) THEN
        CREATE TRIGGER handle_growth_measurements_updated_at
            BEFORE UPDATE ON public.growth_measurements
            FOR EACH ROW EXECUTE FUNCTION handle_updated_at();
    END IF;

    -- nutrition_assessments
    IF NOT EXISTS (
        SELECT 1 FROM pg_trigger WHERE tgname = 'handle_nutrition_assessments_updated_at'
    ) THEN
        CREATE TRIGGER handle_nutrition_assessments_updated_at
            BEFORE UPDATE ON public.nutrition_assessments
            FOR EACH ROW EXECUTE FUNCTION handle_updated_at();
    END IF;

    -- vaccine_inventory
    IF NOT EXISTS (
        SELECT 1 FROM pg_trigger WHERE tgname = 'handle_vaccine_inventory_updated_at'
    ) THEN
        CREATE TRIGGER handle_vaccine_inventory_updated_at
            BEFORE UPDATE ON public.vaccine_inventory
            FOR EACH ROW EXECUTE FUNCTION handle_updated_at();
    END IF;
END;
$$;
