<?php
session_start();

require_once "functions.php";

// Path to category JSON file
$categoryFile = __DIR__ . '/json/video-category.json';

if (!file_exists($categoryFile)) {
    die("<p class='p-8'>Category details not found.</p>");
}

// Load category data
$categories = json_decode(file_get_contents($categoryFile), true);

// Get alias or category_id from the URL
$alias = isset($_GET['alias']) ? trim($_GET['alias']) : null;
$categoryId = isset($_GET['category_id']) && is_numeric($_GET['category_id']) ? (int)$_GET['category_id'] : null;

if ($alias) {
    // Find category_id by alias
    foreach ($categories as $id => $category) {
        if (isset($category['path_alias']) && $category['path_alias'] === $alias) {
            $categoryId = (int)$id;
            break;
        }
    }

    if (!$categoryId) {
        die("<p class='p-8'>Invalid or missing alias.</p>");
    }
}

if (!$categoryId) {
    die("<p class='p-8'>Invalid or missing category ID.</p>");
}

// Get current category details
if (!isset($categories[$categoryId])) {
    die("<p class='p-8'>Category not found.</p>");
}

$currentCategory = $categories[$categoryId];
$categoryName = htmlspecialchars($currentCategory['name']);
$categoryDescription = htmlspecialchars($currentCategory['category_description']);
$alias = htmlspecialchars($currentCategory['path_alias']);
if (isset($currentCategory['image'])) {
    $ogImageURL = "https://lightup.tv/" . htmlspecialchars($currentCategory['image']);
}

if (isset($currentCategory['meta_title'])) {
    $pageTitle = htmlspecialchars($currentCategory['meta_title']);
}
else {
    $pageTitle = "$categoryName - LightUp.TV";
}

$pageDescription = $categoryDescription;
$pageKeywords = $categoryName . ", LightUp.TV, ambience, relaxation";
$canonicalURL = "https://lightup.tv/video-category/" . $alias;

include 'header.php';
include 'menu.php';
include 'sub-heading.php';

// Define items per page
$itemsPerPage = 15;

// Get current page from the URL, default to 1
$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// Check if the content file exists
$contentFile = __DIR__ . '/json/content.json';
if (!file_exists($contentFile)) {
    echo "<p class='p-8'>No videos found in this category.</p>";
} else {
    // Load content data
    $content = json_decode(file_get_contents($contentFile), true);

    // Filter videos by category
    $videos = array_filter($content, function ($video) use ($categoryId) {
        return isset($video['category']) && $video['category'] == $categoryId;
    });

    // Sort videos by updated_date in descending order
    usort($videos, function ($a, $b) {
        return strtotime($b['updated_date']) - strtotime($a['updated_date']);
    });

    // Calculate pagination
    $totalVideos = count($videos);
    $totalPages = ceil($totalVideos / $itemsPerPage);
    $offset = ($currentPage - 1) * $itemsPerPage;
    $currentVideos = array_slice($videos, $offset, $itemsPerPage);
?>

<div class="<?php echo $mainContainerClass ?>">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-sunset-yellow"><?= $categoryName ?></h1>
        <p class="mt-2 text-gray-400"><?= $categoryDescription ?></p>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6" id="videoGrid">
        <?php foreach ($currentVideos as $video): ?>
            <?php
            // Get video details from its JSON file
            $videoFile = __DIR__ . '/item-data/' . $video['unique_id'] . '/' . $video['unique_id'] . '.json';
            if (!file_exists($videoFile)) {
                continue;
            }
            $videoDetails = json_decode(file_get_contents($videoFile), true);
            $description = trimDescription($videoDetails['meta_description'] ?? '', 150);

            // Determine the video image
            $videoImage = !empty($videoDetails['thumbnail']) 
                ? "/item-data/" . $video['unique_id'] . "/" . $videoDetails['thumbnail']
                : (!empty($videoDetails['video_thumbnail_url']) ? $videoDetails['video_thumbnail_url'] : "images/default-image.jpeg");

            // Determine the video link
            $videoLink = !empty($videoDetails['alias']) 
                ? "/video/" . htmlspecialchars($videoDetails['alias']) 
                : "/video?id=" . htmlspecialchars($video['unique_id']);
            ?>
            <div class="bg-gray-800 p-4 rounded-lg shadow-lg">
                <a href="<?= $videoLink ?>">
                    <img src="<?= $videoImage ?>" alt="<?= htmlspecialchars($videoDetails['title'] ?? '') ?>" class="w-full h-40 object-cover rounded">
                </a>
                <h2 class="mt-4 text-lg font-bold text-sunset-yellow leading-tight">
                    <a href="<?= $videoLink ?>"><?= htmlspecialchars($videoDetails['title'] ?? '') ?></a>
                </h2>
                <p class="mt-2 text-sm text-gray-400"><?= htmlspecialchars($description) ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <div class="flex justify-center mt-8">
        <nav class="flex items-center space-x-2">
            <?php if ($currentPage > 1): ?>
                <a href="?category_id=<?= $categoryId ?>&page=<?= $currentPage - 1 ?>" class="px-4 py-2 bg-gray-700 text-white rounded hover:bg-gray-600">Prev</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?category_id=<?= $categoryId ?>&page=<?= $i ?>" class="px-4 py-2 <?= $i == $currentPage ? 'bg-sunset-yellow text-black' : 'bg-gray-700 text-white' ?> rounded hover:bg-gray-600">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($currentPage < $totalPages): ?>
                <a href="?category_id=<?= $categoryId ?>&page=<?= $currentPage + 1 ?>" class="px-4 py-2 bg-gray-700 text-white rounded hover:bg-gray-600">Next</a>
            <?php endif; ?>
        </nav>
    </div>
</div>

<?php } ?>

<?php include 'footer.php'; ?>
