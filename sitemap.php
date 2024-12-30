<?php
session_start();

require_once "functions.php";

// Check if the user is logged in and has the admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login.php');
    exit;
}

$pageTitle = "Sitemap Management";
$pageDescription = "Manage Sitemap Entries";
$pageKeywords = "";
$canonicalURL = "https://lightup.tv/sitemap";
include 'header.php';
include 'menu.php';
include 'sub-heading.php';

// Load existing sitemap entries
$sitemapFile = 'sitemap.xml';
$sitemapEntries = [];
if (file_exists($sitemapFile)) {
    $sitemapEntries = simplexml_load_file($sitemapFile);
}

$staticPaths = [
    ["id" => 0, "path" => "/", "lastmod" => date('Y-m-d'), "priority" => "1.0"],
    ["id" => 1, "path" => "/videos", "lastmod" => date('Y-m-d'), "priority" => "1"],
    ["id" => 2, "path" => "/blogs", "lastmod" => date('Y-m-d'), "priority" => "0.9"],
    ["id" => 3, "path" => "/about", "lastmod" => date('Y-m-d'), "priority" => "0.8"],
    ["id" => 4, "path" => "/contact", "lastmod" => date('Y-m-d'), "priority" => "0.6"],
];

$jsonOptions = [
    '/json/content.json' => 'Videos',
    '/json/post-content.json' => 'Blog Posts'
];

?>

<div class="<?php echo $mainContainerClass ?>">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-sunset-yellow">Manage Sitemap</h1>
        <button id="openModalButton" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">Add Page</button>
    </div>

    <div class="overflow-x-auto mt-6">
        <table id="sitemapTable" class="min-w-full bg-gray-800 text-gray-300">
            <thead>
                <tr class="bg-gray-700">
                    <th>ID</th>
                    <th>Path</th>
                    <th>Last Modified</th>
                    <th>Priority</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($staticPaths as $path): ?>
                    <tr>
                        <td><?php echo $path['id']; ?></td>
                        <td><?php echo htmlspecialchars($path['path']); ?></td>
                        <td><?php echo $path['lastmod']; ?></td>
                        <td><?php echo $path['priority']; ?></td>
                        <td>
                            <button class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded">Edit</button>
                            <button class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        <label for="jsonSource" class="block text-gray-400">Select Source:</label>
        <select id="jsonSource" class="w-full p-2 bg-gray-700 border border-gray-600 rounded">
            <?php foreach ($jsonOptions as $path => $label): ?>
                <option value="<?php echo $path; ?>"><?php echo $label; ?></option>
            <?php endforeach; ?>
        </select>
        <div class="mt-4 flex gap-4">
            <button id="scanNewItemsButton" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">Scan New Items</button>
            <button id="rescanAllButton" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Rescan Everything</button>
        </div>
    </div>
</div>

<script>
document.getElementById('scanNewItemsButton').addEventListener('click', function () {
    const source = document.getElementById('jsonSource').value;
    fetch(source)
        .then(response => response.json())
        .then(data => {
            for (const uniqueId in data) {
                const itemDataPath = source.includes('content.json') ? `item-data/${uniqueId}/${uniqueId}.json` : `post-data/${uniqueId}/${uniqueId}.json`;
                fetch(itemDataPath)
                    .then(itemResponse => itemResponse.json())
                    .then(item => {
                        const priority = source.includes('content.json') ? "0.7" : "0.6"; // Adjust priority based on type
                        console.log(`Adding new item: ${item.title} with priority ${priority}`);
                        // Add sitemap entry logic for new items only
                    })
                    .catch(err => console.error('Item not found:', err));
            }
        })
        .catch(err => console.error('JSON file not found:', err));
});

document.getElementById('rescanAllButton').addEventListener('click', function () {
    const source = document.getElementById('jsonSource').value;
    fetch(source)
        .then(response => response.json())
        .then(data => {
            for (const uniqueId in data) {
                const itemDataPath = source.includes('content.json') ? `item-data/${uniqueId}/${uniqueId}.json` : `post-data/${uniqueId}/${uniqueId}.json`;
                fetch(itemDataPath)
                    .then(itemResponse => itemResponse.json())
                    .then(item => {
                        const priority = source.includes('content.json') ? "0.7" : "0.6"; // Adjust priority based on type
                        console.log(`Rescanning and adding: ${item.title} with priority ${priority}`);
                        // Rescan and update all sitemap entries logic
                    })
                    .catch(err => console.error('Item not found:', err));
            }
        })
        .catch(err => console.error('JSON file not found:', err));
});
</script>

<?php include 'footer.php'; ?>
