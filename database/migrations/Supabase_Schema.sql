create table public.activity_logs (
  id serial not null,
  user_id integer null,
  user_name character varying(100) null default 'System'::character varying,
  action text not null,
  module character varying(100) null,
  details text null,
  ip_address character varying(45) null,
  status text null default 'Success'::text,
  created_at timestamp with time zone null default now(),
  role character varying(100) null default 'System Administrator'::character varying,
  device character varying(255) null default 'Desktop • Chrome (Linux)'::character varying,
  constraint activity_logs_pkey primary key (id),
  constraint activity_logs_status_check check (
    (
      status = any (array['Success'::text, 'Failed'::text])
    )
  )
) TABLESPACE pg_default;

create index IF not exists idx_activity_logs_user_id on public.activity_logs using btree (user_id) TABLESPACE pg_default;

create index IF not exists idx_activity_logs_created_at on public.activity_logs using btree (created_at desc) TABLESPACE pg_default;

create index IF not exists idx_activity_logs_status on public.activity_logs using btree (status) TABLESPACE pg_default;

create index IF not exists idx_activity_logs_role on public.activity_logs using btree (role) TABLESPACE pg_default;

create index IF not exists idx_activity_logs_ip on public.activity_logs using btree (ip_address) TABLESPACE pg_default;

create index IF not exists idx_activity_logs_module on public.activity_logs using btree (module) TABLESPACE pg_default;

create table public.ai_analytics_logs (
  id serial not null,
  insight_key character varying(100) not null,
  category character varying(100) not null,
  badge character varying(50) not null,
  color character varying(50) not null,
  title character varying(255) not null,
  action_text text null,
  confidence integer null default 90,
  metadata jsonb null,
  created_at timestamp with time zone null default CURRENT_TIMESTAMP,
  constraint ai_analytics_logs_pkey primary key (id)
) TABLESPACE pg_default;

create table public.appointments (
  id serial not null,
  appointment_id character varying(20) not null,
  patient_id integer not null,
  employee_id integer not null,
  service_type character varying(100) not null,
  appointment_date date not null,
  appointment_time time without time zone not null,
  status text null default 'pending'::text,
  priority text null default 'medium'::text,
  notes text null,
  reminder_sent boolean null default false,
  created_at timestamp with time zone null default now(),
  updated_at timestamp with time zone null default now(),
  constraint appointments_pkey primary key (id),
  constraint appointments_appointment_id_key unique (appointment_id),
  constraint appointments_employee_id_fkey foreign KEY (employee_id) references employees (id),
  constraint appointments_patient_id_fkey foreign KEY (patient_id) references patients (id) on delete CASCADE,
  constraint appointments_priority_check check (
    (
      priority = any (
        array[
          'critical'::text,
          'high'::text,
          'medium'::text,
          'low'::text
        ]
      )
    )
  ),
  constraint appointments_status_check check (
    (
      status = any (
        array[
          'pending'::text,
          'approved'::text,
          'completed'::text,
          'cancelled'::text,
          'no_show'::text
        ]
      )
    )
  )
) TABLESPACE pg_default;

create table public.backup_history (
  id serial not null,
  backup_type character varying(50) not null,
  file_name character varying(255) not null,
  file_size character varying(50) null,
  status character varying(50) not null default 'completed'::character varying,
  error_message text null,
  started_at timestamp with time zone null default CURRENT_TIMESTAMP,
  completed_at timestamp with time zone null,
  created_by character varying(100) null default 'System'::character varying,
  constraint backup_history_pkey primary key (id)
) TABLESPACE pg_default;

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
    (
      gender = any (array['Male'::text, 'Female'::text])
    )
  ),
  constraint children_nutrition_status_check check (
    (
      nutrition_status = any (
        array[
          'Normal'::text,
          'Moderate'::text,
          'Critical'::text,
          'Overweight'::text
        ]
      )
    )
  ),
  constraint children_status_check check (
    (
      status = any (array['active'::text, 'inactive'::text])
    )
  ),
  constraint children_vaccine_compliance_check check (
    (
      (vaccine_compliance >= 0)
      and (vaccine_compliance <= 100)
    )
  )
) TABLESPACE pg_default;

create index IF not exists idx_children_child_id on public.children using btree (child_id) TABLESPACE pg_default;

create index IF not exists idx_children_status on public.children using btree (status) TABLESPACE pg_default;

