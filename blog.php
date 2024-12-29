<?php
session_start();

$pageTitle = "View Posts";
$pageDescription = "Explore posts on LightUp.TV";
$pageKeywords = "posts, LightUp.TV, blog, content";
$canonicalURL = "https://www.lightup.tv/view-post.php";
include 'header.php';
include 'menu.php';

// Đường dẫn đến file post-content.json
$contentFile = __DIR__ . '/json/post-content.json';

// Kiểm tra nếu file post-content.json tồn tại
if (!file_exists($contentFile)) {
    echo "<p class='p-8'>No posts found.</p>";
} else {
    // Đọc dữ liệu từ file post-content.json
    $content = json_decode(file_get_contents($contentFile), true);
?>

    <div class="container mx-auto p-8">
        <h1 class="text-3xl font-bold text-text-light mb-6">Blog</h1>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($content as $post): ?>
                <?php
                if ($post['status'] !== 'public') {
                    continue;
                }

                // Kiểm tra ngày giờ hẹn, chỉ hiển thị nếu đã đến thời gian
                $currentDateTime = new DateTime(); // Lấy thời gian hiện tại
                $scheduledDateTime = new DateTime($post['scheduled_date']); // Ngày giờ đã hẹn

                if ($scheduledDateTime > $currentDateTime) {
                    continue; // Bỏ qua bài viết nếu chưa đến ngày giờ hẹn
                }

                // Lấy chi tiết bài viết từ file JSON của từng bài viết
                $postFile = __DIR__ . '/post-data/' . $post['unique_id'] . '/' . $post['unique_id'] . '.json';
                if (!file_exists($postFile)) {
                    continue;
                }
                $postDetails = json_decode(file_get_contents($postFile), true);

                // Cắt ngắn mô tả nếu quá dài
                $description = strlen($postDetails['description']) > 150 ? substr($postDetails['description'], 0, 150) . "..." : $postDetails['description'];

                // Tạo link bài viết chỉ sử dụng unique_id
                $postLink = "/lightup/posts.php?id=" . $post['unique_id'];
                ?>
                <div class="bg-gray-800 p-4 rounded-lg shadow-lg">
                    <?php if ($postDetails['thumbnail']): ?>
                        <a href="<?= $postLink ?>">
                            <img src="post-data/<?= $post['unique_id'] ?>/<?= $postDetails['thumbnail'] ?>" alt="<?= htmlspecialchars($postDetails['title']) ?>" class="w-full h-40 object-cover rounded">
                        </a>
                    <?php endif; ?>
                    <h2 class="text-lg font-bold text-sunset-yellow mt-4">
                        <a href="<?= $postLink ?>"><?= htmlspecialchars($postDetails['title']) ?></a>
                    </h2>
                    <p class="text-sm text-gray-400 mt-2"><?= htmlspecialchars($description) ?></p>

                    <div class="flex justify-between mt-4">
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <a href="edit-post.php?id=<?= $post['unique_id'] ?>" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Edit</a>
                            <button onclick="showDeleteModal('<?= $post['unique_id'] ?>')" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Delete</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal xác nhận xóa bài viết -->
    <div id="deleteModal" class="fixed inset-0 hidden z-50 bg-black bg-opacity-50 flex justify-center items-center">
        <div class="bg-gray-800 text-text-light rounded-lg p-6 max-w-md w-full">
            <h2 class="text-lg font-bold mb-4">Confirm Delete</h2>
            <p>Are you sure you want to delete this post?</p>
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
            fetch('delete-post.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        unique_id: deleteUniqueId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Post deleted successfully!');
                        location.reload();
                    } else {
                        alert('Error deleting post: ' + data.message);
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