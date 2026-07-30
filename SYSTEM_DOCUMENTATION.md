# 📋 HIMS System Documentation
> **Health Information Management System** — Barangay Health Center & Sanitation Office
> Last Updated: 2026-07-30

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [Tech Stack](#2-tech-stack)
3. [Project Structure](#3-project-structure)
4. [Authentication Flow](#4-authentication-flow)
5. [RBAC — Role-Based Access Control](#5-rbac--role-based-access-control)
6. [Permission System Deep Dive](#6-permission-system-deep-dive)
7. [Dashboard Customization Flow](#7-dashboard-customization-flow)
8. [Sidebar & Navigation Flow](#8-sidebar--navigation-flow)
9. [Module Inventory](#9-module-inventory)
10. [Database Models](#10-database-models)
11. [Key Config Files](#11-key-config-files)
12. [Role to Dashboard Mapping](#12-role-to-dashboard-mapping)
13. [How to Add a New Role / Department](#13-how-to-add-a-new-role--department)
14. [Common Pitfalls & Rules](#14-common-pitfalls--rules)

---

## 1. System Overview

The **HIMS** is a multi-department health management web application serving:

| Department | Purpose |
|---|---|
| **Health Center** | Patient management, triage, consultations, prescriptions, surveillance |
| **Sanitation & Wastewater** | Sanitation permits, field inspections, wastewater & septic management |
| **Immunization / Nutrition** | Child records, vaccination tracking, nutrition assessment |
| **Surveillance** | Disease outbreak detection, case reports, contact tracing, alerts |
| **System Administration** | User management, roles, settings, audit logs |

All departments share a **single login** and are routed to the same `pages/dashboard.php` which renders **different content per role** using the RBAC permission system.

---

## 2. Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8+ (procedural + OOP, no framework) |
| Frontend | HTML + Vanilla JS + TailwindCSS (JIT, v3) |
| Database | MySQL (via XAMPP / LAMPP) |
| Server | Apache (XAMPP / LAMPP on Linux) |
| Auth | PHP Sessions + Remember Me (cookie token) |
| Build | npm + Tailwind CLI (`npm run dev` / `npm run build`) |

---

## 3. Project Structure

```
/opt/lampp/htdocs/capstone/
│
├── login.php                   # Entry point — auth form + login logic
├── logout.php                  # Clears session + redirects
├── index.php                   # Public landing / redirect
│
├── config/
│   ├── paths.php               # BASE_URL, site_url(), hasPermission() global helper bootstrap
│   ├── navigation.php          # Sidebar navigation config (sections, modules, permissions)
│   └── database.php            # DB connection singleton
│
├── app/
│   ├── Constants/
│   │   └── Permissions.php     # All 33 permission slug constants
│   ├── Middleware/
│   │   └── AuthorizationMiddleware.php  # authorize() — blocks page & API access
│   ├── Models/
│   │   ├── Employee.php        # Employee queries (login, profile, roles)
│   │   ├── Patient.php         # Patient CRUD
│   │   ├── Role.php            # Role CRUD + permission matrix helpers
│   │   ├── Permit.php          # Sanitation permit queries
│   │   ├── Inspection.php      # Inspection queries
│   │   ├── Triage.php          # Triage queue
│   │   ├── TriageQueue.php     # Queue management
│   │   ├── Consultation.php    # Consultations
│   │   ├── Prescription.php    # Prescriptions
│   │   ├── Appointment.php     # Appointments
│   │   ├── MedicalRecord.php   # Medical records
│   │   ├── Referral.php        # Patient referrals
│   │   ├── Child.php           # Child immunization records
│   │   ├── Payment.php         # Permit payment records
│   │   ├── Renewal.php         # Permit renewals
│   │   ├── PermitRecords.php   # Permit record history
│   │   ├── PermitDocument.php  # Permit document uploads
│   │   └── ActivityLog.php     # Audit log model
│   ├── services/
│   │   ├── PermissionService.php     # Core RBAC engine (singleton)
│   │   ├── NavigationService.php     # Builds filtered sidebar per role
│   │   ├── DepartmentResolver.php    # Maps role to department name
│   │   ├── SettingsService.php       # System settings key-value store
│   │   └── RememberMeService.php     # Persistent login cookie manager
│   └── helpers/
│       └── Settings.php              # Global Settings::get() helper
│
├── pages/
│   ├── dashboard.php           # Main role-aware dashboard (3-column layout)
│   ├── ai_insights.php         # AI-powered analytics page
│   ├── compliance_monitoring.php
│   ├── custom_report.php
│   ├── module_activity.php
│   ├── report_templates.php
│   └── export.php
│
├── modules/
│   ├── healthservices/         # Health Center modules (8 files)
│   ├── sanitation/             # Sanitation & Permits modules (6 files)
│   ├── immunization/           # Immunization & Nutrition modules (5 files)
│   ├── surveillence/           # Disease Surveillance modules (6 files)
│   └── services/               # Wastewater & Septic services (5 files)
│
├── includes/                   # Shared layout partials
│   ├── header.php
│   ├── sidebar.php             # Role-filtered sidebar (uses NavigationService)
│   └── footer.php
│
└── management/                 # Admin management pages
    ├── users.php
    ├── roles.php
    ├── settings.php
    └── logs.php
```

---

## 4. Authentication Flow

```
User visits login.php
    |
    v
Submits username + password
    |
    v
login.php queries employees table
    |
    |-- Invalid credentials --> flash error, redirect back to login.php
    |
    +-- Valid --> sets PHP session:
            $_SESSION['employee_id']
            $_SESSION['employee_name']
            $_SESSION['role']              <- e.g. 'Sanitation Director'
            $_SESSION['role_description']  <- same or more descriptive label
            $_SESSION['department']
            $_SESSION['logged_in'] = true
                |
                v
        NavigationService::getLandingPage()
                |
                v
        Redirect --> pages/dashboard.php
```

### Remember Me (Persistent Login)
- `RememberMeService` generates a secure random token on login
- Stored in `remember_tokens` DB table + browser cookie (`remember_token`)
- On subsequent visits, `config/paths.php` checks cookie and auto-restores session

### Session-Based Auth Guard
Every protected page starts with:
```php
require_once __DIR__ . '/../../config/paths.php';
if (!isset($_SESSION['logged_in'])) {
    header('Location: ' . site_url('login.php'));
    exit;
}
```

---

## 5. RBAC — Role-Based Access Control

### The 19 System Roles

| # | Role | Department |
|---|---|---|
| 1 | **System Administrator** | Administration |
| 2 | **Health Center Director** | Health Center |
| 3 | **Doctor** | Health Center |
| 4 | **Nurse** | Health Center |
| 5 | **Dentist** | Health Center |
| 6 | **Laboratory Technician** | Health Center |
| 7 | **Medical Records Clerk** | Health Center |
| 8 | **Appointment Clerk** | Health Center |
| 9 | **Surveillance Officer** | Health Center |
| 10 | **Surveillance Coordinator** | Health Center |
| 11 | **Sanitation Director** | Sanitation |
| 12 | **Inspector** | Sanitation |
| 13 | **Permit Clerk** | Sanitation |
| 14 | **Cashier** | Sanitation |
| 15 | **Wastewater Officer** | Sanitation |
| 16 | **Immunization Coordinator** | Immunization |
| 17 | **Midwife** | Immunization |
| 18 | **Nutritionist** | Immunization |
| 19 | **Nutrition Educator** | Immunization |

### How Roles Are Stored
- Stored in `employees` table: `employees.role = 'Sanitation Director'`
- On login this is saved to `$_SESSION['role']`
- `$_SESSION['role_description']` is a secondary label (same value or more descriptive)

---

## 6. Permission System Deep Dive

### All Permission Slugs (33 total)

| Category | Slugs |
|---|---|
| Dashboard | `dashboard.view`, `dashboard.health_center`, `dashboard.sanitation`, `dashboard.immunization`, `dashboard.system_admin` |
| Analytics | `analytics.view`, `reports.view`, `compliance.view` |
| Patients | `patients.view`, `patients.create`, `patients.edit`, `patients.delete` |
| Consultations | `consultations.view`, `consultations.create` |
| Triage | `triage.view`, `triage.create` |
| Prescriptions | `prescriptions.view`, `prescriptions.create` |
| Sanitation | `permits.view`, `permits.create`, `permits.approve`, `inspections.view`, `inspections.conduct` |
| Immunization | `immunization.view`, `immunization.create`, `immunization.edit` |
| System Admin | `users.view`, `users.create`, `users.edit`, `users.delete`, `roles.manage`, `settings.manage`, `logs.view` |

### How hasPermission() Works — Step by Step

```
Page calls: hasPermission('dashboard.sanitation')
                |
                v
        config/paths.php global helper
                |
                v
        PermissionService::getInstance()->hasPermission($slug)
                |
                |-- Is Admin? (System Administrator / System Admin / admin)
                |       +-- YES --> return TRUE immediately (full bypass, no check needed)
                |
                |-- Feature flag check: Settings::get('feature.{slug}', true)
                |       +-- If disabled globally --> return FALSE
                |
                +-- getGrantedPermissions()
                        |
                        |-- Check session cache ($_SESSION['granted_permission_slugs'])
                        |       +-- Cache HIT --> return cached array directly
                        |
                        |-- Fetch role from DB (Role model queries roles table)
                        |-- Merge DB-granted permissions + defaultRolePermissionMatrix()
                        |-- Store result in session cache
                        +-- return in_array($slug, $grantedSlugs)
```

### Permission Resolution Priority
1. **Admin bypass** (System Administrator always returns true)
2. **Feature flag** (can disable any permission globally via Settings page)
3. **DB role permissions** (stored in `roles` + `role_permissions` tables — admin-configurable)
4. **Default matrix** (hardcoded fallback in `PermissionService::defaultRolePermissionMatrix()`)

> **Important:** DB permissions and matrix defaults are MERGED. The user gets the UNION of both sources.

### Role-to-Permission Matrix Summary

| Role | Dashboard Permission | Key Feature Permissions |
|---|---|---|
| System Administrator | `dashboard.system_admin` | ALL permissions |
| Health Center Director | `dashboard.health_center` | patients.*, consultations.*, triage.*, prescriptions.*, users.* |
| Doctor | `dashboard.health_center` | patients.view/create/edit, consultations.*, triage.view, prescriptions.* |
| Nurse | `dashboard.health_center` | patients.view/create/edit, triage.*, consultations.view, prescriptions.view |
| Dentist | `dashboard.health_center` | patients.view/create/edit, consultations.*, prescriptions.* |
| Laboratory Technician | `dashboard.health_center` | patients.view, consultations.view, prescriptions.view |
| Medical Records Clerk | `dashboard.health_center` | patients.view/create/edit |
| Appointment Clerk | `dashboard.health_center` | patients.view/create, triage.view |
| Surveillance Officer | `dashboard.health_center` | patients.view, consultations.view, inspections.view, **logs.view** |
| Surveillance Coordinator | `dashboard.health_center` | patients.view, consultations.view, inspections.view, **logs.view** |
| Sanitation Director | `dashboard.sanitation` | permits.*, inspections.*, analytics.view, reports.view, users.view/create/edit |
| Inspector | `dashboard.sanitation` | permits.view, inspections.view/conduct, reports.view |
| Permit Clerk | `dashboard.sanitation` | permits.view/create, inspections.view |
| Cashier | `dashboard.sanitation` | permits.view |
| Wastewater Officer | `dashboard.sanitation` | inspections.view/conduct, permits.view, analytics.view, reports.view |
| Immunization Coordinator | `dashboard.immunization` | immunization.*, analytics.view, reports.view, patients.view |
| Midwife | `dashboard.immunization` | immunization.view/create, patients.view/create, triage.create |
| Nutritionist | `dashboard.immunization` | immunization.*, analytics.view, reports.view, patients.view |
| Nutrition Educator | `dashboard.immunization` | immunization.view/create, reports.view |

### Enforcing Access on a Page

```php
// Option 1: Redirect on fail (use at page top)
requirePermission('permits.view');

// Option 2: Conditional UI element
if (hasPermission('permits.approve')) {
    // show approve button
}

// Option 3: Direct middleware call
AuthorizationMiddleware::authorize('users.delete');
```

---

## 7. Dashboard Customization Flow

`pages/dashboard.php` renders 4 role-specific sections. All driven by two flags:

```php
// Computed ONCE near the top of dashboard.php
// These flags are reused in ALL 4 sections — never recompute mid-page
$_isHcRole  = hasPermission('dashboard.health_center');
$_isSanRole = hasPermission('dashboard.sanitation');
```

### Section 1 — Page Header (Title & Badge)

```
hasPermission('dashboard.health_center') = true
    --> "Health Center Services Dashboard"

hasPermission('dashboard.sanitation') = true
    --> "Sanitation Permits Dashboard"

hasPermission('dashboard.system_admin') = true
    --> "System Overview Dashboard"

else
    --> "{currentRole} Dashboard"
```

### Section 2 — KPI Cards Row (6 cards)

```
$_isHcRole = true
    --> Patients Served | Consultations | Triage Visits
        Prescriptions Issued | Health Surveillance | Real-time Alerts

$_isSanRole = true
    --> Sanitation Permits | Field Inspections | Permit Renewals
        Wastewater Requests | Septic Registry | Compliance Violations

else (admin/default)
    --> Health Center | Sanitation | Immunization
        Surveillance | Analytics | System Health
```

### Section 3 — 3-Column Layout

**Column 1 — Module Activity Summary**
```
$_isHcRole  --> Patient Mgmt, Consultations, Prescriptions,
                Triage, Medical Records, Surveillance

$_isSanRole --> Sanitation Permits, Field Inspections,
                Permit Renewals, Wastewater & Septic,
                Regulatory Compliance

else (admin) --> All department summary cards
```

**Column 2 — Alerts & Notifications**
```
$_isSanRole --> Sanitation alerts:
               [CRITICAL] Permit Expiry Alert
               [WARNING]  Compliance Violation Found
               [INFO]     Inspection Schedule Tomorrow
               [SUCCESS]  New Permit Application
               [PENDING]  Desludging Request Pending

else (HC/Admin) --> Health alerts:
               [CRITICAL] Disease Outbreak Alert
               [WARNING]  Vaccine Stocks Running Low
               [INFO]     Pending Permit Inspections
               [SYSTEM]   System Backup Completed
               [SUCCESS]  New Patient Registered
```

**Column 3 — Right Status Panel**
```
$_isHcRole
    --> Triage Status & Patient Queue
        (P1 Emergency, P2 Urgent, P3 Standard queues + Vital Signs Progress)

$_isSanRole
    --> Sanitation Compliance Status
        (Permit Processing, Field Inspections, Compliance Violations,
         Wastewater & Septic, Overall Compliance Rate)

hasPermission('dashboard.system_admin')
    --> System Health Status
        (Server Status, Database, API Services, AI Engine, Backup, Storage)

-- no panel rendered for other roles --
```

---

## 8. Sidebar & Navigation Flow

```
sidebar.php (includes/sidebar.php)
    |
    v
NavigationService::getInstance()->getFilteredNavigation()
    |
    v
Reads config/navigation.php (full sections array)
    |
    v
For each section:
    check section-level permissions (hasAnyPermission)
    For each module / item:
        check item permission (hasPermission)
        filter children by their permissions
    Only render items user has access to
    |
    v
Returns filtered navigation array
    |
    v
sidebar.php renders it as HTML
```

### Sidebar Access Map

```
Dashboard                [dashboard.view]

Health Center            [patients.view OR consultations.view OR triage.view]
  Patients               [patients.view]
  Triage                 [triage.view]
  Consultations          [consultations.view]
  Prescriptions          [prescriptions.view]
  Appointments           [patients.view]
  Medical Records        [patients.view]
  Referrals              [patients.view]

Sanitation               [permits.view OR inspections.view]
  Permit Applications    [permits.view]
  Permit Records         [permits.view]
  Inspections            [inspections.view]
  Renewals               [permits.view]
  Payments               [permits.view]
  Documents              [permits.view]

Wastewater Services      [inspections.view]
  Service Requests       [inspections.view]
  Septic Tanks           [inspections.view]
  Wastewater Billing     [inspections.view]
  Providers              [inspections.view]
  Maintenance            [inspections.view]

Immunization             [immunization.view]
  Child Records          [immunization.view]
  Vaccination Tracking   [immunization.view]
  Vaccine Inventory      [immunization.view]
  Growth Charts          [immunization.view]
  Nutrition Assessment   [immunization.view]

Surveillance             [compliance.view]
  Case Reports           [compliance.view]
  Alerts                 [compliance.view]
  Contact Tracing        [compliance.view]
  Disease Mapping        [compliance.view]
  Outbreak Detection     [compliance.view]
  Response Management    [compliance.view]

Reports                  [reports.view]
  AI Insights            [analytics.view]
  Custom Reports         [reports.view]
  Module Activity        [reports.view]
  Export                 [reports.view]

Administration           [users.view OR roles.manage OR settings.manage OR logs.view]
  Users                  [users.view]
  Roles                  [roles.manage]
  Settings               [settings.manage]
  System Logs            [logs.view]   <-- NOT for Sanitation roles
```

---

## 9. Module Inventory

### Health Center (modules/healthservices/)

| File | Purpose | Key Permission |
|---|---|---|
| patients.php | Patient records CRUD | patients.view |
| triage.php | Triage assessment & queue | triage.view |
| consultations.php | Doctor-patient consultations | consultations.view |
| prescriptions.php | Medication prescriptions | prescriptions.view |
| appointments.php | Appointment scheduling | patients.view |
| medical_records.php | Patient medical history | patients.view |
| referrals.php | Inter-facility referrals | patients.view |
| queue_management.php | Live queue display | triage.view |

### Sanitation (modules/sanitation/)

| File | Purpose | Key Permission |
|---|---|---|
| permit_applications.php | New permit applications | permits.view |
| permit_records.php | Permit history & audit | permits.view |
| inspections.php | Field inspection records | inspections.view |
| renewals.php | Permit renewal processing | permits.view |
| payments.php | Permit payment transactions | permits.view |
| documents.php | Permit document uploads | permits.view |

### Wastewater Services (modules/services/)

| File | Purpose | Key Permission |
|---|---|---|
| service_requests.php | Desludging/wastewater requests | inspections.view |
| septic_tanks.php | Septic tank registry | inspections.view |
| wastewater_billing.php | Service billing & payments | inspections.view |
| providers.php | Service provider directory | inspections.view |
| maintenance.php | Equipment maintenance records | inspections.view |

### Immunization / Nutrition (modules/immunization/)

| File | Purpose | Key Permission |
|---|---|---|
| child_records.php | Child immunization records | immunization.view |
| vaccination_tracking.php | Vaccine dose tracking | immunization.view |
| vaccine_inventory.php | Vaccine stock management | immunization.view |
| growth_charts.php | Child growth monitoring | immunization.view |
| nutrition_assessment.php | Nutritional status assessment | immunization.view |

### Disease Surveillance (modules/surveillence/)

| File | Purpose | Key Permission |
|---|---|---|
| case_reports.php | Disease case reporting | compliance.view |
| alerts.php | Outbreak & disease alerts | compliance.view |
| contact_tracing.php | Contact tracing records | compliance.view |
| mapping.php | Geographic disease mapping | compliance.view |
| outbreak_detection.php | Automated outbreak detection | compliance.view |
| response_management.php | Response coordination | compliance.view |

---

## 10. Database Models

All in `app/Models/`. Use PDO via `config/database.php`.

| Model | Table(s) | Purpose |
|---|---|---|
| Employee | employees | User accounts, login, roles |
| Role | roles, role_permissions | Role definitions & permission matrix |
| Patient | patients | Patient records |
| Triage | triage_records | Triage assessments |
| TriageQueue | triage_queue | Live patient queue |
| Consultation | consultations | Doctor consultations |
| Prescription | prescriptions | Medication prescriptions |
| Appointment | appointments | Scheduled appointments |
| MedicalRecord | medical_records | Patient medical history |
| Referral | referrals | Inter-facility referrals |
| Permit | permits | Sanitation permit applications |
| Inspection | inspections | Field inspection records |
| PermitRecords | permit_records | Permit audit history |
| PermitDocument | permit_documents | Uploaded permit files |
| Renewal | renewals | Permit renewal records |
| Payment | payments | Permit payment transactions |
| Child | children | Child immunization records |
| ActivityLog | activity_logs | System-wide audit trail |

---

## 11. Key Config Files

### config/paths.php — Global Bootstrap
Loaded by every page. Provides:
- `BASE_URL` constant and `site_url($path)` helper
- `hasPermission(string $slug): bool` — GLOBAL function (use this everywhere)
- `requirePermission(string $slug): void` — redirect/block if no access
- `getUserGrantedPermissions(): array` — returns all slugs for current user
- `getClientIP()`, `getClientDevice()` — for audit log entries
- Settings engine bootstrap (timezone, maintenance mode enforcement)

### config/navigation.php — Sidebar Config
PHP array defining all sidebar sections, modules, and child links. Each entry:
```php
[
    'key'         => 'patients',
    'label'       => 'Patients',
    'icon'        => 'fa-users',
    'route'       => 'modules/healthservices/patients.php',
    'permission'  => 'patients.view',    // single slug check
    // OR
    'permissions' => ['permits.view', 'inspections.view'],  // any of these
]
```

### .env — Environment Variables
```
DB_HOST=localhost
DB_NAME=capstone
DB_USER=root
DB_PASS=
BASE_URL=/capstone
APP_ENV=local
```

---

## 12. Role to Dashboard Mapping

| Role | Dashboard Type | KPI Set | Column 3 Panel | Logs Access |
|---|---|---|---|---|
| System Administrator | System Overview | All departments | System Health Status | YES |
| Health Center Director | Health Center | HC-specific | Triage Queue | NO |
| Doctor | Health Center | HC-specific | Triage Queue | NO |
| Nurse | Health Center | HC-specific | Triage Queue | NO |
| Dentist | Health Center | HC-specific | Triage Queue | NO |
| Laboratory Technician | Health Center | HC-specific | Triage Queue | NO |
| Medical Records Clerk | Health Center | HC-specific | Triage Queue | NO |
| Appointment Clerk | Health Center | HC-specific | Triage Queue | NO |
| Surveillance Officer | Health Center | HC-specific | Triage Queue | YES |
| Surveillance Coordinator | Health Center | HC-specific | Triage Queue | YES |
| **Sanitation Director** | **Sanitation** | **Sanitation** | **Compliance Status** | **NO** |
| Inspector | Sanitation | Sanitation | Compliance Status | NO |
| Permit Clerk | Sanitation | Sanitation | Compliance Status | NO |
| Cashier | Sanitation | Sanitation | Compliance Status | NO |
| Wastewater Officer | Sanitation | Sanitation | Compliance Status | NO |
| Immunization Coordinator | Immunization | (default) | none | NO |
| Midwife | Immunization | (default) | none | NO |
| Nutritionist | Immunization | (default) | none | NO |
| Nutrition Educator | Immunization | (default) | none | NO |

---

## 13. How to Add a New Role / Department

### Step 1 — Add Permission Constants (app/Constants/Permissions.php)
```php
public const NEW_DEPT_DASHBOARD = 'dashboard.new_dept';
public const NEW_FEATURE_VIEW   = 'new_feature.view';
```

### Step 2 — Add to Permission Matrix (app/services/PermissionService.php)
In `defaultRolePermissionMatrix()`:
```php
'New Role Name' => [
    'dashboard.view', 'dashboard.new_dept',
    'new_feature.view', 'reports.view',
],
```

### Step 3 — Add Flag and KPI Cards (pages/dashboard.php)
At the top of the KPI section (with other flags):
```php
$_isNewRole = hasPermission('dashboard.new_dept');
```
Then add to the KPI if/elseif chain:
```php
elseif ($_isNewRole) {
    $kpiCards = [ /* 6 cards */ ];
}
```

### Step 4 — Add Module Activity Summary Cards
```php
} elseif ($_isNewRole) {
    $moduleSummaryCards = [ /* cards */ ];
}
```

### Step 5 — Add Custom Alerts Column
```php
<?php elseif ($_isNewRole): ?>
<!-- New dept alerts HTML -->
<?php endif; ?>
```

### Step 6 — Add Column 3 Panel
```php
elseif ($_isNewRole):
<!-- New dept status panel HTML -->
```

### Step 7 — Add Sidebar Navigation (config/navigation.php)
```php
[
    'key'         => 'new_dept',
    'label'       => 'New Department',
    'icon'        => 'fa-building',
    'permissions' => ['new_feature.view'],
    'children'    => [
        [
            'label'      => 'Feature Page',
            'route'      => 'modules/new_dept/feature.php',
            'permission' => 'new_feature.view'
        ],
    ]
]
```

### Step 8 — Create Module PHP Files (modules/new_dept/)
```php
<?php
require_once __DIR__ . '/../../config/paths.php';
requirePermission('new_feature.view');  // auto-redirects if unauthorized
// ... page logic
```

---

## 14. Common Pitfalls & Rules

### RULE 1: Always use hasPermission() — Never string-compare roles
```php
// CORRECT — uses the permission matrix, works for all role name variations
$_isSanRole = hasPermission('dashboard.sanitation');

// WRONG — breaks if session value has different casing or spacing
$_isSanRole = (strcasecmp($_SESSION['role'], 'Sanitation Director') === 0);
```

### RULE 2: Dashboard flags must be set ONCE before all sections
`$_isHcRole` and `$_isSanRole` are computed near the top of `dashboard.php`
and reused across all 4 sections. **Never recompute them mid-page.**

### RULE 3: Invalidate permission cache after role changes
```php
PermissionService::getInstance()->invalidateCache();
// Clears $_SESSION['granted_permission_slugs'] so new permissions load fresh
```

### RULE 4: Admin always has full access — do not use role strings to gate admin
`hasPermission()` returns `true` for System Administrator for ANY slug.
Never write: `if ($role === 'System Administrator')` — use `hasPermission()`.

### RULE 5: Never instantiate PermissionService directly in page files
```php
// WRONG — namespace not auto-loaded on all pages, causes fatal error
$ps = new PermissionService();

// CORRECT — use global helper, always available via config/paths.php
hasPermission('some.slug');
requirePermission('some.slug');
```

### RULE 6: System Logs (logs.view) access
Only these roles have `logs.view` by default:
- System Administrator
- Surveillance Officer
- Surveillance Coordinator

**Sanitation Director does NOT have `logs.view` — by design.**

### RULE 7: Permission resolution is DB + Matrix MERGED
- Hardcoded matrix = baseline defaults (always applied)
- DB permissions = admin-configured overrides (also applied)
- Result = UNION of both — user gets the most permissive combination
- If you remove from the matrix but DB still grants it, user still has it
- To fully revoke: remove from both matrix AND DB

### RULE 8: Clear browser session after permission changes
If you update the permission matrix in code but the page still shows old
behavior, it's the session cache. Either:
- Logout and log back in, OR
- Call `PermissionService::getInstance()->invalidateCache()`

---

*Generated from codebase analysis — /opt/lampp/htdocs/capstone/ — 2026-07-30*