create index IF not exists idx_children_barangay on public.children using btree (barangay) TABLESPACE pg_default;

create index IF not exists idx_children_nutrition_status on public.children using btree (nutrition_status) TABLESPACE pg_default;

create index IF not exists idx_children_last_name on public.children using btree (last_name) TABLESPACE pg_default;

create index IF not exists idx_children_mother_name on public.children using btree (mother_name) TABLESPACE pg_default;

create trigger handle_children_updated_at BEFORE
update on children for EACH row
execute FUNCTION handle_updated_at ();

create table public.consultations (
  id serial not null,
  consultation_id character varying(20) not null,
  patient_id integer not null,
  employee_id integer not null,
  appointment_id integer null,
  date date not null,
  time time without time zone not null,
  diagnosis text null,
  icd_code character varying(20) null,
  symptoms text null,
  vital_signs jsonb null,
  treatment_plan text null,
  notes text null,
  follow_up_date date null,
  status text null default 'completed'::text,
  created_at timestamp with time zone null default now(),
  updated_at timestamp with time zone null default now(),
  constraint consultations_pkey primary key (id),
  constraint consultations_consultation_id_key unique (consultation_id),
  constraint consultations_appointment_id_fkey foreign KEY (appointment_id) references appointments (id),
  constraint consultations_employee_id_fkey foreign KEY (employee_id) references employees (id),
  constraint consultations_patient_id_fkey foreign KEY (patient_id) references patients (id) on delete CASCADE,
  constraint consultations_status_check check (
    (
      status = any (
        array[
          'in_progress'::text,
          'completed'::text,
          'referred'::text,
          'follow_up'::text
        ]
      )
    )
  )
) TABLESPACE pg_default;

create table public.employees (
  id serial not null,
  employee_id character varying(50) not null,
  password character varying(255) not null,
  full_name character varying(100) not null,
  department character varying(100) null,
  role character varying(50) null default 'employee'::character varying,
  created_at timestamp without time zone null default CURRENT_TIMESTAMP,
  role_description text null,
  username character varying(50) null,
  email character varying(100) null,
  status text null default 'Active'::text,
  last_login timestamp with time zone null,
  role_id integer null,
  constraint employees_pkey primary key (id),
  constraint employees_employee_id_key unique (employee_id),
  constraint employees_username_key unique (username),
  constraint employees_role_id_fkey foreign KEY (role_id) references roles (id) on delete set null,
  constraint employees_status_check check (
    (
      status = any (
        array[
          'Active'::text,
          'Inactive'::text,
          'Suspended'::text
        ]
      )
    )
  )
) TABLESPACE pg_default;

create table public.feature_flags (
  id serial not null,
  key character varying(150) not null,
  flag_name character varying(150) not null,
  enabled boolean null default true,
  description text null,
  created_at timestamp with time zone null default CURRENT_TIMESTAMP,
  updated_at timestamp with time zone null default CURRENT_TIMESTAMP,
  constraint feature_flags_pkey primary key (id),
  constraint feature_flags_key_key unique (key)
) TABLESPACE pg_default;

create index IF not exists idx_feature_flags_key on public.feature_flags using btree (key) TABLESPACE pg_default;

create table public.inspections (
  id serial not null,
  inspection_id character varying(20) not null,
  permit_id integer not null,
  inspector_id integer not null,
  scheduled_date date not null,
  scheduled_time time without time zone not null,
  conducted_date timestamp with time zone null,
  findings json null,
  overall_status text null default 'partially_compliant'::text,
  recommendations text null,
  attachments json null,
  status text null default 'scheduled'::text,
  completed_at timestamp with time zone null,
  created_at timestamp with time zone null default now(),
  updated_at timestamp with time zone null default now(),
  notes text null,
  follow_up_date date null,
  constraint inspections_pkey primary key (id),
  constraint inspections_inspection_id_key unique (inspection_id),
  constraint inspections_inspector_id_fkey foreign KEY (inspector_id) references employees (id),
  constraint inspections_permit_id_fkey foreign KEY (permit_id) references permits (id) on delete CASCADE,
  constraint inspections_overall_status_check check (
    (
      overall_status = any (
        array[
          'compliant'::text,
          'partially_compliant'::text,
          'non_compliant'::text
        ]
      )
    )
  ),
  constraint inspections_status_check check (
    (
      status = any (
        array[
          'scheduled'::text,
          'completed'::text,
          'cancelled'::text
        ]
      )
    )
  )
) TABLESPACE pg_default;

