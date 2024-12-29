<?php
session_start();

require_once "functions.php";

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $redirectUrl = urlencode($_SERVER['REQUEST_URI']);
    header("Location: login.php?redirect=$redirectUrl");
    exit;
}

// Get post unique ID from the query parameter
$uniqueId = $_GET['id'] ?? null;
if (!$uniqueId) {
    echo "<div class='site-notification text-red-500 error'>Error: Post ID not provided.</div>";
    exit;
}

$pageTitle = "Edit Post Details";
$pageDescription = "Edit Post Details on LightUp.TV";
$pageKeywords = "";
$canonicalURL = "https://www.lightup.tv/edit-post.php?id=$uniqueId";
include 'header.php';
include 'menu.php';

// Load categories from post-category.json
$categoryFile = __DIR__ . '/json/post-category.json';
$categories = file_exists($categoryFile) ? json_decode(file_get_contents($categoryFile), true) : [];

// Path to the post JSON file
$postDir = __DIR__ . "/post-data/$uniqueId/";
$jsonFilePath = $postDir . "$uniqueId.json";
$contentFile = __DIR__ . '/json/post-content.json';

// Check if the JSON file exists
if (!file_exists($jsonFilePath)) {
    echo "<div class='site-notification text-red-500 error'>Error: Post not found.</div>";
    exit;
}

// Read the JSON data
$postData = json_decode(file_get_contents($jsonFilePath), true);
$additionalFiles = $postData['additional_files'] ?? [];

// Load content.json and retrieve the posted_date
$contentData = file_exists($contentFile) ? json_decode(file_get_contents($contentFile), true) : [];
$postedDate = $contentData[$uniqueId]['posted_date'] ?? $postData['posted_date'] ?? date('Y-m-d H:i:s');

// Handle form submission to update the post details
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postData['title'] = $_POST['title'];
    $postData['description'] = $_POST['description'];
    $postData['tags'] = formatCommaSeparatedInput($_POST['tags']);
    $postData['meta_title'] = $_POST['meta_title'];
    $postData['meta_description'] = $_POST['meta_description'];
    $postData['meta_keywords'] = formatCommaSeparatedInput($_POST['meta_keywords']);
    $postData['content'] = $_POST['content'];
    $postData['category'] = $_POST['category'];
    $postData['updated_date'] = date('Y-m-d H:i:s');
    $postData['status'] = $_POST['status']; // Lưu trạng thái Private/Public
    // Cập nhật ngày giờ đăng bài nếu có thay đổi
    $postData['posted_date'] = $_POST['posted_date'] ?? $postedDate;

    // Update alias if the title changes
    $postData['alias'] = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $postData['title']), '-'));

    // Handle new thumbnail upload
    if (!empty($_FILES['thumbnail']['name'])) {
        $allowedThumbnailTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (in_array($_FILES['thumbnail']['type'], $allowedThumbnailTypes)) {
            // Remove old thumbnail if it exists
            if (!empty($postData['thumbnail']) && file_exists($postDir . $postData['thumbnail'])) {
                unlink($postDir . $postData['thumbnail']);
            }
            $thumbnailPath = 'thumbnail_' . basename($_FILES['thumbnail']['name']);
            move_uploaded_file($_FILES['thumbnail']['tmp_name'], $postDir . $thumbnailPath);
            $postData['thumbnail'] = $thumbnailPath;
        }
    }

    // Preserve existing additional_files
    if (!isset($postData['additional_files'])) {
        $postData['additional_files'] = [];
    }

    // Handle new additional files upload
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
                $additionalFiles[] = 'file_' . basename($name);
            }
        }
    }

    // Filter out removed additional files and delete them from the server
    $removedFiles = isset($_POST['removed_files']) ? json_decode($_POST['removed_files'], true) : [];
    foreach ($removedFiles as $fileToRemove) {
        $decodedFileName = urldecode($fileToRemove); // Decode to the original file name
        $filePath = $postDir . $decodedFileName;
        if (file_exists($filePath)) {
            unlink($filePath); // Delete the file from the server
        }

        // Remove the file entry from the JSON data
        $additionalFiles = array_filter($additionalFiles, function ($file) use ($decodedFileName) {
            return $file !== $decodedFileName;
        });
    }
    $postData['additional_files'] = array_diff($additionalFiles, $removedFiles);

    // Save updated data to the JSON file
    file_put_contents($jsonFilePath, json_encode($postData, JSON_PRETTY_PRINT));

    // Update or create content.json
    $content = file_exists($contentFile) ? json_decode(file_get_contents($contentFile), true) : [];
    $content[$uniqueId] = [
        'unique_id' => $uniqueId,
        'posted_date' => $postedDate,
        'updated_date' => $postData['updated_date'],
        'status' => $postData['status'], // Thêm trạng thái vào content.json
    ];
    file_put_contents($contentFile, json_encode($content, JSON_PRETTY_PRINT));

    echo "<div class='site-notification text-green-500 success'>Post information updated successfully!</div>";
}
?>

