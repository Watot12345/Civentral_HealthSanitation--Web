-- Migration: Create public.announcements for City Health Announcement Board
CREATE TABLE IF NOT EXISTS public.announcements (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL DEFAULT 'General Announcement',
    audience VARCHAR(100) NOT NULL DEFAULT 'All Staff',
    body TEXT NOT NULL,
    author VARCHAR(255) NOT NULL DEFAULT 'System Admin',
    file_url TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_announcements_active ON public.announcements(is_active, created_at DESC);
