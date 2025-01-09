<?php
require '../vendor/autoload.php'; // Ensure OpenAI SDK is installed via Composer
require_once '../functions.php';

use OpenAI;

session_start();

// Ensure the user is logged in
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
$type = $data['type'] ?? '';
$input = $data['input'] ?? '';
$inputWord = $data['inputWord'] ?? '';
$selector = $data['selector'] ?? '';

if (!$type || !$input) {
    echo json_encode(['success' => false, 'message' => 'Missing input data.']);
    exit;
}

$excludeWordsArr = [
  "'Whether you're'",
  "'Immerse yourself'"
];
$excludeWords = implode(',', $excludeWordsArr);

try {
    if ($type == 'html') {
      if (!empty($inputWord)) {
        $prompt = "Generate a new article for the following content, use only HTML tags to wrap content where necessary (e.g., <h3>, <p>, <strong>, <ul>, etc.). Please ensure it meets high-quality standards and '.$inputWord.' number of words. Don't use '.$excludeWords.' or some common forms that make the content like bot/ai created. Do not include backticks (`) or any language markers like ```html in the response. The output should be simple text.\nContent:\n\n$input";
      }
      else {
        $prompt = "Generate a new article for following content, use only HTML tags to wrap content where necessary (e.g., <h3>, <p>, <strong>, <ul>, etc.). Please ensure it meets the number of words from the input. Don't use '.$excludeWords.' or some common forms that make the content like bot/ai created. Do not include backticks (`) or any language markers like ```html in the response. The output should be simple text.\nContent:\n\n$input";
      }
    }
    else if ($type == 'longtext') {
      if (!empty($inputWord)) {
        $prompt = "Rewrite the following longtext content in multiple lines. Please ensure it meets high-quality standards and '.$inputWord.' number of words. Don't use '.$excludeWords.' or some common forms that make the content like bot/ai created. Do not include backticks (`) or any language markers like ```html in the response. The output should be simple text.\nContent:\n\n$input";
      }
      else {
        $prompt = "Rewrite the following longtext content in multiple lines. Please ensure it meets high-quality standards and the same number of words from the input. Don't use '.$excludeWords.' or some common forms that make the content like bot/ai created. Do not include backticks (`) or any language markers like ```html in the response. The output should be simple text.\nContent:\n\n$input";
      }
    }
    else {
      if ($selector === '#meta_description') {
        $prompt = "Write the meta description for following content. Description length must be within 70-150 characters.\nContent:\n\n$input";
      }
      else if ($selector === '#meta_keywords') {
        $prompt = "Write the meta keywords from following content. Keywords length must be within 150-160 characters, 5-10 individual keywords or short phrases. \nContent:\n\n$input";
      }
      else if ($selector === '#meta_title') {
        $prompt = "Write the meta title for following content. Title length must be within 40-55 characters, don't wrap result with double quotes.\nContent:\n\n$input";
      }
      else {
        // Text by default
        if (!empty($inputWord)) {
          $prompt = "Rewrite the following text content in the same line and keep similar meaning. Please ensure it meets high-quality standards and '.$inputWord.' number of words. The output should be simple text.\nContent:\n\n$input";
        }
        else {
          $prompt = "Rewrite the following text content in the same line and keep similar meaning. Please ensure it meets high-quality standards and the same number of words from the input. The output should be simple text.\nContent:\n\n$input";
        }
      }
    }

    // Call the GPT-4 model with the prompt
    $response = $client->chat()->create([
        'model' => 'gpt-4o', // Replace with the appropriate model
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You are a helpful assistant that generates refined content based on the provided input.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ]
    ]);

    $generatedText = $response['choices'][0]['message']['content'];

    // Debugging: log the response
    //debugLog($generatedText);

    echo json_encode([
        'success' => true,
        'result' => trim($generatedText),
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
