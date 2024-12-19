<?php
session_start();

$pageTitle = "Videos";
$pageDescription = "Explore videos on LightUp.TV";
$pageKeywords = "videos, LightUp.TV, ambience, relaxation";
$canonicalURL = "https://www.lightup.tv/videos.php";
include 'header.php';
include 'menu.php';

// Path to content.json
$contentFile = __DIR__ . '/json/content.json';

// Check if content.json exists
if (!file_exists($contentFile)) {
    echo "<p class='p-8'>No videos found.</p>";
} else {
    // Read content.json
    $content = json_decode(file_get_contents($contentFile), true);
?>

<div class="container mx-auto p-8">
    <h1 class="text-3xl font-bold text-text-light mb-6">Videos</h1>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($content as $video): ?>
            <?php
            // Get video details from its JSON file
            $videoFile = __DIR__ . '/item-data/' . $video['unique_id'] . '/' . $video['unique_id'] . '.json';
            if (!file_exists($videoFile)) {
                continue;
            }
            $videoDetails = json_decode(file_get_contents($videoFile), true);
            $description = strlen($videoDetails['description']) > 150 ? substr($videoDetails['description'], 0, 150) . "..." : $videoDetails['description'];
            ?>
            <div class="bg-gray-800 p-4 rounded-lg shadow-lg">
                <img src="item-data/<?= $video['unique_id'] ?>/<?= $videoDetails['thumbnail'] ?>" alt="<?= htmlspecialchars($videoDetails['title']) ?>" class="w-full h-40 object-cover rounded">
                <h2 class="text-lg font-bold text-sunset-yellow mt-4"><?= htmlspecialchars($videoDetails['title']) ?></h2>
                <p class="text-sm text-gray-400 mt-2"><?= htmlspecialchars($description) ?></p>
                
                <div class="flex justify-between mt-4">
                  <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="edit-video.php?id=<?= $video['unique_id'] ?>" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Edit</a>
                    <button onclick="showDeleteModal('<?= $video['unique_id'] ?>')" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Delete</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
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

<script>
    let deleteUniqueId = '';

    function showDeleteModal(uniqueId) {
        deleteUniqueId = uniqueId;
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    document.getElementById('cancelDelete').addEventListener('click', () => {
        document.getElementById('deleteModal').classList.add('hidden');
    });

    document.getElementById('confirmDelete').addEventListener('click', () => {
        fetch('delete-video.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ unique_id: deleteUniqueId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Video deleted successfully!');
                location.reload();
            } else {
                alert('Error deleting video: ' + data.message);
            }
        })
        .catch(error => {
            alert('An error occurred.');
            console.error(error);
        });

        document.getElementById('deleteModal').classList.add('hidden');
    });
</script>

<?php } ?>

<?php include 'footer.php'; ?>
