# 🛡️ Invalid File Detection & Upload Security Test Report

**System:** Civentral Health & Sanitation MIS
**Audit Date:** 2026-09-09 10:20:20
**Audited Validator:** `FileUploadValidator::validate()` (`app/helpers/FileUploadValidator.php`)
**Test Suite Result:** ✅ **VERIFIED PASS (100% Threat Neutralization)**

---

## 1. Executive Summary

In compliance with Section 4.4 of the Civentral System QA Checklist (Invalid File Detection), this report documents negative security testing against the server-side file upload ingestion pipeline. The system enforces deep binary byte inspection via `finfo_file()`, extension allowlists, a 10 MB file size ceiling, and structural parsing verification.

## 2. Test Execution Matrix

| Test ID | Attack / Error Vector | File Tested | HTTP Code | Error Response Message | Result |
|:---|:---|:---|:---:|:---|:---:|
| **TC-01** | Spoofed Executable (ELF Binary -> .csv) | *test-artifact* | `422` | MIME type mismatch: extension '.csv' claimed, but actual byte content is 'application/x-executable'. | ✅ PASS |
| **TC-02** | Oversized File (11 MB > 10 MB limit) | *test-artifact* | `413` | File size (11 MB) exceeds maximum allowed limit of 10 MB. | ✅ PASS |
| **TC-03** | Prohibited Extension: malware.exe | *test-artifact* | `422` | File extension '.exe' is not permitted. Allowed: csv, xlsx, json, pdf | ✅ PASS |
| **TC-04** | Prohibited Extension: exploit.sh | *test-artifact* | `422` | File extension '.sh' is not permitted. Allowed: csv, xlsx, json, pdf | ✅ PASS |
| **TC-05** | Prohibited Extension: backdoor.php | *test-artifact* | `422` | File extension '.php' is not permitted. Allowed: csv, xlsx, json, pdf | ✅ PASS |
| **TC-06** | Prohibited Extension: xss_trap.svg | *test-artifact* | `422` | File extension '.svg' is not permitted. Allowed: csv, xlsx, json, pdf | ✅ PASS |
| **TC-07** | Malformed JSON Syntax | *test-artifact* | `422` | Malformed JSON structure: Syntax error | ✅ PASS |
| **TC-08** | Empty / Headerless CSV | *test-artifact* | `422` | Malformed or empty CSV structure: no valid column headers detected. | ✅ PASS |

---

## 3. Defense Mechanisms Evaluated

1. **Deep MIME Inspection (`finfo_file`)**: Inspects binary magic numbers rather than relying on client-supplied headers or file extensions. Rejects disguised ELF/PE binaries with `422 Unprocessable Entity`.
2. **Size Guardrail**: Enforces a strict 10 MB limit (`10485760 bytes`). Payloads exceeding this limit receive an immediate `413 Payload Too Large` error without exhausting web worker memory.
3. **Extension Allowlist**: Rejects executable scripts (`.exe`, `.sh`, `.php`, `.svg`) with `422 Unprocessable Entity`.
4. **Structural Parser Guard**: Validates JSON syntax using `json_decode()` and CSV header presence using `fgetcsv()` prior to staging records into the database.

---

## 4. Auditor Sign-Off

**Lead QA & Security Engineer:** Civentral Automated Security Suite  
**Checklist Item 4.4 Status:** **PASS** (BUG-012 Resolved)
