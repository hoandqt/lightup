<?php
session_start();

// Ensure the user is logged in and has the admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// Determine the content type
$contentType = $_GET['type'] ?? 'video'; // Default to 'video'

// Set paths based on content type
if ($contentType === 'video') {
    $contentFile = __DIR__ . '/../json/content.json';
    $dataFolderName = 'item-data';
    $dataFolder = __DIR__ . '/../' . $dataFolderName;
} elseif ($contentType === 'post') {
    $contentFile = __DIR__ . '/../json/post-content.json';
    $dataFolderName = 'post-data';
    $dataFolder = __DIR__ . '/../' . $dataFolderName;
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid content type']);
    exit;
}

// Ensure the content file exists
if (!file_exists($contentFile)) {
    http_response_code(404);
    echo json_encode(['error' => 'Content file not found']);
    exit;
}

// Read the content file
$content = json_decode(file_get_contents($contentFile), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid JSON format']);
    exit;
}

$response = [];
foreach ($content as $uniqueId => $metadata) {
    $jsonFilePath = $dataFolder . '/' . $uniqueId . '/' . $uniqueId . '.json';
    if (file_exists($jsonFilePath)) {
        $itemData = json_decode(file_get_contents($jsonFilePath), true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $response[] = [
                'unique_id' => $uniqueId,
                'thumbnail' => isset($itemData['thumbnail']) ? "/{$dataFolderName}/$uniqueId/" . $itemData['thumbnail'] : 'No Thumbnail',
                'title' => $itemData['title'] ?? 'No Title',
                'user' => $itemData['username'] ?? 'Unknown',
                'updated_date' => $itemData['updated_date'] ?? 'Unknown',
                'alias' => $itemData['alias'] ?? '',
            ];
        }
    }
}

// Sort the response by updated_date, latest on top
usort($response, function ($a, $b) {
    $dateA = strtotime($a['updated_date']);
    $dateB = strtotime($b['updated_date']);
    return $dateB <=> $dateA;
});

// Send the response as JSON
header('Content-Type: application/json');
echo json_encode($response);
exit;
