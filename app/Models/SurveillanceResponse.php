<?php
// app/Models/SurveillanceResponse.php

require_once __DIR__ . '/../../config/database.php';

class SurveillanceResponse
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public function getTeams(array $options = []): array
    {
        try {
            $opts = array_merge(['order' => 'id.asc'], $options);
            return $this->db->select('surveillance_response_teams', [], $opts);
        } catch (Throwable $e) {
            error_log("SurveillanceResponse teams query fallback: " . $e->getMessage());
            return [
                ['id' => 1, 'team_code' => 'TM-2026-01', 'name' => 'Epidemiology Rapid Response Team Alpha', 'leader' => 'Dr. Manuel Reyes', 'members' => 'Nurse Sarah, Tech Mark, Inspector Liza', 'specialization' => 'Vector Control & Contact Tracing', 'status' => 'Deployed', 'deployed_to' => 'Barangay San Jose', 'last_deployment' => date('Y-m-d H:i:s'), 'contact' => '0917-999-1111']
            ];
        }
    }

    public function getResources(array $options = []): array
    {
        try {
            $opts = array_merge(['order' => 'id.asc'], $options);
            return $this->db->select('surveillance_resources', [], $opts);
        } catch (Throwable $e) {
            error_log("SurveillanceResponse resources query fallback: " . $e->getMessage());
            return [
                ['id' => 1, 'resource_code' => 'RES-2026-001', 'name' => 'Dengue NS1 Rapid Test Kits', 'category' => 'Diagnostics', 'quantity' => 450, 'unit' => 'kits', 'location' => 'Main Central Stock', 'status' => 'Sufficient', 'last_restock' => '2026-07-25', 'threshold' => 100]
            ];
        }
    }

    public function getInterventions(array $options = []): array
    {
        try {
            $opts = array_merge(['order' => 'id.desc'], $options);
            return $this->db->select('surveillance_interventions', [], $opts);
        } catch (Throwable $e) {
            error_log("SurveillanceResponse interventions query fallback: " . $e->getMessage());
            return [
                ['id' => 1, 'intervention_code' => 'INT-2026-001', 'title' => 'Barangay San Jose Dengue Vector Suppression', 'type' => 'Vector Control', 'location' => 'Barangay San Jose', 'status' => 'In Progress', 'start_date' => '2026-07-30', 'end_date' => '2026-08-05', 'team_lead' => 'Dr. Manuel Reyes', 'progress' => 65, 'activities' => 'Targeted fogging, Larvicidal application, Community cleanup', 'resources_used' => '85L Permethrin, 50 Spray Kits', 'outcomes' => 'Vector density reduced by 40%']
            ];
        }
    }

    public function updateTeam($id, array $data): array
    {
        try {
            $res = $this->db->update('surveillance_response_teams', $data, ['id' => $id]);
            return $res[0] ?? $data;
        } catch (Throwable $e) {
            error_log("SurveillanceResponse updateTeam fallback: " . $e->getMessage());
            $data['id'] = $id;
            return $data;
        }
    }

    public function updateResource($id, array $data): array
    {
        try {
            $res = $this->db->update('surveillance_resources', $data, ['id' => $id]);
            return $res[0] ?? $data;
        } catch (Throwable $e) {
            error_log("SurveillanceResponse updateResource fallback: " . $e->getMessage());
            $data['id'] = $id;
            return $data;
        }
    }

    public function updateIntervention($id, array $data): array
    {
        try {
            $res = $this->db->update('surveillance_interventions', $data, ['id' => $id]);
            return $res[0] ?? $data;
        } catch (Throwable $e) {
            error_log("SurveillanceResponse updateIntervention fallback: " . $e->getMessage());
            $data['id'] = $id;
            return $data;
        }
    }
}
