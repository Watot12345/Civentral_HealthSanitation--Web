-- WARNING: This schema is for context only and is not meant to be run.
-- Table order and constraints may not be valid for execution.

CREATE TABLE public.employees (
  id integer NOT NULL DEFAULT nextval('employees_id_seq'::regclass),
  employee_id character varying NOT NULL UNIQUE,
  password character varying NOT NULL,
  full_name character varying NOT NULL,
  department character varying,
  role character varying DEFAULT 'employee'::character varying,
  created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
  role_description text,
  username character varying UNIQUE,
  email character varying,
  status text DEFAULT 'Active'::text CHECK (status = ANY (ARRAY['Active'::text, 'Inactive'::text, 'Suspended'::text])),
  last_login timestamp with time zone,
  role_id integer,
  CONSTRAINT employees_pkey PRIMARY KEY (id),
  CONSTRAINT employees_role_id_fkey FOREIGN KEY (role_id) REFERENCES public.roles(id)
);
CREATE TABLE public.patients (
  id integer NOT NULL DEFAULT nextval('patients_id_seq'::regclass),
  patient_id character varying NOT NULL UNIQUE,
  first_name character varying NOT NULL,
  last_name character varying NOT NULL,
  middle_name character varying,
  birth_date date NOT NULL,
  gender text NOT NULL CHECK (gender = ANY (ARRAY['Male'::text, 'Female'::text, 'Other'::text])),
  blood_type text CHECK (blood_type = ANY (ARRAY['A+'::text, 'A-'::text, 'B+'::text, 'B-'::text, 'AB+'::text, 'AB-'::text, 'O+'::text, 'O-'::text])),
  contact character varying NOT NULL,
  email character varying,
  address text NOT NULL,
  barangay character varying,
  emergency_contact character varying,
  emergency_contact_number character varying,
  allergies text,
  medical_history jsonb,
  registration_date date NOT NULL,
  status text DEFAULT 'active'::text CHECK (status = ANY (ARRAY['active'::text, 'inactive'::text, 'archived'::text])),
  created_at timestamp with time zone DEFAULT now(),
  updated_at timestamp with time zone DEFAULT now(),
  CONSTRAINT patients_pkey PRIMARY KEY (id)
);
CREATE TABLE public.appointments (
  id integer NOT NULL DEFAULT nextval('appointments_id_seq'::regclass),
  appointment_id character varying NOT NULL UNIQUE,
  patient_id integer NOT NULL,
  employee_id integer NOT NULL,
  service_type character varying NOT NULL,
  appointment_date date NOT NULL,
  appointment_time time without time zone NOT NULL,
  status text DEFAULT 'pending'::text CHECK (status = ANY (ARRAY['pending'::text, 'approved'::text, 'completed'::text, 'cancelled'::text, 'no_show'::text])),
  priority text DEFAULT 'medium'::text CHECK (priority = ANY (ARRAY['critical'::text, 'high'::text, 'medium'::text, 'low'::text])),
  notes text,
  reminder_sent boolean DEFAULT false,
  created_at timestamp with time zone DEFAULT now(),
  updated_at timestamp with time zone DEFAULT now(),
  CONSTRAINT appointments_pkey PRIMARY KEY (id),
  CONSTRAINT appointments_patient_id_fkey FOREIGN KEY (patient_id) REFERENCES public.patients(id),
  CONSTRAINT appointments_employee_id_fkey FOREIGN KEY (employee_id) REFERENCES public.employees(id)
);
CREATE TABLE public.consultations (
  id integer NOT NULL DEFAULT nextval('consultations_id_seq'::regclass),
  consultation_id character varying NOT NULL UNIQUE,
  patient_id integer NOT NULL,
  employee_id integer NOT NULL,
  appointment_id integer,
  date date NOT NULL,
  time time without time zone NOT NULL,
  diagnosis text,
  icd_code character varying,
  symptoms text,
  vital_signs jsonb,
  treatment_plan text,
  notes text,
  follow_up_date date,
  status text DEFAULT 'completed'::text CHECK (status = ANY (ARRAY['in_progress'::text, 'completed'::text, 'referred'::text, 'follow_up'::text])),
  created_at timestamp with time zone DEFAULT now(),
  updated_at timestamp with time zone DEFAULT now(),
  CONSTRAINT consultations_pkey PRIMARY KEY (id),
  CONSTRAINT consultations_patient_id_fkey FOREIGN KEY (patient_id) REFERENCES public.patients(id),
  CONSTRAINT consultations_employee_id_fkey FOREIGN KEY (employee_id) REFERENCES public.employees(id),
  CONSTRAINT consultations_appointment_id_fkey FOREIGN KEY (appointment_id) REFERENCES public.appointments(id)
);
CREATE TABLE public.assessment (
  id integer NOT NULL DEFAULT nextval('triage_id_seq'::regclass),
  triage_id character varying UNIQUE,
  patient_id integer NOT NULL,
  nurse_id integer NOT NULL,
  blood_pressure character varying,
  heart_rate integer,
  temperature numeric,
  respiratory_rate integer,
  oxygen_saturation integer,
  weight numeric,
  height numeric,
  symptoms text,
  priority text NOT NULL CHECK (priority = ANY (ARRAY['critical'::text, 'high'::text, 'medium'::text, 'low'::text])),
  allergies character varying,
  medications character varying,
  notes text,
  consultation_id integer,
  status text DEFAULT 'pending'::text CHECK (status = ANY (ARRAY['pending'::text, 'triaged'::text, 'consulted'::text, 'cancelled'::text])),
  created_at timestamp with time zone DEFAULT now(),
  blood_sugar numeric,
  blood_sugar_type character varying CHECK (blood_sugar_type::text = ANY (ARRAY['fasting'::character varying, 'random'::character varying, 'post_prandial'::character varying]::text[])),
  gcs_eye integer CHECK (gcs_eye >= 1 AND gcs_eye <= 4),
  gcs_verbal integer CHECK (gcs_verbal >= 1 AND gcs_verbal <= 5),
  gcs_motor integer CHECK (gcs_motor >= 1 AND gcs_motor <= 6),
  gcs_total integer CHECK (gcs_total >= 3 AND gcs_total <= 15),
  bmi numeric,
  doctor_id integer,
  doctor_assigned text,
  CONSTRAINT assessment_pkey PRIMARY KEY (id)
);
CREATE TABLE public.prescriptions (
  id integer NOT NULL DEFAULT nextval('prescriptions_id_seq'::regclass),
  prescription_id character varying UNIQUE,
  patient_id integer NOT NULL,
  employee_id integer NOT NULL,
  consultation_id integer,
  date date NOT NULL DEFAULT CURRENT_DATE,
  medications jsonb NOT NULL,
  notes text,
  status text DEFAULT 'pending'::text CHECK (status = ANY (ARRAY['pending'::text, 'dispensed'::text, 'cancelled'::text])),
  dispensed_by integer,
  dispensed_at timestamp with time zone,
  created_at timestamp with time zone DEFAULT now(),
  updated_at timestamp with time zone DEFAULT now(),
  CONSTRAINT prescriptions_pkey PRIMARY KEY (id),
  CONSTRAINT prescriptions_patient_id_fkey FOREIGN KEY (patient_id) REFERENCES public.patients(id),
  CONSTRAINT prescriptions_employee_id_fkey FOREIGN KEY (employee_id) REFERENCES public.employees(id),
  CONSTRAINT prescriptions_consultation_id_fkey FOREIGN KEY (consultation_id) REFERENCES public.consultations(id),
  CONSTRAINT prescriptions_dispensed_by_fkey FOREIGN KEY (dispensed_by) REFERENCES public.employees(id)
);
CREATE TABLE public.referrals (
  id bigint NOT NULL DEFAULT nextval('referrals_id_seq'::regclass),
  referral_id character varying NOT NULL UNIQUE,
  patient_id bigint NOT NULL,
  from_doctor_id bigint NOT NULL,
  to_doctor_id bigint,
  to_hospital character varying,
  reason text NOT NULL,
  diagnosis text,
  urgency character varying DEFAULT 'medium'::character varying CHECK (urgency::text = ANY (ARRAY['emergency'::character varying, 'high'::character varying, 'medium'::character varying, 'low'::character varying]::text[])),
  referral_type character varying DEFAULT 'specialist'::character varying CHECK (referral_type::text = ANY (ARRAY['specialist'::character varying, 'hospital'::character varying]::text[])),
  status character varying DEFAULT 'pending'::character varying CHECK (status::text = ANY (ARRAY['pending'::character varying, 'accepted'::character varying, 'completed'::character varying, 'rejected'::character varying]::text[])),
  accepted_at timestamp with time zone,
  completed_at timestamp with time zone,
  follow_up_date timestamp with time zone,
  notes text,
  feedback text,
  created_at timestamp with time zone DEFAULT now(),
  updated_at timestamp with time zone DEFAULT now(),
  CONSTRAINT referrals_pkey PRIMARY KEY (id),
  CONSTRAINT referrals_to_doctor_id_fkey FOREIGN KEY (to_doctor_id) REFERENCES public.employees(id),
  CONSTRAINT referrals_patient_id_fkey FOREIGN KEY (patient_id) REFERENCES public.patients(id),
  CONSTRAINT referrals_from_doctor_id_fkey FOREIGN KEY (from_doctor_id) REFERENCES public.employees(id)
);
CREATE TABLE public.medical_records (
  id integer NOT NULL DEFAULT nextval('medical_records_id_seq'::regclass),
  patient_id integer NOT NULL,
  record_type text NOT NULL CHECK (record_type = ANY (ARRAY['consultation'::text, 'lab'::text, 'imaging'::text, 'procedure'::text, 'other'::text])),
  date date NOT NULL,
  description text NOT NULL,
  attachments jsonb,
  created_by integer NOT NULL,
  created_at timestamp with time zone DEFAULT now(),
  CONSTRAINT medical_records_pkey PRIMARY KEY (id),
  CONSTRAINT medical_records_patient_id_fkey FOREIGN KEY (patient_id) REFERENCES public.patients(id),
  CONSTRAINT medical_records_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.employees(id)
);
CREATE TABLE public.triage_queue (
  id integer NOT NULL DEFAULT nextval('triage_queue_id_seq'::regclass),
  patient_id integer NOT NULL,
  queue_number character varying NOT NULL UNIQUE,
  check_in_time timestamp with time zone DEFAULT now(),
  status character varying DEFAULT 'waiting'::character varying CHECK (status::text = ANY (ARRAY['waiting'::character varying, 'in_triage'::character varying, 'completed'::character varying]::text[])),
  created_at timestamp with time zone DEFAULT now(),
  reason_for_visit character varying,
  CONSTRAINT triage_queue_pkey PRIMARY KEY (id),
  CONSTRAINT triage_queue_patient_id_fkey FOREIGN KEY (patient_id) REFERENCES public.patients(id)
);
CREATE TABLE public.permits (
  id integer NOT NULL DEFAULT nextval('permits_id_seq'::regclass),
  permit_id character varying NOT NULL UNIQUE,
  applicant character varying NOT NULL,
  business_name character varying,
  business_type character varying NOT NULL,
  address text NOT NULL,
  owner_name character varying NOT NULL,
  contact character varying NOT NULL,
  email character varying,
  fee numeric NOT NULL,
  paid boolean DEFAULT false,
  payment_method character varying,
  payment_reference character varying,
  status text DEFAULT 'pending'::text CHECK (status = ANY (ARRAY['pending'::text, 'under_review'::text, 'approved'::text, 'rejected'::text, 'expired'::text])),
  inspector_id integer,
  inspection_date date,
  approved_date date,
  expiry_date date,
  notes text,
  created_at timestamp with time zone DEFAULT now(),
  updated_at timestamp with time zone DEFAULT now(),
  rejection_reason text,
  CONSTRAINT permits_pkey PRIMARY KEY (id),
  CONSTRAINT permits_inspector_id_fkey FOREIGN KEY (inspector_id) REFERENCES public.employees(id)
);
CREATE TABLE public.inspections (
  id integer NOT NULL DEFAULT nextval('inspections_id_seq'::regclass),
  inspection_id character varying NOT NULL UNIQUE,
  permit_id integer NOT NULL,
  inspector_id integer NOT NULL,
  scheduled_date date NOT NULL,
  scheduled_time time without time zone NOT NULL,
  conducted_date timestamp with time zone,
  findings json,
  overall_status text DEFAULT 'partially_compliant'::text CHECK (overall_status = ANY (ARRAY['compliant'::text, 'partially_compliant'::text, 'non_compliant'::text])),
  recommendations text,
  attachments json,
  status text DEFAULT 'scheduled'::text CHECK (status = ANY (ARRAY['scheduled'::text, 'completed'::text, 'cancelled'::text])),
  completed_at timestamp with time zone,
  created_at timestamp with time zone DEFAULT now(),
  updated_at timestamp with time zone DEFAULT now(),
  notes text,
  follow_up_date date,
  CONSTRAINT inspections_pkey PRIMARY KEY (id),
  CONSTRAINT inspections_permit_id_fkey FOREIGN KEY (permit_id) REFERENCES public.permits(id),
  CONSTRAINT inspections_inspector_id_fkey FOREIGN KEY (inspector_id) REFERENCES public.employees(id)
);
CREATE TABLE public.permit_documents (
  id bigint NOT NULL DEFAULT nextval('permit_documents_id_seq'::regclass),
  permit_id integer NOT NULL,
  document_type character varying NOT NULL CHECK (document_type::text = ANY (ARRAY['business_permit'::character varying, 'sanitary_permit'::character varying, 'fire_safety'::character varying, 'zoning_clearance'::character varying, 'environmental_compliance'::character varying, 'building_permit'::character varying, 'tax_clearance'::character varying, 'other'::character varying]::text[])),
  file_name character varying NOT NULL,
  file_path text NOT NULL,
  file_size bigint,
  mime_type character varying,
  uploaded_by integer NOT NULL,
  uploaded_at timestamp with time zone DEFAULT now(),
  verified boolean DEFAULT false,
  verified_by integer,
  verified_at timestamp with time zone,
  updated_at timestamp with time zone DEFAULT now(),
  document_id character varying UNIQUE,
  applicant character varying,
  file_type character varying,
  status text DEFAULT 'pending'::text CHECK (status = ANY (ARRAY['verified'::text, 'pending'::text, 'expired'::text])),
  expiry_date date,
  qr_code character varying,
  notes text,
  CONSTRAINT permit_documents_pkey PRIMARY KEY (id),
  CONSTRAINT permit_documents_permit_id_fkey FOREIGN KEY (permit_id) REFERENCES public.permits(id),
  CONSTRAINT permit_documents_uploaded_by_fkey FOREIGN KEY (uploaded_by) REFERENCES public.employees(id),
  CONSTRAINT permit_documents_verified_by_fkey FOREIGN KEY (verified_by) REFERENCES public.employees(id)
);
CREATE TABLE public.payments (
  id bigint NOT NULL DEFAULT nextval('payments_id_seq'::regclass),
  payment_id character varying NOT NULL UNIQUE,
  permit_id integer NOT NULL,
  amount numeric NOT NULL,
  method character varying NOT NULL CHECK (method::text = ANY (ARRAY['cash'::character varying, 'gcash'::character varying, 'paymaya'::character varying, 'bank_transfer'::character varying, 'over_the_counter'::character varying]::text[])),
  reference_number character varying UNIQUE,
  status character varying DEFAULT 'pending'::character varying CHECK (status::text = ANY (ARRAY['pending'::character varying, 'completed'::character varying, 'failed'::character varying, 'refunded'::character varying]::text[])),
  receipt_path character varying,
  paid_by character varying,
  paid_at timestamp with time zone,
  notes text,
  created_at timestamp with time zone DEFAULT now(),
  updated_at timestamp with time zone DEFAULT now(),
  CONSTRAINT payments_pkey PRIMARY KEY (id),
  CONSTRAINT payments_permit_id_fkey FOREIGN KEY (permit_id) REFERENCES public.permits(id)
);
CREATE TABLE public.renewals (
  id integer NOT NULL DEFAULT nextval('renewals_id_seq'::regclass),
  renewal_id character varying NOT NULL UNIQUE,
  permit_id integer NOT NULL,
  applicant character varying NOT NULL,
  business_type character varying NOT NULL,
  current_fee numeric NOT NULL,
  renewal_fee numeric NOT NULL,
  status text DEFAULT 'pending'::text CHECK (status = ANY (ARRAY['pending'::text, 'under_review'::text, 'approved'::text, 'rejected'::text])),
  payment_method character varying,
  payment_reference character varying,
  date_applied date NOT NULL DEFAULT CURRENT_DATE,
  date_approved date,
  new_expiry_date date,
  notes text,
  documents jsonb DEFAULT '[]'::jsonb,
  created_at timestamp with time zone DEFAULT now(),
  updated_at timestamp with time zone DEFAULT now(),
  grace_period_end date,
  late_fee numeric DEFAULT 0.00,
  interest numeric DEFAULT 0.00,
  rejection_reason text,
  CONSTRAINT renewals_pkey PRIMARY KEY (id),
  CONSTRAINT renewals_permit_id_fkey FOREIGN KEY (permit_id) REFERENCES public.permits(id)
);
CREATE TABLE public.renewal_history (
  id integer NOT NULL DEFAULT nextval('renewal_history_id_seq'::regclass),
  permit_id character varying NOT NULL,
  applicant character varying NOT NULL,
  renewal_date date NOT NULL,
  fee_paid numeric NOT NULL,
  new_expiry date NOT NULL,
  status text DEFAULT 'completed'::text CHECK (status = ANY (ARRAY['completed'::text, 'expired'::text])),
  created_at timestamp with time zone DEFAULT now(),
  CONSTRAINT renewal_history_pkey PRIMARY KEY (id)
);
CREATE TABLE public.children (
  id integer NOT NULL DEFAULT nextval('children_id_seq'::regclass),
  child_id character varying NOT NULL UNIQUE,
  first_name character varying NOT NULL,
  last_name character varying NOT NULL,
  middle_name character varying,
  gender text NOT NULL CHECK (gender = ANY (ARRAY['Male'::text, 'Female'::text])),
  birth_date date NOT NULL,
  birth_weight numeric,
  birth_height numeric,
  blood_type character varying,
  address text NOT NULL,
  barangay character varying NOT NULL,
  mother_name character varying NOT NULL,
  mother_contact character varying,
  mother_occupation character varying,
  father_name character varying,
  father_contact character varying,
  father_occupation character varying,
  family_history text,
  allergies text,
  health_center character varying NOT NULL,
  registration_date date NOT NULL,
  status text DEFAULT 'active'::text CHECK (status = ANY (ARRAY['active'::text, 'inactive'::text])),
  nutrition_status text CHECK (nutrition_status = ANY (ARRAY['Normal'::text, 'Moderate'::text, 'Critical'::text, 'Overweight'::text])),
  vaccine_compliance integer DEFAULT 0 CHECK (vaccine_compliance >= 0 AND vaccine_compliance <= 100),
  last_visit date,
  created_at timestamp with time zone DEFAULT now(),
  updated_at timestamp with time zone DEFAULT now(),
  CONSTRAINT children_pkey PRIMARY KEY (id)
);
CREATE TABLE public.roles (
  id integer NOT NULL DEFAULT nextval('roles_id_seq'::regclass),
  name character varying NOT NULL UNIQUE,
  slug character varying NOT NULL UNIQUE,
  description text,
  color character varying DEFAULT 'bg-slate-100 text-slate-700'::character varying,
  user_count integer DEFAULT 0,
  created_at timestamp with time zone DEFAULT now(),
  updated_at timestamp with time zone DEFAULT now(),
  CONSTRAINT roles_pkey PRIMARY KEY (id)
);
CREATE TABLE public.permissions (
  id integer NOT NULL DEFAULT nextval('permissions_id_seq'::regclass),
  module character varying NOT NULL,
  slug character varying NOT NULL UNIQUE,
  label character varying NOT NULL,
  created_at timestamp with time zone DEFAULT now(),
  CONSTRAINT permissions_pkey PRIMARY KEY (id)
);
CREATE TABLE public.role_permissions (
  id integer NOT NULL DEFAULT nextval('role_permissions_id_seq'::regclass),
  role_id integer NOT NULL,
  permission_id integer NOT NULL,
  created_at timestamp with time zone DEFAULT now(),
  CONSTRAINT role_permissions_pkey PRIMARY KEY (id),
  CONSTRAINT role_permissions_role_id_fkey FOREIGN KEY (role_id) REFERENCES public.roles(id),
  CONSTRAINT role_permissions_permission_id_fkey FOREIGN KEY (permission_id) REFERENCES public.permissions(id)
);
CREATE TABLE public.activity_logs (
  id integer NOT NULL DEFAULT nextval('activity_logs_id_seq'::regclass),
  user_id integer,
  user_name character varying DEFAULT 'System'::character varying,
  action text NOT NULL,
  module character varying,
  details text,
  ip_address character varying,
  status text DEFAULT 'Success'::text CHECK (status = ANY (ARRAY['Success'::text, 'Failed'::text])),
  created_at timestamp with time zone DEFAULT now(),
  role character varying DEFAULT 'System Administrator'::character varying,
  device character varying DEFAULT 'Desktop • Chrome (Linux)'::character varying,
  CONSTRAINT activity_logs_pkey PRIMARY KEY (id)
);
CREATE TABLE public.setting_categories (
  id integer NOT NULL DEFAULT nextval('setting_categories_id_seq'::regclass),
  name character varying NOT NULL UNIQUE,
  description text,
  icon character varying DEFAULT 'fa-gear'::character varying,
  display_order integer DEFAULT 0,
  created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT setting_categories_pkey PRIMARY KEY (id)
);
CREATE TABLE public.system_settings (
  id integer NOT NULL DEFAULT nextval('system_settings_id_seq'::regclass),
  category_id integer,
  key character varying NOT NULL UNIQUE,
  value text,
  data_type character varying NOT NULL DEFAULT 'string'::character varying,
  validation_rules text,
  description text,
  is_encrypted boolean DEFAULT false,
  is_editable boolean DEFAULT true,
  default_value text,
  created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT system_settings_pkey PRIMARY KEY (id),
  CONSTRAINT system_settings_category_id_fkey FOREIGN KEY (category_id) REFERENCES public.setting_categories(id)
);
CREATE TABLE public.feature_flags (
  id integer NOT NULL DEFAULT nextval('feature_flags_id_seq'::regclass),
  key character varying NOT NULL UNIQUE,
  flag_name character varying NOT NULL,
  enabled boolean DEFAULT true,
  description text,
  created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT feature_flags_pkey PRIMARY KEY (id)
);
CREATE TABLE public.settings_versions (
  id integer NOT NULL DEFAULT nextval('settings_versions_id_seq'::regclass),
  version_number integer NOT NULL,
  snapshot_json jsonb NOT NULL,
  changes_summary text,
  created_by character varying,
  created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT settings_versions_pkey PRIMARY KEY (id)
);
CREATE TABLE public.backup_history (
  id integer NOT NULL DEFAULT nextval('backup_history_id_seq'::regclass),
  backup_type character varying NOT NULL,
  file_name character varying NOT NULL,
  file_size character varying,
  status character varying NOT NULL DEFAULT 'completed'::character varying,
  error_message text,
  started_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  completed_at timestamp with time zone,
  created_by character varying DEFAULT 'System'::character varying,
  CONSTRAINT backup_history_pkey PRIMARY KEY (id)
);
CREATE TABLE public.surveillance_cases (
  id integer NOT NULL DEFAULT nextval('surveillance_cases_id_seq'::regclass),
  case_code character varying UNIQUE,
  disease character varying NOT NULL,
  patient_name character varying NOT NULL,
  age integer NOT NULL DEFAULT 0,
  gender character varying NOT NULL DEFAULT 'Unknown'::character varying,
  address text,
  barangay character varying NOT NULL,
  contact_number character varying,
  symptoms text,
  onset_date date,
  reporting_facility character varying,
  status character varying NOT NULL DEFAULT 'Suspected'::character varying,
  severity character varying NOT NULL DEFAULT 'Moderate'::character varying,
  reported_by character varying,
  investigator_id character varying,
  investigation_notes text,
  outbreak_id character varying,
  created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT surveillance_cases_pkey PRIMARY KEY (id)
);
CREATE TABLE public.surveillance_index_cases (
  id integer NOT NULL DEFAULT nextval('surveillance_index_cases_id_seq'::regclass),
  index_code character varying UNIQUE,
  name character varying NOT NULL,
  age integer NOT NULL DEFAULT 0,
  gender character varying NOT NULL DEFAULT 'Unknown'::character varying,
  barangay character varying NOT NULL,
  disease character varying NOT NULL,
  date_confirmed date,
  status character varying NOT NULL DEFAULT 'Isolated'::character varying,
  risk_level character varying NOT NULL DEFAULT 'High'::character varying,
  created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT surveillance_index_cases_pkey PRIMARY KEY (id)
);
CREATE TABLE public.surveillance_alerts (
  id integer NOT NULL DEFAULT nextval('surveillance_alerts_id_seq'::regclass),
  alert_code character varying UNIQUE,
  disease character varying NOT NULL,
  barangay character varying NOT NULL,
  cases integer NOT NULL DEFAULT 0,
  threshold integer NOT NULL DEFAULT 10,
  severity character varying NOT NULL DEFAULT 'Warning'::character varying,
  status character varying NOT NULL DEFAULT 'Active'::character varying,
  timestamp timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  escalation_level integer NOT NULL DEFAULT 1,
  assigned_to character varying,
  response_actions text,
  message text,
  created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT surveillance_alerts_pkey PRIMARY KEY (id)
);
CREATE TABLE public.ai_analytics_logs (
  id integer NOT NULL DEFAULT nextval('ai_analytics_logs_id_seq'::regclass),
  insight_key character varying NOT NULL,
  category character varying NOT NULL,
  badge character varying NOT NULL,
  color character varying NOT NULL,
  title character varying NOT NULL,
  action_text text,
  confidence integer DEFAULT 90,
  metadata jsonb,
  created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT ai_analytics_logs_pkey PRIMARY KEY (id)
);
CREATE TABLE public.immunization_assessments (
  id integer NOT NULL DEFAULT nextval('immunization_assessments_id_seq'::regclass),
  patient_id integer NOT NULL,
  weight numeric,
  temperature numeric,
  health_status character varying DEFAULT 'Healthy'::character varying,
  contraindications text DEFAULT 'None'::text,
  vaccine_due character varying,
  notes text,
  ai_guidance text,
  assessment_result character varying DEFAULT 'Eligible'::character varying,
  assessed_by character varying,
  created_at timestamp with time zone DEFAULT now(),
  CONSTRAINT immunization_assessments_pkey PRIMARY KEY (id)
);
CREATE TABLE public.user_sessions (
  id integer NOT NULL DEFAULT nextval('user_sessions_id_seq'::regclass),
  employee_id integer NOT NULL,
  session_token character varying NOT NULL UNIQUE,
  otp_code character varying,
  otp_expires_at timestamp with time zone,
  remember_me boolean DEFAULT false,
  expires_at timestamp with time zone NOT NULL,
  created_at timestamp with time zone DEFAULT now(),
  CONSTRAINT user_sessions_pkey PRIMARY KEY (id),
  CONSTRAINT user_sessions_employee_id_fkey FOREIGN KEY (employee_id) REFERENCES public.employees(id)
);
CREATE TABLE public.announcements (
  id integer NOT NULL DEFAULT nextval('announcements_id_seq'::regclass),
  title character varying NOT NULL,
  category character varying NOT NULL DEFAULT 'General Announcement'::character varying,
  audience character varying NOT NULL DEFAULT 'All Staff'::character varying,
  body text NOT NULL,
  author character varying NOT NULL DEFAULT 'System Admin'::character varying,
  file_url text,
  is_active boolean DEFAULT true,
  created_at timestamp with time zone DEFAULT now(),
  CONSTRAINT announcements_pkey PRIMARY KEY (id)
);
CREATE TABLE public.service_providers (
  id integer NOT NULL DEFAULT nextval('service_providers_id_seq'::regclass),
  provider_id character varying NOT NULL UNIQUE,
  name character varying NOT NULL,
  contact character varying,
  email character varying,
  address text,
  license_number character varying,
  specialization text NOT NULL CHECK (specialization = ANY (ARRAY['desludging'::text, 'maintenance'::text, 'inspection'::text, 'installation'::text, 'other'::text])),
  rating numeric DEFAULT 0.00,
  status text NOT NULL DEFAULT 'active'::text CHECK (status = ANY (ARRAY['active'::text, 'inactive'::text, 'suspended'::text])),
  equipment_count integer DEFAULT 0,
  completed_jobs integer DEFAULT 0,
  response_time character varying,
  certification character varying,
  joined_date date,
  notes text,
  created_at timestamp with time zone DEFAULT now(),
  updated_at timestamp with time zone DEFAULT now(),
  CONSTRAINT service_providers_pkey PRIMARY KEY (id)
);
CREATE TABLE public.septic_tanks (
  id integer NOT NULL DEFAULT nextval('septic_tanks_id_seq'::regclass),
  tank_id character varying NOT NULL UNIQUE,
  owner_name character varying NOT NULL,
  address text NOT NULL,
  barangay character varying,
  latitude numeric,
  longitude numeric,
  capacity character varying,
  type character varying CHECK (type::text = ANY (ARRAY['Concrete'::character varying, 'Plastic'::character varying, 'Fiberglass'::character varying, 'Other'::character varying]::text[])),
  installation_year integer,
  last_maintenance date,
  maintenance_frequency integer,
  status text NOT NULL DEFAULT 'good'::text CHECK (status = ANY (ARRAY['good'::text, 'needs_maintenance'::text, 'critical'::text, 'decommissioned'::text])),
  notes text,
  created_at timestamp with time zone DEFAULT now(),
  updated_at timestamp with time zone DEFAULT now(),
  CONSTRAINT septic_tanks_pkey PRIMARY KEY (id)
);
CREATE TABLE public.service_requests (
  id integer NOT NULL DEFAULT nextval('service_requests_id_seq'::regclass),
  request_id character varying NOT NULL UNIQUE,
  tank_id character varying,
  owner_name character varying NOT NULL,
  address text,
  barangay character varying,
  service_type text NOT NULL CHECK (service_type = ANY (ARRAY['desludging'::text, 'maintenance'::text, 'inspection'::text, 'installation'::text, 'emergency'::text, 'other'::text])),
  preferred_date date,
  preferred_time character varying,
  assigned_to character varying,
  provider_id character varying,
  status text NOT NULL DEFAULT 'pending'::text CHECK (status = ANY (ARRAY['pending'::text, 'approved'::text, 'in_progress'::text, 'completed'::text, 'cancelled'::text])),
  priority text NOT NULL DEFAULT 'medium'::text CHECK (priority = ANY (ARRAY['critical'::text, 'high'::text, 'medium'::text, 'low'::text])),
  notes text,
  feedback text,
  rating integer CHECK (rating >= 1 AND rating <= 5),
  created_at timestamp with time zone DEFAULT now(),
  completed_at timestamp with time zone,
  updated_at timestamp with time zone DEFAULT now(),
  CONSTRAINT service_requests_pkey PRIMARY KEY (id),
  CONSTRAINT service_requests_tank_id_fkey FOREIGN KEY (tank_id) REFERENCES public.septic_tanks(tank_id),
  CONSTRAINT service_requests_provider_id_fkey FOREIGN KEY (provider_id) REFERENCES public.service_providers(provider_id),
  CONSTRAINT fk_requests_septic_tank FOREIGN KEY (tank_id) REFERENCES public.septic_tanks(tank_id),
  CONSTRAINT fk_requests_service_provider FOREIGN KEY (provider_id) REFERENCES public.service_providers(provider_id)
);
CREATE TABLE public.maintenance_records (
  id integer NOT NULL DEFAULT nextval('maintenance_records_id_seq'::regclass),
  service_id character varying NOT NULL UNIQUE,
  tank_id character varying,
  owner_name character varying NOT NULL,
  address text,
  service_type text NOT NULL CHECK (service_type = ANY (ARRAY['desludging'::text, 'maintenance'::text, 'inspection'::text, 'installation'::text, 'emergency'::text])),
  scheduled_date date,
  scheduled_time character varying,
  technician character varying,
  provider_id character varying,
  status text NOT NULL DEFAULT 'scheduled'::text CHECK (status = ANY (ARRAY['scheduled'::text, 'in_progress'::text, 'completed'::text, 'cancelled'::text])),
  completed_date date,
  completed_time character varying,
  findings text,
  recommendations text,
  notes text,
  cost numeric DEFAULT 0.00,
  rating integer CHECK (rating >= 1 AND rating <= 5),
  created_at timestamp with time zone DEFAULT now(),
  updated_at timestamp with time zone DEFAULT now(),
  CONSTRAINT maintenance_records_pkey PRIMARY KEY (id),
  CONSTRAINT maintenance_records_tank_id_fkey FOREIGN KEY (tank_id) REFERENCES public.septic_tanks(tank_id),
  CONSTRAINT maintenance_records_provider_id_fkey FOREIGN KEY (provider_id) REFERENCES public.service_providers(provider_id),
  CONSTRAINT fk_maintenance_septic_tank FOREIGN KEY (tank_id) REFERENCES public.septic_tanks(tank_id),
  CONSTRAINT fk_maintenance_service_provider FOREIGN KEY (provider_id) REFERENCES public.service_providers(provider_id)
);
CREATE TABLE public.wastewater_invoices (
  id integer NOT NULL DEFAULT nextval('wastewater_invoices_id_seq'::regclass),
  invoice_id character varying NOT NULL UNIQUE,
  client_name character varying NOT NULL,
  tank_id character varying,
  service_request_id character varying,
  provider_id character varying,
  service_type character varying NOT NULL,
  amount numeric NOT NULL DEFAULT 0.00,
  tax numeric DEFAULT 0.00,
  total_amount numeric NOT NULL DEFAULT 0.00,
  status text NOT NULL DEFAULT 'pending'::text CHECK (status = ANY (ARRAY['pending'::text, 'paid'::text, 'overdue'::text, 'cancelled'::text, 'refunded'::text])),
  payment_method character varying CHECK (payment_method::text = ANY (ARRAY['Cash'::character varying, 'GCash'::character varying, 'Maya'::character varying, 'Bank Transfer'::character varying, 'Credit Card'::character varying, 'Check'::character varying, NULL::character varying]::text[])),
  payment_reference character varying,
  invoice_date date NOT NULL DEFAULT CURRENT_DATE,
  due_date date,
  paid_at timestamp with time zone,
  notes text,
  items jsonb,
  created_at timestamp with time zone DEFAULT now(),
  updated_at timestamp with time zone DEFAULT now(),
  CONSTRAINT wastewater_invoices_pkey PRIMARY KEY (id),
  CONSTRAINT wastewater_invoices_tank_id_fkey FOREIGN KEY (tank_id) REFERENCES public.septic_tanks(tank_id),
  CONSTRAINT wastewater_invoices_service_request_id_fkey FOREIGN KEY (service_request_id) REFERENCES public.service_requests(request_id),
  CONSTRAINT wastewater_invoices_provider_id_fkey FOREIGN KEY (provider_id) REFERENCES public.service_providers(provider_id),
  CONSTRAINT fk_invoices_septic_tank FOREIGN KEY (tank_id) REFERENCES public.septic_tanks(tank_id),
  CONSTRAINT fk_invoices_service_provider FOREIGN KEY (provider_id) REFERENCES public.service_providers(provider_id),
  CONSTRAINT fk_invoices_service_request FOREIGN KEY (service_request_id) REFERENCES public.service_requests(request_id)
);



