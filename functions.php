<?php

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
  // Ensure the 'json' directory exists, create if not
  $debugFilePath = '/var/www/html/json/debug.txt';

  // Append the new info to the current data
  $currentData = [];
  $currentData[] = $info;

  // Save the updated data back to the JSON file
  file_put_contents($debugFilePath, json_encode($currentData, JSON_PRETTY_PRINT), FILE_APPEND);
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