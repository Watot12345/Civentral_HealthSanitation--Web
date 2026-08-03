-- database/migrations/2026_08_02_seed_test_analytics_data.sql
-- ============================================================
-- SEED TEST DATA FOR AI ANALYTICS & DASHBOARD GRAPH TESTING
-- MATCHING STRICT SUPABASE_SCHEMA.SQL CONSTRAINTS
-- ============================================================

-- 1. Insert Test Disease Surveillance Cases
INSERT INTO public.surveillance_cases 
(patient_name, disease, status, severity, barangay, age, gender, created_at)
VALUES 
('Dela Cruz, Juan', 'Cholera Outbreak', 'Active', 'Critical', 'San Jose', 34, 'Male', NOW()),
('Santos, Maria', 'Typhoid Fever', 'Confirmed', 'Moderate', 'Poblacion', 28, 'Female', NOW()),
('Reyes, Roberto', 'Dengue Fever', 'Active', 'Critical', 'Bagong Silang', 42, 'Male', NOW()),
('Bautista, Ana', 'Measles Outbreak', 'Active', 'Moderate', 'Camarin', 19, 'Female', NOW()),
('Gonzales, Pedro', 'Chikungunya', 'Investigating', 'Moderate', 'Riverside', 51, 'Male', NOW());

-- 2. Insert Test Patient Registrations (matching patients schema in Supabase_Schema.sql)
INSERT INTO public.patients 
(patient_id, first_name, last_name, birth_date, gender, contact, address, barangay, registration_date, status, created_at)
VALUES 
('P-99001', 'Alexander', 'Tan', '1995-05-15', 'Male', '09171112233', '123 San Jose St', 'San Jose', CURRENT_DATE, 'active', NOW()),
('P-99002', 'Beatriz', 'Mercado', '1990-08-20', 'Female', '09172223344', '456 Poblacion St', 'Poblacion', CURRENT_DATE, 'active', NOW()),
('P-99003', 'Crispin', 'Ramos', '1988-11-10', 'Male', '09173334455', '789 Camarin St', 'Camarin', CURRENT_DATE, 'active', NOW());

-- 3. Insert Test Sanitation Permits (matching permits schema in Supabase_Schema.sql)
INSERT INTO public.permits 
(permit_id, applicant, business_name, business_type, address, owner_name, contact, fee, status, created_at)
VALUES 
('PERM-88001', 'Elena Vasquez', 'Metro Food Mart', 'Food Establishment', '101 San Jose St', 'Elena Vasquez', '09181112233', 500.00, 'approved', NOW()),
('PERM-88002', 'Fernando Cruz', 'Caloocan Water Depot', 'Water Refilling', '202 Bagong Silang St', 'Fernando Cruz', '09182223344', 750.00, 'approved', NOW());

-- 4. Insert Test Outbreak Alert
INSERT INTO public.surveillance_alerts 
(disease, barangay, cases, threshold, severity, status, timestamp)
VALUES 
('Cholera Outbreak', 'San Jose', 18, 10, 'Critical', 'Active', NOW());
