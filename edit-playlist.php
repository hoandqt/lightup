<?php
session_start();

require_once "functions.php";

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $redirectUrl = urlencode($_SERVER['REQUEST_URI']);
    header("Location: login?redirect=$redirectUrl");
    exit;
}

// Get playlist unique ID from the query parameter and other files.
$uniqueId = $_GET['id'] ?? null;
$playlistDir = __DIR__ . "/playlist-data/$uniqueId/";
$jsonFilePath = $playlistDir . "$uniqueId.json";
$contentFile = __DIR__ . '/json/playlist-content.json';

// Read the JSON data
$playlistData = json_decode(file_get_contents($jsonFilePath), true);
$additionalFiles = $playlistData['additional_files'] ?? [];

// Load content.json and retrieve the posted_date
$contentData = file_exists($contentFile) ? json_decode(file_get_contents($contentFile), true) : [];
$postedDate = $contentData[$uniqueId]['posted_date'] ?? $playlistData['posted_date'] ?? date('Y-m-d H:i:s');

// Load channels from channel.json
$channelFile = __DIR__ . '/json/channel.json';
$channels = file_exists($channelFile) ? json_decode(file_get_contents($channelFile), true) : [];

// Load categories from playlist-category.json
$categoryFile = __DIR__ . '/json/playlist-category.json';
$categories = file_exists($categoryFile) ? json_decode(file_get_contents($categoryFile), true) : [];

$playlistData['visibility'] = $playlistData['visibility'] ?? 'public'; // Default to 'public' if not set

