-- database/migrations/2026_10_03_seed_professional_data.sql
-- ============================================================================
-- CIVENTRAL LGU - PROFESSIONAL ENTERPRISE SEED DATA (INSERT-ONLY)
-- Matches Supabase schema constraints, foreign keys, and Philippine LGU domains.
-- Safe to run multiple times: uses ON CONFLICT DO NOTHING to preserve all existing data.
-- ============================================================================

-- 1. SEED PATIENTS (Primary Healthcare & Clinical Services)
INSERT INTO public.patients 
(patient_id, first_name, middle_name, last_name, birth_date, gender, blood_type, contact, email, address, barangay, emergency_contact, emergency_contact_number, allergies, medical_history, registration_date, status, created_at)
VALUES 
('P-2026-001', 'Ricardo', 'Dela Cruz', 'Alcantara', '1968-04-12', 'Male', 'O+', '09173456781', 'ricardo.alcantara@example.ph', '78 Rizal Avenue, Grace Park West', 'Barangay 8', 'Corazon Alcantara (Spouse)', '09173456782', 'Penicillin', 'Hypertension Stage 2 (Dx 2018); Dyslipidemia', '2026-01-15', 'active', NOW()),
('P-2026-002', 'Jocelyn', 'Villanueva', 'Morales', '1996-09-23', 'Female', 'A+', '09184567892', 'jocelyn.morales@example.ph', '104 5th Avenue, Grace Park East', 'Barangay 12', 'Eduardo Morales (Husband)', '09184567893', 'None known', 'G1P0 Pregnancy at 28 weeks AOG; Mild iron-deficiency anemia', '2026-02-10', 'active', NOW()),
('P-2026-003', 'Eduardo', 'Soriano', 'Manalo', '1982-11-05', 'Male', 'B+', '09195678903', 'eduardo.manalo@example.ph', '214 Samson Road, Sangandaan', 'Barangay 77', 'Carmelita Manalo (Sister)', '09195678904', 'Sulfa drugs', 'Type 2 Diabetes Mellitus (Dx 2021); Mild peripheral neuropathy', '2026-02-28', 'active', NOW()),
('P-2026-004', 'Patricia Mae', 'Galang', 'Reyes', '2004-03-18', 'Female', 'AB+', '09206789014', 'patricia.reyes@example.ph', '321 C-3 Road, Maypajo', 'Barangay 80', 'Lourdes Reyes (Mother)', '09206789015', 'Shellfish', 'Bronchial Asthma since childhood; seasonal allergic rhinitis', '2026-03-05', 'active', NOW()),
('P-2026-005', 'Benigno', 'Cruz', 'Tolentino', '1955-08-30', 'Male', 'O+', '09217890125', 'benigno.tolentino@example.ph', '56 Morning Breeze Subdivision', 'Barangay 82', 'Francis Tolentino (Son)', '09217890126', 'Aspirin', 'Osteoarthritis bilateral knees; Benign Prostatic Hyperplasia (BPH)', '2026-03-12', 'active', NOW())
ON CONFLICT (patient_id) DO NOTHING;

-- 2. SEED APPOINTMENTS
INSERT INTO public.appointments 
(appointment_id, patient_id, employee_id, service_type, appointment_date, appointment_time, status, priority, notes, reminder_sent, created_at)
SELECT 
'APT-202610-001', p.id, 3, 'Hypertension Follow-up', '2026-10-05', '09:00:00', 'approved', 'medium', 'Quarterly cardiovascular review and maintenance prescription renewal.', true, NOW()
FROM public.patients p WHERE p.patient_id = 'P-2026-001'
ON CONFLICT (appointment_id) DO NOTHING;

INSERT INTO public.appointments 
(appointment_id, patient_id, employee_id, service_type, appointment_date, appointment_time, status, priority, notes, reminder_sent, created_at)
SELECT 
'APT-202610-002', p.id, 4, 'Prenatal Checkup', '2026-10-06', '10:30:00', 'approved', 'high', '3rd trimester fundal height measurement & fetal heartbeat auscultation.', true, NOW()
FROM public.patients p WHERE p.patient_id = 'P-2026-002'
ON CONFLICT (appointment_id) DO NOTHING;

