<?php
session_start();

require_once "functions.php";

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $redirectUrl = urlencode($_SERVER['REQUEST_URI']);
    header("Location: login.php?redirect=$redirectUrl");
    exit;
}

$pageTitle = "Add a New Post";
$pageDescription = "Add a New Post to LightUp.TV";
$pageKeywords = "";
$canonicalURL = "https://lightup.tv/add-post";
include 'header.php';
include 'menu.php';
include 'sub-heading.php';

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

$categoryFile = __DIR__ . '/json/post-category.json';
$categories = file_exists($categoryFile) ? json_decode(file_get_contents($categoryFile), true) : [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST)) {
        echo "<p class='text-red-500 site-notification error'>Something went wrong! (1)</p>";
        exit;
    }
    $channel = $_POST['channel'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $tags = formatCommaSeparatedInput($_POST['tags']);
    $metaTitle = $_POST['meta_title'];
    $metaDescription = $_POST['meta_description'];
    $metaKeywords = formatCommaSeparatedInput($_POST['meta_keywords']);
    $newDescription = $_POST['new_description'];
    $postLink = $_POST['post_link'];
    $category = $_POST['category'];
    $subcategory = $_POST['subcategory'];
    $notes = $_POST['notes'];
    $id = $_POST['id'];
    $uniqueId = generateUniqueId();
    $alias = generateAlias($title);
    $username = $_SESSION['username']; // Get username from session
    $currentDate = date('Y-m-d H:i:s');

    // Create directories
    $baseDir = __DIR__ . '/item-data/';
    $postDir = $baseDir . $uniqueId . '/';
    if (!is_dir($baseDir)) mkdir($baseDir, 0755, true);
    if (!is_dir($postDir)) mkdir($postDir, 0755, true);

    // Handle thumbnail upload
    $thumbnailPath = null;

    // Check if the thumbnail file is uploaded
    if (!empty($_FILES['thumbnail']['name'])) {
        $allowedThumbnailTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (in_array($_FILES['thumbnail']['type'], $allowedThumbnailTypes)) {
            $thumbnailPath = $postDir . 'thumbnail_' . basename($_FILES['thumbnail']['name']);
            move_uploaded_file($_FILES['thumbnail']['tmp_name'], $thumbnailPath);
        }
    } else {
        // Check for a URL in the "youtube-thumbnail" element's src attribute and save it to the server
        if (!empty($_POST['youtube_thumbnail_url'])) {
            $youtubeThumbnailUrl = $_POST['youtube_thumbnail_url'];
            $thumbnailContent = file_get_contents($youtubeThumbnailUrl); // Download the youtube thumbnail
            if ($thumbnailContent) {
                $thumbnailExtension = pathinfo(parse_url($youtubeThumbnailUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
                $thumbnailPath = $postDir . 'thumbnail.' . $thumbnailExtension;
                file_put_contents($thumbnailPath, $thumbnailContent); // Save thumbnail onto the server
            }
        }
    }

    // Handle additional files upload
    $additionalFiles = [];
    if (!empty($_FILES['additional_files']['name'][0])) {
        $allowedFileTypes = [
            'image/png', 'image/jpeg', 'image/jpg', 'image/webp',
            'audio/mp3', 'audio/wav', 'post/mp4',
            'application/zip', 'application/x-tar', 'application/gzip',
            'application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain'
        ];
        foreach ($_FILES['additional_files']['name'] as $key => $name) {
            if (in_array($_FILES['additional_files']['type'][$key], $allowedFileTypes)) {
                $path = $postDir . 'file_' . basename($name);
                move_uploaded_file($_FILES['additional_files']['tmp_name'][$key], $path);
                $additionalFiles[] = $path;
            }
        }
    }

    // Save post data to a JSON file
    $postData = [
        'id' => $id,
        'unique_id' => $uniqueId, 
        'channel' => $channel, // Required
        'title' => $title, // Required
        'alias' => $alias,
        'description' => $description, // Required
        'tags' => $tags, // Required
        'meta_title' => $metaTitle, // Optional
        'meta_description' => $metaDescription, // Optional
        'meta_keywords' => $metaKeywords, // Optional
        'new_description' => $newDescription, // Optional
        'thumbnail' => $thumbnailPath ? basename($thumbnailPath) : null, // Optional
        'post_link' => $postLink, // Required
        'category' => $category, // Optional
        'subcategory' => $subcategory, // Optional
        'additional_files' => array_map('basename', $additionalFiles), // Optional
        'notes' => $notes, // Optional
        'posted_date' => $currentDate,
        'updated_date' => $currentDate,
        'username' => $username,
    ];

    // Save the item data
    $itemJsonPath = $postDir . $uniqueId . '.json';
    file_put_contents($itemJsonPath, json_encode($postData, JSON_PRETTY_PRINT));

    // Update or create content.json
    $contentFile = __DIR__ . '/json/content.json';
    $content = file_exists($contentFile) ? json_decode(file_get_contents($contentFile), true) : [];
    $content[$uniqueId] = [
        'unique_id' => $uniqueId,
        'posted_date' => $currentDate,
        'updated_date' => $currentDate,
        'category' => $category
    ];
    file_put_contents($contentFile, json_encode($content, JSON_PRETTY_PRINT));

    echo "<p class='text-green-500 px-8 pt-8'>Post information saved successfully!</p>";
}
?>

<div class="<?php echo $mainContainerClass ?>">
    <h1 class="text-3xl font-bold text-text-light mb-6">Add New Post</h1>
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

        <!-- Post Link -->
        <div>
            <label for="post_link" class="block text-sm font-medium text-text-light">Post Link</label>
            <input type="url" name="post_link" id="post_link" required 
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2">
            <button type="button" onclick="fetchYouTubeDetails()" 
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

        <!-- New Description for content -->
        <div>
            <label for="new_description" class="block text-sm font-medium text-text-light">New Description</label>
            <textarea name="new_description" id="new_description" rows="5" 
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2"></textarea>
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
            <input type="number" name="id" id="id"  
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2">
        </div>

        <!-- Submit Button -->
        <div>
            <input name="type" id="type" value="post" type="hidden">
            <button type="submit" 
                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Save Post
            </button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
