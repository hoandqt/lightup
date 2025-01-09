<?php
session_start();

require_once "functions.php";

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $redirectUrl = urlencode($_SERVER['REQUEST_URI']);
    header("Location: login.php?redirect=$redirectUrl");
    exit;
}

$pageTitle = "Add a New Video";
$pageDescription = "Add a New Video to LightUp.TV";
$pageKeywords = "";
$canonicalURL = "https://lightup.tv/add-video";
include 'header.php';
include 'menu.php';
include 'sub-heading.php';

// Load channels and categories
$channelFile = __DIR__ . '/json/channel.json';
$channels = file_exists($channelFile) ? json_decode(file_get_contents($channelFile), true) : [];

$categoryFile = __DIR__ . '/json/video-category.json';
$categories = file_exists($categoryFile) ? json_decode(file_get_contents($categoryFile), true) : [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST)) {
        echo "<p class='text-red-500 site-notification error'>Something went wrong! (1)</p>";
        exit;
    }

    // Check if the video_link is existing
    if (!empty($_POST['video_link'])) {
        $result = isUrlExisting($_POST['video_link'], 'video');
        if ($result['status'] === 'error') {
            echo "<p class='text-red-500 site-notification error'>" . $result['message'] . "</p>";
            exit;
        }
    }

    $channel = $_POST['channel'];
    $playlistId = $_POST['playlist'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $tags = formatCommaSeparatedInput($_POST['tags']);
    $metaTitle = $_POST['meta_title'];
    $metaDescription = $_POST['meta_description'];
    $metaKeywords = formatCommaSeparatedInput($_POST['meta_keywords']);
    $newDescription = $_POST['new_description'];
    $videoLink = $_POST['video_link'];
    $category = $_POST['category'];
    $subcategory = $_POST['subcategory'];
    $notes = $_POST['notes'];
    $visibility = $_POST['visibility'];
    $newId = getNewId('video');
    $uniqueId = generateUniqueId();
    $alias = generateAlias($title);
    $username = $_SESSION['username']; // Get username from session
    $currentDate = date('Y-m-d H:i:s');

    // Create directories
    $baseDir = __DIR__ . '/item-data/';
    $videoDir = $baseDir . $uniqueId . '/';
    if (!is_dir($baseDir))
        mkdir($baseDir, 0755, true);
    if (!is_dir($videoDir))
        mkdir($videoDir, 0755, true);

    // Handle thumbnail upload
    $thumbnailPath = null;

    // Check if the thumbnail file is uploaded
    if (!empty($_FILES['thumbnail']['name'])) {
        $allowedThumbnailTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (in_array($_FILES['thumbnail']['type'], $allowedThumbnailTypes)) {
            $thumbnailPath = $videoDir . 'thumbnail_' . basename($_FILES['thumbnail']['name']);
            move_uploaded_file($_FILES['thumbnail']['tmp_name'], $thumbnailPath);
        }
    } else {
        // Check for a URL in the "youtube-thumbnail" element's src attribute and save it to the server
        if (!empty($_POST['youtube_thumbnail_url'])) {
            $youtubeThumbnailUrl = $_POST['youtube_thumbnail_url'];
            $thumbnailContent = file_get_contents($youtubeThumbnailUrl); // Download the youtube thumbnail
            if ($thumbnailContent) {
                $thumbnailExtension = pathinfo(parse_url($youtubeThumbnailUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
                $thumbnailPath = $videoDir . 'thumbnail.' . $thumbnailExtension;
                file_put_contents($thumbnailPath, $thumbnailContent); // Save thumbnail onto the server
            }
        }
    }

    // Handle additional files upload
    $additionalFiles = [];
    if (!empty($_FILES['additional_files']['name'][0])) {
        $allowedFileTypes = [
            'image/png',
            'image/jpeg',
            'image/jpg',
            'image/webp',
            'audio/mp3',
            'audio/wav',
            'video/mp4',
            'application/zip',
            'application/x-tar',
            'application/gzip',
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain'
        ];
        foreach ($_FILES['additional_files']['name'] as $key => $name) {
            if (in_array($_FILES['additional_files']['type'][$key], $allowedFileTypes)) {
                $path = $videoDir . 'file_' . basename($name);
                move_uploaded_file($_FILES['additional_files']['tmp_name'][$key], $path);
                $additionalFiles[] = $path;
            }
        }
    }

    // Save video data to a JSON file
    $videoData = [
        'id' => $newId,
        'unique_id' => $uniqueId,
        'channel' => $channel, // Required
        'playlist' => [$playlistId], // Optional
        'title' => $title, // Required
        'alias' => $alias,
        'description' => $description, // Required
        'tags' => $tags, // Required
        'meta_title' => $metaTitle, // Optional
        'meta_description' => $metaDescription, // Optional
        'meta_keywords' => $metaKeywords, // Optional
        'new_description' => $newDescription, // Optional
        'thumbnail' => $thumbnailPath ? basename($thumbnailPath) : null, // Optional
        'video_link' => $videoLink, // Required
        'category' => $category, // Optional
        'subcategory' => $subcategory, // Optional
        'additional_files' => array_map('basename', $additionalFiles), // Optional
        'notes' => $notes, // Optional
        'visibility' => $visibility, // Add visibility here
        'posted_date' => $currentDate,
        'updated_date' => $currentDate,
        'username' => $username,
    ];

    // Save the item data
    $itemJsonPath = $videoDir . $uniqueId . '.json';
    file_put_contents($itemJsonPath, json_encode($videoData, JSON_PRETTY_PRINT));

    // Update or create content.json
    $contentFile = __DIR__ . '/json/content.json';
    $content = file_exists($contentFile) ? json_decode(file_get_contents($contentFile), true) : [];
    $content[$uniqueId] = [
        'id' => $newId,
        'unique_id' => $uniqueId,
        'posted_date' => $currentDate,
        'updated_date' => $currentDate,
        'category' => $category,
        'visibility' => $visibility,
    ];
    file_put_contents($contentFile, json_encode($content, JSON_PRETTY_PRINT));

    $message = '';

    // Add the video to the sitemap
    $sitemapResult = addXmlEntry($siteURL . "/video/" . urlencode($alias), 0.7);

    $editLink = "/edit-video?id=".$uniqueId;

    // Display feedback based on sitemap update
    if ($sitemapResult) {
        $message .= "<p class='site-notification text-green-500 success'>Video <strong><a href='{$editLink}' target='_blank'>{$title}</a></strong> saved as <strong>{$visibility}</strong> successfully and sitemap updated!</p>";
    } else {
        $message .= "<p class='site-notification text-green-500 warning'>Video <strong><a href='{$editLink}' target='_blank'>{$title}</a></strong> saved but sitemap entry already exists!</p>";
    }

    // Update playlist
    if (!empty($playlistId)) {
        $addPlaylistResult = addVideoToPlaylist($playlistId, $uniqueId);
        if (!empty($addPlaylistResult['message'])) {
            if ($addPlaylistResult['success']) {
                $message .= "<p class='site-notification text-green-500 success'>{$addPlaylistResult['message']}</p>";
            }
            else {
                $message .= "<p class='site-notification text-red-500 error'>{$addPlaylistResult['message']}</p>";
            }
        }
    }
    echo $message;
}
?>

<div class="<?php echo $mainContainerClass ?>">
    <h1 class="text-3xl font-bold text-text-light mb-6">Add New Video</h1>
    <form action="" method="POST" enctype="multipart/form-data" class="bg-gray-800 shadow-lg rounded-lg p-6 space-y-6">
        <!-- Form Fields -->
        <!-- Channel Dropdown -->
        <div>
            <label for="channel" class="block text-sm font-medium text-text-light">Channel</label>
            <select name="channel" id="channel" required
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md p-2">
                <option value="">Select a Channel</option>
                <?php foreach ($channels as $channelId => $channel): ?>
                    <option value="<?= htmlspecialchars($channelId) ?>">
                        <?= htmlspecialchars($channel['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Video Link -->
        <div>
            <label for="video_link" class="block text-sm font-medium text-text-light">Video Link</label>
            <input type="url" name="video_link" id="video_link" required
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2">
            <button type="button" onclick="fetchYouTubeDetails(0)"
                class="px-4 py-2 mt-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                Fetch Details
            </button>
        </div>

        <!-- Playlist -->
        <div class="relative">
            <label for="playlist" class="block text-sm font-medium text-text-light">Playlist</label>
            <input type="text" name="playlist" id="playlist"
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2">
            <div id="playlist-dropdown" class="absolute w-96 bg-gray-800 text-white rounded shadow-lg z-20 top-16 hidden"></div>
            <div class="selected-content pt-4 hidden"></div>
        </div>

        <!-- Thumbnail Preview with Download -->
        <div>
            <a id="thumbnail-download-link" href="#" download="youtube_thumbnail.jpg" class="hidden">
                <img id="youtube-thumbnail" src="" alt="Thumbnail Preview" class="h-32 mt-2 rounded cursor-pointer">
            </a>
            <div id='altText' class='mt-2'></div>
        </div>

        <!-- Title -->
        <div>
            <label for="title" class="block text-sm font-medium text-text-light">Title</label>
            <input type="text" name="title" id="title" required
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2">
        </div>

        <!-- Thumbnail Image -->
        <div>
            <label for="thumbnail" class="block text-sm font-medium text-text-light">Thumbnail Image</label>
            <input type="file" name="thumbnail" id="thumbnail" accept=".jpeg, .jpg, .png, .webp"
                class="mt-1 block w-full text-text-light file:mr-4 file:py-2 file:px-4 file:border file:border-gray-600 file:rounded-md file:text-sm file:bg-gray-700 file:text-text-light hover:file:bg-gray-600">
            <input type="hidden" id="youtube-thumbnail-url" name="youtube_thumbnail_url">
        </div>

        <!-- Description -->
        <div>
            <label for="description" class="block text-sm font-medium text-text-light">Description</label>
            <textarea name="description" id="description" rows="5" required
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2"></textarea>
        </div>

        <!-- Tags -->
        <div>
            <label for="tags" class="block text-sm font-medium text-text-light">Tags</label>
            <textarea name="tags" id="tags" rows="3" required
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2"></textarea>
        </div>

        <!-- Category Dropdown -->
        <div>
            <label for="category" class="block text-sm font-medium text-text-light">Category</label>
            <select name="category" id="category"
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md p-2">
                <option value="">Select a Category</option>
                <?php foreach ($categories as $categoryId => $category): ?>
                    <option value="<?= htmlspecialchars($categoryId) ?>">
                        <?= htmlspecialchars($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Subcategory Dropdown (Hidden Initially) -->
        <div id="subcategory-container" class="hidden mt-4">
            <label for="subcategory" class="block text-sm font-medium text-text-light">Subcategory</label>
            <select name="subcategory" id="subcategory"
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md p-2">
                <option value="">Select a Subcategory</option>
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
                <input id="include_content" type="checkbox" checked class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                <label for="include_content" class="ms-2 text-sm font-medium">Include content</label>
            </div>
            <p id="metadata-loading" class="text-gray-400 hidden">Generating metadata...</p>
        </div>

        <!-- Meta Title -->
        <div>
            <label for="meta_title" class="block text-sm font-medium text-text-light">Meta Title</label>
            <input type="text" name="meta_title" id="meta_title"
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2">
        </div>

        <!-- Meta Description -->
        <div>
            <label for="meta_description" class="block text-sm font-medium text-text-light">Meta Description</label>
            <textarea name="meta_description" id="meta_description" rows="3"
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2"></textarea>
        </div>

        <!-- Meta Keywords -->
        <div>
            <label for="meta_keywords" class="block text-sm font-medium text-text-light">Meta Keywords</label>
            <textarea name="meta_keywords" id="meta_keywords" rows="3"
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2"></textarea>
        </div>

        <!-- OG Image Alt -->
        <div>
            <label for="og_image_alt" class="block text-sm font-medium text-text-light">OG Image Alt</label>
            <input type="text" name="og_image_alt" id="og_image_alt"
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2">
        </div>

        <!-- New Description for content -->
        <div>
            <label for="new_description" class="block text-sm font-medium text-text-light">New Description</label>
            <textarea name="new_description" id="new_description" rows="5"
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2"></textarea>
        </div>


        <!-- Additional Files -->
        <div>
            <label for="additional_files" class="block text-sm font-medium text-text-light">Additional Files</label>
            <input type="file" name="additional_files[]" id="additional_files"
                accept=".jpeg, .jpg, .png, .webp, .mp3, .wav, .mp4, .zip, .tar, .gz, .pdf, .docx, .txt" multiple
                class="mt-1 block w-full text-text-light file:mr-4 file:py-2 file:px-4 file:border file:border-gray-600 file:rounded-md file:text-sm file:bg-gray-700 file:text-text-light hover:file:bg-gray-600">
        </div>

        <!-- Notes -->
        <div>
            <label for="notes" class="block text-sm font-medium text-text-light">Notes</label>
            <textarea name="notes" id="notes" rows="5"
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2"></textarea>
        </div>

        <!-- Video Visibility -->
        <div>
            <label for="visibility" class="block text-sm font-medium text-text-light">Visibility</label>
            <select name="visibility" id="visibility" required
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md p-2">
                <option value="public">Public</option>
                <option value="unlisted">Unlisted</option>
                <option value="private">Private</option>
            </select>
        </div>

        <!-- Custom ID -->
        <div>
            <label for="id" class="block text-sm font-medium text-text-light">Custom ID (Integer)</label>
            <input type="number" name="id" id="id"
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2">
        </div>

        <!-- Submit Button -->
        <div>
            <input name="type" id="type" value="video" type="hidden">
            <button type="submit"
                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Save Video
            </button>
        </div>
    </form>
</div>
<div id="fixed-top-content" class="w-full">
    <div id="content-loading" class="status w-full p-4 flex justify-center items-center hidden"></div>
    <div id="content-loading-1" class="status w-full p-4 flex justify-center items-center hidden"></div>
</div>

<?php include 'footer.php'; ?>