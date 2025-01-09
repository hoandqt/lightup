<?php

$debug = true;
$siteName = "LightUp.TV";
$siteURL = "https://lightup.tv";
$apiKey = "AIzaSyD10I62WSWrEaOoHP52NLYy5p51h-QCSMU";
$rootFolderPath = "/";
$mainContainerClass = "container mx-auto p-4 sm:p-8";

// Function to format input
function formatCommaSeparatedInput($input)
{
  return preg_replace('/,\s*/', ', ', $input);
}

function debugLog($info)
{
  $debugFilePath = '/var/www/html/json/debug.txt';

  // Append the new info to the current data
  $currentData = [];
  $currentData[] = $info;

  // Save the updated data back to the JSON file
  file_put_contents($debugFilePath, json_encode($currentData, JSON_PRETTY_PRINT), FILE_APPEND);
}

function randomVersion()
{
    $timestamp = time(); // Get the current Unix timestamp
    echo "v={$timestamp}";
}

/**
 * Create a clean filename by converting to lowercase, replacing spaces with underscores,
 * and removing non-alphanumeric characters except underscores.
 *
 * @param string $name The original name.
 * @return string The cleaned filename without extension.
 */
function createCleanFilename(string $name): string
{
  $name = strtolower($name); // Convert to lowercase
  $name = preg_replace('/[^a-z0-9\s_-]/', '', $name); // Remove unwanted characters
  $name = preg_replace('/[\s]+/', '_', $name); // Replace spaces with underscores

  return trim($name, '_'); // Trim leading and trailing underscores
}

/* Handle the notification */
function notification()
{
  if (!empty($_SESSION['notification'])) {
    echo $_SESSION['notification'];
    unset($_SESSION['notification']);
  }
}

// Function to trim description at 150 characters but crop at full word
function trimDescription($text, $maxLength = 150)
{
  if (strlen($text) <= $maxLength) {
    return $text;
  }

  $trimmed = substr($text, 0, $maxLength);
  return substr($trimmed, 0, strrpos($trimmed, ' ')) . '...';
}

// Function to generate YouTube embed URL
function generateEmbedUrl($url)
{
  // Check if the URL is a YouTube URL
  if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
    parse_str(parse_url($url, PHP_URL_QUERY), $queryParams);
    $videoId = $queryParams['v'] ?? null;
    if ($videoId) {
      return "https://www.youtube.com/embed/" . $videoId . "?controls=0&showinfo=0&modestbranding=1&rel=0&enablejsapi=1";
    }
  }

  return $url; // Return the original URL if it's not a valid YouTube URL
}

// Helper function to generate a unique 9-character string
function generateUniqueId($length = 9) {
  return substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'), 0, $length);
}

// Function to transliterate text to ASCII
function transliterateToAscii($text) {
  return transliterator_transliterate('Any-Latin; Latin-ASCII', $text);
}

// Helper function to create a URL-friendly alias
function generateAlias($title) {
  // Transliterate special characters to ASCII
  $transliterated = transliterator_transliterate('Any-Latin; Latin-ASCII', $title);

  // Replace spaces and special characters with a dash
  $alias = preg_replace('/[^A-Za-z0-9-]+/', '-', $transliterated);

  // Replace multiple consecutive dashes with a single dash
  $alias = preg_replace('/-+/', '-', $alias);

  // Convert to lowercase and trim leading/trailing dashes
  return strtolower(trim($alias, '-'));
}

/* 
 * Check if a url is existing in json file for video or playlist
 * @param string $link
 * @param string $type
 * @return array['status' => '', 'message' => ''].
 */
function isUrlExisting($link, $type) {
  // Determine the directory path based on type
  $baseDir = __DIR__;
  $filePath = '';

  if ($type === 'playlist') {
      $filePath = $baseDir . "/playlist-data/";
  } elseif ($type === 'video') {
      $filePath = $baseDir . "/item-data/";
  } else {
      return [
          'status' => 'error',
          'message' => 'Invalid type specified. Use "playlist" or "video".'
      ];
  }

  // Open the directory and check each JSON file
  foreach (new DirectoryIterator($filePath) as $fileInfo) {
      if ($fileInfo->isDot() || !$fileInfo->isDir()) {
          continue;
      }

      $uniqueId = $fileInfo->getFilename();
      $jsonFilePath = $filePath . $uniqueId . "/" . $uniqueId . ".json";

      if (!file_exists($jsonFilePath)) {
          continue;
      }

      $data = json_decode(file_get_contents($jsonFilePath), true);
      if ($data === null) {
          continue; // Skip invalid JSON files
      }

      // Check the URL field
      if ($type === 'playlist' && isset($data['playlist_link']) && $data['playlist_link'] === $link) {
          return [
              'status' => 'error',
              'message' => 'URL exists in playlist data.'
          ];
      }

      if ($type === 'video' && isset($data['video_link']) && $data['video_link'] === $link) {
          return [
              'status' => 'error',
              'message' => 'URL exists in video data.'
          ];
      }
  }

  return [
      'status' => 'success',
      'message' => 'URL not existed.'
  ];
}

