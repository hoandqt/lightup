<?php
session_start();
header('Content-Type: application/json');

// Ensure the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get the unique ID from the AJAX request
$data = json_decode(file_get_contents('php://input'), true);
$uniqueId = $data['unique_id'] ?? null;

if (!$uniqueId) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Define the paths
$videoDir = __DIR__ . "/item-data/$uniqueId/";
$jsonFilePath = $videoDir . "$uniqueId.json";

// Ensure the JSON file exists
if (!file_exists($jsonFilePath)) {
    echo json_encode(['success' => false, 'message' => 'Video not found']);
    exit;
}

// Read and update the JSON data
$videoData = json_decode(file_get_contents($jsonFilePath), true);
if (!empty($videoData['thumbnail']) && file_exists($videoDir . $videoData['thumbnail'])) {
    unlink($videoDir . $videoData['thumbnail']); // Delete the thumbnail file
    $videoData['thumbnail'] = ""; // Remove thumbnail reference
    file_put_contents($jsonFilePath, json_encode($videoData, JSON_PRETTY_PRINT)); // Update JSON file
    echo json_encode(['success' => true]);
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Thumbnail not found']);
    exit;
}
