<?php
session_start();

require_once "functions.php";

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $redirectUrl = urlencode($_SERVER['REQUEST_URI']);
    header("Location: login.php?redirect=$redirectUrl");
    exit;
}

// Get video unique ID from the query parameter and other files.
$uniqueId = $_GET['id'] ?? null;
$videoDir = __DIR__ . "/item-data/$uniqueId/";
$jsonFilePath = $videoDir . "$uniqueId.json";
$contentFile = __DIR__ . '/json/content.json';

// Read the JSON data
$videoData = json_decode(file_get_contents($jsonFilePath), true);
$additionalFiles = $videoData['additional_files'] ?? [];

// Load content.json and retrieve the posted_date
$contentData = file_exists($contentFile) ? json_decode(file_get_contents($contentFile), true) : [];
$postedDate = $contentData[$uniqueId]['posted_date'] ?? $videoData['posted_date'] ?? date('Y-m-d H:i:s');

// Load channels from channel.json
$channelFile = __DIR__ . '/json/channel.json';
$channels = file_exists($channelFile) ? json_decode(file_get_contents($channelFile), true) : [];

// Load categories from video-category.json
$categoryFile = __DIR__ . '/json/video-category.json';
$categories = file_exists($categoryFile) ? json_decode(file_get_contents($categoryFile), true) : [];

// Handle form submission to update the video details
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $videoData['channel'] = $_POST['channel'];
    $videoData['title'] = $_POST['title'];
    $videoData['description'] = $_POST['description'];
    $videoData['tags'] = formatCommaSeparatedInput($_POST['tags']);
    $videoData['meta_title'] = $_POST['meta_title'];
    $videoData['meta_description'] = $_POST['meta_description'];
    $videoData['meta_keywords'] = formatCommaSeparatedInput($_POST['meta_keywords']);
    $videoData['video_link'] = $_POST['video_link'];
    $videoData['video_thumbnail_url'] = $_POST['video_thumbnail_url'];
    $videoData['category'] = $_POST['category'];
    $videoData['subcategory'] = $_POST['subcategory'];
    $videoData['notes'] = $_POST['notes'];
    $videoData['updated_date'] = date('Y-m-d H:i:s');

    // Update alias if the title changes
    $videoData['alias'] = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $videoData['title']), '-'));

    // Handle new thumbnail upload or remote image download
    if (!empty($_FILES['thumbnail']['name'])) {
      // Handle file upload
      $allowedThumbnailTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
      $thumbnailName = basename($_FILES['thumbnail']['name']);

      if (in_array($_FILES['thumbnail']['type'], $allowedThumbnailTypes)) {
          // Remove old thumbnail if it exists
          if (!empty($videoData['thumbnail']) && file_exists($videoDir . $videoData['thumbnail'])) {
              unlink($videoDir . $videoData['thumbnail']);
          }

          // Check if the name already contains the 'thumbnail_' prefix
          if (!str_starts_with($thumbnailName, 'thumbnail_')) {
              $thumbnailPath = 'thumbnail_' . $thumbnailName;
          } else {
              $thumbnailPath = $thumbnailName;
          }

          move_uploaded_file($_FILES['thumbnail']['tmp_name'], $videoDir . $thumbnailPath);
          $videoData['thumbnail'] = $thumbnailPath;
      }
    } elseif (!empty($videoData['video_thumbnail_url'])) {
      if (!empty($_POST['download_thumbnail']) && $_POST['download_thumbnail'] === '1') {
        // Handle remote image download
        $remoteImageUrl = $videoData['video_thumbnail_url'];
        $thumbnailName = 'thumbnail_' . uniqid() . '.jpg'; // Generate a unique name for the thumbnail
        $thumbnailPath = $videoDir . $thumbnailName;

        // Attempt to download the remote image
        $imageContent = file_get_contents($remoteImageUrl);
        if ($imageContent) {
            file_put_contents($thumbnailPath, $imageContent);
            // Remove old thumbnail if it exists
            if (!empty($videoData['thumbnail']) && file_exists($videoDir . $videoData['thumbnail'])) {
                unlink($videoDir . $videoData['thumbnail']);
            }
            $videoData['thumbnail'] = $thumbnailName;
        } else {
            echo "<div class='site-notification text-red-500 error'>Error: Unable to download the remote thumbnail.</div>";
        }
      }
    }

    // Preserve existing additional_files
    if (!isset($videoData['additional_files'])) {
        $videoData['additional_files'] = [];
    }

    // Handle new additional files upload
    if (!empty($_FILES['additional_files']['name'][0])) {
      $allowedFileTypes = [
          'image/png', 'image/jpeg', 'image/jpg', 'image/webp',
          'audio/mp3', 'audio/wav', 'video/mp4',
          'application/zip', 'application/x-tar', 'application/gzip',
          'application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
          'text/plain'
      ];
      foreach ($_FILES['additional_files']['name'] as $key => $name) {
          if (in_array($_FILES['additional_files']['type'][$key], $allowedFileTypes)) {
              $path = $videoDir . 'file_' . basename($name);
              move_uploaded_file($_FILES['additional_files']['tmp_name'][$key], $path);
              $additionalFiles[] = 'file_' . basename($name);
          }
      }
    }

    // Filter out removed additional files and delete them from the server
    $removedFiles = isset($_POST['removed_files']) ? json_decode($_POST['removed_files'], true) : [];
    foreach ($removedFiles as $fileToRemove) {
        $decodedFileName = urldecode($fileToRemove); // Decode to the origianal file name
        $filePath = $videoDir . $decodedFileName;
        if (file_exists($filePath)) {
            unlink($filePath); // Delete the file from the server
        }

        // Remove the file entry from the JSON data
        $additionalFiles = array_filter($additionalFiles, function ($file) use ($decodedFileName) {
          return $file !== $decodedFileName;
        });
    }
    $videoData['additional_files'] = array_diff($additionalFiles, $removedFiles);

    // Save updated data to the JSON file
    file_put_contents($jsonFilePath, json_encode($videoData, JSON_PRETTY_PRINT));

    // Update or create content.json
    $content = file_exists($contentFile) ? json_decode(file_get_contents($contentFile), true) : [];
    $content[$uniqueId] = [
        'unique_id' => $uniqueId,
        'posted_date' => $postedDate,
        'updated_date' => $videoData['updated_date'],
    ];
    file_put_contents($contentFile, json_encode($content, JSON_PRETTY_PRINT));

    // Start the session if not already started
    if (session_status() == PHP_SESSION_NONE) {
      session_start();
    }

    // Set the success message in the session
    $_SESSION['notification'] = "<div class='site-notification text-green-500 success'>Video information updated successfully!</div>";
    
    // Redirect to the video page using the unique ID
    $redirectUrl = '/video.php?id=' . urlencode($uniqueId);

    // Perform the redirection
    header("Location: $redirectUrl", true, 302);
    exit();
}

