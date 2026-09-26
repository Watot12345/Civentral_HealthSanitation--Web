# Bug Fix Plan — 7 Issues in Civentral

## Status: In Progress

---

## Bug 1: Service Worker (`sw.js`) — ✅ FIXED

### What was done
- Removed all PHP pages from the pre-cache list — now caches **only** static assets (CSS, JS, manifest, offline.html)
- Removed `ignoreSearch: true` that was serving wrong data for query-parameterized requests
- Added skip logic for `_api.php` GET endpoints
- PHP pages now go network-first, falling back to `offline.html` when offline
- Bumped cache version to `v5`

---

## Bug 2: Assigned Attending Doctor (Locked) — ✅ FIXED

### What was done
- Changed `lockDoctorSelect()` calls in the **add** form from hardcoded `true` to `!IS_ADMIN`
- Admin still gets auto-selected doctor for convenience, but can now change it
- Both the initial DOMContentLoaded lock and the appointment/triage pre-fill lock are fixed

---

## Bug 5: Formula Analytics Predictive — ✅ FIXED

### What was done
- Replaced hardcoded confidence values (88, 91, 93) with **data-driven confidence** based on R² fit, data quantity, and coefficient of variation
- Replaced hardcoded R² values (0.85, 0.88, 0.90) with **actual OLS R²** computed from historical data
- Fixed zero-data edge case: now returns confidence=50, R²=0.0 instead of fake 88/0.85
- Raised ceiling clamp from 1.6x to 2.5x to allow genuine outbreak detection
- Confidence is now bounded to 30-99% (never negative, never fake 100%)

---

## Bug 3 & 4: Real-time Operations / No Ctrl+R to Refresh — ⏳ PENDING

### Status
Needs user decision on approach and module priority.

### Options
| Option | Description | Effort |
|--------|-------------|--------|
| **A** — Targeted DOM updates | After add/edit/delete, insert/update/remove the specific table row via JS (no reload) | Medium per module |
| **B** — Polling auto-refresh | Add a timer that re-fetches data every 30-60 seconds | Low, but adds server load |
| **C** — Full real-time (WebSocket/SSE) | Server pushes updates to all clients | Major architectural change |

> [!IMPORTANT]
> **Questions:**
> 1. Which option do you want? I recommend **Option A** (dynamic DOM updates after CRUD).
> 2. Which modules are **highest priority**? Or should I do all of them? I suggest starting with: `patients.php`, `consultations.php`, `appointments.php`.

---

## Bug 6: Staff Performance Visibility & Scope — ✅ FIXED

