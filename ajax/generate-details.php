<?php
require '../vendor/autoload.php'; // Ensure OpenAI SDK is installed via Composer
require_once '../functions.php';

use OpenAI;

session_start();

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

// Load API key from /json/key.json
$keyFilePath = '../../json/generate.json';
if (!file_exists($keyFilePath)) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'API key file not found.']);
  exit;
}

$keyData = json_decode(file_get_contents($keyFilePath), true);
if (!isset($keyData['oa']) || empty($keyData['oa'])) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Invalid API key.']);
  exit;
}

$openaiApiKey = $keyData['oa'];

// Initialize OpenAI client
$client = OpenAI::client($openaiApiKey);

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
debugLog($data);
$type = $data['type'] ?? '';
$input = $data['input'] ?? '';
$output = $data['output'] ?? '';
$requirement = $data['requirement'] ?? '';

if (!$type || !$input) {
  echo json_encode(['success' => false, 'message' => 'Missing input data.']);
  exit;
}

try {
  if ($type == 'playlist') {
    $prompt = "Generate the description content and tags for the following video playlist:\n\nPlaylist title: $input\n\n";
    if (!empty($requirement)) {
      $prompt .= "Playlist Description content must $requirement. ";
    }
    $prompt .= "Playlist Description content length must be within 1000-1500 words, use only HTML tags to wrap content where necessary (e.g., <h3>, <p>, <strong>, <ul>, etc.), don't use 'Whether you're' or common bot/ai phrases. Playlist Tags length must be within 100-250 character. Do not include backticks (`) or any language markers like ```html in the response. The output should be simple text, don't add markup ** to the result. Provide:\n1. Playlist Tags:\n2. Playlist Content:";
  } else {
    $prompt = "Generate the $output for the following $type: $input\n\n.Provide:\nOutput Result:";
  }

  // Call the GPT-4 model with messages
  $response = $client->chat()->create([
    'model' => 'gpt-4o', // Replace with the appropriate model
    'messages' => [
        [
          'role' => 'system',
          'content' => 'You are a helpful assistant.'
        ],
        [
          'role' => 'user',
          'content' => $prompt
        ]
      ]
  ]);

  $generatedText = $response['choices'][0]['message']['content'];

  debugLog($generatedText);

  if ($type == 'playlist') {
    // Updated regex to handle both cases (with ** and without **)
    preg_match('/(?:\*\*|)Playlist Tags:(?:\*\*|)\s*(.*?)\\n/', $generatedText, $playlistTagsMatch);
    preg_match('/(?:\*\*|)Playlist Content:(?:\*\*|)\s*\\n([\s\S]*)(?:\s*<\/[^>]+>)?\s*(.*?)$/', $generatedText, $playlistContentMatch);

    echo json_encode([
      'success' => true,
      'playlist_tags' => trim($playlistTagsMatch[1] ?? ''),
      'playlist_content' => trim($playlistContentMatch[1] ?? '')
    ]);
  } else {

  }
} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
