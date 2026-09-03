# Role-Based Workflows for Health Center Services

This document defines the specific operational workflows for each role authorized to access **Health Center Services** in the system, based on the official Role-Based Access Control (RBAC) permissions matrix (`roles_permissions_matrix.md`).

---

## Summary of Health Center Roles & Access

| Role Title | Department | Permitted Health Center Permissions | Key Operational Workflow Focus |
| :--- | :--- | :--- | :--- |
| **System Administrator** | Administration | All 29/29 (Full Access) | System configuration, user management, audit logs, and override capabilities across all health modules. |
| **Health Center Director** | Health Center Services | Patients (CRUD), Consultations (VC), Triage (VC), Prescriptions (VC), Users, Logs | Overall departmental leadership, clinical oversight, patient record management, staff auditing, and reporting. |
| **Doctor** | Health Center Services | Patients (CRE), Consultations (VC), Triage (V), Prescriptions (CRE) | Clinical examinations, diagnosing patients, recording consultations, reviewing triage vitals, and issuing prescriptions. |
| **Nurse** | Health Center Services | Patients (CRE), Triage (VC), Consultations (V), Prescriptions (V) | Patient intake, recording vital signs and triage notes, reviewing consultations, and assisting clinical staff. |
| **Dentist** | Health Center Services | Patients (CRE), Consultations (VC), Prescriptions (CRE) | Specialized dental examinations, recording dental consultations, and prescribing treatments/medications. |
| **Laboratory Technician** | Health Center Services | Patients (V), Consultations (V), Prescriptions (V) | Reviewing patient diagnostic requirements from consultations/prescriptions and processing lab requests. |
| **Medical Records Clerk** | Health Center Services | Patients (CRE) | Registering new patients, updating demographic information, and maintaining patient archives. |
| **Appointment Clerk** | Health Center Services | Patients (CR), Triage (V) | Scheduling appointments, patient check-ins, and monitoring waiting queue status. |

*(Note: Roles belonging to other departments such as Sanitation Permits, Immunization & Nutrition, Wastewater Services, and Health Surveillance interact with health services only as defined by their specific cross-module permissions).*

---

## Detailed Role Workflows

### 1. System Administrator
- **Scope**: Full System Access (29/29 permissions).
- **Health Center Workflow**:
  1. Access dashboard and system analytics.
  2. Manage user accounts, assign roles, and configure system settings.
  3. Perform full CRUD operations on patients, consultations, triage, and prescriptions.
  4. Review activity logs and audit trails.

### 2. Health Center Director
- **Scope**: Departmental Leadership & Administration (16/29 permissions).
- **Health Center Workflow**:
  1. **Dashboard & Compliance**: Monitor department KPIs, analytics, and compliance reports.
  2. **Patient Management**: View, create, edit, or archive patient profiles (`patients.view`, `patients.create`, `patients.edit`, `patients.delete`).
  3. **Clinical Oversight**: Monitor consultations and triage logs (`consultations.view`, `triage.view`, `prescriptions.view`).
  4. **Staff & Audit**: Review staff user accounts and system activity logs (`users.view`, `logs.view`).

### 3. Doctor
- **Scope**: Clinical Practitioner (10/29 permissions).
- **Health Center Workflow**:
  1. **Patient Intake Review**: View patient profiles and check triage vitals (`patients.view`, `triage.view`).
  2. **Consultation**: Conduct examinations and record clinical consultation notes and diagnoses (`consultations.create`, `consultations.view`).
  3. **Prescription**: Issue and manage patient prescriptions (`prescriptions.create`, `prescriptions.view`).
  4. **Reporting**: Review clinical reports and dashboard metrics (`dashboard.view`, `reports.view`).

