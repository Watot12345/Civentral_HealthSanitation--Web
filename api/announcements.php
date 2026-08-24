<?php
// api/announcements.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../app/Models/Announcement.php';

function compressImageFile(string $sourcePath): void {
    if (!function_exists('imagecreatefromstring')) return;
    $fileSize = @filesize($sourcePath);
    if ($fileSize && $fileSize > 1.5 * 1024 * 1024) {
        $data = @file_get_contents($sourcePath);
        if (!$data) return;
        $img = @imagecreatefromstring($data);
        if (!$img) return;

        $width = imagesx($img);
        $height = imagesy($img);
        $maxDim = 1400;

        if ($width > $maxDim || $height > $maxDim) {
            $ratio = min($maxDim / $width, $maxDim / $height);
            $newW = (int)($width * $ratio);
            $newH = (int)($height * $ratio);
            $newImg = imagecreatetruecolor($newW, $newH);

            if (function_exists('imagealphablending')) {
                imagealphablending($newImg, false);
                imagesavealpha($newImg, true);
            }

            imagecopyresampled($newImg, $img, 0, 0, 0, 0, $newW, $newH, $width, $height);
            imagejpeg($newImg, $sourcePath, 82);
            imagedestroy($newImg);
        } else {
            imagejpeg($img, $sourcePath, 82);
        }
        imagedestroy($img);
    }
}

