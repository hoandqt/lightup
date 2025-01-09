<?php
require_once '../functions.php';

// Get the query from the AJAX request
$data = json_decode(file_get_contents('php://input'), true);
$query = transliterateToAscii(strtolower(trim($data['query'] ?? '')));
$topQuery = transliterateToAscii(strtolower(trim($data['top_query'] ?? '')));
$playlistQuery = transliterateToAscii(strtolower(trim($data['playlist_query'] ?? '')));
$response = [];

// File paths to search
$files = [
    __DIR__ . '/../json/content.json'
];

if (!empty($query)) {
    foreach ($files as $filePath) {
        if (!file_exists($filePath)) {
            continue;
        }

        $items = json_decode(file_get_contents($filePath), true);
        foreach ($items as $item) {
            $detailsPath = ($filePath === __DIR__ . '/../json/content.json'
                ? __DIR__ . "/../item-data/{$item['unique_id']}/{$item['unique_id']}.json"
                : __DIR__ . "/../post-data/{$item['unique_id']}/{$item['unique_id']}.json");

            if (!file_exists($detailsPath)) {
                continue;
            }

            $details = json_decode(file_get_contents($detailsPath), true);

            // Extract searchable fields and transliterate them
            $title = transliterateToAscii(strtolower($details['title'] ?? ''));
            $description = transliterateToAscii(strtolower($details['description'] ?? ''));
            $newDescription = transliterateToAscii(strtolower($details['new_description'] ?? ''));

            // Check if the query matches any of the fields
            if (strpos($title, $query) !== false || strpos($description, $query) !== false || strpos($newDescription, $query) !== false) {
                $response[] = [
                    'id' => $details['unique_id'] ?? 'Unknown',
                    'title' => $details['title'] ?? 'Untitled',
                    'link' => $filePath === __DIR__ . '/../json/content.json'
                        ? "/video/{$details['alias']}"
                        : "/post/{$details['alias']}",
                    'description' => trimDescription($details['description'] ?? '', 150),
                    'thumbnail' => !empty($details['thumbnail'])
                        ? "/item-data/{$item['unique_id']}/{$details['thumbnail']}"
                        : 'images/default-image.jpeg',
                    'created_date' => $details['created_date'] ?? '',
                    'views' => $details['views'] ?? 0
                ];
            }
        }
    }
} else if (!empty($topQuery)) {
  debugLog($topQuery);
    foreach ($files as $filePath) {
        if (!file_exists($filePath)) {
            continue;
        }

        $items = json_decode(file_get_contents($filePath), true);
        foreach ($items as $item) {
            $detailsPath = ($filePath === __DIR__ . '/../json/content.json'
                ? __DIR__ . "/../item-data/{$item['unique_id']}/{$item['unique_id']}.json"
                : __DIR__ . "/../post-data/{$item['unique_id']}/{$item['unique_id']}.json");

            if (!file_exists($detailsPath)) {
                continue;
            }

            $details = json_decode(file_get_contents($detailsPath), true);

            $title = transliterateToAscii(strtolower($details['title'] ?? ''));

            if (strpos($title, $topQuery) !== false) {
                $response[] = [
                    'id' => $details['unique_id'] ?? 'Unknown',
                    'title' => $details['title'] ?? 'Untitled',
                    'link' => $filePath === __DIR__ . '/../json/content.json'
                        ? "/video/{$details['alias']}"
                        : "/post/{$details['alias']}",
                ];
            }
        }
    }

    // Limit the response to 5 results
    $response = array_slice($response, 0, 5);
} else if (!empty($playlistQuery)) {
    $files = [
        __DIR__ . '/../json/playlist-content.json'
    ];

    foreach ($files as $filePath) {
        if (!file_exists($filePath)) {
            continue;
        }

        $items = json_decode(file_get_contents($filePath), true);
        foreach ($items as $item) {
            $detailsPath = __DIR__ . "/../playlist-data/{$item['unique_id']}/{$item['unique_id']}.json";

            if (!file_exists($detailsPath)) {
                continue;
            }

            $details = json_decode(file_get_contents($detailsPath), true);

            $title = transliterateToAscii(strtolower($details['title'] ?? ''));

            if (strpos($title, $playlistQuery) !== false) {
                $response[] = [
                    'id' => $details['unique_id'] ?? 'Unknown',
                    'title' => $details['title'] ?? 'Untitled',
                    'link' => "/playlist/{$details['alias']}"
                ];
            }
        }
    }

    // Limit the response to 10 results
    $response = array_slice($response, 0, 10);
}

// Return the response as JSON
header('Content-Type: application/json');
echo json_encode($response);