-- REMOVE TABLE NOT NEEDED 
CREATE TABLE public.surveillance_intel_queue (
  id bigint NOT NULL DEFAULT nextval('surveillance_intel_queue_id_seq'::regclass),
  source_name text NOT NULL DEFAULT ''::text,
  title text NOT NULL DEFAULT ''::text,
  summary text DEFAULT ''::text,
  url text DEFAULT ''::text,
  url_hash text NOT NULL UNIQUE,
  published_at timestamp with time zone DEFAULT now(),
  matched_disease text DEFAULT ''::text,
  matched_barangay text DEFAULT ''::text,
  severity text DEFAULT 'moderate'::text,
  case_count integer DEFAULT 0,
  confidence_score integer DEFAULT 0,
  status text DEFAULT 'pending'::text,
  reviewed_by text,
  reviewed_at timestamp with time zone,
  reject_reason text,
  case_id bigint,
  created_at timestamp with time zone DEFAULT now(),
  CONSTRAINT surveillance_intel_queue_pkey PRIMARY KEY (id)
);
CREATE TABLE public.surveillance_intel_log (
  id bigint NOT NULL DEFAULT nextval('surveillance_intel_log_id_seq'::regclass),
  fetched_at timestamp with time zone DEFAULT now(),
  sources_checked integer DEFAULT 0,
  items_found integer DEFAULT 0,
  items_matched integer DEFAULT 0,
  items_queued integer DEFAULT 0,
  error_message text,
  created_at timestamp with time zone DEFAULT now(),
  CONSTRAINT surveillance_intel_log_pkey PRIMARY KEY (id)
);
-- REMOVE TABLE NOT NEEDED.



