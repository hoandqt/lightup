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
$canonicalURL = "https://www.lightup.tv/add-post.php";
include 'header.php';
include 'menu.php';

// Helper function to generate a unique 9-character string
function generateUniqueId($length = 9)
{
    return substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'), 0, $length);
}

// Helper function to create a URL-friendly alias
function generateAlias($title)
{
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
}

//Load categories from post-category.json
$categoryFile = __DIR__ . '/json/post-category.json';
$categories = file_exists($categoryFile) ? json_decode(file_get_contents($categoryFile), true) : [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $tags = formatCommaSeparatedInput($_POST['tags']);
    $metaTitle = $_POST['meta_title'];
    $metaDescription = $_POST['meta_description'];
    $metaKeywords = formatCommaSeparatedInput($_POST['meta_keywords']);
    $content = $_POST['content']; // Post content from CKEditor 5
    $category = $_POST['category']; // Category

    $uniqueId = generateUniqueId();
    $alias = generateAlias($title);
    $username = $_SESSION['username']; // Lấy tên người dùng từ phiên
    $currentDate = date('Y-m-d H:i:s');
    $status = $_POST['status'] ?? 'public'; // Lấy trạng thái từ form hoặc mặc định là "public"
    $postData['status'] = $status;
    $scheduledDate = $_POST['scheduled_date'] ?? null;
    $scheduledDate = $scheduledDate ? date('Y-m-d H:i:s', strtotime($scheduledDate)) : null; // Đảm bảo định dạng đúng


    // Tạo thư mục lưu trữ bài viết
    $baseDir = __DIR__ . '/post-data/';
    $postDir = $baseDir . $uniqueId . '/';
    if (!is_dir($baseDir)) mkdir($baseDir, 0755, true);
    if (!is_dir($postDir)) mkdir($postDir, 0755, true);

    // Handle thumbnail upload
    $thumbnailPath = null;
    if (!empty($_FILES['thumbnail']['name'])) {
        $allowedThumbnailTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (in_array($_FILES['thumbnail']['type'], $allowedThumbnailTypes)) {
            $thumbnailPath = $postDir . 'thumbnail_' . basename($_FILES['thumbnail']['name']);
            move_uploaded_file($_FILES['thumbnail']['tmp_name'], $thumbnailPath);
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
                $path = $postDir . 'file_' . basename($name);
                move_uploaded_file($_FILES['additional_files']['tmp_name'][$key], $path);
                $additionalFiles[] = $path;
            }
        }
    }

    // Save post data to a JSON file
    $postData = [
        'unique_id' => $uniqueId,
        'title' => $title,
        'alias' => $alias,
        'description' => $description,
        'tags' => $tags,
        'meta_title' => $metaTitle,
        'meta_description' => $metaDescription,
        'meta_keywords' => $metaKeywords,
        'thumbnail' => $thumbnailPath ? basename($thumbnailPath) : null,
        'content' => $content,  // Nội dung bài viết từ CKEditor 5
        'category' => $category,  // Lưu thể loại
        'additional_files' => array_map('basename', $additionalFiles),
        'posted_date' => $currentDate,
        'updated_date' => $currentDate,
        'scheduled_date' => $scheduledDate, // Lưu thời gian đăng bài
        'username' => $username,
        'status' => 'public', // Thêm trường trạng thái mặc định là "public"
    ];
    file_put_contents($postDir . $uniqueId . '.json', json_encode($postData, JSON_PRETTY_PRINT));

    // Update or create content.json
    $contentFile = __DIR__ . '/json/post-content.json';
    $content = file_exists($contentFile) ? json_decode(file_get_contents($contentFile), true) : [];
    $content[$uniqueId] = [
        'unique_id' => $uniqueId,
        'posted_date' => $currentDate,
        'updated_date' => $currentDate,
    ];
    file_put_contents($contentFile, json_encode($content, JSON_PRETTY_PRINT));

    echo "<p class='text-green-500 px-8 pt-8'>Post saved successfully!</p>";
}
?>

