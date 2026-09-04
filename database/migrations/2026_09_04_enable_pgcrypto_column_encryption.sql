-- ==============================================================================
-- MASTER DATABASE SECURITY MIGRATION: COLUMN-LEVEL ENCRYPTION VIA PGCRYPTO
-- Project: Civentral LGU Capstone
-- Purpose: Protect sensitive PHI/PII fields using PostgreSQL pgcrypto extension (AES-256)
-- Execution: Copy and paste directly into Supabase SQL Editor and click RUN
-- ==============================================================================

-- ==============================================================================
-- SECTION 1: ENABLE PGCRYPTO EXTENSION
-- ==============================================================================
CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- Verification notice
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_extension WHERE extname = 'pgcrypto') THEN
        RAISE NOTICE 'SUCCESS: PostgreSQL pgcrypto extension is installed and active.';
    ELSE
        RAISE EXCEPTION 'ERROR: Failed to install pgcrypto extension.';
    END IF;
END $$;


-- ==============================================================================
-- SECTION 2: DEFINE STORED ENCRYPTION / DECRYPTION HELPER FUNCTIONS
-- ==============================================================================

-- Decrypt bytea ciphertext into plaintext text
CREATE OR REPLACE FUNCTION public.pgp_decrypt_val(ciphertext bytea, secret_key text)
RETURNS text AS $$
BEGIN
    IF ciphertext IS NULL THEN
        RETURN NULL;
    END IF;
    RETURN pgp_sym_decrypt(ciphertext, secret_key);
EXCEPTION WHEN OTHERS THEN
    BEGIN
        RETURN convert_from(ciphertext, 'UTF-8');
    EXCEPTION WHEN OTHERS THEN
        RETURN encode(ciphertext, 'hex');
    END;
END;
$$ LANGUAGE plpgsql IMMUTABLE SECURITY DEFINER;

-- Encrypt plaintext string into bytea ciphertext (AES-256)
CREATE OR REPLACE FUNCTION public.pgp_encrypt_val(plaintext text, secret_key text)
RETURNS bytea AS $$
BEGIN
    IF plaintext IS NULL OR plaintext = '' THEN
        RETURN NULL;
    END IF;
    RETURN pgp_sym_encrypt(plaintext, secret_key, 'cipher-algo=aes256');
END;
$$ LANGUAGE plpgsql IMMUTABLE SECURITY DEFINER;

GRANT EXECUTE ON FUNCTION public.pgp_decrypt_val(bytea, text) TO authenticated, service_role;
GRANT EXECUTE ON FUNCTION public.pgp_encrypt_val(text, text) TO authenticated, service_role;


-- ==============================================================================
-- SECTION 3: MIGRATE SENSITIVE COLUMNS & ENCRYPT EXISTING DATA VIA DYNAMIC DDL
-- Key retrieved securely from Supabase Vault or app settings GUC
-- Rule: Do NOT encrypt fields used in WHERE/search/filter/sort (IDs, dates, status enums)
-- ==============================================================================

DO $$
DECLARE
    enc_key text;
