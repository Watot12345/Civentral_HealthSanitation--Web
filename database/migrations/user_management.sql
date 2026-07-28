-- ============================================================
-- USER MANAGEMENT MODULE DATABASE SCHEMA
-- Run this migration against your Supabase SQL editor.
-- ============================================================

-- ============================================================
-- 1. ALTER employees table — add columns the UI expects
-- ============================================================
ALTER TABLE public.employees
  ADD COLUMN IF NOT EXISTS username character varying(50) UNIQUE,
  ADD COLUMN IF NOT EXISTS email character varying(100),
  ADD COLUMN IF NOT EXISTS status text DEFAULT 'Active',
  ADD COLUMN IF NOT EXISTS last_login timestamp with time zone,
  ADD COLUMN IF NOT EXISTS role_id integer REFERENCES public.roles(id) ON DELETE SET NULL;

-- Backfill username from employee_id for existing rows
UPDATE public.employees SET username = employee_id WHERE username IS NULL;

-- Add constraint for status values
ALTER TABLE public.employees
  DROP CONSTRAINT IF EXISTS employees_status_check;
ALTER TABLE public.employees
  ADD CONSTRAINT employees_status_check CHECK (
    status = ANY (ARRAY['Active'::text, 'Inactive'::text, 'Suspended'::text])
  );

-- ============================================================
-- 2. roles — role definitions
-- ============================================================
CREATE TABLE IF NOT EXISTS public.roles (
  id serial NOT NULL,
  name character varying(50) NOT NULL,
  slug character varying(50) NOT NULL,
  description text NULL,
  color character varying(100) NULL DEFAULT 'bg-slate-100 text-slate-700',
  user_count integer NULL DEFAULT 0,
  created_at timestamp with time zone NULL DEFAULT now(),
  updated_at timestamp with time zone NULL DEFAULT now(),
  CONSTRAINT roles_pkey PRIMARY KEY (id),
  CONSTRAINT roles_name_key UNIQUE (name),
  CONSTRAINT roles_slug_key UNIQUE (slug)
) TABLESPACE pg_default;

-- Seed default 19 roles (matching the 19 employee positions)
INSERT INTO public.roles (id, name, slug, description, color) VALUES
  (1, 'System Administrator', 'system-administrator', 'Full system configuration, security, and user management', 'bg-red-100 text-red-700'),
  (2, 'Health Center Director', 'health-center-director', 'Supervises health center operations and clinical care', 'bg-blue-100 text-blue-700'),
  (3, 'Doctor', 'doctor', 'Performs medical consultations and prescribes treatment', 'bg-cyan-100 text-cyan-700'),
  (4, 'Nurse', 'nurse', 'Patient triage, vital signs, and clinical assistance', 'bg-cyan-100 text-cyan-700'),
  (5, 'Dentist', 'dentist', 'Dental consultations, oral health, and procedures', 'bg-cyan-100 text-cyan-700'),
  (6, 'Laboratory Technician', 'laboratory-technician', 'Laboratory tests, diagnostics, and specimen analysis', 'bg-cyan-100 text-cyan-700'),
  (7, 'Medical Records Clerk', 'medical-records-clerk', 'Manages patient files and medical record archiving', 'bg-sky-100 text-sky-700'),
  (8, 'Appointment Clerk', 'appointment-clerk', 'Schedules patient consultations and clinic appointments', 'bg-sky-100 text-sky-700'),
  (9, 'Sanitation Director', 'sanitation-director', 'Oversees city sanitation policies and permit approvals', 'bg-amber-100 text-amber-700'),
  (10, 'Inspector', 'inspector', 'Conducts field sanitation inspections and compliance reviews', 'bg-yellow-100 text-yellow-700'),
  (11, 'Permit Clerk', 'permit-clerk', 'Processes sanitation permit applications and renewals', 'bg-yellow-100 text-yellow-700'),
  (12, 'Cashier', 'cashier', 'Receives payments for sanitation permits and certificates', 'bg-yellow-100 text-yellow-700'),
  (13, 'Immunization Coordinator', 'immunization-coordinator', 'Manages child immunization schedules and vaccine logistics', 'bg-emerald-100 text-emerald-700'),
  (14, 'Midwife', 'midwife', 'Maternal care, infant delivery, and vaccination assistance', 'bg-emerald-100 text-emerald-700'),
  (15, 'Nutritionist', 'nutritionist', 'Tracks child growth monitoring and nutrition counseling', 'bg-teal-100 text-teal-700'),
  (16, 'Nutrition Educator', 'nutrition-educator', 'Conducts community nutrition workshops and feeding programs', 'bg-teal-100 text-teal-700'),
  (17, 'Wastewater Officer', 'wastewater-officer', 'Inspects wastewater facilities and environmental safety', 'bg-purple-100 text-purple-700'),
  (18, 'Surveillance Officer', 'surveillance-officer', 'Monitors disease outbreaks and health surveillance responses', 'bg-indigo-100 text-indigo-700'),
  (19, 'Surveillance Coordinator', 'surveillance-coordinator', 'Coordinates disease tracking data and field investigations', 'bg-indigo-100 text-indigo-700')