CREATE TABLE public.barangays (
  id integer NOT NULL DEFAULT nextval('barangays_id_seq'::regclass),
  barangay_no integer NOT NULL UNIQUE,
  name character varying NOT NULL,
  zone character varying NOT NULL,
  landmark character varying DEFAULT ''::character varying,
  district integer DEFAULT 1,
  latitude numeric NOT NULL,
  longitude numeric NOT NULL,
  population integer DEFAULT 10000,
  created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT barangays_pkey PRIMARY KEY (id)
);
CREATE TABLE public.user_notification_reads (
  id bigint NOT NULL DEFAULT nextval('user_notification_reads_id_seq'::regclass),
  user_id integer NOT NULL,
  notification_id character varying NOT NULL,
  is_read boolean DEFAULT true,
  read_at timestamp with time zone DEFAULT now(),
  CONSTRAINT user_notification_reads_pkey PRIMARY KEY (id),
  CONSTRAINT user_notification_reads_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.employees(id) ON DELETE CASCADE,
  CONSTRAINT uq_user_notification_read UNIQUE (user_id, notification_id)
);

CREATE TABLE IF NOT EXISTS public.immunizations (
  id bigint GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
  child_id bigint REFERENCES public.children(id) ON DELETE CASCADE,
  vaccine text NOT NULL,
  dose integer NOT NULL DEFAULT 1,
  date_administered date NOT NULL DEFAULT CURRENT_DATE,
  next_due_date date,
  batch_number text,
  administered_by text,
  health_center text DEFAULT 'Caloocan Main Health Center',
  notes text,
  created_at timestamp with time zone DEFAULT now(),
  updated_at timestamp with time zone DEFAULT now()
);

