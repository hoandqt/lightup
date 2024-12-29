<?php
header('Content-Type: application/json');
session_start();

require_once "functions.php";

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

// Delete video folder and its files
if (is_dir($videoDir)) {
    array_map('unlink', glob("$videoDir/*"));
    rmdir($videoDir);
}

// Remove entry from content.json
if (file_exists($contentFile)) {
    $content = json_decode(file_get_contents($contentFile), true);
    unset($content[$uniqueId]);
    file_put_contents($contentFile, json_encode($content, JSON_PRETTY_PRINT));
}

echo json_encode(['success' => true]);
exit;
