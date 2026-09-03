# RBAC Permission Enforcement & Checkbox Usability Plan

**Date:** August 28, 2026  
**Scope:** End-to-End Permission Matrix Enforcement across UI, Navigation, Pages, and APIs

---

## 1. Executive Summary & Objective

When an Administrator or Department Head toggles permission checkboxes in **User Management** (via the `Manage Permissions` modal) and clicks **Save Permissions**, those checkboxes must directly and immediately govern:
1. **Sidebar Navigation**: Show or hide modules, sub-menus, and links.
2. **Page Access (Routing)**: Block direct URL access with a 403 / redirect if the required view permission is unchecked.
3. **UI Action Buttons**: Show or hide action buttons (e.g., `+ Register`, `Edit`, `Delete`, `Approve`, `Conduct Inspection`, `Manage`) based on granular action permissions.
4. **Backend API Endpoints**: Reject unauthorized AJAX/API mutations with HTTP 403 JSON responses.
5. **Real-time Session Cache Invalidation**: Changes take effect immediately without requiring the user to log out and log back in.

---

## 2. Permission Checkbox Mapping & Expected Behavior Matrix

Below is the complete mapping of every checkbox in the User Management Permission Matrix to its real-world effect in the application:

| Permission Slug | Checkbox Label in Modal | When Checked (Granted) | When Unchecked (Revoked) |
|---|---|---|---|
| **`dashboard.view`** | Dashboard Overview | Dashboard link visible in sidebar; full KPI cards displayed | Hidden from sidebar; redirect if accessed |
| **`analytics.view`** | Analytics | `Analytics` link visible; access to `pages/ai_insights.php` | Link hidden; URL access blocked (redirect to dashboard) |
| **`reports.view`** | Reports | `Reports` link visible; export & report generation enabled | Link hidden; report generation APIs return 403 |
| **`compliance.view`** | Compliance & Violations | `Compliance` link visible; monitoring tables accessible | Link hidden; direct URL access blocked |
| **`patients.view`** | View Patients | `Patient Management`, `Medical Records`, `Appointments`, `Referrals` visible | Links hidden; `patients.php` direct access blocked |
| **`patients.create`** | Create Patients | `+ Register Patient` button shown in `patients.php` | `+ Register Patient` button hidden; POST API returns 403 |
| **`patients.edit`** | Edit Patients | `Edit` button (pencil icon) visible in patient list | Edit buttons hidden; PUT/update API returns 403 |
| **`patients.delete`** | Delete Patients | `Delete` (trash icon) visible; deletion allowed | Delete button hidden; DELETE API returns 403 |
| **`consultations.view`** | View Consultations | `Consultations` link visible in sidebar | Link hidden; `consultations.php` access blocked |
| **`consultations.create`** | Create Consultations | `+ New Consultation` button visible in `consultations.php` | Button hidden; consultation saving API returns 403 |
| **`triage.view`** | View Triage | `Triage` link visible in sidebar | Link hidden; `triage.php` access blocked |
| **`triage.create`** | Create Triage | `+ Triage Intake` button visible | Button hidden; intake save API returns 403 |
| **`prescriptions.view`** | View Prescriptions | `Prescriptions` link visible in sidebar | Link hidden; `prescriptions.php` access blocked |
| **`prescriptions.create`** | Create Prescriptions | `+ Issue Prescription` button visible | Button hidden; prescription save API returns 403 |
| **`permits.view`** | View Permits | `Sanitation Permits`, `Permit Applications`, `Permit Records`, `Payments`, `Documents`, `Renewals` links visible | Module hidden from sidebar; permit pages blocked |
| **`permits.create`** | Create Permits | `+ New Application` button visible | Button hidden; application submit API returns 403 |
| **`permits.approve`** | Approve Permits | `Approve` / `Reject` action buttons visible on pending permits | Approve/Reject buttons hidden; approval API returns 403 |
| **`inspections.view`** | View Inspections | `Inspections` link visible in sidebar | Link hidden; `inspections.php` access blocked |
| **`inspections.conduct`** | Conduct Inspections | `Conduct Inspection` button visible in inspection queues | Button hidden; inspection submit API returns 403 |
| **`immunization.view`** | View Immunization | `Immunization & Nutrition` module and all 5 sub-links visible | Module hidden from sidebar; immunization pages blocked |
| **`immunization.create`** | Create Immunization | `+ Add Child`, `+ Record Vaccination`, `+ Add Vaccine Stock` buttons visible | Buttons hidden; creation APIs return 403 |
| **`immunization.edit`** | Edit Immunization | `Edit` child / vaccine stock buttons visible | Edit buttons hidden; update APIs return 403 |
| **`wastewater.view`** | View Wastewater | `Wastewater Services` module and sub-links visible | Module hidden from sidebar; wastewater pages blocked |
| **`wastewater.create`** | Create Wastewater | `+ Register Tank`, `+ Register Provider`, `+ New Request` buttons visible | Buttons hidden; registration APIs return 403 |
| **`wastewater.edit`** | Edit Wastewater | `Edit` buttons on tanks/providers visible | Edit buttons hidden; update APIs return 403 |
| **`wastewater.manage`** | Manage Wastewater | `Assign Provider`, `Equipment Management`, `Generate Quotation` buttons visible | Action buttons hidden; assignment APIs return 403 |
| **`surveillance.view`** | View Surveillance | `Health Surveillance` module (`Case Reports`, `Mapping`, `Outbreak Command`) visible | Module hidden from sidebar; surveillance pages blocked |
| **`surveillance.create`** | Create Surveillance | `+ New Case Report` button visible | Button hidden; case creation API returns 403 |
| **`surveillance.edit`** | Edit Surveillance | `Edit Case` buttons visible | Edit buttons hidden; update API returns 403 |
| **`surveillance.manage`** | Manage Surveillance | `Declare Outbreak`, `Escalate Alert`, `Containment Protocol` controls active | Control buttons hidden; command APIs return 403 |
| **`users.view`** | View Users | `User Management` link visible in sidebar under System Management | Link hidden; `user_management.php` access blocked |
| **`users.create`** | Create Users | `+ Register Employee` button visible | Button hidden; employee creation API returns 403 |
| **`users.edit`** | Edit Users | `Edit Role`, `Set Status` buttons visible in user list | Edit/status buttons hidden; update API returns 403 |
| **`users.delete`** | Delete Users | `Delete` user button visible (Admin only) | Button hidden; delete API returns 403 |
| **`roles.manage`** | Manage Roles & Permissions | `🔑 Manage Permissions` button active; can save role permission matrices | Button disabled/hidden; `update_role_permissions` API returns 403 |
| **`settings.manage`** | Manage Settings | `Settings` link visible in sidebar; access to `settings.php` | Link hidden; `settings.php` access blocked |
| **`logs.view`** | View System Logs | `System Logs` link visible in sidebar; access to `system_logs.php` | Link hidden; `system_logs.php` access blocked |

