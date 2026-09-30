# 🧪 Manual UI Verification & Testing Guide for Local Changes

This document provides a comprehensive, file-by-file breakdown of all modified files in your local workspace. For each file, it explains **what was changed**, **why it matters**, and **exact step-by-step instructions to verify it manually in the web UI**, including which user role to use, what data to enter, and what expected outcome to look for.

---

## 📋 Quick Navigation by Module

- [1. Sanitation Permits & Payments](#1-sanitation-permits--payments)
  - [`PermitController.php`](#appcontrollerspermitcontrollerphp)
  - [`PaymentController.php`](#appcontrollerspaymentcontrollerphp)
  - [`permit_applications.php`](#modulessanitationpermit_applicationsphp)
  - [`documents.php`, `permit_certificate.php`, `verify_permit.php`](#modulessanitationdocumentsphp-permit_certificatephp-verify_permitphp)
- [2. Health Center Services](#2-health-center-services)
  - [`AppointmentController.php` & `Appointment.php`](#appcontrollersappointmentcontrollerphp--appmodelsappointmentphp)
  - [`TriageController.php`](#appcontrollerstriagecontrollerphp)
  - [`ConsultationController.php`](#appcontrollersconsultationcontrollerphp)
  - [`PatientController.php`](#appcontrollerspatientcontrollerphp)
  - [`modules/healthservices/appointments.php`](#moduleshealthservicesappointmentsphp)
- [3. Immunization & Nutrition](#3-immunization--nutrition)
  - [`ChildController.php` & `Child.php`](#appcontrollerschildcontrollerphp--appmodelschildphp)
  - [`api/immunization.php`](#apiimmunizationphp)
  - [`child_records.php`](#modulesimmunizationchild_recordsphp)
  - [`vaccination_tracking.php`](#modulesimmunizationvaccination_trackingphp)
- [4. Wastewater Services](#4-wastewater-services)
  - [`ServiceRequestController.php`](#appcontrollersservicerequestcontrollerphp)
  - [`WastewaterInvoiceController.php` & `WastewaterInvoice.php`](#appcontrollerswastewaterinvoicecontrollerphp--appmodelswastewaterinvoicephp)
- [5. Health Surveillance](#5-health-surveillance)
  - [`SurveillanceCase.php`](#appmodelssurveillancecasephp)
  - [`case_reports.php`](#modulessurveillencecase_reportsphp)
- [6. Core Infrastructure, Security & RBAC](#6-core-infrastructure-security--rbac)
  - [`BaseController.php`](#corebasecontrollerphp)
  - [`AuthorizationMiddleware.php`](#appmiddlewareauthorizationmiddlewarephp)
  - [`DepartmentResolver.php`](#appservicesdepartmentresolverphp)
  - [`PermissionService.php`](#appservicespermissionservicephp)
  - [`api/consent.php`](#apiconsentphp)

---

## 1. Sanitation Permits & Payments

### `app/Controllers/PermitController.php`
- **What Changed**:
  1. Updated mobile phone regex validation from a rigid 12-digit format (`/^\d{12}$/`) to standard Philippine mobile formats (`/^(09\d{9}|639\d{9})$/`).
  2. Enforced fee payment verification check on permit approval: permits with pending/unpaid fees cannot be approved unless an override flag (`bypass_payment_verification` or `fee_waived`) is supplied.
  3. Fixed payment status check to accept **both** `'paid'` and `'completed'` payment statuses (which Treasury sets).
  4. Automatically populates `approved_date` and calculates `expiry_date` (+365 days) on approval.
- **Manual UI Verification**:
  1. **Login as**: `Sanitation Director` or `System Administrator`
  2. **Navigate to**: *Sanitation > Permit Applications* (`modules/sanitation/permit_applications.php`)
  3. **Test 1 (Mobile Validation)**:
     - Click **"+ New Permit Application"**.
     - In the Contact Number field, type `09171234567` (or `639171234567`). Fill the required fields and submit.
     - **Expected Result**: Successfully submits without contact errors.
     - Try with `12345` or `08123456789`.
     - **Expected Result**: Toast error: *"Contact number must be a valid Philippine mobile number (e.g. 09171234567 or 639171234567)"*.
  4. **Test 2 (Unpaid Approval Guard)**:
     - Find an application that has an unpaid fee.
     - In the action menu, click **"Review"** or change status to **"Approved"**.
     - **Expected Result**: Error alert stating *"Cannot approve permit: Sanitation permit fees are unpaid. Please complete fee payment before granting approval."*

---

### `app/Controllers/PaymentController.php`
- **What Changed**:
  1. Updated authorization on `update` and `complete` actions to require `permits.create` instead of `permits.approve`. This empowers Cashiers to process and complete payments without needing Director-level approval powers.
  2. In `complete()`, synchronized the permit's `approved_date` and `expiry_date` dynamically based on system settings.
- **Manual UI Verification**:
  1. **Login as**: `Cashier`
  2. **Navigate to**: *Sanitation > Payments & Treasury* (`modules/sanitation/payments.php`)
  3. **Steps**:
     - Locate a payment with status **Pending**.
     - Click the action button **"Complete Payment"** / **"Verify & Complete"**.
     - Enter reference/OR number and confirm.
  4. **Expected Result**:
     - Payment status turns to **Completed** (green badge).
     - Cashier is NOT denied with 403 Forbidden.
     - The associated permit record automatically updates to **Approved** with validity dates populated.

---

### `modules/sanitation/permit_applications.php`
- **What Changed**:
  1. Aligned client-side JavaScript form validation with Philippine phone numbers (`/^(09\d{9}|639\d{9})$/`).
  2. Integrated Realtime CDC Broadcast sync (`setupRealtimePermitAppSync`) and broadcast hooks (`window.broadcastSanitationChange`) to automatically refresh stats and tables when other users update permits, without closing active modals.
- **Manual UI Verification**:
  1. Open two browser windows side by side (e.g., normal window and incognito window).
  2. In Window 1, log in as `Sanitation Director` and open `permit_applications.php`.
  3. In Window 2, log in as `Permit Clerk` and submit a new permit application.
  4. **Expected Result**:
     - Window 1 automatically refreshes the table and summary KPI counters via live broadcast without manual browser reload.
     - If you have an edit modal open in Window 1, the modal stays open without flickering.

---

### `modules/sanitation/documents.php`, `permit_certificate.php`, `verify_permit.php`
- **What Changed**:
  - Normalized `new Permit()` instantiation to remove redundant `$db` parameters that caused constructor signature mismatches.
- **Manual UI Verification**:
  1. **Navigate to**: *Sanitation > Documents & Certificates* (`modules/sanitation/documents.php`).
  2. Click **"View Certificate"** or scan/visit a permit QR code link (`modules/sanitation/verify_permit.php?code=...`).
  3. **Expected Result**: Page loads cleanly with certificate preview and verification details; no PHP fatal errors or blank white screen.

---

## 2. Health Center Services

### `app/Controllers/AppointmentController.php` & `app/Models/Appointment.php`
- **What Changed**:
  1. Replaced raw database count scans with an optimized `countByDate()` model method for daily quota enforcement.
  2. Replaced unsafe raw SQL `UPDATE ... WHERE patient_id = ? OR id = ?` with structured model calls (`updateById` and `getByPatientId`) during doctor reassignment cascades (`reassignment_pending`, `sent_to_doctor`).
- **Manual UI Verification**:
  1. **Login as**: `Appointment Clerk` or `Nurse`
  2. **Navigate to**: *Health Center > Appointments* (`modules/healthservices/appointments.php`)
  3. **Steps**:
     - Select a patient and schedule an appointment for today.
     - Click **"Reassign Doctor"** on a waiting patient.
     - Choose a different doctor and submit reassignment.
  4. **Expected Result**:
     - Appointment moves to `sent_to_other_doctor` / `sent_to_doctor`.
     - Patient triage record updates with the new doctor name without database syntax errors.

---

### `app/Controllers/TriageController.php`
- **What Changed**:
  - Expanded status whitelist to include `reassignment_pending` and `sent_to_doctor`.
- **Manual UI Verification**:
  1. **Login as**: `Nurse`
  2. **Navigate to**: *Health Center > Triage & Patient Assessment* (`modules/healthservices/triage.php`)
  3. Check the patient waiting queue.
  4. **Expected Result**: When patients are flagged for doctor reassignment, their status displays properly and status transitions proceed without *"Invalid status value provided"* errors.

---

### `app/Controllers/ConsultationController.php`
- **What Changed**:
  - Fixed triage auto-resolution to query by specific patient ID (`getByPatientId`) instead of generic unbounded queries.
- **Manual UI Verification**:
  1. **Login as**: `Doctor`
  2. **Navigate to**: *Health Center > Consultations* (`modules/healthservices/consultations.php`)
  3. **Steps**:
     - Pick a patient who has an active triage assessment waiting in queue.
     - Enter consultation notes, diagnosis, and treatment plan. Save consultation.
  4. **Expected Result**:
     - Consultation records successfully.
     - Go to *Triage Queue* (`modules/healthservices/triage.php`); the patient's queue status is automatically updated to **Consulted** / resolved.

---

### `app/Controllers/PatientController.php`
- **What Changed**:
  - Contact number validation updated to support Philippine formats (`09XXXXXXXXX` or `639XXXXXXXXX`).
- **Manual UI Verification**:
  1. **Login as**: `Medical Records Clerk` or `Nurse`
  2. **Navigate to**: *Health Center > Patient Management* (`modules/healthservices/patients.php`)
  3. Click **"+ Register New Patient"**.
  4. Enter `09181234567` in the contact field and save.
  5. **Expected Result**: Profile is created successfully. Entering an invalid format (like `12345`) triggers a validation error.

---

### `modules/healthservices/appointments.php`
- **What Changed**:
  - Initialized `$rawConsultations` to prevent PHP notice/warning when querying consultations.
- **Manual UI Verification**:
  1. Navigate to `modules/healthservices/appointments.php`.
  2. Check PHP error log / browser output.
  3. **Expected Result**: Clean page rendering without `Undefined variable $rawConsultations` notices.

---

## 3. Immunization & Nutrition

### `app/Controllers/ChildController.php` & `app/Models/Child.php`
- **What Changed**:
  1. `ChildController` now extends `BaseController` with strict CSRF validation (`validateCsrf()`), department authorization (`requireDepartment('immunization & nutrition')`), and granular RBAC checks.
  2. Added `findWithVaccinations()` method on `Child` model to retrieve child demographic details and vaccine dose history in one query.
  3. Added activity logging for child record creation, update, and deletion.
- **Manual UI Verification**:
  1. **Login as**: `Immunization Coordinator`
  2. **Navigate to**: *Immunization > Child Records* (`modules/immunization/child_records.php`)
  3. Click **"View Profile"** on any child.
  4. **Expected Result**: Modal displays the child's complete profile along with all previously administered vaccines.

---

### `api/immunization.php`
- **What Changed**:
  1. Implemented anti-cheat algorithm in `calculateVaccineCompliance()`: compliance evaluates distinct EPI target antigens (BCG, HepB, Pentavalent 1-3, OPV 1-3, IPV 1-2, PCV 1-3, MMR). Duplicate dose submissions for the same antigen cannot artificially inflate score to 100%.
  2. Added CSRF token verification and department authorization to `POST`, `PUT`, `DELETE` routes.
- **Manual UI Verification**:
  1. **Login as**: `Midwife` or `Immunization Coordinator`
  2. **Navigate to**: *Immunization > Vaccination Tracking* (`modules/immunization/vaccination_tracking.php`)
  3. Record a dose (e.g. Pentavalent Dose 1) for a child.
  4. **Expected Result**:
     - Vaccine dose is recorded.
     - The child's compliance percentage recalculates based on actual scheduled antigens received.

---

### `modules/immunization/child_records.php`
- **What Changed**:
  - Added CSRF header (`X-CSRF-Token`) and payload injection to all async `fetch()` requests (`action=record`, update, archive/delete).
- **Manual UI Verification**:
  1. In `child_records.php`, edit a child's address or record a vaccination directly from the modal.
  2. Inspect the browser Network tab (`F12`).
  3. **Expected Result**: Request header includes `X-CSRF-Token` and response returns `200/201 OK`, never `403 Invalid CSRF token`.

---

### `modules/immunization/vaccination_tracking.php`
- **What Changed**:
  - Retained administered records while adding pending and missed vaccine doses to the child tracking schedule so upcoming and overdue vaccinations are visible.
- **Manual UI Verification**:
  1. Open `modules/immunization/vaccination_tracking.php`.
  2. Inspect the immunization timetable for an enrolled infant.
  3. **Expected Result**: Both completed doses and upcoming/missed due dates are visible with status tags (`completed`, `pending`, `missed`).

---

## 4. Wastewater Services

### `app/Controllers/ServiceRequestController.php`
- **What Changed**:
  1. **Deletion Safeguards**: Requests that are `scheduled`, `in_progress`, or `completed` cannot be deleted (HTTP 422). Only `pending` or `cancelled` requests are eligible for deletion.
  2. **Billing Safeguard**: Requests with attached invoices (`pending`, `overdue`, `paid`) cannot be deleted until the invoices are resolved.
  3. **Status Cascade**: Marking a request as `completed` automatically marks the linked maintenance record as completed and updates the septic tank's status to `good` with today's `last_maintenance` date.
- **Manual UI Verification**:
  1. **Login as**: `Wastewater Officer`
  2. **Navigate to**: *Wastewater Services > Service Requests* (`modules/services/service_requests.php`)
  3. **Test Deletion Block**:
     - Find a request that is **In Progress** or has an issued invoice.
     - Attempt to delete it.
     - **Expected Result**: Alert: *"Cannot delete service request in 'in_progress' status... must be cancelled first or retained for auditing."*
  4. **Test Cascade Completion**:
     - Change a service request status to **Completed**.
     - Open *Septic Tanks Registry* (`modules/services/septic_tanks.php`).
     - **Expected Result**: The corresponding septic tank now displays status **Good** and `last_maintenance` reflects today's date.

---

### `app/Controllers/WastewaterInvoiceController.php` & `app/Models/WastewaterInvoice.php`
- **What Changed**:
  - Added duplicate invoice protection: checks for active/unpaid invoices by `service_request_id` as well as `tank_id`.
- **Manual UI Verification**:
  1. **Login as**: `Wastewater Officer`
  2. **Navigate to**: *Wastewater Services > Billing & Invoices* (`modules/services/wastewater_billing.php`)
  3. Create an invoice for a specific Service Request.
  4. Attempt to create a second invoice for the exact same Service Request without checking *"Allow Duplicate"*.
  5. **Expected Result**: Form rejects with HTTP 409: *"An active unpaid invoice already exists for Service Request..."*

---

## 5. Health Surveillance

### `app/Models/SurveillanceCase.php` & `modules/surveillence/case_reports.php`
- **What Changed**:
  1. Implemented monotonic sequential case code generator `generateCaseCode()` (`CS-YYYY-XXX`). Parses existing codes to guarantee monotonic incrementing without collisions even after case records are deleted.
  2. Enforced `requireDepartmentAccess('health surveillance')` before the AJAX POST submission handler.
- **Manual UI Verification**:
  1. **Login as**: `Surveillance Officer`
  2. **Navigate to**: *Health Surveillance > Case Reports* (`modules/surveillence/case_reports.php`)
  3. Click **"+ Report New Case"**. Enter disease (e.g. Dengue), patient details, and submit.
  4. **Expected Result**:
     - Case code is generated following the sequence (e.g. `CS-2026-001`, `CS-2026-002`).
     - If you attempt to access the URL or submit a case while logged in as a `Cashier` or `Permit Clerk`, access is denied.

---

## 6. Core Infrastructure, Security & RBAC

### `Core/BaseController.php` & `app/Middleware/AuthorizationMiddleware.php`
- **What Changed**:
  - Added session guards (`PHP_SAPI !== 'cli' && !headers_sent()`) and CLI-safe CSRF bypass to enable reliable automated testing while ensuring browser sessions remain strictly protected.

### `app/services/DepartmentResolver.php`
- **What Changed**:
  - Normalized `'health_center'` slug mapping to `'health center services'`.

### `app/services/PermissionService.php`
- **What Changed**:
  1. Guaranteed Cashier has `permits.create` for payment processing.
  2. Guaranteed Department Heads have user management capabilities for their department staff.
  3. Implemented robust session cache invalidation (`invalidateCache()`).

### `api/consent.php`
- **What Changed**:
  - Moved `declare(strict_types=1);` to line 2 right after `<?php` to fix PHP fatal parsing error.
- **Manual UI Verification**:
  1. Open browser and visit: `http://localhost/capstone/api/consent.php`
  2. **Expected Result**: Returns JSON response (e.g., status/intake required or method not allowed); **no fatal PHP error**.

---

## 7. Toast Notification System Consistency

### `includes/toast.php`, `assets/js/modal-system.js`, `assets/js/common.js`
- **What Changed**:
  1. `includes/footer.php` automatically loads `includes/toast.php` across all module and page views via `include_once`.
  2. `ModalSystem.toast` and `common.js`'s `showToast()` bridge directly to `window.toast`, ensuring animated, glassmorphic toast notifications with progress countdowns and maximum 3 active cards throughout the portal.
  3. Added `footer.php` include to `modules/services/services_management.php` and `toast.php` include to `modules/sanitation/verify_permit.php`.
- **Manual UI Verification**:
  1. In any module (e.g. Health Center, Sanitation, Wastewater, or Immunization), trigger any action (save, update, delete, or validation error).
  2. **Expected Result**: Toast notification animates from the top-right corner with proper color (emerald for success, red for error, amber for warning, blue for info), an icon pop animation, a smooth progress timer, and an audible/screen-reader live region (`aria-live="polite"`).

---

## 8. Automated Verification Commands

To verify all changes programmatically via terminal, run:

```bash
# 1. Run the comprehensive 19-role RBAC & workflow test suite
php scratch/test_roles_workflows.php

# 2. Run the deep end-to-end integration and cascade test engine
php scratch/test_heavy_all_workflows.php

# 3. Verify zero PHP syntax errors across all modified controllers
php -l Core/BaseController.php app/Controllers/PermitController.php app/Controllers/PaymentController.php api/consent.php
```
