-- Migration: Create public.user_sessions for 12-hour work shift & 7-day OTP authentication
CREATE TABLE IF NOT EXISTS public.user_sessions (
    id SERIAL PRIMARY KEY,
    employee_id INTEGER NOT NULL REFERENCES public.employees(id) ON DELETE CASCADE,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    otp_code VARCHAR(6) NULL,
    otp_expires_at TIMESTAMPTZ NULL,
    remember_me BOOLEAN DEFAULT FALSE,
    expires_at TIMESTAMPTZ NOT NULL, -- 12 hours or 7 days
    created_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_user_sessions_token ON public.user_sessions(session_token);
CREATE INDEX IF NOT EXISTS idx_user_sessions_employee ON public.user_sessions(employee_id);