-- 3. SEED TRIAGE QUEUE & CLINICAL ASSESSMENTS
INSERT INTO public.triage_queue 
(queue_number, patient_id, reason_for_visit, status, check_in_time, created_at)
SELECT 
'Q-20261003-002', p.id, 'Medical Consultation', 'completed', NOW(), NOW()
FROM public.patients p WHERE p.patient_id = 'P-2026-001'
ON CONFLICT (queue_number) DO NOTHING;

INSERT INTO public.assessment 
(triage_id, patient_id, nurse_id, blood_pressure, heart_rate, temperature, respiratory_rate, oxygen_saturation, weight, height, symptoms, priority, allergies, medications, notes, status, created_at)
SELECT 
'TRG-202610-001', p.id, 4, '138/88', 78, 36.6, 18, 98, 74.5, 168.0, 'Occasional morning occipital headache, no chest pain or shortness of breath.', 'medium', 'Penicillin', 'Amlodipine 5mg OD, Losartan 50mg OD', 'Blood pressure mildly elevated above target; referred to Doctor for medication titration.', 'consulted', NOW()
FROM public.patients p WHERE p.patient_id = 'P-2026-001'
ON CONFLICT (triage_id) DO NOTHING;

-- 4. SEED CONSULTATIONS & PRESCRIPTIONS
INSERT INTO public.consultations 
(consultation_id, patient_id, employee_id, date, time, diagnosis, icd_code, symptoms, treatment_plan, notes, follow_up_date, status, created_at)
SELECT 
'CONS-2026-0004', p.id, 3, '2026-10-03', '09:00:00', 'Essential (Primary) Hypertension, Uncontrolled', 'I10', 'Morning occipital tension, elevated clinic BP readings averaging 138/88 mmHg.', 'Maintain Amlodipine 5mg OD; adjust Losartan from 50mg to 100mg OD. Low-salt low-fat DASH diet emphasized. Regular morning home BP logging.', 'Patient advised on dietary sodium reduction and scheduled for 4-week BP re-check.', '2026-10-31', 'completed', NOW()
FROM public.patients p WHERE p.patient_id = 'P-2026-001'
ON CONFLICT (consultation_id) DO NOTHING;

INSERT INTO public.prescriptions 
(prescription_id, patient_id, employee_id, date, medications, notes, status, dispensed_by, dispensed_at, created_at)
SELECT 
'RX-2026-000041', p.id, 3, '2026-10-03', 
'[{"id":1,"name":"Losartan Potassium","dosage":"100mg","frequency":"Once daily in the morning","duration":"30 days","quantity":30},{"id":2,"name":"Amlodipine Besylate","dosage":"5mg","frequency":"Once daily in the evening","duration":"30 days","quantity":30}]'::jsonb, 
'Take with water after meals. Report any dizziness, ankle swelling, or persistent cough.', 'dispensed', 1, NOW(), NOW()
FROM public.patients p WHERE p.patient_id = 'P-2026-001'
ON CONFLICT (prescription_id) DO NOTHING;

-- 5. SEED CHILDREN (Child Health, Immunization & Nutrition)
INSERT INTO public.children 
(child_id, first_name, middle_name, last_name, gender, birth_date, birth_weight, birth_height, blood_type, address, barangay, mother_name, mother_contact, mother_occupation, father_name, father_contact, father_occupation, family_history, allergies, health_center, registration_date, status, nutrition_status, vaccine_compliance, last_visit, created_at)
VALUES 
('CHD-2026-010', 'Mateo', 'Aquino', 'Dela Cruz', 'Male', '2026-01-20', 3.35, 50.0, 'O+', '42 Gen. San Miguel St, Sangandaan', 'Barangay 8', 'Elena Aquino Dela Cruz', '09176543210', 'Bank Teller', 'Mark Dela Cruz', '09187654329', 'Mechanical Technician', 'No known congenital abnormalities; non-contributory.', 'None known', 'Bagong Barrio Health Center', '2026-01-22', 'active', 'Normal', 85, '2026-07-20', NOW()),
('CHD-2026-011', 'Althea', 'Santos', 'Bautista', 'Female', '2025-11-15', 2.95, 48.5, 'A+', '88 10th Avenue, Grace Park', 'Barangay 12', 'Maria Santos Bautista', '09198765431', 'Homemaker', 'Renato Bautista', '09209876542', 'Delivery Driver', 'Maternal bronchial asthma history.', 'None known', 'Bagong Barrio Health Center', '2025-11-18', 'active', 'Normal', 100, '2026-08-15', NOW())
ON CONFLICT (child_id) DO NOTHING;

