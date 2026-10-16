<?php
// app/Models/Patient.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../helpers/EncryptionHelper.php';

class Patient
{
    private Database $db;
    private string $table = 'patients';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function all(array $options = []): array
    {
        try {
            $results = $this->db->select($this->table, [], $options);
            return EncryptionHelper::decryptRows($this->table, $results);
        } catch (\Throwable $e) {
            error_log("Patient::all error: " . $e->getMessage());
            return [];
        }
    }

    public function find(string $id): ?array
    {
        $result = $this->db->select($this->table, ['id' => 'eq.' . $id]);
        return !empty($result) ? EncryptionHelper::decryptModel($this->table, $result[0]) : null;
    }

    public function findByPatientId(string $patientId): ?array
    {
        $result = $this->db->select($this->table, ['patient_id' => 'eq.' . $patientId]);
        return !empty($result) ? EncryptionHelper::decryptModel($this->table, $result[0]) : null;
    }

    public function create(array $data): array
    {
        $encryptedData = EncryptionHelper::encryptModel($this->table, $data);
        $res = $this->db->insert($this->table, $encryptedData, true);
        if (is_array($res) && isset($res[0]) && is_array($res[0])) {
            $res = $res[0];
        }
        if (class_exists('ActivityLog') || file_exists(__DIR__ . '/ActivityLog.php')) {
            require_once __DIR__ . '/ActivityLog.php';
            try {
                $logger = new ActivityLog();
                $name = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
                $pid = $data['patient_id'] ?? ($res['patient_id'] ?? '');
                $logger->log("Added Patient: {$name}", [
                    'module'  => 'Health Center Services',
                    'details' => "Patient ID: {$pid} | Barangay: " . ($data['barangay'] ?? 'N/A'),
                    'status'  => 'Success'
                ]);
            } catch (Throwable $e) {
                error_log('Patient::create ActivityLog error: ' . $e->getMessage());
            }
        }
        return is_array($res) ? EncryptionHelper::decryptModel($this->table, $res) : $res;
    }

    public function updateById(string $id, array $data): array
    {
        $encryptedData = EncryptionHelper::encryptModel($this->table, $data);
        $updated = $this->db->update($this->table, $encryptedData, ['id' => 'eq.' . $id], true);
        if (is_array($updated) && isset($updated[0]) && is_array($updated[0])) {
            $updated = $updated[0];
        }
        if (class_exists('ActivityLog') || file_exists(__DIR__ . '/ActivityLog.php')) {
            require_once __DIR__ . '/ActivityLog.php';
            try {
                $logger = new ActivityLog();
                $name = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
                $logger->log("Updated Patient Record: " . ($name ?: "ID {$id}"), [
                    'module'  => 'Health Center Services',
                    'details' => "Updated patient record #{$id}",
                    'status'  => 'Success'
                ]);
            } catch (Throwable $e) {
                error_log('Patient::updateById ActivityLog error: ' . $e->getMessage());
            }
        }
        return is_array($updated) ? EncryptionHelper::decryptModel($this->table, $updated) : $updated;
    }

    public function deleteById(string $id): bool
    {
        $this->db->delete($this->table, ['id' => 'eq.' . $id], true);
        if (class_exists('ActivityLog') || file_exists(__DIR__ . '/ActivityLog.php')) {
            require_once __DIR__ . '/ActivityLog.php';
            try {
                $logger = new ActivityLog();
                $logger->log("Deleted Patient Record", [
                    'module'  => 'Health Center Services',
                    'details' => "Removed patient record #{$id}",
                    'status'  => 'Success'
                ]);
            } catch (Throwable $e) {
                error_log('Patient::deleteById ActivityLog error: ' . $e->getMessage());
            }
        }
        return true;
    }

    public function search(string $query): array
    {
        $all = $this->all();
        $query = strtolower($query);
        return array_values(array_filter($all, function($p) use ($query) {
            return str_contains(strtolower($p['first_name'] ?? ''), $query) ||
                   str_contains(strtolower($p['last_name'] ?? ''), $query) ||
                   str_contains(strtolower($p['patient_id'] ?? ''), $query) ||
                   str_contains(strtolower($p['barangay'] ?? ''), $query);
        }));
    }