try {
    $announcementModel = new Announcement();
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_REQUEST['action'] ?? '';

    // Handle GET Request (List Announcements)
    if ($method === 'GET') {
        $range = $_GET['range'] ?? 'all';
        $category = $_GET['category'] ?? 'all';

        $announcements = $announcementModel->all();

        // Filter by Category
        if ($category !== 'all' && !empty($category)) {
            $announcements = array_filter($announcements, function($item) use ($category) {
                return strcasecmp($item['category'] ?? '', $category) === 0;
            });
        }

        // Filter by Time Range (Today / 7 Days / 30 Days)
        if ($range !== 'all' && !empty($range)) {
            $cutoff = 0;
            if ($range === 'today') {
                $cutoff = strtotime('today midnight');
            } elseif ($range === '7days') {
                $cutoff = strtotime('-7 days');
            } elseif ($range === '30days') {
                $cutoff = strtotime('-30 days');
            }

            if ($cutoff > 0) {
                $announcements = array_filter($announcements, function($item) use ($cutoff) {
                    $itemTime = !empty($item['created_at']) ? strtotime($item['created_at']) : 0;
                    return $itemTime >= $cutoff;
                });
            }
        }

        $announcements = array_values($announcements);

        if (ob_get_level() > 0) {
            @ob_clean();
        }

        echo json_encode([
            'success' => true,
            'count' => count($announcements),
            'data' => $announcements,
            'range' => $range,
            'category' => $category,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // Handle DELETE Request
    if ($method === 'DELETE' || ($method === 'POST' && $action === 'delete')) {
        $id = $_REQUEST['id'] ?? null;
        if (!$id) {
            $rawInput = json_decode(file_get_contents('php://input'), true);
            $id = $rawInput['id'] ?? null;
        }

        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing announcement ID']);
            exit;
        }

        $announcementModel->delete($id);
        echo json_encode(['success' => true, 'message' => 'Announcement deleted successfully']);
        exit;
    }

    // Handle POST Request (Create Announcement)
    if ($method === 'POST') {
        $title = trim($_POST['title'] ?? ($_POST['announcementTitle'] ?? ''));
        $category = trim($_POST['category'] ?? ($_POST['announcementCategory'] ?? 'General Announcement'));
        $audience = trim($_POST['audience'] ?? ($_POST['announcementAudience'] ?? 'All Staff'));
        $body = trim($_POST['body'] ?? ($_POST['announcementBody'] ?? ''));

        if (empty($title) || empty($body)) {
            $rawInput = json_decode(file_get_contents('php://input'), true);
            if ($rawInput) {
                $title = trim($rawInput['title'] ?? '');
                $category = trim($rawInput['category'] ?? 'General Announcement');
                $audience = trim($rawInput['audience'] ?? 'All Staff');
                $body = trim($rawInput['body'] ?? '');
            }
        }

        if (empty($title) || empty($body)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Title and body are required fields']);
            exit;
        }

        // Handle File Upload if provided via Base64 or $_FILES
        $fileUrl = null;
        $db = Database::getInstance();

        // Priority 1: Client-Side Compressed Base64 Image
        if (!empty($_POST['file_base64']) || !empty($_POST['image_base64'])) {
            $base64 = $_POST['file_base64'] ?? $_POST['image_base64'];
            if (preg_match('/^data:([^;]+);base64,(.*)$/i', $base64, $matches)) {
                $mimeType = $matches[1];
                $binary = base64_decode($matches[2]);
                $ext = 'jpg';
                if (str_contains($mimeType, 'png')) $ext = 'png';
                elseif (str_contains($mimeType, 'pdf')) $ext = 'pdf';

                $fileName = 'announcement_' . time() . '_' . uniqid() . '.' . $ext;
                $tmpPath = sys_get_temp_dir() . '/' . $fileName;
                file_put_contents($tmpPath, $binary);

                $supabasePublicUrl = $db->uploadStorage('announcement', $fileName, $tmpPath, $mimeType);
                if (!$supabasePublicUrl) {
                    $supabasePublicUrl = $db->uploadStorage('announcements', $fileName, $tmpPath, $mimeType);
                }

                if ($supabasePublicUrl) {
                    $fileUrl = $supabasePublicUrl;
                }
                @unlink($tmpPath);
            }
        }

        // Priority 2: Standard $_FILES if Base64 was not uploaded
        if (!$fileUrl) {
            $fileObj = $_FILES['announcementFile'] ?? ($_FILES['file'] ?? ($_FILES['attachment'] ?? null));

            if ($fileObj && !empty($fileObj['name']) && $fileObj['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../storage/uploads/announcements/';
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0755, true);
                }
                $ext = pathinfo($fileObj['name'], PATHINFO_EXTENSION);
                $fileName = 'announcement_' . time() . '_' . uniqid() . '.' . strtolower($ext);
                $targetPath = $uploadDir . $fileName;

                if (move_uploaded_file($fileObj['tmp_name'], $targetPath)) {
                    compressImageFile($targetPath);

                    $mimeType = function_exists('mime_content_type') ? mime_content_type($targetPath) : 'image/jpeg';

                    $supabasePublicUrl = $db->uploadStorage('announcement', $fileName, $targetPath, $mimeType);
                    if (!$supabasePublicUrl) {
                        $supabasePublicUrl = $db->uploadStorage('announcements', $fileName, $targetPath, $mimeType);
                    }

                    if ($supabasePublicUrl) {
                        $fileUrl = $supabasePublicUrl;
                    } else {
                        $fileUrl = '/storage/uploads/announcements/' . $fileName;
                    }
                }
            }
        }

        $authorName = trim($_SESSION['user']['full_name'] ?? ($_SESSION['user']['name'] ?? ($_SESSION['username'] ?? 'System Admin')));
        $roleDesc = trim($_SESSION['role_description'] ?? ($_SESSION['user']['role_description'] ?? ''));
        if (!empty($roleDesc)) {
            $authorName .= ' (' . $roleDesc . ')';
        }

        $newAnnouncement = $announcementModel->create([
            'title' => $title,
            'category' => $category,
            'audience' => $audience,
            'body' => $body,
            'author' => $authorName,
            'file_url' => $fileUrl
        ]);

        if (ob_get_level() > 0) {
            @ob_clean();
        }

        echo json_encode([
            'success' => true,
            'message' => 'Announcement posted successfully',
            'data' => $newAnnouncement
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

} catch (Throwable $e) {
    error_log("API Announcements Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while processing announcements',
        'error' => $e->getMessage()
    ]);
}