// Handle form submission to update the playlist details
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $playlistData['channel'] = $_POST['channel'];
    $playlistData['title'] = $_POST['title'];
    $playlistData['description'] = $_POST['description'];
    $playlistData['tags'] = formatCommaSeparatedInput($_POST['tags']);
    $playlistData['meta_title'] = $_POST['meta_title'];
    $playlistData['meta_description'] = $_POST['meta_description'];
    $playlistData['meta_keywords'] = formatCommaSeparatedInput($_POST['meta_keywords']);
    $playlistData['og_image_alt'] = $_POST['og_image_alt'];
    $playlistData['description_content'] = $_POST['description_content'];
    $playlistData['playlist_link'] = $_POST['playlist_link'];
    $playlistData['playlist_thumbnail_url'] = $_POST['playlist_thumbnail_url'];
    $playlistData['category'] = $_POST['category'];
    $playlistData['subcategory'] = $_POST['subcategory'];
    $playlistData['notes'] = $_POST['notes'];
    $playlistData['visibility'] = $_POST['visibility'];
    $playlistData['updated_date'] = date('Y-m-d H:i:s');

    // Update alias if the title changes
    $playlistData['alias'] = generateAlias($playlistData['title']);

    // Handle new thumbnail upload or remote image download
    if (!empty($_FILES['thumbnail']['name'])) {
      // Handle file upload
      $allowedThumbnailTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
      $thumbnailName = basename($_FILES['thumbnail']['name']);
      $fileExtension = pathinfo($thumbnailName, PATHINFO_EXTENSION);
      $thumbnailPath = 'thumbnail.'.$fileExtension;

      if (in_array($_FILES['thumbnail']['type'], $allowedThumbnailTypes)) {
          // Remove old thumbnail if it exists
          if (!empty($playlistData['thumbnail']) && file_exists($playlistDir . $playlistData['thumbnail'])) {
              unlink($playlistDir . $playlistData['thumbnail']);
          }

          move_uploaded_file($_FILES['thumbnail']['tmp_name'], $playlistDir . $thumbnailPath);
          $playlistData['thumbnail'] = $thumbnailPath;
      }
    } elseif (!empty($playlistData['playlist_thumbnail_url'])) {
      if (!empty($_POST['download_thumbnail']) && $_POST['download_thumbnail'] === '1') {
        // Handle remote image download
        $remoteImageUrl = $playlistData['playlist_thumbnail_url'];
        $thumbnailName = 'thumbnail.jpg'; // Generate a unique name for the thumbnail
        $thumbnailPath = $playlistDir . $thumbnailName;

        // Attempt to download the remote image
        $imageContent = file_get_contents($remoteImageUrl);
        if ($imageContent) {
            file_put_contents($thumbnailPath, $imageContent);
            // Remove old thumbnail if it exists
            if (!empty($playlistData['thumbnail']) && file_exists($playlistDir . $playlistData['thumbnail'])) {
                unlink($playlistDir . $playlistData['thumbnail']);
            }
            $playlistData['thumbnail'] = $thumbnailName;
        } else {
            echo "<div class='site-notification text-red-500 error'>Error: Unable to download the remote thumbnail.</div>";
        }
      }
    }

    // Preserve existing additional_files
    if (!isset($playlistData['additional_files'])) {
        $playlistData['additional_files'] = [];
    }

    // Handle new additional files upload
    if (!empty($_FILES['additional_files']['name'][0])) {
      $allowedFileTypes = [
          'image/png', 'image/jpeg', 'image/jpg', 'image/webp',
          'audio/mp3', 'audio/wav', 'playlist/mp4',
          'application/zip', 'application/x-tar', 'application/gzip',
          'application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
          'text/plain'
      ];
      foreach ($_FILES['additional_files']['name'] as $key => $name) {
          if (in_array($_FILES['additional_files']['type'][$key], $allowedFileTypes)) {
              $path = $playlistDir . 'file_' . basename($name);
              move_uploaded_file($_FILES['additional_files']['tmp_name'][$key], $path);
              $additionalFiles[] = 'file_' . basename($name);
          }
      }
    }

    // Filter out removed additional files and delete them from the server
    $removedFiles = isset($_POST['removed_files']) ? json_decode($_POST['removed_files'], true) : [];
    foreach ($removedFiles as $fileToRemove) {
        $decodedFileName = urldecode($fileToRemove); // Decode to the origianal file name
        $filePath = $playlistDir . $decodedFileName;
        if (file_exists($filePath)) {
            unlink($filePath); // Delete the file from the server
        }

        // Remove the file entry from the JSON data
        $additionalFiles = array_filter($additionalFiles, function ($file) use ($decodedFileName) {
          return $file !== $decodedFileName;
        });
    }
    $playlistData['additional_files'] = array_diff($additionalFiles, $removedFiles);

    // Save updated data to the JSON file
    file_put_contents($jsonFilePath, json_encode($playlistData, JSON_PRETTY_PRINT));

    // Update or create content.json
    $content = file_exists($contentFile) ? json_decode(file_get_contents($contentFile), true) : [];
    $content[$uniqueId] = [
        'id' => $playlistData['id'],
        'unique_id' => $uniqueId,
        'posted_date' => $postedDate,
        'updated_date' => date('Y-m-d H:i:s'),
        'category' => $playlistData['category'],
        'visibility' => $playlistData['visibility']
    ];
    file_put_contents($contentFile, json_encode($content, JSON_PRETTY_PRINT));

    // Start the session if not already started
    if (session_status() == PHP_SESSION_NONE) {
      session_start();
    }

    // Set the success message in the session
    $_SESSION['notification'] = "<div class='site-notification text-green-500 success'>Playlist information updated successfully!</div>";
    
    // Redirect to the playlist page using the unique ID
    $redirectUrl = '/playlist.php?id=' . urlencode($uniqueId);

    // Perform the redirection
    header("Location: $redirectUrl", true, 302);
    exit();
}

$pageTitle = "Edit Playlist Details";
$pageDescription = "Edit Playlist Details on LightUp.TV";
$pageKeywords = "";
$canonicalURL = "https://lightup.tv/edit-playlist?id=$uniqueId";
include 'header.php';
include 'menu.php';
include 'sub-heading.php';