create index IF not exists idx_inspections_permit_id on public.inspections using btree (permit_id) TABLESPACE pg_default;

create index IF not exists idx_inspections_inspector_id on public.inspections using btree (inspector_id) TABLESPACE pg_default;

create index IF not exists idx_inspections_scheduled_date on public.inspections using btree (scheduled_date) TABLESPACE pg_default;

create index IF not exists idx_inspections_status on public.inspections using btree (status) TABLESPACE pg_default;

create index IF not exists idx_inspections_overall_status on public.inspections using btree (overall_status) TABLESPACE pg_default;

create index IF not exists idx_inspections_follow_up_date on public.inspections using btree (follow_up_date) TABLESPACE pg_default;

create trigger handle_inspections_updated_at BEFORE
update on inspections for EACH row
execute FUNCTION handle_updated_at ();

create table public.medical_records (
  id serial not null,
  patient_id integer not null,
  record_type text not null,
  date date not null,
  description text not null,
  attachments jsonb null,
  created_by integer not null,
  created_at timestamp with time zone null default now(),
  constraint medical_records_pkey primary key (id),
  constraint medical_records_created_by_fkey foreign KEY (created_by) references employees (id),
  constraint medical_records_patient_id_fkey foreign KEY (patient_id) references patients (id) on delete CASCADE,
  constraint medical_records_record_type_check check (
    (
      record_type = any (
        array[
          'consultation'::text,
          'lab'::text,
          'imaging'::text,
          'procedure'::text,
          'other'::text
        ]
      )
    )
  )
) TABLESPACE pg_default;

create table public.patients (
  id serial not null,
  patient_id character varying(20) not null,
  first_name character varying(50) not null,
  last_name character varying(50) not null,
  middle_name character varying(50) null,
  birth_date date not null,
  gender text not null,
  blood_type text null,
  contact character varying(20) not null,
  email character varying(100) null,
  address text not null,
  barangay character varying(50) null,
  emergency_contact character varying(50) null,
  emergency_contact_number character varying(20) null,
  allergies text null,
  medical_history jsonb null,
  registration_date date not null,
  status text null default 'active'::text,
  created_at timestamp with time zone null default now(),
  updated_at timestamp with time zone null default now(),
  constraint patients_pkey primary key (id),
  constraint patients_patient_id_key unique (patient_id),
  constraint patients_blood_type_check check (
    (
      blood_type = any (
        array[
          'A+'::text,
          'A-'::text,
          'B+'::text,
          'B-'::text,
          'AB+'::text,
          'AB-'::text,
          'O+'::text,
          'O-'::text
        ]
      )
    )
  ),
  constraint patients_gender_check check (
    (
      gender = any (
        array['Male'::text, 'Female'::text, 'Other'::text]
      )
    )
  ),
  constraint patients_status_check check (
    (
      status = any (
        array[
          'active'::text,
          'inactive'::text,
          'archived'::text
        ]
      )
    )
  )
) TABLESPACE pg_default;

create table public.payments (
  id bigserial not null,
  payment_id character varying(20) not null,
  permit_id integer not null,
  amount numeric(10, 2) not null,
  method character varying(20) not null,
  reference_number character varying(50) null,
  status character varying(20) null default 'pending'::character varying,
  receipt_path character varying(255) null,
  paid_by character varying(100) null,
  paid_at timestamp with time zone null,
  notes text null,
  created_at timestamp with time zone null default now(),
  updated_at timestamp with time zone null default now(),
  constraint payments_pkey primary key (id),
  constraint payments_payment_id_key unique (payment_id),
  constraint payments_reference_number_key unique (reference_number),
  constraint payments_permit_id_fkey foreign KEY (permit_id) references permits (id) on delete CASCADE,
  constraint payments_method_check check (
    (
      (method)::text = any (
        (
          array[
            'cash'::character varying,
            'gcash'::character varying,
            'paymaya'::character varying,
            'bank_transfer'::character varying,
            'over_the_counter'::character varying
          ]
        )::text[]
      )
    )
  ),
  constraint payments_status_check check (
    (
      (status)::text = any (
        (
          array[
            'pending'::character varying,
            'completed'::character varying,
            'failed'::character varying,
            'refunded'::character varying
          ]
        )::text[]
      )
    )
  )
) TABLESPACE pg_default;

