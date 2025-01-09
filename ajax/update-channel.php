<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input']);
        exit;
    }

    $updateHandle = isset($input['update_handle']) ? (bool) $input['update_handle'] : false;

    $channelFile = __DIR__ . '/../json/channel.json';
    $channels = file_exists($channelFile) ? json_decode(file_get_contents($channelFile), true) : [];

    // Update the handle if requested
    if ($updateHandle) {
        if (!isset($channels[$input['id']])) {
            echo json_encode(['error' => 'Input channel id not found: ' . $input['id']]);
            exit;
        }

        if (isset($input['handle'])) {
            $channels[$input['id']]['handle'] = $input['handle'];
        } else {
            echo json_encode(['error' => 'Handle data missing']);
            exit;
        }
    }
    else {
        if (!isset($input['data'])) {
            echo json_encode(['error' => 'Invalid input']);
            exit;
        }
        // Merge or update data for the given ID
        if (isset($channels[$input['id']]) && is_array($channels[$input['id']])) {
            $channels[$input['id']] = array_merge($channels[$input['id']], $input['data']);
        } else {
            $channels[$input['id']] = $input['data'];
        }
    }

    file_put_contents($channelFile, json_encode($channels, JSON_PRETTY_PRINT));

    echo json_encode(['success' => true, 'channel' => $channels[$input['id']]]);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