ON CONFLICT (id) DO UPDATE SET
  name = EXCLUDED.name,
  slug = EXCLUDED.slug,
  description = EXCLUDED.description,
  color = EXCLUDED.color;

-- ============================================================
-- 3. permissions — granular permission definitions
-- ============================================================
CREATE TABLE IF NOT EXISTS public.permissions (
  id serial NOT NULL,
  module character varying(100) NOT NULL,
  slug character varying(100) NOT NULL,
  label character varying(100) NOT NULL,
  created_at timestamp with time zone NULL DEFAULT now(),
  CONSTRAINT permissions_pkey PRIMARY KEY (id),
  CONSTRAINT permissions_slug_key UNIQUE (slug)
) TABLESPACE pg_default;

-- Seed default permissions
INSERT INTO public.permissions (module, slug, label) VALUES
  ('Main Controls', 'dashboard.view', 'System Overview'),
  ('Main Controls', 'analytics.view', 'Analytics'),
  ('Main Controls', 'reports.view', 'Reports'),
  ('Main Controls', 'compliance.view', 'Compliance & Violations'),
  ('Health Center Services', 'patients.view', 'View Patients'),
  ('Health Center Services', 'patients.create', 'Create Patients'),
  ('Health Center Services', 'patients.edit', 'Edit Patients'),
  ('Health Center Services', 'patients.delete', 'Delete Patients'),
  ('Health Center Services', 'consultations.view', 'View Consultations'),
  ('Health Center Services', 'consultations.create', 'Create Consultations'),
  ('Health Center Services', 'triage.view', 'View Triage'),
  ('Health Center Services', 'triage.create', 'Create Triage'),
  ('Health Center Services', 'prescriptions.view', 'View Prescriptions'),
  ('Health Center Services', 'prescriptions.create', 'Create Prescriptions'),
  ('Sanitation Permits', 'permits.view', 'View Permits'),
  ('Sanitation Permits', 'permits.create', 'Create Permits'),
  ('Sanitation Permits', 'permits.approve', 'Approve Permits'),
  ('Sanitation Permits', 'inspections.view', 'View Inspections'),
  ('Sanitation Permits', 'inspections.conduct', 'Conduct Inspections'),
  ('Immunization & Nutrition', 'immunization.view', 'View Records'),
  ('Immunization & Nutrition', 'immunization.create', 'Create Records'),
  ('Immunization & Nutrition', 'immunization.edit', 'Edit Records'),
  ('System Management', 'users.view', 'View Users'),
  ('System Management', 'users.create', 'Create Users'),
  ('System Management', 'users.edit', 'Edit Users'),
  ('System Management', 'users.delete', 'Delete Users'),
  ('System Management', 'roles.manage', 'Manage Roles'),
  ('System Management', 'settings.manage', 'Manage Settings'),
  ('System Management', 'logs.view', 'View Logs')
ON CONFLICT (slug) DO NOTHING;

