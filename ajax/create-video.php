<?php
session_start();
header('Content-Type: application/json');
require_once "../functions.php";

// Ensure user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'User not authenticated.']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request data.']);
    exit;
}

// Required fields from the playlist
$channelName = $data['channel'] ?? null;
$channelUniqueId = $data['channel_unique_id'] ?? null;
$channelHandle = getChannelHandle($data['channel_unique_id'], $apiKey);

if (!empty($channelHandle)) {
  $channelId = getChannelId($channelHandle);
  if (empty($channelId)) {
    // Create new channel and get the id
    $channelId = addNewChannel($channelName, $channelUniqueId, $channelHandle);
  }
}
else {
  echo json_encode(['status' => 'error', 'message' => 'Channel handle not found.']);
  exit;
}

$videoLink = $data['video_link'] ?? null;
$title = $data['title'] ?? null;
$description = $data['description'] ?? null;
$tags = isset($data['tags']) ? formatCommaSeparatedInput($data['tags']) : '';
$categoryId = $data['category'] ?? null;

$thumbnailUrl = $data['thumbnail'] ?? null;

if (!$channelId || !$videoLink || !$title || !$description || !$tags || !$categoryId) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
    exit;
}

// Increment the ID for the new video
$newId = getNewId('video');

// Update the latest.json file
$latestJsonPath = __DIR__ . '/../json/latest.json';
if (file_exists($latestJsonPath)) {
  $latestData = json_decode(file_get_contents($latestJsonPath), true);
  $latestData['video']['id'] = $newId; // Update only the "video" field
  file_put_contents($latestJsonPath, json_encode($latestData, JSON_PRETTY_PRINT));  
}

$uniqueId = generateUniqueId();
$alias = generateAlias($title);
$username = $_SESSION['username']; // Get username from session
$currentDate = date('Y-m-d H:i:s');

// Create directories
$baseDir = __DIR__ . '/../item-data/';
$videoDir = $baseDir . $uniqueId . '/';
if (!is_dir($baseDir)) mkdir($baseDir, 0755, true);
if (!is_dir($videoDir)) mkdir($videoDir, 0755, true);

// Handle thumbnail download
$thumbnailPath = null;
if ($thumbnailUrl) {
    $thumbnailContent = file_get_contents($thumbnailUrl); // Download the thumbnail
    if ($thumbnailContent) {
        $thumbnailExtension = pathinfo(parse_url($thumbnailUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
        $thumbnailPath = $videoDir . 'thumbnail.' . $thumbnailExtension;
        file_put_contents($thumbnailPath, $thumbnailContent); // Save thumbnail to the server
    }
}

// Prepare video data
$videoData = [
    'id' => $newId, // Add the new ID here
    'unique_id' => $uniqueId,
    'channel' => $channelId,
    'title' => $title,
    'alias' => $alias,
    'description' => $description,
    'tags' => $tags,
    'thumbnail' => $thumbnailPath ? basename($thumbnailPath) : null,
    'video_link' => $videoLink,
    'category' => $categoryId,
    'visibility' => 'private', // Default visibility
    'posted_date' => $currentDate,
    'updated_date' => $currentDate,
    'username' => $username,
];

// Save video data to a JSON file
$itemJsonPath = $videoDir . $uniqueId . '.json';
file_put_contents($itemJsonPath, json_encode($videoData, JSON_PRETTY_PRINT));

// Update or create content.json
$contentFile = __DIR__ . '/../json/content.json';
$content = file_exists($contentFile) ? json_decode(file_get_contents($contentFile), true) : [];
$content[$uniqueId] = [
    'id' => $newId,
    'unique_id' => $uniqueId,
    'posted_date' => $currentDate,
    'updated_date' => $currentDate,
    'category' => $category,
    'visibility' => 'private',
];
file_put_contents($contentFile, json_encode($content, JSON_PRETTY_PRINT));

// Respond with success message
echo json_encode([
    'status' => 'success',
    'message' => 'Video created successfully!',
    'unique_id' => $uniqueId,
    'id' => $newId,
]);
exit;
