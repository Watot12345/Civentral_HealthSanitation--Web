# Antigravity Prompt — Civentral Health Surveillance Module Hardening & Persistence

Paste everything below the line into Antigravity as a single task/prompt. It's written as a multi-phase workflow so the agent can work through it in order, verifying each phase before moving to the next.

---

## Context

This is a PHP capstone project (`civentral-web`) — an LGU employee portal. The `modules/surveillance/` directory contains six pages for a disease-surveillance system:

- Real-time Alerts
- Case Reports
- Contact Tracing
- Mapping & Clustering
- Outbreak Detection
- Response Management

All six pages currently hardcode their data as PHP arrays at the top of each file, and every "action" button (Report Case, Confirm, Resolve, Deploy Team, Allocate Resource, etc.) only mutates an in-memory JavaScript object and shows a toast — nothing persists past a page refresh. Every page also calls `requireDepartmentAccess('health surveillance')`, which must already be defined somewhere in `includes/` — locate it before changing auth behavior.

## Goal

Wire these six pages to real persistence and close the security gaps below, without changing the visual design, Tailwind classes, or UX flow already in place.

---

## Phase 0 — Reconnaissance (do this first, report back before changing anything)

1. Locate and read `includes/header.php`, `includes/sidebar.php`, `includes/footer.php`.
2. Find the definition of `requireDepartmentAccess()`. Confirm:
   - Where it reads the department/role from (`$_SESSION['department']`? `$_SESSION['role']`?).
   - What it does on failure (redirect? `die()`? HTTP 403?).
   - Whether the string `'health surveillance'` matches an actual value ever stored in `$_SESSION` at login (check `login.php`).
3. Locate `config/database.php` and the `Database` class used elsewhere (`Database::getInstance()`, `$db->select()`, `$db->update()` from `login.php`). Confirm whether it uses PDO prepared statements or raw string concatenation.
4. Check `DatabaseScheme.md` for any existing tables related to cases, contacts, alerts, teams, or resources. If none exist, note that new tables are needed.
5. Confirm `assets/css/leaflet.css`, `assets/js/leaflet.js`, `assets/js/apexcharts.min.js`, and `assets/js/leaflet-heat.js` actually exist in the repo (the Mapping & Clustering page references them locally, not via CDN).

**Stop and summarize findings before Phase 1** so schema/auth assumptions can be corrected if wrong.

---

## Phase 1 — Schema

Design and create migrations (or raw SQL matching whatever style `DatabaseScheme.md` / existing tables use) for:

- `surveillance_cases` — case_id, disease, patient_name, age, gender, address, barangay, contact, symptoms (JSON or normalized table), onset_date, reporting_facility, status, severity, reported_by, investigator_id, investigation_notes, contact_tracing_done, outbreak_id, created_at, updated_at
- `surveillance_index_cases` — id, name, age, gender, barangay, disease, date_confirmed, status, risk_level
- `surveillance_contacts` — id, index_case_id (FK), name, age, gender, relationship, address, barangay, exposure_type, exposure_date, last_contact_date, symptoms, monitoring_status, quarantine_status, quarantine_start, quarantine_end, risk_level
- `surveillance_alerts` — id, disease, barangay, cases, threshold, severity, status, timestamp, escalation_level, assigned_to, response_actions, message
- `surveillance_response_teams` — id, name, leader, members (JSON or join table), specialization, status, deployed_to, last_deployment, contact
- `surveillance_resources` — id, name, category, quantity, unit, location, status, last_restock, threshold
- `surveillance_interventions` — id, title, type, location, status, start_date, end_date, team_lead, progress, activities, resources_used, outcomes

Seed each table with the current hardcoded sample data from the six PHP files so the UI looks identical on first load.

---

## Phase 2 — Backend endpoints

For each page, create a matching AJAX endpoint (mirror the pattern already used in `login.php`: `header('Content-Type: application/json')`, `X-Requested-With: XMLHttpRequest` check, try/catch, `json_encode(['success' => ..., ...])`). Suggested layout: `modules/surveillance/api/{resource}.php`.

Minimum required actions, one per current fake JS function:

| Page | JS function to replace | New endpoint / action |
|---|---|---|
| Case Reports | `saveCaseReport()` | `POST /api/cases` (create) |
| Case Reports | `updateCase()` | `PUT/POST /api/cases/{id}` (update) |
| Case Reports | `saveInvestigation()` | `POST /api/cases/{id}/investigate` |
| Case Reports | `doConfirmCase()` / `doResolveCase()` | `POST /api/cases/{id}/status` |
| Contact Tracing | `saveContact()` | `POST /api/contacts` |
| Contact Tracing | `updateMonitoring()` | `POST /api/contacts/{id}/monitoring` |
| Real-time Alerts | `resolveAlert()` / `escalateAlert()` | `POST /api/alerts/{id}/status` |
| Real-time Alerts | `activateEmergencyResponse()` | `POST /api/alerts/{id}/emergency-response` |
| Response Mgmt | `deployTeam()` / `activateTeam()` | `POST /api/teams/{id}/deploy` |
| Response Mgmt | `allocateResourceSubmit()` | `POST /api/resources/{id}/allocate` |
| Response Mgmt | `updateIntervention()` | `POST /api/interventions/{id}` |

