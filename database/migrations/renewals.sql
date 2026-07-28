-- ============================================================
-- RENEWALS MODULE DATABASE SCHEMA
-- ============================================================

-- 1. Renewal Applications Table
create table public.renewals (
  id serial not null,
  renewal_id character varying(20) not null,
  permit_id integer not null,
  applicant character varying(100) not null,
  business_type character varying(50) not null,
  current_fee numeric(10, 2) not null,
  renewal_fee numeric(10, 2) not null,
  status text null default 'pending'::text,
  payment_method character varying(50) null,
  payment_reference character varying(100) null,
  date_applied date not null default CURRENT_DATE,
  date_approved date null,
  new_expiry_date date null,
  notes text null,
  documents jsonb null default '[]'::jsonb,
  created_at timestamp with time zone null default now(),
  updated_at timestamp with time zone null default now(),
  constraint renewals_pkey primary key (id),
  constraint renewals_renewal_id_key unique (renewal_id),
  constraint renewals_permit_id_fkey foreign KEY (permit_id) references permits (id) on delete CASCADE,
  constraint renewals_status_check check (
    status = any (array['pending'::text, 'under_review'::text, 'approved'::text, 'rejected'::text])
  )
) TABLESPACE pg_default;

-- 2. Renewal History Table
create table public.renewal_history (
  id serial not null,
  permit_id character varying(20) not null,
  applicant character varying(100) not null,
  renewal_date date not null,
  fee_paid numeric(10, 2) not null,
  new_expiry date not null,
  status text null default 'completed'::text,
  created_at timestamp with time zone null default now(),
  constraint renewal_history_pkey primary key (id),
  constraint renewal_history_status_check check (
    status = any (array['completed'::text, 'expired'::text])
  )
) TABLESPACE pg_default;

-- Indexes for renewals
create index IF not exists idx_renewals_permit_id on public.renewals using btree (permit_id) TABLESPACE pg_default;
create index IF not exists idx_renewals_status on public.renewals using btree (status) TABLESPACE pg_default;
create index IF not exists idx_renewals_date_applied on public.renewals using btree (date_applied) TABLESPACE pg_default;
create index IF not exists idx_renewals_applicant on public.renewals using btree (applicant) TABLESPACE pg_default;

-- Indexes for renewal_history
create index IF not exists idx_renewal_history_permit_id on public.renewal_history using btree (permit_id) TABLESPACE pg_default;
create index IF not exists idx_renewal_history_renewal_date on public.renewal_history using btree (renewal_date) TABLESPACE pg_default;

-- Trigger to update updated_at timestamp
create trigger handle_renewals_updated_at BEFORE
update on renewals for EACH row
execute FUNCTION handle_updated_at ();