// Generate image URL from the JSON
function getImageUrl($unique_id)
{
  // Define the path to the JSON file
  $jsonPath = __DIR__ . "/item-data/$unique_id/$unique_id.json";

  // Define the default image URL
  $defaultImage = "/images/default-image.jpeg";

  // Check if the JSON file exists
  if (!file_exists($jsonPath)) {
    return $defaultImage; // Return the default image if the JSON file does not exist
  }

  // Read and decode the JSON file
  $jsonContent = file_get_contents($jsonPath);
  $data = json_decode($jsonContent, true);

  // Check if the JSON decoding was successful
  if ($data === null) {
    return $defaultImage; // Return the default image if the JSON is invalid
  }

  // Check for the 'thumbnail' key
  if (!empty($data['thumbnail'])) {
    // Construct the image URL using the 'thumbnail' value
    return "/item-data/$unique_id/" . $data['thumbnail'];
  }

  // Check for the 'video_thumbnail_url' key
  if (!empty($data['video_thumbnail_url'])) {
    // Use the 'video_thumbnail_url' directly
    return $data['video_thumbnail_url'];
  }

  // Return the default image if neither 'thumbnail' nor 'video_thumbnail_url' is available
  return $defaultImage;
}

// Get new id
function getNewId($type) {
  $latestId = 0;

  if (!empty($type)) {
    // Fetch the latest ID from /json/latest.json
    $latestJsonPath = __DIR__ . '/json/latest.json';

    if (file_exists($latestJsonPath)) {
        $latestData = json_decode(file_get_contents($latestJsonPath), true);
        if (isset($latestData[$type]['id'])) {
            $latestId = (int) $latestData[$type]['id'];
        }
    }

    // Increment the ID for the new video
    $newId = $latestId + 1;

    return $newId;
  }

  return $latestId;
}

/**
 * Get channel ID based on the channel name.
 *
 * @param string $channelHandle The handle of the channel to look for.
 * @return string|null The channel ID, or null if no channel handle is matched.
 */
function getChannelId($channelHandle): ?string
{
    if (empty($channelHandle)) {
        return null; // Return null if no channel name is provided
    }

    // Load existing channels from channel.json
    $channelFilePath = __DIR__ . '/json/channel.json';
    $channels = file_exists($channelFilePath) ? json_decode(file_get_contents($channelFilePath), true) : [];

    // Ensure channels is an array
    if (!is_array($channels)) {
        $channels = [];
    }

    // Check if the channel already exists
    foreach ($channels as $id => $existingChannel) {
        if (strcasecmp($existingChannel['handle'], $channelHandle) === 0) {
            return $id; // Return the existing channel ID
        }
    }

    // Return null if not match
    return null;
}

function addNewChannel($channelName, $channelUniqueId, $channelHandle) {
    // Load existing channels from channel.json
    $channelFilePath = __DIR__ . '/json/channel.json';
    $channels = file_exists($channelFilePath) ? json_decode(file_get_contents($channelFilePath), true) : [];

    // Check and get the largest id number
    $maxId = null; // Initialize variable to store the maximum numeric ID

    foreach ($channels as $id => $channelDetails) {
        // Convert $id to a number for comparison
        $numericId = (int)$id;
    
        // Update $maxId if it's null or the current $numericId is larger
        if ($maxId === null || $numericId > $maxId) {
            $maxId = $numericId;
        }
    }
    $newChannelId = $maxId + 1;

    // Add the new channel to the list
    $channels[$newChannelId] = [
        'name' => $channelName,
        "channel_unique_id" => $channelUniqueId,
        'handle' => $channelHandle,
        'channel_type' => '',
        'channel_description' => ''
    ];

    // Save the updated channels list back to channel.json
    file_put_contents($channelFilePath, json_encode($channels, JSON_PRETTY_PRINT));

    return $newChannelId;
}

