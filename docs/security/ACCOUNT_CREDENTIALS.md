# 🔐 Civentral Account Credentials & Head Roles Directory

This document contains the account login credentials, Employee IDs (`emp_id`), positions, and department details for the **System Administrator** and the **Head / Lead Roles** across each module in the system.

---

## 🔑 Default Login Credentials Overview

> [!NOTE]
> **Default Password for All Seed Accounts:** `password123`  
> Users can log in using either their **Employee ID / Username** or their registered **Email address**.

---

## 👑 1. System Administration (Admin)

| Field | Information |
| :--- | :--- |
| **Employee ID (`emp_id`)** | `HSA-ADMIN-01` |
| **Username** | `HSA-ADMIN-01` |
| **Full Name** | Joshua Sierra |
| **Role Title** | System Administrator |
| **Department** | Administration |
| **Email** | `admin@health.gov.ph` |
| **Password** | `password123` |
| **Permission Scope** | Full System Access (29/29 Permissions) |

---

## 🏢 2. Module Head Roles & Access Permissions

### 🏥 Module 1: Health Center Services — Health Center Director
* **Employee ID (`emp_id`)**: `HCD-0001`
* **Username**: `HCD-0001`
* **Full Name**: Maria Santos
* **Email**: `maria@health.gov.ph`
* **Password**: `password123`
* **Role**: Health Center Director (Module Head)
* **Access Count**: 16 / 29 Permissions
* **Module & System Access Scope**:
  * **Main Controls**: `dashboard.view`, `analytics.view`, `reports.view`, `compliance.view` (System Overview, Analytics, Reports & Exports, Compliance & Violations)
  * **Clinical & Patient Management**: `patients.view`, `patients.create`, `patients.edit`, `patients.delete` (Full patient profile control & record archiving)
  * **Medical Operations**: `consultations.view`, `consultations.create`, `triage.view`, `triage.create`, `prescriptions.view`, `prescriptions.create` (Consultations, Vital Signs Triage, and Prescriptions)
  * **Administrative Oversight**: `users.view`, `logs.view` (View Employee Directory and System Activity Audit Logs)

---

### 📋 Module 2: Sanitation Permits — Sanitation Director
* **Employee ID (`emp_id`)**: `SD-0001`
* **Username**: `SD-0001`
* **Full Name**: Pedro Garcia
* **Email**: `sdirector@health.gov.ph`
* **Password**: `password123`
* **Role**: Sanitation Director (Module Head)
* **Access Count**: 11 / 29 Permissions
* **Module & System Access Scope**:
  * **Main Controls**: `dashboard.view`, `analytics.view`, `reports.view`, `compliance.view` (Overview, Analytics, Reports, Compliance Monitoring)
  * **Permit Authority**: `permits.view`, `permits.create`, `permits.approve` (Permit application processing, official approval & rejection authority)
  * **Inspections**: `inspections.view`, `inspections.conduct` (Field sanitation inspection reviews and conducting inspections)
  * **Administrative Oversight**: `users.view`, `logs.view` (View Employee List and Activity Logs)

---

### 💉 Module 3: Immunization & Nutrition — Immunization Coordinator
* **Employee ID (`emp_id`)**: `IL-0001`
* **Username**: `IL-0001`
* **Full Name**: Grace Mendoza
* **Email**: `immunization@health.gov.ph`
* **Password**: `password123`
* **Role**: Immunization Coordinator (Module Head / Lead)
* **Access Count**: 7 / 29 Permissions
* **Module & System Access Scope**:
  * **Main Controls**: `dashboard.view`, `analytics.view`, `reports.view` (Overview, Analytics Dashboards, Program Reports)
  * **Immunization & Growth Tracking**: `immunization.view`, `immunization.create`, `immunization.edit` (Vaccination records, infant schedules, child growth logs & BMI tracking)
  * **Patient Registry**: `patients.view` (Look up patient demographics and medical histories)

---

### 🏭 Module 4: Wastewater Services — Wastewater Officer
* **Employee ID (`emp_id`)**: `WL-0001`
* **Username**: `WL-0001`
* **Full Name**: Ramon Flores
* **Email**: `wastewater@health.gov.ph`
* **Password**: `password123`
* **Role**: Wastewater Officer (Module Lead)
* **Access Count**: 6 / 29 Permissions
* **Module & System Access Scope**:
  * **Main Controls**: `dashboard.view`, `analytics.view`, `reports.view` (System Overview, Wastewater Analytics, Service Reports)
  * **Environmental Inspections**: `inspections.view`, `inspections.conduct` (Inspect septic tanks, desludging compliance, and environmental safety)
  * **Sanitation Coordination**: `permits.view` (View business sanitation permits related to wastewater compliance)

---

### 🦟 Module 5: Health Surveillance — Surveillance Coordinator / Officer
* **Employee ID (`emp_id`)**: `SL-0002` (James Rivera - *Surveillance Coordinator*) / `SL-0001` (Sofia Lim - *Surveillance Officer*)
* **Username**: `SL-0002` / `SL-0001`
* **Full Name**: James Rivera / Sofia Lim
* **Email**: `coordinator@health.gov.ph` / `surveillance@health.gov.ph`
* **Password**: `password123`
* **Role**: Surveillance Coordinator / Officer (Module Lead)
* **Access Count**: 8 / 29 Permissions
* **Module & System Access Scope**:
  * **Main Controls**: `dashboard.view`, `analytics.view`, `reports.view`, `compliance.view` (Outbreak Heatmaps, Epidemiologic Analytics, Disease Reports, Compliance)
  * **Cross-Module Case Monitoring**: `patients.view`, `consultations.view`, `inspections.view` (Monitor clinical diagnoses, communicable disease reports, and field inspection risks for contact tracing)
  * **System Auditing**: `logs.view` (Audit trail inspection)

---

## 📑 Complete Quick Reference Table

| Employee ID (`emp_id`) | Full Name | Module / Department | Role | Email | Password | Granted Access Summary |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `HSA-ADMIN-01` | Joshua Sierra | Administration | **System Administrator** | `admin@health.gov.ph` | `password123` | **Full System Access** (29/29 permissions) |
| `HCD-0001` | Maria Santos | Health Center Services | **Health Center Director** | `maria@health.gov.ph` | `password123` | Main Controls, Full Health Center (Patients, Consultations, Triage, RX), Users & Logs |
| `SD-0001` | Pedro Garcia | Sanitation Permits | **Sanitation Director** | `sdirector@health.gov.ph` | `password123` | Main Controls, Full Sanitation (Create/Approve Permits, Inspections), Users & Logs |
| `IL-0001` | Grace Mendoza | Immunization & Nutrition | **Immunization Coordinator** | `immunization@health.gov.ph` | `password123` | Analytics/Reports, Immunization & Nutrition (View/Create/Edit), Patient Lookup |
| `WL-0001` | Ramon Flores | Wastewater Services | **Wastewater Officer** | `wastewater@health.gov.ph` | `password123` | Analytics/Reports, Wastewater & Environmental Inspections, View Permits |
| `SL-0002` | James Rivera | Health Surveillance | **Surveillance Coordinator** | `coordinator@health.gov.ph` | `password123` | Main Controls & Heatmaps, Cross-Module Visibility (Consultations, Patients, Inspections), Logs |
