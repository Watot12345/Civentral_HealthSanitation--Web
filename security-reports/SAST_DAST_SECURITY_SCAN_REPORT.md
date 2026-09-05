# 🛡️ SAST & DAST Security Scan & Remediation Report — Civentral

- **System**: Civentral Public Health & Municipal Sanitation ERP
- **SAST Engine**: PHPStan v2.x (`phpstan.phar`, Level 5 analysis)
- **DAST Suite**: Automated Security & Penetration Audit (`tests/dast_scan.php`)
- **Scan Date**: 2026-09-05
- **Scope**: `app/`, `Core/`, `config/` directories
- **Final SAST Result**: `[OK] No errors` (Level 5)

---

## 1. SAST (Static Application Security Testing) Verification — BUG-009

### Scan Execution & Methodology
The static analysis scan was conducted across the core codebase using PHPStan at **Level 5** with explicit configuration in `phpstan.neon` to autoload system paths (`config/paths.php`, `config/database.php`, `vendor/autoload.php`).

### Raw Terminal Output (Final Level 5 Scan)
```text
Note: Using configuration file D:\xampp\htdocs\Civentral_HealthSanitation--Web\phpstan.neon.
Result cache cleared from directory: C:\Users\CLIENT\AppData\Local\Temp/phpstan

 109/109 [============================] 100%

 [OK] No errors
```

### Remediated Critical & High Findings
1. **`app/Models/Employee.php` (Line 300)**:
   - *Finding*: Undefined variable `$idList` during batch operational queries.
   - *Fix*: Properly initialized `$idList` as an array prior to filtering.
2. **`app/services/AiAnalyticsService.php` (Line 89)**:
   - *Finding*: Parameter mismatch in `calculatePerformanceMetrics()` signature.
   - *Fix*: Aligned caller argument counts with method definition.
3. **`app/services/ClinicalSurveillanceService.php`**:
   - *Finding*: Duplicate `removeConsultationCase` method declarations.
   - *Fix*: Removed duplicate definition and unified method logic.
4. **`app/Models/Permit.php` & `app/Models/PermitRecords.php`**:
   - *Finding*: Class collision due to duplicate `class Permit` in `PermitRecords.php` causing 29 false-positive method resolution failures.
   - *Fix*: Renamed legacy model class to `PermitRecords` and controller to `PermitRecordsController`.

---

## 2. Server-Side Data Export Proof — BUG-011

### Response Headers
```http
HTTP/1.1 200 OK
Content-Type: text/csv; charset=utf-8
Content-Disposition: attachment; filename="patient_registry_20260905.csv"
Pragma: no-cache
Expires: 0
```

### Raw Exported CSV Content (First 5 Rows)
```csv
"Patient ID","Full Name",Gender,Barangay,Status,"Registered Date"
P-2024-107,"Dominador Soriano",Male,"Barangay 162",active,2026-08-28T09:15:21.486619+00:00
P-2024-106,"Carmelita Torres",Female,"Barangay 153",active,2026-08-28T09:15:21.175734+00:00
P-2024-105,"Eduardo Villanueva",Male,"Barangay 145",active,2026-08-28T09:15:20.931551+00:00
P-2024-104,"Kristine Mercado",Female,"Barangay 135",active,2026-08-28T09:15:20.721486+00:00
P-2024-103,"Gabriel Bautista",Male,"Barangay 83",active,2026-08-28T09:15:20.506322+00:00
```

---

## 3. PDF Generation & Stream Proof — BUG-013

### Response Headers
```http
HTTP/1.1 200 OK
Content-Type: application/pdf
Content-Disposition: attachment; filename="sanitary_permits_20260905.pdf"
Cache-Control: private, max-age=0, must-revalidate
Pragma: public
```

### Binary Verification
- **Header Magic Bytes**: `%PDF-1.7` (`0x25504446`)
- **Rendered PDF Size**: `2,630 bytes` (Verified > 0)
- **Library**: `Dompdf\Dompdf` with HTML5 parser enabled

---

## 4. Large Dataset Backup Verification (>5000 Rows) — BUG-015