-- 6. SEED ENVIRONMENTAL SANITATION PERMITS & INSPECTIONS
INSERT INTO public.permits 
(permit_id, applicant, business_name, business_type, address, owner_name, contact, email, fee, paid, payment_method, payment_reference, status, inspector_id, inspection_date, approved_date, expiry_date, notes, created_at)
VALUES 
('SP-2026-1001', 'Ronaldo C. Mendoza', 'Aquasafe Purified Water Refilling Station', 'Water Refilling Station', '234 8th Avenue, Grace Park', 'Ronaldo C. Mendoza', '09171234891', 'aquasafe.caloocan@example.ph', 1800.00, true, 'Cash', 'OR-2026-08912', 'approved', 10, '2026-09-18', '2026-09-20', '2027-09-20', 'Passed microbiological and physical-chemical potable water standards.', NOW()),
('SP-2026-1002', 'Elena D. Santos', 'Aling Nena''s Traditional Pan de Sal Bakery', 'Bakery & Food Processing', '56 Samson Road, Sangandaan', 'Elena D. Santos', '09182345892', 'alingnena.bakery@example.ph', 2200.00, true, 'GCash', 'GC-20260922-8921', 'approved', 10, '2026-09-22', '2026-09-24', '2027-09-24', 'Food handlers possess valid health cards; stainless steel food preparation tables verified.', NOW()),
('SP-2026-1003', 'Danilo V. Cruz', 'Bulalohan sa Caloocan Eatery', 'Food Establishment', '112 Rizal Avenue Extension', 'Danilo V. Cruz', '09193456893', 'bulalohan.caloocan@example.ph', 2500.00, true, 'Cash', 'OR-2026-09144', 'approved', 10, '2026-09-25', '2026-09-27', '2027-09-27', 'Functional grease trap installed; proper waste segregation compliant.', NOW())
ON CONFLICT (permit_id) DO NOTHING;

-- 7. SEED SANITATION RENEWALS
INSERT INTO public.renewals 
(renewal_id, permit_id, applicant, business_type, current_fee, renewal_fee, status, payment_method, payment_reference, date_applied, date_approved, new_expiry_date, notes, created_at)
SELECT 
'REN-2026-1001', p.id, 'Ronaldo C. Mendoza', 'Water Refilling Station', 1800.00, 1800.00, 'approved', 'Cash', 'OR-2026-08912', '2026-09-15', '2026-09-20', '2027-09-20', 'Annual renewal processed; laboratory testing certificate verified.', NOW()
FROM public.permits p WHERE p.permit_id = 'SP-2026-1001'
ON CONFLICT (renewal_id) DO NOTHING;

