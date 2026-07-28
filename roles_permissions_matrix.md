# 🛡️ 19 System Roles & Granular Permission Matrix

This document defines the official **Role-Based Access Control (RBAC)** permissions matrix for all **19 Position Roles** across the **5 Operational Modules** of the City Health System.

---

## 📊 Summary Overview Table (29 Total Perms)

| # | Position / Role Title | Primary Department | Main Controls Access | Total Perms | Assigned Permissions Summary |
| :-: | :--- | :--- | :--- | :-: | :--- |
| **1** | **System Administrator** | Administration | All 4 Controls | **29 / 29** | Full System Access (All 29 permissions) |
| **2** | **Health Center Director** | Health Center Services | All 4 Controls | **16 / 29** | Main Controls (All 4) + All Health Center + View Users & Logs |
| **3** | **Doctor** | Health Center Services | Overview, Reports | **10 / 29** | Overview, Reports + Patients (View/Create/Edit), Consultations (View/Create), Triage (View), Prescriptions (View/Create) |
| **4** | **Nurse** | Health Center Services | Overview, Reports | **9 / 29** | Overview, Reports + Patients (View/Create/Edit), Triage (View/Create), Consultations (View), Prescriptions (View) |
| **5** | **Dentist** | Health Center Services | Overview, Reports | **9 / 29** | Overview, Reports + Patients (View/Create/Edit), Consultations (View/Create), Prescriptions (View/Create) |
| **6** | **Laboratory Technician** | Health Center Services | Overview | **4 / 29** | Overview + Patients (View), Consultations (View), Prescriptions (View) |
| **7** | **Medical Records Clerk** | Health Center Services | Overview | **4 / 29** | Overview + Patients (View/Create/Edit) |
| **8** | **Appointment Clerk** | Health Center Services | Overview | **4 / 29** | Overview + Patients (View/Create), Triage (View) |
| **9** | **Sanitation Director** | Sanitation Permits | All 4 Controls | **11 / 29** | Main Controls (All 4) + Permits (View/Create/Approve), Inspections (View/Conduct), View Users & Logs |
| **10** | **Inspector** | Sanitation Permits | Overview, Reports | **5 / 29** | Overview, Reports + Permits (View), Inspections (View/Conduct) |
| **11** | **Permit Clerk** | Sanitation Permits | Overview | **4 / 29** | Overview + Permits (View/Create), Inspections (View) |
| **12** | **Cashier** | Sanitation Permits | Overview | **2 / 29** | Overview + Permits (View) |
| **13** | **Immunization Coordinator** | Immunization & Nutrition | Overview, Analytics, Reports | **7 / 29** | Overview, Analytics, Reports + Immunization (View/Create/Edit), Patients (View) |
| **14** | **Midwife** | Immunization & Nutrition | Overview, Reports | **7 / 29** | Overview, Reports + Immunization (View/Create), Patients (View/Create), Triage (Create) |
| **15** | **Nutritionist** | Immunization & Nutrition | Overview, Analytics, Reports | **7 / 29** | Overview, Analytics, Reports + Immunization (View/Create/Edit), Patients (View) |
| **16** | **Nutrition Educator** | Immunization & Nutrition | Overview, Reports | **4 / 29** | Overview, Reports + Immunization (View/Create) |
| **17** | **Wastewater Officer** | Wastewater Services | Overview, Analytics, Reports | **6 / 29** | Overview, Analytics, Reports + Inspections (View/Conduct), Permits (View) |
| **18** | **Surveillance Officer** | Health Surveillance | All 4 Controls | **8 / 29** | Main Controls (All 4) + Patients (View), Consultations (View), Inspections (View), View Logs |
| **19** | **Surveillance Coordinator** | Health Surveillance | All 4 Controls | **8 / 29** | Main Controls (All 4) + Patients (View), Consultations (View), Inspections (View), View Logs |

---

## 📑 Detailed Permission Breakdown by Role

