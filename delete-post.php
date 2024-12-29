<?php
header('Content-Type: application/json');
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$uniqueId = $data['unique_id'] ?? null;

if (!$uniqueId) {
    echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
    exit;
}

// Define paths for the post directory and content file
$postDir = __DIR__ . "/post-data/$uniqueId";
$contentFile = __DIR__ . "/json/post-content.json";

// Delete post directory and its files
if (is_dir($postDir)) {
    // Delete all files inside the post directory
    array_map('unlink', glob("$postDir/*"));
    // Delete the directory itself
    rmdir($postDir);
}

// Remove entry from post-content.json
if (file_exists($contentFile)) {
    $content = json_decode(file_get_contents($contentFile), true);
    if (isset($content[$uniqueId])) {
        unset($content[$uniqueId]);
        file_put_contents($contentFile, json_encode($content, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true, 'message' => 'Post deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Post not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Content file not found']);
}
exit;
?>
