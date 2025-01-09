<?php
session_start();

require_once '../functions.php';

// Check if the user is logged in and has the admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Check if playlistId is provided
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['playlistId'])) {
    $playlistId = trim($_POST['playlistId']);

    // YouTube API settings
    $playlistApiUrl = "https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&playlistId=$playlistId&maxResults=50&key=$apiKey";

    $playlistResponse = file_get_contents($playlistApiUrl);

    if ($playlistResponse === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch data from YouTube API']);
        exit;
    }

    $playlistData = json_decode($playlistResponse, true);

    if (!isset($playlistData['items'])) {
        http_response_code(404);
        echo json_encode(['error' => 'Playlist not found or empty']);
        exit;
    }

    // Load category mappings
    $categoryMapping = json_decode(file_get_contents('../json/youtube-category.json'), true);

    $videos = [];

    foreach ($playlistData['items'] as $item) {
        $videoId = $item['snippet']['resourceId']['videoId'];
        $title = $item['snippet']['title'];
        $description = $item['snippet']['description'];
        $thumbnails = $item['snippet']['thumbnails'] ?? [];
        $thumbnail = '';
        
        // Check for thumbnails in order of size
        if (!empty($thumbnails['maxres']['url'])) {
            $thumbnail = $thumbnails['maxres']['url'];
        } elseif (!empty($thumbnails['standard']['url'])) {
            $thumbnail = $thumbnails['standard']['url'];
        } elseif (!empty($thumbnails['high']['url'])) {
            $thumbnail = $thumbnails['high']['url'];
        } elseif (!empty($thumbnails['medium']['url'])) {
            $thumbnail = $thumbnails['medium']['url'];
        } elseif (!empty($thumbnails['default']['url'])) {
            $thumbnail = $thumbnails['default']['url'];
        }
        
        // Fetch additional video details from the videos endpoint
        $videoApiUrl = "https://www.googleapis.com/youtube/v3/videos?part=snippet,statistics&id=$videoId&key=$apiKey";
        $videoResponse = file_get_contents($videoApiUrl);

        if ($videoResponse !== false) {
            $videoData = json_decode($videoResponse, true);
            $videoSnippet = $videoData['items'][0]['snippet'] ?? [];

            $tags = isset($videoSnippet['tags']) ? implode(', ', $videoSnippet['tags']) : 'No tags';
            $channelTitle = $videoSnippet['channelTitle'] ?? 'Unknown';

            $categoryId = $videoSnippet['categoryId'] ?? 'Unknown';
            $categoryName = $categoryMapping[$categoryId] ?? 'Unknown';

            $channelUniqueId = $videoSnippet['channelId'] ?? 'Unknown';
        } else {
            $tags = 'No tags';
            $channelTitle = 'Unknown';
            $categoryName = 'Unknown';
            $categoryId = 'Unknown';
            $channelUniqueId = 'Unknown';
        }

        $videos[] = [
            'channel' => $channelTitle,
            'channel_unique_id' => $channelUniqueId,
            'video_id' => $videoId,
            'video_link' => "https://www.youtube.com/watch?v=$videoId",
            'title' => $title,
            'thumbnail' => $thumbnail,
            'description' => $description,
            'category' => $categoryId,
            'category_name' => $categoryName,
            'tags' => $tags,
            'action' => "https://www.youtube.com/watch?v=$videoId"
        ];
    }

    echo json_encode($videos);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request']);
exit;