function getChannelHandle($channelUniqueId, $apiKey) {
  if (!empty($channelUniqueId)) {
      $channelApiUrl = "https://www.googleapis.com/youtube/v3/channels?part=snippet&id=$channelUniqueId&key=$apiKey";
      $channelResponse = file_get_contents($channelApiUrl);
      $channelData = json_decode($channelResponse, true);
      if (!empty($channelData['items'][0]['snippet']['customUrl'])) {
          return $channelData['items'][0]['snippet']['customUrl']; // Return the handle
      }
  }

  return null; // Handle not found
}

// Function to get related videos
function getRelatedVideos($category, $excludeId)
{
  $relatedVideos = [];
  $videoDir = __DIR__ . '/item-data/';

  foreach (glob($videoDir . '*/') as $dir) {
    $jsonFile = $dir . basename($dir) . '.json';
    if (file_exists($jsonFile)) {
      $videoData = json_decode(file_get_contents($jsonFile), true);
      if (!empty($videoData['category']) && $videoData['category'] === $category && $videoData['unique_id'] !== $excludeId) {
        $relatedVideos[] = [
          'title' => $videoData['title'],
          'link' => '/video/' . $videoData['alias'],
          'thumbnail' => "/item-data/{$videoData['unique_id']}/{$videoData['thumbnail']}"
        ];

        // Break early if we reach the limit of 20
        if (count($relatedVideos) >= 20) {
          break;
        }
      }
    }
  }

  return $relatedVideos;
}

function getPlaylistVideos($uniqueId) {
    // Path to the playlist data directory
    $playlistPath = __DIR__ . "/playlist-data/{$uniqueId}/{$uniqueId}.json";
    
    // Check if the playlist file exists
    if (!file_exists($playlistPath)) {
        return [];
    }

    // Decode the playlist JSON
    $playlistData = json_decode(file_get_contents($playlistPath), true);
    if (empty($playlistData['playlist_videos']) || !is_array($playlistData['playlist_videos'])) {
        return [];
    }

    $videos = [];
    foreach ($playlistData['playlist_videos'] as $videoId) {
        // Path to the video metadata JSON file
        $videoPath = __DIR__ . "/item-data/{$videoId}/{$videoId}.json";

        // Check if the video file exists
        if (file_exists($videoPath)) {
            $videoData = json_decode(file_get_contents($videoPath), true);
            
            if (!empty($videoData['title']) && !empty($videoData['thumbnail']) && !empty($videoData['alias'])) {
                $videos[] = [
                  'unique_id' => $videoData['unique_id'],
                  'title' => $videoData['title'],
                  'thumbnail' => "/item-data/{$videoId}/{$videoData['thumbnail']}",
                  'link' => '/video/'.$videoData['alias'],
                  'video_link' => $videoData['video_link']
                ];
            }
        }
    }

    return $videos;
}

function getPlaylistRemoteVideos($uniqueId) {
  // Path to the playlist data directory
  $playlistPath = __DIR__ . "/playlist-data/{$uniqueId}/{$uniqueId}.json";
  
  // Check if the playlist file exists
  if (!file_exists($playlistPath)) {
      return [];
  }

  // Decode the playlist JSON
  $playlistData = json_decode(file_get_contents($playlistPath), true);
  if (empty($playlistData['playlist_videos']) || !is_array($playlistData['playlist_videos'])) {
      return [];
  }

  $videos = [];
  foreach ($playlistData['playlist_videos'] as $videoId) {
      // Path to the video metadata JSON file
      $videoPath = __DIR__ . "/item-data/{$videoId}/{$videoId}.json";

      // Check if the video file exists
      if (file_exists($videoPath)) {
          $videoData = json_decode(file_get_contents($videoPath), true);
          
          if (!empty($videoData['title']) && !empty($videoData['thumbnail']) && !empty($videoData['alias'])) {
              $videos[] = [
                  'unique_id' => $videoData['unique_id'],
                  'title' => $videoData['title'],
                  'thumbnail' => "/item-data/{$videoId}/{$videoData['thumbnail']}",
                  'link' => '/video/'.$videoData['alias'],
                  'video_link' => $videoData['video_link']
              ];
          }
      }
  }

  return $videos;
}

/**
 * Adds a video to a playlist.
 *
 * @param string $playlistUniqueId The unique ID of the playlist.
 * @param string $videoUniqueId The unique ID of the video.
 * @return array The result of the operation with success status and message.
 */
