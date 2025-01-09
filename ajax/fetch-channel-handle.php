<?php
session_start();
require_once "../functions.php";

// Check if the user is logged in and has the admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header('Location: /login');
  exit;
}

// Ensure the script only allows POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Invalid request method.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($data['id']) || empty($data['id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Channel ID is required.']);
    exit;
}

$channelId = $data['id'];

// YouTube API URL to fetch channel details
$apiUrl = "https://www.googleapis.com/youtube/v3/channels?part=snippet&id=$channelId&key=$apiKey";

// Fetch data from the YouTube API
$response = file_get_contents($apiUrl);

if ($response === false) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Failed to fetch data from YouTube API.']);
    exit;
}

// Decode the API response
$channelData = json_decode($response, true);

// Check if the channel data is valid
if (empty($channelData['items']) || !isset($channelData['items'][0]['snippet']['customUrl'])) {
    http_response_code(404); // Not Found
    echo json_encode(['error' => 'Channel handle not found.']);
    exit;
}

// Extract the channel handle
$channelHandle = $channelData['items'][0]['snippet']['customUrl'];

// Return the handle in JSON format
echo json_encode(['handle' => $channelHandle]);