### Requirement applied
- **System Admin** → sees the Staff Performance of **everyone, including department heads & coordinators**.
- **Department Head / Coordinator** → sees **only the staff under them** (their own department's field staff; leadership peers excluded).

### What was done
| # | File | Change |
|---|------|--------|
| 1 | `app/services/PermissionService.php` | `isHeadOrAdminRole()` now matches **`coordinator`**. Previously `Immunization Coordinator` / `Surveillance Coordinator` were blocked from the panel (only `... Lead`, `director`, `head`, `chief`, `manager`, `supervisor`, `oic` matched) — inconsistent with `dashboard.php`, which already treated `coordinator` as a head role. |
| 2 | `app/services/AiAnalyticsService.php` | `getStaffPerformance()` now: (a) detects leadership from **both** `role` and `role_description` (catches coordinators whose role column reads `... Lead`); (b) only excludes leadership when the viewer is **not** an admin — so admins see heads/coordinators; (c) excludes `System Admin` accounts from the ranking entirely; (d) returns new fields `position` and `is_leadership`. |
| 3 | `pages/ai_insights.php` | Added `$isAdminView`; panel badge is now dynamic (`All Departments` vs `Department View`); description text explains the scope; admins get a colour legend. |
| 4 | `assets/js/ai-insights-app.js` | Bars are colour-coded via `distributed` (amber = head/coordinator, indigo = field staff); leadership rows are prefixed with `★`; tooltip now shows **Role** + `Head/Coordinator`; `updateStaffData()` keeps the scope label in the count badge. |
| 5 | `sw.js` | Cache version bumped `v5` → `v6` so clients pick up the updated JS immediately (script is served stale-while-revalidate). |

### Validation performed
- `php -l` clean on `PermissionService.php`, `AiAnalyticsService.php`, `pages/ai_insights.php`; `node --check` clean on `ai-insights-app.js`.
- Reflection test on `getStaffPerformance()` with seeded employee data:
  - admin scope → all 12 departmental records returned, heads/coordinators flagged `is_leadership=true`, `System Admin` excluded.
  - `health_center` scope → only the 3 Health Center field staff; `Health Center Director` and other departments excluded.
  - `surveillance` / `immunization` scopes → leadership excluded, nutrition staff correctly included in immunization scope.
- Role-matrix test on `isHeadOrAdminRole()`: all 8 head/coordinator titles return `true`; Practitioner / Staff / Inspector / Midwife return `false`.

---

## Bug 7: Add Patient Real-time Adding — ⏳ PENDING

Same scope as Bug #3/4 — will be addressed together once you pick an approach.

---

## Remaining Open Questions

1. **Bug 3/4/7:** Which option (A/B/C)? Which modules first?


Implementation Plan: Project-Wide Real-Time AJAX/JSON CRUD Conversion
Antigravity Architecture & Security Strategy
Target Stack: PHP 8.x MVC-Lite • Supabase / PostgreSQL (PostgREST API) • Tailwind CSS / Vanilla JS

1. Inventory of Modules & Endpoints Currently Triggering Page Reloads
Across the codebase, state mutations (Create, Update, Delete) are typically submitted via forms or fetch() calls that end with explicit page reloads (location.reload(), setTimeout(() => window.location.reload(), ...), or full redirects). Below is the comprehensive inventory across all 6 functional domains:

A. Health Center Services
View / Page	Underlying API / Endpoint	Controller & Model	Reload / Full Submit Trigger


triage.php


api/triage.php
, 

api/triage-queue.php


TriageController
, 

TriageQueueController
saveTriage(), updateTriage(), completeTriage(), callNextPatient(), handleCheckIn() call window.location.reload() (lines 1448, 1688, 1713, 1819, 1859).


appointments.php


api/appointments.php


AppointmentController
saveAppointment(), updateAppointmentStatus(), deleteAppointment() call window.location.reload() (lines 1438, 1491, 1578, 1601, 1707).


consultations.php


api/consultations.php


ConsultationController
Form submit and diagnosis / note saving trigger reloads.


patients.php


api/patients.php


PatientController
savePatient(), archivePatient() call window.location.reload() (line 1478).


prescriptions.php


api/prescriptions.php


PrescriptionController
savePrescription(), updateDispenseStatus() reload table.


referrals.php


api/referrals.php


ReferralController
saveReferral(), saveEditedReferral(), status transitions reload.


medical_records.php


api/medical_records.php


MedicalRecordController
saveMedicalRecord() reloads record list.
B. Sanitation & Environmental Health
View / Page	Underlying API / Endpoint	Controller & Model	Reload / Full Submit Trigger


permit_applications.php


api/permits.php


PermitController
saveNewPermit(), assignInspector(), updatePermitStatus() reload views.


inspections.php


api/inspections.php


InspectionController
saveScheduledInspection(), saveConductedInspection() reload list.


payments.php


api/payments.php


PaymentController
savePayment() reloads transaction table.


renewals.php


api/renewals.php


RenewalController
saveRenewalApplication() reloads page (line 1071).


documents.php


api/permit_documents.php


PermitDocumentController
saveUploadedDocument(), deleteDocument() reload list.


permit_records.php


api/permitrecord.php


PermitRecordsController
Record edits / status archiving reload.
C. Urban Wastewater & Maintenance Services
View / Page	Underlying API / Endpoint	Controller & Model	Reload / Full Submit Trigger


septic_tanks.php


api/septic_tanks.php


SepticTankController
saveTankRegistration(), saveTankEdit() reload (lines 892, 936).


service_requests.php


api/service_requests.php


ServiceRequestController
saveNewRequest(), saveStatusUpdate(), saveFeedback() reload (lines 1293, 1348).


wastewater_billing.php


api/wastewater_billing.php


WastewaterInvoiceController
saveQuotation(), savePayment(), saveFeeStructure() reload (lines 1455, 1734, 1829, 1869, 1902, 1968, 2196).


providers.php


api/providers.php


ServiceProviderController
saveProviderRegistration(), saveEquipment() reload (lines 1047, 1100).


maintenance.php


api/maintenance.php


MaintenanceRecordController
saveScheduleService(), saveCompletionReport() reload (lines 1031, 1056, 1330).
D. Disease Surveillance & Epidemiology
View / Page	Underlying API / Endpoint	Controller & Model	Reload / Full Submit Trigger


case_reports.php


modules/surveillence/api/cases.php
Procedural/API router & Database class	saveCaseReport(), updateCase(), submitImportedCases() reload (lines 1188, 1740).


alerts.php


modules/surveillence/api/alerts.php
Procedural/API router & Database class	Alert dismissal, verification, escalation reload.
E. Immunization & Child Health
View / Page	Underlying API / Endpoint	Controller & Model	Reload / Full Submit Trigger


child_records.php


api/patients.php


ChildController
Child registration & parent/guardian updates reload.


vaccination_tracking.php


api/immunization.php
PostgREST / Model	saveVaccinationRecord() reloads (line 1166).


vaccine_inventory.php


api/inventory.php


InventoryController
Stock in, adjustment, wastage reload (lines 1127, 1188).


nutrition_assessment.php


api/nutrition.php


NutritionController
saveAssessment() reloads (line 1153).


growth_charts.php


api/growth.php


GrowthController
Growth milestone logging reloads chart dataset.
F. Administrative, Management & Common Components
View / Page	Underlying API / Endpoint	Controller & Model	Reload / Full Submit Trigger


user_management.php


management/user_management_api.php
, 

api/employees.php


EmployeeController
submitUserForm(), toggle user status, reset password call location.reload() (lines 2312, 2329).


settings.php


api/settings/*.php


SettingsController
, 

BackupController
System setting updates, backup triggers call window.location.reload() (lines 1212, 1297, 1364).


profile.php
Inline / API	

EmployeeController
Profile info update calls window.location.reload() (line 319).


dashboard.php
 (8 Quick Modals)	Respective module APIs	Quick submit handlers	Modal forms trigger page reload after submission.
2. Shared Backend Architecture & Standard Pattern
To avoid repeating custom response shapes, auth checks, and CSRF verifications in each controller, we will standardize at the base framework level: 

Core/Response.php
 and 

Core/BaseController.php
.

A. Extending Response.php
Add standardized CRUD JSON response helpers:

php
// Core/Response.php
public static function crudSuccess(string $action, mixed $record, string $message = '', int $httpCode = 200, array $extra = []): never
{
    self::json(true, $message ?: "Record {$action}d successfully.", array_merge([
        'action' => $action, // 'create' | 'update' | 'delete'
        'record' => $record,
        'id'     => is_array($record) ? ($record['id'] ?? null) : $record,
    ], $extra), $httpCode);
}
public static function validationError(array $errors, string $message = 'Validation failed.'): never
{
    self::json(false, $message, null, 422, ['errors' => $errors]);
}
B. Standardizing BaseController.php
All controllers extending BaseController will inherit unified guards:

validateCsrfToken():
Inspects HTTP_X_CSRF_TOKEN header or csrf_token input field.
Compares with $_SESSION['csrf_token'] using hash_equals().
Halts with Response::error('Invalid or missing CSRF token', 403) if validation fails.
authorizePermission(string $capabilitySlug):
Enforces App\Middleware\AuthorizationMiddleware::authorize($capabilitySlug).
authorizeDepartment(string $department):
Enforces App\Middleware\AuthorizationMiddleware::authorizeDepartment($department).
Standard CRUD Payload Normalization:
Every create or update action in the controller returns the fully enriched record (e.g. joined patient names, computed queue numbers, formatted dates) so the frontend receives everything needed to insert/replace the DOM row immediately without making a secondary GET request.
3. Frontend Architecture: Reusable crud-ajax.js Utility
Instead of embedding repetitive fetch, error handling, and DOM parsing in 30+ PHP pages, we introduce a single unified module: 

assets/js/crud-ajax.js
.

Core Components of crud-ajax.js
javascript
/**
 * assets/js/crud-ajax.js
 * Universal AJAX CRUD Engine for Civentral
 */
const CrudAjax = {
    // 1. Safe DOM escape to prevent stored/DOM XSS
    escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    },
    // 2. Form submission with auto CSRF, button spinner, and field validation error bindings
    async submitForm(formElement, options = {}) {
        const {
            url,
            method = 'POST',
            onSuccess,
            onError,
            modalId,
            tableId,
            renderRow
        } = options;
        
        const submitBtn = formElement.querySelector('button[type="submit"]') || formElement.querySelector('.btn-submit');
        const origBtnHtml = submitBtn ? submitBtn.innerHTML : '';
        
        try {
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Saving...';
            }
            const formData = new FormData(formElement);
            // Auto-append CSRF token
            const csrfToken = getCsrfToken();
            if (!formData.has('csrf_token') && csrfToken) {
                formData.append('csrf_token', csrfToken);
            }
            const isJson = options.sendJson ?? false;
            const headers = {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken || ''
            };
            let body = formData;
            if (isJson) {
                headers['Content-Type'] = 'application/json';
                const plainObj = {};
                formData.forEach((val, key) => { plainObj[key] = val; });
                body = JSON.stringify(plainObj);
            }
            const res = await fetch(url, { method, headers, body });
            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.message || 'Operation failed');
            }
            // Success feedback
            if (typeof ModalSystem !== 'undefined' && ModalSystem.toast) {
                ModalSystem.toast.success(data.message || 'Saved successfully');
            }
            if (modalId && typeof closeModal === 'function') closeModal(modalId);
            if (modalId && typeof ModalSystem !== 'undefined' && ModalSystem.close) ModalSystem.close(modalId);
            // Instant DOM Table Update
            if (tableId && renderRow && data.record) {
                CrudAjax.upsertRow(tableId, data.record, renderRow, data.action || 'create');
            }
            if (typeof onSuccess === 'function') onSuccess(data);
            return data;
        } catch (err) {
            if (typeof ModalSystem !== 'undefined' && ModalSystem.toast) {
                ModalSystem.toast.error(err.message || 'Network error');
            }
            if (typeof onError === 'function') onError(err);
            throw err;
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = origBtnHtml;
            }
        }
    },
    // 3. Upsert a table row in-place (updates existing row or prepends new row with visual flash)
    upsertRow(tableBodyId, record, renderRowFn, action = 'create') {
        const tbody = document.getElementById(tableBodyId);
        if (!tbody) return;
        const rowId = record.id;
        let existingRow = tbody.querySelector(`tr[data-id="${rowId}"]`);
        const newRowHtml = renderRowFn(record);
        const tempContainer = document.createElement('tbody');
        tempContainer.innerHTML = newRowHtml.trim();
        const newRowElem = tempContainer.firstElementChild;
        if (!newRowElem) return;
        newRowElem.setAttribute('data-id', rowId);
        if (existingRow) {
            // In-place Update
            tbody.replaceChild(newRowElem, existingRow);
            CrudAjax.flashRow(newRowElem, 'update');
        } else {
            // New Record Prepend
            const emptyState = document.getElementById('emptyState');
            if (emptyState) emptyState.style.display = 'none';
            tbody.insertBefore(newRowElem, tbody.firstElementChild);
            CrudAjax.flashRow(newRowElem, 'create');
        }
        // Apply data masking if enabled in DOM
        if (typeof ModalSystem !== 'undefined' && ModalSystem.applyMaskingToModal) {
            ModalSystem.applyMaskingToModal(newRowElem);
        }
    },
    // 4. Delete a table row with subtle exit animation
    deleteRow(tableBodyId, recordId, options = {}) {
        const tbody = document.getElementById(tableBodyId);
        if (!tbody) return;
        const targetRow = tbody.querySelector(`tr[data-id="${recordId}"]`);
        if (!targetRow) return;
        targetRow.classList.add('transition-all', 'duration-300', 'opacity-0', 'bg-rose-50');
        setTimeout(() => {
            targetRow.remove();
            if (tbody.children.length === 0) {
                const emptyState = document.getElementById('emptyState');
                if (emptyState) emptyState.style.display = 'flex';
            }
            if (options.onDeleted) options.onDeleted();
        }, 300);
    },
    // Subtle highlight flash to signal instant feedback to user
    flashRow(rowElem, actionType) {
        const bgClass = actionType === 'create' ? 'bg-emerald-50' : 'bg-amber-50';
        rowElem.classList.add(bgClass, 'transition-colors', 'duration-700');
        setTimeout(() => {
            rowElem.classList.remove(bgClass);
        }, 1200);
    }
};
4. Phased Migration Order (Low-Risk to High-Complexity)
We begin with modules with minimal side effects and existing AJAX foundations, validate the pattern end-to-end, and progressively migrate the more relationally complex domains.

