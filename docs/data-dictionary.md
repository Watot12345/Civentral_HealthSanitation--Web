# 📚 Civentral Enterprise Database Data Dictionary

- **System**: Civentral Health & Sanitation Management Information System
- **Database Engine**: PostgreSQL (Supabase PostgREST Compliant)
- **Checklist Item**: **6.3 Data Dictionary**
- **Date**: September 5, 2026

---

## 1. Core Module Tables

### `public.employees`
Primary staff, doctor, inspector, and administrative user accounts.

| Column Name | Data Type | Constraints | Description |
|---|---|---|---|
| `id` | `INTEGER` | `PRIMARY KEY`, Auto-increment | Internal surrogate primary key |
| `employee_id` | `VARCHAR` | `NOT NULL`, `UNIQUE` | Human-readable municipal employee code (e.g. `EMP-1001`) |
| `password` | `VARCHAR` | `NOT NULL` | Bcrypt hashed password string |
| `full_name` | `VARCHAR` | `NOT NULL` | Employee full name |
| `department` | `VARCHAR` | Nullable | Primary operational department assignment |
| `role` | `VARCHAR` | Default `'employee'` | RBAC functional role key |
| `role_id` | `INTEGER` | `FOREIGN KEY (roles.id)` | Link to granular permission matrix |
| `email` | `BYTEA / VARCHAR` | Encrypted | Staff email address (encrypted via `pgcrypto` AES-256) |
| `status` | `TEXT` | Check: `Active`, `Inactive`, `Suspended` | Account access status |
| `last_login` | `TIMESTAMPTZ` | Nullable | Timestamp of last successful login |

---

### `public.patients`
Clinical patient registry containing demographic and medical information.

| Column Name | Data Type | Constraints | Description |
|---|---|---|---|
| `id` | `INTEGER` | `PRIMARY KEY`, Auto-increment | Internal surrogate primary key |
| `patient_id` | `VARCHAR` | `NOT NULL`, `UNIQUE` | Unique patient code (e.g. `P-2026-00104`) |
| `first_name` | `VARCHAR` | `NOT NULL` | Patient given name |
| `last_name` | `VARCHAR` | `NOT NULL` | Patient surname |
| `middle_name` | `VARCHAR` | Nullable | Patient middle name |
| `birth_date` | `DATE` | `NOT NULL` | Date of birth |
| `gender` | `TEXT` | Check: `Male`, `Female`, `Other` | Gender identity |
| `contact` | `BYTEA` | Encrypted `NOT NULL` | Contact number (AES-256 encrypted) |
| `address` | `TEXT` | `NOT NULL` | Residential street address |
| `barangay` | `VARCHAR` | Indexed | Caloocan Barangay designation |
| `medical_history` | `JSONB` | Nullable | Structured JSON array of past clinical diagnoses & allergies |
| `status` | `TEXT` | Default `'active'` | Patient record status (`active`, `inactive`, `archived`, `deleted`) |

---

### `public.permits`
Sanitary permit applications and business compliance records.

| Column Name | Data Type | Constraints | Description |
|---|---|---|---|
| `id` | `INTEGER` | `PRIMARY KEY` | Internal primary key |
| `permit_id` | `VARCHAR` | `NOT NULL`, `UNIQUE` | Official permit tracking code (e.g. `SAN-2026-042`) |
| `business_name` | `VARCHAR` | `NOT NULL` | Registered commercial establishment name |
| `applicant` | `VARCHAR` | `NOT NULL` | Primary business owner or authorized representative |
| `barangay` | `VARCHAR` | Indexed | Establishment location barangay |
| `contact` | `BYTEA` | Encrypted | Applicant contact phone number |
| `email` | `BYTEA` | Encrypted | Applicant email address |
| `fee` | `NUMERIC(10,2)` | Default `0.00` | Sanitation inspection & permit processing fee |
| `status` | `TEXT` | Check: `Pending`, `Approved`, `Expired`, `Rejected` | Sanitary permit lifecycle status |
| `created_at` | `TIMESTAMPTZ` | Default `now()` | Application submission timestamp |

---

### `public.surveillance_cases`
Epidemiological disease surveillance case tracking repository.

| Column Name | Data Type | Constraints | Description |
|---|---|---|---|
| `id` | `INTEGER` | `PRIMARY KEY` | Primary key |
| `case_code` | `VARCHAR` | `NOT NULL`, `UNIQUE` | Epidemiological outbreak case tracking code |
| `disease` | `VARCHAR` | `NOT NULL` | Disease name (e.g. `Dengue`, `Cholera`, `Measles`, `Leptospirosis`) |
| `barangay` | `VARCHAR` | Indexed | Barangay location of reported case |
| `severity` | `TEXT` | Check: `Mild`, `Moderate`, `Severe`, `Critical` | Case clinical severity |
| `status` | `TEXT` | Check: `Suspected`, `Confirmed`, `Recovered`, `Deceased` | Disease case progression status |
| `contact_number` | `BYTEA` | Encrypted | Case contact phone number |
| `reported_at` | `TIMESTAMPTZ` | Default `now()` | Date and time case was reported to health center |

---

### `public.patient_consents`
Data Privacy Act (RA 10173) patient consent and revocation ledger.

| Column Name | Data Type | Constraints | Description |
|---|---|---|---|
| `id` | `BIGSERIAL` | `PRIMARY KEY` | Primary key |
| `user_id` | `VARCHAR` | `NOT NULL`, Indexed | Subject patient/citizen identifier |
| `consent_type` | `VARCHAR` | `NOT NULL` | Type of data processing consent (`registration_dpa`, `telehealth`) |
| `granted_at` | `TIMESTAMPTZ` | Default `now()` | Timestamp consent was granted |
| `revoked_at` | `TIMESTAMPTZ` | Nullable | Timestamp consent was revoked |
| `ip` | `VARCHAR(45)` | `NOT NULL` | IP address capturing consent |
| `method` | `VARCHAR(50)` | Default `'web_form'` | Channel through which consent was obtained |
| `status` | `VARCHAR(20)` | Default `'active'` | Consent state (`active` vs `revoked`) |

---

### `public.data_deletion_requests` & `public.deletion_audit_logs`
Right to Be Forgotten citizen data deletion queue and execution audit trail.

| Table Name | Column Name | Data Type | Description |
|---|---|---|---|
| `data_deletion_requests` | `id` | `BIGSERIAL` | Request primary key |
| `data_deletion_requests` | `user_id` | `VARCHAR` | Subject citizen/patient ID |
| `data_deletion_requests` | `status` | `VARCHAR` | Queue status (`pending`, `approved`, `rejected`, `executed`) |
| `deletion_audit_logs` | `request_id` | `BIGINT` | Reference to approved deletion request |
| `deletion_audit_logs` | `deleted_records` | `JSONB` | Anonymized and purged table/record inventory |
| `deletion_audit_logs` | `executed_by` | `VARCHAR` | Admin ID or `system_scheduler` |