-- 8. SEED SEPTAGE & WASTEWATER SERVICES
INSERT INTO public.service_providers 
(provider_id, name, contact, email, address, license_number, specialization, rating, status, equipment_count, completed_jobs, response_time, certification, joined_date, notes, created_at)
VALUES 
('PRV-2026-001', 'Metro Enviro-Clean Septic Services Inc.', '09178901234', 'dispatch@metroenviroclean.ph', 'Km 14 McArthur Highway, Caloocan City', 'DENR-EMB-WMS-2025-081', 'desludging', 4.9, 'active', 6, 248, '24-48 hours', 'ISO 14001:2015 Environmental Management', '2025-01-10', 'Primary contracted municipal vacuum tanker fleet operator.', NOW()),
('PRV-2026-002', 'GreenEarth Desludging & Sanitation Corp.', '09189012345', 'operations@greenearth-sanitation.ph', '28 C-3 Road, Dagat-Dagatan, Caloocan City', 'DENR-EMB-WMS-2025-114', 'maintenance', 4.7, 'active', 4, 175, 'Same-day emergency response', 'DOH Certified Septage Hauler', '2025-03-15', 'Specializes in commercial grease traps & confined-space tank repairs.', NOW())
ON CONFLICT (provider_id) DO NOTHING;

INSERT INTO public.septic_tanks 
(tank_id, owner_name, address, barangay, latitude, longitude, capacity, type, installation_year, last_maintenance, maintenance_frequency, status, notes, created_at)
VALUES 
('ST-261001-01', 'Barangay 8 Multi-Purpose Hall', 'Barangay 8 Hall Complex, 4th Avenue', 'Barangay 8', 14.6515, 120.9840, '5000 Liters', 'Concrete', 2022, '2026-08-10', 24, 'good', 'LGU Government building 3-chamber septic vault; easily accessible from road.', NOW()),
('ST-261001-02', 'St. Gabriel Parish Community Center', '120 7th Avenue, Grace Park', 'Barangay 12', 14.6550, 120.9875, '4000 Liters', 'Concrete', 2020, '2025-11-15', 36, 'good', 'Dual-compartment septic tank serving parochial school & parish offices.', NOW())
ON CONFLICT (tank_id) DO NOTHING;

INSERT INTO public.service_requests 
(request_id, tank_id, owner_name, address, barangay, service_type, preferred_date, preferred_time, assigned_to, provider_id, status, priority, notes, rating, created_at)
VALUES 
('SR-2026-0006', 'ST-261001-01', 'Barangay 8 Multi-Purpose Hall', 'Barangay 8 Hall Complex, 4th Avenue', 'Barangay 8', 'desludging', '2026-08-10', '10:00:00', 'Ramon Flores', 'PRV-2026-001', 'completed', 'medium', 'Bi-annual government facility scheduled desludging completed.', 5, NOW())
ON CONFLICT (request_id) DO NOTHING;

INSERT INTO public.maintenance_records 
(service_id, tank_id, owner_name, address, service_type, scheduled_date, scheduled_time, technician, provider_id, status, completed_date, completed_time, findings, recommendations, notes, cost, rating, created_at)
VALUES 
('MR-2026-001', 'ST-261001-01', 'Barangay 8 Multi-Purpose Hall', 'Barangay 8 Hall Complex, 4th Avenue', 'desludging', '2026-08-10', '10:00:00', 'Ramon Flores', 'PRV-2026-001', 'completed', '2026-08-10', '11:45:00', 'Scum layer 32cm, sludge layer 45cm. Effluent outlet tee baffle intact and functional.', 'Schedule next desludging in 24 months. Effluent discharge within city environmental parameters.', 'Full pump-out executed. Hauled to Maynilad Dagat-Dagatan septage treatment facility.', 3500.00, 5, NOW())
ON CONFLICT (service_id) DO NOTHING;

INSERT INTO public.wastewater_invoices 
(invoice_id, client_name, tank_id, service_request_id, provider_id, service_type, amount, tax, total_amount, status, payment_method, payment_reference, invoice_date, due_date, paid_at, notes, created_at)
VALUES 
('INV-2026-007', 'Barangay 8 Multi-Purpose Hall', 'ST-261001-01', 'SR-2026-0006', 'PRV-2026-001', 'Desludging', 3500.00, 0.00, 3500.00, 'paid', 'Landbank Check', 'LBP-CHK-2026-0810-01', '2026-08-10', '2026-08-25', '2026-08-12 09:30:00+00', 'Official City Sanitation Septage Clearance OSS-2026-0810 issued.', NOW())
ON CONFLICT (invoice_id) DO NOTHING;