<!-- Form thêm bài viết -->
<div class="container mx-auto p-8">
    <h1 class="text-3xl font-bold text-text-light mb-6">Add New Blog Post</h1>
    <form action="add-post.php" method="POST" enctype="multipart/form-data" class="bg-gray-800 shadow-lg rounded-lg p-6 space-y-6">
        <div>
            <label for="title" class="block text-sm font-medium text-text-light">Title</label>
            <input type="text" name="title" id="title" required
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2">
        </div>

        <div>
            <label for="description" class="block text-sm font-medium text-text-light">Description</label>
            <textarea name="description" id="description" rows="5" required
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2"></textarea>
        </div>

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

        <div>
            <label for="tags" class="block text-sm font-medium text-text-light">Tags</label>
            <textarea name="tags" id="tags" rows="3"
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2"></textarea>
        </div>

        <div>
            <label for="meta_title" class="block text-sm font-medium text-text-light">Meta Title</label>
            <input type="text" name="meta_title" id="meta_title" required
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2">
        </div>

        <div>
            <label for="meta_description" class="block text-sm font-medium text-text-light">Meta Description</label>
            <textarea name="meta_description" id="meta_description" rows="3" required
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2"></textarea>
        </div>

        <div>
            <label for="meta_keywords" class="block text-sm font-medium text-text-light">Meta Keywords</label>
            <textarea name="meta_keywords" id="meta_keywords" rows="3" required
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2"></textarea>
        </div>

        <div>
            <label for="status" class="block text-sm font-medium text-text-light">Status</label>
            <select name="status" id="status" class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md p-2">
                <option value="public" selected>Public</option>
                <option value="private">Private</option>
            </select>
        </div>

        <!-- Thêm trường Scheduled Date and Time -->
        <div>
            <label for="scheduled_date" class="block text-sm font-medium text-text-light">Scheduled Date and Time</label>
            <input type="datetime-local" name="scheduled_date" id="scheduled_date"
                class="mt-1 block w-full text-text-light border-gray-600 bg-gray-700 rounded-md p-2">
        </div>


        <!-- CKEditor 5 content section -->
        <div>
            <label for="content" class="block text-sm font-medium text-text-light">Content</label>
            <!-- Không dùng required trên textarea -->
            <textarea name="content" id="content" rows="10" style="display: none;"></textarea>
        </div>

        <div>
            <label for="thumbnail" class="block text-sm font-medium text-text-light">Thumbnail Image</label>
            <input type="file" name="thumbnail" id="thumbnail" accept="image/*"
                class="mt-1 block w-full text-text-light file:mr-4 file:py-2 file:px-4 file:border file:border-gray-600 file:rounded-md file:text-sm file:bg-gray-700 file:text-text-light hover:file:bg-gray-600">
        </div>

        <div>
            <label for="additional_files" class="block text-sm font-medium text-text-light">Additional Files</label>
            <input type="file" name="additional_files[]" id="additional_files" multiple
                class="mt-1 block w-full text-text-light file:mr-4 file:py-2 file:px-4 file:border file:border-gray-600 file:rounded-md file:text-sm file:bg-gray-700 file:text-text-light hover:file:bg-gray-600">
        </div>

        <div class="mt-4">
            <button type="submit" class="px-6 py-3 bg-blue-500 text-white rounded-lg">Add Post</button>
        </div>
    </form>
</div>
<!-- Nhúng CKEditor 5 từ CDN với đầy đủ các plugin cần thiết -->
<script src="https://cdn.ckeditor.com/ckeditor5/36.0.1/classic/ckeditor.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Khởi tạo CKEditor 5 với các plugin cần thiết
        ClassicEditor
            .create(document.querySelector('#content'), {
                toolbar: [
                    'bold',
                    'italic',
                    'link',
                    'bulletedList',
                    'numberedList',
                    'blockQuote',
                    'insertTable',
                    'imageUpload', // Plugin Image Upload
                ],
                image: {
                    toolbar: ['imageTextAlternative', 'imageStyle:full', 'imageStyle:side']
                },
                table: {
                    contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells']
                },
                // Các plugin cần thiết cho fontSize và fontColor
                language: 'en',
                plugins: [
                    'Bold', 'Italic', 'Link', 'ImageUpload', 'BlockQuote',
                    'List', 'Table', 'TableToolbar', 'Heading', 'Paragraph'
                ]
            })
            .then(editor => {
                console.log('CKEditor 5 is ready!');
            })
            .catch(error => {
                console.error('There was a problem initializing CKEditor 5:', error);
            });
    });
</script>


<?php include 'footer.php'; ?>