Every write endpoint must:
- Require an active session (`$_SESSION['logged_in'] === true`) and re-check `requireDepartmentAccess('health surveillance')` server-side — never trust that the page-level check was enough, since these are separate HTTP requests.
- Use parameterized queries / prepared statements only. No string-concatenated SQL anywhere, including for IDs pulled from the URL or POST body.
- Validate and sanitize all inputs server-side (required fields, enum values for `status`/`severity`/`risk_level`, numeric checks for `age`/`quantity`).
- Return consistent JSON shape: `{ "success": bool, "message": string, "data"?: object }`.
- Log the action via the existing `ActivityLog` model (same pattern as `login.php`'s login/logout logging) — e.g. "Case CS-004 confirmed by {user}".

---

## Phase 3 — Frontend wiring

For each of the six PHP files:

1. Replace the hardcoded `$cases = [...]` / `$contacts = [...]` / etc. arrays with a real query against the new tables (`$db->select(...)` or equivalent), keeping the exact same array shape so none of the existing Tailwind/HTML templating breaks.
2. Replace every JS function currently listed in the table above with a `fetch()` call to its new endpoint, following the same pattern already used in `login.php`'s `handleLogin()`:
   - `AbortController` with a timeout
   - `X-Requested-With: XMLHttpRequest` header
   - Disable the submitting button while in flight
   - On success: update the DOM (or just reload the relevant list/table) and show the existing toast
   - On failure: show the existing toast with an error message, re-enable the button
3. Keep all existing filter/search functions (`filterCases`, `filterContacts`, `filterAlerts`, etc.) client-side as they are — they only need to operate on freshly-rendered/updated DOM, not be rewritten.

---

## Phase 4 — Security fixes (apply across all six files)

1. **XSS / output escaping** — wrap every PHP-echoed user-controllable string in `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')`. This includes but is not limited to: `patient_name`, `disease`, `address`, `barangay`, `investigation_notes`, `message`, `assigned_to`, team `leader`/`members`, `location`, intervention `title`.
2. **Client-side XSS** — anywhere JS builds HTML via template literals from JSON-embedded data (`viewCase()`, `viewContactDetails()`, `viewInterventionDetails()`), either escape the interpolated values before inserting into `innerHTML`, or switch those specific fields to `textContent` assignment instead of template-literal `innerHTML`.
3. **JSON embedding** — every `<?php echo json_encode(...); ?>` used inside a `<script>` block should use `JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP` flags to prevent breaking out of the script context if any field ever contains `</script>` or similar.
4. **Divide-by-zero guards** — anywhere the code computes a percentage from a count (e.g. Contact Tracing's `($quarantined / $totalContacts) * 100`), guard against `$totalContacts == 0`.
5. **ID-based DOM matching** — fix `updateCaseRow()` in Case Reports, which currently matches table rows by patient name text content. Add `data-case-id="<?php echo $case['id']; ?>"` to each `<tr>` and match on that instead, so duplicate names don't collide.
6. **CSRF** — add a per-session CSRF token (generated in `header.php` or session bootstrap, rendered as a hidden field / meta tag) and require it on every POST endpoint created in Phase 2.
7. **Rate limiting / basic abuse guard** — not required for this module specifically, but flag if any of the new endpoints are reachable pre-login; they should not be.

---

## Phase 5 — Consistency & polish

1. Normalize all sample/seeded dates to a single consistent "current" timeframe (some existing files use `2024-01-*`, others `2026-07-*`) — pick one and make seed data consistent.
2. Confirm the Leaflet/ApexCharts local asset files referenced by Mapping & Clustering actually exist in `assets/css/` and `assets/js/`; if missing, either commit them or switch to the CDN links already used elsewhere in the project (`login.php` uses `cdn.jsdelivr.net` and `cdnjs.cloudflare.com`).
3. Run through each of the six pages manually after wiring:
   - Load page → confirm data renders from DB, not hardcoded array.
   - Perform each write action → confirm DB row changes → refresh page → confirm the change persisted.
   - Attempt a write action while logged out / without the right department → confirm it's rejected server-side, not just hidden client-side.

---

## Deliverable

At the end, provide:
- List of new/changed files.
- The SQL migration(s) or schema additions.
- A short manual test checklist matching Phase 5, step 3, per page.
- Any assumptions made in Phase 0 that couldn't be verified (e.g. if `requireDepartmentAccess()` wasn't found, state what was assumed and where it should be added).