---

## 3. Implementation Workflow

```
                        User Management (Admin/Director)
                                     │
                    [ Toggles Permission Checkbox ]
                                     │
                         [ Click Save Permissions ]
                                     │
                    ┌────────────────┴────────────────┐
                    ▼                                 ▼
         Database Update                    Session Invalidation
   (role_permissions table)         (PermissionService::invalidateCache())
                    │                                 │
                    └────────────────┬────────────────┘
                                     ▼
                      On Next User Action / Page Load
                                     │
       ┌─────────────────────────────┼─────────────────────────────┐
       ▼                             ▼                             ▼
[ Sidebar / Nav ]           [ Page UI Buttons ]          [ API & Backend Routes ]
NavigationService::         hasPermission('slug')        AuthorizationMiddleware::
getFilteredNavigation()     Conditional HTML rendering   authorize('slug')
Hides/Shows links           Hides/Shows action buttons   Blocks with 403 Forbidden
```

---

## 4. Proposed Code Changes by Layer

### Layer 1 — Core Model & Cache Invalidation

#### [MODIFY] [`app/Models/Role.php`](file:///opt/lampp/htdocs/capstone/app/Models/Role.php)
- Add static cache reset helper `Role::clearCache()` inside `syncPermissions()` so fresh role-permission relationships are read immediately without waiting for cache timeout.