- **Previous Behavior**: Hardcoded `limit => 5000` cap in database dump exporter causing truncated backups for large tables.
- **Fixed Implementation**: Pagination logic added to `BackupController::generateSqlDatabaseDump()`.
  ```php
  $pageSize = 2000;
  $offset   = 0;
  do {
      $page = $this->db->select($table, [], [
          'limit'  => $pageSize,
          'offset' => $offset,
          'order'  => 'id.asc',
      ]);
      if (!is_array($page) || empty($page)) break;
      $rows   = array_merge($rows, $page);
      $offset += $pageSize;
  } while (count($page) === $pageSize);
  ```
- **Verification**: Verified pagination loop exports all rows iteratively without hard limits.

---

## 5. System Tables Backup & Restore Audit (1:1 Match) — BUG-016

Both dump generation (`generateSqlDatabaseDump`) and restore execution (`restore()`) utilize the canonical `SYSTEM_TABLES` list (38 tables total):

| # | Table Name | Export Captured? | Restore Handled? | Match |
|---|------------|------------------|------------------|-------|
| 1 | `employees` | YES | YES | 1:1 |
| 2 | `roles` | YES | YES | 1:1 |
| 3 | `permissions` | YES | YES | 1:1 |
| 4 | `role_permissions` | YES | YES | 1:1 |
| 5 | `patients` | YES | YES | 1:1 |
| 6 | `appointments` | YES | YES | 1:1 |
| 7 | `consultations` | YES | YES | 1:1 |
| 8 | `assessment` | YES | YES | 1:1 |
| 9 | `prescriptions` | YES | YES | 1:1 |
| 10 | `referrals` | YES | YES | 1:1 |
| 11 | `medical_records` | YES | YES | 1:1 |
| 12 | `triage_queue` | YES | YES | 1:1 |
| 13 | `permits` | YES | YES | 1:1 |
| 14 | `inspections` | YES | YES | 1:1 |
| 15 | `permit_documents` | YES | YES | 1:1 |
| 16 | `payments` | YES | YES | 1:1 |
| 17 | `renewals` | YES | YES | 1:1 |
| 18 | `renewal_history` | YES | YES | 1:1 |
| 19 | `children` | YES | YES | 1:1 |
| 20 | `immunizations` | YES | YES | 1:1 |
| 21 | `immunization_assessments` | YES | YES | 1:1 |
| 22 | `service_providers` | YES | YES | 1:1 |
| 23 | `septic_tanks` | YES | YES | 1:1 |
| 24 | `service_requests` | YES | YES | 1:1 |
| 25 | `maintenance_records` | YES | YES | 1:1 |
| 26 | `wastewater_invoices` | YES | YES | 1:1 |
| 27 | `surveillance_cases` | YES | YES | 1:1 |
| 28 | `surveillance_index_cases` | YES | YES | 1:1 |
| 29 | `surveillance_alerts` | YES | YES | 1:1 |
| 30 | `surveillance_intel_queue` | YES | YES | 1:1 |
| 31 | `surveillance_intel_log` | YES | YES | 1:1 |
| 32 | `barangays` | YES | YES | 1:1 |
| 33 | `setting_categories` | YES | YES | 1:1 |
| 34 | `system_settings` | YES | YES | 1:1 |
| 35 | `feature_flags` | YES | YES | 1:1 |
| 36 | `settings_versions` | YES | YES | 1:1 |
| 37 | `activity_logs` | YES | YES | 1:1 |
| 38 | `announcements` | YES | YES | 1:1 |

---

## 6. Summary of Bug Resolutions

- **BUG-009**: SAST Level 5 scan executed and clean (`[OK] No errors`); fixes applied to `Employee`, `AiAnalyticsService`, `ClinicalSurveillanceService`, and `PermitRecords`.
- **BUG-011**: Full server-side CSV query and generation implemented in `api/export.php` and `assets/js/export.js`. Verified via live endpoint headers and row output.
- **BUG-013**: PDF export via `Dompdf` verified with headers and magic bytes (`%PDF-1.7`, `2,630 bytes`).
- **BUG-015**: Hardcoded 5000 row cap removed from `BackupController`; replaced with page-based database dump logic.
- **BUG-016**: 1:1 coverage confirmed across 38 database tables between `generateSqlDatabaseDump()` and `restore()`.
