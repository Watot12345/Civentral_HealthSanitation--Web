-- ============================================================
-- Migration: Create technicians table
-- Run in: Supabase SQL Editor
-- ============================================================

CREATE TABLE IF NOT EXISTS technicians (
    id              BIGSERIAL PRIMARY KEY,
    name            TEXT NOT NULL,
    employee_id     TEXT UNIQUE,
    phone           TEXT,
    status          TEXT NOT NULL DEFAULT 'available'
                    CHECK (status IN ('available', 'on_site', 'en_route', 'off_duty')),
    current_assignment TEXT DEFAULT NULL,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_technicians_status ON technicians(status);

INSERT INTO technicians (name, status) VALUES
    ('Roberto Silva', 'available'),
    ('Jose Mendoza',  'available'),
    ('Luis Torres',   'available'),
    ('Carlos Santos', 'available'),
    ('Ana Reyes',     'available')
ON CONFLICT DO NOTHING;

CREATE OR REPLACE FUNCTION update_technicians_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_technicians_updated_at
    BEFORE UPDATE ON technicians
    FOR EACH ROW EXECUTE FUNCTION update_technicians_updated_at();