### 4. Nurse
- **Scope**: Nursing & Triage Staff (9/29 permissions).
- **Health Center Workflow**:
  1. **Patient Registration/Lookup**: Search or register patient profiles during intake (`patients.view`, `patients.create`, `patients.edit`).
  2. **Triage Assessment**: Record vital signs, chief complaints, and triage priorities (`triage.create`, `triage.view`).
  3. **Clinical Assistance**: Review doctor consultations and active prescriptions to assist in patient care (`consultations.view`, `prescriptions.view`).
  4. **Dashboard**: Monitor daily patient flow via dashboard (`dashboard.view`).

### 5. Dentist
- **Scope**: Dental Practitioner (9/29 permissions).
- **Health Center Workflow**:
  1. **Patient Management**: Register or view patient dental records (`patients.view`, `patients.create`, `patients.edit`).
  2. **Dental Consultations**: Record specialized dental examination notes and treatment plans (`consultations.create`, `consultations.view`).
  3. **Prescriptions**: Issue prescriptions for dental care (`prescriptions.create`, `prescriptions.view`).
  4. **Reporting**: Access dashboard and clinical reports (`dashboard.view`, `reports.view`).

### 6. Laboratory Technician
- **Scope**: Diagnostics & Lab (4/29 permissions).
- **Health Center Workflow**:
  1. **Patient Reference**: View patient basic information (`patients.view`).
  2. **Diagnostic Review**: Check consultation notes and prescriptions requesting lab work (`consultations.view`, `prescriptions.view`).
  3. **Dashboard**: Access overview dashboard (`dashboard.view`).

### 7. Medical Records Clerk
- **Scope**: Records Administration (4/29 permissions).
- **Health Center Workflow**:
  1. **Patient Registration**: Register new patients arriving at the facility (`patients.create`).
  2. **Record Maintenance**: Update demographic details, contact information, and patient metadata (`patients.edit`, `patients.view`).
  3. **Dashboard**: Access overview dashboard (`dashboard.view`).

### 8. Appointment Clerk
- **Scope**: Front Desk & Scheduling (4/29 permissions).
- **Health Center Workflow**:
  1. **Scheduling & Check-in**: Register new patient entries and manage appointment queues (`patients.create`, `patients.view`).
  2. **Triage Monitoring**: View incoming triage statuses to coordinate waiting room flow (`triage.view`).
  3. **Dashboard**: Access overview dashboard (`dashboard.view`).

# Health Center Services Module Workflow & Features Documentation

This document provides a comprehensive overview of the **Health Center Services** modules within the system, detailing each module's core features, user interactions, database models, and backend integration.

---

## 1. Module Overview

The **Health Center Services** module is a core clinical and administrative subsystem designed to manage patient visits, appointment scheduling, triage and queue tracking, medical consultations, prescriptions, referrals, and patient records.

Access to this department is restricted via role-based access control requiring `health center services` department access or appropriate clinical roles (Doctors, Nurses, Triage Staff, Administrators).

---

## 2. Core Sub-Modules & Detailed Features

### 2.1 Patient Management (`modules/healthservices/patients.php` & `app/Controllers/PatientController.php`)
- **Patient Registration:** Register new patients with demographic details, contact information, emergency contacts, and personal medical history.
- **Patient Directory & Search:** View, search, and filter patients by name, code (`patient_id`), or contact details.
- **Data Masking Integration:** Sensitive personal and medical identifiers are masked in accordance with data privacy compliance (`includes/data-mask.php`).
- **Patient Profile Viewer:** Inspect comprehensive patient histories, past visits, vital signs, and associated records.

### 2.2 Appointment Scheduling (`modules/healthservices/appointments.php` & `app/Controllers/AppointmentController.php`)
- **Appointment Booking:** Schedule appointments for patients linking them with available medical practitioners/employees (`Employee` model).
- **Status Tracking:** Manage appointment lifecycle states (Scheduled, Confirmed, Completed, Cancelled, Rescheduled).
- **Date & Time Management:** Calendar and list views sorted by appointment date, time, and creation timestamp.
- **Patient & Doctor Mapping:** Associates appointments dynamically with patient records and medical staff directories.