### 1. System Administrator
* **Department**: Administration
* **Granted Permissions (29/29)**:
  * **Main Controls**: `dashboard.view`, `analytics.view`, `reports.view`, `compliance.view`
  * **Health Center Services**: `patients.view`, `patients.create`, `patients.edit`, `patients.delete`, `consultations.view`, `consultations.create`, `triage.view`, `triage.create`, `prescriptions.view`, `prescriptions.create`
  * **Sanitation Permits**: `permits.view`, `permits.create`, `permits.approve`, `inspections.view`, `inspections.conduct`
  * **Immunization & Nutrition**: `immunization.view`, `immunization.create`, `immunization.edit`
  * **System Management**: `users.view`, `users.create`, `users.edit`, `users.delete`, `roles.manage`, `settings.manage`, `logs.view`

---

### 2. Health Center Director
* **Department**: Health Center Services
* **Granted Permissions (16/29)**:
  * **Main Controls**: `dashboard.view`, `analytics.view`, `reports.view`, `compliance.view`
  * **Health Center Services**: `patients.view`, `patients.create`, `patients.edit`, `patients.delete`, `consultations.view`, `consultations.create`, `triage.view`, `triage.create`, `prescriptions.view`, `prescriptions.create`
  * **System Management**: `users.view`, `logs.view`

---

### 3. Doctor
* **Department**: Health Center Services
* **Granted Permissions (10/29)**:
  * **Main Controls**: `dashboard.view`, `reports.view`
  * **Health Center Services**: `patients.view`, `patients.create`, `patients.edit`, `consultations.view`, `consultations.create`, `triage.view`, `prescriptions.view`, `prescriptions.create`

---

### 4. Nurse
* **Department**: Health Center Services
* **Granted Permissions (9/29)**:
  * **Main Controls**: `dashboard.view`, `reports.view`
  * **Health Center Services**: `patients.view`, `patients.create`, `patients.edit`, `triage.view`, `triage.create`, `consultations.view`, `prescriptions.view`

---

### 5. Dentist
* **Department**: Health Center Services
* **Granted Permissions (9/29)**:
  * **Main Controls**: `dashboard.view`, `reports.view`
  * **Health Center Services**: `patients.view`, `patients.create`, `patients.edit`, `consultations.view`, `consultations.create`, `prescriptions.view`, `prescriptions.create`

---

### 6. Laboratory Technician
* **Department**: Health Center Services
* **Granted Permissions (4/29)**:
  * **Main Controls**: `dashboard.view`
  * **Health Center Services**: `patients.view`, `consultations.view`, `prescriptions.view`

---

### 7. Medical Records Clerk
* **Department**: Health Center Services
* **Granted Permissions (4/29)**:
  * **Main Controls**: `dashboard.view`
  * **Health Center Services**: `patients.view`, `patients.create`, `patients.edit`

---

### 8. Appointment Clerk
* **Department**: Health Center Services
* **Granted Permissions (4/29)**:
  * **Main Controls**: `dashboard.view`
  * **Health Center Services**: `patients.view`, `patients.create`, `triage.view`

---

### 9. Sanitation Director
* **Department**: Sanitation Permits
* **Granted Permissions (11/29)**:
  * **Main Controls**: `dashboard.view`, `analytics.view`, `reports.view`, `compliance.view`
  * **Sanitation Permits**: `permits.view`, `permits.create`, `permits.approve`, `inspections.view`, `inspections.conduct`
  * **System Management**: `users.view`, `logs.view`

---

### 10. Inspector
* **Department**: Sanitation Permits
* **Granted Permissions (5/29)**:
  * **Main Controls**: `dashboard.view`, `reports.view`
  * **Sanitation Permits**: `permits.view`, `inspections.view`, `inspections.conduct`

---

### 11. Permit Clerk
* **Department**: Sanitation Permits
* **Granted Permissions (4/29)**:
  * **Main Controls**: `dashboard.view`
  * **Sanitation Permits**: `permits.view`, `permits.create`, `inspections.view`

---

### 12. Cashier
* **Department**: Sanitation Permits
* **Granted Permissions (2/29)**:
  * **Main Controls**: `dashboard.view`
  * **Sanitation Permits**: `permits.view`

