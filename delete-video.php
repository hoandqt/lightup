<?php
header('Content-Type: application/json');
session_start();

require_once "functions.php";

debugLog('trying to delete a video with user role: ' . $_SESSION['role']);

// Check if the user is logged in and is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$uniqueId = $data['unique_id'] ?? null;

if (!$uniqueId) {
    echo json_encode(['success' => false, 'message' => 'Invalid video ID']);
    exit;
}

$videoDir = __DIR__ . "/item-data/$uniqueId";
$contentFile = __DIR__ . "/json/content.json";

try {
    // Delete video folder and its files
    if (is_dir($videoDir)) {
        $files = glob("$videoDir/*"); // Get all file names in the directory
        foreach ($files as $file) {
            if (is_file($file)) {
                if (!unlink($file)) {
                    throw new Exception("Failed to delete file: $file");
                }
            }
        }
        if (!rmdir($videoDir)) {
            throw new Exception("Failed to delete directory: $videoDir");
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Video directory not found']);
        exit;
    }

    // Remove entry from content.json
    if (file_exists($contentFile)) {
        $content = json_decode(file_get_contents($contentFile), true);
        if (isset($content[$uniqueId])) {
            unset($content[$uniqueId]);
            if (!file_put_contents($contentFile, json_encode($content, JSON_PRETTY_PRINT))) {
                throw new Exception("Failed to update content.json");
            }
        }
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Log the error if necessary and return the error message
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. (2)']);
}
exit;