CREATE TABLE IF NOT EXISTS public.nutrition_assessments (
  id bigint GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
  child_id bigint REFERENCES public.children(id) ON DELETE CASCADE,
  assessment_date date NOT NULL DEFAULT CURRENT_DATE,
  age_months integer,
  weight_kg numeric(5,2),
  height_cm numeric(5,2),
  muac_cm numeric(5,2),
  weight_for_age text,
  height_for_age text,
  weight_for_height text,
  overall_status text,
  feeding_practice text,
  clinical_signs text,
  action_plan text,
  assessed_by text,
  notes text,
  created_at timestamp with time zone DEFAULT now(),
  updated_at timestamp with time zone DEFAULT now()
);

CREATE TABLE IF NOT EXISTS public.growth_measurements (
  id bigint GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
  child_id bigint REFERENCES public.children(id) ON DELETE CASCADE,
  measurement_date date NOT NULL DEFAULT CURRENT_DATE,
  age_months integer,
  weight numeric(5,2),
  height numeric(5,2),
  muac numeric(5,2),
  head_circumference numeric(5,2),
  recorded_by text,
  created_at timestamp with time zone DEFAULT now()
);

CREATE TABLE IF NOT EXISTS public.vaccine_inventory (
  id bigint GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY,
  vaccine_name text NOT NULL,
  batch_number text NOT NULL,
  lot_number text,
  expiry_date date NOT NULL,
  stock_quantity integer NOT NULL DEFAULT 0,
  doses_per_vial integer DEFAULT 1,
  min_temperature numeric(4,1) DEFAULT 2.0,
  max_temperature numeric(4,1) DEFAULT 8.0,
  current_temperature numeric(4,1) DEFAULT 4.0,
  storage_location text,
  status text DEFAULT 'in_stock',
  created_at timestamp with time zone DEFAULT now(),
  updated_at timestamp with time zone DEFAULT now()
);

