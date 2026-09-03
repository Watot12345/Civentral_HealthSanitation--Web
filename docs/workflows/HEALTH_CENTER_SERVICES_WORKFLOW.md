# Health Center Services Clinical Workflow Specification

## Capstone System Architecture
**Title**: *Design and Development of a Web-Based Health and Sanitation Management Information System with Gemini-Powered AI Analytics, Decision Support and Automated Report Generation for Government*

This module enforces a **patient-centric clinical workflow** for local health centers (LGU Primary Care Facilities). All queue management, queue numbers, and public call display mechanisms have been replaced with clinical patient care tracking using the primary database table **`public.assessment`**.

---

## Official Health Center Clinical Workflow

```
Patient Management (patients.php)
        │
        ├── Register New Patient
        ├── Search Existing Patient
        └── Check-in
                     │
                     ▼
Today's Visits (triage.php - Today's Arrivals)
                     │
                     ▼
Patient Assessment (triage.php - Saved in public.assessment)
                     │
                     ▼
Assign Doctor / Service (triage.php - Dynamic Doctor Selection)
                     │
                     ▼
Doctor Consultation (consultations.php - Reads public.assessment data)
                     │
          ┌──────────┴──────────┐
          ▼                     ▼
Prescription            Referral
(prescriptions.php)     (referrals.php)
          └──────────┬──────────┘
                     ▼
Medical Records Archive (medical_records.php)
```

---

## Detailed Step-by-Step Verification

### 1. Patient Management (`patients.php`)
- **Register New Patient**: Create new patient demographics in `public.patients`.
- **Search Existing Patient**: Real-time filtering by name, patient ID (`P-2024-001`), or barangay.
- **Check-in**: 1-click auto check-in button logging today's arrival and transferring to assessment.

### 2. Today's Visits (`triage.php`)
- **Arrivals Table**: Displays today's patient arrivals with Visit Type (`Walk-in`, `Scheduled Appointment`), Check-in Time, and status (`Checked In`, `In Assessment`).

### 3. Patient Assessment (`triage.php` → `public.assessment`)
- **Nurse / Midwife Assessment**: Records complete vital signs (BP, HR, Temp, RR, SpO₂, Weight, Height, BMI, Blood Sugar, GCS), symptoms, allergies, medications, and nurse notes.
- **Clinical Decision Support System (DSS)**: Recommends priority level (`Critical 🔴`, `High 🟠`, `Medium 🟡`, `Low 🟢`).
- **Database Persistence**: Saved directly into `public.assessment` table in Supabase.

### 4. Assign Doctor / Service (`triage.php`)
- **Attending Doctor Assignment**: Dynamically lists active doctors from `public.employees` (`full_name`, `role_description`).
- **Health Service Category**: Assigns to `General Medicine`, `Dental Care`, `Maternal & Child Health`, or `Immunization & Nutrition`.

### 5. Doctor Consultation (`consultations.php`)
- **Doctor Consultation Workspace**: Doctor views patients with pre-loaded vitals from `public.assessment`.
- **Clinical Documentation**: Inputs Diagnosis, ICD-10 Code, and Treatment Plan.

### 6. Clinical Outcomes: Prescription & Referral (Optional)
- **Prescription** (`prescriptions.php`): Optional medication prescription form linked to consultation.
- **Referral** (`referrals.php`): Optional inter-facility referral form for specialized hospital care.

### 7. Medical Records Archive (`medical_records.php`)
- **Longitudinal Record**: Read-only patient medical archive automatically aggregating all past assessments, consultations, prescriptions, and referrals.