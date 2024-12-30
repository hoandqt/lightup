<?php
require 'vendor/autoload.php'; // Ensure OpenAI SDK is installed via Composer
require_once 'functions.php';

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
$keyFilePath = '../json/generate.json';
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
$title = $data['title'] ?? '';
$description = $data['description'] ?? '';
$tags = $data['tags'] ?? '';

if (!$title || !$description || !$tags) {
    echo json_encode(['success' => false, 'message' => 'Missing input data.']);
    exit;
}

try {
    $prompt = "Generate metadata for the following video:\n\nTitle: $title\nDescription: $description\nTags: $tags\n\nMeta Description length must be within 70-150 characters. Provide (don't add markup ** to the result):\n1. Meta Title\n2. Meta Description\n3. Meta Keywords.\nAlso, create a new article content for the video based on the above input, containing 1000 to 1300 words. Use only HTML tags to wrap content where necessary (e.g., <h3>, <p>, <strong>, <ul>, etc.). Don't use 'Whether you're' or ask users to visit Youtube channel. Do not include backticks (`) or any language markers like ```html in the response. The output should be simple text.\n4. New Content:";

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

    //debugLog($generatedText);
    
    // Updated regex to handle both cases (with ** and without **)
    preg_match('/(?:\*\*|)Meta Title:(?:\*\*|)\s*(.*?)\n/', $generatedText, $metaTitleMatch);
    preg_match('/(?:\*\*|)Meta Description:(?:\*\*|)\s*(.*?)\n/', $generatedText, $metaDescriptionMatch);
    preg_match('/(?:\*\*|)Meta Keywords:(?:\*\*|)\s*(.*?)\n/', $generatedText, $metaKeywordsMatch);
    preg_match('/(?:\*\*|)New Content:\n+([\s\S]*)(?:\s*<\/[^>]+>)?\s*(.*?)$/', $generatedText, $newContentMatch);

    echo json_encode([
        'success' => true,
        'meta_title' => trim($metaTitleMatch[1] ?? ''),
        'meta_description' => trim($metaDescriptionMatch[1] ?? ''),
        'meta_keywords' => trim($metaKeywordsMatch[1] ?? ''),
        'new_content' => trim($newContentMatch[1] ?? ''),
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