create index IF not exists idx_payments_permit_id on public.payments using btree (permit_id) TABLESPACE pg_default;

create index IF not exists idx_payments_status on public.payments using btree (status) TABLESPACE pg_default;

create index IF not exists idx_payments_method on public.payments using btree (method) TABLESPACE pg_default;

create index IF not exists idx_payments_created_at on public.payments using btree (created_at) TABLESPACE pg_default;

create index IF not exists idx_payments_paid_at on public.payments using btree (paid_at) TABLESPACE pg_default;

create trigger handle_payments_updated_at BEFORE
update on payments for EACH row
execute FUNCTION update_payments_timestamp ();

create table public.permissions (
  id serial not null,
  module character varying(100) not null,
  slug character varying(100) not null,
  label character varying(100) not null,
  created_at timestamp with time zone null default now(),
  constraint permissions_pkey primary key (id),
  constraint permissions_slug_key unique (slug)
) TABLESPACE pg_default;

create table public.permit_documents (
  id bigserial not null,
  permit_id integer not null,
  document_type character varying(50) not null,
  file_name character varying(255) not null,
  file_path text not null,
  file_size bigint null,
  mime_type character varying(100) null,
  uploaded_by integer not null,
  uploaded_at timestamp with time zone null default now(),
  verified boolean null default false,
  verified_by integer null,
  verified_at timestamp with time zone null,
  updated_at timestamp with time zone null default now(),
  document_id character varying(20) null,
  applicant character varying(100) null,
  file_type character varying(50) null,
  status text null default 'pending'::text,
  expiry_date date null,
  qr_code character varying(50) null,
  notes text null,
  constraint permit_documents_pkey primary key (id),
  constraint permit_documents_unique_file unique (permit_id, document_type, file_name),
  constraint permit_documents_document_id_key unique (document_id),
  constraint permit_documents_verified_by_fkey foreign KEY (verified_by) references employees (id),
  constraint permit_documents_permit_id_fkey foreign KEY (permit_id) references permits (id) on delete CASCADE,
  constraint permit_documents_uploaded_by_fkey foreign KEY (uploaded_by) references employees (id),
  constraint permit_documents_document_type_check check (
    (
      (document_type)::text = any (
        (
          array[
            'business_permit'::character varying,
            'sanitary_permit'::character varying,
            'fire_safety'::character varying,
            'zoning_clearance'::character varying,
            'environmental_compliance'::character varying,
            'building_permit'::character varying,
            'tax_clearance'::character varying,
            'other'::character varying
          ]
        )::text[]
      )
    )
  ),
  constraint permit_documents_status_check check (
    (
      status = any (
        array[
          'verified'::text,
          'pending'::text,
          'expired'::text
        ]
      )
    )
  )
) TABLESPACE pg_default;

create index IF not exists idx_permit_documents_permit_id on public.permit_documents using btree (permit_id) TABLESPACE pg_default;

create index IF not exists idx_permit_documents_uploaded_by on public.permit_documents using btree (uploaded_by) TABLESPACE pg_default;

create index IF not exists idx_permit_documents_document_type on public.permit_documents using btree (document_type) TABLESPACE pg_default;

create index IF not exists idx_permit_documents_verified on public.permit_documents using btree (verified) TABLESPACE pg_default
where
  (verified = false);

create index IF not exists idx_permit_documents_uploaded_at on public.permit_documents using btree (uploaded_at) TABLESPACE pg_default;

create index IF not exists idx_permit_documents_document_id on public.permit_documents using btree (document_id) TABLESPACE pg_default;

create index IF not exists idx_permit_documents_status on public.permit_documents using btree (status) TABLESPACE pg_default;

create index IF not exists idx_permit_documents_applicant on public.permit_documents using btree (applicant) TABLESPACE pg_default;

create index IF not exists idx_permit_documents_expiry_date on public.permit_documents using btree (expiry_date) TABLESPACE pg_default;

create index IF not exists idx_permit_documents_qr_code on public.permit_documents using btree (qr_code) TABLESPACE pg_default;

create index IF not exists idx_permit_documents_permit_status on public.permit_documents using btree (permit_id, status) TABLESPACE pg_default;

