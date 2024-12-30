<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['id'], $input['data'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input']);
        exit;
    }

    $channelFile = __DIR__ . '/json/channel.json';
    $channels = file_exists($channelFile) ? json_decode(file_get_contents($channelFile), true) : [];

    // Merge or update data for the given ID
    if (isset($channels[$input['id']]) && is_array($channels[$input['id']])) {
        $channels[$input['id']] = array_merge($channels[$input['id']], $input['data']);
    } else {
        $channels[$input['id']] = $input['data'];
    }

    file_put_contents($channelFile, json_encode($channels, JSON_PRETTY_PRINT));
    echo json_encode(['success' => true, 'channel' => $channels[$input['id']]]);
}