-- 9. SEED EPIDEMIOLOGICAL SURVEILLANCE & ALERTS
INSERT INTO public.surveillance_cases 
(case_code, disease, patient_name, age, gender, address, barangay, contact_number, symptoms, onset_date, reporting_facility, status, severity, reported_by, investigator_id, investigation_notes, created_at)
VALUES 
('CAS-2026-DEN-001', 'Dengue Fever', 'Mark Anthony Ramos', 14, 'Male', '45 Libis Baesa', 'Barangay 8', '09172348911', 'High fever, retro-orbital pain, myalgia, petechiae on both forearms', '2026-09-24', 'Caloocan City Medical Center', 'Resolved', 'Moderate', 'Dr. Santos', 18, 'NS1 rapid test positive. Neighborhood larvicidal application and fogging conducted on Sept 26.', NOW()),
('CAS-2026-DEN-002', 'Dengue Fever', 'Hannah Nicole Gomez', 9, 'Female', '72 6th Avenue, Grace Park', 'Barangay 8', '09183458922', 'Sudden onset fever, headache, vomiting, epistaxis (nosebleed)', '2026-09-26', 'Bagong Barrio Health Center', 'Active', 'Critical', 'Nurse Ana Reyes', 18, 'Dengue with warning signs; platelet count 75,000/uL. Admitted for IV hydration.', NOW()),
('CAS-2026-LEP-001', 'Leptospirosis', 'Danilo E. Hernandez', 41, 'Male', '102 C-3 Road, Maypajo', 'Barangay 80', '09194568933', 'Calf muscle tenderness, conjunctival suffusion, chills after wading in floodwaters', '2026-09-28', 'Caloocan City Medical Center', 'Active', 'Moderate', 'Dr. Juan Dela Cruz', 19, 'History of wading in floodwaters during Habagat rains without protective boots. Doxycycline therapy started.', NOW())
ON CONFLICT (case_code) DO NOTHING;

INSERT INTO public.surveillance_alerts 
(alert_code, disease, barangay, cases, threshold, severity, status, timestamp, escalation_level, assigned_to, message, response_actions, created_at)
VALUES 
('ALT-DEN-2640-01', 'Dengue Fever', 'Barangay 8', 5, 3, 'Critical', 'Active', '2026-10-01 08:00:00+00', 2, 'Sofia Lim (Surveillance Lead)', 'Dengue cluster confirmed in Barangay 8: 5 confirmed cases in past 14 days exceeding epidemic threshold (3 cases). Targeted misting and search-and-destroy cleanup launched.', '1. Community indoor and outdoor misting conducted; 2. Distribution of Olyset mosquito nets; 3. House-to-house fever surveillance.', NOW())
ON CONFLICT (alert_code) DO NOTHING;

-- 10. SEED LGU HEALTH ANNOUNCEMENTS
INSERT INTO public.announcements 
(title, category, audience, body, author, file_url, is_active, created_at)
VALUES 
('Chikiting Ligtas 2026: Supplemental Measles-Rubella & Polio Vaccination Campaign', 'Immunization Drive', 'All Staff', 'The City Health Department hereby announces the launch of the Chikiting Ligtas nationwide supplemental immunization drive starting October 15, 2026. All health center personnel, midwives, and barangay health workers (BHWs) are directed to coordinate with assigned day care centers and public elementary schools for house-to-house and fixed-site vaccination of all children aged 0-59 months.', 'Maria Santos (Health Center Director)', null, true, NOW()),
('Sanitation Code Advisory: Q4 Sanitary Permit & Health Certificate Renewal', 'Environmental Sanitation', 'All Staff', 'In accordance with the Caloocan City Sanitation Code, all food establishments, commercial bakeries, water refilling stations, and wet market stalls are advised that the pre-renewal inspection period for 2027 Sanitary Permits opens on November 3, 2026. All food handlers must present updated chest X-ray and stool examination results for health certificate renewals.', 'Pedro Garcia (Sanitation Director)', null, true, NOW())
ON CONFLICT (title) DO NOTHING;