create trigger generate_document_id_trigger BEFORE INSERT on permit_documents for EACH row
execute FUNCTION generate_document_id ();

create trigger handle_permit_documents_updated_at BEFORE
update on permit_documents for EACH row
execute FUNCTION update_permit_documents_timestamp ();

create trigger populate_applicant_trigger BEFORE INSERT on permit_documents for EACH row
execute FUNCTION populate_applicant ();

create table public.permits (
  id serial not null,
  permit_id character varying(20) not null,
  applicant character varying(100) not null,
  business_name character varying(100) null,
  business_type character varying(50) not null,
  address text not null,
  owner_name character varying(100) not null,
  contact character varying(20) not null,
  email character varying(100) null,
  fee numeric(10, 2) not null,
  paid boolean null default false,
  payment_method character varying(50) null,
  payment_reference character varying(100) null,
  status text null default 'pending'::text,
  inspector_id integer null,
  inspection_date date null,
  approved_date date null,
  expiry_date date null,
  notes text null,
  created_at timestamp with time zone null default now(),
  updated_at timestamp with time zone null default now(),
  constraint permits_pkey primary key (id),
  constraint permits_permit_id_key unique (permit_id),
  constraint permits_inspector_id_fkey foreign KEY (inspector_id) references employees (id),
  constraint permits_status_check check (
    (
      status = any (
        array[
          'pending'::text,
          'under_review'::text,
          'approved'::text,
          'rejected'::text,
          'expired'::text
        ]
      )
    )
  )
) TABLESPACE pg_default;

create table public.prescriptions (
  id serial not null,
  prescription_id character varying(20) null,
  patient_id integer not null,
  employee_id integer not null,
  consultation_id integer null,
  date date not null default CURRENT_DATE,
  medications jsonb not null,
  notes text null,
  status text null default 'pending'::text,
  dispensed_by integer null,
  dispensed_at timestamp with time zone null,
  created_at timestamp with time zone null default now(),
  updated_at timestamp with time zone null default now(),
  constraint prescriptions_pkey primary key (id),
  constraint prescriptions_prescription_id_key unique (prescription_id),
  constraint prescriptions_employee_id_fkey foreign KEY (employee_id) references employees (id),
  constraint prescriptions_consultation_id_fkey foreign KEY (consultation_id) references consultations (id),
  constraint prescriptions_patient_id_fkey foreign KEY (patient_id) references patients (id) on delete CASCADE,
  constraint prescriptions_dispensed_by_fkey foreign KEY (dispensed_by) references employees (id),
  constraint prescriptions_status_check check (
    (
      status = any (
        array[
          'pending'::text,
          'dispensed'::text,
          'cancelled'::text
        ]
      )
    )
  )
) TABLESPACE pg_default;

create trigger generate_prescription_id_trigger BEFORE INSERT on prescriptions for EACH row
execute FUNCTION generate_prescription_id ();

create trigger update_prescription_timestamp BEFORE
update on prescriptions for EACH row
execute FUNCTION update_prescription_timestamp ();

create table public.referrals (
  id bigserial not null,
  referral_id character varying(20) not null,
  patient_id bigint not null,
  from_doctor_id bigint not null,
  to_doctor_id bigint null,
  to_hospital character varying(255) null,
  reason text not null,
  diagnosis text null,
  urgency character varying(20) null default 'medium'::character varying,
  referral_type character varying(20) null default 'specialist'::character varying,
  status character varying(20) null default 'pending'::character varying,
  accepted_at timestamp with time zone null,
  completed_at timestamp with time zone null,
  follow_up_date timestamp with time zone null,
  notes text null,
  feedback text null,
  created_at timestamp with time zone null default now(),
  updated_at timestamp with time zone null default now(),
  constraint referrals_pkey primary key (id),
  constraint referrals_referral_id_key unique (referral_id),
  constraint referrals_to_doctor_id_fkey foreign KEY (to_doctor_id) references employees (id) on delete set null,
  constraint referrals_from_doctor_id_fkey foreign KEY (from_doctor_id) references employees (id) on delete CASCADE,
  constraint referrals_patient_id_fkey foreign KEY (patient_id) references patients (id) on delete CASCADE,
  constraint referrals_urgency_check check (
    (
      (urgency)::text = any (
        (
          array[
            'emergency'::character varying,
            'high'::character varying,
            'medium'::character varying,
            'low'::character varying
          ]
        )::text[]
      )
    )
  ),
  constraint referrals_referral_type_check check (
    (
      (referral_type)::text = any (
        (
          array[
            'specialist'::character varying,
            'hospital'::character varying
          ]
        )::text[]
      )
    )
  ),
  constraint referrals_status_check check (
    (
      (status)::text = any (
        (
          array[
            'pending'::character varying,
            'accepted'::character varying,
            'completed'::character varying,
            'rejected'::character varying
          ]
        )::text[]
      )
    )
  )
) TABLESPACE pg_default;

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
    (
      status = any (array['completed'::text, 'expired'::text])
    )
  )
) TABLESPACE pg_default;

