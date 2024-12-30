<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['id'], $input['data'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input']);
        exit;
    }

    $categoryFile = __DIR__ . '/json/video-category.json';
    $categories = file_exists($categoryFile) ? json_decode(file_get_contents($categoryFile), true) : [];

    // Merge or update data for the given ID
    if (isset($categories[$input['id']]) && is_array($categories[$input['id']])) {
        $categories[$input['id']] = array_merge($categories[$input['id']], $input['data']);
    } else {
        $categories[$input['id']] = $input['data'];
    }

    // Save the updated category data back to the file
    file_put_contents($categoryFile, json_encode($categories, JSON_PRETTY_PRINT));

    // Respond with success and the updated category
    echo json_encode(['success' => true, 'category' => $categories[$input['id']]]);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
