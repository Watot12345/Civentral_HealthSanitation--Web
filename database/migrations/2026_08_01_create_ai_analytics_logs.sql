-- database/migrations/2026_08_01_create_ai_analytics_logs.sql
-- ============================================================
-- AI ANALYTICS LOGS TABLE FOR SUPABASE
-- ============================================================

CREATE TABLE IF NOT EXISTS public.ai_analytics_logs (
    id SERIAL PRIMARY KEY,
    insight_key VARCHAR(100) NOT NULL,
    category VARCHAR(100) NOT NULL,
    badge VARCHAR(50) NOT NULL,
    color VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    action_text TEXT,
    confidence INT DEFAULT 90,
    metadata JSONB,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
