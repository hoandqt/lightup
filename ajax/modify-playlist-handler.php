<?php
session_start();

require_once "../functions.php";

header('Content-Type: application/json');

// Ensure user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

// Check if the user has the "admin" role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden. Only admins can perform this action.']);
    exit;
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get the input data
$data = json_decode(file_get_contents('php://input'), true);
$playlistUniqueId = $data['playlist_unique_id'] ?? null;
$videoUniqueId = $data['video_unique_id'] ?? null;

// Call the function to add the video to the playlist
$result = addVideoToPlaylist($playlistUniqueId, $videoUniqueId);

// Return the result as JSON
echo json_encode($result);
