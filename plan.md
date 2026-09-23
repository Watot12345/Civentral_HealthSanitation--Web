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

## Bug 6: Staff Performance — Head Only — ⏳ PENDING

### Status
Needs clarification on what the actual issue is.

> [!IMPORTANT]
> **Question:** "Staff performance head only" — is the issue that:
> - **(A)** Department heads **can't** see it (but should)? → Likely a role string mismatch
> - **(B)** The data shown is wrong/incomplete for heads?
> - **(C)** You're confirming current behavior is correct and this is not actually a bug?
> - **(D)** You want to restrict it further (e.g., remove admin access)?

---

## Bug 7: Add Patient Real-time Adding — ⏳ PENDING

Same scope as Bug #3/4 — will be addressed together once you pick an approach.

---

## Remaining Open Questions

1. **Bug 3/4/7:** Which option (A/B/C)? Which modules first?
2. **Bug 6:** What exactly is the staff performance issue?