-- ============================================================
-- 4. role_permissions — many-to-many join
-- ============================================================
CREATE TABLE IF NOT EXISTS public.role_permissions (
  id serial NOT NULL,
  role_id integer NOT NULL,
  permission_id integer NOT NULL,
  created_at timestamp with time zone NULL DEFAULT now(),
  CONSTRAINT role_permissions_pkey PRIMARY KEY (id),
  CONSTRAINT role_permissions_unique UNIQUE (role_id, permission_id),
  CONSTRAINT role_permissions_role_id_fkey FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE,
  CONSTRAINT role_permissions_permission_id_fkey FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE
) TABLESPACE pg_default;

CREATE INDEX IF NOT EXISTS idx_role_permissions_role_id ON public.role_permissions USING btree (role_id) TABLESPACE pg_default;
CREATE INDEX IF NOT EXISTS idx_role_permissions_permission_id ON public.role_permissions USING btree (permission_id) TABLESPACE pg_default;

-- Seed default role permissions
-- 1. System Administrator (Role 1): All 29 permissions
INSERT INTO public.role_permissions (role_id, permission_id)
SELECT 1, id FROM public.permissions ON CONFLICT (role_id, permission_id) DO NOTHING;

-- 2. Health Center Director (Role 2): Main Controls + All Health Center permissions + View Users & Logs
INSERT INTO public.role_permissions (role_id, permission_id)
SELECT 2, id FROM public.permissions WHERE slug IN ('dashboard.view','analytics.view','reports.view','compliance.view','patients.view','patients.create','patients.edit','patients.delete','consultations.view','consultations.create','triage.view','triage.create','prescriptions.view','prescriptions.create','users.view','logs.view') ON CONFLICT (role_id, permission_id) DO NOTHING;

-- 3. Doctor (Role 3)
INSERT INTO public.role_permissions (role_id, permission_id)
SELECT 3, id FROM public.permissions WHERE slug IN ('dashboard.view','reports.view','patients.view','patients.create','patients.edit','consultations.view','consultations.create','triage.view','prescriptions.view','prescriptions.create') ON CONFLICT (role_id, permission_id) DO NOTHING;

-- 4. Nurse (Role 4)
INSERT INTO public.role_permissions (role_id, permission_id)
SELECT 4, id FROM public.permissions WHERE slug IN ('dashboard.view','reports.view','patients.view','patients.create','patients.edit','triage.view','triage.create','consultations.view','prescriptions.view') ON CONFLICT (role_id, permission_id) DO NOTHING;

-- 5. Dentist (Role 5)
INSERT INTO public.role_permissions (role_id, permission_id)
SELECT 5, id FROM public.permissions WHERE slug IN ('dashboard.view','reports.view','patients.view','patients.create','patients.edit','consultations.view','consultations.create','prescriptions.view','prescriptions.create') ON CONFLICT (role_id, permission_id) DO NOTHING;

-- 6. Laboratory Technician (Role 6)
INSERT INTO public.role_permissions (role_id, permission_id)
SELECT 6, id FROM public.permissions WHERE slug IN ('dashboard.view','patients.view','consultations.view','prescriptions.view') ON CONFLICT (role_id, permission_id) DO NOTHING;

-- 7. Medical Records Clerk (Role 7)
INSERT INTO public.role_permissions (role_id, permission_id)
SELECT 7, id FROM public.permissions WHERE slug IN ('dashboard.view','patients.view','patients.create','patients.edit') ON CONFLICT (role_id, permission_id) DO NOTHING;

-- 8. Appointment Clerk (Role 8)
INSERT INTO public.role_permissions (role_id, permission_id)
SELECT 8, id FROM public.permissions WHERE slug IN ('dashboard.view','patients.view','patients.create','triage.view') ON CONFLICT (role_id, permission_id) DO NOTHING;

-- 9. Sanitation Director (Role 9)
INSERT INTO public.role_permissions (role_id, permission_id)
SELECT 9, id FROM public.permissions WHERE slug IN ('dashboard.view','analytics.view','reports.view','compliance.view','permits.view','permits.create','permits.approve','inspections.view','inspections.conduct','users.view','logs.view') ON CONFLICT (role_id, permission_id) DO NOTHING;

