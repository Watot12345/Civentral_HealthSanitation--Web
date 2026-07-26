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
  constraint employees_pkey primary key (id),
  constraint employees_employee_id_key unique (employee_id)
) TABLESPACE pg_default;


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