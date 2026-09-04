# Civentral LGU Platform — Database Security & Column-Level Encryption Documentation

> **Capstone Evidence Document**  
> **Standard:** Republic Act No. 10173 (Data Privacy Act of 2012) & DOH EMR Security Standards  
> **Cryptographic Extension:** PostgreSQL `pgcrypto` (RFC 4880 OpenPGP Symmetric Encryption)  
> **Key Cipher:** AES-256 Symmetric Encryption (`cipher-algo=aes256`)  
> **Key Management:** Sourced securely via Supabase Vault (`vault.decrypted_secrets`) or `current_setting('app.settings.db_encryption_key')`, synchronized with `.env` (`DB_ENCRYPTION_KEY`)  

---

## 1. Executive Summary

In compliance with the **Philippine Data Privacy Act of 2012 (RA 10173)**, the **National Privacy Commission (NPC)** guidelines, and **Department of Health (DOH)** standards for Electronic Medical Records (EMR), the Civentral LGU enterprise system implements **Column-Level Encryption (CLE)** for sensitive Protected Health Information (PHI) and Personally Identifiable Information (PII).

Database column-level encryption guarantees that even in the event of an unauthorized database dump, SQL snapshot export, or compromised database backup, sensitive citizen contact details, medical records, and government identifiers remain encrypted and unreadable without the cryptographic key.

---

## 2. Architecture & Cryptographic Methodology

### 2.1 Encryption Standard
- **PostgreSQL Extension:** `pgcrypto`
- **Specification:** RFC 4880 OpenPGP Message Format
- **Symmetric Cipher:** **AES-256** (Advanced Encryption Standard with 256-bit keys)
- **Database Column Data Type:** `bytea` (Binary Large Object storing OpenPGP packets)
- **Key Derivation:** String-to-Key (S2K) Iterated and Salted hashing to prevent rainbow table attacks