function addVideoToPlaylist($playlistUniqueId, $videoUniqueId)
{
    $playlistDataDir = __DIR__ . '/playlist-data';

    // Validate input
    if (empty($playlistUniqueId) || empty($videoUniqueId)) {
        return ['success' => false, 'message' => 'Invalid input'];
    }

    // Path to the playlist JSON file
    $playlistPath = "{$playlistDataDir}/{$playlistUniqueId}/{$playlistUniqueId}.json";

    // Check if the playlist file exists
    if (!file_exists($playlistPath)) {
        return ['success' => false, 'message' => "Playlist <strong>$playlistUniqueId</strong> not found."];
    }

    // Load the existing playlist
    $playlistData = json_decode(file_get_contents($playlistPath), true);

    // Ensure playlist_videos key exists
    if (!isset($playlistData['playlist_videos']) || !is_array($playlistData['playlist_videos'])) {
        $playlistData['playlist_videos'] = [];
    }

    // Add the video ID to the playlist if it's not already present
    if (in_array($videoUniqueId, $playlistData['playlist_videos'])) {
        return ['success' => false, 'message' => "Video <strong>$videoUniqueId</strong> is already in the playlist <strong>$playlistUniqueId</strong>."];
    }

    $playlistData['playlist_videos'][] = $videoUniqueId;

    // Save the updated playlist
    if (file_put_contents($playlistPath, json_encode($playlistData, JSON_PRETTY_PRINT))) {
        return ['success' => true, 'message' => "Video <strong>$videoUniqueId</strong> added to a playlist <strong>$playlistUniqueId</strong> successfully."];
    }

    return ['success' => false, 'message' => "Failed to save <strong>$playlistUniqueId</strong> playlist."];
}
function removeVideoFromPlaylist($playlistID, $removedVideoId) {
  // Define the path to the playlist JSON file
  $playlistFilePath = __DIR__ . "/playlist-data/" . $playlistID . "/" . $playlistID . ".json";

  // Check if the playlist JSON file exists
  if (!file_exists($playlistFilePath)) {
      return [
          'success' => false,
          'message' => "Playlist <strong>$playlistID</strong> file not found."
      ];
  }

  // Load the playlist data
  $playlistData = json_decode(file_get_contents($playlistFilePath), true);

  // Check if the playlist contains videos
  if (!isset($playlistData['playlist_videos']) || !is_array($playlistData['playlist_videos'])) {
      return [
          'success' => false,
          'message' => "Playlist <strong>$playlistID</strong> videos not found or invalid."
      ];
  }

  // Check if the video exists in the playlist
  if (!in_array($removedVideoId, $playlistData['playlist_videos'])) {
      return [
          'success' => false,
          'message' => "Video ID <strong>$removedVideoId</strong> not found in the playlist <strong>$playlistID</strong>."
      ];
  }

  // Remove the video from the playlist
  $playlistData['playlist_videos'] = array_values(array_filter(
      $playlistData['playlist_videos'],
      function ($videoId) use ($removedVideoId) {
          return $videoId !== $removedVideoId;
      }
  ));

  // Save the updated playlist data back to the JSON file
  if (file_put_contents($playlistFilePath, json_encode($playlistData, JSON_PRETTY_PRINT)) === false) {
      return [
          'success' => false,
          'message' => "Failed to update the playlist <strong>$playlistID</strong> file."
      ];
  }

  return [
      'success' => true,
      'message' => "Video <strong>$removedVideoId</strong> successfully removed from the playlist <strong>$playlistID</strong>."
  ];

}

// Define the addXmlEntry function
function addXmlEntry($path, $priority = 0.7)
{
  $sitemapFile = __DIR__ . '/sitemap.xml';
  $currentDate = date('Y-m-d\TH:i:sP'); // Format for lastmod in XML

  // Check if sitemap.xml exists; if not, create a basic structure
  if (!file_exists($sitemapFile)) {
    $sitemapContent = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
</urlset>
XML;
    file_put_contents($sitemapFile, $sitemapContent);
  }

  // Load the existing sitemap
  $xml = new DOMDocument();
  $xml->load($sitemapFile);

  // Check if the entry already exists
  $existingEntries = $xml->getElementsByTagName('url');
  foreach ($existingEntries as $entry) {
    $locElement = $entry->getElementsByTagName('loc')->item(0);
    if ($locElement && $locElement->nodeValue === $path) {
      return false; // Entry already exists
    }
  }

  // Add a new <url> entry
  $urlset = $xml->getElementsByTagName('urlset')->item(0);

  $url = $xml->createElement('url');

  $loc = $xml->createElement('loc', htmlspecialchars($path));
  $lastmod = $xml->createElement('lastmod', $currentDate);
  $priority = $xml->createElement('priority', $priority);

  $url->appendChild($loc);
  $url->appendChild($lastmod);
  $url->appendChild($priority);

  $urlset->appendChild($url);

  // Save the updated sitemap
  $xml->formatOutput = true;
  $xml->save($sitemapFile);

  return true;
}