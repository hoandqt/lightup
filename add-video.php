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
$canonicalURL = "https://www.lightup.tv/add-video.php";
include 'header.php';
include 'menu.php';

// Helper function to generate a unique 9-character string
function generateUniqueId($length = 9) {
    return substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'), 0, $length);
}

// Helper function to create a URL-friendly alias
function generateAlias($title) {
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
}

// Load channels and categories
$channelFile = __DIR__ . '/json/channel.json';
$channels = file_exists($channelFile) ? json_decode(file_get_contents($channelFile), true) : [];

$categoryFile = __DIR__ . '/json/video-category.json';
$categories = file_exists($categoryFile) ? json_decode(file_get_contents($categoryFile), true) : [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $channel = $POST_['channel'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $tags = formatCommaSeparatedInput($_POST['tags']);
    $metaTitle = $_POST['meta_title'];
    $metaDescription = $_POST['meta_description'];
    $metaKeywords = formatCommaSeparatedInput($_POST['meta_keywords']);
    $videoLink = $_POST['video_link'];
    $category = $_POST['category'];
    $notes = $_POST['notes'];
    $id = $_POST['id'];
    $uniqueId = generateUniqueId();
    $alias = generateAlias($title);
    $username = $_SESSION['username']; // Get username from session
    $currentDate = date('Y-m-d H:i:s');

    // Create directories
    $baseDir = __DIR__ . '/item-data/';
    $videoDir = $baseDir . $uniqueId . '/';
    if (!is_dir($baseDir)) mkdir($baseDir, 0755, true);
    if (!is_dir($videoDir)) mkdir($videoDir, 0755, true);

    // Handle thumbnail upload
    $thumbnailPath = null;
    if (!empty($_FILES['thumbnail']['name'])) {
        $allowedThumbnailTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (in_array($_FILES['thumbnail']['type'], $allowedThumbnailTypes)) {
            $thumbnailPath = $videoDir . 'thumbnail_' . basename($_FILES['thumbnail']['name']);
            move_uploaded_file($_FILES['thumbnail']['tmp_name'], $thumbnailPath);
        }
    }

    // Handle additional files upload
    $additionalFiles = [];
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
                $additionalFiles[] = $path;
            }
        }
    }

    // Save video data to a JSON file
    $videoData = [
        'id' => $id,
        'unique_id' => $uniqueId,
        'channel' => $channel,
        'title' => $title,
        'alias' => $alias,
        'description' => $description,
        'tags' => $tags,
        'meta_title' => $metaTitle,
        'meta_description' => $metaDescription,
        'meta_keywords' => $metaKeywords,
        'thumbnail' => $thumbnailPath ? basename($thumbnailPath) : null,
        'video_link' => $videoLink,
        'category' => $category,
        'additional_files' => array_map('basename', $additionalFiles),
        'notes' => $notes,
        'posted_date' => $currentDate,
        'updated_date' => $currentDate,
        'username' => $username,
    ];
    file_put_contents($videoDir . $uniqueId . '.json', json_encode($videoData, JSON_PRETTY_PRINT));

    // Update or create content.json
    $contentFile = __DIR__ . '/json/content.json';
    $content = file_exists($contentFile) ? json_decode(file_get_contents($contentFile), true) : [];
    $content[$uniqueId] = [
        'unique_id' => $uniqueId,
        'posted_date' => $currentDate,
        'updated_date' => $currentDate,
    ];
    file_put_contents($contentFile, json_encode($content, JSON_PRETTY_PRINT));

    echo "<p class='text-green-500 px-8 pt-8'>Video information saved successfully!</p>";
}
?>

<div class="container mx-auto p-8">
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

        <!-- Title -->
        <div>
            <label for="title" class="block text-sm font-medium text-text-light">Title</label>
            <input type="text" name="title" id="title" required 
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2">
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

        <!-- Meta Title -->
        <div>
            <label for="meta_title" class="block text-sm font-medium text-text-light">Meta Title</label>
            <input type="text" name="meta_title" id="meta_title" required 
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2">
        </div>

        <!-- Meta Description -->
        <div>
            <label for="meta_description" class="block text-sm font-medium text-text-light">Meta Description</label>
            <textarea name="meta_description" id="meta_description" rows="3" required 
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2"></textarea>
        </div>

        <!-- Meta Keywords -->
        <div>
            <label for="meta_keywords" class="block text-sm font-medium text-text-light">Meta Keywords</label>
            <textarea name="meta_keywords" id="meta_keywords" rows="3" required 
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2"></textarea>
        </div>

        <!-- Thumbnail Image -->
        <div>
            <label for="thumbnail" class="block text-sm font-medium text-text-light">Thumbnail Image</label>
            <input type="file" name="thumbnail" id="thumbnail" accept=".jpeg, .jpg, .png, .webp" required 
                class="mt-1 block w-full text-text-light file:mr-4 file:py-2 file:px-4 file:border file:border-gray-600 file:rounded-md file:text-sm file:bg-gray-700 file:text-text-light hover:file:bg-gray-600">
        </div>

        <!-- Video Link -->
        <div>
            <label for="video_link" class="block text-sm font-medium text-text-light">Video Link</label>
            <input type="url" name="video_link" id="video_link" required 
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2">
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

        <!-- Category Dropdown -->
        <div>
            <label for="category" class="block text-sm font-medium text-text-light">Category</label>
            <select name="category" id="category" required 
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md p-2">
                <option value="">Select a Category</option>
                <?php foreach ($categories as $categoryId => $category): ?>
                    <option value="<?= htmlspecialchars($categoryId) ?>">
                        <?= htmlspecialchars($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Additional Files -->
        <div>
            <label for="additional_files" class="block text-sm font-medium text-text-light">Additional Files</label>
            <input type="file" name="additional_files[]" id="additional_files" accept=".jpeg, .jpg, .png, .webp, .mp3, .wav, .mp4, .zip, .tar, .gz, .pdf, .docx, .txt" multiple 
                class="mt-1 block w-full text-text-light file:mr-4 file:py-2 file:px-4 file:border file:border-gray-600 file:rounded-md file:text-sm file:bg-gray-700 file:text-text-light hover:file:bg-gray-600">
        </div>

        <!-- Notes -->
        <div>
            <label for="notes" class="block text-sm font-medium text-text-light">Notes</label>
            <textarea name="notes" id="notes" rows="5" 
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2"></textarea>
        </div>

        <!-- Custom ID -->
        <div>
            <label for="id" class="block text-sm font-medium text-text-light">Custom ID (Integer)</label>
            <input type="number" name="id" id="id" required 
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2">
        </div>

        <!-- Submit Button -->
        <div>
            <button type="submit" 
                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Save Video
            </button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