### 2.2 Key Management Lifecycle
1. **Generation:** Cryptographically secure 256-bit random key generated via `random_bytes(32)`.
2. **Key Storage & Supabase Vault Integration:**
   - **Database Layer:** Key sourced from **Supabase Vault** (`vault.decrypted_secrets` where `name = 'db_encryption_key'`) or runtime session setting (`current_setting('app.settings.db_encryption_key', true)`). Hardcoded literal key values in migration scripts are strictly prohibited.
   - **Application Layer:** Stored in environment configuration (`.env` as `DB_ENCRYPTION_KEY`) and accessed securely through [`Env::get('DB_ENCRYPTION_KEY')`](file:///opt/lampp/htdocs/capstone/Core/Env.php).
3. **No Hardcoding:** Hardcoding encryption keys in migration files or application source code is strictly prohibited.

---

## 3. Inventory of Encrypted Sensitive Fields per Module

| Module | Table Name | Column Name | Sensitive Category | Data Type | Encryption Rationale |
|---|---|---|---|---|---|
| **Clinical Health Services** | `public.patients` | `contact` | Patient PII (Contact Number) | `bytea` | Mobile/telephone number of patient (RA 10173 PII protection). |
| **Clinical Health Services** | `public.patients` | `emergency_contact_number` | Patient PII | `bytea` | Confidential emergency contact telephone number. |
| **Clinical Health Services** | `public.patients` | `health_condition_notes` | Clinical PHI | `bytea` | Sensitive medical diagnostics, chronic conditions, and clinical notes. |
| **Clinical Health Services** | `public.patients` | `national_id` | Government Identifier | `bytea` | Philippine PhilSys Card Number / UMID (High Identity Theft Risk). |
| **Clinical Health Services** | `public.patients` | `passport_number` | Government Identifier | `bytea` | International Travel Identification Number. |
| **Environmental Sanitation** | `public.permits` | `contact` | Sanitation Applicant PII | `bytea` | Contact information of business owners and sanitary permit applicants. |
| **Environmental Sanitation** | `public.permits` | `email` | Sanitation Applicant PII | `bytea` | Private email address of business owners and applicants. |
| **User & Staff Management** | `public.employees` | `email` | Staff Information | `bytea` | Work/personal email addresses of municipal employees and doctors. |
| **User & Staff Management** | `public.employees` | `contact_number` | Staff PII | `bytea` | Personal mobile contact of staff members (if present). |
| **User & Staff Management** | `public.employees` | `address` | Staff PII | `bytea` | Residential address of municipal staff (if present). |
| **User & Staff Management** | `public.employees` | `national_id` | Government Identifier | `bytea` | National ID / Government identifier of municipal personnel (if present). |
| **User & Staff Management** | `public.employees` | `birth_date` | Staff PII | `bytea` | Date of birth of employee (if present in employees table). |
| **Pediatric & Immunization** | `public.children` | `mother_contact` | Pediatric Client PII | `bytea` | Maternal contact telephone for pediatric and immunization patients. |
| **Pediatric & Immunization** | `public.children` | `father_contact` | Pediatric Client PII | `bytea` | Paternal contact telephone for pediatric and immunization patients. |
| **Pediatric & Immunization** | `public.children` | `family_history` | Sensitive Health Records | `bytea` | Hereditary health risks and family medical background. |
| **Disease Surveillance** | `public.surveillance_cases` | `contact_number` | Disease Surveillance PII | `bytea` | Patient contact number for epidemiology outbreak investigation. |
| **Disease Surveillance** | `public.surveillance_response_teams` | `contact` | Field Responder PII | `bytea` | Contact telephone of rapid response team members (if present). |
| **Wastewater & Septage** | `public.service_requests` | `contact` | Applicant / Citizen PII | `bytea` | Contact number of citizen requesting desludging/inspection (if present). |
| **Wastewater & Septage** | `public.service_providers` | `contact` | Provider Contact PII | `bytea` | Direct telephone of desludging/septic service contractors (if present). |
| **Wastewater & Septage** | `public.service_providers` | `email` | Provider Email PII | `bytea` | Email address of septic service contractors (if present). |

---

## 4. Fields Excluded from Encryption (Preserved in Plaintext)

As dictated by enterprise database design and system performance standards, **fields involved in search, sorting, filtering, and relational joins remain in plaintext**:

| Field Group | Examples | Rationale for Keeping Plaintext |
|---|---|---|
| **Primary & Foreign Keys** | `id`, `patient_id`, `permit_id`, `employee_id`, `case_code` | Foreign key constraints, indexing, and joins across tables require exact match operations. |
| **Searchable Names** | `first_name`, `last_name`, `applicant`, `business_name` | Essential for B-Tree indexing and `ILIKE` auto-complete search in health center and permit queues. |
| **Date & Time Fields** | `birth_date` (patients/children), `registration_date`, `created_at`, `inspection_date` | Enables range filtering, chronological sorting, and epidemiological timeline queries. |
| **Status Enums** | `status`, `gender`, `blood_type`, `priority`, `role`, `department` | Utilized in KPI metrics aggregation, dashboard statistics, and role-based access filtering. |

---

## 5. SQL Syntax (Supabase SQL Editor Direct Queries)

### 5.1 Extension Activation
```sql
CREATE EXTENSION IF NOT EXISTS pgcrypto;
```

### 5.2 Dynamic Migration Execution with Supabase Vault Key
```sql
-- Migration automatically fetches key from Supabase Vault:
-- SELECT decrypted_secret FROM vault.decrypted_secrets WHERE name = 'db_encryption_key'
-- Or session setting:
-- SET app.settings.db_encryption_key = '<your_key>';
```

### 5.3 Decrypted Read Query
```sql
-- Using the centralized helper function:
SELECT 
    id, 
    patient_id, 
    first_name, 
    last_name, 
    public.pgp_decrypt_val(contact, (SELECT decrypted_secret FROM vault.decrypted_secrets WHERE name = 'db_encryption_key' LIMIT 1)) AS contact
FROM public.patients 
LIMIT 5;
```

---

## 6. PHP Model Layer Implementation (`EncryptionHelper.php`)

To prevent scattering cryptographic code throughout controllers and views, encryption logic is centralized in [`app/helpers/EncryptionHelper.php`](file:///opt/lampp/htdocs/capstone/app/helpers/EncryptionHelper.php).

### 6.1 Helper Features
- **RFC 4880 OpenPGP Compatibility:** Directly compatible with PostgreSQL `pgp_sym_encrypt` and `pgp_sym_decrypt`.
- **Automatic In-Memory Caching:** Prevents redundant cryptographic process invocations during high-throughput page rendering.
- **Fail-Safe Fallbacks:** Transparently handles legacy unencrypted records and provides AES-256-CBC fallback.
- **Zero Double-Encryption:** Detects `\x` hex bytea and skips re-encryption.

### 6.2 Model Integration Example (`Patient.php`)

```php
// SELECT (Read)
public function all(array $options = []): array
{
    $results = $this->db->select($this->table, [], $options);
    return EncryptionHelper::decryptRows($this->table, $results);
}

// INSERT (Write)
public function create(array $data): array
{
    $encryptedData = EncryptionHelper::encryptModel($this->table, $data);
    $res = $this->db->insert($this->table, $encryptedData, true);
    return is_array($res) ? EncryptionHelper::decryptModel($this->table, $res) : $res;
}

// UPDATE (Write)
public function updateById(string $id, array $data): array
{
    $encryptedData = EncryptionHelper::encryptModel($this->table, $data);
    $updated = $this->db->update($this->table, $encryptedData, ['id' => 'eq.' . $id], true);
    return is_array($updated) ? EncryptionHelper::decryptRows($this->table, $updated) : $updated;
}
```

---

## 7. Capstone Verification & Testing Steps

To demonstrate compliance during oral defense and technical review:

1. **Step 1: Inspect Supabase Vault / Configuration**
   Confirm that `db_encryption_key` is registered in Supabase Vault (`vault.decrypted_secrets`) or initialized via session variable, matching `.env` `DB_ENCRYPTION_KEY`.

2. **Step 2: Execute Migration Script**
   Open Supabase Dashboard → **SQL Editor** → Open file [`database/migrations/2026_09_04_enable_pgcrypto_column_encryption.sql`](file:///opt/lampp/htdocs/capstone/database/migrations/2026_09_04_enable_pgcrypto_column_encryption.sql) → Click **RUN**.

3. **Step 3: Direct Query Table Inspection**
   Run `SELECT patient_id, first_name, last_name, contact FROM public.patients;` in Supabase SQL editor.
   - **Expected Result:** `contact` displays hex bytea ciphertext (e.g. `\x8c0d0409030a...` or `\xc30d...`), confirming that direct database access reveals no readable citizen phone numbers.

4. **Step 4: Decrypted Query Verification**
   Run query using `public.pgp_decrypt_val(contact, key)`.
   - **Expected Result:** Output displays original readable contact number.

5. **Step 5: Frontend Verification**
   Navigate to Civentral Health Services portal (`modules/healthservices/patients.php`).
   - Patient list displays telephone numbers normally due to automatic `EncryptionHelper::decryptRows()` decoding at the model layer.