create index IF not exists idx_renewal_history_permit_id on public.renewal_history using btree (permit_id) TABLESPACE pg_default;

create index IF not exists idx_renewal_history_renewal_date on public.renewal_history using btree (renewal_date) TABLESPACE pg_default;

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
    (
      status = any (
        array[
          'pending'::text,
          'under_review'::text,
          'approved'::text,
          'rejected'::text
        ]
      )
    )
  )
) TABLESPACE pg_default;

create index IF not exists idx_renewals_permit_id on public.renewals using btree (permit_id) TABLESPACE pg_default;

create index IF not exists idx_renewals_status on public.renewals using btree (status) TABLESPACE pg_default;

create index IF not exists idx_renewals_date_applied on public.renewals using btree (date_applied) TABLESPACE pg_default;

create index IF not exists idx_renewals_applicant on public.renewals using btree (applicant) TABLESPACE pg_default;

create trigger handle_renewals_updated_at BEFORE
update on renewals for EACH row
execute FUNCTION handle_updated_at ();

create table public.role_permissions (
  id serial not null,
  role_id integer not null,
  permission_id integer not null,
  created_at timestamp with time zone null default now(),
  constraint role_permissions_pkey primary key (id),
  constraint role_permissions_unique unique (role_id, permission_id),
  constraint role_permissions_permission_id_fkey foreign KEY (permission_id) references permissions (id) on delete CASCADE,
  constraint role_permissions_role_id_fkey foreign KEY (role_id) references roles (id) on delete CASCADE
) TABLESPACE pg_default;

create index IF not exists idx_role_permissions_role_id on public.role_permissions using btree (role_id) TABLESPACE pg_default;

create index IF not exists idx_role_permissions_permission_id on public.role_permissions using btree (permission_id) TABLESPACE pg_default;

create table public.roles (
  id serial not null,
  name character varying(50) not null,
  slug character varying(50) not null,
  description text null,
  color character varying(100) null default 'bg-slate-100 text-slate-700'::character varying,
  user_count integer null default 0,
  created_at timestamp with time zone null default now(),
  updated_at timestamp with time zone null default now(),
  constraint roles_pkey primary key (id),
  constraint roles_name_key unique (name),
  constraint roles_slug_key unique (slug)
) TABLESPACE pg_default;

create table public.setting_categories (
  id serial not null,
  name character varying(100) not null,
  description text null,
  icon character varying(50) null default 'fa-gear'::character varying,
  display_order integer null default 0,
  created_at timestamp with time zone null default CURRENT_TIMESTAMP,
  updated_at timestamp with time zone null default CURRENT_TIMESTAMP,
  constraint setting_categories_pkey primary key (id),
  constraint setting_categories_name_key unique (name)
) TABLESPACE pg_default;

create table public.settings_versions (
  id serial not null,
  version_number integer not null,
  snapshot_json jsonb not null,
  changes_summary text null,
  created_by character varying(100) null,
  created_at timestamp with time zone null default CURRENT_TIMESTAMP,
  constraint settings_versions_pkey primary key (id)
) TABLESPACE pg_default;

create table public.surveillance_alerts (
  id serial not null,
  alert_code character varying(50) null,
  disease character varying(100) not null,
  barangay character varying(100) not null,
  cases integer not null default 0,
  threshold integer not null default 10,
  severity character varying(50) not null default 'Warning'::character varying,
  status character varying(50) not null default 'Active'::character varying,
  timestamp timestamp with time zone null default CURRENT_TIMESTAMP,
  escalation_level integer not null default 1,
  assigned_to character varying(150) null,
  response_actions text null,
  message text null,
  created_at timestamp with time zone null default CURRENT_TIMESTAMP,
  constraint surveillance_alerts_pkey primary key (id),
  constraint surveillance_alerts_alert_code_key unique (alert_code)
) TABLESPACE pg_default;

