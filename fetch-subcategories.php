<?php
header('Content-Type: application/json');

$categoryId = $_GET['category_id'] ?? null;

// Path to the JSON file
$jsonFilePath = __DIR__ . '/json/video-category.json';

// Check if the JSON file exists
if (!file_exists($jsonFilePath)) {
    echo json_encode([
        "success" => false,
        "message" => "Category data not found.",
        "subcategories" => []
    ]);
    exit;
}

// Read and decode the JSON file
$categories = json_decode(file_get_contents($jsonFilePath), true);

// Validate the category ID and fetch its subcategories
if ($categoryId && isset($categories[$categoryId]['subcategories'])) {
    $subcategories = $categories[$categoryId]['subcategories'];
    echo json_encode([
        "success" => true,
        "subcategories" => $subcategories
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Subcategories not found for the given category.",
        "subcategories" => []
    ]);
}