$pageTitle = "Edit Video Details";
$pageDescription = "Edit Video Details on LightUp.TV";
$pageKeywords = "";
$canonicalURL = "https://www.lightup.tv/edit-video.php?id=$uniqueId";
include 'header.php';
include 'menu.php';
include 'sub-heading.php';

// Check for video id
if (!$uniqueId) {
  echo "<div class='site-notification text-red-500 error'>Error: Video ID not provided.</div>";
  exit;
}

// Check if the JSON file exists
if (!file_exists($jsonFilePath)) {
    echo "<div class='site-notification text-red-500 error'>Error: Video not found.</div>";
    exit;
}
?>
<div class="container mx-auto p-8">
  <h1 class="text-3xl font-bold text-text-light mb-6">Edit Video Details</h1>

  <form action="" method="POST" enctype="multipart/form-data" class="bg-gray-800 shadow-lg rounded-lg p-6 space-y-6">
  
    <!-- Channel Dropdown -->
    <div>
        <label for="channel" class="block text-sm font-medium text-text-light">Channel</label>
        <select name="channel" id="channel" required class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md p-2">
            <option value="">Select a Channel</option>
            <?php foreach ($channels as $channelId => $channel): ?>
                <option value="<?= htmlspecialchars($channelId) ?>" <?= $videoData['channel'] == $channelId ? 'selected' : '' ?>>
                    <?= htmlspecialchars($channel['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Video Link -->
    <div>
        <label for="video_link" class="block text-sm font-medium text-text-light">Video Link</label>
        <input type="url" name="video_link" id="video_link" required 
            class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2" 
            value="<?= htmlspecialchars($videoData['video_link']) ?>">
        <button type="button" onclick="fetchYouTubeDetails()" 
        class="px-4 py-2 mt-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
            Fetch Details
        </button>
    </div>

    <!-- Thumbnail Preview with Download -->
    <div>
        <a id="thumbnail-download-link" href="#" download="youtube_thumbnail.jpg" class="hidden">
            <img id="youtube-thumbnail" src="" alt="Thumbnail Preview" class="w-48 h-32 mt-2 rounded cursor-pointer">
        </a>
    </div>

    <!-- Title -->
    <div>
      <label for="title" class="block text-sm font-medium text-text-light">Title</label>
      <input type="text" name="title" id="title" value="<?= htmlspecialchars($videoData['title']) ?>" required
        class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md p-2">
    </div>

    <!-- Thumbnail -->
    <div>
      <?php
        // Display Thumbnail with Delete Option
        $thumbnailHtml = "";
        if (!empty($videoData['thumbnail']) && file_exists($videoDir . $videoData['thumbnail'])) {
            // Local thumbnail exists
            $randomNumber = rand(0, 99999);
            $thumbnailPath = "item-data/$uniqueId/" . $videoData['thumbnail'] . "?v=".$randomNumber;
            $thumbnailHtml = "
                <div class='thumbnail-section'>
                    <label for='thumbnail' class='block text-sm font-medium text-text-light'>Thumbnail Image (Current use: Local)</label>
                    <img src='$thumbnailPath' alt='Thumbnail' class='w-48 h-32 mt-2 mb-2 rounded'>
                    <button type='button' onclick='deleteThumbnail(".$uniqueId.")' class='px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700'>
                        Delete Thumbnail
                    </button>
                </div>";
        } elseif (!empty($videoData['video_thumbnail_url'])) {
            // Fallback to remote thumbnail
            $thumbnailPath = htmlspecialchars($videoData['video_thumbnail_url']);
            $thumbnailHtml = "
                <div class='thumbnail-section'>
                    <label for='thumbnail' class='block text-sm font-medium text-text-light'>Thumbnail Image (Current use: Remote)</label>
                    <img src='$thumbnailPath' alt='Thumbnail' class='w-48 h-32 mt-2 mb-2 rounded'>
                </div>";
        }

        // Handle Delete Thumbnail Request
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_thumbnail'])) {
            if (!empty($videoData['thumbnail']) && file_exists($videoDir . $videoData['thumbnail'])) {
                unlink($videoDir . $videoData['thumbnail']); // Delete the local thumbnail
                $videoData['thumbnail'] = ""; // Remove from JSON
                file_put_contents($jsonFilePath, json_encode($videoData, JSON_PRETTY_PRINT)); // Save updated JSON
                echo "<div class='section-notification text-green-500 success'>Thumbnail deleted successfully!</div>";
            } else {
                echo "<div class='section-notification text-red-500 error'>Error: Thumbnail not found or already deleted.</div>";
            }
        }
      ?>

      <!-- Add Thumbnail Preview Section -->
      <?= $thumbnailHtml ?>
      <div class="flex items-center space-x-4 mt-4">
          <input type="file" name="thumbnail" id="thumbnail" accept=".jpeg, .jpg, .png, .webp"
              class="mt-1 block text-text-light file:bg-gray-700 file:text-text-light hover:file:bg-gray-600">
          <input class="inline-block border-gray-600 bg-gray-700 text-text-light rounded-md p-2" id="youtube-thumbnail-url" name="video_thumbnail_url" value="<?= htmlspecialchars($videoData['video_thumbnail_url']) ?>">
          <div class="flex items-center space-x-4">
            <input type="checkbox" id="download-thumbnail-checkbox" name="download_thumbnail" value="1" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
            <label for="download-thumbnail-checkbox" class="ms-2 text-sm font-medium text-white-900 dark:text-white-300">
                Download Remote Thumbnail to Local
            </label>
        </div>
      </div>
      <div id="thumbnail-preview" class="mt-4">
          <img id="youtube-thumbnail" src="" alt="Thumbnail Preview" class="w-32 h-32 object-cover rounded hidden">
      </div>
    </div>

    <!-- Description -->
    <div>
      <label for="description" class="block text-sm font-medium text-text-light">Description</label>
      <textarea name="description" id="description" rows="5" required
        class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md p-2"><?= htmlspecialchars($videoData['description']) ?></textarea>
    </div>

    <!-- Tags -->
    <div>
      <label for="tags" class="block text-sm font-medium text-text-light">Tags</label>
      <textarea name="tags" id="tags" rows="3" required
        class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md p-2"><?= htmlspecialchars($videoData['tags']) ?></textarea>
    </div>

    <!-- Category Dropdown -->
    <div>
        <label for="category" class="block text-sm font-medium text-text-light">Category</label>
        <select name="category" id="category" required class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md p-2">
            <option value="">Select a Category</option>
            <?php foreach ($categories as $categoryId => $category): ?>
                <option value="<?= htmlspecialchars($categoryId) ?>" <?= $videoData['category'] == $categoryId ? 'selected' : '' ?>>
                    <?= htmlspecialchars($category['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Subcategory Dropdown -->
    <div id="subcategory-container" class="mt-4 <?= !empty($videoData['subcategory']) ? '' : 'hidden' ?>">
        <label for="subcategory" class="block text-sm font-medium text-text-light">Subcategory</label>
        <select name="subcategory" id="subcategory"
            class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md p-2">
            <option value="">Select a Subcategory</option>
            <?php
            if (!empty($videoData['category'])):
                $selectedCategoryId = $videoData['category'];
                $subcategories = $categories[$selectedCategoryId]['subcategories'] ?? [];
                foreach ($subcategories as $subcategory):
                    $subcategoryName = $subcategory['name'] ?? ''; // Use the name as the identifier
            ?>
                <option value="<?= htmlspecialchars($subcategoryName) ?>"
                    <?= $videoData['subcategory'] === $subcategoryName ? 'selected' : '' ?>>
                    <?= htmlspecialchars($subcategoryName) ?>
                </option>
            <?php endforeach; endif; ?>
        </select>
    </div>
    <script src="/js/fetch-subcategories.js"></script>

    <!-- Create Metadata Button -->
    <div>
        <button type="button" id="create-metadata" 
            class="px-4 py-2 mt-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
            Generate Metadata
        </button>
        <p id="metadata-loading" class="text-gray-400 hidden">Generating metadata...</p>
    </div>
    <script src="/js/generate-metadata.js"></script>

    <!-- Meta Title -->
    <div>
        <label for="meta_title" class="block text-sm font-medium text-text-light">Meta Title</label>
        <input type="text" name="meta_title" id="meta_title" 
            class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2" value="<?= htmlspecialchars($videoData['meta_title']) ?>">
    </div>

    <!-- Meta Description -->
    <div>
        <label for="meta_description" class="block text-sm font-medium text-text-light">Meta Description</label>
        <textarea name="meta_description" id="meta_description" rows="3" 
            class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2"><?= htmlspecialchars($videoData['meta_description']) ?></textarea>
    </div>

    <!-- Meta Keywords -->
    <div>
        <label for="meta_keywords" class="block text-sm font-medium text-text-light">Meta Keywords</label>
        <textarea name="meta_keywords" id="meta_keywords" rows="3" 
            class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2"><?= htmlspecialchars($videoData['meta_keywords']) ?></textarea>
    </div>

    <!-- Notes -->
    <div>
      <label for="notes" class="block text-sm font-medium text-text-light">Notes</label>
      <textarea name="notes" id="notes" rows="3"
        class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md p-2"><?= htmlspecialchars($videoData['notes']) ?></textarea>
    </div>

    <!-- Additional Files -->
    <div>
            <label class="block text-sm font-medium text-text-light">Additional Files</label>
            <?php if (!empty($additionalFiles)): ?>
                <ul id="file-list" class="text-sm text-gray-400 space-y-2 mb-4">
                    <?php foreach ($additionalFiles as $file): ?>
                        <li id="file-<?= urlencode($file) ?>" class="flex items-center justify-between">
                            <a href="item-data/<?= $uniqueId ?>/<?= $file ?>" target="_blank"
                               class="text-indigo-500 hover:underline"><?= htmlspecialchars($file) ?></a>
                            <button type="button" class="text-red-500 hover:text-red-700 text-sm ml-4"
                                    onclick="removeFile('<?= urlencode($file) ?>')">Remove</button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <!-- File Upload Field -->
            <input type="file" name="additional_files[]" id="additional_files" multiple
                accept=".jpeg, .jpg, .png, .mp3, .wav, .mp4, .zip, .tar, .gz, .pdf, .docx, .txt"
                class="mt-2 block w-full text-text-light file:bg-gray-700 file:text-text-light hover:file:bg-gray-600">
            <input type="hidden" name="removed_files" id="removed-files" value="[]">
        </div>

    <!-- Submit -->
    <div>
      <button type="submit" class="w-full py-2 px-4 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
        Save Changes
      </button>
    </div>
  </form>
</div>

<?php include 'footer.php'; ?>