create table public.surveillance_cases (
  id serial not null,
  case_code character varying(50) null,
  disease character varying(100) not null,
  patient_name character varying(150) not null,
  age integer not null default 0,
  gender character varying(20) not null default 'Unknown'::character varying,
  address text null,
  barangay character varying(100) not null,
  contact_number character varying(50) null,
  symptoms text null,
  onset_date date null,
  reporting_facility character varying(150) null,
  status character varying(50) not null default 'Suspected'::character varying,
  severity character varying(50) not null default 'Moderate'::character varying,
  reported_by character varying(150) null,
  investigator_id character varying(100) null,
  investigation_notes text null,
  contact_tracing_done boolean null default false,
  outbreak_id character varying(50) null,
  created_at timestamp with time zone null default CURRENT_TIMESTAMP,
  updated_at timestamp with time zone null default CURRENT_TIMESTAMP,
  constraint surveillance_cases_pkey primary key (id),
  constraint surveillance_cases_case_code_key unique (case_code)
) TABLESPACE pg_default;

create table public.surveillance_contacts (
  id serial not null,
  contact_code character varying(50) null,
  index_case_id integer null,
  name character varying(150) not null,
  age integer not null default 0,
  gender character varying(20) not null default 'Unknown'::character varying,
  relationship character varying(100) null,
  address text null,
  barangay character varying(100) not null,
  exposure_type character varying(100) null,
  exposure_date date null,
  last_contact_date date null,
  symptoms text null,
  monitoring_status character varying(50) not null default 'Under Monitoring'::character varying,
  quarantine_status character varying(50) not null default 'Quarantined'::character varying,
  quarantine_start date null,
  quarantine_end date null,
  risk_level character varying(50) not null default 'Medium'::character varying,
  created_at timestamp with time zone null default CURRENT_TIMESTAMP,
  constraint surveillance_contacts_pkey primary key (id),
  constraint surveillance_contacts_contact_code_key unique (contact_code)
) TABLESPACE pg_default;

create table public.surveillance_index_cases (
  id serial not null,
  index_code character varying(50) null,
  name character varying(150) not null,
  age integer not null default 0,
  gender character varying(20) not null default 'Unknown'::character varying,
  barangay character varying(100) not null,
  disease character varying(100) not null,
  date_confirmed date null,
  status character varying(50) not null default 'Isolated'::character varying,
  risk_level character varying(50) not null default 'High'::character varying,
  created_at timestamp with time zone null default CURRENT_TIMESTAMP,
  constraint surveillance_index_cases_pkey primary key (id),
  constraint surveillance_index_cases_index_code_key unique (index_code)
) TABLESPACE pg_default;

create table public.surveillance_interventions (
  id serial not null,
  intervention_code character varying(50) null,
  title character varying(200) not null,
  type character varying(100) not null,
  location character varying(150) null,
  status character varying(50) not null default 'In Progress'::character varying,
  start_date date null,
  end_date date null,
  team_lead character varying(150) null,
  progress integer not null default 0,
  activities text null,
  resources_used text null,
  outcomes text null,
  created_at timestamp with time zone null default CURRENT_TIMESTAMP,
  constraint surveillance_interventions_pkey primary key (id),
  constraint surveillance_interventions_intervention_code_key unique (intervention_code)
) TABLESPACE pg_default;

create table public.surveillance_resources (
  id serial not null,
  resource_code character varying(50) null,
  name character varying(150) not null,
  category character varying(100) not null,
  quantity integer not null default 0,
  unit character varying(50) not null default 'pcs'::character varying,
  location character varying(150) null,
  status character varying(50) not null default 'Available'::character varying,
  last_restock date null,
  threshold integer not null default 10,
  created_at timestamp with time zone null default CURRENT_TIMESTAMP,
  constraint surveillance_resources_pkey primary key (id),
  constraint surveillance_resources_resource_code_key unique (resource_code)
) TABLESPACE pg_default;

