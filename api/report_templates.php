<?php
// api/report_templates.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../Core/Env.php';
require_once __DIR__ . '/../Core/Response.php';
require_once __DIR__ . '/../config/database.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

$storageFile = __DIR__ . '/../storage/report_templates.json';

// Helper function to read templates
function getTemplates($file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        if (is_array($data)) return $data;
    }
    
    // Default initial seed matching the 5 Core Modules and 1 Unified Option
    $initial = [
        [
            'id' => 1,
            'name' => 'Unified Global Health & Sanitation Audit',
            'type' => 'unified',
            'department' => 'All Departments',
            'status' => 'active',
            'description' => 'Cross-departmental executive summary covering consultations, inspections, immunizations, wastewater, and surveillance.',
            'config' => ['reportType' => 'unified', 'exportFormat' => 'pdf', 'facility' => 'all'],
            'updated' => date('Y-m-d')
        ],
        [
            'id' => 2,
            'name' => 'Health Center Clinical & Patient Report',
            'type' => 'health_center',
            'department' => 'Health Center Services',
            'status' => 'active',
            'description' => 'Primary healthcare facility diagnostics, treated consultations, and patient treatment metrics.',
            'config' => ['reportType' => 'health_center', 'exportFormat' => 'pdf', 'facility' => 'Central Health Center'],
            'updated' => date('Y-m-d')
        ],
        [
            'id' => 3,
            'name' => 'Sanitation Permits & Food Establishment Inspection',
            'type' => 'sanitation',
            'department' => 'Sanitation Permits',
            'status' => 'active',
            'description' => 'Commercial food safety hygiene compliance, sanitary permits, and inspection pass rates.',
            'config' => ['reportType' => 'sanitation', 'exportFormat' => 'pdf', 'facility' => 'South Sanitation Depot'],
            'updated' => date('Y-m-d')
        ],
        [
            'id' => 4,
            'name' => 'Immunization & Maternal Nutrition Drive',
            'type' => 'immunization',
            'department' => 'Immunization & Nutrition',
            'status' => 'active',
            'description' => 'Community vaccine doses administered, infant monitoring, and nutrition coverage rates.',
            'config' => ['reportType' => 'immunization', 'exportFormat' => 'pdf', 'facility' => 'Central Health Center'],
            'updated' => date('Y-m-d')
        ],
        [
            'id' => 5,
            'name' => 'Wastewater Treatment & Regulatory Billing Audit',
            'type' => 'wastewater',
            'department' => 'Wastewater Services',
            'status' => 'active',
            'description' => 'Industrial discharge tracking, grease trap inspections, environmental fees, and billing collections.',
            'config' => ['reportType' => 'wastewater', 'exportFormat' => 'pdf', 'facility' => 'South Sanitation Depot'],
            'updated' => date('Y-m-d')
        ],
        [
            'id' => 6,
            'name' => 'Epidemiological Health Disease Surveillance',
            'type' => 'surveillance',
            'department' => 'Health Surveillance',
            'status' => 'active',
            'description' => 'Disease outbreak surveillance, active case tracking, cluster investigation, and resolution rates.',
            'config' => ['reportType' => 'surveillance', 'exportFormat' => 'pdf', 'facility' => 'Central Health Center'],
            'updated' => date('Y-m-d')
        ]
    ];
    
    if (!is_dir(dirname($file))) {
        mkdir(dirname($file), 0755, true);
    }
    file_put_contents($file, json_encode($initial, JSON_PRETTY_PRINT));
    return $initial;
}

function saveTemplates($file, $templates) {
    if (!is_dir(dirname($file))) {
        mkdir(dirname($file), 0755, true);
    }
    return file_put_contents($file, json_encode($templates, JSON_PRETTY_PRINT));
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $templates = getTemplates($storageFile);

    switch ($method) {
        case 'GET':
            echo json_encode([
                'success' => true,
                'data' => $templates,
                'total' => count($templates),
                'active' => count(array_filter($templates, fn($t) => ($t['status'] ?? '') === 'active')),
                'draft'  => count(array_filter($templates, fn($t) => ($t['status'] ?? '') === 'draft'))
            ]);
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || empty($input['name'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Template name is required.']);
                exit;
            }

            $maxId = 0;
            foreach ($templates as $t) {
                if (($t['id'] ?? 0) > $maxId) $maxId = $t['id'];
            }

            $newTemplate = [
                'id'          => $maxId + 1,
                'name'        => trim($input['name']),
                'type'        => $input['type'] ?? 'unified',
                'department'  => trim($input['department'] ?? 'General'),
                'status'      => $input['status'] ?? 'active',
                'description' => trim($input['description'] ?? ''),
                'config'      => $input['config'] ?? null,
                'updated'     => date('Y-m-d')
            ];

            array_unshift($templates, $newTemplate);
            saveTemplates($storageFile, $templates);

            echo json_encode(['success' => true, 'message' => 'Template created successfully', 'data' => $newTemplate]);
            break;

        case 'PUT':
        case 'PATCH':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? $_GET['id'] ?? null;

            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Template ID is required.']);
                exit;
            }

            $found = false;
            foreach ($templates as &$t) {
                if ((string)$t['id'] === (string)$id) {
                    if (isset($input['name'])) $t['name'] = trim($input['name']);
                    if (isset($input['type'])) $t['type'] = $input['type'];
                    if (isset($input['department'])) $t['department'] = trim($input['department']);
                    if (isset($input['status'])) $t['status'] = $input['status'];
                    if (isset($input['description'])) $t['description'] = trim($input['description']);
                    if (isset($input['config'])) $t['config'] = $input['config'];
                    $t['updated'] = date('Y-m-d');
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Template not found.']);
                exit;
            }

            saveTemplates($storageFile, $templates);
            echo json_encode(['success' => true, 'message' => 'Template updated successfully']);
            break;

        case 'DELETE':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                $input = json_decode(file_get_contents('php://input'), true);
                $id = $input['id'] ?? null;
            }

            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Template ID is required.']);
                exit;
            }

            $initialCount = count($templates);
            $templates = array_values(array_filter($templates, fn($t) => (string)$t['id'] !== (string)$id));

            if (count($templates) === $initialCount) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Template not found.']);
                exit;
            }

            saveTemplates($storageFile, $templates);
            echo json_encode(['success' => true, 'message' => 'Template deleted successfully']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
            break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