### 2.3 Check-in & Patient Assessment (`modules/healthservices/triage.php` & `app/Controllers/TriageController.php`)
- **Daily Check-in Filtering:** Filters patients who have checked in today (`TriageQueue` model) and sorts them into an assessment-ready pool.
- **Vital Signs Recording:** Capture essential patient vitals (Blood Pressure, Heart Rate, Temperature, Weight, Height, Oxygen Saturation, Respiratory Rate, BMI, Blood Sugar, GCS).
- **Clinical Assessment Notes:** Record chief complaints, DSS priority levels (Critical, High, Medium, Low), doctor assignment, and preliminary nursing observations.
- **Duplicate Assessment Prevention:** Automatically tracks and restricts patients who have already been assessed on the current calendar date.

### 2.5 Medical Consultations (`modules/healthservices/consultations.php` & `app/Controllers/ConsultationController.php`)
- **Clinical Consultation Workspace:** Detailed interface for doctors and medical practitioners to record consultation notes, diagnoses, and treatment plans.
- **Medical Staff Filtering:** Filters and assigns consultations to qualified medical staff (Doctors, Nurses, Dentists, Midwives, Nutritionists, etc.).
- **History & Follow-ups:** Maintains chronological logs of patient consultations mapped by date and practitioner.

### 2.6 Prescriptions (`modules/healthservices/prescriptions.php` & `app/Controllers/PrescriptionController.php`)
- **Medication Management:** Issue prescriptions linked to patient consultations and medical records.
- **Drug Directory Integration:** Connects with drug inventory (`api/drugs.php`) for medication selection, dosage instructions, frequency, and duration.
- **Prescription Status & History:** Tracks active and completed prescriptions per patient.

### 2.7 Referrals (`modules/healthservices/referrals.php` & `app/Controllers/ReferralController.php`)
- **External & Internal Referrals:** Create referral documents when patients require specialized external care or cross-departmental transfer.
- **Referral Tracking:** Records referring physician, destination facility/department, clinical justification, and referral status.

### 2.8 Medical Records (`modules/healthservices/medical_records.php` & `app/Controllers/MedicalRecordController.php`)
- **Centralized Health Records:** Aggregates patient medical history, past diagnoses, lab results, triage notes, and visit summaries.
- **Secure Access Control:** Restricts medical record viewing and modification strictly to authorized clinical personnel.

---

## 3. Architecture & Data Flow

```
[Patient Arrival / Check-in] 
        │
        ▼
[Triage Module] ──► Records Vitals & Priority ──► [Queue Management / Public Display]
        │
        ▼
[Consultation Module] ──► Doctor Examines Patient, Adds Notes
        │
        ├───────────────────────┬───────────────────────┐
        ▼                       ▼                       ▼
[Prescriptions]        [Referrals]            [Medical Records Archive]
```

### Key Models Used:
- `Patient`: Patient demographic and profile management.
- `Employee`: Medical staff and doctor directory.
- `Appointment`: Scheduling and calendar management.
- `Triage`: Vital signs and clinical assessment records.
- `TriageQueue`: Check-in queue tracking.
- `Consultation`: Doctor consultation notes and diagnoses.
- `Prescription`: Medication management.
- `Referral`: Patient transfer and referral documents.
- `MedicalRecord`: Consolidated patient history.