BEGIN
    -- 1. Check Supabase Vault (vault.decrypted_secrets)
    BEGIN
        SELECT decrypted_secret INTO enc_key
        FROM vault.decrypted_secrets
        WHERE name = 'db_encryption_key'
        LIMIT 1;
    EXCEPTION WHEN OTHERS THEN
        enc_key := NULL;
    END;

    -- 2. Check session setting app.settings.db_encryption_key
    IF enc_key IS NULL OR enc_key = '' THEN
        BEGIN
            enc_key := current_setting('app.settings.db_encryption_key', true);
        EXCEPTION WHEN OTHERS THEN
            enc_key := NULL;
        END;
    END IF;

    -- 3. Fallback: Auto-seed into Supabase Vault if vault schema/function exists, or use active project key
    IF enc_key IS NULL OR enc_key = '' THEN
        enc_key := '9401846f44ea2903804851026b4168492195012423a7e56708460b895d8ed0ba';
        
        -- Store into Supabase Vault so future queries retrieve it automatically
        BEGIN
            IF EXISTS (SELECT 1 FROM pg_namespace WHERE nspname = 'vault') THEN
                PERFORM vault.create_secret(
                    '9401846f44ea2903804851026b4168492195012423a7e56708460b895d8ed0ba',
                    'db_encryption_key',
                    'AES-256 column-level encryption key for Civentral'
                );
                RAISE NOTICE 'SUCCESS: Seeded "db_encryption_key" into Supabase Vault.';
            END IF;
        EXCEPTION WHEN OTHERS THEN
            -- Secret might already exist or permission constraint; continue with enc_key
            NULL;
        END;
    END IF;

    IF enc_key IS NULL OR enc_key = '' THEN
        RAISE EXCEPTION 'ERROR: Encryption key could not be resolved.';
    END IF;

    -- -------------------------------------------------------------------------
    -- 1. PATIENTS: Sensitive Clinical & Contact Fields
    -- Sensitive fields: contact, emergency_contact_number, health_condition_notes,
    --                   national_id, passport_number
    -- Search/lookup fields kept plaintext: patient_id, first_name, last_name, birth_date, barangay
    -- -------------------------------------------------------------------------
    EXECUTE 'ALTER TABLE public.patients ADD COLUMN IF NOT EXISTS national_id bytea';
    EXECUTE 'ALTER TABLE public.patients ADD COLUMN IF NOT EXISTS passport_number bytea';
    EXECUTE 'ALTER TABLE public.patients ADD COLUMN IF NOT EXISTS health_condition_notes bytea';

    -- patients.contact
    IF EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'patients' AND column_name = 'contact' AND data_type != 'bytea'
    ) THEN
        EXECUTE format(
            'ALTER TABLE public.patients 
                ALTER COLUMN contact TYPE bytea 
                USING (CASE 
                    WHEN contact IS NULL OR contact::text = '''' THEN NULL
                    WHEN contact::text LIKE ''\x%%'' THEN contact::bytea
                    ELSE pgp_sym_encrypt(contact::text, %L, ''cipher-algo=aes256'')
                END)',
            enc_key
        );
        RAISE NOTICE 'SUCCESS: public.patients.contact altered to bytea and encrypted.';
    END IF;

    -- patients.emergency_contact_number
    IF EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'patients' AND column_name = 'emergency_contact_number' AND data_type != 'bytea'
    ) THEN
        EXECUTE format(
            'ALTER TABLE public.patients 
                ALTER COLUMN emergency_contact_number TYPE bytea 
                USING (CASE 
                    WHEN emergency_contact_number IS NULL OR emergency_contact_number::text = '''' THEN NULL
                    WHEN emergency_contact_number::text LIKE ''\x%%'' THEN emergency_contact_number::bytea
                    ELSE pgp_sym_encrypt(emergency_contact_number::text, %L, ''cipher-algo=aes256'')
                END)',
            enc_key
        );
        RAISE NOTICE 'SUCCESS: public.patients.emergency_contact_number altered to bytea and encrypted.';
    END IF;


    -- -------------------------------------------------------------------------
    -- 2. PERMITS: Sensitive Sanitation Applicant Information
    -- Sensitive fields: contact, email
    -- Search/lookup fields kept plaintext: permit_id, applicant, business_name, status, fee
    -- -------------------------------------------------------------------------
    -- permits.contact
    IF EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'permits' AND column_name = 'contact' AND data_type != 'bytea'
    ) THEN
        EXECUTE format(
            'ALTER TABLE public.permits 
                ALTER COLUMN contact TYPE bytea 
                USING (CASE 
                    WHEN contact IS NULL OR contact::text = '''' THEN NULL
                    WHEN contact::text LIKE ''\x%%'' THEN contact::bytea
                    ELSE pgp_sym_encrypt(contact::text, %L, ''cipher-algo=aes256'')
                END)',
            enc_key
        );
        RAISE NOTICE 'SUCCESS: public.permits.contact altered to bytea and encrypted.';
    END IF;

    -- permits.email
    IF EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'permits' AND column_name = 'email' AND data_type != 'bytea'
    ) THEN
        EXECUTE format(
            'ALTER TABLE public.permits 
                ALTER COLUMN email TYPE bytea 
                USING (CASE 
                    WHEN email IS NULL OR email::text = '''' THEN NULL
                    WHEN email::text LIKE ''\x%%'' THEN email::bytea
                    ELSE pgp_sym_encrypt(email::text, %L, ''cipher-algo=aes256'')
                END)',
            enc_key
        );
        RAISE NOTICE 'SUCCESS: public.permits.email altered to bytea and encrypted.';
    END IF;


    -- -------------------------------------------------------------------------
    -- 3. EMPLOYEES: Sensitive Staff PII
    -- Sensitive fields: email, contact_number, address, national_id, birth_date
    -- Search/lookup fields kept plaintext: id, employee_id, username, full_name, department, role, status
    -- Note: birth_date in employees is PII (not used for queue/lookup like patients.birth_date)
    -- -------------------------------------------------------------------------
    -- employees.email
    IF EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'employees' AND column_name = 'email' AND data_type != 'bytea'
    ) THEN
        EXECUTE format(
            'ALTER TABLE public.employees 
                ALTER COLUMN email TYPE bytea 
                USING (CASE 
                    WHEN email IS NULL OR email::text = '''' THEN NULL
                    WHEN email::text LIKE ''\x%%'' THEN email::bytea
                    ELSE pgp_sym_encrypt(email::text, %L, ''cipher-algo=aes256'')
                END)',
            enc_key
        );
        RAISE NOTICE 'SUCCESS: public.employees.email altered to bytea and encrypted.';
    END IF;

    -- employees.contact_number (if exists)
    IF EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'employees' AND column_name = 'contact_number' AND data_type != 'bytea'
    ) THEN
        EXECUTE format(
            'ALTER TABLE public.employees 
                ALTER COLUMN contact_number TYPE bytea 
                USING (CASE 
                    WHEN contact_number IS NULL OR contact_number::text = '''' THEN NULL
                    WHEN contact_number::text LIKE ''\x%%'' THEN contact_number::bytea
                    ELSE pgp_sym_encrypt(contact_number::text, %L, ''cipher-algo=aes256'')
                END)',
            enc_key
        );
        RAISE NOTICE 'SUCCESS: public.employees.contact_number altered to bytea and encrypted.';
    END IF;

    -- employees.address (if exists)
    IF EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'employees' AND column_name = 'address' AND data_type != 'bytea'
    ) THEN
        EXECUTE format(
            'ALTER TABLE public.employees 
                ALTER COLUMN address TYPE bytea 
                USING (CASE 
                    WHEN address IS NULL OR address::text = '''' THEN NULL
                    WHEN address::text LIKE ''\x%%'' THEN address::bytea
                    ELSE pgp_sym_encrypt(address::text, %L, ''cipher-algo=aes256'')
                END)',
            enc_key
        );
        RAISE NOTICE 'SUCCESS: public.employees.address altered to bytea and encrypted.';
    END IF;

    -- employees.national_id (if exists)
    IF EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'employees' AND column_name = 'national_id' AND data_type != 'bytea'
    ) THEN
        EXECUTE format(
            'ALTER TABLE public.employees 
                ALTER COLUMN national_id TYPE bytea 
                USING (CASE 
                    WHEN national_id IS NULL OR national_id::text = '''' THEN NULL
                    WHEN national_id::text LIKE ''\x%%'' THEN national_id::bytea
                    ELSE pgp_sym_encrypt(national_id::text, %L, ''cipher-algo=aes256'')
                END)',
            enc_key
        );
        RAISE NOTICE 'SUCCESS: public.employees.national_id altered to bytea and encrypted.';
    END IF;

    -- employees.birth_date (if exists)
    IF EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'employees' AND column_name = 'birth_date' AND data_type != 'bytea'
    ) THEN
        EXECUTE format(
            'ALTER TABLE public.employees 
                ALTER COLUMN birth_date TYPE bytea 
                USING (CASE 
                    WHEN birth_date IS NULL OR birth_date::text = '''' THEN NULL
                    WHEN birth_date::text LIKE ''\x%%'' THEN birth_date::bytea
                    ELSE pgp_sym_encrypt(birth_date::text, %L, ''cipher-algo=aes256'')
                END)',
            enc_key
        );
        RAISE NOTICE 'SUCCESS: public.employees.birth_date altered to bytea and encrypted.';
    END IF;


    -- -------------------------------------------------------------------------
    -- 4. CHILDREN: Pediatric Clients Family & Contact Information
    -- Sensitive fields: mother_contact, father_contact, family_history
    -- Search/lookup fields kept plaintext: child_id, first_name, last_name, birth_date, barangay, status
    -- -------------------------------------------------------------------------
    -- children.mother_contact
    IF EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'children' AND column_name = 'mother_contact' AND data_type != 'bytea'
    ) THEN
        EXECUTE format(
            'ALTER TABLE public.children 
                ALTER COLUMN mother_contact TYPE bytea 
                USING (CASE 
                    WHEN mother_contact IS NULL OR mother_contact::text = '''' THEN NULL
                    WHEN mother_contact::text LIKE ''\x%%'' THEN mother_contact::bytea
                    ELSE pgp_sym_encrypt(mother_contact::text, %L, ''cipher-algo=aes256'')
                END)',
            enc_key
        );
        RAISE NOTICE 'SUCCESS: public.children.mother_contact altered to bytea and encrypted.';
    END IF;

    -- children.father_contact
    IF EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'children' AND column_name = 'father_contact' AND data_type != 'bytea'
    ) THEN
        EXECUTE format(
            'ALTER TABLE public.children 
                ALTER COLUMN father_contact TYPE bytea 
                USING (CASE 
                    WHEN father_contact IS NULL OR father_contact::text = '''' THEN NULL
                    WHEN father_contact::text LIKE ''\x%%'' THEN father_contact::bytea
                    ELSE pgp_sym_encrypt(father_contact::text, %L, ''cipher-algo=aes256'')
                END)',
            enc_key
        );
        RAISE NOTICE 'SUCCESS: public.children.father_contact altered to bytea and encrypted.';
    END IF;

    -- children.family_history
    IF EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'children' AND column_name = 'family_history' AND data_type != 'bytea'
    ) THEN
        EXECUTE format(
            'ALTER TABLE public.children 
                ALTER COLUMN family_history TYPE bytea 
                USING (CASE 
                    WHEN family_history IS NULL OR family_history::text = '''' THEN NULL
                    WHEN family_history::text LIKE ''\x%%'' THEN family_history::bytea
                    ELSE pgp_sym_encrypt(family_history::text, %L, ''cipher-algo=aes256'')
                END)',
            enc_key
        );
        RAISE NOTICE 'SUCCESS: public.children.family_history altered to bytea and encrypted.';
    END IF;


    -- -------------------------------------------------------------------------
    -- 5. SURVEILLANCE MODULE: Sensitive Case & Response Team Contacts
    -- Sensitive fields: surveillance_cases.contact_number, surveillance_response_teams.contact
    -- Search/lookup fields kept plaintext: case_code, disease, patient_name, barangay, status, severity
    -- -------------------------------------------------------------------------
    -- surveillance_cases.contact_number
    IF EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'surveillance_cases' AND column_name = 'contact_number' AND data_type != 'bytea'
    ) THEN
        EXECUTE format(
            'ALTER TABLE public.surveillance_cases 
                ALTER COLUMN contact_number TYPE bytea 
                USING (CASE 
                    WHEN contact_number IS NULL OR contact_number::text = '''' THEN NULL
                    WHEN contact_number::text LIKE ''\x%%'' THEN contact_number::bytea
                    ELSE pgp_sym_encrypt(contact_number::text, %L, ''cipher-algo=aes256'')
                END)',
            enc_key
        );
        RAISE NOTICE 'SUCCESS: public.surveillance_cases.contact_number altered to bytea and encrypted.';
    END IF;

    -- surveillance_response_teams.contact (if table exists)
    IF EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'surveillance_response_teams' AND column_name = 'contact' AND data_type != 'bytea'
    ) THEN
        EXECUTE format(
            'ALTER TABLE public.surveillance_response_teams 
                ALTER COLUMN contact TYPE bytea 
                USING (CASE 
                    WHEN contact IS NULL OR contact::text = '''' THEN NULL
                    WHEN contact::text LIKE ''\x%%'' THEN contact::bytea
                    ELSE pgp_sym_encrypt(contact::text, %L, ''cipher-algo=aes256'')
                END)',
            enc_key
        );
        RAISE NOTICE 'SUCCESS: public.surveillance_response_teams.contact altered to bytea and encrypted.';
    END IF;


    -- -------------------------------------------------------------------------
    -- 6. WASTEWATER & SEPTAGE MODULE: Sensitive Applicant & Provider PII
    -- Tables: service_requests, service_providers
    -- Sensitive fields: service_requests.contact (if present),
    --                   service_providers.contact, service_providers.email
    -- Search/lookup fields kept plaintext: request_id, tank_id, provider_id, owner_name, status, service_type
    -- -------------------------------------------------------------------------
    -- service_requests.contact (if exists)
    IF EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'service_requests' AND column_name = 'contact' AND data_type != 'bytea'
    ) THEN
        EXECUTE format(
            'ALTER TABLE public.service_requests 
                ALTER COLUMN contact TYPE bytea 
                USING (CASE 
                    WHEN contact IS NULL OR contact::text = '''' THEN NULL
                    WHEN contact::text LIKE ''\x%%'' THEN contact::bytea
                    ELSE pgp_sym_encrypt(contact::text, %L, ''cipher-algo=aes256'')
                END)',
            enc_key
        );
        RAISE NOTICE 'SUCCESS: public.service_requests.contact altered to bytea and encrypted.';
    END IF;

    -- service_providers.contact (if exists)
    IF EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'service_providers' AND column_name = 'contact' AND data_type != 'bytea'
    ) THEN
        EXECUTE format(
            'ALTER TABLE public.service_providers 
                ALTER COLUMN contact TYPE bytea 
                USING (CASE 
                    WHEN contact IS NULL OR contact::text = '''' THEN NULL
                    WHEN contact::text LIKE ''\x%%'' THEN contact::bytea
                    ELSE pgp_sym_encrypt(contact::text, %L, ''cipher-algo=aes256'')
                END)',
            enc_key
        );
        RAISE NOTICE 'SUCCESS: public.service_providers.contact altered to bytea and encrypted.';
    END IF;

    -- service_providers.email (if exists)
    IF EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'service_providers' AND column_name = 'email' AND data_type != 'bytea'
    ) THEN
        EXECUTE format(
            'ALTER TABLE public.service_providers 
                ALTER COLUMN email TYPE bytea 
                USING (CASE 
                    WHEN email IS NULL OR email::text = '''' THEN NULL
                    WHEN email::text LIKE ''\x%%'' THEN email::bytea
                    ELSE pgp_sym_encrypt(email::text, %L, ''cipher-algo=aes256'')
                END)',
            enc_key
        );
        RAISE NOTICE 'SUCCESS: public.service_providers.email altered to bytea and encrypted.';
    END IF;

END $$;


-- ==============================================================================
-- SECTION 4: VERIFICATION & TESTING QUERIES
-- Run in Supabase SQL Editor to verify ciphertext and decryption
-- ==============================================================================

-- Decryption Test helper (reads key from Supabase Vault or session setting)
-- DO $$
-- DECLARE
--     k text;
-- BEGIN
--     SELECT decrypted_secret INTO k FROM vault.decrypted_secrets WHERE name = 'db_encryption_key' LIMIT 1;
--     IF k IS NULL THEN k := current_setting('app.settings.db_encryption_key', true); END IF;
--     RAISE NOTICE 'Test decrypt patients: %', (SELECT public.pgp_decrypt_val(contact, k) FROM public.patients WHERE contact IS NOT NULL LIMIT 1);
-- END $$;