<div class="container mx-auto p-8">
    <h1 class="text-3xl font-bold text-text-light mb-6">Edit Post Details</h1>
    <form action="" method="POST" enctype="multipart/form-data" class="bg-gray-800 shadow-lg rounded-lg p-6 space-y-6">

        <!-- Title -->
        <div>
            <label for="title" class="block text-sm font-medium text-text-light">Title</label>
            <input type="text" name="title" id="title" value="<?= htmlspecialchars($postData['title']) ?>" required
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md p-2">
        </div>

        <!-- Description -->
        <div>
            <label for="description" class="block text-sm font-medium text-text-light">Description</label>
            <textarea name="description" id="description" rows="5" required
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md p-2"><?= htmlspecialchars($postData['description']) ?></textarea>
        </div>

        <!-- Tags -->
        <div>
            <label for="tags" class="block text-sm font-medium text-text-light">Tags</label>
            <textarea name="tags" id="tags" rows="3" required
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md p-2"><?= htmlspecialchars($postData['tags']) ?></textarea>
        </div>

        <!-- Category Dropdown -->
        <div>
            <label for="category" class="block text-sm font-medium text-text-light">Category</label>
            <select name="category" id="category" required class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md p-2">
                <option value="">Select a Category</option>
                <?php foreach ($categories as $categoryId => $category): ?>
                    <option value="<?= htmlspecialchars($categoryId) ?>" <?= $postData['category'] == $categoryId ? 'selected' : '' ?>>
                        <?= htmlspecialchars($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Thumbnail -->
        <div>
            <label for="thumbnail" class="block text-sm font-medium text-text-light">Thumbnail Image</label>
            <input type="file" name="thumbnail" id="thumbnail" accept=".jpeg, .jpg, .png, .webp"
                class="mt-1 block w-full text-text-light file:bg-gray-700 file:text-text-light hover:file:bg-gray-600">
        </div>

        <!-- Status (Private/Public) -->
        <div>
            <label for="status" class="block text-sm font-medium text-text-light">Status</label>
            <select name="status" id="status" required class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md p-2">
                <option value="public" <?= $postData['status'] === 'public' ? 'selected' : '' ?>>Public</option>
                <option value="private" <?= $postData['status'] === 'private' ? 'selected' : '' ?>>Private</option>
            </select>
        </div>

        <!-- CKEditor 5 content section -->
        <div>
            <label for="content" class="block text-sm font-medium text-text-light">Content</label>
            <!-- Không dùng required trên textarea -->
            <textarea name="content" id="content" rows="10" style="display: none;"></textarea>
        </div>

        <!-- Additional Files -->
        <div>
            <label for="additional_files" class="block text-sm font-medium text-text-light">Additional Files</label>
            <input type="file" name="additional_files[]" id="additional_files" multiple
                class="mt-1 block w-full text-text-light file:bg-gray-700 file:text-text-light hover:file:bg-gray-600">
        </div>

        <!-- Posted Date -->
        <div>
            <label for="posted_date" class="block text-sm font-medium text-text-light">Posted Date</label>
            <input type="datetime-local" name="posted_date" id="posted_date" value="<?= htmlspecialchars($postedDate) ?>" required
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md p-2">
        </div>


        <!-- Meta Tags -->
        <div>
            <label for="meta_title" class="block text-sm font-medium text-text-light">Meta Title</label>
            <input type="text" name="meta_title" id="meta_title" value="<?= htmlspecialchars($postData['meta_title']) ?>"
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md p-2">
        </div>

        <div>
            <label for="meta_description" class="block text-sm font-medium text-text-light">Meta Description</label>
            <textarea name="meta_description" id="meta_description" rows="3"
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md p-2"><?= htmlspecialchars($postData['meta_description']) ?></textarea>
        </div>

        <div>
            <label for="meta_keywords" class="block text-sm font-medium text-text-light">Meta Keywords</label>
            <input type="text" name="meta_keywords" id="meta_keywords" value="<?= htmlspecialchars($postData['meta_keywords']) ?>"
                class="mt-1 block w-full border-gray-600 bg-gray-700 text-text-light rounded-md p-2">
        </div>

        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white p-2 rounded-md">Save Changes</button>
    </form>

</div>


<!-- Nhúng CKEditor 5 từ CDN -->
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