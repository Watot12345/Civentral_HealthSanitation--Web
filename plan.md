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