-- 10. Inspector (Role 10)
INSERT INTO public.role_permissions (role_id, permission_id)
SELECT 10, id FROM public.permissions WHERE slug IN ('dashboard.view','reports.view','permits.view','inspections.view','inspections.conduct') ON CONFLICT (role_id, permission_id) DO NOTHING;

-- 11. Permit Clerk (Role 11)
INSERT INTO public.role_permissions (role_id, permission_id)
SELECT 11, id FROM public.permissions WHERE slug IN ('dashboard.view','permits.view','permits.create','inspections.view') ON CONFLICT (role_id, permission_id) DO NOTHING;

-- 12. Cashier (Role 12)
INSERT INTO public.role_permissions (role_id, permission_id)
SELECT 12, id FROM public.permissions WHERE slug IN ('dashboard.view','permits.view') ON CONFLICT (role_id, permission_id) DO NOTHING;

-- 13. Immunization Coordinator (Role 13)
INSERT INTO public.role_permissions (role_id, permission_id)
SELECT 13, id FROM public.permissions WHERE slug IN ('dashboard.view','analytics.view','reports.view','immunization.view','immunization.create','immunization.edit','patients.view') ON CONFLICT (role_id, permission_id) DO NOTHING;

-- 14. Midwife (Role 14)
INSERT INTO public.role_permissions (role_id, permission_id)
SELECT 14, id FROM public.permissions WHERE slug IN ('dashboard.view','reports.view','immunization.view','immunization.create','patients.view','patients.create','triage.create') ON CONFLICT (role_id, permission_id) DO NOTHING;

-- 15. Nutritionist (Role 15)
INSERT INTO public.role_permissions (role_id, permission_id)
SELECT 15, id FROM public.permissions WHERE slug IN ('dashboard.view','analytics.view','reports.view','immunization.view','immunization.create','immunization.edit','patients.view') ON CONFLICT (role_id, permission_id) DO NOTHING;

-- 16. Nutrition Educator (Role 16)
INSERT INTO public.role_permissions (role_id, permission_id)
SELECT 16, id FROM public.permissions WHERE slug IN ('dashboard.view','reports.view','immunization.view','immunization.create') ON CONFLICT (role_id, permission_id) DO NOTHING;

-- 17. Wastewater Officer (Role 17)
INSERT INTO public.role_permissions (role_id, permission_id)
SELECT 17, id FROM public.permissions WHERE slug IN ('dashboard.view','analytics.view','reports.view','inspections.view','inspections.conduct','permits.view') ON CONFLICT (role_id, permission_id) DO NOTHING;

-- 18. Surveillance Officer (Role 18)
INSERT INTO public.role_permissions (role_id, permission_id)
SELECT 18, id FROM public.permissions WHERE slug IN ('dashboard.view','analytics.view','reports.view','compliance.view','patients.view','consultations.view','inspections.view','logs.view') ON CONFLICT (role_id, permission_id) DO NOTHING;

-- 19. Surveillance Coordinator (Role 19)
INSERT INTO public.role_permissions (role_id, permission_id)
SELECT 19, id FROM public.permissions WHERE slug IN ('dashboard.view','analytics.view','reports.view','compliance.view','patients.view','consultations.view','inspections.view','logs.view') ON CONFLICT (role_id, permission_id) DO NOTHING;

-- ============================================================
-- 5. activity_logs — user activity audit trail
-- ============================================================
CREATE TABLE IF NOT EXISTS public.activity_logs (
  id serial NOT NULL,
  user_id integer NULL,
  user_name character varying(100) NULL DEFAULT 'System',
  role character varying(100) NULL DEFAULT 'System Administrator',
  action text NOT NULL,
  module character varying(100) NULL,
  details text NULL,
  ip_address character varying(45) NULL,
  device character varying(255) NULL DEFAULT 'Desktop • Chrome (Linux)',
  status text NULL DEFAULT 'Success',
  created_at timestamp with time zone NULL DEFAULT now(),
  CONSTRAINT activity_logs_pkey PRIMARY KEY (id),
  CONSTRAINT activity_logs_status_check CHECK (
    status = ANY (ARRAY['Success'::text, 'Failed'::text])
  )
) TABLESPACE pg_default;