Yes, the generated workflows in @HEALTH_CENTER_SERVICES_WORKFLOW.md and
  @ROLE_BASED_HEALTH_WORKFLOWS.md closely align with real-world health center
  operations (such as municipal health centers, rural health units, or outpatient
  clinics), while directly mapping to your system's architecture and RBAC
  implementation.

  Here is an evaluation of how well they align with standard healthcare processes:

  ---

  1. Alignment with Real-World Clinical Flow
  In a standard health center, patient flow follows a strict sequential pathway:
   1. Arrival & Front Desk / Appointment Desk: 
      - Real-world: Patients check in or book visits at the front desk.
      - System Alignment: Handled by Appointment Clerks and Medical Records Clerks
        who manage patient registration (PatientController) and appointment
        scheduling (AppointmentController).
   2. Nursing & Triage Station:
      - Real-world: Before seeing a doctor, patients undergo preliminary screening
        where nurses take vital signs (BP, temperature, heart rate, weight) and log
        chief complaints.
      - System Alignment: Handled by Nurses using the Triage and TriageQueue modules
        to record vitals, assess priority, and queue patients for the waiting room
        monitor (queue_management.php).
   3. Consultation & Examination:
      - Real-world: Doctors or dentists examine the patient, review triage vitals,
        establish a diagnosis, and record medical notes.
      - System Alignment: Handled by Doctors and Dentists using
        ConsultationController to input structured medical findings.
   4. Disposal / Post-Consultation (Pharmacy, Laboratory, Referrals):
      - Real-world: Patients receive prescriptions for pharmacy fulfillment, lab
        requests for diagnostics, or referral slips if specialist care is required.
      - System Alignment: Handled through PrescriptionController, external drug
        inventory integration (api/drugs.php), and the Referral module.
   5. Medical Records Archiving:
      - Real-world: All visit data, diagnoses, and notes are archived into the
        patient's permanent medical chart.
      - System Alignment: Aggregated via the MedicalRecord model with data masking
  2. Alignment with Real-World Role Segregation (RBAC)
  Real-world clinics maintain strict separation of duties (SoD) for compliance and
  patient safety:
   - Clerical vs. Clinical Separation: Appointment and Records Clerks handle
     administrative entry (patients, appointments) but cannot write prescriptions or
     clinical notes. This is accurately reflected in their restricted permissions
     (patients.create, patients.view).
   - Nursing Scope: Nurses capture triage data and assist clinicians, but diagnosing
     and prescribing remain strictly restricted to licensed practitioners (Doctors
     and Dentists).
   - Administrative Oversight: The Health Center Director and System Administrator
     maintain broad oversight across reports, compliance, and user logs without
     needing manual operational bottlenecks.

  ---

  Conclusion
  The documented workflows are fully aligned with both your codebase architecture and
  actual health center operational standards. They ensure data integrity, privacy
  compliance, and a logical progression of patient care from arrival to discharge.




















const {
  Document, Packer, Paragraph, TextRun, HeadingLevel, AlignmentType,
  Table, TableRow, TableCell, WidthType, ShadingType, BorderStyle,
  TableOfContents, PageBreak, LevelFormat, convertInchesToTwip
} = require("docx");

const ACCENT = "0F6E56"; // teal 600
const ACCENT_DARK = "085041";
const GRAY_LIGHT = "F1EFE8";
const GRAY_BORDER = "B4B2A9";

function h1(text) {
  return new Paragraph({ text, heading: HeadingLevel.HEADING_1, spacing: { before: 360, after: 160 } });
}
function h2(text) {
  return new Paragraph({ text, heading: HeadingLevel.HEADING_2, spacing: { before: 280, after: 120 } });
}
function body(text, opts = {}) {
  return new Paragraph({
    children: [new TextRun({ text, ...opts })],
    spacing: { after: 160 },
  });
}
function bullet(text, level = 0) {
  const parts = text.split(/\*\*(.*?)\*\*/g);
  const runs = parts.map((p, i) => new TextRun({ text: p, bold: i % 2 === 1 }));
  return new Paragraph({
    children: runs,
    numbering: { reference: "bullets", level },
    spacing: { after: 80 },
  });
}
function moduleCell(text, width, opts = {}) {
  return new TableCell({
    width: { size: width, type: WidthType.DXA },
    shading: opts.header ? { type: ShadingType.CLEAR, fill: ACCENT } : undefined,
    margins: { top: 100, bottom: 100, left: 120, right: 120 },
    children: [new Paragraph({
      children: [new TextRun({ text, bold: !!opts.header, color: opts.header ? "FFFFFF" : undefined, size: 20 })],
    })],
  });
}