mermaid
flowchart TD
    Phase0["Phase 0: Infrastructure<br/>BaseController CSRF/Auth + Response::crudSuccess + crud-ajax.js"]
    Phase1["Phase 1: Pilot & Low-Risk<br/>Triage & Appointments"]
    Phase2["Phase 2: Core Clinical Services<br/>Consultations, Patients, Prescriptions, Referrals"]
    Phase3["Phase 3: Environmental & Sanitation<br/>Permits, Inspections, Payments, Renewals"]
    Phase4["Phase 4: Urban Wastewater & Services<br/>Septic Tanks, Service Requests, Billing, Providers"]
    Phase5["Phase 5: Surveillance & Immunization<br/>Case Reports, Alerts, Vaccine Inventory, Child Records"]
    Phase6["Phase 6: Admin, Settings & Dashboard<br/>User Management, Settings, Quick Modals"]
    Phase0 --> Phase1
    Phase1 --> Phase2
    Phase2 --> Phase3
    Phase3 --> Phase4
    Phase4 --> Phase5
    Phase5 --> Phase6
Phase Details:
Phase 0: Foundation (Shared Architecture)

Extend Core/Response.php with crudSuccess(), validationError().
Extend Core/BaseController.php with validateCsrf(), requireCapability().
Create assets/js/crud-ajax.js.
Include crud-ajax.js in includes/header.php.
Phase 1: Low-Risk Pilot Validation (Triage)

