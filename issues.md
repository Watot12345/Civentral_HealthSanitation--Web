# Multi-Issue Fix Plan

**Date:** August 28, 2026  
**Scope:** 5 changes across 4 files

---

## Issue 1 — Nutrition Assessment: Waiting Queue Should Be Modal-Only (Today's Visits)

### Current Problem
[`nutrition_assessment.php:L241–L307`](file:///opt/lampp/htdocs/capstone/modules/immunization/nutrition_assessment.php#L241-L307) — The "Patients Waiting for Nutrition Assessment" table is **permanently visible** on the page. It should only appear when a user clicks a "Waiting Queue" button, and it must only show **today's** triage visits (currently it shows all visits with reason = `'Nutrition Assessment'` regardless of date).

### Fix

#### A — PHP: Filter to Today's Visits Only

**File:** [`nutrition_assessment.php:L31`](file:///opt/lampp/htdocs/capstone/modules/immunization/nutrition_assessment.php#L31)

```diff
- $nutritionVisitsRaw = $triageQueueModel->getVisitsByReason('Nutrition Assessment');
+ $nutritionVisitsRaw = $triageQueueModel->getVisitsByReason('Nutrition Assessment', date('Y-m-d'));
```

Then update `TriageQueue::getVisitsByReason()` to accept an optional `$date` parameter and add a `WHERE DATE(check_in_time) = :date` filter when supplied.

#### B — HTML: Remove the Always-Visible Table Block

**File:** [`nutrition_assessment.php:L241–L307`](file:///opt/lampp/htdocs/capstone/modules/immunization/nutrition_assessment.php#L241-L307)

Remove the entire `<?php if (!empty($nutritionVisits)): ?> ... <?php endif; ?>` block.

#### C — HTML: Add a "Waiting Queue" Button to the Page Header (around L233)

```html
<button onclick="openModal('nutritionQueueModal')"
        class="px-3.5 py-2 bg-white border border-emerald-200 text-emerald-700 hover:bg-emerald-50 rounded-lg transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
    <i class="fa-solid fa-apple-whole text-xs text-emerald-600"></i> Waiting Queue
    <?php if (count($nutritionVisits) > 0): ?>
        <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-full text-[10px] font-bold">
            <?php echo count($nutritionVisits); ?>
        </span>
    <?php endif; ?>
</button>
```

#### D — Create Modal at Bottom of Page

The modal body contains the exact same table rows previously inline (cut from L254–L305, paste inside modal body). If no visits: show "No patients in queue for today."

---

## Issue 2 — User Management: Remove Role Assignment & Permission Management Sections

### Current Problem
[`user_management.php:L441–L499`](file:///opt/lampp/htdocs/capstone/management/user_management.php#L441-L499) — The 2-column grid with all 19 roles listed and the Permission Management panel is permanently visible. It's redundant because the Edit button on each user row already handles role editing.

### Fix

**File:** [`management/user_management.php:L441–L499`](file:///opt/lampp/htdocs/capstone/management/user_management.php#L441-L499)

Delete the entire block:
```html
<!-- ROLE ASSIGNMENT & PERMISSION MANAGEMENT -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6"> ... </div>
```

> [!NOTE]
> Keep the Role edit **modal** and `editRole()` JS function — just remove the always-visible listing panel. The per-user 🔑 Permissions icon in the user table row also stays.

---

## Issue 3 — User Management: Remove the Refresh Button

### Current Problem
[`user_management.php:L208–L210`](file:///opt/lampp/htdocs/capstone/management/user_management.php#L208-L210) — Redundant button that calls `location.reload()`.

### Fix

Delete the 3 lines:
```html
<button onclick="refreshData()" ...>
    <i class="fa-solid fa-sync-alt text-xs"></i> Refresh
</button>
```

---

## Issue 4 — User Management: Status Toggle → 3-State Status Picker (Suspended)

### Current Problem
The toggle button at [`user_management.php:L422`](file:///opt/lampp/htdocs/capstone/management/user_management.php#L422) only cycles `Active ↔ Inactive`. The API at [`user_management_api.php:L242`](file:///opt/lampp/htdocs/capstone/management/user_management_api.php#L242) confirms this: `$newStatus = ($currentStatus === 'Active') ? 'Inactive' : 'Active';` — Suspended is never reachable via this toggle.

### Fix — 3-State Status Picker Modal

#### A — Replace Toggle Button Icon (L422 and L1233)

```diff
- <button onclick="toggleUserStatus(<?= (int)$user['id'] ?>)" title="Toggle Status">
-     <i class="fa-solid <?= ... ? 'fa-pause' : 'fa-play' ?>"></i>
- </button>
+ <button onclick="setUserStatus(<?= (int)$user['id'] ?>)" title="Set Status">
+     <i class="fa-solid fa-sliders"></i>
+ </button>
```

#### B — Add Status Picker Modal HTML (at bottom of page)

```html
<div id="setStatusModal" class="modal-overlay hidden fixed inset-0 z-50 bg-black/40 flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full mx-4 p-6">
        <h3 class="font-bold text-slate-900 mb-1 text-base">Set User Status</h3>
        <p id="setStatusUserName" class="text-sm text-slate-500 mb-5"></p>
        <input type="hidden" id="setStatusUserId">
        <div class="grid grid-cols-3 gap-3 mb-6">
            <button onclick="applyStatus('Active')"
                    class="py-3 rounded-xl border-2 border-emerald-300 bg-emerald-50 text-emerald-800 font-bold text-sm hover:bg-emerald-100 transition">
                ✅ Active
            </button>
            <button onclick="applyStatus('Inactive')"
                    class="py-3 rounded-xl border-2 border-slate-300 bg-slate-50 text-slate-700 font-bold text-sm hover:bg-slate-100 transition">
                ⏸ Inactive
            </button>
            <button onclick="applyStatus('Suspended')"
                    class="py-3 rounded-xl border-2 border-red-300 bg-red-50 text-red-800 font-bold text-sm hover:bg-red-100 transition">
                🚫 Suspended
            </button>
        </div>
        <div class="flex justify-end">
            <button onclick="closeModal('setStatusModal')"
                    class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</button>
        </div>
    </div>
</div>
```

#### C — Add JS Functions

```javascript
function setUserStatus(userId) {
    const row = document.querySelector(`.user-row[data-id="${userId}"]`);
    document.getElementById('setStatusUserId').value = userId;
    document.getElementById('setStatusUserName').textContent =
        row ? `User: ${row.dataset.fullname}` : `User ID: ${userId}`;
    openModal('setStatusModal');
}

function applyStatus(newStatus) {
    const userId = document.getElementById('setStatusUserId').value;
    const body = new URLSearchParams();
    body.append('action', 'set_status');
    body.append('user_id', userId);
    body.append('new_status', newStatus);

    fetch('user_management_api.php', { method: 'POST', body })
        .then(res => res.json())
        .then(data => {
            closeModal('setStatusModal');
            if (data.success) {
                const row = document.querySelector(`.user-row[data-id="${userId}"]`);
                if (row) {
                    row.dataset.status = newStatus;
                    const badge = row.children[5]?.querySelector('span');
                    if (badge) {
                        badge.textContent = newStatus;
                        badge.className = `status-badge px-2 py-1 ${
                            newStatus === 'Active' ? 'bg-emerald-100 text-emerald-700' :
                            newStatus === 'Suspended' ? 'bg-red-100 text-red-700' :
                            'bg-slate-100 text-slate-700'
                        } rounded-full text-xs font-semibold`;
                    }
                }
                updateKPISummariesJS();
                showToast(data.message, 'success', 'Status Updated');
            } else {
                showToast(data.message, 'danger', 'Update Failed');
            }
        });
}
```

#### D — Add `set_status` Case to API

**File:** [`management/user_management_api.php`](file:///opt/lampp/htdocs/capstone/management/user_management_api.php) (after the `toggle_status` case, ~L253)

```php
case 'set_status':
    $id = (int)($_POST['user_id'] ?? 0);
    $newStatus = trim($_POST['new_status'] ?? '');
    $allowed = ['Active', 'Inactive', 'Suspended'];

    if (!$id || !in_array($newStatus, $allowed, true)) {
        $response = ['success' => false, 'message' => 'Invalid status value.'];
        break;
    }
    if ($id === (int)($_SESSION['user_id'] ?? 0)) {
        $response = ['success' => false, 'message' => 'You cannot change your own account status.'];
        break;
    }

    $user = $employeeModel->find($id);
    $employeeModel->updateById($id, ['status' => $newStatus]);

    $userName = $user['full_name'] ?? "ID {$id}";
    $logModel->log("Set status to {$newStatus} for: {$userName}", [
        'module'  => 'User Management',
        'details' => "Status changed to {$newStatus}",
        'status'  => 'Success'
    ]);

    $response = ['success' => true, 'message' => "Status for {$userName} set to {$newStatus}.", 'new_status' => $newStatus];
    break;
```

---

## Issue 5 — Surveillance Map: Remove Cluster Checkbox Filter

### Current Problem
[`mapping.php:L324–L327`](file:///opt/lampp/htdocs/capstone/modules/surveillence/mapping.php#L324-L327) — A checkbox labelled "Clusters" in the map control bar.

### Fix

Delete only these 4 lines:
```html
<label class="flex items-center gap-1.5 text-slate-600 font-semibold cursor-pointer select-none">
    <input type="checkbox" id="toggleClusters" checked onchange="toggleLayer('clusters')" class="rounded text-brand-dark focus:ring-brand-medium">
    Clusters
</label>
```

The cluster data and map layer remain intact and always visible. Only the toggle UI is removed.

---

## Issue 6 — Surveillance Map: CARTO Tile URL Diagnosis

### Verdict: No API Key Is Required for the Current Tiles

**File:** [`mapping.php:L576–L577`](file:///opt/lampp/htdocs/capstone/modules/surveillence/mapping.php#L576-L577)

```
https://a.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}@2x.png
```

> [!IMPORTANT]
> **`basemaps.cartocdn.com` is a free, public raster tile service — no API key required.** The `https://gcp-us-east1.api.carto.com` you saw is CARTO's paid Cloud Platform API, which is different. Your tiles are fine.

### If the Map Is Not Rendering, Check These Instead

| Problem | How to Check | Fix |
|---|---|---|
| MapLibre JS/CSS not loaded | DevTools → Console: `maplibregl is not defined` | Verify CDN link in `<head>` |
| Tiles blocked by ad-blocker or CORS | DevTools → Network: tile requests show `(blocked)` or 403 | Switch to OSM tiles |
| Mixed content (HTTP/HTTPS) | Console: `Mixed Content` warning | Access the app via `http://` not `https://` on localhost |

### Optional: Swap to OpenStreetMap Tiles (Most Reliable Free Option)

```diff
  tiles: [
-     'https://a.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}@2x.png',
-     'https://b.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}@2x.png'
+     'https://tile.openstreetmap.org/{z}/{x}/{y}.png'
  ],
- attribution: '&copy; OpenStreetMap &copy; CARTO'
+ attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
```

---

## Summary Table

| # | File | Change | Scope |
|---|---|---|---|
| **1a** | [`nutrition_assessment.php:L31`](file:///opt/lampp/htdocs/capstone/modules/immunization/nutrition_assessment.php#L31) | Add today's date filter to visit query | 1-line |
| **1b** | [`nutrition_assessment.php:L241–L307`](file:///opt/lampp/htdocs/capstone/modules/immunization/nutrition_assessment.php#L241-L307) | Delete inline waiting table block | Delete 67 lines |
| **1c** | [`nutrition_assessment.php:~L233`](file:///opt/lampp/htdocs/capstone/modules/immunization/nutrition_assessment.php#L233) | Add "Waiting Queue" button in header | Add ~8 lines |
| **1d** | [`nutrition_assessment.php`](file:///opt/lampp/htdocs/capstone/modules/immunization/nutrition_assessment.php) | Add `nutritionQueueModal` at bottom | New modal block |
| **2** | [`user_management.php:L441–L499`](file:///opt/lampp/htdocs/capstone/management/user_management.php#L441-L499) | Delete Role Assignment + Permission Management panels | Delete 59 lines |
| **3** | [`user_management.php:L208–L210`](file:///opt/lampp/htdocs/capstone/management/user_management.php#L208-L210) | Delete Refresh button | Delete 3 lines |
| **4a** | [`user_management.php:L422, L1233`](file:///opt/lampp/htdocs/capstone/management/user_management.php#L422) | Replace toggle btn → `setUserStatus()` | 2 locations |
| **4b** | [`user_management.php`](file:///opt/lampp/htdocs/capstone/management/user_management.php) | Add status picker modal + JS | New block |
| **4c** | [`user_management_api.php`](file:///opt/lampp/htdocs/capstone/management/user_management_api.php) | Add `set_status` API case | After L253 |
| **5** | [`mapping.php:L324–L327`](file:///opt/lampp/htdocs/capstone/modules/surveillence/mapping.php#L324-L327) | Remove Clusters checkbox | Delete 4 lines |
| **6** | [`mapping.php:L576–L577`](file:///opt/lampp/htdocs/capstone/modules/surveillence/mapping.php#L576-L577) | Swap CARTO → OSM tiles (if map broken) | 2-line swap |

---

# Wastewater Services — Fix & Enhancement Plan

**Date:** August 28, 2026  
**Files Affected:** `providers.php`, `wastewater_billing.php`, `septic_tanks.php`, `service_requests.php`

---

## Issue 1 — Quotation Generator: Manual Input → Dropdown Selections

### Current Problem
[`wastewater_billing.php:L399–L404`](file:///opt/lampp/htdocs/capstone/modules/services/wastewater_billing.php#L399-L404)

Both **Client Name** and **Tank ID** are free-text `<input type="text">` fields:
```html
<input type="text" id="quote_client" required ...>  <!-- L400 -->
<input type="text" id="quote_tank"   required ...>  <!-- L404 -->
```

This causes typos, inconsistent names, and mismatched tank references.

### Fix

#### A — PHP: Fetch Septic Tanks for Dropdown (top of wastewater_billing.php)

The `septic_tanks` table already exists. Add this fetch:
```php
// Fetch registered septic tanks for quotation dropdown
$septicTanks = [];
try {
    $septicTanks = $db->select('septic_tanks', [], ['order' => 'owner_name.asc']);
} catch (Throwable $e) {
    error_log('Error fetching septic tanks for quotation: ' . $e->getMessage());
}
```

#### B — HTML: Replace Inputs with Linked Dropdowns

```diff
- <label>Client Name</label>
- <input type="text" id="quote_client" required ...>
- <label>Tank ID</label>
- <input type="text" id="quote_tank" required ...>

+ <label>Client (Owner Name)</label>
+ <select id="quote_client" required onchange="populateTankFromOwner(this)" ...>
+     <option value="">— Select Client —</option>
+     <?php foreach ($septicTanks as $tank): ?>
+         <option value="<?= htmlspecialchars($tank['owner_name']) ?>"
+                 data-tank-id="<?= htmlspecialchars($tank['tank_id']) ?>"
+                 data-tank-address="<?= htmlspecialchars($tank['address'] ?? '') ?>">
+             <?= htmlspecialchars($tank['owner_name']) ?>
+         </option>
+     <?php endforeach; ?>
+ </select>
+
+ <label>Tank ID</label>
+ <select id="quote_tank" required ...>
+     <option value="">— Select Client First —</option>
+ </select>
```

#### C — JS: Auto-populate Tank ID when Client is selected

```javascript
// Groups septic tanks by owner for quick lookup
const TANKS_BY_OWNER = {};
<?php foreach ($septicTanks as $tank): ?>
const owner = <?= json_encode($tank['owner_name']) ?>;
if (!TANKS_BY_OWNER[owner]) TANKS_BY_OWNER[owner] = [];
TANKS_BY_OWNER[owner].push({
    tank_id: <?= json_encode($tank['tank_id']) ?>,
    address: <?= json_encode($tank['address'] ?? '') ?>,
    capacity: <?= json_encode($tank['tank_capacity'] ?? '') ?>
});
<?php endforeach; ?>

function populateTankFromOwner(selectEl) {
    const owner = selectEl.value;
    const tankSelect = document.getElementById('quote_tank');
    tankSelect.innerHTML = '<option value="">— Select Tank —</option>';
    const tanks = TANKS_BY_OWNER[owner] || [];
    tanks.forEach(t => {
        const opt = document.createElement('option');
        opt.value = t.tank_id;
        opt.textContent = `${t.tank_id}${t.address ? ' — ' + t.address : ''}`;
        tankSelect.appendChild(opt);
    });
    if (tanks.length === 1) tankSelect.value = tanks[0].tank_id;
}
```

---

## Issue 2 — Register Provider: +63 Prefix & JS Null Error

### Problem A — Phone number input has no +63 prefix

[`providers.php:L357–L358`](file:///opt/lampp/htdocs/capstone/modules/services/providers.php#L357-L358) — The contact input accepts 12 digits but the label says "Contact" with no `+63` prefix shown. Users type a full 11-digit number (09xx), confusing the format.

#### Fix — Split-field UI (Prefix + 10-digit Number)

```diff
- <input type="text" id="prov_contact" inputmode="numeric" maxlength="12" ...>

+ <div class="flex gap-0">
+     <span class="px-3 py-2 bg-slate-100 border border-r-0 border-slate-200 rounded-l-lg text-sm font-semibold text-slate-600 select-none">+63</span>
+     <input type="text" id="prov_contact" inputmode="numeric" maxlength="10"
+            placeholder="9368587433"
+            oninput="limitProviderContact(this)"
+            class="w-full px-3 py-2 border border-slate-200 rounded-r-lg text-sm ...">
+ </div>
```

Then update JS to store as full 12-digit number before saving:
```javascript
function limitProviderContact(input) {
    input.value = input.value.replace(/\D/g, '').slice(0, 10);
}

// When saving, prepend 63 (without +):
const rawContact = document.getElementById('prov_contact').value;
const contact = '63' + rawContact;  // → "639368587433"
```

Also update `isValidProviderData()`:
```diff
- const contactOk = /^\d{12}$/.test(contact);
+ const contactOk = /^63\d{10}$/.test(contact);
```

Apply the same fix to **Edit Provider** modal at [L435](file:///opt/lampp/htdocs/capstone/modules/services/providers.php#L435).

### Problem B — `Cannot read properties of null (reading 'value')` Error

This error occurs in `saveProviderRegistration()` or `saveProviderEdit()` when a DOM element with `getElementById()` returns `null` — meaning the ID doesn't match or the element is outside the active form.

**Most likely cause:** After migrating the contact field to a split-prefix layout, old code still references `getElementById('prov_contact')` before the field is rendered, OR a function is called from outside its modal scope.

**Fix:** Add null-guards to all element reads:
```javascript
async function saveProviderRegistration(event) {
    event.preventDefault();
    const contactEl = document.getElementById('prov_contact');
    if (!contactEl) { showToast('Form error: contact field missing', 'danger'); return; }
    const contact = '63' + contactEl.value;
    // ... rest of function
}
```

---

## Issue 3 — Assign Provider: Wrong Assignment Type Options

### Current Problem
[`providers.php:L490–L494`](file:///opt/lampp/htdocs/capstone/modules/services/providers.php#L490-L494)

```html
<option value="service_request">Service Request</option>   ← generic / wrong
<option value="maintenance">Maintenance</option>
<option value="emergency">Emergency</option>               ← not in scope
```

### Fix

Replace with the 3 correct wastewater assignment types:

```diff
- <option value="service_request">Service Request</option>
- <option value="maintenance">Maintenance</option>
- <option value="emergency">Emergency</option>

+ <option value="maintenance">Maintenance</option>
+ <option value="desludging">Desludging</option>
+ <option value="installation">Installation</option>
```

---

## Issue 4 — Route Planning & View Maps + Transaction History

### Current State
Neither route planning, map view of routes, nor transaction history per provider exists in [`providers.php`](file:///opt/lampp/htdocs/capstone/modules/services/providers.php).

### Fix Plan

#### A — Add Action Buttons to Each Provider Card

```html
<!-- Inside each provider card's action bar -->
<button onclick="viewProviderRoutes(<?= $provider['id'] ?>)" title="Route Planning & Map">
    <i class="fa-solid fa-route"></i> Routes
</button>
<button onclick="viewProviderHistory(<?= $provider['id'] ?>)" title="Transaction History">
    <i class="fa-solid fa-clock-rotate-left"></i> History
</button>
```

#### B — Route Planning Modal

Uses the same MapLibre setup already in [`mapping.php`](file:///opt/lampp/htdocs/capstone/modules/surveillence/mapping.php) — no new library needed.

```html
<div id="routePlanModal" class="hidden fixed inset-0 ...">
    <div class="bg-white rounded-2xl ... max-w-4xl">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h3 class="font-bold">Route Planning — <span id="routeProviderName"></span></h3>
        </div>
        <div class="p-4">
            <!-- Scheduled jobs list for this provider today/upcoming -->
            <div id="routeJobsList" class="space-y-2 mb-4 max-h-[200px] overflow-y-auto"></div>
            <!-- Map renders tank locations of assigned jobs -->
            <div id="providerRouteMap" style="height: 360px; border-radius:12px;"></div>
        </div>
    </div>
</div>
```

**JS: `viewProviderRoutes(providerId)`**
1. Fetch provider's upcoming assignments from `service_requests` or `provider_assignments` where `provider_id = id AND status IN ('pending','in_progress')` via API
2. Plot each job's septic tank lat/lng as a marker on the MapLibre map
3. Connect markers with a `LineString` route layer

**PHP API endpoint to add:** `GET /api/service_requests.php?provider_id=<id>&upcoming=1`
- Returns: `[{ id, tank_id, address, lat, lng, scheduled_date, assignment_type, status }]`

#### C — Transaction History Modal

```html
<div id="providerHistoryModal" class="hidden fixed inset-0 ...">
    <div class="bg-white rounded-2xl ... max-w-2xl">
        <div class="px-6 py-4 border-b">
            <h3 class="font-bold">Transaction History — <span id="historyProviderName"></span></h3>
        </div>
        <div id="historyContent" class="p-4 max-h-[500px] overflow-y-auto space-y-3">
            <!-- Loaded dynamically -->
        </div>
    </div>
</div>
```

**JS: `viewProviderHistory(providerId)`**
- Fetch from `GET /api/service_requests.php?provider_id=<id>&history=1`
- Renders a timeline of: date, job type, tank address, status, rating (if any)

**PHP API endpoint to add:** `GET /api/service_requests.php?provider_id=<id>&history=1`
- Returns completed + cancelled assignments for this provider ordered by `completed_at DESC`

---

## Issue 5 — Avg Rating Shows 0.0

### Root Cause
[`providers.php:L47–L48`](file:///opt/lampp/htdocs/capstone/modules/services/providers.php#L47-L48)

```php
$ratings = array_filter(array_column($serviceProviders, 'rating'));
$avgRating = !empty($ratings) ? round(array_sum($ratings) / count($ratings), 1) : 0.0;
```

The `rating` column is stored on the **`service_providers`** table itself. This is a **provider's self-declared or system-static rating**, not derived from actual client feedback.

Meanwhile, [`service_requests.php:L43`](file:///opt/lampp/htdocs/capstone/modules/services/service_requests.php#L43) computes its own avg rating from the `service_requests` table's `rating` column — which is where actual star ratings submitted via the feedback form go.

**The result is 0.0 because the `service_providers.rating` field is never written to.** No code writes feedback back to `service_providers.rating`.

### Fix — Compute Real Avg from service_requests Table

```php
// PHP: Fetch real avg rating from completed service requests
$avgRating = 0.0;
try {
    $ratedRequests = $db->select('service_requests', [], [
        'select' => 'rating',
        'not' => 'rating.is.null'
    ]);
    $ratingValues = array_filter(array_column($ratedRequests, 'rating'));
    $avgRating = !empty($ratingValues)
        ? round(array_sum($ratingValues) / count($ratingValues), 1)
        : 0.0;
} catch (Throwable $e) {
    error_log('Error fetching avg rating: ' . $e->getMessage());
}
```

Then replace the KPI card display with `$avgRating` (already connected to the correct variable name).

---

## Issue 6 — Equipment Management: Align Per Provider + Availability Status

### Current Problem
[`providers.php:L37–L40`](file:///opt/lampp/htdocs/capstone/modules/services/providers.php#L37-L40)

```php
$equipmentInventory = [
    // HARDCODED — not from database, not filtered per provider
];
```

The Equipment Management modal shows all equipment regardless of which provider it belongs to.

### Fix

#### A — Move Equipment to Database

Create/use an `equipment` table:
```sql
equipment (id, provider_id FK, name, type, status, capacity, license_plate, created_at)
```

#### B — PHP: Fetch Equipment Per Provider

```php
// Replace hardcoded array:
$equipmentInventory = [];
try {
    $equipmentInventory = $db->select('equipment', [], ['order' => 'provider_id.asc,name.asc']);
} catch (Throwable $e) {
    error_log('Equipment fetch error: ' . $e->getMessage());
}

// Group by provider_id for easy JS lookup
$equipmentByProvider = [];
foreach ($equipmentInventory as $eq) {
    $equipmentByProvider[$eq['provider_id']][] = $eq;
}
```

#### C — JS: Filter Equipment Modal by Provider

```javascript
function openEquipmentModal(providerId) {
    const providerEquipment = EQUIPMENT_BY_PROVIDER[providerId] || [];
    document.getElementById('equipmentListContainer').innerHTML = providerEquipment.length
        ? providerEquipment.map(eq => `
            <div class="bg-white rounded-xl p-3 border border-slate-200 flex items-center justify-between">
                <div>
                    <p class="font-semibold text-sm text-slate-800">${escHtml(eq.name)}</p>
                    <p class="text-xs text-slate-400">${escHtml(eq.type)} • Capacity: ${escHtml(eq.capacity || '—')}</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold ${
                        eq.status === 'available' ? 'bg-emerald-100 text-emerald-700' :
                        eq.status === 'in_use' ? 'bg-amber-100 text-amber-700' :
                        'bg-rose-100 text-rose-700'
                    }">${eq.status.replace('_', ' ')}</span>
                    <button onclick="toggleEquipmentStatus(${eq.id}, '${eq.status}')"
                            class="px-2 py-1 bg-slate-100 text-slate-600 text-xs rounded hover:bg-slate-200">
                        Toggle
                    </button>
                </div>
            </div>`).join('')
        : `<p class="text-sm text-slate-400 text-center py-6">No equipment registered for this provider.</p>`;

    openModal('equipmentManagementModal');
}
```

#### D — Add Availability Toggle API

`POST /api/providers.php` with `action=toggle_equipment&equipment_id=<id>&status=<new_status>`

---

## Issue 7 — Total Jobs: Should Be Dynamic from Completed Wastewater Requests

### Current Problem
[`providers.php:L49`](file:///opt/lampp/htdocs/capstone/modules/services/providers.php#L49)

```php
$totalJobs = array_sum(array_column($serviceProviders, 'completed_jobs'));
```

This reads from `service_providers.completed_jobs` — a static integer column that is **never automatically updated** when a service request is completed.

### Fix — Count Directly from service_requests Table

```php
// Count actual completed wastewater service requests
$totalJobs = 0;
try {
    $completedReqs = $db->select('service_requests', ['status' => 'completed']);
    $totalJobs = count($completedReqs);
} catch (Throwable $e) {
    error_log('Total jobs count error: ' . $e->getMessage());
}
```

Also update the **per-provider** completed jobs count using the same pattern:
```php
// Build a lookup: provider_id → count of their completed jobs
$jobCountByProvider = [];
try {
    $allCompleted = $db->select('service_requests', ['status' => 'completed'], ['select' => 'provider_id']);
    foreach ($allCompleted as $row) {
        $pid = $row['provider_id'] ?? null;
        if ($pid) $jobCountByProvider[$pid] = ($jobCountByProvider[$pid] ?? 0) + 1;
    }
} catch (Throwable $e) {}

// Inject live count into each provider
foreach ($serviceProviders as &$p) {
    $p['completed_jobs'] = $jobCountByProvider[$p['id']] ?? 0;
}
unset($p);
```

---

## Summary Table

| # | File | Change | Type |
|---|---|---|---|
| **1a** | [`wastewater_billing.php`](file:///opt/lampp/htdocs/capstone/modules/services/wastewater_billing.php) | Fetch `septic_tanks` for quotation dropdowns | PHP + HTML |
| **1b** | [`wastewater_billing.php:L399–L404`](file:///opt/lampp/htdocs/capstone/modules/services/wastewater_billing.php#L399) | Replace text inputs → linked `<select>` | HTML + JS |
| **2a** | [`providers.php:L357–L358`](file:///opt/lampp/htdocs/capstone/modules/services/providers.php#L357) | Split contact into `+63` prefix + 10-digit field | HTML + JS |
| **2b** | [`providers.php:saveProviderRegistration`](file:///opt/lampp/htdocs/capstone/modules/services/providers.php#L840) | Add null-guards to fix JS crash | JS fix |
| **3** | [`providers.php:L490–L494`](file:///opt/lampp/htdocs/capstone/modules/services/providers.php#L490) | Fix assignment type options | HTML |
| **4** | [`providers.php`](file:///opt/lampp/htdocs/capstone/modules/services/providers.php) | Add Route Map modal + Transaction History modal + API endpoints | New feature |
| **5** | [`providers.php:L47–L48`](file:///opt/lampp/htdocs/capstone/modules/services/providers.php#L47) | Recompute avg rating from `service_requests.rating` | PHP fix |
| **6** | [`providers.php:L37–L40`](file:///opt/lampp/htdocs/capstone/modules/services/providers.php#L37) | Replace hardcoded equipment array → DB query, filter per provider | PHP + HTML + JS |
| **7** | [`providers.php:L49`](file:///opt/lampp/htdocs/capstone/modules/services/providers.php#L49) | Count Total Jobs from `service_requests` WHERE `status=completed` | PHP fix |

---

*Plan written: August 28, 2026*


*Plan written: August 28, 2026*
