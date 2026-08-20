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
    
    // Default initial seed
    $initial = [
        ['id' => 1, 'name' => 'Food Establishment Inspection', 'type' => 'inspection', 'status' => 'active', 'description' => 'Standard hygiene and food safety inspection for food establishments.', 'updated' => date('Y-m-d')],
        ['id' => 2, 'name' => 'Water Quality Audit', 'type' => 'water', 'status' => 'active', 'description' => 'Comprehensive microbial and chemical water supply testing protocol.', 'updated' => date('Y-m-d')],
        ['id' => 3, 'name' => 'Waste Disposal Compliance', 'type' => 'waste', 'status' => 'draft', 'description' => 'Solid waste and commercial grease disposal audit template.', 'updated' => date('Y-m-d')],
        ['id' => 4, 'name' => 'Healthcare Facility Sanitation', 'type' => 'audit', 'status' => 'inactive', 'description' => 'Biohazard and clinic sanitation compliance checklist.', 'updated' => date('Y-m-d')],
        ['id' => 5, 'name' => 'Public Restroom Inspection', 'type' => 'inspection', 'status' => 'active', 'description' => 'Routine public facility cleanliness and plumbing audit.', 'updated' => date('Y-m-d')]
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
                'type'        => $input['type'] ?? 'inspection',
                'status'      => $input['status'] ?? 'active',
                'description' => trim($input['description'] ?? ''),
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
                    if (isset($input['status'])) $t['status'] = $input['status'];
                    if (isset($input['description'])) $t['description'] = trim($input['description']);
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