Module: Health Services — Triage (

triage.php
)
Why Triage first: Already has renderTriageTable() and pagination fetch logic in place. Eliminating window.location.reload() in saveTriage(), updateTriage(), completeTriage(), callNextPatient() immediately validates row-level replacement, priority pill re-rendering, queue count synchronization, and speech synthesis without page disruption.
Phase 2: Health Services Core

Appointments (

appointments.php
): Status update, reschedule, booking.
Patients (

patients.php
): Register and edit patient records.
Prescriptions (

prescriptions.php
): Dispense status, new prescription.
Referrals (

referrals.php
): Referral dispatch and receiving.
Consultations & Medical Records (

consultations.php
, 

medical_records.php
).
Phase 3: Sanitation & Environmental Health

Permit Applications (

permit_applications.php
): New permit, inspector assignment, status badges.
Inspections (

inspections.php
): Schedule, record checklist findings.
Payments & Renewals (

payments.php
, 

renewals.php
).
Documents (

documents.php
).
Phase 4: Wastewater & Urban Services

Septic Tanks (

septic_tanks.php
): Registration and desludging date updates.
Service Requests (

service_requests.php
): Work order dispatch, status updates.
Wastewater Billing (

wastewater_billing.php
): Quotations, invoices, fee tiers.
Providers & Maintenance (

providers.php
, 

maintenance.php
).
Phase 5: Disease Surveillance & Immunization