// Check for playlist id
if (!$uniqueId) {
  echo "<div class='site-notification text-red-500 error'>Error: Playlist ID not provided.</div>";
  exit;
}

// Check if the JSON file exists
if (!file_exists($jsonFilePath)) {
    echo "<div class='site-notification text-red-500 error'>Error: Playlist not found.</div>";
    exit;
}
?>

<script>
    const script2 = document.createElement('script');
    script2.src = `/js/re-generate.js?v=${Math.floor(Math.random() * 10000)}`;
    document.head.appendChild(script2);
</script>

<div class="container mx-auto p-8">
  <h1 class="text-3xl font-bold text-text-light mb-6">Edit Playlist Details</h1>

  <form action="" method="POST" enctype="multipart/form-data" class="bg-gray-800 shadow-lg rounded-lg p-6 space-y-6">
  
    <!-- Channel Dropdown -->
    <div>
        <label for="channel" class="block text-sm font-medium text-text-light">Channel</label>
        <select name="channel" id="channel" required class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md p-2">
            <option value="">Select a Channel</option>
            <?php foreach ($channels as $channelId => $channel): ?>
                <option value="<?= htmlspecialchars($channelId) ?>" <?= $playlistData['channel'] == $channelId ? 'selected' : '' ?>>
                    <?= htmlspecialchars($channel['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Playlist Remote Link -->
    <div>
        <label for="playlist_link" class="block text-sm font-medium text-text-light">Playlist Remote Link</label>
        <input type="url" name="playlist_link" id="playlist_link" 
            class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2" 
            value="<?= htmlspecialchars($playlistData['playlist_link']) ?>">
        <button type="button" onclick="fetchYouTubePlaylistDetails(1)" 
        class="px-4 py-2 mt-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
            Fetch Details
        </button>
    </div>

    <!-- Thumbnail Preview with Download -->
    <div>
        <a id="thumbnail-download-link" href="#" download="youtube_thumbnail.jpg" class="hidden">
            <img id="youtube-thumbnail" src="" alt="Thumbnail Preview" class="h-32 mt-2 rounded cursor-pointer">
        </a>
    </div>

    <!-- Title -->
    <div>
      <label for="title" class="block text-sm font-medium text-text-light">Title</label>
      <input type="text" name="title" id="title" value="<?= htmlspecialchars($playlistData['title']) ?>" required
        class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md p-2">
    </div>

    <!-- Thumbnail -->
    <div>
      <?php
        // Display Thumbnail with Delete Option
        $thumbnailHtml = "";
        if (!empty($playlistData['thumbnail']) && file_exists($playlistDir . $playlistData['thumbnail'])) {
            // Local thumbnail exists
            $randomNumber = rand(0, 99999);
            $thumbnailPath = "playlist-data/$uniqueId/" . $playlistData['thumbnail'] . "?v=".$randomNumber;
            $fullThumbnailPath = "https://lightup.tv/playlist-data/$uniqueId/" . $playlistData['thumbnail'];
            $thumbnailHtml = "
                <div class='thumbnail-section'>
                    <label for='thumbnail' class='block text-sm font-medium text-text-light'>Thumbnail Image (Current use: Local)</label>
                    <img src='$thumbnailPath' alt='Thumbnail' class='w-48 mt-2 mb-2 rounded'>
                    <button type='button' onclick='deleteThumbnail(\"".$uniqueId."\",\"playlist\")' class='px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700'>
                        Delete Thumbnail
                    </button>
                    <span id='generateAltBtn' class='generate-image-alt inline-block cursor-pointer px-4 py-2 mt-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700' img-src-data='$fullThumbnailPath'>Generate Image Alt</span>
                    <div id='altText' class='mt-2'>". htmlspecialchars($playlistData['og_image_alt']) ."</div>
                    <script src='js/generate-image-alt.js?v=".$randomNumber."'></script>
                </div>";
        } elseif (!empty($playlistData['playlist_thumbnail_url'])) {
            // Fallback to remote thumbnail
            $thumbnailPath = htmlspecialchars($playlistData['playlist_thumbnail_url']);
            $thumbnailHtml = "
                <div class='thumbnail-section'>
                    <label for='thumbnail' class='block text-sm font-medium text-text-light'>Thumbnail Image (Current use: Remote)</label>
                    <img src='$thumbnailPath' alt='Thumbnail' class='w-48 mt-2 mb-2 rounded'>
                </div>";
        }

        // Handle Delete Thumbnail Request
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_thumbnail'])) {
            if (!empty($playlistData['thumbnail']) && file_exists($playlistDir . $playlistData['thumbnail'])) {
                unlink($playlistDir . $playlistData['thumbnail']); // Delete the local thumbnail
                $playlistData['thumbnail'] = ""; // Remove from JSON
                file_put_contents($jsonFilePath, json_encode($playlistData, JSON_PRETTY_PRINT)); // Save updated JSON
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
          <input class="inline-block border-gray-600 bg-gray-700 text-text-light rounded-md p-2" id="youtube-thumbnail-url" name="playlist_thumbnail_url" value="<?= htmlspecialchars($playlistData['playlist_thumbnail_url']) ?>">
          <div class="flex items-center space-x-4">
            <input type="checkbox" id="download-thumbnail-checkbox" name="download_thumbnail" value="1" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
            <label for="download-thumbnail-checkbox" class="ms-2 text-sm font-medium text-white-900 dark:text-white-300">
                Download Remote Thumbnail to Local
            </label>
        </div>
      </div>
      <div id="thumbnail-preview" class="mt-4">
          <img id="youtube-thumbnail" src="" alt="Thumbnail Preview" class="h-32 object-cover rounded hidden">
      </div>
    </div>

    <!-- Description -->
    <div>
      <label for="description" class="block text-sm font-medium text-text-light">Description</label>
      <textarea name="description" id="description" rows="5" 
        class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md p-2"><?= htmlspecialchars($playlistData['description']) ?></textarea>
    </div>

    <!-- Content for Playlist Description -->
    <div>
        <label for="description_content" class="inline-block text-sm font-medium text-text-light">Playlist Description Content</label>
        <span><input type="number" id="word_number_required" class="w-20 ml-2 inline-block word-number-required border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm px-2 p-1" placeholder="1200" /> words</span>
        <span class="re-generate inline-block ml-1" onclick="regenerate('html', '#description_content', this)">Re-generate</span>

        <textarea name="description_content" id="description_content" rows="10" 
            class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2"><?= htmlspecialchars($playlistData['description_content']) ?></textarea>
    </div>

    <!-- Tags -->
    <div>
      <label for="tags" class="block text-sm font-medium text-text-light">Tags</label>
      <textarea name="tags" id="tags" rows="3" required
        class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md p-2"><?= htmlspecialchars($playlistData['tags']) ?></textarea>
    </div>

    <!-- Category Dropdown -->
    <div>
        <label for="category" class="block text-sm font-medium text-text-light">Category</label>
        <select name="category" id="category" required class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md p-2">
            <option value="">Select a Category</option>
            <?php foreach ($categories as $categoryId => $category): ?>
                <option value="<?= htmlspecialchars($categoryId) ?>" <?= $playlistData['category'] == $categoryId ? 'selected' : '' ?>>
                    <?= htmlspecialchars($category['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Subcategory Dropdown -->
    <div id="subcategory-container" class="mt-4 <?= !empty($playlistData['subcategory']) ? '' : 'hidden' ?>">
        <label for="subcategory" class="block text-sm font-medium text-text-light">Subcategory</label>
        <select name="subcategory" id="subcategory"
            class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md p-2">
            <option value="">Select a Subcategory</option>
            <?php
            if (!empty($playlistData['category'])):
                $selectedCategoryId = $playlistData['category'];
                $subcategories = $categories[$selectedCategoryId]['subcategories'] ?? [];
                foreach ($subcategories as $subcategory):
                    $subcategoryName = $subcategory['name'] ?? ''; // Use the name as the identifier
            ?>
                <option value="<?= htmlspecialchars($subcategoryName) ?>"
                    <?= $playlistData['subcategory'] === $subcategoryName ? 'selected' : '' ?>>
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
        <div class="inline-flex items-center justify-center ml-2">
            <input id="inlcude_content" type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
            <label for="inlcude_content" class="ms-2 text-sm font-medium">Include content</label>
        </div>
        <p id="metadata-loading" class="text-gray-400 hidden">Generating metadata...</p>
    </div>

    <!-- Meta Title -->
    <div>
        <label for="meta_title" class="inline-block text-sm font-medium text-text-light">Meta Title</label>
        <span class="re-generate inline-block ml-1" onclick="regenerate('text', '#meta_title', this)">Re-generate</span>

        <input type="text" name="meta_title" id="meta_title" 
            class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2" value="<?= htmlspecialchars($playlistData['meta_title']) ?>">
    </div>

    <!-- Meta Description -->
    <div>
        <label for="meta_description" class="inline-block text-sm font-medium text-text-light">Meta Description</label>
        <span class="re-generate inline-block ml-1" onclick="regenerate('text', '#meta_description', this)">Re-generate</span>

        <textarea name="meta_description" id="meta_description" rows="3" 
            class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2"><?= htmlspecialchars($playlistData['meta_description']) ?></textarea>
    </div>

    <!-- Meta Keywords -->
    <div>
        <label for="meta_keywords" class="inline-block text-sm font-medium text-text-light">Meta Keywords</label>
        <span class="re-generate inline-block ml-1" onclick="regenerate('text', '#meta_keywords', this)">Re-generate</span>

        <textarea name="meta_keywords" id="meta_keywords" rows="3" 
            class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2"><?= htmlspecialchars($playlistData['meta_keywords']) ?></textarea>
    </div>

    <!-- OG Image Alt -->
    <div>
        <label for="og_image_alt" class="block text-sm font-medium text-text-light">OG Image Alt</label>
        <input type="text" name="og_image_alt" id="og_image_alt" 
            class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2" value="<?= htmlspecialchars($playlistData['og_image_alt']) ?>">
    </div>

    <!-- Notes -->
    <div>
      <label for="notes" class="block text-sm font-medium text-text-light">Notes</label>
      <textarea name="notes" id="notes" rows="3"
        class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md p-2"><?= htmlspecialchars($playlistData['notes']) ?></textarea>
    </div>

    <!-- Playlist Visibility -->
    <div>
        <label for="visibility" class="block text-sm font-medium text-text-light">Visibility</label>
        <select name="visibility" id="visibility" required
            class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md p-2">
            <option value="public" <?= $playlistData['visibility'] === 'public' ? 'selected' : '' ?>>Public</option>
            <option value="unlisted" <?= $playlistData['visibility'] === 'unlisted' ? 'selected' : '' ?>>Unlisted</option>
            <option value="private" <?= $playlistData['visibility'] === 'private' ? 'selected' : '' ?>>Private</option>
        </select>
    </div>

    <!-- Additional Files -->
    <div>
            <label class="block text-sm font-medium text-text-light">Additional Files</label>
            <?php if (!empty($additionalFiles)): ?>
                <ul id="file-list" class="text-sm text-gray-400 space-y-2 mb-4">
                    <?php foreach ($additionalFiles as $file): ?>
                        <li id="file-<?= urlencode($file) ?>" class="flex items-center justify-between">
                            <a href="playlist-data/<?= $uniqueId ?>/<?= $file ?>" target="_blank"
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
      <input name="type" id="type" value="playlist" type="hidden">  
      <button type="submit" class="w-full py-2 px-4 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
        Save Changes
      </button>
    </div>
  </form>
</div>
<div id="content-loading" class="status w-full p-4 flex justify-center items-center hidden"></div>

<?php include 'footer.php'; ?>