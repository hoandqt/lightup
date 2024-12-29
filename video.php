<?php
session_start();

// Get alias or ID from the request
$alias = $_GET['alias'] ?? null;
$id = $_GET['id'] ?? null;

$videoFile = null;

// Handle alias-based routing
if ($alias) {
    $videoDir = __DIR__ . '/item-data/';

    foreach (glob($videoDir . '*/') as $dir) {
        $jsonFile = $dir . basename($dir) . '.json';
        if (file_exists($jsonFile)) {
            $videoData = json_decode(file_get_contents($jsonFile), true);
            if (!empty($videoData['alias'])) {
              if ($videoData['alias'] === $alias) {
                  $videoFile = $jsonFile;
                  break;
              }
            }
        }
    }
}

// Handle ID-based routing if alias wasn't found
if (!$videoFile && $id) {
    $videoFile = __DIR__ . "/item-data/$id/$id.json";
    if (!file_exists($videoFile)) {
        $videoFile = null;
    }
}

// Error if neither alias nor ID resolves to a valid file
if (!$videoFile) {
    echo "<div class='site-notification text-red-500 error'>Error: Video not found.</div>";
    exit;
}

// Load video data
$videoData = json_decode(file_get_contents($videoFile), true);

// Load category data
$categoryFile = __DIR__ . '/json/video-category.json';
$categories = file_exists($categoryFile) ? json_decode(file_get_contents($categoryFile), true) : [];

// Get category details
$categoryName = $videoData['category'] ?? 'Unknown';
$categoryPath = '#';

if (isset($categories[$videoData['category']])) {
    $categoryDetails = $categories[$videoData['category']];
    $categoryName = $categoryDetails['name'] ?? 'Unknown';
    $categoryPath = '/video-category/' . ($categoryDetails['path_alias'] ?? '');
}

// Determine thumbnail
$baseURL = "https://" . $_SERVER['HTTP_HOST'];
$thumbnail = !empty($videoData['thumbnail']) 
    ? $baseURL . "/item-data/{$videoData['unique_id']}/{$videoData['thumbnail']}" 
    : (!empty($videoData['video_thumbnail_url']) 
        ? $videoData['video_thumbnail_url'] 
        : $baseURL . "/images/default-image.jpeg");

// Page metadata
$pageTitle = htmlspecialchars($videoData['title']);
$pageDescription = htmlspecialchars($videoData['description']);
$pageKeywords = htmlspecialchars($videoData['tags']);
$canonicalURL = $alias 
    ? "https://www.lightup.tv/video/{$alias}" 
    : "https://www.lightup.tv/video?id={$videoData['unique_id']}";

include 'header.php';
include 'menu.php';
include 'sub-heading.php';
?>

<div class="container mx-auto p-8">
    <!-- Breadcrumb -->
    <nav class="text-gray-400 text-sm mb-4">
        <a href="<?= $categoryPath ?>" class="text-sunset-yellow hover:underline"><?= htmlspecialchars($categoryName) ?></a> / 
        <span><?= htmlspecialchars($videoData['title']) ?></span>
    </nav>

    <!-- Title with Dropdown -->
    <div class="flex">
        <h1 class="text-3xl font-bold text-text-light"><?= htmlspecialchars($videoData['title']) ?></h1>
        <div class="relative top-0">
            <button id="dropdownButton" class="px-4 py-2 bg-gray-700 text-white rounded hover:bg-gray-600 focus:outline-none">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 inline-block h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 5.25 7.5 7.5 7.5-7.5m-15 6 7.5 7.5 7.5-7.5" />
              </svg>
            </button>
            <div id="dropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-gray-800 rounded shadow-lg">
                <a href="/edit-video?id=<?= htmlspecialchars($videoData['unique_id']) ?>" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white">
                    Edit
                </a>
                <button onclick="showDeleteModal('<?= htmlspecialchars($videoData['unique_id']) ?>')" class="block w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white">
                    Delete
                </button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-6">
        <!-- Thumbnail -->
        <div>
            <img src="<?= $thumbnail ?>" alt="<?= htmlspecialchars($videoData['title']) ?>" class="w-full h-auto object-cover rounded-lg shadow-md">
        </div>

        <!-- Video Details -->
        <div class="bg-gray-800 p-6 rounded-lg shadow-lg">
            <h2 class="text-lg font-bold text-sunset-yellow">Description</h2>
            <p class="text-gray-400 mt-4"><?= nl2br(htmlspecialchars($videoData['description'])) ?></p>

            <h2 class="text-lg font-bold text-sunset-yellow mt-6">Tags</h2>
            <p class="text-gray-400 mt-4"><?= htmlspecialchars($videoData['tags']) ?></p>

            <h2 class="text-lg font-bold text-sunset-yellow mt-6">Posted Date</h2>
            <p class="text-gray-400 mt-4"><?= htmlspecialchars($videoData['posted_date']) ?></p>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 hidden z-50 bg-black bg-opacity-50 flex justify-center items-center">
    <div class="bg-gray-800 text-text-light rounded-lg p-6 max-w-md w-full">
        <h2 class="text-lg font-bold mb-4">Confirm Delete</h2>
        <p>Are you sure you want to delete this video?</p>
        <div class="flex justify-end mt-4">
            <button id="cancelDelete" class="mr-2 px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">Cancel</button>
            <button id="confirmDelete" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Delete</button>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