const submodules = [
  ["2.1", "Patient Management", "patients.php / PatientController.php",
    "Patient registration, directory search, data masking for sensitive fields, and full profile/history viewing."],
  ["2.2", "Appointment Scheduling", "appointments.php / AppointmentController.php",
    "Booking against available staff, lifecycle status tracking (Scheduled, Confirmed, Completed, Cancelled, Rescheduled), and calendar/list views."],
  ["2.3", "Triage & Check-In", "triage.php / TriageController.php",
    "Daily check-in filtering, vital signs capture, chief complaint and priority-level notes, and duplicate-triage prevention per day."],
  ["2.4", "Queue Management & Public Monitor", "queue_management.php",
    "Standalone public waiting-room display with departmental counter routing and real-time queue status."],
  ["2.5", "Medical Consultations", "consultations.php / ConsultationController.php",
    "Clinical workspace for notes, diagnoses, and treatment plans, filtered by qualified staff type and visit history."],
  ["2.6", "Prescriptions", "prescriptions.php / PrescriptionController.php",
    "Medication issuance linked to consultations, drug directory integration, and prescription status/history."],
  ["2.7", "Referrals", "referrals.php / ReferralController.php",
    "External and internal referral documents recording the referring physician, destination, justification, and status."],
  ["2.8", "Medical Records", "medical_records.php / MedicalRecordController.php",
    "Centralized aggregation of diagnoses, lab results, triage notes, and visit summaries, restricted to authorized clinical personnel."],
];

const models = [
  ["Patient", "Patient demographic and profile management."],
  ["Employee", "Medical staff and doctor directory."],
  ["Appointment", "Scheduling and calendar management."],
  ["Triage", "Vital signs and clinical assessment records."],
  ["TriageQueue", "Check-in queue tracking."],
  ["Consultation", "Doctor consultation notes and diagnoses."],
  ["Prescription", "Medication management."],
  ["Referral", "Patient transfer and referral documents."],
  ["MedicalRecord", "Consolidated patient history."],
];

