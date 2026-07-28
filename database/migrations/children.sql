-- ============================================================
-- CHILD RECORDS MODULE DATABASE SCHEMA
-- ============================================================

create table public.children (
  id serial not null,
  child_id character varying(20) not null,
  first_name character varying(50) not null,
  last_name character varying(50) not null,
  middle_name character varying(50) null,
  gender text not null,
  birth_date date not null,
  birth_weight numeric(4, 2) null,
  birth_height numeric(5, 2) null,
  blood_type character varying(10) null,
  address text not null,
  barangay character varying(100) not null,
  mother_name character varying(100) not null,
  mother_contact character varying(20) null,
  mother_occupation character varying(100) null,
  father_name character varying(100) null,
  father_contact character varying(20) null,
  father_occupation character varying(100) null,
  family_history text null,
  allergies text null,
  health_center character varying(100) not null,
  registration_date date not null,
  status text null default 'active'::text,
  nutrition_status text null,
  vaccine_compliance integer null default 0,
  last_visit date null,
  created_at timestamp with time zone null default now(),
  updated_at timestamp with time zone null default now(),
  constraint children_pkey primary key (id),
  constraint children_child_id_key unique (child_id),
  constraint children_gender_check check (
    gender = any (array['Male'::text, 'Female'::text])
  ),
  constraint children_status_check check (
    status = any (array['active'::text, 'inactive'::text])
  ),
  constraint children_nutrition_status_check check (
    nutrition_status = any (array['Normal'::text, 'Moderate'::text, 'Critical'::text, 'Overweight'::text])
  ),
  constraint children_vaccine_compliance_check check (
    (vaccine_compliance >= 0) and (vaccine_compliance <= 100)
  )
) TABLESPACE pg_default;

-- Indexes
create index IF not exists idx_children_child_id on public.children using btree (child_id) TABLESPACE pg_default;
create index IF not exists idx_children_status on public.children using btree (status) TABLESPACE pg_default;
create index IF not exists idx_children_barangay on public.children using btree (barangay) TABLESPACE pg_default;
create index IF not exists idx_children_nutrition_status on public.children using btree (nutrition_status) TABLESPACE pg_default;
create index IF not exists idx_children_last_name on public.children using btree (last_name) TABLESPACE pg_default;
create index IF not exists idx_children_mother_name on public.children using btree (mother_name) TABLESPACE pg_default;

-- Trigger to update updated_at timestamp
create trigger handle_children_updated_at BEFORE
update on children for EACH row
execute FUNCTION handle_updated_at ();