-- ============================================================
-- 7 SEED PATIENT RECORDS (7 DISTINCT AGES & 7 DISTINCT ZONES)
-- ============================================================
INSERT INTO public.patients (
  patient_id, first_name, last_name, middle_name, birth_date, gender, blood_type,
  contact, email, address, barangay, emergency_contact, emergency_contact_number,
  allergies, medical_history, registration_date, status
) VALUES
-- 1. Age: 2 yrs old (0-5 yrs) | Zone 1 (Barangay 2)
('P-2024-101', 'Mateo', 'Reyes', 'Santos', '2024-05-14', 'Male', 'O+', '+639171110001', 'mateo.reyes@gmail.com', '124 Mabini St.', 'Barangay 2', 'Elena Reyes (Mother)', '+639171110002', 'None', '{"conditions":"None"}', '2024-06-01', 'active'),

-- 2. Age: 9 yrs old (6-17 yrs) | Zone 7 (Barangay 78)
('P-2024-102', 'Althea', 'Cruz', 'Del Rosario', '2017-03-20', 'Female', 'A+', '+639182220001', 'althea.cruz@gmail.com', '56 Rizal Ave.', 'Barangay 78', 'Roberto Cruz (Father)', '+639182220002', 'Penicillin', '{"conditions":"Asthma"}', '2024-06-05', 'active'),

