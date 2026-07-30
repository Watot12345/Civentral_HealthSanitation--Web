-- database/migrations/2026_07_30_create_surveillance_tables.sql
-- ============================================================
-- HEALTH SURVEILLANCE MODULE DATABASE SCHEMAS & INITIAL SEED
-- ============================================================

-- 1. Surveillance Cases Table
CREATE TABLE IF NOT EXISTS public.surveillance_cases (
    id SERIAL PRIMARY KEY,
    case_code VARCHAR(50) UNIQUE,
    disease VARCHAR(100) NOT NULL,
    patient_name VARCHAR(150) NOT NULL,
    age INT NOT NULL DEFAULT 0,
    gender VARCHAR(20) NOT NULL DEFAULT 'Unknown',
    address TEXT,
    barangay VARCHAR(100) NOT NULL,
    contact_number VARCHAR(50),
    symptoms TEXT,
    onset_date DATE,
    reporting_facility VARCHAR(150),
    status VARCHAR(50) NOT NULL DEFAULT 'Suspected',
    severity VARCHAR(50) NOT NULL DEFAULT 'Moderate',
    reported_by VARCHAR(150),
    investigator_id VARCHAR(100),
    investigation_notes TEXT,
    contact_tracing_done BOOLEAN DEFAULT FALSE,
    outbreak_id VARCHAR(50),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 2. Surveillance Index Cases Table
CREATE TABLE IF NOT EXISTS public.surveillance_index_cases (
    id SERIAL PRIMARY KEY,
    index_code VARCHAR(50) UNIQUE,
    name VARCHAR(150) NOT NULL,
    age INT NOT NULL DEFAULT 0,
    gender VARCHAR(20) NOT NULL DEFAULT 'Unknown',
    barangay VARCHAR(100) NOT NULL,
    disease VARCHAR(100) NOT NULL,
    date_confirmed DATE,
    status VARCHAR(50) NOT NULL DEFAULT 'Isolated',
    risk_level VARCHAR(50) NOT NULL DEFAULT 'High',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 3. Surveillance Contacts Table
CREATE TABLE IF NOT EXISTS public.surveillance_contacts (
    id SERIAL PRIMARY KEY,
    contact_code VARCHAR(50) UNIQUE,
    index_case_id INT,
    name VARCHAR(150) NOT NULL,
    age INT NOT NULL DEFAULT 0,
    gender VARCHAR(20) NOT NULL DEFAULT 'Unknown',
    relationship VARCHAR(100),
    address TEXT,
    barangay VARCHAR(100) NOT NULL,
    exposure_type VARCHAR(100),
    exposure_date DATE,
    last_contact_date DATE,
    symptoms TEXT,
    monitoring_status VARCHAR(50) NOT NULL DEFAULT 'Under Monitoring',
    quarantine_status VARCHAR(50) NOT NULL DEFAULT 'Quarantined',
    quarantine_start DATE,
    quarantine_end DATE,
    risk_level VARCHAR(50) NOT NULL DEFAULT 'Medium',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 4. Surveillance Alerts Table
CREATE TABLE IF NOT EXISTS public.surveillance_alerts (
    id SERIAL PRIMARY KEY,
    alert_code VARCHAR(50) UNIQUE,
    disease VARCHAR(100) NOT NULL,
    barangay VARCHAR(100) NOT NULL,
    cases INT NOT NULL DEFAULT 0,
    threshold INT NOT NULL DEFAULT 10,
    severity VARCHAR(50) NOT NULL DEFAULT 'Warning',
    status VARCHAR(50) NOT NULL DEFAULT 'Active',
    timestamp TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    escalation_level INT NOT NULL DEFAULT 1,
    assigned_to VARCHAR(150),
    response_actions TEXT,
    message TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 5. Surveillance Response Teams Table
CREATE TABLE IF NOT EXISTS public.surveillance_response_teams (
    id SERIAL PRIMARY KEY,
    team_code VARCHAR(50) UNIQUE,
    name VARCHAR(150) NOT NULL,
    leader VARCHAR(150) NOT NULL,
    members TEXT,
    specialization VARCHAR(150),
    status VARCHAR(50) NOT NULL DEFAULT 'Available',
    deployed_to VARCHAR(150),
    last_deployment TIMESTAMP WITH TIME ZONE,
    contact VARCHAR(50),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 6. Surveillance Resources Table
CREATE TABLE IF NOT EXISTS public.surveillance_resources (
    id SERIAL PRIMARY KEY,
    resource_code VARCHAR(50) UNIQUE,
    name VARCHAR(150) NOT NULL,
    category VARCHAR(100) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    unit VARCHAR(50) NOT NULL DEFAULT 'pcs',
    location VARCHAR(150),
    status VARCHAR(50) NOT NULL DEFAULT 'Available',
    last_restock DATE,
    threshold INT NOT NULL DEFAULT 10,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 7. Surveillance Interventions Table
CREATE TABLE IF NOT EXISTS public.surveillance_interventions (
    id SERIAL PRIMARY KEY,
    intervention_code VARCHAR(50) UNIQUE,
    title VARCHAR(200) NOT NULL,
    type VARCHAR(100) NOT NULL,
    location VARCHAR(150),
    status VARCHAR(50) NOT NULL DEFAULT 'In Progress',
    start_date DATE,
    end_date DATE,
    team_lead VARCHAR(150),
    progress INT NOT NULL DEFAULT 0,
    activities TEXT,
    resources_used TEXT,
    outcomes TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- SEED OPERATIONAL DATA (SAMPLE RECORDS FROM SPECIFICATION)
-- ============================================================

INSERT INTO public.surveillance_cases (case_code, disease, patient_name, age, gender, address, barangay, contact_number, symptoms, onset_date, reporting_facility, status, severity, reported_by, investigator_id, investigation_notes, contact_tracing_done, outbreak_id) VALUES
('CS-2026-001', 'Dengue Fever', 'Juan Dela Cruz', 34, 'Male', '123 Mabini St', 'San Jose', '0917-123-4567', 'High Fever, Severe Headache, Joint Pain', '2026-07-20', 'San Jose Health Center', 'Confirmed', 'High', 'Dr. Reyes', 'INV-101', 'Patient confirmed Dengue NS1 positive. Larvicidal treatment conducted around household.', true, 'OB-2026-01'),
('CS-2026-002', 'Dengue Fever', 'Maria Santos', 28, 'Female', '45 Rizal Ave', 'San Jose', '0918-234-5678', 'High Fever, Rash, Muscle Pain', '2026-07-21', 'City General Hospital', 'Confirmed', 'Critical', 'Dr. Reyes', 'INV-101', 'Admitted to ICU. Household contacts screened.', true, 'OB-2026-01'),
('CS-2026-003', 'Measles', 'Pedro Penduko', 5, 'Male', '78 Bonifacio St', 'Poblacion', '0919-345-6789', 'Fever, Rash, Cough, Runny Nose', '2026-07-22', 'Poblacion Clinic', 'Suspected', 'Moderate', 'Nurse Ana', 'INV-102', 'Sample collected for laboratory confirmation.', false, null),
('CS-2026-004', 'Chikungunya', 'Elena Torres', 42, 'Female', '12 Luna St', 'San Jose', '0920-456-7890', 'Joint Pain, Rash, Mild Fever', '2026-07-23', 'San Jose Health Center', 'Investigating', 'Moderate', 'Dr. Reyes', 'INV-101', 'Pending serology test results.', false, 'OB-2026-01'),
('CS-2026-005', 'COVID-19', 'Roberto Garcia', 58, 'Male', '99 Aguinaldo St', 'Sta. Cruz', '0921-567-8901', 'Cough, Loss of Taste, Mild Fever', '2026-07-24', 'Sta. Cruz RHU', 'Confirmed', 'Mild', 'Dr. Lim', 'INV-103', 'Home isolation ordered for 7 days.', true, null)
ON CONFLICT (case_code) DO NOTHING;

INSERT INTO public.surveillance_index_cases (index_code, name, age, gender, barangay, disease, date_confirmed, status, risk_level) VALUES
('IDX-2026-001', 'Juan Dela Cruz', 34, 'Male', 'San Jose', 'Dengue Fever', '2026-07-20', 'Isolated', 'High'),
('IDX-2026-002', 'Maria Santos', 28, 'Female', 'San Jose', 'Dengue Fever', '2026-07-21', 'Hospitalized', 'Critical'),
('IDX-2026-003', 'Roberto Garcia', 58, 'Male', 'Sta. Cruz', 'COVID-19', '2026-07-24', 'Home Isolation', 'Moderate')
ON CONFLICT (index_code) DO NOTHING;

INSERT INTO public.surveillance_contacts (contact_code, index_case_id, name, age, gender, relationship, address, barangay, exposure_type, exposure_date, last_contact_date, symptoms, monitoring_status, quarantine_status, quarantine_start, quarantine_end, risk_level) VALUES
('CT-2026-001', 1, 'Anna Dela Cruz', 32, 'Female', 'Spouse', '123 Mabini St', 'San Jose', 'Direct Household', '2026-07-19', '2026-07-20', 'None', 'Under Monitoring', 'Quarantined', '2026-07-20', '2026-08-03', 'High'),
('CT-2026-002', 1, 'Mark Dela Cruz', 8, 'Male', 'Child', '123 Mabini St', 'San Jose', 'Direct Household', '2026-07-19', '2026-07-20', 'Mild Fever', 'Symptomatic', 'Quarantined', '2026-07-20', '2026-08-03', 'High'),
('CT-2026-003', 2, 'Jose Santos', 30, 'Male', 'Spouse', '45 Rizal Ave', 'San Jose', 'Direct Household', '2026-07-20', '2026-07-21', 'None', 'Under Monitoring', 'Quarantined', '2026-07-21', '2026-08-04', 'High'),
('CT-2026-004', 3, 'Liza Garcia', 55, 'Female', 'Spouse', '99 Aguinaldo St', 'Sta. Cruz', 'Direct Household', '2026-07-23', '2026-07-24', 'None', 'Cleared', 'Completed', '2026-07-24', '2026-07-30', 'Low')
ON CONFLICT (contact_code) DO NOTHING;

INSERT INTO public.surveillance_alerts (alert_code, disease, barangay, cases, threshold, severity, status, timestamp, escalation_level, assigned_to, response_actions, message) VALUES
('ALT-2026-001', 'Dengue Fever', 'San Jose', 12, 10, 'Critical', 'Active', '2026-07-30 10:15:00+00', 3, 'Dr. Reyes', 'Misting operations scheduled. Community cleanup drive initiated.', 'Dengue outbreak threshold exceeded in Barangay San Jose (12 cases vs 10 threshold).'),
('ALT-2026-002', 'Measles', 'Poblacion', 3, 2, 'Warning', 'Active', '2026-07-30 08:30:00+00', 2, 'Nurse Ana', 'Supplemental immunization activity prepared for children under 5.', 'Measles cluster detected in Barangay Poblacion (3 suspected cases).'),
('ALT-2026-003', 'COVID-19', 'Sta. Cruz', 5, 15, 'Informational', 'Resolved', '2026-07-29 14:20:00+00', 1, 'Dr. Lim', 'Contact tracing completed. All household contacts isolated.', 'COVID-19 case cluster in Sta. Cruz resolved.')
ON CONFLICT (alert_code) DO NOTHING;

INSERT INTO public.surveillance_response_teams (team_code, name, leader, members, specialization, status, deployed_to, last_deployment, contact) VALUES
('TM-2026-01', 'Epidemiology Rapid Response Team Alpha', 'Dr. Manuel Reyes', 'Nurse Sarah, Tech Mark, Inspector Liza', 'Vector Control & Contact Tracing', 'Deployed', 'Barangay San Jose', '2026-07-30 09:00:00+00', '0917-999-1111'),
('TM-2026-02', 'Immunization Task Force Beta', 'Nurse Ana Santos', 'Midwife Grace, Educator Emma', 'Vaccine Outreach & Screening', 'Available', null, '2026-07-28 15:30:00+00', '0918-888-2222'),
('TM-2026-03', 'Environmental Sanitation Squad', 'Inspector Kevin Reyes', 'Officer Ramon, Tech Carlo', 'Disinfection & Misting', 'Available', null, '2026-07-29 11:00:00+00', '0919-777-3333')
ON CONFLICT (team_code) DO NOTHING;

INSERT INTO public.surveillance_resources (resource_code, name, category, quantity, unit, location, status, last_restock, threshold) VALUES
('RES-2026-001', 'Dengue NS1 Rapid Test Kits', 'Diagnostics', 450, 'kits', 'Main Central Stock', 'Sufficient', '2026-07-25', 100),
('RES-2026-002', 'Permethrin Vector Insecticide', 'Vector Control', 85, 'liters', 'Sanitation Warehouse', 'Low Stock', '2026-07-15', 100),
('RES-2026-003', 'N95 Respirator Masks', 'PPE', 1200, 'pcs', 'Main Central Stock', 'Sufficient', '2026-07-20', 300),
('RES-2026-004', 'Measles Vaccines (MR)', 'Vaccines', 320, 'doses', 'Cold Chain Storage', 'Sufficient', '2026-07-22', 150)
ON CONFLICT (resource_code) DO NOTHING;

INSERT INTO public.surveillance_interventions (intervention_code, title, type, location, status, start_date, end_date, team_lead, progress, activities, resources_used, outcomes) VALUES
('INT-2026-001', 'Barangay San Jose Dengue Vector Suppression', 'Vector Control', 'Barangay San Jose', 'In Progress', '2026-07-30', '2026-08-05', 'Dr. Manuel Reyes', 65, 'Targeted fogging, larvicidal application in standing water, community cleanup.', '85L Permethrin, 50 Spray Kits', 'Vector density reduced by 40% in priority zones.'),
('INT-2026-002', 'Poblacion Measles Catch-up Vaccination', 'Immunization', 'Barangay Poblacion', 'Scheduled', '2026-08-01', '2026-08-03', 'Nurse Ana Santos', 15, 'Door-to-door child immunization, parent counseling.', '200 MR Vaccine Doses', 'Targeting 250 unvaccinated children.')
ON CONFLICT (intervention_code) DO NOTHING;
