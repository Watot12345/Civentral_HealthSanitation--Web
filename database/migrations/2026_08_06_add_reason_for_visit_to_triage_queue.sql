-- Migration: Add reason_for_visit column to triage_queue
ALTER TABLE public.triage_queue ADD COLUMN IF NOT EXISTS reason_for_visit VARCHAR(100) NULL;
