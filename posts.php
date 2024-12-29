<?php
session_start();

// Kiểm tra xem người dùng có đăng nhập chưa
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $redirectUrl = urlencode($_SERVER['REQUEST_URI']);
    header("Location: login.php?redirect=$redirectUrl");
    exit;
}

// Lấy tham số unique_id từ URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Post not found!");
}
$uniqueId = $_GET['id'];
$postFile = __DIR__ . "/post-data/{$uniqueId}/{$uniqueId}.json";

// Kiểm tra xem bài viết có tồn tại không
if (!file_exists($postFile)) {
    die("Post not found!");
}

// Đọc dữ liệu bài viết từ tệp JSON
$postData = json_decode(file_get_contents($postFile), true);

// Nếu không tìm thấy dữ liệu, hiển thị thông báo lỗi
if (!$postData) {
    die("Post data is not available.");
}

// Lấy thông tin bài viết
$title = htmlspecialchars($postData['title']);
$alias = htmlspecialchars($postData['alias']);
$description = htmlspecialchars($postData['description']);
$tags = htmlspecialchars($postData['tags']);
$metaTitle = htmlspecialchars($postData['meta_title']);
$metaDescription = htmlspecialchars($postData['meta_description']);
$metaKeywords = htmlspecialchars($postData['meta_keywords']);
$content = $postData['content']; // Dữ liệu HTML
$categoryId = $postData['category']; // Lấy category của bài viết
$thumbnail = $postData['thumbnail'] ? "/post-data/{$uniqueId}/{$postData['thumbnail']}" : null;
$postedDate = new DateTime($postData['posted_date']);
$author = htmlspecialchars($postData['username']);
$categories = json_decode(file_get_contents(__DIR__ . '/json/post-category.json'), true);

// Tạo URL của bài viết
$postUrl = "/lightup/posts.php?id={$uniqueId}";

// Tiêu đề trang và meta thông tin SEO
$pageTitle = $metaTitle ?: $title;
$pageDescription = $metaDescription ?: $description;
$pageKeywords = $metaKeywords ?: $tags;
?>

<?php include 'header.php'; ?>
<?php include 'menu.php'; ?>


<!-- Container to wrap breadcrumb and thumbnail together -->
<div class="container mx-auto p-4">
    <!-- Breadcrumb & Thumbnail in a flex container -->
    <div class="flex flex-col mb-6">
        <!-- Breadcrumb -->
        <div class="text-sm text-gray-400 mb-4">
            <a href="/" class="hover:underline">Home</a> / 
            <?php 
            $categoryName = "Unknown Category"; // Default category name if not found
            if (isset($categories[$categoryId])) {
                $categoryName = $categories[$categoryId]['name']; 
            }
            ?>
            <a href="/category/<?= htmlspecialchars($categoryId) ?>" class="hover:underline"><?= htmlspecialchars($categoryName) ?></a> /
            <span class="text-gray-500"><?= htmlspecialchars($title) ?></span>
        </div>

        <!-- Thumbnail -->
        <?php if ($thumbnail): ?>
            <div class="max-w-3xl mx-auto mb-6">
                <img src="post-data/<?= $uniqueId ?>/<?= $postData['thumbnail'] ?>" alt="Thumbnail" class="w-full h-auto object-cover rounded-lg">
            </div>
        <?php endif; ?>
    </div>

    <!-- Article Content -->
    <article class="max-w-3xl mx-auto p-6 bg-gray-800 rounded-lg shadow-lg">
        <!-- Title -->
        <h1 class="text-4xl font-bold text-white text-center mb-4"><?= $title ?></h1>

        <!-- Meta Info (Author, Published Date) -->
        <div class="flex justify-between text-sm text-gray-400 mb-6">
            <p><strong class="text-white">Author:</strong> <?= $author ?></p>
            <p><strong class="text-white">Published:</strong> <?= $postedDate->format('d/m/Y H:i') ?></p>
        </div>

        <!-- Content -->
        <div class="prose prose-invert text-white mb-6">
            <?= $content ?> <!-- Nội dung bài viết được lưu dưới dạng HTML -->
        </div>

        <!-- Categories -->
        <div class="flex justify-center space-x-4">
            <?php foreach ($categories as $categoryId => $categoryName): ?>
                <?php if ($categoryId == $postData['category']): ?>
                    <span class="text-lg text-gray-400"><?= htmlspecialchars($categoryName['name']) ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </article>
</div>


<?php include 'footer.php'; ?>
