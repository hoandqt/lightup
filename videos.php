<?php
session_start();

require_once "functions.php";

$pageTitle = "Explore, Engage, and Experience a World of Stories | LightUp.TV Videos";
$pageDescription = "Discover a wide variety of videos, from entertainment and education to inspiring stories and creative content.";
$pageKeywords = "videos, LightUp.TV, ambience, relaxation";
$ogImageURL = "https://lightup.tv/images/Videos-LightUpTV.jpg";
$canonicalURL = "https://lightup.tv/videos";
include 'header.php';
include 'menu.php';
include 'sub-heading.php';

// Path to content.json
$contentFile = __DIR__ . '/json/content.json';

// Define items per page
$itemsPerPage = 15;

// Get current page from the URL, default to 1
$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int) $_GET['page']) : 1;

// Check if content.json exists
if (!file_exists($contentFile)) {
    echo "<p class='p-8'>No videos found.</p>";
} else {
    // Read content.json
    $content = json_decode(file_get_contents($contentFile), true);

    // Extract values and sort by updated_date in descending order
    $videos = array_values($content);
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
        <div class="flex items-center mb-6 mx-4">
            <h1 class="text-3xl font-bold text-sunset-yellow">Videos</h1>
            <div id="filtering" class="flex items-center gap-3 ml-auto">
                <div
                    class="view grid-view active flex items-center justify-center w-8 h-8 bg-gray-700 rounded cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-6 inline-block h-5 text-white">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 0 1-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0 1 12 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25m-17.25 0h7.5c.621 0 1.125.504 1.125 1.125M3.375 8.25c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m17.25-3.75h-7.5c-.621 0-1.125.504-1.125 1.125m8.625-1.125c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125M12 10.875v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 10.875c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125M13.125 12h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125M20.625 12c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5M12 14.625v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 14.625c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125m0 1.5v-1.5m0 0c0-.621.504-1.125 1.125-1.125m0 0h7.5" />
                    </svg>
                </div>

                <div class="view list-view flex items-center justify-center w-8 h-8 bg-gray-700 rounded cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-6 inline-block h-5 text-white">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                </div>

                <div class="relative top-0 right-0 ml-auto">
                    <button id="dropdownButton"
                        class="px-4 py-1 bg-gray-700 text-white rounded hover:bg-gray-600 focus:outline-none">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="size-6 inline-block h-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                        </svg>
                    </button>
                    <div id="dropdownMenu" class="absolute right-0 mt-2 w-52 bg-gray-800 rounded shadow-lg hidden">
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <a href="/content-management"
                                class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white">
                                Manage Content
                            </a>
                        <?php endif; ?>
                        <button onclick="sortTitle('asc')"
                            class="block w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white">
                            Title (A-Z)
                        </button>
                        <button onclick="sortTitle('desc')"
                            class="block w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white">
                            Title (Z-A)
                        </button>
                        <button onclick="sortCreatedDate('desc')"
                            class="block w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white">
                            Date (Newest First)
                        </button>
                        <button onclick="sortCreatedDate('asc')"
                            class="block w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white">
                            Date (Oldest First)
                        </button>
                        <button onclick="sortViews('desc')"
                            class="block w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white">
                            Views (Most Viewed First)
                        </button>
                        <button onclick="sortViews('asc')"
                            class="block w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white">
                            Views (Least Viewed First)
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 gap-1 sm:gap-6" id="videoGrid">
            <?php foreach ($currentVideos as $video): ?>
                <?php
                // Get video details from its JSON file
                $videoFile = __DIR__ . '/item-data/' . $video['unique_id'] . '/' . $video['unique_id'] . '.json';
                if (!file_exists($videoFile)) {
                    continue;
                }
                $videoDetails = json_decode(file_get_contents($videoFile), true);
                if (!empty($videoDetails['meta_description'])) {
                    $description = $videoDetails['meta_description'];
                }
                else {
                    $description = trimDescription($videoDetails['description'] ?? '', 150);
                }

                // Determine the video image
                $videoImage = !empty($videoDetails['thumbnail'])
                    ? "item-data/" . $video['unique_id'] . "/" . $videoDetails['thumbnail']
                    : (!empty($videoDetails['video_thumbnail_url']) ? $videoDetails['video_thumbnail_url'] : "images/default-image.jpeg");

                // Determine the video link
                $videoLink = !empty($videoDetails['alias'])
                    ? "/video/" . htmlspecialchars($videoDetails['alias'])
                    : "/video?id=" . htmlspecialchars($video['unique_id']);
                ?>
                <div class="bg-gray-800 p-4 rounded-lg shadow-lg video-item"
                    data-title="<?= htmlspecialchars($videoDetails['title'] ?? '') ?>"
                    data-created="<?= $video['posted_date'] ?>" data-views="<?= $videoDetails['view'] ?? 0 ?>">
                    <a class="thumbnail" href="<?= $videoLink ?>">
                        <img src="<?= $videoImage ?>" alt="<?= htmlspecialchars($videoDetails['title'] ?? '') ?>"
                            class="w-full h-40 object-cover rounded sm:max-w-96">
                    </a>
                    <div class="item-text">
                        <h2 class="heading text-lg font-bold text-sunset-yellow mt-4">
                            <a href="<?= $videoLink ?>"><?= htmlspecialchars($videoDetails['title'] ?? '') ?></a>
                        </h2>
                        <p class="description text-sm text-gray-400 mt-2"><?= htmlspecialchars($description) ?></p>
                    </div>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <div class="action-buttons flex justify-between mt-4 gap-2">
                            <a href="edit-video?id=<?= $video['unique_id'] ?>"
                                class="btn edit-btn px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Edit</a>
                            <button onclick="showDeleteModal('<?= $video['unique_id'] ?>')"
                                class="btn delete-btn px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Delete</button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <div class="flex justify-center mt-8">
            <nav class="flex items-center space-x-2">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=<?= $currentPage - 1 ?>"
                        class="px-4 py-2 bg-gray-700 rounded hover:bg-gray-600">Prev</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>"
                        class="px-4 py-2 <?= $i == $currentPage ? 'bg-sunset-yellow text-black' : 'bg-gray-700' ?> rounded hover:bg-gray-600">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=<?= $currentPage + 1 ?>"
                        class="px-4 py-2 bg-gray-700 rounded hover:bg-gray-600">Next</a>
                <?php endif; ?>
            </nav>
        </div>
    </div>

    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <!-- Delete Confirmation Modal -->
        <div id="deleteModal" class="fixed inset-0 hidden z-50 bg-black bg-opacity-50 flex justify-center items-center">
            <div class="bg-gray-800 text-text-light rounded-lg p-6 max-w-md w-full">
                <h2 class="text-lg font-bold mb-4">Confirm Delete</h2>
                <p>Are you sure you want to delete this video?</p>
                <div class="flex justify-end mt-4">
                    <button id="cancelDelete"
                        class="mr-2 px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">Cancel</button>
                    <button id="confirmDelete" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Delete</button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script>
        // View toggling logic
        const gridViewButton = document.querySelector('.grid-view');
        const listViewButton = document.querySelector('.list-view');
        const videoGrid = document.getElementById('videoGrid');

        gridViewButton.addEventListener('click', () => {
            gridViewButton.classList.add('active');
            listViewButton.classList.remove('active');
            videoGrid.className = 'grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 gap-1 sm:gap-6';
            const items = document.querySelectorAll('.video-item');
            items.forEach(item => {
                item.classList.remove('flex', 'gap-4', 'justify-center', 'items-center');
                item.querySelector('.thumbnail img').classList.remove('min-w-80');
                item.querySelector('.thumbnail img').classList.add('h-40');
                item.querySelector('.heading').classList.add('mt-4');
                item.querySelector('.description').classList.add('mt-2');
            });
        });

        listViewButton.addEventListener('click', () => {
            listViewButton.classList.add('active');
            gridViewButton.classList.remove('active');
            videoGrid.className = 'grid grid-cols-1 gap-1 sm:gap-6 list-view-content';
            const items = document.querySelectorAll('.video-item');
            items.forEach(item => {
                item.classList.add('flex', 'gap-4', 'justify-center', 'items-center');
                item.querySelector('.thumbnail img').classList.add('min-w-80');
                item.querySelector('.thumbnail img').classList.remove('h-40');
                item.querySelector('.heading').classList.remove('mt-4');
                item.querySelector('.description').classList.remove('mt-2');
            });
        });

        // Sorting functions
        function sortTitle(order) {
            const items = Array.from(videoGrid.getElementsByClassName('video-item'));
            items.sort((a, b) => {
                const titleA = a.dataset.title.toLowerCase();
                const titleB = b.dataset.title.toLowerCase();
                return order === 'asc' ? titleA.localeCompare(titleB) : titleB.localeCompare(titleA);
            });
            videoGrid.innerHTML = '';
            items.forEach(item => videoGrid.appendChild(item));
        }

        function sortCreatedDate(order) {
            const items = Array.from(videoGrid.getElementsByClassName('video-item'));
            items.sort((a, b) => {
                const dateA = new Date(a.dataset.created);
                const dateB = new Date(b.dataset.created);
                return order === 'asc' ? dateA - dateB : dateB - dateA;
            });
            videoGrid.innerHTML = '';
            items.forEach(item => videoGrid.appendChild(item));
        }

        function sortViews(order) {
            const items = Array.from(videoGrid.getElementsByClassName('video-item'));
            items.sort((a, b) => {
                const viewsA = parseInt(a.dataset.views, 10);
                const viewsB = parseInt(b.dataset.views, 10);
                return order === 'asc' ? viewsA - viewsB : viewsB - viewsA;
            });
            videoGrid.innerHTML = '';
            items.forEach(item => videoGrid.appendChild(item));
        }
    </script>

<?php } ?>

<?php include 'footer.php'; ?>