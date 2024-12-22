<?php 

$apiKey = "AIzaSyD10I62WSWrEaOoHP52NLYy5p51h-QCSMU";

// Function to format input
function formatCommaSeparatedInput($input) {
  return preg_replace('/,\s*/', ', ', $input);
}

function debugLog($info) {
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
function createCleanFilename(string $name): string {
  $name = strtolower($name); // Convert to lowercase
  $name = preg_replace('/[^a-z0-9\s_-]/', '', $name); // Remove unwanted characters
  $name = preg_replace('/[\s]+/', '_', $name); // Replace spaces with underscores

  return trim($name, '_'); // Trim leading and trailing underscores
}

/* Handle the notification */
function notification() {
  if (!empty($_SESSION['notification'])) {
    echo $_SESSION['notification'];
    unset($_SESSION['notification']);
  }
}

// Function to trim description at 150 characters but crop at full word
function trimDescription($text, $maxLength = 150) {
  if (strlen($text) <= $maxLength) {
      return $text;
  }

  $trimmed = substr($text, 0, $maxLength);
  return substr($trimmed, 0, strrpos($trimmed, ' ')) . '...';
}