---

### 13. Immunization Coordinator
* **Department**: Immunization & Nutrition
* **Granted Permissions (7/29)**:
  * **Main Controls**: `dashboard.view`, `analytics.view`, `reports.view`
  * **Immunization & Nutrition**: `immunization.view`, `immunization.create`, `immunization.edit`
  * **Health Center Services**: `patients.view`

---

### 14. Midwife
* **Department**: Immunization & Nutrition
* **Granted Permissions (7/29)**:
  * **Main Controls**: `dashboard.view`, `reports.view`
  * **Immunization & Nutrition**: `immunization.view`, `immunization.create`
  * **Health Center Services**: `patients.view`, `patients.create`, `triage.create`

---

### 15. Nutritionist
* **Department**: Immunization & Nutrition
* **Granted Permissions (7/29)**:
  * **Main Controls**: `dashboard.view`, `analytics.view`, `reports.view`
  * **Immunization & Nutrition**: `immunization.view`, `immunization.create`, `immunization.edit`
  * **Health Center Services**: `patients.view`

---

### 16. Nutrition Educator
* **Department**: Immunization & Nutrition
* **Granted Permissions (4/29)**:
  * **Main Controls**: `dashboard.view`, `reports.view`
  * **Immunization & Nutrition**: `immunization.view`, `immunization.create`

---

### 17. Wastewater Officer
* **Department**: Wastewater Services
* **Granted Permissions (6/29)**:
  * **Main Controls**: `dashboard.view`, `analytics.view`, `reports.view`
  * **Sanitation Permits**: `inspections.view`, `inspections.conduct`, `permits.view`

---

### 18. Surveillance Officer
* **Department**: Health Surveillance
* **Granted Permissions (8/29)**:
  * **Main Controls**: `dashboard.view`, `analytics.view`, `reports.view`, `compliance.view`
  * **Health Center / Sanitation**: `patients.view`, `consultations.view`, `inspections.view`
  * **System Management**: `logs.view`

---

### 19. Surveillance Coordinator
* **Department**: Health Surveillance
* **Granted Permissions (8/29)**:
  * **Main Controls**: `dashboard.view`, `analytics.view`, `reports.view`, `compliance.view`
  * **Health Center / Sanitation**: `patients.view`, `consultations.view`, `inspections.view`
  * **System Management**: `logs.view`

---

## 🔑 All 29 Granular Permissions Reference List

### Main Controls (4)
1. `dashboard.view`: Access System Overview
2. `analytics.view`: Access Analytics Dashboards
3. `reports.view`: Access Reports & Export Tools
4. `compliance.view`: Access Compliance & Violations Monitoring

### Health Center Services (10)
5. `patients.view`: View patient records
6. `patients.create`: Register new patients
7. `patients.edit`: Update patient profiles
8. `patients.delete`: Archive or delete patient records
9. `consultations.view`: View clinical consultations
10. `consultations.create`: Record clinical consultations
11. `triage.view`: View vital signs and triage records
12. `triage.create`: Record initial triage assessment
13. `prescriptions.view`: View medication prescriptions
14. `prescriptions.create`: Prescribe medications

### Sanitation Permits (5)
15. `permits.view`: View sanitation permit applications
16. `permits.create`: File new permit applications
17. `permits.approve`: Approve or reject permit applications
18. `inspections.view`: View sanitation inspection reports
19. `inspections.conduct`: Perform and submit sanitation inspections

### Immunization & Nutrition (3)
20. `immunization.view`: View vaccine & growth monitoring records
21. `immunization.create`: Record vaccinations & growth measurements
22. `immunization.edit`: Update vaccine schedules & nutrition logs

### System Management (7)
23. `users.view`: View employee list
24. `users.create`: Register new employees
25. `users.edit`: Update employee profiles and roles
26. `users.delete`: Disable or delete employee accounts
27. `roles.manage`: Modify role definitions and permissions
28. `settings.manage`: Modify system settings
29. `logs.view`: View system activity audit trail