```php
public static function clearCache(): void
{
    self::$cachedRoles = null;
    self::$cacheTime = null;
}
```

---

### Layer 2 — Page View Protection (`requirePermission`)

Add view permission checks at the top of page headers so unauthenticated direct URL access is halted:

- **Health Center**:
  - [`modules/healthservices/consultations.php`](file:///opt/lampp/htdocs/capstone/modules/healthservices/consultations.php): `requirePermission('consultations.view');`
  - [`modules/healthservices/triage.php`](file:///opt/lampp/htdocs/capstone/modules/healthservices/triage.php): `requirePermission('triage.view');`
  - [`modules/healthservices/prescriptions.php`](file:///opt/lampp/htdocs/capstone/modules/healthservices/prescriptions.php): `requirePermission('prescriptions.view');`
  - [`modules/healthservices/appointments.php`](file:///opt/lampp/htdocs/capstone/modules/healthservices/appointments.php): `requirePermission('patients.view');`
  - [`modules/healthservices/medical_records.php`](file:///opt/lampp/htdocs/capstone/modules/healthservices/medical_records.php): `requirePermission('patients.view');`
- **Sanitation Permits**:
  - [`modules/sanitation/permit_applications.php`](file:///opt/lampp/htdocs/capstone/modules/sanitation/permit_applications.php): `requirePermission('permits.view');`
  - [`modules/sanitation/inspections.php`](file:///opt/lampp/htdocs/capstone/modules/sanitation/inspections.php): `requirePermission('inspections.view');`
  - [`modules/sanitation/permit_records.php`](file:///opt/lampp/htdocs/capstone/modules/sanitation/permit_records.php): `requirePermission('permits.view');`
  - [`modules/sanitation/payments.php`](file:///opt/lampp/htdocs/capstone/modules/sanitation/payments.php): `requirePermission('permits.view');`
- **Immunization & Nutrition**:
  - [`modules/immunization/child_records.php`](file:///opt/lampp/htdocs/capstone/modules/immunization/child_records.php): `requirePermission('immunization.view');`
  - [`modules/immunization/vaccination_tracking.php`](file:///opt/lampp/htdocs/capstone/modules/immunization/vaccination_tracking.php): `requirePermission('immunization.view');`
  - [`modules/immunization/vaccine_inventory.php`](file:///opt/lampp/htdocs/capstone/modules/immunization/vaccine_inventory.php): `requirePermission('immunization.view');`
  - [`modules/immunization/nutrition_assessment.php`](file:///opt/lampp/htdocs/capstone/modules/immunization/nutrition_assessment.php): `requirePermission('immunization.view');`
- **Wastewater Services**:
  - [`modules/services/providers.php`](file:///opt/lampp/htdocs/capstone/modules/services/providers.php): `requirePermission('wastewater.view');`
  - [`modules/services/septic_tanks.php`](file:///opt/lampp/htdocs/capstone/modules/services/septic_tanks.php): `requirePermission('wastewater.view');`
  - [`modules/services/wastewater_billing.php`](file:///opt/lampp/htdocs/capstone/modules/services/wastewater_billing.php): `requirePermission('wastewater.view');`
  - [`modules/services/service_requests.php`](file:///opt/lampp/htdocs/capstone/modules/services/service_requests.php): `requirePermission('wastewater.view');`
- **Health Surveillance**:
  - [`modules/surveillence/case_reports.php`](file:///opt/lampp/htdocs/capstone/modules/surveillence/case_reports.php): `requirePermission('surveillance.view');`
  - [`modules/surveillence/mapping.php`](file:///opt/lampp/htdocs/capstone/modules/surveillence/mapping.php): `requirePermission('surveillance.view');`
  - [`modules/surveillence/outbreak_command.php`](file:///opt/lampp/htdocs/capstone/modules/surveillence/outbreak_command.php): `requirePermission('surveillance.view');`

---

### Layer 3 — UI Action Buttons Protection (`hasPermission`)

Wrap operational action buttons in `<?php if (hasPermission('...')): ?>`:

1. **`consultations.php`**:
   ```php
   <?php if (hasPermission('consultations.create')): ?>
       <button onclick="openModal('newConsultationModal')">... New Consultation</button>
   <?php endif; ?>
   ```

2. **`prescriptions.php`**:
   ```php
   <?php if (hasPermission('prescriptions.create')): ?>
       <button onclick="openModal('newPrescriptionModal')">... Issue Prescription</button>
   <?php endif; ?>
   ```

3. **`triage.php`**:
   ```php
   <?php if (hasPermission('triage.create')): ?>
       <button onclick="openModal('triageModal')">... Triage Intake</button>
   <?php endif; ?>
   ```

4. **`permit_applications.php`**:
   ```php
   <?php if (hasPermission('permits.create')): ?>
       <button onclick="openModal('newApplicationModal')">... New Application</button>
   <?php endif; ?>
   <?php if (hasPermission('permits.approve')): ?>
       <button onclick="approvePermit(id)">Approve</button>
       <button onclick="rejectPermit(id)">Reject</button>
   <?php endif; ?>
   ```

5. **`inspections.php`**:
   ```php
   <?php if (hasPermission('inspections.conduct')): ?>
       <button onclick="openModal('conductInspectionModal')">Conduct Inspection</button>
   <?php endif; ?>
   ```

6. **`child_records.php` & `vaccination_tracking.php`**:
   ```php
   <?php if (hasPermission('immunization.create')): ?>
       <button onclick="openModal('registerChildModal')">... Register Child</button>
   <?php endif; ?>
   <?php if (hasPermission('immunization.edit')): ?>
       <button onclick="editChild(id)">Edit</button>
   <?php endif; ?>
   ```

7. **`providers.php` & `wastewater_billing.php`**:
   ```php
   <?php if (hasPermission('wastewater.create')): ?>
       <button onclick="openModal('registerProviderModal')">... Register Provider</button>
   <?php endif; ?>
   <?php if (hasPermission('wastewater.manage')): ?>
       <button onclick="assignProvider(id)">Assign Provider</button>
       <button onclick="openModal('quotationModal')">Generate Quotation</button>
   <?php endif; ?>
   ```

8. **`user_management.php`**:
   ```php
   <?php if (hasPermission('users.create')): ?>
       <button onclick="openModal('registerUserModal')">... Register Employee</button>
   <?php endif; ?>
   <?php if (hasPermission('users.edit')): ?>
       <button onclick="editUser(id)">Edit</button>
       <button onclick="setUserStatus(id)">Status</button>
   <?php endif; ?>
   <?php if (hasPermission('roles.manage')): ?>
       <button onclick="managePermissions(id)">Permissions</button>
   <?php endif; ?>
   <?php if (hasPermission('users.delete') && $isSystemAdmin): ?>
       <button onclick="deleteUser(id)">Delete</button>
   <?php endif; ?>
   ```

---

### Layer 4 — Backend API Route Authorization

In all AJAX/API handlers (`user_management_api.php`, `api/service_requests.php`, `api/immunization.php`, `api/inspections.php`, `api/consultations.php`):
- Call `AuthorizationMiddleware::authorize('specific.action.permission')` at the beginning of each POST/PUT/DELETE handler.
- If unpermitted, an HTTP 403 JSON error is returned immediately and logged into the `activity_logs` table as an `Unauthorized Access Attempt`.

---

## 5. Verification & Testing Checklist

- [ ] **Test Role Permissions Customization**:
  1. Log in as System Administrator.
  2. Open User Management → Click `Manage Permissions` for a `Nurse` role.
  3. Uncheck `prescriptions.create` and save.
  4. Log in as that Nurse → Navigate to Prescriptions page → verify `+ Issue Prescription` button is gone.
  5. Attempt to POST directly to prescription API → verify HTTP 403 Forbidden is returned.
- [ ] **Test Sidebar Auto-filtering**:
  1. Uncheck `permits.view` for a staff role.
  2. Log in as that staff → verify `Sanitation Permits` is completely hidden from the sidebar.
- [ ] **Test Realtime Permission Cache Reset**:
  1. Toggle permissions → verify changes reflect on the next request without needing server or PHP restart.

---

*Plan written: August 28, 2026*