const doc = new Document({
  numbering: {
    config: [{
      reference: "bullets",
      levels: [
        { level: 0, format: LevelFormat.BULLET, text: "\u2022", alignment: AlignmentType.LEFT, style: { paragraph: { indent: { left: 360, hanging: 260 } } } },
        { level: 1, format: LevelFormat.BULLET, text: "\u25E6", alignment: AlignmentType.LEFT, style: { paragraph: { indent: { left: 720, hanging: 260 } } } },
      ],
    }],
  },
  styles: {
    default: {
      document: { run: { font: "Calibri", size: 22 } },
    },
    paragraphStyles: [
      { id: "Heading1", name: "Heading 1", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { size: 30, bold: true, color: ACCENT_DARK, font: "Calibri" },
        paragraph: { spacing: { before: 360, after: 160 }, border: { bottom: { color: ACCENT, space: 4, style: BorderStyle.SINGLE, size: 6 } } } },
      { id: "Heading2", name: "Heading 2", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { size: 24, bold: true, color: ACCENT_DARK, font: "Calibri" },
        paragraph: { spacing: { before: 280, after: 120 } } },
    ],
  },
  sections: [
    {
      properties: {
        page: {
          size: { width: 12240, height: 15840 },
          margin: { top: 1440, bottom: 1440, left: 1440, right: 1440 },
        },
      },
      children: [
        // Title page
        new Paragraph({ spacing: { before: 2400 }, children: [] }),
        new Paragraph({
          alignment: AlignmentType.CENTER,
          children: [new TextRun({ text: "Health Center Services Module", bold: true, size: 44, color: ACCENT_DARK })],
        }),
        new Paragraph({
          alignment: AlignmentType.CENTER,
          spacing: { before: 200, after: 800 },
          children: [new TextRun({ text: "Workflow & Features Documentation", size: 28, color: "5F5E5A" })],
        }),
        new Paragraph({
          alignment: AlignmentType.CENTER,
          children: [new TextRun({ text: "Civentral — Health and Sanitation Management Information System", italics: true, size: 22 })],
        }),
        new Paragraph({
          alignment: AlignmentType.CENTER,
          spacing: { before: 100 },
          children: [new TextRun({ text: "Prepared for: Caloocan City Government", size: 22 })],
        }),
        new Paragraph({ children: [new PageBreak()] }),

        // TOC
        h1("Table of Contents"),
        new TableOfContents("Table of Contents", { hyperlink: true, headingStyleRange: "1-2" }),
        new Paragraph({ children: [new PageBreak()] }),

        // 1. Module Overview
        h1("1. Module Overview"),
        body("The Health Center Services module is a core clinical and administrative subsystem designed to manage patient visits, appointment scheduling, triage and queue tracking, medical consultations, prescriptions, referrals, and patient records."),
        body("Access to this department is restricted via role-based access control requiring health center services department access or appropriate clinical roles (Doctors, Nurses, Triage Staff, Administrators)."),

        // 2. Core Sub-Modules
        h1("2. Core Sub-Modules & Detailed Features"),
        ...submodules.flatMap(([num, title, files, desc]) => [
          h2(`${num} ${title}`),
          new Paragraph({
            spacing: { after: 120 },
            children: [new TextRun({ text: files, italics: true, size: 19, color: "5F5E5A", font: "Consolas" })],
          }),
          body(desc),
        ]),

        // 3. Architecture & Data Flow
        h1("3. Architecture & Data Flow"),
        body("The module follows a linear intake process that branches into three parallel clinical outputs once a consultation is complete:"),
        new Table({
          width: { size: 9360, type: WidthType.DXA },
          borders: {
            top: { style: BorderStyle.SINGLE, size: 4, color: GRAY_BORDER },
            bottom: { style: BorderStyle.SINGLE, size: 4, color: GRAY_BORDER },
            left: { style: BorderStyle.SINGLE, size: 4, color: GRAY_BORDER },
            right: { style: BorderStyle.SINGLE, size: 4, color: GRAY_BORDER },
            insideHorizontal: { style: BorderStyle.SINGLE, size: 4, color: GRAY_BORDER },
            insideVertical: { style: BorderStyle.SINGLE, size: 4, color: GRAY_BORDER },
          },
          rows: [
            new TableRow({ children: [moduleCell("Stage", 3120, { header: true }), moduleCell("Description", 6240, { header: true })] }),
            new TableRow({ children: [moduleCell("1. Patient arrival / check-in", 3120), moduleCell("Patient checks in, either against a booked appointment or as a walk-in.", 6240)] }),
            new TableRow({ children: [moduleCell("2. Triage", 3120), moduleCell("Vitals and priority are recorded; the patient enters the queue.", 6240)] }),
            new TableRow({ children: [moduleCell("3. Queue management", 3120), moduleCell("Public display routes the patient to the correct service counter.", 6240)] }),
            new TableRow({ children: [moduleCell("4. Consultation", 3120), moduleCell("The doctor examines the patient and adds clinical notes.", 6240)] }),
            new TableRow({ children: [moduleCell("5a. Prescriptions", 3120), moduleCell("Medication is issued when treatment requires it.", 6240)] }),
            new TableRow({ children: [moduleCell("5b. Referrals", 3120), moduleCell("The patient is referred internally or to an external facility.", 6240)] }),
            new TableRow({ children: [moduleCell("5c. Medical records archive", 3120), moduleCell("The visit is consolidated into the patient's permanent history.", 6240)] }),
          ],
        }),
        new Paragraph({ spacing: { before: 240 }, children: [] }),
        h2("Key Models Used"),
        ...models.map(([name, desc]) => bullet(`**${name}** — ${desc}`)),
      ],
    },
  ],
});

Packer.toBuffer(doc).then((buf) => {
  require("fs").writeFileSync("/home/claude/hcs_docx/Health_Center_Services_Documentation.docx", buf);
  console.log("done");
});