-- 3. Age: 16 yrs old (6-17 yrs) | Zone 8 (Barangay 83)
('P-2024-103', 'Gabriel', 'Bautista', 'Navarro', '2010-09-12', 'Male', 'B+', '+639193330001', 'gabriel.bautista@gmail.com', '88 Samson Road', 'Barangay 83', 'Maria Bautista (Mother)', '+639193330002', 'Dust / Pollen', '{"conditions":"Allergic Rhinitis"}', '2024-06-10', 'active'),

-- 4. Age: 26 yrs old (18-35 yrs) | Zone 12 (Barangay 135)
('P-2024-104', 'Kristine', 'Mercado', 'Lim', '2000-11-05', 'Female', 'AB+', '+639204440001', 'kristine.mercado@gmail.com', '12 Bagong Barrio', 'Barangay 135', 'Marco Mercado (Spouse)', '+639204440002', 'None', '{"conditions":"None"}', '2024-06-15', 'active'),

-- 5. Age: 42 yrs old (36-50 yrs) | Zone 13 (Barangay 145)
('P-2024-105', 'Eduardo', 'Villanueva', 'Garcia', '1984-07-22', 'Male', 'O+', '+639215550001', 'eduardo.v@gmail.com', '45 EDSA Ext.', 'Barangay 145', 'Teresa Villanueva (Wife)', '+639215550002', 'Aspirin', '{"conditions":"Hypertension"}', '2024-06-20', 'active'),

-- 6. Age: 58 yrs old (51-65 yrs) | Zone 14 (Barangay 153)
('P-2024-106', 'Carmelita', 'Torres', 'Perez', '1968-01-30', 'Female', 'B-', '+639226660001', 'carmelita.torres@gmail.com', '78 Morning Breeze', 'Barangay 153', 'Danilo Torres (Husband)', '+639226660002', 'Seafood', '{"conditions":"Type 2 Diabetes"}', '2024-06-25', 'active'),

-- 7. Age: 71 yrs old (66+ yrs) | Zone 15 (Barangay 162)
('P-2024-107', 'Dominador', 'Soriano', 'Aquino', '1955-04-18', 'Male', 'O-', '+639237770001', 'dominador.soriano@gmail.com', '102 Reparo St.', 'Barangay 162', 'Lourdes Soriano (Daughter)', '+639237770002', 'None', '{"conditions":"Heart Disease"}', '2024-07-01', 'active')
ON CONFLICT (patient_id) DO NOTHING;