Case Reports (

case_reports.php
): Case entry, investigation edits.
Alerts (

alerts.php
): Verification and resolution.
Vaccine Inventory & Tracking (

vaccine_inventory.php
, 

vaccination_tracking.php
).
Nutrition & Growth (

nutrition_assessment.php
, 

growth_charts.php
).
Phase 6: Admin Management & Dashboard

User Management (

user_management.php
): User creation, role changes, deactivation.
System Settings (

settings.php
).
Dashboard Quick Modals (

dashboard.php
): Real-time counter and activity feed bump.
5. Non-Negotiable Checks per Module (Strict Requirements)
During each module conversion, the following 6 rules are mandatory and will be verified prior to marking the module complete:

Prepared Statements Only (Zero Raw SQL Concatenation):
All database reads and writes must pass through Database::getInstance()->select(), insert(), update(), and delete().
PostgREST parameterized queries only. No query interpolation of $_GET, $_POST, or JSON payload values.
CSRF Token Validation on Every State Mutation:
Every POST, PUT, PATCH, and DELETE request must supply the CSRF token via X-CSRF-Token header or csrf_token body parameter.
Server-side check strictly uses hash_equals($_SESSION['csrf_token'] ?? '', $providedCsrf).
Invalid or missing token returns HTTP 403 JSON immediately.
Server-Side Re-Validation of All Request Fields:
Never trust frontend JS or HTML5 form attributes (required, pattern, maxlength).
Controllers must validate types, bounds, enum values, and required fields before sending to the model (e.g. valid priority values, systolic/diastolic ranges, valid dates).
Server-Side Auth & Permission Checks (Inside the Endpoint):
Because API endpoints can be directly hit via curl or Postman, the endpoint/controller itself must enforce RBAC:
Capability slug check: AuthorizationMiddleware::authorize(Permissions::XYZ)
Department scope check: getUserScope() and AuthorizationMiddleware::authorizeDepartment(...)
Unauthenticated requests return HTTP 401; unauthorized requests return HTTP 403.
Escape All Dynamic Data Written into DOM (XSS Mitigation):
When updating or inserting rows, all text nodes must use textContent or CrudAjax.escapeHtml().
Never interpolate raw variables directly into innerHTML without sanitization.
Preserve existing data-masking attributes (.maskable, data-real, data-masked).
Preserve Audit Logging & Employee Context:
Ensure the user's active session is established before invoking ActivityLog::log().
Pass user_id, role, and module context so audit logs record who initiated the AJAX call.
Maintain app.current_employee_id and PostgreSQL context where triggers or RLS rely on session state.
6. Verification and Testing Plan
For each module converted, two levels of verification will be executed:

Test A: Real-Time UI Behavioral Verification
Create Action:
Fill out the module's creation modal/form and click Submit.
Check: Form disables button, shows spinner, saves via AJAX, closes modal, shows success toast.
Check: The new row immediately appears at the top of the table with the highlight flash, without full browser reload (window.location.reload is NOT invoked).
Check: Dynamic counters (e.g. "Total Records", "Waiting Queue") update in-place.
Update Action:
Open edit modal for an existing record, change a field, and submit.
Check: The specific <tr> in the DOM is replaced in-place with updated data; other rows remain undisturbed.
Delete / Status Action:
Click Delete / Complete / Cancel.
Check: Confirm dialog appears; upon confirmation, the row animates out and is removed from the DOM.
Validation Failure:
Submit invalid/empty data.
Check: Server returns HTTP 422 with validation errors; error toast is shown; form remains open with user inputs intact.
Test B: Direct Security & Endpoint Hardening Verification (curl/Postman)
Unauthenticated Access:
bash
curl -s -X POST "http://localhost/capstone/api/triage.php" \
  -H "Content-Type: application/json" \
  -d '{"patient_id": 1, "priority": "high"}'
Expected Result: HTTP 401 Unauthorized or HTTP 403 Forbidden with { "success": false }.
Missing / Tampered CSRF Token:
bash
curl -s -X POST "http://localhost/capstone/api/triage.php" \
  -H "Cookie: PHPSESSID=valid_session_id" \
  -H "X-CSRF-Token: invalid_token" \
  -H "Content-Type: application/json" \
  -d '{"patient_id": 1, "priority": "high"}'
Expected Result: HTTP 403 Forbidden with Invalid or missing CSRF token.
Cross-Department Privilege Escalation:
Attempt an action using a session authenticated for a user outside the authorized department (e.g., Waste Water Staff attempting to triage a patient). Expected Result: HTTP 403 Forbidden with department access restriction message.
Syntax & Linter Integrity:
Run find app Core config api modules pages -name "*.php" -exec php -l {} + after every step to ensure zero syntax regressions.
Current Status
IMPORTANT

Awaiting Review and Approval: Per instructions, no code has been modified. Please confirm your approval of this plan and the proposed module migration order (starting with Phase 0 foundation, followed by Phase 1 Pilot: Health Services — Triage), or specify any adjustments you would like made before implementation begins.