create table public.surveillance_response_teams (
  id serial not null,
  team_code character varying(50) null,
  name character varying(150) not null,
  leader character varying(150) not null,
  members text null,
  specialization character varying(150) null,
  status character varying(50) not null default 'Available'::character varying,
  deployed_to character varying(150) null,
  last_deployment timestamp with time zone null,
  contact character varying(50) null,
  created_at timestamp with time zone null default CURRENT_TIMESTAMP,
  constraint surveillance_response_teams_pkey primary key (id),
  constraint surveillance_response_teams_team_code_key unique (team_code)
) TABLESPACE pg_default;

create table public.system_settings (
  id serial not null,
  category_id integer null,
  key character varying(150) not null,
  value text null,
  data_type character varying(50) not null default 'string'::character varying,
  validation_rules text null,
  description text null,
  is_encrypted boolean null default false,
  is_editable boolean null default true,
  default_value text null,
  created_at timestamp with time zone null default CURRENT_TIMESTAMP,
  updated_at timestamp with time zone null default CURRENT_TIMESTAMP,
  constraint system_settings_pkey primary key (id),
  constraint system_settings_key_key unique (key),
  constraint system_settings_category_id_fkey foreign KEY (category_id) references setting_categories (id) on delete CASCADE
) TABLESPACE pg_default;

create index IF not exists idx_system_settings_key on public.system_settings using btree (key) TABLESPACE pg_default;

create index IF not exists idx_system_settings_category on public.system_settings using btree (category_id) TABLESPACE pg_default;

create table public.triage (
  id serial not null,
  triage_id character varying(20) null,
  patient_id integer not null,
  nurse_id integer not null,
  blood_pressure character varying(20) null,
  heart_rate integer null,
  temperature numeric(4, 1) null,
  respiratory_rate integer null,
  oxygen_saturation integer null,
  weight numeric(5, 2) null,
  height numeric(5, 2) null,
  symptoms text null,
  priority text not null,
  allergies character varying(255) null,
  medications character varying(255) null,
  notes text null,
  consultation_id integer null,
  status text null default 'pending'::text,
  created_at timestamp with time zone null default now(),
  blood_sugar numeric(5, 1) null,
  blood_sugar_type character varying(20) null,
  gcs_eye integer null,
  gcs_verbal integer null,
  gcs_motor integer null,
  gcs_total integer null,
  bmi numeric(4, 1) null,
  constraint triage_pkey primary key (id),
  constraint triage_triage_id_key unique (triage_id),
  constraint triage_gcs_motor_check check (
    (
      (gcs_motor >= 1)
      and (gcs_motor <= 6)
    )
  ),
  constraint triage_gcs_total_check check (
    (
      (gcs_total >= 3)
      and (gcs_total <= 15)
    )
  ),
  constraint triage_blood_sugar_type_check check (
    (
      (blood_sugar_type)::text = any (
        (
          array[
            'fasting'::character varying,
            'random'::character varying,
            'post_prandial'::character varying
          ]
        )::text[]
      )
    )
  ),
  constraint triage_priority_check check (
    (
      priority = any (
        array[
          'critical'::text,
          'high'::text,
          'medium'::text,
          'low'::text
        ]
      )
    )
  ),
  constraint triage_status_check check (
    (
      status = any (
        array[
          'pending'::text,
          'triaged'::text,
          'consulted'::text,
          'cancelled'::text
        ]
      )
    )
  ),
  constraint triage_gcs_verbal_check check (
    (
      (gcs_verbal >= 1)
      and (gcs_verbal <= 5)
    )
  ),
  constraint triage_gcs_eye_check check (
    (
      (gcs_eye >= 1)
      and (gcs_eye <= 4)
    )
  )
) TABLESPACE pg_default;

create trigger calculate_triage_vitals_trigger BEFORE INSERT
or
update on triage for EACH row
execute FUNCTION calculate_triage_vitals ();

create table public.triage_queue (
  id serial not null,
  patient_id integer not null,
  queue_number character varying(20) not null,
  check_in_time timestamp with time zone null default now(),
  status character varying(20) null default 'waiting'::character varying,
  created_at timestamp with time zone null default now(),
  constraint triage_queue_pkey primary key (id),
  constraint triage_queue_queue_number_key unique (queue_number),
  constraint triage_queue_patient_id_fkey foreign KEY (patient_id) references patients (id),
  constraint triage_queue_status_check check (
    (
      (status)::text = any (
        (
          array[
            'waiting'::character varying,
            'in_triage'::character varying,
            'completed'::character varying
          ]
        )::text[]
      )
    )
  )
) TABLESPACE pg_default;