CREATE INDEX IF NOT EXISTS idx_activity_logs_user_id ON public.activity_logs USING btree (user_id) TABLESPACE pg_default;
CREATE INDEX IF NOT EXISTS idx_activity_logs_created_at ON public.activity_logs USING btree (created_at DESC) TABLESPACE pg_default;
CREATE INDEX IF NOT EXISTS idx_activity_logs_status ON public.activity_logs USING btree (status) TABLESPACE pg_default;

-- ============================================================
-- 6. employees — Seed Sample Employees (10 Primary Roles)
-- ============================================================
INSERT INTO "public"."employees" ("id", "employee_id", "password", "full_name", "department", "role", "created_at", "role_description", "username", "email", "status", "last_login") VALUES
  (1, 'HSA-ADMIN-01', '$2y$10$jyYas/PJ.1ehBbLY.Do75.BU4R7M0Gwt7.msyXPL6Eetd.c/22Xfa', 'Joshua Sierra', 'Administration', 'System Admin', '2026-07-28 11:35:33.49098', 'System Administrator', 'HSA-ADMIN-01', 'admin@health.gov.ph', 'Active', null),
  (2, 'HCD-0001', '$2y$10$jyYas/PJ.1ehBbLY.Do75.BU4R7M0Gwt7.msyXPL6Eetd.c/22Xfa', 'Maria Santos', 'Health Center', 'Health Center Director', '2026-07-28 11:35:33.49098', 'Health Center Director', 'HCD-0001', 'maria@health.gov.ph', 'Active', null),
  (3, 'HMP-0001', '$2y$10$jyYas/PJ.1ehBbLY.Do75.BU4R7M0Gwt7.msyXPL6Eetd.c/22Xfa', 'Juan Dela Cruz', 'Health Center', 'Medical Practitioner', '2026-07-28 11:35:33.49098', 'Doctor', 'HMP-0001', 'doctor@health.gov.ph', 'Active', null),
  (4, 'HMP-0002', '$2y$10$jyYas/PJ.1ehBbLY.Do75.BU4R7M0Gwt7.msyXPL6Eetd.c/22Xfa', 'Ana Reyes', 'Health Center', 'Medical Practitioner', '2026-07-28 11:35:33.49098', 'Nurse', 'HMP-0002', 'nurse@health.gov.ph', 'Active', null),
  (5, 'HMP-0003', '$2y$10$jyYas/PJ.1ehBbLY.Do75.BU4R7M0Gwt7.msyXPL6Eetd.c/22Xfa', 'Carlo Ramos', 'Health Center', 'Medical Practitioner', '2026-07-28 11:35:33.49098', 'Dentist', 'HMP-0003', 'dentist@health.gov.ph', 'Active', null),
  (6, 'HMP-0004', '$2y$10$jyYas/PJ.1ehBbLY.Do75.BU4R7M0Gwt7.msyXPL6Eetd.c/22Xfa', 'Leo Cruz', 'Health Center', 'Medical Practitioner', '2026-07-28 11:35:33.49098', 'Laboratory Technician', 'HMP-0004', 'labtech@health.gov.ph', 'Active', null),
  (7, 'HCS-0001', '$2y$10$jyYas/PJ.1ehBbLY.Do75.BU4R7M0Gwt7.msyXPL6Eetd.c/22Xfa', 'Rose Garcia', 'Health Center', 'Health Center Staff', '2026-07-28 11:35:33.49098', 'Medical Records Clerk', 'HCS-0001', 'records@health.gov.ph', 'Active', null),
  (8, 'HCS-0002', '$2y$10$jyYas/PJ.1ehBbLY.Do75.BU4R7M0Gwt7.msyXPL6Eetd.c/22Xfa', 'Mark Lim', 'Health Center', 'Health Center Staff', '2026-07-28 11:35:33.49098', 'Appointment Clerk', 'HCS-0002', 'appointment@health.gov.ph', 'Active', null),
  (9, 'SD-0001', '$2y$10$jyYas/PJ.1ehBbLY.Do75.BU4R7M0Gwt7.msyXPL6Eetd.c/22Xfa', 'Pedro Garcia', 'Sanitation', 'Sanitation Director', '2026-07-28 11:35:33.49098', 'Sanitation Director', 'SD-0001', 'sdirector@health.gov.ph', 'Active', null),
  (10, 'SO-0001', '$2y$10$jyYas/PJ.1ehBbLY.Do75.BU4R7M0Gwt7.msyXPL6Eetd.c/22Xfa', 'Liza Cruz', 'Sanitation', 'Sanitation Officer', '2026-07-28 11:35:33.49098', 'Inspector', 'SO-0001', 'inspector@health.gov.ph', 'Active', null),
  (11, 'SO-0002', '$2y$10$jyYas/PJ.1ehBbLY.Do75.BU4R7M0Gwt7.msyXPL6Eetd.c/22Xfa', 'Kevin Reyes', 'Sanitation', 'Sanitation Officer', '2026-07-28 11:35:33.49098', 'Permit Clerk', 'SO-0002', 'permit@health.gov.ph', 'Active', null),
  (12, 'SO-0003', '$2y$10$jyYas/PJ.1ehBbLY.Do75.BU4R7M0Gwt7.msyXPL6Eetd.c/22Xfa', 'Jenny Flores', 'Sanitation', 'Sanitation Officer', '2026-07-28 11:35:33.49098', 'Cashier', 'SO-0003', 'cashier@health.gov.ph', 'Active', null),
  (13, 'IL-0001', '$2y$10$jyYas/PJ.1ehBbLY.Do75.BU4R7M0Gwt7.msyXPL6Eetd.c/22Xfa', 'Grace Mendoza', 'Immunization', 'Immunization Lead', '2026-07-28 11:35:33.49098', 'Immunization Coordinator', 'IL-0001', 'immunization@health.gov.ph', 'Active', null),
  (14, 'IL-0002', '$2y$10$jyYas/PJ.1ehBbLY.Do75.BU4R7M0Gwt7.msyXPL6Eetd.c/22Xfa', 'Sarah Cruz', 'Immunization', 'Immunization Lead', '2026-07-28 11:35:33.49098', 'Midwife', 'IL-0002', 'midwife@health.gov.ph', 'Active', null),
  (15, 'NS-0001', '$2y$10$jyYas/PJ.1ehBbLY.Do75.BU4R7M0Gwt7.msyXPL6Eetd.c/22Xfa', 'Carla Ramos', 'Nutrition', 'Nutrition Staff', '2026-07-28 11:35:33.49098', 'Nutritionist', 'NS-0001', 'nutritionist@health.gov.ph', 'Active', null),
  (16, 'NS-0002', '$2y$10$jyYas/PJ.1ehBbLY.Do75.BU4R7M0Gwt7.msyXPL6Eetd.c/22Xfa', 'Emma Santos', 'Nutrition', 'Nutrition Staff', '2026-07-28 11:35:33.49098', 'Nutrition Educator', 'NS-0002', 'educator@health.gov.ph', 'Active', null),
  (17, 'WL-0001', '$2y$10$jyYas/PJ.1ehBbLY.Do75.BU4R7M0Gwt7.msyXPL6Eetd.c/22Xfa', 'Ramon Flores', 'Wastewater', 'Wastewater Lead', '2026-07-28 11:35:33.49098', 'Wastewater Officer', 'WL-0001', 'wastewater@health.gov.ph', 'Active', null),
  (18, 'SL-0001', '$2y$10$jyYas/PJ.1ehBbLY.Do75.BU4R7M0Gwt7.msyXPL6Eetd.c/22Xfa', 'Sofia Lim', 'Health Surveillance', 'Surveillance Lead', '2026-07-28 11:35:33.49098', 'Surveillance Officer', 'SL-0001', 'surveillance@health.gov.ph', 'Active', null),
  (19, 'SL-0002', '$2y$10$jyYas/PJ.1ehBbLY.Do75.BU4R7M0Gwt7.msyXPL6Eetd.c/22Xfa', 'James Rivera', 'Health Surveillance', 'Surveillance Lead', '2026-07-28 11:35:33.49098', 'Surveillance Coordinator', 'SL-0002', 'coordinator@health.gov.ph', 'Active', null)
ON CONFLICT (id) DO NOTHING;