    /**
     * Retrieve complete patient clinical history across all health service modules.
     * Aggregates consultations, triage/vitals, prescriptions, appointments, referrals, and legacy records.
     *
     * @param string|int $id Patient primary key or patient_id code
     * @return array Enriched history dataset with timeline and categorized records
     */
    public function getHistory(string|int $id): array
    {
        $patient = $this->find((string)$id);
        if (!$patient && is_string($id)) {
            $patient = $this->findByPatientId($id);
        }
        if (!$patient) {
            return [];
        }

        $pId = (int)$patient['id'];

        // Build employee lookup map for doctor and staff names
        $employeesMap = [];
        try {
            $rawEmployees = $this->db->select('employees', [], ['select' => 'id,full_name,name,first_name,last_name,department,role_description']);
            foreach ($rawEmployees as $e) {
                $name = trim($e['full_name'] ?? (($e['first_name'] ?? '') . ' ' . ($e['last_name'] ?? '')));
                if (empty($name)) {
                    $name = $e['name'] ?? ("Staff #{$e['id']}");
                }
                $displayName = (str_starts_with($name, 'Dr.') || str_starts_with($name, 'Doctor')) ? $name : ('Dr. ' . $name);
                $employeesMap[$e['id']] = $displayName;
            }
        } catch (\Throwable $e) {
            error_log("Patient::getHistory employee lookup error: " . $e->getMessage());
        }

        $consultations = [];
        $triages = [];
        $prescriptions = [];
        $appointments = [];
        $referrals = [];
        $legacyRecords = [];
        $immunizations = [];

        // 1. Consultations
        try {
            $consultations = $this->db->select('consultations', ['patient_id' => 'eq.' . $pId], ['order' => 'date.desc,created_at.desc']);
        } catch (\Throwable $e) {
            error_log("Patient::getHistory consultations error: " . $e->getMessage());
        }

        // 2. Triage & Vitals (checks 'assessment' first, fallbacks to 'triage')
        try {
            $triages = $this->db->select('assessment', ['patient_id' => 'eq.' . $pId], ['order' => 'created_at.desc']);
            if (empty($triages)) {
                $fallback = $this->db->select('triage', ['patient_id' => 'eq.' . $pId], ['order' => 'created_at.desc']);
                if (!empty($fallback)) {
                    $triages = $fallback;
                }
            }
        } catch (\Throwable $e) {
            try {
                $triages = $this->db->select('triage', ['patient_id' => 'eq.' . $pId], ['order' => 'created_at.desc']);
            } catch (\Throwable $e2) {
                error_log("Patient::getHistory triage error: " . $e2->getMessage());
            }
        }

        // 3. Prescriptions
        try {
            $prescriptions = $this->db->select('prescriptions', ['patient_id' => 'eq.' . $pId], ['order' => 'date.desc,created_at.desc']);
        } catch (\Throwable $e) {
            error_log("Patient::getHistory prescriptions error: " . $e->getMessage());
        }

        // 4. Appointments
        try {
            $appointments = $this->db->select('appointments', ['patient_id' => 'eq.' . $pId], ['order' => 'appointment_date.desc,created_at.desc']);
        } catch (\Throwable $e) {
            error_log("Patient::getHistory appointments error: " . $e->getMessage());
        }

        // 5. Referrals
        try {
            $referrals = $this->db->select('referrals', ['patient_id' => 'eq.' . $pId], ['order' => 'created_at.desc']);
        } catch (\Throwable $e) {
            error_log("Patient::getHistory referrals error: " . $e->getMessage());
        }

        // 6. Medical Records (Legacy archive)
        try {
            $legacyRecords = $this->db->select('medical_records', ['patient_id' => 'eq.' . $pId], ['order' => 'date.desc,created_at.desc']);
        } catch (\Throwable $e) {
            error_log("Patient::getHistory legacy medical_records error: " . $e->getMessage());
        }

        // 7. Immunizations
        try {
            $immunizations = $this->db->select('immunizations', ['patient_id' => 'eq.' . $pId], ['order' => 'date_administered.desc']);
        } catch (\Throwable $e) {
            error_log("Patient::getHistory immunizations error: " . $e->getMessage());
        }

        // Build unified chronological timeline
        $timeline = [];

        // Add Consultations
        foreach ($consultations as $c) {
            $docName = $c['doctor_name'] ?? ($employeesMap[$c['employee_id'] ?? 0] ?? 'Attending Doctor');
            $dateStr = $c['date'] ?? (substr($c['created_at'] ?? '', 0, 10));
            $timeStr = $c['time'] ?? '';
            $ts = strtotime("$dateStr $timeStr") ?: (strtotime($c['created_at'] ?? '') ?: time());
            $timeline[] = [
                'id'           => 'cons_' . ($c['id'] ?? uniqid()),
                'type'         => 'consultation',
                'category'     => 'consultation',
                'timestamp'    => $ts,
                'date'         => $dateStr,
                'time'         => $timeStr,
                'date_display' => $dateStr . ($timeStr ? " • $timeStr" : ''),
                'title'        => 'Doctor Consultation: ' . (!empty($c['diagnosis']) ? $c['diagnosis'] : 'General Consultation'),
                'provider'     => $docName,
                'badge'        => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                'badge_label'  => 'Consultation',
                'icon'         => 'fa-stethoscope text-emerald-600',
                'summary'      => 'Diagnosis: ' . ($c['diagnosis'] ?? 'N/A') . (!empty($c['icd_code']) ? " (ICD-10: {$c['icd_code']})" : ''),
                'details'      => [
                    'consultation_id' => $c['consultation_id'] ?? ('CONS-' . ($c['id'] ?? '')),
                    'diagnosis'       => $c['diagnosis'] ?? 'N/A',
                    'icd_code'        => $c['icd_code'] ?? '',
                    'symptoms'        => $c['symptoms'] ?? '',
                    'notes'           => $c['notes'] ?? ($c['clinical_notes'] ?? ''),
                    'treatment'       => $c['treatment'] ?? ($c['treatment_plan'] ?? ''),
                    'status'          => $c['status'] ?? 'Completed'
                ],
                'raw'          => $c
            ];
        }

        // Add Triages & Vitals
        foreach ($triages as $tr) {
            $nurse = $employeesMap[$tr['nurse_id'] ?? 0] ?? ($tr['nurse_name'] ?? 'Triage Staff');
            $dateStr = !empty($tr['created_at']) ? substr($tr['created_at'], 0, 10) : (!empty($tr['date']) ? substr($tr['date'], 0, 10) : date('Y-m-d'));
            $timeStr = !empty($tr['created_at']) ? substr($tr['created_at'], 11, 5) : '';
            $priority = ucfirst(strtolower($tr['priority'] ?? 'routine'));
            $bp = $tr['blood_pressure'] ?? ($tr['bp'] ?? 'N/A');
            $hr = !empty($tr['heart_rate']) ? "{$tr['heart_rate']} bpm" : 'N/A';
            $temp = !empty($tr['temperature']) ? "{$tr['temperature']}°C" : 'N/A';
            $rr = !empty($tr['respiratory_rate']) ? "{$tr['respiratory_rate']} cpm" : 'N/A';
            $spo2 = !empty($tr['oxygen_saturation']) ? "{$tr['oxygen_saturation']}%" : (!empty($tr['spo2']) ? "{$tr['spo2']}%" : 'N/A');
            $bmi = $tr['bmi'] ?? 'N/A';
            $weight = !empty($tr['weight']) ? "{$tr['weight']} kg" : 'N/A';
            $height = !empty($tr['height']) ? "{$tr['height']} cm" : 'N/A';
            $ts = strtotime($tr['created_at'] ?? $dateStr) ?: time();

            $timeline[] = [
                'id'           => 'triage_' . ($tr['id'] ?? uniqid()),
                'type'         => 'triage',
                'category'     => 'triage',
                'timestamp'    => $ts,
                'date'         => $dateStr,
                'time'         => $timeStr,
                'date_display' => $dateStr . ($timeStr ? " • $timeStr" : ''),
                'title'        => "Vital Signs & Triage ({$priority} Priority)",
                'provider'     => $nurse,
                'badge'        => 'bg-purple-100 text-purple-800 border-purple-200',
                'badge_label'  => 'Vitals & Triage',
                'icon'         => 'fa-heart-pulse text-purple-600',
                'summary'      => "BP: {$bp} | Temp: {$temp} | HR: {$hr} | SpO₂: {$spo2}",
                'details'      => [
                    'triage_id'       => $tr['triage_id'] ?? ($tr['id'] ?? ''),
                    'priority'        => $priority,
                    'blood_pressure'  => $bp,
                    'temperature'     => $temp,
                    'heart_rate'      => $hr,
                    'respiratory_rate'=> $rr,
                    'spo2'            => $spo2,
                    'weight'          => $weight,
                    'height'          => $height,
                    'bmi'             => $bmi,
                    'chief_complaint' => $tr['chief_complaint'] ?? ($tr['symptoms'] ?? 'N/A'),
                    'notes'           => $tr['nurse_notes'] ?? ($tr['notes'] ?? '')
                ],
                'raw'          => $tr
            ];
        }

        // Add Prescriptions
        foreach ($prescriptions as $pr) {
            $docName = $pr['doctor_name'] ?? ($employeesMap[$pr['employee_id'] ?? 0] ?? 'Prescribing Physician');
            $dateStr = $pr['date'] ?? (substr($pr['created_at'] ?? '', 0, 10));
            $meds = is_string($pr['medications'] ?? null) ? json_decode($pr['medications'], true) : ($pr['medications'] ?? []);
            if (!is_array($meds)) $meds = [];
            $medsCount = count($meds);
            $status = ucfirst(strtolower($pr['status'] ?? 'pending'));
            $ts = strtotime($pr['created_at'] ?? $dateStr) ?: time();

            $timeline[] = [
                'id'           => 'rx_' . ($pr['id'] ?? uniqid()),
                'type'         => 'prescription',
                'category'     => 'prescription',
                'timestamp'    => $ts,
                'date'         => $dateStr,
                'time'         => '',
                'date_display' => $dateStr,
                'title'        => "Prescription Issued ({$medsCount} item" . ($medsCount === 1 ? '' : 's') . ")",
                'provider'     => $docName,
                'badge'        => 'bg-teal-100 text-teal-800 border-teal-200',
                'badge_label'  => "Rx ({$status})",
                'icon'         => 'fa-pills text-teal-600',
                'summary'      => "Prescription #" . ($pr['prescription_id'] ?? $pr['id']) . " • Status: {$status}",
                'details'      => [
                    'prescription_id' => $pr['prescription_id'] ?? $pr['id'],
                    'status'          => $status,
                    'medications'     => $meds,
                    'notes'           => $pr['notes'] ?? '',
                    'duration'        => $pr['duration'] ?? '',
                ],
                'raw'          => $pr
            ];
        }

        // Add Appointments
        foreach ($appointments as $ap) {
            $doc = $ap['doctor_name'] ?? ($employeesMap[$ap['employee_id'] ?? 0] ?? 'Attending Staff');
            $dateStr = $ap['appointment_date'] ?? (substr($ap['created_at'] ?? '', 0, 10));
            $timeStr = $ap['appointment_time'] ?? '';
            $status = ucfirst(strtolower($ap['status'] ?? 'scheduled'));
            $ts = strtotime("$dateStr $timeStr") ?: (strtotime($ap['created_at'] ?? '') ?: time());

            $timeline[] = [
                'id'           => 'appt_' . ($ap['id'] ?? uniqid()),
                'type'         => 'appointment',
                'category'     => 'appointment',
                'timestamp'    => $ts,
                'date'         => $dateStr,
                'time'         => $timeStr,
                'date_display' => $dateStr . ($timeStr ? " • $timeStr" : ''),
                'title'        => "Appointment: " . ($ap['service_type'] ?? ($ap['reason'] ?? 'General Checkup')),
                'provider'     => $doc,
                'badge'        => 'bg-blue-100 text-blue-800 border-blue-200',
                'badge_label'  => "Appointment ({$status})",
                'icon'         => 'fa-calendar-check text-blue-600',
                'summary'      => "Service: " . ($ap['service_type'] ?? 'General') . " • Status: {$status}",
                'details'      => [
                    'appointment_id' => $ap['id'],
                    'service_type'   => $ap['service_type'] ?? 'General Consultation',
                    'status'         => $status,
                    'reason'         => $ap['reason'] ?? '',
                    'notes'          => $ap['notes'] ?? ''
                ],
                'raw'          => $ap
            ];
        }

        // Add Referrals
        foreach ($referrals as $rf) {
            $fromDoc = $rf['from_doctor'] ?? ($employeesMap[$rf['from_doctor_id'] ?? 0] ?? 'Referring Doctor');
            $target = $rf['to_specialist'] ?? ($rf['to_hospital'] ?? 'Specialist Facility');
            $dateStr = $rf['date'] ?? (substr($rf['created_at'] ?? '', 0, 10));
            $urgency = ucfirst(strtolower($rf['urgency'] ?? 'routine'));
            $ts = strtotime($rf['created_at'] ?? $dateStr) ?: time();

            $timeline[] = [
                'id'           => 'ref_' . ($rf['id'] ?? uniqid()),
                'type'         => 'referral',
                'category'     => 'referral',
                'timestamp'    => $ts,
                'date'         => $dateStr,
                'time'         => '',
                'date_display' => $dateStr,
                'title'        => "Patient Referral to {$target}",
                'provider'     => $fromDoc,
                'badge'        => 'bg-amber-100 text-amber-800 border-amber-200',
                'badge_label'  => 'Referral',
                'icon'         => 'fa-arrow-right-from-bracket text-amber-600',
                'summary'      => "Reason: " . ($rf['reason'] ?? 'Specialist Care') . " • Urgency: {$urgency}",
                'details'      => [
                    'referral_id' => $rf['referral_id'] ?? $rf['id'],
                    'destination' => $target,
                    'urgency'     => $urgency,
                    'reason'      => $rf['reason'] ?? '',
                    'notes'       => $rf['notes'] ?? ($rf['clinical_summary'] ?? ''),
                    'status'      => ucfirst($rf['status'] ?? 'pending')
                ],
                'raw'          => $rf
            ];
        }

        // Add Immunizations
        foreach ($immunizations as $im) {
            $vaxDate = $im['date_administered'] ?? substr($im['created_at'] ?? '', 0, 10);
            $ts = strtotime($vaxDate) ?: time();
            $timeline[] = [
                'id'           => 'vax_' . ($im['id'] ?? uniqid()),
                'type'         => 'immunization',
                'category'     => 'immunization',
                'timestamp'    => $ts,
                'date'         => $vaxDate,
                'time'         => '',
                'date_display' => $vaxDate,
                'title'        => "Vaccination: " . ($im['vaccine'] ?? 'Vaccine') . " (Dose " . ($im['dose'] ?? '1') . ")",
                'provider'     => $im['administered_by'] ?? 'Immunization Staff',
                'badge'        => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                'badge_label'  => 'Immunization',
                'icon'         => 'fa-syringe text-indigo-600',
                'summary'      => "Antigen: " . ($im['vaccine'] ?? 'N/A') . " • Batch: " . ($im['batch_number'] ?? 'N/A'),
                'details'      => $im,
                'raw'          => $im
            ];
        }

        // Add Legacy Medical Records
        foreach ($legacyRecords as $mr) {
            $dateStr = $mr['date'] ?? substr($mr['created_at'] ?? '', 0, 10);
            $ts = strtotime($mr['created_at'] ?? $dateStr) ?: time();
            $timeline[] = [
                'id'           => 'mr_' . ($mr['id'] ?? uniqid()),
                'type'         => 'medical_record',
                'category'     => 'medical_record',
                'timestamp'    => $ts,
                'date'         => $dateStr,
                'time'         => '',
                'date_display' => $dateStr,
                'title'        => "Archived Record: " . ($mr['record_type'] ?? 'Clinical Note'),
                'provider'     => $mr['created_by'] ?? 'Records Officer',
                'badge'        => 'bg-slate-100 text-slate-800 border-slate-200',
                'badge_label'  => 'Archive',
                'icon'         => 'fa-folder-closed text-slate-600',
                'summary'      => $mr['description'] ?? 'General historical record entry',
                'details'      => $mr,
                'raw'          => $mr
            ];
        }

        // Sort descending by timestamp
        usort($timeline, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        // Calculate unique visit dates
        $visitDates = [];
        foreach ($triages as $tr) {
            $d = !empty($tr['created_at']) ? substr($tr['created_at'], 0, 10) : (!empty($tr['date']) ? substr($tr['date'], 0, 10) : '');
            if ($d) $visitDates[$d] = true;
        }
        foreach ($consultations as $cn) {
            $d = !empty($cn['date']) ? substr($cn['date'], 0, 10) : (!empty($cn['created_at']) ? substr($cn['created_at'], 0, 10) : '');
            if ($d) $visitDates[$d] = true;
        }

        $latestTriage = !empty($triages[0]) ? $triages[0] : null;

        $activeRxCount = 0;
        foreach ($prescriptions as $pr) {
            $st = strtolower($pr['status'] ?? 'pending');
            if ($st === 'pending' || $st === 'active') {
                $activeRxCount++;
            }
        }

        $stats = [
            'total_visits'         => count($visitDates),
            'total_consultations'  => count($consultations),
            'total_prescriptions'  => count($prescriptions),
            'active_prescriptions' => $activeRxCount,
            'total_appointments'   => count($appointments),
            'total_referrals'      => count($referrals),
            'total_immunizations'  => count($immunizations),
            'total_timeline'       => count($timeline),
            'last_visit'           => !empty($timeline[0]['date']) ? $timeline[0]['date'] : ($patient['updated_at'] ? substr($patient['updated_at'], 0, 10) : date('Y-m-d')),
            'latest_vitals'        => $latestTriage ? [
                'blood_pressure'   => $latestTriage['blood_pressure'] ?? ($latestTriage['bp'] ?? 'N/A'),
                'heart_rate'       => !empty($latestTriage['heart_rate']) ? "{$latestTriage['heart_rate']} bpm" : 'N/A',
                'temperature'      => !empty($latestTriage['temperature']) ? "{$latestTriage['temperature']}°C" : 'N/A',
                'respiratory_rate' => !empty($latestTriage['respiratory_rate']) ? "{$latestTriage['respiratory_rate']} cpm" : 'N/A',
                'spo2'             => !empty($latestTriage['oxygen_saturation']) ? "{$latestTriage['oxygen_saturation']}%" : (!empty($latestTriage['spo2']) ? "{$latestTriage['spo2']}%" : 'N/A'),
                'bmi'              => $latestTriage['bmi'] ?? 'N/A',
                'weight'           => !empty($latestTriage['weight']) ? "{$latestTriage['weight']} kg" : 'N/A',
                'priority'         => ucfirst(strtolower($latestTriage['priority'] ?? 'routine')),
                'date'             => substr($latestTriage['created_at'] ?? '', 0, 10),
            ] : null,
        ];

        return [
            'patient'         => $patient,
            'stats'           => $stats,
            'timeline'        => $timeline,
            'consultations'   => $consultations,
            'triages'         => $triages,
            'prescriptions'   => $prescriptions,
            'appointments'    => $appointments,
            'referrals'       => $referrals,
            'immunizations'   => $immunizations,
            'medical_records' => $legacyRecords,
        ];
    }
}